<?php 
session_start();

// ========== PROTEKSI LOGIN & ROLE ==========
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'guru') {
    header('Location: ../Auth/login.php');
    exit;
}

// ========== AMBIL DATA DARI SESSION ==========
$idAkun = $_SESSION['user_id']; // Sesuaikan dengan cek_login.php
$namaGuru = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Guru';
$nipGuru = isset($_SESSION['nip']) ? $_SESSION['nip'] : '';

$halaman = isset($_GET['page']) ? $_GET['page'] : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pengelolaan Pembelajaran</title>

  <!-- Font dan Icon -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Gunakan style dari dashboard agar tampilannya seragam -->
  <link rel="stylesheet" href="css/dashboard.css">
  <link rel="stylesheet" href="css/style_guru.css">
</head>
<body>

<!-- HEADER (sama seperti dashboard) -->
<div class="sticky-header">
  <header>
    <img src="../assets/logo-elearning.png" class="logo" alt="E-School">
    
    <div class="menu-row">
      <div class="dropdown">
        <button class="dropbtn">
          <i class="fa-solid fa-database"></i> Data Master
          <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
        </button>
        <div class="dropdown-content">
          <a href="#"><i class="fa-solid fa-user-tie"></i> Kelola Guru</a>
          <a href="#"><i class="fa-solid fa-user-graduate"></i> Kelola Siswa</a>
        </div>
      </div>

      <div class="dropdown">
        <button class="dropbtn">
          <i class="fa-solid fa-clipboard-check"></i> Presensi Siswa
          <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
        </button>
        <div class="dropdown-content">
          <a href="#"><i class="fa-solid fa-list-check"></i> Lihat Presensi</a>
          <a href="#"><i class="fa-solid fa-pen-clip"></i> Buat Presensi</a>
        </div>
      </div>

      <button class="dropbtn">
        <i class="fa-solid fa-school"></i>
        <a href="pengelolaanPembelajaran.php" style="text-decoration: none; color: #2e7dff;">Pengelolaan Pembelajaran</a>
      </button>
    </div>
  </header>
</div>

<!-- WELCOME BOX -->
<section class="welcome-box">
  <?php 
  include '../config/db.php';
  
  // Ambil data guru berdasarkan session login
  $idAkun = isset($_SESSION['idAkun']) ? $_SESSION['idAkun'] : '';
  $namaGuru = "Guru";
  $pelajaranSelanjutnya = "-";
  
  if (!empty($idAkun)) {
      // Query untuk mendapatkan nama guru
      $queryGuru = mysqli_query($conn, "SELECT nama FROM dataguru WHERE idAkun = '$idAkun'");
      if ($queryGuru && mysqli_num_rows($queryGuru) > 0) {
          $dataGuru = mysqli_fetch_assoc($queryGuru);
          $namaGuru = $dataGuru['nama'];
      }
      
      // Query untuk mendapatkan jadwal pelajaran selanjutnya (hari ini)
      $hariIni = date('l'); // Nama hari dalam bahasa Inggris
      $waktuSekarang = date('H:i:s');
      
      // Mapping hari Indonesia
      $hariMap = [
          'Monday' => 'Senin',
          'Tuesday' => 'Selasa', 
          'Wednesday' => 'Rabu',
          'Thursday' => 'Kamis',
          'Friday' => 'Jumat',
          'Saturday' => 'Sabtu',
          'Sunday' => 'Minggu'
      ];
      $hariIndonesia = isset($hariMap[$hariIni]) ? $hariMap[$hariIni] : $hariIni;
      
      // Query jadwal guru untuk hari ini, diurutkan berdasarkan waktu mulai
      $queryJadwal = mysqli_query($conn, "
          SELECT j.*, m.namaMapel
          FROM jadwalmapel j
          JOIN mapel m ON j.kodeMapel = m.kodeMapel
          JOIN dataguru dg ON j.nipGuru = dg.NIP
          WHERE dg.idAkun = '$idAkun'
            AND j.hari = '$hariIndonesia'
            AND j.jamMulai > '$waktuSekarang'
          ORDER BY j.jamMulai ASC
          LIMIT 1
      ");

      
      if ($queryJadwal && mysqli_num_rows($queryJadwal) > 0) {
          $jadwal = mysqli_fetch_assoc($queryJadwal);
          $pelajaranSelanjutnya = $jadwal['namaMapel'] . " (Kelas " . $jadwal['Kelas'] . ")";
      }
  }
  ?>
  <h2>Halo! Selamat Datang, <?= htmlspecialchars($namaGuru) ?></h2>
  <p>Jadwal mengajar selanjutnya: <b><?= htmlspecialchars($pelajaranSelanjutnya) ?></b></p>
</section>


<!-- MENU TOMBOL -->

<div class="btn-container">
    <a href="?page=uploudMateri"><button class="btn">Tambah Materi</button></a>

    <?php if ($halaman == 'uploudMateri') { ?>
        <div class="content"><?php include 'uploudMateri.php'; ?></div>
    <?php } ?>

    <a href="?page=buatTugas"><button class="btn">Buat Tugas</button></a>

    <?php if ($halaman == 'buatTugas') { ?>
        <div class="content"><?php include 'buatTugas.php'; ?></div>
    <?php } ?>

    <a href="?page=koreksiTugas"><button class="btn">Koreksi Tugas</button></a>

    <?php if ($halaman == 'koreksiTugas') { ?>
        <div class="content"><?php include 'koreksiTugas.php'; ?></div>
    <?php } ?>

    <a href="?page=buatQuiz"><button class="btn">Buat Quiz</button></a>

    <?php if ($halaman == 'buatQuiz') { ?>
        <div class="content"><?php include 'buatQuiz.php'; ?></div>
    <?php } ?>

    <a href="?page=koreksiQuiz"><button class="btn">Koreksi Quiz</button></a>

    <?php if ($halaman == 'koreksiQuiz') { ?>
        <div class="content"><?php include 'koreksiQuiz.php'; ?></div>
    <?php } ?>
</div>


<!-- SCRIPT DROPDOWN -->
<script>
document.addEventListener("DOMContentLoaded", () => {
  const buttons = document.querySelectorAll(".dropbtn");
  buttons.forEach(btn => {
    btn.addEventListener("click", e => {
      e.stopPropagation();
      const menu = btn.nextElementSibling;
      document.querySelectorAll(".dropdown-content").forEach(dc => {
        if (dc !== menu) dc.style.display = "none";
      });
      menu.style.display = menu.style.display === "block" ? "none" : "block";
    });
  });
  document.addEventListener("click", () => {
    document.querySelectorAll(".dropdown-content").forEach(dc => dc.style.display = "none");
  });
});
</script>

</body>
</html>
