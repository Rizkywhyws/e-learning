<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Guru | E-School</title>

  <link rel="stylesheet" href="css/presensiSiswa.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

  <!-- HEADER -->
  <header>
    <img src="../assets/logo-elearning.png" class="logo" alt="E-School">
  </header>

  <!-- MENU -->
  <div class="menu-row">

    <div class="dropdown">
      <button class="dropbtn">
        <i class="fa-solid fa-database"></i>
        Data Master
        <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
      </button>
      <div class="dropdown-content">
        <a href="#"><i class="fa-solid fa-user-tie"></i> Dashboard</a>
        <a href="#"><i class="fa-solid fa-user-graduate"></i> Profil Saya</a>
      </div>
    </div>

    <div class="dropdown">
  <button class="dropbtn">
    <i class="fa-solid fa-clipboard-check"></i>
    Presensi Siswa
    <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
  </button>
  <div class="dropdown-content">
    <a href="#"><i class="fa-solid fa-list-check"></i> Presensi</a>
    <a href="#"><i class="fa-solid fa-pen-clip"></i> Buat Presensi</a>
  </div>
</div>


    <div class="dropdown">
  <button class="dropbtn">
    <i class="fa-solid fa-school"></i>
    Pengelolaan Pembelajaran
    <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
  </button>
  <div class="dropdown-content">
    <a href="#"><i class="fa-solid fa-book"></i> Tambah Materi</a>
    <a href="#"><i class="fa-solid fa-file-circle-plus"></i> Tambah Tugas</a>
    <a href="#"><i class="fa-solid fa-pen-to-square"></i> Koreksi Tugas</a>
    <a href="#"><i class="fa-solid fa-circle-question"></i> Buat Quiz</a>
    <a href="#"><i class="fa-solid fa-clipboard-check"></i> Koreksi Quiz</a>
  </div>
</div>


  </div>
  <!-- WELCOME -->
  <section class="welcome-box">
    <h2>Halo! Selamat Datang, Bintang</h2>
    <p>Jadwal mengajar selanjutnya ada di kelas <b>X DKV 2</b></p>
  </section>

  <!-- SEARCH -->
  <div class="search-bar">
    <input type="text" placeholder="Search...">
    <button><i class="fa-solid fa-magnifying-glass"></i></button>
  </div>

  <!-- SCRIPT DROPDOWN -->
<script>
document.addEventListener("DOMContentLoaded", function () {
  const buttons = document.querySelectorAll(".dropbtn");

  buttons.forEach(btn => {
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      const menu = this.nextElementSibling;

      document.querySelectorAll(".dropdown-content").forEach(content => {
        if (content !== menu) content.style.display = "none";
      });

      menu.style.display = menu.style.display === "block" ? "none" : "block";
    });
  });

  document.addEventListener("click", function () {
    document.querySelectorAll(".dropdown-content").forEach(dc => dc.style.display = "none");
  });
});
</script>

 <!-- BAGIAN KELAS PRESENSI -->
  <h3 class="section-title">Presensi Siswa</h3>

  <div class="attendance-cards">
    <div class="card card-x">
      <h3>Kelas X</h3>
      <button><i class="fa-solid fa-plus"></i> Tambah Presensi</button>
    </div>

    <div class="card card-xi">
      <h3>Kelas XI</h3>
      <button><i class="fa-solid fa-plus"></i> Tambah Presensi</button>
    </div>

    <div class="card card-xii">
      <h3>Kelas XII</h3>
      <button><i class="fa-solid fa-plus"></i> Tambah Presensi</button>
    </div>
  </div>


</body>


