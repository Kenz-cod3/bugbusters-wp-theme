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
                <a href="#skills">Our Skill</a>
                <a href="#team">Team</a>
                <a href="#projects">Projects</a>
                <!-- Mobile-only (test) copy of the Contact button -->
                <a href="<?php echo esc_url(bugbusters_contact_url()); ?>" class="nav-button">Contact us</a>
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
            array(
                'name'     => 'Justine Harvey Jaraula',
                'role'     => 'Technical Writer',
                'initials' => 'JH',
                'img'      => 'member-04.png',
                'desc'     => 'Turns complex ideas into clear documentation, reports, and user guides for the team.',
                'profile'  => '#', // <- paste his profile link here (Facebook / LinkedIn / GitHub)
                'slug'     => 'justine', // <- portfolio page of this member (page-justine.php)
            ),
            array(
                'name'     => 'John Micheal Jaranilla',
                'role'     => 'Analyst',
                'initials' => 'JM',
                'img'      => 'member-01.png',
                'desc'     => 'Studies requirements and data to make sure every solution solves the right problem.',
                'profile'  => '#', // <- paste his profile link here
                'slug'     => 'john', // <- portfolio page of this member (page-john.php)
            ),
            array(
                'name'     => 'Kenneth Carl Milarpis',
                'role'     => 'Programmer',
                'initials' => 'KC',
                'img'      => 'member-03.png',
                'desc'     => 'Writes and tests the code that brings our designs and ideas to life.',
                'profile'  => '#', // <- paste his profile link here
                'slug'     => 'kenneth', // <- portfolio page of this member (page-kenneth.php)
            ),
            array(
                'name'     => 'Angel Grace Salingay',
                'role'     => 'Project Manager',
                'initials' => 'AG',
                'img'      => 'member-02.png',
                'desc'     => 'Plans the schedule, guides the team, and keeps every project on track.',
                'profile'  => '#', // <- paste her profile link here
                'slug'     => 'angel', // <- portfolio page of this member (page-angel.php)
            ),
        );

        // Folders to look in, in order
        $bb_img_dirs = array('/images/team/', '/images/');

        // Finds a member's photo (returns '' if there is none)
        $bb_find_img = function ($member) use ($bb_img_dirs) {
            foreach ($bb_img_dirs as $dir) {
                if (file_exists(get_theme_file_path($dir . $member['img']))) {
                    return $dir . $member['img'];
                }
            }
            return '';
        };
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
                                            src="<?php echo esc_url(add_query_arg(
                                                        'ver',
                                                        filemtime(get_theme_file_path($img_path)),
                                                        get_theme_file_uri($img_path)
                                                    )); ?>"
                                            alt=""
                                            loading="lazy">
                                    <?php endif; ?>
                                    <span class="avatar-fallback" aria-hidden="true"><?php echo esc_html($member['initials']); ?></span>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <p class="avatar-text"><strong>Meet the builders</strong><br>Individual strengths. Collective progress</p>
                    </div>

                </div>

                <div class="hero-visual" aria-hidden="true">

                    <!-- Above the card (outside it) -->
                    <div class="visual-heading">
                        <span class="visual-heading-title">Get to know us</span>
                        <span class="visual-heading-count">
                            <span class="online-dot" aria-hidden="true"></span>
                            <?php echo count($bb_team); ?> Team members
                        </span>
                    </div>

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

        <!-- OUR SKILL -->
        <?php
        /*
         * Edit the skill groups here.
         * Each group has a title, a short description, an icon (SVG path data),
         * a list of skills shown as chips, and 'members' = the team members
         * related to it (0 = Justine, 1 = John, 2 = Kenneth, 3 = Angel,
         * same order as $bb_team).
         */
        $bb_skills = array(
            array(
                'title'   => 'Web Development',
                'desc'    => 'Responsive, accessible websites and web systems.',
                'icon'    => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
                'tags'    => array('HTML5', 'CSS3', 'JavaScript', 'PHP', 'Laravel', 'WordPress', 'Tailwind'),
                'members' => array(2),
            ),
            array(
                'title'   => 'Mobile Development',
                'desc'    => 'Cross-platform apps with a focus on user experience.',
                'icon'    => '<rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/>',
                'tags'    => array('Flutter', 'Java', 'React'),
                'members' => array(2),
            ),
            array(
                'title'   => 'Backend & Database',
                'desc'    => 'Reliable logic, APIs, and data storage behind the scenes.',
                'icon'    => '<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/>',
                'tags'    => array('PHP', 'Node.js', 'Python', 'MySQL'),
                'members' => array(1, 2),
            ),
            array(
                'title'   => 'UI/UX Design',
                'desc'    => 'Clean, user-friendly interfaces from wireframe to prototype.',
                'icon'    => '<path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><path d="M2 2l7.6 7.6"/><circle cx="11" cy="11" r="2"/>',
                'tags'    => array('Figma', 'Wireframing', 'Prototyping'),
                'members' => array(1),
            ),
            array(
                'title'   => 'IoT & Hardware',
                'desc'    => 'Projects that connect sensors, devices, and software.',
                'icon'    => '<rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M9 1v3M15 1v3M9 20v3M15 20v3M20 9h3M20 14h3M1 9h3M1 14h3"/>',
                'tags'    => array('Arduino', 'Sensors', 'Embedded C'),
                'members' => array(2),
            ),
            array(
                'title'   => 'Tools & Workflow',
                'desc'    => 'Version control and AI-assisted tools for faster teamwork.',
                'icon'    => '<circle cx="18" cy="18" r="3"/><circle cx="6" cy="6" r="3"/><path d="M6 21V9a9 9 0 0 0 9 9"/>',
                'tags'    => array('Git', 'GitHub', 'Claude Code', 'ChatGPT'),
                'members' => array(2, 3),
            ),
            // NEW: Technical Writer
            array(
                'title'   => 'Technical Writing',
                'desc'    => 'Clear documentation, user guides, and project reports.',
                'icon'    => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/><path d="M8 13h8M8 17h5"/>',
                'tags'    => array('Documentation', 'Markdown', 'Google Docs', 'Notion'),
                'members' => array(0),
            ),
            // NEW: Project Manager
            array(
                'title'   => 'Project Management',
                'desc'    => 'Planning, scheduling, and keeping the team on track.',
                'icon'    => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/><path d="M8 15l2.5 2.5L16 13"/>',
                'tags'    => array('Planning', 'Trello', 'Jira', 'Notion'),
                'members' => array(3),
            ),
        );

        /*
         * Logo for each technology chip (name must match the chip text).
         * Reuses the marquee logos ($bb_tech) and adds a few more.
         * A chip with no logo here is shown as text only.
         */
        $bb_logos = $bb_tech + array(
            'Wireframing' => $bb_tech['Figma'],
            'Prototyping' => $bb_tech['Figma'],
            'Sensors'     => $bb_tech['Arduino'],
            'Embedded C'  => 'c/c-original.svg',
            'GitHub'      => 'github/github-original.svg',
            'Markdown'    => 'https://cdn.simpleicons.org/markdown/083FA1',
            'Google Docs' => 'https://cdn.simpleicons.org/googledocs/4285F4',
            'Notion'      => 'https://cdn.simpleicons.org/notion/000000',
            'Trello'      => 'https://cdn.simpleicons.org/trello/0052CC',
            'Jira'        => 'https://cdn.simpleicons.org/jira/0052CC',
        );
        ?>
        <section class="skills section-padding" id="skills">
            <div class="container">

                <div class="section-heading">
                    <h2>Our skills.</h2>
                    <p>The tools and disciplines we use to bring ideas to life.</p>
                </div>

                <div class="skills-grid">
                    <?php foreach ($bb_skills as $skill) : ?>
                        <article class="skill-card">
                            <div class="skill-head">
                                <div class="skill-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <?php echo $skill['icon']; // Static SVG markup defined above. 
                                        ?>
                                    </svg>
                                </div>
                                <h3><?php echo esc_html($skill['title']); ?></h3>
                            </div>
                            <p><?php echo esc_html($skill['desc']); ?></p>
                            <div class="skill-tech">
                                <p class="skill-members-label">
                                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <polyline points="16 18 22 12 16 6" />
                                        <polyline points="8 6 2 12 8 18" />
                                    </svg>
                                    <span>Related technologies</span>
                                </p>
                                <ul class="skill-tags">
                                    <?php foreach ($skill['tags'] as $tag) :
                                        $logo = isset($bb_logos[$tag])
                                            ? (strpos($bb_logos[$tag], 'https://') === 0 ? $bb_logos[$tag] : $bb_base . $bb_logos[$tag])
                                            : '';
                                    ?>
                                        <li>
                                            <?php if ($logo !== '') : ?>
                                                <img
                                                    src="<?php echo esc_url($logo); ?>"
                                                    alt=""
                                                    width="13"
                                                    height="13"
                                                    loading="lazy"
                                                    onerror="this.style.display='none'">
                                            <?php endif; ?>
                                            <?php echo esc_html($tag); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <?php if (!empty($skill['members'])) : ?>
                                <!-- Related team members -->
                                <div class="skill-members">
                                    <p class="skill-members-label">
                                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                            <circle cx="9" cy="7" r="4" />
                                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                        </svg>
                                        <?php $member_count = count(array_intersect_key(array_flip($skill['members']), $bb_team)); ?>
                                        <span><?php echo $member_count > 1 ? 'Related members' : 'Related member'; ?></span>
                                        <span class="skill-count" aria-label="<?php echo esc_attr($member_count); ?> related <?php echo $member_count > 1 ? 'members' : 'member'; ?>"><?php echo esc_html($member_count); ?></span>
                                    </p>
                                    <div class="skill-members-row">
                                        <div class="avatar-stack">
                                            <?php foreach ($skill['members'] as $idx) :
                                                if (!isset($bb_team[$idx])) continue;
                                                $m = $bb_team[$idx];
                                                $m_img = $bb_find_img($m);
                                            ?>
                                                <span class="avatar<?php echo $m_img !== '' ? '' : ' is-fallback'; ?>" role="img" aria-label="<?php echo esc_attr($m['name']); ?>" title="<?php echo esc_attr($m['name'] . ' – ' . $m['role']); ?>">
                                                    <?php if ($m_img !== '') : ?>
                                                        <img
                                                            src="<?php echo esc_url(add_query_arg(
                                                                        'ver',
                                                                        filemtime(get_theme_file_path($m_img)),
                                                                        get_theme_file_uri($m_img)
                                                                    )); ?>"
                                                            alt=""
                                                            loading="lazy">
                                                    <?php endif; ?>
                                                    <span class="avatar-fallback" aria-hidden="true"><?php echo esc_html($m['initials']); ?></span>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                        <span class="skill-members-names">
                                            <?php
                                            $names = array();
                                            foreach ($skill['members'] as $idx) {
                                                if (isset($bb_team[$idx])) {
                                                    $names[] = explode(' ', $bb_team[$idx]['name'])[0];
                                                }
                                            }
                                            echo esc_html(implode(', ', $names));
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
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
                    <?php foreach ($bb_team as $member) :
                        // Look for the member's photo in the same folders as the hero avatars
                        $img_path = '';
                        foreach ($bb_img_dirs as $dir) {
                            if (file_exists(get_theme_file_path($dir . $member['img']))) {
                                $img_path = $dir . $member['img'];
                                break;
                            }
                        }
                    ?>
                        <div class="team-card" tabindex="0">
                            <div class="member-image">
                                <?php if ($img_path !== '') : ?>
                                    <img
                                        src="<?php echo esc_url(add_query_arg(
                                                    'ver',
                                                    filemtime(get_theme_file_path($img_path)),
                                                    get_theme_file_uri($img_path)
                                                )); ?>"
                                        alt="<?php echo esc_attr($member['name']); ?>"
                                        loading="lazy">
                                <?php else : ?>
                                    <svg viewBox="0 0 24 24" width="64" height="64" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="12" cy="8" r="4" />
                                        <path d="M4 21c0-4 3.6-7 8-7s8 3 8 7" />
                                    </svg>
                                <?php endif; ?>
                            </div>

                            <!-- Name + role always visible; the rest shows on hover -->
                            <div class="member-info">
                                <h3><?php echo esc_html($member['name']); ?></h3>
                                <span class="member-role"><?php echo esc_html($member['role']); ?></span>

                                <div class="member-more">
                                    <div class="member-more-inner">
                                        <p class="member-desc"><?php echo esc_html($member['desc']); ?></p>
                                        <a
                                            href="<?php echo esc_url(bugbusters_member_url($member['slug'])); ?>"
                                            class="member-link"
                                            aria-label="View portfolio of <?php echo esc_attr($member['name']); ?>">
                                            View profile
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M5 12h14M13 6l6 6-6 6" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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

                <a href="<?php echo esc_url(bugbusters_contact_url()); ?>" class="btn-primary btn-light">
                    Send us a message
                </a>

            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <?php
    /*
     * Social links: paste your real links in 'url'.
     * Leave '#' if you don't have one yet (the icon stays but does nothing).
     */
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
    <footer class="site-footer">
        <div class="container">

            <div class="footer-top">

                <!-- Brand: logo + sentence + social icons -->
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

                <!-- Navbar links -->
                <nav class="footer-nav" aria-label="Footer">
                    <h4>Navigate</h4>
                    <ul>
                        <li><a href="#home">Home</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="#skills">Our Skill</a></li>
                        <li><a href="#team">Team</a></li>
                        <li><a href="#projects">Projects</a></li>
                        <li><a href="<?php echo esc_url(bugbusters_contact_url()); ?>">Contact us</a></li>
                    </ul>
                </nav>

            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo esc_html(date('Y')); ?> BugBusters Team. All rights reserved.</p>
                <a href="#home" class="back-top">Back to top</a>
            </div>

        </div>
    </footer>

    <?php wp_footer(); ?>

</body>

</html>