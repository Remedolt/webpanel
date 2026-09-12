<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage, $currentUser) || $currentPage !== 'dashboard') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

$q = static function (PDO $pdo, string $sql) : int {
    try {
        return (int) $pdo->query($sql)->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
};

$totalPosts = $q($pdo, 'SELECT COUNT(*) FROM posts');
$publishedPosts = $q($pdo, "SELECT COUNT(*) FROM posts WHERE status = 'publish'");
$draftPosts = $q($pdo, "SELECT COUNT(*) FROM posts WHERE status = 'draft'");
$totalViews = $q($pdo, 'SELECT COALESCE(SUM(views), 0) FROM posts');
$totalComments = $q($pdo, 'SELECT COUNT(*) FROM comments');
$pendingComments = $q($pdo, "SELECT COUNT(*) FROM comments WHERE status = 'pending'");
$totalUsers = $q($pdo, 'SELECT COUNT(*) FROM users');
$totalPages = $q($pdo, "SELECT COUNT(*) FROM site_pages WHERE status = 'publish'");
$draftPages = $q($pdo, "SELECT COUNT(*) FROM site_pages WHERE status = 'draft'");
$albums = $q($pdo, "SELECT COUNT(*) FROM gallery_albums WHERE status = 'publish'");
$photos = $q($pdo, 'SELECT COUNT(*) FROM gallery_images');
$inquiries = $q($pdo, 'SELECT COUNT(*) FROM inquiries');
$unreadInquiries = $q($pdo, 'SELECT COUNT(*) FROM inquiries WHERE is_read = 0');
$slides = $q($pdo, "SELECT COUNT(*) FROM sliders WHERE status = 'publish'");
$reviews = $q($pdo, "SELECT COUNT(*) FROM testimonials WHERE status = 'publish'");
$subscribers = $q($pdo, 'SELECT COUNT(*) FROM subscribers');
$services = $q($pdo, "SELECT COUNT(*) FROM services WHERE status = 'publish'");

$recentStmt = $pdo->query(
    'SELECT p.id, p.title, p.slug, p.status, p.views, p.created_at, u.display_name
     FROM posts p
     INNER JOIN users u ON u.id = p.author_id
     ORDER BY p.created_at DESC
     LIMIT 6'
);
$recentPosts = $recentStmt->fetchAll();

$pendingList = [];
try {
    $pendingList = $pdo->query(
        "SELECT c.id, c.author_name, c.content, c.created_at, p.title AS post_title
         FROM comments c
         LEFT JOIN posts p ON p.id = c.post_id
         WHERE c.status = 'pending'
         ORDER BY c.created_at DESC
         LIMIT 5"
    )->fetchAll();
} catch (PDOException $e) {
    $pendingList = [];
}

$inbox = [];
try {
    $inbox = $pdo->query(
        'SELECT author_name, email, message, created_at FROM inquiries ORDER BY created_at DESC LIMIT 4'
    )->fetchAll();
} catch (PDOException $e) {
    $inbox = [];
}

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

$clip = static function (string $text, int $len) : string {
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($text, 0, $len, '…', 'UTF-8');
    }
    if (strlen($text) <= $len) {
        return $text;
    }
    return substr($text, 0, $len) . '...';
};
$hour = (int) date('G');
if ($hour < 5) {
    $hello = 'İyi geceler';
} elseif ($hour < 12) {
    $hello = 'Günaydın';
} elseif ($hour < 18) {
    $hello = 'İyi günler';
} else {
    $hello = 'İyi akşamlar';
}
$days = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
$months = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
$todayLabel = $days[(int) date('w')] . ', ' . date('j') . ' ' . $months[(int) date('n') - 1] . ' ' . date('Y');
$displayName = (string) ($currentUser['display_name'] ?? 'Yönetici');

$todo = [];
if ($pendingComments > 0) {
    $todo[] = [$pendingComments . ' yorum onay bekliyor', 'Ziyaretçi yorumlarını onayla veya spam işaretle.', 'index.php?page=comments&status=pending'];
}
if ($unreadInquiries > 0) {
    $todo[] = [$unreadInquiries . ' okunmamış iletişim mesajı', 'Gelen kutuyu kontrol et.', 'index.php?page=contact&status=new'];
}
if ($publishedPosts === 0) {
    $todo[] = ['Yayımlanmış yazı yok', 'İlk yazını yazıp Yayımla de.', 'index.php?page=post-new'];
}
if ($totalPages === 0) {
    $todo[] = ['Yayımlanmış sayfa yok', 'Hakkında veya iletişim sayfası ekle.', 'index.php?page=pages'];
}
if ($albums === 0 || $photos === 0) {
    $todo[] = ['Galeri henüz dolu değil', 'Albüme kapak ve fotoğraf ekle.', 'index.php?page=gallery'];
}
if ($slides === 0) {
    $todo[] = ['Slider boş', 'Ana sayfa için slayt ekle.', 'index.php?page=slider'];
}
if ($services === 0) {
    $todo[] = ['Hizmet kartı yok', 'Sunduğunuz işleri hizmet olarak ekleyin.', 'index.php?page=services'];
}
if (option_get($pdo, 'site_logo', '') === '') {
    $todo[] = ['Site logosu yok', 'Header bölümünden logo yükle.', 'index.php?page=header'];
}
?>
<div class="mb-6 overflow-hidden rounded-2xl text-white" style="background:linear-gradient(135deg,var(--p-sidebar),var(--p-accent))">
    <div class="px-5 py-6 sm:px-7 sm:py-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-white/80"><?= e($todayLabel) ?></p>
            <h1 class="mt-1 text-2xl sm:text-3xl font-semibold tracking-tight"><?= e($hello) ?>, <?= e($displayName) ?></h1>
            <p class="mt-2 text-sm text-white/80 max-w-xl">Sitenin özeti burada. Yazı, sayfa ve galeri yayımlanınca ziyaretçiler görür.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="index.php?page=post-new" class="inline-flex items-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-100">Yeni yazı</a>
            <a href="<?= e(public_url()) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-lg border border-white/30 px-4 py-2 text-sm font-medium text-white hover:bg-white/10">Siteyi aç</a>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <a href="index.php?page=posts" class="bg-white rounded-xl border border-slate-200 p-4 hover:border-slate-300 transition-colors">
        <p class="text-xs font-medium text-slate-500">Yazılar</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900"><?= (int) $totalPosts ?></p>
        <p class="mt-1 text-[11px] text-slate-400"><?= (int) $publishedPosts ?> yayımlanmış · <?= (int) $draftPosts ?> taslak</p>
    </a>
    <a href="index.php?page=posts" class="bg-white rounded-xl border border-slate-200 p-4 hover:border-slate-300 transition-colors">
        <p class="text-xs font-medium text-slate-500">Görüntülenme</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900"><?= number_format($totalViews, 0, ',', '.') ?></p>
        <p class="mt-1 text-[11px] text-slate-400">Tüm yazıların toplamı</p>
    </a>
    <a href="index.php?page=comments" class="bg-white rounded-xl border border-slate-200 p-4 hover:border-slate-300 transition-colors">
        <p class="text-xs font-medium text-slate-500">Yorumlar</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900"><?= (int) $totalComments ?></p>
        <p class="mt-1 text-[11px] <?= $pendingComments > 0 ? 'text-amber-600 font-medium' : 'text-slate-400' ?>"><?= (int) $pendingComments ?> onay bekliyor</p>
    </a>
    <a href="index.php?page=pages" class="bg-white rounded-xl border border-slate-200 p-4 hover:border-slate-300 transition-colors">
        <p class="text-xs font-medium text-slate-500">Sayfalar</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900"><?= (int) $totalPages ?></p>
        <p class="mt-1 text-[11px] text-slate-400"><?= (int) $draftPages ?> taslak</p>
    </a>
    <a href="index.php?page=contact" class="bg-white rounded-xl border border-slate-200 p-4 hover:border-slate-300 transition-colors">
        <p class="text-xs font-medium text-slate-500">İletişim</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900"><?= (int) $inquiries ?></p>
        <p class="mt-1 text-[11px] <?= $unreadInquiries > 0 ? 'text-amber-600 font-medium' : 'text-slate-400' ?>"><?= (int) $unreadInquiries ?> okunmamış</p>
    </a>
    <a href="index.php?page=gallery" class="bg-white rounded-xl border border-slate-200 p-4 hover:border-slate-300 transition-colors">
        <p class="text-xs font-medium text-slate-500">Galeri</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900"><?= (int) $photos ?></p>
        <p class="mt-1 text-[11px] text-slate-400"><?= (int) $albums ?> albüm</p>
    </a>
    <a href="index.php?page=slider" class="bg-white rounded-xl border border-slate-200 p-4 hover:border-slate-300 transition-colors">
        <p class="text-xs font-medium text-slate-500">Slider</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900"><?= (int) $slides ?></p>
        <p class="mt-1 text-[11px] text-slate-400">Yayımlanmış slayt</p>
    </a>
    <a href="index.php?page=users" class="bg-white rounded-xl border border-slate-200 p-4 hover:border-slate-300 transition-colors">
        <p class="text-xs font-medium text-slate-500">Kullanıcılar</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900"><?= (int) $totalUsers ?></p>
        <p class="mt-1 text-[11px] text-slate-400">Panel hesapları</p>
    </a>
    <a href="index.php?page=services" class="bg-white rounded-xl border border-slate-200 p-4 hover:border-slate-300 transition-colors">
        <p class="text-xs font-medium text-slate-500">Hizmetler</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900"><?= (int) $services ?></p>
        <p class="mt-1 text-[11px] text-slate-400">Yayımlanmış hizmet kartı</p>
    </a>
    <a href="index.php?page=reviews" class="bg-white rounded-xl border border-slate-200 p-4 hover:border-slate-300 transition-colors">
        <p class="text-xs font-medium text-slate-500">Müşteri yorumları</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900"><?= (int) $reviews ?></p>
        <p class="mt-1 text-[11px] text-slate-400">Ana sayfada dönen alıntılar</p>
    </a>
    <a href="index.php?page=settings&amp;tab=newsletter" class="bg-white rounded-xl border border-slate-200 p-4 hover:border-slate-300 transition-colors">
        <p class="text-xs font-medium text-slate-500">Bülten</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900"><?= (int) $subscribers ?></p>
        <p class="mt-1 text-[11px] text-slate-400">E-posta kayıtları</p>
    </a>
    <a href="index.php?page=settings" class="bg-white rounded-xl border border-slate-200 p-4 hover:border-slate-300 transition-colors">
        <p class="text-xs font-medium text-slate-500">Site</p>
        <p class="mt-1 text-lg font-semibold text-slate-900 truncate"><?= e(option_get($pdo, 'site_title', 'Site')) ?></p>
        <p class="mt-1 text-[11px] text-slate-400 truncate"><?= e(public_url()) ?></p>
    </a>
</div>

<div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-6">
    <a class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm font-medium text-slate-800 hover:border-slate-300" href="index.php?page=post-new">+ Yazı ekle</a>
    <a class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm font-medium text-slate-800 hover:border-slate-300" href="index.php?page=pages">Sayfa ekle</a>
    <a class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm font-medium text-slate-800 hover:border-slate-300" href="index.php?page=gallery">Galeri</a>
    <a class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm font-medium text-slate-800 hover:border-slate-300" href="index.php?page=services">Hizmet ekle</a>
    <a class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm font-medium text-slate-800 hover:border-slate-300" href="index.php?page=spot">Vitrin</a>
    <a class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm font-medium text-slate-800 hover:border-slate-300" href="index.php?page=home">Ana sayfa sırası</a>
    <a class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm font-medium text-slate-800 hover:border-slate-300" href="index.php?page=places">Öne çıkanlar</a>
    <a class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm font-medium text-slate-800 hover:border-slate-300" href="index.php?page=reviews">Müşteri yorumları</a>
</div>

<?php if ($todo): ?>
<div class="mb-6 bg-white rounded-xl border border-amber-200 p-4">
    <h2 class="text-sm font-semibold text-slate-900">Yapılacaklar</h2>
    <ul class="mt-3 space-y-2">
        <?php foreach ($todo as $item): ?>
            <li class="flex items-start justify-between gap-3 text-sm">
                <div>
                    <p class="font-medium text-slate-800"><?= e($item[0]) ?></p>
                    <p class="text-slate-500 text-xs"><?= e($item[1]) ?></p>
                </div>
                <a class="shrink-0 text-xs font-semibold text-[#2271b1] hover:underline" href="<?= e($item[2]) ?>">Aç</a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200">
            <h2 class="text-sm font-semibold text-slate-900">Son yazılar</h2>
            <a href="index.php?page=posts" class="text-sm text-[#2271b1] hover:underline">Tümü</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Başlık</th>
                        <th class="px-4 py-3 font-medium">Durum</th>
                        <th class="px-4 py-3 font-medium text-right">Görüntülenme</th>
                        <th class="px-4 py-3 font-medium">Tarih</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!$recentPosts): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-slate-500">Henüz yazı yok. <a class="text-[#2271b1] font-medium" href="index.php?page=post-new">İlk yazıyı ekle</a></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentPosts as $row): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <a href="index.php?page=post-new&amp;id=<?= (int) $row['id'] ?>" class="font-medium text-slate-900 hover:text-[#2271b1]">
                                        <?= e((string) $row['title']) ?>
                                    </a>
                                    <p class="text-[11px] text-slate-400"><?= e((string) $row['display_name']) ?></p>
                                </td>
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

    <div class="space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <h2 class="text-sm font-semibold text-slate-900 mb-1">Haftalık tempo</h2>
            <p class="text-xs text-slate-500 mb-4">Son 7 günde eklenen yazılar</p>
            <div class="h-40 flex items-end gap-2">
                <?php foreach ($chartDays as $bar): ?>
                    <?php $h = max(10, (int) round(($bar['value'] / $maxChart) * 100)); ?>
                    <div class="flex-1 flex flex-col items-center gap-1.5">
                        <span class="text-[10px] font-medium text-slate-500"><?= (int) $bar['value'] ?></span>
                        <div class="w-full rounded-t-md" style="height:<?= (int) $h ?>%;background:var(--p-accent);opacity:.85" title="<?= (int) $bar['value'] ?>"></div>
                        <span class="text-[10px] text-slate-400"><?= e($bar['label']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-900">Onay bekleyen yorumlar</h2>
                <a href="index.php?page=comments" class="text-xs text-[#2271b1] hover:underline">Yorumlar</a>
            </div>
            <?php if (!$pendingList): ?>
                <p class="text-sm text-slate-500">Bekleyen yorum yok.</p>
            <?php else: ?>
                <ul class="space-y-3">
                    <?php foreach ($pendingList as $c): ?>
                        <li class="text-sm">
                            <p class="font-medium text-slate-800"><?= e((string) $c['author_name']) ?></p>
                            <p class="text-slate-500 line-clamp-2 text-xs mt-0.5"><?= e($clip((string) $c['content'], 90)) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <?php if ($inbox): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-900">İletişim kutusu</h2>
                <a href="index.php?page=contact" class="text-xs text-[#2271b1] hover:underline">İletişim</a>
            </div>
            <ul class="space-y-3">
                <?php foreach ($inbox as $m): ?>
                    <li class="text-sm">
                        <p class="font-medium text-slate-800"><?= e((string) $m['author_name']) ?></p>
                        <p class="text-xs text-slate-500 line-clamp-2"><?= e($clip((string) $m['message'], 80)) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>
