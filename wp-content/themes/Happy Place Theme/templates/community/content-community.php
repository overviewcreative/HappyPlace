<?php
/**
 * Content Template for Communities
 *
 * @package HappyPlace
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'community-item' ); ?>>
    <header class="entry-header">
        <?php
        if ( is_singular() ) :
            the_title( '<h1 class="entry-title">', '</h1>' );
        else :
            the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
        endif;

        // Community meta information
        $location = get_post_meta( get_the_ID(), 'community_location', true );
        $population = get_post_meta( get_the_ID(), 'community_population', true );
        $amenities = get_post_meta( get_the_ID(), 'community_amenities', true );
        ?>
        
        <div class="community-meta">
            <?php if ( $location ) : ?>
                <span class="community-location">
                    <i class="fas fa-map-marker-alt"></i>
                    <?php echo esc_html( $location ); ?>
                </span>
            <?php endif; ?>
            
            <?php if ( $population ) : ?>
                <span class="community-population">
                    <i class="fas fa-users"></i>
                    <?php echo esc_html( number_format( $population ) ); ?>
                </span>
            <?php endif; ?>
        </div>
    </header>

    <?php if ( has_post_thumbnail() ) : ?>
        <div class="community-image">
            <?php the_post_thumbnail( 'large' ); ?>
        </div>
    <?php endif; ?>

    <div class="entry-content">
        <?php
        if ( is_singular() ) :
            the_content();
            
            if ( $amenities ) :
                echo '<div class="community-amenities">';
                echo '<h3>' . esc_html__( 'Community Amenities', 'happy-place' ) . '</h3>';
                echo '<ul>';
                foreach ( $amenities as $amenity ) {
                    echo '<li>' . esc_html( $amenity ) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            endif;
        else :
            the_excerpt();
        endif;
        ?>
    </div>
</article>
