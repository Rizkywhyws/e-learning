<?php
include '../config/db.php';

// ===== CEK LOGIN =====
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../Auth/login.php");
    exit;
}

$idAkun = $_SESSION['user_id'];
$nis = $_SESSION['nis'];

// --- 1. Ambil Data Profil Siswa ---
$data_profil = [
    'Nama' => 'Data tidak ditemukan',
    'NIS' => $nis,
    'NISN' => '-',
    'Jurusan' => '-',
    'Kelas' => '-',
    'Alamat Email' => '-'
];

// Query untuk mengambil data siswa dan akun
$query = "SELECT ds.nama, ds.NIS, ds.NISN, ds.jurusan, ds.kelas, a.email 
          FROM datasiswa ds
          LEFT JOIN akun a ON ds.idAkun = a.idAkun
          WHERE ds.NIS = '$nis'";

$result_profil = mysqli_query($conn, $query);

if ($result_profil && mysqli_num_rows($result_profil) > 0) {
    $row_profil = mysqli_fetch_assoc($result_profil);
    $data_profil['Nama'] = $row_profil['nama'] ?? 'N/A';
    $data_profil['NISN'] = $row_profil['NISN'] ?? '-';
    $data_profil['Jurusan'] = $row_profil['jurusan'] ?? '-';
    $data_profil['Kelas'] = $row_profil['kelas'] ?? '-';
    $data_profil['Alamat Email'] = $row_profil['email'] ?? '-';
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

  <link rel="stylesheet" href="cssSiswa/profilSiswa.css">
</head>
<body>
    <div class="container">
        <h3>Profil Saya</h3>
        <div class="profile-layout">
            
            <div class="profile-info-card">
                <div class="info-group"><label>Nama</label><span>:</span><p><?php echo htmlspecialchars($data_profil['Nama']); ?></p></div>
                <div class="info-group"><label>NIS</label><span>:</span><p><?php echo htmlspecialchars($data_profil['NIS']); ?></p></div>
                <div class="info-group"><label>NISN</label><span>:</span><p><?php echo htmlspecialchars($data_profil['NISN']); ?></p></div>
                <div class="info-group"><label>Jurusan</label><span>:</span><p><?php echo htmlspecialchars($data_profil['Jurusan']); ?></p></div>
                <div class="info-group"><label>Kelas</label><span>:</span><p><?php echo htmlspecialchars($data_profil['Kelas']); ?></p></div>
                <div class="info-group"><label>Alamat Email</label><span>:</span><p><?php echo htmlspecialchars($data_profil['Alamat Email']); ?></p></div>
            </div>

            <div class="change-password-card">
                <div class="card-title">Ganti Password</div>
                <form action="../Auth/changepassword-process.php" method="POST" id="formChangePassword" onsubmit="return confirmPasswordChange()"> 
                    <input type="hidden" name="nis_siswa" value="<?php echo htmlspecialchars($nis); ?>">
                    
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

    </div>

    <script>
    function confirmPasswordChange() {
        return confirm("Yakin ingin mengubah password?");
    }
    </script>
</body>
</html>