<?php
// cron_kirim_notifikasi_presensi.php
// Jalankan: php cron_kirim_notifikasi_presensi.php

date_default_timezone_set('Asia/Jakarta');

require __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/config/db.php'; // pastikan $conn = mysqli_connect(...) tersedia

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;

// ----- helper: dapatkan Gmail service -----
function getGmailService() {
    $client = new Client();
    $client->setApplicationName('Presensi E-Learning');
    $client->setScopes(Gmail::GMAIL_SEND);
    $client->setAuthConfig(__DIR__ . '/config/google/credentials.json');
    $client->setAccessType('offline');

    $tokenPath = __DIR__ . '/config/google/token.json';
    if (!file_exists($tokenPath)) {
        throw new Exception("token.json tidak ditemukan. Jalankan oauth2callback.php dahulu.");
    }
    $accessToken = json_decode(file_get_contents($tokenPath), true);
    $client->setAccessToken($accessToken);

    if ($client->isAccessTokenExpired()) {
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        } else {
            throw new Exception("Access token expired dan refresh token tidak ada. Re-auth diperlukan.");
        }
    }

    return new Gmail($client);
}

// ----- helper: kirim email via Gmail API -----
function sendGmailRaw($service, $fromEmail, $toEmail, $subject, $htmlBody) {
    $rawMessageString = "From: <$fromEmail>\r\n";
    $rawMessageString .= "To: <$toEmail>\r\n";
    $rawMessageString .= "Subject: $subject\r\n";
    $rawMessageString .= "MIME-Version: 1.0\r\n";
    $rawMessageString .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $rawMessageString .= $htmlBody;

    $raw = rtrim(strtr(base64_encode($rawMessageString), '+/', '-_'), '=');

    $msg = new Message();
    $msg->setRaw($raw);
    return $service->users_messages->send('me', $msg);
}

// ----- helper: catat email agar tidak dikirim dua kali -----
// tabel sederhana email_log: (jika belum ada, kita buat)
function ensureEmailLogTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS email_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        idBuatPresensi VARCHAR(100) NULL,
        NIS VARCHAR(100) NULL,
        type VARCHAR(50) NOT NULL,    -- 'reminder','telat','alpa'
        sent_at DATETIME NOT NULL,
        UNIQUE KEY uniq_log (idBuatPresensi, NIS, type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conn, $sql);
}

function hasSentLog($conn, $idBuat, $NIS, $type) {
    $stmt = $conn->prepare("SELECT 1 FROM email_log WHERE idBuatPresensi = ? AND NIS = ? AND type = ? LIMIT 1");
    $stmt->bind_param('sss', $idBuat, $NIS, $type);
    $stmt->execute();
    $res = $stmt->get_result();
    $exists = $res->num_rows > 0;
    $stmt->close();
    return $exists;
}

function insertLog($conn, $idBuat, $NIS, $type) {
    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT IGNORE INTO email_log (idBuatPresensi, NIS, type, sent_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $idBuat, $NIS, $type, $now);
    $stmt->execute();
    $stmt->close();
}

// ----- mulai skrip utama -----
try {
    $gmail = getGmailService();
} catch (Exception $e) {
    echo "Gagal inisialisasi Gmail API: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Ganti dengan email yang kamu pakai untuk OAuth (From)
$EMAIL_FROM = 'eschool.smk4@gmail.com';

// pastikan tabel email_log ada
ensureEmailLogTable($conn);

// Ambil semua buatpresensi yang relevan:
// Kita ambil yang waktuDimulai IS NOT NULL (sudah dibuat), dan waktuDitutup IS NOT NULL (ada batas).
// Kamu bisa mengubah kriteria WHERE sesuai kebutuhan (mis. aktif=1)
$now = time();
$sqlBP = "SELECT b.idBuatPresensi, b.idJadwalMapel, b.waktuDimulai, b.waktuDitutup, b.toleransiWaktu, j.kelas, j.kodeMapel
          FROM buatpresensi b
          JOIN jadwalmapel j ON b.idJadwalMapel = j.idJadwalMapel
          WHERE b.waktuDimulai IS NOT NULL";
$resBP = mysqli_query($conn, $sqlBP);
if (!$resBP) { echo "Query buatpresensi error: " . mysqli_error($conn) . PHP_EOL; exit; }

while ($bp = mysqli_fetch_assoc($resBP)) {
    $idBuat = $bp['idBuatPresensi'];
    $kelas = $bp['kelas'];
    $kodeMapel = $bp['kodeMapel'];
    $wMulai = strtotime($bp['waktuDimulai']);
    $wTutup = $bp['waktuDitutup'] ? strtotime($bp['waktuDitutup']) : null;
    $toleransi = intval($bp['toleransiWaktu']);
    $deadline = $wMulai + ($toleransi * 60);

    // ambil semua siswa di kelas tersebut
    $stmtS = $conn->prepare("SELECT d.NIS, d.nama, a.email FROM datasiswa d JOIN akun a ON d.idAkun = a.idAkun WHERE d.kelas = ?");
    $stmtS->bind_param('s', $kelas);
    $stmtS->execute();
    $rs = $stmtS->get_result();

    while ($s = $rs->fetch_assoc()) {
        $NIS = $s['NIS'];
        $nama = $s['nama'];
        $email = $s['email'];

        // cek apakah ada presensi siswa untuk pertemuan ini
        $stmtP = $conn->prepare("SELECT waktuPresensi FROM presensisiswa WHERE idBuatPresensi = ? AND NIS = ? LIMIT 1");
        $stmtP->bind_param('ss', $idBuat, $NIS);
        $stmtP->execute();
        $resP = $stmtP->get_result();

        $now = time();

        if ($resP->num_rows == 0) {
            // TIDAK ADA presensi

            if ($wTutup !== null && $now > $wTutup) {
                // melewati waktuDitutup -> kirim ALFA (jika belum pernah dikirim)
                if (!hasSentLog($conn, $idBuat, $NIS, 'alpa')) {
                    $subject = "Pemberitahuan: Status Presensi Anda ALPA";
                    $body = "<p>Halo <b>$nama</b>,</p>"
                          . "<p>Presensi untuk mata pelajaran <b>$kodeMapel</b> (ID: $idBuat) telah ditutup dan kami mencatat Anda <b>ALPA</b>.</p>"
                          . "<p>Jika ada kesalahan, hubungi guru atau admin.</p>";

                    try {
                        sendGmailRaw($gmail, $EMAIL_FROM, $email, $subject, $body);
                        insertLog($conn, $idBuat, $NIS, 'alpa');
                        echo "ALPA dikirim ke $NIS ($email)" . PHP_EOL;
                    } catch (Exception $e) {
                        echo "Gagal kirim ALPA ke $email: " . $e->getMessage() . PHP_EOL;
                    }
                }
            } elseif ($now > $deadline) {
                // sudah melewati deadline namun belum sampai waktuDitutup -> kirim pengingat telat (jika belum pernah)
                if (!hasSentLog($conn, $idBuat, $NIS, 'reminder')) {
                    $subject = "Pengingat: Anda Belum Melakukan Presensi";
                    $body = "<p>Halo <b>$nama</b>,</p>"
                          . "<p>Anda belum melakukan presensi untuk mata pelajaran <b>$kodeMapel</b>. Batas toleransi telah terlewati.</p>"
                          . "<p>Silakan lakukan presensi segera sebelum presensi ditutup.</p>";

                    try {
                        sendGmailRaw($gmail, $EMAIL_FROM, $email, $subject, $body);
                        insertLog($conn, $idBuat, $NIS, 'reminder');
                        echo "Reminder dikirim ke $NIS ($email)" . PHP_EOL;
                    } catch (Exception $e) {
                        echo "Gagal kirim reminder ke $email: " . $e->getMessage() . PHP_EOL;
                    }
                }
            } // else belum melewati deadline -> do nothing

        } else {
            // ADA presensi
            $rowP = $resP->fetch_assoc();
            $wPres = strtotime($rowP['waktuPresensi']);

            // baca status presensi
            $statusDB = $rowP['status'];

            if ($statusDB == 'sakit' || $statusDB == 'izin') {
                // jangan kirim notif telat
                continue;
            } if ($wPres <= $deadline) {
             // hadir tepat waktu
            } else {
                // presensi ada tapi waktuPresensi > deadline => siswa sudah absen tapi terlambat
                // kita kirim notifikasi TELAT sekali jika belum dikirim
                if (!hasSentLog($conn, $idBuat, $NIS, 'telat')) {
                    $subject = "Pemberitahuan: Anda Terlambat Presensi";
                    $body = "<p>Halo <b>$nama</b>,</p>"
                          . "<p>Anda telah melakukan presensi pada <b>" . date('Y-m-d H:i:s', $wPres) . "</b>, namun setelah batas toleransi (".$toleransi." menit).</p>";

                    try {
                        sendGmailRaw($gmail, $EMAIL_FROM, $email, $subject, $body);
                        insertLog($conn, $idBuat, $NIS, 'telat');
                        echo "Telat notif dikirim ke $NIS ($email)" . PHP_EOL;
                    } catch (Exception $e) {
                        echo "Gagal kirim telat notif ke $email: " . $e->getMessage() . PHP_EOL;
                    }
                }
            }
        }

        $stmtP->close();
    }

    $stmtS->close();
}

echo "Selesai process notifikasi presensi." . PHP_EOL;
