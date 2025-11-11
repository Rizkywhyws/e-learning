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
</head>

<body>

  <!-- HEADER -->
  <header>
    <img src="../assets/logo-elearning.png" class="logo" alt="E-School">
  </header>


  <!-- MENU ROW -->
  <div class="menu-row">

    <div class="dropdown">
      <button class="dropbtn">
        <i class="fa-solid fa-database"></i>
        Data Master
        <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
      </button>
      <div class="dropdown-content">
        <a href="#"><i class="fa-solid fa-user"></i> Dashbboard</a>
        <a href="#"><i class="fa-solid fa-users"></i> Profil Saya</a>
      </div>
    </div>

    <div class="dropdown">
      <button class="dropbtn">
        <i class="fa-solid fa-clipboard-check"></i>
        Presensi
        <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
      </button>
      <div class="dropdown-content">
        <a href="#"><i class="fa-solid fa-check"></i> Lihat Presensi</a>
      </div>
    </div>

    <div class="dropdown">
      <button class="dropbtn">
        <i class="fa-solid fa-school"></i>
        Pengelolaan Pembelajaran
        <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
      </button>
      <div class="dropdown-content">
        <a href="#"><i class="fa-solid fa-book-open"></i> Materi</a>
        <a href="#"><i class="fa-solid fa-file-lines"></i> Tugas</a>
        <a href="#"><i class="fa-solid fa-pen-to-square"></i> Quiz</a>
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








<script>
document.addEventListener("DOMContentLoaded", function () {
  // === DROPDOWN UTAMA ===
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
  document.addEventListener("click", () => {
    document.querySelectorAll(".dropdown-content").forEach(dc => dc.style.display = "none");
  });
  });
</script>
</body>
</html>