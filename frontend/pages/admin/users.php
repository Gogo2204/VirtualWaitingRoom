<?php $pageTitle = 'Admin'; require_once __DIR__ . '/../../partials/head.php'; ?>
<?php require_once __DIR__ . '/../../partials/nav.php'; ?>

<div class="container-lg py-4">

    <!-- Stats overview -->
    <div id="stats-row" class="row g-3 mb-4"></div>

    <!-- Subjects -->
    <div class="card mb-4">
        <div class="card-body">
            <p class="page-section-title">Subjects</p>
            <div class="d-flex gap-2 mb-3">
                <input type="text" id="new-subject" class="form-control form-control-sm" placeholder="New subject name" style="max-width:220px">
                <button onclick="addSubject()" class="btn btn-sm btn-primary">Add</button>
                <div id="subj-msg" class="small align-self-center"></div>
            </div>
            <div id="subjects-list"><em class="text-muted small">Loading…</em></div>
        </div>
    </div>

    <!-- Users -->
    <div class="card mb-4">
        <div class="card-body p-0">
            <div class="px-3 pt-3 pb-2"><p class="page-section-title mb-0">Users</p></div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Faculty #</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="users-tbody"><tr><td colspan="6" class="text-center text-muted py-3"><em>Loading…</em></td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Teacher–Student Connections -->
    <div class="card mb-4">
        <div class="card-body">
            <p class="page-section-title">Teacher–Student Connections</p>
            <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
                <select id="link-teacher" class="form-select form-select-sm w-auto"></select>
                <select id="link-student" class="form-select form-select-sm w-auto"></select>
                <button onclick="addLink()" class="btn btn-sm btn-primary">Add</button>
                <div id="link-msg" class="small"></div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>Teacher</th><th>Student</th><th>Faculty #</th><th></th></tr>
                    </thead>
                    <tbody id="links-tbody"><tr><td colspan="4" class="text-center text-muted py-3"><em>Loading…</em></td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Comments -->
    <div class="card mb-4">
        <div class="card-body p-0">
            <div class="px-3 pt-3 pb-2"><p class="page-section-title mb-0">Recent Comments</p></div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>Author</th><th>Room</th><th>Comment</th><th>Visibility</th><th>When</th><th></th></tr>
                    </thead>
                    <tbody id="comments-tbody"><tr><td colspan="6" class="text-center text-muted py-3"><em>Loading…</em></td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../partials/app.js.php'; ?>
<script>
requireAuth('admin');

function esc(v) {
    return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Stats ─────────────────────────────────────────────────────── */
async function loadStats() {
    try {
        const data = await api('GET', '/api/admin/stats');
        const s = data.stats;
        const users = s.users_by_role ?? {};
        const rooms = s.rooms_by_status ?? {};

        const cards = [
            { label: 'Admins',       value: users.admin   ?? 0 },
            { label: 'Teachers',     value: users.teacher ?? 0 },
            { label: 'Students',     value: users.student ?? 0 },
            { label: 'Open rooms',   value: rooms.open    ?? 0 },
            { label: 'Closed rooms', value: rooms.closed  ?? 0 },
            { label: 'Active queue', value: s.active_queue   },
            { label: 'Comments',     value: s.total_comments },
        ];

        document.getElementById('stats-row').innerHTML = cards.map(c => `
            <div class="col-6 col-sm-4 col-md-3 col-lg-auto">
                <div class="card text-center px-3 py-2">
                    <div style="font-size:1.6rem;font-weight:700;color:var(--vwr-primary)">${c.value}</div>
                    <div class="small text-muted">${c.label}</div>
                </div>
            </div>`).join('');
    } catch (err) { /* non-critical */ }
}

/* ── Subjects ───────────────────────────────────────────────────── */
let subjects = [];

async function loadSubjects() {
    try {
        const data = await api('GET', '/api/admin/subjects');
        subjects = data.subjects;
        renderSubjects();
    } catch (err) {
        document.getElementById('subjects-list').innerHTML = `<p class="text-danger small">${err.message}</p>`;
    }
}

function renderSubjects() {
    const el = document.getElementById('subjects-list');
    if (!subjects.length) {
        el.innerHTML = '<p class="text-muted small">No subjects yet.</p>';
        return;
    }
    el.innerHTML = '<div class="d-flex flex-wrap gap-2">'
        + subjects.map(s => `
            <span class="badge border d-flex align-items-center gap-1" style="font-size:.85rem;padding:.4em .7em;background:var(--vwr-surface);color:var(--vwr-text);border-color:var(--vwr-border)!important">
                ${esc(s.type)}
                <button onclick="deleteSubject(${s.id})" class="btn-close btn-close-sm" style="font-size:.55rem" aria-label="Remove"></button>
            </span>`).join('')
        + '</div>';
}

async function addSubject() {
    const input = document.getElementById('new-subject');
    const type  = input.value.trim();
    if (!type) return;
    try {
        await api('POST', '/api/admin/subjects', { type });
        input.value = '';
        setMsg('subj-msg', 'Added.', 'success');
        loadSubjects();
        setTimeout(() => setMsg('subj-msg', ''), 2000);
    } catch (err) { setMsg('subj-msg', err.message); }
}

async function deleteSubject(id) {
    try {
        await api('DELETE', `/api/admin/subjects/${id}`);
        loadSubjects();
    } catch (err) { setMsg('subj-msg', err.message); }
}

/* ── Users ──────────────────────────────────────────────────────── */
let allUsers = [];

async function loadUsers() {
    const data = await api('GET', '/api/admin/users');
    allUsers = data.users;

    const tbody = document.getElementById('users-tbody');
    if (!allUsers.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No users found.</td></tr>';
    } else {
        tbody.innerHTML = allUsers.map(u => `
            <tr>
                <td>${esc(u.first_name + ' ' + u.last_name)}</td>
                <td class="text-muted small">${esc(u.email)}</td>
                <td class="text-muted small">${esc(u.faculty_number ?? '—')}</td>
                <td><span class="sb sb-${esc(u.role)}">${esc(u.role)}</span></td>
                <td><span class="sb sb-${esc(u.status)}">${esc(u.status ?? '')}</span></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteUser(${u.id}, '${esc(u.email)}')">Delete</button>
                </td>
            </tr>`).join('');
    }

    populateSelects();
}

function populateSelects() {
    const teachers   = allUsers.filter(u => u.role === 'teacher');
    const students   = allUsers.filter(u => u.role === 'student');
    const teacherSel = document.getElementById('link-teacher');
    const studentSel = document.getElementById('link-student');
    teacherSel.innerHTML = teachers.map(u => `<option value="${u.id}">${esc(u.first_name + ' ' + u.last_name)}</option>`).join('');
    studentSel.innerHTML = students.map(u  => `<option value="${u.id}">${esc((u.first_name || u.faculty_number) + ' ' + u.last_name)} — ${esc(u.faculty_number ?? '')}</option>`).join('');
}

async function deleteUser(id, email) {
    if (!confirm(`Delete user ${email}? This cannot be undone.`)) return;
    try {
        await api('DELETE', `/api/admin/users/${id}`);
        loadUsers();
        loadLinks();
    } catch (err) { alert(err.message); }
}

/* ── Teacher–Student links ──────────────────────────────────────── */
async function loadLinks() {
    const data  = await api('GET', '/api/admin/teacher-student');
    const tbody = document.getElementById('links-tbody');
    if (!data.links.length) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No connections.</td></tr>';
        return;
    }
    tbody.innerHTML = data.links.map(l => `
        <tr>
            <td>${esc(l.teacher_name)} <span class="text-muted small">(${esc(l.teacher_email)})</span></td>
            <td>${esc(l.student_name)}</td>
            <td class="text-muted small">${esc(l.faculty_number ?? '—')}</td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-danger py-0 px-2"
                    onclick="removeLink(${l.teacher_id}, ${l.student_id})">Remove</button>
            </td>
        </tr>`).join('');
}

async function addLink() {
    const msgEl    = document.getElementById('link-msg');
    const teacherId = parseInt(document.getElementById('link-teacher').value);
    const studentId = parseInt(document.getElementById('link-student').value);
    if (!teacherId || !studentId) {
        msgEl.className = 'small text-warning'; msgEl.textContent = 'Select a teacher and student.'; return;
    }
    try {
        await api('POST', '/api/admin/teacher-student', { teacher_id: teacherId, student_id: studentId });
        msgEl.className = 'small text-success'; msgEl.textContent = 'Added.';
        loadLinks();
        setTimeout(() => msgEl.textContent = '', 2000);
    } catch (err) { msgEl.className = 'small text-danger'; msgEl.textContent = err.message; }
}

async function removeLink(teacherId, studentId) {
    try {
        await api('DELETE', '/api/admin/teacher-student', { teacher_id: teacherId, student_id: studentId });
        loadLinks();
    } catch (err) { alert(err.message); }
}

/* ── Comments ───────────────────────────────────────────────────── */
async function loadComments() {
    try {
        const data  = await api('GET', '/api/admin/comments');
        const tbody = document.getElementById('comments-tbody');
        if (!data.comments.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No comments yet.</td></tr>';
            return;
        }
        tbody.innerHTML = data.comments.map(c => {
            const when = new Date(c.created_at).toLocaleDateString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
            return `<tr>
                <td>${esc(c.first_name + ' ' + c.last_name)}</td>
                <td><a href="/rooms/${c.room_id}" class="text-decoration-none">${esc(c.room_name)}</a></td>
                <td style="max-width:280px;text-align:left">${esc(c.content)}</td>
                <td>${c.visibility === 'teacher_only' ? '<span class="sb sb-imported">Private</span>' : '<span class="sb sb-registered">Public</span>'}</td>
                <td class="text-muted small">${when}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteComment(${c.id})">Delete</button>
                </td>
            </tr>`;
        }).join('');
    } catch (err) {
        document.getElementById('comments-tbody').innerHTML = `<tr><td colspan="6" class="text-danger small p-3">${err.message}</td></tr>`;
    }
}

async function deleteComment(id) {
    try {
        await api('DELETE', `/api/admin/comments/${id}`);
        loadComments();
        loadStats();
    } catch (err) { alert(err.message); }
}

/* ── Init ───────────────────────────────────────────────────────── */
loadStats();
loadSubjects();
loadUsers();
loadLinks();
loadComments();
</script>

<?php require_once __DIR__ . '/../../partials/foot.php'; ?>
