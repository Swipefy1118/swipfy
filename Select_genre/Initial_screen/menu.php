<?php
// PHPブロックの開始: セッションを開始
session_start();

$clientId = '842ac66fe4094da78713889f28fdd033';

// 💡 修正点 1: ここでは urlencode() を使用しない！
// urlencode() は、http_build_query() に任せる
$rawRedirectUri = 'http://127.0.0.1:80/callback/spotify_callback.php'; 

// 必要なスコープをすべて含める（Web Playerで再生するために必須）
$scopes = 'streaming user-modify-playback-state user-read-currently-playing';
// 💡 修正点 2: スコープのエンコードも http_build_query() に任せるため、URLエンコード済み文字列ではない「生」の文字列を使用

// 認証URLを生成
$authUrl = 'https://accounts.spotify.com/authorize?' . http_build_query([
    'response_type' => 'code',
    'client_id' => $clientId,
    'scope' => $scopes,             // 生のスコープ文字列を使用
    'redirect_uri' => $rawRedirectUri, // 生のURI文字列を使用
    'state' => uniqid(),
    'show_dialog' => true 
]);

// セッションからトークンを取得
$accessToken = $_SESSION['spotify_access_token'] ?? '';
$isLoggedIn = !empty($accessToken);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spotify Web Player</title>
</head>
<body>
    <div class="header">
        <img src="../img/SwipeFyLogo.png" alt="SwipeFyLogo">
        <p>さん</p>
        <a href="https://www.example.com/playlists" style="text-decoration: none; color: inherit;">
            <p>プレイリスト</p>
        </a>
        <a href="https://www.example.com/settings" style="text-decoration: none; color: inherit;">
            <p>設定</p>
        </a>
    </div>

<?php if (!$isLoggedIn): ?>
    <div style="margin: 20px; padding: 20px; border: 1px solid #f00; background-color: #fdd;">
        <p style="color: red;">⚠️ **Spotifyの再生権限がありません。**</p>
        <?php echo '<a href="' . $authUrl . '" style="padding: 10px 20px; background-color: #1DB954; color: white; text-decoration: none; border-radius: 5px; display: inline-block;">Spotifyでログインして再生権限を付与する</a>'; ?>
    </div>
<?php endif; ?>

<div class="player-controls" style="margin-top: 20px;">
    <button onclick="togglePlayback()" id="play-pause-btn"
            style="padding: 10px 20px; font-size: 16px; margin-right: 10px; cursor: pointer;"
            <?php echo !$isLoggedIn ? 'disabled' : ''; ?>>
        ▶️ 任意の曲を再生（Bohemian Rhapsody）
    </button>
</div>

<div id="current-track-info" style="margin: 20px; padding: 10px; border: 1px solid #ccc; display: flex;">
    <?php if (!$isLoggedIn): ?>
        <p style="margin: auto; color: red;">再生にはSpotifyへのログインが必要です。</p>
    <?php else: ?>
        Web Playerの準備を待っています...
    <?php endif; ?>
</div>

<script src="https://community.spotify.com/t5/Spotify-for-Developers/error-401-permission-missing/td-p/56058377"></script>

<script>
    // 🔴 トークンをPHPから動的に埋め込む 🔴
    const token = '<?php echo $accessToken; ?>'; 
    
    let player; 
    let deviceId = null; 
    
    const defaultTrackUri = 'spotify:track:3zT6GfX040N0n3q6N1iA8E'; // Queen - Bohemian Rhapsody
    
    // トークンがない場合は、SDKの初期化をスキップ
    if (token === '') {
        console.error("アクセストークンがありません。ログインしてください。");
        document.getElementById('play-pause-btn').disabled = true;
    } else {
        // --- SDK初期化ロジック ---
        window.onSpotifyWebPlaybackSDKReady = () => {
            player = new Spotify.Player({
                name: 'SwipeFy Web Player',
                getOAuthToken: cb => { cb(token); }, // 埋め込まれたトークンを使用
                volume: 0.5
            });

            player.addListener('ready', ({ device_id }) => {
                deviceId = device_id;
                console.log('Web Playback SDK Ready. Device ID:', deviceId);
                document.getElementById('current-track-info').innerHTML = '<p style="margin: auto;">Web Playerが準備できました。再生ボタンを押してください。</p>';
                document.getElementById('play-pause-btn').disabled = false; 
            });

            player.addListener('authentication_error', ({ message }) => { 
                console.error('SDK認証エラー:', message);
                document.getElementById('current-track-info').innerHTML = `<p style="color: red;">認証エラー: トークンが無効か、スコープが不足しています（${message}）。</p>`;
                document.getElementById('play-pause-btn').disabled = true;
            });

            // 🔴 再生状態の変更イベント
            player.addListener('player_state_changed', state => {
                const btn = document.getElementById('play-pause-btn');
                const trackInfoDiv = document.getElementById('current-track-info');

                if (state && state.track_window.current_track) {
                    const track = state.track_window.current_track;
                    const album = track.album;
                    
                    const imageUrl = album.images.length > 0 ? album.images[0].url : 'https://via.placeholder.com/300?text=No+Image';
                    const artists = track.artists.map(a => a.name).join(', ');
                    
                    trackInfoDiv.style.display = 'flex';
                    trackInfoDiv.style.alignItems = 'center';

                    trackInfoDiv.innerHTML = `
                        <img src="${imageUrl}" alt="${album.name} Album Art" 
                             style="width: 120px; height: 120px; margin-right: 15px; border-radius: 4px;">
                        <div class="track-details">
                            <h3 style="margin-top: 0;">🎶 Now Playing</h3>
                            <p><strong>曲名:</strong> ${track.name}</p>
                            <p><strong>歌手名:</strong> ${artists}</p>
                            <p><strong>アルバム:</strong> ${album.name}</p>
                            <p style="font-size: 0.8em; color: #666;">（Web Playerで再生中）</p>
                        </div>
                    `;
                } else if (state) {
                    trackInfoDiv.innerHTML = '<p style="margin: auto;">Web Playerが準備できました。再生ボタンを押してください。</p>';
                }

                if (state && !state.paused) {
                    btn.innerHTML = '⏸️ 再生中';
                } else {
                    btn.innerHTML = '▶️ 任意の曲を再生（Bohemian Rhapsody）';
                }
            });

            player.connect();
        };
    } // else (トークンがある場合) の終わり
    

    // --- fetchWebApi 関数 ---
    async function fetchWebApi(endpoint, method, body) {
        // 🔴 修正済み: 正しいSpotify Web APIのベースURLを使用 🔴
        const apiUrl = `https://api.spotify.com/$${endpoint}`; 
        
        const res = await fetch(apiUrl, {
            headers: {
                Authorization: `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            method,
            body: body ? JSON.stringify(body) : null
        });

        if (res.status === 204) { return ''; }
        if (!res.ok) {
            const errorBody = await res.json();
            throw new Error(`API Error: ${res.status} - ${errorBody.error ? errorBody.error.message : res.statusText}`);
        }
        return res.status === 200 ? await res.json() : ''; 
    } 


    // --- 再生制御ロジック ---
    async function startPlayback(trackUri) {
        if (!deviceId) {
            alert('Web Playerがまだ準備できていません。接続を待ってください。');
            return;
        }

        try {
            // Web API (fetchWebApi) を使って再生開始をリクエスト
            await fetchWebApi(`v1/me/player/play?device_id=${deviceId}`, 'PUT', {
                uris: [trackUri || defaultTrackUri],
                position_ms: 0
            });
            console.log('SDKで再生開始:', trackUri || defaultTrackUri);
        } catch (error) {
            console.error('再生開始エラー:', error);
            alert(`再生開始に失敗しました。トークン/権限（特にstreamingスコープ）を確認してください: ${error.message}`);
        }
    }

    function togglePlayback() {
        if (!player || !deviceId) {
            alert('Web Playerの接続が完了していません。');
            return;
        }

        player.getCurrentState().then(state => {
            if (state) {
                if (state.paused) {
                    // 一時停止状態の場合
                    if (state.track_window.current_track.uri === defaultTrackUri) {
                         // 現在の曲をレジューム
                         player.resume();
                    } else {
                        // 別の曲を再生
                        startPlayback(defaultTrackUri);
                    }
                } else {
                    // 再生中の場合
                    player.pause();
                }
            } else {
                // プレイヤーに何も設定されていない場合
                startPlayback(defaultTrackUri);
            }
        });
    }

    function fetchCurrentTrack() {
        // SDKが再生情報を処理するため、この関数は不要
        console.log("Web Playback SDKが再生情報を処理します。");
    }
</script>
</body>
</html>