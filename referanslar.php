<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';

$heading = option_get($pdo, 'partners_heading', 'Referanslar');
$partners = [];
try {
    $partners = $pdo->query("SELECT name, logo, url FROM partners WHERE status = 'publish' ORDER BY sort_order ASC, id ASC")->fetchAll();
} catch (PDOException $e) {
    $partners = [];
}
$docTitle = $heading . ' — ' . $siteTitle;
require __DIR__ . '/site/header.php';
?>
<article class="max-w-5xl">
    <p class="text-xs uppercase tracking-wide text-slate-400">Referanslar</p>
    <h1 class="mt-2 text-4xl font-bold text-slate-900"><?= e($heading) ?></h1>
    <div class="mt-10 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        <?php foreach ($partners as $i => $partner): ?>
            <?php
            $href = trim((string) ($partner['url'] ?? ''));
            $title = trim((string) ($partner['name'] ?? ''));
            $tag = $href !== '' ? 'a' : 'div';
            ?>
            <<?= $tag ?> <?= $href !== '' ? 'href="' . e($href) . '" target="_blank" rel="noopener noreferrer"' : '' ?> class="cms-reveal cms-card flex min-h-[160px] flex-col items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white p-5" style="transition-delay:<?= (int) $i * 80 ?>ms">
                <?php if (!empty($partner['logo'])): ?>
                    <img src="<?= e(media_src($partner['logo'])) ?>" alt="<?= e($title) ?>" class="h-14 w-full max-w-[150px] object-contain">
                <?php endif; ?>
                <?php if ($title !== ''): ?>
                    <h2 class="text-sm font-semibold text-slate-800 text-center leading-snug"><?= e($title) ?></h2>
                <?php endif; ?>
            </<?= $tag ?>>
        <?php endforeach; ?>
    </div>
</article>
<?php require __DIR__ . '/site/footer.php'; ?>
