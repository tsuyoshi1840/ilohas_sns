<?php
// セッションを開始
session_start();

// functions.php を読み込む
require 'functions.php';

// ① ログインしているかチェック
if (!isset($_SESSION['user_id'])) {
    // ログイン画面で「なぜ飛ばされたか」を伝えるメッセージ
    $_SESSION['login_notice'] = 'プロフィールを見るにはログインしてください。';
    header('Location: login.php');
    exit;
}

// ログイン中のユーザーID（自分のID）
$login_user_id = (int)$_SESSION['user_id']; // int に揃えて === 比較を確実にする

// ② URLパラメータから表示対象のユーザーIDを取得
$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : $login_user_id;
$is_my_profile = ($profile_id === $login_user_id); // 自分のプロフィールかどうか

// ③ 自己紹介の保存は profileupdate.php が担当。
//    エラーで戻ってきたときのメッセージと入力内容を受け取る
$profile_error = $_SESSION['profile_error'] ?? '';
$profile_draft = $_SESSION['profile_draft'] ?? null;
unset($_SESSION['profile_error'], $_SESSION['profile_draft']);

// 削除(del_content.php)で失敗したときのメッセージ
$post_error = $_SESSION['post_error'] ?? '';
unset($_SESSION['post_error']);

try {
    $pdo = db_connect();

    // ④ ユーザー情報を取得（username を使用）
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$profile_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // ユーザーが存在しない場合
    if (!$user) {
        header('Location: index.php');
        exit;
    }

    // ユーザー名（usernameカラムが存在しない場合は 'name' に合わせてね）
    $username = $user['username'] ?? 'ユーザー';

    // ⑤ ユーザーの投稿 + リポストを取得(いいね数・リポスト数・アイコンも含む)
    $posts = fetch_timeline($pdo, $login_user_id, $profile_id);

    // ⑥ 投稿数を取得
    $stmt_count = $pdo->prepare('SELECT COUNT(*) FROM posts WHERE user_id = ? AND deleted_at IS NULL');
    $stmt_count->execute([$profile_id]);
    $post_count = $stmt_count->fetchColumn();

} catch (PDOException $e) {
    exit('データベースエラーが発生しました。');
}

$pageTitle = 'プロフィール';
require 'includes/header.php';
?>

<!-- ===== プロフィール情報エリア ===== -->
<div class="profile-header" style="padding: 20px; border-bottom: 1px solid #eee; margin-bottom: 20px;">
    <!-- プロフィール画像(未設定ならデフォルト画像) -->
    <div class="profile__top">
        <img src="<?= htmlspecialchars(icon_url($user['icon_path'] ?? null), ENT_QUOTES, 'UTF-8') ?>"
             alt="" class="profile__icon" id="js-icon-preview">

        <?php if ($is_my_profile): ?>
            <!-- 画像の変更：iconupdate.php へ送る(ファイルを送るので enctype が必要) -->
            <form action="iconupdate.php" method="post" enctype="multipart/form-data" class="profile__icon-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <label class="btn btn--outline profile__icon-pick">
                    画像を選ぶ
                    <input type="file" name="icon" accept="image/jpeg,image/png,image/gif,image/webp"
                           class="js-icon-input" hidden required>
                </label>
                <button type="submit" class="btn btn--primary btn--flow js-flow-btn">アイコンを保存</button>
                <p class="profile__icon-note">JPEG / PNG / GIF / WebP、2MBまで</p>
            </form>
        <?php endif; ?>
    </div>

    <h2 class="profile__name"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></h2>

    <?php foreach ([$profile_error, $post_error] as $msg): ?>
        <?php if ($msg !== ''): ?>
            <p class="form-error"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- 自己紹介文エリア -->
    <div class="profile__bio-area" style="margin-top: 10px;">
        <?php if ($is_my_profile && (isset($_GET['edit']) || empty($user['profile_text']))): ?>
            <!-- 【自分かつ、未入力または編集モードのとき】フォームを表示。保存は profileupdate.php -->
            <form action="profileupdate.php" method="post" style="margin-top: 10px;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <textarea name="profile_text" rows="3" maxlength="300" placeholder="自己紹介を入力してください" class="form__textarea"><?= htmlspecialchars($profile_draft ?? $user['profile_text'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                <div style="margin-top: 8px;">
                    <button type="submit" class="btn btn--primary btn--flow js-flow-btn">保存する</button>
                    <?php if (!empty($user['profile_text'])): ?>
                        <a href="profile.php" style="margin-left: 10px; font-size: 14px; color: #666; text-decoration: none;">キャンセル</a>
                    <?php endif; ?>
                </div>
            </form>
        <?php else: ?>
            <!-- 【通常表示】 -->
            <?php if (!empty($user['profile_text'])): ?>
                <p class="profile__bio" style="white-space: pre-wrap;"><?= htmlspecialchars($user['profile_text'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php if ($is_my_profile): ?>
                    <a href="profile.php?edit=1" style="font-size: 12px; color: #007bff; text-decoration: none; display: inline-block; margin-top: 5px;">✏️ 自己紹介を編集する</a>
                <?php endif; ?>
            <?php else: ?>
                <?php if ($is_my_profile): ?>
                    <a href="profile.php?edit=1" class="profile__bio-empty" style="color: #888; font-style: italic; text-decoration: none;">
                        プロフィールに自己紹介を追加する
                    </a>
                <?php else: ?>
                    <p class="profile__bio-empty" style="color: #888; font-style: italic;">
                        自己紹介はまだありません
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <p class="profile__count" style="margin-top: 10px; color: #666; font-size: 14px;">
        投稿数: <?= htmlspecialchars($post_count, ENT_QUOTES, 'UTF-8') ?>件
    </p>
</div>

<!-- ===== ユーザーの投稿一覧タイムライン ===== -->
<div class="posts-container">
    <?php if (empty($posts)): ?>
        <p class="timeline__empty" style="padding: 20px; text-align: center; color: #666;">まだ投稿がありません。最初のポストをしてみましょう！</p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
        <article class="post" data-post-id="<?= htmlspecialchars($post['id'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($post['reposted_by'] !== null): ?>
                <p class="post__repost-label">🔁 <?= htmlspecialchars($post['reposted_by'], ENT_QUOTES, 'UTF-8') ?>さんがリポストしました</p>
            <?php endif; ?>
            <img src="<?= htmlspecialchars($post['icon'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="post__icon">

            <div class="post__content">
                <div class="post__header">
                    <a href="profile.php?id=<?= htmlspecialchars($post['user_id'], ENT_QUOTES, 'UTF-8') ?>" class="post__user-name"><?= htmlspecialchars($post['user_name'], ENT_QUOTES, 'UTF-8') ?></a>
                    <span class="post__user-id">@<?= htmlspecialchars($post['user_id'], ENT_QUOTES, 'UTF-8') ?></span>
                    <time class="post__time" datetime="<?= htmlspecialchars($post['datetime'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($post['display_time'], ENT_QUOTES, 'UTF-8') ?></time>
                    <?php if ($post['edited']): ?>
                        <span class="post__edited">(編集済み)</span>
                    <?php endif; ?>
                </div>

                <?php if ($post['reply_to_name'] !== null): ?>
                    <p class="post__reply-to">返信先: <span>@<?= htmlspecialchars($post['reply_to_name'], ENT_QUOTES, 'UTF-8') ?></span></p>
                <?php endif; ?>

                <!-- 本文を押すと詳細ページへ -->
                <a href="post_detail.php?id=<?= htmlspecialchars($post['id'], ENT_QUOTES, 'UTF-8') ?>" class="post__body-link">
                    <p class="post__body"><?= nl2br(htmlspecialchars($post['body'], ENT_QUOTES, 'UTF-8')) ?></p>
                </a>

                <!-- 【追加】ここに投稿された画像を表示するコードを入れたよ -->
                <?php if (!empty($post['image_path'])): ?>
                    <div class="post__image-container" style="margin-top: 10px;">
                        <img src="<?= htmlspecialchars($post['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="投稿画像" style="max-width: 100%; border-radius: 8px; max-height: 350px; object-fit: cover;">
                    </div>
                <?php endif; ?>

                <div class="post__actions">
                    <!-- いいね -->
                    <button type="button"
                            class="post__action post__action--like js-like-btn<?= $post['liked'] ? ' is-liked' : '' ?>"
                            data-post-id="<?= htmlspecialchars($post['id'], ENT_QUOTES, 'UTF-8') ?>"
                            aria-pressed="<?= $post['liked'] ? 'true' : 'false' ?>">
                        <span class="post__action-icon"><?= $post['liked'] ? '♥' : '♡' ?></span>
                        <span class="post__action-count js-like-count"><?= htmlspecialchars($post['likes'], ENT_QUOTES, 'UTF-8') ?></span>
                    </button>

                    <!-- リポスト -->
                    <button type="button"
                            class="post__action post__action--repost js-repost-btn<?= $post['reposted'] ? ' is-reposted' : '' ?>"
                            data-post-id="<?= htmlspecialchars($post['id'], ENT_QUOTES, 'UTF-8') ?>"
                            aria-pressed="<?= $post['reposted'] ? 'true' : 'false' ?>">
                        <span class="post__action-icon">🔁</span>
                        <span class="post__action-count js-repost-count"><?= htmlspecialchars($post['reposts'], ENT_QUOTES, 'UTF-8') ?></span>
                    </button>

                    <!-- 返信 -->
                    <a href="post_detail.php?id=<?= htmlspecialchars($post['id'], ENT_QUOTES, 'UTF-8') ?>" class="post__action post__action--reply">
                        <span class="post__action-icon">💬</span>
                        <span class="post__action-count"><?= htmlspecialchars($post['replies'], ENT_QUOTES, 'UTF-8') ?></span>
                    </a>

                    <?php if ($post['is_my_post']): ?>
                    <a href="update.php?id=<?= htmlspecialchars($post['id'], ENT_QUOTES, 'UTF-8') ?>" class="post__action post__action--edit">編集</a>

                    <form action="del_content.php" method="post" class="post__delete-form js-delete-form">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($post['id'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="return" value="profile.php">
                        <button type="submit" class="post__action post__action--delete js-delete-btn">削除</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- いいね・リポスト用JS 読み込み -->
<script src="./js/like.js" defer></script>
<script src="./js/repost.js" defer></script>

<?php require 'includes/footer.php'; ?>