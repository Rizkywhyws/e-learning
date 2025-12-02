<?php
session_start();

// ===== SET TIMEZONE WIB INDONESIA =====
date_default_timezone_set('Asia/Jakarta');

include '../config/db.php';

function generateToken() {
    return strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 5));
}

// ===== CEK LOGIN =====
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../Auth/login.php");
    exit;
}

// AMBIL DATA DARI SESSION
$idAkun   = $_SESSION['user_id'];
$namaGuru = $_SESSION['nama'];
$email    = $_SESSION['email'];
$kelasMengajar = "belum ada jadwal";

// ====== BAGIAN UNTUK AMBIL WAKTU MAPEL (AJAX) ======
if (isset($_GET['getWaktuMapel']) && isset($_GET['kelas']) && isset($_GET['tanggal'])) {
  $kodeMapel = mysqli_real_escape_string($conn, $_GET['getWaktuMapel']);
  $kelasLengkap = mysqli_real_escape_string($conn, $_GET['kelas']);
  $tanggal = mysqli_real_escape_string($conn, $_GET['tanggal']);
  $nip = $_SESSION['nip'];
  
  // 🔹 Konversi tanggal ke nama hari (Indonesia)
  $namaHari = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
  ];
  $hariDariTanggal = $namaHari[date('l', strtotime($tanggal))];

  // 🔹 Cari jadwal berdasarkan kelas LENGKAP (X-1, XI-2, dll)
  $query = mysqli_query($conn, "
    SELECT hari, jamMulai, durasi 
    FROM jadwalmapel 
    WHERE kodeMapel='$kodeMapel' 
    AND nipGuru='$nip'
    AND kelas='$kelasLengkap'
    AND hari='$hariDariTanggal'
    LIMIT 1
  ");

  if ($row = mysqli_fetch_assoc($query)) {
    $mulai = $row['jamMulai'];
    $selesai = date('H:i', strtotime($mulai) + ($row['durasi'] * 60));
    // 🔹 PENTING: Echo TEXT saja, bukan HTML
    echo $row['hari'] . ', ' . $mulai . ' - ' . $selesai;
  } else {
    echo 'Tidak ada jadwal di hari ' . $hariDariTanggal;
  }
  exit;
}

// ===== BAGIAN AMBIL MAPEL BERDASARKAN KELAS (AJAX) =====
if (isset($_GET['getMapelByKelas']) && isset($_GET['tanggal'])) {
  $tingkatKelas = $_GET['getMapelByKelas']; // X, XI, atau XII
  $tanggal = $_GET['tanggal'];
  $nipGuru = $_SESSION['nip'];

  // 🔹 Konversi tanggal ke nama hari (Indonesia)
  $namaHari = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
  ];
  $hariDariTanggal = $namaHari[date('l', strtotime($tanggal))];

  // 🔹 PENTING: Filter hanya mapel yang DIAMPU oleh guru ini
  $query = mysqli_query($conn, "
    SELECT DISTINCT m.kodeMapel, m.namaMapel 
    FROM mapel m
    JOIN jadwalmapel j ON m.kodeMapel = j.kodeMapel
    WHERE j.kelas LIKE '$tingkatKelas%'
    AND j.nipGuru = '$nipGuru'
    AND j.hari = '$hariDariTanggal'
    ORDER BY m.namaMapel ASC
  ");

  if (mysqli_num_rows($query) > 0) {
    $options = "<option value='' disabled selected hidden>Pilih Mapel...</option>";
    while ($row = mysqli_fetch_assoc($query)) {
      $options .= "<option value='{$row['kodeMapel']}'>{$row['namaMapel']}</option>";
    }
    echo $options;
  } else {
    echo "<option value='' disabled selected>Tidak ada jadwal di hari $hariDariTanggal</option>";
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $idBuatPresensi = "PR" . rand(1000, 9999);
  $keterangan = $_POST['keterangan'];
  $mapel = $_POST['mapel'];
  $toleransi = $_POST['toleransi'];
  $kelas = $_POST['kelas'];
  $nipGuru = $_SESSION['nip'];
  $token = generateToken();

  // 🔹 TAMBAHKAN: Ambil nama mapel dari kodeMapel
  $qNamaMapel = mysqli_query($conn, "SELECT namaMapel FROM mapel WHERE kodeMapel='$mapel' LIMIT 1");
  $dataMapel = mysqli_fetch_assoc($qNamaMapel);
  $namaMapel = $dataMapel['namaMapel'];

  // 🔹 Ambil data jadwal dari tabel jadwalmapel sesuai mapel, guru, dan kelas
  $qJadwal = mysqli_query($conn, "
    SELECT idJadwalMapel, jamMulai, durasi 
    FROM jadwalmapel 
    WHERE kodeMapel='$mapel' 
    AND nipGuru='$nipGuru'
    AND kelas LIKE '%$kelas%'
    LIMIT 1
  ");
  $jadwal = mysqli_fetch_assoc($qJadwal);

  // 🔹 Validasi kalau jadwal tidak ditemukan
  if (!$jadwal) {
    echo "<script>alert('Jadwal tidak ditemukan untuk mapel dan kelas ini!');</script>";
    exit;
  }

  // 🔹 Ambil detail jadwal
  $idJadwalMapel = $jadwal['idJadwalMapel'];
  $hariMapel = $jadwal['hari'];
  $jamMulai = $jadwal['jamMulai'];
  $durasi = $jadwal['durasi'];
  $tanggalDipilih = $_POST['tanggal'] ?? date('Y-m-d');

    $namaHari = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
  ];
  $hariDariTanggal = $namaHari[date('l', strtotime($tanggalDipilih))];

  // ===== VALIDASI: TANGGAL TIDAK BOLEH MUNDUR =====
  $tanggalHariIni = date('Y-m-d');
  if ($tanggalDipilih < $tanggalHariIni) {
    echo "<script>
            alert('⚠️ Tanggal tidak boleh mundur!\\n\\nTanggal yang dipilih: $tanggalDipilih\\nTanggal hari ini: $tanggalHariIni\\n\\nSilakan pilih tanggal hari ini atau masa depan.');
            window.location='presensiSiswa.php';
          </script>";
    exit;
  }

  // ===== VALIDASI: JAM TIDAK BOLEH MUNDUR (UNTUK HARI INI) =====
  $waktuSekarang = date('Y-m-d H:i:s');
  $waktuDimulaiPresensi = $tanggalDipilih . ' ' . $jamMulai;
  
  // Jika tanggal yang dipilih adalah HARI INI, cek apakah jam mapel sudah lewat
  if ($tanggalDipilih == $tanggalHariIni) {
    // if ($hariDariTanggal == $hariMapel) {
    if (strtotime($waktuDimulaiPresensi) < strtotime($waktuSekarang)) {
      $jamSekarang = date('H:i:s');
      echo "<script>
              alert('⚠️ Waktu mapel sudah terlewat!\\n\\nMapel: $namaMapel\\nJam Mapel: $jamMulai\\nJam Sekarang: $jamSekarang\\n\\nAnda tidak bisa membuat presensi untuk waktu yang sudah lewat.\\n\\nSilakan pilih tanggal besok atau mapel lain.');
              window.location='presensiSiswa.php';
            </script>";
      exit;
    }
  }

  // ========== VALIDASI ANTI DUPLIKASI BERDASARKAN TANGGAL & WAKTU MAPEL ==========
  $cekDuplikasi = mysqli_query($conn, "
    SELECT bp.idBuatPresensi, bp.keterangan, j.jamMulai, m.namaMapel
    FROM buatpresensi bp
    JOIN jadwalmapel j ON bp.idJadwalMapel = j.idJadwalMapel
    JOIN mapel m ON j.kodeMapel = m.kodeMapel
    WHERE j.idJadwalMapel = '$idJadwalMapel'
    AND DATE(bp.waktuDimulai) = '$tanggalDipilih'
    AND j.jamMulai = '$jamMulai'
  ");

  if (mysqli_num_rows($cekDuplikasi) > 0) {
    $dataDuplikat = mysqli_fetch_assoc($cekDuplikasi);
    echo "<script>
            alert('⚠️ Presensi sudah dibuat!\\n\\nKelas: $kelas\\nMapel: {$dataDuplikat['namaMapel']}\\nTanggal: $tanggalDipilih\\nWaktu: $jamMulai\\nKeterangan: {$dataDuplikat['keterangan']}\\n\\nSilakan pilih waktu atau kelas lain.');
            window.location='presensiSiswa.php';
          </script>";
    exit;
  }
  // ============================================================================

  // Waktu saat presensi dibuat (sekarang) - TIMEZONE WIB
  $waktuDibuat = date('Y-m-d H:i:s');

  // Gabungkan tanggal yang dipilih dengan jam mulai
  $waktuDimulai = $tanggalDipilih . ' ' . $jadwal['jamMulai'];

  // Hitung waktu tutup dari durasi
  $waktuDitutup = date('Y-m-d H:i:s', strtotime("+{$jadwal['durasi']} minutes", strtotime($waktuDimulai)));

  // 🔹 Simpan ke tabel buatpresensi
  $sql = "INSERT INTO buatpresensi 
          (idBuatPresensi, NIP, idJadwalMapel, waktuDibuat, waktuDimulai, waktuDitutup, toleransiWaktu, keterangan, Token)
          VALUES 
          ('$idBuatPresensi', '$nipGuru', '$idJadwalMapel', '$waktuDibuat', '$waktuDimulai', '$waktuDitutup', '$toleransi', '$keterangan', '$token')";

  if (mysqli_query($conn, $sql)) {
    // 🔹 UBAH: Tampilkan nama mapel, bukan kode mapel
    echo "<script>
            alert('✅ Presensi berhasil ditambahkan!\\n\\nMapel: $namaMapel\\nKelas: $kelas\\nTanggal: $tanggalDipilih\\nWaktu: $jamMulai\\n\\nDibuat pada: $waktuDibuat WIB');
            window.location='presensiSiswa.php';
          </script>";
  } else {
    echo "<script>alert('Gagal menambahkan presensi: " . mysqli_error($conn) . "');</script>";
  }
}


// ===== VARIABEL UNTUK TANGGAL MINIMUM (HARI INI) =====
$tanggalMinimum = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Guru | E-School</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <link rel="stylesheet" href="css/presensiSiswa.css">
</head>

<body>
  <header>
    <img src="../assets/logo-elearning.png" class="logo" alt="E-School">
  </header>

  <nav class="menu-row">
    <div class="dropdown">
      <button class="dropbtn">
        <i class="fa-solid fa-database"></i> Data Master
        <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
      </button>
      <div class="dropdown-content">
        <a href="dashboard.php"><i class="fa-solid fa-user-tie"></i> Dashboard</a>
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
      <button class="dropbtn">
    <i class="fa-solid fa-right-from-bracket"></i>
    <a href="../Auth/logout.php" onclick="return confirm('Yakin ingin logout?')"style="text-decoration:none; color:#2e7dff;"> Logout</a>
  </button>
  </nav>

  <!-- WELCOME -->
  <section class="welcome-box">
    <h2>Halo! Selamat Datang, <?= htmlspecialchars($namaGuru) ?></h2>
    <p>Jadwal mengajar selanjutnya ada di kelas <b><?= htmlspecialchars($kelasMengajar) ?></b></p>
  </section>

  <div class="search-bar">
    <input type="text" placeholder="Search...">
    <button><i class="fa-solid fa-magnifying-glass"></i></button>
  </div>

  <section id="presensi-siswa">
    <h3 class="presensi-header">Presensi Siswa</h3>

    <div class="attendance-cards">
      <div class="card card-x">
        <h3>Kelas X</h3>
        <button data-kelas="X"><i class="fa-solid fa-plus"></i> Tambah Presensi</button>
      </div>

      <div class="card card-xi">
        <h3>Kelas XI</h3>
        <button data-kelas="XI"><i class="fa-solid fa-plus"></i> Tambah Presensi</button>
      </div>

      <div class="card card-xii">
        <h3>Kelas XII</h3>
        <button data-kelas="XII"><i class="fa-solid fa-plus"></i> Tambah Presensi</button>
      </div>
    </div>
  </section>

<div id="presensiModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Tambah Presensi</h2>
    </div>

    <form method="POST">
      <label for="keterangan">Keterangan</label>
      <input type="text" name="keterangan" id="keterangan" placeholder="Masukkan Keterangan..." required>

    <label for="kelas">Kelas</label>
    <select name="kelas" id="kelas" required>
      <option value="" disabled selected>Pilih Kelas...</option>
      <?php
        $kelasQuery = mysqli_query($conn, "SELECT DISTINCT kelas FROM jadwalmapel ORDER BY kelas ASC");
        while ($k = mysqli_fetch_assoc($kelasQuery)) {
          echo "<option value='{$k['kelas']}'>{$k['kelas']}</option>";
        }
      ?>
    </select>

    <label for="waktuDimulai">Pilih Tanggal...</label>
    <input type="date" name="tanggal" id="inputTanggal" min="<?= $tanggalMinimum ?>" value="<?= $tanggalMinimum ?>" required>

    <label for="mapel">Mapel</label>
    <select name="mapel" id="mapel" required>
      <option value="" disabled selected>Pilih Mapel...</option>
      <?php
        $mapelQuery = mysqli_query($conn, "SELECT * FROM mapel");
        while ($m = mysqli_fetch_assoc($mapelQuery)) {
          echo "<option value='{$m['kodeMapel']}'>{$m['namaMapel']}</option>";
        }
      ?>
    </select>

      <label for="waktuMapel">Waktu Mapel</label>
      <input type="text" name="waktuMapel" id="waktuMapel" placeholder="Waktu Mapel..." readonly required>

      <label for="toleransi">Waktu Toleransi</label>
      <div style="display: flex; align-items: center; gap: 5px;">
        <input type="number" name="toleransi" id="toleransi" placeholder="Masukkan Waktu Toleransi..." min="0" max="60" required style="flex:1;">
        <span>menit</span>
      </div>

      <label for="token">Token Presensi</label>
      <div class="token-presensi-wrapper">
          <input type="text" name="token" id="token" value="<?= generateToken() ?>" readonly required class="token-presensi-input">
          <button type="button" onclick="regenerateToken()" class="btn-generate-token" title="Generate Token Baru"><i class="fa-solid fa-rotate"></i></button>
      </div>
      <small style="color:#666; font-size:12px;">Token ini akan digunakan siswa untuk presensi</small>

      <div class="button-container">
        <button type="button" id="btnBatal" class="btn-secondary">Batal</button>
        <button type="submit" class="btn-primary">Tambah</button>
      </div>
    </form>
  </div>
</div>

<section id="rekap-presensi" class="rekap-presensi">
  <div class="rekap-header">
    <h3>Rekap Presensi</h3>
    <form method="GET" class="date-picker">
      <label for="tanggal">Tanggal:</label>
      <input type="date" id="tanggal" name="tanggal" value="<?= $_GET['tanggal'] ?? date('Y-m-d') ?>" onchange="this.form.submit()">
      
      <?php
        // Pertahankan semua filter golongan saat ganti tanggal
        if (isset($_GET['golongan_X'])) echo "<input type='hidden' name='golongan_X' value='{$_GET['golongan_X']}'>";
        if (isset($_GET['golongan_XI'])) echo "<input type='hidden' name='golongan_XI' value='{$_GET['golongan_XI']}'>";
        if (isset($_GET['golongan_XII'])) echo "<input type='hidden' name='golongan_XII' value='{$_GET['golongan_XII']}'>";
      ?>
      
    </form>
  </div>

  <div class="rekap-tabel">
    <?php
      $tanggalDipilih = $_GET['tanggal'] ?? date('Y-m-d');
      $tingkatan = ['X', 'XI', 'XII'];

      foreach ($tingkatan as $tingkat) {
        $golonganKey = 'golongan_' . $tingkat;
        $golonganDipilih = $_GET[$golonganKey] ?? '';
        
        // Mulai section per kelas
        echo "<div class='kelas-section'>";
        // Header dengan judul dan filter (Berubah: Golongan dipindah ke dalam kelas-header)
        echo "<div class='kelas-header'>";
        echo "<h3>Kelas $tingkat</h3>";
        
        // Form filter golongan
        echo "<form method='GET' class='golongan-filter'>";
        echo "<input type='hidden' name='tanggal' value='{$tanggalDipilih}'>";
        
        // Pertahankan filter golongan kelas lain
        foreach ($tingkatan as $t) {
          if ($t != $tingkat) {
            $otherKey = 'golongan_' . $t;
            if (isset($_GET[$otherKey])) {
              echo "<input type='hidden' name='{$otherKey}' value='{$_GET[$otherKey]}'>";
            }
          }
        }
        
        echo "<label for='golongan_{$tingkat}'>Golongan:</label>";
        echo "<select name='{$golonganKey}' id='golongan_{$tingkat}' onchange='this.form.submit()'>";
        echo "<option value=''>Semua Kelas $tingkat</option>";
        
        // Ambil daftar kelas untuk tingkat ini
        $kelasList = mysqli_query($conn, "SELECT DISTINCT kelas FROM datasiswa WHERE kelas LIKE '{$tingkat}-%' ORDER BY kelas ASC");
        while ($row = mysqli_fetch_assoc($kelasList)) {
          $selected = $golonganDipilih === $row['kelas'] ? 'selected' : '';
          echo "<option value='{$row['kelas']}' $selected>{$row['kelas']}</option>";
        }
        
        echo "</select>";
        echo "</form>";
        echo "</div>"; // Tutup kelas-header

        // Tabel presensi
        echo "<table>
                <tr>
                  <th>No</th>
                  <th>Nama</th>
                  <th>NIS</th>
                  <th>Jurusan</th>
                  <th>Kelas</th>
                  <th>Tanggal</th>
                  <th>Waktu Presensi</th>
                  <th>Status</th>
                  <th>File</th>
                </tr>";

        // Query data
        $query = "
          SELECT 
            d.nama, d.NIS, d.jurusan, d.kelas, 
            DATE(p.waktuPresensi) AS tanggal, 
            TIME(p.waktuPresensi) AS jam, 
            p.status,
            p.filePath
          FROM presensisiswa p
          JOIN datasiswa d ON p.NIS = d.NIS
          WHERE d.kelas LIKE '{$tingkat}-%'
            AND DATE(p.waktuPresensi) = '$tanggalDipilih'
        ";

        if (!empty($golonganDipilih)) {
          $query .= " AND d.kelas = '$golonganDipilih'";
        }

        $query .= " ORDER BY d.nama ASC";
        $result = mysqli_query($conn, $query);
        $no = 1;

      if (mysqli_num_rows($result) > 0) {
          while ($row = mysqli_fetch_assoc($result)) {

              $file = $row['filePath'];
              $linkFile = $file 
                  ? "<a href='../uploads/$file' target='_blank'>Lihat</a>"
                  : "-";

              echo "<tr>
                      <td>{$no}</td>
                      <td>{$row['nama']}</td>
                      <td>{$row['NIS']}</td>
                      <td>{$row['jurusan']}</td>
                      <td>{$row['kelas']}</td>
                      <td>{$row['tanggal']}</td>
                      <td>{$row['jam']}</td>
                      <td>{$row['status']}</td>
                      <td>{$linkFile}</td>
                    </tr>";
                  $no++;
          }
        } else {
          echo "<tr><td colspan='8'>Tidak ada data presensi untuk tanggal ini.</td></tr>";
        }
        
        echo "</table>";
        echo "</div>"; // Tutup kelas-section
      }
    ?>
  </div>
</section>

<script>
  //generate token baru
  function regenerateToken() {
    const tokenInput = document.getElementById('token');
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let newToken = '';
    for (let i = 0; i < 5; i++) {
      newToken += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    tokenInput.value = newToken;
  }

  document.addEventListener("DOMContentLoaded", function () {
    // === DROPDOWN MENU ATAS ===
    const buttons = document.querySelectorAll(".dropbtn");
    buttons.forEach(btn => {
      btn.addEventListener("click", function (e) {
        e.stopPropagation();
        const menu = this.nextElementSibling;
        document.querySelectorAll(".dropdown-content").forEach(dc => {
          if (dc !== menu) dc.style.display = "none";
        });
        menu.style.display = menu.style.display === "block" ? "none" : "block";
      });
    });
    document.addEventListener("click", () => {
      document.querySelectorAll(".dropdown-content").forEach(dc => dc.style.display = "none");
    });

    // === MODAL TAMBAH PRESENSI ===
    const modal = document.getElementById("presensiModal");
    const tombolTambah = document.querySelectorAll(".card button");
    const tombolBatal = document.getElementById("btnBatal");
    const selectKelas = document.getElementById("kelas");
    const mapelSelect = document.getElementById("mapel");
    const waktuInput = document.getElementById("waktuMapel");
    const inputTanggal = document.getElementById("inputTanggal");
    const hariIni = new Date().toISOString().split('T')[0];

    // Set minimum date ke hari ini
    inputTanggal.setAttribute('min', hariIni);

    // 🔹 FUNGSI HELPER: Load mapel berdasarkan tingkat kelas DAN tanggal
    function loadMapelByKelasAndDate(tingkatKelas, tanggal) {
      fetch(`presensiSiswa.php?getMapelByKelas=${tingkatKelas}&tanggal=${tanggal}`)
        .then(res => res.text())
        .then(data => {
          mapelSelect.innerHTML = data;
          waktuInput.value = ""; // reset waktu
        })
        .catch(() => {
          mapelSelect.innerHTML = "<option value='' disabled>Gagal memuat mapel...</option>";
        });
    }

    // === VALIDASI TANGGAL: TIDAK BISA MUNDUR ===
    inputTanggal.addEventListener('change', function() {
      const tanggalDipilih = this.value;
      if (tanggalDipilih < hariIni) {
        alert('⚠️ Tanggal tidak boleh mundur! Pilih tanggal hari ini atau masa depan.');
        this.value = hariIni;
      } else {
        // 🔹 Reload mapel saat tanggal berubah
        const tombolAktif = document.querySelector(".card button.active");
        if (tombolAktif) {
          const tingkatKelas = tombolAktif.getAttribute("data-kelas");
          loadMapelByKelasAndDate(tingkatKelas, tanggalDipilih);
        }
      }
    });

    // Cegah input manual tanggal yang salah
    inputTanggal.addEventListener('keydown', function(e) {
      e.preventDefault();
    });

    // === BUKA MODAL + LOAD MAPEL & KELAS ===
    tombolTambah.forEach(btn => {
      btn.addEventListener("click", () => {
        modal.style.display = "flex";
        tombolTambah.forEach(b => b.classList.remove("active"));
        btn.classList.add("active");

        const tingkatKelas = btn.getAttribute("data-kelas"); // X, XI, atau XII
        const tanggalDipilih = inputTanggal.value || hariIni;

        // 🔹 Load mapel berdasarkan tingkat kelas dan tanggal
        loadMapelByKelasAndDate(tingkatKelas, tanggalDipilih);

        // 🔹 Isi dropdown kelas sesuai tingkat
        const kelasList = {
          "X": ["X-1", "X-2"],
          "XI": ["XI-1", "XI-2"],
          "XII": ["XII-1", "XII-2"]
        };
        
        if (kelasList[tingkatKelas]) {
          selectKelas.innerHTML = `
            <option value="" disabled selected hidden>Pilih Kelas...</option>
            ${kelasList[tingkatKelas].map(k => `<option value="${k}">${k.replace('-', ' DKV ')}</option>`).join('')}
          `;
        }
      });
    });

    // === TOMBOL BATAL ===
    tombolBatal.addEventListener("click", () => {
      modal.style.display = "none";
      modal.querySelector("form").reset();
      inputTanggal.value = hariIni;
    });

    // GA RESET KALAU PENCET LUAR FORM
    window.addEventListener("click", (event) => {
      if (event.target === modal) modal.style.display = "none";
    });

    // === UPDATE WAKTU MAPEL SAAT MAPEL DIGANTI ===
    mapelSelect.addEventListener("change", function() {
      const kodeMapel = this.value;
      const kelasDipilih = selectKelas.value || document.querySelector(".card button.active")?.getAttribute("data-kelas");
      const tanggalDipilih = inputTanggal.value || hariIni;

      if (kodeMapel && kelasDipilih) {
        const kelasFinal = kelasDipilih
          .replace(/\s*DKV\s*/i, '-')
          .replace(/\s+/g, '-')
          .trim();

        // 🔹 Kirim tanggal juga
        fetch(`presensiSiswa.php?getWaktuMapel=${kodeMapel}&kelas=${kelasFinal}&tanggal=${tanggalDipilih}`)
          .then(res => res.text())
          .then(data => waktuInput.value = data)
          .catch(() => waktuInput.value = "Gagal memuat jadwal");
      } else {
        waktuInput.value = "";
      }
    });

    // === AUTO UPDATE WAKTU MAPEL SAAT KELAS BERUBAH ===
    selectKelas.addEventListener("change", function() {
      const kelasLengkap = this.value; // Contoh: "X-1", "XI-2"
      const kodeMapel = mapelSelect.value;
      const tanggalDipilih = inputTanggal.value || hariIni;

      if (kodeMapel && kelasLengkap) {
        const kelasFinal = kelasLengkap
          .replace(/\s*DKV\s*/i, '-')
          .replace(/\s+/g, '-')
          .trim();

        // 🔹 Kirim tanggal juga
        fetch(`presensiSiswa.php?getWaktuMapel=${kodeMapel}&kelas=${kelasFinal}&tanggal=${tanggalDipilih}`)
          .then(res => res.text())
          .then(data => waktuInput.value = data)
          .catch(() => waktuInput.value = "Gagal memuat jadwal");
      } else {
        waktuInput.value = "";
      }
    });
  });
</script>
</body>
</html>