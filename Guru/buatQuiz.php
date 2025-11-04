<?php
include_once("../config/db.php");
session_start();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buat Quiz</title>
  <link rel="stylesheet" href="css/buatQuiz.css">
</head>
<body>

<div class="container">
  <h2>Buat Quiz</h2>

  <div class="form-group">
    <!-- Label tetap -->
    <label for="mapel">Pilih Mata Pelajaran</label>
    <select id="mapel" name="mapel" required>
      <?php
        $query = "SELECT kodeMapel, namaMapel FROM mapel";
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
          echo "<option value='' selected disabled>-- Pilih Mata Pelajaran --</option>";
          while ($row = $result->fetch_assoc()) {
            echo "<option value='{$row['kodeMapel']}'>{$row['namaMapel']}</option>";
          }
        } else {
          echo "<option disabled>Tidak ada data mapel</option>";
        }
      ?>
    </select>

    <label for="kelas">Pilih Kelas</label>
    <select id="kelas" name="kelas" required>
      <option value="">-- Pilih Kelas --</option>
      <?php
        // ambil daftar kelas unik dari tabel jadwalmapel
        $queryKelas = "SELECT DISTINCT kelas FROM jadwalmapel ORDER BY kelas ASC";
        $resultKelas = $conn->query($queryKelas);
        if ($resultKelas && $resultKelas->num_rows > 0) {
          while ($row = $resultKelas->fetch_assoc()) {
            echo "<option value='{$row['kelas']}'>{$row['kelas']}</option>";
          }
        } else {
          echo "<option disabled>Tidak ada data kelas</option>";
        }
      ?>
    </select>
  </div>

  <div class="quiz-type">
    <button onclick="window.location.href='buatSoal.php?type=pilgan'">Pilihan Ganda</button>
    <button onclick="window.location.href='buatSoal.php?type=multi'">Multi Select</button>
    <button onclick="window.location.href='buatSoal.php?type=esai'">Esai</button>
  </div>
</div>

</body>
</html>