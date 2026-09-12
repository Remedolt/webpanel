<?php
declare(strict_types=1);
if (!isset($pdo) || !($pdo instanceof PDO)) {
    return;
}
$filmOn = option_get($pdo, 'film_show_home', '1') === '1';
$filmUrl = $filmOn ? trim(option_get($pdo, 'film_url', '')) : '';
$filmSrc = $filmOn ? cms_film_source($filmUrl) : array('type' => '', 'src' => '');
$filmPoster = $filmOn ? cms_film_poster($pdo) : '';
$filmHeading = option_get($pdo, 'film_heading', 'Nasıl çalıştığımızı izleyin');
$filmKicker = option_get($pdo, 'film_kicker', 'Tanıtım');
$filmText = option_get($pdo, 'film_text', '');
$filmOverlay = option_get($pdo, 'film_overlay', '');
if (!$filmOn || ($filmPoster === '' && $filmSrc['src'] === '' && trim($filmHeading) === '')) {
    return;
}
?>
</main>
<section class="cms-film py-16 md:py-20 px-4" id="cms-film">
    <div class="cms-film-inner">
        <div class="cms-film-copy cms-reveal">
            <?php if (trim($filmKicker) !== ''): ?>
                <p class="cms-film-kicker"><?= e($filmKicker) ?></p>
            <?php endif; ?>
            <?php if (trim($filmHeading) !== ''): ?>
                <h2><?= e($filmHeading) ?></h2>
            <?php endif; ?>
            <?php if (trim($filmText) !== ''): ?>
                <p class="cms-film-lead"><?= e($filmText) ?></p>
            <?php endif; ?>
        </div>
        <?php if ($filmPoster !== '' || $filmSrc['src'] !== ''): ?>
            <div class="cms-film-card cms-reveal">
                <?php if ($filmPoster !== ''): ?>
                    <img src="<?= e($filmPoster) ?>" alt="">
                <?php endif; ?>
                <div class="cms-film-shade" aria-hidden="true"></div>
                <?php if (trim($filmOverlay) !== ''): ?>
                    <p class="cms-film-caption"><?= e($filmOverlay) ?></p>
                <?php endif; ?>
                <?php if ($filmSrc['src'] !== ''): ?>
                    <button type="button" class="cms-film-play" data-film-play data-film-type="<?= e((string) $filmSrc['type']) ?>" data-src="<?= e((string) $filmSrc['src']) ?>" aria-label="Videoyu oynat">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.14v13.72L19 12 8 5.14Z"/></svg>
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<div id="cms-film-modal" class="cms-film-modal" hidden>
    <button type="button" class="cms-film-close" data-film-close aria-label="Kapat">&times;</button>
    <div class="cms-film-stage" data-film-stage></div>
</div>
<main class="max-w-5xl mx-auto px-4 py-10">
