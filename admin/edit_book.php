<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireAdmin();

$bookId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$bookId) {
    header("Location: /admin/books");
    exit;
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $bookId);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();
$stmt->close();

if (!$book) {
    header("Location: /admin/books");
    exit;
}

$error = null;
$success = null;
$specialties = getAllSpecialties();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $author = sanitize($_POST['author'] ?? '');
    $specialty_id = intval($_POST['specialty_id'] ?? 0);
    $year = intval($_POST['year'] ?? 0);
    $publisher = sanitize($_POST['publisher'] ?? '');
    $isbn = sanitize($_POST['isbn'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $is_watermarked = isset($_POST['is_watermarked']) ? 1 : 0;
    $status = sanitize($_POST['status'] ?? 'pending');
    
    if ($title && $author && $specialty_id) {
        $updateFields = "title = ?, author = ?, specialty_id = ?, year = ?, publisher = ?, isbn = ?, description = ?, is_watermarked = ?, status = ?, updated_at = NOW()";
        $params = "ssiisssis";
        $values = [$title, $author, $specialty_id, $year, $publisher, $isbn, $description, $is_watermarked, $status];
        
        // Handle file upload
        if (isset($_FILES['book_file']) && $_FILES['book_file']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['book_file'], BOOK_UPLOAD_PATH, ['pdf']);
            if ($uploadResult['success']) {
                // Delete old file
                if ($book['file_path'] && file_exists(BOOK_UPLOAD_PATH . $book['file_path'])) {
                    unlink(BOOK_UPLOAD_PATH . $book['file_path']);
                }
                $updateFields .= ", file_path = ?";
                $params .= "s";
                $values[] = $uploadResult['filename'];
            } else {
                $error = 'File upload failed: ' . $uploadResult['error'];
            }
        }
        
        // Handle cover image upload
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['cover_image'], BOOK_UPLOAD_PATH, ['jpg', 'jpeg', 'png', 'gif']);
            if ($uploadResult['success']) {
                // Delete old cover
                if ($book['cover_image'] && file_exists(BOOK_UPLOAD_PATH . $book['cover_image'])) {
                    unlink(BOOK_UPLOAD_PATH . $book['cover_image']);
                }
                $updateFields .= ", cover_image = ?";
                $params .= "s";
                $values[] = $uploadResult['filename'];
            }
        }
        
        if (!$error) {
            $sql = "UPDATE books SET $updateFields WHERE id = ?";
            $params .= "i";
            $values[] = $bookId;
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param($params, ...$values);
            if ($stmt->execute()) {
                logActivity($_SESSION['user_id'], 'Updated Book', "Updated book ID: $bookId");
                $success = "Book updated successfully.";
                // Refresh book data
                $stmt2 = $db->prepare("SELECT * FROM books WHERE id = ?");
                $stmt2->bind_param("i", $bookId);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                $book = $result2->fetch_assoc();
                $stmt2->close();
            } else {
                $error = "Failed to update book: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book - BJDVL</title>
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
            color: #0d6efd;
            margin-right: 8px;
        }
        .page-header .btn-group-header {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .page-header .btn-group-header .btn {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.8rem;
            padding: 5px 14px;
            border-radius: 6px;
        }
        
        /* ============================================
           FORM SECTIONS
           ============================================ */
        .form-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 16px;
        }
        
        .form-card {
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #eef1f5;
            overflow: hidden;
        }
        
        .form-card .card-header {
            padding: 10px 14px;
            border-bottom: 1px solid #eef1f5;
            background: #f8f9fa;
            font-weight: 700;
            font-size: 0.85rem;
            color: #000000;
            font-family: 'Cambria', Georgia, serif;
        }
        .form-card .card-header i {
            margin-right: 6px;
        }
        .form-card .card-body {
            padding: 16px;
        }
        
        .form-section {
            background: #ffffff;
            padding: 14px 16px;
            border-radius: 6px;
            border: 1px solid #eef1f5;
            margin-bottom: 14px;
        }
        .form-section:last-child {
            margin-bottom: 0;
        }
        .form-section .section-title {
            font-weight: 700;
            font-size: 0.78rem;
            color: #0d6efd;
            margin-bottom: 12px;
            font-family: 'Cambria', Georgia, serif;
        }
        
        /* ============================================
           FORM ELEMENTS
           ============================================ */
        .form-label {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            color: #000000;
            font-size: 0.8rem;
        }
        .form-control, .form-select {
            border-radius: 6px;
            border: 1.5px solid #eef1f5;
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.85rem;
            color: #000000;
            background: #ffffff;
            padding: 6px 12px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13,110,253,0.08);
        }
        .form-control:disabled {
            background: #f8f9fa;
            color: #6c757d;
        }
        .form-check-label {
            font-family: 'Cambria', Georgia, serif;
            color: #000000;
            font-size: 0.82rem;
        }
        
        .btn {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.82rem;
            padding: 5px 16px;
            border-radius: 6px;
        }
        .btn-primary { background: #0d6efd; border: none; }
        .btn-primary:hover { background: #0a58ca; }
        .btn-secondary { background: #6c757d; border: none; }
        .btn-secondary:hover { background: #5a6268; }
        
        /* ============================================
           FILE INFO
           ============================================ */
        .file-info {
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 6px;
            border-left: 3px solid #0d6efd;
            margin-top: 6px;
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.75rem;
            color: #000000;
        }
        .file-info strong {
            color: #0d6efd;
        }
        
        .preview-image {
            max-width: 120px;
            max-height: 150px;
            border-radius: 6px;
            border: 2px solid #eef1f5;
            padding: 3px;
            background: #ffffff;
        }
        
        /* ============================================
           STATS SIDEBAR
           ============================================ */
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #f0f4f8;
        }
        .stat-item:last-child {
            border-bottom: none;
        }
        .stat-item .label {
            color: #6c757d;
            font-size: 0.78rem;
            font-family: 'Cambria', Georgia, serif;
        }
        .stat-item .value {
            font-weight: 600;
            color: #000000;
            font-size: 0.82rem;
            font-family: 'Cambria', Georgia, serif;
        }
        .stat-item .value .badge {
            font-size: 0.65rem;
            padding: 2px 10px;
            border-radius: 10px;
        }
        
        .quick-actions {
            display: grid;
            gap: 6px;
        }
        .quick-actions .btn {
            font-size: 0.78rem;
            padding: 5px 12px;
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
            .form-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .page-header h1 {
                font-size: 1.1rem;
            }
            .page-header .btn-group-header .btn {
                font-size: 0.7rem;
                padding: 4px 10px;
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
                gap: 8px;
                padding-bottom: 10px;
                margin-bottom: 12px;
            }
            .page-header h1 {
                font-size: 1rem;
            }
            .page-header h1 i {
                font-size: 0.9rem;
            }
            .page-header .btn-group-header {
                width: 100%;
            }
            .page-header .btn-group-header .btn {
                font-size: 0.68rem;
                padding: 4px 10px;
                flex: 1;
                text-align: center;
            }
            .form-card .card-header {
                font-size: 0.78rem;
                padding: 8px 10px;
            }
            .form-card .card-body {
                padding: 12px;
            }
            .form-section {
                padding: 10px 12px;
                margin-bottom: 10px;
            }
            .form-section .section-title {
                font-size: 0.72rem;
                margin-bottom: 10px;
            }
            .form-label {
                font-size: 0.75rem;
            }
            .form-control, .form-select {
                font-size: 0.82rem;
                padding: 5px 10px;
            }
            .btn {
                font-size: 0.75rem;
                padding: 4px 12px;
                width: 100%;
                margin-top: 4px;
            }
            .file-info {
                font-size: 0.7rem;
                padding: 6px 10px;
            }
            .preview-image {
                max-width: 80px;
                max-height: 100px;
            }
            .stat-item .label {
                font-size: 0.72rem;
            }
            .stat-item .value {
                font-size: 0.75rem;
            }
            .quick-actions .btn {
                font-size: 0.72rem;
                padding: 4px 10px;
            }
            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 8px;
            }
            .d-flex.justify-content-between .d-flex {
                flex-wrap: wrap;
                gap: 4px;
            }
            .d-flex.justify-content-between .d-flex .btn {
                flex: 1;
                min-width: 80px;
            }
        }
        
        /* Extra Small */
        @media (max-width: 400px) {
            .main-content {
                padding: 8px 4px 10px;
            }
            .page-header h1 {
                font-size: 0.9rem;
            }
            .form-card .card-body {
                padding: 10px;
            }
            .form-section {
                padding: 8px 10px;
            }
            .form-control, .form-select {
                font-size: 0.78rem;
                padding: 4px 8px;
            }
            .form-label {
                font-size: 0.7rem;
            }
            .btn {
                font-size: 0.7rem;
                padding: 3px 10px;
            }
            .stat-item .label {
                font-size: 0.68rem;
            }
            .stat-item .value {
                font-size: 0.7rem;
            }
            .preview-image {
                max-width: 60px;
                max-height: 80px;
            }
            .page-header .btn-group-header .btn {
                font-size: 0.62rem;
                padding: 3px 8px;
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
            .form-card {
                background: #f8f9fa !important;
                border-color: #eef1f5 !important;
            }
            .form-card .card-header {
                background: #f8f9fa !important;
                border-bottom-color: #eef1f5 !important;
                color: #000000 !important;
            }
            .form-card .card-body {
                background: #f8f9fa !important;
            }
            .form-section {
                background: #ffffff !important;
                border-color: #eef1f5 !important;
            }
            .form-section .section-title {
                color: #0d6efd !important;
            }
            .form-label {
                color: #000000 !important;
            }
            .form-control, .form-select {
                background: #ffffff !important;
                border-color: #eef1f5 !important;
                color: #000000 !important;
            }
            .form-control:disabled {
                background: #f8f9fa !important;
                color: #6c757d !important;
            }
            .form-check-label {
                color: #000000 !important;
            }
            .file-info {
                background: #f8f9fa !important;
                color: #000000 !important;
            }
            .file-info strong {
                color: #0d6efd !important;
            }
            .stat-item {
                border-bottom-color: #f0f4f8 !important;
            }
            .stat-item .label {
                color: #6c757d !important;
            }
            .stat-item .value {
                color: #000000 !important;
            }
            .page-header {
                border-bottom-color: #eef1f5 !important;
            }
            .page-header h1 {
                color: #000000 !important;
            }
            .preview-image {
                border-color: #eef1f5 !important;
                background: #ffffff !important;
            }
        }
    </style>
</head>
<body>
    <!-- ============================================
    NAVIGATION
    ============================================ -->
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>
    
    <!-- ============================================
    SIDEBAR
    ============================================ -->
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
            
            <!-- ============================================
            MAIN CONTENT
            ============================================ -->
            <main class="main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <h1>
                        <i class="fas fa-book"></i> Edit Book
                    </h1>
                    <div class="btn-group-header">
                        <a href="/admin/books" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                        <a href="/admin/manage_books.php" class="btn btn-outline-primary">
                            <i class="fas fa-list"></i> All Books
                        </a>
                    </div>
                </div>
                
                <!-- Alert Messages -->
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" style="border-radius: 6px; font-family: 'Cambria', Georgia, serif; font-size: 0.85rem;">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" style="border-radius: 6px; font-family: 'Cambria', Georgia, serif; font-size: 0.85rem;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Form Grid -->
                <div class="form-grid">
                    
                    <!-- ============================================
                    LEFT COLUMN - EDIT FORM
                    ============================================ -->
                    <div>
                        <div class="form-card">
                            <div class="card-header">
                                <i class="fas fa-edit"></i> Book Information
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" enctype="multipart/form-data">
                                    <!-- Basic Information -->
                                    <div class="form-section">
                                        <div class="section-title"><i class="fas fa-info-circle"></i> Basic Information</div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Book Title <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="title" 
                                                       value="<?php echo htmlspecialchars($book['title']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Author(s) <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="author" 
                                                       value="<?php echo htmlspecialchars($book['author']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Specialty <span class="text-danger">*</span></label>
                                                <select class="form-select" name="specialty_id" required>
                                                    <option value="">Select Specialty</option>
                                                    <?php foreach ($specialties as $specialty): ?>
                                                    <option value="<?php echo $specialty['id']; ?>" 
                                                            <?php echo $book['specialty_id'] == $specialty['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($specialty['name']); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Publication Year</label>
                                                <input type="number" class="form-control" name="year" 
                                                       value="<?php echo $book['year']; ?>" min="1900" max="<?php echo date('Y'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Publisher Details -->
                                    <div class="form-section">
                                        <div class="section-title"><i class="fas fa-building"></i> Publisher Details</div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Publisher</label>
                                                <input type="text" class="form-control" name="publisher" 
                                                       value="<?php echo htmlspecialchars($book['publisher'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">ISBN</label>
                                                <input type="text" class="form-control" name="isbn" 
                                                       value="<?php echo htmlspecialchars($book['isbn'] ?? ''); ?>" placeholder="978-3-16-148410-0">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Description -->
                                    <div class="form-section">
                                        <div class="section-title"><i class="fas fa-align-left"></i> Description</div>
                                        <div class="mb-3">
                                            <label class="form-label">Book Description</label>
                                            <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($book['description'] ?? ''); ?></textarea>
                                            <small class="text-muted" style="font-family: 'Cambria', Georgia, serif; font-size: 0.7rem;">Provide a brief description of the book content.</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Files & Media -->
                                    <div class="form-section">
                                        <div class="section-title"><i class="fas fa-file-pdf"></i> Files & Media</div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Book PDF</label>
                                                <input type="file" class="form-control" name="book_file" accept=".pdf">
                                                <?php if ($book['file_path']): ?>
                                                <div class="file-info mt-2">
                                                    <i class="fas fa-file-pdf" style="color: #dc3545;"></i> 
                                                    Current: <strong><?php echo htmlspecialchars($book['file_path']); ?></strong>
                                                    <br><small>Leave empty to keep current file</small>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Cover Image</label>
                                                <input type="file" class="form-control" name="cover_image" accept="image/*">
                                                <?php if ($book['cover_image']): ?>
                                                <div class="mt-2">
                                                    <img src="/uploads/books/<?php echo $book['cover_image']; ?>" 
                                                         class="preview-image" alt="Current cover" loading="lazy">
                                                    <br><small class="text-muted" style="font-family: 'Cambria', Georgia, serif; font-size: 0.65rem;">Current: <?php echo htmlspecialchars($book['cover_image']); ?></small>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Settings -->
                                    <div class="form-section">
                                        <div class="section-title"><i class="fas fa-cog"></i> Settings</div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" id="is_watermarked" name="is_watermarked" 
                                                           <?php echo $book['is_watermarked'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="is_watermarked">
                                                        <i class="fas fa-water"></i> Add Watermark (DRM Protection)
                                                    </label>
                                                    <br><small class="text-muted" style="font-family: 'Cambria', Georgia, serif; font-size: 0.65rem;">Prevents unauthorized copying and printing</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" name="status">
                                                    <option value="pending" <?php echo $book['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="approved" <?php echo $book['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                    <option value="rejected" <?php echo $book['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Form Actions -->
                                    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px; margin-top: 4px;">
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Update Book
                                            </button>
                                            <a href="/admin/books" class="btn btn-secondary">Cancel</a>
                                        </div>
                                        <div>
                                            <span class="badge bg-info" style="font-size: 0.7rem; padding: 4px 12px;">Views: <?php echo $book['view_count']; ?></span>
                                            <span class="badge bg-success" style="font-size: 0.7rem; padding: 4px 12px;">Downloads: <?php echo $book['download_count']; ?></span>
                                            <span class="badge bg-secondary" style="font-size: 0.7rem; padding: 4px 12px;">ID: #<?php echo $book['id']; ?></span>
                                        </div>
                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ============================================
                    RIGHT COLUMN - STATS & QUICK ACTIONS
                    ============================================ -->
                    <div>
                        <!-- Book Statistics -->
                        <div class="form-card">
                            <div class="card-header">
                                <i class="fas fa-chart-bar"></i> Book Statistics
                            </div>
                            <div class="card-body">
                                <div class="stat-item">
                                    <span class="label">Status</span>
                                    <span class="value">
                                        <span class="badge bg-<?php echo $book['status'] == 'approved' ? 'success' : ($book['status'] == 'pending' ? 'warning' : 'danger'); ?>" style="font-size: 0.7rem;">
                                            <?php echo ucfirst($book['status']); ?>
                                        </span>
                                    </span>
                                </div>
                                <div class="stat-item">
                                    <span class="label">Uploaded</span>
                                    <span class="value"><?php echo date('F d, Y', strtotime($book['created_at'])); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="label">Last Updated</span>
                                    <span class="value"><?php echo $book['updated_at'] ? date('F d, Y', strtotime($book['updated_at'])) : 'Never'; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="label"><i class="fas fa-eye"></i> Total Views</span>
                                    <span class="value"><?php echo number_format($book['view_count']); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="label"><i class="fas fa-download"></i> Total Downloads</span>
                                    <span class="value"><?php echo number_format($book['download_count']); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="label"><i class="fas fa-water"></i> Watermark</span>
                                    <span class="value"><?php echo $book['is_watermarked'] ? '<span style="color: #198754;">Enabled</span>' : '<span style="color: #6c757d;">Disabled</span>'; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="label"><i class="fas fa-file-pdf"></i> File Size</span>
                                    <span class="value">
                                        <?php 
                                        if ($book['file_path'] && file_exists(BOOK_UPLOAD_PATH . $book['file_path'])) {
                                            $size = filesize(BOOK_UPLOAD_PATH . $book['file_path']);
                                            echo formatFileSize($size);
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="form-card" style="margin-top: 12px;">
                            <div class="card-header">
                                <i class="fas fa-link"></i> Quick Actions
                            </div>
                            <div class="card-body">
                                <div class="quick-actions">
                                    <a href="/doctor/view-book/<?php echo $book['id']; ?>" target="_blank" class="btn btn-outline-primary">
                                        <i class="fas fa-eye"></i> View Book
                                    </a>
                                    <a href="/doctor/read/<?php echo $book['id']; ?>" target="_blank" class="btn btn-outline-success">
                                        <i class="fas fa-book-open"></i> Open Reader
                                    </a>
                                    <?php if ($book['file_path']): ?>
                                    <a href="/uploads/books/<?php echo $book['file_path']; ?>" target="_blank" class="btn btn-outline-secondary">
                                        <i class="fas fa-file-pdf"></i> View PDF
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>