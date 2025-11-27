<?php
require_once "../config/db.php";
include "../config/session.php"; // tetap disertakan untuk keamanan login

// Ambil data guru + akun
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

  <!-- HEADER -->
  <header>
    <div class="logo">
      <img src="../assets/logo-elearning.png" alt="E-School Logo">
    </div>
  </header>

  <!-- MENU -->
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

  <!-- MAIN -->
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
    <div class="action-buttons">
        <button id="btnAdd" class="btn green"><i class="fa fa-plus"></i> Add Teacher</button>
        <button id="btnEdit" class="btn yellow" disabled><i class="fa fa-pen"></i> Edit Teacher</button>
        <button id="btnDelete" class="btn red" disabled><i class="fa fa-trash"></i> Delete Teacher</button>
    </div>

    <div class="table-container">
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
                  data-nama="<?= htmlspecialchars($row['nama']) ?>"
                  data-notelp="<?= htmlspecialchars($row['noTelp']) ?>"
                  data-email="<?= htmlspecialchars($row['email']) ?>"
                  data-id="<?= htmlspecialchars($row['idAkun']) ?>"
              >
                  <td><input type="checkbox" class="row-check"></td>
                  <td><?= $row['NIP'] ?></td>
                  <td><?= htmlspecialchars($row['nama']) ?></td>
                  <td><?= htmlspecialchars($row['noTelp']) ?></td>
                  <td><?= htmlspecialchars($row['email']) ?></td>
              </tr>
          <?php endwhile; ?>
          </tbody>
      </table>
    </div>
  </section>

  <!-- MODAL -->
  <div id="guruModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="formTitle">Add Teacher</h3>
        <span class="close">&times;</span>
      </div>
      
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
                  <i class="fa-solid fa-floppy-disk"></i> Submit
              </button>
              <button type="button" id="cancelBtn" class="btn">Cancel</button>
          </div>
      </form>
    </div>
  </div>

  <script>
  const rows = document.querySelectorAll("#guruTable tbody tr");
  const btnEdit = document.getElementById("btnEdit");
  const btnDelete = document.getElementById("btnDelete");
  const btnAdd = document.getElementById("btnAdd");
  const modal = document.getElementById("guruModal");
  const closeModal = document.querySelector(".close");
  const cancelBtn = document.getElementById("cancelBtn");
  const guruForm = document.getElementById("guruForm");

  const ipOriginal = document.getElementById("original_nip");
  const ipNip = document.getElementById("nip");
  const ipNama = document.getElementById("nama");
  const ipEmail = document.getElementById("email");
  const ipPassword = document.getElementById("password");
  const ipTelp = document.getElementById("noTelp");

  let selectedRow = null;

  // Pilih baris
  rows.forEach(row => {
      row.addEventListener("click", () => {
          rows.forEach(r => r.classList.remove("selected"));
          row.classList.add("selected");
          selectedRow = row;
          btnEdit.disabled = false;
          btnDelete.disabled = false;
      });
  });

  // Tambah guru
  btnAdd.onclick = () => {
      guruForm.action = "backend/add-guru.php";
      document.getElementById("formTitle").innerText = "Add Teacher";
      ipOriginal.value = "";
      ipNip.value = "";
      ipNama.value = "";
      ipEmail.value = "";
      ipPassword.value = "";
      ipTelp.value = "";
      modal.style.display = "block";
  };

  // Edit guru
  btnEdit.onclick = () => {
      if (!selectedRow) return;
      guruForm.action = "backend/edit-guru.php";
      document.getElementById("formTitle").innerText = "Edit Teacher";

      ipOriginal.value = selectedRow.dataset.nip;
      ipNip.value = selectedRow.dataset.nip;
      ipNama.value = selectedRow.dataset.nama;
      ipEmail.value = selectedRow.dataset.email;
      ipPassword.value = "";
      ipTelp.value = selectedRow.dataset.notelp;

      modal.style.display = "block";
  };

  // Close modal
  closeModal.onclick = () => {
      modal.style.display = "none";
  };

  cancelBtn.onclick = () => {
      modal.style.display = "none";
  };

  // Close modal saat klik luar modal
  window.onclick = (event) => {
      if (event.target == modal) {
          modal.style.display = "none";
      }
  };

  // Hapus guru
  btnDelete.onclick = async () => {
      if (!selectedRow) return;
      const nip = selectedRow.dataset.nip;

      if (!confirm(`Yakin ingin menghapus guru NIP ${nip}?`)) return;

      try {
          const res = await fetch(`backend/delete-guru.php?nip=${nip}`);
          const result = await res.json();

          if (result.success) {
              alert(`Guru berhasil dihapus (${result.deletedGuru} guru, ${result.deletedAkun} akun)`);
              location.reload();
          } else {
              alert("Gagal menghapus: " + result.error);
          }
      } catch (err) {
          console.error(err);
          alert("Terjadi kesalahan koneksi.");
      }
  };
  </script>

  <script>
  // Dropdown logic
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
