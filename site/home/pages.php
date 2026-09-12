<?php
declare(strict_types=1);
if (!isset($pdo) || !($pdo instanceof PDO) || option_get($pdo, 'pages_show_home', '1') !== '1') {
    return;
}
if (empty($homePages)) {
    return;
}
?>
<section class="mb-12">
    <div class="flex items-end justify-between gap-3 mb-5">
        <h2 class="text-2xl font-semibold text-slate-900"><?= e($homePagesHeading !== '' ? $homePagesHeading : 'Sayfalar') ?></h2>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($homePages as $hp): ?>
            <a href="<?= e(page_permalink($hp['slug'])) ?>" class="cms-reveal cms-card group bg-white border border-slate-200 rounded-xl overflow-hidden">
                <?php if (!empty($hp['featured_image'])): ?>
                    <img src="<?= e(media_src($hp['featured_image'])) ?>" alt="" class="h-40 w-full object-cover bg-slate-100">
                <?php else: ?>
                    <div class="h-28 bg-gradient-to-br from-slate-800 to-sky-700"></div>
                <?php endif; ?>
                <div class="p-4">
                    <h3 class="font-semibold text-slate-900 group-hover:text-sky-700"><?= e((string) $hp['title']) ?></h3>
                    <?php if (!empty($hp['excerpt'])): ?>
                        <p class="mt-2 text-sm text-slate-600"><?= e((string) $hp['excerpt']) ?></p>
                    <?php endif; ?>
                    <span class="inline-block mt-3 text-sm font-medium text-sky-700">İncele</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
