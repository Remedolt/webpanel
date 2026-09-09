<?php
declare(strict_types=1);
if (!isset($pdo) || !($pdo instanceof PDO)) {
    return;
}

$homeSlides = isset($homeSlides) && is_array($homeSlides) ? $homeSlides : active_home_slides($pdo);
if ($homeSlides === []) {
    return;
}

$sliderAutoplay = option_get($pdo, 'slider_autoplay', '1') === '1' ? '1' : '0';
$sliderInterval = (int) option_get($pdo, 'slider_interval', '5000');
if ($sliderInterval < 2000) {
    $sliderInterval = 5000;
}
$tones = ['from-slate-950 via-slate-900 to-cyan-900', 'from-ink via-slate-900 to-sky-900', 'from-slate-950 via-cyan-950 to-slate-800'];
?>
<section class="hero-slider relative mb-8 overflow-hidden rounded-2xl bg-ink text-white select-none"
         data-hero-slider
         data-autoplay="<?= e($sliderAutoplay) ?>"
         data-interval="<?= (int) $sliderInterval ?>"
         aria-roledescription="carousel"
         aria-label="Öne çıkan slaytlar">
    <div class="overflow-hidden">
        <div class="hero-slider-track flex" data-hero-track>
            <?php foreach ($homeSlides as $i => $slide): ?>
                <?php
                $title = trim((string) ($slide['title'] ?? ''));
                $subtitle = trim((string) ($slide['subtitle'] ?? ''));
                $button = trim((string) ($slide['button_text'] ?? ''));
                $href = slider_public_link((string) ($slide['link_url'] ?? ''));
                $image = trim((string) ($slide['image'] ?? ''));
                $tone = $tones[$i % count($tones)];
                ?>
                <article class="hero-slider-slide relative min-w-full h-64 sm:h-80 lg:h-[26rem]" data-hero-slide>
                    <?php if ($image !== ''): ?>
                        <img src="<?= e(media_src($image)) ?>" alt="<?= e($title) ?>" class="absolute inset-0 h-full w-full object-cover">
                    <?php else: ?>
                        <div class="absolute inset-0 bg-gradient-to-br <?= e($tone) ?>"></div>
                    <?php endif; ?>
                    <div class="absolute inset-0 bg-gradient-to-r from-black/75 via-black/40 to-black/10"></div>
                    <div class="relative z-10 flex h-full flex-col justify-end p-6 sm:p-10 max-w-2xl pointer-events-none">
                        <?php if ($title !== ''): ?>
                            <h2 class="text-2xl sm:text-4xl font-bold tracking-tight"><?= e($title) ?></h2>
                        <?php endif; ?>
                        <?php if ($subtitle !== ''): ?>
                            <p class="mt-2 sm:mt-3 text-sm sm:text-lg text-slate-200 max-w-xl"><?= e($subtitle) ?></p>
                        <?php endif; ?>
                        <?php if ($button !== ''): ?>
                            <a href="<?= e($href) ?>" class="pointer-events-auto mt-4 inline-flex w-fit items-center rounded-md bg-cyan-400 px-4 py-2 text-sm font-semibold text-ink hover:bg-cyan-300">
                                <?= e($button) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
    <?php if (count($homeSlides) > 1): ?>
        <button type="button" data-hero-prev class="absolute left-3 top-1/2 z-20 -translate-y-1/2 rounded-full bg-black/40 p-2 text-white hover:bg-black/60" aria-label="Önceki slayt">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
        </button>
        <button type="button" data-hero-next class="absolute right-3 top-1/2 z-20 -translate-y-1/2 rounded-full bg-black/40 p-2 text-white hover:bg-black/60" aria-label="Sonraki slayt">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </button>
        <div class="absolute bottom-3 left-0 right-0 z-20 flex justify-center gap-2" data-hero-dots></div>
    <?php endif; ?>
</section>
<script src="<?= e(public_url('site/hero-slider.js')) ?>" defer></script>
