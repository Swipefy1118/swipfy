<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>好きなジャンルを選択</title>
    <style>
        html {
    background-color: black;
    color: white;
}

.header {
    padding: 10px;
    text-align: center;
}

.header img {
    width: 150px;
    height: auto;
}

.Genre form {
    width: fit-content; 
    margin: 0 auto;
}

.Genre .check {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    column-gap: 120px; 
    padding-bottom: 40px;
}

.genre-item {
    display: flex; 
    align-items: center;
    padding: 5px;
    gap: 40px; /* ラベルとチェックボックスの間の隙間 */
}

.genre-item label {
    font-size: 30px;
    flex-grow: 1; 
    text-align: left; /* テキストは左揃えにする */
}

.genre-item input[type="checkbox"] {
    transform: scale(2.5);
    flex-shrink: 0;
}

.genre-text {
    width: 100%; 
    display: flex;
    margin-top: 20px;
    gap: 30px;
    justify-content: center;
    padding-bottom: 60px;
}
.genre-text label {
    font-size: 24px;
    margin-bottom: 10px;
}
.genre-text input[type="text"] {
    width: 300px;
    height: 40px;
    font-size: 20px;
}

.skip-link {
    text-align: center;
    margin-top: 20px;
}
.skip-link a{
    color: #ffffff;
    text-decoration: none;
}

.skip-link a:hover {
    color: #8f8b8b;
}



.submit {
    text-align: center;
    margin-top: 20px;
    padding-bottom: 30px;
}

.submit input[type="submit"] {
    padding: 10px 20px;
    font-size: 16px;
    cursor: pointer;
}
    </style>
</head>

<body>
    <div class="header">
        <img src="../img/SwipeFyLogo.png" alt="SwipeFyLogo">
        <h1>好きなジャンルを選択(複数選択可)</h1>
    </div>

    <div class="Genre">
        <form action="second_screen.html" method="get">
            <div class="check">
                <div class="genre-item">
                    <label for="action1">pop</label>
                    <input type="checkbox" id="action1" name="genre" value="アクション1">
                </div>
                <div class="genre-item">
                    <label for="comedy1">J-pop</label>
                    <input type="checkbox" id="comedy1" name="genre" value="コメディ1">
                </div>

                <div class="genre-item">
                    <label for="drama1">K-pop</label>
                    <input type="checkbox" id="drama1" name="genre" value="ドラマ1">
                </div>
                <div class="genre-item">
                    <label for="horror1">Rock</label>
                    <input type="checkbox" id="horror1" name="genre" value="ホラー1">
                </div>

                <div class="genre-item">
                    <label for="action2">hip-pop</label>
                    <input type="checkbox" id="action2" name="genre" value="アクション2">
                </div>
                <div class="genre-item">
                    <label for="comedy2">blues</label>
                    <input type="checkbox" id="comedy2" name="genre" value="コメディ2">
                </div>

                <div class="genre-item">
                    <label for="drama2">Classical</label>
                    <input type="checkbox" id="drama2" name="genre" value="ドラマ2">
                </div>
                <div class="genre-item">
                    <label for="horror2">jazz</label>
                    <input type="checkbox" id="horror2" name="genre" value="ホラー2">
                </div>
            </div>
            <div class="genre-text">
                <label for="horror2">その他</label>
                <input type="text" id="username" name="username" placeholder="何かありますか？">
            </div>
        </form>
    </div>

    <div class="submit">
        <input type="submit" value="次へ">
    </div>
    </form>

    <h2 class="skip-link">
        <a href="second_screen.html">ジャンル選択をスキップして次へ</a>
    </h2>
</body>

</html>