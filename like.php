<?php
// ----------------------------実装2026/10/22/10:32-------------------------------------------
// ===== いいねの切り替え (like.php) =====
// JS(main.js)から fetch で呼ばれ、結果を JSON で返す。
session_start();
require 'functions.php';
header('Content-Type: application/json; charset=UTF-8');

// 未ログイン → ログイン画面へ誘導(画面側でページ移動する)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['login_notice'] = 'いいねするにはログインしてください。';
    http_response_code(401);
    echo json_encode(['login' => 'login.php']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    http_response_code(400);
    echo json_encode(['error' => '不正な送信です。']);
    exit;
}

try {
    $result = toggle_reaction(db_connect(), 'likes', (int)$_SESSION['user_id'], (int)($_POST['post_id'] ?? 0));
    if (!$result['ok']) {
        http_response_code(404);
        echo json_encode(['error' => 'このポストは見つかりません。']);
        exit;
    }
    // { "active": true/false, "count": 数 }
    echo json_encode(['active' => $result['active'], 'count' => $result['count']]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'いいねに失敗しました。']);
}
// ----------------------------実装2026/10/22/10:32-------------------------------------------
