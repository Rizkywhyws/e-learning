<?php
require_once "../../config/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../kelolaguru.php');
    exit;
}

$original_nip = $_POST['original_nip']; 
$nip = $_POST['nip'];
$nama = $_POST['nama'];
$email = $_POST['email'];
$password = $_POST['password'];
$noTelp = $_POST['noTelp'];

// ambil idAkun lama
$q = $conn->prepare("SELECT idAkun FROM dataGuru WHERE NIP = ?");
$q->bind_param("i", $original_nip);
$q->execute();
$res = $q->get_result();
$row = $res->fetch_assoc();
$idAkun = $row['idAkun'] ?? null;

$conn->begin_transaction();

try {
    // update guru
    $u1 = $conn->prepare("UPDATE dataGuru SET NIP = ?, nama = ?, noTelp = ? WHERE NIP = ?");
    $u1->bind_param("issi", $nip, $nama, $noTelp, $original_nip);
    $u1->execute();

    // update email akun
    if ($idAkun) {
        $u2 = $conn->prepare("UPDATE akun SET email = ? WHERE idAkun = ?");
        $u2->bind_param("ss", $email, $idAkun);
        $u2->execute();

        // update password bila diisi
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $u3 = $conn->prepare("UPDATE akun SET password = ? WHERE idAkun = ?");
            $u3->bind_param("ss", $hash, $idAkun);
            $u3->execute();
        }
    }

    $conn->commit();
    header("Location: ../kelolaguru.php?status=updated");

} catch (Exception $e) {
    $conn->rollback();
    header("Location: ../kelolaguru.php?status=error");
}

exit;
?>
