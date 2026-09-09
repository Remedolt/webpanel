<?php
declare(strict_types=1);

if (!isset($currentPage) || !is_string($currentPage)) {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

$postsOpen = in_array($currentPage, ['posts', 'post-new', 'categories'], true);

$navLink = static function (string $page, string $current, string $label, string $svg) : string {
    $active = $page === $current;
    $cls = $active
        ? 'bg-slate-800 text-white border-l-[3px] border-[#2271b1]'
        : 'text-slate-300 hover:bg-slate-800/80 hover:text-white border-l-[3px] border-transparent';
    return '<a href="index.php?page=' . e($page) . '" class="flex items-center gap-3 px-4 py-2.5 text-[13px] font-medium ' . $cls . '">'
        . $svg
        . '<span>' . e($label) . '</span></a>';
};

$iconHome = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955a1.126 1.126 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>';
$iconDoc = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>';
$pendingComments = isset($pdo) && $pdo instanceof PDO
    ? (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn()
    : 0;
$unreadMessages = 0;
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $unreadMessages = (int) $pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();
    } catch (Throwable $e) {
        $unreadMessages = 0;
    }
}

$iconPages = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6.878V6a2.25 2.25 0 0 1 2.25-2.25h7.5A2.25 2.25 0 0 1 18 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 0 0 4.5 9v.878m13.5-3A2.25 2.25 0 0 1 19.5 9v.878m0 0a2.246 2.246 0 0 0-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0 1 21 12v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6c0-.98.626-1.813 1.5-2.122" /></svg>';
$iconComments = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 0 1 1.037-.443 48.282 48.282 0 0 0 5.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>';
$iconInbox = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859M2.25 13.5V6A2.25 2.25 0 0 1 4.5 3.75h15A2.25 2.25 0 0 1 21.75 6v7.5m-19.5 0V18A2.25 2.25 0 0 0 4.5 20.25h15A2.25 2.25 0 0 0 21.75 18v-4.5" /></svg>';
$iconMedia = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A1.5 1.5 0 0 0 21.75 19.5V4.5A1.5 1.5 0 0 0 20.25 3H3.75A1.5 1.5 0 0 0 2.25 4.5v15A1.5 1.5 0 0 0 3.75 21Z" /></svg>';
$iconUsers = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>';
$iconCog = '<svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>';
?>
<aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-40 w-60 -translate-x-full bg-slate-900 text-slate-300 transition-transform lg:static lg:col-start-1 lg:row-start-1 lg:row-span-2 lg:min-h-screen lg:w-auto lg:translate-x-0">
    <div class="flex h-14 items-center gap-2 border-b border-slate-800 px-4">
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-[#2271b1] text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" />
            </svg>
        </span>
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-white">CMS Yönetim</p>
            <p class="truncate text-[11px] text-slate-400">Kontrol paneli</p>
        </div>
    </div>

    <nav class="py-3 text-sm" aria-label="Yönetim menüsü">
        <?= $navLink('dashboard', $currentPage, 'Başlangıç', $iconHome) ?>

        <div>
            <button type="button" class="flex w-full items-center gap-3 px-4 py-2.5 text-[13px] font-medium <?= $postsOpen ? 'text-white' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' ?>" data-submenu-toggle="submenu-posts" aria-expanded="<?= $postsOpen ? 'true' : 'false' ?>">
                <?= $iconDoc ?>
                <span class="flex-1 text-left">Yazılar</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform <?= $postsOpen ? 'rotate-180' : '' ?>" data-submenu-chevron fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </button>
            <div id="submenu-posts" class="<?= $postsOpen ? '' : 'hidden' ?> bg-slate-950/50 py-1">
                <a href="index.php?page=posts" class="block py-2 pl-12 pr-4 text-[13px] <?= $currentPage === 'posts' ? 'text-white' : 'text-slate-400 hover:text-white' ?>">Tüm Yazılar</a>
                <a href="index.php?page=post-new" class="block py-2 pl-12 pr-4 text-[13px] <?= $currentPage === 'post-new' ? 'text-white' : 'text-slate-400 hover:text-white' ?>">Yeni Ekle</a>
                <a href="index.php?page=categories" class="block py-2 pl-12 pr-4 text-[13px] <?= $currentPage === 'categories' ? 'text-white' : 'text-slate-400 hover:text-white' ?>">Kategoriler</a>
            </div>
        </div>

        <?= $navLink('pages', $currentPage, 'Sayfalar', $iconPages) ?>
        <?= $navLink('media', $currentPage, 'Medya Kütüphanesi', $iconMedia) ?>
        <a href="index.php?page=comments" class="flex items-center gap-3 px-4 py-2.5 text-[13px] font-medium <?= $currentPage === 'comments' ? 'bg-slate-800 text-white border-l-[3px] border-[#2271b1]' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white border-l-[3px] border-transparent' ?>">
            <?= $iconComments ?>
            <span class="flex-1">Yorumlar</span>
            <?php if ($pendingComments > 0): ?>
                <span class="rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-semibold text-slate-900"><?= (int) $pendingComments ?></span>
            <?php endif; ?>
        </a>
        <a href="index.php?page=messages" class="flex items-center gap-3 px-4 py-2.5 text-[13px] font-medium <?= $currentPage === 'messages' ? 'bg-slate-800 text-white border-l-[3px] border-[#2271b1]' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white border-l-[3px] border-transparent' ?>">
            <?= $iconInbox ?>
            <span class="flex-1">Mesajlar</span>
            <?php if ($unreadMessages > 0): ?>
                <span class="rounded-full bg-sky-400 px-1.5 py-0.5 text-[10px] font-semibold text-slate-900"><?= (int) $unreadMessages ?></span>
            <?php endif; ?>
        </a>
        <?= $navLink('users', $currentPage, 'Kullanıcılar', $iconUsers) ?>
        <?= $navLink('settings', $currentPage, 'Ayarlar', $iconCog) ?>
    </nav>
</aside>
<div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-slate-900/50 lg:hidden"></div>

<main class="col-start-1 lg:col-start-2 row-start-2 min-w-0 p-4 lg:p-6">
    <?php if (!empty($flash) && is_array($flash)): ?>
        <div class="mb-4 rounded-md border px-4 py-3 text-sm <?= (($flash['type'] ?? '') === 'error') ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-800' ?>">
            <?= e((string) ($flash['message'] ?? '')) ?>
        </div>
    <?php endif; ?>
