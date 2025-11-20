<?php
include "../../config/db.php";
session_start();

$idAkun = $_SESSION['idAkun'];
$qGuru = mysqli_query($conn, "SELECT NIP FROM dataguru WHERE idAkun='$idAkun'");
$nipGuru = mysqli_fetch_assoc($qGuru)['NIP'];

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
