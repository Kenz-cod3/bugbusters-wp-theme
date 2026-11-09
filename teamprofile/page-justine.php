<?php
/**
 * Template Name: Portfolio - Justine
 * Description: Portfolio page of Justine Harvey Jaraula. Edit the details below to update the page.
 *
 * The page is created automatically (slug "justine"). If you ever make it by hand:
 * Pages > Add New > title it "Justine Harvey Jaraula" > Template: "Portfolio - Justine".
 */

$bb_member = array(
    'name'      => 'Justine Harvey Jaraula',
    'role'      => 'Technical Writer',
    'initials'  => 'JH',
    'img'       => 'member-04.png',                 // photo in images/ (or images/team/)
    'desc'      => 'Turns complex ideas into clear documentation, reports, and user guides for the team.',
    'bio'       => '',                   // <- write a longer "About me" here (empty = uses 'desc')
    'areas'     => array('Technical Writing'),
    'tags'      => array('Documentation', 'Markdown', 'Google Docs', 'Notion'),
    'projects'  => array(
        // array('title' => 'Project name', 'desc' => 'What it is.', 'url' => 'https://...', 'tags' => array('Laravel', 'MySQL')),
    ),
    'profile'   => '#',                  // <- Facebook / LinkedIn / GitHub link ('#' = hidden)
    'portfolio' => '',                   // <- optional link to a full portfolio website
);

require get_theme_file_path('/teamprofile/portfolio-layout.php');