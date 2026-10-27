<?php
// ===== 新規登録画面 =====
session_start();
require 'functions.php'; // db_connect() が入っているファイル

// すでにログインしているならタイムラインへ
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// 入力値を保持するための変数
$username     = '';
$profile_text = '';

// ===== フォームが送信されたときだけ処理する =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ① 入力値を受け取る(前後の空白は消す)
    $username     = trim($_POST['username'] ?? '');
    $password     = $_POST['password'] ?? ''; // パスワードは空白も含めてそのまま
    $profile_text = trim($_POST['profile_text'] ?? ''); // 任意項目または初期プロフィール

    // ② 入力チェック
    if ($username === '' || $password === '') {
        $error = 'ユーザーネームとパスワードは必須です';
    } elseif (mb_strlen($username) > 50) {
        $error = 'ユーザーネームは50文字以内で入力してください';
    } elseif (!preg_match('/\A[a-zA-Z0-9_]{1,20}\z/', $username)) {
        $error = 'ユーザーネームは半角英数字と_(アンダーバー)の20文字以内で入力してください';
    } elseif (strlen($password) < 8) {
        $error = 'パスワードは8文字以上で入力してください';
    }

    // ③ 入力に問題がなければDBの処理へ
    if ($error === '') {
        try {
            $pdo = db_connect();

            // ③-1 ユーザーネームがすでに使われていないか確認
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $exists = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($exists) {
                $error = 'このユーザーネームはすでに使われています';
            } else {
                // ③-2 パスワードをハッシュ化して登録
                $hash = password_hash($password, PASSWORD_DEFAULT);

                // ilohas_db の users テーブル構造 (id, username, password, profile_text) に合わせる
                // ※ created_at などのカラムが必要な場合は適宜テーブルに追加してください
                $stmt = $pdo->prepare(
                    'INSERT INTO users (username, password, profile_text)
                     VALUES (?, ?, ?)'
                );
                $stmt->execute([$username, $hash, $profile_text]);

                // ③-3 登録したユーザーでそのままログイン状態にする
                session_regenerate_id(true); // なりすまし対策
                $_SESSION['user_id']  = (int)$pdo->lastInsertId(); // 判定用
                $_SESSION['username'] = $username;                  // 表示用
                // ----------------------------実装2026/10/22/10:49-------------------------------------------
                $_SESSION['icon_path'] = null; // 登録直後はデフォルトアイコン
                // ----------------------------実装2026/10/22/10:49-------------------------------------------

                header('Location: index.php');
                exit;
            }
        } catch (PDOException $e) {
            // 開発中は原因を確認するためにコメントアウトを外すと便利です
            // exit($e->getMessage());
            $error = 'データベースエラーが発生しました';
        }
    }
}

// ===== ここから画面表示 =====
$pageTitle  = '新規登録';
$isAuthPage = true;
require 'includes/header.php';

// 表示用の短い関数
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>

<section class="auth-card">
  <h1 class="auth-card__logo">いろはす</h1>
  <h2 class="auth-card__title">アカウントを作成</h2>

  <?php if ($error): ?>
    <p class="form-error"><?= h($error) ?></p>
  <?php endif; ?>

  <form action="register.php" method="post" class="form" id="js-register-form">
    <div class="form__group">
      <label for="username" class="form__label">ユーザーネーム（半角英数字と_）</label>
      <input type="text" name="username" id="username" class="form__input"
             maxlength="20" pattern="[a-zA-Z0-9_]+" value="<?= h($username) ?>" required>
    </div>

    <div class="form__group">
      <label for="password" class="form__label">パスワード（8文字以上）</label>
      <input type="password" name="password" id="password" class="form__input"
             minlength="8" required>
    </div>

    <!-- 必要であればプロフィール文も登録時に含められます（不要なら削除OK） -->
    <div class="form__group">
      <label for="profile_text" class="form__label">自己紹介（任意）</label>
      <textarea name="profile_text" id="profile_text" class="form__textarea" maxlength="255"><?= h($profile_text) ?></textarea>
    </div>

    <button type="submit" class="btn btn--primary btn--block">登録する</button>
  </form>

  <p class="auth-card__link">
    すでにアカウントをお持ちの方は <a href="login.php">ログイン</a>
  </p>
</section>

<?php require 'includes/footer.php'; ?>