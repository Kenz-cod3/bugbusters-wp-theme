
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
