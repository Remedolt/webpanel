<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';

$heading = option_get($pdo, 'services_heading', 'Hizmetler');
$items = [];
try {
    $items = $pdo->query("SELECT title, slug, excerpt, image FROM services WHERE status = 'publish' ORDER BY sort_order ASC, id ASC")->fetchAll();
} catch (PDOException $e) {
    $items = [];
}
$docTitle = $heading . ' — ' . $siteTitle;
require __DIR__ . '/site/header.php';
?>
<section class="cms-svc max-w-5xl" id="cms-svc">
    <?php cms_crumbs(array(
        array('label' => 'Ana sayfa', 'url' => public_url()),
        array('label' => $heading, 'url' => ''),
    )); ?>
    <p class="text-xs uppercase tracking-wide text-slate-400">Hizmetler</p>
    <h1 class="mt-2 text-4xl font-bold text-slate-900"><?= e($heading) ?></h1>
    <div class="mt-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (!$items): ?>
            <div class="sm:col-span-2 lg:col-span-3 bg-white border border-slate-200 rounded-xl p-8 text-slate-500">Henüz yayımlanmış hizmet yok.</div>
        <?php else: ?>
            <?php foreach ($items as $i => $item): ?>
                <?php cms_service_card($item, (int) $i); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/site/footer.php'; ?>
