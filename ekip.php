<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';

$heading = option_get($pdo, 'staff_heading', 'Ekibimiz');
$people = [];
try {
    $people = $pdo->query("SELECT name, title, photo, interests, bio FROM staff WHERE status = 'publish' ORDER BY sort_order ASC, id ASC")->fetchAll();
} catch (PDOException $e) {
    $people = [];
}
$docTitle = $heading . ' — ' . $siteTitle;
require __DIR__ . '/site/header.php';
?>
<section class="cms-team mb-12" id="cms-team">
    <p class="text-xs uppercase tracking-wide text-slate-400">Personel</p>
    <h1 class="mt-2 text-4xl font-bold text-slate-900"><?= e($heading) ?></h1>
    <div class="mt-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($people as $person): ?>
            <article class="cms-team-card bg-white border border-slate-200 rounded-xl p-6 text-center">
                <div class="cms-team-photo">
                    <img src="<?= e(staff_photo_src((string) ($person['photo'] ?? ''))) ?>" alt="">
                </div>
                <h2 class="mt-4 font-semibold text-slate-900"><?= e((string) $person['name']) ?></h2>
                <?php if (!empty($person['title'])): ?>
                    <p class="text-sm text-sky-700"><?= e((string) $person['title']) ?></p>
                <?php endif; ?>
                <?php if (!empty($person['interests'])): ?>
                    <p class="mt-2 text-xs text-slate-500"><?= e((string) $person['interests']) ?></p>
                <?php endif; ?>
                <?php if (!empty($person['bio'])): ?>
                    <div class="mt-2 text-sm text-slate-600"><?= public_html((string) $person['bio']) ?></div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/site/footer.php'; ?>
