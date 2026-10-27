<?php
session_start();
require_once __DIR__ . '/functions.php';

// 1. ログインチェック（ログインしていなければログイン画面へ）
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 2. 管理者権限チェック（is_admin が 1 でなければ、一般のホームへ追い返す）
$is_admin = isset($_SESSION['is_admin']) ? (int)$_SESSION['is_admin'] : 0;

if ($is_admin !== 1) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン先選択</title>
    <style>
        /* 全体のベース設定（ごく薄いミント・ホワイト系の背景） */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f3f7f4;
            color: #333;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        /* まとまり感のある白いカードコンテナ */
        .select-container {
            background: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(76, 175, 80, 0.08);
            width: 100%;
            max-width: 420px;
            box-sizing: border-box;
            text-align: center; /* コンテナ内のテキストをすべて中央揃え */
            border-top: 5px solid #81c784; /* 上部にアクセントの薄緑ライン */
        }

        /* 見出し（中央揃え） */
        .select-container h1 {
            font-size: 20px;
            color: #2e7d32;
            margin-top: 0;
            margin-bottom: 10px;
            text-align: center;
        }

        /* 説明テキスト（中央揃え） */
        .select-container p {
            font-size: 14px;
            color: #666666;
            margin-bottom: 25px;
            text-align: center;
        }

        /* リスト（リンクの並び） */
        .select-container ul {
            list-style: none;
            padding: 0;
            margin: 0 0 25px 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        /* 管理者サイトへ行くボタン（中央揃え） */
        .select-container li:nth-child(1) a {
            background-color: #4caf50;
            color: #ffffff;
        }
        .select-container li:nth-child(1) a:hover {
            background-color: #43a047;
        }

        /* 通常サイトへ行くボタン（中央揃え） */
        .select-container li:nth-child(2) a {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        .select-container li:nth-child(2) a:hover {
            background-color: #d0ebd2;
        }

        .select-container li a {
            display: block;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 15px;
            text-align: center; /* ボタン内のテキストを確実に中央揃え */
            transition: background-color 0.2s, transform 0.1s;
        }

        .select-container li a:active {
            transform: scale(0.98);
        }

        /* ログアウトリンク（中央揃え） */
        .logout-link {
            margin-top: 20px;
            text-align: center;
        }

        .logout-link a {
            color: #e57373;
            text-decoration: none;
            font-size: 14px;
        }

        .logout-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="select-container">
        <h1>管理者権限が確認されました</h1>
        <p>ログイン先を選択してください。</p>

        <ul>
            <!-- 管理者サイトへ移動 -->
            <li><a href="admin.php">管理者サイトへ行く</a></li>
            <!-- 通常の利用サイト（ホーム）へ移動 -->
            <li><a href="index.php">通常の利用サイト（ホーム）へ行く</a></li>
        </ul>

        <p><a href="logout.php">ログアウト</a></p>
    </div>
</body>
</html>

