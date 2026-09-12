<?php
declare(strict_types=1);
if (!isset($pdo) || !($pdo instanceof PDO)) {
    return;
}
$homeServices = [];
if (option_get($pdo, 'services_show_home', '1') === '1') {
    try {
        $homeServices = $pdo->query("SELECT title, slug, excerpt, image FROM services WHERE status = 'publish' ORDER BY sort_order ASC, id ASC")->fetchAll();
    } catch (PDOException $e) {
        $homeServices = [];
    }
}
if (!$homeServices) {
    return;
}
?>
<section class="cms-svc mb-12" id="cms-svc">
    <div class="flex items-end justify-between gap-3 mb-8">
        <h2 class="text-2xl font-semibold text-slate-900"><?= e(option_get($pdo, 'services_heading', 'Hizmetler')) ?></h2>
        <a class="text-sm font-medium text-sky-700 hover:underline" href="<?= e(services_permalink()) ?>">Tümü</a>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($homeServices as $i => $item): ?>
            <?php cms_service_card($item, (int) $i); ?>
        <?php endforeach; ?>
    </div>
</section>
