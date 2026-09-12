<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'staff') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'heading') {
        option_set($pdo, 'staff_heading', trim((string) ($_POST['staff_heading'] ?? 'Ekibimiz')));
        option_set($pdo, 'staff_show_home', isset($_POST['staff_show_home']) ? '1' : '0');
        flash_set('success', 'Personel ayarları kaydedildi.');
        redirect('index.php?page=staff');
    }
    if ($action === 'reorder') {
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : array();
        $upd = $pdo->prepare('UPDATE staff SET sort_order = ? WHERE id = ?');
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
        redirect('index.php?page=staff');
    }
    if ($action === 'save') {
        $id = (int) ($_POST['staff_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $title = trim((string) ($_POST['title'] ?? ''));
        $interests = trim((string) ($_POST['interests'] ?? ''));
        $bio = (string) ($_POST['bio'] ?? '');
        $status = ((string) ($_POST['status'] ?? 'publish') === 'publish') ? 'publish' : 'draft';
        if ($name === '') {
            flash_set('error', 'Ad soyad zorunludur.');
        } else {
            $photo = '';
            if ($id > 0) {
                $cur = $pdo->prepare('SELECT photo FROM staff WHERE id = ?');
                $cur->execute([$id]);
                $photo = (string) ($cur->fetchColumn() ?: '');
            }
            $uploaded = handle_image_upload('photo');
            if ($uploaded) {
                $photo = $uploaded;
            }
            if (isset($_POST['remove_photo'])) {
                $photo = '';
            }
            if ($id > 0) {
                $pdo->prepare('UPDATE staff SET name = ?, title = ?, photo = ?, interests = ?, bio = ?, status = ? WHERE id = ?')
                    ->execute([$name, $title, $photo, $interests, $bio, $status, $id]);
                flash_set('success', 'Personel güncellendi.');
                cms_audit($pdo, 'update', 'staff', $id, $name);
                redirect('index.php?page=staff&id=' . $id);
            }
            $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM staff')->fetchColumn();
            $pdo->prepare('INSERT INTO staff (name, title, photo, interests, bio, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$name, $title, $photo, $interests, $bio, $max + 1, $status]);
            flash_set('success', 'Personel eklendi.');
            cms_audit($pdo, 'create', 'staff', (int) $pdo->lastInsertId(), $name);
        }
        redirect('index.php?page=staff');
    }
    if ($action === 'delete') {
        $id = (int) ($_POST['staff_id'] ?? 0);
        if ($id > 0) {
            $gone = cms_row_title($pdo, 'staff', $id, 'name');
            $pdo->prepare('DELETE FROM staff WHERE id = ?')->execute([$id]);
            flash_set('success', 'Personel silindi.');
            cms_audit($pdo, 'delete', 'staff', $id, $gone);
        }
        redirect('index.php?page=staff');
    }
}

$editId = (int) ($_GET['id'] ?? 0);
$edit = null;
if ($editId > 0) {
    $st = $pdo->prepare('SELECT * FROM staff WHERE id = ?');
    $st->execute([$editId]);
    $edit = $st->fetch() ?: null;
}
$rows = $pdo->query('SELECT * FROM staff ORDER BY sort_order ASC, id ASC')->fetchAll();
$csrf = csrf_token();
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Personel</h1>
    <p class="mt-1 text-sm text-slate-500">Fotoğraf, unvan ve ilgi alanları. Fotoğraf yoksa varsayılan silüet kullanılır.</p>
</div>
<div class="mb-6 bg-white rounded-lg border border-slate-200 p-4">
    <form method="post" class="flex flex-col sm:flex-row gap-3 sm:items-end">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="heading">
        <div class="flex-1">
            <label class="mb-1 block text-sm font-medium">Bölüm başlığı</label>
            <input name="staff_heading" value="<?= e(option_get($pdo, 'staff_heading', 'Ekibimiz')) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="staff_show_home" value="1" <?= option_get($pdo, 'staff_show_home', '1') === '1' ? 'checked' : '' ?>> Ana sayfada göster</label>
        <button class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white" type="submit">Kaydet</button>
    </form>
</div>
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg border border-slate-200 p-4 h-fit">
        <h2 class="text-sm font-semibold mb-4"><?= $edit ? 'Personeli düzenle' : 'Yeni personel' ?></h2>
        <form method="post" enctype="multipart/form-data" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="staff_id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
            <div>
                <label class="mb-1 block text-sm font-medium">Ad soyad</label>
                <input name="name" required value="<?= e($edit ? (string) $edit['name'] : '') ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Unvan</label>
                <input name="title" value="<?= e($edit ? (string) ($edit['title'] ?? '') : '') ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">İlgi alanları</label>
                <input name="interests" value="<?= e($edit ? (string) ($edit['interests'] ?? '') : '') ?>" placeholder="Tasarım, yazılım" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Kısa yazı</label>
                <textarea name="bio" rows="4" class="cms-editor w-full rounded-md border border-slate-200 px-3 py-2 text-sm"><?= e($edit ? (string) ($edit['bio'] ?? '') : '') ?></textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Fotoğraf</label>
                <img src="<?= e(staff_photo_src($edit ? (string) ($edit['photo'] ?? '') : '')) ?>" alt="" class="mb-2 h-20 w-20 rounded-full object-cover bg-slate-100">
                <?php if ($edit && !empty($edit['photo'])): ?>
                    <label class="mb-2 flex items-center gap-2 text-xs"><input type="checkbox" name="remove_photo" value="1"> Varsayılan fotoğrafa dön</label>
                <?php endif; ?>
                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp,image/gif">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Durum</label>
                <select name="status" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    <option value="publish" <?= (!$edit || $edit['status'] === 'publish') ? 'selected' : '' ?>>Yayımlanmış</option>
                    <option value="draft" <?= ($edit && $edit['status'] === 'draft') ? 'selected' : '' ?>>Taslak</option>
                </select>
            </div>
            <button class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white" type="submit"><?= $edit ? 'Güncelle' : 'Ekle' ?></button>
            <?php if ($edit): ?><a class="ml-2 text-sm text-slate-500" href="index.php?page=staff">Vazgeç</a><?php endif; ?>
        </form>
    </div>
    <div class="bg-white rounded-lg border border-slate-200 p-4">
        <ul class="space-y-2" data-sortable data-page="staff">
            <?php foreach ($rows as $row): ?>
                <li draggable="true" data-id="<?= (int) $row['id'] ?>" class="flex items-center gap-3 rounded-md border border-slate-200 px-3 py-2 cursor-grab">
                    <span class="text-slate-400">⋮⋮</span>
                    <img src="<?= e(staff_photo_src((string) ($row['photo'] ?? ''))) ?>" alt="" class="h-12 w-12 rounded-full object-cover bg-slate-100">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium"><?= e((string) $row['name']) ?></p>
                        <p class="text-[11px] text-slate-400"><?= e((string) ($row['title'] ?? '')) ?></p>
                    </div>
                    <a class="text-sm text-[#2271b1]" href="index.php?page=staff&amp;id=<?= (int) $row['id'] ?>">Düzenle</a>
                    <form method="post" onsubmit="return confirm('Silinsin mi?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="staff_id" value="<?= (int) $row['id'] ?>">
                        <button class="text-sm text-red-600" type="submit">Sil</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<input type="hidden" id="menu-csrf" value="<?= e($csrf) ?>">
