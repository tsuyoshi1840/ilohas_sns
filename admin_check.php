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
    <title>管理者サイト</title>
</head>
<body>
    <h1>【管理者専用】いろはす王国 管理センター</h1>
    <p>管理者権限（ユーザーID: <?= htmlspecialchars($_SESSION['user_id'], ENT_QUOTES, 'UTF-8') ?>）でのアクセスが確認されました。</p>
</body>
</html>