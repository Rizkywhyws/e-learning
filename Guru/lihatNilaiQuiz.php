<?php
// FILE: lihatNilaiQuiz.php
// Start session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// PENTING: Selalu include config untuk memastikan $conn tersedia
if (!isset($conn)) {
    include_once("../config/db.php");
}

// Cek login untuk semua request (AJAX atau tidak)
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'guru') {
    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        echo "<p style='text-align:center; padding:40px; color:#dc3545;'>Session tidak valid. Silakan <a href='../Auth/login.php'>login</a> kembali.</p>";
    } else {
        echo "<script>alert('Anda harus login sebagai guru!'); window.location='../Auth/login.php';</script>";
    }
    exit;
}

$idQuiz = isset($_GET['idQuiz']) ? mysqli_real_escape_string($conn, $_GET['idQuiz']) : '';

if (empty($idQuiz)) {
    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        echo "<p style='text-align:center; padding:40px; color:#dc3545;'>ID Quiz tidak valid!</p>";
    } else {
        echo "<script>alert('ID Quiz tidak valid!'); window.location='?page=rekapQuiz';</script>";
    }
    exit;
}

$nipGuru = $_SESSION['nip'];

// Get quiz info - Tambahkan filter NIP untuk keamanan
$queryQuiz = "SELECT q.*, m.namaMapel,
              DATE_FORMAT(q.waktuMulai, '%d/%m/%Y %H:%i') as waktuMulaiFormat,
              DATE_FORMAT(q.waktuSelesai, '%d/%m/%Y %H:%i') as waktuSelesaiFormat
              FROM quiz q
              JOIN mapel m ON q.kodeMapel = m.kodeMapel
              WHERE q.idQuiz = '$idQuiz' AND q.NIP = '$nipGuru'";
$quizInfo = mysqli_fetch_assoc(mysqli_query($conn, $queryQuiz));

if (!$quizInfo) {
    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        echo "<p style='text-align:center; padding:40px; color:#dc3545;'>Quiz tidak ditemukan atau bukan milik Anda!</p>";
    } else {
        echo "<script>alert('Quiz tidak ditemukan atau bukan milik Anda!'); window.location='?page=rekapQuiz';</script>";
    }
    exit;
}

// Get all students in the class
$querySiswa = "SELECT NIS, nama FROM datasiswa WHERE kelas = '{$quizInfo['kelas']}' ORDER BY nama ASC";
$resultSiswa = mysqli_query($conn, $querySiswa);

$dataNilai = [];
while ($siswa = mysqli_fetch_assoc($resultSiswa)) {
    $nis = $siswa['NIS'];
    
    // Check if student has submitted
    $queryHasil = "SELECT h.nilai, DATE_FORMAT(h.tanggalSubmit, '%d/%m/%Y %H:%i') as tanggalSubmit
                   FROM hasilquiz h
                   WHERE h.idQuiz = '$idQuiz' AND h.NIS = '$nis'";
    $hasilResult = mysqli_query($conn, $queryHasil);
    
    if ($hasilResult && mysqli_num_rows($hasilResult) > 0) {
        $hasil = mysqli_fetch_assoc($hasilResult);
        $dataNilai[] = [
            'nis' => $nis,
            'nama' => $siswa['nama'],
            'kelas' => $quizInfo['kelas'],
            'nilai' => $hasil['nilai'],
            'tanggalSubmit' => $hasil['tanggalSubmit'],
            'status' => 'Sudah'
        ];
    } else {
        $dataNilai[] = [
            'nis' => $nis,
            'nama' => $siswa['nama'],
            'kelas' => $quizInfo['kelas'],
            'nilai' => '-',
            'tanggalSubmit' => '-',
            'status' => 'Belum'
        ];
    }
}

// Calculate statistics
$totalSiswa = count($dataNilai);
$sudahMengerjakan = count(array_filter($dataNilai, function($item) { return $item['status'] === 'Sudah'; }));
$belumMengerjakan = $totalSiswa - $sudahMengerjakan;

// Calculate average
$nilaiValid = array_filter(array_column($dataNilai, 'nilai'), function($val) { return $val !== '-'; });
$rataRata = count($nilaiValid) > 0 ? round(array_sum($nilaiValid) / count($nilaiValid), 2) : 0;
$nilaiTertinggi = count($nilaiValid) > 0 ? max($nilaiValid) : 0;
$nilaiTerendah = count($nilaiValid) > 0 ? min($nilaiValid) : 0;

// Cek apakah dipanggil via AJAX
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';
?>

<?php if (!$isAjax): ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Nilai Quiz</title>
<link rel="stylesheet" href="css/lihatNilaiQuiz.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="nilai-container">
  <!-- Header with Back Button -->
  <div class="header-section">
    <a href="?page=rekapQuiz" class="btn-back">
      <i class="fa-solid fa-arrow-left"></i> Kembali
    </a>
    <h2><i class="fa-solid fa-chart-bar"></i> Daftar Nilai Quiz</h2>
  </div>
<?php endif; ?>

  <!-- Quiz Info Card -->
  <div class="quiz-info-card">
    <div class="quiz-info-header">
      <h3><i class="fa-solid fa-clipboard-question"></i> Informasi Quiz</h3>
    </div>
    <div class="quiz-info-body">
      <div class="info-grid">
        <div class="info-item">
          <span class="label">Judul:</span>
          <span class="value"><?php echo htmlspecialchars($quizInfo['judul']); ?></span>
        </div>
        <div class="info-item">
          <span class="label">Mata Pelajaran:</span>
          <span class="value"><?php echo htmlspecialchars($quizInfo['namaMapel']); ?></span>
        </div>
        <div class="info-item">
          <span class="label">Kelas:</span>
          <span class="value"><?php echo $quizInfo['kelas']; ?></span>
        </div>
        <div class="info-item">
          <span class="label">Tipe Quiz:</span>
          <span class="value">
            <?php 
            if ($quizInfo['type'] === 'pilihan ganda') echo '📘 Pilihan Ganda';
            else if ($quizInfo['type'] === 'multi-select') echo '📝 Multi-Select';
            else if ($quizInfo['type'] === 'esai') echo '✏️ Esai';
            ?>
          </span>
        </div>
        <div class="info-item">
          <span class="label">Deadline:</span>
          <span class="value"><?php echo $quizInfo['waktuSelesaiFormat']; ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Statistics Cards -->
  <div class="stats-row">
    <div class="stat-card stat-primary">
      <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
      <div class="stat-content">
        <div class="stat-label">Total Siswa</div>
        <div class="stat-value"><?php echo $totalSiswa; ?></div>
      </div>
    </div>

    <div class="stat-card stat-success">
      <div class="stat-icon"><i class="fa-solid fa-check-circle"></i></div>
      <div class="stat-content">
        <div class="stat-label">Sudah Mengerjakan</div>
        <div class="stat-value"><?php echo $sudahMengerjakan; ?></div>
      </div>
    </div>

    <div class="stat-card stat-warning">
      <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
      <div class="stat-content">
        <div class="stat-label">Belum Mengerjakan</div>
        <div class="stat-value"><?php echo $belumMengerjakan; ?></div>
      </div>
    </div>

    <div class="stat-card stat-info">
      <div class="stat-icon"><i class="fa-solid fa-chart-line"></i></div>
      <div class="stat-content">
        <div class="stat-label">Rata-rata Nilai</div>
        <div class="stat-value"><?php echo $rataRata; ?></div>
      </div>
    </div>
  </div>

  <!-- Search & Filter -->
  <div class="search-section">
    <div class="search-box">
      <i class="fa-solid fa-search"></i>
      <input type="text" id="<?php echo $isAjax ? 'modalSearchInput' : 'searchInput'; ?>" placeholder="Cari nama siswa..." onkeyup="<?php echo $isAjax ? 'searchSiswaModal()' : 'searchSiswa()'; ?>">
    </div>
    
    <div class="filter-status">
      <label>Filter Status:</label>
      <select id="<?php echo $isAjax ? 'modalFilterStatus' : 'filterStatus'; ?>" onchange="<?php echo $isAjax ? 'filterByStatusModal()' : 'filterByStatus()'; ?>">
        <option value="all">Semua Siswa</option>
        <option value="sudah">Sudah Mengerjakan</option>
        <option value="belum">Belum Mengerjakan</option>
      </select>
    </div>
  </div>

  <!-- Table Nilai -->
  <div class="table-container">
    <table class="nilai-table">
      <thead>
        <tr>
          <th style="width: 50px;">No</th>
          <th>NIS</th>
          <th>Nama Siswa</th>
          <th>Kelas</th>
          <th>Nilai</th>
          <th>Tanggal Submit</th>
          <th>Status</th>
          <th style="width: 100px;">Aksi</th>
        </tr>
      </thead>
      <tbody id="<?php echo $isAjax ? 'modalNilaiTableBody' : 'nilaiTableBody'; ?>">
        <?php 
        $no = 1;
        foreach ($dataNilai as $data): 
          $statusClass = $data['status'] === 'Sudah' ? 'badge-success' : 'badge-warning';
          $statusIcon = $data['status'] === 'Sudah' ? '✓' : '⏳';
          
          $nilaiClass = '';
          if ($data['nilai'] !== '-') {
            if ($data['nilai'] >= 80) $nilaiClass = 'nilai-tinggi';
            else if ($data['nilai'] >= 60) $nilaiClass = 'nilai-sedang';
            else $nilaiClass = 'nilai-rendah';
          }
        ?>
        <tr data-status="<?php echo strtolower($data['status']); ?>" data-nama="<?php echo strtolower($data['nama']); ?>">
          <td style="text-align: center; font-weight: 600;"><?php echo $no++; ?></td>
          <td><?php echo $data['nis']; ?></td>
          <td><strong><?php echo htmlspecialchars($data['nama']); ?></strong></td>
          <td><?php echo $data['kelas']; ?></td>
          <td>
            <?php if ($data['nilai'] !== '-'): ?>
              <span class="nilai-badge <?php echo $nilaiClass; ?>"><?php echo $data['nilai']; ?></span>
            <?php else: ?>
              <span class="nilai-badge nilai-kosong">-</span>
            <?php endif; ?>
          </td>
          <td><?php echo $data['tanggalSubmit']; ?></td>
          <td>
            <span class="status-badge <?php echo $statusClass; ?>">
              <?php echo $statusIcon . ' ' . $data['status']; ?>
            </span>
          </td>
          <td>
            <?php if ($data['status'] === 'Sudah'): ?>
              <a href="<?php echo $isAjax ? 'javascript:void(0);' : '?page=detailHasilQuiz&quiz=' . $idQuiz . '&nis=' . $data['nis']; ?>" 
                 <?php if ($isAjax): ?>
                 onclick="window.open('detailHasilQuiz.php?quiz=<?php echo $idQuiz; ?>&nis=<?php echo $data['nis']; ?>', '_blank'); return false;"
                 <?php endif; ?>
                 class="btn-detail" 
                 title="Lihat Detail">
                <i class="fa-solid fa-eye"></i>
              </a>
            <?php else: ?>
              <span class="btn-disabled" title="Belum mengerjakan">
                <i class="fa-solid fa-eye-slash"></i>
              </span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Export Button -->
  <div class="action-footer">
    <button onclick="<?php echo $isAjax ? 'exportCSVModal()' : 'exportCSV()'; ?>" class="btn-export">
      <i class="fa-solid fa-download"></i> Export ke CSV
    </button>
  </div>

<?php if (!$isAjax): ?>
</div>
</body>
</html>
<?php endif; ?>

<script>
<?php if ($isAjax): ?>
// Functions untuk Modal
function searchSiswaModal() {
  const input = document.getElementById('modalSearchInput').value.toLowerCase();
  const rows = document.querySelectorAll('#modalNilaiTableBody tr');
  
  rows.forEach(row => {
    const nama = row.getAttribute('data-nama');
    if (nama.includes(input)) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
  
  updateRowNumbersModal();
}

function filterByStatusModal() {
  const filter = document.getElementById('modalFilterStatus').value;
  const rows = document.querySelectorAll('#modalNilaiTableBody tr');
  
  rows.forEach(row => {
    const status = row.getAttribute('data-status');
    
    if (filter === 'all' || filter === status) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
  
  updateRowNumbersModal();
}

function updateRowNumbersModal() {
  const rows = document.querySelectorAll('#modalNilaiTableBody tr');
  let visibleNo = 1;
  
  rows.forEach(row => {
    if (row.style.display !== 'none') {
      row.querySelector('td:first-child').textContent = visibleNo++;
    }
  });
}

function exportCSVModal() {
  let csv = 'No,NIS,Nama,Kelas,Nilai,Tanggal Submit,Status\n';
  
  const rows = document.querySelectorAll('#modalNilaiTableBody tr');
  let no = 1;
  
  rows.forEach(row => {
    if (row.style.display !== 'none') {
      const cells = row.querySelectorAll('td');
      const rowData = [
        no++,
        cells[1].textContent.trim(),
        cells[2].textContent.trim(),
        cells[3].textContent.trim(),
        cells[4].textContent.trim(),
        cells[5].textContent.trim(),
        cells[6].textContent.trim().replace(/[✓⏳]/g, '').trim()
      ];
      csv += rowData.join(',') + '\n';
    }
  });
  
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', 'nilai_quiz_<?php echo $idQuiz; ?>.csv');
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}
<?php else: ?>
// Functions untuk Page Normal
function searchSiswa() {
  const input = document.getElementById('searchInput').value.toLowerCase();
  const rows = document.querySelectorAll('#nilaiTableBody tr');
  
  rows.forEach(row => {
    const nama = row.getAttribute('data-nama');
    if (nama.includes(input)) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
  
  updateRowNumbers();
}

function filterByStatus() {
  const filter = document.getElementById('filterStatus').value;
  const rows = document.querySelectorAll('#nilaiTableBody tr');
  
  rows.forEach(row => {
    const status = row.getAttribute('data-status');
    
    if (filter === 'all' || filter === status) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
  
  updateRowNumbers();
}

function updateRowNumbers() {
  const rows = document.querySelectorAll('#nilaiTableBody tr');
  let visibleNo = 1;
  
  rows.forEach(row => {
    if (row.style.display !== 'none') {
      row.querySelector('td:first-child').textContent = visibleNo++;
    }
  });
}

function exportCSV() {
  let csv = 'No,NIS,Nama,Kelas,Nilai,Tanggal Submit,Status\n';
  
  const rows = document.querySelectorAll('#nilaiTableBody tr');
  let no = 1;
  
  rows.forEach(row => {
    if (row.style.display !== 'none') {
      const cells = row.querySelectorAll('td');
      const rowData = [
        no++,
        cells[1].textContent.trim(),
        cells[2].textContent.trim(),
        cells[3].textContent.trim(),
        cells[4].textContent.trim(),
        cells[5].textContent.trim(),
        cells[6].textContent.trim().replace(/[✓⏳]/g, '').trim()
      ];
      csv += rowData.join(',') + '\n';
    }
  });
  
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', 'nilai_quiz_<?php echo $idQuiz; ?>.csv');
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}
<?php endif; ?>
</script>