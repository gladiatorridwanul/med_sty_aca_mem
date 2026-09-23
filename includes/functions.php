<?php
require_once __DIR__ . '/db_connection.php';

// ============================================
// URL HELPER FUNCTION
// ============================================

// Generate URL with proper base path
function url($path = '') {
    // Remove leading slash from path if exists
    $path = ltrim($path, '/');
    // If BASE_URL is '/', return '/path', else return '/base/path'
    $base = rtrim(BASE_URL, '/');
    if (empty($path)) {
        return $base . '/';
    }
    return $base . '/' . $path;
}

// ============================================
// LOGGING FUNCTIONS
// ============================================

// Log activity function
function logActivity($user_id, $activity, $details = null) {
    $db = Database::getInstance()->getConnection();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Get user type from session or database
    $user_type = 'guest';
    if (isset($_SESSION['user_type'])) {
        $user_type = $_SESSION['user_type'];
    } else if ($user_id) {
        // Get user type from database
        $stmt = $db->prepare("SELECT user_type FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $user_type = $row['user_type'];
        }
        $stmt->close();
    }
    
    $stmt = $db->prepare("INSERT INTO audit_trail (user_id, user_type, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $user_type, $activity, $details, $ip, $user_agent);
    $stmt->execute();
    $stmt->close();
}

// ============================================
// AUTHENTICATION FUNCTIONS
// ============================================

// Get current user type
function getCurrentUserType() {
    return $_SESSION['user_type'] ?? 'guest';
}

// Check authentication
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check role
function hasRole($role) {
    return isAuthenticated() && $_SESSION['user_type'] === $role;
}

// Check if admin or editor
function isAdminOrEditor() {
    return isAuthenticated() && in_array($_SESSION['user_type'], ['admin', 'editor']);
}

// Redirect if not authenticated
function requireLogin() {
    if (!isAuthenticated()) {
        header("Location: " . url('login'));
        exit;
    }
}

// Redirect if not admin/editor
function requireAdminOrEditor() {
    requireLogin();
    if (!isAdminOrEditor()) {
        header("Location: " . url('login'));
        exit;
    }
}

// Redirect function
function redirect($url) {
    // If URL doesn't start with http or /, use url() function
    if (strpos($url, 'http') !== 0 && strpos($url, '/') !== 0) {
        $url = url($url);
    }
    header("Location: " . $url);
    exit();
}

// ============================================
// SANITIZATION & VALIDATION FUNCTIONS
// ============================================

// Sanitize input
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Generate random string
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length));
}

// ============================================
// FILE UPLOAD FUNCTIONS
// ============================================

// Upload file
function uploadFile($file, $targetDir, $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed'];
    }
    
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExt, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    if ($file['size'] > 50 * 1024 * 1024) { // 50MB limit
        return ['success' => false, 'error' => 'File too large'];
    }
    
    $fileName = time() . '_' . generateRandomString(8) . '.' . $fileExt;
    $targetPath = $targetDir . $fileName;
    
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $fileName];
    }
    
    return ['success' => false, 'error' => 'Failed to move file'];
}

// Format file size
function formatFileSize($bytes, $decimal = 2) {
    $size = ['B', 'KB', 'MB', 'GB', 'TB'];
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimal}f", $bytes / pow(1024, $factor)) . ' ' . $size[$factor];
}

// ============================================
// WATERMARK FUNCTIONS
// ============================================

// Get watermark text
function getWatermarkText() {
    return 'UCLP Academy - ' . date('Y-m-d H:i:s');
}

// ============================================
// USER FUNCTIONS
// ============================================

// Get user by ID
function getUserById($id) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

// ============================================
// SPECIALTY FUNCTIONS
// ============================================

// Get specialty by ID
function getSpecialtyById($id) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM specialties WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $specialty = $result->fetch_assoc();
    $stmt->close();
    return $specialty;
}

// Get all specialties
function getAllSpecialties() {
    $db = Database::getInstance()->getConnection();
    $result = $db->query("SELECT * FROM specialties WHERE status = 1 ORDER BY name");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// ============================================
// BOOK & JOURNAL FUNCTIONS
// ============================================

// Get books by specialty
function getBooksBySpecialty($specialtyId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM books WHERE specialty_id = ? AND status = 'approved' ORDER BY title");
    $stmt->bind_param("i", $specialtyId);
    $stmt->execute();
    $result = $stmt->get_result();
    $books = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $books;
}

// Get journals by specialty
function getJournalsBySpecialty($specialtyId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM journals WHERE specialty_id = ? AND status = 'approved' ORDER BY date DESC");
    $stmt->bind_param("i", $specialtyId);
    $stmt->execute();
    $result = $stmt->get_result();
    $journals = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $journals;
}

// ============================================
// SUPPLY REQUEST FUNCTIONS
// ============================================

// Create supply request
function createSupplyRequest($userId, $itemId, $itemType, $deliveryAddress, $deliveryPhone) {
    $db = Database::getInstance()->getConnection();
    $bookId = $itemType === 'book' ? $itemId : null;
    $journalId = $itemType === 'journal' ? $itemId : null;
    
    $stmt = $db->prepare("INSERT INTO supply_requests (user_id, book_id, journal_id, request_type, delivery_address, delivery_phone) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisss", $userId, $bookId, $journalId, $itemType, $deliveryAddress, $deliveryPhone);
    $result = $stmt->execute();
    $id = $db->insert_id;
    $stmt->close();
    
    if ($result) {
        logActivity($userId, 'Requested Supply', $itemType . '_' . $itemId);
    }
    
    return $result ? $id : false;
}

// ============================================
// DOWNLOAD PERMISSION FUNCTIONS
// ============================================

// Check if user has download permission for a specific item
function hasDownloadPermission($userId, $itemId, $itemType = 'book') {
    $db = Database::getInstance()->getConnection();
    
    // Check if user has an approved download permission
    $stmt = $db->prepare("SELECT id, max_downloads, used_downloads, expires_at FROM download_permissions 
                          WHERE user_id = ? AND item_id = ? AND item_type = ? AND status = 'approved' 
                          AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->bind_param("iis", $userId, $itemId, $itemType);
    $stmt->execute();
    $result = $stmt->get_result();
    $permission = $result->fetch_assoc();
    $stmt->close();
    
    if (!$permission) {
        return false;
    }
    
    // Check if used downloads have reached the limit
    if ($permission['used_downloads'] >= $permission['max_downloads']) {
        return false;
    }
    
    return true;
}

// Get remaining downloads for a user on a specific item
function getRemainingDownloads($userId, $itemId, $itemType = 'book') {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT max_downloads, used_downloads FROM download_permissions 
                          WHERE user_id = ? AND item_id = ? AND item_type = ? AND status = 'approved' 
                          AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->bind_param("iis", $userId, $itemId, $itemType);
    $stmt->execute();
    $result = $stmt->get_result();
    $permission = $result->fetch_assoc();
    $stmt->close();
    
    if (!$permission) {
        return 0;
    }
    
    return max(0, $permission['max_downloads'] - $permission['used_downloads']);
}

// Increment download count for an item
function incrementDownloadCount($itemId, $itemType = 'book') {
    $db = Database::getInstance()->getConnection();
    $table = $itemType === 'book' ? 'books' : 'journals';
    $stmt = $db->prepare("UPDATE $table SET download_count = download_count + 1 WHERE id = ?");
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $stmt->close();
}

// Increment used downloads for a user permission
function incrementUsedDownloads($userId, $itemId, $itemType = 'book') {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("UPDATE download_permissions SET used_downloads = used_downloads + 1 
                          WHERE user_id = ? AND item_id = ? AND item_type = ? AND status = 'approved'");
    $stmt->bind_param("iis", $userId, $itemId, $itemType);
    $stmt->execute();
    $stmt->close();
}

// Request download permission - Allow re-request after downloads are used or expired
function requestDownloadPermission($userId, $itemId, $itemType = 'book') {
    $db = Database::getInstance()->getConnection();
    
    // Check if there's an active (not expired and not fully used) request
    $stmt = $db->prepare("SELECT id, status, max_downloads, used_downloads, expires_at FROM download_permissions 
                          WHERE user_id = ? AND item_id = ? AND item_type = ? 
                          AND status IN ('pending', 'approved') 
                          AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->bind_param("iis", $userId, $itemId, $itemType);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();
    
    // If there's an active request
    if ($existing) {
        // If it's pending, don't allow new request
        if ($existing['status'] == 'pending') {
            return ['success' => false, 'message' => 'You already have a pending request for this item. Please wait for admin approval.'];
        }
        
        // If it's approved but has remaining downloads, don't allow new request
        if ($existing['status'] == 'approved' && $existing['used_downloads'] < $existing['max_downloads']) {
            $remaining = $existing['max_downloads'] - $existing['used_downloads'];
            return ['success' => false, 'message' => "You still have $remaining download(s) remaining for this item."];
        }
        
        // If it's approved and downloads are used up, allow new request
        if ($existing['status'] == 'approved' && $existing['used_downloads'] >= $existing['max_downloads']) {
            // Create a new request with 'pending' status
            $stmt = $db->prepare("INSERT INTO download_permissions (user_id, item_type, item_id, status) VALUES (?, ?, ?, 'pending')");
            $stmt->bind_param("isi", $userId, $itemType, $itemId);
            $result = $stmt->execute();
            $id = $db->insert_id;
            $stmt->close();
            
            if ($result) {
                logActivity($userId, 'Requested Download Permission (Renewal)', $itemType . '_' . $itemId);
                return ['success' => true, 'message' => 'New download permission requested successfully. Please wait for admin approval.'];
            }
            return ['success' => false, 'message' => 'Failed to request download permission.'];
        }
    }
    
    // No existing active request - create new one
    $stmt = $db->prepare("INSERT INTO download_permissions (user_id, item_type, item_id, status) VALUES (?, ?, ?, 'pending')");
    $stmt->bind_param("isi", $userId, $itemType, $itemId);
    $result = $stmt->execute();
    $id = $db->insert_id;
    $stmt->close();
    
    if ($result) {
        logActivity($userId, 'Requested Download Permission', $itemType . '_' . $itemId);
        return ['success' => true, 'message' => 'Download permission requested successfully. Please wait for admin approval.'];
    }
    
    return ['success' => false, 'message' => 'Failed to request download permission.'];
}

// Get download permission requests for admin
function getDownloadPermissionRequests() {
    $db = Database::getInstance()->getConnection();
    $query = "SELECT dp.*, u.name as user_name, u.email as user_email,
              CASE WHEN dp.item_type = 'book' THEN b.title ELSE j.title END as item_title
              FROM download_permissions dp
              LEFT JOIN users u ON dp.user_id = u.id
              LEFT JOIN books b ON dp.item_type = 'book' AND dp.item_id = b.id
              LEFT JOIN journals j ON dp.item_type = 'journal' AND dp.item_id = j.id
              WHERE dp.status = 'pending'
              ORDER BY dp.requested_at DESC";
    return $db->query($query)->fetch_all(MYSQLI_ASSOC);
}

// Process download permission request
function processDownloadPermission($permissionId, $status, $maxDownloads = 2, $expiryDays = 30, $adminNotes = '') {
    $db = Database::getInstance()->getConnection();
    $processedBy = $_SESSION['user_id'] ?? 0;
    $expiresAt = $status === 'approved' ? date('Y-m-d H:i:s', strtotime("+$expiryDays days")) : null;
    
    $stmt = $db->prepare("UPDATE download_permissions 
                          SET status = ?, max_downloads = ?, expires_at = ?, approved_at = NOW(), 
                          admin_notes = ?, processed_by = ? 
                          WHERE id = ?");
    $stmt->bind_param("sisssi", $status, $maxDownloads, $expiresAt, $adminNotes, $processedBy, $permissionId);
    $result = $stmt->execute();
    $stmt->close();
    
    if ($result) {
        logActivity($processedBy, 'Processed Download Permission', "Permission ID: $permissionId, Status: $status");
    }
    
    return $result;
}

// ============================================
// ADDITIONAL UTILITY FUNCTIONS
// ============================================

// Check if user has supply request for an item
function hasSupplyRequest($userId, $itemId, $itemType = 'book') {
    $db = Database::getInstance()->getConnection();
    $column = $itemType === 'book' ? 'book_id' : 'journal_id';
    
    $stmt = $db->prepare("SELECT id FROM supply_requests WHERE user_id = ? AND $column = ? AND status != 'rejected'");
    $stmt->bind_param("ii", $userId, $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    
    return $exists;
}

// Get user's supply requests
function getUserSupplyRequests($userId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM supply_requests WHERE user_id = ? ORDER BY request_date DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $requests = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $requests;
}

// Check if user is verified
function isUserVerified($userId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT is_verified FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user && $user['is_verified'] == 1;
}

// Get user role label
function getUserRoleLabel($role) {
    $labels = [
        'admin' => 'Administrator',
        'editor' => 'Editor',
        'doctor' => 'Doctor'
    ];
    return $labels[$role] ?? ucfirst($role);
}

// Get status badge class
function getStatusBadgeClass($status) {
    $classes = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'completed' => 'info',
        'active' => 'success',
        'inactive' => 'danger'
    ];
    return $classes[$status] ?? 'secondary';
}
?>