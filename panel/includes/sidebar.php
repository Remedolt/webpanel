<?php
declare(strict_types=1);

if (!isset($currentPage) || !is_string($currentPage)) {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

$postsOpen = in_array($currentPage, ['posts', 'post-new', 'categories', 'comments'], true);
$settingsOpen = ($currentPage === 'settings');
$settingsTabNav = $settingsOpen ? cms_settings_tab() : '';
$pendingCommentsNav = 0;
$newInquiriesNav = 0;
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $pendingCommentsNav = (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn();
    } catch (PDOException $e) {
        $pendingCommentsNav = 0;
    }
    try {
        $newInquiriesNav = (int) $pdo->query('SELECT COUNT(*) FROM inquiries WHERE is_read = 0')->fetchColumn();
    } catch (PDOException $e) {
        try {
            $newInquiriesNav = (int) $pdo->query('SELECT COUNT(*) FROM inquiries')->fetchColumn();
        } catch (PDOException $e2) {
            $newInquiriesNav = 0;
        }
    }
}

$navLink = static function (string $page, string $current, string $label, string $svg, int $badge = 0) : string {
    $active = $page === $current;
    $cls = $active ? 'panel-nav-link is-active' : 'panel-nav-link';
    $html = '<a href="index.php?page=' . e($page) . '" class="' . $cls . '">'
        . $svg
        . '<span class="flex-1">' . e($label) . '</span>';
    if ($badge > 0) {
        $html .= '<span class="panel-badge">' . (int) $badge . '</span>';
    }
    return $html . '</a>';
};

$iconHome = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955a1.126 1.126 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>';
$iconDoc = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>';
$iconPages = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6.878V6a2.25 2.25 0 0 1 2.25-2.25h7.5A2.25 2.25 0 0 1 18 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 0 0 4.5 9v.878m13.5-3A2.25 2.25 0 0 1 19.5 9v.878m0 0a2.246 2.246 0 0 0-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0 1 21 12v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6c0-.98.626-1.813 1.5-2.122" /></svg>';
$iconMedia = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A1.5 1.5 0 0 0 21.75 19.5V4.5A1.5 1.5 0 0 0 20.25 3H3.75A1.5 1.5 0 0 0 2.25 4.5v15A1.5 1.5 0 0 0 3.75 21Z" /></svg>';
$iconUsers = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>';
$iconCog = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>';
$iconSlider = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25ZM8.25 12h.008v.008H8.25V12Zm0 0 2.47 2.47a.75.75 0 0 0 1.06 0L15.75 10.5" /></svg>';
$iconMenu = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>';
$iconFooter = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5M3.75 9.75h16.5m-16.5 9h16.5M3.75 15.75h16.5" /></svg>';
$iconComments = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 0 1 1.037-.443 48.282 48.282 0 0 0 5.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>';
$iconGallery = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A1.5 1.5 0 0 0 21.75 19.5V4.5A1.5 1.5 0 0 0 20.25 3H3.75A1.5 1.5 0 0 0 2.25 4.5v15A1.5 1.5 0 0 0 3.75 21Z" /></svg>';
$iconBtn = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" /></svg>';
$iconPanel = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.098 19.902a3.75 3.75 0 0 0 5.304 0l6.401-6.402M6.75 21A3.75 3.75 0 0 1 3 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v1.072M6.75 21a3.75 3.75 0 0 0 3.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.048M9.75 9.75h4.5m-9 3h4.5" /></svg>';
$iconInbox = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859M2.25 13.5V6.75A2.25 2.25 0 0 1 4.5 4.5h15a2.25 2.25 0 0 1 2.25 2.25v6.75m-19.5 0V18A2.25 2.25 0 0 0 4.5 20.25h15A2.25 2.25 0 0 0 21.75 18v-4.5" /></svg>';
$iconPartners = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 9V6Zm9.75 0A2.25 2.25 0 0 1 15.75 3.75H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 8.25 20.25H6A2.25 2.25 0 0 1 3.75 18v-2.25Zm9.75 0A2.25 2.25 0 0 1 15.75 13.5H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" /></svg>';
$iconStaff = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.118a7.5 7.5 0 0 1 15 0A17.93 17.93 0 0 1 12 21.75a17.93 17.93 0 0 1-7.5-1.632Z" /></svg>';
$iconCounters = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5" /></svg>';
$iconServices = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" /><path stroke-linecap="round" stroke-linejoin="round" d="M21 16.5a2.25 2.25 0 0 1-2.25 2.25h-3a2.25 2.25 0 0 1 0-4.5h3A2.25 2.25 0 0 1 21 16.5Z" /></svg>';
$iconSpot = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h4.5A2.25 2.25 0 0 1 12.75 6v12A2.25 2.25 0 0 1 10.5 20.25H6A2.25 2.25 0 0 1 3.75 18V6Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 8.25h3.75M16.5 12h3.75M16.5 15.75H19.5" /></svg>';
$iconVideo = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15.91 11.672a.375.375 0 0 1 0 .656l-5.603 3.113a.375.375 0 0 1-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112Z" /></svg>';
$iconPlaces = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h5.25v16.5H6A2.25 2.25 0 0 1 3.75 18V6ZM12.75 3.75H18A2.25 2.25 0 0 1 20.25 6v12A2.25 2.25 0 0 1 18 20.25h-5.25V3.75Z" /></svg>';
$iconLayout = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5" /></svg>';
$iconBrand = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.098 19.902a3.75 3.75 0 0 0 5.304 0l6.401-6.402M6.75 21A3.75 3.75 0 0 1 3 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v1.072M6.75 21a3.75 3.75 0 0 0 3.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.048M9.75 9.75h4.5m-9 3h4.5" /></svg>';
$userRole = isset($currentUser['role']) ? (string) $currentUser['role'] : '';
?>
<aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-40 w-60 -translate-x-full transition-transform lg:static lg:col-start-1 lg:row-start-1 lg:row-span-2 lg:min-h-screen lg:w-auto lg:translate-x-0">
    <div class="flex h-14 items-center gap-2 border-b px-4 shrink-0" style="border-color:rgba(255,255,255,.08)">
        <?= isset($pdo) && $pdo instanceof PDO ? panel_brand_html($pdo) : '' ?>
    </div>

    <nav class="py-2 text-sm" aria-label="Yönetim menüsü">
        <?= $navLink('dashboard', $currentPage, 'Başlangıç', $iconHome) ?>

        <p class="panel-nav-label">İçerik</p>
        <div>
            <button type="button" class="panel-nav-link w-full <?= $postsOpen ? 'is-active' : '' ?>" data-submenu-toggle="submenu-posts" aria-expanded="<?= $postsOpen ? 'true' : 'false' ?>">
                <?= $iconDoc ?>
                <span class="flex-1 text-left">Yazılar</span>
                <?php if ($pendingCommentsNav > 0 && !$postsOpen): ?>
                    <span class="panel-badge"><?= (int) $pendingCommentsNav ?></span>
                <?php endif; ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform <?= $postsOpen ? 'rotate-180' : '' ?>" data-submenu-chevron fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </button>
            <div id="submenu-posts" class="<?= $postsOpen ? '' : 'hidden' ?> panel-subwrap py-1">
                <a href="index.php?page=posts" class="panel-nav-sub <?= $currentPage === 'posts' ? 'is-active' : '' ?>">Tüm yazılar</a>
                <a href="index.php?page=post-new" class="panel-nav-sub <?= $currentPage === 'post-new' ? 'is-active' : '' ?>">Yeni yazı</a>
                <a href="index.php?page=categories" class="panel-nav-sub <?= $currentPage === 'categories' ? 'is-active' : '' ?>">Kategoriler</a>
                <a href="index.php?page=comments" class="panel-nav-sub <?= $currentPage === 'comments' ? 'is-active' : '' ?>">Yorumlar<?= $pendingCommentsNav > 0 ? ' (' . (int) $pendingCommentsNav . ')' : '' ?></a>
            </div>
        </div>
        <?= $navLink('pages', $currentPage, 'Sayfalar', $iconPages) ?>
        <?= $navLink('services', $currentPage, 'Hizmetler', $iconServices) ?>
        <?= $navLink('partners', $currentPage, 'Referanslar', $iconPartners) ?>
        <?= $navLink('staff', $currentPage, 'Personel', $iconStaff) ?>
        <?= $navLink('contact', $currentPage, 'İletişim', $iconInbox, $newInquiriesNav) ?>
        <?= $navLink('gallery', $currentPage, 'Galeri', $iconGallery) ?>
        <?= $navLink('slider', $currentPage, 'Slider', $iconSlider) ?>
        <?= $navLink('counters', $currentPage, 'Sayaçlar', $iconCounters) ?>
        <?= $navLink('faq', $currentPage, 'SSS', $iconPages) ?>
        <?= $navLink('reviews', $currentPage, 'Müşteri yorumları', $iconComments) ?>
        <?= $navLink('media', $currentPage, 'Medya', $iconMedia) ?>

        <p class="panel-nav-label">Görünüm</p>
        <?= $navLink('home', $currentPage, 'Ana sayfa', $iconLayout) ?>
        <?= $navLink('header', $currentPage, 'Header', $iconMenu) ?>
        <?= $navLink('brand', $currentPage, 'Marka', $iconBrand) ?>
        <?= $navLink('footer', $currentPage, 'Footer', $iconFooter) ?>
        <?= $navLink('spot', $currentPage, 'Vitrin', $iconSpot) ?>
        <?= $navLink('video', $currentPage, 'Video', $iconVideo) ?>
        <?= $navLink('places', $currentPage, 'Öne çıkanlar', $iconPlaces) ?>
        <?= $navLink('buttons', $currentPage, 'Sağ butonlar', $iconBtn) ?>

        <p class="panel-nav-label">Sistem</p>
        <?= $navLink('users', $currentPage, 'Kullanıcılar', $iconUsers) ?>
        <?= $navLink('activity', $currentPage, 'İşlem günlüğü', $iconCounters) ?>
        <?= $navLink('redirects', $currentPage, 'Yönlendirmeler', $iconPages) ?>
        <?= $navLink('panel', $currentPage, 'Panel', $iconPanel) ?>
        <div>
            <button type="button" class="panel-nav-link w-full <?= $settingsOpen ? 'is-active' : '' ?>" data-submenu-toggle="submenu-settings" aria-expanded="<?= $settingsOpen ? 'true' : 'false' ?>">
                <?= $iconCog ?>
                <span class="flex-1 text-left">Ayarlar</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform <?= $settingsOpen ? 'rotate-180' : '' ?>" data-submenu-chevron fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </button>
            <div id="submenu-settings" class="<?= $settingsOpen ? '' : 'hidden' ?> panel-subwrap py-1">
                <?php foreach (cms_settings_tabs() as $tabKey => $tabLabel): ?>
                    <a href="index.php?page=settings&amp;tab=<?= e($tabKey) ?>" class="panel-nav-sub <?= $settingsTabNav === $tabKey ? 'is-active' : '' ?>"><?= e($tabLabel) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </nav>

    <?php if (isset($currentUser) && is_array($currentUser)): ?>
        <div class="mt-auto border-t px-3 py-3" style="border-color:rgba(255,255,255,.08)">
            <a href="index.php?page=profile" class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-white/5">
                <?= user_avatar_html($currentUser, 'h-8 w-8') ?>
                <span class="min-w-0">
                    <span class="block truncate text-xs font-semibold" style="color:var(--p-nav-active)"><?= e((string) $currentUser['display_name']) ?></span>
                    <span class="block truncate text-[11px]" style="color:var(--p-sidebar-muted)"><?= e($roleLabel[$userRole] ?? $userRole) ?></span>
                </span>
            </a>
        </div>
    <?php endif; ?>
</aside>
<div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-slate-900/50 lg:hidden"></div>

<main class="col-start-1 lg:col-start-2 row-start-2 min-w-0 p-4 lg:p-6">
    <?php if (!empty($flash) && is_array($flash) && (($flash['type'] ?? '') === 'error')): ?>
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-4 text-red-800">
            <p class="text-base font-semibold">Kayıt yapılamadı</p>
            <p class="mt-1 text-sm"><?= e((string) ($flash['message'] ?? '')) ?></p>
        </div>
    <?php endif; ?>
