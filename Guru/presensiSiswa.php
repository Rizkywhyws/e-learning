<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Guru | E-School</title>

  <!-- FONT & ICON -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- CSS -->
  <link rel="stylesheet" href="css/presensiSiswa.css">
</head>

<body>
  <!-- HEADER -->
  <header>
    <img src="../assets/logo-elearning.png" class="logo" alt="E-School">
  </header>

  <!-- MENU -->
  <nav class="menu-row">
    <div class="dropdown">
      <button class="dropbtn">
        <i class="fa-solid fa-database"></i> Data Master
        <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
      </button>
      <div class="dropdown-content">
        <a href="#"><i class="fa-solid fa-user-tie"></i> Dashboard</a>
        <a href="#"><i class="fa-solid fa-user-graduate"></i> Profil Saya</a>
      </div>
    </div>

    <div class="dropdown">
      <button class="dropbtn">
        <i class="fa-solid fa-clipboard-check"></i> Presensi Siswa
        <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
      </button>
      <div class="dropdown-content">
        <a href="#presensi-siswa"><i class="fa-solid fa-pen-clip"></i> Buat Presensi</a>
        <a href="#rekap-presensi"><i class="fa-solid fa-list-check"></i> Rekap Presensi</a>
      </div>
    </div>

    <div class="dropdown">
      <button class="dropbtn">
        <i class="fa-solid fa-school"></i> Pengelolaan Pembelajaran
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
  </nav>

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

  <!-- BAGIAN PRESENSI -->
  <section id="presensi-siswa">
    <h3 class="presensi-header">Presensi Siswa</h3>

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
  </section>

  <!-- MODAL TAMBAH PRESENSI -->
  <div id="presensiModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Tambah Presensi</h2>
      </div>

      <label for="keterangan">Keterangan</label>
      <input type="text" id="keterangan" placeholder="Masukkan Keterangan...">

      <label for="mapel">Mapel</label>
      <select id="mapel">
        <option value="">Pilih Mapel...</option>
        <option value="matematika">Matematika</option>
        <option value="b.indonesia">Bahasa Indonesia</option>
        <option value="desain">Desain Grafis</option>
      </select>

      <label for="waktuMapel">Waktu Mapel</label>
      <input type="text" id="waktuMapel" placeholder="Waktu Mapel..." readonly>

      <label for="toleransi">Waktu Toleransi</label>
      <input type="text" id="toleransi" placeholder="Masukkan Waktu Toleransi...">

      <label for="kelas">Kelas</label>
      <select id="kelas">
        <option value="">Pilih Kelas...</option>
      </select>

      <div class="button-container">
        <button id="btnBatal" class="btn-secondary">Batal</button>
        <button id="btnTambah" class="btn-primary">Tambah</button>
      </div>
    </div>
  </div>

  <!-- BAGIAN REKAP PRESENSI -->
  <section id="rekap-presensi" class="rekap-presensi">
    <div class="rekap-header">
      <h3>Rekap Presensi</h3>
      <div class="date-picker">
        <label for="tanggal">Tanggal:</label>
        <input type="date" id="tanggal" name="tanggal">
      </div>
    </div>

    <div class="rekap-tabel">
      <h3>Kelas X</h3>
      <table>
        <tr>
          <th>No</th>
          <th>Nama</th>
          <th>NIS</th>
          <th>Kelas</th>
          <th>Tanggal</th>
          <th>Waktu Presensi</th>
          <th>Status Presensi</th>
        </tr>
        <?php
          for ($i = 1; $i <= 5; $i++) {
            echo "<tr>
                    <td>$i</td>
                    <td>Nama Siswa $i</td>
                    <td>12345$i</td>
                    <td>X DKV 1</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                  </tr>";
          }
        ?>
      </table>

      <h3>Kelas XI</h3>
      <table>
        <tr>
          <th>No</th>
          <th>Nama</th>
          <th>NIS</th>
          <th>Kelas</th>
          <th>Tanggal</th>
          <th>Waktu Presensi</th>
          <th>Status Presensi</th>
        </tr>
        <?php
          for ($i = 1; $i <= 5; $i++) {
            echo "<tr>
                    <td>$i</td>
                    <td>Nama Siswa $i</td>
                    <td>22345$i</td>
                    <td>XI DKV 1</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                  </tr>";
          }
        ?>
      </table>

      <h3>Kelas XII</h3>
      <table>
        <tr>
          <th>No</th>
          <th>Nama</th>
          <th>NIS</th>
          <th>Kelas</th>
          <th>Tanggal</th>
          <th>Waktu Presensi</th>
          <th>Status Presensi</th>
        </tr>
        <?php
          for ($i = 1; $i <= 5; $i++) {
            echo "<tr>
                    <td>$i</td>
                    <td>Nama Siswa $i</td>
                    <td>32345$i</td>
                    <td>XII DKV 1</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                  </tr>";
          }
        ?>
      </table>
    </div>
  </section>

  <!-- SCRIPT DROPDOWN & MODAL -->
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
      document.addEventListener("click", () => {
        document.querySelectorAll(".dropdown-content").forEach(dc => dc.style.display = "none");
      });
    });

    // Modal
    const modal = document.getElementById("presensiModal");
    const tombolTambah = document.querySelectorAll(".card button");
    const tombolBatal = document.getElementById("btnBatal");

    tombolTambah.forEach(btn => {
      btn.addEventListener("click", () => {
        modal.style.display = "flex";
      });
    });

    tombolBatal.addEventListener("click", () => {
      modal.style.display = "none";
    });

    window.addEventListener("click", (event) => {
      if (event.target === modal) modal.style.display = "none";
    });
  </script>
</body>
</html>
