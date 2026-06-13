<?php $pageTitle = 'Profile'; require_once __DIR__ . '/../partials/head.php'; ?>
<?php require_once __DIR__ . '/../partials/nav.php'; ?>

<div class="container-lg py-4" style="max-width:640px">
    <h4 class="fw-bold mb-4">Profile</h4>

    <!-- Profile picture -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body text-center">
            <p class="page-section-title text-start">Profile Picture</p>
            <div id="avatar-display" class="mb-3"></div>
            <div class="d-flex justify-content-center align-items-center gap-2 flex-wrap">
                <input type="file" id="avatar-input" class="form-control form-control-sm"
                    accept="image/jpeg,image/png,image/gif,image/webp" style="max-width:240px">
                <button onclick="uploadAvatar()" class="btn btn-sm btn-outline-primary">Upload</button>
            </div>
            <div class="small text-muted mt-1">JPEG, PNG, GIF or WebP · max 2 MB</div>
            <div id="avatar-msg" class="small mt-2"></div>
        </div>
    </div>

    <!-- Personal info -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <p class="page-section-title">Personal Information</p>
            <div id="profile-meta" class="text-muted small mb-3"></div>
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label">First name</label>
                    <input type="text" id="first_name" class="form-control form-control-sm">
                </div>
                <div class="col-6">
                    <label class="form-label">Last name</label>
                    <input type="text" id="last_name" class="form-control form-control-sm">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" id="email" class="form-control form-control-sm">
            </div>
            <div class="text-center mt-3">
                <button onclick="updateProfile()" class="btn btn-sm btn-primary px-4">Save changes</button>
            </div>
            <div id="profile-msg" class="small mt-2"></div>
        </div>
    </div>

    <!-- Change password -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <p class="page-section-title">Change Password</p>
            <div class="mb-2">
                <label class="form-label">Current password</label>
                <input type="password" id="current_password" class="form-control form-control-sm">
            </div>
            <div class="mb-2">
                <label class="form-label">New password</label>
                <input type="password" id="new_password" class="form-control form-control-sm">
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm new password</label>
                <input type="password" id="confirm_password" class="form-control form-control-sm">
            </div>
            <div class="text-center mt-3">
                <button onclick="changePassword()" class="btn btn-sm btn-outline-primary px-4">Change password</button>
            </div>
            <div id="pwd-msg" class="small mt-2"></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/app.js.php'; ?>
<script>
requireAuth();

function renderAvatarDisplay(u) {
    document.getElementById('avatar-display').innerHTML =
        mkAvatar(u.profile_picture, u.first_name, u.last_name, 'lg');
}

async function loadProfile() {
    try {
        const data = await api('GET', '/api/users/profile');
        const u    = data.user;
        document.getElementById('first_name').value = u.first_name || '';
        document.getElementById('last_name').value  = u.last_name  || '';
        document.getElementById('email').value      = u.email      || '';
        let meta = `Role: <strong>${u.role}</strong>`;
        if (u.faculty_number) meta += ` &nbsp;·&nbsp; Faculty No: <strong>${u.faculty_number}</strong>`;
        document.getElementById('profile-meta').innerHTML = meta;
        renderAvatarDisplay(u);
    } catch (err) {
        setMsg('profile-msg', err.message);
    }
}

async function uploadAvatar() {
    const input = document.getElementById('avatar-input');
    const file  = input.files?.[0];
    if (!file) { setMsg('avatar-msg', 'Please choose a file first.'); return; }

    const fd = new FormData();
    fd.append('avatar', file);

    try {
        const data   = await uploadFile('/api/users/avatar', fd);
        const stored = JSON.parse(localStorage.getItem('user') || '{}');
        localStorage.setItem('user', JSON.stringify({ ...stored, profile_picture: data.user.profile_picture }));
        renderAvatarDisplay(data.user);
        input.value = '';
        setMsg('avatar-msg', 'Photo updated.', 'success');
    } catch (err) {
        setMsg('avatar-msg', err.message);
    }
}

async function updateProfile() {
    const msgEl = document.getElementById('profile-msg');
    try {
        const data = await api('PUT', '/api/users/profile', {
            first_name: document.getElementById('first_name').value.trim(),
            last_name:  document.getElementById('last_name').value.trim(),
            email:      document.getElementById('email').value.trim(),
        });
        const stored = JSON.parse(localStorage.getItem('user') || '{}');
        localStorage.setItem('user', JSON.stringify({ ...stored, ...data.user }));
        setMsg('profile-msg', 'Profile updated.', 'success');
    } catch (err) {
        setMsg('profile-msg', err.message);
    }
}

async function changePassword() {
    const msgEl     = document.getElementById('pwd-msg');
    const newPwd    = document.getElementById('new_password').value;
    const confirmPwd = document.getElementById('confirm_password').value;
    if (newPwd !== confirmPwd) {
        setMsg('pwd-msg', 'Passwords do not match.');
        return;
    }
    try {
        const data = await api('POST', '/api/auth/change-password', {
            old_password: document.getElementById('current_password').value,
            new_password: newPwd,
        });
        localStorage.setItem('token', data.token);
        localStorage.setItem('user',  JSON.stringify(data.user));
        setMsg('pwd-msg', 'Password changed successfully.', 'success');
        document.getElementById('current_password').value = '';
        document.getElementById('new_password').value     = '';
        document.getElementById('confirm_password').value = '';
    } catch (err) {
        setMsg('pwd-msg', err.message);
    }
}

loadProfile();
</script>

<?php require_once __DIR__ . '/../partials/foot.php'; ?>
