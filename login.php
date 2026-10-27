<?php 
session_start();
require_once __DIR__ . '/functions.php';
$redirect = $_REQUEST['redirect'] ?? 'index.php';

// リダイレクト先の不正な文字列チェック
if (!preg_match('/^[a-zA-Z0-9_\-\.\/]+$/', $redirect) || strpos($redirect, 'http') === 0) {
    $redirect = 'index.php';
}

// すでにセッションログインしている場合はリダイレクト
if (isset($_SESSION['user_id'])) {
    header('Location: ' . $redirect);
    exit;
}

// クッキー（自動ログイン）による復元処理
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    try {
        $dbh = db_connect();
        $sql = "SELECT id, username, is_admin FROM users WHERE remember_token = :token";
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':token', $token, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            session_regenerate_id(true);
            $_SESSION['user_id']  = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = (int)($user['is_admin'] ?? 0); // 管理者フラグをセット
            
            header('Location: ' . $redirect);
            exit;
        }
    } catch (PDOException $e) {
    }
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error_message = 'ユーザー名、パスワードを入力してください。';
    } else {
        try {
            $dbh = db_connect();
            // id, password に加えて is_admin も確実に取得する
            $sql = "SELECT id, password, is_admin FROM users WHERE username = :username";
            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(":username", $username, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && password_verify($password, $result['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']  = (int)$result['id'];
                $_SESSION['username'] = $username;
                $_SESSION['is_admin'] = (int)($result['is_admin'] ?? 0); // セッションに管理者フラグを保存

                // ★ 1. ログイン状態を保持する処理
// ★ 1. ログイン状態を保持する処理
            if (isset($_POST['remember'])) {
                $token = bin2hex(random_bytes(32));
                $update_stmt = $dbh->prepare("UPDATE users SET remember_token = :token WHERE username = :username");
                $update_stmt->bindValue(':token', $token, PDO::PARAM_STR);
                $update_stmt->bindValue(':username', $username, PDO::PARAM_STR);
                $update_stmt->execute();

                $is_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
                setcookie('remember_token', $token, time() + (86400 * 30), '/', '', $is_secure, true);
            }

            // ★ 2. 管理者なら admin_select.php へ、一般なら redirect 先へ
            if ((int)$_SESSION['is_admin'] === 1) {
                header("Location: admin_select.php");
                exit;
            }

            header("Location: " . $redirect);
            exit;
            } else {
                $error_message = 'ユーザー名またはパスワードが間違っています。';
            }
        } catch (PDOException $e) {
            $error_message = 'データベースエラーが発生しました。';
        }
    }
}

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン - いろはす王国</title>
    <style>
        :root {
            --bg-main: #f4f9fb;
            --card-bg: #ffffff;
            --text-main: #0f1419;
            --text-sub: #536471;
            --border-color: #e1eef3;
            --btn-blue: #2ab7e9;
            --btn-blue-hover: #199ecd;
            --accent-sky: #4bc0e6;
            --error-bg: rgba(244, 33, 46, 0.08);
            --error-text: #f4212e;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-main);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .page-auth {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }

        .auth-wrapper {
            width: 100%;
            max-width: 440px;
            padding: 20px;
            box-sizing: border-box;
        }

        .auth-card {
            background-color: var(--card-bg);
            padding: 40px;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 8px 24px rgba(42, 183, 233, 0.08);
            position: relative;
        }

        .auth-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #2ab7e9, #4bc0e6);
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
        }

        .auth-card__logo {
            text-align: center;
            color: #199ecd;
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 8px;
            margin-top: 5px;
        }

        .auth-card__title {
            font-size: 16px;
            font-weight: normal;
            margin-bottom: 25px;
            text-align: center;
            color: var(--text-sub);
        }

        .form__group {
            margin-bottom: 20px;
        }

        .form__label {
            display: block;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 6px;
            color: var(--text-sub);
        }

        .form__input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: #fafcfd;
            color: var(--text-main);
            box-sizing: border-box;
            font-size: 15px;
            transition: all 0.2s;
        }

        .form__input:focus {
            border-color: var(--btn-blue);
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(42, 183, 233, 0.15);
            outline: none;
        }

        .form-error {
            color: var(--error-text);
            background-color: var(--error-bg);
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            text-align: center;
        }

        .btn {
            display: block;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            border-radius: 8px;
            padding: 12px;
            font-size: 16px;
            cursor: pointer;
            border: none;
            box-sizing: border-box;
            transition: background-color 0.2s, transform 0.1s;
        }

        .btn:active {
            transform: scale(0.99);
        }

        .btn--primary {
            background-color: var(--btn-blue);
            color: #ffffff;
        }

        .btn--primary:hover {
            background-color: var(--btn-blue-hover);
        }

        .btn--block {
            width: 100%;
        }

        .auth-card__link {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: var(--text-sub);
        }

        .auth-card__link a {
            color: var(--btn-blue);
            text-decoration: none;
            font-weight: bold;
        }

        .auth-card__link a:hover {
            text-decoration: underline;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--text-sub);
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="page-auth">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-card__logo">いろはす王国</div>
            <h1 class="auth-card__title">ログインして王国へ入国する</h1>

            <?php if (!empty($error_message)): ?>
                <div class="form-error"><?= h($error_message) ?></div>
            <?php endif; ?>

            <form method="post" action="login.php?redirect=<?= urlencode($redirect) ?>">
                <input type="hidden" name="redirect" value="<?= h($redirect) ?>">

                <div class="form__group">
                    <label for="username" class="form__label">ユーザー名</label>
                    <input type="text" name="username" id="username" class="form__input" value="<?= h($_POST['username'] ?? '') ?>" required>
                </div>

                <div class="form__group">
                    <label for="password" class="form__label">パスワード</label>
                    <input type="password" name="password" id="password" class="form__input" required>
                </div>

                <div class="form__group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" value="1"> ログイン状態を保持する
                    </label>
                </div>
                
                <button type="submit" class="btn btn--primary btn--block">ログイン</button>
            </form>

            <div class="auth-card__link">
                アカウントをお持ちでない方は <a href="register.php">新規登録</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>