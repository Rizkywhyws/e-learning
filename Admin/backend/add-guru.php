<?php
require_once "../../config/db.php";

// Ambil data
$nama = $_POST['nama'];
$nip = $_POST['nip'];
$email = $_POST['email'];
$noTelp = $_POST['noTelp'];
$password = $_POST['password'];

// Generate ID akun
$idAkun = "GR" . rand(10000, 99999);

// Jika password kosong -> pakai default (opsional)
if (empty($password)) {
    $password = "guru123"; 
}

$hash = password_hash($password, PASSWORD_BCRYPT);

// -----------------------------
// INSERT TABEL AKUN
// -----------------------------
$q1 = $conn->prepare("
    INSERT INTO akun (idAkun, role, email, password, isPasswordChanged)
    VALUES (?, 'guru', ?, ?, 0)
");
$q1->bind_param("sss", $idAkun, $email, $hash);

if (!$q1->execute()) {
    die("Error akun: " . $q1->error);
}

// -----------------------------
// INSERT TABEL GURU
// -----------------------------
$q2 = $conn->prepare("
    INSERT INTO dataGuru (NIP, nama, noTelp, idAkun)
    VALUES (?, ?, ?, ?)
");

// ✅ semua VARCHAR → pakai "ssss"
$q2->bind_param("isss", $nip, $nama, $noTelp, $idAkun);

if (!$q2->execute()) {
    die("Error guru: " . $q2->error);
}

// Redirect
header("Location: ../kelolaguru.php?status=added");
exit;
?>
