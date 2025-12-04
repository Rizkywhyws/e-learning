<?php
include '../config/db.php';

//  CEK LOGIN

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'siswa') {
    echo "<script>alert('Anda harus login sebagai siswa!'); window.location='../Auth/login.php';</script>";
    exit;
}

$idAkun = $_SESSION['user_id'];

// Ambil data siswa BERDASARKAN idAkun
$qSiswa = mysqli_query($conn, "SELECT * FROM datasiswa WHERE idAkun='$idAkun'");
$siswa = mysqli_fetch_assoc($qSiswa);

if (!$siswa) {
    die("Error: Data siswa tidak ditemukan untuk idAkun: $idAkun");
}

$nis = $siswa['NIS'];
$kelas = $siswa['kelas'];

// Ambil mapel berdasarkan kelas (DISTINCT untuk 1 baris per mapel)
$qMapel = mysqli_query($conn, "
    SELECT DISTINCT m.kodeMapel, m.namaMapel
    FROM mapel m
    JOIN jadwalmapel j ON m.kodeMapel = j.kodeMapel
    WHERE j.kelas = '$kelas'
    ORDER BY m.namaMapel ASC
");

// Ambil SEMUA presensi yang dibuat untuk kelas ini
$qAllPresensi = mysqli_query($conn, "
    SELECT bp.*, j.idJadwalMapel, j.kodeMapel, ps.status, ps.NIS
    FROM buatpresensi bp
    JOIN jadwalmapel j ON bp.idJadwalMapel = j.idJadwalMapel
    LEFT JOIN presensisiswa ps ON bp.idBuatPresensi = ps.idBuatPresensi AND ps.NIS = '$nis'
    WHERE j.kelas = '$kelas'
    ORDER BY j.kodeMapel ASC, bp.waktuDibuat ASC
");

// Kelompokkan presensi per mapel
$presensiPerMapel = [];
while ($row = mysqli_fetch_assoc($qAllPresensi)) {
    $presensiPerMapel[$row['kodeMapel']][] = $row;
}

// Hitung maksimal pertemuan (dinamis berdasarkan data terbanyak)
$maxPertemuan = 0;
foreach ($presensiPerMapel as $kodeMapel => $data) {
    $jumlah = count($data);
    if ($jumlah > $maxPertemuan) {
        $maxPertemuan = $jumlah;
    }
}

if ($maxPertemuan == 0) $maxPertemuan = 1; // Minimal 1 kolom

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rekap Presensi</title>
<link rel="stylesheet" href="cssSiswa/rekapPresensi.css?v=<?= time() ?>">
</head>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Kode Mapel</th>
                <th>Mata Pelajaran</th>
                <?php for ($i = 1; $i <= $maxPertemuan; $i++): ?>
                    <th><?= $i ?></th>
                <?php endfor; ?>
                <th>Kehadiran</th>
            </tr>
        </thead>

        <tbody>
            <?php 
            if (mysqli_num_rows($qMapel) == 0) {
                echo "<tr><td colspan='" . (3 + $maxPertemuan) . "' style='text-align:center; color:#999;'>Belum ada mata pelajaran untuk kelas ini.</td></tr>";
            }
            
            mysqli_data_seek($qMapel, 0);
            while ($m = mysqli_fetch_assoc($qMapel)): 
                $kodeMapel = $m['kodeMapel'];
                $dataPresensi = isset($presensiPerMapel[$kodeMapel]) ? $presensiPerMapel[$kodeMapel] : [];
                
                // Hitung statistik kehadiran
                $totalPresensi = count($dataPresensi);
                $jumlahHadir = 0;
                
                foreach ($dataPresensi as $p) {
                    if ($p['status'] === 'Hadir' || $p['status'] === 'Terlambat') {
                        $jumlahHadir++;
                    }
                }
                
                $persentase = ($totalPresensi > 0) ? round(($jumlahHadir / $totalPresensi) * 100) : 0;
            ?>
                <tr>
                    <td><?= htmlspecialchars($kodeMapel) ?></td>
                    <td style="text-align: left; padding-left: 10px;"><?= htmlspecialchars($m['namaMapel']) ?></td>

                    <?php
                    // Tampilkan pertemuan sesuai jumlah maksimal
                    for ($i = 0; $i < $maxPertemuan; $i++) {
                        if (isset($dataPresensi[$i])) {
                            $status = $dataPresensi[$i]['status'] ?? '';
                            $statusClass = 'none';
                            $displayStatus = '-';
                            
                            // Mapping status
                            if ($status === 'Hadir') {
                                $displayStatus = 'H';
                                $statusClass = 'H';
                            } elseif ($status === 'Terlambat') {
                                $displayStatus = 'T';
                                $statusClass = 'T';
                            } elseif ($status === 'Izin') {
                                $displayStatus = 'I';
                                $statusClass = 'I';
                            } elseif ($status === 'Alfa') {
                                $displayStatus = 'A';
                                $statusClass = 'A';
                            } elseif ($status === 'Sakit') {
                                $displayStatus = 'S';
                                $statusClass = 'S';
                            } else {
                                $displayStatus = '-';
                                $statusClass = 'none';
                            }
                            
                            echo "<td class='status-$statusClass'><strong>$displayStatus</strong></td>";
                        } else {
                            echo "<td class='status-none'>-</td>";
                        }
                    }
                    ?>
                    
                    <td class="persentase"><?= $persentase ?>%</td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div class="legend">
    <span class="h">Hadir</span>
    <span class="t">Terlambat</span>
    <span class="a">Alpa</span>
    <span class="s">Sakit</span>
    <span class="i">Izin</span>
    <span class="n">Tidak Ada Presensi</span>
</div>

</body>
</html>