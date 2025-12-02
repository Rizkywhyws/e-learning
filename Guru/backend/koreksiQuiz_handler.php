<?php
// file e-learningMrt/Guru/backend/koreksiQuiz_handler.php
session_start();
header('Content-Type: application/json');
include('../../config/db.php');

// Proteksi
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'guru') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

function jsonResponse($data) {
    echo json_encode($data);
    exit;
}

// ============= GET MAPEL =============
if ($action == 'getMapel') {
    $idAkun    = isset($_GET['idAkun'])    ? $_GET['idAkun']    : '';
    
    // Ambil NIP guru dari idAkun
    $queryGuru = mysqli_query($conn, "SELECT NIP FROM dataguru WHERE idAkun = '$idAkun'");
    
    if (!$queryGuru || mysqli_num_rows($queryGuru) == 0) {
        jsonResponse(['success' => false, 'message' => 'Data guru tidak ditemukan']);
    }
    
    $guru = mysqli_fetch_assoc($queryGuru);
    $nipGuru = $guru['NIP'];
    
    // Ambil mapel yang diampu guru
    $query = mysqli_query($conn, "
        SELECT DISTINCT m.kodeMapel, m.namaMapel 
        FROM gurumapel gm
        JOIN mapel m ON gm.kodeMapel = m.kodeMapel
        WHERE gm.nipGuru = '$nipGuru'
        ORDER BY m.namaMapel ASC
    ");
    
    $data = [];
    while ($row = mysqli_fetch_assoc($query)) {
        $data[] = $row;
    }
    
    jsonResponse(['success' => true, 'data' => $data]);
}

// ============= GET KELAS =============
if ($action == 'getKelas') {
    $idAkun    = isset($_GET['idAkun'])    ? $_GET['idAkun']    : '';
    $kodeMapel = isset($_GET['kodeMapel']) ? $_GET['kodeMapel'] : '';
    
    // Ambil NIP guru
    $queryGuru = mysqli_query($conn, "SELECT NIP FROM dataguru WHERE idAkun = '$idAkun'");
    $guru = mysqli_fetch_assoc($queryGuru);
    $nipGuru = $guru['NIP'];
    
    // Ambil kelas yang diampu guru untuk mapel tertentu
    $query = mysqli_query($conn, "
        SELECT DISTINCT j.Kelas as kelas
        FROM jadwalmapel j
        WHERE j.nipGuru = '$nipGuru' 
        AND j.kodeMapel = '$kodeMapel'
        ORDER BY j.Kelas ASC
    ");
    
    $data = [];
    while ($row = mysqli_fetch_assoc($query)) {
        $data[] = $row;
    }
    
    jsonResponse(['success' => true, 'data' => $data]);
}

// ============= GET QUIZ (Hanya esai) =============
if ($action == 'getQuiz') {
    $kodeMapel = isset($_GET['kodeMapel']) ? $_GET['kodeMapel'] : '';
    $kelas     = isset($_GET['kelas'])     ? $_GET['kelas']     : '';
    
    // Ambil quiz dengan type esai saja
    $query = mysqli_query($conn, "
        SELECT idQuiz, judul, deskripsi, kelas, waktuMulai, waktuSelesai
        FROM quiz
        WHERE kodeMapel = '$kodeMapel' 
        AND kelas = '$kelas'
        AND type = 'esai'
        ORDER BY waktuMulai DESC
    ");
    
    $data = [];
    while ($row = mysqli_fetch_assoc($query)) {
        $data[] = $row;
    }
    
    jsonResponse(['success' => true, 'data' => $data]);
}

// ============= GET DATA SISWA & JAWABAN =============
if ($action == 'getDataSiswa') {
    $idQuiz    = isset($_GET['idQuiz'])    ? $_GET['idQuiz']    : '';
    $kelas     = isset($_GET['kelas'])     ? $_GET['kelas']     : '';
    
    // Ambil info quiz
    $queryQuiz = mysqli_query($conn, "
        SELECT judul, deskripsi, 
               DATE_FORMAT(waktuMulai, '%d %M %Y %H:%i') as waktuMulai,
               DATE_FORMAT(waktuSelesai, '%d %M %Y %H:%i') as waktuSelesai
        FROM quiz
        WHERE idQuiz = '$idQuiz'
    ");
    
    if (!$queryQuiz || mysqli_num_rows($queryQuiz) == 0) {
        jsonResponse(['success' => false, 'message' => 'Quiz tidak ditemukan']);
    }
    
    $quizInfo = mysqli_fetch_assoc($queryQuiz);
    
    // Ambil semua siswa di kelas tersebut
    $querySiswa = mysqli_query($conn, "
        SELECT NIS, nama
        FROM datasiswa
        WHERE kelas = '$kelas'
        ORDER BY nama ASC
    ");
    
    $siswaList = [];
    while ($siswa = mysqli_fetch_assoc($querySiswa)) {
        $nis = $siswa['NIS'];
        
        // Cek apakah siswa sudah mengerjakan
        $queryJawaban = mysqli_query($conn, "
            SELECT nilai
            FROM jawabanquiz
            WHERE idQuiz = '$idQuiz' AND NIS = '$nis'
            LIMIT 1
        ");
        
        $sudahMengerjakan = mysqli_num_rows($queryJawaban) > 0;
        $nilai = null;
        
        if ($sudahMengerjakan) {
            $jawaban = mysqli_fetch_assoc($queryJawaban);
            $nilai = $jawaban['nilai'];
        }
        
        $siswaList[] = [
            'NIS' => $nis,
            'nama' => $siswa['nama'],
            'sudahMengerjakan' => $sudahMengerjakan,
            'nilai' => $nilai
        ];
    }
    
    jsonResponse([
        'success' => true,
        'quiz' => $quizInfo,
        'siswa' => $siswaList
    ]);
}

// Jika action tidak dikenali
jsonResponse(['success' => false, 'message' => 'Invalid action']);
?>