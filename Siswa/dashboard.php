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
        <a href="#"><i class="fa-solid fa-user"></i> Data Guru</a>
        <a href="#"><i class="fa-solid fa-users"></i> Data Siswa</a>
      </div>
    </div>

    <div class="dropdown">
      <button class="dropbtn">
        <i class="fa-solid fa-clipboard-check"></i>
        Presensi Siswa
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

  <!-- WELCOME BOX -->
  <section class="welcome-box">
    <h2>Halo! Selamat Datang, Marta</h2>
    <p>Jadwal Pelajaran selanjutnya Matematika</p>
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
          <tr><td>1</td><td>Senin</td><td>07.00 - 09.00</td><td>Matematika</td><td>Bu Rina, S.Pd</td></tr>
          <tr><td>2</td><td>Senin</td><td>09.15 - 11.00</td><td>Bhs Indonesia</td><td>Pak Sandi, M.Pd</td></tr>
          <tr><td>3</td><td>Selasa</td><td>07.00 - 09.00</td><td>Sejarah</td><td>Bu Angela, S.Pd</td></tr>
        </tbody>
      </table>
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
      <p><b>Matematika</b></p>
      <p>08.30 - 09.15</p>
      <div class="jam-box">On Progress</div>
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
