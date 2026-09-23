<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireLogin();

$userId = $_SESSION['user_id'];
$db = Database::getInstance()->getConnection();

// Get Print Requests (Supply Requests)
$printRequests = $db->query("SELECT sr.*, 
                            b.title as book_title, 
                            j.title as journal_title,
                            CASE WHEN sr.request_type = 'book' THEN b.title ELSE j.title END as item_title
                            FROM supply_requests sr 
                            LEFT JOIN books b ON sr.book_id = b.id 
                            LEFT JOIN journals j ON sr.journal_id = j.id 
                            WHERE sr.user_id = $userId 
                            ORDER BY sr.request_date DESC")->fetch_all(MYSQLI_ASSOC);

// Get Download Requests
$downloadRequests = $db->query("SELECT dp.*,
                               CASE WHEN dp.item_type = 'book' THEN b.title ELSE j.title END as item_title,
                               u.name as processed_by_name
                               FROM download_permissions dp
                               LEFT JOIN books b ON dp.item_type = 'book' AND dp.item_id = b.id
                               LEFT JOIN journals j ON dp.item_type = 'journal' AND dp.item_id = j.id
                               LEFT JOIN users u ON dp.processed_by = u.id
                               WHERE dp.user_id = $userId 
                               ORDER BY dp.requested_at DESC")->fetch_all(MYSQLI_ASSOC);

$user = getUserById($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests - UCLP Academy</title>
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
        .page-header h1 i { color: #0d6efd; margin-right: 8px; }
        .page-header .total-count {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.8rem;
            color: #6c757d;
        }
        .page-header .total-count strong { color: #000000; }
        
        .stat-summary {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }
        .stat-summary .stat-item {
            background: #f8f9fa;
            padding: 6px 14px;
            border-radius: 6px;
            border: 1px solid #eef1f5;
            flex: 1;
            min-width: 60px;
            text-align: center;
        }
        .stat-summary .stat-item .number { font-size: 1.1rem; font-weight: 700; font-family: 'Cambria', Georgia, serif; }
        .stat-summary .stat-item .label { font-size: 0.6rem; color: #6c757d; font-family: 'Cambria', Georgia, serif; }
        .stat-summary .stat-item .number.pending { color: #f39c12; }
        .stat-summary .stat-item .number.approved { color: #198754; }
        .stat-summary .stat-item .number.rejected { color: #dc3545; }
        .stat-summary .stat-item .number.completed { color: #0dcaf0; }
        .stat-summary .stat-item .number.expired { color: #6c757d; }
        
        .tab-nav {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 6px;
            margin-bottom: 14px;
            border: 1px solid #eef1f5;
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }
        .tab-nav .nav-link {
            border-radius: 4px;
            padding: 6px 14px;
            font-weight: 500;
            color: #6c757d;
            font-size: 0.82rem;
            font-family: 'Cambria', Georgia, serif;
            transition: all 0.3s ease;
            border: none;
            background: transparent;
            cursor: pointer;
        }
        .tab-nav .nav-link:hover { background: #e9ecef; color: #000000; }
        .tab-nav .nav-link.active {
            background: #0d6efd;
            color: #fff;
        }
        .tab-nav .nav-link .badge {
            margin-left: 4px;
            font-size: 0.6rem;
            padding: 1px 8px;
            border-radius: 10px;
            background: #e9ecef;
            color: #6c757d;
        }
        .tab-nav .nav-link.active .badge { background: rgba(255,255,255,0.2); color: #fff; }
        
        .request-item {
            background: #ffffff;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 8px;
            border: 1px solid #eef1f5;
            transition: all 0.3s ease;
        }
        .request-item:hover {
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            border-color: #0d6efd;
        }
        
        .request-item .status-badge {
            padding: 2px 10px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.6rem;
            font-family: 'Cambria', Georgia, serif;
            display: inline-block;
        }
        .request-item .status-badge.pending { background: #fff3e0; color: #f39c12; }
        .request-item .status-badge.approved { background: #e8f5e9; color: #198754; }
        .request-item .status-badge.rejected { background: #fce4ec; color: #dc3545; }
        .request-item .status-badge.completed { background: #e0f7fa; color: #0dcaf0; }
        .request-item .status-badge.expired { background: #e9ecef; color: #6c757d; }
        
        .request-item .request-type-badge {
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 0.6rem;
            font-weight: 600;
            font-family: 'Cambria', Georgia, serif;
            display: inline-block;
        }
        .request-item .request-type-badge.print { background: #e8f0fe; color: #0d6efd; }
        .request-item .request-type-badge.download { background: #fce4ec; color: #dc3545; }
        
        .request-item .item-title { font-weight: 600; font-size: 0.85rem; font-family: 'Cambria', Georgia, serif; color: #000000; }
        .request-item .item-meta { font-size: 0.72rem; color: #6c757d; font-family: 'Cambria', Georgia, serif; }
        .request-item .item-meta i { width: 14px; }
        
        .empty-state { text-align: center; padding: 30px 20px; }
        .empty-state .icon { font-size: 2.5rem; color: #dee2e6; margin-bottom: 10px; }
        .empty-state h4 { font-weight: 700; color: #000000; font-family: 'Cambria', Georgia, serif; font-size: 1rem; }
        .empty-state p { color: #6c757d; font-family: 'Cambria', Georgia, serif; font-size: 0.85rem; }
        
        .btn-details {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.65rem;
            padding: 2px 10px;
            border-radius: 4px;
            border: 1px solid #eef1f5;
            background: transparent;
            color: #6c757d;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .btn-details:hover { background: #f8f9fa; color: #000000; }
        
        @media (max-width: 991.98px) {
            .main-content { margin-left: 0 !important; max-width: 100% !important; padding: 12px 14px 16px; }
            .page-header h1 { font-size: 1.1rem; }
        }
        
        @media (max-width: 575.98px) {
            .main-content { padding: 10px 8px 14px; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 4px; padding-bottom: 10px; margin-bottom: 12px; }
            .page-header h1 { font-size: 1rem; }
            .page-header .total-count { font-size: 0.7rem; }
            .stat-summary { gap: 4px; }
            .stat-summary .stat-item { padding: 4px 8px; min-width: 40px; }
            .stat-summary .stat-item .number { font-size: 0.9rem; }
            .stat-summary .stat-item .label { font-size: 0.5rem; }
            .tab-nav .nav-link { font-size: 0.7rem; padding: 4px 10px; }
            .request-item { padding: 8px 10px; }
            .request-item .item-title { font-size: 0.78rem; }
            .request-item .item-meta { font-size: 0.65rem; }
        }
        
        @media (max-width: 400px) {
            .main-content { padding: 8px 4px 10px; }
            .stat-summary .stat-item { padding: 3px 6px; }
            .stat-summary .stat-item .number { font-size: 0.8rem; }
            .tab-nav .nav-link { font-size: 0.65rem; padding: 3px 8px; }
            .request-item { padding: 6px 8px; }
            .page-header h1 { font-size: 0.9rem; }
        }
        
        @media (prefers-color-scheme: dark) {
            body { background: #ffffff !important; }
            .main-content { background: #ffffff !important; }
            .stat-summary .stat-item { background: #f8f9fa !important; border-color: #eef1f5 !important; }
            .stat-summary .stat-item .number { color: #000000 !important; }
            .tab-nav { background: #f8f9fa !important; border-color: #eef1f5 !important; }
            .tab-nav .nav-link { color: #6c757d !important; }
            .tab-nav .nav-link:hover { background: #e9ecef !important; color: #000000 !important; }
            .tab-nav .nav-link.active { background: #0d6efd !important; color: #fff !important; }
            .request-item { background: #ffffff !important; border-color: #eef1f5 !important; }
            .request-item .item-title { color: #000000 !important; }
            .page-header { border-bottom-color: #eef1f5 !important; }
            .page-header h1 { color: #000000 !important; }
            .page-header .total-count { color: #6c757d !important; }
            .page-header .total-count strong { color: #000000 !important; }
            .empty-state h4 { color: #000000 !important; }
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
                    <h1><i class="fas fa-tasks"></i> My Requests</h1>
                    <span class="total-count"><strong><?php echo count($printRequests) + count($downloadRequests); ?></strong> total requests</span>
                </div>

                <?php 
                $totalPrint = count($printRequests);
                $totalDownload = count($downloadRequests);
                $pendingPrint = $approvedPrint = $rejectedPrint = $completedPrint = 0;
                $pendingDownload = $approvedDownload = $rejectedDownload = $expiredDownload = 0;
                
                foreach ($printRequests as $req) {
                    if ($req['status'] == 'pending') $pendingPrint++;
                    elseif ($req['status'] == 'approved') $approvedPrint++;
                    elseif ($req['status'] == 'rejected') $rejectedPrint++;
                    elseif ($req['status'] == 'completed') $completedPrint++;
                }
                
                foreach ($downloadRequests as $req) {
                    if ($req['status'] == 'pending') $pendingDownload++;
                    elseif ($req['status'] == 'approved') $approvedDownload++;
                    elseif ($req['status'] == 'rejected') $rejectedDownload++;
                    elseif ($req['status'] == 'expired') $expiredDownload++;
                }
                ?>
                
                <div class="stat-summary">
                    <div class="stat-item"><div class="number pending"><?php echo $pendingPrint + $pendingDownload; ?></div><div class="label">Pending</div></div>
                    <div class="stat-item"><div class="number approved"><?php echo $approvedPrint + $approvedDownload; ?></div><div class="label">Approved</div></div>
                    <div class="stat-item"><div class="number rejected"><?php echo $rejectedPrint + $rejectedDownload; ?></div><div class="label">Rejected</div></div>
                    <div class="stat-item"><div class="number completed"><?php echo $completedPrint; ?></div><div class="label">Completed</div></div>
                    <div class="stat-item"><div class="number expired"><?php echo $expiredDownload; ?></div><div class="label">Expired</div></div>
                </div>

                <div class="tab-nav">
                    <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button">
                        <i class="fas fa-list"></i> All <span class="badge"><?php echo $totalPrint + $totalDownload; ?></span>
                    </button>
                    <button class="nav-link" id="print-tab" data-bs-toggle="tab" data-bs-target="#print" type="button">
                        <i class="fas fa-print"></i> Print <span class="badge"><?php echo $totalPrint; ?></span>
                    </button>
                    <button class="nav-link" id="download-tab" data-bs-toggle="tab" data-bs-target="#download" type="button">
                        <i class="fas fa-download"></i> Download <span class="badge"><?php echo $totalDownload; ?></span>
                    </button>
                </div>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="all">
                        <?php if (empty($printRequests) && empty($downloadRequests)): ?>
                        <div class="empty-state">
                            <div class="icon"><i class="fas fa-inbox"></i></div>
                            <h4>No Requests Yet</h4>
                            <p>You haven't made any requests yet.</p>
                            <div class="mt-2">
                                <a href="/doctor/books" class="btn btn-primary btn-sm"><i class="fas fa-book"></i> Browse Books</a>
                                <a href="/doctor/journals" class="btn btn-success btn-sm"><i class="fas fa-newspaper"></i> Browse Journals</a>
                            </div>
                        </div>
                        <?php else: ?>
                        <?php foreach ($printRequests as $request): ?>
                        <div class="request-item">
                            <div class="row align-items-center">
                                <div class="col-md-5">
                                    <span class="request-type-badge print"><i class="fas fa-print"></i> Print</span>
                                    <div class="item-title mt-1"><?php echo htmlspecialchars($request['book_title'] ?? $request['journal_title'] ?? 'Unknown Item'); ?></div>
                                    <div class="item-meta"><i class="fas fa-tag"></i> <?php echo ucfirst($request['request_type']); ?></div>
                                </div>
                                <div class="col-md-3">
                                    <div class="item-meta"><i class="fas fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($request['request_date'])); ?></div>
                                </div>
                                <div class="col-md-2">
                                    <span class="status-badge <?php echo $request['status']; ?>"><?php echo ucfirst($request['status']); ?></span>
                                </div>
                                <div class="col-md-2 text-md-end">
                                    <button class="btn-details" data-bs-toggle="collapse" data-bs-target="#printDetails<?php echo $request['id']; ?>"><i class="fas fa-chevron-down"></i></button>
                                </div>
                            </div>
                            <div class="collapse request-details mt-2" id="printDetails<?php echo $request['id']; ?>">
                                <div class="row small">
                                    <div class="col-md-6"><strong>Delivery Address:</strong> <?php echo htmlspecialchars($request['delivery_address']); ?></div>
                                    <div class="col-md-6">
                                        <?php if ($request['admin_notes']): ?><strong>Admin Notes:</strong> <?php echo htmlspecialchars($request['admin_notes']); ?><br><?php endif; ?>
                                        <?php if ($request['processed_date']): ?><strong>Processed:</strong> <?php echo date('M d, Y', strtotime($request['processed_date'])); ?><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php foreach ($downloadRequests as $request): ?>
                        <div class="request-item">
                            <div class="row align-items-center">
                                <div class="col-md-5">
                                    <span class="request-type-badge download"><i class="fas fa-download"></i> Download</span>
                                    <div class="item-title mt-1"><?php echo htmlspecialchars($request['item_title'] ?? 'Unknown Item'); ?></div>
                                    <div class="item-meta"><i class="fas fa-tag"></i> <?php echo ucfirst($request['item_type']); ?></div>
                                </div>
                                <div class="col-md-3">
                                    <div class="item-meta"><i class="fas fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($request['requested_at'])); ?></div>
                                    <?php if ($request['status'] == 'approved'): ?>
                                    <div class="item-meta" style="color: #198754;"><i class="fas fa-download"></i> <?php echo $request['used_downloads']; ?>/<?php echo $request['max_downloads']; ?> used</div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-2">
                                    <span class="status-badge <?php echo $request['status']; ?>"><?php echo ucfirst($request['status']); ?></span>
                                    <?php if ($request['status'] == 'approved' && $request['expires_at']): ?>
                                    <br><small class="text-muted" style="font-family: 'Cambria', Georgia, serif; font-size: 0.55rem;">Expires: <?php echo date('M d, Y', strtotime($request['expires_at'])); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-2 text-md-end">
                                    <button class="btn-details" data-bs-toggle="collapse" data-bs-target="#downloadDetails<?php echo $request['id']; ?>"><i class="fas fa-chevron-down"></i></button>
                                </div>
                            </div>
                            <div class="collapse request-details mt-2" id="downloadDetails<?php echo $request['id']; ?>">
                                <div class="row small">
                                    <div class="col-md-6">
                                        <strong>Type:</strong> <?php echo ucfirst($request['item_type']); ?><br>
                                        <?php if ($request['max_downloads']): ?><strong>Max Downloads:</strong> <?php echo $request['max_downloads']; ?><?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?php if ($request['admin_notes']): ?><strong>Admin Notes:</strong> <?php echo htmlspecialchars($request['admin_notes']); ?><br><?php endif; ?>
                                        <?php if ($request['approved_at']): ?><strong>Approved:</strong> <?php echo date('M d, Y', strtotime($request['approved_at'])); ?><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="print">
                        <?php if (empty($printRequests)): ?>
                        <div class="empty-state">
                            <div class="icon"><i class="fas fa-print"></i></div>
                            <h4>No Print Requests</h4>
                            <p>You haven't made any print requests yet.</p>
                            <a href="/doctor/books" class="btn btn-primary btn-sm mt-2"><i class="fas fa-book"></i> Browse Books</a>
                        </div>
                        <?php else: ?>
                        <?php foreach ($printRequests as $request): ?>
                        <div class="request-item">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <span class="request-type-badge print"><i class="fas fa-print"></i> Print</span>
                                    <div class="item-title mt-1"><?php echo htmlspecialchars($request['book_title'] ?? $request['journal_title'] ?? 'Unknown Item'); ?></div>
                                    <div class="item-meta"><i class="fas fa-tag"></i> <?php echo ucfirst($request['request_type']); ?></div>
                                </div>
                                <div class="col-md-3">
                                    <div class="item-meta"><i class="fas fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($request['request_date'])); ?></div>
                                </div>
                                <div class="col-md-2">
                                    <span class="status-badge <?php echo $request['status']; ?>"><?php echo ucfirst($request['status']); ?></span>
                                </div>
                                <div class="col-md-1 text-md-end">
                                    <button class="btn-details" data-bs-toggle="collapse" data-bs-target="#printDetails<?php echo $request['id']; ?>"><i class="fas fa-info-circle"></i></button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="download">
                        <?php if (empty($downloadRequests)): ?>
                        <div class="empty-state">
                            <div class="icon"><i class="fas fa-download"></i></div>
                            <h4>No Download Requests</h4>
                            <p>You haven't made any download requests yet.</p>
                            <a href="/doctor/books" class="btn btn-primary btn-sm mt-2"><i class="fas fa-book"></i> Browse Books</a>
                        </div>
                        <?php else: ?>
                        <?php foreach ($downloadRequests as $request): ?>
                        <div class="request-item">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <span class="request-type-badge download"><i class="fas fa-download"></i> Download</span>
                                    <div class="item-title mt-1"><?php echo htmlspecialchars($request['item_title'] ?? 'Unknown Item'); ?></div>
                                    <div class="item-meta"><i class="fas fa-tag"></i> <?php echo ucfirst($request['item_type']); ?></div>
                                </div>
                                <div class="col-md-3">
                                    <div class="item-meta"><i class="fas fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($request['requested_at'])); ?></div>
                                    <?php if ($request['status'] == 'approved'): ?>
                                    <div class="item-meta" style="color: #198754;"><i class="fas fa-download"></i> <?php echo $request['used_downloads']; ?>/<?php echo $request['max_downloads']; ?> used</div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-2">
                                    <span class="status-badge <?php echo $request['status']; ?>"><?php echo ucfirst($request['status']); ?></span>
                                    <?php if ($request['status'] == 'approved' && $request['expires_at']): ?>
                                    <br><small class="text-muted" style="font-family: 'Cambria', Georgia, serif; font-size: 0.55rem;">Expires: <?php echo date('M d, Y', strtotime($request['expires_at'])); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-1 text-md-end">
                                    <button class="btn-details" data-bs-toggle="collapse" data-bs-target="#downloadDetails<?php echo $request['id']; ?>"><i class="fas fa-info-circle"></i></button>
                                </div>
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