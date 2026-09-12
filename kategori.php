<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '') {
    header('Location: ' . posts_list_permalink());
    exit;
}

$catStmt = $pdo->prepare('SELECT id, name, slug FROM categories WHERE slug = ? LIMIT 1');
$catStmt->execute([$slug]);
$category = $catStmt->fetch();
if (!$category) {
    http_response_code(404);
    $docTitle = 'Kategori bulunamadı — ' . $siteTitle;
    require __DIR__ . '/site/header.php';
    echo '<h1 class="text-2xl font-bold">Kategori bulunamadı</h1><p class="mt-3 text-slate-600">Bu kategori yok.</p><p class="mt-4"><a class="text-sky-700 hover:underline" href="' . e(posts_list_permalink()) . '">Tüm yazılar</a></p>';
    require __DIR__ . '/site/footer.php';
    exit;
}

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
$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM posts p
     INNER JOIN post_categories pc ON pc.post_id = p.id
     WHERE p.status = 'publish' AND pc.category_id = ?"
);
$countStmt->execute([(int) $category['id']]);
$total = (int) $countStmt->fetchColumn();
$pages = (int) ceil($total / $perPage);
if ($pages < 1) {
    $pages = 1;
}
if ($pageNo > $pages) {
    $pageNo = $pages;
}
$offset = ($pageNo - 1) * $perPage;
$stmt = $pdo->prepare(
    "SELECT p.title, p.slug, p.excerpt, p.featured_image, p.created_at, u.display_name
     FROM posts p
     INNER JOIN users u ON u.id = p.author_id
     INNER JOIN post_categories pc ON pc.post_id = p.id
     WHERE p.status = 'publish' AND pc.category_id = ?
     ORDER BY p.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute([(int) $category['id']]);
$posts = $stmt->fetchAll();
$docTitle = (string) $category['name'] . ' — ' . $siteTitle;
require __DIR__ . '/site/header.php';
?>
<div class="flex items-end justify-between gap-3 mb-6">
    <div>
        <?php cms_crumbs(array(
            array('label' => 'Ana sayfa', 'url' => public_url()),
            array('label' => 'Yazılar', 'url' => posts_list_permalink()),
            array('label' => (string) $category['name'], 'url' => ''),
        )); ?>
        <p class="text-xs uppercase tracking-wide text-slate-400">Kategori</p>
        <h1 class="mt-1 text-3xl font-bold text-slate-900"><?= e((string) $category['name']) ?></h1>
    </div>
    <p class="text-sm text-slate-400"><?= (int) $total ?> yazı</p>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php if (!$posts): ?>
        <div class="md:col-span-2 bg-white border border-slate-200 rounded-xl p-8 text-slate-500">Bu kategoride yayımlanmış yazı yok.</div>
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
<?php if ($pages > 1): ?>
    <nav class="mt-8 flex flex-wrap gap-2">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a class="rounded-md px-3 py-1.5 text-sm <?= $i === $pageNo ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-700' ?>" href="<?= e(category_permalink($category['slug'], $i)) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
<?php require __DIR__ . '/site/footer.php'; ?>
