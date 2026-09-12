<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'inquiries') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int) ($_POST['inquiry_id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    if ($id > 0) {
        if ($action === 'read') {
            try {
                $pdo->prepare('UPDATE inquiries SET is_read = 1 WHERE id = ?')->execute([$id]);
            } catch (PDOException $e) {
                // sütun yoksa yoksay
            }
            flash_set('success', 'Mesaj okundu olarak işaretlendi.');
        } elseif ($action === 'unread') {
            try {
                $pdo->prepare('UPDATE inquiries SET is_read = 0 WHERE id = ?')->execute([$id]);
            } catch (PDOException $e) {
                // sütun yoksa yoksay
            }
            flash_set('success', 'Mesaj yeni olarak işaretlendi.');
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM inquiries WHERE id = ?')->execute([$id]);
            flash_set('success', 'Mesaj silindi.');
        }
    }
    redirect('index.php?page=inquiries');
}

$filter = (string) ($_GET['status'] ?? '');
if (!in_array($filter, ['', 'new', 'read'], true)) {
    $filter = '';
}

$inquiries = [];
try {
    $sql = 'SELECT i.id, i.author_name, i.email, i.message, i.created_at, i.is_read, p.title AS page_title
            FROM inquiries i
            LEFT JOIN site_pages p ON p.id = i.page_id';
    $params = [];
    if ($filter === 'new') {
        $sql .= ' WHERE i.is_read = 0';
    } elseif ($filter === 'read') {
        $sql .= ' WHERE i.is_read = 1';
    }
    $sql .= ' ORDER BY i.created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $inquiries = $stmt->fetchAll();
} catch (PDOException $e) {
    try {
        $inquiries = $pdo->query(
            'SELECT i.id, i.author_name, i.email, i.message, i.created_at, 0 AS is_read, p.title AS page_title
             FROM inquiries i
             LEFT JOIN site_pages p ON p.id = i.page_id
             ORDER BY i.created_at DESC'
        )->fetchAll();
    } catch (PDOException $e2) {
        $inquiries = [];
    }
}

$totalAll = count($inquiries);
$newCount = 0;
$readCount = 0;
foreach ($inquiries as $row) {
    if ((int) ($row['is_read'] ?? 0) === 1) {
        $readCount++;
    } else {
        $newCount++;
    }
}
if ($filter !== '') {
    try {
        $newCount = (int) $pdo->query('SELECT COUNT(*) FROM inquiries WHERE is_read = 0')->fetchColumn();
        $readCount = (int) $pdo->query('SELECT COUNT(*) FROM inquiries WHERE is_read = 1')->fetchColumn();
        $totalAll = $newCount + $readCount;
    } catch (PDOException $e) {
        $totalAll = count($inquiries);
    }
}
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">İletişim</h1>
    <p class="mt-1 text-sm text-slate-500">İletişim şablonlu sayfalardan gelen formlar. Okundu işaretleyebilir veya silebilirsiniz.</p>
</div>

<div class="mb-4 flex flex-wrap gap-2 text-sm">
    <a class="panel-chip <?= $filter === '' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600' ?>" href="index.php?page=inquiries">Tümü (<?= (int) $totalAll ?>)</a>
    <a class="panel-chip <?= $filter === 'new' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600' ?>" href="index.php?page=inquiries&amp;status=new">Yeni (<?= (int) $newCount ?>)</a>
    <a class="panel-chip <?= $filter === 'read' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600' ?>" href="index.php?page=inquiries&amp;status=read">Okunan (<?= (int) $readCount ?>)</a>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <?php if (!$inquiries): ?>
        <div class="panel-empty">
            <p>Henüz mesaj yok. İletişim şablonlu bir sayfa yayımlayınca ziyaretçi formu buraya düşer.</p>
            <a class="mt-3 inline-block text-sm font-semibold text-[#2271b1] hover:underline" href="index.php?page=pages">Sayfa ekle</a>
        </div>
    <?php else: ?>
        <ul class="divide-y divide-slate-100">
            <?php foreach ($inquiries as $msg): ?>
                <?php $isRead = (int) ($msg['is_read'] ?? 0) === 1; ?>
                <li class="p-4 <?= $isRead ? '' : 'bg-sky-50/60' ?>">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <?php if (!$isRead): ?>
                                    <span class="inline-flex rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-semibold text-sky-800">Yeni</span>
                                <?php endif; ?>
                                <p class="text-sm font-semibold text-slate-900"><?= e((string) $msg['author_name']) ?></p>
                                <a class="text-xs text-[#2271b1] hover:underline" href="mailto:<?= e((string) $msg['email']) ?>"><?= e((string) $msg['email']) ?></a>
                            </div>
                            <p class="mt-1 text-xs text-slate-400">
                                <?= e(format_datetime((string) $msg['created_at'])) ?>
                                <?= !empty($msg['page_title']) ? ' · ' . e((string) $msg['page_title']) : '' ?>
                            </p>
                            <p class="mt-2 text-sm text-slate-700 whitespace-pre-wrap"><?= e((string) $msg['message']) ?></p>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="inquiry_id" value="<?= (int) $msg['id'] ?>">
                                <?php if ($isRead): ?>
                                    <input type="hidden" name="action" value="unread">
                                    <button type="submit" class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">Yeni yap</button>
                                <?php else: ?>
                                    <input type="hidden" name="action" value="read">
                                    <button type="submit" class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">Okundu</button>
                                <?php endif; ?>
                            </form>
                            <form method="post" onsubmit="return confirm('Mesaj silinsin mi?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="inquiry_id" value="<?= (int) $msg['id'] ?>">
                                <button type="submit" class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">Sil</button>
                            </form>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
