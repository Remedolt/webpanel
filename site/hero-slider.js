(function () {
    function initSlider(root) {
        var track = root.querySelector('[data-hero-track]');
        var slides = root.querySelectorAll('[data-hero-slide]');
        var prev = root.querySelector('[data-hero-prev]');
        var next = root.querySelector('[data-hero-next]');
        var dotsWrap = root.querySelector('[data-hero-dots]');
        var count = slides.length;
        if (!track || count < 1) return;

        var index = 0;
        var autoplay = root.getAttribute('data-autoplay') === '1' && count > 1;
        var interval = parseInt(root.getAttribute('data-interval') || '5000', 10);
        if (isNaN(interval) || interval < 2000) interval = 5000;
        var timer = null;
        var drag = {
            active: false,
            startX: 0,
            startY: 0,
            dx: 0,
            locked: null,
            pointerId: null
        };

        function dots() {
            if (!dotsWrap) return;
            dotsWrap.innerHTML = '';
            if (count < 2) return;
            for (var i = 0; i < count; i++) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'hero-slider-dot' + (i === index ? ' is-active' : '');
                btn.setAttribute('aria-label', 'Slayt ' + (i + 1));
                btn.setAttribute('data-hero-to', String(i));
                dotsWrap.appendChild(btn);
            }
        }

        function go(to, animate) {
            if (count < 1) return;
            index = (to + count) % count;
            if (animate === false) {
                track.style.transition = 'none';
            } else {
                track.style.transition = 'transform 0.45s ease';
            }
            track.style.transform = 'translate3d(' + (-index * 100) + '%,0,0)';
            root.querySelectorAll('[data-hero-dots] .hero-slider-dot').forEach(function (dot, i) {
                if (i === index) dot.classList.add('is-active');
                else dot.classList.remove('is-active');
            });
            if (animate === false) {
                track.offsetHeight;
                track.style.transition = 'transform 0.45s ease';
            }
        }

        function stop() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }

        function start() {
            stop();
            if (!autoplay || count < 2) return;
            timer = setInterval(function () {
                go(index + 1);
            }, interval);
        }

        if (prev) {
            prev.addEventListener('click', function () {
                go(index - 1);
                start();
            });
        }
        if (next) {
            next.addEventListener('click', function () {
                go(index + 1);
                start();
            });
        }
        if (dotsWrap) {
            dotsWrap.addEventListener('click', function (ev) {
                var btn = ev.target.closest('[data-hero-to]');
                if (!btn) return;
                go(parseInt(btn.getAttribute('data-hero-to') || '0', 10));
                start();
            });
        }

        root.addEventListener('mouseenter', stop);
        root.addEventListener('mouseleave', start);
        root.addEventListener('keydown', function (ev) {
            if (ev.key === 'ArrowLeft') {
                go(index - 1);
                start();
            } else if (ev.key === 'ArrowRight') {
                go(index + 1);
                start();
            }
        });
        root.setAttribute('tabindex', '0');

        function onPointerDown(ev) {
            if (count < 2) return;
            if (ev.target.closest('a, button')) return;
            drag.active = true;
            drag.startX = ev.clientX;
            drag.startY = ev.clientY;
            drag.dx = 0;
            drag.locked = null;
            drag.pointerId = ev.pointerId;
            root.classList.add('is-dragging');
            track.style.transition = 'none';
            try { root.setPointerCapture(ev.pointerId); } catch (err) {}
            stop();
        }

        function onPointerMove(ev) {
            if (!drag.active) return;
            var dx = ev.clientX - drag.startX;
            var dy = ev.clientY - drag.startY;
            if (drag.locked === null && (Math.abs(dx) > 8 || Math.abs(dy) > 8)) {
                drag.locked = Math.abs(dx) >= Math.abs(dy) ? 'x' : 'y';
            }
            if (drag.locked === 'y') return;
            drag.dx = dx;
            var width = root.offsetWidth || 1;
            var percent = (dx / width) * 100;
            track.style.transform = 'translate3d(' + ((-index * 100) + percent) + '%,0,0)';
            ev.preventDefault();
        }

        function onPointerUp(ev) {
            if (!drag.active) return;
            drag.active = false;
            root.classList.remove('is-dragging');
            try { root.releasePointerCapture(ev.pointerId); } catch (err) {}
            var width = root.offsetWidth || 1;
            var threshold = Math.min(120, width * 0.18);
            if (drag.locked === 'x' && Math.abs(drag.dx) > threshold) {
                go(drag.dx < 0 ? index + 1 : index - 1);
            } else {
                go(index);
            }
            start();
        }

        root.addEventListener('pointerdown', onPointerDown);
        root.addEventListener('pointermove', onPointerMove);
        root.addEventListener('pointerup', onPointerUp);
        root.addEventListener('pointercancel', onPointerUp);

        dots();
        go(0, false);
        start();
    }

    document.querySelectorAll('[data-hero-slider]').forEach(initSlider);
})();
