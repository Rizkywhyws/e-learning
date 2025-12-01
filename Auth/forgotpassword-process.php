<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($email) || empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['error'] = "Semua kolom wajib diisi!";
        header("Location: login.php");
        exit;
    }

    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "Password baru dan konfirmasi tidak cocok!";
        header("Location: login.php");
        exit;
    }

    $query = $conn->prepare("SELECT password FROM akun WHERE email = ?");
    $query->bind_param("s", $email);
    $query->execute();
    $result = $query->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($old_password, $user['password'])) {
            // Verifikasi berhasil
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE akun SET password = ? WHERE email = ?");
            $update->bind_param("ss", $hashed_new_password, $email);

            if ($update->execute()) {
                if ($update->affected_rows > 0) {
                    $_SESSION['success'] = "Password berhasil diperbarui! Silakan login kembali.";
                } else {
                    $_SESSION['error'] = "Password tidak berubah (mungkin sama dengan sebelumnya).";
                }
            } else {
                $_SESSION['error'] = "Gagal update: " . $conn->error;
            }
        } else {
            $_SESSION['error'] = "Password lama salah!";
        }
    } else {
        $_SESSION['error'] = "Email tidak ditemukan!";
    }

    header("Location: login.php");
    exit;
}
?>