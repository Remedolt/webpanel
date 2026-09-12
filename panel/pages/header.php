<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'header') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

$headerTypes = [
    'top' => 'Klasik üst bar',
    'centered' => 'Ortalı logo + menü',
    'split' => 'Logo / menü / buton',
    'stacked' => 'İki satır (logo + menü)',
    'overlay' => 'Slider üstüne şeffaf',
    'left' => 'Sol dikey menü',
    'right' => 'Sağ dikey menü',
];
$itemStyles = [
    'link' => 'Düz link',
    'dropdown' => 'Açılır menü',
    'button' => 'Buton',
    'highlight' => 'Vurgulu',
];
$mobileTypes = [
    'drawer' => 'Soldan çekmece',
    'drawer-right' => 'Sağdan çekmece',
    'fullscreen' => 'Tam ekran',
    'dropdown' => 'Başlığın altından açılır',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'appearance') {
        option_set($pdo, 'header_type', option_pick($_POST['header_type'] ?? 'top', array_keys($headerTypes), 'top'));
        option_set($pdo, 'header_size', option_pick($_POST['header_size'] ?? 'md', ['sm', 'md', 'lg'], 'md'));
        option_set($pdo, 'header_width', option_pick($_POST['header_width'] ?? 'boxed', ['boxed', 'full'], 'boxed'));
        option_set($pdo, 'header_sticky', isset($_POST['header_sticky']) ? '1' : '0');
        option_set($pdo, 'header_bg', hex_color($_POST['header_bg'] ?? '', '#0f172a'));
        option_set($pdo, 'header_text', hex_color($_POST['header_text'] ?? '', '#e2e8f0'));
        option_set($pdo, 'header_accent', hex_color($_POST['header_accent'] ?? '', '#38bdf8'));
        option_set($pdo, 'mobile_menu_type', option_pick($_POST['mobile_menu_type'] ?? 'drawer', array_keys($mobileTypes), 'drawer'));
        option_set($pdo, 'mobile_btn_style', option_pick($_POST['mobile_btn_style'] ?? 'hamburger', ['hamburger', 'text', 'both'], 'hamburger'));
        option_set($pdo, 'mobile_show_pages', isset($_POST['mobile_show_pages']) ? '1' : '0');
        option_set($pdo, 'mobile_show_home', isset($_POST['mobile_show_home']) ? '1' : '0');
        flash_set('success', 'Header görünümü kaydedildi.');
        redirect('index.php?page=header');
    }
    if ($action === 'logo') {
        cms_save_logo_field($pdo, 'site_logo');
        cms_save_logo_field($pdo, 'site_logo_retina');
        cms_save_logo_field($pdo, 'site_logo_mobile');
        cms_save_logo_field($pdo, 'site_logo_mobile_retina');
        option_set($pdo, 'site_logo_alt', trim((string) ($_POST['site_logo_alt'] ?? '')));
        $h = (int) ($_POST['site_logo_height'] ?? 40);
        if ($h < 20) {
            $h = 20;
        }
        if ($h > 120) {
            $h = 120;
        }
        option_set($pdo, 'site_logo_height', (string) $h);
        $mh = (int) ($_POST['site_logo_mobile_height'] ?? 32);
        if ($mh < 16) {
            $mh = 16;
        }
        if ($mh > 80) {
            $mh = 80;
        }
        option_set($pdo, 'site_logo_mobile_height', (string) $mh);
        option_set($pdo, 'site_logo_show_title', isset($_POST['site_logo_show_title']) ? '1' : '0');
        option_set($pdo, 'site_logo_link', trim((string) ($_POST['site_logo_link'] ?? '')));
        flash_set('success', 'Header logosu güncellendi.');
        redirect('index.php?page=header');
    }
    if ($action === 'reorder') {
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : array();
        $upd = $pdo->prepare('UPDATE menus SET sort_order = ? WHERE id = ? AND location = ?');
        $i = 1;
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $upd->execute([$i, $id, 'header']);
                $i++;
            }
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            cms_json_ok();
        }
        redirect('index.php?page=header');
    }
    if ($action === 'create') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $url = trim((string) ($_POST['url'] ?? ''));
        if ($title === '' || $url === '') {
            flash_set('error', 'Başlık ve link zorunludur.');
        } else {
            $style = option_pick($_POST['item_style'] ?? 'link', array_keys($itemStyles), 'link');
            $max = (int) $pdo->query("SELECT COALESCE(MAX(sort_order), 0) FROM menus WHERE location = 'header'")->fetchColumn();
            $ins = $pdo->prepare(
                'INSERT INTO menus (title, url, parent_id, sort_order, item_style, status, location) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([$title, $url, (int) ($_POST['parent_id'] ?? 0), $max + 1, $style, 'publish', 'header']);
            flash_set('success', 'Header öğesi eklendi. Sıralamak için sürükleyin.');
        }
        redirect('index.php?page=header');
    }
    if ($action === 'delete') {
        $id = (int) ($_POST['menu_id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM menus WHERE (id = ? OR parent_id = ?) AND location = 'header'")->execute([$id, $id]);
            flash_set('success', 'Öğe silindi.');
        }
        redirect('index.php?page=header');
    }
}

$items = $pdo->query("SELECT * FROM menus WHERE location = 'header' ORDER BY sort_order ASC, id ASC")->fetchAll();
$parents = $pdo->query("SELECT id, title FROM menus WHERE location = 'header' AND parent_id = 0 ORDER BY sort_order ASC")->fetchAll();
$publishedPages = $pdo->query("SELECT title, slug FROM site_pages WHERE status = 'publish' ORDER BY title ASC")->fetchAll();

$hType = option_get($pdo, 'header_type', 'top');
$hSize = option_get($pdo, 'header_size', 'md');
$hWidth = option_get($pdo, 'header_width', 'boxed');
$hSticky = option_get($pdo, 'header_sticky', '0');
$hBg = option_get($pdo, 'header_bg', '#0f172a');
$hText = option_get($pdo, 'header_text', '#e2e8f0');
$hAccent = option_get($pdo, 'header_accent', '#38bdf8');
$mType = option_get($pdo, 'mobile_menu_type', 'drawer');
$mBtn = option_get($pdo, 'mobile_btn_style', 'hamburger');
$mPages = option_get($pdo, 'mobile_show_pages', '1');
$mHome = option_get($pdo, 'mobile_show_home', '1');
$logo = option_get($pdo, 'site_logo', '');
$logoRetina = option_get($pdo, 'site_logo_retina', '');
$logoMobile = option_get($pdo, 'site_logo_mobile', '');
$logoMobileRetina = option_get($pdo, 'site_logo_mobile_retina', '');
$logoAlt = option_get($pdo, 'site_logo_alt', '');
$logoHeight = option_get($pdo, 'site_logo_height', '40');
$logoMobileHeight = option_get($pdo, 'site_logo_mobile_height', '32');
$logoShowTitle = option_get($pdo, 'site_logo_show_title', '1');
$logoLink = option_get($pdo, 'site_logo_link', '');
$csrf = csrf_token();
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Header</h1>
    <p class="mt-1 text-sm text-slate-500">Logo, üst menü tipi, renkler ve mobil menü. Footer ayrı bölümdedir.</p>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 mb-6">
    <h2 class="text-sm font-semibold text-slate-900 mb-1">Logo</h2>
    <p class="text-xs text-slate-500 mb-4">Standart logo 1x, retina 2x (aynı görselin iki katı çözünürlüğü). PNG, SVG, WebP önerilir.</p>
    <form method="post" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="logo">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Standart logo</label>
            <?php if ($logo !== ''): ?>
                <img src="<?= e(media_src($logo)) ?>" alt="" class="mb-2 h-12 w-auto object-contain bg-slate-50 rounded border border-slate-200 p-1">
                <label class="mb-2 flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" name="remove_site_logo" value="1"> Logoyu kaldır</label>
            <?php endif; ?>
            <input type="file" name="site_logo" accept="image/png,image/jpeg,image/webp,image/svg+xml,image/gif">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Retina logo (2x)</label>
            <p class="text-[11px] text-slate-400 mb-1">Örn. 200×80 logo için 400×160 görsel</p>
            <?php if ($logoRetina !== ''): ?>
                <img src="<?= e(media_src($logoRetina)) ?>" alt="" class="mb-2 h-12 w-auto object-contain bg-slate-50 rounded border border-slate-200 p-1">
                <label class="mb-2 flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" name="remove_site_logo_retina" value="1"> Retina logoyu kaldır</label>
            <?php endif; ?>
            <input type="file" name="site_logo_retina" accept="image/png,image/jpeg,image/webp,image/svg+xml,image/gif">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Mobil logo</label>
            <?php if ($logoMobile !== ''): ?>
                <img src="<?= e(media_src($logoMobile)) ?>" alt="" class="mb-2 h-10 w-auto object-contain bg-slate-50 rounded border border-slate-200 p-1">
                <label class="mb-2 flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" name="remove_site_logo_mobile" value="1"> Mobil logoyu kaldır</label>
            <?php endif; ?>
            <input type="file" name="site_logo_mobile" accept="image/png,image/jpeg,image/webp,image/svg+xml,image/gif">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Mobil retina (2x)</label>
            <?php if ($logoMobileRetina !== ''): ?>
                <img src="<?= e(media_src($logoMobileRetina)) ?>" alt="" class="mb-2 h-10 w-auto object-contain bg-slate-50 rounded border border-slate-200 p-1">
                <label class="mb-2 flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" name="remove_site_logo_mobile_retina" value="1"> Mobil retina logoyu kaldır</label>
            <?php endif; ?>
            <input type="file" name="site_logo_mobile_retina" accept="image/png,image/jpeg,image/webp,image/svg+xml,image/gif">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Alt metin</label>
            <input name="site_logo_alt" value="<?= e($logoAlt) ?>" placeholder="Site adı" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Logo linki</label>
            <input name="site_logo_link" value="<?= e($logoLink) ?>" placeholder="<?= e(public_url()) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Masaüstü yükseklik (px)</label>
            <input type="number" name="site_logo_height" min="20" max="120" value="<?= e($logoHeight) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Mobil yükseklik (px)</label>
            <input type="number" name="site_logo_mobile_height" min="16" max="80" value="<?= e($logoMobileHeight) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
        </div>
        <label class="md:col-span-2 flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="site_logo_show_title" value="1" <?= $logoShowTitle === '1' ? 'checked' : '' ?>>
            Logo yanında site adını da göster
        </label>
        <div class="md:col-span-2">
            <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Logoyu kaydet</button>
        </div>
    </form>
</div>

<div class="flex flex-col lg:flex-row gap-6 items-start">
    <aside class="w-full lg:w-80 shrink-0 lg:sticky lg:top-20 space-y-4">
        <form method="post" class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="appearance">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Masaüstü görünüm</p>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Tip</label>
                <select name="header_type" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    <?php foreach ($headerTypes as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $hType === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Boyut</label>
                    <select name="header_size" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                        <option value="sm" <?= $hSize === 'sm' ? 'selected' : '' ?>>Küçük</option>
                        <option value="md" <?= $hSize === 'md' ? 'selected' : '' ?>>Orta</option>
                        <option value="lg" <?= $hSize === 'lg' ? 'selected' : '' ?>>Büyük</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Genişlik</label>
                    <select name="header_width" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                        <option value="boxed" <?= $hWidth === 'boxed' ? 'selected' : '' ?>>Kutulu</option>
                        <option value="full" <?= $hWidth === 'full' ? 'selected' : '' ?>>Tam genişlik</option>
                    </select>
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="header_sticky" value="1" <?= $hSticky === '1' ? 'checked' : '' ?>>
                Kaydırırken üstte kalsın (sticky)
            </label>
            <div class="grid grid-cols-3 gap-2">
                <label class="text-xs text-slate-500">Arka plan<input type="color" name="header_bg" value="<?= e($hBg) ?>" class="mt-1 h-9 w-full border border-slate-200 rounded"></label>
                <label class="text-xs text-slate-500">Yazı<input type="color" name="header_text" value="<?= e($hText) ?>" class="mt-1 h-9 w-full border border-slate-200 rounded"></label>
                <label class="text-xs text-slate-500">Vurgu<input type="color" name="header_accent" value="<?= e($hAccent) ?>" class="mt-1 h-9 w-full border border-slate-200 rounded"></label>
            </div>
            <p class="text-[11px] text-slate-400">Menü butonu ve logo kutusu. Site içi link / CTA rengi <a class="underline" href="index.php?page=brand">Marka</a> sayfasında.</p>

            <div class="border-t border-slate-100 pt-3 space-y-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Mobil menü</p>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Açılış tipi</label>
                    <select name="mobile_menu_type" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                        <?php foreach ($mobileTypes as $key => $label): ?>
                            <option value="<?= e($key) ?>" <?= $mType === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Buton</label>
                    <select name="mobile_btn_style" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                        <option value="hamburger" <?= $mBtn === 'hamburger' ? 'selected' : '' ?>>Yalnız hamburger</option>
                        <option value="text" <?= $mBtn === 'text' ? 'selected' : '' ?>>Yalnız “Menü” yazısı</option>
                        <option value="both" <?= $mBtn === 'both' ? 'selected' : '' ?>>İkon + yazı</option>
                    </select>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="mobile_show_home" value="1" <?= $mHome === '1' ? 'checked' : '' ?>>
                    Ana sayfa linki
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="mobile_show_pages" value="1" <?= $mPages === '1' ? 'checked' : '' ?>>
                    Yayımlanmış sayfaları da listele
                </label>
            </div>
            <button class="w-full rounded-md bg-[#2271b1] px-3 py-2 text-sm font-semibold text-white hover:bg-blue-800" type="submit">Header’ı kaydet</button>
        </form>
    </aside>

    <div class="flex-1 space-y-6 min-w-0">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
            <h2 class="text-sm font-semibold text-slate-900 mb-1">Menü öğeleri</h2>
            <p class="text-xs text-slate-400 mb-4">Sıralamak için ⋮⋮ tutup sürükleyin. Sayfa eklemek için hazır linkler:</p>
            <?php if ($publishedPages): ?>
                <p class="text-[11px] text-slate-500 mb-3">
                    <?php foreach ($publishedPages as $p): ?>
                        <span class="inline-block mr-3 font-mono"><?= e((string) $p['title']) ?> → <?= e(page_permalink($p['slug'])) ?></span>
                    <?php endforeach; ?>
                </p>
            <?php endif; ?>
            <?php if (!$items): ?>
                <p class="text-sm text-slate-500 px-3 py-6">Henüz öğe yok. Aşağıdan ekleyin.</p>
            <?php else: ?>
                <ul class="space-y-2" data-sortable data-page="header" data-location="header">
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
                    <label class="mb-1 block text-sm font-medium text-slate-700">Üst öğe (dropdown için)</label>
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
