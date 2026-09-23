<nav class="navbar navbar-doctor">
    <div class="container-fluid px-3 px-md-4">
        <!-- Brand / Logo -->
        <a class="navbar-brand" href="/doctor/dashboard">
            <div class="brand-icon">
                <i class="fas fa-user-md"></i>
            </div>
            <div class="brand-text">
                BJDVL <span>Doctor</span>
            </div>
        </a>

        <!-- Mobile Hamburger Button -->
        <button class="navbar-toggler" type="button" id="sidebarToggle" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Desktop Navigation - Only User Dropdown -->
        <div class="desktop-nav">
            <ul class="nav-list">
                <!-- User Dropdown - Desktop (Only visible on desktop) -->
                <li class="nav-item dropdown">
                    <a class="nav-link user-dropdown" href="#" id="userDropdown" role="button">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'D', 0, 2)); ?>
                        </div>
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Doctor'); ?></span>
                        <i class="fas fa-chevron-down dropdown-icon"></i>
                    </a>
                    <ul class="dropdown-menu" id="dropdownMenu">
                        <li>
                            <a class="dropdown-item" href="/doctor/profile">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/doctor/dashboard">
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
            </ul>
        </div>
    </div>
</nav>

<!-- Mobile Sidebar - Only shows on mobile (Full Menu) -->
<div class="mobile-sidebar-overlay" id="sidebarOverlay"></div>
<div class="mobile-sidebar" id="mobileSidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <i class="fas fa-user-md"></i>
            </div>
            <div class="brand-text">
                UCLP<span>Doctor</span>
            </div>
        </div>
        <button class="sidebar-close" id="sidebarClose">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="sidebar-body">
        <ul class="sidebar-nav">
            <li class="sidebar-item">
                <a class="sidebar-link" href="/">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                   href="/doctor/dashboard">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'books.php' ? 'active' : ''; ?>" 
                   href="/doctor/books">
                    <i class="fas fa-book"></i>
                    <span>Books</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'journals.php' ? 'active' : ''; ?>" 
                   href="/doctor/journals">
                    <i class="fas fa-newspaper"></i>
                    <span>Journals</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'my_requests.php' ? 'active' : ''; ?>" 
                   href="/doctor/requests">
                    <i class="fas fa-tasks"></i>
                    <span>Requests</span>
                </a>
            </li>
            <li class="sidebar-divider"></li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="/doctor/profile">
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
        </ul>
    </div>
    
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar-sidebar">
                <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'D', 0, 2)); ?>
            </div>
            <div class="user-info">
                <div class="user-name-sidebar"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Doctor'); ?></div>
                <div class="user-role">Doctor</div>
            </div>
        </div>
    </div>
</div>

<!-- Styles -->
<style>
/* ============================================
   DOCTOR NAVIGATION - PROFESSIONAL DESIGN
   ============================================ */

.navbar-doctor {
    background: #ffffff !important;
    padding: 0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    position: sticky;
    top: 0;
    z-index: 1000;
    border-bottom: 1px solid #eef1f5;
    min-height: 68px;
}

.navbar-doctor .container-fluid {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.navbar-doctor .navbar-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 0;
    text-decoration: none;
}

.navbar-doctor .brand-icon {
    width: 38px;
    height: 38px;
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.1rem;
    transition: transform 0.2s ease;
}

.navbar-doctor .navbar-brand:hover .brand-icon {
    transform: scale(1.05);
}

.navbar-doctor .brand-text {
    font-weight: 800;
    font-size: 1.1rem;
    color: #000000;
    letter-spacing: -0.3px;
}

.navbar-doctor .brand-text span {
    color: #0d6efd;
}

/* Desktop Navigation - Only User Dropdown */
.navbar-doctor .desktop-nav {
    display: flex;
    align-items: center;
}

.navbar-doctor .nav-list {
    display: flex;
    align-items: center;
    gap: 6px;
    list-style: none;
    margin: 0;
    padding: 6px 0;
}

.navbar-doctor .nav-item {
    list-style: none;
    position: relative;
}

/* User Dropdown - Desktop */
.navbar-doctor .user-dropdown {
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

.navbar-doctor .user-dropdown:hover {
    background: #eef1f5;
    border-color: #d4d8dd;
    transform: translateY(-1px);
    color: #0d6efd;
}

.navbar-doctor .user-avatar {
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

.navbar-doctor .user-name {
    font-weight: 500;
    font-size: 0.85rem;
    color: #000000;
}

.navbar-doctor .dropdown-icon {
    font-size: 0.6rem;
    color: #4a4a5e;
    transition: transform 0.3s ease;
    margin-left: 2px;
}

.navbar-doctor .user-dropdown.open .dropdown-icon {
    transform: rotate(180deg);
}

/* Dropdown Menu - Desktop */
.navbar-doctor .dropdown-menu {
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

.navbar-doctor .dropdown-menu.show {
    display: block;
    animation: dropdownFade 0.2s ease;
}

@keyframes dropdownFade {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.navbar-doctor .dropdown-item {
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

.navbar-doctor .dropdown-item i {
    width: 18px;
    font-size: 0.85rem;
    color: #4a4a5e;
}

.navbar-doctor .dropdown-item:hover {
    background: #f0f4f8;
    color: #0d6efd;
}

.navbar-doctor .dropdown-item:hover i {
    color: #0d6efd;
}

.navbar-doctor .dropdown-item.text-danger:hover {
    background: #fce4ec;
    color: #dc3545 !important;
}

.navbar-doctor .dropdown-item.text-danger:hover i {
    color: #dc3545 !important;
}

.navbar-doctor .dropdown-divider {
    height: 0;
    margin: 4px 0;
    overflow: hidden;
    border-top: 1px solid #eef1f5;
}

/* Mobile Toggle Button - Hidden on Desktop */
.navbar-doctor .navbar-toggler {
    display: none;
    border: 1px solid #eef1f5;
    padding: 8px 10px;
    border-radius: 6px;
    background: #f8f9fa;
    transition: all 0.25s ease;
    cursor: pointer;
}

.navbar-doctor .navbar-toggler:hover {
    background: #eef1f5;
}

.navbar-doctor .navbar-toggler:focus {
    outline: none;
}

.navbar-doctor .navbar-toggler-icon {
    display: block;
    width: 24px;
    height: 24px;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(0, 0, 0, 0.8)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: center;
    background-size: contain;
}

/* ============================================
   MOBILE SIDEBAR - APP STYLE (Full Menu)
   ============================================ */

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

.sidebar-brand .brand-icon {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1rem;
}

.sidebar-brand .brand-text {
    font-weight: 800;
    font-size: 1rem;
    color: #000000;
    letter-spacing: -0.3px;
}

.sidebar-brand .brand-text span {
    color: #0d6efd;
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
   RESPONSIVE
   ============================================ */

/* Tablet & Mobile - Show Hamburger, Hide Desktop Nav */
@media (max-width: 991.98px) {
    .navbar-doctor {
        min-height: 60px;
    }
    .navbar-doctor .navbar-brand {
        padding: 10px 0;
    }
    .navbar-doctor .brand-icon {
        width: 34px;
        height: 34px;
        font-size: 1rem;
    }
    .navbar-doctor .brand-text {
        font-size: 1rem;
        color: #000000;
    }
    .navbar-doctor .desktop-nav {
        display: none;
    }
    .navbar-doctor .navbar-toggler {
        display: block;
    }
}

/* Mobile Small */
@media (max-width: 575.98px) {
    .navbar-doctor {
        min-height: 54px;
    }
    .navbar-doctor .navbar-brand {
        padding: 8px 0;
        gap: 8px;
    }
    .navbar-doctor .brand-icon {
        width: 30px;
        height: 30px;
        font-size: 0.85rem;
        border-radius: 6px;
    }
    .navbar-doctor .brand-text {
        font-size: 0.9rem;
        color: #000000;
    }
    .mobile-sidebar {
        width: 88%;
        max-width: 300px;
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

/* Desktop - Show Only User Dropdown */
@media (min-width: 992px) {
    .navbar-doctor .nav-list {
        gap: 6px;
    }
    .navbar-doctor .user-dropdown {
        padding: 4px 12px 4px 4px;
    }
    .navbar-doctor .user-name {
        color: #000000;
    }
}
</style>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile Sidebar
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

    // Desktop Dropdown
    const dropdownToggle = document.querySelector('.navbar-doctor .user-dropdown');
    const dropdownMenu = document.getElementById('dropdownMenu');

    if (dropdownToggle && dropdownMenu) {
        dropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.toggle('open');
            dropdownMenu.classList.toggle('show');
        });

        document.addEventListener('click', function(e) {
            if (!dropdownToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('show');
                dropdownToggle.classList.remove('open');
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && dropdownMenu.classList.contains('show')) {
                dropdownMenu.classList.remove('show');
                dropdownToggle.classList.remove('open');
            }
        });

        const dropdownItems = dropdownMenu.querySelectorAll('.dropdown-item');
        dropdownItems.forEach(function(item) {
            item.addEventListener('click', function() {
                dropdownMenu.classList.remove('show');
                dropdownToggle.classList.remove('open');
            });
        });
    }
});
</script>