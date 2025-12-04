<?php
require_once "../../config/db.php";

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM jadwalmapel WHERE idJadwalMapel = ?");
    $stmt->bind_param("s", $id);

    if ($stmt->execute()) {
        header("Location: ../kelolajadwal.php?success=delete");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
