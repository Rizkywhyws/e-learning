<?php
session_start();
include '../config/db.php';

date_default_timezone_set('Asia/Jakarta');// pastikan path benar dan $conn adalah mysqli object


// ===== CEK LOGIN =====
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../Auth/login.php");
    exit;
}


// AMBIL DATA DARI SESSION
$idAkun     = $_SESSION['user_id'];
$namaSiswa  = $_SESSION['nama'];
$email      = $_SESSION['email'];
$namaMapel  = "(Tidak ada jadwal)";

// Inisialisasi variabel siswa dan pesan status
$nisSiswa = null;
$kelasSiswa = null;
$jurusanSiswa = null;
$statusMsg = null;
$statusType = null;

// Ambil pesan dari session (dari prosesPresensi.php)
if (isset($_SESSION['statusMsg'])) {
    $statusMsg = $_SESSION['statusMsg'];
    $statusType = $_SESSION['statusType'];
    unset($_SESSION['statusMsg']);
    unset($_SESSION['statusType']);
}

// ===== 1. AMBIL DATA LENGKAP SISWA (NIS, KELAS, JURUSAN) =====

$querySiswa = $conn->prepare("
    SELECT NIS, kelas, jurusan 
    FROM datasiswa 
    WHERE idAkun = ? 
");
$querySiswa->bind_param('s', $idAkun); 
$querySiswa->execute();
$dataSiswa = $querySiswa->get_result()->fetch_assoc(); 
$querySiswa->close();

if ($dataSiswa) {
    $nisSiswa = $dataSiswa['NIS'];
    $kelasSiswa = $dataSiswa['kelas']; 
    $jurusanSiswa = $dataSiswa['jurusan']; 
}

// -----------------------------
// PERBAIKAN: Ambil presensi terdekat (baik aktif maupun belum aktif)
// -----------------------------
$presensi = null;
$presensiAktif = false;
$statusPresensiSiswa = '-';
$bisakahPresensi = false;

if ($kelasSiswa) {

    // Query untuk mencari sesi presensi terdekat (aktif atau akan datang)
    $queryPresensi = $conn->prepare("
        SELECT 
            bp.*, 
            jm.kelas, 
            m.namaMapel,
            dg.nama AS namaGuru,
            UNIX_TIMESTAMP(bp.waktuDimulai) as mulai_unix,
            UNIX_TIMESTAMP(bp.waktuDitutup) as tutup_unix,
            UNIX_TIMESTAMP(NOW()) as now_unix
        FROM 
            buatpresensi bp
        JOIN
            jadwalmapel jm ON bp.idJadwalMapel = jm.idJadwalMapel
        JOIN
            mapel m ON jm.kodeMapel = m.kodeMapel
        JOIN
            dataguru dg ON bp.NIP = dg.NIP

        LEFT JOIN
            presensisiswa ps ON bp.idBuatPresensi = ps.idBuatPresensi AND ps.NIS = ?
        WHERE 
            jm.kelas = ? 
            AND UNIX_TIMESTAMP(bp.waktuDitutup) >= UNIX_TIMESTAMP(NOW())
            AND ps.idPresensi IS NULL
        WHERE 
            jm.kelas = ? 
            AND UNIX_TIMESTAMP(bp.waktuDitutup) >= UNIX_TIMESTAMP(NOW())
            AND NOW() >= bp.waktuDimulai 
            AND NOW() <= bp.waktuDitutup
        ORDER BY 
            bp.waktuDimulai ASC
        LIMIT 1
    ");

    $queryPresensi->bind_param('s', $kelasSiswa);
    $queryPresensi->execute();
    $presensi = $queryPresensi->get_result()->fetch_assoc();
    $queryPresensi->close();
    
    if ($presensi) {
        // Ambil timestamp dari database (lebih akurat)

        $now_unix = $presensi['now_unix'];
        $mulai_unix = $presensi['mulai_unix'];
        $tutup_unix = $presensi['tutup_unix'];
        
        // Cek apakah presensi sudah aktif (dimulai)
        if ($now_unix >= $mulai_unix && $now_unix <= $tutup_unix) {
            $presensiAktif = true;
            $bisakahPresensi = true;
        } else {
            // Presensi belum dimulai
            $presensiAktif = false;
            $bisakahPresensi = false;
        }

        // Ambil status presensi siswa
        $queryStatus = $conn->prepare("
            SELECT status FROM presensisiswa 
            WHERE idBuatPresensi = ? AND NIS = ? 
            LIMIT 1
        ");
        
        $queryStatus->bind_param('ss', $presensi['idBuatPresensi'], $nisSiswa);
        $queryStatus->execute();
        $resultStatus = $queryStatus->get_result();
        $statusPresensiSiswa = $resultStatus->num_rows > 0 ? $resultStatus->fetch_assoc()['status'] : '-';
        $queryStatus->close();
        
        // Jika sudah presensi, tidak bisa presensi lagi
        if ($statusPresensiSiswa !== '-') {
            $bisakahPresensi = false;
        }
    }
}

// Ambil jadwal selanjutnya untuk welcome box
if ($kelasSiswa) {
    $qNext = $conn->prepare("
        SELECT bp.*, m.namaMapel
        FROM buatpresensi bp
        JOIN jadwalmapel jm ON bp.idJadwalMapel = jm.idJadwalMapel
        JOIN mapel m ON jm.kodeMapel = m.kodeMapel
        WHERE jm.kelas = ? 
          AND bp.waktuDimulai >= NOW()
        ORDER BY bp.waktuDibuat ASC
        LIMIT 1
    ");
    $qNext->bind_param('s', $kelasSiswa);
    $qNext->execute();
    $next = $qNext->get_result()->fetch_assoc();
    $qNext->close();

    if ($next) {
        $namaMapel = $next['namaMapel'];
    }
}

// AMBIL DATA KEHADIRAN SISWA UNTUK CHART
$dataKehadiran = [
    'hadir' => 0,
    'terlambat' => 0,
    'sakit' => 0,
    'izin' => 0,
    'alpa' => 0
];

if ($nisSiswa) {
    $qChart = $conn->prepare("
        SELECT 
            LOWER(status) as status,
            COUNT(*) as jumlah
        FROM presensisiswa
        WHERE NIS = ?
        GROUP BY LOWER(status)
    ");
    $qChart->bind_param('s', $nisSiswa);
    $qChart->execute();
    $resultChart = $qChart->get_result();
    
    while ($row = $resultChart->fetch_assoc()) {
        $stat = $row['status'];
        $jml = $row['jumlah'];
        
        if (isset($dataKehadiran[$stat])) {
            $dataKehadiran[$stat] = $jml;
        }
    }
    $qChart->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa | E-School</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="cssSiswa/presensi.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<header>
    <img src="../assets/logo-elearning.png" class="logo" alt="E-School">
</header>

<div class="menu-row">
    <div class="dropdown">
        <button class="dropbtn"><i class="fa-solid fa-user"></i> Profil <i class="fa-solid fa-chevron-down dropdown-arrow"></i></button>
        <div class="dropdown-content">
            <a href="#"><i class="fa-solid fa-users"></i> Profil Saya</a>
        </div>
    </div>

    <div class="dropdown">
        <button class="dropbtn"><i class="fa-solid fa-clipboard-check"></i> Presensi Siswa <i class="fa-solid fa-chevron-down dropdown-arrow"></i></button>
        <div class="dropdown-content">
            <a href="rekapPresensi.php"><i class="fa-solid fa-check"></i> Lihat Presensi</a>

        </div>
    </div>

    <div class="dropdown">
        <button class="dropbtn"><i class="fa-solid fa-school"></i> Pengelolaan Pembelajaran <i class="fa-solid fa-chevron-down dropdown-arrow"></i></button>
        <div class="dropdown-content">
            <a href="mataPelajaran.php"><i class="fa-solid fa-book-open"></i> Mapel</a>
            <a href="#"><i class="fa-solid fa-pen-to-square"></i> Quiz</a>
        </div>
    </div>
    <div class="dropdown">
        <button class="dropbtn"><i class="fa-solid fa-house"></i> Dashboard</button>
        <div class="dropdown-content">
        <a href="dashboard.php"><i class="fa-solid fa-gauge"></i>Dashboard Utama</a>
        </div>
    </div>
    <div class="dropdown">
      <button class="dropbtn">
        <i class="fa-solid fa-right-from-bracket"></i>
        <a href="../Auth/logout.php" onclick="return confirm('Yakin ingin logout?')"style="text-decoration:none; color:#2e7dff;"> Logout</a>
      </button>
    </div>
</div>

<section class="welcome-box">
    <h2>Halo! Selamat Datang, <?= htmlspecialchars($namaSiswa) ?></h2>
    <p>Jadwal mapel selanjutnya adalah <b><?= htmlspecialchars($namaMapel) ?></b></p>
</section>

<?php if ($statusMsg): ?>
    <div class="notification-box <?= htmlspecialchars($statusType) ?>">
        <?= htmlspecialchars($statusMsg) ?>
    </div>
<?php endif; ?>

<div class="search-bar">
    <input type="text" placeholder="Search...">
    <button><i class="fa-solid fa-magnifying-glass"></i></button>
</div>

<main class="content-container">
    <section class="presensi-section">
        <h1>Presensi</h1>

        <div class="main-content-flex">
            <div class="card now-presensi-card">
                <div class="card-header-blue">
                    <h2>Presensi Mendatang</h2>
                </div>
                <div class="card-content">
                    <?php if ($presensi): ?>
                        <h3 class="mapel-title"><?= htmlspecialchars($presensi['namaMapel']) ?></h3>
                        <p class="description"><?= htmlspecialchars($presensi['keterangan'] ?? '(Tidak ada keterangan)') ?></p>

                        <div class="presensi-details">
                            <p><strong>Presensi Mulai</strong> : <?= htmlspecialchars($presensi['waktuDimulai']) ?></p>
                            <p><strong>Presensi Akhir</strong> : <?= htmlspecialchars($presensi['waktuDitutup']) ?></p>
                            <p><strong>Guru Pengampu</strong> : <?= htmlspecialchars($presensi['namaGuru']) ?></p>
                        </div>

                        <div class="status-box">
                            <?php if ($statusPresensiSiswa === '-'): ?>
                                <div class="belum-absen">
                                    <?php if ($presensiAktif && $bisakahPresensi): ?>
                                        <p>Anda belum melakukan presensi</p>
                                        <small>Klik untuk absen (Token diperlukan)</small>
                                        <button type="button" onclick="openModalToken()" class="x-icon-box" title="Klik untuk Absen">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    <?php else: ?>
                                        <p>Presensi belum dibuka</p>
                                        <small>Tunggu hingga waktu presensi dimulai</small>
                                        <div class="x-icon-box disabled" title="Belum bisa absen">
                                            <i class="fa-solid fa-xmark"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($presensiAktif && $bisakahPresensi): ?>
                                    <a href="#" id="openModalBtn" class="upload-izin-btn">
                                        <i class="fa-solid fa-cloud-arrow-up"></i> Unggah surat izin/sakit
                                    </a>
                                <?php else: ?>
                                    <div class="upload-izin-btn disabled">
                                        <i class="fa-solid fa-cloud-arrow-up"></i> Unggah surat izin/sakit
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="sudah-absen">
                                    <p>Status Anda: <?= htmlspecialchars(ucwords($statusPresensiSiswa)) ?></p>
                                    <small>Anda telah melakukan presensi pada waktu yang ditentukan.</small>
                                    <div class="v-icon-box"><i class="fa-solid fa-check"></i></div>
                                </div>
                                <div class="upload-izin-btn disabled">
                                    <i class="fa-solid fa-cloud-arrow-up"></i> Unggah surat izin/sakit
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php else: ?>
                        <p style="text-align: center; color: #666; font-size: 1.1em; padding: 20px 0;">Belum ada presensi berjalan saat ini.</p>

                        <div class="status-box">
                            <div class="sudah-absen">
                                <p>Tidak ada presensi</p>
                                <small>Anda telah menyelesaikan semua presensi aktif.</small>
                                <div class="v-icon-box"><i class="fa-solid fa-check"></i></div>
                            </div>
                            <div class="upload-izin-btn disabled">
                                <i class="fa-solid fa-cloud-arrow-up"></i> Unggah surat izin/sakit
                            </div>
                        </div>
                    <?php endif; ?>
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
        <?php include "../Siswa/rekapPresensi.php"; ?>
    </section>

    <div class="legend-row">
        <span class="legend-item"><span class="legend-color hadir"></span> Hadir</span>
        <span class="legend-item"><span class="legend-color alpa"></span> Alpa</span>
        <span class="legend-item"><span class="legend-color sakit"></span> Sakit</span>
        <span class="legend-item"><span class="legend-color izin"></span> Izin</span>
        <span class="legend-item"><span class="legend-color tidak-ada"></span> Tidak Ada Presensi</span>
    </div>
</main>

<!-- Modal Token Presensi -->
<div id="tokenModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            Masukkan Token Presensi
            <button type="button" class="close-modal" onclick="closeModalToken()">&times;</button>
        </div>
        <form action="prosesPresensi.php" method="POST">
            <div class="modal-body">
                <div class="modal-field">
                    <strong>NIS</strong> <span class="colon">:</span>
                    <span><?= htmlspecialchars($nisSiswa ?? '-') ?></span>
                    <input type="hidden" name="nis" value="<?= htmlspecialchars($nisSiswa ?? '') ?>">
                    <input type="hidden" name="action" value="do_presensi">
                    <input type="hidden" name="idBuatPresensi" value="<?= htmlspecialchars($presensi['idBuatPresensi'] ?? '') ?>">
                </div>
                <div class="modal-field">
                    <strong>Nama</strong> <span class="colon">:</span>
                    <span><?= htmlspecialchars($namaSiswa) ?></span>
                </div>
                <div class="modal-field">
                    <strong>Mata Pelajaran</strong> <span class="colon">:</span>
                    <span><?= htmlspecialchars($presensi['namaMapel'] ?? '-') ?></span>
                </div>
                <div class="modal-field">
                    <strong>Token Presensi</strong> <span class="colon">:</span>
                    <input type="text" name="token" id="tokenInput" class="token-input" placeholder="Masukkan token dari guru" required autofocus>
                    <small class="file-note">Dapatkan token dari guru pengampu</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="modal-kirim-btn">Absen Sekarang</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Upload Surat Izin/Sakit -->
<div id="uploadModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            Upload surat izin/sakit
            <button type="button" class="close-modal" onclick="closeModalUpload()">&times;</button>
        </div>
        <form action="prosesPresensi.php" method="POST" enctype="multipart/form-data">
            <div class="modal-body">
                <div class="modal-field">
                    <strong>NIS</strong> <span class="colon">:</span>
                    <span><?= htmlspecialchars($nisSiswa ?? '-') ?></span>
                    <input type="hidden" name="nis" value="<?= htmlspecialchars($nisSiswa ?? '') ?>">
                </div>
                <div class="modal-field">
                    <strong>Nama</strong> <span class="colon">:</span>
                    <span><?= htmlspecialchars($namaSiswa) ?></span>
                </div>
                <div class="modal-field">
                    <strong>Kelas</strong> <span class="colon">:</span>
                    <span><?= htmlspecialchars($kelasSiswa ?? '-') ?></span>
                </div>
                <div class="modal-field">
                    <strong>Jurusan</strong> <span class="colon">:</span>
                    <span><?= htmlspecialchars($jurusanSiswa ?? '-') ?></span>
                </div>
                <div class="modal-field">
                    <strong>Jenis Perizinan</strong> <span class="colon">:</span>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="jenis_izin" value="sakit" required checked> Sakit
                        </label>
                        <label>
                            <input type="radio" name="jenis_izin" value="izin"> Izin
                        </label>
                    </div>
                </div>

                <div class="upload-box" style="margin-top: 15px;">
                    <label id="fileLabel" class="upload-label" for="fileInput">Upload file maksimal ukuran 2MB, format PDF/JPG</label>
                    <input type="file" name="surat_izin" id="fileInput" class="upload-input" accept=".pdf,.jpg,.jpeg" required>
                    <label for="fileInput" class="upload-icon-btn">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="submit_izin" class="modal-kirim-btn">Kirim</button>
            </div>
        </form>
    </div>
</div>

<script>
// ========================================
// CHART.JS - DATA KEHADIRAN
// ========================================
const dataKehadiran = {
    hadir: <?= $dataKehadiran['hadir'] ?>,
    terlambat: <?= $dataKehadiran['terlambat'] ?>,
    sakit: <?= $dataKehadiran['sakit'] ?>,
    izin: <?= $dataKehadiran['izin'] ?>,
    alpa: <?= $dataKehadiran['alpa'] ?>
};

const ctx = document.getElementById('kehadiranChart').getContext('2d');
const kehadiranChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Hadir', 'Terlambat', 'Sakit', 'Izin', 'Alpa'],
        datasets: [{
            label: 'Jumlah',
            data: [
                dataKehadiran.hadir,
                dataKehadiran.terlambat,
                dataKehadiran.sakit,
                dataKehadiran.izin,
                dataKehadiran.alpa
            ],
            backgroundColor: [
                '#4CAF50',  // Hadir - Hijau
                '#FF9800',  // Terlambat - Orange
                '#2196F3',  // Sakit - Biru
                '#9C27B0',  // Izin - Ungu
                '#F44336'   // Alpa - Merah
            ],
            borderColor: '#fff',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: {
                        size: 12,
                        family: 'Poppins'
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += context.parsed + ' kali';
                        return label;
                    }
                }
            }
        }
    }
});

// ========================================
// FUNGSI MODAL
// ========================================
function openModalToken() {
    document.getElementById('tokenModal').style.display = 'flex';
    document.getElementById('tokenInput').focus();
}

function closeModalToken() {
    document.getElementById('tokenModal').style.display = 'none';
}

function closeModalUpload() {
    document.getElementById('uploadModal').style.display = 'none';
}

// ========================================
// FUNGSI JAVASCRIPT UNTUK MODAL dan Dropdown
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    const uploadModal = document.getElementById('uploadModal');
    const tokenModal = document.getElementById('tokenModal');
    const openBtn = document.getElementById('openModalBtn');
    const fileInput = document.getElementById('fileInput');
    const fileLabel = document.getElementById('fileLabel');

    if (openBtn) {
        openBtn.addEventListener('click', function(e) {
            e.preventDefault();
            uploadModal.style.display = 'flex';
            document.getElementById('tokenIzinInput').focus();
        });
    }

    // Close modal when clicking outside
    uploadModal.addEventListener('click', function(e) {
        if (e.target === uploadModal) {
            closeModalUpload();
        }
    });

    tokenModal.addEventListener('click', function(e) {
        if (e.target === tokenModal) {
            closeModalToken();
        }
    });

    // File input change handler
    fileInput.addEventListener('change', function() {
        if (this.files && this.files.length > 0) {
            fileLabel.textContent = this.files[0].name;
        } else {
            fileLabel.textContent = 'Upload File (Max 2MB, PDF/JPG)';
        }
    });

    // Dropdown functionality
    const dropdowns = document.querySelectorAll('.dropdown');
    dropdowns.forEach(dropdown => {
        const dropbtn = dropdown.querySelector('.dropbtn');
        const dropdownContent = dropdown.querySelector('.dropdown-content');

        dropbtn.addEventListener('click', function() {
            document.querySelectorAll('.dropdown-content').forEach(content => {
                if (content !== dropdownContent) {
                    content.style.display = 'none';
                }
            });
            dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
        });
    });

    window.addEventListener('click', function(e) {
        if (!e.target.matches('.dropbtn') && !e.target.matches('.dropbtn *')) {
            document.querySelectorAll('.dropdown-content').forEach(content => {
                content.style.display = 'none';
            });
        }
    });
});
</script>

</body>
</html>