<?php

/**
 * BugBusters Billing Guard (v3)
 * ------------------------------------------------------------------
 * - Dense "PREVIEW - PAYMENT PENDING" watermark with date/time, so any
 *   screenshot (even from a phone) shows it and can be traced.
 * - The watermark rebuilds itself if someone deletes or hides it
 *   in DevTools (MutationObserver + interval check).
 * - Popup + blur on PrintScreen, Win+Shift+S, Cmd+Shift+3/4/5, Ctrl+P.
 * - Blur when the tab is hidden, the window loses focus, or the mouse
 *   leaves the page (optional).
 * - Right-click, image dragging and text selection are disabled.
 * - GCash QR (images/payment.png) + account name + number are shown
 *   in the popup ONLY when a screenshot / print attempt is detected.
 *
 * NOTE: A website can NEVER truly detect or block screenshots.
 * The watermark is the real protection. Everything else only
 * discourages casual screenshots.
 *
 * When the client has paid: set BB_BILLING_GUARD to false below,
 * or remove the require_once line in functions.php.
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ---------- Settings: edit here ---------- */

// true = guard ON, false = guard OFF (client has paid)
if (!defined('BB_BILLING_GUARD')) {
    define('BB_BILLING_GUARD', true);
}

$GLOBALS['bb_guard'] = array(
    'title'              => 'Screenshots are not allowed',
    'message'            => 'This website is a preview and the payment is still pending. Please settle the billing to get the final version.',
    'contact'            => 'sipralim105@gmail.com',   // <- change to your real email
    'button'             => 'I understand',
    'watermark_text'     => 'PREVIEW - PAYMENT PENDING',
    'watermark_opacity'  => 0.30,   // 0.1 (faint) to 0.6 (very strong)
    'watermark_mode'     => 'on_capture', // 'on_capture' = shows only on screenshot triggers, 'always' = always visible
    'mobile_always'      => false,   // true = on phones/tablets the watermark is ALWAYS visible
    'reveal_seconds'     => 5,      // how long the watermark stays after a trigger (on_capture mode)
    'blur_on_focus_loss' => true,   // blur when window loses focus / tab hidden / mouse leaves
    'block_right_click'  => true,   // disable right-click menu
    'show_to_admin'      => false,  // false = logged-in admins do not see the guard

    /* ----- GCash payment (shown in the popup on screenshot attempt) ----- */
    'show_gcash'         => true,                 // show QR + name in the popup
    'qr_image'           => '/images/payment.png', // path inside your theme folder
    'gcash_name'         => 'Kenneth Carl Milarpis',     // <- change: name shown under the QR
    'gcash_number'       => '09XX XXX XXXX',      // <- change: GCash number (leave '' to hide)
);

/* ---------- Output (printed before </body>) ---------- */

add_action('wp_footer', function () {

    if (!BB_BILLING_GUARD) {
        return;
    }

    $g = $GLOBALS['bb_guard'];

    if (!$g['show_to_admin'] && current_user_can('manage_options')) {
        return;
    }

    $opacity = max(0.05, min(0.8, (float) $g['watermark_opacity']));
    $mode    = ($g['watermark_mode'] === 'always') ? 'always' : 'on_capture';
    $reveal  = max(1, (int) $g['reveal_seconds']);

    // GCash QR: only show if enabled and the image file really exists
    $qr_url = '';
    if (!empty($g['show_gcash']) && !empty($g['qr_image'])) {
        $qr_path = get_theme_file_path($g['qr_image']);
        if (file_exists($qr_path)) {
            $qr_url = get_theme_file_uri($g['qr_image']);
        }
    }
?>
    <!-- BILLING GUARD -->
    <div class="bb-watermark" aria-hidden="true"></div>

    <div class="bb-billing" id="bb-billing" role="alertdialog" aria-modal="true" aria-labelledby="bb-billing-title" hidden>
        <div class="bb-billing-box">
            <h2 id="bb-billing-title"><?php echo esc_html($g['title']); ?></h2>
            <p><?php echo esc_html($g['message']); ?></p>

            <?php if ($qr_url) : ?>
                <div class="bb-pay">
                    <img src="<?php echo esc_url($qr_url); ?>" alt="GCash QR code" class="bb-pay-qr" draggable="false">
                    <?php if (!empty($g['gcash_name'])) : ?>
                        <p class="bb-pay-name"><?php echo esc_html($g['gcash_name']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($g['gcash_number'])) : ?>
                        <p class="bb-pay-num">GCash: <?php echo esc_html($g['gcash_number']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <p><strong>Contact:</strong> <?php echo esc_html($g['contact']); ?></p>
            <button type="button" id="bb-billing-close"><?php echo esc_html($g['button']); ?></button>
        </div>
    </div>

    <style>
        /* Fallback watermark (used if JS is off). JS replaces it with a dated one. */
        .bb-watermark {
            position: fixed !important;
            inset: 0 !important;
            z-index: 2147483646 !important;
            pointer-events: none !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            background-image: url("data:image/svg+xml;utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='180'%3E%3Ctext x='20' y='100' transform='rotate(-25 150 90)' font-size='18' font-family='Arial' font-weight='bold' fill='rgba(220,38,38,0.30)' stroke='rgba(255,255,255,0.5)' stroke-width='0.6' paint-order='stroke'%3EPREVIEW - PAYMENT PENDING%3C/text%3E%3C/svg%3E") !important;
        }

        <?php if ($mode === 'on_capture') : ?>
        /* on_capture mode: hidden until a trigger (JS sets .bb-on) */
        .bb-watermark { opacity: 0 !important; }
        .bb-watermark.bb-on { opacity: 1 !important; }
        <?php endif; ?>

        /* Popup */
        .bb-billing {
            position: fixed;
            inset: 0;
            z-index: 2147483647;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, .8);
            padding: 1rem;
            overflow-y: auto;
        }

        .bb-billing[hidden] {
            display: none;
        }

        .bb-billing-box {
            background: #fff;
            color: #111;
            max-width: 420px;
            width: 100%;
            max-height: 94vh;
            overflow-y: auto;
            padding: 2rem;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .35);
        }

        .bb-billing-box h2 {
            margin: 0 0 .75rem;
            font-size: 1.4rem;
        }

        .bb-billing-box p {
            margin: 0 0 .6rem;
            line-height: 1.5;
        }

        .bb-billing-box button {
            margin-top: 1rem;
            padding: .7rem 1.4rem;
            border: 0;
            border-radius: 999px;
            background: #dc2626;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
        }

        .bb-billing-box button:hover {
            background: #b91c1c;
        }

        /* GCash block */
        .bb-pay {
            margin: 1rem 0 .8rem;
            padding: 1rem;
            border: 2px solid #007cff;
            border-radius: 14px;
            background: #f2f8ff;
        }

        .bb-pay-qr {
            display: block;
            width: 100%;
            max-width: 240px;
            height: auto;
            margin: 0 auto .7rem;
            border-radius: 10px;
            background: #fff;
            pointer-events: none;
            -webkit-user-drag: none;
        }

        .bb-pay-name {
            margin: 0 !important;
            font-weight: 800;
            font-size: 1.05rem;
            color: #0a58ca;
            text-transform: uppercase;
        }

        .bb-pay-num {
            margin: .15rem 0 0 !important;
            font-weight: 600;
            color: #333;
        }

        /* Blur everything except the guard itself */
        body.bb-blur>*:not(.bb-billing):not(.bb-watermark) {
            filter: blur(18px);
        }

        /* No text selection / image dragging */
        body {
            -webkit-user-select: none;
            user-select: none;
        }

        img {
            -webkit-user-drag: none;
        }

        /* Block printing completely */
        @media print {
            body>* {
                display: none !important;
            }

            body::before {
                content: "Printing is disabled. Payment pending.";
                display: block;
                padding: 2rem;
                font: 700 24px Arial, sans-serif;
            }
        }
    </style>

    <script>
        (function() {
            var CFG = {
                text: <?php echo wp_json_encode($g['watermark_text']); ?>,
                opacity: <?php echo json_encode($opacity); ?>,
                mode: <?php echo wp_json_encode($mode); ?>,
                revealMs: <?php echo (int) $reveal * 1000; ?>,
                mobileAlways: <?php echo $g['mobile_always'] ? 'true' : 'false'; ?>,
                blurOnFocusLoss: <?php echo $g['blur_on_focus_loss'] ? 'true' : 'false'; ?>,
                blockRightClick: <?php echo $g['block_right_click'] ? 'true' : 'false'; ?>
            };

            var modal = document.getElementById('bb-billing');
            var closeBtn = document.getElementById('bb-billing-close');
            if (!modal || !closeBtn) return;

            /* ================= WATERMARK (self-repairing) ================= */

            // Phones / tablets: screenshots cannot be detected, so keep the watermark always on
            var isMobile = (window.matchMedia && window.matchMedia('(pointer: coarse)').matches) ||
                /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent);
            if (CFG.mobileAlways && isMobile) {
                CFG.mode = 'always';
            }

            var stamp = new Date().toLocaleString();

            function watermarkImage() {
                var svg =
                    "<svg xmlns='http://www.w3.org/2000/svg' width='300' height='180'>" +
                    "<g transform='rotate(-25 150 90)' font-family='Arial' font-weight='bold' text-anchor='middle'" +
                    " fill='rgba(220,38,38," + CFG.opacity + ")' stroke='rgba(255,255,255,0.5)' stroke-width='0.6' paint-order='stroke'>" +
                    "<text x='150' y='85' font-size='18'>" + CFG.text + "</text>" +
                    "<text x='150' y='108' font-size='12'>" + stamp + "</text>" +
                    "</g></svg>";
                return 'url("data:image/svg+xml;utf8,' + encodeURIComponent(svg) + '")';
            }

            var wm = document.querySelector('.bb-watermark');
            var wmOn = (CFG.mode === 'always');   // is the watermark visible right now?
            var wmTimer = null;

            // Remove the watermark right away (on_capture mode only)
            function hideWm() {
                if (CFG.mode === 'always') return;
                clearTimeout(wmTimer);
                wmOn = false;
                if (wm) wm.classList.remove('bb-on');
                ensureWatermark();
            }

            // Show the watermark (on_capture mode). It hides again after revealMs.
            function reveal(keep) {
                if (CFG.mode === 'always') return;
                wmOn = true;
                if (wm) wm.classList.add('bb-on');
                ensureWatermark();
                clearTimeout(wmTimer);
                if (!keep) {
                    wmTimer = setTimeout(function() {
                        // stay visible while the popup is open
                        if (!modal.hidden) return;
                        wmOn = false;
                        ensureWatermark();
                    }, CFG.revealMs);
                }
            }

            function styleWatermark(el) {
                el.className = 'bb-watermark';
                el.setAttribute('aria-hidden', 'true');
                el.setAttribute(
                    'style',
                    'position:fixed!important;inset:0!important;z-index:2147483646!important;' +
                    'pointer-events:none!important;display:block!important;visibility:visible!important;' +
                    'opacity:' + (wmOn ? '1' : '0') + '!important;background-image:' + watermarkImage() + '!important;'
                );
            }

            function ensureWatermark() {
                if (!wm || !document.body.contains(wm)) {
                    wm = document.createElement('div');
                    document.body.appendChild(wm);
                    styleWatermark(wm);
                    return;
                }
                var cs = getComputedStyle(wm);
                var wantOpacity = wmOn ? 1 : 0;
                if (cs.display === 'none' || cs.visibility !== 'visible' || Math.abs(parseFloat(cs.opacity) - wantOpacity) > 0.01) {
                    styleWatermark(wm);
                }
            }

            if (wm) styleWatermark(wm);
            ensureWatermark();

            // Rebuild instantly if removed or edited in DevTools
            new MutationObserver(ensureWatermark).observe(document.documentElement, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['style', 'class', 'hidden']
            });
            setInterval(ensureWatermark, 1000); // backup check

            /* ================= POPUP + BLUR ================= */

            function show() {
                modal.hidden = false;
                document.body.classList.add('bb-blur');
                reveal(true);   // keep watermark while the popup is open
            }

            function hide() {
                modal.hidden = true;
                document.body.classList.remove('bb-blur');
                hideWm();
            }

            closeBtn.addEventListener('click', hide);

            function clearClipboard() {
                try {
                    navigator.clipboard.writeText(' ');
                } catch (err) {}
            }

            /* ================= KEYBOARD SHORTCUTS ================= */

            function isShot(e) {
                var k = (e.key || '').toLowerCase();
                var code = e.code || '';
                return (
                    k === 'printscreen' || code === 'PrintScreen' || e.keyCode === 44 ||      // PrintScreen
                    (e.metaKey && e.shiftKey && ['3', '4', '5', 's'].indexOf(k) !== -1) ||   // Win/Cmd + Shift + ...
                    (e.metaKey && e.shiftKey) ||                                               // Win+Shift (Snipping Tool start)
                    (e.ctrlKey && e.shiftKey && k === 's') ||                                  // Ctrl+Shift+S (Edge / Firefox screenshot)
                    (e.metaKey && (k === 'printscreen' || code === 'PrintScreen')) ||          // Win+PrintScreen
                    (e.altKey && (k === 'printscreen' || code === 'PrintScreen'))              // Alt+PrintScreen
                );
            }

            function isPrint(e) {
                var k = (e.key || '').toLowerCase();
                return (e.ctrlKey || e.metaKey) && (k === 'p' || k === 's');   // Ctrl+P / Ctrl+S
            }

            // keydown AND keyup: some browsers only fire one of them for PrintScreen
            ['keydown', 'keyup'].forEach(function(type) {
                document.addEventListener(type, function(e) {
                    if (isShot(e)) {
                        clearClipboard();
                        show();
                    } else if (type === 'keydown' && isPrint(e)) {
                        e.preventDefault();
                        show();
                    }
                }, true);
            });

            // Blur as soon as the Windows / Cmd key is pressed (before the screenshot tool opens)
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Meta' || e.key === 'OS') {
                    document.body.classList.add('bb-blur');
                    reveal(false);
                }
            }, true);

            document.addEventListener('keyup', function(e) {
                if ((e.key === 'Meta' || e.key === 'OS') && modal.hidden) {
                    document.body.classList.remove('bb-blur');
                }
            }, true);

            /* ================= FOCUS LOSS / TAB HIDDEN ================= */

            if (CFG.blurOnFocusLoss) {
                function blurOn() {
                    document.body.classList.add('bb-blur');
                    hideWm();   // leaving the page: watermark is removed
                }

                function blurOff() {
                    if (modal.hidden) {
                        document.body.classList.remove('bb-blur');
                    } else {
                        reveal(true);   // popup still open: show watermark again
                    }
                }

                window.addEventListener('blur', blurOn);
                window.addEventListener('focus', blurOff);

                document.addEventListener('visibilitychange', function() {
                    document.hidden ? blurOn() : blurOff();
                });
            }

            /* ================= RIGHT CLICK / DRAG ================= */

            if (CFG.blockRightClick) {
                document.addEventListener('contextmenu', function(e) {
                    e.preventDefault();
                });
            }

            document.addEventListener('dragstart', function(e) {
                e.preventDefault();
            });

            // Leaving / closing the page: remove the watermark
            window.addEventListener('pagehide', hideWm);

            // Printing from the browser menu
            window.addEventListener('beforeprint', show);
        })();
    </script>
<?php
});