<?php
session_start();
require_once '../config/db.php'; // Path relatif dari folder admin

// Cek apakah user sudah login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../Auth/login.php');
    exit;
}
?>
<?php
$countGuru = $conn->query("SELECT COUNT(*) AS total FROM dataGuru")->fetch_assoc()['total'];
$countSiswa = $conn->query("SELECT COUNT(*) AS total FROM dataSiswa")->fetch_assoc()['total'];
$countMapel = $conn->query("SELECT COUNT(*) AS total FROM mapel")->fetch_assoc()['total'];
$countJadwal = $conn->query("SELECT COUNT(*) AS total FROM jadwalmapel")->fetch_assoc()['total'];
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
<div class="sticky-header">
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
        <a href="kelolaguru.php"><i class="fa-solid fa-chalkboard-user"></i> Kelola Guru</a>
        <a href="kelolasiswa.php"><i class="fa-solid fa-user-graduate"></i> Kelola Siswa</a>
      </div>
    </div>

    <div class="dropdown">
      <button class="dropbtn">
        <i class="fa-solid fa-school"></i> Pembelajaran
      </button>
      <div class="dropdown-content">
        <a href="kelolamapel.php"><i class="fa-solid fa-book"></i> Kelola Mapel</a>
        <a href="kelolajadwal.php"><i class="fa-solid fa-calendar-days"></i> Kelola Jadwal</a>
      </div>
    </div>
    <button class="dropbtn">
        <i class="fa-solid fa-right-from-bracket"></i>
        <a href="../Auth/logout.php" onclick="return confirm('Yakin ingin logout?')"style="text-decoration:none; color:#2e7dff;"> Logout</a>
      </button>
  </div>
</div>

  <main>
    <section class="welcome-box">
      <h2>Halo! Selamat Datang, <span>Rizky</span></h2>
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
          <p><?= $countGuru ?></p>
        </div>
        <div class="card siswa">
          <h4><i class="fa-solid fa-user-graduate"></i> Siswa</h4>
          <p><?= $countSiswa ?></p>
        </div>
        <div class="card mapel">
          <h4><i class="fa-solid fa-book"></i> Mapel</h4>
          <p><?= $countMapel ?></p>
        </div>
        <div class="card jadwal">
          <h4><i class="fa-solid fa-calendar-days"></i> Jadwal</h4>
          <p><?= $countJadwal ?></p>
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
      <?php
    $resultGuru = $conn->query("SELECT NIP, nama, noTelp FROM dataGuru ORDER BY nama ASC");

  if ($resultGuru->num_rows > 0) {
      while($row = $resultGuru->fetch_assoc()) {
          echo "<tr>
                  <td>".$row['NIP']."</td>
                  <td>".$row['nama']."</td>
                  <td>".$row['noTelp']."</td>
                </tr>";
      }
  } else {
      echo "<tr><td colspan='3'>Tidak ada data guru</td></tr>";
  }
  ?>
    </tbody>
  </table>

  <!-- TABEL SISWA -->
  <div class="table-container hidden" id="table-siswa">
    <div class="filter-container">
      <select id="filterKelas" class="filter-select">
        <option value="">Semua Kelas</option>
        <?php
        $kelasOptions = $conn->query("SELECT DISTINCT kelas FROM dataSiswa ORDER BY kelas ASC");
        while($kelas = $kelasOptions->fetch_assoc()) {
            echo "<option value='".$kelas['kelas']."'>".$kelas['kelas']."</option>";
        }
        ?>
      </select>
    </div>
    <table class="data-table">
      <thead>
        <tr>
          <th>Nama</th>
          <th>NIS</th>
          <th>NISN</th>
          <th>Kelas</th>
          <th>Jurusan</th>
        </tr>
      </thead>
      <tbody id="siswaTableBody">
       <?php
      $resultSiswa = $conn->query("SELECT nama, NIS, NISN, kelas, jurusan FROM dataSiswa ORDER BY nama ASC");

      if ($resultSiswa->num_rows > 0) {
          while($row = $resultSiswa->fetch_assoc()) {
              echo "<tr data-kelas='".$row['kelas']."'>
                      <td>".$row['nama']."</td>
                      <td>".$row['NIS']."</td>
                      <td>".$row['NISN']."</td>
                      <td>".$row['kelas']."</td>
                      <td>".$row['jurusan']."</td>
                    </tr>";
          }
      } else {
          echo "<tr><td colspan='5'>Tidak ada data siswa</td></tr>";
      }
      ?>
      </tbody>
    </table>
  </div>

  <!-- TABEL MAPEL -->
  <table class="data-table hidden" id="table-mapel">
    <thead>
      <tr>
        <th>Kode Mapel</th>
        <th>Nama Mapel</th>
      </tr>
    </thead>
    <tbody>
 <?php
        $resultMapel = $conn->query("SELECT kodeMapel, namaMapel FROM mapel ORDER BY namaMapel ASC");
        if ($resultMapel->num_rows > 0) {
            while($row = $resultMapel->fetch_assoc()) {
                echo "<tr>
                        <td>".$row['kodeMapel']."</td>
                        <td>".$row['namaMapel']."</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='2'>Tidak ada data mapel</td></tr>";
        }
        ?>
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
    
    // sembunyikan semua tabel dan container
    document.querySelectorAll("#table-guru, #table-siswa, #table-mapel, #table-jadwal")
    .forEach(el => el.classList.add("hidden"));
    
    // tampilkan target yang dipilih
    let targetElement = document.getElementById(target);
    if (targetElement) {
      targetElement.classList.remove("hidden");
    }
  });
});

// Filter Kelas untuk Tabel Siswa
document.getElementById("filterKelas").addEventListener("change", function() {
  let selectedKelas = this.value;
  let rows = document.querySelectorAll("#siswaTableBody tr");
  
  rows.forEach(row => {
    if (selectedKelas === "" || row.getAttribute("data-kelas") === selectedKelas) {
      row.style.display = "";
    } else {
      row.style.display = "none";
    }
  });
});
</script>

</html>