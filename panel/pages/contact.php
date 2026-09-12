<?php
declare(strict_types=1);

if (!isset($pdo, $currentPage) || $currentPage !== 'contact') {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'settings') {
        option_set($pdo, 'contact_heading', trim((string) ($_POST['contact_heading'] ?? 'İletişim')));
        option_set($pdo, 'contact_intro', (string) ($_POST['contact_intro'] ?? ''));
        option_set($pdo, 'contact_address', trim((string) ($_POST['contact_address'] ?? '')));
        option_set($pdo, 'contact_phone', trim((string) ($_POST['contact_phone'] ?? '')));
        option_set($pdo, 'contact_email', trim((string) ($_POST['contact_email'] ?? '')));
        option_set($pdo, 'contact_whatsapp', trim((string) ($_POST['contact_whatsapp'] ?? '')));
        option_set($pdo, 'contact_hours', trim((string) ($_POST['contact_hours'] ?? '')));
        option_set($pdo, 'contact_map', trim((string) ($_POST['contact_map'] ?? '')));
        option_set($pdo, 'contact_show_form', isset($_POST['contact_show_form']) ? '1' : '0');
        option_set($pdo, 'contact_notify_mail', isset($_POST['contact_notify_mail']) ? '1' : '0');
        flash_set('success', 'İletişim sayfası kaydedildi.');
        redirect('index.php?page=contact');
    }
    $id = (int) ($_POST['inquiry_id'] ?? 0);
    if ($id > 0) {
        if ($action === 'read') {
            try {
                $pdo->prepare('UPDATE inquiries SET is_read = 1 WHERE id = ?')->execute([$id]);
            } catch (PDOException $e) {
            }
            flash_set('success', 'Mesaj okundu olarak işaretlendi.');
        } elseif ($action === 'unread') {
            try {
                $pdo->prepare('UPDATE inquiries SET is_read = 0 WHERE id = ?')->execute([$id]);
            } catch (PDOException $e) {
            }
            flash_set('success', 'Mesaj yeni olarak işaretlendi.');
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM inquiries WHERE id = ?')->execute([$id]);
            flash_set('success', 'Mesaj silindi.');
        }
    }
    redirect('index.php?page=contact');
}

$filter = (string) ($_GET['status'] ?? '');
if (!in_array($filter, ['', 'new', 'read'], true)) {
    $filter = '';
}

$inquiries = [];
try {
    $sql = 'SELECT i.id, i.author_name, i.email, i.message, i.created_at, i.is_read, i.phone, i.subject, p.title AS page_title
            FROM inquiries i
            LEFT JOIN site_pages p ON p.id = i.page_id';
    if ($filter === 'new') {
        $sql .= ' WHERE i.is_read = 0';
    } elseif ($filter === 'read') {
        $sql .= ' WHERE i.is_read = 1';
    }
    $sql .= ' ORDER BY i.created_at DESC';
    $inquiries = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) {
    try {
        $inquiries = $pdo->query(
            'SELECT i.id, i.author_name, i.email, i.message, i.created_at, 0 AS is_read, \'\' AS phone, \'mesaj\' AS subject, p.title AS page_title
             FROM inquiries i
             LEFT JOIN site_pages p ON p.id = i.page_id
             ORDER BY i.created_at DESC'
        )->fetchAll();
    } catch (PDOException $e2) {
        $inquiries = [];
    }
}

$totalAll = count($inquiries);
$newCount = 0;
$readCount = 0;
foreach ($inquiries as $row) {
    if ((int) ($row['is_read'] ?? 0) === 1) {
        $readCount++;
    } else {
        $newCount++;
    }
}
if ($filter !== '') {
    try {
        $newCount = (int) $pdo->query('SELECT COUNT(*) FROM inquiries WHERE is_read = 0')->fetchColumn();
        $readCount = (int) $pdo->query('SELECT COUNT(*) FROM inquiries WHERE is_read = 1')->fetchColumn();
        $totalAll = $newCount + $readCount;
    } catch (PDOException $e) {
        $totalAll = count($inquiries);
    }
}
$publicLink = contact_permalink();
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">İletişim</h1>
    <p class="mt-1 text-sm text-slate-500">Sabit iletişim sayfası: adres, harita, form. Yayın adresi: <a class="text-[#2271b1] hover:underline font-mono" href="<?= e($publicLink) ?>" target="_blank"><?= e($publicLink) ?></a></p>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-lg border border-slate-200 p-4 h-fit">
        <h2 class="text-sm font-semibold mb-4">Sayfa içeriği</h2>
        <form method="post" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="settings">
            <div>
                <label class="mb-1 block text-sm font-medium">Başlık</label>
                <input name="contact_heading" value="<?= e(option_get($pdo, 'contact_heading', 'İletişim')) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Giriş yazısı</label>
                <textarea name="contact_intro" rows="5" class="cms-editor w-full rounded-md border border-slate-200 px-3 py-2 text-sm"><?= e(option_get($pdo, 'contact_intro', '')) ?></textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Adres</label>
                <textarea name="contact_address" rows="2" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"><?= e(option_get($pdo, 'contact_address', '')) ?></textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Telefon</label>
                    <input name="contact_phone" value="<?= e(option_get($pdo, 'contact_phone', '')) ?>" placeholder="+90 …" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">E-posta</label>
                    <input name="contact_email" value="<?= e(option_get($pdo, 'contact_email', '')) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">WhatsApp</label>
                    <input name="contact_whatsapp" value="<?= e(option_get($pdo, 'contact_whatsapp', '')) ?>" placeholder="90555…" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Çalışma saatleri</label>
                    <input name="contact_hours" value="<?= e(option_get($pdo, 'contact_hours', '')) ?>" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Google Harita</label>
                <textarea name="contact_map" rows="3" placeholder="Adres yazın veya Google Haritalar → Paylaş → Harita yerleştir iframe’ini yapıştırın" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-mono"><?= e(option_get($pdo, 'contact_map', '')) ?></textarea>
                <p class="mt-1 text-[11px] text-slate-400">Yalnızca iletişim sayfasında görünür. Örnek: Kadıköy, İstanbul</p>
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="contact_show_form" value="1" <?= option_get($pdo, 'contact_show_form', '1') === '1' ? 'checked' : '' ?>>
                İletişim formunu göster
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="contact_notify_mail" value="1" <?= option_get($pdo, 'contact_notify_mail', '1') === '1' ? 'checked' : '' ?>>
                Yeni mesajı iletişim e-postasına gönder
            </label>
            <button class="rounded-md bg-[#2271b1] px-4 py-2 text-sm font-semibold text-white" type="submit">Sayfayı kaydet</button>
        </form>
    </div>
    <div class="bg-white rounded-lg border border-slate-200 p-4 h-fit">
        <h2 class="text-sm font-semibold mb-2">Gelen mesajlar</h2>
        <div class="mb-3 flex flex-wrap gap-2 text-sm">
            <a class="panel-chip <?= $filter === '' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600' ?>" href="index.php?page=contact">Tümü (<?= (int) $totalAll ?>)</a>
            <a class="panel-chip <?= $filter === 'new' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600' ?>" href="index.php?page=contact&amp;status=new">Yeni (<?= (int) $newCount ?>)</a>
            <a class="panel-chip <?= $filter === 'read' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600' ?>" href="index.php?page=contact&amp;status=read">Okunan (<?= (int) $readCount ?>)</a>
        </div>
        <?php if (!$inquiries): ?>
            <p class="text-sm text-slate-500">Henüz mesaj yok. Form gönderilince burada listelenir.</p>
        <?php else: ?>
            <ul class="divide-y divide-slate-100 max-h-[640px] overflow-auto">
                <?php foreach ($inquiries as $msg): ?>
                    <?php $isRead = (int) ($msg['is_read'] ?? 0) === 1; ?>
                    <li class="py-3 <?= $isRead ? '' : 'bg-sky-50/60' ?>">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <?php if (!$isRead): ?>
                                        <span class="inline-flex rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-semibold text-sky-800">Yeni</span>
                                    <?php endif; ?>
                                    <p class="text-sm font-semibold text-slate-900"><?= e((string) $msg['author_name']) ?></p>
                                    <a class="text-xs text-[#2271b1] hover:underline" href="mailto:<?= e((string) $msg['email']) ?>"><?= e((string) $msg['email']) ?></a>
                                </div>
                                <p class="mt-1 text-xs text-slate-400">
                                    <?= e(format_datetime((string) $msg['created_at'])) ?>
                                    <?php
                                    $subj = (string) ($msg['subject'] ?? 'mesaj');
                                    $subjLabels = array('mesaj' => 'Mesaj', 'teklif' => 'Teklif', 'randevu' => 'Randevu');
                                    echo ' · ' . e($subjLabels[$subj] ?? $subj);
                                    if (!empty($msg['phone'])) {
                                        echo ' · ' . e((string) $msg['phone']);
                                    }
                                    ?>
                                    <?= !empty($msg['page_title']) ? ' · ' . e((string) $msg['page_title']) : '' ?>
                                </p>
                                <p class="mt-2 text-sm text-slate-700 whitespace-pre-wrap"><?= e((string) $msg['message']) ?></p>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="inquiry_id" value="<?= (int) $msg['id'] ?>">
                                    <?php if ($isRead): ?>
                                        <input type="hidden" name="action" value="unread">
                                        <button type="submit" class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">Yeni yap</button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="read">
                                        <button type="submit" class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">Okundu</button>
                                    <?php endif; ?>
                                </form>
                                <form method="post" onsubmit="return confirm('Mesaj silinsin mi?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="inquiry_id" value="<?= (int) $msg['id'] ?>">
                                    <button type="submit" class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">Sil</button>
                                </form>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
