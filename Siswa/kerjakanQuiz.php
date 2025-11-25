<?php
include('../config/db.php');
include('../config/session.php');
date_default_timezone_set('Asia/Jakarta');

// Cek login & role
checkLogin();
checkRole(['siswa']);

// Ambil data dari SESSION
$idAkun = $_SESSION['user_id'] ?? null;

if (!$idAkun) {
    die("Error: Session tidak valid. <a href='ngerjakanQuiz.php'>Kembali</a>");
}

// Query untuk mendapatkan data siswa
$querySiswa = "SELECT NIS, nama, kelas FROM datasiswa WHERE idAkun = ?";
$stmtSiswa = mysqli_prepare($conn, $querySiswa);
mysqli_stmt_bind_param($stmtSiswa, "s", $idAkun);
mysqli_stmt_execute($stmtSiswa);
$resultSiswa = mysqli_stmt_get_result($stmtSiswa);

if (!$resultSiswa || mysqli_num_rows($resultSiswa) == 0) {
    die("Error: Data siswa tidak ditemukan!");
}

$dataSiswa = mysqli_fetch_assoc($resultSiswa);
$NIS = $dataSiswa['NIS'];

// Ambil idQuiz dari parameter
if(!isset($_GET['idQuiz'])) {
    die("Error: ID Quiz tidak ditemukan! <a href='ngerjakanQuiz.php'>Kembali</a>");
}

$idQuiz = mysqli_real_escape_string($conn, $_GET['idQuiz']);

// Cek apakah siswa sudah pernah mengerjakan quiz ini
$queryCheck = "SELECT COUNT(*) as sudah FROM jawabanquiz WHERE idQuiz = ? AND NIS = ?";
$stmtCheck = mysqli_prepare($conn, $queryCheck);
mysqli_stmt_bind_param($stmtCheck, "ss", $idQuiz, $NIS);
mysqli_stmt_execute($stmtCheck);
$resultCheck = mysqli_stmt_get_result($stmtCheck);
$dataCheck = mysqli_fetch_assoc($resultCheck);

if($dataCheck['sudah'] > 0) {
    // Sudah pernah mengerjakan, redirect ke pembahasan
    header('Location: pembahasanQuiz.php?idQuiz=' . $idQuiz);
    exit;
}

// Query untuk mendapatkan detail quiz
$queryQuiz = "SELECT q.*, m.namaMapel 
              FROM quiz q
              INNER JOIN mapel m ON q.kodeMapel = m.kodeMapel
              WHERE q.idQuiz = ?";
$stmtQuiz = mysqli_prepare($conn, $queryQuiz);
mysqli_stmt_bind_param($stmtQuiz, "s", $idQuiz);
mysqli_stmt_execute($stmtQuiz);
$resultQuiz = mysqli_stmt_get_result($stmtQuiz);

if(!$resultQuiz || mysqli_num_rows($resultQuiz) == 0) {
    die("Error: Quiz tidak ditemukan! <a href='ngerjakanQuiz.php'>Kembali</a>");
}

$dataQuiz = mysqli_fetch_assoc($resultQuiz);

// Cek apakah quiz masih dalam waktu yang diizinkan
$waktuSekarang = time();
$waktuMulai = strtotime($dataQuiz['waktuMulai']);
$waktuSelesai = strtotime($dataQuiz['waktuSelesai']);

if($waktuSekarang < $waktuMulai) {
    die("Error: Quiz belum dimulai! <a href='ngerjakanQuiz.php'>Kembali</a>");
}

if($waktuSekarang > $waktuSelesai) {
    die("Error: Waktu quiz sudah berakhir! <a href='ngerjakanQuiz.php'>Kembali</a>");
}

// Query untuk mendapatkan soal-soal quiz
$querySoal = "SELECT * FROM soalquiz WHERE idQuiz = ? ORDER BY idSoal ASC";
$stmtSoal = mysqli_prepare($conn, $querySoal);
mysqli_stmt_bind_param($stmtSoal, "s", $idQuiz);
mysqli_stmt_execute($stmtSoal);
$resultSoal = mysqli_stmt_get_result($stmtSoal);

if(!$resultSoal || mysqli_num_rows($resultSoal) == 0) {
    die("Error: Tidak ada soal untuk quiz ini! <a href='ngerjakanQuiz.php'>Kembali</a>");
}

$soalList = [];
while($soal = mysqli_fetch_assoc($resultSoal)) {
    $soalList[] = $soal;
}

// Hitung durasi: gunakan default 30 menit atau 2 menit per soal (pilih yang lebih besar)
$durasiPerSoal = count($soalList) * 2; // 2 menit per soal
$durasiMenit = max(30, $durasiPerSoal); // Minimal 30 menit
$durasiDetik = $durasiMenit * 60;
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kerjakan Quiz - <?= htmlspecialchars($dataQuiz['judul']) ?></title>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background: #f0f4f8;
    padding: 20px;
}

.container {
    max-width: 900px;
    margin: 0 auto;
}

.quiz-header {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.quiz-info h2 {
    color: #1e40af;
    font-size: 24px;
    margin-bottom: 8px;
}

.quiz-info p {
    color: #64748b;
    font-size: 14px;
}

.timer-box {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 25px;
    border-radius: 10px;
    text-align: center;
}

.timer-box p {
    font-size: 12px;
    margin-bottom: 5px;
    opacity: 0.9;
}

.timer-box .time {
    font-size: 32px;
    font-weight: 700;
}

.warning-time {
    background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%) !important;
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

.soal-container {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.soal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e2e8f0;
}

.soal-number {
    background: #3b82f6;
    color: white;
    padding: 8px 20px;
    border-radius: 25px;
    font-weight: 600;
}

.soal-type {
    background: #e0f2fe;
    color: #0369a1;
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
}

.pertanyaan {
    font-size: 16px;
    color: #1e293b;
    margin-bottom: 25px;
    line-height: 1.6;
}

.pilihan-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.pilihan-item {
    display: flex;
    align-items: flex-start;
    padding: 15px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.pilihan-item:hover {
    border-color: #3b82f6;
    background: #eff6ff;
}

.pilihan-item input[type="radio"],
.pilihan-item input[type="checkbox"] {
    margin-top: 4px;
    margin-right: 12px;
    cursor: pointer;
    width: 18px;
    height: 18px;
}

.pilihan-item input[type="radio"]:checked,
.pilihan-item input[type="checkbox"]:checked {
    accent-color: #3b82f6;
}

.pilihan-item.selected {
    border-color: #3b82f6;
    background: #eff6ff;
}

.pilihan-label {
    font-weight: 600;
    color: #3b82f6;
    margin-right: 10px;
    min-width: 30px;
}

.pilihan-text {
    color: #475569;
    flex: 1;
    line-height: 1.5;
}

.textarea-jawaban {
    width: 100%;
    min-height: 150px;
    padding: 15px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-family: 'Poppins', sans-serif;
    font-size: 14px;
    resize: vertical;
    transition: border-color 0.3s ease;
}

.textarea-jawaban:focus {
    outline: none;
    border-color: #3b82f6;
}

.navigation-buttons {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-top: 30px;
}

.btn {
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    font-family: 'Poppins', sans-serif;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-prev {
    background: #e2e8f0;
    color: #475569;
}

.btn-prev:hover {
    background: #cbd5e1;
}

.btn-next {
    background: #3b82f6;
    color: white;
}

.btn-next:hover {
    background: #2563eb;
}

.btn-submit {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    margin-left: auto;
}

.btn-submit:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.progress-bar {
    background: white;
    padding: 15px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.progress-text {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 8px;
    text-align: center;
}

.progress {
    width: 100%;
    height: 10px;
    background: #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6 0%, #8b5cf6 100%);
    transition: width 0.3s ease;
    border-radius: 10px;
}

@media (max-width: 768px) {
    .quiz-header {
        flex-direction: column;
        text-align: center;
    }
    
    .navigation-buttons {
        flex-direction: column;
    }
    
    .btn-submit {
        margin-left: 0;
    }
}
</style>
</head>
<body>

<div class="container">
    <!-- Header Quiz -->
    <div class="quiz-header">
        <div class="quiz-info">
            <h2><?= htmlspecialchars($dataQuiz['judul']) ?></h2>
            <p><?= htmlspecialchars($dataQuiz['namaMapel']) ?> • <?= count($soalList) ?> Soal • <?= $durasiMenit ?> Menit</p>
        </div>
        <div class="timer-box" id="timerBox">
            <p>SISA WAKTU</p>
            <div class="time" id="timer">--:--</div>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="progress-bar">
        <div class="progress-text">
            Soal <span id="currentProgress">1</span> dari <?= count($soalList) ?>
        </div>
        <div class="progress">
            <div class="progress-fill" id="progressFill" style="width: 0%"></div>
        </div>
    </div>

    <!-- Form Quiz -->
    <form id="formQuiz" method="POST" action="backend/submitQuiz.php">
        <input type="hidden" name="idQuiz" value="<?= htmlspecialchars($idQuiz) ?>">
        <input type="hidden" name="NIS" value="<?= htmlspecialchars($NIS) ?>">
        <input type="hidden" name="waktuMulai" id="waktuMulai" value="">
        <input type="hidden" name="waktuSelesai" id="waktuSelesai" value="">

<?php foreach($soalList as $index => $soal): ?>
<div class="soal-container" id="soal-<?= $index ?>" style="display: <?= $index === 0 ? 'block' : 'none' ?>">

    <!-- Soal Header -->
<div class="soal-header">
    <span class="soal-number">Soal <?= $index + 1 ?></span>
    <span class="soal-type">
        <?php
        // Normalisasi type: ubah jadi lowercase dan trim spasi
        $typeNormalized = strtolower(trim($soal['type']));

        switch($typeNormalized) {
            case 'pilgan':
                echo '<i class="fas fa-check-circle"></i> Pilihan Ganda';
                break;
            case 'multi':
                echo '<i class="fas fa-list-check"></i> Pilihan Ganda Berganda';
                break;
            case 'esai':
                echo '<i class="fas fa-pen"></i> Esai';
                break;
            default:
                echo htmlspecialchars($soal['type']);
        }
        ?>
    </span>
</div>

<div class="pertanyaan">
    <?= nl2br(htmlspecialchars($soal['pertanyaan'])) ?>
</div>

<?php 
// Normalisasi type untuk kondisi if
$typeForIf = strtolower(trim($soal['type']));
?>

<?php if($typeForIf === 'pilihan ganda'): ?>
    <!-- Pilihan Ganda -->
    <div class="pilihan-container">
        <?php 
        $pilihanLabels = ['a', 'b', 'c', 'd', 'e'];
        $pilihanFields = ['opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'opsi_e'];
        
        foreach($pilihanFields as $idx => $field):
            // Ambil nilai, pastikan tidak null
            $nilaiOpsi = $soal[$field] ?? '';
            
            // Hapus semua jenis whitespace (spasi, tab, newline, dll) dari awal dan akhir string
            $nilaiTrimmed = preg_replace('/^\s+|\s+$/u', '', $nilaiOpsi);

            // Debug: Tampilkan info tentang nilai sebelum dan sesudah trim
            echo "<!-- DEBUG: Field=$field, Raw Value=" . json_encode($nilaiOpsi) . ", Trimmed=" . json_encode($nilaiTrimmed) . ", Length=" . strlen($nilaiTrimmed) . " -->";

            // Tampilkan hanya jika hasil trim bukan string kosong
            if ($nilaiTrimmed !== ''):
        ?>
        <label class="pilihan-item" data-soal="<?= $index ?>">
            <input type="radio" 
                   name="jawaban[<?= $soal['idSoal'] ?>]" 
                   value="<?= $pilihanLabels[$idx] ?>"
                   onchange="updateSelection(this)">
            <span class="pilihan-label"><?= strtoupper($pilihanLabels[$idx]) ?>.</span>
            <span class="pilihan-text"><?= htmlspecialchars($nilaiTrimmed) ?></span>
        </label>
        <?php 
            else:
                echo "<!-- DEBUG: Melewati pilihan $field karena kosong setelah trim -->";
            endif;
        endforeach; 
        ?>
    </div>

<?php elseif($typeForIf === 'multi-select'): ?>
    <!-- Pilihan Ganda Berganda -->
    <div class="pilihan-container">
        <?php 
        $pilihanLabels = ['a', 'b', 'c', 'd', 'e'];
        $pilihanFields = ['opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'opsi_e'];
        
        foreach($pilihanFields as $idx => $field):
            $nilaiOpsi = $soal[$field] ?? '';
            // Hapus semua jenis whitespace (spasi, tab, newline, dll) dari awal dan akhir string
            $nilaiTrimmed = preg_replace('/^\s+|\s+$/u', '', $nilaiOpsi);

            echo "<!-- DEBUG: Field=$field, Raw Value=" . json_encode($nilaiOpsi) . ", Trimmed=" . json_encode($nilaiTrimmed) . ", Length=" . strlen($nilaiTrimmed) . " -->";

            if ($nilaiTrimmed !== ''):
        ?>
        <label class="pilihan-item" data-soal="<?= $index ?>">
            <input type="checkbox" 
                   name="jawaban_multi[<?= $soal['idSoal'] ?>][]" 
                   value="<?= $pilihanLabels[$idx] ?>"
                   onchange="updateSelection(this)">
            <span class="pilihan-label"><?= strtoupper($pilihanLabels[$idx]) ?>.</span>
            <span class="pilihan-text"><?= htmlspecialchars($nilaiTrimmed) ?></span>
        </label>
        <?php 
            else:
                echo "<!-- DEBUG: Melewati pilihan $field karena kosong setelah trim -->";
            endif;
        endforeach; 
        ?>
    </div>
    <!-- Hidden input untuk menyimpan jawaban multi sebagai string -->
    <input type="hidden" name="jawaban[<?= $soal['idSoal'] ?>]" id="multi-<?= $soal['idSoal'] ?>">

<?php elseif($typeForIf === 'esai'): ?>
    <!-- Esai -->
    <textarea 
        class="textarea-jawaban" 
        name="jawaban[<?= $soal['idSoal'] ?>]" 
        placeholder="Tuliskan jawaban Anda di sini..."
        onchange="markAnswered(<?= $index ?>)"></textarea>
<?php endif; ?>

    <!-- Navigation Buttons -->
    <div class="navigation-buttons">
        <?php if($index > 0): ?>
        <button type="button" class="btn btn-prev" onclick="prevSoal(<?= $index ?>)">
            <i class="fas fa-arrow-left"></i> Sebelumnya
        </button>
        <?php else: ?>
        <div></div>
        <?php endif; ?>

        <?php if($index < count($soalList) - 1): ?>
        <button type="button" class="btn btn-next" onclick="nextSoal(<?= $index ?>)">
            Selanjutnya <i class="fas fa-arrow-right"></i>
        </button>
        <?php else: ?>
        <button type="button" class="btn btn-submit" onclick="confirmSubmit()">
            <i class="fas fa-paper-plane"></i> Selesai & Submit
        </button>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
    </form>
</div>

<script>
// Inisialisasi waktu
const durasiDetik = <?= $durasiDetik ?>;
let sisaWaktu = durasiDetik;
let timerInterval;
let waktuMulai = new Date();

// Set waktu mulai
document.getElementById('waktuMulai').value = formatDateTime(waktuMulai);

// Mulai timer
startTimer();

function startTimer() {
    updateTimerDisplay();
    timerInterval = setInterval(() => {
        sisaWaktu--;
        updateTimerDisplay();
        
        // Peringatan 5 menit terakhir
        if(sisaWaktu <= 300 && sisaWaktu > 0) {
            document.getElementById('timerBox').classList.add('warning-time');
        }
        
        // Waktu habis
        if(sisaWaktu <= 0) {
            clearInterval(timerInterval);
            alert('Waktu habis! Quiz akan otomatis disubmit.');
            autoSubmit();
        }
    }, 1000);
}

function updateTimerDisplay() {
    const menit = Math.floor(sisaWaktu / 60);
    const detik = sisaWaktu % 60;
    document.getElementById('timer').textContent = 
        String(menit).padStart(2, '0') + ':' + String(detik).padStart(2, '0');
}

function formatDateTime(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

let currentSoal = 0;
const totalSoal = <?= count($soalList) ?>;
const answeredSoals = new Set();

function nextSoal(current) {
    document.getElementById('soal-' + current).style.display = 'none';
    document.getElementById('soal-' + (current + 1)).style.display = 'block';
    currentSoal = current + 1;
    updateProgress();
    window.scrollTo(0, 0);
}

function prevSoal(current) {
    document.getElementById('soal-' + current).style.display = 'none';
    document.getElementById('soal-' + (current - 1)).style.display = 'block';
    currentSoal = current - 1;
    updateProgress();
    window.scrollTo(0, 0);
}

function updateProgress() {
    const progress = ((currentSoal + 1) / totalSoal) * 100;
    document.getElementById('progressFill').style.width = progress + '%';
    document.getElementById('currentProgress').textContent = currentSoal + 1;
}

function updateSelection(element) {
    // Update visual selection
    const container = element.closest('.pilihan-item');
    const soalIndex = container.dataset.soal;
    
    if(element.type === 'radio') {
        // Reset semua pilihan di soal yang sama
        document.querySelectorAll(`.pilihan-item[data-soal="${soalIndex}"]`).forEach(item => {
            item.classList.remove('selected');
        });
        container.classList.add('selected');
        markAnswered(parseInt(soalIndex));
    } else if(element.type === 'checkbox') {
        if(element.checked) {
            container.classList.add('selected');
        } else {
            container.classList.remove('selected');
        }
        updateMultiAnswer(element);
        markAnswered(parseInt(soalIndex));
    }
}

function updateMultiAnswer(checkbox) {
    // Ambil semua checkbox yang checked dalam soal yang sama
    const name = checkbox.name;
    const idSoal = name.match(/\[([^\]]+)\]/)[1];
    const checkboxes = document.querySelectorAll(`input[name="${name}"]:checked`);
    
    let selectedValues = [];
    checkboxes.forEach(cb => {
        selectedValues.push(cb.value);
    });
    
    // Update hidden input dengan jawaban yang digabung (misal: "abc")
    const hiddenInput = document.getElementById('multi-' + idSoal);
    if(hiddenInput) {
        hiddenInput.value = selectedValues.sort().join('');
    }
}

function markAnswered(index) {
    answeredSoals.add(index);
}

function confirmSubmit() {
    const unanswered = totalSoal - answeredSoals.size;
    let message = 'Apakah Anda yakin ingin menyelesaikan quiz ini?';
    
    if(unanswered > 0) {
        message += `\n\nAnda belum menjawab ${unanswered} soal.`;
    }
    
    if(confirm(message)) {
        submitQuiz();
    }
}

function submitQuiz() {
    clearInterval(timerInterval);
    
    // Set waktu selesai
    const waktuSelesai = new Date();
    document.getElementById('waktuSelesai').value = formatDateTime(waktuSelesai);
    
    // Submit form
    document.getElementById('formQuiz').submit();
}

function autoSubmit() {
    const waktuSelesai = new Date();
    document.getElementById('waktuSelesai').value = formatDateTime(waktuSelesai);
    document.getElementById('formQuiz').submit();
}

// Prevent page refresh/close
window.addEventListener('beforeunload', function (e) {
    e.preventDefault();
    e.returnValue = '';
});

// Initialize on load
window.onload = function() {
    updateProgress();
}
</script>

</body>
</html>