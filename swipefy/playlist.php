<?php
// すでに取得済みのトークン
// $access_token = $_SESSION['at'] ?? null;
// if (!$accessToken) die("認証が必要です。");
$access_token = 'BQAmwJPnm66S5JV6r2RnYDzNV05nrN46O31UPLe6GrdCIOCNQXIHSBcUCMiqxRxle1w0Wml_t5JHFW_ACXiTNVfR6f41ux0zOd1LqLv4OSQ0U3YpCWTk6pv1-V0CzbSS5duDQsYBx4cy26NnWeYSY_-QtYP73efU06fS5N3iNizzc4W_kSe2wgV8qCNumWzACscvA0IM8TueP6pO41SzrgSPGUlw31qLmSjHVuz3LXvSozQ_UOKZQ0C9vU_v2CUa49Qk__lj3DuhKFzWzXFXMaCQfUnqCMkD7C8zmtUXQZZxh3Zv4aJogmT78vbkl0USvc3V';
// --- STEP 1: ユーザーIDを取得 ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.spotify.com/v1/me');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token
]);
$user_res = json_decode(curl_exec($ch), true);
$spotify_user_id = $user_res['id']; // あなたのSpotifyユーザーID


// --- STEP 2: プレイリストを新規作成 ---
$playlist_data = json_encode([
    'name'        => 'My New XAMPP Playlist', // プレイリスト名
    'description' => 'Created via PHP script',
    'public'      => true
]);

curl_setopt($ch, CURLOPT_URL, "https://api.spotify.com/v1/users/$spotify_user_id/playlists");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $playlist_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token,
    'Content-Type: application/json'
]);

$playlist_res = json_decode(curl_exec($ch), true);
curl_close($ch);

// 作成されたプレイリストのIDを取得
if (isset($playlist_res['id'])) {
    $created_playlist_id = $playlist_res['id'];
    $created_playlist_name = $playlist_res['name'];
    
    echo "Spotify上にプレイリストを作成しました。ID: " . $created_playlist_id . "<br>";
} else {
    die("エラー：プレイリストの作成に失敗しました。");
}


// --- STEP 3: MySQL(DB)へ保存 ---
$host = 'localhost';
$dbname = 'your_database_name'; // あなたのDB名
$user = 'root';                 // XAMPPのデフォルト
$pass = '';                     // XAMPPのデフォルト

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    
    $sql = "INSERT INTO playlists (spotify_id, playlist_name, created_at) VALUES (:id, :name, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id'   => $created_playlist_id,
        ':name' => $created_playlist_name
    ]);
    
    echo "データベースへの保存も完了しました！";
} catch (PDOException $e) {
    echo "DBエラー: " . $e->getMessage();
}
?>