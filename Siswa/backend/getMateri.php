<?php
// File: Siswa/getMateri.php
session_start();
include('../../config/db.php');

header('Content-Type: application/json');

if(!isset($_GET['idMateri'])) {
    echo json_encode(['success' => false, 'message' => 'ID materi tidak ditemukan']);
    exit;
}

$idMateri = mysqli_real_escape_string($conn, $_GET['idMateri']);

// Query sesuai dengan struktur database
$query = "SELECT m.idMateri, m.judul, m.deskripsi, m.filePath, m.linkVideo, m.createdAt, m.kodeMapel, mp.namaMapel 
          FROM materi m
          INNER JOIN mapel mp ON m.kodeMapel = mp.kodeMapel
          WHERE m.idMateri = '$idMateri'";

$result = mysqli_query($conn, $query);

// Tambahkan error handling
if(!$result) {
    echo json_encode(['success' => false, 'message' => 'Query error: ' . mysqli_error($conn)]);
    exit;
}

if(mysqli_num_rows($result) > 0) {
    $data = mysqli_fetch_assoc($result);
    
    // CEK APAKAH MATERI KOSONG (tidak ada file dan tidak ada link)
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
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
        'Sunday' => 'Minggu'
    ];
    $bulanIndo = [
        'January' => 'Januari',
        'February' => 'Februari',
        'March' => 'Maret',
        'April' => 'April',
        'May' => 'Mei',
        'June' => 'Juni',
        'July' => 'Juli',
        'August' => 'Agustus',
        'September' => 'September',
        'October' => 'Oktober',
        'November' => 'November',
        'December' => 'Desember'
    ];
    
    foreach($hariIndo as $en => $id) {
        $tanggal = str_replace($en, $id, $tanggal);
    }
    foreach($bulanIndo as $en => $id) {
        $tanggal = str_replace($en, $id, $tanggal);
    }
    
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
    echo json_encode(['success' => false, 'message' => 'Materi tidak ditemukan']);
}

mysqli_close($conn);
?>