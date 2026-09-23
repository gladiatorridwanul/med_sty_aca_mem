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

// Get activities from audit_trail
$stmt = $db->prepare("SELECT action, details, created_at FROM audit_trail WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$result = $stmt->get_result();
$activities = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// If no activities in audit_trail, get from activity_logs
if (empty($activities)) {
    $stmt = $db->prepare("SELECT activity as action, details, created_at FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $result = $stmt->get_result();
    $activities = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Format dates
foreach ($activities as &$activity) {
    $activity['created_at'] = date('Y-m-d H:i:s', strtotime($activity['created_at']));
}

echo json_encode(['success' => true, 'activities' => $activities]);
?>