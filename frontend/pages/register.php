<?php $pageTitle = 'Register'; require_once __DIR__ . '/../partials/head.php'; ?>
<?php require_once __DIR__ . '/../partials/nav.php'; ?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h4 class="fw-bold text-center mb-1">Student Registration</h4>
        <p class="text-center text-muted small mb-4">Create your account</p>

        <div class="row g-2 mb-2">
            <div class="col-6">
                <label for="first_name" class="form-label">First name</label>
                <input type="text" id="first_name" class="form-control" placeholder="First name">
            </div>
            <div class="col-6">
                <label for="last_name" class="form-label">Last name</label>
                <input type="text" id="last_name" class="form-control" placeholder="Last name">
            </div>
        </div>
        <div class="mb-2">
            <label for="faculty_number" class="form-label">Faculty number</label>
            <input type="text" id="faculty_number" class="form-control" placeholder="e.g. 12345">
        </div>
        <div class="mb-2">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" class="form-control" placeholder="you@example.com">
        </div>
        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" class="form-control" placeholder="Password">
        </div>

        <button onclick="register()" class="btn btn-primary w-100">Register</button>

        <div id="msg" class="alert alert-danger py-2 mt-3 mb-0 small" style="display:none"></div>

        <p class="text-center text-muted small mt-4 mb-0">
            Already have an account? <a href="/login">Sign in</a>
        </p>
    </div>
</div>

<script>
async function register() {
    const msgEl = document.getElementById('msg');
    msgEl.style.display = 'none';
    try {
        const res  = await fetch('/api/auth/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                first_name:     document.getElementById('first_name').value.trim(),
                last_name:      document.getElementById('last_name').value.trim(),
                email:          document.getElementById('email').value.trim(),
                password:       document.getElementById('password').value,
                faculty_number: document.getElementById('faculty_number').value.trim(),
            })
        });
        const data = await res.json();
        if (!res.ok) {
            msgEl.textContent   = data.message ?? 'Registration failed.';
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
</script>

<?php require_once __DIR__ . '/../partials/foot.php'; ?>
