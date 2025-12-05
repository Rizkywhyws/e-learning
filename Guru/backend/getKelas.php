<?php
// file: getKelas.php
session_start();

// Set header JSON di awal
header('Content-Type: application/json');

// Cek login
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'guru') {
    http_response_code(401);
    echo json_encode(array('error' => 'Unauthorized'));
    exit;
}

include "../../config/db.php";

$nipGuru = isset($_SESSION['nip']) ? $_SESSION['nip'] : '';

// Jika NIP tidak ada di session, ambil dari database
if (empty($nipGuru)) {
    $idAkun = $_SESSION['user_id'];
    $queryGuru = mysqli_query($conn, "SELECT NIP FROM dataguru WHERE idAkun = '$idAkun'");
    
    if (!$queryGuru) {
        echo json_encode(array('error' => 'Query error: ' . mysqli_error($conn)));
        exit;
    }
    
    $dataGuru = mysqli_fetch_assoc($queryGuru);
    $nipGuru = isset($dataGuru['NIP']) ? $dataGuru['NIP'] : '';
}

// Validasi kodeMapel
if (!isset($_GET['kodeMapel']) || empty($_GET['kodeMapel'])) {
    echo json_encode(array('error' => 'kodeMapel tidak boleh kosong'));
    exit;
}

$kodeMapel = mysqli_real_escape_string($conn, $_GET['kodeMapel']);

$result = mysqli_query($conn, "
    SELECT DISTINCT kelas 
    FROM jadwalmapel 
    WHERE kodeMapel = '$kodeMapel' AND nipGuru = '$nipGuru'
");

if (!$result) {
    echo json_encode(array('error' => 'Query error: ' . mysqli_error($conn)));
    exit;
}

$data = array();
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = array('kelas' => $row['kelas']);
}

echo json_encode($data);
exit;
?>
