<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rooms</title>
</head>
<body>

<h2>Rooms</h2>
<p><a href="/dashboard">← Dashboard</a></p>

<div id="teacher-section" style="display:none">
    <p><a href="/rooms/create">+ Create Room</a> &nbsp; <a href="/stats">Statistics</a></p>
</div>

<div id="student-section" style="display:none">
    <p><a href="/stats">Statistics</a></p>
</div>

<div id="rooms-list"></div>
<p id="msg"></p>

<?php require_once __DIR__ . '/../partials/app.js.php'; ?>
<script>
const user = requireAuth('teacher', 'student', 'admin');

(async () => {
    if (user.role === 'teacher' || user.role === 'admin') {
        document.getElementById('teacher-section').style.display = 'block';
    } else if (user.role === 'student') {
        document.getElementById('student-section').style.display = 'block';
    }

    try {
        const data = await api('GET', '/api/rooms');
        const list = document.getElementById('rooms-list');

        if (!data.rooms.length) {
            list.textContent = user.role === 'student' ? 'No open rooms available.' : 'No rooms yet.';
            return;
        }

        data.rooms.forEach(room => {
            const div  = document.createElement('div');
            const link = document.createElement('a');
            link.href        = `/rooms/${room.id}`;
            link.textContent = room.name;
            div.appendChild(link);
            div.appendChild(document.createTextNode(` — ${room.subject_type} [${room.status}]`));
            list.appendChild(div);
        });
    } catch (err) {
        setMsg('msg', err.message);
    }
})();
</script>

</body>
</html>
