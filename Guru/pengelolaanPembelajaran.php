<?php
// pengelolaanPembelajaran.php
session_start();
$halaman = isset($_GET['page']) ? $_GET['page'] : '';
?>

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pengelolaan Pembelajaran</title>
        
        <!-- ====== Tambahkan Google Fonts ====== -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <!-- ====== Hubungkan file CSS utama ====== -->
        <link rel="stylesheet" href="CSS/style_guru.css">
        
        <style>
            /* Terapkan font ke seluruh elemen */
            * {
                font-family: "Poppins", sans-serif;
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            </style>
</head>
<body>

<header>
    <img src="CSS/logo.png" alt="Logo E-School">
    <h1>E-School</h1>

<nav>
    <div class="bgDropdown">
        <div class="dropdown">
            <button class="dropbtn">▼ Data Master</button>
            <div class="dropdown-content">
                <a href="#">Data Guru</a>
                <a href="#">Data Siswa</a>
            </div>
        </div>

        <div class="dropdown">
            <button class="dropbtn">▼ Presensi Siswa</button>
            <div class="dropdown-content">
                <a href="#">Rekap Presensi</a>
                <a href="#">Laporan</a>
            </div>
        </div>

        <div class="dropdown">
            <button class="dropbtn">▼ Pengelolaan Pembelajaran</button>
            <div class="dropdown-content">
                <a href="#">Materi</a>
                <a href="#">Tugas</a>
                <a href="#">Quiz</a>
            </div>
        </div>
    </div>
</nav>
</header>

<div class="welcome-box">
    <?php
    // --- Ambil data dari database (nanti sesuaikan query-nya) ---
    // contoh variable default sementara:
    $namaGuru = "Marta"; // nanti diganti hasil dari database
    $pelajaranSelanjutnya = "Matematika"; // nanti diganti hasil dari database

    // contoh konsep ambil dari database (belum aktif):
    /*
    include 'koneksi.php';
    $idGuru = $_SESSION['id_guru'];
    $query = mysqli_query($koneksi, "SELECT nama_guru FROM guru WHERE id_guru='$idGuru'");
    $dataGuru = mysqli_fetch_assoc($query);
    $namaGuru = $dataGuru['nama_guru'];

    $jadwal = mysqli_query($koneksi, "SELECT nama_mapel FROM jadwal WHERE id_guru='$idGuru' ORDER BY waktu_mulai ASC LIMIT 1");
    $dataJadwal = mysqli_fetch_assoc($jadwal);
    $pelajaranSelanjutnya = $dataJadwal['nama_mapel'];
    */
    ?>
    <h2>Halo! Selamat Datang, <?= htmlspecialchars($namaGuru) ?></h2>
    <p>Jadwal Pelajaran selanjutnya <?= htmlspecialchars($pelajaranSelanjutnya) ?></p>
</div>

<div class="search-box">
    <input type="text" placeholder="Search...">
</div>


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

</body>

<script>
window.addEventListener('scroll', function() {
  const header = document.querySelector('header');
  if (window.scrollY > 20) header.classList.add('scrolled');
  else header.classList.remove('scrolled');
});
</script>


</html>
