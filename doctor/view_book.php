<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireLogin();

$bookId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$bookId) {
    header("Location: /doctor/books");
    exit;
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT b.*, s.name as specialty_name FROM books b 
                      JOIN specialties s ON b.specialty_id = s.id 
                      WHERE b.id = ? AND b.status = 'approved'");
$stmt->bind_param("i", $bookId);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();
$stmt->close();

if (!$book) {
    header("Location: /doctor/books");
    exit;
}

// Increment view count
$db->query("UPDATE books SET view_count = view_count + 1 WHERE id = $bookId");

$userId = $_SESSION['user_id'];
$canDownload = hasDownloadPermission($userId, $bookId, 'book');
$remainingDownloads = getRemainingDownloads($userId, $bookId, 'book');
$user = getUserById($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> - UCLP Academy</title>
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
        .top-nav .btn-back:hover { background: #f8f9fa; color: #000000; }
        .top-nav .badge-specialty {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.7rem;
            padding: 4px 12px;
            border-radius: 12px;
            background: #f8f9fa;
            color: #6c757d;
        }
        
        .book-card {
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #eef1f5;
            overflow: hidden;
        }
        .book-card .book-cover-large {
            height: 100%;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            overflow: hidden;
            padding: 20px;
        }
        .book-card .book-cover-large img { max-height: 100%; width: auto; object-fit: contain; }
        .book-card .book-cover-large .placeholder { font-size: 3.5rem; color: #ced4da; }
        
        .book-info { padding: 20px 24px; }
        .book-info h2 { font-weight: 700; font-size: 1.3rem; color: #000000; font-family: 'Cambria', Georgia, serif; margin: 0 0 4px 0; }
        .book-info .author { font-size: 0.9rem; color: #6c757d; font-family: 'Cambria', Georgia, serif; margin-bottom: 12px; }
        .book-info .meta-item { padding: 3px 0; font-size: 0.82rem; font-family: 'Cambria', Georgia, serif; }
        .book-info .meta-item strong { width: 100px; display: inline-block; color: #000000; }
        .book-info .meta-item .value { color: #000000; }
        .book-info .description { margin-top: 12px; padding-top: 12px; border-top: 1px solid #eef1f5; }
        .book-info .description h6 { font-weight: 700; font-size: 0.85rem; color: #000000; font-family: 'Cambria', Georgia, serif; margin-bottom: 4px; }
        .book-info .description p { font-size: 0.82rem; color: #000000; font-family: 'Cambria', Georgia, serif; line-height: 1.5; margin: 0; }
        
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 2px solid #eef1f5;
        }
        .action-buttons .btn {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.78rem;
            padding: 6px 16px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .action-buttons .btn:hover { transform: translateY(-1px); }
        
        .btn-read { background: #0d6efd; color: #fff; border: none; }
        .btn-read:hover { background: #0a58ca; color: #fff; }
        .btn-download { background: #198754; color: #fff; border: none; }
        .btn-download:hover { background: #157347; color: #fff; }
        .btn-request-download { background: #fd7e14; color: #fff; border: none; }
        .btn-request-download:hover { background: #dc6b0a; color: #fff; }
        .btn-request-print { background: #f39c12; color: #fff; border: none; }
        .btn-request-print:hover { background: #e08e0b; color: #fff; }
        .btn-back-action { background: #6c757d; color: #fff; border: none; }
        .btn-back-action:hover { background: #5a6268; color: #fff; }
        .btn .badge-count {
            background: rgba(255,255,255,0.2);
            color: #fff;
            font-size: 0.55rem;
            padding: 1px 8px;
            border-radius: 10px;
            margin-left: 4px;
        }
        
        .download-info {
            font-size: 0.75rem;
            color: #6c757d;
            font-family: 'Cambria', Georgia, serif;
            text-align: center;
            margin-top: 8px;
            padding: 6px 12px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        .download-info .remaining { color: #198754; font-weight: 600; }
        
        /* Modal */
        .modal-content {
            border-radius: 10px;
            border: 1px solid #eef1f5;
            background: #ffffff;
        }
        .modal-header { border-bottom: 1px solid #eef1f5; padding: 14px 20px; }
        .modal-header .modal-title { font-family: 'Cambria', Georgia, serif; font-weight: 700; font-size: 1.1rem; color: #000000; }
        .modal-body { padding: 20px; }
        .modal-footer { border-top: 1px solid #eef1f5; padding: 12px 20px; }
        .modal-icon { font-size: 2.5rem; color: #fd7e14; margin-bottom: 12px; }
        
        @media (max-width: 991.98px) {
            .main-content { margin-left: 0 !important; max-width: 100% !important; padding: 12px 14px 16px; }
            .book-card .book-cover-large { min-height: 200px; }
        }
        
        @media (max-width: 575.98px) {
            .main-content { padding: 10px 8px 14px; }
            .top-nav { flex-direction: column; align-items: flex-start; gap: 6px; }
            .top-nav .btn-back { font-size: 0.7rem; padding: 3px 10px; }
            .top-nav .badge-specialty { font-size: 0.6rem; padding: 3px 10px; }
            .book-card .book-cover-large { min-height: 150px; padding: 10px; }
            .book-card .book-cover-large .placeholder { font-size: 2.5rem; }
            .book-info { padding: 14px; }
            .book-info h2 { font-size: 1.1rem; }
            .book-info .author { font-size: 0.8rem; }
            .book-info .meta-item { font-size: 0.75rem; }
            .book-info .meta-item strong { width: 80px; }
            .book-info .description p { font-size: 0.78rem; }
            .action-buttons .btn { font-size: 0.7rem; padding: 5px 12px; flex: 1 1 calc(50% - 4px); justify-content: center; }
            .download-info { font-size: 0.7rem; padding: 5px 10px; }
        }
        
        @media (max-width: 400px) {
            .main-content { padding: 8px 4px 10px; }
            .book-card .book-cover-large { min-height: 120px; }
            .book-info { padding: 10px; }
            .book-info h2 { font-size: 1rem; }
            .action-buttons .btn { font-size: 0.65rem; padding: 4px 8px; flex: 1 1 100%; }
        }
        
        @media (prefers-color-scheme: dark) {
            body { background: #ffffff !important; }
            .main-content { background: #ffffff !important; }
            .book-card { background: #ffffff !important; border-color: #eef1f5 !important; }
            .book-card .book-cover-large { background: #f8f9fa !important; }
            .book-info h2 { color: #000000 !important; }
            .book-info .author { color: #6c757d !important; }
            .book-info .meta-item strong { color: #000000 !important; }
            .book-info .meta-item .value { color: #000000 !important; }
            .book-info .description { border-top-color: #eef1f5 !important; }
            .book-info .description h6 { color: #000000 !important; }
            .book-info .description p { color: #000000 !important; }
            .action-buttons { border-top-color: #eef1f5 !important; }
            .download-info { background: #f8f9fa !important; color: #6c757d !important; }
            .top-nav .btn-back { color: #6c757d !important; border-color: #eef1f5 !important; }
            .top-nav .btn-back:hover { background: #f8f9fa !important; color: #000000 !important; }
            .top-nav .badge-specialty { background: #f8f9fa !important; color: #6c757d !important; }
            .modal-content { background: #ffffff !important; border-color: #eef1f5 !important; }
            .modal-header { border-bottom-color: #eef1f5 !important; }
            .modal-header .modal-title { color: #000000 !important; }
            .modal-footer { border-top-color: #eef1f5 !important; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/doctor_nav.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/doctor_sidebar.php'; ?>
            
            <main class="main-content">
                <div class="top-nav">
                    <a href="/doctor/books" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Books</a>
                    <span class="badge-specialty"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($book['specialty_name']); ?></span>
                </div>

                <div class="book-card">
                    <div class="row g-0">
                        <div class="col-md-4">
                            <div class="book-cover-large">
                                <?php if ($book['cover_image']): ?>
                                <img src="/uploads/books/<?php echo $book['cover_image']; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" loading="lazy">
                                <?php else: ?>
                                <div class="placeholder"><i class="fas fa-book"></i></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="book-info">
                                <h2><?php echo htmlspecialchars($book['title']); ?></h2>
                                <div class="author"><i class="fas fa-user-edit"></i> <?php echo htmlspecialchars($book['author']); ?></div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="meta-item"><strong>Publisher:</strong> <span class="value"><?php echo htmlspecialchars($book['publisher'] ?? 'N/A'); ?></span></div>
                                        <div class="meta-item"><strong>Year:</strong> <span class="value"><?php echo htmlspecialchars($book['year'] ?? 'N/A'); ?></span></div>
                                        <div class="meta-item"><strong>ISBN:</strong> <span class="value"><?php echo htmlspecialchars($book['isbn'] ?? 'N/A'); ?></span></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="meta-item"><strong><i class="fas fa-eye"></i> Views:</strong> <span class="value"><?php echo number_format($book['view_count']); ?></span></div>
                                        <div class="meta-item"><strong><i class="fas fa-download"></i> Downloads:</strong> <span class="value"><?php echo number_format($book['download_count']); ?></span></div>
                                        <div class="meta-item"><strong>Status:</strong> <span class="value"><span class="badge bg-<?php echo $book['is_watermarked'] ? 'warning' : 'success'; ?>" style="font-size: 0.6rem;"><?php echo $book['is_watermarked'] ? 'Watermarked' : 'Open Access'; ?></span></span></div>
                                    </div>
                                </div>
                                
                                <?php if ($book['description']): ?>
                                <div class="description">
                                    <h6><i class="fas fa-align-left"></i> Description</h6>
                                    <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <div class="action-buttons">
                                    <a href="/doctor/read/<?php echo $book['id']; ?>" class="btn btn-read"><i class="fas fa-book-open"></i> Read Online</a>
                                    
                                    <?php if ($canDownload): ?>
                                    <a href="/download.php?type=book&id=<?php echo $book['id']; ?>" class="btn btn-download">
                                        <i class="fas fa-download"></i> Download <span class="badge-count"><?php echo $remainingDownloads; ?> left</span>
                                    </a>
                                    <?php else: ?>
                                    <button class="btn btn-request-download" onclick="showDownloadModal(<?php echo $book['id']; ?>, 'book', '<?php echo addslashes($book['title']); ?>')">
                                        <i class="fas fa-lock"></i> Request Download
                                    </button>
                                    <?php endif; ?>
                                    
                                    <a href="/doctor/request/book/<?php echo $book['id']; ?>" class="btn btn-request-print"><i class="fas fa-print"></i> Request Print Copy</a>
                                    <a href="/doctor/books" class="btn btn-back-action"><i class="fas fa-arrow-left"></i> Back</a>
                                </div>
                                
                                <?php if ($canDownload): ?>
                                <div class="download-info"><i class="fas fa-info-circle"></i> You have <span class="remaining"><?php echo $remainingDownloads; ?></span> download(s) remaining for this book.</div>
                                <?php else: ?>
                                <div class="download-info"><i class="fas fa-info-circle"></i> You need permission to download this book. Click "Request Download" to request access.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Download Permission Modal -->
    <div class="modal fade" id="downloadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-lock" style="color: #fd7e14; margin-right: 8px;"></i> Download Permission Required</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="modal-icon"><i class="fas fa-download"></i></div>
                    <h5 class="fw-bold mb-2" style="color: #000000; font-family: 'Cambria', Georgia, serif;">You don't have permission to download this item</h5>
                    <p class="text-muted mb-3" id="downloadModalMessage" style="font-family: 'Cambria', Georgia, serif;">You need to request download permission from the admin. Once approved, you will be able to download this item.</p>
                    <div class="alert alert-info" style="border-radius: 6px; font-family: 'Cambria', Georgia, serif; font-size: 0.8rem;"><i class="fas fa-info-circle"></i> <strong>Note:</strong> You can download up to <strong>2 times</strong> per approved request.</div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="font-family: 'Cambria', Georgia, serif; font-weight: 600; font-size: 0.85rem; padding: 6px 18px; border-radius: 6px;"><i class="fas fa-times"></i> Cancel</button>
                        <a href="#" id="requestDownloadLink" class="btn btn-request-download" style="font-family: 'Cambria', Georgia, serif; font-weight: 600; font-size: 0.85rem; padding: 6px 18px; border-radius: 6px; text-decoration: none;"><i class="fas fa-paper-plane"></i> Request Download</a>
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
            document.getElementById('downloadModalMessage').innerHTML = 'You need to request download permission for <strong style="color: #000000;">"' + itemTitle + '"</strong> from the admin. Once approved, you will be able to download this item.';
            var modal = new bootstrap.Modal(document.getElementById('downloadModal'));
            modal.show();
        }
    </script>
</body>
</html>