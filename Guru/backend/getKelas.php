<?php
//file getKelas.php
session_start();

// Cek login
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'guru') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include "../../config/db.php";

$nipGuru = isset($_SESSION['nip']) ? $_SESSION['nip'] : '';

// Jika NIP tidak ada di session, ambil dari database
if (empty($nipGuru)) {
    $idAkun = $_SESSION['user_id'];
    $queryGuru = mysqli_query($conn, "SELECT NIP FROM dataguru WHERE idAkun = '$idAkun'");
    $dataGuru = mysqli_fetch_assoc($queryGuru);
    $nipGuru = isset($dataGuru['NIP']) ? $dataGuru['NIP'] : '';
}


$kodeMapel = $_GET['kodeMapel'];
$result = mysqli_query($conn, "
    SELECT DISTINCT kelas 
    FROM jadwalmapel 
    WHERE kodeMapel = '$kodeMapel' AND nipGuru = '$nipGuru'
");

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = ['kelas' => $row['kelas']];
}

echo json_encode($data);
