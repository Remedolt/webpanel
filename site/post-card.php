<?php
declare(strict_types=1);
if (!isset($post) || !is_array($post)) {
    return;
}
$cats = isset($post['categories']) && is_array($post['categories']) ? $post['categories'] : [];
$excerpt = trim((string) ($post['excerpt'] ?? ''));
if ($excerpt === '' && !empty($post['content'])) {
    $excerpt = excerpt_plain((string) $post['content'], 180);
}
?>
<article class="bg-white border border-slate-200 rounded-xl overflow-hidden hover:border-cyan-300 transition-colors">
    <?php if (!empty($post['featured_image'])): ?>
        <a href="<?= e(post_permalink($post['slug'])) ?>">
            <img src="<?= e(media_src($post['featured_image'])) ?>" alt="" class="h-48 w-full object-cover bg-slate-100">
        </a>
    <?php endif; ?>
    <div class="p-5">
        <?php if ($cats): ?>
            <div class="flex flex-wrap gap-1.5 mb-2">
                <?php foreach ($cats as $cat): ?>
                    <a href="<?= e(category_permalink($cat['slug'])) ?>" class="text-[11px] uppercase tracking-wide font-semibold text-cyan-700 hover:underline"><?= e((string) $cat['name']) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <p class="text-xs text-slate-400"><?= e(format_datetime((string) $post['created_at'])) ?><?php if (!empty($post['display_name'])): ?> · <?= e((string) $post['display_name']) ?><?php endif; ?></p>
        <h2 class="mt-2 text-xl font-semibold leading-snug">
            <a class="hover:text-cyan-700" href="<?= e(post_permalink($post['slug'])) ?>"><?= e((string) $post['title']) ?></a>
        </h2>
        <?php if ($excerpt !== ''): ?>
            <p class="mt-2 text-sm text-slate-600"><?= e($excerpt) ?></p>
        <?php endif; ?>
        <a class="inline-block mt-4 text-sm font-medium text-cyan-700 hover:underline" href="<?= e(post_permalink($post['slug'])) ?>">Devamını oku</a>
    </div>
</article>
