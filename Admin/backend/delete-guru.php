<?php
require_once "../../config/db.php";
header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);

if (!isset($body['nips']) || !is_array($body['nips'])) {
    echo json_encode(['success'=>false,'error'=>'invalid']);
    exit;
}

$nips = $body['nips'];

$conn->begin_transaction();

try {

    // Ambil idAkun dulu
    $in = implode(',', array_fill(0, count($nips), '?'));
    $types = str_repeat('i', count($nips));

    $stmt = $conn->prepare("SELECT idAkun FROM dataGuru WHERE NIP IN ($in)");
    $stmt->bind_param($types, ...array_map('intval', $nips));
    $stmt->execute();
    $res = $stmt->get_result();

    $akunIDs = [];
    while ($r = $res->fetch_assoc()) {
        if (!empty($r['idAkun'])) $akunIDs[] = $r['idAkun'];
    }

    // Hapus akun terkait
    if (count($akunIDs) > 0) {
        $in2 = implode(',', array_fill(0, count($akunIDs), '?'));
        $types2 = str_repeat('s', count($akunIDs));

        $stmt2 = $conn->prepare("DELETE FROM akun WHERE idAkun IN ($in2)");
        $stmt2->bind_param($types2, ...$akunIDs);
        $stmt2->execute();
    }

    // Hapus guru
    $stmt3 = $conn->prepare("DELETE FROM dataGuru WHERE NIP IN ($in)");
    $stmt3->bind_param($types, ...array_map('intval', $nips));
    $stmt3->execute();

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}

exit;
?>
