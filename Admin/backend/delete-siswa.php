<?php
require_once "../../config/db.php";

if (!isset($_GET['nis'])) {
    header("Location: ../kelolasiswa.php?status=invalid");
    exit;
}

$nis = intval($_GET['nis']);

// Ambil idAkun terlebih dahulu
$q = $conn->prepare("SELECT idAkun FROM dataSiswa WHERE NIS = ?");
$q->bind_param("i", $nis);
$q->execute();
$res = $q->get_result();
$row = $res->fetch_assoc();

$idAkun = $row['idAkun'] ?? null;

$conn->begin_transaction();

try {

    // Hapus akun
    if ($idAkun) {
        $delAkun = $conn->prepare("DELETE FROM akun WHERE idAkun = ?");
        $delAkun->bind_param("s", $idAkun);
        $delAkun->execute();
    }

    // Hapus siswa
    $delSiswa = $conn->prepare("DELETE FROM dataSiswa WHERE NIS = ?");
    $delSiswa->bind_param("i", $nis);
    $delSiswa->execute();

    $conn->commit();

    header("Location: ../kelolasiswa.php?status=deleted");
    exit;

} catch (Exception $e) {

    $conn->rollback();
    header("Location: ../kelolasiswa.php?status=error");
    exit;
}
?>
