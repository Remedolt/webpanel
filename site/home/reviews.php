<?php
declare(strict_types=1);
if (!isset($pdo) || !($pdo instanceof PDO)) {
    return;
}
$homeReviews = [];
if (option_get($pdo, 'reviews_show_home', '1') === '1') {
    try {
        $homeReviews = $pdo->query("SELECT name, title, quote, rating FROM testimonials WHERE status = 'publish' ORDER BY sort_order ASC, id ASC")->fetchAll();
    } catch (PDOException $e) {
        $homeReviews = [];
    }
}
if (!$homeReviews) {
    return;
}
?>
<section class="mb-12 cms-reveal">
    <h2 class="text-2xl font-semibold text-slate-900 mb-6"><?= e(option_get($pdo, 'reviews_heading', 'Müşteri yorumları')) ?></h2>
    <div class="cms-quotes rounded-2xl border border-slate-200 bg-white px-6 py-8 md:px-10" id="cms-quotes">
        <?php foreach ($homeReviews as $i => $rev): ?>
            <blockquote class="cms-quote <?= $i === 0 ? 'is-on' : '' ?>">
                <p class="cms-stars text-sm"><?= str_repeat('★', max(1, min(5, (int) $rev['rating']))) ?></p>
                <p class="mt-4 text-xl md:text-2xl font-medium leading-relaxed text-slate-800">“<?= e((string) $rev['quote']) ?>”</p>
                <footer class="mt-5 text-sm text-slate-500">
                    <span class="font-semibold text-slate-900"><?= e((string) $rev['name']) ?></span>
                    <?php if (!empty($rev['title'])): ?>
                        · <?= e((string) $rev['title']) ?>
                    <?php endif; ?>
                </footer>
            </blockquote>
        <?php endforeach; ?>
    </div>
</section>
