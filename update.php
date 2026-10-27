<?php
// ===== 投稿の編集画面（update.php） =====
session_start();
require 'functions.php';

// ① ログインしていなければログイン画面へ
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$login_user_id = (int)$_SESSION['user_id'];

// ② 編集する投稿の id を受け取る（GET でも POST でも）
$post_id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

// CSRF 対策用のトークン（なりすまし送信を防ぐ）
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

try {
    $pdo = db_connect();

    // ③ 「自分の投稿」だけを取得する（imageカラムも一緒に取得するように追加！）
    $stmt = $pdo->prepare('SELECT id, content, reply_to_id, image FROM posts WHERE id = ? AND user_id = ? AND deleted_at IS NULL');
    $stmt->execute([$post_id, $login_user_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        // 存在しない or 他人の投稿
        header('Location: index.php');
        exit;
    }

    $body = $post['content'];
    $current_image = $post['image']; // 現在登録されている画像パス

    // ④ 保存ボタンが押されたとき
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = trim($_POST['body'] ?? '');
        $delete_image = isset($_POST['delete_image']); // 画像削除チェックボックス

        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
            $error = '不正な送信です。もう一度お試しください。';
        } elseif ($body === '') {
            $error = '本文を入力してください。';
        } elseif (mb_strlen($body) > 140) {
            $error = '140文字以内で入力してください。';
        } else {
            // 新しい画像ファイルがアップロードされた場合の処理
            $image_path = $current_image; // デフォルトは今の画像を維持

            if ($delete_image) {
                // 画像削除にチェックが入っていたら空にする
                $image_path = null;
            } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                // 新しい画像が選択されていたらアップロード処理
                $file = $_FILES['image'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (!in_array($ext, $allowed_exts, true)) {
                    $error = '許可されていない画像形式です。';
                } else {
                    $upload_dir = 'uploads/posts/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $filename = uniqid('post_', true) . '.' . $ext;
                    $destination = $upload_dir . $filename;

                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $image_path = $destination;
                    } else {
                        $error = '画像のアップロードに失敗しました。';
                    }
                }
            }

            // エラーがなければデータベースを更新
            if ($error === '') {
                $stmt = $pdo->prepare('UPDATE posts SET content = ?, image = ? WHERE id = ? AND user_id = ? AND deleted_at IS NULL');
                $stmt->execute([$body, $image_path, $post_id, $login_user_id]);

                // 返信なら詳細ページ、普通の投稿ならタイムラインへ戻る
                header('Location: ' . ($post['reply_to_id'] !== null ? 'post_detail.php?id=' . $post_id : 'index.php'));
                exit;
            }
        }
    }
} catch (PDOException $e) {
    exit('データベースエラーが発生しました。');
}

$pageTitle = 'ポストを編集';
require 'includes/header.php';
?>

<header class="page-header">
  <h1 class="page-header__title">ポストを編集</h1>
</header>

<?php if ($error !== ''): ?>
  <div style="background: #ffe6e6; color: #d9534f; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php endif; ?>

<section class="post-form">
  <!-- 画像を送るので enctype="multipart/form-data" を追加したよ -->
  <form action="update.php" method="post" enctype="multipart/form-data" class="post-form__body">
    <input type="hidden" name="id" value="<?= $post_id ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

    <textarea name="body" class="post-form__textarea" maxlength="140" required
              style="width: 100%; min-height: 100px; padding: 8px; box-sizing: border-box;"><?= htmlspecialchars($body, ENT_QUOTES, 'UTF-8') ?></textarea>

    <!-- ===== 画像のプレビューと管理エリア ===== -->
    <div class="post-form__media-edit" style="margin: 12px 0; padding: 10px; background: #f7f9f9; border-radius: 8px;">
        <?php if (!empty($current_image)): ?>
            <div style="margin-bottom: 8px;">
                <p style="font-size: 12px; color: #657786; margin-bottom: 4px;">現在の画像:</p>
                <img src="<?= htmlspecialchars($current_image, ENT_QUOTES, 'UTF-8') ?>" alt="投稿画像" style="max-width: 150px; border-radius: 4px; display: block; margin-bottom: 6px;">
                <label style="font-size: 13px; color: #d9534f; cursor: pointer;">
                    <input type="checkbox" name="delete_image" value="1"> この画像を削除する
                </label>
            </div>
        <?php endif; ?>

        <div style="margin-top: 8px;">
            <label style="font-size: 13px; display: block; margin-bottom: 4px; color: #657786;">新しい画像に変更する:</label>
            <input type="file" name="image" accept="image/*" style="font-size: 13px;">
        </div>
    </div>

    <div class="post-form__footer" style="margin-top: 8px;">
      <button type="submit" class="btn btn--primary">保存する</button>
      <a href="<?= $post['reply_to_id'] !== null ? 'post_detail.php?id=' . $post_id : 'index.php' ?>" style="margin-left: 12px; color: #536471; text-decoration: none;">キャンセル</a>
    </div>
  </form>
</section>

<?php require 'includes/footer.php'; ?>
