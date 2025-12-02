<?php
// config/session.php

// Cegah error jika session sudah dimulai sebelumnya
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sudah login
function checkLogin() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        // Jika belum login, arahkan ke halaman login
        header('Location: ../Auth/login.php');
        exit;
    }
}

// Cek apakah user punya role tertentu
function checkRole($allowedRoles = []) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
        header('Location: ../Auth/login.php');
        exit;
    }
}

// Logout function (optional)
function logout() {
    session_unset();
    session_destroy();
    header('Location: ../Auth/login.php');
    exit;
}
?>
