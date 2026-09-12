<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'home') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

$catalog = cms_home_catalog();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'reorder') {
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : array();
        cms_home_save_order($pdo, $ids);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            cms_json_ok();
        }
        flash_set('success', 'Ana sayfa sırası kaydedildi.');
        redirect('index.php?page=home');
    }
    if ($action === 'toggle') {
        $key = preg_replace('/[^a-z_]/', '', strtolower((string) ($_POST['flag'] ?? ''))) ?? '';
        $allowed = array();
        foreach ($catalog as $row) {
            if (!empty($row['flag'])) {
                $allowed[] = (string) $row['flag'];
            }
        }
        if (in_array($key, $allowed, true)) {
            option_set($pdo, $key, isset($_POST['on']) ? '1' : '0');
            flash_set('success', 'Görünürlük güncellendi.');
        }
        redirect('index.php?page=home');
    }
}

$order = cms_home_modules($pdo);
$csrf = csrf_token();
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Ana sayfa</h1>
    <p class="mt-1 text-sm text-slate-500">Blokları sürükleyerek üste veya alta alın. Soldaki kutu ana sayfada gösterilsin mi, sağdaki link içeriği düzenler.</p>
</div>
<input type="hidden" id="menu-csrf" value="<?= e($csrf) ?>">
<ul class="max-w-2xl space-y-2" data-sortable data-page="home">
    <?php foreach ($order as $id): ?>
        <?php
        $row = $catalog[$id];
        $flag = (string) ($row['flag'] ?? '');
        $on = $flag === '' ? true : option_get($pdo, $flag, '1') === '1';
        ?>
        <li draggable="true" data-id="<?= e($id) ?>" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 cursor-grab">
            <span class="text-slate-400 select-none" aria-hidden="true">⋮⋮</span>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-900"><?= e((string) $row['label']) ?></p>
                <p class="text-[11px] <?= $on ? 'text-emerald-600' : 'text-slate-400' ?>"><?= $on ? 'Ana sayfada görünür' : 'Gizli' ?></p>
            </div>
            <?php if ($flag !== ''): ?>
                <form method="post" class="shrink-0">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="flag" value="<?= e($flag) ?>">
                    <?php if (!$on): ?>
                        <input type="hidden" name="on" value="1">
                    <?php endif; ?>
                    <button type="submit" class="rounded-md border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"><?= $on ? 'Gizle' : 'Göster' ?></button>
                </form>
            <?php endif; ?>
            <a class="shrink-0 text-sm text-[#2271b1] hover:underline" href="<?= e((string) $row['edit']) ?>">Düzenle</a>
        </li>
    <?php endforeach; ?>
</ul>
<p class="mt-4 max-w-2xl text-xs text-slate-400">Slider her zaman en üstte kalır (Header / Slider). Yeni eklenen bölümler listenin sonuna düşer; sürükleyip yerleştirin.</p>
