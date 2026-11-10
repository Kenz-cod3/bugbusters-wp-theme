<?php

/**
 * BugBusters - Visitor log
 * Records device brand/model, OS, browser, page, IP and approximate
 * location of each visit, and shows a summary in WP Admin > Visitors.
 *
 * Optional (wp-config.php):
 *   define('BB_VISITS_STORE_IP', false);  // do not keep the real IP address
 */

if (!defined('ABSPATH')) {
    exit;
}

// Bumped to 3: adds brand, model, ip, country, region, city columns.
define('BB_VISITS_DB_VERSION', '3');

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
        PRIMARY KEY  (id),
        KEY visited_at (visited_at),
        KEY device (device)
    ) $charset;";

    dbDelta($sql);

    // One-time cleanup: remove old records of files such as /favicon.ico
    foreach (array('ico', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'css', 'js', 'map', 'woff', 'woff2') as $ext) {
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE page LIKE %s",
            '%' . $wpdb->esc_like('.' . $ext)
        ));
    }

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
        '/^(SM-|GT-|SAMSUNG)/i'                     => 'Samsung',
        '/^(CPH|OPPO|PH[A-Z]M)/i'                   => 'OPPO',
        '/^(RMX|realme)/i'                          => 'Realme',
        '/^(V\d{4}|vivo)/i'                         => 'vivo',
        '/^(Redmi|POCO|Mi |MI |Xiaomi|\d{5,}[A-Z0-9]{2,})/' => 'Xiaomi',
        '/infinix/i'                                => 'Infinix',
        '/tecno/i'                                  => 'TECNO',
        '/itel/i'                                   => 'itel',
        '/^Pixel/i'                                 => 'Google',
        '/^(HUAWEI|[A-Z]{3}-[A-Z]{1,2}\d{2})/'      => 'Huawei',
        '/honor/i'                                  => 'Honor',
        '/^(moto|XT\d{4})/i'                        => 'Motorola',
        '/^(OnePlus|[A-Z]{2}\d{4}$)/'               => 'OnePlus',
        '/nokia/i'                                  => 'Nokia',
        '/^(Sony|XQ-|SO-|SOV)/i'                    => 'Sony',
        '/^(ASUS|ZS\d)/i'                           => 'ASUS',
        '/lenovo/i'                                 => 'Lenovo',
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

    if ($os === 'Android') {
        // 1) Client Hint (most reliable when the browser sends it)
        if (!empty($_SERVER['HTTP_SEC_CH_UA_MODEL'])) {
            $model = trim(sanitize_text_field(wp_unslash($_SERVER['HTTP_SEC_CH_UA_MODEL'])), " \t\n\r\0\x0B\"");
        }
        // 2) Model written inside the User-Agent
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

/* ---------- 3. Visitor IP + location lookup ---------- */
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

    $ip      = bugbusters_visits_get_ip();
    $ip_hash = hash('sha256', $ip . wp_salt('auth')); // used for "unique visitors"

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
        if ((int) $existing > 0 && $info['model'] !== '') {
            $wpdb->query($wpdb->prepare(
                "UPDATE $table SET brand = %s, model = %s WHERE id = %d AND model = ''",
                $info['brand'],
                $info['model'],
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

    // Look up the location in the background (skipped for localhost / private IPs).
    if ($row_id > 0 && $ip !== '' && bugbusters_visits_is_public_ip($ip)) {
        wp_schedule_single_event(time() + 2, 'bb_visits_geo_event', array($row_id, $ip));
    }
}
add_action('template_redirect', 'bugbusters_visits_log');

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
                        <th>Came from</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent)) : ?>
                        <tr>
                            <td colspan="9">No visits recorded yet. Open your site in a private window to test.</td>
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
                                <td><?php echo esc_html($v->referrer !== '' ? $v->referrer : '(direct)'); ?></td>
                            </tr>
                    <?php endforeach;
                    endif; ?>
                </tbody>
            </table>
        </div>

        <p class="description" style="margin-top:12px;">
            Location is approximate (based on IP address) and may show the internet provider's city.
            Brand/model is only available when the browser shares it.
        </p>
    </div>
<?php
}
