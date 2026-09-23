<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireAdmin();

$userId = $_SESSION['user_id'];
$user = getUserById($userId);

$error = null;
$success = null;

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required';
    } elseif (!validateEmail($email)) {
        $error = 'Invalid email address';
    } else {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error = 'Email already in use by another account';
        } else {
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssi", $name, $email, $userId);
            if ($stmt->execute()) {
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $success = 'Profile updated successfully';
                logActivity($userId, 'Profile Updated', 'Admin updated profile information');
                $user = getUserById($userId);
            } else {
                $error = 'Failed to update profile';
            }
            $stmt->close();
        }
    }
}

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password)) {
        $error = 'Current password is required';
    } elseif (!verifyPassword($current_password, $user['password'])) {
        $error = 'Current password is incorrect';
    } elseif (strlen($new_password) < 8) {
        $error = 'New password must be at least 8 characters';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        $db = Database::getInstance()->getConnection();
        $hashedPassword = hashPassword($new_password);
        $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);
        if ($stmt->execute()) {
            $success = 'Password updated successfully';
            logActivity($userId, 'Password Changed', 'Admin changed password');
        } else {
            $error = 'Failed to update password';
        }
        $stmt->close();
    }
}

// Handle profile image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $uploadResult = uploadFile($_FILES['profile_image'], PROFILE_UPLOAD_PATH, ['jpg', 'jpeg', 'png', 'gif']);
    if ($uploadResult['success']) {
        // Delete old image if exists
        if ($user['profile_image'] && $user['profile_image'] !== 'default.jpg' && file_exists(PROFILE_UPLOAD_PATH . $user['profile_image'])) {
            unlink(PROFILE_UPLOAD_PATH . $user['profile_image']);
        }
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE users SET profile_image = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $uploadResult['filename'], $userId);
        if ($stmt->execute()) {
            $success = 'Profile image updated successfully';
            $user = getUserById($userId);
        }
        $stmt->close();
    } else {
        $error = 'Failed to upload image: ' . $uploadResult['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - BJDVL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cambria&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Cambria', Georgia, serif;
            background: #ffffff;
            color: #000000;
            overflow-x: hidden;
        }
        
        /* ============================================
           MAIN CONTENT AREA
           ============================================ */
        .main-content {
            margin-left: 250px;
            padding: 16px 20px 20px;
            min-height: 100vh;
            transition: all 0.3s ease;
            max-width: calc(100% - 250px);
            overflow-x: hidden;
            background: #ffffff;
        }
        
        /* ============================================
           PAGE HEADER
           ============================================ */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            padding: 0 0 12px 0;
            border-bottom: 1px solid #eef1f5;
            margin-bottom: 16px;
        }
        .page-header h1 {
            font-weight: 700;
            font-size: 1.3rem;
            color: #000000;
            margin: 0;
            font-family: 'Cambria', Georgia, serif;
        }
        .page-header h1 i {
            color: #0d6efd;
            margin-right: 8px;
        }
        .page-header .status-badge {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.7rem;
            padding: 4px 12px;
            border-radius: 12px;
        }
        
        /* ============================================
           PROFILE CARDS
           ============================================ */
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 16px;
        }
        
        .profile-card {
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #eef1f5;
            overflow: hidden;
        }
        
        .profile-card .card-header {
            padding: 12px 16px;
            border-bottom: 1px solid #eef1f5;
            background: #f8f9fa;
            font-weight: 700;
            font-size: 0.9rem;
            color: #000000;
            font-family: 'Cambria', Georgia, serif;
        }
        .profile-card .card-header i {
            margin-right: 6px;
        }
        .profile-card .card-body {
            padding: 16px;
        }
        
        /* ============================================
           PROFILE AVATAR
           ============================================ */
        .profile-avatar-wrapper {
            text-align: center;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #0d6efd;
            padding: 3px;
            background: #ffffff;
        }
        .profile-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: #000000;
            margin: 8px 0 2px;
            font-family: 'Cambria', Georgia, serif;
        }
        .profile-email {
            color: #6c757d;
            font-size: 0.85rem;
            font-family: 'Cambria', Georgia, serif;
        }
        
        .badge-role {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.65rem;
            padding: 3px 12px;
            border-radius: 12px;
            display: inline-block;
            margin: 4px 2px;
        }
        .badge-role.admin { background: #e8f0fe; color: #0d6efd; }
        .badge-role.active { background: #e8f5e9; color: #198754; }
        .badge-role.inactive { background: #fce4ec; color: #dc3545; }
        
        /* ============================================
           FORM ELEMENTS
           ============================================ */
        .form-label {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            color: #000000;
            font-size: 0.82rem;
        }
        .form-control {
            border-radius: 6px;
            border: 1.5px solid #eef1f5;
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.9rem;
            color: #000000;
            background: #ffffff;
            padding: 7px 12px;
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13,110,253,0.08);
        }
        .form-control:disabled {
            background: #f8f9fa;
            color: #6c757d;
        }
        
        .btn {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 6px 18px;
            border-radius: 6px;
        }
        .btn-primary { background: #0d6efd; border: none; }
        .btn-primary:hover { background: #0a58ca; }
        .btn-warning { background: #f39c12; border: none; color: #fff; }
        .btn-warning:hover { background: #e08e0b; color: #fff; }
        .btn-success { background: #198754; border: none; }
        .btn-success:hover { background: #157347; }
        
        /* ============================================
           STATISTICS
           ============================================ */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 8px;
        }
        .stat-item {
            background: #ffffff;
            border-radius: 6px;
            padding: 10px 12px;
            border: 1px solid #eef1f5;
            text-align: center;
        }
        .stat-item .stat-number {
            font-size: 1.2rem;
            font-weight: 700;
            color: #000000;
            font-family: 'Cambria', Georgia, serif;
        }
        .stat-item .stat-label {
            font-size: 0.6rem;
            color: #6c757d;
            font-family: 'Cambria', Georgia, serif;
        }
        
        /* ============================================
           ACTIVITY ITEMS
           ============================================ */
        .activity-item {
            padding: 8px 12px;
            border-left: 3px solid #0d6efd;
            margin-bottom: 6px;
            background: #ffffff;
            border-radius: 4px;
            border: 1px solid #eef1f5;
            border-left-width: 3px;
        }
        .activity-item .activity-action {
            font-weight: 600;
            color: #000000;
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.8rem;
        }
        .activity-item .activity-details {
            font-size: 0.7rem;
            color: #6c757d;
            font-family: 'Cambria', Georgia, serif;
        }
        .activity-item .activity-time {
            font-size: 0.65rem;
            color: #adb5bd;
            font-family: 'Cambria', Georgia, serif;
        }
        
        /* ============================================
           RESPONSIVE
           ============================================ */
        
        /* Tablet - Hide Sidebar */
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0 !important;
                max-width: 100% !important;
                padding: 12px 14px 16px;
            }
            .profile-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .page-header h1 {
                font-size: 1.1rem;
            }
        }
        
        /* Mobile */
        @media (max-width: 575.98px) {
            .main-content {
                padding: 10px 8px 14px;
            }
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
                padding-bottom: 10px;
                margin-bottom: 12px;
            }
            .page-header h1 {
                font-size: 1rem;
            }
            .page-header h1 i {
                font-size: 0.9rem;
            }
            .page-header .status-badge {
                font-size: 0.6rem;
                padding: 3px 10px;
            }
            .profile-avatar {
                width: 90px;
                height: 90px;
            }
            .profile-name {
                font-size: 1rem;
            }
            .profile-email {
                font-size: 0.8rem;
            }
            .profile-card .card-header {
                font-size: 0.8rem;
                padding: 10px 12px;
            }
            .profile-card .card-body {
                padding: 12px;
            }
            .form-label {
                font-size: 0.78rem;
            }
            .form-control {
                font-size: 0.85rem;
                padding: 6px 10px;
            }
            .btn {
                font-size: 0.78rem;
                padding: 5px 14px;
                width: 100%;
                margin-top: 4px;
            }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 6px;
            }
            .stat-item .stat-number {
                font-size: 1rem;
            }
            .stat-item .stat-label {
                font-size: 0.55rem;
            }
            .activity-item {
                padding: 6px 10px;
            }
            .activity-item .activity-action {
                font-size: 0.75rem;
            }
            .activity-item .activity-details {
                font-size: 0.65rem;
            }
            .activity-item .activity-time {
                font-size: 0.6rem;
            }
            .badge-role {
                font-size: 0.55rem;
                padding: 2px 8px;
            }
            .profile-avatar-wrapper {
                padding: 4px 0;
            }
        }
        
        /* Extra Small */
        @media (max-width: 400px) {
            .main-content {
                padding: 8px 4px 10px;
            }
            .profile-avatar {
                width: 75px;
                height: 75px;
            }
            .profile-name {
                font-size: 0.9rem;
            }
            .profile-email {
                font-size: 0.75rem;
            }
            .profile-card .card-header {
                font-size: 0.75rem;
                padding: 8px 10px;
            }
            .profile-card .card-body {
                padding: 10px;
            }
            .form-label {
                font-size: 0.72rem;
            }
            .form-control {
                font-size: 0.8rem;
                padding: 5px 8px;
            }
            .btn {
                font-size: 0.72rem;
                padding: 4px 10px;
            }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 4px;
            }
            .stat-item {
                padding: 6px 8px;
            }
            .stat-item .stat-number {
                font-size: 0.9rem;
            }
            .stat-item .stat-label {
                font-size: 0.5rem;
            }
        }
        
        /* ============================================
           DARK MODE OVERRIDE - Keep white
           ============================================ */
        @media (prefers-color-scheme: dark) {
            body {
                background: #ffffff !important;
            }
            .main-content {
                background: #ffffff !important;
            }
            .profile-card {
                background: #f8f9fa !important;
                border-color: #eef1f5 !important;
            }
            .profile-card .card-header {
                background: #f8f9fa !important;
                border-bottom-color: #eef1f5 !important;
                color: #000000 !important;
            }
            .profile-card .card-body {
                background: #f8f9fa !important;
            }
            .profile-name {
                color: #000000 !important;
            }
            .profile-email {
                color: #6c757d !important;
            }
            .form-label {
                color: #000000 !important;
            }
            .form-control {
                background: #ffffff !important;
                border-color: #eef1f5 !important;
                color: #000000 !important;
            }
            .form-control:disabled {
                background: #f8f9fa !important;
                color: #6c757d !important;
            }
            .stat-item {
                background: #ffffff !important;
                border-color: #eef1f5 !important;
            }
            .stat-item .stat-number {
                color: #000000 !important;
            }
            .stat-item .stat-label {
                color: #6c757d !important;
            }
            .activity-item {
                background: #ffffff !important;
                border-color: #eef1f5 !important;
            }
            .activity-item .activity-action {
                color: #000000 !important;
            }
            .activity-item .activity-details {
                color: #6c757d !important;
            }
            .activity-item .activity-time {
                color: #adb5bd !important;
            }
            .page-header {
                border-bottom-color: #eef1f5 !important;
            }
            .page-header h1 {
                color: #000000 !important;
            }
            .badge-role.admin { background: #e8f0fe !important; color: #0d6efd !important; }
            .badge-role.active { background: #e8f5e9 !important; color: #198754 !important; }
            .badge-role.inactive { background: #fce4ec !important; color: #dc3545 !important; }
        }
    </style>
</head>
<body>
    <!-- ============================================
    NAVIGATION
    ============================================ -->
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>
    
    <!-- ============================================
    SIDEBAR
    ============================================ -->
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
            
            <!-- ============================================
            MAIN CONTENT
            ============================================ -->
            <main class="main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <h1>
                        <i class="fas fa-user-cog"></i> Admin Profile
                    </h1>
                    <span class="badge bg-success status-badge">
                        <i class="fas fa-check-circle"></i> Active
                    </span>
                </div>
                
                <!-- Alert Messages -->
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" style="border-radius: 6px; font-family: 'Cambria', Georgia, serif; font-size: 0.85rem;">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" style="border-radius: 6px; font-family: 'Cambria', Georgia, serif; font-size: 0.85rem;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Profile Grid -->
                <div class="profile-grid">
                    
                    <!-- ============================================
                    LEFT COLUMN - PROFILE IMAGE & STATS
                    ============================================ -->
                    <div>
                        <!-- Profile Image Card -->
                        <div class="profile-card">
                            <div class="card-body profile-avatar-wrapper">
                                <img src="/uclp_academy/uploads/profiles/<?php echo $user['profile_image'] ?? 'default.jpg'; ?>" 
                                     alt="Profile" class="profile-avatar">
                                <div class="profile-name"><?php echo htmlspecialchars($user['name']); ?></div>
                                <div class="profile-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                <div>
                                    <span class="badge-role admin">Administrator</span>
                                    <span class="badge-role <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                                
                                <hr style="border-color: #eef1f5; margin: 12px 0;">
                                
                                <div style="font-weight: 600; font-size: 0.8rem; color: #000000; font-family: 'Cambria', Georgia, serif; margin-bottom: 8px;">
                                    <i class="fas fa-upload"></i> Update Profile Image
                                </div>
                                <form method="POST" action="" enctype="multipart/form-data">
                                    <div class="mb-2">
                                        <input type="file" class="form-control form-control-sm" name="profile_image" accept="image/*" style="font-size: 0.75rem; padding: 4px 8px;">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm w-100" style="font-size: 0.75rem; padding: 4px 12px;">
                                        <i class="fas fa-upload"></i> Upload Image
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Account Statistics -->
                        <div class="profile-card" style="margin-top: 12px;">
                            <div class="card-header"><i class="fas fa-chart-bar"></i> Account Statistics</div>
                            <div class="card-body">
                                <div class="stats-grid">
                                    <div class="stat-item">
                                        <div class="stat-number"><?php echo $user['id']; ?></div>
                                        <div class="stat-label">User ID</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></div>
                                        <div class="stat-label">Member Since</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ============================================
                    RIGHT COLUMN - PROFILE FORM & ACTIVITIES
                    ============================================ -->
                    <div>
                        <!-- Edit Profile -->
                        <div class="profile-card">
                            <div class="card-header"><i class="fas fa-edit"></i> Edit Profile Information</div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="name" 
                                                   value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" name="email" 
                                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">User Type</label>
                                            <input type="text" class="form-control" value="Administrator" disabled>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Account Status</label>
                                            <input type="text" class="form-control" value="<?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>" disabled>
                                        </div>
                                    </div>
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Profile
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Change Password -->
                        <div class="profile-card" style="margin-top: 12px;">
                            <div class="card-header"><i class="fas fa-key"></i> Change Password</div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Current Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" name="current_password" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">New Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" name="new_password" required>
                                            <small class="text-muted" style="font-family: 'Cambria', Georgia, serif; font-size: 0.65rem;">Minimum 8 characters</small>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" name="confirm_password" required>
                                        </div>
                                    </div>
                                    <button type="submit" name="update_password" class="btn btn-warning">
                                        <i class="fas fa-key"></i> Change Password
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Recent Activities -->
                        <div class="profile-card" style="margin-top: 12px;">
                            <div class="card-header"><i class="fas fa-history"></i> Recent Activities</div>
                            <div class="card-body">
                                <?php
                                $db = Database::getInstance()->getConnection();
                                $activities = $db->query("SELECT action, details, created_at FROM audit_trail WHERE user_id = $userId ORDER BY created_at DESC LIMIT 10");
                                if ($activities->num_rows > 0):
                                ?>
                                <div>
                                    <?php while ($activity = $activities->fetch_assoc()): ?>
                                    <div class="activity-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="activity-action"><?php echo htmlspecialchars($activity['action']); ?></div>
                                                <div class="activity-details"><?php echo htmlspecialchars($activity['details'] ?? ''); ?></div>
                                            </div>
                                            <span class="activity-time"><?php echo date('M d, Y h:i A', strtotime($activity['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                                <?php else: ?>
                                <p class="text-muted" style="font-family: 'Cambria', Georgia, serif; font-size: 0.85rem; margin: 0;">No recent activities</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>