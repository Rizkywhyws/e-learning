<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';

    if (empty($email) || empty($new_password)) {
        $_SESSION['error'] = "Semua kolom wajib diisi!";
        header("Location: login.php");
        exit;
    }

    // Cek apakah email terdaftar
    $query = $conn->prepare("SELECT * FROM akun WHERE email = ?");
    $query->bind_param("s", $email);
    $query->execute();
    $result = $query->get_result();

    if ($user = $result->fetch_assoc()) {
        // Update password baru
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE akun SET password = ? WHERE email = ?");
        $update->bind_param("ss", $hashed, $email);
        $update->execute();

        $_SESSION['success'] = "Password berhasil diperbarui! Silakan login kembali.";
    } else {
        $_SESSION['error'] = "Email tidak ditemukan!";
    }

    header("Location: login.php");
    exit;
}
?>
