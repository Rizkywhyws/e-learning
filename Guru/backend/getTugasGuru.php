<?php
session_start();
//file getTugasGuru.php
include "../../config/session.php";
include "../../config/db.php";

// Cek login
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'guru') {
    http_response_code(401);
    echo '<tr><td colspan="6" style="color:red;">Unauthorized</td></tr>';
    exit;
}


// Ambil NIP guru dari session
$nipGuru = isset($_SESSION['nip']) ? $_SESSION['nip'] : '';

// Jika NIP tidak ada di session, ambil dari database
if (empty($nipGuru)) {
    $idAkun = $_SESSION['user_id'];
    $qGuru = mysqli_query($conn, "SELECT NIP FROM dataguru WHERE idAkun='$idAkun'");
    $dataGuru = mysqli_fetch_assoc($qGuru);
    $nipGuru = isset($dataGuru['NIP']) ? $dataGuru['NIP'] : '';
}


// Ambil parameter dari AJAX
$kodeMapel = mysqli_real_escape_string($conn, $_GET['mapel']);
$kelas = mysqli_real_escape_string($conn, $_GET['kelas']);
$no = 1;

// Ambil tugas berdasarkan mapel & kelas melalui relasi jadwalmapel
$tugasRes = mysqli_query($conn, "
    SELECT t.*
    FROM tugas t
    JOIN jadwalmapel jm ON jm.kodeMapel = t.kodeMapel AND jm.nipGuru = t.NIP
    WHERE t.NIP = '$nipGuru'
      AND t.kodeMapel = '$kodeMapel'
      AND jm.kelas = '$kelas'
    ORDER BY t.createdAt DESC
");

if (!$tugasRes) {
    echo '<tr><td colspan="6" style="color:red;">Query error: ' . mysqli_error($conn) . '</td></tr>';
    exit;
}

if (mysqli_num_rows($tugasRes) > 0) {
    while ($t = mysqli_fetch_assoc($tugasRes)) {
        // Format file column
        $fileColumn = "-";
        if (!empty($t['filePath'])) {
            $fileName = basename($t['filePath']);
            $fileColumn = "<a href='{$t['filePath']}' target='_blank' style='color: #4c6ef5; text-decoration: none;'>📎 {$fileName}</a>";
        }
        
        echo "
        <tr data-id='{$t['idTugas']}'>
            <td>{$no}</td>
            <td>{$t['judul']}</td>
            <td style='max-width:400px;'>{$t['deskripsi']}</td>
            <td>{$t['deadline']}</td>
            <td>{$fileColumn}</td>
            <td>{$t['createdAt']}</td>
        </tr>";
        $no++;
    }
} else {
    echo "<tr><td colspan='6' style='text-align:center; color:#999;'>Tidak ada tugas untuk kombinasi ini.</td></tr>";
}
?>