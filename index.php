<?php
// ===== タイムライン(ホーム) =====
session_start();
require 'functions.php'; // db_connect() を使うため

$pageTitle = 'ホーム';

// エラーメッセージがあれば取得してセッションから消す
$post_error = $_SESSION['post_error'] ?? '';
unset($_SESSION['post_error']);

// ログイン判定
// タイムラインは誰でも見られる。投稿・削除はログイン中のみ。
$is_logged_in    = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? (int)$_SESSION['user_id'] : null;

require 'includes/header.php';
?>

<!-- ページ見出し -->
<header class="page-header">


    <!-- ===== 全体お知らせバナー（新着連動カルーセル版） ===== -->
<?php  
try {
    $pdo_notice = db_connect();
    $stmt_notices = $pdo_notice->prepare("SELECT * FROM announcements ORDER BY id DESC LIMIT 5");
    $stmt_notices->execute();
    $notices = $stmt_notices->fetchAll();
} catch (PDOException $e) {
    $notices = [];
}
?>

<?php if (!empty($notices)): ?>
<div class="notice-banner" id="noticeBanner">
  <!-- 左矢印ボタン -->
  <button type="button" class="notice-arrow notice-prev" id="noticePrevBtn" aria-label="前へ">&lsaquo;</button>

  <div class="notice-carousel">
    <div class="notice-track" id="noticeTrack">
      <?php foreach ($notices as $notice): ?>
        <div class="notice-item" data-notice-id="<?= htmlspecialchars($notice['id'], ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($notice['badge'])): ?>
            <span class="notice-badge"><?= htmlspecialchars($notice['badge'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
          <span class="notice-text"><?= htmlspecialchars($notice['content'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- 右矢印ボタン -->
  <button type="button" class="notice-arrow notice-next" id="noticeNextBtn" aria-label="次へ">&rsaquo;</button>

  <div class="notice-dots" id="noticeDots"></div>
  <button type="button" class="notice-close" id="noticeCloseBtn" aria-label="お知らせを閉じる">&times;</button>
</div>
<?php endif; ?>
<!-- お知らせここまで -->

  <h1 class="page-header__title">ホーム</h1>
  
</header>

<!-- 投稿エラーがあれば表示 -->
<?php if ($post_error !== ''): ?>
  <div style="background: #ffe6e6; color: #d9534f; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
    <?= htmlspecialchars($post_error, ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php endif; ?>

<?php if ($is_logged_in): ?>
<!-- ===== 投稿フォーム ===== -->

<!-- ログイン中：投稿フォームを表示 -->
<section class="post-form" id="post-form">
<img src="<?= htmlspecialchars(icon_url($_SESSION['icon_path'] ?? null), ENT_QUOTES, 'UTF-8') ?>" alt="" class="post-form__icon">

<!-- ※ js-post-form などのJavaScript用IDを外し、確実に対話・送信できるようにしたフォーム -->
<form action="post.php" method="post" enctype="multipart/form-data" class="post-form__body">

<textarea
  name="body"
  class="post-form__textarea"
  placeholder="いまどうしてる?"
  maxlength="140"
  required
></textarea>

<!-- 画像を選択するためのファイルアップロード欄 -->
<div class="post-form__media-upload" style="margin: 8px 0;">
  <input type="file" name="image" accept="image/*" style="font-size: 13px;">
</div>

<div class="post-form__footer">
  <span class="post-form__counter">140</span>
  <!-- JavaScriptのクラス/IDを外して、確実に通常の送信ができるようにしたボタン -->
  <button type="submit" class="btn btn--primary">
    ポストする
  </button>
</div>

</form>
</section>
<?php else: ?>
<!-- 未ログイン：投稿フォームは出さず、案内とボタンを表示 -->
<section class="post-form post-form--guest">
  <p class="post-form__guest-text">ポストするにはログインしてください。</p>
  <div class="post-form__guest-actions">
    <a href="login.php" class="btn btn--primary btn--flow js-flow-btn">ログイン</a>
    <a href="register.php" class="btn btn--outline">新規登録</a>
  </div>
</section>

<?php endif; ?>

<!-- ===== タイムライン ===== -->
<section class="timeline" id="js-timeline">

<?php
try {
    $pdo = db_connect();

    // ----------------------------未実装コード-------------------------------------------
    // 投稿 + リポスト + いいね数などをまとめて取得(中身は functions.php の fetch_timeline)
    // 未ログインのときは 0 を渡す → 「自分がいいね済みか」などは全部 false になる
    $posts = fetch_timeline($pdo, $current_user_id ?? 0);
    // ----------------------------未実装コード-------------------------------------------
    } catch (PDOException $e) {
    // どんなエラーが起きているのか画面に表示してストップさせる
    exit('データベースエラー: ' . $e->getMessage());
 }
// } catch (PDOException $e) {
//     $raw_posts = [];
//     $posts = [];
// }
?>

<!-- --- HTMLをループで表示する処理 --- -->
<div class="posts-container">
    <?php if (empty($posts)): ?>
        <p class="timeline__empty" style="padding: 20px; text-align: center; color: #666;">
            <?php if ($is_logged_in): ?>
                まだ投稿がありません。最初のポストをしてみましょう！
            <?php else: ?>
                まだ投稿がありません。
            <?php endif; ?>
        </p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
        <article class="post" data-post-id="<?= htmlspecialchars($post['id'], ENT_QUOTES, 'UTF-8') ?>">
            <?php // ----------------------------未実装コード------------------------------------------- ?>
            <?php if ($post['reposted_by'] !== null): ?>
                <!-- リポストとして並んでいる行 -->
                <p class="post__repost-label">🔁 <?= htmlspecialchars($post['reposted_by'], ENT_QUOTES, 'UTF-8') ?>さんがリポストしました</p>
            <?php endif; ?>
            <?php // ----------------------------未実装コード------------------------------------------- ?>
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

                <?php // ----------------------------未実装コード------------------------------------------- ?>
                <?php if ($post['reply_to_name'] !== null): ?>
                    <!-- 返信がリポストされて並んでいるとき -->
                    <p class="post__reply-to">返信先: <span>@<?= htmlspecialchars($post['reply_to_name'], ENT_QUOTES, 'UTF-8') ?></span></p>
                <?php endif; ?>
                <!-- 本文を押すと詳細ページへ -->
                <a href="post_detail.php?id=<?= htmlspecialchars($post['id'], ENT_QUOTES, 'UTF-8') ?>" class="post__body-link">
                <?php // ----------------------------未実装コード------------------------------------------- ?>
                <p class="post__body"><?= nl2br(htmlspecialchars($post['body'], ENT_QUOTES, 'UTF-8')) ?></p>
                <?php // ----------------------------未実装コード------------------------------------------- ?>
                </a>
<!-- 【修正】先頭の余分なスラッシュを外し、データベースに入っているパスをそのまま綺麗に出力する -->
<?php if (!empty($post['image_path'])): ?>
    <div class="post__image-container" style="margin-top: 10px;">
        <img src="<?= htmlspecialchars($post['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="投稿画像" style="max-width: 100%; border-radius: 8px; max-height: 350px; object-fit: cover;">
    </div>
<?php endif; ?>

<!-- 【追加】本文に含まれるURLがある場合にプレビューを表示する -->
<?php if (!empty($post['url'])): ?>
    <a href="<?= htmlspecialchars($post['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="post__url-preview" style="display: block; border: 1px solid #e1e8ed; border-radius: 8px; padding: 10px; margin-top: 10px; text-decoration: none; color: inherit; background: #f7f9f9;">
        <div style="font-size: 11px; color: #657786; margin-bottom: 2px;">
            <?= htmlspecialchars(parse_url($post['url'], PHP_URL_HOST), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div style="font-size: 13px; font-weight: bold; color: #0f141a;">
            <?= htmlspecialchars($post['url_title'] ?? $post['url'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    </a>
<?php endif; ?>
                <?php // ----------------------------未実装コード------------------------------------------- ?>

                <div class="post__actions">
                    <?php // ----------------------------未実装コード------------------------------------------- ?>
                    <!-- いいね：like.php に送る(main.js)。いいね済みなら is-liked -->
                    <button type="button"
                            class="post__action post__action--like js-like-btn<?= $post['liked'] ? ' is-liked' : '' ?>"
                            data-post-id="<?= htmlspecialchars($post['id'], ENT_QUOTES, 'UTF-8') ?>"
                            aria-pressed="<?= $post['liked'] ? 'true' : 'false' ?>">
                        <span class="post__action-icon"><?= $post['liked'] ? '♥' : '♡' ?></span>
                        <span class="post__action-count js-like-count"><?= htmlspecialchars($post['likes'], ENT_QUOTES, 'UTF-8') ?></span>
                    </button>

                    <!-- リポスト：repost.php に送る(main.js)。リポスト済みなら is-reposted -->
                    <button type="button"
                            class="post__action post__action--repost js-repost-btn<?= $post['reposted'] ? ' is-reposted' : '' ?>"
                            data-post-id="<?= htmlspecialchars($post['id'], ENT_QUOTES, 'UTF-8') ?>"
                            aria-pressed="<?= $post['reposted'] ? 'true' : 'false' ?>">
                        <span class="post__action-icon">🔁</span>
                        <span class="post__action-count js-repost-count"><?= htmlspecialchars($post['reposts'], ENT_QUOTES, 'UTF-8') ?></span>
                    </button>
                    <?php // ----------------------------未実装コード------------------------------------------- ?>

                    <?php // ----------------------------未実装コード------------------------------------------- ?>
                    <!-- 返信：詳細ページで返信を読む・書く -->
                    <a href="post_detail.php?id=<?= htmlspecialchars($post['id'], ENT_QUOTES, 'UTF-8') ?>" class="post__action post__action--reply">
                        <span class="post__action-icon">💬</span>
                        <span class="post__action-count"><?= htmlspecialchars($post['replies'], ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                    <?php // ----------------------------未実装コード------------------------------------------- ?>

                    <?php if ($post['is_my_post']): ?>
                    <!-- 編集：update.php へ -->
                    <a href="update.php?id=<?= htmlspecialchars($post['id'], ENT_QUOTES, 'UTF-8') ?>" class="post__action post__action--edit">編集</a>

                    <!-- 削除：del_content.php へ POST（deleted_at に日時が入り、一覧から消える） -->
                    <form action="del_content.php" method="post" class="post__delete-form js-delete-form">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($post['id'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="return" value="index.php">
                        <button type="submit" class="post__action post__action--delete js-delete-btn">削除</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</section>

<!-- いいね・リポスト用JS 読み込み -->
<script src="./js/like.js" defer></script>
<script src="./js/repost.js" defer></script>

<?php require 'includes/footer.php'; ?>
