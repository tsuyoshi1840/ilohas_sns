<?php
session_start();
require_once __DIR__ . '/functions.php';


// 1. ログインチェック（ログインしていなければログイン画面へ）
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode(basename($_SERVER['PHP_SELF'])));
    exit;
}


// 2. 管理者権限チェック（is_admin が 1 でなければ、一般のホームへ追い返す）
$is_admin = isset($_SESSION['is_admin']) ? (int)$_SESSION['is_admin'] : 0;

if ($is_admin !== 1) {
    header('Location: index.php');
    exit;
}


$dbh = db_connect();
$message = '';
$db_error = '';

// --- データの追加・削除処理（POST送信された場合） ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_post_id'])) {
        $post_id = (int)$_POST['delete_post_id'];
        $stmt = $dbh->prepare("DELETE FROM posts WHERE id = :id");
        $stmt->bindValue(':id', $post_id, PDO::PARAM_INT);
        $stmt->execute();
        $message = "投稿ID{$post_id}を削除しました。";
    }

    if (isset($_POST['delete_user_id'])) {
        $user_id = (int)$_POST['delete_user_id'];
        $stmt = $dbh->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $message = "ユーザーID: {$user_id} を削除しました。";
    }

    if (isset($_POST['announcement_content'])) {
        $content = trim($_POST['announcement_content']);
        if ($content !== '') {
            $stmt = $dbh->prepare("INSERT INTO announcements (content, created_at) VALUES (:content, NOW())");
            $stmt->bindValue(':content', $content, PDO::PARAM_STR);
            $stmt->execute();
            $message = "王国全体にお知らせを通知しました。";
        }
    }
}


// --- 現在選択されているタブを取得（デフォルトは 'stats'） ---
$tab = $_GET['tab'] ?? 'stats';

// --- 各種データの取得 ---
$users = [];
$posts = [];
$stats = ['users_count' => 0, 'posts_count' => 0];

try {
    $stats['users_count'] = $dbh->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['posts_count'] = $dbh->query("SELECT COUNT(*) FROM posts")->fetchColumn();

    if ($tab === 'users') {
        $stmt_users = $dbh->query("SELECT id, username FROM users ORDER BY id ASC");
        $users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($tab === 'posts') {
        $stmt_posts = $dbh->query("SELECT id, user_id, content, created_at FROM posts ORDER BY created_at DESC LIMIT 50");
        $posts = $stmt_posts->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $db_error = 'データベースの取得に失敗しました。';
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理センター - いろはす王国</title>
    <style>
        body { font-family: sans-serif; margin: 0; background: #f4f6f9; color: #333; }
        .wrapper { display: flex; min-height: 100vh; }
        
        .sidebar { width: 250px; background: #343a40; color: #fff; padding: 20px; box-sizing: border-box; }
        .sidebar h2 { font-size: 18px; border-bottom: 1px solid #4f5962; padding-bottom: 10px; margin-top: 0; }
        .sidebar ul { list-style: none; padding: 0; margin: 20px 0; }
        .sidebar li { margin-bottom: 10px; }
        .sidebar a { display: block; color: #c2c7d0; text-decoration: none; padding: 10px 15px; border-radius: 4px; transition: 0.2s; }
        .sidebar a:hover, .sidebar a.active { background: #007bff; color: #fff; }
        .sidebar .back-home { margin-top: 40px; border-top: 1px solid #4f5962; padding-top: 20px; }

        .main-content { flex: 1; padding: 30px; box-sizing: border-box; }
        h1 { color: #333; margin-top: 0; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; background: #fff; }
        th, td { border: 1px solid #dee2e6; padding: 12px; text-align: left; }
        th { background: #f8f9fa; }
        .success { color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 12px; border-radius: 4px; margin-bottom: 20px; }
        .error { color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb; padding: 12px; border-radius: 4px; margin-bottom: 20px; }
        
        .stats-box { display: flex; gap: 20px; margin-top: 20px; }
        .stat-card { background: #fff; padding: 25px; border-radius: 6px; flex: 1; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 5px solid #007bff; }
        .stat-card h3 { margin: 0; font-size: 14px; color: #6c757d; }
        .stat-card p { font-size: 28px; font-weight: bold; color: #007bff; margin: 10px 0 0 0; }

        textarea { width: 100%; height: 120px; padding: 12px; box-sizing: border-box; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px; }
        button { cursor: pointer; padding: 8px 16px; border-radius: 4px; border: none; font-weight: bold; }
        .btn-danger { background: #dc3545; color: #fff; }
        .btn-primary { background: #007bff; color: #fff; margin-top: 10px; }
    </style>
</head>
<body>
<div class="wrapper">
<nav class="sidebar">
    <h2>管理センター</h2>
    <ul>
        <li><a href="admin.php?tab=stats" class="<?= $tab === 'stats' ? 'active' : '' ?>">📊 サイトの統計</a></li>
        <li><a href="admin.php?tab=posts" class="<?= $tab === 'posts' ? 'active' : '' ?>">📝 投稿管理</a></li>
        <li><a href="admin.php?tab=users" class="<?= $tab === 'users' ? 'active' : '' ?>">👥 ユーザー管理</a></li>
        <li><a href="admin.php?tab=announcement" class="<?= $tab === 'announcement' ? 'active' : '' ?>">📢 お知らせ全体通知</a></li>
    </ul>
    <div class="back-home">
        <a href="index.php">🏠 ホームに戻る</a>
        <a href="logout.php" style="color: #ff6b6b;">🚪 ログアウト</a>
    </div>
</nav>

<div class="main-content">
    <?php if (!empty($message)): ?>
        <div class="success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($db_error)): ?>
        <div class="error"><?= htmlspecialchars($db_error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php else: ?>

        <?php if ($tab === 'stats'): ?>
            <h1>📊 サイトの統計アナリティクス</h1>
            <p>いろはす王国の現在の稼働状況です。</p>
            <div class="stats-box">
                <div class="stat-card">
                    <h3>総住人（ユーザー）数</h3>
                    <p><?= number_format($stats['users_count']) ?> 人</p>
                </div>
                <div class="stat-card">
                    <h3>総ポスト数</h3>
                    <p><?= number_format($stats['posts_count']) ?> 件</p>
                </div>
            </div>

        <?php elseif ($tab === 'posts'): ?>
            <h1>📝 投稿管理</h1>
            <p>不適切な投稿などを確認・削除できます（直近50件）。</p>
            <table>
                <thead>
                    <tr>
                        <th>投稿ID</th>
                        <th>投稿者ID</th>
                        <th>内容</th>
                        <th>投稿日時</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($posts)): ?>
                        <tr><td colspan="5">投稿はまだありません。</td></tr>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td><?= htmlspecialchars($post['id'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($post['user_id'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= nl2br(htmlspecialchars($post['content'], ENT_QUOTES, 'UTF-8')) ?></td>
                                <td><?= htmlspecialchars($post['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <form action="admin.php?tab=posts" method="post" onsubmit="return confirm('本当にこの投稿を削除しますか？');">
                                        <input type="hidden" name="delete_post_id" value="<?= $post['id'] ?>">
                                        <button type="submit" class="btn-danger">削除</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php elseif ($tab === 'users'): ?>
            <h1>👥 ユーザー管理</h1>
            <p>登録されている王国の住人一覧です。</p>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ユーザー名</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="3">登録されているユーザーはまだいません。</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <form action="admin.php?tab=users" method="post" onsubmit="return confirm('本当にこのユーザーを削除しますか？');">
                                        <input type="hidden" name="delete_user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn-danger">削除</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php elseif ($tab === 'announcement'): ?>
            <h1>📢 お知らせ全体通知</h1>
            <p>王国全体のユーザーに向けて、重要なお知らせやメッセージを配信します。</p>
            <form action="admin.php?tab=announcement" method="post">
                <textarea name="announcement_content" placeholder="王国のみんなに伝えるメッセージを入力してください..." required></textarea>
                <button type="submit" class="btn-primary">📢 お知らせを全体に通知する</button>
            </form>
        <?php endif; ?>

    <?php endif; ?>
</div>
</div>
</body>
</html>