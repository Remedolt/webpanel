<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'faq') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'heading') {
        option_set($pdo, 'faq_heading', trim((string) ($_POST['faq_heading'] ?? 'Sıkça sorulanlar')));
        option_set($pdo, 'faq_show_home', isset($_POST['faq_show_home']) ? '1' : '0');
        flash_set('success', 'SSS ayarları kaydedildi.');
        redirect('index.php?page=faq');
    }
    if ($action === 'reorder') {
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : array();
        $upd = $pdo->prepare('UPDATE faqs SET sort_order = ? WHERE id = ?');
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
        redirect('index.php?page=faq');
    }
    if ($action === 'save') {
        $id = (int) ($_POST['faq_id'] ?? 0);
        $question = trim((string) ($_POST['question'] ?? ''));
        $answer = (string) ($_POST['answer'] ?? '');
        $status = ((string) ($_POST['status'] ?? 'publish') === 'publish') ? 'publish' : 'draft';
        if ($question === '') {
            flash_set('error', 'Soru zorunludur.');
        } else {
            if ($id > 0) {
                $pdo->prepare('UPDATE faqs SET question = ?, answer = ?, status = ? WHERE id = ?')
                    ->execute([$question, $answer, $status, $id]);
                flash_set('success', 'Soru güncellendi.');
                redirect('index.php?page=faq&id=' . $id);
            }
            $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM faqs')->fetchColumn();
            $pdo->prepare('INSERT INTO faqs (question, answer, sort_order, status) VALUES (?, ?, ?, ?)')
                ->execute([$question, $answer, $max + 1, $status]);
            flash_set('success', 'Soru eklendi.');
        }
        redirect('index.php?page=faq');
    }
    if ($action === 'delete') {
        $id = (int) ($_POST['faq_id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM faqs WHERE id = ?')->execute([$id]);
            flash_set('success', 'Soru silindi.');
        }
        redirect('index.php?page=faq');
    }
}

$editId = (int) ($_GET['id'] ?? 0);
$edit = null;
if ($editId > 0) {
    $st = $pdo->prepare('SELECT * FROM faqs WHERE id = ?');
    $st->execute([$editId]);
    $edit = $st->fetch() ?: null;
}
$rows = [];
try {
    $rows = $pdo->query('SELECT * FROM faqs ORDER BY sort_order ASC, id ASC')->fetchAll();
} catch (PDOException $e) {
    $rows = [];
}
$csrf = csrf_token();
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">SSS</h1>
    <p class="mt-1 text-sm text-slate-500">Sıkça sorulan sorular. Ana sayfada akordeon, tam liste: <a class="text-[#2271b1] hover:underline font-mono" href="<?= e(faq_permalink()) ?>" target="_blank"><?= e(faq_permalink()) ?></a></p>
</div>
<div class="mb-6 bg-white rounded-lg border border-slate-200 p-4">
    <form method="post" class="flex flex-col sm:flex-row gap-3 sm:items-end">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="heading">
        <div class="flex-1">
            <label class="mb-1 block text-sm font-medium">Bölüm başlığı</label>
            <input name="faq_heading" value="<?= e(option_get($pdo, 'faq_heading', 'Sıkça sorulanlar')) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="faq_show_home" value="1" <?= option_get($pdo, 'faq_show_home', '1') === '1' ? 'checked' : '' ?>> Ana sayfada göster</label>
        <button class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white" type="submit">Kaydet</button>
    </form>
</div>
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg border border-slate-200 p-4 h-fit">
        <h2 class="text-sm font-semibold mb-4"><?= $edit ? 'Soruyu düzenle' : 'Yeni soru' ?></h2>
        <form method="post" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="faq_id" value="<?= $edit ? (int) $edit['id'] : 0 ?>">
            <div>
                <label class="mb-1 block text-sm font-medium">Soru</label>
                <input name="question" required value="<?= e($edit ? (string) $edit['question'] : '') ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Cevap</label>
                <textarea name="answer" rows="6" class="cms-editor w-full rounded-md border border-slate-200 px-3 py-2 text-sm"><?= e($edit ? (string) ($edit['answer'] ?? '') : '') ?></textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Durum</label>
                <select name="status" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    <option value="publish" <?= (!$edit || $edit['status'] === 'publish') ? 'selected' : '' ?>>Yayımlanmış</option>
                    <option value="draft" <?= ($edit && $edit['status'] === 'draft') ? 'selected' : '' ?>>Taslak</option>
                </select>
            </div>
            <button class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white" type="submit"><?= $edit ? 'Güncelle' : 'Ekle' ?></button>
            <?php if ($edit): ?><a class="ml-2 text-sm text-slate-500" href="index.php?page=faq">Vazgeç</a><?php endif; ?>
        </form>
    </div>
    <div class="bg-white rounded-lg border border-slate-200 p-4">
        <ul class="space-y-2" data-sortable data-page="faq">
            <?php foreach ($rows as $row): ?>
                <li draggable="true" data-id="<?= (int) $row['id'] ?>" class="flex items-center gap-3 rounded-md border border-slate-200 px-3 py-2 cursor-grab">
                    <span class="text-slate-400">⋮⋮</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium"><?= e((string) $row['question']) ?></p>
                    </div>
                    <a class="text-sm text-[#2271b1]" href="index.php?page=faq&amp;id=<?= (int) $row['id'] ?>">Düzenle</a>
                    <form method="post" onsubmit="return confirm('Silinsin mi?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="faq_id" value="<?= (int) $row['id'] ?>">
                        <button class="text-sm text-red-600" type="submit">Sil</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<input type="hidden" id="menu-csrf" value="<?= e($csrf) ?>">
