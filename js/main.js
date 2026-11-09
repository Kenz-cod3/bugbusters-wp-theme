(function () {
    'use strict';

    /* ---------- Loading screen ---------- */
    var MIN_SHOW = 700;   // show the loader at least this long (ms)
    var MAX_WAIT = 6000;  // never block the page longer than this (ms)
    var startTime = Date.now();
    var loaderDone = false;

    function hideLoader() {
        if (loaderDone) return;
        loaderDone = true;
        document.body.classList.add('is-loaded');
    }

    function finishLoading() {
        var wait = Math.max(0, MIN_SHOW - (Date.now() - startTime));
        setTimeout(hideLoader, wait);
    }

    if (document.readyState === 'complete') {
        finishLoading();
    } else {
        window.addEventListener('load', finishLoading);
    }
    setTimeout(hideLoader, MAX_WAIT); // safety net

    /* ---------- Dark mode ---------- */
    var root = document.documentElement;
    var themeBtn = document.getElementById('theme-toggle');
    var STORAGE_KEY = 'bb-theme';

    function getSaved() {
        try { return localStorage.getItem(STORAGE_KEY); } catch (e) { return null; }
    }

    function save(theme) {
        try { localStorage.setItem(STORAGE_KEY, theme); } catch (e) { /* ignore */ }
    }

    function applyTheme(theme) {
        root.setAttribute('data-theme', theme);
        if (themeBtn) {
            var dark = theme === 'dark';
            themeBtn.setAttribute('aria-pressed', dark ? 'true' : 'false');
            themeBtn.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
        }
    }

    // Sync the button with the theme chosen by the early script in <head>
    applyTheme(root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light');

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function switchTheme(next, x, y) {
        // Fallback: no View Transitions support (or reduced motion) -> CSS color fade only
        if (!document.startViewTransition || reduceMotion) {
            applyTheme(next);
            return;
        }

        root.classList.add('theme-vt');

        var transition = document.startViewTransition(function () {
            applyTheme(next);
        });

        transition.ready.then(function () {
            var radius = Math.hypot(
                Math.max(x, window.innerWidth - x),
                Math.max(y, window.innerHeight - y)
            );
            root.animate(
                {
                    clipPath: [
                        'circle(0px at ' + x + 'px ' + y + 'px)',
                        'circle(' + radius + 'px at ' + x + 'px ' + y + 'px)'
                    ]
                },
                {
                    duration: 650,
                    easing: 'cubic-bezier(0.4, 0, 0.2, 1)',
                    pseudoElement: '::view-transition-new(root)'
                }
            );
        });

        transition.finished.then(function () {
            root.classList.remove('theme-vt');
        }, function () {
            root.classList.remove('theme-vt');
        });
    }

    if (themeBtn) {
        themeBtn.addEventListener('click', function () {
            var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            var rect = themeBtn.getBoundingClientRect();

            // Icon spin
            themeBtn.classList.remove('is-animating');
            void themeBtn.offsetWidth; // restart the animation
            themeBtn.classList.add('is-animating');
            setTimeout(function () { themeBtn.classList.remove('is-animating'); }, 700);

            switchTheme(next, rect.left + rect.width / 2, rect.top + rect.height / 2);
            save(next);
        });
    }

    // Follow the system setting until the visitor picks one manually
    if (window.matchMedia) {
        var mq = window.matchMedia('(prefers-color-scheme: dark)');
        var onSystemChange = function (e) {
            if (!getSaved()) applyTheme(e.matches ? 'dark' : 'light');
        };
        if (mq.addEventListener) {
            mq.addEventListener('change', onSystemChange);
        } else if (mq.addListener) {
            mq.addListener(onSystemChange);
        }
    }

    /* ---------- Tech marquee: auto-scroll + drag / swipe ---------- */
    (function initMarquee() {
        var marquee = document.querySelector('.marquee');
        var track = marquee ? marquee.querySelector('.marquee-track') : null;
        var firstGroup = track ? track.querySelector('.marquee-group') : null;
        if (!marquee || !track || !firstGroup) return;

        // Reduced motion: leave the native horizontally scrollable strip (CSS handles it)
        if (reduceMotion) return;

        marquee.classList.add('is-js');

        var AUTO_SPEED = 40;     // px per second (auto-scroll to the left)
        var FRICTION = 0.94;     // momentum fade after you release (closer to 1 = glides longer)

        var x = 0;               // current translateX of the track
        var loopWidth = 0;       // width of ONE group (the track holds two copies)
        var velocity = 0;        // px per second, from dragging
        var dragging = false;
        var hovering = false;
        var visible = true;
        var pointerId = null;
        var lastX = 0;
        var lastTime = 0;
        var lastFrame = 0;

        function measure() {
            loopWidth = firstGroup.getBoundingClientRect().width;
        }

        function wrap() {
            if (!loopWidth) return;
            while (x <= -loopWidth) x += loopWidth;
            while (x > 0) x -= loopWidth;
        }

        function render() {
            track.style.transform = 'translate3d(' + x + 'px, 0, 0)';
        }

        function frame(now) {
            var dt = Math.min((now - lastFrame) / 1000, 0.05); // clamp big jumps (tab switch)
            lastFrame = now;

            if (visible && !dragging) {
                // Momentum from the last swipe
                if (Math.abs(velocity) > 5) {
                    x += velocity * dt;
                    velocity *= Math.pow(FRICTION, dt * 60);
                } else {
                    velocity = 0;
                    // Keep auto-scrolling (pauses while hovering with a mouse)
                    if (!hovering) x -= AUTO_SPEED * dt;
                }
                wrap();
                render();
            }

            window.requestAnimationFrame(frame);
        }

        /* --- Pointer events (mouse, touch and pen) --- */
        marquee.addEventListener('pointerdown', function (e) {
            if (e.pointerType === 'mouse' && e.button !== 0) return;
            dragging = true;
            pointerId = e.pointerId;
            lastX = e.clientX;
            lastTime = performance.now();
            velocity = 0;
            marquee.classList.add('is-dragging');
            try { marquee.setPointerCapture(pointerId); } catch (err) { /* ignore */ }
        });

        marquee.addEventListener('pointermove', function (e) {
            if (!dragging || e.pointerId !== pointerId) return;

            var now = performance.now();
            var dx = e.clientX - lastX;
            var dt = (now - lastTime) / 1000;

            x += dx;
            wrap();
            render();

            if (dt > 0) {
                // Smooth the speed so a flick feels natural
                velocity = velocity * 0.6 + (dx / dt) * 0.4;
            }

            lastX = e.clientX;
            lastTime = now;
        });

        function endDrag(e) {
            if (!dragging || (e && e.pointerId !== pointerId)) return;
            dragging = false;
            marquee.classList.remove('is-dragging');
            try { marquee.releasePointerCapture(pointerId); } catch (err) { /* ignore */ }
            pointerId = null;

            // If the finger stopped before lifting, don't fling
            if (performance.now() - lastTime > 80) velocity = 0;

            // Limit crazy-fast flicks
            velocity = Math.max(-2500, Math.min(2500, velocity));
        }

        marquee.addEventListener('pointerup', endDrag);
        marquee.addEventListener('pointercancel', endDrag);
        marquee.addEventListener('lostpointercapture', endDrag);

        // Pause auto-scroll on hover (mouse only)
        marquee.addEventListener('pointerenter', function (e) {
            if (e.pointerType === 'mouse') hovering = true;
        });
        marquee.addEventListener('pointerleave', function (e) {
            if (e.pointerType === 'mouse') hovering = false;
        });

        // Stop images from being dragged like files
        marquee.addEventListener('dragstart', function (e) { e.preventDefault(); });

        // Save work when the strip is off screen
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                visible = entries[0].isIntersecting;
            }).observe(marquee);
        }

        // Re-measure when the layout changes
        window.addEventListener('resize', function () { measure(); wrap(); render(); });
        window.addEventListener('load', function () { measure(); wrap(); render(); });

        measure();
        render();
        lastFrame = performance.now();
        window.requestAnimationFrame(frame);
    })();

    /* ---------- Custom scroll indicator: slim bar + up / down arrows ---------- */
    (function initScrollbar() {
        // Only on devices with a mouse; phones keep their own overlay scrollbar
        if (!window.matchMedia || !window.matchMedia('(pointer: fine)').matches) return;

        var STEP = 60;          // px moved per arrow click / repeat tick
        var HOLD_DELAY = 350;   // ms before holding an arrow starts repeating
        var HOLD_TICK = 16;     // ms between repeat ticks

        var ARROW_UP = '<svg viewBox="0 0 10 10" aria-hidden="true"><path d="M5 2.5 9 7.5H1z" fill="currentColor"/></svg>';
        var ARROW_DOWN = '<svg viewBox="0 0 10 10" aria-hidden="true"><path d="M5 7.5 9 2.5H1z" fill="currentColor"/></svg>';

        // Build: [ up arrow ] [ rail + thumb ] [ down arrow ]
        var track = document.createElement('div');
        track.className = 'scroll-track';
        track.setAttribute('aria-hidden', 'true');

        var upBtn = document.createElement('button');
        upBtn.type = 'button';
        upBtn.className = 'scroll-arrow scroll-arrow-up';
        upBtn.tabIndex = -1;
        upBtn.innerHTML = ARROW_UP;

        var rail = document.createElement('div');
        rail.className = 'scroll-rail';

        var thumb = document.createElement('div');
        thumb.className = 'scroll-thumb';
        rail.appendChild(thumb);

        var downBtn = document.createElement('button');
        downBtn.type = 'button';
        downBtn.className = 'scroll-arrow scroll-arrow-down';
        downBtn.tabIndex = -1;
        downBtn.innerHTML = ARROW_DOWN;

        track.appendChild(upBtn);
        track.appendChild(rail);
        track.appendChild(downBtn);
        document.body.appendChild(track);
        root.classList.add('has-scrollbar');

        var dragging = false;
        var grabOffset = 0;

        function metrics() {
            var doc = document.documentElement;
            var total = doc.scrollHeight;
            var view = window.innerHeight;
            var railH = rail.clientHeight;
            var thumbH = Math.max(30, railH * (view / total));
            return { total: total, view: view, railH: railH, thumbH: thumbH, maxScroll: total - view };
        }

        function update() {
            var m = metrics();
            if (m.maxScroll <= 0) { track.style.visibility = 'hidden'; return; }
            track.style.visibility = 'visible';
            var y = window.pageYOffset || document.documentElement.scrollTop || 0;
            var ratio = Math.min(Math.max(y / m.maxScroll, 0), 1);
            thumb.style.height = m.thumbH + 'px';
            thumb.style.transform = 'translateY(' + (ratio * (m.railH - m.thumbH)) + 'px)';

            // Dim an arrow when you can't go any further that way
            upBtn.classList.toggle('is-disabled', y <= 0);
            downBtn.classList.toggle('is-disabled', y >= m.maxScroll - 1);
        }

        function scrollToPointer(clientY) {
            var m = metrics();
            var rect = rail.getBoundingClientRect();
            var pos = clientY - rect.top - grabOffset;
            var space = m.railH - m.thumbH;
            var ratio = space > 0 ? Math.min(Math.max(pos / space, 0), 1) : 0;
            window.scrollTo(0, ratio * m.maxScroll);
        }

        /* --- Thumb drag --- */
        thumb.addEventListener('pointerdown', function (e) {
            dragging = true;
            grabOffset = e.clientY - thumb.getBoundingClientRect().top;
            track.classList.add('is-dragging');
            document.documentElement.style.scrollBehavior = 'auto';
            try { thumb.setPointerCapture(e.pointerId); } catch (err) { /* ignore */ }
            e.preventDefault();
            e.stopPropagation();
        });

        thumb.addEventListener('pointermove', function (e) {
            if (dragging) scrollToPointer(e.clientY);
        });

        function endDrag() {
            if (!dragging) return;
            dragging = false;
            track.classList.remove('is-dragging');
            document.documentElement.style.scrollBehavior = '';
        }

        thumb.addEventListener('pointerup', endDrag);
        thumb.addEventListener('pointercancel', endDrag);

        /* --- Click on the empty rail: jump there --- */
        rail.addEventListener('pointerdown', function (e) {
            if (e.target !== rail) return;
            grabOffset = thumb.offsetHeight / 2;
            scrollToPointer(e.clientY);
        });

        /* --- Arrow buttons: click = one step, hold = keep scrolling --- */
        function bindArrow(btn, direction) {
            var delayTimer = null;
            var repeatTimer = null;

            function step() {
                window.scrollBy({ top: direction * STEP, left: 0, behavior: 'instant' });
            }

            function stop() {
                clearTimeout(delayTimer);
                clearInterval(repeatTimer);
                delayTimer = null;
                repeatTimer = null;
            }

            btn.addEventListener('pointerdown', function (e) {
                if (e.pointerType === 'mouse' && e.button !== 0) return;
                e.preventDefault();
                step();
                stop();
                delayTimer = setTimeout(function () {
                    repeatTimer = setInterval(step, HOLD_TICK);
                }, HOLD_DELAY);
            });

            btn.addEventListener('pointerup', stop);
            btn.addEventListener('pointerleave', stop);
            btn.addEventListener('pointercancel', stop);
            window.addEventListener('blur', stop);
        }

        bindArrow(upBtn, -1);
        bindArrow(downBtn, 1);

        window.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        window.addEventListener('load', update);
        if ('ResizeObserver' in window) new ResizeObserver(update).observe(document.body);
        update();
    })();

    /* ---------- Contact form: show the email of the chosen "Send to" ---------- */
    (function initSendTo() {
        var select = document.getElementById('bb_to');
        var out = document.getElementById('bb_to_email');
        if (!select || !out) return;

        function show() {
            var opt = select.options[select.selectedIndex];
            out.textContent = opt ? opt.getAttribute('data-email') || '' : '';
        }

        select.addEventListener('change', show);
        show();
    })();

    /* ---------- Contact form: styled "Send to" dropdown ---------- */
    (function initSelectUI() {
        var select = document.getElementById('bb_to');
        if (!select) return;

        var CHEVRON = '<svg class="cp-chevron" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>';
        var CHECK = '<svg class="cp-check" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>';
        var MAIL = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>';

        var wrap = document.createElement('div');
        wrap.className = 'cp-select';

        var trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'cp-select-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');

        var list = document.createElement('ul');
        list.className = 'cp-select-list';
        list.setAttribute('role', 'listbox');
        list.id = 'bb_to_list';
        list.hidden = true;
        trigger.setAttribute('aria-controls', list.id);

        function iconFor(opt) {
            var box = document.createElement('span');
            box.className = 'cp-opt-icon';
            var img = opt.getAttribute('data-img');
            if (img) {
                var im = document.createElement('img');
                im.src = img;
                im.alt = '';
                im.loading = 'lazy';
                box.appendChild(im);
            } else if (opt.getAttribute('data-initials')) {
                box.textContent = opt.getAttribute('data-initials');
            } else {
                box.classList.add('is-mail');
                box.innerHTML = MAIL;
            }
            return box;
        }

        function textFor(opt) {
            var t = document.createElement('span');
            t.className = 'cp-opt-text';
            var a = document.createElement('strong');
            a.textContent = opt.textContent;
            var b = document.createElement('small');
            b.textContent = opt.getAttribute('data-email') || '';
            t.appendChild(a);
            t.appendChild(b);
            return t;
        }

        var items = [];
        Array.prototype.forEach.call(select.options, function (opt, i) {
            var li = document.createElement('li');
            li.className = 'cp-select-option';
            li.setAttribute('role', 'option');
            li.id = 'bb_to_opt_' + i;
            li.appendChild(iconFor(opt));
            li.appendChild(textFor(opt));
            var tick = document.createElement('span');
            tick.className = 'cp-opt-tick';
            tick.innerHTML = CHECK;
            li.appendChild(tick);
            li.addEventListener('click', function () { choose(i); close(true); });
            li.addEventListener('mousemove', function () { setActive(i); });
            list.appendChild(li);
            items.push(li);
        });

        wrap.appendChild(trigger);
        wrap.appendChild(list);
        select.parentNode.insertBefore(wrap, select.nextSibling);
        select.classList.add('cp-select-native');
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');

        var active = select.selectedIndex;

        function renderTrigger() {
            var opt = select.options[select.selectedIndex];
            trigger.innerHTML = '';
            trigger.appendChild(iconFor(opt));
            trigger.appendChild(textFor(opt));
            trigger.insertAdjacentHTML('beforeend', CHEVRON);
            items.forEach(function (li, i) {
                li.setAttribute('aria-selected', i === select.selectedIndex ? 'true' : 'false');
                li.classList.toggle('is-selected', i === select.selectedIndex);
            });
        }

        function setActive(i) {
            active = Math.max(0, Math.min(items.length - 1, i));
            items.forEach(function (li, n) { li.classList.toggle('is-active', n === active); });
            trigger.setAttribute('aria-activedescendant', items[active].id);
            items[active].scrollIntoView({ block: 'nearest' });
        }

        function choose(i) {
            if (select.selectedIndex !== i) {
                select.selectedIndex = i;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
            renderTrigger();
        }

        function isOpen() { return !list.hidden; }

        function open() {
            list.hidden = false;
            wrap.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            setActive(select.selectedIndex);
        }

        function close(focusTrigger) {
            list.hidden = true;
            wrap.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            trigger.removeAttribute('aria-activedescendant');
            if (focusTrigger) trigger.focus();
        }

        trigger.addEventListener('click', function () { isOpen() ? close(false) : open(); });

        trigger.addEventListener('keydown', function (e) {
            var k = e.key;
            if (k === 'ArrowDown' || k === 'ArrowUp') {
                e.preventDefault();
                if (!isOpen()) { open(); return; }
                setActive(active + (k === 'ArrowDown' ? 1 : -1));
            } else if (k === 'Home' && isOpen()) {
                e.preventDefault(); setActive(0);
            } else if (k === 'End' && isOpen()) {
                e.preventDefault(); setActive(items.length - 1);
            } else if (k === 'Enter' || k === ' ') {
                if (isOpen()) { e.preventDefault(); choose(active); close(true); }
            } else if (k === 'Escape' && isOpen()) {
                e.preventDefault(); close(true);
            } else if (k === 'Tab' && isOpen()) {
                close(false);
            }
        });

        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) close(false);
        });

        // Clicking the "Send to" label focuses the new dropdown
        var label = document.querySelector('label[for="bb_to"]');
        if (label) label.addEventListener('click', function (e) { e.preventDefault(); trigger.focus(); });

        renderTrigger();
    })();

    /* ---------- Navigation ---------- */
    var header = document.getElementById('site-header');
    var toggle = document.querySelector('.nav-toggle');
    var nav = document.getElementById('primary-nav');
    if (!header || !toggle || !nav) return;

    var links = nav.querySelectorAll('a[href^="#"]');

    /* ---------- Mobile menu ---------- */
    function setMenu(open) {
        nav.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    }

    toggle.addEventListener('click', function () {
        setMenu(toggle.getAttribute('aria-expanded') !== 'true');
    });

    links.forEach(function (link) {
        link.addEventListener('click', function () { setMenu(false); });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') setMenu(false);
    });

    document.addEventListener('click', function (e) {
        if (!header.contains(e.target)) setMenu(false);
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 860) setMenu(false);
    });

    /* ---------- Header shadow ---------- */
    function updateHeader() {
        var y = window.pageYOffset || document.documentElement.scrollTop || 0;
        header.classList.toggle('is-scrolled', y > 10);
    }

    /* ---------- Active link follows the section on screen ---------- */
    var items = [];
    links.forEach(function (link) {
        if (link.classList.contains('nav-button')) return;
        var section = document.getElementById(link.getAttribute('href').slice(1));
        if (section) items.push({ link: link, section: section });
    });

    var currentLink = null;

    function setActive() {
        if (!items.length) return;

        var y = window.pageYOffset || document.documentElement.scrollTop || 0;
        var line = y + window.innerHeight * 0.35; // reading line (35% from top of screen)
        var current = items[0];                   // Home by default

        items.forEach(function (item) {
            var top = item.section.getBoundingClientRect().top + y;
            if (top <= line) current = item;
        });

        var atBottom = window.innerHeight + y >= document.documentElement.scrollHeight - 4;
        if (atBottom) current = items[items.length - 1];

        if (current.link === currentLink) return;
        currentLink = current.link;

        items.forEach(function (item) {
            var on = item.link === currentLink;
            item.link.classList.toggle('is-active', on);
            if (on) {
                item.link.setAttribute('aria-current', 'true');
            } else {
                item.link.removeAttribute('aria-current');
            }
        });
    }

    /* ---------- Run on scroll (throttled with requestAnimationFrame) ---------- */
    var ticking = false;

    function onScroll() {
        if (ticking) return;
        ticking = true;
        window.requestAnimationFrame(function () {
            updateHeader();
            setActive();
            ticking = false;
        });
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);
    window.addEventListener('load', onScroll);
    window.addEventListener('hashchange', onScroll);

    updateHeader();
    setActive();
})();