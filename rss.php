<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';

header('Content-Type: application/rss+xml; charset=UTF-8');

$posts = $pdo->query(
    "SELECT p.title, p.slug, p.excerpt, p.content, p.created_at
     FROM posts p
     WHERE p.status = 'publish'
     ORDER BY p.created_at DESC
     LIMIT 30"
)->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0">
  <channel>
    <title><?= e($siteTitle) ?></title>
    <link><?= e(public_url()) ?></link>
    <description><?= e($siteTagline !== '' ? $siteTagline : $siteTitle) ?></description>
    <language>tr</language>
    <?php foreach ($posts as $post): ?>
    <item>
      <title><?= e((string) $post['title']) ?></title>
      <link><?= e(post_permalink($post['slug'])) ?></link>
      <guid><?= e(post_permalink($post['slug'])) ?></guid>
      <pubDate><?= e(date(DATE_RSS, strtotime((string) $post['created_at']) ?: time())) ?></pubDate>
      <description><?= e(excerpt_plain((string) (($post['excerpt'] !== '' ? $post['excerpt'] : $post['content'])), 240)) ?></description>
    </item>
    <?php endforeach; ?>
  </channel>
</rss>
