<?php
// file: getDetailTugas.php

session_start();

// Cek login
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'guru') {
    http_response_code(401);
    echo json_encode(array('error' => 'Unauthorized'));
    exit;
}

header('Content-Type: application/json');

require_once "../../config/db.php";

// Validasi idTugas
$idTugas = isset($_GET['idTugas']) ? $_GET['idTugas'] : '';

if (empty($idTugas)) {
    echo json_encode(array('error' => 'idTugas kosong'));
    exit;
}

// ✅ UPDATE QUERY: Ambil kelas langsung dari tabel tugas
$sql = "
    SELECT 
        t.idTugas, 
        t.kodeMapel, 
        t.kelas,
        m.namaMapel, 
        t.judul, 
        t.deskripsi, 
        t.deadline, 
        t.filePath
    FROM tugas t
    JOIN mapel m ON t.kodeMapel = m.kodeMapel
    WHERE t.idTugas = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(array('error' => 'Prepare error: ' . $conn->error));
    exit;
}

$stmt->bind_param("s", $idTugas);
$stmt->execute();

$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(array('error' => 'Data tidak ditemukan untuk ID: ' . $idTugas));
    exit;
}

$data = $res->fetch_assoc();

// Kirim hasil
echo json_encode($data);

// Cleanup
$stmt->close();
$conn->close();
exit;
?>