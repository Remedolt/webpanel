<?php
declare(strict_types=1);
http_response_code(404);
require __DIR__ . '/site/bootstrap.php';
$docTitle = 'Sayfa bulunamadı — ' . $siteTitle;
require __DIR__ . '/site/header.php';
?>
<article class="max-w-xl text-center mx-auto py-10 cms-reveal is-on">
    <p class="text-sm font-semibold text-sky-700">404</p>
    <h1 class="mt-2 text-3xl font-bold text-slate-900">Sayfa bulunamadı</h1>
    <p class="mt-3 text-slate-600">Aradığınız adres yok veya taşınmış olabilir.</p>
    <form method="get" action="<?= e(search_permalink()) ?>" class="mt-6 flex gap-2">
        <input name="q" placeholder="Sitede ara…" class="flex-1 rounded-md border border-slate-200 px-3 py-2 text-sm">
        <button class="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white" type="submit">Ara</button>
    </form>
    <p class="mt-6 flex flex-wrap justify-center gap-x-4 gap-y-2">
        <a class="text-sky-700 font-medium hover:underline" href="<?= e(public_url()) ?>">Ana sayfa</a>
        <a class="text-sky-700 font-medium hover:underline" href="<?= e(services_permalink()) ?>">Hizmetler</a>
        <a class="text-sky-700 font-medium hover:underline" href="<?= e(posts_list_permalink()) ?>">Yazılar</a>
        <a class="text-sky-700 font-medium hover:underline" href="<?= e(contact_permalink()) ?>">İletişim</a>
    </p>
</article>
<?php require __DIR__ . '/site/footer.php'; ?>
