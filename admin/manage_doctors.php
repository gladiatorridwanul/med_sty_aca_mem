<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireAdmin();

$db = Database::getInstance()->getConnection();
$message = '';
$messageType = '';

// Handle Add Doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_doctor'])) {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $bmdc_reg_no = sanitize($_POST['bmdc_reg_no'] ?? '');
    $specialty = sanitize($_POST['specialty'] ?? '');
    $hospital_institute = sanitize($_POST['hospital_institute'] ?? '');
    $mobile = sanitize($_POST['mobile'] ?? '');
    $is_verified = isset($_POST['is_verified']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($name) || empty($email) || empty($password)) {
        $message = 'Name, Email and Password are required';
        $messageType = 'danger';
    } elseif (!validateEmail($email)) {
        $message = 'Invalid email address';
        $messageType = 'danger';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters';
        $messageType = 'danger';
    } else {
        // Check if email exists
        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();
        if ($result->num_rows > 0) {
            $message = 'Email already registered';
            $messageType = 'danger';
        } else {
            $hashedPassword = hashPassword($password);
            $stmt = $db->prepare("INSERT INTO users (user_type, name, email, password, bmdc_reg_no, specialty, hospital_institute, mobile, is_verified, is_active) VALUES ('doctor', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssii", $name, $email, $hashedPassword, $bmdc_reg_no, $specialty, $hospital_institute, $mobile, $is_verified, $is_active);
            if ($stmt->execute()) {
                logActivity($_SESSION['user_id'], 'Added Doctor', "Added doctor: $name ($email)");
                $message = 'Doctor added successfully';
                $messageType = 'success';
            } else {
                $message = 'Failed to add doctor: ' . $stmt->error;
                $messageType = 'danger';
            }
            $stmt->close();
        }
        $check->close();
    }
}

// Handle Edit Doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_doctor'])) {
    $doctor_id = intval($_POST['doctor_id']);
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $bmdc_reg_no = sanitize($_POST['bmdc_reg_no'] ?? '');
    $specialty = sanitize($_POST['specialty'] ?? '');
    $hospital_institute = sanitize($_POST['hospital_institute'] ?? '');
    $mobile = sanitize($_POST['mobile'] ?? '');
    $is_verified = isset($_POST['is_verified']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($name) || empty($email)) {
        $message = 'Name and Email are required';
        $messageType = 'danger';
    } elseif (!validateEmail($email)) {
        $message = 'Invalid email address';
        $messageType = 'danger';
    } else {
        // Check if email exists for other users
        $check = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->bind_param("si", $email, $doctor_id);
        $check->execute();
        $result = $check->get_result();
        if ($result->num_rows > 0) {
            $message = 'Email already in use by another account';
            $messageType = 'danger';
        } else {
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, bmdc_reg_no = ?, specialty = ?, hospital_institute = ?, mobile = ?, is_verified = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssssssiii", $name, $email, $bmdc_reg_no, $specialty, $hospital_institute, $mobile, $is_verified, $is_active, $doctor_id);
            if ($stmt->execute()) {
                logActivity($_SESSION['user_id'], 'Updated Doctor', "Updated doctor ID: $doctor_id");
                $message = 'Doctor updated successfully';
                $messageType = 'success';
            } else {
                $message = 'Failed to update doctor: ' . $stmt->error;
                $messageType = 'danger';
            }
            $stmt->close();
        }
        $check->close();
    }
}

// Handle Update Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $doctor_id = intval($_POST['doctor_id']);
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (strlen($new_password) < 8) {
        $message = 'Password must be at least 8 characters';
        $messageType = 'danger';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Passwords do not match';
        $messageType = 'danger';
    } else {
        $hashedPassword = hashPassword($new_password);
        $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $doctor_id);
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], 'Updated Doctor Password', "Updated password for doctor ID: $doctor_id");
            $message = 'Password updated successfully';
            $messageType = 'success';
        } else {
            $message = 'Failed to update password: ' . $stmt->error;
            $messageType = 'danger';
        }
        $stmt->close();
    }
}

// Handle Verification
if (isset($_POST['verify_doctor']) && isset($_POST['user_id'])) {
    $userId = intval($_POST['user_id']);
    $status = intval($_POST['status'] ?? 1);
    
    $stmt = $db->prepare("UPDATE users SET is_verified = ?, updated_at = NOW() WHERE id = ? AND user_type = 'doctor'");
    $stmt->bind_param("ii", $status, $userId);
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], 'Doctor Verification', "Verified doctor ID: $userId, Status: $status");
        $message = "Doctor verification updated successfully.";
        $messageType = 'success';
    } else {
        $message = "Failed to update verification status.";
        $messageType = 'danger';
    }
    $stmt->close();
}

// Handle Delete
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $doctor_id = intval($_GET['id']);
    $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND user_type = 'doctor'");
    $stmt->bind_param("i", $doctor_id);
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], 'Deleted Doctor', "Deleted doctor ID: $doctor_id");
        $message = 'Doctor deleted successfully';
        $messageType = 'success';
    } else {
        $message = 'Failed to delete doctor: ' . $stmt->error;
        $messageType = 'danger';
    }
    $stmt->close();
}

// Get search and filter parameters
$searchQuery = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$verificationFilter = isset($_GET['verification']) ? sanitize($_GET['verification']) : '';
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$specialtyFilter = isset($_GET['specialty']) ? sanitize($_GET['specialty']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build the WHERE clause for filtering
$whereConditions = ["user_type = 'doctor'"];
$params = [];
$types = "";

if (!empty($searchQuery)) {
    $searchTerm = '%' . $searchQuery . '%';
    $whereConditions[] = "(name LIKE ? OR email LIKE ? OR bmdc_reg_no LIKE ? OR mobile LIKE ? OR specialty LIKE ? OR hospital_institute LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ssssss";
}

if ($verificationFilter !== '') {
    $whereConditions[] = "is_verified = ?";
    $params[] = $verificationFilter;
    $types .= "i";
}

if ($statusFilter !== '') {
    $whereConditions[] = "is_active = ?";
    $params[] = $statusFilter;
    $types .= "i";
}

if (!empty($specialtyFilter)) {
    $whereConditions[] = "specialty = ?";
    $params[] = $specialtyFilter;
    $types .= "s";
}

$whereClause = "WHERE " . implode(" AND ", $whereConditions);

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM users $whereClause";
$countStmt = $db->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalResult = $countStmt->get_result();
$totalDoctors = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalDoctors / $perPage);
$countStmt->close();

// Get doctors with pagination
$sql = "SELECT * FROM users $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$doctors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get all specialties for filter
$allSpecialties = $db->query("SELECT DISTINCT specialty FROM users WHERE user_type = 'doctor' AND specialty IS NOT NULL AND specialty != '' ORDER BY specialty")->fetch_all(MYSQLI_ASSOC);
$specialties = getAllSpecialties();

// Get status counts for filter badges
$statusCounts = [
    'total' => $totalDoctors,
    'verified' => $db->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'doctor' AND is_verified = 1")->fetch_assoc()['count'],
    'pending' => $db->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'doctor' AND is_verified = 0")->fetch_assoc()['count'],
    'active' => $db->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'doctor' AND is_active = 1")->fetch_assoc()['count'],
    'inactive' => $db->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'doctor' AND is_active = 0")->fetch_assoc()['count'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Doctors - BJDVL</title>
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
            font-size: 16px;
        }
        
        /* ============================================
           MAIN CONTENT AREA
           ============================================ */
        .main-content {
            margin-left: 250px;
            padding: 20px 24px 24px;
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
            padding: 0 0 14px 0;
            border-bottom: 1px solid #eef1f5;
            margin-bottom: 18px;
        }
        .page-header h1 {
            font-weight: 700;
            font-size: 1.5rem;
            color: #000000;
            margin: 0;
            font-family: 'Cambria', Georgia, serif;
        }
        .page-header h1 i {
            color: #0d6efd;
            margin-right: 10px;
        }
        .page-header .btn-add {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            padding: 8px 22px;
            border-radius: 6px;
            font-size: 0.95rem;
        }
        
        /* ============================================
           FILTER SECTION
           ============================================ */
        .filter-section {
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #eef1f5;
            padding: 14px 18px;
            margin-bottom: 16px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
        }
        .filter-section .filter-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #000000;
            font-family: 'Cambria', Georgia, serif;
            margin-right: 4px;
        }
        .filter-section .filter-group {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            flex: 1;
        }
        .filter-section .filter-group .form-control,
        .filter-section .filter-group .form-select {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.9rem;
            padding: 6px 12px;
            border-radius: 6px;
            border: 1.5px solid #eef1f5;
            background: #ffffff;
            color: #000000;
            min-width: 150px;
        }
        .filter-section .filter-group .form-control:focus,
        .filter-section .filter-group .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13,110,253,0.08);
            outline: none;
        }
        .filter-section .filter-group .btn-filter {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 6px 18px;
            border-radius: 6px;
        }
        .filter-section .filter-group .btn-clear {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.85rem;
            padding: 6px 14px;
            border-radius: 6px;
            color: #6c757d;
            text-decoration: none;
        }
        .filter-section .filter-group .btn-clear:hover {
            color: #dc3545;
        }
        
        .filter-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 6px;
        }
        .filter-badges .badge {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.75rem;
            padding: 4px 12px;
            border-radius: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1.5px solid transparent;
        }
        .filter-badges .badge:hover {
            transform: translateY(-1px);
        }
        .filter-badges .badge-all {
            background: #e8f0fe;
            color: #0d6efd;
            border-color: #0d6efd;
        }
        .filter-badges .badge-all.active {
            background: #0d6efd;
            color: #fff;
        }
        .filter-badges .badge-verified {
            background: #e8f5e9;
            color: #198754;
            border-color: #198754;
        }
        .filter-badges .badge-verified.active {
            background: #198754;
            color: #fff;
        }
        .filter-badges .badge-pending {
            background: #fff3e0;
            color: #f39c12;
            border-color: #f39c12;
        }
        .filter-badges .badge-pending.active {
            background: #f39c12;
            color: #fff;
        }
        .filter-badges .badge-active {
            background: #e8f5e9;
            color: #198754;
            border-color: #198754;
        }
        .filter-badges .badge-active.active {
            background: #198754;
            color: #fff;
        }
        .filter-badges .badge-inactive {
            background: #fce4ec;
            color: #dc3545;
            border-color: #dc3545;
        }
        .filter-badges .badge-inactive.active {
            background: #dc3545;
            color: #fff;
        }
        
        /* ============================================
           TABLE CARD
           ============================================ */
        .table-card {
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #eef1f5;
            overflow: hidden;
        }
        
        .table-card .table-body {
            padding: 0;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .table-card .table-body table {
            margin: 0;
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.9rem;
            width: 100%;
            min-width: 900px;
            background: #ffffff;
        }
        .table-card .table-body table thead th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            border-bottom: 2px solid #eef1f5;
            padding: 10px 14px;
            white-space: nowrap;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .table-card .table-body table tbody td {
            padding: 10px 14px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f4f8;
            color: #000000;
            font-size: 0.9rem;
        }
        .table-card .table-body table tbody tr:hover {
            background: #f8f9fa;
        }
        .table-card .table-body table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* ============================================
           DOCTOR AVATAR
           ============================================ */
        .doctor-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        /* ============================================
           BADGES
           ============================================ */
        .status-badge {
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 0.68rem;
            font-weight: 600;
            display: inline-block;
            font-family: 'Cambria', Georgia, serif;
        }
        .status-badge.verified { background: #e8f5e9; color: #198754; }
        .status-badge.pending { background: #fff3e0; color: #f39c12; }
        .status-badge.active { background: #e8f5e9; color: #198754; }
        .status-badge.inactive { background: #fce4ec; color: #dc3545; }
        
        /* ============================================
           ACTION BUTTONS
           ============================================ */
        .action-btn {
            width: 30px;
            height: 30px;
            border-radius: 4px;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
        }
        .action-btn:hover {
            transform: translateY(-1px);
        }
        .action-btn.edit { background: #e8f0fe; color: #0d6efd; }
        .action-btn.edit:hover { background: #0d6efd; color: #fff; }
        .action-btn.key { background: #fff3e0; color: #f39c12; }
        .action-btn.key:hover { background: #f39c12; color: #fff; }
        .action-btn.history { background: #e0f7fa; color: #0dcaf0; }
        .action-btn.history:hover { background: #0dcaf0; color: #fff; }
        .action-btn.verify { background: #e8f5e9; color: #198754; }
        .action-btn.verify:hover { background: #198754; color: #fff; }
        .action-btn.revoke { background: #fce4ec; color: #dc3545; }
        .action-btn.revoke:hover { background: #dc3545; color: #fff; }
        .action-btn.delete { background: #fce4ec; color: #dc3545; }
        .action-btn.delete:hover { background: #dc3545; color: #fff; }
        
        .action-group {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        /* ============================================
           PAGINATION
           ============================================ */
        .pagination-wrapper {
            padding: 14px 18px;
            background: #f8f9fa;
            border-top: 1px solid #eef1f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .pagination-wrapper .info-text {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.85rem;
            color: #6c757d;
        }
        .pagination-wrapper .info-text strong {
            color: #000000;
        }
        .pagination-wrapper .pagination {
            margin: 0;
            gap: 3px;
        }
        .pagination-wrapper .pagination .page-link {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.85rem;
            padding: 6px 14px;
            border-radius: 4px;
            border: 1px solid #eef1f5;
            color: #000000;
            background: #ffffff;
            transition: all 0.2s ease;
        }
        .pagination-wrapper .pagination .page-link:hover {
            background: #f0f4f8;
            border-color: #0d6efd;
            color: #0d6efd;
        }
        .pagination-wrapper .pagination .page-item.active .page-link {
            background: #0d6efd;
            border-color: #0d6efd;
            color: #ffffff;
        }
        .pagination-wrapper .pagination .page-item.disabled .page-link {
            color: #adb5bd;
            pointer-events: none;
            background: #f8f9fa;
        }
        
        /* ============================================
           MODALS
           ============================================ */
        .modal-content {
            border-radius: 10px;
            border: 1px solid #eef1f5;
            background: #ffffff;
        }
        .modal-header {
            border-bottom: 1px solid #eef1f5;
            padding: 16px 22px;
        }
        .modal-header .modal-title {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 700;
            font-size: 1.2rem;
            color: #000000;
        }
        .modal-body {
            padding: 22px;
        }
        .modal-footer {
            border-top: 1px solid #eef1f5;
            padding: 14px 22px;
        }
        .modal-footer .btn {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 8px 22px;
            border-radius: 6px;
        }
        
        .form-label {
            font-family: 'Cambria', Georgia, serif;
            font-weight: 600;
            color: #000000;
            font-size: 0.9rem;
        }
        .form-control, .form-select {
            border-radius: 6px;
            border: 1.5px solid #eef1f5;
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.95rem;
            color: #000000;
            background: #ffffff;
            padding: 8px 14px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13,110,253,0.08);
        }
        .form-check-label {
            font-family: 'Cambria', Georgia, serif;
            color: #000000;
            font-size: 0.9rem;
        }
        
        /* ============================================
           ACTIVITY LOG
           ============================================ */
        .activity-log {
            max-height: 400px;
            overflow-y: auto;
        }
        .activity-log .list-group-item {
            border-left: 3px solid #0d6efd;
            margin-bottom: 4px;
            border-radius: 4px;
            background: #f8f9fa;
            border-color: #eef1f5;
            padding: 10px 14px;
        }
        .activity-log .list-group-item .time {
            font-size: 0.75rem;
            color: #6c757d;
        }
        .activity-log .list-group-item .activity-action {
            font-weight: 600;
            color: #000000;
        }
        
        /* ============================================
           RESPONSIVE
           ============================================ */
        
        /* Tablet - Hide Sidebar */
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0 !important;
                max-width: 100% !important;
                padding: 14px 16px 18px;
            }
            .page-header h1 {
                font-size: 1.3rem;
            }
            .page-header .btn-add {
                font-size: 0.85rem;
                padding: 6px 16px;
            }
            .table-card .table-body table {
                min-width: 700px;
                font-size: 0.85rem;
            }
            .filter-section {
                padding: 12px 14px;
                flex-direction: column;
                align-items: stretch;
            }
            .filter-section .filter-group {
                flex-wrap: wrap;
            }
            .filter-section .filter-group .form-control,
            .filter-section .filter-group .form-select {
                min-width: 120px;
                flex: 1;
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
                gap: 10px;
                padding-bottom: 10px;
                margin-bottom: 12px;
            }
            .page-header h1 {
                font-size: 1.15rem;
            }
            .page-header h1 i {
                font-size: 1rem;
            }
            .page-header .btn-add {
                font-size: 0.8rem;
                padding: 6px 14px;
                width: 100%;
            }
            .table-card .table-body table {
                min-width: 550px;
                font-size: 0.8rem;
            }
            .table-card .table-body table thead th {
                padding: 6px 10px;
                font-size: 0.68rem;
            }
            .table-card .table-body table tbody td {
                padding: 6px 10px;
                font-size: 0.8rem;
            }
            .doctor-avatar {
                width: 32px;
                height: 32px;
                font-size: 0.75rem;
            }
            .status-badge {
                font-size: 0.58rem;
                padding: 2px 8px;
            }
            .action-btn {
                width: 26px;
                height: 26px;
                font-size: 0.7rem;
            }
            .filter-section {
                padding: 10px 12px;
                flex-direction: column;
                align-items: stretch;
            }
            .filter-section .filter-label {
                font-size: 0.8rem;
            }
            .filter-section .filter-group {
                flex-direction: column;
                gap: 6px;
            }
            .filter-section .filter-group .form-control,
            .filter-section .filter-group .form-select {
                min-width: 100%;
                font-size: 0.85rem;
                padding: 5px 10px;
            }
            .filter-section .filter-group .btn-filter {
                font-size: 0.8rem;
                padding: 5px 14px;
                width: 100%;
            }
            .filter-section .filter-group .btn-clear {
                font-size: 0.8rem;
                padding: 5px 12px;
                text-align: center;
                width: 100%;
            }
            .filter-badges .badge {
                font-size: 0.65rem;
                padding: 3px 10px;
            }
            .pagination-wrapper {
                flex-direction: column;
                align-items: center;
                padding: 12px 14px;
            }
            .pagination-wrapper .info-text {
                font-size: 0.8rem;
                text-align: center;
            }
            .pagination-wrapper .pagination .page-link {
                font-size: 0.78rem;
                padding: 4px 10px;
            }
            .modal-body {
                padding: 14px;
            }
            .form-control, .form-select {
                font-size: 0.9rem;
                padding: 6px 10px;
            }
            .modal-header .modal-title {
                font-size: 1.05rem;
            }
            .modal-footer .btn {
                font-size: 0.82rem;
                padding: 6px 14px;
            }
        }
        
        /* Extra Small */
        @media (max-width: 400px) {
            .main-content {
                padding: 8px 4px 10px;
            }
            .table-card .table-body table {
                min-width: 480px;
                font-size: 0.75rem;
            }
            .table-card .table-body table thead th {
                padding: 4px 8px;
                font-size: 0.62rem;
            }
            .table-card .table-body table tbody td {
                padding: 4px 8px;
                font-size: 0.75rem;
            }
            .doctor-avatar {
                width: 28px;
                height: 28px;
                font-size: 0.65rem;
            }
            .action-btn {
                width: 22px;
                height: 22px;
                font-size: 0.6rem;
            }
            .action-group {
                gap: 3px;
            }
            .page-header h1 {
                font-size: 1rem;
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
            .filter-section {
                background: #f8f9fa !important;
                border-color: #eef1f5 !important;
            }
            .filter-section .filter-label {
                color: #000000 !important;
            }
            .filter-section .filter-group .form-control,
            .filter-section .filter-group .form-select {
                background: #ffffff !important;
                border-color: #eef1f5 !important;
                color: #000000 !important;
            }
            .table-card {
                background: #f8f9fa !important;
                border-color: #eef1f5 !important;
            }
            .table-card .table-body table {
                background: #ffffff !important;
            }
            .table-card .table-body table thead th {
                background: #f8f9fa !important;
                color: #495057 !important;
                border-bottom-color: #eef1f5 !important;
            }
            .table-card .table-body table tbody td {
                color: #000000 !important;
                border-bottom-color: #f0f4f8 !important;
            }
            .table-card .table-body table tbody tr:hover {
                background: #f8f9fa !important;
            }
            .page-header {
                border-bottom-color: #eef1f5 !important;
            }
            .page-header h1 {
                color: #000000 !important;
            }
            .pagination-wrapper {
                background: #f8f9fa !important;
                border-top-color: #eef1f5 !important;
            }
            .pagination-wrapper .info-text {
                color: #6c757d !important;
            }
            .pagination-wrapper .info-text strong {
                color: #000000 !important;
            }
            .pagination-wrapper .pagination .page-link {
                background: #ffffff !important;
                border-color: #eef1f5 !important;
                color: #000000 !important;
            }
            .pagination-wrapper .pagination .page-link:hover {
                background: #f0f4f8 !important;
            }
            .pagination-wrapper .pagination .page-item.active .page-link {
                background: #0d6efd !important;
                border-color: #0d6efd !important;
                color: #ffffff !important;
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
            .form-control, .form-select {
                background: #ffffff !important;
                border-color: #eef1f5 !important;
                color: #000000 !important;
            }
            .form-label {
                color: #000000 !important;
            }
            .form-check-label {
                color: #000000 !important;
            }
            .activity-log .list-group-item {
                background: #f8f9fa !important;
                border-color: #eef1f5 !important;
            }
            .activity-log .list-group-item .activity-action {
                color: #000000 !important;
            }
            .status-badge.verified { background: #e8f5e9 !important; color: #198754 !important; }
            .status-badge.pending { background: #fff3e0 !important; color: #f39c12 !important; }
            .status-badge.active { background: #e8f5e9 !important; color: #198754 !important; }
            .status-badge.inactive { background: #fce4ec !important; color: #dc3545 !important; }
            .filter-badges .badge-all { background: #e8f0fe !important; color: #0d6efd !important; border-color: #0d6efd !important; }
            .filter-badges .badge-all.active { background: #0d6efd !important; color: #fff !important; }
            .filter-badges .badge-verified { background: #e8f5e9 !important; color: #198754 !important; border-color: #198754 !important; }
            .filter-badges .badge-verified.active { background: #198754 !important; color: #fff !important; }
            .filter-badges .badge-pending { background: #fff3e0 !important; color: #f39c12 !important; border-color: #f39c12 !important; }
            .filter-badges .badge-pending.active { background: #f39c12 !important; color: #fff !important; }
            .filter-badges .badge-active { background: #e8f5e9 !important; color: #198754 !important; border-color: #198754 !important; }
            .filter-badges .badge-active.active { background: #198754 !important; color: #fff !important; }
            .filter-badges .badge-inactive { background: #fce4ec !important; color: #dc3545 !important; border-color: #dc3545 !important; }
            .filter-badges .badge-inactive.active { background: #dc3545 !important; color: #fff !important; }
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
                        <i class="fas fa-users"></i> Manage Doctors
                        <span style="font-size: 0.8rem; font-weight: 400; color: #6c757d; margin-left: 8px;">
                            (<?php echo $totalDoctors; ?> total)
                        </span>
                    </h1>
                    <button class="btn btn-primary btn-add" data-bs-toggle="modal" data-bs-target="#addDoctorModal">
                        <i class="fas fa-plus"></i> Add New Doctor
                    </button>
                </div>
                
                <!-- Alert Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" style="border-radius: 6px; font-family: 'Cambria', Georgia, serif; font-size: 0.9rem;">
                        <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Filter & Search Section -->
                <div class="filter-section">
                    <span class="filter-label"><i class="fas fa-filter"></i> Filter:</span>
                    <div class="filter-group">
                        <form method="GET" action="" class="d-flex flex-wrap gap-2" style="flex: 1;">
                            <input type="text" class="form-control" name="search" placeholder="Search by name, email, BMDC, mobile..." value="<?php echo htmlspecialchars($searchQuery); ?>" style="flex: 2; min-width: 180px;">
                            <select class="form-select" name="verification" style="flex: 1; min-width: 130px;">
                                <option value="">All Verification</option>
                                <option value="1" <?php echo $verificationFilter === '1' ? 'selected' : ''; ?>>Verified</option>
                                <option value="0" <?php echo $verificationFilter === '0' ? 'selected' : ''; ?>>Pending</option>
                            </select>
                            <select class="form-select" name="status" style="flex: 1; min-width: 120px;">
                                <option value="">All Status</option>
                                <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                            <select class="form-select" name="specialty" style="flex: 1; min-width: 150px;">
                                <option value="">All Specialties</option>
                                <?php foreach ($allSpecialties as $spec): ?>
                                <option value="<?php echo htmlspecialchars($spec['specialty']); ?>" <?php echo $specialtyFilter === $spec['specialty'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($spec['specialty']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary btn-filter">
                                <i class="fas fa-search"></i> Apply
                            </button>
                            <?php if (!empty($searchQuery) || $verificationFilter !== '' || $statusFilter !== '' || !empty($specialtyFilter)): ?>
                            <a href="manage_doctors.php" class="btn-clear">
                                <i class="fas fa-times"></i> Clear
                            </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <!-- Status Filter Badges -->
                <div class="filter-badges">
                    <a href="manage_doctors.php" class="badge badge-all <?php echo (empty($searchQuery) && $verificationFilter === '' && $statusFilter === '' && empty($specialtyFilter)) ? 'active' : ''; ?>">
                        All (<?php echo $statusCounts['total']; ?>)
                    </a>
                    <a href="?verification=1<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $statusFilter !== '' ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo !empty($specialtyFilter) ? '&specialty=' . urlencode($specialtyFilter) : ''; ?>" 
                       class="badge badge-verified <?php echo $verificationFilter === '1' ? 'active' : ''; ?>">
                        Verified (<?php echo $statusCounts['verified']; ?>)
                    </a>
                    <a href="?verification=0<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $statusFilter !== '' ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo !empty($specialtyFilter) ? '&specialty=' . urlencode($specialtyFilter) : ''; ?>" 
                       class="badge badge-pending <?php echo $verificationFilter === '0' ? 'active' : ''; ?>">
                        Pending (<?php echo $statusCounts['pending']; ?>)
                    </a>
                    <a href="?status=1<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $verificationFilter !== '' ? '&verification=' . urlencode($verificationFilter) : ''; ?><?php echo !empty($specialtyFilter) ? '&specialty=' . urlencode($specialtyFilter) : ''; ?>" 
                       class="badge badge-active <?php echo $statusFilter === '1' ? 'active' : ''; ?>">
                        Active (<?php echo $statusCounts['active']; ?>)
                    </a>
                    <a href="?status=0<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $verificationFilter !== '' ? '&verification=' . urlencode($verificationFilter) : ''; ?><?php echo !empty($specialtyFilter) ? '&specialty=' . urlencode($specialtyFilter) : ''; ?>" 
                       class="badge badge-inactive <?php echo $statusFilter === '0' ? 'active' : ''; ?>">
                        Inactive (<?php echo $statusCounts['inactive']; ?>)
                    </a>
                </div>
                
                <!-- Table Card -->
                <div class="table-card">
                    <div class="table-body">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 50px;">ID</th>
                                    <th style="width: 55px;">Avatar</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Specialty</th>
                                    <th>BMDC Reg.</th>
                                    <th style="width: 110px;">Status</th>
                                    <th style="width: 200px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($doctors)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4" style="color: #6c757d; font-family: 'Cambria', Georgia, serif; font-size: 0.95rem;">
                                        <i class="fas fa-inbox fa-3x d-block mb-3" style="color: #dee2e6;"></i>
                                        <?php if (!empty($searchQuery) || $verificationFilter !== '' || $statusFilter !== '' || !empty($specialtyFilter)): ?>
                                            No doctors match your filter criteria.
                                        <?php else: ?>
                                            No doctors found. Click "Add New Doctor" to get started.
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($doctors as $doctor): ?>
                                <tr>
                                    <td><strong><?php echo $doctor['id']; ?></strong></td>
                                    <td>
                                        <div class="doctor-avatar">
                                            <?php echo strtoupper(substr($doctor['name'], 0, 2)); ?>
                                        </div>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($doctor['name']); ?></strong></td>
                                    <td><small><?php echo htmlspecialchars($doctor['email']); ?></small></td>
                                    <td><small><?php echo htmlspecialchars($doctor['specialty'] ?? 'N/A'); ?></small></td>
                                    <td><small><?php echo htmlspecialchars($doctor['bmdc_reg_no'] ?? 'N/A'); ?></small></td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 3px;">
                                            <span class="status-badge <?php echo $doctor['is_verified'] ? 'verified' : 'pending'; ?>">
                                                <?php echo $doctor['is_verified'] ? 'Verified' : 'Pending'; ?>
                                            </span>
                                            <span class="status-badge <?php echo $doctor['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $doctor['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <button class="action-btn edit" onclick="editDoctor(<?php echo $doctor['id']; ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn key" onclick="changePassword(<?php echo $doctor['id']; ?>)" title="Change Password">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <button class="action-btn history" onclick="viewActivities(<?php echo $doctor['id']; ?>)" title="View Activities">
                                                <i class="fas fa-history"></i>
                                            </button>
                                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to change verification status?')">
                                                <input type="hidden" name="user_id" value="<?php echo $doctor['id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo $doctor['is_verified'] ? 0 : 1; ?>">
                                                <button type="submit" name="verify_doctor" class="action-btn <?php echo $doctor['is_verified'] ? 'revoke' : 'verify'; ?>" title="<?php echo $doctor['is_verified'] ? 'Revoke Verification' : 'Verify Doctor'; ?>">
                                                    <i class="fas fa-<?php echo $doctor['is_verified'] ? 'times' : 'check'; ?>"></i>
                                                </button>
                                            </form>
                                            <a href="?delete=1&id=<?php echo $doctor['id']; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $verificationFilter !== '' ? '&verification=' . urlencode($verificationFilter) : ''; ?><?php echo $statusFilter !== '' ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo !empty($specialtyFilter) ? '&specialty=' . urlencode($specialtyFilter) : ''; ?><?php echo $page > 1 ? '&page=' . $page : ''; ?>" 
                                               class="action-btn delete" onclick="return confirm('Are you sure you want to delete this doctor?')" title="Delete">
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
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination-wrapper">
                        <div class="info-text">
                            Showing <strong><?php echo $offset + 1; ?></strong> to 
                            <strong><?php echo min($offset + $perPage, $totalDoctors); ?></strong> 
                            of <strong><?php echo $totalDoctors; ?></strong> doctors
                        </div>
                        <nav>
                            <ul class="pagination">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $verificationFilter !== '' ? '&verification=' . urlencode($verificationFilter) : ''; ?><?php echo $statusFilter !== '' ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo !empty($specialtyFilter) ? '&specialty=' . urlencode($specialtyFilter) : ''; ?>" aria-label="Previous">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $verificationFilter !== '' ? '&verification=' . urlencode($verificationFilter) : ''; ?><?php echo $statusFilter !== '' ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo !empty($specialtyFilter) ? '&specialty=' . urlencode($specialtyFilter) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $verificationFilter !== '' ? '&verification=' . urlencode($verificationFilter) : ''; ?><?php echo $statusFilter !== '' ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo !empty($specialtyFilter) ? '&specialty=' . urlencode($specialtyFilter) : ''; ?>" aria-label="Next">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- ============================================
    ADD DOCTOR MODAL
    ============================================ -->
    <div class="modal fade" id="addDoctorModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus" style="color: #0d6efd;"></i> Add New Doctor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="password" required>
                                <small class="text-muted" style="font-family: 'Cambria', Georgia, serif; font-size: 0.75rem;">Minimum 8 characters</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">BMDC Reg. No.</label>
                                <input type="text" class="form-control" name="bmdc_reg_no">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Specialty</label>
                                <select class="form-select" name="specialty">
                                    <option value="">Select Specialty</option>
                                    <?php foreach ($specialties as $specialty): ?>
                                    <option value="<?php echo htmlspecialchars($specialty['name']); ?>">
                                        <?php echo htmlspecialchars($specialty['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hospital/Institute</label>
                                <input type="text" class="form-control" name="hospital_institute">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mobile</label>
                                <input type="text" class="form-control" name="mobile">
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="add_is_verified" name="is_verified" checked>
                                    <label class="form-check-label" for="add_is_verified">Verified</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="add_is_active" name="is_active" checked>
                                    <label class="form-check-label" for="add_is_active">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_doctor" class="btn btn-primary">
                            <i class="fas fa-save"></i> Add Doctor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================
    EDIT DOCTOR MODAL
    ============================================ -->
    <div class="modal fade" id="editDoctorModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit" style="color: #0d6efd;"></i> Edit Doctor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="doctor_id" id="edit_doctor_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">BMDC Reg. No.</label>
                                <input type="text" class="form-control" id="edit_bmdc_reg_no" name="bmdc_reg_no">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Specialty</label>
                                <select class="form-select" id="edit_specialty" name="specialty">
                                    <option value="">Select Specialty</option>
                                    <?php foreach ($specialties as $specialty): ?>
                                    <option value="<?php echo htmlspecialchars($specialty['name']); ?>">
                                        <?php echo htmlspecialchars($specialty['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hospital/Institute</label>
                                <input type="text" class="form-control" id="edit_hospital_institute" name="hospital_institute">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mobile</label>
                                <input type="text" class="form-control" id="edit_mobile" name="mobile">
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="edit_is_verified" name="is_verified">
                                    <label class="form-check-label" for="edit_is_verified">Verified</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="edit_is_active" name="is_active">
                                    <label class="form-check-label" for="edit_is_active">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_doctor" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Doctor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================
    CHANGE PASSWORD MODAL
    ============================================ -->
    <div class="modal fade" id="passwordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key" style="color: #f39c12;"></i> Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="doctor_id" id="password_doctor_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <small class="text-muted" style="font-family: 'Cambria', Georgia, serif; font-size: 0.75rem;">Minimum 8 characters</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_password" class="btn btn-warning" style="color: #fff;">
                            <i class="fas fa-save"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================
    ACTIVITIES MODAL
    ============================================ -->
    <div class="modal fade" id="activitiesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-history" style="color: #0d6efd;"></i> Doctor Activities</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="activitiesContent" class="activity-log">
                        <div class="text-center text-muted py-4" style="font-family: 'Cambria', Georgia, serif;">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p class="mt-2">Loading activities...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        // Auto-submit filter on change
        document.addEventListener('DOMContentLoaded', function() {
            const filterSelects = document.querySelectorAll('.filter-section select');
            filterSelects.forEach(function(select) {
                select.addEventListener('change', function() {
                    this.closest('form').submit();
                });
            });
        });

        // ============================================
        // EDIT DOCTOR
        // ============================================
        function editDoctor(id) {
            $.ajax({
                url: '/admin/get_doctor.php',
                type: 'GET',
                data: { id: id },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        $('#edit_doctor_id').val(data.doctor.id);
                        $('#edit_name').val(data.doctor.name);
                        $('#edit_email').val(data.doctor.email);
                        $('#edit_bmdc_reg_no').val(data.doctor.bmdc_reg_no || '');
                        $('#edit_specialty').val(data.doctor.specialty || '');
                        $('#edit_hospital_institute').val(data.doctor.hospital_institute || '');
                        $('#edit_mobile').val(data.doctor.mobile || '');
                        $('#edit_is_verified').prop('checked', data.doctor.is_verified == 1);
                        $('#edit_is_active').prop('checked', data.doctor.is_active == 1);
                        $('#editDoctorModal').modal('show');
                    } else {
                        alert('Failed to load doctor data');
                    }
                },
                error: function() {
                    alert('Error loading doctor data');
                }
            });
        }

        // ============================================
        // CHANGE PASSWORD
        // ============================================
        function changePassword(id) {
            $('#password_doctor_id').val(id);
            $('#new_password').val('');
            $('#confirm_password').val('');
            $('#passwordModal').modal('show');
        }

        // ============================================
        // VIEW ACTIVITIES
        // ============================================
        function viewActivities(id) {
            $('#activitiesContent').html(`
                <div class="text-center text-muted py-4" style="font-family: 'Cambria', Georgia, serif;">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Loading activities...</p>
                </div>
            `);
            $('#activitiesModal').modal('show');
            
            $.ajax({
                url: '/admin/get_doctor_activities.php',
                type: 'GET',
                data: { id: id },
                dataType: 'json',
                success: function(data) {
                    if (data.success && data.activities.length > 0) {
                        var html = '<ul class="list-group">';
                        data.activities.forEach(function(activity) {
                            var icon = activity.action.includes('Login') ? 'fa-sign-in-alt' :
                                      activity.action.includes('Logout') ? 'fa-sign-out-alt' :
                                      activity.action.includes('Request') ? 'fa-paper-plane' :
                                      activity.action.includes('Download') ? 'fa-download' :
                                      'fa-clock';
                            html += `
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <i class="fas ${icon}" style="color: #0d6efd;"></i>
                                            <span class="activity-action">${activity.action}</span>
                                            <p class="mb-0 text-muted" style="font-size: 0.78rem; font-family: 'Cambria', Georgia, serif;">${activity.details || ''}</p>
                                        </div>
                                        <span class="time">${activity.created_at}</span>
                                    </div>
                                </li>
                            `;
                        });
                        html += '</ul>';
                        $('#activitiesContent').html(html);
                    } else {
                        $('#activitiesContent').html(`
                            <div class="text-center text-muted py-4" style="font-family: 'Cambria', Georgia, serif;">
                                <i class="fas fa-inbox fa-3x mb-3" style="color: #dee2e6;"></i>
                                <p>No activities found for this doctor.</p>
                            </div>
                        `);
                    }
                },
                error: function() {
                    $('#activitiesContent').html(`
                        <div class="text-center text-danger py-4" style="font-family: 'Cambria', Georgia, serif;">
                            <i class="fas fa-exclamation-circle fa-2x mb-3"></i>
                            <p>Failed to load activities. Please try again.</p>
                        </div>
                    `);
                }
            });
        }
    </script>
</body>
</html>