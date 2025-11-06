<?php
require_once "../../config/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../kelolasiswa.php');
    exit;
}

$old_nis  = $_POST['old_nis'];  
$nis      = $_POST['nis'];
$nisn     = $_POST['nisn'];
$nama     = $_POST['nama'];
$kelas    = $_POST['kelas'];
$jurusan  = $_POST['jurusan'];
$email    = $_POST['email'];
$password = $_POST['password'];

// ✅ Ambil idAkun lama
$q = $conn->prepare("SELECT idAkun FROM dataSiswa WHERE NIS = ?");
$q->bind_param("i", $old_nis);
$q->execute();
$res = $q->get_result();
$row = $res->fetch_assoc();
$idAkun = $row['idAkun'] ?? null;

$conn->begin_transaction();

try {

    // ✅ Update tabel dataSiswa
    $u1 = $conn->prepare("
        UPDATE dataSiswa 
        SET NIS = ?, NISN = ?, nama = ?, kelas = ?, jurusan = ? 
        WHERE NIS = ?
    ");
    $u1->bind_param("issssi", $nis, $nisn, $nama, $kelas, $jurusan, $old_nis);
    $u1->execute();

    // ✅ Update tabel akun
    if ($idAkun) {

        // Update email
        $u2 = $conn->prepare("UPDATE akun SET email = ? WHERE idAkun = ?");
        $u2->bind_param("ss", $email, $idAkun);
        $u2->execute();

        // Update password apabila diisi
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_BCRYPT);

            $u3 = $conn->prepare("UPDATE akun SET password = ? WHERE idAkun = ?");
            $u3->bind_param("ss", $hash, $idAkun);
            $u3->execute();
        }
    }

    $conn->commit();
    header("Location: ../kelolasiswa.php?status=updated");

} catch (Exception $e) {
    $conn->rollback();
    header("Location: ../kelolasiswa.php?status=error");
}

exit;
?>
