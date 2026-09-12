<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'redirects') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'save') {
        $source = cms_normalize_path((string) ($_POST['source'] ?? ''));
        $target = trim((string) ($_POST['target'] ?? ''));
        if ($source === '/' || $source === '' || $target === '') {
            flash_set('error', 'Kaynak yol ve hedef zorunludur. Ana sayfa (/) yönlendirilemez.');
        } else {
            try {
                $pdo->prepare('INSERT INTO redirects (source, target) VALUES (?, ?) ON DUPLICATE KEY UPDATE target = VALUES(target)')
                    ->execute([$source, $target]);
                flash_set('success', 'Yönlendirme kaydedildi.');
            } catch (PDOException $e) {
                flash_set('error', 'Yönlendirme kaydedilemedi.');
            }
        }
        redirect('index.php?page=redirects');
    }
    if ($action === 'delete') {
        $id = (int) ($_POST['redirect_id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM redirects WHERE id = ?')->execute([$id]);
            flash_set('success', 'Yönlendirme silindi.');
        }
        redirect('index.php?page=redirects');
    }
}

$rows = [];
try {
    $rows = $pdo->query('SELECT * FROM redirects ORDER BY id DESC')->fetchAll();
} catch (PDOException $e) {
    $rows = [];
}
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Yönlendirmeler</h1>
    <p class="mt-1 text-sm text-slate-500">Eski adresi yeni adrese 301 ile taşı. Kaynak: <span class="font-mono">/eski-sayfa</span></p>
</div>
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg border border-slate-200 p-4 h-fit">
        <h2 class="text-sm font-semibold mb-4">Yeni yönlendirme</h2>
        <form method="post" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <div>
                <label class="mb-1 block text-sm font-medium">Kaynak</label>
                <input name="source" required placeholder="/eski-adres" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-mono">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Hedef</label>
                <input name="target" required placeholder="/yeni-adres veya https://…" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-mono">
            </div>
            <button class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white" type="submit">Ekle</button>
        </form>
    </div>
    <div class="bg-white rounded-lg border border-slate-200 p-4">
        <?php if (!$rows): ?>
            <p class="text-sm text-slate-500">Henüz yönlendirme yok.</p>
        <?php else: ?>
            <ul class="divide-y divide-slate-100">
                <?php foreach ($rows as $row): ?>
                    <li class="py-3 flex items-start justify-between gap-3">
                        <div class="min-w-0 text-sm">
                            <p class="font-mono text-slate-800 truncate"><?= e((string) $row['source']) ?></p>
                            <p class="mt-1 text-xs text-slate-400 truncate">→ <?= e((string) $row['target']) ?></p>
                        </div>
                        <form method="post" onsubmit="return confirm('Silinsin mi?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="redirect_id" value="<?= (int) $row['id'] ?>">
                            <button class="text-sm text-red-600" type="submit">Sil</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
