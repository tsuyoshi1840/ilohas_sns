<?php
// ===== 投稿の保存処理 (post.php) =====
session_start();
require 'functions.php'; // db_connect() が入っているファイル

// ① ログインしていなければログイン画面（または登録画面）へ
if (!isset($_SESSION['user_id'])) {
    header('Location: register.php');
    exit;
}

// ② POST以外で直接アクセスされた場合は index.php へ戻す
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// ③ フォームから送られてきた内容（textareaの name="body"）を受け取る
$body    = trim($_POST['body'] ?? '');
$user_id = (int)$_SESSION['user_id']; // 投稿者はセッションから確実に取得
// 返信の時普段はNULL
$reply_to_id = isset($_POST['reply_to_id']) && $_POST['reply_to_id'] !=='' ? (int)$_POST['reply_to_id']:null;
// 終わりに戻る場所
$back = $reply_to_id !== null ? 'post_detail.php?id=' . $reply_to_id:'index.php';
// 検出<script
if (preg_match(' /<[\s　]*\/?[\s　]*(script|img|svg|iframe|object|embed|style|link|meta|body|video|audio)\b|\bon[a-z]+[\s　]*=|javascript[\s　]*:|\'[\s　]*or[\s　]+\'?\d+\'?[\s　]*=[\s　]*\'?\d+|\bunion[\s　]+(all[\s　]+)?select\b|;[\s　]*(drop|delete|truncate)[\s　]+|\'[\s　]*--/iu',$body)){
    header('Location:neko.php');
    exit;
}
// 💡 画像の保存パス用変数
$image_path = null;
$uploadFileDir = 'uploads/';

// --- パターンA: フォームからファイル（画像）が直接アップロードされた場合 ---
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['image']['tmp_name'];
    $fileName = $_FILES['image']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (in_array($fileExtension, $allowedExtensions)) {
        if (!is_dir($uploadFileDir)) {
            mkdir($uploadFileDir, 0755, true);
        }
        
        $newFileName = md5(time() . mt_rand() . $fileName) . '.' . $fileExtension;
        $dest_path = $uploadFileDir . $newFileName;
        
        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            chmod($dest_path, 0644);
            $image_path = $dest_path;
        }
    }
}

// --- パターンB: ファイルが未選択で、本文中に画像のURLが含まれている場合 ---
if ($image_path === null && preg_match('/https?:\/\/[^\s"\'<>#%\{\}\|\\\^\~\[\]`]+\.(jpg|jpeg|png|gif|webp)(?:\?[^\s]*)?/i', $body, $matches)) {
    $image_url = $matches[0];
    $fileExtension = strtolower($matches[1]);
    
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($fileExtension, $allowedExtensions)) {
        $ch = curl_init($image_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $img_data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200 && $img_data !== false) {
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            
            $newFileName = md5(time() . mt_rand() . $image_url) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;
            
            if (file_put_contents($dest_path, $img_data) !== false) {
                chmod($dest_path, 0644);
                $image_path = $dest_path;
            }
        }
    }
}

// ④ 入力値のチェック
$error = '';
if ($body === '') {
    $error = '投稿内容を入力してください';
} elseif (mb_strlen($body) > 140) {
    $error = '投稿は140文字以内で入力してください';
}

// エラーがあった場合は、セッションにエラー文を入れて index.php に戻す
if ($error !== '') {
    $_SESSION['post_error'] = $error;
    header('Location: ' . $back);
    exit;
}

// ⑤ データベースに保存
try {
    $pdo = db_connect();

    // 返信先が「存在して、削除されていない」か確認する
    if ($reply_to_id !== null) {
        $check = $pdo->prepare('SELECT 1 FROM posts WHERE id = ? AND deleted_at IS NULL');
        $check->execute([$reply_to_id]);
        if (!$check->fetchColumn()) {
            $_SESSION['post_error'] = '返信先のポストが見つかりません';
            header('Location: index.php');
            exit;
        }
    }

    // ★修正: INSERT文に `image` カラムを追加し、execute に `$image_path` を渡すように変更
    $stmt = $pdo->prepare(
        'INSERT INTO posts (user_id, reply_to_id, content, image, created_at) VALUES (?, ?, ?, ?, NOW())'
    );
    
    // 4つの ? に対応する変数を順番に渡す
    $stmt->execute([$user_id, $reply_to_id, $body, $image_path]);

    // 返信なら、書いた返信の位置までスクロールさせる
    if ($reply_to_id !== null) {
        $back .= '#post-' . (int)$pdo->lastInsertId();
    }

} catch (PDOException $e) {
    // エラー時の処理（デバッグ時は $e->getMessage() を入れると原因が分かりやすくなります）
    $_SESSION['post_error'] = '投稿に失敗しました: ' . $e->getMessage();
}

// ⑥ 処理が終わったら元の画面へ
header('Location: ' . $back);
exit;