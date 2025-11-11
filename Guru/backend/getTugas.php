<?php
include "../../config/db.php";
session_start();

$idAkun = $_SESSION['idAkun'];
$qGuru = mysqli_query($conn, "SELECT NIP FROM dataguru WHERE idAkun='$idAkun'");
$nipGuru = mysqli_fetch_assoc($qGuru)['NIP'];

$kodeMapel = $_GET['kodeMapel'];
$kelas = $_GET['kelas'];

$q = mysqli_query($conn, "
    SELECT idTugas, judul FROM tugas 
    WHERE NIP='$nipGuru' AND kodeMapel='$kodeMapel'
");
$data = [];
while($r = mysqli_fetch_assoc($q)) $data[] = $r;
echo json_encode($data);
?>
