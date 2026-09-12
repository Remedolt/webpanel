<?php
declare(strict_types=1);

if (!isset($currentPage, $pageTitle, $currentUser, $pdo) || !is_array($currentUser)) {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

$siteTitle = option_get($pdo, 'site_title', 'Örnek Site');
$siteUrl = option_get($pdo, 'site_url', PUBLIC_URL);
$flash = flash_get();
$searchQuery = trim((string) ($_GET['q'] ?? ''));
$pendingComments = 0;
$newInquiries = 0;
try {
    $pendingComments = (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn();
} catch (PDOException $e) {
    $pendingComments = 0;
}
try {
    $newInquiries = (int) $pdo->query('SELECT COUNT(*) FROM inquiries WHERE is_read = 0')->fetchColumn();
} catch (PDOException $e) {
    $newInquiries = 0;
}
$notifyCount = $pendingComments + $newInquiries;
$panelTheme = panel_theme_get($pdo);
?>
<!DOCTYPE html>
<html lang="tr" data-panel-theme="<?= e((string) $panelTheme['id']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= e(SITE_NAME) ?></title>
    <?= favicon_tags() ?>
    <base href="<?= e(PANEL_URL) ?>/">
    <script src="https://cdn.tailwindcss.com"></script>
    <style><?= panel_theme_css($panelTheme) ?></style>
</head>
<body class="panel-app text-slate-800 antialiased">
<div id="admin-shell" class="min-h-screen lg:grid lg:grid-cols-[16.5rem_1fr] lg:grid-rows-[3.5rem_1fr]">
    <header class="sticky top-0 z-30 col-start-1 lg:col-start-2 row-start-1 flex h-14 items-center gap-3 border-b border-slate-200 bg-white px-4">
        <button type="button" id="sidebar-toggle" class="lg:hidden rounded-md p-2 text-slate-600 hover:bg-slate-100" aria-label="Menüyü aç">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>

        <a href="<?= e($siteUrl) ?>" target="_blank" rel="noopener noreferrer"
           class="hidden sm:inline-flex items-center gap-1.5 rounded-md border border-slate-200 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
            </svg>
            Siteyi görüntüle
        </a>

        <form action="index.php" method="get" class="flex-1 max-w-md">
            <input type="hidden" name="page" value="posts">
            <label class="relative block">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" />
                    </svg>
                </span>
                <input type="search" name="q" value="<?= e($searchQuery) ?>" placeholder="Yazılarda ara…"
                       class="w-full rounded-full border border-slate-200 bg-slate-50 py-1.5 pl-9 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-200">
            </label>
        </form>

        <div class="ml-auto flex items-center gap-3">
            <?php
            $saveHint = '';
            if (!empty($flash) && is_array($flash) && (string) ($flash['type'] ?? '') !== 'error') {
                $kind = (string) ($flash['kind'] ?? 'success');
                $hints = array(
                    'add' => 'Ekleme yapıldı',
                    'update' => 'Güncelleme yapıldı',
                    'delete' => 'Silindi',
                    'approve' => 'Onaylandı',
                    'success' => 'Güncelleme yapıldı',
                );
                $saveHint = $hints[$kind] ?? 'Güncelleme yapıldı';
            }
            ?>
            <?php if ($saveHint !== ''): ?>
                <span id="cms-save-hint" class="text-sm font-medium text-emerald-600 whitespace-nowrap"><?= e($saveHint) ?></span>
            <?php endif; ?>
            <a href="index.php?page=post-new" class="hidden sm:inline-flex items-center rounded-lg bg-[#2271b1] px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-800">
                Yeni yazı
            </a>
            <div class="relative" data-dropdown>
                <button type="button" class="relative rounded-md p-2 text-slate-600 hover:bg-slate-100" data-dropdown-button aria-label="Bildirimler">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                    <?php if ($notifyCount > 0): ?>
                        <span class="absolute right-1 top-1 min-w-[1rem] h-4 px-1 rounded-full bg-red-500 text-[10px] font-bold leading-4 text-white text-center"><?= $notifyCount > 9 ? '9+' : (int) $notifyCount ?></span>
                    <?php endif; ?>
                </button>
                <div class="hidden absolute right-0 mt-2 w-80 rounded-lg border border-slate-200 bg-white py-2 shadow-lg z-40" data-dropdown-panel>
                    <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Bildirimler</p>
                    <a href="index.php?page=comments<?= $pendingComments > 0 ? '&amp;status=pending' : '' ?>" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        <?= $pendingComments > 0 ? e((string) $pendingComments . ' yorum onay bekliyor') : 'Bekleyen yorum yok' ?>
                    </a>
                    <a href="index.php?page=contact" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        <?= $newInquiries > 0 ? e((string) $newInquiries . ' yeni iletişim mesajı') : 'Yeni iletişim mesajı yok' ?>
                    </a>
                </div>
            </div>

            <div class="relative" data-dropdown>
                <button type="button" class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-slate-700 hover:bg-slate-100" data-dropdown-button>
                    <?= user_avatar_html($currentUser, 'h-8 w-8') ?>
                    <span class="hidden md:block font-medium"><?= e((string) $currentUser['display_name']) ?></span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div class="hidden absolute right-0 mt-2 w-52 rounded-lg border border-slate-200 bg-white py-1 shadow-lg z-40" data-dropdown-panel>
                    <a href="index.php?page=profile" class="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        Profili düzenle
                    </a>
                    <a href="index.php?page=logout" class="flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>
                        Çıkış
                    </a>
                </div>
            </div>
        </div>
    </header>
