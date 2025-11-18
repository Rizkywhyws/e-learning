<?php
// file getTugas.php
session_start();
include('../../config/db.php');

header('Content-Type: application/json');

// Validasi parameter
if (!isset($_GET['kodeMapel']) || !isset($_GET['idMateri'])) {
    echo json_encode(array('success' => false, 'message' => 'Parameter tidak lengkap'));
    exit;
}

$kodeMapel = mysqli_real_escape_string($conn, $_GET['kodeMapel']);
$idMateri  = mysqli_real_escape_string($conn, $_GET['idMateri']);

$NIS = isset($_SESSION['NIS']) ? $_SESSION['NIS'] : null;

// Jika belum ada NIS di session, ambil dari idAkun
if (!$NIS) {
    $idAkun = isset($_SESSION['idAkun']) ? $_SESSION['idAkun'] : 'A0004';

    $queryNIS = "SELECT NIS FROM datasiswa WHERE idAkun = '$idAkun'";
    $resultNIS = mysqli_query($conn, $queryNIS);

    if ($resultNIS && mysqli_num_rows($resultNIS) > 0) {
        $dataNIS = mysqli_fetch_assoc($resultNIS);
        $NIS = $dataNIS['NIS'];
        $_SESSION['NIS'] = $NIS;
    } else {
        echo json_encode(array('success' => false, 'message' => 'NIS tidak ditemukan'));
        exit;
    }
}

// ================= QUERY TUGAS =================
$query = "
SELECT 
    t.idTugas, t.judul, t.deskripsi, t.deadline, t.createdAt, t.filePath,
    mp.namaMapel, m.judul AS judulMateri
FROM tugas t
INNER JOIN mapel mp ON t.kodeMapel = mp.kodeMapel
INNER JOIN materi m ON t.idMateri = m.idMateri
WHERE t.kodeMapel = '$kodeMapel' AND t.idMateri = '$idMateri'
LIMIT 1
";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(array('success' => false, 'message' => 'Query error: ' . mysqli_error($conn)));
    exit;
}

if (mysqli_num_rows($result) > 0) {
    $data = mysqli_fetch_assoc($result);

    // Cek pengumpulan tugas
    $queryPengumpulan = "
        SELECT pt.submittedAt, pt.nilai, pt.status, pt.filePath, pt.idPengumpulan
        FROM pengumpulantugas pt
        WHERE pt.idTugas = '{$data['idTugas']}' AND pt.NIS = '$NIS'
        LIMIT 1
    ";
    $resultPengumpulan = mysqli_query($conn, $queryPengumpulan);

    $statusTugas = 'belum';
    $dikirimPada = '';
    $nilai = '';
    $statusWaktu = '';
    $filePathSiswa = '';
    $idPengumpulan = '';

    if ($resultPengumpulan && mysqli_num_rows($resultPengumpulan) > 0) {
        $peng = mysqli_fetch_assoc($resultPengumpulan);

        $statusTugas = 'selesai';
        $dikirimPada = date('d F Y, H:i', strtotime($peng['submittedAt']));

        // ubah nama bulan bahasa inggris -> indonesia
        $bulanIndo = array(
            'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
            'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
            'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
            'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
        );
        foreach ($bulanIndo as $en => $id) {
            $dikirimPada = str_replace($en, $id, $dikirimPada);
        }

        $nilai = $peng['nilai'];
        $statusWaktu = $peng['status'];
        $filePathSiswa = !empty($peng['filePath']) ? '/' . $peng['filePath'] : '';
        $idPengumpulan = $peng['idPengumpulan'];
    }

    // Format tanggal dibuat
    $tanggal = date('l, d F Y', strtotime($data['createdAt']));
    $hariIndo = array(
        'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'
    );
    $bulanIndo = array(
        'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
        'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
        'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
        'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
    );
    foreach ($hariIndo as $en => $id) { $tanggal = str_replace($en, $id, $tanggal); }
    foreach ($bulanIndo as $en => $id) { $tanggal = str_replace($en, $id, $tanggal); }

    // Deadline
    $deadline = date('d F Y, H:i', strtotime($data['deadline']));
    foreach ($bulanIndo as $en => $id) { $deadline = str_replace($en, $id, $deadline); }

    $filePath = $data['filePath'];
    $fileURL = "/" . $filePath;

    echo json_encode(array(
        'success' => true,
        'idTugas' => $data['idTugas'],
        'judul' => $data['judul'],
        'namaMapel' => $data['namaMapel'],
        'tanggal' => $tanggal,
        'deskripsi' => !empty($data['deskripsi']) ? $data['deskripsi'] : 'Tidak ada deskripsi',
        'deadline' => $deadline,
        'status' => $statusTugas,
        'dikirimPada' => $dikirimPada,
        'nilai' => $nilai,
        'statusWaktu' => $statusWaktu,
        'filePath' => $fileURL,
        'filePathSiswa' => $filePathSiswa,  // File yang diupload siswa
        'idPengumpulan' => $idPengumpulan   // ID pengumpulan untuk update
    ));

} else {
    echo json_encode(array('success' => false, 'message' => 'Tidak ada tugas untuk materi ini'));
}

mysqli_close($conn);
?>