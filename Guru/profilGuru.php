<?php
include '../config/db.php';

// CEK LOGIN
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../Auth/login.php");
    exit;
}

$idAkun   = $_SESSION['user_id'];
$nip = $_SESSION['nip'];

// Ambil Data Profil Guru
$data_profil = [
    'Nama' => 'Data tidak ditemukan',
    'NIP' => $nip,
    'No. Telp' => '-',
    'Alamat Email' => '-'
];

// Query untuk mengambil data guru dan akun
$query = "SELECT dg.nama, dg.NIP, dg.noTelp, a.email 
                 FROM dataguru dg
                 LEFT JOIN akun a ON dg.idAkun = a.idAkun
                 WHERE dg.NIP = '$nip'";

$result_profil = mysqli_query($conn, $query);

if ($result_profil && mysqli_num_rows($result_profil) > 0) {
    $row_profil = mysqli_fetch_assoc($result_profil);
    $data_profil['Nama'] = $row_profil['nama'] ?? 'N/A';
    $data_profil['No. Telp'] = $row_profil['noTelp'] ?? '-';
    $data_profil['Alamat Email'] = $row_profil['email'] ?? '-';
}

// Ambil Data Jadwal Mengajar
$jadwal_mengajar = [];

// Query untuk mengambil jadwal mengajar berdasarkan NIP
$query_jadwal = "SELECT * FROM jadwalmapel WHERE nipGuru = '$nip' ORDER BY hari, jamMulai";
$result_jadwal = mysqli_query($conn, $query_jadwal);

if ($result_jadwal) {
    while ($row_jadwal = mysqli_fetch_assoc($result_jadwal)) {
        
        $kode_mapel = $row_jadwal['kodeMapel'];
        $nama_mapel = $kode_mapel; 

        $query_mapel = "SELECT namaMapel FROM mapel WHERE kodeMapel = '$kode_mapel'";
        $result_mapel = mysqli_query($conn, $query_mapel);
        if ($result_mapel && mysqli_num_rows($result_mapel) > 0) {
            $row_mapel = mysqli_fetch_assoc($result_mapel);
            $nama_mapel = $row_mapel['namaMapel'];
        }
        
        // HITUNG WAKTU NGAJAR
        $jam_mulai = strtotime($row_jadwal['jamMulai']);
        $durasi_menit = $row_jadwal['durasi'];
        $waktu_selesai = date('H:i', $jam_mulai + ($durasi_menit * 60));
        $waktu_ajar = date('H:i', $jam_mulai) . " - " . $waktu_selesai;

        $jadwal_mengajar[] = [
            'nama_mapel' => $nama_mapel,
            'hari' => $row_jadwal['hari'],
            'waktu' => $waktu_ajar,
            'kelas' => $row_jadwal['kelas'],
            'ruangan' => $row_jadwal['ruangan']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profil Saya</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <link rel="stylesheet" href="css/profilGuru.css">
</head>
<body>
    <div class="container">
        <h3>Profil Saya</h3>
        <div class="profile-layout">
            
            <div class="profile-info-card">
                <div class="info-group"><label>Nama</label><span>:</span><p><?php echo htmlspecialchars($data_profil['Nama']); ?></p></div>
                <div class="info-group"><label>NIP</label><span>:</span><p><?php echo htmlspecialchars($data_profil['NIP']); ?></p></div>
                <div class="info-group"><label>No. Telp</label><span>:</span><p><?php echo htmlspecialchars($data_profil['No. Telp']); ?></p></div>
                <div class="info-group"><label>Alamat Email</label><span>:</span><p><?php echo htmlspecialchars($data_profil['Alamat Email']); ?></p></div>
            </div>

            <div class="change-password-card">
                <div class="card-title">Ganti Password</div>
                <form action="../Auth/changepassword-process.php" method="POST" id="formChangePassword" onsubmit="return confirmPasswordChange()"> 
                    <input type="hidden" name="nip_guru" value="<?php echo htmlspecialchars($NIP_GURU); ?>">
                    
                    <div class="form-group">
                        <label for="password_baru">Password Baru</label>
                        <input type="password" id="password_baru" name="new_password" placeholder="Masukkan Password Baru..." required>
                    </div>
                    <div class="form-group">
                        <label for="konfirmasi_password">Konfirmasi Password Baru</label>
                        <input type="password" id="konfirmasi_password" name="confirm_password" placeholder="Konfirmasi Password Baru..." required>
                    </div>
                    <button type="submit" class="btn-primary">Perbarui Password</button>
                </form>
            </div>
        </div>

        <h3 class="section-title">Jadwal Mengajar</h3>
        
        <div class="schedule-table-container">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Mapel</th>
                        <th>Hari</th>
                        <th>Waktu</th>
                        <th>Kelas</th>
                        <th>Ruangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($jadwal_mengajar)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">Tidak ada jadwal mengajar yang ditemukan untuk NIP <?php echo $NIP_GURU; ?>.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($jadwal_mengajar as $jadwal): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($jadwal['nama_mapel']); ?></td>
                                <td><?php echo htmlspecialchars($jadwal['hari']); ?></td>
                                <td><?php echo htmlspecialchars($jadwal['waktu']); ?></td>
                                <td><?php echo htmlspecialchars($jadwal['kelas']); ?></td>
                                <td><?php echo htmlspecialchars($jadwal['ruangan']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <script>
    function confirmPasswordChange() {
        return confirm("Yakin ingin mengubah password?");
    }
    </script>
</body>
</html>
