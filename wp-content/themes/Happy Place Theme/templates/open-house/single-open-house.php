<?php

/**
 * Single Open House Template
 *
 * @package HappyPlace
 */

get_header();
?>

<div class="single-open-house">
    <div class="hph-container">
        <?php
        while (have_posts()) :
            the_post();
            get_template_part('templates/open-house/content', 'open-house');

            // If comments are open or we have at least one comment, load up the comment template.
            if (comments_open() || get_comments_number()) :
                comments_template();
            endif;
        endwhile;
        ?>
    </div>
</div>

<?php
get_footer();
