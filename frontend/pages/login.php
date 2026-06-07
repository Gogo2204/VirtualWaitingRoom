<?php $pageTitle = 'Login'; require_once __DIR__ . '/../partials/head.php'; ?>
<?php require_once __DIR__ . '/../partials/nav.php'; ?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h4 class="fw-bold text-center mb-1">Sign in</h4>
        <p class="text-center text-muted small mb-4">Virtual Waiting Room</p>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" class="form-control" placeholder="you@example.com" autofocus>
        </div>
        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" class="form-control" placeholder="Password">
        </div>

        <button onclick="login()" class="btn btn-primary w-100">Sign in</button>

        <div id="msg" class="alert alert-danger py-2 mt-3 mb-0 small" style="display:none"></div>

        <p class="text-center text-muted small mt-4 mb-0">
            New student? <a href="/register">Register here</a>
        </p>
    </div>
</div>

<script>
async function login() {
    const msgEl = document.getElementById('msg');
    msgEl.style.display = 'none';
    try {
        const res  = await fetch('/api/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                email:    document.getElementById('email').value,
                password: document.getElementById('password').value,
            })
        });
        const data = await res.json();
        if (!res.ok) {
            msgEl.textContent   = data.message ?? 'Login failed.';
            msgEl.style.display = 'block';
            return;
        }
        localStorage.setItem('token', data.token);
        localStorage.setItem('user',  JSON.stringify(data.user));
        window.location.href = '/dashboard';
    } catch {
        msgEl.textContent   = 'Network error.';
        msgEl.style.display = 'block';
    }
}

document.addEventListener('keydown', e => { if (e.key === 'Enter') login(); });
</script>

<?php require_once __DIR__ . '/../partials/foot.php'; ?>
