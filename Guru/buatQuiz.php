<?php
include_once("../config/db.php");

// Cek apakah guru sudah login
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'guru') {
  echo "<script>alert('Anda harus login sebagai guru!'); window.location='../Auth/login.php';</script>";
  exit;
}

$nipGuru = $_SESSION['nip'];

// ========================
// CEK MODE EDIT
// ========================
$isEdit = false;
$dataEdit = null;

if (isset($_GET['edit'])) {
  $idQuizEdit = mysqli_real_escape_string($conn, $_GET['edit']);
  $queryEdit = "SELECT * FROM quiz WHERE idQuiz = '$idQuizEdit' AND NIP = '$nipGuru'";
  $resultEdit = mysqli_query($conn, $queryEdit);
  
  if ($resultEdit && mysqli_num_rows($resultEdit) > 0) {
    $isEdit = true;
    $dataEdit = mysqli_fetch_assoc($resultEdit);
  } else {
    echo "<script>alert('Quiz tidak ditemukan!'); window.location='?page=buatQuiz';</script>";
    exit;
  }
}

// ========================
// PROSES UPDATE QUIZ (EDIT)
// ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
  $idQuiz = mysqli_real_escape_string($conn, $_POST['idQuiz']);
  $kodeMapel = mysqli_real_escape_string($conn, $_POST['kodeMapel']);
  $judul = mysqli_real_escape_string($conn, $_POST['judul']);
  $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
  $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
  
  $queryUpdate = "UPDATE quiz 
                  SET kodeMapel = '$kodeMapel',
                      judul = '$judul', 
                      deskripsi = '$deskripsi', 
                      kelas = '$kelas'
                  WHERE idQuiz = '$idQuiz' AND NIP = '$nipGuru'";
  
  if (mysqli_query($conn, $queryUpdate)) {
    echo "<script>
      alert('Quiz berhasil diupdate!');
      window.location='?page=buatQuiz';
    </script>";
    exit;
  } else {
    echo "<script>alert('Gagal mengupdate quiz!');</script>";
  }
}

// ========================
// PROSES HAPUS QUIZ
// ========================
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['idQuiz'])) {
  $idQuiz = mysqli_real_escape_string($conn, $_GET['idQuiz']);
  
  // Cek apakah quiz milik guru ini
  $checkQuery = "SELECT idQuiz FROM quiz WHERE idQuiz = '$idQuiz' AND NIP = '$nipGuru'";
  $checkResult = mysqli_query($conn, $checkQuery);
  
  if (mysqli_num_rows($checkResult) > 0) {
    // Mulai transaction
    mysqli_begin_transaction($conn);
    
    try {
      // 1. Hapus jawaban siswa
      $deleteJawaban = "DELETE FROM jawabanquiz WHERE idQuiz = '$idQuiz'";
      mysqli_query($conn, $deleteJawaban);
      
      // 2. Hapus hasil quiz
      $deleteHasil = "DELETE FROM hasilquiz WHERE idQuiz = '$idQuiz'";
      mysqli_query($conn, $deleteHasil);
      
      // 3. Hapus soal quiz
      $deleteSoal = "DELETE FROM soalquiz WHERE idQuiz = '$idQuiz'";
      mysqli_query($conn, $deleteSoal);
      
      // 4. Hapus quiz
      $deleteQuiz = "DELETE FROM quiz WHERE idQuiz = '$idQuiz'";
      mysqli_query($conn, $deleteQuiz);
      
      // Commit transaction
      mysqli_commit($conn);
      
      echo "<script>
        alert('Quiz berhasil dihapus beserta semua soal dan jawabannya!');
        window.location='?page=buatQuiz';
      </script>";
      exit;
      
    } catch (Exception $e) {
      // Rollback jika ada error
      mysqli_rollback($conn);
      echo "<script>
        alert('Gagal menghapus quiz: " . $e->getMessage() . "');
        window.location='?page=buatQuiz';
      </script>";
      exit;
    }
  } else {
    echo "<script>
      alert('Quiz tidak ditemukan atau bukan milik Anda!');
      window.location='?page=buatQuiz';
    </script>";
    exit;
  }
}

// ========================
// PROSES SIMPAN QUIZ BARU
// ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
  $kodeMapel = mysqli_real_escape_string($conn, $_POST['kodeMapel']);
  $judul = mysqli_real_escape_string($conn, $_POST['judul']);
  $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
  $waktuMulai = mysqli_real_escape_string($conn, $_POST['waktuMulai']);
  $waktuSelesai = mysqli_real_escape_string($conn, $_POST['waktuSelesai']);
  $tipeQuiz = mysqli_real_escape_string($conn, $_POST['tipeQuiz']);
  $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);

  if (empty($tipeQuiz)) {
    echo "<script>alert('Pilih tipe quiz terlebih dahulu!');</script>";
    exit;
  }

  // Generate ID quiz otomatis
  $idQuiz = "QZ" . rand(1000, 9999);

  // Simpan quiz dengan kolom type
  $query = "INSERT INTO quiz (idQuiz, kodeMapel, NIP, judul, deskripsi, waktuMulai, waktuSelesai, kelas, type)
            VALUES ('$idQuiz', '$kodeMapel', '$nipGuru', '$judul', '$deskripsi', '$waktuMulai', '$waktuSelesai', '$kelas', '$tipeQuiz')";

  if ($conn->query($query)) {
    echo "<script>
      alert('Quiz berhasil dibuat!');
      window.location='buatSoal.php?idQuiz=$idQuiz&type=$tipeQuiz';
    </script>";
    exit;
  } else {
    echo "<script>alert('Gagal menyimpan quiz: " . $conn->error . "');</script>";
    exit;
  }
}

// ========================
// QUERY UNTUK REKAP QUIZ
// ========================
$filterMapel = isset($_GET['mapel']) ? mysqli_real_escape_string($conn, $_GET['mapel']) : '';
$filterKelas = isset($_GET['kelas']) ? mysqli_real_escape_string($conn, $_GET['kelas']) : '';

$queryQuiz = "SELECT q.*, m.namaMapel,
              DATE_FORMAT(q.waktuMulai, '%d/%m/%Y %H:%i') as waktuMulaiFormat,
              DATE_FORMAT(q.waktuSelesai, '%d/%m/%Y %H:%i') as waktuSelesaiFormat,
              (SELECT COUNT(DISTINCT NIS) FROM jawabanquiz WHERE idQuiz = q.idQuiz) as jumlahSiswaKerjakan,
              (SELECT COUNT(*) FROM datasiswa WHERE kelas = q.kelas) as totalSiswaKelas
              FROM quiz q
              JOIN mapel m ON q.kodeMapel = m.kodeMapel
              WHERE q.NIP = '$nipGuru'";

if (!empty($filterMapel)) {
  $queryQuiz .= " AND q.kodeMapel = '$filterMapel'";
}

if (!empty($filterKelas)) {
  $queryQuiz .= " AND q.kelas = '$filterKelas'";
}

$queryQuiz .= " ORDER BY q.waktuMulai DESC";
$resultQuiz = mysqli_query($conn, $queryQuiz);

$queryMapel = "SELECT DISTINCT m.kodeMapel, m.namaMapel 
               FROM jadwalmapel jm
               JOIN mapel m ON jm.kodeMapel = m.kodeMapel
               WHERE jm.nipGuru = '$nipGuru'
               ORDER BY m.namaMapel ASC";
$resultMapel = mysqli_query($conn, $queryMapel);

$queryKelas = "SELECT DISTINCT kelas 
               FROM jadwalmapel 
               WHERE nipGuru = '$nipGuru'
               ORDER BY kelas ASC";
$resultKelas = mysqli_query($conn, $queryKelas);
?>

<link rel="stylesheet" href="css/buatQuiz.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="css/rekapQuiz.css?v=<?php echo time(); ?>">

<!-- FORM BUAT/EDIT QUIZ -->
<div class="form-container">
  <h2><?php echo $isEdit ? '✏ Edit Quiz' : '➕ Tambah / Buat Quiz'; ?></h2>

  <form action="" method="POST" enctype="multipart/form-data" id="quizForm">
    
    <?php if ($isEdit): ?>
      <!-- Mode Edit -->
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="idQuiz" value="<?php echo $dataEdit['idQuiz']; ?>">
      
      <!-- Info Quiz (Read Only) -->
      <div style="background: #f0f4ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <p style="margin: 0; color: #666;">
          <strong>ID Quiz:</strong> <?php echo $dataEdit['idQuiz']; ?> | 
          <strong>Tipe:</strong> <?php echo ucfirst($dataEdit['type']); ?>
        </p>
      </div>

    <?php else: ?>
      <!-- Mode Tambah Baru -->
      <input type="hidden" id="tipeQuizHidden" name="tipeQuiz">
    <?php endif; ?>

    <!-- Pilih Mapel -->
    <label for="kodeMapel">Mata Pelajaran <span style="color:red;">*</span></label>
    <select id="kodeMapel" name="kodeMapel" required>
      <option value="">-- Pilih Mata Pelajaran --</option>
      <?php
        mysqli_data_seek($resultMapel, 0);
        $queryMapelForm = "SELECT DISTINCT m.kodeMapel, m.namaMapel 
                       FROM jadwalmapel jm
                       JOIN mapel m ON jm.kodeMapel = m.kodeMapel
                       WHERE jm.nipGuru = '$nipGuru'
                       ORDER BY m.namaMapel ASC";
        $mapel = $conn->query($queryMapelForm);

        if ($mapel && $mapel->num_rows > 0) {
          while ($row = $mapel->fetch_assoc()) {
            $selected = ($isEdit && $dataEdit['kodeMapel'] === $row['kodeMapel']) ? 'selected' : '';
            echo "<option value='{$row['kodeMapel']}' $selected>{$row['namaMapel']}</option>";
          }
        } else {
          echo "<option value='' disabled>Tidak ada mata pelajaran</option>";
        }
      ?>
    </select>

    <!-- Pilih Kelas -->
    <label for="kelas">Kelas <span style="color:red;">*</span></label>
    <select id="kelas" name="kelas" required>
      <option value="">-- Pilih Kelas --</option>
      <?php
        $qKelas = "SELECT DISTINCT kelas 
                   FROM jadwalmapel 
                   WHERE nipGuru = '$nipGuru'
                   ORDER BY kelas ASC";
        $kelasResult = $conn->query($qKelas);

        if ($kelasResult && $kelasResult->num_rows > 0) {
          while ($row = $kelasResult->fetch_assoc()) {
            $selected = ($isEdit && $dataEdit['kelas'] === $row['kelas']) ? 'selected' : '';
            echo "<option value='{$row['kelas']}' $selected>{$row['kelas']}</option>";
          }
        } else {
          echo "<option value='' disabled>Tidak ada kelas</option>";
        }
      ?>
    </select>

    <!-- Judul -->
    <label for="judul">Judul Quiz <span style="color:red;">*</span></label>
    <input type="text" id="judul" name="judul" placeholder="Masukkan judul quiz..." 
           value="<?php echo $isEdit ? htmlspecialchars($dataEdit['judul']) : ''; ?>" required>

    <!-- Deskripsi -->
    <label for="deskripsi">Deskripsi Quiz <span style="color:red;">*</span></label>
    <textarea id="deskripsi" name="deskripsi" placeholder="Tuliskan deskripsi quiz..." required><?php echo $isEdit ? htmlspecialchars($dataEdit['deskripsi']) : ''; ?></textarea>

    <?php if (!$isEdit): ?>
      <!-- Waktu Mulai & Selesai -->
      <label for="waktuMulai">Waktu Mulai <span style="color:red;">*</span></label>
      <input type="datetime-local" id="waktuMulai" name="waktuMulai" required>

      <label for="waktuSelesai">Waktu Selesai <span style="color:red;">*</span></label>
      <input type="datetime-local" id="waktuSelesai" name="waktuSelesai" required>

      <!-- Pilih Jenis Quiz -->
      <div class="upload-box">
        <label>Pilih Jenis Quiz <span style="color:red;">*</span></label>
        <p style="font-size: 13px; color: #666; margin: 5px 0 10px 0;">
          <i class="fa-solid fa-info-circle"></i> Isi semua form di atas, lalu klik jenis quiz untuk melanjutkan
        </p>
        <div class="type-options">
          <button type="button" onclick="pilihTipeQuiz('pilihan ganda')" class="save-btn">
            📘 Pilihan Ganda
          </button>
          <button type="button" onclick="pilihTipeQuiz('multi-select')" class="save-btn">
            📝 Multiselect
          </button>
          <button type="button" onclick="pilihTipeQuiz('esai')" class="save-btn">
            ✏ Esai
          </button>
        </div>
      </div>
    <?php else: ?>
      <!-- Tombol Update -->
      <div style="display: flex; gap: 10px; margin-top: 20px;">
        <button type="submit" class="save-btn" style="flex: 1;">
          <i class="fa-solid fa-check"></i> Update Quiz
        </button>
        <a href="?page=buatQuiz" class="save-btn" style="flex: 1; background: #6c757d; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">
          <i class="fa-solid fa-times"></i> Batal
        </a>
      </div>
    <?php endif; ?>

  </form>
</div>

<!-- REKAP QUIZ -->
<div class="rekap-container" style="margin-top: 40px;">
  <div class="header-section">
    <h2><i class="fa-solid fa-clipboard-list"></i> Rekap Quiz yang Telah Dibuat</h2>
  </div>

  <!-- Filter Section -->
  <div class="filter-section">
    <div class="filter-row">
      <div class="filter-item">
        <label for="filterMapel">Mata Pelajaran</label>
        <select id="filterMapel" onchange="applyFilter()">
          <option value="">-- Semua Mapel --</option>
          <?php
          mysqli_data_seek($resultMapel, 0);
          while ($mapel = mysqli_fetch_assoc($resultMapel)) {
            $selected = ($filterMapel == $mapel['kodeMapel']) ? 'selected' : '';
            echo "<option value='{$mapel['kodeMapel']}' $selected>{$mapel['namaMapel']}</option>";
          }
          ?>
        </select>
      </div>

      <div class="filter-item">
        <label for="filterKelas">Kelas</label>
        <select id="filterKelas" onchange="applyFilter()">
          <option value="">-- Semua Kelas --</option>
          <?php
          mysqli_data_seek($resultKelas, 0);
          while ($kelas = mysqli_fetch_assoc($resultKelas)) {
            $selected = ($filterKelas == $kelas['kelas']) ? 'selected' : '';
            echo "<option value='{$kelas['kelas']}' $selected>{$kelas['kelas']}</option>";
          }
          ?>
        </select>
      </div>

      <?php if (!empty($filterMapel) || !empty($filterKelas)): ?>
      <div class="filter-item" style="display: flex; align-items: flex-end;">
        <button onclick="resetFilter()" class="btn-reset">
          <i class="fa-solid fa-rotate-left"></i> Reset Filter
        </button>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Statistics Cards -->
  <div class="stats-cards">
    <div class="stat-card stat-primary">
      <div class="stat-icon"><i class="fa-solid fa-clipboard-question"></i></div>
      <div class="stat-content">
        <div class="stat-label">Total Quiz</div>
        <div class="stat-value"><?php echo mysqli_num_rows($resultQuiz); ?></div>
      </div>
    </div>

    <div class="stat-card stat-success">
      <div class="stat-icon"><i class="fa-solid fa-book-open"></i></div>
      <div class="stat-content">
        <div class="stat-label">Mapel Diampu</div>
        <div class="stat-value">
          <?php 
          mysqli_data_seek($resultMapel, 0);
          echo mysqli_num_rows($resultMapel);
          ?>
        </div>
      </div>
    </div>

    <div class="stat-card stat-info">
      <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
      <div class="stat-content">
        <div class="stat-label">Kelas Diampu</div>
        <div class="stat-value">
          <?php 
          mysqli_data_seek($resultKelas, 0);
          echo mysqli_num_rows($resultKelas);
          ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Table Quiz -->
  <div class="table-container">
    <?php if (mysqli_num_rows($resultQuiz) > 0): ?>
    <table class="quiz-table">
      <thead>
        <tr>
          <th style="width: 50px;">No</th>
          <th>Tanggal Dibuat</th>
          <th>Judul Quiz</th>
          <th>Mapel</th>
          <th>Kelas</th>
          <th>Tipe</th>
          <th>Deadline</th>
          <th>Siswa Mengerjakan</th>
          <th style="width: 250px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $no = 1;
        mysqli_data_seek($resultQuiz, 0);
        while ($quiz = mysqli_fetch_assoc($resultQuiz)): 
          $typeBadge = '';
          $typeClass = '';
          if ($quiz['type'] === 'pilihan ganda') {
            $typeBadge = '📘 Pilgan';
            $typeClass = 'badge-pilgan';
          } else if ($quiz['type'] === 'multi-select') {
            $typeBadge = '📝 Multi';
            $typeClass = 'badge-multi';
          } else if ($quiz['type'] === 'esai') {
            $typeBadge = '✏ Esai';
            $typeClass = 'badge-esai';
          }

          $progress = $quiz['jumlahSiswaKerjakan'] . '/' . $quiz['totalSiswaKelas'];
          $percentage = $quiz['totalSiswaKelas'] > 0 ? round(($quiz['jumlahSiswaKerjakan'] / $quiz['totalSiswaKelas']) * 100) : 0;
          
          $progressClass = '';
          if ($percentage >= 80) $progressClass = 'progress-high';
          else if ($percentage >= 50) $progressClass = 'progress-medium';
          else $progressClass = 'progress-low';
        ?>
        <tr>
          <td style="text-align: center; font-weight: 600;"><?php echo $no++; ?></td>
          <td><?php echo $quiz['waktuMulaiFormat']; ?></td>
          <td><strong><?php echo htmlspecialchars($quiz['judul']); ?></strong></td>
          <td><?php echo htmlspecialchars($quiz['namaMapel']); ?></td>
          <td><span class="badge-kelas"><?php echo $quiz['kelas']; ?></span></td>
          <td><span class="type-badge <?php echo $typeClass; ?>"><?php echo $typeBadge; ?></span></td>
          <td><?php echo $quiz['waktuSelesaiFormat']; ?></td>
          <td>
            <div class="progress-info">
              <span class="progress-text"><?php echo $progress; ?> siswa</span>
              <div class="progress-bar">
                <div class="progress-fill <?php echo $progressClass; ?>" style="width: <?php echo $percentage; ?>%"></div>
              </div>
            </div>
          </td>
          <td>
            <div class="action-buttons">
              <a href="?page=lihatNilaiQuiz&idQuiz=<?php echo $quiz['idQuiz']; ?>" 
                 class="btn-nilai" title="Lihat Nilai">
                <i class="fa-solid fa-chart-bar"></i> Nilai
              </a>
              <a href="?page=buatQuiz&edit=<?php echo $quiz['idQuiz']; ?>" 
                 class="btn-edit" title="Edit Quiz">
                <i class="fa-solid fa-pen-to-square"></i>
              </a>
              <a href="javascript:void(0);" 
                 onclick="hapusQuiz('<?php echo $quiz['idQuiz']; ?>', '<?php echo addslashes($quiz['judul']); ?>')" 
                 class="btn-delete" title="Hapus Quiz">
                <i class="fa-solid fa-trash"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
      <i class="fa-solid fa-inbox"></i>
      <p>Belum ada quiz yang dibuat</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
// ==================== FORM QUIZ SCRIPT ====================
<?php if (!$isEdit): ?>
const waktuMulaiInput = document.getElementById("waktuMulai");
const waktuSelesaiInput = document.getElementById("waktuSelesai");
const quizForm = document.getElementById("quizForm");
const tipeQuizHidden = document.getElementById("tipeQuizHidden");

function setMinDateTime() {
  const now = new Date();
  now.setSeconds(0);
  now.setMilliseconds(0);
  
  const tzOffset = now.getTimezoneOffset() * 60000;
  const localISO = new Date(now - tzOffset).toISOString().slice(0, 16);
  
  waktuMulaiInput.min = localISO;
  waktuSelesaiInput.min = localISO;
}

setMinDateTime();
setInterval(setMinDateTime, 30000);

function pilihTipeQuiz(tipe) {
  if (!quizForm.checkValidity()) {
    quizForm.reportValidity();
    return;
  }

  const mulai = new Date(waktuMulaiInput.value);
  const selesai = new Date(waktuSelesaiInput.value);
  const now = new Date();
  
  now.setSeconds(0);
  now.setMilliseconds(0);
  mulai.setSeconds(0);
  mulai.setMilliseconds(0);

  if (mulai.getTime() < now.getTime()) {
    alert("Waktu mulai tidak boleh di masa lalu!");
    return;
  }

  if (selesai.getTime() <= mulai.getTime()) {
    alert("Waktu selesai harus setelah waktu mulai!");
    return;
  }

  tipeQuizHidden.value = tipe;
  quizForm.submit();
}

waktuMulaiInput.addEventListener("change", () => {
  const mulai = new Date(waktuMulaiInput.value);
  const now = new Date();
  
  now.setSeconds(0);
  now.setMilliseconds(0);
  mulai.setSeconds(0);
  mulai.setMilliseconds(0);

  if (mulai.getTime() < now.getTime()) {
    alert("Waktu mulai tidak boleh di masa lalu!");
    waktuMulaiInput.value = "";
    return;
  }

  waktuSelesaiInput.min = waktuMulaiInput.value;

  if (waktuSelesaiInput.value && new Date(waktuSelesaiInput.value) <= mulai) {
    waktuSelesaiInput.value = "";
  }
});

waktuSelesaiInput.addEventListener("change", () => {
  const mulai = new Date(waktuMulaiInput.value);
  const selesai = new Date(waktuSelesaiInput.value);

  if (!waktuMulaiInput.value) {
    alert("Pilih waktu mulai terlebih dahulu!");
    waktuSelesaiInput.value = "";
    return;
  }

  if (selesai.getTime() <= mulai.getTime()) {
    alert("Waktu selesai harus setelah waktu mulai!");
    waktuSelesaiInput.value = "";
  }
});
<?php endif; ?>

// ==================== FILTER REKAP QUIZ ====================
function applyFilter() {
  const mapel = document.getElementById('filterMapel').value;
  const kelas = document.getElementById('filterKelas').value;
  
  let url = '?page=buatQuiz';
  if (mapel) url += '&mapel=' + encodeURIComponent(mapel);
  if (kelas) url += '&kelas=' + encodeURIComponent(kelas);
  
  window.location.href = url;
}

function resetFilter() {
  window.location.href = '?page=buatQuiz';
}

// ==================== TOMBOL AKSI ====================
function hapusQuiz(idQuiz, judul) {
  if (confirm("Apakah Anda yakin ingin menghapus quiz \"" + judul + "\"?\n\nSemua soal, jawaban, dan nilai siswa akan terhapus!")) {
    window.location.href = '?page=buatQuiz&action=delete&idQuiz=' + encodeURIComponent(idQuiz);
  }
}
</script>