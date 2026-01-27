<?php
session_start();

// --- 1. 初期設定 ---
$playlist = $_SESSION['playlist'] ?? [];
$defaults = ['genre' => 'j-pop', 'artist' => '', 'tempo' => 'medium'];

if (!isset($_SESSION['filters'])) {
    $_SESSION['filters'] = $defaults;
} else {
    $_SESSION['filters'] = array_merge($defaults, $_SESSION['filters']);
}

if (!isset($_SESSION['seen_ids'])) $_SESSION['seen_ids'] = [];
if (!isset($_SESSION['history'])) $_SESSION['history'] = [];

$filters = $_SESSION['filters'];
$accessToken = $_SESSION['at'] ?? null;

if (!$accessToken){
header('HTTP/1.1 401 Unauthorized');
header('Content-Type: text/html; charset=UTF-8');

echo <<<EOD
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>認証が必要です</title>
    <style>
        body { background-color: #000000; color: #ffffff; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; font-family: sans-serif; flex-direction: column; }
        a { color: #09d32bff; text-decoration: none; margin-top: 20px; border: 1px solid #ffffff; padding: 10px 20px; transition: 0.3s; }
        a:hover { background-color: #ffffff; color: #09d32bff; }
    </style>
</head>
<body>
    <div>認証が必要です</div>
    <a href="spotify_login.php">ログイン画面へ移動する</a>
</body>
</html>
EOD;
exit;
}

if (!isset($_SESSION['user_display_name'])) {
    $ch = curl_init("https://api.spotify.com/v1/me");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $accessToken"]);
    $userData = json_decode(curl_exec($ch), true);
    curl_close($ch);
    $_SESSION['user_display_name'] = $userData['display_name'] ?? 'ユーザー';
    $_SESSION['user_image'] = $userData['images'][0]['url'] ?? ''; 
}

$displayName = $_SESSION['user_display_name'];
$userImage = $_SESSION['user_image'];

$trackInfo = $_SESSION['current_track'] ?? null;
$action = $_POST['action'] ?? 'next';

$addedMessage = false;
if ($action === 'like' && !empty($_POST['current_track_json'])) {
    $likeData = json_decode($_POST['current_track_json'], true);
    if ($likeData) {
        if (!isset($_SESSION['playlist'])) $_SESSION['playlist'] = [];
        $ids = array_column($_SESSION['playlist'], 'id');
        if (!in_array($likeData['id'], $ids)) {
            array_unshift($_SESSION['playlist'], $likeData);
            $addedMessage = true;
        }
    }
    $action = 'next';
}

if (isset($_POST['apply_filters'])) {
    $_SESSION['filters'] = ['genre' => $_POST['f_genre'] ?? 'j-pop', 'artist' => $_POST['f_artist'] ?? '', 'tempo' => $_POST['f_tempo'] ?? 'medium'];
    $_SESSION['seen_ids'] = []; 
    $_SESSION['current_track'] = null;
    header("Location: menu.php?init=1");
    exit;
}

if (isset($_POST['reset_filters'])) {
    $_SESSION['filters'] = $defaults;
    $_SESSION['seen_ids'] = []; 
    $_SESSION['history'] = [];
    $_SESSION['current_track'] = null;
    header("Location: menu.php?init=1");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['init']) || !$trackInfo) {
    if ($action === 'prev' && !empty($_SESSION['history'])) {
        $trackInfo = array_pop($_SESSION['history']);
    } else {
        if ($action === 'next' && !empty($_POST['current_track_json'])) {
            $currentData = json_decode($_POST['current_track_json'], true);
            if ($currentData) {
                array_push($_SESSION['history'], $currentData);
                if (count($_SESSION['history']) > 15) array_shift($_SESSION['history']);
            }
        }
        $max_tries = 3;
        $selected_track = null;
        for ($i = 0; $i < $max_tries; $i++) {
            if (!empty($filters['artist'])) { $q = 'artist:' . $filters['artist']; $offset = 0; }
            else { $g = $filters['genre']; $q = ($g === 'pop') ? 'top hits pop' : (($g === 'k-pop') ? 'k-pop' : "genre:\"$g\""); 
                if ($filters['tempo'] === 'fast') $q .= ' upbeat'; if ($filters['tempo'] === 'slow') $q .= ' chill'; $offset = rand(0, 150); }
            $params = ['q' => $q, 'type' => 'track', 'limit' => 50, 'offset' => $offset, 'market' => 'JP'];
            $ch = curl_init("https://api.spotify.com/v1/search?" . http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $accessToken"]);
            $res = json_decode(curl_exec($ch), true); curl_close($ch);
            if (!empty($res['tracks']['items'])) {
                $pool = $res['tracks']['items'];
                usort($pool, function($a, $b) { return $b['popularity'] <=> $a['popularity']; });
                $new_tracks = array_filter($pool, function($t) { return !in_array($t['id'], $_SESSION['seen_ids']); });
                if (!empty($new_tracks)) {
                    $famous_new = array_slice($new_tracks, 0, 15);
                    $selected_track = $famous_new[array_rand($famous_new)];
                    break;
                }
            }
        }
        if (!$selected_track && isset($pool)) $selected_track = $pool[array_rand($pool)];
        if ($selected_track) {
            $_SESSION['seen_ids'][] = $selected_track['id'];
            if (count($_SESSION['seen_ids']) > 500) array_shift($_SESSION['seen_ids']);
            $clean_name = preg_replace('/[\(\-\[].*$/', '', $selected_track['name']);
            $itunesUrl = "https://itunes.apple.com/search?term=" . urlencode($clean_name . " " . $selected_track['artists'][0]['name']) . "&entity=song&limit=1&country=JP";
            $itunesRes = json_decode(@file_get_contents($itunesUrl), true);
            $trackInfo = [
                'id' => $selected_track['id'], 'name' => $selected_track['name'],
                'artist' => $selected_track['artists'][0]['name'],
                'image' => $selected_track['album']['images'][0]['url'],
                'preview' => $itunesRes['results'][0]['previewUrl'] ?? ''
            ];
        }
    }
    $_SESSION['current_track'] = $trackInfo;
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
        header { display: flex; align-items: center; justify-content: space-between; padding: 10px 20px; flex-shrink: 0; background: #000; z-index: 100; }
        .logo { height: 60px; width: auto; }
        .header-right { display: flex; align-items: center; gap: 20px; }
        .icon-btn { background: none; border: none; color: #fff; cursor: pointer; padding: 5px; display: flex; flex-direction: column; align-items: center; text-decoration: none; transition: transform 0.2s; }
        .icon-btn:active { transform: scale(0.9); }
        .icon-btn span { font-size: 10px; margin-top: 4px; opacity: 0.8; }
        .playlist-icon { width: 28px; height: 28px; border: 2px solid #fff; border-radius: 4px; position: relative; }
        .playlist-icon::after { content: "≡"; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 20px; }
        .hamburger { width: 30px; height: 20px; display: flex; flex-direction: column; justify-content: space-between; background: none; border: none; cursor: pointer; }
        .hamburger div { width: 100%; height: 3px; background-color: #fff; border-radius: 2px; }
        .settings-menu { position: fixed; top: 0; right: -100%; width: 100%; height: 100%; background: #000; z-index: 200; transition: 0.4s cubic-bezier(0.77, 0, 0.175, 1); padding: 40px; box-sizing: border-box; display: flex; flex-direction: column; }
        .settings-menu.active { right: 0; }
        .menu-close { background: none; border: none; color: #fff; font-size: 45px; cursor: pointer; align-self: flex-end; }
        .overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: none; z-index: 150; }
        .overlay.active { display: block; }
        main { flex-grow: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; }
        .swipe-wrapper { display: flex; align-items: center; justify-content: center; gap: 15px; width: 100%; max-width: 500px; }
        
        .side-nav-btn { background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.4); color: #fff; width: 55px; height: 55px; border-radius: 50%; cursor: pointer; font-size: 24px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: 0.2s; }
        .side-nav-btn:active { background: rgba(255,255,255,0.3); transform: scale(0.9); }
        
        .stack-container { position: relative; width: 330px; height: 420px; flex-shrink: 0; perspective: 1000px; }
        .stack-bg { position: absolute; top: -8px; width: 100%; height: 100%; border-radius: 15px; border-top: 5px solid #ff4d4d; background: #222; z-index: 1; }
        .stack-bg2 { position: absolute; top: -16px; width: 100%; height: 100%; border-radius: 15px; border-top: 5px solid #4da3ff; background: #111; z-index: 0; }
        
        /* カードのアニメーション設定 */
        .card { 
            position: relative; z-index: 10; width: 100%; height: 100%; background: #a8d5ba; border-radius: 15px; padding: 20px; box-sizing: border-box; color: #121212; display: flex; flex-direction: column; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            animation: cardEntrance 0.6s ease-out;
        }
        @keyframes cardEntrance {
            from { opacity: 0; transform: translateY(30px) rotateX(-10deg); }
            to { opacity: 1; transform: translateY(0) rotateX(0); }
        }

        .card img { width: 100%; aspect-ratio: 1/1; border-radius: 10px; pointer-events: none; object-fit: cover; }
        .track-meta { margin-top: 15px; }
        .track-name { font-weight: bold; margin: 0; font-size: 1.3em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .artist-name { font-size: 1.0em; margin: 4px 0; opacity: 0.8; }
        .time-container { display: flex; align-items: center; gap: 10px; margin-top: auto; font-family: monospace; }
        #seek-bar { flex-grow: 1; accent-color: #121212; }
        
        .play-center { margin: 35px 0; z-index: 20; }
        .btn-play { width: 95px; height: 95px; background: #fff; border-radius: 50%; border: none; display: flex; justify-content: center; align-items: center; cursor: pointer; transition: 0.2s; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .btn-play:active { transform: scale(0.9); }
        
        .play-icon { width: 0; height: 0; border-top: 18px solid transparent; border-bottom: 18px solid transparent; border-left: 30px solid #121212; margin-left: 8px; }
        .pause-icon { width: 26px; height: 32px; border-left: 10px solid #121212; border-right: 10px solid #121212; }
        
        .controls-bottom { display: flex; justify-content: center; gap: 40px; padding-bottom: 40px; }
        .round-btn { width: 75px; height: 75px; border-radius: 50%; border: none; background: #a8d5ba; display: flex; justify-content: center; align-items: center; font-size: 32px; cursor: pointer; transition: 0.2s; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .round-btn:active { transform: scale(0.85); }
        
        .toast { position: fixed; top: 100px; left: 50%; transform: translateX(-50%); background: rgba(168, 213, 186, 0.95); color: #121212; padding: 12px 25px; border-radius: 30px; font-weight: bold; z-index: 1000; display: none; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
        .fade-in-out { display: block; animation: fadeInOut 2.5s forwards; }
        @keyframes fadeInOut {
            0% { opacity: 0; transform: translate(-50%, -20px); }
            15% { opacity: 1; transform: translate(-50%, 0); }
            85% { opacity: 1; transform: translate(-50%, 0); }
            100% { opacity: 0; transform: translate(-50%, -20px); }
        }
    </style>
</head>
<body>
    <div id="toast" class="toast">プレイリストに追加しました！</div>

<div class="overlay" id="overlay" onclick="toggleSettings()"></div>
<div class="settings-menu" id="settings-menu">
    <button type="button" class="menu-close" onclick="toggleSettings()">×</button>
    <form method="POST" style="display: flex; flex-direction: column; gap: 20px;">
        <h2 style="text-align: center; color: #a8d5ba;">楽曲フィルター</h2>
        <div style="display:flex; flex-direction:column; gap:8px;">
            <label style="font-size:14px; color:#a8d5ba;">アーティスト名</label>
            <input type="text" name="f_artist" id="artist-input" list="artist-list" autocomplete="off" style="padding:12px; border-radius:8px; background:#222; color:#fff; border:1px solid #444;" value="<?= htmlspecialchars($filters['artist']) ?>" placeholder="例: 髭男">
            <datalist id="artist-list"></datalist>
        </div>
        <div style="display:flex; flex-direction:column; gap:8px;">
            <label style="font-size:14px; color:#a8d5ba;">ジャンル</label>
            <select name="f_genre" style="padding:12px; border-radius:8px; background:#222; color:#fff; border:1px solid #444;">
                <option value="j-pop" <?= ($filters['genre']=='j-pop'?'selected':'') ?>>J-POP</option>
                <option value="pop" <?= ($filters['genre']=='pop'?'selected':'') ?>>Pop (洋楽)</option>
                <option value="k-pop" <?= ($filters['genre']=='k-pop'?'selected':'') ?>>K-POP</option>
                <option value="rock" <?= ($filters['genre']=='rock'?'selected':'') ?>>Rock</option>
                <option value="anime" <?= ($filters['genre']=='anime'?'selected':'') ?>>Anime</option>
            </select>
        </div>
        <div style="display:flex; flex-direction:column; gap:8px;">
            <label style="font-size:14px; color:#a8d5ba;">テンポ感</label>
            <select name="f_tempo" style="padding:12px; border-radius:8px; background:#222; color:#fff; border:1px solid #444;">
                <option value="slow" <?= ($filters['tempo']=='slow'?'selected':'') ?>>ゆったり</option>
                <option value="medium" <?= ($filters['tempo']=='medium'?'selected':'') ?>>普通</option>
                <option value="fast" <?= ($filters['tempo']=='fast'?'selected':'') ?>>ノリノリ</option>
            </select>
        </div>
        <button type="submit" name="apply_filters" style="background:#a8d5ba; color:#000; border:none; padding:15px; border-radius:30px; font-weight:bold; cursor:pointer; margin-top: 10px;">設定を保存</button>
        <button type="submit" name="reset_filters" style="background:none; border:none; color:red; cursor:pointer; margin-top: 10px;">履歴をリセット</button>
    </form>
</div>

<header>
    <img src="SwipeFyLogo.png" class="logo">
    <div class="header-right">
        <a href="playlist.php" class="icon-btn">
            <div class="playlist-icon"></div>
            <span>プレイリスト</span>
        </a>
    <div style="display:flex; align-items:center; gap:8px;">
        <?php if ($userImage): ?>
            <img src="<?= $userImage ?>" style="width:35px; height:35px; border-radius:50%; object-fit:cover; border: 1px solid #fff;">
        <?php else: ?>
            <div style="background:#a8d5ba; width:35px; height:35px; border-radius:50%;"></div>
        <?php endif; ?>
        <span style="font-size:14px; font-weight:bold;"><?= htmlspecialchars($displayName) ?>さん</span>
    </div>
        <button class="hamburger" onclick="toggleSettings()">
            <div></div><div></div><div></div>
        </button>
    </div>
</header>

<main>
    <form method="POST" id="main-form" style="width: 100%; display: flex; flex-direction: column; align-items: center;">
        <input type="hidden" name="action" id="action-field" value="next">
        <input type="hidden" name="current_track_json" value='<?= json_encode($trackInfo) ?>'>

        <div class="swipe-wrapper">
            <button type="button" class="side-nav-btn" onclick="goPrev()"><</button>
            <div class="stack-container">
                <div class="stack-bg2"></div><div class="stack-bg"></div>
                <?php if ($trackInfo): ?>
                    <div class="card" id="card">
                        <img src="<?= htmlspecialchars($trackInfo['image']) ?>">
                        <div class="track-meta">
                            <p class="track-name"><?= htmlspecialchars($trackInfo['name']) ?></p>
                            <p class="artist-name"><?= htmlspecialchars($trackInfo['artist']) ?></p>
                        </div>
                        <div class="time-container">
                            <span id="current-time">0:00</span>
                            <input type="range" id="seek-bar" value="0" step="0.1">
                            <span>0:30</span>
                        </div>
                        <audio id="player" autoplay src="<?= htmlspecialchars($trackInfo['preview']) ?>"></audio>
                    </div>
                <?php else: ?>
                    <div class="card" style="justify-content:center; align-items:center;">
                        <button type="submit" name="init" value="1" style="background:none; border:3px solid #121212; padding:15px 40px; font-weight:bold; font-size:24px; cursor:pointer; border-radius:10px;">START</button>
                    </div>
                <?php endif; ?>
            </div>
            <button type="button" class="side-nav-btn" onclick="goNext()">></button>
        </div>

        <div class="play-center">
            <button type="button" class="btn-play" onclick="togglePlay()">
                <div id="btn-icon"><div class="pause-icon"></div></div>
            </button>
        </div>

        <div class="controls-bottom">
            <button type="button" class="round-btn" onclick="goPrev()">↩︎</button>
            <button type="button" class="round-btn" onclick="goNext()" style="color:#ff4d4d;">✖️</button>
            <button type="button" class="round-btn" onclick="goLike()" style="color:#ff00ff;">♥</button>
        </div>
    </form>
</main>

<script>
    const accessToken = "<?= $accessToken ?>";
    const artistInput = document.getElementById('artist-input');
    const artistList = document.getElementById('artist-list');

    artistInput.addEventListener('input', async (e) => {
        const query = e.target.value.trim();
        if (query.length < 2) return;
        const url = `https://api.spotify.com/v1/search?q=${encodeURIComponent(query)}&type=artist&limit=5&market=JP`;
        try {
            const res = await fetch(url, { headers: { 'Authorization': `Bearer ${accessToken}` } });
            const data = await res.json();
            artistList.innerHTML = '';
            if (data.artists && data.artists.items) {
                data.artists.items.forEach(artist => {
                    const option = document.createElement('option');
                    option.value = artist.name;
                    artistList.appendChild(option);
                });
            }
        } catch (err) {}
    });

    const card = document.getElementById('card'), player = document.getElementById('player'), btnIcon = document.getElementById('btn-icon'), seekBar = document.getElementById('seek-bar'), form = document.getElementById('main-form');
    function toggleSettings() { document.getElementById('settings-menu').classList.toggle('active'); document.getElementById('overlay').classList.toggle('active'); }
    function togglePlay() { if (!player) return; player.paused ? player.play() : player.pause(); updatePlayButton(); }
    function updatePlayButton() { if (!player) return; btnIcon.innerHTML = player.paused ? '<div class="play-icon"></div>' : '<div class="pause-icon"></div>'; }
    
    if (player) {
        player.addEventListener('timeupdate', () => { seekBar.value = player.currentTime; let sec = Math.floor(player.currentTime % 60); document.getElementById('current-time').innerText = `0:${sec.toString().padStart(2, '0')}`; });
        player.addEventListener('loadedmetadata', () => seekBar.max = player.duration);
        player.addEventListener('ended', goNext);
    }
    
    function goNext() { 
        if(card) {
            card.style.transition = '0.3s ease-in';
            card.style.transform = 'translateX(500px) rotate(20deg)';
            card.style.opacity = '0';
        }
        setTimeout(() => { document.getElementById('action-field').value = 'next'; form.submit(); }, 200);
    }
    
    function goPrev() { 
        if(card) {
            card.style.transition = '0.3s ease-in';
            card.style.transform = 'translateX(-500px) rotate(-20deg)';
            card.style.opacity = '0';
        }
        setTimeout(() => { document.getElementById('action-field').value = 'prev'; form.submit(); }, 200);
    }
    
    function goLike() {
        document.getElementById('action-field').value = 'like';
        form.submit();
    }

    let isDragging = false, startX = 0;
    const start = (e) => { 
        if(e.target.closest('button') || e.target.closest('input')) return; 
        isDragging = true; 
        startX = e.pageX || e.touches[0].pageX; 
        if(card) card.style.transition = 'none'; 
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
        let x = e.changedTouches ? e.changedTouches[0].pageX : e.pageX; 
        let diff = x - startX;
        if (Math.abs(diff) > 130) { 
            if(diff > 0) goNext(); else goPrev();
        } else { 
            if(card) {
                card.style.transition = '0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                card.style.transform = ''; 
            }
        } 
    };

    if (card) { 
        card.addEventListener('mousedown', start); 
        window.addEventListener('mousemove', move); 
        window.addEventListener('mouseup', end); 
        card.addEventListener('touchstart', start, {passive: true}); 
        card.addEventListener('touchmove', move, {passive: true}); 
        card.addEventListener('touchend', end); 
    }

    <?php if ($addedMessage): ?>
        const toast = document.getElementById('toast');
        toast.classList.add('fade-in-out');
        setTimeout(() => { toast.classList.remove('fade-in-out'); }, 2500);
    <?php endif; ?>
</script>

</body>
</html>