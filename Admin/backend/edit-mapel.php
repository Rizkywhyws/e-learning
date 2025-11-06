<?php
require_once "../../config/db.php"; // sesuaikan path ke db.php kamu

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $original_kode = trim($_POST["original_kode"]);
    $kodeMapel = trim($_POST["kodeMapel"]);
    $namaMapel = trim($_POST["namaMapel"]);
    $nipGuru   = isset($_POST["nipGuru"]) ? trim($_POST["nipGuru"]) : "";

    if (empty($original_kode) || empty($kodeMapel) || empty($namaMapel)) {
        echo "<script>alert('Data tidak lengkap!'); history.back();</script>";
        exit;
    }

    // Jika kode mapel diubah, pastikan belum digunakan oleh mapel lain
    if ($kodeMapel !== $original_kode) {
        $check = $conn->prepare("SELECT kodeMapel FROM mapel WHERE kodeMapel = ?");
        $check->bind_param("s", $kodeMapel);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            echo "<script>alert('Kode mapel baru sudah terpakai!'); history.back();</script>";
            exit;
        }
        $check->close();
    }

    // Update data mapel
    $stmt = $conn->prepare("UPDATE mapel SET kodeMapel = ?, namaMapel = ? WHERE kodeMapel = ?");
    $stmt->bind_param("sss", $kodeMapel, $namaMapel, $original_kode);

    if ($stmt->execute()) {
        // Hapus relasi lama di gurumapel (jika ada)
        $conn->query("DELETE FROM gurumapel WHERE kodeMapel = '$kodeMapel' OR kodeMapel = '$original_kode'");

        // Jika guru dipilih, masukkan kembali
        if (!empty($nipGuru)) {
            $stmt2 = $conn->prepare("INSERT INTO gurumapel (kodeMapel, nipGuru) VALUES (?, ?)");
            $stmt2->bind_param("ss", $kodeMapel, $nipGuru);
            $stmt2->execute();
            $stmt2->close();
        }

        echo "<script>alert('Mapel berhasil diperbarui!'); window.location.href='../kelolamapel.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui mapel!'); history.back();</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: ../kelolamapel.php");
    exit;
}
?>
