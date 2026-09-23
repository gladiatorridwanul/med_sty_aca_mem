<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireAdmin();

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

// Get search and filter parameters
$searchQuery = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$typeFilter = isset($_GET['type']) ? sanitize($_GET['type']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build the WHERE clause for filtering
$whereConditions = [];
$params = [];
$types = "";

if (!empty($searchQuery)) {
    $searchTerm = '%' . $searchQuery . '%';
    $whereConditions[] = "(u.name LIKE ? OR u.email LIKE ? OR 
                          CASE WHEN dp.item_type = 'book' THEN b.title ELSE j.title END LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

if (!empty($statusFilter)) {
    $whereConditions[] = "dp.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

if (!empty($typeFilter)) {
    $whereConditions[] = "dp.item_type = ?";
    $params[] = $typeFilter;
    $types .= "s";
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM download_permissions dp
             LEFT JOIN users u ON dp.user_id = u.id
             LEFT JOIN books b ON dp.item_type = 'book' AND dp.item_id = b.id
             LEFT JOIN journals j ON dp.item_type = 'journal' AND dp.item_id = j.id
             $whereClause";
$countStmt = $db->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalResult = $countStmt->get_result();
$totalPermissions = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalPermissions / $perPage);
$countStmt->close();

// Get all download permission requests with pagination
$sql = "SELECT dp.*, u.name as user_name, u.email as user_email,
        CASE WHEN dp.item_type = 'book' THEN b.title ELSE j.title END as item_title
        FROM download_permissions dp
        LEFT JOIN users u ON dp.user_id = u.id
        LEFT JOIN books b ON dp.item_type = 'book' AND dp.item_id = b.id
        LEFT JOIN journals j ON dp.item_type = 'journal' AND dp.item_id = j.id
        $whereClause
        ORDER BY dp.requested_at DESC 
        LIMIT ? OFFSET ?";

$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$permissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get statistics
$stats = [
    'total' => $db->query("SELECT COUNT(*) as count FROM download_permissions")->fetch_assoc()['count'],
    'pending' => $db->query("SELECT COUNT(*) as count FROM download_permissions WHERE status = 'pending'")->fetch_assoc()['count'],
    'approved' => $db->query("SELECT COUNT(*) as count FROM download_permissions WHERE status = 'approved'")->fetch_assoc()['count'],
    'rejected' => $db->query("SELECT COUNT(*) as count FROM download_permissions WHERE status = 'rejected'")->fetch_assoc()['count'],
    'expired' => $db->query("SELECT COUNT(*) as count FROM download_permissions WHERE status = 'expired'")->fetch_assoc()['count'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Permissions - BJDVL</title>
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
            font-size: 16px;
        }
        
        /* ============================================
           MAIN CONTENT AREA
           ============================================ */
        .main-content {
            margin-left: 250px;
            padding: 20px 24px 24px;
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
            padding: 0 0 14px 0;
            border-bottom: 1px solid #eef1f5;
            margin-bottom: 18px;
        }
        .page-header h1 {
            font-weight: 700;
            font-size: 1.5rem;
            color: #000000;
            margin: 0;
            font-family: 'Cambria', Georgia, serif;
        }
        .page-header h1 i {
            color: #0d6efd;
            margin-right: 10px;
        }
        .page-header .badge-admin {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.8rem;
            padding: 5px 14px;
            border-radius: 12px;
        }
        
        /* ============================================
           STATISTICS CARDS
           ============================================ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .stat-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 14px 16px;
            border: 1px solid #eef1f5;
            transition: all 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        }
        .stat-card .stat-number {
            font-size: 1.6rem;
            font-weight: 700;
            color: #000000;
            line-height: 1.2;
            font-family: 'Cambria', Georgia, serif;
        }
        .stat-card .stat-label {
            font-size: 0.75rem;
            color: #6c757d;
            font-weight: 500;
            font-family: 'Cambria', Georgia, serif;
            margin-top: 2px;
        }
        .stat-card .stat-icon {
            float: right;
            font-size: 1.8rem;
            opacity: 0.15;
            color: #000000;
        }
        .stat-card.border-total { border-left: 3px solid #0d6efd; }
        .stat-card.border-pending { border-left: 3px solid #f39c12; }
        .stat-card.border-approved { border-left: 3px solid #198754; }
        .stat-card.border-rejected { border-left: 3px solid #dc3545; }
        .stat-card.border-expired { border-left: 3px solid #6c757d; }
        
        /* ============================================
           FILTER SECTION
           ============================================ */
        .filter-section {
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #eef1f5;
            padding: 14px 18px;
            margin-bottom: 16px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
        }
        .filter-section .filter-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #000000;
            font-family: 'Cambria', Georgia, serif;
            margin-right: 4px;
        }
        .filter-section .filter-group {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            flex: 1;
        }
        .filter-section .filter-group .form-control,
        .filter-section .filter-group .form-select {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.9rem;
            padding: 6px 12px;
            border-radius: 6px;
            border: 1.5px solid #eef1f5;
            background: #ffffff;
            color: #000000;
            min-width: 150px;
        }
        .filter-section .filter-group .form-control:focus,
        .filter-section .filter-group .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13,110,253,0.08);
            outline: none;
        }
        .filter-section .filter-group .btn-filter {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 6px 18px;
            border-radius: 6px;
        }
        .filter-section .filter-group .btn-clear {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.85rem;
            padding: 6px 14px;
            border-radius: 6px;
            color: #6c757d;
            text-decoration: none;
        }
        .filter-section .filter-group .btn-clear:hover {
            color: #dc3545;
        }
        
        .filter-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 6px;
        }
        .filter-badges .badge {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.75rem;
            padding: 4px 12px;
            border-radius: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1.5px solid transparent;
        }
        .filter-badges .badge:hover {
            transform: translateY(-1px);
        }
        .filter-badges .badge-all {
            background: #e8f0fe;
            color: #0d6efd;
            border-color: #0d6efd;
        }
        .filter-badges .badge-all.active {
            background: #0d6efd;
            color: #fff;
        }
        .filter-badges .badge-pending {
            background: #fff3e0;
            color: #f39c12;
            border-color: #f39c12;
        }
        .filter-badges .badge-pending.active {
            background: #f39c12;
            color: #fff;
        }
        .filter-badges .badge-approved {
            background: #e8f5e9;
            color: #198754;
            border-color: #198754;
        }
        .filter-badges .badge-approved.active {
            background: #198754;
            color: #fff;
        }
        .filter-badges .badge-rejected {
            background: #fce4ec;
            color: #dc3545;
            border-color: #dc3545;
        }
        .filter-badges .badge-rejected.active {
            background: #dc3545;
            color: #fff;
        }
        .filter-badges .badge-expired {
            background: #e9ecef;
            color: #6c757d;
            border-color: #6c757d;
        }
        .filter-badges .badge-expired.active {
            background: #6c757d;
            color: #fff;
        }
        
        /* ============================================
           TABLE CARD
           ============================================ */
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
            padding: 12px 16px;
            border-bottom: 1px solid #eef1f5;
            flex-wrap: wrap;
            gap: 10px;
            background: #f8f9fa;
        }
        .table-card .table-header h5 {
            font-weight: 700;
            font-size: 1rem;
            color: #000000;
            margin: 0;
            font-family: 'Cambria', Georgia, serif;
        }
        .table-card .table-header h5 i {
            margin-right: 8px;
        }
        .table-card .table-header .badge-count {
            font-size: 0.78rem;
            padding: 3px 14px;
            border-radius: 12px;
            font-weight: 600;
        }
        .table-card .table-header .badge-count.pending {
            background: #fff3e0;
            color: #f39c12;
        }
        
        .table-card .table-body {
            padding: 0;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .table-card .table-body table {
            margin: 0;
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.88rem;
            width: 100%;
            min-width: 900px;
            background: #ffffff;
        }
        .table-card .table-body table thead th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            border-bottom: 2px solid #eef1f5;
            padding: 8px 12px;
            white-space: nowrap;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .table-card .table-body table tbody td {
            padding: 8px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f4f8;
            color: #000000;
            font-size: 0.88rem;
        }
        .table-card .table-body table tbody tr:hover {
            background: #f8f9fa;
        }
        .table-card .table-body table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* ============================================
           BADGES
           ============================================ */
        .status-badge {
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 0.68rem;
            font-weight: 600;
            display: inline-block;
            font-family: 'Cambria', Georgia, serif;
        }
        .status-badge.pending { background: #fff3e0; color: #f39c12; }
        .status-badge.approved { background: #e8f5e9; color: #198754; }
        .status-badge.rejected { background: #fce4ec; color: #dc3545; }
        .status-badge.expired { background: #e9ecef; color: #6c757d; }
        
        .type-badge {
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 0.68rem;
            font-weight: 600;
            display: inline-block;
            font-family: 'Cambria', Georgia, serif;
        }
        .type-badge.book { background: #e8f0fe; color: #0d6efd; }
        .type-badge.journal { background: #e8f5e9; color: #198754; }
        
        /* ============================================
           ACTION BUTTONS
           ============================================ */
        .action-btn {
            padding: 4px 12px;
            border-radius: 4px;
            border: none;
            font-size: 0.72rem;
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
        .action-btn.approve { background: #e8f5e9; color: #198754; }
        .action-btn.approve:hover { background: #198754; color: #fff; }
        .action-btn.reject { background: #fce4ec; color: #dc3545; }
        .action-btn.reject:hover { background: #dc3545; color: #fff; }
        .action-btn.processed { background: #f8f9fa; color: #6c757d; }
        
        .action-group {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        /* ============================================
           PAGINATION
           ============================================ */
        .pagination-wrapper {
            padding: 14px 18px;
            background: #f8f9fa;
            border-top: 1px solid #eef1f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .pagination-wrapper .info-text {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.85rem;
            color: #6c757d;
        }
        .pagination-wrapper .info-text strong {
            color: #000000;
        }
        .pagination-wrapper .pagination {
            margin: 0;
            gap: 3px;
        }
        .pagination-wrapper .pagination .page-link {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.85rem;
            padding: 6px 14px;
            border-radius: 4px;
            border: 1px solid #eef1f5;
            color: #000000;
            background: #ffffff;
            transition: all 0.2s ease;
        }
        .pagination-wrapper .pagination .page-link:hover {
            background: #f0f4f8;
            border-color: #0d6efd;
            color: #0d6efd;
        }
        .pagination-wrapper .pagination .page-item.active .page-link {
            background: #0d6efd;
            border-color: #0d6efd;
            color: #ffffff;
        }
        .pagination-wrapper .pagination .page-item.disabled .page-link {
            color: #adb5bd;
            pointer-events: none;
            background: #f8f9fa;
        }
        
        /* ============================================
           MODALS
           ============================================ */
        .modal-content {
            border-radius: 10px;
            border: 1px solid #eef1f5;
            background: #ffffff;
        }
        .modal-header {
            border-bottom: 1px solid #eef1f5;
            padding: 16px 22px;
        }
        .modal-header .modal-title {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 700;
            font-size: 1.2rem;
            color: #000000;
        }
        .modal-body {
            padding: 22px;
        }
        .modal-footer {
            border-top: 1px solid #eef1f5;
            padding: 14px 22px;
        }
        .modal-footer .btn {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 8px 22px;
            border-radius: 6px;
        }
        
        .form-label {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            color: #000000;
            font-size: 0.9rem;
        }
        .form-control, .form-select {
            border-radius: 6px;
            border: 1.5px solid #eef1f5;
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.95rem;
            color: #000000;
            background: #ffffff;
            padding: 8px 14px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13,110,253,0.08);
        }
        
        /* ============================================
           RESPONSIVE
           ============================================ */
        
        /* Tablet - Hide Sidebar */
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0 !important;
                max-width: 100% !important;
                padding: 14px 16px 18px;
            }
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }
            .page-header h1 {
                font-size: 1.3rem;
            }
            .page-header .badge-admin {
                font-size: 0.72rem;
                padding: 4px 12px;
            }
            .table-card .table-body table {
                min-width: 750px;
                font-size: 0.82rem;
            }
            .table-card .table-header {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-section {
                padding: 12px 14px;
                flex-direction: column;
                align-items: stretch;
            }
            .filter-section .filter-group {
                flex-wrap: wrap;
            }
            .filter-section .filter-group .form-control,
            .filter-section .filter-group .form-select {
                min-width: 120px;
                flex: 1;
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
                gap: 10px;
                padding-bottom: 10px;
                margin-bottom: 12px;
            }
            .page-header h1 {
                font-size: 1.15rem;
            }
            .page-header h1 i {
                font-size: 1rem;
            }
            .page-header .badge-admin {
                font-size: 0.65rem;
                padding: 3px 10px;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 6px;
            }
            .stat-card {
                padding: 10px 12px;
            }
            .stat-card .stat-number {
                font-size: 1.2rem;
            }
            .stat-card .stat-label {
                font-size: 0.62rem;
            }
            .stat-card .stat-icon {
                font-size: 1.3rem;
            }
            .table-card .table-body table {
                min-width: 550px;
                font-size: 0.78rem;
            }
            .table-card .table-body table thead th {
                padding: 5px 8px;
                font-size: 0.62rem;
            }
            .table-card .table-body table tbody td {
                padding: 5px 8px;
                font-size: 0.78rem;
            }
            .status-badge, .type-badge {
                font-size: 0.58rem;
                padding: 2px 8px;
            }
            .action-btn {
                font-size: 0.62rem;
                padding: 2px 8px;
            }
            .table-card .table-header {
                padding: 10px 12px;
            }
            .table-card .table-header h5 {
                font-size: 0.88rem;
            }
            .table-card .table-header .badge-count {
                font-size: 0.68rem;
                padding: 2px 10px;
            }
            .filter-section {
                padding: 10px 12px;
                flex-direction: column;
                align-items: stretch;
            }
            .filter-section .filter-label {
                font-size: 0.8rem;
            }
            .filter-section .filter-group {
                flex-direction: column;
                gap: 6px;
            }
            .filter-section .filter-group .form-control,
            .filter-section .filter-group .form-select {
                min-width: 100%;
                font-size: 0.85rem;
                padding: 5px 10px;
            }
            .filter-section .filter-group .btn-filter {
                font-size: 0.8rem;
                padding: 5px 14px;
                width: 100%;
            }
            .filter-section .filter-group .btn-clear {
                font-size: 0.8rem;
                padding: 5px 12px;
                text-align: center;
                width: 100%;
            }
            .filter-badges .badge {
                font-size: 0.65rem;
                padding: 3px 10px;
            }
            .pagination-wrapper {
                flex-direction: column;
                align-items: center;
                padding: 12px 14px;
            }
            .pagination-wrapper .info-text {
                font-size: 0.8rem;
                text-align: center;
            }
            .pagination-wrapper .pagination .page-link {
                font-size: 0.78rem;
                padding: 4px 10px;
            }
            .modal-body {
                padding: 14px;
            }
            .form-control, .form-select {
                font-size: 0.9rem;
                padding: 6px 10px;
            }
            .modal-header .modal-title {
                font-size: 1.05rem;
            }
            .modal-footer .btn {
                font-size: 0.82rem;
                padding: 6px 14px;
            }
        }
        
        /* Extra Small */
        @media (max-width: 400px) {
            .main-content {
                padding: 8px 4px 10px;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 4px;
            }
            .stat-card {
                padding: 8px 10px;
            }
            .stat-card .stat-number {
                font-size: 1rem;
            }
            .stat-card .stat-label {
                font-size: 0.55rem;
            }
            .stat-card .stat-icon {
                font-size: 1.1rem;
            }
            .table-card .table-body table {
                min-width: 480px;
                font-size: 0.72rem;
            }
            .table-card .table-body table thead th {
                padding: 4px 6px;
                font-size: 0.55rem;
            }
            .table-card .table-body table tbody td {
                padding: 4px 6px;
                font-size: 0.72rem;
            }
            .action-btn {
                font-size: 0.55rem;
                padding: 1px 6px;
            }
            .page-header h1 {
                font-size: 1rem;
            }
            .status-badge, .type-badge {
                font-size: 0.5rem;
                padding: 1px 6px;
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
            .filter-section {
                background: #f8f9fa !important;
                border-color: #eef1f5 !important;
            }
            .filter-section .filter-label {
                color: #000000 !important;
            }
            .filter-section .filter-group .form-control,
            .filter-section .filter-group .form-select {
                background: #ffffff !important;
                border-color: #eef1f5 !important;
                color: #000000 !important;
            }
            .table-card {
                background: #f8f9fa !important;
                border-color: #eef1f5 !important;
            }
            .table-card .table-header {
                background: #f8f9fa !important;
                border-bottom-color: #eef1f5 !important;
            }
            .table-card .table-header h5 {
                color: #000000 !important;
            }
            .table-card .table-body table {
                background: #ffffff !important;
            }
            .table-card .table-body table thead th {
                background: #f8f9fa !important;
                color: #495057 !important;
                border-bottom-color: #eef1f5 !important;
            }
            .table-card .table-body table tbody td {
                color: #000000 !important;
                border-bottom-color: #f0f4f8 !important;
            }
            .table-card .table-body table tbody tr:hover {
                background: #f8f9fa !important;
            }
            .page-header {
                border-bottom-color: #eef1f5 !important;
            }
            .page-header h1 {
                color: #000000 !important;
            }
            .pagination-wrapper {
                background: #f8f9fa !important;
                border-top-color: #eef1f5 !important;
            }
            .pagination-wrapper .info-text {
                color: #6c757d !important;
            }
            .pagination-wrapper .info-text strong {
                color: #000000 !important;
            }
            .pagination-wrapper .pagination .page-link {
                background: #ffffff !important;
                border-color: #eef1f5 !important;
                color: #000000 !important;
            }
            .pagination-wrapper .pagination .page-link:hover {
                background: #f0f4f8 !important;
            }
            .pagination-wrapper .pagination .page-item.active .page-link {
                background: #0d6efd !important;
                border-color: #0d6efd !important;
                color: #ffffff !important;
            }
            .modal-content {
                background: #ffffff !important;
                border-color: #eef1f5 !important;
            }
            .modal-header {
                border-bottom-color: #eef1f5 !important;
            }
            .modal-header .modal-title {
                color: #000000 !important;
            }
            .modal-footer {
                border-top-color: #eef1f5 !important;
            }
            .form-control, .form-select {
                background: #ffffff !important;
                border-color: #eef1f5 !important;
                color: #000000 !important;
            }
            .form-label {
                color: #000000 !important;
            }
            .status-badge.pending { background: #fff3e0 !important; color: #f39c12 !important; }
            .status-badge.approved { background: #e8f5e9 !important; color: #198754 !important; }
            .status-badge.rejected { background: #fce4ec !important; color: #dc3545 !important; }
            .status-badge.expired { background: #e9ecef !important; color: #6c757d !important; }
            .type-badge.book { background: #e8f0fe !important; color: #0d6efd !important; }
            .type-badge.journal { background: #e8f5e9 !important; color: #198754 !important; }
            .filter-badges .badge-all { background: #e8f0fe !important; color: #0d6efd !important; border-color: #0d6efd !important; }
            .filter-badges .badge-all.active { background: #0d6efd !important; color: #fff !important; }
            .filter-badges .badge-pending { background: #fff3e0 !important; color: #f39c12 !important; border-color: #f39c12 !important; }
            .filter-badges .badge-pending.active { background: #f39c12 !important; color: #fff !important; }
            .filter-badges .badge-approved { background: #e8f5e9 !important; color: #198754 !important; border-color: #198754 !important; }
            .filter-badges .badge-approved.active { background: #198754 !important; color: #fff !important; }
            .filter-badges .badge-rejected { background: #fce4ec !important; color: #dc3545 !important; border-color: #dc3545 !important; }
            .filter-badges .badge-rejected.active { background: #dc3545 !important; color: #fff !important; }
            .filter-badges .badge-expired { background: #e9ecef !important; color: #6c757d !important; border-color: #6c757d !important; }
            .filter-badges .badge-expired.active { background: #6c757d !important; color: #fff !important; }
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
                        <i class="fas fa-download"></i> Download Permissions
                        <span style="font-size: 0.8rem; font-weight: 400; color: #6c757d; margin-left: 8px;">
                            (<?php echo $totalPermissions; ?> total)
                        </span>
                    </h1>
                    <span class="badge bg-primary badge-admin">Admin Panel</span>
                </div>
                
                <!-- Alert Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" style="border-radius: 6px; font-family: 'Cambria', Georgia, serif; font-size: 0.9rem;">
                        <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card border-total">
                        <div class="stat-icon"><i class="fas fa-list"></i></div>
                        <div class="stat-number"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total Requests</div>
                    </div>
                    <div class="stat-card border-pending">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-number"><?php echo $stats['pending']; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card border-approved">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-number"><?php echo $stats['approved']; ?></div>
                        <div class="stat-label">Approved</div>
                    </div>
                    <div class="stat-card border-rejected">
                        <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                        <div class="stat-number"><?php echo $stats['rejected']; ?></div>
                        <div class="stat-label">Rejected</div>
                    </div>
                    <div class="stat-card border-expired">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-number"><?php echo $stats['expired']; ?></div>
                        <div class="stat-label">Expired</div>
                    </div>
                </div>
                
                <!-- Filter & Search Section -->
                <div class="filter-section">
                    <span class="filter-label"><i class="fas fa-filter"></i> Filter:</span>
                    <div class="filter-group">
                        <form method="GET" action="" class="d-flex flex-wrap gap-2" style="flex: 1;">
                            <input type="text" class="form-control" name="search" placeholder="Search by user or item..." value="<?php echo htmlspecialchars($searchQuery); ?>" style="flex: 2; min-width: 180px;">
                            <select class="form-select" name="status" style="flex: 1; min-width: 130px;">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="expired" <?php echo $statusFilter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                            </select>
                            <select class="form-select" name="type" style="flex: 1; min-width: 120px;">
                                <option value="">All Types</option>
                                <option value="book" <?php echo $typeFilter === 'book' ? 'selected' : ''; ?>>Book</option>
                                <option value="journal" <?php echo $typeFilter === 'journal' ? 'selected' : ''; ?>>Journal</option>
                            </select>
                            <button type="submit" class="btn btn-primary btn-filter">
                                <i class="fas fa-search"></i> Apply
                            </button>
                            <?php if (!empty($searchQuery) || !empty($statusFilter) || !empty($typeFilter)): ?>
                            <a href="manage_download_permissions.php" class="btn-clear">
                                <i class="fas fa-times"></i> Clear
                            </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <!-- Status Filter Badges -->
                <div class="filter-badges">
                    <a href="manage_download_permissions.php" class="badge badge-all <?php echo (empty($searchQuery) && empty($statusFilter) && empty($typeFilter)) ? 'active' : ''; ?>">
                        All (<?php echo $stats['total']; ?>)
                    </a>
                    <a href="?status=pending<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo !empty($typeFilter) ? '&type=' . urlencode($typeFilter) : ''; ?>" 
                       class="badge badge-pending <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
                        Pending (<?php echo $stats['pending']; ?>)
                    </a>
                    <a href="?status=approved<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo !empty($typeFilter) ? '&type=' . urlencode($typeFilter) : ''; ?>" 
                       class="badge badge-approved <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>">
                        Approved (<?php echo $stats['approved']; ?>)
                    </a>
                    <a href="?status=rejected<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo !empty($typeFilter) ? '&type=' . urlencode($typeFilter) : ''; ?>" 
                       class="badge badge-rejected <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>">
                        Rejected (<?php echo $stats['rejected']; ?>)
                    </a>
                    <a href="?status=expired<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo !empty($typeFilter) ? '&type=' . urlencode($typeFilter) : ''; ?>" 
                       class="badge badge-expired <?php echo $statusFilter === 'expired' ? 'active' : ''; ?>">
                        Expired (<?php echo $stats['expired']; ?>)
                    </a>
                </div>
                
                <!-- Table Card -->
                <div class="table-card">
                    <div class="table-header">
                        <h5><i class="fas fa-list"></i> All Download Permission Requests</h5>
                        <?php if ($stats['pending'] > 0): ?>
                        <span class="badge-count pending">
                            <i class="fas fa-bell"></i> <?php echo $stats['pending']; ?> Pending
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="table-body">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 50px;">ID</th>
                                    <th>User</th>
                                    <th>Item</th>
                                    <th style="width: 80px;">Type</th>
                                    <th style="width: 100px;">Downloads</th>
                                    <th style="width: 110px;">Status</th>
                                    <th style="width: 110px;">Requested</th>
                                    <th style="width: 190px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($permissions)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4" style="color: #6c757d; font-family: 'Cambria', Georgia, serif; font-size: 0.95rem;">
                                        <i class="fas fa-inbox fa-3x d-block mb-3" style="color: #dee2e6;"></i>
                                        <?php if (!empty($searchQuery) || !empty($statusFilter) || !empty($typeFilter)): ?>
                                            No permissions match your filter criteria.
                                        <?php else: ?>
                                            No download permission requests found.
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($permissions as $perm): ?>
                                <tr>
                                    <td><strong><?php echo $perm['id']; ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($perm['user_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($perm['user_email']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($perm['item_title']): ?>
                                        <a href="<?php echo $perm['item_type'] == 'book' ? '/doctor/view-book/' . $perm['item_id'] : '/doctor/view-journal/' . $perm['item_id']; ?>" 
                                           target="_blank" style="color: <?php echo $perm['item_type'] == 'book' ? '#0d6efd' : '#198754'; ?>; text-decoration: none;">
                                            <i class="fas fa-<?php echo $perm['item_type'] == 'book' ? 'book' : 'newspaper'; ?>"></i>
                                            <?php echo htmlspecialchars($perm['item_title']); ?>
                                        </a>
                                        <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="type-badge <?php echo $perm['item_type']; ?>">
                                            <?php echo ucfirst($perm['item_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-weight: 600;"><?php echo $perm['used_downloads'] ?? '0'; ?></span>
                                        <span class="text-muted">/ <?php echo $perm['max_downloads'] ?? 'N/A'; ?></span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $perm['status']; ?>">
                                            <?php echo ucfirst($perm['status']); ?>
                                        </span>
                                        <?php if ($perm['status'] == 'approved' && $perm['expires_at']): ?>
                                        <br><small class="text-muted">Expires: <?php echo date('M d, Y', strtotime($perm['expires_at'])); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($perm['requested_at'])); ?>
                                        <br><small class="text-muted"><?php echo date('h:i A', strtotime($perm['requested_at'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($perm['status'] === 'pending'): ?>
                                        <div class="action-group">
                                            <button class="action-btn approve" onclick="processPermission(<?php echo $perm['id']; ?>, 'approved')">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button class="action-btn reject" onclick="processPermission(<?php echo $perm['id']; ?>, 'rejected')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </div>
                                        <?php else: ?>
                                        <span class="action-btn processed">
                                            <i class="fas fa-check"></i> Processed
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination-wrapper">
                        <div class="info-text">
                            Showing <strong><?php echo $offset + 1; ?></strong> to 
                            <strong><?php echo min($offset + $perPage, $totalPermissions); ?></strong> 
                            of <strong><?php echo $totalPermissions; ?></strong> permissions
                        </div>
                        <nav>
                            <ul class="pagination">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo !empty($typeFilter) ? '&type=' . urlencode($typeFilter) : ''; ?>" aria-label="Previous">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo !empty($typeFilter) ? '&type=' . urlencode($typeFilter) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo !empty($typeFilter) ? '&type=' . urlencode($typeFilter) : ''; ?>" aria-label="Next">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    
    <!-- ============================================
    PROCESS PERMISSION MODAL
    ============================================ -->
    <div class="modal fade" id="processModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-cog" style="color: #0d6efd;"></i> Process Download Permission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="permission_id" id="permission_id">
                    <input type="hidden" name="status" id="permission_status">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Max Downloads</label>
                            <input type="number" class="form-control" id="max_downloads" name="max_downloads" value="2" min="1" max="10">
                            <small class="text-muted" style="font-family: 'Cambria', Georgia, serif; font-size: 0.75rem;">Number of times the user can download</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expiry Days</label>
                            <input type="number" class="form-control" id="expiry_days" name="expiry_days" value="30" min="1" max="365">
                            <small class="text-muted" style="font-family: 'Cambria', Georgia, serif; font-size: 0.75rem;">Days until permission expires</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Admin Notes</label>
                            <textarea class="form-control" id="admin_notes" name="admin_notes" rows="2" placeholder="Add notes about this decision..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="process_permission" class="btn btn-primary">
                            <i class="fas fa-save"></i> Process
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        // Auto-submit filter on change
        document.addEventListener('DOMContentLoaded', function() {
            const filterSelects = document.querySelectorAll('.filter-section select');
            filterSelects.forEach(function(select) {
                select.addEventListener('change', function() {
                    this.closest('form').submit();
                });
            });
        });

        function processPermission(id, status) {
            $('#permission_id').val(id);
            $('#permission_status').val(status);
            $('#processModal').modal('show');
        }
    </script>
</body>
</html>