<?php
declare(strict_types=1);
if (!isset($siteTitle)) {
    return;
}
$navCategories = isset($navCategories) && is_array($navCategories) ? $navCategories : [];
$sidebarRecent = isset($sidebarRecent) && is_array($sidebarRecent) ? $sidebarRecent : [];
$searchQuery = isset($searchQuery) ? (string) $searchQuery : '';
?>
<aside class="space-y-6">
    <form action="<?= e(public_url('ara')) ?>" method="get" class="sm:hidden bg-white border border-slate-200 rounded-xl p-4">
        <label class="block text-sm font-semibold text-slate-900 mb-2" for="mobile-search">Ara</label>
        <input id="mobile-search" type="search" name="q" value="<?= e($searchQuery) ?>" placeholder="Yazılarda ara…"
               class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100">
    </form>

    <?php if ($navCategories): ?>
        <section class="bg-white border border-slate-200 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-slate-900">Kategoriler</h2>
            <ul class="mt-3 space-y-2 text-sm">
                <?php foreach ($navCategories as $cat): ?>
                    <li class="flex items-center justify-between gap-3">
                        <a class="text-slate-700 hover:text-cyan-700" href="<?= e(category_permalink($cat['slug'])) ?>"><?= e((string) $cat['name']) ?></a>
                        <span class="text-xs text-slate-400"><?= (int) $cat['post_count'] ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if ($sidebarRecent): ?>
        <section class="bg-white border border-slate-200 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-slate-900">Son yazılar</h2>
            <ul class="mt-3 space-y-3">
                <?php foreach ($sidebarRecent as $item): ?>
                    <li>
                        <a class="text-sm font-medium text-slate-800 hover:text-cyan-700" href="<?= e(post_permalink($item['slug'])) ?>">
                            <?= e((string) $item['title']) ?>
                        </a>
                        <p class="text-xs text-slate-400 mt-0.5"><?= e(format_datetime((string) $item['created_at'])) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section class="bg-ink text-white rounded-xl p-5">
        <p class="text-xs uppercase tracking-wider text-cyan-300 font-semibold">RSS</p>
        <p class="mt-2 text-sm text-slate-300">Yeni yazılar yayınlanınca okuyucunuza düşsün.</p>
        <a class="inline-block mt-4 text-sm font-medium text-cyan-300 hover:text-white" href="<?= e(public_url('rss.php')) ?>">Beslemeyi aç →</a>
    </section>
</aside>
