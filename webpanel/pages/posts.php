<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage, $currentUser) || $currentPage !== 'posts') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_post') {
    csrf_verify();
    $deleteId = (int) ($_POST['post_id'] ?? 0);
    if ($deleteId > 0) {
        $stmt = $pdo->prepare('DELETE FROM posts WHERE id = ?');
        $stmt->execute([$deleteId]);
        flash_set('success', 'Yazı silindi.');
    }
    redirect('index.php?page=posts');
}

$q = trim((string) ($_GET['q'] ?? ''));
$statusFilter = (string) ($_GET['status'] ?? '');
if (!in_array($statusFilter, ['', 'draft', 'publish'], true)) {
    $statusFilter = '';
}

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

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(p.title LIKE ? OR p.excerpt LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
}
if ($statusFilter !== '') {
    $where[] = 'p.status = ?';
    $params[] = $statusFilter;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countSql = "SELECT COUNT(*) FROM posts p {$whereSql}";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
if ($pageNum > $totalPages) {
    $pageNum = $totalPages;
}
$offset = ($pageNum - 1) * $perPage;

$listSql = "SELECT p.id, p.title, p.slug, p.status, p.views, p.created_at, u.display_name,
            (SELECT GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ', ')
             FROM post_categories pc
             INNER JOIN categories c ON c.id = pc.category_id
             WHERE pc.post_id = p.id) AS category_names
            FROM posts p
            INNER JOIN users u ON u.id = p.author_id
            {$whereSql}
            ORDER BY p.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}";
$listStmt = $pdo->prepare($listSql);
$listStmt->execute($params);
$posts = $listStmt->fetchAll();
?>
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Yazılar</h1>
        <p class="mt-1 text-sm text-slate-500"><?= (int) $total ?> kayıt listeleniyor.</p>
    </div>
    <a href="index.php?page=post-new" class="inline-flex items-center justify-center rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
        Yeni ekle
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200">
    <div class="flex flex-col gap-3 border-b border-slate-200 p-4 sm:flex-row sm:items-center">
        <div class="flex gap-2 text-sm">
            <a href="index.php?page=posts<?= $q !== '' ? '&amp;q=' . e(rawurlencode($q)) : '' ?>" class="<?= $statusFilter === '' ? 'font-semibold text-slate-900' : 'text-slate-500 hover:text-slate-800' ?>">Tümü</a>
            <span class="text-slate-300">|</span>
            <a href="index.php?page=posts&amp;status=publish<?= $q !== '' ? '&amp;q=' . e(rawurlencode($q)) : '' ?>" class="<?= $statusFilter === 'publish' ? 'font-semibold text-slate-900' : 'text-slate-500 hover:text-slate-800' ?>">Yayımlanmış</a>
            <span class="text-slate-300">|</span>
            <a href="index.php?page=posts&amp;status=draft<?= $q !== '' ? '&amp;q=' . e(rawurlencode($q)) : '' ?>" class="<?= $statusFilter === 'draft' ? 'font-semibold text-slate-900' : 'text-slate-500 hover:text-slate-800' ?>">Taslak</a>
        </div>
        <form method="get" action="index.php" class="sm:ml-auto flex gap-2">
            <input type="hidden" name="page" value="posts">
            <?php if ($statusFilter !== ''): ?>
                <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
            <?php endif; ?>
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Yazı ara…"
                   class="rounded-md border border-slate-200 px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            <button type="submit" class="rounded-md border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm hover:bg-slate-100">Ara</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Başlık</th>
                    <th class="px-4 py-3 font-medium">Yazar</th>
                    <th class="px-4 py-3 font-medium">Kategoriler</th>
                    <th class="px-4 py-3 font-medium">Durum</th>
                    <th class="px-4 py-3 font-medium text-right">Görüntülenme</th>
                    <th class="px-4 py-3 font-medium">Tarih</th>
                    <th class="px-4 py-3 font-medium text-right">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (!$posts): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-slate-500">Kayıt bulunamadı.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($posts as $row): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <a href="index.php?page=post-new&amp;id=<?= (int) $row['id'] ?>" class="font-medium text-slate-900 hover:text-[#2271b1]">
                                    <?= e((string) $row['title']) ?>
                                </a>
                            </td>
                            <td class="px-4 py-3 text-slate-600"><?= e((string) $row['display_name']) ?></td>
                            <td class="px-4 py-3 text-slate-500"><?= e((string) ($row['category_names'] ?: '—')) ?></td>
                            <td class="px-4 py-3">
                                <?php if ($row['status'] === 'publish'): ?>
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Yayımlanmış</span>
                                <?php else: ?>
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">Taslak</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-600"><?= number_format((int) $row['views'], 0, ',', '.') ?></td>
                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap"><?= format_datetime((string) $row['created_at']) ?></td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="index.php?page=post-new&amp;id=<?= (int) $row['id'] ?>" class="text-[#2271b1] hover:underline">Düzenle</a>
                                <?php if ($row['status'] === 'publish'): ?>
                                    <a href="<?= e(post_permalink($row['slug'])) ?>" target="_blank" rel="noopener noreferrer" class="ml-2 text-slate-600 hover:underline">Görüntüle</a>
                                <?php endif; ?>
                                <form method="post" action="index.php?page=posts" class="inline" onsubmit="return confirm('Bu yazı silinsin mi?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_post">
                                    <input type="hidden" name="post_id" value="<?= (int) $row['id'] ?>">
                                    <button type="submit" class="ml-2 text-red-600 hover:underline">Sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="flex items-center justify-between border-t border-slate-200 px-4 py-3 text-sm text-slate-600">
            <span>Sayfa <?= (int) $pageNum ?> / <?= (int) $totalPages ?></span>
            <div class="flex gap-2">
                <?php
                $base = 'index.php?page=posts';
                if ($q !== '') {
                    $base .= '&amp;q=' . e(rawurlencode($q));
                }
                if ($statusFilter !== '') {
                    $base .= '&amp;status=' . e($statusFilter);
                }
                ?>
                <?php if ($pageNum > 1): ?>
                    <a class="rounded-md border border-slate-200 px-3 py-1 hover:bg-slate-50" href="<?= $base ?>&amp;p=<?= (int) ($pageNum - 1) ?>">Önceki</a>
                <?php endif; ?>
                <?php if ($pageNum < $totalPages): ?>
                    <a class="rounded-md border border-slate-200 px-3 py-1 hover:bg-slate-50" href="<?= $base ?>&amp;p=<?= (int) ($pageNum + 1) ?>">Sonraki</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
