<?php
require_once "../../config/db.php"; // pastikan path ini benar

// Aktifkan laporan error untuk debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Ambil data dari form
    $kodeMapel = trim($_POST["kodeMapel"]);
    $namaMapel = trim($_POST["namaMapel"]);
    $nipGuru   = isset($_POST["nipGuru"]) ? trim($_POST["nipGuru"]) : "";

    // Validasi dasar
    if (empty($kodeMapel) || empty($namaMapel)) {
        echo "<script>alert('Kode dan Nama Mapel wajib diisi!'); history.back();</script>";
        exit;
    }

    // Cek apakah kode mapel sudah ada
    $check = $conn->prepare("SELECT kodeMapel FROM mapel WHERE kodeMapel = ?");
    $check->bind_param("s", $kodeMapel);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->close();
        echo "<script>alert('Kode Mapel sudah terdaftar!'); history.back();</script>";
        exit;
    }
    $check->close();

    // Simpan ke tabel mapel
    $stmt = $conn->prepare("INSERT INTO mapel (kodeMapel, namaMapel) VALUES (?, ?)");
    $stmt->bind_param("ss", $kodeMapel, $namaMapel);
    $stmt->execute();

    // Jika ada guru pengampu, masukkan ke tabel gurumapel
    if (!empty($nipGuru)) {
    // Buat ID otomatis
    $result = $conn->query("SELECT MAX(id) AS last_id FROM gurumapel");
    $row = $result->fetch_assoc();
    $last_id = $row['last_id'];

    if ($last_id) {
        $num = (int)substr($last_id, 2) + 1;
        $new_id = 'GM' . str_pad($num, 3, '0', STR_PAD_LEFT);
    } else {
        $new_id = 'GM001';
    }

    // Insert data
    $stmt2 = $conn->prepare("INSERT INTO gurumapel (id, kodeMapel, nipGuru) VALUES (?, ?, ?)");
    $stmt2->bind_param("sss", $new_id, $kodeMapel, $nipGuru);
    $stmt2->execute();
    $stmt2->close();
}
    echo "<script>alert('Mapel berhasil ditambahkan!'); window.location.href='../kelolamapel.php';</script>";
} else {
    header("Location: ../kelolamapel.php");
    exit;
}
?>
