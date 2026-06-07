<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>

<h2>Dashboard</h2>
<p id="welcome"></p>

<div id="admin-section" style="display:none">
    <hr>
    <h3>Add Teacher</h3>
    <input type="text"  id="first_name" placeholder="First name" /><br>
    <input type="text"  id="last_name"  placeholder="Last name"  /><br>
    <input type="email" id="email"      placeholder="Email"      /><br>
    <button onclick="addTeacher()">Create Teacher</button>
    <p id="msg"></p>
</div>

<div id="teacher-section" style="display:none">
    <hr>
    <h3>Import Students</h3>
    <input type="file" id="import-file" accept=".csv,.xlsx,.xls,.txt" /><br><br>
    <div id="preview" style="display:none">
        <p>Found <strong id="fn-count">0</strong> faculty numbers:</p>
        <textarea id="fn-preview" rows="5" cols="40" readonly></textarea><br>
        <button onclick="importStudents()">Import</button>
    </div>
    <p id="import-msg"></p>
</div>

<br><a href="/rooms">My Rooms</a>
<br><a href="/change-password">Change password</a>
<br><br><button onclick="logout()">Logout</button>

<script>
    const token = localStorage.getItem('token');
    const user  = JSON.parse(localStorage.getItem('user') || 'null');

    if (!token) {
        window.location.href = '/login';
    }

    document.getElementById('welcome').textContent = `Logged in as ${user?.email} (${user?.role})`;

    if (user?.role === 'admin') {
        document.getElementById('admin-section').style.display = 'block';
    }

    async function addTeacher() {
        const first_name = document.getElementById('first_name').value;
        const last_name  = document.getElementById('last_name').value;
        const email      = document.getElementById('email').value;

        try {
            const res = await fetch('/api/users/teacher', {
                method: 'POST',
                headers: {
                    'Content-Type':  'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({ first_name, last_name, email })
            });

            const data = await res.json();

            if (!res.ok) {
                document.getElementById('msg').textContent = data.message ?? 'Failed.';
                return;
            }

            document.getElementById('msg').textContent = `Teacher ${data.user.email} created.`;

        } catch (err) {
            console.error(err);
            document.getElementById('msg').textContent = 'Network error.';
        }
    }

    function logout() {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = '/login';
    }
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
    if (user?.role === 'admin') {
        document.getElementById('admin-section').style.display = 'block';
    }
    if (user?.role === 'admin' || user?.role === 'teacher') {
        document.getElementById('teacher-section').style.display = 'block';
    }

    let parsedFacultyNumbers = [];

    document.getElementById('import-file').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;

        const ext = file.name.split('.').pop().toLowerCase();

        if (ext === 'xlsx' || ext === 'xls') {
            parseExcel(file);
        } else {
            parseCsv(file);
        }
    });

    function extractFacultyNumbers(rows) {
        const numbers = [];

        for (const row of rows) {
            const values = Array.isArray(row) ? row : Object.values(row);
            for (const val of values) {
                const str = String(val ?? '').trim();
                if (str.length > 0 && /^[a-zA-Z0-9]+$/.test(str) && str !== 'faculty_number' && str !== 'fn') {
                    numbers.push(str);
                }
            }
        }

        return [...new Set(numbers)];
    }

    function parseExcel(file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const workbook = XLSX.read(e.target.result, { type: 'binary' });
            const sheet    = workbook.Sheets[workbook.SheetNames[0]];
            const rows     = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' });

            parsedFacultyNumbers = extractFacultyNumbers(rows.flat().map(v => ({ v })));
            parsedFacultyNumbers = [...new Set(
                rows.flat()
                    .map(v => String(v ?? '').trim())
                    .filter(v => v.length > 0 && /^[a-zA-Z0-9]+$/.test(v) && !isNaN(v))
            )];

            showPreview();
        };
        reader.readAsBinaryString(file);
    }

    function parseCsv(file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const text  = e.target.result;
            const lines = text.split(/\r?\n/);

            const allValues = [];
            for (const line of lines) {
                const cells = line.split(/[,;]/);
                for (const cell of cells) {
                    allValues.push(cell.trim());
                }
            }

            parsedFacultyNumbers = [...new Set(
                allValues.filter(v => v.length > 0 && /^[a-zA-Z0-9]+$/.test(v) && !isNaN(v))
            )];

            showPreview();
        };
        reader.readAsText(file);
    }

    function showPreview() {
        if (parsedFacultyNumbers.length === 0) {
            document.getElementById('import-msg').textContent = 'No faculty numbers found in file.';
            document.getElementById('preview').style.display = 'none';
            return;
        }

        document.getElementById('fn-count').textContent    = parsedFacultyNumbers.length;
        document.getElementById('fn-preview').value        = parsedFacultyNumbers.join('\n');
        document.getElementById('preview').style.display   = 'block';
        document.getElementById('import-msg').textContent  = '';
    }

    async function importStudents() {
        if (parsedFacultyNumbers.length === 0) return;

        try {
            const res  = await fetch('/api/users/import', {
                method: 'POST',
                headers: {
                    'Content-Type':  'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({ faculty_numbers: parsedFacultyNumbers })
            });

            const data = await res.json();

            if (!res.ok) {
                document.getElementById('import-msg').textContent = data.message ?? 'Import failed.';
                return;
            }

            document.getElementById('import-msg').textContent =
                `Done. ${data.created} created, ${data.skipped} already existed.`;
            document.getElementById('preview').style.display = 'none';

        } catch (err) {
            console.error(err);
            document.getElementById('import-msg').textContent = 'Network error.';
        }
    }
</script>
</body>
</html>