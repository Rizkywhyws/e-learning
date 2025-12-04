<?php
header('Content-Type: application/json');

require_once '../config/db.php';

// Ambil input JSON
$input = json_decode(file_get_contents("php://input"), true);
$email = $input['email'] ?? '';
$oldPassword = $input['oldPassword'] ?? '';

if (empty($email) || empty($oldPassword)) {
    echo json_encode(['success' => false, 'message' => 'Email dan password lama wajib diisi.']);
    exit;
}

// Validasi format email (opsional tapi dianjurkan)
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Format email tidak valid.']);
    exit;
}

// Query untuk mengambil password dari database berdasarkan email
$query = $conn->prepare("SELECT password FROM akun WHERE email = ?");
$query->bind_param("s", $email);
$query->execute();
$result = $query->get_result();

if ($user = $result->fetch_assoc()) {
    // Verifikasi apakah password lama cocok
    if (password_verify($oldPassword, $user['password'])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Password lama salah.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Email tidak ditemukan.']);
}
?>