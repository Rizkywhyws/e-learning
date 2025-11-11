<?php
require_once "../config/db.php";

$data = $conn->query("
    SELECT s.NIS, s.NISN, s.nama, s.kelas, s.jurusan, a.email, s.idAkun
    FROM dataSiswa s
    LEFT JOIN akun a ON s.idAkun = a.idAkun
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Siswa | E-School</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/kelolasiswa.css">
  <link rel="stylesheet" href="css/kelolaguru.css">
</head>
<body>

<header>
  <div class="logo">
    <img src="../assets/logo-elearning.png" alt="Logo">
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
    <h2>Halo! Selamat Datang, <span>Admin</span></h2>
    <p>Kelola Data Siswa</p>
  </section>

  <div class="search-bar">
    <input type="text" placeholder="Search...">
    <button><i class="fa-solid fa-magnifying-glass"></i></button>
  </div>
</main>

<section class="data-section">

  <div class="action-buttons">
      <button id="btnAdd" class="btn green"><i class="fa fa-plus"></i> Add Student</button>
      <button id="btnEdit" class="btn yellow" disabled><i class="fa fa-pen"></i> Edit Student</button>
      <button id="btnDelete" class="btn red" disabled><i class="fa fa-trash"></i> Delete Student</button>
  </div>

  <table class="data-table" id="siswaTable">
      <thead>
          <tr>
              <th>Pilih</th>
              <th>NIS</th>
              <th>NISN</th>
              <th>Nama</th>
              <th>Kelas</th>
              <th>Jurusan</th>
              <th>Email</th>
          </tr>
      </thead>

      <tbody>
      <?php while ($row = $data->fetch_assoc()): ?>
          <tr
            data-nis="<?= $row['NIS'] ?>"
            data-nisn="<?= $row['NISN'] ?>"
            data-nama="<?= $row['nama'] ?>"
            data-kelas="<?= $row['kelas'] ?>"
            data-jurusan="<?= $row['jurusan'] ?>"
            data-email="<?= $row['email'] ?>"
          >
              <td><input type="checkbox" class="row-check"></td>
              <td><?= $row['NIS'] ?></td>
              <td><?= $row['NISN'] ?></td>
              <td><?= $row['nama'] ?></td>
              <td><?= $row['kelas'] ?></td>
              <td><?= $row['jurusan'] ?></td>
              <td><?= $row['email'] ?></td>
          </tr>
      <?php endwhile; ?>
      </tbody>
  </table>

  <!-- ✅ FORM SESUAI add-siswa.php -->
  <section class="form-panel hidden" id="formPanel">
    <h3 id="formTitle">Add Student</h3>

    <form id="siswaForm" method="POST" action="backend/add-siswa.php">

      <input type="hidden" id="old_nis" name="old_nis">

      <div class="row">
        <label>NIS</label>
        <input type="number" id="nis" name="nis" required>
      </div>

      <div class="row">
        <label>NISN</label>
        <input type="number" id="nisn" name="nisn" required>
      </div>

      <div class="row">
        <label>Nama</label>
        <input type="text" id="nama" name="nama" required>
      </div>

      <div class="row">
        <label>Kelas</label>
        <select id="kelas" name="kelas" required>
          <option value="">-- Pilih Kelas --</option>
          <option value="X-1">X-1</option>
          <option value="X-2">X-2</option>
          <option value="XI-1">XI-1</option>
          <option value="XI-2">XI-2</option>
          <option value="XII-1">XII-1</option>
          <option value="XII-2">XII-2</option>
        </select>
      </div>

      <div class="row">
        <label>Jurusan</label>
        <input type="text" id="jurusan" name="jurusan" required>
      </div>

      <div class="row">
        <label>Email</label>
        <input type="email" id="email" name="email" required>
      </div>

      <div class="row">
        <label>Password (opsional)</label>
        <input type="password" id="password" name="password">
      </div>

        <div class="form-actions">
                <button class="btn green" type="submit">
                    <i class="fa-solid fa-floppy-disk"></i> Submit
                </button>
                <button type="button" id="cancelBtn" class="btn">Cancel</button>
            </div>

    </form>
  </section>

</section>

<script>
const rows = document.querySelectorAll("#siswaTable tbody tr");
const btnAdd = document.getElementById("btnAdd");
const btnEdit = document.getElementById("btnEdit");
const btnDelete = document.getElementById("btnDelete");
const formPanel = document.getElementById("formPanel");
const cancelBtn = document.getElementById("cancelBtn");
const siswaForm = document.getElementById("siswaForm");

const oldNis = document.getElementById("old_nis");
const ipNis = document.getElementById("nis");
const ipNisn = document.getElementById("nisn");
const ipNama = document.getElementById("nama");
const ipKelas = document.getElementById("kelas");
const ipJurusan = document.getElementById("jurusan");
const ipEmail = document.getElementById("email");
const ipPassword = document.getElementById("password");

let selectedRow = null;

// ✅ PILIH ROW
rows.forEach(row => {
  row.addEventListener("click", () => {
    rows.forEach(r => r.classList.remove("selected"));
    row.classList.add("selected");
    selectedRow = row;

    btnEdit.disabled = false;
    btnDelete.disabled = false;
  });
});

// ✅ ADD
btnAdd.onclick = () => {
  siswaForm.action = "backend/add-siswa.php";
  document.getElementById("formTitle").innerText = "Add Student";

  oldNis.value = "";
  ipNis.value = "";
  ipNisn.value = "";
  ipNama.value = "";
  ipKelas.value = "";
  ipJurusan.value = "";
  ipEmail.value = "";
  ipPassword.value = "";

  formPanel.classList.remove("hidden");
};

// ✅ EDIT
btnEdit.onclick = () => {
  if (!selectedRow) return;

  siswaForm.action = "backend/edit-siswa.php";
  document.getElementById("formTitle").innerText = "Edit Student";

  oldNis.value = selectedRow.dataset.nis;
  ipNis.value = selectedRow.dataset.nis;
  ipNisn.value = selectedRow.dataset.nisn;
  ipNama.value = selectedRow.dataset.nama;
  ipKelas.value = selectedRow.dataset.kelas;
  ipJurusan.value = selectedRow.dataset.jurusan;
  ipEmail.value = selectedRow.dataset.email;
  ipPassword.value = "";

  formPanel.classList.remove("hidden");
};

// ✅ CANCEL
cancelBtn.onclick = () => {
  formPanel.classList.add("hidden");
};

// ✅ DELETE
btnDelete.onclick = () => {
  if (!selectedRow) return;

  if (confirm("Yakin ingin menghapus siswa ini?")) {
    const nis = selectedRow.dataset.nis;
    window.location.href = "backend/delete-siswa.php?nis=" + nis;
  }
};
</script>

<script>
// ✅ Dropdown JS
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
