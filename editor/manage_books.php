<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireEditor();

$db = Database::getInstance()->getConnection();

// Handle book addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book'])) {
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
        $filePath = '';
        if (isset($_FILES['book_file']) && $_FILES['book_file']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['book_file'], BOOK_UPLOAD_PATH, ['pdf']);
            if ($uploadResult['success']) {
                $filePath = $uploadResult['filename'];
            } else {
                $error = 'File upload failed: ' . $uploadResult['error'];
            }
        }
        
        $coverImage = '';
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['cover_image'], BOOK_UPLOAD_PATH, ['jpg', 'jpeg', 'png', 'gif']);
            if ($uploadResult['success']) {
                $coverImage = $uploadResult['filename'];
            }
        }
        
        if ($filePath) {
            $stmt = $db->prepare("INSERT INTO books (title, author, specialty_id, year, publisher, isbn, description, cover_image, file_path, is_watermarked, status, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $uploaded_by = $_SESSION['user_id'];
            $stmt->bind_param("ssiissssisi", $title, $author, $specialty_id, $year, $publisher, $isbn, $description, $coverImage, $filePath, $is_watermarked, $status, $uploaded_by);
            if ($stmt->execute()) {
                logActivity($_SESSION['user_id'], 'Added Book', "Added book: $title");
                $success = "Book added successfully.";
                $_POST = [];
            } else {
                $error = "Failed to add book: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Book file is required.";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Handle status update
if (isset($_POST['update_status']) && isset($_POST['book_id'])) {
    $bookId = intval($_POST['book_id']);
    $status = sanitize($_POST['status'] ?? 'pending');
    
    $stmt = $db->prepare("UPDATE books SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $status, $bookId);
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], 'Updated Book Status', "Book ID: $bookId, Status: $status");
        $success = "Book status updated successfully.";
    } else {
        $error = "Failed to update book status.";
    }
    $stmt->close();
}

// Get all books
$books = $db->query("SELECT b.*, s.name as specialty_name, u.name as uploaded_by_name 
                     FROM books b 
                     LEFT JOIN specialties s ON b.specialty_id = s.id 
                     LEFT JOIN users u ON b.uploaded_by = u.id 
                     ORDER BY b.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$specialties = getAllSpecialties();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books - Editor - UCLP Academy</title>
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
        .page-header h1 i { color: #198754; margin-right: 8px; }
        .page-header .btn-add {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            padding: 6px 18px;
            border-radius: 6px;
            font-size: 0.85rem;
        }
        
        .table-card {
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #eef1f5;
            overflow: hidden;
        }
        .table-card .table-body { padding: 0; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .table-card .table-body table {
            margin: 0;
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.78rem;
            width: 100%;
            min-width: 850px;
            background: #ffffff;
        }
        .table-card .table-body table thead th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            border-bottom: 2px solid #eef1f5;
            padding: 6px 10px;
            white-space: nowrap;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .table-card .table-body table tbody td {
            padding: 6px 10px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f4f8;
            color: #000000;
            font-size: 0.78rem;
        }
        .table-card .table-body table tbody tr:hover { background: #f8f9fa; }
        .table-card .table-body table tbody tr:last-child td { border-bottom: none; }
        
        .book-cover-small {
            width: 40px; height: 56px; object-fit: cover;
            border-radius: 4px; border: 1px solid #eef1f5;
        }
        .book-placeholder-small {
            width: 40px; height: 56px; background: #f8f9fa;
            display: flex; align-items: center; justify-content: center;
            border-radius: 4px; border: 1px solid #eef1f5;
            color: #adb5bd; font-size: 1.2rem;
        }
        
        .status-badge {
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 0.6rem;
            font-weight: 600;
            display: inline-block;
            font-family: 'Cambria', Georgia, serif;
        }
        .status-badge.approved { background: #e8f5e9; color: #198754; }
        .status-badge.pending { background: #fff3e0; color: #f39c12; }
        .status-badge.rejected { background: #fce4ec; color: #dc3545; }
        
        .action-btn {
            width: 28px; height: 28px; border-radius: 4px; border: none;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 0.7rem; transition: all 0.2s ease;
            cursor: pointer; text-decoration: none;
        }
        .action-btn:hover { transform: translateY(-1px); }
        .action-btn.edit { background: #e8f0fe; color: #0d6efd; }
        .action-btn.edit:hover { background: #0d6efd; color: #fff; }
        .action-group { display: flex; gap: 4px; flex-wrap: wrap; align-items: center; }
        
        .status-select {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 4px;
            border: 1.5px solid #eef1f5;
            background: #ffffff;
            color: #000000;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .status-select:focus { border-color: #198754; outline: none; }
        
        .modal-content {
            border-radius: 10px;
            border: 1px solid #eef1f5;
            background: #ffffff;
        }
        .modal-header { border-bottom: 1px solid #eef1f5; padding: 14px 20px; }
        .modal-header .modal-title { font-family: 'Cambria', Georgia, serif; font-weight: 700; font-size: 1.1rem; color: #000000; }
        .modal-body { padding: 20px; }
        .modal-footer { border-top: 1px solid #eef1f5; padding: 12px 20px; }
        .form-label { font-family: 'Cambria', Georgia, serif; font-weight: 600; color: #000000; font-size: 0.82rem; }
        .form-control, .form-select {
            border-radius: 6px;
            border: 1.5px solid #eef1f5;
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.9rem;
            color: #000000;
            background: #ffffff;
            padding: 7px 12px;
        }
        .form-control:focus, .form-select:focus { border-color: #198754; box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.08); }
        
        @media (max-width: 991.98px) {
            .main-content { margin-left: 0 !important; max-width: 100% !important; padding: 12px 14px 16px; }
            .page-header h1 { font-size: 1.1rem; }
            .table-card .table-body table { min-width: 700px; }
        }
        
        @media (max-width: 575.98px) {
            .main-content { padding: 10px 8px 14px; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 6px; padding-bottom: 10px; margin-bottom: 12px; }
            .page-header h1 { font-size: 1rem; }
            .page-header .btn-add { font-size: 0.7rem; padding: 4px 12px; width: 100%; }
            .table-card .table-body table { min-width: 500px; font-size: 0.72rem; }
            .table-card .table-body table thead th { padding: 4px 6px; font-size: 0.55rem; }
            .table-card .table-body table tbody td { padding: 4px 6px; font-size: 0.72rem; }
            .status-badge { font-size: 0.5rem; padding: 1px 6px; }
            .action-btn { width: 24px; height: 24px; font-size: 0.6rem; }
            .status-select { font-size: 0.6rem; padding: 1px 4px; }
            .book-cover-small { width: 30px; height: 42px; }
            .book-placeholder-small { width: 30px; height: 42px; font-size: 0.9rem; }
            .modal-body { padding: 14px; }
        }
        
        @media (max-width: 400px) {
            .table-card .table-body table { min-width: 430px; font-size: 0.68rem; }
            .action-btn { width: 20px; height: 20px; font-size: 0.5rem; }
            .page-header h1 { font-size: 0.9rem; }
            .book-cover-small { width: 24px; height: 34px; }
            .book-placeholder-small { width: 24px; height: 34px; font-size: 0.7rem; }
        }
        
        @media (prefers-color-scheme: dark) {
            body { background: #ffffff !important; }
            .main-content { background: #ffffff !important; }
            .table-card { background: #f8f9fa !important; border-color: #eef1f5 !important; }
            .table-card .table-body table { background: #ffffff !important; }
            .table-card .table-body table thead th { background: #f8f9fa !important; color: #495057 !important; border-bottom-color: #eef1f5 !important; }
            .table-card .table-body table tbody td { color: #000000 !important; border-bottom-color: #f0f4f8 !important; }
            .page-header { border-bottom-color: #eef1f5 !important; }
            .page-header h1 { color: #000000 !important; }
            .status-select { background: #ffffff !important; border-color: #eef1f5 !important; color: #000000 !important; }
            .modal-content { background: #ffffff !important; border-color: #eef1f5 !important; }
            .modal-header { border-bottom-color: #eef1f5 !important; }
            .modal-header .modal-title { color: #000000 !important; }
            .form-control, .form-select { background: #ffffff !important; border-color: #eef1f5 !important; color: #000000 !important; }
            .form-label { color: #000000 !important; }
            .book-placeholder-small { background: #f8f9fa !important; border-color: #eef1f5 !important; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/editor_nav.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/editor_sidebar.php'; ?>
            
            <main class="main-content">
                <div class="page-header">
                    <h1><i class="fas fa-book"></i> Manage Books</h1>
                    <button class="btn btn-success btn-add" data-bs-toggle="modal" data-bs-target="#addBookModal"><i class="fas fa-plus"></i> Add New Book</button>
                </div>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" style="border-radius: 6px; font-family: 'Cambria', Georgia, serif; font-size: 0.85rem;"><i class="fas fa-check-circle"></i> <?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" style="border-radius: 6px; font-family: 'Cambria', Georgia, serif; font-size: 0.85rem;"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
                
                <div class="table-card">
                    <div class="table-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cover</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Specialty</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($books)): ?>
                                <tr><td colspan="7" class="text-center py-4" style="color: #6c757d; font-family: 'Cambria', Georgia, serif;"><i class="fas fa-inbox fa-2x d-block mb-2" style="color: #dee2e6;"></i>No books found. Click "Add New Book" to get started.</td></tr>
                                <?php else: ?>
                                <?php foreach ($books as $book): ?>
                                <tr>
                                    <td><strong><?php echo $book['id']; ?></strong></td>
                                    <td>
                                        <?php if ($book['cover_image']): ?>
                                        <img src="/uploads/books/<?php echo $book['cover_image']; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="book-cover-small" loading="lazy">
                                        <?php else: ?>
                                        <div class="book-placeholder-small"><i class="fas fa-book"></i></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($book['title']); ?></strong></td>
                                    <td><small><?php echo htmlspecialchars($book['author']); ?></small></td>
                                    <td><small><?php echo htmlspecialchars($book['specialty_name']); ?></small></td>
                                    <td><span class="status-badge <?php echo $book['status']; ?>"><?php echo ucfirst($book['status']); ?></span></td>
                                    <td>
                                        <div class="action-group">
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                                <select name="status" class="status-select" onchange="this.form.submit()">
                                                    <option value="pending" <?php echo $book['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="approved" <?php echo $book['status'] == 'approved' ? 'selected' : ''; ?>>Approve</option>
                                                    <option value="rejected" <?php echo $book['status'] == 'rejected' ? 'selected' : ''; ?>>Reject</option>
                                                </select>
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
                                            <a href="/admin/edit_book.php?id=<?php echo $book['id']; ?>" class="action-btn edit" title="Edit Book"><i class="fas fa-edit"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <div class="modal fade" id="addBookModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus" style="color: #198754;"></i> Add New Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Title <span class="text-danger">*</span></label><input type="text" class="form-control" name="title" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Author <span class="text-danger">*</span></label><input type="text" class="form-control" name="author" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Specialty <span class="text-danger">*</span></label><select class="form-select" name="specialty_id" required><option value="">Select Specialty</option><?php foreach ($specialties as $specialty): ?><option value="<?php echo $specialty['id']; ?>"><?php echo htmlspecialchars($specialty['name']); ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Year</label><input type="number" class="form-control" name="year" min="1900" max="<?php echo date('Y'); ?>"></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Publisher</label><input type="text" class="form-control" name="publisher"></div>
                            <div class="col-md-6 mb-3"><label class="form-label">ISBN</label><input type="text" class="form-control" name="isbn"></div>
                            <div class="col-12 mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"></textarea></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Book PDF <span class="text-danger">*</span></label><input type="file" class="form-control" name="book_file" accept=".pdf" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Cover Image</label><input type="file" class="form-control" name="cover_image" accept="image/*"></div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check"><input type="checkbox" class="form-check-input" id="is_watermarked" name="is_watermarked" checked><label class="form-check-label" for="is_watermarked">Add Watermark</label></div>
                            </div>
                            <div class="col-md-6 mb-3"><label class="form-label">Status</label><select class="form-select" name="status"><option value="pending">Pending</option><option value="approved">Approved</option><option value="rejected">Rejected</option></select></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_book" class="btn btn-success"><i class="fas fa-save"></i> Add Book</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>