<nav class="navbar navbar-public">
    <div class="container-fluid px-3 px-md-4">
        <!-- Brand / Logo -->
        <a class="navbar-brand" href="/">
            <img src="/assets/images/logo.png" alt="UCLP Academy" class="brand-logo">
        </a>

        <!-- Mobile Hamburger Button -->
        <button class="navbar-toggler" type="button" id="sidebarToggle" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Desktop Navigation (Always visible on desktop) -->
        <div class="desktop-nav">
            <ul class="nav-list">
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' || $_SERVER['REQUEST_URI'] == '/') ? 'active' : ''; ?>" href="/">
                        <i class="fas fa-home"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'browse.php' ? 'active' : ''; ?>" href="/browse">
                        <i class="fas fa-th-large"></i> Browse
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'committee.php' ? 'active' : ''; ?>" href="/committee">
                        <i class="fas fa-users"></i> Committee
                    </a>
                </li>
                <?php if (isAuthenticated()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false ? 'active' : ''; ?>" 
                           href="/<?php echo $_SESSION['user_type']; ?>/dashboard">
                            <i class="fas fa-chart-line"></i> Dashboard
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if (isAuthenticated()): ?>
                    <!-- User Dropdown - Desktop (Custom) -->
                    <li class="nav-item dropdown">
                        <a class="nav-link user-dropdown" href="#" id="userDropdown" role="button">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 2)); ?>
                            </div>
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                            <i class="fas fa-chevron-down dropdown-icon"></i>
                        </a>
                        <ul class="dropdown-menu" id="dropdownMenu">
                            <li>
                                <a class="dropdown-item" href="/<?php echo $_SESSION['user_type']; ?>/profile">
                                    <i class="fas fa-user"></i> Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="/<?php echo $_SESSION['user_type']; ?>/dashboard">
                                    <i class="fas fa-dashboard"></i> Dashboard
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="/logout">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link btn-login <?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : ''; ?>" href="/login">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn-register <?php echo basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : ''; ?>" href="/register">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Mobile Sidebar - Only shows on mobile -->
<div class="mobile-sidebar-overlay" id="sidebarOverlay"></div>
<div class="mobile-sidebar" id="mobileSidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <img src="/assets/images/logo.png" alt="UCLP Academy" class="sidebar-logo">
        </div>
        <button class="sidebar-close" id="sidebarClose">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="sidebar-body">
        <ul class="sidebar-nav">
            <li class="sidebar-item">
                <a class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' || $_SERVER['REQUEST_URI'] == '/') ? 'active' : ''; ?>" href="/">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'browse.php' ? 'active' : ''; ?>" href="/browse">
                    <i class="fas fa-th-large"></i>
                    <span>Browse</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'committee.php' ? 'active' : ''; ?>" href="/committee">
                    <i class="fas fa-users"></i>
                    <span>Committee</span>
                </a>
            </li>
            <?php if (isAuthenticated()): ?>
                <li class="sidebar-item">
                    <a class="sidebar-link <?php echo strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false ? 'active' : ''; ?>" 
                       href="/<?php echo $_SESSION['user_type']; ?>/dashboard">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-divider"></li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="/<?php echo $_SESSION['user_type']; ?>/profile">
                        <i class="fas fa-user"></i>
                        <span>Profile</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link text-danger" href="/logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            <?php else: ?>
                <li class="sidebar-divider"></li>
                <li class="sidebar-item">
                    <a class="sidebar-link btn-login-sidebar <?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : ''; ?>" href="/login">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Login</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link btn-register-sidebar <?php echo basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : ''; ?>" href="/register">
                        <i class="fas fa-user-plus"></i>
                        <span>Register</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
    
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <?php if (isAuthenticated()): ?>
                <div class="user-avatar-sidebar">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 2)); ?>
                </div>
                <div class="user-info">
                    <div class="user-name-sidebar"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></div>
                    <div class="user-role"><?php echo ucfirst($_SESSION['user_type'] ?? 'User'); ?></div>
                </div>
            <?php else: ?>
                <div class="user-info">
                    <div class="user-name-sidebar">Guest</div>
                    <div class="user-role">Not logged in</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Styles -->
<style>
/* ============================================
   PUBLIC NAVIGATION - PROFESSIONAL DESIGN
   ============================================ */

.navbar-public {
    background: #ffffff !important;
    padding: 0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    position: sticky;
    top: 0;
    z-index: 1000;
    border-bottom: 1px solid #eef1f5;
    min-height: 68px;
}

.navbar-public .container-fluid {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Brand / Logo */
.navbar-public .navbar-brand {
    display: flex;
    align-items: center;
    padding: 8px 0;
    text-decoration: none;
}

.navbar-public .brand-logo {
    height: 48px;
    width: auto;
    object-fit: contain;
    transition: transform 0.2s ease;
}

.navbar-public .navbar-brand:hover .brand-logo {
    transform: scale(1.03);
}

/* Desktop Navigation */
.navbar-public .desktop-nav {
    display: flex;
    align-items: center;
}

.navbar-public .nav-list {
    display: flex;
    align-items: center;
    gap: 6px;
    list-style: none;
    margin: 0;
    padding: 6px 0;
}

.navbar-public .nav-item {
    list-style: none;
    position: relative;
}

.navbar-public .nav-link {
    color: #000000;
    font-weight: 500;
    font-size: 0.9rem;
    padding: 8px 16px;
    border-radius: 6px;
    transition: all 0.25s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    cursor: pointer;
}

.navbar-public .nav-link i {
    font-size: 0.9rem;
    color: #4a4a5e;
    transition: color 0.25s ease;
}

.navbar-public .nav-link:hover {
    background: #f0f4f8;
    color: #0d6efd;
    transform: translateY(-1px);
    text-decoration: none;
}

.navbar-public .nav-link:hover i {
    color: #0d6efd;
}

.navbar-public .nav-link.active {
    color: #0d6efd;
    font-weight: 600;
    background: #e8f0fe;
}

.navbar-public .nav-link.active i {
    color: #0d6efd;
}

/* Login Button - Desktop */
.navbar-public .btn-login {
    color: #0d6efd;
    font-weight: 600;
    border: 1.5px solid #0d6efd;
    padding: 6px 20px;
    border-radius: 6px;
    transition: all 0.25s ease;
    background: transparent;
}

.navbar-public .btn-login:hover {
    background: #0d6efd;
    color: #ffffff !important;
    box-shadow: 0 2px 10px rgba(13,110,253,0.2);
    transform: translateY(-1px);
}

.navbar-public .btn-login:hover i {
    color: #ffffff !important;
}

/* Login Button - Active State */
.navbar-public .btn-login.active {
    background: #0d6efd;
    color: #ffffff !important;
    border-color: #0d6efd;
    box-shadow: 0 2px 10px rgba(13,110,253,0.15);
}

.navbar-public .btn-login.active i {
    color: #ffffff !important;
}

/* Register Button - Desktop */
.navbar-public .btn-register {
    color: #ffffff;
    font-weight: 600;
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
    padding: 6px 20px;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(13,110,253,0.15);
    transition: all 0.25s ease;
    border: none;
}

.navbar-public .btn-register:hover {
    background: linear-gradient(135deg, #0a58ca, #084298);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(13,110,253,0.3);
    color: #ffffff !important;
}

.navbar-public .btn-register i {
    color: #ffffff !important;
}

.navbar-public .btn-register:hover i {
    color: #ffffff !important;
}

/* Register Button - Active State */
.navbar-public .btn-register.active {
    background: linear-gradient(135deg, #0a58ca, #084298);
    box-shadow: 0 4px 15px rgba(13,110,253,0.25);
    transform: translateY(-1px);
    color: #ffffff !important;
}

.navbar-public .btn-register.active i {
    color: #ffffff !important;
}

/* User Dropdown - Desktop (Custom) */
.navbar-public .user-dropdown {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 12px 4px 4px;
    border-radius: 30px;
    background: #f8f9fa;
    border: 1px solid #eef1f5;
    transition: all 0.25s ease;
    cursor: pointer;
    color: #000000;
    text-decoration: none;
}

.navbar-public .user-dropdown:hover {
    background: #eef1f5;
    border-color: #d4d8dd;
    transform: translateY(-1px);
    color: #0d6efd;
}

.navbar-public .user-avatar {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    flex-shrink: 0;
}

.navbar-public .user-name {
    font-weight: 500;
    font-size: 0.85rem;
    color: #000000;
}

.navbar-public .dropdown-icon {
    font-size: 0.6rem;
    color: #4a4a5e;
    transition: transform 0.3s ease;
    margin-left: 2px;
}

.navbar-public .user-dropdown.open .dropdown-icon {
    transform: rotate(180deg);
}

/* Dropdown Menu - Desktop (Custom) */
.navbar-public .dropdown-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    z-index: 1000;
    border-radius: 8px;
    border: 1px solid #eef1f5;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    padding: 6px 4px;
    min-width: 180px;
    background: #ffffff;
    display: none;
    list-style: none;
    margin: 0;
}

.navbar-public .dropdown-menu.show {
    display: block;
    animation: dropdownFade 0.2s ease;
}

@keyframes dropdownFade {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.navbar-public .dropdown-item {
    border-radius: 6px;
    padding: 8px 14px;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.25s ease;
    color: #000000;
    text-decoration: none;
    width: 100%;
    clear: both;
    font-weight: 400;
    text-align: inherit;
    white-space: nowrap;
    background: transparent;
    border: 0;
    cursor: pointer;
}

.navbar-public .dropdown-item i {
    width: 18px;
    font-size: 0.85rem;
    color: #4a4a5e;
}

.navbar-public .dropdown-item:hover {
    background: #f0f4f8;
    color: #0d6efd;
}

.navbar-public .dropdown-item:hover i {
    color: #0d6efd;
}

.navbar-public .dropdown-item.text-danger:hover {
    background: #fce4ec;
    color: #dc3545 !important;
}

.navbar-public .dropdown-item.text-danger:hover i {
    color: #dc3545 !important;
}

.navbar-public .dropdown-divider {
    height: 0;
    margin: 4px 0;
    overflow: hidden;
    border-top: 1px solid #eef1f5;
}

/* Mobile Toggle Button - Hidden on Desktop */
.navbar-public .navbar-toggler {
    display: none;
    border: 1px solid #eef1f5;
    padding: 8px 10px;
    border-radius: 6px;
    background: #f8f9fa;
    transition: all 0.25s ease;
    cursor: pointer;
}

.navbar-public .navbar-toggler:hover {
    background: #eef1f5;
}

.navbar-public .navbar-toggler:focus {
    outline: none;
}

.navbar-public .navbar-toggler-icon {
    display: block;
    width: 24px;
    height: 24px;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(0, 0, 0, 0.8)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: center;
    background-size: contain;
}

/* ============================================
   MOBILE SIDEBAR - APP STYLE
   ============================================ */

/* Sidebar Overlay */
.mobile-sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1040;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.mobile-sidebar-overlay.active {
    display: block;
    opacity: 1;
}

/* Sidebar Container */
.mobile-sidebar {
    position: fixed;
    top: 0;
    right: -100%;
    width: 85%;
    max-width: 340px;
    height: 100vh;
    background: #ffffff;
    z-index: 1050;
    box-shadow: -4px 0 30px rgba(0, 0, 0, 0.1);
    transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.mobile-sidebar.open {
    right: 0;
}

/* Sidebar Header */
.sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #eef1f5;
    flex-shrink: 0;
}

.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 10px;
}

.sidebar-logo {
    height: 44px;
    width: auto;
    object-fit: contain;
}

.sidebar-close {
    width: 36px;
    height: 36px;
    border: none;
    background: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4a4a5e;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.sidebar-close:hover {
    background: #eef1f5;
    transform: rotate(90deg);
}

/* Sidebar Body */
.sidebar-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px 12px;
}

.sidebar-nav {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-item {
    margin-bottom: 2px;
}

.sidebar-link {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 16px;
    border-radius: 10px;
    color: #000000;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    cursor: pointer;
}

.sidebar-link i {
    width: 22px;
    font-size: 1rem;
    color: #6c757d;
    transition: color 0.2s ease;
}

.sidebar-link:hover {
    background: #f0f4f8;
    color: #0d6efd;
    text-decoration: none;
}

.sidebar-link:hover i {
    color: #0d6efd;
}

.sidebar-link.active {
    background: #e8f0fe;
    color: #0d6efd;
    font-weight: 600;
}

.sidebar-link.active i {
    color: #0d6efd;
}

.sidebar-link.text-danger {
    color: #dc3545;
}

.sidebar-link.text-danger i {
    color: #dc3545;
}

.sidebar-link.text-danger:hover {
    background: #fce4ec;
    color: #dc3545;
}

.sidebar-divider {
    height: 1px;
    background: #eef1f5;
    margin: 8px 16px;
}

/* Sidebar Login Button - OUTLINE */
.sidebar-link.btn-login-sidebar {
    border: 1.5px solid #0d6efd;
    justify-content: center;
    color: #0d6efd;
    margin-top: 4px;
    padding: 14px 16px;
    border-radius: 10px;
    font-weight: 600;
    background: transparent;
}

.sidebar-link.btn-login-sidebar:hover {
    background: #0d6efd;
    color: #ffffff !important;
}

.sidebar-link.btn-login-sidebar:hover i {
    color: #ffffff !important;
}

/* Sidebar Login Button - Active State */
.sidebar-link.btn-login-sidebar.active {
    background: #0d6efd;
    color: #ffffff !important;
    border-color: #0d6efd;
}

.sidebar-link.btn-login-sidebar.active i {
    color: #ffffff !important;
}

/* Sidebar Register Button - FILLED */
.sidebar-link.btn-register-sidebar {
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
    justify-content: center;
    color: #ffffff;
    margin-top: 4px;
    padding: 14px 16px;
    border-radius: 10px;
    font-weight: 600;
    border: none;
}

.sidebar-link.btn-register-sidebar:hover {
    background: linear-gradient(135deg, #0a58ca, #084298);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(13,110,253,0.3);
    color: #ffffff !important;
}

.sidebar-link.btn-register-sidebar i {
    color: #ffffff;
}

.sidebar-link.btn-register-sidebar:hover i {
    color: #ffffff !important;
}

/* Sidebar Register Button - Active State */
.sidebar-link.btn-register-sidebar.active {
    background: linear-gradient(135deg, #0a58ca, #084298);
    box-shadow: 0 4px 15px rgba(13,110,253,0.25);
    transform: translateY(-1px);
    color: #ffffff !important;
}

.sidebar-link.btn-register-sidebar.active i {
    color: #ffffff !important;
}

/* Sidebar Footer */
.sidebar-footer {
    flex-shrink: 0;
    padding: 16px 24px;
    border-top: 1px solid #eef1f5;
    background: #fafafa;
}

.sidebar-user {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar-sidebar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
}

.user-info {
    flex: 1;
}

.user-name-sidebar {
    font-weight: 600;
    font-size: 0.9rem;
    color: #000000;
}

.user-role {
    font-size: 0.75rem;
    color: #6c757d;
}

/* ============================================
   RESPONSIVE - MOBILE (≤991px)
   ============================================ */
@media (max-width: 991.98px) {
    .navbar-public {
        min-height: 60px;
    }

    .navbar-public .navbar-brand {
        padding: 6px 0;
    }

    .navbar-public .brand-logo {
        height: 40px;
    }

    /* Hide Desktop Navigation */
    .navbar-public .desktop-nav {
        display: none;
    }

    /* Show Mobile Toggle */
    .navbar-public .navbar-toggler {
        display: block;
    }
}

/* ============================================
   MOBILE SMALL (≤575px)
   ============================================ */
@media (max-width: 575.98px) {
    .navbar-public {
        min-height: 54px;
    }

    .navbar-public .navbar-brand {
        padding: 4px 0;
    }

    .navbar-public .brand-logo {
        height: 34px;
    }

    .mobile-sidebar {
        width: 88%;
        max-width: 300px;
    }

    .sidebar-logo {
        height: 38px;
    }

    .sidebar-header {
        padding: 16px 20px;
    }

    .sidebar-body {
        padding: 12px 10px;
    }

    .sidebar-link {
        padding: 10px 14px;
        font-size: 0.9rem;
    }

    .sidebar-link.btn-login-sidebar,
    .sidebar-link.btn-register-sidebar {
        padding: 12px 14px;
        font-size: 0.9rem;
    }

    .sidebar-footer {
        padding: 12px 20px;
    }

    .user-avatar-sidebar {
        width: 36px;
        height: 36px;
        font-size: 0.7rem;
    }

    .user-name-sidebar {
        font-size: 0.85rem;
    }
}

/* ============================================
   DESKTOP (≥992px)
   ============================================ */
@media (min-width: 992px) {
    .navbar-public .nav-list {
        gap: 6px;
    }

    .navbar-public .nav-link {
        padding: 8px 16px;
        font-size: 0.9rem;
        color: #000000;
    }

    .navbar-public .btn-login {
        padding: 6px 20px;
        font-size: 0.85rem;
    }

    .navbar-public .btn-register {
        padding: 6px 20px;
        font-size: 0.85rem;
    }

    .navbar-public .user-dropdown {
        padding: 4px 12px 4px 4px;
    }

    .navbar-public .user-name {
        color: #000000;
    }
}
</style>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // MOBILE SIDEBAR
    // ============================================
    const sidebar = document.getElementById('mobileSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('sidebarToggle');
    const closeBtn = document.getElementById('sidebarClose');

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    toggleBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        if (sidebar.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    closeBtn.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });

    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    sidebarLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 991.98) {
                closeSidebar();
            }
        });
    });

    window.addEventListener('resize', function() {
        if (window.innerWidth > 991.98 && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });

    // ============================================
    // DESKTOP DROPDOWN - FULLY CUSTOM (No Bootstrap JS)
    // ============================================
    const dropdownToggle = document.querySelector('.navbar-public .user-dropdown');
    const dropdownMenu = document.getElementById('dropdownMenu');

    if (dropdownToggle && dropdownMenu) {
        // Toggle dropdown on click
        dropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle open class on toggle button
            this.classList.toggle('open');
            
            // Toggle show class on menu
            dropdownMenu.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!dropdownToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('show');
                dropdownToggle.classList.remove('open');
            }
        });

        // Close dropdown on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && dropdownMenu.classList.contains('show')) {
                dropdownMenu.classList.remove('show');
                dropdownToggle.classList.remove('open');
            }
        });

        // Close dropdown when clicking on a dropdown item
        const dropdownItems = dropdownMenu.querySelectorAll('.dropdown-item');
        dropdownItems.forEach(function(item) {
            item.addEventListener('click', function() {
                dropdownMenu.classList.remove('show');
                dropdownToggle.classList.remove('open');
            });
        });
    }

    // ============================================
    // ACTIVE STATE - Home & Dashboard
    // ============================================
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.navbar-public .nav-link:not(.user-dropdown)');
    
    navLinks.forEach(function(link) {
        const href = link.getAttribute('href');
        if (href && href !== '#') {
            link.classList.remove('active');
            
            if (href === currentPath || 
                (href !== '/' && currentPath.startsWith(href + '/')) ||
                (href === '/' && currentPath === '/')) {
                link.classList.add('active');
            }
        }
    });
});
</script>