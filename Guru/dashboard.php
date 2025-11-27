<?php
require_once '../config/session.php';
require_once '../config/db.php';

// Pastikan user sudah login dan rolenya guru
checkLogin();
checkRole(['guru']);
?>
<?php
$namaGuru = $_SESSION['nama'] ?? 'Guru';
$nipGuru  = $_SESSION['nipGuru'] ?? null;
$kelasMengajar = 'Belum ada jadwal';

// --- MODIFIKASI: HAPUS LOGIKA JADWAL SELANJUTNYA ---
// Kita tidak lagi mencari jadwal hari ini yang akan datang.
// Sebagai gantinya, kita bisa menampilkan jumlah total jadwal atau kelas yang diajar.
if ($nipGuru) {
    // Query untuk menghitung jumlah jadwal atau kelas unik yang diajar
    $queryCount = $conn->prepare("
        SELECT COUNT(*) as totalJadwal, GROUP_CONCAT(DISTINCT kelas ORDER BY kelas SEPARATOR ', ') as kelasList
        FROM jadwalmapel 
        WHERE nipGuru = ?
    ");
    $queryCount->bind_param("s", $nipGuru);
    $queryCount->execute();
    $resultCount = $queryCount->get_result();
    $rowCount = $resultCount->fetch_assoc();

    if ($rowCount['totalJadwal'] > 0) {
        $kelasMengajar = "Mengajar di kelas " . htmlspecialchars($rowCount['kelasList']) . " (" . $rowCount['totalJadwal'] . " jadwal)";
    }
}

// --- HITUNG TUGAS BELUM DINILAI ---
$tugasBelumDinilai = 0;
if ($nipGuru) {
    $queryTugas = $conn->prepare("
        SELECT COUNT(*) as total
        FROM pengumpulantugas pt
        JOIN tugas t ON pt.idTugas = t.idTugas
        WHERE TRIM(t.NIP) = TRIM(?) AND pt.status = 'selesai' AND pt.nilai IS NULL
    ");
    $queryTugas->bind_param("s", $nipGuru);
    $queryTugas->execute();
    $resultTugas = $queryTugas->get_result();
    $rowTugas = $resultTugas->fetch_assoc();
    $tugasBelumDinilai = $rowTugas['total'] ?? 0;
}

// --- HITUNG QUIZ BELUM DINILAI (SEMUA JENIS) ---
$quizBelumDinilai = 0;
if ($nipGuru) {
    // Karena tabel hasilquiz tidak memiliki kolom 'jenis', kita hitung semua quiz yang belum dinilai
    $queryQuiz = $conn->prepare("
        SELECT COUNT(*) as total
        FROM hasilquiz hq
        JOIN quiz q ON hq.idQuiz = q.idQuiz
        WHERE q.NIP = ? AND hq.nilai IS NULL
    ");
    $queryQuiz->bind_param("s", $nipGuru);
    $queryQuiz->execute();
    $resultQuiz = $queryQuiz->get_result();
    $rowQuiz = $resultQuiz->fetch_assoc();
    $quizBelumDinilai = $rowQuiz['total'] ?? 0;
}
?>
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
   <div class="sticky-header">
  <header>
    <img src="../assets/logo-elearning.png" class="logo" alt="E-School">

  <!-- MENU -->
  <div class="menu-row">
    <div class="dropdown">
      <button class="dropbtn">
        <i class="fa-solid fa-user"></i> Profil
        <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
      </button>
      <div class="dropdown-content">
        <a href="#"><i class="fa-solid fa-user-tie"></i> Profil Saya</a>
      </div>
    </div>

    <div class="dropdown">
      <button class="dropbtn">
        <i class="fa-solid fa-clipboard-check"></i> Presensi Siswa
        <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
      </button>
      <div class="dropdown-content">
        <a href="presensiSiswa.php"><i class="fa-solid fa-list-check"></i> Lihat Presensi</a>
        <a href="presensiSiswa.php"><i class="fa-solid fa-pen-clip"></i> Buat Presensi</a>
      </div>
    </div>

      <button class="dropbtn">
        <i class="fa-solid fa-school"></i> 
        <a href="pengelolaanPembelajaran.php" style="text-decoration: none; color: #2e7dff;">Pengelolaan Pembelajaran</a>
      </button>
      <button class="dropbtn">
        <i class="fa-solid fa-right-from-bracket"></i>
        <a href="../Auth/logout.php" onclick="return confirm('Yakin ingin logout?')"style="text-decoration:none; color:#2e7dff;"> Logout</a>
      </button>
  </div>
  </div>
  </header>

  <!-- WELCOME -->
  <section class="welcome-box">
  <h2>Halo! Selamat Datang, <?= htmlspecialchars($namaGuru) ?></h2>
  <p>Jadwal mengajar Anda: <b><?= htmlspecialchars($kelasMengajar) ?></b></p>
  </section>

  <!-- SEARCH -->
  <div class="search-bar">
    <input type="text" placeholder="Search...">
    <button><i class="fa-solid fa-magnifying-glass"></i></button>
  </div>

  <!-- JADWAL SECTION -->
<!-- JADWAL SECTION -->
<section class="grafik-section">
    <h3>Jadwal Pelajaran</h3>

    <div class="grafik-container">
      <!-- KIRI -->
      <div class="left-panel">
        <div class="card-row">
          <div class="card-box">
            <div class="card-label">Tugas Belum Dinilai</div>
            <div class="card-value"><?= htmlspecialchars($tugasBelumDinilai) ?></div>
          </div>
          <div class="card-box">
            <div class="card-label">Quiz Belum Dinilai</div>
            <div class="card-value"><?= htmlspecialchars($quizBelumDinilai) ?></div>
            
          </div>
        </div>

        <!-- Kalender -->
        <div class="calendar-box" id="calendar">
          <div class="calendar-header">
          <button id="prev-month">‹</button>
          <h4 id="calendar-title"></h4>
          <button id="next-month">›</button>
        </div>
          <div class="calendar-days">
            <div class="day-name">Min</div>
            <div class="day-name">Sen</div>
            <div class="day-name">Sel</div>
            <div class="day-name">Rab</div>
            <div class="day-name">Kam</div>
            <div class="day-name">Jum</div>
            <div class="day-name">Sab</div>
          </div>
          <div class="calendar-dates" id="calendar-dates"></div>
        </div>
      </div>

      <!-- KANAN - JADWAL -->
      <div class="right-panel">
        <div class="jadwal-table-box">
          <div class="table-scroll">
            <table class="jadwal-table">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Hari</th>
                  <th>Jam</th>
                  <th>Mata Pelajaran</th>
                  <th>Guru Pengajar</th>
                  <th>Ruangan</th>
                </tr>
              </thead>
              <tbody>
                <?php
                // --- MODIFIKASI: AMBIL SEMUA JADWAL DARI DATABASE ---
                // Query untuk mengambil semua jadwal, gabungkan dengan nama guru dari tabel dataguru
                // Dan hitung jamSelesai dari jamMulai + durasi
                $queryJadwal = $conn->prepare("
                    SELECT 
                        jm.idJadwalMapel, 
                        jm.kodeMapel, 
                        jm.hari, 
                        jm.jamMulai, 
                        jm.durasi, -- Ambil durasi
                        jm.ruangan, 
                        jm.kelas, 
                        m.namaMapel,
                        dg.nama AS namaGuru -- Ambil nama guru dari tabel dataguru
                    FROM jadwalmapel jm
                    LEFT JOIN mapel m ON jm.kodeMapel = m.kodeMapel
                    LEFT JOIN dataguru dg ON jm.nipGuru = dg.NIP -- JOIN dengan tabel dataguru
                    ORDER BY 
                        FIELD(jm.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'),
                        jm.jamMulai ASC
                ");
                $queryJadwal->execute();
                $resultJadwal = $queryJadwal->get_result();
                
                if ($resultJadwal->num_rows > 0) {
                    $no = 1;
                    while ($jadwal = $resultJadwal->fetch_assoc()) {
                        $namaMapel = $jadwal['namaMapel'] ?? $jadwal['kodeMapel'];
                        $namaGuru = $jadwal['namaGuru'] ?? 'Guru Tidak Dikenal';

                        // --- HITUNG JAM SELESAI ---
                        // Konversi jamMulai ke format waktu (DateTime)
                        $jamMulai = new DateTime($jadwal['jamMulai']);
                        // Tambahkan durasi (dalam menit)
                        $jamMulai->add(new DateInterval('PT' . $jadwal['durasi'] . 'M'));
                        // Format kembali ke string 'H:i:s'
                        $jamSelesai = $jamMulai->format('H:i:s');
                        // --- AKHIR HITUNG JAM SELESAI ---

                        echo "<tr>";
                        echo "<td>{$no}</td>";
                        echo "<td>" . htmlspecialchars($jadwal['hari']) . "</td>";
                        echo "<td>" . htmlspecialchars($jadwal['jamMulai']) . " - " . htmlspecialchars($jamSelesai) . "</td>";
                        echo "<td>" . htmlspecialchars($namaMapel) . "</td>";
                        echo "<td>" . htmlspecialchars($namaGuru) . "</td>";
                        echo "<td>" . htmlspecialchars($jadwal['ruangan']) . "</td>";
                        echo "</tr>";
                        $no++;
                    }
                } else {
                    echo "<tr><td colspan='6' class='no-data-row'>Belum ada jadwal mengajar</td></tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
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

  <!-- SCRIPT DROPDOWN -->
  <script>
  document.addEventListener("DOMContentLoaded", () => {
    const buttons = document.querySelectorAll(".dropbtn");
    buttons.forEach(btn => {
      btn.addEventListener("click", e => {
        e.stopPropagation();
        const menu = btn.nextElementSibling;
        document.querySelectorAll(".dropdown-content").forEach(dc => {
          if (dc !== menu) dc.style.display = "none";
        });
        menu.style.display = menu.style.display === "block" ? "none" : "block";
      });
    });
    document.addEventListener("click", () => {
      document.querySelectorAll(".dropdown-content").forEach(dc => dc.style.display = "none");
    });
  });
  </script>

  <!-- SCRIPT KALENDER OTOMATIS -->
  <script>
  let currentMonth = new Date().getMonth();
  let currentYear = new Date().getFullYear();

  function generateCalendar(year, month) {
    const title = document.getElementById("calendar-title");
    const dates = document.getElementById("calendar-dates");

    const monthNames = [
      "Januari","Februari","Maret","April","Mei","Juni",
      "Juli","Agustus","September","Oktober","November","Desember"
    ];

    title.textContent = `${monthNames[month]} ${year}`;

    const firstDay = new Date(year, month, 1).getDay();
    const lastDate = new Date(year, month + 1, 0).getDate();

    let html = "";
    for (let i = 0; i < firstDay; i++) html += `<div class='empty'></div>`;

    const today = new Date();

    for (let d = 1; d <= lastDate; d++) {
      const isToday =
        d === today.getDate() &&
        month === today.getMonth() &&
        year === today.getFullYear();
      html += `<div class='date ${isToday ? "today" : ""}'>${d}</div>`;
    }

    dates.innerHTML = html;
  }

  document.getElementById("prev-month").addEventListener("click", () => {
    currentMonth--;
    if (currentMonth < 0) {
      currentMonth = 11;
      currentYear--;
    }
    generateCalendar(currentYear, currentMonth);
  });

  document.getElementById("next-month").addEventListener("click", () => {
    currentMonth++;
    if (currentMonth > 11) {
      currentMonth = 0;
      currentYear++;
    }
    generateCalendar(currentYear, currentMonth);
  });

  generateCalendar(currentYear, currentMonth);
  </script>

</body>
</html>