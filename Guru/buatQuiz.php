<?php
include_once("../config/db.php");


// Cek apakah guru sudah login
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'guru') {
  echo "<script>alert('Anda harus login sebagai guru!'); window.location='../Auth/login.php';</script>";
  exit;
}

$nipGuru = $_SESSION['nip']; // Ambil NIP guru dari session

// ========================
// PROSES SIMPAN QUIZ
// ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $kodeMapel = $_POST['kodeMapel'];
  $judul = $_POST['judul'];
  $deskripsi = $_POST['deskripsi'];
  $waktuMulai = $_POST['waktuMulai'];
  $waktuSelesai = $_POST['waktuSelesai'];
  $tipeQuiz = $_POST['tipeQuiz'];
  $kelas = $_POST['kelas'];

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
?>

<link rel="stylesheet" href="css/buatQuiz.css?v=<?php echo time(); ?>">

<div class="form-container">
  <h2>Tambah / Buat Quiz</h2>

  <form action="" method="POST" enctype="multipart/form-data" id="quizForm">
    
    <input type="hidden" id="tipeQuizHidden" name="tipeQuiz">

    <!-- Pilih Mapel -->
    <label for="kodeMapel">Mata Pelajaran</label>
    <select id="kodeMapel" name="kodeMapel" required>
      <option value="">-- Pilih Mata Pelajaran --</option>
      <?php
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

    <!-- Pilih Kelas -->
    <label for="kelas">Kelas</label>
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
            echo "<option value='{$row['kelas']}'>{$row['kelas']}</option>";
          }
        } else {
          echo "<option value='' disabled>Tidak ada kelas</option>";
        }
      ?>
    </select>

    <!-- Judul -->
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
        <button type="button" onclick="pilihTipeQuiz('esai')" class="save-btn">✏️ Esai</button>
      </div>
    </div>

  </form>
</div>

<script>
  const waktuMulaiInput = document.getElementById("waktuMulai");
  const waktuSelesaiInput = document.getElementById("waktuSelesai");
  const quizForm = document.getElementById("quizForm");
  const tipeQuizHidden = document.getElementById("tipeQuizHidden");

  // Set minimal waktu = sekarang (tanpa detik, bulatkan ke menit sebelumnya)
  function setMinDateTime() {
    const now = new Date();
    // Bulatkan ke menit sebelumnya agar menit sekarang bisa dipilih
    now.setSeconds(0);
    now.setMilliseconds(0);
    
    const tzOffset = now.getTimezoneOffset() * 60000;
    const localISO = new Date(now - tzOffset).toISOString().slice(0, 16);
    
    waktuMulaiInput.min = localISO;
    waktuSelesaiInput.min = localISO;
  }

  setMinDateTime();
  // Update setiap 30 detik
  setInterval(setMinDateTime, 30000);

  function pilihTipeQuiz(tipe) {
    if (!quizForm.checkValidity()) {
      quizForm.reportValidity();
      return;
    }

    const mulai = new Date(waktuMulaiInput.value);
    const selesai = new Date(waktuSelesaiInput.value);
    const now = new Date();
    
    // Set detik ke 0 untuk perbandingan yang adil (karena input tidak punya detik)
    now.setSeconds(0);
    now.setMilliseconds(0);
    mulai.setSeconds(0);
    mulai.setMilliseconds(0);

    // Waktu mulai boleh = waktu sekarang (menit ini)
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
</script>