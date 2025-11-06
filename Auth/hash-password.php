<?php
// File: hash_password.php
$password = '12345';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password: $password<br>";
echo "Hash: <strong>$hash</strong><br><br>";
echo "Copy hash di atas dan update ke database!";
?>