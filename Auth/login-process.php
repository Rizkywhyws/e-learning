<?php
session_start();
require_once '../config/db.php';

// Ambil data dari form
$email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validasi input
if (empty($email) || empty($password)) {
    $_SESSION['error'] = 'Email dan password wajib diisi!';
    header('Location: login.php');
    exit;
}

// Query cari akun
$query = "SELECT * FROM akun WHERE email = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    $_SESSION['error'] = 'Terjadi kesalahan sistem!';
    header('Location: login.php');
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Cek apakah email ditemukan
if ($user = mysqli_fetch_assoc($result)) {
    // Verifikasi password
    if (password_verify($password, $user['password'])) {
        // Simpan session dasar
        $_SESSION['user_id'] = $user['idAkun'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;


        // Ambil data tambahan berdasarkan role
        if ($user['role'] === 'guru') {
            $qGuru = $conn->prepare("SELECT nama, nip FROM dataguru WHERE idAkun = ? LIMIT 1");
            $qGuru->bind_param("s", $user['idAkun']);
            $qGuru->execute();
            $guru = $qGuru->get_result()->fetch_assoc();

            $_SESSION['nama'] = $guru['nama'] ?? $user['email'];
            $_SESSION['nip'] = $guru['nip'] ?? null;
            $redirect = '../Guru/dashboard.php';

        } elseif ($user['role'] === 'siswa') {
            $qSiswa = $conn->prepare("SELECT nama, NIS, kelas FROM datasiswa WHERE idAkun = ? LIMIT 1");
            $qSiswa->bind_param("s", $user['idAkun']);
            $qSiswa->execute();
            $siswa = $qSiswa->get_result()->fetch_assoc();

            $_SESSION['nama'] = $siswa['nama'] ?? $user['email'];
            $_SESSION['nis'] = $siswa['NIS'] ?? null;
            $_SESSION['kelas'] = $siswa['kelas'] ?? null;
            $redirect = '../Siswa/dashboard.php';

        } else { // admin
            $_SESSION['nama'] = 'Administrator';
            $redirect = '../Admin/dashboard.php';
        }


        if ($user['isPasswordChanged'] == 0) {
            header('Location: change-password.php');
            exit;
        }

        // Redirect ke dashboard
        header("Location: $redirect");
        exit;

    } else {
        $_SESSION['error'] = 'Email atau password salah!';
        header('Location: login.php');
        exit;
    }
} else {
    $_SESSION['error'] = 'Email tidak ditemukan!';
    header('Location: login.php');
    exit;
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>