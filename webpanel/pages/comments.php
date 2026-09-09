<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'comments') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    $commentId = (int) ($_POST['comment_id'] ?? 0);
    if ($commentId > 0) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE comments SET status = 'approved' WHERE id = ?")->execute([$commentId]);
            flash_set('success', 'Yorum onaylandı.');
        } elseif ($action === 'pending') {
            $pdo->prepare("UPDATE comments SET status = 'pending' WHERE id = ?")->execute([$commentId]);
            flash_set('success', 'Yorum onaya alındı.');
        } elseif ($action === 'spam') {
            $pdo->prepare("UPDATE comments SET status = 'spam' WHERE id = ?")->execute([$commentId]);
            flash_set('success', 'Yorum spam olarak işaretlendi.');
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM comments WHERE id = ?')->execute([$commentId]);
            flash_set('success', 'Yorum silindi.');
        }
    }
    $statusKeep = (string) ($_POST['status'] ?? '');
    $redir = 'index.php?page=comments';
    if (in_array($statusKeep, ['pending', 'approved', 'spam', 'all'], true)) {
        $redir .= '&status=' . rawurlencode($statusKeep);
    }
    redirect($redir);
}

$statusRaw = (string) ($_GET['status'] ?? 'pending');
if ($statusRaw === 'all') {
    $statusFilter = '';
} elseif (!in_array($statusRaw, ['pending', 'approved', 'spam'], true)) {
    $statusFilter = 'pending';
} else {
    $statusFilter = $statusRaw;
}

$where = '';
$params = [];
if ($statusFilter !== '') {
    $where = 'WHERE c.status = ?';
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare(
    "SELECT c.id, c.author_name, c.content, c.status, c.created_at, p.title, p.slug
     FROM comments c
     INNER JOIN posts p ON p.id = c.post_id
     {$where}
     ORDER BY c.created_at DESC
     LIMIT 200"
);
$stmt->execute($params);
$comments = $stmt->fetchAll();

$counts = [
    'pending'  => (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn(),
    'approved' => (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'approved'")->fetchColumn(),
    'spam'     => (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'spam'")->fetchColumn(),
];
$totalAll = $counts['pending'] + $counts['approved'] + $counts['spam'];
$statusLabel = ['pending' => 'Onay bekliyor', 'approved' => 'Onaylı', 'spam' => 'Spam'];
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Yorumlar</h1>
    <p class="mt-1 text-sm text-slate-500">Ziyaretçi yorumlarını onaylayın veya kaldırın.</p>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200">
    <div class="flex flex-wrap gap-2 border-b border-slate-200 p-4 text-sm">
        <a href="index.php?page=comments&amp;status=all" class="<?= $statusFilter === '' ? 'font-semibold text-slate-900' : 'text-slate-500 hover:text-slate-800' ?>">Tümü (<?= (int) $totalAll ?>)</a>
        <span class="text-slate-300">|</span>
        <a href="index.php?page=comments&amp;status=pending" class="<?= $statusFilter === 'pending' ? 'font-semibold text-slate-900' : 'text-slate-500 hover:text-slate-800' ?>">Onay bekleyen (<?= (int) $counts['pending'] ?>)</a>
        <span class="text-slate-300">|</span>
        <a href="index.php?page=comments&amp;status=approved" class="<?= $statusFilter === 'approved' ? 'font-semibold text-slate-900' : 'text-slate-500 hover:text-slate-800' ?>">Onaylı (<?= (int) $counts['approved'] ?>)</a>
        <span class="text-slate-300">|</span>
        <a href="index.php?page=comments&amp;status=spam" class="<?= $statusFilter === 'spam' ? 'font-semibold text-slate-900' : 'text-slate-500 hover:text-slate-800' ?>">Spam (<?= (int) $counts['spam'] ?>)</a>
    </div>

    <div class="divide-y divide-slate-100">
        <?php if (!$comments): ?>
            <p class="px-4 py-10 text-center text-sm text-slate-500">Kayıt yok.</p>
        <?php else: ?>
            <?php foreach ($comments as $row): ?>
                <div class="px-4 py-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-900"><?= e((string) $row['author_name']) ?></p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                <?= e(format_datetime((string) $row['created_at'])) ?>
                                · <a class="hover:underline" href="<?= e(post_permalink($row['slug'])) ?>" target="_blank" rel="noopener"><?= e((string) $row['title']) ?></a>
                            </p>
                            <p class="mt-2 text-sm text-slate-700 whitespace-pre-wrap"><?= e((string) $row['content']) ?></p>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs shrink-0">
                            <span class="inline-flex rounded-full px-2 py-0.5 font-medium <?= $row['status'] === 'approved' ? 'bg-emerald-50 text-emerald-700' : ($row['status'] === 'spam' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') ?>">
                                <?= e($statusLabel[$row['status']] ?? (string) $row['status']) ?>
                            </span>
                            <?php if ($row['status'] !== 'approved'): ?>
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="comment_id" value="<?= (int) $row['id'] ?>">
                                    <input type="hidden" name="status" value="<?= e($statusFilter === '' ? 'all' : $statusFilter) ?>">
                                    <button class="text-[#2271b1] hover:underline">Onayla</button>
                                </form>
                            <?php else: ?>
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="pending">
                                    <input type="hidden" name="comment_id" value="<?= (int) $row['id'] ?>">
                                    <input type="hidden" name="status" value="<?= e($statusFilter === '' ? 'all' : $statusFilter) ?>">
                                    <button class="text-slate-600 hover:underline">Beklet</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($row['status'] !== 'spam'): ?>
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="spam">
                                    <input type="hidden" name="comment_id" value="<?= (int) $row['id'] ?>">
                                    <input type="hidden" name="status" value="<?= e($statusFilter === '' ? 'all' : $statusFilter) ?>">
                                    <button class="text-amber-700 hover:underline">Spam</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" onsubmit="return confirm('Yorum silinsin mi?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="comment_id" value="<?= (int) $row['id'] ?>">
                                <input type="hidden" name="status" value="<?= e($statusFilter === '' ? 'all' : $statusFilter) ?>">
                                <button class="text-red-600 hover:underline">Sil</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
