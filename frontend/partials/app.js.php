<script>
function requireAuth(...roles) {
    const token = localStorage.getItem('token');
    const user  = JSON.parse(localStorage.getItem('user') || 'null');
    if (!token || !user) { window.location.href = '/login'; return null; }
    if (roles.length && !roles.includes(user.role)) { window.location.href = '/dashboard'; return null; }
    return user;
}

async function api(method, path, body = null) {
    const opts = {
        method,
        headers: {
            'Content-Type':  'application/json',
            'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
    };
    if (body !== null) opts.body = JSON.stringify(body);
    const res  = await fetch(path, opts);
    const data = await res.json();
    if (!res.ok) throw new Error(data.message ?? 'Request failed.');
    return data;
}

function setMsg(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
}
</script>
