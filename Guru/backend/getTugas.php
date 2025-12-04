<?php
// file: getTugas.php
session_start();

// ⚠️ SET HEADER JSON DI AWAL
header('Content-Type: application/json');

include "../../config/db.php";

// ========== CEK LOGIN ==========
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'guru') {
    http_response_code(401);
    echo json_encode(array('error' => 'Unauthorized'));
    exit;
}

// ========== AMBIL NIP GURU ==========
$nipGuru = isset($_SESSION['nip']) ? $_SESSION['nip'] : null;

if (!$nipGuru) {
    // fallback ambil ke DB
    $idAkun = $_SESSION['user_id'];
    $qGuru = mysqli_query($conn, "SELECT NIP FROM dataguru WHERE idAkun='$idAkun'");
    
    if (!$qGuru) {
        echo json_encode(array('error' => 'Query guru error: ' . mysqli_error($conn)));
        exit;
    }
    
    $guru = mysqli_fetch_assoc($qGuru);
    $nipGuru = isset($guru['NIP']) ? $guru['NIP'] : null;
}

if (!$nipGuru) {
    echo json_encode(array('error' => 'NIP Guru tidak ditemukan'));
    exit;
}

// ========== AMBIL PARAMETER ==========
$kodeMapel = isset($_GET['kodeMapel']) ? $_GET['kodeMapel'] : '';
$kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';

if (!$kodeMapel) {
    echo json_encode(array('error' => 'kodeMapel tidak boleh kosong'));
    exit;
}

if (!$kelas) {
    echo json_encode(array('error' => 'kelas tidak boleh kosong'));
    exit;
}

// Escape
$kodeMapel = mysqli_real_escape_string($conn, $kodeMapel);
$kelas = mysqli_real_escape_string($conn, $kelas);

// ========== QUERY DATA TUGAS (DENGAN FILTER KELAS) ==========
$q = mysqli_query($conn, "
    SELECT idTugas, judul, filePath 
    FROM tugas 
    WHERE NIP = '$nipGuru' 
      AND kodeMapel = '$kodeMapel'
      AND kelas = '$kelas'
    ORDER BY createdAt DESC
");

if (!$q) {
    echo json_encode(array('error' => 'Query error: ' . mysqli_error($conn)));
    exit;
}

// ========== SUSUN DATA ==========
$data = array();

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
exit;
?>