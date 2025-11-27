<?php
// file: getTugasGuru.php
session_start();
include "../../config/db.php";

// ========== CEK LOGIN ==========
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'guru') {
    http_response_code(401);
    echo '<tr><td colspan="6" style="color:red; text-align:center;">Unauthorized</td></tr>';
    exit;
}

// ========== AMBIL NIP GURU ==========
$nipGuru = $_SESSION['nip'] ?? null;

if (!$nipGuru) {
    // fallback ambil dari DB
    $idAkun = $_SESSION['user_id'];
    $qGuru = mysqli_query($conn, "SELECT NIP FROM dataguru WHERE idAkun='$idAkun'");
    $guru = mysqli_fetch_assoc($qGuru);
    $nipGuru = $guru['NIP'] ?? null;
}

if (!$nipGuru) {
    echo '<tr><td colspan="6" style="color:red;">NIP Guru tidak ditemukan</td></tr>';
    exit;
}

// ========== AMBIL PARAMETER GET ==========
$kodeMapel = mysqli_real_escape_string($conn, $_GET['mapel'] ?? '');
$kelas      = mysqli_real_escape_string($conn, $_GET['kelas'] ?? '');

if (!$kodeMapel || !$kelas) {
    echo '<tr><td colspan="6" style="text-align:center; color:red;">Mapel atau kelas tidak valid!</td></tr>';
    exit;
}

// ========== QUERY AMBIL TUGAS ==========
$tugasRes = mysqli_query($conn, "
    SELECT t.*
    FROM tugas t
    JOIN jadwalmapel jm 
        ON jm.kodeMapel = t.kodeMapel 
       AND jm.nipGuru = t.NIP
    WHERE t.NIP = '$nipGuru'
      AND t.kodeMapel = '$kodeMapel'
      AND jm.kelas = '$kelas'
    ORDER BY t.createdAt DESC
");

if (!$tugasRes) {
    echo '<tr><td colspan="6" style="color:red;">Query error: ' . mysqli_error($conn) . '</td></tr>';
    exit;
}

// ========== TAMPILKAN DATA ==========
$no = 1;

if (mysqli_num_rows($tugasRes) > 0) {
    while ($t = mysqli_fetch_assoc($tugasRes)) {

        // File column
        $fileColumn = "-";
        if (!empty($t['filePath'])) {
            $fileName = basename($t['filePath']);
            $absolutePath = "http://localhost/elearning-app/" . ltrim($t['filePath'], '/');

            $fileColumn = "
                <a href='$absolutePath' target='_blank' style='color:#4c6ef5; text-decoration:none;'>
                    📎 $fileName
                </a>";
        }

        echo "
            <tr data-id='{$t['idTugas']}'>
                <td>{$no}</td>
                <td>" . htmlspecialchars($t['judul']) . "</td>
                <td style='max-width:400px;'>" . htmlspecialchars($t['deskripsi']) . "</td>
                <td>{$t['deadline']}</td>
                <td>{$fileColumn}</td>
                <td>{$t['createdAt']}</td>
            </tr>";
        $no++;
    }
} else {
    echo "<tr><td colspan='6' style='text-align:center; color:#888;'>Tidak ada tugas untuk mapel & kelas ini.</td></tr>";
}
?>
