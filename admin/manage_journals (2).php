<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireAdmin();

$db = Database::getInstance()->getConnection();

// Handle journal addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_journal'])) {
    $title = sanitize($_POST['title'] ?? '');
    $journal_name = sanitize($_POST['journal_name'] ?? '');
    $specialty_id = intval($_POST['specialty_id'] ?? 0);
    $issue = sanitize($_POST['issue'] ?? '');
    $date = !empty($_POST['date']) ? $_POST['date'] : null;
    $volume = sanitize($_POST['volume'] ?? '');
    $pages = sanitize($_POST['pages'] ?? '');
    $doi = sanitize($_POST['doi'] ?? '');
    $abstract = sanitize($_POST['abstract'] ?? '');
    $is_watermarked = isset($_POST['is_watermarked']) ? 1 : 0;
    $status = sanitize($_POST['status'] ?? 'pending');
    
    if ($title && $journal_name && $specialty_id) {
        // Handle file upload
        $filePath = '';
        if (isset($_FILES['journal_file']) && $_FILES['journal_file']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['journal_file'], JOURNAL_UPLOAD_PATH, ['pdf']);
            if ($uploadResult['success']) {
                $filePath = $uploadResult['filename'];
            } else {
                $error = 'File upload failed: ' . $uploadResult['error'];
            }
        }
        
        if ($filePath) {
            $uploaded_by = $_SESSION['user_id'];
            
            // Build data array
            $data = [
                'title' => $title,
                'journal_name' => $journal_name,
                'specialty_id' => $specialty_id,
                'issue' => $issue,
                'date' => $date,
                'volume' => $volume,
                'pages' => $pages,
                'doi' => $doi,
                'abstract' => $abstract,
                'file_path' => $filePath,
                'is_watermarked' => $is_watermarked,
                'status' => $status,
                'uploaded_by' => $uploaded_by
            ];
            
            // Build the query dynamically
            $columns = array_keys($data);
            $placeholders = array_fill(0, count($columns), '?');
            $sql = "INSERT INTO journals (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $db->prepare($sql);
            
            // Build types string and values array
            $types = '';
            $values = [];
            foreach ($data as $key => $value) {
                if (is_int($value)) {
                    $types .= 'i';
                } else {
                    $types .= 's';
                }
                $values[] = $value;
            }
            
            // Debug: Log the count
            error_log("Number of columns: " . count($columns));
            error_log("Number of values: " . count($values));
            error_log("Types string: $types (length: " . strlen($types) . ")");
            
            // Bind parameters dynamically
            $stmt->bind_param($types, ...$values);
            
            if ($stmt->execute()) {
                logActivity($_SESSION['user_id'], 'Added Journal', "Added journal: $title");
                $success = "Journal added successfully.";
                $_POST = [];
            } else {
                $error = "Failed to add journal: " . $stmt->error;
                error_log("MySQL Error: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $error = "Journal file is required.";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Handle journal deletion
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $journalId = intval($_GET['id']);
    $stmt = $db->prepare("SELECT file_path FROM journals WHERE id = ?");
    $stmt->bind_param("i", $journalId);
    $stmt->execute();
    $result = $stmt->get_result();
    $journal = $result->fetch_assoc();
    $stmt->close();
    
    if ($journal && $journal['file_path'] && file_exists(JOURNAL_UPLOAD_PATH . $journal['file_path'])) {
        unlink(JOURNAL_UPLOAD_PATH . $journal['file_path']);
    }
    
    $stmt = $db->prepare("DELETE FROM journals WHERE id = ?");
    $stmt->bind_param("i", $journalId);
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], 'Deleted Journal', "Deleted journal ID: $journalId");
        $success = "Journal deleted successfully.";
    } else {
        $error = "Failed to delete journal.";
    }
    $stmt->close();
}

// Handle status update
if (isset($_POST['update_status']) && isset($_POST['journal_id'])) {
    $journalId = intval($_POST['journal_id']);
    $status = sanitize($_POST['status'] ?? 'pending');
    
    $stmt = $db->prepare("UPDATE journals SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $status, $journalId);
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], 'Updated Journal Status', "Journal ID: $journalId, Status: $status");
        $success = "Journal status updated successfully.";
    } else {
        $error = "Failed to update journal status.";
    }
    $stmt->close();
}

// Get all journals
$journals = $db->query("SELECT j.*, s.name as specialty_name, u.name as uploaded_by_name 
                        FROM journals j 
                        LEFT JOIN specialties s ON j.specialty_id = s.id 
                        LEFT JOIN users u ON j.uploaded_by = u.id 
                        ORDER BY j.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$specialties = getAllSpecialties();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Journals - UCLP Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4" style="padding: 20px;">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-newspaper text-success"></i> Manage Journals</h1>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addJournalModal">
                        <i class="fas fa-plus"></i> Add New Journal
                    </button>
                </div>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="journalsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Journal</th>
                                        <th>Specialty</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($journals as $journal): ?>
                                    <tr>
                                        <td><?php echo $journal['id']; ?></td>
                                        <td><?php echo htmlspecialchars($journal['title']); ?></td>
                                        <td><?php echo htmlspecialchars($journal['journal_name']); ?></td>
                                        <td><?php echo htmlspecialchars($journal['specialty_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $journal['status'] == 'approved' ? 'success' : ($journal['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst($journal['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <form method="POST" action="" class="d-inline">
                                                    <input type="hidden" name="journal_id" value="<?php echo $journal['id']; ?>">
                                                    <select name="status" class="form-select form-select-sm d-inline-block" style="width: auto;" onchange="this.form.submit()">
                                                        <option value="pending" <?php echo $journal['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="approved" <?php echo $journal['status'] == 'approved' ? 'selected' : ''; ?>>Approve</option>
                                                        <option value="rejected" <?php echo $journal['status'] == 'rejected' ? 'selected' : ''; ?>>Reject</option>
                                                    </select>
                                                    <input type="hidden" name="update_status" value="1">
                                                </form>
                                                <a href="/admin/edit_journal.php?id=<?php echo $journal['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?delete=1&id=<?php echo $journal['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this journal?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Add Journal Modal -->
    <div class="modal fade" id="addJournalModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Add New Journal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="title" class="form-label">Article Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="journal_name" class="form-label">Journal Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="journal_name" name="journal_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="specialty_id" class="form-label">Specialty <span class="text-danger">*</span></label>
                                <select class="form-select" id="specialty_id" name="specialty_id" required>
                                    <option value="">Select Specialty</option>
                                    <?php foreach ($specialties as $specialty): ?>
                                    <option value="<?php echo $specialty['id']; ?>">
                                        <?php echo htmlspecialchars($specialty['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="date" name="date">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="volume" class="form-label">Volume</label>
                                <input type="text" class="form-control" id="volume" name="volume">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="issue" class="form-label">Issue</label>
                                <input type="text" class="form-control" id="issue" name="issue">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="pages" class="form-label">Pages</label>
                                <input type="text" class="form-control" id="pages" name="pages">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="doi" class="form-label">DOI</label>
                                <input type="text" class="form-control" id="doi" name="doi">
                            </div>
                            <div class="col-12 mb-3">
                                <label for="abstract" class="form-label">Abstract</label>
                                <textarea class="form-control" id="abstract" name="abstract" rows="3"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="journal_file" class="form-label">Journal PDF <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="journal_file" name="journal_file" accept=".pdf" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="is_watermarked" name="is_watermarked" checked>
                                    <label class="form-check-label" for="is_watermarked">Add Watermark</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_journal" class="btn btn-success">
                            <i class="fas fa-save"></i> Add Journal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#journalsTable').DataTable({
                "pageLength": 25,
                "order": [[0, "desc"]]
            });
        });
    </script>
</body>
</html>