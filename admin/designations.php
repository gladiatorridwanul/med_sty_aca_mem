<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireAdmin();

$db = Database::getInstance()->getConnection();
$success = $error = null;

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_designation'])) {
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $sort_order = intval($_POST['sort_order'] ?? 0);
    $status = isset($_POST['status']) ? 1 : 0;

    if ($name) {
        $stmt = $db->prepare("INSERT INTO designations (name, description, sort_order, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $name, $description, $sort_order, $status);
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], 'Added Designation', "Designation: $name");
            $success = "Designation added successfully.";
        } else {
            $error = "Failed to add: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Name is required.";
    }
}

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_designation'])) {
    $id = intval($_POST['id']);
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $sort_order = intval($_POST['sort_order'] ?? 0);
    $status = isset($_POST['status']) ? 1 : 0;

    if ($name) {
        $stmt = $db->prepare("UPDATE designations SET name=?, description=?, sort_order=?, status=? WHERE id=?");
        $stmt->bind_param("ssiii", $name, $description, $sort_order, $status, $id);
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], 'Updated Designation', "ID: $id");
            $success = "Designation updated successfully.";
        } else {
            $error = "Failed to update: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Name is required.";
    }
}

// Handle Delete
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $db->prepare("DELETE FROM designations WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], 'Deleted Designation', "ID: $id");
        $success = "Designation deleted successfully.";
    } else {
        $error = "Failed to delete (may be in use by committee members).";
    }
    $stmt->close();
}

// Get all designations with member count
$designations = $db->query("
    SELECT d.*, 
           (SELECT COUNT(*) FROM committee_members WHERE designation_id = d.id) as member_count
    FROM designations d 
    ORDER BY d.sort_order ASC, d.name ASC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Designations - BJDVL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cambria&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Cambria', Georgia, serif; background: #ffffff; color: #000; overflow-x: hidden; font-size: 16px; }
        .main-content { margin-left: 250px; padding: 20px 24px 24px; min-height: 100vh; max-width: calc(100% - 250px); background: #ffffff; transition: all 0.3s ease; }

        .page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; padding: 0 0 14px 0; border-bottom: 1px solid #eef1f5; margin-bottom: 18px; }
        .page-header h1 { font-weight: 700; font-size: 1.5rem; color: #000; margin: 0; }
        .page-header h1 i { color: #0d6efd; margin-right: 10px; }
        .page-header .btn-add { font-weight: 600; padding: 8px 22px; border-radius: 6px; font-size: 0.95rem; }

        .table-card { background: #f8f9fa; border-radius: 8px; border: 1px solid #eef1f5; overflow: hidden; }
        .table-card .table-body { overflow-x: auto; }
        .table-card table { margin: 0; width: 100%; background: #fff; min-width: 700px; }
        .table-card table thead th { background: #f8f9fa; color: #495057; font-weight: 600; border-bottom: 2px solid #eef1f5; padding: 10px 14px; white-space: nowrap; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.3px; }
        .table-card table tbody td { padding: 10px 14px; vertical-align: middle; border-bottom: 1px solid #f0f4f8; font-size: 0.9rem; }
        .table-card table tbody tr:hover { background: #f8f9fa; }

        .badge-status { padding: 4px 12px; border-radius: 12px; font-size: 0.68rem; font-weight: 600; display: inline-block; }
        .badge-status.active { background: #e8f5e9; color: #198754; }
        .badge-status.inactive { background: #fce4ec; color: #dc3545; }
        .badge-count-info { font-size: 0.72rem; background: #e8f0fe; color: #0d6efd; padding: 3px 10px; border-radius: 10px; font-weight: 600; }

        .action-btn { width: 30px; height: 30px; border-radius: 4px; border: none; display: inline-flex; align-items: center; justify-content: center; font-size: 0.8rem; cursor: pointer; text-decoration: none; transition: all 0.2s ease; }
        .action-btn.edit { background: #e8f0fe; color: #0d6efd; }
        .action-btn.edit:hover { background: #0d6efd; color: #fff; }
        .action-btn.delete { background: #fce4ec; color: #dc3545; }
        .action-btn.delete:hover { background: #dc3545; color: #fff; }
        .action-group { display: flex; gap: 5px; align-items: center; }

        .modal-content { border-radius: 10px; border: 1px solid #eef1f5; }
        .modal-header { border-bottom: 1px solid #eef1f5; padding: 16px 22px; }
        .modal-header .modal-title { font-weight: 700; font-size: 1.15rem; }
        .modal-body { padding: 22px; }
        .modal-footer { border-top: 1px solid #eef1f5; padding: 14px 22px; }
        .form-label { font-weight: 600; font-size: 0.9rem; }
        .form-control, .form-select { border-radius: 6px; border: 1.5px solid #eef1f5; font-size: 0.95rem; padding: 8px 14px; }
        .form-control:focus, .form-select:focus { border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13,110,253,0.08); }

        @media (max-width: 991.98px) {
            .main-content { margin-left: 0 !important; max-width: 100% !important; padding: 14px 16px 18px; }
            .page-header h1 { font-size: 1.3rem; }
        }
        @media (max-width: 575.98px) {
            .main-content { padding: 10px 8px 14px; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .page-header .btn-add { width: 100%; }
            .table-card table { min-width: 550px; font-size: 0.8rem; }
            .table-card table thead th { padding: 6px 10px; font-size: 0.65rem; }
            .table-card table tbody td { padding: 6px 10px; font-size: 0.8rem; }
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
                    <h1><i class="fas fa-user-tag"></i> Manage Designations</h1>
                    <button class="btn btn-primary btn-add" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus"></i> Add Designation
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
                                    <th>Designation Name</th>
                                    <th>Description</th>
                                    <th style="width: 100px;">Order</th>
                                    <th style="width: 110px;">Members</th>
                                    <th style="width: 100px;">Status</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($designations)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4" style="color:#6c757d;">
                                        <i class="fas fa-inbox fa-3x d-block mb-3" style="color:#dee2e6;"></i>
                                        No designations yet. Click "Add Designation" to get started.
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($designations as $d): ?>
                                <tr>
                                    <td><strong><?php echo $d['id']; ?></strong></td>
                                    <td><strong><?php echo htmlspecialchars($d['name']); ?></strong></td>
                                    <td><small style="color:#6c757d;"><?php echo htmlspecialchars($d['description'] ?? '—'); ?></small></td>
                                    <td><span class="badge-count-info"><?php echo $d['sort_order']; ?></span></td>
                                    <td><span class="badge-count-info"><?php echo $d['member_count']; ?></span></td>
                                    <td>
                                        <span class="badge-status <?php echo $d['status'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $d['status'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <button class="action-btn edit" onclick='editDesignation(<?php echo json_encode($d); ?>)' title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?delete=1&id=<?php echo $d['id']; ?>" class="action-btn delete" 
                                               onclick="return confirm('Delete this designation? Members under it will also be removed.')" title="Delete">
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
                    <h5 class="modal-title"><i class="fas fa-plus" style="color:#0d6efd;"></i> Add Designation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Designation Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" class="form-control" name="description" placeholder="Optional short description">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" class="form-control" name="sort_order" value="0">
                            <small style="color:#6c757d; font-size:0.75rem;">Lower number appears first</small>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="add_status" name="status" checked>
                            <label class="form-check-label" for="add_status">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_designation" class="btn btn-primary">
                            <i class="fas fa-save"></i> Add
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
                    <h5 class="modal-title"><i class="fas fa-edit" style="color:#0d6efd;"></i> Edit Designation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Designation Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" class="form-control" name="description" id="edit_description">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" class="form-control" name="sort_order" id="edit_sort_order">
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="edit_status" name="status">
                            <label class="form-check-label" for="edit_status">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_designation" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editDesignation(d) {
            document.getElementById('edit_id').value = d.id;
            document.getElementById('edit_name').value = d.name;
            document.getElementById('edit_description').value = d.description || '';
            document.getElementById('edit_sort_order').value = d.sort_order;
            document.getElementById('edit_status').checked = d.status == 1;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
    </script>
</body>
</html>