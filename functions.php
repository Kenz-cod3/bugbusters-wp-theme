<?php

/**
 * BugBusters Custom – theme functions
 */

function bugbusters_theme_setup()
{
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
function bugbusters_theme_init_script()
{
?>
    <script>
        (function() {
            var theme = null;

            try {
                theme = localStorage.getItem('bb-theme');
            } catch (e) {}

            if (theme !== 'dark' && theme !== 'light') {
                theme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ?
                    'dark' :
                    'light';
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
function bugbusters_enqueue_assets()
{

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
function bugbusters_required_pages()
{
    return array(
        'contact' => array('Contact', 'page-contact.php'),
        'justin' => array('Justin Harvey Jaraula', 'teamprofile/page-justin.php'),
        'john'    => array('John Micheal Jaranilla', 'teamprofile/page-john.php'),
        'kenneth' => array('Kenneth Carl Milarpis', 'teamprofile/page-kenneth.php'),
        'angel'   => array('Angel Grace Salingay', 'teamprofile/page-angel.php'),
    );
}

/**
 * Finds a published page: first by its template, then by its slug.
 * Returns a WP_Post or null.
 */
function bugbusters_find_page($slug)
{
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
        if (
            get_post_meta($page->ID, '_wp_page_template', true) !== $pages[$slug][1]
            && $slug !== 'contact'
        ) {
            update_post_meta($page->ID, '_wp_page_template', $pages[$slug][1]);
        }
    }

    return $cache[$slug];
}

/**
 * Makes sure a published page exists for a slug (creates / publishes it).
 * Returns the page ID, or 0 if nothing could be created.
 */
function bugbusters_ensure_page($slug)
{
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
function bugbusters_contact_url()
{
    $page = bugbusters_find_page('contact');
    return $page ? get_permalink($page) : home_url('/contact/');
}

/**
 * URL of a member's portfolio page (used by "View profile").
 */
function bugbusters_member_url($slug)
{
    $page = bugbusters_find_page($slug);
    return $page ? get_permalink($page) : home_url('/' . $slug . '/');
}

// Create all the pages when the theme is activated.
function bugbusters_ensure_all_pages()
{
    foreach (array_keys(bugbusters_required_pages()) as $slug) {
        bugbusters_ensure_page($slug);
    }
}
add_action('after_switch_theme', 'bugbusters_ensure_all_pages');

/**
 * Safety net: if someone opens one of these pages (e.g. /kenneth/ or /contact/)
 * before it exists, create it and reload - instead of showing a 404.
 */
function bugbusters_pages_404_rescue()
{
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

/* =====================================================================
   CHATBOT
   - Powered by Google Gemini. Add this line to wp-config.php (NOT in the theme):
         define('BB_GEMINI_API_KEY', 'your-gemini-key');
     The key stays on the server, visitors never see it.
   - Auto-learning: the bot reads your home page text by itself.
===================================================================== */

/**
 * Debug messages in the chat are OFF by default.
 * To see the real error inside the chat while testing, add this to wp-config.php:
 *     define('BB_CHAT_DEBUG', true);
 */
function bugbusters_chat_debug()
{
    return defined('BB_CHAT_DEBUG') && BB_CHAT_DEBUG;
}

/**
 * Everything the chatbot "knows". Edit this to update its answers.
 */
function bugbusters_chat_knowledge()
{
    return array(
        'team_desc' => 'BugBusters is a team of IT students turning ideas into reality through creativity, collaboration, and code.',
        'location'  => 'Alubijid, Misamis Oriental, Philippines',
        'members'   => array(
            array(
                'slug'   => 'justin',
                'keys'   => array('justin', 'harvey', 'jaraula'),
                'name'   => 'Justin Harvey Jaraula',
                'role'   => 'Technical Writer',
                'about'  => 'Turns complex ideas into clear documentation, reports, and user guides.',
                'skills' => 'Documentation, Markdown, Google Docs, Notion',
            ),
            array(
                'slug'   => 'john',
                'keys'   => array('john', 'micheal', 'jaranilla'),
                'name'   => 'John Micheal Jaranilla',
                'role'   => 'Analyst',
                'about'  => 'Studies requirements and data to make sure every solution solves the right problem.',
                'skills' => 'PHP, Node.js, Python, MySQL, Figma, Wireframing, Prototyping',
            ),
            array(
                'slug'   => 'kenneth',
                'keys'   => array('kenneth', 'carl', 'milarpis'),
                'name'   => 'Kenneth Carl Milarpis',
                'role'   => 'Programmer',
                'about'  => 'Writes and tests the code that brings our designs and ideas to life.',
                'skills' => 'HTML5, CSS3, JavaScript, PHP, Laravel, WordPress, Tailwind, Flutter, Java, React, Node.js, Python, MySQL, Arduino, Embedded C, Git',
            ),
            array(
                'slug'   => 'angel',
                'keys'   => array('angel', 'grace', 'salingay'),
                'name'   => 'Angel Grace Salingay',
                'role'   => 'Project Manager',
                'about'  => 'Plans the schedule, guides the team, and keeps every project on track.',
                'skills' => 'Git, GitHub, Planning, Trello, Jira, Notion',
            ),
        ),
    );
}

/**
 * AUTO-LEARNING: reads the text of your own home page (About, Skills,
 * Projects, ...) so the chatbot knows what is on the website.
 *
 * IMPORTANT: the page is NEVER fetched while a visitor is chatting (that can
 * freeze small servers such as Local). It is read in the background by
 * WP-Cron, saved, and refreshed when you save a page or switch the theme.
 */
function bugbusters_chat_refresh_site_content()
{
    $text     = '';
    $response = wp_remote_get(home_url('/'), array(
        'timeout'   => 15,
        'sslverify' => (wp_get_environment_type() === 'local') ? false : true, // Local uses a self-signed cert
    ));

    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        $html = wp_remote_retrieve_body($response);
        $html = preg_replace('#<(script|style|noscript|svg|header|footer|form)\b[^>]*>.*?</\1>#is', ' ', $html);
        $text = wp_strip_all_tags($html);
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = mb_substr(trim($text), 0, 4000);
    }

    if ($text !== '') {
        update_option('bb_chat_site_content', $text, false);
    }
}
add_action('bb_chat_refresh_site_event', 'bugbusters_chat_refresh_site_content');

function bugbusters_chat_schedule_refresh()
{
    if (!wp_next_scheduled('bb_chat_refresh_site_event')) {
        wp_schedule_single_event(time() + 5, 'bb_chat_refresh_site_event');
    }
}
add_action('save_post', 'bugbusters_chat_schedule_refresh');
add_action('after_switch_theme', 'bugbusters_chat_schedule_refresh');

/**
 * Returns the saved site text (instant, no web request).
 * If nothing is saved yet, it asks WP-Cron to read the site in the background.
 */
function bugbusters_chat_site_content()
{
    $text = get_option('bb_chat_site_content', '');
    if ($text === '' || $text === false) {
        bugbusters_chat_schedule_refresh();
        return '';
    }
    return $text;
}

/**
 * Plain-text version of the knowledge, used as the AI system prompt.
 * The wording below controls HOW the chatbot talks (tone, style, language).
 */
function bugbusters_chat_system_prompt()
{
    $k = bugbusters_chat_knowledge();

    $text = "You are the official virtual assistant of the BugBusters Development Team portfolio website.\n\n"

        . "TONE AND STYLE\n"
        . "- Always sound professional, courteous, and polite, like a well-trained customer service representative.\n"
        . "- Write in complete, well-structured sentences with correct grammar and punctuation.\n"
        . "- Do not use slang, jokes, emojis, exclamation overload, markdown, bullet points, or asterisks. Use plain text only.\n"
        . "- Match the length of the answer to the question. For greetings and simple questions, use 1 to 2 sentences. For general questions, use 3 to 4 sentences. For detailed questions (for example about a member's skills, the team, or projects), use 5 to 6 sentences. Never exceed 6 sentences.\n"
        . "- When it is natural, end with a brief offer of further help, for example: \"Is there anything else I can help you with?\"\n\n"

        . "LANGUAGE\n"
        . "- Reply in the same language the visitor uses (English, Tagalog, Bisaya, or Taglish), but always in a formal and respectful register.\n"
        . "- In Tagalog, use respectful words such as \"po\" and \"opo\". In Bisaya, use respectful words such as \"palihog\" and \"salamat\".\n\n"

        . "SCOPE AND ACCURACY\n"
        . "- Answer ONLY about the team, its members, skills, projects, location, and how to contact them, using the information below.\n"
        . "- If the answer is not in the information below, politely say that you do not have that information and recommend that the visitor use the Contact page.\n"
        . "- Never invent projects, prices, schedules, or contact details.\n"
        . "- If the visitor asks about unrelated topics, courteously explain that you can only assist with questions about BugBusters.\n\n"

        . "GREETINGS\n"
        . "- If the visitor only greets you (hi, hello, good morning, good afternoon, good evening, magandang umaga, maayong buntag), "
        . "greet them back in one short, courteous sentence in the same language and ask how you may assist them. "
        . "Do not repeat a long welcome message.\n\n"

        . "INFORMATION ABOUT THE TEAM\n"
        . $k['team_desc'] . "\n"
        . 'The team is based in ' . $k['location'] . ".\n\n"
        . "Team members:\n";

    foreach ($k['members'] as $m) {
        $text .= '- ' . $m['name'] . ' (' . $m['role'] . '): ' . $m['about'] . ' Skills: ' . $m['skills'] . ".\n";
    }
    $text .= "\nContact page: " . bugbusters_contact_url() . "\n";

    $site = bugbusters_chat_site_content();
    if ($site !== '') {
        $text .= "\nText from the website (use it as extra information):\n" . $site . "\n";
    }
    return $text;
}

/**
 * Some Windows / Local setups hang when connecting over IPv6 (cURL error 28).
 * Force IPv4 for Gemini requests only.
 */
function bugbusters_chat_force_ipv4($handle, $args, $url)
{
    if (strpos($url, 'generativelanguage.googleapis.com') !== false) {
        curl_setopt($handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
    }
}
add_action('http_api_curl', 'bugbusters_chat_force_ipv4', 10, 3);

/**
 * Ask Google Gemini. The key is read from wp-config.php:
 *     define('BB_GEMINI_API_KEY', 'your-key');
 * Optional: define('BB_GEMINI_MODEL', 'model-name');  // the model tried first
 *
 * If a model is busy (503 / 429) or not found (404), the next model in the
 * list is tried automatically.
 *
 * Returns array('text' => string|null, 'error' => string|null)
 */
function bugbusters_chat_ai_answer($history)
{

    if (!defined('BB_GEMINI_API_KEY') || !BB_GEMINI_API_KEY) {
        return array('text' => null, 'error' => 'BB_GEMINI_API_KEY is missing in wp-config.php');
    }

    @set_time_limit(90); // allow for slow retries across several models

    // Fastest first. A chatbot FAQ does not need a big "thinking" model.
    $main   = defined('BB_GEMINI_MODEL') ? BB_GEMINI_MODEL : 'gemini-flash-lite-latest';
    $models = array_values(array_unique(array($main, 'gemini-flash-latest', 'gemini-2.5-flash')));

    $contents = array();
    foreach ($history as $h) {
        $contents[] = array(
            'role'  => ($h['role'] === 'assistant') ? 'model' : 'user',
            'parts' => array(array('text' => $h['content'])),
        );
    }

    $system = bugbusters_chat_system_prompt();

    $make_body = function ($fast) use ($system, $contents) {
        // Lower temperature = more consistent, professional wording.
        $config = array('maxOutputTokens' => 2048, 'temperature' => 0.3);
        if ($fast) {
            $config['thinkingConfig'] = array('thinkingLevel' => 'low'); // quicker replies
        }
        return wp_json_encode(array(
            'system_instruction' => array('parts' => array(array('text' => $system))),
            'contents'           => $contents,
            'generationConfig'   => $config,
        ));
    };

    $errors = array();

    foreach ($models as $model) {

        foreach (array(true, false) as $fast) {
            $t        = microtime(true);
            $response = wp_remote_post(
                'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent',
                array(
                    'timeout' => 20,
                    'headers' => array(
                        'content-type'   => 'application/json',
                        'x-goog-api-key' => BB_GEMINI_API_KEY,
                    ),
                    'body'    => $make_body($fast),
                )
            );
            $secs = round(microtime(true) - $t, 1);

            if (is_wp_error($response)) {
                $errors[] = $model . ' - connection error: ' . $response->get_error_message();
                continue 2; // next model
            }

            $code = wp_remote_retrieve_response_code($response);
            $data = json_decode(wp_remote_retrieve_body($response), true);

            if ($code === 200) {
                if (!empty($data['candidates'][0]['content']['parts'][0]['text'])) {
                    return array(
                        'text'  => sanitize_textarea_field($data['candidates'][0]['content']['parts'][0]['text']),
                        'error' => null,
                        'info'  => $model . ', ' . $secs . 's',
                    );
                }
                $errors[] = $model . ' - empty answer (it may have been blocked).';
                continue 2;
            }

            $msg      = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
            $errors[] = $model . ' - Gemini error ' . $code . ': ' . $msg;

            // 400 with the thinking option: the model does not support it -> retry plain.
            if ($code === 400 && $fast) {
                continue;
            }

            // Busy / rate limited / model not found / server error: try the next model.
            if (in_array($code, array(404, 429, 500, 502, 503, 504), true)) {
                continue 2;
            }

            // Anything else (bad key, bad request...) will not be fixed by another model.
            return array('text' => null, 'error' => implode(' | ', $errors));
        }
    }

    return array('text' => null, 'error' => $errors ? implode(' | ', $errors) : 'Unknown error');
}

/**
 * REST endpoint: POST /wp-json/bugbusters/v1/chat
 */
function bugbusters_chat_register_route()
{
    register_rest_route('bugbusters/v1', '/chat', array(
        'methods'             => 'POST',
        'callback'            => 'bugbusters_chat_handle',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'bugbusters_chat_register_route');

function bugbusters_chat_handle(WP_REST_Request $request)
{

    // Simple rate limit: 15 messages per minute per visitor.
    $ip  = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'x';
    $key = 'bb_chat_' . md5($ip);
    $n   = (int) get_transient($key);
    if ($n >= 15) {
        return new WP_REST_Response(array(
            'reply' => 'You are sending messages too quickly. Kindly wait a moment before sending another message.',
            'links' => array(),
        ), 429);
    }
    set_transient($key, $n + 1, MINUTE_IN_SECONDS);

    // Clean the history sent by the browser (last 6 messages, max 500 chars each).
    $raw     = $request->get_param('messages');
    $history = array();
    if (is_array($raw)) {
        foreach (array_slice($raw, -6) as $item) {
            if (!is_array($item) || empty($item['content'])) {
                continue;
            }
            $role      = (isset($item['role']) && $item['role'] === 'assistant') ? 'assistant' : 'user';
            $history[] = array('role' => $role, 'content' => mb_substr(sanitize_textarea_field($item['content']), 0, 500));
        }
    }

    // Must start with a user message and end with one.
    while (!empty($history) && $history[0]['role'] !== 'user') {
        array_shift($history);
    }
    if (empty($history) || end($history)['role'] !== 'user') {
        return new WP_REST_Response(array(
            'reply' => 'Kindly type your message so that we may assist you.',
            'links' => array(),
        ), 400);
    }

    $result = bugbusters_chat_ai_answer($history);

    if ($result['text'] !== null) {
        $links = array();
        if (stripos($result['text'], 'contact') !== false) {
            $links[] = array('label' => 'Contact us', 'url' => bugbusters_contact_url());
        }
        $reply = $result['text'];
        if (bugbusters_chat_debug() && !empty($result['info'])) {
            $reply .= "\n\n[Dev only: " . $result['info'] . ']'; // shows which model answered and how long it took
        }
        return array('reply' => $reply, 'links' => $links);
    }

    // Failed: save the real reason in the PHP error log (for you),
    // and show only a professional message in the chat.
    error_log('[BugBusters chatbot] ' . $result['error']);

    if (bugbusters_chat_debug()) {
        return array('reply' => '[Debug] ' . $result['error'], 'links' => array());
    }

    return array(
        'reply' => 'We apologize, but we are unable to respond at the moment. Please try again in a few minutes, or contact us directly through our Contact page.',
        'links' => array(array('label' => 'Contact us', 'url' => bugbusters_contact_url())),
    );
}

/**
 * Load chatbot CSS + JS on every page.
 */
function bugbusters_chat_assets()
{
    $css = get_theme_file_path('/css/chatbot.css');
    $js  = get_theme_file_path('/js/chatbot.js');

    if (!file_exists($css) || !file_exists($js)) {
        return; // files missing: skip the chatbot instead of breaking the page
    }

    wp_enqueue_style(
        'bugbusters-chatbot',
        get_theme_file_uri('/css/chatbot.css'),
        array('bugbusters-style'),
        filemtime($css)
    );

    wp_enqueue_script(
        'bugbusters-chatbot',
        get_theme_file_uri('/js/chatbot.js'),
        array(),
        filemtime($js),
        true
    );

    wp_localize_script('bugbusters-chatbot', 'BBChat', array(
        'url'     => esc_url_raw(rest_url('bugbusters/v1/chat')),
        // {greeting} is replaced in chatbot.js with Good morning / afternoon / evening
        'welcome' => '{greeting}. Welcome to BugBusters. How may I assist you today?',
        'quick'   => array(), // add quick-reply buttons here if you want, e.g. array('Who is on the team?')
    ));
}
add_action('wp_enqueue_scripts', 'bugbusters_chat_assets');

/**
 * Chat widget markup, printed once in the footer of every template
 * (index, contact, portfolio pages, 404) - no need to edit each file.
 */
function bugbusters_chat_markup()
{
?>
    <div class="bb-chat" id="bb-chat">
        <button class="bb-chat-toggle" id="bb-chat-toggle" type="button" aria-expanded="false" aria-controls="bb-chat-panel" aria-label="Open chat">
            <svg class="bb-ic-chat" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
            </svg>
            <svg class="bb-ic-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M18 6 6 18M6 6l12 12" />
            </svg>
        </button>

        <section class="bb-chat-panel" id="bb-chat-panel" role="dialog" aria-label="BugBusters chat" hidden>
            <header class="bb-chat-head">
                <span class="bb-chat-dot" aria-hidden="true"></span>
                <div>
                    <strong>BugBusters Assistant</strong>
                    <small>Ask about our team &amp; projects</small>
                </div>
            </header>

            <div class="bb-chat-log" id="bb-chat-log" aria-live="polite"></div>
            <div class="bb-chat-quick" id="bb-chat-quick"></div>

            <form class="bb-chat-form" id="bb-chat-form" autocomplete="off">
                <label class="screen-reader-text" for="bb-chat-input">Your message</label>
                <input type="text" id="bb-chat-input" maxlength="300" placeholder="Type your message…" required>
                <button type="submit" aria-label="Send">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m22 2-7 20-4-9-9-4zM22 2 11 13" />
                    </svg>
                </button>
            </form>
        </section>
    </div>
<?php
}
add_action('wp_footer', 'bugbusters_chat_markup');

/**
 * Returns the email design (HTML) with {{placeholders}}.
 */
function bugbusters_email_template()
{
    return '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>New Inquiry</title>
</head>
<body style="margin:0;padding:0;background:#f5f7f6;font-family:Arial,Helvetica,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f7f6;padding:32px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:20px;border:1px solid #eceeed;">
          <tr>
            <td style="padding:32px 36px 0;">

              <!-- Logo -->
              <img src="{{logo}}" alt="BugBusters Development Team" height="48" style="display:block;height:48px;width:auto;border:0;">
              <div style="height:1px;background:#e4e9e6;margin-top:22px;line-height:1px;font-size:1px;">&nbsp;</div>

              <!-- Icon + title -->
              <table role="presentation" align="center" cellpadding="0" cellspacing="0" style="margin:32px auto 0;">
                <tr>
                  <td align="center" width="64" height="64" style="width:64px;height:64px;background:#e8f3ee;border-radius:16px;font-size:30px;line-height:64px;color:#0b7a56;">&#9993;</td>
                </tr>
              </table>
              <h1 style="margin:18px 0 6px;text-align:center;font-size:30px;line-height:1.2;color:#10231d;">New Inquiry</h1>
              <p style="margin:0 0 30px;text-align:center;font-size:15px;color:#6b7773;">You have received a new inquiry from your website.</p>

              <!-- Details -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e4e9e6;border-radius:12px;border-collapse:separate;overflow:hidden;">
                <tr>
                  <td width="130" style="width:130px;padding:14px 18px;background:#e8f3ee;border-bottom:1px solid #ffffff;font-size:14px;color:#10231d;">Name</td>
                  <td style="padding:14px 18px;border-bottom:1px solid #e4e9e6;font-size:15px;font-weight:bold;color:#10231d;">{{name}}</td>
                </tr>
                <tr>
                  <td style="padding:14px 18px;background:#e8f3ee;border-bottom:1px solid #ffffff;font-size:14px;color:#10231d;">Email</td>
                  <td style="padding:14px 18px;border-bottom:1px solid #e4e9e6;font-size:15px;"><a href="{{reply_url}}" style="color:#0b7a56;text-decoration:none;">{{email}}</a></td>
                </tr>
                <tr>
                  <td style="padding:14px 18px;background:#e8f3ee;font-size:14px;color:#10231d;">Subject</td>
                  <td style="padding:14px 18px;font-size:15px;color:#10231d;">{{subject}}</td>
                </tr>
              </table>

              <!-- Message -->
              <h2 style="margin:28px 0 10px;font-size:16px;color:#10231d;">&#9993;&nbsp; Inquiry Message</h2>
              <div style="padding:18px 20px;background:#f3f4f4;border-left:4px solid #0b7a56;border-radius:8px;font-size:15px;line-height:1.7;color:#10231d;">
                {{message}}
              </div>

              <!-- Button -->
              <table role="presentation" align="center" cellpadding="0" cellspacing="0" style="margin:30px auto 0;">
                <tr>
                  <td style="background:#0b7a56;border-radius:12px;">
                    <a href="{{reply_url}}" style="display:inline-block;padding:16px 36px;font-size:16px;font-weight:bold;color:#ffffff;text-decoration:none;">Reply to {{name}}</a>
                  </td>
                </tr>
              </table>

              <!-- Footer -->
              <div style="height:1px;background:#e4e9e6;margin-top:34px;line-height:1px;font-size:1px;">&nbsp;</div>
              <p style="margin:18px 0 0;text-align:center;font-size:13px;line-height:1.7;color:#6b7773;">
                Sent on {{date}}<br>
                &copy; {{year}} BugBusters Development Team.
              </p>
            </td>
          </tr>
          <tr><td style="height:28px;line-height:28px;font-size:1px;">&nbsp;</td></tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
}
/**
 * Search and replace: {{name}} -> real value.
 * Values must already be escaped (esc_html etc.).
 */
function bugbusters_render_email($vars)
{
    $html    = bugbusters_email_template();
    $search  = array();
    $replace = array();

    // Fills {{logo}} automatically, so page-contact.php needs no change
    $vars['logo'] = esc_url(get_theme_file_uri('/images/bugbusters-logo.png'));

    foreach ($vars as $key => $value) {
        $search[]  = '{{' . $key . '}}';
        $replace[] = $value;
    }

    return str_replace($search, $replace, $html);
}

/* =====================================================================
   MAIL (SMTP) - makes the contact form really send emails.
   Uses the BB_SMTP_* values you set in wp-config.php:
     BB_CONTACT_FROM, BB_SMTP_HOST, BB_SMTP_PORT, BB_SMTP_USER, BB_SMTP_PASS
   ===================================================================== */
function bugbusters_smtp_setup($phpmailer)
{
    if (!defined('BB_SMTP_HOST') || !defined('BB_SMTP_USER') || !defined('BB_SMTP_PASS')) {
        return; // not configured: leave the WordPress default
    }

    // Still the placeholder values from the template? Skip SMTP so local
    // testing (e.g. LocalWP's Mailpit) keeps working until real ones are set.
    if (strpos(BB_SMTP_USER, 'yourdomain.com') !== false || BB_SMTP_PASS === 'password-ng-mailbox') {
        return;
    }

    $port = defined('BB_SMTP_PORT') ? (int) BB_SMTP_PORT : 465;

    $phpmailer->isSMTP();
    $phpmailer->Host       = BB_SMTP_HOST;
    $phpmailer->Port       = $port;
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Username   = BB_SMTP_USER;
    $phpmailer->Password   = BB_SMTP_PASS;
    $phpmailer->SMTPSecure = ($port === 465) ? 'ssl' : 'tls';
    $phpmailer->CharSet    = 'UTF-8';

    // The "From" address must match the mailbox, or the SMTP server rejects it.
    $from = defined('BB_CONTACT_FROM') ? BB_CONTACT_FROM : BB_SMTP_USER;
    $phpmailer->setFrom($from, 'BugBusters Website', false);
}
add_action('phpmailer_init', 'bugbusters_smtp_setup');

/* If sending fails, write the real reason to wp-content/debug.log
   (turn on WP_DEBUG + WP_DEBUG_LOG in wp-config.php to see it). */
function bugbusters_mail_failed($error)
{
    error_log('BugBusters mail error: ' . $error->get_error_message());
}
add_action('wp_mail_failed', 'bugbusters_mail_failed');

require_once get_theme_file_path('/billing-guard.php');
require_once get_theme_file_path('/device-block.php');
require_once get_theme_file_path('/visitor-log.php');
