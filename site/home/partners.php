<?php
declare(strict_types=1);
if (!isset($pdo) || !($pdo instanceof PDO)) {
    return;
}
$homePartners = [];
if (option_get($pdo, 'partners_show_home', '1') === '1') {
    try {
        $homePartners = $pdo->query("SELECT name, logo, url FROM partners WHERE status = 'publish' ORDER BY sort_order ASC, id ASC")->fetchAll();
    } catch (PDOException $e) {
        $homePartners = [];
    }
}
if (!$homePartners) {
    return;
}
$loop = array_merge($homePartners, $homePartners);
?>
<section class="mb-12">
    <div class="flex items-end justify-between gap-3 mb-6">
        <h2 class="text-2xl font-semibold text-slate-900"><?= e(option_get($pdo, 'partners_heading', 'Referanslar')) ?></h2>
        <a class="text-sm font-medium text-sky-700 hover:underline" href="<?= e(partners_permalink()) ?>">Tümü</a>
    </div>
    <div class="cms-marquee rounded-xl border border-slate-200 bg-white py-6">
        <div class="cms-marquee-track">
            <?php foreach ($loop as $partner): ?>
                <?php
                $href = trim((string) ($partner['url'] ?? ''));
                $title = trim((string) ($partner['name'] ?? ''));
                $inner = '';
                if (!empty($partner['logo'])) {
                    $inner .= '<img src="' . e(media_src($partner['logo'])) . '" alt="' . e($title) . '">';
                }
                if ($title !== '') {
                    $inner .= '<span class="cms-partner-title">' . e($title) . '</span>';
                }
                if ($inner === '') {
                    continue;
                }
                ?>
                <?php if ($href !== ''): ?>
                    <a href="<?= e($href) ?>" target="_blank" rel="noopener noreferrer" class="cms-partner-item"><?= $inner ?></a>
                <?php else: ?>
                    <span class="cms-partner-item"><?= $inner ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
