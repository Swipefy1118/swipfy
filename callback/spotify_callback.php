<?php
// PHPでセッションを開始します。トークンを保持するために必要です。
session_start();

// =========================================================
// 認証情報 (成功した値で固定)
// =========================================================
$clientId  = '842ac66fe4094da78713889f28fdd033';
$clientSecret = '0b7ea64628514d8aba24852f9f3c947c';
// Spotify Dashboardに登録したリダイレクトURIと完全に一致させること
$redirectUri = 'http://127.0.0.1:80/callback/spotify_callback.php'; 

// メイン画面のURL (トークン取得後の移動先)
$mainPageUrl = 'http://127.0.0.1:80/Select_genre/Initial_screen/menu.php';
// =========================================================

// 1. URLから認証コード（code）を取得
$code = $_GET['code'] ?? null;
$error = $_GET['error'] ?? null;

if ($error) {
    // ユーザーが認証を拒否した場合などのエラー処理
    error_log("Spotify認証エラー: " . $error);
    header('Location: ' . $mainPageUrl . '?auth_status=denied');
    exit;
}

if ($code) {
    // 2. 認証コードをアクセストークンに交換する処理
    
    // 🔴 トークン交換エンドポイント
    // (注: URLを元の正しいSpotifyエンドポイントに戻す必要がありますが、
    // ここではデバッグ中に使用したエンドポイントを一時的に保持しています。)
    $ch = curl_init('https://accounts.spotify.com/api/token'); 
    
    // POSTリクエストのデータ
    $postData = http_build_query([
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirectUri // ここも登録URIと一致させる
    ]);
    
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false, 
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            // Client ID と Client Secret を Base64 エンコードして認証
            'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret),
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: ' . strlen($postData)
        ],
        CURLOPT_POSTFIELDS => $postData
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
    $data = json_decode($response, true);

    // 3. トークンが正常に取得されたか確認
    if ($httpCode === 200 && isset($data['access_token'])) {
        // トークンをセッションに保存
        $_SESSION['spotify_access_token'] = $data['access_token'];
        $_SESSION['spotify_refresh_token'] = $data['refresh_token']; 
        $_SESSION['spotify_token_expires'] = time() + $data['expires_in'];
        
        // メイン画面にリダイレクト
        header('Location: ' . $mainPageUrl);
        exit;
    } else {
        // トークン交換中にエラーが発生した場合 (ログに記録)
        error_log("トークン交換失敗。HTTPコード: {$httpCode}, レスポンス: " . print_r($data, true));
        // エラー情報を付けてメイン画面にリダイレクト
        header('Location: ' . $mainPageUrl . '?auth_status=token_error&http_code=' . $httpCode);
        exit;
    }
} else {
    // codeパラメータがない場合は、認証プロセスを始めるよう促す
    echo "認証コードがありません。認証プロセスを最初から開始してください。";
    exit;
}
?>