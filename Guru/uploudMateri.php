<?php
include_once("../config/db.php");

// Fungsi untuk buat ID acak 7 karakter (M + 6 karakter)
function generateIdMateri() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $id = 'M';
    for ($i = 0; $i < 6; $i++) {
        $id .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $id;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['data_json'])) {
    $data = json_decode($_POST['data_json'], true);

    // Kalau cuma satu objek dikirim
    if (!is_array($data)) {
        $data = [$data];
    } elseif (isset($data['kodeMapel'])) {
        $data = [$data];
    }

    $success = 0;
    $errors = [];

    foreach ($data as $item) {
        $idmateri = generateIdMateri();
        $kodeMapel = mysqli_real_escape_string($conn, $item['kodeMapel']);
        $NIP = intval($item['NIP']);
        $deskripsi = mysqli_real_escape_string($conn, $item['deskripsi']);
        $filePath = mysqli_real_escape_string($conn, $item['file_pdf']);
        $linkVideo = mysqli_real_escape_string($conn, $item['link_video']);
        $createdAt = date('Y-m-d H:i:s');

        $sql = "INSERT INTO materi (idmateri, kodeMapel, NIP, deskripsi, filePath, linkVideo, createdAt)
                VALUES ('$idmateri', '$kodeMapel', '$NIP', '$deskripsi', '$filePath', '$linkVideo', '$createdAt')";

        if (mysqli_query($conn, $sql)) {
            $success++;
        } else {
            $errors[] = mysqli_error($conn);
        }
    }

    if ($success > 0) {
        echo json_encode(["status" => "success", "inserted" => $success, "errors" => $errors]);
    } else {
        echo json_encode(["status" => "error", "message" => "Tidak ada data tersimpan", "errors" => $errors]);
    }
    exit();
}

// Ambil data guru dan mapel
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
    <style>
        /* Tombol edit dan hapus */
        td button {
            border: none;
            padding: 6px 10px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
        }

        .edit-btn {
            background-color: #1e70c1;
            color: white;
            margin-right: 5px;
        }

        .edit-btn:hover {
            background-color: #155a99;
        }

        .delete-btn {
            background-color: #ff4d4d;
            color: white;
        }

        .delete-btn:hover {
            background-color: #e60000;
        }
    </style>
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
                    <th>Deskripsi</th>
                    <th>Link Video</th>
                    <th>File PDF</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

        <div class="save-container">
            <button type="button" id="saveData">Simpan ke Database</button>
        </div>
    </form>
</div>

<script>
let dataList = [];
let editIndex = -1;

document.getElementById("addRow").addEventListener("click", function() {
    let kelas = document.getElementById("kelas").value;
    let mapelSelect = document.getElementById("mapel");
    let guruSelect = document.getElementById("nama_guru");
    let kodeMapel = mapelSelect.value;
    let namaMapel = mapelSelect.options[mapelSelect.selectedIndex]?.text || "";
    let NIP = guruSelect.value;
    let namaGuru = guruSelect.options[guruSelect.selectedIndex]?.text || "";
    let deskripsi = document.getElementById("deskripsi").value;
    let link = document.getElementById("link_video").value;
    let file = document.getElementById("file_pdf").files[0]?.name || "";
    let tanggal = new Date().toLocaleString();

    if (!kelas || !kodeMapel || !NIP || !deskripsi) {
        alert("Lengkapi semua field terlebih dahulu!");
        return;
    }

    const newData = { kelas, kodeMapel, namaMapel, NIP, namaGuru, deskripsi, link_video: link, file_pdf: file, created_at: tanggal };

    if (editIndex === -1) {
        dataList.push(newData);
    } else {
        dataList[editIndex] = newData;
        editIndex = -1;
        document.getElementById("addRow").textContent = "Tambah";
    }

    renderTable();
    document.getElementById("materiForm").reset();
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

function deleteRow(index) {
    if (confirm("Yakin ingin menghapus data ini?")) {
        dataList.splice(index, 1);
        renderTable();
    }
}

function editRow(index) {
    const item = dataList[index];
    document.getElementById("kelas").value = item.kelas;
    document.getElementById("mapel").value = item.kodeMapel;
    document.getElementById("nama_guru").value = item.NIP;
    document.getElementById("deskripsi").value = item.deskripsi;
    document.getElementById("link_video").value = item.link_video;
    editIndex = index;
    document.getElementById("addRow").textContent = "Update";
}

document.getElementById("saveData").addEventListener("click", async () => {
    if (dataList.length === 0) {
        alert("Tidak ada data yang disimpan!");
        return;
    }

    if (!confirm("Yakin ingin menyimpan semua data ke database?")) return;

    let formData = new FormData();
    formData.append("data_json", JSON.stringify(dataList));

    let res = await fetch("uploudMateri.php", { method: "POST", body: formData });
    let result = await res.json();

    console.log(result);

    if (result.status === "success") {
        alert(`✅ ${result.inserted} data berhasil disimpan!`);
        dataList = [];
        renderTable();
    } else {
        alert("❌ Gagal menyimpan data, cek console untuk detail.");
        console.log(result.errors);
    }
});
</script>
</body>
</html>
