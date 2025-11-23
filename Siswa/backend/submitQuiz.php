<?php

// Start session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../../config/db.php');

// Cek login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die("Error: Sesi tidak valid. <a href='../ngerjakanQuiz.php'>Kembali</a>");
}

// Cek role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    die("Error: Akses ditolak! Hanya siswa yang bisa submit quiz.");
}

// Validasi data POST
if(!isset($_POST['idQuiz']) || !isset($_POST['NIS']) || !isset($_POST['jawaban'])) {
    die("Error: Data tidak lengkap! <a href='../ngerjakanQuiz.php'>Kembali</a>");
}

$idQuiz = mysqli_real_escape_string($conn, $_POST['idQuiz']);
$NIS = mysqli_real_escape_string($conn, $_POST['NIS']);
$waktuMulai = mysqli_real_escape_string($conn, $_POST['waktuMulai']);
$waktuSelesai = mysqli_real_escape_string($conn, $_POST['waktuSelesai']);
$jawaban = $_POST['jawaban']; // Array jawaban

// Validasi NIS dari data siswa di database
$queryValidasi = "SELECT NIS FROM datasiswa WHERE idAkun = ?";
$stmtValidasi = mysqli_prepare($conn, $queryValidasi);
mysqli_stmt_bind_param($stmtValidasi, "s", $_SESSION['user_id']);
mysqli_stmt_execute($stmtValidasi);
$resultValidasi = mysqli_stmt_get_result($stmtValidasi);
$dataValidasi = mysqli_fetch_assoc($resultValidasi);

if(!$dataValidasi || $NIS !== $dataValidasi['NIS']) {
    die("Error: Data tidak valid! <a href='../ngerjakanQuiz.php'>Kembali</a>");
}

// Cek apakah sudah pernah submit
$queryCheck = "SELECT COUNT(*) as sudah FROM jawabanquiz WHERE idQuiz = ? AND NIS = ?";
$stmtCheck = mysqli_prepare($conn, $queryCheck);
mysqli_stmt_bind_param($stmtCheck, "ss", $idQuiz, $NIS);
mysqli_stmt_execute($stmtCheck);
$resultCheck = mysqli_stmt_get_result($stmtCheck);
$dataCheck = mysqli_fetch_assoc($resultCheck);

if($dataCheck['sudah'] > 0) {
    // Sudah pernah submit, redirect ke pembahasan
    header('Location: ../pembahasanQuiz.php?idQuiz=' . $idQuiz);
    exit;
}

// Mulai transaction
mysqli_begin_transaction($conn);

try {
    $totalBenar = 0;
    $totalSoalPilgan = 0; // Hanya hitung soal pilgan dan multi untuk nilai
    $adaEsai = false;
    
    // Loop setiap jawaban dan simpan ke database
    foreach($jawaban as $idSoal => $jawabanSiswa) {
        $idSoal = mysqli_real_escape_string($conn, $idSoal);
        
        // Ambil data soal untuk cek tipe dan jawaban benar
        $querySoal = "SELECT type, jawabanMulti FROM soalquiz WHERE idSoal = ?";
        $stmtSoal = mysqli_prepare($conn, $querySoal);
        mysqli_stmt_bind_param($stmtSoal, "s", $idSoal);
        mysqli_stmt_execute($stmtSoal);
        $resultSoal = mysqli_stmt_get_result($stmtSoal);
        
        if($resultSoal && mysqli_num_rows($resultSoal) > 0) {
            $dataSoal = mysqli_fetch_assoc($resultSoal);
            $tipeSoal = $dataSoal['type'];
            
            // Inisialisasi variabel
            $jawabanPilgan = null;
            $jawabanEsai = null;
            $isBenar = false;
            
            // Proses berdasarkan tipe soal
            if($tipeSoal === 'Esai') {
                // Untuk esai, simpan ke jawabanEsai
                $jawabanEsai = mysqli_real_escape_string($conn, $jawabanSiswa);
                $adaEsai = true;
                // Esai tidak dihitung dalam nilai otomatis (guru yang menilai)
                
            } else {
                // Untuk Pilgan dan Multi, simpan ke jawabanPilgan
                $jawabanPilgan = mysqli_real_escape_string($conn, strtolower(trim($jawabanSiswa)));
                $jawabanBenar = strtolower(trim($dataSoal['jawabanMulti']));
                
                // Hitung apakah jawaban benar
                if($jawabanPilgan === $jawabanBenar) {
                    $isBenar = true;
                    $totalBenar++;
                }
                
                $totalSoalPilgan++;
            }
            
            // Insert ke jawabanquiz
            $queryInsert = "INSERT INTO jawabanquiz 
                           (idQuiz, idSoal, NIS, jawabanPilgan, jawabanEsai, waktuMulai, waktuSelesai) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmtInsert = mysqli_prepare($conn, $queryInsert);
            mysqli_stmt_bind_param($stmtInsert, "sssssss", 
                $idQuiz, 
                $idSoal, 
                $NIS, 
                $jawabanPilgan,
                $jawabanEsai,
                $waktuMulai,
                $waktuSelesai
            );
            
            if(!mysqli_stmt_execute($stmtInsert)) {
                throw new Exception("Gagal menyimpan jawaban: " . mysqli_error($conn));
            }
            
            mysqli_stmt_close($stmtInsert);
        }
        
        mysqli_stmt_close($stmtSoal);
    }
    
    // Hitung nilai (hanya untuk soal pilgan/multi)
    $nilai = 0;
    if($totalSoalPilgan > 0) {
        $nilai = round(($totalBenar / $totalSoalPilgan) * 100, 2);
    }
    
    // Jika ada soal esai, nilai masih menunggu koreksi guru
    $statusNilai = $adaEsai ? 'menunggu_koreksi' : 'selesai';
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Redirect ke halaman hasil dengan nilai
    $redirectUrl = '../hasilQuiz.php?idQuiz=' . $idQuiz . 
                   '&nilai=' . $nilai . 
                   '&benar=' . $totalBenar . 
                   '&total=' . $totalSoalPilgan;
    
    if($adaEsai) {
        $redirectUrl .= '&ada_esai=1';
    }
    
    header('Location: ' . $redirectUrl);
    exit;
    
} catch (Exception $e) {
    // Rollback jika ada error
    mysqli_rollback($conn);
    die("Error: " . $e->getMessage() . " <a href='../ngerjakanQuiz.php'>Kembali</a>");
}

?>