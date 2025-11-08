<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] === 'guru') {
    header("Location: ../Guru/dashboard.php");
} elseif ($_SESSION['role'] === 'siswa') {
    header("Location: ../Siswa/dashboard.php");
} else {
    header("Location: ../Admin/dashboard.php");
}
exit;
?>
