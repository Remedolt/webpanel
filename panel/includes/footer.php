<?php
declare(strict_types=1);

if (!isset($currentPage)) {
    http_response_code(403);
    exit('Doğrudan erişim engellendi.');
}
?>
</main>
</div>
<script>
(function () {
    var sidebar = document.getElementById('admin-sidebar');
    var toggle = document.getElementById('sidebar-toggle');
    var backdrop = document.getElementById('sidebar-backdrop');

    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('-translate-x-full');
        if (backdrop) backdrop.classList.add('hidden');
    }
    function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('-translate-x-full');
        if (backdrop) backdrop.classList.remove('hidden');
    }
    if (toggle) {
        toggle.addEventListener('click', function () {
            if (sidebar && sidebar.classList.contains('-translate-x-full')) openSidebar();
            else closeSidebar();
        });
    }
    if (backdrop) backdrop.addEventListener('click', closeSidebar);

    document.querySelectorAll('[data-submenu-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-submenu-toggle');
            var panel = id ? document.getElementById(id) : null;
            if (!panel) return;
            panel.classList.toggle('hidden');
            btn.setAttribute('aria-expanded', panel.classList.contains('hidden') ? 'false' : 'true');
            var chevron = btn.querySelector('[data-submenu-chevron]');
            if (chevron) chevron.classList.toggle('rotate-180');
        });
    });

    document.querySelectorAll('[data-dropdown]').forEach(function (wrap) {
        var button = wrap.querySelector('[data-dropdown-button]');
        var panel = wrap.querySelector('[data-dropdown-panel]');
        if (!button || !panel) return;
        button.addEventListener('click', function (ev) {
            ev.stopPropagation();
            document.querySelectorAll('[data-dropdown-panel]').forEach(function (other) {
                if (other !== panel) other.classList.add('hidden');
            });
            panel.classList.toggle('hidden');
        });
    });
    document.addEventListener('click', function () {
        document.querySelectorAll('[data-dropdown-panel]').forEach(function (panel) {
            panel.classList.add('hidden');
        });
    });

    function bindSortable(list) {
        var dragEl = null;
        list.querySelectorAll('[draggable="true"]').forEach(function (item) {
            item.addEventListener('dragstart', function (ev) {
                dragEl = item;
                item.classList.add('opacity-50');
                ev.dataTransfer.effectAllowed = 'move';
            });
            item.addEventListener('dragend', function () {
                item.classList.remove('opacity-50');
                dragEl = null;
                var ids = [];
                list.querySelectorAll('[data-id]').forEach(function (el) {
                    ids.push(el.getAttribute('data-id'));
                });
                var page = list.getAttribute('data-page') || 'menus';
                var loc = list.getAttribute('data-location') || '';
                var csrfEl = document.getElementById('menu-csrf');
                var token = csrfEl ? csrfEl.value : '';
                var csrfInput = document.querySelector('input[name="csrf_token"]');
                if (!token && csrfInput) token = csrfInput.value;
                var body = 'action=reorder&csrf_token=' + encodeURIComponent(token);
                if (loc) body += '&location=' + encodeURIComponent(loc);
                ids.forEach(function (id) { body += '&ids[]=' + encodeURIComponent(id); });
                fetch('index.php?page=' + encodeURIComponent(page), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: body
                });
            });
            item.addEventListener('dragover', function (ev) {
                ev.preventDefault();
                if (!dragEl || dragEl === item) return;
                var rect = item.getBoundingClientRect();
                var after = (ev.clientY - rect.top) > (rect.height / 2);
                list.insertBefore(dragEl, after ? item.nextSibling : item);
            });
        });
    }
    document.querySelectorAll('[data-sortable]').forEach(bindSortable);

    function fileLabel(input) {
        var files = input.files;
        if (!files || !files.length) {
            return 'Dosya seçilmedi';
        }
        if (files.length === 1) {
            return files[0].name;
        }
        return files.length + ' dosya seçildi';
    }
    document.querySelectorAll('input[type="file"]').forEach(function (input) {
        if (input.closest('.cms-file')) {
            return;
        }
        if (input.classList.contains('sr-only')) {
            var drop = input.closest('label');
            if (drop && !drop.querySelector('.cms-file-name')) {
                var hint = document.createElement('span');
                hint.className = 'cms-file-name mt-2 block text-xs text-slate-500';
                hint.textContent = 'Dosya seçilmedi';
                drop.appendChild(hint);
                input.addEventListener('change', function () {
                    hint.textContent = fileLabel(input);
                });
            }
            return;
        }
        var wrap = document.createElement('div');
        wrap.className = 'cms-file';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);
        input.classList.add('cms-file-native');
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'cms-file-btn';
        btn.textContent = input.multiple ? 'Dosyaları seç' : 'Dosya seç';
        btn.addEventListener('click', function () { input.click(); });
        var name = document.createElement('span');
        name.className = 'cms-file-name';
        name.textContent = 'Dosya seçilmedi';
        wrap.appendChild(btn);
        wrap.appendChild(name);
        input.addEventListener('change', function () {
            name.textContent = fileLabel(input);
        });
    });
})();
</script>
<script>
(function () {
    try {
        var u = new URL(window.location.href);
        if (u.searchParams.has('done')) {
            u.searchParams.delete('done');
            history.replaceState({}, '', u.pathname + u.search + u.hash);
        }
    } catch (err) {}
    var hint = document.getElementById('cms-save-hint');
    if (hint) {
        window.addEventListener('pageshow', function (ev) {
            if (ev.persisted) hint.style.display = 'none';
        });
        setTimeout(function () {
            hint.style.transition = 'opacity .4s ease';
            hint.style.opacity = '0';
            setTimeout(function () { hint.style.display = 'none'; }, 400);
        }, 4000);
    }
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.5/tinymce.min.js"></script>
<script>
(function () {
    if (!window.tinymce || !document.querySelector('textarea.cms-editor')) return;
    tinymce.init({
        selector: 'textarea.cms-editor',
        menubar: false,
        branding: false,
        statusbar: false,
        plugins: 'lists link autolink',
        toolbar: 'undo redo | blocks | fontfamily fontsize | bold italic underline | alignleft aligncenter alignright | bullist numlist | link | removeformat',
        font_size_formats: '12px 14px 16px 18px 20px 24px 32px',
        font_family_formats: 'Arial=arial,helvetica,sans-serif; Georgia=georgia,serif; Tahoma=tahoma,sans-serif; Times New Roman=times new roman,times,serif; Verdana=verdana,geneva,sans-serif',
        height: 340,
        relative_urls: false,
        convert_urls: false,
        setup: function (ed) {
            ed.on('change keyup', function () { ed.save(); });
        }
    });
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            if (window.tinymce) tinymce.triggerSave();
        });
    });
})();
</script>
<script>
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
</script>
<style>
.cms-file{position:relative;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.cms-file-native{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;opacity:0;pointer-events:none}
.cms-file-btn{display:inline-flex;align-items:center;border-radius:.375rem;border:1px solid #cbd5e1;background:#f8fafc;padding:.45rem .85rem;font-size:.875rem;font-weight:600;color:#0f172a;cursor:pointer}
.cms-file-btn:hover{background:#e2e8f0}
.cms-file-name{font-size:.8125rem;color:#64748b;word-break:break-all}
#admin-sidebar .panel-brand-title{color:var(--p-nav-active)}
#admin-sidebar .panel-brand-sub{color:var(--p-sidebar-muted)}
</style>
</body>
</html>
