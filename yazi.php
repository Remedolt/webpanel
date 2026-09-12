<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '') {
    header('Location: ' . posts_list_permalink());
    exit;
}
$req = (string) ($_SERVER['REQUEST_URI'] ?? '');
if (strpos($req, 'yazi.php') !== false) {
    header('Location: ' . post_permalink($slug), true, 301);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT p.id, p.title, p.content, p.excerpt, p.featured_image, p.created_at, p.views, u.display_name
     FROM posts p
     INNER JOIN users u ON u.id = p.author_id
     WHERE p.slug = ? AND p.status = 'publish'
     LIMIT 1"
);
$stmt->execute([$slug]);
$post = $stmt->fetch();
if (!$post) {
    http_response_code(404);
    $docTitle = 'Yazı bulunamadı — ' . $siteTitle;
    require __DIR__ . '/site/header.php';
    echo '<h1 class="text-2xl font-bold">Yazı bulunamadı</h1><p class="mt-3 text-slate-600">Bu yazı yayımlanmamış veya silinmiş olabilir.</p><p class="mt-4"><a class="text-sky-700 hover:underline" href="' . e(posts_list_permalink()) . '">Tüm yazılar</a></p>';
    require __DIR__ . '/site/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'comment') {
    csrf_verify();
    $name = trim((string) ($_POST['author_name'] ?? ''));
    $content = trim((string) ($_POST['comment_content'] ?? ''));
    if ($name !== '' && $content !== '') {
        $ins = $pdo->prepare('INSERT INTO comments (post_id, author_name, content, status) VALUES (?, ?, ?, ?)');
        $ins->execute([(int) $post['id'], $name, $content, 'pending']);
        flash_set('success', 'Yorumunuz alındı, onay sonrası yayınlanır.');
    } else {
        flash_set('error', 'Ad ve yorum zorunludur.');
    }
    header('Location: ' . post_permalink($slug));
    exit;
}

$upd = $pdo->prepare('UPDATE posts SET views = views + 1 WHERE id = ?');
$upd->execute([(int) $post['id']]);

$commentStmt = $pdo->prepare("SELECT author_name, content, created_at FROM comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at ASC");
$commentStmt->execute([(int) $post['id']]);
$approvedComments = $commentStmt->fetchAll();
$commentFlash = flash_get();

$postCats = [];
try {
    $catSt = $pdo->prepare(
        "SELECT c.name, c.slug FROM categories c INNER JOIN post_categories pc ON pc.category_id = c.id WHERE pc.post_id = ? ORDER BY c.name ASC"
    );
    $catSt->execute([(int) $post['id']]);
    $postCats = $catSt->fetchAll();
} catch (PDOException $e) {
    $postCats = [];
}

$related = [];
try {
    $rel = $pdo->prepare(
        "SELECT DISTINCT p.title, p.slug, p.excerpt, p.featured_image
         FROM posts p
         INNER JOIN post_categories pc ON pc.post_id = p.id
         WHERE p.status = 'publish' AND p.id != ? AND pc.category_id IN (SELECT category_id FROM post_categories WHERE post_id = ?)
         ORDER BY p.created_at DESC
         LIMIT 3"
    );
    $rel->execute([(int) $post['id'], (int) $post['id']]);
    $related = $rel->fetchAll();
} catch (PDOException $e) {
    $related = [];
}
if (!$related) {
    try {
        $rel = $pdo->prepare(
            "SELECT title, slug, excerpt, featured_image FROM posts WHERE status = 'publish' AND id != ? ORDER BY created_at DESC LIMIT 3"
        );
        $rel->execute([(int) $post['id']]);
        $related = $rel->fetchAll();
    } catch (PDOException $e) {
        $related = [];
    }
}

$readMin = reading_minutes((string) $post['content']);
$shareUrl = post_permalink($post['slug']);
$shareText = (string) $post['title'];
$tocPack = cms_with_toc(public_html((string) $post['content']));
$postHtml = (string) $tocPack['html'];
$postToc = is_array($tocPack['toc'] ?? null) ? $tocPack['toc'] : array();
$docTitle = (string) $post['title'] . ' — ' . $siteTitle;
$ogType = 'article';
if (!empty($post['featured_image'])) {
    $ogImage = media_src($post['featured_image']);
}
if (!empty($post['excerpt'])) {
    $seoDescription = (string) $post['excerpt'];
}
require __DIR__ . '/site/header.php';
?>
<div class="cms-read" id="cms-read"></div>
<article class="max-w-3xl" id="cms-article">
    <?php cms_crumbs(array(
        array('label' => 'Ana sayfa', 'url' => public_url()),
        array('label' => 'Yazılar', 'url' => posts_list_permalink()),
        array('label' => (string) $post['title'], 'url' => ''),
    )); ?>
    <a href="<?= e(posts_list_permalink()) ?>" class="text-sm text-sky-700 hover:underline">← Tüm yazılar</a>
    <p class="mt-4 text-sm text-slate-400"><?= e(format_datetime((string) $post['created_at'])) ?> · <?= e((string) $post['display_name']) ?> · <?= (int) $readMin ?> dk okuma</p>
    <h1 class="mt-2 text-4xl font-bold text-slate-900"><?= e((string) $post['title']) ?></h1>
    <?php if ($postCats): ?>
        <div class="mt-3 flex flex-wrap gap-2">
            <?php foreach ($postCats as $cat): ?>
                <a class="rounded-full border border-slate-200 bg-white px-3 py-0.5 text-xs text-slate-600 hover:border-sky-300" href="<?= e(category_permalink($cat['slug'])) ?>"><?= e((string) $cat['name']) ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($post['featured_image'])): ?>
        <img src="<?= e(media_src($post['featured_image'])) ?>" alt="" class="mt-6 w-full rounded-xl border border-slate-200 object-cover max-h-[420px]">
    <?php endif; ?>
    <div class="mt-8 flex flex-wrap gap-2 text-sm">
        <a class="rounded-md border border-slate-200 bg-white px-3 py-1.5 hover:border-sky-300" href="https://wa.me/?text=<?= e(rawurlencode($shareText . ' ' . $shareUrl)) ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a>
        <a class="rounded-md border border-slate-200 bg-white px-3 py-1.5 hover:border-sky-300" href="https://twitter.com/intent/tweet?text=<?= e(rawurlencode($shareText)) ?>&amp;url=<?= e(rawurlencode($shareUrl)) ?>" target="_blank" rel="noopener noreferrer">X</a>
        <button type="button" class="rounded-md border border-slate-200 bg-white px-3 py-1.5" id="cms-copy-link" data-url="<?= e($shareUrl) ?>">Linki kopyala</button>
    </div>
    <?php if (count($postToc) >= 2): ?>
        <nav class="cms-toc" aria-label="İçindekiler">
            <p>Bu yazıda</p>
            <?php foreach ($postToc as $item): ?>
                <a class="<?= ((int) $item['level'] === 3) ? 'is-h3' : '' ?>" href="#<?= e((string) $item['id']) ?>"><?= e((string) $item['text']) ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>
    <div class="mt-8 prose prose-slate max-w-none leading-7 space-y-4">
        <?= $postHtml ?>
    </div>
</article>
<?php
$articleLd = array(
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => (string) $post['title'],
    'datePublished' => date('c', strtotime((string) $post['created_at'])),
    'author' => array('@type' => 'Person', 'name' => (string) $post['display_name']),
    'mainEntityOfPage' => $shareUrl,
);
if (!empty($post['excerpt'])) {
    $articleLd['description'] = (string) $post['excerpt'];
}
if (!empty($post['featured_image'])) {
    $articleLd['image'] = media_src($post['featured_image']);
}
?>
<script type="application/ld+json"><?= json_encode($articleLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

<?php if ($related): ?>
<section class="max-w-3xl mt-12">
    <h2 class="text-xl font-semibold text-slate-900">Benzer yazılar</h2>
    <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
        <?php foreach ($related as $item): ?>
            <a href="<?= e(post_permalink($item['slug'])) ?>" class="rounded-xl border border-slate-200 bg-white overflow-hidden hover:border-sky-300">
                <?php if (!empty($item['featured_image'])): ?>
                    <img src="<?= e(media_src($item['featured_image'])) ?>" alt="" class="h-28 w-full object-cover bg-slate-100">
                <?php endif; ?>
                <div class="p-3">
                    <p class="text-sm font-semibold text-slate-900"><?= e((string) $item['title']) ?></p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="max-w-3xl mt-12 border-t border-slate-200 pt-8">
    <h2 class="text-xl font-semibold text-slate-900">Yorumlar</h2>
    <?php if (!empty($commentFlash)): ?>
        <div class="mt-4 rounded-md border px-3 py-2 text-sm <?= (($commentFlash['type'] ?? '') === 'error') ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-800' ?>">
            <?= e((string) $commentFlash['message']) ?>
        </div>
    <?php endif; ?>
    <div class="mt-4 space-y-4">
        <?php if (!$approvedComments): ?>
            <p class="text-sm text-slate-500">Henüz onaylanmış yorum yok.</p>
        <?php else: ?>
            <?php foreach ($approvedComments as $c): ?>
                <div class="rounded-lg border border-slate-200 bg-white p-4">
                    <p class="text-sm font-semibold text-slate-900"><?= e((string) $c['author_name']) ?></p>
                    <p class="text-xs text-slate-400"><?= format_datetime((string) $c['created_at']) ?></p>
                    <p class="mt-2 text-sm text-slate-700"><?= e((string) $c['content']) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <form method="post" class="mt-6 space-y-3 bg-white border border-slate-200 rounded-lg p-4">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="comment">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Adınız</label>
            <input name="author_name" required class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Yorum</label>
            <textarea name="comment_content" rows="4" required class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"></textarea>
        </div>
        <button type="submit" class="rounded-md bg-sky-500 px-4 py-2 text-sm font-semibold text-slate-900">Gönder</button>
        <p class="text-xs text-slate-400">Yorumlar onaylandıktan sonra yayınlanır.</p>
    </form>
</section>
<script>
(function () {
    var bar = document.getElementById('cms-read');
    var art = document.getElementById('cms-article');
    if (!bar || !art) return;
    function tick() {
        var rect = art.getBoundingClientRect();
        var start = window.scrollY + rect.top;
        var end = start + art.offsetHeight - window.innerHeight;
        var p = 0;
        if (end > start) {
            p = Math.min(1, Math.max(0, (window.scrollY - start) / (end - start)));
        }
        bar.style.width = (p * 100) + '%';
    }
    window.addEventListener('scroll', tick, { passive: true });
    tick();
})();
(function () {
    var btn = document.getElementById('cms-copy-link');
    if (!btn) return;
    btn.addEventListener('click', function () {
        var url = btn.getAttribute('data-url') || '';
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(function () {
                btn.textContent = 'Kopyalandı';
                setTimeout(function () { btn.textContent = 'Linki kopyala'; }, 1800);
            });
            return;
        }
        window.prompt('Bağlantı', url);
    });
})();
</script>
<?php require __DIR__ . '/site/footer.php'; ?>
