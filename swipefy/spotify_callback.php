<?php
session_start();
$clientId     = '842ac66fe4094da78713889f28fdd033';
$clientSecret = '0b7ea64628514d8aba24852f9f3c947c';

if (isset($_GET['code'])) {
    $url = 'https://accounts.spotify.com/api/token';
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret)],
        CURLOPT_POSTFIELDS     => http_build_query([
            'grant_type'   => 'authorization_code',
            'code'         => $_GET['code'],
            'redirect_uri' => 'http://127.0.0.1:80/swipefy/spotify_callback.php' // ここを修正
        ])
    ]);

    $data = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($data['access_token'])) {
        $_SESSION['at'] = $data['access_token'];
        $_SESSION['rt'] = $data['refresh_token'] ?? '取得失敗';
        header('Location: menu.php');
        exit;
    }
}
echo "取得エラー。";