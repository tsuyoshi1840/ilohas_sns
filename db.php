<?php 
$host = 'localhost';
$db   = 'irohas_db';
$user = 'root'; 
$pass = '';     
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// PDOのオプション設定
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // エラー発生時に例外をスローする
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // 取得データを連想配列に統一する
    PDO::ATTR_EMULATE_PREPARES   => false,                // 静的プレースホルダーを使用する（セキュリティ向上）
];

try {
    // 接続インスタンスの生成
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // 接続失敗時のエラー処理
    exit('データベース接続失敗: ' . $e->getMessage());
}