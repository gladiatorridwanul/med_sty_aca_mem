<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireEditor();

$userId = $_SESSION['user_id'];
$user = getUserById($userId);

$error = null;
$success = null;

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
                logActivity($userId, 'Profile Updated', 'Editor updated profile information');
                $user = getUserById($userId);
            } else {
                $error = 'Failed to update profile';
            }
            $stmt->close();
        }
    }
}

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
            logActivity($userId, 'Password Changed', 'Editor changed password');
        } else {
            $error = 'Failed to update password';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor Profile - UCLP Academy</title>
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
        
        .main-content {
            margin-left: 250px;
            padding: 16px 20px 20px;
            min-height: 100vh;
            transition: all 0.3s ease;
            max-width: calc(100% - 250px);
            overflow-x: hidden;
            background: #ffffff;
        }
        
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
        .page-header h1 i { color: #198754; margin-right: 8px; }
        .page-header .badge-role {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.7rem;
            padding: 4px 12px;
            border-radius: 12px;
        }
        
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
            padding: 10px 14px;
            border-bottom: 1px solid #eef1f5;
            background: #f8f9fa;
            font-weight: 700;
            font-size: 0.85rem;
            color: #000000;
            font-family: 'Cambria', Georgia, serif;
        }
        .profile-card .card-header i { margin-right: 6px; }
        .profile-card .card-body { padding: 16px; }
        
        .profile-avatar-wrapper { text-align: center; }
        .profile-avatar {
            width: 110px; height: 110px; border-radius: 50%;
            object-fit: cover; border: 3px solid #198754; padding: 3px; background: #ffffff;
        }
        .profile-name { font-weight: 700; font-size: 1.1rem; color: #000000; margin: 8px 0 2px; font-family: 'Cambria', Georgia, serif; }
        .profile-email { color: #6c757d; font-size: 0.85rem; font-family: 'Cambria', Georgia, serif; }
        
        .badge-role-tag {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.65rem;
            padding: 3px 12px;
            border-radius: 12px;
            display: inline-block;
            margin: 4px 2px;
        }
        .badge-role-tag.editor { background: #e8f5e9; color: #198754; }
        .badge-role-tag.active { background: #e8f5e9; color: #198754; }
        .badge-role-tag.inactive { background: #fce4ec; color: #dc3545; }
        
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
        .form-control:focus { border-color: #198754; box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.08); }
        
        .btn {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 6px 18px;
            border-radius: 6px;
        }
        .btn-success { background: #198754; border: none; color: #fff; }
        .btn-success:hover { background: #157347; color: #fff; }
        .btn-warning { background: #f39c12; border: none; color: #fff; }
        .btn-warning:hover { background: #e08e0b; color: #fff; }
        
        .stats-mini-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 4px;
        }
        .stat-mini {
            background: #ffffff; border-radius: 6px; padding: 8px 10px;
            border: 1px solid #eef1f5; text-align: center;
        }
        .stat-mini .stat-number { font-size: 0.9rem; font-weight: 700; color: #000000; font-family: 'Cambria', Georgia, serif; }
        .stat-mini .stat-label { font-size: 0.55rem; color: #6c757d; font-family: 'Cambria', Georgia, serif; }
        
        @media (max-width: 991.98px) {
            .main-content { margin-left: 0 !important; max-width: 100% !important; padding: 12px 14px 16px; }
            .profile-grid { grid-template-columns: 1fr; gap: 12px; }
            .page-header h1 { font-size: 1.1rem; }
        }
        
        @media (max-width: 575.98px) {
            .main-content { padding: 10px 8px 14px; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 6px; padding-bottom: 10px; margin-bottom: 12px; }
            .page-header h1 { font-size: 1rem; }
            .page-header .badge-role { font-size: 0.6rem; padding: 3px 10px; }
            .profile-avatar { width: 80px; height: 80px; }
            .profile-name { font-size: 1rem; }
            .profile-email { font-size: 0.8rem; }
            .profile-card .card-header { font-size: 0.78rem; padding: 8px 10px; }
            .profile-card .card-body { padding: 12px; }
            .form-label { font-size: 0.78rem; }
            .form-control { font-size: 0.85rem; padding: 6px 10px; }
            .btn { font-size: 0.78rem; padding: 5px 14px; width: 100%; margin-top: 4px; }
            .stats-mini-grid { grid-template-columns: 1fr 1fr; gap: 4px; }
            .stat-mini .stat-number { font-size: 0.8rem; }
            .stat-mini .stat-label { font-size: 0.5rem; }
        }
        
        @media (max-width: 400px) {
            .profile-avatar { width: 65px; height: 65px; }
            .profile-name { font-size: 0.9rem; }
            .profile-card .card-body { padding: 10px; }
            .page-header h1 { font-size: 0.9rem; }
        }
        
        @media (prefers-color-scheme: dark) {
            body { background: #ffffff !important; }
            .main-content { background: #ffffff !important; }
            .profile-card { background: #f8f9fa !important; border-color: #eef1f5 !important; }
            .profile-card .card-header { background: #f8f9fa !important; border-bottom-color: #eef1f5 !important; color: #000000 !important; }
            .profile-name { color: #000000 !important; }
            .profile-email { color: #6c757d !important; }
            .form-label { color: #000000 !important; }
            .form-control { background: #ffffff !important; border-color: #eef1f5 !important; color: #000000 !important; }
            .stat-mini { background: #ffffff !important; border-color: #eef1f5 !important; }
            .stat-mini .stat-number { color: #000000 !important; }
            .page-header { border-bottom-color: #eef1f5 !important; }
            .page-header h1 { color: #000000 !important; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/editor_nav.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/editor_sidebar.php'; ?>
            
            <main class="main-content">
                <div class="page-header">
                    <h1><i class="fas fa-user-edit"></i> Editor Profile</h1>
                    <span class="badge bg-success badge-role">Editor</span>
                </div>
                
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
                
                <div class="profile-grid">
                    <div>
                        <div class="profile-card">
                            <div class="card-body profile-avatar-wrapper">
                                <img src="/uploads/profiles/<?php echo $user['profile_image'] ?? 'default.jpg'; ?>" alt="Profile" class="profile-avatar">
                                <div class="profile-name"><?php echo htmlspecialchars($user['name']); ?></div>
                                <div class="profile-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                <div>
                                    <span class="badge-role-tag editor">Editor</span>
                                    <span class="badge-role-tag <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></span>
                                </div>
                                <hr style="border-color: #eef1f5; margin: 10px 0;">
                                <div class="stats-mini-grid">
                                    <div class="stat-mini"><div class="stat-number"><?php echo $user['id']; ?></div><div class="stat-label">User ID</div></div>
                                    <div class="stat-mini"><div class="stat-number"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></div><div class="stat-label">Member Since</div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="profile-card">
                            <div class="card-header"><i class="fas fa-edit"></i> Edit Profile</div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                    </div>
                                    <button type="submit" name="update_profile" class="btn btn-success"><i class="fas fa-save"></i> Update Profile</button>
                                </form>
                            </div>
                        </div>
                        
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
                                            <small class="text-muted" style="font-family: 'Cambria', Georgia, serif; font-size: 0.65rem;">Min 8 characters</small>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" name="confirm_password" required>
                                        </div>
                                    </div>
                                    <button type="submit" name="update_password" class="btn btn-warning"><i class="fas fa-key"></i> Change Password</button>
                                </form>
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