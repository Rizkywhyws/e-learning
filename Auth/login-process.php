<?php
session_start();
require_once '../config/db.php';

// Ambil data dari form
$email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validasi server-side
if (empty($email) || empty($password)) {
    $_SESSION['error'] = 'Email dan password wajib diisi!';
    header('Location: login.php');
    exit;
}

// Query untuk mencari user berdasarkan email dan role admin
$query = "SELECT * FROM akun WHERE email = ? AND role = 'admin' LIMIT 1";
$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        // Verifikasi password
        if (password_verify($password, $user['password'])) {
            // Login berhasil
            $_SESSION['user_id'] = $user['idAkun'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            
            // Redirect berdasarkan role
            if ($user['role'] === 'admin') {
                header('Location: ../Admin/dashboard.php');
            } elseif ($user['role'] === 'guru') {
                header('Location: ../Guru/dashboard.php');
            } elseif ($user['role'] === 'siswa') {
                header('Location: ../Siswa/dashboard.php');
            }
            exit;
        } else {
            // Password salah
            $_SESSION['error'] = 'Email atau password salah!';
            header('Location: login.php');
            exit;
        }
    } else {
        // Email tidak ditemukan atau bukan admin
        $_SESSION['error'] = 'Email atau password salah!';
        header('Location: login.php');
        exit;
    }
    
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['error'] = 'Terjadi kesalahan sistem!';
    header('Location: login.php');
    exit;
}

mysqli_close($conn);
?>