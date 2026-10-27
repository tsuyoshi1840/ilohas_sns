<?php
// ----------------------------実装2026/10/22/10:42-------------------------------------------
// ===== ポストの詳細ページ (post_detail.php?id=投稿id) =====
// 元の投稿と、その返信・返信への返信…を段々に表示し、ここから返信できる。
session_start();
require 'functions.php';

$is_logged_in    = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? (int)$_SESSION['user_id'] : 0;

$post_id = (int)($_GET['id'] ?? 0);

// エラー(返信に失敗など)があれば受け取る
$post_error = $_SESSION['post_error'] ?? '';
unset($_SESSION['post_error']);

try {
    $thread = fetch_thread(db_connect(), $current_user_id, $post_id);
} catch (PDOException $e) {
    exit('データベースエラーが発生しました。');
}

// 見つからない・全部削除済みならタイムラインへ
if ($thread === null || empty($thread['items'])) {
    header('Location: index.php');
    exit;
}

// 削除などで戻ってくるときの行き先
$return_here = 'post_detail.php?id=' . $post_id;

// 返信の段のずれは4段までにする(それより深くても同じ位置に並べる)
$MAX_INDENT = 4;

$pageTitle = 'ポスト';
require 'includes/header.php';
?>

<header class="page-header page-header--with-back">
  <a href="index.php" class="page-header__back" aria-label="ホームへ戻る">←</a>
  <h1 class="page-header__title">ポスト</h1>
</header>

<?php if ($post_error !== ''): ?>
  <p class="form-error" style="margin: 12px 16px;"><?= htmlspecialchars($post_error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<?php if (!$is_logged_in): ?>
  <!-- 未ログイン：返信は読めるが、書くにはログインが必要 -->
  <section class="post-form post-form--guest">
    <p class="post-form__guest-text">返信するにはログインしてください。</p>
    <div class="post-form__guest-actions">
      <a href="login.php" class="btn btn--primary btn--flow js-flow-btn">ログイン</a>
      <a href="register.php" class="btn btn--outline">新規登録</a>
    </div>
  </section>
<?php endif; ?>

<section class="thread">
<?php foreach ($thread['items'] as $item): ?>
  <?php
    $post   = $item['post'];
    $depth  = min($item['depth'], $MAX_INDENT);
    $pid    = htmlspecialchars($post['id'], ENT_QUOTES, 'UTF-8');
    $is_root    = $item['depth'] === 0;
    $is_focused = $post['id'] === $post_id;   // URL で指定された投稿を目立たせる
  ?>

  <?php if ($post['deleted']): ?>
    <!-- 削除された投稿(下に返信が残っているときだけ表示) -->
    <article class="post post--deleted thread__item thread__item--d<?= $depth ?>" id="post-<?= $pid ?>">
      <p class="post__deleted-text">このポストは削除されました</p>
    </article>
    <?php continue; ?>
  <?php endif; ?>

  <article class="post thread__item thread__item--d<?= $depth ?><?= $is_root ? ' thread__item--root' : '' ?><?= $is_focused ? ' is-focused' : '' ?>"
           id="post-<?= $pid ?>" data-post-id="<?= $pid ?>">
    <img src="<?= htmlspecialchars($post['icon'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="post__icon">

    <div class="post__content">
      <div class="post__header">
        <a href="profile.php?id=<?= htmlspecialchars($post['user_id'], ENT_QUOTES, 'UTF-8') ?>" class="post__user-name"><?= htmlspecialchars($post['user_name'], ENT_QUOTES, 'UTF-8') ?></a>
        <span class="post__user-id">@<?= htmlspecialchars($post['user_name'], ENT_QUOTES, 'UTF-8') ?></span>
        <time class="post__time" datetime="<?= htmlspecialchars($post['datetime'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($post['display_time'], ENT_QUOTES, 'UTF-8') ?></time>
        <?php if ($post['edited']): ?>
          <span class="post__edited">(編集済み)</span>
        <?php endif; ?>
      </div>

      <?php if ($post['reply_to_name'] !== null && !$is_root): ?>
        <p class="post__reply-to">返信先: <span>@<?= htmlspecialchars($post['reply_to_name'], ENT_QUOTES, 'UTF-8') ?></span></p>
      <?php endif; ?>

      <!-- 本文 -->
      <p class="post__body"><?= nl2br(htmlspecialchars($post['body'], ENT_QUOTES, 'UTF-8')) ?></p>

      <!-- 【追加】投稿された画像を表示 -->
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

      <div class="post__actions">
        <button type="button" class="post__action post__action--like js-like-btn<?= $post['liked'] ? ' is-liked' : '' ?>"
                data-post-id="<?= $pid ?>" aria-pressed="<?= $post['liked'] ? 'true' : 'false' ?>">
          <span class="post__action-icon"><?= $post['liked'] ? '♥' : '♡' ?></span>
          <span class="post__action-count js-like-count"><?= (int)$post['likes'] ?></span>
        </button>

        <button type="button" class="post__action post__action--repost js-repost-btn<?= $post['reposted'] ? ' is-reposted' : '' ?>"
                data-post-id="<?= $pid ?>" aria-pressed="<?= $post['reposted'] ? 'true' : 'false' ?>">
          <span class="post__action-icon">🔁</span>
          <span class="post__action-count js-repost-count"><?= (int)$post['reposts'] ?></span>
        </button>

        <?php if ($is_logged_in): ?>
          <!-- 返信：押すとこの投稿の下に返信欄が開く(main.js) -->
          <button type="button" class="post__action post__action--reply js-reply-toggle"
                  data-target="reply-form-<?= $pid ?>" aria-expanded="<?= $is_root ? 'true' : 'false' ?>">
            <span class="post__action-icon">💬</span>
            <span class="post__action-count"><?= (int)$post['replies'] ?></span>
          </button>
        <?php else: ?>
          <span class="post__action post__action--reply">
            <span class="post__action-icon">💬</span>
            <span class="post__action-count"><?= (int)$post['replies'] ?></span>
          </span>
        <?php endif; ?>

        <?php if ($post['is_my_post']): ?>
          <a href="update.php?id=<?= $pid ?>" class="post__action post__action--edit">編集</a>
          <form action="del_content.php" method="post" class="post__delete-form js-delete-form">
            <input type="hidden" name="id" value="<?= $pid ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="return" value="<?= htmlspecialchars($return_here, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="post__action post__action--delete js-delete-btn">削除</button>
          </form>
        <?php endif; ?>
      </div>

      <?php if ($is_logged_in): ?>
        <!-- 返信欄：いちばん上の投稿は最初から開いておく。それ以外は「💬」で開閉 -->
        <form action="post.php" method="post" class="reply-form" id="reply-form-<?= $pid ?>"<?= $is_root ? '' : ' hidden' ?>>
          <input type="hidden" name="reply_to_id" value="<?= $pid ?>">
          <textarea name="body" class="reply-form__textarea" maxlength="140" required
                    placeholder="@<?= htmlspecialchars($post['user_name'], ENT_QUOTES, 'UTF-8') ?> さんに返信"></textarea>
          <div class="reply-form__footer">
            <button type="submit" class="btn btn--primary btn--flow js-flow-btn">返信する</button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </article>
<?php endforeach; ?>
</section>

<!-- いいね・リポスト用JS 読み込み -->
<script src="./js/like.js" defer></script>
<script src="./js/repost.js" defer></script>

<?php require 'includes/footer.php'; ?>
<?php // ----------------------------実装2026/10/22/10:42------------------------------------------- ?>