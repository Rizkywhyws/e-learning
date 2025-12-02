<?php
// backend/naik-kelas.php

// Matikan error display, paksa JSON output
ini_set('display_errors', 0);
error_reporting(0);

// PENTING: Header JSON harus paling atas
header('Content-Type: application/json; charset=utf-8');

// Coba koneksi database
try {
    require_once "../../config/db.php";
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
    exit;
}

// Cek koneksi
if (!isset($conn) || !$conn) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal']);
    exit;
}

// Ambil input JSON
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);

// Validasi input
if (!$input || !isset($input['siswa']) || !isset($input['tujuan'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

$siswa = $input['siswa'];
$tujuan = $input['tujuan'];

// Validasi array siswa
if (!is_array($siswa) || count($siswa) === 0) {
    echo json_encode(['success' => false, 'message' => 'Data siswa tidak valid']);
    exit;
}

// Validasi kelas tujuan
$valid = ['X-1', 'X-2', 'XI-1', 'XI-2', 'XII-1', 'XII-2', 'LULUS'];
if (!in_array($tujuan, $valid)) {
    echo json_encode(['success' => false, 'message' => 'Kelas tidak valid: ' . $tujuan]);
    exit;
}

// Update database
$stmt = $conn->prepare("UPDATE dataSiswa SET kelas = ? WHERE NIS = ?");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare statement gagal']);
    exit;
}

$updated = 0;
$failed = 0;

foreach ($siswa as $nis) {
    $nis_clean = trim($nis);
    $stmt->bind_param('ss', $tujuan, $nis_clean);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $updated++;
        } else {
            $failed++;
        }
    } else {
        $failed++;
    }
}

$stmt->close();
$conn->close();

// Return JSON
echo json_encode([
    'success' => true,
    'message' => "Berhasil menaikkan $updated siswa ke kelas $tujuan!" . 
                 ($failed > 0 ? " ($failed gagal)" : "")
]);
exit;
?>