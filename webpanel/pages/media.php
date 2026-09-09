<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'media') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'upload') {
        $path = handle_image_upload('media_file');
        if ($path) {
            flash_set('success', 'Dosya yüklendi.');
        } elseif (!isset($_FILES['media_file']) || (int) ($_FILES['media_file']['error'] ?? 0) === UPLOAD_ERR_NO_FILE) {
            flash_set('error', 'Bir dosya seçin.');
        }
    } elseif ($action === 'delete') {
        $file = basename((string) ($_POST['file'] ?? ''));
        $full = UPLOAD_DIR . $file;
        $realUpload = realpath(UPLOAD_DIR);
        $realFile = $file !== '' ? realpath($full) : false;
        if ($realUpload && $realFile && strpos(str_replace('\\', '/', $realFile), str_replace('\\', '/', $realUpload) . '/') === 0 && is_file($realFile)) {
            unlink($realFile);
            flash_set('success', 'Dosya silindi.');
        } else {
            flash_set('error', 'Dosya silinemedi.');
        }
    }
    redirect('index.php?page=media');
}

$files = [];
$entries = scandir(UPLOAD_DIR);
if ($entries !== false) {
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..' || $entry === '.htaccess' || $entry === 'index.html') {
            continue;
        }
        $full = UPLOAD_DIR . $entry;
        if (!is_file($full)) {
            continue;
        }
        $files[] = [
            'name' => $entry,
            'url'  => UPLOAD_URL . $entry,
            'size' => filesize($full),
            'time' => filemtime($full),
        ];
    }
}
usort($files, static function ($a, $b) {
    return $b['time'] <=> $a['time'];
});
?>
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Medya Kütüphanesi</h1>
        <p class="mt-1 text-sm text-slate-500"><?= count($files) ?> dosya</p>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 mb-6">
    <form method="post" enctype="multipart/form-data" class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="upload">
        <div class="flex-1">
            <label class="mb-1 block text-sm font-medium text-slate-700" for="media_file">Dosya yükle</label>
            <input id="media_file" name="media_file" type="file" accept="image/jpeg,image/png,image/webp,image/gif"
                   class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium">
        </div>
        <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Yükle</button>
    </form>
</div>

<?php if (!$files): ?>
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 px-4 py-12 text-center text-slate-500">
        Kütüphanede henüz görsel yok.
    </div>
<?php else: ?>
    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-4">
        <?php foreach ($files as $file): ?>
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                <img src="<?= e($file['url']) ?>" alt="" class="h-32 w-full object-cover bg-slate-100">
                <div class="p-2">
                    <p class="truncate text-xs text-slate-600" title="<?= e($file['name']) ?>"><?= e($file['name']) ?></p>
                    <p class="text-[11px] text-slate-400"><?= number_format(((int) $file['size']) / 1024, 1, ',', '.') ?> KB</p>
                    <form method="post" class="mt-2" onsubmit="return confirm('Dosya silinsin mi?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="file" value="<?= e($file['name']) ?>">
                        <button type="submit" class="text-xs text-red-600 hover:underline">Sil</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
