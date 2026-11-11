<?php
/**
 * Template Name: Portfolio - Angel
 * Description: Portfolio page of Angel Grace Salingay. Edit the details below to update the page.
 *
 * The page is created automatically (slug "angel"). If you ever make it by hand:
 * Pages > Add New > title it "Angel Grace Salingay" > Template: "Portfolio - Angel".
 */

$bb_member = array(
    'name'      => 'Angel Grace Salingay',
    'role'      => 'Project Manager',
    'initials'  => 'AG',
    'img'       => 'member-02.png',
    'img_dark'  => 'member-02glass.png',                // photo in images/ (or images/team/)
    'desc'      => 'Plans the schedule, guides the team, and keeps every project on track.',
    'bio'       => '',                   // <- write a longer "About me" here (empty = uses 'desc')
    'areas'     => array('Tools & Workflow', 'Project Management'),
    'tags'      => array('Git', 'GitHub', 'Claude Code', 'ChatGPT', 'Planning', 'Trello', 'Jira', 'Notion'),
    'projects'  => array(
        // array('title' => 'Project name', 'desc' => 'What it is.', 'url' => 'https://...', 'tags' => array('Laravel', 'MySQL')),
    ),
    'profile'   => '#',                  // <- Facebook / LinkedIn / GitHub link ('#' = hidden)
    'portfolio' => '',                   // <- optional link to a full portfolio website
);

require get_theme_file_path('/teamprofile/portfolio-layout.php');