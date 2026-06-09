<?php $pageTitle = 'Rooms'; require_once __DIR__ . '/../partials/head.php'; ?>
<?php require_once __DIR__ . '/../partials/nav.php'; ?>

<div class="container-lg py-4">
    <div class="d-flex flex-wrap justify-content-end align-items-center gap-2 mb-3">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <div class="btn-group btn-group-sm" id="filter-bar">
                <button class="btn btn-outline-secondary active" data-filter="all">All</button>
                <button class="btn btn-outline-secondary" data-filter="open">Open</button>
                <button class="btn btn-outline-secondary" data-filter="closed">Closed</button>
                <button class="btn btn-outline-secondary" data-filter="archived">Archived</button>
            </div>
            <div id="teacher-actions" style="display:none" class="d-flex gap-2">
                <a href="/rooms/create" class="btn btn-sm btn-primary">+ New Room</a>
                <a href="/stats" class="btn btn-sm btn-outline-secondary">Statistics</a>
            </div>
            <div id="student-actions" style="display:none">
                <a href="/stats" class="btn btn-sm btn-outline-secondary">Statistics</a>
            </div>
        </div>
    </div>

    <p id="msg" class="text-danger small"></p>

    <div id="rooms-grid" class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
        <div class="col"><p class="text-muted small"><em>Loading…</em></p></div>
    </div>

</div>

<?php require_once __DIR__ . '/../partials/app.js.php'; ?>
<script>
const user = requireAuth('teacher', 'student', 'admin');
let allRooms     = [];
let activeFilter = 'all';

if (user.role === 'teacher') {
    document.getElementById('teacher-actions').style.display = 'flex';
} else {
    document.getElementById('student-actions').style.display = 'block';
}

document.querySelectorAll('#filter-bar .btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('#filter-bar .btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        activeFilter = btn.dataset.filter;
        renderRooms();
    });
});

const STATUS_LABEL = { open: 'Open', closed: 'Closed', archived: 'Archived' };

function renderRooms() {
    const grid  = document.getElementById('rooms-grid');
    const rooms = activeFilter === 'all' ? allRooms : allRooms.filter(r => r.status === activeFilter);

    if (!rooms.length) {
        grid.innerHTML = '<div class="col"><p class="text-muted small">No rooms found.</p></div>';
        return;
    }

    grid.innerHTML = rooms.map(r => `
        <div class="col">
            <a href="/rooms/${r.id}" class="room-card d-block text-decoration-none">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="overflow-hidden">
                        <h6 class="mb-1 text-truncate">${r.name}</h6>
                        <small class="text-muted">${r.subject_type}</small>
                        ${r.description ? `<p class="small text-muted mt-1 mb-0 text-truncate" title="${r.description}">${r.description}</p>` : ''}
                    </div>
                    <span class="sb sb-${r.status} flex-shrink-0">${STATUS_LABEL[r.status] ?? r.status}</span>
                </div>
            </a>
        </div>
    `).join('');
}

async function loadRooms() {
    try {
        const data = await api('GET', '/api/rooms');
        allRooms   = data.rooms;
        renderRooms();
    } catch (err) {
        setMsg('msg', err.message);
    }
}

loadRooms();
setInterval(loadRooms, 10000);
</script>

<?php require_once __DIR__ . '/../partials/foot.php'; ?>
