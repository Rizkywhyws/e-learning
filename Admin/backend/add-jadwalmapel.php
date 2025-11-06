<?php
require_once "../../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idJadwal = uniqid("JDW"); // generate id otomatis
    $kodeMapel = $_POST['kodeMapel'];
    $nipGuru = $_POST['nipGuru'];
    $hari = $_POST['hari'];
    $jamMulai = $_POST['jamMulai'];
    $durasi = $_POST['durasi'];
    $ruangan = $_POST['ruangan'];
    $kelas = $_POST['kelas'];

    $sql = "INSERT INTO jadwalmapel 
            (idJadwal, kodeMapel, nipGuru, hari, jamMulai, durasi, ruangan, kelas)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssississ", $idJadwal, $kodeMapel, $nipGuru, $hari, $jamMulai, $durasi, $ruangan, $kelas);

    if ($stmt->execute()) {
        header("Location: ../kelolajadwal.php?success=add");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
