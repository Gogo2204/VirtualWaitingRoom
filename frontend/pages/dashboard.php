<?php $pageTitle = 'Dashboard'; require_once __DIR__ . '/../partials/head.php'; ?>
<?php require_once __DIR__ . '/../partials/nav.php'; ?>

<div class="container-lg py-4">
    <h4 class="fw-bold mb-4">Dashboard</h4>

    <div class="row g-4">

        <!-- Admin: create teacher -->
        <div id="admin-section" class="col-lg-5" style="display:none">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="page-section-title">Add Teacher</p>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label">First name</label>
                            <input type="text" id="first_name" class="form-control form-control-sm" placeholder="First name">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Last name</label>
                            <input type="text" id="last_name" class="form-control form-control-sm" placeholder="Last name">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" id="email" class="form-control form-control-sm" placeholder="teacher@example.com">
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
} else {
    document.getElementById('teacher-section').style.display = 'block';
    document.getElementById('teacher-students-section').style.display = 'block';
    loadStudents();
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
