<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage, $currentUser) || $currentPage !== 'profile') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

$userId = (int) $currentUser['id'];
try {
    $stmt = $pdo->prepare('SELECT id, username, email, display_name, role, avatar FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
} catch (PDOException $e) {
    $stmt = $pdo->prepare('SELECT id, username, email, display_name, role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
}
$me = $stmt->fetch() ?: $currentUser;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'save') {
        $display = trim((string) ($_POST['display_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if ($display === '' || $email === '') {
            flash_set('error', 'Görünen ad ve e-posta zorunludur.');
            redirect('index.php?page=profile');
        }
        $avatar = (string) ($me['avatar'] ?? '');
        $hadUpload = isset($_FILES['avatar'])
            && (int) ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        $uploaded = handle_image_upload('avatar');
        if ($uploaded) {
            $avatar = $uploaded;
        } elseif ($hadUpload) {
            redirect('index.php?page=profile');
        }
        if (isset($_POST['remove_avatar'])) {
            $avatar = '';
        }
        try {
            if ($password !== '') {
                if (strlen($password) < 8) {
                    flash_set('error', 'Yeni şifre en az 8 karakter olmalı.');
                    redirect('index.php?page=profile');
                }
                $upd = $pdo->prepare('UPDATE users SET display_name = ?, email = ?, avatar = ?, password_hash = ? WHERE id = ?');
                $upd->execute([$display, $email, $avatar !== '' ? $avatar : null, password_hash($password, PASSWORD_DEFAULT), $userId]);
            } else {
                $upd = $pdo->prepare('UPDATE users SET display_name = ?, email = ?, avatar = ? WHERE id = ?');
                $upd->execute([$display, $email, $avatar !== '' ? $avatar : null, $userId]);
            }
            $_SESSION['user_name'] = $display;
            flash_set('success', 'Profil ve avatar kaydedildi.');
        } catch (PDOException $e) {
            flash_set('error', 'E-posta başka bir hesapta kullanılıyor olabilir.');
        }
    }
    redirect('index.php?page=profile');
}

$roleLabel = ['admin' => 'Yönetici', 'editor' => 'Editör', 'author' => 'Yazar'];
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Profil</h1>
    <p class="mt-1 text-sm text-slate-500">Avatar, görünen ad ve şifre. Panel: <?= e(SITE_NAME) ?></p>
</div>

<div class="max-w-xl bg-white rounded-lg shadow-sm border border-slate-200 p-6">
    <form method="post" enctype="multipart/form-data" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <div class="flex items-center gap-4">
            <?= user_avatar_html($me, 'h-16 w-16 text-lg') ?>
            <div>
                <p class="font-semibold text-slate-900"><?= e((string) $me['display_name']) ?></p>
                <p class="text-xs text-slate-400"><?= e($roleLabel[$me['role'] ?? ''] ?? (string) ($me['role'] ?? '')) ?> · <?= e((string) $me['username']) ?></p>
            </div>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Avatar</label>
            <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif" class="block w-full text-sm">
            <?php if (!empty($me['avatar'])): ?>
                <label class="mt-2 flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remove_avatar" value="1"> Avatarı kaldır
                </label>
            <?php endif; ?>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700" for="display_name">Görünen ad</label>
            <input id="display_name" name="display_name" required value="<?= e((string) $me['display_name']) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700" for="email">E-posta</label>
            <input id="email" name="email" type="email" required value="<?= e((string) $me['email']) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700" for="password">Yeni şifre (opsiyonel)</label>
            <input id="password" name="password" type="password" minlength="8" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm" placeholder="Değiştirmek istemezseniz boş bırakın">
        </div>
        <button type="submit" class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Kaydet</button>
    </form>
</div>
