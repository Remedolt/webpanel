<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'services') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'heading') {
        option_set($pdo, 'services_heading', trim((string) ($_POST['services_heading'] ?? 'Hizmetler')));
        option_set($pdo, 'services_show_home', isset($_POST['services_show_home']) ? '1' : '0');
        flash_set('success', 'Hizmet ayarları kaydedildi.');
        redirect('index.php?page=services');
    }
    if ($action === 'reorder') {
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : array();
        $upd = $pdo->prepare('UPDATE services SET sort_order = ? WHERE id = ?');
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
        redirect('index.php?page=services');
    }
    if ($action === 'save') {
        $id = (int) ($_POST['service_id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
        $content = (string) ($_POST['content'] ?? '');
        $status = ((string) ($_POST['status'] ?? 'publish') === 'publish') ? 'publish' : 'draft';
        $slugIn = trim((string) ($_POST['slug'] ?? ''));
        if ($title === '') {
            flash_set('error', 'Başlık zorunludur.');
        } else {
            $slug = $slugIn !== '' ? slugify($slugIn) : slugify($title);
            if ($slug === '') {
                $slug = 'hizmet';
            }
            $slug = unique_service_slug($pdo, $slug, $id > 0 ? $id : null);
            $image = '';
            if ($id > 0) {
                $cur = $pdo->prepare('SELECT image FROM services WHERE id = ?');
                $cur->execute([$id]);
                $image = (string) ($cur->fetchColumn() ?: '');
            }
            $uploaded = handle_image_upload('image');
            if ($uploaded) {
                $image = $uploaded;
            }
            if (isset($_POST['remove_image'])) {
                $image = '';
            }
            if ($id > 0) {
                $pdo->prepare('UPDATE services SET title = ?, slug = ?, excerpt = ?, content = ?, image = ?, status = ? WHERE id = ?')
                    ->execute([$title, $slug, $excerpt, $content, $image, $status, $id]);
                flash_set('success', 'Hizmet güncellendi.');
                cms_audit($pdo, $status === 'publish' ? 'publish' : 'update', 'service', $id, $title);
                redirect('index.php?page=services&id=' . $id);
            }
            $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM services')->fetchColumn();
            $pdo->prepare('INSERT INTO services (title, slug, excerpt, content, image, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$title, $slug, $excerpt, $content, $image, $max + 1, $status]);
            flash_set('success', 'Hizmet eklendi.');
            cms_audit($pdo, 'create', 'service', (int) $pdo->lastInsertId(), $title);
        }
        redirect('index.php?page=services');
    }
    if ($action === 'delete') {
        $id = (int) ($_POST['service_id'] ?? 0);
        if ($id > 0) {
            $gone = cms_row_title($pdo, 'services', $id);
            $pdo->prepare('DELETE FROM services WHERE id = ?')->execute([$id]);
            flash_set('success', 'Hizmet silindi.');
            cms_audit($pdo, 'delete', 'service', $id, $gone);
        }
        redirect('index.php?page=services');
    }
}

$editId = (int) ($_GET['id'] ?? 0);
$edit = null;
if ($editId > 0) {
    $st = $pdo->prepare('SELECT * FROM services WHERE id = ?');
    $st->execute([$editId]);
    $edit = $st->fetch() ?: null;
}
$rows = [];
try {
    $rows = $pdo->query('SELECT * FROM services ORDER BY sort_order ASC, id ASC')->fetchAll();
} catch (PDOException $e) {
    $rows = [];
}
$csrf = csrf_token();
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Hizmetler</h1>
    <p class="mt-1 text-sm text-slate-500">Kartlar ana sayfada ve <a class="text-[#2271b1] hover:underline font-mono" href="<?= e(services_permalink()) ?>" target="_blank"><?= e(services_permalink()) ?></a> adresinde görünür.</p>
</div>
<div class="mb-6 bg-white rounded-lg border border-slate-200 p-4">
    <form method="post" class="flex flex-col sm:flex-row gap-3 sm:items-end">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="heading">
        <div class="flex-1">
            <label class="mb-1 block text-sm font-medium">Bölüm başlığı</label>
            <input name="services_heading" value="<?= e(option_get($pdo, 'services_heading', 'Hizmetler')) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="services_show_home" value="1" <?= option_get($pdo, 'services_show_home', '1') === '1' ? 'checked' : '' ?>> Ana sayfada göster</label>
        <button class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white" type="submit">Kaydet</button>
    </form>
</div>
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg border border-slate-200 p-4 h-fit">
        <h2 class="text-sm font-semibold mb-4"><?= $edit ? 'Hizmeti düzenle' : 'Yeni hizmet' ?></h2>
        <form method="post" enctype="multipart/form-data" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="service_id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
            <div>
                <label class="mb-1 block text-sm font-medium">Başlık</label>
                <input name="title" required value="<?= e($edit ? (string) $edit['title'] : '') ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Kısa adres (slug)</label>
                <input name="slug" value="<?= e($edit ? (string) ($edit['slug'] ?? '') : '') ?>" placeholder="otomatik" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Kısa yazı</label>
                <input name="excerpt" value="<?= e($edit ? (string) ($edit['excerpt'] ?? '') : '') ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">İçerik</label>
                <textarea name="content" rows="6" class="cms-editor w-full rounded-md border border-slate-200 px-3 py-2 text-sm"><?= e($edit ? (string) ($edit['content'] ?? '') : '') ?></textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Görsel</label>
                <?php if ($edit && !empty($edit['image'])): ?>
                    <img src="<?= e(media_src((string) $edit['image'])) ?>" alt="" class="mb-2 h-24 w-36 rounded object-cover bg-slate-100">
                    <label class="mb-2 flex items-center gap-2 text-xs"><input type="checkbox" name="remove_image" value="1"> Görseli kaldır</label>
                <?php endif; ?>
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Durum</label>
                <select name="status" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    <option value="publish" <?= (!$edit || $edit['status'] === 'publish') ? 'selected' : '' ?>>Yayımlanmış</option>
                    <option value="draft" <?= ($edit && $edit['status'] === 'draft') ? 'selected' : '' ?>>Taslak</option>
                </select>
            </div>
            <button class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white" type="submit"><?= $edit ? 'Güncelle' : 'Ekle' ?></button>
            <?php if ($edit): ?><a class="ml-2 text-sm text-slate-500" href="index.php?page=services">Vazgeç</a><?php endif; ?>
        </form>
    </div>
    <div class="bg-white rounded-lg border border-slate-200 p-4">
        <ul class="space-y-2" data-sortable data-page="services">
            <?php foreach ($rows as $row): ?>
                <li draggable="true" data-id="<?= (int) $row['id'] ?>" class="flex items-center gap-3 rounded-md border border-slate-200 px-3 py-2 cursor-grab">
                    <span class="text-slate-400">⋮⋮</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium"><?= e((string) $row['title']) ?></p>
                        <p class="text-[11px] text-slate-400"><?= e((string) ($row['slug'] ?? '')) ?></p>
                    </div>
                    <a class="text-sm text-[#2271b1]" href="index.php?page=services&amp;id=<?= (int) $row['id'] ?>">Düzenle</a>
                    <form method="post" onsubmit="return confirm('Silinsin mi?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="service_id" value="<?= (int) $row['id'] ?>">
                        <button class="text-sm text-red-600" type="submit">Sil</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<input type="hidden" id="menu-csrf" value="<?= e($csrf) ?>">
