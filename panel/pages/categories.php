<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'categories') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            flash_set('error', 'Kategori adı zorunludur.');
        } else {
            $slug = slugify($name);
            $check = $pdo->prepare('SELECT id FROM categories WHERE slug = ? LIMIT 1');
            $check->execute([$slug]);
            if ($check->fetch()) {
                $slug .= '-' . bin2hex(random_bytes(2));
            }
            $ins = $pdo->prepare('INSERT INTO categories (name, slug) VALUES (?, ?)');
            $ins->execute([$name, $slug]);
            flash_set('success', 'Kategori eklendi.');
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['category_id'] ?? 0);
        if ($id > 0) {
            $del = $pdo->prepare('DELETE FROM categories WHERE id = ?');
            $del->execute([$id]);
            flash_set('success', 'Kategori silindi.');
        }
    }
    redirect('index.php?page=categories');
}

$categories = $pdo->query(
    'SELECT c.id, c.name, c.slug, c.created_at, COUNT(pc.post_id) AS post_count
     FROM categories c
     LEFT JOIN post_categories pc ON pc.category_id = c.id
     GROUP BY c.id, c.name, c.slug, c.created_at
     ORDER BY c.name ASC'
)->fetchAll();
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Kategoriler</h1>
    <p class="mt-1 text-sm text-slate-500">Yazıları gruplamak için kategorileri yönetin.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 h-fit">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Yeni kategori</h2>
        <form method="post" action="index.php?page=categories" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Ad</label>
                <input id="name" name="name" type="text" required
                       class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>
            <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Ekle</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Ad</th>
                        <th class="px-4 py-3 font-medium">Kalıcı bağlantı</th>
                        <th class="px-4 py-3 font-medium text-right">Yazı</th>
                        <th class="px-4 py-3 font-medium text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!$categories): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-slate-500">Henüz kategori yok. Soldan bir ad yazıp ekleyin.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-900"><?= e((string) $cat['name']) ?></td>
                            <td class="px-4 py-3 text-slate-500 font-mono text-xs"><?= e((string) $cat['slug']) ?></td>
                            <td class="px-4 py-3 text-right"><?= (int) $cat['post_count'] ?></td>
                            <td class="px-4 py-3 text-right">
                                <form method="post" class="inline" onsubmit="return confirm('Kategori silinsin mi?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="category_id" value="<?= (int) $cat['id'] ?>">
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
