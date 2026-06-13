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

async function uploadFile(path, formData) {
    const token = localStorage.getItem('token');
    const headers = {};
    if (token) headers['Authorization'] = `Bearer ${token}`;
    const res  = await fetch(path, { method: 'POST', headers, body: formData });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message ?? 'Upload failed.');
    return data;
}

function mkAvatar(src, firstName, lastName, size) {
    const px = { xs: 22, sm: 28, lg: 84 }[size] ?? 28;
    const fs = { xs: '.55rem', sm: '.65rem', lg: '1.5rem' }[size] ?? '.65rem';
    if (src) {
        return `<img src="${src}" alt="" class="avatar" style="width:${px}px;height:${px}px" onerror="this.onerror=null;this.src='/assets/img/default-avatar.svg'">`;
    }
    const initials = ((firstName?.[0] || '') + (lastName?.[0] || '')).toUpperCase() || '?';
    return `<span class="avatar-initials" style="width:${px}px;height:${px}px;font-size:${fs}" aria-hidden="true">${initials}</span>`;
}

function fmtSeconds(s) {
    if (s === null || s === undefined) return '-';
    const m   = Math.floor(s / 60);
    const sec = Math.round(s % 60);
    return m > 0 ? `${m}m ${sec}s` : `${sec}s`;
}
</script>
