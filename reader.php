<?php
// ============================================
// reader.php - View-only document reader
// Place at: uclp_academy/reader.php  (ROOT level)
// ============================================

error_reporting(E_ALL);
ini_set('display_errors', 0); // don't print errors into PDF stream

// Path to includes (root-level file, so no ../)
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// ============================================
// Get parameters
// ============================================
$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : 'book';
$id   = isset($_GET['id'])   ? intval($_GET['id']) : 0;

if (!$id) {
    http_response_code(400);
    exit('Invalid document ID');
}

if (!in_array($type, ['book', 'journal'])) {
    http_response_code(400);
    exit('Invalid document type');
}

// ============================================
// Get database connection
// ============================================
try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    exit('DB connection failed');
}

// ============================================
// Lookup document — NO status filter
// ============================================
if ($type === 'book') {
    $stmt = $db->prepare("SELECT id, title, file_path FROM books WHERE id = ? LIMIT 1");
    $basePath = defined('BOOK_UPLOAD_PATH') ? BOOK_UPLOAD_PATH : __DIR__ . '/uploads/books/';
} else {
    $stmt = $db->prepare("SELECT id, title, file_path FROM journals WHERE id = ? LIMIT 1");
    $basePath = defined('JOURNAL_UPLOAD_PATH') ? JOURNAL_UPLOAD_PATH : __DIR__ . '/uploads/journals/';
}

if (!$stmt) {
    http_response_code(500);
    exit('Query prepare failed: ' . $db->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

// ============================================
// Document not found in database
// ============================================
if (!$row) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><title>Not Found</title>';
    echo '<style>body{font-family:Cambria,Georgia,serif;background:#525659;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;text-align:center;padding:20px;}';
    echo '.box{max-width:500px;} .icon{font-size:3rem;margin-bottom:16px;opacity:.5;}';
    echo 'h2{font-weight:700;margin-bottom:8px;} p{opacity:.7;font-size:.95rem;line-height:1.5;}';
    echo 'code{background:rgba(255,255,255,.1);padding:2px 8px;border-radius:4px;font-size:.85rem;}</style>';
    echo '</head><body><div class="box">';
    echo '<div class="icon">📄</div>';
    echo '<h2>Document not found in Database</h2>';
    echo '<p>The ' . htmlspecialchars($type) . ' with ID <code>' . $id . '</code> does not exist in the database.</p>';
    echo '<p style="margin-top:12px;font-size:.8rem;opacity:.5;">Check that the record exists and the ID is correct.</p>';
    echo '</div></body></html>';
    exit;
}

// ============================================
// Build file path
// ============================================
$fileName = $row['file_path'];
$filePath = rtrim($basePath, '/') . '/' . ltrim($fileName, '/');

// If file_path already includes "uploads/" prefix, try as-is
if (!file_exists($filePath) && file_exists(__DIR__ . '/' . $fileName)) {
    $filePath = __DIR__ . '/' . $fileName;
}

// ============================================
// File missing on server
// ============================================
if (!file_exists($filePath)) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><title>File Missing</title>';
    echo '<style>body{font-family:Cambria,Georgia,serif;background:#525659;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;text-align:center;padding:20px;}';
    echo '.box{max-width:500px;} .icon{font-size:3rem;margin-bottom:16px;opacity:.5;}';
    echo 'h2{font-weight:700;margin-bottom:8px;} p{opacity:.7;font-size:.95rem;line-height:1.5;}';
    echo 'code{background:rgba(255,255,255,.1);padding:2px 8px;border-radius:4px;font-size:.8rem;word-break:break-all;display:inline-block;margin-top:4px;}</style>';
    echo '</head><body><div class="box">';
    echo '<div class="icon">⚠️</div>';
    echo '<h2>PDF File Missing on Server</h2>';
    echo '<p>The record exists in the database, but the PDF file was not found:</p>';
    echo '<code>' . htmlspecialchars($filePath) . '</code>';
    echo '<p style="margin-top:12px;font-size:.8rem;opacity:.5;">Upload the PDF to the correct folder.</p>';
    echo '</div></body></html>';
    exit;
}

// ============================================
// Stream the PDF — view-only, inline
// ============================================
while (ob_get_level()) { ob_end_clean(); }

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($fileName) . '"');
header('Content-Transfer-Encoding: binary');
header('Accept-Ranges: bytes');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

readfile($filePath);
exit;