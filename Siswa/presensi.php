<?php
session_start();
include '../config/db.php'; // pastikan path benar dan $conn adalah mysqli object

// ===== CEK LOGIN =====
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../Auth/login.php");
    exit;
}

// AMBIL DATA DARI SESSION
$idAkun     = $_SESSION['user_id'];
$namaSiswa  = $_SESSION['nama'];
$email      = $_SESSION['email'];
// namaMapel nanti diisi dari DB (jadwal berikutnya)
$namaMapel  = "(Tidak ada jadwal)";

// Inisialisasi variabel siswa dan pesan status
$nisSiswa = null;
$kelasSiswa = null;
$jurusanSiswa = null;
$statusMsg = null;
$statusType = null; // 'success', 'error', 'warning'

// ===== 1. AMBIL DATA LENGKAP SISWA (NIS, KELAS, JURUSAN) =====
$querySiswa = $conn->prepare("
    SELECT NIS, kelas, jurusan 
    FROM datasiswa 
    WHERE idAkun = ? 
");
$querySiswa->bind_param('s', $idAkun); 
$querySiswa->execute();
$dataSiswa = $querySiswa->get_result()->fetch_assoc(); 
$querySiswa->close();

if ($dataSiswa) {
    $nisSiswa = $dataSiswa['NIS'];
    $kelasSiswa = $dataSiswa['kelas']; 
    $jurusanSiswa = $dataSiswa['jurusan']; 
}

// -----------------------------
// Helper: generate idPresensi unik per siswa per sesi
// Format: PR<TAHUN>-<3digit acak>
// Contoh: PR2025-123
// -----------------------------
function generateIdPresensi($conn) {
    $tahun = date('Y'); // Ambil tahun sekarang (misal: 2025)
    
    // Loop untuk memastikan ID unik (jika terjadi collision)
    $maxAttempts = 10;
    $attempt = 0;
    
    do {
        $rand = str_pad(mt_rand(0, 999), 3, '0', STR_PAD_LEFT); // 3 digit acak (000-999)
        $newId = "PR{$tahun}-{$rand}";
        
        // Cek apakah ID sudah ada di database
        $checkQuery = $conn->prepare("SELECT idPresensi FROM presensisiswa WHERE idPresensi = ? LIMIT 1");
        $checkQuery->bind_param('s', $newId);
        $checkQuery->execute();
        $result = $checkQuery->get_result();
        $exists = $result->num_rows > 0;
        $checkQuery->close();
        
        $attempt++;
        
        // Jika ID belum ada, gunakan ID ini
        if (!$exists) {
            return $newId;
        }
        
    } while ($attempt < $maxAttempts);
    
    // Jika setelah 10 percobaan masih collision, gunakan timestamp
    return "PR{$tahun}-" . substr(time(), -3);
}

// -----------------------------
// Helper: Hitung jarak antara 2 koordinat (Haversine Formula)
// Return: jarak dalam meter
// -----------------------------
function hitungJarak($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // Radius bumi dalam meter
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $jarak = $earthRadius * $c;
    
    return $jarak; // dalam meter
}

// -----------------------------
//  HANDLE PRESENSI (tombol) & UPLOAD IZIN (form)
//  Both are handled in this single file
// -----------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // -------------------------
    // 1) PROSES PRESENSI via tombol (action = do_presensi)
    // -------------------------
    if (isset($_POST['action']) && $_POST['action'] === 'do_presensi') {
        $idBuatPresensi = $_POST['idBuatPresensi'] ?? null;
        $nisForm = $_POST['nis'] ?? $nisSiswa;
        $latitudeSiswa = $_POST['latitude'] ?? null;
        $longitudeSiswa = $_POST['longitude'] ?? null;

        if (!$idBuatPresensi) {
            $statusType = 'error';
            $statusMsg = 'Data presensi tidak lengkap.';
        } elseif (!$latitudeSiswa || !$longitudeSiswa) {
            $statusType = 'error';
            $statusMsg = 'Lokasi GPS tidak terdeteksi. Pastikan Anda mengizinkan akses lokasi.';
        } else {
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
                $statusType = 'error';
                $statusMsg = 'Sesi presensi tidak ditemukan.';
            } else {
                // Ambil data lokasi dari database
                $idLokasiPresensi = $bp['idLokasi'] ?? null;
                
                if (!$idLokasiPresensi) {
                    $statusType = 'error';
                    $statusMsg = 'Lokasi presensi tidak ditemukan. Hubungi admin.';
                } else {
                    $qLok = $conn->prepare("SELECT * FROM lokasi WHERE idLokasi = ? LIMIT 1");
                    $qLok->bind_param('s', $idLokasiPresensi);
                    $qLok->execute();
                    $lokasiData = $qLok->get_result()->fetch_assoc();
                    $qLok->close();
                    
                    if (!$lokasiData) {
                        $statusType = 'error';
                        $statusMsg = 'Data lokasi tidak valid. Hubungi admin.';
                    } else {
                        $latLokasi = $lokasiData['latitude'];
                        $lonLokasi = $lokasiData['longitude'];
                        $radiusLokasi = $lokasiData['radius']; // dalam meter
                        
                        // Hitung jarak antara lokasi siswa dan lokasi presensi
                        $jarakSiswa = hitungJarak($latitudeSiswa, $longitudeSiswa, $latLokasi, $lonLokasi);
                        
                        // Validasi: apakah siswa berada dalam radius?
                        if ($jarakSiswa > $radiusLokasi) {
                            $statusType = 'error';
                            $statusMsg = "Anda berada di luar area presensi. Jarak Anda: " . round($jarakSiswa) . "m (Maks: {$radiusLokasi}m)";
                        } else {
                            // Cek apakah user termasuk di kelas yang sesuai (safety)
                            if ($kelasSiswa && strpos($bp['kelas'], $kelasSiswa) === false && $bp['kelas'] !== $kelasSiswa) {
                                $statusType = 'error';
                                $statusMsg = 'Anda tidak terdaftar pada kelas sesi presensi ini.';
                            } else {
                                $now_ts = time();
                                $mulai_ts = strtotime($bp['waktuDimulai']);
                                $tutup_ts = strtotime($bp['waktuDitutup']);
                                $toleransi_minutes = isset($bp['toleransiWaktu']) ? (int)$bp['toleransiWaktu'] : 0;
                                $batas_toleransi_ts = $mulai_ts + ($toleransi_minutes * 60);

                                // Jika sekarang sebelum waktuDimulai -> belum dibuka
                                if ($now_ts < $mulai_ts) {
                                    $statusType = 'warning';
                                    $statusMsg = 'Presensi belum dibuka. Tunggu hingga waktu presensi dimulai.';
                                }
                                // Jika setelah waktuDitutup -> tidak bisa presensi (Alpa otomatis)
                                elseif ($now_ts > $tutup_ts) {
                                    $statusType = 'error';
                                    $statusMsg = 'Presensi sudah ditutup. Anda dinyatakan Alpa untuk sesi ini.';
                                } else {
                                    // Dalam rentang waktuDimulai - waktuDitutup -> bisa presensi
                                    // Cek apakah sudah presensi
                                    $check = $conn->prepare("SELECT idPresensi FROM presensisiswa WHERE idBuatPresensi = ? AND NIS = ? LIMIT 1");
                                    $check->bind_param('ss', $idBuatPresensi, $nisForm);
                                    $check->execute();
                                    $resCheck = $check->get_result();
                                    $already = $resCheck->num_rows > 0;
                                    $check->close();

                                    if ($already) {
                                        $statusType = 'warning';
                                        $statusMsg = 'Anda sudah melakukan presensi pada sesi ini.';
                                    } else {
                                        // Tentukan status: Hadir atau Terlambat
                                        if ($now_ts <= $batas_toleransi_ts) {
                                            $statusPresensi = 'Hadir';
                                        } else {
                                            $statusPresensi = 'Terlambat';
                                        }

                                        $newIdPresensi = generateIdPresensi($conn);

                                        // SIMPAN idLokasi ke database
                                        $ins = $conn->prepare("
                                            INSERT INTO presensisiswa (idPresensi, idBuatPresensi, NIS, status, waktuPresensi, idLokasi)
                                            VALUES (?, ?, ?, ?, NOW(), ?)
                                        ");
                                        $ins->bind_param('sssss', $newIdPresensi, $idBuatPresensi, $nisForm, $statusPresensi, $idLokasiPresensi);

                                        if ($ins->execute()) {
                                            $statusType = 'success';
                                            $statusMsg = "Presensi berhasil: $statusPresensi (Jarak: " . round($jarakSiswa) . "m)";
                                            $ins->close();
                                        } else {
                                            $statusType = 'error';
                                            $statusMsg = 'Gagal menyimpan data presensi. (' . $conn->error . ')';
                                            $ins->close();
                                        }
                                    }
                                } // end else in-range
                            }
                        } // end validasi radius
                    }
                }
            }
        }
    } // end do_presensi

    // -------------------------
    // 2) UPLOAD IZIN / SAKIT (submit_izin)
    // -------------------------
    if (isset($_POST['submit_izin'])) {
        $nisForm = $_POST['nis'] ?? $nisSiswa;
        $jenisIzin = $_POST['jenis_izin'] ?? null; // 'sakit' atau 'izin'
        $fileSurat = $_FILES['surat_izin'] ?? null;
        $idBuatPresensiAktif = null;

        // Cari sesi buatpresensi aktif berdasarkan kelas siswa dan waktu sekarang (menggunakan waktuDimulai)
        if ($kelasSiswa) {
            $qAct = $conn->prepare("
                SELECT bp.idBuatPresensi, bp.waktuDimulai, bp.waktuDitutup, bp.toleransiWaktu, bp.idLokasi
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
        }

        // Validasi dasar
        if (!$nisForm || !$jenisIzin || !$fileSurat || $fileSurat['error'] !== UPLOAD_ERR_OK) {
            $statusType = 'error';
            $statusMsg = 'Data tidak lengkap atau upload file gagal. (Error Code: ' . ($fileSurat['error'] ?? 'N/A') . ')';
        } else {
            // Jika tidak ada sesi aktif: tolak
            if (!$idBuatPresensiAktif) {
                $statusType = 'error';
                $statusMsg = 'Saat ini tidak ada sesi presensi aktif untuk kelas Anda, atau sudah diluar jam upload surat.';
            } else {
                // Pastikan waktu sekarang masih di rentang waktuDimulai - waktuDitutup
                $now_ts = time();
                if ($now_ts < strtotime($waktuDimulaiAktif) || $now_ts > strtotime($waktuDitutupAktif)) {
                    $statusType = 'error';
                    $statusMsg = 'Upload surat hanya diperbolehkan selama waktu presensi (waktuDimulai - waktuDitutup).';
                } else {
                    // --- Proses Upload File ---
                    $target_dir = "../uploads/surat_izin/"; 
                    if (!is_dir($target_dir)) {
                        if (!mkdir($target_dir, 0777, true)) {
                            $statusType = 'error';
                            $statusMsg = 'Gagal membuat folder tujuan upload. Coba buat folder uploads/surat_izin secara manual.';
                            goto end_upload_logic;
                        }
                    }

                    $fileType = strtolower(pathinfo($fileSurat['name'], PATHINFO_EXTENSION));
                    $newFileName = $nisForm . "_" . time() . "." . $fileType; 
                    $target_file = $target_dir . $newFileName;

                    if ($fileSurat['size'] > 2000000) { // 2MB
                        $statusType = 'error';
                        $statusMsg = 'Ukuran file terlalu besar (Maksimal 2MB).';
                    } elseif (!in_array($fileType, ['pdf', 'jpg', 'jpeg'])) {
                        $statusType = 'error';
                        $statusMsg = 'Format file tidak didukung (gunakan PDF/JPG).';
                    } elseif (!move_uploaded_file($fileSurat['tmp_name'], $target_file)) {
                        $statusType = 'error';
                        $statusMsg = 'Gagal memindahkan file surat ke server. Cek izin folder.';
                    } else {
                        // File berhasil diupload
                        $filePathDB = $target_file;
                        $statusPresensi = ucwords($jenisIzin); // 'Sakit' atau 'Izin'

                        // --- Cek apakah siswa sudah punya record presensi untuk sesi ini
                        $queryCheck = $conn->prepare("SELECT idPresensi FROM presensisiswa WHERE idBuatPresensi = ? AND NIS = ? LIMIT 1");
                        $queryCheck->bind_param('ss', $idBuatPresensiAktif, $nisForm);
                        $queryCheck->execute();
                        $resChk = $queryCheck->get_result();
                        $isExisting = $resChk->num_rows > 0;
                        $queryCheck->close();

                        if ($isExisting) {
                            // UPDATE: jika sudah ada record, ubah status & filePath & waktuPresensi
                            $queryAction = $conn->prepare("
                                UPDATE presensisiswa 
                                SET status = ?, waktuPresensi = NOW(), filePath = ?
                                WHERE idBuatPresensi = ? AND NIS = ?
                            ");
                            $queryAction->bind_param('ssss', $statusPresensi, $filePathDB, $idBuatPresensiAktif, $nisForm);
                        } else {
                            // INSERT: buat idPresensi baru (SIMPAN idLokasi juga)
                            $newIdPresensi = generateIdPresensi($conn);
                            $queryAction = $conn->prepare("
                                INSERT INTO presensisiswa (idPresensi, idBuatPresensi, NIS, status, waktuPresensi, filePath, idLokasi)
                                VALUES (?, ?, ?, ?, NOW(), ?, ?)
                            ");
                            $queryAction->bind_param('ssssss', $newIdPresensi, $idBuatPresensiAktif, $nisForm, $statusPresensi, $filePathDB, $idLokasiAktif);
                        }

                        if ($queryAction->execute()) {
                            $queryAction->close();
                            $statusType = 'success';
                            $statusMsg = 'Surat berhasil diunggah dan status presensi diperbarui menjadi ' . $statusPresensi . '!';
                        } else {
                            $queryAction->close();
                            unlink($target_file); 
                            $statusType = 'error';
                            $statusMsg = 'Gagal menyimpan data presensi (upload).';
                        }
                    }
                } // end waktu check
            }
        }
    }
}
end_upload_logic:
// -----------------------------
// END HANDLE POST
// -----------------------------

// -----------------------------
// Ambil sesi presensi aktif untuk tampilan (MENGGUNAKAN waktuDibuat)
// -----------------------------
$presensi = null;
$presensiAktif = false;
$statusPresensiSiswa = '-'; // Default status
$bisakahPresensi = false; // Flag: apakah siswa bisa klik tombol presensi?

if ($kelasSiswa) {
    $queryPresensi = $conn->prepare("
        SELECT 
            bp.*, 
            jm.kelas, 
            m.namaMapel,
            dg.nama AS namaGuru
        FROM 
            buatpresensi bp
        JOIN
            jadwalmapel jm ON bp.idJadwalMapel = jm.idJadwalMapel
        JOIN
            mapel m ON jm.kodeMapel = m.kodeMapel
        JOIN
            dataguru dg ON bp.NIP = dg.NIP
        WHERE 
            jm.kelas = ? 
            AND NOW() >= bp.waktuDibuat 
            AND NOW() <= bp.waktuDitutup
        ORDER BY 
            bp.waktuDibuat DESC 
        LIMIT 1
    ");

    $queryPresensi->bind_param('s', $kelasSiswa);
    $queryPresensi->execute();
    $presensi = $queryPresensi->get_result()->fetch_assoc();
    $queryPresensi->close();
    
    if ($presensi) {
        $presensiAktif = true;

        // CEK: apakah sekarang ada di rentang waktuDimulai - waktuDitutup?
        $now_ts = time();
        $mulai_ts = strtotime($presensi['waktuDimulai']);
        $tutup_ts = strtotime($presensi['waktuDitutup']);
        
        if ($now_ts >= $mulai_ts && $now_ts <= $tutup_ts) {
            $bisakahPresensi = true;
        }

        // Ambil status presensi siswa (jika sudah ada)
        $queryStatus = $conn->prepare("
            SELECT status FROM presensisiswa 
            WHERE idBuatPresensi = ? AND NIS = ? 
            LIMIT 1
        ");
        
        $queryStatus->bind_param('ss', $presensi['idBuatPresensi'], $nisSiswa);
        $queryStatus->execute();
        $resultStatus = $queryStatus->get_result();
        $statusPresensiSiswa = $resultStatus->num_rows > 0 ? $resultStatus->fetch_assoc()['status'] : '-';
        $queryStatus->close();
    }
}

// -----------------------------
// Ambil jadwal / presensi selanjutnya untuk welcome box
// -----------------------------
if ($kelasSiswa) {
    $qNext = $conn->prepare("
        SELECT bp.*, m.namaMapel
        FROM buatpresensi bp
        JOIN jadwalmapel jm ON bp.idJadwalMapel = jm.idJadwalMapel
        JOIN mapel m ON jm.kodeMapel = m.kodeMapel
        WHERE jm.kelas = ? 
          AND bp.waktuDibuat >= NOW()
        ORDER BY bp.waktuDibuat ASC
        LIMIT 1
    ");
    $qNext->bind_param('s', $kelasSiswa);
    $qNext->execute();
    $next = $qNext->get_result()->fetch_assoc();
    $qNext->close();

    if ($next) {
        $namaMapel = $next['namaMapel'];
    }
}

// -----------------------------
// AMBIL DATA KEHADIRAN SISWA UNTUK CHART
// -----------------------------
$dataKehadiran = [
    'hadir' => 0,
    'terlambat' => 0,
    'sakit' => 0,
    'izin' => 0,
    'alpa' => 0
];

if ($nisSiswa) {
    // Hitung jumlah masing-masing status
    $qChart = $conn->prepare("
        SELECT 
            LOWER(status) as status,
            COUNT(*) as jumlah
        FROM presensisiswa
        WHERE NIS = ?
        GROUP BY LOWER(status)
    ");
    $qChart->bind_param('s', $nisSiswa);
    $qChart->execute();
    $resultChart = $qChart->get_result();
    
    while ($row = $resultChart->fetch_assoc()) {
        $stat = $row['status'];
        $jml = $row['jumlah'];
        
        if (isset($dataKehadiran[$stat])) {
            $dataKehadiran[$stat] = $jml;
        }
    }
    $qChart->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Siswa | E-School</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <link rel="stylesheet" href="cssSiswa/presensi.css">
  
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

  <header>
    <img src="../assets/logo-elearning.png" class="logo" alt="E-School">
  </header>

  <div class="menu-row">
    <div class="dropdown">
      <button class="dropbtn"><i class="fa-solid fa-database"></i> Data Master <i class="fa-solid fa-chevron-down dropdown-arrow"></i></button>
      <div class="dropdown-content">
        <a href="#"><i class="fa-solid fa-user"></i> Dashboard</a>
        <a href="#"><i class="fa-solid fa-users"></i> Profil Saya</a>
      </div>
    </div>

    <div class="dropdown">
      <button class="dropbtn"><i class="fa-solid fa-clipboard-check"></i> Presensi Siswa <i class="fa-solid fa-chevron-down dropdown-arrow"></i></button>
      <div class="dropdown-content">
        <a href="#"><i class="fa-solid fa-check"></i> Lihat Presensi</a>
      </div>
    </div>

    <div class="dropdown">
      <button class="dropbtn"><i class="fa-solid fa-school"></i> Pengelolaan Pembelajaran <i class="fa-solid fa-chevron-down dropdown-arrow"></i></button>
      <div class="dropdown-content">
        <a href="#"><i class="fa-solid fa-book-open"></i> Materi</a>
        <a href="#"><i class="fa-solid fa-file-lines"></i> Tugas</a>
        <a href="#"><i class="fa-solid fa-pen-to-square"></i> Quiz</a>
      </div>
    </div>
  </div>


  <section class="welcome-box">
    <h2>Halo! Selamat Datang, <?= htmlspecialchars($namaSiswa) ?></h2>
    <p>Jadwal mapel selanjutnya adalah <b><?= htmlspecialchars($namaMapel) ?></b></p>
  </section>
  
  <?php if ($statusMsg): ?>
    <div class="notification-box <?= htmlspecialchars($statusType) ?>">
        <?= htmlspecialchars($statusMsg) ?>
    </div>
  <?php endif; ?>

  <div class="search-bar">
    <input type="text" placeholder="Search...">
    <button><i class="fa-solid fa-magnifying-glass"></i></button>
  </div>

  <main class="content-container">
    <section class="presensi-section">
      <h1>Presensi</h1>

      <div class="main-content-flex">
        <div class="card now-presensi-card">
          <div class="card-header-blue">
            <h2>Presensi Mendatang</h2>
          </div>
          <div class="card-content">
            <?php if ($presensi && $presensiAktif): ?>
              <h3 class="mapel-title"><?= htmlspecialchars($presensi['namaMapel']) ?></h3>
              <p class="description"><?= htmlspecialchars($presensi['keterangan'] ?? '(Tidak ada keterangan)') ?></p>

              <div class="presensi-details">
                <p><strong>Presensi Mulai</strong> : <?= htmlspecialchars($presensi['waktuDimulai']) ?></p>
                <p><strong>Presensi Akhir</strong> : <?= htmlspecialchars($presensi['waktuDitutup']) ?></p>
                <p><strong>ID Lokasi</strong> : <?= htmlspecialchars($presensi['idLokasi'] ?? '-') ?></p>
                <p><strong>Guru Pengampu</strong> : <?= htmlspecialchars($presensi['namaGuru']) ?></p>
              </div>

              <div class="status-box">
                <?php if ($statusPresensiSiswa === '-'): ?>
                  <div class="belum-absen">
                    <?php if ($bisakahPresensi): ?>
                      <p>Anda belum melakukan presensi</p>
                      <small>Klik untuk absen (Lokasi diperlukan)</small>

                      <!-- FORM PRESENSI: kirim POST ke file ini -->
                      <form method="POST" id="formPresensi" style="display:inline;">
                          <input type="hidden" name="action" value="do_presensi">
                          <input type="hidden" name="idBuatPresensi" value="<?= htmlspecialchars($presensi['idBuatPresensi']) ?>">
                          <input type="hidden" name="nis" value="<?= htmlspecialchars($nisSiswa) ?>">
                          <input type="hidden" name="latitude" id="inputLatitude">
                          <input type="hidden" name="longitude" id="inputLongitude">
                          <button type="button" onclick="getLocationAndSubmit()" class="x-icon-box" title="Klik untuk Absen">
                             <i class="fa-solid fa-xmark"></i>
                          </button>
                      </form>
                    <?php else: ?>
                      <p>Presensi belum dibuka</p>
                      <small>Tunggu hingga waktu presensi dimulai</small>
                      <div class="x-icon-box disabled" title="Belum bisa absen">
                         <i class="fa-solid fa-xmark"></i>
                      </div>
                    <?php endif; ?>
                  </div>
                  <?php if ($bisakahPresensi): ?>
                    <a href="#" id="openModalBtn" class="upload-izin-btn">
                      <i class="fa-solid fa-cloud-arrow-up"></i> Unggah surat izin/sakit
                    </a>
                  <?php else: ?>
                    <div class="upload-izin-btn disabled">
                      <i class="fa-solid fa-cloud-arrow-up"></i> Unggah surat izin/sakit
                    </div>
                  <?php endif; ?>
                <?php else: ?>
                  <div class="sudah-absen">
                    <p>Status Anda: <?= htmlspecialchars(ucwords($statusPresensiSiswa)) ?></p>
                    <small>Anda telah melakukan presensi pada waktu yang ditentukan.</small>
                    <div class="v-icon-box"><i class="fa-solid fa-check"></i></div>
                  </div>
                   <div class="upload-izin-btn disabled">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Unggah surat izin/sakit
                  </div>
                <?php endif; ?>
              </div>

            <?php else: ?>
              <p style="text-align: center; color: #666; font-size: 1.1em; padding: 20px 0;">Belum ada presensi berjalan saat ini.</p>

              <div class="status-box">
                <div class="sudah-absen">
                  <p>Tidak ada presensi</p>
                  <small>Anda telah menyelesaikan semua presensi aktif.</small>
                  <div class="v-icon-box"><i class="fa-solid fa-check"></i></div>
                </div>
                <div class="upload-izin-btn disabled">
                  <i class="fa-solid fa-cloud-arrow-up"></i> Unggah surat izin/sakit
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="card data-kehadiran-card">
          <div class="card-header-blue">
            <h2>Data Kehadiran</h2>
          </div>
          <div class="chart-container">
            <canvas id="kehadiranChart"></canvas>
          </div>
        </div>
      </div>
    </section>

    <section class="rekap-presensi-section">
      <h1>Rekap Presensi</h1>

      <?php 
        // include '#'; // Pastikan file ini ada
      ?>
    </section>

    <div class="legend-row">
      <span class="legend-item"><span class="legend-color hadir"></span> Hadir</span>
      <span class="legend-item"><span class="legend-color alpa"></span> Alpa</span>
      <span class="legend-item"><span class="legend-color sakit"></span> Sakit</span>
      <span class="legend-item"><span class="legend-color izin"></span> Izin</span>
      <span class="legend-item"><span class="legend-color tidak-ada"></span> Tidak Ada Presensi</span>
    </div>
  </main>

  <div id="uploadModal" class="modal-overlay">
      <div class="modal-content">
        <div class="modal-header">
            Upload surat izin/sakit
        </div>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-body">
              <div class="modal-field">
                  <strong>NIS</strong> <span class="colon">:</span>
                  <span><?= htmlspecialchars($nisSiswa ?? '-') ?></span>
                  <input type="hidden" name="nis" value="<?= htmlspecialchars($nisSiswa ?? '') ?>">
              </div>
              <div class="modal-field">
                  <strong>Nama</strong> <span class="colon">:</span>
                  <span><?= htmlspecialchars($namaSiswa) ?></span>
              </div>
              <div class="modal-field">
                  <strong>Kelas</strong> <span class="colon">:</span>
                  <span><?= htmlspecialchars($kelasSiswa ?? '-') ?></span>
              </div>
              <div class="modal-field">
                  <strong>Jurusan</strong> <span class="colon">:</span>
                  <span><?= htmlspecialchars($jurusanSiswa ?? '-') ?></span>
              </div>
              <div class="modal-field">
                  <strong>Jenis Perizinan</strong> <span class="colon">:</span>
                  <div class="radio-group">
                      <label>
                          <input type="radio" name="jenis_izin" value="sakit" required checked> Sakit
                      </label>
                      <label>
                          <input type="radio" name="jenis_izin" value="izin"> Izin
                      </label>
                  </div>
              </div>
              
              <div class="upload-box">
                  <label id="fileLabel" class="upload-label" for="fileInput">Upload File (Max 2MB, PDF/JPG)</label>
                  <input type="file" name="surat_izin" id="fileInput" class="upload-input" accept=".pdf,.jpg,.jpeg" required>
                  <label for="fileInput" class="upload-icon-btn">
                      <i class="fa-solid fa-cloud-arrow-up"></i>
                  </label>
              </div>
              <small class="file-note">(Maksimal ukuran file 2MB, format PDF atau JPG)</small>
            </div>
            <div class="modal-footer">
                <button type="submit" name="submit_izin" class="modal-kirim-btn">Kirim</button>
            </div>
        </form>
      </div>
  </div>

  
  <script>
    // ========================================
    // CHART.JS - DATA KEHADIRAN
    // ========================================
    const dataKehadiran = {
      hadir: <?= $dataKehadiran['hadir'] ?>,
      terlambat: <?= $dataKehadiran['terlambat'] ?>,
      sakit: <?= $dataKehadiran['sakit'] ?>,
      izin: <?= $dataKehadiran['izin'] ?>,
      alpa: <?= $dataKehadiran['alpa'] ?>
    };

    const ctx = document.getElementById('kehadiranChart').getContext('2d');
    const kehadiranChart = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Hadir', 'Terlambat', 'Sakit', 'Izin', 'Alpa'],
        datasets: [{
          label: 'Jumlah',
          data: [
            dataKehadiran.hadir,
            dataKehadiran.terlambat,
            dataKehadiran.sakit,
            dataKehadiran.izin,
            dataKehadiran.alpa
          ],
          backgroundColor: [
            '#4CAF50',  // Hadir - Hijau
            '#FF9800',  // Terlambat - Orange
            '#2196F3',  // Sakit - Biru
            '#9C27B0',  // Izin - Ungu
            '#F44336'   // Alpa - Merah
          ],
          borderColor: '#fff',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              padding: 15,
              font: {
                size: 12,
                family: 'Poppins'
              }
            }
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                let label = context.label || '';
                if (label) {
                  label += ': ';
                }
                label += context.parsed + ' kali';
                return label;
              }
            }
          }
        }
      }
    });

    // ========================================
    // FUNGSI GPS LOCATION
    // ========================================
    function getLocationAndSubmit() {
      if (!navigator.geolocation) {
        alert('Browser Anda tidak mendukung GPS. Gunakan browser modern seperti Chrome atau Firefox.');
        return;
      }

      // Tampilkan loading
      const btn = event.target.closest('button');
      const originalHTML = btn.innerHTML;
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
      btn.disabled = true;

      navigator.geolocation.getCurrentPosition(
        function(position) {
          // Berhasil mendapatkan lokasi
          document.getElementById('inputLatitude').value = position.coords.latitude;
          document.getElementById('inputLongitude').value = position.coords.longitude;
          
          // Submit form
          document.getElementById('formPresensi').submit();
        },
        function(error) {
          // Gagal mendapatkan lokasi
          btn.innerHTML = originalHTML;
          btn.disabled = false;
          
          let errorMsg = '';
          switch(error.code) {
            case error.PERMISSION_DENIED:
              errorMsg = 'Anda menolak akses lokasi. Silakan izinkan akses lokasi di pengaturan browser.';
              break;
            case error.POSITION_UNAVAILABLE:
              errorMsg = 'Informasi lokasi tidak tersedia. Pastikan GPS/lokasi di perangkat Anda aktif.';
              break;
            case error.TIMEOUT:
              errorMsg = 'Waktu permintaan lokasi habis. Coba lagi.';
              break;
            default:
              errorMsg = 'Terjadi kesalahan saat mengambil lokasi. Coba lagi.';
          }
          alert(errorMsg);
        },
        {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 0
        }
      );
    }

    // ========================================
    // FUNGSI JAVASCRIPT UNTUK MODAL dan Dropdown
    // ========================================
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('uploadModal');
        const openBtn = document.getElementById('openModalBtn');
        const fileInput = document.getElementById('fileInput');
        const fileLabel = document.getElementById('fileLabel');

        // Buka Modal saat tombol diklik (hanya jika tombolnya aktif)
        if (openBtn) {
            openBtn.addEventListener('click', function(e) {
                e.preventDefault();
                modal.style.display = 'flex';
            });
        }

        // Tutup Modal jika area overlay diklik
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });

        // Update Label saat file dipilih
        fileInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                fileLabel.textContent = this.files[0].name;
            } else {
                fileLabel.textContent = 'Upload File (Max 2MB, PDF/JPG)';
            }
        });

        // Handle dropdowns
        const dropdowns = document.querySelectorAll('.dropdown');
        dropdowns.forEach(dropdown => {
            const dropbtn = dropdown.querySelector('.dropbtn');
            const dropdownContent = dropdown.querySelector('.dropdown-content');

            dropbtn.addEventListener('click', function() {
                // Tutup dropdown lain
                document.querySelectorAll('.dropdown-content').forEach(content => {
                    if (content !== dropdownContent) {
                        content.style.display = 'none';
                    }
                });
                // Toggle dropdown yang sedang dibuka
                dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
            });
        });

        // Tutup dropdown jika klik di luar
        window.addEventListener('click', function(e) {
            if (!e.target.matches('.dropbtn') && !e.target.matches('.dropbtn *')) {
                document.querySelectorAll('.dropdown-content').forEach(content => {
                    content.style.display = 'none';
                });
            }
        });

    });
  </script>
</body>
</html>