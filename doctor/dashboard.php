<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireLogin();

$userId = $_SESSION['user_id'];
$user = getUserById($userId);

if ($_SESSION['user_type'] !== 'doctor') {
    header("Location: /login");
    exit;
}

$db = Database::getInstance()->getConnection();

// Get statistics
$stats = [
    'total_books' => $db->query("SELECT COUNT(*) as count FROM books WHERE status = 'approved'")->fetch_assoc()['count'],
    'total_journals' => $db->query("SELECT COUNT(*) as count FROM journals WHERE status = 'approved'")->fetch_assoc()['count'],
    'my_requests' => $db->query("SELECT COUNT(*) as count FROM supply_requests WHERE user_id = $userId")->fetch_assoc()['count'],
    'pending_requests' => $db->query("SELECT COUNT(*) as count FROM supply_requests WHERE user_id = $userId AND status = 'pending'")->fetch_assoc()['count'],
];

// Get recent books
$recentBooks = $db->query("SELECT b.*, s.name as specialty_name FROM books b JOIN specialties s ON b.specialty_id = s.id WHERE b.status = 'approved' ORDER BY b.created_at DESC LIMIT 4")->fetch_all(MYSQLI_ASSOC);

// Get recent journals
$recentJournals = $db->query("SELECT j.*, s.name as specialty_name FROM journals j JOIN specialties s ON j.specialty_id = s.id WHERE j.status = 'approved' ORDER BY j.created_at DESC LIMIT 4")->fetch_all(MYSQLI_ASSOC);

// Get recent requests
$recentRequests = $db->query("SELECT sr.*, b.title as book_title, j.title as journal_title FROM supply_requests sr LEFT JOIN books b ON sr.book_id = b.id LEFT JOIN journals j ON sr.journal_id = j.id WHERE sr.user_id = $userId ORDER BY sr.request_date DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - UCLP Academy</title>
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
        .page-header .status-badge {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.7rem;
            padding: 4px 12px;
            border-radius: 12px;
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
        .section-header h4 { font-weight: 700; font-size: 1rem; color: #000000; margin: 0; font-family: 'Cambria', Georgia, serif; }
        .section-header h4 i { margin-right: 6px; }
        .section-header .btn { font-family: 'Cambria', Georgia, serif; font-weight: 600; font-size: 0.7rem; padding: 3px 12px; border-radius: 6px; }
        
        .items-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        
        .book-card, .journal-card {
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #eef1f5;
            transition: all 0.2s ease;
        }
        .book-card:hover, .journal-card:hover { transform: translateY(-3px); box-shadow: 0 4px 15px rgba(0,0,0,0.04); }
        
        .book-card .book-cover {
            height: 140px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 8px;
        }
        .book-card .book-cover img { max-height: 100%; width: auto; object-fit: contain; }
        .book-card .book-cover .placeholder { font-size: 2.5rem; color: #ced4da; }
        .book-card .card-body { padding: 8px 12px 4px; }
        .book-card .card-body h6 { font-weight: 700; font-size: 0.78rem; color: #000000; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.3; font-family: 'Cambria', Georgia, serif; margin: 0; }
        .book-card .card-body .author { color: #6c757d; font-size: 0.65rem; font-family: 'Cambria', Georgia, serif; }
        .book-card .card-footer { background: transparent; border-top: 1px solid #f0f4f8; padding: 4px 12px 10px; }
        .book-card .card-footer .btn { width: 100%; font-size: 0.6rem; padding: 3px 6px; border-radius: 4px; font-weight: 600; font-family: 'Cambria', Georgia, serif; }
        
        .journal-card .journal-header {
            background: #f8f9fa;
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #eef1f5;
        }
        .journal-card .journal-header .icon { font-size: 1.8rem; color: #198754; }
        .journal-card .journal-header .badge { font-size: 0.5rem; padding: 1px 8px; border-radius: 8px; font-family: 'Cambria', Georgia, serif; }
        .journal-card .card-body { padding: 8px 12px 4px; }
        .journal-card .card-body h6 { font-weight: 700; font-size: 0.78rem; color: #000000; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.3; font-family: 'Cambria', Georgia, serif; margin: 0; }
        .journal-card .card-body .journal-name { color: #6c757d; font-size: 0.65rem; font-family: 'Cambria', Georgia, serif; }
        .journal-card .card-footer { background: transparent; border-top: 1px solid #f0f4f8; padding: 4px 12px 10px; }
        .journal-card .card-footer .btn { width: 100%; font-size: 0.6rem; padding: 3px 6px; border-radius: 4px; font-weight: 600; font-family: 'Cambria', Georgia, serif; }
        
        .request-item {
            background: #ffffff;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 6px;
            border-left: 3px solid #0d6efd;
            border: 1px solid #eef1f5;
            border-left-width: 3px;
        }
        .request-item .status {
            font-size: 0.55rem; font-weight: 600; padding: 1px 8px; border-radius: 8px; display: inline-block;
        }
        .request-item .status.pending { background: #fff3e0; color: #f39c12; }
        .request-item .status.approved { background: #e8f5e9; color: #198754; }
        .request-item .status.rejected { background: #fce4ec; color: #dc3545; }
        .request-item .status.completed { background: #e0f7fa; color: #0dcaf0; }
        .request-item .item-title { font-weight: 600; font-size: 0.82rem; color: #000000; font-family: 'Cambria', Georgia, serif; }
        .request-item .item-meta { font-size: 0.7rem; color: #6c757d; font-family: 'Cambria', Georgia, serif; }
        
        .empty-state { text-align: center; padding: 20px 16px; }
        .empty-state .icon { font-size: 2rem; color: #dee2e6; margin-bottom: 8px; }
        .empty-state p { color: #6c757d; font-family: 'Cambria', Georgia, serif; font-size: 0.8rem; }
        
        @media (max-width: 991.98px) {
            .main-content { margin-left: 0 !important; max-width: 100% !important; padding: 12px 14px 16px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .items-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
        }
        
        @media (max-width: 575.98px) {
            .main-content { padding: 10px 8px 14px; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 4px; padding-bottom: 10px; margin-bottom: 12px; }
            .page-header h1 { font-size: 1rem; }
            .page-header .status-badge { font-size: 0.6rem; padding: 3px 10px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 6px; }
            .stat-card { padding: 8px 10px; }
            .stat-card .stat-number { font-size: 1.1rem; }
            .stat-card .stat-label { font-size: 0.55rem; }
            .items-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
            .book-card .book-cover { height: 100px; }
            .section-header h4 { font-size: 0.85rem; }
            .request-item { padding: 6px 10px; }
            .request-item .item-title { font-size: 0.75rem; }
        }
        
        @media (max-width: 400px) {
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 4px; }
            .stat-card { padding: 6px 8px; }
            .stat-card .stat-number { font-size: 0.9rem; }
            .items-grid { grid-template-columns: 1fr 1fr; gap: 6px; }
            .book-card .book-cover { height: 80px; }
            .book-card .card-body h6 { font-size: 0.68rem; }
        }
        
        @media (prefers-color-scheme: dark) {
            body { background: #ffffff !important; }
            .main-content { background: #ffffff !important; }
            .stat-card { background: #f8f9fa !important; border-color: #eef1f5 !important; }
            .stat-card .stat-number { color: #000000 !important; }
            .stat-card .stat-label { color: #6c757d !important; }
            .stat-card .stat-icon { color: #000000 !important; }
            .book-card { background: #ffffff !important; border-color: #eef1f5 !important; }
            .book-card .card-body h6 { color: #000000 !important; }
            .journal-card { background: #ffffff !important; border-color: #eef1f5 !important; }
            .journal-card .card-body h6 { color: #000000 !important; }
            .request-item { background: #ffffff !important; border-color: #eef1f5 !important; }
            .request-item .item-title { color: #000000 !important; }
            .page-header { border-bottom-color: #eef1f5 !important; }
            .page-header h1 { color: #000000 !important; }
            .section-header h4 { color: #000000 !important; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/doctor_nav.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/doctor_sidebar.php'; ?>
            
            <main class="main-content">
                <div class="page-header">
                    <h1><i class="fas fa-chart-line" style="color: #0d6efd; margin-right: 8px;"></i> Dashboard</h1>
                    <span class="badge bg-<?php echo $user['is_verified'] ? 'success' : 'warning'; ?> status-badge">
                        <i class="fas fa-<?php echo $user['is_verified'] ? 'check-circle' : 'clock'; ?>"></i>
                        <?php echo $user['is_verified'] ? 'Verified' : 'Pending Verification'; ?>
                    </span>
                </div>

                <div class="stats-grid">
                    <div class="stat-card border-blue">
                        <div class="stat-icon"><i class="fas fa-book"></i></div>
                        <div class="stat-number"><?php echo $stats['total_books']; ?></div>
                        <div class="stat-label">Books Available</div>
                    </div>
                    <div class="stat-card border-green">
                        <div class="stat-icon"><i class="fas fa-newspaper"></i></div>
                        <div class="stat-number"><?php echo $stats['total_journals']; ?></div>
                        <div class="stat-label">Journals Available</div>
                    </div>
                    <div class="stat-card border-orange">
                        <div class="stat-icon"><i class="fas fa-truck"></i></div>
                        <div class="stat-number"><?php echo $stats['my_requests']; ?></div>
                        <div class="stat-label">My Requests</div>
                    </div>
                    <div class="stat-card border-cyan">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-number"><?php echo $stats['pending_requests']; ?></div>
                        <div class="stat-label">Pending Requests</div>
                    </div>
                </div>

                <div class="section-header">
                    <h4><i class="fas fa-book" style="color: #0d6efd;"></i> Recently Added Books</h4>
                    <a href="/doctor/books" class="btn btn-primary btn-sm">View All</a>
                </div>
                <div class="items-grid">
                    <?php if (empty($recentBooks)): ?>
                        <div class="col-12"><div class="empty-state"><div class="icon"><i class="fas fa-book"></i></div><p>No books available yet.</p></div></div>
                    <?php else: ?>
                        <?php foreach ($recentBooks as $book): ?>
                        <div class="book-card">
                            <div class="book-cover">
                                <?php if ($book['cover_image']): ?>
                                <img src="/uploads/books/<?php echo $book['cover_image']; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" loading="lazy">
                                <?php else: ?>
                                <div class="placeholder"><i class="fas fa-book"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h6><?php echo htmlspecialchars($book['title']); ?></h6>
                                <div class="author"><?php echo htmlspecialchars($book['author']); ?></div>
                            </div>
                            <div class="card-footer">
                                <a href="/doctor/view-book/<?php echo $book['id']; ?>" class="btn btn-primary">View</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="section-header">
                    <h4><i class="fas fa-newspaper" style="color: #198754;"></i> Recently Added Journals</h4>
                    <a href="/doctor/journals" class="btn btn-success btn-sm">View All</a>
                </div>
                <div class="items-grid">
                    <?php if (empty($recentJournals)): ?>
                        <div class="col-12"><div class="empty-state"><div class="icon"><i class="fas fa-newspaper"></i></div><p>No journals available yet.</p></div></div>
                    <?php else: ?>
                        <?php foreach ($recentJournals as $journal): ?>
                        <div class="journal-card">
                            <div class="journal-header">
                                <div class="icon"><i class="fas fa-file-alt"></i></div>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($journal['specialty_name']); ?></span>
                            </div>
                            <div class="card-body">
                                <h6><?php echo htmlspecialchars($journal['title']); ?></h6>
                                <div class="journal-name"><?php echo htmlspecialchars($journal['journal_name']); ?></div>
                            </div>
                            <div class="card-footer">
                                <a href="/doctor/view-journal/<?php echo $journal['id']; ?>" class="btn btn-success">View</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="section-header">
                    <h4><i class="fas fa-truck" style="color: #f39c12;"></i> My Recent Requests</h4>
                    <a href="/doctor/requests" class="btn btn-warning btn-sm">View All</a>
                </div>
                <div class="card" style="border: 1px solid #eef1f5; border-radius: 8px; overflow: hidden;">
                    <div class="card-body" style="padding: 12px 16px;">
                        <?php if (empty($recentRequests)): ?>
                            <div class="empty-state"><div class="icon"><i class="fas fa-inbox"></i></div><p>You haven't made any requests yet. <a href="/doctor/books" style="color: #0d6efd; text-decoration: none;">Browse Books</a></p></div>
                        <?php else: ?>
                            <?php foreach ($recentRequests as $request): ?>
                            <div class="request-item">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div>
                                        <div class="item-title"><?php echo htmlspecialchars($request['book_title'] ?? $request['journal_title'] ?? 'Unknown Item'); ?></div>
                                        <div class="item-meta"><i class="fas fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($request['request_date'])); ?> <span class="badge bg-secondary" style="font-size: 0.5rem; padding: 1px 6px;"><?php echo ucfirst($request['request_type']); ?></span></div>
                                    </div>
                                    <span class="status <?php echo $request['status']; ?>"><?php echo ucfirst($request['status']); ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>