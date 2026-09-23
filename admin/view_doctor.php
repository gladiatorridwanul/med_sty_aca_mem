<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireAdmin();

$doctorId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$doctorId) {
    redirect('manage_doctors.php');
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'doctor'");
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();
$stmt->close();

if (!$doctor) {
    redirect('manage_doctors.php');
}

// Get search and filter parameters for requests
$requestSearch = isset($_GET['request_search']) ? sanitize($_GET['request_search']) : '';
$requestStatusFilter = isset($_GET['request_status']) ? sanitize($_GET['request_status']) : '';
$requestTypeFilter = isset($_GET['request_type']) ? sanitize($_GET['request_type']) : '';
$requestPage = isset($_GET['request_page']) ? intval($_GET['request_page']) : 1;
$perPage = 10;
$requestOffset = ($requestPage - 1) * $perPage;

// Build WHERE clause for requests
$requestWhere = ["sr.user_id = $doctorId"];
$requestParams = [];
$requestTypes = "";

if (!empty($requestSearch)) {
    $searchTerm = '%' . $requestSearch . '%';
    $requestWhere[] = "(b.title LIKE ? OR j.title LIKE ? OR sr.delivery_address LIKE ? OR sr.delivery_phone LIKE ?)";
    $requestParams[] = $searchTerm;
    $requestParams[] = $searchTerm;
    $requestParams[] = $searchTerm;
    $requestParams[] = $searchTerm;
    $requestTypes .= "ssss";
}

if (!empty($requestStatusFilter)) {
    $requestWhere[] = "sr.status = ?";
    $requestParams[] = $requestStatusFilter;
    $requestTypes .= "s";
}

if (!empty($requestTypeFilter)) {
    $requestWhere[] = "sr.request_type = ?";
    $requestParams[] = $requestTypeFilter;
    $requestTypes .= "s";
}

$requestWhereClause = "WHERE " . implode(" AND ", $requestWhere);

// Get total request count for pagination
$countSql = "SELECT COUNT(*) as total FROM supply_requests sr 
             LEFT JOIN books b ON sr.book_id = b.id 
             LEFT JOIN journals j ON sr.journal_id = j.id 
             $requestWhereClause";
$countStmt = $db->prepare($countSql);
if (!empty($requestParams)) {
    $countStmt->bind_param($requestTypes, ...$requestParams);
}
$countStmt->execute();
$totalRequests = $countStmt->get_result()->fetch_assoc()['total'];
$totalRequestPages = ceil($totalRequests / $perPage);
$countStmt->close();

// Get requests with pagination
$requestSql = "SELECT sr.*, b.title as book_title, j.title as journal_title 
               FROM supply_requests sr 
               LEFT JOIN books b ON sr.book_id = b.id 
               LEFT JOIN journals j ON sr.journal_id = j.id 
               $requestWhereClause
               ORDER BY sr.request_date DESC 
               LIMIT ? OFFSET ?";
$requestParams[] = $perPage;
$requestParams[] = $requestOffset;
$requestTypes .= "ii";

$requestStmt = $db->prepare($requestSql);
$requestStmt->bind_param($requestTypes, ...$requestParams);
$requestStmt->execute();
$requests = $requestStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$requestStmt->close();

// Get search and filter parameters for downloads
$downloadSearch = isset($_GET['download_search']) ? sanitize($_GET['download_search']) : '';
$downloadStatusFilter = isset($_GET['download_status']) ? sanitize($_GET['download_status']) : '';
$downloadTypeFilter = isset($_GET['download_type']) ? sanitize($_GET['download_type']) : '';
$downloadPage = isset($_GET['download_page']) ? intval($_GET['download_page']) : 1;
$downloadOffset = ($downloadPage - 1) * $perPage;

// Build WHERE clause for downloads
$downloadWhere = ["dp.user_id = $doctorId"];
$downloadParams = [];
$downloadTypes = "";

if (!empty($downloadSearch)) {
    $searchTerm = '%' . $downloadSearch . '%';
    $downloadWhere[] = "(b.title LIKE ? OR j.title LIKE ?)";
    $downloadParams[] = $searchTerm;
    $downloadParams[] = $searchTerm;
    $downloadTypes .= "ss";
}

if (!empty($downloadStatusFilter)) {
    $downloadWhere[] = "dp.status = ?";
    $downloadParams[] = $downloadStatusFilter;
    $downloadTypes .= "s";
}

if (!empty($downloadTypeFilter)) {
    $downloadWhere[] = "dp.item_type = ?";
    $downloadParams[] = $downloadTypeFilter;
    $downloadTypes .= "s";
}

$downloadWhereClause = "WHERE " . implode(" AND ", $downloadWhere);

// Get total download count for pagination
$countSql2 = "SELECT COUNT(*) as total FROM download_permissions dp 
              LEFT JOIN books b ON dp.item_type = 'book' AND dp.item_id = b.id 
              LEFT JOIN journals j ON dp.item_type = 'journal' AND dp.item_id = j.id 
              $downloadWhereClause";
$countStmt2 = $db->prepare($countSql2);
if (!empty($downloadParams)) {
    $countStmt2->bind_param($downloadTypes, ...$downloadParams);
}
$countStmt2->execute();
$totalDownloads = $countStmt2->get_result()->fetch_assoc()['total'];
$totalDownloadPages = ceil($totalDownloads / $perPage);
$countStmt2->close();

// Get downloads with pagination
$downloadSql = "SELECT dp.*, 
                CASE WHEN dp.item_type = 'book' THEN b.title ELSE j.title END as item_title
                FROM download_permissions dp 
                LEFT JOIN books b ON dp.item_type = 'book' AND dp.item_id = b.id 
                LEFT JOIN journals j ON dp.item_type = 'journal' AND dp.item_id = j.id 
                $downloadWhereClause
                ORDER BY dp.requested_at DESC 
                LIMIT ? OFFSET ?";
$downloadParams[] = $perPage;
$downloadParams[] = $downloadOffset;
$downloadTypes .= "ii";

$downloadStmt = $db->prepare($downloadSql);
$downloadStmt->bind_param($downloadTypes, ...$downloadParams);
$downloadStmt->execute();
$downloads = $downloadStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$downloadStmt->close();

// Get statistics
$stats = [
    'total_requests' => $db->query("SELECT COUNT(*) as count FROM supply_requests WHERE user_id = $doctorId")->fetch_assoc()['count'],
    'pending_requests' => $db->query("SELECT COUNT(*) as count FROM supply_requests WHERE user_id = $doctorId AND status = 'pending'")->fetch_assoc()['count'],
    'total_downloads' => $db->query("SELECT COUNT(*) as count FROM download_permissions WHERE user_id = $doctorId")->fetch_assoc()['count'],
    'pending_downloads' => $db->query("SELECT COUNT(*) as count FROM download_permissions WHERE user_id = $doctorId AND status = 'pending'")->fetch_assoc()['count'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Doctor - BJDVL</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
        .page-header .btn-back {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 6px 16px;
            border-radius: 6px;
        }
        
        /* ============================================
           DOCTOR PROFILE GRID
           ============================================ */
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 18px;
        }
        
        .profile-card {
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #eef1f5;
            overflow: hidden;
        }
        
        .profile-card .card-header {
            padding: 12px 16px;
            border-bottom: 1px solid #eef1f5;
            background: #f8f9fa;
            font-weight: 700;
            font-size: 0.95rem;
            color: #000000;
            font-family: 'Cambria', Georgia, serif;
        }
        .profile-card .card-header i {
            margin-right: 8px;
        }
        .profile-card .card-body {
            padding: 18px;
        }
        
        /* ============================================
           PROFILE AVATAR
           ============================================ */
        .profile-avatar-wrapper {
            text-align: center;
        }
        .profile-avatar {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #0d6efd;
            padding: 3px;
            background: #ffffff;
        }
        .profile-name {
            font-weight: 700;
            font-size: 1.2rem;
            color: #000000;
            margin: 10px 0 4px;
            font-family: 'Cambria', Georgia, serif;
        }
        .profile-email {
            color: #6c757d;
            font-size: 0.9rem;
            font-family: 'Cambria', Georgia, serif;
        }
        
        .badge-role {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.7rem;
            padding: 4px 14px;
            border-radius: 12px;
            display: inline-block;
            margin: 4px 2px;
        }
        .badge-role.verified { background: #e8f5e9; color: #198754; }
        .badge-role.pending { background: #fff3e0; color: #f39c12; }
        .badge-role.active { background: #e8f5e9; color: #198754; }
        .badge-role.inactive { background: #fce4ec; color: #dc3545; }
        
        /* ============================================
           STATS MINI CARDS
           ============================================ */
        .stats-mini-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 14px;
        }
        .stat-mini {
            background: #ffffff;
            border-radius: 6px;
            padding: 12px 14px;
            border: 1px solid #eef1f5;
            text-align: center;
        }
        .stat-mini .stat-number {
            font-size: 1.4rem;
            font-weight: 700;
            color: #000000;
            font-family: 'Cambria', Georgia, serif;
        }
        .stat-mini .stat-label {
            font-size: 0.7rem;
            color: #6c757d;
            font-family: 'Cambria', Georgia, serif;
        }
        
        /* ============================================
           INFORMATION ITEMS
           ============================================ */
        .info-item {
            display: flex;
            padding: 7px 0;
            border-bottom: 1px solid #f0f4f8;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-item .info-label {
            width: 130px;
            font-weight: 600;
            color: #000000;
            font-size: 0.9rem;
            font-family: 'Cambria', Georgia, serif;
            flex-shrink: 0;
        }
        .info-item .info-value {
            color: #000000;
            font-size: 0.9rem;
            font-family: 'Cambria', Georgia, serif;
        }
        
        /* ============================================
           FILTER SECTION
           ============================================ */
        .filter-section {
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #eef1f5;
            padding: 12px 14px;
            margin-bottom: 12px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }
        .filter-section .filter-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #000000;
            font-family: 'Cambria', Georgia, serif;
            margin-right: 2px;
        }
        .filter-section .filter-group {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
            flex: 1;
        }
        .filter-section .filter-group .form-control,
        .filter-section .filter-group .form-select {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.85rem;
            padding: 4px 10px;
            border-radius: 4px;
            border: 1.5px solid #eef1f5;
            background: #ffffff;
            color: #000000;
            min-width: 130px;
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
            font-size: 0.8rem;
            padding: 4px 14px;
            border-radius: 4px;
        }
        .filter-section .filter-group .btn-clear {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.8rem;
            padding: 4px 10px;
            border-radius: 4px;
            color: #6c757d;
            text-decoration: none;
        }
        .filter-section .filter-group .btn-clear:hover {
            color: #dc3545;
        }
        
        /* ============================================
           TABLES
           ============================================ */
        .table-responsive-custom {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .table-custom {
            margin: 0;
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.88rem;
            width: 100%;
            min-width: 500px;
            background: #ffffff;
        }
        .table-custom thead th {
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
        .table-custom tbody td {
            padding: 8px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f4f8;
            color: #000000;
            font-size: 0.88rem;
        }
        .table-custom tbody tr:hover {
            background: #f8f9fa;
        }
        .table-custom tbody tr:last-child td {
            border-bottom: none;
        }
        
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
        .status-badge.completed { background: #e0f7fa; color: #0dcaf0; }
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
           PAGINATION
           ============================================ */
        .pagination-wrapper {
            padding: 10px 14px;
            background: #f8f9fa;
            border-top: 1px solid #eef1f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        .pagination-wrapper .info-text {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.82rem;
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
            font-size: 0.82rem;
            padding: 4px 12px;
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
           RESPONSIVE
           ============================================ */
        
        /* Tablet - Hide Sidebar */
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0 !important;
                max-width: 100% !important;
                padding: 14px 16px 18px;
            }
            .profile-grid {
                grid-template-columns: 1fr;
                gap: 14px;
            }
            .page-header h1 {
                font-size: 1.3rem;
            }
            .page-header .btn-back {
                font-size: 0.82rem;
                padding: 5px 12px;
            }
            .info-item .info-label {
                width: 110px;
            }
            .table-custom {
                min-width: 400px;
            }
            .filter-section .filter-group .form-control,
            .filter-section .filter-group .form-select {
                min-width: 100px;
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
            .page-header .btn-back {
                font-size: 0.78rem;
                padding: 4px 12px;
                width: 100%;
                text-align: center;
            }
            .profile-avatar {
                width: 100px;
                height: 100px;
            }
            .profile-name {
                font-size: 1.05rem;
            }
            .profile-email {
                font-size: 0.85rem;
            }
            .profile-card .card-header {
                font-size: 0.85rem;
                padding: 10px 12px;
            }
            .profile-card .card-body {
                padding: 14px;
            }
            .info-item {
                flex-direction: column;
                padding: 5px 0;
            }
            .info-item .info-label {
                width: 100%;
                font-size: 0.78rem;
            }
            .info-item .info-value {
                font-size: 0.85rem;
            }
            .stats-mini-grid {
                grid-template-columns: 1fr 1fr;
                gap: 6px;
            }
            .stat-mini .stat-number {
                font-size: 1.1rem;
            }
            .stat-mini .stat-label {
                font-size: 0.6rem;
            }
            .stat-mini {
                padding: 8px 10px;
            }
            .table-custom {
                min-width: 350px;
                font-size: 0.8rem;
            }
            .table-custom thead th {
                padding: 5px 8px;
                font-size: 0.62rem;
            }
            .table-custom tbody td {
                padding: 5px 8px;
                font-size: 0.8rem;
            }
            .status-badge, .type-badge {
                font-size: 0.58rem;
                padding: 2px 8px;
            }
            .badge-role {
                font-size: 0.6rem;
                padding: 3px 10px;
            }
            .filter-section {
                padding: 10px 12px;
                flex-direction: column;
                align-items: stretch;
            }
            .filter-section .filter-label {
                font-size: 0.78rem;
            }
            .filter-section .filter-group {
                flex-direction: column;
                gap: 5px;
            }
            .filter-section .filter-group .form-control,
            .filter-section .filter-group .form-select {
                min-width: 100%;
                font-size: 0.82rem;
                padding: 4px 8px;
            }
            .filter-section .filter-group .btn-filter {
                font-size: 0.75rem;
                padding: 4px 12px;
                width: 100%;
            }
            .filter-section .filter-group .btn-clear {
                font-size: 0.75rem;
                padding: 4px 10px;
                text-align: center;
                width: 100%;
            }
            .pagination-wrapper {
                flex-direction: column;
                align-items: center;
                padding: 10px 12px;
            }
            .pagination-wrapper .info-text {
                font-size: 0.75rem;
                text-align: center;
            }
            .pagination-wrapper .pagination .page-link {
                font-size: 0.75rem;
                padding: 3px 8px;
            }
        }
        
        /* Extra Small */
        @media (max-width: 400px) {
            .main-content {
                padding: 8px 4px 10px;
            }
            .profile-avatar {
                width: 85px;
                height: 85px;
            }
            .profile-name {
                font-size: 0.95rem;
            }
            .profile-email {
                font-size: 0.78rem;
            }
            .profile-card .card-body {
                padding: 10px;
            }
            .stats-mini-grid {
                grid-template-columns: 1fr 1fr;
                gap: 4px;
            }
            .stat-mini {
                padding: 6px 8px;
            }
            .stat-mini .stat-number {
                font-size: 0.95rem;
            }
            .stat-mini .stat-label {
                font-size: 0.55rem;
            }
            .table-custom {
                min-width: 300px;
                font-size: 0.72rem;
            }
            .table-custom thead th {
                padding: 3px 6px;
                font-size: 0.55rem;
            }
            .table-custom tbody td {
                padding: 3px 6px;
                font-size: 0.72rem;
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
            .profile-card {
                background: #f8f9fa !important;
                border-color: #eef1f5 !important;
            }
            .profile-card .card-header {
                background: #f8f9fa !important;
                border-bottom-color: #eef1f5 !important;
                color: #000000 !important;
            }
            .profile-card .card-body {
                background: #f8f9fa !important;
            }
            .profile-name {
                color: #000000 !important;
            }
            .profile-email {
                color: #6c757d !important;
            }
            .info-item {
                border-bottom-color: #f0f4f8 !important;
            }
            .info-item .info-label {
                color: #000000 !important;
            }
            .info-item .info-value {
                color: #000000 !important;
            }
            .stat-mini {
                background: #ffffff !important;
                border-color: #eef1f5 !important;
            }
            .stat-mini .stat-number {
                color: #000000 !important;
            }
            .stat-mini .stat-label {
                color: #6c757d !important;
            }
            .table-custom {
                background: #ffffff !important;
            }
            .table-custom thead th {
                background: #f8f9fa !important;
                color: #495057 !important;
                border-bottom-color: #eef1f5 !important;
            }
            .table-custom tbody td {
                color: #000000 !important;
                border-bottom-color: #f0f4f8 !important;
            }
            .table-custom tbody tr:hover {
                background: #f8f9fa !important;
            }
            .page-header {
                border-bottom-color: #eef1f5 !important;
            }
            .page-header h1 {
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
            .badge-role.verified { background: #e8f5e9 !important; color: #198754 !important; }
            .badge-role.pending { background: #fff3e0 !important; color: #f39c12 !important; }
            .badge-role.active { background: #e8f5e9 !important; color: #198754 !important; }
            .badge-role.inactive { background: #fce4ec !important; color: #dc3545 !important; }
            .status-badge.pending { background: #fff3e0 !important; color: #f39c12 !important; }
            .status-badge.approved { background: #e8f5e9 !important; color: #198754 !important; }
            .status-badge.rejected { background: #fce4ec !important; color: #dc3545 !important; }
            .status-badge.completed { background: #e0f7fa !important; color: #0dcaf0 !important; }
            .status-badge.expired { background: #e9ecef !important; color: #6c757d !important; }
            .type-badge.book { background: #e8f0fe !important; color: #0d6efd !important; }
            .type-badge.journal { background: #e8f5e9 !important; color: #198754 !important; }
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
                        <i class="fas fa-user-md"></i> Doctor Details
                    </h1>
                    <a href="manage_doctors.php" class="btn btn-secondary btn-back">
                        <i class="fas fa-arrow-left"></i> Back to Doctors
                    </a>
                </div>
                
                <!-- Profile Grid -->
                <div class="profile-grid">
                    
                    <!-- ============================================
                    LEFT COLUMN - PROFILE INFO
                    ============================================ -->
                    <div>
                        <!-- Profile Avatar -->
                        <div class="profile-card">
                            <div class="card-body profile-avatar-wrapper">
                                <img src="../uploads/profiles/<?php echo $doctor['profile_image'] ?? 'default.jpg'; ?>" 
                                     alt="Profile" class="profile-avatar">
                                <div class="profile-name"><?php echo htmlspecialchars($doctor['name']); ?></div>
                                <div class="profile-email"><?php echo htmlspecialchars($doctor['email']); ?></div>
                                <div>
                                    <span class="badge-role <?php echo $doctor['is_verified'] ? 'verified' : 'pending'; ?>">
                                        <?php echo $doctor['is_verified'] ? 'Verified' : 'Pending Verification'; ?>
                                    </span>
                                    <span class="badge-role <?php echo $doctor['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $doctor['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Account Information -->
                        <div class="profile-card" style="margin-top: 14px;">
                            <div class="card-header"><i class="fas fa-clock"></i> Account Information</div>
                            <div class="card-body">
                                <div class="info-item">
                                    <span class="info-label">Member Since</span>
                                    <span class="info-value"><?php echo date('F d, Y', strtotime($doctor['created_at'])); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Last Updated</span>
                                    <span class="info-value"><?php echo date('F d, Y', strtotime($doctor['updated_at'] ?? $doctor['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Stats -->
                        <div class="profile-card" style="margin-top: 14px;">
                            <div class="card-header"><i class="fas fa-chart-bar"></i> Activity Stats</div>
                            <div class="card-body">
                                <div class="stats-mini-grid">
                                    <div class="stat-mini">
                                        <div class="stat-number"><?php echo $stats['total_requests']; ?></div>
                                        <div class="stat-label">Total Requests</div>
                                    </div>
                                    <div class="stat-mini">
                                        <div class="stat-number"><?php echo $stats['pending_requests']; ?></div>
                                        <div class="stat-label">Pending</div>
                                    </div>
                                    <div class="stat-mini">
                                        <div class="stat-number"><?php echo $stats['total_downloads']; ?></div>
                                        <div class="stat-label">Download Permissions</div>
                                    </div>
                                    <div class="stat-mini">
                                        <div class="stat-number"><?php echo $stats['pending_downloads']; ?></div>
                                        <div class="stat-label">Pending Downloads</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ============================================
                    RIGHT COLUMN - DETAILS & HISTORY
                    ============================================ -->
                    <div>
                        <!-- Personal Information -->
                        <div class="profile-card">
                            <div class="card-header"><i class="fas fa-user"></i> Personal Information</div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <span class="info-label">Name</span>
                                            <span class="info-value"><?php echo htmlspecialchars($doctor['name']); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Email</span>
                                            <span class="info-value"><?php echo htmlspecialchars($doctor['email']); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Mobile</span>
                                            <span class="info-value"><?php echo htmlspecialchars($doctor['mobile'] ?? 'N/A'); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <span class="info-label">Specialty</span>
                                            <span class="info-value"><?php echo htmlspecialchars($doctor['specialty'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">BMDC Reg. No.</span>
                                            <span class="info-value"><?php echo htmlspecialchars($doctor['bmdc_reg_no'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Hospital/Institute</span>
                                            <span class="info-value"><?php echo htmlspecialchars($doctor['hospital_institute'] ?? 'N/A'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Request History -->
                        <div class="profile-card" style="margin-top: 14px;">
                            <div class="card-header"><i class="fas fa-truck"></i> Supply Request History</div>
                            <div class="card-body">
                                <!-- Request Filter -->
                                <div class="filter-section">
                                    <span class="filter-label"><i class="fas fa-filter"></i> Filter:</span>
                                    <div class="filter-group">
                                        <form method="GET" action="" class="d-flex flex-wrap gap-2" style="flex: 1;">
                                            <input type="hidden" name="id" value="<?php echo $doctorId; ?>">
                                            <input type="text" class="form-control" name="request_search" placeholder="Search requests..." value="<?php echo htmlspecialchars($requestSearch); ?>" style="flex: 2; min-width: 120px;">
                                            <select class="form-select" name="request_status" style="flex: 1; min-width: 100px;">
                                                <option value="">All Status</option>
                                                <option value="pending" <?php echo $requestStatusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="approved" <?php echo $requestStatusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                <option value="rejected" <?php echo $requestStatusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                <option value="completed" <?php echo $requestStatusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            </select>
                                            <select class="form-select" name="request_type" style="flex: 1; min-width: 100px;">
                                                <option value="">All Types</option>
                                                <option value="book" <?php echo $requestTypeFilter === 'book' ? 'selected' : ''; ?>>Book</option>
                                                <option value="journal" <?php echo $requestTypeFilter === 'journal' ? 'selected' : ''; ?>>Journal</option>
                                            </select>
                                            <button type="submit" class="btn btn-primary btn-filter">
                                                <i class="fas fa-search"></i>
                                            </button>
                                            <?php if (!empty($requestSearch) || !empty($requestStatusFilter) || !empty($requestTypeFilter)): ?>
                                            <a href="?id=<?php echo $doctorId; ?>" class="btn-clear">
                                                <i class="fas fa-times"></i> Clear
                                            </a>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </div>
                                
                                <?php if (empty($requests)): ?>
                                <p class="text-muted" style="font-family: 'Cambria', Georgia, serif; font-size: 0.9rem; margin: 0;">No requests found <?php echo (!empty($requestSearch) || !empty($requestStatusFilter) || !empty($requestTypeFilter)) ? 'matching your filters' : 'made by this doctor.'; ?></p>
                                <?php else: ?>
                                <div class="table-responsive-custom">
                                    <table class="table-custom">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($requests as $request): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($request['book_title'] ?? $request['journal_title'] ?? 'N/A'); ?></td>
                                                <td><span class="type-badge <?php echo $request['request_type']; ?>"><?php echo ucfirst($request['request_type']); ?></span></td>
                                                <td><span class="status-badge <?php echo $request['status']; ?>"><?php echo ucfirst($request['status']); ?></span></td>
                                                <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if ($totalRequestPages > 1): ?>
                                <div class="pagination-wrapper">
                                    <div class="info-text">
                                        Showing <strong><?php echo $requestOffset + 1; ?></strong> to 
                                        <strong><?php echo min($requestOffset + $perPage, $totalRequests); ?></strong> 
                                        of <strong><?php echo $totalRequests; ?></strong>
                                    </div>
                                    <nav>
                                        <ul class="pagination">
                                            <li class="page-item <?php echo $requestPage <= 1 ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?id=<?php echo $doctorId; ?>&request_page=<?php echo $requestPage - 1; ?><?php echo !empty($requestSearch) ? '&request_search=' . urlencode($requestSearch) : ''; ?><?php echo !empty($requestStatusFilter) ? '&request_status=' . urlencode($requestStatusFilter) : ''; ?><?php echo !empty($requestTypeFilter) ? '&request_type=' . urlencode($requestTypeFilter) : ''; ?><?php echo !empty($downloadSearch) ? '&download_search=' . urlencode($downloadSearch) : ''; ?><?php echo !empty($downloadStatusFilter) ? '&download_status=' . urlencode($downloadStatusFilter) : ''; ?><?php echo !empty($downloadTypeFilter) ? '&download_type=' . urlencode($downloadTypeFilter) : ''; ?><?php echo $downloadPage > 1 ? '&download_page=' . $downloadPage : ''; ?>" aria-label="Previous">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                            <?php for ($i = 1; $i <= $totalRequestPages; $i++): ?>
                                            <li class="page-item <?php echo $i == $requestPage ? 'active' : ''; ?>">
                                                <a class="page-link" href="?id=<?php echo $doctorId; ?>&request_page=<?php echo $i; ?><?php echo !empty($requestSearch) ? '&request_search=' . urlencode($requestSearch) : ''; ?><?php echo !empty($requestStatusFilter) ? '&request_status=' . urlencode($requestStatusFilter) : ''; ?><?php echo !empty($requestTypeFilter) ? '&request_type=' . urlencode($requestTypeFilter) : ''; ?><?php echo !empty($downloadSearch) ? '&download_search=' . urlencode($downloadSearch) : ''; ?><?php echo !empty($downloadStatusFilter) ? '&download_status=' . urlencode($downloadStatusFilter) : ''; ?><?php echo !empty($downloadTypeFilter) ? '&download_type=' . urlencode($downloadTypeFilter) : ''; ?><?php echo $downloadPage > 1 ? '&download_page=' . $downloadPage : ''; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                            <?php endfor; ?>
                                            <li class="page-item <?php echo $requestPage >= $totalRequestPages ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?id=<?php echo $doctorId; ?>&request_page=<?php echo $requestPage + 1; ?><?php echo !empty($requestSearch) ? '&request_search=' . urlencode($requestSearch) : ''; ?><?php echo !empty($requestStatusFilter) ? '&request_status=' . urlencode($requestStatusFilter) : ''; ?><?php echo !empty($requestTypeFilter) ? '&request_type=' . urlencode($requestTypeFilter) : ''; ?><?php echo !empty($downloadSearch) ? '&download_search=' . urlencode($downloadSearch) : ''; ?><?php echo !empty($downloadStatusFilter) ? '&download_status=' . urlencode($downloadStatusFilter) : ''; ?><?php echo !empty($downloadTypeFilter) ? '&download_type=' . urlencode($downloadTypeFilter) : ''; ?><?php echo $downloadPage > 1 ? '&download_page=' . $downloadPage : ''; ?>" aria-label="Next">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Download Permissions -->
                        <div class="profile-card" style="margin-top: 14px;">
                            <div class="card-header"><i class="fas fa-download"></i> Download Permissions</div>
                            <div class="card-body">
                                <!-- Download Filter -->
                                <div class="filter-section">
                                    <span class="filter-label"><i class="fas fa-filter"></i> Filter:</span>
                                    <div class="filter-group">
                                        <form method="GET" action="" class="d-flex flex-wrap gap-2" style="flex: 1;">
                                            <input type="hidden" name="id" value="<?php echo $doctorId; ?>">
                                            <input type="text" class="form-control" name="download_search" placeholder="Search downloads..." value="<?php echo htmlspecialchars($downloadSearch); ?>" style="flex: 2; min-width: 120px;">
                                            <select class="form-select" name="download_status" style="flex: 1; min-width: 100px;">
                                                <option value="">All Status</option>
                                                <option value="pending" <?php echo $downloadStatusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="approved" <?php echo $downloadStatusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                <option value="rejected" <?php echo $downloadStatusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                <option value="expired" <?php echo $downloadStatusFilter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                                            </select>
                                            <select class="form-select" name="download_type" style="flex: 1; min-width: 100px;">
                                                <option value="">All Types</option>
                                                <option value="book" <?php echo $downloadTypeFilter === 'book' ? 'selected' : ''; ?>>Book</option>
                                                <option value="journal" <?php echo $downloadTypeFilter === 'journal' ? 'selected' : ''; ?>>Journal</option>
                                            </select>
                                            <button type="submit" class="btn btn-primary btn-filter">
                                                <i class="fas fa-search"></i>
                                            </button>
                                            <?php if (!empty($downloadSearch) || !empty($downloadStatusFilter) || !empty($downloadTypeFilter)): ?>
                                            <a href="?id=<?php echo $doctorId; ?>" class="btn-clear">
                                                <i class="fas fa-times"></i> Clear
                                            </a>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </div>
                                
                                <?php if (empty($downloads)): ?>
                                <p class="text-muted" style="font-family: 'Cambria', Georgia, serif; font-size: 0.9rem; margin: 0;">No download permissions found <?php echo (!empty($downloadSearch) || !empty($downloadStatusFilter) || !empty($downloadTypeFilter)) ? 'matching your filters' : 'requested by this doctor.'; ?></p>
                                <?php else: ?>
                                <div class="table-responsive-custom">
                                    <table class="table-custom">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($downloads as $download): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($download['item_title'] ?? 'N/A'); ?></td>
                                                <td><span class="type-badge <?php echo $download['item_type']; ?>"><?php echo ucfirst($download['item_type']); ?></span></td>
                                                <td><span class="status-badge <?php echo $download['status']; ?>"><?php echo ucfirst($download['status']); ?></span></td>
                                                <td><?php echo date('M d, Y', strtotime($download['requested_at'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if ($totalDownloadPages > 1): ?>
                                <div class="pagination-wrapper">
                                    <div class="info-text">
                                        Showing <strong><?php echo $downloadOffset + 1; ?></strong> to 
                                        <strong><?php echo min($downloadOffset + $perPage, $totalDownloads); ?></strong> 
                                        of <strong><?php echo $totalDownloads; ?></strong>
                                    </div>
                                    <nav>
                                        <ul class="pagination">
                                            <li class="page-item <?php echo $downloadPage <= 1 ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?id=<?php echo $doctorId; ?>&download_page=<?php echo $downloadPage - 1; ?><?php echo !empty($requestSearch) ? '&request_search=' . urlencode($requestSearch) : ''; ?><?php echo !empty($requestStatusFilter) ? '&request_status=' . urlencode($requestStatusFilter) : ''; ?><?php echo !empty($requestTypeFilter) ? '&request_type=' . urlencode($requestTypeFilter) : ''; ?><?php echo !empty($downloadSearch) ? '&download_search=' . urlencode($downloadSearch) : ''; ?><?php echo !empty($downloadStatusFilter) ? '&download_status=' . urlencode($downloadStatusFilter) : ''; ?><?php echo !empty($downloadTypeFilter) ? '&download_type=' . urlencode($downloadTypeFilter) : ''; ?><?php echo $requestPage > 1 ? '&request_page=' . $requestPage : ''; ?>" aria-label="Previous">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                            <?php for ($i = 1; $i <= $totalDownloadPages; $i++): ?>
                                            <li class="page-item <?php echo $i == $downloadPage ? 'active' : ''; ?>">
                                                <a class="page-link" href="?id=<?php echo $doctorId; ?>&download_page=<?php echo $i; ?><?php echo !empty($requestSearch) ? '&request_search=' . urlencode($requestSearch) : ''; ?><?php echo !empty($requestStatusFilter) ? '&request_status=' . urlencode($requestStatusFilter) : ''; ?><?php echo !empty($requestTypeFilter) ? '&request_type=' . urlencode($requestTypeFilter) : ''; ?><?php echo !empty($downloadSearch) ? '&download_search=' . urlencode($downloadSearch) : ''; ?><?php echo !empty($downloadStatusFilter) ? '&download_status=' . urlencode($downloadStatusFilter) : ''; ?><?php echo !empty($downloadTypeFilter) ? '&download_type=' . urlencode($downloadTypeFilter) : ''; ?><?php echo $requestPage > 1 ? '&request_page=' . $requestPage : ''; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                            <?php endfor; ?>
                                            <li class="page-item <?php echo $downloadPage >= $totalDownloadPages ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?id=<?php echo $doctorId; ?>&download_page=<?php echo $downloadPage + 1; ?><?php echo !empty($requestSearch) ? '&request_search=' . urlencode($requestSearch) : ''; ?><?php echo !empty($requestStatusFilter) ? '&request_status=' . urlencode($requestStatusFilter) : ''; ?><?php echo !empty($requestTypeFilter) ? '&request_type=' . urlencode($requestTypeFilter) : ''; ?><?php echo !empty($downloadSearch) ? '&download_search=' . urlencode($downloadSearch) : ''; ?><?php echo !empty($downloadStatusFilter) ? '&download_status=' . urlencode($downloadStatusFilter) : ''; ?><?php echo !empty($downloadTypeFilter) ? '&download_type=' . urlencode($downloadTypeFilter) : ''; ?><?php echo $requestPage > 1 ? '&request_page=' . $requestPage : ''; ?>" aria-label="Next">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
    </script>
</body>
</html>