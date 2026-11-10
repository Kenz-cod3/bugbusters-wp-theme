<?php

/**
 * BugBusters - Block devices
 * Blocks visitors (by IP or phone model) from opening the website.
 * Manage the list in WP Admin > Visitors > Blocked devices.
 *
 * Emergency switch (wp-config.php): define('BB_BLOCK_DISABLED', true);
 */

if (!defined('ABSPATH')) {
    exit;
}

function bugbusters_block_list()
{
    $list = get_option('bb_blocked_devices', array());
    return is_array($list) ? $list : array();
}

/**
 * IPv4 stays as is. IPv6 is reduced to its first 64 bits, because phones
 * change the last part of their IPv6 address all the time.
 */
function bugbusters_block_normalize_ip($ip)
{
    $ip = trim((string) $ip);
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return '';
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $bin = inet_pton($ip);
        if ($bin === false) {
            return '';
        }
        return implode(':', str_split(bin2hex(substr($bin, 0, 8)), 4)) . '::/64';
    }
    return $ip;
}

function bugbusters_block_current_ip()
{
    if (function_exists('bugbusters_visits_get_ip')) {
        return bugbusters_visits_get_ip();
    }
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
}

function bugbusters_block_is_blocked()
{
    $list = bugbusters_block_list();
    if (empty($list)) {
        return false;
    }

    $ip_key = bugbusters_block_normalize_ip(bugbusters_block_current_ip());

    $has_model_rule = false;
    foreach ($list as $item) {
        if ($item['type'] === 'ip' && $ip_key !== '' && $item['value'] === $ip_key) {
            return true;
        }
        if ($item['type'] === 'model') {
            $has_model_rule = true;
        }
    }

    if ($has_model_rule && function_exists('bugbusters_visits_parse_ua')) {
        $ua    = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
        $info  = bugbusters_visits_parse_ua($ua);
        $model = $info['model'];
        if ($model !== '') {
            foreach ($list as $item) {
                if ($item['type'] === 'model' && stripos($model, $item['value']) !== false) {
                    return true;
                }
            }
        }
    }

    return false;
}

/* ---------- Enforce the block (before anything else is shown or logged) ---------- */
function bugbusters_block_enforce()
{
    if (defined('BB_BLOCK_DISABLED') && BB_BLOCK_DISABLED) {
        return;
    }
    if (is_admin() || wp_doing_cron() || (defined('WP_CLI') && WP_CLI)) {
        return;
    }

    global $pagenow;
    if ($pagenow === 'wp-login.php') {
        return; // never lock anyone out of the login page
    }
    if (is_user_logged_in()) {
        return; // never block the admin
    }

    if (bugbusters_block_is_blocked()) {
        nocache_headers();
        wp_die(
            'Access to this website is not available from your device.',
            'Access denied',
            array('response' => 403)
        );
    }
}
add_action('init', 'bugbusters_block_enforce', 1);

/* ---------- Admin: Visitors > Blocked devices ---------- */
function bugbusters_block_menu()
{
    add_submenu_page(
        'bb-visitors',
        'Blocked devices',
        'Blocked devices',
        'manage_options',
        'bb-blocked',
        'bugbusters_block_page'
    );
}
add_action('admin_menu', 'bugbusters_block_menu', 20);

function bugbusters_block_actions()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $back = admin_url('admin.php?page=bb-blocked');

    // Add
    if (isset($_POST['bb_block_add']) && check_admin_referer('bb_block_add', 'bb_block_nonce')) {
        $type = (isset($_POST['bb_block_type']) && $_POST['bb_block_type'] === 'model') ? 'model' : 'ip';
        $raw  = isset($_POST['bb_block_value']) ? sanitize_text_field(wp_unslash($_POST['bb_block_value'])) : '';
        $note = isset($_POST['bb_block_note']) ? sanitize_text_field(wp_unslash($_POST['bb_block_note'])) : '';

        $value = ($type === 'ip') ? bugbusters_block_normalize_ip($raw) : trim($raw);
        if ($value === '' || ($type === 'model' && strlen($value) < 3)) {
            wp_safe_redirect(add_query_arg('msg', 'invalid', $back));
            exit;
        }

        // Safety: do not let the admin block the network they are using right now
        if ($type === 'ip' && $value === bugbusters_block_normalize_ip(bugbusters_block_current_ip())) {
            wp_safe_redirect(add_query_arg('msg', 'self', $back));
            exit;
        }

        $list = bugbusters_block_list();
        $id   = md5($type . '|' . $value);
        if (isset($list[$id])) {
            wp_safe_redirect(add_query_arg('msg', 'exists', $back));
            exit;
        }
        $list[$id] = array(
            'type'  => $type,
            'value' => $value,
            'note'  => mb_substr($note, 0, 100),
            'added' => current_time('mysql'),
        );
        update_option('bb_blocked_devices', $list, false);
        wp_safe_redirect(add_query_arg('msg', 'added', $back));
        exit;
    }

    // Remove (unblock)
    if (isset($_POST['bb_block_remove']) && check_admin_referer('bb_block_remove', 'bb_block_nonce')) {
        $id   = sanitize_text_field(wp_unslash($_POST['bb_block_remove']));
        $list = bugbusters_block_list();
        unset($list[$id]);
        update_option('bb_blocked_devices', $list, false);
        wp_safe_redirect(add_query_arg('msg', 'removed', $back));
        exit;
    }
}
add_action('admin_init', 'bugbusters_block_actions');

function bugbusters_block_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $list = bugbusters_block_list();

    // Prefill from the "Block" links in the Visitors page
    $pre_type  = (isset($_GET['type']) && $_GET['type'] === 'model') ? 'model' : 'ip';
    $pre_value = isset($_GET['value']) ? sanitize_text_field(wp_unslash($_GET['value'])) : '';

    $messages = array(
        'added'   => array('success', 'Device blocked.'),
        'removed' => array('success', 'Device unblocked.'),
        'exists'  => array('warning', 'That device is already in the list.'),
        'invalid' => array('error', 'Please enter a valid IP address or a phone model (at least 3 characters).'),
        'self'    => array('error', 'That is the network you are using right now, so it was not blocked.'),
    );
    $msg = isset($_GET['msg']) ? sanitize_key($_GET['msg']) : '';
?>
    <div class="wrap">
        <h1>Blocked devices</h1>

        <?php if (isset($messages[$msg])) : ?>
            <div class="notice notice-<?php echo esc_attr($messages[$msg][0]); ?> is-dismissible">
                <p><?php echo esc_html($messages[$msg][1]); ?></p>
            </div>
        <?php endif; ?>

        <p>Blocked devices see "Access denied" when they open the website. You (when logged in to WordPress) are never blocked.</p>

        <h2>Block a device</h2>
        <form method="post" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:24px;">
            <?php wp_nonce_field('bb_block_add', 'bb_block_nonce'); ?>
            <select name="bb_block_type">
                <option value="ip" <?php selected($pre_type, 'ip'); ?>>IP address</option>
                <option value="model" <?php selected($pre_type, 'model'); ?>>Phone model</option>
            </select>
            <input type="text" name="bb_block_value" value="<?php echo esc_attr($pre_value); ?>" placeholder="IP or model (e.g. 2201117TG)" style="min-width:280px;" required>
            <input type="text" name="bb_block_note" placeholder="Note (optional)" style="min-width:200px;">
            <button type="submit" name="bb_block_add" value="1" class="button button-primary">Block</button>
        </form>

        <h2>Blocked list</h2>
        <table class="widefat striped" style="max-width:900px;">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Note</th>
                    <th>Added</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list)) : ?>
                    <tr>
                        <td colspan="5">No blocked devices.</td>
                    </tr>
                    <?php else : foreach ($list as $id => $item) : ?>
                        <tr>
                            <td><?php echo esc_html($item['type'] === 'ip' ? 'IP' : 'Phone model'); ?></td>
                            <td><code><?php echo esc_html($item['value']); ?></code></td>
                            <td><?php echo esc_html($item['note']); ?></td>
                            <td><?php echo esc_html($item['added']); ?></td>
                            <td>
                                <form method="post">
                                    <?php wp_nonce_field('bb_block_remove', 'bb_block_nonce'); ?>
                                    <button type="submit" name="bb_block_remove" value="<?php echo esc_attr($id); ?>" class="button button-small">Unblock</button>
                                </form>
                            </td>
                        </tr>
                <?php endforeach;
                endif; ?>
            </tbody>
        </table>

        <p class="description" style="margin-top:12px;max-width:900px;">
            IPv6 addresses (common on mobile data) are blocked by their first 64 bits, because phones keep changing the end of the address.
            Blocking a phone model blocks every phone of that model, and it only works when the browser shares the model.
        </p>
    </div>
<?php
}