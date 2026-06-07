<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
</head>
<body>

<h2>Change Password</h2>

<input type="password" id="current_password" placeholder="Current password" /><br>
<input type="password" id="new_password"     placeholder="New password"     /><br>
<input type="password" id="confirm_password" placeholder="Confirm password" /><br>
<button onclick="changePassword()">Change Password</button>

<p id="msg"></p>
<a href="/dashboard">Back to dashboard</a>

<script>
    const token = localStorage.getItem('token');
    if (!token) window.location.href = '/login';

    async function changePassword() {
        const current_password = document.getElementById('current_password').value;
        const new_password     = document.getElementById('new_password').value;
        const confirm_password = document.getElementById('confirm_password').value;

        if (new_password !== confirm_password) {
            document.getElementById('msg').textContent = 'Passwords do not match.';
            return;
        }

        try {
            const res  = await fetch('/api/auth/change-password', {
                method: 'POST',
                headers: {
                    'Content-Type':  'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({ old_password: current_password, new_password })
            });

            const data = await res.json();

            if (!res.ok) {
                document.getElementById('msg').textContent = data.message ?? 'Failed.';
                return;
            }

            localStorage.setItem('token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));

            document.getElementById('msg').textContent = 'Password changed successfully.';
            setTimeout(() => window.location.href = '/dashboard', 1500);

        } catch (err) {
            console.error(err);
            document.getElementById('msg').textContent = 'Network error.';
        }
    }
</script>

</body>
</html>