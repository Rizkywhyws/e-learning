<?php
// Periksa status session sebelum memulainya
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once("../config/db.php");

// Cek apakah guru sudah login
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'guru') {
    echo json_encode(["status" => "error", "message" => "Anda harus login sebagai guru!"]);
    exit;
}

$nipGuru = $_SESSION['nip']; // Ambil NIP dari session

// === Fungsi generate ID unik (MT + random number 5 digit) ===
function generateIdMateri($conn) {
    do {
        $id = "MT" . rand(10000, 99999);
        $check = mysqli_query($conn, "SELECT idmateri FROM materi WHERE idmateri='$id'");
    } while (mysqli_num_rows($check) > 0);
    return $id;
}

// === PROSES UPLOAD FILE ===
function uploadFile($file) {
    // Folder tujuan upload (sejajar dengan folder Guru/)
    $targetDir = "../uploads/";
    
    // Buat folder jika belum ada
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // Cek apakah ada file yang diupload
    if (!isset($file) || $file['error'] == UPLOAD_ERR_NO_FILE) {
        return null; // Tidak ada file
    }
    
    // Validasi error upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ["status" => "error", "message" => "Error saat upload file"];
    }
    
    // Validasi tipe file (hanya PDF)
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($fileType != "pdf") {
        return ["status" => "error", "message" => "Hanya file PDF yang diperbolehkan"];
    }
    
    // Validasi ukuran file (max 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        return ["status" => "error", "message" => "Ukuran file maksimal 10MB"];
    }
    
    // Generate nama file unik
    $fileName = uniqid() . '_' . basename($file['name']);
    $targetFile = $targetDir . $fileName;
    
    // Pindahkan file ke folder tujuan
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        // Return path relatif untuk database (dari folder e-learning)
        return "uploads/" . $fileName;
    } else {
        return ["status" => "error", "message" => "Gagal memindahkan file"];
    }
}

// === PROSES REQUEST ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- TAMBAH DATA ---
    if ($action === 'add') {
        $idmateri = generateIdMateri($conn);
        $kodeMapel = mysqli_real_escape_string($conn, $_POST['kodeMapel']);
        $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
        $judul = mysqli_real_escape_string($conn, $_POST['judul']);
        $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
        $linkVideo = mysqli_real_escape_string($conn, $_POST['link_video']);
        $createdAt = date('Y-m-d H:i:s');
        
        // Upload file PDF
        $filePath = null;
        if (isset($_FILES['file_pdf'])) {
            $uploadResult = uploadFile($_FILES['file_pdf']);
            
            if (is_array($uploadResult) && isset($uploadResult['status']) && $uploadResult['status'] == 'error') {
                echo json_encode($uploadResult);
                exit;
            }
            $filePath = $uploadResult;
        }

        // Insert ke database
        $sql = "INSERT INTO materi (idmateri, kodeMapel, kelas, NIP, judul, deskripsi, filePath, linkVideo, createdAt)
                VALUES ('$idmateri', '$kodeMapel', '$kelas', '$nipGuru', '$judul', '$deskripsi', " . 
                ($filePath ? "'$filePath'" : "NULL") . ", " . 
                ($linkVideo ? "'$linkVideo'" : "NULL") . ", '$createdAt')";

        if (mysqli_query($conn, $sql)) {
            echo json_encode([
                "status" => "success", 
                "idmateri" => $idmateri,
                "kelas" => $kelas,
                "filePath" => $filePath,
                "createdAt" => $createdAt
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        }
        exit;
    }

    // --- UPDATE DATA ---
    if ($action === 'update') {
        $idmateri = mysqli_real_escape_string($conn, $_POST['idmateri']);
        $kodeMapel = mysqli_real_escape_string($conn, $_POST['kodeMapel']);
        $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
        $judul = mysqli_real_escape_string($conn, $_POST['judul']);
        $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
        $linkVideo = mysqli_real_escape_string($conn, $_POST['link_video']);
        
        // Ambil data lama untuk cek file lama
        $oldData = mysqli_query($conn, "SELECT filePath FROM materi WHERE idmateri='$idmateri' AND NIP='$nipGuru'");
        $oldRow = mysqli_fetch_assoc($oldData);
        $filePath = $oldRow['filePath'];
        
        // Upload file baru jika ada
        if (isset($_FILES['file_pdf']) && $_FILES['file_pdf']['error'] != UPLOAD_ERR_NO_FILE) {
            $uploadResult = uploadFile($_FILES['file_pdf']);
            
            if (is_array($uploadResult) && isset($uploadResult['status']) && $uploadResult['status'] == 'error') {
                echo json_encode($uploadResult);
                exit;
            }
            
            // Hapus file lama jika ada
            if ($filePath && file_exists("../" . $filePath)) {
                unlink("../" . $filePath);
            }
            
            $filePath = $uploadResult;
        }

        // Update database
        $sql = "UPDATE materi 
                SET kodeMapel='$kodeMapel', kelas='$kelas', NIP='$nipGuru', judul='$judul', deskripsi='$deskripsi',
                    filePath=" . ($filePath ? "'$filePath'" : "NULL") . ",
                    linkVideo=" . ($linkVideo ? "'$linkVideo'" : "NULL") . "
                WHERE idmateri='$idmateri' AND NIP='$nipGuru'";

        if (mysqli_query($conn, $sql)) {
            echo json_encode(["status" => "success", "filePath" => $filePath]);
        } else {
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        }
        exit;
    }

    // --- DELETE DATA ---
    if ($action === 'delete') {
        $idmateri = mysqli_real_escape_string($conn, $_POST['idmateri']);

        // Ambil data file
        $getData = mysqli_query($conn, "SELECT filePath FROM materi WHERE idmateri='$idmateri' AND NIP='$nipGuru'");
        $dataRow = mysqli_fetch_assoc($getData);
        
        // Cek apakah materi digunakan di tabel tugas
        $cek = mysqli_query($conn, "SELECT idTugas FROM tugas WHERE idMateri = '$idmateri'");

        if (mysqli_num_rows($cek) > 0) {
            // Hapus file fisik
            if ($dataRow['filePath'] && file_exists("../" . $dataRow['filePath'])) {
                unlink("../" . $dataRow['filePath']);
            }
            
            // Update: set NULL untuk filePath dan linkVideo
            $sql = "UPDATE materi
                    SET filePath = NULL, linkVideo = NULL
                    WHERE idmateri = '$idmateri' AND NIP='$nipGuru'";

            if (mysqli_query($conn, $sql)) {
                echo json_encode([
                    "status" => "warning",
                    "message" => "Materi dipakai di tugas → hanya file & video dihapus"
                ]);
            } else {
                echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
            }
        } else {
            // Hapus file fisik
            if ($dataRow['filePath'] && file_exists("../" . $dataRow['filePath'])) {
                unlink("../" . $dataRow['filePath']);
            }
            
            // Delete row
            $sql = "DELETE FROM materi WHERE idmateri='$idmateri' AND NIP='$nipGuru'";
            if (mysqli_query($conn, $sql)) {
                echo json_encode(["status" => "success"]);
            } else {
                echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
            }
        }
        exit;
    }
}

// === LOAD DATA EXISTING ===
if (isset($_GET['load_data'])) {
    $query = "SELECT m.idmateri, m.kodeMapel, m.kelas, m.judul, m.deskripsi, m.filePath, m.linkVideo, m.createdAt,
                     mp.namaMapel
              FROM materi m
              JOIN mapel mp ON m.kodeMapel = mp.kodeMapel
              WHERE m.NIP = '$nipGuru'
              ORDER BY m.createdAt DESC";
    
    $result = mysqli_query($conn, $query);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    echo json_encode($data);
    exit;
}

// Ambil mapel dan kelas yang diampu guru ini dari jadwalmapel
$mapelList = [];
$kelasList = [];

$queryMapel = "SELECT DISTINCT m.kodeMapel, m.namaMapel 
               FROM jadwalmapel jm
               JOIN mapel m ON jm.kodeMapel = m.kodeMapel
               WHERE jm.nipGuru = '$nipGuru'
               ORDER BY m.namaMapel ASC";
$result = mysqli_query($conn, $queryMapel);
while ($row = mysqli_fetch_assoc($result)) $mapelList[] = $row;

$queryKelas = "SELECT DISTINCT kelas 
               FROM jadwalmapel 
               WHERE nipGuru = '$nipGuru'
               ORDER BY kelas ASC";
$result2 = mysqli_query($conn, $queryKelas);
while ($row = mysqli_fetch_assoc($result2)) $kelasList[] = $row;
?>

<link rel="stylesheet" href="CSS/uploudMateri.css?v=<?php echo time(); ?>">

<div class="form-container">
  <h2>Upload Materi</h2>

  <form id="materiForm">
    
    <!-- Pilih Mapel -->
    <label for="mapel">Mata Pelajaran</label>
    <select id="mapel" required>
      <option value="">-- Pilih Mata Pelajaran --</option>
      <?php foreach ($mapelList as $mapel): ?>
        <option value="<?= htmlspecialchars($mapel['kodeMapel']) ?>">
          <?= htmlspecialchars($mapel['namaMapel']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <!-- Pilih Kelas -->
    <label for="kelas">Kelas</label>
    <select id="kelas" required>
      <option value="">-- Pilih Kelas --</option>
      <?php foreach ($kelasList as $kelas): ?>
        <option value="<?= htmlspecialchars($kelas['kelas']) ?>">
          <?= htmlspecialchars($kelas['kelas']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <!-- Judul Materi -->
    <label for="judul">Judul Materi</label>
    <input type="text" id="judul" placeholder="Masukkan judul materi..." required>

    <!-- Deskripsi -->
    <label for="deskripsi">Deskripsi</label>
    <textarea id="deskripsi" placeholder="Tuliskan deskripsi materi..." required></textarea>

    <!-- Link Video -->
    <label for="link_video">Link Video</label>
    <input type="url" id="link_video" placeholder="https://contoh.com/video">

    <!-- Upload File (PDF) -->
    <label for="file_pdf">Upload File (PDF)</label>
    <input type="file" id="file_pdf" accept=".pdf">

    <div class="button-group">
      <button type="button" id="addRow" class="save-btn">Tambah</button>
      <button type="button" id="cancelEdit" class="cancel-btn" style="display:none;">Batal</button>
    </div>
  </form>

  <!-- Search Box -->
  <div class="search-container">
    <input type="text" id="searchMapel" placeholder="Cari Mata Pelajaran...">
    <input type="text" id="searchKelas" placeholder="Cari Kelas...">
  </div>

  <!-- Header Tabel -->
  <h3 id="tableHeader">Rekap Materi</h3>

  <!-- Wrapper untuk tabel responsive -->
  <div class="table-wrapper">
    <table id="dataTable">
      <thead>
        <tr>
          <th>Kelas</th>
          <th>Mapel</th>
          <th>Judul</th>
          <th>Deskripsi</th>
          <th>Link Video</th>
          <th>File PDF</th>
          <th>Tanggal</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<script>
let dataList = [];
let editIndex = -1;

// Load data saat halaman pertama kali dibuka
window.addEventListener('DOMContentLoaded', () => {
    loadExistingData();
});

// Fungsi untuk load data yang sudah ada
async function loadExistingData() {
    try {
        let res = await fetch("uploudMateri.php?load_data=1&t=" + Date.now());
        let data = await res.json();
        
        dataList = data.map(item => ({
            idmateri: item.idmateri,
            kelas: item.kelas,
            kodeMapel: item.kodeMapel,
            namaMapel: item.namaMapel,
            judul: item.judul,
            deskripsi: item.deskripsi,
            link_video: item.linkVideo || '',
            file_pdf: item.filePath || '',
            created_at: item.createdAt
        }));
        
        renderTable();
    } catch (error) {
        console.error("Error loading data:", error);
    }
}

// Event listener untuk search
document.getElementById("searchMapel").addEventListener("input", renderTable);
document.getElementById("searchKelas").addEventListener("input", renderTable);

document.getElementById("addRow").addEventListener("click", async function() {
    let kelas = document.getElementById("kelas").value;
    let mapelSelect = document.getElementById("mapel");
    let kodeMapel = mapelSelect.value;
    let namaMapel = mapelSelect.options[mapelSelect.selectedIndex]?.text || "";
    let judul = document.getElementById("judul").value;
    let deskripsi = document.getElementById("deskripsi").value;
    let link = document.getElementById("link_video").value;
    let fileInput = document.getElementById("file_pdf");
    let file = fileInput.files[0];

    if (!kelas || !kodeMapel || !judul || !deskripsi) {
        alert("Lengkapi field Kelas, Mapel, Judul, dan Deskripsi terlebih dahulu!");
        return;
    }

    // Buat FormData untuk kirim file
    let formData = new FormData();
    
    // === TAMBAH ===
    if (editIndex === -1) {
        formData.append("action", "add");
        formData.append("kodeMapel", kodeMapel);
        formData.append("kelas", kelas);
        formData.append("judul", judul);
        formData.append("deskripsi", deskripsi);
        formData.append("link_video", link);
        
        if (file) {
            formData.append("file_pdf", file);
        }
        
        let res = await fetch("uploudMateri.php?t=" + Date.now(), { 
            method: "POST", 
            body: formData 
        });
        let result = await res.json();

        if (result.status === "success") {
            let newData = {
                idmateri: result.idmateri,
                kelas: kelas,
                kodeMapel: kodeMapel,
                namaMapel: namaMapel,
                judul: judul,
                deskripsi: deskripsi,
                link_video: link,
                file_pdf: result.filePath || '',
                created_at: result.createdAt
            };
            
            dataList.unshift(newData); // Tambahkan di awal array
            renderTable();
            document.getElementById("materiForm").reset();
            alert("Data berhasil disimpan!");
        } else {
            alert("Gagal menambah data: " + result.message);
        }
    } else {
        // === UPDATE ===
        let item = dataList[editIndex];
        
        formData.append("action", "update");
        formData.append("idmateri", item.idmateri);
        formData.append("kodeMapel", kodeMapel);
        formData.append("kelas", kelas);
        formData.append("judul", judul);
        formData.append("deskripsi", deskripsi);
        formData.append("link_video", link);
        
        if (file) {
            formData.append("file_pdf", file);
        }

        let res = await fetch("uploudMateri.php?t=" + Date.now(), { 
            method: "POST", 
            body: formData 
        });
        let result = await res.json();

        if (result.status === "success") {
            dataList[editIndex].kelas = kelas;
            dataList[editIndex].kodeMapel = kodeMapel;
            dataList[editIndex].namaMapel = namaMapel;
            dataList[editIndex].judul = judul;
            dataList[editIndex].deskripsi = deskripsi;
            dataList[editIndex].link_video = link;
            
            if (result.filePath) {
                dataList[editIndex].file_pdf = result.filePath;
            }
            
            editIndex = -1;
            renderTable();
            document.getElementById("materiForm").reset();
            document.getElementById("addRow").textContent = "Tambah";
            document.getElementById("cancelEdit").style.display = "none";
            alert("Data berhasil diupdate!");
        } else {
            alert("Gagal update: " + result.message);
        }
    }
});

// Tombol Batal Edit
document.getElementById("cancelEdit").addEventListener("click", function() {
    editIndex = -1;
    document.getElementById("materiForm").reset();
    document.getElementById("addRow").textContent = "Tambah";
    document.getElementById("cancelEdit").style.display = "none";
});

function renderTable() {
    const table = document.getElementById("dataTable");
    const tbody = table.querySelector("tbody");
    tbody.innerHTML = "";
    
    // Ambil nilai search
    const searchMapel = document.getElementById("searchMapel").value.toLowerCase();
    const searchKelas = document.getElementById("searchKelas").value.toLowerCase();
    
    // Filter data berdasarkan search
    const filteredData = dataList.filter(item => {
        const matchMapel = item.namaMapel.toLowerCase().includes(searchMapel);
        const matchKelas = item.kelas.toLowerCase().includes(searchKelas);
        return matchMapel && matchKelas;
    });
    
    // Jika tidak ada data setelah filter, tampilkan pesan
    if (filteredData.length === 0) {
        const row = document.createElement("tr");
        row.innerHTML = `<td colspan="8" style="text-align: center; padding: 20px; color: #999;">Tidak ada data materi</td>`;
        tbody.appendChild(row);
        return;
    }
    
    filteredData.forEach((item, index) => {
        // Cari index asli di dataList
        const originalIndex = dataList.indexOf(item);
        
        const row = document.createElement("tr");
        
        // Format nama file untuk tampilan
        let fileName = item.file_pdf ? item.file_pdf.split('/').pop() : '-';
        
        // Buat link untuk buka file (path dari root e-learning)
        let fileLink = item.file_pdf 
            ? `<a href="../${item.file_pdf}" target="_blank" style="color: #007bff; text-decoration: underline;">${fileName}</a>`
            : '-';
        
        // Format link video
        let videoLink = item.link_video 
            ? `<a href="${item.link_video}" target="_blank" style="color: #007bff; text-decoration: underline;">Lihat Video</a>`
            : '-';
        
        row.innerHTML = `
            <td>${item.kelas || '-'}</td>
            <td>${item.namaMapel}</td>
            <td>${item.judul}</td>
            <td>${item.deskripsi}</td>
            <td>${videoLink}</td>
            <td>${fileLink}</td>
            <td>${new Date(item.created_at).toLocaleString('id-ID')}</td>
            <td>
                <button type="button" class="edit-btn" onclick="editRow(${originalIndex})">Edit</button>
                <button type="button" class="delete-btn" onclick="deleteRow(${originalIndex})">Hapus</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function editRow(index) {
    const item = dataList[index];
    document.getElementById("kelas").value = item.kelas;
    document.getElementById("mapel").value = item.kodeMapel;
    document.getElementById("judul").value = item.judul;
    document.getElementById("deskripsi").value = item.deskripsi;
    document.getElementById("link_video").value = item.link_video;

    editIndex = index;
    document.getElementById("addRow").textContent = "Update";
    document.getElementById("cancelEdit").style.display = "inline-block";
    
    // Scroll ke form
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function deleteRow(index) {
    if (!confirm("Yakin ingin menghapus materi ini?")) return;

    const item = dataList[index];
    let formData = new FormData();
    formData.append("action", "delete");
    formData.append("idmateri", item.idmateri);

    let res = await fetch("uploudMateri.php?t=" + Date.now(), { 
        method: "POST", 
        body: formData 
    });
    let result = await res.json();

    if (result.status === "success") {
        dataList.splice(index, 1);
        renderTable();
        alert("Berhasil hapus materi");
    } else if (result.status === "warning") {
        // filePath + linkVideo jadi NULL
        dataList[index].file_pdf = "";
        dataList[index].link_video = "";
        renderTable();
        alert(result.message);
    } else {
        alert("Gagal hapus: " + result.message);
    }
}
</script>