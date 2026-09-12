<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'spot') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    option_set($pdo, 'spot_show_home', isset($_POST['spot_show_home']) ? '1' : '0');
    option_set($pdo, 'spot_kicker', trim((string) ($_POST['spot_kicker'] ?? '')));
    option_set($pdo, 'spot_heading', trim((string) ($_POST['spot_heading'] ?? '')));
    option_set($pdo, 'spot_text', trim((string) ($_POST['spot_text'] ?? '')));
    option_set($pdo, 'spot_badge_kicker', trim((string) ($_POST['spot_badge_kicker'] ?? '')));
    option_set($pdo, 'spot_badge_title', trim((string) ($_POST['spot_badge_title'] ?? '')));
    option_set($pdo, 'spot_badge_kicker_2', trim((string) ($_POST['spot_badge_kicker_2'] ?? '')));
    option_set($pdo, 'spot_badge_title_2', trim((string) ($_POST['spot_badge_title_2'] ?? '')));
    option_set($pdo, 'spot_1_title', trim((string) ($_POST['spot_1_title'] ?? '')));
    option_set($pdo, 'spot_1_text', trim((string) ($_POST['spot_1_text'] ?? '')));
    option_set($pdo, 'spot_2_title', trim((string) ($_POST['spot_2_title'] ?? '')));
    option_set($pdo, 'spot_2_text', trim((string) ($_POST['spot_2_text'] ?? '')));
    option_set($pdo, 'spot_3_title', trim((string) ($_POST['spot_3_title'] ?? '')));
    option_set($pdo, 'spot_3_text', trim((string) ($_POST['spot_3_text'] ?? '')));
    $up1 = handle_image_upload('spot_image');
    if ($up1) {
        option_set($pdo, 'spot_image', $up1);
    }
    $up2 = handle_image_upload('spot_image_2');
    if ($up2) {
        option_set($pdo, 'spot_image_2', $up2);
    }
    if (isset($_POST['remove_spot_image'])) {
        option_set($pdo, 'spot_image', '');
    }
    if (isset($_POST['remove_spot_image_2'])) {
        option_set($pdo, 'spot_image_2', '');
    }
    flash_set('success', 'Vitrin kaydedildi.');
    redirect('index.php?page=spot');
}

$kicker = option_get($pdo, 'spot_kicker', 'Çalışma şeklimiz');
$heading = option_get($pdo, 'spot_heading', 'Sade, canlı ve panelden yönetilen bir site deneyimi');
$lead = option_get($pdo, 'spot_text', '');
$img1 = option_get($pdo, 'spot_image', '');
$img2 = option_get($pdo, 'spot_image_2', '');
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Vitrin</h1>
    <p class="mt-1 text-sm text-slate-500">Ana sayfadaki fotoğraf + üç madde. Soldaki görsel, sağdaki yazılar buradan değişir.</p>
</div>
<form method="post" enctype="multipart/form-data" class="grid grid-cols-1 xl:grid-cols-[minmax(0,28rem)_1fr] gap-6 items-start" data-preview="spot">
    <?= csrf_field() ?>
    <div class="space-y-4">
        <div class="bg-white rounded-lg border border-slate-200 p-5 space-y-3">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="spot_show_home" value="1" <?= option_get($pdo, 'spot_show_home', '1') === '1' ? 'checked' : '' ?>>
                Ana sayfada göster
            </label>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Küçük başlık</label>
                <input name="spot_kicker" value="<?= e($kicker) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Başlık</label>
                <input name="spot_heading" value="<?= e($heading) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Yazı</label>
                <textarea name="spot_text" rows="4" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"><?= e($lead) ?></textarea>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-slate-200 p-5 space-y-4">
            <h2 class="text-sm font-semibold text-slate-900">Görseller</h2>
            <p class="text-xs text-slate-500">İki foto varsa sitede noktalarla döner. Rozet fotoğrafın sol altındadır.</p>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Foto 1</p>
                    <?php if ($img1 !== ''): ?>
                        <img src="<?= e(media_src($img1)) ?>" alt="" class="mb-2 h-28 w-full rounded-lg object-cover border border-slate-200">
                        <label class="mb-2 flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" name="remove_spot_image" value="1"> Bu görseli kaldır</label>
                    <?php endif; ?>
                    <input type="file" name="spot_image" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm">
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <input name="spot_badge_kicker" value="<?= e(option_get($pdo, 'spot_badge_kicker', 'Stüdyodan')) ?>" placeholder="Rozet üst yazı" class="rounded-md border border-slate-200 px-3 py-2 text-sm">
                        <input name="spot_badge_title" value="<?= e(option_get($pdo, 'spot_badge_title', 'Tasarım masası')) ?>" placeholder="Rozet başlık" class="rounded-md border border-slate-200 px-3 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Foto 2 (isteğe bağlı)</p>
                    <?php if ($img2 !== ''): ?>
                        <img src="<?= e(media_src($img2)) ?>" alt="" class="mb-2 h-28 w-full rounded-lg object-cover border border-slate-200">
                        <label class="mb-2 flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" name="remove_spot_image_2" value="1"> Bu görseli kaldır</label>
                    <?php endif; ?>
                    <input type="file" name="spot_image_2" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm">
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <input name="spot_badge_kicker_2" value="<?= e(option_get($pdo, 'spot_badge_kicker_2', 'Ekipten')) ?>" placeholder="Rozet üst yazı" class="rounded-md border border-slate-200 px-3 py-2 text-sm">
                        <input name="spot_badge_title_2" value="<?= e(option_get($pdo, 'spot_badge_title_2', 'Planlama toplantısı')) ?>" placeholder="Rozet başlık" class="rounded-md border border-slate-200 px-3 py-2 text-sm">
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-slate-200 p-5 space-y-3">
            <h2 class="text-sm font-semibold text-slate-900">Üç madde</h2>
            <?php for ($n = 1; $n <= 3; $n++): ?>
                <div class="rounded-md border border-slate-100 bg-slate-50 p-3 space-y-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Madde <?= $n ?></p>
                    <input name="spot_<?= $n ?>_title" value="<?= e(option_get($pdo, 'spot_' . $n . '_title', '')) ?>" placeholder="Başlık" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm bg-white">
                    <textarea name="spot_<?= $n ?>_text" rows="2" placeholder="Kısa yazı" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm bg-white"><?= e(option_get($pdo, 'spot_' . $n . '_text', '')) ?></textarea>
                </div>
            <?php endfor; ?>
        </div>

        <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Vitrini kaydet</button>
    </div>

    <aside class="xl:sticky xl:top-20">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Sitede böyle durur</p>
        <div class="rounded-2xl overflow-hidden border border-slate-200 bg-[#eef6fb] p-4" data-preview-off="spot">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                <div class="relative rounded-2xl overflow-hidden bg-slate-200 min-h-[140px]">
                    <?php if ($img1 !== ''): ?>
                        <img src="<?= e(media_src($img1)) ?>" alt="" class="h-40 w-full object-cover">
                    <?php else: ?>
                        <div class="h-40 bg-slate-300"></div>
                    <?php endif; ?>
                    <div class="absolute left-2 bottom-2 rounded-xl bg-white px-2 py-1.5 shadow-sm">
                        <p class="text-[9px] uppercase tracking-wide text-slate-400" data-preview-spot-bk><?= e(option_get($pdo, 'spot_badge_kicker', 'Stüdyodan')) ?></p>
                        <p class="text-[11px] font-semibold text-slate-900" data-preview-spot-bt><?= e(option_get($pdo, 'spot_badge_title', 'Tasarım masası')) ?></p>
                    </div>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wide text-sky-600" data-preview-spot-kicker><?= e($kicker) ?></p>
                    <p class="mt-1 text-sm font-bold text-slate-900 leading-snug" data-preview-spot-heading><?= e($heading) ?></p>
                    <p class="mt-2 text-[11px] text-slate-500 leading-relaxed" data-preview-spot-text><?= e($lead) ?></p>
                    <div class="mt-3 space-y-2">
                        <?php for ($n = 1; $n <= 3; $n++): ?>
                            <div>
                                <p class="text-[11px] font-semibold text-slate-800" data-preview-spot-t<?= $n ?>><?= e(option_get($pdo, 'spot_' . $n . '_title', '')) ?></p>
                                <p class="text-[10px] text-slate-500" data-preview-spot-d<?= $n ?>><?= e(option_get($pdo, 'spot_' . $n . '_text', '')) ?></p>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
        <p class="mt-3 text-xs text-slate-400">Kaydettikten sonra <a class="text-[#2271b1] hover:underline" href="<?= e(public_url()) ?>" target="_blank" rel="noopener noreferrer">ana sayfada</a> görünür.</p>
    </aside>
</form>
<script>
(function () {
    var form = document.querySelector('form[data-preview="spot"]');
    if (!form) return;
    function set(sel, val) {
        var el = document.querySelector(sel);
        if (!el) return;
        el.textContent = val || '';
        el.style.display = val ? '' : 'none';
    }
    function run() {
        var g = function (name) {
            var el = form.querySelector('[name="' + name + '"]');
            return el ? (el.value || '').trim() : '';
        };
        set('[data-preview-spot-kicker]', g('spot_kicker'));
        set('[data-preview-spot-heading]', g('spot_heading'));
        set('[data-preview-spot-text]', g('spot_text'));
        set('[data-preview-spot-bk]', g('spot_badge_kicker'));
        set('[data-preview-spot-bt]', g('spot_badge_title'));
        set('[data-preview-spot-t1]', g('spot_1_title'));
        set('[data-preview-spot-d1]', g('spot_1_text'));
        set('[data-preview-spot-t2]', g('spot_2_title'));
        set('[data-preview-spot-d2]', g('spot_2_text'));
        set('[data-preview-spot-t3]', g('spot_3_title'));
        set('[data-preview-spot-d3]', g('spot_3_text'));
    }
    form.addEventListener('input', run);
    run();
})();
</script>
