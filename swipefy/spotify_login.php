<?php
$clientId = '842ac66fe4094da78713889f28fdd033';
$redirectUri = 'http://127.0.0.1:80/swipefy/spotify_callback.php';
$scopes = 'user-read-private';

$authUrl = 'https://accounts.spotify.com/authorize?' . http_build_query([
    'response_type' => 'code',
    'client_id'     => $clientId,
    'redirect_uri'  => $redirectUri,
    'scope'         => $scopes,
    'show_dialog'   => 'true'
]);
?>
<!DOCTYPE html>
<html lang="ja">
<head><meta charset="UTF-8"><title>Login</title></head>
<body style="background:#121212; color:white; text-align:center; padding-top:100px;">
    <h1>SwipeFy Login</h1>
    <a href="<?php echo $authUrl; ?>" style="background:#1DB954; color:white; padding:15px 30px; border-radius:30px; text-decoration:none; font-weight:bold;">Spotifyでログイン</a>
</body>
</html>