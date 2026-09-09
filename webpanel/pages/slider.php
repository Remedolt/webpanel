<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'slider') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

$isAjax = isset($_POST['ajax'])
    || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

$sliderJson = static function (array $payload, int $code = 200) : void {
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
};

$nextSortOrder = static function (PDO $pdo) : int {
    $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), -1) FROM slides')->fetchColumn();
    return $max + 1;
};

$insertSlide = static function (PDO $pdo, array $row, int $sort) : int {
    $ins = $pdo->prepare(
        'INSERT INTO slides (title, subtitle, button_text, link_url, image, sort_order, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([
        $row['title'],
        $row['subtitle'],
        $row['button_text'],
        $row['link_url'],
        $row['image'],
        $sort,
        $row['is_active'],
    ]);
    return (int) $pdo->lastInsertId();
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'settings') {
        $enabled = isset($_POST['slider_enabled']) ? '1' : '0';
        $autoplay = isset($_POST['slider_autoplay']) ? '1' : '0';
        $interval = (int) ($_POST['slider_interval'] ?? 5000);
        if ($interval < 2000) {
            $interval = 2000;
        }
        if ($interval > 20000) {
            $interval = 20000;
        }
        option_set($pdo, 'slider_enabled', $enabled);
        option_set($pdo, 'slider_autoplay', $autoplay);
        option_set($pdo, 'slider_interval', (string) $interval);
        flash_set('success', 'Slider ayarları kaydedildi.');
        redirect('index.php?page=slider');
    }

    if ($action === 'create') {
        $paths = handle_multiple_image_uploads('slide_images');
        $single = handle_image_upload('slide_image');
        if ($single) {
            $paths[] = $single;
        }
        $title = trim((string) ($_POST['title'] ?? ''));
        $subtitle = trim((string) ($_POST['subtitle'] ?? ''));
        $buttonText = trim((string) ($_POST['button_text'] ?? ''));
        $linkUrl = sanitize_slide_url((string) ($_POST['link_url'] ?? ''));
        if ($paths === [] && $title === '') {
            $msg = 'Bir görsel sürükleyin veya en az bir başlık yazın.';
            if ($isAjax) {
                $sliderJson(['ok' => false, 'error' => $msg], 400);
            }
            flash_set('error', $msg);
            redirect('index.php?page=slider');
        }
        $sort = $nextSortOrder($pdo);
        if ($paths === []) {
            $insertSlide($pdo, [
                'title'       => $title,
                'subtitle'    => $subtitle,
                'button_text' => $buttonText,
                'link_url'    => $linkUrl,
                'image'       => '',
                'is_active'   => 1,
            ], $sort);
        } else {
            foreach ($paths as $i => $path) {
                $insertSlide($pdo, [
                    'title'       => $i === 0 ? $title : '',
                    'subtitle'    => $i === 0 ? $subtitle : '',
                    'button_text' => $i === 0 ? $buttonText : '',
                    'link_url'    => $i === 0 ? $linkUrl : '',
                    'image'       => $path,
                    'is_active'   => 1,
                ], $sort + $i);
            }
        }
        $msg = count($paths) > 1 ? 'Slaytlar eklendi. Sırayı sürükleyerek değiştirin.' : 'Slayt eklendi.';
        if ($isAjax) {
            $sliderJson(['ok' => true, 'message' => $msg]);
        }
        flash_set('success', $msg);
        redirect('index.php?page=slider');
    }

    if ($action === 'update') {
        $id = (int) ($_POST['slide_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT id, image FROM slides WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $found = $stmt->fetch();
        if (!$found) {
            if ($isAjax) {
                $sliderJson(['ok' => false, 'error' => 'Slayt bulunamadı.'], 404);
            }
            flash_set('error', 'Slayt bulunamadı.');
            redirect('index.php?page=slider');
        }
        $title = trim((string) ($_POST['title'] ?? ''));
        $subtitle = trim((string) ($_POST['subtitle'] ?? ''));
        $buttonText = trim((string) ($_POST['button_text'] ?? ''));
        $linkUrl = sanitize_slide_url((string) ($_POST['link_url'] ?? ''));
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $image = (string) ($found['image'] ?? '');
        $hadUpload = isset($_FILES['slide_image'])
            && (int) ($_FILES['slide_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        $uploaded = handle_image_upload('slide_image');
        if ($hadUpload && $uploaded === null) {
            redirect('index.php?page=slider');
        }
        if ($uploaded) {
            $image = $uploaded;
        }
        if (isset($_POST['remove_image'])) {
            $image = '';
        }
        $upd = $pdo->prepare(
            'UPDATE slides SET title = ?, subtitle = ?, button_text = ?, link_url = ?, image = ?, is_active = ? WHERE id = ?'
        );
        $upd->execute([$title, $subtitle, $buttonText, $linkUrl, $image, $isActive, $id]);
        if ($isAjax) {
            $sliderJson(['ok' => true, 'message' => 'Slayt güncellendi.']);
        }
        flash_set('success', 'Slayt güncellendi.');
        redirect('index.php?page=slider');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['slide_id'] ?? 0);
        if ($id > 0) {
            $del = $pdo->prepare('DELETE FROM slides WHERE id = ?');
            $del->execute([$id]);
        }
        if ($isAjax) {
            $sliderJson(['ok' => true]);
        }
        flash_set('success', 'Slayt silindi.');
        redirect('index.php?page=slider');
    }

    if ($action === 'reorder') {
        $order = $_POST['order'] ?? [];
        if (is_string($order)) {
            $decoded = json_decode($order, true);
            $order = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($order)) {
            $order = [];
        }
        $upd = $pdo->prepare('UPDATE slides SET sort_order = ? WHERE id = ?');
        $position = 0;
        foreach ($order as $rawId) {
            $id = (int) $rawId;
            if ($id <= 0) {
                continue;
            }
            $upd->execute([$position, $id]);
            $position++;
        }
        if ($isAjax) {
            $sliderJson(['ok' => true]);
        }
        flash_set('success', 'Sıra kaydedildi.');
        redirect('index.php?page=slider');
    }

    if ($action === 'move') {
        $id = (int) ($_POST['slide_id'] ?? 0);
        $direction = (string) ($_POST['direction'] ?? '');
        $slides = $pdo->query('SELECT id FROM slides ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_COLUMN);
        $ids = array_map('intval', $slides);
        $index = array_search($id, $ids, true);
        if ($index !== false) {
            $swapWith = $direction === 'up' ? $index - 1 : $index + 1;
            if (isset($ids[$swapWith])) {
                $tmp = $ids[$index];
                $ids[$index] = $ids[$swapWith];
                $ids[$swapWith] = $tmp;
                $upd = $pdo->prepare('UPDATE slides SET sort_order = ? WHERE id = ?');
                foreach ($ids as $pos => $slideId) {
                    $upd->execute([$pos, $slideId]);
                }
            }
        }
        flash_set('success', 'Sıra güncellendi.');
        redirect('index.php?page=slider');
    }

    if ($isAjax) {
        $sliderJson(['ok' => false, 'error' => 'Geçersiz istek.'], 400);
    }
    redirect('index.php?page=slider');
}

$slides = $pdo->query(
    'SELECT id, title, subtitle, button_text, link_url, image, sort_order, is_active, created_at
     FROM slides
     ORDER BY sort_order ASC, id ASC'
)->fetchAll();
$sliderEnabled = option_get($pdo, 'slider_enabled', '1') === '1';
$sliderAutoplay = option_get($pdo, 'slider_autoplay', '1') === '1';
$sliderInterval = option_get($pdo, 'slider_interval', '5000');
$csrf = csrf_token();
?>
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Slider</h1>
        <p class="mt-1 text-sm text-slate-500">Ana sayfa slaytlarını sürükleyerek sıralayın. Görselleri kutuya bırakarak yeni slayt ekleyin.</p>
    </div>
    <a href="<?= e(public_url()) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
        Sitede gör
    </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_18rem] gap-6 items-start">
    <div class="space-y-6">
        <form id="slider-create-form" method="post" enctype="multipart/form-data" class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div id="slider-dropzone"
                 class="relative rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center transition-colors hover:border-[#2271b1] hover:bg-blue-50/40">
                <input id="slide_images" name="slide_images[]" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple
                       class="absolute inset-0 cursor-pointer opacity-0">
                <p class="text-sm font-semibold text-slate-800">Görselleri buraya sürükleyip bırakın</p>
                <p class="mt-1 text-xs text-slate-500">veya tıklayıp JPEG, PNG, WebP, GIF seçin (en fazla 5 MB)</p>
                <p id="slider-drop-names" class="mt-3 hidden text-xs text-slate-600"></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="new-title">Başlık</label>
                    <input id="new-title" name="title" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200" placeholder="Örn. Yeni yazılar">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="new-button">Buton yazısı</label>
                    <input id="new-button" name="button_text" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200" placeholder="Örn. Devamını oku">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="new-subtitle">Alt yazı</label>
                    <input id="new-subtitle" name="subtitle" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="new-link">Bağlantı</label>
                    <input id="new-link" name="link_url" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200" placeholder="https://… veya /yazi/…">
                </div>
            </div>
            <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Slayt ekle</button>
        </form>

        <?php if (!$slides): ?>
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 px-4 py-12 text-center text-slate-500">
                Henüz slayt yok. Yukarıdaki alana bir görsel bırakın.
            </div>
        <?php else: ?>
            <ol id="slider-list" class="space-y-3" data-csrf="<?= e($csrf) ?>">
                <?php foreach ($slides as $index => $slide): ?>
                    <li class="slider-item bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden" data-id="<?= (int) $slide['id'] ?>">
                        <form method="post" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-[7rem_minmax(0,1fr)]">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="slide_id" value="<?= (int) $slide['id'] ?>">
                            <div class="relative bg-slate-900 min-h-[7rem]">
                                <?php if (!empty($slide['image'])): ?>
                                    <img src="<?= e(media_src((string) $slide['image'])) ?>" alt="" class="h-full w-full object-cover min-h-[7rem]">
                                <?php else: ?>
                                    <div class="flex h-full min-h-[7rem] items-center justify-center text-[11px] text-slate-400 px-2 text-center">Görsel yok</div>
                                <?php endif; ?>
                            </div>
                            <div class="p-3 space-y-2">
                                <div class="flex items-center gap-2">
                                    <button type="button" data-drag-handle class="cursor-grab rounded-md border border-slate-200 px-2 py-1 text-slate-500 hover:bg-slate-50" aria-label="Sürükle" title="Sürükleyerek sırala">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5M3.75 15h16.5" />
                                        </svg>
                                    </button>
                                    <span class="text-[11px] uppercase tracking-wide text-slate-400">#<?= (int) $index + 1 ?></span>
                                    <label class="ml-auto inline-flex items-center gap-2 text-xs text-slate-600">
                                        <input type="checkbox" name="is_active" value="1" <?= ((int) $slide['is_active'] === 1) ? 'checked' : '' ?>>
                                        Yayında
                                    </label>
                                </div>
                                <input name="title" value="<?= e((string) $slide['title']) ?>" placeholder="Başlık"
                                       class="w-full rounded-md border border-slate-200 px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                                <input name="subtitle" value="<?= e((string) $slide['subtitle']) ?>" placeholder="Alt yazı"
                                       class="w-full rounded-md border border-slate-200 px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <input name="button_text" value="<?= e((string) $slide['button_text']) ?>" placeholder="Buton yazısı"
                                           class="w-full rounded-md border border-slate-200 px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                                    <input name="link_url" value="<?= e((string) $slide['link_url']) ?>" placeholder="Bağlantı"
                                           class="w-full rounded-md border border-slate-200 px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                                </div>
                                <div class="flex flex-wrap items-center gap-2 pt-1">
                                    <label class="text-xs text-slate-500">
                                        Görsel değiştir
                                        <input name="slide_image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="mt-1 block text-xs">
                                    </label>
                                    <?php if (!empty($slide['image'])): ?>
                                        <label class="text-xs text-slate-500 inline-flex items-center gap-1">
                                            <input type="checkbox" name="remove_image" value="1"> Görseli kaldır
                                        </label>
                                    <?php endif; ?>
                                    <div class="ml-auto flex items-center gap-2">
                                        <button type="submit" class="rounded-md bg-[#2271b1] px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-800">Kaydet</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <div class="flex items-center justify-between border-t border-slate-100 px-3 py-2 bg-slate-50">
                            <div class="flex gap-1">
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="move">
                                    <input type="hidden" name="slide_id" value="<?= (int) $slide['id'] ?>">
                                    <input type="hidden" name="direction" value="up">
                                    <button type="submit" class="rounded-md border border-slate-200 bg-white px-2 py-1 text-xs text-slate-600 hover:bg-slate-100" <?= $index === 0 ? 'disabled' : '' ?>>Yukarı</button>
                                </form>
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="move">
                                    <input type="hidden" name="slide_id" value="<?= (int) $slide['id'] ?>">
                                    <input type="hidden" name="direction" value="down">
                                    <button type="submit" class="rounded-md border border-slate-200 bg-white px-2 py-1 text-xs text-slate-600 hover:bg-slate-100" <?= $index === count($slides) - 1 ? 'disabled' : '' ?>>Aşağı</button>
                                </form>
                            </div>
                            <form method="post" onsubmit="return confirm('Bu slayt silinsin mi?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="slide_id" value="<?= (int) $slide['id'] ?>">
                                <button type="submit" class="text-xs text-red-600 hover:underline">Sil</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>

    <aside class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 h-fit">
        <h2 class="text-sm font-semibold text-slate-900">Görünüm</h2>
        <p class="mt-1 text-xs text-slate-500 mb-4">Ana sayfanın en üstünde gösterilir. Ziyaretçi slaytı sürükleyerek de değiştirebilir.</p>
        <form method="post" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="settings">
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="slider_enabled" value="1" <?= $sliderEnabled ? 'checked' : '' ?>>
                Ana sayfada göster
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="slider_autoplay" value="1" <?= $sliderAutoplay ? 'checked' : '' ?>>
                Otomatik geçiş
            </label>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="slider_interval">Geçiş süresi (ms)</label>
                <input id="slider_interval" name="slider_interval" type="number" min="2000" max="20000" step="500" value="<?= e($sliderInterval) ?>"
                       class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>
            <button type="submit" class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Ayarları kaydet</button>
        </form>
        <p class="mt-4 text-xs text-slate-400"><?= count($slides) ?> slayt · <?= (int) array_sum(array_map(static function ($s) { return (int) $s['is_active']; }, $slides)) ?> yayında</p>
    </aside>
</div>
<script src="js/slider-admin.js"></script>
