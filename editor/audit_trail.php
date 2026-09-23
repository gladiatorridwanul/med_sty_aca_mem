<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireEditor();

$db = Database::getInstance()->getConnection();

// Get filter parameters
$userType = isset($_GET['user_type']) ? sanitize($_GET['user_type']) : '';
$actionFilter = isset($_GET['action']) ? sanitize($_GET['action']) : '';
$dateFrom = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

// Build query with filters
$whereConditions = [];
$params = [];
$types = "";

if ($userType) {
    $whereConditions[] = "at.user_type = ?";
    $params[] = $userType;
    $types .= "s";
}

if ($actionFilter) {
    $whereConditions[] = "at.action LIKE ?";
    $params[] = "%$actionFilter%";
    $types .= "s";
}

if ($dateFrom) {
    $whereConditions[] = "DATE(at.created_at) >= ?";
    $params[] = $dateFrom;
    $types .= "s";
}

if ($dateTo) {
    $whereConditions[] = "DATE(at.created_at) <= ?";
    $params[] = $dateTo;
    $types .= "s";
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

$query = "SELECT at.*, u.name as user_name, u.email as user_email 
          FROM audit_trail at 
          LEFT JOIN users u ON at.user_id = u.id 
          $whereClause
          ORDER BY at.created_at DESC";

$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get statistics
$stats = [
    'total' => $db->query("SELECT COUNT(*) as count FROM audit_trail")->fetch_assoc()['count'],
    'today' => $db->query("SELECT COUNT(*) as count FROM audit_trail WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'],
    'this_week' => $db->query("SELECT COUNT(*) as count FROM audit_trail WHERE WEEK(created_at) = WEEK(CURDATE())")->fetch_assoc()['count'],
    'this_month' => $db->query("SELECT COUNT(*) as count FROM audit_trail WHERE MONTH(created_at) = MONTH(CURDATE())")->fetch_assoc()['count'],
];

// Get unique action types for filter
$actionTypes = $db->query("SELECT DISTINCT action FROM audit_trail ORDER BY action")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail - Editor - UCLP Academy</title>
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
        .page-header .total-count {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.8rem;
            color: #6c757d;
        }
        .page-header .total-count strong { color: #000000; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
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
        .stat-card.border-today { border-left: 3px solid #198754; }
        .stat-card.border-week { border-left: 3px solid #f39c12; }
        .stat-card.border-month { border-left: 3px solid #6f42c1; }
        
        .filter-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px 14px;
            border: 1px solid #eef1f5;
            margin-bottom: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .filter-section .filter-label {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.78rem;
            color: #000000;
        }
        .filter-section .form-select, .filter-section .form-control {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.75rem;
            padding: 3px 8px;
            border-radius: 4px;
            border: 1.5px solid #eef1f5;
            background: #ffffff;
            color: #000000;
            min-width: 120px;
        }
        .filter-section .btn {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 3px 14px;
            border-radius: 4px;
        }
        .filter-section .btn-clear { background: transparent; border: 1px solid #eef1f5; color: #6c757d; }
        .filter-section .btn-clear:hover { background: #eef1f5; }
        
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
            font-size: 0.75rem;
            width: 100%;
            min-width: 900px;
            background: #ffffff;
        }
        .table-card .table-body table thead th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            border-bottom: 2px solid #eef1f5;
            padding: 5px 8px;
            white-space: nowrap;
            font-size: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .table-card .table-body table tbody td {
            padding: 5px 8px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f4f8;
            color: #000000;
            font-size: 0.75rem;
        }
        .table-card .table-body table tbody tr:hover { background: #f8f9fa; }
        .table-card .table-body table tbody tr:last-child td { border-bottom: none; }
        
        .user-type-badge {
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 0.55rem;
            font-weight: 600;
            display: inline-block;
            font-family: 'Cambria', Georgia, serif;
        }
        .user-type-badge.admin { background: #fce4ec; color: #dc3545; }
        .user-type-badge.editor { background: #fff3e0; color: #f39c12; }
        .user-type-badge.doctor { background: #e8f0fe; color: #0d6efd; }
        .user-type-badge.system { background: #e9ecef; color: #6c757d; }
        
        .action-badge {
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 0.55rem;
            font-weight: 600;
            display: inline-block;
            font-family: 'Cambria', Georgia, serif;
        }
        .action-badge.login { background: #e8f0fe; color: #0d6efd; }
        .action-badge.logout { background: #e9ecef; color: #6c757d; }
        .action-badge.add { background: #e8f5e9; color: #198754; }
        .action-badge.update { background: #fff3e0; color: #f39c12; }
        .action-badge.delete { background: #fce4ec; color: #dc3545; }
        .action-badge.default { background: #e9ecef; color: #6c757d; }
        
        @media (max-width: 991.98px) {
            .main-content { margin-left: 0 !important; max-width: 100% !important; padding: 12px 14px 16px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .page-header h1 { font-size: 1.1rem; }
            .table-card .table-body table { min-width: 750px; }
            .filter-section { flex-direction: column; align-items: stretch; }
            .filter-section .filter-label { display: none; }
            .filter-section .form-select, .filter-section .form-control { min-width: 100%; }
        }
        
        @media (max-width: 575.98px) {
            .main-content { padding: 10px 8px 14px; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 4px; padding-bottom: 10px; margin-bottom: 12px; }
            .page-header h1 { font-size: 1rem; }
            .page-header .total-count { font-size: 0.7rem; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 6px; }
            .stat-card { padding: 6px 8px; }
            .stat-card .stat-number { font-size: 1rem; }
            .stat-card .stat-label { font-size: 0.5rem; }
            .filter-section { padding: 8px 10px; gap: 4px; }
            .filter-section .form-select, .filter-section .form-control { font-size: 0.7rem; padding: 2px 6px; min-width: 100%; }
            .filter-section .btn { font-size: 0.7rem; padding: 2px 10px; }
            .table-card .table-body table { min-width: 550px; font-size: 0.7rem; }
            .table-card .table-body table thead th { padding: 3px 5px; font-size: 0.5rem; }
            .table-card .table-body table tbody td { padding: 3px 5px; font-size: 0.7rem; }
            .user-type-badge, .action-badge { font-size: 0.45rem; padding: 1px 5px; }
        }
        
        @media (max-width: 400px) {
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 4px; }
            .stat-card { padding: 4px 6px; }
            .stat-card .stat-number { font-size: 0.85rem; }
            .table-card .table-body table { min-width: 450px; font-size: 0.65rem; }
            .page-header h1 { font-size: 0.9rem; }
        }
        
        @media (prefers-color-scheme: dark) {
            body { background: #ffffff !important; }
            .main-content { background: #ffffff !important; }
            .stat-card { background: #f8f9fa !important; border-color: #eef1f5 !important; }
            .stat-card .stat-number { color: #000000 !important; }
            .stat-card .stat-label { color: #6c757d !important; }
            .stat-card .stat-icon { color: #000000 !important; }
            .filter-section { background: #f8f9fa !important; border-color: #eef1f5 !important; }
            .filter-section .filter-label { color: #000000 !important; }
            .filter-section .form-select, .filter-section .form-control { background: #ffffff !important; border-color: #eef1f5 !important; color: #000000 !important; }
            .filter-section .btn-clear { color: #6c757d !important; border-color: #eef1f5 !important; }
            .table-card { background: #f8f9fa !important; border-color: #eef1f5 !important; }
            .table-card .table-body table { background: #ffffff !important; }
            .table-card .table-body table thead th { background: #f8f9fa !important; color: #495057 !important; border-bottom-color: #eef1f5 !important; }
            .table-card .table-body table tbody td { color: #000000 !important; border-bottom-color: #f0f4f8 !important; }
            .page-header { border-bottom-color: #eef1f5 !important; }
            .page-header h1 { color: #000000 !important; }
            .page-header .total-count { color: #6c757d !important; }
            .page-header .total-count strong { color: #000000 !important; }
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
                    <h1><i class="fas fa-history"></i> Audit Trail</h1>
                    <span class="total-count"><strong><?php echo $stats['total']; ?></strong> total records</span>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card border-total"><div class="stat-icon"><i class="fas fa-list"></i></div><div class="stat-number"><?php echo $stats['total']; ?></div><div class="stat-label">Total Records</div></div>
                    <div class="stat-card border-today"><div class="stat-icon"><i class="fas fa-calendar-day"></i></div><div class="stat-number"><?php echo $stats['today']; ?></div><div class="stat-label">Today</div></div>
                    <div class="stat-card border-week"><div class="stat-icon"><i class="fas fa-calendar-week"></i></div><div class="stat-number"><?php echo $stats['this_week']; ?></div><div class="stat-label">This Week</div></div>
                    <div class="stat-card border-month"><div class="stat-icon"><i class="fas fa-calendar-alt"></i></div><div class="stat-number"><?php echo $stats['this_month']; ?></div><div class="stat-label">This Month</div></div>
                </div>
                
                <div class="filter-section">
                    <span class="filter-label"><i class="fas fa-filter"></i> Filter:</span>
                    <form method="GET" action="" class="d-flex flex-wrap gap-2 align-items-center" style="flex: 1;">
                        <select name="user_type" class="form-select">
                            <option value="">All User Types</option>
                            <option value="admin" <?php echo $userType == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="editor" <?php echo $userType == 'editor' ? 'selected' : ''; ?>>Editor</option>
                            <option value="doctor" <?php echo $userType == 'doctor' ? 'selected' : ''; ?>>Doctor</option>
                        </select>
                        <select name="action" class="form-select">
                            <option value="">All Actions</option>
                            <?php foreach ($actionTypes as $action): ?>
                            <option value="<?php echo htmlspecialchars($action['action']); ?>" <?php echo $actionFilter == $action['action'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($action['action']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="date" name="date_from" class="form-control" value="<?php echo $dateFrom; ?>" placeholder="Date From">
                        <input type="date" name="date_to" class="form-control" value="<?php echo $dateTo; ?>" placeholder="Date To">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply</button>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-clear"><i class="fas fa-times"></i> Clear</a>
                    </form>
                </div>
                
                <div class="table-card">
                    <div class="table-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>IP</th>
                                    <th>Date/Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                <tr><td colspan="7" class="text-center py-4" style="color: #6c757d; font-family: 'Cambria', Georgia, serif;"><i class="fas fa-inbox fa-2x d-block mb-2" style="color: #dee2e6;"></i>No audit records found.</td></tr>
                                <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><strong><?php echo $log['id']; ?></strong></td>
                                    <td>
                                        <?php if ($log['user_id']): ?>
                                        <strong><?php echo htmlspecialchars($log['user_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($log['user_email']); ?></small>
                                        <?php else: ?>
                                        <span class="text-muted">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="user-type-badge <?php echo $log['user_type'] ?? 'system'; ?>"><?php echo ucfirst($log['user_type'] ?? 'System'); ?></span></td>
                                    <td>
                                        <?php 
                                            $actionClass = 'default';
                                            $actionLower = strtolower($log['action']);
                                            if (strpos($actionLower, 'login') !== false) $actionClass = 'login';
                                            elseif (strpos($actionLower, 'logout') !== false) $actionClass = 'logout';
                                            elseif (strpos($actionLower, 'add') !== false || strpos($actionLower, 'added') !== false) $actionClass = 'add';
                                            elseif (strpos($actionLower, 'update') !== false || strpos($actionLower, 'updated') !== false) $actionClass = 'update';
                                            elseif (strpos($actionLower, 'delete') !== false || strpos($actionLower, 'deleted') !== false) $actionClass = 'delete';
                                        ?>
                                        <span class="action-badge <?php echo $actionClass; ?>"><?php echo htmlspecialchars($log['action']); ?></span>
                                    </td>
                                    <td><small><?php echo htmlspecialchars($log['details'] ?? ''); ?></small></td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($log['ip_address']); ?></small></td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($log['created_at'])); ?>
                                        <br><small class="text-muted"><?php echo date('h:i A', strtotime($log['created_at'])); ?></small>
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