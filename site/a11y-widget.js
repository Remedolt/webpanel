(function () {
    var KEY = 'kodcu-a11y';
    var root = document.getElementById('a11y-root');
    if (!root) {
        return;
    }

    var defaults = {
        font: 0,
        contrast: false,
        gray: false,
        invert: false,
        links: false,
        readable: false,
        spacing: false,
        cursor: false,
        motion: false
    };
    var fontSteps = [100, 112, 125, 140];

    function loadState() {
        var state = {};
        var key;
        for (key in defaults) {
            if (Object.prototype.hasOwnProperty.call(defaults, key)) {
                state[key] = defaults[key];
            }
        }
        try {
            var raw = window.localStorage.getItem(KEY);
            if (!raw) {
                return state;
            }
            var saved = JSON.parse(raw);
            if (!saved || typeof saved !== 'object') {
                return state;
            }
            if (typeof saved.font === 'number' && saved.font >= 0 && saved.font < fontSteps.length) {
                state.font = saved.font;
            }
            ['contrast', 'gray', 'invert', 'links', 'readable', 'spacing', 'cursor', 'motion'].forEach(function (flag) {
                if (typeof saved[flag] === 'boolean') {
                    state[flag] = saved[flag];
                }
            });
        } catch (err) {
            return state;
        }
        return state;
    }

    var state = loadState();
    var open = false;

    function saveState() {
        try {
            window.localStorage.setItem(KEY, JSON.stringify(state));
        } catch (err) {
            /* ignore quota / private mode */
        }
    }

    function apply() {
        var html = document.documentElement;
        html.style.fontSize = fontSteps[state.font] + '%';
        html.classList.toggle('a11y-contrast', state.contrast);
        html.classList.toggle('a11y-gray', state.gray);
        html.classList.toggle('a11y-invert', state.invert);
        html.classList.toggle('a11y-links', state.links);
        html.classList.toggle('a11y-readable', state.readable);
        html.classList.toggle('a11y-spacing', state.spacing);
        html.classList.toggle('a11y-cursor', state.cursor);
        html.classList.toggle('a11y-motion', state.motion);
        syncButtons();
    }

    function syncButtons() {
        root.querySelectorAll('[data-a11y-toggle]').forEach(function (btn) {
            var flag = btn.getAttribute('data-a11y-toggle');
            var on = !!state[flag];
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            btn.classList.toggle('is-on', on);
        });
        var label = root.querySelector('[data-a11y-font-label]');
        if (label) {
            label.textContent = fontSteps[state.font] + '%';
        }
    }

    function setOpen(next) {
        open = next;
        var panel = root.querySelector('[data-a11y-panel]');
        var toggle = root.querySelector('[data-a11y-open]');
        if (!panel || !toggle) {
            return;
        }
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            var closeBtn = panel.querySelector('[data-a11y-close]');
            if (closeBtn) {
                closeBtn.focus();
            }
        } else {
            toggle.focus();
        }
    }

    function reset() {
        var key;
        for (key in defaults) {
            if (Object.prototype.hasOwnProperty.call(defaults, key)) {
                state[key] = defaults[key];
            }
        }
        saveState();
        apply();
    }

    if (!document.getElementById('a11y-widget-css')) {
        var css = document.createElement('style');
        css.id = 'a11y-widget-css';
        css.textContent = [
            '.skip-link{position:absolute;left:1rem;top:-4rem;z-index:80;background:#22d3ee;color:#071018;padding:.55rem 1rem;border-radius:.55rem;font-weight:700;font-size:.875rem}',
            '.skip-link:focus{top:1rem;outline:2px solid #fff;outline-offset:2px}',
            '#a11y-root{position:fixed;z-index:70;bottom:1.1rem;font-family:ui-sans-serif,system-ui,sans-serif}',
            '#a11y-root[data-a11y-position="left"]{left:1.1rem}',
            '#a11y-root[data-a11y-position="right"]{right:1.1rem}',
            '#a11y-root .a11y-fab{display:inline-flex;align-items:center;justify-content:center;width:3.4rem;height:3.4rem;border-radius:999px;background:#071018;color:#22d3ee;border:2px solid #22d3ee;box-shadow:0 10px 30px rgba(7,16,24,.35);cursor:pointer}',
            '#a11y-root .a11y-fab:focus{outline:3px solid #22d3ee;outline-offset:3px}',
            '#a11y-root .a11y-panel{position:absolute;bottom:4.2rem;width:19.5rem;max-height:min(34rem,calc(100vh - 6rem));overflow:auto;background:#fff;color:#0f172a;border:1px solid #e2e8f0;border-radius:1rem;box-shadow:0 18px 50px rgba(15,23,42,.2);padding:1rem}',
            '#a11y-root[data-a11y-position="left"] .a11y-panel{left:0}',
            '#a11y-root[data-a11y-position="right"] .a11y-panel{right:0}',
            '#a11y-root .a11y-head{display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:.85rem}',
            '#a11y-root .a11y-head h2{margin:0;font-size:1rem;font-weight:700}',
            '#a11y-root .a11y-close{border:0;background:#f1f5f9;color:#334155;border-radius:.55rem;width:2rem;height:2rem;cursor:pointer}',
            '#a11y-root .a11y-font{display:flex;align-items:center;gap:.4rem;margin-bottom:.85rem}',
            '#a11y-root .a11y-font button,#a11y-root .a11y-grid button{border:1px solid #e2e8f0;background:#f8fafc;color:#0f172a;border-radius:.7rem;cursor:pointer}',
            '#a11y-root .a11y-font button{flex:1;padding:.55rem .4rem;font-weight:700}',
            '#a11y-root .a11y-font [data-a11y-font-label]{min-width:3.2rem;text-align:center;font-size:.75rem;color:#64748b}',
            '#a11y-root .a11y-grid{display:grid;grid-template-columns:1fr 1fr;gap:.5rem}',
            '#a11y-root .a11y-grid button{display:flex;flex-direction:column;align-items:flex-start;gap:.2rem;padding:.7rem;text-align:left;font-size:.78rem;font-weight:600;min-height:4.1rem}',
            '#a11y-root .a11y-grid button span{font-size:.68rem;font-weight:400;color:#64748b}',
            '#a11y-root button.is-on{background:#ecfeff;border-color:#22d3ee;color:#155e75}',
            '#a11y-root .a11y-reset{margin-top:.75rem;width:100%;border:0;background:#071018;color:#fff;border-radius:.7rem;padding:.65rem;font-weight:600;cursor:pointer}',
            'html.a11y-contrast header,html.a11y-contrast main,html.a11y-contrast footer{background:#000!important;color:#fff!important}',
            'html.a11y-contrast header a,html.a11y-contrast main a,html.a11y-contrast footer a{color:#ffe600!important}',
            'html.a11y-contrast header,html.a11y-contrast header *{background-color:#000!important;color:#fff!important;border-color:#fff!important}',
            'html.a11y-gray header,html.a11y-gray main,html.a11y-gray footer{filter:grayscale(1)}',
            'html.a11y-invert header,html.a11y-invert main,html.a11y-invert footer{filter:invert(1) hue-rotate(180deg)}',
            'html.a11y-invert.a11y-gray header,html.a11y-invert.a11y-gray main,html.a11y-invert.a11y-gray footer{filter:grayscale(1) invert(1) hue-rotate(180deg)}',
            'html.a11y-links a{text-decoration:underline!important;text-underline-offset:3px;text-decoration-thickness:2px}',
            'html.a11y-readable,html.a11y-readable body,html.a11y-readable header,html.a11y-readable main,html.a11y-readable footer{font-family:Verdana,Tahoma,Arial,sans-serif!important;letter-spacing:.03em}',
            'html.a11y-spacing header,html.a11y-spacing main,html.a11y-spacing footer,html.a11y-spacing p,html.a11y-spacing li,html.a11y-spacing a{line-height:1.9!important;letter-spacing:.06em!important;word-spacing:.16em!important}',
            'html.a11y-cursor,html.a11y-cursor *{cursor:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'48\' height=\'48\' viewBox=\'0 0 48 48\'%3E%3Cpath fill=\'%23000\' stroke=\'%23fff\' stroke-width=\'3\' d=\'M8 4v32l8-7 5 12 6-3-5-12h12z\'/%3E%3C/svg%3E") 8 6, auto!important}',
            'html.a11y-motion,html.a11y-motion *,html.a11y-motion *::before,html.a11y-motion *::after{animation:none!important;transition:none!important;scroll-behavior:auto!important}'
        ].join('');
        document.head.appendChild(css);
    }

    root.innerHTML = [
        '<button type="button" class="a11y-fab" data-a11y-open aria-expanded="false" aria-controls="a11y-panel" aria-label="Erişilebilirlik menüsü">',
        '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">',
        '<circle cx="12" cy="4.2" r="2.1" fill="currentColor" stroke="none"/>',
        '<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 9.25h15M12 9.25v4.5M8.2 21 12 13.75 15.8 21"/>',
        '</svg></button>',
        '<div id="a11y-panel" class="a11y-panel" data-a11y-panel role="dialog" aria-labelledby="a11y-title" hidden>',
        '<div class="a11y-head"><h2 id="a11y-title">Erişilebilirlik</h2>',
        '<button type="button" class="a11y-close" data-a11y-close aria-label="Kapat">×</button></div>',
        '<div class="a11y-font">',
        '<button type="button" data-a11y-font="-1" aria-label="Yazıyı küçült">A−</button>',
        '<span data-a11y-font-label>100%</span>',
        '<button type="button" data-a11y-font="1" aria-label="Yazıyı büyüt">A+</button>',
        '</div>',
        '<div class="a11y-grid">',
        '<button type="button" data-a11y-toggle="contrast" aria-pressed="false">Yüksek kontrast<span>Siyah zemin, sarı bağlantı</span></button>',
        '<button type="button" data-a11y-toggle="gray" aria-pressed="false">Gri tonlama<span>Renkleri kaldırır</span></button>',
        '<button type="button" data-a11y-toggle="invert" aria-pressed="false">Negatif kontrast<span>Renkleri ters çevirir</span></button>',
        '<button type="button" data-a11y-toggle="links" aria-pressed="false">Bağlantıları vurgula<span>Altını çizer</span></button>',
        '<button type="button" data-a11y-toggle="readable" aria-pressed="false">Okunaklı yazı tipi<span>Verdana / Arial</span></button>',
        '<button type="button" data-a11y-toggle="spacing" aria-pressed="false">Satır aralığı<span>Harf ve satır boşluğu</span></button>',
        '<button type="button" data-a11y-toggle="cursor" aria-pressed="false">Büyük imleç<span>Daha görünür fare</span></button>',
        '<button type="button" data-a11y-toggle="motion" aria-pressed="false">Animasyonu durdur<span>Hareketi kapatır</span></button>',
        '</div>',
        '<button type="button" class="a11y-reset" data-a11y-reset>Ayarları sıfırla</button>',
        '</div>'
    ].join('');

    root.addEventListener('click', function (ev) {
        var target = ev.target.closest('button');
        if (!target || !root.contains(target)) {
            return;
        }
        if (target.hasAttribute('data-a11y-open')) {
            setOpen(!open);
            return;
        }
        if (target.hasAttribute('data-a11y-close')) {
            setOpen(false);
            return;
        }
        if (target.hasAttribute('data-a11y-reset')) {
            reset();
            return;
        }
        if (target.hasAttribute('data-a11y-font')) {
            var delta = parseInt(target.getAttribute('data-a11y-font') || '0', 10);
            state.font = Math.max(0, Math.min(fontSteps.length - 1, state.font + delta));
            saveState();
            apply();
            return;
        }
        if (target.hasAttribute('data-a11y-toggle')) {
            var flag = target.getAttribute('data-a11y-toggle');
            if (flag && Object.prototype.hasOwnProperty.call(state, flag)) {
                state[flag] = !state[flag];
                saveState();
                apply();
            }
        }
    });

    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && open) {
            setOpen(false);
        }
    });

    document.addEventListener('click', function (ev) {
        if (open && !root.contains(ev.target)) {
            setOpen(false);
        }
    });

    apply();
})();
