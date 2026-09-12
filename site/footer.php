<?php
declare(strict_types=1);
if (!isset($siteTitle)) {
    exit;
}
$navPages = isset($navPages) && is_array($navPages) ? $navPages : [];
$navCategories = isset($navCategories) && is_array($navCategories) ? $navCategories : [];
?>
</main>
<footer class="border-t border-slate-200 bg-white mt-4">
    <div class="max-w-6xl mx-auto px-4 py-10 grid grid-cols-1 sm:grid-cols-3 gap-8 text-sm">
        <div>
            <p class="font-semibold text-slate-900"><?= e($siteTitle) ?></p>
            <p class="mt-2 text-slate-500"><?= e($siteTagline !== '' ? $siteTagline : 'Yazılım notları, rehberler ve günlük.') ?></p>
        </div>
        <div>
            <p class="font-semibold text-slate-900">Sayfalar</p>
            <ul class="mt-2 space-y-1 text-slate-500">
                <li><a class="hover:text-slate-800" href="<?= e(public_url()) ?>">Yazılar</a></li>
                <?php foreach ($navPages as $nav): ?>
                    <li><a class="hover:text-slate-800" href="<?= e(page_permalink($nav['slug'])) ?>"><?= e((string) $nav['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <p class="font-semibold text-slate-900">Kategoriler</p>
            <ul class="mt-2 space-y-1 text-slate-500">
                <?php if (!$navCategories): ?>
                    <li>Henüz kategori yok.</li>
                <?php else: ?>
                    <?php foreach ($navCategories as $cat): ?>
                        <li><a class="hover:text-slate-800" href="<?= e(category_permalink($cat['slug'])) ?>"><?= e((string) $cat['name']) ?></a></li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="border-t border-slate-100">
        <div class="max-w-6xl mx-auto px-4 py-5 text-xs text-slate-400 flex flex-col sm:flex-row gap-2 sm:items-center sm:justify-between">
            <p>&copy; <?= date('Y') ?> <?= e($siteTitle) ?></p>
            <a class="hover:text-slate-700" href="<?= e(public_url('rss.php')) ?>">RSS</a>
        </div>
    </div>
</footer>
<?php require __DIR__ . '/a11y-widget.php'; ?>
</body>
</html>
