<?php
//file buatTugas.php
include "../config/session.php";

// Cek apakah user sudah login dan merupakan guru
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'guru') {
    header('Location: ../Auth/login.php');
    exit;
}
require_once "../config/db.php";
//include "../config/db.php";

// ==================== AMBIL DATA DARI SESSION LOGIN ====================
$idAkun = $_SESSION['user_id']; // Gunakan user_id dari login
$nipGuru = isset($_SESSION['nip']) ? $_SESSION['nip'] : '';

// Jika NIP tidak ada di session, ambil dari database
if (empty($nipGuru)) {
    $queryGuru = mysqli_query($conn, "SELECT NIP FROM dataguru WHERE idAkun = '$idAkun'");
    $dataGuru = mysqli_fetch_assoc($queryGuru);
    $nipGuru = isset($dataGuru['NIP']) ? $dataGuru['NIP'] : '';
    $_SESSION['nip'] = $nipGuru; // Simpan ke session untuk request berikutnya
}


// ==================== FUNGSI BUAT ID OTOMATIS ====================
function generateIdTugas($conn) {
    $query = mysqli_query($conn, "SELECT idTugas FROM tugas ORDER BY idTugas DESC LIMIT 1");
    if (mysqli_num_rows($query) > 0) {
        $lastId = mysqli_fetch_assoc($query)['idTugas'];
        $num = (int) substr($lastId, 3) + 1;
        return "TGS" . str_pad($num, 4, "0", STR_PAD_LEFT);
    } else {
        return "TGS0001";
    }
}

// Fungsi generate random ID untuk materi
function generateRandomIdMateri($conn) {
    do {
        $id = "MT" . rand(10000, 99999);
        $check = mysqli_query($conn, "SELECT idmateri FROM materi WHERE idmateri='$id'");
    } while (mysqli_num_rows($check) > 0);
    return $id;
}

// ==================== HANDLE AJAX GET MATERI ====================
if (isset($_GET['action']) && $_GET['action'] === 'getMateri' && isset($_GET['kodeMapel'])) {
    $kodeMapel = mysqli_real_escape_string($conn, $_GET['kodeMapel']);
    
    $qMateri = mysqli_query($conn, "
        SELECT idMateri, judul 
        FROM materi 
        WHERE kodeMapel = '$kodeMapel' AND NIP = '$nipGuru'
        ORDER BY createdAt DESC
    ");
    
    $data = [];
    while ($m = mysqli_fetch_assoc($qMateri)) {
        $data[] = $m;
    }
    
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ==================== HANDLE UPDATE (AJAX) ====================
if (isset($_POST['action']) && $_POST['action'] === 'update' && isset($_POST['idTugas'])) {
    $id = mysqli_real_escape_string($conn, $_POST['idTugas']);
    $kodeMapel = mysqli_real_escape_string($conn, $_POST['mapel']);
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $deadline = mysqli_real_escape_string($conn, $_POST['deadline']);

    // Handle file upload untuk edit
    $filePath = "";
    if (!empty($_FILES['file']['name'])) {
        $uploadDir = "../../uploads/tugas/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = basename($_FILES['file']['name']);
        $targetFile = $uploadDir . time() . "_" . $fileName;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
            $filePath = $targetFile;
            // Update dengan file baru
            $update = mysqli_query($conn, "
                UPDATE tugas 
                SET kodeMapel='$kodeMapel', judul='$judul', deskripsi='$deskripsi', 
                    deadline='$deadline', filePath='$filePath' 
                WHERE idTugas='$id'
            ");
        }
    } else {
        // Update tanpa ubah file
        $update = mysqli_query($conn, "
            UPDATE tugas 
            SET kodeMapel='$kodeMapel', judul='$judul', deskripsi='$deskripsi', deadline='$deadline' 
            WHERE idTugas='$id'
        ");
    }

    if ($update) {
        echo "✅ Tugas berhasil diupdate!";
    } else {
        echo "❌ Gagal mengupdate tugas: " . mysqli_error($conn);
    }
    exit;
}

// ==================== HANDLE DELETE (AJAX) ====================
if (isset($_POST['action']) && $_POST['action'] === 'hapus' && isset($_POST['idTugas'])) {
    $id = mysqli_real_escape_string($conn, $_POST['idTugas']);
    
    // Ambil data tugas termasuk idMateri
    $qTugas = mysqli_query($conn, "SELECT filePath, idMateri FROM tugas WHERE idTugas='$id'");
    if (!$qTugas || mysqli_num_rows($qTugas) == 0) {
        echo "❌ Tugas tidak ditemukan!";
        exit;
    }
    
    $tugasData = mysqli_fetch_assoc($qTugas);
    $idMateri = $tugasData['idMateri'];
    
    // Hapus file tugas jika ada
    if (!empty($tugasData['filePath']) && file_exists($tugasData['filePath'])) {
        unlink($tugasData['filePath']);
    }
    
    // Hapus tugas dari database
    $delete = mysqli_query($conn, "DELETE FROM tugas WHERE idTugas='$id'");

    if ($delete) {
        $message = "✅ Tugas berhasil dihapus!";
        
        // Cek apakah perlu hapus materi juga
        if (!empty($idMateri)) {
            // Cek kolom filePath dan linkVideo di tabel materi
            $qMateri = mysqli_query($conn, "SELECT filePath, linkVideo FROM materi WHERE idMateri='$idMateri'");
            
            if ($qMateri && mysqli_num_rows($qMateri) > 0) {
                $materiData = mysqli_fetch_assoc($qMateri);
                $materiFilePath = $materiData['filePath'];
                $materiLinkVideo = $materiData['linkVideo'];
                
                // Jika kedua kolom kosong/null, hapus materi
                if ((empty($materiFilePath) || is_null($materiFilePath)) && 
                    (empty($materiLinkVideo) || is_null($materiLinkVideo))) {
                    
                    $deleteMateri = mysqli_query($conn, "DELETE FROM materi WHERE idMateri='$idMateri'");
                    
                    if ($deleteMateri) {
                        $message .= " Materi terkait juga dihapus karena tidak memiliki file atau video.";
                    } else {
                        $message .= " ⚠️ Namun gagal menghapus materi: " . mysqli_error($conn);
                    }
                } else {
                    $message .= " Materi terkait tetap tersimpan karena masih memiliki file/video.";
                }
            }
        }
        
        echo $message;
    } else {
        echo "❌ Gagal menghapus tugas: " . mysqli_error($conn);
    }
    exit;
}

// ==================== JIKA FORM DISUBMIT ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $kodeMapel = mysqli_real_escape_string($conn, $_POST['mapel']);
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $deadline = mysqli_real_escape_string($conn, $_POST['deadline']);
    $createdAt = date("Y-m-d H:i:s");
    $materiOption = mysqli_real_escape_string($conn, $_POST['materi']);

    // Upload file (optional)
    $filePath = "";
    if (!empty($_FILES['file']['name'])) {
        $uploadDir = "../../uploads/tugas/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = basename($_FILES['file']['name']);
        $targetFile = $uploadDir . time() . "_" . $fileName;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
            $filePath = $targetFile;
        }
    }

    // Cek apakah user memilih "buat materi baru"
    $idMateri = "";
    if ($materiOption === "new") {
        // Buat materi baru
        $idMateri = generateRandomIdMateri($conn);
        
        // Insert ke tabel materi
        $sqlMateri = "INSERT INTO materi (idMateri, kodeMapel, NIP, judul, createdAt)
                      VALUES ('$idMateri', '$kodeMapel', '$nipGuru', '$judul', '$createdAt')";
        
        $resultMateri = mysqli_query($conn, $sqlMateri);
        
        if (!$resultMateri) {
            echo "<script>alert('❌ Gagal menyimpan materi: " . mysqli_error($conn) . "');</script>";
            exit;
        }
    } else {
        // Gunakan materi yang sudah ada
        $idMateri = $materiOption;
    }

    // Generate ID Tugas
    $idTugas = generateIdTugas($conn);

    // Insert data ke tabel tugas dengan idMateri
    $sql = "INSERT INTO tugas (idTugas, kodeMapel, NIP, judul, deskripsi, deadline, filePath, createdAt, idMateri)
            VALUES ('$idTugas', '$kodeMapel', '$nipGuru', '$judul', '$deskripsi', '$deadline', '$filePath', '$createdAt', '$idMateri')";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        $message = "✅ Tugas berhasil disimpan!";
        if ($materiOption === "new") {
            $message .= " Materi baru juga telah dibuat.";
        }
        echo "<script>alert('$message'); window.location='pengelolaanPembelajaran.php';</script>";
    } else {
        echo "<script>alert('❌ Gagal menyimpan tugas: " . mysqli_error($conn) . "');</script>";
    }
}

// ==================== AMBIL DATA MATA PELAJARAN YANG DIAMPU ====================
$mapelQuery = mysqli_query($conn, "
    SELECT DISTINCT m.kodeMapel, m.namaMapel
    FROM gurumapel g
    JOIN mapel m ON g.kodeMapel = m.kodeMapel
    WHERE g.nipGuru = '$nipGuru'
");
?>

<link rel="stylesheet" href="css/buatTugas.css?v=1.3">

<div class="form-container">
    <h2>Tambah / Buat Tugas</h2>

    <form action="" method="POST" enctype="multipart/form-data">

        <!-- Dropdown Mata Pelajaran -->
        <label for="mapel">Mata Pelajaran</label>
        <select name="mapel" id="mapel" required>
            <option value="">-- Pilih Mata Pelajaran --</option>
            <?php while ($m = mysqli_fetch_assoc($mapelQuery)) { ?>
                <option value="<?= $m['kodeMapel']; ?>"><?= $m['namaMapel']; ?></option>
            <?php } ?>
        </select>

        <!-- Dropdown Kelas -->
        <div id="kelasContainer" style="display:none;">
            <label for="kelas">Kelas</label>
            <select name="kelas" id="kelas">
                <option value="">-- Pilih Kelas --</option>
            </select>
        </div>

        <!-- Input tambahan -->
        <div id="inputLain" style="display:none;">
            <!-- Dropdown Materi -->
            <label for="materi">Materi Terkait</label>
            <select name="materi" id="materi" required>
                <option value="">-- Pilih Materi --</option>
                <option value="new" style="font-weight: 600; color: #4c6ef5;">➕ Jadikan Tugas Sebagai Materi Baru</option>
            </select>

            <label for="judul">Judul Tugas</label>
            <input type="text" id="judul" name="judul" placeholder="Masukkan judul tugas" required>

            <label for="deskripsi">Deskripsi Tugas</label>
            <textarea id="deskripsi" name="deskripsi" placeholder="Tuliskan deskripsi tugas..." required></textarea>

            <label for="deadline">Deadline Tugas</label>
            <input type="datetime-local" id="deadline" name="deadline" required>

            <div class="upload-box">
                <label for="file">Upload File (opsional)</label><br>
                <input type="file" id="file" name="file">
            </div>

            <br>
            <button type="submit" class="save-btn">💾 Simpan</button>
        </div>
    </form>
</div>

<!-- === MODAL EDIT TUGAS === -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>✏️ Edit Tugas</h2>
        
        <form id="editForm" enctype="multipart/form-data">
            <input type="hidden" id="edit_idTugas" name="idTugas">
            
            <label for="edit_mapel">Mata Pelajaran</label>
            <select id="edit_mapel" name="mapel" required>
                <option value="">-- Pilih Mata Pelajaran --</option>
                <?php 
                mysqli_data_seek($mapelQuery, 0); // reset pointer
                while ($m = mysqli_fetch_assoc($mapelQuery)) { ?>
                    <option value="<?= $m['kodeMapel']; ?>"><?= $m['namaMapel']; ?></option>
                <?php } ?>
            </select>

            <label for="edit_kelas">Kelas</label>
            <input type="text" id="edit_kelas" readonly style="background-color: #f0f0f0;">

            <label for="edit_judul">Judul Tugas</label>
            <input type="text" id="edit_judul" name="judul" required>

            <label for="edit_deskripsi">Deskripsi Tugas</label>
            <textarea id="edit_deskripsi" name="deskripsi" required></textarea>

            <label for="edit_deadline">Deadline Tugas</label>
            <input type="datetime-local" id="edit_deadline" name="deadline" required>

            <!-- File saat ini -->
            <div id="currentFileInfo" style="margin-bottom: 10px;"></div>

            <!-- Upload file baru -->
            <div class="upload-box">
                <label for="edit_file">Upload File Baru (opsional)</label><br>
                <input type="file" id="edit_file" name="file">
            </div>

            <br>
            <button type="submit" class="save-btn">💾 Update Tugas</button>
        </form>
    </div>
</div>

<!-- === Tabel Daftar seluruh tugas di mata pelajaran itu === -->
<div class="wide-section">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <h3 style="margin:0;">Daftar Tugas yang Telah Dibuat</h3>
        
        <!-- Dropdown Filter -->
        <div style="display:flex; gap:10px; align-items:center;">
            <select id="filterMapel" style="padding:8px 12px; border-radius:8px; border:1px solid #ccc;">
                <option value="">-- Pilih Mapel --</option>
                <?php
                // Ambil daftar mapel yang diampu guru
                mysqli_data_seek($mapelQuery, 0);
                while ($m = mysqli_fetch_assoc($mapelQuery)) {
                    echo "<option value='{$m['kodeMapel']}'>{$m['namaMapel']}</option>";
                }
                ?>
            </select>

            <select id="filterKelas" style="padding:8px 12px; border-radius:8px; border:1px solid #ccc; display:none;">
                <option value="">-- Pilih Kelas --</option>
            </select>
        </div>
    </div>

    <div class="table-container">
        <table class="koreksi-table" id="tugasTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Judul Tugas</th>
                    <th>Deskripsi</th>
                    <th>Deadline</th>
                    <th>File</th>
                    <th>Dibuat Pada</th>
                </tr>
            </thead>
            <tbody>
                <!-- Akan diisi lewat JavaScript -->
            </tbody>
        </table>
    </div>

    <!-- tombol aksi untuk update/delete tugas yang sudah dibuat -->
    <div style="margin-top: 20px;">
        <button type="button" class="edit-btn">✏️ Edit Tugas</button>
        <button type="button" class="delete-btn">🗑️ Hapus Tugas</button>
    </div>
</div>

<script>
// === FILTER MAPEL DAN KELAS ===
const filterMapel = document.getElementById("filterMapel");
const filterKelas = document.getElementById("filterKelas");
const tugasTableBody = document.querySelector("#tugasTable tbody");

// Saat mapel dipilih → ambil kelas
filterMapel.addEventListener("change", () => {
    const kodeMapel = filterMapel.value;
    tugasTableBody.innerHTML = "";

    if (kodeMapel) {
        fetch(`backend/getKelas.php?kodeMapel=${kodeMapel}`)
            .then(res => res.json())
            .then(data => {
                filterKelas.style.display = "inline-block";
                filterKelas.innerHTML = '<option value="">-- Pilih Kelas --</option>';
                data.forEach(k => {
                    const opt = document.createElement("option");
                    opt.value = k.kelas;
                    opt.textContent = k.kelas;
                    filterKelas.appendChild(opt);
                });
            });
    } else {
        filterKelas.style.display = "none";
        filterKelas.innerHTML = '<option value="">-- Pilih Kelas --</option>';
    }
});

// Saat kelas dipilih → tampilkan tabel tugas
filterKelas.addEventListener("change", () => {
    const kodeMapel = filterMapel.value;
    const kelas = filterKelas.value;
    tugasTableBody.innerHTML = "";

    if (kodeMapel && kelas) {
        fetch(`backend/getTugasGuru.php?mapel=${kodeMapel}&kelas=${kelas}`)
            .then(res => res.text())
            .then(html => {
                tugasTableBody.innerHTML = html;
            });
    } else {
        tugasTableBody.innerHTML = "";
    }
});

// === PILIH TUGAS & AKSI TOMBOL ===
let selectedRow = null;
let selectedTugas = null;
let isLoading = false;

// Delegasi klik pada TABEL
document.getElementById("tugasTable").addEventListener("click", (e) => {
    const row = e.target.closest("tbody tr");
    if (!row) return;

    // Reset selection
    document.querySelectorAll(".koreksi-table tbody tr").forEach(r => {
        r.classList.remove("selected-row");
        r.classList.remove("loading-row");
    });
    
    row.classList.add("selected-row");
    row.classList.add("loading-row");

    const idTugas = row.getAttribute("data-id");
    selectedRow = row;
    selectedTugas = null;
    isLoading = true;

    // Disable tombol sementara
    document.querySelector(".edit-btn").disabled = true;
    document.querySelector(".delete-btn").disabled = true;

    // Ambil detail tugas dari database
    fetch(`backend/getDetailTugas.php?idTugas=${idTugas}`)
        .then(res => res.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                
                if (data.error) {
                    alert("⚠️ " + data.error);
                    row.classList.remove("loading-row");
                    row.classList.remove("selected-row");
                    isLoading = false;
                    document.querySelector(".edit-btn").disabled = false;
                    document.querySelector(".delete-btn").disabled = false;
                    return;
                }
                
                selectedTugas = data;
                row.classList.remove("loading-row");
                isLoading = false;
                
                document.querySelector(".edit-btn").disabled = false;
                document.querySelector(".delete-btn").disabled = false;
                
            } catch (e) {
                console.error("❌ JSON Parse Error:", e);
                alert("❌ Server mengembalikan response yang tidak valid.");
                row.classList.remove("loading-row");
                row.classList.remove("selected-row");
                isLoading = false;
                document.querySelector(".edit-btn").disabled = false;
                document.querySelector(".delete-btn").disabled = false;
            }
        })
        .catch(err => {
            console.error("❌ Fetch Error:", err);
            alert("❌ Gagal memuat data tugas: " + err);
            row.classList.remove("loading-row");
            row.classList.remove("selected-row");
            isLoading = false;
            document.querySelector(".edit-btn").disabled = false;
            document.querySelector(".delete-btn").disabled = false;
        });
});

// === MODAL EDIT ===
const modal = document.getElementById("editModal");
const closeModal = document.querySelector(".close");

// TOMBOL EDIT - Buka Modal
document.querySelector(".edit-btn").addEventListener("click", () => {
    if (isLoading) {
        alert("⏳ Sedang memuat data, tunggu sebentar...");
        return;
    }
    
    if (!selectedTugas) {
        alert("⚠️ Pilih tugas dulu sebelum edit!");
        return;
    }

    // Isi data ke modal
    document.getElementById("edit_idTugas").value = selectedTugas.idTugas;
    document.getElementById("edit_mapel").value = selectedTugas.kodeMapel;
    document.getElementById("edit_kelas").value = selectedTugas.kelas;
    document.getElementById("edit_judul").value = selectedTugas.judul;
    document.getElementById("edit_deskripsi").value = selectedTugas.deskripsi;
    document.getElementById("edit_deadline").value = selectedTugas.deadline;

    // Tampilkan file yang ada
    const fileInfo = document.getElementById("currentFileInfo");
    if (selectedTugas.filePath && selectedTugas.filePath !== '') {
        const fileName = selectedTugas.filePath.split('/').pop();
        fileInfo.innerHTML = `
            <div class="file-info">
                📎 <strong>File saat ini:</strong> 
                <a href="${selectedTugas.filePath}" target="_blank" class="file-link">${fileName}</a>
            </div>
        `;
    } else {
        fileInfo.innerHTML = '<p style="color: #999; font-size: 14px;">📌 Belum ada file yang diupload</p>';
    }

    // Tampilkan modal
    modal.style.display = "block";
});

// Close modal
closeModal.onclick = () => modal.style.display = "none";
window.onclick = (e) => {
    if (e.target == modal) modal.style.display = "none";
}

// SUBMIT EDIT FORM
document.getElementById("editForm").addEventListener("submit", (e) => {
    e.preventDefault();

    const formData = new FormData();
    formData.append("action", "update");
    formData.append("idTugas", document.getElementById("edit_idTugas").value);
    formData.append("mapel", document.getElementById("edit_mapel").value);
    formData.append("judul", document.getElementById("edit_judul").value);
    formData.append("deskripsi", document.getElementById("edit_deskripsi").value);
    formData.append("deadline", document.getElementById("edit_deadline").value);
    
    // Tambahkan file jika ada
    const fileInput = document.getElementById("edit_file");
    if (fileInput.files.length > 0) {
        formData.append("file", fileInput.files[0]);
    }

    fetch("buatTugas.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.text())
    .then(msg => {
        alert(msg);
        modal.style.display = "none";
        
        // Refresh tabel
        const kodeMapel = filterMapel.value;
        const kelas = filterKelas.value;
        if (kodeMapel && kelas) {
            fetch(`backend/getTugasGuru.php?mapel=${kodeMapel}&kelas=${kelas}`)
                .then(res => res.text())
                .then(html => {
                    tugasTableBody.innerHTML = html;
                    selectedRow = null;
                    selectedTugas = null;
                });
        }
    })
    .catch(err => alert("❌ Gagal: " + err));
});

// === TOMBOL DELETE ===
document.querySelector(".delete-btn").addEventListener("click", () => {
    if (isLoading) {
        alert("⏳ Sedang memuat data, tunggu sebentar...");
        return;
    }
    
    if (!selectedTugas) {
        alert("⚠️ Pilih tugas dulu sebelum hapus!");
        return;
    }

    const yakin = confirm(`🗑️ Yakin mau hapus "${selectedTugas.judul}"?`);
    if (!yakin) return;

    fetch("buatTugas.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "action=hapus&idTugas=" + encodeURIComponent(selectedTugas.idTugas)
    })
    .then(res => res.text())
    .then(msg => {
        alert(msg);
        if (selectedRow) selectedRow.remove();
        selectedRow = null;
        selectedTugas = null;
    })
    .catch(err => alert("❌ Gagal: " + err));
});

// === Dropdown Dinamis untuk Form Tambah ===
const mapel = document.getElementById("mapel");
const kelasContainer = document.getElementById("kelasContainer");
const kelas = document.getElementById("kelas");
const inputLain = document.getElementById("inputLain");
const materiSelect = document.getElementById("materi");

mapel.addEventListener("change", () => {
    const kodeMapel = mapel.value;
    if (kodeMapel) {
        // Ambil daftar kelas
        fetch(`backend/getKelas.php?kodeMapel=${kodeMapel}`)
            .then(res => res.json())
            .then(data => {
                kelas.innerHTML = '<option value="">-- Pilih Kelas --</option>';
                data.forEach(k => {
                    const opt = document.createElement("option");
                    opt.value = k.kelas;
                    opt.textContent = k.kelas;
                    kelas.appendChild(opt);
                });
            });
        
        // Ambil daftar materi untuk mapel ini
        fetch(`buatTugas.php?action=getMateri&kodeMapel=${kodeMapel}`)
            .then(res => res.json())
            .then(data => {
                materiSelect.innerHTML = '<option value="">-- Pilih Materi --</option>';
                materiSelect.innerHTML += '<option value="new" style="font-weight: 600; color: #4c6ef5;">➕ Jadikan Tugas Sebagai Materi Baru</option>';
                
                data.forEach(m => {
                    const opt = document.createElement("option");
                    opt.value = m.idMateri;
                    opt.textContent = m.judul;
                    materiSelect.appendChild(opt);
                });
            });
            
        kelasContainer.style.display = "block";
    } else {
        kelasContainer.style.display = "none";
        inputLain.style.display = "none";
    }
});

kelas.addEventListener("change", () => {
    if (kelas.value !== "") inputLain.style.display = "block";
    else inputLain.style.display = "none";
});

// === BATASI DEADLINE HANYA BISA PILIH WAKTU SETELAH SEKARANG ===
const deadlineInput = document.getElementById("deadline");
if (deadlineInput) {
    const now = new Date();
    const pad = (n) => n.toString().padStart(2, '0');
    const localISOTime = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
    deadlineInput.min = localISOTime;
    deadlineInput.value = localISOTime;
}

// === Untuk input edit deadline juga (modal edit) ===
const editDeadlineInput = document.getElementById("edit_deadline");
if (editDeadlineInput) {
    const now = new Date();
    const pad = (n) => n.toString().padStart(2, '0');
    const localISOTime = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
    editDeadlineInput.min = localISOTime;
}

// === Gaya highlight baris ===
const style = document.createElement("style");
style.textContent = `
.selected-row {
    background-color: #dbe4ff !important;
    box-shadow: inset 0 0 0 2px #4c6ef5;
}

.loading-row {
    position: relative;
    opacity: 0.7;
}

.loading-row::after {
    content: "⏳ Memuat...";
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #4c6ef5;
    font-weight: 600;
    font-size: 13px;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.edit-btn:disabled,
.delete-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.file-info {
    background: #e7f5ff;
    padding: 10px 15px;
    border-radius: 8px;
    border-left: 4px solid #4c6ef5;
    margin-bottom: 15px;
}

.file-link {
    color: #4c6ef5;
    text-decoration: none;
    font-weight: 500;
}

.file-link:hover {
    text-decoration: underline;
}
`;
document.head.appendChild(style);
</script>