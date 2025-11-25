<?php
//simpanNilai.php
session_start();

// ========== PROTEKSI LOGIN ==========
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'guru') {
    http_response_code(401);
    echo "❌ Unauthorized - Silakan login terlebih dahulu";
    exit;
}

include "../../config/db.php";

$idTugas = isset($_POST['idTugas']) ? mysqli_real_escape_string($conn, $_POST['idTugas']) : '';

if (empty($idTugas)) {
    echo "❌ ID Tugas tidak ditemukan";
    exit;
}

if (!isset($_POST['nilai']) || !is_array($_POST['nilai'])) {
    echo "❌ Data nilai tidak valid";
    exit;
}

// Update nilai untuk setiap siswa
$berhasil = 0;
$gagal = 0;

foreach($_POST['nilai'] as $nis => $nilai) {
    // Escape input untuk keamanan
    $nis = mysqli_real_escape_string($conn, $nis);
    $nilai = mysqli_real_escape_string($conn, $nilai);
    
    // Validasi nilai (0-100)
    if (!is_numeric($nilai) || $nilai < 0 || $nilai > 100) {
        continue;
    }
    
    $update = mysqli_query($conn, "
        UPDATE pengumpulantugas 
        SET nilai='$nilai', status='selesai'
        WHERE idTugas='$idTugas' AND NIS='$nis'
    ");
    
    if ($update && mysqli_affected_rows($conn) > 0) {
        $berhasil++;
    } else {
        $gagal++;
    }
}

if ($berhasil > 0) {
    echo "✅ Berhasil menyimpan $berhasil nilai!";
    if ($gagal > 0) {
        echo " ($gagal nilai tidak berubah)";
    }
} else {
    echo "⚠️ Tidak ada nilai yang diupdate";
}
?>