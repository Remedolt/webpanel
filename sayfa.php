<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '') {
    header('Location: ' . public_url());
    exit;
}

$stmt = $pdo->prepare("SELECT id, title, slug, content FROM site_pages WHERE slug = ? AND status = 'publish' LIMIT 1");
$stmt->execute([$slug]);
$page = $stmt->fetch();
if (!$page) {
    http_response_code(404);
    $docTitle = 'Sayfa bulunamadı — ' . $siteTitle;
    $metaDescription = 'İstenen sayfa bulunamadı.';
    require __DIR__ . '/site/header.php';
    echo '<h1 class="text-2xl font-bold">Sayfa bulunamadı</h1>';
    require __DIR__ . '/site/footer.php';
    exit;
}

$isContact = ((string) $page['slug'] === 'iletisim');

if ($isContact && $_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'contact') {
    csrf_verify();
    $honeypot = trim((string) ($_POST['website'] ?? ''));
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));
    $last = (int) ($_SESSION['last_contact_at'] ?? 0);
    $redirectTo = page_permalink($page['slug']);

    if ($honeypot !== '') {
        flash_set('success', 'Mesajınız alındı.', 'flash_public');
        redirect($redirectTo);
    }
    if (time() - $last < 30) {
        flash_set('error', 'Lütfen tekrar göndermeden önce kısa bir süre bekleyin.', 'flash_public');
        redirect($redirectTo);
    }
    if ($name === '' || $email === '' || $message === '') {
        flash_set('error', 'Tüm alanlar zorunludur.', 'flash_public');
        redirect($redirectTo);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('error', 'Geçerli bir e-posta girin.', 'flash_public');
        redirect($redirectTo);
    }
    if (strlen($message) > 4000) {
        flash_set('error', 'Mesaj çok uzun.', 'flash_public');
        redirect($redirectTo);
    }

    $ins = $pdo->prepare('INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)');
    $ins->execute([$name, $email, $message]);
    $_SESSION['last_contact_at'] = time();
    flash_set('success', 'Mesajınız alındı. En kısa sürede dönüş yapılacak.', 'flash_public');
    redirect($redirectTo);
}

$docTitle = (string) $page['title'] . ' — ' . $siteTitle;
$metaDescription = excerpt_plain((string) $page['content'], 160);
$canonicalUrl = page_permalink($page['slug']);
require __DIR__ . '/site/header.php';
?>
<div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_18rem] gap-8 items-start">
    <article class="max-w-3xl">
        <h1 class="text-4xl font-bold text-slate-900"><?= e((string) $page['title']) ?></h1>
        <div class="mt-8 article-body text-slate-700 leading-7">
            <?= public_html((string) $page['content']) ?>
        </div>

        <?php if ($isContact): ?>
            <form method="post" action="<?= e(page_permalink($page['slug'])) ?>" class="mt-8 bg-white border border-slate-200 rounded-xl p-5 space-y-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="contact">
                <div class="hidden" aria-hidden="true">
                    <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="name">Ad</label>
                    <input id="name" name="name" required maxlength="120"
                           class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="email">E-posta</label>
                    <input id="email" name="email" type="email" required maxlength="190"
                           class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="message">Mesaj</label>
                    <textarea id="message" name="message" required maxlength="4000" rows="6"
                              class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"></textarea>
                </div>
                <button type="submit" class="rounded-md bg-ink px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Gönder</button>
            </form>
        <?php endif; ?>
    </article>
    <?php require __DIR__ . '/site/sidebar.php'; ?>
</div>
<?php require __DIR__ . '/site/footer.php'; ?>
