<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
</head>
<body>

<h2>Student Registration</h2>

<input type="text"     id="first_name"     placeholder="First name"     /><br>
<input type="text"     id="last_name"      placeholder="Last name"      /><br>
<input type="email"    id="email"          placeholder="Email"          /><br>
<input type="password" id="password"       placeholder="Password"       /><br>
<input type="text"     id="faculty_number" placeholder="Faculty number" /><br>
<button onclick="register()">Register</button>

<p>Already have an account? <a href="/login">Login</a></p>
<p id="msg"></p>

<script>
async function register() {
    const payload = {
        first_name:     document.getElementById('first_name').value.trim(),
        last_name:      document.getElementById('last_name').value.trim(),
        email:          document.getElementById('email').value.trim(),
        password:       document.getElementById('password').value,
        faculty_number: document.getElementById('faculty_number').value.trim(),
    };

    try {
        const res  = await fetch('/api/auth/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        if (!res.ok) {
            document.getElementById('msg').textContent = data.message ?? 'Registration failed.';
            return;
        }

        localStorage.setItem('token', data.token);
        localStorage.setItem('user',  JSON.stringify(data.user));

        window.location.href = '/dashboard';

    } catch (err) {
        console.error(err);
        document.getElementById('msg').textContent = 'Network error.';
    }
}
</script>

</body>
</html>