<?php
require_once "../config/db.php";
include '../config/session.php';

// Ambil data jadwal beserta nama mapel dan guru
$data = $conn->query("
    SELECT 
        j.idJadwalMapel, 
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

  <style>
    .filter-box {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      margin-bottom: 15px;
      margin-right: 50px;
      gap: 10px;
    }
    .filter-box label {
      font-weight: 600;
      color: #333;
    }
    .filter-box select {
      padding: 8px 8px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 14px;
      min-width: 100px;
      cursor: pointer;
    }
    .filter-box select:focus {
      outline: none;
      border-color: #4CAF50;
    }
  </style>

</head>
<body>

<?php include 'sidebarAdmin.php'; ?>

<div class="content">
  <h1>Kelola Jadwal Mata Pelajaran</h1>

  <!-- FILTER -->
  <div class="filter-box">
    <label for="filterHari">Filter Hari:</label>
    <select id="filterHari">
      <option value="all">Semua Hari</option>
      <option value="Senin">Senin</option>
      <option value="Selasa">Selasa</option>
      <option value="Rabu">Rabu</option>
      <option value="Kamis">Kamis</option>
      <option value="Jumat">Jumat</option>
      <option value="Sabtu">Sabtu</option>
    </select>

    <label for="searchInput">Cari:</label>
    <input type="text" id="searchInput" placeholder="Cari kelas / mapel / guru..." style="padding: 7px 10px; border-radius: 5px; border:1px solid #ddd;">
  </div>

  <button class="add-btn" id="btnTambah" onclick="openModal()">+ Tambah Jadwal</button>

  <table id="jadwalTable">
    <thead>
      <tr>
        <th>Kode Mapel</th>
        <th>Nama Mapel</th>
        <th>Nama Guru</th>
        <th>Hari</th>
        <th>Jam Mulai</th>
        <th>Durasi (menit)</th>
        <th>Ruangan</th>
        <th>Kelas</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody id="jadwalBody">
      <?php while ($row = $data->fetch_assoc()): ?>
        <tr data-hari="<?= $row['hari'] ?>">
          <td><?= $row['kodeMapel'] ?></td>
          <td><?= $row['namaMapel'] ?></td>
          <td><?= $row['namaGuru'] ?></td>
          <td><?= $row['hari'] ?></td>
          <td><?= $row['jamMulai'] ?></td>
          <td><?= $row['durasi'] ?></td>
          <td><?= $row['ruangan'] ?></td>
          <td><?= $row['kelas'] ?></td>
          <td>
            <button class="edit-btn" onclick='editJadwal(<?= json_encode($row) ?>)'>Edit</button>
            <button class="delete-btn" onclick="hapusJadwal(<?= $row['idJadwalMapel'] ?>)">Hapus</button>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<!-- MODAL FORM TAMBAH/EDIT -->
<div class="modal" id="modalForm">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>

    <h2 id="modalTitle">Tambah Jadwal</h2>

    <form id="formJadwal">
      <input type="hidden" id="idJadwalMapel">

      <label>Kode Mapel</label>
      <select id="kodeMapel" required>
        <option value="">Pilih Mapel</option>
        <?php
          $mapel = $conn->query("SELECT * FROM mapel ORDER BY namaMapel ASC");
          while ($m = $mapel->fetch_assoc()):
        ?>
        <option value="<?= $m['kodeMapel']; ?>"><?= $m['namaMapel']; ?></option>
        <?php endwhile; ?>
      </select>

      <label>Guru</label>
      <select id="nipGuru" required>
        <option value="">Pilih Guru</option>
        <?php
          $guru = $conn->query("SELECT NIP, nama FROM dataguru ORDER BY nama ASC");
          while ($g = $guru->fetch_assoc()):
        ?>
        <option value="<?= $g['NIP']; ?>"><?= $g['nama']; ?></option>
        <?php endwhile; ?>
      </select>

      <label>Hari</label>
      <select id="hari" required>
        <option value="Senin">Senin</option>
        <option value="Selasa">Selasa</option>
        <option value="Rabu">Rabu</option>
        <option value="Kamis">Kamis</option>
        <option value="Jumat">Jumat</option>
        <option value="Sabtu">Sabtu</option>
      </select>

      <label>Jam Mulai</label>
      <input type="time" id="jamMulai" required>

      <label>Durasi (menit)</label>
      <input type="number" id="durasi" required placeholder="Masukkan durasi">

      <label>Ruangan</label>
      <input type="text" id="ruangan" required placeholder="Cth: R-102">

      <label>Kelas</label>
      <select id="kelas" required>
        <option value="X">X</option>
        <option value="XI">XI</option>
        <option value="XII">XII</option>
      </select>

      <button type="submit" class="save-btn">Simpan</button>
    </form>
  </div>
</div>

<script>
// Open Modal
function openModal() {
  document.getElementById("modalTitle").innerText = "Tambah Jadwal";
  document.getElementById("formJadwal").reset();
  document.getElementById("idJadwalMapel").value = "";
  document.getElementById("modalForm").style.display = "block";
}

// Close Modal
function closeModal() {
  document.getElementById("modalForm").style.display = "none";
}

// Edit
function editJadwal(data) {
  openModal();
  document.getElementById("modalTitle").innerText = "Edit Jadwal";

  document.getElementById("idJadwalMapel").value = data.idJadwalMapel;
  document.getElementById("kodeMapel").value = data.kodeMapel;
  document.getElementById("nipGuru").value = data.nipGuru;
  document.getElementById("hari").value = data.hari;
  document.getElementById("jamMulai").value = data.jamMulai;
  document.getElementById("durasi").value = data.durasi;
  document.getElementById("ruangan").value = data.ruangan;
  document.getElementById("kelas").value = data.kelas;
}

// Submit Form (Tambah/Edit)
document.getElementById("formJadwal").addEventListener("submit", async function(e) {
  e.preventDefault();

  const formData = new FormData();
  formData.append("idJadwalMapel", document.getElementById("idJadwalMapel").value);
  formData.append("kodeMapel", document.getElementById("kodeMapel").value);
  formData.append("nipGuru", document.getElementById("nipGuru").value);
  formData.append("hari", document.getElementById("hari").value);
  formData.append("jamMulai", document.getElementById("jamMulai").value);
  formData.append("durasi", document.getElementById("durasi").value);
  formData.append("ruangan", document.getElementById("ruangan").value);
  formData.append("kelas", document.getElementById("kelas").value);

  const response = await fetch("backend/simpanJadwal.php", {
    method: "POST",
    body: formData
  });

  const result = await response.json();
  alert(result.message);

  if (result.success) location.reload();
});

// Hapus Jadwal
function hapusJadwal(id) {
  if (!confirm("Yakin ingin menghapus jadwal ini?")) return;

  fetch("backend/hapusJadwal.php?id=" + id)
    .then(res => res.json())
    .then(data => {
      alert(data.message);
      if (data.success) location.reload();
    });
}

// Filter hari
document.getElementById("filterHari").addEventListener("change", function() {
  let value = this.value.toLowerCase();
  document.querySelectorAll("#jadwalBody tr").forEach(tr => {
    let hari = tr.getAttribute("data-hari").toLowerCase();
    tr.style.display = value === "all" || hari === value ? "" : "none";
  });
});

// Search
document.getElementById("searchInput").addEventListener("keyup", function() {
  let keyword = this.value.toLowerCase();
  document.querySelectorAll("#jadwalBody tr").forEach(tr => {
    tr.style.display = tr.innerText.toLowerCase().includes(keyword) ? "" : "none";
  });
});
</script>

</body>
</html>
