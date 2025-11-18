<?php
// File: Siswa/submitTugas.php
session_start();
date_default_timezone_set('Asia/Jakarta');
include('../../config/db.php');

header('Content-Type: application/json');

// Validasi request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validasi parameter
if (!isset($_POST['idTugas']) || !isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

$idTugas = mysqli_real_escape_string($conn, $_POST['idTugas']);

// Cek apakah ini adalah update atau insert baru
$isUpdate = isset($_POST['isUpdate']) && $_POST['isUpdate'] == '1';
$idPengumpulan = isset($_POST['idPengumpulan']) ? mysqli_real_escape_string($conn, $_POST['idPengumpulan']) : null;

// Ambil NIS dari session
$NIS = isset($_SESSION['NIS']) ? $_SESSION['NIS'] : null;

if (!$NIS) {
    // Ambil NIS dari idAkun jika belum ada di session
    $idAkun = isset($_SESSION['idAkun']) ? $_SESSION['idAkun'] : 'A0004';
    $queryNIS = "SELECT NIS FROM datasiswa WHERE idAkun = '$idAkun'";
    $resultNIS = mysqli_query($conn, $queryNIS);
    
    if ($resultNIS && mysqli_num_rows($resultNIS) > 0) {
        $dataNIS = mysqli_fetch_assoc($resultNIS);
        $NIS = $dataNIS['NIS'];
        $_SESSION['NIS'] = $NIS;
    } else {
        echo json_encode(['success' => false, 'message' => 'NIS tidak ditemukan']);
        exit;
    }
}

// Jika bukan update, cek apakah tugas sudah pernah dikumpulkan
if (!$isUpdate) {
    $queryCheck = "SELECT idPengumpulan FROM pengumpulantugas WHERE idTugas = '$idTugas' AND NIS = '$NIS'";
    $resultCheck = mysqli_query($conn, $queryCheck);

    if ($resultCheck && mysqli_num_rows($resultCheck) > 0) {
        echo json_encode(['success' => false, 'message' => 'Tugas sudah pernah dikumpulkan sebelumnya']);
        exit;
    }
}

// Ambil data tugas untuk cek deadline
$queryTugas = "SELECT deadline FROM tugas WHERE idTugas = '$idTugas'";
$resultTugas = mysqli_query($conn, $queryTugas);

if (!$resultTugas || mysqli_num_rows($resultTugas) == 0) {
    echo json_encode(['success' => false, 'message' => 'Tugas tidak ditemukan']);
    exit;
}

$dataTugas = mysqli_fetch_assoc($resultTugas);
$deadline = strtotime($dataTugas['deadline']);
$submittedAt = time();

// Tentukan status: Terlambat atau Tepat Waktu
$statusDB = ($submittedAt > $deadline) ? 'terlambat' : 'selesai';
$statusDisplay = ($submittedAt > $deadline) ? 'Terlambat' : 'Tepat Waktu';

// ==================== UPLOAD FILE ====================
$file = $_FILES['file'];
$fileName = $file['name'];
$fileTmpName = $file['tmp_name'];
$fileSize = $file['size'];
$fileError = $file['error'];

// Validasi error upload
if ($fileError !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Error saat upload file']);
    exit;
}

// Validasi ukuran file (maksimal 10MB)
if ($fileSize > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Ukuran file terlalu besar (maksimal 10MB)']);
    exit;
}

// Validasi tipe file
$allowedExtensions = ['pdf', 'docx', 'pptx', 'jpg', 'jpeg', 'png'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($fileExtension, $allowedExtensions)) {
    echo json_encode(['success' => false, 'message' => 'Tipe file tidak diizinkan. Hanya PDF, DOCX, PPTX, JPG, PNG']);
    exit;
}

// Buat nama file unik untuk menghindari overwrite
$uniqueFileName = $NIS . '_' . $idTugas . '_' . time() . '.' . $fileExtension;

// Tentukan folder upload
$uploadDir = '../uploads/tugas/';

// Buat folder jika belum ada
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$uploadPath = $uploadDir . $uniqueFileName;

// Pindahkan file ke folder upload
if (!move_uploaded_file($fileTmpName, $uploadPath)) {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file']);
    exit;
}

// Path relatif untuk disimpan ke database
$filePathDB = 'uploads/tugas/' . $uniqueFileName;

// ==================== PROSES UPDATE ATAU INSERT ====================
$submittedAtDB = date('Y-m-d H:i:s', $submittedAt);
$dikirimPadaDisplay = date('d M Y H:i', $submittedAt);

if ($isUpdate && $idPengumpulan) {
    // ========== MODE UPDATE ==========
    
    // Hapus file lama terlebih dahulu
    $queryOldFile = "SELECT filePath FROM pengumpulantugas WHERE idPengumpulan = '$idPengumpulan' AND NIS = '$NIS'";
    $resultOldFile = mysqli_query($conn, $queryOldFile);
    
    if ($resultOldFile && mysqli_num_rows($resultOldFile) > 0) {
        $dataOldFile = mysqli_fetch_assoc($resultOldFile);
        $oldFilePath = '../' . $dataOldFile['filePath'];
        
        // Hapus file lama jika ada
        if (file_exists($oldFilePath)) {
            unlink($oldFilePath);
        }
    }
    
    // Update data pengumpulan
    $queryUpdate = "UPDATE pengumpulantugas 
                    SET filePath = '$filePathDB', 
                        submittedAt = '$submittedAtDB', 
                        status = '$statusDB'
                    WHERE idPengumpulan = '$idPengumpulan' AND NIS = '$NIS'";
    
    $resultUpdate = mysqli_query($conn, $queryUpdate);
    
    if ($resultUpdate) {
        echo json_encode([
            'success' => true, 
            'message' => 'Tugas berhasil diperbarui!',
            'idPengumpulan' => $idPengumpulan,
            'status' => $statusDisplay,
            'fileName' => $uniqueFileName,
            'dikirimPada' => $dikirimPadaDisplay,
            'filePathSiswa' => $filePathDB,
            'isUpdate' => true
        ]);
    } else {
        // Jika update gagal, hapus file baru yang sudah diupload
        if (file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui data: ' . mysqli_error($conn)]);
    }
    
} else {
    // ========== MODE INSERT BARU ==========
    
    // Generate ID Pengumpulan
    // Format: PTG0001, PTG0002, dst
    $queryLastId = "SELECT idPengumpulan FROM pengumpulantugas ORDER BY idPengumpulan DESC LIMIT 1";
    $resultLastId = mysqli_query($conn, $queryLastId);

    if ($resultLastId && mysqli_num_rows($resultLastId) > 0) {
        $dataLastId = mysqli_fetch_assoc($resultLastId);
        $lastId = $dataLastId['idPengumpulan'];
        
        // Ambil angka dari ID terakhir (contoh: PTG0005 -> 5)
        $lastNumber = intval(substr($lastId, 3));
        $newNumber = $lastNumber + 1;
        
        // Format dengan leading zero (contoh: 6 -> PTG0006)
        $idPengumpulan = 'PTG' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    } else {
        // Jika belum ada data, mulai dari PTG0001
        $idPengumpulan = 'PTG0001';
    }

    // Insert ke database
    $queryInsert = "INSERT INTO pengumpulantugas (idPengumpulan, idTugas, NIS, filePath, submittedAt, nilai, status) 
                    VALUES ('$idPengumpulan', '$idTugas', '$NIS', '$filePathDB', '$submittedAtDB', NULL, '$statusDB')";

    $resultInsert = mysqli_query($conn, $queryInsert);

    if ($resultInsert) {
        echo json_encode([
            'success' => true, 
            'message' => 'Tugas berhasil dikumpulkan!',
            'idPengumpulan' => $idPengumpulan,
            'status' => $statusDisplay,
            'fileName' => $uniqueFileName,
            'dikirimPada' => $dikirimPadaDisplay,
            'filePathSiswa' => $filePathDB, // penting kalau JS lihat file
            'isUpdate' => false
        ]);
    } else {
        // Jika insert gagal, hapus file yang sudah diupload
        if (file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data: ' . mysqli_error($conn)]);
    }
}

mysqli_close($conn);
?>