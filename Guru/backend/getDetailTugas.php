<?php
// file: getDetailTugas.php

session_start();

// Cek login
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'guru') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

require_once "../../config/db.php";

// Validasi idTugas
$idTugas = $_GET['idTugas'] ?? '';

if (empty($idTugas)) {
    echo json_encode(['error' => 'idTugas kosong']);
    exit;
}

// Gunakan prepared statement untuk keamanan
$sql = "
    SELECT 
        t.idTugas, 
        t.kodeMapel, 
        m.namaMapel, 
        t.judul, 
        t.deskripsi, 
        t.deadline, 
        t.filePath,
        GROUP_CONCAT(DISTINCT jm.kelas ORDER BY jm.kelas SEPARATOR ', ') AS kelas
    FROM tugas t
    JOIN mapel m ON t.kodeMapel = m.kodeMapel
    LEFT JOIN jadwalmapel jm 
        ON jm.kodeMapel = t.kodeMapel 
        AND jm.nipGuru = t.NIP
    WHERE t.idTugas = ?
    GROUP BY t.idTugas
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['error' => 'Prepare error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("s", $idTugas);
$stmt->execute();

$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['error' => 'Data tidak ditemukan untuk ID: ' . $idTugas]);
    exit;
}

$data = $res->fetch_assoc();

// Kirim hasil
echo json_encode($data);

// Cleanup
$stmt->close();
$conn->close();
?>
