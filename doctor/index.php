<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (isAuthenticated() && $_SESSION['user_type'] === 'doctor') {
    redirect('dashboard.php');
} else {
    redirect('../public/login.php');
}
?>