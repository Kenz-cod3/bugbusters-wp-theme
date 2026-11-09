<?php
/**
 * BugBusters Custom – theme functions
 */

function bugbusters_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('style', 'script', 'search-form', 'gallery', 'caption'));

    register_nav_menus(array(
        'primary' => 'Primary Menu',
    ));
}
add_action('after_setup_theme', 'bugbusters_theme_setup');

/**
 * Apply the saved (or system) color theme BEFORE the page paints,
 * so there is no white flash when dark mode is on.
 */
function bugbusters_theme_init_script() {
    ?>
    <script>
    (function () {
        var theme = null;

        try {
            theme = localStorage.getItem('bb-theme');
        } catch (e) {}

        if (theme !== 'dark' && theme !== 'light') {
            theme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)
                ? 'dark'
                : 'light';
        }

        document.documentElement.setAttribute('data-theme', theme);
    })();
    </script>
    <?php
}
add_action('wp_head', 'bugbusters_theme_init_script', 1);

/**
 * Load theme styles and scripts.
 * Automatically update version when files change.
 */
function bugbusters_enqueue_assets() {

    // Google Font
    wp_enqueue_style(
        'bugbusters-fonts',
        'https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap',
        array(),
        null
    );

    // Main stylesheet
    wp_enqueue_style(
        'bugbusters-style',
        get_stylesheet_uri(),
        array('bugbusters-fonts'),
        filemtime(get_stylesheet_directory() . '/style.css')
    );

    // Main JavaScript
    wp_enqueue_script(
        'bugbusters-main',
        get_theme_file_uri('/js/main.js'),
        array(),
        filemtime(get_theme_file_path('/js/main.js')),
        true
    );
}
add_action('wp_enqueue_scripts', 'bugbusters_enqueue_assets');

/**
 * Pages the theme needs. Each one is created automatically if missing.
 * slug => array(title, template file)
 */
function bugbusters_required_pages() {
    return array(
        'contact' => array('Contact', 'page-contact.php'),
        'justine' => array('Justine Harvey Jaraula', 'teamprofile/page-justine.php'),
        'john'    => array('John Micheal Jaranilla', 'teamprofile/page-john.php'),
        'kenneth' => array('Kenneth Carl Milarpis', 'teamprofile/page-kenneth.php'),
        'angel'   => array('Angel Grace Salinga', 'teamprofile/page-angel.php'),
    );
}

/**
 * Finds a published page: first by its template, then by its slug.
 * Returns a WP_Post or null.
 */
function bugbusters_find_page($slug) {
    static $cache = array();
    if (array_key_exists($slug, $cache)) {
        return $cache[$slug];
    }

    $cache[$slug] = null;
    $pages        = bugbusters_required_pages();
    if (!isset($pages[$slug])) {
        return null;
    }

    $found = get_posts(array(
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'meta_key'       => '_wp_page_template',
        'meta_value'     => $pages[$slug][1],
        'posts_per_page' => 1,
        'no_found_rows'  => true,
    ));

    if (!empty($found)) {
        $cache[$slug] = $found[0];
        return $cache[$slug];
    }

    $page = get_page_by_path($slug);
    if ($page && $page->post_status === 'publish') {
        $cache[$slug] = $page;

        // Page made earlier with another template path? Point it to the right file.
        if (get_post_meta($page->ID, '_wp_page_template', true) !== $pages[$slug][1]
            && $slug !== 'contact') {
            update_post_meta($page->ID, '_wp_page_template', $pages[$slug][1]);
        }
    }

    return $cache[$slug];
}

/**
 * Makes sure a published page exists for a slug (creates / publishes it).
 * Returns the page ID, or 0 if nothing could be created.
 */
function bugbusters_ensure_page($slug) {
    $pages = bugbusters_required_pages();
    if (!isset($pages[$slug])) {
        return 0;
    }

    $found = bugbusters_find_page($slug);
    if ($found) {
        return (int) $found->ID;
    }

    $existing = get_page_by_path($slug);
    if ($existing) {
        wp_update_post(array(
            'ID'          => $existing->ID,
            'post_status' => 'publish',
        ));
        update_post_meta($existing->ID, '_wp_page_template', $pages[$slug][1]);
        return (int) $existing->ID;
    }

    $id = wp_insert_post(array(
        'post_title'    => $pages[$slug][0],
        'post_name'     => $slug,
        'post_status'   => 'publish',
        'post_type'     => 'page',
        'page_template' => $pages[$slug][1],
    ));

    if (is_wp_error($id)) {
        return 0;
    }

    update_post_meta($id, '_wp_page_template', $pages[$slug][1]);
    return (int) $id;
}

/**
 * URL of the Contact page (used by every "Contact us" button).
 */
function bugbusters_contact_url() {
    $page = bugbusters_find_page('contact');
    return $page ? get_permalink($page) : home_url('/contact/');
}

/**
 * URL of a member's portfolio page (used by "View profile").
 */
function bugbusters_member_url($slug) {
    $page = bugbusters_find_page($slug);
    return $page ? get_permalink($page) : home_url('/' . $slug . '/');
}

// Create all the pages when the theme is activated.
function bugbusters_ensure_all_pages() {
    foreach (array_keys(bugbusters_required_pages()) as $slug) {
        bugbusters_ensure_page($slug);
    }
}
add_action('after_switch_theme', 'bugbusters_ensure_all_pages');

/**
 * Safety net: if someone opens one of these pages (e.g. /kenneth/ or /contact/)
 * before it exists, create it and reload - instead of showing a 404.
 */
function bugbusters_pages_404_rescue() {
    global $wp;

    if (!is_404() || !isset($wp->request)) {
        return;
    }

    $slug = trim($wp->request, '/');
    if (!isset(bugbusters_required_pages()[$slug])) {
        return;
    }

    $id = bugbusters_ensure_page($slug);
    if ($id) {
        wp_safe_redirect(get_permalink($id));
        exit;
    }
}
add_action('template_redirect', 'bugbusters_pages_404_rescue');