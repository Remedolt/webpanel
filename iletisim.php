<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';

$req = (string) ($_SERVER['REQUEST_URI'] ?? '');
if (strpos($req, 'iletisim.php') !== false) {
    header('Location: ' . contact_permalink(), true, 301);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'inquiry') {
    csrf_verify();
    $honeypot = trim((string) ($_POST['website'] ?? ''));
    $name = trim((string) ($_POST['author_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));
    $type = (string) ($_POST['inquiry_type'] ?? 'mesaj');
    if (!in_array($type, array('mesaj', 'teklif', 'randevu'), true)) {
        $type = 'mesaj';
    }
    $svcPick = trim((string) ($_POST['service_slug'] ?? ''));
    if ($svcPick !== '') {
        try {
            $st = $pdo->prepare("SELECT title FROM services WHERE slug = ? AND status = 'publish' LIMIT 1");
            $st->execute([$svcPick]);
            $svcTitle = (string) ($st->fetchColumn() ?: '');
            if ($svcTitle !== '') {
                $message = 'Hizmet: ' . $svcTitle . "\n\n" . $message;
            }
        } catch (PDOException $e) {
        }
    }
    if ($honeypot !== '') {
        flash_set('success', 'Mesajınız alındı, teşekkürler.');
    } elseif ($name === '' || $email === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('error', 'Ad, geçerli e-posta ve mesaj zorunludur.');
    } else {
        try {
            $ins = $pdo->prepare('INSERT INTO inquiries (page_id, author_name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?, ?)');
            $ins->execute([null, $name, $email, $phone, $type, $message]);
        } catch (PDOException $e) {
            $ins = $pdo->prepare('INSERT INTO inquiries (page_id, author_name, email, message) VALUES (?, ?, ?, ?)');
            $ins->execute([null, $name, $email, $message]);
        }
        cms_mail_inquiry($pdo, array(
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $type,
            'message' => $message,
        ));
        flash_set('success', 'Mesajınız alındı, teşekkürler.');
    }
    $back = contact_permalink();
    if ($svcPick !== '') {
        $back .= (strpos($back, '?') === false ? '?' : '&') . 'hizmet=' . rawurlencode($svcPick);
    }
    header('Location: ' . $back);
    exit;
}

$heading = option_get($pdo, 'contact_heading', 'İletişim');
$intro = option_get($pdo, 'contact_intro', '');
$address = option_get($pdo, 'contact_address', '');
$phone = option_get($pdo, 'contact_phone', '');
$email = option_get($pdo, 'contact_email', '');
$whatsapp = option_get($pdo, 'contact_whatsapp', '');
$hours = option_get($pdo, 'contact_hours', '');
$mapSrc = google_map_embed_src(option_get($pdo, 'contact_map', ''));
if ($mapSrc === '' && $address !== '') {
    $mapSrc = google_map_embed_src($address);
}
$showForm = option_get($pdo, 'contact_show_form', '1') === '1';

$docTitle = $heading . ' — ' . $siteTitle;
if (trim(option_get($pdo, 'seo_description', '')) !== '') {
    $seoDescription = option_get($pdo, 'seo_description', $siteTagline);
}

$waHref = '';
if ($whatsapp !== '') {
    $digits = preg_replace('/\D+/', '', $whatsapp);
    if ($digits !== '') {
        $waHref = 'https://wa.me/' . $digits;
    }
}

$serviceChoices = [];
try {
    $serviceChoices = $pdo->query("SELECT title, slug FROM services WHERE status = 'publish' ORDER BY sort_order ASC, id ASC")->fetchAll();
} catch (PDOException $e) {
    $serviceChoices = [];
}
$preService = trim((string) ($_GET['hizmet'] ?? ''));

require __DIR__ . '/site/header.php';
?>
<article class="max-w-5xl">
    <?php cms_crumbs(array(
        array('label' => 'Ana sayfa', 'url' => public_url()),
        array('label' => $heading, 'url' => ''),
    )); ?>
    <p class="text-xs uppercase tracking-wide text-slate-400">İletişim</p>
    <h1 class="mt-2 text-4xl font-bold text-slate-900"><?= e($heading) ?></h1>
    <?php $inqFlash = flash_get(); if (!empty($inqFlash)): ?>
        <div class="mt-4 rounded-md border px-3 py-2 text-sm <?= (($inqFlash['type'] ?? '') === 'error') ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-800' ?>">
            <?= e((string) $inqFlash['message']) ?>
        </div>
    <?php endif; ?>
    <?php if (trim(strip_tags($intro)) !== ''): ?>
        <div class="mt-6 leading-7 text-slate-700 space-y-3"><?= public_html($intro) ?></div>
    <?php endif; ?>

    <div class="mt-10 grid grid-cols-1 <?= $mapSrc !== '' ? 'lg:grid-cols-2' : '' ?> gap-6 items-stretch">
        <div class="space-y-6">
            <div class="bg-white border border-slate-200 rounded-xl p-5 space-y-3">
                <?php if ($address !== ''): ?>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Adres</p>
                        <p class="mt-1 text-slate-800 whitespace-pre-wrap"><?= e($address) ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($phone !== ''): ?>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Telefon</p>
                        <a class="mt-1 block text-sky-700 hover:underline" href="tel:<?= e(preg_replace('/\s+/', '', $phone)) ?>"><?= e($phone) ?></a>
                    </div>
                <?php endif; ?>
                <?php if ($email !== ''): ?>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">E-posta</p>
                        <a class="mt-1 block text-sky-700 hover:underline" href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
                    </div>
                <?php endif; ?>
                <?php if ($waHref !== ''): ?>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">WhatsApp</p>
                        <a class="mt-1 block text-sky-700 hover:underline" href="<?= e($waHref) ?>" target="_blank" rel="noopener noreferrer">WhatsApp ile yazın</a>
                    </div>
                <?php endif; ?>
                <?php if ($hours !== ''): ?>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Çalışma saatleri</p>
                        <p class="mt-1 text-slate-800"><?= e($hours) ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($showForm): ?>
                <form method="post" class="space-y-3 bg-white border border-slate-200 rounded-xl p-5">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="inquiry">
                    <h2 class="text-lg font-semibold text-slate-900">Mesaj gönder</h2>
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="inq-name">Adınız</label>
                        <input id="inq-name" name="author_name" required class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="inq-email">E-posta</label>
                        <input id="inq-email" name="email" type="email" required class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div class="hidden" aria-hidden="true">
                        <label for="inq-web">Website</label>
                        <input id="inq-web" name="website" tabindex="-1" autocomplete="off">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="inq-phone">Telefon</label>
                        <input id="inq-phone" name="phone" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="inq-type">Konu</label>
                        <select id="inq-type" name="inquiry_type" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                            <option value="mesaj">Mesaj</option>
                            <option value="teklif">Teklif</option>
                            <option value="randevu">Randevu</option>
                        </select>
                    </div>
                    <?php if ($serviceChoices): ?>
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="inq-svc">Hizmet</label>
                        <select id="inq-svc" name="service_slug" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                            <option value="">Seçilmedi</option>
                            <?php foreach ($serviceChoices as $svc): ?>
                                <option value="<?= e((string) $svc['slug']) ?>" <?= $preService === (string) $svc['slug'] ? 'selected' : '' ?>><?= e((string) $svc['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="inq-msg">Mesaj</label>
                        <textarea id="inq-msg" name="message" required rows="4" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"></textarea>
                    </div>
                    <button type="submit" class="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Gönder</button>
                </form>
            <?php endif; ?>
        </div>
        <?php if ($mapSrc !== ''): ?>
            <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-100 min-h-[320px]">
                <iframe title="Google Harita" src="<?= e($mapSrc) ?>" class="w-full h-full min-h-[420px]" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
            </div>
        <?php endif; ?>
    </div>
</article>
<?php require __DIR__ . '/site/footer.php'; ?>
