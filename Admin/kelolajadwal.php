<?php
require_once "../config/db.php";

// Ambil data jadwal beserta nama mapel dan guru
$data = $conn->query("
    SELECT 
        j.idJadwal, 
        j.kodeMapel, 
        m.namaMapel, 
        j.nipGuru, 
        g.nama AS namaGuru,
        j.hari, 
        j.jamMulai, 
        j.durasi, 
        j.ruangan, 
        j.kelas
    FROM jadwalmapel j
    LEFT JOIN mapel m ON j.kodeMapel = m.kodeMapel
    LEFT JOIN dataguru g ON j.nipGuru = g.NIP
    ORDER BY FIELD(j.hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), j.jamMulai
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Jadwal Mapel | E-School</title>
  <link rel="stylesheet" href="css/kelolamapel.css?v=<?php echo time(); ?>">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<header>
  <div class="logo">
    <img src="../assets/logo-elearning.png" alt="E-School Logo">
  </div>
</header>

<div class="menu-row">
  <div class="dropdown">
    <button class="dropbtn"><i class="fa-solid fa-database"></i> Data Master</button>
    <div class="dropdown-content">
      <a href="kelolaguru.php"><i class="fa-solid fa-chalkboard-user"></i> Kelola Guru</a>
      <a href="kelolasiswa.php"><i class="fa-solid fa-user-graduate"></i> Kelola Siswa</a>
    </div>
  </div>

  <div class="dropdown">
    <button class="dropbtn"><i class="fa-solid fa-school"></i> Pembelajaran</button>
    <div class="dropdown-content">
      <a href="kelolamapel.php"><i class="fa-solid fa-book"></i> Kelola Mapel</a>
      <a href="kelolajadwal.php"><i class="fa-solid fa-calendar-days"></i> Kelola Jadwal</a>
    </div>
  </div>

  <div class="dropdown">
    <button class="dropbtn"><i class="fa-solid fa-house"></i> Dashboard</button>
    <div class="dropdown-content">
      <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard Utama</a>
    </div>
  </div>
</div>

<main>
  <section class="welcome-box">
    <h2>Halo! Selamat Datang, <span>Rizky</span></h2>
    <p>Kelola jadwal pelajaran sesuai kelas dan guru pengampu.</p>
  </section>

  <div class="search-bar">
    <input type="text" placeholder="Search...">
    <button><i class="fa-solid fa-magnifying-glass"></i></button>
  </div>
</main>

<section class="data-section">
  <div class="action-buttons">
    <button id="btnAdd" class="btn green"><i class="fa fa-plus"></i> Add Jadwal</button>
    <button id="btnEdit" class="btn yellow" disabled><i class="fa fa-pen"></i> Edit Jadwal</button>
    <button id="btnDelete" class="btn red" disabled><i class="fa fa-trash"></i> Delete Jadwal</button>
  </div>

  <table class="data-table" id="jadwalTable">
    <thead>
      <tr>
        <th>Pilih</th>
        <th>Kode Mapel</th>
        <th>Nama Mapel</th>
        <th>Guru</th>
        <th>Hari</th>
        <th>Jam Mulai</th>
        <th>Durasi (menit)</th>
        <th>Ruangan</th>
        <th>Kelas</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $data->fetch_assoc()): ?>
      <tr 
        data-id="<?= $row['idJadwal'] ?>"
        data-mapel="<?= $row['kodeMapel'] ?>"
        data-guru="<?= $row['nipGuru'] ?>"
        data-hari="<?= $row['hari'] ?>"
        data-jam="<?= $row['jamMulai'] ?>"
        data-durasi="<?= $row['durasi'] ?>"
        data-ruangan="<?= $row['ruangan'] ?>"
        data-kelas="<?= $row['kelas'] ?>"
      >
        <td><input type="checkbox" class="row-check"></td>
        <td><?= $row['kodeMapel'] ?></td>
        <td><?= $row['namaMapel'] ?></td>
        <td><?= $row['namaGuru'] ?: '-' ?></td>
        <td><?= $row['hari'] ?></td>
        <td><?= $row['jamMulai'] ?></td>
        <td><?= $row['durasi'] ?></td>
        <td><?= $row['ruangan'] ?></td>
        <td><?= $row['kelas'] ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <section class="form-panel hidden" id="formPanel">
    <h3 id="formTitle">Add Jadwal</h3>
    <form id="jadwalForm" method="POST" action="backend/add-jadwalmapel.php">
      <input type="hidden" name="idJadwal" id="idJadwal">

      <div class="row">
        <label>Kode Mapel</label>
        <select name="kodeMapel" id="kodeMapel" required>
          <option value="">-- Pilih Mapel --</option>
          <?php
          $mapelList = $conn->query("SELECT kodeMapel, namaMapel FROM mapel");
          while ($m = $mapelList->fetch_assoc()):
          ?>
          <option value="<?= $m['kodeMapel'] ?>"><?= $m['namaMapel'] ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="row">
        <label>Guru Pengampu</label>
        <select name="nipGuru" id="nipGuru" required>
          <option value="">-- Pilih Guru --</option>
          <?php
          $guruList = $conn->query("SELECT NIP, nama FROM dataguru");
          while ($g = $guruList->fetch_assoc()):
          ?>
          <option value="<?= $g['NIP'] ?>"><?= $g['nama'] ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="row">
        <label>Hari</label>
        <select name="hari" id="hari" required>
          <option value="">-- Pilih Hari --</option>
          <?php
          $hariList = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
          foreach ($hariList as $h) echo "<option value='$h'>$h</option>";
          ?>
        </select>
      </div>

      <div class="row">
        <label>Jam Mulai</label>
        <input type="time" name="jamMulai" id="jamMulai" required>
      </div>

      <div class="row">
        <label>Durasi (menit)</label>
        <input type="number" name="durasi" id="durasi" min="10" required>
      </div>

      <div class="row">
        <label>Ruangan</label>
        <input type="text" name="ruangan" id="ruangan" maxlength="20">
      </div>

      <div class="row">
        <label>Kelas</label>
        <select name="kelas" id="kelas" required>
          <option value="">-- Pilih Kelas --</option>
          <?php
          $kelasList = ['X-1','X-2','XI-1','XI-2','XII-1','XII-2'];
          foreach ($kelasList as $k) echo "<option value='$k'>$k</option>";
          ?>
        </select>
      </div>

      <div class="form-actions">
        <button class="btn green" type="submit"><i class="fa-solid fa-floppy-disk"></i> Submit</button>
        <button type="button" id="cancelBtn" class="btn">Cancel</button>
      </div>
    </form>
  </section>
</section>

<script>
const rows = document.querySelectorAll("#jadwalTable tbody tr");
const btnEdit = document.getElementById("btnEdit");
const btnDelete = document.getElementById("btnDelete");
const btnAdd = document.getElementById("btnAdd");
const formPanel = document.getElementById("formPanel");
const cancelBtn = document.getElementById("cancelBtn");
const jadwalForm = document.getElementById("jadwalForm");

const ipId = document.getElementById("idJadwal");
const ipMapel = document.getElementById("kodeMapel");
const ipGuru = document.getElementById("nipGuru");
const ipHari = document.getElementById("hari");
const ipJam = document.getElementById("jamMulai");
const ipDurasi = document.getElementById("durasi");
const ipRuangan = document.getElementById("ruangan");
const ipKelas = document.getElementById("kelas");

let selectedRow = null;

rows.forEach(row => {
  row.addEventListener("click", () => {
    rows.forEach(r => r.classList.remove("selected"));
    row.classList.add("selected");
    selectedRow = row;
    btnEdit.disabled = false;
    btnDelete.disabled = false;
  });
});

btnAdd.onclick = () => {
  jadwalForm.action = "backend/add-jadwalmapel.php";
  document.getElementById("formTitle").innerText = "Add Jadwal";
  ipId.value = "";
  jadwalForm.reset();
  formPanel.classList.remove("hidden");
};

btnEdit.onclick = () => {
  if (!selectedRow) return;
  jadwalForm.action = "backend/edit-jadwalmapel.php";
  document.getElementById("formTitle").innerText = "Edit Jadwal";

  ipId.value = selectedRow.dataset.id;
  ipMapel.value = selectedRow.dataset.mapel;
  ipGuru.value = selectedRow.dataset.guru;
  ipHari.value = selectedRow.dataset.hari;
  ipJam.value = selectedRow.dataset.jam;
  ipDurasi.value = selectedRow.dataset.durasi;
  ipRuangan.value = selectedRow.dataset.ruangan;
  ipKelas.value = selectedRow.dataset.kelas;
  formPanel.classList.remove("hidden");
};

cancelBtn.onclick = () => formPanel.classList.add("hidden");

btnDelete.onclick = () => {
  if (!selectedRow) return;
  if (confirm("Yakin ingin menghapus jadwal ini?")) {
    const id = selectedRow.dataset.id;
    window.location.href = "backend/delete-jadwalmapel.php?id=" + id;
  }
};
</script>

<script>
// Dropdown handler
document.addEventListener("DOMContentLoaded", function () {
  const dropdownButtons = document.querySelectorAll(".dropbtn");

  dropdownButtons.forEach(btn => {
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      const menu = this.nextElementSibling;
      document.querySelectorAll(".dropdown-content").forEach(dc => {
        if (dc !== menu) dc.style.display = "none";
      });
      menu.style.display = menu.style.display === "block" ? "none" : "block";
    });
  });

  document.addEventListener("click", () => {
    document.querySelectorAll(".dropdown-content").forEach(dc => dc.style.display = "none");
  });
});
</script>

</body>
</html>
