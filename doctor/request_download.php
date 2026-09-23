<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireLogin();

$userId = $_SESSION['user_id'];
$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$type || !$id || !in_array($type, ['book', 'journal'])) {
    header("Location: /doctor/books");
    exit;
}

$db = Database::getInstance()->getConnection();

// Get item details
if ($type === 'book') {
    $stmt = $db->prepare("SELECT id, title FROM books WHERE id = ? AND status = 'approved'");
} else {
    $stmt = $db->prepare("SELECT id, title FROM journals WHERE id = ? AND status = 'approved'");
}
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
$stmt->close();

if (!$item) {
    header("Location: /doctor/" . $type . "s");
    exit;
}

$message = null;
$messageType = null;
$existingRequest = null;
$canRequest = true;

// Check existing request
$checkStmt = $db->prepare("SELECT id, status, max_downloads, used_downloads, expires_at FROM download_permissions 
                           WHERE user_id = ? AND item_id = ? AND item_type = ? 
                           AND status IN ('pending', 'approved') 
                           AND (expires_at IS NULL OR expires_at > NOW())");
$checkStmt->bind_param("iis", $userId, $id, $type);
$checkStmt->execute();
$result = $checkStmt->get_result();
$existingRequest = $result->fetch_assoc();
$checkStmt->close();

// Determine if user can request
if ($existingRequest) {
    if ($existingRequest['status'] == 'pending') {
        $canRequest = false;
        $message = "You already have a pending request for this item. Please wait for admin approval.";
        $messageType = 'warning';
    } elseif ($existingRequest['status'] == 'approved') {
        $remaining = $existingRequest['max_downloads'] - $existingRequest['used_downloads'];
        if ($remaining > 0) {
            $canRequest = false;
            $message = "You still have <strong>$remaining</strong> download(s) remaining for this item. Use them before requesting again.";
            $messageType = 'info';
        } else {
            $canRequest = true;
            $message = "You have used all your downloads. You can request a new permission.";
            $messageType = 'info';
        }
    }
}

// Handle download permission request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_download'])) {
    if ($canRequest) {
        $result = requestDownloadPermission($userId, $id, $type);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'danger';
        
        if ($result['success']) {
            header("Location: /doctor/view-" . $type . "/" . $id . "?message=" . urlencode($message) . "&type=success");
            exit;
        }
    }
}

$user = getUserById($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Download - UCLP Academy</title>
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
        
        .top-nav {
            display: flex;
            align-items: center;
            margin-bottom: 14px;
        }
        .top-nav .btn-back {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.78rem;
            padding: 4px 14px;
            border-radius: 6px;
            border: 1px solid #eef1f5;
            background: transparent;
            color: #6c757d;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .top-nav .btn-back:hover { background: #f8f9fa; color: #000000; }
        
        .request-card {
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #eef1f5;
            overflow: hidden;
            max-width: 600px;
            margin: 0 auto;
        }
        .request-card .card-header {
            background: linear-gradient(135deg, #fd7e14, #dc6b0a);
            padding: 14px 20px;
            border-bottom: none;
            color: #fff;
        }
        .request-card .card-header h4 { font-weight: 700; margin: 0; font-size: 1.1rem; font-family: 'Cambria', Georgia, serif; }
        .request-card .card-body { padding: 20px; }
        
        .item-info {
            background: #f8f9fa;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 14px;
            border: 1px solid #eef1f5;
        }
        .item-info .label { color: #6c757d; font-size: 0.75rem; font-family: 'Cambria', Georgia, serif; }
        .item-info .value { font-weight: 600; font-size: 0.9rem; font-family: 'Cambria', Georgia, serif; color: #000000; }
        
        .btn-submit {
            background: linear-gradient(135deg, #fd7e14, #dc6b0a);
            border: none;
            color: #fff;
            padding: 9px 18px;
            border-radius: 6px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.9rem;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(253,126,20,0.3); color: #fff; }
        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }
        .btn-submit:disabled:hover { transform: none; box-shadow: none; }
        
        .status-badge {
            padding: 3px 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.7rem;
            font-family: 'Cambria', Georgia, serif;
            display: inline-block;
        }
        .status-badge.pending { background: #fff3e0; color: #f39c12; }
        .status-badge.approved { background: #e8f5e9; color: #198754; }
        .status-badge.used { background: #e9ecef; color: #6c757d; }
        
        .status-box {
            background: #f8f9fa;
            padding: 10px 14px;
            border-radius: 6px;
            border-left: 4px solid #0d6efd;
            margin-bottom: 14px;
        }
        .status-box .status-label { font-size: 0.75rem; color: #6c757d; font-family: 'Cambria', Georgia, serif; }
        .status-box .status-value { font-weight: 600; font-size: 0.9rem; font-family: 'Cambria', Georgia, serif; color: #000000; }
        .status-box .status-detail { font-size: 0.8rem; margin-top: 4px; font-family: 'Cambria', Georgia, serif; color: #000000; }
        
        @media (max-width: 991.98px) {
            .main-content { margin-left: 0 !important; max-width: 100% !important; padding: 12px 14px 16px; }
        }
        
        @media (max-width: 575.98px) {
            .main-content { padding: 10px 8px 14px; }
            .request-card .card-body { padding: 14px; }
            .request-card .card-header h4 { font-size: 1rem; }
            .item-info { padding: 8px 12px; }
            .item-info .value { font-size: 0.82rem; }
            .btn-submit { font-size: 0.82rem; padding: 8px 14px; }
        }
        
        @media (prefers-color-scheme: dark) {
            body { background: #ffffff !important; }
            .main-content { background: #ffffff !important; }
            .request-card { background: #ffffff !important; border-color: #eef1f5 !important; }
            .item-info { background: #f8f9fa !important; border-color: #eef1f5 !important; }
            .item-info .value { color: #000000 !important; }
            .status-box { background: #f8f9fa !important; }
            .status-box .status-value { color: #000000 !important; }
            .status-box .status-detail { color: #000000 !important; }
            .top-nav .btn-back { color: #6c757d !important; border-color: #eef1f5 !important; }
            .top-nav .btn-back:hover { background: #f8f9fa !important; color: #000000 !important; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/doctor_nav.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/doctor_sidebar.php'; ?>
            
            <main class="main-content">
                <div class="top-nav">
                    <a href="/doctor/<?php echo $type; ?>s" class="btn-back"><i class="fas fa-arrow-left"></i> Back to <?php echo ucfirst($type); ?>s</a>
                </div>

                <div class="request-card">
                    <div class="card-header">
                        <h4><i class="fas fa-download me-2"></i> Request Download Permission</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message && $messageType): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" style="border-radius: 6px; font-family: 'Cambria', Georgia, serif; font-size: 0.85rem;">
                            <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : ($messageType == 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?>"></i> 
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <div class="item-info">
                            <div class="row">
                                <div class="col-md-6 mb-2 mb-md-0">
                                    <div class="label">Item Type</div>
                                    <div class="value"><span class="badge bg-primary" style="font-size: 0.7rem; font-family: 'Cambria', Georgia, serif;"><?php echo ucfirst($type); ?></span></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="label">Title</div>
                                    <div class="value"><?php echo htmlspecialchars($item['title']); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($existingRequest && $existingRequest['status'] == 'approved'): ?>
                            <?php 
                            $remaining = $existingRequest['max_downloads'] - $existingRequest['used_downloads'];
                            $isUsedUp = $remaining <= 0;
                            ?>
                            <div class="status-box" style="border-left-color: <?php echo $isUsedUp ? '#dc3545' : '#198754'; ?>;">
                                <div class="status-label">Current Status</div>
                                <div class="status-value">
                                    <span class="status-badge <?php echo $isUsedUp ? 'used' : 'approved'; ?>">
                                        <?php echo $isUsedUp ? 'Downloads Used' : 'Approved'; ?>
                                    </span>
                                </div>
                                <div class="status-detail">
                                    <strong><?php echo $existingRequest['used_downloads']; ?></strong> of <strong><?php echo $existingRequest['max_downloads']; ?></strong> downloads used
                                    <?php if ($isUsedUp): ?>
                                    <br><span style="color: #dc3545;"><i class="fas fa-exclamation-circle"></i> You have used all your downloads. Request a new permission.</span>
                                    <?php else: ?>
                                    <br><span style="color: #198754;"><i class="fas fa-check-circle"></i> You have <strong><?php echo $remaining; ?></strong> download(s) remaining.</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php elseif ($existingRequest && $existingRequest['status'] == 'pending'): ?>
                            <div class="status-box" style="border-left-color: #f39c12;">
                                <div class="status-label">Current Status</div>
                                <div class="status-value">
                                    <span class="status-badge pending">Pending</span>
                                </div>
                                <div class="status-detail">
                                    Your request is waiting for admin approval.
                                    <br><small class="text-muted" style="font-family: 'Cambria', Georgia, serif;">You will be notified once it's processed.</small>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($canRequest): ?>
                            <div class="alert alert-info" style="border-radius: 6px; font-family: 'Cambria', Georgia, serif; font-size: 0.8rem;">
                                <i class="fas fa-info-circle"></i> 
                                <strong>Download Limit:</strong> You will be allowed to download this <?php echo $type; ?> up to <strong>2 times</strong> after approval.
                            </div>
                            <form method="POST" action="">
                                <button type="submit" name="request_download" class="btn-submit">
                                    <i class="fas fa-paper-plane"></i> Submit Download Request
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="mt-3 text-center">
                                <a href="/doctor/view-<?php echo $type; ?>/<?php echo $id; ?>" class="text-muted" style="font-family: 'Cambria', Georgia, serif; text-decoration: none;">
                                    <i class="fas fa-arrow-left"></i> Go Back
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>