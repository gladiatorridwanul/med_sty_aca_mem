<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireEditor();

$db = Database::getInstance()->getConnection();

$stats = [
    'total_books' => $db->query("SELECT COUNT(*) as count FROM books WHERE status != 'rejected'")->fetch_assoc()['count'],
    'total_journals' => $db->query("SELECT COUNT(*) as count FROM journals WHERE status != 'rejected'")->fetch_assoc()['count'],
    'pending_books' => $db->query("SELECT COUNT(*) as count FROM books WHERE status = 'pending'")->fetch_assoc()['count'],
    'pending_journals' => $db->query("SELECT COUNT(*) as count FROM journals WHERE status = 'pending'")->fetch_assoc()['count'],
    'pending_requests' => $db->query("SELECT COUNT(*) as count FROM supply_requests WHERE status = 'pending'")->fetch_assoc()['count'],
    'total_requests' => $db->query("SELECT COUNT(*) as count FROM supply_requests")->fetch_assoc()['count'],
];

// Get recent activities
$recentActivities = $db->query("SELECT at.*, u.name as user_name 
                                FROM audit_trail at 
                                LEFT JOIN users u ON at.user_id = u.id 
                                WHERE at.user_id = " . $_SESSION['user_id'] . " 
                                ORDER BY at.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor Dashboard - UCLP Academy</title>
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
        .page-header .welcome-text {
            color: #6c757d;
            font-size: 0.85rem;
            font-family: 'Cambria', Georgia, serif;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
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
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.04); }
        .stat-card .stat-number { font-size: 1.4rem; font-weight: 700; color: #000000; line-height: 1.2; font-family: 'Cambria', Georgia, serif; }
        .stat-card .stat-label { font-size: 0.65rem; color: #6c757d; font-weight: 500; font-family: 'Cambria', Georgia, serif; margin-top: 1px; }
        .stat-card .stat-icon { float: right; font-size: 1.5rem; opacity: 0.15; color: #000000; }
        .stat-card.border-blue { border-left: 3px solid #0d6efd; }
        .stat-card.border-green { border-left: 3px solid #198754; }
        .stat-card.border-orange { border-left: 3px solid #f39c12; }
        .stat-card.border-cyan { border-left: 3px solid #0dcaf0; }
        
        .section-header {
            display: flex; justify-content: space-between; align-items: center; margin: 16px 0 12px;
        }
        .section-header h5 { font-weight: 700; font-size: 0.9rem; color: #000000; margin: 0; font-family: 'Cambria', Georgia, serif; }
        .section-header .btn { font-family: 'Cambria', Georgia, serif; font-weight: 600; font-size: 0.7rem; padding: 3px 12px; border-radius: 6px; }
        
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
            min-width: 400px;
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
        .table-card .table-footer { padding: 8px 14px; border-top: 1px solid #eef1f5; background: #f8f9fa; }
        
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
        
        .activity-item {
            padding: 6px 10px;
            border-bottom: 1px solid #f0f4f8;
        }
        .activity-item:last-child { border-bottom: none; }
        .activity-item .action { font-weight: 600; color: #000000; font-family: 'Cambria', Georgia, serif; font-size: 0.78rem; }
        .activity-item .time { font-size: 0.65rem; color: #6c757d; font-family: 'Cambria', Georgia, serif; }
        
        .empty-state { text-align: center; padding: 20px 16px; }
        .empty-state .icon { font-size: 2rem; color: #dee2e6; margin-bottom: 8px; }
        .empty-state p { color: #6c757d; font-family: 'Cambria', Georgia, serif; font-size: 0.8rem; }
        
        @media (max-width: 991.98px) {
            .main-content { margin-left: 0 !important; max-width: 100% !important; padding: 12px 14px 16px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .page-header h1 { font-size: 1.1rem; }
            .table-card .table-body table { min-width: 350px; }
        }
        
        @media (max-width: 575.98px) {
            .main-content { padding: 10px 8px 14px; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 4px; padding-bottom: 10px; margin-bottom: 12px; }
            .page-header h1 { font-size: 1rem; }
            .page-header .welcome-text { font-size: 0.75rem; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 6px; }
            .stat-card { padding: 8px 10px; }
            .stat-card .stat-number { font-size: 1.1rem; }
            .stat-card .stat-label { font-size: 0.55rem; }
            .table-card .table-body table { min-width: 300px; font-size: 0.72rem; }
            .table-card .table-body table thead th { padding: 4px 6px; font-size: 0.55rem; }
            .table-card .table-body table tbody td { padding: 4px 6px; font-size: 0.72rem; }
            .section-header h5 { font-size: 0.8rem; }
            .section-header .btn { font-size: 0.6rem; padding: 2px 8px; }
            .status-badge { font-size: 0.5rem; padding: 1px 6px; }
            .activity-item .action { font-size: 0.72rem; }
            .activity-item .time { font-size: 0.6rem; }
        }
        
        @media (max-width: 400px) {
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 4px; }
            .stat-card { padding: 6px 8px; }
            .stat-card .stat-number { font-size: 0.9rem; }
            .page-header h1 { font-size: 0.9rem; }
            .table-card .table-body table { min-width: 280px; font-size: 0.68rem; }
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
            .table-card .table-footer { background: #f8f9fa !important; border-top-color: #eef1f5 !important; }
            .page-header { border-bottom-color: #eef1f5 !important; }
            .page-header h1 { color: #000000 !important; }
            .page-header .welcome-text { color: #6c757d !important; }
            .activity-item { border-bottom-color: #f0f4f8 !important; }
            .activity-item .action { color: #000000 !important; }
            .empty-state p { color: #6c757d !important; }
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
                    <h1><i class="fas fa-chart-line" style="color: #198754; margin-right: 8px;"></i> Dashboard</h1>
                    <span class="welcome-text"><i class="fas fa-user-edit"></i> Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card border-blue">
                        <div class="stat-icon"><i class="fas fa-book"></i></div>
                        <div class="stat-number"><?php echo $stats['total_books']; ?></div>
                        <div class="stat-label">Total Books</div>
                    </div>
                    <div class="stat-card border-green">
                        <div class="stat-icon"><i class="fas fa-newspaper"></i></div>
                        <div class="stat-number"><?php echo $stats['total_journals']; ?></div>
                        <div class="stat-label">Total Journals</div>
                    </div>
                    <div class="stat-card border-orange">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-number"><?php echo $stats['pending_books'] + $stats['pending_journals']; ?></div>
                        <div class="stat-label">Pending Items</div>
                    </div>
                    <div class="stat-card border-cyan">
                        <div class="stat-icon"><i class="fas fa-truck"></i></div>
                        <div class="stat-number"><?php echo $stats['pending_requests']; ?></div>
                        <div class="stat-label">Pending Requests</div>
                    </div>
                </div>
                
                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="section-header">
                            <h5><i class="fas fa-truck" style="color: #f39c12;"></i> Recent Supply Requests</h5>
                            <a href="/editor/requests" class="btn btn-success">View All</a>
                        </div>
                        <?php
                        $requests = $db->query("SELECT sr.*, u.name as doctor_name 
                                                FROM supply_requests sr 
                                                LEFT JOIN users u ON sr.user_id = u.id 
                                                ORDER BY sr.request_date DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
                        ?>
                        <div class="table-card">
                            <div class="table-body">
                                <?php if (empty($requests)): ?>
                                <div class="empty-state"><div class="icon"><i class="fas fa-inbox"></i></div><p>No requests yet.</p></div>
                                <?php else: ?>
                                <table>
                                    <thead><tr><th>Doctor</th><th>Status</th><th>Date</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($requests as $req): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($req['doctor_name']); ?></strong></td>
                                            <td><span class="status-badge <?php echo $req['status']; ?>"><?php echo ucfirst($req['status']); ?></span></td>
                                            <td><small><?php echo date('M d, Y', strtotime($req['request_date'])); ?></small></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="section-header">
                            <h5><i class="fas fa-history" style="color: #0d6efd;"></i> Recent Activities</h5>
                            <a href="/editor/audit" class="btn btn-success">View All</a>
                        </div>
                        <div class="table-card">
                            <div class="table-body">
                                <?php if (empty($recentActivities)): ?>
                                <div class="empty-state"><div class="icon"><i class="fas fa-inbox"></i></div><p>No recent activities</p></div>
                                <?php else: ?>
                                <div>
                                    <?php foreach ($recentActivities as $activity): ?>
                                    <div class="activity-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="action"><?php echo htmlspecialchars($activity['action']); ?></span>
                                            <span class="time"><?php echo date('M d, H:i', strtotime($activity['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
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