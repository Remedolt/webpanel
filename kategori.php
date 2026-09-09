<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '') {
    header('Location: ' . public_url());
    exit;
}

$catStmt = $pdo->prepare('SELECT id, name, slug FROM categories WHERE slug = ? LIMIT 1');
$catStmt->execute([$slug]);
$category = $catStmt->fetch();
if (!$category) {
    http_response_code(404);
    $docTitle = 'Kategori bulunamadı — ' . $siteTitle;
    $metaDescription = 'İstenen kategori bulunamadı.';
    require __DIR__ . '/site/header.php';
    echo '<h1 class="text-2xl font-bold">Kategori bulunamadı</h1>';
    require __DIR__ . '/site/footer.php';
    exit;
}

$perPage = (int) option_get($pdo, 'posts_per_page', '10');
if ($perPage < 5) {
    $perPage = 10;
}
$pageNum = (int) ($_GET['p'] ?? 1);
if ($pageNum < 1) {
    $pageNum = 1;
}

$countStmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM posts p
     INNER JOIN post_categories pc ON pc.post_id = p.id
     WHERE p.status = 'publish' AND pc.category_id = ?"
);
$countStmt->execute([(int) $category['id']]);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
if ($pageNum > $totalPages) {
    $pageNum = $totalPages;
}
$offset = ($pageNum - 1) * $perPage;

$listStmt = $pdo->prepare(
    "SELECT p.id, p.title, p.slug, p.content, p.excerpt, p.featured_image, p.created_at, u.display_name
     FROM posts p
     INNER JOIN users u ON u.id = p.author_id
     INNER JOIN post_categories pc ON pc.post_id = p.id
     WHERE p.status = 'publish' AND pc.category_id = ?
     ORDER BY p.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$listStmt->execute([(int) $category['id']]);
$posts = attach_post_categories($pdo, $listStmt->fetchAll());

$docTitle = (string) $category['name'] . ' — ' . $siteTitle;
$metaDescription = $category['name'] . ' kategorisindeki yazılar';
$canonicalUrl = category_permalink($category['slug']) . ($pageNum > 1 ? '?p=' . $pageNum : '');
require __DIR__ . '/site/header.php';
?>
<div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_18rem] gap-8 items-start">
    <div>
        <p class="text-sm uppercase tracking-wide text-cyan-700 font-semibold">Kategori</p>
        <h1 class="mt-2 text-3xl font-bold text-slate-900"><?= e((string) $category['name']) ?></h1>
        <p class="mt-2 text-sm text-slate-500"><?= (int) $total ?> yazı</p>

        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php if (!$posts): ?>
                <div class="md:col-span-2 bg-white border border-slate-200 rounded-xl p-8 text-slate-500">
                    Bu kategoride yayımlanmış yazı yok.
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <?php require __DIR__ . '/site/post-card.php'; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?= pagination_html($pageNum, $totalPages, category_permalink($category['slug'])) ?>
    </div>
    <?php require __DIR__ . '/site/sidebar.php'; ?>
</div>
<?php require __DIR__ . '/site/footer.php'; ?>
