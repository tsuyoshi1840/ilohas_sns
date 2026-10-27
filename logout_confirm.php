<?php
session_start();

// ログインしていない場合はログイン画面へ
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログアウト確認 - いろはす</title>
    <style>
        /* ==========================================
           白と黒を基調としたモノトーン・おしゃれデザイン
        ========================================== */
        :root {
            --bg-main: #f8f9fa;            /* ごく薄いクリーンなライトグレーの背景 */
            --card-bg: #ffffff;
            --text-main: #111111;          /* 引き締まった黒 */
            --text-sub: #666666;           /* 落ち着いたグレー */
            --border-color: #e5e5e5;       /* 繊細なボーダー線 */
            
            --btn-black: #111111;          /* メインの黒ボタン */
            --btn-black-hover: #333333;    /* ホバー時の黒 */
            
            --btn-gray-bg: #f1f3f5;        /* キャンセル用の薄いグレー */
            --btn-gray-hover: #e2e6ea;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-main);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .logout-container {
            background-color: var(--card-bg);
            padding: 40px;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.04);
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
            text-align: center;
            position: relative;
        }

        /* 上部にアクセントの黒いライン */
        .logout-container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: #111111;
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
        }

        .logout-container h1 {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-main);
            margin-top: 5px;
            margin-bottom: 12px;
        }

        .logout-container p {
            font-size: 14px;
            color: var(--text-sub);
            margin-bottom: 30px;
        }

        .button-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn {
            display: block;
            width: 100%;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            border-radius: 8px;
            padding: 12px;
            font-size: 15px;
            cursor: pointer;
            border: none;
            box-sizing: border-box;
            transition: background-color 0.2s, transform 0.1s;
        }

        .btn:active {
            transform: scale(0.99);
        }

        /* ログアウト実行ボタン（黒） */
        .btn--dark {
            background-color: var(--btn-black);
            color: #ffffff;
        }

        .btn--dark:hover {
            background-color: var(--btn-black-hover);
        }

        /* 戻るボタン（薄いグレー） */
        .btn--light {
            background-color: var(--btn-gray-bg);
            color: var(--text-main);
            border: 1px solid var(--border-color);
        }

        .btn--light:hover {
            background-color: var(--btn-gray-hover);
        }
    </style>
</head>
<body>

    <div class="logout-container">
        <h1>ログアウトの確認</h1>
        <p>本当にいろはすからログアウトしますか？</p>

        <div class="button-group">
            <form action="logout.php" method="post">
                <button type="submit" class="btn btn--dark">はい（ログアウトする）</button>
            </form>

            <button type="button" class="btn btn--light" onclick="location.href='index.php'">いいえ（戻る）</button>
        </div>
    </div>

</body>
</html>