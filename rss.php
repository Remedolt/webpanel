<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';

header('Content-Type: application/rss+xml; charset=UTF-8');
$posts = [];
try {
    $posts = $pdo->query(
        "SELECT p.title, p.slug, p.excerpt, p.content, p.created_at, u.display_name
         FROM posts p
         INNER JOIN users u ON u.id = p.author_id
         WHERE p.status = 'publish'
         ORDER BY p.created_at DESC
         LIMIT 30"
    )->fetchAll();
} catch (PDOException $e) {
    $posts = [];
}

$esc = static function ($value) {
    return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
};

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<rss version="2.0"><channel>' . "\n";
echo '<title>' . $esc($siteTitle) . '</title>' . "\n";
echo '<link>' . $esc(public_url()) . '</link>' . "\n";
echo '<description>' . $esc($siteTagline !== '' ? $siteTagline : $siteTitle) . '</description>' . "\n";
echo '<language>tr</language>' . "\n";
foreach ($posts as $post) {
    $desc = trim((string) ($post['excerpt'] ?? ''));
    if ($desc === '') {
        $desc = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($post['content'] ?? ''))));
        if (function_exists('mb_substr')) {
            $desc = mb_substr($desc, 0, 220, 'UTF-8');
        } else {
            $desc = substr($desc, 0, 220);
        }
    }
    $ts = strtotime((string) $post['created_at']);
    echo '<item>';
    echo '<title>' . $esc($post['title']) . '</title>';
    echo '<link>' . $esc(post_permalink($post['slug'])) . '</link>';
    echo '<guid>' . $esc(post_permalink($post['slug'])) . '</guid>';
    echo '<pubDate>' . $esc($ts ? date(DATE_RSS, $ts) : date(DATE_RSS)) . '</pubDate>';
    echo '<author>' . $esc($post['display_name']) . '</author>';
    echo '<description>' . $esc($desc) . '</description>';
    echo '</item>' . "\n";
}
echo '</channel></rss>';
