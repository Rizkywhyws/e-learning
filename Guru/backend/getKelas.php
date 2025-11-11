<?php
//file getKelas.php
include "../../config/db.php";
session_start();

$idAkun = $_SESSION['idAkun'];
$queryGuru = mysqli_query($conn, "SELECT NIP FROM dataguru WHERE idAkun = '$idAkun'");
$nipGuru = mysqli_fetch_assoc($queryGuru)['NIP'];

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
