<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireAdmin();

$db = Database::getInstance()->getConnection();
$success = $error = null;

$uploadDir = __DIR__ . '/../uploads/committee/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
    $year_id = intval($_POST['year_id']);
    $designation_id = intval($_POST['designation_id']);
    $name = sanitize($_POST['name'] ?? '');
    $affiliation = sanitize($_POST['affiliation'] ?? '');
    $bio = sanitize($_POST['bio'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $status = isset($_POST['status']) ? 1 : 0;

    $photo = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['photo'], $uploadDir, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        if ($uploadResult['success']) {
            $photo = $uploadResult['filename'];
        } else {
            $error = 'Photo upload failed: ' . $uploadResult['error'];
        }
    }

    if ($name && $year_id && $designation_id && !$error) {
        $stmt = $db->prepare("INSERT INTO committee_members (year_id, designation_id, name, photo, affiliation, bio, email, phone, display_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissssssii", $year_id, $designation_id, $name, $photo, $affiliation, $bio, $email, $phone, $display_order, $status);
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], 'Added Committee Member', "Name: $name");
            $success = "Member added successfully.";
        } else {
            $error = "Failed: " . $stmt->error;
        }
        $stmt->close();
    } elseif (!$error) {
        $error = "Please fill all required fields.";
    }
}

// Handle Delete
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $db->prepare("SELECT photo FROM committee_members WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row && $row['photo'] && file_exists($uploadDir . $row['photo'])) {
        @unlink($uploadDir . $row['photo']);
    }
    $stmt = $db->prepare("DELETE FROM committee_members WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], 'Deleted Committee Member', "ID: $id");
        $success = "Member deleted.";
    } else {
        $error = "Failed to delete.";
    }
    $stmt->close();
}

// Filters
$filterYear = isset($_GET['year']) ? intval($_GET['year']) : 0;
$filterDesignation = isset($_GET['designation']) ? intval($_GET['designation']) : 0;
$searchQuery = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$where = [];
$params = [];
$types = "";

if ($filterYear > 0) {
    $where[] = "m.year_id = ?";
    $params[] = $filterYear;
    $types .= "i";
}
if ($filterDesignation > 0) {
    $where[] = "m.designation_id = ?";
    $params[] = $filterDesignation;
    $types .= "i";
}
if ($searchQuery) {
    $searchTerm = '%' . $searchQuery . '%';
    $where[] = "(m.name LIKE ? OR m.affiliation LIKE ? OR m.email LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT m.*, y.year_label, d.name as designation_name 
        FROM committee_members m 
        JOIN committee_years y ON m.year_id = y.id 
        JOIN designations d ON m.designation_id = d.id 
        $whereClause
        ORDER BY y.year_label DESC, d.sort_order ASC, m.display_order ASC, m.name ASC";

$stmt = $db->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$years = $db->query("SELECT * FROM committee_years WHERE status = 1 ORDER BY year_label DESC")->fetch_all(MYSQLI_ASSOC);
$designations = $db->query("SELECT * FROM designations WHERE status = 1 ORDER BY sort_order ASC, name ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Committee Members - BJDVL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cambria&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Cambria', Georgia, serif; background: #fff; color: #000; overflow-x: hidden; font-size: 16px; }
        .main-content { margin-left: 250px; padding: 20px 24px 24px; min-height: 100vh; max-width: calc(100% - 250px); background: #fff; }

        .page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; padding: 0 0 14px 0; border-bottom: 1px solid #eef1f5; margin-bottom: 18px; }
        .page-header h1 { font-weight: 700; font-size: 1.5rem; margin: 0; }
        .page-header h1 i { color: #6f42c1; margin-right: 10px; }
        .page-header .btn-add { font-weight: 600; padding: 8px 22px; border-radius: 6px; font-size: 0.95rem; }

        .filter-section { background: #f8f9fa; border-radius: 8px; border: 1px solid #eef1f5; padding: 14px 18px; margin-bottom: 16px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .filter-section .filter-group { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; flex: 1; }
        .filter-section .form-control, .filter-section .form-select { font-size: 0.9rem; padding: 6px 12px; border-radius: 6px; border: 1.5px solid #eef1f5; min-width: 150px; }
        .filter-section .btn { font-weight: 600; font-size: 0.85rem; padding: 6px 18px; border-radius: 6px; }
        .filter-section .btn-clear { font-size: 0.85rem; color: #6c757d; text-decoration: none; padding: 6px 14px; }
        .filter-section .btn-clear:hover { color: #dc3545; }

        .member-card-admin {
            background: #f8f9fa; border: 1px solid #eef1f5; border-radius: 8px; padding: 14px 16px;
            margin-bottom: 10px; display: flex; gap: 14px; align-items: center; transition: all 0.2s;
        }
        .member-card-admin:hover { background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.04); }
        .member-card-admin .photo-wrap {
            width: 60px; height: 60px; border-radius: 50%; overflow: hidden;
            background: linear-gradient(135deg, #e8e0f9, #d4c7f5);
            display: flex; align-items: center; justify-content: center;
            color: #6f42c1; font-size: 1.4rem; flex-shrink: 0; font-weight: 700;
        }
        .member-card-admin .photo-wrap img { width: 100%; height: 100%; object-fit: cover; }
        .member-card-admin .info { flex: 1; min-width: 0; }
        .member-card-admin .info h6 { font-weight: 700; font-size: 1rem; margin-bottom: 2px; }
        .member-card-admin .info .desig { font-size: 0.82rem; color: #6f42c1; font-weight: 600; }
        .member-card-admin .info .affil { font-size: 0.78rem; color: #6c757d; margin-top: 2px; }
        .member-card-admin .info .meta { font-size: 0.72rem; color: #6c757d; margin-top: 4px; display: flex; gap: 8px; flex-wrap: wrap; }
        .member-card-admin .actions { display: flex; gap: 6px; flex-shrink: 0; }

        .badge-status { padding: 3px 10px; border-radius: 10px; font-size: 0.65rem; font-weight: 600; }
        .badge-status.active { background: #e8f5e9; color: #198754; }
        .badge-status.inactive { background: #fce4ec; color: #dc3545; }
        .badge-year { font-size: 0.7rem; background: #198754; color: #fff; padding: 3px 10px; border-radius: 10px; font-weight: 600; }

        .action-btn { width: 30px; height: 30px; border-radius: 4px; border: none; display: inline-flex; align-items: center; justify-content: center; font-size: 0.8rem; cursor: pointer; text-decoration: none; transition: all 0.2s; }
        .action-btn.edit { background: #e8f0fe; color: #0d6efd; }
        .action-btn.edit:hover { background: #0d6efd; color: #fff; }
        .action-btn.delete { background: #fce4ec; color: #dc3545; }
        .action-btn.delete:hover { background: #dc3545; color: #fff; }

        .empty-state { text-align: center; padding: 60px 20px; color: #6c757d; }
        .empty-state i { font-size: 3rem; color: #dee2e6; margin-bottom: 12px; display: block; }

        .modal-content { border-radius: 10px; border: 1px solid #eef1f5; }
        .modal-header { border-bottom: 1px solid #eef1f5; padding: 16px 22px; }
        .modal-header .modal-title { font-weight: 700; font-size: 1.15rem; }
        .modal-body { padding: 22px; }
        .modal-footer { border-top: 1px solid #eef1f5; padding: 14px 22px; }
        .form-label { font-weight: 600; font-size: 0.9rem; }
        .form-control, .form-select { border-radius: 6px; border: 1.5px solid #eef1f5; padding: 8px 14px; }

        .photo-preview-wrap {
            width: 90px; height: 90px; border-radius: 50%; overflow: hidden;
            border: 2px dashed #dee2e6; display: flex; align-items: center; justify-content: center;
            color: #adb5bd; font-size: 1.8rem; margin: 0 auto 10px; cursor: pointer;
        }
        .photo-preview-wrap img { width: 100%; height: 100%; object-fit: cover; }

        @media (max-width: 991.98px) {
            .main-content { margin-left: 0 !important; max-width: 100% !important; padding: 14px 16px 18px; }
            .page-header h1 { font-size: 1.3rem; }
        }
        @media (max-width: 575.98px) {
            .main-content { padding: 10px 8px 14px; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .page-header .btn-add { width: 100%; }
            .filter-section { flex-direction: column; align-items: stretch; }
            .member-card-admin { flex-direction: column; align-items: flex-start; text-align: center; }
            .member-card-admin .photo-wrap { margin: 0 auto; }
            .member-card-admin .info { text-align: center; width: 100%; }
            .member-card-admin .actions { margin: 0 auto; }
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
                    <h1><i class="fas fa-users-cog"></i> Committee Members</h1>
                    <button class="btn btn-primary btn-add" data-bs-toggle="modal" data-bs-target="#addModal"
                            <?php echo (empty($years) || empty($designations)) ? 'disabled title="Add years & designations first"' : ''; ?>>
                        <i class="fas fa-plus"></i> Add Member
                    </button>
                </div>

                <?php if (empty($years)): ?>
                    <div class="alert alert-warning" style="border-radius: 6px; font-size: 0.9rem;">
                        <i class="fas fa-exclamation-triangle"></i> You must add at least one <a href="committee_years.php">Committee Year</a> before adding members.
                    </div>
                <?php endif; ?>
                <?php if (empty($designations)): ?>
                    <div class="alert alert-warning" style="border-radius: 6px; font-size: 0.9rem;">
                        <i class="fas fa-exclamation-triangle"></i> You must add at least one <a href="designations.php">Designation</a> before adding members.
                    </div>
                <?php endif; ?>

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

                <!-- Filter -->
                <div class="filter-section">
                    <form method="GET" class="filter-group d-flex flex-wrap gap-2" style="flex:1;">
                        <input type="text" class="form-control" name="search" placeholder="Search by name, affiliation, email..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                        <select class="form-select" name="year">
                            <option value="0">All Years</option>
                            <?php foreach ($years as $y): ?>
                                <option value="<?php echo $y['id']; ?>" <?php echo $filterYear == $y['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($y['year_label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select class="form-select" name="designation">
                            <option value="0">All Designations</option>
                            <?php foreach ($designations as $d): ?>
                                <option value="<?php echo $d['id']; ?>" <?php echo $filterDesignation == $d['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($d['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <?php if ($filterYear || $filterDesignation || $searchQuery): ?>
                            <a href="committee_members.php" class="btn-clear">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Members List -->
                <?php if (empty($members)): ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h5>No members found</h5>
                        <p>Add committee members using the button above.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($members as $m): ?>
                    <div class="member-card-admin">
                        <div class="photo-wrap">
                            <?php if (!empty($m['photo']) && file_exists($uploadDir . $m['photo'])): ?>
                                <img src="/uploads/committee/<?php echo htmlspecialchars($m['photo']); ?>" alt="<?php echo htmlspecialchars($m['name']); ?>">
                            <?php else: ?>
                                <?php echo strtoupper(substr($m['name'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div class="info">
                            <h6><?php echo htmlspecialchars($m['name']); ?></h6>
                            <div class="desig">
                                <i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($m['designation_name']); ?>
                            </div>
                            <?php if ($m['affiliation']): ?>
                                <div class="affil"><i class="fas fa-building"></i> <?php echo htmlspecialchars($m['affiliation']); ?></div>
                            <?php endif; ?>
                            <div class="meta">
                                <span><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($m['year_label']); ?></span>
                                <?php if ($m['email']): ?><span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($m['email']); ?></span><?php endif; ?>
                                <?php if ($m['phone']): ?><span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($m['phone']); ?></span><?php endif; ?>
                                <?php if ($m['display_order'] != 0): ?><span><i class="fas fa-sort"></i> Order: <?php echo $m['display_order']; ?></span><?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <span class="badge-year me-2"><?php echo htmlspecialchars($m['year_label']); ?></span>
                            <span class="badge-status <?php echo $m['status'] ? 'active' : 'inactive'; ?>">
                                <?php echo $m['status'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                        <div class="actions">
                            <a href="edit_committee_member.php?id=<?php echo $m['id']; ?>" class="action-btn edit" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?delete=1&id=<?php echo $m['id']; ?>" class="action-btn delete"
                               onclick="return confirm('Delete this committee member?')" title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus" style="color:#6f42c1;"></i> Add Committee Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 text-center mb-3">
                                <label for="add_photo" style="cursor:pointer;">
                                    <div class="photo-preview-wrap" id="photoPreview">
                                        <i class="fas fa-camera"></i>
                                    </div>
                                </label>
                                <input type="file" id="add_photo" name="photo" accept="image/*" style="display:none;" onchange="previewPhoto(this)">
                                <div style="font-size:0.78rem; color:#6c757d;">Click to upload photo (optional)</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Committee Year <span class="text-danger">*</span></label>
                                <select class="form-select" name="year_id" required>
                                    <option value="">Select Year</option>
                                    <?php foreach ($years as $y): ?>
                                        <option value="<?php echo $y['id']; ?>">
                                            <?php echo htmlspecialchars($y['year_label']); ?>
                                            <?php echo $y['is_current'] ? ' (Current)' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Designation <span class="text-danger">*</span></label>
                                <select class="form-select" name="designation_id" required>
                                    <option value="">Select Designation</option>
                                    <?php foreach ($designations as $d): ?>
                                        <option value="<?php echo $d['id']; ?>">
                                            <?php echo htmlspecialchars($d['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Affiliation / Institution</label>
                                <input type="text" class="form-control" name="affiliation">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Display Order</label>
                                <input type="number" class="form-control" name="display_order" value="0">
                                <small style="color:#6c757d; font-size:0.72rem;">Lower number appears first</small>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Bio / Short Description</label>
                                <textarea class="form-control" name="bio" rows="3"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="add_status" name="status" checked>
                                    <label class="form-check-label" for="add_status">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_member" class="btn btn-primary">
                            <i class="fas fa-save"></i> Add Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewPhoto(input) {
            const preview = document.getElementById('photoPreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.innerHTML = '<img src="' + e.target.result + '">';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>