<?php
// ====================================================================
// FIXED getQuiz.php - Ambil nilai dan waktu pengerjaan dari tabel hasilquiz
// ====================================================================

// 1. Error Reporting (Aktifkan sementara untuk debugging)
error_reporting(E_ALL);
ini_set('display_errors', 1); // UBAH KE 0 setelah fix
ini_set('log_errors', 1);

// 2. Fungsi universal untuk return JSON
function returnJSON($data) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// 3. Start output buffering
ob_start();

// 4. Tentukan path config yang benar
$possiblePaths = [
    __DIR__ . '/../../config/db.php',
    dirname(dirname(__DIR__)) . '/config/db.php',
    $_SERVER['DOCUMENT_ROOT'] . '/config/db.php'
];

$configPath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $configPath = $path;
        break;
    }
}

if (!$configPath) {
    returnJSON([
        'success' => false,
        'message' => 'File konfigurasi tidak ditemukan.',
        'debug' => 'CONFIG_FILE_NOT_FOUND',
        'tried_paths' => $possiblePaths,
        'current_dir' => __DIR__
    ]);
}

// 5. Include file konfigurasi
require_once($configPath);

// Include session config (PENTING!)
$sessionPath = dirname($configPath) . '/session.php';
if (file_exists($sessionPath)) {
    require_once($sessionPath);
}

// 6. Start session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== CEK KONEKSI DB =====
if (!isset($conn)) {
    returnJSON([
        'success' => false,
        'message' => 'Koneksi database tidak tersedia.',
        'debug' => 'DB_CONN_NOT_SET'
    ]);
}

if ($conn->connect_error) {
    returnJSON([
        'success' => false,
        'message' => 'Koneksi database gagal: ' . $conn->connect_error,
        'debug' => 'DB_CONNECTION_ERROR'
    ]);
}

// ===== CEK SESSION =====
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    returnJSON([
        'success' => false,
        'message' => 'Session user_id tidak ditemukan. Silakan login ulang.',
        'debug' => 'SESSION_ERROR',
        'session_data' => [
            'session_id' => session_id(),
            'has_user_id' => isset($_SESSION['user_id']),
            'session_keys' => array_keys($_SESSION)
        ]
    ]);
}

$idAkun = $_SESSION['user_id'];

// ===== AMBIL NIS & KELAS SISWA =====
$queryNIS = "SELECT NIS, kelas FROM datasiswa WHERE idAkun = ?";
$stmtNIS = mysqli_prepare($conn, $queryNIS);

if (!$stmtNIS) {
    returnJSON([
        'success' => false,
        'message' => 'Error prepare query NIS.',
        'debug' => 'PREPARE_NIS_ERROR',
        'sql_error' => mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param($stmtNIS, "s", $idAkun);

if (!mysqli_stmt_execute($stmtNIS)) {
    mysqli_stmt_close($stmtNIS);
    returnJSON([
        'success' => false,
        'message' => 'Error execute query NIS.',
        'debug' => 'EXECUTE_NIS_ERROR',
        'sql_error' => mysqli_stmt_error($stmtNIS)
    ]);
}

$resultNIS = mysqli_stmt_get_result($stmtNIS);

if (!$resultNIS || mysqli_num_rows($resultNIS) == 0) {
    mysqli_stmt_close($stmtNIS);
    returnJSON([
        'success' => false,
        'message' => 'Data siswa tidak ditemukan untuk idAkun: ' . $idAkun,
        'debug' => 'NIS_NOT_FOUND'
    ]);
}

$dataNIS = mysqli_fetch_assoc($resultNIS);
$NIS = $dataNIS['NIS'];
$kelasSiswa = $dataNIS['kelas'];
mysqli_stmt_close($stmtNIS);

// ===== VALIDASI PARAMETER idQuiz =====
if (!isset($_GET['idQuiz']) || empty(trim($_GET['idQuiz']))) {
    returnJSON([
        'success' => false,
        'message' => 'Parameter idQuiz tidak valid.',
        'debug' => 'IDQUIZ_EMPTY',
        'get_params' => $_GET
    ]);
}

$idQuiz = trim($_GET['idQuiz']);

// ===== AMBIL DATA QUIZ =====
$queryQuiz = "
    SELECT 
        q.idQuiz, 
        q.judul, 
        q.`waktuMulai`, 
        q.`waktuSelesai`, 
        q.kodeMapel, 
        q.kelas,
        m.namaMapel AS namaMapel
    FROM quiz q
    INNER JOIN mapel m ON m.kodeMapel = q.kodeMapel
    WHERE q.idQuiz = ?
";

$stmtQuiz = mysqli_prepare($conn, $queryQuiz);

if (!$stmtQuiz) {
    returnJSON([
        'success' => false,
        'message' => 'Error prepare query quiz.',
        'debug' => 'QUIZ_PREPARE_ERROR',
        'sql_error' => mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param($stmtQuiz, "s", $idQuiz);

if (!mysqli_stmt_execute($stmtQuiz)) {
    $error_msg = mysqli_stmt_error($stmtQuiz);
    mysqli_stmt_close($stmtQuiz);
    returnJSON([
        'success' => false,
        'message' => 'Error execute query quiz: ' . $error_msg,
        'debug' => 'EXECUTE_QUIZ_ERROR',
        'sql_error' => $error_msg
    ]);
}

$resultQuiz = mysqli_stmt_get_result($stmtQuiz);

if (!$resultQuiz) {
    mysqli_stmt_close($stmtQuiz);
    returnJSON([
        'success' => false,
        'message' => 'Error getting result from quiz query.',
        'debug' => 'GET_RESULT_QUIZ_ERROR',
        'sql_error' => mysqli_error($conn)
    ]);
}

if (mysqli_num_rows($resultQuiz) == 0) {
    mysqli_stmt_close($stmtQuiz);
    returnJSON([
        'success' => false,
        'message' => 'Quiz tidak ditemukan untuk idQuiz: ' . $idQuiz,
        'debug' => 'QUIZ_NOT_FOUND'
    ]);
}

$dataQuiz = mysqli_fetch_assoc($resultQuiz);
mysqli_stmt_close($stmtQuiz);

// ===== VALIDASI KELAS =====
$kelasQuizArray = array_map('trim', explode(',', ($dataQuiz['kelas'] ?? '')));

if (!in_array($kelasSiswa, $kelasQuizArray)) {
    returnJSON([
        'success' => false,
        'message' => 'Quiz ini tidak untuk kelas Anda.',
        'debug' => 'CLASS_MISMATCH',
        'kelas_siswa' => $kelasSiswa,
        'kelas_quiz' => $kelasQuizArray
    ]);
}

// ===== HITUNG JUMLAH SOAL =====
$querySoal = "SELECT COUNT(*) AS jumlah FROM soalquiz WHERE idQuiz = ?";
$stmtSoal = mysqli_prepare($conn, $querySoal);

if (!$stmtSoal) {
    returnJSON([
        'success' => false,
        'message' => 'Error prepare query soal.',
        'debug' => 'SOAL_PREPARE_ERROR',
        'sql_error' => mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param($stmtSoal, "s", $idQuiz);
mysqli_stmt_execute($stmtSoal);
$resSoal = mysqli_stmt_get_result($stmtSoal);
$dataSoal = mysqli_fetch_assoc($resSoal);
$jumlahSoal = intval($dataSoal['jumlah'] ?? 0);
mysqli_stmt_close($stmtSoal);

// ===== CEK SUDAH DIKERJAKAN & AMBIL NILAI & WAKTU PENGERJAAN DARI HASILQUIZ =====
$queryHasil = "
    SELECT 
        nilai, 
        tanggalSubmit
    FROM hasilquiz 
    WHERE idQuiz = ? AND NIS = ?
    LIMIT 1 -- Ambil satu entri terakhir jika ada unique constraint
";
$stmtHasil = mysqli_prepare($conn, $queryHasil);

if (!$stmtHasil) {
    returnJSON([
        'success' => false,
        'message' => 'Error prepare query hasilquiz.',
        'debug' => 'HASIL_PREPARE_ERROR',
        'sql_error' => mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param($stmtHasil, "ss", $idQuiz, $NIS);
mysqli_stmt_execute($stmtHasil);
$resHasil = mysqli_stmt_get_result($stmtHasil);
$dataHasil = mysqli_fetch_assoc($resHasil);
mysqli_stmt_close($stmtHasil);

// Tentukan status dan data berdasarkan hasil
$sudahDikerjakan = ($dataHasil !== null); // Jika ada baris hasil, berarti sudah dikerjakan
$nilai = null;
$waktuPengerjaan = null;

if ($sudahDikerjakan) {
    $nilai = $dataHasil['nilai']; // Ambil nilai dari hasilquiz
    // Format tanggalSubmit menjadi string untuk UI
    if ($dataHasil['tanggalSubmit']) {
        // Contoh format: 01 Jan 2025 14:30
        $waktuPengerjaan = date('d M Y H:i', strtotime($dataHasil['tanggalSubmit']));
    }
}

// ===== FORMAT TANGGAL =====
$tanggal = null;
$waktuMulaiFormatted = null;
$waktuSelesaiFormatted = null;

if (isset($dataQuiz['waktuMulai']) && !empty($dataQuiz['waktuMulai'])) {
    $timestamp = strtotime($dataQuiz['waktuMulai']);
    if ($timestamp !== false) {
        $tanggal = date('d M Y', $timestamp);
        $waktuMulaiFormatted = date('d M Y H:i', $timestamp);
    }
}

if (isset($dataQuiz['waktuSelesai']) && !empty($dataQuiz['waktuSelesai'])) {
    $timestamp = strtotime($dataQuiz['waktuSelesai']);
    if ($timestamp !== false) {
        $waktuSelesaiFormatted = date('d M Y H:i', $timestamp);
    }
}

$namaMapelVal = $dataQuiz['namaMapel'] ?? $dataQuiz['nama_mapel'] ?? null;

// ===== RETURN SUCCESS =====
returnJSON([
    'success' => true,
    'idQuiz' => $dataQuiz['idQuiz'] ?? null,
    'judul' => $dataQuiz['judul'] ?? null,
    'namaMapel' => $namaMapelVal,
    'tanggal' => $tanggal,
    'waktuMulai' => $waktuMulaiFormatted,
    'waktuSelesai' => $waktuSelesaiFormatted,
    'jumlahSoal' => $jumlahSoal,
    'sudahDikerjakan' => $sudahDikerjakan,
    'nilai' => $nilai, // Nilai diambil dari tabel hasilquiz
    'waktuPengerjaan' => $waktuPengerjaan // Waktu pengerjaan diambil dari tanggalSubmit hasilquiz
]);
?>