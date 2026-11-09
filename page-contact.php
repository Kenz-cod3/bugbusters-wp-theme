<?php
/**
 * Template Name: Contact Us
 * Description: Contact page with a working form (sent through wp_mail).
 *
 * HOW TO USE
 * 1. Put this file inside:  wp-content/themes/bugbusters-custom/
 * 2. WordPress Admin > Pages > Add New > title it "Contact".
 * 3. On the right side, under "Template", choose "Contact Us". Publish.
 * 4. Messages go to the email chosen in the "Send to" dropdown (see $bb_recipients below).
 */

/* ---------- Edit your contact details here ---------- */
$bb_contact = array(
    'email'    => 'bugbusters@example.com',   // <- where messages are sent + shown on the page
    'location' => 'Philippines',               // <- change to your school / city
    'response' => 'We usually reply within 1–2 days.',
);

/* ---------- Who can receive a message (shown in the "Send to" dropdown) ----------
 * key => label shown to the visitor + the email that really receives it.
 * Change the emails below. Add or remove lines as you like.
 */
$bb_recipients = array(
    'general' => array('label' => 'General Inquiry - BugBusters',  'email' => $bb_contact['email']),
    'justine' => array('label' => 'Justine Harvey Jaraula',        'email' => 'occ.jaraulla.justineharvey@gmail.com'),
    'john'    => array('label' => 'John Micheal Jaranilla',        'email' => 'occ.jaranilla.johnmicheal23@gmail.com'),
    'kenneth' => array('label' => 'Kenneth Carl Milarpis',         'email' => 'occ.milarpis.kennethcal@gmail.com'),
    'angel'   => array('label' => 'Angel Grace Salinga',           'email' => 'occ.salingay.angelgrace@gmail.com'),
);

/* ---------- Team shown in "Meet the team" (name, photo, page) ---------- */
$bb_team = array(
    array('name' => 'Justine Harvey Jaraula', 'initials' => 'JH', 'img' => 'member-04.png', 'slug' => 'justine'),
    array('name' => 'John Micheal Jaranilla', 'initials' => 'JM', 'img' => 'member-01.png', 'slug' => 'john'),
    array('name' => 'Kenneth Carl Milarpis',  'initials' => 'KC', 'img' => 'member-03.png', 'slug' => 'kenneth'),
    array('name' => 'Angel Grace Salinga',    'initials' => 'AG', 'img' => 'member-02.png', 'slug' => 'angel'),
);

/* Photo + initials of each person, used by the "Send to" dropdown */
$bb_opt_img  = array();
$bb_opt_init = array();
foreach ($bb_team as $bb_m) {
    $bb_opt_init[$bb_m['slug']] = $bb_m['initials'];
    foreach (array('/images/team/', '/images/') as $bb_dir) {
        if (file_exists(get_theme_file_path($bb_dir . $bb_m['img']))) {
            $bb_opt_img[$bb_m['slug']] = get_theme_file_uri($bb_dir . $bb_m['img']);
            break;
        }
    }
}

/* ---------- Handle the form (before any HTML is printed) ---------- */
$bb_errors = array();
$bb_old    = array('to' => 'general', 'name' => '', 'email' => '', 'subject' => '', 'message' => '');
$bb_sent   = isset($_GET['sent']) && $_GET['sent'] === '1';
$bb_page   = get_permalink() ? get_permalink() : home_url('/');

if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['bb_contact_submit'])) {

    if (!isset($_POST['bb_contact_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bb_contact_nonce'])), 'bb_contact')) {
        $bb_errors[] = 'Your session expired. Please refresh the page and try again.';
    } elseif (!empty($_POST['bb_website'])) {
        // Honeypot field was filled in = a bot. Pretend it worked.
        wp_safe_redirect(add_query_arg('sent', '1', $bb_page) . '#contact-form');
        exit;
    } else {
        $bb_to = sanitize_key(wp_unslash($_POST['bb_to'] ?? 'general'));
        $bb_old['to']      = isset($bb_recipients[$bb_to]) ? $bb_to : 'general';
        $bb_old['name']    = sanitize_text_field(wp_unslash($_POST['bb_name'] ?? ''));
        $bb_old['email']   = sanitize_email(wp_unslash($_POST['bb_email'] ?? ''));
        $bb_old['subject'] = sanitize_text_field(wp_unslash($_POST['bb_subject'] ?? ''));
        $bb_old['message'] = sanitize_textarea_field(wp_unslash($_POST['bb_message'] ?? ''));

        if ($bb_old['name'] === '')                 $bb_errors[] = 'Please enter your name.';
        if (!is_email($bb_old['email']))            $bb_errors[] = 'Please enter a valid email address.';
        if (strlen($bb_old['message']) < 10)        $bb_errors[] = 'Your message is too short (at least 10 characters).';

        if (empty($bb_errors)) {
            $subject = $bb_old['subject'] !== '' ? $bb_old['subject'] : 'New message from the website';
            $body    = "Name: {$bb_old['name']}\nEmail: {$bb_old['email']}\n\nMessage:\n{$bb_old['message']}\n";
            $headers = array(
                'Content-Type: text/plain; charset=UTF-8',
                'Reply-To: ' . $bb_old['name'] . ' <' . $bb_old['email'] . '>',
            );

            $bb_target = $bb_recipients[$bb_old['to']];
            $body      = 'Sent to: ' . $bb_target['label'] . "\n" . $body;

            if (wp_mail($bb_target['email'], '[BugBusters - ' . $bb_target['label'] . '] ' . $subject, $body, $headers)) {
                wp_safe_redirect(add_query_arg('sent', '1', $bb_page) . '#contact-form');
                exit;
            }
            $bb_errors[] = 'Sorry, the message could not be sent. Please email us directly instead.';
        }
    }
}

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

<body <?php body_class('page-contact'); ?>>
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
                <a href="<?php echo esc_url($bb_page); ?>" class="nav-button" aria-current="page">Contact us</a>
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

                <a href="<?php echo esc_url($bb_page); ?>" class="nav-button nav-cta" aria-current="page">Contact us</a>

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

        <section class="contact-page">
            <div class="container">

                <div class="section-heading">
                    <p class="hero-label"><span class="status-dot"></span> We reply fast</p>
                    <h1>Have an idea? Let's build it.</h1>
                    <p>
                        Interested in collaborating or learning more about our projects?
                        Send us a message and we'll get back to you.
                    </p>
                </div>

                <div class="cp-grid">

                    <!-- LEFT: contact details -->
                    <aside class="cp-info">

                        <div class="cp-item">
                            <span class="cp-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="5" width="18" height="14" rx="2" />
                                    <path d="M3 7l9 6 9-6" />
                                </svg>
                            </span>
                            <div>
                                <h3>Email us</h3>
                                <a href="mailto:<?php echo esc_attr($bb_contact['email']); ?>"><?php echo esc_html($bb_contact['email']); ?></a>
                            </div>
                        </div>

                        <div class="cp-item">
                            <span class="cp-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z" />
                                    <circle cx="12" cy="10" r="2.5" />
                                </svg>
                            </span>
                            <div>
                                <h3>Based in</h3>
                                <p><?php echo esc_html($bb_contact['location']); ?></p>
                            </div>
                        </div>

                        <div class="cp-item">
                            <span class="cp-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 7v5l3 2" />
                                </svg>
                            </span>
                            <div>
                                <h3>Response time</h3>
                                <p><?php echo esc_html($bb_contact['response']); ?></p>
                            </div>
                        </div>

                        <div class="cp-team">
                            <h3>Meet the team</h3>
                            <ul class="cp-team-list">
                                <?php foreach ($bb_team as $member) :
                                    $img = '';
                                    foreach (array('/images/team/', '/images/') as $dir) {
                                        if (file_exists(get_theme_file_path($dir . $member['img']))) {
                                            $img = $dir . $member['img'];
                                            break;
                                        }
                                    }
                                ?>
                                    <li>
                                        <span class="cp-avatar<?php echo $img !== '' ? '' : ' is-fallback'; ?>" aria-hidden="true">
                                            <?php if ($img !== '') : ?>
                                                <img src="<?php echo esc_url(get_theme_file_uri($img)); ?>" alt="" loading="lazy">
                                            <?php else : ?>
                                                <?php echo esc_html($member['initials']); ?>
                                            <?php endif; ?>
                                        </span>
                                        <span class="cp-team-name"><?php echo esc_html($member['name']); ?></span>
                                        <a href="<?php echo esc_url(bugbusters_member_url($member['slug'])); ?>" class="cp-team-link" aria-label="View profile of <?php echo esc_attr($member['name']); ?>">
                                            View profile
                                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                    </aside>

                    <!-- RIGHT: the form -->
                    <div class="cp-form-wrap" id="contact-form">

                        <div class="cp-form-head">
                            <h2>Start a conversation</h2>
                            <p>Choose who you want to reach, then tell us about your idea or question.</p>
                        </div>

                        <?php if ($bb_sent) : ?>
                            <div class="cp-alert cp-alert-success" role="status">
                                <strong>Message sent!</strong> Thank you for reaching out. We'll reply soon.
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($bb_errors)) : ?>
                            <div class="cp-alert cp-alert-error" role="alert">
                                <ul>
                                    <?php foreach ($bb_errors as $error) : ?>
                                        <li><?php echo esc_html($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form class="cp-form" method="post" action="<?php echo esc_url($bb_page . '#contact-form'); ?>" novalidate>

                            <div class="cp-field">
                                <label for="bb_to">Send to</label>
                                <select id="bb_to" name="bb_to">
                                    <?php foreach ($bb_recipients as $key => $rcpt) : ?>
                                        <option value="<?php echo esc_attr($key); ?>" data-email="<?php echo esc_attr($rcpt['email']); ?>" data-img="<?php echo isset($bb_opt_img[$key]) ? esc_url($bb_opt_img[$key]) : ''; ?>" data-initials="<?php echo isset($bb_opt_init[$key]) ? esc_attr($bb_opt_init[$key]) : ''; ?>" <?php selected($bb_old['to'], $key); ?>><?php echo esc_html($rcpt['label']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="cp-hint" id="bb_to_hint" aria-live="polite">
                                    Your message will be sent to
                                    <strong id="bb_to_email"><?php echo esc_html($bb_recipients[$bb_old['to']]['email']); ?></strong>
                                </p>
                            </div>

                            <div class="cp-row">
                                <div class="cp-field">
                                    <label for="bb_name">Your name</label>
                                    <input type="text" id="bb_name" name="bb_name" value="<?php echo esc_attr($bb_old['name']); ?>" placeholder="Juan Dela Cruz" autocomplete="name" required>
                                </div>
                                <div class="cp-field">
                                    <label for="bb_email">Email address</label>
                                    <input type="email" id="bb_email" name="bb_email" value="<?php echo esc_attr($bb_old['email']); ?>" placeholder="you@example.com" autocomplete="email" required>
                                </div>
                            </div>

                            <div class="cp-field">
                                <label for="bb_subject">Subject <span>(optional)</span></label>
                                <input type="text" id="bb_subject" name="bb_subject" value="<?php echo esc_attr($bb_old['subject']); ?>" placeholder="What is this about?">
                            </div>

                            <div class="cp-field">
                                <label for="bb_message">Message</label>
                                <textarea id="bb_message" name="bb_message" rows="6" placeholder="Tell us about your idea or question..." required><?php echo esc_textarea($bb_old['message']); ?></textarea>
                            </div>

                            <!-- Spam trap: real people never see or fill this -->
                            <div class="cp-hp" aria-hidden="true">
                                <label for="bb_website">Leave this empty</label>
                                <input type="text" id="bb_website" name="bb_website" tabindex="-1" autocomplete="off">
                            </div>

                            <?php wp_nonce_field('bb_contact', 'bb_contact_nonce'); ?>

                            <button type="submit" name="bb_contact_submit" value="1" class="btn-primary cp-submit">
                                Send message
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14M13 6l6 6-6 6" />
                                </svg>
                            </button>

                        </form>
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
                        <li><a href="<?php echo esc_url($bb_page); ?>">Contact us</a></li>
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