<?php

/**
 * Template Name: Portfolio - Kenneth
 * Description: Portfolio page of Kenneth Carl Milarpis. Edit the details below to update the page.
 *
 * The page is created automatically (slug "kenneth"). If you ever make it by hand:
 * Pages > Add New > title it "Kenneth Carl Milarpis" > Template: "Portfolio - Kenneth".
 */

$bb_member = array(
    'name'           => 'Kenneth Carl Milarpis',
    'role'           => 'Programmer',
    'initials'       => 'KC',
    'img'            => 'member-03.png',   // light mode photo in images/ (or images/team/)
    'img_dark'       => 'member-03glass.png',        // dark mode photo (falls back to 'img' if missing)
    'img_hover'      => 'member-03shock1.png',   // light mode hover photo (only Kenneth has one)
    'img_hover_dark' => 'member-03shock.png',  // dark mode hover photo  (only Kenneth has one)
    'desc'           => 'Writes and tests the code that brings our designs and ideas to life.',
    'bio'            => '',                     // <- write a longer "About me" here (empty = uses 'desc')
    'areas'          => array('Web Development', 'Mobile Development', 'Backend & Database', 'IoT & Hardware', 'Tools & Workflow'),
    'tags'           => array('HTML5', 'CSS3', 'JavaScript', 'PHP', 'Laravel', 'WordPress', 'Tailwind', 'Flutter', 'Java', 'React', 'Node.js', 'Python', 'MySQL', 'Arduino', 'Sensors', 'Embedded C', 'Git', 'GitHub', 'Claude Code', 'ChatGPT'),
    'projects'       => array(
        // array('title' => 'Project name', 'desc' => 'What it is.', 'url' => 'https://...', 'tags' => array('Laravel', 'MySQL')),
    ),
    'profile'        => '#',                    // <- Facebook / LinkedIn / GitHub link ('#' = hidden)
    'portfolio'      => '',                     // <- optional link to a full portfolio website
);

require get_theme_file_path('/teamprofile/portfolio-layout.php');
