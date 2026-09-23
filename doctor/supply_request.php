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

$error = null;
$success = null;
$user = getUserById($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deliveryAddress = sanitize($_POST['delivery_address'] ?? '');
    $deliveryPhone = sanitize($_POST['delivery_phone'] ?? '');
    
    if (empty($deliveryAddress)) {
        $error = 'Please provide delivery address';
    } else {
        $requestId = createSupplyRequest($userId, $id, $type, $deliveryAddress, $deliveryPhone);
        if ($requestId) {
            $success = 'Your request has been submitted successfully. You will be notified when it is processed.';
        } else {
            $error = 'Failed to submit request. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Print Copy - UCLP Academy</title>
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
        
        .top-nav { display: flex; align-items: center; margin-bottom: 14px; }
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
            background: linear-gradient(135deg, #f39c12, #e08e0b);
            padding: 14px 20px;
            border-bottom: none;
            color: #ffffff;
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
            background: linear-gradient(135deg, #f39c12, #e08e0b);
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
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(243,156,18,0.3); color: #fff; }
        
        .success-card { text-align: center; padding: 30px; }
        .success-card .icon { font-size: 3rem; color: #198754; margin-bottom: 15px; }
        .success-card h4 { font-weight: 700; margin-bottom: 10px; font-family: 'Cambria', Georgia, serif; color: #000000; }
        .success-card p { color: #6c757d; font-family: 'Cambria', Georgia, serif; }
        
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
            .success-card { padding: 20px; }
            .success-card .icon { font-size: 2.5rem; }
        }
        
        @media (prefers-color-scheme: dark) {
            body { background: #ffffff !important; }
            .main-content { background: #ffffff !important; }
            .request-card { background: #ffffff !important; border-color: #eef1f5 !important; }
            .item-info { background: #f8f9fa !important; border-color: #eef1f5 !important; }
            .item-info .value { color: #000000 !important; }
            .success-card h4 { color: #000000 !important; }
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
                    <?php if ($success): ?>
                    <div class="success-card">
                        <div class="icon"><i class="fas fa-check-circle"></i></div>
                        <h4>Request Submitted Successfully!</h4>
                        <p><?php echo $success; ?></p>
                        <div class="mt-3">
                            <a href="/doctor/<?php echo $type; ?>s" class="btn btn-primary btn-sm" style="font-family: 'Cambria', Georgia, serif;"><i class="fas fa-arrow-left"></i> Continue Browsing</a>
                            <a href="/doctor/requests" class="btn btn-outline-primary btn-sm" style="font-family: 'Cambria', Georgia, serif;"><i class="fas fa-tasks"></i> View My Requests</a>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="card-header">
                        <h4><i class="fas fa-print me-2"></i> Request Print Copy</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger" style="border-radius: 6px; font-family: 'Cambria', Georgia, serif; font-size: 0.85rem;">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
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
                        
                        <div class="row mb-3">
                            <div class="col-md-6 mb-2 mb-md-0">
                                <div class="label" style="color: #6c757d; font-size: 0.75rem; font-family: 'Cambria', Georgia, serif;">Doctor</div>
                                <div class="value" style="font-weight: 600; font-size: 0.9rem; font-family: 'Cambria', Georgia, serif; color: #000000;"><?php echo htmlspecialchars($user['name']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="label" style="color: #6c757d; font-size: 0.75rem; font-family: 'Cambria', Georgia, serif;">Email</div>
                                <div class="value" style="font-weight: 600; font-size: 0.9rem; font-family: 'Cambria', Georgia, serif; color: #000000;"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                        </div>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label" style="font-family: 'Cambria', Georgia, serif; font-weight: 600; color: #000000; font-size: 0.82rem;">Delivery Address <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="delivery_address" rows="3" placeholder="Enter your complete delivery address" required style="border-radius: 6px; border: 1.5px solid #eef1f5; font-family: 'Cambria', Georgia, serif; font-size: 0.9rem; color: #000000; background: #ffffff; padding: 7px 12px;"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" style="font-family: 'Cambria', Georgia, serif; font-weight: 600; color: #000000; font-size: 0.82rem;">Contact Phone</label>
                                <input type="text" class="form-control" name="delivery_phone" placeholder="Enter phone number for delivery coordination" value="<?php echo htmlspecialchars($user['mobile'] ?? ''); ?>" style="border-radius: 6px; border: 1.5px solid #eef1f5; font-family: 'Cambria', Georgia, serif; font-size: 0.9rem; color: #000000; background: #ffffff; padding: 7px 12px;">
                            </div>
                            
                            <div class="alert alert-info" style="border-radius: 6px; font-family: 'Cambria', Georgia, serif; font-size: 0.8rem;">
                                <i class="fas fa-info-circle"></i> Your request will be reviewed by the admin team. You will receive a notification once it's processed.
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Submit Request</button>
                                <a href="/doctor/<?php echo $type; ?>s" class="btn btn-secondary" style="font-family: 'Cambria', Georgia, serif; font-weight: 600; font-size: 0.85rem; padding: 6px 18px; border-radius: 6px; background: #6c757d; border: none; color: #fff; text-decoration: none;"><i class="fas fa-times"></i> Cancel</a>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>