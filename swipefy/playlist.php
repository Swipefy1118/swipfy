<?php
session_start();
$playlist = $_SESSION['playlist'] ?? [];

// 削除処理
if (isset($_POST['delete_id'])) {
    $_SESSION['playlist'] = array_filter($_SESSION['playlist'], function($item) {
        return $item['id'] !== $_POST['delete_id'];
    });
    header("Location: playlist.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Playlist - SwipeFy</title>
    <style>
        body { background: #000; color: #fff; font-family: sans-serif; margin: 0; padding: 20px; }
        header { display: flex; align-items: center; margin-bottom: 30px; }
        .back-btn { color: #a8d5ba; text-decoration: none; font-size: 24px; margin-right: 20px; }
        h1 { font-size: 24px; margin: 0; }
        
        .playlist-container { display: flex; flex-direction: column; gap: 15px; }
        .track-item { 
            display: flex; align-items: center; background: #111; 
            padding: 10px; border-radius: 10px; border-left: 4px solid #a8d5ba;
        }
        .track-item img { width: 60px; height: 60px; border-radius: 5px; margin-right: 15px; }
        .track-info { flex-grow: 1; min-width: 0; }
        .track-name { font-weight: bold; margin: 0; font-size: 16px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .artist-name { font-size: 13px; color: #aaa; margin: 4px 0 0; }
        
        .play-preview { background: none; border: 1px solid #a8d5ba; color: #a8d5ba; border-radius: 20px; padding: 5px 12px; font-size: 12px; cursor: pointer; margin-right: 10px; }
        .del-btn { background: none; border: none; color: #ff4d4d; font-size: 20px; cursor: pointer; }
        
        .empty-msg { text-align: center; color: #666; margin-top: 100px; }
    </style>
</head>
<body>

<header>
    <a href="menu.php" class="back-btn">❮</a>
    <h1>My Playlist</h1>
</header>

<div class="playlist-container">
    <?php if (empty($playlist)): ?>
        <div class="empty-msg">
            <p>まだお気に入りの曲がありません。<br>スワイプして曲を探そう！</p>
        </div>
    <?php else: ?>
        <?php foreach ($playlist as $track): ?>
            <div class="track-item">
                <img src="<?= htmlspecialchars($track['image']) ?>">
                <div class="track-info">
                    <p class="track-name"><?= htmlspecialchars($track['name']) ?></p>
                    <p class="artist-name"><?= htmlspecialchars($track['artist']) ?></p>
                </div>
                
                <?php if ($track['preview']): ?>
                    <button class="play-preview" onclick="playAudio('<?= $track['preview'] ?>')">試聴</button>
                <?php endif; ?>

                <form method="POST" style="margin:0;">
                    <input type="hidden" name="delete_id" value="<?= $track['id'] ?>">
                    <button type="submit" class="del-btn">×</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    let currentAudio = null;
    function playAudio(url) {
        if (currentAudio) {
            currentAudio.pause();
            if (currentAudio.src === url) {
                currentAudio = null;
                return;
            }
        }
        currentAudio = new Audio(url);
        currentAudio.play();
    }
</script>

</body>
</html>