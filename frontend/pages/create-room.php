<?php $pageTitle = 'Create Room'; require_once __DIR__ . '/../partials/head.php'; ?>
<?php require_once __DIR__ . '/../partials/nav.php'; ?>

<div class="container-lg py-4" style="max-width:620px">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="/rooms" class="text-muted text-decoration-none small">← Rooms</a>
        <span class="text-muted">/</span>
        <h4 class="fw-bold mb-0">Create Room</h4>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="mb-3">
                <label for="name" class="form-label">Room name <span class="text-danger">*</span></label>
                <input type="text" id="name" class="form-control">
            </div>
            <div class="mb-3">
                <label for="subject_id" class="form-label">Purpose <span class="text-danger">*</span></label>
                <select id="subject_id" class="form-select">
                    <option value="">Loading purposes…</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description <span class="text-muted small">(optional)</span></label>
                <textarea id="description" class="form-control" rows="2"></textarea>
            </div>
            <div class="mb-3">
                <label for="wait_time_minutes" class="form-label">Wait time per student (minutes)</label>
                <input type="number" id="wait_time_minutes" class="form-control" value="15" min="1" max="120">
            </div>
            <div class="mb-4">
                <label for="url" class="form-label">Meeting URL <span class="text-muted small">(optional)</span></label>
                <input type="url" id="url" class="form-control">
            </div>
            <div class="text-center mt-3">
                <button onclick="createRoom()" class="btn btn-primary px-5">Create Room</button>
            </div>
            <div id="msg" class="small mt-2"></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/app.js.php'; ?>
<script>
requireAuth('teacher');

(async () => {
    try {
        const data   = await api('GET', '/api/subjects');
        const select = document.getElementById('subject_id');
        select.innerHTML = '<option value="">Select a purpose…</option>';
        data.subjects.forEach(s => {
            const opt      = document.createElement('option');
            opt.value       = s.id;
            opt.textContent = s.name || s.type;
            select.appendChild(opt);
        });
    } catch (err) {
        setMsg('msg', 'Could not load purposes: ' + err.message);
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

<?php require_once __DIR__ . '/../partials/foot.php'; ?>
