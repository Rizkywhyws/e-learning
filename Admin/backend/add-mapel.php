<?php
require_once "../../config/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Fungsi generate kode mapel acak unik
function generateKodeMapel($conn) {
    do {
        $kode = strtoupper(substr(bin2hex(random_bytes(3)), 0, 7)); // kode acak 6-7 char
        $check = $conn->prepare("SELECT kodeMapel FROM mapel WHERE kodeMapel = ?");
        $check->bind_param("s", $kode);
        $check->execute();
        $check->store_result();
        $exists = $check->num_rows > 0;
        $check->close();
    } while ($exists);

    return $kode;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $namaMapel = trim($_POST["namaMapel"]);
    $nipGuru   = isset($_POST["nipGuru"]) ? trim($_POST["nipGuru"]) : "";

    // Validasi wajib
    if (empty($namaMapel)) {
        echo "<script>alert('Nama Mapel wajib diisi!'); history.back();</script>";
        exit;
    }

    // buat kodeMapel acak
    $kodeMapel = generateKodeMapel($conn);

    // insert mapel
    $stmt = $conn->prepare("INSERT INTO mapel (kodeMapel, namaMapel) VALUES (?, ?)");
    $stmt->bind_param("ss", $kodeMapel, $namaMapel);
    $stmt->execute();
    $stmt->close();

    // Tambah guru pengampu jika diisi
    if (!empty($nipGuru)) {

        // ID otomatis GM001, GM002, dst.
        $result = $conn->query("SELECT MAX(id) AS last_id FROM gurumapel");
        $row = $result->fetch_assoc();
        $last_id = $row['last_id'];

        if ($last_id) {
            $num = (int) substr($last_id, 2) + 1;
            $new_id = 'GM' . str_pad($num, 3, '0', STR_PAD_LEFT);
        } else {
            $new_id = 'GM001';
        }

        $stmt2 = $conn->prepare("INSERT INTO gurumapel (id, kodeMapel, nipGuru) VALUES (?, ?, ?)");
        $stmt2->bind_param("sss", $new_id, $kodeMapel, $nipGuru);
        $stmt2->execute();
        $stmt2->close();
    }

    echo "<script>alert('Mapel berhasil ditambahkan!'); window.location.href='../kelolamapel.php';</script>";
    exit;
}

// Jika akses selain POST
header("Location: ../kelolamapel.php");
exit;
?>
