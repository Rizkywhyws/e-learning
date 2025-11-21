<?php
require_once '../config/session.php';
require_once '../config/db.php';

checkLogin();
checkRole(['siswa']);

$namaSiswa = $_SESSION['nama'] ?? 'Siswa';
$kelasSiswa = $_SESSION['kelas'] ?? null;

$mapelSelanjutnya = 'Belum ada jadwal';
$nextMapelName = '';
$nextJamMulai = '';
$nextJamSelesai = '';
$nextStatus = '';

date_default_timezone_set('Asia/Jakarta');

$hariEng = date("l"); // Friday, Saturday, etc
$jamSekarang = date("H:i");

// Konversi nama hari ke Indonesia
$hariIndo = [
    'Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
    'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu','Sunday'=>'Minggu'
][$hariEng];

if ($kelasSiswa) {

    // ==========================
    // CEK JADWAL HARI INI YANG BELUM DIMULAI
    $sqlNext = "
        SELECT m.namaMapel, jm.jamMulai, jm.durasi
        FROM jadwalmapel jm
        JOIN mapel m ON jm.kodeMapel = m.kodeMapel
        WHERE jm.kelas = ?
          AND jm.hari = ?
          AND jm.jamMulai > ?
        ORDER BY jm.jamMulai ASC
        LIMIT 1
    ";

    $stmtN = $conn->prepare($sqlNext);
    $stmtN->bind_param("sss", $kelasSiswa, $hariIndo, $jamSekarang);
    $stmtN->execute();
    $resultN = $stmtN->get_result();

    if ($rowN = $resultN->fetch_assoc()) {

        $nextMapelName = $rowN['namaMapel'];
        $nextJamMulai = date("H:i", strtotime($rowN['jamMulai']));
        $nextJamSelesai = date("H:i", strtotime($rowN['jamMulai'] . " + {$rowN['durasi']} minutes"));
        $nextStatus = "Hari ini";

    } else {

        // ==========================
        // TIDAK ADA JADWAL HARI INI → CARI HARI BERIKUTNYA (SKIP SABTU-MINGGU)

        switch ($hariEng) {
            case 'Friday':
                $hariBerikutnyaEng = 'Monday';
                break;
            case 'Saturday':
                $hariBerikutnyaEng = 'Monday';
                break;
            case 'Sunday':
                $hariBerikutnyaEng = 'Monday';
                break;
            default:
                $hariBerikutnyaEng = date("l", strtotime("+1 day"));
        }

        // Konversi hari Inggris → Indonesia
        $hariBerikutnya = [
            'Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
            'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu','Sunday'=>'Minggu'
        ][$hariBerikutnyaEng];

        // ==========================
        // AMBIL JADWAL HARI BERIKUTNYA
        $sqlNext2 = "
            SELECT m.namaMapel, jm.jamMulai, jm.durasi
            FROM jadwalmapel jm
            JOIN mapel m ON jm.kodeMapel = m.kodeMapel
            WHERE jm.kelas = ?
              AND jm.hari = ?
            ORDER BY jm.jamMulai ASC
            LIMIT 1
        ";

        $stmtN2 = $conn->prepare($sqlNext2);
        $stmtN2->bind_param("ss", $kelasSiswa, $hariBerikutnya);
        $stmtN2->execute();
        $resultN2 = $stmtN2->get_result();

        if ($rowN2 = $resultN2->fetch_assoc()) {
            $nextMapelName = $rowN2['namaMapel'];
            $nextJamMulai = date("H:i", strtotime($rowN2['jamMulai']));
            $nextJamSelesai = date("H:i", strtotime($rowN2['jamMulai'] . " + {$rowN2['durasi']} minutes"));
            $nextStatus = "Besok ($hariBerikutnya)";
        }
    }
}
?>



<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Siswa | E-School</title>

  <link rel="stylesheet" href="cssSiswa/dashboard.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>

  <!-- HEADER -->
<div class="sticky-header">
  <header>
    <img src="../assets/logo-elearning.png" class="logo" alt="E-School">
 

  <!-- MENU ROW -->
  <div class="menu-row">

    <div class="dropdown">
      <button class="dropbtn">
        <i class="fa-solid fa-database"></i>
        Profil
        <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
      </button>
      <div class="dropdown-content">
        <a href="#"><i class="fa-solid fa-user"></i> Profil Saya</a>
      </div>
    </div>

    <div class="dropdown">
      <button class="dropbtn">
        <i class="fa-solid fa-clipboard-check"></i>
        Presensi Siswa
        <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
      </button>
      <div class="dropdown-content">
        <a href="../Siswa/presensi.php"><i class="fa-solid fa-check"></i> Lihat Presensi</a>
      </div>
    </div>

    <div class="dropdown">
      <button class="dropbtn">
        <i class="fa-solid fa-school"></i>
        Pengelolaan Pembelajaran
        <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
      </button>
      <div class="dropdown-content">
        <a href="../Siswa/mataPelajaran.php"><i class="fa-solid fa-book-open"></i> Mata Pelajaran</a>
        <a href="#"><i class="fa-solid fa-pen-to-square"></i> Quiz</a>
      </div>
    </div>
    <div class="dropdown">
      <button class="dropbtn">
        <i class="fa-solid fa-right-from-bracket"></i>
        <a href="../Auth/logout.php" onclick="return confirm('Yakin ingin logout?')"style="text-decoration:none; color:#2e7dff;"> Logout</a>
      </button>
    </div>
</div>
  </header>
</div>

  <!-- WELCOME BOX -->
  <section class="welcome-box">
  <h2>Halo! Selamat Datang, <?= htmlspecialchars($namaSiswa) ?></h2>
  <p>Jadwal Pelajaran selanjutnya <b><?= htmlspecialchars($mapelSelanjutnya) ?></b></p>
  </section>
  
  <!-- SEARCH -->
  <div class="search-bar">
    <input type="text" placeholder="Search...">
    <button><i class="fa-solid fa-magnifying-glass"></i></button>
  </div>
  <!-- GRID WRAPPER -->
  <section class="main-grid">

<!-- JADWAL PELAJARAN -->
    <div class="box jadwal">
      <h3>Jadwal Pelajaran</h3>
      <div class="table-scroll">
        <table class="table-jadwal">
          <thead>
            <tr>
              <th>No</th>
              <th>Hari</th>
              <th>Jam</th>
              <th>Mata Pelajaran</th>
              <th>Guru Pengajar</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if ($kelasSiswa) {
                $sqlJadwal = "
              SELECT jm.hari, jm.jamMulai, jm.durasi, m.namaMapel, g.nama AS namaGuru
              FROM jadwalmapel jm
              JOIN mapel m ON jm.kodeMapel = m.kodeMapel
              LEFT JOIN dataguru g ON jm.nipGuru = g.NIP
              WHERE jm.kelas = ?
              ORDER BY 
                  FIELD(jm.hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'),
                  jm.jamMulai ASC
          ";
                $stmtJadwal = $conn->prepare($sqlJadwal);
                $stmtJadwal->bind_param("s", $kelasSiswa);
                $stmtJadwal->execute();
                $resultJadwal = $stmtJadwal->get_result();
                
                if ($resultJadwal->num_rows > 0) {
                    $no = 1;
                   while ($jadwal = $resultJadwal->fetch_assoc()) {
                      $jamMulaiRaw = $jadwal['jamMulai'];
                      $durasi = intval($jadwal['durasi']); // ambil durasi dari database

                      // Hitung jam selesai
                      $jamSelesaiRaw = date("H:i", strtotime($jamMulaiRaw . " +$durasi minutes"));

                      $jamMulai = date("H:i", strtotime($jamMulaiRaw));
                      $jamSelesai = $jamSelesaiRaw;

                      $namaGuru = $jadwal['namaGuru'] ?? 'Belum ditentukan';

                      echo "<tr>";
                      echo "<td>" . $no++ . "</td>";
                      echo "<td>" . htmlspecialchars($jadwal['hari']) . "</td>";
                      echo "<td>" . $jamMulai . " - " . $jamSelesai . "</td>";
                      echo "<td>" . htmlspecialchars($jadwal['namaMapel']) . "</td>";
                      echo "<td>" . htmlspecialchars($namaGuru) . "</td>";
                      echo "</tr>";
                  }
                } else {
                    echo "<tr><td colspan='5' style='text-align: center;'>Belum ada jadwal tersedia</td></tr>";
                }
            } else {
                echo "<tr><td colspan='5' style='text-align: center;'>Data kelas tidak ditemukan</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- KALENDER -->
    <div class="box kalender">
      <h3>Kalender</h3>
      <div class="calendar-header">
        <button id="prevMonth">‹</button>
        <span id="monthYear"></span>
        <button id="nextMonth">›</button>
      </div>
      <table class="calendar-table" id="calendar">
        <thead>
          <tr>
            <th>M</th><th>S</th><th>S</th><th>R</th><th>K</th><th>J</th><th>S</th>
          </tr>
        </thead>
        <tbody id="calendar-body"></tbody>
      </table>
    </div>

    <!-- TIMELINE + SEKARANG (sejajar) -->
    <div class="timeline-sekarang-container">
      <!-- TIMELINE -->
      <div class="box timeline">
        <h3>Timeline</h3>
        <div class="timeline-list">
          <p><b>Tugas:</b> Bahasa Inggris — 09.00</p>
          <p><b>Quiz:</b> Matematika — 10.00</p>
          <p><b>Tugas:</b> Sosiologi — 13.00</p>
        </div>
      </div>

      <!-- SEKARANG -->
      <div class="box sekarang">
        <h3>Sekarang</h3>
        <p class="lokasi">
          <i class="fa-solid fa-location-dot"></i>
          Gedung Jurusan Teknologi Informasi (JTI)<br>
          Politeknik Negeri Jember Jl. Mastrip No.164
        </p>
        <div class="sekarang-content">
          <div class="absen-area">
            <span class="x">X</span>
            <button class="absen-btn">Absen Sekarang</button>
          </div>
          <div class="info-area">
            <p><i class="fa-solid fa-book"></i> Bahasa Indonesia</p>
            <h2>08.30 - 09.15</h2>
            <p class="status">Anda belum melakukan presensi</p>
          </div>
        </div>
      </div>
    </div>

    <!-- INFO KELAS -->
    <div class="box selanjutnya">
      <h3>Selanjutnya</h3>
      <p><b><?= htmlspecialchars($nextMapelName) ?></b></p>
      <p><?= htmlspecialchars($nextJamMulai . " - " . $nextJamSelesai) ?></p>
      
      <div class="jam-box">
        <?= htmlspecialchars($nextStatus) ?>
  </div>
    </div>
    
    <div class="charts">
    <!-- PROGRESS -->
    <div class="box progress">
      <h3>Progress</h3>
      <div class="progress-item">
        <label>Materi</label>
        <div class="bar"><span style="width: 70%"></span></div>
      </div>
      <div class="progress-item">
        <label>Tugas</label>
        <div class="bar"><span style="width: 40%"></span></div>
      </div>
      <div class="progress-item">
        <label>Quiz</label>
        <div class="bar"><span style="width: 80%"></span></div>
      </div>
    </div>

    <!-- GRAFIK -->
    <div class="box chart1">
      <h3>Nilai Per Mata Pelajaran</h3>
      <img src="chart-placeholder.png" alt="Grafik" class="chart-img">
    </div>

    <!-- PIE CHART -->
    <div class="box chart2">
      <h3>Status Pengumpulan Tugas</h3>
      <img src="pie-placeholder.png" alt="Pie" class="pie-img">
    </div>
    </div>

  </section>

  <!-- SCRIPT KALENDER -->
  <script>
    const monthYear = document.getElementById("monthYear");
    const calendarBody = document.getElementById("calendar-body");
    let currentDate = new Date();

    function renderCalendar(date) {
      const year = date.getFullYear();
      const month = date.getMonth();
      const monthNames = [
        "Januari","Februari","Maret","April","Mei","Juni",
        "Juli","Agustus","September","Oktober","November","Desember"
      ];
      monthYear.textContent = `${monthNames[month]} ${year}`;
      const firstDay = new Date(year, month, 1).getDay();
      const daysInMonth = new Date(year, month + 1, 0).getDate();
      const startDay = (firstDay === 0) ? 6 : firstDay - 1;

      calendarBody.innerHTML = "";
      let dateNum = 1;
      for (let i = 0; i < 6; i++) {
        let row = "<tr>";
        for (let j = 0; j < 7; j++) {
          if (i === 0 && j < startDay) row += "<td></td>";
          else if (dateNum > daysInMonth) row += "<td></td>";
          else {
            let today = new Date();
            let isToday = dateNum === today.getDate() &&
              month === today.getMonth() && year === today.getFullYear();
            row += `<td class="${isToday ? "today" : ""}">${dateNum}</td>`;
            dateNum++;
          }
        }
        row += "</tr>";
        calendarBody.innerHTML += row;
      }
    }

    document.getElementById("prevMonth").addEventListener("click", () => {
      currentDate.setMonth(currentDate.getMonth() - 1);
      renderCalendar(currentDate);
    });

    document.getElementById("nextMonth").addEventListener("click", () => {
      currentDate.setMonth(currentDate.getMonth() + 1);
      renderCalendar(currentDate);
    });

    renderCalendar(currentDate);
  </script>
<script>
// === GANTI GAMBAR PLACEHOLDER DENGAN CANVAS ===
document.querySelector(".chart1").innerHTML += '<canvas id="barChart"></canvas>';
document.querySelector(".chart2").innerHTML += '<canvas id="pieChart"></canvas>';

// === BAR CHART: NILAI PER MATA PELAJARAN ===
const ctxBar = document.getElementById("barChart").getContext("2d");

new Chart(ctxBar, {
  type: "bar",
  data: {
    labels: ["Matematika", "B. Indonesia", "B. Inggris", "IPA", "IPS", "PKN"],
    datasets: [{
      label: "Nilai",
      data: [85, 90, 88, 75, 80, 92],
      backgroundColor: "rgba(54, 162, 235, 0.7)",
      borderColor: "rgba(54, 162, 235, 1)",
      borderWidth: 2,
      borderRadius: 8
    }]
  },
  options: {
    responsive: true,
    scales: {
      y: {
        beginAtZero: true,
        ticks: { color: "#333" }
      },
      x: {
        ticks: { color: "#333" }
      }
    },
    plugins: {
      legend: { display: false }
    }
  }
});

// === PIE CHART: STATUS PENGUMPULAN TUGAS ===
const ctxPie = document.getElementById("pieChart").getContext("2d");

new Chart(ctxPie, {
  type: "pie",
  data: {
    labels: ["Terkumpul", "Belum", "Terlambat"],
    datasets: [{
      data: [12, 4, 2],
      backgroundColor: [
        "rgba(46, 204, 113, 0.8)",  // Hijau
        "rgba(231, 76, 60, 0.8)",   // Merah
        "rgba(241, 196, 15, 0.8)"   // Kuning
      ]
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: {
        position: "bottom",
        labels: { color: "#333" }
      }
    }
  }
});
</script>

</body>
</html>
