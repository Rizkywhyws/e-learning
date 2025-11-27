<?php

// Start session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../config/db.php');

// Cek login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die("Error: Anda belum login! <a href='../Auth/login.php'>Login di sini</a>");
}

// Cek role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    die("Error: Akses ditolak! Role: " . ($_SESSION['role'] ?? 'tidak ada'));
}

// Ambil data dari URL
if(!isset($_GET['idQuiz']) || !isset($_GET['nilai'])) {
    header('Location: ngerjakanQuiz.php');
    exit;
}

$idQuiz = mysqli_real_escape_string($conn, $_GET['idQuiz']);
$nilai = floatval($_GET['nilai']);
$benar = intval($_GET['benar'] ?? 0);
$total = intval($_GET['total'] ?? 0);

// Query untuk mendapatkan data quiz
$queryQuiz = "SELECT q.*, m.namaMapel 
              FROM quiz q
              INNER JOIN mapel m ON q.kodeMapel = m.kodeMapel
              WHERE q.idQuiz = ?";
$stmtQuiz = mysqli_prepare($conn, $queryQuiz);
mysqli_stmt_bind_param($stmtQuiz, "s", $idQuiz);
mysqli_stmt_execute($stmtQuiz);
$resultQuiz = mysqli_stmt_get_result($stmtQuiz);

if(!$resultQuiz || mysqli_num_rows($resultQuiz) == 0) {
    die("Error: Quiz tidak ditemukan!");
}

$dataQuiz = mysqli_fetch_assoc($resultQuiz);

// Tentukan status nilai
if($nilai >= 80) {
    $status = "Sangat Baik";
    $statusColor = "#28a745";
    $emoji = "🎉";
} elseif($nilai >= 70) {
    $status = "Baik";
    $statusColor = "#17a2b8";
    $emoji = "👍";
} elseif($nilai >= 60) {
    $status = "Cukup";
    $statusColor = "#ffc107";
    $emoji = "😊";
} else {
    $status = "Perlu Ditingkatkan";
    $statusColor = "#dc3545";
    $emoji = "💪";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hasil Quiz - <?= htmlspecialchars($dataQuiz['judul']) ?></title>

<!-- ====== Google Fonts ====== -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- ====== Font Awesome ====== -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.container {
    max-width: 600px;
    width: 100%;
}

.result-card {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    text-align: center;
    animation: slideUp 0.5s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.success-icon {
    font-size: 80px;
    margin-bottom: 20px;
    animation: bounce 1s ease infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

h1 {
    color: #333;
    font-size: 28px;
    margin-bottom: 10px;
}

.quiz-title {
    color: #666;
    font-size: 18px;
    margin-bottom: 30px;
}

.score-circle {
    width: 200px;
    height: 200px;
    margin: 30px auto;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
}

.score-inner {
    width: 170px;
    height: 170px;
    background: white;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.score-value {
    font-size: 48px;
    font-weight: 700;
    color: #333;
}

.score-label {
    font-size: 14px;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.score-details {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 20px;
    margin: 30px 0;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #dee2e6;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    color: #666;
    font-weight: 500;
}

.detail-value {
    color: #333;
    font-weight: 600;
}

.status-badge {
    display: inline-block;
    padding: 10px 25px;
    border-radius: 25px;
    font-weight: 600;
    margin: 20px 0;
    font-size: 16px;
}

.action-buttons {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.btn {
    flex: 1;
    padding: 15px 30px;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

@media (max-width: 600px) {
    .result-card {
        padding: 30px 20px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>
</head>
<body>

<div class="container">
    <div class="result-card">
        <div class="success-icon"><?= $emoji ?></div>
        
        <h1>Quiz Selesai!</h1>
        <p class="quiz-title"><?= htmlspecialchars($dataQuiz['judul']) ?></p>
        
        <div class="score-circle">
            <div class="score-inner">
                <div class="score-value"><?= number_format($nilai, 0) ?></div>
                <div class="score-label">Nilai</div>
            </div>
        </div>
        
        <div class="status-badge" style="background: <?= $statusColor ?>15; color: <?= $statusColor ?>">
            <?= $status ?>
        </div>
        
        <div class="score-details">
            <div class="detail-row">
                <span class="detail-label">Mata Pelajaran</span>
                <span class="detail-value"><?= htmlspecialchars($dataQuiz['namaMapel']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Jawaban Benar</span>
                <span class="detail-value"><?= $benar ?> dari <?= $total ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Persentase</span>
                <span class="detail-value"><?= number_format($nilai, 1) ?>%</span>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="pembahasanQuiz.php?idQuiz=<?= $idQuiz ?>" class="btn btn-primary">
                <i class="fa-solid fa-eye"></i> Lihat Pembahasan
            </a>
            <a href="ngerjakanQuiz.php" class="btn btn-secondary">
                <i class="fa-solid fa-home"></i> Kembali
            </a>
        </div>
    </div>
</div>

<script>
// Prevent back button
window.history.pushState(null, "", window.location.href);
window.onpopstate = function() {
    window.history.pushState(null, "", window.location.href);
};

// Confetti effect for good scores
if(<?= $nilai ?> >= 70) {
    // Simple confetti animation
    setTimeout(() => {
        for(let i = 0; i < 50; i++) {
            createConfetti();
        }
    }, 300);
}

function createConfetti() {
    const confetti = document.createElement('div');
    confetti.style.position = 'fixed';
    confetti.style.width = '10px';
    confetti.style.height = '10px';
    confetti.style.background = ['#667eea', '#764ba2', '#28a745', '#ffc107', '#dc3545'][Math.floor(Math.random() * 5)];
    confetti.style.left = Math.random() * window.innerWidth + 'px';
    confetti.style.top = '-10px';
    confetti.style.borderRadius = '50%';
    confetti.style.opacity = '1';
    confetti.style.zIndex = '9999';
    document.body.appendChild(confetti);
    
    let pos = 0;
    const interval = setInterval(() => {
        if(pos >= window.innerHeight) {
            clearInterval(interval);
            confetti.remove();
        } else {
            pos += 5;
            confetti.style.top = pos + 'px';
            confetti.style.opacity = 1 - (pos / window.innerHeight);
        }
    }, 20);
}
</script>

</body>
</html>