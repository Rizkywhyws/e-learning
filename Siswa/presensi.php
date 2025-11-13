<?php
session_start();
include '../config/db.php';

// ===== CEK LOGIN =====
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../Auth/login.php");
    exit;
}

// AMBIL DATA DARI SESSION
$idAkun   = $_SESSION['user_id'];
$namaSiswa = $_SESSION['nama'];
$email    = $_SESSION['email'];

// ===== AMBIL DATA PRESENSI BERJALAN =====
$queryPresensi = $conn->prepare("
    SELECT * FROM presensisiswa 
    WHERE NIS = ? 
    ORDER BY waktuPresensi DESC LIMIT 1
");

$queryPresensi->bind_param("s", $idAkun);
$queryPresensi->execute();
$presensi = $queryPresensi->get_result()->fetch_assoc();

// Cek apakah presensi masih berjalan
$presensiAktif = false;
if ($presensi) {
    $waktuAkhir = strtotime($presensi['waktu_akhir']);
    $waktuSekarang = time();
    $presensiAktif = ($waktuSekarang <= $waktuAkhir);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Siswa | E-School</title>

  <!-- FONT & ICON -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- CSS -->
  <link rel="stylesheet" href="cssSiswa/presensi.css">
  <style>
    /* Disabled upload button style */
    .upload-izin-btn.disabled {
      color: #9e9e9e;
      border-color: #e0e0e0;
      background: #f5f5f5;
      cursor: not-allowed;
      pointer-events: none;
      opacity: 0.6;
    }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

  <!-- HEADER -->
  <header>
    <img src="../assets/logo-elearning.png" class="logo" alt="E-School">
  </header>

  <!-- MENU -->
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

  <!-- WELCOME -->
  <section class="welcome-box">
    <h2>Halo! Selamat Datang, <?= htmlspecialchars($namaSiswa) ?></h2>
    <p>Jadwal Pelajaran selanjutnya <b>Matematika</b></p>
  </section>

  <!-- SEARCH -->
  <div class="search-bar">
    <input type="text" placeholder="Search...">
    <button><i class="fa-solid fa-magnifying-glass"></i></button>
  </div>

  <!-- KONTEN UTAMA -->
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
              <h3 class="mapel-title"><?= htmlspecialchars($presensi['mapel']) ?></h3>
              <p class="description"><?= htmlspecialchars($presensi['deskripsi'] ?? '(tidak ada deskripsi)') ?></p>

              <div class="presensi-details">
                <p><strong>Presensi Mulai</strong> : <?= htmlspecialchars($presensi['waktu_mulai']) ?></p>
                <p><strong>Presensi Akhir</strong> : <?= htmlspecialchars($presensi['waktu_akhir']) ?></p>
                <p><strong>Lokasi Presensi</strong> : <?= htmlspecialchars($presensi['lokasi']) ?></p>
                <p><strong>Guru Pengampu</strong> : <?= htmlspecialchars($presensi['guru']) ?></p>
              </div>

              <div class="status-box">
                <div class="belum-absen">
                  <p>Anda belum melakukan presensi</p>
                  <small>Klik untuk absen</small>
                  <div class="x-icon-box"><i class="fa-solid fa-xmark"></i></div>
                </div>
                <a href="#" class="upload-izin-btn">
                  <i class="fa-solid fa-cloud-arrow-up"></i> Unggah surat izin/sakit
                </a>
              </div>

            <?php else: ?>
              <p style="text-align: center; color: #666; font-size: 1.1em; padding: 20px 0;">Belum ada presensi berjalan saat ini.</p>

              <div class="status-box">
                <div class="sudah-absen">
                  <p>Tidak ada presensi</p>
                  <small>Anda sudah menyelesaikan semua presensi</small>
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

    <!-- REKAP PRESENSI -->
    <section class="rekap-presensi-section">
      <h1>Rekap Presensi</h1>

      <?php 
        include '#'; 
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

  <script src="js/chart-presensi.js"></script>
</body>
</html>