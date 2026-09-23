<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// If already logged in, redirect
if (isAuthenticated()) {
    $userType = $_SESSION['user_type'];
    if ($userType === 'admin') {
        header("Location: /admin");
        exit;
    } elseif ($userType === 'editor') {
        header("Location: /editor");
        exit;
    } else {
        header("Location: /doctor");
        exit;
    }
}

$error = null;
$remember_email = '';

// Check if remember me cookie exists
if (isset($_COOKIE['remember_email'])) {
    $remember_email = $_COOKIE['remember_email'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $result = Auth::login($email, $password);
        if ($result['success']) {
            // Handle Remember Me
            if ($remember) {
                // Set cookie for 30 days
                setcookie('remember_email', $email, time() + (86400 * 30), "/");
            } else {
                // Clear cookie if exists
                if (isset($_COOKIE['remember_email'])) {
                    setcookie('remember_email', '', time() - 3600, "/");
                }
            }
            
            $user = $result['user'];
            if ($user['user_type'] === 'admin') {
                header("Location: /admin");
                exit;
            } elseif ($user['user_type'] === 'editor') {
                header("Location: /editor");
                exit;
            } else {
                header("Location: /doctor");
                exit;
            }
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOGIN - BJDVL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cambria&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Cambria', Georgia, serif;
            background: #f5f7fa;
            color: #1a1a2e;
            font-size: 16px;
        }
        
        .login-wrapper { 
            min-height: calc(100vh - 160px); 
            display: flex; 
            align-items: center; 
            padding: 35px 0; 
        }
        
        .login-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            padding: 45px 40px;
            max-width: 480px;
            margin: 0 auto;
            width: 100%;
            border: 1px solid #eef1f5;
        }
        
        .login-card .logo { 
            text-align: center; 
            margin-bottom: 28px; 
        }
        .login-card .logo .logo-image {
            height: 70px;
            width: auto;
            margin-bottom: 8px;
        }
        .login-card .logo h3 { 
            font-weight: 700; 
            font-size: 1.6rem;
            margin-top: 6px; 
            font-family: 'Cambria', Georgia, serif;
            color: #1a1a2e;
        }
        .login-card .logo p { 
            color: #6c757d; 
            font-size: 0.95rem; 
            font-family: 'Cambria', Georgia, serif;
            margin: 2px 0 0;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px 16px;
            border: 2px solid #eef1f5;
            font-family: 'Cambria', Georgia, serif;
            background: #ffffff;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            color: #1a1a2e;
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13,110,253,0.1);
            background: #ffffff;
        }
        .form-control::placeholder {
            color: #adb5bd;
            font-size: 0.9rem;
        }
        .form-label { 
            font-weight: 600; 
            font-family: 'Cambria', Georgia, serif;
            color: #1a1a2e;
            font-size: 0.95rem;
            margin-bottom: 6px;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            border: none;
            color: #fff;
            padding: 13px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            font-family: 'Cambria', Georgia, serif;
            font-size: 1rem;
        }
        .btn-login:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 6px 20px rgba(13,110,253,0.3);
            color: #fff;
        }
        .btn-login i {
            margin-right: 8px;
        }
        
        .demo-credentials {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 16px 18px;
            margin-bottom: 22px;
            border: 1px solid #eef1f5;
        }
        .demo-credentials small { 
            display: block; 
            color: #6c757d; 
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.85rem;
            margin-bottom: 2px;
        }
        .demo-credentials .cred {
            font-family: 'Cambria', Georgia, serif;
            background: #fff;
            padding: 2px 10px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            color: #0d6efd;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .remember-checkbox {
            font-family: 'Cambria', Georgia, serif;
        }
        .remember-checkbox input[type="checkbox"] {
            width: 16px;
            height: 16px;
            margin-right: 6px;
            accent-color: #0d6efd;
        }
        .remember-checkbox .form-check-label {
            font-size: 0.9rem;
            color: #4a4a5e;
        }
        
        .forgot-link {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.9rem;
            color: #6c757d;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .forgot-link:hover {
            color: #0d6efd;
        }
        
        .register-link {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.9rem;
        }
        .register-link a {
            color: #0d6efd;
            text-decoration: none;
            font-weight: 600;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            font-family: 'Cambria', Georgia, serif;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .alert-danger {
            background: #fce4ec;
            color: #c62828;
        }
        .alert .btn-close {
            font-size: 0.7rem;
        }
        
        .footer-main {
            background: #ffffff;
            padding: 16px 0;
            border-top: 1px solid #eef1f5;
            margin-top: 0;
        }
        .footer-main p {
            margin: 0;
            color: #1a1a2e;
            font-size: 0.85rem;
            text-align: center;
            font-family: 'Cambria', Georgia, serif;
        }
        .footer-main p strong { color: #0d6efd; }
        .footer-main p .version { color: #6c757d; }
        
        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 768px) {
            .login-card { 
                padding: 35px 28px; 
                max-width: 440px;
            }
            .login-card .logo .logo-image {
                height: 60px;
            }
            .login-card .logo h3 {
                font-size: 1.4rem;
            }
        }
        
        @media (max-width: 576px) {
            .login-card { 
                padding: 25px 20px; 
                margin: 0 12px;
                border-radius: 12px;
            }
            .login-card .logo .logo-image {
                height: 50px;
            }
            .login-card .logo h3 { 
                font-size: 1.2rem; 
            }
            .login-card .logo p { 
                font-size: 0.85rem; 
            }
            .form-control { 
                padding: 10px 14px; 
                font-size: 0.9rem; 
            }
            .form-label {
                font-size: 0.85rem;
            }
            .btn-login { 
                padding: 11px; 
                font-size: 0.95rem; 
            }
            .demo-credentials { 
                padding: 12px 14px; 
            }
            .demo-credentials small { 
                font-size: 0.78rem; 
            }
            .demo-credentials .cred {
                font-size: 0.78rem;
            }
            .login-wrapper { 
                min-height: calc(100vh - 140px); 
                padding: 20px 0;
            }
            .remember-checkbox .form-check-label {
                font-size: 0.85rem;
            }
            .forgot-link {
                font-size: 0.85rem;
            }
            .register-link {
                font-size: 0.85rem;
            }
            .alert {
                font-size: 0.85rem;
                padding: 10px 14px;
            }
        }
        
        @media (max-width: 400px) {
            .login-card { 
                padding: 18px 14px; 
                margin: 0 8px;
            }
            .login-card .logo .logo-image {
                height: 44px;
            }
            .login-card .logo h3 {
                font-size: 1.1rem;
            }
            .login-card .logo p {
                font-size: 0.78rem;
            }
            .form-control {
                padding: 8px 12px;
                font-size: 0.85rem;
                border-radius: 8px;
            }
            .form-label {
                font-size: 0.8rem;
            }
            .btn-login {
                padding: 9px;
                font-size: 0.88rem;
                border-radius: 8px;
            }
            .demo-credentials {
                padding: 10px 12px;
            }
            .demo-credentials small {
                font-size: 0.72rem;
            }
            .demo-credentials .cred {
                font-size: 0.72rem;
                padding: 1px 6px;
            }
            .remember-checkbox input[type="checkbox"] {
                width: 14px;
                height: 14px;
            }
            .remember-checkbox .form-check-label {
                font-size: 0.8rem;
            }
            .forgot-link {
                font-size: 0.8rem;
            }
            .register-link {
                font-size: 0.8rem;
            }
            .footer-main p {
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body>
    <!-- ============================================
    NAVIGATION - Using public_nav.php
    ============================================ -->
    <?php include __DIR__ . '/../includes/public_nav.php'; ?>

    <!-- ============================================
    LOGIN FORM
    ============================================ -->
    <div class="login-wrapper">
        <div class="login-card">
            <div class="logo">
                <img src="/assets/images/logo.png" alt="UCLP Academy" class="logo-image">
                <h3>Welcome Back</h3>
                <p>Sign in to access medical books and journals</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- <div class="demo-credentials">
                <small><i class="fas fa-info-circle"></i> <strong>Demo Credentials:</strong></small>
                <small>Admin: <span class="cred">admin@uclp.edu</span> / <span class="cred">admin123</span></small>
                <small>Editor: <span class="cred">editor@uclp.edu</span> / <span class="cred">editor123</span></small>
            </div> -->
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control" name="email" 
                           value="<?php echo htmlspecialchars($remember_email); ?>" 
                           placeholder="Enter your email" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" placeholder="Enter your password" required>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check remember-checkbox">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember" 
                               <?php echo !empty($remember_email) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="remember">
                            Remember me
                        </label>
                    </div>
                    <a href="/forgot-password" class="forgot-link">Forgot password?</a>
                </div>
                <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Sign In</button>
            </form>
            
            <div class="text-center mt-3 register-link">
                <small class="text-muted">Don't have an account? <a href="/register">Register here</a></small>
            </div>
        </div>
    </div>

    <!-- ============================================
    FOOTER
    ============================================ -->
    <footer class="footer-main">
        <div class="container">
            <p>
                <strong>BJDVL</strong> &copy; <?php echo date('Y'); ?> 
                <span class="version">v2.0</span> &bull; 
                Powered by UniMed UniHealth Group
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>