import json
import os
import re
import requests
from github import Github, Auth


def build_position_map(diff_text: str) -> dict[tuple[str, int], int]:
    """
    Returns a map of (filepath, absolute_line_number) -> diff_position.
    diff_position is the line offset within the PR diff, which GitHub
    requires for suggestions to apply correctly.
    """
    position_map = {}
    current_file = None
    position = 0
    current_line = 0

    for line in diff_text.splitlines():
        if line.startswith("diff --git"):
            current_file = None
            position = 0
        elif line.startswith("+++ b/"):
            current_file = line[6:]
            position = 0
        elif line.startswith("@@ "):
            match = re.search(r"\+(\d+)", line)
            if match:
                current_line = int(match.group(1)) - 1
            position += 1
        elif current_file:
            position += 1
            if line.startswith("+"):
                current_line += 1
                position_map[(current_file, current_line)] = position
            elif not line.startswith("-"):
                current_line += 1

    return position_map


repo_name = os.environ["GITHUB_REPOSITORY"]
pr_number = int(os.environ["PR_NUMBER"])

with open("pr.diff", "r", encoding="utf-8") as f:
    diff = f.read()

MAX_DIFF_SIZE = 40_000
trimmed_diff = diff[:MAX_DIFF_SIZE]

prompt = f"""
You are reviewing a GitHub pull request.

Return ONLY valid JSON - no markdown, no explanation.

Format:
[
  {{
    "path": "relative/file/path.ts",
    "line": 123,
    "severity": "low|medium|high",
    "comment": "Short actionable review comment",
    "suggestion": "Optional improved code snippet or null"
  }}
]

Rules:
- Only report REAL issues
- Maximum 10 comments
- Ignore formatting/style
- Ignore speculative issues
- Use exact file paths from the diff
- Only reference line numbers of ADDED lines (starting with +) from the diff
- Never comment on removed lines (starting with -)
- suggestion must be short and valid code
- A suggestion REPLACES the entire commented line - always include the original line plus any additions
- Return ONLY a JSON array, nothing else

PR Diff:
{trimmed_diff}
"""

response = requests.post(
    "https://openrouter.ai/api/v1/chat/completions",
    headers={
        "Authorization": f"Bearer {os.environ['OPENROUTER_API_KEY']}",
        "Content-Type": "application/json",
    },
    json={
        "model": "anthropic/claude-sonnet-4.5",
        "messages": [{"role": "user", "content": prompt}],
    },
    timeout=120,
)

print("Status:", response.status_code)
print(response.text)

response.raise_for_status()

data = response.json()

if "choices" not in data:
    raise Exception(f"Unexpected response: {data}")

content = data["choices"][0]["message"]["content"].strip()

if content.startswith("```"):
    content = content.split("```")[1]
    if content.startswith("json"):
        content = content[4:]
    content = content.strip()

try:
    reviews = json.loads(content)
except Exception as e:
    print("Failed to parse JSON response")
    print(content)
    raise e

if not reviews:
    print("No review comments generated.")
    exit(0)

position_map = build_position_map(trimmed_diff)

auth = Auth.Token(os.environ["GITHUB_TOKEN"])
g = Github(auth=auth)

repo = g.get_repo(repo_name)
pr = repo.get_pull(pr_number)

comments = []

for review in reviews:
    try:
        body = review["comment"]
        suggestion = review.get("suggestion")

        if suggestion and suggestion.strip():
            body += f"\n\n```suggestion\n{suggestion}\n```"

        path = review["path"]
        line = int(review["line"])
        position = position_map.get((path, line))

        if position is None:
            print(f"Could not find diff position for {path}:{line}, skipping.")
            continue

        comments.append({
            "path": path,
            "position": position,
            "line": line,
            "body": body,
        })
    except Exception as e:
        print(f"Skipping invalid review item: {review}")
        print(e)

if not comments:
    print("No valid comments to post.")
    exit(0)

last_commit = pr.get_commits().reversed[0]

posted = 0
for c in comments:
    try:
        pr.create_review_comment(
            body=c["body"],
            commit=last_commit,
            path=c["path"],
            line=c["line"],
        )
        posted += 1
    except Exception as e:
        print(f"Failed to post comment on {c['path']}:{c['line']}")
        print(e)

print(f"Posted {posted} review comments.")

if posted == 0:
    fallback = "# AI Code Review\n\n"
    for c in comments:
        fallback += f"**`{c['path']}` line {c['line']}**\n\n{c['body']}\n\n---\n\n"
    pr.create_issue_comment(fallback)
    print("Posted fallback issue comment.")