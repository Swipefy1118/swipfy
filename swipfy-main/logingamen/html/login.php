<?php
require_once __DIR__ . '/../db.php';
session_start();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // フォームの name が username か userid か両方に対応
    $userid = isset($_POST['userid']) ? trim((string)$_POST['userid']) : (isset($_POST['username']) ? trim((string)$_POST['username']) : '');
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if ($userid === '' || $password === '') {
        $errors[] = 'ID とパスワードを入力してください。';
    } elseif (!empty($dbConnectError)) {
        $errors[] = $dbConnectError;
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, userid, password FROM users WHERE userid = :userid LIMIT 1');
            $stmt->execute([':userid' => $userid]);
            $user = $stmt->fetch();

            if ($user && isset($user['password']) && password_verify($password, $user['password'])) {
                // ログイン成功
                $_SESSION['userid'] = $user['userid'];
                $_SESSION['user_id'] = $user['id'] ?? null;
                header('Location: ../../test.php');
                exit;
            } else {
                $errors[] = 'ID またはパスワードが正しくありません。';
            }
        } catch (Exception $e) {
            $errors[] = '認証処理中にエラーが発生しました。';
            // error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .loginPng {
    width: 100%;
    text-align: left;
    margin-bottom: 16px;
}
.loginPng img {
    display: inline-block;
    width: 80px;
    height: 40px;
    margin: 0;
    padding: 0;
}
.loginTitle {
    text-align: center;
    margin-bottom: 32px;
}
body{
    display: flex;
    min-height: 100vh; 
    align-items: center; 
    justify-content: center; 
    flex-direction: column; 
    margin: 0;
    background-color: black;
    color: white;
    text-align: center;
}
.loginPng{
    display: block;
    margin-right: 30px;
    margin-bottom: 20px;
    width: 50%;
    height: auto;
}

.loginPng{
    position: relative;
}

.loginPng img{
    display: block;
    width: 80px; 
    height: 40px;
    margin: 0;
    padding: 0;
}
.loginForm .input{
    font-size: 30px;
    flex: 1;
}
.logo{
    text-align: center;
}
a{
    font-size: 50px;
    color: white;
    text-decoration: none;
    border-bottom: 10px solid green;
    display: inline-block; 
    margin: 40px 12px; 
}


.loginForm{
    width: 520px; 
    margin: 0 auto;
    text-align: left; 
}

.loginForm .user,
.loginForm .password{
    margin-bottom: 16px;
}

.loginForm label{
    display: inline-block;
    width: 140px; 
    font-size: 18px;
    vertical-align: middle;
}

.loginForm input[type="text"],
.loginForm input[type="password"],
.loginForm input[type="email"]{
    display: inline-block;
    width: 340px; 
    padding: 8px;
    font-size: 16px;
    box-sizing: border-box;
}
.loginSubmit{
    text-align: center;
    margin-top: 24px;
    color: white;
}

/* ログインボタンを緑にするスタイル */
.loginSubmit input[type="submit"]{
    background-color: #28a745; 
    color: #ffffff;
    border: none;
    padding: 12px 28px;
    font-size: 18px;
    border-radius: 6px;
    cursor: pointer;
    box-shadow: 0 2px 0 rgba(0,0,0,0.15);
}

.loginSubmit input[type="submit"]:hover{
    background-color: #218838; 
}



/* キーボード操作時のフォーカス*/
.loginSubmit input[type="submit"]:focus{
    outline: 3px solid rgba(40,167,69,0.25);
    outline-offset: 2px;
}
.errorBox{ color:#ffdddd; background:#400; padding:10px; margin-bottom:12px; }
    </style>
</head>
<body>
    <div class="loginPng">
        <img src="../img/SwipeFyLogo.png" alt="slogo" style="width:80px;height:40px;">
    </div>
    <h1 class="loginTitle">ログイン</h1>

    <?php if (!empty($errors)): ?>
        <div class="errorBox">
            <ul style="margin:0;padding-left:18px;">
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="" method="post" class="loginForm" autocomplete="off">
        <div class="user">
            <label for="username">ID</label>
            <input type="text" name="username" id="username" required value="<?php echo isset($userid) ? htmlspecialchars($userid, ENT_QUOTES, 'UTF-8') : ''; ?>">
        </div>
        <div class="password" style="margin-top:12px;">
            <label for="password">password</label>
            <input type="password" name="password" id="password" required>
        </div>
        <div class="loginSubmit" style="text-align:center;margin-top:20px;">
            <input type="submit" value="ログイン" style="background:#28a745;color:#fff;padding:12px 28px;border:none;border-radius:6px;cursor:pointer;">
        </div>
    </form>
</body>
</html>