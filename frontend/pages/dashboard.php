<?php $pageTitle = 'Dashboard'; require_once __DIR__ . '/../partials/head.php'; ?>
<?php require_once __DIR__ . '/../partials/nav.php'; ?>

<div class="container-lg py-4">
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Dashboard</h4>
        <p id="welcome" class="text-muted small mb-0"></p>
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

        <!-- Teacher / Admin: import students -->
        <div id="teacher-section" class="col-lg-7" style="display:none">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="page-section-title">Import Students</p>
                    <div class="mb-3">
                        <label class="form-label">Upload CSV or Excel (.xlsx)</label>
                        <input type="file" id="import-file" class="form-control form-control-sm" accept=".csv,.xlsx,.xls,.txt">
                    </div>
                    <div id="preview" style="display:none">
                        <p class="small text-muted mb-1">Found <strong id="fn-count">0</strong> faculty numbers:</p>
                        <textarea id="fn-preview" rows="4" class="form-control form-control-sm mb-2" readonly></textarea>
                        <button onclick="importStudents()" class="btn btn-sm btn-primary">Import</button>
                    </div>
                    <div id="import-msg" class="small mt-2"></div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../partials/app.js.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
const user = requireAuth();
document.getElementById('welcome').textContent =
    `Signed in as ${user.first_name ? user.first_name + ' ' + user.last_name : user.email} · ${user.role}`;

if (user.role === 'admin') {
    document.getElementById('admin-section').style.display = 'block';
}
if (user.role === 'admin' || user.role === 'teacher') {
    document.getElementById('teacher-section').style.display = 'block';
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

let parsedFacultyNumbers = [];

document.getElementById('import-file').addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (!file) return;
    const ext = file.name.split('.').pop().toLowerCase();
    if (ext === 'xlsx' || ext === 'xls') parseExcel(file);
    else parseCsv(file);
});

function parseExcel(file) {
    const reader = new FileReader();
    reader.onload = e => {
        const wb   = XLSX.read(e.target.result, { type: 'binary' });
        const rows = XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]], { header: 1, defval: '' });
        parsedFacultyNumbers = [...new Set(
            rows.flat().map(v => String(v ?? '').trim()).filter(v => v.length > 0 && /^\d+$/.test(v))
        )];
        showPreview();
    };
    reader.readAsBinaryString(file);
}

function parseCsv(file) {
    const reader = new FileReader();
    reader.onload = e => {
        parsedFacultyNumbers = [...new Set(
            e.target.result.split(/\r?\n/).flatMap(l => l.split(/[,;]/))
                .map(v => v.trim()).filter(v => v.length > 0 && /^\d+$/.test(v))
        )];
        showPreview();
    };
    reader.readAsText(file);
}

function showPreview() {
    const importMsg = document.getElementById('import-msg');
    if (!parsedFacultyNumbers.length) {
        importMsg.className = 'small mt-2 text-warning';
        importMsg.textContent = 'No faculty numbers found in file.';
        document.getElementById('preview').style.display = 'none';
        return;
    }
    document.getElementById('fn-count').textContent  = parsedFacultyNumbers.length;
    document.getElementById('fn-preview').value      = parsedFacultyNumbers.join('\n');
    document.getElementById('preview').style.display = 'block';
    importMsg.textContent = '';
}

async function importStudents() {
    if (!parsedFacultyNumbers.length) return;
    const importMsg = document.getElementById('import-msg');
    try {
        const data = await api('POST', '/api/users/import', { faculty_numbers: parsedFacultyNumbers });
        importMsg.className = 'small mt-2 text-success';
        importMsg.textContent = `Done — ${data.created} created, ${data.skipped} already existed.`;
        document.getElementById('preview').style.display = 'none';
        parsedFacultyNumbers = [];
    } catch (err) {
        importMsg.className = 'small mt-2 text-danger';
        importMsg.textContent = err.message;
    }
}
</script>

<?php require_once __DIR__ . '/../partials/foot.php'; ?>
