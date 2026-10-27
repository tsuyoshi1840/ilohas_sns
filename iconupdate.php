<?php
// ----------------------------実装2026/10/22/10:30-------------------------------------------
// ===== プロフィール画像のアップロード (iconupdate.php) =====
// 画像そのものは uploads/icons/ フォルダに保存し、
// DB(users.icon_path)には「uploads/icons/ランダムな名前.png」という置き場所だけを入れる。
session_start();
require 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

// エラーのときはメッセージを持ってプロフィールへ戻す
function back_with_error(string $msg): void {
    $_SESSION['profile_error'] = $msg;
    header('Location: profile.php');
    exit;
}

if (!csrf_check()) {
    back_with_error('不正な送信です。もう一度お試しください。');
}

$user_id = (int)$_SESSION['user_id'];
$file    = $_FILES['icon'] ?? null;

// ① ちゃんと届いたか
if ($file === null || $file['error'] === UPLOAD_ERR_NO_FILE) {
    back_with_error('画像を選んでください。');
}
if ($file['error'] !== UPLOAD_ERR_OK) {
    back_with_error('アップロードに失敗しました。(ファイルが大きすぎる可能性があります)');
}

// ② 大きさ(2MBまで)
$MAX_BYTES = 2 * 1024 * 1024;
if ($file['size'] > $MAX_BYTES) {
    back_with_error('画像は2MB以下にしてください。');
}

// ③ 本当に画像か(拡張子ではなく中身で判定する。名前だけ .png の偽物を防ぐ)
$info = @getimagesize($file['tmp_name']);
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];
if ($info === false || !isset($allowed[$info['mime']])) {
    back_with_error('JPEG / PNG / GIF / WebP の画像を選んでください。');
}

// ④ 保存先フォルダ(無ければ作る)
$dir = __DIR__ . '/uploads/icons';
if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
    back_with_error('保存用フォルダを作れませんでした。');
}

// ⑤ ファイル名はランダムに付け直す(元の名前は使わない：上書き・なりすまし・日本語名の文字化け対策)
$filename = bin2hex(random_bytes(16)) . '.' . $allowed[$info['mime']];
$relative = 'uploads/icons/' . $filename;   // DB に入れるのはこの文字列

if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
    back_with_error('画像の保存に失敗しました。');
}

try {
    $pdo = db_connect();

    // 古い画像の場所を覚えておく(あとで消す)
    $stmt = $pdo->prepare('SELECT icon_path FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $old = $stmt->fetchColumn();

    $stmt = $pdo->prepare('UPDATE users SET icon_path = ? WHERE id = ?');
    $stmt->execute([$relative, $user_id]);
} catch (PDOException $e) {
    @unlink($dir . '/' . $filename);   // DB に入らなかったので画像も消す
    back_with_error('保存に失敗しました。');
}

// ⑥ 古い画像ファイルを片付ける(uploads/icons の中のものだけ)
if (is_string($old) && strpos($old, 'uploads/icons/') === 0) {
    $old_file = __DIR__ . '/' . $old;
    if (is_file($old_file)) {
        @unlink($old_file);
    }
}

// サイドバーのアイコンもすぐ変わるようにセッションにも入れる
$_SESSION['icon_path'] = $relative;

header('Location: profile.php');
exit;
// ----------------------------実装2026/10/22/10:30-------------------------------------------
