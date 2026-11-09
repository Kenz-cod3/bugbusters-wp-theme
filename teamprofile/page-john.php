<?php

/**
 * Template Name: Portfolio - John
 * Description: Portfolio page of John Micheal Jaranilla. Edit the details below to update the page.
 *
 * The page is created automatically (slug "john"). If you ever make it by hand:
 * Pages > Add New > title it "John Micheal Jaranilla" > Template: "Portfolio - John".
 */

$bb_member = array(
    'name'      => 'John Micheal Jaranilla',
    'role'      => 'Analyst',
    'initials'  => 'JM',
    'img'       => 'member-01.png',   // light mode photo
    'img_dark'  => 'member-01glass.png',        // dark mode photo
    'desc'      => 'Studies requirements and data to make sure every solution solves the right problem.',
    'bio'       => '',                     // <- write a longer "About me" here (empty = uses 'desc')
    'areas'     => array('Backend & Database', 'UI/UX Design'),
    'tags'      => array('PHP', 'Node.js', 'Python', 'MySQL', 'Figma', 'Wireframing', 'Prototyping'),
    'projects'  => array(
        // array('title' => 'Project name', 'desc' => 'What it is.', 'url' => 'https://...', 'tags' => array('Laravel', 'MySQL')),
    ),
    'profile'   => '#',                    // <- Facebook / LinkedIn / GitHub link ('#' = hidden)
    'portfolio' => '',                     // <- optional link to a full portfolio website
);

require get_theme_file_path('/teamprofile/portfolio-layout.php');
