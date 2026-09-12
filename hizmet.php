<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '') {
    header('Location: ' . services_permalink());
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM services WHERE slug = ? AND status = 'publish' LIMIT 1");
$stmt->execute([$slug]);
$item = $stmt->fetch();
if (!$item) {
    http_response_code(404);
    $docTitle = 'Hizmet bulunamadı — ' . $siteTitle;
    require __DIR__ . '/site/header.php';
    echo '<h1 class="text-2xl font-bold">Hizmet bulunamadı</h1><p class="mt-3 text-slate-600">Bu hizmet yayımlanmamış veya silinmiş olabilir.</p><p class="mt-4"><a class="text-sky-700 hover:underline" href="' . e(services_permalink()) . '">Tüm hizmetler</a></p>';
    require __DIR__ . '/site/footer.php';
    exit;
}

$heading = option_get($pdo, 'services_heading', 'Hizmetler');
$docTitle = (string) $item['title'] . ' — ' . $siteTitle;
if (!empty($item['excerpt'])) {
    $seoDescription = (string) $item['excerpt'];
}
if (!empty($item['image'])) {
    $ogImage = media_src((string) $item['image']);
}

$others = [];
try {
    $st = $pdo->prepare("SELECT title, slug, excerpt, image FROM services WHERE status = 'publish' AND id != ? ORDER BY sort_order ASC, id ASC LIMIT 3");
    $st->execute([(int) $item['id']]);
    $others = $st->fetchAll();
} catch (PDOException $e) {
    $others = [];
}

$contactHref = contact_permalink();
$sep = strpos($contactHref, '?') === false ? '?' : '&';
$contactHref .= $sep . 'hizmet=' . rawurlencode((string) $item['slug']);

require __DIR__ . '/site/header.php';
?>
<article class="max-w-3xl cms-reveal is-on">
    <?php cms_crumbs(array(
        array('label' => 'Ana sayfa', 'url' => public_url()),
        array('label' => $heading, 'url' => services_permalink()),
        array('label' => (string) $item['title'], 'url' => ''),
    )); ?>
    <p class="text-xs uppercase tracking-wide text-slate-400">Hizmet</p>
    <h1 class="mt-2 text-4xl font-bold text-slate-900"><?= e((string) $item['title']) ?></h1>
    <?php if (!empty($item['image'])): ?>
        <img src="<?= e(media_src((string) $item['image'])) ?>" alt="" class="mt-6 w-full rounded-xl border border-slate-200 object-cover max-h-[380px]">
    <?php endif; ?>
    <?php if (!empty($item['excerpt'])): ?>
        <p class="mt-6 text-lg text-slate-600"><?= e((string) $item['excerpt']) ?></p>
    <?php endif; ?>
    <div class="mt-8 prose prose-slate max-w-none leading-7 space-y-4">
        <?= public_html((string) ($item['content'] ?? '')) ?>
    </div>
    <a href="<?= e($contactHref) ?>" class="inline-flex mt-10 rounded-md bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">Teklif alın</a>
</article>
<?php
$svcLd = array(
    '@context' => 'https://schema.org',
    '@type' => 'Service',
    'name' => (string) $item['title'],
    'url' => service_permalink($item['slug']),
    'provider' => array('@type' => 'Organization', 'name' => $siteTitle, 'url' => public_url()),
);
if (!empty($item['excerpt'])) {
    $svcLd['description'] = (string) $item['excerpt'];
}
if (!empty($item['image'])) {
    $svcLd['image'] = media_src((string) $item['image']);
}
?>
<script type="application/ld+json"><?= json_encode($svcLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php if ($others): ?>
<section class="cms-svc mt-14 max-w-5xl" id="cms-svc-more">
    <h2 class="text-xl font-semibold text-slate-900 mb-6">Diğer hizmetler</h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <?php foreach ($others as $i => $row): ?>
            <?php cms_service_card($row, (int) $i); ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/site/footer.php'; ?>
