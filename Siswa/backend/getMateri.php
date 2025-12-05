<?php
// File: Siswa/backend/getMateri.php
session_start();
include('../../config/db.php');

header('Content-Type: application/json');

if(!isset($_GET['idMateri'])) {
    echo json_encode(['success' => false, 'message' => 'ID materi tidak ditemukan']);
    exit;
}

$idMateri = mysqli_real_escape_string($conn, $_GET['idMateri']);

// Ambil kelas siswa dari session
$idAkun = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
if (!$idAkun) {
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

// Ambil kelas siswa
$querySiswa = "SELECT kelas FROM datasiswa WHERE idAkun = '$idAkun'";
$resultSiswa = mysqli_query($conn, $querySiswa);
$dataSiswa = mysqli_fetch_assoc($resultSiswa);
$kelasSiswa = $dataSiswa['kelas'];

// Query dengan filter kelas
$query = "SELECT m.idMateri, m.judul, m.deskripsi, m.filePath, m.linkVideo, m.createdAt, m.kodeMapel, mp.namaMapel 
          FROM materi m
          INNER JOIN mapel mp ON m.kodeMapel = mp.kodeMapel
          WHERE m.idMateri = '$idMateri' 
          AND m.kelas = '$kelasSiswa'";

$result = mysqli_query($conn, $query);

if(!$result) {
    echo json_encode(['success' => false, 'message' => 'Query error: ' . mysqli_error($conn)]);
    exit;
}

if(mysqli_num_rows($result) > 0) {
    $data = mysqli_fetch_assoc($result);
    
    // CEK APAKAH MATERI KOSONG
    if(empty($data['filePath']) && empty($data['linkVideo'])) {
        echo json_encode([
            'success' => false, 
            'message' => 'Tidak ada materi',
            'isEmpty' => true
        ]);
        exit;
    }
    
    // Format tanggal
    $tanggal = date('l, d F Y', strtotime($data['createdAt']));
    $hariIndo = [
        'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'
    ];
    $bulanIndo = [
        'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
        'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
        'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
        'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
    ];
    
    foreach($hariIndo as $en => $id) { $tanggal = str_replace($en, $id, $tanggal); }
    foreach($bulanIndo as $en => $id) { $tanggal = str_replace($en, $id, $tanggal); }
    
    echo json_encode([
        'success' => true,
        'judul' => $data['judul'],
        'namaMapel' => $data['namaMapel'],
        'tanggal' => $tanggal,
        'deskripsi' => $data['deskripsi'] ?: 'Tidak ada deskripsi',
        'filePath' => $data['filePath'],
        'linkVideo' => $data['linkVideo']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Materi tidak ditemukan atau bukan untuk kelas Anda']);
}

mysqli_close($conn);
?>