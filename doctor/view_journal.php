<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireLogin();

$journalId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$journalId) {
    header("Location: /doctor/journals");
    exit;
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT j.*, s.name as specialty_name FROM journals j 
                      JOIN specialties s ON j.specialty_id = s.id 
                      WHERE j.id = ? AND j.status = 'approved'");
$stmt->bind_param("i", $journalId);
$stmt->execute();
$result = $stmt->get_result();
$journal = $result->fetch_assoc();
$stmt->close();

if (!$journal) {
    header("Location: /doctor/journals");
    exit;
}

// Increment view count
$db->query("UPDATE journals SET view_count = view_count + 1 WHERE id = $journalId");

$userId = $_SESSION['user_id'];
$canDownload = hasDownloadPermission($userId, $journalId, 'journal');
$remainingDownloads = getRemainingDownloads($userId, $journalId, 'journal');
$user = getUserById($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($journal['title']); ?> - UCLP Academy</title>
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
           TOP NAVIGATION
           ============================================ */
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 14px;
            gap: 8px;
        }
        .top-nav .btn-back {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.78rem;
            padding: 4px 14px;
            border-radius: 6px;
            border: 1px solid #eef1f5;
            background: transparent;
            color: #6c757d;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .top-nav .btn-back:hover {
            background: #f8f9fa;
            color: #000000;
        }
        .top-nav .badge-specialty {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.7rem;
            padding: 4px 12px;
            border-radius: 12px;
            background: #f8f9fa;
            color: #6c757d;
        }
        
        /* ============================================
           JOURNAL DETAIL CARD
           ============================================ */
        .journal-card {
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #eef1f5;
            overflow: hidden;
        }
        
        .journal-card .journal-header {
            background: linear-gradient(135deg, #198754, #157347);
            padding: 20px 24px;
            color: #ffffff;
        }
        .journal-card .journal-header h2 {
            font-weight: 700;
            font-size: 1.3rem;
            margin: 0;
            font-family: 'Cambria', Georgia, serif;
        }
        .journal-card .journal-header h2 i {
            margin-right: 8px;
        }
        .journal-card .journal-header .journal-name {
            font-size: 0.9rem;
            opacity: 0.9;
            font-family: 'Cambria', Georgia, serif;
            margin-top: 2px;
        }
        .journal-card .journal-header .journal-meta {
            margin-top: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }
        .journal-card .journal-header .journal-meta .badge {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 500;
            font-size: 0.65rem;
            padding: 3px 10px;
            border-radius: 12px;
            background: rgba(255,255,255,0.2);
            color: #ffffff;
        }
        
        /* ============================================
           JOURNAL INFO
           ============================================ */
        .journal-info {
            padding: 20px 24px;
        }
        
        .journal-info .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 30px;
        }
        .journal-info .info-item {
            display: flex;
            padding: 4px 0;
            border-bottom: 1px solid #f0f4f8;
        }
        .journal-info .info-item:last-child {
            border-bottom: none;
        }
        .journal-info .info-item .label {
            font-weight: 600;
            color: #000000;
            font-size: 0.82rem;
            font-family: 'Cambria', Georgia, serif;
            min-width: 90px;
        }
        .journal-info .info-item .value {
            color: #000000;
            font-size: 0.82rem;
            font-family: 'Cambria', Georgia, serif;
        }
        .journal-info .info-item .value code {
            background: #f8f9fa;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
            color: #000000;
        }
        
        /* ============================================
           ABSTRACT
           ============================================ */
        .abstract-section {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #eef1f5;
        }
        .abstract-section .abstract-title {
            font-weight: 700;
            font-size: 0.9rem;
            color: #000000;
            font-family: 'Cambria', Georgia, serif;
            margin-bottom: 6px;
        }
        .abstract-section .abstract-title i {
            margin-right: 6px;
        }
        .abstract-section p {
            color: #000000;
            font-size: 0.85rem;
            font-family: 'Cambria', Georgia, serif;
            line-height: 1.6;
            margin: 0;
        }
        
        /* ============================================
           ACTION BUTTONS
           ============================================ */
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 2px solid #eef1f5;
        }
        .action-buttons .btn {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.8rem;
            padding: 6px 18px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .action-buttons .btn:hover {
            transform: translateY(-1px);
        }
        
        .btn-read { background: #198754; color: #fff; border: none; }
        .btn-read:hover { background: #157347; color: #fff; }
        
        .btn-download { background: #0d6efd; color: #fff; border: none; }
        .btn-download:hover { background: #0a58ca; color: #fff; }
        
        .btn-request-download { background: #fd7e14; color: #fff; border: none; }
        .btn-request-download:hover { background: #dc6b0a; color: #fff; }
        
        .btn-request-print { background: #f39c12; color: #fff; border: none; }
        .btn-request-print:hover { background: #e08e0b; color: #fff; }
        
        .btn-back-action { background: #6c757d; color: #fff; border: none; }
        .btn-back-action:hover { background: #5a6268; color: #fff; }
        
        .btn .badge-count {
            background: rgba(255,255,255,0.2);
            color: #fff;
            font-size: 0.6rem;
            padding: 1px 8px;
            border-radius: 10px;
            margin-left: 4px;
        }
        
        /* ============================================
           DOWNLOAD INFO
           ============================================ */
        .download-info {
            font-size: 0.78rem;
            color: #6c757d;
            font-family: 'Cambria', Georgia, serif;
            text-align: center;
            margin-top: 8px;
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        .download-info .remaining {
            color: #198754;
            font-weight: 600;
        }
        .download-info i {
            margin-right: 4px;
        }
        
        /* ============================================
           MODAL
           ============================================ */
        .modal-content {
            border-radius: 10px;
            border: 1px solid #eef1f5;
            background: #ffffff;
        }
        .modal-header {
            border-bottom: 1px solid #eef1f5;
            padding: 14px 20px;
        }
        .modal-header .modal-title {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 700;
            font-size: 1.1rem;
            color: #000000;
        }
        .modal-body {
            padding: 20px;
        }
        .modal-footer {
            border-top: 1px solid #eef1f5;
            padding: 12px 20px;
        }
        .modal-footer .btn {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 6px 18px;
            border-radius: 6px;
        }
        .modal-icon {
            font-size: 2.5rem;
            color: #fd7e14;
            margin-bottom: 12px;
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
            .journal-card .journal-header {
                padding: 16px 18px;
            }
            .journal-card .journal-header h2 {
                font-size: 1.1rem;
            }
            .journal-info {
                padding: 16px 18px;
            }
            .journal-info .info-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
        
        /* Mobile */
        @media (max-width: 575.98px) {
            .main-content {
                padding: 10px 8px 14px;
            }
            .top-nav {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
            }
            .top-nav .btn-back {
                font-size: 0.7rem;
                padding: 3px 10px;
            }
            .top-nav .badge-specialty {
                font-size: 0.6rem;
                padding: 3px 10px;
            }
            .journal-card .journal-header {
                padding: 14px 14px;
            }
            .journal-card .journal-header h2 {
                font-size: 1rem;
            }
            .journal-card .journal-header h2 i {
                font-size: 0.9rem;
            }
            .journal-card .journal-header .journal-name {
                font-size: 0.8rem;
            }
            .journal-card .journal-header .journal-meta .badge {
                font-size: 0.55rem;
                padding: 2px 8px;
            }
            .journal-info {
                padding: 14px 14px;
            }
            .journal-info .info-item .label {
                font-size: 0.75rem;
                min-width: 70px;
            }
            .journal-info .info-item .value {
                font-size: 0.75rem;
            }
            .abstract-section .abstract-title {
                font-size: 0.82rem;
            }
            .abstract-section p {
                font-size: 0.78rem;
            }
            .action-buttons {
                gap: 6px;
            }
            .action-buttons .btn {
                font-size: 0.72rem;
                padding: 5px 12px;
                flex: 1 1 calc(50% - 4px);
                justify-content: center;
            }
            .download-info {
                font-size: 0.7rem;
                padding: 6px 10px;
            }
            .modal-body {
                padding: 14px;
            }
            .modal-icon {
                font-size: 2rem;
            }
        }
        
        /* Extra Small */
        @media (max-width: 400px) {
            .main-content {
                padding: 8px 4px 10px;
            }
            .journal-card .journal-header {
                padding: 10px 10px;
            }
            .journal-card .journal-header h2 {
                font-size: 0.9rem;
            }
            .journal-info {
                padding: 10px 10px;
            }
            .action-buttons .btn {
                font-size: 0.65rem;
                padding: 4px 8px;
                flex: 1 1 100%;
            }
            .journal-info .info-item .label {
                font-size: 0.7rem;
                min-width: 60px;
            }
            .journal-info .info-item .value {
                font-size: 0.7rem;
            }
            .abstract-section p {
                font-size: 0.72rem;
            }
            .download-info {
                font-size: 0.65rem;
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
            .journal-card {
                background: #ffffff !important;
                border-color: #eef1f5 !important;
            }
            .journal-card .journal-header {
                background: linear-gradient(135deg, #198754, #157347) !important;
            }
            .journal-info .info-item {
                border-bottom-color: #f0f4f8 !important;
            }
            .journal-info .info-item .label {
                color: #000000 !important;
            }
            .journal-info .info-item .value {
                color: #000000 !important;
            }
            .journal-info .info-item .value code {
                background: #f8f9fa !important;
                color: #000000 !important;
            }
            .abstract-section {
                border-top-color: #eef1f5 !important;
            }
            .abstract-section .abstract-title {
                color: #000000 !important;
            }
            .abstract-section p {
                color: #000000 !important;
            }
            .action-buttons {
                border-top-color: #eef1f5 !important;
            }
            .download-info {
                background: #f8f9fa !important;
                color: #6c757d !important;
            }
            .top-nav .btn-back {
                color: #6c757d !important;
                border-color: #eef1f5 !important;
            }
            .top-nav .btn-back:hover {
                background: #f8f9fa !important;
                color: #000000 !important;
            }
            .top-nav .badge-specialty {
                background: #f8f9fa !important;
                color: #6c757d !important;
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
                
                <!-- Top Navigation -->
                <div class="top-nav">
                    <a href="/doctor/journals" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Back to Journals
                    </a>
                    <span class="badge-specialty">
                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($journal['specialty_name']); ?>
                    </span>
                </div>

                <!-- Journal Detail Card -->
                <div class="journal-card">
                    <!-- Header -->
                    <div class="journal-header">
                        <h2>
                            <i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($journal['title']); ?>
                        </h2>
                        <div class="journal-name">
                            <i class="fas fa-book"></i> <?php echo htmlspecialchars($journal['journal_name']); ?>
                        </div>
                        <div class="journal-meta">
                            <?php if ($journal['date']): ?>
                            <span class="badge">
                                <i class="far fa-calendar-alt"></i> <?php echo date('F Y', strtotime($journal['date'])); ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($journal['volume']): ?>
                            <span class="badge">Volume <?php echo $journal['volume']; ?></span>
                            <?php endif; ?>
                            <?php if ($journal['issue']): ?>
                            <span class="badge">Issue <?php echo $journal['issue']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Body -->
                    <div class="journal-info">
                        <div class="info-grid">
                            <div>
                                <div class="info-item">
                                    <span class="label">Journal:</span>
                                    <span class="value"><?php echo htmlspecialchars($journal['journal_name']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="label">Specialty:</span>
                                    <span class="value"><?php echo htmlspecialchars($journal['specialty_name']); ?></span>
                                </div>
                                <?php if ($journal['volume']): ?>
                                <div class="info-item">
                                    <span class="label">Volume:</span>
                                    <span class="value"><?php echo htmlspecialchars($journal['volume']); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($journal['issue']): ?>
                                <div class="info-item">
                                    <span class="label">Issue:</span>
                                    <span class="value"><?php echo htmlspecialchars($journal['issue']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if ($journal['pages']): ?>
                                <div class="info-item">
                                    <span class="label">Pages:</span>
                                    <span class="value"><?php echo htmlspecialchars($journal['pages']); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($journal['doi']): ?>
                                <div class="info-item">
                                    <span class="label">DOI:</span>
                                    <span class="value"><code><?php echo htmlspecialchars($journal['doi']); ?></code></span>
                                </div>
                                <?php endif; ?>
                                <div class="info-item">
                                    <span class="label"><i class="fas fa-eye"></i> Views:</span>
                                    <span class="value"><?php echo number_format($journal['view_count']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="label"><i class="fas fa-download"></i> Downloads:</span>
                                    <span class="value"><?php echo number_format($journal['download_count']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Abstract -->
                        <?php if ($journal['abstract']): ?>
                        <div class="abstract-section">
                            <div class="abstract-title"><i class="fas fa-align-left"></i> Abstract</div>
                            <p><?php echo nl2br(htmlspecialchars($journal['abstract'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <a href="/doctor/read-journal/<?php echo $journal['id']; ?>" class="btn btn-read">
                                <i class="fas fa-book-open"></i> Read Online
                            </a>
                            
                            <?php if ($canDownload): ?>
                            <a href="/download.php?type=journal&id=<?php echo $journal['id']; ?>" class="btn btn-download">
                                <i class="fas fa-download"></i> Download
                                <span class="badge-count"><?php echo $remainingDownloads; ?> left</span>
                            </a>
                            <?php else: ?>
                            <button class="btn btn-request-download" onclick="showDownloadModal(<?php echo $journal['id']; ?>, 'journal', '<?php echo addslashes($journal['title']); ?>')">
                                <i class="fas fa-lock"></i> Request Download
                            </button>
                            <?php endif; ?>
                            
                            <a href="/doctor/request/journal/<?php echo $journal['id']; ?>" class="btn btn-request-print">
                                <i class="fas fa-print"></i> Request Print Copy
                            </a>
                            
                            <a href="/doctor/journals" class="btn btn-back-action">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                        
                        <!-- Download Info -->
                        <?php if ($canDownload): ?>
                        <div class="download-info">
                            <i class="fas fa-info-circle"></i> You have <span class="remaining"><?php echo $remainingDownloads; ?></span> download(s) remaining for this journal.
                        </div>
                        <?php else: ?>
                        <div class="download-info">
                            <i class="fas fa-info-circle"></i> You need permission to download this journal. Click "Request Download" to request access.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- ============================================
    DOWNLOAD PERMISSION MODAL
    ============================================ -->
    <div class="modal fade" id="downloadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-lock" style="color: #fd7e14; margin-right: 8px;"></i> Download Permission Required
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="modal-icon">
                        <i class="fas fa-download"></i>
                    </div>
                    <h5 class="fw-bold mb-2" style="color: #000000; font-family: 'Cambria', Georgia, serif;">You don't have permission to download this item</h5>
                    <p class="text-muted mb-3" id="downloadModalMessage" style="font-family: 'Cambria', Georgia, serif;">
                        You need to request download permission from the admin. 
                        Once approved, you will be able to download this item.
                    </p>
                    <div class="alert alert-info" style="border-radius: 6px; font-family: 'Cambria', Georgia, serif; font-size: 0.8rem;">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Note:</strong> You can download up to <strong>2 times</strong> per approved request.
                    </div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="font-family: 'Cambria', Georgia, serif; font-weight: 600; font-size: 0.85rem; padding: 6px 18px; border-radius: 6px;">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <a href="#" id="requestDownloadLink" class="btn btn-request-download" style="font-family: 'Cambria', Georgia, serif; font-weight: 600; font-size: 0.85rem; padding: 6px 18px; border-radius: 6px; text-decoration: none;">
                            <i class="fas fa-paper-plane"></i> Request Download
                        </a>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <small class="text-muted" style="font-family: 'Cambria', Georgia, serif;">Your request will be reviewed by the admin team.</small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showDownloadModal(itemId, itemType, itemTitle) {
            document.getElementById('requestDownloadLink').href = '/doctor/request-download/' + itemType + '/' + itemId;
            document.getElementById('downloadModalMessage').innerHTML = 
                'You need to request download permission for <strong style="color: #000000;">"' + itemTitle + '"</strong> from the admin. ' +
                'Once approved, you will be able to download this item.';
            var modal = new bootstrap.Modal(document.getElementById('downloadModal'));
            modal.show();
        }
    </script>
</body>
</html>