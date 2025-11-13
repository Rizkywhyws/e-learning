<?php
session_start();
// Pastikan file db.php mengembalikan objek koneksi MySQLi ($conn)
include '../config/db.php'; 

// ===== CEK LOGIN =====
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../Auth/login.php");
    exit;
}

// AMBIL DATA DARI SESSION
$idAkun     = $_SESSION['user_id'];
$namaSiswa = $_SESSION['nama'];
$email     = $_SESSION['email'];
$namaMapel = "XII DKV 1";

// Inisialisasi variabel siswa
$nisSiswa = null;
$kelasSiswa = null;
$jurusanSiswa = null;

// ===== 1. AMBIL DATA LENGKAP SISWA (NIS, KELAS, JURUSAN) - MENGGUNAKAN MYSQLi =====
$querySiswa = $conn->prepare("
    SELECT NIS, kelas, jurusan 
    FROM datasiswa 
    WHERE idAkun = ? 
");
// bind_param() digunakan untuk MySQLi: 's' = string
$querySiswa->bind_param('s', $idAkun); 
$querySiswa->execute();

// Mengambil hasil menggunakan metode MySQLi (get_result()->fetch_assoc())
$dataSiswa = $querySiswa->get_result()->fetch_assoc(); 
$querySiswa->close();

if ($dataSiswa) {
    $nisSiswa = $dataSiswa['NIS'];
    $kelasSiswa = $dataSiswa['kelas']; 
    $jurusanSiswa = $dataSiswa['jurusan']; 
} else {
    // Penanganan jika data siswa tidak ditemukan (seharusnya tidak terjadi)
}


// Inisialisasi variabel presensi
$presensi = null;
$presensiAktif = false;
$statusPresensiSiswa = '-'; // Default status

// Pastikan kelasSiswa sudah terambil sebelum menjalankan query presensi
if ($kelasSiswa) {
    // ===== 2. AMBIL DATA PRESENSI BERJALAN BERDASARKAN KELAS SISWA - MENGGUNAKAN MYSQLi =====
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
            jm.kelas = ? /* <-- Placeholder tanda tanya */
            AND NOW() >= bp.waktuDibuat 
            AND NOW() <= bp.waktuDitutup
        ORDER BY 
            bp.waktuDibuat DESC 
        LIMIT 1
    ");

    // bind_param() digunakan untuk MySQLi: 's' = string
    $queryPresensi->bind_param('s', $kelasSiswa);
    $queryPresensi->execute();
    $presensi = $queryPresensi->get_result()->fetch_assoc();
    $queryPresensi->close();
    
    if ($presensi) {
        $presensiAktif = true;

        // ===== 3. AMBIL STATUS PRESENSI SISWA - MENGGUNAKAN MYSQLi =====
        $queryStatus = $conn->prepare("
            SELECT status FROM presensisiswa 
            WHERE idBuatPresensi = ? AND NIS = ? /* <-- Dua placeholder tanda tanya */
        ");
        
        // bind_param() untuk MySQLi: 'si' = string (idBuatPresensi) dan integer (NIS)
        $queryStatus->bind_param('si', $presensi['idBuatPresensi'], $nisSiswa);
        $queryStatus->execute();
        
        $resultStatus = $queryStatus->get_result();
        
        // Ambil status presensi (jika ada, jika tidak, tetap '-')
        $statusPresensiSiswa = $resultStatus->num_rows > 0 ? $resultStatus->fetch_assoc()['status'] : '-';
        $queryStatus->close();
    }
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
            <h2>Sekarang</h2>
          </div>
          <div class="card-content">
            <?php if ($presensi && $presensiAktif): ?>
              <h3 class="mapel-title"><?= htmlspecialchars($presensi['namaMapel']) ?></h3>
              <p class="description"><?= htmlspecialchars($presensi['keterangan'] ?? '(Tidak ada keterangan)') ?></p>

              <div class="presensi-details">
                <p><strong>Presensi Mulai</strong> : <?= htmlspecialchars($presensi['waktuDibuat']) ?></p>
                <p><strong>Presensi Akhir</strong> : <?= htmlspecialchars($presensi['waktuDitutup']) ?></p>
                <p><strong>ID Lokasi</strong> : <?= htmlspecialchars($presensi['idLokasi']) ?></p>
                <p><strong>Guru Pengampu</strong> : <?= htmlspecialchars($presensi['namaGuru']) ?></p>
              </div>

              <div class="status-box">
                <?php if ($statusPresensiSiswa === '-'): ?>
                  <div class="belum-absen">
                    <p>Anda belum melakukan presensi</p>
                    <small>Klik untuk absen</small>
                    <a href="proses_presensi.php?id=<?= urlencode($presensi['idBuatPresensi']) ?>" class="x-icon-box" title="Klik untuk Absen">
                       <i class="fa-solid fa-xmark"></i>
                    </a>
                  </div>
                  <a href="#" id="openModalBtn" class="upload-izin-btn">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Unggah surat izin/sakit
                  </a>
                <?php else: ?>
                  <div class="sudah-absen">
                    <p>Status Anda: **<?= htmlspecialchars(ucwords($statusPresensiSiswa)) ?>**</p>
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
        <form action="proses_upload_izin.php" method="POST" enctype="multipart/form-data">
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
                <button type="submit" class="modal-kirim-btn">Kirim</button>
            </div>
        </form>
      </div>
  </div>

  
  <script src="js/chart-presensi.js"></script>
  <script>
    // FUNGSI JAVASCRIPT UNTUK MODAL dan Dropdown
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