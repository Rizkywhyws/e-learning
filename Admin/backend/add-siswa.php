<?php
require_once "../../config/db.php";

// Ambil data dari form
$nama     = $_POST['nama'];
$nis      = $_POST['nis'];
$nisn     = $_POST['nisn'];
$jurusan  = $_POST['jurusan'];
$kelas    = $_POST['kelas'];
$email    = $_POST['email'];
$password = $_POST['password'];

// Generate ID akun siswa
$idAkun = "SW" . rand(10000, 99999);

// Jika password kosong → pakai default
if (empty($password)) {
    $password = "siswa123";
}

$hash = password_hash($password, PASSWORD_BCRYPT);

// --------------------------------------------
// INSERT KE TABEL AKUN
// --------------------------------------------
$q1 = $conn->prepare("
    INSERT INTO akun (idAkun, role, email, password, isPasswordChanged)
    VALUES (?, 'siswa', ?, ?, 0)
");
$q1->bind_param("sss", $idAkun, $email, $hash);

if (!$q1->execute()) {
    die("Error akun: " . $q1->error);
}

// --------------------------------------------
// INSERT KE TABEL dataSiswa
// --------------------------------------------
$q2 = $conn->prepare("
    INSERT INTO dataSiswa (NIS, NISN, nama, jurusan, kelas, idAkun)
    VALUES (?, ?, ?, ?, ?, ?)
");

// ✅ NIS & NISN integer, lainnya string
$q2->bind_param("iissss", $nis, $nisn, $nama, $jurusan, $kelas, $idAkun);

if (!$q2->execute()) {
    die("Error siswa: " . $q2->error);
}

// Redirect kembali
header("Location: ../kelolasiswa.php?status=added");
exit;

?>
