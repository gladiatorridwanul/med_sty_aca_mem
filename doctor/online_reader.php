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
$stmt = $db->prepare("SELECT * FROM books WHERE id = ? AND status = 'approved'");
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

$user = getUserById($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> - Reader</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cambria&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: #f5f7fa; 
            font-family: 'Cambria', Georgia, serif;
            overflow: hidden;
        }
        
        .reader-navbar {
            background: #ffffff;
            padding: 10px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            border-bottom: 1px solid #eef1f5;
        }
        .reader-navbar .brand {
            color: #000000;
            font-weight: 700;
            font-size: 1rem;
            text-decoration: none;
            font-family: 'Cambria', Georgia, serif;
        }
        .reader-navbar .brand i { margin-right: 10px; color: #0d6efd; }
        .reader-navbar .nav-links a {
            color: #6c757d;
            text-decoration: none;
            padding: 4px 12px;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-size: 0.82rem;
            font-family: 'Cambria', Georgia, serif;
        }
        .reader-navbar .nav-links a:hover { background: #f0f4f8; color: #0d6efd; }
        .reader-navbar .nav-links .badge { 
            font-size: 0.6rem; 
            padding: 3px 10px; 
            font-family: 'Cambria', Georgia, serif;
        }
        
        .reader-container {
            margin-top: 60px;
            height: calc(100vh - 60px);
            position: relative;
            background: #ffffff;
        }
        .reader-container iframe {
            width: 100%;
            height: 100%;
            border: none;
            background: #ffffff;
        }
        
        .watermark-overlay {
            position: fixed;
            bottom: 20px;
            right: 20px;
            color: rgba(0,0,0,0.04);
            font-size: 12px;
            pointer-events: none;
            z-index: 1001;
            font-weight: 700;
            letter-spacing: 2px;
            transform: rotate(-5deg);
            font-family: 'Cambria', Georgia, serif;
        }
        .watermark-text {
            position: fixed;
            bottom: 50%;
            right: 20px;
            color: rgba(0,0,0,0.02);
            font-size: 24px;
            transform: rotate(-90deg);
            transform-origin: right bottom;
            pointer-events: none;
            z-index: 1001;
            white-space: nowrap;
            letter-spacing: 6px;
            font-weight: 800;
            font-family: 'Cambria', Georgia, serif;
        }
        .disable-interaction {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 999;
        }
        
        .reader-info {
            position: fixed;
            bottom: 15px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.7);
            color: #fff;
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 0.65rem;
            z-index: 1002;
            opacity: 0.6;
            pointer-events: none;
            font-family: 'Cambria', Georgia, serif;
        }
        
        .no-file-message {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            flex-direction: column;
            background: #f8f9fa;
        }
        .no-file-message i { font-size: 3.5rem; color: #dee2e6; margin-bottom: 15px; }
        .no-file-message h4 { color: #000000; font-weight: 700; font-family: 'Cambria', Georgia, serif; }
        .no-file-message p { color: #6c757d; font-family: 'Cambria', Georgia, serif; }
        
        @media (max-width: 768px) {
            .reader-navbar .brand { font-size: 0.85rem; }
            .reader-navbar .nav-links a { font-size: 0.7rem; padding: 3px 8px; }
            .watermark-text { font-size: 16px; letter-spacing: 4px; right: 10px; }
            .watermark-overlay { font-size: 9px; bottom: 10px; right: 10px; }
            .reader-info { font-size: 0.55rem; padding: 4px 12px; bottom: 8px; }
            .reader-container { margin-top: 54px; height: calc(100vh - 54px); }
        }
        
        @media (max-width: 576px) {
            .reader-navbar { padding: 6px 12px; }
            .reader-navbar .brand { font-size: 0.78rem; }
            .reader-navbar .nav-links a { font-size: 0.65rem; padding: 2px 6px; }
            .watermark-text { font-size: 12px; letter-spacing: 3px; }
        }
        
        @media (prefers-color-scheme: dark) {
            body { background: #ffffff !important; }
            .reader-navbar { background: #ffffff !important; border-bottom-color: #eef1f5 !important; }
            .reader-navbar .brand { color: #000000 !important; }
            .reader-navbar .nav-links a { color: #6c757d !important; }
            .reader-navbar .nav-links a:hover { background: #f0f4f8 !important; color: #0d6efd !important; }
            .reader-container { background: #ffffff !important; }
            .reader-container iframe { background: #ffffff !important; }
            .no-file-message { background: #f8f9fa !important; }
            .no-file-message h4 { color: #000000 !important; }
        }
    </style>
</head>
<body>
    <nav class="reader-navbar">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="/doctor/books" class="brand">
                        <i class="fas fa-arrow-left"></i> Back to Books
                    </a>
                </div>
                <div class="nav-links d-flex align-items-center gap-1">
                    <span class="text-muted me-1 d-none d-md-inline" style="font-family: 'Cambria', Georgia, serif; font-size: 0.78rem;">
                        <i class="fas fa-book"></i> <?php echo htmlspecialchars(substr($book['title'], 0, 25)) . (strlen($book['title']) > 25 ? '...' : ''); ?>
                    </span>
                    <span class="badge bg-warning text-dark"><i class="fas fa-eye"></i> View Only</span>
                    <span class="badge bg-danger"><i class="fas fa-lock"></i> DRM</span>
                    <a href="/doctor/view-book/<?php echo $book['id']; ?>" class="text-muted"><i class="fas fa-info-circle"></i></a>
                </div>
            </div>
        </div>
    </nav>

    <div class="reader-container">
        <?php if ($book['file_path'] && file_exists(BOOK_UPLOAD_PATH . $book['file_path'])): ?>
        <iframe src="/uploads/books/<?php echo $book['file_path']; ?>#toolbar=0&navpanes=0&scrollbar=0&view=FitH"></iframe>
        <?php else: ?>
        <div class="no-file-message">
            <i class="fas fa-file-pdf"></i>
            <h4>Book File Not Available</h4>
            <p>The PDF file for this book is not available. Please contact the administrator.</p>
            <a href="/doctor/books" class="btn btn-primary mt-3" style="font-family: 'Cambria', Georgia, serif;"><i class="fas fa-arrow-left"></i> Back to Books</a>
        </div>
        <?php endif; ?>
        
        <div class="watermark-overlay">UCLP Academy &bull; <?php echo date('Y'); ?></div>
        <div class="watermark-text">UCLP ACADEMY &bull; <?php echo date('Y-m-d'); ?></div>
        <div class="disable-interaction" id="disableInteraction"></div>
        
        <div class="reader-info">
            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['name'] ?? 'Doctor'); ?> &bull; 
            <i class="fas fa-clock"></i> <?php echo date('h:i A'); ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                alert('Right click is disabled for copyright protection.');
                return false;
            });
            
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && (e.key === 'p' || e.key === 'P')) {
                    e.preventDefault();
                    alert('Printing is disabled for copyright protection.');
                    return false;
                }
                if (e.key === 'PrintScreen') {
                    e.preventDefault();
                    alert('Screenshots are disabled for copyright protection.');
                    return false;
                }
                if (e.ctrlKey && (e.key === 's' || e.key === 'S')) {
                    e.preventDefault();
                    alert('Saving is disabled for copyright protection.');
                    return false;
                }
                if (e.ctrlKey && (e.key === 'c' || e.key === 'C')) {
                    e.preventDefault();
                    alert('Copying is disabled for copyright protection.');
                    return false;
                }
                if (e.ctrlKey && (e.key === 'u' || e.key === 'U')) {
                    e.preventDefault();
                    alert('View source is disabled for copyright protection.');
                    return false;
                }
            });
            
            document.addEventListener('dragstart', function(e) {
                e.preventDefault();
                return false;
            });
            
            document.addEventListener('copy', function(e) {
                e.preventDefault();
                alert('Copying is disabled for copyright protection.');
                return false;
            });
            
            document.addEventListener('cut', function(e) {
                e.preventDefault();
                alert('Cutting is disabled for copyright protection.');
                return false;
            });
            
            console.log('📚 UCLP Academy Reader - DRM Protected');
            console.log('📖 Reading: <?php echo addslashes($book['title']); ?>');
        });
    </script>
</body>
</html>