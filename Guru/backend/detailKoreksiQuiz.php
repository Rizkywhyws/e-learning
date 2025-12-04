<?php
// file e-learningMrt/Guru/backend/detailKoreksiQuiz.php
session_start();

// Proteksi role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'guru') {
    header('Location: ../Auth/login.php');
    exit;
}

include '../../config/db.php';

$idQuiz = isset($_GET['idQuiz']) ? mysqli_real_escape_string($conn, $_GET['idQuiz']) : '';
$nis = isset($_GET['nis']) ? mysqli_real_escape_string($conn, $_GET['nis']) : '';

// DEBUG
error_log("=== DEBUG detailKoreksiQuiz ===");
error_log("idQuiz: $idQuiz, NIS: $nis");

if (empty($idQuiz) || empty($nis)) {
    header('Location: ../pengelolaanPembelajaran.php?page=koreksiQuiz');
    exit;
}

// Ambil data quiz
$queryQuiz = mysqli_query($conn, "SELECT * FROM quiz WHERE idQuiz = '$idQuiz'");

if (!$queryQuiz || mysqli_num_rows($queryQuiz) == 0) {
    die("Error: Quiz tidak ditemukan");
}

$dataQuiz = mysqli_fetch_assoc($queryQuiz);

// Ambil data siswa
$querySiswa = mysqli_query($conn, "SELECT * FROM datasiswa WHERE NIS = '$nis'");

if (!$querySiswa || mysqli_num_rows($querySiswa) == 0) {
    die("Error: Siswa tidak ditemukan");
}

$dataSiswa = mysqli_fetch_assoc($querySiswa);

// Ambil semua soal quiz
$querySoal = mysqli_query($conn, "SELECT * FROM soalquiz WHERE idQuiz = '$idQuiz' ORDER BY idSoal ASC");

if (!$querySoal) {
    die("Error: Gagal memuat soal - " . mysqli_error($conn));
}

// Proses simpan nilai
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_nilai'])) {
    error_log("=== PROSES SIMPAN NILAI ===");
    
    $totalNilai = 0;
    $jumlahSoal = 0;
    
    foreach ($_POST['nilai'] as $idSoal => $nilai) {
        $idSoal = mysqli_real_escape_string($conn, $idSoal);
        $nilai = floatval($nilai);
        $totalNilai += $nilai;
        $jumlahSoal++;
        
        error_log("Update nilai - idSoal: $idSoal, nilai: $nilai");
        
        // Update nilai per soal di jawabanquiz
        $updateQuery = "
            UPDATE jawabanquiz 
            SET nilai = '$nilai'
            WHERE idQuiz = '$idQuiz' AND NIS = '$nis' AND idSoal = '$idSoal'
        ";
        
        $result = mysqli_query($conn, $updateQuery);
        
        if (!$result) {
            error_log("ERROR Update nilai: " . mysqli_error($conn));
            die("Error: Gagal menyimpan nilai - " . mysqli_error($conn));
        }
    }
    
    // Hitung rata-rata nilai
    $nilaiAkhir = $jumlahSoal > 0 ? round($totalNilai / $jumlahSoal, 2) : 0;
    
    error_log("Nilai Akhir: $nilaiAkhir (Total: $totalNilai, Jumlah Soal: $jumlahSoal)");
    
    // Update nilai akhir di salah satu record (atau buat tabel terpisah)
    $updateFinalQuery = "
        UPDATE jawabanquiz 
        SET nilai = '$nilaiAkhir'
        WHERE idQuiz = '$idQuiz' AND NIS = '$nis'
        LIMIT 1
    ";
    
    $resultFinal = mysqli_query($conn, $updateFinalQuery);
    
    if (!$resultFinal) {
        error_log("ERROR Update nilai akhir: " . mysqli_error($conn));
    }
    
    // Ambil kodeMapel dan kelas dari dataQuiz untuk redirect
    $kodeMapel = $dataQuiz['kodeMapel'];
    $kelas = $dataQuiz['kelas'];
    
    error_log("Redirect ke: koreksiQuiz dengan params - mapel: $kodeMapel, kelas: $kelas, quiz: $idQuiz");
    
    // Redirect langsung tanpa alert
    header("Location: ../pengelolaanPembelajaran.php?page=koreksiQuiz&mapel=" . urlencode($kodeMapel) . "&kelas=" . urlencode($kelas) . "&quiz=" . urlencode($idQuiz) . "&auto=1");
    exit;
}

// Simpan kodeMapel dan kelas untuk tombol kembali
$kodeMapel = $dataQuiz['kodeMapel'];
$kelas = $dataQuiz['kelas'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detail Koreksi Quiz</title>
<link rel="stylesheet" href="../css/detailKoreksiQuiz.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header-section">
        <a href="../pengelolaanPembelajaran.php?page=koreksiQuiz&mapel=<?= urlencode($kodeMapel) ?>&kelas=<?= urlencode($kelas) ?>&quiz=<?= urlencode($idQuiz) ?>&auto=1" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
        <h2><i class="fa-solid fa-clipboard-check"></i> Detail Koreksi Quiz Essay</h2>
    </div>

    <!-- Info Quiz & Siswa -->
    <div class="info-grid">
        <div class="info-card">
            <h3><i class="fa-solid fa-file-lines"></i> Informasi Quiz</h3>
            <table class="info-table">
                <tr>
                    <td><strong>Judul Quiz</strong></td>
                    <td>: <?= htmlspecialchars($dataQuiz['judul']) ?></td>
                </tr>
                <tr>
                    <td><strong>Mata Pelajaran</strong></td>
                    <td>: <?= htmlspecialchars($dataQuiz['kodeMapel']) ?></td>
                </tr>
                <tr>
                    <td><strong>Kelas</strong></td>
                    <td>: <?= htmlspecialchars($dataQuiz['kelas']) ?></td>
                </tr>
                <tr>
                    <td><strong>Deskripsi</strong></td>
                    <td>: <?= htmlspecialchars($dataQuiz['deskripsi']) ?></td>
                </tr>
            </table>
        </div>

        <div class="info-card">
            <h3><i class="fa-solid fa-user-graduate"></i> Informasi Siswa</h3>
            <table class="info-table">
                <tr>
                    <td><strong>NIS</strong></td>
                    <td>: <?= htmlspecialchars($dataSiswa['NIS']) ?></td>
                </tr>
                <tr>
                    <td><strong>Nama</strong></td>
                    <td>: <?= htmlspecialchars($dataSiswa['nama']) ?></td>
                </tr>
                <tr>
                    <td><strong>Kelas</strong></td>
                    <td>: <?= htmlspecialchars($dataSiswa['kelas']) ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Form Koreksi -->
    <form method="POST" class="form-koreksi" id="formKoreksi">
        <div class="soal-container">
            <?php 
            $no = 1;
            mysqli_data_seek($querySoal, 0); // Reset pointer
            while ($soal = mysqli_fetch_assoc($querySoal)) {
                $idSoal = $soal['idSoal'];
                
                // Ambil jawaban siswa
                $queryJawaban = mysqli_query($conn, "
                    SELECT jawabanEsai, nilai 
                    FROM jawabanquiz 
                    WHERE idQuiz = '$idQuiz' AND NIS = '$nis' AND idSoal = '$idSoal'
                ");
                $jawaban = mysqli_fetch_assoc($queryJawaban);
                $jawabanEsai = $jawaban ? $jawaban['jawabanEsai'] : 'Tidak ada jawaban';
                $nilaiSoal = $jawaban ? $jawaban['nilai'] : '';
            ?>
            
            <div class="soal-card">
                <div class="soal-header">
                    <span class="soal-number">Soal #<?= $no ?></span>
                </div>
                
                <div class="soal-content">
                    <div class="pertanyaan-section">
                        <label class="label-bold">
                            <i class="fa-solid fa-question-circle"></i> Pertanyaan:
                        </label>
                        <div class="pertanyaan-box">
                            <?= nl2br(htmlspecialchars($soal['pertanyaan'])) ?>
                        </div>
                    </div>

                    <div class="jawaban-section">
                        <label class="label-bold">
                            <i class="fa-solid fa-pen-to-square"></i> Jawaban Siswa:
                        </label>
                        <div class="jawaban-box">
                            <?= nl2br(htmlspecialchars($jawabanEsai)) ?>
                        </div>
                    </div>

                    <div class="nilai-section">
                        <label for="nilai_<?= $idSoal ?>" class="label-bold">
                            <i class="fa-solid fa-star"></i> Nilai (0-100):
                        </label>
                        <input 
                            type="number" 
                            name="nilai[<?= $idSoal ?>]" 
                            id="nilai_<?= $idSoal ?>"
                            class="input-nilai"
                            min="0" 
                            max="100" 
                            step="0.1"
                            value="<?= $nilaiSoal ?>"
                            required
                        >
                    </div>
                </div>
            </div>

            <?php 
                $no++;
            } 
            ?>
        </div>

        <div class="action-section">
            <button type="submit" name="simpan_nilai" class="btn-submit">
                <i class="fa-solid fa-save"></i> Simpan Nilai
            </button>
            <a href="../pengelolaanPembelajaran.php?page=koreksiQuiz&mapel=<?= urlencode($kodeMapel) ?>&kelas=<?= urlencode($kelas) ?>&quiz=<?= urlencode($idQuiz) ?>&auto=1" class="btn-cancel">
                <i class="fa-solid fa-times"></i> Batal
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nilaiInputs = document.querySelectorAll('.input-nilai');
    const form = document.getElementById('formKoreksi');
    
    // Validasi input nilai
    nilaiInputs.forEach(input => {
        input.addEventListener('input', function() {
            let value = parseFloat(this.value);
            if (value < 0) this.value = 0;
            if (value > 100) this.value = 100;
        });
    });
    
    // Validasi sebelum submit
    form.addEventListener('submit', function(e) {
        let allFilled = true;
        let invalidValues = [];
        
        nilaiInputs.forEach(input => {
            if (input.value === '') {
                allFilled = false;
            }
            
            let value = parseFloat(input.value);
            if (value < 0 || value > 100) {
                invalidValues.push(input.id);
            }
        });
        
        if (!allFilled) {
            e.preventDefault();
            alert('Semua nilai harus diisi!');
            return false;
        }
        
        if (invalidValues.length > 0) {
            e.preventDefault();
            alert('Nilai harus antara 0-100!');
            return false;
        }
        
        // Konfirmasi sebelum simpan
        if (!confirm('Apakah Anda yakin ingin menyimpan nilai ini?')) {
            e.preventDefault();
            return false;
        }
    });
});
</script>

</body>
</html>