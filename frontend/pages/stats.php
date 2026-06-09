<?php $pageTitle = 'Statistics'; require_once __DIR__ . '/../partials/head.php'; ?>
<?php require_once __DIR__ . '/../partials/nav.php'; ?>

<div class="container-lg py-4">
    <div id="msg" class="text-danger small mb-2"></div>

    <!-- Teacher: subject breakdown -->
    <div id="teacher-section" style="display:none">
        <p class="page-section-title">By Subject</p>
        <div id="subject-stats"><p class="text-muted small"><em>Loading…</em></p></div>
    </div>

    <!-- Room stats -->
    <div class="mt-4">
        <p class="page-section-title">Room Statistics</p>

        <div id="student-room-section" class="mb-3" style="display:none">
            <label class="form-label small">Select room:</label>
            <select id="room-select" class="form-select form-select-sm w-auto">
                <option value="">- pick a room -</option>
            </select>
        </div>

        <div id="teacher-room-section" class="mb-3 d-flex gap-2 align-items-end" style="display:none">
            <div>
                <label class="form-label small mb-1">Room ID:</label>
                <input type="number" id="room-id-input" class="form-control form-control-sm" min="1" placeholder="e.g. 3" style="width:110px">
            </div>
            <button class="btn btn-sm btn-outline-primary" onclick="loadRoomStatsFromInput()">Load</button>
        </div>

        <div id="room-stats"><p class="text-muted small">Select a room to view its statistics.</p></div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/app.js.php'; ?>
<script>
const user = requireAuth('teacher', 'student', 'admin');
let activeRoomId = null;
let subjectData  = [];
let sortCol = null, sortDir = 1;

function fmtHour(h) {
    if (h === null || h === undefined) return '-';
    return `${String(h).padStart(2, '0')}:00`;
}

function sortBy(col) {
    sortDir = (sortCol === col) ? sortDir * -1 : 1;
    sortCol = col;
    renderSubjectTable();
}

function renderSubjectTable() {
    const container = document.getElementById('subject-stats');
    if (!subjectData.length) {
        container.innerHTML = '<p class="text-muted small">No data yet.</p>';
        return;
    }

    const sorted = [...subjectData].sort((a, b) => {
        if (!sortCol) return 0;
        const va = a[sortCol] ?? -1, vb = b[sortCol] ?? -1;
        return (va > vb ? 1 : va < vb ? -1 : 0) * sortDir;
    });

    function th(label, col) {
        const dir = sortCol === col ? (sortDir === 1 ? ' asc' : ' desc') : '';
        return `<th data-sort="${col}" class="${dir}" onclick="sortBy('${col}')">${label}</th>`;
    }

    const rows = sorted.map(s => `<tr>
        <td>${s.subject_type}</td>
        <td class="text-center">${s.room_count}</td>
        <td class="text-center">${s.students_served}</td>
        <td class="text-center">${fmtSeconds(s.avg_queue_seconds)}</td>
        <td class="text-center">${fmtSeconds(s.avg_meeting_seconds)}</td>
    </tr>`).join('');

    container.innerHTML = `<div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
            <thead class="table-light">
                <tr>
                    ${th('Subject', 'subject_type')}
                    ${th('Rooms', 'room_count')}
                    ${th('Served', 'students_served')}
                    ${th('Avg Queue', 'avg_queue_seconds')}
                    ${th('Avg Meeting', 'avg_meeting_seconds')}
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    </div>`;
}

async function loadSubjectStats() {
    try {
        const data  = await api('GET', '/api/stats/subjects');
        subjectData = data.stats;
        renderSubjectTable();
    } catch (err) { setMsg('msg', err.message); }
}

function renderRoomStats(s) {
    function row(label, value, highlight = false) {
        return `<tr><th scope="row" class="text-muted fw-normal">${label}</th>
            <td class="${highlight ? 'fw-bold text-primary' : ''}">${value}</td></tr>`;
    }

    const waitCls = s.currently_waiting > 0;
    const meetCls = s.currently_in_meeting > 0;

    document.getElementById('room-stats').innerHTML = `<div class="table-responsive" style="max-width:400px">
        <table class="table table-sm table-bordered align-middle mb-0">
            <tbody>
                ${row('Currently waiting',    s.currently_waiting,    waitCls)}
                ${row('Currently in meeting', s.currently_in_meeting, meetCls)}
                ${row('Students served',      s.students_served)}
                ${row('Avg queue time',       fmtSeconds(s.avg_queue_seconds))}
                ${row('Avg meeting time',     fmtSeconds(s.avg_meeting_seconds))}
                ${row('Peak hour',            fmtHour(s.peak_hour))}
            </tbody>
        </table>
    </div>`;
}

async function loadRoomStats(roomId) {
    try {
        const data = await api('GET', `/api/stats/rooms/${roomId}`);
        renderRoomStats(data.stats);
    } catch (err) { setMsg('msg', err.message); }
}

async function loadRoomStatsFromInput() {
    const input = document.getElementById('room-id-input');
    const id = parseInt(input.value);
    if (!id) return;
    try {
        const data = await api('GET', `/api/stats/rooms/${id}`);
        activeRoomId = id;
        renderRoomStats(data.stats);
        setMsg('msg', '');
    } catch (err) {
        setMsg('msg', err.message);
        input.value  = '';
        activeRoomId = null;
    }
}

async function loadRoomStatsFromSelect() {
    const id = parseInt(document.getElementById('room-select').value);
    if (!id) return;
    activeRoomId = id;
    await loadRoomStats(id);
}

(async () => {
    if (user.role === 'teacher' || user.role === 'admin') {
        document.getElementById('teacher-section').style.display      = 'block';
        document.getElementById('teacher-room-section').style.display = 'flex';
        loadSubjectStats();
        setInterval(loadSubjectStats, 5000);
    } else {
        document.getElementById('student-room-section').style.display = 'block';
        try {
            const data   = await api('GET', '/api/rooms');
            const select = document.getElementById('room-select');
            data.rooms.forEach(r => {
                const opt = document.createElement('option');
                opt.value = r.id;
                opt.textContent = `${r.name} (${r.subject_type})`;
                select.appendChild(opt);
            });
            select.addEventListener('change', loadRoomStatsFromSelect);
        } catch (err) { setMsg('msg', err.message); }
    }

    setInterval(() => { if (activeRoomId) loadRoomStats(activeRoomId); }, 5000);
})();
</script>

<?php require_once __DIR__ . '/../partials/foot.php'; ?>
