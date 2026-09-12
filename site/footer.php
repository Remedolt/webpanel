<?php
declare(strict_types=1);
if (!isset($siteTitle)) {
    exit;
}
?>
</main>
<?php
$footerType = isset($footerType) ? $footerType : 'simple';
$footerSize = isset($footerSize) ? $footerSize : 'md';
$footerBg = isset($footerBg) ? $footerBg : '#0f172a';
$footerText = isset($footerText) ? $footerText : '#94a3b8';
$footerCustom = isset($footerCustom) ? $footerCustom : '';
$footerShowPages = isset($footerShowPages) ? $footerShowPages : '0';
$fPad = $footerSize === 'lg' ? '48px 0' : ($footerSize === 'sm' ? '24px 0' : '36px 0');
$fFont = $footerSize === 'lg' ? '15px' : ($footerSize === 'sm' ? '12px' : '14px');
$footerParents = isset($footerMenuTree[0]) ? $footerMenuTree[0] : array();
$copy = $footerCustom !== '' ? $footerCustom : ('© ' . date('Y') . ' ' . $siteTitle);
$headerType = isset($headerType) ? $headerType : 'top';
$brandBtn = isset($cmsBrand['primary']) ? $cmsBrand['primary'] : ($headerAccent ?? '#38bdf8');
$brandInk = isset($cmsBrand['ink']) ? $cmsBrand['ink'] : cms_on_color($brandBtn);
$showNewsletter = option_get($pdo, 'newsletter_show', '1') === '1';
$footerSocials = cms_social_links($pdo);
?>
<footer style="background:<?= e($footerBg) ?>;color:<?= e($footerText) ?>;font-size:<?= e($fFont) ?>;padding:<?= e($fPad) ?>">
    <div class="max-w-5xl mx-auto px-4">
        <?php if ($footerType === 'cta'): ?>
            <div class="rounded-xl px-6 py-8 mb-8 text-center" style="background:rgba(255,255,255,.06)">
                <p class="text-xl font-semibold" style="color:#fff"><?= e($siteTitle) ?></p>
                <p class="mt-2 opacity-90"><?= e($copy) ?></p>
                <a href="<?= e(public_url()) ?>" class="inline-flex mt-4 rounded-md px-4 py-2 text-sm font-semibold" style="background:<?= e($brandBtn) ?>;color:<?= e($brandInk) ?>">Ana sayfa</a>
            </div>
            <?php if ($footerParents || $footerShowPages === '1'): ?>
                <nav class="flex flex-wrap justify-center gap-4">
                    <?php foreach ($footerParents as $item): ?>
                        <a class="hover:opacity-80" href="<?= e((string) $item['url']) ?>"><?= e((string) $item['title']) ?></a>
                    <?php endforeach; ?>
                    <?php if ($footerShowPages === '1'): ?>
                        <?php foreach ($navPages as $nav): ?>
                            <a class="hover:opacity-80" href="<?= e(page_permalink($nav['slug'])) ?>"><?= e((string) $nav['title']) ?></a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php elseif ($footerType === 'stacked'): ?>
            <div class="text-center space-y-4">
                <p class="text-lg font-semibold" style="color:#fff"><?= e($siteTitle) ?></p>
                <?php if ($footerParents): ?>
                    <nav class="flex flex-wrap justify-center gap-4">
                        <?php foreach ($footerParents as $item): ?>
                            <a class="hover:opacity-80" href="<?= e((string) $item['url']) ?>"><?= e((string) $item['title']) ?></a>
                        <?php endforeach; ?>
                    </nav>
                <?php endif; ?>
                <?php if ($footerShowPages === '1' && $navPages): ?>
                    <nav class="flex flex-wrap justify-center gap-3 text-sm opacity-80">
                        <?php foreach ($navPages as $nav): ?>
                            <a href="<?= e(page_permalink($nav['slug'])) ?>"><?= e((string) $nav['title']) ?></a>
                        <?php endforeach; ?>
                    </nav>
                <?php endif; ?>
                <p class="text-sm opacity-80"><?= e($copy) ?></p>
            </div>
        <?php elseif ($footerType === 'mega'): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8">
                <div>
                    <p class="font-semibold mb-3" style="color:#fff"><?= e($siteTitle) ?></p>
                    <p><?= e($copy) ?></p>
                </div>
                <?php if ($footerShowPages === '1' && $navPages): ?>
                    <div>
                        <p class="font-semibold mb-3" style="color:#fff">Sayfalar</p>
                        <?php foreach ($navPages as $nav): ?>
                            <a class="block py-1 hover:opacity-80" href="<?= e(page_permalink($nav['slug'])) ?>"><?= e((string) $nav['title']) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php foreach ($footerParents as $item): ?>
                    <div>
                        <a class="font-semibold block mb-2" style="color:#fff" href="<?= e((string) $item['url']) ?>"><?= e((string) $item['title']) ?></a>
                        <?php if (!empty($footerMenuTree[(int) $item['id']])): ?>
                            <?php foreach ($footerMenuTree[(int) $item['id']] as $child): ?>
                                <a class="block py-1 opacity-80 hover:opacity-100" href="<?= e((string) $child['url']) ?>"><?= e((string) $child['title']) ?></a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($footerType === 'centered'): ?>
            <div class="text-center space-y-4">
                <p><?= e($copy) ?></p>
                <?php if ($footerParents || $footerShowPages === '1'): ?>
                    <nav class="flex flex-wrap justify-center gap-4">
                        <?php foreach ($footerParents as $item): ?>
                            <a class="hover:opacity-80" href="<?= e((string) $item['url']) ?>"><?= e((string) $item['title']) ?></a>
                        <?php endforeach; ?>
                        <?php if ($footerShowPages === '1'): ?>
                            <?php foreach ($navPages as $nav): ?>
                                <a class="hover:opacity-80" href="<?= e(page_permalink($nav['slug'])) ?>"><?= e((string) $nav['title']) ?></a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </div>
        <?php elseif ($footerType === 'columns'): ?>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div>
                    <p class="font-semibold mb-3" style="color:#fff"><?= e($siteTitle) ?></p>
                    <p><?= e($copy) ?></p>
                </div>
                <?php foreach ($footerParents as $item): ?>
                    <div>
                        <a class="font-semibold block mb-2" href="<?= e((string) $item['url']) ?>"><?= e((string) $item['title']) ?></a>
                        <?php if (!empty($footerMenuTree[(int) $item['id']])): ?>
                            <?php foreach ($footerMenuTree[(int) $item['id']] as $child): ?>
                                <a class="block py-1 opacity-80 hover:opacity-100" href="<?= e((string) $child['url']) ?>"><?= e((string) $child['title']) ?></a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if ($footerShowPages === '1' && $navPages): ?>
                    <div>
                        <p class="font-semibold mb-2" style="color:#fff">Sayfalar</p>
                        <?php foreach ($navPages as $nav): ?>
                            <a class="block py-1 opacity-80 hover:opacity-100" href="<?= e(page_permalink($nav['slug'])) ?>"><?= e((string) $nav['title']) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                <p><?= e($copy) ?></p>
                <?php if ($footerParents || $footerShowPages === '1'): ?>
                    <nav class="flex flex-wrap gap-4">
                        <?php foreach ($footerParents as $item): ?>
                            <a class="hover:opacity-80" href="<?= e((string) $item['url']) ?>"><?= e((string) $item['title']) ?></a>
                        <?php endforeach; ?>
                        <?php if ($footerShowPages === '1'): ?>
                            <?php foreach ($navPages as $nav): ?>
                                <a class="hover:opacity-80" href="<?= e(page_permalink($nav['slug'])) ?>"><?= e((string) $nav['title']) ?></a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($showNewsletter || $footerSocials): ?>
            <div class="cms-footer-meta">
                <?php if ($showNewsletter): ?>
                    <div class="cms-footer-news">
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="subscribe">
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide" style="color:#fff"><?= e(option_get($pdo, 'newsletter_heading', 'Bülten')) ?></label>
                                <input type="email" name="email" required placeholder="E-posta adresiniz" class="w-full rounded-md border border-white/15 bg-white/10 px-3 py-2 text-sm text-white placeholder:text-white/50">
                            </div>
                            <button type="submit" class="rounded-md px-4 py-2 text-sm font-semibold" style="background:<?= e($brandBtn) ?>;color:<?= e($brandInk) ?>">Kaydol</button>
                        </form>
                        <?php if (option_get($pdo, 'newsletter_text', '') !== ''): ?>
                            <p class="hint"><?= e(option_get($pdo, 'newsletter_text', '')) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php
                if ($footerSocials) {
                    cms_footer_follow($pdo);
                }
                ?>
            </div>
        <?php endif; ?>
    </div>
</footer>
<?php if ($headerType === 'left' || $headerType === 'right'): ?>
</div>
</div>
<?php endif; ?>
<?php if (!empty($sitePopup) && is_array($sitePopup)): ?>
<div id="site-popup" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
        <h3 class="text-lg font-semibold text-slate-900"><?= e((string) $sitePopup['title']) ?></h3>
        <p class="mt-3 text-sm text-slate-600"><?= e((string) $sitePopup['content']) ?></p>
        <div class="mt-5 flex items-center justify-end gap-3">
            <button type="button" id="popup-close" class="text-sm text-slate-500 hover:text-slate-800">Kapat</button>
            <?php if (!empty($sitePopup['button_text'])): ?>
                <a href="<?= e((string) ($sitePopup['button_url'] ?: '#')) ?>" class="rounded-md bg-sky-500 px-4 py-2 text-sm font-semibold text-slate-900"><?= e((string) $sitePopup['button_text']) ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
(function () {
    var box = document.getElementById('site-popup');
    var closeBtn = document.getElementById('popup-close');
    var delay = <?= (int) ($sitePopup['delay_seconds'] ?? 2) ?> * 1000;
    if (!box) return;
    if (window.sessionStorage && sessionStorage.getItem('kodcu_popup') === '1') return;
    setTimeout(function () {
        box.classList.remove('hidden');
    }, delay);
    function hide() {
        box.classList.add('hidden');
        if (window.sessionStorage) sessionStorage.setItem('kodcu_popup', '1');
    }
    if (closeBtn) closeBtn.addEventListener('click', hide);
    box.addEventListener('click', function (ev) { if (ev.target === box) hide(); });
})();
</script>
<?php endif; ?>
<?php if (!empty($homeSlides) && count($homeSlides) > 1): ?>
<script>
(function () {
    var root = document.getElementById('site-slider');
    var slides = document.querySelectorAll('.site-slide');
    var dots = document.querySelectorAll('.slider-dot');
    var meta = document.querySelector('[data-slider-dots]');
    if (!root || slides.length < 2) return;
    var i = 0;
    var timer = null;
    var interval = meta ? (parseInt(meta.getAttribute('data-interval'), 10) || 6000) : 6000;
    var auto = !meta || meta.getAttribute('data-autoplay') !== '0';
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function show(n) {
        i = (n + slides.length) % slides.length;
        slides.forEach(function (el, idx) {
            el.classList.toggle('is-on', idx === i);
            var frame = el.querySelector('iframe');
            if (frame && frame.dataset.src) {
                if (idx === i) frame.src = frame.dataset.src;
                else frame.src = '';
            }
        });
        dots.forEach(function (dot, idx) {
            dot.classList.toggle('bg-white', idx === i);
            dot.classList.toggle('bg-white/40', idx !== i);
        });
    }
    function stop() {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    }
    function start() {
        stop();
        if (!auto || reduce) return;
        timer = setInterval(function () { show(i + 1); }, interval);
    }
    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            show(parseInt(dot.getAttribute('data-slide'), 10) || 0);
            start();
        });
    });
    var prev = document.querySelector('[data-slide-prev]');
    var next = document.querySelector('[data-slide-next]');
    if (prev) prev.addEventListener('click', function () { show(i - 1); start(); });
    if (next) next.addEventListener('click', function () { show(i + 1); start(); });
    document.addEventListener('keydown', function (ev) {
        var tag = ((ev.target && ev.target.tagName) || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select' || (ev.target && ev.target.isContentEditable)) return;
        if (ev.key === 'ArrowLeft') { show(i - 1); start(); }
        if (ev.key === 'ArrowRight') { show(i + 1); start(); }
    });
    var section = root.closest('section');
    if (section) {
        section.addEventListener('mouseenter', stop);
        section.addEventListener('mouseleave', start);
    }
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) stop();
        else start();
    });
    start();
})();
</script>
<?php endif; ?>
<script>
(function () {
    document.querySelectorAll('[data-hsearch], form[data-suggest]').forEach(function (form) {
        var input = form.querySelector('[data-hsearch-input]') || form.querySelector('input[name="q"]');
        var btn = form.querySelector('[data-hsearch-toggle]');
        var box = form.querySelector('[data-suggest-box]');
        var url = form.getAttribute('data-suggest') || '';
        var compact = form.hasAttribute('data-hsearch');
        if (!input) return;
        var t = null;
        var items = [];
        var active = -1;
        function open() {
            form.classList.add('is-on');
            setTimeout(function () { input.focus(); }, 80);
        }
        function close() {
            if (!compact) {
                hideSuggest();
                return;
            }
            if (input.value.trim() !== '') return;
            form.classList.remove('is-on');
            hideSuggest();
        }
        function hideSuggest() {
            if (!box) return;
            box.hidden = true;
            box.classList.remove('is-on');
            box.innerHTML = '';
            items = [];
            active = -1;
        }
        function render() {
            if (!box) return;
            if (!items.length) {
                hideSuggest();
                return;
            }
            box.hidden = false;
            box.classList.add('is-on');
            box.innerHTML = '';
            items.forEach(function (row, idx) {
                var a = document.createElement('a');
                a.href = row.url || '#';
                a.setAttribute('data-i', String(idx));
                if (idx === active) a.className = 'is-on';
                var t = document.createElement('span');
                t.className = 't';
                t.textContent = row.title || '';
                var k = document.createElement('span');
                k.className = 'k';
                k.textContent = row.kind || '';
                a.appendChild(t);
                a.appendChild(k);
                box.appendChild(a);
            });
        }
        function lookup() {
            var q = input.value.trim();
            if (!url || q.length < 2) {
                hideSuggest();
                return;
            }
            fetch(url + (url.indexOf('?') === -1 ? '?' : '&') + 'q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    items = (data && data.items) ? data.items : [];
                    active = -1;
                    render();
                })
                .catch(function () { hideSuggest(); });
        }
        if (btn) {
            btn.addEventListener('click', function (ev) {
                if (compact && !form.classList.contains('is-on')) {
                    ev.preventDefault();
                    open();
                    return;
                }
                if (input.value.trim() === '') {
                    ev.preventDefault();
                    input.focus();
                }
            });
        }
        input.addEventListener('input', function () {
            if (t) clearTimeout(t);
            t = setTimeout(lookup, 180);
        });
        input.addEventListener('keydown', function (ev) {
            if (!items.length) return;
            if (ev.key === 'ArrowDown') {
                ev.preventDefault();
                active = (active + 1) % items.length;
                render();
            } else if (ev.key === 'ArrowUp') {
                ev.preventDefault();
                active = (active - 1 + items.length) % items.length;
                render();
            } else if (ev.key === 'Enter' && active >= 0 && items[active]) {
                ev.preventDefault();
                window.location.href = items[active].url;
            } else if (ev.key === 'Escape') {
                hideSuggest();
            }
        });
        document.addEventListener('click', function (ev) {
            if (!form.contains(ev.target)) close();
        });
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape') close();
        });
    });
})();
</script>
<script>
(function () {
    var root = document.getElementById('mobile-menu');
    var openBtn = document.getElementById('mobile-menu-open');
    var closeBtn = document.getElementById('mobile-menu-close');
    if (!root) return;
    function show() {
        root.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
    function hide() {
        root.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
    if (openBtn) openBtn.addEventListener('click', show);
    if (closeBtn) closeBtn.addEventListener('click', hide);
    root.addEventListener('click', function (ev) {
        if (ev.target && ev.target.getAttribute && ev.target.getAttribute('data-close-mobile')) hide();
    });
})();
</script>
<?php
$floatButtons = isset($floatButtons) && is_array($floatButtons) ? $floatButtons : array();
$visibleFloat = array();
foreach ($floatButtons as $fb) {
    $href = float_button_href($fb['icon_type'] ?? 'phone', $fb['url'] ?? '');
    if ($href === '') {
        continue;
    }
    $fb['_href'] = $href;
    $visibleFloat[] = $fb;
}
if ($visibleFloat):
?>
<style>
.cms-float{position:fixed;z-index:50;right:16px;bottom:16px;display:flex;flex-direction:column;gap:12px}
.cms-float a{width:56px;height:56px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;color:#fff;box-shadow:0 10px 24px rgba(0,0,0,.28);overflow:hidden}
.cms-float svg{display:block;width:26px;height:26px;flex-shrink:0}
</style>
<div class="cms-float">
    <?php foreach ($visibleFloat as $fb): ?>
        <a href="<?= e($fb['_href']) ?>" <?= in_array((string) ($fb['icon_type'] ?? ''), ['phone', 'email'], true) ? '' : 'target="_blank" rel="noopener noreferrer"' ?> style="background:<?= e(hex_color($fb['color'] ?? '#2563eb', '#2563eb')) ?>" aria-label="<?= e((string) $fb['label']) ?>" title="<?= e((string) $fb['label']) ?>">
            <?= cms_float_icon((string) ($fb['icon_type'] ?? 'phone')) ?>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<button type="button" id="cms-top" class="cms-top" aria-label="Yukarı">↑</button>
<?php if (option_get($pdo, 'cookie_show', '1') === '1'): ?>
<div id="cms-cookie" class="cms-cookie" role="dialog" aria-label="Çerez">
    <p class="text-sm"><?= e(option_get($pdo, 'cookie_text', 'Bu sitede deneyiminiz için çerez kullanılır.')) ?>
        <?php $cookiePolicy = trim(option_get($pdo, 'cookie_policy_url', '')); ?>
        <?php if ($cookiePolicy !== ''): ?>
            <a class="underline text-sky-300" href="<?= e($cookiePolicy) ?>">Gizlilik</a>
        <?php endif; ?>
    </p>
    <button type="button" id="cms-cookie-ok" class="shrink-0 rounded-md bg-sky-500 px-3 py-1.5 text-sm font-semibold text-slate-900">Tamam</button>
</div>
<?php endif; ?>
<script>
(function () {
    var btn = document.getElementById('cms-top');
    if (!btn) return;
    window.addEventListener('scroll', function () {
        btn.classList.toggle('is-on', window.scrollY > 400);
    });
    btn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
})();
(function () {
    var box = document.getElementById('cms-cookie');
    var ok = document.getElementById('cms-cookie-ok');
    if (!box) return;
    if (window.localStorage && localStorage.getItem('kodcu_cookie') === '1') return;
    box.classList.add('is-on');
    if (ok) ok.addEventListener('click', function () {
        box.classList.remove('is-on');
        if (window.localStorage) localStorage.setItem('kodcu_cookie', '1');
    });
})();
(function () {
    function msg(el) {
        if (!el.validity || el.validity.valid) return '';
        if (el.validity.valueMissing) return 'Bu alanı doldurun.';
        if (el.validity.typeMismatch) {
            if (el.type === 'email') return 'Geçerli bir e-posta girin.';
            if (el.type === 'url') return 'Geçerli bir adres girin.';
            return 'Geçerli bir değer girin.';
        }
        if (el.validity.tooShort) return 'Daha uzun bir değer girin.';
        if (el.validity.tooLong) return 'Daha kısa bir değer girin.';
        if (el.validity.rangeUnderflow || el.validity.rangeOverflow || el.validity.stepMismatch) return 'Geçerli bir sayı girin.';
        if (el.validity.patternMismatch) return 'İstenen biçime uygun girin.';
        if (el.validity.badInput) return 'Geçerli bir değer girin.';
        return 'Bu alanı kontrol edin.';
    }
    document.addEventListener('invalid', function (e) {
        var el = e.target;
        if (!el || !el.setCustomValidity) return;
        el.setCustomValidity(msg(el));
    }, true);
    function clear(e) {
        if (e.target && e.target.setCustomValidity) e.target.setCustomValidity('');
    }
    document.addEventListener('input', clear, true);
    document.addEventListener('change', clear, true);
})();
(function () {
    var root = document.querySelector('main');
    if (!root) return;
    var imgs = root.querySelectorAll('img:not([loading])');
    for (var i = 0; i < imgs.length; i++) {
        imgs[i].setAttribute('loading', 'lazy');
        imgs[i].setAttribute('decoding', 'async');
    }
})();
(function () {
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function turn(sel) {
        var els = document.querySelectorAll(sel);
        if (!els.length) return;
        if (reduce || !('IntersectionObserver' in window)) {
            for (var i = 0; i < els.length; i++) els[i].classList.add('is-on');
            return;
        }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-on');
                io.unobserve(entry.target);
            });
        }, { threshold: 0.18 });
        for (var j = 0; j < els.length; j++) io.observe(els[j]);
    }
    turn('.cms-svc');
    turn('.cms-team');
    turn('.cms-reveal');
})();
</script>
<?php
$analyticsBody = option_get($pdo, 'analytics_body', '');
if ($analyticsBody !== '') {
    echo $analyticsBody . "\n";
}
?>
</body>
</html>
