<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireAdmin();

$db = Database::getInstance()->getConnection();
$uploadDir = __DIR__ . '/../uploads/committee/';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) { header("Location: committee_members.php"); exit; }

$stmt = $db->prepare("SELECT * FROM committee_members WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$member) { header("Location: committee_members.php"); exit; }

$success = $error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year_id = intval($_POST['year_id']);
    $designation_id = intval($_POST['designation_id']);
    $name = sanitize($_POST['name'] ?? '');
    $affiliation = sanitize($_POST['affiliation'] ?? '');
    $bio = sanitize($_POST['bio'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $status = isset($_POST['status']) ? 1 : 0;

    $photo = $member['photo'];

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        if ($photo && file_exists($uploadDir . $photo)) @unlink($uploadDir . $photo);
        $up = uploadFile($_FILES['photo'], $uploadDir, ['jpg','jpeg','png','gif','webp']);
        if ($up['success']) $photo = $up['filename'];
    }

    if (isset($_POST['remove_photo']) && $_POST['remove_photo'] == '1') {
        if ($photo && file_exists($uploadDir . $photo)) @unlink($uploadDir . $photo);
        $photo = null;
    }

    if ($name && $year_id && $designation_id) {
        $stmt = $db->prepare("UPDATE committee_members SET year_id=?, designation_id=?, name=?, photo=?, affiliation=?, bio=?, email=?, phone=?, display_order=?, status=? WHERE id=?");
        $stmt->bind_param("iissssssiii", $year_id, $designation_id, $name, $photo, $affiliation, $bio, $email, $phone, $display_order, $status, $id);
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], 'Updated Committee Member', "ID: $id");
            $success = "Member updated successfully.";
            $stmt2 = $db->prepare("SELECT * FROM committee_members WHERE id = ?");
            $stmt2->bind_param("i", $id);
            $stmt2->execute();
            $member = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
        } else {
            $error = "Failed: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Please fill all required fields.";
    }
}

$years = $db->query("SELECT * FROM committee_years WHERE status = 1 ORDER BY year_label DESC")->fetch_all(MYSQLI_ASSOC);
$designations = $db->query("SELECT * FROM designations WHERE status = 1 ORDER BY sort_order ASC, name ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Committee Member - BJDVL</title>
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
        .page-header .btn { font-weight: 600; padding: 8px 22px; border-radius: 6px; font-size: 0.9rem; }

        .form-card { background: #f8f9fa; border: 1px solid #eef1f5; border-radius: 8px; padding: 22px; }
        .form-label { font-weight: 600; font-size: 0.9rem; }
        .form-control, .form-select { border-radius: 6px; border: 1.5px solid #eef1f5; padding: 8px 14px; }
        .form-control:focus, .form-select:focus { border-color: #6f42c1; box-shadow: 0 0 0 3px rgba(111,66,193,0.08); }

        .photo-preview-wrap {
            width: 120px; height: 120px; border-radius: 50%; overflow: hidden;
            border: 3px solid #eef1f5; display: flex; align-items: center; justify-content: center;
            color: #adb5bd; font-size: 2rem; margin: 0 auto 10px; background: #fff;
        }
        .photo-preview-wrap img { width: 100%; height: 100%; object-fit: cover; }

        @media (max-width: 991.98px) {
            .main-content { margin-left: 0 !important; max-width: 100% !important; padding: 14px 16px 18px; }
        }
        @media (max-width: 575.98px) {
            .main-content { padding: 10px 8px 14px; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 10px; }
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
                    <h1><i class="fas fa-user-edit"></i> Edit Committee Member</h1>
                    <a href="committee_members.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
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

                <div class="form-card">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-12 text-center mb-3">
                                <label for="photo_input" style="cursor:pointer;">
                                    <div class="photo-preview-wrap" id="photoPreview">
                                        <?php if (!empty($member['photo']) && file_exists($uploadDir . $member['photo'])): ?>
                                            <img src="/uploads/committee/<?php echo htmlspecialchars($member['photo']); ?>" id="currentPhoto">
                                        <?php else: ?>
                                            <i class="fas fa-camera"></i>
                                        <?php endif; ?>
                                    </div>
                                </label>
                                <input type="file" id="photo_input" name="photo" accept="image/*" style="display:none;" onchange="previewPhoto(this)">
                                <div style="font-size:0.82rem; color:#6c757d;">Click photo to change</div>
                                <?php if (!empty($member['photo'])): ?>
                                    <div class="form-check justify-content-center mt-2">
                                        <input type="checkbox" class="form-check-input" id="remove_photo" name="remove_photo" value="1">
                                        <label class="form-check-label" for="remove_photo" style="font-size:0.82rem;">Remove current photo</label>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($member['name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Committee Year <span class="text-danger">*</span></label>
                                <select class="form-select" name="year_id" required>
                                    <?php foreach ($years as $y): ?>
                                        <option value="<?php echo $y['id']; ?>" <?php echo $member['year_id'] == $y['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($y['year_label']); ?>
                                            <?php echo $y['is_current'] ? ' (Current)' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Designation <span class="text-danger">*</span></label>
                                <select class="form-select" name="designation_id" required>
                                    <?php foreach ($designations as $d): ?>
                                        <option value="<?php echo $d['id']; ?>" <?php echo $member['designation_id'] == $d['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($d['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Affiliation / Institution</label>
                                <input type="text" class="form-control" name="affiliation" value="<?php echo htmlspecialchars($member['affiliation'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($member['email'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($member['phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Display Order</label>
                                <input type="number" class="form-control" name="display_order" value="<?php echo $member['display_order']; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-check" style="padding-top:6px;">
                                    <input type="checkbox" class="form-check-input" id="status" name="status" <?php echo $member['status'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="status">Active</label>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Bio / Short Description</label>
                                <textarea class="form-control" name="bio" rows="3"><?php echo htmlspecialchars($member['bio'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="committee_members.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </main>
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