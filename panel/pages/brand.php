<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'brand') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

$fonts = cms_fonts();
$presets = cms_brand_presets();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $primary = hex_color($_POST['brand_primary'] ?? '', '#0ea5e9');
    $dark = hex_color($_POST['brand_dark'] ?? '', '#0369a1');
    $fontId = option_pick($_POST['brand_font'] ?? 'system', array_keys($fonts), 'system');
    option_set($pdo, 'brand_primary', $primary);
    option_set($pdo, 'brand_dark', $dark);
    option_set($pdo, 'brand_font', $fontId);
    if (isset($_POST['sync_header_accent'])) {
        option_set($pdo, 'header_accent', $primary);
    }
    cms_audit($pdo, 'update', 'brand', 0, 'Marka renkleri');
    flash_set('success', 'Marka görünümü kaydedildi.');
    redirect('index.php?page=brand');
}

$brand = cms_brand($pdo);
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Marka</h1>
    <p class="mt-1 text-sm text-slate-500">Buton, link, sayaç ve CTA rengi. Yazı tipi tüm ziyaretçi sayfalarına uygulanır. Header arka planı ayrı durur.</p>
</div>

<form method="post" class="grid grid-cols-1 xl:grid-cols-[minmax(0,28rem)_1fr] gap-6 items-start" data-brand-form>
    <?= csrf_field() ?>
    <div class="space-y-4">
        <div class="bg-white rounded-lg border border-slate-200 p-5 space-y-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Hazır palet</p>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($presets as $preset): ?>
                    <button type="button" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 hover:border-slate-300" data-preset="<?= e($preset['primary']) ?>" data-preset-dark="<?= e($preset['dark']) ?>">
                        <span class="h-4 w-4 rounded-full border border-black/10" style="background:<?= e($preset['primary']) ?>"></span>
                        <?= e($preset['name']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <label class="text-sm text-slate-700">Ana renk
                    <input type="color" name="brand_primary" value="<?= e($brand['primary']) ?>" class="mt-1 h-10 w-full border border-slate-200 rounded" data-brand-primary>
                </label>
                <label class="text-sm text-slate-700">Link / koyu
                    <input type="color" name="brand_dark" value="<?= e($brand['dark']) ?>" class="mt-1 h-10 w-full border border-slate-200 rounded" data-brand-dark>
                </label>
            </div>
            <p class="text-xs text-slate-400">Ana renk buton, üst şerit, CTA ve sayaç çizgisi. Koyu renk “Devamını oku”, kicker ve hover.</p>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Yazı tipi</label>
                <select name="brand_font" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm" data-brand-font>
                    <?php foreach ($fonts as $id => $font): ?>
                        <option value="<?= e($id) ?>" <?= $brand['font_id'] === $id ? 'selected' : '' ?>><?= e($font['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="mt-1 text-xs text-slate-400">Sistem, ziyaretçinin cihaz yazı tipini kullanır. Diğerleri Google Fonts CDN.</p>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="sync_header_accent" value="1">
                Header menü vurgusunu da ana renge çek
            </label>
            <button class="w-full rounded-md bg-[#2271b1] px-3 py-2 text-sm font-semibold text-white hover:bg-blue-800" type="submit">Markayı kaydet</button>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 p-5 min-w-0">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-4">Önizleme</p>
        <div class="rounded-2xl border border-slate-200 overflow-hidden" data-brand-preview style="font-family:<?= htmlspecialchars($brand['font'], ENT_QUOTES, 'UTF-8') ?>;--cms-brand:<?= e($brand['primary']) ?>;--cms-brand-dark:<?= e($brand['dark']) ?>;--cms-brand-ink:<?= e($brand['ink']) ?>">
            <div class="px-5 py-3 text-sm font-semibold" data-preview-bar style="background:<?= e($brand['primary']) ?>;color:<?= e($brand['ink']) ?>">Yeni projeler için iletişime geçin.</div>
            <div class="p-6 space-y-4 bg-slate-50">
                <p class="text-[11px] font-bold uppercase tracking-[0.18em]" data-preview-kicker style="color:<?= e($brand['dark']) ?>">Çalışma şeklimiz</p>
                <h2 class="text-2xl font-extrabold text-slate-900 leading-tight" data-preview-title>Sade, canlı ve sizin renginize göre</h2>
                <p class="text-sm text-slate-600 leading-relaxed">Buton ve linkler seçtiğiniz paleti kullanır. Teslimatta CSS kırmanıza gerek kalmaz.</p>
                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex rounded-md px-4 py-2 text-sm font-semibold" data-preview-btn style="background:<?= e($brand['primary']) ?>;color:<?= e($brand['ink']) ?>">İletişime geç</span>
                    <a class="text-sm font-medium hover:underline" href="#" data-preview-link style="color:<?= e($brand['dark']) ?>">Devamını oku</a>
                </div>
                <div class="rounded-xl bg-slate-900 text-white px-5 py-4">
                    <p class="text-3xl font-extrabold tabular-nums leading-none" data-preview-num>128<span class="text-[0.5em] font-bold ml-0.5" data-preview-sfx>+</span></p>
                    <span class="mt-3 block h-0.5 w-10 rounded-full" data-preview-statbar style="background:<?= e($brand['primary']) ?>"></span>
                    <p class="mt-2 text-sm text-slate-300">Tamamlanan iş</p>
                </div>
            </div>
        </div>
    </div>
</form>
<script>
(function () {
    var fonts = <?= json_encode($fonts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var primary = document.querySelector('[data-brand-primary]');
    var dark = document.querySelector('[data-brand-dark]');
    var fontSel = document.querySelector('[data-brand-font]');
    var preview = document.querySelector('[data-brand-preview]');
    var darkTouched = false;
    if (!primary || !dark || !fontSel || !preview) return;

    function rgbOf(hex) {
        hex = (hex || '').replace('#', '');
        return [parseInt(hex.slice(0, 2), 16), parseInt(hex.slice(2, 4), 16), parseInt(hex.slice(4, 6), 16)];
    }
    function onColor(hex) {
        var rgb = rgbOf(hex);
        var luma = (0.2126 * rgb[0] + 0.7152 * rgb[1] + 0.0722 * rgb[2]) / 255;
        return luma > 0.62 ? '#0f172a' : '#ffffff';
    }
    function darken(hex) {
        var rgb = rgbOf(hex);
        return '#' + rgb.map(function (c) {
            var n = Math.round(c * 0.68);
            return ('0' + Math.max(0, Math.min(255, n)).toString(16)).slice(-2);
        }).join('');
    }
    function loadGoogle(q) {
        var id = 'cms-brand-font-preview';
        var el = document.getElementById(id);
        if (!q) {
            if (el) el.remove();
            return;
        }
        if (!el) {
            el = document.createElement('link');
            el.id = id;
            el.rel = 'stylesheet';
            document.head.appendChild(el);
        }
        el.href = 'https://fonts.googleapis.com/css2?family=' + q + '&display=swap';
    }
    function apply() {
        var p = primary.value;
        var d = dark.value;
        var ink = onColor(p);
        var font = fonts[fontSel.value] || fonts.system;
        preview.style.setProperty('--cms-brand', p);
        preview.style.setProperty('--cms-brand-dark', d);
        preview.style.setProperty('--cms-brand-ink', ink);
        preview.style.fontFamily = font.css;
        preview.querySelector('[data-preview-bar]').style.background = p;
        preview.querySelector('[data-preview-bar]').style.color = ink;
        preview.querySelector('[data-preview-kicker]').style.color = d;
        preview.querySelector('[data-preview-btn]').style.background = p;
        preview.querySelector('[data-preview-btn]').style.color = ink;
        preview.querySelector('[data-preview-link]').style.color = d;
        preview.querySelector('[data-preview-statbar]').style.background = p;
        preview.querySelector('[data-preview-sfx]').style.color = p;
        loadGoogle(font.google || '');
    }
    primary.addEventListener('input', function () {
        if (!darkTouched) dark.value = darken(primary.value);
        apply();
    });
    dark.addEventListener('input', function () {
        darkTouched = true;
        apply();
    });
    fontSel.addEventListener('change', apply);
    document.querySelectorAll('[data-preset]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            primary.value = btn.getAttribute('data-preset');
            dark.value = btn.getAttribute('data-preset-dark');
            darkTouched = true;
            apply();
        });
    });
    apply();
})();
</script>
