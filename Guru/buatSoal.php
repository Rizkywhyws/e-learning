<?php
include_once("../config/db.php");
session_start();

// Ambil parameter tipe quiz
$type = isset($_GET['type']) ? $_GET['type'] : 'pilgan';
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buat Soal - <?= ucfirst($type) ?></title>
  <link rel="stylesheet" href="css/buatSoal.css">
</head>
<body>

<div class="container">
  <h2>Buat Soal <?= ucfirst($type) ?></h2>

    <label for="pertanyaan">Pertanyaan</label>
    <textarea name="pertanyaan" id="pertanyaan" rows="4" placeholder="Tulis pertanyaan di sini..." required></textarea>

    <?php if ($type === "esai") { ?>
      <!-- Soal Esai -->
      <div class="esai-info">
        <p>Soal esai tidak membutuhkan pilihan jawaban. Siswa akan menulis jawaban secara manual.</p>
      </div>

    <?php } else { ?>
      <!-- Soal Pilihan Ganda / Multi Select -->
      <div class="jawaban-container">
        <?php
        for ($i = 1; $i <= 4; $i++) {
          echo "
          <div class='jawaban-item'>
            <div class='check-box' onclick='toggleCheck(this, \"$type\")'></div>
            <input type='text' name='jawaban[]' placeholder='Jawaban $i' required>
          </div>
          ";
        }
        ?>
      </div>

      <input type="hidden" id="jawabanBenar" name="jawabanBenar">
    <?php } ?>

    <input type="hidden" name="type" value="<?= $type ?>">
    <button type="submit" class="btn-simpan">Simpan Soal</button>
  </form>
</div>

<script>
  // Menyimpan pilihan jawaban benar (bebas untuk multi select)
  const selectedAnswers = [];

  function toggleCheck(el, type) {
    const index = Array.from(document.querySelectorAll('.check-box')).indexOf(el);

    if (type === "multi") {
      // bisa pilih lebih dari 2 jawaban
      if (selectedAnswers.includes(index)) {
        // jika sudah dipilih, batalkan
        selectedAnswers.splice(selectedAnswers.indexOf(index), 1);
        el.classList.remove('checked');
      } else {
        // tambahkan ke daftar jawaban benar
        selectedAnswers.push(index);
        el.classList.add('checked');
      }
    } else {
      // untuk pilihan ganda (hanya 1 jawaban)
      document.querySelectorAll('.check-box').forEach(cb => cb.classList.remove('checked'));
      selectedAnswers.splice(0, selectedAnswers.length, index);
      el.classList.add('checked');
    }

    // simpan index jawaban benar ke hidden input
    document.getElementById('jawabanBenar').value = selectedAnswers.join(',');
  }
</script>

</body>
</html>