<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage, $currentUser) || $currentPage !== 'post-new') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

$editId = (int) ($_GET['id'] ?? 0);
$post = [
    'id' => 0,
    'title' => '',
    'slug' => '',
    'content' => '',
    'excerpt' => '',
    'status' => 'draft',
    'featured_image' => '',
];
$selectedCats = [];

if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT id, title, slug, content, excerpt, status, featured_image FROM posts WHERE id = ? LIMIT 1');
    $stmt->execute([$editId]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('error', 'Yazı bulunamadı.');
        redirect('index.php?page=posts');
    }
    $post = $found;
    $catStmt = $pdo->prepare('SELECT category_id FROM post_categories WHERE post_id = ?');
    $catStmt->execute([$editId]);
    $selectedCats = array_map('intval', $catStmt->fetchAll(PDO::FETCH_COLUMN));
}

$catList = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['form_action'] ?? 'draft');
    $title = trim((string) ($_POST['title'] ?? ''));
    $content = (string) ($_POST['content'] ?? '');
    $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
    $postedCats = isset($_POST['categories']) && is_array($_POST['categories']) ? $_POST['categories'] : [];
    $cleanCats = [];
    foreach ($postedCats as $cid) {
        $cid = (int) $cid;
        if ($cid > 0) {
            $cleanCats[] = $cid;
        }
    }
    $cleanCats = array_values(array_unique($cleanCats));

    if ($title === '') {
        flash_set('error', 'Başlık zorunludur.');
        redirect($editId > 0 ? ('index.php?page=post-new&id=' . $editId) : 'index.php?page=post-new');
    }

    $status = ($action === 'publish') ? 'publish' : 'draft';
    $hadUpload = isset($_FILES['featured_image'])
        && (int) ($_FILES['featured_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    $imagePath = handle_image_upload('featured_image');
    if ($hadUpload && $imagePath === null) {
        redirect($editId > 0 ? ('index.php?page=post-new&id=' . $editId) : 'index.php?page=post-new');
    }
    $removeImage = isset($_POST['remove_featured']);
    $featured = (string) ($post['featured_image'] ?? '');
    if ($removeImage) {
        $featured = '';
    }
    if ($imagePath !== null) {
        $featured = $imagePath;
    }

    $slug = unique_slug($pdo, slugify($title), $editId > 0 ? $editId : null);
    $authorId = (int) $currentUser['id'];

    $pdo->beginTransaction();
    try {
        if ($editId > 0) {
            $upd = $pdo->prepare(
                'UPDATE posts
                 SET title = ?, slug = ?, content = ?, excerpt = ?, status = ?, featured_image = ?
                 WHERE id = ?'
            );
            $upd->execute([$title, $slug, $content, $excerpt, $status, $featured !== '' ? $featured : null, $editId]);
            $postId = $editId;
            $del = $pdo->prepare('DELETE FROM post_categories WHERE post_id = ?');
            $del->execute([$postId]);
        } else {
            $ins = $pdo->prepare(
                'INSERT INTO posts (title, slug, content, excerpt, status, featured_image, author_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([$title, $slug, $content, $excerpt, $status, $featured !== '' ? $featured : null, $authorId]);
            $postId = (int) $pdo->lastInsertId();
        }

        if ($cleanCats) {
            $link = $pdo->prepare('INSERT INTO post_categories (post_id, category_id) VALUES (?, ?)');
            $exists = $pdo->prepare('SELECT id FROM categories WHERE id = ? LIMIT 1');
            foreach ($cleanCats as $cid) {
                $exists->execute([$cid]);
                if ($exists->fetch()) {
                    $link->execute([$postId, $cid]);
                }
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash_set('error', 'Yazı kaydedilemedi.');
        redirect($editId > 0 ? ('index.php?page=post-new&id=' . $editId) : 'index.php?page=post-new');
    }

    flash_set('success', $status === 'publish' ? 'Yazı yayımlandı.' : 'Taslak kaydedildi.');
    redirect('index.php?page=post-new&id=' . $postId);
}

$isEdit = (int) $post['id'] > 0;
?>
<div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900"><?= $isEdit ? 'Yazıyı düzenle' : 'Yeni yazı ekle' ?></h1>
        <p class="mt-1 text-sm text-slate-500">Yayımlayınca yazı <span class="font-medium text-slate-700">kodcu.site</span> ana sayfasında görünür.</p>
    </div>
    <?php if ($isEdit && ($post['status'] ?? '') === 'publish' && !empty($post['slug'])): ?>
        <a href="<?= e(post_permalink($post['slug'])) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Sitede görüntüle
        </a>
    <?php endif; ?>
</div>

<form method="post" action="index.php?page=post-new<?= $isEdit ? '&amp;id=' . (int) $post['id'] : '' ?>" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
    <?= csrf_field() ?>

    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
            <label for="title" class="sr-only">Başlık</label>
            <input id="title" name="title" type="text" required value="<?= e((string) $post['title']) ?>"
                   placeholder="Başlık ekle"
                   class="w-full border-0 p-0 text-2xl font-bold text-slate-900 placeholder:text-slate-300 focus:outline-none focus:ring-0">
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
            <label for="content" class="mb-2 block text-sm font-medium text-slate-700">İçerik</label>
            <textarea id="content" name="content" rows="18" placeholder="Yazınızı buraya yazın…"
                      class="w-full resize-y rounded-md border border-slate-200 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"><?= e((string) $post['content']) ?></textarea>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
            <label for="excerpt" class="mb-2 block text-sm font-medium text-slate-700">Özet</label>
            <textarea id="excerpt" name="excerpt" rows="4" placeholder="Liste ve arama sonuçlarında görünecek kısa özet"
                      class="w-full resize-y rounded-md border border-slate-200 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"><?= e((string) $post['excerpt']) ?></textarea>
            <p class="mt-2 text-xs text-slate-400">Boş bırakılırsa liste görünümünde yalnızca başlık kullanılır.</p>
        </div>
    </div>

    <div class="space-y-4 lg:sticky lg:top-20">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-sm font-semibold text-slate-900">Yayımla</h2>
            </div>
            <div class="space-y-3 p-4 text-sm">
                <p class="flex items-center justify-between text-slate-600">
                    <span>Durum</span>
                    <span class="font-medium text-slate-900"><?= $post['status'] === 'publish' ? 'Yayımlanmış' : 'Taslak' ?></span>
                </p>
                <p class="flex items-center justify-between text-slate-600">
                    <span>Görünürlük</span>
                    <span class="font-medium text-slate-900">Herkese açık</span>
                </p>
                <div class="flex flex-col gap-2 pt-2">
                    <button type="submit" name="form_action" value="draft"
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Taslak kaydet
                    </button>
                    <button type="submit" name="form_action" value="publish"
                            class="w-full rounded-md bg-[#2271b1] px-3 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                        Yayımla
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-sm font-semibold text-slate-900">Kategoriler</h2>
            </div>
            <div class="p-4 space-y-2 max-h-56 overflow-y-auto">
                <?php if (!$catList): ?>
                    <p class="text-sm text-slate-500">Kategori yok. <a class="text-[#2271b1] hover:underline" href="index.php?page=categories">Ekleyin</a>.</p>
                <?php else: ?>
                    <?php foreach ($catList as $cat): ?>
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="categories[]" value="<?= (int) $cat['id'] ?>"
                                   class="rounded border-slate-300 text-[#2271b1] focus:ring-blue-200"
                                <?= in_array((int) $cat['id'], $selectedCats, true) ? 'checked' : '' ?>>
                            <?= e((string) $cat['name']) ?>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-sm font-semibold text-slate-900">Öne çıkan görsel</h2>
            </div>
            <div class="p-4">
                <?php if (!empty($post['featured_image'])): ?>
                    <img src="<?= e((string) $post['featured_image']) ?>" alt="" class="mb-3 w-full rounded-md border border-slate-200 object-cover max-h-48">
                    <label class="mb-3 flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remove_featured" value="1" class="rounded border-slate-300">
                        Görseli kaldır
                    </label>
                <?php endif; ?>
                <label class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center hover:border-blue-300 hover:bg-blue-50/40">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mb-2 h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A1.5 1.5 0 0 0 21.75 19.5V4.5A1.5 1.5 0 0 0 20.25 3H3.75A1.5 1.5 0 0 0 2.25 4.5v15A1.5 1.5 0 0 0 3.75 21Z" />
                    </svg>
                    <span class="text-sm font-medium text-slate-700">Görsel seç veya sürükle</span>
                    <span class="mt-1 text-xs text-slate-400">JPEG, PNG, WebP, GIF — en fazla 5 MB</span>
                    <input type="file" name="featured_image" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only">
                </label>
            </div>
        </div>
    </div>
</form>
