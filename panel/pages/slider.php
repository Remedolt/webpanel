<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'slider') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'settings') {
        $sec = (int) ($_POST['slider_interval'] ?? 6);
        if ($sec < 3) {
            $sec = 3;
        }
        if ($sec > 15) {
            $sec = 15;
        }
        option_set($pdo, 'slider_interval', (string) $sec);
        option_set($pdo, 'slider_autoplay', isset($_POST['slider_autoplay']) ? '1' : '0');
        flash_set('success', 'Slider geçişi kaydedildi.');
        redirect('index.php?page=slider');
    }
    if ($action === 'save') {
        $id = (int) ($_POST['slide_id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            flash_set('error', 'Slider başlığı zorunludur.');
        } else {
            $image = '';
            $youtube = trim((string) ($_POST['youtube_url'] ?? ''));
            if ($id > 0) {
                $cur = $pdo->prepare('SELECT image, youtube_url FROM sliders WHERE id = ?');
                $cur->execute([$id]);
                $row = $cur->fetch() ?: array();
                $image = (string) ($row['image'] ?? '');
                if ($youtube === '' && empty($_POST['youtube_url'])) {
                    $youtube = (string) ($row['youtube_url'] ?? '');
                }
            }
            $uploaded = handle_image_upload('image');
            if ($uploaded) {
                $image = $uploaded;
            }
            if (isset($_POST['remove_image'])) {
                $image = '';
            }
            $subtitle = trim((string) ($_POST['subtitle'] ?? ''));
            $buttonText = trim((string) ($_POST['button_text'] ?? ''));
            $buttonUrl = trim((string) ($_POST['button_url'] ?? ''));
            $sortOrder = (int) ($_POST['sort_order'] ?? 0);
            $status = ((string) ($_POST['status'] ?? 'publish') === 'publish') ? 'publish' : 'draft';
            if ($id > 0) {
                $pdo->prepare(
                    'UPDATE sliders SET title = ?, subtitle = ?, image = ?, youtube_url = ?, button_text = ?, button_url = ?, sort_order = ?, status = ? WHERE id = ?'
                )->execute([$title, $subtitle, $image, $youtube, $buttonText, $buttonUrl, $sortOrder, $status, $id]);
                flash_set('success', 'Slayt güncellendi.');
                cms_audit($pdo, 'update', 'slider', $id, $title);
                redirect('index.php?page=slider&id=' . $id);
            }
            $pdo->prepare(
                'INSERT INTO sliders (title, subtitle, image, youtube_url, button_text, button_url, sort_order, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([$title, $subtitle, $image, $youtube, $buttonText, $buttonUrl, $sortOrder, $status]);
            flash_set('success', 'Slider slaytı eklendi.');
            cms_audit($pdo, 'create', 'slider', (int) $pdo->lastInsertId(), $title);
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['slide_id'] ?? 0);
        if ($id > 0) {
            $gone = cms_row_title($pdo, 'sliders', $id);
            $pdo->prepare('DELETE FROM sliders WHERE id = ?')->execute([$id]);
            flash_set('success', 'Slayt silindi.');
            cms_audit($pdo, 'delete', 'slider', $id, $gone);
        }
    } elseif ($action === 'reorder') {
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : array();
        $upd = $pdo->prepare('UPDATE sliders SET sort_order = ? WHERE id = ?');
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
        flash_set('success', 'Sıra güncellendi.');
    }
    redirect('index.php?page=slider');
}

$editId = (int) ($_GET['id'] ?? 0);
$edit = null;
if ($editId > 0) {
    $st = $pdo->prepare('SELECT * FROM sliders WHERE id = ?');
    $st->execute([$editId]);
    $edit = $st->fetch() ?: null;
}
$slides = $pdo->query('SELECT * FROM sliders ORDER BY sort_order ASC, id DESC')->fetchAll();
$csrf = csrf_token();
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Slider</h1>
    <p class="mt-1 text-sm text-slate-500">Sabit görsel veya YouTube videosu arka planda döner. Yayımlanmış slaytlar ana sayfada görünür.</p>
</div>

<div class="mb-6 bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <form method="post" class="flex flex-wrap items-end gap-4">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="settings">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Otomatik geçiş (saniye)</label>
            <input type="number" name="slider_interval" min="3" max="15" value="<?= e(option_get($pdo, 'slider_interval', '6')) ?>" class="w-28 rounded-md border border-slate-200 px-3 py-2 text-sm">
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-700 pb-2">
            <input type="checkbox" name="slider_autoplay" value="1" <?= option_get($pdo, 'slider_autoplay', '1') === '1' ? 'checked' : '' ?>>
            Otomatik kaydır
        </label>
        <button type="submit" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Kaydet</button>
    </form>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 h-fit">
        <h2 class="text-sm font-semibold text-slate-900 mb-4"><?= $edit ? 'Slaytı düzenle' : 'Yeni slayt' ?></h2>
        <form method="post" enctype="multipart/form-data" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="slide_id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Başlık</label>
                <input name="title" required value="<?= e($edit ? (string) $edit['title'] : '') ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Alt yazı</label>
                <textarea name="subtitle" rows="3" class="cms-editor w-full rounded-md border border-slate-200 px-3 py-2 text-sm"><?= e($edit ? (string) ($edit['subtitle'] ?? '') : '') ?></textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Görsel</label>
                <?php if ($edit && !empty($edit['image'])): ?>
                    <img src="<?= e(media_src($edit['image'])) ?>" alt="" class="mb-2 h-24 w-full object-cover rounded-md">
                    <label class="mb-2 flex items-center gap-2 text-xs"><input type="checkbox" name="remove_image" value="1"> Görseli kaldır</label>
                <?php endif; ?>
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" class="block w-full text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Arka plan YouTube</label>
                <input name="youtube_url" value="<?= e($edit ? (string) ($edit['youtube_url'] ?? '') : '') ?>" placeholder="https://www.youtube.com/watch?v=…" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                <p class="mt-1 text-[11px] text-slate-400">Link girilirse slaydın arkasında video döner. Görsel yedek olarak kalır.</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Buton metni</label>
                <input name="button_text" value="<?= e($edit ? (string) ($edit['button_text'] ?? '') : '') ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Buton linki</label>
                <input name="button_url" value="<?= e($edit ? (string) ($edit['button_url'] ?? '') : '') ?>" placeholder="https://kodcu.site" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Sıra</label>
                <input name="sort_order" type="number" value="<?= $edit ? (int) $edit['sort_order'] : 0 ?>" class="w-24 rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Durum</label>
                <select name="status" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    <option value="publish" <?= (!$edit || $edit['status'] === 'publish') ? 'selected' : '' ?>>Yayımlanmış</option>
                    <option value="draft" <?= ($edit && $edit['status'] === 'draft') ? 'selected' : '' ?>>Taslak</option>
                </select>
            </div>
            <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800"><?= $edit ? 'Güncelle' : 'Ekle' ?></button>
            <?php if ($edit): ?><a class="ml-2 text-sm text-slate-500" href="index.php?page=slider">Vazgeç</a><?php endif; ?>
        </form>
    </div>

    <div class="space-y-4">
        <p class="text-xs text-slate-400">Sıralamak için sürükleyip bırakın.</p>
        <?php if (!$slides): ?>
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-8 text-center text-slate-500">Henüz slayt yok.</div>
        <?php else: ?>
            <ul class="space-y-4" data-sortable data-page="slider">
            <?php foreach ($slides as $slide): ?>
                <li draggable="true" data-id="<?= (int) $slide['id'] ?>" class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex gap-4 cursor-grab">
                    <span class="text-slate-400 select-none self-center">⋮⋮</span>
                    <?php if (!empty($slide['image'])): ?>
                        <img src="<?= e(media_src($slide['image'])) ?>" alt="" class="h-24 w-36 rounded-md object-cover bg-slate-100">
                    <?php else: ?>
                        <div class="h-24 w-36 rounded-md bg-slate-800 text-white text-xs flex items-center justify-center"><?= !empty($slide['youtube_url']) ? 'Video' : 'Görsel yok' ?></div>
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-slate-900"><?= e((string) $slide['title']) ?></p>
                        <p class="text-sm text-slate-500 line-clamp-2"><?= e(strip_tags((string) ($slide['subtitle'] ?? ''))) ?></p>
                        <p class="mt-1 text-xs text-slate-400">
                            Sıra <?= (int) $slide['sort_order'] ?> · <?= $slide['status'] === 'publish' ? 'Yayımlanmış' : 'Taslak' ?>
                            <?= !empty($slide['youtube_url']) ? ' · YouTube' : '' ?>
                        </p>
                    </div>
                    <a class="text-sm text-[#2271b1] self-start" href="index.php?page=slider&amp;id=<?= (int) $slide['id'] ?>">Düzenle</a>
                    <form method="post" onsubmit="return confirm('Slayt silinsin mi?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="slide_id" value="<?= (int) $slide['id'] ?>">
                        <button type="submit" class="text-sm text-red-600 hover:underline">Sil</button>
                    </form>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
<input type="hidden" id="menu-csrf" value="<?= e($csrf) ?>">
