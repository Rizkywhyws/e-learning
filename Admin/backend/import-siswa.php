<?php
require_once "../../config/db.php";
require_once "../../vendor/autoload.php"; // Untuk PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Method not allowed");
}

// Cek apakah file di-upload
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    die("Error: File tidak ditemukan atau gagal upload");
}

$file = $_FILES['file'];
$fileName = $file['name'];
$fileTmpName = $file['tmp_name'];
$fileSize = $file['size'];
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Validasi extension
$allowedExt = ['xlsx', 'xls', 'csv'];
if (!in_array($fileExt, $allowedExt)) {
    die("Error: Format file tidak didukung. Gunakan .xlsx, .xls, atau .csv");
}

// Validasi ukuran file (max 5MB)
if ($fileSize > 5 * 1024 * 1024) {
    die("Error: Ukuran file terlalu besar. Maksimal 5MB");
}

try {
    // Load spreadsheet
    $spreadsheet = IOFactory::load($fileTmpName);
    $sheet = $spreadsheet->getActiveSheet();
    $data = $sheet->toArray();

    // Ambil header (baris pertama)
    $header = array_shift($data);
    
    // Normalisasi header (trim dan lowercase)
    $header = array_map(function($h) {
        return strtolower(trim($h));
    }, $header);

    // Mapping kolom yang diperlukan
    $requiredColumns = ['nis', 'nisn', 'nama', 'kelas', 'jurusan', 'email', 'password'];
    
    // Cek apakah semua kolom ada
    foreach ($requiredColumns as $col) {
        if (!in_array($col, $header)) {
            die("Error: Kolom '$col' tidak ditemukan di file. Pastikan header sesuai template.");
        }
    }

    // Ambil index kolom
    $colIndex = array_flip($header);

    $conn->begin_transaction();
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];

    foreach ($data as $index => $row) {
        // Skip baris kosong
        if (empty(array_filter($row))) continue;

        $nis = trim($row[$colIndex['nis']]);
        $nisn = trim($row[$colIndex['nisn']]);
        $nama = trim($row[$colIndex['nama']]);
        $kelas = trim($row[$colIndex['kelas']]);
        $jurusan = trim($row[$colIndex['jurusan']]);
        $email = trim($row[$colIndex['email']]);
        $password = trim($row[$colIndex['password']]);

        // Validasi data wajib
        if (empty($nis) || empty($nisn) || empty($nama) || empty($kelas) || empty($jurusan) || empty($email)) {
            $errors[] = "Baris " . ($index + 2) . ": Data tidak lengkap";
            $errorCount++;
            continue;
        }

        // Validasi email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Baris " . ($index + 2) . ": Email tidak valid ($email)";
            $errorCount++;
            continue;
        }

        try {
            // Hash password jika ada, jika tidak gunakan default
            $hashedPassword = !empty($password) ? password_hash($password, PASSWORD_BCRYPT) : password_hash('12345678', PASSWORD_BCRYPT);
            $idAkun = "SW" . rand(10000, 99999);
            // Insert ke tabel akun
            $stmtAkun = $conn->prepare("INSERT INTO akun (idAkun, email, password, role) VALUES (?, ?, ?, 'siswa')");
            $stmtAkun->bind_param("sss", $idAkun, $email, $hashedPassword);

            
            if (!$stmtAkun->execute()) {
                throw new Exception("Gagal insert akun: " . $stmtAkun->error);
            }



            // Insert ke tabel dataSiswa
            $stmtSiswa = $conn->prepare("INSERT INTO dataSiswa (NIS, NISN, nama, kelas, jurusan, idAkun) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtSiswa->bind_param("ssssss", $nis, $nisn, $nama, $kelas, $jurusan, $idAkun);
            
            if (!$stmtSiswa->execute()) {
                throw new Exception("Gagal insert siswa: " . $stmtSiswa->error);
            }

            $successCount++;

        } catch (Exception $e) {
            $errors[] = "Baris " . ($index + 2) . ": " . $e->getMessage();
            $errorCount++;
            
            // Rollback untuk baris ini saja (hapus akun jika sudah diinsert)
            if (isset($idAkun)) {
                $conn->query("DELETE FROM akun WHERE idAkun = '$idAkun'");
            }
        }
    }

    if ($errorCount > 0 && $successCount === 0) {
        $conn->rollback();
        die("Error: Semua data gagal diimport.<br>" . implode("<br>", array_slice($errors, 0, 10)));
    }

    $conn->commit();

    // Redirect dengan pesan sukses
    $message = "Berhasil import $successCount data siswa";
    if ($errorCount > 0) {
        $message .= ", $errorCount data gagal";
    }

    header("Location: ../kelolasiswa.php?import=success&msg=" . urlencode($message));
    exit;

} catch (Exception $e) {
    $conn->rollback();
    die("Error: " . $e->getMessage());
}
?>