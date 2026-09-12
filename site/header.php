<?php
declare(strict_types=1);
if (!isset($pdo, $siteTitle)) {
    exit;
}
$docTitle = isset($docTitle) ? (string) $docTitle : ($seoTitle !== '' ? $seoTitle : $siteTitle);
$menuParents = isset($headerMenuTree[0]) ? $headerMenuTree[0] : array();
$menuTreeUse = isset($headerMenuTree) ? $headerMenuTree : array();
$headerType = isset($headerType) ? $headerType : 'top';
$headerSize = isset($headerSize) ? $headerSize : 'md';
$headerBg = isset($headerBg) ? $headerBg : '#0f172a';
$headerText = isset($headerText) ? $headerText : '#e2e8f0';
$headerAccent = isset($headerAccent) ? $headerAccent : '#38bdf8';
$headerSticky = isset($headerSticky) ? $headerSticky : '0';
$headerWidth = isset($headerWidth) ? $headerWidth : 'boxed';
$mobileMenuType = isset($mobileMenuType) ? $mobileMenuType : 'drawer';
$mobileBtnStyle = isset($mobileBtnStyle) ? $mobileBtnStyle : 'hamburger';
$mobileShowPages = isset($mobileShowPages) ? $mobileShowPages : '1';
$mobileShowHome = isset($mobileShowHome) ? $mobileShowHome : '1';
$hFont = $headerSize === 'lg' ? '16px' : ($headerSize === 'sm' ? '13px' : '14px');
$hBar = $headerSize === 'lg' ? '80px' : ($headerSize === 'sm' ? '52px' : '64px');
$hSide = $headerSize === 'lg' ? '272px' : ($headerSize === 'sm' ? '188px' : '224px');
$wrapCls = $headerWidth === 'full' ? 'px-4' : 'max-w-5xl mx-auto px-4';
$stickyCls = $headerSticky === '1' ? 'sticky top-0 z-30' : 'relative z-20';
$sideLayout = ($headerType === 'left' || $headerType === 'right');
$overlayOn = ($headerType === 'overlay' && !empty($homeSlides));
if ($headerType === 'overlay' && empty($homeSlides)) {
    $headerType = 'top';
}

$siteLogo = static function ($compact = false) use ($pdo) {
    echo site_brand_html($pdo, (bool) $compact);
};

$renderItems = static function ($parents, $tree, $headerAccent, $vertical) {
    if (!$parents) {
        return;
    }
    foreach ($parents as $item) {
        $style = (string) ($item['item_style'] ?? 'link');
        $children = isset($tree[(int) $item['id']]) ? $tree[(int) $item['id']] : array();
        $wide = $vertical ? 'w-full' : '';
        if ($children || $style === 'dropdown') {
            echo '<div class="relative group ' . $wide . '">';
            echo '<a href="' . e((string) $item['url']) . '" class="inline-flex items-center gap-1 px-3 py-2 rounded-md ' . $wide . '">';
            echo e((string) $item['title']) . ' <span class="text-[10px]">▾</span></a>';
            echo '<div class="' . ($vertical ? 'ml-3 mt-1' : 'hidden group-hover:block absolute right-0 top-full min-w-[180px] bg-white text-slate-800 rounded-md shadow-lg border border-slate-200 py-1 z-40') . '">';
            foreach ($children as $child) {
                echo '<a class="block px-3 py-2 text-sm opacity-90 hover:opacity-100" href="' . e((string) $child['url']) . '">' . e((string) $child['title']) . '</a>';
            }
            echo '</div></div>';
        } elseif ($style === 'button') {
            echo '<a href="' . e((string) $item['url']) . '" class="inline-flex items-center rounded-md px-3 py-1.5 font-semibold ' . $wide . '" style="background:' . e($headerAccent) . ';color:' . e(cms_on_color($headerAccent)) . '">' . e((string) $item['title']) . '</a>';
        } elseif ($style === 'highlight') {
            echo '<a class="px-3 py-2 rounded-md inline-block font-semibold ' . $wide . '" style="color:' . e($headerAccent) . '" href="' . e((string) $item['url']) . '">' . e((string) $item['title']) . '</a>';
        } else {
            echo '<a class="px-3 py-2 rounded-md inline-block ' . $wide . '" href="' . e((string) $item['url']) . '">' . e((string) $item['title']) . '</a>';
        }
    }
};

$fallbackNav = static function ($navPages, $vertical) {
    echo '<a class="px-3 py-2' . ($vertical ? ' block' : '') . '" href="' . e(services_permalink()) . '">Hizmetler</a>';
    echo '<a class="px-3 py-2' . ($vertical ? ' block' : '') . '" href="' . e(posts_list_permalink()) . '">Yazılar</a>';
    echo '<a class="px-3 py-2' . ($vertical ? ' block' : '') . '" href="' . e(contact_permalink()) . '">İletişim</a>';
    echo '<a class="px-3 py-2' . ($vertical ? ' block' : '') . '" href="' . e(staff_list_permalink()) . '">Ekip</a>';
    echo '<a class="px-3 py-2' . ($vertical ? ' block' : '') . '" href="' . e(faq_permalink()) . '">SSS</a>';
    foreach ($navPages as $nav) {
        echo '<a class="px-3 py-2' . ($vertical ? ' block' : '') . '" href="' . e(page_permalink($nav['slug'])) . '">' . e((string) $nav['title']) . '</a>';
    }
};

$hamburger = static function ($mobileBtnStyle, $headerText) {
    echo '<button type="button" id="mobile-menu-open" class="inline-flex items-center gap-2 rounded-md px-2 py-1.5" aria-label="Menüyü aç">';
    if ($mobileBtnStyle !== 'text') {
        echo '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>';
    }
    if ($mobileBtnStyle !== 'hamburger') {
        echo '<span class="text-sm font-medium">Menü</span>';
    }
    echo '</button>';
};

$searchQ = trim((string) ($_GET['q'] ?? ''));
$cmsBrand = isset($cmsBrand) && is_array($cmsBrand) ? $cmsBrand : cms_brand($pdo);

$headerSearch = static function () use ($searchQ) {
    $open = $searchQ !== '';
    echo '<form method="get" action="' . e(search_permalink()) . '" class="cms-hsearch' . ($open ? ' is-on' : '') . '" data-hsearch data-suggest="' . e(search_permalink() . '?suggest=1') . '">';
    echo '<input type="search" name="q" value="' . e($searchQ) . '" placeholder="Ara…" aria-label="Sitede ara" autocomplete="off" data-hsearch-input>';
    echo '<button type="submit" data-hsearch-toggle aria-label="Ara">';
    echo '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" /></svg>';
    echo '</button><div class="cms-suggest" hidden data-suggest-box></div></form>';
};
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($docTitle) ?></title>
    <?php if ($seoDescription !== ''): ?>
        <meta name="description" content="<?= e($seoDescription) ?>">
        <meta property="og:description" content="<?= e($seoDescription) ?>">
    <?php endif; ?>
    <?php if ($seoKeywords !== ''): ?>
        <meta name="keywords" content="<?= e($seoKeywords) ?>">
    <?php endif; ?>
    <meta name="robots" content="<?= e($seoRobots !== '' ? $seoRobots : 'index,follow') ?>">
    <meta property="og:title" content="<?= e($docTitle) ?>">
    <meta property="og:type" content="<?= e(isset($ogType) && $ogType !== '' ? $ogType : 'website') ?>">
    <?php
    if (empty($ogImage)) {
        $ogDefault = option_get($pdo, 'og_image', '');
        if ($ogDefault !== '') {
            $ogImage = media_src($ogDefault);
        }
    }
    ?>
    <?php if (!empty($ogImage)): ?>
        <meta property="og:image" content="<?= e((string) $ogImage) ?>">
    <?php endif; ?>
    <link rel="canonical" href="<?= e(rtrim(PUBLIC_URL, '/') . strtok((string) ($_SERVER['REQUEST_URI'] ?? '/'), '?')) ?>">
    <link rel="alternate" type="application/rss+xml" title="<?= e($siteTitle) ?> RSS" href="<?= e(rss_permalink()) ?>">
    <?= favicon_tags() ?>
    <?php
    $analyticsHead = option_get($pdo, 'analytics_head', '');
    if ($analyticsHead !== '') {
        echo $analyticsHead . "\n";
    }
    $orgSame = array();
    foreach (cms_social_links($pdo) as $soc) {
        $orgSame[] = (string) $soc['url'];
    }
    $org = array(
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $siteTitle,
        'url' => public_url(),
    );
    if (!empty($ogImage)) {
        $org['logo'] = (string) $ogImage;
    }
    if ($orgSame) {
        $org['sameAs'] = $orgSame;
    }
    ?>
    <script type="application/ld+json"><?= json_encode($org, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <?= cms_brand_font_link($cmsBrand) ?>
    <script>tailwind.config={theme:{fontFamily:{sans:['var(--cms-font)']}}}</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
    :root{--cms-brand:<?= e($cmsBrand['primary']) ?>;--cms-brand-dark:<?= e($cmsBrand['dark']) ?>;--cms-brand-ink:<?= e($cmsBrand['ink']) ?>;--cms-brand-rgb:<?= e($cmsBrand['rgb']) ?>;--cms-font:<?= $cmsBrand['font'] ?>}
    html,body{font-family:var(--cms-font)!important}
    @keyframes cmsMarquee{from{transform:translateX(0)}to{transform:translateX(-50%)}}
    .cms-marquee{overflow:hidden}
    .cms-marquee-track{display:flex;width:max-content;gap:2rem;align-items:stretch;animation:cmsMarquee 32s linear infinite}
    .cms-marquee:hover .cms-marquee-track{animation-play-state:paused}
    .cms-partner-item{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;width:9.5rem;min-height:5.5rem;flex-shrink:0;padding:0 .5rem;text-decoration:none;color:#334155}
    .cms-marquee img{max-height:40px;max-width:132px;object-fit:contain;filter:grayscale(1);opacity:.82;transition:filter .25s ease,opacity .25s ease}
    .cms-partner-title{font-size:12px;font-weight:650;line-height:1.25;text-align:center;color:#475569;max-width:100%;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
    .cms-partner-item:hover img{filter:none;opacity:1}
    .cms-partner-item:hover .cms-partner-title{color:var(--cms-brand-dark)}
    .cms-stats{position:relative;overflow:hidden;background:#07111d;color:#fff}
    .cms-stats:before{content:"";position:absolute;inset:0;background:radial-gradient(900px 420px at 12% -10%,rgba(var(--cms-brand-rgb),.22),transparent 55%),radial-gradient(700px 380px at 100% 110%,rgba(var(--cms-brand-rgb),.12),transparent 50%);pointer-events:none}
    .cms-stats:after{content:"";position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px);background-size:48px 48px;mask-image:radial-gradient(ellipse at center,black 40%,transparent 78%);pointer-events:none;opacity:.7}
    .cms-stats-inner{position:relative;z-index:1;max-width:72rem;margin:0 auto;padding:4.2rem 1.25rem 4.6rem}
    .cms-stats-kicker{margin:0;text-align:center;font-size:11px;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:var(--cms-brand)}
    .cms-stats h2{margin:.55rem 0 2.4rem;text-align:center;font-size:clamp(1.85rem,3vw,2.5rem);font-weight:800;letter-spacing:-.03em}
    .cms-stats-grid{display:grid;grid-template-columns:1fr 1fr;gap:.9rem}
    .cms-stat{opacity:0;transform:translateY(22px);transition:opacity .65s ease,transform .65s ease;text-align:center;padding:1.5rem 1rem 1.4rem;border-radius:20px;background:rgba(255,255,255,.045);border:1px solid rgba(255,255,255,.08);box-shadow:inset 0 1px 0 rgba(255,255,255,.06)}
    .cms-stats.is-on .cms-stat{opacity:1;transform:none}
    .cms-stat-num{margin:0;display:flex;align-items:baseline;justify-content:center;gap:1px;font-size:clamp(2.35rem,5vw,3.4rem);font-weight:800;letter-spacing:-.045em;line-height:1;font-variant-numeric:tabular-nums;color:#fff;text-shadow:0 0 34px rgba(var(--cms-brand-rgb),.22)}
    .cms-stat-num .sfx{font-size:.48em;font-weight:700;color:color-mix(in srgb,var(--cms-brand) 55%,#fff);letter-spacing:0;text-shadow:none}
    .cms-stat-bar{display:block;width:42px;height:3px;margin:16px auto 0;border-radius:99px;background:linear-gradient(90deg,var(--cms-brand),var(--cms-brand-dark));transform:scaleX(0);transform-origin:center;transition:transform .7s cubic-bezier(.2,.8,.2,1) .15s}
    .cms-stats.is-on .cms-stat-bar{transform:scaleX(1)}
    .cms-stat-label{margin:.75rem 0 0;font-size:14px;line-height:1.4;color:#cbd5e1;font-weight:500}
    @keyframes cmsStatsGlow{from{transform:translate3d(0,0,0) scale(1)}to{transform:translate3d(3%,2%,0) scale(1.06)}}
    @media (min-width:900px){
        .cms-stats-grid{grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:0;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.09);border-radius:24px;overflow:hidden;backdrop-filter:blur(14px)}
        .cms-stat{border-radius:0;background:transparent;border:0;box-shadow:none;padding:2.35rem 1.4rem 2.15rem;border-right:1px solid rgba(255,255,255,.08)}
        .cms-stat:last-child{border-right:0}
        .cms-stat:hover{background:rgba(var(--cms-brand-rgb),.06)}
    }
    .cms-team-card{opacity:0;transform:translateY(36px);transition:opacity .7s ease,transform .7s ease,box-shadow .3s ease}
    .cms-team.is-on .cms-team-card{opacity:1;transform:none}
    .cms-team-card:hover{transform:translateY(-8px);box-shadow:0 18px 40px rgba(15,23,42,.12)}
    .cms-team-photo{position:relative;width:7.5rem;height:7.5rem;margin:0 auto}
    .cms-team-photo:before{content:"";position:absolute;inset:-7px;border-radius:999px;background:conic-gradient(from 0deg,var(--cms-brand),var(--cms-brand-dark),transparent 40%,color-mix(in srgb,var(--cms-brand) 55%,#fff),transparent 72%,var(--cms-brand));animation:cmsTeamSpin 7s linear infinite}
    .cms-team-photo:after{content:"";position:absolute;inset:-2px;border-radius:999px;background:#fff}
    .cms-team-photo img{position:relative;z-index:1;width:7.5rem;height:7.5rem;border-radius:999px;object-fit:cover;background:#e2e8f0;transform:scale(1.04);transition:transform .7s ease}
    .cms-team.is-on .cms-team-photo img{animation:cmsTeamZoom 7s ease-in-out infinite alternate}
    .cms-team-card:hover .cms-team-photo img{transform:scale(1.1)}
    @keyframes cmsTeamSpin{to{transform:rotate(360deg)}}
    @keyframes cmsTeamZoom{from{transform:scale(1)}to{transform:scale(1.08)}}
    .cms-faq summary::-webkit-details-marker{display:none}
    .cms-faq summary:after{content:"+";float:right;color:var(--cms-brand-dark);transition:transform .25s ease}
    .cms-faq[open] summary:after{transform:rotate(45deg)}
    .cms-faq[open]{border-color:color-mix(in srgb,var(--cms-brand) 45%,#fff);box-shadow:0 10px 28px rgba(var(--cms-brand-rgb),.12)}
    .cms-card{transition:transform .35s ease,box-shadow .35s ease,border-color .35s ease}
    .cms-card:hover{transform:translateY(-7px);box-shadow:0 18px 36px rgba(15,23,42,.1);border-color:color-mix(in srgb,var(--cms-brand) 45%,#fff)}
    .cms-card img{transition:transform .55s ease}
    .cms-card:hover img{transform:scale(1.05)}
    .cms-svc{position:relative}
    .cms-svc-card{position:relative;display:flex;flex-direction:column;background:#fff;border:1px solid #e2e8f0;border-radius:18px;overflow:hidden;opacity:0;transform:translateY(42px) scale(.96);transition:opacity .7s cubic-bezier(.2,.8,.2,1),transform .7s cubic-bezier(.2,.8,.2,1),box-shadow .35s ease,border-color .35s ease;color:inherit;text-decoration:none}
    .cms-svc.is-on .cms-svc-card{opacity:1;transform:none}
    .cms-svc-card:hover{box-shadow:0 22px 44px rgba(var(--cms-brand-rgb),.16);border-color:color-mix(in srgb,var(--cms-brand) 45%,#fff)}
    .cms-svc-no{position:absolute;z-index:2;top:14px;left:14px;font-size:12px;font-weight:800;letter-spacing:.12em;color:#fff;background:rgba(15,23,42,.55);backdrop-filter:blur(8px);border-radius:999px;padding:4px 9px}
    .cms-svc-media{display:block;height:168px;overflow:hidden;background:linear-gradient(135deg,#0f172a,var(--cms-brand-dark));position:relative}
    .cms-svc-media-empty:after{content:"";position:absolute;inset:-30%;background:radial-gradient(circle at 30% 30%,rgba(var(--cms-brand-rgb),.45),transparent 42%),radial-gradient(circle at 80% 80%,rgba(var(--cms-brand-rgb),.35),transparent 40%);animation:cmsStatsGlow 8s ease-in-out infinite alternate}
    .cms-svc-media img{width:100%;height:100%;object-fit:cover;transform:scale(1.08);transition:transform .8s ease}
    .cms-svc.is-on .cms-svc-media img{animation:cmsSvcKen 9s ease-in-out infinite alternate}
    .cms-svc-card:hover .cms-svc-media img{transform:scale(1.16);animation:none}
    .cms-svc-media:before{content:"";position:absolute;inset:0;z-index:1;background:linear-gradient(180deg,transparent 40%,rgba(15,23,42,.35));pointer-events:none}
    .cms-svc-card:after{content:"";position:absolute;top:0;left:-60%;width:40%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.28),transparent);transform:skewX(-18deg);opacity:0}
    .cms-svc-card:hover:after{animation:cmsSvcShine .85s ease}
    @keyframes cmsSvcKen{from{transform:scale(1.08) translateX(0)}to{transform:scale(1.16) translateX(-3%)}}
    @keyframes cmsSvcShine{from{left:-60%;opacity:1}to{left:120%;opacity:0}}
    .cms-svc-body{display:flex;flex-direction:column;flex:1;padding:18px 18px 20px}
    .cms-svc-body h3{font-size:1.125rem;font-weight:650;color:#0f172a;margin:0}
    .cms-svc-body p{margin:8px 0 0;font-size:.875rem;line-height:1.55;color:#475569}
    .cms-svc-more{margin-top:16px;display:inline-flex;align-items:center;gap:6px;font-size:.8125rem;font-weight:650;color:var(--cms-brand-dark)}
    .cms-svc-more:after{content:"→";transition:transform .3s ease}
    .cms-svc-card:hover .cms-svc-more:after{transform:translateX(5px)}
    .cms-cta{position:relative;overflow:hidden;background:linear-gradient(120deg,#0f172a,var(--cms-brand) 140%);color:#fff}
    .cms-cta:before{content:"";position:absolute;inset:-30%;background:radial-gradient(circle at 20% 20%,rgba(var(--cms-brand-rgb),.28),transparent 36%),radial-gradient(circle at 80% 70%,rgba(var(--cms-brand-rgb),.22),transparent 40%);animation:cmsStatsGlow 9s ease-in-out infinite alternate;pointer-events:none}
    .cms-cta > *{position:relative;z-index:1}
    .cms-cta a{transition:transform .3s ease,box-shadow .3s ease}
    .cms-cta a:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(0,0,0,.18)}
    .cms-skip{position:absolute;left:-999px;top:8px;z-index:90;background:#fff;color:#0f172a;padding:8px 14px;border-radius:8px;font-size:14px;font-weight:600}
    .cms-skip:focus{left:16px}
    .cms-footer-meta{display:flex;flex-wrap:wrap;gap:1.5rem 2rem;align-items:flex-end;justify-content:space-between;margin-top:1.75rem}
    .cms-footer-news{flex:1 1 16rem;min-width:min(100%,16rem);max-width:28rem}
    .cms-footer-news form{display:flex;flex-wrap:wrap;gap:.5rem;align-items:flex-end}
    .cms-footer-news form > div{flex:1 1 10rem}
    .cms-footer-news .hint{margin:.5rem 0 0;font-size:12px;opacity:.7}
    .cms-follow{margin:0 0 0 auto;text-align:right}
    .cms-follow p{margin:0 0 10px;font-size:11px;font-weight:650;letter-spacing:.22em;text-transform:uppercase;color:#fff}
    .cms-follow-row{display:flex;flex-wrap:wrap;gap:10px;justify-content:flex-end}
    .cms-follow a{width:44px;height:44px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;color:#e8eef6;background:rgba(255,255,255,.07);transition:background .2s ease,transform .2s ease,color .2s ease}
    .cms-follow a:hover{background:rgba(255,255,255,.14);transform:translateY(-2px);color:#fff}
    .cms-follow svg{display:block;width:18px;height:18px}
    @media (max-width:640px){
        .cms-follow{margin-left:0;width:100%;text-align:left}
        .cms-follow-row{justify-content:flex-start}
    }
    .cms-spot{background:color-mix(in srgb,var(--cms-brand) 8%,#fff)}
    .cms-spot-grid{display:grid;grid-template-columns:1fr;gap:2.5rem;align-items:center}
    @media (min-width:900px){.cms-spot-grid{grid-template-columns:minmax(0,1.05fr) minmax(0,1fr);gap:3.5rem}}
    .cms-spot-photo{position:relative;border-radius:28px;overflow:hidden;box-shadow:0 22px 50px rgba(15,23,42,.12);background:color-mix(in srgb,var(--cms-brand) 18%,#fff);min-height:280px}
    .cms-spot-slide{display:none;position:relative}
    .cms-spot-slide.is-on{display:block}
    .cms-spot-slide img{width:100%;height:min(420px,58vw);object-fit:cover;display:block}
    .cms-spot-badge{position:absolute;left:16px;bottom:16px;display:flex;align-items:center;gap:10px;background:#fff;border-radius:16px;padding:10px 14px 10px 10px;box-shadow:0 10px 28px rgba(15,23,42,.12);max-width:calc(100% - 32px)}
    .cms-spot-badge > div span{display:block;font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:#64748b;font-weight:650}
    .cms-spot-badge strong{display:block;font-size:14px;color:#0f172a}
    .cms-spot-pin{width:36px;height:36px;border-radius:999px;background:color-mix(in srgb,var(--cms-brand) 12%,#fff);color:var(--cms-brand-dark);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0}
    .cms-spot-dots{position:absolute;top:14px;right:16px;display:flex;gap:6px;z-index:2}
    .cms-spot-dots button{width:7px;height:7px;border:0;border-radius:999px;background:rgba(255,255,255,.45);padding:0;cursor:pointer}
    .cms-spot-dots button.is-on{background:#fff;width:16px}
    .cms-spot-kicker{margin:0;font-size:12px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:var(--cms-brand-dark)}
    .cms-spot h2{margin:.6rem 0 0;font-size:clamp(1.7rem,3vw,2.35rem);line-height:1.2;font-weight:800;color:#0f172a}
    .cms-spot-lead{margin:1rem 0 0;color:#475569;line-height:1.65;font-size:15px}
    .cms-spot-list{margin:1.6rem 0 0;display:flex;flex-direction:column;gap:1.15rem}
    .cms-spot-item{display:flex;gap:14px;align-items:flex-start}
    .cms-spot-ico{width:44px;height:44px;border-radius:999px;background:#fff;box-shadow:0 6px 16px rgba(15,23,42,.08);color:var(--cms-brand-dark);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0}
    .cms-spot-item h3{margin:0;font-size:15px;font-weight:700;color:#0f172a}
    .cms-spot-item p{margin:.25rem 0 0;font-size:14px;line-height:1.55;color:#64748b}
    .cms-film{background:#fff}
    .cms-film-inner{max-width:58rem;margin:0 auto}
    .cms-film-copy{text-align:center;max-width:40rem;margin:0 auto 2.4rem}
    .cms-film-kicker{margin:0;font-size:12px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--cms-brand-dark)}
    .cms-film h2{margin:.7rem 0 0;font-size:clamp(1.9rem,3.4vw,2.6rem);line-height:1.15;font-weight:800;color:#0f172a}
    .cms-film-lead{margin:1rem auto 0;color:#64748b;line-height:1.7;font-size:16px;max-width:36rem}
    .cms-film-card{position:relative;border-radius:28px;overflow:hidden;box-shadow:0 28px 70px rgba(15,23,42,.18);background:#0f172a;min-height:240px}
    .cms-film-card img{width:100%;height:min(460px,58vw);object-fit:cover;display:block}
    .cms-film-shade{position:absolute;inset:0;background:linear-gradient(180deg,rgba(15,23,42,.08) 30%,rgba(15,23,42,.55) 100%);pointer-events:none}
    .cms-film-caption{position:absolute;left:28px;bottom:28px;margin:0;color:#fff;font-weight:800;font-size:clamp(1.55rem,4.4vw,2.9rem);line-height:1.05;letter-spacing:-.02em;text-shadow:0 10px 28px rgba(0,0,0,.4);max-width:72%;white-space:pre-line}
    .cms-film-play{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:84px;height:84px;border:0;border-radius:999px;background:#fff;color:#0f172a;cursor:pointer;box-shadow:0 16px 40px rgba(15,23,42,.35);display:grid;place-items:center;transition:transform .2s ease,box-shadow .2s ease}
    .cms-film-play:hover{transform:translate(-50%,-50%) scale(1.06);box-shadow:0 18px 48px rgba(15,23,42,.42)}
    .cms-film-play svg{width:28px;height:28px;margin-left:3px;display:block}
    .cms-film-card:has([data-film-play]){cursor:pointer}
    .cms-film-modal{position:fixed;inset:0;z-index:80;background:rgba(15,23,42,.84);display:flex;align-items:center;justify-content:center;padding:20px}
    .cms-film-modal[hidden]{display:none}
    .cms-film-stage{width:min(960px,100%);aspect-ratio:16/9;background:#000;border-radius:16px;overflow:hidden;box-shadow:0 24px 80px rgba(0,0,0,.45)}
    .cms-film-stage iframe,.cms-film-stage video{width:100%;height:100%;border:0;display:block}
    .cms-film-close{position:absolute;top:16px;right:16px;width:40px;height:40px;border:0;border-radius:999px;background:rgba(255,255,255,.12);color:#fff;font-size:22px;line-height:1;cursor:pointer}
    .cms-film-close:hover{background:rgba(255,255,255,.22)}
    .cms-places{background:#fff}
    .cms-places-copy{text-align:center;max-width:42rem;margin:0 auto 2.2rem}
    .cms-places-kicker{margin:0;font-size:12px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--cms-brand-dark)}
    .cms-places h2{margin:.7rem 0 0;font-size:clamp(1.8rem,3.2vw,2.45rem);line-height:1.15;font-weight:800;color:#0f172a}
    .cms-places-grid{display:grid;grid-template-columns:1fr;gap:1.25rem}
    @media (min-width:800px){.cms-places-grid{grid-template-columns:1fr 1fr;gap:1.5rem}}
    .cms-place{position:relative;border-radius:28px;overflow:hidden;display:block;color:#fff;box-shadow:0 22px 50px rgba(15,23,42,.14);min-height:280px;text-decoration:none}
    .cms-place img,.cms-place-fallback{width:100%;height:min(420px,68vw);object-fit:cover;display:block;transform:scale(1.02);transition:transform .7s ease}
    .cms-place-fallback{background:#1e293b}
    .cms-place:hover img{transform:scale(1.08)}
    .cms-place-shade{position:absolute;inset:0;background:linear-gradient(180deg,rgba(15,23,42,.06) 18%,rgba(15,23,42,.72) 100%)}
    .cms-place-body{position:absolute;left:0;right:0;bottom:0;padding:1.6rem 1.6rem 1.45rem;display:block}
    .cms-place-kicker{display:block;font-size:11px;letter-spacing:.12em;text-transform:uppercase;opacity:.82;font-weight:650}
    .cms-place h3{margin:.4rem 0 0;font-size:clamp(1.2rem,2.2vw,1.55rem);font-weight:800;line-height:1.2}
    .cms-place p{margin:.45rem 0 0;font-size:14px;line-height:1.55;color:rgba(255,255,255,.88);max-width:28rem}
    .cms-place-more{display:inline-flex;align-items:center;gap:6px;margin-top:14px;font-size:14px;font-weight:700}
    .cms-search-hit{transition:transform .3s ease,border-color .3s ease}
    .cms-search-hit:hover{transform:translateY(-3px);border-color:color-mix(in srgb,var(--cms-brand) 45%,#fff)}
    .cms-reveal{opacity:0;transform:translateY(22px);transition:opacity .6s ease,transform .6s ease}
    .cms-reveal.is-on{opacity:1;transform:none}
    .cms-top{position:fixed;z-index:50;left:16px;bottom:16px;width:44px;height:44px;border-radius:999px;background:#0f172a;color:#fff;display:none;align-items:center;justify-content:center;box-shadow:0 8px 20px rgba(0,0,0,.25)}
    .cms-top.is-on{display:inline-flex}
    .cms-bar{background:var(--cms-brand);color:var(--cms-brand-ink);font-size:13px;font-weight:600;text-align:center}
    .cms-bar a{display:block;padding:8px 16px}
    .cms-bar a:hover{filter:brightness(1.08)}
    .cms-quotes{position:relative;min-height:220px}
    .cms-quote{display:none;opacity:0;transform:translateY(12px);transition:opacity .5s ease,transform .5s ease}
    .cms-quote.is-on{display:block;opacity:1;transform:none}
    .cms-stars{color:#f59e0b;letter-spacing:.12em}
    .cms-read{position:fixed;top:0;left:0;height:3px;width:0;z-index:70;background:var(--cms-brand);box-shadow:0 0 8px rgba(var(--cms-brand-rgb),.7)}
    .cms-cookie{position:fixed;z-index:60;left:16px;right:16px;bottom:16px;max-width:640px;margin:0 auto;background:#0f172a;color:#e2e8f0;border-radius:12px;padding:14px 16px;box-shadow:0 12px 40px rgba(0,0,0,.28);display:none;align-items:center;gap:12px;justify-content:space-between}
    .cms-cookie.is-on{display:flex}
    .cms-slide-nav{position:absolute;z-index:4;top:50%;width:56px;height:56px;margin-top:-28px;border:0;border-radius:999px;background:rgba(15,23,42,.55);color:#fff;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 8px 24px rgba(0,0,0,.28);backdrop-filter:blur(6px);transition:background .2s ease,transform .2s ease,box-shadow .2s ease}
    .cms-slide-nav:hover{background:rgba(15,23,42,.82);transform:scale(1.06);box-shadow:0 10px 28px rgba(0,0,0,.35)}
    .cms-slide-nav svg{display:block;width:26px;height:26px;flex-shrink:0}
    .cms-slide-prev{left:18px}
    .cms-slide-next{right:18px}
    @media (max-width:640px){
        .cms-slide-nav{width:46px;height:46px;margin-top:-23px}
        .cms-slide-nav svg{width:22px;height:22px}
        .cms-slide-prev{left:10px}
        .cms-slide-next{right:10px}
    }
    .site-slide{opacity:0;visibility:hidden;pointer-events:none;transition:opacity .55s ease,visibility .55s ease}
    .site-slide.is-on{opacity:1;visibility:visible;pointer-events:auto}
    .cms-hsearch{display:inline-flex;align-items:center;justify-content:flex-end;max-width:100%;flex-shrink:0;overflow:hidden;position:relative}
    .cms-hsearch.is-on{overflow:visible}
    .cms-hsearch input{width:0;min-width:0;opacity:0;padding:0;border:0;margin:0;background:transparent;color:inherit;font-size:13px;line-height:1.2;pointer-events:none;visibility:hidden;transition:width .35s ease,opacity .25s ease,padding .35s ease}
    .cms-hsearch.is-on input{width:min(220px,52vw);opacity:1;padding:7px 12px;pointer-events:auto;visibility:visible;border:1px solid currentColor;border-radius:999px;outline:none;background:rgba(255,255,255,.08)}
    .cms-hsearch input::placeholder{color:currentColor;opacity:.55}
    .cms-hsearch button{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border:0;background:transparent;color:inherit;border-radius:999px;flex-shrink:0;cursor:pointer}
    .cms-hsearch button:hover{background:rgba(148,163,184,.22)}
    .cms-hsearch svg{width:20px;height:20px}
    .cms-suggest{position:absolute;top:calc(100% + 8px);right:0;z-index:60;width:min(320px,86vw);display:none;background:#fff;color:#0f172a;border-radius:14px;box-shadow:0 18px 40px rgba(15,23,42,.22);border:1px solid #e2e8f0;padding:6px;max-height:min(70vh,360px);overflow:auto}
    .cms-suggest.is-on{display:block}
    .cms-suggest a{display:block;padding:8px 10px;border-radius:10px;text-decoration:none;color:#0f172a}
    .cms-suggest a:hover,.cms-suggest a.is-on{background:#f1f5f9}
    .cms-suggest .t{display:block;font-size:13px;font-weight:650;line-height:1.3}
    .cms-suggest .k{display:block;font-size:11px;color:#64748b;margin-top:2px}
    .cms-toc{border:1px solid #e2e8f0;background:#fff;border-radius:12px;padding:16px 18px;margin:24px 0}
    .cms-toc p{font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#64748b;margin:0 0 8px}
    .cms-toc a{display:block;font-size:14px;color:#0f172a;padding:3px 0}
    .cms-toc a.is-h3{padding-left:12px;color:#475569;font-size:13px}
    .cms-toc a:hover{color:var(--cms-brand-dark)}
    .cms-gallery-band{position:relative;overflow:hidden}
    .cms-gallery-band:before{content:"";position:absolute;inset:-25%;background:radial-gradient(circle at 18% 20%,rgba(var(--cms-brand-rgb),.16),transparent 42%),radial-gradient(circle at 82% 80%,rgba(var(--cms-brand-rgb),.12),transparent 40%);animation:cmsStatsGlow 10s ease-in-out infinite alternate;pointer-events:none}
    .cms-gallery-band > *{position:relative;z-index:1}
    @media (prefers-reduced-motion:reduce){
        .cms-marquee-track,.cms-team-photo:before,.cms-team.is-on .cms-team-photo img,.cms-gallery-band:before,.cms-cta:before,.cms-svc.is-on .cms-svc-media img,.cms-svc-media-empty:after,.cms-svc-card:hover:after{animation:none}
        .cms-stat,.cms-team-card,.cms-reveal,.cms-svc-card{opacity:1;transform:none}
        .cms-hsearch input{transition:none}
        .cms-stat-bar{transition:none;transform:none}
        .site-slide{transition:none}
    }
    @media print{
        header,.cms-bar,.cms-skip,.cms-hsearch,.cms-float,.cms-cookie,.cms-top,.cms-read,footer,#mobile-menu{display:none!important}
        main{max-width:none;padding:0}
        .cms-svc-card,.cms-reveal,.cms-team-card{opacity:1;transform:none}
    }
    .cms-link{color:var(--cms-brand-dark)}
    .cms-btn{background:var(--cms-brand);color:var(--cms-brand-ink)}
    .text-sky-700,.text-sky-600,.hover\:text-sky-700:hover,.group:hover .group-hover\:text-sky-700{color:var(--cms-brand-dark)!important}
    .text-sky-300{color:color-mix(in srgb,var(--cms-brand) 55%,#fff)!important}
    .text-sky-50{color:color-mix(in srgb,#fff 88%,var(--cms-brand))!important}
    .bg-sky-400,.bg-sky-500,.bg-sky-600{background-color:var(--cms-brand)!important;color:var(--cms-brand-ink)!important}
    .hover\:bg-sky-400:hover{filter:brightness(1.08)}
    .hover\:bg-sky-700:hover{filter:brightness(.92)}
    .hover\:bg-sky-50:hover{background-color:color-mix(in srgb,var(--cms-brand) 12%,#fff)!important}
    .hover\:border-sky-300:hover{border-color:color-mix(in srgb,var(--cms-brand) 45%,#fff)!important}
    .to-sky-700,.to-sky-800{--tw-gradient-to:var(--cms-brand-dark)!important}
    </style>
    <noscript><style>.cms-svc-card,.cms-reveal,.cms-stat,.cms-team-card,.site-slide.is-on{opacity:1!important;visibility:visible!important;transform:none!important}</style></noscript>
    <?php
    $customCss = option_get($pdo, 'custom_css', '');
    $customCss = str_replace(array('</style>', '</STYLE>'), '', $customCss);
    if (trim($customCss) !== ''):
    ?>
    <style id="cms-custom"><?= $customCss ?></style>
    <?php endif; ?>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
<a class="cms-skip" href="#cms-main">İçeriğe geç</a>
<?php
$barText = option_get($pdo, 'bar_text', '');
if (option_get($pdo, 'bar_show', '1') === '1' && $barText !== ''):
    $barUrl = trim(option_get($pdo, 'bar_url', ''));
    if ($barUrl === '') {
        $barUrl = contact_permalink();
    }
?>
<div class="cms-bar"><a href="<?= e($barUrl) ?>"><?= e($barText) ?></a></div>
<?php endif; ?>
<?php if ($sideLayout): ?>
<div class="min-h-screen flex <?= $headerType === 'right' ? 'flex-row-reverse' : '' ?>">
<aside class="hidden md:flex flex-col shrink-0" style="width:<?= e($hSide) ?>;background:<?= e($headerBg) ?>;color:<?= e($headerText) ?>;font-size:<?= e($hFont) ?>">
    <div class="px-4 py-4"><?php $siteLogo(); ?></div>
    <nav class="flex flex-col px-2 py-2 gap-1">
        <?php if ($menuParents): ?>
            <?php $renderItems($menuParents, $menuTreeUse, $headerAccent, true); ?>
        <?php else: ?>
            <?php $fallbackNav($navPages, true); ?>
        <?php endif; ?>
        <div class="px-2 pt-3"><?php $headerSearch(); ?></div>
    </nav>
</aside>
<div class="flex-1 min-w-0">
<header class="md:hidden px-4 py-3 flex items-center justify-between gap-2 <?= $headerSticky === '1' ? 'sticky top-0 z-30' : '' ?>" style="background:<?= e($headerBg) ?>;color:<?= e($headerText) ?>">
    <?php $siteLogo(true); ?>
    <div class="flex items-center gap-1">
        <?php $headerSearch(); ?>
        <?php $hamburger($mobileBtnStyle, $headerText); ?>
    </div>
</header>
<?php else: ?>
<header class="md:hidden <?= $headerSticky === '1' ? 'sticky top-0 z-30' : 'relative z-20' ?>" style="background:<?= e($overlayOn ? $headerBg : $headerBg) ?>;color:<?= e($headerText) ?>">
    <div class="flex items-center justify-between gap-2 <?= e($wrapCls) ?>" style="min-height:<?= e($hBar) ?>">
        <?php $siteLogo(true); ?>
        <div class="flex items-center gap-1">
            <?php $headerSearch(); ?>
            <?php $hamburger($mobileBtnStyle, $headerText); ?>
        </div>
    </div>
</header>
<header class="hidden md:block <?= $overlayOn ? 'absolute top-0 left-0 right-0 z-30' : $stickyCls ?>" style="<?= $overlayOn ? 'background:transparent;color:#fff;font-size:' . e($hFont) : 'background:' . e($headerBg) . ';color:' . e($headerText) . ';font-size:' . e($hFont) ?>">
    <?php if ($headerType === 'centered'): ?>
        <div class="<?= e($wrapCls) ?> py-3 text-center space-y-2">
            <div class="flex justify-center"><?php $siteLogo(); ?></div>
            <nav class="flex flex-wrap items-center justify-center gap-1">
                <?php if ($menuParents): $renderItems($menuParents, $menuTreeUse, $headerAccent, false); else: $fallbackNav($navPages, false); endif; ?>
                <?php $headerSearch(); ?>
            </nav>
        </div>
    <?php elseif ($headerType === 'split'): ?>
        <div class="<?= e($wrapCls) ?> flex items-center justify-between gap-4" style="min-height:<?= e($hBar) ?>">
            <?php $siteLogo(); ?>
            <div class="flex items-center gap-2">
                <nav class="flex items-center gap-1">
                    <?php if ($menuParents): $renderItems($menuParents, $menuTreeUse, $headerAccent, false); else: $fallbackNav($navPages, false); endif; ?>
                </nav>
                <?php $headerSearch(); ?>
            </div>
        </div>
    <?php elseif ($headerType === 'stacked'): ?>
        <div style="border-bottom:1px solid rgba(255,255,255,.12)">
            <div class="<?= e($wrapCls) ?> flex items-center justify-between" style="min-height:<?= e($hBar) ?>">
                <?php $siteLogo(); ?>
                <?php $headerSearch(); ?>
            </div>
        </div>
        <nav class="<?= e($wrapCls) ?> flex flex-wrap items-center gap-1 py-2">
            <?php if ($menuParents): $renderItems($menuParents, $menuTreeUse, $headerAccent, false); else: $fallbackNav($navPages, false); endif; ?>
        </nav>
    <?php else: ?>
        <div class="<?= e($wrapCls) ?> flex items-center justify-between gap-4" style="min-height:<?= e($hBar) ?>">
            <?php $siteLogo(); ?>
            <div class="flex items-center gap-2">
                <nav class="flex items-center gap-1">
                    <?php if ($menuParents): $renderItems($menuParents, $menuTreeUse, $headerAccent, false); else: $fallbackNav($navPages, false); endif; ?>
                </nav>
                <?php $headerSearch(); ?>
            </div>
        </div>
    <?php endif; ?>
</header>
<?php endif; ?>

<div id="mobile-menu" class="hidden">
    <?php if ($mobileMenuType === 'dropdown'): ?>
        <div class="md:hidden border-t" style="background:<?= e($headerBg) ?>;color:<?= e($headerText) ?>">
            <div class="px-4 py-3 space-y-1" data-mobile-panel>
                <?php if ($mobileShowHome === '1'): ?>
                    <a class="block px-3 py-2 rounded-md" href="<?= e(public_url()) ?>">Ana sayfa</a>
                <?php endif; ?>
                <?php if ($menuParents): $renderItems($menuParents, $menuTreeUse, $headerAccent, true); endif; ?>
                <?php if ($mobileShowPages === '1'): ?>
                    <?php foreach ($navPages as $nav): ?>
                        <a class="block px-3 py-2 rounded-md" href="<?= e(page_permalink($nav['slug'])) ?>"><?= e((string) $nav['title']) ?></a>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    <a class="block px-3 py-2 rounded-md" href="<?= e(services_permalink()) ?>">Hizmetler</a>
                    <a class="block px-3 py-2 rounded-md" href="<?= e(contact_permalink()) ?>">İletişim</a>
                    <a class="block px-3 py-2 rounded-md" href="<?= e(staff_list_permalink()) ?>">Ekip</a>
                    <a class="block px-3 py-2 rounded-md" href="<?= e(posts_list_permalink()) ?>">Yazılar</a>
                    <button type="button" id="mobile-menu-close" class="mt-2 text-sm opacity-80">Kapat</button>
            </div>
        </div>
    <?php else: ?>
        <div class="fixed inset-0 z-50 md:hidden" data-mobile-overlay>
            <div class="absolute inset-0 bg-slate-900/60" data-close-mobile="1"></div>
            <div class="<?= $mobileMenuType === 'fullscreen' ? 'absolute inset-0' : ('absolute top-0 bottom-0 w-[80%] max-w-xs ' . ($mobileMenuType === 'drawer-right' ? 'right-0' : 'left-0')) ?> overflow-y-auto p-4" style="background:<?= e($headerBg) ?>;color:<?= e($headerText) ?>" data-mobile-panel>
                <div class="flex items-center justify-between mb-4">
                    <?php $siteLogo(true); ?>
                    <button type="button" id="mobile-menu-close" class="text-sm">Kapat</button>
                </div>
                <nav class="flex flex-col gap-1">
                    <?php if ($mobileShowHome === '1'): ?>
                        <a class="block px-3 py-2 rounded-md" href="<?= e(public_url()) ?>">Ana sayfa</a>
                    <?php endif; ?>
                    <?php if ($menuParents): $renderItems($menuParents, $menuTreeUse, $headerAccent, true); endif; ?>
                    <?php if ($mobileShowPages === '1'): ?>
                        <?php foreach ($navPages as $nav): ?>
                            <a class="block px-3 py-2 rounded-md" href="<?= e(page_permalink($nav['slug'])) ?>"><?= e((string) $nav['title']) ?></a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <a class="block px-3 py-2 rounded-md" href="<?= e(services_permalink()) ?>">Hizmetler</a>
                    <a class="block px-3 py-2 rounded-md" href="<?= e(contact_permalink()) ?>">İletişim</a>
                    <a class="block px-3 py-2 rounded-md" href="<?= e(staff_list_permalink()) ?>">Ekip</a>
                    <a class="block px-3 py-2 rounded-md" href="<?= e(posts_list_permalink()) ?>">Yazılar</a>
                </nav>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($overlayOn): ?>
<div class="relative">
<?php endif; ?>
<?php if (!empty($homeSlides)): ?>
<section class="relative bg-slate-900 text-white">
    <div id="site-slider" class="relative overflow-hidden min-h-[320px] md:min-h-[420px]">
        <?php foreach ($homeSlides as $i => $slide): ?>
            <?php
            $ytSrc = youtube_embed_src((string) ($slide['youtube_url'] ?? ''), true);
            $slideCover = cms_slider_cover($slide, (int) $i);
            ?>
            <div class="site-slide <?= $i === 0 ? 'is-on' : '' ?> absolute inset-0">
                <?php if ($ytSrc !== ''): ?>
                    <div class="absolute inset-0 overflow-hidden bg-slate-900">
                        <iframe class="absolute left-1/2 top-1/2 h-[177%] min-h-full w-[177%] min-w-full -translate-x-1/2 -translate-y-1/2 pointer-events-none" data-src="<?= e($ytSrc) ?>" src="<?= $i === 0 ? e($ytSrc) : '' ?>" title="" allow="autoplay; muted; encrypted-media" tabindex="-1"></iframe>
                    </div>
                    <div class="absolute inset-0 bg-slate-900/45"></div>
                <?php elseif ($slideCover !== ''): ?>
                    <img src="<?= e($slideCover) ?>" alt="" class="absolute inset-0 h-full w-full object-cover">
                    <div class="absolute inset-0 bg-slate-900/50"></div>
                <?php else: ?>
                    <div class="absolute inset-0 bg-gradient-to-r from-slate-900 to-sky-800"></div>
                <?php endif; ?>
                <div class="relative max-w-5xl mx-auto px-4 py-16 md:py-24 <?= $overlayOn ? 'pt-28' : '' ?>">
                    <h2 class="text-3xl md:text-5xl font-bold max-w-3xl"><?= e((string) $slide['title']) ?></h2>
                    <?php if (!empty($slide['subtitle'])): ?>
                        <div class="mt-4 text-lg text-slate-200 max-w-2xl"><?= public_html((string) $slide['subtitle']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($slide['button_text']) && !empty($slide['button_url'])): ?>
                        <a href="<?= e((string) $slide['button_url']) ?>" class="inline-flex mt-6 rounded-md bg-sky-500 text-slate-900 font-semibold px-5 py-2.5 hover:bg-sky-400"><?= e((string) $slide['button_text']) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if (count($homeSlides) > 1): ?>
        <?php
        $slideSec = (int) option_get($pdo, 'slider_interval', '6');
        if ($slideSec < 3) {
            $slideSec = 6;
        }
        if ($slideSec > 15) {
            $slideSec = 15;
        }
        $slideAuto = option_get($pdo, 'slider_autoplay', '1') === '1' ? '1' : '0';
        ?>
        <button type="button" class="cms-slide-nav cms-slide-prev" data-slide-prev aria-label="Önceki slayt"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 5.5 8.5 12 15 18.5"/></svg></button>
        <button type="button" class="cms-slide-nav cms-slide-next" data-slide-next aria-label="Sonraki slayt"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 5.5 6.5 6.5L9 18.5"/></svg></button>
        <div class="absolute bottom-4 left-0 right-0 z-[4] flex justify-center gap-2" data-slider-dots data-interval="<?= (int) ($slideSec * 1000) ?>" data-autoplay="<?= e($slideAuto) ?>">
            <?php foreach ($homeSlides as $i => $slide): ?>
                <button type="button" class="slider-dot h-2.5 w-2.5 rounded-full <?= $i === 0 ? 'bg-white' : 'bg-white/40' ?>" data-slide="<?= (int) $i ?>" aria-label="Slayt <?= (int) ($i + 1) ?>"></button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>
<?php if ($overlayOn): ?>
</div>
<?php endif; ?>
<main id="cms-main" class="max-w-5xl mx-auto px-4 py-10">
<?php
$pubFlash = flash_get();
if (!empty($pubFlash) && is_array($pubFlash)):
?>
    <div class="mb-6 rounded-md border px-4 py-3 text-sm <?= (($pubFlash['type'] ?? '') === 'error') ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-800' ?>">
        <?= e((string) ($pubFlash['message'] ?? '')) ?>
    </div>
<?php endif; ?>
