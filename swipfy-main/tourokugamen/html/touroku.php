<?php
$root = '../../';
$css   = '../css/style.css';
$title = '登録画面';
$logoSrc    = '../img/SwipeFyLogo.png';
$loginPath  = $root . 'logingamen/html/login.php';

// db.php のパスを修正（tourokugamen/html から見た swipfy-main/logingamen/db.php）
require_once __DIR__ . '/../../logingamen/db.php';

$errors = [];
$values = [
    'userid'  => '',
    'password'=> '',
    'display' => '',
    'year'    => '',
    'month'   => '',
    'day'     => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 入力取得
    foreach ($values as $k => $_) {
        $values[$k] = isset($_POST[$k]) ? trim((string)$_POST[$k]) : '';
    }

    // バリデーション
    if ($values['userid'] === '') {
        $errors[] = 'ID を入力してください。';
    }
    if ($values['password'] === '') {
        $errors[] = 'パスワードを入力してください。';
    }
    if ($values['display'] === '') {
        $errors[] = '表示名を入力してください。';
    }
    if ($values['year'] === '' || !ctype_digit($values['year']) || (int)$values['year'] < 1900 || (int)$values['year'] > 2100) {
        $errors[] = '正しい年を入力してください。';
    }
    if ($values['month'] === '' || !ctype_digit($values['month']) || (int)$values['month'] < 1 || (int)$values['month'] > 12) {
        $errors[] = '正しい月を入力してください。';
    }
    if ($values['day'] === '' || !ctype_digit($values['day']) || (int)$values['day'] < 1 || (int)$values['day'] > 31) {
        $errors[] = '正しい日を入力してください。';
    }

    // DB 接続エラーがあれば停止
    if (!empty($dbConnectError)) {
        $errors[] = $dbConnectError;
    }

    if (empty($errors)) {
        // // birth を YYYY-MM-DD に
        // $birth = sprintf('%04d-%02d-%02d', (int)$values['year'], (int)$values['month'], (int)$values['day']);

        try {
            // 重複チェック
            $stmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM users WHERE userid = :userid');
            $stmt->execute([':userid' => $values['userid']]);
            $row = $stmt->fetch();
            if ($row && isset($row['cnt']) && (int)$row['cnt'] > 0) {
                $errors[] = 'その ID は既に使われています。';
            } else {
                // パスワードハッシュ化して登録
                $hashed = password_hash($values['password'], PASSWORD_DEFAULT);

                $insert = $pdo->prepare('INSERT INTO users (userid, password, display, year, month, day, created_at) VALUES (:userid, :password, :display, :year, :month, :day, :created_at)');
                $ok = $insert->execute([
                    ':userid' => $values['userid'],
                    ':password' => $hashed,
                    ':display' => $values['display'],
                    ':year' => $values['year'],
                    ':month' => $values['month'],
                    ':day' => $values['day'],
                    ':created_at' => date('Y-m-d H:i:s'),
                ]);

                if ($ok && (int)$pdo->lastInsertId() > 0) {
                    header('Location: ' . $loginPath);
                    exit;
                } else {
                    $errors[] = 'ユーザー情報の保存に失敗しました。';
                    error_log('INSERT failed: ' . json_encode($pdo->errorInfo()));
                }
            }
        } catch (Exception $e) {
            $errors[] = '登録処理中にエラーが発生しました。';
            error_log('touroku exception: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
    <style>
            body {
        background-color: black;
        color: white;
        font-size: 20px;
    }

    a {
        text-align: center;
    }

    .page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        color: #e6e6e6;
        text-align: center;
        font-size: 24px;
    }

    /* カード */
    .card {
        background: #000;
        width: 700px;
        padding: 20px 30px 30px;
        text-align: center;
    }

    /* タイトル */
    .card h1 {
        margin: 0 0 10px;
        color: #fff;
        font-size: 90px;
        padding-bottom: 6px;
        border-bottom: 1px solid #2e2e2e;
    }

    /* ロゴ */
    .logo {
        width: 90px;
        height: auto;
        display: block;
        margin: 0 auto 12px;
    }

    /* 行レイアウト */
    .row {
        display: flex;
        align-items: center;
        margin: 35px 0;
    }

    label {
        width: 120px;
        color: #6fe24a;
        font-weight: 600;
        font-size: 23px;
        text-decoration: underline;
    }

    /* 入力欄 */
    .input {
        flex: 1;
        background: #fff;
        color: #000;
        border: 1px solid #ccc;
        padding: 12px 14px;
        height: 32px;
        font-size: 18px;
        box-sizing: border-box;
    }

    /* 生年月日*/
    .date-of-birth {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        margin-top: 6px;
    }

    .date {
        display: flex;
        gap: 8px;
        flex: 1;
        align-items: center;
    }

    .date-item {
        width: 75px;
    }

    .date-input {
        width: 100%;
        padding: 12px 10px;
        box-sizing: border-box;
        border: 1px solid #ccc;
        background: #fff;
        color: #000;
        height: 32px;
        font-size: 18px;
    }

    /* アクション行 */
    .actions {
        margin-top: 12px;
        align-items: center;
    }

    .spacer {
        flex: 1;
    }

    /* 登録ボタン */
    .btn {
        background: #9cc86c;
        border: 1px solid #6fa23f;
        color: #082004;
        padding: 12px 24px;
        cursor: pointer;
        font-weight: 700;
        font-size: 20px;
    }
    </style>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <style>

    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <img src="../img/SwipeFyLogo.png" alt="SwipeFy" class="logo">
            <h1>登録する</h1>

            <?php if (!empty($errors)): ?>
                <div class="errors">
                    <ul style="margin:0; padding-left:18px;">
                        <?php foreach ($errors as $e): ?>
                            <li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="" method="post" novalidate>
                <div class="row">
                    <label for="userid">ID</label>
                    <input id="userid" name="userid" type="text" class="input" value="<?php echo htmlspecialchars($values['userid'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="row">
                    <label for="password">password</label>
                    <input id="password" name="password" type="password" class="input" value="">
                </div>

                <div class="row">
                    <label for="display">表示名</label>
                    <input id="display" name="display" type="text" class="input" value="<?php echo htmlspecialchars($values['display'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="row" style="align-items:flex-start;">
                    <label>生年月日</label>
                    <div class="date">
                        <div class="date-item">
                            <input name="year" type="number" class="date-input" placeholder="年" min="1900" max="2100" value="<?php echo htmlspecialchars($values['year'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="date-item">
                            <input name="month" type="number" class="date-input" placeholder="月" min="1" max="12" value="<?php echo htmlspecialchars($values['month'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="date-item">
                            <input name="day" type="number" class="date-input" placeholder="日" min="1" max="31" value="<?php echo htmlspecialchars($values['day'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="spacer"></div>
                    <button type="submit" class="btn">登録</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>