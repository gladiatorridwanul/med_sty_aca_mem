<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$error = null;
$success = null;
$specialties = getAllSpecialties();

// Clear any cached form data
$formData = [
    'name' => '',
    'bmdc_reg_no' => '',
    'specialty' => '',
    'hospital_institute' => '',
    'mobile' => '',
    'email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitize($_POST['name'] ?? ''),
        'bmdc_reg_no' => sanitize($_POST['bmdc_reg_no'] ?? ''),
        'specialty' => sanitize($_POST['specialty'] ?? ''),
        'hospital_institute' => sanitize($_POST['hospital_institute'] ?? ''),
        'mobile' => sanitize($_POST['mobile'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? ''
    ];
    
    if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
        $error = 'Please fill in all required fields';
    } elseif (!validateEmail($data['email'])) {
        $error = 'Invalid email address';
    } elseif (strlen($data['password']) < 8) {
        $error = 'Password must be at least 8 characters';
    } elseif ($data['password'] !== $data['confirm_password']) {
        $error = 'Passwords do not match';
    } else {
        $result = Auth::register($data);
        if ($result['success']) {
            $success = $result['message'];
            // Clear form data on success
            $formData = [
                'name' => '',
                'bmdc_reg_no' => '',
                'specialty' => '',
                'hospital_institute' => '',
                'mobile' => '',
                'email' => '',
            ];
        } else {
            $error = $result['error'];
            // Keep the entered data for correction
            $formData = $data;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - BJDVL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cambria&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Cambria', Georgia, serif;
            background: #ffffff;
            color: #000000;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-size: 16px;
        }
        
        /* ============================================
           MAIN WRAPPER - FLEXIBLE
           ============================================ */
        .register-wrapper { 
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 35px 15px;
            min-height: calc(100vh - 140px);
            background: #f8f9fa;
        }
        
        /* ============================================
           REGISTER CARD - WHITE BACKGROUND
           ============================================ */
        .register-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            padding: 45px 40px;
            max-width: 760px;
            width: 100%;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .register-card .logo { 
            text-align: center; 
            margin-bottom: 30px; 
        }
        .register-card .logo .logo-image {
            height: 70px;
            width: auto;
            margin-bottom: 6px;
        }
        .register-card .logo h3 { 
            font-weight: 700; 
            font-size: 1.6rem;
            margin: 4px 0 0;
            font-family: 'Cambria', Georgia, serif;
            color: #000000;
        }
        .register-card .logo p { 
            color: #6c757d; 
            font-size: 0.95rem; 
            font-family: 'Cambria', Georgia, serif;
            margin: 2px 0 0;
        }
        
        /* ============================================
           FORM ELEMENTS - BLACK TEXT
           ============================================ */
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-label { 
            font-weight: 600; 
            font-family: 'Cambria', Georgia, serif;
            color: #000000;
            font-size: 0.92rem;
            margin-bottom: 6px;
            display: block;
        }
        .form-label .required {
            color: #dc3545;
            margin-left: 2px;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            font-family: 'Cambria', Georgia, serif;
            background: #ffffff;
            transition: all 0.3s ease;
            width: 100%;
            font-size: 0.95rem;
            color: #000000;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13,110,253,0.1);
            background: #ffffff;
            outline: none;
        }
        .form-control::placeholder {
            color: #adb5bd;
            font-size: 0.9rem;
        }
        
        .form-select {
            appearance: auto;
            cursor: pointer;
        }
        .form-select option {
            font-family: 'Cambria', Georgia, serif;
            color: #000000;
            background: #ffffff;
        }
        
        .password-hint {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.78rem;
            color: #6c757d;
            margin-top: 4px;
        }
        .password-hint i {
            margin-right: 4px;
        }
        
        .info-text {
            font-family: 'Cambria', Georgia, serif;
            font-size: 0.88rem;
            color: #6c757d;
            padding: 12px 16px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            margin-bottom: 20px;
        }
        .info-text i {
            color: #0d6efd;
            margin-right: 6px;
        }
        
        /* ============================================
           BUTTONS
           ============================================ */
        .btn-register {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            border: none;
            color: #ffffff;
            padding: 14px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            font-family: 'Cambria', Georgia, serif;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-register:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 6px 20px rgba(13,110,253,0.3);
            color: #ffffff;
        }
        .btn-register:active {
            transform: translateY(0);
        }
        
        /* ============================================
           LINKS
           ============================================ */
        .login-link {
            font-family: 'Cambria', Georgia, serif;
            text-align: center;
            margin-top: 20px;
        }
        .login-link small {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .login-link a {
            color: #0d6efd;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .login-link a:hover {
            text-decoration: underline;
            color: #0a58ca;
        }
        
        /* ============================================
           ALERTS
           ============================================ */
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
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .alert .btn-close {
            font-size: 0.7rem;
        }
        
        /* ============================================
           FOOTER
           ============================================ */
        .footer-main {
            background: #ffffff;
            padding: 16px 0;
            border-top: 1px solid #e9ecef;
            margin-top: auto;
            flex-shrink: 0;
        }
        .footer-main p {
            margin: 0;
            color: #000000;
            font-size: 0.85rem;
            text-align: center;
            font-family: 'Cambria', Georgia, serif;
        }
        .footer-main p strong { color: #0d6efd; }
        .footer-main p .version { color: #6c757d; }
        
        /* ============================================
           RESPONSIVE BREAKPOINTS
           ============================================ */
        
        @media (max-width: 768px) {
            .register-card {
                padding: 35px 28px;
                max-width: 100%;
            }
            .register-card .logo .logo-image {
                height: 60px;
            }
            .register-card .logo h3 {
                font-size: 1.4rem;
            }
            .form-control, .form-select {
                padding: 10px 14px;
                font-size: 0.92rem;
            }
        }
        
        @media (max-width: 576px) {
            .register-wrapper {
                padding: 20px 12px;
                min-height: calc(100vh - 130px);
            }
            .register-card {
                padding: 25px 20px;
                border-radius: 12px;
            }
            .register-card .logo {
                margin-bottom: 22px;
            }
            .register-card .logo .logo-image {
                height: 50px;
            }
            .register-card .logo h3 {
                font-size: 1.2rem;
            }
            .register-card .logo p {
                font-size: 0.85rem;
            }
            .form-control, .form-select {
                padding: 10px 14px;
                font-size: 0.9rem;
                border-radius: 8px;
            }
            .form-label {
                font-size: 0.85rem;
            }
            .btn-register {
                padding: 12px;
                font-size: 0.95rem;
                border-radius: 8px;
            }
            .form-group {
                margin-bottom: 14px;
            }
            .info-text {
                font-size: 0.82rem;
                padding: 10px 14px;
                border-radius: 6px;
            }
            .login-link small {
                font-size: 0.85rem;
            }
            .alert {
                font-size: 0.85rem;
                padding: 10px 14px;
            }
            .password-hint {
                font-size: 0.72rem;
            }
            .footer-main p {
                font-size: 0.78rem;
            }
        }
        
        @media (max-width: 400px) {
            .register-wrapper {
                padding: 12px 8px;
            }
            .register-card {
                padding: 18px 14px;
                border-radius: 10px;
            }
            .register-card .logo .logo-image {
                height: 44px;
            }
            .register-card .logo h3 {
                font-size: 1.05rem;
            }
            .register-card .logo p {
                font-size: 0.78rem;
            }
            .form-control, .form-select {
                padding: 8px 12px;
                font-size: 0.85rem;
                border-radius: 6px;
                border-width: 1.5px;
            }
            .form-control::placeholder {
                font-size: 0.82rem;
            }
            .form-label {
                font-size: 0.8rem;
            }
            .form-group {
                margin-bottom: 10px;
            }
            .btn-register {
                padding: 10px;
                font-size: 0.88rem;
                border-radius: 6px;
            }
            .info-text {
                font-size: 0.75rem;
                padding: 8px 12px;
            }
            .login-link {
                margin-top: 14px;
            }
            .login-link small {
                font-size: 0.8rem;
            }
            .alert {
                font-size: 0.8rem;
                padding: 8px 12px;
            }
            .password-hint {
                font-size: 0.68rem;
            }
            .footer-main p {
                font-size: 0.7rem;
            }
        }
        
        /* ============================================
           PRINT STYLES
           ============================================ */
        @media print {
            .register-wrapper {
                min-height: auto;
                padding: 20px;
            }
            .register-card {
                box-shadow: none;
                border: 1px solid #000;
            }
            .btn-register {
                display: none;
            }
            .footer-main {
                display: none;
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
    REGISTER FORM
    ============================================ -->
    <div class="register-wrapper">
        <div class="register-card">
            <div class="logo">
                <img src="/assets/images/logo.png" alt="UCLP Academy" class="logo-image">
                <h3>Doctor Registration</h3>
                <p>Join UCLP Academy to access medical resources</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" autocomplete="off">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="form-label">Full Name <span class="required">*</span></label>
                        <input type="text" class="form-control" name="name" 
                               value="<?php echo htmlspecialchars($formData['name'] ?? ''); ?>" 
                               autocomplete="off" placeholder="Enter your full name" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="form-label">BMDC Reg. No.</label>
                        <input type="text" class="form-control" name="bmdc_reg_no" 
                               value="<?php echo htmlspecialchars($formData['bmdc_reg_no'] ?? ''); ?>" 
                               autocomplete="off" placeholder="Enter BMDC registration number">
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="form-label">Specialty <span class="required">*</span></label>
                        <select class="form-select" name="specialty" required>
                            <option value="">Select Specialty</option>
                            <?php foreach ($specialties as $specialty): ?>
                            <option value="<?php echo $specialty['id']; ?>" 
                                    <?php echo (isset($formData['specialty']) && $formData['specialty'] == $specialty['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($specialty['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="form-label">Hospital/Institute</label>
                        <input type="text" class="form-control" name="hospital_institute" 
                               value="<?php echo htmlspecialchars($formData['hospital_institute'] ?? ''); ?>" 
                               autocomplete="off" placeholder="Enter hospital or institute name">
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="form-label">Mobile</label>
                        <input type="text" class="form-control" name="mobile" 
                               value="<?php echo htmlspecialchars($formData['mobile'] ?? ''); ?>" 
                               autocomplete="off" placeholder="Enter mobile number">
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="form-label">Email <span class="required">*</span></label>
                        <input type="email" class="form-control" name="email" 
                               value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" 
                               autocomplete="off" placeholder="Enter your email address" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="form-label">Password <span class="required">*</span></label>
                        <input type="password" class="form-control" name="password" 
                               autocomplete="new-password" placeholder="Create a password (min. 8 characters)" required>
                        <div class="password-hint"><i class="fas fa-info-circle"></i> Minimum 8 characters</div>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="form-label">Confirm Password <span class="required">*</span></label>
                        <input type="password" class="form-control" name="confirm_password" 
                               autocomplete="new-password" placeholder="Confirm your password" required>
                    </div>
                </div>
                
                <div class="info-text">
                    <i class="fas fa-shield-alt"></i> Your account will be reviewed by admin before activation. Please provide accurate information.
                </div>
                
                <button type="submit" class="btn-register">
                    <i class="fas fa-user-plus"></i> Register Account
                </button>
            </form>
            
            <div class="login-link">
                <small>Already have an account? <a href="/login">Login here</a></small>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Reset all form fields to prevent browser autofill
            var form = document.querySelector('form');
            if (form) {
                var inputs = form.querySelectorAll('input');
                inputs.forEach(function(input) {
                    if (input.type !== 'hidden' && input.type !== 'submit') {
                        if (input.type !== 'password' || !input.value) {
                            input.value = '';
                        }
                    }
                });
            }
            
            // Add random attribute to prevent autofill
            var forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                form.setAttribute('autocomplete', 'off');
            });
        });
    </script>
</body>
</html>