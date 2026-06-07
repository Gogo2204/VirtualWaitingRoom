<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Room Queue</title>
</head>
<body>

<h2 id="room-title">Queue</h2>
<p><a href="/rooms">← Rooms</a></p>
<p id="msg"></p>

<div id="teacher-controls" style="display:none">
    <button onclick="setStatus('open')">Open</button>
    <button onclick="setStatus('closed')">Close</button>
    <button onclick="setStatus('archived')">Archive</button>
    &nbsp;
    <button onclick="loadQueue()">Refresh</button>
</div>

<div id="student-controls" style="display:none">
    <button id="join-btn"  onclick="joinQueue()">Join Queue</button>
    <button id="leave-btn" onclick="leaveQueue()" style="display:none">Leave Queue</button>
</div>

<div id="meeting-link" style="display:none">
    <strong>Meeting link:</strong> <a id="meeting-href" href="#" target="_blank"></a>
</div>

<div id="queue-container"><em>Loading…</em></div>

<?php require_once __DIR__ . '/../partials/app.js.php'; ?>
<script>
const roomId = <?= (int)$roomId ?>;
const user   = requireAuth();
let   myItem = null;

function fmtSeconds(s) {
    if (s === null || s === undefined) return '—';
    const m = Math.floor(s / 60);
    const sec = s % 60;
    return m > 0 ? `${m}m ${sec}s` : `${sec}s`;
}

async function loadQueue() {
    try {
        const data = await api('GET', `/api/rooms/${roomId}/queue`);
        myItem = null;
        renderQueue(data.queue);
    } catch (err) {
        setMsg('msg', err.message);
    }
}

function renderQueue(queue) {
    const container = document.getElementById('queue-container');
    container.innerHTML = '';

    if (!queue.length) {
        container.textContent = 'Queue is empty.';
        updateStudentControls(null);
        return;
    }

    queue.forEach(item => {
        if (String(item.student_id) === String(user.id)) myItem = item;

        const div   = document.createElement('div');
        let   parts = [`#${item.position} — ${item.first_name} ${item.last_name} [${item.status}]`];

        if (item.eta && item.status === 'waiting') {
            parts.push(`ETA: ${new Date(item.eta).toLocaleTimeString()}`);
        }

        if (item.status === 'done' && item.times) {
            parts.push(`| Queue: ${fmtSeconds(item.times.queue_seconds)} | Meeting: ${fmtSeconds(item.times.meeting_seconds)}`);
        }

        if (item.comments && item.comments.length) {
            const cmtHtml = item.comments.map(c =>
                `<em>[${c.visibility === 'teacher_only' ? 'private' : 'public'}] ${c.first_name} ${c.last_name}: ${c.content}</em>`
            ).join('<br>');
            parts.push(`<br>${cmtHtml}`);
        }

        if (user.role === 'teacher') {
            if (item.status === 'waiting') {
                parts.push(`<button onclick="invite(${item.id},'temp')">Temp invite</button>`);
                parts.push(`<button onclick="invite(${item.id},'perm')">Perm invite</button>`);
                parts.push(`<input type="datetime-local" id="slot-${item.id}">`);
                parts.push(`<button onclick="if(document.getElementById('slot-${item.id}').value) setSlot(${item.id})">Set slot</button>`);
            }
            if (item.status === 'invited_temp') {
                parts.push(`<button onclick="studentReturn(${item.id})">Mark returned</button>`);
            }
            if (item.status === 'invited_perm') {
                parts.push(`<button onclick="finishMeeting(${item.id})">Finish meeting</button>`);
            }
            parts.push(`<br><input type="text" id="cmt-${item.id}" placeholder="Add comment…" size="30"><select id="vis-${item.id}"><option value="teacher_only">Private</option><option value="public">Public</option></select><button onclick="addComment(${item.id})">Add</button>`);
        }

        if (user.role === 'student' && item.status !== 'done') {
            const myOwn = String(item.student_id) === String(user.id);
            if (myOwn) {
                parts.push(`<br><input type="text" id="cmt-${item.id}" placeholder="Add comment…" size="30"><select id="vis-${item.id}"><option value="public">Public</option><option value="teacher_only">Private</option></select><button onclick="addComment(${item.id})">Add</button>`);
            } else {
                parts.push(`<br><input type="text" id="cmt-${item.id}" placeholder="Add public comment…" size="30"><button onclick="addComment(${item.id})">Add</button>`);
            }
        }

        div.innerHTML = parts.join(' ');
        container.appendChild(div);
    });

    updateStudentControls(myItem);
}

function updateStudentControls(item) {
    if (user.role !== 'student') return;

    const inQueue  = item && item.status !== 'done';
    const canLeave = item && item.status === 'waiting';
    const inMeet   = item && (item.status === 'invited_perm') && item.meeting_link;

    document.getElementById('join-btn').style.display  = item ? 'none' : 'inline';
    document.getElementById('leave-btn').style.display = canLeave ? 'inline' : 'none';

    const linkDiv = document.getElementById('meeting-link');
    if (inMeet) {
        document.getElementById('meeting-href').href        = item.meeting_link || '#';
        document.getElementById('meeting-href').textContent = item.meeting_link || '(link pending)';
        linkDiv.style.display = 'block';
    } else {
        linkDiv.style.display = 'none';
    }
}

async function joinQueue() {
    try {
        await api('POST', `/api/rooms/${roomId}/queue`);
        setMsg('msg', 'Joined queue.');
        loadQueue();
    } catch (err) {
        setMsg('msg', err.message);
    }
}

async function leaveQueue() {
    if (!myItem) return;
    try {
        await api('DELETE', `/api/rooms/${roomId}/queue`, { room_item_id: myItem.id });
        setMsg('msg', 'Left queue.');
        loadQueue();
    } catch (err) {
        setMsg('msg', err.message);
    }
}

async function invite(itemId, mode) {
    try {
        const data = await api('POST', `/api/rooms/${roomId}/queue/${itemId}/invite`, { mode });
        setMsg('msg', `Invited. Link: ${data.meeting_link || '—'}`);
        loadQueue();
    } catch (err) {
        setMsg('msg', err.message);
    }
}

async function finishMeeting(itemId) {
    try {
        const data = await api('POST', `/api/rooms/${roomId}/queue/${itemId}/finish`);
        const t = data.times ?? {};
        setMsg('msg', `Done. Queue time: ${fmtSeconds(t.queue_seconds)}, Meeting time: ${fmtSeconds(t.meeting_seconds)}`);
        loadQueue();
    } catch (err) {
        setMsg('msg', err.message);
    }
}

async function studentReturn(itemId) {
    try {
        await api('POST', `/api/rooms/${roomId}/queue/${itemId}/return`);
        setMsg('msg', 'Student returned to queue.');
        loadQueue();
    } catch (err) {
        setMsg('msg', err.message);
    }
}

async function setSlot(itemId) {
    const val = document.getElementById(`slot-${itemId}`).value;
    try {
        await api('POST', `/api/rooms/${roomId}/queue/${itemId}/slot`, { datetime: val });
        setMsg('msg', 'Slot set.');
        loadQueue();
    } catch (err) {
        setMsg('msg', err.message);
    }
}

async function addComment(itemId) {
    const content    = document.getElementById(`cmt-${itemId}`)?.value?.trim();
    const visibility = document.getElementById(`vis-${itemId}`)?.value ?? 'teacher_only';
    if (!content) return;
    try {
        await api('POST', `/api/rooms/${roomId}/queue/${itemId}/comments`, { content, visibility });
        setMsg('msg', 'Comment added.');
        loadQueue();
    } catch (err) {
        setMsg('msg', err.message);
    }
}

async function setStatus(status) {
    try {
        await api('PATCH', `/api/rooms/${roomId}/status`, { status });
        setMsg('msg', `Room status: ${status}.`);
    } catch (err) {
        setMsg('msg', err.message);
    }
}

(async () => {
    if (user.role === 'teacher' || user.role === 'admin') {
        document.getElementById('teacher-controls').style.display = 'block';
    } else {
        document.getElementById('student-controls').style.display = 'block';
    }
    await loadQueue();
})();
</script>

</body>
</html>
