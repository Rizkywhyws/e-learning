<?php
require_once "../../config/db.php";

// opsional: aktifkan laporan error saat debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ambil input (sanitasi ringan)
    $idJadwal     = trim($_POST['idJadwal']);     // ini berasal dari <input name="idJadwal">
    $kodeMapel    = trim($_POST['kodeMapel']);
    $nipGuru      = isset($_POST['nipGuru']) ? (int)$_POST['nipGuru'] : null;
    $hari         = trim($_POST['hari']);
    $jamMulai     = trim($_POST['jamMulai']);
    $durasi       = isset($_POST['durasi']) ? (int)$_POST['durasi'] : null;
    $ruangan      = trim($_POST['ruangan']);
    $kelas        = trim($_POST['kelas']);

    // validasi singkat
    if (empty($idJadwal) || empty($kodeMapel) || empty($hari) || empty($jamMulai) || empty($durasi) || empty($kelas)) {
        echo "Data tidak lengkap.";
        exit;
    }

    // Query: perhatikan nama kolom primary key di DB = idJadwalMapel
    $sql = "UPDATE jadwalmapel 
            SET kodeMapel = ?, nipGuru = ?, hari = ?, jamMulai = ?, durasi = ?, ruangan = ?, kelas = ?
            WHERE idJadwalMapel = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "Prepare failed: " . $conn->error;
        exit;
    }

    // Tipe: kodeMapel (s), nipGuru (i), hari (s), jamMulai (s), durasi (i), ruangan (s), kelas (s), idJadwal (s)
    $types = "sississs";
    $stmt->bind_param($types,
        $kodeMapel,
        $nipGuru,
        $hari,
        $jamMulai,
        $durasi,
        $ruangan,
        $kelas,
        $idJadwal
    );

    if ($stmt->execute()) {
        // sukses
        header("Location: ../kelolajadwal.php?success=edit");
        exit;
    } else {
        echo "Execute failed: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: ../kelolajadwal.php");
    exit;
}
?>
