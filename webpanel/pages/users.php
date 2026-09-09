<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage, $currentUser) || $currentPage !== 'users') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'create') {
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $display = trim((string) ($_POST['display_name'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = (string) ($_POST['role'] ?? 'author');
        if (!in_array($role, ['admin', 'editor', 'author'], true)) {
            $role = 'author';
        }
        if ($username === '' || $email === '' || $display === '' || strlen($password) < 8) {
            flash_set('error', 'Tüm alanlar zorunlu; şifre en az 8 karakter olmalı.');
        } else {
            $ins = $pdo->prepare(
                'INSERT INTO users (username, email, password_hash, display_name, role) VALUES (?, ?, ?, ?, ?)'
            );
            try {
                $ins->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $display, $role]);
                flash_set('success', 'Kullanıcı eklendi.');
            } catch (PDOException $e) {
                flash_set('error', 'Kullanıcı adı veya e-posta zaten kayıtlı.');
            }
        }
    }
    redirect('index.php?page=users');
}

$users = $pdo->query('SELECT id, username, email, display_name, role, created_at FROM users ORDER BY id ASC')->fetchAll();
$roleLabel = ['admin' => 'Yönetici', 'editor' => 'Editör', 'author' => 'Yazar'];
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Kullanıcılar</h1>
    <p class="mt-1 text-sm text-slate-500">Panel erişimi olan hesaplar.</p>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 h-fit">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Yeni kullanıcı</h2>
        <form method="post" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="username">Kullanıcı adı</label>
                <input id="username" name="username" required class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="email">E-posta</label>
                <input id="email" name="email" type="email" required class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="display_name">Görünen ad</label>
                <input id="display_name" name="display_name" required class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="password">Şifre</label>
                <input id="password" name="password" type="password" minlength="8" required class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="role">Rol</label>
                <select id="role" name="role" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="author">Yazar</option>
                    <option value="editor">Editör</option>
                    <option value="admin">Yönetici</option>
                </select>
            </div>
            <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Ekle</button>
        </form>
    </div>

    <div class="xl:col-span-2 bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Ad</th>
                        <th class="px-4 py-3 font-medium">Kullanıcı adı</th>
                        <th class="px-4 py-3 font-medium">E-posta</th>
                        <th class="px-4 py-3 font-medium">Rol</th>
                        <th class="px-4 py-3 font-medium">Kayıt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($users as $row): ?>
                        <tr class="hover:bg-slate-50 <?= ((int) $row['id'] === (int) $currentUser['id']) ? 'bg-blue-50/40' : '' ?>">
                            <td class="px-4 py-3 font-medium text-slate-900"><?= e((string) $row['display_name']) ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= e((string) $row['username']) ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= e((string) $row['email']) ?></td>
                            <td class="px-4 py-3"><?= e($roleLabel[$row['role']] ?? (string) $row['role']) ?></td>
                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap"><?= format_datetime((string) $row['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
