<?php
session_start();
$halaman = isset($_GET['page']) ? $_GET['page'] : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mata Pelajaran - E-School</title>

<!-- ====== Google Fonts ====== -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- ====== Font Awesome ====== -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- ====== File CSS ====== -->
<link rel="stylesheet" href="cssSiswa/dashboard.css">
<link rel="stylesheet" href="cssSiswa/mataPelajaran.css">
</head>
<body>

<!-- ===== HEADER (disamakan seperti di dashboard) ===== -->
<div class="sticky-header">
  <header>
    <img src="../assets/logo-elearning.png" class="logo" alt="E-School">

    <!-- MENU ROW -->
    <div class="menu-row">

      <div class="dropdown">
        <button class="dropbtn">
          <i class="fa-solid fa-database"></i>
          Data Master
          <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
        </button>
        <div class="dropdown-content">
          <a href="#"><i class="fa-solid fa-user"></i> Data Guru</a>
          <a href="#"><i class="fa-solid fa-users"></i> Data Siswa</a>
        </div>
      </div>

      <div class="dropdown">
        <button class="dropbtn">
          <i class="fa-solid fa-clipboard-check"></i>
          Presensi Siswa
          <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
        </button>
        <div class="dropdown-content">
          <a href="#"><i class="fa-solid fa-check"></i> Lihat Presensi</a>
        </div>
      </div>

      <div class="dropdown">
        <button class="dropbtn">
          <i class="fa-solid fa-school"></i>
          Pengelolaan Pembelajaran
          <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
        </button>
        <div class="dropdown-content">
          <a href="#"><i class="fa-solid fa-book-open"></i> Materi</a>
          <a href="#"><i class="fa-solid fa-file-lines"></i> Tugas</a>
          <a href="#"><i class="fa-solid fa-pen-to-square"></i> Quiz</a>
        </div>
      </div>

    </div>
  </header>
</div>

<!-- ===== WELCOME BOX ===== -->
<div class="welcome-box">
    <?php
    $namaGuru = "Marta";
    $pelajaranSelanjutnya = "Matematika";
    ?>
    <h2>Halo! Selamat Datang, <?= htmlspecialchars($namaGuru) ?></h2>
    <p>Jadwal Pelajaran selanjutnya <?= htmlspecialchars($pelajaranSelanjutnya) ?></p>
</div>


<!-- ===== SECTION MATA PELAJARAN ===== -->
<section class="mapel-container">
    <h3>Mata Pelajaran</h3>
    <div class="mapel-grid">

        <!-- === Contoh Card Mapel === -->
        <div class="mapel-card" onclick="toggleMateri(this)">
            <p class="kode-mapel">BIN101</p>
            <h4>Bahasa Indonesia</h4>

            <!-- Materi list -->
            <div class="materi-list">
                <div class="materi merah" onclick="toggleButton(this, event)">
                    <span class="judul">Materi 1: Esai yang baik dan benar</span>
                    <span class="tgl-materi">15 Sept 2025</span>
                </div>
                <div class="materi biru" onclick="toggleButton(this, event)">
                    <span class="judul">Materi 2: Penyusunan proposal kegiatan</span>
                    <span class="tgl-materi">15 Sept 2025</span>
                </div>
                <div class="materi biru" onclick="toggleButton(this, event)">
                    <span class="judul">Materi 3: Perbedaan dongeng dan cerita rakyat</span>
                    <span class="tgl-materi">15 Sept 2025</span>
                </div>
                <div class="materi merah" onclick="toggleButton(this, event)">
                    <span class="judul">Materi 4: Pantun dan Puisi</span>
                    <span class="tgl-materi">15 Sept 2025</span>
                </div>
            </div>
        </div>

        <div class="mapel-card" onclick="toggleMateri(this)">
            <p class="kode-mapel">BIG102</p>
            <h4>Bahasa Inggris</h4>
            <div class="materi-list">
                <div class="materi biru" onclick="toggleButton(this, event)">
                    <span class="judul">Materi 1: Introduction & Greetings</span>
                    <span class="tgl-materi">16 Sept 2025</span>
                </div>
                <div class="materi merah" onclick="toggleButton(this, event)">
                    <span class="judul">Materi 2: Simple Present Tense</span>
                    <span class="tgl-materi">16 Sept 2025</span>
                </div>
            </div>
        </div>

        <div class="mapel-card" onclick="toggleMateri(this)">
            <p class="kode-mapel">MTK103</p>
            <h4>Matematika</h4>
            <div class="materi-list">
                <div class="materi merah" onclick="toggleButton(this, event)">
                    <span class="judul">Materi 1: Bilangan Pecahan</span>
                    <span class="tgl-materi">17 Sept 2025</span>
                </div>
                <div class="materi biru" onclick="toggleButton(this, event)">
                    <span class="judul">Materi 2: Persamaan Linear</span>
                    <span class="tgl-materi">17 Sept 2025</span>
                </div>
                <div class="materi biru" onclick="toggleButton(this, event)">
                    <span class="judul">Materi 3: Geometri Dasar</span>
                    <span class="tgl-materi">17 Sept 2025</span>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- === Script interaktif untuk mapel === -->
<script>
function toggleMateri(card) {
    document.querySelectorAll('.mapel-card').forEach(c => {
        if (c !== card) c.classList.remove('active');
    });
    card.classList.toggle('active');
}

function toggleButton(el, event) {
    event.stopPropagation();
    document.querySelectorAll('.button-group').forEach(b => b.remove());
    const btnGroup = document.createElement('div');
    btnGroup.className = 'button-group';
    btnGroup.innerHTML = `
        <button class="btn-materi" onclick="openForm('materi', event)">Materi</button>
        <button class="btn-tugas" onclick="openForm('tugas', event)">Tugas</button>
    `;
    el.insertAdjacentElement('afterend', btnGroup);
}

function openForm(type, event) {
    event.stopPropagation();
    alert("Form " + type + " akan muncul di sini (bisa diganti popup atau section baru)");
}
</script>

<!-- ===== REKAP NILAI ===== -->
<section class="rekap-nilai">
    <h3>Rekap Nilai</h3>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Kode Mapel</th>
                    <th>Mata Pelajaran</th>
                    <th colspan="2">Minggu 1</th>
                    <th colspan="2">Minggu 2</th>
                    <th colspan="2">Minggu 3</th>
                    <th colspan="2">Minggu 4</th>
                    <th colspan="2">Minggu 5</th>
                    <th colspan="2">Minggu 6</th>
                </tr>
                <tr class="sub-header">
                    <th></th><th></th>
                    <th>Tgs 1</th><th>Tgs 2</th>
                    <th>Tgs 1</th><th>Tgs 2</th>
                    <th>Tgs 1</th><th>Tgs 2</th>
                    <th>Tgs 1</th><th>Tgs 2</th>
                    <th>Tgs 1</th><th>Tgs 2</th>
                    <th>Tgs 1</th><th>Tgs 2</th>
                </tr>
            </thead>
            <tbody>
                <?php
                for ($i = 0; $i < 10; $i++) {
                    echo "
                    <tr>
                        <td>MIF302801</td>
                        <td>Bahasa Indonesia</td>
                        <td>80</td><td>80</td>
                        <td>80</td><td>80</td>
                        <td>80</td><td>80</td>
                        <td>80</td><td>80</td>
                        <td>80</td><td>80</td>
                        <td>80</td><td>80</td>
                    </tr>
                    ";
                }
                ?>
            </tbody>
        </table>
    </div>
</section>



<!-- ===== POPUP FORM PENGUMPULAN TUGAS ===== -->
<div id="popupTugas" class="popup-overlay">
  <div class="popup-content">
    <div class="popup-header">
      <h2>Pengumpulan Tugas</h2>
      <span class="status-label belum">Belum Dikerjakan</span>
      <button class="close-btn" onclick="closePopup()">&times;</button>
    </div>

    <div class="popup-body">
      <div class="mapel-row">
        <p class="mapel">Bahasa Indonesia</p>
        <p class="tanggal">Kamis, 20 September 2021</p>
      </div>

      <h3>Materi 3 : Perbedaan dongeng dan cerita rakyat (ini judul tugas)</h3>

      <label>Deskripsi Tugas</label>
      <textarea readonly>BLABLABLABLABLABLA (ini ambil dari database dan gabisa di edit)</textarea>

      <label>Dateline</label>
      <input type="text" readonly value="Sabtu, 23 September 2021">

      <label>Upload Tugas</label>
      <div class="upload-box">
        <input type="file" id="fileUpload" accept=".pdf,.docx,.pptx,.jpg,.png" onchange="showFileName()">
        <!--<span id="fileName">Tidak ada file yang diupload</span>-->
        <button class="upload-icon"><i class="fa-solid fa-upload"></i></button>
      </div>

      <div class="info-row">
        <div>
          <label>Dikirim Pada</label>
          <input type="text" readonly value="">
        </div>
        <div>
          <label>Nilai</label>
          <input type="text" readonly value="">
        </div>
      </div>

      <label>Status</label>
      <input type="text" readonly value="Tepat waktu / Terlambat">

      <button class="btn-kumpul">KUMPULKAN</button>
    </div>
  </div>
</div>

<!-- ===== POPUP FORM MATERI PEMBELAJARAN ===== -->
<div id="popupMateri" class="popup-overlay">
  <div class="popup-content">
    <div class="popup-header">
      <h2>Materi Pembelajaran</h2>
      <span class="status-label selesai">Selesai</span>
      <button class="close-btn" onclick="closePopup()">&times;</button>
    </div>

    <div class="popup-body">
      <div class="mapel-row">
        <p class="mapel">Bahasa Indonesia</p>
        <p class="tanggal">Kamis, 20 September 2021</p>
      </div>

      <h3>Materi 3 : Perbedaan dongeng dan cerita rakyat (ini judul materi)</h3>

      <p class="deskripsi">
        Deskripsi materi (klo ada), blablablablablabalbalblablablablabla balbalblabalblabla
        lablbabababal balalbbalbalblaa balablablablbalblabla blablalbalbalbabla
      </p>

      <label>File/Link Materi</label>
      <div class="upload-box materi-box" id="materiBox">
        <!-- Contoh tampilan jika file -->
        <!-- <a href="uploads/materi1.pdf" target="_blank">📄 Lihat File Materi</a> -->
        <!-- Contoh tampilan jika link -->
        <a href="https://contohlinkmateri.com" target="_blank">🔗 https://contohlinkmateri.com</a>
      </div>

      <button class="btn-kumpul">SELESAI</button>
    </div>
  </div>
</div>


<script>
function openForm(type, event) {
  event.stopPropagation();
  closePopup(); // tutup popup lain kalau ada yang terbuka

  if (type === 'tugas') {
    document.getElementById('popupTugas').style.display = 'flex';
  } else if (type === 'materi') {
    document.getElementById('popupMateri').style.display = 'flex';
  } else {
    alert("Form " + type + " belum dibuat.");
  }
}

function closePopup() {
  document.querySelectorAll('.popup-overlay').forEach(p => p.style.display = 'none');
}

function showFileName() {
  const fileInput = document.getElementById('fileUpload');
  const fileName = document.getElementById('fileName');
  fileName.textContent = fileInput.files.length ? fileInput.files[0].name : 'Tidak ada file yang diupload';
}
</script>




</body>
</html>
