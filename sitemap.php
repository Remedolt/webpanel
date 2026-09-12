<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';

header('Content-Type: application/xml; charset=UTF-8');
$urls = array(
    public_url(),
    contact_permalink(),
    gallery_permalink(),
    faq_permalink(),
    staff_list_permalink(),
    partners_permalink(),
    posts_list_permalink(),
    services_permalink(),
    rss_permalink(),
);
try {
    foreach ($pdo->query("SELECT slug, updated_at FROM site_pages WHERE status = 'publish'") as $row) {
        $urls[] = page_permalink($row['slug']);
    }
} catch (PDOException $e) {
}
try {
    foreach ($pdo->query("SELECT slug FROM posts WHERE status = 'publish'") as $row) {
        $urls[] = post_permalink($row['slug']);
    }
} catch (PDOException $e) {
}
try {
    foreach ($pdo->query("SELECT slug FROM gallery_albums WHERE status = 'publish'") as $row) {
        $urls[] = gallery_permalink($row['slug']);
    }
} catch (PDOException $e) {
}
try {
    foreach ($pdo->query("SELECT slug FROM services WHERE status = 'publish'") as $row) {
        $urls[] = service_permalink($row['slug']);
    }
} catch (PDOException $e) {
}
try {
    foreach ($pdo->query('SELECT slug FROM categories') as $row) {
        $urls[] = category_permalink($row['slug']);
    }
} catch (PDOException $e) {
}
$urls = array_values(array_unique($urls));
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $url) {
    echo '  <url><loc>' . htmlspecialchars((string) $url, ENT_XML1, 'UTF-8') . '</loc></url>' . "\n";
}
echo '</urlset>';
