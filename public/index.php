<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$db = Database::getInstance()->getConnection();

// Get search parameter
$searchQuery = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$searchResults = [];
$searchPerformed = false;

if (!empty($searchQuery)) {
    $searchPerformed = true;
    $searchTerm = '%' . $searchQuery . '%';

    $bookStmt = $db->prepare("
        SELECT b.*, s.name as specialty_name, 'book' as item_type 
        FROM books b 
        JOIN specialties s ON b.specialty_id = s.id 
        WHERE b.status = 'approved' 
        AND (b.title LIKE ? OR b.author LIKE ? OR b.description LIKE ? OR b.publisher LIKE ?)
        ORDER BY b.title
    ");
    $bookStmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $bookStmt->execute();
    $bookResults = $bookStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $bookStmt->close();

    $journalStmt = $db->prepare("
        SELECT j.*, s.name as specialty_name, 'journal' as item_type 
        FROM journals j 
        JOIN specialties s ON j.specialty_id = s.id 
        WHERE j.status = 'approved' 
        AND (j.title LIKE ? OR j.journal_name LIKE ? OR j.abstract LIKE ? OR j.doi LIKE ?)
        ORDER BY j.title
    ");
    $journalStmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $journalStmt->execute();
    $journalResults = $journalStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $journalStmt->close();

    $searchResults = array_merge($bookResults, $journalResults);
    usort($searchResults, function ($a, $b) use ($searchQuery) {
        $aScore = 0; $bScore = 0;
        $queryLower = strtolower($searchQuery);
        if (stripos($a['title'], $searchQuery) !== false) $aScore += 10;
        if (stripos($b['title'], $searchQuery) !== false) $bScore += 10;
        if (strtolower($a['title']) === $queryLower) $aScore += 5;
        if (strtolower($b['title']) === $queryLower) $bScore += 5;
        return $bScore - $aScore;
    });
}

// MEMBER STATS
$activeMembers = $db->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'doctor' AND is_active = 1")->fetch_assoc()['count'];
$verifiedMembers = $db->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'doctor' AND is_verified = 1")->fetch_assoc()['count'];
$newThisMonth = $db->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'doctor' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetch_assoc()['count'];

// LATEST JOURNALS
$latestJournalsFeed = $db->query("
    SELECT j.*, s.name as specialty_name 
    FROM journals j 
    JOIN specialties s ON j.specialty_id = s.id 
    WHERE j.status = 'approved' 
    ORDER BY j.created_at DESC LIMIT 15
")->fetch_all(MYSQLI_ASSOC);

$featuredJournal = !empty($latestJournalsFeed) ? $latestJournalsFeed[0] : null;

// Build the featured PDF URL server-side
$featuredPdfUrl = $featuredJournal
    ? '/reader.php?type=journal&id=' . intval($featuredJournal['id'])
    : '';

$latestJournals = $db->query("
    SELECT j.*, s.name as specialty_name 
    FROM journals j 
    JOIN specialties s ON j.specialty_id = s.id 
    WHERE j.status = 'approved' 
    ORDER BY j.created_at DESC LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

$latestBooks = $db->query("
    SELECT b.*, s.name as specialty_name 
    FROM books b 
    JOIN specialties s ON b.specialty_id = s.id 
    WHERE b.status = 'approved' 
    ORDER BY b.created_at DESC LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

// STATS
$totalJournals = $db->query("SELECT COUNT(*) as count FROM journals WHERE status = 'approved'")->fetch_assoc()['count'];
$totalBooks = $db->query("SELECT COUNT(*) as count FROM books WHERE status = 'approved'")->fetch_assoc()['count'];
$totalVolumes = $db->query("SELECT COUNT(DISTINCT CONCAT(volume, '-', issue)) as count FROM journals WHERE status = 'approved' AND volume IS NOT NULL AND volume != ''")->fetch_assoc()['count'];

$stats = [
    'books' => $totalBooks,
    'journals' => $totalJournals,
    'volumes' => $totalVolumes,
    'members' => $activeMembers,
];

// TOP SPECIALTIES
$topSpecialties = $db->query("
    SELECT s.id, s.name,
        ((SELECT COUNT(*) FROM books WHERE specialty_id = s.id AND status = 'approved') + 
         (SELECT COUNT(*) FROM journals WHERE specialty_id = s.id AND status = 'approved')) as total_count
    FROM specialties s
    WHERE s.status = 1
    HAVING total_count > 0
    ORDER BY total_count DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BJDVL - HOME</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cambria&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Cambria', Georgia, serif;
            background: #f5f7fa;
            color: #1a1a2e;
        }

        .main-wrapper { padding: 25px 0 10px; min-height: calc(100vh - 140px); }

        .equal-height-row {
            display: flex;
            align-items: stretch;
        }
        .equal-height-row > [class*="col-"] { display: flex; }
        .equal-height-row > [class*="col-"] > * { width: 100%; }

        /* ============================================
           LEFT SIDEBAR
           ============================================ */
        .members-sidebar {
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #eef1f5;
            padding: 20px 18px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
        }

        .bjdvl-logo-block {
            text-align: center;
            padding: 24px 12px;
            background: linear-gradient(180deg, #f8fafc 0%, #eef4fb 100%);
            border-radius: 12px;
            border: 1px solid #e3ebf4;
            margin-bottom: 16px;
        }
        .bjdvl-logo-block .bjdvl-logo-img {
            height: 100px;
            width: auto;
            max-width: 100%;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        .member-trio {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            margin-bottom: 14px;
        }
        .trio-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px 6px;
            text-align: center;
            border: 1px solid #eef1f5;
            transition: all 0.2s ease;
        }
        .trio-item:hover { border-color: #0d6efd; background: #f0f7ff; }
        .trio-item .num { font-size: 1.15rem; font-weight: 700; line-height: 1.2; }
        .trio-item .lbl { font-size: 0.63rem; color: #6c757d; margin-top: 2px; line-height: 1.2; }
        .trio-item.verified .num { color: #198754; }
        .trio-item.new .num { color: #f39c12; }
        .trio-item.active .num { color: #0d6efd; }

        .platform-stats {
            padding-top: 10px;
            border-top: 1px solid #eef1f5;
            margin-top: auto;
        }
        .platform-stat-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f5f7fa;
        }
        .platform-stat-row:last-child { border-bottom: none; }
        .platform-stat-row .lbl {
            font-size: 0.88rem;
            color: #4a4a5e;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .platform-stat-row .lbl i { width: 18px; font-size: 0.85rem; }
        .platform-stat-row .val { font-size: 0.9rem; font-weight: 700; color: #1a1a2e; }

        /* ============================================
           JOURNALS FEED
           ============================================ */
        .journals-feed {
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #eef1f5;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
        }

        .journals-feed .feed-header {
            background: linear-gradient(135deg, #198754, #146c43);
            padding: 14px 20px;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        .journals-feed .feed-header h5 {
            font-weight: 700;
            font-size: 1.05rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .journals-feed .feed-header .live-dot {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.68rem;
            background: rgba(255,255,255,0.2);
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        .journals-feed .feed-header .live-dot::before {
            content: '';
            width: 7px;
            height: 7px;
            background: #4ade80;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.4; } }

        .feed-split {
            display: grid;
            grid-template-columns: 65% 35%;
            flex: 1;
            min-height: 0;
            overflow: hidden;
        }

        /* ---- LEFT 65%: Real PDF Page Preview ---- */
        .feed-preview {
            padding: 14px;
            background: linear-gradient(180deg, #f0f4f0 0%, #dce8dc 100%);
            border-right: 1px solid #eef1f5;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            min-height: 320px;
        }
        .feed-preview .preview-label {
            font-size: 0.66rem;
            font-weight: 700;
            color: #198754;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .feed-preview .preview-label::before {
            content: '';
            width: 4px;
            height: 4px;
            background: #198754;
            border-radius: 50%;
        }

        .book-stage {
            position: relative;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex: 1;
            min-height: 0;
        }

        .book-nav-btn {
            flex-shrink: 0;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: none;
            background: #ffffff;
            color: #198754;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            transition: all 0.2s ease;
            z-index: 5;
        }
        .book-nav-btn:hover {
            background: #198754;
            color: #fff;
            transform: scale(1.08);
            box-shadow: 0 6px 16px rgba(25,135,84,0.35);
        }
        .book-nav-btn:active { transform: scale(0.98); }
        .book-nav-btn:disabled {
            opacity: 0.35;
            cursor: not-allowed;
            background: #f5f5f5;
            color: #adb5bd;
            box-shadow: none;
        }
        .book-nav-btn:disabled:hover {
            background: #f5f5f5;
            color: #adb5bd;
            transform: none;
            box-shadow: none;
        }

        .open-book {
            position: relative;
            flex: 1;
            max-width: 500px;
            aspect-ratio: 4 / 3;
            display: flex;
            border-radius: 4px;
            overflow: hidden;
            box-shadow:
                0 12px 32px rgba(0,0,0,0.18),
                0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            background: #ffffff;
        }
        .open-book:hover { transform: translateY(-3px); }
        .open-book::after {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 50%;
            width: 8px;
            transform: translateX(-50%);
            background: linear-gradient(90deg,
                rgba(0,0,0,0.18) 0%,
                rgba(0,0,0,0.05) 40%,
                rgba(0,0,0,0.05) 60%,
                rgba(0,0,0,0.18) 100%);
            pointer-events: none;
            z-index: 3;
        }

        .book-page {
            flex: 1;
            background: #ffffff;
            overflow: hidden;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 200px;
        }
        .book-page canvas {
            max-width: 100%;
            max-height: 100%;
            display: block;
            object-fit: contain;
        }
        .book-page-left { border-right: 1px solid rgba(0,0,0,0.04); background: #fdfdfb; }
        .book-page-right { background: #ffffff; }

        .book-loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #adb5bd;
            gap: 8px;
            padding: 16px;
            text-align: center;
            position: absolute;
            inset: 0;
        }
        .book-loading i {
            font-size: 1.6rem;
            color: #198754;
        }
        .book-loading span {
            font-size: 0.72rem;
            color: #6c757d;
        }

        .book-page .page-num-badge {
            position: absolute;
            bottom: 6px;
            font-size: 0.6rem;
            color: #6c757d;
            background: rgba(255,255,255,0.9);
            padding: 2px 8px;
            border-radius: 8px;
            font-weight: 600;
            z-index: 4;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .book-page-left .page-num-badge { left: 8px; }
        .book-page-right .page-num-badge { right: 8px; }

        .page-indicator {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 10px;
        }
        .page-indicator .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #cbd5c9;
            transition: all 0.3s ease;
        }
        .page-indicator .dot.active {
            background: #198754;
            width: 20px;
            border-radius: 3px;
        }

        .preview-read-btn {
            margin-top: 10px;
            width: 100%;
            max-width: 240px;
            font-size: 0.82rem;
            font-weight: 700;
            padding: 10px 14px;
            border: none;
            border-radius: 6px;
            background: linear-gradient(135deg, #198754, #146c43);
            color: #fff;
            transition: all 0.2s ease;
            cursor: pointer;
            flex-shrink: 0;
        }
        .preview-read-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(25,135,84,0.35);
        }
        .preview-read-btn i { margin-right: 6px; }

        .preview-empty {
            text-align: center;
            color: #adb5bd;
            padding: 40px 20px;
        }
        .preview-empty i { font-size: 3rem; color: #dee2e6; margin-bottom: 12px; }
        .preview-empty p { font-size: 0.85rem; margin: 0; }

        /* ---- RIGHT 35%: Journals List ---- */
        .feed-list {
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }
        .feed-list-header {
            padding: 12px 16px 8px;
            font-size: 0.68rem;
            font-weight: 700;
            color: #6c757d;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            border-bottom: 1px solid #eef1f5;
            flex-shrink: 0;
            background: #fcfdfe;
        }
        .feed-list-header i { color: #198754; margin-right: 6px; }

        .feed-body {
            overflow-y: auto;
            flex: 1;
            min-height: 0;
        }
        .feed-body::-webkit-scrollbar { width: 5px; }
        .feed-body::-webkit-scrollbar-track { background: #f8f9fa; }
        .feed-body::-webkit-scrollbar-thumb { background: #dee2e6; border-radius: 3px; }
        .feed-body::-webkit-scrollbar-thumb:hover { background: #adb5bd; }

        .feed-item {
            display: flex;
            gap: 10px;
            padding: 12px 14px;
            border-bottom: 1px solid #f5f7fa;
            transition: background 0.2s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        .feed-item:last-child { border-bottom: none; }
        .feed-item:hover { background: #f8f9fa; text-decoration: none; color: inherit; }

        .feed-item .feed-icon {
            flex-shrink: 0;
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, #e8f5e9, #d1fae5);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #198754;
            font-size: 0.9rem;
            overflow: hidden;
        }
        .feed-item .feed-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .feed-item .feed-content { flex: 1; min-width: 0; }
        .feed-item .feed-content h6 {
            font-size: 0.82rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 2px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.3;
        }
        .feed-item .feed-content .journal-source {
            font-size: 0.68rem;
            color: #198754;
            font-weight: 600;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .feed-item .feed-content .feed-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            font-size: 0.6rem;
            color: #6c757d;
        }
        .feed-item .feed-content .feed-meta span {
            background: #f0f4f8;
            padding: 1px 6px;
            border-radius: 6px;
        }
        .feed-item .feed-content .feed-meta .specialty-chip {
            background: #e8f0fe;
            color: #0d6efd;
        }

        .feed-footer {
            padding: 10px 16px;
            background: #f8f9fa;
            border-top: 1px solid #eef1f5;
            text-align: center;
            flex-shrink: 0;
        }
        .feed-footer a {
            font-size: 0.8rem;
            font-weight: 600;
            color: #198754;
            text-decoration: none;
        }
        .feed-footer a:hover { text-decoration: underline; }

        .feed-empty {
            padding: 30px 16px;
            text-align: center;
            color: #6c757d;
        }
        .feed-empty i { font-size: 2rem; color: #dee2e6; margin-bottom: 8px; }
        .feed-empty p { font-size: 0.8rem; }

        /* ============================================
           SEARCH SECTION
           ============================================ */
        .search-section { margin-top: 22px; }
        .search-section .search-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: #1a1a2e;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .search-section .search-title i { color: #0d6efd; }

        .search-box {
            display: flex;
            gap: 8px;
            background: #ffffff;
            border-radius: 10px;
            padding: 5px;
            border: 1px solid #eef1f5;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .search-box:focus-within {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13,110,253,0.08);
        }
        .search-box .search-input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 10px 16px;
            font-size: 1rem;
            outline: none;
            color: #1a1a2e;
        }
        .search-box .search-input::placeholder { color: #adb5bd; }
        .search-box .search-btn {
            padding: 10px 24px;
            border: none;
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            color: #fff;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            cursor: pointer;
            white-space: nowrap;
        }
        .search-box .search-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 10px rgba(13,110,253,0.2);
        }
        .search-clear-btn {
            padding: 10px 16px;
            border: none;
            background: transparent;
            color: #6c757d;
            font-size: 0.95rem;
            cursor: pointer;
            text-decoration: none;
        }
        .search-clear-btn:hover { color: #dc3545; }

        .quick-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 12px; }
        .quick-chips .chip {
            font-size: 0.75rem;
            padding: 4px 12px;
            border-radius: 20px;
            background: #ffffff;
            border: 1px solid #eef1f5;
            color: #4a4a5e;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .quick-chips .chip:hover {
            background: #e8f0fe;
            border-color: #0d6efd;
            color: #0d6efd;
            text-decoration: none;
        }
        .quick-chips .chip .chip-count {
            font-size: 0.65rem;
            background: #f0f4f8;
            padding: 0 6px;
            border-radius: 8px;
            color: #6c757d;
        }

        .search-results {
            margin-top: 14px;
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #eef1f5;
            padding: 16px;
        }
        .search-results .result-count {
            font-size: 0.95rem;
            color: #6c757d;
            margin-bottom: 12px;
        }
        .search-results .result-count strong { color: #1a1a2e; }

        .search-result-item {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 6px;
            border-left: 3px solid #0d6efd;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }
        .search-result-item:hover { background: #f0f4f8; }
        .search-result-item .result-info { flex: 1; }
        .search-result-item .result-info h6 {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 1px;
        }
        .search-result-item .result-info .result-meta {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .search-result-item .result-info .result-meta .badge-type {
            padding: 1px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .search-result-item .result-actions .btn {
            font-size: 0.8rem;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: 600;
        }

        .full-width-section {
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid #eef1f5;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }
        .section-header h4 {
            font-weight: 700;
            font-size: 1.3rem;
            color: #1a1a2e;
            margin: 0;
        }
        .section-header h4 i { margin-right: 8px; }
        .section-header a {
            font-size: 0.95rem;
            color: #0d6efd;
            text-decoration: none;
            font-weight: 500;
        }
        .section-header a:hover { text-decoration: underline; }

        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
        }

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
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            border-color: #0d6efd;
        }
        .item-card .cover {
            height: 160px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            padding: 8px;
            flex-shrink: 0;
        }
        .item-card .cover img { max-height: 100%; width: auto; object-fit: contain; }
        .item-card .cover .placeholder { font-size: 2.8rem; color: #ced4da; }
        .item-card .cover .specialty-tag {
            position: absolute;
            top: 6px;
            right: 6px;
            background: rgba(0,0,0,0.6);
            color: #fff;
            font-size: 0.6rem;
            padding: 1px 8px;
            border-radius: 10px;
            font-weight: 500;
        }
        .item-card .info { padding: 10px 12px 6px; flex: 1; }
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
        .item-card .info .author { font-size: 0.78rem; color: #6c757d; }
        .item-card .info .meta {
            font-size: 0.68rem;
            color: #adb5bd;
            display: flex;
            gap: 4px;
            margin-top: 3px;
            flex-wrap: wrap;
        }
        .item-card .info .meta span {
            background: #f0f4f8;
            padding: 1px 8px;
            border-radius: 8px;
        }
        .item-card .actions {
            padding: 4px 12px 10px;
            display: flex;
            gap: 4px;
            flex-shrink: 0;
        }
        .item-card .actions .btn {
            flex: 1;
            font-size: 0.75rem;
            padding: 5px 8px;
            border-radius: 4px;
            font-weight: 600;
        }

        .item-card.journal .cover {
            height: 130px;
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        }
        .item-card.journal .cover .journal-icon { font-size: 2.4rem; color: #198754; }
        .item-card.journal .cover .journal-name-tag {
            position: absolute;
            bottom: 6px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(25,135,84,0.9);
            color: #fff;
            font-size: 0.6rem;
            padding: 2px 10px;
            border-radius: 10px;
            font-weight: 500;
            white-space: nowrap;
            max-width: 90%;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .item-card.journal:hover { border-color: #198754; }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            width: 100%;
        }
        .empty-state .icon { font-size: 2.5rem; color: #dee2e6; margin-bottom: 10px; }
        .empty-state h5 { font-weight: 700; color: #1a1a2e; font-size: 1.1rem; }
        .empty-state p { color: #6c757d; font-size: 0.95rem; }

        /* ============================================
           VIEW-ONLY READER MODAL
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
        .reader-modal .reader-header .btn-close { filter: invert(1); opacity: 0.8; }
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
        @media (max-width: 992px) {
            .items-grid { grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); }
            .search-box { flex-wrap: wrap; }
            .search-box .search-input { flex: 1 1 100%; padding: 8px 12px; }
            .search-box .search-btn { flex: 1; padding: 8px 14px; }
            .open-book { max-width: 400px; }
        }

        @media (max-width: 768px) {
            .items-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; }
            .item-card .cover { height: 140px; }
            .item-card.journal .cover { height: 110px; }
            .search-result-item { flex-direction: column; align-items: flex-start; }
            .search-result-item .result-actions { width: 100%; }
            .search-result-item .result-actions .btn { width: 100%; }
            .reader-modal .modal-dialog {
                max-width: 100vw;
                width: 100vw;
                margin: 0;
                height: 100vh;
            }
            .reader-modal .modal-content { border-radius: 0; }
            .feed-split { grid-template-columns: 1fr; }
            .feed-preview {
                border-right: none;
                border-bottom: 1px solid #eef1f5;
                padding: 14px;
                min-height: 280px;
            }
            .open-book { max-width: 360px; }
        }

        @media (max-width: 576px) {
            .items-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
            .item-card .cover { height: 120px; }
            .item-card.journal .cover { height: 95px; }
            .item-card .info h6 { font-size: 0.82rem; }
            .item-card .actions .btn { font-size: 0.65rem; padding: 3px 4px; }
            .section-header h4 { font-size: 1.05rem; }
            .search-section .search-title { font-size: 0.95rem; }
            .search-box .search-input { font-size: 0.9rem; padding: 6px 10px; }
            .search-box .search-btn { font-size: 0.85rem; padding: 6px 10px; }
            .member-trio { gap: 4px; }
            .trio-item { padding: 8px 4px; }
            .trio-item .num { font-size: 1rem; }
            .trio-item .lbl { font-size: 0.55rem; }
            .bjdvl-logo-block .bjdvl-logo-img { height: 80px; }
            .main-wrapper { padding: 15px 0 10px; }
            .container { padding-left: 10px; padding-right: 10px; }
            .feed-item { padding: 10px 12px; }
            .feed-item .feed-icon { width: 30px; height: 30px; font-size: 0.8rem; }
            .feed-item .feed-content h6 { font-size: 0.78rem; }
            .open-book { max-width: 280px; }
            .book-nav-btn { width: 32px; height: 32px; font-size: 0.85rem; }
        }

        @media (max-width: 400px) {
            .items-grid { grid-template-columns: repeat(2, 1fr); gap: 6px; }
            .item-card .cover { height: 100px; }
            .item-card.journal .cover { height: 80px; }
            .item-card .info h6 { font-size: 0.78rem; }
            .trio-item .num { font-size: 0.9rem; }
            .trio-item .lbl { font-size: 0.5rem; }
            .open-book { max-width: 240px; }
            .book-nav-btn { width: 28px; height: 28px; font-size: 0.75rem; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/public_nav.php'; ?>

    <div class="main-wrapper">
        <div class="container">
            <div class="row g-3 equal-height-row">

                <!-- ============================================
                     LEFT SIDEBAR
                     ============================================ -->
                <div class="col-lg-4">
                    <div class="members-sidebar">

                        <div class="bjdvl-logo-block">
                            <img src="/assets/images/logo.png"
                                 alt="Logo"
                                 class="bjdvl-logo-img"
                                 onerror="this.style.display='none'">
                        </div>

                        <div class="member-trio">
                            <div class="trio-item verified">
                                <div class="num"><?php echo number_format($verifiedMembers); ?></div>
                                <div class="lbl">Verified</div>
                            </div>
                            <div class="trio-item new">
                                <div class="num"><?php echo number_format($newThisMonth); ?></div>
                                <div class="lbl">New This Month</div>
                            </div>
                            <div class="trio-item active">
                                <div class="num"><?php echo number_format($activeMembers); ?></div>
                                <div class="lbl">Active Members</div>
                            </div>
                        </div>

                        <div class="platform-stats">
                            <div class="platform-stat-row">
                                <span class="lbl"><i class="fas fa-newspaper text-success"></i> Total Journals</span>
                                <span class="val"><?php echo number_format($stats['journals']); ?></span>
                            </div>
                            <div class="platform-stat-row">
                                <span class="lbl"><i class="fas fa-layer-group text-info"></i> Total Volumes</span>
                                <span class="val"><?php echo number_format($stats['volumes']); ?></span>
                            </div>
                            <div class="platform-stat-row">
                                <span class="lbl"><i class="fas fa-book text-primary"></i> Total Books</span>
                                <span class="val"><?php echo number_format($stats['books']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================
                     RIGHT: Latest Journal Publications
                     ============================================ -->
                <div class="col-lg-8">
                    <div class="journals-feed">
                        <div class="feed-header">
                            <h5><i class="fas fa-rss"></i> Latest Journal Publications</h5>
                            <span class="live-dot">Live Feed</span>
                        </div>

                        <div class="feed-split">

                            <!-- LEFT 65%: Real PDF Page Preview -->
                            <div class="feed-preview">
                                <div class="preview-label">Featured Preview</div>

                                <?php if ($featuredJournal && !empty($featuredPdfUrl)): ?>
                                <div class="book-stage">
                                    <button type="button" class="book-nav-btn" id="bookPrevBtn"
                                            onclick="changeBookPage(-1)" title="Previous Page">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>

                                    <div class="open-book" id="openBook"
                                         onclick='openReader("journal", <?php echo $featuredJournal["id"]; ?>, <?php echo json_encode($featuredJournal["title"]); ?>)'>
                                        <div class="book-page book-page-left">
                                            <div class="book-loading" id="leftPageLoading">
                                                <i class="fas fa-spinner fa-spin"></i>
                                                <span>Loading page...</span>
                                            </div>
                                            <canvas id="leftPageCanvas"></canvas>
                                            <div class="page-num-badge" id="leftPageNum">1</div>
                                        </div>
                                        <div class="book-page book-page-right">
                                            <div class="book-loading" id="rightPageLoading">
                                                <i class="fas fa-spinner fa-spin"></i>
                                                <span>Loading page...</span>
                                            </div>
                                            <canvas id="rightPageCanvas"></canvas>
                                            <div class="page-num-badge" id="rightPageNum">2</div>
                                        </div>
                                    </div>

                                    <button type="button" class="book-nav-btn" id="bookNextBtn"
                                            onclick="changeBookPage(1)" title="Next Page">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>

                                <div class="page-indicator" id="pageIndicator">
                                    <span class="dot active"></span>
                                    <span class="dot"></span>
                                    <span class="dot"></span>
                                </div>

                                <button type="button" class="preview-read-btn"
                                        onclick='openReader("journal", <?php echo $featuredJournal["id"]; ?>, <?php echo json_encode($featuredJournal["title"]); ?>)'>
                                    <i class="fas fa-book-open"></i> Read Full Journal
                                </button>
                                <?php else: ?>
                                <div class="preview-empty">
                                    <i class="fas fa-inbox d-block"></i>
                                    <p>No journal available</p>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- RIGHT 35%: Journals List -->
                            <div class="feed-list">
                                <div class="feed-list-header">
                                    <i class="fas fa-list-ul"></i> Recent Journals
                                </div>

                                <div class="feed-body">
                                    <?php 
                                    $recentThree = array_slice($latestJournalsFeed, 0, 3);
                                    if (empty($recentThree)): 
                                    ?>
                                        <div class="feed-empty">
                                            <i class="fas fa-inbox d-block"></i>
                                            <p>No journals published yet.</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($recentThree as $journal): ?>
                                        <a class="feed-item" href="javascript:void(0)"
                                           onclick='openReader("journal", <?php echo $journal["id"]; ?>, <?php echo json_encode($journal["title"]); ?>)'>
                                            <div class="feed-icon">
                                                <?php if (!empty($journal['cover_image'])): ?>
                                                    <img src="/uploads/journals/<?php echo htmlspecialchars($journal['cover_image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($journal['title']); ?>"
                                                         loading="lazy">
                                                <?php else: ?>
                                                    <i class="fas fa-file-medical-alt"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="feed-content">
                                                <h6><?php echo htmlspecialchars($journal['title']); ?></h6>
                                                <div class="journal-source">
                                                    <?php echo htmlspecialchars($journal['journal_name']); ?>
                                                </div>
                                                <div class="feed-meta">
                                                    <?php if (!empty($journal['date'])): ?>
                                                    <span><i class="far fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($journal['date'])); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($journal['volume'])): ?>
                                                    <span>Vol <?php echo htmlspecialchars($journal['volume']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="feed-footer">
                                    <a href="/browse">
                                        <i class="fas fa-arrow-right"></i> View All
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SEARCH SECTION -->
            <div class="search-section">
                <div class="search-title">
                    <i class="fas fa-search"></i> Search Medical Resources
                </div>

                <form method="GET" action="/" class="search-box">
                    <input type="text" class="search-input" name="search"
                           placeholder="Search books, journals, authors, DOI, or topics..."
                           value="<?php echo htmlspecialchars($searchQuery); ?>"
                           autocomplete="off">
                    <?php if (!empty($searchQuery)): ?>
                    <a href="/" class="search-clear-btn" title="Clear search">
                        <i class="fas fa-times"></i> Clear
                    </a>
                    <?php endif; ?>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                </form>

                <?php if (!empty($topSpecialties) && empty($searchQuery)): ?>
                <div class="quick-chips">
                    <span style="font-size: 0.8rem; color: #6c757d; align-self: center; margin-right: 4px;">
                        <i class="fas fa-bolt" style="color: #f39c12;"></i> Popular:
                    </span>
                    <?php foreach ($topSpecialties as $spec): ?>
                    <a href="/browse/<?php echo $spec['id']; ?>" class="chip">
                        <?php echo htmlspecialchars($spec['name']); ?>
                        <span class="chip-count"><?php echo $spec['total_count']; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($searchPerformed): ?>
                <div class="search-results">
                    <?php if (!empty($searchResults)): ?>
                        <div class="result-count">
                            Found <strong><?php echo count($searchResults); ?></strong> result(s) for "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>"
                        </div>

                        <?php foreach ($searchResults as $result): ?>
                        <div class="search-result-item">
                            <div class="result-info">
                                <h6>
                                    <?php if ($result['item_type'] == 'book'): ?>
                                        <i class="fas fa-book text-primary"></i>
                                    <?php else: ?>
                                        <i class="fas fa-newspaper text-success"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($result['title']); ?>
                                </h6>
                                <div class="result-meta">
                                    <?php if ($result['item_type'] == 'book'): ?>
                                        <span class="badge-type book">Book</span>
                                        <?php echo htmlspecialchars($result['author']); ?>
                                        <?php if (!empty($result['year'])): ?>&bull; <?php echo $result['year']; ?><?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge-type journal">Journal</span>
                                        <?php echo htmlspecialchars($result['journal_name']); ?>
                                        <?php if (!empty($result['date'])): ?>&bull; <?php echo date('M Y', strtotime($result['date'])); ?><?php endif; ?>
                                    <?php endif; ?>
                                    &bull; <span class="text-muted"><?php echo htmlspecialchars($result['specialty_name']); ?></span>
                                </div>
                            </div>
                            <div class="result-actions">
                                <?php if ($result['item_type'] == 'book'): ?>
                                    <button type="button" class="btn btn-primary btn-sm"
                                            onclick='openReader("book", <?php echo $result["id"]; ?>, <?php echo json_encode($result["title"]); ?>)'>
                                        <i class="fas fa-book-open"></i> Read Now
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-success btn-sm"
                                            onclick='openReader("journal", <?php echo $result["id"]; ?>, <?php echo json_encode($result["title"]); ?>)'>
                                        <i class="fas fa-book-open"></i> Read Now
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                    <?php else: ?>
                        <div class="search-no-results">
                            <div class="icon"><i class="fas fa-search"></i></div>
                            <h5>No results found</h5>
                            <p>We couldn't find any matches for "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>"</p>
                            <div class="suggestions">
                                <span class="text-muted">Try:</span>
                                <a href="/" class="btn btn-outline-secondary btn-sm">Clear Search</a>
                                <a href="/browse" class="btn btn-outline-primary btn-sm">Browse All Resources</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- FULL WIDTH SECTION -->
            <div class="full-width-section">
                <div class="section-header">
                    <h4><i class="fas fa-newspaper text-success"></i> Latest Journals</h4>
                    <a href="/browse" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                </div>

                <?php if (empty($latestJournals)): ?>
                    <div class="empty-state">
                        <div class="icon"><i class="fas fa-newspaper"></i></div>
                        <h5>No Journals Available</h5>
                        <p>No journals are available at the moment.</p>
                    </div>
                <?php else: ?>
                <div class="items-grid">
                    <?php foreach ($latestJournals as $journal): ?>
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
                            <span class="specialty-tag"><?php echo htmlspecialchars($journal['specialty_name']); ?></span>
                            <span class="journal-name-tag"><?php echo htmlspecialchars($journal['journal_name']); ?></span>
                        </div>
                        <div class="info">
                            <h6><?php echo htmlspecialchars($journal['title']); ?></h6>
                            <div class="meta">
                                <?php if ($journal['date']): ?>
                                <span><i class="far fa-calendar-alt"></i> <?php echo date('M Y', strtotime($journal['date'])); ?></span>
                                <?php endif; ?>
                                <?php if ($journal['volume']): ?>
                                <span>Vol <?php echo $journal['volume']; ?></span>
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

                <div class="section-header" style="margin-top: 32px;">
                    <h4><i class="fas fa-book text-primary"></i> Latest Books</h4>
                    <a href="/browse" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                </div>

                <?php if (empty($latestBooks)): ?>
                    <div class="empty-state">
                        <div class="icon"><i class="fas fa-book"></i></div>
                        <h5>No Books Available</h5>
                        <p>No books are available at the moment.</p>
                    </div>
                <?php else: ?>
                <div class="items-grid">
                    <?php foreach ($latestBooks as $book): ?>
                    <div class="item-card">
                        <div class="cover">
                            <?php if ($book['cover_image']): ?>
                            <img src="/uploads/books/<?php echo $book['cover_image']; ?>"
                                 alt="<?php echo htmlspecialchars($book['title']); ?>" loading="lazy">
                            <?php else: ?>
                            <div class="placeholder"><i class="fas fa-book"></i></div>
                            <?php endif; ?>
                            <span class="specialty-tag"><?php echo htmlspecialchars($book['specialty_name']); ?></span>
                        </div>
                        <div class="info">
                            <h6><?php echo htmlspecialchars($book['title']); ?></h6>
                            <div class="author"><?php echo htmlspecialchars($book['author']); ?></div>
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

    <!-- READER MODAL -->
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
                        <p style="font-family: 'Cambria', Georgia, serif; font-size: 1rem;">Loading document...</p>
                    </div>
                </div>
                <div class="reader-footer">
                    <i class="fas fa-shield-alt"></i>
                    <span>Protected view-only mode. Download, print, copy, and right-click are disabled.</span>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer-main" style="background: #ffffff; padding: 16px 0; border-top: 1px solid #eef1f5; margin-top: 12px;">
        <div class="container">
            <p style="margin: 0; color: #1a1a2e; font-size: 0.9rem; text-align: center;">
                <strong style="color: #0d6efd;">BJDVL</strong> &copy; <?php echo date('Y'); ?>
                <span style="color: #6c757d;">v2.0</span> &bull;
                <span style="color: #6c757d;">Powered by UniMed UniHealth Group</span>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- PDF.js from CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

    <!-- IMPORTANT: Pass PDF URL directly from PHP as a JS variable -->
    <script>
        window.FEATURED_PDF_URL = <?php echo json_encode($featuredPdfUrl); ?>;
        window.FEATURED_JOURNAL_ID = <?php echo json_encode($featuredJournal ? $featuredJournal['id'] : null); ?>;
        window.FEATURED_JOURNAL_TITLE = <?php echo json_encode($featuredJournal ? $featuredJournal['title'] : ''); ?>;
    </script>

    <script>
    (function() {
        'use strict';

        const DEBUG = true;
        function log(...args) { if (DEBUG) console.log('[PDF Preview]', ...args); }
        function err(...args) { console.error('[PDF Preview]', ...args); }

        let pdfDoc = null;
        let currentSpread = 0;
        let totalPages = 0;
        const pagesPerSpread = 2;
        const maxSpreads = 3;

        // ============================================
        // BOOT
        // ============================================
        window.addEventListener('load', function () {
            log('Window fully loaded');
            initPdfPreview();
            initReaderModal();
            initSearch();
        });

        function initSearch() {
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.addEventListener('keypress', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.form.submit();
                    }
                });
            }
        }

        function initPdfPreview() {
            const pdfUrl = window.FEATURED_PDF_URL;
            log('Featured PDF URL:', pdfUrl);

            if (!pdfUrl) {
                err('FEATURED_PDF_URL is empty — no journal to preview');
                showErrorInBook('No journal selected');
                return;
            }

            if (typeof pdfjsLib === 'undefined') {
                err('pdfjsLib is NOT defined — CDN failed to load');
                showErrorInBook('PDF.js library failed to load');
                return;
            }

            log('PDF.js version:', pdfjsLib.version || 'unknown');

            pdfjsLib.GlobalWorkerOptions.workerSrc =
                'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            log('Worker:', pdfjsLib.GlobalWorkerOptions.workerSrc);

            loadPdf(pdfUrl);
        }

        // ============================================
        // LOAD PDF
        // ============================================
        function loadPdf(pdfUrl) {
            log('Fetching PDF...');

            fetch(pdfUrl, { method: 'HEAD' })
                .then(function (res) {
                    log('HEAD response status:', res.status, 'content-type:', res.headers.get('content-type'));
                    if (!res.ok) {
                        throw new Error('Server returned ' + res.status);
                    }
                    return pdfjsLib.getDocument(pdfUrl).promise;
                })
                .then(function (doc) {
                    pdfDoc = doc;
                    totalPages = doc.numPages;
                    log('✅ PDF loaded. Pages:', totalPages);
                    renderSpread(0);
                    updateNavButtons();
                })
                .catch(function (error) {
                    err('❌ PDF failed:', error);
                    showErrorInBook('PDF error: ' + (error.message || 'Unknown'));
                });
        }

        function showErrorInBook(message) {
            const html = `
                <div style="text-align:center; padding:16px;">
                    <i class="fas fa-exclamation-triangle" style="font-size:1.8rem; color:#dc3545; margin-bottom:8px;"></i>
                    <div style="font-size:0.72rem; color:#6c757d; line-height:1.4;">${message}</div>
                </div>`;
            ['leftPageLoading', 'rightPageLoading'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) { el.innerHTML = html; el.style.display = 'flex'; }
            });
        }

        // ============================================
        // RENDER PAGE
        // ============================================
        function renderPage(pageNum, canvasId, loadingId) {
            const canvas = document.getElementById(canvasId);
            const loadingEl = document.getElementById(loadingId);

            if (!canvas || !pdfDoc) return Promise.resolve();

            if (pageNum < 1 || pageNum > totalPages) {
                canvas.style.display = 'none';
                if (loadingEl) {
                    loadingEl.innerHTML = '<i class="fas fa-file" style="color:#dee2e6;font-size:1.5rem;"></i>';
                    loadingEl.style.display = 'flex';
                }
                return Promise.resolve();
            }

            return pdfDoc.getPage(pageNum).then(function (page) {
                const parent = canvas.parentElement;
                let cw = parent.clientWidth;
                let ch = parent.clientHeight;

                if (!cw || cw < 50) cw = 300;
                if (!ch || ch < 50) ch = 400;

                const viewport = page.getViewport({ scale: 1 });
                const scaleX = cw / viewport.width;
                const scaleY = ch / viewport.height;
                const scale = Math.min(scaleX, scaleY) * 0.98;

                const scaledViewport = page.getViewport({ scale: scale });
                const outputScale = window.devicePixelRatio || 1;

                canvas.width = Math.floor(scaledViewport.width * outputScale);
                canvas.height = Math.floor(scaledViewport.height * outputScale);
                canvas.style.width = Math.floor(scaledViewport.width) + 'px';
                canvas.style.height = Math.floor(scaledViewport.height) + 'px';
                canvas.style.display = 'block';

                const ctx = canvas.getContext('2d');
                const renderContext = {
                    canvasContext: ctx,
                    viewport: scaledViewport,
                    transform: outputScale !== 1 ? [outputScale, 0, 0, outputScale, 0, 0] : null
                };

                return page.render(renderContext).promise.then(function () {
                    log('✅ Page', pageNum, 'rendered');
                    if (loadingEl) loadingEl.style.display = 'none';
                });
            }).catch(function (e) {
                err('Render page', pageNum, 'failed:', e);
                if (loadingEl) {
                    loadingEl.innerHTML = '<i class="fas fa-exclamation-triangle" style="color:#dc3545;"></i>';
                    loadingEl.style.display = 'flex';
                }
            });
        }

        // ============================================
        // RENDER SPREAD
        // ============================================
        function renderSpread(spreadIndex) {
            if (!pdfDoc) return;

            const leftNum = spreadIndex * pagesPerSpread + 1;
            const rightNum = leftNum + 1;

            log('Rendering spread', spreadIndex, '→ pages', leftNum, '&', rightNum);

            const leftNumEl = document.getElementById('leftPageNum');
            const rightNumEl = document.getElementById('rightPageNum');
            if (leftNumEl) leftNumEl.textContent = leftNum <= totalPages ? leftNum : '-';
            if (rightNumEl) rightNumEl.textContent = rightNum <= totalPages ? rightNum : '-';

            ['leftPageLoading', 'rightPageLoading'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) {
                    el.innerHTML = '<i class="fas fa-spinner fa-spin" style="color:#198754;font-size:1.5rem;"></i>';
                    el.style.display = 'flex';
                }
            });

            const leftCanvas = document.getElementById('leftPageCanvas');
            const rightCanvas = document.getElementById('rightPageCanvas');
            if (leftCanvas) leftCanvas.style.display = 'none';
            if (rightCanvas) rightCanvas.style.display = 'none';

            Promise.all([
                renderPage(leftNum, 'leftPageCanvas', 'leftPageLoading'),
                renderPage(rightNum, 'rightPageCanvas', 'rightPageLoading')
            ]);
        }

        function updateNavButtons() {
            const prevBtn = document.getElementById('bookPrevBtn');
            const nextBtn = document.getElementById('bookNextBtn');
            const totalSpreads = Math.min(Math.ceil(totalPages / pagesPerSpread), maxSpreads);
            if (prevBtn) prevBtn.disabled = (currentSpread <= 0);
            if (nextBtn) nextBtn.disabled = (currentSpread >= totalSpreads - 1);
            document.querySelectorAll('#pageIndicator .dot').forEach(function (dot, i) {
                dot.classList.toggle('active', i === currentSpread);
            });
        }

        window.changeBookPage = function (direction) {
            const totalSpreads = Math.min(Math.ceil(totalPages / pagesPerSpread), maxSpreads);
            const next = currentSpread + direction;
            if (next < 0 || next >= totalSpreads) return;
            currentSpread = next;

            const book = document.querySelector('.open-book');
            if (book) {
                book.style.transition = 'transform 0.3s ease';
                book.style.transform = 'scale(0.96)';
                setTimeout(function () {
                    book.style.transform = '';
                    renderSpread(currentSpread);
                    updateNavButtons();
                }, 150);
            } else {
                renderSpread(currentSpread);
                updateNavButtons();
            }
        };

        let resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                if (pdfDoc) renderSpread(currentSpread);
            }, 300);
        });

        // ============================================
        // READER MODAL
        // ============================================
        let readerModal = null;

        window.openReader = function (type, id, title) {
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
                <div style="display:flex; align-items:center; justify-content:center; height:100%; color:#adb5bd; flex-direction:column; gap:12px;">
                    <i class="fas fa-spinner fa-spin" style="font-size:2.5rem;"></i>
                    <p style="font-family:'Cambria',Georgia,serif; font-size:1rem;">Loading document...</p>
                </div>`;

            readerModal.show();

            const pdfUrl = `/reader.php?type=${encodeURIComponent(type)}&id=${encodeURIComponent(id)}&t=${Date.now()}`;
            const viewerUrl = `/assets/pdfjs/web/viewer.html?file=${encodeURIComponent(pdfUrl)}#viewonly`;

            setTimeout(function () {
                bodyEl.innerHTML = `
                    <iframe src="${viewerUrl}" 
                            id="readerFrame"
                            oncontextmenu="return false;"
                            allowfullscreen="false"
                            referrerpolicy="no-referrer"
                            style="width:100%;height:100%;border:none;"></iframe>`;
            }, 250);
        };

        function initReaderModal() {
            const modalEl = document.getElementById('readerModal');
            if (!modalEl) return;

            modalEl.addEventListener('contextmenu', function (e) {
                e.preventDefault();
                return false;
            });

            document.addEventListener('keydown', function (e) {
                if (!modalEl.classList.contains('show')) return;
                if (e.ctrlKey || e.metaKey) {
                    const k = e.key.toLowerCase();
                    if (['p','s','c','a','u'].indexOf(k) !== -1) { e.preventDefault(); return false; }
                }
                if (e.key === 'F12') { e.preventDefault(); return false; }
                if ((e.ctrlKey || e.metaKey) && e.shiftKey) {
                    const k = e.key.toLowerCase();
                    if (['i','j','c'].indexOf(k) !== -1) { e.preventDefault(); return false; }
                }
            });
        }

        window.addEventListener('beforeprint', function (e) {
            const modalEl = document.getElementById('readerModal');
            if (modalEl && modalEl.classList.contains('show')) {
                e.preventDefault();
                alert('Printing is disabled in view-only mode.');
                return false;
            }
        });

    })();
    </script>
</body>
</html>