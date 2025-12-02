<?php
require_once "../../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Buat ID unik dengan entropi tinggi
    $idJadwal = 'JDW' . strtoupper(bin2hex(random_bytes(4))); 
    // Contoh hasil: JDW8F3A9B2C

    // Ambil data dari form
    $kodeMapel = $_POST['kodeMapel'];
    $nipGuru   = $_POST['nipGuru'];
    $hari      = $_POST['hari'];
    $jamMulai  = $_POST['jamMulai'];
    $durasi    = $_POST['durasi'];
    $ruangan   = $_POST['ruangan'];
    $kelas     = $_POST['kelas'];

    // Query insert
    $sql = "INSERT INTO jadwalmapel 
            (idJadwalMapel, kodeMapel, nipGuru, hari, jamMulai, durasi, ruangan, kelas)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssississ", 
        $idJadwal, 
        $kodeMapel, 
        $nipGuru, 
        $hari, 
        $jamMulai, 
        $durasi, 
        $ruangan, 
        $kelas
    );

    if ($stmt->execute()) {
        header("Location: ../kelolajadwal.php?success=add");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
