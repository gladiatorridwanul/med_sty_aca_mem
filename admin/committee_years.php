<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireAdmin();

$db = Database::getInstance()->getConnection();
$success = $error = null;

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_year'])) {
    $year_label = sanitize($_POST['year_label'] ?? '');
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $is_current = isset($_POST['is_current']) ? 1 : 0;
    $status = isset($_POST['status']) ? 1 : 0;

    if ($year_label) {
        if ($is_current) {
            $db->query("UPDATE committee_years SET is_current = 0");
        }
        $stmt = $db->prepare("INSERT INTO committee_years (year_label, start_date, end_date, is_current, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssii", $year_label, $start_date, $end_date, $is_current, $status);
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], 'Added Committee Year', "Year: $year_label");
            $success = "Committee year added successfully.";
        } else {
            $error = "Failed: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Year label is required.";
    }
}

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_year'])) {
    $id = intval($_POST['id']);
    $year_label = sanitize($_POST['year_label'] ?? '');
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $is_current = isset($_POST['is_current']) ? 1 : 0;
    $status = isset($_POST['status']) ? 1 : 0;

    if ($year_label) {
        if ($is_current) {
            $db->query("UPDATE committee_years SET is_current = 0");
        }
        $stmt = $db->prepare("UPDATE committee_years SET year_label=?, start_date=?, end_date=?, is_current=?, status=? WHERE id=?");
        $stmt->bind_param("sssiii", $year_label, $start_date, $end_date, $is_current, $status, $id);
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], 'Updated Committee Year', "ID: $id");
            $success = "Committee year updated.";
        } else {
            $error = "Failed: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Year label is required.";
    }
}

// Handle Delete
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $db->prepare("DELETE FROM committee_years WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], 'Deleted Committee Year', "ID: $id");
        $success = "Year deleted (members also removed).";
    } else {
        $error = "Failed to delete.";
    }
    $stmt->close();
}

// Get all years with member count
$years = $db->query("
    SELECT y.*, (SELECT COUNT(*) FROM committee_members WHERE year_id = y.id) as member_count
    FROM committee_years y 
    ORDER BY y.year_label DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Committee Years - BJDVL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cambria&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Cambria', Georgia, serif; background: #ffffff; color: #000; overflow-x: hidden; font-size: 16px; }
        .main-content { margin-left: 250px; padding: 20px 24px 24px; min-height: 100vh; max-width: calc(100% - 250px); background: #fff; }
        .page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; padding: 0 0 14px 0; border-bottom: 1px solid #eef1f5; margin-bottom: 18px; }
        .page-header h1 { font-weight: 700; font-size: 1.5rem; margin: 0; }
        .page-header h1 i { color: #198754; margin-right: 10px; }
        .page-header .btn-add { font-weight: 600; padding: 8px 22px; border-radius: 6px; font-size: 0.95rem; }

        .table-card { background: #f8f9fa; border-radius: 8px; border: 1px solid #eef1f5; overflow: hidden; }
        .table-card .table-body { overflow-x: auto; }
        .table-card table { margin: 0; width: 100%; background: #fff; min-width: 700px; }
        .table-card table thead th { background: #f8f9fa; color: #495057; font-weight: 600; border-bottom: 2px solid #eef1f5; padding: 10px 14px; font-size: 0.78rem; text-transform: uppercase; }
        .table-card table tbody td { padding: 10px 14px; vertical-align: middle; border-bottom: 1px solid #f0f4f8; font-size: 0.9rem; }
        .table-card table tbody tr:hover { background: #f8f9fa; }

        .badge-status { padding: 4px 12px; border-radius: 12px; font-size: 0.68rem; font-weight: 600; }
        .badge-status.active { background: #e8f5e9; color: #198754; }
        .badge-status.inactive { background: #fce4ec; color: #dc3545; }
        .badge-current { padding: 4px 12px; border-radius: 12px; font-size: 0.68rem; font-weight: 700; background: #198754; color: #fff; }
        .badge-count-info { font-size: 0.72rem; background: #e8f0fe; color: #0d6efd; padding: 3px 10px; border-radius: 10px; font-weight: 600; }

        .action-btn { width: 30px; height: 30px; border-radius: 4px; border: none; display: inline-flex; align-items: center; justify-content: center; font-size: 0.8rem; cursor: pointer; text-decoration: none; transition: all 0.2s ease; }
        .action-btn.edit { background: #e8f0fe; color: #0d6efd; }
        .action-btn.edit:hover { background: #0d6efd; color: #fff; }
        .action-btn.delete { background: #fce4ec; color: #dc3545; }
        .action-btn.delete:hover { background: #dc3545; color: #fff; }
        .action-group { display: flex; gap: 5px; }

        .modal-content { border-radius: 10px; border: 1px solid #eef1f5; }
        .modal-header { border-bottom: 1px solid #eef1f5; padding: 16px 22px; }
        .modal-header .modal-title { font-weight: 700; font-size: 1.15rem; }
        .modal-body { padding: 22px; }
        .modal-footer { border-top: 1px solid #eef1f5; padding: 14px 22px; }
        .form-label { font-weight: 600; font-size: 0.9rem; }
        .form-control, .form-select { border-radius: 6px; border: 1.5px solid #eef1f5; padding: 8px 14px; }

        @media (max-width: 991.98px) {
            .main-content { margin-left: 0 !important; max-width: 100% !important; padding: 14px 16px 18px; }
        }
        @media (max-width: 575.98px) {
            .main-content { padding: 10px 8px 14px; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .page-header .btn-add { width: 100%; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

            <main class="main-content">
                <div class="page-header">
                    <h1><i class="fas fa-calendar-alt"></i> Committee Years / Terms</h1>
                    <button class="btn btn-success btn-add" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus"></i> Add Year
                    </button>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" style="border-radius: 6px; font-size: 0.9rem;">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" style="border-radius: 6px; font-size: 0.9rem;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="table-card">
                    <div class="table-body">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 50px;">ID</th>
                                    <th>Year Label</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th style="width: 110px;">Members</th>
                                    <th style="width: 120px;">Current</th>
                                    <th style="width: 100px;">Status</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($years)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4" style="color:#6c757d;">
                                        <i class="fas fa-inbox fa-3x d-block mb-3" style="color:#dee2e6;"></i>
                                        No committee years yet.
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($years as $y): ?>
                                <tr>
                                    <td><strong><?php echo $y['id']; ?></strong></td>
                                    <td><strong style="color:#198754;"><?php echo htmlspecialchars($y['year_label']); ?></strong></td>
                                    <td><small><?php echo $y['start_date'] ? date('M d, Y', strtotime($y['start_date'])) : '—'; ?></small></td>
                                    <td><small><?php echo $y['end_date'] ? date('M d, Y', strtotime($y['end_date'])) : '—'; ?></small></td>
                                    <td><span class="badge-count-info"><?php echo $y['member_count']; ?></span></td>
                                    <td>
                                        <?php if ($y['is_current']): ?>
                                            <span class="badge-current"><i class="fas fa-star"></i> Current</span>
                                        <?php else: ?>
                                            <small style="color:#6c757d;">—</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge-status <?php echo $y['status'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $y['status'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <button class="action-btn edit" onclick='editYear(<?php echo json_encode($y); ?>)' title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?delete=1&id=<?php echo $y['id']; ?>" class="action-btn delete"
                                               onclick="return confirm('Delete this year? All its committee members will also be removed.')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
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

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus" style="color:#198754;"></i> Add Committee Year</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Year Label <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="year_label" placeholder="e.g., 2024-2025" required>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date">
                            </div>
                        </div>
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" id="add_is_current" name="is_current">
                            <label class="form-check-label" for="add_is_current">Mark as Current Committee</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="add_status" name="status" checked>
                            <label class="form-check-label" for="add_status">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_year" class="btn btn-success">
                            <i class="fas fa-save"></i> Add Year
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit" style="color:#198754;"></i> Edit Committee Year</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Year Label <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="year_label" id="edit_year_label" required>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" id="edit_start_date">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date" id="edit_end_date">
                            </div>
                        </div>
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" id="edit_is_current" name="is_current">
                            <label class="form-check-label" for="edit_is_current">Mark as Current</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="edit_status" name="status">
                            <label class="form-check-label" for="edit_status">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_year" class="btn btn-success">
                            <i class="fas fa-save"></i> Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editYear(y) {
            document.getElementById('edit_id').value = y.id;
            document.getElementById('edit_year_label').value = y.year_label;
            document.getElementById('edit_start_date').value = y.start_date || '';
            document.getElementById('edit_end_date').value = y.end_date || '';
            document.getElementById('edit_is_current').checked = y.is_current == 1;
            document.getElementById('edit_status').checked = y.status == 1;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
    </script>
</body>
</html>