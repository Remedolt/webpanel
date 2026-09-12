<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'comments') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int) ($_POST['comment_id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    if ($id > 0) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE comments SET status = 'approved' WHERE id = ?")->execute([$id]);
            flash_set('success', 'Yorum onaylandı.');
            cms_audit($pdo, 'approve', 'comment', $id, cms_row_title($pdo, 'comments', $id, 'author_name'));
            redirect('index.php?page=dashboard');
        } elseif ($action === 'pending') {
            $pdo->prepare("UPDATE comments SET status = 'pending' WHERE id = ?")->execute([$id]);
            flash_set('success', 'Yorum beklemeye alındı.');
        } elseif ($action === 'spam') {
            $pdo->prepare("UPDATE comments SET status = 'spam' WHERE id = ?")->execute([$id]);
            flash_set('success', 'Yorum spam olarak işaretlendi.');
        } elseif ($action === 'delete') {
            $gone = cms_row_title($pdo, 'comments', $id, 'author_name');
            $pdo->prepare('DELETE FROM comments WHERE id = ?')->execute([$id]);
            flash_set('success', 'Yorum silindi.');
            cms_audit($pdo, 'delete', 'comment', $id, $gone);
        }
    }
    redirect('index.php?page=comments');
}

$statusFilter = (string) ($_GET['status'] ?? '');
if (!in_array($statusFilter, ['', 'pending', 'approved', 'spam'], true)) {
    $statusFilter = '';
}

$sql = 'SELECT c.id, c.author_name, c.content, c.status, c.created_at, p.title AS post_title, p.slug AS post_slug
        FROM comments c
        LEFT JOIN posts p ON p.id = c.post_id';
$params = [];
if ($statusFilter !== '') {
    $sql .= ' WHERE c.status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY c.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$comments = $stmt->fetchAll();

$commentCounts = ['all' => 0, 'pending' => 0, 'approved' => 0, 'spam' => 0];
try {
    foreach ($pdo->query('SELECT status, COUNT(*) AS c FROM comments GROUP BY status') as $row) {
        $st = (string) $row['status'];
        $c = (int) $row['c'];
        if (isset($commentCounts[$st])) {
            $commentCounts[$st] = $c;
        }
        $commentCounts['all'] += $c;
    }
} catch (PDOException $e) {
    $commentCounts['all'] = count($comments);
}
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Yorumlar</h1>
    <p class="mt-1 text-sm text-slate-500">Yazı sayfalarından gelen yorumlar. Onaylananlar sitede görünür.</p>
</div>

<div class="mb-4 flex flex-wrap gap-2 text-sm">
    <a class="panel-chip <?= $statusFilter === '' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600' ?>" href="index.php?page=comments">Tümü (<?= (int) $commentCounts['all'] ?>)</a>
    <a class="panel-chip <?= $statusFilter === 'pending' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600' ?>" href="index.php?page=comments&amp;status=pending">Bekleyen (<?= (int) $commentCounts['pending'] ?>)</a>
    <a class="panel-chip <?= $statusFilter === 'approved' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600' ?>" href="index.php?page=comments&amp;status=approved">Onaylı (<?= (int) $commentCounts['approved'] ?>)</a>
    <a class="panel-chip <?= $statusFilter === 'spam' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600' ?>" href="index.php?page=comments&amp;status=spam">Spam (<?= (int) $commentCounts['spam'] ?>)</a>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-4 py-3 font-medium">Yazar</th>
                <th class="px-4 py-3 font-medium">Yorum</th>
                <th class="px-4 py-3 font-medium">Yazı</th>
                <th class="px-4 py-3 font-medium">Durum</th>
                <th class="px-4 py-3 font-medium text-right">İşlem</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (!$comments): ?>
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center text-slate-500">Henüz yorum yok. Ziyaretçiler yazı sayfasından yorum bırakabilir.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($comments as $row): ?>
                    <tr class="hover:bg-slate-50 align-top">
                        <td class="px-4 py-3 font-medium text-slate-900"><?= e((string) $row['author_name']) ?>
                            <div class="text-xs text-slate-400"><?= format_datetime((string) $row['created_at']) ?></div>
                        </td>
                        <td class="px-4 py-3 text-slate-700 max-w-md"><?= e((string) $row['content']) ?></td>
                        <td class="px-4 py-3">
                            <?php if (!empty($row['post_slug'])): ?>
                                <a class="text-[#2271b1] hover:underline" href="<?= e(post_permalink($row['post_slug'])) ?>" target="_blank"><?= e((string) $row['post_title']) ?></a>
                            <?php else: ?>
                                <span class="text-slate-400">Silinmiş yazı</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($row['status'] === 'approved'): ?>
                                <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Onaylı</span>
                            <?php elseif ($row['status'] === 'spam'): ?>
                                <span class="inline-flex rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Spam</span>
                            <?php else: ?>
                                <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Bekliyor</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="comment_id" value="<?= (int) $row['id'] ?>">
                                <button name="action" value="approve" class="text-emerald-700 hover:underline">Onayla</button>
                            </form>
                            <form method="post" class="inline ml-2"><?= csrf_field() ?><input type="hidden" name="comment_id" value="<?= (int) $row['id'] ?>">
                                <button name="action" value="spam" class="text-amber-700 hover:underline">Spam</button>
                            </form>
                            <form method="post" class="inline ml-2" onsubmit="return confirm('Silinsin mi?');"><?= csrf_field() ?><input type="hidden" name="comment_id" value="<?= (int) $row['id'] ?>">
                                <button name="action" value="delete" class="text-red-600 hover:underline">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
