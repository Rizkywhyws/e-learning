<?php
require __DIR__ . '/../vendor/autoload.php';

function sendMail($to, $subject, $messageHtml)
{
    $client = new Google_Client();
    $client->setAuthConfig(__DIR__.'/google/credentials.json');
    $client->addScope(Google_Service_Gmail::GMAIL_SEND);

    // load refresh token
    $tokenPath = __DIR__.'/google/token.json';
    $accessToken = json_decode(file_get_contents($tokenPath), true);
    $client->setAccessToken($accessToken);

    // refresh token jika expired
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }

    $gmail = new Google_Service_Gmail($client);

    $from = "eschool.smk4@gmail.com"; // email API
    $rawMessageString =
        "From: <$from>\r\n" .
        "To: <$to>\r\n" .
        "Subject: $subject\r\n" .
        "Content-Type: text/html; charset=utf-8\r\n\r\n" .
        $messageHtml;

    $mime = rtrim(strtr(base64_encode($rawMessageString), '+/', '-_'), '=');
    $message = new Google_Service_Gmail_Message();
    $message->setRaw($mime);

    return $gmail->users_messages->send('me', $message);
}
