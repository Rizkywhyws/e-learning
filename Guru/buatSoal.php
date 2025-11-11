<?php
include_once("../config/db.php");
session_start();

// Ambil parameter dari halaman sebelumnya (idQuiz dan type)
$idQuiz = isset($_GET['idQuiz']) ? $_GET['idQuiz'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'pilgan';

// Kalau tombol Simpan ke Database ditekan
if (isset($_POST['soal_list'])) {
    $dataSoal = json_decode($_POST['soal_list'], true);
    $idQuiz = $_POST['id_quiz'];

    if ($dataSoal && is_array($dataSoal)) {
        foreach ($dataSoal as $soal) {
            // ID soal otomatis
            $getLast = mysqli_query($conn, "SELECT idsoal FROM soalquiz ORDER BY idsoal DESC LIMIT 1");
            $lastId = mysqli_fetch_assoc($getLast);
            if ($lastId) {
                $num = (int)substr($lastId['idsoal'], 1) + 1;
                $idSoal = 'S' . str_pad($num, 4, '0', STR_PAD_LEFT);
            } else {
                $idSoal = 'S0001';
            }

            $pertanyaan = mysqli_real_escape_string($conn, $soal['pertanyaan']);
            $type = mysqli_real_escape_string($conn, $soal['type']);

            // Ambil opsi jawaban
            $opsi_a = isset($soal['jawaban'][0]) ? mysqli_real_escape_string($conn, $soal['jawaban'][0]) : '';
            $opsi_b = isset($soal['jawaban'][1]) ? mysqli_real_escape_string($conn, $soal['jawaban'][1]) : '';
            $opsi_c = isset($soal['jawaban'][2]) ? mysqli_real_escape_string($conn, $soal['jawaban'][2]) : '';
            $opsi_d = isset($soal['jawaban'][3]) ? mysqli_real_escape_string($conn, $soal['jawaban'][3]) : '';
            $opsi_e = isset($soal['jawaban'][4]) ? mysqli_real_escape_string($conn, $soal['jawaban'][4]) : '';

            // Jawaban benar
            $jawabanPilgan = ($type == 'pilgan') ? mysqli_real_escape_string($conn, $soal['jawabanBenar']) : '';
            $jawabanMulti = ($type == 'multiselect') ? mysqli_real_escape_string($conn, $soal['jawabanBenar']) : '';

            $query = "INSERT INTO soalquiz 
            (idsoal, idquiz, pertanyaan, type, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, jawabanPilgan, jawabanMulti)
            VALUES 
            ('$idSoal', '$idQuiz', '$pertanyaan', '$type', '$opsi_a', '$opsi_b', '$opsi_c', '$opsi_d', '$opsi_e', '$jawabanPilgan', '$jawabanMulti')";
            
            mysqli_query($conn, $query);
        }
        echo "<script>alert('✅ Semua soal berhasil disimpan ke database!');</script>";
    } else {
        echo "<script>alert('⚠ Tidak ada soal yang disimpan.');</script>";
    }
}
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

<div class="main-container">
  <!-- BAGIAN KIRI -->
  <div class="left-side">
    <h2>Buat Soal <?= ucfirst($type) ?></h2>

    <label>Pertanyaan</label>
    <textarea id="pertanyaan" rows="4" placeholder="Tulis pertanyaan di sini..."></textarea>

    <div class="jawaban-container" id="jawaban-container">
      <?php if ($type != 'esai'): ?>
        <?php for ($i = 1; $i <= 5; $i++) { ?>
          <div class="jawaban-item">
            <div class="check-box" onclick="toggleCheck(this)"></div>
            <input type="text" placeholder="Opsi <?= chr(64 + $i) ?>">
          </div>
        <?php } ?>
      <?php else: ?>
        <p style="color:#555;">(Tipe esai hanya memerlukan pertanyaan tanpa opsi jawaban)</p>
      <?php endif; ?>
    </div>

    <div class="button-group">
      <button onclick="tambahSoal()">Tambah Soal</button>
      <button onclick="resetForm()">Reset</button>
    </div>
  </div>

  <!-- BAGIAN KANAN -->
  <div class="right-side">
    <h2>Daftar Soal</h2>
    <div id="list-soal"></div>
    <button class="btn-simpan" onclick="simpanSemua()">Simpan ke Database</button>
  </div>
</div>

<script>
let daftarSoal = [];
let selectedAnswers = [];
let editIndex = -1;
const quizType = "<?= $type ?>";

// ✅ Fungsi checkbox
function toggleCheck(el) {
  const all = Array.from(document.querySelectorAll('.check-box'));
  const index = all.indexOf(el);

  if (quizType === 'pilgan') {
    all.forEach(cb => cb.classList.remove('checked'));
    el.classList.add('checked');
    selectedAnswers = [index];
  } else if (quizType === 'multiselect') {
    el.classList.toggle('checked');
    if (el.classList.contains('checked')) {
      if (!selectedAnswers.includes(index)) selectedAnswers.push(index);
    } else {
      selectedAnswers = selectedAnswers.filter(i => i !== index);
    }
  }
}

// ✅ Tambah soal
function tambahSoal() {
  const pertanyaan = document.getElementById('pertanyaan').value.trim();
  const inputs = Array.from(document.querySelectorAll('.jawaban-item input'));
  const jawaban = inputs.map(i => i.value.trim());
  const jawabanBenar = selectedAnswers.join(',');

  if (!pertanyaan) return alert('Tulis pertanyaannya dulu!');
  if (quizType !== 'esai') {
    if (jawaban.some(j => !j)) return alert('Lengkapi semua opsi jawaban!');
    if (selectedAnswers.length === 0) return alert('Pilih minimal satu jawaban benar!');
  }

  const soalData = { pertanyaan, jawaban, jawabanBenar, type: quizType };
  if (editIndex >= 0) daftarSoal[editIndex] = soalData; else daftarSoal.push(soalData);
  editIndex = -1;
  renderSoal();
  resetForm();
}

// ✅ Render daftar soal
function renderSoal() {
  const container = document.getElementById('list-soal');
  container.innerHTML = '';
  daftarSoal.forEach((soal, i) => {
    let listJawaban = '';
    if (soal.type !== 'esai') {
      const benarArr = soal.jawabanBenar.split(',').map(n => parseInt(n));
      listJawaban = `
        <ul>
          ${soal.jawaban.map((j, idx) => `
            <li>${String.fromCharCode(65 + idx)}. ${j} ${benarArr.includes(idx) ? '✅' : ''}</li>
          `).join('')}
        </ul>`;
    }
    const div = document.createElement('div');
    div.className = 'soal-item';
    div.innerHTML = `
      <p><b>${i + 1}.</b> ${soal.pertanyaan}</p>
      ${listJawaban}
      <button onclick="editSoal(${i})">Edit</button>
      <button onclick="hapusSoal(${i})" class="hapus">Hapus</button>`;
    container.appendChild(div);
  });
}

// ✅ Edit soal
function editSoal(index) {
  const soal = daftarSoal[index];
  document.getElementById('pertanyaan').value = soal.pertanyaan;
  const inputs = document.querySelectorAll('.jawaban-item input');
  soal.jawaban.forEach((j, i) => inputs[i].value = j);
  selectedAnswers = soal.jawabanBenar.split(',').map(n => parseInt(n));
  document.querySelectorAll('.check-box').forEach((cb, i) => {
    cb.classList.toggle('checked', selectedAnswers.includes(i));
  });
  editIndex = index;
}

// ✅ Hapus soal
function hapusSoal(index) {
  daftarSoal.splice(index, 1);
  renderSoal();
}

// ✅ Reset form
function resetForm() {
  document.getElementById('pertanyaan').value = '';
  document.querySelectorAll('.jawaban-item input').forEach(i => i.value = '');
  document.querySelectorAll('.check-box').forEach(cb => cb.classList.remove('checked'));
  selectedAnswers = [];
  editIndex = -1;
}

// ✅ Simpan ke database
function simpanSemua() {
  if (daftarSoal.length === 0) return alert('Belum ada soal yang ditambahkan!');
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '';
  form.innerHTML = `<input type="hidden" name="soal_list" value='${JSON.stringify(daftarSoal)}'>
                    <input type="hidden" name="id_quiz" value="<?= $idQuiz ?>">`;
  document.body.appendChild(form);
  form.submit();
}
</script>

</body>
</html>