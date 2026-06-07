<nav class="navbar navbar-expand-md site-nav navbar-dark">
    <div class="container-lg">
        <a class="navbar-brand fw-bold" href="/dashboard">VWR</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
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
        ul.innerHTML = li('/login', 'Login') + li('/register', 'Register');
        return;
    }

    ul.innerHTML = li('/dashboard', 'Dashboard')
        + li('/rooms', 'Rooms')
        + li('/stats', 'Statistics')
        + li('/profile', user.first_name || 'Profile')
        + `<li class="nav-item ms-md-2">
               <button class="btn btn-sm btn-outline-light"
                   onclick="localStorage.removeItem('token');localStorage.removeItem('user');window.location.href='/login'">
                   Logout
               </button>
           </li>`;
})();
</script>
