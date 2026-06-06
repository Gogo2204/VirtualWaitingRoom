<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>

<h2>Login</h2>

<input type="email"    id="email"    placeholder="Email" /><br>
<input type="password" id="password" placeholder="Password" /><br>
<button onclick="login()">Login</button>

<p id="msg"></p>

<script>
async function login() {
    const email    = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    try {
        const res = await fetch('/api/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });

        const data = await res.json();

        if (!res.ok) {
            document.getElementById('msg').textContent = data.message ?? 'Login failed.';
            return;
        }

        console.log('Token:', data.token);
        console.log('User:',  data.user);
        document.getElementById('msg').textContent = `Logged in as ${data.user.email}`;

    } catch (err) {
        console.error(err);
        document.getElementById('msg').textContent = 'Network error.';
    }
}
</script>

</body>
</html>