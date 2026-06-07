<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Room</title>
</head>
<body>

<h2>Create Room</h2>
<p><a href="/rooms">← Rooms</a></p>

<input type="text"   id="name"              placeholder="Room name" /><br>
<select id="subject_id"><option value="">Loading subjects…</option></select><br>
<textarea id="description" placeholder="Description (optional)" rows="3" cols="40"></textarea><br>
<input type="number" id="wait_time_minutes" placeholder="Wait time per person (minutes)" value="15" min="1" /><br>
<input type="text"   id="url"              placeholder="Meeting URL (optional)" /><br>
<button onclick="createRoom()">Create</button>

<p id="msg"></p>

<?php require_once __DIR__ . '/../partials/app.js.php'; ?>
<script>
requireAuth('teacher');

(async () => {
    try {
        const data   = await api('GET', '/api/subjects');
        const select = document.getElementById('subject_id');
        select.innerHTML = '';
        data.subjects.forEach(s => {
            const opt      = document.createElement('option');
            opt.value       = s.id;
            opt.textContent = s.name || s.type;
            select.appendChild(opt);
        });
    } catch (err) {
        setMsg('msg', 'Could not load subjects: ' + err.message);
    }
})();

async function createRoom() {
    const payload = {
        name:              document.getElementById('name').value.trim(),
        subject_id:        parseInt(document.getElementById('subject_id').value) || null,
        description:       document.getElementById('description').value.trim(),
        wait_time_minutes: parseInt(document.getElementById('wait_time_minutes').value) || 15,
        url:               document.getElementById('url').value.trim(),
    };

    try {
        const data = await api('POST', '/api/rooms', payload);
        window.location.href = `/rooms/${data.room.id}`;
    } catch (err) {
        setMsg('msg', err.message);
    }
}
</script>

</body>
</html>
