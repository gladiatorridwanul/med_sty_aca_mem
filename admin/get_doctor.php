<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireAdmin();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Doctor ID required']);
    exit;
}

$doctorId = intval($_GET['id']);
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT id, name, email, bmdc_reg_no, specialty, hospital_institute, mobile, is_verified, is_active FROM users WHERE id = ? AND user_type = 'doctor'");
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();
$stmt->close();

if ($doctor) {
    echo json_encode(['success' => true, 'doctor' => $doctor]);
} else {
    echo json_encode(['success' => false, 'message' => 'Doctor not found']);
}
?>