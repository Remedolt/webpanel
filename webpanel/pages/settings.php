<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'settings') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    option_set($pdo, 'site_title', trim((string) ($_POST['site_title'] ?? '')));
    option_set($pdo, 'site_tagline', trim((string) ($_POST['site_tagline'] ?? '')));
    option_set($pdo, 'site_url', trim((string) ($_POST['site_url'] ?? PUBLIC_URL)));
    $perPage = (int) ($_POST['posts_per_page'] ?? 10);
    if ($perPage < 5) {
        $perPage = 5;
    }
    if ($perPage > 50) {
        $perPage = 50;
    }
    option_set($pdo, 'posts_per_page', (string) $perPage);
    flash_set('success', 'Ayarlar kaydedildi.');
    redirect('index.php?page=settings');
}

$siteTitleVal = option_get($pdo, 'site_title', 'Örnek Site');
$siteTagline = option_get($pdo, 'site_tagline', '');
$siteUrlVal = option_get($pdo, 'site_url', PUBLIC_URL);
$perPageVal = option_get($pdo, 'posts_per_page', '10');
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Ayarlar</h1>
    <p class="mt-1 text-sm text-slate-500">Genel site bilgileri.</p>
</div>

<div class="max-w-2xl bg-white rounded-lg shadow-sm border border-slate-200 p-6">
    <form method="post" class="space-y-4">
        <?= csrf_field() ?>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700" for="site_title">Site başlığı</label>
            <input id="site_title" name="site_title" value="<?= e($siteTitleVal) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700" for="site_tagline">Slogan</label>
            <input id="site_tagline" name="site_tagline" value="<?= e($siteTagline) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700" for="site_url">Site URL</label>
            <input id="site_url" name="site_url" value="<?= e($siteUrlVal) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            <p class="mt-1 text-xs text-slate-400">“Siteyi görüntüle” bağlantısı bu adresi açar.</p>
        </div>
        <p class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">Ana sayfa slider’ını <a class="text-[#2271b1] hover:underline" href="index.php?page=slider">Slider</a> sayfasından sürükleyerek yönetin.</p>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700" for="posts_per_page">Sayfa başına yazı</label>
            <input id="posts_per_page" name="posts_per_page" type="number" min="5" max="50" value="<?= e($perPageVal) ?>" class="w-32 rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
        </div>
        <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Değişiklikleri kaydet</button>
    </form>
</div>
