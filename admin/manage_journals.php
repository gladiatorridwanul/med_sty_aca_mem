<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireAdmin();

$db = Database::getInstance()->getConnection();

// ============================================
// Handle journal addition
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_journal'])) {
    $title = sanitize($_POST['title'] ?? '');
    $journal_name = sanitize($_POST['journal_name'] ?? '');
    $specialty_id = intval($_POST['specialty_id'] ?? 0);
    $issue = sanitize($_POST['issue'] ?? '');
    $date = !empty($_POST['date']) ? $_POST['date'] : null;
    $volume = sanitize($_POST['volume'] ?? '');
    $pages = sanitize($_POST['pages'] ?? '');
    $doi = sanitize($_POST['doi'] ?? '');
    $abstract = sanitize($_POST['abstract'] ?? '');
    $is_watermarked = isset($_POST['is_watermarked']) ? 1 : 0;
    $status = sanitize($_POST['status'] ?? 'pending');

    if ($title && $journal_name && $specialty_id) {
        // Handle journal file upload
        $filePath = '';
        if (isset($_FILES['journal_file']) && $_FILES['journal_file']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['journal_file'], JOURNAL_UPLOAD_PATH, ['pdf']);
            if ($uploadResult['success']) {
                $filePath = $uploadResult['filename'];
            } else {
                $error = 'Journal file upload failed: ' . $uploadResult['error'];
            }
        }

        // ✅ Handle cover image upload
        $coverImage = null;
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $coverResult = uploadFile($_FILES['cover_image'], JOURNAL_UPLOAD_PATH, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
            if ($coverResult['success']) {
                $coverImage = $coverResult['filename'];
            } else {
                $error = 'Cover image upload failed: ' . $coverResult['error'];
            }
        }

        if ($filePath) {
            $uploaded_by = $_SESSION['user_id'];

            $data = [
                'title' => $title,
                'journal_name' => $journal_name,
                'specialty_id' => $specialty_id,
                'issue' => $issue,
                'date' => $date,
                'volume' => $volume,
                'pages' => $pages,
                'doi' => $doi,
                'abstract' => $abstract,
                'file_path' => $filePath,
                'cover_image' => $coverImage,
                'is_watermarked' => $is_watermarked,
                'status' => $status,
                'uploaded_by' => $uploaded_by
            ];

            $columns = array_keys($data);
            $placeholders = array_fill(0, count($columns), '?');
            $sql = "INSERT INTO journals (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

            $stmt = $db->prepare($sql);

            $types = '';
            $values = [];
            foreach ($data as $key => $value) {
                if (is_int($value)) {
                    $types .= 'i';
                } else {
                    $types .= 's';
                }
                $values[] = $value;
            }

            $stmt->bind_param($types, ...$values);

            if ($stmt->execute()) {
                logActivity($_SESSION['user_id'], 'Added Journal', "Added journal: $title");
                $success = "Journal added successfully.";
                $_POST = [];
            } else {
                $error = "Failed to add journal: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Journal file is required.";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// ============================================
// Handle journal deletion (cleanup both files)
// ============================================
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $journalId = intval($_GET['id']);
    $stmt = $db->prepare("SELECT file_path, cover_image FROM journals WHERE id = ?");
    $stmt->bind_param("i", $journalId);
    $stmt->execute();
    $result = $stmt->get_result();
    $journal = $result->fetch_assoc();
    $stmt->close();

    if ($journal) {
        // Delete PDF
        if (!empty($journal['file_path']) && file_exists(JOURNAL_UPLOAD_PATH . $journal['file_path'])) {
            @unlink(JOURNAL_UPLOAD_PATH . $journal['file_path']);
        }
        // ✅ Delete cover image
        if (!empty($journal['cover_image']) && file_exists(JOURNAL_UPLOAD_PATH . $journal['cover_image'])) {
            @unlink(JOURNAL_UPLOAD_PATH . $journal['cover_image']);
        }
    }

    $stmt = $db->prepare("DELETE FROM journals WHERE id = ?");
    $stmt->bind_param("i", $journalId);
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], 'Deleted Journal', "Deleted journal ID: $journalId");
        $success = "Journal deleted successfully.";
    } else {
        $error = "Failed to delete journal.";
    }
    $stmt->close();
}

// ============================================
// Handle status update
// ============================================
if (isset($_POST['update_status']) && isset($_POST['journal_id'])) {
    $journalId = intval($_POST['journal_id']);
    $status = sanitize($_POST['status'] ?? 'pending');

    $stmt = $db->prepare("UPDATE journals SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $status, $journalId);
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], 'Updated Journal Status', "Journal ID: $journalId, Status: $status");
        $success = "Journal status updated successfully.";
    } else {
        $error = "Failed to update journal status.";
    }
    $stmt->close();
}

// ============================================
// Get search and filter parameters
// ============================================
$searchQuery = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$specialtyFilter = isset($_GET['specialty']) ? intval($_GET['specialty']) : 0;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build WHERE clause
$whereConditions = [];
$params = [];
$types = "";

if (!empty($searchQuery)) {
    $searchTerm = '%' . $searchQuery . '%';
    $whereConditions[] = "(j.title LIKE ? OR j.journal_name LIKE ? OR j.abstract LIKE ? OR j.doi LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ssss";
}

if (!empty($statusFilter)) {
    $whereConditions[] = "j.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

if ($specialtyFilter > 0) {
    $whereConditions[] = "j.specialty_id = ?";
    $params[] = $specialtyFilter;
    $types .= "i";
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Total count for pagination
$countSql = "SELECT COUNT(*) as total FROM journals j $whereClause";
$countStmt = $db->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalResult = $countStmt->get_result();
$totalJournals = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalJournals / $perPage);
$countStmt->close();

// Get journals with pagination
$sql = "SELECT j.*, s.name as specialty_name, u.name as uploaded_by_name 
        FROM journals j 
        LEFT JOIN specialties s ON j.specialty_id = s.id 
        LEFT JOIN users u ON j.uploaded_by = u.id 
        $whereClause
        ORDER BY j.created_at DESC 
        LIMIT ? OFFSET ?";

$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$journals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get all specialties
$specialties = getAllSpecialties();

// Status counts
$statusCounts = [];
$statusQuery = $db->query("SELECT status, COUNT(*) as count FROM journals GROUP BY status");
while ($row = $statusQuery->fetch_assoc()) {
    $statusCounts[$row['status']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Journals - BJDVL</title>
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

        .main-content {
            margin-left: 250px;
            padding: 20px 24px 24px;
            min-height: 100vh;
            transition: all 0.3s ease;
            max-width: calc(100% - 250px);
            overflow-x: hidden;
            background: #ffffff;
        }

        /* Page Header */
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
        }
        .page-header h1 i {
            color: #198754;
            margin-right: 10px;
        }
        .page-header .btn-add {
            font-weight: 600;
            padding: 8px 22px;
            border-radius: 6px;
            font-size: 0.95rem;
        }

        /* Filter Section */
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
            font-size: 0.9rem;
            padding: 6px 12px;
            border-radius: 6px;
            border: 1.5px solid #eef1f5;
            background: #ffffff;
            color: #000000;
            min-width: 160px;
        }
        .filter-section .filter-group .form-control:focus,
        .filter-section .filter-group .form-select:focus {
            border-color: #198754;
            box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.08);
            outline: none;
        }
        .filter-section .filter-group .btn-filter {
            font-weight: 600;
            font-size: 0.85rem;
            padding: 6px 18px;
            border-radius: 6px;
        }
        .filter-section .filter-group .btn-clear {
            font-size: 0.85rem;
            padding: 6px 14px;
            border-radius: 6px;
            color: #6c757d;
            text-decoration: none;
        }
        .filter-section .filter-group .btn-clear:hover { color: #dc3545; }

        .filter-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 6px;
        }
        .filter-badges .badge {
            font-size: 0.75rem;
            padding: 4px 12px;
            border-radius: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1.5px solid transparent;
        }
        .filter-badges .badge:hover { transform: translateY(-1px); }
        .filter-badges .badge-all { background: #e8f5e9; color: #198754; border-color: #198754; }
        .filter-badges .badge-all.active { background: #198754; color: #fff; }
        .filter-badges .badge-pending { background: #fff3e0; color: #f39c12; border-color: #f39c12; }
        .filter-badges .badge-pending.active { background: #f39c12; color: #fff; }
        .filter-badges .badge-approved { background: #e8f5e9; color: #198754; border-color: #198754; }
        .filter-badges .badge-approved.active { background: #198754; color: #fff; }
        .filter-badges .badge-rejected { background: #fce4ec; color: #dc3545; border-color: #dc3545; }
        .filter-badges .badge-rejected.active { background: #dc3545; color: #fff; }

        /* Table Card */
        .table-card {
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #eef1f5;
            overflow: hidden;
        }
        .table-card .table-body {
            padding: 0;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .table-card .table-body table {
            margin: 0;
            font-size: 0.9rem;
            width: 100%;
            min-width: 950px;
            background: #ffffff;
        }
        .table-card .table-body table thead th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            border-bottom: 2px solid #eef1f5;
            padding: 10px 14px;
            white-space: nowrap;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .table-card .table-body table tbody td {
            padding: 10px 14px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f4f8;
            color: #000000;
            font-size: 0.9rem;
        }
        .table-card .table-body table tbody tr:hover { background: #f8f9fa; }
        .table-card .table-body table tbody tr:last-child td { border-bottom: none; }

        /* ============================================
           JOURNAL COVER (with fallback icon)
           ============================================ */
        .journal-cover-wrapper {
            width: 55px;
            height: 72px;
            border-radius: 5px;
            overflow: hidden;
            background: #e8f5e9;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #eef1f5;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            position: relative;
        }
        .journal-cover-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .journal-cover-wrapper .cover-fallback {
            color: #198754;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #e8f5e9, #d1fae5);
        }

        /* Status badges */
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
        }
        .status-badge.approved { background: #e8f5e9; color: #198754; }
        .status-badge.pending { background: #fff3e0; color: #f39c12; }
        .status-badge.rejected { background: #fce4ec; color: #dc3545; }

        /* Action buttons */
        .action-btn {
            width: 30px;
            height: 30px;
            border-radius: 4px;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
        }
        .action-btn:hover { transform: translateY(-1px); }
        .action-btn.edit { background: #e8f0fe; color: #0d6efd; }
        .action-btn.edit:hover { background: #0d6efd; color: #fff; }
        .action-btn.delete { background: #fce4ec; color: #dc3545; }
        .action-btn.delete:hover { background: #dc3545; color: #fff; }

        .action-group {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            align-items: center;
        }

        .status-select {
            font-size: 0.78rem;
            padding: 3px 8px;
            border-radius: 4px;
            border: 1.5px solid #eef1f5;
            background: #ffffff;
            color: #000000;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .status-select:focus { border-color: #198754; outline: none; }

        /* Pagination */
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
            font-size: 0.85rem;
            color: #6c757d;
        }
        .pagination-wrapper .info-text strong { color: #000000; }
        .pagination-wrapper .pagination { margin: 0; gap: 3px; }
        .pagination-wrapper .pagination .page-link {
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
            border-color: #198754;
            color: #198754;
        }
        .pagination-wrapper .pagination .page-item.active .page-link {
            background: #198754;
            border-color: #198754;
            color: #ffffff;
        }
        .pagination-wrapper .pagination .page-item.disabled .page-link {
            color: #adb5bd;
            pointer-events: none;
            background: #f8f9fa;
        }

        /* Modals */
        .modal-content { border-radius: 10px; border: 1px solid #eef1f5; background: #ffffff; }
        .modal-header { border-bottom: 1px solid #eef1f5; padding: 16px 22px; }
        .modal-header .modal-title { font-weight: 700; font-size: 1.2rem; color: #000000; }
        .modal-body { padding: 22px; }
        .modal-footer { border-top: 1px solid #eef1f5; padding: 14px 22px; }
        .modal-footer .btn { font-weight: 600; font-size: 0.9rem; padding: 8px 22px; border-radius: 6px; }

        .form-label { font-weight: 600; color: #000000; font-size: 0.9rem; }
        .form-control, .form-select {
            border-radius: 6px;
            border: 1.5px solid #eef1f5;
            font-size: 0.95rem;
            color: #000000;
            background: #ffffff;
            padding: 8px 14px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #198754;
            box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.08);
        }
        .form-check-label { color: #000000; font-size: 0.9rem; }

        /* Cover upload preview */
        .cover-upload-group {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        .cover-upload-preview {
            width: 80px;
            height: 104px;
            border-radius: 6px;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
            color: #adb5bd;
            font-size: 1.5rem;
        }
        .cover-upload-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .cover-upload-fields {
            flex: 1;
        }
        .cover-upload-hint {
            font-size: 0.72rem;
            color: #6c757d;
            margin-top: 4px;
            line-height: 1.3;
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0 !important;
                max-width: 100% !important;
                padding: 14px 16px 18px;
            }
            .page-header h1 { font-size: 1.3rem; }
            .page-header .btn-add { font-size: 0.85rem; padding: 6px 16px; }
            .table-card .table-body table { min-width: 800px; font-size: 0.85rem; }
            .filter-section {
                padding: 12px 14px;
                flex-direction: column;
                align-items: stretch;
            }
            .filter-section .filter-group .form-control,
            .filter-section .filter-group .form-select {
                min-width: 120px;
                flex: 1;
            }
        }

        @media (max-width: 575.98px) {
            .main-content { padding: 10px 8px 14px; }
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                padding-bottom: 10px;
                margin-bottom: 12px;
            }
            .page-header h1 { font-size: 1.15rem; }
            .page-header .btn-add { font-size: 0.8rem; padding: 6px 14px; width: 100%; }
            .table-card .table-body table { min-width: 600px; font-size: 0.8rem; }
            .table-card .table-body table thead th { padding: 6px 10px; font-size: 0.68rem; }
            .table-card .table-body table tbody td { padding: 6px 10px; font-size: 0.8rem; }
            .journal-cover-wrapper { width: 40px; height: 52px; }
            .journal-cover-wrapper .cover-fallback { font-size: 1rem; }
            .status-badge { font-size: 0.6rem; padding: 3px 8px; }
            .action-btn { width: 26px; height: 26px; font-size: 0.7rem; }
            .status-select { font-size: 0.7rem; padding: 2px 6px; }
            .filter-section { padding: 10px 12px; flex-direction: column; align-items: stretch; }
            .filter-section .filter-label { font-size: 0.8rem; }
            .filter-section .filter-group { flex-direction: column; gap: 6px; }
            .filter-section .filter-group .form-control,
            .filter-section .filter-group .form-select {
                min-width: 100%;
                font-size: 0.85rem;
                padding: 5px 10px;
            }
            .filter-section .filter-group .btn-filter { font-size: 0.8rem; padding: 5px 14px; width: 100%; }
            .filter-section .filter-group .btn-clear { font-size: 0.8rem; padding: 5px 12px; text-align: center; width: 100%; }
            .filter-badges .badge { font-size: 0.65rem; padding: 3px 10px; }
            .pagination-wrapper { flex-direction: column; align-items: center; padding: 12px 14px; }
            .pagination-wrapper .info-text { font-size: 0.8rem; text-align: center; }
            .pagination-wrapper .pagination .page-link { font-size: 0.78rem; padding: 4px 10px; }
            .modal-body { padding: 14px; }
            .form-control, .form-select { font-size: 0.9rem; padding: 6px 10px; }
            .modal-header .modal-title { font-size: 1.05rem; }
            .modal-footer .btn { font-size: 0.82rem; padding: 6px 14px; }
            .cover-upload-preview { width: 60px; height: 80px; }
        }

        @media (max-width: 400px) {
            .main-content { padding: 8px 4px 10px; }
            .table-card .table-body table { min-width: 500px; font-size: 0.75rem; }
            .table-card .table-body table thead th { padding: 4px 8px; font-size: 0.62rem; }
            .table-card .table-body table tbody td { padding: 4px 8px; font-size: 0.75rem; }
            .action-btn { width: 22px; height: 22px; font-size: 0.6rem; }
            .action-group { gap: 3px; }
            .page-header h1 { font-size: 1rem; }
            .journal-cover-wrapper { width: 32px; height: 42px; }
            .journal-cover-wrapper .cover-fallback { font-size: 0.8rem; }
        }

        /* Dark mode - keep white */
        @media (prefers-color-scheme: dark) {
            body, .main-content { background: #ffffff !important; }
            .filter-section, .table-card, .pagination-wrapper, .modal-content { background: #f8f9fa !important; border-color: #eef1f5 !important; }
            .table-card .table-body table { background: #ffffff !important; }
            .table-card .table-body table thead th { background: #f8f9fa !important; color: #495057 !important; border-bottom-color: #eef1f5 !important; }
            .table-card .table-body table tbody td { color: #000000 !important; border-bottom-color: #f0f4f8 !important; }
            .table-card .table-body table tbody tr:hover { background: #f8f9fa !important; }
            .form-control, .form-select { background: #ffffff !important; border-color: #eef1f5 !important; color: #000000 !important; }
            .form-label, .form-check-label { color: #000000 !important; }
            .journal-cover-wrapper { background: #e8f5e9 !important; border-color: #eef1f5 !important; }
            .journal-cover-wrapper .cover-fallback { color: #198754 !important; background: linear-gradient(135deg, #e8f5e9, #d1fae5) !important; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

            <main class="main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <h1>
                        <i class="fas fa-newspaper"></i> Manage Journals
                        <span style="font-size: 0.8rem; font-weight: 400; color: #6c757d; margin-left: 8px;">
                            (<?php echo $totalJournals; ?> total)
                        </span>
                    </h1>
                    <button class="btn btn-success btn-add" data-bs-toggle="modal" data-bs-target="#addJournalModal">
                        <i class="fas fa-plus"></i> Add New Journal
                    </button>
                </div>

                <!-- Alerts -->
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" style="border-radius: 6px; font-size: 0.9rem;">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" style="border-radius: 6px; font-size: 0.9rem;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filter & Search -->
                <div class="filter-section">
                    <span class="filter-label"><i class="fas fa-filter"></i> Filter:</span>
                    <div class="filter-group">
                        <form method="GET" action="" class="d-flex flex-wrap gap-2" style="flex: 1;">
                            <input type="text" class="form-control" name="search" placeholder="Search by title, journal name, doi..." value="<?php echo htmlspecialchars($searchQuery); ?>" style="flex: 2; min-width: 180px;">
                            <select class="form-select" name="status" style="flex: 1; min-width: 130px;">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                            <select class="form-select" name="specialty" style="flex: 1; min-width: 150px;">
                                <option value="">All Specialties</option>
                                <?php foreach ($specialties as $specialty): ?>
                                <option value="<?php echo $specialty['id']; ?>" <?php echo $specialtyFilter == $specialty['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($specialty['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-success btn-filter">
                                <i class="fas fa-search"></i> Apply
                            </button>
                            <?php if (!empty($searchQuery) || !empty($statusFilter) || $specialtyFilter > 0): ?>
                            <a href="manage_journals.php" class="btn-clear">
                                <i class="fas fa-times"></i> Clear
                            </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Status Filter Badges -->
                <div class="filter-badges">
                    <a href="manage_journals.php" class="badge badge-all <?php echo empty($statusFilter) ? 'active' : ''; ?>">
                        All (<?php echo array_sum($statusCounts); ?>)
                    </a>
                    <a href="?status=pending<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $specialtyFilter > 0 ? '&specialty=' . $specialtyFilter : ''; ?>"
                       class="badge badge-pending <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
                        Pending (<?php echo $statusCounts['pending'] ?? 0; ?>)
                    </a>
                    <a href="?status=approved<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $specialtyFilter > 0 ? '&specialty=' . $specialtyFilter : ''; ?>"
                       class="badge badge-approved <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>">
                        Approved (<?php echo $statusCounts['approved'] ?? 0; ?>)
                    </a>
                    <a href="?status=rejected<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $specialtyFilter > 0 ? '&specialty=' . $specialtyFilter : ''; ?>"
                       class="badge badge-rejected <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>">
                        Rejected (<?php echo $statusCounts['rejected'] ?? 0; ?>)
                    </a>
                </div>

                <!-- Table Card -->
                <div class="table-card">
                    <div class="table-body">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 50px;">ID</th>
                                    <th style="width: 80px;">Cover</th>
                                    <th>Title</th>
                                    <th>Journal</th>
                                    <th>Specialty</th>
                                    <th style="width: 100px;">Status</th>
                                    <th style="width: 240px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($journals)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4" style="color: #6c757d; font-size: 0.95rem;">
                                        <i class="fas fa-inbox fa-3x d-block mb-3" style="color: #dee2e6;"></i>
                                        <?php if (!empty($searchQuery) || !empty($statusFilter) || $specialtyFilter > 0): ?>
                                            No journals match your filter criteria.
                                        <?php else: ?>
                                            No journals found. Click "Add New Journal" to get started.
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($journals as $journal): ?>
                                <tr>
                                    <td><strong><?php echo $journal['id']; ?></strong></td>
                                    <td>
                                        <div class="journal-cover-wrapper">
                                            <?php if (!empty($journal['cover_image']) && file_exists(JOURNAL_UPLOAD_PATH . $journal['cover_image'])): ?>
                                                <img src="/uploads/journals/<?php echo htmlspecialchars($journal['cover_image']); ?>"
                                                     alt="<?php echo htmlspecialchars($journal['title']); ?>"
                                                     loading="lazy">
                                            <?php else: ?>
                                                <div class="cover-fallback">
                                                    <i class="fas fa-file-alt"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($journal['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($journal['journal_name']); ?></td>
                                    <td><?php echo htmlspecialchars($journal['specialty_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $journal['status']; ?>">
                                            <?php echo ucfirst($journal['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="journal_id" value="<?php echo $journal['id']; ?>">
                                                <select name="status" class="status-select" onchange="this.form.submit()">
                                                    <option value="pending" <?php echo $journal['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="approved" <?php echo $journal['status'] == 'approved' ? 'selected' : ''; ?>>Approve</option>
                                                    <option value="rejected" <?php echo $journal['status'] == 'rejected' ? 'selected' : ''; ?>>Reject</option>
                                                </select>
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
                                            <a href="/admin/edit_journal.php?id=<?php echo $journal['id']; ?>" class="action-btn edit" title="Edit Journal">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete=1&id=<?php echo $journal['id']; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo $specialtyFilter > 0 ? '&specialty=' . $specialtyFilter : ''; ?><?php echo $page > 1 ? '&page=' . $page : ''; ?>"
                                               class="action-btn delete" onclick="return confirm('Are you sure you want to delete this journal?')" title="Delete Journal">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
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
                            <strong><?php echo min($offset + $perPage, $totalJournals); ?></strong>
                            of <strong><?php echo $totalJournals; ?></strong> journals
                        </div>
                        <nav>
                            <ul class="pagination">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo $specialtyFilter > 0 ? '&specialty=' . $specialtyFilter : ''; ?>" aria-label="Previous">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo $specialtyFilter > 0 ? '&specialty=' . $specialtyFilter : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo $specialtyFilter > 0 ? '&specialty=' . $specialtyFilter : ''; ?>" aria-label="Next">
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
    ADD JOURNAL MODAL
    ============================================ -->
    <div class="modal fade" id="addJournalModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus" style="color: #198754;"></i> Add New Journal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Article Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Journal Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="journal_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Specialty <span class="text-danger">*</span></label>
                                <select class="form-select" name="specialty_id" required>
                                    <option value="">Select Specialty</option>
                                    <?php foreach ($specialties as $specialty): ?>
                                    <option value="<?php echo $specialty['id']; ?>">
                                        <?php echo htmlspecialchars($specialty['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" class="form-control" name="date">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Volume</label>
                                <input type="text" class="form-control" name="volume">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Issue</label>
                                <input type="text" class="form-control" name="issue">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Pages</label>
                                <input type="text" class="form-control" name="pages">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">DOI</label>
                                <input type="text" class="form-control" name="doi">
                            </div>

                            <!-- ✅ COVER IMAGE UPLOAD -->
                            <div class="col-12 mb-3">
                                <label class="form-label">Journal Cover Image</label>
                                <div class="cover-upload-group">
                                    <div class="cover-upload-preview" id="coverPreview">
                                        <i class="fas fa-image"></i>
                                    </div>
                                    <div class="cover-upload-fields">
                                        <input type="file" class="form-control" name="cover_image"
                                               accept="image/*" id="coverImageInput"
                                               onchange="previewCover(this)">
                                        <div class="cover-upload-hint">
                                            <i class="fas fa-info-circle"></i>
                                            Recommended: <strong>JPG / PNG / WebP</strong>, portrait orientation (approx. <strong>600×800 px</strong>). Max 5 MB.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">Abstract</label>
                                <textarea class="form-control" name="abstract" rows="3"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Journal PDF <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" name="journal_file" accept=".pdf" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="is_watermarked" name="is_watermarked" checked>
                                    <label class="form-check-label" for="is_watermarked">Add Watermark</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_journal" class="btn btn-success">
                            <i class="fas fa-save"></i> Add Journal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        // ============================================
        // COVER IMAGE LIVE PREVIEW
        // ============================================
        function previewCover(input) {
            const preview = document.getElementById('coverPreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Cover Preview">';
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.innerHTML = '<i class="fas fa-image"></i>';
            }
        }

        // ============================================
        // AUTO-SUBMIT FILTERS
        // ============================================
        document.addEventListener('DOMContentLoaded', function () {
            const filterSelects = document.querySelectorAll('.filter-section select');
            filterSelects.forEach(function (select) {
                select.addEventListener('change', function () {
                    this.closest('form').submit();
                });
            });
        });
    </script>
</body>
</html>