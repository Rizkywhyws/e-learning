<?php
// ====================================================================
// CRITICAL GUARDRAILS: Mencegah output sebelum JSON
// ====================================================================

// 1. Matikan error display agar pesan error tidak merusak JSON
error_reporting(E_ALL); 
ini_set('display_errors', 0); // Pastikan ini 0 di production!

// 2. Definisi universal untuk return JSON
function returnJSON($data) {
    // Jika ada buffer yang berjalan, bersihkan dan hentikan
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    // Set header dan encode JSON, lalu keluar (exit)
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. Start output buffering. Semua output akan ditangkap.
ob_start();

// 4. Start session (pastikan ini di atas include jika include menggunakan session)
session_start();

// ====================================================================
// LOGIKA UTAMA
// ====================================================================

// Path include disesuaikan: ../../config/db.php
// Asumsi path: /folder_aplikasi/Siswa/backend/getQuiz.php
// Perlu kembali 2 level ke /folder_aplikasi/config/db.php
include_once('../../config/db.php');

// ===== 1. CEK SESSION & KONEKSI DB =====
if (!isset($conn) || $conn->connect_error) {
    returnJSON([
        'success' => false, 
        'message' => 'Koneksi database gagal atau tidak tersedia.',
        'debug' => 'DB_CONNECTION_ERROR'
    ]);
}
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    returnJSON([
        'success' => false, 
        'message' => 'Session user_id tidak ditemukan. Silakan login ulang.',
        'debug' => 'SESSION_ERROR'
    ]);
}

$idAkun = $_SESSION['user_id'];

// ===== 2. AMBIL NIS DARI DATABASE =====
$queryNIS = "SELECT NIS, kelas FROM datasiswa WHERE idAkun = ?";
$stmtNIS = mysqli_prepare($conn, $queryNIS);

if (!$stmtNIS) {
    returnJSON(['success' => false, 'message' => 'Error prepare NIS: ' . mysqli_error($conn), 'debug' => 'PREPARE_NIS_ERROR']);
}

mysqli_stmt_bind_param($stmtNIS, "s", $idAkun);
if (!mysqli_stmt_execute($stmtNIS)) {
    returnJSON(['success' => false, 'message' => 'Error execute NIS: ' . mysqli_stmt_error($stmtNIS), 'debug' => 'EXECUTE_NIS_ERROR']);
}

$resultNIS = mysqli_stmt_get_result($stmtNIS);

if (!$resultNIS || mysqli_num_rows($resultNIS) == 0) {
    mysqli_stmt_close($stmtNIS);
    returnJSON(['success' => false, 'message' => 'Data siswa tidak ditemukan untuk idAkun: ' . $idAkun, 'debug' => 'NIS_NOT_FOUND']);
}

$dataNIS = mysqli_fetch_assoc($resultNIS);
$NIS = $dataNIS['NIS'];
$kelasSiswa = $dataNIS['kelas']; // Ambil kelas siswa untuk validasi
mysqli_stmt_close($stmtNIS);

// ===== 3. VALIDASI PARAMETER idQuiz =====
if (!isset($_GET['idQuiz']) || empty($_GET['idQuiz'])) {
    returnJSON(['success' => false, 'message' => 'Parameter idQuiz tidak ditemukan', 'debug' => 'IDQUIZ_EMPTY']);
}

$idQuiz = trim($_GET['idQuiz']); 
// Tidak perlu mysqli_real_escape_string karena kita pakai prepared statement

// ===== 4. AMBIL DATA QUIZ & VALIDASI KELAS =====
$queryQuiz = "
    SELECT q.idQuiz, q.judul, q.waktuMulai, q.waktuSelesai, q.durasi, q.kodeMapel, q.kelas,
           m.namaMapel
    FROM quiz q
    INNER JOIN mapel m ON m.kodeMapel = q.kodeMapel
    WHERE q.idQuiz = ?
";

$stmtQuiz = mysqli_prepare($conn, $queryQuiz);

if (!$stmtQuiz) {
    returnJSON(['success' => false, 'message' => 'Error prepare query quiz', 'debug' => 'QUIZ_PREPARE_ERROR']);
}

mysqli_stmt_bind_param($stmtQuiz, "s", $idQuiz);
if (!mysqli_stmt_execute($stmtQuiz)) {
    returnJSON(['success' => false, 'message' => 'Error execute quiz: ' . mysqli_stmt_error($stmtQuiz), 'debug' => 'EXECUTE_QUIZ_ERROR']);
}

$resultQuiz = mysqli_stmt_get_result($stmtQuiz);

if (!$resultQuiz || mysqli_num_rows($resultQuiz) == 0) {
    mysqli_stmt_close($stmtQuiz);
    returnJSON(['success' => false, 'message' => 'Quiz tidak ditemukan dengan ID: ' . $idQuiz, 'debug' => 'QUIZ_NOT_FOUND']);
}

$dataQuiz = mysqli_fetch_assoc($resultQuiz);
mysqli_stmt_close($stmtQuiz);

// Lakukan validasi kelas di sisi server
if (!in_array($kelasSiswa, explode(',', $dataQuiz['kelas']))) {
    returnJSON(['success' => false, 'message' => 'Quiz tidak dialokasikan untuk kelas Anda.', 'debug' => 'CLASS_MISMATCH']);
}

// ===== 5. HITUNG JUMLAH SOAL (Sudah Bagus) =====
$querySoal = "SELECT COUNT(*) AS jumlah FROM soalquiz WHERE idQuiz = ?";
$stmtSoal = mysqli_prepare($conn, $querySoal);

if (!$stmtSoal) {
    returnJSON(['success' => false, 'message' => 'Error prepare query soal', 'debug' => 'SOAL_PREPARE_ERROR']);
}

mysqli_stmt_bind_param($stmtSoal, "s", $idQuiz);
mysqli_stmt_execute($stmtSoal);
$resSoal = mysqli_stmt_get_result($stmtSoal);
$dataSoal = mysqli_fetch_assoc($resSoal);
$jumlahSoal = $dataSoal['jumlah'] ?? 0;
mysqli_stmt_close($stmtSoal);

// ===== 6. CEK SUDAH DIKERJAKAN (Sudah Bagus) =====
$queryJawab = "
    SELECT COUNT(*) AS totalDijawab,
           MIN(waktuMulai) as waktuMulaiPengerjaan,
           MAX(waktuSelesai) as waktuSelesaiPengerjaan
    FROM jawabanquiz 
    WHERE idQuiz = ? AND NIS = ?
";
// ... (Bagian 6 tetap sama)

$stmtJawab = mysqli_prepare($conn, $queryJawab);
// ... (Error handling tetap sama)

mysqli_stmt_bind_param($stmtJawab, "ss", $idQuiz, $NIS);
mysqli_stmt_execute($stmtJawab);
$resJawab = mysqli_stmt_get_result($stmtJawab);
$dataJawab = mysqli_fetch_assoc($resJawab);
mysqli_stmt_close($stmtJawab);

$sudahDikerjakan = ($dataJawab['totalDijawab'] > 0);

// ===== 7. HITUNG NILAI DAN WAKTU JIKA SUDAH DIKERJAKAN (Sudah Bagus) =====
$nilai = null;
$waktuPengerjaan = null;

if ($sudahDikerjakan) {
    // Hitung waktu pengerjaan
    // ... (Logika perhitungan waktu tetap sama)
    if (!empty($dataJawab['waktuMulaiPengerjaan']) && !empty($dataJawab['waktuSelesaiPengerjaan'])) {
        $mulai = strtotime($dataJawab['waktuMulaiPengerjaan']);
        $selesai = strtotime($dataJawab['waktuSelesaiPengerjaan']);
        $selisihDetik = $selesai - $mulai;
        
        $jam = floor($selisihDetik / 3600);
        $menit = floor(($selisihDetik % 3600) / 60);
        $detik = $selisihDetik % 60;
        
        if ($jam > 0) {
            $waktuPengerjaan = $jam . ' jam ' . $menit . ' menit';
        } else {
            $waktuPengerjaan = $menit . ' menit ' . $detik . ' detik';
        }
    }
    
    // Hitung nilai (hanya untuk soal pilgan/multi)
    // ... (Logika query nilai tetap sama)
    $queryNilai = "
        SELECT 
            COUNT(*) as totalDijawab,
            SUM(CASE 
                WHEN LOWER(TRIM(jq.jawabanPilgan)) = LOWER(TRIM(sq.jawabanMulti)) 
                THEN 1 
                ELSE 0 
            END) as totalBenar
        FROM jawabanquiz jq
        INNER JOIN soalquiz sq ON jq.idSoal = sq.idSoal
        WHERE jq.idQuiz = ? 
        AND jq.NIS = ?
        AND sq.type IN ('Pilgan', 'Multi')
        AND jq.jawabanPilgan IS NOT NULL
    ";
    
    $stmtNilai = mysqli_prepare($conn, $queryNilai);
    if ($stmtNilai) {
        mysqli_stmt_bind_param($stmtNilai, "ss", $idQuiz, $NIS);
        mysqli_stmt_execute($stmtNilai);
        $resNilai = mysqli_stmt_get_result($stmtNilai);
        $dataNilai = mysqli_fetch_assoc($resNilai);
        
        if ($dataNilai['totalDijawab'] > 0) {
            $nilai = round(($dataNilai['totalBenar'] / $dataNilai['totalDijawab']) * 100, 2);
        }
        
        mysqli_stmt_close($stmtNilai);
    }
}

// ===== 8. FORMAT TANGGAL (Sudah Bagus) =====
$tanggal = null;
$waktuMulaiFormatted = null;
$waktuSelesaiFormatted = null;

if (!empty($dataQuiz['waktuMulai'])) {
    $timestamp = strtotime($dataQuiz['waktuMulai']);
    if ($timestamp !== false) {
        $tanggal = date('d M Y', $timestamp);
        $waktuMulaiFormatted = date('d M Y H:i', $timestamp);
    }
}

if (!empty($dataQuiz['waktuSelesai'])) {
    $timestamp = strtotime($dataQuiz['waktuSelesai']);
    if ($timestamp !== false) {
        $waktuSelesaiFormatted = date('d M Y H:i', $timestamp);
    }
}


// ===== 9. RETURN SUCCESS =====
returnJSON([
    'success' => true,
    'idQuiz' => $dataQuiz['idQuiz'],
    'judul' => $dataQuiz['judul'],
    'namaMapel' => $dataQuiz['namaMapel'],
    'tanggal' => $tanggal,
    'waktuMulai' => $waktuMulaiFormatted,
    'waktuSelesai' => $waktuSelesaiFormatted,
    'durasi' => intval($dataQuiz['durasi']),
    'jumlahSoal' => intval($jumlahSoal),
    'sudahDikerjakan' => $sudahDikerjakan,
    'nilai' => $nilai,
    'waktuPengerjaan' => $waktuPengerjaan
]);
?>