<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '') {
    header('Location: ' . public_url());
    exit;
}

$stmt = $pdo->prepare(
    "SELECT p.id, p.title, p.slug, p.content, p.excerpt, p.featured_image, p.created_at, p.views, u.display_name
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
    $metaDescription = 'İstenen yazı bulunamadı.';
    require __DIR__ . '/site/header.php';
    echo '<h1 class="text-2xl font-bold">Yazı bulunamadı</h1><p class="mt-3 text-slate-600">Bu yazı yayımlanmamış veya silinmiş olabilir.</p>';
    require __DIR__ . '/site/footer.php';
    exit;
}

$viewedKey = 'viewed_post_' . (int) $post['id'];
if (empty($_SESSION[$viewedKey])) {
    $upd = $pdo->prepare('UPDATE posts SET views = views + 1 WHERE id = ?');
    $upd->execute([(int) $post['id']]);
    $_SESSION[$viewedKey] = 1;
    $post['views'] = (int) $post['views'] + 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'comment') {
    csrf_verify();
    $honeypot = trim((string) ($_POST['website'] ?? ''));
    $authorName = trim((string) ($_POST['author_name'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));
    $last = (int) ($_SESSION['last_comment_at'] ?? 0);
    $redirectTo = post_permalink($post['slug']) . '#yorumlar';

    if ($honeypot !== '') {
        flash_set('success', 'Yorumunuz alındı, onay sonrası yayınlanır.', 'flash_public');
        redirect($redirectTo);
    }
    if (time() - $last < 20) {
        flash_set('error', 'Lütfen yorum göndermeden önce kısa bir süre bekleyin.', 'flash_public');
        redirect($redirectTo);
    }
    if ($authorName === '' || $content === '') {
        flash_set('error', 'Ad ve yorum alanları zorunludur.', 'flash_public');
        redirect($redirectTo);
    }
    if (function_exists('mb_strlen')) {
        if (mb_strlen($authorName) > 80 || mb_strlen($content) > 2000) {
            flash_set('error', 'Yorum çok uzun.', 'flash_public');
            redirect($redirectTo);
        }
    } elseif (strlen($authorName) > 80 || strlen($content) > 2000) {
        flash_set('error', 'Yorum çok uzun.', 'flash_public');
        redirect($redirectTo);
    }

    $ins = $pdo->prepare(
        'INSERT INTO comments (post_id, author_name, content, status) VALUES (?, ?, ?, ?)'
    );
    $ins->execute([(int) $post['id'], $authorName, $content, 'pending']);
    $_SESSION['last_comment_at'] = time();
    flash_set('success', 'Yorumunuz alındı. Onaylandıktan sonra burada görünür.', 'flash_public');
    redirect($redirectTo);
}

$withCats = attach_post_categories($pdo, [$post]);
$post = $withCats[0];

$related = [];
if (!empty($post['categories'])) {
    $catIds = [];
    foreach ($post['categories'] as $cat) {
        $catIds[] = (int) $cat['id'];
    }
    $catIds = array_values(array_unique(array_filter($catIds)));
    if ($catIds) {
        $placeholders = implode(',', array_fill(0, count($catIds), '?'));
        $relStmt = $pdo->prepare(
            "SELECT DISTINCT p.id, p.title, p.slug, p.excerpt, p.featured_image, p.created_at, u.display_name
             FROM posts p
             INNER JOIN users u ON u.id = p.author_id
             INNER JOIN post_categories pc ON pc.post_id = p.id
             WHERE p.status = 'publish' AND p.id != ? AND pc.category_id IN ({$placeholders})
             ORDER BY p.created_at DESC
             LIMIT 3"
        );
        $relParams = array_merge([(int) $post['id']], $catIds);
        $relStmt->execute($relParams);
        $related = attach_post_categories($pdo, $relStmt->fetchAll());
    }
}
if (!$related) {
    $fallback = $pdo->prepare(
        "SELECT p.id, p.title, p.slug, p.excerpt, p.featured_image, p.created_at, u.display_name
         FROM posts p
         INNER JOIN users u ON u.id = p.author_id
         WHERE p.status = 'publish' AND p.id != ?
         ORDER BY p.created_at DESC
         LIMIT 3"
    );
    $fallback->execute([(int) $post['id']]);
    $related = attach_post_categories($pdo, $fallback->fetchAll());
}

$cmtStmt = $pdo->prepare(
    "SELECT author_name, content, created_at
     FROM comments
     WHERE post_id = ? AND status = 'approved'
     ORDER BY created_at ASC"
);
$cmtStmt->execute([(int) $post['id']]);
$comments = $cmtStmt->fetchAll();

$docTitle = (string) $post['title'] . ' — ' . $siteTitle;
$metaDescription = excerpt_plain((string) ($post['excerpt'] !== '' ? $post['excerpt'] : $post['content']), 160);
$canonicalUrl = post_permalink($post['slug']);
$ogImage = !empty($post['featured_image']) ? media_src($post['featured_image']) : '';
$isPostPage = true;
require __DIR__ . '/site/header.php';
?>
<div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_18rem] gap-8 items-start">
    <div>
        <article class="max-w-3xl">
            <a href="<?= e(public_url()) ?>" class="text-sm text-cyan-700 hover:underline">← Tüm yazılar</a>
            <?php if (!empty($post['categories'])): ?>
                <div class="mt-4 flex flex-wrap gap-2">
                    <?php foreach ($post['categories'] as $cat): ?>
                        <a href="<?= e(category_permalink($cat['slug'])) ?>" class="text-xs font-semibold uppercase tracking-wide text-cyan-700 hover:underline"><?= e((string) $cat['name']) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <p class="mt-3 text-sm text-slate-400">
                <?= e(format_datetime((string) $post['created_at'])) ?>
                · <?= e((string) $post['display_name']) ?>
                · <?= number_format((int) $post['views'], 0, ',', '.') ?> okunma
            </p>
            <h1 class="mt-2 text-4xl font-bold text-slate-900 leading-tight"><?= e((string) $post['title']) ?></h1>
            <?php if (!empty($post['featured_image'])): ?>
                <img src="<?= e(media_src($post['featured_image'])) ?>" alt="" class="mt-6 w-full rounded-xl border border-slate-200 object-cover max-h-[420px]">
            <?php endif; ?>
            <div class="mt-8 article-body text-slate-700 leading-7">
                <?= public_html((string) $post['content']) ?>
            </div>
        </article>

        <?php if ($related): ?>
            <section class="mt-12">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">İlgili yazılar</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php foreach ($related as $item): ?>
                        <a class="block bg-white border border-slate-200 rounded-xl p-4 hover:border-cyan-300" href="<?= e(post_permalink($item['slug'])) ?>">
                            <p class="text-xs text-slate-400"><?= e(format_datetime((string) $item['created_at'])) ?></p>
                            <p class="mt-1 font-medium text-slate-900"><?= e((string) $item['title']) ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section id="yorumlar" class="mt-12 max-w-3xl">
            <h2 class="text-lg font-semibold text-slate-900"><?= count($comments) ?> yorum</h2>
            <div class="mt-4 space-y-4">
                <?php if (!$comments): ?>
                    <p class="text-sm text-slate-500">Henüz onaylanmış yorum yok. İlk yorumu siz yazın.</p>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="bg-white border border-slate-200 rounded-xl p-4">
                            <p class="text-sm font-medium text-slate-900"><?= e((string) $comment['author_name']) ?></p>
                            <p class="text-xs text-slate-400 mt-0.5"><?= e(format_datetime((string) $comment['created_at'])) ?></p>
                            <p class="mt-2 text-sm text-slate-700 whitespace-pre-wrap"><?= e((string) $comment['content']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= e(post_permalink($post['slug'])) ?>#yorumlar" class="mt-6 bg-white border border-slate-200 rounded-xl p-5 space-y-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="comment">
                <div class="hidden" aria-hidden="true">
                    <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>
                <h3 class="text-sm font-semibold text-slate-900">Yorum yaz</h3>
                <p class="text-xs text-slate-500">Yorumlar onaylandıktan sonra yayınlanır.</p>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="author_name">Ad</label>
                    <input id="author_name" name="author_name" required maxlength="80"
                           class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="comment_content">Yorum</label>
                    <textarea id="comment_content" name="content" required maxlength="2000" rows="4"
                              class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"></textarea>
                </div>
                <button type="submit" class="rounded-md bg-ink px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Gönder</button>
            </form>
        </section>
    </div>
    <?php require __DIR__ . '/site/sidebar.php'; ?>
</div>
<?php require __DIR__ . '/site/footer.php'; ?>
