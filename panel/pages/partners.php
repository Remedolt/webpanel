<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'partners') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'heading') {
        option_set($pdo, 'partners_heading', trim((string) ($_POST['partners_heading'] ?? 'Referanslar')));
        option_set($pdo, 'partners_show_home', isset($_POST['partners_show_home']) ? '1' : '0');
        flash_set('success', 'Referans ayarları kaydedildi.');
        redirect('index.php?page=partners');
    }
    if ($action === 'reorder') {
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : array();
        $upd = $pdo->prepare('UPDATE partners SET sort_order = ? WHERE id = ?');
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
        redirect('index.php?page=partners');
    }
    if ($action === 'save') {
        $id = (int) ($_POST['partner_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $url = trim((string) ($_POST['url'] ?? ''));
        $status = ((string) ($_POST['status'] ?? 'publish') === 'publish') ? 'publish' : 'draft';
        if ($name === '') {
            flash_set('error', 'Başlık zorunludur.');
        } else {
            $logo = '';
            if ($id > 0) {
                $cur = $pdo->prepare('SELECT logo FROM partners WHERE id = ?');
                $cur->execute([$id]);
                $logo = (string) ($cur->fetchColumn() ?: '');
            }
            $uploaded = handle_image_upload('logo');
            if ($uploaded) {
                $logo = $uploaded;
            }
            if (isset($_POST['remove_logo'])) {
                $logo = '';
            }
            if ($id > 0) {
                $pdo->prepare('UPDATE partners SET name = ?, logo = ?, url = ?, status = ? WHERE id = ?')
                    ->execute([$name, $logo, $url, $status, $id]);
                flash_set('success', 'Referans güncellendi.');
                cms_audit($pdo, 'update', 'partner', $id, $name);
                redirect('index.php?page=partners&id=' . $id);
            }
            $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM partners')->fetchColumn();
            $pdo->prepare('INSERT INTO partners (name, logo, url, sort_order, status) VALUES (?, ?, ?, ?, ?)')
                ->execute([$name, $logo, $url, $max + 1, $status]);
            flash_set('success', 'Referans eklendi.');
            cms_audit($pdo, 'create', 'partner', (int) $pdo->lastInsertId(), $name);
        }
        redirect('index.php?page=partners');
    }
    if ($action === 'delete') {
        $id = (int) ($_POST['partner_id'] ?? 0);
        if ($id > 0) {
            $gone = cms_row_title($pdo, 'partners', $id, 'name');
            $pdo->prepare('DELETE FROM partners WHERE id = ?')->execute([$id]);
            flash_set('success', 'Referans silindi.');
            cms_audit($pdo, 'delete', 'partner', $id, $gone);
        }
        redirect('index.php?page=partners');
    }
}

$editId = (int) ($_GET['id'] ?? 0);
$edit = null;
if ($editId > 0) {
    $st = $pdo->prepare('SELECT * FROM partners WHERE id = ?');
    $st->execute([$editId]);
    $edit = $st->fetch() ?: null;
}
$rows = $pdo->query('SELECT * FROM partners ORDER BY sort_order ASC, id ASC')->fetchAll();
$csrf = csrf_token();
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Referanslar</h1>
    <p class="mt-1 text-sm text-slate-500">Logo ve başlık. Ana sayfada kayarak döner; /referanslar sayfasında kart olarak listelenir.</p>
</div>
<div class="mb-6 bg-white rounded-lg border border-slate-200 p-4">
    <form method="post" class="flex flex-col sm:flex-row gap-3 sm:items-end">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="heading">
        <div class="flex-1">
            <label class="mb-1 block text-sm font-medium">Bölüm başlığı</label>
            <input name="partners_heading" value="<?= e(option_get($pdo, 'partners_heading', 'Referanslar')) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="partners_show_home" value="1" <?= option_get($pdo, 'partners_show_home', '1') === '1' ? 'checked' : '' ?>> Ana sayfada göster</label>
        <button class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white" type="submit">Kaydet</button>
    </form>
</div>
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg border border-slate-200 p-4 h-fit">
        <h2 class="text-sm font-semibold mb-4"><?= $edit ? 'Referansı düzenle' : 'Yeni referans' ?></h2>
        <form method="post" enctype="multipart/form-data" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="partner_id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
            <div>
                <label class="mb-1 block text-sm font-medium">Başlık</label>
                <input name="name" required value="<?= e($edit ? (string) $edit['name'] : '') ?>" placeholder="Firma veya proje adı" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Site (isteğe bağlı)</label>
                <input name="url" value="<?= e($edit ? (string) ($edit['url'] ?? '') : '') ?>" placeholder="https://" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Logo</label>
                <?php if ($edit && !empty($edit['logo'])): ?>
                    <img src="<?= e(media_src($edit['logo'])) ?>" alt="" class="mb-2 h-12 w-auto object-contain">
                    <label class="mb-2 flex items-center gap-2 text-xs"><input type="checkbox" name="remove_logo" value="1"> Logoyu kaldır</label>
                <?php endif; ?>
                <input type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Durum</label>
                <select name="status" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    <option value="publish" <?= (!$edit || $edit['status'] === 'publish') ? 'selected' : '' ?>>Yayımlanmış</option>
                    <option value="draft" <?= ($edit && $edit['status'] === 'draft') ? 'selected' : '' ?>>Taslak</option>
                </select>
            </div>
            <button class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white" type="submit"><?= $edit ? 'Güncelle' : 'Ekle' ?></button>
            <?php if ($edit): ?><a class="ml-2 text-sm text-slate-500" href="index.php?page=partners">Vazgeç</a><?php endif; ?>
        </form>
    </div>
    <div class="bg-white rounded-lg border border-slate-200 p-4">
        <ul class="space-y-2" data-sortable data-page="partners">
            <?php foreach ($rows as $row): ?>
                <li draggable="true" data-id="<?= (int) $row['id'] ?>" class="flex items-center gap-3 rounded-md border border-slate-200 px-3 py-2 cursor-grab">
                    <span class="text-slate-400">⋮⋮</span>
                    <?php if (!empty($row['logo'])): ?>
                        <img src="<?= e(media_src($row['logo'])) ?>" alt="" class="h-10 w-16 object-contain">
                    <?php else: ?>
                        <span class="h-10 w-16 rounded bg-slate-100 text-[10px] flex items-center justify-center text-slate-400">Logo yok</span>
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium"><?= e((string) $row['name']) ?></p>
                        <p class="text-[11px] text-slate-400 truncate"><?= e((string) ($row['url'] ?? '')) ?></p>
                    </div>
                    <a class="text-sm text-[#2271b1]" href="index.php?page=partners&amp;id=<?= (int) $row['id'] ?>">Düzenle</a>
                    <form method="post" onsubmit="return confirm('Silinsin mi?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="partner_id" value="<?= (int) $row['id'] ?>">
                        <button class="text-sm text-red-600" type="submit">Sil</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<input type="hidden" id="menu-csrf" value="<?= e($csrf) ?>">
