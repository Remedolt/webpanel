<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'panel') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

$themes = panel_themes();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? 'theme');
    if ($action === 'logo') {
        cms_save_logo_field($pdo, 'panel_logo');
        cms_save_logo_field($pdo, 'panel_logo_retina');
        $h = (int) ($_POST['panel_logo_height'] ?? 32);
        if ($h < 20) {
            $h = 20;
        }
        if ($h > 56) {
            $h = 56;
        }
        option_set($pdo, 'panel_logo_height', (string) $h);
        option_set($pdo, 'panel_logo_show_name', isset($_POST['panel_logo_show_name']) ? '1' : '0');
        flash_set('success', 'Panel logosu güncellendi.');
        redirect('index.php?page=panel');
    }
    $picked = option_pick($_POST['panel_theme'] ?? 'classic', array_keys($themes), 'classic');
    option_set($pdo, 'panel_theme', $picked);
    flash_set('success', 'Panel teması güncellendi.');
    redirect('index.php?page=panel');
}

$current = option_get($pdo, 'panel_theme', 'classic');
if (!isset($themes[$current])) {
    $current = 'classic';
}
$panelLogo = option_get($pdo, 'panel_logo', '');
$panelLogoRetina = option_get($pdo, 'panel_logo_retina', '');
$panelLogoHeight = option_get($pdo, 'panel_logo_height', '32');
$panelLogoShowName = option_get($pdo, 'panel_logo_show_name', '1');
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Panel ayarları</h1>
    <p class="mt-1 text-sm text-slate-500">Yönetim panelinin teması ve logosu. Ziyaretçi sitesi etkilenmez.</p>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 mb-6">
    <h2 class="text-sm font-semibold text-slate-900 mb-1">Panel logosu</h2>
    <p class="text-xs text-slate-500 mb-4">Sol menünün üstünde görünür. Retina için 2x görsel yükleyin (PNG veya SVG).</p>
    <form method="post" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="logo">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Logo</label>
            <?php if ($panelLogo !== ''): ?>
                <img src="<?= e(media_src($panelLogo)) ?>" alt="" class="mb-2 h-10 w-auto object-contain bg-slate-900 rounded p-1">
                <label class="mb-2 flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" name="remove_panel_logo" value="1"> Logoyu kaldır</label>
            <?php endif; ?>
            <input type="file" name="panel_logo" accept="image/png,image/jpeg,image/webp,image/svg+xml,image/gif">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Retina logo (2x)</label>
            <?php if ($panelLogoRetina !== ''): ?>
                <img src="<?= e(media_src($panelLogoRetina)) ?>" alt="" class="mb-2 h-10 w-auto object-contain bg-slate-900 rounded p-1">
                <label class="mb-2 flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" name="remove_panel_logo_retina" value="1"> Retina logoyu kaldır</label>
            <?php endif; ?>
            <input type="file" name="panel_logo_retina" accept="image/png,image/jpeg,image/webp,image/svg+xml,image/gif">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Yükseklik (px)</label>
            <input type="number" name="panel_logo_height" min="20" max="56" value="<?= e($panelLogoHeight) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-700 md:mt-8">
            <input type="checkbox" name="panel_logo_show_name" value="1" <?= $panelLogoShowName === '1' ? 'checked' : '' ?>>
            Logo yanında “Remedolt Web Panel” yazısı
        </label>
        <div class="md:col-span-2">
            <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Panel logosunu kaydet</button>
        </div>
    </form>
</div>

<form method="post" class="space-y-6">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="theme">
    <p class="text-sm font-semibold text-slate-900">Panel teması</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <?php foreach ($themes as $id => $theme): ?>
            <?php $on = $current === $id; ?>
            <label class="block cursor-pointer rounded-xl border-2 bg-white p-3 shadow-sm transition-shadow hover:shadow-md <?= $on ? 'ring-2' : '' ?>" style="<?= $on ? 'border-color:var(--p-accent);--tw-ring-color:var(--p-accent)' : 'border-color:#e2e8f0' ?>">
                <input class="sr-only" type="radio" name="panel_theme" value="<?= e($id) ?>" <?= $on ? 'checked' : '' ?>>
                <div class="mb-3 h-24 overflow-hidden rounded-lg flex border border-slate-200">
                    <div style="width:30%;background:<?= e($theme['sidebar']) ?>">
                        <div class="h-3 mt-2 mx-1.5 rounded-sm" style="background:<?= e($theme['accent']) ?>"></div>
                        <div class="h-1.5 mt-2 mx-1.5 rounded-sm opacity-70" style="background:<?= e($theme['sidebar_text']) ?>"></div>
                        <div class="h-1.5 mt-1.5 mx-1.5 rounded-sm opacity-40" style="background:<?= e($theme['sidebar_text']) ?>"></div>
                    </div>
                    <div class="flex-1 flex flex-col" style="background:<?= e($theme['body']) ?>">
                        <div class="h-5 border-b" style="background:<?= e($theme['header']) ?>;border-color:<?= e($theme['header_border']) ?>"></div>
                        <div class="m-2 flex-1 rounded" style="background:<?= e($theme['accent']) ?>;opacity:.25"></div>
                    </div>
                </div>
                <p class="text-sm font-semibold text-slate-900"><?= e((string) $theme['name']) ?></p>
                <p class="mt-0.5 text-xs text-slate-500"><?= e((string) $theme['desc']) ?></p>
                <?php if ($on): ?>
                    <p class="mt-2 text-xs font-medium" style="color:var(--p-accent)">Seçili</p>
                <?php endif; ?>
            </label>
        <?php endforeach; ?>
    </div>
    <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Temayı kaydet</button>
</form>
<script>
document.querySelectorAll('input[name="panel_theme"]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
});
</script>
