<?php
include '../config/db.php'; // sesuaikan dengan lokasi file koneksi kamu

// ====== BAGIAN UNTUK AMBIL WAKTU MAPEL (AJAX) ======
if (isset($_GET['getWaktuMapel']) && isset($_GET['kelas'])) {
  $kodeMapel = $_GET['getWaktuMapel'];
  $kelas = $_GET['kelas'];
  $nip = 123345; // nanti diganti $_SESSION['nip'] kalau login udah nyala

  $query = mysqli_query($conn, "
    SELECT hari, jamMulai, durasi 
    FROM jadwalmapel 
    WHERE kodeMapel='$kodeMapel' 
    AND nipGuru='$nip'
    AND kelas LIKE '%$kelas%'
    LIMIT 1
  ");

  if ($row = mysqli_fetch_assoc($query)) {
    $mulai = $row['jamMulai'];
    $selesai = date('H:i', strtotime($mulai) + ($row['durasi'] * 60));
    echo $row['hari'] . ', ' . $mulai . ' - ' . $selesai;
  } else {
    echo 'Jadwal tidak ditemukan';
  }
  exit;
}

// ===== BAGIAN AMBIL MAPEL BERDASARKAN KELAS (AJAX) =====
if (isset($_GET['getMapelByKelas'])) {
  $kelas = $_GET['getMapelByKelas'];
  $nipGuru = 123345; // nanti bisa diganti $_SESSION['nip']

  $query = mysqli_query($conn, "
    SELECT DISTINCT m.kodeMapel, m.namaMapel 
    FROM mapel m
    JOIN jadwalmapel j ON m.kodeMapel = j.kodeMapel
    WHERE j.kelas LIKE '%$kelas%' AND j.nipGuru = '$nipGuru'
  ");

  $options = "<option value='' disabled selected hidden>Pilih Mapel...</option>";
  while ($row = mysqli_fetch_assoc($query)) {
    $options .= "<option value='{$row['kodeMapel']}'>{$row['namaMapel']}</option>";
  }

  echo $options;
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $idBuatPresensi = "PR" . rand(1000, 9999);
  $keterangan = $_POST['keterangan'];
  $mapel = $_POST['mapel'];
  $toleransi = $_POST['toleransi'];
  $kelas = $_POST['kelas'];
  $nipGuru = 123345;
  $idLokasi = 'LKSSMK4'; // lokasi SMKN 4 Jember

  // 🔹 Ambil data jadwal dari tabel jadwalmapel sesuai mapel, guru, dan kelas
  $qJadwal = mysqli_query($conn, "
    SELECT idJadwal, jamMulai, durasi 
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

    // 🔹 Cek apakah keterangan sudah ada di tabel buatpresensi untuk hari ini, kelas, dan mapel yang sama
  $cekKeterangan = mysqli_query($conn, "
    SELECT bp.idBuatPresensi 
    FROM buatpresensi bp
    JOIN jadwalmapel j ON bp.idJadwalMapel = j.idJadwal
    WHERE bp.keterangan = '$keterangan'
    AND j.kelas = '$kelas'
    AND j.kodeMapel = '$mapel'
    AND DATE(bp.waktuDibuat) = CURDATE()
  ");

  if (mysqli_num_rows($cekKeterangan) > 0) {
    echo "<script>
            alert('Presensi dengan keterangan ini sudah dibuat untuk kelas dan mapel ini hari ini!');
            window.location='presensiSiswa.php';
          </script>";
    exit;
  }
  
  // 🔹 Ambil detail jadwal
  $idJadwalMapel = $jadwal['idJadwal'];
  $jamMulai = $jadwal['jamMulai'];
  $durasi = $jadwal['durasi'];

  // 🔹 Hitung waktu mulai dan waktu tutup otomatis berdasarkan jam dan durasi
  $waktuMulai = date('Y-m-d ') . $jamMulai; // tanggal hari ini + jam mulai
  $waktuDitutup = date('Y-m-d H:i:s', strtotime("$waktuMulai +$durasi minutes"));

  // 🔹 Simpan ke tabel buatpresensi
  $sql = "INSERT INTO buatpresensi 
          (idBuatPresensi, NIP, idJadwalMapel, waktuDibuat, waktuDitutup, toleransiWaktu, keterangan, idLokasi)
          VALUES 
          ('$idBuatPresensi', '$nipGuru', '$idJadwalMapel', '$waktuMulai', '$waktuDitutup', '$toleransi', '$keterangan', '$idLokasi')";

  if (mysqli_query($conn, $sql)) {
    echo "<script>alert('Presensi berhasil ditambahkan!'); window.location='presensiSiswa.php';</script>";
  } else {
    echo "<script>alert('Gagal menambahkan presensi: " . mysqli_error($conn) . "');</script>";
  }
}
?>

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

<!-- MODAL TAMBAH PRESENSI -->
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
        <input type="number" name="toleransi" id="toleransi" placeholder="Masukkan Waktu Toleransi..." required style="flex:1;">
        <span>menit</span>
      </div>

      <div class="button-container">
        <button type="button" id="btnBatal" class="btn-secondary">Batal</button>
        <button type="submit" class="btn-primary">Tambah</button>
      </div>
    </form>
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

  // === BUKA MODAL + LOAD MAPEL & KELAS ===
  tombolTambah.forEach(btn => {
    btn.addEventListener("click", () => {
      modal.style.display = "flex";
      tombolTambah.forEach(b => b.classList.remove("active"));
      btn.classList.add("active");

      const kelasDipilih = btn.getAttribute("data-kelas");

      // 🔹 ambil daftar mapel dari PHP
      fetch(`presensiSiswa.php?getMapelByKelas=${kelasDipilih}`)
        .then(res => res.text())
        .then(data => {
          mapelSelect.innerHTML = `<option value="" disabled selected hidden>Pilih Mapel...</option>` + data;
          waktuInput.value = ""; // reset waktu saat ganti kelas
        })
        .catch(() => {
          mapelSelect.innerHTML = "<option value='' disabled>Gagal memuat mapel...</option>";
        });

      // 🔹 isi dropdown kelas
      const kelasList = {
        "X": ["X-1", "X-2"],
        "XI": ["XI-1", "XI-2"],
        "XII": ["XII-1", "XII-2"]
      };
      if (kelasList[kelasDipilih]) {
        selectKelas.innerHTML = `
          <option value="" disabled selected hidden>Pilih Kelas...</option>
          ${kelasList[kelasDipilih].map(k => `<option value="${k}">${k.replace('-', ' DKV ')}</option>`).join('')}
        `;
      }
    });
  });

  // === TOMBOL BATAL ===
  tombolBatal.addEventListener("click", () => {
    modal.style.display = "none";
    modal.querySelector("form").reset(); // 🔥 reset semua input form
  });

  //GA RESET KALAU PENCET LUAR FORM
  window.addEventListener("click", (event) => {
    if (event.target === modal) modal.style.display = "none";
  });

  // LEK MAU LANGSUNG RESET KALAU DIPENCET LUAR FORM
//   window.addEventListener("click", (event) => {
//   if (event.target === modal) {
//     modal.style.display = "none";
//     modal.querySelector("form").reset(); // reset juga kalau modal ditutup dari luar
//   }
// });

  // === UPDATE WAKTU MAPEL SAAT MAPEL DIGANTI ===
  mapelSelect.addEventListener("change", function() {
    const kodeMapel = this.value;
    const kelasDipilih = selectKelas.value || document.querySelector(".card button.active")?.getAttribute("data-kelas");

    if (kodeMapel && kelasDipilih) {
      const kelasFinal = kelasDipilih
        .replace(/\s*DKV\s*/i, '-')
        .replace(/\s+/g, '-')
        .trim();

      fetch(`presensiSiswa.php?getWaktuMapel=${kodeMapel}&kelas=${kelasFinal}`)
        .then(res => res.text())
        .then(data => waktuInput.value = data)
        .catch(() => waktuInput.value = "Gagal memuat jadwal");
    } else {
      waktuInput.value = "";
    }
  });

  // === AUTO UPDATE WAKTU MAPEL SAAT KELAS BERUBAH ===
  selectKelas.addEventListener("change", function() {
    const kelasDipilih = this.value;
    const kodeMapel = mapelSelect.value;

    if (kodeMapel && kelasDipilih) {
      const kelasFinal = kelasDipilih
        .replace(/\s*DKV\s*/i, '-')
        .replace(/\s+/g, '-')
        .trim();

      fetch(`presensiSiswa.php?getWaktuMapel=${kodeMapel}&kelas=${kelasFinal}`)
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
