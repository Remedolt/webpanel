<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'pages') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

$editId = (int) ($_GET['id'] ?? 0);
$editPage = null;
if ($editId > 0) {
    $found = $pdo->prepare('SELECT id, title, slug, content, status FROM site_pages WHERE id = ? LIMIT 1');
    $found->execute([$editId]);
    $editPage = $found->fetch() ?: null;
    if (!$editPage) {
        flash_set('error', 'Sayfa bulunamadı.');
        redirect('index.php?page=pages');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'save') {
        $id = (int) ($_POST['page_id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $content = (string) ($_POST['content'] ?? '');
        $status = ((string) ($_POST['status'] ?? 'draft') === 'publish') ? 'publish' : 'draft';
        if ($title === '') {
            flash_set('error', 'Sayfa başlığı zorunludur.');
            redirect($id > 0 ? ('index.php?page=pages&id=' . $id) : 'index.php?page=pages');
        }
        $slug = unique_page_slug($pdo, slugify($title), $id > 0 ? $id : null);
        if ($id > 0) {
            $upd = $pdo->prepare('UPDATE site_pages SET title = ?, slug = ?, content = ?, status = ? WHERE id = ?');
            $upd->execute([$title, $slug, $content, $status, $id]);
            flash_set('success', 'Sayfa güncellendi.');
            redirect('index.php?page=pages&id=' . $id);
        }
        $ins = $pdo->prepare('INSERT INTO site_pages (title, slug, content, status) VALUES (?, ?, ?, ?)');
        $ins->execute([$title, $slug, $content, $status]);
        flash_set('success', 'Sayfa oluşturuldu.');
        redirect('index.php?page=pages&id=' . (int) $pdo->lastInsertId());
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['page_id'] ?? 0);
        if ($id > 0) {
            $del = $pdo->prepare('DELETE FROM site_pages WHERE id = ?');
            $del->execute([$id]);
            flash_set('success', 'Sayfa silindi.');
        }
    }
    redirect('index.php?page=pages');
}

$pages = $pdo->query('SELECT id, title, slug, status, updated_at FROM site_pages ORDER BY updated_at DESC')->fetchAll();
$formTitle = $editPage ? (string) $editPage['title'] : '';
$formContent = $editPage ? (string) $editPage['content'] : '';
$formStatus = $editPage ? (string) $editPage['status'] : 'draft';
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Sayfalar</h1>
    <p class="mt-1 text-sm text-slate-500">Sabit sayfalar (Hakkında, İletişim vb.). Yayımlananlar üst menüde görünür.</p>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 h-fit">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-slate-900"><?= $editPage ? 'Sayfayı düzenle' : 'Yeni sayfa' ?></h2>
            <?php if ($editPage): ?>
                <a href="index.php?page=pages" class="text-xs text-[#2271b1] hover:underline">Yeni oluştur</a>
            <?php endif; ?>
        </div>
        <form method="post" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="page_id" value="<?= $editPage ? (int) $editPage['id'] : 0 ?>">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="page-title">Başlık</label>
                <input id="page-title" name="title" required value="<?= e($formTitle) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="page-content">İçerik</label>
                <textarea id="page-content" name="content" rows="8" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"><?= e($formContent) ?></textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="page-status">Durum</label>
                <select id="page-status" name="status" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="draft" <?= $formStatus === 'draft' ? 'selected' : '' ?>>Taslak</option>
                    <option value="publish" <?= $formStatus === 'publish' ? 'selected' : '' ?>>Yayımlanmış</option>
                </select>
            </div>
            <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800"><?= $editPage ? 'Güncelle' : 'Kaydet' ?></button>
        </form>
    </div>

    <div class="xl:col-span-2 bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Başlık</th>
                        <th class="px-4 py-3 font-medium">Kalıcı bağlantı</th>
                        <th class="px-4 py-3 font-medium">Durum</th>
                        <th class="px-4 py-3 font-medium">Güncelleme</th>
                        <th class="px-4 py-3 font-medium text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($pages as $row): ?>
                        <tr class="hover:bg-slate-50 <?= ((int) $row['id'] === $editId) ? 'bg-blue-50/40' : '' ?>">
                            <td class="px-4 py-3 font-medium text-slate-900"><?= e((string) $row['title']) ?></td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500"><?= e((string) $row['slug']) ?></td>
                            <td class="px-4 py-3">
                                <?php if ($row['status'] === 'publish'): ?>
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Yayımlanmış</span>
                                <?php else: ?>
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">Taslak</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap"><?= format_datetime((string) $row['updated_at']) ?></td>
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
</div>
