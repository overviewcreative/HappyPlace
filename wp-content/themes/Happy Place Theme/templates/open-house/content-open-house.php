<?php
/**
 * Content Template for Open Houses
 *
 * @package HappyPlace
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'open-house-item' ); ?>>
    <header class="entry-header">
        <?php
        if ( is_singular() ) :
            the_title( '<h1 class="entry-title">', '</h1>' );
        else :
            the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
        endif;
        ?>

        <div class="open-house-meta">
            <?php
            // Add custom meta information for open houses
            $date = get_post_meta( get_the_ID(), 'open_house_date', true );
            $time = get_post_meta( get_the_ID(), 'open_house_time', true );
            if ( $date && $time ) :
            ?>
                <div class="open-house-schedule">
                    <time datetime="<?php echo esc_attr( $date ); ?>"><?php echo esc_html( $date ); ?></time>
                    <span class="time"><?php echo esc_html( $time ); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <?php if ( has_post_thumbnail() ) : ?>
        <div class="open-house-image">
            <?php the_post_thumbnail( 'large' ); ?>
        </div>
    <?php endif; ?>

    <div class="entry-content">
        <?php
        if ( is_singular() ) :
            the_content();
        else :
            the_excerpt();
        endif;
        ?>
    </div>
</article>
