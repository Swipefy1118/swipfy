<?php
$host = "localhost";
$dbname = "swipefy";  
$user = "root";
$pass = "";  
$option = array(
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
PDO::ATTR_EMULATE_PREPARES => false,
PDO::ATTR_STRINGIFY_FETCHES => false
);




try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass,$option);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    echo "データベース接続成功！";
} catch (PDOException $e) {
    echo "接続失敗: " . $e->getMessage();
}
