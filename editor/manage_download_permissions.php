<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireEditor();

$db = Database::getInstance()->getConnection();
$message = '';
$messageType = '';

// Handle approval/rejection
if (isset($_POST['process_permission'])) {
    $permissionId = intval($_POST['permission_id']);
    $status = sanitize($_POST['status'] ?? 'rejected');
    $maxDownloads = intval($_POST['max_downloads'] ?? 2);
    $expiryDays = intval($_POST['expiry_days'] ?? 30);
    $adminNotes = sanitize($_POST['admin_notes'] ?? '');
    
    if (processDownloadPermission($permissionId, $status, $maxDownloads, $expiryDays, $adminNotes)) {
        $message = "Permission request processed successfully.";
        $messageType = 'success';
    } else {
        $message = "Failed to process permission request.";
        $messageType = 'danger';
    }
}

// Get statistics
$stats = [
    'total' => $db->query("SELECT COUNT(*) as count FROM download_permissions")->fetch_assoc()['count'],
    'pending' => $db->query("SELECT COUNT(*) as count FROM download_permissions WHERE status = 'pending'")->fetch_assoc()['count'],
    'approved' => $db->query("SELECT COUNT(*) as count FROM download_permissions WHERE status = 'approved'")->fetch_assoc()['count'],
    'rejected' => $db->query("SELECT COUNT(*) as count FROM download_permissions WHERE status = 'rejected'")->fetch_assoc()['count'],
    'expired' => $db->query("SELECT COUNT(*) as count FROM download_permissions WHERE status = 'expired'")->fetch_assoc()['count'],
];

// Get all download permission requests
$permissions = $db->query("SELECT dp.*, u.name as user_name, u.email as user_email,
                          CASE WHEN dp.item_type = 'book' THEN b.title ELSE j.title END as item_title
                          FROM download_permissions dp
                          LEFT JOIN users u ON dp.user_id = u.id
                          LEFT JOIN books b ON dp.item_type = 'book' AND dp.item_id = b.id
                          LEFT JOIN journals j ON dp.item_type = 'journal' AND dp.item_id = j.id
                          ORDER BY dp.requested_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Permissions - Editor - UCLP Academy</title>
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
        .stat-card.border-rejected { border-left: 3px solid #dc3545; }
        .stat-card.border-expired { border-left: 3px solid #6c757d; }
        
        .table-card {
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #eef1f5;
            overflow: hidden;
        }
        .table-card .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px;
            border-bottom: 1px solid #eef1f5;
            background: #f8f9fa;
            flex-wrap: wrap;
            gap: 8px;
        }
        .table-card .table-header h5 {
            font-weight: 700;
            font-size: 0.85rem;
            color: #000000;
            margin: 0;
            font-family: 'Cambria', Georgia, serif;
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
        .status-badge.rejected { background: #fce4ec; color: #dc3545; }
        .status-badge.expired { background: #e9ecef; color: #6c757d; }
        
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
        
        .modal-content {
            border-radius: 10px;
            border: 1px solid #eef1f5;
            background: #ffffff;
        }
        .modal-header { border-bottom: 1px solid #eef1f5; padding: 14px 20px; }
        .modal-header .modal-title { font-family: 'Cambria', Georgia, serif; font-weight: 700; font-size: 1.1rem; color: #000000; }
        .modal-body { padding: 20px; }
        .modal-footer { border-top: 1px solid #eef1f5; padding: 12px 20px; }
        .form-label { font-family: 'Cambria', Georgia, serif; font-weight: 600; color: #000000; font-size: 0.82rem; }
        .form-control { border-radius: 6px; border: 1.5px solid #eef1f5; font-family: 'Cambria', Georgia, serif; font-size: 0.9rem; color: #000000; background: #ffffff; padding: 7px 12px; }
        .form-control:focus { border-color: #198754; box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.08); }
        
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
            .page-header .badge-role { font-size: 0.6rem; padding: 3px 10px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 6px; }
            .stat-card { padding: 8px 10px; }
            .stat-card .stat-number { font-size: 1rem; }
            .stat-card .stat-label { font-size: 0.55rem; }
            .table-card .table-body table { min-width: 550px; font-size: 0.7rem; }
            .table-card .table-body table thead th { padding: 4px 6px; font-size: 0.55rem; }
            .table-card .table-body table tbody td { padding: 4px 6px; font-size: 0.7rem; }
            .status-badge, .type-badge { font-size: 0.5rem; padding: 1px 6px; }
            .action-btn { font-size: 0.55rem; padding: 2px 6px; }
            .table-card .table-header { padding: 8px 10px; }
            .table-card .table-header h5 { font-size: 0.78rem; }
            .modal-body { padding: 14px; }
        }
        
        @media (max-width: 400px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 4px; }
            .stat-card { padding: 6px 8px; }
            .stat-card .stat-number { font-size: 0.85rem; }
            .stat-card .stat-label { font-size: 0.5rem; }
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
            .table-card .table-header { background: #f8f9fa !important; border-bottom-color: #eef1f5 !important; }
            .table-card .table-header h5 { color: #000000 !important; }
            .table-card .table-body table { background: #ffffff !important; }
            .table-card .table-body table thead th { background: #f8f9fa !important; color: #495057 !important; border-bottom-color: #eef1f5 !important; }
            .table-card .table-body table tbody td { color: #000000 !important; border-bottom-color: #f0f4f8 !important; }
            .page-header { border-bottom-color: #eef1f5 !important; }
            .page-header h1 { color: #000000 !important; }
            .modal-content { background: #ffffff !important; border-color: #eef1f5 !important; }
            .modal-header { border-bottom-color: #eef1f5 !important; }
            .modal-header .modal-title { color: #000000 !important; }
            .form-control { background: #ffffff !important; border-color: #eef1f5 !important; color: #000000 !important; }
            .form-label { color: #000000 !important; }
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
                    <h1><i class="fas fa-download"></i> Download Permissions</h1>
                    <span class="badge bg-success badge-role">Editor Access</span>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" style="border-radius: 6px; font-family: 'Cambria', Georgia, serif; font-size: 0.85rem;">
                        <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="stats-grid">
                    <div class="stat-card border-total"><div class="stat-icon"><i class="fas fa-list"></i></div><div class="stat-number"><?php echo $stats['total']; ?></div><div class="stat-label">Total Requests</div></div>
                    <div class="stat-card border-pending"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-number"><?php echo $stats['pending']; ?></div><div class="stat-label">Pending</div></div>
                    <div class="stat-card border-approved"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-number"><?php echo $stats['approved']; ?></div><div class="stat-label">Approved</div></div>
                    <div class="stat-card border-rejected"><div class="stat-icon"><i class="fas fa-times-circle"></i></div><div class="stat-number"><?php echo $stats['rejected']; ?></div><div class="stat-label">Rejected</div></div>
                    <div class="stat-card border-expired"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-number"><?php echo $stats['expired']; ?></div><div class="stat-label">Expired</div></div>
                </div>
                
                <div class="table-card">
                    <div class="table-header">
                        <h5><i class="fas fa-list"></i> All Download Permission Requests</h5>
                        <?php if ($stats['pending'] > 0): ?>
                        <span class="badge bg-warning" style="font-size: 0.65rem; padding: 2px 10px;"><?php echo $stats['pending']; ?> Pending</span>
                        <?php endif; ?>
                    </div>
                    <div class="table-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Item</th>
                                    <th>Type</th>
                                    <th>Downloads</th>
                                    <th>Status</th>
                                    <th>Requested</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($permissions)): ?>
                                <tr><td colspan="8" class="text-center py-4" style="color: #6c757d; font-family: 'Cambria', Georgia, serif;"><i class="fas fa-inbox fa-2x d-block mb-2" style="color: #dee2e6;"></i>No download permission requests found.</td></tr>
                                <?php else: ?>
                                <?php foreach ($permissions as $perm): ?>
                                <tr>
                                    <td><strong><?php echo $perm['id']; ?></strong></td>
                                    <td><strong><?php echo htmlspecialchars($perm['user_name']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($perm['user_email']); ?></small></td>
                                    <td><?php echo htmlspecialchars($perm['item_title'] ?? 'N/A'); ?></td>
                                    <td><span class="type-badge <?php echo $perm['item_type']; ?>"><?php echo ucfirst($perm['item_type']); ?></span></td>
                                    <td><span style="font-weight: 600;"><?php echo $perm['used_downloads'] ?? '0'; ?></span> <span class="text-muted">/ <?php echo $perm['max_downloads'] ?? 'N/A'; ?></span></td>
                                    <td>
                                        <span class="status-badge <?php echo $perm['status']; ?>"><?php echo ucfirst($perm['status']); ?></span>
                                        <?php if ($perm['status'] == 'approved' && $perm['expires_at']): ?>
                                        <br><small class="text-muted" style="font-size: 0.55rem;">Expires: <?php echo date('M d, Y', strtotime($perm['expires_at'])); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($perm['requested_at'])); ?></td>
                                    <td>
                                        <?php if ($perm['status'] === 'pending'): ?>
                                        <div class="action-group">
                                            <button class="action-btn approve" onclick="processPermission(<?php echo $perm['id']; ?>, 'approved')"><i class="fas fa-check"></i> Approve</button>
                                            <button class="action-btn reject" onclick="processPermission(<?php echo $perm['id']; ?>, 'rejected')"><i class="fas fa-times"></i> Reject</button>
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
    
    <div class="modal fade" id="processModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-cog" style="color: #198754;"></i> Process Download Permission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="permission_id" id="permission_id">
                    <input type="hidden" name="status" id="permission_status">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Max Downloads</label>
                            <input type="number" class="form-control" id="max_downloads" name="max_downloads" value="2" min="1" max="10">
                            <small class="text-muted" style="font-family: 'Cambria', Georgia, serif; font-size: 0.7rem;">Number of times the user can download</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expiry Days</label>
                            <input type="number" class="form-control" id="expiry_days" name="expiry_days" value="30" min="1" max="365">
                            <small class="text-muted" style="font-family: 'Cambria', Georgia, serif; font-size: 0.7rem;">Days until permission expires</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Admin Notes</label>
                            <textarea class="form-control" id="admin_notes" name="admin_notes" rows="2" placeholder="Add notes about this decision..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="font-family: 'Cambria', Georgia, serif; font-weight: 600; font-size: 0.85rem; padding: 6px 18px; border-radius: 6px;">Cancel</button>
                        <button type="submit" name="process_permission" class="btn btn-success" style="font-family: 'Cambria', Georgia, serif; font-weight: 600; font-size: 0.85rem; padding: 6px 18px; border-radius: 6px;"><i class="fas fa-save"></i> Process</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function processPermission(id, status) {
            $('#permission_id').val(id);
            $('#permission_status').val(status);
            $('#processModal').modal('show');
        }
    </script>
</body>
</html>