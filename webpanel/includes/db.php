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
    define('SITE_NAME', 'CMS Yönetim');
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

function redirect(string $url): void
{
    if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
        $url = rtrim(PANEL_URL, '/') . '/' . ltrim($url, '/');
    }
    header('Location: ' . $url);
    exit;
}

function flash_set(string $type, string $message, string $bag = 'flash'): void
{
    $_SESSION[$bag] = ['type' => $type, 'message' => $message];
}

function flash_get(string $bag = 'flash'): ?array
{
    if (empty($_SESSION[$bag]) || !is_array($_SESSION[$bag])) {
        return null;
    }
    $flash = $_SESSION[$bag];
    unset($_SESSION[$bag]);
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
    return public_url('yazi/' . rawurlencode((string) $slug));
}

function page_permalink($slug)
{
    return public_url('sayfa/' . rawurlencode((string) $slug));
}

function category_permalink($slug)
{
    return public_url('kategori/' . rawurlencode((string) $slug));
}

function search_url($query = '')
{
    $query = trim((string) $query);
    $base = public_url('ara');
    return $query === '' ? $base : $base . '?q=' . rawurlencode($query);
}

function excerpt_plain($text, $length = 160): string
{
    $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($text) > $length) {
            return rtrim(mb_substr($text, 0, $length), " \t\n\r\0\x0B.,;:") . '…';
        }
        return $text;
    }
    if (strlen($text) > $length) {
        return rtrim(substr($text, 0, $length), " \t\n\r\0\x0B.,;:") . '…';
    }
    return $text;
}

function pagination_html(int $pageNum, int $totalPages, string $base): string
{
    if ($totalPages <= 1) {
        return '';
    }
    $sep = strpos($base, '?') !== false ? '&amp;' : '?';
    $html = '<nav class="mt-8 flex items-center justify-between text-sm text-slate-600">';
    $html .= '<span>Sayfa ' . $pageNum . ' / ' . $totalPages . '</span><div class="flex gap-2">';
    if ($pageNum > 1) {
        $html .= '<a class="rounded-md border border-slate-200 bg-white px-3 py-1.5 hover:bg-slate-50" href="' . e($base . $sep . 'p=' . ($pageNum - 1)) . '">Önceki</a>';
    }
    if ($pageNum < $totalPages) {
        $html .= '<a class="rounded-md border border-slate-200 bg-white px-3 py-1.5 hover:bg-slate-50" href="' . e($base . $sep . 'p=' . ($pageNum + 1)) . '">Sonraki</a>';
    }
    $html .= '</div></nav>';
    return $html;
}

function attach_post_categories(PDO $pdo, array $posts): array
{
    if ($posts === []) {
        return $posts;
    }
    $ids = [];
    foreach ($posts as $post) {
        $id = (int) ($post['id'] ?? 0);
        if ($id > 0) {
            $ids[] = $id;
        }
    }
    $ids = array_values(array_unique($ids));
    if ($ids === []) {
        foreach ($posts as &$post) {
            $post['categories'] = [];
        }
        unset($post);
        return $posts;
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT pc.post_id, c.id, c.name, c.slug
         FROM post_categories pc
         INNER JOIN categories c ON c.id = pc.category_id
         WHERE pc.post_id IN ({$placeholders})
         ORDER BY c.name ASC"
    );
    $stmt->execute($ids);
    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $map[(int) $row['post_id']][] = [
            'id'   => (int) $row['id'],
            'name' => (string) $row['name'],
            'slug' => (string) $row['slug'],
        ];
    }
    foreach ($posts as &$post) {
        $post['categories'] = $map[(int) ($post['id'] ?? 0)] ?? [];
    }
    unset($post);
    return $posts;
}

function cms_ensure_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS contact_messages (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_contact_read (is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS slides (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                title VARCHAR(255) NOT NULL DEFAULT '',
                subtitle TEXT NULL,
                button_text VARCHAR(120) NULL,
                link_url VARCHAR(500) NULL,
                image VARCHAR(255) NULL,
                sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_slides_active_order (is_active, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $pdo->exec("INSERT IGNORE INTO options (option_key, option_value) VALUES ('slider_autoplay', '1')");
        $pdo->exec("INSERT IGNORE INTO options (option_key, option_value) VALUES ('slider_interval', '5000')");
        $pdo->exec("INSERT IGNORE INTO options (option_key, option_value) VALUES ('slider_enabled', '1')");
        cms_seed_slides($pdo);
    } catch (Throwable $e) {
        // ignore
    }
    try {
        $iletisim = $pdo->query("SELECT id, content FROM site_pages WHERE slug = 'iletisim' LIMIT 1")->fetch();
        if ($iletisim && is_string($iletisim['content']) && strpos($iletisim['content'], 'yakında eklenecek') !== false) {
            $pdo->prepare('UPDATE site_pages SET content = ? WHERE id = ?')->execute([
                '<p>Öneri, hata bildirimi veya iş birliği için aşağıdaki formu kullanabilirsiniz.</p>',
                (int) $iletisim['id'],
            ]);
        }
    } catch (Throwable $e) {
        // ignore
    }
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

function favicon_tags()
{
    $svg = public_url('favicon.svg');
    $png = public_url('favicon.png');
    return '<link rel="icon" href="' . e($svg) . '" type="image/svg+xml">'
        . '<link rel="icon" href="' . e($png) . '" type="image/png">';
}

function cms_seed(PDO $pdo): void
{
    cms_ensure_schema($pdo);
    $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($userCount === 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, display_name, role)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            'admin',
            'admin@example.com',
            password_hash('Admin123!', PASSWORD_DEFAULT),
            'Yönetici',
            'admin',
        ]);
        $stmt->execute([
            'editor',
            'editor@example.com',
            password_hash('Editor123!', PASSWORD_DEFAULT),
            'Editör',
            'editor',
        ]);
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
            'INSERT INTO site_pages (title, slug, content, status) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute(['Hakkında', 'hakkinda', '<p>Kodcu; yazılım, web ve sunucu notlarını toplayan bir geliştirici günlüğüdür.</p>', 'publish']);
        $stmt->execute(['İletişim', 'iletisim', '<p>Öneri, hata bildirimi veya iş birliği için aşağıdaki formu kullanabilirsiniz.</p>', 'publish']);
        $stmt->execute(['Gizlilik', 'gizlilik', '<p>Taslak gizlilik metni.</p>', 'draft']);
    }

    $optCount = (int) $pdo->query('SELECT COUNT(*) FROM options')->fetchColumn();
    if ($optCount === 0) {
        $stmt = $pdo->prepare('INSERT INTO options (option_key, option_value) VALUES (?, ?)');
        $stmt->execute(['site_title', 'Kodcu']);
        $stmt->execute(['site_tagline', 'Yazılar, sayfalar ve projeler']);
        $stmt->execute(['posts_per_page', '10']);
        $stmt->execute(['site_url', PUBLIC_URL]);
        $stmt->execute(['slider_autoplay', '1']);
        $stmt->execute(['slider_interval', '5000']);
        $stmt->execute(['slider_enabled', '1']);
    }

    cms_seed_slides($pdo);
}

function cms_seed_slides(PDO $pdo): void
{
    try {
        $slideCount = (int) $pdo->query('SELECT COUNT(*) FROM slides')->fetchColumn();
    } catch (Throwable $e) {
        return;
    }
    if ($slideCount > 0) {
        return;
    }
    $ins = $pdo->prepare(
        'INSERT INTO slides (title, subtitle, button_text, link_url, image, sort_order, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([
        'Kodcu’ya hoş geldiniz',
        'Yazılım notları, rehberler ve geliştirici günlüğü.',
        'Yazıları oku',
        '/',
        '',
        0,
        1,
    ]);
    $ins->execute([
        'Görselleri sürükleyip bırakın',
        'Slider slaytlarını panelden sıralayın, yükleyin ve anında yayına alın.',
        'Hakkında',
        public_url('sayfa/hakkinda'),
        '',
        1,
        1,
    ]);
    $ins->execute([
        'Yazılar, sayfalar ve projeler',
        'Kategoriler, medya kütüphanesi ve yorumlarla büyüyen bir geliştirici sitesi.',
        'Kategorilere bak',
        public_url('kategori/rehber'),
        '',
        2,
        1,
    ]);
}

function sanitize_slide_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    if (isset($url[0]) && $url[0] === '/') {
        return $url;
    }
    if (stripos($url, 'http://') === 0 || stripos($url, 'https://') === 0) {
        return $url;
    }
    return '';
}

function active_home_slides(PDO $pdo): array
{
    if (option_get($pdo, 'slider_enabled', '1') !== '1') {
        return [];
    }
    try {
        return $pdo->query(
            'SELECT id, title, subtitle, button_text, link_url, image, sort_order
             FROM slides
             WHERE is_active = 1
             ORDER BY sort_order ASC, id ASC'
        )->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function slider_public_link(string $url): string
{
    $url = sanitize_slide_url($url);
    if ($url === '' || $url === '/') {
        return public_url();
    }
    return $url;
}

function handle_image_upload(string $field = 'featured_image'): ?string
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return null;
    }
    return handle_uploaded_image_file($_FILES[$field]);
}

function handle_uploaded_image_file(array $file): ?string
{
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
    ];
    if (!is_string($mime) || !isset($allowed[$mime])) {
        flash_set('error', 'Yalnızca JPEG, PNG, WebP ve GIF dosyaları kabul edilir.');
        return null;
    }
    $name = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $dest = UPLOAD_DIR . $name;
    if (!move_uploaded_file($tmp, $dest)) {
        flash_set('error', 'Görsel kaydedilemedi.');
        return null;
    }
    return UPLOAD_URL . $name;
}

function handle_multiple_image_uploads(string $field): array
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return [];
    }
    $bag = $_FILES[$field];
    if (!isset($bag['name']) || !is_array($bag['name'])) {
        $path = handle_image_upload($field);
        return $path ? [$path] : [];
    }
    $paths = [];
    $count = count($bag['name']);
    for ($i = 0; $i < $count; $i++) {
        $one = [
            'name'     => $bag['name'][$i] ?? '',
            'type'     => $bag['type'][$i] ?? '',
            'tmp_name' => $bag['tmp_name'][$i] ?? '',
            'error'    => $bag['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size'     => $bag['size'][$i] ?? 0,
        ];
        $path = handle_uploaded_image_file($one);
        if ($path) {
            $paths[] = $path;
        }
    }
    return $paths;
}
