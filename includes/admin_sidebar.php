<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar-admin" style="background: #1a2035; width: 250px; height: 100vh; position: fixed; top: 0; left: 0; overflow-y: auto; padding: 0; z-index: 1000; transition: all 0.3s ease; display: flex; flex-direction: column;">
    <div class="sidebar-wrapper" style="display: flex; flex-direction: column; height: 100%;">
        <!-- Sidebar Header -->
        <div class="sidebar-header" style="padding: 20px 20px 15px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); flex-shrink: 0;">
            <div class="d-flex align-items-center gap-3">
                <div class="logo-icon" style="width: 40px; height: 40px; background: linear-gradient(135deg, #0d6efd, #0a58ca); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 1.2rem;">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div>
                    <div style="color: #fff; font-weight: 700; font-size: 1rem;">BJDVL Admin</div>
                    <div style="color: rgba(255,255,255,0.4); font-size: 0.65rem; letter-spacing: 0.5px;">Administrator Panel</div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar User Info -->
        <div style="padding: 15px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); flex-shrink: 0;">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar" style="width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #0d6efd, #0a58ca); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 0.9rem;">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 2)); ?>
                </div>
                <div>
                    <div style="color: #fff; font-weight: 500; font-size: 0.85rem;"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></div>
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
                       href="/admin/dashboard" 
                       style="color: rgba(255,255,255,0.7); padding: 10px 15px; margin: 2px 0; border-radius: 8px; transition: all 0.3s ease; text-decoration: none; display: block; font-size: 0.85rem; font-weight: 500;">
                        <i class="fas fa-tachometer-alt" style="margin-right: 12px; width: 20px; text-align: center;"></i> Dashboard
                    </a>
                </li>
                
                <!-- Manage Doctors -->
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_doctors.php' ? 'active' : ''; ?>" 
                       href="/admin/doctors" 
                       style="color: rgba(255,255,255,0.7); padding: 10px 15px; margin: 2px 0; border-radius: 8px; transition: all 0.3s ease; text-decoration: none; display: block; font-size: 0.85rem; font-weight: 500;">
                        <i class="fas fa-users" style="margin-right: 12px; width: 20px; text-align: center;"></i> Manage Doctors
                    </a>
                </li>
                
                <!-- Verify Doctors -->
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'verify_doctor.php' ? 'active' : ''; ?>" 
                       href="/admin/verify" 
                       style="color: rgba(255,255,255,0.7); padding: 10px 15px; margin: 2px 0; border-radius: 8px; transition: all 0.3s ease; text-decoration: none; display: block; font-size: 0.85rem; font-weight: 500;">
                        <i class="fas fa-user-check" style="margin-right: 12px; width: 20px; text-align: center;"></i> Verify Doctors
                    </a>
                </li>
                
                <!-- Manage Books -->
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_books.php' ? 'active' : ''; ?>" 
                       href="/admin/books" 
                       style="color: rgba(255,255,255,0.7); padding: 10px 15px; margin: 2px 0; border-radius: 8px; transition: all 0.3s ease; text-decoration: none; display: block; font-size: 0.85rem; font-weight: 500;">
                        <i class="fas fa-book" style="margin-right: 12px; width: 20px; text-align: center;"></i> Manage Books
                    </a>
                </li>
                
                <!-- Manage Journals -->
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_journals.php' ? 'active' : ''; ?>" 
                       href="/admin/journals" 
                       style="color: rgba(255,255,255,0.7); padding: 10px 15px; margin: 2px 0; border-radius: 8px; transition: all 0.3s ease; text-decoration: none; display: block; font-size: 0.85rem; font-weight: 500;">
                        <i class="fas fa-newspaper" style="margin-right: 12px; width: 20px; text-align: center;"></i> Manage Journals
                    </a>
                </li>
                
                <!-- Supply Requests -->
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'supply_requests.php' ? 'active' : ''; ?>" 
                       href="/admin/requests" 
                       style="color: rgba(255,255,255,0.7); padding: 10px 15px; margin: 2px 0; border-radius: 8px; transition: all 0.3s ease; text-decoration: none; display: block; font-size: 0.85rem; font-weight: 500;">
                        <i class="fas fa-truck" style="margin-right: 12px; width: 20px; text-align: center;"></i> Supply Requests
                    </a>
                </li>
                
                <!-- Download Permissions -->
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_download_permissions.php' ? 'active' : ''; ?>" 
                       href="/admin/download-permissions" 
                       style="color: rgba(255,255,255,0.7); padding: 10px 15px; margin: 2px 0; border-radius: 8px; transition: all 0.3s ease; text-decoration: none; display: block; font-size: 0.85rem; font-weight: 500;">
                        <i class="fas fa-download" style="margin-right: 12px; width: 20px; text-align: center;"></i> Download Permissions
                    </a>
                </li>
                
                <!-- Audit Trail -->
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'audit_trail.php' ? 'active' : ''; ?>" 
                       href="/admin/audit" 
                       style="color: rgba(255,255,255,0.7); padding: 10px 15px; margin: 2px 0; border-radius: 8px; transition: all 0.3s ease; text-decoration: none; display: block; font-size: 0.85rem; font-weight: 500;">
                        <i class="fas fa-history" style="margin-right: 12px; width: 20px; text-align: center;"></i> Audit Trail
                    </a>
                </li>

                <!-- ============================================
                     COMMITTEE SECTION DIVIDER
                     ============================================ -->
                <li class="nav-item" style="margin: 10px 15px 6px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.08);">
                    <span style="color: rgba(255,255,255,0.35); font-size: 0.65rem; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700;">
                        <i class="fas fa-users" style="margin-right: 6px;"></i> Committee
                    </span>
                </li>

                <!-- Designations -->
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'designations.php' ? 'active' : ''; ?>" 
                       href="/admin/designations.php"
                       style="color: rgba(255,255,255,0.7); padding: 10px 15px; margin: 2px 0; border-radius: 8px; transition: all 0.3s ease; text-decoration: none; display: block; font-size: 0.85rem; font-weight: 500;">
                        <i class="fas fa-user-tag" style="margin-right: 12px; width: 20px; text-align: center;"></i> Designations
                    </a>
                </li>

                <!-- Committee Years -->
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'committee_years.php' ? 'active' : ''; ?>" 
                       href="/admin/committee_years.php"
                       style="color: rgba(255,255,255,0.7); padding: 10px 15px; margin: 2px 0; border-radius: 8px; transition: all 0.3s ease; text-decoration: none; display: block; font-size: 0.85rem; font-weight: 500;">
                        <i class="fas fa-calendar-alt" style="margin-right: 12px; width: 20px; text-align: center;"></i> Committee Years
                    </a>
                </li>

                <!-- Committee Members -->
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'committee_members.php' || basename($_SERVER['PHP_SELF']) == 'edit_committee_member.php' ? 'active' : ''; ?>" 
                       href="/admin/committee_members.php"
                       style="color: rgba(255,255,255,0.7); padding: 10px 15px; margin: 2px 0; border-radius: 8px; transition: all 0.3s ease; text-decoration: none; display: block; font-size: 0.85rem; font-weight: 500;">
                        <i class="fas fa-users-cog" style="margin-right: 12px; width: 20px; text-align: center;"></i> Committee Members
                    </a>
                </li>
                
                <!-- Profile Settings -->
                <li class="nav-item" style="margin-top: 6px;">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" 
                       href="/admin/profile" 
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
.sidebar-admin {
    background: #1a2035 !important;
}

@media (max-width: 991.98px) {
    .sidebar-admin { display: none !important; }
}

@media (min-width: 992px) {
    .sidebar-admin {
        display: flex !important;
        position: fixed !important;
        top: 0;
        left: 0;
        width: 250px;
        height: 100vh;
        z-index: 1000;
    }
}

.sidebar-admin .nav-link:hover {
    background: rgba(255, 255, 255, 0.06);
    color: #ffffff !important;
}

.sidebar-admin .nav-link.active {
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
    color: #ffffff !important;
    box-shadow: 0 4px 15px rgba(13, 110, 253, 0.25);
}

.sidebar-admin::-webkit-scrollbar { width: 4px; }
.sidebar-admin::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); }
.sidebar-admin::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 10px; }
.sidebar-admin::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.25); }
</style>