<?php
declare(strict_types=1);
if (!isset($pdo) || !($pdo instanceof PDO)) {
    return;
}
$spotOn = option_get($pdo, 'spot_show_home', '1') === '1';
$spotSlides = $spotOn ? cms_spot_slides($pdo) : array();
$spotItems = array();
if ($spotOn) {
    for ($si = 1; $si <= 3; $si++) {
        $stitle = trim(option_get($pdo, 'spot_' . $si . '_title', ''));
        $stext = trim(option_get($pdo, 'spot_' . $si . '_text', ''));
        if ($stitle !== '' || $stext !== '') {
            $spotItems[] = array('title' => $stitle, 'text' => $stext);
        }
    }
}
if (!$spotOn || (!$spotSlides && option_get($pdo, 'spot_heading', '') === '')) {
    return;
}
?>
</main>
<section class="cms-spot py-14 md:py-20 px-4">
    <div class="cms-spot-grid max-w-6xl mx-auto">
        <?php if ($spotSlides): ?>
            <div class="cms-spot-photo" id="cms-spot">
                <?php if (count($spotSlides) > 1): ?>
                    <div class="cms-spot-dots" role="tablist" aria-label="Vitrin görselleri">
                        <?php foreach ($spotSlides as $di => $slide): ?>
                            <button type="button" class="<?= $di === 0 ? 'is-on' : '' ?>" data-spot-dot="<?= (int) $di ?>" aria-label="Görsel <?= (int) $di + 1 ?>"></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php foreach ($spotSlides as $si => $slide): ?>
                    <div class="cms-spot-slide <?= $si === 0 ? 'is-on' : '' ?>">
                        <img src="<?= e(media_src((string) $slide['image'])) ?>" alt="">
                        <div class="cms-spot-badge">
                            <span class="cms-spot-pin"><?php
                            echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s7-5.4 7-11a7 7 0 1 0-14 0c0 5.6 7 11 7 11Z"/><circle cx="12" cy="10" r="2.2"/></svg>';
                            ?></span>
                            <div>
                                <?php if (trim((string) $slide['kicker']) !== ''): ?><span><?= e((string) $slide['kicker']) ?></span><?php endif; ?>
                                <?php if (trim((string) $slide['title']) !== ''): ?><strong><?= e((string) $slide['title']) ?></strong><?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div>
            <p class="cms-spot-kicker"><?= e(option_get($pdo, 'spot_kicker', 'Çalışma şeklimiz')) ?></p>
            <h2><?= e(option_get($pdo, 'spot_heading', 'Sade, canlı ve panelden yönetilen bir site deneyimi')) ?></h2>
            <?php if (trim(option_get($pdo, 'spot_text', '')) !== ''): ?>
                <p class="cms-spot-lead"><?= e(option_get($pdo, 'spot_text', '')) ?></p>
            <?php endif; ?>
            <?php if ($spotItems): ?>
                <div class="cms-spot-list">
                    <?php foreach ($spotItems as $ii => $it): ?>
                        <div class="cms-spot-item">
                            <span class="cms-spot-ico"><?= cms_spot_icon($ii + 1) ?></span>
                            <div>
                                <?php if ($it['title'] !== ''): ?><h3><?= e($it['title']) ?></h3><?php endif; ?>
                                <?php if ($it['text'] !== ''): ?><p><?= e($it['text']) ?></p><?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<main class="max-w-5xl mx-auto px-4 py-10">
