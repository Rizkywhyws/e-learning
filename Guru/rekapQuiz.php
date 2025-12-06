<?php
//file: Guru/rekapQuiz.php
session_start();

// Proteksi Login & Role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'guru') {
    header('Location: ../Auth/login.php');
    exit;
}

include '../config/db.php';

$nipGuru = isset($_SESSION['nip']) ? $_SESSION['nip'] : '';

// Query untuk mendapatkan daftar quiz
$queryQuiz = "SELECT q.*, m.namaMapel,
              DATE_FORMAT(q.waktuMulai, '%d/%m/%Y %H:%i') as waktuMulaiFormat,
              DATE_FORMAT(q.waktuSelesai, '%d/%m/%Y %H:%i') as waktuSelesaiFormat,
              (SELECT COUNT(*) FROM hasilquiz h WHERE h.idQuiz = q.idQuiz) as jumlahSubmit
              FROM quiz q
              JOIN mapel m ON q.kodeMapel = m.kodeMapel
              WHERE q.NIP = '$nipGuru'
              ORDER BY q.waktuMulai DESC";
$resultQuiz = mysqli_query($conn, $queryQuiz);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Quiz</title>
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
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        h2 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }

        .quiz-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .quiz-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 20px;
            color: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .quiz-card:hover {
            transform: translateY(-5px);
        }

        .quiz-header {
            border-bottom: 2px solid rgba(255,255,255,0.3);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .quiz-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .quiz-mapel {
            font-size: 14px;
            opacity: 0.9;
        }

        .quiz-info {
            margin: 10px 0;
            font-size: 14px;
        }

        .quiz-info i {
            margin-right: 8px;
            width: 20px;
        }

        .quiz-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-nilai {
            background: #4CAF50;
            color: white;
        }

        .btn-nilai:hover {
            background: #45a049;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 0;
            border-radius: 15px;
            width: 95%;
            max-width: 1400px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 10px 50px rgba(0,0,0,0.3);
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 24px;
        }

        .close {
            color: white;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .close:hover {
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 30px;
            max-height: calc(90vh - 80px);
            overflow-y: auto;
        }

        .loading {
            text-align: center;
            padding: 40px;
            font-size: 18px;
            color: #667eea;
        }

        .loading i {
            font-size: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Styles untuk konten modal dari lihatNilaiQuiz.php */
        .quiz-info-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .quiz-info-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
        }

        .quiz-info-header h3 {
            margin: 0;
            font-size: 18px;
        }

        .quiz-info-body {
            padding: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .info-item .label {
            font-size: 12px;
            color: #666;
            font-weight: 500;
        }

        .info-item .value {
            font-size: 14px;
            color: #333;
            font-weight: 600;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-primary .stat-icon { background: #e3f2fd; color: #2196F3; }
        .stat-success .stat-icon { background: #e8f5e9; color: #4CAF50; }
        .stat-warning .stat-icon { background: #fff3e0; color: #FF9800; }
        .stat-info .stat-icon { background: #e1f5fe; color: #00BCD4; }

        .stat-content {
            flex: 1;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }

        .search-section {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }

        .filter-status {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-status label {
            font-size: 14px;
            color: #666;
            white-space: nowrap;
        }

        .filter-status select {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-status select:focus {
            outline: none;
            border-color: #667eea;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .nilai-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .nilai-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .nilai-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }

        .nilai-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .nilai-table tbody tr:hover {
            background-color: #f8f9ff;
        }

        .nilai-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            display: inline-block;
        }

        .nilai-tinggi { background: #e8f5e9; color: #2e7d32; }
        .nilai-sedang { background: #fff3e0; color: #f57c00; }
        .nilai-rendah { background: #ffebee; color: #c62828; }
        .nilai-kosong { background: #f5f5f5; color: #999; }

        .status-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            display: inline-block;
        }

        .badge-success { background: #e8f5e9; color: #2e7d32; }
        .badge-warning { background: #fff3e0; color: #f57c00; }

        .btn-detail {
            background: #2196F3;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-detail:hover {
            background: #1976D2;
        }

        .btn-disabled {
            background: #e0e0e0;
            color: #999;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
            cursor: not-allowed;
            display: inline-block;
        }

        .action-footer {
            margin-top: 20px;
            text-align: right;
        }

        .btn-export {
            background: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-export:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fa-solid fa-chart-bar"></i> Rekap Quiz</h2>
        
        <div class="quiz-grid">
            <?php while($quiz = mysqli_fetch_assoc($resultQuiz)): ?>
            <div class="quiz-card">
                <div class="quiz-header">
                    <div class="quiz-title"><?php echo htmlspecialchars($quiz['judul']); ?></div>
                    <div class="quiz-mapel"><?php echo htmlspecialchars($quiz['namaMapel']); ?></div>
                </div>
                
                <div class="quiz-info">
                    <div><i class="fa-solid fa-users"></i> Kelas: <?php echo $quiz['kelas']; ?></div>
                    <div><i class="fa-solid fa-book"></i> Tipe: <?php echo ucwords($quiz['type']); ?></div>
                    <div><i class="fa-solid fa-calendar"></i> Deadline: <?php echo $quiz['waktuSelesaiFormat']; ?></div>
                    <div><i class="fa-solid fa-check-circle"></i> Submit: <?php echo $quiz['jumlahSubmit']; ?> siswa</div>
                </div>
                
                <div class="quiz-actions">
                    <button class="btn btn-nilai" onclick="openNilaiModal('<?php echo $quiz['idQuiz']; ?>')">
                        <i class="fa-solid fa-eye"></i> Lihat Nilai
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Modal untuk menampilkan nilai -->
    <div id="nilaiModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-solid fa-chart-line"></i> Daftar Nilai Quiz</h3>
                <span class="close" onclick="closeNilaiModal()">&times;</span>
            </div>
            <div class="modal-body" id="modalBodyContent">
                <div class="loading">
                    <i class="fa-solid fa-spinner"></i>
                    <p>Memuat data...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fungsi untuk membuka modal dan load data nilai
        function openNilaiModal(idQuiz) {
            const modal = document.getElementById('nilaiModal');
            const modalBody = document.getElementById('modalBodyContent');
            
            // Tampilkan modal
            modal.style.display = 'block';
            
            // Reset konten dengan loading
            modalBody.innerHTML = `
                <div class="loading">
                    <i class="fa-solid fa-spinner"></i>
                    <p>Memuat data...</p>
                </div>
            `;
            
            // Load data via AJAX
            fetch(`lihatNilaiQuiz.php?idQuiz=${idQuiz}&ajax=1`)
                .then(response => response.text())
                .then(data => {
                    modalBody.innerHTML = data;
                })
                .catch(error => {
                    modalBody.innerHTML = `
                        <div style="text-align:center; padding:40px; color:#dc3545;">
                            <i class="fa-solid fa-exclamation-circle" style="font-size:48px;"></i>
                            <p>Terjadi kesalahan saat memuat data.</p>
                        </div>
                    `;
                    console.error('Error:', error);
                });
        }

        // Fungsi untuk menutup modal
        function closeNilaiModal() {
            const modal = document.getElementById('nilaiModal');
            modal.style.display = 'none';
        }

        // Tutup modal jika user klik di luar modal
        window.onclick = function(event) {
            const modal = document.getElementById('nilaiModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Tutup modal dengan tombol ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeNilaiModal();
            }
        });
    </script>
</body>
</html>