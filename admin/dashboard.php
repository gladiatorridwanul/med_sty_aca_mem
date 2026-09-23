<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireAdmin();

$db = Database::getInstance()->getConnection();

// ============================================
// EXISTING STATS
// ============================================
$stats = [
    'total_doctors' => $db->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'doctor'")->fetch_assoc()['count'],
    'pending_doctors' => $db->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'doctor' AND is_verified = 0")->fetch_assoc()['count'],
    'verified_doctors' => $db->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'doctor' AND is_verified = 1")->fetch_assoc()['count'],
    'total_books' => $db->query("SELECT COUNT(*) as count FROM books")->fetch_assoc()['count'],
    'pending_books' => $db->query("SELECT COUNT(*) as count FROM books WHERE status = 'pending'")->fetch_assoc()['count'],
    'total_journals' => $db->query("SELECT COUNT(*) as count FROM journals")->fetch_assoc()['count'],
    'pending_journals' => $db->query("SELECT COUNT(*) as count FROM journals WHERE status = 'pending'")->fetch_assoc()['count'],
    'pending_requests' => $db->query("SELECT COUNT(*) as count FROM supply_requests WHERE status = 'pending'")->fetch_assoc()['count'],
    'total_requests' => $db->query("SELECT COUNT(*) as count FROM supply_requests")->fetch_assoc()['count'],
    'pending_downloads' => $db->query("SELECT COUNT(*) as count FROM download_permissions WHERE status = 'pending'")->fetch_assoc()['count'],
];

// ============================================
// ✅ COMMITTEE STATS
// ============================================
$committeeStats = [
    'total_designations' => $db->query("SELECT COUNT(*) as count FROM designations WHERE status = 1")->fetch_assoc()['count'],
    'total_years' => $db->query("SELECT COUNT(*) as count FROM committee_years WHERE status = 1")->fetch_assoc()['count'],
    'total_members' => $db->query("SELECT COUNT(*) as count FROM committee_members WHERE status = 1")->fetch_assoc()['count'],
    'current_year' => null,
];

$currentYearRow = $db->query("SELECT year_label FROM committee_years WHERE is_current = 1 LIMIT 1")->fetch_assoc();
if ($currentYearRow) {
    $committeeStats['current_year'] = $currentYearRow['year_label'];
}

// ============================================
// Pending doctors
// ============================================
$pendingDoctors = $db->query("
    SELECT id, name, email, bmdc_reg_no, specialty, created_at 
    FROM users 
    WHERE user_type = 'doctor' AND is_verified = 0 
    ORDER BY created_at DESC LIMIT 5
");

// Pending books
$pendingBooks = $db->query("
    SELECT b.*, s.name as specialty_name, u.name as uploaded_by_name 
    FROM books b 
    LEFT JOIN specialties s ON b.specialty_id = s.id 
    LEFT JOIN users u ON b.uploaded_by = u.id 
    WHERE b.status = 'pending' 
    ORDER BY b.created_at DESC LIMIT 5
");

// Pending journals
$pendingJournals = $db->query("
    SELECT j.*, s.name as specialty_name, u.name as uploaded_by_name 
    FROM journals j 
    LEFT JOIN specialties s ON j.specialty_id = s.id 
    LEFT JOIN users u ON j.uploaded_by = u.id 
    WHERE j.status = 'pending' 
    ORDER BY j.created_at DESC LIMIT 5
");

// Pending supply requests
$pendingRequests = $db->query("
    SELECT sr.*, u.name as doctor_name, 
           CASE 
               WHEN sr.book_id IS NOT NULL THEN b.title 
               WHEN sr.journal_id IS NOT NULL THEN j.title 
           END as item_title
    FROM supply_requests sr 
    LEFT JOIN users u ON sr.user_id = u.id 
    LEFT JOIN books b ON sr.book_id = b.id 
    LEFT JOIN journals j ON sr.journal_id = j.id 
    WHERE sr.status = 'pending' 
    ORDER BY sr.request_date DESC LIMIT 5
");

// Pending download permissions
$pendingDownloads = $db->query("
    SELECT dp.*, u.name as doctor_name,
           CASE 
               WHEN dp.item_type = 'book' THEN b.title 
               WHEN dp.item_type = 'journal' THEN j.title 
           END as item_title
    FROM download_permissions dp 
    LEFT JOIN users u ON dp.user_id = u.id 
    LEFT JOIN books b ON dp.item_type = 'book' AND dp.item_id = b.id 
    LEFT JOIN journals j ON dp.item_type = 'journal' AND dp.item_id = j.id 
    WHERE dp.status = 'pending' 
    ORDER BY dp.requested_at DESC LIMIT 5
");

// ============================================
// ✅ Recent committee members
// ============================================
$recentCommitteeMembers = $db->query("
    SELECT m.id, m.name, m.photo, m.affiliation, m.created_at,
           d.name as designation_name,
           y.year_label
    FROM committee_members m
    JOIN designations d ON m.designation_id = d.id
    JOIN committee_years y ON m.year_id = y.id
    WHERE m.status = 1
    ORDER BY m.created_at DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BJDVL</title>
    
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
        
        /* Main content */
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
            font-family: 'Cambria', Georgia, serif;
        }
        .page-header h1 i {
            color: #0d6efd;
            margin-right: 10px;
        }
        .page-header .welcome-text {
            color: #6c757d;
            font-size: 0.95rem;
            font-family: 'Cambria', Georgia, serif;
        }
        .page-header .welcome-text i {
            margin-right: 6px;
        }
        
        /* ============================================
           STATISTICS CARDS - FLEXIBLE GRID
           ============================================ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 14px;
            margin-bottom: 18px;
        }
        
        .stat-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 14px 16px;
            border: 1px solid #eef1f5;
            transition: all 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .stat-card .stat-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            font-size: 1rem;
            margin-bottom: 8px;
        }
        .stat-card .stat-icon.blue { background: #e8f0fe; color: #0d6efd; }
        .stat-card .stat-icon.green { background: #e8f5e9; color: #198754; }
        .stat-card .stat-icon.orange { background: #fff3e0; color: #f39c12; }
        .stat-card .stat-icon.red { background: #fce4ec; color: #dc3545; }
        .stat-card .stat-icon.purple { background: #f3e5f5; color: #6f42c1; }
        .stat-card .stat-icon.cyan { background: #e0f7fa; color: #0dcaf0; }
        
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
        .stat-card .stat-link {
            font-size: 0.7rem;
            color: #0d6efd;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            margin-top: 6px;
            font-family: 'Cambria', Georgia, serif;
        }
        .stat-card .stat-link:hover {
            text-decoration: underline;
        }
        
        .stat-card .badge-pending {
            font-size: 0.55rem;
            padding: 2px 10px;
            border-radius: 10px;
            background: #fff3e0;
            color: #f39c12;
            font-weight: 600;
            margin-left: 4px;
        }

        /* Committee section header */
        .section-divider {
            margin: 24px 0 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .section-divider .line {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, #eef1f5, transparent);
        }
        .section-divider .label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #6f42c1;
            letter-spacing: 2px;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-divider .label i {
            font-size: 0.9rem;
        }
        
        /* ============================================
           TABLES - 2 COLUMN GRID
           ============================================ */
        .tables-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        
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
            gap: 6px;
            background: #f8f9fa;
        }
        .table-card .table-header h5 {
            font-weight: 700;
            font-size: 0.95rem;
            color: #000000;
            margin: 0;
            font-family: 'Cambria', Georgia, serif;
        }
        .table-card .table-header h5 i {
            margin-right: 8px;
        }
        .table-card .table-header .badge-count {
            font-size: 0.7rem;
            padding: 2px 12px;
            border-radius: 10px;
            font-weight: 600;
        }
        .table-card .table-header .badge-count.orange { background: #fff3e0; color: #f39c12; }
        .table-card .table-header .badge-count.blue { background: #e8f0fe; color: #0d6efd; }
        .table-card .table-header .badge-count.green { background: #e8f5e9; color: #198754; }
        .table-card .table-header .badge-count.purple { background: #f3e5f5; color: #6f42c1; }
        
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
            min-width: 400px;
            background: #ffffff;
        }
        .table-card .table-body table thead th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            border-bottom: 2px solid #eef1f5;
            padding: 8px 14px;
            white-space: nowrap;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .table-card .table-body table tbody td {
            padding: 8px 14px;
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
        
        .table-card .table-footer {
            padding: 10px 16px;
            border-top: 1px solid #eef1f5;
            background: #f8f9fa;
        }
        .table-card .table-footer .btn {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 4px 16px;
            border-radius: 4px;
        }
        
        .action-link {
            font-size: 0.72rem;
            padding: 3px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            font-family: 'Cambria', Georgia, serif;
            display: inline-block;
            transition: all 0.2s ease;
        }
        .action-link:hover {
            opacity: 0.8;
            transform: translateY(-1px);
        }
        .action-link.primary { background: #e8f0fe; color: #0d6efd; }
        .action-link.purple { background: #f3e5f5; color: #6f42c1; }
        
        /* Committee member avatar */
        .committee-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e8e0f9, #d4c7f5);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            font-weight: 700;
            color: #6f42c1;
            font-size: 0.85rem;
            flex-shrink: 0;
        }
        .committee-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .committee-member-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .committee-member-cell .details .name {
            font-weight: 700;
            font-size: 0.85rem;
            color: #1a1a2e;
        }
        .committee-member-cell .details .desig {
            font-size: 0.72rem;
            color: #6f42c1;
            font-weight: 600;
        }
        
        .badge-year-mini {
            font-size: 0.65rem;
            background: #198754;
            color: #fff;
            padding: 2px 8px;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            padding: 24px 16px;
            background: #ffffff;
        }
        .empty-state .icon {
            font-size: 1.8rem;
            color: #198754;
            margin-bottom: 6px;
        }
        .empty-state p {
            color: #6c757d;
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.88rem;
            margin: 0;
        }
        
        /* Responsive */
        @media (max-width: 1400px) {
            .stats-grid { grid-template-columns: repeat(4, 1fr); }
        }
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0 !important;
                max-width: 100% !important;
                padding: 14px 16px 18px;
            }
            .stats-grid { grid-template-columns: repeat(3, 1fr); gap: 12px; }
            .tables-grid { grid-template-columns: 1fr; gap: 14px; }
            .page-header h1 { font-size: 1.3rem; }
            .page-header .welcome-text { font-size: 0.9rem; }
            .stat-card .stat-number { font-size: 1.4rem; }
        }
        @media (max-width: 575.98px) {
            .main-content { padding: 10px 8px 14px; }
            .stats-grid { grid-template-columns: repeat(3, 1fr); gap: 8px; }
            .stat-card { padding: 10px 12px; }
            .stat-card .stat-number { font-size: 1.15rem; }
            .stat-card .stat-icon { width: 28px; height: 28px; font-size: 0.8rem; margin-bottom: 4px; }
            .stat-card .stat-label { font-size: 0.62rem; }
            .stat-card .stat-link { font-size: 0.6rem; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 6px; }
            .page-header h1 { font-size: 1.15rem; }
            .page-header .welcome-text { font-size: 0.8rem; }
            .table-card .table-body table { min-width: 320px; font-size: 0.78rem; }
            .table-card .table-body table thead th { padding: 6px 10px; font-size: 0.62rem; }
            .table-card .table-body table tbody td { padding: 6px 10px; font-size: 0.78rem; }
            .committee-avatar { width: 28px; height: 28px; font-size: 0.7rem; }
        }
        @media (max-width: 400px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .stat-card .stat-number { font-size: 1rem; }
            .stat-card .stat-icon { width: 24px; height: 24px; font-size: 0.7rem; }
        }
        
        /* Dark mode - keep white */
        @media (prefers-color-scheme: dark) {
            body, .main-content { background: #ffffff !important; }
            .stat-card, .table-card, .table-card .table-header, .table-card .table-footer { background: #f8f9fa !important; border-color: #eef1f5 !important; }
            .stat-card .stat-number { color: #000000 !important; }
            .stat-card .stat-label { color: #6c757d !important; }
            .table-card .table-body table { background: #ffffff !important; }
            .table-card .table-body table thead th { background: #f8f9fa !important; color: #495057 !important; border-bottom-color: #eef1f5 !important; }
            .table-card .table-body table tbody td { color: #000000 !important; border-bottom-color: #f0f4f8 !important; }
            .table-card .table-body table tbody tr:hover { background: #f8f9fa !important; }
            .page-header h1 { color: #000000 !important; }
            .page-header .welcome-text { color: #6c757d !important; }
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
                        <i class="fas fa-chart-line"></i> Dashboard
                    </h1>
                    <div class="welcome-text">
                        <i class="fas fa-user-cog"></i> Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </div>
                </div>

                <!-- ============================================
                     PUBLICATION STATS CARDS
                     ============================================ -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                        <div class="stat-number"><?php echo $stats['total_doctors']; ?></div>
                        <div class="stat-label">Total Doctors</div>
                        <a href="/admin/manage_doctors.php" class="stat-link">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
                        <div class="stat-number"><?php echo $stats['pending_doctors']; ?></div>
                        <div class="stat-label">Pending Verification</div>
                        <?php if ($stats['pending_doctors'] > 0): ?>
                            <a href="/admin/manage_doctors.php" class="stat-link">
                                Verify Now <i class="fas fa-arrow-right"></i>
                                <span class="badge-pending"><?php echo $stats['pending_doctors']; ?></span>
                            </a>
                        <?php else: ?>
                            <span class="stat-link" style="color: #198754;">All Verified <i class="fas fa-check-circle"></i></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fas fa-book"></i></div>
                        <div class="stat-number"><?php echo $stats['total_books']; ?></div>
                        <div class="stat-label">Total Books</div>
                        <?php if ($stats['pending_books'] > 0): ?>
                            <a href="/admin/manage_books.php" class="stat-link">
                                <?php echo $stats['pending_books']; ?> Pending <i class="fas fa-arrow-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="stat-link" style="color: #198754;">All Approved <i class="fas fa-check-circle"></i></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon purple"><i class="fas fa-newspaper"></i></div>
                        <div class="stat-number"><?php echo $stats['total_journals']; ?></div>
                        <div class="stat-label">Total Journals</div>
                        <?php if ($stats['pending_journals'] > 0): ?>
                            <a href="/admin/manage_journals.php" class="stat-link">
                                <?php echo $stats['pending_journals']; ?> Pending <i class="fas fa-arrow-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="stat-link" style="color: #198754;">All Approved <i class="fas fa-check-circle"></i></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon cyan"><i class="fas fa-truck"></i></div>
                        <div class="stat-number"><?php echo $stats['total_requests']; ?></div>
                        <div class="stat-label">Supply Requests</div>
                        <?php if ($stats['pending_requests'] > 0): ?>
                            <a href="/admin/supply_requests.php" class="stat-link">
                                <?php echo $stats['pending_requests']; ?> Pending <i class="fas fa-arrow-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="stat-link" style="color: #198754;">No Pending <i class="fas fa-check-circle"></i></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon red"><i class="fas fa-download"></i></div>
                        <div class="stat-number"><?php echo $stats['pending_downloads']; ?></div>
                        <div class="stat-label">Pending Downloads</div>
                        <?php if ($stats['pending_downloads'] > 0): ?>
                            <a href="/admin/manage_download_permissions.php" class="stat-link">
                                Review Now <i class="fas fa-arrow-right"></i>
                                <span class="badge-pending"><?php echo $stats['pending_downloads']; ?></span>
                            </a>
                        <?php else: ?>
                            <span class="stat-link" style="color: #198754;">All Approved <i class="fas fa-check-circle"></i></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ============================================
                     COMMITTEE SECTION DIVIDER
                     ============================================ -->
                <div class="section-divider">
                    <span class="label"><i class="fas fa-users"></i> Committee Management</span>
                    <span class="line"></span>
                </div>

                <!-- ============================================
                     COMMITTEE STATS CARDS
                     ============================================ -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon purple"><i class="fas fa-user-tag"></i></div>
                        <div class="stat-number"><?php echo $committeeStats['total_designations']; ?></div>
                        <div class="stat-label">Designations</div>
                        <a href="/admin/designations.php" class="stat-link">Manage <i class="fas fa-arrow-right"></i></a>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fas fa-calendar-alt"></i></div>
                        <div class="stat-number"><?php echo $committeeStats['total_years']; ?></div>
                        <div class="stat-label">Committee Years</div>
                        <a href="/admin/committee_years.php" class="stat-link">Manage <i class="fas fa-arrow-right"></i></a>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="fas fa-users-cog"></i></div>
                        <div class="stat-number"><?php echo $committeeStats['total_members']; ?></div>
                        <div class="stat-label">Total Members</div>
                        <a href="/admin/committee_members.php" class="stat-link">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon cyan"><i class="fas fa-star"></i></div>
                        <div class="stat-number" style="font-size: <?php echo $committeeStats['current_year'] && strlen($committeeStats['current_year']) > 6 ? '1rem' : '1.6rem'; ?>;">
                            <?php echo $committeeStats['current_year'] ? htmlspecialchars($committeeStats['current_year']) : '—'; ?>
                        </div>
                        <div class="stat-label">Current Term</div>
                        <a href="/admin/committee_years.php" class="stat-link">
                            <?php echo $committeeStats['current_year'] ? 'Change' : 'Set Current'; ?> <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange"><i class="fas fa-plus-circle"></i></div>
                        <div class="stat-number">+</div>
                        <div class="stat-label">Add Member</div>
                        <a href="/admin/committee_members.php" class="stat-link">Quick Add <i class="fas fa-arrow-right"></i></a>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon red"><i class="fas fa-globe"></i></div>
                        <div class="stat-number"><i class="fas fa-external-link-alt" style="font-size:1.1rem;"></i></div>
                        <div class="stat-label">Public Page</div>
                        <a href="/committee.php" target="_blank" class="stat-link">View Public <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <!-- ============================================
                     TABLES GRID
                     ============================================ -->
                <div class="tables-grid">
                    
                    <!-- PENDING DOCTORS -->
                    <div class="table-card">
                        <div class="table-header">
                            <h5><i class="fas fa-user-check" style="color: #f39c12;"></i> Pending Doctors</h5>
                            <span class="badge-count orange"><?php echo $stats['pending_doctors']; ?></span>
                        </div>
                        <div class="table-body">
                            <?php if ($pendingDoctors->num_rows > 0): ?>
                            <table>
                                <thead>
                                    <tr><th>Name</th><th>Email</th><th>Action</th></tr>
                                </thead>
                                <tbody>
                                    <?php while ($doctor = $pendingDoctors->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($doctor['name']); ?></strong></td>
                                        <td><small><?php echo htmlspecialchars($doctor['email']); ?></small></td>
                                        <td><a href="/admin/manage_doctors.php" class="action-link primary"><i class="fas fa-check"></i> Verify</a></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="empty-state">
                                <div class="icon"><i class="fas fa-check-circle"></i></div>
                                <p>All doctors are verified.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- PENDING BOOKS -->
                    <div class="table-card">
                        <div class="table-header">
                            <h5><i class="fas fa-book" style="color: #f39c12;"></i> Pending Books</h5>
                            <span class="badge-count orange"><?php echo $stats['pending_books']; ?></span>
                        </div>
                        <div class="table-body">
                            <?php if ($pendingBooks->num_rows > 0): ?>
                            <table>
                                <thead><tr><th>Title</th><th>Specialty</th><th>Action</th></tr></thead>
                                <tbody>
                                    <?php while ($book = $pendingBooks->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($book['title']); ?></strong></td>
                                        <td><small><?php echo htmlspecialchars($book['specialty_name']); ?></small></td>
                                        <td><a href="/admin/manage_books.php" class="action-link primary"><i class="fas fa-edit"></i> Review</a></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="empty-state">
                                <div class="icon"><i class="fas fa-check-circle"></i></div>
                                <p>All books are approved.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- PENDING JOURNALS -->
                    <div class="table-card">
                        <div class="table-header">
                            <h5><i class="fas fa-newspaper" style="color: #f39c12;"></i> Pending Journals</h5>
                            <span class="badge-count orange"><?php echo $stats['pending_journals']; ?></span>
                        </div>
                        <div class="table-body">
                            <?php if ($pendingJournals->num_rows > 0): ?>
                            <table>
                                <thead><tr><th>Title</th><th>Journal</th><th>Action</th></tr></thead>
                                <tbody>
                                    <?php while ($journal = $pendingJournals->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($journal['title']); ?></strong></td>
                                        <td><small><?php echo htmlspecialchars($journal['journal_name']); ?></small></td>
                                        <td><a href="/admin/manage_journals.php" class="action-link primary"><i class="fas fa-edit"></i> Review</a></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="empty-state">
                                <div class="icon"><i class="fas fa-check-circle"></i></div>
                                <p>All journals are approved.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- ✅ RECENT COMMITTEE MEMBERS (NEW) -->
                    <div class="table-card">
                        <div class="table-header">
                            <h5><i class="fas fa-users" style="color: #6f42c1;"></i> Recent Committee Members</h5>
                            <span class="badge-count purple"><?php echo $committeeStats['total_members']; ?> total</span>
                        </div>
                        <div class="table-body">
                            <?php if ($recentCommitteeMembers->num_rows > 0): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Member</th>
                                        <th>Year</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($cm = $recentCommitteeMembers->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="committee-member-cell">
                                                <div class="committee-avatar">
                                                    <?php if (!empty($cm['photo']) && file_exists(__DIR__ . '/../uploads/committee/' . $cm['photo'])): ?>
                                                        <img src="/uploads/committee/<?php echo htmlspecialchars($cm['photo']); ?>" 
                                                             alt="<?php echo htmlspecialchars($cm['name']); ?>">
                                                    <?php else: ?>
                                                        <?php echo strtoupper(substr($cm['name'], 0, 1)); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="details">
                                                    <div class="name"><?php echo htmlspecialchars($cm['name']); ?></div>
                                                    <div class="desig"><?php echo htmlspecialchars($cm['designation_name']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge-year-mini"><?php echo htmlspecialchars($cm['year_label']); ?></span></td>
                                        <td>
                                            <a href="/admin/edit_committee_member.php?id=<?php echo $cm['id']; ?>" 
                                               class="action-link purple">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="empty-state">
                                <div class="icon"><i class="fas fa-users"></i></div>
                                <p>No committee members yet.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="table-footer">
                            <a href="/admin/committee_members.php" class="btn btn-primary">
                                <i class="fas fa-eye"></i> Manage All Members
                            </a>
                        </div>
                    </div>
                    
                    <!-- SUPPLY REQUESTS -->
                    <div class="table-card">
                        <div class="table-header">
                            <h5><i class="fas fa-truck" style="color: #f39c12;"></i> Supply Requests</h5>
                            <span class="badge-count orange"><?php echo $stats['pending_requests']; ?></span>
                        </div>
                        <div class="table-body">
                            <?php if ($pendingRequests->num_rows > 0): ?>
                            <table>
                                <thead><tr><th>Doctor</th><th>Item</th><th>Action</th></tr></thead>
                                <tbody>
                                    <?php while ($req = $pendingRequests->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($req['doctor_name']); ?></strong></td>
                                        <td><small><?php echo htmlspecialchars($req['item_title'] ?? 'N/A'); ?></small></td>
                                        <td><a href="/admin/supply_requests.php" class="action-link primary"><i class="fas fa-check"></i> Process</a></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="empty-state">
                                <div class="icon"><i class="fas fa-check-circle"></i></div>
                                <p>No pending supply requests.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- DOWNLOAD PERMISSIONS -->
                    <div class="table-card">
                        <div class="table-header">
                            <h5><i class="fas fa-download" style="color: #f39c12;"></i> Download Permissions</h5>
                            <span class="badge-count orange"><?php echo $stats['pending_downloads']; ?></span>
                        </div>
                        <div class="table-body">
                            <?php if ($pendingDownloads->num_rows > 0): ?>
                            <table>
                                <thead><tr><th>Doctor</th><th>Item</th><th>Action</th></tr></thead>
                                <tbody>
                                    <?php while ($dl = $pendingDownloads->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($dl['doctor_name']); ?></strong></td>
                                        <td><small><?php echo htmlspecialchars($dl['item_title'] ?? 'N/A'); ?></small></td>
                                        <td><a href="/admin/manage_download_permissions.php" class="action-link primary"><i class="fas fa-check"></i> Review</a></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="empty-state">
                                <div class="icon"><i class="fas fa-check-circle"></i></div>
                                <p>No pending download permissions.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- RECENT ACTIVITIES -->
                    <div class="table-card">
                        <div class="table-header">
                            <h5><i class="fas fa-history" style="color: #0d6efd;"></i> Recent Activities</h5>
                            <span class="badge-count blue">Latest</span>
                        </div>
                        <div class="table-body">
                            <?php
                            $activities = $db->query("
                                SELECT action, user_type, created_at, 
                                       COALESCE(
                                           (SELECT name FROM users WHERE id = audit_trail.user_id),
                                           'System'
                                       ) as user_name
                                FROM audit_trail 
                                ORDER BY created_at DESC LIMIT 5
                            ");
                            ?>
                            <?php if ($activities->num_rows > 0): ?>
                            <table>
                                <thead><tr><th>User</th><th>Action</th><th>Time</th></tr></thead>
                                <tbody>
                                    <?php while ($activity = $activities->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($activity['user_name']); ?></strong></td>
                                        <td><small><?php echo htmlspecialchars($activity['action']); ?></small></td>
                                        <td><small><?php echo date('M d, H:i', strtotime($activity['created_at'])); ?></small></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="empty-state">
                                <div class="icon"><i class="fas fa-inbox"></i></div>
                                <p>No activities recorded yet.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="table-footer">
                            <a href="/admin/audit_trail.php" class="btn btn-primary">
                                <i class="fas fa-eye"></i> View All
                            </a>
                        </div>
                    </div>
                    
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/custom.js"></script>
</body>
</html>