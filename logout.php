<?php
require_once 'session_helper.php';

// Find and destroy the active role session
$roles = ['acquisition', 'operation', 'superadmin'];

foreach ($roles as $role) {
    $sessionName = 'CARMAX_' . strtoupper($role) . '_SESSION';
    session_name($sessionName);
    session_start();
    
    if (isset($_SESSION['id'])) {
        session_unset();
        session_destroy();
        break;
    }
    
    session_write_close();
}


header('Location: /LandingPage/LandingPage.html');
exit();