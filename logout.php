<?php
// ===== ログアウト =====
session_start();
require 'functions.php';

// 「ログイン状態を保持する」のトークンも消しておく(残っていると自動でログインし直してしまう)
if (isset($_SESSION['user_id'])) {
    try {
        $pdo  = db_connect();
        $stmt = $pdo->prepare('UPDATE users SET remember_token = NULL WHERE id = ?');
        $stmt->execute([(int)$_SESSION['user_id']]);
    } catch (PDOException $e) {
        // DBに失敗してもログアウト自体は続ける
    }
}
setcookie('remember_token', '', time() - 3600, '/');

// セッションを空にして破棄する
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

// 未ログインでもタイムラインは見られるので index.php へ戻す
header('Location: index.php');
exit;
// 中村君→ログアウトしますか？の注釈と戻る動き、ログアウトする動きが作られている。
// ここはログアウトしていても見られるようにしたい。からこのコードを組み込んでほしい。
// (index.phpの表示が変わったら理解できると思う。)
