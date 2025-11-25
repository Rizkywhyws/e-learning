<?php
//file: e-learningMrt/Guru/backend/getTabelKoreksi.php
session_start();

// ========== PROTEKSI LOGIN ==========
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'guru') {
    http_response_code(401);
    echo "<div style='color:red; text-align:center; padding:20px;'>⚠️ Unauthorized - Silakan login terlebih dahulu</div>";
    exit;
}

include "../../config/db.php";

$idTugas = isset($_GET['idTugas']) ? mysqli_real_escape_string($conn, $_GET['idTugas']) : '';

if (empty($idTugas)) {
    echo "<div style='color:red; text-align:center; padding:20px;'>⚠️ ID Tugas tidak ditemukan</div>";
    exit;
}

// Ambil data tugas
$qTugas = mysqli_query($conn, "SELECT * FROM tugas WHERE idTugas='$idTugas'");
$tugas = mysqli_fetch_assoc($qTugas);

if (!$tugas) {
    echo "<div style='color:red; text-align:center; padding:20px;'>⚠️ Data tugas tidak ditemukan</div>";
    exit;
}

// Ambil kelas berdasarkan jadwalmapel (relasi dengan mapel yang sama)
$qKelas = mysqli_query($conn, "
    SELECT DISTINCT kelas 
    FROM jadwalmapel 
    WHERE kodeMapel='{$tugas['kodeMapel']}'
");
$dKelas = mysqli_fetch_assoc($qKelas);
$kelas = $dKelas ? $dKelas['kelas'] : '';

// Ambil data siswa dan status tugas
$q = mysqli_query($conn, "
SELECT 
    s.NIS, s.nama, p.filePath, p.submittedAt, p.nilai,
    CASE
        WHEN p.idPengumpulan IS NULL THEN 'Kosong'
        ELSE p.status
    END AS status
FROM datasiswa s
LEFT JOIN pengumpulantugas p ON s.NIS = p.NIS AND p.idTugas = '$idTugas'
JOIN tugas t ON t.idTugas = '$idTugas'
WHERE s.kelas = '$kelas'
");

echo "
<div class='wide-section'>
    <div class='tugas-info'>
        <div class='tugas-header'>
            <div class='judul-container'>
                <label><strong>Deskripsi Tugas:</strong></label>
                <p style='max-width: 800px; margin-top: 5px; color: #333; line-height: 1.6;'>" . htmlspecialchars($tugas['deskripsi']) . "</p>
            </div>

            <div class='search-container'>
                <i class='fa fa-search'></i>
                <input type='text' id='searchNama' placeholder='Cari nama siswa...'>
            </div>
        </div>
    </div>

    <h3 style='margin-top: 30px; text-align:left;'>Daftar Pengumpulan Tugas</h3>
    <div class='table-container'>
        <table class='koreksi-table' id='tabelKoreksi'>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Siswa</th>
                    <th>File</th>
                    <th>Status</th>
                    <th>Nilai</th>
                </tr>
            </thead>
            <tbody>
";

$no = 1;
while ($r = mysqli_fetch_assoc($q)) {
    $file = $r['filePath'] ? "<a href='{$r['filePath']}' target='_blank' class='file-link'>Lihat File</a>" : "-";
    $disabled = $r['status'] == 'Kosong' ? 'disabled' : '';
    $nilaiValue = $r['nilai'] ? $r['nilai'] : '';
    
    echo "
    <tr>
        <td>$no</td>
        <td class='nama-siswa'>" . htmlspecialchars($r['nama']) . "</td>
        <td>$file</td>
        <td>{$r['status']}</td>
        <td><input type='number' name='nilai[{$r['NIS']}]' value='$nilaiValue' min='0' max='100' class='nilai-input' $disabled></td>
    </tr>
    ";
    $no++;
}

echo "
            </tbody>
        </table>
    </div>
    <button type='button' class='save-btn' id='saveNilai'>💾 Simpan Nilai</button>
</div>
";

?>

<!-- Tambahkan Font Awesome buat ikon search -->
<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>

<script>
$('#saveNilai').click(function(){
    let data = $('input[name^="nilai"]').serialize();
    $.post('backend/simpanNilai.php', data + '&idTugas=<?=$idTugas?>', function(res){
        alert(res);
    });
});

// 🔍 Fitur Search Nama
$('#searchNama').on('keyup', function(){
    let keyword = $(this).val().toLowerCase();
    $('#tabelKoreksi tbody tr').filter(function(){
        $(this).toggle($(this).find('.nama-siswa').text().toLowerCase().indexOf(keyword) > -1);
    });
});
</script>