<?php
declare(strict_types=1);

$siteRoot = dirname(__DIR__);
$dbCandidates = array(
    $siteRoot . DIRECTORY_SEPARATOR . 'webpanel' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'db.php',
    $siteRoot . DIRECTORY_SEPARATOR . 'panel' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'db.php',
    $siteRoot . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'db.php',
);

$loaded = false;
foreach ($dbCandidates as $dbFile) {
    if (is_file($dbFile)) {
        require $dbFile;
        $loaded = true;
        break;
    }
}

if (!$loaded || !isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(503);
    echo 'Site henüz bağlanmadı. Yönetim paneli üzerinden kurulumu tamamlayın.';
    exit;
}

$httpsOn = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443')
    || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');

if (session_status() !== PHP_SESSION_ACTIVE) {
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params(array(
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $httpsOn,
            'httponly' => true,
            'samesite' => 'Lax',
        ));
    } else {
        session_set_cookie_params(0, '/', '', $httpsOn, true);
    }
    session_start();
}

cms_ensure_schema($pdo);

if (!function_exists('public_html')) {
    function public_html($html)
    {
        $html = (string) $html;
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;
        $html = preg_replace('#<iframe\b[^>]*>.*?</iframe>#is', '', $html) ?? $html;
        $html = preg_replace('#<object\b[^>]*>.*?</object>#is', '', $html) ?? $html;
        $html = preg_replace('#<embed\b[^>]*>#is', '', $html) ?? $html;
        $html = preg_replace('#on[a-z]+\s*=#i', '', $html) ?? $html;
        $html = preg_replace('#javascript:#i', '', $html) ?? $html;
        return $html;
    }
}

$siteTitle = option_get($pdo, 'site_title', 'Kodcu');
$siteTagline = option_get($pdo, 'site_tagline', '');
$a11yWidget = option_get($pdo, 'a11y_widget', '1') === '1';
$a11yPosition = option_get($pdo, 'a11y_position', 'left') === 'right' ? 'right' : 'left';
$a11ySkip = option_get($pdo, 'a11y_skip_link', '1') === '1';
$navPages = $pdo->query("SELECT title, slug FROM site_pages WHERE status = 'publish' ORDER BY title ASC")->fetchAll();
$navCategories = $pdo->query(
    "SELECT c.name, c.slug, COUNT(p.id) AS post_count
     FROM categories c
     LEFT JOIN post_categories pc ON pc.category_id = c.id
     LEFT JOIN posts p ON p.id = pc.post_id AND p.status = 'publish'
     GROUP BY c.id, c.name, c.slug
     HAVING post_count > 0
     ORDER BY c.name ASC"
)->fetchAll();
$sidebarRecent = $pdo->query(
    "SELECT title, slug, created_at
     FROM posts
     WHERE status = 'publish'
     ORDER BY created_at DESC
     LIMIT 5"
)->fetchAll();
$currentPath = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$searchQuery = trim((string) ($_GET['q'] ?? ''));
$metaDescription = $siteTagline !== '' ? $siteTagline : ($siteTitle . ' — yazılım notları ve rehberler');
$canonicalUrl = public_url(ltrim((string) (parse_url($currentPath, PHP_URL_PATH) ?? ''), '/'));
$ogImage = '';
