<?php
declare(strict_types=1);
if (!isset($pdo) || !($pdo instanceof PDO) || option_get($pdo, 'posts_show_home', '1') !== '1') {
    return;
}
$postsLimit = (int) option_get($pdo, 'posts_per_page', '10');
if ($postsLimit < 5) {
    $postsLimit = 5;
}
if ($postsLimit > 50) {
    $postsLimit = 50;
}
$posts = $pdo->query(
    "SELECT p.title, p.slug, p.excerpt, p.featured_image, p.created_at, u.display_name
     FROM posts p
     INNER JOIN users u ON u.id = p.author_id
     WHERE p.status = 'publish'
     ORDER BY p.created_at DESC
     LIMIT " . $postsLimit
)->fetchAll();
?>
<div class="mb-5 flex items-end justify-between gap-3">
    <h2 class="text-2xl font-semibold text-slate-900">Yazılar</h2>
    <a class="text-sm font-medium text-sky-700 hover:underline" href="<?= e(posts_list_permalink()) ?>">Tümü</a>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php if (!$posts): ?>
        <div class="md:col-span-2 bg-white border border-slate-200 rounded-xl p-8 text-slate-500">
            Henüz yayımlanmış yazı yok. Panelden bir yazı oluşturup <strong>Yayımla</strong> deyin.
        </div>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <article class="cms-reveal cms-card bg-white border border-slate-200 rounded-xl overflow-hidden">
                <?php if (!empty($post['featured_image'])): ?>
                    <a href="<?= e(post_permalink($post['slug'])) ?>">
                        <img src="<?= e(media_src($post['featured_image'])) ?>" alt="" class="h-48 w-full object-cover bg-slate-100">
                    </a>
                <?php endif; ?>
                <div class="p-5">
                    <p class="text-xs text-slate-400"><?= e(format_datetime((string) $post['created_at'])) ?> · <?= e((string) $post['display_name']) ?></p>
                    <h2 class="mt-2 text-xl font-semibold">
                        <a class="hover:text-sky-700" href="<?= e(post_permalink($post['slug'])) ?>"><?= e((string) $post['title']) ?></a>
                    </h2>
                    <?php if (!empty($post['excerpt'])): ?>
                        <p class="mt-2 text-sm text-slate-600"><?= e((string) $post['excerpt']) ?></p>
                    <?php endif; ?>
                    <a class="inline-block mt-4 text-sm font-medium text-sky-700 hover:underline" href="<?= e(post_permalink($post['slug'])) ?>">Devamını oku</a>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
