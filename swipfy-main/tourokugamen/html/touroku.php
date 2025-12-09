<?php
$root = '../../';
$css   = '../css/style.css';
$title = '登録画面';
$logoSrc    = '../img/SwipeFyLogo.png';
$loginPath  = $root . 'logingamen/html/login.php';

// 保存先ディレクトリ・ファイル
$dataDir = __DIR__ . '/../data';
$dataFile = $dataDir . '/users.json';

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

    // ユーザー保存処理
    if (empty($errors)) {
        // ディレクトリ作成
        if (!is_dir($dataDir) && !mkdir($dataDir, 0755, true)) {
            $errors[] = 'データ保存ディレクトリを作成できませんでした。';
        } else {
            $users = [];
            if (file_exists($dataFile)) {
                $json = file_get_contents($dataFile);
                $decoded = json_decode($json, true);
                if (is_array($decoded)) {
                    $users = $decoded;
                }
            }

            // userid の重複チェック
            foreach ($users as $u) {
                if (isset($u['userid']) && $u['userid'] === $values['userid']) {
                    $errors[] = 'その ID は既に使われています。';
                    break;
                }
            }

            if (empty($errors)) {
                $hashed = password_hash($values['password'], PASSWORD_DEFAULT);
                $users[] = [
                    'userid'  => $values['userid'],
                    'password'=> $hashed,
                    'display' => $values['display'],
                    'birth'   => sprintf('%04d-%02d-%02d', (int)$values['year'], (int)$values['month'], (int)$values['day']),
                    'created_at' => date('c')
                ];

                $saved = file_put_contents($dataFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
                if ($saved === false) {
                    $errors[] = 'ユーザー情報の保存に失敗しました。';
                } else {
                    // 保存成功 -> ログインへ遷移または完了メッセージ
                    header('Location: ' . $loginPath);
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($css, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
    <div class="page">
        <div class="card">
            <img src="<?php echo htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="SwipeFy" class="logo">
            <h1>登録する</h1>

            <?php if (!empty($errors)): ?>
                <div class="errors" style="color:#ffdddd;background:#400; padding:10px; margin-bottom:12px;">
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

                <div class="date-of-birth">
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