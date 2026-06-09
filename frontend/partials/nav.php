<nav class="navbar navbar-expand-md site-nav navbar-dark">
    <div class="container-lg">
        <a class="navbar-brand fw-bold" href="/dashboard">VWR</a>
        <button class="navbar-toggler" type="button" aria-label="Toggle navigation"
            onclick="var m=document.getElementById('navMenu');m.classList.toggle('show')">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="navbar-collapse" id="navMenu">
            <ul id="nav-links" class="navbar-nav ms-auto align-items-md-center gap-md-1"></ul>
        </div>
    </div>
</nav>
<script>
(function () {
    const user = JSON.parse(localStorage.getItem('user') || 'null');
    const ul   = document.getElementById('nav-links');
    const path = window.location.pathname;

    function li(href, label) {
        const active = (path === href || (href !== '/dashboard' && path.startsWith(href))) ? ' active' : '';
        return `<li class="nav-item"><a class="nav-link${active}" href="${href}">${label}</a></li>`;
    }

    if (!user) {
        ul.className = 'navbar-nav mx-auto align-items-md-center gap-md-4';
        ul.innerHTML = `
            <li class="nav-item"><a class="btn btn-outline-light btn-sm" href="/login">Login</a></li>
            <li class="nav-item"><a class="btn btn-outline-light btn-sm" href="/register">Register</a></li>`;
        return;
    }

    let roleLinks = '';
    if (user.role === 'admin') {
        roleLinks = li('/dashboard', 'Dashboard') + li('/admin/users', 'Users');
    } else if (user.role === 'teacher') {
        roleLinks = li('/dashboard', 'Dashboard') + li('/rooms', 'Rooms') + li('/stats', 'Statistics');
    } else {
        roleLinks = li('/rooms', 'Rooms');
    }

    ul.innerHTML = roleLinks
        + li('/profile', 'Profile')
        + `<li class="nav-item ms-md-2">
               <button class="btn btn-sm btn-outline-light"
                   onclick="localStorage.removeItem('token');localStorage.removeItem('user');window.location.href='/login'">
                   Logout
               </button>
           </li>`;
})();
</script>
