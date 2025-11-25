<?php
//file getDetailTugas.php
session_start();

// Cek login
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'guru') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
include "../../config/db.php";


$idTugas = isset($_GET['idTugas']) ? $_GET['idTugas'] : '';

if (!$idTugas) {
    echo json_encode(['error' => 'idTugas kosong']);
    exit;
}

// Escape untuk keamanan
$idTugas = mysqli_real_escape_string($conn, $idTugas);

// Query diperbaiki - ambil kelas dari jadwalmapel (bisa multiple kelas)
$q = mysqli_query($conn, "
    SELECT 
        t.idTugas, 
        t.kodeMapel, 
        m.namaMapel, 
        t.judul, 
        t.deskripsi, 
        t.deadline, 
        t.filePath,
        GROUP_CONCAT(DISTINCT jm.kelas ORDER BY jm.kelas SEPARATOR ', ') as kelas
    FROM tugas t
    JOIN mapel m ON t.kodeMapel = m.kodeMapel
    LEFT JOIN jadwalmapel jm ON jm.kodeMapel = t.kodeMapel AND jm.nipGuru = t.NIP
    WHERE t.idTugas = '$idTugas'
    GROUP BY t.idTugas
    LIMIT 1
");

if (!$q) {
    echo json_encode(['error' => 'Query error: ' . mysqli_error($conn)]);
    exit;
}

if (mysqli_num_rows($q) > 0) {
    $data = mysqli_fetch_assoc($q);
    echo json_encode($data);
} else {
    echo json_encode(['error' => 'Data tidak ditemukan untuk ID: ' . $idTugas]);
}
?>