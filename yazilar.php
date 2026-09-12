<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';

$perPage = (int) option_get($pdo, 'posts_per_page', '10');
if ($perPage < 5) {
    $perPage = 5;
}
if ($perPage > 50) {
    $perPage = 50;
}
$pageNo = (int) ($_GET['p'] ?? 1);
if ($pageNo < 1) {
    $pageNo = 1;
}
$total = (int) $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'publish'")->fetchColumn();
$pages = (int) ceil($total / $perPage);
if ($pages < 1) {
    $pages = 1;
}
if ($pageNo > $pages) {
    $pageNo = $pages;
}
$offset = ($pageNo - 1) * $perPage;
$stmt = $pdo->prepare(
    "SELECT p.title, p.slug, p.excerpt, p.featured_image, p.created_at, p.views, u.display_name
     FROM posts p
     INNER JOIN users u ON u.id = p.author_id
     WHERE p.status = 'publish'
     ORDER BY p.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute();
$posts = $stmt->fetchAll();
$categories = [];
try {
    $categories = $pdo->query('SELECT name, slug FROM categories ORDER BY name ASC')->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}
$popular = [];
try {
    $popular = $pdo->query(
        "SELECT title, slug, views FROM posts WHERE status = 'publish' ORDER BY views DESC, created_at DESC LIMIT 6"
    )->fetchAll();
} catch (PDOException $e) {
    $popular = [];
}
$docTitle = 'Yazılar — ' . $siteTitle;
require __DIR__ . '/site/header.php';
?>
<div class="flex items-end justify-between gap-3 mb-6">
    <div>
        <?php cms_crumbs(array(
            array('label' => 'Ana sayfa', 'url' => public_url()),
            array('label' => 'Yazılar', 'url' => ''),
        )); ?>
        <p class="text-xs uppercase tracking-wide text-slate-400">Blog</p>
        <h1 class="mt-1 text-3xl font-bold text-slate-900">Yazılar</h1>
    </div>
    <p class="text-sm text-slate-400"><?= (int) $total ?> yazı</p>
</div>
<?php if ($categories): ?>
    <nav class="mb-6 flex flex-wrap gap-2">
        <a class="rounded-full bg-slate-900 px-3 py-1 text-xs font-medium text-white" href="<?= e(posts_list_permalink()) ?>">Tümü</a>
        <?php foreach ($categories as $cat): ?>
            <a class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-700 hover:border-sky-300" href="<?= e(category_permalink($cat['slug'])) ?>"><?= e((string) $cat['name']) ?></a>
        <?php endforeach; ?>
        <a class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-700 hover:border-sky-300" href="<?= e(rss_permalink()) ?>">RSS</a>
    </nav>
<?php endif; ?>
<div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_16rem] gap-8 items-start">
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php if (!$posts): ?>
        <div class="md:col-span-2 bg-white border border-slate-200 rounded-xl p-8 text-slate-500">Henüz yayımlanmış yazı yok.</div>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <article class="cms-reveal is-on cms-card bg-white border border-slate-200 rounded-xl overflow-hidden">
                <?php if (!empty($post['featured_image'])): ?>
                    <a href="<?= e(post_permalink($post['slug'])) ?>">
                        <img src="<?= e(media_src($post['featured_image'])) ?>" alt="" class="h-48 w-full object-cover bg-slate-100">
                    </a>
                <?php endif; ?>
                <div class="p-5">
                    <p class="text-xs text-slate-400"><?= e(format_datetime((string) $post['created_at'])) ?> · <?= e((string) $post['display_name']) ?></p>
                    <h2 class="mt-2 text-xl font-semibold"><a class="hover:text-sky-700" href="<?= e(post_permalink($post['slug'])) ?>"><?= e((string) $post['title']) ?></a></h2>
                    <?php if (!empty($post['excerpt'])): ?>
                        <p class="mt-2 text-sm text-slate-600"><?= e((string) $post['excerpt']) ?></p>
                    <?php endif; ?>
                    <a class="inline-block mt-4 text-sm font-medium text-sky-700 hover:underline" href="<?= e(post_permalink($post['slug'])) ?>">Devamını oku</a>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php if ($popular): ?>
    <aside class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="text-sm font-semibold text-slate-900">Çok okunanlar</h2>
        <ol class="mt-3 space-y-3">
            <?php foreach ($popular as $i => $hit): ?>
                <li>
                    <a class="text-sm font-medium text-slate-800 hover:text-sky-700" href="<?= e(post_permalink($hit['slug'])) ?>"><?= e((string) $hit['title']) ?></a>
                    <p class="text-[11px] text-slate-400"><?= number_format((int) ($hit['views'] ?? 0), 0, ',', '.') ?> görüntülenme</p>
                </li>
            <?php endforeach; ?>
        </ol>
    </aside>
<?php endif; ?>
</div>
<?php if ($pages > 1): ?>
    <nav class="mt-8 flex flex-wrap gap-2">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a class="rounded-md px-3 py-1.5 text-sm <?= $i === $pageNo ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-700' ?>" href="<?= e(posts_list_permalink($i)) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
<?php require __DIR__ . '/site/footer.php'; ?>
