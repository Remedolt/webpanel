<?php
declare(strict_types=1);
if (!isset($pdo) || !($pdo instanceof PDO) || option_get($pdo, 'cta_show_home', '1') !== '1') {
    return;
}
?>
</main>
<section class="cms-cta py-14 px-4">
    <div class="max-w-3xl mx-auto text-center">
        <h2 class="text-2xl md:text-3xl font-bold"><?= e(option_get($pdo, 'cta_heading', 'Birlikte çalışalım')) ?></h2>
        <?php if (option_get($pdo, 'cta_text', '') !== ''): ?>
            <p class="mt-3 text-sky-50"><?= e(option_get($pdo, 'cta_text', '')) ?></p>
        <?php endif; ?>
        <a href="<?= e(option_get($pdo, 'cta_url', '') !== '' ? option_get($pdo, 'cta_url', '') : contact_permalink()) ?>" class="inline-flex mt-6 rounded-md bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 hover:bg-sky-50"><?= e(option_get($pdo, 'cta_button', 'İletişime geç')) ?></a>
    </div>
</section>
<main class="max-w-5xl mx-auto px-4 py-10">
