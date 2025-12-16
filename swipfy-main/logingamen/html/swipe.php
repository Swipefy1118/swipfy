<?php
require_once __DIR__ . '/../db.php';

$title = '初期画面';
$logoSrc = '../img/SwipeFyLogo.png';
$tourokuPath = '../../tourokugamen/html/touroku.php';
$loginPath = 'login.php';

$userCount = null;
$dbQueryError = null;
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $stmt = $pdo->query('SELECT COUNT(*) AS cnt FROM users');
        $row = $stmt->fetch();
        $userCount = isset($row['cnt']) ? (int)$row['cnt'] : 0;
    } catch (Exception $e) {
        $dbQueryError = 'ユーザーデータの取得に失敗しました。';
        // 開発時の詳細: $dbQueryError .= ' ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* 元のスタイルを維持（必要に応じて外部 CSS に移してください） */
        .loginPng
        { 
        width:100%; 
        text-align:left; 
        margin-bottom:16px; 
    }
        .loginPng img 
        { 
            display:inline-block; 
            width:80px; height:40px; 
            margin:0; 
            padding:0; 
        }
            .loginTitle { 
            text-align:center;
            margin-bottom:32px; 
        }
        body{ 
            display:flex; 
            min-height:100vh; 
            align-items:center; 
            justify-content:center; 
            flex-direction:column; 
            margin:0; 
            background-color:black; 
            color:white; 
            text-align:center; 
        }
        .logo{ 
            text-align:center; 
        }
        a{ font-size:50px; 
            color:white; 
            text-decoration:none; 
            border-bottom:10px solid green;
            display:inline-block; 
            margin:40px 12px; 
        }
    </style>
</head>
<body>
    <div class="logoPng">
        <img src="<?php echo htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="slogo">
    </div>

    <div class="logo">
        <h1>こんにちは</h1>
        <p><a href="<?php echo htmlspecialchars($tourokuPath, ENT_QUOTES, 'UTF-8'); ?>">登録</a></p>
        <p><a href="<?php echo htmlspecialchars($loginPath, ENT_QUOTES, 'UTF-8'); ?>">ログイン</a></p>

        <?php if (!empty($dbConnectError)): ?>
            <div style="color:#ffdddd; background:#400; padding:8px; margin-top:16px;">
                <?php echo htmlspecialchars($dbConnectError, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php else: ?>
            <div style="margin-top:16px; font-size:18px; color:#bfe6a2;">
                登録ユーザー数: <?php echo htmlspecialchars((string)($userCount ?? 0), ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <?php if (!empty($dbQueryError)): ?>
                <div style="color:#ffdddd; background:#400; padding:8px; margin-top:8px;">
                    <?php echo htmlspecialchars($dbQueryError, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>