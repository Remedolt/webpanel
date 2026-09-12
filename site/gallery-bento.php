<?php
if (!isset($galleryAlbums) || !is_array($galleryAlbums) || !$galleryAlbums) {
    return;
}
$sizeClass = static function ($size) {
    $size = (string) $size;
    if ($size === 'large') {
        return 'bento-large';
    }
    if ($size === 'wide') {
        return 'bento-wide';
    }
    if ($size === 'tall') {
        return 'bento-tall';
    }
    return 'bento-square';
};
$placeholder = static function ($slug, $id) {
    $slug = (string) $slug;
    $map = array(
        'projeler' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1400&q=80',
        'etkinlikler' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=1400&q=80',
        'atolye' => 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=1400&q=80',
        'ofis' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1400&q=80',
        'ekip' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=1400&q=80',
        'sahne' => 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?auto=format&fit=crop&w=1400&q=80',
        'calisma-alani' => 'https://images.unsplash.com/photo-1486312338219-ce68d2c6f44d?auto=format&fit=crop&w=1400&q=80',
        'kampus' => 'https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=1400&q=80',
        'manzara' => 'assets/login-bg.jpg',
    );
    if (isset($map[$slug])) {
        return $map[$slug];
    }
    return 'https://picsum.photos/seed/gal-' . rawurlencode($slug !== '' ? $slug : ('id' . $id)) . '/1400/900';
};
$camIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9 3.75 7.4 6H4.5A1.5 1.5 0 0 0 3 7.5v10.5A1.5 1.5 0 0 0 4.5 18h15a1.5 1.5 0 0 0 1.5-1.5V7.5A1.5 1.5 0 0 0 19.5 6h-2.9L15 3.75H9Zm3 13.5a4.5 4.5 0 1 1 0-9 4.5 4.5 0 0 1 0 9Z"/></svg>';
?>
<style>
.bento-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));grid-auto-rows:160px;gap:12px}
.bento-large{grid-column:span 2;grid-row:span 2}
.bento-wide{grid-column:span 2;grid-row:span 1}
.bento-tall{grid-column:span 1;grid-row:span 2}
.bento-square{grid-column:span 1;grid-row:span 1}
.bento-head{opacity:0;animation:bentoIn .55s ease forwards}
.bento-card{position:relative;overflow:hidden;isolation:isolate;opacity:0;transform:translateY(28px) scale(.96);animation:bentoIn .8s cubic-bezier(.2,.75,.2,1) forwards;background:#10243f;transition:transform .45s ease,box-shadow .45s ease}
.bento-card img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;animation:bentoKen 18s ease-in-out infinite alternate;transform-origin:center;transition:transform .6s ease,filter .45s ease}
.bento-shade{position:absolute;inset:0;z-index:1;pointer-events:none;background:linear-gradient(180deg,rgba(7,15,37,.45) 0%,transparent 42%,rgba(7,15,37,.78) 100%)}
.bento-card:after{content:"";position:absolute;inset:0;z-index:1;background:linear-gradient(115deg,transparent 20%,rgba(255,255,255,.22) 48%,transparent 72%);transform:translateX(-130%);pointer-events:none}
.bento-card:hover{transform:translateY(-8px);box-shadow:0 22px 48px rgba(0,0,0,.38);z-index:2}
.bento-card:hover img{animation-play-state:paused;transform:scale(1.1);filter:saturate(1.15) brightness(1.05)}
.bento-card:hover:after{animation:bentoShine .9s ease}
.bento-count{position:absolute;z-index:2;top:12px;left:12px;display:inline-flex;align-items:center;gap:6px;padding:5px 10px;border-radius:999px;background:rgba(7,15,37,.48);color:#fff;font-size:13px;font-weight:600;line-height:1;backdrop-filter:blur(8px)}
.bento-title{position:absolute;z-index:2;left:14px;right:14px;bottom:12px;margin:0;color:#fff;font-size:clamp(1.05rem,1.7vw,1.3rem);font-weight:700;line-height:1.25;letter-spacing:-.02em;text-shadow:0 2px 14px rgba(0,0,0,.55);overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
.bento-copy{position:absolute;z-index:2;left:14px;right:14px;bottom:12px}
.bento-copy .bento-title{position:static;left:auto;right:auto;bottom:auto;display:block;-webkit-line-clamp:2}
.bento-desc{display:block;margin-top:4px;font-size:12px;line-height:1.35;font-weight:500;color:rgba(255,255,255,.86);text-shadow:0 1px 8px rgba(0,0,0,.45);overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
.bento-square .bento-desc,.bento-tall .bento-desc{-webkit-line-clamp:1}
@keyframes bentoIn{from{opacity:0;transform:translateY(28px) scale(.96)}to{opacity:1;transform:none}}
@keyframes bentoKen{from{transform:scale(1) translate3d(0,0,0)}to{transform:scale(1.14) translate3d(-1%,-1%,0)}}
@keyframes bentoShine{to{transform:translateX(130%)}}
@media (max-width:900px){
  .bento-grid{grid-template-columns:repeat(2,minmax(0,1fr));grid-auto-rows:140px}
  .bento-large,.bento-wide{grid-column:span 2;grid-row:span 1}
  .bento-tall{grid-column:span 1;grid-row:span 2}
}
@media (max-width:520px){
  .bento-grid{grid-template-columns:1fr;grid-auto-rows:200px}
  .bento-large,.bento-wide,.bento-tall,.bento-square{grid-column:span 1;grid-row:span 1}
}
@media (prefers-reduced-motion:reduce){
  .bento-card,.bento-card img,.bento-head,.bento-card:hover:after{animation:none;opacity:1;transform:none}
}
</style>
<div class="bento-grid">
    <?php foreach ($galleryAlbums as $i => $album): ?>
        <?php
        $cover = (string) ($album['cover_image'] ?? '');
        if ($cover === '' && !empty($album['first_image'])) {
            $cover = (string) $album['first_image'];
        }
        if ($cover === '') {
            $cover = $placeholder($album['slug'] ?? '', (int) ($album['id'] ?? $i));
        }
        $count = (int) ($album['photo_count'] ?? 0);
        if ($count < 1) {
            $count = 1;
        }
        $hues = array(210, 165, 28, 280, 345, 190, 42, 222);
        $hue = $hues[(int) $i % 8];
        ?>
        <a href="<?= e(gallery_permalink($album['slug'])) ?>" class="<?= e($sizeClass($album['tile_size'] ?? 'square')) ?> bento-card rounded-2xl group block min-h-[140px]" style="animation-delay:<?= (int) $i * 70 ?>ms;background:linear-gradient(145deg,hsl(<?= (int) $hue ?> 58% 34%),#071525)">
            <img src="<?= e(media_src($cover)) ?>" alt="<?= e((string) $album['title']) ?>" loading="lazy" decoding="async" onerror="this.style.opacity='0'">
            <span class="bento-shade" aria-hidden="true"></span>
            <span class="bento-count">
                <?= $camIcon ?>
                <?= $count ?>
            </span>
            <?php $albumDesc = trim((string) ($album['description'] ?? '')); ?>
            <span class="bento-copy">
                <span class="bento-title"><?= e((string) $album['title']) ?></span>
                <?php if ($albumDesc !== ''): ?>
                    <span class="bento-desc"><?= e($albumDesc) ?></span>
                <?php endif; ?>
            </span>
        </a>
    <?php endforeach; ?>
</div>
