<?php
require_once "../../config/db.php";
header('Content-Type: application/json');

// Ambil parameter dari URL atau form
if (!isset($_GET['nip']) || empty($_GET['nip'])) {
    echo json_encode(['success' => false, 'error' => 'NIP tidak diberikan']);
    exit;
}

$nip = intval($_GET['nip']); // pastikan tipe angka

try {
    $conn->begin_transaction();

    // 1️⃣ Ambil idAkun berdasarkan NIP
    $stmt = $conn->prepare("SELECT idAkun FROM dataGuru WHERE NIP = ?");
    $stmt->bind_param("i", $nip);
    $stmt->execute();
    $res = $stmt->get_result();
    $idAkun = $res->fetch_assoc()['idAkun'] ?? null;
    $stmt->close();

    // 2️⃣ Hapus data guru dulu
    $stmt2 = $conn->prepare("DELETE FROM dataGuru WHERE NIP = ?");
    $stmt2->bind_param("i", $nip);
    $stmt2->execute();
    $stmt2->close();

    // 3️⃣ Baru hapus akun jika ada
    if ($idAkun) {
        $stmt3 = $conn->prepare("DELETE FROM akun WHERE idAkun = ?");
        $stmt3->bind_param("s", $idAkun);
        $stmt3->execute();
        $stmt3->close();
    }

    $conn->commit();
    echo json_encode([
        'success' => true,
        'deletedGuru' => 1,
        'deletedAkun' => $idAkun ? 1 : 0
    ]);

} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
exit;
?>
