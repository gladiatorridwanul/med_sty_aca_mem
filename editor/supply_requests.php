<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireEditor();

$db = Database::getInstance()->getConnection();

$requests = $db->query("SELECT sr.*, u.name as doctor_name, u.email as doctor_email,
                        b.title as book_title, j.title as journal_title,
                        a.name as processed_by_name
                        FROM supply_requests sr 
                        LEFT JOIN users u ON sr.user_id = u.id 
                        LEFT JOIN books b ON sr.book_id = b.id 
                        LEFT JOIN journals j ON sr.journal_id = j.id 
                        LEFT JOIN users a ON sr.processed_by = a.id
                        ORDER BY sr.request_date DESC")->fetch_all(MYSQLI_ASSOC);

if (isset($_POST['process']) && isset($_POST['request_id'])) {
    $requestId = intval($_POST['request_id']);
    $status = $_POST['status'] ?? 'approved';
    $notes = sanitize($_POST['admin_notes'] ?? '');
    $userId = $_SESSION['user_id'];
    
    $stmt = $db->prepare("UPDATE supply_requests SET status = ?, admin_notes = ?, processed_by = ?, processed_date = NOW() WHERE id = ?");
    $stmt->bind_param("ssii", $status, $notes, $userId, $requestId);
    if ($stmt->execute()) {
        logActivity($userId, 'Processed Supply Request', "Request ID: $requestId, Status: $status");
        $success = "Request processed successfully.";
    } else {
        $error = "Failed to process request.";
    }
    $stmt->close();
}

// Get statistics
$stats = [
    'total' => $db->query("SELECT COUNT(*) as count FROM supply_requests")->fetch_assoc()['count'],
    'pending' => $db->query("SELECT COUNT(*) as count FROM supply_requests WHERE status = 'pending'")->fetch_assoc()['count'],
    'approved' => $db->query("SELECT COUNT(*) as count FROM supply_requests WHERE status = 'approved'")->fetch_assoc()['count'],
    'completed' => $db->query("SELECT COUNT(*) as count FROM supply_requests WHERE status = 'completed'")->fetch_assoc()['count'],
    'rejected' => $db->query("SELECT COUNT(*) as count FROM supply_requests WHERE status = 'rejected'")->fetch_assoc()['count'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supply Requests - Editor - UCLP Academy</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        .stat-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px 12px;
            border: 1px solid #eef1f5;
            transition: all 0.2s ease;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.04); }
        .stat-card .stat-number { font-size: 1.2rem; font-weight: 700; color: #000000; line-height: 1.2; font-family: 'Cambria', Georgia, serif; }
        .stat-card .stat-label { font-size: 0.6rem; color: #6c757d; font-weight: 500; font-family: 'Cambria', Georgia, serif; margin-top: 1px; }
        .stat-card .stat-icon { float: right; font-size: 1.2rem; opacity: 0.15; color: #000000; }
        .stat-card.border-total { border-left: 3px solid #0d6efd; }
        .stat-card.border-pending { border-left: 3px solid #f39c12; }
        .stat-card.border-approved { border-left: 3px solid #198754; }
        .stat-card.border-completed { border-left: 3px solid #0dcaf0; }
        .stat-card.border-rejected { border-left: 3px solid #dc3545; }
        
        .table-card {
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #eef1f5;
            overflow: hidden;
        }
        .table-card .table-body { padding: 0; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .table-card .table-body table {
            margin: 0;
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.78rem;
            width: 100%;
            min-width: 900px;
            background: #ffffff;
        }
        .table-card .table-body table thead th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            border-bottom: 2px solid #eef1f5;
            padding: 6px 10px;
            white-space: nowrap;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .table-card .table-body table tbody td {
            padding: 6px 10px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f4f8;
            color: #000000;
            font-size: 0.78rem;
        }
        .table-card .table-body table tbody tr:hover { background: #f8f9fa; }
        .table-card .table-body table tbody tr:last-child td { border-bottom: none; }
        
        .status-badge {
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 0.6rem;
            font-weight: 600;
            display: inline-block;
            font-family: 'Cambria', Georgia, serif;
        }
        .status-badge.pending { background: #fff3e0; color: #f39c12; }
        .status-badge.approved { background: #e8f5e9; color: #198754; }
        .status-badge.completed { background: #e0f7fa; color: #0dcaf0; }
        .status-badge.rejected { background: #fce4ec; color: #dc3545; }
        
        .type-badge {
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 0.6rem;
            font-weight: 600;
            display: inline-block;
            font-family: 'Cambria', Georgia, serif;
        }
        .type-badge.book { background: #e8f0fe; color: #0d6efd; }
        .type-badge.journal { background: #e8f5e9; color: #198754; }
        
        .action-btn {
            padding: 2px 10px;
            border-radius: 4px;
            border: none;
            font-size: 0.6rem;
            font-weight: 600;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
            font-family: 'Cambria', Georgia, serif;
            display: inline-block;
        }
        .action-btn:hover { transform: translateY(-1px); }
        .action-btn.approve { background: #e8f5e9; color: #198754; }
        .action-btn.approve:hover { background: #198754; color: #fff; }
        .action-btn.reject { background: #fce4ec; color: #dc3545; }
        .action-btn.reject:hover { background: #dc3545; color: #fff; }
        .action-btn.processed { background: #f8f9fa; color: #6c757d; }
        .action-group { display: flex; gap: 4px; flex-wrap: wrap; }
        
        @media (max-width: 991.98px) {
            .main-content { margin-left: 0 !important; max-width: 100% !important; padding: 12px 14px 16px; }
            .stats-grid { grid-template-columns: repeat(3, 1fr); gap: 10px; }
            .page-header h1 { font-size: 1.1rem; }
            .table-card .table-body table { min-width: 750px; font-size: 0.75rem; }
        }
        
        @media (max-width: 575.98px) {
            .main-content { padding: 10px 8px 14px; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 6px; padding-bottom: 10px; margin-bottom: 12px; }
            .page-header h1 { font-size: 1rem; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 6px; }
            .stat-card { padding: 8px 10px; }
            .stat-card .stat-number { font-size: 1rem; }
            .stat-card .stat-label { font-size: 0.55rem; }
            .table-card .table-body table { min-width: 550px; font-size: 0.7rem; }
            .table-card .table-body table thead th { padding: 4px 6px; font-size: 0.55rem; }
            .table-card .table-body table tbody td { padding: 4px 6px; font-size: 0.7rem; }
            .status-badge, .type-badge { font-size: 0.5rem; padding: 1px 6px; }
            .action-btn { font-size: 0.55rem; padding: 2px 6px; }
        }
        
        @media (max-width: 400px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 4px; }
            .stat-card { padding: 6px 8px; }
            .stat-card .stat-number { font-size: 0.85rem; }
            .table-card .table-body table { min-width: 450px; font-size: 0.65rem; }
        }
        
        @media (prefers-color-scheme: dark) {
            body { background: #ffffff !important; }
            .main-content { background: #ffffff !important; }
            .stat-card { background: #f8f9fa !important; border-color: #eef1f5 !important; }
            .stat-card .stat-number { color: #000000 !important; }
            .stat-card .stat-label { color: #6c757d !important; }
            .stat-card .stat-icon { color: #000000 !important; }
            .table-card { background: #f8f9fa !important; border-color: #eef1f5 !important; }
            .table-card .table-body table { background: #ffffff !important; }
            .table-card .table-body table thead th { background: #f8f9fa !important; color: #495057 !important; border-bottom-color: #eef1f5 !important; }
            .table-card .table-body table tbody td { color: #000000 !important; border-bottom-color: #f0f4f8 !important; }
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
                    <h1><i class="fas fa-truck"></i> Supply Requests</h1>
                    <span class="badge bg-success" style="font-family: 'Cambria', Georgia, serif; font-weight: 600; font-size: 0.7rem; padding: 4px 12px; border-radius: 12px;">Editor Access</span>
                </div>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" style="border-radius: 6px; font-family: 'Cambria', Georgia, serif; font-size: 0.85rem;"><i class="fas fa-check-circle"></i> <?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" style="border-radius: 6px; font-family: 'Cambria', Georgia, serif; font-size: 0.85rem;"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
                
                <div class="stats-grid">
                    <div class="stat-card border-total"><div class="stat-icon"><i class="fas fa-list"></i></div><div class="stat-number"><?php echo $stats['total']; ?></div><div class="stat-label">Total</div></div>
                    <div class="stat-card border-pending"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-number"><?php echo $stats['pending']; ?></div><div class="stat-label">Pending</div></div>
                    <div class="stat-card border-approved"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-number"><?php echo $stats['approved']; ?></div><div class="stat-label">Approved</div></div>
                    <div class="stat-card border-completed"><div class="stat-icon"><i class="fas fa-check-double"></i></div><div class="stat-number"><?php echo $stats['completed']; ?></div><div class="stat-label">Completed</div></div>
                    <div class="stat-card border-rejected"><div class="stat-icon"><i class="fas fa-times-circle"></i></div><div class="stat-number"><?php echo $stats['rejected']; ?></div><div class="stat-label">Rejected</div></div>
                </div>
                
                <div class="table-card">
                    <div class="table-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Doctor</th>
                                    <th>Item</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($requests)): ?>
                                <tr><td colspan="7" class="text-center py-4" style="color: #6c757d; font-family: 'Cambria', Georgia, serif;"><i class="fas fa-inbox fa-2x d-block mb-2" style="color: #dee2e6;"></i>No supply requests found.</td></tr>
                                <?php else: ?>
                                <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><strong><?php echo $request['id']; ?></strong></td>
                                    <td><strong><?php echo htmlspecialchars($request['doctor_name']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($request['doctor_email']); ?></small></td>
                                    <td><?php echo htmlspecialchars($request['book_title'] ?? $request['journal_title'] ?? 'N/A'); ?></td>
                                    <td><span class="type-badge <?php echo $request['request_type']; ?>"><?php echo ucfirst($request['request_type']); ?></span></td>
                                    <td><span class="status-badge <?php echo $request['status']; ?>"><?php echo ucfirst($request['status']); ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                    <td>
                                        <?php if ($request['status'] === 'pending'): ?>
                                        <div class="action-group">
                                            <form method="POST" action="" class="d-inline"><input type="hidden" name="request_id" value="<?php echo $request['id']; ?>"><input type="hidden" name="status" value="approved"><button type="submit" name="process" class="action-btn approve"><i class="fas fa-check"></i> Approve</button></form>
                                            <form method="POST" action="" class="d-inline"><input type="hidden" name="request_id" value="<?php echo $request['id']; ?>"><input type="hidden" name="status" value="rejected"><button type="submit" name="process" class="action-btn reject"><i class="fas fa-times"></i> Reject</button></form>
                                        </div>
                                        <?php else: ?>
                                        <span class="action-btn processed"><i class="fas fa-check"></i> Processed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>