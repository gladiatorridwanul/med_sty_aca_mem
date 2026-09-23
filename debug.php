<?php
require_once 'includes/config.php';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

echo "<h1>UCLP Academy - Debug Information</h1>";

// Check database connection
$db = Database::getInstance()->getConnection();

if ($db) {
    echo "<p style='color:green;'>✅ Database connected successfully</p>";
} else {
    echo "<p style='color:red;'>❌ Database connection failed</p>";
    exit;
}

// Check users table
$result = $db->query("SELECT COUNT(*) as count FROM users");
if ($result) {
    $count = $result->fetch_assoc();
    echo "<p>Total users: " . $count['count'] . "</p>";
} else {
    echo "<p style='color:red;'>❌ Error checking users table: " . $db->error . "</p>";
}

// Check admin user
$result = $db->query("SELECT id, user_type, name, email, is_verified, is_active FROM users WHERE email = 'admin@uclp.edu'");
if ($result && $result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo "<h2>Admin User Found:</h2>";
    echo "<pre>";
    print_r($admin);
    echo "</pre>";
} else {
    echo "<p style='color:red;'>❌ Admin user not found!</p>";
}

// Check editor user
$result = $db->query("SELECT id, user_type, name, email, is_verified, is_active FROM users WHERE email = 'editor@uclp.edu'");
if ($result && $result->num_rows > 0) {
    $editor = $result->fetch_assoc();
    echo "<h2>Editor User Found:</h2>";
    echo "<pre>";
    print_r($editor);
    echo "</pre>";
} else {
    echo "<p style='color:red;'>❌ Editor user not found!</p>";
}

// Test password verification
$test_password = 'admin123';
$test_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "<h2>Password Verification Test:</h2>";
echo "Password: 'admin123'<br>";
echo "Hash: $test_hash<br>";

if (password_verify($test_password, $test_hash)) {
    echo "<p style='color:green;'>✅ Password verification successful!</p>";
} else {
    echo "<p style='color:red;'>❌ Password verification failed!</p>";
}

// Test creating a new hash
$new_hash = password_hash('admin123', PASSWORD_DEFAULT);
echo "<p>New hash for 'admin123': " . $new_hash . "</p>";

echo "<h2>PHP Information:</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Password Hash Algorithm: " . PASSWORD_DEFAULT . "<br>";

// Check config
echo "<h2>Configuration:</h2>";
echo "DB_HOST: " . DB_HOST . "<br>";
echo "DB_NAME: " . DB_NAME . "<br>";
echo "DB_USER: " . DB_USER . "<br>";

// Test SQL to fix user if needed
echo "<h2>Fix Commands (Copy and run in phpMyAdmin):</h2>";
echo "<pre>
-- Update admin password
UPDATE users SET password = '" . password_hash('admin123', PASSWORD_DEFAULT) . "' WHERE email = 'admin@uclp.edu';

-- Update editor password
UPDATE users SET password = '" . password_hash('editor123', PASSWORD_DEFAULT) . "' WHERE email = 'editor@uclp.edu';

-- Check if users exist
SELECT * FROM users WHERE email IN ('admin@uclp.edu', 'editor@uclp.edu');
</pre>";
?>