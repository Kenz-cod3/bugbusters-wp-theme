<?php

/**
 * BugBusters - Visitor log
 * Records device brand/model, OS, browser, page, IP, approximate location
 * and (only if the visitor clicks "Allow") the exact location of each visit.
 * Summary is shown in WP Admin > Visitors.
 *
 * Optional (wp-config.php):
 *   define('BB_VISITS_STORE_IP', false);  // do not keep the real IP address
 */

if (!defined('ABSPATH')) {
    exit;
}

// Bumped to 5: adds lat, lng, accuracy, place columns (exact location).
define('BB_VISITS_DB_VERSION', '5');

function bugbusters_visits_table()
{
    global $wpdb;
    return $wpdb->prefix . 'bb_visits';
}

function bugbusters_visits_store_ip()
{
    return !defined('BB_VISITS_STORE_IP') || BB_VISITS_STORE_IP;
}

/* ---------- 1. Create / upgrade the table ---------- */
function bugbusters_visits_install()
{
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $table   = bugbusters_visits_table();
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        visited_at DATETIME NOT NULL,
        page VARCHAR(255) NOT NULL DEFAULT '',
        device VARCHAR(20) NOT NULL DEFAULT '',
        brand VARCHAR(40) NOT NULL DEFAULT '',
        model VARCHAR(60) NOT NULL DEFAULT '',
        os VARCHAR(30) NOT NULL DEFAULT '',
        browser VARCHAR(30) NOT NULL DEFAULT '',
        referrer VARCHAR(255) NOT NULL DEFAULT '',
        ip VARCHAR(45) NOT NULL DEFAULT '',
        ip_hash CHAR(64) NOT NULL DEFAULT '',
        country VARCHAR(60) NOT NULL DEFAULT '',
        region VARCHAR(80) NOT NULL DEFAULT '',
        city VARCHAR(80) NOT NULL DEFAULT '',
        lat DECIMAL(9,6) DEFAULT NULL,
        lng DECIMAL(9,6) DEFAULT NULL,
        accuracy INT DEFAULT NULL,
        place VARCHAR(255) NOT NULL DEFAULT '',
        PRIMARY KEY  (id),
        KEY visited_at (visited_at),
        KEY device (device)
    ) $charset;";

    dbDelta($sql);

    // One-time cleanup: records of files such as /favicon.ico
    foreach (array('ico', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'css', 'js', 'map', 'woff', 'woff2') as $ext) {
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE page LIKE %s",
            '%' . $wpdb->esc_like('.' . $ext)
        ));
    }

    // One-time cleanup: records made by Hostinger's own network (not real visitors)
    $wpdb->query($wpdb->prepare(
        "DELETE FROM $table WHERE ip LIKE %s",
        $wpdb->esc_like('2a02:4780:') . '%'
    ));

    update_option('bb_visits_db_version', BB_VISITS_DB_VERSION);
}
add_action('after_switch_theme', 'bugbusters_visits_install');

// Also creates / upgrades the table automatically (no need to re-activate the theme).
function bugbusters_visits_maybe_install()
{
    if (get_option('bb_visits_db_version') !== BB_VISITS_DB_VERSION) {
        bugbusters_visits_install();
    }
}
add_action('init', 'bugbusters_visits_maybe_install');

/* ---------- 2. Detect device / brand / model / OS / browser ---------- */

/**
 * Asks the browser to also send the phone model (Client Hints).
 * Works on HTTPS sites; Chrome on Android hides the model otherwise.
 */
function bugbusters_visits_client_hints()
{
    if (!is_admin() && !headers_sent()) {
        header('Accept-CH: Sec-CH-UA-Model');
    }
}
add_action('template_redirect', 'bugbusters_visits_client_hints', 1);

function bugbusters_visits_brand_from_model($model)
{
    $map = array(
        '/^(SM-|GT-|SAMSUNG)/i'                             => 'Samsung',
        '/^(CPH|OPPO|PH[A-Z]M)/i'                           => 'OPPO',
        '/^(RMX|realme)/i'                                  => 'Realme',
        '/^(V\d{4}|vivo)/i'                                 => 'vivo',
        '/^(Redmi|POCO|Mi |MI |Xiaomi|\d{5,}[A-Z0-9]{2,})/' => 'Xiaomi',
        '/infinix/i'                                        => 'Infinix',
        '/tecno/i'                                          => 'TECNO',
        '/itel/i'                                           => 'itel',
        '/^Pixel/i'                                         => 'Google',
        '/^(HUAWEI|[A-Z]{3}-[A-Z]{1,2}\d{2})/'              => 'Huawei',
        '/honor/i'                                          => 'Honor',
        '/^(moto|XT\d{4})/i'                                => 'Motorola',
        '/^(OnePlus|[A-Z]{2}\d{4}$)/'                       => 'OnePlus',
        '/nokia/i'                                          => 'Nokia',
        '/^(Sony|XQ-|SO-|SOV)/i'                            => 'Sony',
        '/^(ASUS|ZS\d)/i'                                   => 'ASUS',
        '/lenovo/i'                                         => 'Lenovo',
    );
    foreach ($map as $pattern => $brand) {
        if (preg_match($pattern, $model)) {
            return $brand;
        }
    }
    return '';
}

function bugbusters_visits_parse_ua($ua)
{
    // Device type
    if (preg_match('/ipad|tablet|kindle|silk|android(?!.*mobile)/i', $ua)) {
        $device = 'Tablet';
    } elseif (preg_match('/mobi|iphone|ipod|android|blackberry|opera mini|iemobile/i', $ua)) {
        $device = 'Mobile';
    } else {
        $device = 'Desktop';
    }

    // Operating system (order matters: Android/iOS before Linux/macOS)
    if (preg_match('/windows/i', $ua))                  $os = 'Windows';
    elseif (preg_match('/android/i', $ua))              $os = 'Android';
    elseif (preg_match('/iphone|ipad|ipod/i', $ua))     $os = 'iOS';
    elseif (preg_match('/macintosh|mac os x/i', $ua))   $os = 'macOS';
    elseif (preg_match('/cros/i', $ua))                 $os = 'ChromeOS';
    elseif (preg_match('/linux/i', $ua))                $os = 'Linux';
    else                                                $os = 'Other';

    // Browser (order matters: Edge/Opera/Chrome all contain "Chrome" or "Safari")
    if (preg_match('/edg\//i', $ua))                    $browser = 'Edge';
    elseif (preg_match('/opr\/|opera/i', $ua))          $browser = 'Opera';
    elseif (preg_match('/firefox|fxios/i', $ua))        $browser = 'Firefox';
    elseif (preg_match('/chrome|crios/i', $ua))         $browser = 'Chrome';
    elseif (preg_match('/safari/i', $ua))               $browser = 'Safari';
    else                                                $browser = 'Other';

    // Brand + model
    $brand = '';
    $model = '';

    // Phone model from Client Hints (works even when Chrome is in "Desktop site" mode)
    $hint_model = '';
    if (!empty($_SERVER['HTTP_SEC_CH_UA_MODEL'])) {
        $hint_model = trim(sanitize_text_field(wp_unslash($_SERVER['HTTP_SEC_CH_UA_MODEL'])), " \t\n\r\0\x0B\"");
    }
    // Chrome "Desktop site" on a phone: a real phone model is present, so treat it as Android mobile
    if ($hint_model !== '' && strlen($hint_model) > 2 && $os !== 'iOS' && $os !== 'macOS' && $os !== 'Windows') {
        $os     = 'Android';
        $device = 'Mobile';
    }

    if ($os === 'Android') {
        $model = $hint_model;
        if ($model === '' && preg_match('/Android [^;]+;\s*([^;)]+?)(?:\s+Build|\))/i', $ua, $m)) {
            $model = trim(str_ireplace(' wv', '', $m[1]));
        }
        // Chrome hides the model as a single letter such as "K"
        if (strlen($model) <= 2) {
            $model = '';
        }
        $brand = $model !== '' ? bugbusters_visits_brand_from_model($model) : '';
        if ($brand === '') {
            $brand = 'Android';
        }
    } elseif ($os === 'iOS') {
        $brand = 'Apple';
        $model = preg_match('/ipad/i', $ua) ? 'iPad' : (preg_match('/ipod/i', $ua) ? 'iPod' : 'iPhone');
    } elseif ($os === 'macOS') {
        $brand = 'Apple';
        $model = 'Mac';
    } elseif ($os === 'Windows') {
        $brand = 'Windows PC';
    } elseif ($os === 'ChromeOS') {
        $brand = 'Chromebook';
    } elseif ($os === 'Linux') {
        $brand = 'Linux PC';
    }

    return array(
        'device'  => $device,
        'os'      => $os,
        'browser' => $browser,
        'brand'   => mb_substr($brand, 0, 40),
        'model'   => mb_substr($model, 0, 60),
    );
}

function bugbusters_visits_is_bot($ua)
{
    if ($ua === '') {
        return true;
    }
    return (bool) preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|curl|wget|python|headless|lighthouse/i', $ua);
}

/* ---------- 3. Visitor IP + approximate location lookup ---------- */
function bugbusters_visits_get_ip()
{
    $ip = '';
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {            // behind Cloudflare
        $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP']));
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
}

function bugbusters_visits_is_public_ip($ip)
{
    return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
}

/**
 * Runs in the background (WP-Cron) so visitors never wait for it.
 */
function bugbusters_visits_geo_lookup($row_id, $ip)
{
    if (!bugbusters_visits_is_public_ip($ip)) {
        return;
    }

    $cache_key = 'bb_geo_' . md5($ip);
    $geo       = get_transient($cache_key);

    if (!is_array($geo)) {
        $response = wp_remote_get(
            'https://ipwho.is/' . rawurlencode($ip) . '?fields=success,country,region,city',
            array('timeout' => 8)
        );
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return;
        }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data['success'])) {
            return;
        }
        $geo = array(
            'country' => isset($data['country']) ? (string) $data['country'] : '',
            'region'  => isset($data['region']) ? (string) $data['region'] : '',
            'city'    => isset($data['city']) ? (string) $data['city'] : '',
        );
        set_transient($cache_key, $geo, 7 * DAY_IN_SECONDS);
    }

    global $wpdb;
    $wpdb->update(
        bugbusters_visits_table(),
        array(
            'country' => mb_substr(sanitize_text_field($geo['country']), 0, 60),
            'region'  => mb_substr(sanitize_text_field($geo['region']), 0, 80),
            'city'    => mb_substr(sanitize_text_field($geo['city']), 0, 80),
        ),
        array('id' => (int) $row_id),
        array('%s', '%s', '%s'),
        array('%d')
    );
}
add_action('bb_visits_geo_event', 'bugbusters_visits_geo_lookup', 10, 2);

/* ---------- 4. Record the visit ---------- */
function bugbusters_visits_log()
{
    if (is_admin() || is_user_logged_in() || is_feed() || is_robots() || is_404() || wp_doing_ajax()) {
        return; // do not count yourself or the dashboard
    }
    if (function_exists('is_favicon') && is_favicon()) {
        return; // the browser tab icon is not a real visit
    }
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return;
    }
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
        return;
    }

    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
    if (bugbusters_visits_is_bot($ua)) {
        return;
    }
    if (preg_match('/WordPress\//i', $ua)) {
        return; // WordPress internal requests (wp_remote_get, WP-Cron, chatbot auto-learning)
    }

    $ip      = bugbusters_visits_get_ip();
    $ip_hash = hash('sha256', $ip . wp_salt('auth')); // used for "unique visitors"

    // Skip requests made by the website's own server
    $server_ip = isset($_SERVER['SERVER_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_ADDR'])) : '';
    if ($ip !== '' && $ip === $server_ip) {
        return;
    }
    if (strpos($ip, '2a02:4780:') === 0) {
        return; // Hostinger's own network
    }

    $uri  = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '/';
    $page = mb_substr(strtok($uri, '?'), 0, 255);

    // Skip files such as favicon.ico, images, css, js
    if (preg_match('/\.(ico|png|jpe?g|gif|svg|webp|css|js|map|woff2?)$/i', $page)) {
        return;
    }

    $info = bugbusters_visits_parse_ua($ua);

    global $wpdb;
    $table = bugbusters_visits_table();

    // Same visitor + same page within 30 minutes = counted once (ignores refreshes).
    // If the phone model arrives on a later request, fill it into the existing row.
    $dedupe   = 'bb_v_' . md5($ip_hash . $page);
    $existing = get_transient($dedupe);
    if ($existing) {
        if ((int) $existing > 0) {
            // Remember the row so the "Allow location" script can update it
            $GLOBALS['bb_visit'] = array('id' => (int) $existing, 'hash' => $ip_hash);
        }
        if ((int) $existing > 0 && $info['model'] !== '') {
            $wpdb->query($wpdb->prepare(
                "UPDATE $table SET brand = %s, model = %s, os = %s, device = %s WHERE id = %d AND model = ''",
                $info['brand'],
                $info['model'],
                $info['os'],
                $info['device'],
                (int) $existing
            ));
        }
        return;
    }

    $ref = '';
    if (!empty($_SERVER['HTTP_REFERER'])) {
        $ref_url  = esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER']));
        $ref_host = wp_parse_url($ref_url, PHP_URL_HOST);
        $own_host = wp_parse_url(home_url('/'), PHP_URL_HOST);
        if ($ref_host && $ref_host !== $own_host) {
            $ref = mb_substr($ref_host, 0, 255); // only the site name, not the full URL
        }
    }

    $wpdb->insert(
        $table,
        array(
            'visited_at' => current_time('mysql'),
            'page'       => $page,
            'device'     => $info['device'],
            'brand'      => $info['brand'],
            'model'      => $info['model'],
            'os'         => $info['os'],
            'browser'    => $info['browser'],
            'referrer'   => $ref,
            'ip'         => bugbusters_visits_store_ip() ? $ip : '',
            'ip_hash'    => $ip_hash,
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
    );

    $row_id = (int) $wpdb->insert_id;
    set_transient($dedupe, $row_id > 0 ? $row_id : 1, 30 * MINUTE_IN_SECONDS);

    if ($row_id > 0) {
        $GLOBALS['bb_visit'] = array('id' => $row_id, 'hash' => $ip_hash);
    }

    // Look up the approximate location in the background (skipped for localhost / private IPs).
    if ($row_id > 0 && $ip !== '' && bugbusters_visits_is_public_ip($ip)) {
        wp_schedule_single_event(time() + 2, 'bb_visits_geo_event', array($row_id, $ip));
    }
}
add_action('template_redirect', 'bugbusters_visits_log');

/* ---------- 4b. Exact location (visitor must click "Allow") ---------- */

// Endpoint: POST /wp-json/bugbusters/v1/location
function bugbusters_visits_geo_route()
{
    register_rest_route('bugbusters/v1', '/location', array(
        'methods'             => 'POST',
        'callback'            => 'bugbusters_visits_geo_save',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'bugbusters_visits_geo_route');

function bugbusters_visits_geo_save(WP_REST_Request $request)
{
    $id    = (int) $request->get_param('id');
    $token = (string) $request->get_param('token');
    $lat   = (float) $request->get_param('lat');
    $lng   = (float) $request->get_param('lng');
    $acc   = (int) $request->get_param('acc');

    $ip      = bugbusters_visits_get_ip();
    $ip_hash = hash('sha256', $ip . wp_salt('auth'));

    // The token proves this browser is the one that made the visit
    if ($id <= 0 || !hash_equals(wp_hash($id . '|' . $ip_hash), $token)) {
        return new WP_REST_Response(array('ok' => false), 403);
    }
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        return new WP_REST_Response(array('ok' => false), 400);
    }

    global $wpdb;
    $table = bugbusters_visits_table();
    $wpdb->query($wpdb->prepare(
        "UPDATE $table SET lat = %f, lng = %f, accuracy = %d WHERE id = %d AND lat IS NULL",
        $lat,
        $lng,
        max(0, $acc),
        $id
    ));

    // Find the place name (barangay / town) in the background
    wp_schedule_single_event(time() + 2, 'bb_visits_place_event', array($id, $lat, $lng));

    return array('ok' => true);
}

// Background: convert coordinates to a place name (OpenStreetMap Nominatim)
function bugbusters_visits_place_lookup($row_id, $lat, $lng)
{
    $url = add_query_arg(array(
        'format'          => 'jsonv2',
        'lat'             => $lat,
        'lon'             => $lng,
        'zoom'            => 16,
        'accept-language' => 'en',
    ), 'https://nominatim.openstreetmap.org/reverse');

    $response = wp_remote_get($url, array(
        'timeout' => 8,
        'headers' => array('User-Agent' => 'BugBustersPortfolio/1.0 (' . home_url('/') . ')'),
    ));
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return;
    }
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($data['address'])) {
        return;
    }

    $a     = $data['address'];
    $parts = array();
    foreach (array('neighbourhood', 'suburb', 'village', 'town', 'city', 'municipality', 'county', 'state') as $k) {
        if (!empty($a[$k]) && !in_array($a[$k], $parts, true)) {
            $parts[] = $a[$k];
        }
    }
    if (empty($parts)) {
        return;
    }

    global $wpdb;
    $wpdb->update(
        bugbusters_visits_table(),
        array('place' => mb_substr(sanitize_text_field(implode(', ', $parts)), 0, 255)),
        array('id' => (int) $row_id),
        array('%s'),
        array('%d')
    );
}
add_action('bb_visits_place_event', 'bugbusters_visits_place_lookup', 10, 3);

// The "Allow location?" banner + script (printed in the footer)
function bugbusters_visits_geo_footer()
{
    if (empty($GLOBALS['bb_visit'])) {
        return;
    }
    $v   = $GLOBALS['bb_visit'];
    $cfg = array(
        'url'   => esc_url_raw(rest_url('bugbusters/v1/location')),
        'id'    => (int) $v['id'],
        'token' => wp_hash($v['id'] . '|' . $v['hash']),
    );
?>
    <script>
        (function() {
            var c = <?php echo wp_json_encode($cfg); ?>;
            if (!navigator.geolocation) return;

            var saved = null;
            try {
                saved = localStorage.getItem('bb-geo');
            } catch (e) {}
            if (saved === 'no') return;

            function send() {
                navigator.geolocation.getCurrentPosition(function(p) {
                    fetch(c.url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: c.id,
                            token: c.token,
                            lat: p.coords.latitude,
                            lng: p.coords.longitude,
                            acc: Math.round(p.coords.accuracy)
                        })
                    }).catch(function() {});
                }, function() {}, {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 60000
                });
            }

            // Already allowed before: just send the location again
            if (saved === 'yes') {
                send();
                return;
            }

            var box = document.createElement('div');
            box.setAttribute('role', 'dialog');
            box.setAttribute('aria-label', 'Location request');
            box.style.cssText = 'position:fixed;left:16px;bottom:16px;z-index:9999;max-width:320px;padding:16px;border-radius:16px;background:#fff;color:#12261f;box-shadow:0 10px 30px rgba(0,0,0,.18);font:500 14px/1.5 Manrope,system-ui,sans-serif;';
            box.innerHTML = '<p style="margin:0 0 12px;">Allow us to see your approximate location? It is used only to understand where our visitors come from.</p>' +
                '<div style="display:flex;gap:8px;">' +
                '<button type="button" id="bb-geo-yes" style="flex:1;padding:8px 12px;border:0;border-radius:999px;background:#0b7a52;color:#fff;font:inherit;cursor:pointer;">Allow</button>' +
                '<button type="button" id="bb-geo-no" style="flex:1;padding:8px 12px;border:1px solid #cfd8d3;border-radius:999px;background:#fff;color:inherit;font:inherit;cursor:pointer;">No thanks</button>' +
                '</div>';
            document.body.appendChild(box);

            document.getElementById('bb-geo-yes').onclick = function() {
                try {
                    localStorage.setItem('bb-geo', 'yes');
                } catch (e) {}
                box.remove();
                send();
            };
            document.getElementById('bb-geo-no').onclick = function() {
                try {
                    localStorage.setItem('bb-geo', 'no');
                } catch (e) {}
                box.remove();
            };
        })();
    </script>
<?php
}
add_action('wp_footer', 'bugbusters_visits_geo_footer');

/* ---------- 5. Auto-delete old records (older than 180 days) ---------- */
function bugbusters_visits_cleanup()
{
    global $wpdb;
    $table = bugbusters_visits_table();
    $wpdb->query($wpdb->prepare(
        "DELETE FROM $table WHERE visited_at < %s",
        gmdate('Y-m-d H:i:s', strtotime('-180 days', current_time('timestamp')))
    ));
}
add_action('bb_visits_cleanup_event', 'bugbusters_visits_cleanup');

function bugbusters_visits_schedule_cleanup()
{
    if (!wp_next_scheduled('bb_visits_cleanup_event')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'bb_visits_cleanup_event');
    }
}
add_action('init', 'bugbusters_visits_schedule_cleanup');

/* ---------- 6. Admin page: WP Admin > Visitors ---------- */
function bugbusters_visits_menu()
{
    add_menu_page(
        'Visitors',
        'Visitors',
        'manage_options',
        'bb-visitors',
        'bugbusters_visits_page',
        'dashicons-chart-bar',
        30
    );
}
add_action('admin_menu', 'bugbusters_visits_menu');

function bugbusters_visits_group($column)
{
    global $wpdb;
    $table   = bugbusters_visits_table();
    $allowed = array('device', 'brand', 'os', 'browser', 'referrer', 'page', 'country', 'city');
    if (!in_array($column, $allowed, true)) {
        return array();
    }
    return $wpdb->get_results("SELECT $column AS label, COUNT(*) AS total FROM $table GROUP BY $column ORDER BY total DESC LIMIT 10");
}

function bugbusters_visits_location_text($v)
{
    $parts = array_filter(array($v->city, $v->region, $v->country));
    if (!empty($parts)) {
        return implode(', ', $parts);
    }
    if ($v->ip !== '' && !bugbusters_visits_is_public_ip($v->ip)) {
        return 'Localhost / private network';
    }
    return '(not available yet)';
}

function bugbusters_visits_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;
    $table = bugbusters_visits_table();

    $total  = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
    $unique = (int) $wpdb->get_var("SELECT COUNT(DISTINCT ip_hash) FROM $table");
    $today  = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE visited_at >= %s",
        current_time('Y-m-d') . ' 00:00:00'
    ));
    $recent = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 50");

    $blocks = array(
        'Devices'           => bugbusters_visits_group('device'),
        'Brands'            => bugbusters_visits_group('brand'),
        'Operating systems' => bugbusters_visits_group('os'),
        'Browsers'          => bugbusters_visits_group('browser'),
        'Countries'         => bugbusters_visits_group('country'),
        'Cities'            => bugbusters_visits_group('city'),
        'Top pages'         => bugbusters_visits_group('page'),
        'Referrers'         => bugbusters_visits_group('referrer'),
    );
?>
    <div class="wrap">
        <h1>Visitors</h1>
        <p>
            <strong>Total visits:</strong> <?php echo esc_html($total); ?> &nbsp;|&nbsp;
            <strong>Unique visitors:</strong> <?php echo esc_html($unique); ?> &nbsp;|&nbsp;
            <strong>Today:</strong> <?php echo esc_html($today); ?>
        </p>

        <div style="display:flex;flex-wrap:wrap;gap:16px;margin:16px 0;">
            <?php foreach ($blocks as $title => $rows) : ?>
                <table class="widefat striped" style="width:260px;">
                    <thead>
                        <tr>
                            <th colspan="2"><?php echo esc_html($title); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)) : ?>
                            <tr>
                                <td colspan="2">No data yet</td>
                            </tr>
                            <?php else : foreach ($rows as $r) :
                                $empty_label = ($title === 'Referrers') ? '(direct)' : '(unknown)';
                            ?>
                                <tr>
                                    <td><?php echo esc_html($r->label !== '' ? $r->label : $empty_label); ?></td>
                                    <td><strong><?php echo esc_html($r->total); ?></strong></td>
                                </tr>
                        <?php endforeach;
                        endif; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        </div>

        <h2>Last 50 visits</h2>
        <div style="overflow-x:auto;">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Date &amp; time</th>
                        <th>Page</th>
                        <th>Device</th>
                        <th>Brand / Model</th>
                        <th>OS</th>
                        <th>Browser</th>
                        <th>IP address</th>
                        <th>Location</th>
                        <th>Exact location</th>
                        <th>Came from</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent)) : ?>
                        <tr>
                            <td colspan="10">No visits recorded yet. Open your site in a private window to test.</td>
                        </tr>
                        <?php else : foreach ($recent as $v) :
                            $bm = trim($v->brand . ' ' . $v->model);
                        ?>
                            <tr>
                                <td><?php echo esc_html($v->visited_at); ?></td>
                                <td><?php echo esc_html($v->page); ?></td>
                                <td><?php echo esc_html($v->device); ?></td>
                                <td><?php echo esc_html($bm !== '' ? $bm : '(unknown)'); ?></td>
                                <td><?php echo esc_html($v->os); ?></td>
                                <td><?php echo esc_html($v->browser); ?></td>
                                <td><?php echo esc_html($v->ip !== '' ? $v->ip : '(not stored)'); ?></td>
                                <td><?php echo esc_html(bugbusters_visits_location_text($v)); ?></td>
                                <td>
                                    <?php if ($v->lat !== null && $v->lng !== null) : ?>
                                        <a href="<?php echo esc_url('https://www.google.com/maps?q=' . $v->lat . ',' . $v->lng); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php echo esc_html($v->place !== '' ? $v->place : $v->lat . ', ' . $v->lng); ?>
                                        </a>
                                        <br><small>&plusmn;<?php echo esc_html((int) $v->accuracy); ?> m</small>
                                    <?php else : ?>
                                        (not shared)
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($v->referrer !== '' ? $v->referrer : '(direct)'); ?></td>
                            </tr>
                    <?php endforeach;
                    endif; ?>
                </tbody>
            </table>
        </div>

        <p class="description" style="margin-top:12px;">
            Location is approximate (based on IP address) and may show the internet provider's city.
            Exact location appears only when the visitor clicks "Allow".
            Brand/model is only available when the browser shares it.
        </p>
    </div>
<?php
}