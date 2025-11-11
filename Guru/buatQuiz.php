<?php
include_once("../config/db.php");
session_start();

// Simpan quiz ke database
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $kodeMapel = $_POST['kodeMapel'];
  $NIP = $_POST['NIP'];
  $judul = $_POST['judul'];
  $deskripsi = $_POST['deskripsi'];
  $waktuMulai = $_POST['waktuMulai'];
  $tipeQuiz = $_POST['tipeQuiz'];

  // Generate ID quiz otomatis
  $idQuiz = "QZ" . rand(1000, 9999);

  $query = "INSERT INTO quiz (idQuiz, kodeMapel, NIP, judul, deskripsi, waktuMulai) 
            VALUES ('$idQuiz', '$kodeMapel', '$NIP', '$judul', '$deskripsi', '$waktuMulai')";

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

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buat Quiz</title>
  <link rel="stylesheet" href="cssGuru/buatQuiz.css">
</head>
<body>

<div class="container">
  <h2>Buat Quiz Baru</h2>

  <form action="" method="POST">
    <label for="kodeMapel">Pilih Mata Pelajaran</label>
    <select id="kodeMapel" name="kodeMapel" required>
      <option value="">-- Pilih Mata Pelajaran --</option>
      <?php
        $mapel = $conn->query("SELECT kodeMapel, namaMapel FROM mapel");
        while ($row = $mapel->fetch_assoc()) {
          echo "<option value='{$row['kodeMapel']}'>{$row['namaMapel']}</option>";
        }
      ?>
    </select>

    <label for="NIP">Pilih Guru</label>
    <select id="NIP" name="NIP" required>
      <option value="">-- Pilih Guru --</option>
      <?php
        $guru = $conn->query("SELECT NIP, nama FROM dataguru");
        while ($row = $guru->fetch_assoc()) {
          echo "<option value='{$row['NIP']}'>{$row['nama']}</option>";
        }
      ?>
    </select>

    <label for="judul">Judul Quiz</label>
    <input type="text" id="judul" name="judul" placeholder="Masukkan judul quiz..." required>

    <label for="deskripsi">Deskripsi Quiz</label>
    <textarea id="deskripsi" name="deskripsi" placeholder="Masukkan deskripsi quiz..." rows="4" required></textarea>

    <label for="waktuMulai">Waktu Mulai</label>
    <input type="datetime-local" id="waktuMulai" name="waktuMulai" required>

    <div class="quiz-type">
      <h3>Pilih Jenis Quiz</h3>
      <div class="type-options">
        <button type="submit" name="tipeQuiz" value="pilgan" class="type-btn">Pilihan Ganda</button>
        <button type="submit" name="tipeQuiz" value="multiselect" class="type-btn">Multiselect</button>
        <button type="submit" name="tipeQuiz" value="esai" class="type-btn">Esai</button>
      </div>
    </div>
  </form>
</div>

</body>
</html>