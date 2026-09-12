<?php
declare(strict_types=1);
if (!isset($pdo) || !($pdo instanceof PDO)) {
    return;
}
$homeStaff = [];
if (option_get($pdo, 'staff_show_home', '1') === '1') {
    try {
        $homeStaff = $pdo->query("SELECT name, title, photo, interests, bio FROM staff WHERE status = 'publish' ORDER BY sort_order ASC, id ASC")->fetchAll();
    } catch (PDOException $e) {
        $homeStaff = [];
    }
}
if (!$homeStaff) {
    return;
}
?>
<section class="cms-team mb-12" id="cms-team">
    <div class="flex items-end justify-between gap-3 mb-6">
        <h2 class="text-2xl font-semibold text-slate-900"><?= e(option_get($pdo, 'staff_heading', 'Ekibimiz')) ?></h2>
        <a class="text-sm font-medium text-sky-700 hover:underline" href="<?= e(staff_list_permalink()) ?>">Tümü</a>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($homeStaff as $i => $person): ?>
            <article class="cms-team-card bg-white border border-slate-200 rounded-xl p-6 text-center" style="transition-delay:<?= (int) $i * 140 ?>ms">
                <div class="cms-team-photo">
                    <img src="<?= e(staff_photo_src((string) ($person['photo'] ?? ''))) ?>" alt="">
                </div>
                <h3 class="mt-4 font-semibold text-slate-900"><?= e((string) $person['name']) ?></h3>
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
