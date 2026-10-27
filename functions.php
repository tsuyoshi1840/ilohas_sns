<?php 
function protect($string){
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function db_connect(): PDO {
    $host = 'localhost';
    $db   = 'ilohas_db'; // データベース名を 'login' に合わせる
    $user = 'root';  // XAMPPの標準ユーザー名
    $pass = '';      // XAMPPの標準パスワード（初期値は空）
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

    $opt = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // 取得データを連想配列に統一
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false, // 複数のSQL文を同時に実行させない（セキュリティ向上）
    ];

    $dbh = new PDO($dsn, $user, $pass, $opt);
    return $dbh;
}

// ===== CSRF 対策(なりすまし送信を防ぐ) =====
// フォームに hidden の csrf_token(値は csrf_token() の戻り値)を入れ、
// 受け取る側で csrf_check() が true か確認する。※ session_start() の後で使うこと
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_check(): bool {
    return isset($_SESSION['csrf_token'], $_POST['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}


// ===== アイコン画像の URL =====
// DB の users.icon_path には「uploads/icons/xxxx.png」のような“置き場所”だけが入っている。
// 未設定、またはファイルが見つからないときはデフォルト画像を返す。
function icon_url(?string $path): string {
    if ($path !== null && $path !== '' && is_file(__DIR__ . '/' . $path)) {
        return $path;
    }
    return 'img/default-icon.png';
}

// ===== タイムラインの取得(index.php / profile.php 共通) =====
// ・通常の投稿と、リポストされた投稿を「起きた時刻順」に並べる
// ・いいね数 / リポスト数 / 自分がいいね・リポスト済みか も一緒に取る
// $me          … ログイン中のユーザーID(未ログインは 0 → 何にも一致しない)
// $only_user   … 指定するとそのユーザーの投稿とリポストだけ(プロフィール用)
function fetch_timeline(PDO $pdo, int $me, ?int $only_user = null): array {
    $where_post   = $only_user !== null ? 'AND p0.user_id = :u1' : '';
    $where_repost = $only_user !== null ? 'AND r.user_id = :u2'  : '';

    // ※ 同じ名前のプレースホルダは2回使えない(EMULATE_PREPARES=false)ので :me1 / :me2 と分ける
    $sql = "SELECT t.sort_at, t.reposter_id, ru.username AS reposter_name,
                   p.id, p.user_id, p.content, p.created_at, p.updated_at, p.image,
                   u.username, u.icon_path,


                   p.reply_to_id, pu.username AS reply_to_name,
                   (SELECT COUNT(*) FROM posts c WHERE c.reply_to_id = p.id AND c.deleted_at IS NULL) AS reply_count,

                   (SELECT COUNT(*) FROM likes   l  WHERE l.post_id  = p.id) AS like_count,
                   (SELECT COUNT(*) FROM reposts r2 WHERE r2.post_id = p.id) AS repost_count,
                   EXISTS(SELECT 1 FROM likes   l2 WHERE l2.post_id = p.id AND l2.user_id = :me1) AS liked,
                   EXISTS(SELECT 1 FROM reposts r3 WHERE r3.post_id = p.id AND r3.user_id = :me2) AS reposted
            FROM (
                SELECT p0.id AS post_id, p0.created_at AS sort_at, NULL AS reposter_id
                FROM posts AS p0
                WHERE p0.deleted_at IS NULL $where_post
                  AND p0.reply_to_id IS NULL  
                UNION ALL
                SELECT r.post_id, r.created_at, r.user_id
                FROM reposts AS r
                JOIN posts AS p1 ON p1.id = r.post_id
                WHERE p1.deleted_at IS NULL $where_repost
            ) AS t
            JOIN posts AS p       ON p.id  = t.post_id
            LEFT JOIN users AS u  ON u.id  = p.user_id
            LEFT JOIN users AS ru ON ru.id = t.reposter_id

            LEFT JOIN posts AS pp ON pp.id = p.reply_to_id   
            LEFT JOIN users AS pu ON pu.id = pp.user_id      

            ORDER BY t.sort_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':me1', $me, PDO::PARAM_INT);
    $stmt->bindValue(':me2', $me, PDO::PARAM_INT);
    if ($only_user !== null) {
        $stmt->bindValue(':u1', $only_user, PDO::PARAM_INT);
        $stmt->bindValue(':u2', $only_user, PDO::PARAM_INT);
    }
    $stmt->execute();

   // ----------------------------実装2026/10/22/10:22-------------------------------------------
    // 1行分の整形は build_post() にまとめた(詳細ページでも同じ形を使うため)
    $posts = [];
    $all_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all_rows as $row) {
        $post = build_post($row, $me);
        // リポストとして並んでいる行なら、リポストした人の名前
        $post['reposted_by'] = $row['reposter_id'] !== null ? ($row['reposter_name'] ?? '名前未設定') : null;
        $posts[] = $post;
    }

   // ----------------------------実装2026/10/22/10:22-------------------------------------------
    return $posts;
}

// ===== いいね / リポストの切り替え(like.php / repost.php 共通) =====
// まだしていなければ追加、していれば取り消し。結果と最新の数を返す。
function toggle_reaction(PDO $pdo, string $table, int $user_id, int $post_id): array {
    if (!in_array($table, ['likes', 'reposts'], true)) {   // テーブル名は決まったものだけ
        throw new InvalidArgumentException('bad table');
    }

    // 削除済み・存在しない投稿には反応できない
    $stmt = $pdo->prepare('SELECT 1 FROM posts WHERE id = ? AND deleted_at IS NULL');
    $stmt->execute([$post_id]);
    if (!$stmt->fetchColumn()) {
        return ['ok' => false];
    }

    $stmt = $pdo->prepare("SELECT id FROM $table WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        $pdo->prepare("DELETE FROM $table WHERE id = ?")->execute([$exists]);
        $active = false;
    } else {
        try {
            $pdo->prepare("INSERT INTO $table (user_id, post_id) VALUES (?, ?)")->execute([$user_id, $post_id]);
        } catch (PDOException $e) {
            // 連打で同時に2回届いたとき(UNIQUE 制約)は「済み」として扱う
        }
        $active = true;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE post_id = ?");
    $stmt->execute([$post_id]);

    return ['ok' => true, 'active' => $active, 'count' => (int)$stmt->fetchColumn()];
}


// ===== 1件分の投稿データを画面用に整える(タイムライン・詳細ページ共通) =====
function build_post(array $row, int $me): array {
    $timestamp = strtotime($row['created_at']);
    return [
        'image_path'    => $row['image'] ?? null,
        'id'            => (int)$row['id'],
        'user_id'       => (int)$row['user_id'],
        'user_name'     => $row['username'] ?? '名前未設定',
        'icon'          => icon_url($row['icon_path'] ?? null),
        'datetime'      => date('Y-m-d\TH:i', $timestamp),
        'display_time'  => date('n月j日 H:i', $timestamp),
        'body'          => $row['content'],
        'edited'        => !empty($row['updated_at']),
        'likes'         => (int)$row['like_count'],
        'reposts'       => (int)$row['repost_count'],
        'replies'       => (int)($row['reply_count'] ?? 0),
        'liked'         => (bool)$row['liked'],
        'reposted'      => (bool)$row['reposted'],
        'is_my_post'    => $me > 0 && (int)$row['user_id'] === $me,
        'reply_to_id'   => $row['reply_to_id'] !== null ? (int)$row['reply_to_id'] : null,
        'reply_to_name' => $row['reply_to_name'] ?? null,
        'reposted_by'   => null,
        'deleted'       => !empty($row['deleted_at']),
    ];
}

// ===== 詳細ページ用：スレッド(元の投稿 + 返信 + 返信への返信…)を取得 =====
// $post_id にはスレッド内のどの投稿の id を渡してもよい(いちばん上の投稿を自動で探す)。
// 戻り値：['root_id' => 一番上の投稿id, 'items' => [ ['post' => 投稿データ, 'depth' => 段の深さ], ... ] ]
// 見つからなければ null。
function fetch_thread(PDO $pdo, int $me, int $post_id): ?array {
    // ① いちばん上(返信ではない投稿)までさかのぼる
    $find = $pdo->prepare('SELECT id, reply_to_id FROM posts WHERE id = ?');
    $root_id = $post_id;
    for ($i = 0; $i < 100; $i++) {                  // 念のため100段で打ち切り
        $find->execute([$root_id]);
        $node = $find->fetch(PDO::FETCH_ASSOC);
        if (!$node) {
            return null;
        }
        if ($node['reply_to_id'] === null) {
            break;
        }
        $root_id = (int)$node['reply_to_id'];
    }

    // ② 下へ向かって、返信・返信への返信…の id を全部集める
    $children = [];                                 // 親id => [子id, ...]
    $all_ids  = [$root_id];
    $frontier = [$root_id];
    while ($frontier) {
        $in   = implode(',', array_fill(0, count($frontier), '?'));
        $stmt = $pdo->prepare("SELECT id, reply_to_id FROM posts WHERE reply_to_id IN ($in) ORDER BY created_at ASC, id ASC");
        $stmt->execute($frontier);
        $frontier = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $children[(int)$r['reply_to_id']][] = (int)$r['id'];
            $all_ids[]  = (int)$r['id'];
            $frontier[] = (int)$r['id'];
        }
    }

    // ③ 集めた投稿の中身を、いいね数などと一緒にまとめて取得（ここに p.image を追加しました！）
    $in  = implode(',', array_fill(0, count($all_ids), '?'));
    $sql = "SELECT p.id, p.user_id, p.content, p.created_at, p.updated_at, p.deleted_at, p.reply_to_id, p.image,
                   u.username, u.icon_path, pu.username AS reply_to_name,
                   (SELECT COUNT(*) FROM posts c    WHERE c.reply_to_id = p.id AND c.deleted_at IS NULL) AS reply_count,
                   (SELECT COUNT(*) FROM likes l    WHERE l.post_id  = p.id) AS like_count,
                   (SELECT COUNT(*) FROM reposts r2 WHERE r2.post_id = p.id) AS repost_count,
                   EXISTS(SELECT 1 FROM likes   l2 WHERE l2.post_id = p.id AND l2.user_id = ?) AS liked,
                   EXISTS(SELECT 1 FROM reposts r3 WHERE r3.post_id = p.id AND r3.user_id = ?) AS reposted
            FROM posts AS p
            LEFT JOIN users AS u  ON u.id  = p.user_id
            LEFT JOIN posts AS pp ON pp.id = p.reply_to_id
            LEFT JOIN users AS pu ON pu.id = pp.user_id
            WHERE p.id IN ($in)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$me, $me], $all_ids));
    $by_id = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $by_id[(int)$row['id']] = build_post($row, $me);
    }

    // ④ 木の形に並べる(親 → その返信 → その返信…)。
    //    削除済みの投稿は、下に生きている返信があるときだけ「削除されました」として残す
    $items = [];
    $walk = function (int $id, int $depth) use (&$walk, &$items, $children, $by_id): bool {
        if (!isset($by_id[$id])) {
            return false;
        }
        $pos = count($items);
        $items[] = ['post' => $by_id[$id], 'depth' => $depth];
        $has_visible_child = false;
        foreach ($children[$id] ?? [] as $child) {
            $has_visible_child = $walk($child, $depth + 1) || $has_visible_child;
        }
        if ($by_id[$id]['deleted'] && !$has_visible_child) {
            array_splice($items, $pos, 1);           // 返信もない削除済み投稿は出さない
            return false;
        }
        return true;
    };
    $walk($root_id, 0);

    return ['root_id' => $root_id, 'items' => $items];
}
?>