<?php
declare(strict_types=1);

$httpsOn = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443');

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

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-XSS-Protection: 1; mode=block');

require __DIR__ . '/includes/db.php';

if (!$pdo instanceof PDO) {
    header('Location: install.php');
    exit;
}

cms_ensure_schema($pdo);
cms_seed($pdo);

$allowedPages = [
    'dashboard'  => 'dashboard.php',
    'posts'      => 'posts.php',
    'post-new'   => 'post-new.php',
    'categories' => 'categories.php',
    'pages'      => 'pages.php',
    'slider'     => 'slider.php',
    'header'     => 'header.php',
    'brand'      => 'brand.php',
    'footer'     => 'footer.php',
    'gallery'    => 'gallery.php',
    'buttons'    => 'buttons.php',
    'partners'   => 'partners.php',
    'staff'      => 'staff.php',
    'counters'   => 'counters.php',
    'faq'        => 'faq.php',
    'reviews'    => 'reviews.php',
    'services'   => 'services.php',
    'spot'       => 'spot.php',
    'video'      => 'video.php',
    'places'     => 'places.php',
    'home'       => 'home.php',
    'activity'   => 'activity.php',
    'redirects'  => 'redirects.php',
    'contact'    => 'contact.php',
    'inquiries'  => 'contact.php',
    'comments'   => 'comments.php',
    'media'      => 'media.php',
    'users'      => 'users.php',
    'profile'    => 'profile.php',
    'panel'      => 'panel.php',
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
    try {
        $stmt = $pdo->prepare('SELECT id, username, email, password_hash, display_name, role, avatar FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
    } catch (PDOException $e) {
        $stmt = $pdo->prepare('SELECT id, username, email, password_hash, display_name, role FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
    }
    $userRow = $stmt->fetch();
    if ($userRow && password_verify($password, (string) $userRow['password_hash'])) {
        session_regenerate_id(true);
        unset($_SESSION['login_attempts']);
        $_SESSION['user_id'] = (int) $userRow['id'];
        $_SESSION['user_name'] = (string) $userRow['display_name'];
        $_SESSION['user_role'] = (string) $userRow['role'];
        $GLOBALS['cms_actor'] = $userRow;
        cms_audit($pdo, 'login', 'user', (int) $userRow['id'], (string) $userRow['display_name']);
        redirect('index.php?page=dashboard');
    }
    $_SESSION['login_attempts'] = $attempts + 1;
    flash_set('error', 'Kullanıcı adı veya şifre hatalı.');
    redirect('index.php');
}

$currentUser = null;
if (!empty($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare('SELECT id, username, email, display_name, role, avatar FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $_SESSION['user_id']]);
    } catch (PDOException $e) {
        $stmt = $pdo->prepare('SELECT id, username, email, display_name, role FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $_SESSION['user_id']]);
    }
    $currentUser = $stmt->fetch() ?: null;
    if (!$currentUser) {
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role']);
    }
}
$GLOBALS['cms_actor'] = is_array($currentUser) ? $currentUser : null;

if ($currentUser === null) {
    $loginFlash = flash_get();
    $panelTheme = panel_theme_get($pdo);
    ?>
<!DOCTYPE html>
<html lang="tr" data-panel-theme="<?= e((string) $panelTheme['id']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş — <?= e(SITE_NAME) ?></title>
    <?= favicon_tags() ?>
    <base href="<?= e(PANEL_URL) ?>/">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        <?= panel_theme_css($panelTheme) ?>
        .login-hero{position:relative;overflow:hidden;min-height:100vh}
        .login-hero-photo{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;transform:scale(1.06);animation:loginKen 22s ease-in-out infinite alternate}
        .login-hero-wash{position:absolute;inset:0;background:linear-gradient(160deg,rgba(12,10,18,.28),rgba(70,14,32,.28) 45%,rgba(8,10,16,.38))}
        .login-hero-copy{position:relative;z-index:1}
        .login-hero-copy p,.login-hero-copy h1{animation:loginIn .9s ease both}
        .login-hero-copy h1{animation-delay:.12s}
        @keyframes loginKen{from{transform:scale(1.04) translate3d(0,0,0)}to{transform:scale(1.12) translate3d(-1.5%,-1%,0)}}
        @keyframes loginIn{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
        @media (prefers-reduced-motion:reduce){
            .login-hero-photo,.login-hero-copy p,.login-hero-copy h1{animation:none}
        }
    </style>
</head>
<body class="panel-app min-h-screen">
    <div class="min-h-screen grid lg:grid-cols-2">
        <div class="hidden lg:block login-hero">
            <img class="login-hero-photo" src="assets/login-bg.jpg" alt="">
            <div class="login-hero-wash"></div>
            <div class="login-hero-copy flex h-full min-h-screen flex-col justify-center p-12 text-white">
                <div>
                    <p class="text-xl font-medium text-white/90 drop-shadow">Yönetim paneli</p>
                    <h1 class="mt-4 text-5xl xl:text-6xl font-semibold tracking-tight leading-tight drop-shadow-lg"><?= e(SITE_NAME) ?></h1>
                </div>
            </div>
        </div>
        <div class="flex items-center justify-center p-6">
            <div class="w-full max-w-md">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-white" style="background:var(--p-accent)">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0v-4m0-10v.01" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Panele giriş</h2>
                            <p class="text-sm text-slate-500">Hesabınızla devam edin</p>
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
                </div>
            </div>
        </div>
    </div>
<script>
(function () {
    function msg(el) {
        if (!el.validity || el.validity.valid) return '';
        if (el.validity.valueMissing) return 'Bu alanı doldurun.';
        if (el.validity.typeMismatch) return 'Geçerli bir değer girin.';
        return 'Bu alanı kontrol edin.';
    }
    document.addEventListener('invalid', function (e) {
        var el = e.target;
        if (!el || !el.setCustomValidity) return;
        el.setCustomValidity(msg(el));
    }, true);
    document.addEventListener('input', function (e) {
        if (e.target && e.target.setCustomValidity) e.target.setCustomValidity('');
    }, true);
})();
</script>
</body>
</html>
    <?php
    exit;
}

if ($pageKey === 'menus') {
    $pageKey = 'header';
}
if ($pageKey === 'inquiries') {
    $pageKey = 'contact';
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
$pageTitles = [
    'dashboard'  => 'Başlangıç',
    'posts'      => 'Tüm Yazılar',
    'post-new'   => isset($_GET['id']) ? 'Yazıyı Düzenle' : 'Yeni Yazı',
    'categories' => 'Kategoriler',
    'pages'      => 'Sayfalar',
    'slider'     => 'Slider',
    'header'     => 'Header',
    'brand'      => 'Marka',
    'footer'     => 'Footer',
    'gallery'    => 'Galeri',
    'buttons'    => 'Sağ butonlar',
    'partners'   => 'Referanslar',
    'staff'      => 'Personel',
    'counters'   => 'Sayaçlar',
    'faq'        => 'SSS',
    'reviews'    => 'Müşteri yorumları',
    'services'   => 'Hizmetler',
    'spot'       => 'Vitrin',
    'video'      => 'Video',
    'places'     => 'Öne çıkanlar',
    'home'       => 'Ana sayfa',
    'activity'   => 'İşlem günlüğü',
    'redirects'  => 'Yönlendirmeler',
    'contact'    => 'İletişim',
    'comments'   => 'Yorumlar',
    'media'      => 'Medya Kütüphanesi',
    'users'      => 'Kullanıcılar',
    'profile'    => 'Profil',
    'panel'      => 'Panel ayarları',
    'settings'   => 'Ayarlar',
];
$pageTitle = $pageTitles[$currentPage] ?? 'Yönetim';
if ($currentPage === 'settings') {
    $tabs = cms_settings_tabs();
    $tabNow = cms_settings_tab();
    $pageTitle = 'Ayarlar — ' . ($tabs[$tabNow] ?? 'Genel');
}

ob_start();
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/sidebar.php';
require $pagePath;
require __DIR__ . '/includes/footer.php';
