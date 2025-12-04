<?php
// file e-learningMrt/Guru/koreksiQuiz.php

// Proteksi role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'guru') {
    header('Location: ../Auth/login.php');
    exit;
}

$idAkun = $_SESSION['user_id'];

// DEBUG: Cek apakah idAkun terisi
error_log("DEBUG koreksiQuiz - idAkun: " . $idAkun);
error_log("DEBUG koreksiQuiz - Role: " . $_SESSION['role']);

// Validasi idAkun tidak boleh kosong
if (empty($idAkun)) {
    die("Error: Session user_id tidak ditemukan. Silakan login ulang.");
}

// Ambil parameter dari URL untuk auto-fill
$autoMapel = isset($_GET['mapel']) ? $_GET['mapel'] : '';
$autoKelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';
$autoQuiz = isset($_GET['quiz']) ? $_GET['quiz'] : '';
$autoLoad = isset($_GET['auto']) ? $_GET['auto'] : '0';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Koreksi Quiz</title>
<link rel="stylesheet" href="css/koreksiQuiz.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php if ($autoLoad === '1' && !empty($autoQuiz)): ?>
<div class="alert-success" id="successAlert">
    <i class="fa-solid fa-circle-check"></i> Nilai berhasil disimpan!
</div>
<script>
    setTimeout(() => {
        const alert = document.getElementById('successAlert');
        if (alert) {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => alert.remove(), 500);
        }
    }, 3000);
</script>
<?php endif; ?>

<div class="container">
    <h2><i class="fa-solid fa-clipboard-check"></i> KOREKSI QUIZ</h2>

    <!-- Container 1: Form Pemilihan -->
    <div class="form-section">
        <div class="form-group">
            <label><i class="fa-solid fa-book"></i> Mata Pelajaran:</label>
            <select id="mapelSelect">
                <option value="">-- Pilih Mapel --</option>
            </select>
        </div>

        <div class="form-group">
            <label><i class="fa-solid fa-users"></i> Kelas:</label>
            <select id="kelasSelect" disabled>
                <option value="">-- Pilih Kelas --</option>
            </select>
        </div>

        <div class="form-group">
            <label><i class="fa-solid fa-file-lines"></i> Judul Quiz:</label>
            <select id="quizSelect" disabled>
                <option value="">-- Pilih Quiz --</option>
            </select>
        </div>

        <button id="btnTampilkan" class="btn-primary" disabled>
            <i class="fa-solid fa-eye"></i> Tampilkan Data
        </button>
    </div>

    <!-- Container 2: Deskripsi & Tabel (Hidden by default) -->
    <div id="dataSection" class="data-section" style="display: none;">
        <div class="info-container">
            <div class="quiz-description">
                <h3>Deskripsi Quiz</h3>
                <div id="deskripsiQuiz">-</div>
                <div class="quiz-meta">
                    <p><strong>Waktu Mulai:</strong> <span id="waktuMulai">-</span></p>
                    <p><strong>Waktu Selesai:</strong> <span id="waktuSelesai">-</span></p>
                </div>
            </div>

            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchSiswa" placeholder="Cari nama siswa...">
            </div>
        </div>

        <div class="table-container">
            <table id="tableSiswa">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Siswa</th>
                        <th>Koreksi Jawaban</th>
                        <th>Nilai</th>
                    </tr>
                </thead>
                <tbody id="tableSiswaBody">
                    <tr>
                        <td colspan="4" class="text-center">Pilih quiz untuk melihat data siswa</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const idAkun = '<?= $idAkun ?>';
    const mapelSelect = document.getElementById('mapelSelect');
    const kelasSelect = document.getElementById('kelasSelect');
    const quizSelect = document.getElementById('quizSelect');
    const btnTampilkan = document.getElementById('btnTampilkan');
    const dataSection = document.getElementById('dataSection');
    const searchSiswa = document.getElementById('searchSiswa');
    const tableSiswaBody = document.getElementById('tableSiswaBody');

    // Parameter auto-fill dari URL
    const autoMapel = '<?= $autoMapel ?>';
    const autoKelas = '<?= $autoKelas ?>';
    const autoQuiz = '<?= $autoQuiz ?>';
    const autoLoad = '<?= $autoLoad ?>';

    // DEBUG: Cek idAkun
    console.log('DEBUG - idAkun:', idAkun);
    console.log('DEBUG - Auto params:', {autoMapel, autoKelas, autoQuiz, autoLoad});

    // Validasi idAkun
    if (!idAkun || idAkun === '') {
        alert('Error: Session tidak valid. Silakan login ulang.');
        window.location.href = '../Auth/login.php';
        return;
    }

    // Load Mapel
    loadMapel();

    mapelSelect.addEventListener('change', function() {
        const kodeMapel = this.value;
        kelasSelect.innerHTML = '<option value="">-- Pilih Kelas --</option>';
        quizSelect.innerHTML = '<option value="">-- Pilih Quiz --</option>';
        kelasSelect.disabled = true;
        quizSelect.disabled = true;
        btnTampilkan.disabled = true;
        dataSection.style.display = 'none';

        if (kodeMapel) {
            loadKelas(kodeMapel);
        }
    });

    kelasSelect.addEventListener('change', function() {
        const kelas = this.value;
        quizSelect.innerHTML = '<option value="">-- Pilih Quiz --</option>';
        quizSelect.disabled = true;
        btnTampilkan.disabled = true;
        dataSection.style.display = 'none';

        if (kelas && mapelSelect.value) {
            loadQuiz(mapelSelect.value, kelas);
        }
    });

    quizSelect.addEventListener('change', function() {
        btnTampilkan.disabled = !this.value;
        dataSection.style.display = 'none';
    });

    btnTampilkan.addEventListener('click', function() {
        if (quizSelect.value) {
            loadDataSiswa();
        }
    });

    searchSiswa.addEventListener('input', function() {
        filterTable(this.value);
    });

    function loadMapel() {
        console.log('DEBUG loadMapel - Fetching with idAkun:', idAkun);
        
        fetch(`backend/koreksiQuiz_handler.php?action=getMapel&idAkun=${idAkun}`)
            .then(res => {
                console.log('DEBUG loadMapel - Response status:', res.status);
                return res.json();
            })
            .then(data => {
                console.log('DEBUG loadMapel - Response data:', data);
                
                if (data.success) {
                    if (data.data.length === 0) {
                        alert('Tidak ada mata pelajaran yang Anda ampu. Silakan hubungi admin.');
                        return;
                    }
                    
                    data.data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.kodeMapel;
                        option.textContent = item.namaMapel;
                        mapelSelect.appendChild(option);
                    });
                    
                    console.log('DEBUG loadMapel - Total mapel loaded:', data.data.length);
                    
                    // Auto-fill jika ada parameter dari URL
                    if (autoMapel && autoLoad === '1') {
                        mapelSelect.value = autoMapel;
                        loadKelas(autoMapel, true);
                    }
                } else {
                    console.error('DEBUG loadMapel - Error:', data.message);
                    alert('Gagal memuat mata pelajaran: ' + data.message);
                }
            })
            .catch(error => {
                console.error('DEBUG loadMapel - Fetch error:', error);
                alert('Terjadi kesalahan koneksi saat memuat mata pelajaran');
            });
    }

    function loadKelas(kodeMapel, isAutoFill = false) {
        console.log('DEBUG loadKelas - kodeMapel:', kodeMapel);
        
        fetch(`backend/koreksiQuiz_handler.php?action=getKelas&idAkun=${idAkun}&kodeMapel=${kodeMapel}`)
            .then(res => res.json())
            .then(data => {
                console.log('DEBUG loadKelas - Response:', data);
                
                if (data.success) {
                    if (data.data.length === 0) {
                        alert('Tidak ada kelas untuk mata pelajaran ini.');
                        return;
                    }
                    
                    data.data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.kelas;
                        option.textContent = item.kelas;
                        kelasSelect.appendChild(option);
                    });
                    kelasSelect.disabled = false;
                    
                    // Auto-fill kelas jika diperlukan
                    if (isAutoFill && autoKelas) {
                        kelasSelect.value = autoKelas;
                        loadQuiz(kodeMapel, autoKelas, true);
                    }
                } else {
                    alert('Gagal memuat kelas: ' + data.message);
                }
            })
            .catch(error => {
                console.error('DEBUG loadKelas - Fetch error:', error);
                alert('Terjadi kesalahan koneksi saat memuat kelas');
            });
    }

    function loadQuiz(kodeMapel, kelas, isAutoFill = false) {
        console.log('DEBUG loadQuiz - kodeMapel:', kodeMapel, 'kelas:', kelas);
        
        fetch(`backend/koreksiQuiz_handler.php?action=getQuiz&kodeMapel=${kodeMapel}&kelas=${kelas}`)
            .then(res => res.json())
            .then(data => {
                console.log('DEBUG loadQuiz - Response:', data);
                
                if (data.success) {
                    if (data.data.length === 0) {
                        alert('Tidak ada quiz esai untuk kelas ini.');
                        return;
                    }
                    
                    data.data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.idQuiz;
                        option.textContent = item.judul;
                        quizSelect.appendChild(option);
                    });
                    quizSelect.disabled = false;
                    
                    // Auto-fill quiz dan auto-load data jika diperlukan
                    if (isAutoFill && autoQuiz) {
                        quizSelect.value = autoQuiz;
                        btnTampilkan.disabled = false;
                        
                        // Otomatis tampilkan data
                        setTimeout(() => {
                            loadDataSiswa();
                        }, 300);
                    }
                } else {
                    alert('Gagal memuat quiz: ' + data.message);
                }
            })
            .catch(error => {
                console.error('DEBUG loadQuiz - Fetch error:', error);
                alert('Terjadi kesalahan koneksi saat memuat quiz');
            });
    }

    function loadDataSiswa() {
        const idQuiz = quizSelect.value;
        const kelas = kelasSelect.value;

        console.log('DEBUG loadDataSiswa - idQuiz:', idQuiz, 'kelas:', kelas);

        fetch(`backend/koreksiQuiz_handler.php?action=getDataSiswa&idQuiz=${idQuiz}&kelas=${kelas}`)
            .then(res => res.json())
            .then(data => {
                console.log('DEBUG loadDataSiswa - Response:', data);
                
                if (data.success) {
                    // Tampilkan deskripsi quiz
                    document.getElementById('deskripsiQuiz').textContent = data.quiz.deskripsi;
                    document.getElementById('waktuMulai').textContent = data.quiz.waktuMulai;
                    document.getElementById('waktuSelesai').textContent = data.quiz.waktuSelesai;

                    // Tampilkan tabel siswa
                    renderTable(data.siswa);
                    dataSection.style.display = 'block';
                } else {
                    alert(data.message || 'Gagal memuat data');
                }
            })
            .catch(error => {
                console.error('DEBUG loadDataSiswa - Fetch error:', error);
                alert('Terjadi kesalahan koneksi saat memuat data siswa');
            });
    }

    function renderTable(siswaList) {
        tableSiswaBody.innerHTML = '';
        
        if (siswaList.length === 0) {
            tableSiswaBody.innerHTML = '<tr><td colspan="4" class="text-center">Tidak ada siswa di kelas ini</td></tr>';
            return;
        }

        siswaList.forEach((siswa, index) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${index + 1}</td>
                <td>${siswa.nama}</td>
                <td class="text-center">
                    ${siswa.sudahMengerjakan 
                        ? `<a href="backend/detailKoreksiQuiz.php?idQuiz=${quizSelect.value}&nis=${siswa.NIS}" class="btn-koreksi">
                            <i class="fa-solid fa-edit"></i> Koreksi
                           </a>` 
                        : '<span class="status-belum">Belum mengerjakan</span>'}
                </td>
                <td class="text-center">${siswa.nilai !== null ? siswa.nilai : '-'}</td>
            `;
            tableSiswaBody.appendChild(row);
        });
    }

    function filterTable(searchTerm) {
        const rows = tableSiswaBody.getElementsByTagName('tr');
        const lowerSearch = searchTerm.toLowerCase();

        for (let row of rows) {
            const namaCell = row.cells[1];
            if (namaCell) {
                const nama = namaCell.textContent.toLowerCase();
                row.style.display = nama.includes(lowerSearch) ? '' : 'none';
            }
        }
    }
});
</script>
</body>
</html>