<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'footer') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

$footerTypes = [
    'simple' => 'Tek satır',
    'columns' => 'Kolonlar (menü grupları)',
    'centered' => 'Ortalı',
    'mega' => 'Mega (marka + sayfalar + menü)',
    'stacked' => 'Üst üste yığın',
    'cta' => 'Çağrı şeridi + linkler',
];
$itemStyles = [
    'link' => 'Düz link',
    'dropdown' => 'Alt başlık + çocuklar',
    'button' => 'Buton',
    'highlight' => 'Vurgulu',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'appearance') {
        option_set($pdo, 'footer_type', option_pick($_POST['footer_type'] ?? 'simple', array_keys($footerTypes), 'simple'));
        option_set($pdo, 'footer_size', option_pick($_POST['footer_size'] ?? 'md', ['sm', 'md', 'lg'], 'md'));
        option_set($pdo, 'footer_bg', hex_color($_POST['footer_bg'] ?? '', '#0f172a'));
        option_set($pdo, 'footer_text', hex_color($_POST['footer_text'] ?? '', '#94a3b8'));
        option_set($pdo, 'footer_text_custom', trim((string) ($_POST['footer_text_custom'] ?? '')));
        option_set($pdo, 'footer_show_pages', isset($_POST['footer_show_pages']) ? '1' : '0');
        flash_set('success', 'Footer görünümü kaydedildi.');
        redirect('index.php?page=footer');
    }
    if ($action === 'reorder') {
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : array();
        $upd = $pdo->prepare('UPDATE menus SET sort_order = ? WHERE id = ? AND location = ?');
        $i = 1;
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $upd->execute([$i, $id, 'footer']);
                $i++;
            }
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            cms_json_ok();
        }
        redirect('index.php?page=footer');
    }
    if ($action === 'create') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $url = trim((string) ($_POST['url'] ?? ''));
        if ($title === '' || $url === '') {
            flash_set('error', 'Başlık ve link zorunludur.');
        } else {
            $style = option_pick($_POST['item_style'] ?? 'link', array_keys($itemStyles), 'link');
            $max = (int) $pdo->query("SELECT COALESCE(MAX(sort_order), 0) FROM menus WHERE location = 'footer'")->fetchColumn();
            $ins = $pdo->prepare(
                'INSERT INTO menus (title, url, parent_id, sort_order, item_style, status, location) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([$title, $url, (int) ($_POST['parent_id'] ?? 0), $max + 1, $style, 'publish', 'footer']);
            flash_set('success', 'Footer öğesi eklendi.');
        }
        redirect('index.php?page=footer');
    }
    if ($action === 'delete') {
        $id = (int) ($_POST['menu_id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM menus WHERE (id = ? OR parent_id = ?) AND location = 'footer'")->execute([$id, $id]);
            flash_set('success', 'Öğe silindi.');
        }
        redirect('index.php?page=footer');
    }
}

$items = $pdo->query("SELECT * FROM menus WHERE location = 'footer' ORDER BY sort_order ASC, id ASC")->fetchAll();
$parents = $pdo->query("SELECT id, title FROM menus WHERE location = 'footer' AND parent_id = 0 ORDER BY sort_order ASC")->fetchAll();
$publishedPages = $pdo->query("SELECT title, slug FROM site_pages WHERE status = 'publish' ORDER BY title ASC")->fetchAll();

$fType = option_get($pdo, 'footer_type', 'simple');
$fSize = option_get($pdo, 'footer_size', 'md');
$fBg = option_get($pdo, 'footer_bg', '#0f172a');
$fText = option_get($pdo, 'footer_text', '#94a3b8');
$fCustom = option_get($pdo, 'footer_text_custom', '');
$fPages = option_get($pdo, 'footer_show_pages', '0');
$csrf = csrf_token();
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Footer</h1>
    <p class="mt-1 text-sm text-slate-500">Alt bilgi şeridi, renkler ve linkler. Linkler yalnızca burada eklediklerinizdir; tüm sayfalar otomatik gelmez.</p>
</div>

<div class="flex flex-col lg:flex-row gap-6 items-start">
    <aside class="w-full lg:w-80 shrink-0 lg:sticky lg:top-20">
        <form method="post" class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="appearance">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Tip</label>
                <select name="footer_type" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    <?php foreach ($footerTypes as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $fType === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Boyut</label>
                <select name="footer_size" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    <option value="sm" <?= $fSize === 'sm' ? 'selected' : '' ?>>Küçük</option>
                    <option value="md" <?= $fSize === 'md' ? 'selected' : '' ?>>Orta</option>
                    <option value="lg" <?= $fSize === 'lg' ? 'selected' : '' ?>>Büyük</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <label class="text-xs text-slate-500">Arka plan<input type="color" name="footer_bg" value="<?= e($fBg) ?>" class="mt-1 h-9 w-full border border-slate-200 rounded"></label>
                <label class="text-xs text-slate-500">Yazı<input type="color" name="footer_text" value="<?= e($fText) ?>" class="mt-1 h-9 w-full border border-slate-200 rounded"></label>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Alt yazı / telif</label>
                <input name="footer_text_custom" value="<?= e($fCustom) ?>" placeholder="© <?= e(date('Y')) ?> Kodcu" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <label class="flex items-start gap-2 text-sm text-slate-700">
                <input type="checkbox" name="footer_show_pages" value="1" class="mt-0.5" <?= $fPages === '1' ? 'checked' : '' ?>>
                <span>Tüm yayımlanmış sayfaları da otomatik ekle <span class="block text-xs text-slate-400">Kapalı kalsın; linkleri aşağıdan kendiniz ekleyin.</span></span>
            </label>
            <button class="w-full rounded-md bg-[#2271b1] px-3 py-2 text-sm font-semibold text-white hover:bg-blue-800" type="submit">Footer’ı kaydet</button>
        </form>
    </aside>

    <div class="flex-1 space-y-6 min-w-0">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
            <h2 class="text-sm font-semibold text-slate-900 mb-1">Footer menüsü</h2>
            <p class="text-xs text-slate-400 mb-4">Sadece burada eklediğiniz linkler görünür. Sıralamak için ⋮⋮ tutup sürükleyin.</p>
            <?php if ($publishedPages): ?>
                <p class="text-[11px] text-slate-500 mb-3">Hazır linkler:
                    <?php foreach ($publishedPages as $p): ?>
                        <span class="inline-block mr-3 font-mono"><?= e((string) $p['title']) ?> → <?= e(page_permalink($p['slug'])) ?></span>
                    <?php endforeach; ?>
                    <span class="inline-block mr-3 font-mono">İletişim → <?= e(contact_permalink()) ?></span>
                    <span class="inline-block mr-3 font-mono">Hizmetler → <?= e(services_permalink()) ?></span>
                </p>
            <?php endif; ?>
            <?php if (!$items): ?>
                <p class="text-sm text-slate-500 px-3 py-6">Henüz öğe yok. Aşağıdan ekleyin.</p>
            <?php else: ?>
                <ul class="space-y-2" data-sortable data-page="footer" data-location="footer">
                    <?php foreach ($items as $row): ?>
                        <li draggable="true" data-id="<?= (int) $row['id'] ?>" class="flex items-center gap-3 rounded-md border border-slate-200 bg-white px-3 py-2 cursor-grab <?= ((int) $row['parent_id'] > 0) ? 'pl-8' : '' ?>">
                            <span class="text-slate-400 select-none" title="Sürükle">⋮⋮</span>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-slate-900 text-sm"><?= e((string) $row['title']) ?></p>
                                <p class="text-[11px] text-slate-400 truncate"><?= e((string) $row['url']) ?> · <?= e((string) ($itemStyles[$row['item_style']] ?? $row['item_style'])) ?></p>
                            </div>
                            <form method="post" onsubmit="return confirm('Silinsin mi?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="menu_id" value="<?= (int) $row['id'] ?>">
                                <button class="text-xs text-red-600 hover:underline" type="submit">Sil</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
            <h2 class="text-sm font-semibold text-slate-900 mb-4">Yeni öğe</h2>
            <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Başlık</label>
                    <input name="title" required class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Link</label>
                    <input name="url" required placeholder="<?= e(public_url()) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Üst öğe (kolon başlığı)</label>
                    <select name="parent_id" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                        <option value="0">Yok</option>
                        <?php foreach ($parents as $parent): ?>
                            <option value="<?= (int) $parent['id'] ?>"><?= e((string) $parent['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Öğe tipi</label>
                    <select name="item_style" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                        <?php foreach ($itemStyles as $key => $label): ?>
                            <option value="<?= e($key) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>
<input type="hidden" id="menu-csrf" value="<?= e($csrf) ?>">
