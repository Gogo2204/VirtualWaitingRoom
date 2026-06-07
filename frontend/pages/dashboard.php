<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>

<h2>Dashboard</h2>
<p id="welcome"></p>

<div id="admin-section" style="display:none">
    <hr>
    <h3>Add Teacher</h3>
    <input type="text"  id="first_name" placeholder="First name" /><br>
    <input type="text"  id="last_name"  placeholder="Last name"  /><br>
    <input type="email" id="email"      placeholder="Email"      /><br>
    <button onclick="addTeacher()">Create Teacher</button>
    <p id="msg"></p>
</div>

<br><a href="/change-password">Change password</a>
<br><br><button onclick="logout()">Logout</button>

<script>
    const token = localStorage.getItem('token');
    const user  = JSON.parse(localStorage.getItem('user') || 'null');
    console.log(user)

    if (!token) {
        window.location.href = '/login';
    }

    document.getElementById('welcome').textContent = `Logged in as ${user?.email} (${user?.role})`;

    if (user?.role === 'admin') {
        document.getElementById('admin-section').style.display = 'block';
    }

    async function addTeacher() {
        const first_name = document.getElementById('first_name').value;
        const last_name  = document.getElementById('last_name').value;
        const email      = document.getElementById('email').value;

        try {
            const res = await fetch('/api/users/teacher', {
                method: 'POST',
                headers: {
                    'Content-Type':  'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({ first_name, last_name, email })
            });

            const data = await res.json();

            if (!res.ok) {
                document.getElementById('msg').textContent = data.message ?? 'Failed.';
                return;
            }

            document.getElementById('msg').textContent = `Teacher ${data.user.email} created.`;

        } catch (err) {
            console.error(err);
            document.getElementById('msg').textContent = 'Network error.';
        }
    }

    function logout() {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = '/login';
    }
</script>

</body>
</html>