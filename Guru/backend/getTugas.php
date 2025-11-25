<?php
//getTugas.php
session_start();
include "../../config/session.php";
include "../../config/db.php";

// Cek login
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'guru') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}


$nipGuru = isset($_SESSION['nip']) ? $_SESSION['nip'] : '';

// Jika NIP tidak ada di session, ambil dari database
if (empty($nipGuru)) {
    $idAkun = $_SESSION['user_id'];
    $qGuru = mysqli_query($conn, "SELECT NIP FROM dataguru WHERE idAkun='$idAkun'");
    $dataGuru = mysqli_fetch_assoc($qGuru);
    $nipGuru = isset($dataGuru['NIP']) ? $dataGuru['NIP'] : '';
}

$kodeMapel = $_GET['kodeMapel'];
$kelas = $_GET['kelas'];

$q = mysqli_query($conn, "
    SELECT idTugas, judul, filePath 
    FROM tugas 
    WHERE NIP='$nipGuru' AND kodeMapel='$kodeMapel'
");

$data = [];

while($r = mysqli_fetch_assoc($q)) {

    // Buat absolute URL agar tidak jadi Guru/Guru atau Siswa/Siswa
    $r['filePath'] = "http://localhost/elearning-app/" . $r['filePath'];

    $data[] = $r;
}

echo json_encode($data);
?>
