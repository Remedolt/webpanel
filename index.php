<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';

$perPage = (int) option_get($pdo, 'posts_per_page', '10');
if ($perPage < 5) {
    $perPage = 10;
}
if ($perPage > 50) {
    $perPage = 50;
}
$pageNum = (int) ($_GET['p'] ?? 1);
if ($pageNum < 1) {
    $pageNum = 1;
}

$total = (int) $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'publish'")->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
if ($pageNum > $totalPages) {
    $pageNum = $totalPages;
}
$offset = ($pageNum - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT p.id, p.title, p.slug, p.content, p.excerpt, p.featured_image, p.created_at, u.display_name
     FROM posts p
     INNER JOIN users u ON u.id = p.author_id
     WHERE p.status = 'publish'
     ORDER BY p.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute();
$posts = attach_post_categories($pdo, $stmt->fetchAll());

$docTitle = $siteTitle . ($siteTagline !== '' ? ' — ' . $siteTagline : '');
$metaDescription = $siteTagline !== '' ? $siteTagline : ($siteTitle . ' yazılım notları ve rehberler');
$canonicalUrl = $pageNum > 1 ? public_url('?p=' . $pageNum) : public_url();
require __DIR__ . '/site/header.php';
?>
<div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_18rem] gap-8 items-start">
    <div>
        <section class="mb-8">
            <p class="text-sm uppercase tracking-wide text-cyan-700 font-semibold">Geliştirici günlüğü</p>
            <h1 class="mt-2 text-4xl font-bold text-slate-900"><?= e($siteTitle) ?></h1>
            <?php if ($siteTagline !== ''): ?>
                <p class="mt-3 text-lg text-slate-600 max-w-2xl"><?= e($siteTagline) ?></p>
            <?php endif; ?>
        </section>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php if (!$posts): ?>
                <div class="md:col-span-2 bg-white border border-slate-200 rounded-xl p-8 text-slate-500">
                    Henüz yayımlanmış yazı yok. Panelden bir yazı oluşturup <strong>Yayımla</strong> deyin.
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <?php require __DIR__ . '/site/post-card.php'; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?= pagination_html($pageNum, $totalPages, public_url()) ?>
    </div>
    <?php require __DIR__ . '/site/sidebar.php'; ?>
</div>
<?php require __DIR__ . '/site/footer.php'; ?>
