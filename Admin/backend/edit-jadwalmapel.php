<?php
require_once "../../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idJadwal = $_POST['idJadwal'];
    $kodeMapel = $_POST['kodeMapel'];
    $nipGuru = $_POST['nipGuru'];
    $hari = $_POST['hari'];
    $jamMulai = $_POST['jamMulai'];
    $durasi = $_POST['durasi'];
    $ruangan = $_POST['ruangan'];
    $kelas = $_POST['kelas'];

    $sql = "UPDATE jadwalmapel 
            SET kodeMapel=?, nipGuru=?, hari=?, jamMulai=?, durasi=?, ruangan=?, kelas=?
            WHERE idJadwal=?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sississs", $kodeMapel, $nipGuru, $hari, $jamMulai, $durasi, $ruangan, $kelas, $idJadwal);

    if ($stmt->execute()) {
        header("Location: ../kelolajadwal.php?success=edit");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
