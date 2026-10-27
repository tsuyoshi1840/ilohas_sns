<?php 
session_start();
if(!isset($_SESSION['username'])){
    header('Location:login.php');
    exit;
}

$allowed = [
    '172.19.71.107', // 今後、必要に応じて残すか削除
    '127.0.0.1',     // ローカルテスト用（IPv4）
    '::1'            // ローカルテスト用（IPv6）
];

$client_ip =$_SERVER['REMOTE_ADDR']??'';
$is_admin_ip =in_array($client_ip,$allowed,true);



echo '<h1>';
echo 'ようこそ！いろはす王国へ！';
echo '</h1>';



?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>マイページ</title>
</head>
<body>
    <h1>ようこそ、<?= htmlspecialchars($_SESSION["username"], ENT_QUOTES, 'UTF-8') ?> さん！</h1>
    <p>ここはいろはす王国の一般マイページです。</p>

    <!-- 🌐 管理者IPからアクセスしている場合のみ、管理者サイトへのリンクを表示する -->
    <?php if ($is_admin_ip): ?>
        <div style="margin: 20px 0; padding: 15px; background: #ffe6e6; border: 1px solid #ff9999;">
            <p>管理者権限が確認されました：</p>
            <a href="admin.php"><button type="button">管理者サイトへ移動する</button></a>
        </div>
    <?php endif; ?>

    <p>
        <a href="logout_confirm.php">ログアウトはこちら</a>
    </p>
</body>
</html>

