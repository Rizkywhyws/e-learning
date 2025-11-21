<?php
include_once("../config/db.php");

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
  $tipeQuiz = $_POST['tipeQuiz'];
  $kelas = $_POST['kelas'];

  // Generate ID quiz otomatis
  $idQuiz = "QZ" . rand(1000, 9999);

  $query = "INSERT INTO quiz (idQuiz, kodeMapel, NIP, judul, deskripsi, waktuMulai, kelas)
            VALUES ('$idQuiz', '$kodeMapel', '$nipGuru', '$judul', '$deskripsi', '$waktuMulai', '$kelas')";

  if ($conn->query($query)) {
    echo "<script>
      alert('Quiz berhasil dibuat!');
      window.location='buatSoal.php?idQuiz=$idQuiz&type=$tipeQuiz';
    </script>";
  } else {
    echo "<script>alert('Gagal menyimpan quiz: " . $conn->error . "');</script>";
  }
}
?>

<link rel="stylesheet" href="css/buatQuiz.css?v=<?php echo time(); ?>">

<div class="form-container">
  <h2>Tambah / Buat Quiz</h2>

  <form action="" method="POST" enctype="multipart/form-data">
    
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

    <!-- Pilih Jenis Quiz -->
    <div class="upload-box">
      <label>Pilih Jenis Quiz</label><br>
      <div class="type-options">
        <button type="submit" name="tipeQuiz" value="pilgan" class="save-btn">📘 Pilihan Ganda</button>
        <button type="submit" name="tipeQuiz" value="multiselect" class="save-btn">📝 Multiselect</button>
        <button type="submit" name="tipeQuiz" value="esai" class="save-btn">✏ Esai</button>
      </div>
    </div>
  </form>
</div>

<script>
  // Atur waktu minimal datetime-local
  const waktuMulaiInput = document.getElementById("waktuMulai");

  function setMinDateTime() {
    const now = new Date();
    const tzOffset = now.getTimezoneOffset() * 60000;
    const localISOTime = new Date(now - tzOffset).toISOString().slice(0, 16);
    waktuMulaiInput.min = localISOTime;
  }

  setMinDateTime();

  document.querySelector("form").addEventListener("submit", function (e) {
    const selected = new Date(waktuMulaiInput.value);
    const now = new Date();
    if (selected.getTime() < now.getTime()) {
      e.preventDefault();
      alert("Tanggal dan waktu tidak boleh di masa lalu!");
    }
  });
</script>