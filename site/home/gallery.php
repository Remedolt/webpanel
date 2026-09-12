<?php
declare(strict_types=1);
if (!isset($pdo) || !($pdo instanceof PDO)) {
    return;
}
$homeGallery = [];
if (isset($galleryShowHome) && $galleryShowHome === '1') {
    try {
        $homeGallery = $pdo->query(
            "SELECT a.*,
                (SELECT COUNT(*) FROM gallery_images g WHERE g.album_id = a.id) AS photo_count,
                (SELECT image FROM gallery_images g WHERE g.album_id = a.id ORDER BY sort_order ASC, id ASC LIMIT 1) AS first_image
             FROM gallery_albums a WHERE a.status = 'publish' ORDER BY a.sort_order ASC, a.id ASC"
        )->fetchAll();
    } catch (PDOException $e) {
        $homeGallery = [];
    }
}
if (!$homeGallery) {
    return;
}
$galleryAlbums = $homeGallery;
$ctaHref = ($galleryCtaUrl !== '') ? $galleryCtaUrl : gallery_permalink();
?>
</main>
<section class="cms-gallery-band py-12 px-4" style="background:#0b1b33">
    <div class="max-w-6xl mx-auto">
        <div class="bento-head flex items-end justify-between gap-4 mb-6">
            <h2 class="text-2xl md:text-3xl font-bold text-white"><?= e($galleryHeading !== '' ? $galleryHeading : 'Galeri') ?></h2>
            <?php if ($galleryCtaText !== ''): ?>
                <a href="<?= e($ctaHref) ?>" class="inline-flex items-center gap-2 text-sm font-medium text-emerald-400 hover:text-emerald-300 whitespace-nowrap">
                    <?= e($galleryCtaText) ?>
                    <span aria-hidden="true">→</span>
                </a>
            <?php endif; ?>
        </div>
        <?php require dirname(__DIR__) . '/gallery-bento.php'; ?>
    </div>
</section>
<main class="max-w-5xl mx-auto px-4 py-10">
