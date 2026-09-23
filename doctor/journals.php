<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireLogin();

$userId = $_SESSION['user_id'];
$specialtyId = isset($_GET['specialty']) ? intval($_GET['specialty']) : 0;

$specialties = getAllSpecialties();
$journals = $specialtyId ? getJournalsBySpecialty($specialtyId) : [];

if (!$specialtyId) {
    $db = Database::getInstance()->getConnection();
    $journals = $db->query("SELECT j.*, s.name as specialty_name FROM journals j 
                            JOIN specialties s ON j.specialty_id = s.id 
                            WHERE j.status = 'approved' 
                            ORDER BY j.date DESC")->fetch_all(MYSQLI_ASSOC);
}

$user = getUserById($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journals - UCLP Academy</title>
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
        
        /* ============================================
           MAIN CONTENT AREA
           ============================================ */
        .main-content {
            margin-left: 250px;
            padding: 16px 20px 20px;
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
        .page-header h1 i {
            color: #198754;
            margin-right: 8px;
        }
        .page-header .total-count {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.8rem;
            color: #6c757d;
        }
        .page-header .total-count strong {
            color: #000000;
        }
        
        /* ============================================
           FILTER SIDEBAR
           ============================================ */
        .filter-card {
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #eef1f5;
            position: sticky;
            top: 20px;
            overflow: hidden;
        }
        .filter-card .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #eef1f5;
            padding: 10px 14px;
            font-weight: 700;
            font-size: 0.85rem;
            color: #000000;
            font-family: 'Cambria', Georgia, serif;
        }
        .filter-card .card-header i {
            margin-right: 6px;
        }
        .filter-card .list-group {
            padding: 4px 0;
        }
        .filter-card .list-group-item {
            border: none;
            padding: 6px 14px;
            border-radius: 4px;
            margin: 1px 6px;
            transition: all 0.2s ease;
            cursor: pointer;
            font-size: 0.82rem;
            color: #000000;
            background: transparent;
            font-family: 'Cambria', Georgia, serif;
            text-decoration: none;
        }
        .filter-card .list-group-item:hover {
            background: #e8f5e9;
            color: #198754;
        }
        .filter-card .list-group-item.active {
            background: #198754;
            color: #ffffff;
        }
        .filter-card .list-group-item i {
            margin-right: 6px;
            width: 16px;
        }
        
        /* ============================================
           JOURNALS GRID
           ============================================ */
        .journals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
        }
        
        .journal-card {
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #eef1f5;
            transition: all 0.2s ease;
        }
        .journal-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            border-color: #198754;
        }
        
        .journal-card .journal-header {
            background: #f8f9fa;
            padding: 14px;
            text-align: center;
            border-bottom: 1px solid #eef1f5;
        }
        .journal-card .journal-header .icon {
            font-size: 2rem;
            color: #198754;
        }
        .journal-card .journal-header .badge-specialty {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.55rem;
            padding: 2px 10px;
            border-radius: 10px;
            margin-top: 4px;
            background: #e9ecef;
            color: #6c757d;
            font-weight: 500;
            display: inline-block;
        }
        
        .journal-card .card-body {
            padding: 10px 14px 6px;
        }
        .journal-card .card-body h6 {
            font-weight: 700;
            font-size: 0.82rem;
            color: #000000;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.3;
            font-family: 'Cambria', Georgia, serif;
            margin: 0 0 2px 0;
        }
        .journal-card .card-body .journal-name {
            color: #6c757d;
            font-size: 0.72rem;
            font-family: 'Cambria', Georgia, serif;
        }
        .journal-card .card-body .journal-name i {
            margin-right: 2px;
        }
        .journal-card .card-body .meta {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            margin-top: 6px;
        }
        .journal-card .card-body .meta .badge {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 500;
            font-size: 0.55rem;
            padding: 2px 8px;
            border-radius: 8px;
            background: #f8f9fa;
            color: #6c757d;
        }
        
        .journal-card .card-footer {
            background: transparent;
            border-top: 1px solid #f0f4f8;
            padding: 6px 14px 12px;
        }
        .journal-card .card-footer .btn {
            font-size: 0.7rem;
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 600;
            font-family: 'Cambria', Georgia, serif;
            width: 100%;
            text-align: center;
            text-decoration: none;
            display: block;
        }
        .btn-success { background: #198754; border: none; color: #fff; }
        .btn-success:hover { background: #157347; color: #fff; }
        
        /* ============================================
           EMPTY STATE
           ============================================ */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        .empty-state .icon {
            font-size: 2.5rem;
            color: #dee2e6;
            margin-bottom: 10px;
        }
        .empty-state h5 {
            font-weight: 700;
            color: #000000;
            font-family: 'Cambria', Georgia, serif;
            font-size: 1rem;
        }
        .empty-state p {
            color: #6c757d;
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.85rem;
        }
        
        /* ============================================
           RESPONSIVE
           ============================================ */
        
        /* Tablet - Hide Sidebar */
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0 !important;
                max-width: 100% !important;
                padding: 12px 14px 16px;
            }
            .filter-card {
                position: relative;
                top: 0;
                margin-bottom: 16px;
            }
            .filter-card .list-group {
                display: flex;
                flex-wrap: wrap;
                padding: 4px 6px;
            }
            .filter-card .list-group-item {
                padding: 4px 10px;
                font-size: 0.75rem;
                margin: 2px 3px;
                white-space: nowrap;
            }
            .journals-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                gap: 14px;
            }
            .page-header h1 {
                font-size: 1.1rem;
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
                gap: 4px;
                padding-bottom: 10px;
                margin-bottom: 12px;
            }
            .page-header h1 {
                font-size: 1rem;
            }
            .page-header h1 i {
                font-size: 0.9rem;
            }
            .page-header .total-count {
                font-size: 0.7rem;
            }
            .filter-card .list-group-item {
                font-size: 0.7rem;
                padding: 3px 8px;
                margin: 1px 2px;
            }
            .filter-card .card-header {
                font-size: 0.78rem;
                padding: 8px 10px;
            }
            .journals-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            .journal-card .journal-header {
                padding: 10px;
            }
            .journal-card .journal-header .icon {
                font-size: 1.5rem;
            }
            .journal-card .journal-header .badge-specialty {
                font-size: 0.45rem;
                padding: 1px 6px;
            }
            .journal-card .card-body {
                padding: 8px 10px 4px;
            }
            .journal-card .card-body h6 {
                font-size: 0.75rem;
            }
            .journal-card .card-body .journal-name {
                font-size: 0.65rem;
            }
            .journal-card .card-body .meta .badge {
                font-size: 0.45rem;
                padding: 1px 6px;
            }
            .journal-card .card-footer {
                padding: 4px 10px 10px;
            }
            .journal-card .card-footer .btn {
                font-size: 0.6rem;
                padding: 3px 8px;
            }
            .empty-state {
                padding: 30px 16px;
            }
            .empty-state .icon {
                font-size: 2rem;
            }
            .empty-state h5 {
                font-size: 0.9rem;
            }
            .empty-state p {
                font-size: 0.78rem;
            }
        }
        
        /* Extra Small */
        @media (max-width: 400px) {
            .main-content {
                padding: 8px 4px 10px;
            }
            .journals-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 6px;
            }
            .journal-card .journal-header {
                padding: 8px;
            }
            .journal-card .journal-header .icon {
                font-size: 1.2rem;
            }
            .journal-card .card-body {
                padding: 6px 8px 2px;
            }
            .journal-card .card-body h6 {
                font-size: 0.68rem;
            }
            .journal-card .card-body .journal-name {
                font-size: 0.6rem;
            }
            .journal-card .card-body .meta .badge {
                font-size: 0.4rem;
                padding: 1px 4px;
            }
            .journal-card .card-footer {
                padding: 2px 8px 8px;
            }
            .journal-card .card-footer .btn {
                font-size: 0.55rem;
                padding: 2px 6px;
            }
            .page-header h1 {
                font-size: 0.9rem;
            }
            .filter-card .list-group-item {
                font-size: 0.6rem;
                padding: 2px 6px;
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
            .filter-card {
                background: #f8f9fa !important;
                border-color: #eef1f5 !important;
            }
            .filter-card .card-header {
                background: #f8f9fa !important;
                border-bottom-color: #eef1f5 !important;
                color: #000000 !important;
            }
            .filter-card .list-group-item {
                color: #000000 !important;
            }
            .filter-card .list-group-item:hover {
                background: #e8f5e9 !important;
                color: #198754 !important;
            }
            .filter-card .list-group-item.active {
                background: #198754 !important;
                color: #ffffff !important;
            }
            .journal-card {
                background: #ffffff !important;
                border-color: #eef1f5 !important;
            }
            .journal-card .journal-header {
                background: #f8f9fa !important;
                border-bottom-color: #eef1f5 !important;
            }
            .journal-card .journal-header .badge-specialty {
                background: #e9ecef !important;
                color: #6c757d !important;
            }
            .journal-card .card-body h6 {
                color: #000000 !important;
            }
            .journal-card .card-body .journal-name {
                color: #6c757d !important;
            }
            .journal-card .card-body .meta .badge {
                background: #f8f9fa !important;
                color: #6c757d !important;
            }
            .journal-card .card-footer {
                border-top-color: #f0f4f8 !important;
            }
            .page-header {
                border-bottom-color: #eef1f5 !important;
            }
            .page-header h1 {
                color: #000000 !important;
            }
            .page-header .total-count {
                color: #6c757d !important;
            }
            .page-header .total-count strong {
                color: #000000 !important;
            }
            .empty-state h5 {
                color: #000000 !important;
            }
            .empty-state p {
                color: #6c757d !important;
            }
        }
    </style>
</head>
<body>
    <!-- ============================================
    NAVIGATION
    ============================================ -->
    <?php include __DIR__ . '/../includes/doctor_nav.php'; ?>
    
    <!-- ============================================
    SIDEBAR
    ============================================ -->
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/doctor_sidebar.php'; ?>
            
            <!-- ============================================
            MAIN CONTENT
            ============================================ -->
            <main class="main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <h1>
                        <i class="fas fa-newspaper"></i> Medical Journals
                    </h1>
                    <span class="total-count">
                        <strong><?php echo count($journals); ?></strong> journals available
                    </span>
                </div>

                <div class="row g-3">
                    
                    <!-- ============================================
                    FILTER SIDEBAR
                    ============================================ -->
                    <div class="col-lg-3">
                        <div class="filter-card">
                            <div class="card-header">
                                <i class="fas fa-filter"></i> Filter by Specialty
                            </div>
                            <div class="list-group">
                                <a href="/doctor/journals" class="list-group-item <?php echo $specialtyId == 0 ? 'active' : ''; ?>">
                                    <i class="fas fa-th"></i> All Journals
                                </a>
                                <?php foreach ($specialties as $specialty): ?>
                                <a href="/doctor/journals?specialty=<?php echo $specialty['id']; ?>" 
                                   class="list-group-item <?php echo $specialtyId == $specialty['id'] ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($specialty['name']); ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================
                    JOURNALS GRID
                    ============================================ -->
                    <div class="col-lg-9">
                        <?php if (empty($journals)): ?>
                            <div class="empty-state">
                                <div class="icon"><i class="fas fa-newspaper"></i></div>
                                <h5>No Journals Found</h5>
                                <p>No journals are available <?php echo $specialtyId ? 'for this specialty' : 'at the moment'; ?>.</p>
                            </div>
                        <?php else: ?>
                        <div class="journals-grid">
                            <?php foreach ($journals as $journal): ?>
                            <div class="journal-card">
                                <div class="journal-header">
                                    <div class="icon"><i class="fas fa-file-alt"></i></div>
                                    <span class="badge-specialty">
                                        <?php echo htmlspecialchars($journal['specialty_name']); ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <h6><?php echo htmlspecialchars($journal['title']); ?></h6>
                                    <div class="journal-name">
                                        <i class="fas fa-book"></i> <?php echo htmlspecialchars($journal['journal_name']); ?>
                                    </div>
                                    <div class="meta">
                                        <?php if ($journal['date']): ?>
                                        <span class="badge">
                                            <i class="far fa-calendar-alt"></i> <?php echo date('M Y', strtotime($journal['date'])); ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if ($journal['volume']): ?>
                                        <span class="badge">Vol <?php echo $journal['volume']; ?></span>
                                        <?php endif; ?>
                                        <?php if ($journal['issue']): ?>
                                        <span class="badge">Iss <?php echo $journal['issue']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <a href="/doctor/view-journal/<?php echo $journal['id']; ?>" class="btn btn-success">
                                        <i class="fas fa-eye"></i> View Journal
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>