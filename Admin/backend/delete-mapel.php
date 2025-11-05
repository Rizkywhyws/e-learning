<?php
require_once "../../config/db.php"; // sesuaikan path ke db.php kamu

if (isset($_GET["kode"])) {
    $kodeMapel = trim($_GET["kode"]);

    if (empty($kodeMapel)) {
        echo "<script>alert('Kode mapel tidak ditemukan!'); history.back();</script>";
        exit;
    }

    // Hapus dari tabel gurumapel terlebih dahulu (relasi)
    $conn->query("DELETE FROM gurumapel WHERE kodeMapel = '$kodeMapel'");

    // Hapus dari tabel mapel
    $stmt = $conn->prepare("DELETE FROM mapel WHERE kodeMapel = ?");
    $stmt->bind_param("s", $kodeMapel);

    if ($stmt->execute()) {
        echo "<script>alert('Mapel berhasil dihapus!'); window.location.href='../kelolamapel.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus mapel!'); history.back();</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: ../kelolamapel.php");
    exit;
}
?>
