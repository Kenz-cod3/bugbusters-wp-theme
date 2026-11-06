<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>

    <!-- LOADING SCREEN -->
    <div class="preloader" id="preloader" role="status" aria-live="polite" aria-label="Loading">
        <img
            src="<?php echo esc_url(get_theme_file_uri('/images/bugbusters-logo.png')); ?>"
            alt="BugBusters Development Team"
            class="preloader-logo logo-light">
        <img
            src="<?php echo esc_url(get_theme_file_uri('/images/bugbusters-logoD.png')); ?>"
            alt=""
            class="preloader-logo logo-dark"
            aria-hidden="true">
        <div class="preloader-bar" aria-hidden="true"></div>
    </div>
    <noscript>
        <style>
            .preloader {
                display: none !important
            }

            body:not(.is-loaded) {
                overflow: auto !important
            }
        </style>
    </noscript>

    <a class="skip-link" href="#main">Skip to content</a>

    <!-- SCROLL PROGRESS -->
    <div class="scroll-progress" aria-hidden="true"><span id="scroll-progress-bar"></span></div>

    <!-- FLOATING PILL HEADER -->
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

            <!-- Centered links -->
            <nav class="main-nav" id="primary-nav" aria-label="Primary">
                <a href="#home" class="is-active">Home</a>
                <a href="#about">About</a>
                <a href="#team">Our Team</a>
                <a href="#projects">Projects</a>
                <!-- Mobile-only copy of the Contact button -->
                <a href="#contact" class="nav-button">Contact us</a>
            </nav>

            <!-- Right side: Contact (desktop) + dark mode + hamburger (mobile) -->
            <div class="nav-actions">
                <span class="nav-divider" aria-hidden="true"></span>

                <button
                    class="theme-toggle"
                    id="theme-toggle"
                    type="button"
                    aria-label="Switch to dark mode"
                    aria-pressed="false"
                    title="Toggle dark mode">
                    <!-- Moon (shown in light mode) -->
                    <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z" />
                    </svg>
                    <!-- Sun (shown in dark mode) -->
                    <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="4" />
                        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" />
                    </svg>
                </button>

                <a href="#contact" class="nav-button nav-cta">Contact us</a>

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

        <!-- HERO -->
        <?php
        /*
     * Team avatars (image + fallback).
     * - Photos can be in:  bugbusters-custom/images/        (current setup)
     *                  or  bugbusters-custom/images/team/
     * - Set 'img' to the file name. If the file is missing in both folders,
     *   the initials in 'initials' are shown instead.
     */
        $bb_team = array(
            array('name' => 'Team Member 01', 'initials' => 'M1', 'img' => 'member-01.png'),
            array('name' => 'Team Member 02', 'initials' => 'M2', 'img' => 'member-02.png'),
            array('name' => 'Team Member 03', 'initials' => 'M3', 'img' => 'member-03.png'),
            array('name' => 'Team Member 04', 'initials' => 'M4', 'img' => 'member-04.png'),
        );

        // Folders to look in, in order
        $bb_img_dirs = array('/images/team/', '/images/');
        ?>
        <section class="hero" id="home">
            <div class="container hero-content">

                <div class="hero-text">

                    <p class="hero-label">
                        <span class="status-dot"></span>
                        Student development team
                    </p>

                    <h1>We build ideas into reality.</h1>

                    <p class="hero-lead">
                        We are BugBusters, a team of IT students creating
                        digital solutions through creativity, collaboration,
                        and code.
                    </p>

                    <div class="hero-buttons">
                        <a href="#projects" class="btn-primary">See our projects</a>
                        <a href="#team" class="btn-secondary">Meet the team</a>
                    </div>

                    <!-- AVATARS: an image element with a fallback -->
                    <div class="hero-avatars">
                        <div class="avatar-stack">
                            <?php foreach ($bb_team as $member) :
                                // Find the first folder that actually has this image
                                $img_path = '';
                                foreach ($bb_img_dirs as $dir) {
                                    if (file_exists(get_theme_file_path($dir . $member['img']))) {
                                        $img_path = $dir . $member['img'];
                                        break;
                                    }
                                }
                                $has_img = ($img_path !== '');
                            ?>
                                <span class="avatar<?php echo $has_img ? '' : ' is-fallback'; ?>" role="img" aria-label="<?php echo esc_attr($member['name']); ?>" title="<?php echo esc_attr($member['name']); ?>">
                                    <?php if ($has_img) : ?>
                                        <img
                                            src="<?php echo esc_url(get_theme_file_uri($img_path)); ?>"
                                            alt=""
                                            loading="lazy">
                                    <?php endif; ?>
                                    <span class="avatar-fallback" aria-hidden="true"><?php echo esc_html($member['initials']); ?></span>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <p class="avatar-text"><strong><?php echo count($bb_team); ?> Team members</strong><br>Individual strengths. Collective progress</p>
                    </div>

                </div>

                <div class="hero-visual" aria-hidden="true">
                    <div class="visual-card">

                        <div class="visual-top">
                            <span class="code-dot red"></span>
                            <span class="code-dot yellow"></span>
                            <span class="code-dot green"></span>
                            <span class="code-label">bugbusters.dev</span>
                        </div>

                        <div class="code-content">
                            <span class="code-comment">// Our mission</span>
                            <p><span class="code-purple">const</span> team = {</p>
                            <p class="code-indent">name: <span class="code-green">"BugBusters"</span>,</p>
                            <p class="code-indent">passion: <span class="code-green">"Technology"</span>,</p>
                            <p class="code-indent">mission: <span class="code-green">"Build solutions"</span></p>
                            <p>};</p>

                            <div class="code-result">
                                <span class="status-dot"></span>
                                Ready to innovate...
                            </div>
                        </div>

                    </div>

                    <!-- Below the card (outside it): name + description, and </> -->
                    <div class="visual-caption">
                        <div class="visual-caption-text">
                            <strong>BugBusters</strong>
                            <span>Student development team</span>
                        </div>
                        <span class="visual-caption-icon">&lt;/&gt;</span>
                    </div>

                </div>

            </div>
        </section>

        <!-- TECH MARQUEE -->
        <?php
        $bb_tech = array(
            'HTML5'      => 'html5/html5-original.svg',
            'CSS3'       => 'css3/css3-original.svg',
            'JavaScript' => 'javascript/javascript-original.svg',
            'Tailwind'   => 'tailwindcss/tailwindcss-original.svg',
            'PHP'        => 'php/php-original.svg',
            'Laravel'    => 'laravel/laravel-original.svg',
            'WordPress'  => 'wordpress/wordpress-plain.svg',
            'MySQL'      => 'mysql/mysql-original.svg',
            'React'      => 'react/react-original.svg',
            'Node.js'    => 'nodejs/nodejs-original.svg',
            'Python'     => 'python/python-original.svg',
            'Java'       => 'java/java-original.svg',
            'Flutter'    => 'flutter/flutter-original.svg',
            'Arduino'    => 'arduino/arduino-original.svg',
            'Git'        => 'git/git-original.svg',
            'Figma'      => 'figma/figma-original.svg',
            'Claude Code' => 'https://cdn.simpleicons.org/claude/D97757',
            'ChatGPT'     => 'https://cdn.simpleicons.org/openai/10A37F',
        );
        $bb_base = 'https://cdn.jsdelivr.net/gh/devicons/devicon@v2.16.0/icons/';
        ?>
        <section class="tech-marquee" aria-label="Technologies we use">
            <p class="tech-marquee-label">Technologies we work with</p>

            <div class="marquee">
                <div class="marquee-track">
                    <?php for ($i = 0; $i < 2; $i++) : ?>
                        <ul class="marquee-group" <?php echo $i === 1 ? 'aria-hidden="true"' : ''; ?>>
                            <?php foreach ($bb_tech as $name => $path) : ?>
                                <li class="tech-item">
                                    <img
                                        src="<?php echo esc_url(strpos($path, 'https://') === 0 ? $path : $bb_base . $path); ?>"
                                        alt="<?php echo $i === 0 ? esc_attr($name) : ''; ?>"
                                        width="36"
                                        height="36"
                                        loading="lazy">
                                    <span><?php echo esc_html($name); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endfor; ?>
                </div>
            </div>
        </section>

        <!-- ABOUT -->
        <section class="about section-padding" id="about">
            <div class="container">

                <div class="section-heading">
                    <h2>More than just coders.</h2>
                    <p>
                        We are a group of aspiring IT professionals driven by
                        curiosity and the goal of making technology useful.
                    </p>
                </div>

                <div class="about-grid">

                    <div class="about-item">
                        <h3>Innovation</h3>
                        <p>We turn creative ideas into practical digital solutions.</p>
                    </div>

                    <div class="about-item">
                        <h3>Collaboration</h3>
                        <p>We combine our skills to achieve more together.</p>
                    </div>

                    <div class="about-item">
                        <h3>Continuous learning</h3>
                        <p>We take on new challenges and keep improving our skills.</p>
                    </div>

                </div>

            </div>
        </section>

        <!-- TEAM -->
        <section class="team section-padding" id="team">
            <div class="container">

                <div class="section-heading">
                    <h2>Meet our team.</h2>
                    <p>The people and skills behind BugBusters.</p>
                </div>

                <div class="team-grid">

                    <div class="team-card">
                        <div class="member-image">
                            <!-- Replace with: <img src="..." alt="Member name"> -->
                            <svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="8" r="4" />
                                <path d="M4 21c0-4 3.6-7 8-7s8 3 8 7" />
                            </svg>
                        </div>
                        <h3>Team Member 01</h3>
                        <p>Web Developer</p>
                    </div>

                    <div class="team-card">
                        <div class="member-image">
                            <svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="8" r="4" />
                                <path d="M4 21c0-4 3.6-7 8-7s8 3 8 7" />
                            </svg>
                        </div>
                        <h3>Team Member 02</h3>
                        <p>UI/UX Designer</p>
                    </div>

                    <div class="team-card">
                        <div class="member-image">
                            <svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="8" r="4" />
                                <path d="M4 21c0-4 3.6-7 8-7s8 3 8 7" />
                            </svg>
                        </div>
                        <h3>Team Member 03</h3>
                        <p>Mobile Developer</p>
                    </div>

                    <div class="team-card">
                        <div class="member-image">
                            <svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="8" r="4" />
                                <path d="M4 21c0-4 3.6-7 8-7s8 3 8 7" />
                            </svg>
                        </div>
                        <h3>Team Member 04</h3>
                        <p>Backend Developer</p>
                    </div>

                </div>

            </div>
        </section>

        <!-- PROJECTS -->
        <section class="projects section-padding" id="projects">
            <div class="container">

                <div class="section-heading">
                    <h2>Featured projects.</h2>
                    <p>Academic projects, experiments, and digital creations.</p>
                </div>

                <div class="project-grid">

                    <article class="project-card">
                        <div class="project-preview preview-one"><span>&lt;/&gt;</span></div>
                        <div class="project-info">
                            <span class="project-category">Web development</span>
                            <h3>Project One</h3>
                            <p>A web-based system designed to solve real-world problems.</p>
                            <a href="#contact">View project</a>
                        </div>
                    </article>

                    <article class="project-card">
                        <div class="project-preview preview-two"><span>&#9633;</span></div>
                        <div class="project-info">
                            <span class="project-category">Mobile development</span>
                            <h3>Project Two</h3>
                            <p>A mobile app focused on accessibility and user experience.</p>
                            <a href="#contact">View project</a>
                        </div>
                    </article>

                    <article class="project-card">
                        <div class="project-preview preview-three"><span>&#9678;</span></div>
                        <div class="project-info">
                            <span class="project-category">IoT development</span>
                            <h3>Project Three</h3>
                            <p>A project that connects hardware and software.</p>
                            <a href="#contact">View project</a>
                        </div>
                    </article>

                </div>

            </div>
        </section>

        <!-- CONTACT -->
        <section class="contact section-padding" id="contact">
            <div class="container contact-content">

                <h2>Have an idea? Let's build it.</h2>

                <p>
                    Interested in collaborating or learning more about our
                    projects? We'd love to hear from you.
                </p>

                <a href="mailto:bugbusters@example.com" class="btn-primary btn-light">
                    Email the team
                </a>

            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <footer class="site-footer">
        <div class="container footer-content">

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

            <p>&copy; <?php echo esc_html(date('Y')); ?> BugBusters Team. All rights reserved.</p>

            <a href="#home" class="back-top">Back to top</a>

        </div>
    </footer>

    <?php wp_footer(); ?>

</body>

</html>