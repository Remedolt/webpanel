<?php
declare(strict_types=1);

error_reporting(E_ALL);
register_shutdown_function(static function () {
    $err = error_get_last();
    if (!is_array($err)) {
        return;
    }
    $fatalTypes = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
    if (!in_array($err['type'], $fatalTypes, true)) {
        return;
    }
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<pre style="font:14px/1.45 ui-sans-serif,system-ui,sans-serif;padding:16px">Yönetim paneli hatası: '
        . htmlspecialchars((string) ($err['message'] ?? ''), ENT_QUOTES, 'UTF-8')
        . "\n" . htmlspecialchars((string) ($err['file'] ?? '') . ':' . (string) ($err['line'] ?? ''), ENT_QUOTES, 'UTF-8')
        . '</pre>';
});

$httpsOn = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443')
    || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');

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
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: text/html; charset=UTF-8');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-XSS-Protection: 1; mode=block');

require __DIR__ . '/includes/db.php';

if (!$pdo instanceof PDO) {
    header('Location: install.php');
    exit;
}

try {
    cms_seed($pdo);
} catch (Throwable $seedError) {
    error_log('cms_seed: ' . $seedError->getMessage());
}

$allowedPages = [
    'dashboard'  => 'dashboard.php',
    'posts'      => 'posts.php',
    'post-new'   => 'post-new.php',
    'categories' => 'categories.php',
    'pages'      => 'pages.php',
    'media'      => 'media.php',
    'slider'     => 'slider.php',
    'comments'   => 'comments.php',
    'messages'   => 'messages.php',
    'users'      => 'users.php',
    'settings'   => 'settings.php',
];

$rawPage = isset($_GET['page']) ? (string) $_GET['page'] : 'dashboard';
$rawPage = strtolower($rawPage);
$pageKey = preg_replace('/[^a-z0-9\-]/', '', $rawPage) ?? '';

if ($pageKey === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    csrf_verify();
    $attempts = (int) ($_SESSION['login_attempts'] ?? 0);
    if ($attempts >= 8) {
        flash_set('error', 'Çok fazla başarısız deneme. Oturumu kapatıp tekrar deneyin.');
        redirect('index.php');
    }
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $stmt = $pdo->prepare('SELECT id, username, email, password_hash, display_name, role FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $userRow = $stmt->fetch();
    if ($userRow && password_verify($password, (string) $userRow['password_hash'])) {
        session_regenerate_id(true);
        unset($_SESSION['login_attempts']);
        $_SESSION['user_id'] = (int) $userRow['id'];
        $_SESSION['user_name'] = (string) $userRow['display_name'];
        $_SESSION['user_role'] = (string) $userRow['role'];
        redirect('index.php?page=dashboard');
    }
    $_SESSION['login_attempts'] = $attempts + 1;
    flash_set('error', 'Kullanıcı adı veya şifre hatalı.');
    redirect('index.php');
}

$currentUser = null;
if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT id, username, email, display_name, role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $currentUser = $stmt->fetch() ?: null;
    if (!$currentUser) {
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role']);
    }
}

if ($currentUser === null) {
    $loginFlash = flash_get();
    ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş — <?= e(SITE_NAME) ?></title>
    <?= favicon_tags() ?>
    <base href="<?= e(PANEL_URL) ?>/">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-8">
            <div class="flex items-center gap-3 mb-6">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-slate-900 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0v-4m0-10v.01" />
                    </svg>
                </span>
                <div>
                    <h1 class="text-lg font-semibold text-slate-900"><?= e(SITE_NAME) ?></h1>
                    <p class="text-sm text-slate-500">Yönetim paneline giriş yapın</p>
                </div>
            </div>
            <?php if ($loginFlash): ?>
                <div class="mb-4 rounded-md border px-3 py-2 text-sm <?= $loginFlash['type'] === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' ?>">
                    <?= e((string) $loginFlash['message']) ?>
                </div>
            <?php endif; ?>
            <form method="post" action="index.php" class="space-y-4" autocomplete="on">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="login">
                <div>
                    <label for="username" class="block text-sm font-medium text-slate-700 mb-1">Kullanıcı adı</label>
                    <input id="username" name="username" type="text" required autofocus
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Şifre</label>
                    <input id="password" name="password" type="password" required
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
                <button type="submit" class="w-full rounded-md bg-[#2271b1] px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">
                    Giriş yap
                </button>
            </form>
            <p class="mt-4 text-xs text-slate-400">Varsayılan: <span class="font-mono">admin</span> / <span class="font-mono">Admin123!</span></p>
        </div>
    </div>
</body>
</html>
    <?php
    exit;
}

if ($pageKey === '' || !isset($allowedPages[$pageKey])) {
    $pageKey = 'dashboard';
}

$pageFileName = $allowedPages[$pageKey];
$pagesDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'pages');
$pagePath = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . $pageFileName);

if ($pagesDir === false || $pagePath === false || !is_file($pagePath)) {
    http_response_code(404);
    exit('Sayfa bulunamadı.');
}

$pagesDirNorm = strtolower(str_replace('\\', '/', $pagesDir));
$pagePathNorm = strtolower(str_replace('\\', '/', $pagePath));
if (strpos($pagePathNorm, $pagesDirNorm . '/') !== 0 && $pagePathNorm !== $pagesDirNorm) {
    http_response_code(400);
    exit('Geçersiz sayfa isteği.');
}

$currentPage = $pageKey;

$wantsJson = $_SERVER['REQUEST_METHOD'] === 'POST'
    && (
        isset($_POST['ajax'])
        || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
    );
if ($wantsJson && $pageKey === 'slider') {
    require $pagePath;
    exit;
}

$pageTitles = [
    'dashboard'  => 'Başlangıç',
    'posts'      => 'Tüm Yazılar',
    'post-new'   => isset($_GET['id']) ? 'Yazıyı Düzenle' : 'Yeni Yazı',
    'categories' => 'Kategoriler',
    'pages'      => 'Sayfalar',
    'media'      => 'Medya Kütüphanesi',
    'slider'     => 'Slider',
    'comments'   => 'Yorumlar',
    'messages'   => 'Mesajlar',
    'users'      => 'Kullanıcılar',
    'settings'   => 'Ayarlar',
];
$pageTitle = $pageTitles[$currentPage] ?? 'Yönetim';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/sidebar.php';
require $pagePath;
require __DIR__ . '/includes/footer.php';
