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
})();
</script>
</body>
</html>
