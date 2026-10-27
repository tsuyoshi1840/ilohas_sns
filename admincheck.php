<?php 
session_start();

// 1. ログインチェック
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

// 2. 許可するIPアドレスのリスト
$allowed_ips = [
    '172.19.71.107',
    '127.0.0.1',    
    '::1'           
    ];

// 3. クライアントのIPアドレスを取得
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '';

// 4. 許可されたIPに含まれているかチェック
if (!in_array($client_ip, $allowed_ips, true)) {
    // 許可されていない場合は一般ページへリダイレクト（無限ループ防止）
    header("Location: test.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>管理者サイト</title>
</head>
<body>
    <h1>【管理者専用】いろはす王国 管理センター</h1>
    <p>あなたのIPアドレス（<?= htmlspecialchars($client_ip, ENT_QUOTES, 'UTF-8') ?>
    ）からのアクセスが許可されました。</p>
</body>
</html>