<?php
require_once "../config/db.php";

$data = $conn->query("
    SELECT d.NIP, d.nama, d.noTelp, a.email, d.idAkun
    FROM dataGuru d
    LEFT JOIN akun a ON d.idAkun = a.idAkun
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin | E-School</title>
  <link rel="stylesheet" href="css/kelolaguru.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

  <!-- HEADER HANYA LOGO -->
  <header>
    <div class="logo">
      <img src="../assets/logo-elearning.png" alt="E-School Logo">
    </div>
  </header>

  <!-- DROPDOWN DIPINDAH KE SINI -->
  <div class="menu-row">
    <div class="dropdown">
      <button class="dropbtn">
        <i class="fa-solid fa-database"></i> Data Master
      </button>
      <div class="dropdown-content">
        <a href="kelolaguru.php"><i class="fa-solid fa-chalkboard-user"></i> Kelola Guru</a>
        <a href="kelolasiswa.php"><i class="fa-solid fa-user-graduate"></i> Kelola Siswa</a>
      </div>
    </div>

    <div class="dropdown">
      <button class="dropbtn">
        <i class="fa-solid fa-school"></i> Pembelajaran
      </button>
      <div class="dropdown-content">
        <a href="kelolamapel.php"><i class="fa-solid fa-book"></i> Kelola Mapel</a>
        <a href="jadwal.php"><i class="fa-solid fa-calendar-days"></i> Kelola Jadwal</a>
      </div>
    </div>
    <div class="dropdown">
  <button class="dropbtn">
    <i class="fa-solid fa-house"></i> Dashboard
  </button>
  <div class="dropdown-content">
      <a href="dashboard.php">
        <i class="fa-solid fa-gauge"></i> Dashboard Utama
      </a>
  </div>
</div>
  </div>

  <main>
    <section class="welcome-box">
      <h2>Halo! Selamat Datang, <span>Rizky</span></h2>
      <p>Jadwal mengajar selanjutnya ada di kelas <b>XII AKL 2</b></p>
    </section>

    <div class="search-bar">
      <input type="text" placeholder="Search...">
      <button><i class="fa-solid fa-magnifying-glass"></i></button>
    </div>
  </main>
  <section class="data-section">

    <!-- ===== BUTTON AREA ===== -->
    <div class="action-buttons">
        <button id="btnAdd" class="btn green"><i class="fa fa-plus"></i> Add Teacher</button>
        <button id="btnEdit" class="btn yellow" disabled><i class="fa fa-pen"></i> Edit Teacher</button>
        <button id="btnDelete" class="btn red" disabled><i class="fa fa-trash"></i> Delete Teacher</button>
    </div>

    <!-- ===== TABLE ===== -->
    <table class="data-table" id="guruTable">
        <thead>
            <tr>
                <th>Pilih</th>
                <th>NIP</th>
                <th>Nama</th>
                <th>No Telp</th>
                <th>Email</th>
            </tr>
        </thead>

        <tbody>
        <?php while ($row = $data->fetch_assoc()): ?>
            <tr 
                data-nip="<?= $row['NIP'] ?>"
                data-nama="<?= $row['nama'] ?>"
                data-notelp="<?= $row['noTelp'] ?>"
                data-email="<?= $row['email'] ?>"
                data-id="<?= $row['idAkun'] ?>"
            >
                <td><input type="checkbox" class="row-check"></td>
                <td><?= $row['NIP'] ?></td>
                <td><?= $row['nama'] ?></td>
                <td><?= $row['noTelp'] ?></td>
                <td><?= $row['email'] ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <!-- ===== FORM ADD/EDIT ===== -->
    <section class="form-panel hidden" id="formPanel">
        <h3 id="formTitle">Add Teacher</h3>

        <form id="guruForm" method="POST" action="backend/add-guru.php">

            <input type="hidden" name="original_nip" id="original_nip">

            <div class="row">
                <label>NIP</label>
                <input type="number" id="nip" name="nip" required>
            </div>

            <div class="row">
                <label>Nama</label>
                <input type="text" id="nama" name="nama" required>
            </div>

            <div class="row">
                <label>Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="row">
                <label>Password</label>
                <input type="password" id="password" name="password">
            </div>

            <div class="row">
                <label>No Telp</label>
                <input type="text" id="noTelp" name="noTelp" required>
            </div>

            <div class="form-actions">
                <button class="btn green" type="submit">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Submit
                </button>
                <button type="button" id="cancelBtn" class="btn">Cancel</button>
            </div>

        </form>
    </section>

</section>

<script>
// ELEMENT
const rows = document.querySelectorAll("#guruTable tbody tr");
const btnEdit = document.getElementById("btnEdit");
const btnDelete = document.getElementById("btnDelete");
const btnAdd = document.getElementById("btnAdd");
const formPanel = document.getElementById("formPanel");
const cancelBtn = document.getElementById("cancelBtn");
const guruForm = document.getElementById("guruForm");

// INPUT
const ipOriginal = document.getElementById("original_nip");
const ipNip = document.getElementById("nip");
const ipNama = document.getElementById("nama");
const ipEmail = document.getElementById("email");
const ipPassword = document.getElementById("password");
const ipTelp = document.getElementById("noTelp");

// STATE
let selectedRow = null;

// ✅ SELECT ROW
rows.forEach(row => {
    row.addEventListener("click", () => {
        rows.forEach(r => r.classList.remove("selected"));
        row.classList.add("selected");

        selectedRow = row;

        btnEdit.disabled = false;
        btnDelete.disabled = false;
    });
});

// ✅ OPEN ADD FORM
btnAdd.onclick = () => {
    guruForm.action = "backend/add-guru.php";
    document.getElementById("formTitle").innerText = "Add Teacher";

    ipOriginal.value = "";
    ipNip.value = "";
    ipNama.value = "";
    ipEmail.value = "";
    ipPassword.value = "";
    ipTelp.value = "";

    formPanel.classList.remove("hidden");
};

// ✅ OPEN EDIT FORM
btnEdit.onclick = () => {
    if (!selectedRow) return;

    guruForm.action = "backend/edit-guru.php";
    document.getElementById("formTitle").innerText = "Edit Teacher";

    ipOriginal.value = selectedRow.dataset.nip;
    ipNip.value = selectedRow.dataset.nip;
    ipNama.value = selectedRow.dataset.nama;
    ipEmail.value = selectedRow.dataset.email;
    ipPassword.value = ""; // kosong → tidak mengubah password
    ipTelp.value = selectedRow.dataset.notelp;

    formPanel.classList.remove("hidden");
};

// ✅ CLOSE FORM
cancelBtn.onclick = () => {
    formPanel.classList.add("hidden");
};

// ✅ DELETE CONFIRM
btnDelete.onclick = () => {
    if (!selectedRow) return;

    if (confirm("Yakin ingin menghapus guru ini?")) {
        const nip = selectedRow.dataset.nip;
        window.location.href = "backend/delete-guru.php?nip=" + nip;
    }
};
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  const dropdownButtons = document.querySelectorAll(".dropbtn");

  dropdownButtons.forEach(btn => {
    btn.addEventListener("click", function (e) {
      e.stopPropagation(); // cegah menutup langsung
      const menu = this.nextElementSibling;

      // tutup semua dropdown lain
      document.querySelectorAll(".dropdown-content").forEach(dc => {
        if (dc !== menu) dc.style.display = "none";
      });

      // toggle dropdown ini
      menu.style.display = menu.style.display === "block" ? "none" : "block";
    });
  });

  // klik di luar dropdown → tutup semua
  document.addEventListener("click", function () {
    document.querySelectorAll(".dropdown-content").forEach(dc => {
      dc.style.display = "none";
    });
  });
});
</script>
