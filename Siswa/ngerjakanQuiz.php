<?php

include('../config/db.php');
include('../config/session.php');

// Cek login & role
checkLogin();
checkRole(['siswa']);

// Ambil data dari SESSION
$idAkun = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;

if (!$idAkun) {
    die("Error: Session tidak ditemukan. Silakan login ulang.");
}

// Ambil data siswa
$querySiswa = "SELECT NIS, nama, kelas FROM datasiswa WHERE idAkun = ?";
$stmtSiswa = mysqli_prepare($conn, $querySiswa);
if (!$stmtSiswa) die('Prepare error: ' . mysqli_error($conn));
mysqli_stmt_bind_param($stmtSiswa, "s", $idAkun);
mysqli_stmt_execute($stmtSiswa);
$resultSiswa = mysqli_stmt_get_result($stmtSiswa);

if (!$resultSiswa || mysqli_num_rows($resultSiswa) == 0) {
    die("Error: Data siswa tidak ditemukan!");
}

$dataSiswa = mysqli_fetch_assoc($resultSiswa);
$NIS = $dataSiswa['NIS'];
$namaSiswa = $dataSiswa['nama'];
$kelasSiswa = $dataSiswa['kelas'];

// Ambil jadwal pelajaran terdekat (dipertahankan)
$hariIni = date('l');
$hariIndo = [
    'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
    'Sunday' => 'Minggu'
];
$hari = $hariIndo[$hariIni] ?? $hariIni;

$queryJadwal = "SELECT m.namaMapel 
                FROM jadwalmapel jm 
                INNER JOIN mapel m ON jm.kodeMapel = m.kodeMapel 
                WHERE jm.Kelas = ? AND jm.hari = ?
                ORDER BY jm.jamMulai ASC LIMIT 1";
$stmtJadwal = mysqli_prepare($conn, $queryJadwal);
if ($stmtJadwal) {
    mysqli_stmt_bind_param($stmtJadwal, "ss", $kelasSiswa, $hari);
    mysqli_stmt_execute($stmtJadwal);
    $resultJadwal = mysqli_stmt_get_result($stmtJadwal);
    if($resultJadwal && mysqli_num_rows($resultJadwal) > 0) {
        $mapelSelanjutnya = mysqli_fetch_assoc($resultJadwal)['namaMapel'];
    } else {
        $mapelSelanjutnya = "Tidak ada jadwal";
    }
    mysqli_stmt_close($stmtJadwal);
} else {
    $mapelSelanjutnya = "Tidak ada jadwal";
}

// =========================
// TAMPILKAN SEMUA MAPEL (FIX UTAMA)
// =========================
$queryMapel = "SELECT kodeMapel, namaMapel
               FROM mapel
               ORDER BY namaMapel ASC";
$stmtMapel = mysqli_prepare($conn, $queryMapel);
if (!$stmtMapel) {
    die("Error prepare mapel: " . mysqli_error($conn));
}
mysqli_stmt_execute($stmtMapel);
$resultMapel = mysqli_stmt_get_result($stmtMapel);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quiz - E-School</title>

<!-- ====== Google Fonts ====== -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- ====== Font Awesome ====== -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- ====== File CSS ====== -->
<link rel="stylesheet" href="cssSiswa/dashboard.css">
<link rel="stylesheet" href="cssSiswa/ngerjakanQuiz.css">
<style>
    /* Tambahkan style khusus untuk popup detail quiz */
    .popup-content {
        max-width: 500px; /* Atur lebar maksimal */
        width: 90%; /* Atur lebar relatif untuk mobile */
        border-radius: 12px; /* Sudut membulat */
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15); /* Bayangan */
        background: #fff;
        overflow: hidden; /* Pastikan elemen tidak meluber */
    }
    .popup-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        background-color: #f8fafc; /* Warna latar header */
        border-bottom: 1px solid #e2e8f0; /* Garis batas bawah */
    }
    .popup-header h2 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        color: #1e293b;
    }
    .status-label {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
        text-align: center;
    }
    .close-btn {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #94a3b8;
    }
    .close-btn:hover {
        color: #64748b;
    }
    .popup-body {
        padding: 20px;
    }
    .mapel-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e2e8f0;
    }
    .mapel-row p {
        margin: 0;
        font-weight: 600;
        color: #334155;
    }
    .popup-body h3 {
        margin: 10px 0;
        font-size: 1.1rem;
        color: #0f172a;
    }
    .info-row {
        display: flex;
        justify-content: space-between;
        gap: 15px; /* Jarak antar kolom */
        margin-bottom: 15px;
    }
    .info-row > div {
        flex: 1; /* Agar kolom sama lebar */
    }
    .info-row label {
        display: block;
        font-size: 0.8rem;
        font-weight: 500;
        color: #64748b;
        margin-bottom: 5px;
    }
    .info-row input[type="text"] {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background-color: #f1f5f9;
        font-size: 0.9rem;
        color: #334155;
        box-sizing: border-box;
    }
    .info-row input[type="text"]:focus {
        outline: none;
        border-color: #93c5fd;
    }
    #hasilQuizSection {
        background-color: #f8fafc;
        padding: 15px;
        border-radius: 8px;
        margin: 15px 0;
    }
    #btnKerjakan {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    #btnKerjakan:hover {
        opacity: 0.9;
    }
</style>
</head>
<body>

<!-- ===== HEADER (DICOPY DARI DASHBOARD - JANGAN DIUBAH) ===== -->
<div class="sticky-header">
  <header>
    <img src="../assets/logo-elearning.png" class="logo" alt="E-School">

    <!-- MENU ROW -->
    <div class="menu-row">

      <div class="dropdown">
        <button class="dropbtn">
          <i class="fa-solid fa-user"></i>
          Profil
          <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
        </button>
        <div class="dropdown-content">
          <a href="#"><i class="fa-solid fa-user"></i> Profil</a>
        </div>
      </div>

      <div class="dropdown">
        <button class="dropbtn">
          <i class="fa-solid fa-clipboard-check"></i>
          Presensi Siswa
          <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
        </button>
        <div class="dropdown-content">
          <a href="../Siswa/presensi.php"><i class="fa-solid fa-check"></i> Lihat Presensi</a>
        </div>
      </div>

      <div class="dropdown">
        <button class="dropbtn">
          <i class="fa-solid fa-school"></i>
          Pengelolaan Pembelajaran
          <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
        </button>
        <div class="dropdown-content">
          <a href="../Siswa/mataPelajaran.php"><i class="fa-solid fa-book-open"></i> Mata Pelajaran</a>
          <a href="../Siswa/ngerjakanQuiz.php"><i class="fa-solid fa-pen-to-square"></i> Quiz</a>
        </div>
      </div>

      <div class="dropdown">
        <button class="dropbtn"><i class="fa-solid fa-house"></i> Dashboard</button>
        <div class="dropdown-content">
          <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard Utama</a>
        </div>
      </div>

      <div class="dropdown">
        <button class="dropbtn">
          <i class="fa-solid fa-right-from-bracket"></i>
          <a href="../Auth/logout.php" onclick="return confirm('Yakin ingin logout?')" style="text-decoration:none; color:inherit;"> Logout</a>
        </button>
      </div>

    </div>
  </header>
</div>
<!-- ===== END HEADER ===== -->

<!-- ===== WELCOME BOX ===== -->
<div class="welcome-box">
    <h2>Halo! Selamat Datang, <?= htmlspecialchars($namaSiswa) ?></h2>
    <p>Jadwal Pelajaran selanjutnya <b><?= htmlspecialchars($mapelSelanjutnya) ?></b></p>
</div>

<!-- ===== SECTION MATA PELAJARAN ===== -->
<section class="mapel-container">
    <h3>Mata Pelajaran</h3>
    
    <?php if(!$resultMapel || mysqli_num_rows($resultMapel) == 0): ?>
        <div style="text-align: center; padding: 40px; background: white; border-radius: 12px;">
            <p style="color: #999;">Tidak ada mata pelajaran.</p>
        </div>
    <?php else: ?>
    
    <div class="mapel-grid">

        <?php while($mapel = mysqli_fetch_assoc($resultMapel)): 
            $kodeMapel = $mapel['kodeMapel'];          
            
            // Cek apakah ada quiz yang belum dikerjakan untuk mapel ini
            $queryQuizBelum = "
                SELECT COUNT(*) as jumlahQuizBelum
                FROM quiz q
                WHERE q.kodeMapel = ?
                AND FIND_IN_SET(?, q.kelas) > 0
                AND q.idQuiz NOT IN (
                    SELECT idQuiz FROM jawabanquiz WHERE NIS = ?
                )
            ";
            
            $stmtQuizBelum = mysqli_prepare($conn, $queryQuizBelum);
            if ($stmtQuizBelum) {
                mysqli_stmt_bind_param($stmtQuizBelum, "sss", $kodeMapel, $kelasSiswa, $NIS);
                mysqli_stmt_execute($stmtQuizBelum);
                $resultQuizBelum = mysqli_stmt_get_result($stmtQuizBelum);
                $dataQuizBelum = $resultQuizBelum ? mysqli_fetch_assoc($resultQuizBelum) : ['jumlahQuizBelum' => 0];
                mysqli_stmt_close($stmtQuizBelum);
            } else {
                $dataQuizBelum = ['jumlahQuizBelum' => 0];
            }

            $adaQuizBelum = ($dataQuizBelum['jumlahQuizBelum'] > 0);
        ?>
        <div class="mapel-card <?= $adaQuizBelum ? 'has-unfinished-quiz' : '' ?>" onclick="toggleQuiz(this)">
            <p class="kode-mapel"><?= htmlspecialchars($mapel['kodeMapel']) ?></p>
            <h4><?= htmlspecialchars($mapel['namaMapel']) ?></h4>
            <?php if($adaQuizBelum): ?>
                <span class="quiz-indicator"></span>
            <?php endif; ?>

            <!-- Quiz list -->
            <div class="quiz-list">
                <?php
                // Query untuk mendapatkan quiz dari mata pelajaran ini SESUAI KELAS SISWA
                $queryQuiz = "SELECT q.idQuiz, q.judul, q.waktuMulai, q.waktuSelesai
                              FROM quiz q
                              WHERE q.kodeMapel = ?
                              AND FIND_IN_SET(?, q.kelas) > 0
                              ORDER BY q.waktuMulai DESC";
                $stmtQuiz = mysqli_prepare($conn, $queryQuiz);
                if ($stmtQuiz) {
                    mysqli_stmt_bind_param($stmtQuiz, "ss", $kodeMapel, $kelasSiswa);
                    mysqli_stmt_execute($stmtQuiz);
                    $resultQuiz = mysqli_stmt_get_result($stmtQuiz);
                } else {
                    $resultQuiz = false;
                }
                
                if (!$resultQuiz) {
                    echo "<p style='color:red;'>Error query quiz: " . mysqli_error($conn) . "</p>";
                } elseif (mysqli_num_rows($resultQuiz) == 0) {
                    echo "<p style='color:#999; font-size:13px; text-align:center; padding:10px;'>Tidak ada quiz tersedia</p>";
                } else {
                    while($quiz = mysqli_fetch_assoc($resultQuiz)):
                        $idQuiz = $quiz['idQuiz'];
                        
                        // Cek apakah siswa sudah mengerjakan quiz ini
                        $queryJawaban = "SELECT COUNT(*) as jumlahDijawab
                                        FROM jawabanquiz 
                                        WHERE idQuiz = ? AND NIS = ?";
                        $stmtJawaban = mysqli_prepare($conn, $queryJawaban);
                        if ($stmtJawaban) {
                            mysqli_stmt_bind_param($stmtJawaban, "ss", $idQuiz, $NIS);
                            mysqli_stmt_execute($stmtJawaban);
                            $resultJawaban = mysqli_stmt_get_result($stmtJawaban);
                            $dataJawaban = $resultJawaban ? mysqli_fetch_assoc($resultJawaban) : ['jumlahDijawab' => 0];
                            mysqli_stmt_close($stmtJawaban);
                        } else {
                            $dataJawaban = ['jumlahDijawab' => 0];
                        }
                        
                        $statusWarna = 'biru'; // default biru (belum dikerjakan)
                        
                        // Jika ada jawaban, berarti sudah dikerjakan
                        if(!empty($dataJawaban['jumlahDijawab']) && $dataJawaban['jumlahDijawab'] > 0) {
                            $statusWarna = 'hijau';
                        }
                        
                        $tanggalQuiz = date('d M Y', strtotime($quiz['waktuMulai']));
                ?>
                <div class="quiz-item <?= $statusWarna ?>" 
                     onclick="showQuizButton(this, event, '<?= htmlspecialchars($quiz['idQuiz']) ?>', '<?= htmlspecialchars($kodeMapel) ?>')">
                    <span class="judul"><?= htmlspecialchars($quiz['judul']) ?></span>
                    <span class="tgl-quiz"><?= $tanggalQuiz ?></span>
                </div>
                <?php 
                    endwhile;
                }
                // bersihkan result set jika ada
                if (isset($resultQuiz) && $resultQuiz) mysqli_free_result($resultQuiz);
                ?>
            </div>
        </div>
        <?php endwhile; ?>

    </div>
    <?php endif; ?>
</section>

<!-- ===== POPUP DETAIL QUIZ ===== -->
<div id="popupQuiz" class="popup-overlay" style="display:none;">
  <div class="popup-content">
    <div class="popup-header">
      <h2>Detail Quiz</h2>
      <span class="status-label" id="statusQuiz">Belum Dikerjakan</span>
      <button class="close-btn" onclick="closePopup()">&times;</button>
    </div>

    <div class="popup-body">
      <div class="mapel-row">
        <p class="mapel" id="quizMapel">-</p>
        <p class="tanggal" id="quizTanggal">-</p>
      </div>

      <h3 id="quizJudul">-</h3>

      <div class="info-row">
        <div>
          <label>Waktu Mulai</label>
          <input type="text" id="quizWaktuMulai" readonly>
        </div>
        <div>
          <label>Waktu Selesai</label>
          <input type="text" id="quizWaktuSelesai" readonly>
        </div>
      </div>

      <!-- Hapus baris info-row untuk Durasi -->
      <div class="info-row">
        <div>
          <label>Jumlah Soal</label>
          <input type="text" id="quizJumlahSoal" readonly>
        </div>
      </div>

      <!-- Info hasil jika sudah dikerjakan -->
      <div id="hasilQuizSection" style="display: none;">
        <div class="info-row">
          <div>
            <label>Nilai</label>
            <input type="text" id="quizNilai" readonly>
          </div>
          <div>
            <label>Waktu Pengerjaan</label>
            <input type="text" id="quizWaktuPengerjaan" readonly>
          </div>
        </div>
      </div>

      <input type="hidden" id="idQuizHidden">

      <button class="btn-kerjakan" id="btnKerjakan" onclick="mulaiQuiz()">KERJAKAN QUIZ</button>
    </div>
  </div>
</div>

<!-- === Script interaktif === -->
<script>
function toggleQuiz(card) {
    document.querySelectorAll('.mapel-card').forEach(c => {
        if (c !== card) c.classList.remove('active');
    });
    card.classList.toggle('active');
}

function showQuizButton(el, event, idQuiz, kodeMapel) {
    event.stopPropagation();
    loadQuizDetail(idQuiz, kodeMapel);
}

function closePopup() {
    document.querySelectorAll('.popup-overlay').forEach(p => p.style.display = 'none');
}
</script>
<script>
// ... (fungsi toggleQuiz tetap sama) ...
function toggleQuiz(card) {
    document.querySelectorAll('.mapel-card').forEach(c => {
        if (c !== card) c.classList.remove('active');
    });
    card.classList.toggle('active');
}

function showQuizButton(el, event, idQuiz, kodeMapel) {
    event.stopPropagation();
    loadQuizDetail(idQuiz, kodeMapel);
}

function loadQuizDetail(idQuiz, kodeMapel) {
    // ASUMSI: File ngerjakanQuiz.php ada di /Siswa/
    // dan file getQuiz.php ada di /Siswa/backend/
    
    // **FIX PATH UTAMA:** Gunakan path relatif eksplisit yang dihitung dari direktori saat ini.
    // Jika ngerjakanQuiz.php ada di /Siswa/, maka getQuiz.php ada di /Siswa/backend/getQuiz.php
    const relativePath = 'backend/getQuiz.php'; 
    const url = relativePath + '?idQuiz=' + encodeURIComponent(idQuiz);

    // Hapus alert debug yang mengganggu (Opsional, tapi disarankan setelah PHP fix)
    // alert('DEBUG: lihat console (F12) untuk raw response. Ringkasan: ...');

    console.log('--- DEBUG getQuiz request start ---');
    console.log('URL requested:', url);
    
    fetch(url, { credentials: 'same-origin' }) // Menggunakan URL relatif yang diperbaiki
        .then(response => {
            // FIX: Cek status HTTP
            if (!response.ok) {
                // Jika server mengembalikan error (404, 500, dll)
                return response.text().then(text => {
                    throw new Error(`Permintaan gagal dengan status HTTP ${response.status}. Pesan server: ${text.slice(0, 100)}...`);
                });
            }
            
            // Ambil text apa pun yang dikembalikan server untuk debugging
            return response.text().then(text => {
                console.log('Response headers Content-Type:', response.headers.get('content-type'));
                console.log('Raw response text:\n', text);
                console.log('--- DEBUG getQuiz response end ---');

                // Coba parse JSON
                try {
                    const data = JSON.parse(text);
                    return data;
                } catch (err) {
                    // Jika gagal parse, ini adalah error JSON utama
                    throw new Error('Response bukan JSON valid (PHP mungkin menghasilkan output non-JSON): ' + err.message + '\nRaw response start:\n' + text.slice(0,1000));
                }
            });
        })
        .then(data => {
            // jika sampai sini, data adalah objek JSON
            if (data.success) {
                // ... (Logika pembaruan DOM, tanpa durasi) ...
                document.getElementById('quizJudul').textContent = data.judul;
                document.getElementById('quizMapel').textContent = data.namaMapel;
                document.getElementById('quizTanggal').textContent = data.tanggal;
                document.getElementById('quizWaktuMulai').value = data.waktuMulai ?? '';
                document.getElementById('quizWaktuSelesai').value = data.waktuSelesai ?? '';
                // Hapus referensi ke data.durasi
                document.getElementById('quizJumlahSoal').value = (data.jumlahSoal ?? 0) + ' soal';
                document.getElementById('idQuizHidden').value = data.idQuiz;
                
                let statusLabel = document.getElementById('statusQuiz');
                let btnKerjakan = document.getElementById('btnKerjakan');
                let hasilSection = document.getElementById('hasilQuizSection');
                
                if(data.sudahDikerjakan) {
                    statusLabel.textContent = 'Selesai';
                    statusLabel.style.backgroundColor = '#bbf7d0';
                    statusLabel.style.color = '#064e3b';
                    hasilSection.style.display = 'block';
                    document.getElementById('quizNilai').value = (data.nilai ?? 'N/A') + ' / 100'; // Format nilai
                    document.getElementById('quizWaktuPengerjaan').value = data.waktuPengerjaan ?? 'N/A';
                    btnKerjakan.textContent = 'LIHAT PEMBAHASAN';
                    btnKerjakan.style.backgroundColor = '#d1fae5';
                    btnKerjakan.style.color = '#065f46';
                } else {
                    statusLabel.textContent = 'Belum Dikerjakan';
                    statusLabel.style.backgroundColor = '#ff9ea9';
                    statusLabel.style.color = 'white';
                    hasilSection.style.display = 'none';
                    btnKerjakan.textContent = 'KERJAKAN QUIZ';
                    btnKerjakan.style.backgroundColor = '#c5d7ff';
                    btnKerjakan.style.color = '#1a3e8e';
                }
                
                document.getElementById('popupQuiz').style.display = 'flex';
            } else {
                // Menampilkan pesan error dari PHP (jika PHP berhasil mereturn JSON error)
                alert('Gagal memuat detail quiz: ' + (data.message || 'Unknown error') + '\n(Debug: ' + (data.debug || 'NO_DEBUG_CODE') + ')');
            }
        })
        .catch(error => {
            // Tangkap error jaringan atau error parse JSON
            console.error('loadQuizDetail error:', error);
            alert('Terjadi kesalahan saat memuat quiz. (Cek path/server)\nDetail error:\n' + error.message);
        });
}

// ... (fungsi mulaiQuiz dan closePopup tetap sama) ...
function mulaiQuiz() {
    const idQuiz = document.getElementById('idQuizHidden').value;
    const btnKerjakan = document.getElementById('btnKerjakan');
    
    if(btnKerjakan.textContent === 'LIHAT PEMBAHASAN') {
        // Redirect ke halaman pembahasan
        window.location.href = 'pembahasanQuiz.php?idQuiz=' + encodeURIComponent(idQuiz);
    } else {
        // Konfirmasi mulai quiz
        if(confirm('Apakah Anda yakin ingin memulai quiz ini? Timer akan mulai berjalan.')) {
            window.location.href = 'kerjakanQuiz.php?idQuiz=' + encodeURIComponent(idQuiz);
        }
    }
}

function closePopup() {
    document.querySelectorAll('.popup-overlay').forEach(p => p.style.display = 'none');
}
</script>
</body>
</html>