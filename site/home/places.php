<?php
declare(strict_types=1);
if (!isset($pdo) || !($pdo instanceof PDO) || option_get($pdo, 'place_show_home', '1') !== '1') {
    return;
}
$placeHeading = option_get($pdo, 'place_heading', 'İki bakış, tek sade deneyim');
$placeKicker = option_get($pdo, 'place_kicker', 'Çalışma alanımız');
$placeCards = array();
for ($pi = 1; $pi <= 2; $pi++) {
    $img = trim(option_get($pdo, 'place_' . $pi . '_image', ''));
    $title = trim(option_get($pdo, 'place_' . $pi . '_title', ''));
    $text = trim(option_get($pdo, 'place_' . $pi . '_text', ''));
    $kicker = trim(option_get($pdo, 'place_' . $pi . '_kicker', ''));
    $link = trim(option_get($pdo, 'place_' . $pi . '_link', ''));
    $btn = trim(option_get($pdo, 'place_' . $pi . '_btn', 'İncele'));
    if ($img === '' && $title === '' && $text === '') {
        continue;
    }
    $placeCards[] = array(
        'image' => $img,
        'title' => $title,
        'text' => $text,
        'kicker' => $kicker,
        'link' => $link,
        'btn' => $btn !== '' ? $btn : 'İncele',
    );
}
if (!$placeCards && trim($placeHeading) === '') {
    return;
}
?>
</main>
<section class="cms-places py-16 md:py-20 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="cms-places-copy cms-reveal">
            <?php if (trim($placeKicker) !== ''): ?>
                <p class="cms-places-kicker"><?= e($placeKicker) ?></p>
            <?php endif; ?>
            <?php if (trim($placeHeading) !== ''): ?>
                <h2><?= e($placeHeading) ?></h2>
            <?php endif; ?>
        </div>
        <?php if ($placeCards): ?>
            <div class="cms-places-grid">
                <?php foreach ($placeCards as $card): ?>
                    <?php
                    $href = (string) $card['link'];
                    $tag = $href !== '' ? 'a' : 'div';
                    $extra = $href !== '' ? ' href="' . e($href) . '"' : '';
                    ?>
                    <<?= $tag ?> class="cms-place cms-reveal"<?= $extra ?>>
                        <?php if ($card['image'] !== ''): ?>
                            <img src="<?= e(media_src($card['image'])) ?>" alt="">
                        <?php else: ?>
                            <div class="cms-place-fallback"></div>
                        <?php endif; ?>
                        <span class="cms-place-shade" aria-hidden="true"></span>
                        <span class="cms-place-body">
                            <?php if ($card['kicker'] !== ''): ?><span class="cms-place-kicker"><?= e($card['kicker']) ?></span><?php endif; ?>
                            <?php if ($card['title'] !== ''): ?><h3><?= e($card['title']) ?></h3><?php endif; ?>
                            <?php if ($card['text'] !== ''): ?><p><?= e($card['text']) ?></p><?php endif; ?>
                            <?php if ($href !== ''): ?>
                                <span class="cms-place-more"><?= e($card['btn']) ?> <span aria-hidden="true">→</span></span>
                            <?php endif; ?>
                        </span>
                    </<?= $tag ?>>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<main class="max-w-5xl mx-auto px-4 py-10">
