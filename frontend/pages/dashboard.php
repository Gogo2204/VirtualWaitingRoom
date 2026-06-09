<?php $pageTitle = 'Dashboard'; require_once __DIR__ . '/../partials/head.php'; ?>
<?php require_once __DIR__ . '/../partials/nav.php'; ?>

<div class="container-lg py-4">
    <!-- Admin stats overview -->
    <div id="admin-stats-row" class="d-flex flex-wrap gap-3 mb-4" style="display:none"></div>

    <!-- Admin: subjects -->
    <div id="admin-subjects-section" class="mb-4" style="display:none">
        <div class="card">
            <div class="card-body">
                <p class="page-section-title">Subjects</p>
                <div class="d-flex gap-2 mb-3">
                    <input type="text" id="new-subject" class="form-control form-control-sm" style="max-width:220px">
                    <button onclick="addSubject()" class="btn btn-sm btn-primary">Add</button>
                    <div id="subj-msg" class="small align-self-center"></div>
                </div>
                <div id="subjects-list"><em class="text-muted small">Loading…</em></div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <!-- Admin: create teacher -->
        <div id="admin-section" class="col-lg-5" style="display:none">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="page-section-title">Add Teacher</p>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label">First name</label>
                            <input type="text" id="first_name" class="form-control form-control-sm" >
                        </div>
                        <div class="col-6">
                            <label class="form-label">Last name</label>
                            <input type="text" id="last_name" class="form-control form-control-sm" >
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" id="email" class="form-control form-control-sm" >
                    </div>
                    <button onclick="addTeacher()" class="btn btn-sm btn-primary">Create Teacher</button>
                    <div id="msg" class="small mt-2"></div>
                </div>
            </div>
        </div>

        <!-- Teacher: import students -->
        <div id="teacher-section" class="col-lg-7" style="display:none">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="page-section-title">Import Students</p>
                    <div class="mb-3">
                        <label class="form-label">Upload CSV or Excel (.xlsx)</label>
                        <input type="file" id="import-file" class="form-control form-control-sm" accept=".csv,.xlsx,.xls,.txt">
                    </div>
                    <div id="preview" style="display:none">
                        <p class="small text-muted mb-1">Found <strong id="fn-count">0</strong> students:</p>
                        <textarea id="fn-preview" rows="4" class="form-control form-control-sm mb-2" readonly></textarea>
                        <button onclick="importStudents()" class="btn btn-sm btn-primary">Import</button>
                    </div>
                    <div id="import-msg" class="small mt-2"></div>
                </div>
            </div>
        </div>

    </div><!-- /.row -->

    <!-- Teacher: imported students list -->
    <div id="teacher-students-section" class="mt-4" style="display:none">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <p class="page-section-title">Imported Students</p>
                <div id="students-list"><p class="text-muted small"><em>Loading…</em></p></div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/app.js.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
const user = requireAuth();

if (user.role === 'student') {
    window.location.replace('/rooms');
} else if (user.role === 'admin') {
    document.getElementById('admin-section').style.display = 'block';
    document.getElementById('admin-stats-row').style.display = 'flex';
    document.getElementById('admin-subjects-section').style.display = 'block';
    loadAdminStats();
    loadSubjects();
} else {
    document.getElementById('teacher-section').style.display = 'block';
    document.getElementById('teacher-students-section').style.display = 'block';
    loadStudents();
}

function esc(v) {
    return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function setDashMsg(id, text, type) {
    const el = document.getElementById(id);
    if (!el) return;
    el.className = `small align-self-center${type === 'success' ? ' text-success' : ' text-danger'}`;
    el.textContent = text;
}

/* ── Subjects ───────────────────────────────────────────────────── */
let subjects = [];

async function loadSubjects() {
    try {
        const data = await api('GET', '/api/admin/subjects');
        subjects = data.subjects;
        renderSubjects();
    } catch (err) {
        const el = document.getElementById('subjects-list');
        if (el) el.innerHTML = `<p class="text-danger small">${err.message}</p>`;
    }
}

function renderSubjects() {
    const el = document.getElementById('subjects-list');
    if (!el) return;
    if (!subjects.length) { el.innerHTML = '<p class="text-muted small">No subjects yet.</p>'; return; }
    el.innerHTML = '<div class="d-flex flex-wrap gap-2">'
        + subjects.map(s => `
            <span class="d-inline-flex align-items-center gap-2 border rounded px-2 py-1"
                style="background:var(--vwr-surface);font-size:.875rem">
                ${esc(s.type)}
                <button onclick="deleteSubject(${s.id})"
                    style="width:22px;height:22px;padding:0;font-size:.8rem;line-height:1;border-radius:3px;flex-shrink:0"
                    class="btn btn-danger" aria-label="Remove">&#x2715;</button>
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
        setDashMsg('subj-msg', 'Added.', 'success');
        loadSubjects();
        setTimeout(() => { const el = document.getElementById('subj-msg'); if (el) el.textContent = ''; }, 2000);
    } catch (err) { setDashMsg('subj-msg', err.message); }
}

async function deleteSubject(id) {
    try { await api('DELETE', `/api/admin/subjects/${id}`); loadSubjects(); }
    catch (err) { setDashMsg('subj-msg', err.message); }
}

async function loadAdminStats() {
    try {
        const data = await api('GET', '/api/admin/stats');
        const s = data.stats, u = s.users_by_role ?? {}, r = s.rooms_by_status ?? {};
        const cards = [
            { label: 'Admins',       value: u.admin   ?? 0 },
            { label: 'Teachers',     value: u.teacher ?? 0 },
            { label: 'Students',     value: u.student ?? 0 },
            { label: 'Open rooms',   value: r.open    ?? 0 },
            { label: 'Closed rooms', value: r.closed  ?? 0 },
            { label: 'Active queue', value: s.active_queue   },
            { label: 'Comments',     value: s.total_comments },
        ];
        document.getElementById('admin-stats-row').innerHTML = cards.map(c =>
            `<div class="card px-3 py-2 text-center" style="min-width:88px">
                <div style="font-size:1.5rem;font-weight:700;color:var(--vwr-primary)">${c.value}</div>
                <div class="small text-muted">${c.label}</div>
            </div>`).join('');
    } catch { /* non-critical */ }
}

async function loadStudents() {
    const container = document.getElementById('students-list');
    try {
        const data = await api('GET', '/api/users/students');
        if (!data.students.length) {
            container.innerHTML = '<p class="text-muted small">No students imported yet.</p>';
            return;
        }
        const rows = data.students.map(s => `<tr>
            <td>${s.faculty_number ?? '—'}</td>
            <td>${s.first_name} ${s.last_name}</td>
            <td>${s.email}</td>
            <td><span class="sb sb-${s.status}">${s.status}</span></td>
        </tr>`).join('');
        container.innerHTML = `<div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Faculty #</th><th>Name</th><th>Email</th><th>Status</th></tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>`;
    } catch (err) {
        container.innerHTML = `<p class="text-danger small">${err.message}</p>`;
    }
}

async function addTeacher() {
    const msgEl = document.getElementById('msg');
    try {
        const data = await api('POST', '/api/users/teacher', {
            first_name: document.getElementById('first_name').value.trim(),
            last_name:  document.getElementById('last_name').value.trim(),
            email:      document.getElementById('email').value.trim(),
        });
        msgEl.className = 'small mt-2 text-success';
        msgEl.textContent = `Teacher ${data.user.email} created successfully.`;
        document.getElementById('first_name').value = '';
        document.getElementById('last_name').value  = '';
        document.getElementById('email').value      = '';
    } catch (err) {
        msgEl.className = 'small mt-2 text-danger';
        msgEl.textContent = err.message;
    }
}

let parsedStudents = [];

document.getElementById('import-file')?.addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (!file) return;
    const ext = file.name.split('.').pop().toLowerCase();
    if (ext === 'xlsx' || ext === 'xls') parseExcel(file);
    else parseCsv(file);
});

function isFacultyNumber(v) {
    return /^[a-zA-Z0-9]+$/.test(v);
}

function normalizeHeader(h) {
    return String(h).toLowerCase().trim().replace(/[\s_-]+/g, '_');
}

function detectColumns(headers) {
    const fnAliases   = ['faculty_number', 'facultynumber', 'fn', 'faculty_no', 'student_id'];
    const firstAliases = ['first_name', 'firstname', 'first'];
    const lastAliases  = ['last_name', 'lastname', 'last'];

    const norm = headers.map(normalizeHeader);
    const fnIdx    = norm.findIndex(h => fnAliases.includes(h));
    const firstIdx = norm.findIndex(h => firstAliases.includes(h));
    const lastIdx  = norm.findIndex(h => lastAliases.includes(h));
    return { fnIdx, firstIdx, lastIdx };
}

function buildStudentObjects(rows) {
    if (!rows.length) return [];

    const firstRow = rows[0].map(v => String(v ?? '').trim());
    const { fnIdx, firstIdx, lastIdx } = detectColumns(firstRow);

    // If header row detected (has a recognizable faculty number column)
    if (fnIdx !== -1) {
        const dataRows = rows.slice(1);
        const seen = new Set();
        return dataRows.flatMap(row => {
            const fn = String(row[fnIdx] ?? '').trim();
            if (!fn || !isFacultyNumber(fn) || seen.has(fn)) return [];
            seen.add(fn);
            return [{
                faculty_number: fn,
                first_name: firstIdx !== -1 ? String(row[firstIdx] ?? '').trim() : '',
                last_name:  lastIdx  !== -1 ? String(row[lastIdx]  ?? '').trim() : '',
            }];
        });
    }

    // No header — flat extraction of anything that looks like a faculty number
    const seen = new Set();
    return rows.flat().flatMap(v => {
        const fn = String(v ?? '').trim();
        if (!fn || !isFacultyNumber(fn) || seen.has(fn)) return [];
        seen.add(fn);
        return [{ faculty_number: fn, first_name: '', last_name: '' }];
    });
}

function parseExcel(file) {
    const reader = new FileReader();
    reader.onload = e => {
        const wb   = XLSX.read(e.target.result, { type: 'binary' });
        const rows = XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]], { header: 1, defval: '' });
        parsedStudents = buildStudentObjects(rows);
        showPreview();
    };
    reader.readAsBinaryString(file);
}

function parseCsv(file) {
    const reader = new FileReader();
    reader.onload = e => {
        const rows = e.target.result.split(/\r?\n/).map(l => l.split(/[,;]/));
        parsedStudents = buildStudentObjects(rows);
        showPreview();
    };
    reader.readAsText(file);
}

function showPreview() {
    const importMsg = document.getElementById('import-msg');
    if (!parsedStudents.length) {
        importMsg.className = 'small mt-2 text-warning';
        importMsg.textContent = 'No valid faculty numbers found in file.';
        document.getElementById('preview').style.display = 'none';
        return;
    }
    document.getElementById('fn-count').textContent = parsedStudents.length;
    document.getElementById('fn-preview').value = parsedStudents
        .map(s => s.first_name || s.last_name
            ? `${s.faculty_number} (${[s.first_name, s.last_name].filter(Boolean).join(' ')})`
            : s.faculty_number)
        .join('\n');
    document.getElementById('preview').style.display = 'block';
    importMsg.textContent = '';
}

async function importStudents() {
    if (!parsedStudents.length) return;
    const importMsg = document.getElementById('import-msg');
    try {
        const data = await api('POST', '/api/users/import', { students: parsedStudents });
        importMsg.className = 'small mt-2 text-success';
        importMsg.textContent = `Done — ${data.created} created, ${data.skipped} already existed.`;
        document.getElementById('preview').style.display = 'none';
        parsedStudents = [];
        loadStudents();
    } catch (err) {
        importMsg.className = 'small mt-2 text-danger';
        importMsg.textContent = err.message;
    }
}
</script>

<?php require_once __DIR__ . '/../partials/foot.php'; ?>
