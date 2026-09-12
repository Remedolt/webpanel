<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'places') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    option_set($pdo, 'place_show_home', isset($_POST['place_show_home']) ? '1' : '0');
    option_set($pdo, 'place_kicker', trim((string) ($_POST['place_kicker'] ?? '')));
    option_set($pdo, 'place_heading', trim((string) ($_POST['place_heading'] ?? '')));
    for ($n = 1; $n <= 2; $n++) {
        option_set($pdo, 'place_' . $n . '_kicker', trim((string) ($_POST['place_' . $n . '_kicker'] ?? '')));
        option_set($pdo, 'place_' . $n . '_title', trim((string) ($_POST['place_' . $n . '_title'] ?? '')));
        option_set($pdo, 'place_' . $n . '_text', trim((string) ($_POST['place_' . $n . '_text'] ?? '')));
        option_set($pdo, 'place_' . $n . '_link', trim((string) ($_POST['place_' . $n . '_link'] ?? '')));
        option_set($pdo, 'place_' . $n . '_btn', trim((string) ($_POST['place_' . $n . '_btn'] ?? '')));
        $up = handle_image_upload('place_' . $n . '_image');
        if ($up) {
            option_set($pdo, 'place_' . $n . '_image', $up);
        }
        if (isset($_POST['remove_place_' . $n . '_image'])) {
            option_set($pdo, 'place_' . $n . '_image', '');
        }
    }
    flash_set('success', 'Öne çıkanlar kaydedildi.');
    redirect('index.php?page=places');
}

$kicker = option_get($pdo, 'place_kicker', 'Çalışma alanımız');
$heading = option_get($pdo, 'place_heading', 'İki bakış, tek sade deneyim');
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Öne çıkanlar</h1>
    <p class="mt-1 text-sm text-slate-500">Ana sayfadaki iki büyük fotoğraf kartı. Başlık ortada, kartların üzerinde yazı ve link durur.</p>
</div>
<form method="post" enctype="multipart/form-data" class="grid grid-cols-1 xl:grid-cols-[minmax(0,28rem)_1fr] gap-6 items-start" data-preview="places">
    <?= csrf_field() ?>
    <div class="space-y-4">
        <div class="bg-white rounded-lg border border-slate-200 p-5 space-y-3">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="place_show_home" value="1" <?= option_get($pdo, 'place_show_home', '1') === '1' ? 'checked' : '' ?>>
                Ana sayfada göster
            </label>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Küçük başlık</label>
                <input name="place_kicker" value="<?= e($kicker) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Başlık</label>
                <input name="place_heading" value="<?= e($heading) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
        </div>
        <?php for ($n = 1; $n <= 2; $n++): ?>
            <?php $img = option_get($pdo, 'place_' . $n . '_image', ''); ?>
            <div class="bg-white rounded-lg border border-slate-200 p-5 space-y-3">
                <h2 class="text-sm font-semibold text-slate-900">Kart <?= $n ?></h2>
                <?php if ($img !== ''): ?>
                    <img src="<?= e(media_src($img)) ?>" alt="" class="h-28 w-full rounded-lg object-cover border border-slate-200">
                    <label class="flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" name="remove_place_<?= $n ?>_image" value="1"> Bu görseli kaldır</label>
                <?php endif; ?>
                <input type="file" name="place_<?= $n ?>_image" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm">
                <input name="place_<?= $n ?>_kicker" value="<?= e(option_get($pdo, 'place_' . $n . '_kicker', '')) ?>" placeholder="Üst yazı (konum)" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                <input name="place_<?= $n ?>_title" value="<?= e(option_get($pdo, 'place_' . $n . '_title', '')) ?>" placeholder="Başlık" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                <textarea name="place_<?= $n ?>_text" rows="2" placeholder="Kısa yazı" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"><?= e(option_get($pdo, 'place_' . $n . '_text', '')) ?></textarea>
                <div class="grid grid-cols-2 gap-2">
                    <input name="place_<?= $n ?>_btn" value="<?= e(option_get($pdo, 'place_' . $n . '_btn', 'İncele')) ?>" placeholder="Buton" class="rounded-md border border-slate-200 px-3 py-2 text-sm">
                    <input name="place_<?= $n ?>_link" value="<?= e(option_get($pdo, 'place_' . $n . '_link', '')) ?>" placeholder="/hizmetler" class="rounded-md border border-slate-200 px-3 py-2 text-sm">
                </div>
            </div>
        <?php endfor; ?>
        <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Kartları kaydet</button>
    </div>
    <aside class="xl:sticky xl:top-20">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Sitede böyle durur</p>
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <p class="text-center text-[10px] font-bold uppercase tracking-wide text-sky-600" data-preview-place-kicker><?= e($kicker) ?></p>
            <p class="text-center mt-1 text-sm font-bold text-slate-900" data-preview-place-heading><?= e($heading) ?></p>
            <div class="mt-4 grid grid-cols-2 gap-2">
                <?php for ($n = 1; $n <= 2; $n++): ?>
                    <?php $img = option_get($pdo, 'place_' . $n . '_image', ''); ?>
                    <div class="relative overflow-hidden rounded-xl bg-slate-800 min-h-[110px]">
                        <?php if ($img !== ''): ?><img src="<?= e(media_src($img)) ?>" alt="" class="h-28 w-full object-cover"><?php else: ?><div class="h-28 bg-slate-700"></div><?php endif; ?>
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 to-transparent"></div>
                        <div class="absolute left-2 right-2 bottom-2 text-white">
                            <p class="text-[9px] uppercase tracking-wide opacity-80" data-preview-place-k<?= $n ?>><?= e(option_get($pdo, 'place_' . $n . '_kicker', '')) ?></p>
                            <p class="text-[11px] font-bold leading-tight" data-preview-place-t<?= $n ?>><?= e(option_get($pdo, 'place_' . $n . '_title', '')) ?></p>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        <p class="mt-3 text-xs text-slate-400">Sıra için <a class="text-[#2271b1] hover:underline" href="index.php?page=home">Ana sayfa</a> listesini sürükleyin.</p>
    </aside>
</form>
<script>
(function () {
    var form = document.querySelector('form[data-preview="places"]');
    if (!form) return;
    function set(sel, val) {
        var el = document.querySelector(sel);
        if (!el) return;
        el.textContent = val || '';
        el.style.display = val ? '' : 'none';
    }
    function g(name) {
        var el = form.querySelector('[name="' + name + '"]');
        return el ? (el.value || '').trim() : '';
    }
    function run() {
        set('[data-preview-place-kicker]', g('place_kicker'));
        set('[data-preview-place-heading]', g('place_heading'));
        set('[data-preview-place-k1]', g('place_1_kicker'));
        set('[data-preview-place-t1]', g('place_1_title'));
        set('[data-preview-place-k2]', g('place_2_kicker'));
        set('[data-preview-place-t2]', g('place_2_title'));
    }
    form.addEventListener('input', run);
    run();
})();
</script>
