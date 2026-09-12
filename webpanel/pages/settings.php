<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'settings') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

$settingsTab = strtolower((string) ($_GET['tab'] ?? 'genel'));
if (!in_array($settingsTab, ['genel', 'erisilebilirlik'], true)) {
    $settingsTab = 'genel';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $form = strtolower((string) ($_POST['settings_form'] ?? 'genel'));

    if ($form === 'erisilebilirlik') {
        option_set($pdo, 'a11y_widget', isset($_POST['a11y_widget']) ? '1' : '0');
        $position = ((string) ($_POST['a11y_position'] ?? 'left') === 'right') ? 'right' : 'left';
        option_set($pdo, 'a11y_position', $position);
        option_set($pdo, 'a11y_skip_link', isset($_POST['a11y_skip_link']) ? '1' : '0');
        flash_set('success', 'Erişilebilirlik ayarları kaydedildi.');
        redirect('index.php?page=settings&tab=erisilebilirlik');
    }

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
$a11yWidgetVal = option_get($pdo, 'a11y_widget', '1') === '1';
$a11yPositionVal = option_get($pdo, 'a11y_position', 'left') === 'right' ? 'right' : 'left';
$a11ySkipVal = option_get($pdo, 'a11y_skip_link', '1') === '1';

$tabClass = static function (bool $active): string {
    return $active
        ? 'border-[#2271b1] text-slate-900 font-semibold'
        : 'border-transparent text-slate-500 hover:text-slate-800';
};
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Ayarlar</h1>
    <p class="mt-1 text-sm text-slate-500">Genel site bilgileri ve erişilebilirlik seçenekleri.</p>
</div>

<nav class="mb-6 flex gap-6 border-b border-slate-200 text-sm" aria-label="Ayar sekmeleri">
    <a href="index.php?page=settings"
       class="border-b-2 pb-2.5 <?= $tabClass($settingsTab === 'genel') ?>">Genel</a>
    <a href="index.php?page=settings&amp;tab=erisilebilirlik"
       class="border-b-2 pb-2.5 <?= $tabClass($settingsTab === 'erisilebilirlik') ?>">Erişilebilirlik</a>
</nav>

<?php if ($settingsTab === 'erisilebilirlik'): ?>
    <div class="max-w-2xl bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <form method="post" class="space-y-5">
            <?= csrf_field() ?>
            <input type="hidden" name="settings_form" value="erisilebilirlik">

            <label class="flex items-start gap-3 cursor-pointer">
                <input id="a11y_widget" name="a11y_widget" type="checkbox" value="1" <?= $a11yWidgetVal ? 'checked' : '' ?>
                       class="mt-1 h-4 w-4 rounded border-slate-300 text-[#2271b1] focus:ring-[#2271b1]">
                <span>
                    <span class="block text-sm font-medium text-slate-800">Erişilebilirlik menüsü</span>
                    <span class="mt-0.5 block text-xs text-slate-500">Ziyaretçi sitesinin köşesinde yazı boyutu, kontrast, okunaklı yazı tipi ve imleç ayarları sunar. Tercihler tarayıcıda saklanır.</span>
                </span>
            </label>

            <fieldset class="space-y-2">
                <legend class="text-sm font-medium text-slate-700">Menü konumu</legend>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="radio" name="a11y_position" value="left" <?= $a11yPositionVal === 'left' ? 'checked' : '' ?>
                           class="h-4 w-4 border-slate-300 text-[#2271b1] focus:ring-[#2271b1]">
                    Sol alt
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="radio" name="a11y_position" value="right" <?= $a11yPositionVal === 'right' ? 'checked' : '' ?>
                           class="h-4 w-4 border-slate-300 text-[#2271b1] focus:ring-[#2271b1]">
                    Sağ alt
                </label>
            </fieldset>

            <label class="flex items-start gap-3 cursor-pointer">
                <input id="a11y_skip_link" name="a11y_skip_link" type="checkbox" value="1" <?= $a11ySkipVal ? 'checked' : '' ?>
                       class="mt-1 h-4 w-4 rounded border-slate-300 text-[#2271b1] focus:ring-[#2271b1]">
                <span>
                    <span class="block text-sm font-medium text-slate-800">“İçeriğe atla” bağlantısı</span>
                    <span class="mt-0.5 block text-xs text-slate-500">Klavye ile ilk Tab’da görünür; menüyü atlayıp ana içeriğe geçer.</span>
                </span>
            </label>

            <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">
                Menüdeki araçlar: yazı boyutu, yüksek / negatif kontrast, gri tonlama, bağlantı vurgusu, okunaklı yazı tipi, satır aralığı, büyük imleç ve animasyonu durdurma.
            </div>

            <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Değişiklikleri kaydet</button>
        </form>
    </div>
<?php else: ?>
    <div class="max-w-2xl bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <form method="post" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="settings_form" value="genel">
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
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="posts_per_page">Sayfa başına yazı</label>
                <input id="posts_per_page" name="posts_per_page" type="number" min="5" max="50" value="<?= e($perPageVal) ?>" class="w-32 rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>
            <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Değişiklikleri kaydet</button>
        </form>
    </div>
<?php endif; ?>
