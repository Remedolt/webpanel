<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';

$q = trim((string) ($_GET['q'] ?? ''));
$searchQuery = $q;
$perPage = (int) option_get($pdo, 'posts_per_page', '10');
if ($perPage < 5) {
    $perPage = 10;
}
$pageNum = (int) ($_GET['p'] ?? 1);
if ($pageNum < 1) {
    $pageNum = 1;
}

$posts = [];
$total = 0;
$totalPages = 1;
if ($q !== '') {
    $like = '%' . $q . '%';
    $countStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM posts p
         WHERE p.status = 'publish' AND (p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)"
    );
    $countStmt->execute([$like, $like, $like]);
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
         WHERE p.status = 'publish' AND (p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)
         ORDER BY p.created_at DESC
         LIMIT {$perPage} OFFSET {$offset}"
    );
    $listStmt->execute([$like, $like, $like]);
    $posts = attach_post_categories($pdo, $listStmt->fetchAll());
}

$docTitle = ($q !== '' ? ('“' . $q . '” araması') : 'Ara') . ' — ' . $siteTitle;
$metaDescription = $q !== '' ? ($q . ' için arama sonuçları') : 'Yazılarda ara';
$canonicalUrl = $q !== '' ? search_url($q) : public_url('ara');
require __DIR__ . '/site/header.php';
?>
<div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_18rem] gap-8 items-start">
    <div>
        <h1 class="text-3xl font-bold text-slate-900">Ara</h1>
        <form method="get" action="<?= e(public_url('ara')) ?>" class="mt-4 flex gap-2">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Başlık veya içerik…"
                   class="flex-1 rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100">
            <button type="submit" class="rounded-md bg-ink px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Ara</button>
        </form>

        <?php if ($q === ''): ?>
            <p class="mt-6 text-slate-500">Bir anahtar kelime yazın.</p>
        <?php else: ?>
            <p class="mt-4 text-sm text-slate-500">“<?= e($q) ?>” için <?= (int) $total ?> sonuç</p>
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php if (!$posts): ?>
                    <div class="md:col-span-2 bg-white border border-slate-200 rounded-xl p-8 text-slate-500">
                        Eşleşen yazı bulunamadı.
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <?php require __DIR__ . '/site/post-card.php'; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?= pagination_html($pageNum, $totalPages, search_url($q)) ?>
        <?php endif; ?>
    </div>
    <?php require __DIR__ . '/site/sidebar.php'; ?>
</div>
<?php require __DIR__ . '/site/footer.php'; ?>
