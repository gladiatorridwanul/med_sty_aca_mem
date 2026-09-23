<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireAdmin();

$db = Database::getInstance()->getConnection();

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
    $whereConditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.mobile LIKE ? OR b.title LIKE ? OR j.title LIKE ? OR sr.delivery_address LIKE ? OR sr.delivery_phone LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sssssss";
}

if (!empty($statusFilter)) {
    $whereConditions[] = "sr.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

if (!empty($typeFilter)) {
    $whereConditions[] = "sr.request_type = ?";
    $params[] = $typeFilter;
    $types .= "s";
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM supply_requests sr 
             LEFT JOIN users u ON sr.user_id = u.id 
             LEFT JOIN books b ON sr.book_id = b.id 
             LEFT JOIN journals j ON sr.journal_id = j.id 
             $whereClause";
$countStmt = $db->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalResult = $countStmt->get_result();
$totalRequests = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRequests / $perPage);
$countStmt->close();

// Get all supply requests with details and pagination
$sql = "SELECT sr.*, 
        u.name as doctor_name, u.email as doctor_email, u.mobile as doctor_mobile,
        b.title as book_title, b.id as book_id,
        j.title as journal_title, j.id as journal_id,
        a.name as processed_by_name
        FROM supply_requests sr 
        LEFT JOIN users u ON sr.user_id = u.id 
        LEFT JOIN books b ON sr.book_id = b.id 
        LEFT JOIN journals j ON sr.journal_id = j.id 
        LEFT JOIN users a ON sr.processed_by = a.id
        $whereClause
        ORDER BY sr.request_date DESC 
        LIMIT ? OFFSET ?";

$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle request processing
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
        // Refresh data
        $stmt2 = $db->prepare($sql);
        $stmt2->bind_param($types, ...$params);
        $stmt2->execute();
        $requests = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt2->close();
    } else {
        $error = "Failed to process request.";
    }
    $stmt->close();
}

// Handle bulk action
if (isset($_POST['bulk_action']) && isset($_POST['selected_requests'])) {
    $action = $_POST['bulk_action'];
    $selected = $_POST['selected_requests'];
    $notes = sanitize($_POST['bulk_notes'] ?? '');
    $userId = $_SESSION['user_id'];
    
    if ($action && !empty($selected)) {
        $placeholders = implode(',', array_fill(0, count($selected), '?'));
        $types2 = str_repeat('i', count($selected));
        
        $stmt = $db->prepare("UPDATE supply_requests SET status = ?, admin_notes = CONCAT(admin_notes, ?), processed_by = ?, processed_date = NOW() WHERE id IN ($placeholders)");
        $params2 = array_merge([$action, "\nBulk action: $notes\n", $userId], $selected);
        $stmt->bind_param("ssi" . $types2, ...$params2);
        
        if ($stmt->execute()) {
            logActivity($userId, 'Bulk Processed Supply Requests', "Processed " . count($selected) . " requests");
            $success = count($selected) . " requests processed successfully.";
            // Refresh data
            $stmt2 = $db->prepare($sql);
            $stmt2->bind_param($types, ...$params);
            $stmt2->execute();
            $requests = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt2->close();
        } else {
            $error = "Failed to process requests.";
        }
        $stmt->close();
    }
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
    <title>Supply Requests - BJDVL</title>
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
        .page-header .btn-refresh {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            padding: 6px 16px;
            border-radius: 6px;
            font-size: 0.9rem;
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
            position: relative;
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
        .stat-card.border-completed { border-left: 3px solid #0dcaf0; }
        .stat-card.border-rejected { border-left: 3px solid #dc3545; }
        
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
        .filter-badges .badge-completed {
            background: #e0f7fa;
            color: #0dcaf0;
            border-color: #0dcaf0;
        }
        .filter-badges .badge-completed.active {
            background: #0dcaf0;
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
        .table-card .table-header .bulk-form {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
        .table-card .table-header .bulk-form .form-select,
        .table-card .table-header .bulk-form .form-control {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.8rem;
            padding: 4px 10px;
            border-radius: 4px;
            border: 1.5px solid #eef1f5;
            background: #ffffff;
            color: #000000;
            min-width: 130px;
        }
        .table-card .table-header .bulk-form .form-control {
            min-width: 160px;
        }
        .table-card .table-header .bulk-form .btn {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 4px 16px;
            border-radius: 4px;
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
            min-width: 950px;
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
        .status-badge.completed { background: #e0f7fa; color: #0dcaf0; }
        .status-badge.rejected { background: #fce4ec; color: #dc3545; }
        
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
           CHECKBOX
           ============================================ */
        .form-check-input {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #0d6efd;
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
           REQUEST DETAILS
           ============================================ */
        .request-details {
            font-size: 0.78rem;
            color: #6c757d;
        }
        .request-details strong {
            color: #000000;
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
            .page-header .btn-refresh {
                font-size: 0.82rem;
                padding: 5px 12px;
            }
            .table-card .table-body table {
                min-width: 750px;
                font-size: 0.82rem;
            }
            .table-card .table-header {
                flex-direction: column;
                align-items: stretch;
            }
            .table-card .table-header .bulk-form {
                width: 100%;
            }
            .table-card .table-header .bulk-form .form-control {
                min-width: 100px;
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
            .page-header .btn-refresh {
                font-size: 0.78rem;
                padding: 4px 12px;
                width: 100%;
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
            .table-card .table-header .bulk-form {
                gap: 5px;
            }
            .table-card .table-header .bulk-form .form-select,
            .table-card .table-header .bulk-form .form-control {
                font-size: 0.7rem;
                padding: 3px 8px;
                min-width: 80px;
            }
            .table-card .table-header .bulk-form .btn {
                font-size: 0.7rem;
                padding: 3px 10px;
            }
            .request-details {
                font-size: 0.68rem;
            }
            .form-check-input {
                width: 14px;
                height: 14px;
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
            .request-details {
                color: #6c757d !important;
            }
            .request-details strong {
                color: #000000 !important;
            }
            .table-card .table-header .bulk-form .form-select,
            .table-card .table-header .bulk-form .form-control {
                background: #ffffff !important;
                border-color: #eef1f5 !important;
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
            .status-badge.pending { background: #fff3e0 !important; color: #f39c12 !important; }
            .status-badge.approved { background: #e8f5e9 !important; color: #198754 !important; }
            .status-badge.completed { background: #e0f7fa !important; color: #0dcaf0 !important; }
            .status-badge.rejected { background: #fce4ec !important; color: #dc3545 !important; }
            .type-badge.book { background: #e8f0fe !important; color: #0d6efd !important; }
            .type-badge.journal { background: #e8f5e9 !important; color: #198754 !important; }
            .filter-badges .badge-all { background: #e8f0fe !important; color: #0d6efd !important; border-color: #0d6efd !important; }
            .filter-badges .badge-all.active { background: #0d6efd !important; color: #fff !important; }
            .filter-badges .badge-pending { background: #fff3e0 !important; color: #f39c12 !important; border-color: #f39c12 !important; }
            .filter-badges .badge-pending.active { background: #f39c12 !important; color: #fff !important; }
            .filter-badges .badge-approved { background: #e8f5e9 !important; color: #198754 !important; border-color: #198754 !important; }
            .filter-badges .badge-approved.active { background: #198754 !important; color: #fff !important; }
            .filter-badges .badge-completed { background: #e0f7fa !important; color: #0dcaf0 !important; border-color: #0dcaf0 !important; }
            .filter-badges .badge-completed.active { background: #0dcaf0 !important; color: #fff !important; }
            .filter-badges .badge-rejected { background: #fce4ec !important; color: #dc3545 !important; border-color: #dc3545 !important; }
            .filter-badges .badge-rejected.active { background: #dc3545 !important; color: #fff !important; }
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
                        <i class="fas fa-truck"></i> Supply Requests
                        <span style="font-size: 0.8rem; font-weight: 400; color: #6c757d; margin-left: 8px;">
                            (<?php echo $totalRequests; ?> total)
                        </span>
                    </h1>
                    <button class="btn btn-outline-primary btn-refresh" onclick="window.location.reload()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                </div>
                
                <!-- Alert Messages -->
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" style="border-radius: 6px; font-family: 'Cambria', Georgia, serif; font-size: 0.9rem;">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" style="border-radius: 6px; font-family: 'Cambria', Georgia, serif; font-size: 0.9rem;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
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
                    <div class="stat-card border-completed">
                        <div class="stat-icon"><i class="fas fa-check-double"></i></div>
                        <div class="stat-number"><?php echo $stats['completed']; ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-card border-rejected">
                        <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                        <div class="stat-number"><?php echo $stats['rejected']; ?></div>
                        <div class="stat-label">Rejected</div>
                    </div>
                </div>
                
                <!-- Filter & Search Section -->
                <div class="filter-section">
                    <span class="filter-label"><i class="fas fa-filter"></i> Filter:</span>
                    <div class="filter-group">
                        <form method="GET" action="" class="d-flex flex-wrap gap-2" style="flex: 1;">
                            <input type="text" class="form-control" name="search" placeholder="Search by doctor, item, address..." value="<?php echo htmlspecialchars($searchQuery); ?>" style="flex: 2; min-width: 180px;">
                            <select class="form-select" name="status" style="flex: 1; min-width: 130px;">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
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
                            <a href="supply_requests.php" class="btn-clear">
                                <i class="fas fa-times"></i> Clear
                            </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <!-- Status Filter Badges -->
                <div class="filter-badges">
                    <a href="supply_requests.php" class="badge badge-all <?php echo (empty($searchQuery) && empty($statusFilter) && empty($typeFilter)) ? 'active' : ''; ?>">
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
                    <a href="?status=completed<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo !empty($typeFilter) ? '&type=' . urlencode($typeFilter) : ''; ?>" 
                       class="badge badge-completed <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">
                        Completed (<?php echo $stats['completed']; ?>)
                    </a>
                    <a href="?status=rejected<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo !empty($typeFilter) ? '&type=' . urlencode($typeFilter) : ''; ?>" 
                       class="badge badge-rejected <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>">
                        Rejected (<?php echo $stats['rejected']; ?>)
                    </a>
                </div>
                
                <!-- Table Card -->
                <div class="table-card">
                    <div class="table-header">
                        <h5><i class="fas fa-list"></i> All Requests</h5>
                        <form method="POST" action="" class="bulk-form">
                            <select name="bulk_action" class="form-select">
                                <option value="">Bulk Action</option>
                                <option value="approved">Approve Selected</option>
                                <option value="completed">Mark as Completed</option>
                                <option value="rejected">Reject Selected</option>
                            </select>
                            <input type="text" name="bulk_notes" class="form-control" placeholder="Admin notes">
                            <button type="submit" class="btn btn-primary">Apply</button>
                        </form>
                    </div>
                    <div class="table-body">
                        <form method="POST" action="" id="bulkForm">
                            <table>
                                <thead>
                                    <tr>
                                        <th width="35"><input type="checkbox" class="form-check-input" id="selectAll"></th>
                                        <th style="width: 50px;">ID</th>
                                        <th>Doctor</th>
                                        <th>Item</th>
                                        <th style="width: 80px;">Type</th>
                                        <th>Address</th>
                                        <th style="width: 110px;">Status</th>
                                        <th style="width: 110px;">Date</th>
                                        <th style="width: 180px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($requests)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4" style="color: #6c757d; font-family: 'Cambria', Georgia, serif; font-size: 0.95rem;">
                                            <i class="fas fa-inbox fa-3x d-block mb-3" style="color: #dee2e6;"></i>
                                            <?php if (!empty($searchQuery) || !empty($statusFilter) || !empty($typeFilter)): ?>
                                                No requests match your filter criteria.
                                            <?php else: ?>
                                                No supply requests found.
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($requests as $request): ?>
                                    <tr>
                                        <td><input type="checkbox" name="selected_requests[]" value="<?php echo $request['id']; ?>" class="form-check-input"></td>
                                        <td><strong><?php echo $request['id']; ?></strong></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($request['doctor_name']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($request['doctor_email']); ?></small>
                                            <?php if ($request['doctor_mobile']): ?>
                                            <br><small><i class="fas fa-phone"></i> <?php echo htmlspecialchars($request['doctor_mobile']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($request['request_type'] == 'book'): ?>
                                            <a href="/doctor/view-book/<?php echo $request['book_id']; ?>" target="_blank" style="color: #0d6efd; text-decoration: none;">
                                                <i class="fas fa-book"></i> <?php echo htmlspecialchars($request['book_title']); ?>
                                            </a>
                                            <?php else: ?>
                                            <a href="/doctor/view-journal/<?php echo $request['journal_id']; ?>" target="_blank" style="color: #198754; text-decoration: none;">
                                                <i class="fas fa-newspaper"></i> <?php echo htmlspecialchars($request['journal_title']); ?>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="type-badge <?php echo $request['request_type']; ?>">
                                                <?php echo ucfirst($request['request_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="request-details">
                                                <strong>Address:</strong> <?php echo substr(htmlspecialchars($request['delivery_address']), 0, 40); ?>...
                                                <?php if ($request['delivery_phone']): ?>
                                                <br><strong>Phone:</strong> <?php echo htmlspecialchars($request['delivery_phone']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $request['status']; ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                            <?php if ($request['processed_by_name']): ?>
                                            <br><small class="text-muted">by <?php echo htmlspecialchars($request['processed_by_name']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($request['request_date'])); ?>
                                            <br><small class="text-muted"><?php echo date('h:i A', strtotime($request['request_date'])); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($request['status'] === 'pending'): ?>
                                            <div class="action-group">
                                                <form method="POST" action="" class="d-inline">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" name="process" class="action-btn approve" onclick="return confirm('Approve this request?')">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" action="" class="d-inline">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <input type="hidden" name="status" value="rejected">
                                                    <button type="submit" name="process" class="action-btn reject" onclick="return confirm('Reject this request?')">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
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
                        </form>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination-wrapper">
                        <div class="info-text">
                            Showing <strong><?php echo $offset + 1; ?></strong> to 
                            <strong><?php echo min($offset + $perPage, $totalRequests); ?></strong> 
                            of <strong><?php echo $totalRequests; ?></strong> requests
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

        $(document).ready(function() {
            // Select all checkbox
            $('#selectAll').on('change', function() {
                $('input[name="selected_requests[]"]').prop('checked', this.checked);
            });
        });
    </script>
</body>
</html>