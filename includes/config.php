<?php
// Define base paths - ROOT DOMAIN VERSION
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
if (!defined('BASE_URL')) {
    define('BASE_URL', '/'); // Changed from '/uclp_academy/' to '/'
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'uclp_dummy_academy');
define('DB_PASS', '<Admin123!@#>');
define('DB_NAME', 'uclp_dummy_academy');

// Application configuration
define('SITE_NAME', 'Dummy Academy');
define('SITE_URL', 'https://dummy.uclp.academy/');
define('ADMIN_EMAIL', 'admin@uclp.edu');
define('TIMEZONE', 'Asia/Dhaka');

// Upload paths
define('UPLOAD_PATH', BASE_PATH . '/uploads/');
define('BOOK_UPLOAD_PATH', UPLOAD_PATH . 'books/');
define('JOURNAL_UPLOAD_PATH', UPLOAD_PATH . 'journals/');
define('PROFILE_UPLOAD_PATH', UPLOAD_PATH . 'profiles/');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set(TIMEZONE);

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define user roles
define('ROLE_ADMIN', 'admin');
define('ROLE_EDITOR', 'editor');
define('ROLE_DOCTOR', 'doctor');
?>