<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Guru | E-School</title>

  <link rel="stylesheet" href="css/dashboard.css">
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
        <a href="#"><i class="fa-solid fa-user-tie"></i> Kelola Guru</a>
        <a href="#"><i class="fa-solid fa-user-graduate"></i> Kelola Siswa</a>
      </div>
    </div>

    <div class="dropdown">
  <button class="dropbtn">
    <i class="fa-solid fa-clipboard-check"></i>
    Presensi Siswa
    <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
  </button>

  <div class="dropdown-content">

    <a href="#">
      <i class="fa-solid fa-list-check"></i> Lihat Presensi
    </a>

    <a href="#">
      <i class="fa-solid fa-pen-clip"></i> Buat Presensi
    </a>

  </div>
</div>


    <div class="dropdown">
  <button class="dropbtn">
    <i class="fa-solid fa-school"></i>
    Pengelolaan Pembelajaran
    <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
  </button>

  <div class="dropdown-content">

    <a href="#">
      <i class="fa-solid fa-book"></i> Tambah Materi
    </a>

    <a href="#">
      <i class="fa-solid fa-file-circle-plus"></i> Tambah Tugas
    </a>

    <a href="#">
      <i class="fa-solid fa-pen-to-square"></i> Koreksi Tugas
    </a>

    <a href="#">
      <i class="fa-solid fa-circle-question"></i> Buat Quiz
    </a>

    <a href="#">
      <i class="fa-solid fa-clipboard-check"></i> Koreksi Quiz
    </a>

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

  <!-- GRAFIK SECTION -->
  <section class="grafik-section">
    <h3>Grafik Pembelajaran</h3>

    <div class="grafik-row">
      
      <!-- CARD 1 -->
      <div class="card-box">
        <div class="card-label">Tugas belum dinilai</div>
      </div>

      <!-- CARD 2 -->
      <div class="card-box">
        <div class="card-label">Quiz belum dinilai</div>
      </div>

      <!-- GRAFIK -->
      <div class="chart-box">
        <img src="chart-placeholder.png" alt="Grafik">
      </div>

    </div>
  </section>

  <!-- TABEL -->
  <section class="tabel-section">
    <h3>Baru Saja Mengumpulkan</h3>

    <table class="data-table">
      <thead>
        <tr>
          <th>Nama Tugas</th>
          <th>Nama Siswa</th>
          <th>NIS</th>
          <th>Kelas</th>
          <th>Keterangan Pengumpulan</th>
        </tr>
      </thead>

      <tbody>
        <tr>
          <td colspan="5" class="no-data-row">Belum ada data</td>
        </tr>
      </tbody>
    </table>
</section>


</body>

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

</html>
