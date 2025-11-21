<?php
include '../config/db.php';
include '../config/session.php';

// ===========================
//  CEK LOGIN
// ===========================
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../Auth/login.php");
    exit;
}

$idAkun = $_SESSION['user_id'];   // Ini idAkun

// Ambil data siswa BERDASARKAN idAkun
$qSiswa = mysqli_query($conn, "SELECT * FROM datasiswa WHERE idAkun='$idAkun'");
$siswa = mysqli_fetch_assoc($qSiswa);

if (!$siswa) {
    die("Error: Data siswa tidak ditemukan untuk idAkun: $idAkun");
}

$nis = $siswa['NIS'];  // Baru ambil NIS dari hasil query
$kelas = $siswa['kelas'];

// Ambil mapel berdasarkan kelas
$qMapel = mysqli_query($conn, "
    SELECT m.kodeMapel, m.namaMapel, j.idJadwalMapel
    FROM mapel m
    JOIN jadwalmapel j ON m.kodeMapel = j.kodeMapel
    WHERE j.kelas = '$kelas'
");

// Ambil presensi siswa
$presensiData = [];
$qPresensi = mysqli_query($conn, "SELECT * FROM presensisiswa WHERE NIS='$nis'");
while ($p = mysqli_fetch_assoc($qPresensi)) {
    $presensiData[$p['idBuatPresensi']] = $p;
}

// Ambil seluruh sesi presensi untuk kelas tersebut
$qBuatPresensi = mysqli_query($conn, "
    SELECT bp.*, j.idJadwalMapel 
    FROM buatpresensi bp
    JOIN jadwalmapel j ON bp.idJadwalMapel = j.idJadwalMapel
    WHERE j.kelas = '$kelas'
    ORDER BY bp.idBuatPresensi ASC
");

$jadwalPresensi = [];
while ($bp = mysqli_fetch_assoc($qBuatPresensi)) {
    $jadwalPresensi[$bp['idJadwalMapel']][] = $bp;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekap Presensi</title>
<link rel="stylesheet" href="cssSiswa/rekapPresensi.css">

</head>

<body>
<h2>Rekap Presensi - <?= $siswa['nama'] ?> (Kelas: <?= $kelas ?>)</h2>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Kode Mapel</th>
                <th>Mata Pelajaran</th>
                <?php for ($i = 1; $i <= 17; $i++): ?>
                    <th><?= $i ?></th>
                <?php endfor; ?>
            </tr>
        </thead>

        <tbody>
            <?php while ($m = mysqli_fetch_assoc($qMapel)): ?>
                <tr>
                    <td><?= $m['kodeMapel'] ?></td>
                    <td><?= $m['namaMapel'] ?></td>

                    <?php
                    $idJ = $m['idJadwalMapel'];
                    $list = isset($jadwalPresensi[$idJ]) ? $jadwalPresensi[$idJ] : [];

                    for ($i = 0; $i < 17; $i++) {
                        if (isset($list[$i])) {

                            $idBP = $list[$i]['idBuatPresensi'];

                            if (isset($presensiData[$idBP])) {
                                $status = $presensiData[$idBP]['status'];
                                echo "<td class='status-$status'>$status</td>";
                            } else {
                                echo "<td class='status-none'>-</td>";
                            }

                        } else {
                            echo "<td class='status-none'>-</td>";
                        }
                    }
                    ?>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>