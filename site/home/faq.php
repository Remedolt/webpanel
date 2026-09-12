<?php
declare(strict_types=1);
if (!isset($pdo) || !($pdo instanceof PDO)) {
    return;
}
$homeFaqs = [];
if (option_get($pdo, 'faq_show_home', '1') === '1') {
    try {
        $homeFaqs = $pdo->query("SELECT question, answer FROM faqs WHERE status = 'publish' ORDER BY sort_order ASC, id ASC LIMIT 6")->fetchAll();
    } catch (PDOException $e) {
        $homeFaqs = [];
    }
}
if (!$homeFaqs) {
    return;
}
?>
<section class="mb-12 cms-reveal">
    <div class="flex items-end justify-between gap-3 mb-6">
        <h2 class="text-2xl font-semibold text-slate-900"><?= e(option_get($pdo, 'faq_heading', 'Sıkça sorulanlar')) ?></h2>
        <a class="text-sm font-medium text-sky-700 hover:underline" href="<?= e(faq_permalink()) ?>">Tümü</a>
    </div>
    <div class="space-y-3">
        <?php foreach ($homeFaqs as $faq): ?>
            <details class="cms-faq group rounded-xl border border-slate-200 bg-white px-4 py-3">
                <summary class="cursor-pointer list-none font-semibold text-slate-900"><?= e((string) $faq['question']) ?></summary>
                <div class="mt-3 text-sm leading-6 text-slate-600"><?= public_html((string) ($faq['answer'] ?? '')) ?></div>
            </details>
        <?php endforeach; ?>
    </div>
</section>
