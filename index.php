<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';

$homeSlides = [];
try {
    $homeSlides = $pdo->query("SELECT title, subtitle, image, button_text, button_url, youtube_url FROM sliders WHERE status = 'publish' ORDER BY sort_order ASC, id ASC")->fetchAll();
} catch (PDOException $e) {
    $homeSlides = $pdo->query("SELECT title, subtitle, image, button_text, button_url FROM sliders WHERE status = 'publish' ORDER BY sort_order ASC, id ASC")->fetchAll();
}

$docTitle = ($seoTitle !== '' ? $seoTitle : $siteTitle);
if ($seoTitle === '' && $siteTagline !== '') {
    $docTitle .= ' — ' . $siteTagline;
}
require __DIR__ . '/site/header.php';
?>
<?php if (empty($homeSlides)): ?>
<section class="mb-10 cms-reveal">
    <p class="text-sm uppercase tracking-wide text-sky-700 font-semibold">Kodcu</p>
    <h1 class="mt-2 text-4xl font-bold text-slate-900"><?= e($siteTitle) ?></h1>
    <?php if ($siteTagline !== ''): ?>
        <p class="mt-3 text-lg text-slate-600 max-w-2xl"><?= e($siteTagline) ?></p>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php
$homeModDir = __DIR__ . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'home';
$homeModReal = realpath($homeModDir);
$homeModNorm = $homeModReal ? strtolower(str_replace('\\', '/', $homeModReal)) : '';
if ($homeModNorm !== '') {
    foreach (cms_home_modules($pdo) as $homeMod) {
        if (!preg_match('/^[a-z]+$/', $homeMod)) {
            continue;
        }
        $homeFile = $homeModDir . DIRECTORY_SEPARATOR . $homeMod . '.php';
        $homeReal = realpath($homeFile);
        if (!$homeReal || !is_file($homeReal)) {
            continue;
        }
        $homeNorm = strtolower(str_replace('\\', '/', $homeReal));
        if (strpos($homeNorm, $homeModNorm . '/') !== 0 && $homeNorm !== $homeModNorm) {
            continue;
        }
        require $homeReal;
    }
}
?>
<script>
(function () {
    var box = document.getElementById('cms-stats');
    var els = document.querySelectorAll('[data-counter]');
    if (!els.length) return;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function run(el, delay) {
        var target = parseInt(el.getAttribute('data-counter'), 10) || 0;
        if (reduce) {
            el.textContent = String(target);
            return;
        }
        setTimeout(function () {
            var duration = 2200;
            var t0 = null;
            function step(ts) {
                if (!t0) t0 = ts;
                var p = Math.min(1, (ts - t0) / duration);
                var eased = 1 - Math.pow(1 - p, 3);
                el.textContent = String(Math.round(target * eased));
                if (p < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        }, delay);
    }
    function start() {
        if (box) box.classList.add('is-on');
        els.forEach(function (el, i) { run(el, i * 160); });
    }
    if (reduce) {
        start();
        return;
    }
    if ('IntersectionObserver' in window && box) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                start();
                io.disconnect();
            });
        }, { threshold: 0.35 });
        io.observe(box);
    } else {
        start();
    }
})();
(function () {
    var quotes = document.querySelectorAll('#cms-quotes .cms-quote');
    if (quotes.length < 2) return;
    var i = 0;
    setInterval(function () {
        quotes[i].classList.remove('is-on');
        i = (i + 1) % quotes.length;
        quotes[i].classList.add('is-on');
    }, 5200);
})();
(function () {
    var root = document.getElementById('cms-spot');
    if (!root) return;
    var slides = root.querySelectorAll('.cms-spot-slide');
    var dots = root.querySelectorAll('[data-spot-dot]');
    if (slides.length < 2) return;
    var i = 0;
    var timer;
    function show(n) {
        slides[i].classList.remove('is-on');
        if (dots[i]) dots[i].classList.remove('is-on');
        i = n;
        slides[i].classList.add('is-on');
        if (dots[i]) dots[i].classList.add('is-on');
    }
    function tick() { show((i + 1) % slides.length); }
    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            show(parseInt(dot.getAttribute('data-spot-dot'), 10) || 0);
            clearInterval(timer);
            timer = setInterval(tick, 5200);
        });
    });
    timer = setInterval(tick, 5200);
})();
(function () {
    var play = document.querySelector('[data-film-play]');
    var modal = document.getElementById('cms-film-modal');
    if (!play || !modal) return;
    var card = document.querySelector('#cms-film .cms-film-card');
    var stage = modal.querySelector('[data-film-stage]');
    function closeFilm() {
        modal.hidden = true;
        document.body.style.overflow = '';
        if (stage) stage.innerHTML = '';
    }
    function openFilm() {
        var type = play.getAttribute('data-film-type') || '';
        var src = play.getAttribute('data-src') || '';
        if (!src || !stage) return;
        stage.innerHTML = '';
        if (type === 'file') {
            var vid = document.createElement('video');
            vid.src = src;
            vid.controls = true;
            vid.autoplay = true;
            vid.setAttribute('playsinline', '');
            stage.appendChild(vid);
        } else {
            var frame = document.createElement('iframe');
            frame.src = src;
            frame.title = 'Video';
            frame.allow = 'autoplay; encrypted-media; picture-in-picture';
            frame.setAttribute('allowfullscreen', '');
            stage.appendChild(frame);
        }
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    }
    play.addEventListener('click', function (e) {
        e.stopPropagation();
        openFilm();
    });
    if (card) card.addEventListener('click', openFilm);
    modal.addEventListener('click', function (e) {
        if (e.target === modal || (e.target && e.target.getAttribute && e.target.getAttribute('data-film-close') !== null)) {
            closeFilm();
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) closeFilm();
    });
})();
</script>
<?php require __DIR__ . '/site/footer.php'; ?>
