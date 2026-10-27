<?php
// ===== 共通ヘッダー =====
// 使い方(各ページの先頭で):
//   $pageTitle = 'ホーム';
//   $isAuthPage = false; // ログイン・登録画面なら true(サイドバーを出さない)
//   require 'includes/header.php';
// ※ session_start() は各ページ側で先に呼んでおくこと

$pageTitle  = $pageTitle  ?? 'ホーム';
$isAuthPage = $isAuthPage ?? false;

// ログイン状態(サイドバーの出し分けに使う)
$header_logged_in = isset($_SESSION['user_id']);
$header_username  = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | いろはす</title>
  <link rel="stylesheet" href="css/style.css">
  <script>
  // 保存されたモードを、画面を描く前に反映する(一瞬白くなるのを防ぐ)
  try {
    if (localStorage.getItem('theme') === 'dark') {
      document.documentElement.setAttribute('data-theme', 'dark');
    }
  } catch (e) {}
</script>
  <?php if ($header_logged_in): ?>
    <!-- いいね・リポストを JS から送るときのなりすまし対策トークン -->
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
  <?php endif; ?>
</head>
<body class="<?= $isAuthPage ? 'page-auth' : 'page-main' ?>">

<?php if (!$isAuthPage): ?>
<div class="layout">

  <!-- ===== 左サイドバー ===== -->
  <nav class="sidebar">
    <a href="index.php" class="sidebar__logo">
      <img src="img/logo.jpg" alt="ILOHAS">
    </a>

    <ul class="sidebar__menu">
      <li class="sidebar__item">
        <a href="index.php" class="sidebar__link">
         <img src="img/home-icon.png" alt="" class="sidebar__icon">
         <span class="sidebar__text">ホーム</span>
        </a>
      </li>
      <?php if ($header_logged_in): ?>
        <li class="sidebar__item">
          <a href="profile.php" class="sidebar__link">
            <img src="img/profile-icon.png" alt="" class="sidebar__icon">
          <span class="sidebar__text">プロフィール</span>
          </a>
        </li>
        <li class="sidebar__item">
          <a href="logout.php" class="sidebar__link">
            <img src="img/logout-icon.png" alt="" class="sidebar__icon">
          <span class="sidebar__text">ログアウト</span>
          </a>
        </li>
      <?php else: ?>
        <li class="sidebar__item">
          <a href="login.php" class="sidebar__link">ログイン</a>
        </li>
        <li class="sidebar__item">
          <a href="register.php" class="sidebar__link">新規登録</a>
        </li>
      <?php endif; ?>
    </ul>

      <?php if ($header_logged_in): ?>
      <a href="index.php#post-form" class="btn btn--primary btn--block btn--flow sidebar__post-btn js-flow-btn">
        <img src="img/post-icon.png" alt="" class="sidebar__icon">
        <span class="sidebar__text">ポストする</span>
      </a>

    <button type="button" class="sidebar__link sidebar__theme-btn-icon" id="theme-toggle" style="background:none; border:none; cursor:pointer; text-align:left;">
      <img src="img/theme-icon.png" alt="" class="sidebar__icon">
      <span class="sidebar__text">モード切り替え</span>
    </button>



      <!-- ログイン中のユーザー -->
      <a href="profile.php" class="sidebar__user">
        <?php // ----------------------------未実装コード------------------------------------------- ?>
        <img src="<?= htmlspecialchars(icon_url($_SESSION['icon_path'] ?? null), ENT_QUOTES, 'UTF-8') ?>" alt="" class="sidebar__user-icon">
        <?php // ----------------------------未実装コード------------------------------------------- ?>
        <div class="sidebar__user-info">
          <span class="sidebar__user-name"><?= htmlspecialchars($header_username, ENT_QUOTES, 'UTF-8') ?></span>
          <span class="sidebar__user-id">@<?= htmlspecialchars($header_username, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
      </a>
    <?php endif; ?>
  </nav>

  <!-- ===== メインエリア ===== -->
  <main class="main">
<?php else: ?>
<main class="auth-wrapper">
<?php endif; ?>
