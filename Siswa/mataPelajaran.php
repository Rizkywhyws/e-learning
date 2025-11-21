<?php
//file mataPelajaran.php


// Set session login otomatis
$_SESSION['idAkun'] = 'SW83675';
 
// Include koneksi database
include('../config/db.php');

// Ambil data siswa yang login
$idAkun = $_SESSION['idAkun'];

// Query untuk mendapatkan NIS dari idAkun
$querySiswa = "SELECT NIS, nama FROM datasiswa WHERE idAkun = '$idAkun'";
$resultSiswa = mysqli_query($conn, $querySiswa);
$dataSiswa = mysqli_fetch_assoc($resultSiswa);
$NIS = $dataSiswa['NIS'];
$namaSiswa = $dataSiswa['nama'];

// Query untuk mendapatkan kelas siswa
$queryKelas = "SELECT kelas FROM datasiswa WHERE NIS = '$NIS'";
$resultKelas = mysqli_query($conn, $queryKelas);
$dataKelas = mysqli_fetch_assoc($resultKelas);
$kelasSiswa = $dataKelas['kelas'];

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
    <div class="menu-row">

      <div class="dropdown">
        <button class="dropbtn">
          <i class="fa-solid fa-database"></i>
          Profil
          <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
        </button>
        <div class="dropdown-content">
          <a href="#"><i class="fa-solid fa-user"></i> Profil</a>
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
          <a href="#"><i class="fa-solid fa-book-open"></i> Materi</a>
          <a href="#"><i class="fa-solid fa-file-lines"></i> Tugas</a>
          <a href="#"><i class="fa-solid fa-pen-to-square"></i> Quiz</a>
        </div>
      </div>
      <div class="dropdown">
        <button class="dropbtn"><i class="fa-solid fa-house"></i> Dashboard</button>
         <div class="dropdown-content">
        <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard Utama</a>
        </div>
  </div>
      

    </div>
  </header>
</div>

<!-- ===== WELCOME BOX ===== -->
<div class="welcome-box">
    <?php
    // Query untuk mendapatkan pelajaran selanjutnya (contoh: dari jadwal hari ini)
    $hariIni = date('l'); // Nama hari dalam bahasa Inggris
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
    
    $queryJadwal = "SELECT m.namaMapel 
                    FROM jadwalmapel jm 
                    INNER JOIN mapel m ON jm.kodeMapel = m.kodeMapel 
                    WHERE jm.Kelas = '$kelasSiswa' AND jm.hari = '$hari' 
                    ORDER BY jm.jamMulai ASC LIMIT 1";
    $resultJadwal = mysqli_query($conn, $queryJadwal);
    
    if($resultJadwal && mysqli_num_rows($resultJadwal) > 0) {
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
                
                // PERBAIKAN: Query sesuai dengan struktur tabel materi
                $queryMateri = "SELECT m.idMateri, m.judul, m.createdAt, m.deskripsi, m.filePath, m.linkVideo
                                FROM materi m
                                WHERE m.kodeMapel = '$kodeMapel'
                                ORDER BY m.createdAt DESC";
                $resultMateri = mysqli_query($conn, $queryMateri);
                
                // Error handling untuk query materi
                if (!$resultMateri) {
                    echo "<p style='color:red;'>Error query materi: " . mysqli_error($conn) . "</p>";
                    continue;
                }
                
                while($materi = mysqli_fetch_assoc($resultMateri)):
                    // PERBAIKAN: Cek apakah ada tugas untuk materi ini yang belum dikumpulkan
                    // Relasi: tugas berelasi dengan materi melalui idMateri
                    $idMateri = $materi['idMateri'];
                    
                    // Query untuk cek: apakah ada tugas DAN belum dikumpulkan
                    $queryTugasBelum = "SELECT 
                                            COUNT(CASE WHEN pt.idPengumpulan IS NULL THEN 1 END) as belum,
                                            COUNT(t.idTugas) as total
                                        FROM tugas t 
                                        LEFT JOIN pengumpulantugas pt ON t.idTugas = pt.idTugas AND pt.NIS = '$NIS'
                                        WHERE t.idMateri = '$idMateri'";
                    $resultTugasBelum = mysqli_query($conn, $queryTugasBelum);
                    
                    $statusWarna = 'biru'; // default biru
                    
                    if($resultTugasBelum) {
                        $dataTugasBelum = mysqli_fetch_assoc($resultTugasBelum);
                        
                        // Logika warna:
                        // - Merah: Ada tugas (total > 0) DAN ada yang belum dikumpulkan (belum > 0)
                        // - Biru: Tidak ada tugas (total = 0) ATAU semua tugas sudah dikumpulkan (belum = 0)
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
        loadTugas(kodeMapel, idMateri);
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
                    materiBox.innerHTML = `<a href="${data.filePath}" target="_blank">📄 Lihat File Materi</a>`;
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

function loadTugas(kodeMapel, idMateri) {
    fetch('backend/getTugas.php?kodeMapel=' + kodeMapel + '&idMateri=' + idMateri)
        .then(response => response.json())
        .then(data => {
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
                        uploadedFileBox.innerHTML = `
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: #e8f5e9; border-radius: 8px; border: 1px solid #81c784;">
                                <span style="color: #2e7d32; font-weight: 500;">📄 ${fileName}</span>
                                <a href="${data.filePathSiswa}" target="_blank" style="color: #1976d2; text-decoration: none; font-size: 12px;">Lihat File</a>
                            </div>
                        `;
                        uploadedFileBox.style.display = 'block';
                    } else {
                        uploadedFileBox.style.display = 'none';
                    }
                    
                    // Ubah tombol menjadi bisa update
                    btnKumpul.disabled = false;
                    btnKumpul.textContent = 'PERBARUI TUGAS';
                    btnKumpul.style.backgroundColor = '#fff3cd';
                    btnKumpul.style.color = '#856404';
                    
                    // Reset file input untuk upload ulang
                    document.getElementById('fileUpload').value = '';
                    document.getElementById('fileName').textContent = 'Tidak ada file yang diupload';
                    
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
                    
                    document.getElementById('fileUpload').value = '';
                    document.getElementById('fileName').textContent = 'Tidak ada file yang diupload';
                }
                
                document.getElementById('popupTugas').style.display = 'flex';
            } else {
                alert('Tidak ada tugas untuk materi ini');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat memuat tugas');
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
    
    // Jika ada idPengumpulan, berarti ini adalah update
    if(idPengumpulan) {
        formData.append('idPengumpulan', idPengumpulan);
        formData.append('isUpdate', '1');
    }
    
    fetch('backend/submitTugas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {

            // Update kolom status
            document.getElementById('tugasStatus').value = data.status;  // <-- statusWaktu
            document.getElementById('tugasDikirimPada').value = data.dikirimPada;
            document.querySelector('.status-label').textContent = 'Selesai';
            document.querySelector('.status-label').style.backgroundColor = '#bbf7d0';
            document.querySelector('.status-label').style.color = '#064e3b';

            // Munculkan file yang diupdate
            if (data.fileName) {
                document.getElementById('uploadedFileBox').innerHTML = `
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: #e8f5e9; border-radius: 8px; border: 1px solid #81c784;">
                        <span style="color: #2e7d32; font-weight: 500;">📄 ${data.fileName}</span>
                        <a href="${data.filePathSiswa}" target="_blank" style="color: #1976d2; text-decoration: none; font-size: 12px;">Lihat File</a>
                    </div>
                `;
                document.getElementById('uploadedFileBox').style.display = 'block';
            }

            alert(idPengumpulan ? 'Tugas berhasil diperbarui!' : 'Tugas berhasil dikumpulkan!');
        } else {
        alert('Gagal mengumpulkan tugas: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat mengumpulkan tugas');
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