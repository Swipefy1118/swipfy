<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>好きなアーティストを選択</title>
    <style>
        /* ---------------------------------- */
        /* CSS: スタイルを更新 (チェックボックス重ね合わせ) */
        /* ---------------------------------- */

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
        .artist-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            padding: 20px;
        }
        .artist-item {
            text-align: center;
            padding: 10px;
            border-radius: 8px;
            position: relative; 
        }
        .artist-visual {
            display: block;
            cursor: pointer;
            padding-bottom: 5px; /* 名前との間隔を確保 */
            position: relative; /* アイコンに対するチェックボックスの基準点 */
        }
        .artist-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid transparent; 
            transition: border 0.2s;
            display: block; /* 中央寄せのためにブロック要素に */
            margin: 0 auto 5px; /* 中央寄せ */
        }

        /* 【修正点】チェックボックスのスタイルと重ね合わせ */
        .artist-checkbox {
            position: absolute;
            /* アイコンの中心下部に配置 */
            top: 100px; /* アイコンの高さ(80px)の少し下 */
            left: 50%;
            transform: translateX(-50%);
            z-index: 10; /* アイコンより手前に表示 */
            width: 20px;
            height: 20px;
            margin: 0;
            padding: 0;
            /* 背景の白丸を目立たせる */
            border: 2px solid white; 
            border-radius: 50%;
            background-color: white; 
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
            /* 標準のチェックボックスの見た目を調整（環境により差が出る可能性あり） */
        }

        .artist-item input:checked ~ .artist-visual .artist-icon {
            border: 4px solid #1DB954; /* Spotify Green */
        }
        
        .artist-name {
            font-size: 0.9em;
            font-weight: bold;
            height: 2.4em;
            overflow: hidden;
            color: #fff;
        }
        .loading-message {
            text-align: center;
            padding: 20px;
            font-size: 1.2em;
        }
        h2, .header h1 {
            padding: 0 20px;
        }
        .submit-container {
            padding: 20px;
            text-align: center;
            margin-top: 30px;
            border-top: 1px solid #eee;
        }
        .submit-container button {
            padding: 10px 20px;
            margin: 0 10px;
            border: none;
            border-radius: 50px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .submit-container .next-button {
            background-color: #1DB954; /* Spotify Green */
            color: white;
        }
        .submit-container .skip-button {
            background-color: #f0f0f0;
            color: #333;
        }
        /* 検索フォームのスタイル */
        .genre-text {
            padding: 0 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        #searchInput {
            flex-grow: 1;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        #searchButton {
            padding: 8px 15px;
            background-color: #333;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="../img/SwipeFyLogo.png" alt="SwipeFyLogo">
        <h1>好きなジャンルを選択(複数選択可)</h1>
    </div>
    <div class="genre-text">
        <input type="text" id="searchInput" placeholder="(例: BTS, YOASOBI...)">
        <button id="searchButton">検索</button>
    </div>
    <h2>人気のアーティスト達</h2>

    <div id="artist-output" class="artist-grid">
        <div class="loading-message">情報を取得中です...</div>
    </div>
    
    <div class="submit-container">
        <button class="next-button" onclick="handleNext()">次へ</button>
        <button class="skip-button" onclick="handleSkip()">スキップして次へ</button>
    </div>

<script>
    // ---------------------------------------------
    // 1. API通信に必要な定義
    // ---------------------------------------------
    const token = 'BQCRq-xSrltojMScO3gHnONeT4eG2RN03RVMxOOVosXrLWAxPblwLe7r-wiJu4AYGd5NcG6m7Kw5lc1gJv4FXIkLn3RGQY0rgAe-kw0As9yLIwI59IfeIorurU4U-707fatlMvuAolHXt1czK1hOqsA19MQJAe11LT5KJp_2gxgsWb7-Ipzz1l8qDWzKl12caWjWQlEkqkrpHM2zae_5H4PGfhG4rv2KK7FNLu0nsby9JaMgi93Gn3fCmDuA7DrUyUeNYphXyWNdXMzFfut9DWNbRTL-UL8XX9TJSL4a3DJDIhVSannSFa3WXjRILtV6yfPg'; 

    async function fetchWebApi(endpoint, method, body) {
        const res = await fetch(`https://api.spotify.com/${endpoint}`, {
            headers: {
                Authorization: `Bearer ${token}`,
            },
            method,
            body: body ? JSON.stringify(body) : null
        });
        if (!res.ok) {
            const errorBody = await res.json();
            throw new Error(`API Error: ${res.status} - ${errorBody.error.message}`);
        }
        return await res.json();
    }
    
    async function searchPopularArtists(query, limit) {
        if (!query || query.trim() === '') {
            return [];
        }
        const encodedQuery = encodeURIComponent(query.trim());
        
        const response = await fetchWebApi(
            `v1/search?q=${encodedQuery}&type=artist&limit=${limit}`, 'GET'
        );
        return response.artists.items;
    }

    /**
     * アーティスト情報をHTMLとして画面にレンダリングする
     */
    function displayArtists(artists) {
        const outputDiv = document.getElementById('artist-output');
        outputDiv.innerHTML = ''; 

        if (artists.length === 0) {
            outputDiv.innerHTML = '<p style="text-align: center; padding: 20px;">検索条件に一致するアーティストが見つかりませんでした。</p>';
            return;
        }

        artists.forEach((artist) => {
            if (!artist || !artist.id) return; 

            const imageUrl = artist.images.length > 0 ? artist.images[0].url : 'https://via.placeholder.com/80?text=No+Image';

            const artistHtml = `
                <div class="artist-item">
                    <input type="checkbox" class="artist-checkbox" id="artist-${artist.id}" name="selected_artists" value="${artist.id}">
                    
                    <label for="artist-${artist.id}" class="artist-visual">
                        <img src="${imageUrl}" alt="${artist.name}のアイコン" class="artist-icon">
                        <div class="artist-name">${artist.name}</div>
                    </label>
                </div>
            `;
            outputDiv.innerHTML += artistHtml;
        });
    }
    
    // ---------------------------------------------
    // 2. 検索実行ロジック
    // ---------------------------------------------
    const MAX_DISPLAY = 15;
    const INITIAL_QUERY = 'Global Top Artists'; 

    async function executeSearch(query) {
        const outputDiv = document.getElementById('artist-output');
        
        if (!query || query.trim() === '') {
            outputDiv.innerHTML = '<div class="loading-message">検索キーワードを入力してください。</div>';
            return;
        }
        
        try {
            outputDiv.innerHTML = `<div class="loading-message">"${query}" を検索中です...</div>`;
            
            const searchResults = await searchPopularArtists(query, MAX_DISPLAY); 
            
            displayArtists(searchResults);
            
            console.log(`検索結果 ${searchResults.length}件を画面に表示しました。`);

        } catch (error) {
            console.error('データの取得中にエラーが発生しました:', error);
            outputDiv.innerHTML = `<p style="color: red; text-align: center;">エラーが発生しました: ${error.message}<br>※トークンの期限切れや、APIエラーが原因である可能性があります。</p>`;
        }
    }

    // ---------------------------------------------
    // 3. イベントリスナーとボタン処理
    // ---------------------------------------------

    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('searchInput');
        const searchButton = document.getElementById('searchButton');
        
        // --- ページ初期表示時に人気アーティスト一覧を表示 ---
        executeSearch(INITIAL_QUERY);

        searchButton.addEventListener('click', () => {
            const query = searchInput.value;
            // ユーザー入力で絞り込み検索を実行
            executeSearch(query);
        });

        searchInput.addEventListener('keypress', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault(); // Enterキーによるフォーム送信を防止
                const query = searchInput.value;
                // ユーザー入力で絞り込み検索を実行
                executeSearch(query);
            }
        });
    });

    function getSelectedArtists() {
        const checkboxes = document.querySelectorAll('input.artist-checkbox:checked');
        const selectedIds = Array.from(checkboxes).map(cb => cb.value);
        return selectedIds;
    }

    function handleNext() {
        const selected = getSelectedArtists();
        if (selected.length === 0) {
            alert('アーティストを少なくとも1人選択してください。');
        } else {
            alert(`選択されたアーティストID: ${selected.join(', ')}`);
            // ここに次の画面への遷移ロジックを実装
        }
    }

    function handleSkip() {
        alert('スキップして次の画面へ進みます。');
        // ここに次の画面への遷移ロジックを実装
    }

</script>
</body>
</html>