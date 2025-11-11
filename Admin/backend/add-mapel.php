<?php
require_once "../../config/db.php"; // sesuaikan path ke file koneksi kamu

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
        echo "<script>alert('Kode Mapel sudah terdaftar!'); history.back();</script>";
        exit;
    }

    $check->close();

    // Simpan ke tabel mapel
    $stmt = $conn->prepare("INSERT INTO mapel (kodeMapel, namaMapel) VALUES (?, ?)");
    $stmt->bind_param("ss", $kodeMapel, $namaMapel);

    if ($stmt->execute()) {
        // Jika ada guru pengampu, masukkan ke tabel gurumapel
        if (!empty($nipGuru)) {
            $stmt2 = $conn->prepare("INSERT INTO gurumapel (kodeMapel, nipGuru) VALUES (?, ?)");
            $stmt2->bind_param("ss", $kodeMapel, $nipGuru);
            $stmt2->execute();
            $stmt2->close();
        }

        echo "<script>alert('Mapel berhasil ditambahkan!'); window.location.href='../kelolamapel.php';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan mapel.'); history.back();</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: ../kelolamapel.php");
    exit;
}
?>
