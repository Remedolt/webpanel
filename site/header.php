<?php
declare(strict_types=1);
if (!isset($pdo, $siteTitle)) {
    exit;
}
$docTitle = isset($docTitle) ? (string) $docTitle : $siteTitle;
$metaDescription = isset($metaDescription) ? (string) $metaDescription : $siteTitle;
$canonicalUrl = isset($canonicalUrl) ? (string) $canonicalUrl : public_url();
$ogImage = isset($ogImage) ? (string) $ogImage : '';
$navPages = isset($navPages) && is_array($navPages) ? $navPages : [];
$navCategories = isset($navCategories) && is_array($navCategories) ? $navCategories : [];
$searchQuery = isset($searchQuery) ? (string) $searchQuery : '';
$flashPublic = function_exists('flash_get') ? flash_get('flash_public') : null;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($docTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <meta property="og:title" content="<?= e($docTitle) ?>">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <meta property="og:type" content="<?= !empty($isPostPage) ? 'article' : 'website' ?>">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <?php if ($ogImage !== ''): ?>
        <meta property="og:image" content="<?= e($ogImage) ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="<?= $ogImage !== '' ? 'summary_large_image' : 'summary' ?>">
    <?= favicon_tags() ?>
    <link rel="alternate" type="application/rss+xml" title="<?= e($siteTitle) ?> RSS" href="<?= e(public_url('rss.php')) ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ink: '#071018',
                        accent: '#22d3ee'
                    }
                }
            }
        }
    </script>
    <style>
        .article-body p { margin: 0 0 1.1rem; }
        .article-body h2, .article-body h3 { font-weight: 700; color: #0f172a; margin: 1.6rem 0 0.7rem; line-height: 1.3; }
        .article-body h2 { font-size: 1.45rem; }
        .article-body h3 { font-size: 1.2rem; }
        .article-body a { color: #0369a1; text-decoration: underline; text-underline-offset: 2px; }
        .article-body ul, .article-body ol { margin: 0 0 1.1rem; padding-left: 1.25rem; }
        .article-body ul { list-style: disc; }
        .article-body ol { list-style: decimal; }
        .article-body pre {
            background: #0b1220;
            color: #e2e8f0;
            padding: 1rem 1.1rem;
            border-radius: 0.85rem;
            overflow-x: auto;
            font-size: 0.875rem;
            line-height: 1.6;
            margin: 0 0 1.25rem;
        }
        .article-body code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 0.875em;
        }
        .article-body :not(pre) > code {
            background: #e2e8f0;
            color: #0f172a;
            padding: 0.1rem 0.35rem;
            border-radius: 0.3rem;
        }
        .article-body img { max-width: 100%; height: auto; border-radius: 0.75rem; }
        .article-body blockquote {
            border-left: 4px solid #22d3ee;
            padding: 0.2rem 0 0.2rem 1rem;
            color: #475569;
            margin: 0 0 1.1rem;
        }
        .skip-link {
            position: absolute;
            left: 1rem;
            top: -4rem;
            z-index: 80;
            background: #22d3ee;
            color: #071018;
            padding: 0.55rem 1rem;
            border-radius: 0.55rem;
            font-weight: 700;
            font-size: 0.875rem;
        }
        .skip-link:focus {
            top: 1rem;
            outline: 2px solid #fff;
            outline-offset: 2px;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
<?php if (!empty($a11ySkip)): ?>
    <a class="skip-link" href="#icerik">İçeriğe atla</a>
<?php endif; ?>
<header class="bg-ink text-white">
    <div class="max-w-6xl mx-auto px-4">
        <div class="h-16 flex items-center justify-between gap-4">
            <a href="<?= e(public_url()) ?>" class="flex items-center gap-2 font-semibold tracking-tight shrink-0">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-cyan-400 text-ink text-sm font-black">&lt;/&gt;</span>
                <?= e($siteTitle) ?>
            </a>
            <nav class="hidden md:flex items-center gap-5 text-sm text-slate-300">
                <a class="hover:text-white <?= strpos($currentPath, '/kategori') === false && strpos($currentPath, '/sayfa') === false && strpos($currentPath, '/ara') === false ? 'text-white' : '' ?>" href="<?= e(public_url()) ?>">Yazılar</a>
                <?php foreach ($navPages as $nav): ?>
                    <a class="hover:text-white" href="<?= e(page_permalink($nav['slug'])) ?>"><?= e((string) $nav['title']) ?></a>
                <?php endforeach; ?>
            </nav>
            <form action="<?= e(public_url('ara')) ?>" method="get" class="hidden sm:block w-44 lg:w-64">
                <label class="relative block">
                    <span class="sr-only">Ara</span>
                    <input type="search" name="q" value="<?= e($searchQuery) ?>" placeholder="Yazılarda ara…"
                           class="w-full rounded-md border border-slate-700 bg-slate-900/70 py-1.5 pl-3 pr-3 text-sm text-white placeholder:text-slate-500 focus:border-cyan-400 focus:outline-none">
                </label>
            </form>
        </div>
        <?php if ($navCategories): ?>
            <div class="flex gap-2 overflow-x-auto pb-3 text-xs">
                <?php foreach ($navCategories as $cat): ?>
                    <a href="<?= e(category_permalink($cat['slug'])) ?>"
                       class="shrink-0 rounded-full border border-slate-700 bg-slate-900/50 px-3 py-1 text-slate-300 hover:border-cyan-400 hover:text-white">
                        <?= e((string) $cat['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</header>
<main id="icerik" class="max-w-6xl mx-auto px-4 py-10" tabindex="-1">
    <?php if (!empty($flashPublic) && is_array($flashPublic)): ?>
        <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= (($flashPublic['type'] ?? '') === 'error') ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-800' ?>">
            <?= e((string) ($flashPublic['message'] ?? '')) ?>
        </div>
    <?php endif; ?>
