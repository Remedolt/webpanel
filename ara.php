<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';

if (isset($_GET['suggest'])) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    $q = trim((string) ($_GET['q'] ?? ''));
    $len = function_exists('mb_strlen') ? mb_strlen($q) : strlen($q);
    $items = array();
    if ($len >= 2 && $len <= 80) {
        $safe = str_replace(array('%', '_'), '', $q);
        $like = '%' . $safe . '%';
        $push = static function (array &$items, $rows, string $kind, callable $urlFn, string $titleKey = 'title') {
            foreach ($rows as $row) {
                $title = trim((string) ($row[$titleKey] ?? ''));
                if ($title === '') {
                    continue;
                }
                $items[] = array(
                    'kind' => $kind,
                    'title' => $title,
                    'url' => $urlFn($row),
                );
            }
        };
        try {
            $st = $pdo->prepare("SELECT title, slug FROM posts WHERE status = 'publish' AND title LIKE ? ORDER BY created_at DESC LIMIT 3");
            $st->execute([$like]);
            $push($items, $st->fetchAll(), 'Yazı', static function ($row) {
                return post_permalink($row['slug']);
            });
        } catch (PDOException $e) {
        }
        try {
            $st = $pdo->prepare("SELECT title, slug FROM site_pages WHERE status = 'publish' AND title LIKE ? ORDER BY sort_order ASC LIMIT 3");
            $st->execute([$like]);
            $push($items, $st->fetchAll(), 'Sayfa', static function ($row) {
                return page_permalink($row['slug']);
            });
        } catch (PDOException $e) {
        }
        try {
            $st = $pdo->prepare("SELECT title, slug FROM services WHERE status = 'publish' AND title LIKE ? ORDER BY sort_order ASC LIMIT 2");
            $st->execute([$like]);
            $push($items, $st->fetchAll(), 'Hizmet', static function ($row) {
                return service_permalink($row['slug']);
            });
        } catch (PDOException $e) {
        }
    }
    echo json_encode(array('items' => $items), JSON_UNESCAPED_UNICODE);
    exit;
}

$q = trim((string) ($_GET['q'] ?? ''));
$qLike = '%' . $q . '%';
$postsFound = [];
$pagesFound = [];
$staffFound = [];
$faqFound = [];
$svcFound = [];
if ($q !== '') {
    try {
        $st = $pdo->prepare("SELECT title, slug, excerpt FROM posts WHERE status = 'publish' AND (title LIKE ? OR content LIKE ? OR excerpt LIKE ?) ORDER BY created_at DESC LIMIT 20");
        $st->execute([$qLike, $qLike, $qLike]);
        $postsFound = $st->fetchAll();
    } catch (PDOException $e) {
        $postsFound = [];
    }
    try {
        $st = $pdo->prepare("SELECT title, slug, excerpt FROM site_pages WHERE status = 'publish' AND (title LIKE ? OR content LIKE ? OR excerpt LIKE ?) ORDER BY sort_order ASC LIMIT 20");
        $st->execute([$qLike, $qLike, $qLike]);
        $pagesFound = $st->fetchAll();
    } catch (PDOException $e) {
        $pagesFound = [];
    }
    try {
        $st = $pdo->prepare("SELECT name, title, interests FROM staff WHERE status = 'publish' AND (name LIKE ? OR title LIKE ? OR interests LIKE ?) ORDER BY sort_order ASC LIMIT 12");
        $st->execute([$qLike, $qLike, $qLike]);
        $staffFound = $st->fetchAll();
    } catch (PDOException $e) {
        $staffFound = [];
    }
    try {
        $st = $pdo->prepare("SELECT question FROM faqs WHERE status = 'publish' AND (question LIKE ? OR answer LIKE ?) ORDER BY sort_order ASC LIMIT 12");
        $st->execute([$qLike, $qLike]);
        $faqFound = $st->fetchAll();
    } catch (PDOException $e) {
        $faqFound = [];
    }
    try {
        $st = $pdo->prepare("SELECT title, slug, excerpt FROM services WHERE status = 'publish' AND (title LIKE ? OR excerpt LIKE ? OR content LIKE ?) ORDER BY sort_order ASC LIMIT 12");
        $st->execute([$qLike, $qLike, $qLike]);
        $svcFound = $st->fetchAll();
    } catch (PDOException $e) {
        $svcFound = [];
    }
}
$total = count($postsFound) + count($pagesFound) + count($staffFound) + count($faqFound) + count($svcFound);
$docTitle = ($q !== '' ? ('Arama: ' . $q) : 'Site içi arama') . ' — ' . $siteTitle;
require __DIR__ . '/site/header.php';
?>
<article class="max-w-3xl">
    <p class="text-xs uppercase tracking-wide text-slate-400">Arama</p>
    <h1 class="mt-2 text-3xl font-bold text-slate-900">Site içi arama</h1>
    <form method="get" action="<?= e(search_permalink()) ?>" class="mt-6 relative" data-suggest="<?= e(search_permalink() . '?suggest=1') ?>">
        <div class="flex gap-2">
            <input name="q" value="<?= e($q) ?>" placeholder="Yazı, sayfa, hizmet, SSS veya personel ara…" class="flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-sm shadow-sm" autocomplete="off" data-hsearch-input>
            <button class="rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white" type="submit">Ara</button>
        </div>
        <div class="cms-suggest !right-auto left-0 mt-2" hidden data-suggest-box></div>
    </form>
    <?php if ($q === ''): ?>
        <p class="mt-8 text-slate-500">Bir kelime yazın; yazı, sayfa, SSS ve ekip içinde arar.</p>
    <?php else: ?>
        <p class="mt-6 text-sm text-slate-500"><?= (int) $total ?> sonuç — “<?= e($q) ?>”</p>
        <?php if ($pagesFound): ?>
            <h2 class="mt-8 text-lg font-semibold">Sayfalar</h2>
            <ul class="mt-3 space-y-2">
                <?php foreach ($pagesFound as $row): ?>
                    <li class="cms-search-hit rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <a class="text-sky-700 hover:underline font-medium" href="<?= e(page_permalink($row['slug'])) ?>"><?= e((string) $row['title']) ?></a>
                        <?php if (!empty($row['excerpt'])): ?><p class="mt-1 text-sm text-slate-500"><?= e((string) $row['excerpt']) ?></p><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if ($postsFound): ?>
            <h2 class="mt-8 text-lg font-semibold">Yazılar</h2>
            <ul class="mt-3 space-y-2">
                <?php foreach ($postsFound as $row): ?>
                    <li class="cms-search-hit rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <a class="text-sky-700 hover:underline font-medium" href="<?= e(post_permalink($row['slug'])) ?>"><?= e((string) $row['title']) ?></a>
                        <?php if (!empty($row['excerpt'])): ?><p class="mt-1 text-sm text-slate-500"><?= e((string) $row['excerpt']) ?></p><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if ($svcFound): ?>
            <h2 class="mt-8 text-lg font-semibold">Hizmetler</h2>
            <ul class="mt-3 space-y-2">
                <?php foreach ($svcFound as $row): ?>
                    <li class="cms-search-hit rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <a class="text-sky-700 hover:underline font-medium" href="<?= e(service_permalink($row['slug'])) ?>"><?= e((string) $row['title']) ?></a>
                        <?php if (!empty($row['excerpt'])): ?><p class="mt-1 text-sm text-slate-500"><?= e((string) $row['excerpt']) ?></p><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if ($faqFound): ?>
            <h2 class="mt-8 text-lg font-semibold">SSS</h2>
            <ul class="mt-3 space-y-2">
                <?php foreach ($faqFound as $row): ?>
                    <li class="cms-search-hit rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <a class="text-sky-700 hover:underline font-medium" href="<?= e(faq_permalink()) ?>"><?= e((string) $row['question']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if ($staffFound): ?>
            <h2 class="mt-8 text-lg font-semibold">Personel</h2>
            <ul class="mt-3 space-y-2">
                <?php foreach ($staffFound as $row): ?>
                    <li class="cms-search-hit rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <a class="text-sky-700 hover:underline font-medium" href="<?= e(staff_list_permalink()) ?>"><?= e((string) $row['name']) ?></a>
                        <span class="text-sm text-slate-500"><?= e((string) ($row['title'] ?? '')) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if ($total === 0): ?>
            <p class="mt-6 text-slate-500">Sonuç bulunamadı. Farklı bir kelime deneyin.</p>
        <?php endif; ?>
    <?php endif; ?>
</article>
<?php require __DIR__ . '/site/footer.php'; ?>
