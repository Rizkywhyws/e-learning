<?php
include_once("../config/db.php");
session_start();

// Cek apakah guru sudah login
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'guru') {
  echo "<script>alert('Anda harus login sebagai guru!'); window.location='../Auth/login.php';</script>";
  exit;
}

$nipGuru = $_SESSION['nip']; // Ambil NIP dari session

// Simpan quiz ke database
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $kodeMapel = $_POST['kodeMapel'];
  $judul = $_POST['judul'];
  $deskripsi = $_POST['deskripsi'];
  $waktuMulai = $_POST['waktuMulai'];
  $waktuSelesai = $_POST['waktuSelesai'];
  $tipeQuiz = $_POST['tipeQuiz'] ?? '';
  $kelas = $_POST['kelas'];

  // Validasi tipe quiz
  if (empty($tipeQuiz)) {
    echo "<script>alert('Pilih tipe quiz terlebih dahulu!');</script>";
  } else {
    // Generate ID quiz otomatis
    $idQuiz = "QZ" . rand(1000, 9999);

    $query = "INSERT INTO quiz (idQuiz, kodeMapel, NIP, judul, deskripsi, waktuMulai, waktuSelesai, kelas)
              VALUES ('$idQuiz', '$kodeMapel', '$nipGuru', '$judul', '$deskripsi', '$waktuMulai', '$waktuSelesai', '$kelas')";

    if ($conn->query($query)) {
      echo "<script>
        alert('Quiz berhasil dibuat!');
        window.location='buatSoal.php?idQuiz=$idQuiz&type=$tipeQuiz';
      </script>";
    } else {
      echo "<script>alert('Gagal menyimpan quiz: " . $conn->error . "');</script>";
    }
  }
}
?>

<link rel="stylesheet" href="css/buatQuiz.css?v=<?php echo time(); ?>">

<div class="form-container">
  <h2>Tambah / Buat Quiz</h2>

  <form action="" method="POST" enctype="multipart/form-data" id="quizForm">
    
    <!-- Hidden input untuk tipe quiz -->
    <input type="hidden" id="tipeQuizHidden" name="tipeQuiz" value="">
    
    <!-- Pilih Mapel (hanya yang diampu guru ini) -->
    <label for="kodeMapel">Mata Pelajaran</label>
    <select id="kodeMapel" name="kodeMapel" required>
      <option value="">-- Pilih Mata Pelajaran --</option>
      <?php
        // Ambil mapel yang diampu guru ini dari jadwalmapel
        $queryMapel = "SELECT DISTINCT m.kodeMapel, m.namaMapel 
                       FROM jadwalmapel jm
                       JOIN mapel m ON jm.kodeMapel = m.kodeMapel
                       WHERE jm.nipGuru = '$nipGuru'
                       ORDER BY m.namaMapel ASC";
        $mapel = $conn->query($queryMapel);
        
        if ($mapel && $mapel->num_rows > 0) {
          while ($row = $mapel->fetch_assoc()) {
            echo "<option value='{$row['kodeMapel']}'>{$row['namaMapel']}</option>";
          }
        } else {
          echo "<option value='' disabled>Tidak ada mata pelajaran</option>";
        }
      ?>
    </select>

    <!-- Pilih Kelas (hanya yang diajar guru ini) -->
    <label for="kelas">Kelas</label>
    <select id="kelas" name="kelas" required>
      <option value="">-- Pilih Kelas --</option>
      <?php
        // Ambil kelas yang diajar guru ini dari jadwalmapel
        $queryKelas = "SELECT DISTINCT kelas 
                       FROM jadwalmapel 
                       WHERE nipGuru = '$nipGuru'
                       ORDER BY kelas ASC";
        $kelasResult = $conn->query($queryKelas);
        
        if ($kelasResult && $kelasResult->num_rows > 0) {
          while ($row = $kelasResult->fetch_assoc()) {
            echo "<option value='{$row['kelas']}'>{$row['kelas']}</option>";
          }
        } else {
          echo "<option value='' disabled>Tidak ada kelas</option>";
        }
      ?>
    </select>

    <!-- Judul Quiz -->
    <label for="judul">Judul Quiz</label>
    <input type="text" id="judul" name="judul" placeholder="Masukkan judul quiz..." required>

    <!-- Deskripsi -->
    <label for="deskripsi">Deskripsi Quiz</label>
    <textarea id="deskripsi" name="deskripsi" placeholder="Tuliskan deskripsi quiz..." required></textarea>

    <!-- Waktu Mulai -->
    <label for="waktuMulai">Waktu Mulai</label>
    <input type="datetime-local" id="waktuMulai" name="waktuMulai" required>

    <!-- Waktu Selesai -->
    <label for="waktuSelesai">Waktu Selesai</label>
    <input type="datetime-local" id="waktuSelesai" name="waktuSelesai" required>

    <!-- Pilih Jenis Quiz -->
    <div class="upload-box">
      <label>Pilih Jenis Quiz</label><br>
      <div class="type-options">
        <button type="button" onclick="pilihTipeQuiz('pilihan ganda')" class="save-btn">📘 Pilihan Ganda</button>
        <button type="button" onclick="pilihTipeQuiz('multi-select')" class="save-btn">📝 Multiselect</button>
        <button type="button" onclick="pilihTipeQuiz('esai')" class="save-btn">✏ Esai</button>
      </div>
    </div>
  </form>
</div>

<script>
  // Atur waktu minimal datetime-local
  const waktuMulaiInput = document.getElementById("waktuMulai");
  const waktuSelesaiInput = document.getElementById("waktuSelesai");
  const quizForm = document.getElementById("quizForm");
  const tipeQuizHidden = document.getElementById("tipeQuizHidden");

  function setMinDateTime() {
    const now = new Date();
    const tzOffset = now.getTimezoneOffset() * 60000;
    const localISOTime = new Date(now - tzOffset).toISOString().slice(0, 16);
    waktuMulaiInput.min = localISOTime;
    waktuSelesaiInput.min = localISOTime;
  }

  setMinDateTime();

  // Fungsi saat button tipe quiz diklik
  function pilihTipeQuiz(tipe) {
    // Validasi form dulu
    if (!quizForm.checkValidity()) {
      quizForm.reportValidity();
      return;
    }

    const mulai = new Date(waktuMulaiInput.value);
    const selesai = new Date(waktuSelesaiInput.value);
    const now = new Date();
    
    if (mulai.getTime() < now.getTime()) {
      alert("Waktu mulai tidak boleh di masa lalu!");
      return;
    }
    
    if (!waktuSelesaiInput.value) {
      alert("Waktu selesai harus diisi!");
      return;
    }
    
    if (selesai.getTime() <= mulai.getTime()) {
      alert("Waktu selesai harus setelah waktu mulai!");
      return;
    }

    // Set tipe quiz dan submit form
    tipeQuizHidden.value = tipe;
    quizForm.submit();
  }

  // Validasi waktu mulai tidak boleh di masa lalu
  waktuMulaiInput.addEventListener('change', function() {
    const mulai = new Date(waktuMulaiInput.value);
    const now = new Date();
    
    if (mulai.getTime() < now.getTime()) {
      alert("Waktu mulai tidak boleh di masa lalu!");
      waktuMulaiInput.value = "";
      return;
    }
    
    // Set waktu selesai minimal = waktu mulai
    waktuSelesaiInput.min = waktuMulaiInput.value;
    
    // Reset waktu selesai jika lebih kecil dari waktu mulai
    if (waktuSelesaiInput.value && new Date(waktuSelesaiInput.value) <= mulai) {
      waktuSelesaiInput.value = "";
    }
  });

  // Validasi waktu selesai harus setelah waktu mulai
  waktuSelesaiInput.addEventListener('change', function() {
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
      return;
    }
  });
</script>