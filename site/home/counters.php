<?php
declare(strict_types=1);
if (!isset($pdo) || !($pdo instanceof PDO)) {
    return;
}
$homeCounters = [];
if (option_get($pdo, 'counters_show_home', '1') === '1') {
    try {
        $homeCounters = $pdo->query("SELECT label, value_num, suffix FROM counters WHERE status = 'publish' ORDER BY sort_order ASC, id ASC")->fetchAll();
    } catch (PDOException $e) {
        $homeCounters = [];
    }
}
if (!$homeCounters) {
    return;
}
?>
</main>
<section class="cms-stats" id="cms-stats">
    <div class="cms-stats-inner">
        <p class="cms-stats-kicker">Özet</p>
        <h2><?= e(option_get($pdo, 'counters_heading', 'Rakamlarla')) ?></h2>
        <div class="cms-stats-grid">
            <?php foreach ($homeCounters as $i => $c): ?>
                <div class="cms-stat" style="transition-delay:<?= (int) $i * 110 ?>ms">
                    <p class="cms-stat-num">
                        <span data-counter="<?= (int) $c['value_num'] ?>">0</span><?php if (trim((string) ($c['suffix'] ?? '')) !== ''): ?><span class="sfx"><?= e((string) $c['suffix']) ?></span><?php endif; ?>
                    </p>
                    <span class="cms-stat-bar" style="transition-delay:<?= 220 + (int) $i * 90 ?>ms" aria-hidden="true"></span>
                    <p class="cms-stat-label"><?= e((string) $c['label']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<main class="max-w-5xl mx-auto px-4 py-10">
