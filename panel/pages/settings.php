<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'settings') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $section = (string) ($_POST['settings_section'] ?? 'general');

    if ($section === 'general') {
        option_set($pdo, 'site_title', trim((string) ($_POST['site_title'] ?? '')));
        option_set($pdo, 'site_tagline', trim((string) ($_POST['site_tagline'] ?? '')));
        option_set($pdo, 'site_url', trim((string) ($_POST['site_url'] ?? PUBLIC_URL)));
        $perPage = (int) ($_POST['posts_per_page'] ?? 10);
        if ($perPage < 5) {
            $perPage = 5;
        }
        if ($perPage > 50) {
            $perPage = 50;
        }
        option_set($pdo, 'posts_per_page', (string) $perPage);
        flash_set('success', 'Genel ayarlar kaydedildi.');
    } elseif ($section === 'favicon') {
        $uploaded = handle_image_upload('favicon');
        if ($uploaded) {
            option_set($pdo, 'favicon_path', $uploaded);
            flash_set('success', 'Favicon güncellendi.');
        } else {
            flash_set('error', 'Favicon yüklenemedi. PNG, JPEG, WebP, GIF veya ICO kullanın.');
        }
    } elseif ($section === 'seo') {
        option_set($pdo, 'seo_title', trim((string) ($_POST['seo_title'] ?? '')));
        option_set($pdo, 'seo_description', trim((string) ($_POST['seo_description'] ?? '')));
        option_set($pdo, 'seo_keywords', trim((string) ($_POST['seo_keywords'] ?? '')));
        option_set($pdo, 'seo_robots', trim((string) ($_POST['seo_robots'] ?? 'index,follow')));
        flash_set('success', 'SEO ayarları kaydedildi.');
    } elseif ($section === 'popup') {
        $title = trim((string) ($_POST['popup_title'] ?? ''));
        $content = trim((string) ($_POST['popup_content'] ?? ''));
        $buttonText = trim((string) ($_POST['popup_button_text'] ?? ''));
        $buttonUrl = trim((string) ($_POST['popup_button_url'] ?? ''));
        $delay = (int) ($_POST['popup_delay'] ?? 2);
        if ($delay < 0) {
            $delay = 0;
        }
        $status = isset($_POST['popup_enabled']) ? 'publish' : 'draft';
        $existing = $pdo->query('SELECT id FROM popups ORDER BY id ASC LIMIT 1')->fetch();
        if ($existing) {
            $upd = $pdo->prepare('UPDATE popups SET title = ?, content = ?, button_text = ?, button_url = ?, delay_seconds = ?, status = ? WHERE id = ?');
            $upd->execute([$title !== '' ? $title : 'Duyuru', $content, $buttonText, $buttonUrl, $delay, $status, (int) $existing['id']]);
        } else {
            $ins = $pdo->prepare('INSERT INTO popups (title, content, button_text, button_url, delay_seconds, status) VALUES (?, ?, ?, ?, ?, ?)');
            $ins->execute([$title !== '' ? $title : 'Duyuru', $content, $buttonText, $buttonUrl, $delay, $status]);
        }
        flash_set('success', 'Popup kaydedildi.');
    } elseif ($section === 'cta') {
        option_set($pdo, 'cta_heading', trim((string) ($_POST['cta_heading'] ?? '')));
        option_set($pdo, 'cta_text', trim((string) ($_POST['cta_text'] ?? '')));
        option_set($pdo, 'cta_button', trim((string) ($_POST['cta_button'] ?? '')));
        option_set($pdo, 'cta_url', trim((string) ($_POST['cta_url'] ?? '')));
        option_set($pdo, 'cta_show_home', isset($_POST['cta_show_home']) ? '1' : '0');
        flash_set('success', 'Çağrı bandı kaydedildi.');
    } elseif ($section === 'bar') {
        option_set($pdo, 'bar_text', trim((string) ($_POST['bar_text'] ?? '')));
        option_set($pdo, 'bar_url', trim((string) ($_POST['bar_url'] ?? '')));
        option_set($pdo, 'bar_show', isset($_POST['bar_show']) ? '1' : '0');
        flash_set('success', 'Duyuru şeridi kaydedildi.');
    } elseif ($section === 'cookie') {
        option_set($pdo, 'cookie_text', trim((string) ($_POST['cookie_text'] ?? '')));
        option_set($pdo, 'cookie_policy_url', trim((string) ($_POST['cookie_policy_url'] ?? '')));
        option_set($pdo, 'cookie_show', isset($_POST['cookie_show']) ? '1' : '0');
        flash_set('success', 'Çerez çubuğu kaydedildi.');
    } elseif ($section === 'social') {
        foreach (array('social_facebook', 'social_instagram', 'social_twitter', 'social_youtube', 'social_linkedin', 'social_tiktok') as $key) {
            $url = trim((string) ($_POST[$key] ?? ''));
            if ($url !== '' && !preg_match('#^https?://#i', $url)) {
                $url = 'https://' . $url;
            }
            option_set($pdo, $key, $url);
        }
        $heading = trim((string) ($_POST['social_heading'] ?? 'Bizi takip edin'));
        if ($heading === '') {
            $heading = 'Bizi takip edin';
        }
        option_set($pdo, 'social_heading', $heading);
        flash_set('success', 'Sosyal medya kaydedildi.');
    } elseif ($section === 'ogimage') {
        $uploaded = handle_image_upload('og_image');
        if ($uploaded) {
            option_set($pdo, 'og_image', $uploaded);
            flash_set('success', 'Paylaşım görseli güncellendi.');
        } else {
            flash_set('error', 'Görsel yüklenemedi. PNG, JPEG veya WebP kullanın.');
        }
    } elseif ($section === 'analytics') {
        option_set($pdo, 'analytics_head', (string) ($_POST['analytics_head'] ?? ''));
        option_set($pdo, 'analytics_body', (string) ($_POST['analytics_body'] ?? ''));
        flash_set('success', 'Ölçüm kodları kaydedildi.');
    } elseif ($section === 'maintenance') {
        option_set($pdo, 'maintenance_mode', isset($_POST['maintenance_mode']) ? '1' : '0');
        option_set($pdo, 'maintenance_text', trim((string) ($_POST['maintenance_text'] ?? '')));
        flash_set('success', 'Bakım ayarı kaydedildi.');
    } elseif ($section === 'customcss') {
        option_set($pdo, 'custom_css', (string) ($_POST['custom_css'] ?? ''));
        flash_set('success', 'Özel CSS kaydedildi.');
    } elseif ($section === 'newsletter') {
        option_set($pdo, 'newsletter_heading', trim((string) ($_POST['newsletter_heading'] ?? 'Bülten')));
        option_set($pdo, 'newsletter_text', trim((string) ($_POST['newsletter_text'] ?? '')));
        option_set($pdo, 'newsletter_show', isset($_POST['newsletter_show']) ? '1' : '0');
        flash_set('success', 'Bülten ayarları kaydedildi.');
    } elseif ($section === 'subscriber-delete') {
        $id = (int) ($_POST['subscriber_id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('DELETE FROM subscribers WHERE id = ?')->execute([$id]);
            } catch (PDOException $e) {
            }
            flash_set('success', 'Kayıt silindi.');
        }
    }
    $tab = cms_settings_section_tab($section);
    redirect('index.php?page=settings&tab=' . rawurlencode($tab));
}

if (isset($_GET['export'])) {
    $ex = (string) $_GET['export'];
    if ($ex === 'subscribers') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="bulten-' . date('Ymd') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, array('email', 'kayit'), ';');
        try {
            foreach ($pdo->query('SELECT email, created_at FROM subscribers ORDER BY id DESC') as $row) {
                fputcsv($out, array($row['email'], $row['created_at']), ';');
            }
        } catch (PDOException $e) {
        }
        fclose($out);
        exit;
    }
    if ($ex === 'sql') {
        cms_export_sql($pdo);
        exit;
    }
}

$siteTitleVal = option_get($pdo, 'site_title', 'Kodcu');
$siteTagline = option_get($pdo, 'site_tagline', '');
$siteUrlVal = option_get($pdo, 'site_url', PUBLIC_URL);
$perPageVal = option_get($pdo, 'posts_per_page', '10');
$faviconPath = option_get($pdo, 'favicon_path', '');
$seoTitle = option_get($pdo, 'seo_title', '');
$seoDescription = option_get($pdo, 'seo_description', '');
$seoKeywords = option_get($pdo, 'seo_keywords', '');
$seoRobots = option_get($pdo, 'seo_robots', 'index,follow');
$popup = $pdo->query('SELECT * FROM popups ORDER BY id ASC LIMIT 1')->fetch() ?: array();
$settingsTab = cms_settings_tab();
$settingsTabs = cms_settings_tabs();
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Ayarlar</h1>
    <p class="mt-1 text-sm text-slate-500"><?= e($settingsTabs[$settingsTab] ?? 'Ayarlar') ?> — soldaki alan kaydedilir, sağdaki kutu sitede nasıl göründüğünü gösterir.</p>
</div>
<nav class="mb-6 flex flex-wrap gap-2 max-w-5xl" aria-label="Ayar grupları">
    <?php foreach ($settingsTabs as $tabKey => $tabLabel): ?>
        <a class="rounded-full px-3 py-1.5 text-sm font-medium <?= $settingsTab === $tabKey ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-700 hover:border-slate-300' ?>" href="index.php?page=settings&amp;tab=<?= e($tabKey) ?>"><?= e($tabLabel) ?></a>
    <?php endforeach; ?>
</nav>

<div class="space-y-6 max-w-5xl">
<?php if ($settingsTab === 'general'): ?>
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Genel</h2>
        <form method="post" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="settings_section" value="general">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="site_title">Site başlığı</label>
                <input id="site_title" name="site_title" value="<?= e($siteTitleVal) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="site_tagline">Slogan</label>
                <input id="site_tagline" name="site_tagline" value="<?= e($siteTagline) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="site_url">Site URL</label>
                <input id="site_url" name="site_url" value="<?= e($siteUrlVal) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="posts_per_page">Sayfa başına yazı</label>
                <input id="posts_per_page" name="posts_per_page" type="number" min="5" max="50" value="<?= e($perPageVal) ?>" class="w-32 rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Kaydet</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Favicon</h2>
        <?php if ($faviconPath !== ''): ?>
            <img src="<?= e(media_src($faviconPath)) ?>" alt="" class="mb-3 h-12 w-12 rounded border border-slate-200 bg-white object-contain">
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="settings_section" value="favicon">
            <input type="file" name="favicon" accept="image/png,image/jpeg,image/webp,image/gif,image/x-icon" required class="block w-full text-sm">
            <p class="text-xs text-slate-400">PNG, JPEG, WebP, GIF veya ICO. Tarayıcıda görmek için Ctrl+F5.</p>
            <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Favicon yükle</button>
        </form>
    </div>
<?php endif; ?>

<?php if ($settingsTab === 'seo'): ?>
    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,28rem)_1fr] gap-6 items-start">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h2 class="text-sm font-semibold text-slate-900 mb-4">SEO</h2>
            <form method="post" class="space-y-4" data-preview="seo">
                <?= csrf_field() ?>
                <input type="hidden" name="settings_section" value="seo">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">SEO başlık (title)</label>
                    <input name="seo_title" value="<?= e($seoTitle) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Meta açıklama</label>
                    <textarea name="seo_description" rows="3" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"><?= e($seoDescription) ?></textarea>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Anahtar kelimeler</label>
                    <input name="seo_keywords" value="<?= e($seoKeywords) ?>" placeholder="yazılım, php, cms" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Robots</label>
                    <input name="seo_robots" value="<?= e($seoRobots) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-mono focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
                <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">SEO kaydet</button>
            </form>
        </div>
        <aside class="xl:sticky xl:top-20">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Google’da böyle görünür</p>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm text-emerald-800 truncate" data-preview-url><?= e(rtrim($siteUrlVal, '/') ?: 'https://ornek.site') ?></p>
                <p class="mt-1 text-lg text-sky-800 leading-snug" data-preview-seo-title><?= e($seoTitle !== '' ? $seoTitle : $siteTitleVal) ?></p>
                <p class="mt-1 text-sm text-slate-600" data-preview-seo-desc><?= e($seoDescription !== '' ? $seoDescription : 'Arama sonuçlarında görünen kısa açıklama.') ?></p>
            </div>
        </aside>
    </div>
<?php endif; ?>

<?php if ($settingsTab === 'site'): ?>
    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,28rem)_1fr] gap-6 items-start">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h2 class="text-sm font-semibold text-slate-900 mb-1">Popup</h2>
            <p class="text-xs text-slate-500 mb-4">Ziyaretçi siteye girince, gecikmeden sonra ortada açılan kutu.</p>
            <form method="post" class="space-y-4" data-preview="popup">
                <?= csrf_field() ?>
                <input type="hidden" name="settings_section" value="popup">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="popup_enabled" value="1" <?= (($popup['status'] ?? '') === 'publish') ? 'checked' : '' ?>>
                    Sitede göster
                </label>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Başlık</label>
                    <input name="popup_title" value="<?= e((string) ($popup['title'] ?? '')) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">İçerik</label>
                    <textarea name="popup_content" rows="4" class="cms-editor w-full rounded-md border border-slate-200 px-3 py-2 text-sm"><?= e((string) ($popup['content'] ?? '')) ?></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Buton metni</label>
                        <input name="popup_button_text" value="<?= e((string) ($popup['button_text'] ?? '')) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Buton linki</label>
                        <input name="popup_button_url" value="<?= e((string) ($popup['button_url'] ?? '')) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Gecikme (saniye)</label>
                    <input name="popup_delay" type="number" min="0" value="<?= e((string) ($popup['delay_seconds'] ?? '2')) ?>" class="w-24 rounded-md border border-slate-200 px-3 py-2 text-sm">
                </div>
                <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Popup kaydet</button>
            </form>
        </div>
        <aside class="xl:sticky xl:top-20">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Sitede böyle durur</p>
            <div class="rounded-xl border border-slate-200 overflow-hidden bg-slate-200/80 shadow-sm relative min-h-[220px]" data-preview-off="popup">
                <div class="h-8 bg-slate-800"></div>
                <div class="p-3 space-y-2">
                    <div class="h-2 w-2/3 rounded bg-white/80"></div>
                    <div class="h-2 w-1/2 rounded bg-white/60"></div>
                </div>
                <div class="absolute inset-0 bg-slate-900/50 flex items-center justify-center p-4">
                    <div class="w-full max-w-[240px] rounded-xl bg-white p-4 shadow-xl">
                        <p class="text-sm font-semibold text-slate-900" data-preview-popup-title><?= e((string) ($popup['title'] ?? 'Duyuru')) ?></p>
                        <p class="mt-2 text-xs text-slate-600 line-clamp-3" data-preview-popup-content><?= e(trim(strip_tags((string) ($popup['content'] ?? ''))) ?: 'Kısa duyuru metni burada görünür.') ?></p>
                        <div class="mt-3 flex justify-end gap-2">
                            <span class="text-[11px] text-slate-400">Kapat</span>
                            <span class="rounded bg-sky-500 px-2 py-1 text-[11px] font-semibold text-slate-900" data-preview-popup-btn><?= e((string) ($popup['button_text'] ?? 'Tamam')) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,28rem)_1fr] gap-6 items-start">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-1">Ana sayfa çağrı bandı</h2>
            <p class="text-xs text-slate-500 mb-4">Ana sayfada yazıların üstünde, tam genişlikte renkli şerit. Butona basınca verdiğiniz linke gider.</p>
            <form method="post" class="space-y-3" data-preview="cta">
                <?= csrf_field() ?>
                <input type="hidden" name="settings_section" value="cta">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="cta_show_home" value="1" <?= option_get($pdo, 'cta_show_home', '1') === '1' ? 'checked' : '' ?>>
                    Ana sayfada göster
                </label>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Başlık</label>
                    <input name="cta_heading" value="<?= e(option_get($pdo, 'cta_heading', 'Birlikte çalışalım')) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Yazı</label>
                    <textarea name="cta_text" rows="2" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"><?= e(option_get($pdo, 'cta_text', '')) ?></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Buton</label>
                        <input name="cta_button" value="<?= e(option_get($pdo, 'cta_button', 'İletişime geç')) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Link</label>
                        <input name="cta_url" value="<?= e(option_get($pdo, 'cta_url', '')) ?>" placeholder="/iletisim" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    </div>
                </div>
                <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Bandı kaydet</button>
            </form>
        </div>
        <aside class="xl:sticky xl:top-20">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Sitede böyle durur</p>
            <div class="rounded-xl border border-slate-200 overflow-hidden bg-white shadow-sm" data-preview-off="cta">
                <div class="h-8 bg-slate-800 flex items-center px-3 gap-2">
                    <span class="h-2 w-2 rounded-full bg-slate-500"></span>
                    <span class="h-2 w-16 rounded bg-slate-600"></span>
                </div>
                <div class="bg-slate-50 px-3 py-4 text-[11px] text-slate-400">Sayfa içeriği…</div>
                <div class="px-5 py-7 text-center text-white" style="background:linear-gradient(120deg,#0f172a,#0ea5e9 140%)">
                    <p class="text-base font-bold" data-preview-cta-heading><?= e(option_get($pdo, 'cta_heading', 'Birlikte çalışalım')) ?></p>
                    <p class="mt-2 text-xs text-sky-50" data-preview-cta-text><?= e(option_get($pdo, 'cta_text', '')) ?></p>
                    <span class="inline-flex mt-4 rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-slate-900" data-preview-cta-btn><?= e(option_get($pdo, 'cta_button', 'İletişime geç')) ?></span>
                </div>
                <div class="bg-slate-50 px-3 py-4 text-[11px] text-slate-400">Yazılar…</div>
            </div>
        </aside>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Vitrin</h2>
            <p class="text-xs text-slate-500 mt-1">Ana sayfadaki fotoğraf + üç madde. Soldaki menüde Görünüm → Vitrin.</p>
        </div>
        <a href="index.php?page=spot" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Vitrini düzenle</a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,28rem)_1fr] gap-6 items-start">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-1">Üst duyuru şeridi</h2>
            <p class="text-xs text-slate-500 mb-4">Sitenin en tepesinde, menünün üstünde ince mavi bant. Tıklanınca linke gider.</p>
            <form method="post" class="space-y-3" data-preview="bar">
                <?= csrf_field() ?>
                <input type="hidden" name="settings_section" value="bar">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="bar_show" value="1" <?= option_get($pdo, 'bar_show', '1') === '1' ? 'checked' : '' ?>>
                    Sitede göster
                </label>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Metin</label>
                    <input name="bar_text" value="<?= e(option_get($pdo, 'bar_text', '')) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Link</label>
                    <input name="bar_url" value="<?= e(option_get($pdo, 'bar_url', '')) ?>" placeholder="/iletisim" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                </div>
                <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Şeridi kaydet</button>
            </form>
        </div>
        <aside class="xl:sticky xl:top-20">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Sitede böyle durur</p>
            <div class="rounded-xl border border-slate-200 overflow-hidden bg-white shadow-sm" data-preview-off="bar">
                <div class="bg-sky-500 text-slate-900 text-center text-xs font-semibold py-2 px-3" data-preview-bar-text><?= e(option_get($pdo, 'bar_text', 'Yeni projeler için iletişime geçin.')) ?></div>
                <div class="h-10 bg-slate-800 flex items-center px-3 gap-2">
                    <span class="h-2.5 w-16 rounded bg-slate-500"></span>
                    <span class="ml-auto h-2 w-10 rounded bg-slate-600"></span>
                </div>
                <div class="bg-slate-50 px-3 py-8 text-[11px] text-slate-400">Sayfa içeriği…</div>
            </div>
        </aside>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,28rem)_1fr] gap-6 items-start">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-1">Çerez çubuğu</h2>
            <p class="text-xs text-slate-500 mb-4">Sayfanın altında bir kez çıkan kutu. Ziyaretçi Tamam deyince bir daha görünmez.</p>
            <form method="post" class="space-y-3" data-preview="cookie">
                <?= csrf_field() ?>
                <input type="hidden" name="settings_section" value="cookie">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="cookie_show" value="1" <?= option_get($pdo, 'cookie_show', '1') === '1' ? 'checked' : '' ?>>
                    Sitede göster
                </label>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Metin</label>
                    <textarea name="cookie_text" rows="2" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"><?= e(option_get($pdo, 'cookie_text', '')) ?></textarea>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Gizlilik / KVKK bağlantısı</label>
                    <input name="cookie_policy_url" value="<?= e(option_get($pdo, 'cookie_policy_url', '')) ?>" placeholder="/gizlilik veya tam adres" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                </div>
                <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Çerezi kaydet</button>
            </form>
        </div>
        <aside class="xl:sticky xl:top-20">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Sitede böyle durur</p>
            <div class="rounded-xl border border-slate-200 overflow-hidden bg-slate-100 shadow-sm relative min-h-[200px]" data-preview-off="cookie">
                <div class="h-8 bg-slate-800"></div>
                <div class="p-4 text-[11px] text-slate-400">Sayfa içeriği…</div>
                <div class="absolute left-3 right-3 bottom-3 rounded-xl bg-slate-900 text-slate-100 p-3 flex items-center gap-3">
                    <p class="text-[11px] leading-snug flex-1" data-preview-cookie-text><?= e(option_get($pdo, 'cookie_text', 'Bu sitede deneyiminiz için çerez kullanılır.')) ?></p>
                    <span class="shrink-0 rounded-md bg-sky-500 px-2 py-1 text-[11px] font-semibold text-slate-900">Tamam</span>
                </div>
            </div>
        </aside>
    </div>
<?php endif; ?>

<?php if ($settingsTab === 'newsletter'): ?>
    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,28rem)_1fr] gap-6 items-start">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-1">Bülten</h2>
            <p class="text-xs text-slate-500 mb-4">Sitenin en altındaki (footer) e-posta formu. Kayıtlar bu sayfada listelenir.</p>
            <form method="post" class="space-y-3 mb-6" data-preview="newsletter">
                <?= csrf_field() ?>
                <input type="hidden" name="settings_section" value="newsletter">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="newsletter_show" value="1" <?= option_get($pdo, 'newsletter_show', '1') === '1' ? 'checked' : '' ?>>
                    Footer’da göster
                </label>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Başlık</label>
                    <input name="newsletter_heading" value="<?= e(option_get($pdo, 'newsletter_heading', 'Bülten')) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Kısa yazı</label>
                    <input name="newsletter_text" value="<?= e(option_get($pdo, 'newsletter_text', '')) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                </div>
                <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Bülteni kaydet</button>
            </form>
        <?php
        $subs = [];
        try {
            $subs = $pdo->query('SELECT id, email, created_at FROM subscribers ORDER BY id DESC LIMIT 80')->fetchAll();
        } catch (PDOException $e) {
            $subs = [];
        }
        ?>
        <div class="flex items-center justify-between gap-3 mb-2">
            <h3 class="text-sm font-semibold text-slate-800">Kayıtlar (<?= count($subs) ?>)</h3>
            <?php if ($subs): ?>
                <a class="text-xs font-medium text-[#2271b1] hover:underline" href="index.php?page=settings&amp;tab=newsletter&amp;export=subscribers">CSV indir</a>
            <?php endif; ?>
        </div>
        <?php if (!$subs): ?>
            <p class="text-sm text-slate-500">Henüz bülten kaydı yok.</p>
        <?php else: ?>
            <ul class="divide-y divide-slate-100">
                <?php foreach ($subs as $sub): ?>
                    <li class="flex items-center justify-between gap-3 py-2 text-sm">
                        <span><?= e((string) $sub['email']) ?> <span class="text-slate-400"><?= e(format_datetime((string) $sub['created_at'])) ?></span></span>
                        <form method="post" onsubmit="return confirm('Silinsin mi?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="settings_section" value="subscriber-delete">
                            <input type="hidden" name="subscriber_id" value="<?= (int) $sub['id'] ?>">
                            <button class="text-red-600" type="submit">Sil</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        </div>
        <aside class="xl:sticky xl:top-20">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Sitede böyle durur</p>
            <div class="rounded-xl border border-slate-200 overflow-hidden bg-white shadow-sm" data-preview-off="newsletter">
                <div class="h-8 bg-slate-800"></div>
                <div class="bg-slate-50 px-3 py-6 text-[11px] text-slate-400">Sayfa içeriği…</div>
                <div class="bg-slate-900 text-slate-300 px-4 py-5">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white" data-preview-news-heading><?= e(option_get($pdo, 'newsletter_heading', 'Bülten')) ?></p>
                    <div class="mt-2 flex gap-2">
                        <span class="flex-1 rounded-md border border-white/15 bg-white/10 px-2 py-1.5 text-[11px] text-white/40">E-posta adresiniz</span>
                        <span class="rounded-md bg-sky-400 px-2 py-1.5 text-[11px] font-semibold text-slate-900">Kaydol</span>
                    </div>
                    <p class="mt-2 text-[10px] text-white/50" data-preview-news-text><?= e(option_get($pdo, 'newsletter_text', '')) ?></p>
                </div>
            </div>
        </aside>
    </div>
<?php endif; ?>

<?php if ($settingsTab === 'social'): ?>
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-1">Sosyal medya</h2>
        <p class="text-xs text-slate-500 mb-4">Footer’da “Bizi takip edin” bloğu olarak görünür. Dolu olan hesaplar ikon olur.</p>
        <form method="post" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <?= csrf_field() ?>
            <input type="hidden" name="settings_section" value="social">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">Başlık</label>
                <input name="social_heading" value="<?= e(option_get($pdo, 'social_heading', 'Bizi takip edin')) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <?php
            $socialFields = array(
                'social_facebook' => 'Facebook',
                'social_instagram' => 'Instagram',
                'social_twitter' => 'X / Twitter',
                'social_youtube' => 'YouTube',
                'social_linkedin' => 'LinkedIn',
                'social_tiktok' => 'TikTok',
            );
            foreach ($socialFields as $key => $label):
            ?>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700"><?= e($label) ?></label>
                    <input name="<?= e($key) ?>" value="<?= e(option_get($pdo, $key, '')) ?>" placeholder="https://" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                </div>
            <?php endforeach; ?>
            <div class="sm:col-span-2">
                <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Sosyal hesapları kaydet</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php if ($settingsTab === 'seo'): ?>
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-1">Paylaşım görseli (Open Graph)</h2>
        <p class="text-xs text-slate-500 mb-4">Yazıda görsel yoksa WhatsApp / X önizlemesinde bu resim kullanılır.</p>
        <?php $ogPath = option_get($pdo, 'og_image', ''); ?>
        <?php if ($ogPath !== ''): ?>
            <img src="<?= e(media_src($ogPath)) ?>" alt="" class="mb-3 h-24 w-40 rounded border border-slate-200 object-cover">
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="settings_section" value="ogimage">
            <input type="file" name="og_image" accept="image/png,image/jpeg,image/webp" required class="block w-full text-sm">
            <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Görseli yükle</button>
        </form>
    </div>
<?php endif; ?>

<?php if ($settingsTab === 'tech'): ?>
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-1">Ölçüm kodları</h2>
        <p class="text-xs text-slate-500 mb-4">Google Analytics, Meta Pixel veya benzeri kod. Yalnızca güvendiğiniz snippet’leri yapıştırın.</p>
        <form method="post" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="settings_section" value="analytics">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">&lt;head&gt; içi</label>
                <textarea name="analytics_head" rows="4" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-mono"><?= e(option_get($pdo, 'analytics_head', '')) ?></textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Sayfa sonu</label>
                <textarea name="analytics_body" rows="4" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-mono"><?= e(option_get($pdo, 'analytics_body', '')) ?></textarea>
            </div>
            <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Kodları kaydet</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-1">Bakım modu</h2>
        <p class="text-xs text-slate-500 mb-4">Açıkken ziyaretçi bakım sayfasını görür. Panele giriş yapan yönetici ve editör siteyi görmeye devam eder.</p>
        <form method="post" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="settings_section" value="maintenance">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="maintenance_mode" value="1" <?= option_get($pdo, 'maintenance_mode', '0') === '1' ? 'checked' : '' ?>>
                Bakım sayfasını aç
            </label>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Mesaj</label>
                <textarea name="maintenance_text" rows="2" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"><?= e(option_get($pdo, 'maintenance_text', '')) ?></textarea>
            </div>
            <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Bakımı kaydet</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-1">Özel CSS</h2>
        <p class="text-xs text-slate-500 mb-4">Sitenin &lt;head&gt; bölümüne eklenir. Tema rengi, boşluk gibi küçük dokunuşlar için.</p>
        <form method="post" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="settings_section" value="customcss">
            <textarea name="custom_css" rows="8" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-mono" placeholder=".cms-card { border-radius: 20px; }"><?= e(option_get($pdo, 'custom_css', '')) ?></textarea>
            <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">CSS’i kaydet</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-1">Yedek</h2>
        <p class="text-xs text-slate-500 mb-4">İçerik tablolarının SQL dökümü. Kullanıcı hesapları dahil değildir. Canlı <span class="font-mono">config.php</span> dosyasını ezmeyin.</p>
        <a class="inline-flex rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800" href="index.php?page=settings&amp;tab=tech&amp;export=sql">SQL yedek indir</a>
    </div>
<?php endif; ?>
</div>
<script>
(function () {
    function text(el, value, fallback) {
        if (!el) return;
        var v = (value || '').trim();
        el.textContent = v !== '' ? v : (fallback || '');
        el.style.display = (v === '' && !fallback) ? 'none' : '';
    }
    function bind(name, checkboxName, map) {
        var form = document.querySelector('form[data-preview="' + name + '"]');
        if (!form) return;
        var box = document.querySelector('[data-preview-off="' + name + '"]');
        function run() {
            var check = checkboxName ? form.querySelector('[name="' + checkboxName + '"]') : null;
            if (box) box.style.opacity = (check && !check.checked) ? '0.38' : '1';
            map.forEach(function (item) {
                var input = form.querySelector('[name="' + item.name + '"]');
                var target = document.querySelector(item.to);
                if (!input || !target) return;
                text(target, input.value, item.fallback || '');
            });
        }
        form.addEventListener('input', run);
        form.addEventListener('change', run);
        run();
    }
    var seoForm = document.querySelector('form[data-preview="seo"]');
    if (seoForm) {
        var titleEl = document.querySelector('[data-preview-seo-title]');
        var descEl = document.querySelector('[data-preview-seo-desc]');
        var fallbackTitle = <?= json_encode($siteTitleVal, JSON_UNESCAPED_UNICODE) ?>;
        function seoRun() {
            var t = seoForm.querySelector('[name="seo_title"]');
            var d = seoForm.querySelector('[name="seo_description"]');
            if (titleEl && t) titleEl.textContent = (t.value || '').trim() || fallbackTitle;
            if (descEl && d) descEl.textContent = (d.value || '').trim() || 'Arama sonuçlarında görünen kısa açıklama.';
        }
        seoForm.addEventListener('input', seoRun);
    }
    bind('cta', 'cta_show_home', [
        { name: 'cta_heading', to: '[data-preview-cta-heading]', fallback: 'Birlikte çalışalım' },
        { name: 'cta_text', to: '[data-preview-cta-text]' },
        { name: 'cta_button', to: '[data-preview-cta-btn]', fallback: 'İletişime geç' }
    ]);
    bind('bar', 'bar_show', [
        { name: 'bar_text', to: '[data-preview-bar-text]', fallback: 'Yeni projeler için iletişime geçin.' }
    ]);
    bind('cookie', 'cookie_show', [
        { name: 'cookie_text', to: '[data-preview-cookie-text]', fallback: 'Bu sitede deneyiminiz için çerez kullanılır.' }
    ]);
    bind('newsletter', 'newsletter_show', [
        { name: 'newsletter_heading', to: '[data-preview-news-heading]', fallback: 'Bülten' },
        { name: 'newsletter_text', to: '[data-preview-news-text]' }
    ]);
    bind('popup', 'popup_enabled', [
        { name: 'popup_title', to: '[data-preview-popup-title]', fallback: 'Duyuru' },
        { name: 'popup_button_text', to: '[data-preview-popup-btn]', fallback: 'Tamam' }
    ]);
})();
</script>
