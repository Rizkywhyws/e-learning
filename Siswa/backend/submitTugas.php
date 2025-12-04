<?php
// File: Siswa/backend/submitTugas.php
session_start();
date_default_timezone_set('Asia/Jakarta');

// MATIKAN SEMUA OUTPUT SEBELUM JSON
ob_start();

include('../../config/db.php');

// Set header JSON
header('Content-Type: application/json');

try {
    // Validasi request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validasi parameter
    if (!isset($_POST['idTugas']) || !isset($_FILES['file'])) {
        throw new Exception('Data tidak lengkap');
    }

    $idTugas = mysqli_real_escape_string($conn, $_POST['idTugas']);
    $isUpdate = isset($_POST['isUpdate']) && $_POST['isUpdate'] == '1';
    $idPengumpulan = isset($_POST['idPengumpulan']) ? mysqli_real_escape_string($conn, $_POST['idPengumpulan']) : null;

    // Ambil NIS dari session
    $NIS = isset($_SESSION['NIS']) ? $_SESSION['NIS'] : null;

    if (!$NIS) {
        $idAkun = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$idAkun) {
            throw new Exception('Session expired. Silakan login ulang.');
        }
        
        $queryNIS = "SELECT NIS FROM datasiswa WHERE idAkun = '$idAkun'";
        $resultNIS = mysqli_query($conn, $queryNIS);
        
        if (!$resultNIS || mysqli_num_rows($resultNIS) == 0) {
            throw new Exception('NIS tidak ditemukan');
        }
        
        $dataNIS = mysqli_fetch_assoc($resultNIS);
        $NIS = $dataNIS['NIS'];
        $_SESSION['NIS'] = $NIS;
    }

    // Ambil data tugas untuk cek deadline
    $queryTugas = "SELECT deadline FROM tugas WHERE idTugas = '$idTugas'";
    $resultTugas = mysqli_query($conn, $queryTugas);

    if (!$resultTugas || mysqli_num_rows($resultTugas) == 0) {
        throw new Exception('Tugas tidak ditemukan');
    }

    $dataTugas = mysqli_fetch_assoc($resultTugas);
    $deadline = strtotime($dataTugas['deadline']);
    $submittedAt = time();

    // Tentukan status
    $statusDB = ($submittedAt > $deadline) ? 'terlambat' : 'selesai';
    $statusDisplay = ($submittedAt > $deadline) ? 'Terlambat' : 'Tepat Waktu';

    // ==================== UPLOAD FILE ====================
    $file = $_FILES['file'];
    
    // Validasi error upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error upload file: ' . $file['error']);
    }

    // Validasi ukuran file (maksimal 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception('Ukuran file terlalu besar (maksimal 10MB)');
    }

    // Validasi tipe file
    $allowedExtensions = ['pdf', 'docx', 'pptx', 'jpg', 'jpeg', 'png'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($fileExtension, $allowedExtensions)) {
        throw new Exception('Tipe file tidak diizinkan. Hanya PDF, DOCX, PPTX, JPG, PNG');
    }

    // Buat nama file unik
    $uniqueFileName = $NIS . '_' . $idTugas . '_' . time() . '.' . $fileExtension;

    // ==================== SETUP PATH - PERBAIKAN ====================
    // Relative path dari document root
    $uploadDirRelative = 'uploads/tugas/';
    
    // PENTING: Cari document root yang benar
    // Karena file PHP ini ada di Siswa/backend/, maka document root adalah 2 level ke atas
    $documentRoot = $_SERVER['DOCUMENT_ROOT'];
    
    // Path absolut lengkap
    $uploadDirAbsolute = $documentRoot . '/elearning-app/' . $uploadDirRelative;

    // DEBUG: Log path untuk troubleshooting
    error_log("Document Root: " . $documentRoot);
    error_log("Upload Dir Absolute: " . $uploadDirAbsolute);

    // Buat folder jika belum ada
    if (!file_exists($uploadDirAbsolute)) {
        if (!mkdir($uploadDirAbsolute, 0777, true)) {
            throw new Exception('Gagal membuat folder upload di: ' . $uploadDirAbsolute);
        }
    }

    // Path lengkap untuk save file
    $uploadPath = $uploadDirAbsolute . $uniqueFileName;
    
    error_log("Full Upload Path: " . $uploadPath);

    // Upload file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Gagal menyimpan file ke: ' . $uploadPath);
    }

    // Verifikasi file berhasil ter-upload
    if (!file_exists($uploadPath)) {
        throw new Exception('File tidak ditemukan setelah upload: ' . $uploadPath);
    }

    // Path untuk database (relative dari document root)
    $filePathDB = $uploadDirRelative . $uniqueFileName;

    // ==================== SAVE TO DATABASE ====================
    $submittedAtDB = date('Y-m-d H:i:s', $submittedAt);
    $dikirimPadaDisplay = date('d M Y H:i', $submittedAt);

    if ($isUpdate && $idPengumpulan) {
        // MODE UPDATE
        
        // Hapus file lama
        $queryOldFile = "SELECT filePath FROM pengumpulantugas WHERE idPengumpulan = '$idPengumpulan' AND NIS = '$NIS'";
        $resultOldFile = mysqli_query($conn, $queryOldFile);
        
        if ($resultOldFile && mysqli_num_rows($resultOldFile) > 0) {
            $dataOldFile = mysqli_fetch_assoc($resultOldFile);
            $oldFilePath = $documentRoot . '/elearning-app/' . $dataOldFile['filePath'];
            
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        }
        
        // Update
        $queryUpdate = "UPDATE pengumpulantugas 
                        SET filePath = '$filePathDB', 
                            submittedAt = '$submittedAtDB', 
                            status = '$statusDB'
                        WHERE idPengumpulan = '$idPengumpulan' AND NIS = '$NIS'";
        
        if (!mysqli_query($conn, $queryUpdate)) {
            throw new Exception('Gagal memperbarui data: ' . mysqli_error($conn));
        }
        
        $message = 'Tugas berhasil diperbarui!';
        
    } else {
        // MODE INSERT
        
        // Cek apakah sudah pernah submit
        $queryCheck = "SELECT idPengumpulan FROM pengumpulantugas WHERE idTugas = '$idTugas' AND NIS = '$NIS'";
        $resultCheck = mysqli_query($conn, $queryCheck);
        
        if ($resultCheck && mysqli_num_rows($resultCheck) > 0) {
            // Sudah ada, update saja
            $dataCheck = mysqli_fetch_assoc($resultCheck);
            $idPengumpulan = $dataCheck['idPengumpulan'];
            
            // Hapus file lama
            $queryOldFile = "SELECT filePath FROM pengumpulantugas WHERE idPengumpulan = '$idPengumpulan'";
            $resultOldFile = mysqli_query($conn, $queryOldFile);
            
            if ($resultOldFile && mysqli_num_rows($resultOldFile) > 0) {
                $dataOldFile = mysqli_fetch_assoc($resultOldFile);
                $oldFilePath = $documentRoot . '/elearning-app/' . $dataOldFile['filePath'];
                
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }
            
            // Update
            $queryUpdate = "UPDATE pengumpulantugas 
                            SET filePath = '$filePathDB', 
                                submittedAt = '$submittedAtDB', 
                                status = '$statusDB'
                            WHERE idPengumpulan = '$idPengumpulan'";
            
            if (!mysqli_query($conn, $queryUpdate)) {
                throw new Exception('Gagal memperbarui data: ' . mysqli_error($conn));
            }
            
            $message = 'Tugas berhasil diperbarui!';
            
        } else {
            // Insert baru
            
            // Generate ID (gunakan MAX + 1 untuk integer)
            $queryLastId = "SELECT MAX(idPengumpulan) as maxId FROM pengumpulantugas";
            $resultLastId = mysqli_query($conn, $queryLastId);

            if ($resultLastId && mysqli_num_rows($resultLastId) > 0) {
                $dataLastId = mysqli_fetch_assoc($resultLastId);
                $lastId = $dataLastId['maxId'];
                $idPengumpulan = $lastId + 1;
            } else {
                $idPengumpulan = 1;
            }

            // Insert
            $queryInsert = "INSERT INTO pengumpulantugas (idPengumpulan, idTugas, NIS, filePath, submittedAt, nilai, status) 
                            VALUES ($idPengumpulan, '$idTugas', '$NIS', '$filePathDB', '$submittedAtDB', NULL, '$statusDB')";

            if (!mysqli_query($conn, $queryInsert)) {
                throw new Exception('Gagal menyimpan data: ' . mysqli_error($conn));
            }
            
            $message = 'Tugas berhasil dikumpulkan!';
        }
    }

    // BUANG SEMUA OUTPUT SEBELUMNYA
    ob_end_clean();

    // Output JSON
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'idPengumpulan' => $idPengumpulan,
        'status' => $statusDisplay,
        'fileName' => $uniqueFileName,
        'dikirimPada' => $dikirimPadaDisplay,
        'filePathSiswa' => $filePathDB, // uploads/tugas/filename.pdf
        'isUpdate' => $isUpdate,
        // Debug info
        'debug' => [
            'uploadPath' => $uploadPath,
            'fileExists' => file_exists($uploadPath),
            'filePathDB' => $filePathDB
        ]
    ]);

} catch (Exception $e) {
    // BUANG SEMUA OUTPUT SEBELUMNYA
    ob_end_clean();
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?>