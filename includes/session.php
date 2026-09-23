<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/functions.php';

class Session {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public static function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    public static function has($key) {
        return isset($_SESSION[$key]);
    }
    
    public static function remove($key) {
        unset($_SESSION[$key]);
    }
    
    public static function destroy() {
        session_destroy();
        $_SESSION = [];
    }
    
    public static function setFlash($key, $message) {
        $_SESSION['flash'][$key] = $message;
    }
    
    public static function getFlash($key, $default = null) {
        $message = $_SESSION['flash'][$key] ?? $default;
        unset($_SESSION['flash'][$key]);
        return $message;
    }
    
    public static function login($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_verified'] = $user['is_verified'];
        $_SESSION['login_time'] = time();
        
        // Store session in database
        self::storeSession($user['id']);
        
        // Log login activity
        if (function_exists('logActivity')) {
            logActivity($user['id'], 'User Login', 'User logged in successfully');
        }
    }
    
    private static function storeSession($userId) {
        $db = Database::getInstance()->getConnection();
        $sessionToken = session_id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Delete old sessions for this user
        $stmt = $db->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        // Create new session
        $stmt = $db->prepare("INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $sessionToken, $ip, $userAgent);
        $stmt->execute();
        $stmt->close();
    }
    
    public static function logout() {
        $userId = $_SESSION['user_id'] ?? null;
        
        if ($userId) {
            if (function_exists('logActivity')) {
                logActivity($userId, 'User Logout', 'User logged out');
            }
            
            $db = Database::getInstance()->getConnection();
            $sessionToken = session_id();
            $stmt = $db->prepare("DELETE FROM user_sessions WHERE session_token = ?");
            $stmt->bind_param("s", $sessionToken);
            $stmt->execute();
            $stmt->close();
        }
        
        self::destroy();
    }
    
    public static function validate() {
        if (!self::has('user_id')) {
            return false;
        }
        
        $db = Database::getInstance()->getConnection();
        $sessionToken = session_id();
        $stmt = $db->prepare("SELECT user_id FROM user_sessions WHERE session_token = ? AND last_activity > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stmt->bind_param("s", $sessionToken);
        $stmt->execute();
        $result = $stmt->get_result();
        $valid = $result->num_rows > 0;
        $stmt->close();
        
        if (!$valid) {
            self::destroy();
            return false;
        }
        
        // Update last activity
        $stmt = $db->prepare("UPDATE user_sessions SET last_activity = NOW() WHERE session_token = ?");
        $stmt->bind_param("s", $sessionToken);
        $stmt->execute();
        $stmt->close();
        
        return true;
    }
}
?>