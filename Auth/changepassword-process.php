<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$new = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (empty($new) || empty($confirm)) {
    $_SESSION['error'] = "Semua kolom wajib diisi!";
    header("Location: change-password.php");
    exit;
}

if ($new !== $confirm) {
    $_SESSION['error'] = "Password tidak sama!";
    header("Location: change-password.php");
    exit;
}

$hashed = password_hash($new, PASSWORD_DEFAULT);
$id = $_SESSION['user_id'];

$update = $conn->prepare("UPDATE akun SET password = ?, isPasswordChanged = 1 WHERE idAkun = ?");
$update->bind_param("ss", $hashed, $id);

if ($update->execute()) {
    $_SESSION['success'] = "Password berhasil diubah!";
    
    // Redirect sesuai role
    if ($_SESSION['role'] === 'guru') {
        header("Location: ../Guru/dashboard.php");
    } elseif ($_SESSION['role'] === 'siswa') {
        header("Location: ../Siswa/dashboard.php");
    } else {
        header("Location: ../Admin/dashboard.php");
    }
    exit;
} else {
    $_SESSION['error'] = "Gagal mengubah password!";
    header("Location: change-password.php");
    exit;
}
?>
