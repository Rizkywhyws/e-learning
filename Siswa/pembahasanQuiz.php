<?php
session_start();
require_once '../config/db.php';

// Cek login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die("Error: Sesi tidak valid. <a href='login.php'>Login dulu</a>");
}

// Cek role (pastikan hanya siswa yang bisa akses)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    die("Error: Akses ditolak! Hanya siswa yang bisa melihat pembahasan.");
}

// Ambil idQuiz dari URL
$idQuiz = $_GET['idQuiz'] ?? null;

if (!$idQuiz) {
    die("Error: ID Quiz tidak ditemukan! <a href='dashboard.php'>Kembali ke Dashboard</a>");
}

$NIS = $_SESSION['nis'] ?? null;

if (!$NIS) {
    die("Error: Data siswa tidak valid! <a href='login.php'>Login ulang</a>");
}

// Cek apakah siswa ini benar-benar sudah mengerjakan quiz ini
$queryCek = "SELECT COUNT(*) as jumlah FROM jawabanquiz WHERE idQuiz = ? AND NIS = ?";
$stmtCek = mysqli_prepare($conn, $queryCek);
mysqli_stmt_bind_param($stmtCek, "ss", $idQuiz, $NIS);
mysqli_stmt_execute($stmtCek);
$resultCek = mysqli_stmt_get_result($stmtCek);
$rowCek = mysqli_fetch_assoc($resultCek);

if ($rowCek['jumlah'] == 0) {
    die("Error: Anda belum mengerjakan quiz ini atau data jawaban tidak ditemukan. <a href='dashboard.php'>Kembali ke Dashboard</a>");
}

// Ambil data soal dan jawaban siswa
// Perhatikan: Nama kolom disesuaikan dengan struktur tabel yang diberikan
$querySoal = "
    SELECT
        sq.idSoal,
        sq.pertanyaan AS soal,
        sq.type,
        sq.opsi_a,
        sq.opsi_b,
        sq.opsi_c,
        sq.opsi_d,
        sq.opsi_e,
        sq.jawabanPilgan AS jawabanBenarPG,
        sq.jawabanMulti AS jawabanBenarMulti, -- <-- TAMBAHKAN ALIAS INI
        jq.jawabanPilgan,   -- Jangan alias dulu
        jq.jawabanMulti,    -- Jangan alias dulu
        jq.jawabanEsai,
        jq.nilai
    FROM soalquiz sq
    LEFT JOIN jawabanquiz jq ON sq.idSoal = jq.idSoal AND jq.NIS = ? AND jq.idQuiz = ?
    WHERE sq.idQuiz = ?
    ORDER BY sq.idSoal ASC
";

$stmtSoal = mysqli_prepare($conn, $querySoal);
mysqli_stmt_bind_param($stmtSoal, "sss", $NIS, $idQuiz, $idQuiz);
mysqli_stmt_execute($stmtSoal);
$resultSoal = mysqli_stmt_get_result($stmtSoal);

// Ambil informasi quiz (judul, dll) - opsional, untuk tampilan
$judulQuiz = "Quiz $idQuiz"; // Default jika tidak ingin query tambahan
$queryInfo = "SELECT judul FROM quiz WHERE idQuiz = ?";
$stmtInfo = mysqli_prepare($conn, $queryInfo);
mysqli_stmt_bind_param($stmtInfo, "s", $idQuiz);
mysqli_stmt_execute($stmtInfo);
$resultInfo = mysqli_stmt_get_result($stmtInfo);
if ($info = mysqli_fetch_assoc($resultInfo)) {
    $judulQuiz = $info['judul'];
}

// Ambil nilai total (jika disimpan di tabel hasilquiz atau dihitung ulang)
$nilaiTotal = null;
$totalSoal = 0;
$benarPG = 0;
$adaEsai = false;

// Ambil semua baris untuk perhitungan
$soalDanJawaban = [];
while ($row = mysqli_fetch_assoc($resultSoal)) {
    $soalDanJawaban[] = $row;
    $totalSoal++;

    // Normalisasi tipe soal
    $typeNormalized = strtolower(trim($row['type']));

    if ($typeNormalized === 'pilgan' || $typeNormalized === 'pilihan ganda') {
        // Konversi jawaban siswa (huruf) ke angka
        $jawabanSiswaAngka = null;
        switch (strtolower($row['jawabanPilgan'])) {
            case 'a': $jawabanSiswaAngka = 0; break;
            case 'b': $jawabanSiswaAngka = 1; break;
            case 'c': $jawabanSiswaAngka = 2; break;
            case 'd': $jawabanSiswaAngka = 3; break;
            default: $jawabanSiswaAngka = -1; // Tidak valid
        }

        // Bandingkan dengan jawaban benar (yang sudah dalam bentuk angka)
        if ($jawabanSiswaAngka === intval($row['jawabanBenarPG'])) {
            $benarPG++;
        }
    } elseif ($typeNormalized === 'multi-select' || $typeNormalized === 'multi pilihan') {
        // Bandingkan jawaban siswa (string angka, misal: "0,1") dengan jawaban benar (dari kolom jawabanMulti)
        if ($row['jawabanMulti'] === $row['jawabanBenarMulti']) { // <-- PERBAIKAN DI SINI
            $benarPG++;
        }
    }
    if ($typeNormalized === 'esai') {
        $adaEsai = true;
    }
}

// Hitung nilai total otomatis (hanya dari soal PG/Multi)
if ($totalSoal > 0) {
    $jumlahSoalPGMulti = array_filter($soalDanJawaban, function($s) {
        $typeNormalized = strtolower(trim($s['type']));
        return $typeNormalized === 'pilihan ganda' || $typeNormalized === 'multi pilihan';
    });
    $jumlahSoalPGMulti = count($jumlahSoalPGMulti);

    if ($jumlahSoalPGMulti > 0) {
        $nilaiTotal = round(($benarPG / $jumlahSoalPGMulti) * 100, 2);
    } else {
        $nilaiTotal = 0;
    }
}

// Ambil nilai dari tabel hasilquiz jika ada (opsional)
$queryHasil = "SELECT nilai FROM hasilquiz WHERE idQuiz = ? AND NIS = ? LIMIT 1";
if (mysqli_query($conn, "SHOW TABLES LIKE 'hasilquiz'")->num_rows == 1) {
    $stmtHasil = mysqli_prepare($conn, $queryHasil);
    mysqli_stmt_bind_param($stmtHasil, "ss", $idQuiz, $NIS);
    mysqli_stmt_execute($stmtHasil);
    $resultHasil = mysqli_stmt_get_result($stmtHasil);
    if ($rowHasil = mysqli_fetch_assoc($resultHasil)) {
        $nilaiTotal = $rowHasil['nilai']; // Gunakan nilai dari tabel hasil jika ada
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembahasan Quiz: <?= htmlspecialchars($judulQuiz) ?></title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
            --border-color: #dee2e6;
            --card-bg: #ffffff;
            --text-primary: #212529;
            --text-secondary: #6c757d;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            margin: 0;
            padding: 20px;
            color: var(--text-primary);
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .header h2 {
            margin: 0;
            font-size: 1.8rem;
        }

        .nilai-box {
            background-color: rgba(255, 255, 255, 0.15);
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            text-align: center;
            font-size: 1.2rem;
        }

        .nilai-box strong {
            font-size: 1.5rem;
            color: var(--warning-color);
        }

        .peringatan {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px 15px;
            margin: 15px 20px;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .soal-item {
            margin: 20px;
            padding: 20px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--card-bg);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .soal-header {
            font-weight: bold;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .soal-pertanyaan {
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .pilihan {
            margin: 8px 0;
            padding: 8px 12px;
            border-radius: 5px;
            transition: background-color 0.2s;
        }

        .pilihan:hover {
            background-color: var(--light-bg);
        }

        .jawaban-anda-box, .jawaban-benar-box {
            margin: 10px 0;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: bold;
        }

        .jawaban-anda-box {
            background-color: #e3f2fd;
            color: var(--primary-color);
            border-left: 4px solid var(--primary-color);
        }

        .jawaban-benar-box {
            background-color: #d4edda;
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .status-benar {
            color: var(--success-color);
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: bold;
            margin-top: 10px;
        }

        .status-salah {
            color: var(--danger-color);
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: bold;
            margin-top: 10px;
        }

        .nilai-soal {
            font-size: 0.9em;
            color: var(--text-secondary);
            margin-top: 10px;
            font-weight: bold;
        }

        .btn-kembali {
            display: inline-block;
            margin: 20px;
            padding: 12px 20px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-kembali:hover {
            background-color: #0056b3;
        }

        .esai-jawaban {
            background-color: #f8f9fa;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            padding: 10px;
            margin-top: 5px;
            white-space: pre-wrap; /* Menjaga format baris baru */
        }

        .info-soal {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 5px;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h2><i class="fas fa-graduation-cap"></i> Pembahasan: <?= htmlspecialchars($judulQuiz) ?></h2>
            <div class="nilai-box">
                <strong>Nilai Total:</strong> <?= $nilaiTotal !== null ? number_format($nilaiTotal, 2) . '%' : 'Belum Dinilai' ?>
            </div>
        </div>

        <?php if ($adaEsai): ?>
            <div class="peringatan">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Perhatian:</strong> Quiz ini mengandung soal esai. Nilai mungkin belum final jika soal esai belum dikoreksi oleh guru.
            </div>
        <?php endif; ?>

        <?php if (empty($soalDanJawaban)): ?>
            <div class="soal-item">
                <p style="text-align: center; color: var(--text-secondary);"><i class="fas fa-info-circle"></i> Belum ada soal atau jawaban yang ditemukan untuk quiz ini.</p>
            </div>
        <?php else: ?>
            <?php foreach ($soalDanJawaban as $index => $item): ?>
                <div class="soal-item">
                    <div class="soal-header">
                        <i class="fas fa-question-circle"></i> Soal <?= $index + 1 ?> (<?= ucfirst(htmlspecialchars($item['type'])) ?>)
                    </div>
                    <div class="soal-pertanyaan">
                        <?= htmlspecialchars($item['soal']) ?>
                    </div>

                    <?php if ($item['type'] === 'pilihan ganda'): ?>
                        <div class="pilihan-container">
                            <?php if ($item['opsi_a']): ?><div class="pilihan">A. <?= htmlspecialchars($item['opsi_a']) ?></div><?php endif; ?>
                            <?php if ($item['opsi_b']): ?><div class="pilihan">B. <?= htmlspecialchars($item['opsi_b']) ?></div><?php endif; ?>
                            <?php if ($item['opsi_c']): ?><div class="pilihan">C. <?= htmlspecialchars($item['opsi_c']) ?></div><?php endif; ?>
                            <?php if ($item['opsi_d']): ?><div class="pilihan">D. <?= htmlspecialchars($item['opsi_d']) ?></div><?php endif; ?>
                        </div>

                        <div class="jawaban-anda-box">
                            <i class="fas fa-user"></i> <strong>Jawaban Anda:</strong>
                            <span class="jawaban-siswa"><?= strtoupper(htmlspecialchars($item['jawabanPilgan'])) ?></span>
                        </div>

                        <!-- Konversi jawaban benar dari angka ke huruf untuk ditampilkan -->
                        <?php
                        $hurufBenar = '';
                        // Logika standar: 0 -> A, 1 -> B, 2 -> C, 3 -> D
                        switch (intval($item['jawabanBenarPG'])) {
                            case 0: $hurufBenar = 'A'; break;
                            case 1: $hurufBenar = 'B'; break;
                            case 2: $hurufBenar = 'C'; break;
                            case 3: $hurufBenar = 'D'; break;
                            default: $hurufBenar = 'Tidak Diketahui';
                        }
                        ?>
                        <div class="jawaban-benar-box">
                            <i class="fas fa-check-circle"></i> <strong>Jawaban Benar:</strong>
                            <span class="jawaban-benar"><?= htmlspecialchars($hurufBenar) ?></span>
                        </div>

                        <?php
                        // Konversi jawaban siswa ke angka untuk pengecekan
                        $jawabanSiswaAngka = null;
                        switch (strtolower($item['jawabanPilgan'])) {
                            case 'a': $jawabanSiswaAngka = 0; break;
                            case 'b': $jawabanSiswaAngka = 1; break;
                            case 'c': $jawabanSiswaAngka = 2; break;
                            case 'd': $jawabanSiswaAngka = 3; break;
                            default: $jawabanSiswaAngka = -1;
                        }
                        ?>
                        <?php if ($jawabanSiswaAngka === intval($item['jawabanBenarPG'])): ?>
                            <div class="status-benar">
                                <i class="fas fa-check-circle"></i> Benar
                            </div>
                        <?php else: ?>
                            <div class="status-salah">
                                <i class="fas fa-times-circle"></i> Salah
                            </div>
                        <?php endif; ?>

                    <?php elseif ($item['type'] === 'multi-select'): ?>
                        <div class="pilihan-container">
                            <?php if ($item['opsi_a']): ?><div class="pilihan">A. <?= htmlspecialchars($item['opsi_a']) ?></div><?php endif; ?>
                            <?php if ($item['opsi_b']): ?><div class="pilihan">B. <?= htmlspecialchars($item['opsi_b']) ?></div><?php endif; ?>
                            <?php if ($item['opsi_c']): ?><div class="pilihan">C. <?= htmlspecialchars($item['opsi_c']) ?></div><?php endif; ?>
                            <?php if ($item['opsi_d']): ?><div class="pilihan">D. <?= htmlspecialchars($item['opsi_d']) ?></div><?php endif; ?>
                            <?php if ($item['opsi_e']): ?><div class="pilihan">E. <?= htmlspecialchars($item['opsi_e']) ?></div><?php endif; ?>
                        </div>

                        <div class="jawaban-anda-box">
                            <i class="fas fa-user"></i> <strong>Jawaban Anda:</strong>
                            <span class="jawaban-siswa">
                                <?php
                                $hurufJawaban = '';
                                if (!empty($item['jawabanMulti'])) {
                                    $angkaArray = explode(',', $item['jawabanMulti']);
                                    $hurufArray = [];
                                    foreach ($angkaArray as $angka) {
                                        switch (intval($angka)) {
                                            case 0: $hurufArray[] = 'A'; break;
                                            case 1: $hurufArray[] = 'B'; break;
                                            case 2: $hurufArray[] = 'C'; break;
                                            case 3: $hurufArray[] = 'D'; break;
                                            case 4: $hurufArray[] = 'E'; break;
                                            default: $hurufArray[] = '?';
                                        }
                                    }
                                    $hurufJawaban = implode(', ', $hurufArray);
                                } else {
                                    $hurufJawaban = 'Tidak Dijawab';
                                }
                                echo htmlspecialchars($hurufJawaban);
                                ?>
                            </span>
                        </div>

                        <!-- Konversi jawaban benar dari angka ke huruf -->
                        <?php
                        $hurufBenar = '';
                        if (!empty($item['jawabanBenarMulti'])) {
                            $angkaArray = explode(',', $item['jawabanBenarMulti']);
                            $hurufArray = [];
                            foreach ($angkaArray as $angka) {
                                switch (intval($angka)) {
                                    case 0: $hurufArray[] = 'A'; break;
                                    case 1: $hurufArray[] = 'B'; break;
                                    case 2: $hurufArray[] = 'C'; break;
                                    case 3: $hurufArray[] = 'D'; break;
                                    case 4: $hurufArray[] = 'E'; break;
                                    default: $hurufArray[] = '?';
                                }
                            }
                            $hurufBenar = implode(', ', $hurufArray);
                        } else {
                            $hurufBenar = 'Tidak Diketahui';
                        }
                        ?>
                        <div class="jawaban-benar-box">
                            <i class="fas fa-check-circle"></i> <strong>Jawaban Benar:</strong>
                            <span class="jawaban-benar"><?= htmlspecialchars($hurufBenar) ?></span>
                        </div>

                        <?php if ($item['jawabanMulti'] === $item['jawabanBenarMulti']): ?>
                            <div class="status-benar">
                                <i class="fas fa-check-circle"></i> Benar
                            </div>
                        <?php else: ?>
                            <div class="status-salah">
                                <i class="fas fa-times-circle"></i> Salah
                            </div>
                        <?php endif; ?>

                    <?php elseif ($item['type'] === 'esai'): ?>
                        <div class="jawaban-anda-box">
                            <i class="fas fa-user"></i> <strong>Jawaban Anda:</strong>
                        </div>
                        <div class="esai-jawaban">
                            <?= htmlspecialchars($item['jawabanEsai']) ?>
                        </div>
                        <div class="nilai-soal">
                            <i class="fas fa-star"></i> Nilai: <?= $item['nilai'] !== null ? $item['nilai'] : '<span style="color:var(--warning-color);">Belum Dinilai</span>' ?>
                        </div>
                        <div class="info-soal">
                            <i class="fas fa-info-circle"></i> Jawaban esai akan dinilai oleh guru.
                        </div>

                    <?php else: ?>
                        <p><em>Tipe soal tidak dikenali.</em></p>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <a href="dashboard.php" class="btn-kembali">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>

</body>
</html>