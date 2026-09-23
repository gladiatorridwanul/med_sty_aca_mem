<?php
/**
 * UCLP Academy - Main Entry Point
 * Handles all routing - Root domain version
 */

// Define base path
define('BASE_PATH', __DIR__);
define('BASE_URL', '/');

// Load configuration
require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/functions.php';

// Get the request URI
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = BASE_URL;

// Remove base path
if (strpos($request_uri, $base_path) === 0 && $base_path != '/') {
    $request_uri = substr($request_uri, strlen($base_path));
} else if ($base_path == '/') {
    $request_uri = ltrim($request_uri, '/');
}

// Remove query string
if (strpos($request_uri, '?') !== false) {
    $request_uri = substr($request_uri, 0, strpos($request_uri, '?'));
}

// Remove trailing slash
$request_uri = rtrim($request_uri, '/');

// If empty, go to home
if (empty($request_uri)) {
    include BASE_PATH . '/public/index.php';
    exit;
}

// Route definitions
$routes = [
    '' => 'public/index.php',
    'home' => 'public/index.php',
    'login' => 'public/login.php',
    'register' => 'public/register.php',
    'logout' => 'public/logout.php',
    'browse' => 'public/browse.php',
    'forgot-password' => 'public/forgot_password.php',
    'reset-password' => 'public/reset_password.php',
    
    // ✅ Public Committee Page
    'committee' => 'committee.php',
    
    // Admin routes
    'admin' => 'admin/dashboard.php',
    'admin/dashboard' => 'admin/dashboard.php',
    'admin/doctors' => 'admin/manage_doctors.php',
    'admin/verify' => 'admin/verify_doctor.php',
    'admin/books' => 'admin/manage_books.php',
    'admin/journals' => 'admin/manage_journals.php',
    'admin/requests' => 'admin/supply_requests.php',
    'admin/download-permissions' => 'admin/manage_download_permissions.php',
    'admin/audit' => 'admin/audit_trail.php',
    'admin/profile' => 'admin/profile.php',
    'admin/edit_book' => 'admin/edit_book.php',
    'admin/edit_journal' => 'admin/edit_journal.php',
    'admin/view_doctor' => 'admin/view_doctor.php',
    'admin/get-doctor' => 'admin/get_doctor.php',
    'admin/get-doctor-activities' => 'admin/get_doctor_activities.php',
    
    // ✅ Admin Committee Routes
    'admin/designations' => 'admin/designations.php',
    'admin/committee_years' => 'admin/committee_years.php',
    'admin/committee_years.php' => 'admin/committee_years.php',
    'admin/committee_members' => 'admin/committee_members.php',
    'admin/committee_members.php' => 'admin/committee_members.php',
    'admin/edit_committee_member' => 'admin/edit_committee_member.php',
    'admin/committee_members' => 'admin/committee_members.php',
    'admin/committee_members.php' => 'admin/committee_members.php',
    
    // Editor routes
    'editor' => 'editor/dashboard.php',
    'editor/dashboard' => 'editor/dashboard.php',
    'editor/books' => 'editor/manage_books.php',
    'editor/journals' => 'editor/manage_journals.php',
    'editor/requests' => 'editor/supply_requests.php',
    'editor/download-permissions' => 'editor/manage_download_permissions.php',
    'editor/audit' => 'editor/audit_trail.php',
    'editor/profile' => 'editor/profile.php',
    
    // Doctor routes
    'doctor' => 'doctor/dashboard.php',
    'doctor/dashboard' => 'doctor/dashboard.php',
    'doctor/books' => 'doctor/books.php',
    'doctor/journals' => 'doctor/journals.php',
    'doctor/requests' => 'doctor/my_requests.php',
    'doctor/profile' => 'doctor/profile.php',
];

// Check if route exists
if (isset($routes[$request_uri])) {
    $file = BASE_PATH . '/' . $routes[$request_uri];
    if (file_exists($file)) {
        include $file;
        exit;
    } else {
        // Debug: show what's missing
        if (strpos($request_uri, 'admin/committee') !== false || strpos($request_uri, 'admin/designations') !== false) {
            die("File not found: " . $file . " (route: " . $request_uri . ")");
        }
    }
}

// Pattern routes for doctor actions
if (preg_match('/^doctor\/view-book\/(\d+)$/', $request_uri, $matches)) {
    $_GET['id'] = $matches[1];
    $file = BASE_PATH . '/doctor/view_book.php';
    if (file_exists($file)) { include $file; exit; }
}

if (preg_match('/^doctor\/view-journal\/(\d+)$/', $request_uri, $matches)) {
    $_GET['id'] = $matches[1];
    $file = BASE_PATH . '/doctor/view_journal.php';
    if (file_exists($file)) { include $file; exit; }
}

if (preg_match('/^doctor\/read\/(\d+)$/', $request_uri, $matches)) {
    $_GET['id'] = $matches[1];
    $file = BASE_PATH . '/doctor/online_reader.php';
    if (file_exists($file)) { include $file; exit; }
}

if (preg_match('/^doctor\/read-journal\/(\d+)$/', $request_uri, $matches)) {
    $_GET['id'] = $matches[1];
    $file = BASE_PATH . '/doctor/online_reader_journal.php';
    if (file_exists($file)) { include $file; exit; }
}

if (preg_match('/^doctor\/request\/(book|journal)\/(\d+)$/', $request_uri, $matches)) {
    $_GET['type'] = $matches[1];
    $_GET['id'] = $matches[2];
    $file = BASE_PATH . '/doctor/supply_request.php';
    if (file_exists($file)) { include $file; exit; }
}

if (preg_match('/^doctor\/request-download\/(book|journal)\/(\d+)$/', $request_uri, $matches)) {
    $_GET['type'] = $matches[1];
    $_GET['id'] = $matches[2];
    $file = BASE_PATH . '/doctor/request_download.php';
    if (file_exists($file)) { include $file; exit; }
}

if (preg_match('/^browse\/(\d+)$/', $request_uri, $matches)) {
    $_GET['specialty'] = $matches[1];
    $file = BASE_PATH . '/public/browse.php';
    if (file_exists($file)) { include $file; exit; }
}

// ✅ Committee year filter pattern (e.g., /committee?year=2024-2025)
if ($request_uri === 'committee') {
    $file = BASE_PATH . '/committee.php';
    if (file_exists($file)) { include $file; exit; }
}

// 404 - Page Not Found
header("HTTP/1.0 404 Not Found");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | UCLP Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f0f4f8, #d9e2ec);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-box {
            text-align: center;
            padding: 60px 40px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            max-width: 500px;
        }
        .error-box .icon { font-size: 5rem; color: #0d6efd; margin-bottom: 20px; }
        .error-box h1 { font-size: 4rem; font-weight: 800; color: #1a1a2e; }
        .error-box h4 { color: #4a4a5e; margin-bottom: 20px; }
        .error-box p { color: #6c757d; margin-bottom: 30px; }
        .btn-home {
            padding: 12px 35px;
            border-radius: 50px;
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            border: none;
            color: #fff;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-home:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(13,110,253,0.3); color: #fff; }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="icon"><i class="fas fa-search"></i></div>
        <h1>404</h1>
        <h4>Page Not Found</h4>
        <p>The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
        <a href="/" class="btn-home"><i class="fas fa-home"></i> Go to Homepage</a>
    </div>
</body>
</html>