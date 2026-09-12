<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'gallery') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

$sizes = [
    'large' => 'Büyük (2x2)',
    'wide' => 'Geniş (yatay)',
    'tall' => 'Yüksek (dikey)',
    'square' => 'Kare',
];

$editId = (int) ($_GET['id'] ?? 0);
$editAlbum = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM gallery_albums WHERE id = ? LIMIT 1');
    $stmt->execute([$editId]);
    $editAlbum = $stmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'settings') {
        option_set($pdo, 'gallery_heading', trim((string) ($_POST['gallery_heading'] ?? 'Galeri')));
        option_set($pdo, 'gallery_cta_text', trim((string) ($_POST['gallery_cta_text'] ?? 'Tüm galeri')));
        option_set($pdo, 'gallery_cta_url', trim((string) ($_POST['gallery_cta_url'] ?? '')));
        option_set($pdo, 'gallery_show_home', isset($_POST['gallery_show_home']) ? '1' : '0');
        flash_set('success', 'Galeri ayarları kaydedildi.');
        redirect('index.php?page=gallery');
    }
    if ($action === 'reorder') {
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : array();
        $upd = $pdo->prepare('UPDATE gallery_albums SET sort_order = ? WHERE id = ?');
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
        redirect('index.php?page=gallery');
    }
    if ($action === 'save') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $id = (int) ($_POST['album_id'] ?? 0);
        $tile = option_pick($_POST['tile_size'] ?? 'square', array_keys($sizes), 'square');
        $status = ((string) ($_POST['status'] ?? 'publish') === 'publish') ? 'publish' : 'draft';
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $description = trim((string) ($_POST['description'] ?? ''));
        if ($title === '') {
            flash_set('error', 'Albüm başlığı zorunludur.');
        } else {
            $slug = unique_gallery_slug($pdo, slugify(trim((string) ($_POST['slug'] ?? '')) !== '' ? (string) $_POST['slug'] : $title), $id > 0 ? $id : null);
            $cover = '';
            if ($id > 0) {
                $cur = $pdo->prepare('SELECT cover_image FROM gallery_albums WHERE id = ?');
                $cur->execute([$id]);
                $cover = (string) ($cur->fetchColumn() ?: '');
            }
            $uploaded = handle_image_upload('cover_image');
            if ($uploaded) {
                $cover = $uploaded;
            }
            if ($id > 0) {
                $pdo->prepare('UPDATE gallery_albums SET title = ?, slug = ?, cover_image = ?, description = ?, tile_size = ?, sort_order = ?, status = ? WHERE id = ?')
                    ->execute([$title, $slug, $cover, $description, $tile, $sort, $status, $id]);
                $albumId = $id;
                flash_set('success', 'Albüm güncellendi.');
                cms_audit($pdo, $status === 'publish' ? 'publish' : 'update', 'album', $albumId, $title);
            } else {
                $pdo->prepare('INSERT INTO gallery_albums (title, slug, cover_image, description, tile_size, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?)')
                    ->execute([$title, $slug, $cover, $description, $tile, $sort, $status]);
                $albumId = (int) $pdo->lastInsertId();
                flash_set('success', 'Albüm oluşturuldu. Fotoğraf ekleyebilirsiniz.');
                cms_audit($pdo, 'create', 'album', $albumId, $title);
            }
            $photos = handle_multi_uploads('images');
            if ($photos && $albumId > 0) {
                $maxStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM gallery_images WHERE album_id = ?');
                $maxStmt->execute([$albumId]);
                $max = (int) $maxStmt->fetchColumn();
                $insImg = $pdo->prepare('INSERT INTO gallery_images (album_id, image, sort_order) VALUES (?, ?, ?)');
                foreach ($photos as $path) {
                    $max++;
                    $insImg->execute([$albumId, $path, $max]);
                }
            }
            redirect('index.php?page=gallery&id=' . $albumId);
        }
        redirect('index.php?page=gallery');
    }
    if ($action === 'delete') {
        $id = (int) ($_POST['album_id'] ?? 0);
        if ($id > 0) {
            $gone = cms_row_title($pdo, 'gallery_albums', $id);
            $pdo->prepare('DELETE FROM gallery_images WHERE album_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM gallery_albums WHERE id = ?')->execute([$id]);
            flash_set('success', 'Albüm silindi.');
            cms_audit($pdo, 'delete', 'album', $id, $gone);
        }
        redirect('index.php?page=gallery');
    }
    if ($action === 'delete_image') {
        $id = (int) ($_POST['image_id'] ?? 0);
        $albumId = (int) ($_POST['album_id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM gallery_images WHERE id = ?')->execute([$id]);
            flash_set('success', 'Fotoğraf silindi.');
        }
        redirect('index.php?page=gallery' . ($albumId > 0 ? '&id=' . $albumId : ''));
    }
}

$albums = $pdo->query(
    "SELECT a.*, (SELECT COUNT(*) FROM gallery_images g WHERE g.album_id = a.id) AS photo_count
     FROM gallery_albums a ORDER BY a.sort_order ASC, a.id ASC"
)->fetchAll();
$albumPhotos = [];
if ($editAlbum) {
    $ph = $pdo->prepare('SELECT * FROM gallery_images WHERE album_id = ? ORDER BY sort_order ASC, id ASC');
    $ph->execute([(int) $editAlbum['id']]);
    $albumPhotos = $ph->fetchAll();
}
$heading = option_get($pdo, 'gallery_heading', 'Galeri');
$ctaText = option_get($pdo, 'gallery_cta_text', 'Tüm galeri');
$ctaUrl = option_get($pdo, 'gallery_cta_url', '');
$showHome = option_get($pdo, 'gallery_show_home', '1');
$csrf = csrf_token();
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Galeri</h1>
    <p class="mt-1 text-sm text-slate-500">Bento ızgara albümleri. Kapak + fotoğraflar. Site: <a class="text-[#2271b1] hover:underline" href="<?= e(gallery_permalink()) ?>" target="_blank"><?= e(gallery_permalink()) ?></a></p>
</div>

<div class="mb-6 bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <form method="post" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="settings">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Bölüm başlığı</label>
            <input name="gallery_heading" value="<?= e($heading) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Sağdaki buton yazısı</label>
            <input name="gallery_cta_text" value="<?= e($ctaText) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Buton linki</label>
            <input name="gallery_cta_url" value="<?= e($ctaUrl) ?>" placeholder="<?= e(gallery_permalink()) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
        </div>
        <div class="flex items-center gap-3">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="gallery_show_home" value="1" <?= $showHome === '1' ? 'checked' : '' ?>> Ana sayfada göster</label>
            <button class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white" type="submit">Kaydet</button>
        </div>
    </form>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 h-fit">
        <h2 class="text-sm font-semibold text-slate-900 mb-4"><?= $editAlbum ? 'Albümü düzenle' : 'Yeni albüm' ?></h2>
        <form method="post" enctype="multipart/form-data" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="album_id" value="<?= $editAlbum ? (int) $editAlbum['id'] : 0 ?>">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Başlık</label>
                <input name="title" required value="<?= e($editAlbum ? (string) $editAlbum['title'] : '') ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Slug</label>
                <input name="slug" value="<?= e($editAlbum ? (string) $editAlbum['slug'] : '') ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-mono">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Kısa açıklama</label>
                <textarea name="description" rows="2" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"><?= e($editAlbum ? (string) ($editAlbum['description'] ?? '') : '') ?></textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Kart boyutu</label>
                <select name="tile_size" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    <?php foreach ($sizes as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= ($editAlbum && $editAlbum['tile_size'] === $key) ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Kapak görseli</label>
                <?php if ($editAlbum && !empty($editAlbum['cover_image'])): ?>
                    <img src="<?= e(media_src($editAlbum['cover_image'])) ?>" alt="" class="mb-2 h-24 w-full object-cover rounded-md">
                <?php endif; ?>
                <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp,image/gif" class="block w-full text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Albüm fotoğrafları (birden fazla)</label>
                <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp,image/gif" class="block w-full text-sm">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Sıra</label>
                    <input type="number" name="sort_order" value="<?= $editAlbum ? (int) $editAlbum['sort_order'] : 0 ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Durum</label>
                    <select name="status" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                        <option value="publish" <?= (!$editAlbum || $editAlbum['status'] === 'publish') ? 'selected' : '' ?>>Yayımlanmış</option>
                        <option value="draft" <?= ($editAlbum && $editAlbum['status'] === 'draft') ? 'selected' : '' ?>>Taslak</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800"><?= $editAlbum ? 'Güncelle' : 'Kaydet' ?></button>
            <?php if ($editAlbum): ?>
                <a href="index.php?page=gallery" class="ml-2 text-sm text-slate-500 hover:underline">Yeni albüm</a>
            <?php endif; ?>
        </form>
        <?php if ($albumPhotos): ?>
            <div class="mt-4 grid grid-cols-3 gap-2">
                <?php foreach ($albumPhotos as $img): ?>
                    <div class="relative">
                        <img src="<?= e(media_src($img['image'])) ?>" alt="" class="h-20 w-full object-cover rounded-md">
                        <form method="post" class="absolute top-1 right-1" onsubmit="return confirm('Fotoğraf silinsin mi?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete_image">
                            <input type="hidden" name="image_id" value="<?= (int) $img['id'] ?>">
                            <input type="hidden" name="album_id" value="<?= (int) $editAlbum['id'] ?>">
                            <button class="rounded bg-white/90 px-1 text-xs text-red-600" type="submit">Sil</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
        <p class="px-4 py-3 text-xs text-slate-400 border-b">Sıralamak için sürükleyin. Kart boyutu ızgaradaki şekli belirler.</p>
        <ul class="divide-y divide-slate-100" data-sortable data-page="gallery">
            <?php foreach ($albums as $row): ?>
                <li draggable="true" data-id="<?= (int) $row['id'] ?>" class="flex items-center gap-3 px-4 py-3 cursor-grab hover:bg-slate-50">
                    <span class="text-slate-400">⋮⋮</span>
                    <?php if (!empty($row['cover_image'])): ?>
                        <img src="<?= e(media_src($row['cover_image'])) ?>" alt="" class="h-12 w-16 object-cover rounded">
                    <?php else: ?>
                        <span class="h-12 w-16 rounded bg-slate-200"></span>
                    <?php endif; ?>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-slate-900"><?= e((string) $row['title']) ?></p>
                        <p class="text-[11px] text-slate-400"><?= e($sizes[$row['tile_size']] ?? $row['tile_size']) ?> · <?= (int) $row['photo_count'] ?> fotoğraf</p>
                        <?php if (trim((string) ($row['description'] ?? '')) !== ''): ?>
                            <p class="text-[11px] text-slate-500 line-clamp-1"><?= e((string) $row['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    <a class="text-sm text-[#2271b1] hover:underline" href="index.php?page=gallery&amp;id=<?= (int) $row['id'] ?>">Düzenle</a>
                    <form method="post" onsubmit="return confirm('Albüm silinsin mi?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="album_id" value="<?= (int) $row['id'] ?>">
                        <button type="submit" class="text-sm text-red-600 hover:underline">Sil</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<input type="hidden" id="menu-csrf" value="<?= e($csrf) ?>">
