<?php
// file: getTugas.php
session_start();
include "../../config/db.php";

// ========== CEK LOGIN ==========
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'guru') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ========== AMBIL NIP GURU ==========
$nipGuru = $_SESSION['nip'] ?? null;

if (!$nipGuru) {
    // fallback ambil ke DB
    $idAkun = $_SESSION['user_id'];
    $qGuru = mysqli_query($conn, "SELECT NIP FROM dataguru WHERE idAkun='$idAkun'");
    $guru = mysqli_fetch_assoc($qGuru);
    $nipGuru = $guru['NIP'] ?? null;
}

if (!$nipGuru) {
    echo json_encode(['error' => 'NIP Guru tidak ditemukan']);
    exit;
}

// ========== AMBIL PARAMETER ==========
$kodeMapel = $_GET['kodeMapel'] ?? '';
$kelas      = $_GET['kelas'] ?? '';

if (!$kodeMapel) {
    echo json_encode(['error' => 'kodeMapel tidak boleh kosong']);
    exit;
}

// Escape
$kodeMapel = mysqli_real_escape_string($conn, $kodeMapel);
$kelas = mysqli_real_escape_string($conn, $kelas);

// ========== QUERY DATA TUGAS ==========
$q = mysqli_query($conn, "
    SELECT idTugas, judul, filePath 
    FROM tugas 
    WHERE NIP = '$nipGuru' 
      AND kodeMapel = '$kodeMapel'
");

// ========== SUSUN DATA ==========
$data = [];

while ($r = mysqli_fetch_assoc($q)) {

    // Pastikan filePath memakai absolute URL  
    if (!empty($r['filePath'])) {
        $r['filePath'] = "http://localhost/elearning-app/" . ltrim($r['filePath'], '/');
    } else {
        $r['filePath'] = null;
    }

    $data[] = $r;
}

echo json_encode($data);
?>
