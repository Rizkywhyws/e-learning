<?php
include "../../config/db.php";
session_start();

$idTugas = $_POST['idTugas'];

foreach($_POST['nilai'] as $nis => $nilai){
    mysqli_query($conn, "
        UPDATE pengumpulantugas 
        SET nilai='$nilai', status='selesai'
        WHERE idTugas='$idTugas' AND idSiswa='$nis'
    ");
}
echo "✅ Nilai berhasil disimpan!";
?>
