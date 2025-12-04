<?php
// file e-learningMrt/Guru/backend/koreksiQuiz_handler.php
session_start();
header('Content-Type: application/json');
include('../../config/db.php');

// Proteksi
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'guru') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Silakan login sebagai guru']);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

function jsonResponse($data) {
    echo json_encode($data);
    exit;
}

// ============= GET MAPEL =============
if ($action == 'getMapel') {
    $idAkun = isset($_GET['idAkun']) ? mysqli_real_escape_string($conn, $_GET['idAkun']) : '';
    
    // DEBUG LOG
    error_log("=== DEBUG getMapel ===");
    error_log("idAkun dari GET: " . $idAkun);
    
    // Validasi idAkun
    if (empty($idAkun)) {
        error_log("ERROR: idAkun kosong");
        jsonResponse(['success' => false, 'message' => 'Parameter idAkun tidak ditemukan']);
    }
    
    // Ambil NIP guru dari idAkun
    $queryGuru = mysqli_query($conn, "SELECT NIP FROM dataguru WHERE idAkun = '$idAkun'");
    
    if (!$queryGuru) {
        error_log("ERROR Query dataguru: " . mysqli_error($conn));
        jsonResponse(['success' => false, 'message' => 'Query error: ' . mysqli_error($conn)]);
    }
    
    if (mysqli_num_rows($queryGuru) == 0) {
        error_log("ERROR: Data guru tidak ditemukan untuk idAkun: " . $idAkun);
        
        // Cek apakah idAkun memang ada di tabel akun
        $cekAkun = mysqli_query($conn, "SELECT * FROM akun WHERE idAkun = '$idAkun'");
        if (mysqli_num_rows($cekAkun) == 0) {
            error_log("ERROR: idAkun tidak ada di tabel akun");
            jsonResponse(['success' => false, 'message' => 'Session tidak valid. Silakan login ulang.']);
        } else {
            error_log("ERROR: idAkun ada di tabel akun tapi tidak ada di dataguru");
            jsonResponse(['success' => false, 'message' => 'Data guru belum terdaftar. Hubungi admin.']);
        }
    }
    
    $guru = mysqli_fetch_assoc($queryGuru);
    $nipGuru = $guru['NIP'];
    
    error_log("NIP Guru ditemukan: " . $nipGuru);
    
    // Ambil mapel yang diampu guru
    $queryMapel = mysqli_query($conn, "
        SELECT DISTINCT m.kodeMapel, m.namaMapel 
        FROM gurumapel gm
        JOIN mapel m ON gm.kodeMapel = m.kodeMapel
        WHERE gm.nipGuru = '$nipGuru'
        ORDER BY m.namaMapel ASC
    ");
    
    if (!$queryMapel) {
        error_log("ERROR Query gurumapel: " . mysqli_error($conn));
        jsonResponse(['success' => false, 'message' => 'Query mapel error: ' . mysqli_error($conn)]);
    }
    
    $data = [];
    while ($row = mysqli_fetch_assoc($queryMapel)) {
        $data[] = $row;
    }
    
    error_log("Jumlah mapel ditemukan: " . count($data));
    
    if (count($data) == 0) {
        error_log("WARNING: Tidak ada mapel untuk NIP: " . $nipGuru);
        
        // Cek apakah ada data di gurumapel
        $cekGM = mysqli_query($conn, "SELECT * FROM gurumapel WHERE nipGuru = '$nipGuru'");
        error_log("Jumlah record di gurumapel: " . mysqli_num_rows($cekGM));
        
        jsonResponse(['success' => false, 'message' => 'Belum ada mata pelajaran yang Anda ampu. Hubungi admin untuk penugasan mapel.']);
    }
    
    jsonResponse([
        'success' => true, 
        'data' => $data,
        'debug' => [
            'idAkun' => $idAkun,
            'nipGuru' => $nipGuru,
            'jumlahMapel' => count($data)
        ]
    ]);
}

// ============= GET KELAS =============
if ($action == 'getKelas') {
    $idAkun    = isset($_GET['idAkun'])    ? mysqli_real_escape_string($conn, $_GET['idAkun'])    : '';
    $kodeMapel = isset($_GET['kodeMapel']) ? mysqli_real_escape_string($conn, $_GET['kodeMapel']) : '';
    
    error_log("=== DEBUG getKelas ===");
    error_log("idAkun: $idAkun, kodeMapel: $kodeMapel");
    
    if (empty($idAkun) || empty($kodeMapel)) {
        jsonResponse(['success' => false, 'message' => 'Parameter tidak lengkap']);
    }
    
    // Ambil NIP guru
    $queryGuru = mysqli_query($conn, "SELECT NIP FROM dataguru WHERE idAkun = '$idAkun'");
    
    if (!$queryGuru || mysqli_num_rows($queryGuru) == 0) {
        jsonResponse(['success' => false, 'message' => 'Data guru tidak ditemukan']);
    }
    
    $guru = mysqli_fetch_assoc($queryGuru);
    $nipGuru = $guru['NIP'];
    
    error_log("NIP Guru: $nipGuru");
    
    // Ambil kelas yang diampu guru untuk mapel tertentu
    $queryKelas = mysqli_query($conn, "
        SELECT DISTINCT j.Kelas as kelas
        FROM jadwalmapel j
        WHERE j.nipGuru = '$nipGuru' 
        AND j.kodeMapel = '$kodeMapel'
        ORDER BY j.Kelas ASC
    ");
    
    if (!$queryKelas) {
        error_log("ERROR Query kelas: " . mysqli_error($conn));
        jsonResponse(['success' => false, 'message' => 'Query error: ' . mysqli_error($conn)]);
    }
    
    $data = [];
    while ($row = mysqli_fetch_assoc($queryKelas)) {
        $data[] = $row;
    }
    
    error_log("Jumlah kelas: " . count($data));
    
    if (count($data) == 0) {
        jsonResponse(['success' => false, 'message' => 'Belum ada kelas untuk mata pelajaran ini']);
    }
    
    jsonResponse(['success' => true, 'data' => $data]);
}

// ============= GET QUIZ (Hanya esai) =============
if ($action == 'getQuiz') {
    $kodeMapel = isset($_GET['kodeMapel']) ? mysqli_real_escape_string($conn, $_GET['kodeMapel']) : '';
    $kelas     = isset($_GET['kelas'])     ? mysqli_real_escape_string($conn, $_GET['kelas'])     : '';
    
    error_log("=== DEBUG getQuiz ===");
    error_log("kodeMapel: $kodeMapel, kelas: $kelas");
    
    if (empty($kodeMapel) || empty($kelas)) {
        jsonResponse(['success' => false, 'message' => 'Parameter tidak lengkap']);
    }
    
    // Ambil quiz dengan type esai saja
    $queryQuiz = mysqli_query($conn, "
        SELECT idQuiz, judul, deskripsi, kelas, waktuMulai, waktuSelesai
        FROM quiz
        WHERE kodeMapel = '$kodeMapel' 
        AND kelas = '$kelas'
        AND type = 'esai'
        ORDER BY waktuMulai DESC
    ");
    
    if (!$queryQuiz) {
        error_log("ERROR Query quiz: " . mysqli_error($conn));
        jsonResponse(['success' => false, 'message' => 'Query error: ' . mysqli_error($conn)]);
    }
    
    $data = [];
    while ($row = mysqli_fetch_assoc($queryQuiz)) {
        $data[] = $row;
    }
    
    error_log("Jumlah quiz: " . count($data));
    
    if (count($data) == 0) {
        jsonResponse(['success' => false, 'message' => 'Belum ada quiz esai untuk kelas ini']);
    }
    
    jsonResponse(['success' => true, 'data' => $data]);
}

// ============= GET DATA SISWA & JAWABAN =============
if ($action == 'getDataSiswa') {
    $idQuiz = isset($_GET['idQuiz']) ? mysqli_real_escape_string($conn, $_GET['idQuiz']) : '';
    $kelas  = isset($_GET['kelas'])  ? mysqli_real_escape_string($conn, $_GET['kelas'])  : '';
    
    error_log("=== DEBUG getDataSiswa ===");
    error_log("idQuiz: $idQuiz, kelas: $kelas");
    
    if (empty($idQuiz) || empty($kelas)) {
        jsonResponse(['success' => false, 'message' => 'Parameter tidak lengkap']);
    }
    
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
    
    if (!$querySiswa) {
        error_log("ERROR Query siswa: " . mysqli_error($conn));
        jsonResponse(['success' => false, 'message' => 'Query error: ' . mysqli_error($conn)]);
    }
    
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
    
    error_log("Jumlah siswa: " . count($siswaList));
    
    jsonResponse([
        'success' => true,
        'quiz' => $quizInfo,
        'siswa' => $siswaList
    ]);
}

// Jika action tidak dikenali
error_log("ERROR: Action tidak dikenali: " . $action);
jsonResponse(['success' => false, 'message' => 'Invalid action: ' . $action]);
?>