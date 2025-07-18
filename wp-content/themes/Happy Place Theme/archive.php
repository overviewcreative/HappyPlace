<?php get_header(); ?>
<main>
    <h1>Archive</h1>
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article><?php the_title(); ?></article>
    <?php endwhile; endif; ?>
</main>
<?php get_footer(); ?>
