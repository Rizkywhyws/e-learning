<?php
require_once "../config/db.php";
include '../config/session.php';

$data = $conn->query("
    SELECT s.NIS, s.NISN, s.nama, s.kelas, s.jurusan, a.email, s.idAkun
    FROM dataSiswa s
    LEFT JOIN akun a ON s.idAkun = a.idAkun
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Siswa | E-School</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/kelolasiswa.css">
  <link rel="stylesheet" href="css/kelolaguru.css">
</head>
<body>

<header>
  <div class="logo">
    <img src="../assets/logo-elearning.png" alt="Logo">
  </div>
</header>

<div class="menu-row">
  <div class="dropdown">
    <button class="dropbtn"><i class="fa-solid fa-database"></i> Data Master</button>
    <div class="dropdown-content">
      <a href="kelolaguru.php"><i class="fa-solid fa-chalkboard-user"></i> Kelola Guru</a>
      <a href="kelolasiswa.php"><i class="fa-solid fa-user-graduate"></i> Kelola Siswa</a>
    </div>
  </div>

  <div class="dropdown">
    <button class="dropbtn"><i class="fa-solid fa-school"></i> Pembelajaran</button>
    <div class="dropdown-content">
      <a href="kelolamapel.php"><i class="fa-solid fa-book"></i> Kelola Mapel</a>
      <a href="kelolajadwal.php"><i class="fa-solid fa-calendar-days"></i> Kelola Jadwal</a>
    </div>
  </div>

  <div class="dropdown">
    <button class="dropbtn"><i class="fa-solid fa-house"></i> Dashboard</button>
    <div class="dropdown-content">
      <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard Utama</a>
    </div>
  </div>
</div>

<main>
  <section class="welcome-box">
    <h2>Halo! Selamat Datang, <span>Admin</span></h2>
    <p>Kelola Data Siswa</p>
  </section>

  <div class="search-bar">
    <input type="text" id="searchInput" placeholder="Search...">
    <button><i class="fa-solid fa-magnifying-glass"></i></button>
  </div>
</main>

<section class="data-section">

  <div class="action-buttons">
      <button id="btnAdd" class="btn green"><i class="fa fa-plus"></i> Add Student</button>
      <button id="btnImport" class="btn purple"><i class="fa fa-file-import"></i> Import Excel/CSV</button>
      <button id="btnEdit" class="btn yellow" disabled><i class="fa fa-pen"></i> Edit Student</button>
      <button id="btnDelete" class="btn red" disabled><i class="fa fa-trash"></i> Delete Student</button>
      <button id="btnNaikKelas" class="btn blue"><i class="fa-solid fa-arrow-up"></i> Naik Kelas</button>
  </div>

  <!-- FILTER KELAS -->
  <div class="filter-box">
      <label><b>Filter Kelas:</b></label>
      <select id="filterKelas">
          <option value="">Semua</option>
          <option value="X-1">X-1</option>
          <option value="X-2">X-2</option>
          <option value="XI-1">XI-1</option>
          <option value="XI-2">XI-2</option>
          <option value="XII-1">XII-1</option>
          <option value="XII-2">XII-2</option>
      </select>
  </div>

<div class="table-wrapper">
  <table class="data-table" id="siswaTable">
      <thead>
          <tr>
              <th>Pilih</th>
              <th>NIS</th>
              <th>NISN</th>
              <th>Nama</th>
              <th>Kelas</th>
              <th>Jurusan</th>
              <th>Email</th>
          </tr>
      </thead>

      <tbody>
      <?php while ($row = $data->fetch_assoc()): ?>
          <tr
            data-nis="<?= $row['NIS'] ?>"
            data-nisn="<?= $row['NISN'] ?>"
            data-nama="<?= htmlspecialchars($row['nama']) ?>"
            data-kelas="<?= $row['kelas'] ?>"
            data-jurusan="<?= htmlspecialchars($row['jurusan']) ?>"
            data-email="<?= htmlspecialchars($row['email']) ?>"
          >
              <td><input type="checkbox" class="row-check"></td>
              <td><?= $row['NIS'] ?></td>
              <td><?= $row['NISN'] ?></td>
              <td><?= htmlspecialchars($row['nama']) ?></td>
              <td><?= $row['kelas'] ?></td>
              <td><?= htmlspecialchars($row['jurusan']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
          </tr>
      <?php endwhile; ?>
      </tbody>
  </table>
</div>

</section>

<!-- ================== MODAL & SCRIPT TETAP SAMA ================== -->
<!-- (Tidak aku potong, kode kamu lanjut di sini tanpa error) -->

