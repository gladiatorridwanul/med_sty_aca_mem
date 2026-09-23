<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireAdmin();

$db = Database::getInstance()->getConnection();

// Get pending doctors
$pendingDoctors = $db->query("SELECT * FROM users WHERE user_type = 'doctor' AND is_verified = 0 ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$verifiedDoctors = $db->query("SELECT * FROM users WHERE user_type = 'doctor' AND is_verified = 1 ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Handle verification
if (isset($_POST['verify']) && isset($_POST['user_id'])) {
    $userId = intval($_POST['user_id']);
    $status = intval($_POST['status'] ?? 1);
    
    $stmt = $db->prepare("UPDATE users SET is_verified = ?, updated_at = NOW() WHERE id = ? AND user_type = 'doctor'");
    $stmt->bind_param("ii", $status, $userId);
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], 'Doctor Verification', "Verified doctor ID: $userId, Status: $status");
        $success = "Doctor verification updated successfully.";
        // Refresh data
        $pendingDoctors = $db->query("SELECT * FROM users WHERE user_type = 'doctor' AND is_verified = 0 ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
        $verifiedDoctors = $db->query("SELECT * FROM users WHERE user_type = 'doctor' AND is_verified = 1 ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
    } else {
        $error = "Failed to update verification status.";
    }
    $stmt->close();
}

// Get statistics
$stats = [
    'total' => $db->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'doctor'")->fetch_assoc()['count'],
    'pending' => count($pendingDoctors),
    'verified' => count($verifiedDoctors),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Doctors - BJDVL</title>
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
        .page-header .total-count {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.8rem;
            color: #6c757d;
        }
        .page-header .total-count strong {
            color: #000000;
        }
        
        /* ============================================
           STATISTICS CARDS
           ============================================ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .stat-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px 14px;
            border: 1px solid #eef1f5;
            transition: all 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        }
        .stat-card .stat-number {
            font-size: 1.4rem;
            font-weight: 700;
            color: #000000;
            line-height: 1.2;
            font-family: 'Cambria', Georgia, serif;
        }
        .stat-card .stat-label {
            font-size: 0.65rem;
            color: #6c757d;
            font-weight: 500;
            font-family: 'Cambria', Georgia, serif;
            margin-top: 1px;
        }
        .stat-card .stat-icon {
            float: right;
            font-size: 1.5rem;
            opacity: 0.15;
            color: #000000;
        }
        .stat-card.border-total { border-left: 3px solid #0d6efd; }
        .stat-card.border-pending { border-left: 3px solid #f39c12; }
        .stat-card.border-verified { border-left: 3px solid #198754; }
        
        /* ============================================
           DOCTOR CARDS
           ============================================ */
        .doctor-cards-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .doctor-card {
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #eef1f5;
            overflow: hidden;
        }
        
        .doctor-card .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px;
            border-bottom: 1px solid #eef1f5;
            background: #f8f9fa;
            font-weight: 700;
            font-size: 0.85rem;
            color: #000000;
            font-family: 'Cambria', Georgia, serif;
        }
        .doctor-card .card-header .badge-count {
            font-size: 0.65rem;
            padding: 2px 10px;
            border-radius: 12px;
            font-weight: 600;
        }
        .doctor-card .card-header .badge-count.pending { background: #fff3e0; color: #f39c12; }
        .doctor-card .card-header .badge-count.verified { background: #e8f5e9; color: #198754; }
        
        .doctor-card .card-body {
            padding: 0;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .doctor-card .card-body table {
            margin: 0;
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.78rem;
            width: 100%;
            min-width: 400px;
            background: #ffffff;
        }
        .doctor-card .card-body table thead th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            border-bottom: 2px solid #eef1f5;
            padding: 6px 10px;
            white-space: nowrap;
            font-size: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .doctor-card .card-body table tbody td {
            padding: 6px 10px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f4f8;
            color: #000000;
            font-size: 0.78rem;
        }
        .doctor-card .card-body table tbody tr:hover {
            background: #f8f9fa;
        }
        .doctor-card .card-body table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* ============================================
           DOCTOR AVATAR
           ============================================ */
        .doctor-avatar-small {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
        }
        
        /* ============================================
           ACTION BUTTONS
           ============================================ */
        .action-btn {
            padding: 3px 12px;
            border-radius: 4px;
            border: none;
            font-size: 0.65rem;
            font-weight: 600;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
            font-family: 'Cambria', Georgia, serif;
            display: inline-block;
        }
        .action-btn:hover {
            transform: translateY(-1px);
        }
        .action-btn.verify { background: #e8f5e9; color: #198754; }
        .action-btn.verify:hover { background: #198754; color: #fff; }
        .action-btn.revoke { background: #fce4ec; color: #dc3545; }
        .action-btn.revoke:hover { background: #dc3545; color: #fff; }
        
        .action-group {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        }
        
        /* ============================================
           EMPTY STATE
           ============================================ */
        .empty-state {
            text-align: center;
            padding: 20px 16px;
            background: #ffffff;
        }
        .empty-state .icon {
            font-size: 1.5rem;
            color: #198754;
            margin-bottom: 4px;
        }
        .empty-state p {
            color: #6c757d;
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.78rem;
            margin: 0;
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
            .doctor-cards-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }
            .page-header h1 {
                font-size: 1.1rem;
            }
            .doctor-card .card-body table {
                min-width: 350px;
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
                gap: 4px;
                padding-bottom: 10px;
                margin-bottom: 12px;
            }
            .page-header h1 {
                font-size: 1rem;
            }
            .page-header h1 i {
                font-size: 0.9rem;
            }
            .page-header .total-count {
                font-size: 0.7rem;
            }
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 6px;
            }
            .stat-card {
                padding: 8px 10px;
            }
            .stat-card .stat-number {
                font-size: 1.1rem;
            }
            .stat-card .stat-label {
                font-size: 0.55rem;
            }
            .stat-card .stat-icon {
                font-size: 1.2rem;
            }
            .doctor-cards-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            .doctor-card .card-body table {
                min-width: 320px;
                font-size: 0.72rem;
            }
            .doctor-card .card-body table thead th {
                padding: 4px 6px;
                font-size: 0.55rem;
            }
            .doctor-card .card-body table tbody td {
                padding: 4px 6px;
                font-size: 0.72rem;
            }
            .doctor-card .card-header {
                font-size: 0.78rem;
                padding: 8px 10px;
            }
            .doctor-card .card-header .badge-count {
                font-size: 0.55rem;
                padding: 1px 8px;
            }
            .doctor-avatar-small {
                width: 24px;
                height: 24px;
                font-size: 0.6rem;
            }
            .action-btn {
                font-size: 0.55rem;
                padding: 2px 8px;
            }
            .empty-state {
                padding: 14px 12px;
            }
            .empty-state .icon {
                font-size: 1.2rem;
            }
            .empty-state p {
                font-size: 0.7rem;
            }
        }
        
        /* Extra Small */
        @media (max-width: 400px) {
            .main-content {
                padding: 8px 4px 10px;
            }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 4px;
            }
            .stat-card {
                padding: 6px 8px;
            }
            .stat-card .stat-number {
                font-size: 0.9rem;
            }
            .stat-card .stat-label {
                font-size: 0.5rem;
            }
            .stat-card .stat-icon {
                font-size: 1rem;
            }
            .doctor-card .card-body table {
                min-width: 280px;
                font-size: 0.68rem;
            }
            .doctor-card .card-body table thead th {
                padding: 3px 4px;
                font-size: 0.5rem;
            }
            .doctor-card .card-body table tbody td {
                padding: 3px 4px;
                font-size: 0.68rem;
            }
            .action-btn {
                font-size: 0.5rem;
                padding: 1px 6px;
            }
            .page-header h1 {
                font-size: 0.9rem;
            }
            .doctor-card .card-header {
                font-size: 0.7rem;
                padding: 6px 8px;
            }
            .doctor-avatar-small {
                width: 20px;
                height: 20px;
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
            .stat-card {
                background: #f8f9fa !important;
                border-color: #eef1f5 !important;
            }
            .stat-card .stat-number {
                color: #000000 !important;
            }
            .stat-card .stat-label {
                color: #6c757d !important;
            }
            .stat-card .stat-icon {
                color: #000000 !important;
            }
            .doctor-card {
                background: #f8f9fa !important;
                border-color: #eef1f5 !important;
            }
            .doctor-card .card-header {
                background: #f8f9fa !important;
                border-bottom-color: #eef1f5 !important;
                color: #000000 !important;
            }
            .doctor-card .card-body table {
                background: #ffffff !important;
            }
            .doctor-card .card-body table thead th {
                background: #f8f9fa !important;
                color: #495057 !important;
                border-bottom-color: #eef1f5 !important;
            }
            .doctor-card .card-body table tbody td {
                color: #000000 !important;
                border-bottom-color: #f0f4f8 !important;
            }
            .doctor-card .card-body table tbody tr:hover {
                background: #f8f9fa !important;
            }
            .page-header {
                border-bottom-color: #eef1f5 !important;
            }
            .page-header h1 {
                color: #000000 !important;
            }
            .page-header .total-count {
                color: #6c757d !important;
            }
            .page-header .total-count strong {
                color: #000000 !important;
            }
            .empty-state {
                background: #ffffff !important;
            }
            .empty-state p {
                color: #6c757d !important;
            }
            .doctor-card .card-header .badge-count.pending { background: #fff3e0 !important; color: #f39c12 !important; }
            .doctor-card .card-header .badge-count.verified { background: #e8f5e9 !important; color: #198754 !important; }
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
                        <i class="fas fa-user-check"></i> Doctor Verification
                    </h1>
                    <span class="total-count">
                        <strong><?php echo $stats['total']; ?></strong> total doctors
                    </span>
                </div>
                
                <!-- Alert Messages -->
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" style="border-radius: 6px; font-family: 'Cambria', Georgia, serif; font-size: 0.85rem;">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" style="border-radius: 6px; font-family: 'Cambria', Georgia, serif; font-size: 0.85rem;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card border-total">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-number"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total Doctors</div>
                    </div>
                    <div class="stat-card border-pending">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-number"><?php echo $stats['pending']; ?></div>
                        <div class="stat-label">Pending Verification</div>
                    </div>
                    <div class="stat-card border-verified">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-number"><?php echo $stats['verified']; ?></div>
                        <div class="stat-label">Verified Doctors</div>
                    </div>
                </div>
                
                <!-- Doctor Cards Grid -->
                <div class="doctor-cards-grid">
                    
                    <!-- ============================================
                    PENDING DOCTORS
                    ============================================ -->
                    <div class="doctor-card">
                        <div class="card-header">
                            <span><i class="fas fa-clock" style="color: #f39c12;"></i> Pending Verification</span>
                            <span class="badge-count pending"><?php echo $stats['pending']; ?></span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($pendingDoctors)): ?>
                            <div class="empty-state">
                                <div class="icon"><i class="fas fa-check-circle"></i></div>
                                <p>All doctors are verified!</p>
                            </div>
                            <?php else: ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Avatar</th>
                                        <th>Name</th>
                                        <th>Specialty</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingDoctors as $doctor): ?>
                                    <tr>
                                        <td>
                                            <div class="doctor-avatar-small">
                                                <?php echo strtoupper(substr($doctor['name'], 0, 2)); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($doctor['name']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($doctor['email']); ?></small>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($doctor['specialty'] ?? 'N/A'); ?></small></td>
                                        <td>
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo $doctor['id']; ?>">
                                                <input type="hidden" name="status" value="1">
                                                <button type="submit" name="verify" class="action-btn verify">
                                                    <i class="fas fa-check"></i> Verify
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- ============================================
                    VERIFIED DOCTORS
                    ============================================ -->
                    <div class="doctor-card">
                        <div class="card-header">
                            <span><i class="fas fa-check-circle" style="color: #198754;"></i> Verified Doctors</span>
                            <span class="badge-count verified"><?php echo $stats['verified']; ?></span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($verifiedDoctors)): ?>
                            <div class="empty-state">
                                <div class="icon"><i class="fas fa-users" style="color: #6c757d;"></i></div>
                                <p>No verified doctors yet.</p>
                            </div>
                            <?php else: ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Avatar</th>
                                        <th>Name</th>
                                        <th>Specialty</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($verifiedDoctors as $doctor): ?>
                                    <tr>
                                        <td>
                                            <div class="doctor-avatar-small">
                                                <?php echo strtoupper(substr($doctor['name'], 0, 2)); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($doctor['name']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($doctor['email']); ?></small>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($doctor['specialty'] ?? 'N/A'); ?></small></td>
                                        <td>
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo $doctor['id']; ?>">
                                                <input type="hidden" name="status" value="0">
                                                <button type="submit" name="verify" class="action-btn revoke">
                                                    <i class="fas fa-times"></i> Revoke
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>