<?php $pageTitle = 'Users'; require_once __DIR__ . '/../../partials/head.php'; ?>
<?php require_once __DIR__ . '/../../partials/nav.php'; ?>

<div class="container-lg py-4">
    <h4 class="fw-bold mb-1">Users</h4>
    <p class="text-muted small mb-4">Manage accounts and teacher–student connections.</p>

    <!-- Users table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-0">
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

    <!-- Teacher–Student links -->
    <h5 class="fw-semibold mb-3">Teacher–Student Connections</h5>

    <div class="row g-3 mb-3">
        <div class="col-auto">
            <select id="link-teacher" class="form-select form-select-sm"></select>
        </div>
        <div class="col-auto">
            <select id="link-student" class="form-select form-select-sm"></select>
        </div>
        <div class="col-auto">
            <button onclick="addLink()" class="btn btn-sm btn-primary">Add Link</button>
        </div>
        <div class="col-12"><div id="link-msg" class="small"></div></div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
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
</div>

<?php require_once __DIR__ . '/../../partials/app.js.php'; ?>
<script>
requireAuth('admin');

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
                <td><span class="badge bg-secondary text-capitalize">${esc(u.role)}</span></td>
                <td><span class="badge bg-light text-secondary border text-capitalize">${esc(u.status ?? '')}</span></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteUser(${u.id}, '${esc(u.email)}')">Delete</button>
                </td>
            </tr>`).join('');
    }

    populateSelects();
}

function populateSelects() {
    const teachers = allUsers.filter(u => u.role === 'teacher');
    const students  = allUsers.filter(u => u.role === 'student');
    const teacherSel = document.getElementById('link-teacher');
    const studentSel = document.getElementById('link-student');
    teacherSel.innerHTML = teachers.map(u => `<option value="${u.id}">${esc(u.first_name + ' ' + u.last_name)} (${esc(u.email)})</option>`).join('');
    studentSel.innerHTML = students.map(u =>  `<option value="${u.id}">${esc(u.first_name || u.faculty_number)} ${esc(u.last_name)} — ${esc(u.faculty_number ?? '')}</option>`).join('');
}

async function deleteUser(id, email) {
    if (!confirm(`Delete user ${email}? This cannot be undone.`)) return;
    try {
        await api('DELETE', `/api/admin/users/${id}`);
        loadUsers();
        loadLinks();
    } catch (err) { alert(err.message); }
}

async function loadLinks() {
    const data = await api('GET', '/api/admin/teacher-student');
    const tbody = document.getElementById('links-tbody');
    if (!data.links.length) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No connections.</td></tr>';
        return;
    }
    tbody.innerHTML = data.links.map(l => `
        <tr>
            <td>${esc(l.teacher_name)}<span class="text-muted small ms-1">(${esc(l.teacher_email)})</span></td>
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
        msgEl.className = 'small text-success'; msgEl.textContent = 'Connection added.';
        loadLinks();
    } catch (err) { msgEl.className = 'small text-danger'; msgEl.textContent = err.message; }
}

async function removeLink(teacherId, studentId) {
    try {
        await api('DELETE', '/api/admin/teacher-student', { teacher_id: teacherId, student_id: studentId });
        loadLinks();
    } catch (err) { alert(err.message); }
}

function esc(v) {
    return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

loadUsers();
loadLinks();
</script>

<?php require_once __DIR__ . '/../../partials/foot.php'; ?>
