<?php

/**
 * Shared layout for the member portfolio pages.
 * Do not edit the member details here - edit each member's own file
 * (page-justine.php, page-john.php, page-kenneth.php, page-angel.php).
 * Those files fill in $bb_member and then load this layout.
 *
 * Layout:
 *   [ one card: photo | name + role (bottom), description (bottom), buttons (very bottom) ]
 *   [ About card + Contact card (left) | Technical, Experience, Projects (right) ]
 *
 * Theme-aware photo:
 *   'img'            = the member's photo. If it is the ONLY photo, it is shown
 *                      in BOTH light and dark mode (default behavior for
 *                      Justine, John, Angel).
 *   'img_dark'       = optional separate dark-mode photo (Kenneth).
 *                      When set and the file exists, it replaces 'img' in dark mode.
 *   'img_hover'      = optional light-mode hover photo (Kenneth only).
 *   'img_hover_dark' = optional dark-mode hover photo  (Kenneth only).
 */

if (!isset($bb_member) || !is_array($bb_member)) {
    return;
}

$bb_member = wp_parse_args($bb_member, array(
    'name'           => '',
    'role'           => '',
    'initials'       => '',
    'img'            => '',
    'img_dark'       => '',
    'img_hover'      => '',
    'img_hover_dark' => '',
    'desc'           => '',
    'bio'            => '',
    'areas'          => array(),
    'tags'           => array(),
    'experience'     => array(),
    'projects'       => array(),
    'cv'             => '',
    'github'         => '',
    'profile'        => '#',
    'portfolio'      => '',
));

/* Show the CV / GitHub buttons greyed out while a link is still empty.
   Set to false to hide them until the link is filled in. */
$bb_show_empty_buttons = true;

// Photo lookup helper: returns the theme-relative path or '' if not found.
$bb_find_photo = function ($file) {
    if ($file === '') {
        return '';
    }
    foreach (array('/images/team/', '/images/') as $dir) {
        if (file_exists(get_theme_file_path($dir . $file))) {
            return $dir . $file;
        }
    }
    return '';
};

// Light-mode photo (required)
$bb_photo = $bb_find_photo($bb_member['img']);

// Dark-mode photo — ONLY when the member actually supplies a separate,
// existing dark file. Otherwise leave empty: no dark <img> is rendered and
// the light <img> stays visible in both modes.
$bb_photo_dark = $bb_find_photo($bb_member['img_dark']);
if ($bb_photo_dark !== '' && $bb_photo_dark === $bb_photo) {
    // Same file as the light photo → not a real separate dark image.
    $bb_photo_dark = '';
}

// Hover photos — only used by members who opt in (Kenneth).
$bb_photo_hover = $bb_find_photo($bb_member['img_hover']);

$bb_photo_hover_dark = $bb_find_photo($bb_member['img_hover_dark']);
if ($bb_photo_hover_dark === '') {
    $bb_photo_hover_dark = $bb_photo_hover;
}
if ($bb_photo_hover_dark === $bb_photo_hover) {
    $bb_photo_hover_dark = '';
}

$bb_lead = $bb_member['bio'] !== '' ? $bb_member['bio'] : $bb_member['desc'];

/* CV: either a full link (Google Drive, etc.) or a file name saved in the
   theme's cv/ folder (e.g. 'angel-cv.pdf'). Returns '' if nothing is found. */
$bb_cv_url   = '';
$bb_cv_local = false;
$bb_cv       = trim((string) $bb_member['cv']);
if ($bb_cv !== '') {
    if (preg_match('#^https?://#i', $bb_cv)) {
        $bb_cv_url = $bb_cv;
    } else {
        foreach (array('/cv/', '/files/', '/') as $dir) {
            $path = $dir . ltrim($bb_cv, '/');
            if (file_exists(get_theme_file_path($path))) {
                $bb_cv_url   = get_theme_file_uri($path);
                $bb_cv_local = true;
                break;
            }
        }
    }
}
$bb_cv_ext      = pathinfo((string) wp_parse_url($bb_cv_url, PHP_URL_PATH), PATHINFO_EXTENSION);
$bb_cv_filename = sanitize_file_name($bb_member['name'] . ' CV') . '.' . ($bb_cv_ext !== '' ? $bb_cv_ext : 'pdf');

/* GitHub: a full link or just the username. */
$bb_github = trim((string) $bb_member['github']);
if ($bb_github !== '' && !preg_match('#^https?://#i', $bb_github)) {
    $bb_github = 'https://github.com/' . ltrim($bb_github, '@/');
}

// Technology logos
$bb_base  = 'https://cdn.jsdelivr.net/gh/devicons/devicon@v2.16.0/icons/';
$bb_logos = array(
    'HTML5'       => 'html5/html5-original.svg',
    'CSS3'        => 'css3/css3-original.svg',
    'JavaScript'  => 'javascript/javascript-original.svg',
    'Tailwind'    => 'tailwindcss/tailwindcss-original.svg',
    'PHP'         => 'php/php-original.svg',
    'Laravel'     => 'laravel/laravel-original.svg',
    'WordPress'   => 'wordpress/wordpress-plain.svg',
    'MySQL'       => 'mysql/mysql-original.svg',
    'React'       => 'react/react-original.svg',
    'Node.js'     => 'nodejs/nodejs-original.svg',
    'Python'      => 'python/python-original.svg',
    'Java'        => 'java/java-original.svg',
    'Flutter'     => 'flutter/flutter-original.svg',
    'Arduino'     => 'arduino/arduino-original.svg',
    'Git'         => 'git/git-original.svg',
    'GitHub'      => 'github/github-original.svg',
    'Figma'       => 'figma/figma-original.svg',
    'Wireframing' => 'figma/figma-original.svg',
    'Prototyping' => 'figma/figma-original.svg',
    'Sensors'     => 'arduino/arduino-original.svg',
    'Embedded C'  => 'c/c-original.svg',
    'Claude Code' => 'https://cdn.simpleicons.org/claude/D97757',
    'ChatGPT'     => 'https://cdn.simpleicons.org/openai/10A37F',
    'Markdown'    => 'https://cdn.simpleicons.org/markdown/083FA1',
    'Google Docs' => 'https://cdn.simpleicons.org/googledocs/4285F4',
    'Notion'      => 'https://cdn.simpleicons.org/notion/000000',
    'Trello'      => 'https://cdn.simpleicons.org/trello/0052CC',
    'Jira'        => 'https://cdn.simpleicons.org/jira/0052CC',
);

$bb_github_path = 'M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12';

/* Social links (same as the footer on the home page) */
$bb_socials = array(
    array(
        'name' => 'GitHub',
        'url'  => '#', // e.g. https://github.com/your-team
        'path' => $bb_github_path,
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

<body <?php body_class('page-member'); ?>>
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
                <a href="<?php echo esc_url(home_url('/#our-skill')); ?>">Our Skill</a>
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

        <section class="member-page">
            <div class="container">

                <a href="<?php echo esc_url(home_url('/#team')); ?>" class="mp-back">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M11 6l-6 6 6 6" />
                    </svg>
                    Back to team
                </a>

                <!-- ============ TOP: ONE card wrapping photo + right side ============ -->
                <!-- has-dark: only when a real separate dark photo exists (Kenneth). -->
                <!-- has-hover: only when the member set an img_hover (Kenneth). -->
                <header class="mp-hero-card<?php echo $bb_photo_hover !== '' ? ' has-hover' : ''; ?>">

                    <div class="mp-photo<?php echo $bb_photo !== '' ? '' : ' is-fallback'; ?><?php echo $bb_photo_dark !== '' ? ' has-dark' : ''; ?>">
                        <?php if ($bb_photo !== '') : ?>
                            <img
                                class="mp-photo-img mp-photo-light"
                                src="<?php echo esc_url(add_query_arg(
                                            'ver',
                                            filemtime(get_theme_file_path($bb_photo)),
                                            get_theme_file_uri($bb_photo)
                                        )); ?>"
                                alt="<?php echo esc_attr($bb_member['name']); ?>">

                            <?php if ($bb_photo_dark !== '') : ?>
                                <img
                                    class="mp-photo-img mp-photo-dark"
                                    src="<?php echo esc_url(add_query_arg(
                                                'ver',
                                                filemtime(get_theme_file_path($bb_photo_dark)),
                                                get_theme_file_uri($bb_photo_dark)
                                            )); ?>"
                                    alt="<?php echo esc_attr($bb_member['name']); ?>">
                            <?php endif; ?>

                            <?php if ($bb_photo_hover !== '') : ?>
                                <img
                                    class="mp-photo-img mp-photo-hover mp-hover-light"
                                    src="<?php echo esc_url(add_query_arg(
                                                'ver',
                                                filemtime(get_theme_file_path($bb_photo_hover)),
                                                get_theme_file_uri($bb_photo_hover)
                                            )); ?>"
                                    alt="<?php echo esc_attr($bb_member['name']); ?>">
                            <?php endif; ?>

                            <?php if ($bb_photo_hover_dark !== '') : ?>
                                <img
                                    class="mp-photo-img mp-photo-hover mp-hover-dark"
                                    src="<?php echo esc_url(add_query_arg(
                                                'ver',
                                                filemtime(get_theme_file_path($bb_photo_hover_dark)),
                                                get_theme_file_uri($bb_photo_hover_dark)
                                            )); ?>"
                                    alt="<?php echo esc_attr($bb_member['name']); ?>">
                            <?php endif; ?>
                        <?php else : ?>
                            <span class="mp-photo-initials"><?php echo esc_html($bb_member['initials']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="mp-intro">
                        <div class="mp-title">
                            <h1 class="mp-name"><?php echo esc_html($bb_member['name']); ?></h1>
                            <p class="mp-role"><?php echo esc_html($bb_member['role']); ?></p>
                        </div>

                        <p class="mp-desc"><?php echo esc_html($bb_member['desc']); ?></p>

                        <div class="mp-actions">

                            <?php if ($bb_cv_url !== '') : ?>
                                <a class="btn-primary mp-btn" href="<?php echo esc_url($bb_cv_url); ?>"
                                    <?php echo $bb_cv_local ? 'download="' . esc_attr($bb_cv_filename) . '"' : 'target="_blank" rel="noopener noreferrer"'; ?>>
                                    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 3v12M7 10l5 5 5-5M5 21h14" />
                                    </svg>
                                    Download CV
                                </a>
                                <a class="btn-secondary mp-btn" href="<?php echo esc_url($bb_cv_url); ?>" target="_blank" rel="noopener noreferrer">
                                    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M14 3h7v7M21 3l-9 9M19 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h5" />
                                    </svg>
                                    Open CV
                                </a>
                            <?php elseif ($bb_show_empty_buttons) : ?>
                                <span class="btn-primary mp-btn is-disabled" aria-disabled="true" title="CV not added yet">
                                    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 3v12M7 10l5 5 5-5M5 21h14" />
                                    </svg>
                                    Download CV
                                </span>
                                <span class="btn-secondary mp-btn is-disabled" aria-disabled="true" title="CV not added yet">
                                    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M14 3h7v7M21 3l-9 9M19 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h5" />
                                    </svg>
                                    Open CV
                                </span>
                            <?php endif; ?>

                            <?php if ($bb_github !== '') : ?>
                                <a class="btn-secondary mp-btn" href="<?php echo esc_url($bb_github); ?>" target="_blank" rel="noopener noreferrer">
                                    <svg viewBox="0 0 24 24" width="17" height="17" fill="currentColor" aria-hidden="true">
                                        <path d="<?php echo esc_attr($bb_github_path); ?>" />
                                    </svg>
                                    GitHub
                                </a>
                            <?php elseif ($bb_show_empty_buttons) : ?>
                                <span class="btn-secondary mp-btn is-disabled" aria-disabled="true" title="GitHub not added yet">
                                    <svg viewBox="0 0 24 24" width="17" height="17" fill="currentColor" aria-hidden="true">
                                        <path d="<?php echo esc_attr($bb_github_path); ?>" />
                                    </svg>
                                    GitHub
                                </span>
                            <?php endif; ?>

                        </div>
                    </div>

                </header>

                <div class="mp-body">

                    <!-- ============ LEFT: About card + separate Contact card ============ -->
                    <div class="mp-side">

                        <aside class="mp-card mp-about" aria-labelledby="mp-about-title">
                            <h2 id="mp-about-title">About</h2>

                            <div class="mp-about-text">
                                <?php echo wp_kses_post(wpautop(esc_html($bb_lead))); ?>
                            </div>

                            <?php
                            $bb_has_profile   = ($bb_member['profile'] !== '' && $bb_member['profile'] !== '#');
                            $bb_has_portfolio = ($bb_member['portfolio'] !== '');
                            ?>
                            <?php if ($bb_has_profile || $bb_has_portfolio) : ?>
                                <ul class="mp-links">
                                    <?php if ($bb_has_portfolio) : ?>
                                        <li><a href="<?php echo esc_url($bb_member['portfolio']); ?>" target="_blank" rel="noopener noreferrer">Full portfolio</a></li>
                                    <?php endif; ?>
                                    <?php if ($bb_has_profile) : ?>
                                        <li><a href="<?php echo esc_url($bb_member['profile']); ?>" target="_blank" rel="noopener noreferrer">Social profile</a></li>
                                    <?php endif; ?>
                                </ul>
                            <?php endif; ?>

                        </aside>

                        <section class="mp-card mp-contact-card" aria-labelledby="mp-contact-title">
                            <h2 id="mp-contact-title">Contact</h2>
                            <p>Have a project or a question? Send a message to the team.</p>
                            <a href="<?php echo esc_url(bugbusters_contact_url()); ?>" class="btn-primary mp-contact">Contact us</a>
                        </section>

                    </div>

                    <!-- ============ RIGHT: Technical, Experience, Projects ============ -->
                    <div class="mp-main">

                        <?php if (!empty($bb_member['areas']) || !empty($bb_member['tags'])) : ?>
                            <section class="mp-card" aria-labelledby="mp-tech-title">
                                <h2 id="mp-tech-title">Technical</h2>

                                <?php if (!empty($bb_member['areas'])) : ?>
                                    <h3 class="mp-sub">Focus areas</h3>
                                    <ul class="skill-tags mp-areas">
                                        <?php foreach ($bb_member['areas'] as $area) : ?>
                                            <li><?php echo esc_html($area); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>

                                <?php if (!empty($bb_member['tags'])) : ?>
                                    <h3 class="mp-sub">Tools and technologies</h3>
                                    <ul class="mp-tech">
                                        <?php foreach ($bb_member['tags'] as $tag) :
                                            $logo = isset($bb_logos[$tag])
                                                ? (strpos($bb_logos[$tag], 'https://') === 0 ? $bb_logos[$tag] : $bb_base . $bb_logos[$tag])
                                                : '';
                                        ?>
                                            <li>
                                                <?php if ($logo !== '') : ?>
                                                    <img src="<?php echo esc_url($logo); ?>" alt="" width="22" height="22" loading="lazy" onerror="this.style.display='none'">
                                                <?php endif; ?>
                                                <span><?php echo esc_html($tag); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </section>
                        <?php endif; ?>

                        <section class="mp-card" aria-labelledby="mp-exp-title">
                            <h2 id="mp-exp-title">Experience</h2>
                            <?php if (!empty($bb_member['experience'])) : ?>
                                <ol class="mp-timeline">
                                    <?php foreach ($bb_member['experience'] as $job) : ?>
                                        <li>
                                            <h3><?php echo esc_html(isset($job['role']) ? $job['role'] : ''); ?></h3>
                                            <p class="mp-meta">
                                                <?php if (!empty($job['org'])) : ?>
                                                    <span class="mp-org"><?php echo esc_html($job['org']); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($job['period'])) : ?>
                                                    <span class="mp-period"><?php echo esc_html($job['period']); ?></span>
                                                <?php endif; ?>
                                            </p>
                                            <?php if (!empty($job['desc'])) : ?>
                                                <p class="mp-job-desc"><?php echo esc_html($job['desc']); ?></p>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            <?php else : ?>
                                <p class="mp-empty">Experience will be added soon.</p>
                            <?php endif; ?>
                        </section>

                        <section class="mp-card" aria-labelledby="mp-proj-title">
                            <h2 id="mp-proj-title">Projects</h2>
                            <?php if (!empty($bb_member['projects'])) : ?>
                                <div class="mp-projects">
                                    <?php foreach ($bb_member['projects'] as $proj) : ?>
                                        <article class="mp-project">
                                            <h3><?php echo esc_html($proj['title']); ?></h3>
                                            <?php if (!empty($proj['desc'])) : ?>
                                                <p><?php echo esc_html($proj['desc']); ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($proj['tags'])) : ?>
                                                <ul class="skill-tags">
                                                    <?php foreach ($proj['tags'] as $t) : ?>
                                                        <li><?php echo esc_html($t); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                            <?php if (!empty($proj['url'])) : ?>
                                                <a class="mp-project-link" href="<?php echo esc_url($proj['url']); ?>" target="_blank" rel="noopener noreferrer">View project &rarr;</a>
                                            <?php endif; ?>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <p class="mp-empty">Projects will be added soon.</p>
                            <?php endif; ?>
                        </section>

                    </div>

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