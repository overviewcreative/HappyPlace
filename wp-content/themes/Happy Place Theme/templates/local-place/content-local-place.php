<?php
/**
 * Content Template for Places
 *
 * @package HappyPlace
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'place-item' ); ?>>
    <header class="entry-header">
        <?php
        if ( is_singular() ) :
            the_title( '<h1 class="entry-title">', '</h1>' );
        else :
            the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
        endif;

        // Place meta information
        $address = get_post_meta( get_the_ID(), 'place_address', true );
        $hours = get_post_meta( get_the_ID(), 'place_hours', true );
        $phone = get_post_meta( get_the_ID(), 'place_phone', true );
        $website = get_post_meta( get_the_ID(), 'place_website', true );
        $rating = get_post_meta( get_the_ID(), 'place_rating', true );
        ?>
        
        <div class="place-meta">
            <?php if ( $address ) : ?>
                <span class="place-address">
                    <i class="fas fa-map-marker-alt"></i>
                    <?php echo esc_html( $address ); ?>
                </span>
            <?php endif; ?>
            
            <?php if ( $phone ) : ?>
                <span class="place-phone">
                    <i class="fas fa-phone"></i>
                    <a href="tel:<?php echo esc_attr( $phone ); ?>"><?php echo esc_html( $phone ); ?></a>
                </span>
            <?php endif; ?>
            
            <?php if ( $website ) : ?>
                <span class="place-website">
                    <i class="fas fa-globe"></i>
                    <a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer">
                        <?php esc_html_e( 'Visit Website', 'happy-place' ); ?>
                    </a>
                </span>
            <?php endif; ?>
            
            <?php if ( $rating ) : ?>
                <span class="place-rating">
                    <i class="fas fa-star"></i>
                    <?php echo esc_html( $rating ); ?>/5
                </span>
            <?php endif; ?>
        </div>
    </header>

    <?php if ( has_post_thumbnail() ) : ?>
        <div class="place-image">
            <?php the_post_thumbnail( 'large' ); ?>
        </div>
    <?php endif; ?>

    <div class="entry-content">
        <?php
        if ( is_singular() ) :
            the_content();
            
            if ( $hours ) :
                echo '<div class="place-hours">';
                echo '<h3>' . esc_html__( 'Business Hours', 'happy-place' ) . '</h3>';
                echo '<pre>' . esc_html( $hours ) . '</pre>';
                echo '</div>';
            endif;
        else :
            the_excerpt();
        endif;
        ?>
    </div>
</article>
