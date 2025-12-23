<?php
// 前のステップで取得した変数
// $access_token
// $created_playlist_id

// --- STEP 4: 曲を検索する (例: "Official髭男dism Mixed Nuts") ---
$search_query = urlencode('Official髭男dism Mixed Nuts');
$search_url = "https://api.spotify.com/v1/search?q=$search_query&type=track&limit=1";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $search_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $access_token"]);

$search_res = json_decode(curl_exec($ch), true);
$track_uri = $search_res['tracks']['items'][0]['uri'] ?? null;
$track_name = $search_res['tracks']['items'][0]['name'] ?? null;

if (!$track_uri) {
    die("曲が見つかりませんでした。");
}

// --- STEP 5: プレイリストに曲を追加する ---
$add_url = "https://api.spotify.com/v1/playlists/$created_playlist_id/tracks";
$track_data = json_encode(['uris' => [$track_uri]]);

curl_setopt($ch, CURLOPT_URL, $add_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $track_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token,
    'Content-Type: application/json'
]);

$add_res = json_decode(curl_exec($ch), true);
curl_close($ch);

if (isset($add_res['snapshot_id'])) {
    echo "「{$track_name}」をプレイリストに追加しました！<br>";
}

// --- STEP 6: DBに追加した曲の情報を保存 (オプション) ---
try {
    // すでに $pdo が接続されている前提
    $stmt = $pdo->prepare("INSERT INTO playlist_tracks (playlist_id, track_name, track_uri) VALUES (?, ?, ?)");
    $stmt->execute([$created_playlist_id, $track_name, $track_uri]);
    echo "DBに曲情報を保存しました。";
} catch (PDOException $e) {
    echo "DBエラー: " . $e->getMessage();
}
?>