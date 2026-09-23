<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$specialtyId = isset($_GET['specialty']) ? intval($_GET['specialty']) : 0;
$volumeFilter = isset($_GET['volume']) ? sanitize($_GET['volume']) : '';
$issueFilter = isset($_GET['issue']) ? sanitize($_GET['issue']) : '';
$searchQuery = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$specialty = $specialtyId ? getSpecialtyById($specialtyId) : null;

$db = Database::getInstance()->getConnection();

// ============================================
// BUILD JOURNALS QUERY
// ============================================
$journals = [];
$journalConditions = ["j.status = 'approved'"];
$journalParams = [];
$journalTypes = "";

if ($specialtyId && $specialty) {
    $journalConditions[] = "j.specialty_id = ?";
    $journalParams[] = $specialtyId;
    $journalTypes .= "i";
}

if ($volumeFilter !== '') {
    $journalConditions[] = "j.volume = ?";
    $journalParams[] = $volumeFilter;
    $journalTypes .= "s";
}

if ($issueFilter !== '') {
    $journalConditions[] = "j.issue = ?";
    $journalParams[] = $issueFilter;
    $journalTypes .= "s";
}

if (!empty($searchQuery)) {
    $searchTerm = '%' . $searchQuery . '%';
    $journalConditions[] = "(j.title LIKE ? OR j.journal_name LIKE ? OR j.abstract LIKE ? OR j.doi LIKE ?)";
    $journalParams[] = $searchTerm;
    $journalParams[] = $searchTerm;
    $journalParams[] = $searchTerm;
    $journalParams[] = $searchTerm;
    $journalTypes .= "ssss";
}

$journalWhere = implode(' AND ', $journalConditions);
$journalSql = "
    SELECT j.*, s.name as specialty_name 
    FROM journals j 
    JOIN specialties s ON j.specialty_id = s.id 
    WHERE $journalWhere
    ORDER BY j.date DESC, j.created_at DESC
";
$journalStmt = $db->prepare($journalSql);
if (!empty($journalParams)) {
    $journalStmt->bind_param($journalTypes, ...$journalParams);
}
$journalStmt->execute();
$journals = $journalStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$journalStmt->close();

// ============================================
// BUILD BOOKS QUERY
// ============================================
$books = [];
$bookConditions = ["b.status = 'approved'"];
$bookParams = [];
$bookTypes = "";

if ($specialtyId && $specialty) {
    $bookConditions[] = "b.specialty_id = ?";
    $bookParams[] = $specialtyId;
    $bookTypes .= "i";
}

if (!empty($searchQuery)) {
    $searchTerm = '%' . $searchQuery . '%';
    $bookConditions[] = "(b.title LIKE ? OR b.author LIKE ? OR b.description LIKE ? OR b.publisher LIKE ? OR b.isbn LIKE ?)";
    $bookParams[] = $searchTerm;
    $bookParams[] = $searchTerm;
    $bookParams[] = $searchTerm;
    $bookParams[] = $searchTerm;
    $bookParams[] = $searchTerm;
    $bookTypes .= "sssss";
}

$bookWhere = implode(' AND ', $bookConditions);
$bookSql = "
    SELECT b.*, s.name as specialty_name 
    FROM books b 
    JOIN specialties s ON b.specialty_id = s.id 
    WHERE $bookWhere
    ORDER BY b.title
";
$bookStmt = $db->prepare($bookSql);
if (!empty($bookParams)) {
    $bookStmt->bind_param($bookTypes, ...$bookParams);
}
$bookStmt->execute();
$books = $bookStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$bookStmt->close();

// ============================================
// GET ALL VOLUMES & ISSUES for sidebar
// ============================================
$volumeIssueQuery = "
    SELECT volume, issue, COUNT(*) as count
    FROM journals
    WHERE status = 'approved' AND volume IS NOT NULL AND volume != ''
    GROUP BY volume, issue
    ORDER BY volume DESC, issue DESC
";
$volumeIssueList = $db->query($volumeIssueQuery)->fetch_all(MYSQLI_ASSOC);

// Group by volume for sidebar display
$volumesGrouped = [];
foreach ($volumeIssueList as $vi) {
    $vol = $vi['volume'];
    if (!isset($volumesGrouped[$vol])) {
        $volumesGrouped[$vol] = ['total' => 0, 'issues' => []];
    }
    $volumesGrouped[$vol]['total'] += $vi['count'];
    $volumesGrouped[$vol]['issues'][] = $vi;
}

// ============================================
// TOTAL STATS
// ============================================
$stats = [
    'books' => $db->query("SELECT COUNT(*) as count FROM books WHERE status = 'approved'")->fetch_assoc()['count'],
    'journals' => $db->query("SELECT COUNT(*) as count FROM journals WHERE status = 'approved'")->fetch_assoc()['count'],
    'volumes' => count($volumesGrouped),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $specialty ? htmlspecialchars($specialty['name']) : 'Browse'; ?> - BJDVL</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cambria&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Cambria', Georgia, serif;
            background: #f5f7fa;
            color: #1a1a2e;
            font-size: 16px;
        }
        
        .main-wrapper { padding: 25px 0 10px; min-height: calc(100vh - 140px); }
        
        /* ============================================
           PAGE HEADER + SEARCH (in one row)
           ============================================ */
        .page-header {
            background: #ffffff;
            padding: 18px 0;
            border-bottom: 1px solid #eef1f5;
        }
        .page-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            flex-wrap: wrap;
        }
        .page-header-title {
            flex: 0 1 auto;
            min-width: 220px;
        }
        .page-header-title h1 {
            font-weight: 700;
            font-size: 1.4rem;
            color: #1a1a2e;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .page-header-title h1 i {
            color: #0d6efd;
            font-size: 1.2rem;
        }
        .page-header-title .subtitle {
            color: #6c757d;
            font-size: 0.85rem;
            margin: 4px 0 0;
        }
        .page-header-title .stats-inline {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 6px;
        }
        .page-header-title .stats-inline .badge {
            padding: 4px 12px;
            font-size: 0.72rem;
            font-weight: 600;
            border-radius: 20px;
        }
        
        /* Search inline on the right */
        .page-search {
            flex: 1 1 380px;
            max-width: 520px;
            min-width: 260px;
        }
        .page-search .search-box {
            display: flex;
            gap: 6px;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 4px;
            border: 2px solid #eef1f5;
            transition: all 0.3s ease;
        }
        .page-search .search-box:focus-within {
            border-color: #0d6efd;
            box-shadow: 0 0 0 4px rgba(13,110,253,0.08);
            background: #ffffff;
        }
        .page-search .search-box .search-input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 8px 14px;
            font-size: 0.92rem;
            outline: none;
            color: #1a1a2e;
            min-width: 0;
        }
        .page-search .search-box .search-input::placeholder { color: #adb5bd; }
        .page-search .search-box .search-btn {
            padding: 8px 20px;
            border: none;
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            color: #fff;
            border-radius: 7px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s ease;
        }
        .page-search .search-box .search-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13,110,253,0.25);
        }
        .page-search .search-box .search-btn i { margin-right: 5px; }
        .page-search .search-box .search-clear-btn {
            padding: 8px 12px;
            border: none;
            background: transparent;
            color: #6c757d;
            font-size: 0.85rem;
            cursor: pointer;
            text-decoration: none;
            white-space: nowrap;
        }
        .page-search .search-box .search-clear-btn:hover { color: #dc3545; }
        .page-search .search-results-info {
            margin-top: 6px;
            font-size: 0.8rem;
            color: #6c757d;
        }
        .page-search .search-results-info strong { color: #1a1a2e; }
        
        /* ============================================
           VOLUME & ISSUE SIDEBAR
           ============================================ */
        .filter-sidebar {
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #eef1f5;
            padding: 18px 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            height: 100%;
            position: sticky;
            top: 80px;
        }
        .filter-sidebar .header {
            font-weight: 700;
            font-size: 1rem;
            color: #1a1a2e;
            padding-bottom: 10px;
            border-bottom: 1px solid #eef1f5;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .filter-sidebar .header i { color: #198754; }
        
        .filter-list {
            flex: 1;
            overflow-y: auto;
            max-height: 520px;
            padding-right: 4px;
        }
        .filter-list::-webkit-scrollbar { width: 5px; }
        .filter-list::-webkit-scrollbar-thumb { background: #dee2e6; border-radius: 3px; }
        
        /* All Volumes item */
        .filter-item-all {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 11px;
            text-decoration: none;
            color: #4a4a5e;
            cursor: pointer;
            font-size: 0.88rem;
            border-radius: 6px;
            margin-bottom: 4px;
            transition: all 0.2s ease;
            border: 1px solid #eef1f5;
        }
        .filter-item-all:hover {
            background: #f0f7ff;
            color: #0d6efd;
            text-decoration: none;
            border-color: #0d6efd;
        }
        .filter-item-all .name {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .filter-item-all .badge-count {
            font-size: 0.68rem;
            background: #e9ecef;
            padding: 2px 10px;
            border-radius: 12px;
            color: #6c757d;
            min-width: 24px;
            text-align: center;
            font-weight: 600;
        }
        .filter-item-all.active {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            color: #fff;
            border-color: #0d6efd;
        }
        .filter-item-all.active .badge-count {
            background: rgba(255,255,255,0.25);
            color: #fff;
        }
        
        /* Volume group */
        .volume-group {
            margin-bottom: 6px;
        }
        .volume-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 10px;
            background: #f8fafb;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
            border: 1px solid #eef1f5;
        }
        .volume-header:hover {
            background: #eef4fb;
            border-color: #cce0f5;
        }
        .volume-header .vol-name {
            font-weight: 700;
            font-size: 0.85rem;
            color: #1a1a2e;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .volume-header .vol-name i {
            color: #0d6efd;
            font-size: 0.75rem;
        }
        .volume-header .vol-count {
            font-size: 0.65rem;
            background: #e8f0fe;
            color: #0d6efd;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 600;
        }
        .volume-header .vol-toggle {
            font-size: 0.6rem;
            color: #6c757d;
            transition: transform 0.25s ease;
            margin-left: 4px;
        }
        .volume-group.expanded .volume-header .vol-toggle {
            transform: rotate(90deg);
        }
        
        /* Issues list under volume */
        .volume-issues {
            display: none;
            padding: 4px 0 4px 8px;
            margin-top: 4px;
        }
        .volume-group.expanded .volume-issues {
            display: block;
            animation: fadeIn 0.25s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .issue-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 10px;
            text-decoration: none;
            color: #4a4a5e;
            font-size: 0.82rem;
            border-radius: 5px;
            margin-bottom: 2px;
            transition: all 0.2s ease;
            border-left: 2px solid transparent;
        }
        .issue-item:hover {
            background: #f0f7ff;
            color: #0d6efd;
            text-decoration: none;
            border-left-color: #0d6efd;
        }
        .issue-item .issue-name {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }
        .issue-item .issue-name i {
            font-size: 0.65rem;
            color: #adb5bd;
        }
        .issue-item .issue-badge {
            font-size: 0.6rem;
            background: #e9ecef;
            padding: 1px 8px;
            border-radius: 8px;
            color: #6c757d;
            font-weight: 600;
        }
        .issue-item.active {
            background: #dcfce7;
            color: #166534;
            border-left-color: #198754;
            font-weight: 600;
        }
        .issue-item.active .issue-name i { color: #198754; }
        .issue-item.active .issue-badge {
            background: #198754;
            color: #fff;
        }
        
        .filter-divider { height: 1px; background: #eef1f5; margin: 10px 0; }
        
        .back-home-btn {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eef1f5;
        }
        .back-home-btn .btn {
            font-weight: 600;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 0.82rem;
            width: 100%;
        }
        
        /* ============================================
           CONTENT AREA
           ============================================ */
        .content-area { padding-left: 0; }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 0 0 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eef1f5;
        }
        .section-header h4 {
            font-weight: 700;
            font-size: 1.15rem;
            color: #1a1a2e;
            margin: 0;
        }
        .section-header h4 i { margin-right: 8px; }
        .section-header .count {
            font-size: 0.82rem;
            color: #6c757d;
        }
        
        /* Filter chips row (showing active filters) */
        .active-filters {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 14px;
        }
        .active-filters .lbl {
            font-size: 0.78rem;
            color: #6c757d;
            font-weight: 600;
        }
        .active-filters .filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            background: #e8f0fe;
            color: #0d6efd;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .active-filters .filter-chip:hover {
            background: #fce4ec;
            color: #dc3545;
            text-decoration: none;
        }
        .active-filters .filter-chip i {
            font-size: 0.7rem;
        }
        
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
        }
        
        /* ============================================
           ITEM CARD
           ============================================ */
        .item-card {
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #eef1f5;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .item-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border-color: #0d6efd;
        }
        
        .item-card .cover {
            height: 180px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            padding: 10px;
            flex-shrink: 0;
        }
        .item-card .cover img {
            max-height: 100%;
            width: auto;
            object-fit: contain;
        }
        .item-card .cover .placeholder { font-size: 2.8rem; color: #ced4da; }
        .item-card .cover .specialty-tag {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(0,0,0,0.6);
            color: #fff;
            font-size: 0.6rem;
            padding: 2px 10px;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .item-card .info { padding: 12px 14px 8px; flex: 1; }
        .item-card .info h6 {
            font-size: 0.92rem;
            font-weight: 700;
            margin-bottom: 2px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.3;
            color: #1a1a2e;
        }
        .item-card .info .author { font-size: 0.8rem; color: #6c757d; }
        .item-card .info .meta {
            font-size: 0.68rem;
            color: #adb5bd;
            display: flex;
            gap: 4px;
            margin-top: 4px;
            flex-wrap: wrap;
        }
        .item-card .info .meta span {
            background: #f0f4f8;
            padding: 1px 10px;
            border-radius: 10px;
        }
        
        .item-card .actions {
            padding: 4px 14px 12px;
            display: flex;
            gap: 6px;
            flex-shrink: 0;
        }
        .item-card .actions .btn {
            flex: 1;
            font-size: 0.75rem;
            padding: 6px 10px;
            border-radius: 5px;
            font-weight: 600;
        }
        
        /* Journal Card Specific */
        .item-card.journal .cover {
            height: 180px;
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        }
        .item-card.journal .cover .journal-icon {
            font-size: 2.4rem;
            color: #198754;
        }
        .item-card.journal .cover .journal-name-tag {
            position: absolute;
            bottom: 8px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(25,135,84,0.9);
            color: #fff;
            font-size: 0.6rem;
            padding: 2px 12px;
            border-radius: 12px;
            font-weight: 500;
            white-space: nowrap;
            max-width: 90%;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .item-card.journal:hover { border-color: #198754; }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #eef1f5;
        }
        .empty-state .icon { font-size: 3rem; color: #dee2e6; margin-bottom: 12px; }
        .empty-state h5 {
            font-weight: 700;
            color: #1a1a2e;
            font-size: 1.05rem;
        }
        .empty-state p { color: #6c757d; font-size: 0.9rem; }
        
        /* ============================================
           READER MODAL
           ============================================ */
        .reader-modal { z-index: 9999 !important; }
        .reader-modal .modal-dialog {
            max-width: 96vw;
            width: 96vw;
            margin: 1.5rem auto;
            height: calc(100vh - 3rem);
        }
        .reader-modal .modal-content {
            border-radius: 12px;
            border: none;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .reader-modal .reader-header {
            background: #1a1a2e;
            color: #fff;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-shrink: 0;
        }
        .reader-modal .reader-header .reader-title {
            font-weight: 600;
            font-size: 1rem;
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .reader-modal .reader-header .reader-badge {
            font-size: 0.68rem;
            background: rgba(255,255,255,0.15);
            padding: 3px 12px;
            border-radius: 20px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .reader-modal .reader-header .btn-close {
            filter: invert(1);
            opacity: 0.8;
        }
        .reader-modal .reader-header .btn-close:hover { opacity: 1; }
        
        .reader-modal .reader-body {
            background: #525659;
            padding: 0;
            position: relative;
            flex: 1;
            overflow: hidden;
        }
        .reader-modal .reader-body iframe {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }
        
        .reader-modal .reader-footer {
            background: #f8f9fa;
            padding: 10px 20px;
            border-top: 1px solid #eef1f5;
            font-size: 0.82rem;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
            flex-shrink: 0;
        }
        .reader-modal .reader-footer i { color: #dc3545; }
        
        .modal-backdrop {
            z-index: 9998 !important;
            background-color: rgba(0,0,0,0.7) !important;
        }
        
        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 1200px) {
            .page-header-row {
                gap: 16px;
            }
            .page-search {
                flex: 1 1 320px;
                max-width: 460px;
            }
        }
        
        @media (max-width: 992px) {
            .page-header-row {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }
            .page-search {
                max-width: 100%;
                flex: 1 1 auto;
            }
            .filter-sidebar {
                position: relative;
                top: 0;
                margin-bottom: 16px;
            }
            .content-area { padding-left: 0; }
            .items-grid { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
        }
        
        @media (max-width: 768px) {
            .items-grid { grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 12px; }
            .item-card .cover { height: 160px; }
            .item-card.journal .cover { height: 160px; }
            .page-header-title h1 { font-size: 1.2rem; }
            .page-header { padding: 14px 0; }
            .page-header-title .subtitle { font-size: 0.8rem; }
            .page-search .search-box .search-input { font-size: 0.88rem; }
            .reader-modal .modal-dialog {
                max-width: 100vw;
                width: 100vw;
                margin: 0;
                height: 100vh;
            }
            .reader-modal .modal-content { border-radius: 0; }
        }
        
        @media (max-width: 576px) {
            /* Reorder: Sidebar first, then content */
            .row.g-4 {
                display: flex;
                flex-direction: column;
            }
            .col-lg-3 {
                order: 1;
                width: 100%;
                flex: 0 0 100%;
                max-width: 100%;
                padding: 0 12px;
            }
            .col-lg-9 {
                order: 2;
                width: 100%;
                flex: 0 0 100%;
                max-width: 100%;
                padding: 0 12px;
            }
            .items-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .item-card .cover { height: 130px; }
            .item-card.journal .cover { height: 130px; }
            .item-card .info h6 { font-size: 0.82rem; }
            .item-card .actions .btn { font-size: 0.65rem; padding: 3px 6px; }
            .item-card .info { padding: 8px 10px 4px; }
            .item-card .actions { padding: 2px 10px 10px; }
            
            .page-header { padding: 12px 0; }
            .page-header-title h1 { font-size: 1.1rem; }
            .page-header-title .subtitle { font-size: 0.78rem; }
            .page-header-title .stats-inline .badge { font-size: 0.65rem; padding: 3px 10px; }
            
            .page-search .search-box { flex-wrap: wrap; border-radius: 8px; padding: 4px; }
            .page-search .search-box .search-input { flex: 1 1 100%; padding: 8px 12px; font-size: 0.88rem; }
            .page-search .search-box .search-btn { flex: 1; padding: 6px 12px; font-size: 0.82rem; }
            .page-search .search-box .search-clear-btn { flex: 0 1 auto; font-size: 0.82rem; padding: 6px 10px; }
            
            .filter-sidebar { padding: 14px 12px; margin-bottom: 14px; border-radius: 8px; }
            .filter-list { max-height: 300px; }
            .section-header h4 { font-size: 1.05rem; }
            .section-header .count { font-size: 0.78rem; }
            
            .main-wrapper { padding: 14px 0 10px; }
            .container { padding-left: 10px; padding-right: 10px; }
        }
        
        @media (max-width: 400px) {
            .items-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
            .item-card .cover { height: 110px; }
            .item-card.journal .cover { height: 110px; }
            .item-card .info h6 { font-size: 0.78rem; }
            .item-card .actions .btn { font-size: 0.55rem; padding: 2px 4px; }
            .page-header-title h1 { font-size: 1rem; }
        }
        
        /* ============================================
           FOOTER
           ============================================ */
        .footer-main {
            background: #ffffff;
            padding: 16px 0;
            border-top: 1px solid #eef1f5;
            margin-top: 12px;
        }
        .footer-main p {
            margin: 0;
            color: #1a1a2e;
            font-size: 0.9rem;
            text-align: center;
        }
        .footer-main p strong { color: #0d6efd; }
        .footer-main p .version { color: #6c757d; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/public_nav.php'; ?>

    <!-- ============================================
         PAGE HEADER + SEARCH (in one row)
         ============================================ -->
    <div class="page-header">
        <div class="container">
            <div class="page-header-row">
                <!-- Title + badges (left) -->
                <div class="page-header-title">
                    <h1>
                        <?php if ($specialty): ?>
                            <i class="fas fa-tag"></i>
                            <?php echo htmlspecialchars($specialty['name']); ?>
                        <?php else: ?>
                            <i class="fas fa-search"></i>
                            Browse Resources
                        <?php endif; ?>
                    </h1>
                    <div class="subtitle">
                        <?php if ($specialty && !empty($specialty['description'])): ?>
                            <?php echo htmlspecialchars($specialty['description']); ?>
                        <?php else: ?>
                            Explore our collection — free to read, no login required
                        <?php endif; ?>
                    </div>
                    <div class="stats-inline">
                        <span class="badge bg-success"><i class="fas fa-newspaper"></i> <?php echo count($journals); ?> Journals</span>
                        <span class="badge bg-primary"><i class="fas fa-book"></i> <?php echo count($books); ?> Books</span>
                    </div>
                </div>
                
                <!-- Search box (right) -->
                <div class="page-search">
                    <form method="GET" action="/browse">
                        <?php if ($specialtyId): ?>
                        <input type="hidden" name="specialty" value="<?php echo $specialtyId; ?>">
                        <?php endif; ?>
                        <?php if ($volumeFilter !== ''): ?>
                        <input type="hidden" name="volume" value="<?php echo htmlspecialchars($volumeFilter); ?>">
                        <?php endif; ?>
                        <?php if ($issueFilter !== ''): ?>
                        <input type="hidden" name="issue" value="<?php echo htmlspecialchars($issueFilter); ?>">
                        <?php endif; ?>
                        
                        <div class="search-box">
                            <input type="text" class="search-input" name="search" 
                                   placeholder="Search books, journals, authors..."
                                   value="<?php echo htmlspecialchars($searchQuery); ?>"
                                   autocomplete="off">
                            <?php if (!empty($searchQuery)): ?>
                            <a href="/browse?<?php echo http_build_query(array_filter(['specialty' => $specialtyId ?: null, 'volume' => $volumeFilter ?: null, 'issue' => $issueFilter ?: null])); ?>" 
                               class="search-clear-btn" title="Clear search">
                                <i class="fas fa-times"></i>
                            </a>
                            <?php endif; ?>
                            <button type="submit" class="search-btn">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </form>
                    
                    <?php if (!empty($searchQuery)): ?>
                    <div class="search-results-info">
                        Found <strong><?php echo count($books) + count($journals); ?></strong> result(s) for "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>"
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-wrapper">
        <div class="container">
            <div class="row g-4">
                
                <!-- ============================================
                     VOLUME & ISSUE SIDEBAR
                     ============================================ -->
                <div class="col-lg-3">
                    <div class="filter-sidebar">
                        <div class="header">
                            <i class="fas fa-layer-group"></i> Volume &amp; Issue
                        </div>
                        
                        <div class="filter-list">
                            <!-- All Resources -->
                            <a href="/browse<?php echo !empty($searchQuery) ? '?search=' . urlencode($searchQuery) : ''; ?>" 
                               class="filter-item-all <?php echo ($volumeFilter === '' && $issueFilter === '') ? 'active' : ''; ?>">
                                <span class="name"><i class="fas fa-th"></i> All Resources</span>
                                <span class="badge-count"><?php echo $stats['books'] + $stats['journals']; ?></span>
                            </a>
                            
                            <?php if (empty($volumesGrouped)): ?>
                                <div style="text-align:center; padding: 20px 10px; color:#adb5bd; font-size:0.8rem;">
                                    <i class="fas fa-inbox" style="font-size:1.5rem; display:block; margin-bottom:6px;"></i>
                                    No volumes yet
                                </div>
                            <?php else: ?>
                                <?php foreach ($volumesGrouped as $volName => $volData): 
                                    $isExpanded = ($volumeFilter === (string)$volName);
                                ?>
                                <div class="volume-group <?php echo $isExpanded ? 'expanded' : ''; ?>">
                                    <div class="volume-header" onclick="toggleVolume(this)">
                                        <span class="vol-name">
                                            <i class="fas fa-book"></i> Vol <?php echo htmlspecialchars($volName); ?>
                                        </span>
                                        <span style="display: flex; align-items: center; gap: 6px;">
                                            <span class="vol-count"><?php echo $volData['total']; ?></span>
                                            <i class="fas fa-chevron-right vol-toggle"></i>
                                        </span>
                                    </div>
                                    <div class="volume-issues">
                                        <?php foreach ($volData['issues'] as $vi): 
                                            $issueNum = $vi['issue'];
                                            $isActive = ($volumeFilter === (string)$volName && $issueFilter === (string)$issueNum);
                                        ?>
                                        <a href="/browse?<?php echo http_build_query(array_filter([
                                            'specialty' => $specialtyId ?: null,
                                            'volume' => $volName,
                                            'issue' => $issueNum,
                                            'search' => $searchQuery ?: null
                                        ])); ?>" 
                                           class="issue-item <?php echo $isActive ? 'active' : ''; ?>">
                                            <span class="issue-name">
                                                <i class="fas fa-file-alt"></i> Issue <?php echo htmlspecialchars($issueNum); ?>
                                            </span>
                                            <span class="issue-badge"><?php echo $vi['count']; ?></span>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="filter-divider"></div>
                        
                        <div class="back-home-btn">
                            <a href="/" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Back to Home
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- ============================================
                     CONTENT AREA
                     ============================================ -->
                <div class="col-lg-9 content-area">
                    
                    <!-- Active filters chips -->
                    <?php if ($volumeFilter !== '' || $issueFilter !== '' || !empty($searchQuery)): ?>
                    <div class="active-filters">
                        <span class="lbl"><i class="fas fa-filter"></i> Active:</span>
                        
                        <?php if ($volumeFilter !== ''): ?>
                        <a href="/browse?<?php echo http_build_query(array_filter([
                            'specialty' => $specialtyId ?: null,
                            'issue' => $issueFilter ?: null,
                            'search' => $searchQuery ?: null
                        ])); ?>" class="filter-chip">
                            Vol <?php echo htmlspecialchars($volumeFilter); ?> <i class="fas fa-times"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($issueFilter !== ''): ?>
                        <a href="/browse?<?php echo http_build_query(array_filter([
                            'specialty' => $specialtyId ?: null,
                            'volume' => $volumeFilter ?: null,
                            'search' => $searchQuery ?: null
                        ])); ?>" class="filter-chip">
                            Issue <?php echo htmlspecialchars($issueFilter); ?> <i class="fas fa-times"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($searchQuery)): ?>
                        <a href="/browse?<?php echo http_build_query(array_filter([
                            'specialty' => $specialtyId ?: null,
                            'volume' => $volumeFilter ?: null,
                            'issue' => $issueFilter ?: null
                        ])); ?>" class="filter-chip">
                            "<?php echo htmlspecialchars($searchQuery); ?>" <i class="fas fa-times"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- JOURNALS -->
                    <div class="section-header">
                        <h4><i class="fas fa-newspaper text-success"></i> Journals</h4>
                        <span class="count"><?php echo count($journals); ?> journals found</span>
                    </div>
                    
                    <?php if (empty($journals)): ?>
                        <div class="empty-state">
                            <div class="icon"><i class="fas fa-newspaper"></i></div>
                            <h5>No Journals Found</h5>
                            <p>No journals match your current filters.</p>
                        </div>
                    <?php else: ?>
                    <div class="items-grid">
                        <?php foreach ($journals as $journal): ?>
                        <div class="item-card journal">
                            <div class="cover">
                                <?php if (!empty($journal['cover_image'])): ?>
                                    <img src="/uploads/journals/<?php echo htmlspecialchars($journal['cover_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($journal['title']); ?>" 
                                         loading="lazy"
                                         style="max-height:100%;width:auto;object-fit:contain;">
                                <?php else: ?>
                                    <div class="journal-icon"><i class="fas fa-file-medical-alt"></i></div>
                                <?php endif; ?>
                                <span class="specialty-tag"><?php echo htmlspecialchars($journal['specialty_name'] ?? ''); ?></span>
                                <span class="journal-name-tag"><?php echo htmlspecialchars($journal['journal_name'] ?? ''); ?></span>
                            </div>
                            <div class="info">
                                <h6><?php echo htmlspecialchars($journal['title'] ?? ''); ?></h6>
                                <div class="meta">
                                    <?php if (!empty($journal['date'])): ?>
                                    <span><i class="far fa-calendar-alt"></i> <?php echo date('M Y', strtotime($journal['date'])); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($journal['volume'])): ?>
                                    <span>Vol <?php echo $journal['volume']; ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($journal['issue'])): ?>
                                    <span>Issue <?php echo $journal['issue']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="actions">
                                <button type="button" class="btn btn-success btn-sm w-100"
                                        onclick='openReader("journal", <?php echo $journal["id"]; ?>, <?php echo json_encode($journal["title"]); ?>)'>
                                    <i class="fas fa-book-open"></i> Read Journal
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- BOOKS -->
                    <div class="section-header" style="margin-top: 32px;">
                        <h4><i class="fas fa-book text-primary"></i> Books</h4>
                        <span class="count"><?php echo count($books); ?> books found</span>
                    </div>
                    
                    <?php if (empty($books)): ?>
                        <div class="empty-state">
                            <div class="icon"><i class="fas fa-book-open"></i></div>
                            <h5>No Books Found</h5>
                            <p>No books match your current filters.</p>
                        </div>
                    <?php else: ?>
                    <div class="items-grid">
                        <?php foreach ($books as $book): ?>
                        <div class="item-card">
                            <div class="cover">
                                <?php if (!empty($book['cover_image'])): ?>
                                <img src="/uploads/books/<?php echo $book['cover_image']; ?>" 
                                     alt="<?php echo htmlspecialchars($book['title'] ?? ''); ?>" loading="lazy">
                                <?php else: ?>
                                <div class="placeholder"><i class="fas fa-book"></i></div>
                                <?php endif; ?>
                                <span class="specialty-tag"><?php echo htmlspecialchars($book['specialty_name'] ?? ''); ?></span>
                            </div>
                            <div class="info">
                                <h6><?php echo htmlspecialchars($book['title'] ?? ''); ?></h6>
                                <div class="author"><?php echo htmlspecialchars($book['author'] ?? ''); ?></div>
                                <div class="meta">
                                    <span><?php echo $book['year'] ?? 'N/A'; ?></span>
                                </div>
                            </div>
                            <div class="actions">
                                <button type="button" class="btn btn-primary btn-sm w-100"
                                        onclick='openReader("book", <?php echo $book["id"]; ?>, <?php echo json_encode($book["title"]); ?>)'>
                                    <i class="fas fa-book-open"></i> Read Book
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================
         VIEW-ONLY READER MODAL
         ============================================ -->
    <div class="modal fade reader-modal" id="readerModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="reader-header">
                    <span class="reader-title" id="readerTitle">Loading...</span>
                    <span class="reader-badge">
                        <i class="fas fa-lock"></i> View Only
                    </span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="reader-body" id="readerBody">
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #adb5bd; flex-direction: column; gap: 12px;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 2.5rem;"></i>
                        <p style="font-size: 1rem;">Loading document...</p>
                    </div>
                </div>
                <div class="reader-footer">
                    <i class="fas fa-shield-alt"></i>
                    <span>Protected view-only mode. Download, print, copy, and right-click are disabled.</span>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="footer-main">
        <div class="container">
            <p>
                <strong>BJDVL</strong> &copy; <?php echo date('Y'); ?> 
                <span class="version">v2.0</span> &bull; 
                Powered by UniMed UniHealth Group
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ============================================
        // TOGGLE VOLUME GROUP
        // ============================================
        function toggleVolume(el) {
            const group = el.closest('.volume-group');
            if (group) group.classList.toggle('expanded');
        }
        
        // Auto-expand active volume on page load
        document.addEventListener('DOMContentLoaded', function() {
            const activeIssue = document.querySelector('.issue-item.active');
            if (activeIssue) {
                const parentGroup = activeIssue.closest('.volume-group');
                if (parentGroup) parentGroup.classList.add('expanded');
            }
        });
        
        // ============================================
        // AUTO-SUBMIT SEARCH ON ENTER
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.form.submit();
                    }
                });
            }
        });
        
        // ============================================
        // VIEW-ONLY READER MODAL
        // ============================================
        let readerModal = null;
        
        function openReader(type, id, title) {
            if (!readerModal) {
                readerModal = new bootstrap.Modal(document.getElementById('readerModal'), {
                    backdrop: 'static',
                    keyboard: false
                });
            }
            
            const titleEl = document.getElementById('readerTitle');
            const bodyEl = document.getElementById('readerBody');
            
            titleEl.textContent = title || (type === 'book' ? 'Book' : 'Journal');
            
            bodyEl.innerHTML = `
                <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #adb5bd; flex-direction: column; gap: 12px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2.5rem;"></i>
                    <p style="font-size: 1rem;">Loading document...</p>
                </div>
            `;
            
            readerModal.show();
            
            const pdfUrl = `/reader.php?type=${encodeURIComponent(type)}&id=${encodeURIComponent(id)}&t=${Date.now()}`;
            const viewerUrl = `/assets/pdfjs/web/viewer.html?file=${encodeURIComponent(pdfUrl)}#viewonly`;
            
            setTimeout(function() {
                bodyEl.innerHTML = `
                    <iframe src="${viewerUrl}" 
                            id="readerFrame"
                            oncontextmenu="return false;"
                            allowfullscreen="false"
                            referrerpolicy="no-referrer"
                            style="width:100%;height:100%;border:none;"></iframe>
                `;
            }, 300);
        }
        
        // Global protections
        document.addEventListener('DOMContentLoaded', function() {
            const modalEl = document.getElementById('readerModal');
            if (modalEl) {
                modalEl.addEventListener('contextmenu', function(e) {
                    e.preventDefault();
                    return false;
                });
                
                document.addEventListener('keydown', function(e) {
                    const modalOpen = modalEl.classList.contains('show');
                    if (!modalOpen) return;
                    
                    if ((e.ctrlKey || e.metaKey)) {
                        const k = e.key.toLowerCase();
                        if (k === 'p' || k === 's' || k === 'c' || k === 'a' || k === 'u') {
                            e.preventDefault();
                            return false;
                        }
                    }
                    if (e.key === 'F12') { e.preventDefault(); return false; }
                    if ((e.ctrlKey || e.metaKey) && e.shiftKey) {
                        const k = e.key.toLowerCase();
                        if (k === 'i' || k === 'j' || k === 'c') { e.preventDefault(); return false; }
                    }
                });
            }
        });
        
        window.addEventListener('beforeprint', function(e) {
            const modalEl = document.getElementById('readerModal');
            if (modalEl && modalEl.classList.contains('show')) {
                e.preventDefault();
                alert('Printing is disabled in view-only mode.');
                return false;
            }
        });
    </script>
</body>
</html>