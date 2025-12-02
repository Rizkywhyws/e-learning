<?php
require '../vendor/autoload.php';

session_start();

$client = new Google_Client();
$client->setAuthConfig('../config/google/credentials.json');
$client->setAccessType('offline');
$client->setPrompt('consent');
$client->addScope(Google_Service_Gmail::GMAIL_SEND);

if (!isset($_GET['code'])) {
    $authUrl = $client->createAuthUrl();
    header("Location: $authUrl");
    exit;
}

$client->authenticate($_GET['code']);
$token = $client->getAccessToken();

// simpan token
file_put_contents('../config/google/token.json', json_encode($token));

echo "TOKEN TERSIMPAN!<br>";
echo "<pre>";
print_r($token);
echo "</pre>";
