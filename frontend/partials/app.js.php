<script>
function requireAuth(...roles) {
    const token = localStorage.getItem('token');
    const user  = JSON.parse(localStorage.getItem('user') || 'null');
    if (!token || !user) { window.location.href = '/login'; return null; }
    if (roles.length && !roles.includes(user.role)) { window.location.href = '/dashboard'; return null; }
    return user;
}

async function api(method, path, body = null) {
    const headers = { 'Content-Type': 'application/json' };
    const token   = localStorage.getItem('token');
    if (token) headers['Authorization'] = `Bearer ${token}`;
    const opts = { method, headers };
    if (body !== null) opts.body = JSON.stringify(body);
    const res  = await fetch(path, opts);
    const data = await res.json();
    if (!res.ok) throw new Error(data.message ?? 'Request failed.');
    return data;
}

function setMsg(id, text, type = 'danger') {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = text;
    el.className   = text ? `small mt-2 text-${type}` : 'small mt-2';
}

function fmtSeconds(s) {
    if (s === null || s === undefined) return '-';
    const m   = Math.floor(s / 60);
    const sec = Math.round(s % 60);
    return m > 0 ? `${m}m ${sec}s` : `${sec}s`;
}
</script>
