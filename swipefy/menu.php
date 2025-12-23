<?php
session_start();
$accessToken = $_SESSION['at'] ?? null;
if (!$accessToken) die("認証が必要です。");



if (!isset($_SESSION['history'])) $_SESSION['history'] = [];
$trackInfo = null;
$action = $_POST['action'] ?? 'next';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'prev' && !empty($_SESSION['history'])) {
        $trackInfo = array_pop($_SESSION['history']);
    } else {
        if (isset($_POST['current_track_json']) && !empty($_POST['current_track_json'])) {
            $currentData = json_decode($_POST['current_track_json'], true);
            if ($currentData) {
                array_push($_SESSION['history'], $currentData);
                if (count($_SESSION['history']) > 15) array_shift($_SESSION['history']);
            }
        }

        $genre = $_POST['genre'] ?? 'j-pop';
        $spotifySearchUrl = "https://api.spotify.com/v1/search?" . http_build_query([
            'q' => "genre:\"$genre\"", 'type' => 'track', 'limit' => 1, 'offset' => rand(0, 500)
        ]);

        $ch = curl_init($spotifySearchUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $accessToken"]);
        $spotifyRes = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (!empty($spotifyRes['tracks']['items'])) {
            $track = $spotifyRes['tracks']['items'][0];
            $itunesSearchUrl = "https://itunes.apple.com/search?" . http_build_query([
                'term' => $track['name'] . " " . $track['artists'][0]['name'], 'entity' => 'song', 'limit' => 1, 'country' => 'JP'
            ]);
            $itunesRes = json_decode(@file_get_contents($itunesSearchUrl), true);

            if ($itunesRes && $itunesRes['resultCount'] > 0) {
                $itunesData = $itunesRes['results'][0];
                $trackInfo = [
                    'name'    => $track['name'],
                    'artist'  => $track['artists'][0]['name'],
                    'image'   => $track['album']['images'][0]['url'],
                    'preview' => $itunesData['previewUrl']
                ];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>SwipeFy Large</title>
    <style>
        body { background: #000; color: #fff; font-family: sans-serif; margin: 0; height: 100vh; display: flex; flex-direction: column; overflow: hidden; }
        
        /* ヘッダー調整 */
        header { display: flex; align-items: center; justify-content: space-between; padding: 10px 20px; flex-shrink: 0; background: #000; z-index: 100; }
        .logo { height: 60px; width: auto; }
        
        .header-right { display: flex; align-items: center; gap: 20px; }
        .icon-btn { background: none; border: none; color: #fff; cursor: pointer; padding: 5px; display: flex; flex-direction: column; align-items: center; text-decoration: none; }
        .icon-btn span { font-size: 10px; margin-top: 4px; opacity: 0.8; }
        
        /* プレイリスト・ハンバーガーの見た目 */
        .playlist-icon { width: 28px; height: 28px; border: 2px solid #fff; border-radius: 4px; position: relative; }
        .playlist-icon::after { content: "≡"; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 20px; }
        
        .hamburger { width: 30px; height: 20px; display: flex; flex-direction: column; justify-content: space-between; background: none; border: none; cursor: pointer; }
        .hamburger div { width: 100%; height: 3px; background-color: #fff; border-radius: 2px; }

        /* 設定サイドメニュー */
        .settings-menu { 
            position: fixed; top: 0; right: -300px; width: 260px; height: 100%; background: #111; 
            box-shadow: -5px 0 15px rgba(0,0,0,0.5); z-index: 200; transition: 0.3s; padding: 20px; box-sizing: border-box;
        }
        .settings-menu.active { right: 0; }
        .menu-close { background: none; border: none; color: #fff; font-size: 30px; cursor: pointer; float: right; }
        .menu-list { list-style: none; padding: 40px 0 0; margin: 0; }
        .menu-list li { padding: 15px 0; border-bottom: 1px solid #333; font-size: 18px; cursor: pointer; }
        .menu-list li:hover { color: #a8d5ba; }

        .overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: none; z-index: 150; }
        .overlay.active { display: block; }

        /* メインコンテンツ */
        main { flex-grow: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; }
        .swipe-wrapper { display: flex; align-items: center; justify-content: center; gap: 15px; width: 100%; max-width: 500px; }
        .side-nav-btn { background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.4); color: #fff; width: 55px; height: 55px; border-radius: 50%; cursor: pointer; font-size: 24px; display: flex; align-items: center; justify-content: center; transition: 0.2s; flex-shrink: 0; }
        .stack-container { position: relative; width: 330px; height: 420px; flex-shrink: 0; }
        .stack-bg { position: absolute; top: -8px; width: 100%; height: 100%; border-radius: 15px; border-top: 5px solid #ff4d4d; background: #222; z-index: 1; }
        .stack-bg2 { position: absolute; top: -16px; width: 100%; height: 100%; border-radius: 15px; border-top: 5px solid #4da3ff; background: #111; z-index: 0; }
        .card { position: relative; z-index: 10; width: 100%; height: 100%; background: #a8d5ba; border-radius: 15px; padding: 20px; box-sizing: border-box; color: #121212; cursor: grab; display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .card img { width: 100%; aspect-ratio: 1/1; border-radius: 10px; pointer-events: none; object-fit: cover; }
        .track-meta { margin-top: 15px; text-align: left; }
        .track-name { font-weight: bold; margin: 0; font-size: 1.3em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .artist-name { font-size: 1.0em; margin: 4px 0; opacity: 0.8; }
        .time-container { display: flex; align-items: center; gap: 10px; margin-top: auto; padding-top: 15px; font-family: monospace; font-size: 14px; }
        input[type="range"] { flex-grow: 1; accent-color: #222; cursor: pointer; height: 6px; }

        .play-center { margin: 35px 0; z-index: 20; }
        .btn-play { width: 95px; height: 95px; background: #ddd; border-radius: 50%; border: none; display: flex; justify-content: center; align-items: center; cursor: pointer; box-shadow: 0 8px 25px rgba(0,0,0,0.7); }
        .play-icon { width: 0; height: 0; border-top: 18px solid transparent; border-bottom: 18px solid transparent; border-left: 30px solid #121212; margin-left: 8px; }
        .pause-icon { width: 26px; height: 32px; border-left: 10px solid #121212; border-right: 10px solid #121212; }

        .controls-bottom { display: flex; justify-content: center; gap: 40px; padding-bottom: 40px; }
        .round-btn { width: 75px; height: 75px; border-radius: 50%; border: none; background: #a8d5ba; display: flex; justify-content: center; align-items: center; font-size: 32px; cursor: pointer; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
    </style>
</head>
<body>

<div class="overlay" id="overlay" onclick="toggleSettings()"></div>
<div class="settings-menu" id="settings-menu">
    <button class="menu-close" onclick="toggleSettings()">×</button>
    <ul class="menu-list">
        <li>アカウント設定</li>
        <li>再生音質</li>
        <li>通知設定</li>
        <li>ライセンス</li>
        <li style="color: #ff4d4d;" onclick="location.href='logout.php'">ログアウト</li>
    </ul>
</div>

<header>
    <img src="SwipeFyLogo.png" alt="SwipeFy Logo" class="logo">
    
    <div class="header-right">
        <a href="playlist.php" class="icon-btn">
            <div class="playlist-icon"></div>
            <span>プレイリスト</span>
        </a>

        <div style="display:flex; align-items:center; gap:8px;">
            <div style="background:#a8d5ba; width:35px; height:35px; border-radius:50%;"></div> 
            <span style="font-size: 14px; font-weight: bold;">userさん</span>
        </div>

        <button class="hamburger" onclick="toggleSettings()">
            <div></div><div></div><div></div>
        </button>
    </div>
</header>

<main>
    <form method="POST" id="main-form" style="width: 100%; display: flex; flex-direction: column; align-items: center;">
        <input type="hidden" name="genre" value="j-pop">
        <input type="hidden" name="action" id="action-field" value="next">
        <input type="hidden" name="current_track_json" value='<?= json_encode($trackInfo) ?>'>

        <div class="swipe-wrapper">
            <button type="button" class="side-nav-btn" onclick="goNext()">❮</button>

            <div class="stack-container">
                <div class="stack-bg2"></div><div class="stack-bg"></div>
                <?php if ($trackInfo): ?>
                    <div class="card" id="card">
                        <img src="<?= $trackInfo['image'] ?>">
                        <div class="track-meta">
                            <p class="track-name"><?= htmlspecialchars($trackInfo['name']) ?></p>
                            <p class="artist-name"><?= htmlspecialchars($trackInfo['artist']) ?></p>
                        </div>
                        <div class="time-container">
                            <span id="current-time">0:00</span>
                            <input type="range" id="seek-bar" value="0" step="0.1">
                            <span id="duration">-0:30</span>
                        </div>
                        <audio id="player" autoplay src="<?= $trackInfo['preview'] ?>"></audio>
                    </div>
                <?php else: ?>
                    <div class="card" style="display:flex; align-items:center; justify-content:center;">
                        <button type="submit" style="background:none; border:3px solid #121212; padding:15px 30px; font-weight:bold; font-size:20px;">START</button>
                    </div>
                <?php endif; ?>
            </div>

            <button type="button" class="side-nav-btn" onclick="goNext()">❯</button>
        </div>

        <div class="play-center">
            <button type="button" class="btn-play" onclick="togglePlay()">
                <div id="btn-icon"><div class="pause-icon"></div></div>
            </button>
        </div>

        <div class="controls-bottom">
            <button type="button" class="round-btn" onclick="goPrev()">↩︎</button>
            <button type="button" class="round-btn" style="color:#ff4d4d;">✖️</button>
            <button type="button" class="round-btn" style="color:#ff00ff;">♥</button>
        </div>
    </form>
</main>

<script>
    const card = document.getElementById('card'), 
        player = document.getElementById('player'), 
        btnIcon = document.getElementById('btn-icon'), 
        seekBar = document.getElementById('seek-bar'), 
        currentTimeText = document.getElementById('current-time'), 
        form = document.getElementById('main-form'),
        settingsMenu = document.getElementById('settings-menu'),
        overlay = document.getElementById('overlay');

    // 設定メニューの表示・非表示
    function toggleSettings() {
        settingsMenu.classList.toggle('active');
        overlay.classList.toggle('active');
    }

    function updatePlayButton() { if (!player) return; btnIcon.innerHTML = player.paused ? '<div class="play-icon"></div>' : '<div class="pause-icon"></div>'; }
    function togglePlay() { if (!player) return; player.paused ? player.play() : player.pause(); updatePlayButton(); }

    if (player) {
        player.addEventListener('loadedmetadata', () => { seekBar.max = player.duration; });
        player.addEventListener('timeupdate', () => {
            seekBar.value = player.currentTime;
            let sec = Math.floor(player.currentTime % 60);
            currentTimeText.innerText = `0:${sec.toString().padStart(2, '0')}`;
        });
        seekBar.addEventListener('input', () => { player.currentTime = seekBar.value; });
        player.addEventListener('ended', () => { goNext(); });
    }

    function goNext() { document.getElementById('action-field').value = 'next'; form.submit(); }
    function goPrev() { document.getElementById('action-field').value = 'prev'; form.submit(); }

    // スワイプ処理
    let isDragging = false, startX = 0;
    const start = (e) => { 
        if(e.target.closest('button') || e.target.closest('input')) return; 
        isDragging = true; startX = e.pageX || e.touches[0].pageX; if(card) card.style.transition = 'none'; 
    };
    const move = (e) => {
        if (!isDragging || !card) return;
        let x = e.pageX || (e.touches ? e.touches[0].pageX : 0);
        let diff = x - startX;
        card.style.transform = `translateX(${diff}px) rotate(${diff / 20}deg)`;
    };
    const end = (e) => {
        if (!isDragging) return;
        isDragging = false;
        if(card) card.style.transition = 'transform 0.3s ease';
        let x = e.changedTouches ? e.changedTouches[0].pageX : e.pageX;
        if (Math.abs(x - startX) > 130) { goNext(); } else { if(card) card.style.transform = ''; }
    };

    if (card) {
        card.addEventListener('mousedown', start);
        window.addEventListener('mousemove', move);
        window.addEventListener('mouseup', end);
        card.addEventListener('touchstart', start);
        card.addEventListener('touchmove', move);
        card.addEventListener('touchend', end);
    }
</script>
</body>
</html>