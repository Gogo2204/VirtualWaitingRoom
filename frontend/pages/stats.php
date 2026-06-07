<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Statistics</title>
</head>
<body>

<h2>Statistics</h2>
<p><a href="/rooms">← Rooms</a></p>
<p id="msg"></p>

<h3>By Subject</h3>
<div id="subject-stats"><em>Loading…</em></div>

<h3>By Room</h3>
<label>Room ID: <input type="number" id="room-id-input" min="1"></label>
<button onclick="loadRoomStats()">Load</button>
<div id="room-stats"></div>

<?php require_once __DIR__ . '/../partials/app.js.php'; ?>
<script>
requireAuth('teacher');

function fmtSeconds(s) {
    if (s === null || s === undefined) return '—';
    const m   = Math.floor(s / 60);
    const sec = Math.round(s % 60);
    return m > 0 ? `${m}m ${sec}s` : `${sec}s`;
}

function fmtHour(h) {
    if (h === null || h === undefined) return '—';
    return `${String(h).padStart(2, '0')}:00`;
}

async function loadSubjectStats() {
    try {
        const data = await api('GET', '/api/stats/subjects');
        const container = document.getElementById('subject-stats');
        if (!data.stats.length) {
            container.textContent = 'No data yet.';
            return;
        }
        const table = document.createElement('table');
        table.innerHTML = `<tr>
            <th>Subject</th>
            <th>Rooms</th>
            <th>Students Served</th>
            <th>Avg Queue Time</th>
            <th>Avg Meeting Time</th>
        </tr>`;
        data.stats.forEach(s => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${s.subject_type}</td>
                <td>${s.room_count}</td>
                <td>${s.students_served}</td>
                <td>${fmtSeconds(s.avg_queue_seconds)}</td>
                <td>${fmtSeconds(s.avg_meeting_seconds)}</td>
            `;
            table.appendChild(tr);
        });
        container.innerHTML = '';
        container.appendChild(table);
    } catch (err) {
        setMsg('msg', err.message);
    }
}

async function loadRoomStats() {
    const roomId = parseInt(document.getElementById('room-id-input').value);
    if (!roomId) return;
    const container = document.getElementById('room-stats');
    try {
        const data = await api('GET', `/api/stats/rooms/${roomId}`);
        const s = data.stats;
        container.innerHTML = `<table>
            <tr><th>Students Served</th><td>${s.students_served}</td></tr>
            <tr><th>Avg Queue Time</th><td>${fmtSeconds(s.avg_queue_seconds)}</td></tr>
            <tr><th>Avg Meeting Time</th><td>${fmtSeconds(s.avg_meeting_seconds)}</td></tr>
            <tr><th>Peak Hour</th><td>${fmtHour(s.peak_hour)}</td></tr>
        </table>`;
    } catch (err) {
        setMsg('msg', err.message);
    }
}

loadSubjectStats();
</script>

</body>
</html>
