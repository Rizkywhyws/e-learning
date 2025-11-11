<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Siswa | E-School</title>

  <!-- FONT & ICON -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- CSS -->
  <link rel="stylesheet" href="cssSiswa/presensi.css">
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
        <a href="#"><i class="fa-solid fa-user"></i> Dashboard</a>
        <a href="#"><i class="fa-solid fa-users"></i> Profil Saya</a>
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

  <!-- WELCOME -->
  <section class="welcome-box">
    <h2>Halo! Selamat Datang, Marta</h2>
    <p>Jadwal Pelajaran selanjutnya <b>Matematika</b></p>
  </section>

  <!-- SEARCH -->
  <div class="search-bar">
    <input type="text" placeholder="Search...">
    <button><i class="fa-solid fa-magnifying-glass"></i></button>
  </div>

  <!-- KONTEN UTAMA -->
  <main class="content-container">
        <section class="presensi-section">
            <h1>Presensi</h1>

            <div class="main-content-flex">
                <div class="card now-presensi-card">
                    <div class="card-header-blue">
                        <h2>Sekarang</h2>
                    </div>
                    
                    <div class="card-content">
                        <h3 class="mapel-title">Bahasa Indonesia</h3>
                        <p class="description">(tulisan dari deskripsi absennya taruh disini)</p>
                        
                        <div class="presensi-details">
                            <p><strong>Presensi Mulai</strong> : 09.00, 23 Agustus 2020</p>
                            <p><strong>Presensi Akhir</strong> : 10.30, 23 Agustus 2020</p>
                            <p><strong>Lokasi Presensi</strong> : JTI POLIJE</p>
                            <p><strong>Guru Pengampu</strong> : Drs. Siapa Yaa, S.Ss, M.Mm</p>
                        </div>

                        <div class="status-box">
                            <div class="belum-absen">
                                <p>Anda belum melakukan presensi</p>
                                <small>Klik untuk absen</small>
                                <div class="x-icon-box">
                                    <i class="fa-solid fa-xmark"></i>
                                </div>
                            </div>
                            <a href="#" class="upload-izin-btn">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                Unggah surat izin/sakit
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card data-kehadiran-card">
                    <div class="card-header-blue">
                        <h2>Data Kehadiran</h2>
                    </div>
                    <div class="chart-container">
                        <canvas id="kehadiranChart"></canvas>
                    </div>
                </div>
            </div>
        </section>

        <section class="rekap-presensi-section">
            <h1>Rekap Presensi</h1>
            <!-- Konten rekap presensi akan ditambahkan di sini -->
        </section>

        <div class="legend-row">
            <span class="legend-item"><span class="legend-color hadir"></span> Hadir</span>
            <span class="legend-item"><span class="legend-color alpa"></span> Alpa</span>
            <span class="legend-item"><span class="legend-color sakit"></span> Sakit</span>
            <span class="legend-item"><span class="legend-color izin"></span> Izin</span>
            <span class="legend-item"><span class="legend-color tidak-ada"></span> Tidak Ada Presensi</span>
        </div>
    </main>

<script>
document.addEventListener("DOMContentLoaded", function () {
  // === DROPDOWN UTAMA ===
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
  
  // === PIE CHART (DATA KEHADIRAN) ===
    const ctx = document.getElementById('kehadiranChart').getContext('2d');
    
    const dataKehadiran = {
        labels: ['Hadir', 'Alpa', 'Sakit', 'Izin'],
        datasets: [{
            data: [25, 3, 2, 5], 
            backgroundColor: [
                '#4CAF50', // Hadir - Hijau
                '#F44336', // Alpa - Merah
                '#FFEB3B', // Sakit - Kuning
                '#2196F3'  // Izin - Biru
            ],
            borderWidth: 0,
            hoverOffset: 10
        }]
    };

    const config = {
        type: 'pie',
        data: dataKehadiran,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12,
                        padding: 10,
                        font: {
                            size: 11
                        },
                        generateLabels: function(chart) {
                            const data = chart.data;
                            return data.labels.map((label, i) => ({
                                text: `${label}: ${data.datasets[0].data[i]}`,
                                fillStyle: data.datasets[0].backgroundColor[i],
                                hidden: false,
                                index: i
                            }));
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (context.parsed !== null) {
                                label += ': ' + context.parsed + ' kali';
                            }
                            return label;
                        }
                    }
                }
            }
        }
    };

    new Chart(ctx, config);
  });
</script>
</body>
</html>