<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
include '../config/db.php';

// ===== CEK LOGIN =====
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../Auth/login.php");
    exit;
}

// AMBIL DATA DARI SESSION
$idAkun = $_SESSION['user_id'];
$nisSiswa = null;
$kelasSiswa = null;

// Ambil data siswa
$querySiswa = $conn->prepare("SELECT NIS, kelas FROM datasiswa WHERE idAkun = ?");
$querySiswa->bind_param('s', $idAkun);
$querySiswa->execute();
$dataSiswa = $querySiswa->get_result()->fetch_assoc();
$querySiswa->close();

if ($dataSiswa) {
    $nisSiswa = $dataSiswa['NIS'];
    $kelasSiswa = $dataSiswa['kelas'];
}

// Helper: generate idPresensi unik
function generateIdPresensi($conn) {
    $tahun = date('Y');
    $maxAttempts = 10;
    $attempt = 0;
    
    do {
        $rand = str_pad(mt_rand(0, 999), 3, '0', STR_PAD_LEFT);
        $newId = "PR{$tahun}-{$rand}";
        
        $checkQuery = $conn->prepare("SELECT idPresensi FROM presensisiswa WHERE idPresensi = ? LIMIT 1");
        $checkQuery->bind_param('s', $newId);
        $checkQuery->execute();
        $result = $checkQuery->get_result();
        $exists = $result->num_rows > 0;
        $checkQuery->close();
        
        $attempt++;
        
        if (!$exists) {
            return $newId;
        }
        
    } while ($attempt < $maxAttempts);
    
    return "PR{$tahun}-" . substr(time(), -3);
}

// ===== PROSES PRESENSI DENGAN TOKEN =====
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // -------------------------
    // 1) PROSES PRESENSI HADIR
    // -------------------------
    if (isset($_POST['action']) && $_POST['action'] === 'do_presensi') {
        $idBuatPresensi = $_POST['idBuatPresensi'] ?? null;
        $nisForm = $_POST['nis'] ?? $nisSiswa;
        $tokenInput = $_POST['token'] ?? null;

        if (!$idBuatPresensi || !$tokenInput) {
            $_SESSION['statusType'] = 'error';
            $_SESSION['statusMsg'] = 'Data presensi tidak lengkap. Token wajib diisi.';
            header("Location: presensi.php");
            exit;
        }

        // Ambil data buatpresensi
        $q = $conn->prepare("
            SELECT bp.*, jm.kelas, jm.kodeMapel, m.namaMapel
            FROM buatpresensi bp
            JOIN jadwalmapel jm ON bp.idJadwalMapel = jm.idJadwalMapel
            JOIN mapel m ON jm.kodeMapel = m.kodeMapel
            WHERE bp.idBuatPresensi = ?
            LIMIT 1
        ");
        $q->bind_param('s', $idBuatPresensi);
        $q->execute();
        $bp = $q->get_result()->fetch_assoc();
        $q->close();

        if (!$bp) {
            $_SESSION['statusType'] = 'error';
            $_SESSION['statusMsg'] = 'Sesi presensi tidak ditemukan.';
            header("Location: presensi.php");
            exit;
        }

        // Validasi Token
        if ($tokenInput !== $bp['Token']) {
            $_SESSION['statusType'] = 'error';
            $_SESSION['statusMsg'] = 'Token presensi salah! Silakan masukkan token yang benar.';
            header("Location: presensi.php");
            exit;
        }

        // Validasi Kelas
        if ($kelasSiswa && strpos($bp['kelas'], $kelasSiswa) === false && $bp['kelas'] !== $kelasSiswa) {
            $_SESSION['statusType'] = 'error';
            $_SESSION['statusMsg'] = 'Anda tidak terdaftar pada kelas sesi presensi ini.';
            header("Location: presensi.php");
            exit;
        }

        // Validasi Waktu
        $now_ts = time();
        $mulai_ts = strtotime($bp['waktuDimulai']);
        $tutup_ts = strtotime($bp['waktuDitutup']);
        $toleransi_minutes = isset($bp['toleransiWaktu']) ? (int)$bp['toleransiWaktu'] : 0;
        $batas_toleransi_ts = $mulai_ts + ($toleransi_minutes * 60);

        if ($now_ts < $mulai_ts) {
            $_SESSION['statusType'] = 'warning';
            $_SESSION['statusMsg'] = 'Presensi belum dibuka. Tunggu hingga waktu presensi dimulai.';
            header("Location: presensi.php");
            exit;
        }

        if ($now_ts > $tutup_ts) {
            $_SESSION['statusType'] = 'error';
            $_SESSION['statusMsg'] = 'Presensi sudah ditutup. Anda dinyatakan Alpa untuk sesi ini.';
            header("Location: presensi.php");
            exit;
        }

        // Cek apakah sudah presensi
        $check = $conn->prepare("SELECT idPresensi FROM presensisiswa WHERE idBuatPresensi = ? AND NIS = ? LIMIT 1");
        $check->bind_param('ss', $idBuatPresensi, $nisForm);
        $check->execute();
        $resCheck = $check->get_result();
        $already = $resCheck->num_rows > 0;
        $check->close();

        if ($already) {
            $_SESSION['statusType'] = 'warning';
            $_SESSION['statusMsg'] = 'Anda sudah melakukan presensi pada sesi ini.';
            header("Location: presensi.php");
            exit;
        }

        // Tentukan status (Hadir atau Terlambat)
        if ($now_ts <= $batas_toleransi_ts) {
            $statusPresensi = 'Hadir';
        } else {
            $statusPresensi = 'Terlambat';
        }

        // Simpan presensi
        $newIdPresensi = generateIdPresensi($conn);
        $idLokasiPresensi = $bp['idLokasi'] ?? null;

        $ins = $conn->prepare("
            INSERT INTO presensisiswa (idPresensi, idBuatPresensi, NIS, status, waktuPresensi)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $ins->bind_param('ssss', $newIdPresensi, $idBuatPresensi, $nisForm, $statusPresensi);

        if ($ins->execute()) {
            $_SESSION['statusType'] = 'success';
            $_SESSION['statusMsg'] = "Presensi berhasil: $statusPresensi";
            $ins->close();
        } else {
            $_SESSION['statusType'] = 'error';
            $_SESSION['statusMsg'] = 'Gagal menyimpan data presensi. (' . $conn->error . ')';
            $ins->close();
        }

        header("Location: presensi.php");
        exit;
    }

    // -------------------------
    // 2) UPLOAD IZIN / SAKIT DENGAN TOKEN
    // -------------------------
    if (isset($_POST['submit_izin'])) {
        $nisForm = $_POST['nis'] ?? $nisSiswa;
        $jenisIzin = $_POST['jenis_izin'] ?? null;
        $tokenInput = $_POST['token_izin'] ?? null;
        $fileSurat = $_FILES['surat_izin'] ?? null;
        $idBuatPresensiAktif = null;

        // Cari sesi buatpresensi aktif
        if ($kelasSiswa) {
            $qAct = $conn->prepare("
                SELECT bp.idBuatPresensi, bp.waktuDimulai, bp.waktuDitutup, bp.toleransiWaktu, bp.idLokasi, bp.token
                FROM buatpresensi bp
                JOIN jadwalmapel jm ON bp.idJadwalMapel = jm.idJadwalMapel
                WHERE jm.kelas = ?
                AND NOW() >= bp.waktuDimulai
                AND NOW() <= bp.waktuDitutup
                ORDER BY bp.waktuDibuat DESC
                LIMIT 1
            ");
            $qAct->bind_param('s', $kelasSiswa);
            $qAct->execute();
            $activeRow = $qAct->get_result()->fetch_assoc();
            $qAct->close();
            $idBuatPresensiAktif = $activeRow['idBuatPresensi'] ?? null;
            $waktuDimulaiAktif = $activeRow['waktuDimulai'] ?? null;
            $waktuDitutupAktif = $activeRow['waktuDitutup'] ?? null;
            $idLokasiAktif = $activeRow['idLokasi'] ?? null;
            $tokenAktif = $activeRow['token'] ?? null;
        }

        if (!$nisForm || !$jenisIzin || !$tokenInput || !$fileSurat || $fileSurat['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['statusType'] = 'error';
            $_SESSION['statusMsg'] = 'Data tidak lengkap atau upload file gagal. Token wajib diisi. (Error Code: ' . ($fileSurat['error'] ?? 'N/A') . ')';
            header("Location: presensi.php");
            exit;
        }

        if (!$idBuatPresensiAktif) {
            $_SESSION['statusType'] = 'error';
            $_SESSION['statusMsg'] = 'Saat ini tidak ada sesi presensi aktif untuk kelas Anda.';
            header("Location: presensi.php");
            exit;
        }

        // Validasi Token untuk upload surat
        if ($tokenInput !== $tokenAktif) {
            $_SESSION['statusType'] = 'error';
            $_SESSION['statusMsg'] = 'Token presensi salah! Silakan masukkan token yang benar.';
            header("Location: presensi.php");
            exit;
        }

        $now_ts = time();
        if ($now_ts < strtotime($waktuDimulaiAktif) || $now_ts > strtotime($waktuDitutupAktif)) {
            $_SESSION['statusType'] = 'error';
            $_SESSION['statusMsg'] = 'Upload surat hanya diperbolehkan selama waktu presensi aktif.';
            header("Location: presensi.php");
            exit;
        }

        $target_dir = "../uploads/surat_izin/";
        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0777, true)) {
                $_SESSION['statusType'] = 'error';
                $_SESSION['statusMsg'] = 'Gagal membuat folder tujuan upload.';
                header("Location: presensi.php");
                exit;
            }
        }

        $fileType = strtolower(pathinfo($fileSurat['name'], PATHINFO_EXTENSION));
        $newFileName = $nisForm . "_" . time() . "." . $fileType;
        $target_file = $target_dir . $newFileName;

        if ($fileSurat['size'] > 2000000) {
            $_SESSION['statusType'] = 'error';
            $_SESSION['statusMsg'] = 'Ukuran file terlalu besar (Maksimal 2MB).';
            header("Location: presensi.php");
            exit;
        }

        if (!in_array($fileType, ['pdf', 'jpg', 'jpeg'])) {
            $_SESSION['statusType'] = 'error';
            $_SESSION['statusMsg'] = 'Format file tidak didukung (gunakan PDF/JPG).';
            header("Location: presensi.php");
            exit;
        }

        if (!move_uploaded_file($fileSurat['tmp_name'], $target_file)) {
            $_SESSION['statusType'] = 'error';
            $_SESSION['statusMsg'] = 'Gagal memindahkan file surat ke server.';
            header("Location: presensi.php");
            exit;
        }

        $filePathDB = $target_file;
        $statusPresensi = ucwords($jenisIzin);

        $queryCheck = $conn->prepare("SELECT idPresensi FROM presensisiswa WHERE idBuatPresensi = ? AND NIS = ? LIMIT 1");
        $queryCheck->bind_param('ss', $idBuatPresensiAktif, $nisForm);
        $queryCheck->execute();
        $resChk = $queryCheck->get_result();
        $isExisting = $resChk->num_rows > 0;
        $queryCheck->close();

        if ($isExisting) {
            $queryAction = $conn->prepare("
                UPDATE presensisiswa 
                SET status = ?, waktuPresensi = NOW(), filePath = ?
                WHERE idBuatPresensi = ? AND NIS = ?
            ");
            $queryAction->bind_param('ssss', $statusPresensi, $filePathDB, $idBuatPresensiAktif, $nisForm);
        } else {
            $newIdPresensi = generateIdPresensi($conn);
            $queryAction = $conn->prepare("
                INSERT INTO presensisiswa (idPresensi, idBuatPresensi, NIS, status, waktuPresensi, filePath, idLokasi)
                VALUES (?, ?, ?, ?, NOW(), ?, ?)
            ");
            $queryAction->bind_param('ssssss', $newIdPresensi, $idBuatPresensiAktif, $nisForm, $statusPresensi, $filePathDB, $idLokasiAktif);
        }

        if ($queryAction->execute()) {
            $queryAction->close();
            $_SESSION['statusType'] = 'success';
            $_SESSION['statusMsg'] = 'Surat berhasil diunggah dan status presensi diperbarui menjadi ' . $statusPresensi . '!';
        } else {
            $queryAction->close();
            unlink($target_file);
            $_SESSION['statusType'] = 'error';
            $_SESSION['statusMsg'] = 'Gagal menyimpan data presensi.';
        }

        header("Location: presensi.php");
        exit;
    }
}

// Jika tidak ada POST, redirect ke halaman presensi
header("Location: presensi.php");
exit;
?>