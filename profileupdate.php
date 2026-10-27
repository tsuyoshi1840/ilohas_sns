<?php
// ===== 自己紹介の更新処理 (profileupdate.php) =====
session_start();
require 'functions.php';

// ① ログインしていなければログイン画面へ
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ② POST 以外は受け付けない
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

$user_id = (int)$_SESSION['user_id']; // 更新するのは必ず「自分」
$new_bio = trim($_POST['profile_text'] ?? '');

// ③ チェック
$error = '';
if (!csrf_check()) {
    $error = '不正な送信です。もう一度お試しください。';
} elseif (mb_strlen($new_bio) > 300) {
    $error = '自己紹介は300文字以内で入力してください。';
}

if ($error !== '') {
    // エラー文と入力内容を持たせて、編集画面に戻す
    $_SESSION['profile_error'] = $error;
    $_SESSION['profile_draft'] = $new_bio;
    header('Location: profile.php?edit=1');
    exit;
}

// ④ 保存(空欄なら自己紹介を消す)
try {
    $pdo  = db_connect();
    $stmt = $pdo->prepare('UPDATE users SET profile_text = ? WHERE id = ?');
    $stmt->execute([$new_bio === '' ? null : $new_bio, $user_id]);
} catch (PDOException $e) {
    $_SESSION['profile_error'] = '保存に失敗しました。';
    $_SESSION['profile_draft'] = $new_bio;
    header('Location: profile.php?edit=1');
    exit;
}

// ⑤ 自分のプロフィールへ戻る
header('Location: profile.php');
exit;
