<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';

header('Content-Type: application/xml; charset=UTF-8');

$posts = $pdo->query("SELECT slug, updated_at FROM posts WHERE status = 'publish' ORDER BY updated_at DESC")->fetchAll();
$pages = $pdo->query("SELECT slug, updated_at FROM site_pages WHERE status = 'publish' ORDER BY updated_at DESC")->fetchAll();
$cats = $pdo->query('SELECT slug FROM categories ORDER BY name ASC')->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

$urls = [
    [public_url(), date('c')],
    [public_url('ara'), date('c')],
];
foreach ($posts as $row) {
    $urls[] = [post_permalink($row['slug']), date('c', strtotime((string) $row['updated_at']) ?: time())];
}
foreach ($pages as $row) {
    $urls[] = [page_permalink($row['slug']), date('c', strtotime((string) $row['updated_at']) ?: time())];
}
foreach ($cats as $row) {
    $urls[] = [category_permalink($row['slug']), date('c')];
}

foreach ($urls as $item) {
    echo '  <url><loc>' . e($item[0]) . '</loc><lastmod>' . e($item[1]) . '</lastmod></url>' . "\n";
}
echo '</urlset>';
