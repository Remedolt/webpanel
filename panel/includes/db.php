<?php
declare(strict_types=1);

$dbHost = 'localhost';
$dbName = '';
$dbUser = '';
$dbPass = '';
$dbCharset = 'utf8mb4';
$dbConnectError = '';
$pdo = null;

$configFile = __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$configCandidates = array(
    __DIR__ . DIRECTORY_SEPARATOR . 'config.php',
    dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'config.php',
);
foreach ($configCandidates as $candidate) {
    if (is_file($candidate)) {
        $configFile = $candidate;
        require $candidate;
        break;
    }
}

$httpsOn = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443')
    || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
$httpHost = (string) ($_SERVER['HTTP_HOST'] ?? 'kodcu.site');
$detectedPublic = ($httpsOn ? 'https' : 'http') . '://' . $httpHost;

$panelRootFs = str_replace('\\', '/', dirname(__DIR__));
$docRootFs = '';
if (!empty($_SERVER['DOCUMENT_ROOT'])) {
    $resolvedRoot = realpath($_SERVER['DOCUMENT_ROOT']);
    if ($resolvedRoot !== false) {
        $docRootFs = str_replace('\\', '/', $resolvedRoot);
    }
}
$panelRel = '';
if ($docRootFs !== '' && strpos($panelRootFs, $docRootFs) === 0) {
    $panelRel = substr($panelRootFs, strlen($docRootFs));
    $panelRel = '/' . trim(str_replace('\\', '/', $panelRel), '/');
    if ($panelRel === '/') {
        $panelRel = '';
    }
} else {
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/webpanel/index.php'));
    $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($scriptDir !== '/' && $scriptDir !== '.' && $scriptDir !== '') {
        $panelRel = $scriptDir;
    }
}
$detectedPanel = $detectedPublic . $panelRel;

if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Remedolt Web Panel');
}
if (!defined('PUBLIC_URL')) {
    define('PUBLIC_URL', $detectedPublic);
}
if (!defined('PANEL_URL')) {
    define('PANEL_URL', $detectedPanel);
}
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
}
if (!defined('UPLOAD_URL')) {
    define('UPLOAD_URL', PANEL_URL . '/uploads/');
}
if (!defined('MAX_UPLOAD_BYTES')) {
    define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024);
}

$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$skipConnect = defined('CMS_SKIP_CONNECT') && CMS_SKIP_CONNECT;
if (!$skipConnect && $dbName !== '' && $dbUser !== '') {
    try {
        $dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";
        $pdo = new PDO($dsn, $dbUser, $dbPass, $pdoOptions);
    } catch (PDOException $e) {
        $pdo = null;
        $dbConnectError = $e->getMessage();
    }
}

if (!function_exists('e')) {
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
}

if (!function_exists('public_html')) {
function public_html($html)
{
    $html = (string) $html;
    $stripped = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
    return is_string($stripped) ? $stripped : $html;
}
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $token = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (!is_string($token) || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
        http_response_code(403);
        exit('Geçersiz güvenlik jetonu. Sayfayı yenileyip tekrar deneyin.');
    }
}

function slugify(string $text): string
{
    $map = [
        'ş' => 's', 'Ş' => 's', 'ı' => 'i', 'İ' => 'i', 'ğ' => 'g', 'Ğ' => 'g',
        'ü' => 'u', 'Ü' => 'u', 'ö' => 'o', 'Ö' => 'o', 'ç' => 'c', 'Ç' => 'c',
    ];
    $text = strtr($text, $map);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'yazi';
}

function unique_slug(PDO $pdo, string $slug, ?int $ignoreId = null): string
{
    $base = $slug;
    $i = 2;
    while (true) {
        if ($ignoreId !== null) {
            $stmt = $pdo->prepare('SELECT id FROM posts WHERE slug = ? AND id != ? LIMIT 1');
            $stmt->execute([$slug, $ignoreId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM posts WHERE slug = ? LIMIT 1');
            $stmt->execute([$slug]);
        }
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
    }
}

function unique_page_slug(PDO $pdo, string $slug, ?int $ignoreId = null): string
{
    $base = $slug;
    $i = 2;
    while (true) {
        if ($ignoreId !== null) {
            $stmt = $pdo->prepare('SELECT id FROM site_pages WHERE slug = ? AND id != ? LIMIT 1');
            $stmt->execute([$slug, $ignoreId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM site_pages WHERE slug = ? LIMIT 1');
            $stmt->execute([$slug]);
        }
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
    }
}

function unique_gallery_slug(PDO $pdo, string $slug, ?int $ignoreId = null): string
{
    $base = $slug;
    $i = 2;
    while (true) {
        if ($ignoreId !== null) {
            $stmt = $pdo->prepare('SELECT id FROM gallery_albums WHERE slug = ? AND id != ? LIMIT 1');
            $stmt->execute([$slug, $ignoreId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM gallery_albums WHERE slug = ? LIMIT 1');
            $stmt->execute([$slug]);
        }
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
    }
}

function unique_service_slug(PDO $pdo, string $slug, ?int $ignoreId = null): string
{
    $base = $slug;
    $i = 2;
    while (true) {
        if ($ignoreId !== null) {
            $stmt = $pdo->prepare('SELECT id FROM services WHERE slug = ? AND id != ? LIMIT 1');
            $stmt->execute([$slug, $ignoreId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM services WHERE slug = ? LIMIT 1');
            $stmt->execute([$slug]);
        }
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
    }
}

function cms_ensure_schema(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS sliders (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            subtitle TEXT NULL,
            image VARCHAR(255) NULL,
            button_text VARCHAR(120) NULL,
            button_url VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            status ENUM('draft','publish') NOT NULL DEFAULT 'publish',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_sliders_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    cms_add_column($pdo, 'sliders', 'youtube_url', 'VARCHAR(255) NULL');
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS menus (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(120) NOT NULL,
            url VARCHAR(255) NOT NULL DEFAULT '#',
            parent_id INT UNSIGNED NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            item_style ENUM('link','dropdown','button') NOT NULL DEFAULT 'link',
            status ENUM('draft','publish') NOT NULL DEFAULT 'publish',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_menus_parent (parent_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    cms_add_column($pdo, 'menus', 'location', "VARCHAR(20) NOT NULL DEFAULT 'header'");
    try {
        $pdo->exec("ALTER TABLE menus MODIFY item_style VARCHAR(20) NOT NULL DEFAULT 'link'");
    } catch (PDOException $e) {
        // geç
    }
    cms_add_column($pdo, 'site_pages', 'excerpt', 'TEXT NULL');
    cms_add_column($pdo, 'site_pages', 'featured_image', 'VARCHAR(255) NULL');
    cms_add_column($pdo, 'site_pages', 'template', "VARCHAR(40) NOT NULL DEFAULT 'default'");
    cms_add_column($pdo, 'site_pages', 'show_on_home', 'TINYINT(1) NOT NULL DEFAULT 1');
    cms_add_column($pdo, 'site_pages', 'sort_order', 'INT NOT NULL DEFAULT 0');
    cms_add_column($pdo, 'site_pages', 'seo_title', 'VARCHAR(255) NULL');
    cms_add_column($pdo, 'site_pages', 'seo_description', 'TEXT NULL');
    cms_add_column($pdo, 'site_pages', 'map_embed', 'TEXT NULL');
    cms_add_column($pdo, 'site_pages', 'video_url', 'VARCHAR(500) NULL');
    cms_add_column($pdo, 'users', 'avatar', 'VARCHAR(255) NULL');
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS inquiries (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            page_id INT UNSIGNED NULL,
            author_name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_inquiries_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    cms_add_column($pdo, 'inquiries', 'is_read', 'TINYINT(1) NOT NULL DEFAULT 0');
    cms_add_column($pdo, 'inquiries', 'phone', 'VARCHAR(40) NULL');
    cms_add_column($pdo, 'inquiries', 'subject', "VARCHAR(40) NOT NULL DEFAULT 'mesaj'");
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS gallery_albums (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            cover_image VARCHAR(255) NULL,
            tile_size VARCHAR(20) NOT NULL DEFAULT 'square',
            sort_order INT NOT NULL DEFAULT 0,
            status ENUM('draft','publish') NOT NULL DEFAULT 'publish',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_gallery_albums_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    cms_add_column($pdo, 'gallery_albums', 'description', 'TEXT NULL');
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS gallery_images (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            album_id INT UNSIGNED NOT NULL,
            image VARCHAR(255) NOT NULL,
            caption VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_gallery_images_album (album_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS activity_log (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL DEFAULT 0,
            user_name VARCHAR(120) NOT NULL DEFAULT '',
            action VARCHAR(40) NOT NULL,
            entity VARCHAR(40) NOT NULL,
            entity_id INT UNSIGNED NOT NULL DEFAULT 0,
            title VARCHAR(255) NOT NULL DEFAULT '',
            ip VARCHAR(45) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_activity_created (created_at),
            KEY idx_activity_entity (entity)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS float_buttons (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            label VARCHAR(80) NOT NULL,
            url VARCHAR(255) NOT NULL DEFAULT '',
            icon_type VARCHAR(20) NOT NULL DEFAULT 'phone',
            color VARCHAR(7) NOT NULL DEFAULT '#2563eb',
            sort_order INT NOT NULL DEFAULT 0,
            status ENUM('draft','publish') NOT NULL DEFAULT 'publish',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS page_images (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            page_id INT UNSIGNED NOT NULL,
            image VARCHAR(255) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_page_images_page (page_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS partners (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(160) NOT NULL,
            logo VARCHAR(255) NULL,
            url VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            status ENUM('draft','publish') NOT NULL DEFAULT 'publish',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS staff (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(160) NOT NULL,
            title VARCHAR(160) NULL,
            photo VARCHAR(255) NULL,
            interests TEXT NULL,
            bio TEXT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            status ENUM('draft','publish') NOT NULL DEFAULT 'publish',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS counters (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            label VARCHAR(120) NOT NULL,
            value_num INT NOT NULL DEFAULT 0,
            suffix VARCHAR(20) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            status ENUM('draft','publish') NOT NULL DEFAULT 'publish',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS faqs (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            question VARCHAR(255) NOT NULL,
            answer TEXT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            status ENUM('draft','publish') NOT NULL DEFAULT 'publish',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS testimonials (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(160) NOT NULL,
            title VARCHAR(160) NULL,
            quote TEXT NULL,
            rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
            sort_order INT NOT NULL DEFAULT 0,
            status ENUM('draft','publish') NOT NULL DEFAULT 'publish',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS subscribers (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(190) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_subscribers_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS services (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            excerpt TEXT NULL,
            content LONGTEXT NULL,
            image VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            status ENUM('draft','publish') NOT NULL DEFAULT 'publish',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_services_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    try {
        $pdo->exec('DROP TABLE IF EXISTS pricing');
        $pdo->exec("DELETE FROM options WHERE option_key IN ('pricing_heading','pricing_show_home')");
    } catch (PDOException $e) {
    }
    try {
        $st = $pdo->query("SELECT id, url FROM menus WHERE url LIKE '%fiyatlar%'");
        if ($st) {
            $updMenu = $pdo->prepare('UPDATE menus SET url = ? WHERE id = ?');
            foreach ($st->fetchAll() as $row) {
                $url = str_replace(array('/fiyatlar', 'fiyatlar.php'), array('/hizmetler', 'hizmetler.php'), (string) $row['url']);
                $updMenu->execute([$url, (int) $row['id']]);
            }
        }
    } catch (PDOException $e) {
    }
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS redirects (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            source VARCHAR(255) NOT NULL,
            target VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_redirects_source (source)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS popups (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            content TEXT NULL,
            button_text VARCHAR(120) NULL,
            button_url VARCHAR(255) NULL,
            delay_seconds INT UNSIGNED NOT NULL DEFAULT 2,
            status ENUM('draft','publish') NOT NULL DEFAULT 'draft',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $defaults = array(
        'seo_title' => '',
        'seo_description' => '',
        'seo_keywords' => '',
        'seo_robots' => 'index,follow',
        'favicon_path' => '',
        'popup_enabled' => '0',
        'header_type' => 'top',
        'header_size' => 'md',
        'header_bg' => '#0f172a',
        'header_text' => '#e2e8f0',
        'header_accent' => '#38bdf8',
        'brand_primary' => '#0ea5e9',
        'brand_dark' => '#0369a1',
        'brand_font' => 'system',
        'header_sticky' => '0',
        'header_width' => 'boxed',
        'mobile_menu_type' => 'drawer',
        'mobile_btn_style' => 'hamburger',
        'mobile_show_pages' => '1',
        'mobile_show_home' => '1',
        'footer_type' => 'simple',
        'footer_size' => 'md',
        'footer_bg' => '#0f172a',
        'footer_text' => '#94a3b8',
        'footer_text_custom' => '',
        'footer_show_pages' => '0',
        'home_pages_heading' => 'Sayfalar',
        'gallery_heading' => 'Galeri',
        'gallery_cta_text' => 'Tüm galeri',
        'gallery_cta_url' => '',
        'gallery_show_home' => '1',
        'panel_theme' => 'classic',
        'site_logo' => '',
        'site_logo_retina' => '',
        'site_logo_mobile' => '',
        'site_logo_mobile_retina' => '',
        'site_logo_alt' => '',
        'site_logo_height' => '40',
        'site_logo_mobile_height' => '32',
        'site_logo_show_title' => '1',
        'site_logo_link' => '',
        'panel_logo' => '',
        'panel_logo_retina' => '',
        'panel_logo_height' => '32',
        'panel_logo_show_name' => '1',
        'contact_heading' => 'İletişim',
        'contact_intro' => '<p>Bize ulaşın. Form, adres ve harita bu sayfada hazırdır.</p>',
        'contact_address' => 'Kadıköy, İstanbul',
        'contact_phone' => '',
        'contact_email' => '',
        'contact_whatsapp' => '',
        'contact_hours' => 'Hafta içi 09:00–18:00',
        'contact_map' => 'Kadıköy, İstanbul',
        'contact_show_form' => '1',
        'partners_heading' => 'Referanslar',
        'partners_show_home' => '1',
        'staff_heading' => 'Ekibimiz',
        'staff_show_home' => '1',
        'counters_heading' => 'Rakamlarla',
        'counters_show_home' => '1',
        'faq_heading' => 'Sıkça sorulanlar',
        'faq_show_home' => '1',
        'cta_heading' => 'Birlikte çalışalım',
        'cta_text' => 'Projeniz veya sorunuz için bize yazın. Form ve harita iletişim sayfasında hazır.',
        'cta_button' => 'İletişime geç',
        'cta_url' => '',
        'cta_show_home' => '1',
        'spot_show_home' => '1',
        'spot_kicker' => 'Çalışma şeklimiz',
        'spot_heading' => 'Sade, canlı ve panelden yönetilen bir site deneyimi',
        'spot_text' => 'Paylaşımlı sunucuda vanilla PHP ile kuruyoruz. İçerikleri siz güncellersiniz; menü, iletişim ve galeri tek panelde durur.',
        'spot_image' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1400&q=80',
        'spot_badge_kicker' => 'Stüdyodan',
        'spot_badge_title' => 'Tasarım masası',
        'spot_image_2' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=1400&q=80',
        'spot_badge_kicker_2' => 'Ekipten',
        'spot_badge_title_2' => 'Planlama toplantısı',
        'spot_1_title' => 'Yönetim paneli',
        'spot_1_text' => 'Yazı, sayfa, galeri ve hizmetleri tarayıcıdan yayınlayın. Kod yazmadan.',
        'spot_2_title' => 'Hızlı teslim',
        'spot_2_text' => 'İlk yayına kısa sürede çıkarız. Mobil uyum, form ve menü hazır gelir.',
        'spot_3_title' => 'Yayından sonra destek',
        'spot_3_text' => 'Yedek, güncelleme ve küçük revizyonlar. Site yalnız bırakılmaz.',
        'film_show_home' => '1',
        'film_kicker' => 'Tanıtım',
        'film_heading' => 'Nasıl çalıştığımızı izleyin',
        'film_text' => 'Kısa bakış: sade site, canlı görünüm, panelden yönetim. Kapak ve videoyu buradan değiştirirsiniz.',
        'film_overlay' => "Kodcu\nCanlı site",
        'film_url' => '',
        'film_poster' => 'https://images.unsplash.com/photo-1480714378408-67cf0d13bc1b?auto=format&fit=crop&w=1600&q=80',
        'place_show_home' => '1',
        'place_kicker' => 'Çalışma alanımız',
        'place_heading' => 'İki bakış, tek sade deneyim',
        'place_1_kicker' => 'Stüdyo — tasarım',
        'place_1_title' => 'Tasarım masası',
        'place_1_text' => 'Görsel dil, sayfa düzeni ve içerik birlikte kurulur. Site canlı durur.',
        'place_1_link' => '/hizmetler',
        'place_1_btn' => 'İncele',
        'place_1_image' => 'https://images.unsplash.com/photo-1480714378408-67cf0d13bc1b?auto=format&fit=crop&w=1400&q=80',
        'place_2_kicker' => 'Panel — yönetim',
        'place_2_title' => 'Yönetim ekranı',
        'place_2_text' => 'Yazı, menü ve görseller tarayıcıdan güncellenir. Kod yazmadan.',
        'place_2_link' => '/iletisim',
        'place_2_btn' => 'İncele',
        'place_2_image' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1400&q=80',
        'pages_show_home' => '1',
        'posts_show_home' => '1',
        'home_modules' => 'counters,pages,gallery,services,spot,film,places,staff,partners,reviews,faq,cta,posts',
        'reviews_heading' => 'Müşteri yorumları',
        'reviews_show_home' => '1',
        'bar_text' => 'Yeni projeler için iletişime geçin.',
        'bar_url' => '',
        'bar_show' => '1',
        'cookie_text' => 'Bu sitede deneyiminiz için çerez kullanılır. Devam ederek kabul etmiş olursunuz.',
        'cookie_show' => '1',
        'newsletter_heading' => 'Bülten',
        'newsletter_text' => 'Yenilikleri e-postanıza gönderelim.',
        'newsletter_show' => '1',
        'services_heading' => 'Hizmetler',
        'services_show_home' => '1',
        'social_facebook' => '',
        'social_instagram' => '',
        'social_twitter' => '',
        'social_youtube' => '',
        'social_linkedin' => '',
        'social_tiktok' => '',
        'social_heading' => 'Bizi takip edin',
        'og_image' => '',
        'analytics_head' => '',
        'analytics_body' => '',
        'maintenance_mode' => '0',
        'maintenance_text' => 'Sitemiz kısa süreli bakımdadır. Lütfen daha sonra tekrar deneyin.',
        'slider_interval' => '6',
        'slider_autoplay' => '1',
        'contact_notify_mail' => '1',
        'custom_css' => '',
    );
    $insOpt = $pdo->prepare('INSERT IGNORE INTO options (option_key, option_value) VALUES (?, ?)');
    foreach ($defaults as $key => $value) {
        $insOpt->execute([$key, $value]);
    }
    try {
        $pdo->exec("UPDATE options SET option_value = 'Bizi takip edin' WHERE option_key = 'social_heading' AND option_value IN ('FOLLOW US','Follow us','Follow Us')");
    } catch (PDOException $e) {
    }
    try {
        $pdo->exec("UPDATE options SET option_value = '0' WHERE option_key = 'footer_show_pages' AND option_value = '1'");
    } catch (PDOException $e) {
    }
    try {
        $pdo->exec("UPDATE options SET option_value = 'Galeri' WHERE option_key = 'gallery_heading' AND option_value IN ('Test','test')");
    } catch (PDOException $e) {
        // geç
    }
    try {
        $menuRows = $pdo->query('SELECT id, url FROM menus')->fetchAll();
        foreach ($menuRows as $row) {
            $url = (string) $row['url'];
            $new = $url;
            if (preg_match('/sayfa\.php\?slug=([A-Za-z0-9\-]+)/', $url, $m)) {
                $new = page_permalink($m[1]);
            } elseif (preg_match('/yazi\.php\?slug=([A-Za-z0-9\-]+)/', $url, $m)) {
                $new = post_permalink($m[1]);
            }
            if ($new !== $url) {
                $pdo->prepare('UPDATE menus SET url = ? WHERE id = ?')->execute([$new, (int) $row['id']]);
            }
        }
    } catch (PDOException $e) {
        // menü tablosu yoksa geç
    }
}

function menu_tree(PDO $pdo, $location = 'header'): array
{
    $location = ($location === 'footer') ? 'footer' : 'header';
    try {
        $stmt = $pdo->prepare("SELECT id, title, url, parent_id, sort_order, item_style, status, location FROM menus WHERE status = 'publish' AND location = ? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$location]);
        $rows = $stmt->fetchAll();
    } catch (PDOException $e) {
        try {
            $rows = $pdo->query("SELECT id, title, url, parent_id, sort_order, item_style, status FROM menus WHERE status = 'publish' ORDER BY sort_order ASC, id ASC")->fetchAll();
            if ($location === 'footer') {
                return array();
            }
        } catch (PDOException $e2) {
            return array();
        }
    }
    $tree = array();
    foreach ($rows as $row) {
        $pid = (int) $row['parent_id'];
        if (!isset($tree[$pid])) {
            $tree[$pid] = array();
        }
        $tree[$pid][] = $row;
    }
    return $tree;
}

function cms_add_column(PDO $pdo, string $table, string $column, string $definition): void
{
    $table = preg_replace('/[^a-z0-9_]/', '', strtolower($table)) ?? '';
    $column = preg_replace('/[^a-z0-9_]/', '', strtolower($column)) ?? '';
    if ($table === '' || $column === '') {
        return;
    }
    try {
        $exists = $pdo->query('SHOW COLUMNS FROM `' . $table . '` LIKE ' . $pdo->quote($column))->fetch();
        if (!$exists) {
            $pdo->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
        }
    } catch (PDOException $e) {
        // tablo henüz yoksa geç
    }
}

function option_pick($value, array $allowed, string $fallback): string
{
    $value = (string) $value;
    return in_array($value, $allowed, true) ? $value : $fallback;
}

function panel_themes(): array
{
    return array(
        'classic' => array(
            'name' => 'Klasik',
            'desc' => 'Koyu lacivert menü, WordPress mavisi',
            'sidebar' => '#0f172a',
            'sidebar_text' => '#cbd5e1',
            'sidebar_muted' => '#94a3b8',
            'sidebar_hover' => '#1e293b',
            'sidebar_active' => '#1e293b',
            'nav_active' => '#ffffff',
            'accent' => '#2271b1',
            'accent_hover' => '#1d4ed8',
            'header' => '#ffffff',
            'header_text' => '#334155',
            'header_border' => '#e2e8f0',
            'body' => '#f1f5f9',
        ),
        'midnight' => array(
            'name' => 'Gece',
            'desc' => 'Siyah menü, camgöbeği vurgu',
            'sidebar' => '#020617',
            'sidebar_text' => '#cbd5e1',
            'sidebar_muted' => '#64748b',
            'sidebar_hover' => '#0f172a',
            'sidebar_active' => '#111827',
            'nav_active' => '#ffffff',
            'accent' => '#06b6d4',
            'accent_hover' => '#0891b2',
            'header' => '#0f172a',
            'header_text' => '#e2e8f0',
            'header_border' => '#1e293b',
            'body' => '#0b1220',
        ),
        'ocean' => array(
            'name' => 'Okyanus',
            'desc' => 'Petrol mavisi, turkuaz vurgu',
            'sidebar' => '#082f49',
            'sidebar_text' => '#bae6fd',
            'sidebar_muted' => '#7dd3fc',
            'sidebar_hover' => '#0c4a6e',
            'sidebar_active' => '#075985',
            'nav_active' => '#ffffff',
            'accent' => '#14b8a6',
            'accent_hover' => '#0d9488',
            'header' => '#ffffff',
            'header_text' => '#0f172a',
            'header_border' => '#e2e8f0',
            'body' => '#ecfeff',
        ),
        'forest' => array(
            'name' => 'Orman',
            'desc' => 'Koyu yeşil menü, yaprak yeşili vurgu',
            'sidebar' => '#14532d',
            'sidebar_text' => '#dcfce7',
            'sidebar_muted' => '#86efac',
            'sidebar_hover' => '#166534',
            'sidebar_active' => '#15803d',
            'nav_active' => '#ffffff',
            'accent' => '#65a30d',
            'accent_hover' => '#4d7c0f',
            'header' => '#ffffff',
            'header_text' => '#14532d',
            'header_border' => '#d9f99d',
            'body' => '#f7fee7',
        ),
        'grape' => array(
            'name' => 'Üzüm',
            'desc' => 'Mor menü, fuşya vurgu',
            'sidebar' => '#3b0764',
            'sidebar_text' => '#f3e8ff',
            'sidebar_muted' => '#d8b4fe',
            'sidebar_hover' => '#4c1d95',
            'sidebar_active' => '#6b21a8',
            'nav_active' => '#ffffff',
            'accent' => '#d946ef',
            'accent_hover' => '#c026d3',
            'header' => '#ffffff',
            'header_text' => '#3b0764',
            'header_border' => '#f3e8ff',
            'body' => '#faf5ff',
        ),
        'ember' => array(
            'name' => 'Kor',
            'desc' => 'Koyu kahve menü, turuncu vurgu',
            'sidebar' => '#431407',
            'sidebar_text' => '#ffedd5',
            'sidebar_muted' => '#fdba74',
            'sidebar_hover' => '#7c2d12',
            'sidebar_active' => '#9a3412',
            'nav_active' => '#ffffff',
            'accent' => '#f97316',
            'accent_hover' => '#ea580c',
            'header' => '#fff7ed',
            'header_text' => '#431407',
            'header_border' => '#fed7aa',
            'body' => '#fff7ed',
        ),
        'rose' => array(
            'name' => 'Gül',
            'desc' => 'Bordo menü, gül pembesi vurgu',
            'sidebar' => '#4c0519',
            'sidebar_text' => '#ffe4e6',
            'sidebar_muted' => '#fda4af',
            'sidebar_hover' => '#881337',
            'sidebar_active' => '#9f1239',
            'nav_active' => '#ffffff',
            'accent' => '#e11d48',
            'accent_hover' => '#be123c',
            'header' => '#ffffff',
            'header_text' => '#4c0519',
            'header_border' => '#fecdd3',
            'body' => '#fff1f2',
        ),
        'light' => array(
            'name' => 'Açık',
            'desc' => 'Beyaz menü, indigo vurgu',
            'sidebar' => '#ffffff',
            'sidebar_text' => '#334155',
            'sidebar_muted' => '#64748b',
            'sidebar_hover' => '#f1f5f9',
            'sidebar_active' => '#eef2ff',
            'nav_active' => '#312e81',
            'accent' => '#4f46e5',
            'accent_hover' => '#4338ca',
            'header' => '#ffffff',
            'header_text' => '#0f172a',
            'header_border' => '#e2e8f0',
            'body' => '#f8fafc',
        ),
    );
}

function panel_theme_get(PDO $pdo): array
{
    $themes = panel_themes();
    $id = option_get($pdo, 'panel_theme', 'classic');
    if (!isset($themes[$id])) {
        $id = 'classic';
    }
    $theme = $themes[$id];
    $theme['id'] = $id;
    return $theme;
}

function panel_theme_css(array $t): string
{
    $v = static function ($key, $fallback) use ($t) {
        $val = isset($t[$key]) ? (string) $t[$key] : $fallback;
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $val) ? $val : $fallback;
    };
    $sidebar = $v('sidebar', '#0f172a');
    $sidebarText = $v('sidebar_text', '#cbd5e1');
    $sidebarMuted = $v('sidebar_muted', '#94a3b8');
    $sidebarHover = $v('sidebar_hover', '#1e293b');
    $sidebarActive = $v('sidebar_active', '#1e293b');
    $navActive = $v('nav_active', '#ffffff');
    $accent = $v('accent', '#2271b1');
    $accentHover = $v('accent_hover', '#1d4ed8');
    $header = $v('header', '#ffffff');
    $headerText = $v('header_text', '#334155');
    $headerBorder = $v('header_border', '#e2e8f0');
    $body = $v('body', '#f1f5f9');
    return ':root{--p-sidebar:' . $sidebar . ';--p-sidebar-text:' . $sidebarText . ';--p-sidebar-muted:' . $sidebarMuted
        . ';--p-sidebar-hover:' . $sidebarHover . ';--p-sidebar-active:' . $sidebarActive . ';--p-nav-active:' . $navActive
        . ';--p-accent:' . $accent . ';--p-accent-hover:' . $accentHover . ';--p-header:' . $header . ';--p-header-text:' . $headerText
        . ';--p-header-border:' . $headerBorder . ';--p-body:' . $body . '}'
        . 'body.panel-app{background:var(--p-body)}'
        . '#admin-sidebar{background:var(--p-sidebar)!important;color:var(--p-sidebar-text)!important}'
        . '#admin-sidebar .panel-brand-title{color:var(--p-nav-active)}'
        . '#admin-sidebar .panel-brand-sub{color:var(--p-sidebar-muted)}'
        . '#admin-sidebar .panel-brand-mark{background:var(--p-accent)}'
        . '.panel-nav-link{display:flex;align-items:center;gap:.75rem;padding:.625rem 1rem;font-size:13px;font-weight:500;color:var(--p-sidebar-text);border-left:3px solid transparent}'
        . '.panel-nav-link:hover{background:var(--p-sidebar-hover);color:var(--p-nav-active)}'
        . '.panel-nav-link.is-active{background:var(--p-sidebar-active);color:var(--p-nav-active);border-left-color:var(--p-accent)}'
        . '.panel-nav-sub{display:block;padding:.5rem 1rem .5rem 3rem;font-size:13px;color:var(--p-sidebar-muted)}'
        . '.panel-nav-sub:hover,.panel-nav-sub.is-active{color:var(--p-nav-active)}'
        . '.panel-subwrap{background:rgba(0,0,0,.18)}'
        . 'html[data-panel-theme="light"] .panel-subwrap{background:#f8fafc}'
        . 'html[data-panel-theme="light"] #admin-sidebar{border-right:1px solid var(--p-header-border)}'
        . 'html[data-panel-theme="light"] #admin-sidebar .border-b{border-color:var(--p-header-border)!important}'
        . '#admin-shell>header{background:var(--p-header)!important;border-color:var(--p-header-border)!important;color:var(--p-header-text)}'
        . '[class*="bg-[#2271b1]"]{background-color:var(--p-accent)!important}'
        . '[class*="hover:bg-blue-800"]:hover{background-color:var(--p-accent-hover)!important}'
        . '[class*="text-[#2271b1]"]{color:var(--p-accent)!important}'
        . '#admin-shell>header .text-slate-600,#admin-shell>header .text-slate-700{color:var(--p-header-text)!important}'
        . 'html[data-panel-theme="midnight"] .bg-white{background:#111827!important;color:#e2e8f0}'
        . 'html[data-panel-theme="midnight"] .text-slate-900,html[data-panel-theme="midnight"] .text-slate-800{color:#f1f5f9!important}'
        . 'html[data-panel-theme="midnight"] .text-slate-500,html[data-panel-theme="midnight"] .text-slate-600{color:#94a3b8!important}'
        . 'html[data-panel-theme="midnight"] .border-slate-200{border-color:#1e293b!important}'
        . 'html[data-panel-theme="midnight"] input,html[data-panel-theme="midnight"] select,html[data-panel-theme="midnight"] textarea{background:#0f172a;color:#e2e8f0;border-color:#334155}'
        . '#admin-sidebar{display:flex;flex-direction:column}'
        . '#admin-sidebar nav{flex:1;overflow-y:auto;padding-bottom:1rem}'
        . '.panel-nav-label{padding:.85rem 1rem .35rem;font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--p-sidebar-muted);opacity:.85}'
        . '.panel-nav-link{border-radius:0 8px 8px 0;margin-right:8px}'
        . '.panel-badge{margin-left:auto;min-width:1.25rem;height:1.25rem;padding:0 .35rem;border-radius:999px;background:var(--p-accent);color:#fff;font-size:10px;font-weight:700;display:inline-flex;align-items:center;justify-content:center}'
        . '.panel-app main .bg-white.rounded-lg,.panel-app main .bg-white.rounded-xl{border-radius:14px;box-shadow:0 1px 2px rgba(15,23,42,.05),0 10px 28px rgba(15,23,42,.05)}'
        . '.panel-app main h1{letter-spacing:-.02em}'
        . '.panel-empty{padding:2.5rem 1.25rem;text-align:center}'
        . '.panel-empty p{color:#64748b;font-size:.875rem}'
        . '.panel-chip{display:inline-flex;align-items:center;gap:.35rem;border-radius:999px;padding:.2rem .65rem;font-size:12px;font-weight:600}'
        . '@media(min-width:1024px){#admin-shell{grid-template-columns:16.5rem 1fr}}';
}

function hex_color($value, $fallback)
{
    $value = trim((string) $value);
    if (preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
        return $value;
    }
    return $fallback;
}

function cms_hex_rgb(string $hex): array
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
        return array(14, 165, 233);
    }
    return array(
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    );
}

function cms_on_color(string $hex): string
{
    $rgb = cms_hex_rgb($hex);
    $luma = (0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2]) / 255;
    return $luma > 0.62 ? '#0f172a' : '#ffffff';
}

function cms_hex_shift(string $hex, float $amount): string
{
    $rgb = cms_hex_rgb($hex);
    $out = array();
    foreach ($rgb as $c) {
        if ($amount < 0) {
            $n = (int) round($c * (1 + $amount));
        } else {
            $n = (int) round($c + ((255 - $c) * $amount));
        }
        $out[] = max(0, min(255, $n));
    }
    return sprintf('#%02x%02x%02x', $out[0], $out[1], $out[2]);
}

function cms_fonts(): array
{
    return array(
        'system' => array(
            'name' => 'Sistem',
            'css' => 'ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif',
            'google' => '',
        ),
        'inter' => array(
            'name' => 'Inter',
            'css' => 'Inter,ui-sans-serif,system-ui,sans-serif',
            'google' => 'Inter:wght@400;500;600;700;800',
        ),
        'source' => array(
            'name' => 'Source Sans 3',
            'css' => '"Source Sans 3",ui-sans-serif,system-ui,sans-serif',
            'google' => 'Source+Sans+3:wght@400;500;600;700;800',
        ),
        'nunito' => array(
            'name' => 'Nunito',
            'css' => 'Nunito,ui-sans-serif,system-ui,sans-serif',
            'google' => 'Nunito:wght@400;600;700;800',
        ),
        'outfit' => array(
            'name' => 'Outfit',
            'css' => 'Outfit,ui-sans-serif,system-ui,sans-serif',
            'google' => 'Outfit:wght@400;500;600;700;800',
        ),
        'poppins' => array(
            'name' => 'Poppins',
            'css' => 'Poppins,ui-sans-serif,system-ui,sans-serif',
            'google' => 'Poppins:wght@400;500;600;700;800',
        ),
        'manrope' => array(
            'name' => 'Manrope',
            'css' => 'Manrope,ui-sans-serif,system-ui,sans-serif',
            'google' => 'Manrope:wght@400;500;600;700;800',
        ),
        'jakarta' => array(
            'name' => 'Plus Jakarta Sans',
            'css' => '"Plus Jakarta Sans",ui-sans-serif,system-ui,sans-serif',
            'google' => 'Plus+Jakarta+Sans:wght@400;500;600;700;800',
        ),
        'dm' => array(
            'name' => 'DM Sans',
            'css' => '"DM Sans",ui-sans-serif,system-ui,sans-serif',
            'google' => 'DM+Sans:wght@400;500;600;700;800',
        ),
        'grotesk' => array(
            'name' => 'Space Grotesk',
            'css' => '"Space Grotesk",ui-sans-serif,system-ui,sans-serif',
            'google' => 'Space+Grotesk:wght@400;500;600;700;800',
        ),
    );
}

function cms_brand_presets(): array
{
    return array(
        array('name' => 'Gök', 'primary' => '#0ea5e9', 'dark' => '#0369a1'),
        array('name' => 'Zeytin', 'primary' => '#65a30d', 'dark' => '#3f6212'),
        array('name' => 'Bordo', 'primary' => '#be123c', 'dark' => '#9f1239'),
        array('name' => 'Turuncu', 'primary' => '#ea580c', 'dark' => '#c2410c'),
        array('name' => 'Lacivert', 'primary' => '#1d4ed8', 'dark' => '#1e3a8a'),
        array('name' => 'Teal', 'primary' => '#0d9488', 'dark' => '#115e59'),
    );
}

function cms_brand(PDO $pdo): array
{
    $primary = hex_color(option_get($pdo, 'brand_primary', '#0ea5e9'), '#0ea5e9');
    $dark = hex_color(option_get($pdo, 'brand_dark', '#0369a1'), '#0369a1');
    $fonts = cms_fonts();
    $fontId = option_pick(option_get($pdo, 'brand_font', 'system'), array_keys($fonts), 'system');
    $font = $fonts[$fontId];
    $rgb = cms_hex_rgb($primary);
    return array(
        'primary' => $primary,
        'dark' => $dark,
        'ink' => cms_on_color($primary),
        'rgb' => $rgb[0] . ',' . $rgb[1] . ',' . $rgb[2],
        'font_id' => $fontId,
        'font' => $font['css'],
        'google' => $font['google'],
    );
}

function cms_brand_font_link(array $brand): string
{
    $g = trim((string) ($brand['google'] ?? ''));
    if ($g === '') {
        return '';
    }
    return '<link rel="preconnect" href="https://fonts.googleapis.com">'
        . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
        . '<link href="https://fonts.googleapis.com/css2?family=' . $g . '&display=swap" rel="stylesheet">';
}

function cms_clear_output(): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}

function redirect(string $url): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    cms_clear_output();
    if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
        $url = rtrim(PANEL_URL, '/') . '/' . ltrim($url, '/');
    }
    header('Location: ' . $url, true, 303);
    exit;
}

function cms_json_ok(): void
{
    cms_clear_output();
    header('Content-Type: application/json; charset=UTF-8');
    echo '{"ok":true}';
    exit;
}

function flash_set(string $type, string $message, string $kind = ''): void
{
    if ($kind === '' && $type !== 'error') {
        $lower = function_exists('mb_strtolower') ? mb_strtolower($message, 'UTF-8') : strtolower($message);
        if (strpos($lower, 'silindi') !== false) {
            $kind = 'delete';
        } elseif (strpos($lower, 'onaylandı') !== false) {
            $kind = 'approve';
        } elseif (strpos($lower, 'güncell') !== false || strpos($lower, 'kaydedildi') !== false || strpos($lower, 'yayımlandı') !== false) {
            $kind = 'update';
        } elseif (strpos($lower, 'eklendi') !== false || strpos($lower, 'oluşturuldu') !== false || strpos($lower, 'yüklendi') !== false) {
            $kind = 'add';
        } else {
            $kind = 'success';
        }
    }
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
        'kind' => $kind,
        'id' => bin2hex(random_bytes(6)),
    ];
}

function flash_get(): ?array
{
    if (empty($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function option_get(PDO $pdo, string $key, string $default = ''): string
{
    $stmt = $pdo->prepare('SELECT option_value FROM options WHERE option_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if (!$row || $row['option_value'] === null) {
        return $default;
    }
    return (string) $row['option_value'];
}

function option_set(PDO $pdo, string $key, string $value): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO options (option_key, option_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)'
    );
    $stmt->execute([$key, $value]);
}

function user_avatar_html(array $user, string $sizeClass = 'h-8 w-8'): string
{
    $name = (string) ($user['display_name'] ?? ($user['username'] ?? '?'));
    $initial = function_exists('mb_substr')
        ? mb_strtoupper(mb_substr($name, 0, 1))
        : strtoupper(substr($name, 0, 1));
    $avatar = trim((string) ($user['avatar'] ?? ''));
    if ($avatar !== '') {
        return '<img src="' . e(media_src($avatar)) . '" alt="" class="' . e($sizeClass) . ' rounded-full object-cover bg-slate-200 shrink-0">';
    }
    return '<span class="inline-flex ' . e($sizeClass) . ' items-center justify-center rounded-full bg-slate-900 text-xs font-semibold text-white shrink-0">' . e($initial) . '</span>';
}

function format_datetime(?string $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return e($value);
    }
    return date('d.m.Y H:i', $ts);
}

function public_url($path = '')
{
    $path = ltrim((string) $path, '/');
    return rtrim(PUBLIC_URL, '/') . ($path !== '' ? '/' . $path : '/');
}

function post_permalink($slug)
{
    return rtrim(PUBLIC_URL, '/') . '/yazi/' . rawurlencode((string) $slug);
}

function page_permalink($slug)
{
    return rtrim(PUBLIC_URL, '/') . '/' . rawurlencode((string) $slug);
}

function google_map_embed_src($input): string
{
    $input = trim((string) $input);
    if ($input === '') {
        return '';
    }
    if (preg_match('/<iframe[^>]*\ssrc=["\']([^"\']+)["\']/i', $input, $m)) {
        $input = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
    }
    $input = trim($input);
    if (preg_match('#^https://#i', $input)) {
        $parts = parse_url($input);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');
        if ($host !== '' && preg_match('/(^|\.)google\.(com|com\.tr)$/i', $host) && stripos($path, 'maps') !== false) {
            if (stripos($path, '/maps/embed') === false && stripos($input, 'output=embed') === false) {
                $input .= (strpos($input, '?') === false ? '?' : '&') . 'output=embed';
            }
            return $input;
        }
        return '';
    }
    if (strlen($input) > 180) {
        return '';
    }
    return 'https://maps.google.com/maps?q=' . rawurlencode($input) . '&hl=tr&z=15&output=embed';
}

function youtube_id($url): string
{
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }
    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $url)) {
        return $url;
    }
    if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|v/|live/))([A-Za-z0-9_-]{11})~', $url, $m)) {
        return $m[1];
    }
    return '';
}

function youtube_embed_src($url, $autoplay = false): string
{
    $id = youtube_id($url);
    if ($id === '') {
        return '';
    }
    $params = 'rel=0&modestbranding=1&playsinline=1&loop=1&playlist=' . rawurlencode($id);
    if ($autoplay) {
        $params .= '&autoplay=1&mute=1&controls=0';
    }
    return 'https://www.youtube.com/embed/' . rawurlencode($id) . '?' . $params;
}

function youtube_play_src($url): string
{
    $id = youtube_id($url);
    if ($id === '') {
        return '';
    }
    return 'https://www.youtube.com/embed/' . rawurlencode($id) . '?rel=0&modestbranding=1&playsinline=1&autoplay=1';
}

function youtube_poster_src($url): string
{
    $id = youtube_id($url);
    if ($id === '') {
        return '';
    }
    return 'https://i.ytimg.com/vi/' . rawurlencode($id) . '/hqdefault.jpg';
}

function cms_is_file_video($url): bool
{
    return (bool) preg_match('/\.(mp4|webm)(\?|$)/i', trim((string) $url));
}

function cms_film_source(string $url): array
{
    $url = trim($url);
    $yt = youtube_play_src($url);
    if ($yt !== '') {
        return array('type' => 'youtube', 'src' => $yt);
    }
    if ($url !== '' && cms_is_file_video($url)) {
        if (preg_match('#^https?://#i', $url) || strpos($url, 'uploads/') === 0) {
            return array('type' => 'file', 'src' => media_src($url));
        }
    }
    return array('type' => '', 'src' => '');
}

function cms_film_poster(PDO $pdo): string
{
    $poster = trim(option_get($pdo, 'film_poster', ''));
    if ($poster !== '') {
        return media_src($poster);
    }
    return youtube_poster_src(option_get($pdo, 'film_url', ''));
}

function cms_home_catalog(): array
{
    return array(
        'counters' => array('label' => 'Sayaçlar', 'edit' => 'index.php?page=counters', 'flag' => 'counters_show_home'),
        'pages' => array('label' => 'Sayfalar', 'edit' => 'index.php?page=pages', 'flag' => 'pages_show_home'),
        'gallery' => array('label' => 'Galeri', 'edit' => 'index.php?page=gallery', 'flag' => 'gallery_show_home'),
        'services' => array('label' => 'Hizmetler', 'edit' => 'index.php?page=services', 'flag' => 'services_show_home'),
        'spot' => array('label' => 'Vitrin', 'edit' => 'index.php?page=spot', 'flag' => 'spot_show_home'),
        'film' => array('label' => 'Video', 'edit' => 'index.php?page=video', 'flag' => 'film_show_home'),
        'places' => array('label' => 'Öne çıkanlar', 'edit' => 'index.php?page=places', 'flag' => 'place_show_home'),
        'staff' => array('label' => 'Ekip', 'edit' => 'index.php?page=staff', 'flag' => 'staff_show_home'),
        'partners' => array('label' => 'Referanslar', 'edit' => 'index.php?page=partners', 'flag' => 'partners_show_home'),
        'reviews' => array('label' => 'Yorumlar', 'edit' => 'index.php?page=reviews', 'flag' => 'reviews_show_home'),
        'faq' => array('label' => 'SSS', 'edit' => 'index.php?page=faq', 'flag' => 'faq_show_home'),
        'cta' => array('label' => 'Çağrı bandı', 'edit' => 'index.php?page=settings&tab=site', 'flag' => 'cta_show_home'),
        'posts' => array('label' => 'Yazılar', 'edit' => 'index.php?page=posts', 'flag' => 'posts_show_home'),
    );
}

function cms_home_modules(PDO $pdo): array
{
    $catalog = cms_home_catalog();
    $raw = trim(option_get($pdo, 'home_modules', ''));
    $ids = array();
    if ($raw !== '') {
        foreach (preg_split('/[,\s]+/', $raw) as $id) {
            $id = preg_replace('/[^a-z]/', '', strtolower((string) $id)) ?? '';
            if ($id !== '' && isset($catalog[$id]) && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }
    }
    foreach (array_keys($catalog) as $id) {
        if (!in_array($id, $ids, true)) {
            $ids[] = $id;
        }
    }
    return $ids;
}

function cms_home_save_order(PDO $pdo, array $ids): void
{
    $catalog = cms_home_catalog();
    $clean = array();
    foreach ($ids as $id) {
        $id = preg_replace('/[^a-z]/', '', strtolower((string) $id)) ?? '';
        if ($id !== '' && isset($catalog[$id]) && !in_array($id, $clean, true)) {
            $clean[] = $id;
        }
    }
    foreach (array_keys($catalog) as $id) {
        if (!in_array($id, $clean, true)) {
            $clean[] = $id;
        }
    }
    option_set($pdo, 'home_modules', implode(',', $clean));
}

function gallery_permalink($slug = '')
{
    $slug = trim((string) $slug);
    $base = rtrim(PUBLIC_URL, '/') . '/galeri';
    return $slug === '' ? $base : $base . '/' . rawurlencode($slug);
}

function float_button_href($type, $url)
{
    $type = (string) $type;
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }
    if ($type === 'phone') {
        if (strpos($url, 'tel:') === 0) {
            return $url;
        }
        $digits = preg_replace('/[^0-9+]/', '', $url) ?? '';
        return $digits !== '' ? ('tel:' . $digits) : '';
    }
    if ($type === 'whatsapp') {
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            return $url;
        }
        $digits = preg_replace('/[^0-9]/', '', $url) ?? '';
        return $digits !== '' ? ('https://wa.me/' . $digits) : '';
    }
    if ($type === 'email') {
        if (strpos($url, 'mailto:') === 0) {
            return $url;
        }
        return 'mailto:' . $url;
    }
    return $url;
}

function cms_float_icon(string $type): string
{
    $type = (string) $type;
    $svg = 'xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"';
    if ($type === 'whatsapp') {
        return '<svg ' . $svg . '>'
            . '<path d="M20.5 12a8.5 8.5 0 0 1-12.7 7.55L3.5 20.7l1.35-4.15A8.5 8.5 0 1 1 20.5 12Z"/>'
            . '<path d="M10.15 8.35h-.65a.7.7 0 0 0-.7.75c.07 2.85 2.35 5.35 5.3 5.45a.7.7 0 0 0 .78-.72v-.68a.5.5 0 0 0-.42-.5l-1-.16a.5.5 0 0 0-.5.18l-.3.4a4.2 4.2 0 0 1-2.1-2.1l.4-.3a.5.5 0 0 0 .18-.5l-.16-1a.5.5 0 0 0-.5-.42h-.6z"/>'
            . '</svg>';
    }
    if ($type === 'telegram') {
        return '<svg ' . $svg . '>'
            . '<path d="M21.6 3.4 11.15 13.4"/>'
            . '<path d="M21.6 3.4 14.7 21.2 11.15 13.4 2.8 9.95 21.6 3.4Z"/>'
            . '</svg>';
    }
    if ($type === 'email') {
        return '<svg ' . $svg . '>'
            . '<rect x="3.2" y="5.4" width="17.6" height="13.2" rx="2.2"/>'
            . '<path d="m4 7.1 8 6.1 8-6.1"/>'
            . '</svg>';
    }
    if ($type === 'instagram') {
        return '<svg ' . $svg . '>'
            . '<rect x="3.6" y="3.6" width="16.8" height="16.8" rx="5.4"/>'
            . '<circle cx="12" cy="12" r="3.6"/>'
            . '<circle cx="16.9" cy="7.1" r="1" fill="currentColor" stroke="none"/>'
            . '</svg>';
    }
    if ($type === 'link') {
        return '<svg ' . $svg . '>'
            . '<path d="m9.2 14.8 5.6-5.6"/>'
            . '<path d="M8.15 10.45 6.55 12.05a3.5 3.5 0 0 0 0 5.1 3.5 3.5 0 0 0 5.1 0l1.6-1.6"/>'
            . '<path d="m15.85 13.55 1.6-1.6a3.5 3.5 0 0 0 0-5.1 3.5 3.5 0 0 0-5.1 0l-1.6 1.6"/>'
            . '</svg>';
    }
    return '<svg ' . $svg . '>'
        . '<path d="M7.15 3.6H5.4A1.8 1.8 0 0 0 3.6 5.5c.18 8.2 6.7 14.7 14.9 14.9a1.8 1.8 0 0 0 1.9-1.8v-1.75a1.2 1.2 0 0 0-1-1.18l-2.55-.4a1.2 1.2 0 0 0-1.22.45l-.78 1.05a12.4 12.4 0 0 1-5.55-5.55l1.05-.78a1.2 1.2 0 0 0 .45-1.22l-.4-2.55a1.2 1.2 0 0 0-1.18-1z"/>'
        . '</svg>';
}

function contact_permalink()
{
    return rtrim(PUBLIC_URL, '/') . '/iletisim';
}

function faq_permalink()
{
    return rtrim(PUBLIC_URL, '/') . '/sss';
}

function search_permalink($q = '')
{
    $base = rtrim(PUBLIC_URL, '/') . '/ara';
    $q = trim((string) $q);
    return $q === '' ? $base : ($base . '?q=' . rawurlencode($q));
}

function staff_list_permalink()
{
    return rtrim(PUBLIC_URL, '/') . '/ekip';
}

function partners_permalink()
{
    return rtrim(PUBLIC_URL, '/') . '/referanslar';
}

function posts_list_permalink($page = 1)
{
    $page = (int) $page;
    $base = rtrim(PUBLIC_URL, '/') . '/yazilar';
    return $page > 1 ? ($base . '?p=' . $page) : $base;
}

function services_permalink()
{
    return rtrim(PUBLIC_URL, '/') . '/hizmetler';
}

function service_permalink($slug)
{
    return rtrim(PUBLIC_URL, '/') . '/hizmet/' . rawurlencode((string) $slug);
}

function cms_service_card(array $item, int $i = 0): void
{
    $n = str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
    $img = trim((string) ($item['image'] ?? ''));
    echo '<a href="' . e(service_permalink((string) ($item['slug'] ?? ''))) . '" class="cms-svc-card" style="transition-delay:' . ((int) $i * 120) . 'ms">';
    echo '<span class="cms-svc-no" aria-hidden="true">' . e($n) . '</span>';
    if ($img !== '') {
        echo '<span class="cms-svc-media"><img src="' . e(media_src($img)) . '" alt=""></span>';
    } else {
        echo '<span class="cms-svc-media cms-svc-media-empty"></span>';
    }
    echo '<span class="cms-svc-body">';
    echo '<h3>' . e((string) ($item['title'] ?? '')) . '</h3>';
    if (!empty($item['excerpt'])) {
        echo '<p>' . e((string) $item['excerpt']) . '</p>';
    }
    echo '<span class="cms-svc-more">İncele</span>';
    echo '</span></a>';
}

function category_permalink($slug, $page = 1)
{
    $base = rtrim(PUBLIC_URL, '/') . '/kategori/' . rawurlencode((string) $slug);
    $page = (int) $page;
    return $page > 1 ? ($base . '?p=' . $page) : $base;
}

function rss_permalink()
{
    return rtrim(PUBLIC_URL, '/') . '/rss.xml';
}

function cms_normalize_path($path): string
{
    $path = (string) $path;
    if ($path !== '' && (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0)) {
        $parsed = parse_url($path, PHP_URL_PATH);
        $path = is_string($parsed) ? $parsed : '/';
    }
    $q = strpos($path, '?');
    if ($q !== false) {
        $path = substr($path, 0, $q);
    }
    $path = '/' . trim(str_replace('\\', '/', $path), '/');
    return $path === '/' ? '/' : rtrim($path, '/');
}

function cms_apply_redirects(PDO $pdo): void
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path = cms_normalize_path($uri);
    if (in_array($path, array('/sitemap.xml', '/robots.txt', '/rss.xml'), true)) {
        return;
    }
    if ($path === '' || $path === '/') {
        return;
    }
    try {
        $st = $pdo->prepare('SELECT target FROM redirects WHERE source = ? LIMIT 1');
        $st->execute([$path]);
        $to = trim((string) ($st->fetchColumn() ?: ''));
    } catch (PDOException $e) {
        return;
    }
    if ($to === '') {
        return;
    }
    if (!preg_match('#^https?://#i', $to)) {
        $to = rtrim(PUBLIC_URL, '/') . '/' . ltrim($to, '/');
    }
    $toPath = cms_normalize_path($to);
    if ($toPath === $path) {
        return;
    }
    header('Location: ' . $to, true, 301);
    exit;
}

function cms_with_toc(string $html): array
{
    $toc = array();
    $n = 0;
    $out = preg_replace_callback('#<h([23])(\b[^>]*)>(.*?)</h\1>#is', static function ($m) use (&$toc, &$n) {
        $n++;
        $id = 'baslik-' . $n;
        if (preg_match('/\sid=["\']([^"\']+)["\']/i', $m[2], $idm)) {
            $id = $idm[1];
        }
        $text = trim(strip_tags($m[3]));
        if ($text !== '') {
            $toc[] = array('id' => $id, 'text' => $text, 'level' => (int) $m[1]);
        }
        $attrs = $m[2];
        if (!preg_match('/\sid=/i', $attrs)) {
            $attrs .= ' id="' . e($id) . '"';
        }
        return '<h' . $m[1] . $attrs . '>' . $m[3] . '</h' . $m[1] . '>';
    }, $html);
    return array('html' => is_string($out) ? $out : $html, 'toc' => $toc);
}

function cms_export_sql(PDO $pdo): void
{
    $tables = array(
        'categories', 'posts', 'post_categories', 'comments', 'site_pages', 'page_images',
        'options', 'sliders', 'menus', 'inquiries', 'popups', 'gallery_albums', 'gallery_images',
        'float_buttons', 'testimonials', 'subscribers', 'partners', 'staff', 'counters', 'faqs',
        'services', 'redirects',
    );
    $lines = array('-- Kodcu CMS yedek ' . date('Y-m-d H:i:s'), 'SET NAMES utf8mb4;', '');
    foreach ($tables as $table) {
        try {
            $rows = $pdo->query('SELECT * FROM `' . str_replace('`', '', $table) . '`')->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            continue;
        }
        if (!$rows) {
            continue;
        }
        $lines[] = 'DELETE FROM `' . $table . '`;';
        foreach ($rows as $row) {
            $cols = array();
            $vals = array();
            foreach ($row as $col => $val) {
                $cols[] = '`' . str_replace('`', '', (string) $col) . '`';
                if ($val === null) {
                    $vals[] = 'NULL';
                } else {
                    $vals[] = $pdo->quote((string) $val);
                }
            }
            $lines[] = 'INSERT INTO `' . $table . '` (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ');';
        }
        $lines[] = '';
    }
    $body = implode("\n", $lines);
    header('Content-Type: application/sql; charset=UTF-8');
    header('Content-Disposition: attachment; filename="cms-yedek-' . date('Ymd-His') . '.sql"');
    header('Content-Length: ' . (string) strlen($body));
    echo $body;
}

function cms_crumbs(array $items): void
{
    if (!$items) {
        return;
    }
    echo '<nav class="cms-crumbs mb-5 text-sm text-slate-500" aria-label="Sayfa yolu"><ol class="flex flex-wrap items-center gap-1.5">';
    $last = count($items) - 1;
    foreach ($items as $i => $item) {
        $label = (string) ($item['label'] ?? '');
        $url = (string) ($item['url'] ?? '');
        echo '<li class="inline-flex items-center gap-1.5">';
        if ($i > 0) {
            echo '<span class="opacity-40" aria-hidden="true">/</span> ';
        }
        if ($i === $last || $url === '') {
            echo '<span class="text-slate-800">' . e($label) . '</span>';
        } else {
            echo '<a class="hover:text-sky-700" href="' . e($url) . '">' . e($label) . '</a>';
        }
        echo '</li>';
    }
    echo '</ol></nav>';
    $ld = array(
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => array(),
    );
    foreach ($items as $i => $item) {
        $el = array(
            '@type' => 'ListItem',
            'position' => $i + 1,
            'name' => (string) ($item['label'] ?? ''),
        );
        $url = (string) ($item['url'] ?? '');
        if ($url !== '') {
            $el['item'] = $url;
        }
        $ld['itemListElement'][] = $el;
    }
    echo '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}

function cms_social_links(PDO $pdo): array
{
    $map = array(
        'instagram' => 'social_instagram',
        'youtube' => 'social_youtube',
        'tiktok' => 'social_tiktok',
        'linkedin' => 'social_linkedin',
        'facebook' => 'social_facebook',
        'twitter' => 'social_twitter',
    );
    $out = array();
    foreach ($map as $type => $key) {
        $url = trim(option_get($pdo, $key, ''));
        if ($url !== '' && preg_match('#^https?://#i', $url)) {
            $out[] = array('type' => $type, 'url' => $url);
        }
    }
    return $out;
}

function cms_social_icon(string $type): string
{
    $svg = 'xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';
    if ($type === 'instagram') {
        return '<svg ' . $svg . '><rect x="3.4" y="3.4" width="17.2" height="17.2" rx="5"/><circle cx="12" cy="12" r="3.7"/><circle cx="17.15" cy="6.85" r=".9" fill="currentColor" stroke="none"/></svg>';
    }
    if ($type === 'youtube') {
        return '<svg ' . $svg . '><path d="m9.2 7.6 7.2 4.4-7.2 4.4V7.6Z"/></svg>';
    }
    if ($type === 'tiktok') {
        return '<svg ' . $svg . '><path d="M14.2 3.5v9.4a3.4 3.4 0 1 1-2.6-3.3V8.4A6.6 6.6 0 0 0 17.2 11V7.9A4.8 4.8 0 0 1 14.2 3.5Z"/></svg>';
    }
    if ($type === 'linkedin') {
        return '<svg ' . $svg . '><path d="M6.4 10.2V17M6.4 7.2h.01M10.6 17v-3.6c0-1.5 1.1-2.5 2.5-2.5s2.5 1.1 2.5 2.6V17"/></svg>';
    }
    if ($type === 'facebook') {
        return '<svg ' . $svg . '><path d="M14 8h3V4.5h-3A4.5 4.5 0 0 0 9.5 9v2H7v3.5h2.5V20h3.5v-5.5H16L17 11h-3.5V9A1 1 0 0 1 14 8Z"/></svg>';
    }
    if ($type === 'twitter') {
        return '<svg ' . $svg . '><path d="M4 4h4.7l4.1 5.6L17.8 4H20l-6.2 8.1L20.4 20h-4.7l-4.4-6L6.2 20H4l6.6-8.6L4 4Z"/></svg>';
    }
    return cms_float_icon('instagram');
}

function cms_footer_follow(PDO $pdo): void
{
    $socials = cms_social_links($pdo);
    if (!$socials) {
        return;
    }
    $heading = trim(option_get($pdo, 'social_heading', 'Bizi takip edin'));
    if ($heading === '') {
        $heading = 'Bizi takip edin';
    }
    echo '<div class="cms-follow">';
    echo '<p>' . e($heading) . '</p>';
    echo '<div class="cms-follow-row">';
    foreach ($socials as $soc) {
        echo '<a href="' . e((string) $soc['url']) . '" target="_blank" rel="noopener noreferrer" aria-label="' . e((string) $soc['type']) . '">';
        echo cms_social_icon((string) $soc['type']);
        echo '</a>';
    }
    echo '</div></div>';
}

function cms_spot_icon(int $i): string
{
    $svg = 'xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';
    if ($i === 1) {
        return '<svg ' . $svg . '><rect x="3.2" y="4.4" width="17.6" height="15.2" rx="2.2"/><path d="M3.2 9.2h17.6M9.6 9.2V19.6"/></svg>';
    }
    if ($i === 2) {
        return '<svg ' . $svg . '><path d="M13 3.5 6.2 13.2h5.1L11 20.5 17.8 10.8h-5.1L13 3.5Z"/></svg>';
    }
    return '<svg ' . $svg . '><path d="M12 21a8.2 8.2 0 0 0 8.2-8.2V8.8A3.3 3.3 0 0 0 16.9 5.5h-1.4A3.5 3.5 0 0 0 12 3.8 3.5 3.5 0 0 0 8.5 5.5H7.1A3.3 3.3 0 0 0 3.8 8.8v4A8.2 8.2 0 0 0 12 21Z"/><path d="M8.2 14.5h7.6"/></svg>';
}

function cms_spot_slides(PDO $pdo): array
{
    $slides = array();
    $one = trim(option_get($pdo, 'spot_image', ''));
    $two = trim(option_get($pdo, 'spot_image_2', ''));
    if ($one !== '') {
        $slides[] = array(
            'image' => $one,
            'kicker' => option_get($pdo, 'spot_badge_kicker', 'Stüdyodan'),
            'title' => option_get($pdo, 'spot_badge_title', 'Tasarım masası'),
        );
    }
    if ($two !== '') {
        $slides[] = array(
            'image' => $two,
            'kicker' => option_get($pdo, 'spot_badge_kicker_2', 'Ekipten'),
            'title' => option_get($pdo, 'spot_badge_title_2', 'Planlama toplantısı'),
        );
    }
    return $slides;
}

function cms_mail_inquiry(PDO $pdo, array $data): void
{
    if (option_get($pdo, 'contact_notify_mail', '1') !== '1') {
        return;
    }
    $to = trim(option_get($pdo, 'contact_email', ''));
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return;
    }
    $labels = array('mesaj' => 'Mesaj', 'teklif' => 'Teklif', 'randevu' => 'Randevu');
    $type = (string) ($data['subject'] ?? 'mesaj');
    $label = $labels[$type] ?? 'Mesaj';
    $site = option_get($pdo, 'site_title', 'Site');
    $subject = '[' . $site . '] Yeni ' . $label;
    $body = "Ad: " . (string) ($data['name'] ?? '') . "\n"
        . "E-posta: " . (string) ($data['email'] ?? '') . "\n"
        . "Telefon: " . (string) ($data['phone'] ?? '') . "\n"
        . "Konu: " . $label . "\n\n"
        . (string) ($data['message'] ?? '');
    $reply = filter_var((string) ($data['email'] ?? ''), FILTER_VALIDATE_EMAIL) ? (string) $data['email'] : $to;
    $headers = 'MIME-Version: 1.0' . "\r\n"
        . 'Content-Type: text/plain; charset=UTF-8' . "\r\n"
        . 'From: ' . $to . "\r\n"
        . 'Reply-To: ' . $reply;
    @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
}

function cms_settings_tabs(): array
{
    return array(
        'general' => 'Genel',
        'seo' => 'SEO',
        'site' => 'Site öğeleri',
        'newsletter' => 'Bülten',
        'social' => 'Sosyal',
        'tech' => 'Teknik',
    );
}

function cms_settings_tab(): string
{
    $tab = strtolower((string) ($_GET['tab'] ?? 'general'));
    $tab = preg_replace('/[^a-z]/', '', $tab) ?? 'general';
    $tabs = cms_settings_tabs();
    return isset($tabs[$tab]) ? $tab : 'general';
}

function cms_settings_section_tab(string $section): string
{
    $map = array(
        'general' => 'general',
        'favicon' => 'general',
        'seo' => 'seo',
        'ogimage' => 'seo',
        'popup' => 'site',
        'cta' => 'site',
        'bar' => 'site',
        'cookie' => 'site',
        'newsletter' => 'newsletter',
        'subscriber-delete' => 'newsletter',
        'social' => 'social',
        'analytics' => 'tech',
        'maintenance' => 'tech',
        'customcss' => 'tech',
    );
    return $map[$section] ?? 'general';
}

function reading_minutes($html)
{
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $html)));
    if ($text === '') {
        return 1;
    }
    $words = preg_split('/\s+/u', $text);
    $count = is_array($words) ? count($words) : 0;
    return max(1, (int) ceil($count / 200));
}

function staff_photo_src($path): string
{
    $path = trim((string) $path);
    if ($path !== '') {
        return media_src($path);
    }
    return rtrim(PUBLIC_URL, '/') . '/site/assets/person.svg';
}

function handle_multi_uploads(string $field): array
{
    if (!isset($_FILES[$field])) {
        return array();
    }
    $files = $_FILES[$field];
    if (!is_array($files['name'])) {
        $one = handle_image_upload($field);
        return $one ? array($one) : array();
    }
    $out = array();
    foreach ($files['name'] as $i => $name) {
        $_FILES['_cms_one'] = array(
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i],
        );
        $path = handle_image_upload('_cms_one');
        if ($path) {
            $out[] = $path;
        }
    }
    unset($_FILES['_cms_one']);
    return $out;
}

function media_src($path)
{
    $path = (string) $path;
    if ($path === '') {
        return '';
    }
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        return $path;
    }
    return rtrim(PANEL_URL, '/') . '/' . ltrim($path, '/');
}

function cms_logo_img(string $src, string $retina, int $height, string $alt): string
{
    $height = max(16, min(160, $height));
    $srcUrl = media_src($src);
    $html = '<img src="' . e($srcUrl) . '" alt="' . e($alt) . '" height="' . $height . '" decoding="async" style="height:' . $height . 'px;width:auto;max-width:280px;object-fit:contain;display:block"';
    if ($retina !== '') {
        $html .= ' srcset="' . e($srcUrl) . ' 1x, ' . e(media_src($retina)) . ' 2x"';
    }
    $html .= '>';
    return $html;
}

function site_brand_html(PDO $pdo, bool $compact = false): string
{
    $title = option_get($pdo, 'site_title', 'Site');
    $alt = trim(option_get($pdo, 'site_logo_alt', $title));
    if ($alt === '') {
        $alt = $title;
    }
    $heightKey = $compact ? 'site_logo_mobile_height' : 'site_logo_height';
    $height = (int) option_get($pdo, $heightKey, $compact ? '32' : '40');
    if ($height < 16) {
        $height = $compact ? 32 : 40;
    }
    $showTitle = option_get($pdo, 'site_logo_show_title', '1') === '1';
    $logo = option_get($pdo, $compact ? 'site_logo_mobile' : 'site_logo', '');
    $retina = option_get($pdo, $compact ? 'site_logo_mobile_retina' : 'site_logo_retina', '');
    if ($logo === '') {
        $logo = option_get($pdo, 'site_logo', '');
        $retina = option_get($pdo, 'site_logo_retina', '');
    }
    $href = trim(option_get($pdo, 'site_logo_link', ''));
    if ($href === '') {
        $href = public_url();
    }
    $accent = hex_color(option_get($pdo, 'header_accent', '#38bdf8'), '#38bdf8');
    $inner = '';
    if ($logo !== '') {
        $inner .= cms_logo_img($logo, $retina, $height, $alt);
        if ($showTitle) {
            $inner .= '<span>' . e($title) . '</span>';
        }
    } else {
        $box = $compact ? 'h-8 w-8 text-xs' : 'h-8 w-8 text-sm';
        $inner .= '<span class="inline-flex ' . $box . ' items-center justify-center rounded-md font-bold" style="background:' . e($accent) . ';color:' . e(cms_on_color($accent)) . '">&lt;/&gt;</span>';
        $inner .= e($title);
    }
    return '<a href="' . e($href) . '" class="flex items-center gap-2 font-semibold tracking-tight">' . $inner . '</a>';
}

function panel_brand_html(PDO $pdo): string
{
    $logo = option_get($pdo, 'panel_logo', '');
    $retina = option_get($pdo, 'panel_logo_retina', '');
    $height = (int) option_get($pdo, 'panel_logo_height', '32');
    if ($height < 20) {
        $height = 32;
    }
    if ($height > 56) {
        $height = 56;
    }
    $showName = option_get($pdo, 'panel_logo_show_name', '1') === '1';
    $html = '';
    if ($logo !== '') {
        $html .= cms_logo_img($logo, $retina, $height, 'Panel');
    } else {
        $html .= '<span class="panel-brand-mark inline-flex h-8 w-8 items-center justify-center rounded-md text-white shrink-0">'
            . '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" /></svg>'
            . '</span>';
    }
    if ($showName) {
        $html .= '<div class="min-w-0"><p class="panel-brand-title truncate text-sm font-semibold">Remedolt Web Panel</p><p class="panel-brand-sub truncate text-[11px]">Yönetim paneli</p></div>';
    }
    return $html;
}

function cms_save_logo_field(PDO $pdo, string $key): void
{
    if (isset($_POST['remove_' . $key])) {
        option_set($pdo, $key, '');
    }
    $uploaded = handle_image_upload($key);
    if ($uploaded) {
        option_set($pdo, $key, $uploaded);
    }
}

function favicon_tags()
{
    $custom = '';
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
        $custom = option_get($GLOBALS['pdo'], 'favicon_path', '');
    }
    if ($custom !== '') {
        return '<link rel="icon" href="' . e(media_src($custom)) . '">';
    }
    $svg = public_url('favicon.svg');
    $png = public_url('favicon.png');
    return '<link rel="icon" href="' . e($svg) . '" type="image/svg+xml">'
        . '<link rel="icon" href="' . e($png) . '" type="image/png">';
}

function cms_seed(PDO $pdo): void
{
    $adminUser = 'Deniz';
    $adminPass = 'LUD5Wu6u]uNUs@]q';
    $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($userCount === 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, display_name, role)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $adminUser,
            'deniz@example.com',
            password_hash($adminPass, PASSWORD_DEFAULT),
            'Deniz',
            'admin',
        ]);
        $stmt->execute([
            'editor',
            'editor@example.com',
            password_hash('Editor123!', PASSWORD_DEFAULT),
            'Editör',
            'editor',
        ]);
    } else {
        $old = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $old->execute(['admin']);
        $oldAdmin = $old->fetch();
        if ($oldAdmin) {
            $taken = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1');
            $taken->execute([$adminUser, (int) $oldAdmin['id']]);
            if (!$taken->fetch()) {
                $pdo->prepare('UPDATE users SET username = ?, email = ?, password_hash = ?, display_name = ?, role = ? WHERE id = ?')
                    ->execute([
                        $adminUser,
                        'deniz@example.com',
                        password_hash($adminPass, PASSWORD_DEFAULT),
                        'Deniz',
                        'admin',
                        (int) $oldAdmin['id'],
                    ]);
            }
        }
    }

    $catCount = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    if ($catCount === 0) {
        $stmt = $pdo->prepare('INSERT INTO categories (name, slug) VALUES (?, ?)');
        foreach ([
            ['Haberler', 'haberler'],
            ['Rehber', 'rehber'],
            ['Duyurular', 'duyurular'],
            ['Blog', 'blog'],
        ] as $cat) {
            $stmt->execute($cat);
        }
    }

    $postCount = (int) $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();
    if ($postCount === 0) {
        $authorId = (int) $pdo->query('SELECT id FROM users ORDER BY id ASC LIMIT 1')->fetchColumn();
        $seedCatIds = array_map('intval', $pdo->query('SELECT id FROM categories ORDER BY id ASC')->fetchAll(PDO::FETCH_COLUMN));
        $ins = $pdo->prepare(
            'INSERT INTO posts (title, slug, content, excerpt, status, views, author_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $samples = [
            [
                'Paylaşımlı sunucuda CMS kurulumu',
                'paylasimli-sunucuda-cms-kurulumu',
                '<p>Bu yazı, FTP ile yüklenen vanilla PHP yönetim panelinin ilk örnek içeriğidir.</p>',
                'FTP, MySQL ve dosya izinleriyle ilk kurulumu özetler.',
                'publish',
                1284,
                '2026-08-12 10:20:00',
            ],
            [
                'Güvenli yönlendirme ve beyaz liste',
                'guvenli-yonlendirme-ve-beyaz-liste',
                '<p>Sayfa parametresi yalnızca izin verilen anahtarlarla eşleşir; dosya yolu kullanıcıdan alınmaz.</p>',
                'LFI riskini ortadan kaldıran include mimarisi.',
                'publish',
                876,
                '2026-08-20 14:05:00',
            ],
            [
                'Yazı düzenleyici ipuçları',
                'yazi-duzenleyici-ipuclari',
                '<p>Başlık, içerik ve özet alanlarını doldurduktan sonra taslak veya yayımlama seçebilirsiniz.</p>',
                'Klasik iki sütunlu düzenleyici kullanımı.',
                'draft',
                42,
                '2026-09-01 09:12:00',
            ],
            [
                'Medya yükleme kuralları',
                'medya-yukleme-kurallari',
                '<p>Öne çıkan görseller JPEG, PNG, WebP veya GIF olmalıdır. Azami boyut 5 MB’dır.</p>',
                'Yükleme kutusu ve dosya doğrulama.',
                'publish',
                533,
                '2026-09-03 18:40:00',
            ],
        ];
        $link = $pdo->prepare('INSERT INTO post_categories (post_id, category_id) VALUES (?, ?)');
        $catIdCount = count($seedCatIds);
        foreach ($samples as $i => $row) {
            $ins->execute([
                $row[0],
                $row[1],
                $row[2],
                $row[3],
                $row[4],
                $row[5],
                $authorId,
                $row[6],
            ]);
            $postId = (int) $pdo->lastInsertId();
            if ($catIdCount > 0) {
                $link->execute([$postId, $seedCatIds[$i % $catIdCount]]);
            }
        }
    }

    $commentCount = (int) $pdo->query('SELECT COUNT(*) FROM comments')->fetchColumn();
    if ($commentCount === 0) {
        $firstPost = (int) $pdo->query('SELECT id FROM posts ORDER BY id ASC LIMIT 1')->fetchColumn();
        if ($firstPost > 0) {
            $stmt = $pdo->prepare(
                'INSERT INTO comments (post_id, author_name, content, status) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$firstPost, 'Ayşe Kaya', 'Kurulum adımları net olmuş, teşekkürler.', 'approved']);
            $stmt->execute([$firstPost, 'Mert Demir', 'PDO örnekleri işime yaradı.', 'approved']);
            $stmt->execute([$firstPost, 'Ziyaretçi', 'Onay bekleyen bir yorum.', 'pending']);
        }
    }

    $pageCount = (int) $pdo->query('SELECT COUNT(*) FROM site_pages')->fetchColumn();
    if ($pageCount === 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO site_pages (title, slug, content, excerpt, status, template, show_on_home, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            'Hakkında',
            'hakkinda',
            '<p>Kodcu; yazılım, proje ve duyuruları tek yerde toplayan bir sitedir.</p><p>Yönetim panelinden sayfa şablonu, görsel ve özet ekleyebilirsiniz.</p>',
            'Kim olduğumuzu ve neler yaptığımızı kısaca anlatır.',
            'publish',
            'default',
            1,
            1,
        ]);
        $stmt->execute([
            'Kurumsal',
            'kurumsal',
            '<p>Kurumsal bilgiler ve çalışma alanlarımız.</p>',
            'Kurumsal tanıtım.',
            'publish',
            'default',
            1,
            2,
        ]);
        $stmt->execute([
            'Hizmetler',
            'hizmetler',
            '<p>Web sitesi, içerik yönetimi ve proje sayfaları.</p>',
            'Sunduğumuz başlıca hizmetler.',
            'publish',
            'landing',
            1,
            3,
        ]);
        $stmt->execute(['Gizlilik', 'gizlilik', '<p>Taslak gizlilik metni.</p>', 'Gizlilik politikası taslağı.', 'draft', 'default', 0, 4]);
    }

    $optCount = (int) $pdo->query('SELECT COUNT(*) FROM options')->fetchColumn();
    if ($optCount === 0) {
        $stmt = $pdo->prepare('INSERT INTO options (option_key, option_value) VALUES (?, ?)');
        $stmt->execute(['site_title', 'Kodcu']);
        $stmt->execute(['site_tagline', 'Yazılar, sayfalar ve projeler']);
        $stmt->execute(['posts_per_page', '10']);
        $stmt->execute(['site_url', PUBLIC_URL]);
        $stmt->execute(['seo_title', 'Kodcu']);
        $stmt->execute(['seo_description', 'Kod, yazılım ve proje yazıları.']);
        $stmt->execute(['seo_keywords', 'yazılım, php, cms, kodcu']);
        $stmt->execute(['seo_robots', 'index,follow']);
        $stmt->execute(['favicon_path', '']);
        $stmt->execute(['popup_enabled', '0']);
    }

    try {
        $slideCount = (int) $pdo->query('SELECT COUNT(*) FROM sliders')->fetchColumn();
        if ($slideCount === 0) {
            $stock = cms_slider_stock_images();
            $ins = $pdo->prepare(
                'INSERT INTO sliders (title, subtitle, image, button_text, button_url, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute(['Kodcu’ya hoş geldiniz', 'Yazılar, sayfalar ve duyurular tek yerde.', $stock[0], 'Yazılara bak', public_url(), 1, 'publish']);
            $ins->execute(['Yeni içerikler', 'Panelden yayımladığınız her yazı burada görünür.', $stock[1], 'Panele git', rtrim(PANEL_URL, '/') . '/', 2, 'publish']);
        }
        $menuCount = (int) $pdo->query('SELECT COUNT(*) FROM menus')->fetchColumn();
        if ($menuCount === 0) {
            $ins = $pdo->prepare('INSERT INTO menus (title, url, parent_id, sort_order, item_style, status) VALUES (?, ?, ?, ?, ?, ?)');
            $ins->execute(['Ana Sayfa', public_url(), 0, 1, 'link', 'publish']);
            $ins->execute(['Yazılar', public_url(), 0, 2, 'dropdown', 'publish']);
            $parentId = (int) $pdo->lastInsertId();
            $ins->execute(['Tüm Yazılar', public_url(), $parentId, 1, 'link', 'publish']);
            $about = $pdo->query("SELECT slug FROM site_pages WHERE slug = 'hakkinda' LIMIT 1")->fetch();
            if ($about) {
                $ins->execute(['Hakkında', page_permalink($about['slug']), 0, 3, 'link', 'publish']);
            }
            $ins->execute(['İletişim', contact_permalink(), 0, 4, 'button', 'publish']);
            $ins->execute(['Galeri', gallery_permalink(), 0, 5, 'link', 'publish']);
        }
        $footerCount = (int) $pdo->query("SELECT COUNT(*) FROM menus WHERE location = 'footer'")->fetchColumn();
        if ($footerCount === 0) {
            $ins = $pdo->prepare('INSERT INTO menus (title, url, parent_id, sort_order, item_style, status, location) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $ins->execute(['Ana Sayfa', public_url(), 0, 1, 'link', 'publish', 'footer']);
            $ins->execute(['Hakkında', page_permalink('hakkinda'), 0, 2, 'link', 'publish', 'footer']);
            $ins->execute(['İletişim', contact_permalink(), 0, 3, 'link', 'publish', 'footer']);
        }
        $popupCount = (int) $pdo->query('SELECT COUNT(*) FROM popups')->fetchColumn();
        if ($popupCount === 0) {
            $ins = $pdo->prepare(
                'INSERT INTO popups (title, content, button_text, button_url, delay_seconds, status) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([
                'Bülten',
                'Yeni yazılardan haberdar olmak için siteyi takip edin.',
                'Tamam',
                public_url(),
                3,
                'draft',
            ]);
        }
        $albumCount = (int) $pdo->query('SELECT COUNT(*) FROM gallery_albums')->fetchColumn();
        if ($albumCount === 0) {
            $ins = $pdo->prepare(
                'INSERT INTO gallery_albums (title, slug, tile_size, sort_order, status) VALUES (?, ?, ?, ?, ?)'
            );
            $ins->execute(['Projeler', 'projeler', 'large', 1, 'publish']);
            $ins->execute(['Etkinlikler', 'etkinlikler', 'wide', 2, 'publish']);
            $ins->execute(['Atölye', 'atolye', 'square', 3, 'publish']);
            $ins->execute(['Ofis', 'ofis', 'square', 4, 'publish']);
            $ins->execute(['Ekip', 'ekip', 'tall', 5, 'publish']);
            $ins->execute(['Sahne', 'sahne', 'wide', 6, 'publish']);
            $ins->execute(['Çalışma alanı', 'calisma-alani', 'square', 7, 'publish']);
            $ins->execute(['Kampüs', 'kampus', 'square', 8, 'publish']);
            $ins->execute(['Manzara', 'manzara', 'wide', 9, 'publish']);
        }
        $hasManzara = (int) $pdo->query("SELECT COUNT(*) FROM gallery_albums WHERE slug = 'manzara'")->fetchColumn();
        if ($hasManzara === 0) {
            $maxSort = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM gallery_albums')->fetchColumn();
            $insManzara = $pdo->prepare(
                'INSERT INTO gallery_albums (title, slug, cover_image, tile_size, sort_order, status) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $insManzara->execute(['Manzara', 'manzara', 'assets/login-bg.jpg', 'wide', $maxSort + 1, 'publish']);
        } else {
            $pdo->exec("UPDATE gallery_albums SET cover_image = 'assets/login-bg.jpg', tile_size = 'wide' WHERE slug = 'manzara' AND (cover_image IS NULL OR cover_image = '')");
        }
        $btnCount = (int) $pdo->query('SELECT COUNT(*) FROM float_buttons')->fetchColumn();
        if ($btnCount === 0) {
            $ins = $pdo->prepare(
                'INSERT INTO float_buttons (label, url, icon_type, color, sort_order, status) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $ins->execute(['Telefon', '', 'phone', '#2563eb', 1, 'draft']);
            $ins->execute(['WhatsApp', '', 'whatsapp', '#25D366', 2, 'draft']);
        }
        try {
            $partnerCount = (int) $pdo->query('SELECT COUNT(*) FROM partners')->fetchColumn();
            if ($partnerCount === 0) {
                $ins = $pdo->prepare('INSERT INTO partners (name, logo, url, sort_order, status) VALUES (?, ?, ?, ?, ?)');
                $ins->execute(['Acme Yazılım', 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=800&h=400&q=80', 'https://example.com', 1, 'publish']);
                $ins->execute(['Nova Ajans', 'https://images.unsplash.com/photo-1542744173-8e7e53415bb0?auto=format&fit=crop&w=800&h=400&q=80', 'https://example.com', 2, 'publish']);
                $ins->execute(['Delta Grup', 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=800&h=400&q=80', 'https://example.com', 3, 'publish']);
                $ins->execute(['Orion Teknoloji', 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=800&h=400&q=80', 'https://example.com', 4, 'publish']);
            }
            $staffCount = (int) $pdo->query('SELECT COUNT(*) FROM staff')->fetchColumn();
            if ($staffCount === 0) {
                $ins = $pdo->prepare('INSERT INTO staff (name, title, photo, interests, bio, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $ins->execute(['Ayşe Yılmaz', 'Genel Müdür', 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=600&h=600&q=80', 'Strateji, ürün', '<p>Kurumsal gelişim ve iş ortaklıkları.</p>', 1, 'publish']);
                $ins->execute(['Mehmet Kaya', 'Yazılım Lideri', 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=600&h=600&q=80', 'PHP, arayüz', '<p>Web uygulamaları ve CMS.</p>', 2, 'publish']);
                $ins->execute(['Elif Demir', 'İletişim', 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=600&h=600&q=80', 'Marka, içerik', '<p>Kurumsal iletişim ve içerik.</p>', 3, 'publish']);
            }
            $counterCount = (int) $pdo->query('SELECT COUNT(*) FROM counters')->fetchColumn();
            if ($counterCount === 0) {
                $ins = $pdo->prepare('INSERT INTO counters (label, value_num, suffix, sort_order, status) VALUES (?, ?, ?, ?, ?)');
                $ins->execute(['Tamamlanan proje', 120, '+', 1, 'publish']);
                $ins->execute(['Mutlu müşteri', 80, '', 2, 'publish']);
                $ins->execute(['Yıllık deneyim', 10, '', 3, 'publish']);
                $ins->execute(['Çalışan', 25, '', 4, 'publish']);
            }
        } catch (PDOException $e) {
            // yeni tablolar henüz yoksa geç
        }
        try {
            $faqCount = (int) $pdo->query('SELECT COUNT(*) FROM faqs')->fetchColumn();
            if ($faqCount === 0) {
                $ins = $pdo->prepare('INSERT INTO faqs (question, answer, sort_order, status) VALUES (?, ?, ?, ?)');
                $ins->execute(['Nasıl iletişime geçebilirim?', '<p>İletişim sayfasındaki formu doldurun; mesajlar yönetim paneline düşer. Telefon ve harita da oradadır.</p>', 1, 'publish']);
                $ins->execute(['İçerikleri kim yönetir?', '<p>Yönetim panelinden sayfa, yazı, galeri, personel ve referanslar düzenlenir.</p>', 2, 'publish']);
                $ins->execute(['Galeri nerede?', '<p>Ana sayfadaki galeri bölümünden veya /galeri adresinden albümlere bakabilirsiniz.</p>', 3, 'publish']);
            }
        } catch (PDOException $e) {
            // faq tablosu yoksa geç
        }
        try {
            $revCount = (int) $pdo->query('SELECT COUNT(*) FROM testimonials')->fetchColumn();
            if ($revCount === 0) {
                $ins = $pdo->prepare('INSERT INTO testimonials (name, title, quote, rating, sort_order, status) VALUES (?, ?, ?, ?, ?, ?)');
                $ins->execute(['Deniz Kaya', 'Kurucu, Nova', 'Süreç şeffaftı, teslim net oldu. Siteyi birlikte büyütüyoruz.', 5, 1, 'publish']);
                $ins->execute(['Selin Aksoy', 'Pazarlama', 'Panel sade, içerikleri kendimiz güncelliyoruz. Çok zaman kazandırdı.', 5, 2, 'publish']);
                $ins->execute(['Emre Yıldız', 'Operasyon', 'İletişim formu, harita ve galeri tek yerde. Ziyaretçi tarafı hızlı.', 4, 3, 'publish']);
            }
        } catch (PDOException $e) {
            // testimonials yoksa geç
        }
        try {
            $svcCount = (int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn();
            if ($svcCount === 0) {
                $ins = $pdo->prepare('INSERT INTO services (title, slug, excerpt, content, image, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $ins->execute(['Web sitesi', 'web-sitesi', 'Kurumsal site, blog ve yönetim paneli.', '<p>Markanıza uygun, panelden güncellenen web sitesi.</p>', 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=1400&q=80', 1, 'publish']);
                $ins->execute(['Yazılım geliştirme', 'yazilim-gelistirme', 'İhtiyaca özel PHP ve arayüz işleri.', '<p>Mevcut sitenizi büyütün veya yeni bir uygulama kurun.</p>', 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=1400&q=80', 2, 'publish']);
                $ins->execute(['Bakım ve destek', 'bakim-ve-destek', 'Güncelleme, yedek ve teknik destek.', '<p>Siteniz yayındayken içerik ve güvenlik takibi.</p>', 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=1400&q=80', 3, 'publish']);
            }
        } catch (PDOException $e) {
            // services yoksa geç
        }
        cms_fill_gallery_placeholders($pdo);
        cms_fill_staff_placeholders($pdo);
        cms_fill_service_placeholders($pdo);
        cms_fill_partner_placeholders($pdo);
        cms_fill_slider_placeholders($pdo);
        cms_fill_album_descriptions($pdo);
    } catch (PDOException $e) {
        // tablolar henüz yoksa cms_ensure_schema sonra tekrar dener
    }
}

function cms_fill_gallery_placeholders(PDO $pdo): void
{
    try {
        $stock = array(
            'projeler' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1400&q=80',
            'etkinlikler' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=1400&q=80',
            'atolye' => 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=1400&q=80',
            'ofis' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1400&q=80',
            'ekip' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=1400&q=80',
            'sahne' => 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?auto=format&fit=crop&w=1400&q=80',
            'calisma-alani' => 'https://images.unsplash.com/photo-1486312338219-ce68d2c6f44d?auto=format&fit=crop&w=1400&q=80',
            'kampus' => 'https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=1400&q=80',
            'manzara' => 'assets/login-bg.jpg',
        );
        $albums = $pdo->query('SELECT id, slug, cover_image FROM gallery_albums')->fetchAll();
        $upd = $pdo->prepare('UPDATE gallery_albums SET cover_image = ? WHERE id = ?');
        $cnt = $pdo->prepare('SELECT COUNT(*) FROM gallery_images WHERE album_id = ?');
        $ins = $pdo->prepare('INSERT INTO gallery_images (album_id, image, sort_order) VALUES (?, ?, ?)');
        foreach ($albums as $album) {
            $id = (int) $album['id'];
            $slug = (string) $album['slug'];
            $cover = trim((string) ($album['cover_image'] ?? ''));
            $stockUrl = $stock[$slug] ?? ('https://picsum.photos/seed/gal-' . rawurlencode($slug !== '' ? $slug : (string) $id) . '/1400/900');
            if ($cover === '') {
                $upd->execute([$stockUrl, $id]);
                $cover = $stockUrl;
            }
            $cnt->execute([$id]);
            if ((int) $cnt->fetchColumn() === 0) {
                $ins->execute([$id, $cover, 1]);
                for ($n = 1; $n <= 3; $n++) {
                    $ins->execute([$id, 'https://picsum.photos/seed/' . rawurlencode($slug . '-p' . $n) . '/1200/800', $n + 1]);
                }
            }
        }
    } catch (PDOException $e) {
        // galeri tabloları yoksa sessiz geç
    }
}

function cms_fill_staff_placeholders(PDO $pdo): void
{
    try {
        $named = array(
            'Ayşe Yılmaz' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=600&h=600&q=80',
            'Mehmet Kaya' => 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=600&h=600&q=80',
            'Elif Demir' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=600&h=600&q=80',
        );
        $pool = array_values($named);
        $upd = $pdo->prepare('UPDATE staff SET photo = ? WHERE id = ?');
        $rows = $pdo->query('SELECT id, name, photo FROM staff')->fetchAll();
        foreach ($rows as $i => $row) {
            if (trim((string) ($row['photo'] ?? '')) !== '') {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            $url = $named[$name] ?? $pool[$i % count($pool)];
            $upd->execute([$url, (int) $row['id']]);
        }
    } catch (PDOException $e) {
        // personel tablosu yoksa geç
    }
}

function cms_fill_service_placeholders(PDO $pdo): void
{
    try {
        $named = array(
            'web-sitesi' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=1400&q=80',
            'yazilim-gelistirme' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=1400&q=80',
            'bakim-ve-destek' => 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=1400&q=80',
        );
        $pool = array(
            'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=1400&q=80',
            'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=1400&q=80',
            'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=1400&q=80',
        );
        $upd = $pdo->prepare('UPDATE services SET image = ? WHERE id = ?');
        $rows = $pdo->query('SELECT id, slug, image FROM services')->fetchAll();
        foreach ($rows as $i => $row) {
            if (trim((string) ($row['image'] ?? '')) !== '') {
                continue;
            }
            $slug = (string) ($row['slug'] ?? '');
            $url = $named[$slug] ?? $pool[$i % count($pool)];
            $upd->execute([$url, (int) $row['id']]);
        }
    } catch (PDOException $e) {
        // hizmet tablosu yoksa geç
    }
}

function cms_fill_partner_placeholders(PDO $pdo): void
{
    try {
        $named = array(
            'Acme Yazılım' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=800&h=400&q=80',
            'Nova Ajans' => 'https://images.unsplash.com/photo-1542744173-8e7e53415bb0?auto=format&fit=crop&w=800&h=400&q=80',
            'Delta Grup' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=800&h=400&q=80',
            'Orion Teknoloji' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=800&h=400&q=80',
        );
        $pool = array_values($named);
        $upd = $pdo->prepare('UPDATE partners SET logo = ? WHERE id = ?');
        $rows = $pdo->query('SELECT id, name, logo FROM partners')->fetchAll();
        foreach ($rows as $i => $row) {
            if (trim((string) ($row['logo'] ?? '')) !== '') {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            $url = $named[$name] ?? $pool[$i % count($pool)];
            $upd->execute([$url, (int) $row['id']]);
        }
    } catch (PDOException $e) {
        // referans tablosu yoksa geç
    }
}

function cms_slider_stock_images(): array
{
    return array(
        'https://images.unsplash.com/photo-1480714378408-67cf0d13bc1b?auto=format&fit=crop&w=1920&q=80',
        'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=1920&q=80',
        'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1920&q=80',
        'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=1920&q=80',
    );
}

function cms_slider_cover(array $slide, int $i = 0): string
{
    $img = trim((string) ($slide['image'] ?? ''));
    if ($img !== '') {
        return media_src($img);
    }
    $stock = cms_slider_stock_images();
    $title = trim((string) ($slide['title'] ?? ''));
    $named = array(
        'Kodcu’ya hoş geldiniz' => $stock[0],
        "Kodcu'ya hoş geldiniz" => $stock[0],
        'Yeni içerikler' => $stock[1],
    );
    if (isset($named[$title])) {
        return $named[$title];
    }
    return $stock[$i % count($stock)];
}

function cms_fill_slider_placeholders(PDO $pdo): void
{
    try {
        $upd = $pdo->prepare('UPDATE sliders SET image = ? WHERE id = ?');
        $rows = $pdo->query('SELECT id, title, image FROM sliders')->fetchAll();
        foreach ($rows as $i => $row) {
            if (trim((string) ($row['image'] ?? '')) !== '') {
                continue;
            }
            $upd->execute([cms_slider_cover($row, (int) $i), (int) $row['id']]);
        }
    } catch (PDOException $e) {
        // slider tablosu yoksa geç
    }
}

function cms_fill_album_descriptions(PDO $pdo): void
{
    $map = array(
        'projeler' => 'Teslim ettiğimiz işlerden seçilmiş kareler.',
        'etkinlikler' => 'Toplantı, lansman ve sahne anları.',
        'atolye' => 'Üretim ve deneme süreçlerinden kareler.',
        'ofis' => 'Çalışma ortamımız ve günlük ritim.',
        'ekip' => 'Birlikte üreten insanlar.',
        'sahne' => 'Canlı etkinlik ve sahne ışıkları.',
        'calisma-alani' => 'Masa, stüdyo ve ortak alanlar.',
        'kampus' => 'Kampüs ve çevresinden bakış.',
        'manzara' => 'Dış çekimler ve geniş planlar.',
    );
    try {
        $upd = $pdo->prepare('UPDATE gallery_albums SET description = ? WHERE id = ?');
        $rows = $pdo->query('SELECT id, slug, description FROM gallery_albums')->fetchAll();
        foreach ($rows as $row) {
            if (trim((string) ($row['description'] ?? '')) !== '') {
                continue;
            }
            $slug = (string) ($row['slug'] ?? '');
            $text = $map[$slug] ?? '';
            if ($text === '') {
                continue;
            }
            $upd->execute([$text, (int) $row['id']]);
        }
    } catch (PDOException $e) {
        // albüm açıklaması yoksa geç
    }
}

function cms_row_title(PDO $pdo, string $table, int $id, string $col = 'title'): string
{
    $table = preg_replace('/[^a-z0-9_]/', '', strtolower($table)) ?? '';
    $col = preg_replace('/[^a-z0-9_]/', '', strtolower($col)) ?? '';
    if ($table === '' || $col === '' || $id < 1) {
        return '';
    }
    try {
        $st = $pdo->prepare('SELECT `' . $col . '` FROM `' . $table . '` WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        return trim((string) ($st->fetchColumn() ?: ''));
    } catch (PDOException $e) {
        return '';
    }
}

function cms_audit(PDO $pdo, string $action, string $entity, int $entityId = 0, string $title = ''): void
{
    $action = preg_replace('/[^a-z_]/', '', strtolower($action)) ?? '';
    $entity = preg_replace('/[^a-z_]/', '', strtolower($entity)) ?? '';
    if ($action === '' || $entity === '') {
        return;
    }
    $actor = (isset($GLOBALS['cms_actor']) && is_array($GLOBALS['cms_actor'])) ? $GLOBALS['cms_actor'] : array();
    $uid = (int) ($actor['id'] ?? 0);
    $name = trim((string) ($actor['display_name'] ?? $actor['username'] ?? ''));
    if ($name === '' && !empty($_SESSION['user_name'])) {
        $name = (string) $_SESSION['user_name'];
        $uid = (int) ($_SESSION['user_id'] ?? 0);
    }
    $title = function_exists('mb_substr') ? mb_substr($title, 0, 255) : substr($title, 0, 255);
    $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    try {
        $pdo->prepare(
            'INSERT INTO activity_log (user_id, user_name, action, entity, entity_id, title, ip) VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([$uid, $name, $action, $entity, $entityId, $title, $ip !== '' ? $ip : null]);
        if (random_int(1, 25) === 1) {
            $cut = $pdo->query('SELECT id FROM activity_log ORDER BY id DESC LIMIT 1 OFFSET 499');
            $cutId = $cut ? $cut->fetchColumn() : false;
            if ($cutId) {
                $pdo->prepare('DELETE FROM activity_log WHERE id < ?')->execute([(int) $cutId]);
            }
        }
    } catch (PDOException $e) {
        // günlük tablosu yoksa sessiz geç
    }
}

function cms_audit_action_label(string $action): string
{
    $map = array(
        'create' => 'ekledi',
        'update' => 'güncelledi',
        'publish' => 'yayımladı',
        'delete' => 'sildi',
        'login' => 'giriş yaptı',
        'approve' => 'onayladı',
    );
    return $map[$action] ?? $action;
}

function cms_audit_entity_label(string $entity): string
{
    $map = array(
        'post' => 'Yazı',
        'page' => 'Sayfa',
        'album' => 'Albüm',
        'service' => 'Hizmet',
        'slider' => 'Slayt',
        'user' => 'Kullanıcı',
        'comment' => 'Yorum',
        'staff' => 'Personel',
        'partner' => 'Referans',
        'brand' => 'Marka',
        'faq' => 'SSS',
        'review' => 'Müşteri yorumu',
        'photo' => 'Fotoğraf',
    );
    return $map[$entity] ?? $entity;
}

function handle_image_upload(string $field = 'featured_image'): ?string
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return null;
    }
    $file = $_FILES[$field];
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error !== UPLOAD_ERR_OK) {
        flash_set('error', 'Görsel yüklenemedi. Dosya boyutunu ve izinleri kontrol edin.');
        return null;
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    $size = (int) ($file['size'] ?? 0);
    if ($tmp === '' || !is_uploaded_file($tmp) || $size <= 0 || $size > MAX_UPLOAD_BYTES) {
        flash_set('error', 'Görsel geçersiz veya 5 MB sınırını aşıyor.');
        return null;
    }
    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true) && !is_dir(UPLOAD_DIR)) {
        flash_set('error', 'uploads klasörü oluşturulamadı.');
        return null;
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        'image/svg+xml' => 'svg',
        'image/x-icon' => 'ico',
        'image/vnd.microsoft.icon' => 'ico',
    ];
    if (!is_string($mime) || !isset($allowed[$mime])) {
        $ext = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($ext === 'svg' && (is_string($mime) && (strpos($mime, 'xml') !== false || $mime === 'text/plain' || $mime === 'application/octet-stream'))) {
            $mime = 'image/svg+xml';
        } else {
            flash_set('error', 'Yalnızca JPEG, PNG, WebP, GIF, SVG ve ICO dosyaları kabul edilir.');
            return null;
        }
    }
    if ($allowed[$mime] === 'svg') {
        $raw = (string) file_get_contents($tmp);
        if ($raw === '' || preg_match('/<script|on[a-z]+\s*=/i', $raw)) {
            flash_set('error', 'SVG dosyası güvenlik kontrolünden geçmedi.');
            return null;
        }
    }
    $name = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $dest = UPLOAD_DIR . $name;
    if (!move_uploaded_file($tmp, $dest)) {
        flash_set('error', 'Görsel kaydedilemedi.');
        return null;
    }
    return UPLOAD_URL . $name;
}
