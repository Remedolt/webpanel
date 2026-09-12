<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';

$heading = option_get($pdo, 'faq_heading', 'Sıkça sorulanlar');
$faqs = [];
try {
    $faqs = $pdo->query("SELECT question, answer FROM faqs WHERE status = 'publish' ORDER BY sort_order ASC, id ASC")->fetchAll();
} catch (PDOException $e) {
    $faqs = [];
}
$docTitle = $heading . ' — ' . $siteTitle;
require __DIR__ . '/site/header.php';
?>
<article class="max-w-3xl">
    <p class="text-xs uppercase tracking-wide text-slate-400">SSS</p>
    <h1 class="mt-2 text-4xl font-bold text-slate-900"><?= e($heading) ?></h1>
    <div class="mt-8 space-y-3">
        <?php if (!$faqs): ?>
            <p class="text-slate-500">Henüz soru yok.</p>
        <?php else: ?>
            <?php foreach ($faqs as $faq): ?>
                <details class="cms-faq group rounded-xl border border-slate-200 bg-white px-4 py-3">
                    <summary class="cursor-pointer list-none font-semibold text-slate-900"><?= e((string) $faq['question']) ?></summary>
                    <div class="mt-3 text-sm leading-6 text-slate-600"><?= public_html((string) ($faq['answer'] ?? '')) ?></div>
                </details>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</article>
<?php if ($faqs): ?>
<?php
$faqLd = array(
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array(),
);
foreach ($faqs as $faq) {
    $faqLd['mainEntity'][] = array(
        '@type' => 'Question',
        'name' => (string) $faq['question'],
        'acceptedAnswer' => array(
            '@type' => 'Answer',
            'text' => trim(strip_tags((string) ($faq['answer'] ?? ''))),
        ),
    );
}
?>
<script type="application/ld+json"><?= json_encode($faqLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?>
<?php require __DIR__ . '/site/footer.php'; ?>
