(function () {
    'use strict';

    if (typeof BBChat === 'undefined') return;

    var toggle = document.getElementById('bb-chat-toggle');
    var panel = document.getElementById('bb-chat-panel');
    var log = document.getElementById('bb-chat-log');
    var quick = document.getElementById('bb-chat-quick');
    var form = document.getElementById('bb-chat-form');
    var input = document.getElementById('bb-chat-input');
    var root = document.getElementById('bb-chat');
    if (!toggle || !panel || !log || !form || !input) return;

    var history = [];
    var busy = false;
    var started = false;

    // Greeting based on the visitor's own clock
    function getGreeting() {
        var h = new Date().getHours();
        if (h >= 5 && h < 12) return 'Good morning';
        if (h >= 12 && h < 18) return 'Good afternoon';
        return 'Good evening';
    }

    // Turns http(s):// and www. links inside text into real clickable links.
    // Built with text nodes + createElement (never innerHTML), so it stays safe.
    function appendWithLinks(el, text) {
        var re = /(https?:\/\/[^\s<>"']+|www\.[^\s<>"']+)/gi;
        var last = 0;
        var m;

        while ((m = re.exec(text)) !== null) {
            var url = m[0];
            var trail = '';

            // keep punctuation at the end of a sentence outside the link
            var punct = url.match(/[.,;:!?)\]]+$/);
            if (punct) {
                trail = punct[0];
                url = url.slice(0, -trail.length);
            }

            if (m.index > last) {
                el.appendChild(document.createTextNode(text.slice(last, m.index)));
            }

            var a = document.createElement('a');
            a.href = /^www\./i.test(url) ? 'https://' + url : url;
            a.textContent = url;
            a.className = 'bb-link';
            a.target = '_blank';
            a.rel = 'noopener noreferrer';
            el.appendChild(a);

            if (trail) {
                el.appendChild(document.createTextNode(trail));
            }
            last = m.index + m[0].length;
        }

        if (last < text.length) {
            el.appendChild(document.createTextNode(text.slice(last)));
        }
    }

    function scrollDown() {
        log.scrollTop = log.scrollHeight;
    }

    function addMessage(text, who, links) {
        var el = document.createElement('div');
        el.className = 'bb-msg bb-msg-' + who;
        if (who === 'bot') {
            appendWithLinks(el, text); // clickable links, still safe from HTML injection
        } else {
            el.textContent = text; // textContent = safe from HTML injection
        }

        if (links && links.length) {
            var wrap = document.createElement('div');
            wrap.className = 'bb-msg-links';
            links.forEach(function (l) {
                var a = document.createElement('a');
                a.href = l.url;
                a.textContent = l.label;
                wrap.appendChild(a);
            });
            el.appendChild(wrap);
        }

        log.appendChild(el);
        scrollDown();
        return el;
    }

    function showTyping() {
        var el = document.createElement('div');
        el.className = 'bb-msg bb-msg-bot bb-typing';
        el.innerHTML = '<span></span><span></span><span></span>';
        log.appendChild(el);
        scrollDown();
        return el;
    }

    function setOpen(open) {
        root.classList.toggle('is-open', open);
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Close chat' : 'Open chat');

        if (open) {
            if (!started) {
                started = true;
                addMessage(BBChat.welcome.replace('{greeting}', getGreeting()), 'bot');
                (BBChat.quick || []).forEach(function (q) {
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.textContent = q;
                    b.addEventListener('click', function () { send(q); });
                    quick.appendChild(b);
                });
            }
            setTimeout(function () { input.focus(); }, 50);
        }
    }

    function send(text) {
        text = (text || '').trim();
        if (!text || busy) return;

        busy = true;
        quick.hidden = true;
        addMessage(text, 'user');
        history.push({ role: 'user', content: text });
        input.value = '';

        var typing = showTyping();

        fetch(BBChat.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ messages: history })
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                typing.remove();
                var reply = (data && data.reply) ? data.reply : 'Sorry, something went wrong.';
                addMessage(reply, 'bot', data && data.links);
                history.push({ role: 'assistant', content: reply });
            })
            .catch(function () {
                typing.remove();
                addMessage('Sorry, I cannot connect right now. Please try again later.', 'bot');
                history.pop(); // drop the failed question so the history stays valid
            })
            .then(function () {
                busy = false;
            });
    }

    toggle.addEventListener('click', function () {
        setOpen(panel.hidden);
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        send(input.value);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !panel.hidden) {
            setOpen(false);
            toggle.focus();
        }
    });
})();