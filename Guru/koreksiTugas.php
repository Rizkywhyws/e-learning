<?php
//session_start();

// ========== PROTEKSI LOGIN & ROLE ==========
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'guru') {
    header('Location: ../Auth/login.php');
    exit;
}

include "../config/db.php";

// ========== AMBIL DATA DARI SESSION ==========
$idAkun = $_SESSION['user_id']; // Gunakan user_id dari login
$nipGuru = isset($_SESSION['nip']) ? $_SESSION['nip'] : '';

// Jika NIP tidak ada di session, ambil dari database
if (empty($nipGuru)) {
    $qGuru = mysqli_query($conn, "SELECT NIP FROM dataguru WHERE idAkun='$idAkun'");
    $dataGuru = mysqli_fetch_assoc($qGuru);
    $nipGuru = isset($dataGuru['NIP']) ? $dataGuru['NIP'] : '';
    $_SESSION['nip'] = $nipGuru; // Simpan ke session
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Koreksi Tugas Siswa</title>
<link rel="stylesheet" href="CSS/koreksiTugas.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
<div class="form-container">
    <h2>Koreksi Tugas Siswa</h2>

    <!-- Form -->
    <form id="formKoreksi">
        <!-- Dropdown Mata Pelajaran -->
        <label for="mapel">Mata Pelajaran</label>
        <select name="mapel" id="mapel">
            <option value="">-- Pilih Mata Pelajaran --</option>
            <?php
            // Ambil mapel dari database (yang diajar guru login)
            $qMapel = mysqli_query($conn, "
                SELECT DISTINCT m.kodeMapel, m.namaMapel 
                FROM mapel m
                JOIN gurumapel gm ON gm.kodeMapel=m.kodeMapel
                WHERE gm.nipGuru='$nipGuru'
            ");
            while ($r = mysqli_fetch_assoc($qMapel)) {
                echo "<option value='{$r['kodeMapel']}'>{$r['namaMapel']}</option>";
            }
            ?>
        </select>

        <!-- Dropdown Kelas -->
        <div id="kelasContainer" style="display:none;">
            <label for="kelas">Kelas</label>
            <select name="kelas" id="kelas">
                <option value="">-- Pilih Kelas --</option>
            </select>
        </div>

        <!-- Dropdown Judul Tugas -->
        <div id="tugasContainer" style="display:none;">
            <label for="tugas">Judul Tugas</label>
            <select name="tugas" id="tugas">
                <option value="">-- Pilih Tugas --</option>
            </select>
        </div>
    </form>
</div>

<!-- Bagian detail tugas & tabel koreksi -->
<div id="inputLain" style="display:none;"></div>

<script>
// ========== Ambil kelas setelah mapel dipilih ==========
$('#mapel').on('change', function() {
    let kodeMapel = $(this).val();
    if (kodeMapel === "") {
        $('#kelasContainer').hide();
        $('#tugasContainer').hide();
        $('#inputLain').hide();
        return;
    }

    $.get('backend/getKelas.php', { kodeMapel: kodeMapel }, function(res){
        let data = JSON.parse(res);
        let html = '<option value="">-- Pilih Kelas --</option>';
        data.forEach(d => {
            html += `<option value="${d.kelas}">${d.kelas}</option>`;
        });
        $('#kelas').html(html);
        $('#kelasContainer').show();
        $('#tugasContainer').hide();
        $('#inputLain').hide();
    });
});

// ========== Ambil daftar tugas ==========
$('#kelas').on('change', function(){
    let kodeMapel = $('#mapel').val();
    let kelas = $(this).val();

    if (kelas === "") {
        $('#tugasContainer').hide();
        $('#inputLain').hide();
        return;
    }

    $.get('backend/getTugas.php', { kodeMapel: kodeMapel, kelas: kelas }, function(res){
        let data = JSON.parse(res);
        let html = '<option value="">-- Pilih Tugas --</option>';
        data.forEach(d => {
            html += `<option value="${d.idTugas}">${d.judul}</option>`;
        });
        $('#tugas').html(html);
        $('#tugasContainer').show();
        $('#inputLain').hide();
    });
});

// ========== Ambil detail & tabel tugas ==========
$('#tugas').on('change', function(){
    let idTugas = $(this).val();
    if (idTugas === "") {
        $('#inputLain').hide();
        return;
    }

    $.get('backend/getTabelKoreksi.php', { idTugas: idTugas }, function(html){
        $('#inputLain').html(html).show();
    });
});
</script>
</body>
</html>
