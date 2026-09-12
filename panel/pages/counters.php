<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'counters') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'heading') {
        option_set($pdo, 'counters_heading', trim((string) ($_POST['counters_heading'] ?? 'Rakamlarla')));
        option_set($pdo, 'counters_show_home', isset($_POST['counters_show_home']) ? '1' : '0');
        flash_set('success', 'Sayaç ayarları kaydedildi.');
        redirect('index.php?page=counters');
    }
    if ($action === 'reorder') {
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : array();
        $upd = $pdo->prepare('UPDATE counters SET sort_order = ? WHERE id = ?');
        $i = 1;
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $upd->execute([$i, $id]);
                $i++;
            }
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            cms_json_ok();
        }
        redirect('index.php?page=counters');
    }
    if ($action === 'save') {
        $id = (int) ($_POST['counter_id'] ?? 0);
        $label = trim((string) ($_POST['label'] ?? ''));
        $value = (int) ($_POST['value_num'] ?? 0);
        if ($value < 0) {
            $value = 0;
        }
        $suffix = trim((string) ($_POST['suffix'] ?? ''));
        $status = ((string) ($_POST['status'] ?? 'publish') === 'publish') ? 'publish' : 'draft';
        if ($label === '') {
            flash_set('error', 'Etiket zorunludur.');
        } else {
            if ($id > 0) {
                $pdo->prepare('UPDATE counters SET label = ?, value_num = ?, suffix = ?, status = ? WHERE id = ?')
                    ->execute([$label, $value, $suffix, $status, $id]);
                flash_set('success', 'Sayaç güncellendi.');
                redirect('index.php?page=counters&id=' . $id);
            }
            $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM counters')->fetchColumn();
            $pdo->prepare('INSERT INTO counters (label, value_num, suffix, sort_order, status) VALUES (?, ?, ?, ?, ?)')
                ->execute([$label, $value, $suffix, $max + 1, $status]);
            flash_set('success', 'Sayaç eklendi.');
        }
        redirect('index.php?page=counters');
    }
    if ($action === 'delete') {
        $id = (int) ($_POST['counter_id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM counters WHERE id = ?')->execute([$id]);
            flash_set('success', 'Sayaç silindi.');
        }
        redirect('index.php?page=counters');
    }
}

$editId = (int) ($_GET['id'] ?? 0);
$edit = null;
if ($editId > 0) {
    $st = $pdo->prepare('SELECT * FROM counters WHERE id = ?');
    $st->execute([$editId]);
    $edit = $st->fetch() ?: null;
}
$rows = $pdo->query('SELECT * FROM counters ORDER BY sort_order ASC, id ASC')->fetchAll();
$csrf = csrf_token();
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Sayaçlar</h1>
    <p class="mt-1 text-sm text-slate-500">Ana sayfada animasyonla artan rakamlar. İstediğiniz etiketi ve sayıyı yazın.</p>
</div>
<div class="mb-6 bg-white rounded-lg border border-slate-200 p-4">
    <form method="post" class="flex flex-col sm:flex-row gap-3 sm:items-end">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="heading">
        <div class="flex-1">
            <label class="mb-1 block text-sm font-medium">Bölüm başlığı</label>
            <input name="counters_heading" value="<?= e(option_get($pdo, 'counters_heading', 'Rakamlarla')) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="counters_show_home" value="1" <?= option_get($pdo, 'counters_show_home', '1') === '1' ? 'checked' : '' ?>> Ana sayfada göster</label>
        <button class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white" type="submit">Kaydet</button>
    </form>
</div>
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg border border-slate-200 p-4 h-fit">
        <h2 class="text-sm font-semibold mb-4"><?= $edit ? 'Sayacı düzenle' : 'Yeni sayaç' ?></h2>
        <form method="post" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="counter_id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
            <div>
                <label class="mb-1 block text-sm font-medium">Etiket</label>
                <input name="label" required value="<?= e($edit ? (string) $edit['label'] : '') ?>" placeholder="Proje" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Sayı</label>
                    <input name="value_num" type="number" min="0" value="<?= $edit ? (int) $edit['value_num'] : 0 ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Sonek</label>
                    <input name="suffix" value="<?= e($edit ? (string) ($edit['suffix'] ?? '') : '') ?>" placeholder="+" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Durum</label>
                <select name="status" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    <option value="publish" <?= (!$edit || $edit['status'] === 'publish') ? 'selected' : '' ?>>Yayımlanmış</option>
                    <option value="draft" <?= ($edit && $edit['status'] === 'draft') ? 'selected' : '' ?>>Taslak</option>
                </select>
            </div>
            <button class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white" type="submit"><?= $edit ? 'Güncelle' : 'Ekle' ?></button>
            <?php if ($edit): ?><a class="ml-2 text-sm text-slate-500" href="index.php?page=counters">Vazgeç</a><?php endif; ?>
        </form>
    </div>
    <div class="bg-white rounded-lg border border-slate-200 p-4">
        <ul class="space-y-2" data-sortable data-page="counters">
            <?php foreach ($rows as $row): ?>
                <li draggable="true" data-id="<?= (int) $row['id'] ?>" class="flex items-center gap-3 rounded-md border border-slate-200 px-3 py-2 cursor-grab">
                    <span class="text-slate-400">⋮⋮</span>
                    <div class="flex-1">
                        <p class="text-sm font-medium"><?= e((string) $row['label']) ?></p>
                        <p class="text-lg font-semibold text-slate-800"><?= (int) $row['value_num'] ?><?= e((string) ($row['suffix'] ?? '')) ?></p>
                    </div>
                    <a class="text-sm text-[#2271b1]" href="index.php?page=counters&amp;id=<?= (int) $row['id'] ?>">Düzenle</a>
                    <form method="post" onsubmit="return confirm('Silinsin mi?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="counter_id" value="<?= (int) $row['id'] ?>">
                        <button class="text-sm text-red-600" type="submit">Sil</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<input type="hidden" id="menu-csrf" value="<?= e($csrf) ?>">
