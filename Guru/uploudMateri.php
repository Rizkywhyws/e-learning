<?php
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

// === PROSES REQUEST ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $data = json_decode($_POST['data_json'] ?? '[]', true);

    // --- TAMBAH DATA ---
    if ($action === 'add') {
        $idmateri = generateIdMateri($conn);
        $kodeMapel = mysqli_real_escape_string($conn, $data['kodeMapel']);
        $judul = mysqli_real_escape_string($conn, $data['judul']);
        $deskripsi = mysqli_real_escape_string($conn, $data['deskripsi']);
        $filePath = mysqli_real_escape_string($conn, $data['file_pdf']);
        $linkVideo = mysqli_real_escape_string($conn, $data['link_video']);
        $createdAt = date('Y-m-d H:i:s');

        // Gunakan NIP dari session, bukan dari form
        $sql = "INSERT INTO materi (idmateri, kodeMapel, NIP, judul, deskripsi, filePath, linkVideo, createdAt)
                VALUES ('$idmateri', '$kodeMapel', '$nipGuru', '$judul', '$deskripsi', '$filePath', '$linkVideo', '$createdAt')";

        if (mysqli_query($conn, $sql)) {
            echo json_encode(["status" => "success", "idmateri" => $idmateri]);
        } else {
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        }
        exit;
    }

    // --- UPDATE DATA ---
    if ($action === 'update') {
        $idmateri = mysqli_real_escape_string($conn, $data['idmateri']);
        $kodeMapel = mysqli_real_escape_string($conn, $data['kodeMapel']);
        $judul = mysqli_real_escape_string($conn, $data['judul']);
        $deskripsi = mysqli_real_escape_string($conn, $data['deskripsi']);
        $filePath = mysqli_real_escape_string($conn, $data['file_pdf']);
        $linkVideo = mysqli_real_escape_string($conn, $data['link_video']);

        // Gunakan NIP dari session
        $sql = "UPDATE materi 
                SET kodeMapel='$kodeMapel', NIP='$nipGuru', judul='$judul', deskripsi='$deskripsi',
                    filePath='$filePath', linkVideo='$linkVideo'
                WHERE idmateri='$idmateri' AND NIP='$nipGuru'";

        if (mysqli_query($conn, $sql)) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        }
        exit;
    }

    // --- DELETE DATA ---
    if ($action === 'delete') {
        $idmateri = mysqli_real_escape_string($conn, $_POST['idmateri']);

        // cek apakah materi digunakan di tabel tugas
        $cek = mysqli_query($conn, "SELECT idTugas FROM tugas WHERE idMateri = '$idmateri'");

        if (mysqli_num_rows($cek) > 0) {
            // kalau dipakai → TIDAK BOLEH HAPUS ROW
            // hanya hapus filePath dan linkVideo
            $sql = "UPDATE materi
                    SET filePath = NULL, linkVideo = NULL
                    WHERE idMateri = '$idmateri' AND NIP='$nipGuru'";

            if (mysqli_query($conn, $sql)) {
                echo json_encode([
                    "status" => "warning",
                    "message" => "Materi dipakai di tugas → hanya file & video dihapus"
                ]);
            } else {
                echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
            }
        } else {
            // tidak dipakai tugas → boleh delete row
            $sql = "DELETE FROM materi WHERE idMateri='$idmateri' AND NIP='$nipGuru'";
            if (mysqli_query($conn, $sql)) {
                echo json_encode(["status" => "success"]);
            } else {
                echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
            }
        }
        exit;
    }
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
    </div>

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
  </form>
</div>

<script>
let dataList = [];
let editIndex = -1;

document.getElementById("addRow").addEventListener("click", async function() {
    let kelas = document.getElementById("kelas").value;
    let mapelSelect = document.getElementById("mapel");
    let kodeMapel = mapelSelect.value;
    let namaMapel = mapelSelect.options[mapelSelect.selectedIndex]?.text || "";
    let judul = document.getElementById("judul").value;
    let deskripsi = document.getElementById("deskripsi").value;
    let link = document.getElementById("link_video").value;
    let file = document.getElementById("file_pdf").files[0]?.name || "";
    let tanggal = new Date().toLocaleString();

    if (!kelas || !kodeMapel || !judul || !deskripsi) {
        alert("Lengkapi semua field terlebih dahulu!");
        return;
    }

    let newData = { kelas, kodeMapel, namaMapel, judul, deskripsi, link_video: link, file_pdf: file, created_at: tanggal };

    // === TAMBAH ===
    if (editIndex === -1) {
        let formData = new FormData();
        formData.append("action", "add");
        formData.append("data_json", JSON.stringify(newData));
        let res = await fetch("uploudMateri.php?t=" + Date.now(), { method: "POST", body: formData });
        let result = await res.json();

        if (result.status === "success") {
            newData.idmateri = result.idmateri;
            dataList.push(newData);
            renderTable();
            document.getElementById("materiForm").reset();
            alert("Data berhasil disimpan (ID: " + result.idmateri + ")");
        } else {
            alert("Gagal menambah data: " + result.message);
        }
    } else {
        // === UPDATE ===
        let item = dataList[editIndex];
        newData.idmateri = item.idmateri;

        let formData = new FormData();
        formData.append("action", "update");
        formData.append("data_json", JSON.stringify(newData));

        let res = await fetch("uploudMateri.php?t=" + Date.now(), { method: "POST", body: formData });
        let result = await res.json();

        if (result.status === "success") {
            dataList[editIndex] = newData;
            editIndex = -1;
            renderTable();
            document.getElementById("materiForm").reset();
            document.getElementById("addRow").textContent = "Tambah";
            alert("Data berhasil diupdate");
        } else {
            alert("Gagal update!");
        }
    }
});

function renderTable() {
    const tbody = document.querySelector("#dataTable tbody");
    tbody.innerHTML = "";
    dataList.forEach((item, index) => {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${item.kelas}</td>
            <td>${item.namaMapel}</td>
            <td>${item.judul}</td>
            <td>${item.deskripsi}</td>
            <td>${item.link_video}</td>
            <td>${item.file_pdf}</td>
            <td>${item.created_at}</td>
            <td>
                <button type="button" class="edit-btn" onclick="editRow(${index})">Edit</button>
                <button type="button" class="delete-btn" onclick="deleteRow(${index})">Hapus</button>
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
}

async function deleteRow(index) {
    if (!confirm("Yakin ingin menghapus?")) return;

    const item = dataList[index];
    let formData = new FormData();
    formData.append("action", "delete");
    formData.append("idmateri", item.idmateri);

    let res = await fetch("uploudMateri.php?t=" + Date.now(), { method: "POST", body: formData });
    let result = await res.json();

    if (result.status === "success") {
        dataList.splice(index, 1);
        renderTable();
        alert("Berhasil hapus");
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