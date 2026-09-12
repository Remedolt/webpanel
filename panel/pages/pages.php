<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'pages') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

$templates = [
    'default' => 'Standart',
    'full' => 'Tam genişlik',
    'landing' => 'Kapak görselli',
    'sidebar' => 'Kenar çubuklu',
    'contact' => 'İletişim formu',
];

$editId = (int) ($_GET['id'] ?? 0);
$editPage = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM site_pages WHERE id = ? LIMIT 1');
    $stmt->execute([$editId]);
    $editPage = $stmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'home_heading') {
        option_set($pdo, 'home_pages_heading', trim((string) ($_POST['home_pages_heading'] ?? 'Sayfalar')));
        flash_set('success', 'Ana sayfa bölüm başlığı kaydedildi.');
        redirect('index.php?page=pages');
    }
    if ($action === 'reorder') {
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : array();
        $upd = $pdo->prepare('UPDATE site_pages SET sort_order = ? WHERE id = ?');
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
        redirect('index.php?page=pages');
    }
    if ($action === 'save') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $status = ((string) ($_POST['status'] ?? 'draft') === 'publish') ? 'publish' : 'draft';
        $content = (string) ($_POST['content'] ?? '');
        $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
        $seoTitle = trim((string) ($_POST['seo_title'] ?? ''));
        $seoDescription = trim((string) ($_POST['seo_description'] ?? ''));
        $videoUrl = trim((string) ($_POST['video_url'] ?? ''));
        $template = option_pick($_POST['template'] ?? 'default', array_keys($templates), 'default');
        $showHome = isset($_POST['show_on_home']) ? 1 : 0;
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $id = (int) ($_POST['page_id'] ?? 0);
        $rawSlug = trim((string) ($_POST['slug'] ?? ''));
        if ($title === '') {
            flash_set('error', 'Sayfa başlığı zorunludur.');
        } else {
            $slug = unique_page_slug($pdo, slugify($rawSlug !== '' ? $rawSlug : $title), $id > 0 ? $id : null);
            $featured = '';
            if ($id > 0) {
                $cur = $pdo->prepare('SELECT featured_image FROM site_pages WHERE id = ?');
                $cur->execute([$id]);
                $featured = (string) ($cur->fetchColumn() ?: '');
            }
            $hadUpload = isset($_FILES['featured_image'])
                && (int) ($_FILES['featured_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            $uploaded = handle_image_upload('featured_image');
            if ($uploaded) {
                $featured = $uploaded;
            } elseif ($hadUpload) {
                redirect('index.php?page=pages' . ($id > 0 ? '&id=' . $id : ''));
            }
            if ($id > 0) {
                $upd = $pdo->prepare(
                    'UPDATE site_pages SET title = ?, slug = ?, content = ?, excerpt = ?, status = ?, featured_image = ?, template = ?, show_on_home = ?, sort_order = ?, seo_title = ?, seo_description = ?, video_url = ? WHERE id = ?'
                );
                $upd->execute([$title, $slug, $content, $excerpt, $status, $featured, $template, $showHome, $sortOrder, $seoTitle, $seoDescription, $videoUrl, $id]);
                $savedId = $id;
                flash_set('success', 'Sayfa güncellendi.');
                cms_audit($pdo, $status === 'publish' ? 'publish' : 'update', 'page', $id, $title);
            } else {
                $ins = $pdo->prepare(
                    'INSERT INTO site_pages (title, slug, content, excerpt, status, featured_image, template, show_on_home, sort_order, seo_title, seo_description, video_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $ins->execute([$title, $slug, $content, $excerpt, $status, $featured, $template, $showHome, $sortOrder, $seoTitle, $seoDescription, $videoUrl]);
                $savedId = (int) $pdo->lastInsertId();
                flash_set('success', 'Sayfa oluşturuldu.');
                cms_audit($pdo, $status === 'publish' ? 'publish' : 'create', 'page', $savedId, $title);
            }
            if ($savedId > 0) {
                $extras = handle_multi_uploads('extra_images');
                if ($extras) {
                    $maxImg = 0;
                    try {
                        $mx = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM page_images WHERE page_id = ?');
                        $mx->execute([$savedId]);
                        $maxImg = (int) $mx->fetchColumn();
                    } catch (PDOException $e) {
                        $maxImg = 0;
                    }
                    $insImg = $pdo->prepare('INSERT INTO page_images (page_id, image, sort_order) VALUES (?, ?, ?)');
                    foreach ($extras as $img) {
                        $maxImg++;
                        $insImg->execute([$savedId, $img, $maxImg]);
                    }
                }
                redirect('index.php?page=pages&id=' . $savedId);
            }
            redirect('index.php?page=pages');
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['page_id'] ?? 0);
        if ($id > 0) {
            $gone = cms_row_title($pdo, 'site_pages', $id);
            $pdo->prepare('DELETE FROM site_pages WHERE id = ?')->execute([$id]);
            flash_set('success', 'Sayfa silindi.');
            cms_audit($pdo, 'delete', 'page', $id, $gone);
        }
    } elseif ($action === 'delete_image') {
        $imgId = (int) ($_POST['image_id'] ?? 0);
        $pageId = (int) ($_POST['page_id'] ?? 0);
        if ($imgId > 0) {
            try {
                $pdo->prepare('DELETE FROM page_images WHERE id = ?')->execute([$imgId]);
            } catch (PDOException $e) {
            }
            flash_set('success', 'Görsel silindi.');
        }
        redirect('index.php?page=pages' . ($pageId > 0 ? '&id=' . $pageId : ''));
    } elseif ($action === 'delete_inquiry') {
        $id = (int) ($_POST['inquiry_id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM inquiries WHERE id = ?')->execute([$id]);
            flash_set('success', 'Mesaj silindi.');
        }
    }
    redirect('index.php?page=pages');
}

$pages = $pdo->query('SELECT id, title, slug, status, template, show_on_home, sort_order, updated_at FROM site_pages ORDER BY sort_order ASC, updated_at DESC')->fetchAll();
$inquiries = [];
try {
    $inquiries = $pdo->query(
        'SELECT i.id, i.author_name, i.email, i.message, i.created_at, p.title AS page_title
         FROM inquiries i
         LEFT JOIN site_pages p ON p.id = i.page_id
         ORDER BY i.created_at DESC
         LIMIT 20'
    )->fetchAll();
} catch (PDOException $e) {
    $inquiries = [];
}

$formTitle = $editPage ? (string) $editPage['title'] : '';
$formSlug = $editPage ? (string) $editPage['slug'] : '';
$formContent = $editPage ? (string) $editPage['content'] : '';
$formExcerpt = $editPage ? (string) ($editPage['excerpt'] ?? '') : '';
$formStatus = $editPage ? (string) $editPage['status'] : 'draft';
$formTemplate = $editPage ? (string) ($editPage['template'] ?? 'default') : 'default';
$formShowHome = $editPage ? (int) ($editPage['show_on_home'] ?? 1) : 1;
$formSort = $editPage ? (int) ($editPage['sort_order'] ?? 0) : 0;
$formSeoTitle = $editPage ? (string) ($editPage['seo_title'] ?? '') : '';
$formSeoDesc = $editPage ? (string) ($editPage['seo_description'] ?? '') : '';
$formVideo = $editPage ? (string) ($editPage['video_url'] ?? '') : '';
$formImage = $editPage ? (string) ($editPage['featured_image'] ?? '') : '';
$pageExtraImages = [];
if ($editPage) {
    try {
        $stImg = $pdo->prepare('SELECT id, image FROM page_images WHERE page_id = ? ORDER BY sort_order ASC, id ASC');
        $stImg->execute([(int) $editPage['id']]);
        $pageExtraImages = $stImg->fetchAll();
    } catch (PDOException $e) {
        $pageExtraImages = [];
    }
}
$homeHeading = option_get($pdo, 'home_pages_heading', 'Sayfalar');
$csrf = csrf_token();
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Sayfalar</h1>
    <p class="mt-1 text-sm text-slate-500">Özet, görsel, şablon ve ana sayfada göster. Yayın adresi: <span class="font-mono"><?= e(rtrim(PUBLIC_URL, '/') . '/') ?>slug</span></p>
</div>

<div class="mb-6 bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <form method="post" class="flex flex-col sm:flex-row gap-3 sm:items-end">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="home_heading">
        <div class="flex-1">
            <label class="mb-1 block text-sm font-medium text-slate-700" for="home-heading">Ana sayfadaki sayfalar başlığı</label>
            <input id="home-heading" name="home_pages_heading" value="<?= e($homeHeading) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
        </div>
        <button type="submit" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Kaydet</button>
    </form>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 h-fit">
        <h2 class="text-sm font-semibold text-slate-900 mb-4"><?= $editPage ? 'Sayfayı düzenle' : 'Yeni sayfa' ?></h2>
        <form method="post" enctype="multipart/form-data" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="page_id" value="<?= $editPage ? (int) $editPage['id'] : 0 ?>">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="page-title">Başlık</label>
                <input id="page-title" name="title" required value="<?= e($formTitle) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="page-slug">Slug / link</label>
                <input id="page-slug" name="slug" value="<?= e($formSlug) ?>" placeholder="ornek-hakkinda" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-mono focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="page-excerpt">Özet (ana sayfa kartı)</label>
                <textarea id="page-excerpt" name="excerpt" rows="2" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"><?= e($formExcerpt) ?></textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="page-content">İçerik</label>
                <textarea id="page-content" name="content" rows="8" class="cms-editor w-full rounded-md border border-slate-200 px-3 py-2 text-sm"><?= e($formContent) ?></textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Kapak görseli</label>
                <?php if ($formImage !== ''): ?>
                    <img src="<?= e(media_src($formImage)) ?>" alt="" class="mb-2 h-28 w-full object-cover rounded-md border border-slate-200">
                <?php endif; ?>
                <input type="file" name="featured_image" accept="image/jpeg,image/png,image/webp,image/gif" class="block w-full text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Video (YouTube veya mp4 linki)</label>
                <input id="page-video" name="video_url" value="<?= e($formVideo) ?>" placeholder="https://www.youtube.com/watch?v=…" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Ek resimler</label>
                <?php if ($pageExtraImages): ?>
                    <div class="mb-2 grid grid-cols-3 gap-2">
                        <?php foreach ($pageExtraImages as $img): ?>
                            <div class="relative">
                                <img src="<?= e(media_src($img['image'])) ?>" alt="" class="h-20 w-full object-cover rounded-md border border-slate-200">
                                <form method="post" class="absolute top-1 right-1" onsubmit="return confirm('Görsel silinsin mi?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_image">
                                    <input type="hidden" name="image_id" value="<?= (int) $img['id'] ?>">
                                    <input type="hidden" name="page_id" value="<?= $editPage ? (int) $editPage['id'] : 0 ?>">
                                    <button type="submit" class="rounded bg-white/90 px-1.5 text-[11px] text-red-600">Sil</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <input type="file" name="extra_images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="page-template">Şablon</label>
                    <select id="page-template" name="template" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                        <?php foreach ($templates as $key => $label): ?>
                            <option value="<?= e($key) ?>" <?= $formTemplate === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="page-status">Durum</label>
                    <select id="page-status" name="status" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                        <option value="draft" <?= $formStatus === 'draft' ? 'selected' : '' ?>>Taslak</option>
                        <option value="publish" <?= $formStatus === 'publish' ? 'selected' : '' ?>>Yayımlanmış</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="page-sort">Sıra (ana sayfa)</label>
                <input id="page-sort" type="number" name="sort_order" value="<?= (int) $formSort ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="show_on_home" value="1" <?= $formShowHome === 1 ? 'checked' : '' ?>>
                Ana sayfada kart olarak göster
            </label>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="page-seo-title">SEO başlık</label>
                <input id="page-seo-title" name="seo_title" value="<?= e($formSeoTitle) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="page-seo-desc">SEO açıklama</label>
                <textarea id="page-seo-desc" name="seo_description" rows="2" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"><?= e($formSeoDesc) ?></textarea>
            </div>
            <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800"><?= $editPage ? 'Güncelle' : 'Kaydet' ?></button>
            <?php if ($editPage): ?>
                <a href="index.php?page=pages" class="ml-2 text-sm text-slate-500 hover:underline">Vazgeç</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 text-xs text-slate-400">Sıralamak için sürükleyin. Ana sayfada gösterilenler işaretlidir.</div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium w-8"></th>
                            <th class="px-4 py-3 font-medium">Başlık</th>
                            <th class="px-4 py-3 font-medium">Şablon</th>
                            <th class="px-4 py-3 font-medium">Ana sayfa</th>
                            <th class="px-4 py-3 font-medium">Durum</th>
                            <th class="px-4 py-3 font-medium text-right">İşlem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" data-sortable data-page="pages">
                        <?php foreach ($pages as $row): ?>
                            <tr draggable="true" data-id="<?= (int) $row['id'] ?>" class="hover:bg-slate-50 cursor-grab">
                                <td class="px-4 py-3 text-slate-400">⋮⋮</td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-900"><?= e((string) $row['title']) ?></p>
                                    <a class="text-[11px] text-[#2271b1] hover:underline break-all" href="<?= e(page_permalink($row['slug'])) ?>" target="_blank"><?= e(page_permalink($row['slug'])) ?></a>
                                </td>
                                <td class="px-4 py-3 text-slate-600"><?= e($templates[$row['template'] ?? ''] ?? 'Standart') ?></td>
                                <td class="px-4 py-3"><?= ((int) $row['show_on_home'] === 1) ? 'Evet' : 'Hayır' ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($row['status'] === 'publish'): ?>
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Yayımlanmış</span>
                                    <?php else: ?>
                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">Taslak</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="index.php?page=pages&amp;id=<?= (int) $row['id'] ?>" class="text-[#2271b1] hover:underline mr-2">Düzenle</a>
                                    <?php if ($row['status'] === 'publish'): ?>
                                        <a href="<?= e(page_permalink($row['slug'])) ?>" target="_blank" rel="noopener noreferrer" class="text-slate-600 hover:underline mr-2">Görüntüle</a>
                                    <?php endif; ?>
                                    <form method="post" class="inline" onsubmit="return confirm('Sayfa silinsin mi?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="page_id" value="<?= (int) $row['id'] ?>">
                                        <button type="submit" class="text-red-600 hover:underline">Sil</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-900">İletişim mesajları</h2>
                <a class="text-xs font-semibold text-[#2271b1] hover:underline" href="index.php?page=contact">İletişim bölümü</a>
            </div>
            <?php if (!$inquiries): ?>
                <p class="text-sm text-slate-500">Henüz mesaj yok. Formlar <a class="text-[#2271b1] hover:underline" href="index.php?page=contact">İletişim</a> bölümünde durur.</p>
            <?php else: ?>
                <p class="text-sm text-slate-600"><?= count($inquiries) ?> son mesaj. Tam liste ve okundu işaretleme için gelen kutuyu aç.</p>
                <ul class="mt-3 divide-y divide-slate-100">
                    <?php foreach (array_slice($inquiries, 0, 3) as $msg): ?>
                        <li class="py-2">
                            <p class="text-sm font-medium text-slate-900"><?= e((string) $msg['author_name']) ?></p>
                            <p class="text-xs text-slate-500 line-clamp-2"><?= e((string) $msg['message']) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
<input type="hidden" id="menu-csrf" value="<?= e($csrf) ?>">
