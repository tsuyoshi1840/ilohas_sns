<!-- 2026/10/22/10:15追加php -->
<?php
// ===== 投稿の削除処理 (del_content.php) =====
// 行そのものは消さず、deleted_at に削除した日時を入れる(論理削除)。
// 一覧側は「deleted_at IS NULL」のものだけ表示するので、画面から見えなくなる。
session_start();
require 'functions.php';

// ① ログインしていなければログイン画面へ
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ② POST 以外(URL を直接開いた等)は受け付けない
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// 戻り先(決まったページだけ許可する)
$return = $_POST['return'] ?? 'index.php';
// ----------------------------未実装コード-------------------------------------------
// 詳細ページ(post_detail.php?id=数字)からの削除も、そこへ戻れるように許可する
if (!in_array($return, ['index.php', 'profile.php'], true)
    && !preg_match('/\Apost_detail\.php\?id=\d+\z/', $return)) {
    $return = 'index.php';
}
// ----------------------------未実装コード-------------------------------------------

// ③ なりすまし送信のチェック
if (!csrf_check()) {
    $_SESSION['post_error'] = '不正な送信です。もう一度お試しください。';
    header('Location: ' . $return);
    exit;
}

$post_id = (int)($_POST['id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

// ④ 自分の投稿で、まだ削除されていないものだけ deleted_at を入れる
try {
    $pdo  = db_connect();
    $stmt = $pdo->prepare(
        'UPDATE posts SET deleted_at = NOW() WHERE id = ? AND user_id = ? AND deleted_at IS NULL'
    );
    $stmt->execute([$post_id, $user_id]);

    if ($stmt->rowCount() === 0) {
        // 他人の投稿・存在しない・すでに削除済み
        $_SESSION['post_error'] = 'このポストは削除できませんでした。';
    }
} catch (PDOException $e) {
    $_SESSION['post_error'] = '削除に失敗しました。';
}

// ⑤ 元のページへ戻る
header('Location: ' . $return);
exit;
