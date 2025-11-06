<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Guru | E-School</title>

  <link rel="stylesheet" href="css/dashboard.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <i class="fa-solid fa-database"></i> Data Master
        <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
      </button>
      <div class="dropdown-content">
        <a href="#"><i class="fa-solid fa-user-tie"></i> Kelola Guru</a>
        <a href="#"><i class="fa-solid fa-user-graduate"></i> Kelola Siswa</a>
      </div>
    </div>

    <div class="dropdown">
      <button class="dropbtn">
        <i class="fa-solid fa-clipboard-check"></i> Presensi Siswa
        <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
      </button>
      <div class="dropdown-content">
        <a href="#"><i class="fa-solid fa-list-check"></i> Lihat Presensi</a>
        <a href="#"><i class="fa-solid fa-pen-clip"></i> Buat Presensi</a>
      </div>
    </div>

      <button class="dropbtn">
        <i class="fa-solid fa-school"></i> <a href="pengelolaanPembelajaran.php">Pengelolaan Pembelajaran</a>
      </button>
  </div>
  </div>
  </header>

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

    <div class="grafik-container">
      <!-- KIRI -->
      <div class="left-panel">
        <div class="card-row">
          <div class="card-box">
            <div class="card-label">Tugas Belum Dinilai</div>
          </div>
          <div class="card-box">
            <div class="card-label">Quiz Belum Dinilai</div>
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

      <!-- KANAN -->
      <div class="right-panel">
        <div class="chart-box">
          <canvas id="lineChart"></canvas>
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

  <!-- SCRIPT CHART -->
  <script>
  const ctx = document.getElementById("lineChart").getContext("2d");
  new Chart(ctx, {
    type: "bar",
    data: {
      labels: ["Senin", "Selasa", "Rabu", "Kamis", "Jumat"],
      datasets: [{
        label: "Nilai Rata-rata",
        data: [80, 85, 90, 88, 92],
        backgroundColor: "rgba(46, 125, 255, 0.6)",
        borderColor: "#2e7dff",
        borderWidth: 2
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: { beginAtZero: true }
      }
    }
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
