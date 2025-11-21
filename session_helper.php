<?php

function startRoleSession($role) {
    // Create a unique session name depending on the role
    $sessionName = 'CARMAX_' . strtoupper($role) . '_SESSION';
    session_name($sessionName);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // If not logged in for this role, redirect
    if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        header('Location: ../LoginPage/loginPage.php');
        exit();
    }
}

function endRoleSession($role) {
    $sessionName = 'CARMAX_' . strtoupper($role) . '_SESSION';
    session_name($sessionName);

    session_start();
    session_unset();
    session_destroy();
}
