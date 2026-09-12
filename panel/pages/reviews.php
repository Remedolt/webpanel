<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'reviews') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'heading') {
        option_set($pdo, 'reviews_heading', trim((string) ($_POST['reviews_heading'] ?? 'Müşteri yorumları')));
        option_set($pdo, 'reviews_show_home', isset($_POST['reviews_show_home']) ? '1' : '0');
        flash_set('success', 'Yorum ayarları kaydedildi.');
        redirect('index.php?page=reviews');
    }
    if ($action === 'reorder') {
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : array();
        $upd = $pdo->prepare('UPDATE testimonials SET sort_order = ? WHERE id = ?');
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
        redirect('index.php?page=reviews');
    }
    if ($action === 'save') {
        $id = (int) ($_POST['review_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $title = trim((string) ($_POST['title'] ?? ''));
        $quote = trim((string) ($_POST['quote'] ?? ''));
        $rating = (int) ($_POST['rating'] ?? 5);
        if ($rating < 1) {
            $rating = 1;
        }
        if ($rating > 5) {
            $rating = 5;
        }
        $status = ((string) ($_POST['status'] ?? 'publish') === 'publish') ? 'publish' : 'draft';
        if ($name === '' || $quote === '') {
            flash_set('error', 'Ad ve yorum zorunludur.');
        } else {
            if ($id > 0) {
                $pdo->prepare('UPDATE testimonials SET name = ?, title = ?, quote = ?, rating = ?, status = ? WHERE id = ?')
                    ->execute([$name, $title, $quote, $rating, $status, $id]);
                flash_set('success', 'Yorum güncellendi.');
                redirect('index.php?page=reviews&id=' . $id);
            }
            $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM testimonials')->fetchColumn();
            $pdo->prepare('INSERT INTO testimonials (name, title, quote, rating, sort_order, status) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$name, $title, $quote, $rating, $max + 1, $status]);
            flash_set('success', 'Yorum eklendi.');
        }
        redirect('index.php?page=reviews');
    }
    if ($action === 'delete') {
        $id = (int) ($_POST['review_id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM testimonials WHERE id = ?')->execute([$id]);
            flash_set('success', 'Yorum silindi.');
        }
        redirect('index.php?page=reviews');
    }
}

$editId = (int) ($_GET['id'] ?? 0);
$edit = null;
if ($editId > 0) {
    $st = $pdo->prepare('SELECT * FROM testimonials WHERE id = ?');
    $st->execute([$editId]);
    $edit = $st->fetch() ?: null;
}
$rows = [];
try {
    $rows = $pdo->query('SELECT * FROM testimonials ORDER BY sort_order ASC, id ASC')->fetchAll();
} catch (PDOException $e) {
    $rows = [];
}
$csrf = csrf_token();
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Müşteri yorumları</h1>
    <p class="mt-1 text-sm text-slate-500">Ana sayfada dönen alıntılar. Yıldız ve unvan ekleyebilirsiniz.</p>
</div>
<div class="mb-6 bg-white rounded-lg border border-slate-200 p-4">
    <form method="post" class="flex flex-col sm:flex-row gap-3 sm:items-end">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="heading">
        <div class="flex-1">
            <label class="mb-1 block text-sm font-medium">Bölüm başlığı</label>
            <input name="reviews_heading" value="<?= e(option_get($pdo, 'reviews_heading', 'Müşteri yorumları')) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="reviews_show_home" value="1" <?= option_get($pdo, 'reviews_show_home', '1') === '1' ? 'checked' : '' ?>> Ana sayfada göster</label>
        <button class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white" type="submit">Kaydet</button>
    </form>
</div>
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg border border-slate-200 p-4 h-fit">
        <h2 class="text-sm font-semibold mb-4"><?= $edit ? 'Yorumu düzenle' : 'Yeni yorum' ?></h2>
        <form method="post" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="review_id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
            <div>
                <label class="mb-1 block text-sm font-medium">Ad</label>
                <input name="name" required value="<?= e($edit ? (string) $edit['name'] : '') ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Unvan / firma</label>
                <input name="title" value="<?= e($edit ? (string) ($edit['title'] ?? '') : '') ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Yorum</label>
                <textarea name="quote" rows="4" required class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"><?= e($edit ? (string) ($edit['quote'] ?? '') : '') ?></textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Yıldız</label>
                <select name="rating" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    <?php for ($s = 5; $s >= 1; $s--): ?>
                        <option value="<?= $s ?>" <?= ($edit ? (int) $edit['rating'] : 5) === $s ? 'selected' : '' ?>><?= $s ?> / 5</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Durum</label>
                <select name="status" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    <option value="publish" <?= (!$edit || $edit['status'] === 'publish') ? 'selected' : '' ?>>Yayımlanmış</option>
                    <option value="draft" <?= ($edit && $edit['status'] === 'draft') ? 'selected' : '' ?>>Taslak</option>
                </select>
            </div>
            <button class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white" type="submit"><?= $edit ? 'Güncelle' : 'Ekle' ?></button>
            <?php if ($edit): ?><a class="ml-2 text-sm text-slate-500" href="index.php?page=reviews">Vazgeç</a><?php endif; ?>
        </form>
    </div>
    <div class="bg-white rounded-lg border border-slate-200 p-4">
        <ul class="space-y-2" data-sortable data-page="reviews">
            <?php foreach ($rows as $row): ?>
                <li draggable="true" data-id="<?= (int) $row['id'] ?>" class="flex items-start gap-3 rounded-md border border-slate-200 px-3 py-2 cursor-grab">
                    <span class="text-slate-400">⋮⋮</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium"><?= e((string) $row['name']) ?> <span class="text-amber-500"><?= str_repeat('★', max(1, min(5, (int) $row['rating']))) ?></span></p>
                        <p class="text-xs text-slate-500 line-clamp-2"><?= e((string) ($row['quote'] ?? '')) ?></p>
                    </div>
                    <a class="text-sm text-[#2271b1]" href="index.php?page=reviews&amp;id=<?= (int) $row['id'] ?>">Düzenle</a>
                    <form method="post" onsubmit="return confirm('Silinsin mi?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="review_id" value="<?= (int) $row['id'] ?>">
                        <button class="text-sm text-red-600" type="submit">Sil</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<input type="hidden" id="menu-csrf" value="<?= e($csrf) ?>">
