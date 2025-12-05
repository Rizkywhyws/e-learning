<?php

// Start session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../../config/db.php');

// Cek login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die("Error: Sesi tidak valid. <a href='../ngerjakanQuiz.php'>Kembali</a>");
}

// Cek role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    die("Error: Akses ditolak! Hanya siswa yang bisa submit quiz.");
}

// Validasi data POST
if(!isset($_POST['idQuiz']) || !isset($_POST['NIS']) || !isset($_POST['jawaban'])) {
    die("Error: Data tidak lengkap! <a href='../ngerjakanQuiz.php'>Kembali</a>");
}

$idQuiz = mysqli_real_escape_string($conn, $_POST['idQuiz']);
$NIS = mysqli_real_escape_string($conn, $_POST['NIS']);
$waktuMulai = mysqli_real_escape_string($conn, $_POST['waktuMulai']);
$waktuSelesai = mysqli_real_escape_string($conn, $_POST['waktuSelesai']);
$jawaban = $_POST['jawaban'];

// ===============================
// PERBAIKAN UTAMA: HAPUS DATA LAMA SEBELUM SUBMIT
// ===============================
$queryDelete = "DELETE FROM jawabanquiz WHERE idQuiz = ? AND NIS = ?";
$stmtDelete = mysqli_prepare($conn, $queryDelete);
mysqli_stmt_bind_param($stmtDelete, "ss", $idQuiz, $NIS);
mysqli_stmt_execute($stmtDelete);

// Jangan cek lagi apakah sudah submit, karena kita sudah hapus datanya
// Jadi langsung lanjut ke proses insert

// Mulai transaction
mysqli_begin_transaction($conn);

try {
    $totalBenar = 0;
    $totalSoalPilgan = 0;
    $adaEsai = false;
    
    foreach($jawaban as $idSoal => $jawabanSiswa) {
        $idSoal = mysqli_real_escape_string($conn, $idSoal);
        
        $querySoal = "SELECT type, jawabanPilgan, jawabanMulti FROM soalquiz WHERE idSoal = ?";
        $stmtSoal = mysqli_prepare($conn, $querySoal);
        mysqli_stmt_bind_param($stmtSoal, "s", $idSoal);
        mysqli_stmt_execute($stmtSoal);
        $resultSoal = mysqli_stmt_get_result($stmtSoal);
        
        if($resultSoal && mysqli_num_rows($resultSoal) > 0) {
            $dataSoal = mysqli_fetch_assoc($resultSoal);
            $tipeSoal = strtolower(trim($dataSoal['type']));
            
            // Inisialisasi variabel untuk insert
            $kolomJawabanPilgan = null;
            $kolomJawabanMulti = null;
            $jawabanEsai = null;
            $nilaiSoal = null;
            
            if($tipeSoal === 'esai') {
                $jawabanEsai = mysqli_real_escape_string($conn, $jawabanSiswa);
                $adaEsai = true;
                $nilaiSoal = null;
                
            } else {
                // Pilgan atau Multi
                $jawabanSiswaEscaped = mysqli_real_escape_string($conn, strtolower(trim($jawabanSiswa)));

                if ($tipeSoal === 'pilgan' || $tipeSoal === 'pilihan ganda') {
                        $kolomJawabanPilgan = mysqli_real_escape_string($conn, strtolower(trim($jawabanSiswa)));
                        $kolomJawabanMulti = null;

                        $jawabanBenarRaw = trim($dataSoal['jawabanPilgan']);
                        $jawabanBenar = strtolower($jawabanBenarRaw); // Misal: 'A', 'B', dll.

                        // Bandingkan langsung sebagai string (huruf)
                        if (strtolower($jawabanSiswa) === $jawabanBenar) {
                            $totalBenar++;
                            $nilaiSoal = 100;
                        } else {
                            $nilaiSoal = 0;
                        }
                    } else {
                        // Multi-select
                        $kolomJawabanPilgan = null;
                        $kolomJawabanMulti = mysqli_real_escape_string($conn, trim($jawabanSiswa));

                        $jawabanBenarRaw = trim($dataSoal['jawabanMulti']);
                        $jawabanBenar = strtolower($jawabanBenarRaw);

                        // Normalisasi: Hilangkan spasi, ubah ke lowercase, urutkan, lalu bandingkan
                        $jawabanSiswaNormalized = strtolower(trim($jawabanSiswa));
                        $jawabanBenarNormalized = strtolower(trim($jawabanBenarRaw));

                        // Hilangkan spasi di sekitar koma
                        $jawabanSiswaNormalized = preg_replace('/\s*,\s*/', ',', $jawabanSiswaNormalized);
                        $jawabanBenarNormalized = preg_replace('/\s*,\s*/', ',', $jawabanBenarNormalized);

                        // Pisah dan urutkan jawaban
                        $jawabanSiswaArray = explode(',', $jawabanSiswaNormalized);
                        $jawabanBenarArray = explode(',', $jawabanBenarNormalized);

                        sort($jawabanSiswaArray);
                        sort($jawabanBenarArray);

                        // Gabung kembali
                        $jawabanSiswaSorted = implode(',', $jawabanSiswaArray);
                        $jawabanBenarSorted = implode(',', $jawabanBenarArray);

                        // Bandingkan
                        if ($jawabanSiswaSorted === $jawabanBenarSorted) {
                            $totalBenar++;
                            $nilaiSoal = 100;
                        } else {
                            $nilaiSoal = 0;
                        }
                    }

                $totalSoalPilgan++;
            }
            
            // Generate idJawaban
            $queryLastId = "SELECT MAX(idJawaban) as last_id FROM jawabanquiz WHERE idJawaban LIKE 'JW%'";
            $stmtLastId = mysqli_prepare($conn, $queryLastId);
            mysqli_stmt_execute($stmtLastId);
            $resultLastId = mysqli_stmt_get_result($stmtLastId);
            $rowLastId = mysqli_fetch_assoc($resultLastId);
            $lastId = $rowLastId['last_id'] ?? 'JW00000';
            $number = intval(substr($lastId, 2));
            $newNumber = $number + 1;
            $idJawaban = 'JW' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);

            // Safety check
            $queryCheckId = "SELECT COUNT(*) as count FROM jawabanquiz WHERE idJawaban = ?";
            $stmtCheckId = mysqli_prepare($conn, $queryCheckId);
            mysqli_stmt_bind_param($stmtCheckId, "s", $idJawaban);
            mysqli_stmt_execute($stmtCheckId);
            $resultCheckId = mysqli_stmt_get_result($stmtCheckId);
            $rowCheckId = mysqli_fetch_assoc($resultCheckId);
            if ($rowCheckId['count'] > 0) {
                do {
                    $newNumber++;
                    $idJawaban = 'JW' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
                    mysqli_stmt_bind_param($stmtCheckId, "s", $idJawaban);
                    mysqli_stmt_execute($stmtCheckId);
                    $resultCheckId = mysqli_stmt_get_result($stmtCheckId);
                    $rowCheckId = mysqli_fetch_assoc($resultCheckId);
                } while ($rowCheckId['count'] > 0);
            }
            mysqli_stmt_close($stmtCheckId);
            mysqli_stmt_close($stmtLastId);

            // Insert jawaban
            // PERBAIKAN: Gunakan kolom jawabanPilgan dan jawabanMulti yang benar
            $queryInsert = "INSERT INTO jawabanquiz 
                           (idJawaban, idQuiz, idSoal, NIS, jawabanPilgan, jawabanMulti, jawabanEsai, nilai) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtInsert = mysqli_prepare($conn, $queryInsert);
            mysqli_stmt_bind_param($stmtInsert, "ssssssss", 
                $idJawaban, 
                $idQuiz, 
                $idSoal, 
                $NIS, 
                $kolomJawabanPilgan,
                $kolomJawabanMulti,
                $jawabanEsai,
                $nilaiSoal
            );
            
            if(!mysqli_stmt_execute($stmtInsert)) {
                throw new Exception("Gagal menyimpan jawaban: " . mysqli_error($conn));
            }
            
            mysqli_stmt_close($stmtInsert);
        }
        
        mysqli_stmt_close($stmtSoal);
    }
    
    // PERBAIKAN: Hitung nilai berdasarkan soal yang dijawab (bukan semua soal)
    $nilai = 0;
    if($totalSoalPilgan > 0) {
        // Nilai = (jumlah benar / total soal pilgan) * 100
        $nilai = round(($totalBenar / $totalSoalPilgan) * 100, 2);
    }
    
    $statusNilai = $adaEsai ? 'menunggu_koreksi' : 'selesai';
    
    mysqli_commit($conn);

    // Generate idHasil
    $queryLastId = "SELECT MAX(idHasil) as last_id FROM hasilquiz WHERE idHasil LIKE 'HZ%'";
    $stmtLastId = mysqli_prepare($conn, $queryLastId);
    mysqli_stmt_execute($stmtLastId);
    $resultLastId = mysqli_stmt_get_result($stmtLastId);
    $rowLastId = mysqli_fetch_assoc($resultLastId);
    $lastId = $rowLastId['last_id'] ?? 'HZ00000';
    $number = intval(substr($lastId, 2));
    $newNumber = $number + 1;
    $idHasil = 'HZ' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);

    // Safety check
    $queryCheckId = "SELECT COUNT(*) as count FROM hasilquiz WHERE idHasil = ?";
    $stmtCheckId = mysqli_prepare($conn, $queryCheckId);
    mysqli_stmt_bind_param($stmtCheckId, "s", $idHasil);
    mysqli_stmt_execute($stmtCheckId);
    $resultCheckId = mysqli_stmt_get_result($stmtCheckId);
    $rowCheckId = mysqli_fetch_assoc($resultCheckId);
    if ($rowCheckId['count'] > 0) {
        do {
            $newNumber++;
            $idHasil = 'HZ' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
            mysqli_stmt_bind_param($stmtCheckId, "s", $idHasil);
            mysqli_stmt_execute($stmtCheckId);
            $resultCheckId = mysqli_stmt_get_result($stmtCheckId);
            $rowCheckId = mysqli_fetch_assoc($resultCheckId);
        } while ($rowCheckId['count'] > 0);
    }
    mysqli_stmt_close($stmtCheckId);
    mysqli_stmt_close($stmtLastId);

    // Simpan hasil
    $queryHasil = "INSERT INTO hasilquiz (idHasil, idQuiz, NIS, nilai, tanggalSubmit) VALUES (?, ?, ?, ?, NOW()) 
                   ON DUPLICATE KEY UPDATE nilai = VALUES(nilai), idHasil = VALUES(idHasil), tanggalSubmit = VALUES(tanggalSubmit)";
    $stmtHasil = mysqli_prepare($conn, $queryHasil);
    mysqli_stmt_bind_param($stmtHasil, "sssd", $idHasil, $idQuiz, $NIS, $nilai);
    if (!mysqli_stmt_execute($stmtHasil)) {
        throw new Exception("Gagal menyimpan hasil quiz: " . mysqli_error($conn));
    }
    mysqli_stmt_close($stmtHasil);

    $redirectUrl = '../hasilQuiz.php?idQuiz=' . $idQuiz . 
                   '&nilai=' . $nilai . 
                   '&benar=' . $totalBenar . 
                   '&total=' . $totalSoalPilgan;
    
    if($adaEsai) {
        $redirectUrl .= '&ada_esai=1';
    }
    
    echo "<script>
            alert('Quiz selesai dikerjakan!');
            window.location.href = '$redirectUrl';
          </script>";
    exit;
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    die("Error: " . $e->getMessage() . " <a href='../ngerjakanQuiz.php'>Kembali</a>");
}

?>
