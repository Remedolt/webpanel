<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'dashboard') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

$totalPosts = (int) $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();
$totalViews = (int) $pdo->query('SELECT COALESCE(SUM(views), 0) FROM posts')->fetchColumn();
$totalComments = (int) $pdo->query('SELECT COUNT(*) FROM comments')->fetchColumn();
$pendingCommentsDash = (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn();
$totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$unreadMessagesDash = 0;
try {
    $unreadMessagesDash = (int) $pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();
} catch (Throwable $e) {
    $unreadMessagesDash = 0;
}

$recentStmt = $pdo->query(
    'SELECT p.id, p.title, p.slug, p.status, p.views, p.created_at, u.display_name
     FROM posts p
     INNER JOIN users u ON u.id = p.author_id
     ORDER BY p.created_at DESC
     LIMIT 8'
);
$recentPosts = $recentStmt->fetchAll();

$chartStmt = $pdo->query(
    "SELECT DATE(created_at) AS d, COUNT(*) AS c
     FROM posts
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY DATE(created_at)
     ORDER BY d ASC"
);
$chartRows = [];
foreach ($chartStmt->fetchAll() as $row) {
    $chartRows[$row['d']] = (int) $row['c'];
}
$chartDays = [];
$maxChart = 1;
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime('-' . $i . ' days'));
    $count = $chartRows[$day] ?? 0;
    $chartDays[] = ['label' => date('d.m', strtotime($day)), 'value' => $count];
    if ($count > $maxChart) {
        $maxChart = $count;
    }
}

?>
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Başlangıç</h1>
        <p class="mt-1 text-sm text-slate-500">Yazılar ve sayfalar yayımlanınca ziyaretçi sitesinde görünür.</p>
    </div>
    <a href="<?= e(public_url()) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
        Siteyi aç
    </a>
</div>

<div class="mb-6 bg-white rounded-lg shadow-sm border border-slate-200 p-4 text-sm text-slate-600">
    <p><span class="font-semibold text-slate-900">Ziyaretçi sitesi:</span> <a class="text-[#2271b1] hover:underline" href="<?= e(public_url()) ?>" target="_blank" rel="noopener noreferrer"><?= e(public_url()) ?></a></p>
    <p class="mt-1"><span class="font-semibold text-slate-900">Yönetim paneli:</span> <?= e(rtrim(PANEL_URL, '/') . '/') ?></p>
    <p class="mt-2 text-slate-500">Yazılar → Yeni Ekle → Yayımla. Sayfalar menüsünden eklenen ve yayımlanan sayfalar sitenin üst menüsünde çıkar. Yorumlar onaylanınca yazının altında görünür.</p>
</div>

<?php if ($pendingCommentsDash > 0 || $unreadMessagesDash > 0): ?>
    <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php if ($pendingCommentsDash > 0): ?>
            <a href="index.php?page=comments" class="block bg-amber-50 border border-amber-200 rounded-lg p-4 hover:bg-amber-100/70">
                <p class="text-sm font-semibold text-amber-900"><?= (int) $pendingCommentsDash ?> yorum onay bekliyor</p>
                <p class="mt-1 text-xs text-amber-800">Onaylamak için tıklayın.</p>
            </a>
        <?php endif; ?>
        <?php if ($unreadMessagesDash > 0): ?>
            <a href="index.php?page=messages" class="block bg-sky-50 border border-sky-200 rounded-lg p-4 hover:bg-sky-100/70">
                <p class="text-sm font-semibold text-sky-900"><?= (int) $unreadMessagesDash ?> yeni iletişim mesajı</p>
                <p class="mt-1 text-xs text-sky-800">Gelen kutusunu açın.</p>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex items-start gap-4">
        <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50 text-[#2271b1]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
        </span>
        <div>
            <p class="text-sm text-slate-500">Toplam Yazı</p>
            <p class="text-2xl font-semibold text-slate-900"><?= (int) $totalPosts ?></p>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex items-start gap-4">
        <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
        </span>
        <div>
            <p class="text-sm text-slate-500">Görüntülenme</p>
            <p class="text-2xl font-semibold text-slate-900"><?= number_format($totalViews, 0, ',', '.') ?></p>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex items-start gap-4">
        <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 0 1 1.037-.443 48.282 48.282 0 0 0 5.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
            </svg>
        </span>
        <div>
            <p class="text-sm text-slate-500">Yorumlar</p>
            <p class="text-2xl font-semibold text-slate-900"><?= (int) $totalComments ?></p>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex items-start gap-4">
        <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
            </svg>
        </span>
        <div>
            <p class="text-sm text-slate-500">Kullanıcılar</p>
            <p class="text-2xl font-semibold text-slate-900"><?= (int) $totalUsers ?></p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200">
            <h2 class="text-sm font-semibold text-slate-900">Son Yazılar</h2>
            <a href="index.php?page=posts" class="text-sm text-[#2271b1] hover:underline">Tümünü gör</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Başlık</th>
                        <th class="px-4 py-3 font-medium">Yazar</th>
                        <th class="px-4 py-3 font-medium">Durum</th>
                        <th class="px-4 py-3 font-medium text-right">Görüntülenme</th>
                        <th class="px-4 py-3 font-medium">Tarih</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!$recentPosts): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">Henüz yazı yok.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentPosts as $row): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <a href="index.php?page=post-new&amp;id=<?= (int) $row['id'] ?>" class="font-medium text-slate-900 hover:text-[#2271b1]">
                                        <?= e((string) $row['title']) ?>
                                    </a>
                                    <?php if ($row['status'] === 'publish' && !empty($row['slug'])): ?>
                                        <a href="<?= e(post_permalink($row['slug'])) ?>" target="_blank" class="ml-2 text-xs text-slate-400 hover:text-[#2271b1]">görüntüle</a>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-slate-600"><?= e((string) $row['display_name']) ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($row['status'] === 'publish'): ?>
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Yayımlanmış</span>
                                    <?php else: ?>
                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">Taslak</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-600"><?= number_format((int) $row['views'], 0, ',', '.') ?></td>
                                <td class="px-4 py-3 text-slate-500 whitespace-nowrap"><?= format_datetime((string) $row['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
        <h2 class="text-sm font-semibold text-slate-900 mb-1">Haftalık yazı temposu</h2>
        <p class="text-xs text-slate-500 mb-4">Son 7 günde oluşturulan yazılar</p>
        <div class="h-48 flex items-end gap-2">
            <?php foreach ($chartDays as $bar): ?>
                <?php $h = max(8, (int) round(($bar['value'] / $maxChart) * 100)); ?>
                <div class="flex-1 flex flex-col items-center gap-2">
                    <div class="w-full rounded-t bg-blue-200 hover:bg-[#2271b1] transition-colors" style="height: <?= (int) $h ?>%" title="<?= (int) $bar['value'] ?>"></div>
                    <span class="text-[10px] text-slate-400"><?= e($bar['label']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
