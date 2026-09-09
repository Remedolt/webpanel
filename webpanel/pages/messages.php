<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'messages') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

cms_ensure_schema($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    $messageId = (int) ($_POST['message_id'] ?? 0);
    if ($messageId > 0) {
        if ($action === 'read') {
            $pdo->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')->execute([$messageId]);
            flash_set('success', 'Mesaj okundu olarak işaretlendi.');
        } elseif ($action === 'unread') {
            $pdo->prepare('UPDATE contact_messages SET is_read = 0 WHERE id = ?')->execute([$messageId]);
            flash_set('success', 'Mesaj okunmadı olarak işaretlendi.');
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$messageId]);
            flash_set('success', 'Mesaj silindi.');
        }
    }
    redirect('index.php?page=messages');
}

$messages = $pdo->query(
    'SELECT id, name, email, message, is_read, created_at
     FROM contact_messages
     ORDER BY created_at DESC
     LIMIT 200'
)->fetchAll();
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">İletişim mesajları</h1>
    <p class="mt-1 text-sm text-slate-500">Sitedeki iletişim formundan gelen kayıtlar.</p>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 divide-y divide-slate-100">
    <?php if (!$messages): ?>
        <p class="px-4 py-10 text-center text-sm text-slate-500">Henüz mesaj yok.</p>
    <?php else: ?>
        <?php foreach ($messages as $row): ?>
            <div class="px-4 py-4 <?= ((int) $row['is_read'] === 0) ? 'bg-blue-50/40' : '' ?>">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-900">
                            <?= e((string) $row['name']) ?>
                            <?php if ((int) $row['is_read'] === 0): ?>
                                <span class="ml-2 inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-medium text-blue-700">Yeni</span>
                            <?php endif; ?>
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            <a class="hover:underline" href="mailto:<?= e((string) $row['email']) ?>"><?= e((string) $row['email']) ?></a>
                            · <?= e(format_datetime((string) $row['created_at'])) ?>
                        </p>
                        <p class="mt-2 text-sm text-slate-700 whitespace-pre-wrap"><?= e((string) $row['message']) ?></p>
                    </div>
                    <div class="flex gap-2 text-xs shrink-0">
                        <?php if ((int) $row['is_read'] === 0): ?>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="read">
                                <input type="hidden" name="message_id" value="<?= (int) $row['id'] ?>">
                                <button class="text-[#2271b1] hover:underline">Okundu</button>
                            </form>
                        <?php else: ?>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="unread">
                                <input type="hidden" name="message_id" value="<?= (int) $row['id'] ?>">
                                <button class="text-slate-600 hover:underline">Okunmadı</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" onsubmit="return confirm('Mesaj silinsin mi?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="message_id" value="<?= (int) $row['id'] ?>">
                            <button class="text-red-600 hover:underline">Sil</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
