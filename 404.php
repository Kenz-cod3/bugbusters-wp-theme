<?php
/**
 * 404 template - shown when a page is not found.
 * Has the same navbar and footer as the other pages.
 */

/* Social links (same as the footer on the home page) */
$bb_socials = array(
    array(
        'name' => 'GitHub',
        'url'  => '#', // e.g. https://github.com/your-team
        'path' => 'M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12',
    ),
    array(
        'name' => 'Telegram',
        'url'  => '#', // e.g. https://t.me/your-username
        'path' => 'M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z',
    ),
    array(
        'name' => 'LinkedIn',
        'url'  => '#', // e.g. https://www.linkedin.com/company/your-team
        'path' => 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z',
    ),
);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>

<body <?php body_class('page-404'); ?>>
    <?php wp_body_open(); ?>

    <a class="skip-link" href="#main">Skip to content</a>

    <!-- FLOATING PILL HEADER (links go back to the home page sections) -->
    <header class="site-header" id="site-header">
        <div class="nav-container">

            <a href="<?php echo esc_url(home_url('/')); ?>" class="logo">
                <img
                    src="<?php echo esc_url(get_theme_file_uri('/images/bugbusters-logo.png')); ?>"
                    alt="BugBusters Development Team"
                    class="logo-image logo-light">
                <img
                    src="<?php echo esc_url(get_theme_file_uri('/images/bugbusters-logoD.png')); ?>"
                    alt=""
                    class="logo-image logo-dark"
                    aria-hidden="true">
            </a>

            <nav class="main-nav" id="primary-nav" aria-label="Primary">
                <a href="<?php echo esc_url(home_url('/#home')); ?>">Home</a>
                <a href="<?php echo esc_url(home_url('/#about')); ?>">About</a>
                <a href="<?php echo esc_url(home_url('/#skills')); ?>">Our Skill</a>
                <a href="<?php echo esc_url(home_url('/#team')); ?>">Team</a>
                <a href="<?php echo esc_url(home_url('/#projects')); ?>">Projects</a>
                <a href="<?php echo esc_url(bugbusters_contact_url()); ?>" class="nav-button">Contact us</a>
            </nav>

            <div class="nav-actions">
                <span class="nav-divider" aria-hidden="true"></span>

                <button
                    class="theme-toggle"
                    id="theme-toggle"
                    type="button"
                    aria-label="Switch to dark mode"
                    aria-pressed="false"
                    title="Toggle dark mode">
                    <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z" />
                    </svg>
                    <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="4" />
                        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" />
                    </svg>
                </button>

                <a href="<?php echo esc_url(bugbusters_contact_url()); ?>" class="nav-button nav-cta">Contact us</a>

                <button
                    class="nav-toggle"
                    type="button"
                    aria-expanded="false"
                    aria-controls="primary-nav"
                    aria-label="Open menu">
                    <span></span>
                    <span></span>
                </button>
            </div>

        </div>
    </header>

    <main id="main">

        <section class="error-page">
            <div class="container error-content">

                <p class="hero-label"><span class="status-dot"></span> Error 404</p>

                <div class="error-code" aria-hidden="true">404</div>

                <h1>Page not found</h1>

                <p>
                    Sorry, the page you are looking for does not exist or may have been moved.
                    Let's get you back on track.
                </p>

                <div class="hero-buttons">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-primary">Back to home</a>
                    <button type="button" class="btn-secondary" onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = '<?php echo esc_js(home_url('/')); ?>'; }">Go back</button>
                </div>

            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <footer class="site-footer">
        <div class="container">

            <div class="footer-top">

                <div class="footer-brand">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="logo">
                        <img
                            src="<?php echo esc_url(get_theme_file_uri('/images/bugbusters-logo.png')); ?>"
                            alt="BugBusters Development Team"
                            class="logo-image logo-light">
                        <img
                            src="<?php echo esc_url(get_theme_file_uri('/images/bugbusters-logoD.png')); ?>"
                            alt=""
                            class="logo-image logo-dark"
                            aria-hidden="true">
                    </a>

                    <p class="footer-desc">
                        A team of IT students turning ideas into reality through
                        creativity, collaboration, and code.
                    </p>

                    <ul class="footer-social">
                        <?php foreach ($bb_socials as $social) : ?>
                            <li>
                                <a
                                    href="<?php echo esc_url($social['url']); ?>"
                                    aria-label="<?php echo esc_attr($social['name']); ?>"
                                    title="<?php echo esc_attr($social['name']); ?>"
                                    <?php echo ($social['url'] !== '#') ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
                                        <path d="<?php echo esc_attr($social['path']); ?>" />
                                    </svg>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <nav class="footer-nav" aria-label="Footer">
                    <h4>Navigate</h4>
                    <ul>
                        <li><a href="<?php echo esc_url(home_url('/#home')); ?>">Home</a></li>
                        <li><a href="<?php echo esc_url(home_url('/#about')); ?>">About</a></li>
                        <li><a href="<?php echo esc_url(home_url('/#skills')); ?>">Our Skill</a></li>
                        <li><a href="<?php echo esc_url(home_url('/#team')); ?>">Team</a></li>
                        <li><a href="<?php echo esc_url(home_url('/#projects')); ?>">Projects</a></li>
                        <li><a href="<?php echo esc_url(bugbusters_contact_url()); ?>">Contact us</a></li>
                    </ul>
                </nav>

            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo esc_html(date('Y')); ?> BugBusters Team. All rights reserved.</p>
                <a href="#main" class="back-top">Back to top</a>
            </div>

        </div>
    </footer>

    <?php wp_footer(); ?>

</body>

</html>