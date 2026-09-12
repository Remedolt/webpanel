<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'buttons') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

$icons = [
    'phone' => 'Telefon',
    'whatsapp' => 'WhatsApp',
    'email' => 'E-posta',
    'telegram' => 'Telegram',
    'instagram' => 'Instagram',
    'link' => 'Özel link',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'reorder') {
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : array();
        $upd = $pdo->prepare('UPDATE float_buttons SET sort_order = ? WHERE id = ?');
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
        redirect('index.php?page=buttons');
    }
    if ($action === 'save') {
        $id = (int) ($_POST['button_id'] ?? 0);
        $label = trim((string) ($_POST['label'] ?? ''));
        $url = trim((string) ($_POST['url'] ?? ''));
        $icon = option_pick($_POST['icon_type'] ?? 'phone', array_keys($icons), 'phone');
        $color = hex_color($_POST['color'] ?? '#2563eb', $icon === 'whatsapp' ? '#25D366' : '#2563eb');
        $status = ((string) ($_POST['status'] ?? 'publish') === 'publish') ? 'publish' : 'draft';
        if ($label === '') {
            flash_set('error', 'Buton adı zorunludur.');
        } else {
            if ($id > 0) {
                $pdo->prepare('UPDATE float_buttons SET label = ?, url = ?, icon_type = ?, color = ?, status = ? WHERE id = ?')
                    ->execute([$label, $url, $icon, $color, $status, $id]);
                flash_set('success', 'Buton güncellendi.');
                redirect('index.php?page=buttons&id=' . $id);
            }
            $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM float_buttons')->fetchColumn();
            $pdo->prepare('INSERT INTO float_buttons (label, url, icon_type, color, sort_order, status) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$label, $url, $icon, $color, $max + 1, $status]);
            $newId = (int) $pdo->lastInsertId();
            flash_set('success', 'Buton eklendi. Yayınlayınca sitenin sağ altında görünür.');
            redirect('index.php?page=buttons' . ($newId > 0 ? '&id=' . $newId : ''));
        }
        redirect('index.php?page=buttons');
    }
    if ($action === 'delete') {
        $id = (int) ($_POST['button_id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM float_buttons WHERE id = ?')->execute([$id]);
            flash_set('success', 'Buton silindi.');
        }
        redirect('index.php?page=buttons');
    }
}

$editId = (int) ($_GET['id'] ?? 0);
$edit = null;
if ($editId > 0) {
    $st = $pdo->prepare('SELECT * FROM float_buttons WHERE id = ?');
    $st->execute([$editId]);
    $edit = $st->fetch() ?: null;
}
$rows = $pdo->query('SELECT * FROM float_buttons ORDER BY sort_order ASC, id ASC')->fetchAll();
$csrf = csrf_token();
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Sağ butonlar</h1>
    <p class="mt-1 text-sm text-slate-500">Sitenin sağ altında sabit duran telefon / WhatsApp gibi yuvarlak butonlar.</p>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 h-fit">
        <h2 class="text-sm font-semibold mb-4"><?= $edit ? 'Butonu düzenle' : 'Yeni buton' ?></h2>
        <form method="post" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="button_id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
            <div>
                <label class="mb-1 block text-sm font-medium">Ad (erişilebilirlik)</label>
                <input name="label" required value="<?= e($edit ? (string) $edit['label'] : '') ?>" placeholder="WhatsApp" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Tip</label>
                <select name="icon_type" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    <?php foreach ($icons as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= ($edit && $edit['icon_type'] === $key) ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Numara / link</label>
                <input name="url" value="<?= e($edit ? (string) $edit['url'] : '') ?>" placeholder="905551112233 veya https://..." class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                <p class="mt-1 text-[11px] text-slate-400">Telefon: 0212... WhatsApp: 90555... (ülke koduyla, 0 olmadan)</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Renk</label>
                <input type="color" name="color" value="<?= e($edit ? (string) $edit['color'] : '#2563eb') ?>" class="h-10 w-full border border-slate-200 rounded">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Durum</label>
                <select name="status" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    <option value="publish" <?= (!$edit || $edit['status'] === 'publish') ? 'selected' : '' ?>>Yayımlanmış</option>
                    <option value="draft" <?= ($edit && $edit['status'] === 'draft') ? 'selected' : '' ?>>Taslak</option>
                </select>
            </div>
            <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white"><?= $edit ? 'Güncelle' : 'Ekle' ?></button>
            <?php if ($edit): ?>
                <a href="index.php?page=buttons" class="ml-2 text-sm text-slate-500">Vazgeç</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
        <ul class="space-y-2" data-sortable data-page="buttons">
            <?php foreach ($rows as $row): ?>
                <li draggable="true" data-id="<?= (int) $row['id'] ?>" class="flex items-center gap-3 rounded-md border border-slate-200 px-3 py-2 cursor-grab">
                    <span class="text-slate-400">⋮⋮</span>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full text-white overflow-hidden" style="background:<?= e($row['color']) ?>"><?= cms_float_icon((string) $row['icon_type']) ?></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium"><?= e((string) $row['label']) ?> · <?= e($icons[$row['icon_type']] ?? $row['icon_type']) ?></p>
                        <p class="text-[11px] text-slate-400 truncate"><?= e((string) $row['url']) ?: 'Link boş — yayınlanınca görünmez' ?></p>
                    </div>
                    <?php if ($row['status'] === 'publish'): ?>
                        <span class="text-xs text-emerald-700">Yayında</span>
                    <?php else: ?>
                        <span class="text-xs text-slate-400">Taslak</span>
                    <?php endif; ?>
                    <a class="text-sm text-[#2271b1]" href="index.php?page=buttons&amp;id=<?= (int) $row['id'] ?>">Düzenle</a>
                    <form method="post" onsubmit="return confirm('Silinsin mi?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="button_id" value="<?= (int) $row['id'] ?>">
                        <button class="text-sm text-red-600" type="submit">Sil</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<input type="hidden" id="menu-csrf" value="<?= e($csrf) ?>">
