<?php
session_start();
require_once '../config/db.php'; // Path relatif dari folder admin

// Cek apakah user sudah login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin | E-School</title>
  <link rel="stylesheet" href="css/dashboard.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

  <!-- HEADER HANYA LOGO -->
  <header>
    <div class="logo">
      <img src="../assets/logo-elearning.png" alt="E-School Logo">
    </div>
  </header>

  <!-- DROPDOWN DIPINDAH KE SINI -->
  <div class="menu-row">
    <div class="dropdown">
      <button class="dropbtn">
        <i class="fa-solid fa-database"></i> Data Master
      </button>
      <div class="dropdown-content">
        <a href="guru.php"><i class="fa-solid fa-chalkboard-user"></i> Kelola Guru</a>
        <a href="siswa.php"><i class="fa-solid fa-user-graduate"></i> Kelola Siswa</a>
      </div>
    </div>

    <div class="dropdown">
      <button class="dropbtn">
        <i class="fa-solid fa-school"></i> Pembelajaran
      </button>
      <div class="dropdown-content">
        <a href="mapel.php"><i class="fa-solid fa-book"></i> Kelola Mapel</a>
        <a href="jadwal.php"><i class="fa-solid fa-calendar-days"></i> Kelola Jadwal</a>
      </div>
    </div>
  </div>

  <main>
    <section class="welcome-box">
      <h2>Halo! Selamat Datang, <span>Rizky</span></h2>
      <p>Jadwal mengajar selanjutnya ada di kelas <b>XII AKL 2</b></p>
    </section>

    <div class="search-bar">
      <input type="text" placeholder="Search...">
      <button><i class="fa-solid fa-magnifying-glass"></i></button>
    </div>

    <section class="dashboard">
      <h3><i class="fa-solid fa-chart-line"></i> Dashboard</h3>
      <div class="card-container">
        <div class="card guru">
          <h4><i class="fa-solid fa-chalkboard-user"></i> Guru</h4>
          <p>15</p>
        </div>
        <div class="card siswa">
          <h4><i class="fa-solid fa-user-graduate"></i> Siswa</h4>
          <p>200</p>
        </div>
        <div class="card mapel">
          <h4><i class="fa-solid fa-book"></i> Mapel</h4>
          <p>12</p>
        </div>
        <div class="card jadwal">
          <h4><i class="fa-solid fa-calendar-days"></i> Jadwal</h4>
          <p>12</p>
        </div>
      </div>
    </section>
  </main>
  <!-- SECTION TABEL -->
<section class="data-section">

  <!-- Tombol Pilihan -->
  <div class="data-tabs">
    <button class="tab-btn active" data-target="table-guru">Guru</button>
    <button class="tab-btn" data-target="table-siswa">Siswa</button>
    <button class="tab-btn" data-target="table-mapel">Mapel</button>
    <button class="tab-btn" data-target="table-jadwal">Jadwal</button>
  </div>

  <!-- TABEL GURU -->
  <table class="data-table" id="table-guru">
    <thead>
      <tr>
        <th>NIP</th>
        <th>Nama</th>
        <th>No Telp</th>
      </tr>
    </thead>
    <tbody>
      <tr><td></td><td></td><td></td></tr>
      <tr><td></td><td></td><td></td></tr>
      <tr><td></td><td></td><td></td></tr>
      <tr><td></td><td></td><td></td></tr>
      <tr><td></td><td></td><td></td></tr>
      <tr><td></td><td></td><td></td></tr>
      <tr><td></td><td></td><td></td></tr>
      <tr><td></td><td></td><td></td></tr>
      <tr><td></td><td></td><td></td></tr>
      <tr><td></td><td></td><td></td></tr>
    </tbody>
  </table>

  <!-- TABEL SISWA -->
  <table class="data-table hidden" id="table-siswa">
    <thead>
      <tr>
        <th>Nama</th>
        <th>NIS</th>
        <th>NISN</th>
        <th>Kelas</th>
        <th>Jurusan</th>
      </tr>
    </thead>
    <tbody>
      <tr><td></td><td></td><td></td><td></td><td></td></tr>
      <tr><td></td><td></td><td></td><td></td><td></td></tr>
      <tr><td></td><td></td><td></td><td></td><td></td></tr>
      <tr><td></td><td></td><td></td><td></td><td></td></tr>
      <tr><td></td><td></td><td></td><td></td><td></td></tr>
      <tr><td></td><td></td><td></td><td></td><td></td></tr>
      <tr><td></td><td></td><td></td><td></td><td></td></tr>
    </tbody>
  </table>

  <!-- TABEL MAPEL -->
  <table class="data-table hidden" id="table-mapel">
    <thead>
      <tr>
        <th>Kode Mapel</th>
        <th>Nama Mapel</th>
      </tr>
    </thead>
    <tbody>
      <tr><td></td><td></td></tr>
      <tr><td></td><td></td></tr>
      <tr><td></td><td></td></tr>
      <tr><td></td><td></td></tr>
      <tr><td></td><td></td></tr>
      <tr><td></td><td></td></tr>
      <tr><td></td><td></td></tr>
    </tbody>
  </table>

  <!-- TABEL JADWAL -->
  <table class="data-table hidden" id="table-jadwal">
    <thead>
      <tr>
        <th>Kelas</th>
        <th>Mapel</th>
        <th>Guru</th>
        <th>Hari</th>
        <th>Jam</th>
      </tr>
    </thead>
    <tbody>
      <tr><td></td><td></td><td></td><td></td><td></td></tr>
      <tr><td></td><td></td><td></td><td></td><td></td></tr>
      <tr><td></td><td></td><td></td><td></td><td></td></tr>
      <tr><td></td><td></td><td></td><td></td><td></td></tr>
      <tr><td></td><td></td><td></td><td></td><td></td></tr>
      <tr><td></td><td></td><td></td><td></td><td></td></tr>
      <tr><td></td><td></td><td></td><td></td><td></td></tr>
    </tbody>
  </table>

</section>


</body>
<script>
document.addEventListener("DOMContentLoaded", function () {
  const dropdownButtons = document.querySelectorAll(".dropbtn");

  dropdownButtons.forEach(btn => {
    btn.addEventListener("click", function (e) {
      e.stopPropagation(); // cegah menutup langsung
      const menu = this.nextElementSibling;

      // tutup semua dropdown lain
      document.querySelectorAll(".dropdown-content").forEach(dc => {
        if (dc !== menu) dc.style.display = "none";
      });

      // toggle dropdown ini
      menu.style.display = menu.style.display === "block" ? "none" : "block";
    });
  });

  // klik di luar dropdown → tutup semua
  document.addEventListener("click", function () {
    document.querySelectorAll(".dropdown-content").forEach(dc => {
      dc.style.display = "none";
    });
  });
});
</script>
<script>
document.querySelectorAll(".tab-btn").forEach(btn => {
  btn.addEventListener("click", function () {
    // aktifkan tombol
    document.querySelectorAll(".tab-btn").forEach(b => b.classList.remove("active"));
    this.classList.add("active");

    // tampilkan tabel sesuai target
    let target = this.getAttribute("data-target");
    document.querySelectorAll(".data-table").forEach(tbl => tbl.classList.add("hidden"));
    document.getElementById(target).classList.remove("hidden");
  });
});
</script>

</html>
