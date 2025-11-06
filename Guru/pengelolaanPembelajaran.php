<?php 
session_start();
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
  $namaGuru = "Marta"; 
  $pelajaranSelanjutnya = "Matematika";
  ?>
  <h2>Halo! Selamat Datang, <?= htmlspecialchars($namaGuru) ?></h2>
  <p>Jadwal pelajaran selanjutnya: <b><?= htmlspecialchars($pelajaranSelanjutnya) ?></b></p>
</section>


<!-- MENU TOMBOL -->

<div class="btn-container">
    <a href="?page=tambahMateri"><button class="btn">Tambah Materi</button></a>

    <?php if ($halaman == 'tambahMateri') { ?>
        <div class="content"><?php include 'tambahMateri.php'; ?></div>
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
