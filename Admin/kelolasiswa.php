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
    <input type="text" id="searchInput" placeholder="Search...">
    <button><i class="fa-solid fa-magnifying-glass"></i></button>
  </div>
</main>

<section class="data-section">

  <div class="action-buttons">
      <button id="btnAdd" class="btn green"><i class="fa fa-plus"></i> Add Student</button>
      <button id="btnImport" class="btn purple"><i class="fa fa-file-import"></i> Import Excel/CSV</button>
      <button id="btnEdit" class="btn yellow" disabled><i class="fa fa-pen"></i> Edit Student</button>
      <button id="btnDelete" class="btn red" disabled><i class="fa fa-trash"></i> Delete Student</button>
      <button id="btnNaikKelas" class="btn blue"><i class="fa-solid fa-arrow-up"></i> Naik Kelas</button>
  </div>

  <!-- FILTER KELAS -->
  <div class="filter-box">
      <label><b>Filter Kelas:</b></label>
      <select id="filterKelas">
          <option value="">Semua</option>
          <option value="X-1">X-1</option>
          <option value="X-2">X-2</option>
          <option value="XI-1">XI-1</option>
          <option value="XI-2">XI-2</option>
          <option value="XII-1">XII-1</option>
          <option value="XII-2">XII-2</option>
      </select>
  </div>

<div class="table-wrapper">
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
            data-nama="<?= htmlspecialchars($row['nama']) ?>"
            data-kelas="<?= $row['kelas'] ?>"
            data-jurusan="<?= htmlspecialchars($row['jurusan']) ?>"
            data-email="<?= htmlspecialchars($row['email']) ?>"
          >
              <td><input type="checkbox" class="row-check"></td>
              <td><?= $row['NIS'] ?></td>
              <td><?= $row['NISN'] ?></td>
              <td><?= htmlspecialchars($row['nama']) ?></td>
              <td><?= $row['kelas'] ?></td>
              <td><?= htmlspecialchars($row['jurusan']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
          </tr>
      <?php endwhile; ?>
      </tbody>
  </table>
</div>

</section>

<!-- MODAL ADD/EDIT SISWA -->
<div id="siswaModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h3 id="formTitle">Add Student</h3>
    
    <form id="siswaForm" method="POST" action="backend/add-siswa.php">
      <input type="hidden" id="old_nis" name="old_nis">

      <div class="row"><label>NIS</label><input type="number" id="nis" name="nis" required></div>
      <div class="row"><label>NISN</label><input type="number" id="nisn" name="nisn" required></div>
      <div class="row"><label>Nama</label><input type="text" id="nama" name="nama" required></div>
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

      <div class="row"><label>Jurusan</label><input type="text" id="jurusan" name="jurusan" required></div>
      <div class="row"><label>Email</label><input type="email" id="email" name="email" required></div>
      <div class="row"><label>Password (opsional)</label><input type="password" id="password" name="password"></div>

      <div class="form-actions">
        <button class="btn green" type="submit"><i class="fa-solid fa-floppy-disk"></i> Submit</button>
        <button type="button" id="cancelBtn" class="btn">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL IMPORT EXCEL/CSV -->
<div id="importModal" class="modal">
  <div class="modal-content">
    <span class="close-import">&times;</span>
    <h3>Import Data Siswa (Excel/CSV)</h3>
    
    <div class="import-info">
      <p><i class="fa-solid fa-info-circle"></i> <strong>Format file yang diperlukan:</strong></p>
      <ul>
        <li>File CSV (.csv) - Buka dengan Excel atau Google Sheets</li>
        <li>Kolom: <strong>NIS, NISN, Nama, Kelas, Jurusan, Email, Password</strong></li>
        <li>Kelas format: X-1, X-2, XI-1, XI-2, XII-1, XII-2</li>
        <li>Password akan di-hash otomatis oleh sistem</li>
      </ul>
      <a href="template/template_siswa.csv" class="btn-download"><i class="fa-solid fa-download"></i> Download Template CSV</a>
    </div>

    <form id="importForm" method="POST" action="backend/import-siswa.php" enctype="multipart/form-data">
      <div class="row">
        <label>Pilih File</label>
        <input type="file" id="fileImport" name="file" accept=".csv" required>
        <small class="file-info">File maksimal 5MB</small>
      </div>

      <div id="previewSection" style="display: none;">
        <h4>Preview Data:</h4>
        <div class="preview-wrapper">
          <table class="preview-table" id="previewTable">
            <thead></thead>
            <tbody></tbody>
          </table>
        </div>
        <p class="preview-count">Total: <span id="totalRows">0</span> data</p>
      </div>

      <div class="form-actions">
        <button class="btn green" type="submit" id="btnSubmitImport"><i class="fa-solid fa-upload"></i> Import Data</button>
        <button type="button" id="cancelImportBtn" class="btn">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL NAIK KELAS -->
<div id="modalNaikKelas" class="modal" style="display: none;">
  <div class="modal-content modal-naik-kelas">
    <span class="close-naik">&times;</span>
    <h3>Naikkan Kelas Siswa</h3>

    <div class="kelas-tujuan-wrapper">
      <label><b>Kelas Asal:</b></label>
      <select id="filterKelasModal" class="kelas-select">
        <option value="">-- Semua Kelas --</option>
        <option value="X-1">X-1</option>
        <option value="X-2">X-2</option>
        <option value="XI-1">XI-1</option>
        <option value="XI-2">XI-2</option>
        <option value="XII-1">XII-1</option>
        <option value="XII-2">XII-2</option>
      </select>
    </div>

    <hr style="margin: 15px 0; border: none; border-top: 1px solid #ddd;">

    <div class="select-all-wrapper">
      <label><input type="checkbox" id="selectAll"> <b>Select All</b></label>
    </div>

    <div class="table-wrapper modal-table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>Pilih</th>
            <th>NIS</th>
            <th>Nama</th>
            <th>Kelas</th>
            <th>Kelas Tujuan</th>
            <th>Jurusan</th>
          </tr>
        </thead>
        <tbody id="tableNaikKelas"></tbody>
      </table>
    </div>

    <div class="modal-actions">
      <button id="confirmNaik" class="btn green"><i class="fa-solid fa-check"></i> Naikkan</button>
      <button id="cancelNaik" class="btn red"><i class="fa-solid fa-xmark"></i> Batal</button>
    </div>

  </div>
</div>


<script>
const btnAdd = document.getElementById("btnAdd");
const btnImport = document.getElementById("btnImport");
const btnEdit = document.getElementById("btnEdit");
const btnDelete = document.getElementById("btnDelete");
const btnNaikKelas = document.getElementById("btnNaikKelas");

const siswaModal = document.getElementById("siswaModal");
const importModal = document.getElementById("importModal");
const modalNaikKelas = document.getElementById("modalNaikKelas");

const closeModal = document.querySelector(".close");
const closeImportModal = document.querySelector(".close-import");
const closeNaikModal = document.querySelector(".close-naik");
const cancelBtn = document.getElementById("cancelBtn");
const cancelImportBtn = document.getElementById("cancelImportBtn");

const siswaForm = document.getElementById("siswaForm");
const importForm = document.getElementById("importForm");

const oldNis = document.getElementById("old_nis");
const ipNis = document.getElementById("nis");
const ipNisn = document.getElementById("nisn");
const ipNama = document.getElementById("nama");
const ipKelas = document.getElementById("kelas");
const ipJurusan = document.getElementById("jurusan");
const ipEmail = document.getElementById("email");
const ipPassword = document.getElementById("password");

let selectedRow = null;

// ==================== MODAL SISWA ====================

// Pilih Row dengan Event Delegation
document.getElementById("siswaTable").addEventListener("click", function(e) {
  const row = e.target.closest("tbody tr");
  
  if (!row) return;
  if (e.target.type === "checkbox") return;
  
  document.querySelectorAll("#siswaTable tbody tr").forEach(r => {
    r.classList.remove("selected");
  });
  
  row.classList.add("selected");
  selectedRow = row;
  
  btnEdit.disabled = false;
  btnDelete.disabled = false;
});

// Add Student
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

  siswaModal.style.display = "block";
};

// Edit Student
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

  siswaModal.style.display = "block";
};

// Close Modal Siswa
closeModal.onclick = () => {
  siswaModal.style.display = "none";
};

cancelBtn.onclick = () => {
  siswaModal.style.display = "none";
};

// Delete Student
btnDelete.onclick = () => {
  if (!selectedRow) return;

  if (confirm("Yakin ingin menghapus siswa ini?")) {
    const nis = selectedRow.dataset.nis;
    window.location.href = "backend/delete-siswa.php?nis=" + nis;
  }
};

// ==================== MODAL IMPORT ====================

btnImport.onclick = () => {
  importModal.style.display = "block";
  document.getElementById("previewSection").style.display = "none";
  document.getElementById("fileImport").value = "";
};

closeImportModal.onclick = () => {
  importModal.style.display = "none";
};

cancelImportBtn.onclick = () => {
  importModal.style.display = "none";
};

// Preview File Import (CSV only)
document.getElementById("fileImport").addEventListener("change", function(e) {
  const file = e.target.files[0];
  if (!file) return;

  const reader = new FileReader();

  reader.onload = function(e) {
    const text = e.target.result;
    const lines = text.split('\n').filter(line => line.trim() !== '');
    
    if (lines.length === 0) {
      alert("File kosong!");
      return;
    }

    // Parse CSV manually
    const data = lines.map(line => {
      const result = [];
      let current = '';
      let inQuotes = false;
      
      for (let i = 0; i < line.length; i++) {
        const char = line[i];
        
        if (char === '"') {
          inQuotes = !inQuotes;
        } else if (char === ',' && !inQuotes) {
          result.push(current.trim());
          current = '';
        } else {
          current += char;
        }
      }
      result.push(current.trim());
      
      return result;
    });

    if (data.length < 2) {
      alert("File harus memiliki header dan minimal 1 data!");
      return;
    }

    showPreviewCSV(data);
  };

  reader.readAsText(file);
});

function showPreviewCSV(data) {
  const previewSection = document.getElementById("previewSection");
  const previewTable = document.getElementById("previewTable");
  const totalRows = document.getElementById("totalRows");

  const headers = data[0];
  const rows = data.slice(1);

  let headerHTML = "<tr>";
  headers.forEach(h => {
    headerHTML += `<th>${h}</th>`;
  });
  headerHTML += "</tr>";
  previewTable.querySelector("thead").innerHTML = headerHTML;

  let bodyHTML = "";
  rows.slice(0, 5).forEach(row => {
    bodyHTML += "<tr>";
    row.forEach(cell => {
      bodyHTML += `<td>${cell || '-'}</td>`;
    });
    bodyHTML += "</tr>";
  });
  previewTable.querySelector("tbody").innerHTML = bodyHTML;

  totalRows.innerText = rows.length;
  previewSection.style.display = "block";
}

// ==================== NAIK KELAS ====================

function hitungKelasTujuan(kelasAsal) {
  const mapping = {
    'X-1': 'XI-1',
    'X-2': 'XI-2',
    'XI-1': 'XII-1',
    'XI-2': 'XII-2',
    'XII-1': 'LULUS',
    'XII-2': 'LULUS'
  };
  return mapping[kelasAsal] || 'LULUS';
}

btnNaikKelas.onclick = () => {
  const tbody = document.getElementById("tableNaikKelas");
  tbody.innerHTML = ""; 

  document.querySelectorAll("#siswaTable tbody tr").forEach(r => {
    const kelasAsal = r.dataset.kelas;
    const kelasTujuan = hitungKelasTujuan(kelasAsal);
    
    tbody.innerHTML += `
      <tr data-kelas-asal="${kelasAsal}">
        <td><input type='checkbox' class='chkRow' data-nis='${r.dataset.nis}' data-kelas-asal='${kelasAsal}' data-kelas-tujuan='${kelasTujuan}'></td>
        <td>${r.dataset.nis}</td>
        <td>${r.dataset.nama}</td>
        <td>${kelasAsal}</td>
        <td><strong>${kelasTujuan}</strong></td>
        <td>${r.dataset.jurusan}</td>
      </tr>
    `;
  });

  document.getElementById("selectAll").checked = false;
  document.getElementById("filterKelasModal").value = "";

  modalNaikKelas.style.display = "block";
};

// Close Modal Naik Kelas
closeNaikModal.onclick = () => {
  modalNaikKelas.style.display = "none";
};

document.getElementById("cancelNaik").onclick = () => {
  modalNaikKelas.style.display = "none";
};

// Filter kelas di modal naik kelas
document.getElementById("filterKelasModal").addEventListener("change", function() {
  const selectedKelas = this.value;
  const rows = document.querySelectorAll("#tableNaikKelas tr[data-kelas-asal]");
  
  rows.forEach(row => {
    if (selectedKelas === "" || row.dataset.kelasAsal === selectedKelas) {
      row.style.display = "";
    } else {
      row.style.display = "none";
    }
  });
  
  document.getElementById("selectAll").checked = false;
});

// Select All checkbox
document.getElementById("selectAll").addEventListener("change", function() {
  const checkboxes = document.querySelectorAll(".chkRow");
  checkboxes.forEach(c => {
    const row = c.closest("tr");
    if (row.style.display !== "none") {
      c.checked = this.checked;
    }
  });
});

// Confirm Naik Kelas
document.getElementById("confirmNaik").onclick = async () => {
  const checkedBoxes = document.querySelectorAll(".chkRow:checked");
  
  if (checkedBoxes.length === 0) {
    alert("Pilih minimal 1 siswa!");
    return;
  }

  const groupedData = {};
  
  checkedBoxes.forEach(checkbox => {
    const nis = checkbox.dataset.nis;
    const kelasTujuan = checkbox.dataset.kelasTujuan;
    
    if (!groupedData[kelasTujuan]) {
      groupedData[kelasTujuan] = [];
    }
    groupedData[kelasTujuan].push(nis);
  });

  const btnConfirm = document.getElementById("confirmNaik");
  btnConfirm.disabled = true;
  btnConfirm.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';

  try {
    let totalSuccess = 0;
    let totalFailed = 0;

    for (const [kelasTujuan, siswaNIS] of Object.entries(groupedData)) {
      const dataToSend = {
        siswa: siswaNIS,
        tujuan: kelasTujuan
      };

      const response = await fetch("backend/naik-kelas.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify(dataToSend)
      });

      const result = await response.json();

      if (result.success) {
        totalSuccess += siswaNIS.length;
      } else {
        totalFailed += siswaNIS.length;
        console.error(`Error untuk kelas ${kelasTujuan}:`, result.message);
      }
    }

    if (totalSuccess > 0) {
      window.location.href = 'kelolasiswa.php';
    } else {
      alert(`Gagal menaikkan siswa. Total gagal: ${totalFailed}`);
      btnConfirm.disabled = false;
      btnConfirm.innerHTML = '<i class="fa-solid fa-check"></i> Naikkan';
    }

  } catch (error) {
    alert("Terjadi kesalahan: " + error.message);
    console.error("Error:", error);
    btnConfirm.disabled = false;
    btnConfirm.innerHTML = '<i class="fa-solid fa-check"></i> Naikkan';
  }
};

// ==================== FILTER KELAS ====================

const filterKelas = document.getElementById("filterKelas");

filterKelas.addEventListener("change", function () {
  const selected = this.value.toLowerCase();

  document.querySelectorAll("#siswaTable tbody tr").forEach(row => {
    const kelas = row.dataset.kelas.toLowerCase();
    row.style.display = (selected === "" || kelas === selected) ? "" : "none";
  });
});

// ==================== SEARCH FUNCTION ====================

const searchInput = document.getElementById("searchInput");

searchInput.addEventListener("input", function() {
  const searchValue = this.value.toLowerCase();
  
  document.querySelectorAll("#siswaTable tbody tr").forEach(row => {
    const nis = row.dataset.nis.toLowerCase();
    const nisn = row.dataset.nisn.toLowerCase();
    const nama = row.dataset.nama.toLowerCase();
    const kelas = row.dataset.kelas.toLowerCase();
    const jurusan = row.dataset.jurusan.toLowerCase();
    const email = row.dataset.email.toLowerCase();
    
    const match = nis.includes(searchValue) || 
                  nisn.includes(searchValue) || 
                  nama.includes(searchValue) || 
                  kelas.includes(searchValue) || 
                  jurusan.includes(searchValue) || 
                  email.includes(searchValue);
    
    row.style.display = match ? "" : "none";
  });
});

// ==================== CLOSE MODAL SAAT KLIK DI LUAR ====================

window.onclick = (event) => {
  if (event.target == siswaModal) {
    siswaModal.style.display = "none";
  }
  if (event.target == importModal) {
    importModal.style.display = "none";
  }
  if (event.target == modalNaikKelas) {
    modalNaikKelas.style.display = "none";
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