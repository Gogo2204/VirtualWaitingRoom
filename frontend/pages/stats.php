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

<div id="teacher-section" style="display:none">
    <h3>By Subject</h3>
    <div id="subject-stats"><em>Loading…</em></div>
</div>

<h3>Room Stats</h3>
<div id="student-room-section" style="display:none">
    <label>Select room: <select id="room-select"><option value="">—</option></select></label>
    <button onclick="loadRoomStatsFromSelect()">Load</button>
</div>
<div id="teacher-room-section" style="display:none">
    <label>Room ID: <input type="number" id="room-id-input" min="1"></label>
    <button onclick="loadRoomStatsFromInput()">Load</button>
</div>
<div id="room-stats"></div>

<?php require_once __DIR__ . '/../partials/app.js.php'; ?>
<script>
const user = requireAuth('teacher', 'student', 'admin');
let activeRoomId = null;
let refreshTimer  = null;

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

function renderRoomStats(s) {
    document.getElementById('room-stats').innerHTML = `<table border="1" cellpadding="4">
        <tr><th>Currently Waiting</th><td>${s.currently_waiting}</td></tr>
        <tr><th>Currently In Meeting</th><td>${s.currently_in_meeting}</td></tr>
        <tr><th>Students Served (done)</th><td>${s.students_served}</td></tr>
        <tr><th>Avg Queue Time</th><td>${fmtSeconds(s.avg_queue_seconds)}</td></tr>
        <tr><th>Avg Meeting Time</th><td>${fmtSeconds(s.avg_meeting_seconds)}</td></tr>
        <tr><th>Peak Hour</th><td>${fmtHour(s.peak_hour)}</td></tr>
    </table>`;
}

async function loadRoomStats(roomId) {
    try {
        const data = await api('GET', `/api/stats/rooms/${roomId}`);
        renderRoomStats(data.stats);
    } catch (err) {
        setMsg('msg', err.message);
    }
}

function startAutoRefresh(roomId) {
    if (refreshTimer) clearInterval(refreshTimer);
    activeRoomId  = roomId;
    refreshTimer  = setInterval(() => loadRoomStats(activeRoomId), 5000);
}

async function loadRoomStatsFromSelect() {
    const roomId = parseInt(document.getElementById('room-select').value);
    if (!roomId) return;
    await loadRoomStats(roomId);
    startAutoRefresh(roomId);
}

async function loadRoomStatsFromInput() {
    const roomId = parseInt(document.getElementById('room-id-input').value);
    if (!roomId) return;
    await loadRoomStats(roomId);
    startAutoRefresh(roomId);
}

async function loadSubjectStats() {
    try {
        const data      = await api('GET', '/api/stats/subjects');
        const container = document.getElementById('subject-stats');
        if (!data.stats.length) {
            container.textContent = 'No data yet.';
            return;
        }
        const table = document.createElement('table');
        table.setAttribute('border', '1');
        table.setAttribute('cellpadding', '4');
        table.innerHTML = `<tr>
            <th>Subject</th><th>Rooms</th><th>Students Served</th>
            <th>Avg Queue Time</th><th>Avg Meeting Time</th>
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

(async () => {
    if (user.role === 'teacher' || user.role === 'admin') {
        document.getElementById('teacher-section').style.display   = 'block';
        document.getElementById('teacher-room-section').style.display = 'block';
        loadSubjectStats();
        setInterval(loadSubjectStats, 5000);
    } else {
        document.getElementById('student-room-section').style.display = 'block';
        try {
            const data   = await api('GET', '/api/rooms');
            const select = document.getElementById('room-select');
            data.rooms.forEach(r => {
                const opt = document.createElement('option');
                opt.value       = r.id;
                opt.textContent = `${r.name} (${r.subject_type})`;
                select.appendChild(opt);
            });
        } catch (err) {
            setMsg('msg', err.message);
        }
    }
})();
</script>

</body>
</html>
