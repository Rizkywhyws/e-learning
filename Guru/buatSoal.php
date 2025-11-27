<?php
include_once("../config/db.php");
session_start();

$idQuiz = isset($_GET['idQuiz']) ? $_GET['idQuiz'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'pilihan ganda';

// Mapping untuk display
$typeDisplay = [
  'pilihan ganda' => 'Pilihan Ganda',
  'multi-select' => 'Multi-Select',
  'esai' => 'Esai'
];

// Fungsi ambil semua soal
function getSoal($conn, $idQuiz) {
  $result = mysqli_query($conn, "SELECT * FROM soalquiz WHERE idQuiz='$idQuiz' ORDER BY idSoal ASC");
  $data = [];
  while ($row = mysqli_fetch_assoc($result)) $data[] = $row;
  return $data;
}

// Tambah soal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'tambah') {
  $pertanyaan = mysqli_real_escape_string($conn, $_POST['pertanyaan']);
  $jawaban = json_decode($_POST['jawaban'], true);
  $jawabanBenar = mysqli_real_escape_string($conn, $_POST['jawabanBenar']);
  $typeSoal = mysqli_real_escape_string($conn, $_POST['type']);
  $idQuiz = mysqli_real_escape_string($conn, $_POST['id_quiz']);

  $getLast = mysqli_query($conn, "SELECT idSoal FROM soalquiz ORDER BY idSoal DESC LIMIT 1");
  $lastId = mysqli_fetch_assoc($getLast);
  $idSoal = $lastId ? 'S' . str_pad(((int)substr($lastId['idSoal'], 1)) + 1, 4, '0', STR_PAD_LEFT) : 'S0001';

  $opsi_a = $jawaban[0] ?? '';
  $opsi_b = $jawaban[1] ?? '';
  $opsi_c = $jawaban[2] ?? '';
  $opsi_d = $jawaban[3] ?? '';
  $opsi_e = $jawaban[4] ?? '';

  $jawabanPilgan = ($typeSoal == 'pilihan ganda') ? chr(65 + (int)$jawabanBenar) : '';
  $jawabanMulti  = ($typeSoal == 'multi-select') ? $jawabanBenar : '';

  mysqli_query($conn, "INSERT INTO soalquiz 
    (idSoal, idQuiz, pertanyaan, type, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, jawabanPilgan, jawabanMulti)
    VALUES ('$idSoal', '$idQuiz', '$pertanyaan', '$typeSoal', '$opsi_a', '$opsi_b', '$opsi_c', '$opsi_d', '$opsi_e',
    '$jawabanPilgan', '$jawabanMulti')");

  echo json_encode(["success" => true, "message" => "Soal berhasil ditambahkan!"]);
  exit;
}

// Update soal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update') {
  $idSoal = mysqli_real_escape_string($conn, $_POST['idsoal']);
  $pertanyaan = mysqli_real_escape_string($conn, $_POST['pertanyaan']);
  $jawaban = json_decode($_POST['jawaban'], true);
  $jawabanBenar = mysqli_real_escape_string($conn, $_POST['jawabanBenar']);
  $typeSoal = mysqli_real_escape_string($conn, $_POST['type']);

  $opsi_a = $jawaban[0] ?? '';
  $opsi_b = $jawaban[1] ?? '';
  $opsi_c = $jawaban[2] ?? '';
  $opsi_d = $jawaban[3] ?? '';
  $opsi_e = $jawaban[4] ?? '';

  $jawabanPilgan = ($typeSoal == 'pilihan ganda')
      ? chr(65 + (int)$jawabanBenar)
      : '';

  $jawabanMulti  = ($typeSoal == 'multi-select') ? $jawabanBenar : '';

  mysqli_query($conn, "UPDATE soalquiz 
    SET pertanyaan='$pertanyaan', type='$typeSoal',
        opsi_a='$opsi_a', opsi_b='$opsi_b', opsi_c='$opsi_c',
        opsi_d='$opsi_d', opsi_e='$opsi_e',
        jawabanPilgan='$jawabanPilgan', jawabanMulti='$jawabanMulti'
    WHERE idSoal='$idSoal'");

  echo json_encode(["success" => true, "message" => "Soal berhasil diperbarui!"]);
  exit;
}

// Hapus soal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'hapus') {
  $idSoal = mysqli_real_escape_string($conn, $_POST['idsoal']);
  mysqli_query($conn, "DELETE FROM soalquiz WHERE idSoal='$idSoal'");
  echo json_encode(["success" => true]);
  exit;
}

// Ambil semua soal
$daftarSoal = getSoal($conn, $idQuiz);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Buat Soal - <?= $typeDisplay[$type] ?? ucfirst($type) ?></title>
<link rel="stylesheet" href="css/buatSoal.css">
<style>
.soal-item {background:#f8f8f8; padding:10px; margin-bottom:10px; border-radius:10px;}
.soal-item ul {margin-left:20px;}
button.hapus {background:red; color:white; border:none; padding:4px 10px; border-radius:5px; cursor:pointer;}
button.edit {background:orange; color:white; border:none; padding:4px 10px; border-radius:5px; cursor:pointer; margin-right:5px;}
button:hover {opacity:0.85;}

.right-side { position: relative; }

.btn-selesai-container {
  text-align: center;
  margin-top: 30px;
  padding: 20px 0;
}

.btn-selesai {
  padding: 14px 40px;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
  transition: all 0.3s;
  background: linear-gradient(135deg, #4CAF50, #45a049);
  color: white;
}

.btn-selesai:hover {
  background: linear-gradient(135deg, #45a049, #3d8b40);
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(76, 175, 80, 0.4);
}
</style>
</head>
<body>
<div class="main-container">

  <div class="left-side">
    <h2 id="form-title">Buat Soal <?= $typeDisplay[$type] ?? ucfirst($type) ?></h2>

    <input type="hidden" id="idsoal">

    <label>Pertanyaan</label>
    <textarea id="pertanyaan" rows="4" placeholder="Tulis pertanyaan di sini..."></textarea>

    <div class="jawaban-container" id="jawaban-container">
      <?php if ($type != 'esai'): ?>
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <div class="jawaban-item">
            <div class="check-box" onclick="toggleCheck(this)"></div>
            <input type="text" placeholder="Opsi <?= chr(64 + $i) ?>">
          </div>
        <?php endfor; ?>
      <?php else: ?>
        <p style="color:#555;">(Tipe esai hanya memerlukan pertanyaan tanpa opsi jawaban)</p>
      <?php endif; ?>
    </div>

    <div class="button-group">
      <button id="btnAction" onclick="tambahSoal()">Tambah Soal</button>
      <button onclick="resetForm()">Reset</button>
    </div>
  </div>


  <div class="right-side">
    <h2>Daftar Soal</h2>
    <div id="list-soal">

      <?php if (empty($daftarSoal)): ?>
        <p style="color:#999; text-align:center; padding:20px;">Belum ada soal. Silakan tambahkan soal baru.</p>

      <?php else: ?>
        <?php foreach ($daftarSoal as $i => $soal): ?>
          <div class="soal-item" data-id="<?= $soal['idSoal'] ?>" data-index="<?= $i+1 ?>">
            <p><b><?= $i+1 ?>.</b> <?= htmlspecialchars($soal['pertanyaan']) ?> (<?= htmlspecialchars($soal['type']) ?>)</p>

            <?php if ($soal['type'] != 'esai'): ?>
              <ul>
                <?php foreach (['opsi_a','opsi_b','opsi_c','opsi_d','opsi_e'] as $key):
                  if (!empty($soal[$key])): ?>
                    <li><?= strtoupper(substr($key, -1)) ?>. <?= htmlspecialchars($soal[$key]) ?></li>
                <?php endif; endforeach; ?>
              </ul>
            <?php endif; ?>

            <button class="edit" onclick='editSoal(<?= json_encode($soal) ?>)'>Edit</button>
            <button class="hapus" onclick="hapusSoal('<?= $soal['idSoal'] ?>', this)">Hapus</button>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

    </div>

    <div class="btn-selesai-container">
      <button class="btn-selesai" onclick="selesai()">✅ Selesai</button>
    </div>
  </div>

</div>

<script>
let selectedAnswers = [];
const quizType = "<?= $type ?>";
const idQuiz = "<?= $idQuiz ?>";
let isEditMode = false;

// Toggle jawaban benar
function toggleCheck(el) {
  const all = [...document.querySelectorAll('.check-box')];
  const index = all.indexOf(el);

  if (quizType === 'pilihan ganda') {
    all.forEach(cb => cb.classList.remove('checked'));
    el.classList.add('checked');
    selectedAnswers = [index];
  } 
  else if (quizType === 'multi-select') {
    el.classList.toggle('checked');
    if (el.classList.contains('checked')) selectedAnswers.push(index);
    else selectedAnswers = selectedAnswers.filter(i => i !== index);
  }
}

// Tambah atau update soal
function tambahSoal() {
  const pertanyaan = document.getElementById('pertanyaan').value.trim();
  const inputs = [...document.querySelectorAll('.jawaban-item input')];
  const jawaban = inputs.map(i => i.value.trim());
  const jawabanBenar = selectedAnswers.join(',');
  const idSoal = document.getElementById('idsoal').value;

  if (!pertanyaan) return alert('Tulis pertanyaan dulu!');

  if (quizType !== 'esai') {
    if (jawaban.some(j => !j)) return alert('Lengkapi semua opsi jawaban!');
    if (selectedAnswers.length === 0) return alert('Pilih minimal satu jawaban benar!');
  }

  const data = new FormData();
  data.append('pertanyaan', pertanyaan);
  data.append('jawaban', JSON.stringify(jawaban));
  data.append('jawabanBenar', jawabanBenar);
  data.append('type', quizType);
  data.append('id_quiz', idQuiz);
  data.append('action', isEditMode ? 'update' : 'tambah');

  if (isEditMode) data.append('idsoal', idSoal);

  fetch('', { method: 'POST', body: data })
    .then(res => res.json())
    .then(res => {
      alert(res.message);
      location.reload();
    })
    .catch(err => console.error(err));
}

// Edit soal
function editSoal(soal) {
  isEditMode = true;
  document.getElementById('form-title').textContent = 'Edit Soal';
  document.getElementById('btnAction').textContent = 'Update Soal';
  document.getElementById('idsoal').value = soal.idSoal;
  document.getElementById('pertanyaan').value = soal.pertanyaan;

  const inputs = document.querySelectorAll('.jawaban-item input');
  const checks = document.querySelectorAll('.check-box');

  let jawabanBenar = soal.type === 'pilihan ganda' 
    ? soal.jawabanPilgan 
    : soal.jawabanMulti;

  // Pilihan ganda (A/B/C → index)
  if (soal.type === 'pilihan ganda' && jawabanBenar) {
    selectedAnswers = [jawabanBenar.charCodeAt(0) - 65];
  } 
  else {
    selectedAnswers = jawabanBenar ? jawabanBenar.split(',').map(n => parseInt(n)) : [];
  }

  ['opsi_a','opsi_b','opsi_c','opsi_d','opsi_e'].forEach((k, i) => {
    if (inputs[i]) {
      inputs[i].value = soal[k] ?? '';
      if (selectedAnswers.includes(i)) checks[i].classList.add('checked');
      else checks[i].classList.remove('checked');
    }
  });
}

// Hapus soal
function hapusSoal(idSoal, btn) {
  if (!confirm('Hapus soal ini?')) return;

  const data = new FormData();
  data.append('action', 'hapus');
  data.append('idsoal', idSoal);

  fetch('', { method: 'POST', body: data })
    .then(r => r.json())
    .then(r => {
      if (r.success) {
        alert('Soal berhasil dihapus!');
        btn.closest('.soal-item').remove();
      }
    });
}

// Reset form
function resetForm() {
  document.getElementById('pertanyaan').value = '';
  document.querySelectorAll('.jawaban-item input').forEach(i => i.value = '');
  document.querySelectorAll('.check-box').forEach(cb => cb.classList.remove('checked'));
  selectedAnswers = [];
  isEditMode = false;

  document.getElementById('form-title').textContent = 'Buat Soal <?= $typeDisplay[$type] ?? ucfirst($type) ?>';
  document.getElementById('btnAction').textContent = 'Tambah Soal';
  document.getElementById('idsoal').value = '';
}

// Tombol Selesai
function selesai() {
  window.location.href = 'pengelolaanPembelajaran.php';
}
</script>

</body>
</html>
