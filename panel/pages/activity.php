<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'activity') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

$entity = preg_replace('/[^a-z_]/', '', strtolower((string) ($_GET['entity'] ?? ''))) ?? '';
$params = array();
$sql = 'SELECT id, user_name, action, entity, entity_id, title, ip, created_at FROM activity_log';
if ($entity !== '') {
    $sql .= ' WHERE entity = ?';
    $params[] = $entity;
}
$sql .= ' ORDER BY id DESC LIMIT 200';
try {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();
} catch (PDOException $e) {
    $rows = array();
}

$filters = array(
    '' => 'Tümü',
    'post' => 'Yazı',
    'page' => 'Sayfa',
    'album' => 'Albüm',
    'service' => 'Hizmet',
    'slider' => 'Slider',
    'user' => 'Kullanıcı',
    'comment' => 'Yorum',
);
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">İşlem günlüğü</h1>
    <p class="mt-1 text-sm text-slate-500">Kim neyi ekledi, yayımladı veya sildi. Son 200 kayıt.</p>
</div>

<div class="mb-4 flex flex-wrap gap-2">
    <?php foreach ($filters as $key => $label): ?>
        <a href="index.php?page=activity<?= $key !== '' ? '&amp;entity=' . e($key) : '' ?>" class="rounded-full border px-3 py-1 text-sm <?= $entity === $key ? 'border-slate-800 bg-slate-800 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
    <?php if (!$rows): ?>
        <p class="p-8 text-center text-slate-500">Henüz kayıt yok. İlk yazı, silme veya giriş burada görünür.</p>
    <?php else: ?>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Zaman</th>
                    <th class="px-4 py-3 font-medium">Kişi</th>
                    <th class="px-4 py-3 font-medium">İşlem</th>
                    <th class="px-4 py-3 font-medium">Öğe</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-500"><?= e(date('d.m.Y H:i', strtotime((string) $row['created_at']))) ?></td>
                        <td class="px-4 py-3 font-medium text-slate-900"><?= e((string) ($row['user_name'] !== '' ? $row['user_name'] : 'Sistem')) ?></td>
                        <td class="px-4 py-3 text-slate-600"><?= e(cms_audit_action_label((string) $row['action'])) ?></td>
                        <td class="px-4 py-3">
                            <span class="text-[11px] uppercase tracking-wide text-slate-400"><?= e(cms_audit_entity_label((string) $row['entity'])) ?></span>
                            <?php if (trim((string) $row['title']) !== ''): ?>
                                <span class="block text-slate-800"><?= e((string) $row['title']) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
