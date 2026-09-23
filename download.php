<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

Auth::requireLogin();

$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$type || !$id || !in_array($type, ['book', 'journal'])) {
    die('Invalid request');
}

$userId = $_SESSION['user_id'];
$db = Database::getInstance()->getConnection();

// Get file path
if ($type === 'book') {
    $stmt = $db->prepare("SELECT file_path, title FROM books WHERE id = ? AND status = 'approved'");
} else {
    $stmt = $db->prepare("SELECT file_path, title FROM journals WHERE id = ? AND status = 'approved'");
}
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
$stmt->close();

if (!$item) {
    die('Item not found');
}

// Check download permission
if (!hasDownloadPermission($userId, $id, $type)) {
    die('You do not have permission to download this item. Please request permission first.');
}

// Check remaining downloads
$remaining = getRemainingDownloads($userId, $id, $type);
if ($remaining <= 0) {
    die('You have reached your download limit for this item.');
}

$filePath = ($type === 'book') ? BOOK_UPLOAD_PATH : JOURNAL_UPLOAD_PATH;
$fullPath = $filePath . $item['file_path'];

if (!file_exists($fullPath)) {
    die('File not found');
}

// Increment download count
if ($type === 'book') {
    $db->query("UPDATE books SET download_count = download_count + 1 WHERE id = $id");
} else {
    $db->query("UPDATE journals SET download_count = download_count + 1 WHERE id = $id");
}

// Increment used downloads
incrementUsedDownloads($userId, $id, $type);

// Log download
logActivity($userId, 'Download', "Downloaded $type: " . $item['title']);

// Set headers for download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $item['title'] . '.pdf"');
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Read file
readfile($fullPath);
exit;