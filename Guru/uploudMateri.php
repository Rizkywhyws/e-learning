<?php
include_once("../config/db.php");

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
        $NIP = mysqli_real_escape_string($conn, $data['NIP']);
        $judul = mysqli_real_escape_string($conn, $data['judul']);
        $deskripsi = mysqli_real_escape_string($conn, $data['deskripsi']);
        $filePath = mysqli_real_escape_string($conn, $data['file_pdf']);
        $linkVideo = mysqli_real_escape_string($conn, $data['link_video']);
        $createdAt = date('Y-m-d H:i:s');

        $sql = "INSERT INTO materi (idmateri, kodeMapel, NIP, judul, deskripsi, filePath, linkVideo, createdAt)
                VALUES ('$idmateri', '$kodeMapel', '$NIP', '$judul', '$deskripsi', '$filePath', '$linkVideo', '$createdAt')";

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
        $NIP = mysqli_real_escape_string($conn, $data['NIP']);
        $judul = mysqli_real_escape_string($conn, $data['judul']);
        $deskripsi = mysqli_real_escape_string($conn, $data['deskripsi']);
        $filePath = mysqli_real_escape_string($conn, $data['file_pdf']);
        $linkVideo = mysqli_real_escape_string($conn, $data['link_video']);

        $sql = "UPDATE materi 
                SET kodeMapel='$kodeMapel', NIP='$NIP', judul='$judul', deskripsi='$deskripsi',
                    filePath='$filePath', linkVideo='$linkVideo'
                WHERE idmateri='$idmateri'";

        if (mysqli_query($conn, $sql)) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        }
        exit;
    }

    // --- DELETE DATA FIX SESUAI PERINTAHMU ---
    if ($action === 'delete') {
        $idmateri = mysqli_real_escape_string($conn, $_POST['idmateri']);

        // cek apakah materi digunakan di tabel tugas
        $cek = mysqli_query($conn, "SELECT idTugas FROM tugas WHERE idMateri = '$idmateri'");

        if (mysqli_num_rows($cek) > 0) {
            // kalau dipakai → TIDAK BOLEH HAPUS ROW
            // hanya hapus filePath dan linkVideo
            $sql = "
                UPDATE materi
                SET filePath = NULL, linkVideo = NULL
                WHERE idMateri = '$idmateri'
            ";

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
            $sql = "DELETE FROM materi WHERE idMateri='$idmateri'";
            if (mysqli_query($conn, $sql)) {
                echo json_encode(["status" => "success"]);
            } else {
                echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
            }
        }
        exit;
    }
}

// Ambil data guru & mapel
$guruList = [];
$mapelList = [];
$result = mysqli_query($conn, "SELECT nama, NIP FROM dataguru");
while ($row = mysqli_fetch_assoc($result)) $guruList[] = $row;

$result2 = mysqli_query($conn, "SELECT kodeMapel, namaMapel FROM mapel");
while ($row = mysqli_fetch_assoc($result2)) $mapelList[] = $row;
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Upload Materi</title>
<link rel="stylesheet" href="CSS/uploudMateri.css">
</head>
<body>
<div class="container">
    <h2>Upload Materi</h2>

    <form id="materiForm">
        <div class="form-group">
            <label>Kelas</label>
            <select id="kelas">
                <option value="">-- Pilih Kelas --</option>
                <option value="10">Kelas 10</option>
                <option value="11">Kelas 11</option>
                <option value="12">Kelas 12</option>
            </select>
        </div>

        <div class="form-group">
            <label>Mata Pelajaran</label>
            <select id="mapel">
                <option value="">-- Pilih Mapel --</option>
                <?php foreach ($mapelList as $mapel): ?>
                    <option value="<?= htmlspecialchars($mapel['kodeMapel']) ?>">
                        <?= htmlspecialchars($mapel['namaMapel']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Nama Guru</label>
            <select id="nama_guru">
                <option value="">-- Pilih Guru --</option>
                <?php foreach ($guruList as $guru): ?>
                    <option value="<?= htmlspecialchars($guru['NIP']) ?>">
                        <?= htmlspecialchars($guru['nama']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Judul Materi</label>
            <input type="text" id="judul" placeholder="Masukkan judul materi...">
        </div>

        <div class="form-group">
            <label>Deskripsi</label>
            <textarea id="deskripsi" placeholder="Tuliskan deskripsi materi..."></textarea>
        </div>

        <div class="form-group">
            <label>Link Video</label>
            <input type="url" id="link_video" placeholder="https://contoh.com/video">
        </div>

        <div class="form-group">
            <label>Upload File (PDF)</label>
            <input type="file" id="file_pdf" accept=".pdf">
        </div>

        <div class="button-group">
            <button type="button" id="addRow">Tambah</button>
        </div>

        <table id="dataTable">
            <thead>
                <tr>
                    <th>Kelas</th>
                    <th>Mapel</th>
                    <th>Guru</th>
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
    let guruSelect = document.getElementById("nama_guru");
    let kodeMapel = mapelSelect.value;
    let namaMapel = mapelSelect.options[mapelSelect.selectedIndex]?.text || "";
    let NIP = guruSelect.value;
    let namaGuru = guruSelect.options[guruSelect.selectedIndex]?.text || "";
    let judul = document.getElementById("judul").value;
    let deskripsi = document.getElementById("deskripsi").value;
    let link = document.getElementById("link_video").value;
    let file = document.getElementById("file_pdf").files[0]?.name || "";
    let tanggal = new Date().toLocaleString();

    if (!kelas || !kodeMapel || !NIP || !judul || !deskripsi) {
        alert("Lengkapi semua field terlebih dahulu!");
        return;
    }

    let newData = { kelas, kodeMapel, namaMapel, NIP, namaGuru, judul, deskripsi, link_video: link, file_pdf: file, created_at: tanggal };

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
            <td>${item.namaGuru}</td>
            <td>${item.judul}</td>
            <td>${item.deskripsi}</td>
            <td>${item.link_video}</td>
            <td>${item.file_pdf}</td>
            <td>${item.created_at}</td>
            <td>
                <button type="button" onclick="editRow(${index})">Edit</button>
                <button type="button" onclick="deleteRow(${index})">Hapus</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function editRow(index) {
    const item = dataList[index];
    document.getElementById("kelas").value = item.kelas;
    document.getElementById("mapel").value = item.kodeMapel;
    document.getElementById("nama_guru").value = item.NIP;
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
</body>
</html>