<?php
// backend/get-all-siswa.php
require_once "../config/db.php";

// pastikan header JSON
header('Content-Type: application/json; charset=utf-8');

$sql = "SELECT NIS, nama, kelas, jurusan FROM dataSiswa ORDER BY kelas, nama";
$result = $conn->query($sql);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);
exit;
