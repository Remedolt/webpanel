<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '') {
    header('Location: ' . public_url());
    exit;
}
if ($slug === 'iletisim') {
    header('Location: ' . contact_permalink(), true, 301);
    exit;
}
if ($slug === 'sss') {
    header('Location: ' . faq_permalink(), true, 301);
    exit;
}
if ($slug === 'ekip') {
    header('Location: ' . staff_list_permalink(), true, 301);
    exit;
}
if ($slug === 'referanslar') {
    header('Location: ' . partners_permalink(), true, 301);
    exit;
}
if ($slug === 'ara') {
    header('Location: ' . search_permalink(), true, 301);
    exit;
}
if ($slug === 'yazilar') {
    header('Location: ' . posts_list_permalink(), true, 301);
    exit;
}
if ($slug === 'hizmetler') {
    header('Location: ' . services_permalink(), true, 301);
    exit;
}
if ($slug === 'hizmet') {
    header('Location: ' . services_permalink(), true, 301);
    exit;
}
if ($slug === 'kategori') {
    header('Location: ' . posts_list_permalink(), true, 301);
    exit;
}
if ($slug === 'fiyatlar') {
    header('Location: ' . services_permalink(), true, 301);
    exit;
}
$req = (string) ($_SERVER['REQUEST_URI'] ?? '');
if (strpos($req, 'sayfa.php') !== false) {
    header('Location: ' . page_permalink($slug), true, 301);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM site_pages WHERE slug = ? AND status = 'publish' LIMIT 1");
$stmt->execute([$slug]);
$page = $stmt->fetch();
if (!$page) {
    http_response_code(404);
    $docTitle = 'Sayfa bulunamadı — ' . $siteTitle;
    require __DIR__ . '/site/header.php';
    echo '<h1 class="text-2xl font-bold">Sayfa bulunamadı</h1>';
    require __DIR__ . '/site/footer.php';
    exit;
}

$pageSeoTitle = trim((string) ($page['seo_title'] ?? ''));
$pageSeoDesc = trim((string) ($page['seo_description'] ?? ''));
$docTitle = ($pageSeoTitle !== '' ? $pageSeoTitle : ((string) $page['title'] . ' — ' . $siteTitle));
if ($pageSeoDesc !== '') {
    $seoDescription = $pageSeoDesc;
}
$template = option_pick($page['template'] ?? 'default', ['default', 'full', 'landing', 'sidebar', 'contact'], 'default');
$otherPages = $pdo->prepare("SELECT title, slug FROM site_pages WHERE status = 'publish' AND id != ? ORDER BY sort_order ASC, title ASC");
$otherPages->execute([(int) $page['id']]);
$otherPages = $otherPages->fetchAll();
$pageImages = [];
try {
    $stImg = $pdo->prepare('SELECT image FROM page_images WHERE page_id = ? ORDER BY sort_order ASC, id ASC');
    $stImg->execute([(int) $page['id']]);
    $pageImages = $stImg->fetchAll();
} catch (PDOException $e) {
    $pageImages = [];
}
$videoUrl = trim((string) ($page['video_url'] ?? ''));
$ytSrc = youtube_embed_src($videoUrl, false);
$isFileVideo = (bool) preg_match('/\.(mp4|webm)(\?|$)/i', $videoUrl);

require __DIR__ . '/site/header.php';

$articleClass = ($template === 'full' || $template === 'contact') ? 'max-w-5xl' : 'max-w-3xl';
if ($template === 'landing' && !empty($page['featured_image'])):
?>
    <div class="-mt-10 -mx-4 mb-10">
        <img src="<?= e(media_src($page['featured_image'])) ?>" alt="" class="w-full max-h-[360px] object-cover">
    </div>
<?php endif; ?>

<?php if ($template === 'sidebar'): ?>
<div class="grid grid-cols-1 lg:grid-cols-[1fr_240px] gap-10">
<?php endif; ?>

<article class="<?= $template === 'sidebar' ? '' : $articleClass ?>">
    <?php cms_crumbs(array(
        array('label' => 'Ana sayfa', 'url' => public_url()),
        array('label' => (string) $page['title'], 'url' => ''),
    )); ?>
    <p class="text-xs uppercase tracking-wide text-slate-400">Sayfa</p>
    <h1 class="mt-2 text-4xl font-bold text-slate-900"><?= e((string) $page['title']) ?></h1>
    <?php if (!empty($page['excerpt'])): ?>
        <p class="mt-4 text-lg text-slate-600"><?= e((string) $page['excerpt']) ?></p>
    <?php endif; ?>
    <?php if ($template !== 'landing' && !empty($page['featured_image'])): ?>
        <img src="<?= e(media_src($page['featured_image'])) ?>" alt="" class="mt-6 w-full rounded-xl object-cover max-h-80">
    <?php endif; ?>
    <?php if ($ytSrc !== ''): ?>
        <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 aspect-video bg-black">
            <iframe title="Video" src="<?= e($ytSrc) ?>" class="h-full w-full min-h-[220px]" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>
    <?php elseif ($isFileVideo): ?>
        <video class="mt-6 w-full rounded-xl border border-slate-200" controls src="<?= e($videoUrl) ?>"></video>
    <?php endif; ?>
    <div class="mt-8 leading-7 space-y-4 text-slate-700">
        <?= public_html((string) $page['content']) ?>
    </div>
    <?php if ($pageImages): ?>
        <div class="mt-8 grid grid-cols-2 md:grid-cols-3 gap-3">
            <?php foreach ($pageImages as $img): ?>
                <img src="<?= e(media_src($img['image'])) ?>" alt="" class="w-full h-40 object-cover rounded-lg border border-slate-200">
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($template === 'contact'): ?>
        <p class="mt-10 text-sm text-slate-600">İletişim formu, harita ve adres için <a class="text-sky-700 font-medium hover:underline" href="<?= e(contact_permalink()) ?>">İletişim</a> sayfasını kullanın.</p>
    <?php endif; ?>
</article>

<?php if ($template === 'sidebar'): ?>
    <aside class="space-y-3">
        <h2 class="text-sm font-semibold text-slate-900">Diğer sayfalar</h2>
        <?php foreach ($otherPages as $op): ?>
            <a class="block rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm hover:border-sky-300" href="<?= e(page_permalink($op['slug'])) ?>"><?= e((string) $op['title']) ?></a>
        <?php endforeach; ?>
    </aside>
</div>
<?php elseif ($otherPages): ?>
    <section class="mt-12 <?= $articleClass ?>">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Diğer sayfalar</h2>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($otherPages as $op): ?>
                <a class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm hover:border-sky-300" href="<?= e(page_permalink($op['slug'])) ?>"><?= e((string) $op['title']) ?></a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/site/footer.php'; ?>
