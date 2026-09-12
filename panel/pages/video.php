<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'video') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    option_set($pdo, 'film_show_home', isset($_POST['film_show_home']) ? '1' : '0');
    option_set($pdo, 'film_kicker', trim((string) ($_POST['film_kicker'] ?? '')));
    option_set($pdo, 'film_heading', trim((string) ($_POST['film_heading'] ?? '')));
    option_set($pdo, 'film_text', trim((string) ($_POST['film_text'] ?? '')));
    option_set($pdo, 'film_overlay', trim((string) ($_POST['film_overlay'] ?? '')));
    option_set($pdo, 'film_url', trim((string) ($_POST['film_url'] ?? '')));
    $up = handle_image_upload('film_poster');
    if ($up) {
        option_set($pdo, 'film_poster', $up);
    }
    if (isset($_POST['remove_film_poster'])) {
        option_set($pdo, 'film_poster', '');
    }
    flash_set('success', 'Video bölümü kaydedildi.');
    redirect('index.php?page=video');
}

$kicker = option_get($pdo, 'film_kicker', 'Tanıtım');
$heading = option_get($pdo, 'film_heading', 'Nasıl çalıştığımızı izleyin');
$lead = option_get($pdo, 'film_text', '');
$overlay = option_get($pdo, 'film_overlay', '');
$url = option_get($pdo, 'film_url', '');
$poster = cms_film_poster($pdo);
$src = cms_film_source($url);
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Video</h1>
    <p class="mt-1 text-sm text-slate-500">Ana sayfadaki büyük kapak: üstte başlık, altta oynat butonlu görsel. YouTube veya mp4 linki yapıştırın.</p>
</div>
<form method="post" enctype="multipart/form-data" class="grid grid-cols-1 xl:grid-cols-[minmax(0,28rem)_1fr] gap-6 items-start" data-preview="film">
    <?= csrf_field() ?>
    <div class="space-y-4">
        <div class="bg-white rounded-lg border border-slate-200 p-5 space-y-3">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="film_show_home" value="1" <?= option_get($pdo, 'film_show_home', '1') === '1' ? 'checked' : '' ?>>
                Ana sayfada göster
            </label>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Küçük başlık</label>
                <input name="film_kicker" value="<?= e($kicker) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Başlık</label>
                <input name="film_heading" value="<?= e($heading) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Yazı</label>
                <textarea name="film_text" rows="3" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"><?= e($lead) ?></textarea>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-slate-200 p-5 space-y-3">
            <h2 class="text-sm font-semibold text-slate-900">Video</h2>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">YouTube veya mp4 linki</label>
                <input name="film_url" value="<?= e($url) ?>" placeholder="https://www.youtube.com/watch?v=…" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                <p class="mt-1 text-[11px] text-slate-400">Link yoksa sadece kapak görünür, oynat butonu çıkmaz.</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Kapak görseli</label>
                <?php if ($poster !== ''): ?>
                    <img src="<?= e($poster) ?>" alt="" class="mb-2 h-32 w-full rounded-lg object-cover border border-slate-200">
                    <?php if (trim(option_get($pdo, 'film_poster', '')) !== ''): ?>
                        <label class="mb-2 flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" name="remove_film_poster" value="1"> Bu görseli kaldır (YouTube kapağı kullanılır)</label>
                    <?php endif; ?>
                <?php endif; ?>
                <input type="file" name="film_poster" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Kapak yazısı</label>
                <textarea name="film_overlay" rows="2" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"><?= e($overlay) ?></textarea>
                <p class="mt-1 text-[11px] text-slate-400">Görselin sol altındaki büyük yazı. Satır kırmak için Enter.</p>
            </div>
        </div>

        <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Videoyu kaydet</button>
    </div>

    <aside class="xl:sticky xl:top-20">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Sitede böyle durur</p>
        <div class="rounded-2xl overflow-hidden border border-slate-200 bg-white p-5" data-preview-off="film">
            <div class="text-center max-w-md mx-auto">
                <p class="text-[10px] font-bold uppercase tracking-wide text-sky-600" data-preview-film-kicker><?= e($kicker) ?></p>
                <p class="mt-1 text-lg font-bold text-slate-900 leading-snug" data-preview-film-heading><?= e($heading) ?></p>
                <p class="mt-2 text-[11px] text-slate-500 leading-relaxed" data-preview-film-text><?= e($lead) ?></p>
            </div>
            <div class="relative mt-4 overflow-hidden rounded-2xl bg-slate-800 min-h-[160px]">
                <?php if ($poster !== ''): ?>
                    <img src="<?= e($poster) ?>" alt="" class="h-44 w-full object-cover">
                <?php else: ?>
                    <div class="h-44 bg-slate-700"></div>
                <?php endif; ?>
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/70 via-slate-900/10 to-transparent"></div>
                <p class="absolute left-3 bottom-3 text-white text-sm font-extrabold leading-tight whitespace-pre-line" data-preview-film-overlay><?= e($overlay) ?></p>
                <?php if ($src['src'] !== ''): ?>
                    <span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 inline-flex h-12 w-12 items-center justify-center rounded-full bg-white shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="ml-0.5 h-5 w-5 fill-slate-900"><path d="M8 5.14v13.72L19 12 8 5.14Z"/></svg>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <p class="mt-3 text-xs text-slate-400">Kaydettikten sonra <a class="text-[#2271b1] hover:underline" href="<?= e(public_url()) ?>" target="_blank" rel="noopener noreferrer">ana sayfada</a> görünür.</p>
    </aside>
</form>
<script>
(function () {
    var form = document.querySelector('form[data-preview="film"]');
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
        set('[data-preview-film-kicker]', g('film_kicker'));
        set('[data-preview-film-heading]', g('film_heading'));
        set('[data-preview-film-text]', g('film_text'));
        set('[data-preview-film-overlay]', g('film_overlay'));
    }
    form.addEventListener('input', run);
    run();
})();
</script>
