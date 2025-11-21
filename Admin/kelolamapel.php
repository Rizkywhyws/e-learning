<?php
require_once "../config/db.php";
include '../config/session.php';

// Ambil data mapel + guru pengampu (jika ada)
$data = $conn->query("
    SELECT 
        m.kodeMapel, 
        m.namaMapel, 
        g.NIP AS nipGuru, 
        g.nama AS namaGuru
    FROM mapel m
    LEFT JOIN gurumapel gm ON m.kodeMapel = gm.kodeMapel
    LEFT JOIN dataGuru g ON gm.nipGuru = g.NIP
");

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Mapel | E-School</title>
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
    <p>Kelola daftar mata pelajaran dan guru pengampu.</p>
  </section>

  <div class="search-bar">
    <input type="text" placeholder="Search...">
    <button><i class="fa-solid fa-magnifying-glass"></i></button>
  </div>
</main>

<section class="data-section">

  <div class="action-buttons">
    <button id="btnAdd" class="btn green"><i class="fa fa-plus"></i> Add Mapel</button>
    <button id="btnEdit" class="btn yellow" disabled><i class="fa fa-pen"></i> Edit Mapel</button>
    <button id="btnDelete" class="btn red" disabled><i class="fa fa-trash"></i> Delete Mapel</button>
  </div>

  <table class="data-table" id="mapelTable">
    <thead>
      <tr>
        <th>Pilih</th>
        <th>Kode Mapel</th>
        <th>Nama Mapel</th>
        <th>Guru Pengampu</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $data->fetch_assoc()): ?>
      <tr 
        data-kode="<?= $row['kodeMapel'] ?>"
        data-nama="<?= $row['namaMapel'] ?>"
        data-guru="<?= $row['nipGuru'] ?>"
      >
        <td><input type="checkbox" class="row-check"></td>
        <td><?= $row['kodeMapel'] ?></td>
        <td><?= $row['namaMapel'] ?></td>
        <td><?= $row['namaGuru'] ?: '-' ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

</section>

<!-- MODAL -->
<div id="mapelModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h3 id="formTitle">Add Mapel</h3>

    <form id="mapelForm" method="POST" action="backend/add-mapel.php">
      <input type="hidden" name="original_kode" id="original_kode">

      <div class="row">
        <label>Kode Mapel</label>
        <input type="text" id="kodeMapel" name="kodeMapel" maxlength="8" required>
      </div>

      <div class="row">
        <label>Nama Mapel</label>
        <input type="text" id="namaMapel" name="namaMapel" required>
      </div>

      <div class="row">
        <label>Guru Pengampu</label>
        <select name="nipGuru" id="nipGuru">
          <option value="">-- Pilih Guru --</option>
          <?php
          $guruList = $conn->query("SELECT NIP, nama FROM dataGuru");
          while ($g = $guruList->fetch_assoc()):
          ?>
          <option value="<?= $g['NIP'] ?>"><?= $g['nama'] ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="form-actions">
        <button class="btn green" type="submit"><i class="fa-solid fa-floppy-disk"></i> Submit</button>
        <button type="button" id="cancelBtn" class="btn">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
const rows = document.querySelectorAll("#mapelTable tbody tr");
const btnEdit = document.getElementById("btnEdit");
const btnDelete = document.getElementById("btnDelete");
const btnAdd = document.getElementById("btnAdd");
const modal = document.getElementById("mapelModal");
const closeModal = document.querySelector(".close");
const cancelBtn = document.getElementById("cancelBtn");
const mapelForm = document.getElementById("mapelForm");

const ipKode = document.getElementById("kodeMapel");
const ipNama = document.getElementById("namaMapel");
const ipGuru = document.getElementById("nipGuru");
const ipOriginal = document.getElementById("original_kode");

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

// Buka modal untuk Add
btnAdd.onclick = () => {
  mapelForm.action = "backend/add-mapel.php";
  document.getElementById("formTitle").innerText = "Add Mapel";
  ipOriginal.value = "";
  ipKode.value = "";
  ipNama.value = "";
  ipGuru.value = "";
  modal.style.display = "block";
};

// Buka modal untuk Edit
btnEdit.onclick = () => {
  if (!selectedRow) return;
  mapelForm.action = "backend/edit-mapel.php";
  document.getElementById("formTitle").innerText = "Edit Mapel";
  ipOriginal.value = selectedRow.dataset.kode;
  ipKode.value = selectedRow.dataset.kode;
  ipNama.value = selectedRow.dataset.nama;
  ipGuru.value = selectedRow.dataset.guru;
  modal.style.display = "block";
};

// Tutup modal
closeModal.onclick = () => {
  modal.style.display = "none";
};

cancelBtn.onclick = () => {
  modal.style.display = "none";
};

// Tutup modal jika klik di luar modal
window.onclick = (event) => {
  if (event.target == modal) {
    modal.style.display = "none";
  }
};

// Delete
btnDelete.onclick = () => {
  if (!selectedRow) return;
  if (confirm("Yakin ingin menghapus mapel ini?")) {
    const kode = selectedRow.dataset.kode;
    window.location.href = "backend/delete-mapel.php?kode=" + kode;
  }
};
</script>

<script>
// Dropdown JS
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