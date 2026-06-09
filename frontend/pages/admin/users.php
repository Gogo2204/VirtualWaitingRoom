<?php $pageTitle = 'Admin'; require_once __DIR__ . '/../../partials/head.php'; ?>
<?php require_once __DIR__ . '/../../partials/nav.php'; ?>

<div class="container-lg py-4">

    <!-- Users -->
    <div class="card mb-4">
        <div class="card-body p-0">
            <div class="px-3 pt-3 pb-2"><p class="page-section-title mb-0">Users</p></div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>Name</th><th>Email</th><th>Faculty #</th><th>Role</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody id="users-tbody"><tr><td colspan="6" class="text-center text-muted py-3"><em>Loading…</em></td></tr></tbody>
                </table>
            </div>
            <div id="users-pager" class="px-3 pb-3"></div>
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
                        <tr><th>Teacher</th><th>Student</th><th>Faculty #</th><th>Actions</th></tr>
                    </thead>
                    <tbody id="links-tbody"><tr><td colspan="4" class="text-center text-muted py-3"><em>Loading…</em></td></tr></tbody>
                </table>
            </div>
            <div id="links-pager" class="mt-2"></div>
        </div>
    </div>

    <!-- Recent Comments -->
    <div class="card mb-4">
        <div class="card-body p-0">
            <div class="px-3 pt-3 pb-2"><p class="page-section-title mb-0">Recent Comments</p></div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>Author</th><th>Room</th><th>Comment</th><th>Visibility</th><th>When</th><th>Actions</th></tr>
                    </thead>
                    <tbody id="comments-tbody"><tr><td colspan="6" class="text-center text-muted py-3"><em>Loading…</em></td></tr></tbody>
                </table>
            </div>
            <div id="comments-pager" class="px-3 pb-3"></div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../partials/app.js.php'; ?>
<script>
requireAuth('admin');

const PAGE_SIZE = 15;

function esc(v) {
    return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function pager(current, total, fn) {
    if (total <= 1) return '';
    const btns = [];
    if (current > 1) btns.push(`<button class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="${fn}(${current-1})">‹</button>`);
    const from = Math.max(1, current - 2), to = Math.min(total, current + 2);
    for (let i = from; i <= to; i++)
        btns.push(`<button class="btn btn-sm ${i===current?'btn-primary':'btn-outline-secondary'} py-0 px-2" onclick="${fn}(${i})">${i}</button>`);
    if (current < total) btns.push(`<button class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="${fn}(${current+1})">›</button>`);
    btns.push(`<span class="text-muted small ms-1">${current} / ${total}</span>`);
    return `<div class="d-flex gap-1 align-items-center mt-2">${btns.join('')}</div>`;
}

/* ── Users ──────────────────────────────────────────────────────── */
let allUsers = [], usersPage = 1;

async function loadUsers() {
    const data = await api('GET', '/api/admin/users');
    allUsers = data.users;
    usersPage = 1;
    renderPagedUsers();
    populateSelects();
}

function renderPagedUsers() {
    const total  = Math.ceil(allUsers.length / PAGE_SIZE);
    const slice  = allUsers.slice((usersPage - 1) * PAGE_SIZE, usersPage * PAGE_SIZE);
    const tbody  = document.getElementById('users-tbody');

    if (!slice.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No users found.</td></tr>';
    } else {
        tbody.innerHTML = slice.map(u => `
            <tr>
                <td style="text-align:left">${esc(u.first_name + ' ' + u.last_name)}</td>
                <td class="text-muted small" style="text-align:left">${esc(u.email)}</td>
                <td>${esc(u.faculty_number ?? '-')}</td>
                <td><span class="sb sb-${esc(u.role)}">${esc(u.role)}</span></td>
                <td><span class="sb sb-${esc(u.status)}">${esc(u.status ?? '')}</span></td>
                <td><button class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteUser(${u.id},'${esc(u.email)}')">Delete</button></td>
            </tr>`).join('');
    }
    document.getElementById('users-pager').innerHTML = pager(usersPage, total, 'setUsersPage');
}

function setUsersPage(p) { usersPage = p; renderPagedUsers(); }

function populateSelects() {
    const teachers = allUsers.filter(u => u.role === 'teacher');
    const students  = allUsers.filter(u => u.role === 'student');
    document.getElementById('link-teacher').innerHTML = teachers.map(u =>
        `<option value="${u.id}">${esc(u.first_name + ' ' + u.last_name)}</option>`).join('');
    document.getElementById('link-student').innerHTML = students.map(u =>
        `<option value="${u.id}">${esc((u.first_name || u.faculty_number) + ' ' + u.last_name)} - ${esc(u.faculty_number ?? '')}</option>`).join('');
}

async function deleteUser(id, email) {
    if (!confirm(`Delete user ${email}? This cannot be undone.`)) return;
    try { await api('DELETE', `/api/admin/users/${id}`); loadUsers(); loadLinks(); }
    catch (err) { alert(err.message); }
}

/* ── Teacher–Student links ──────────────────────────────────────── */
let allLinks = [], linksPage = 1;

async function loadLinks() {
    const data = await api('GET', '/api/admin/teacher-student');
    allLinks = data.links;
    linksPage = 1;
    renderPagedLinks();
}

function renderPagedLinks() {
    const total = Math.ceil(allLinks.length / PAGE_SIZE);
    const slice = allLinks.slice((linksPage - 1) * PAGE_SIZE, linksPage * PAGE_SIZE);
    const tbody = document.getElementById('links-tbody');

    if (!slice.length) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No connections.</td></tr>';
    } else {
        tbody.innerHTML = slice.map(l => `
            <tr>
                <td style="text-align:left">${esc(l.teacher_name)} <span class="text-muted small">(${esc(l.teacher_email)})</span></td>
                <td style="text-align:left">${esc(l.student_name)}</td>
                <td class="text-muted small">${esc(l.faculty_number ?? '-')}</td>
                <td><button class="btn btn-sm btn-outline-danger py-0 px-2"
                    onclick="removeLink(${l.teacher_id},${l.student_id})">Remove</button></td>
            </tr>`).join('');
    }
    document.getElementById('links-pager').innerHTML = pager(linksPage, total, 'setLinksPage');
}

function setLinksPage(p) { linksPage = p; renderPagedLinks(); }

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
    try { await api('DELETE', '/api/admin/teacher-student', { teacher_id: teacherId, student_id: studentId }); loadLinks(); }
    catch (err) { alert(err.message); }
}

/* ── Comments ───────────────────────────────────────────────────── */
let allComments = [], commentsPage = 1;

async function loadComments() {
    try {
        const data = await api('GET', '/api/admin/comments');
        allComments = data.comments;
        commentsPage = 1;
        renderPagedComments();
    } catch (err) {
        document.getElementById('comments-tbody').innerHTML =
            `<tr><td colspan="6" class="text-danger small p-3">${err.message}</td></tr>`;
    }
}

function renderPagedComments() {
    const total = Math.ceil(allComments.length / PAGE_SIZE);
    const slice = allComments.slice((commentsPage - 1) * PAGE_SIZE, commentsPage * PAGE_SIZE);
    const tbody = document.getElementById('comments-tbody');

    if (!slice.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No comments yet.</td></tr>';
    } else {
        tbody.innerHTML = slice.map(c => {
            const when = new Date(c.created_at).toLocaleDateString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
            return `<tr>
                <td style="text-align:left">${esc(c.first_name + ' ' + c.last_name)}</td>
                <td><a href="/rooms/${c.room_id}" class="text-decoration-none">${esc(c.room_name)}</a></td>
                <td style="text-align:left;max-width:260px">${esc(c.content)}</td>
                <td>${c.visibility === 'teacher_only' ? '<span class="sb sb-imported">Private</span>' : '<span class="sb sb-registered">Public</span>'}</td>
                <td class="text-muted small">${when}</td>
                <td><button class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteComment(${c.id})">Delete</button></td>
            </tr>`;
        }).join('');
    }
    document.getElementById('comments-pager').innerHTML = pager(commentsPage, total, 'setCommentsPage');
}

function setCommentsPage(p) { commentsPage = p; renderPagedComments(); }

async function deleteComment(id) {
    try { await api('DELETE', `/api/admin/comments/${id}`); loadComments(); }
    catch (err) { alert(err.message); }
}

/* ── Init ───────────────────────────────────────────────────────── */
loadUsers();
loadLinks();
loadComments();
</script>

<?php require_once __DIR__ . '/../../partials/foot.php'; ?>
