<?php

/**
 * Template Name: Listing Page
 *
 * @package HappyPlace
 */

get_header();

while (have_posts()) :
    the_post();
    get_template_part('templates/content', 'listing');
endwhile;

get_footer();
