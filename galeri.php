<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$req = (string) ($_SERVER['REQUEST_URI'] ?? '');
if (strpos($req, 'galeri.php') !== false) {
    header('Location: ' . ($slug !== '' ? gallery_permalink($slug) : gallery_permalink()), true, 301);
    exit;
}

$albumQuery = "SELECT a.*,
    (SELECT COUNT(*) FROM gallery_images g WHERE g.album_id = a.id) AS photo_count,
    (SELECT image FROM gallery_images g WHERE g.album_id = a.id ORDER BY sort_order ASC, id ASC LIMIT 1) AS first_image
    FROM gallery_albums a WHERE a.status = 'publish'";

if ($slug === '') {
    $galleryAlbums = $pdo->query($albumQuery . ' ORDER BY a.sort_order ASC, a.id ASC')->fetchAll();
    $docTitle = (option_get($pdo, 'gallery_heading', 'Galeri') !== '' ? option_get($pdo, 'gallery_heading', 'Galeri') : 'Galeri') . ' — ' . $siteTitle;
    require __DIR__ . '/site/header.php';
    $heading = option_get($pdo, 'gallery_heading', 'Galeri');
    ?>
    </main>
    <section class="cms-gallery-band py-12 px-4" style="background:#0b1b33">
        <div class="max-w-6xl mx-auto">
            <div class="bento-head flex items-end justify-between gap-4 mb-6">
                <h1 class="text-3xl font-bold text-white"><?= e($heading) ?></h1>
            </div>
            <?php require __DIR__ . '/site/gallery-bento.php'; ?>
        </div>
    </section>
    <main class="max-w-5xl mx-auto px-4 py-10">
    <?php
    require __DIR__ . '/site/footer.php';
    exit;
}

$stmt = $pdo->prepare($albumQuery . ' AND a.slug = ? LIMIT 1');
$stmt->execute([$slug]);
$album = $stmt->fetch();
if (!$album) {
    http_response_code(404);
    $docTitle = 'Albüm bulunamadı — ' . $siteTitle;
    require __DIR__ . '/site/header.php';
    echo '<h1 class="text-2xl font-bold">Albüm bulunamadı</h1>';
    require __DIR__ . '/site/footer.php';
    exit;
}

$photos = $pdo->prepare('SELECT * FROM gallery_images WHERE album_id = ? ORDER BY sort_order ASC, id ASC');
$photos->execute([(int) $album['id']]);
$photos = $photos->fetchAll();
$docTitle = (string) $album['title'] . ' — ' . $siteTitle;
require __DIR__ . '/site/header.php';
?>
<p class="text-sm"><a class="text-sky-700 hover:underline" href="<?= e(gallery_permalink()) ?>">← Galeri</a></p>
<h1 class="mt-2 text-4xl font-bold text-slate-900"><?= e((string) $album['title']) ?></h1>
<?php if (trim((string) ($album['description'] ?? '')) !== ''): ?>
    <p class="mt-3 text-lg text-slate-600 max-w-2xl"><?= e((string) $album['description']) ?></p>
<?php endif; ?>
<p class="mt-2 text-slate-500"><?= count($photos) ?> fotoğraf</p>
<?php if (!$photos): ?>
    <p class="mt-8 text-slate-500">Bu albüme henüz fotoğraf eklenmedi.</p>
<?php else: ?>
    <style>
    .gallery-open{opacity:0;transform:translateY(18px);animation:galIn .55s ease forwards;transition:transform .35s ease,box-shadow .35s ease}
    .gallery-open img{transition:transform .55s ease,filter .4s ease}
    .gallery-open:hover{transform:translateY(-6px);box-shadow:0 16px 32px rgba(15,23,42,.18)}
    .gallery-open:hover img{transform:scale(1.08)}
    #gallery-lightbox{opacity:0;transition:opacity .28s ease}
    #gallery-lightbox.is-on{opacity:1}
    #lightbox-img{transform:scale(.94);opacity:0;transition:transform .35s ease,opacity .35s ease}
    #gallery-lightbox.is-on #lightbox-img{transform:scale(1);opacity:1}
    @keyframes galIn{to{opacity:1;transform:none}}
    @media (prefers-reduced-motion:reduce){
      .gallery-open,#lightbox-img{animation:none;opacity:1;transform:none}
    }
    </style>
    <div class="mt-8 grid grid-cols-2 md:grid-cols-3 gap-3">
        <?php foreach ($photos as $i => $img): ?>
            <button type="button" class="gallery-open overflow-hidden rounded-xl aspect-[4/3] bg-slate-100" style="animation-delay:<?= (int) $i * 70 ?>ms" data-src="<?= e(media_src($img['image'])) ?>" data-index="<?= (int) $i ?>">
                <img src="<?= e(media_src($img['image'])) ?>" alt="<?= e((string) ($img['caption'] ?? $album['title'])) ?>" class="h-full w-full object-cover pointer-events-none">
            </button>
        <?php endforeach; ?>
    </div>
    <div id="gallery-lightbox" class="hidden fixed inset-0 z-[60] bg-slate-900/90 flex items-center justify-center p-4">
        <button type="button" id="lightbox-close" class="absolute top-4 right-4 text-white text-sm">Kapat</button>
        <button type="button" id="lightbox-prev" class="absolute left-3 md:left-6 text-white text-3xl px-2" aria-label="Önceki">‹</button>
        <img id="lightbox-img" src="" alt="" class="max-h-[90vh] max-w-full rounded-lg">
        <button type="button" id="lightbox-next" class="absolute right-3 md:right-6 text-white text-3xl px-2" aria-label="Sonraki">›</button>
    </div>
    <script>
    (function () {
        var box = document.getElementById('gallery-lightbox');
        var img = document.getElementById('lightbox-img');
        var closeBtn = document.getElementById('lightbox-close');
        var prevBtn = document.getElementById('lightbox-prev');
        var nextBtn = document.getElementById('lightbox-next');
        var items = Array.prototype.slice.call(document.querySelectorAll('.gallery-open'));
        var i = 0;
        function show(n) {
            if (!items.length) return;
            i = (n + items.length) % items.length;
            img.style.opacity = '0';
            img.style.transform = 'scale(.94)';
            img.src = items[i].getAttribute('data-src');
            box.classList.remove('hidden');
            requestAnimationFrame(function () { box.classList.add('is-on'); });
            img.onload = function () {
                img.style.opacity = '1';
                img.style.transform = 'scale(1)';
            };
        }
        items.forEach(function (btn, idx) {
            btn.addEventListener('click', function () { show(idx); });
        });
        function hide() {
            box.classList.remove('is-on');
            setTimeout(function () { box.classList.add('hidden'); }, 220);
        }
        if (closeBtn) closeBtn.addEventListener('click', hide);
        if (prevBtn) prevBtn.addEventListener('click', function (e) { e.stopPropagation(); show(i - 1); });
        if (nextBtn) nextBtn.addEventListener('click', function (e) { e.stopPropagation(); show(i + 1); });
        box.addEventListener('click', function (e) { if (e.target === box) hide(); });
        document.addEventListener('keydown', function (e) {
            if (box.classList.contains('hidden')) return;
            if (e.key === 'Escape') hide();
            if (e.key === 'ArrowLeft') show(i - 1);
            if (e.key === 'ArrowRight') show(i + 1);
        });
    })();
    </script>
<?php endif; ?>
<?php require __DIR__ . '/site/footer.php'; ?>
