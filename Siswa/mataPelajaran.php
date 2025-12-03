<?php
//file mataPelajaran.php
session_start();

// Include koneksi database
include('../config/session.php');
checkLogin();  
include('../config/db.php');

// PERBAIKAN: Ambil idAkun dari session yang benar
$idAkun = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Jika session tidak ada, redirect ke login
if (!$idAkun) {
    header('Location: ../Auth/login.php');
    exit;
}

// Query siswa SEKALIGUS ambil NIS, nama, kelas
$querySiswa = "SELECT NIS, nama, kelas FROM datasiswa WHERE idAkun = '$idAkun'";
$resultSiswa = mysqli_query($conn, $querySiswa);

// Tambahkan validasi
if (!$resultSiswa || mysqli_num_rows($resultSiswa) == 0) {
    die("Data siswa tidak ditemukan!");
}

$dataSiswa = mysqli_fetch_assoc($resultSiswa);

$NIS = $dataSiswa['NIS'];
$namaSiswa = $dataSiswa['nama'];
$kelasSiswa = $dataSiswa['kelas'];

// Simpan NIS ke session untuk digunakan di file lain
$_SESSION['NIS'] = $NIS;

// Query untuk mendapatkan mata pelajaran dari kelas siswa
$queryMapel = "SELECT DISTINCT m.kodeMapel, m.namaMapel 
               FROM jadwalmapel jm 
               INNER JOIN mapel m ON jm.kodeMapel = m.kodeMapel 
               WHERE jm.Kelas = '$kelasSiswa'
               ORDER BY m.namaMapel";
$resultMapel = mysqli_query($conn, $queryMapel);

$halaman = isset($_GET['page']) ? $_GET['page'] : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mata Pelajaran - E-School</title>

<!-- ====== Google Fonts ====== -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- ====== Font Awesome ====== -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- ====== File CSS ====== -->
<link rel="stylesheet" href="cssSiswa/dashboard.css">
<link rel="stylesheet" href="cssSiswa/mataPelajaran.css">
</head>
<body>

<!-- ===== HEADER (disamakan seperti di dashboard) ===== -->
<div class="sticky-header">
  <header>
    <img src="../assets/logo-elearning.png" class="logo" alt="E-School">

    <!-- MENU ROW -->
    <di class="menu-row">

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
          <a href="#"><i class="fa-solid fa-book-open"></i> Mapel</a>
          <a href="../Siswa/ngerjakanQuiz.php"><i class="fa-solid fa-pen-to-square"></i> Quiz</a>
        </div>
      </div>
       <div class="dropdown">
        <button class="dropbtn"><i class="fa-solid fa-house"></i> Dashboard</button>
        <div class="dropdown-content">
        <a href="dashboard.php"><i class="fa-solid fa-gauge"></i>Dashboard Utama</a>
        </div>
    </div>
      
      <!-- Tambahkan tombol logout -->
      <div class="dropdown">
        <button class="dropbtn">
        <i class="fa-solid fa-right-from-bracket"></i>
        <a href="../Auth/logout.php" onclick="return confirm('Yakin ingin logout?')"style="text-decoration:none; color:#2e7dff;"> Logout</a>
        </button>
      </div>

    </div>
  </header>
</div>

<!-- ===== WELCOME BOX ===== -->
<div class="welcome-box">
    <?php
    // Query untuk mendapatkan pelajaran selanjutnya (contoh: dari jadwal hari ini)
    $hariIni = date('l');
    $hariIndo = [
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
        'Sunday' => 'Minggu'
    ];
    $hari = $hariIndo[$hariIni];

    $jamSekarang = date('H:i:s');

    $queryJadwal = "
        SELECT m.namaMapel, jm.jamMulai
        FROM jadwalmapel jm
        INNER JOIN mapel m ON jm.kodeMapel = m.kodeMapel
        WHERE jm.Kelas = '$kelasSiswa'
        AND jm.hari = '$hari'
        AND jm.jamMulai > '$jamSekarang'
        ORDER BY jm.jamMulai ASC
        LIMIT 1
    ";

    $resultJadwal = mysqli_query($conn, $queryJadwal);

    if ($resultJadwal && mysqli_num_rows($resultJadwal) > 0) {
        $dataJadwal = mysqli_fetch_assoc($resultJadwal);
        $pelajaranSelanjutnya = $dataJadwal['namaMapel'];
    } else {
        $pelajaranSelanjutnya = "Tidak ada jadwal";
    }


    ?>
    <h2>Halo! Selamat Datang, <?= htmlspecialchars($namaSiswa) ?></h2>
    <p>Jadwal Pelajaran selanjutnya <?= htmlspecialchars($pelajaranSelanjutnya) ?></p>
</div>


<!-- ===== SECTION MATA PELAJARAN ===== -->
<section class="mapel-container">
    <h3>Mata Pelajaran</h3>
    <div class="mapel-grid">

        <?php while($mapel = mysqli_fetch_assoc($resultMapel)): ?>
        <div class="mapel-card" onclick="toggleMateri(this)">
            <p class="kode-mapel"><?= htmlspecialchars($mapel['kodeMapel']) ?></p>
            <h4><?= htmlspecialchars($mapel['namaMapel']) ?></h4>

            <!-- Materi list -->
            <div class="materi-list">
                <?php
                // Query untuk mendapatkan materi dari mata pelajaran ini
                $kodeMapel = $mapel['kodeMapel'];
                

                $queryMateri = "SELECT m.idMateri, m.judul, m.createdAt, m.deskripsi, m.filePath, m.linkVideo
                                FROM materi m
                                WHERE m.kodeMapel = '$kodeMapel'
                                ORDER BY m.createdAt DESC";
                $resultMateri = mysqli_query($conn, $queryMateri);
                
                if (!$resultMateri) {
                    echo "<p style='color:red;'>Error query materi: " . mysqli_error($conn) . "</p>";
                    continue;
                }
                
                while($materi = mysqli_fetch_assoc($resultMateri)):
                    $idMateri = $materi['idMateri'];
                    
                    // Query untuk cek tugas
                    $queryTugasBelum = "SELECT 
                                            COUNT(CASE WHEN pt.idPengumpulan IS NULL THEN 1 END) as belum,
                                            COUNT(t.idTugas) as total
                                        FROM tugas t 
                                        LEFT JOIN pengumpulantugas pt ON t.idTugas = pt.idTugas AND pt.NIS = '$NIS'
                                        WHERE t.idMateri = '$idMateri'";
                    $resultTugasBelum = mysqli_query($conn, $queryTugasBelum);
                    
                    $statusWarna = 'biru';
                    
                    if($resultTugasBelum) {
                        $dataTugasBelum = mysqli_fetch_assoc($resultTugasBelum);
                        if($dataTugasBelum['total'] > 0 && $dataTugasBelum['belum'] > 0) {
                            $statusWarna = 'merah';
                        } else {
                            $statusWarna = 'biru';
                        }
                    }
                    
                    $tanggalMateri = date('d M Y', strtotime($materi['createdAt']));
                ?>
                <div class="materi <?= $statusWarna ?>" 
                     onclick="toggleButton(this, event, '<?= $materi['idMateri'] ?>', '<?= $kodeMapel ?>')">
                    <span class="judul"><?= htmlspecialchars($materi['judul']) ?></span>
                    <span class="tgl-materi"><?= $tanggalMateri ?></span>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endwhile; ?>

    </div>
</section>

<!-- Rest of the HTML remains the same... -->
<!-- Script, popup forms, etc. -->
<!-- === Script interaktif untuk mapel === -->
<script>
function toggleMateri(card) {
    document.querySelectorAll('.mapel-card').forEach(c => {
        if (c !== card) c.classList.remove('active');
    });
    card.classList.toggle('active');
}

function toggleButton(el, event, idMateri, kodeMapel) {
    event.stopPropagation();
    document.querySelectorAll('.button-group').forEach(b => b.remove());
    const btnGroup = document.createElement('div');
    btnGroup.className = 'button-group';
    btnGroup.innerHTML = `
        <button class="btn-materi" onclick="openForm('materi', event, '${idMateri}')">Materi</button>
        <button class="btn-tugas" onclick="openForm('tugas', event, '${idMateri}', '${kodeMapel}')">Tugas</button>
    `;
    el.insertAdjacentElement('afterend', btnGroup);
}

function openForm(type, event, idMateri, kodeMapel) {
    event.stopPropagation();
    closePopup();

    if (type === 'tugas') {
        // PERBAIKAN: Hanya kirim idMateri
        loadTugas(idMateri);
    } else if (type === 'materi') {
        loadMateri(idMateri);
    }
}

function loadMateri(idMateri) {
    fetch('backend/getMateri.php?idMateri=' + idMateri)
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                document.getElementById('materiJudul').textContent = data.judul;
                document.getElementById('materiMapel').textContent = data.namaMapel;
                document.getElementById('materiTanggal').textContent = data.tanggal;
                document.getElementById('materiDeskripsi').textContent = data.deskripsi;
                
                let materiBox = document.getElementById('materiBox');
                if(data.linkVideo) {
                    materiBox.innerHTML = `<a href="${data.linkVideo}" target="_blank">🔗 ${data.linkVideo}</a>`;
                } else if(data.filePath) {
                    // Path langsung ke uploads tanpa folder Siswa
                    materiBox.innerHTML = `<a href="../${data.filePath}" target="_blank">📄 Lihat File Materi</a>`;
                } else {
                    materiBox.innerHTML = '<span style="color: #999;">Tidak ada file/link</span>';
                }
                
                document.getElementById('popupMateri').style.display = 'flex';
            } else {
                // Cek apakah materi kosong
                if(data.isEmpty) {
                    alert('Tidak ada materi');
                } else {
                    alert('Gagal memuat materi: ' + data.message);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat memuat materi');
        });
}

function loadTugas(idMateri) {
    console.log('Loading tugas for idMateri:', idMateri); // Debug
    
    fetch('backend/getTugas.php?idMateri=' + idMateri)
        .then(response => {
            console.log('Response status:', response.status); // Debug
            return response.text(); // Ambil sebagai text dulu
        })
        .then(text => {
            console.log('Raw response:', text); // Debug
            try {
                return JSON.parse(text);
            } catch(e) {
                console.error('JSON parse error:', e);
                throw new Error('Response bukan JSON valid: ' + text.substring(0, 200));
            }
        })
        .then(data => {
            console.log('Parsed data:', data); // Debug
            
            if(data.success) {
                document.getElementById('tugasJudul').textContent = data.judul;
                document.getElementById('tugasMapel').textContent = data.namaMapel;
                document.getElementById('tugasTanggal').textContent = data.tanggal;
                document.getElementById('tugasDeskripsi').value = data.deskripsi;
                
                // Tampilkan lampiran tugas dari guru
                let lampiranBox = document.getElementById('tugasLampiranBox');
                if(data.filePath) {
                    lampiranBox.innerHTML = `<a href="${data.filePath}" target="_blank">📎 Lihat File Lampiran</a>`;
                } else {
                    lampiranBox.innerHTML = '<span style="color: #999;">Tidak ada lampiran</span>';
                }
                
                document.getElementById('tugasDeadline').value = data.deadline;
                document.getElementById('idTugasHidden').value = data.idTugas;
                document.getElementById('idPengumpulanHidden').value = data.idPengumpulan || '';
                
                // Set status
                let statusLabel = document.querySelector('#popupTugas .status-label');
                let btnKumpul = document.querySelector('.btn-kumpul');
                let uploadedFileBox = document.getElementById('uploadedFileBox');
                
                if(data.status === 'selesai') {
                    statusLabel.textContent = 'Selesai';
                    statusLabel.style.backgroundColor = '#bbf7d0';
                    statusLabel.style.color = '#064e3b';
                    document.getElementById('tugasDikirimPada').value = data.dikirimPada;
                    document.getElementById('tugasNilai').value = data.nilai || '-';
                    document.getElementById('tugasStatus').value = data.statusWaktu;
                    
                    // Tampilkan file yang sudah diupload
                    if(data.filePathSiswa) {
                        let fileName = data.filePathSiswa.split('/').pop();
                        let fullUrl = '/elearning-app' + data.filePathSiswa;
                        uploadedFileBox.innerHTML = `
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: #e8f5e9; border-radius: 8px; border: 1px solid #81c784;">
                                <span style="color: #2e7d32; font-weight: 500;">📄 ${fileName}</span>
                                <a href="${fullUrl}" target="_blank" style="color: #1976d2; text-decoration: none; font-size: 12px;">Lihat File</a>
                            </div>
                        `;
                        uploadedFileBox.style.display = 'block';
                    } else {
                        uploadedFileBox.style.display = 'none';
                    }
                    
                    btnKumpul.disabled = false;
                    btnKumpul.textContent = 'PERBARUI TUGAS';
                    btnKumpul.style.backgroundColor = '#fff3cd';
                    btnKumpul.style.color = '#856404';
                    
                } else {
                    statusLabel.textContent = 'Belum Dikerjakan';
                    statusLabel.style.backgroundColor = '#ff9ea9';
                    statusLabel.style.color = 'white';
                    document.getElementById('tugasDikirimPada').value = '';
                    document.getElementById('tugasNilai').value = '';
                    document.getElementById('tugasStatus').value = '';
                    
                    uploadedFileBox.style.display = 'none';
                    
                    btnKumpul.disabled = false;
                    btnKumpul.textContent = 'KUMPULKAN';
                    btnKumpul.style.backgroundColor = '#c5d7ff';
                    btnKumpul.style.color = '#1a3e8e';
                }
                
                // Reset file input
                document.getElementById('fileUpload').value = '';
                document.getElementById('fileName').textContent = 'Tidak ada file yang diupload';
                
                // Tampilkan popup
                document.getElementById('popupTugas').style.display = 'flex';
                
            } else {
                // Tangani error dari server
                if(data.isEmpty) {
                    alert('Tidak ada tugas untuk materi ini');
                } else {
                    alert('Gagal memuat tugas: ' + (data.message || 'Unknown error'));
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat memuat tugas: ' + error.message);
        });
}





function closePopup() {
    document.querySelectorAll('.popup-overlay').forEach(p => p.style.display = 'none');
}

function showFileName() {
    const fileInput = document.getElementById('fileUpload');
    const fileName = document.getElementById('fileName');
    const btnKumpul = document.querySelector('.btn-kumpul');
    const idPengumpulan = document.getElementById('idPengumpulanHidden').value;
    
    if(fileName) {
        fileName.textContent = fileInput.files.length ? fileInput.files[0].name : 'Tidak ada file yang diupload';
    }
    
    // Ubah teks tombol jika ada file baru dipilih dan sudah pernah upload
    if(fileInput.files.length && idPengumpulan) {
        btnKumpul.textContent = 'PERBARUI TUGAS';
        btnKumpul.style.backgroundColor = '#fff3cd';
        btnKumpul.style.color = '#856404';
    }
}

function submitTugas() {
    const idTugas = document.getElementById('idTugasHidden').value;
    const idPengumpulan = document.getElementById('idPengumpulanHidden').value;
    const fileInput = document.getElementById('fileUpload');
    
    if(!fileInput.files.length) {
        alert('Silakan pilih file untuk diupload');
        return;
    }
    
    const formData = new FormData();
    formData.append('idTugas', idTugas);
    formData.append('file', fileInput.files[0]);
    if(idPengumpulan) {
        formData.append('idPengumpulan', idPengumpulan);
        formData.append('isUpdate', '1');
    }
    
    // Simpan teks asli tombol sebelum diubah
    const btnKumpul = document.querySelector('.btn-kumpul');
    const originalText = btnKumpul.textContent;

    // Ubah teks tombol saat sedang mengirim
    btnKumpul.disabled = true;
    btnKumpul.textContent = 'Mengirim...';

    fetch('backend/submitTugas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        console.log('Raw Response:', text);
        try {
            return JSON.parse(text);
        } catch(e) {
            throw new Error('Server response bukan JSON: ' + text.substring(0, 200));
        }
    })
    .then(data => {
        console.log('Parsed Data:', data);
        
        if(data.success) {
            // Update kolom status
            document.getElementById('tugasStatus').value = data.status;
            document.getElementById('tugasDikirimPada').value = data.dikirimPada;
            document.querySelector('.status-label').textContent = 'Selesai';
            document.querySelector('.status-label').style.backgroundColor = '#bbf7d0';
            document.querySelector('.status-label').style.color = '#064e3b';
            document.getElementById('idPengumpulanHidden').value = data.idPengumpulan;

            // PERBAIKAN URL - Buat URL yang BENAR
            if (data.fileName) {
                let fullUrl = '/elearning-app/' + data.filePathSiswa;
                
                console.log('File URL:', fullUrl); // Debug
                
                document.getElementById('uploadedFileBox').innerHTML = `
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: #e8f5e9; border-radius: 8px; border: 1px solid #81c784;">
                        <span style="color: #2e7d32; font-weight: 500;">📄 ${data.fileName}</span>
                        <a href="${fullUrl}" target="_blank" style="color: #1976d2; text-decoration: none; font-size: 12px;">Lihat File</a>
                    </div>
                `;
                document.getElementById('uploadedFileBox').style.display = 'block';
            }
            fileInput.value = '';
            document.getElementById('fileName').textContent = 'Tidak ada file yang diupload';

            btnKumpul.textContent = 'PERBARUI TUGAS';
            btnKumpul.style.backgroundColor = '#fff3cd';
            btnKumpul.style.color = '#856404';

            alert(data.message);
        } else {
            alert('Gagal: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan: ' + error.message);
    })
    .finally(() => {
        // Reset tombol
        btnKumpul.disabled = false;
        if (btnKumpul.textContent === 'Mengirim...') {
            btnKumpul.textContent = originalText;
        }
    });
}
</script>


<!-- ===== REKAP NILAI ===== -->
<section class="rekap-nilai">
    <h3>Rekap Nilai</h3>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Kode Mapel</th>
                    <th>Mata Pelajaran</th>

                    <?php
                    // Cari jumlah tugas paling banyak dari semua mapel
                    $queryCount = "
                        SELECT m.kodeMapel, COUNT(t.idTugas) AS jumlahTugas
                        FROM materi m
                        LEFT JOIN tugas t ON m.idMateri = t.idMateri
                        GROUP BY m.kodeMapel
                    ";
                    $resultCount = mysqli_query($conn, $queryCount);

                    $maxTugasGlobal = 0;
                    while ($row = mysqli_fetch_assoc($resultCount)) {
                        if ($row['jumlahTugas'] > $maxTugasGlobal) {
                            $maxTugasGlobal = $row['jumlahTugas'];
                        }
                    }

                    // Generate header sesuai jumlah tugas terbesar
                    for ($i = 1; $i <= $maxTugasGlobal; $i++) {
                        echo "<th>Tgs $i</th>";
                    }
                    ?>
                </tr>
            </thead>

            <tbody>
                <?php
                // Ambil semua mapel yang ada di jadwal siswa
                $queryNilai = "SELECT m.kodeMapel, m.namaMapel
                               FROM jadwalmapel jm 
                               INNER JOIN mapel m ON jm.kodeMapel = m.kodeMapel 
                               WHERE jm.Kelas = '$kelasSiswa'
                               GROUP BY m.kodeMapel
                               ORDER BY m.namaMapel";

                $resultNilai = mysqli_query($conn, $queryNilai);

                while ($mapel = mysqli_fetch_assoc($resultNilai)):
                    $kodeMapel = $mapel['kodeMapel'];

                    // Ambil semua tugas pada mapel tersebut
                    $queryTugas = "
                        SELECT t.idTugas, t.judul, t.deadline, pt.nilai, pt.status
                        FROM materi m
                        INNER JOIN tugas t ON m.idMateri = t.idMateri
                        LEFT JOIN pengumpulantugas pt 
                               ON pt.idTugas = t.idTugas AND pt.NIS = '$NIS'
                        WHERE m.kodeMapel = '$kodeMapel'
                        ORDER BY m.createdAt ASC, t.createdAt ASC
                    ";
                    $resultTugas = mysqli_query($conn, $queryTugas);

                    $tugasList = [];
                    while ($row = mysqli_fetch_assoc($resultTugas)) {
                        $tugasList[] = $row;
                    }
                ?>
                <tr>
                    <td><?= $mapel['kodeMapel'] ?></td>
                    <td><?= $mapel['namaMapel'] ?></td>

                    <?php
                    // Tampilkan data sesuai jumlah tugas pada mapel tersebut
                    foreach ($tugasList as $tugas):
                        $nilai = ($tugas['nilai'] !== null && $tugas['nilai'] !== "") ? $tugas['nilai'] : "-";
                        $status = ($tugas['status'] !== null && $tugas['status'] !== "") ? $tugas['status'] : "-";
                        $judul = htmlspecialchars($tugas['judul']);
                        $deadline = date('d M Y H:i', strtotime($tugas['deadline']));
                    ?>
                        <td class="nilai-cell"
                            data-judul="<?= $judul ?>"
                            data-deadline="<?= $deadline ?>"
                            data-nilai="<?= $nilai ?>"
                            data-status="<?= $status ?>">
                            <?= $nilai ?>
                        </td>
                    <?php endforeach; ?>

                    <?php
                    // Jika jumlah tugas mapel ini lebih sedikit dari max → tambal kolom kosong
                    $sisa = $maxTugasGlobal - count($tugasList);
                    for ($i = 0; $i < $sisa; $i++) echo "<td></td>";
                    ?>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</section>


<!-- ===== POPUP DETAIL NILAI ===== -->
<div id="popupNilai" class="popup-nilai">
    <div class="popup-nilai-content">
        <h4 id="nilaiJudul">-</h4>
        <div class="nilai-detail">
            <p><strong>Deadline:</strong> <span id="nilaiDeadline">-</span></p>
            <p><strong>Nilai:</strong> <span id="nilaiAngka">-</span></p>
            <p><strong>Status:</strong> <span id="nilaiStatus" class="status-badge">-</span></p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nilaiCells = document.querySelectorAll('.nilai-cell');
    const popupNilai = document.getElementById('popupNilai');

    nilaiCells.forEach(cell => {
        cell.style.cursor = 'pointer';

        cell.addEventListener('click', function(e) {
            const judul = this.getAttribute('data-judul');
            const deadline = this.getAttribute('data-deadline');
            const nilai = this.getAttribute('data-nilai');
            let status = this.getAttribute('data-status');

            // Jika status kosong/null/"-" → set "Belum dikerjakan"
            if (!status || status === "-" || status.trim() === "") {
                status = "Belum dikerjakan";
            }

            // Isi data popup
            document.getElementById('nilaiJudul').textContent = judul;
            document.getElementById('nilaiDeadline').textContent = deadline;
            document.getElementById('nilaiAngka').textContent = nilai;

            // Badge warna
            const statusBadge = document.getElementById('nilaiStatus');
            statusBadge.textContent = status;

            const safeStatus = status.toLowerCase();

            if (safeStatus === 'tepat waktu' || safeStatus === 'selesai') {
                statusBadge.className = 'status-badge status-selesai';
            } 
            else if (safeStatus === 'terlambat') {
                statusBadge.className = 'status-badge status-terlambat';
            } 
            else if (safeStatus === 'belum dikerjakan') {
                statusBadge.className = 'status-badge status-belum';
            }
            else {
                statusBadge.className = 'status-badge';
            }

            // Posisi popup
            popupNilai.style.left = (e.pageX + 10) + 'px';
            popupNilai.style.top = (e.pageY - 10) + 'px';

            // Animasi show
            popupNilai.style.display = 'block';
            popupNilai.classList.remove('show');
            setTimeout(() => popupNilai.classList.add('show'), 10);
        });

        cell.addEventListener('mouseleave', function() {
            setTimeout(() => {
                if (!popupNilai.matches(':hover')) hidePopup();
            }, 200);
        });
    });

    popupNilai.addEventListener('mouseleave', hidePopup);

    function hidePopup() {
        popupNilai.classList.remove('show');
        setTimeout(() => popupNilai.style.display = 'none', 150);
    }
});
</script>



<!-- ===== POPUP FORM PENGUMPULAN TUGAS ===== -->
<div id="popupTugas" class="popup-overlay">
  <div class="popup-content">
    <div class="popup-header">
      <h2>Pengumpulan Tugas</h2>
      <span class="status-label belum">Belum Dikerjakan</span>
      <button class="close-btn" onclick="closePopup()">&times;</button>
    </div>

    <div class="popup-body">
      <div class="mapel-row">
        <p class="mapel" id="tugasMapel">-</p>
        <p class="tanggal" id="tugasTanggal">-</p>
      </div>

      <h3 id="tugasJudul">-</h3>

      <label>Deskripsi Tugas</label>
      <textarea id="tugasDeskripsi" readonly></textarea>

      <label>Lampiran Guru</label>
      <div class="lampiran-box" id="tugasLampiranBox">
        <span style="color: #999;">Memuat...</span>
      </div>

      <label>Deadline</label>
      <input type="text" id="tugasDeadline" readonly>

      <input type="hidden" id="idTugasHidden">
      <input type="hidden" id="idPengumpulanHidden">

      <!-- Box untuk menampilkan file yang sudah diupload -->
      <div id="uploadedFileBox" style="display: none; margin-top: 15px; margin-bottom: 10px;">
        <!-- Akan diisi via JavaScript -->
      </div>

      <label>Upload Tugas</label>
      <div class="upload-box">
        <input type="file" id="fileUpload" accept=".pdf,.docx,.pptx,.jpg,.png" onchange="showFileName()">
        <span id="fileName">Tidak ada file yang diupload</span>
        <button class="upload-icon"><i class="fa-solid fa-upload"></i></button>
      </div>

      <div class="info-row">
        <div>
          <label>Dikirim Pada</label>
          <input type="text" id="tugasDikirimPada" readonly>
        </div>
        <div>
          <label>Nilai</label>
          <input type="text" id="tugasNilai" readonly>
        </div>
      </div>

      <label>Status</label>
      <input type="text" id="tugasStatus" readonly>

      <button class="btn-kumpul" onclick="submitTugas()">KUMPULKAN</button>
    </div>
  </div>
</div>

<!-- ===== POPUP FORM MATERI PEMBELAJARAN ===== -->
<div id="popupMateri" class="popup-overlay">
  <div class="popup-content">
    <div class="popup-header">
      <h2>Materi Pembelajaran</h2>
      <!-- <span class="status-label selesai">Selesai</span> -->
      <button class="close-btn" onclick="closePopup()">&times;</button>
    </div>

    <div class="popup-body">
      <div class="mapel-row">
        <p class="mapel" id="materiMapel">-</p>
        <p class="tanggal" id="materiTanggal">-</p>
      </div>

      <h3 id="materiJudul">-</h3>

      <p class="deskripsi" id="materiDeskripsi">-</p>

      <label>File/Link Materi</label>
      <div class="upload-box materi-box" id="materiBox">
        <span style="color: #999;">Memuat...</span>
      </div>

      <button class="btn-kumpul" onclick="closePopup()">SELESAI</button>
    </div>
  </div>
</div>

</body>
</html>