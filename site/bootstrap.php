<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

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

cms_ensure_schema($pdo);
cms_seed($pdo);
cms_apply_redirects($pdo);

if (!function_exists('public_html')) {
    function public_html($html)
    {
        $html = (string) $html;
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
        return $html;
    }
}

$siteTitle = option_get($pdo, 'site_title', 'Kodcu');
$siteTagline = option_get($pdo, 'site_tagline', '');
$seoTitle = option_get($pdo, 'seo_title', $siteTitle);
$seoDescription = option_get($pdo, 'seo_description', $siteTagline);
$seoKeywords = option_get($pdo, 'seo_keywords', '');
$seoRobots = option_get($pdo, 'seo_robots', 'index,follow');
$navPages = [];
try {
    $navPages = $pdo->query("SELECT title, slug FROM site_pages WHERE status = 'publish' ORDER BY sort_order ASC, title ASC")->fetchAll();
} catch (PDOException $e) {
    $navPages = $pdo->query("SELECT title, slug FROM site_pages WHERE status = 'publish' ORDER BY title ASC")->fetchAll();
}
$headerMenuTree = menu_tree($pdo, 'header');
$footerMenuTree = menu_tree($pdo, 'footer');
$menuTree = $headerMenuTree;
$headerType = option_get($pdo, 'header_type', 'top');
$headerSize = option_get($pdo, 'header_size', 'md');
$headerBg = hex_color(option_get($pdo, 'header_bg', '#0f172a'), '#0f172a');
$headerText = hex_color(option_get($pdo, 'header_text', '#e2e8f0'), '#e2e8f0');
$headerAccent = hex_color(option_get($pdo, 'header_accent', '#38bdf8'), '#38bdf8');
$cmsBrand = cms_brand($pdo);
$headerSticky = option_get($pdo, 'header_sticky', '0');
$headerWidth = option_get($pdo, 'header_width', 'boxed');
$mobileMenuType = option_get($pdo, 'mobile_menu_type', 'drawer');
$mobileBtnStyle = option_get($pdo, 'mobile_btn_style', 'hamburger');
$mobileShowPages = option_get($pdo, 'mobile_show_pages', '1');
$mobileShowHome = option_get($pdo, 'mobile_show_home', '1');
$footerType = option_get($pdo, 'footer_type', 'simple');
$footerSize = option_get($pdo, 'footer_size', 'md');
$footerBg = hex_color(option_get($pdo, 'footer_bg', '#0f172a'), '#0f172a');
$footerText = hex_color(option_get($pdo, 'footer_text', '#94a3b8'), '#94a3b8');
$footerCustom = option_get($pdo, 'footer_text_custom', '');
$footerShowPages = option_get($pdo, 'footer_show_pages', '0');
$homePagesHeading = option_get($pdo, 'home_pages_heading', 'Sayfalar');
$galleryHeading = option_get($pdo, 'gallery_heading', 'Galeri');
$galleryCtaText = option_get($pdo, 'gallery_cta_text', 'Tüm galeri');
$galleryCtaUrl = option_get($pdo, 'gallery_cta_url', '');
$galleryShowHome = option_get($pdo, 'gallery_show_home', '1');
$floatButtons = [];
try {
    $floatButtons = $pdo->query("SELECT * FROM float_buttons WHERE status = 'publish' ORDER BY sort_order ASC, id ASC")->fetchAll();
} catch (PDOException $e) {
    $floatButtons = [];
}
$sitePopup = $pdo->query("SELECT * FROM popups WHERE status = 'publish' ORDER BY id ASC LIMIT 1")->fetch() ?: null;
$homePages = [];
try {
    $homePages = $pdo->query(
        "SELECT title, slug, excerpt, featured_image FROM site_pages WHERE status = 'publish' AND show_on_home = 1 ORDER BY sort_order ASC, title ASC"
    )->fetchAll();
} catch (PDOException $e) {
    $homePages = $navPages;
}

$scriptName = strtolower(basename((string) ($_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['SCRIPT_NAME'] ?? '')));
$skipMaintenance = in_array($scriptName, array('sitemap.php', 'robots.php', 'rss.php'), true);
if (!$skipMaintenance && option_get($pdo, 'maintenance_mode', '0') === '1') {
    $role = (string) ($_SESSION['user_role'] ?? '');
    if ($role !== 'admin' && $role !== 'editor') {
        http_response_code(503);
        header('Retry-After: 3600');
        $maintText = option_get($pdo, 'maintenance_text', 'Sitemiz kısa süreli bakımdadır. Lütfen daha sonra tekrar deneyin.');
        echo '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Bakım — ' . e($siteTitle) . '</title>' . cms_brand_font_link($cmsBrand) . '</head>';
        echo '<body style="margin:0;font-family:' . $cmsBrand['font'] . ';background:#0f172a;color:#e2e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px">';
        echo '<div style="max-width:28rem;text-align:center"><p style="letter-spacing:.16em;text-transform:uppercase;font-size:12px;color:' . e($cmsBrand['primary']) . '">Bakım</p>';
        echo '<h1 style="font-size:1.75rem;margin:12px 0">' . e($siteTitle) . '</h1>';
        echo '<p style="line-height:1.6;color:#94a3b8">' . e($maintText) . '</p></div></body></html>';
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && (string) $_POST['action'] === 'subscribe') {
    csrf_verify();
    $email = trim((string) ($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('error', 'Geçerli bir e-posta girin.');
    } else {
        try {
            $ins = $pdo->prepare('INSERT IGNORE INTO subscribers (email) VALUES (?)');
            $ins->execute([$email]);
            flash_set('success', 'Bültene kaydoldunuz.');
        } catch (PDOException $e) {
            flash_set('error', 'Kayıt alınamadı. Daha sonra deneyin.');
        }
    }
    $back = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    $base = rtrim(PUBLIC_URL, '/');
    if ($back === '' || strpos($back, $base) !== 0) {
        $back = public_url();
    }
    header('Location: ' . $back);
    exit;
}
