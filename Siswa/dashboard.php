<?php
require_once '../config/session.php';
require_once '../config/db.php';

checkLogin();
checkRole(['siswa']);

$namaSiswa = $_SESSION['nama'] ?? 'Siswa';
$kelasSiswa = $_SESSION['kelas'] ?? null;
$nisSiswa = $_SESSION['NIS'] ?? null; // Pastikan NIS tersedia di session

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


$timelineItems = [];
$sekarangMapel = '';
$sekarangJam = '';
$sekarangStatus = 'Anda belum melakukan presensi';
$sekarangGuru = '';
$sekarangRuangan = ''; // Tambahkan variabel untuk ruangan
$sekarangIdJadwal = ''; // Tambahkan variabel untuk idJadwal
$absenStatus = ''; // Tambahkan variabel untuk status absen

if ($kelasSiswa) {
    // 1. Ambil Daftar kodeMapel untuk kelas siswa
    $sqlGetMapel = "
        SELECT DISTINCT kodeMapel
        FROM jadwalmapel
        WHERE kelas = ?
    ";
    $stmtGetMapel = $conn->prepare($sqlGetMapel);
    $stmtGetMapel->bind_param("s", $kelasSiswa);
    $stmtGetMapel->execute();
    $resultGetMapel = $stmtGetMapel->get_result();

    $kodeMapelList = [];
    while ($row = $resultGetMapel->fetch_assoc()) {
        $kodeMapelList[] = $row['kodeMapel'];
    }

    // Jika tidak ada mapel, set array kosong agar query tidak error
    if (empty($kodeMapelList)) {
        $kodeMapelList = ['NULL']; // Dummy value
    }

    // 2. Ambil Quiz (SEMUA, untuk memastikan data muncul)
    $placeholders = str_repeat('?,', count($kodeMapelList) - 1) . '?';
    $sqlQuiz = "
        SELECT q.judul, q.waktuMulai, m.namaMapel
        FROM quiz q
        JOIN mapel m ON q.kodeMapel = m.kodeMapel
        WHERE q.kodeMapel IN ($placeholders)
        ORDER BY q.waktuMulai ASC
        LIMIT 3
    ";

    $stmtQuiz = $conn->prepare($sqlQuiz);
    $types = str_repeat('s', count($kodeMapelList));
    $stmtQuiz->bind_param($types, ...$kodeMapelList);
    $stmtQuiz->execute();
    $resultQuiz = $stmtQuiz->get_result();

    while ($row = $resultQuiz->fetch_assoc()) {
        $timelineItems[] = [
            'type' => 'Quiz',
            'judul' => $row['judul'],
            'mapel' => $row['namaMapel'],
            'waktu' => date("H:i", strtotime($row['waktuMulai']))
        ];
    }

    // 3. Ambil Tugas yang deadline-nya akan datang (tetap kosong, karena tabel tugas kosong)
    $sqlTugas = "
        SELECT t.judul, t.deadline, m.namaMapel
        FROM tugas t
        JOIN mapel m ON t.kodeMapel = m.kodeMapel
        WHERE t.kodeMapel IN ($placeholders)
          AND t.deadline > NOW()
        ORDER BY t.deadline ASC
        LIMIT 3
    ";

    $stmtTugas = $conn->prepare($sqlTugas);
    $stmtTugas->bind_param($types, ...$kodeMapelList);
    $stmtTugas->execute();
    $resultTugas = $stmtTugas->get_result();

    while ($row = $resultTugas->fetch_assoc()) {
        $timelineItems[] = [
            'type' => 'Tugas',
            'judul' => $row['judul'],
            'mapel' => $row['namaMapel'],
            'waktu' => date("H:i", strtotime($row['deadline']))
        ];
    }

    // 4. Ambil Materi terbaru (tetap kosong, karena tabel materi kosong)
    $sqlMateri = "
        SELECT m.judul, m.createdAt, mp.namaMapel
        FROM materi m
        JOIN mapel mp ON m.kodeMapel = mp.kodeMapel
        WHERE mp.kodeMapel IN ($placeholders)
        ORDER BY m.createdAt DESC
        LIMIT 3
    ";

    $stmtMateri = $conn->prepare($sqlMateri);
    $stmtMateri->bind_param($types, ...$kodeMapelList);
    $stmtMateri->execute();
    $resultMateri = $stmtMateri->get_result();

    while ($row = $resultMateri->fetch_assoc()) {
        $timelineItems[] = [
            'type' => 'Materi',
            'judul' => $row['judul'],
            'mapel' => $row['namaMapel'],
            'waktu' => date("H:i", strtotime($row['createdAt']))
        ];
    }

    // Urutkan timelineItems berdasarkan waktu (ascending)
    usort($timelineItems, function($a, $b) {
        $timeA = strtotime($a['waktu']);
        $timeB = strtotime($b['waktu']);
        return $timeA <=> $timeB;
    });

    // Ambil maksimal 3 item untuk ditampilkan di timeline
    $timelineItems = array_slice($timelineItems, 0, 3);

    // === LOGIKA UNTUK BAGIAN "SEKARANG" ===
    // === LOGIKA UNTUK BAGIAN "SEKARANG" ===
// Gabungkan jadwalmapel, buatpresensi, dan cek presensi siswa
$sqlSekarang = "
    SELECT 
        m.namaMapel, 
        jm.jamMulai, 
        jm.durasi, 
        g.nama AS namaGuru, 
        jm.idJadwalMapel, 
        jm.ruangan,
        bp.idBuatPresensi -- Ambil idBuatPresensi jika ada
    FROM jadwalmapel jm
    JOIN mapel m ON jm.kodeMapel = m.kodeMapel
    LEFT JOIN dataguru g ON jm.nipGuru = g.NIP
    LEFT JOIN buatpresensi bp ON jm.idJadwalMapel = bp.idJadwalMapel AND DATE(bp.waktuDimulai) = CURDATE() -- ✅ Benar
    WHERE jm.kelas = ?
      AND jm.hari = ?
      AND ? BETWEEN jm.jamMulai AND DATE_ADD(jm.jamMulai, INTERVAL jm.durasi MINUTE)
    LIMIT 1
";

$stmtSekarang = $conn->prepare($sqlSekarang);
$stmtSekarang->bind_param("sss", $kelasSiswa, $hariIndo, $jamSekarang);
$stmtSekarang->execute();
$resultSekarang = $stmtSekarang->get_result();

if ($rowSekarang = $resultSekarang->fetch_assoc()) {
    $sekarangMapel = $rowSekarang['namaMapel'];
    $jamMulaiRaw = $rowSekarang['jamMulai'];
    $durasi = intval($rowSekarang['durasi']);
    $jamSelesaiRaw = date("H:i", strtotime("$jamMulaiRaw +$durasi minutes"));
    $sekarangJam = "$jamMulaiRaw - $jamSelesaiRaw";
    $sekarangGuru = $rowSekarang['namaGuru'] ?? 'Belum ditentukan';
    $sekarangRuangan = $rowSekarang['ruangan'] ?? 'Ruangan tidak diketahui';
    $sekarangIdJadwal = $rowSekarang['idJadwalMapel'];
    $idBuatPresensi = $rowSekarang['idBuatPresensi']; // Ambil idBuatPresensi

    // Cek status presensi SISWA hanya jika guru TELAH MEMBUAT PRESENSI
    if ($nisSiswa && $idBuatPresensi) { // Pastikan NIS tersedia DAN idBuatPresensi ditemukan
        $sqlCekPresensi = "
            SELECT COUNT(*) as total
            FROM presensisiswa ps
            WHERE ps.NIS = ? 
              AND ps.idBuatPresensi = ? -- Gunakan idBuatPresensi
        ";
        $stmtCekPresensi = $conn->prepare($sqlCekPresensi);
        $stmtCekPresensi->bind_param("ss", $nisSiswa, $idBuatPresensi);
        $stmtCekPresensi->execute();
        $resultCekPresensi = $stmtCekPresensi->get_result();
        $rowCekPresensi = $resultCekPresensi->fetch_assoc();

        if ($rowCekPresensi['total'] > 0) {
            $sekarangStatus = 'Anda sudah melakukan presensi';
            $absenStatus = 'sudah_absen';
        } else {
            $sekarangStatus = 'Presensi sedang aktif, silakan absen'; // Ubah status
            $absenStatus = 'belum_absen';
        }
    } elseif ($nisSiswa && !$idBuatPresensi) {
        // Guru belum membuat presensi untuk jadwal ini
        $sekarangStatus = 'Presensi belum dibuka oleh guru';
        $absenStatus = 'presensi_tidak_aktif'; // Tambahkan status baru
    } else {
        // NIS tidak ditemukan
        $sekarangStatus = 'Tidak dapat memeriksa presensi (NIS tidak ditemukan).';
        $absenStatus = 'tidak_dapat_absen';
    }

} else {
    // Jika tidak ada pelajaran yang sedang berlangsung
    $sekarangMapel = 'Tidak ada pelajaran';
    $sekarangJam = '';
    $sekarangStatus = 'Tidak ada pelajaran saat ini';
    $sekarangGuru = '';
    $sekarangRuangan = '';
    $absenStatus = 'tidak_ada_pelajaran';
}
}

// --- HITUNG STATISTIK PEMBELAJARAN SISWA ---
$jumlahMateri = 0;
$jumlahTugasDikumpulkan = 0;
$jumlahQuizDikerjakan = 0;

if ($nisSiswa && $kelasSiswa) {
    // --- JUMLAH MATERI YANG SUDAH DILIHAT ---
    // Asumsi: Ada tabel `materidilihat` atau `riwayat_materi` yang mencatat NIS dan idMateri
    // Karena tidak ada tabel ini di kode Anda, kita asumsikan hanya hitung total materi yang tersedia untuk kelasnya.
    // Jika ingin lebih akurat, buat tabel `riwayat_materi` nanti.
    $sqlMateri = "
        SELECT COUNT(*) as total
        FROM materi m
        JOIN mapel mp ON m.kodeMapel = mp.kodeMapel
        WHERE mp.kodeMapel IN (
            SELECT DISTINCT kodeMapel FROM jadwalmapel WHERE kelas = ?
        )
    ";
    $stmtMateri = $conn->prepare($sqlMateri);
    $stmtMateri->bind_param("s", $kelasSiswa);
    $stmtMateri->execute();
    $resultMateri = $stmtMateri->get_result();
    $rowMateri = $resultMateri->fetch_assoc();
    $jumlahMateri = $rowMateri['total'] ?? 0;

    // --- JUMLAH TUGAS YANG SUDAH DIKUMPULKAN ---
    $sqlTugasDikumpulkan = "
        SELECT COUNT(*) as total
        FROM pengumpulantugas pt
        JOIN tugas t ON pt.idTugas = t.idTugas
        WHERE pt.NIS = ? AND pt.status = 'selesai'
    ";
    $stmtTugasDikumpulkan = $conn->prepare($sqlTugasDikumpulkan);
    $stmtTugasDikumpulkan->bind_param("s", $nisSiswa);
    $stmtTugasDikumpulkan->execute();
    $resultTugasDikumpulkan = $stmtTugasDikumpulkan->get_result();
    $rowTugasDikumpulkan = $resultTugasDikumpulkan->fetch_assoc();
    $jumlahTugasDikumpulkan = $rowTugasDikumpulkan['total'] ?? 0;

    // --- JUMLAH QUIZ YANG SUDAH DIKERJAKAN ---
    $sqlQuizDikerjakan = "
        SELECT COUNT(*) as total
        FROM hasilquiz hq
        JOIN quiz q ON hq.idQuiz = q.idQuiz
        WHERE hq.NIS = ?
    ";
    $stmtQuizDikerjakan = $conn->prepare($sqlQuizDikerjakan);
    $stmtQuizDikerjakan->bind_param("s", $nisSiswa);
    $stmtQuizDikerjakan->execute();
    $resultQuizDikerjakan = $stmtQuizDikerjakan->get_result();
    $rowQuizDikerjakan = $resultQuizDikerjakan->fetch_assoc();
    $jumlahQuizDikerjakan = $rowQuizDikerjakan['total'] ?? 0;
}

// --- AKTIVITAS TERBARU ---
$aktivitasItems = [];

if ($nisSiswa) {
    // Ambil 3 aktivitas terbaru dari pengumpulan tugas
    $sqlAktivitasTugas = "
        SELECT pt.submittedAt, t.judul AS nama, 'Tugas' AS jenis, m.namaMapel AS mapel
        FROM pengumpulantugas pt
        JOIN tugas t ON pt.idTugas = t.idTugas
        JOIN mapel m ON t.kodeMapel = m.kodeMapel
        WHERE pt.NIS = ? AND pt.status = 'selesai'
        ORDER BY pt.submittedAt DESC
        LIMIT 3
    ";

    $stmtAktivitasTugas = $conn->prepare($sqlAktivitasTugas);
    $stmtAktivitasTugas->bind_param("s", $nisSiswa);
    $stmtAktivitasTugas->execute();
    $resultAktivitasTugas = $stmtAktivitasTugas->get_result();

    while ($row = $resultAktivitasTugas->fetch_assoc()) {
        $aktivitasItems[] = [
            'jenis' => $row['jenis'],
            'nama' => $row['nama'],
            'mapel' => $row['mapel'],
            'waktu' => date("Y-m-d H:i", strtotime($row['submittedAt']))
        ];
    }

    // Ambil 3 aktivitas terbaru dari hasil quiz
    $sqlAktivitasQuiz = "
        SELECT hq.nilai, hq.tanggalSubmit, q.judul AS nama, 'Quiz' AS jenis, m.namaMapel AS mapel
        FROM hasilquiz hq
        JOIN quiz q ON hq.idQuiz = q.idQuiz
        JOIN mapel m ON q.kodeMapel = m.kodeMapel
        WHERE hq.NIS = ?
        ORDER BY hq.tanggalSubmit DESC
        LIMIT 3
    ";

    $stmtAktivitasQuiz = $conn->prepare($sqlAktivitasQuiz);
    $stmtAktivitasQuiz->bind_param("s", $nisSiswa);
    $stmtAktivitasQuiz->execute();
    $resultAktivitasQuiz = $stmtAktivitasQuiz->get_result();

    while ($row = $resultAktivitasQuiz->fetch_assoc()) {
        $aktivitasItems[] = [
            'jenis' => $row['jenis'],
            'nama' => $row['nama'],
            'mapel' => $row['mapel'],
            'waktu' => date("Y-m-d H:i", strtotime($row['tanggalSubmit'])) // Gunakan kolom yang benar
        ];
    }

    // Urutkan berdasarkan waktu (descending)
    usort($aktivitasItems, function($a, $b) {
        $timeA = strtotime($a['waktu']);
        $timeB = strtotime($b['waktu']);
        return $timeB <=> $timeA; // Descending
    });

    // Ambil maksimal 3 item
    $aktivitasItems = array_slice($aktivitasItems, 0, 3);
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css    ">
  <script src="https://cdn.jsdelivr.net/npm/chart.js    "></script>

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
        <i class="fa-solid fa-user"></i>
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
        <a href="../Siswa/ngerjakanQuiz.php"><i class="fa-solid fa-pen-to-square"></i> Quiz</a>
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
          <?php if (!empty($timelineItems)): ?>
            <?php foreach ($timelineItems as $item): ?>
              <p><b><?= htmlspecialchars($item['type']) ?>:</b> <?= htmlspecialchars($item['judul']) ?> — <?= htmlspecialchars($item['waktu']) ?></p>
            <?php endforeach; ?>
          <?php else: ?>
            <p>Tidak ada aktivitas mendatang.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- SEKARANG -->
      <div class="box sekarang">
        <h3>Sekarang</h3>
        <p class="lokasi">
          <i class="fa-solid fa-location-dot"></i>
          SMKN 4 Jember<br>
          Jl. Kartini No.1
        </p>
        <div class="sekarang-content">
          <div class="absen-area">
            <?php if ($absenStatus === 'belum_absen'): ?>
              <span class="x">X</span>
              <button class="absen-btn" onclick="openAbsenModal()">Absen Sekarang</button>
            <?php elseif ($absenStatus === 'sudah_absen'): ?>
              <span class="checkmark" style="color: green; font-size: 40px;">✓</span>
              <p style="margin-top: 5px; font-size: 12px; color: #6c7a91;">Sudah Absen</p>
            <?php elseif ($absenStatus === 'presensi_tidak_aktif'): ?>
              <!-- Tampilkan ikon dan pesan bahwa presensi belum dibuka -->
              <span class="x" style="color: #ccc; font-size: 40px;">⏱️</span> <!-- Atau ikon jam -->
              <p style="margin-top: 5px; font-size: 12px; color: #6c7a91;">Presensi Belum Dibuka</p>
            <?php else: ?>
              <!-- Tidak ada pelajaran atau tidak bisa absen -->
              <span class="x" style="color: #ccc; font-size: 40px;">—</span>
              <p style="margin-top: 5px; font-size: 12px; color: #6c7a91;">Tidak ada pelajaran</p>
            <?php endif; ?>
          </div>
          <div class="info-area">
            <p><i class="fa-solid fa-book"></i> <?= htmlspecialchars($sekarangMapel) ?></p>
            <h2><?= htmlspecialchars($sekarangJam) ?></h2>
            <p class="status"><?= htmlspecialchars($sekarangStatus) ?></p>
            <?php if (!empty($sekarangGuru) && $sekarangGuru !== 'Belum ditentukan'): ?>
              <p class="guru"><i class="fa-solid fa-chalkboard-teacher"></i> Guru: <?= htmlspecialchars($sekarangGuru) ?></p>
            <?php endif; ?>
            <?php if (!empty($sekarangRuangan)): ?>
              <p class="ruangan"><i class="fa-solid fa-door-closed"></i> Ruangan: <?= htmlspecialchars($sekarangRuangan) ?></p>
            <?php endif; ?>
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
    
    <!-- STATISTIK PEMBELAJARAN -->
    <div class="box progress">
      <h3>Progress Pembelajaran</h3>
      <div class="stat-item">
        <div class="stat-icon"><i class="fa-solid fa-book-open"></i></div>
        <div class="stat-content">
          <span class="stat-label">Materi</span>
          <span class="stat-value"><?= htmlspecialchars($jumlahMateri) ?></span>
          <span class="stat-desc">Materi tersedia</span>
        </div>
      </div>
      <div class="stat-item">
        <div class="stat-icon"><i class="fa-solid fa-pen-to-square"></i></div>
        <div class="stat-content">
          <span class="stat-label">Tugas</span>
          <span class="stat-value"><?= htmlspecialchars($jumlahTugasDikumpulkan) ?></span>
          <span class="stat-desc">Dikumpulkan</span>
        </div>
      </div>
      <div class="stat-item">
        <div class="stat-icon"><i class="fa-solid fa-clipboard-check"></i></div>
        <div class="stat-content">
          <span class="stat-label">Quiz</span>
          <span class="stat-value"><?= htmlspecialchars($jumlahQuizDikerjakan) ?></span>
          <span class="stat-desc">Dikerjakan</span>
        </div>
      </div>
    </div>

    <!-- AKTIVITAS TERBARU -->
    <div class="box aktivitas">
      <h3>Aktivitas Terbaru</h3>
      <div class="aktivitas-list">
        <?php if (!empty($aktivitasItems)): ?>
          <?php foreach ($aktivitasItems as $item): ?>
            <div class="aktivitas-item">
              <div class="aktivitas-icon">
                <?php if ($item['jenis'] === 'Tugas'): ?>
                  <i class="fa-solid fa-pen-to-square"></i>
                <?php else: ?>
                  <i class="fa-solid fa-clipboard-check"></i>
                <?php endif; ?>
              </div>
              <div class="aktivitas-content">
                <span class="aktivitas-jenis"><?= htmlspecialchars($item['jenis']) ?>:</span>
                <span class="aktivitas-nama"><?= htmlspecialchars($item['nama']) ?></span>
                <span class="aktivitas-mapel">— <?= htmlspecialchars($item['mapel']) ?></span>
                <span class="aktivitas-waktu"><?= htmlspecialchars($item['waktu']) ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>Tidak ada aktivitas terbaru.</p>
        <?php endif; ?>
      </div>
    </div>

  </section>

  <!-- GRAFIK POWER BI SECTION -->
  <section class="grafik-pbi-section">
    <h3><i class="fas fa-chart-line"></i> Statistik Pembelajaran Saya</h3>
    <div class="pbi-container">
      <!-- GANTI URL INI DENGAN EMBED URL POWER BI ANDA YANG BENAR -->
      <iframe 
        title="Statistik Pembelajaran Saya"
        width="100%" 
        height="500" 
        src="YOUR_POWER_BI_EMBED_URL_HERE" 
        frameborder="0" 
        allowFullScreen="true">
      </iframe>
    </div>
  </section>

  <!-- MODAL ABSEN -->
  <div id="absenModal" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
      <h3>Absen Sekarang</h3>
      <form id="absenForm">
        <label for="token">Token Absen:</label>
        <input type="text" id="token" name="token" required>
        <button type="submit">Kirim Absen</button>
      </form>
    </div>
  </div>

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
// === HAPUS SCRIPT CHART.JS YANG LAMA KARENA DIGANTI POWER BI ===
// Script untuk menggambar grafik dengan Chart.js dihapus.
// Grafik sekarang akan ditampilkan melalui Power BI embed.
</script>

<!-- SCRIPT MODAL ABSEN -->
<script>
// Fungsi untuk membuka modal
function openAbsenModal() {
  document.getElementById("absenModal").style.display = "block";
}

// Fungsi untuk menutup modal
document.querySelector(".close").onclick = function() {
  document.getElementById("absenModal").style.display = "none";
}

// Tutup modal jika klik di luar modal
window.onclick = function(event) {
  if (event.target == document.getElementById("absenModal")) {
    document.getElementById("absenModal").style.display = "none";
  }
}

// Form submission (contoh sederhana, Anda bisa tambahkan AJAX untuk mengirim ke server)
document.getElementById("absenForm").onsubmit = function(e) {
  e.preventDefault(); // Prevent form from submitting normally
  alert("Absen berhasil dikirim!"); // Ganti dengan logika AJAX Anda
  document.getElementById("absenModal").style.display = "none";
}
</script>

</body>
</html>