<?php
$root = '../../';             
$css1 = 'css/reset.css';
$css2 = '../css/style.css';
$title = '初期画面';
$logoSrc = '../img/SwipeFyLogo.png';
$tourokuPath = $root . 'tourokugamen/html/touroku.php';
$loginPath = 'login.php';
?> 
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($css1, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($css2, ENT_QUOTES, 'UTF-8'); ?>">
</head>

<body>
    <div class="logoPng">
        <img src="<?php echo htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="slogo">
    </div>
    <div class="logo">
        <h1>こんにちは</h1>
        <p><a href="<?php echo htmlspecialchars($tourokuPath, ENT_QUOTES, 'UTF-8'); ?>">登録</a></p>
        <p><a href="<?php echo htmlspecialchars($loginPath, ENT_QUOTES, 'UTF-8'); ?>">ログイン</a></p>
    </div>
</body>

</html>