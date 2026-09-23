<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar-editor" style="background: #1a2035; width: 250px; height: 100vh; position: fixed; top: 0; left: 0; overflow-y: auto; padding: 0; z-index: 1000; transition: all 0.3s ease; display: flex; flex-direction: column;">
    <div class="sidebar-wrapper" style="display: flex; flex-direction: column; height: 100%;">
        <!-- Sidebar Header -->
        <div class="sidebar-header" style="padding: 20px 20px 15px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); flex-shrink: 0;">
            <div class="d-flex align-items-center gap-3">
                <div class="logo-icon" style="width: 40px; height: 40px; background: linear-gradient(135deg, #198754, #157347); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 1.2rem;">
                    <i class="fas fa-edit"></i>
                </div>
                <div>
                    <div style="color: #fff; font-weight: 700; font-size: 1rem;">BJDVL Editor</div>
                    <div style="color: rgba(255,255,255,0.4); font-size: 0.65rem; letter-spacing: 0.5px;">Editor Panel</div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar User Info -->
        <div style="padding: 15px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); flex-shrink: 0;">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar" style="width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #198754, #157347); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 0.9rem;">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'E', 0, 2)); ?>
                </div>
                <div>
                    <div style="color: #fff; font-weight: 500; font-size: 0.85rem;"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Editor'); ?></div>
                    <div style="color: rgba(255,255,255,0.4); font-size: 0.65rem;"><i class="fas fa-circle" style="color: #198754; font-size: 0.35rem;"></i> Online</div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Menu - Scrollable -->
        <div style="flex: 1; overflow-y: auto; padding: 10px 12px 0;">
            <ul class="nav flex-column">
                <!-- Home -->
                <li class="nav-item">
                    <a class="nav-link" href="/" 
                       style="color: rgba(255,255,255,0.7); padding: 10px 15px; margin: 2px 0; border-radius: 8px; transition: all 0.3s ease; text-decoration: none; display: block; font-size: 0.85rem; font-weight: 500;">
                        <i class="fas fa-home" style="margin-right: 12px; width: 20px; text-align: center;"></i> Home
                    </a>
                </li>
                
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                       href="/editor/dashboard" 
                       style="color: rgba(255,255,255,0.7); padding: 10px 15px; margin: 2px 0; border-radius: 8px; transition: all 0.3s ease; text-decoration: none; display: block; font-size: 0.85rem; font-weight: 500;">
                        <i class="fas fa-tachometer-alt" style="margin-right: 12px; width: 20px; text-align: center;"></i> Dashboard
                    </a>
                </li>
                
                <!-- Manage Books -->
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_books.php' ? 'active' : ''; ?>" 
                       href="/editor/books" 
                       style="color: rgba(255,255,255,0.7); padding: 10px 15px; margin: 2px 0; border-radius: 8px; transition: all 0.3s ease; text-decoration: none; display: block; font-size: 0.85rem; font-weight: 500;">
                        <i class="fas fa-book" style="margin-right: 12px; width: 20px; text-align: center;"></i> Manage Books
                    </a>
                </li>
                
                <!-- Manage Journals -->
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_journals.php' ? 'active' : ''; ?>" 
                       href="/editor/journals" 
                       style="color: rgba(255,255,255,0.7); padding: 10px 15px; margin: 2px 0; border-radius: 8px; transition: all 0.3s ease; text-decoration: none; display: block; font-size: 0.85rem; font-weight: 500;">
                        <i class="fas fa-newspaper" style="margin-right: 12px; width: 20px; text-align: center;"></i> Manage Journals
                    </a>
                </li>
                
                <!-- Supply Requests -->
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'supply_requests.php' ? 'active' : ''; ?>" 
                       href="/editor/requests" 
                       style="color: rgba(255,255,255,0.7); padding: 10px 15px; margin: 2px 0; border-radius: 8px; transition: all 0.3s ease; text-decoration: none; display: block; font-size: 0.85rem; font-weight: 500;">
                        <i class="fas fa-truck" style="margin-right: 12px; width: 20px; text-align: center;"></i> Supply Requests
                    </a>
                </li>
                
                <!-- Download Permissions -->
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_download_permissions.php' ? 'active' : ''; ?>" 
                       href="/editor/download-permissions" 
                       style="color: rgba(255,255,255,0.7); padding: 10px 15px; margin: 2px 0; border-radius: 8px; transition: all 0.3s ease; text-decoration: none; display: block; font-size: 0.85rem; font-weight: 500;">
                        <i class="fas fa-download" style="margin-right: 12px; width: 20px; text-align: center;"></i> Download Permissions
                    </a>
                </li>
                
                <!-- Audit Trail -->
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'audit_trail.php' ? 'active' : ''; ?>" 
                       href="/editor/audit" 
                       style="color: rgba(255,255,255,0.7); padding: 10px 15px; margin: 2px 0; border-radius: 8px; transition: all 0.3s ease; text-decoration: none; display: block; font-size: 0.85rem; font-weight: 500;">
                        <i class="fas fa-history" style="margin-right: 12px; width: 20px; text-align: center;"></i> Audit Trail
                    </a>
                </li>
                
                <!-- Profile Settings -->
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" 
                       href="/editor/profile" 
                       style="color: rgba(255,255,255,0.7); padding: 10px 15px; margin: 2px 0; border-radius: 8px; transition: all 0.3s ease; text-decoration: none; display: block; font-size: 0.85rem; font-weight: 500;">
                        <i class="fas fa-user-edit" style="margin-right: 12px; width: 20px; text-align: center;"></i> Profile Settings
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Sidebar Footer with Copyright - Fixed at Bottom -->
        <div style="padding: 15px 20px; border-top: 1px solid rgba(255,255,255,0.05); flex-shrink: 0; background: #1a2035;">
            <div style="color: rgba(255,255,255,0.15); font-size: 0.6rem; text-align: center; letter-spacing: 0.5px; line-height: 1.6;">
                <i class="fas fa-graduation-cap"></i> BJDVL Panel v2.0
                <br>
                &copy; <?php echo date('Y'); ?> UniMed UniHealth Group
                <br>
                <span style="color: rgba(255,255,255,0.08); font-size: 0.5rem;">All Rights Reserved</span>
            </div>
        </div>
    </div>
</nav>

<style>
.sidebar-editor {
    background: #1a2035 !important;
}

/* Hide sidebar on mobile & tablet */
@media (max-width: 991.98px) {
    .sidebar-editor {
        display: none !important;
    }
}

/* Show sidebar only on desktop & laptop */
@media (min-width: 992px) {
    .sidebar-editor {
        display: flex !important;
        position: fixed !important;
        top: 0;
        left: 0;
        width: 250px;
        height: 100vh;
        z-index: 1000;
    }
}

.sidebar-editor .nav-link:hover {
    background: rgba(255, 255, 255, 0.06);
    color: #ffffff !important;
}

.sidebar-editor .nav-link.active {
    background: linear-gradient(135deg, #198754, #157347);
    color: #ffffff !important;
    box-shadow: 0 4px 15px rgba(25, 135, 84, 0.25);
}

.sidebar-editor .nav-link.text-danger:hover {
    background: rgba(220, 53, 69, 0.12);
    color: #dc3545 !important;
}

/* Scrollbar Styling */
.sidebar-editor::-webkit-scrollbar {
    width: 4px;
}
.sidebar-editor::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.02);
}
.sidebar-editor::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.15);
    border-radius: 10px;
}
.sidebar-editor::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.25);
}
</style>