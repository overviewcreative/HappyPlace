<?php
/**
 * Content Template for Cities
 *
 * @package HappyPlace
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'city-item' ); ?>>
    <header class="entry-header">
        <?php
        if ( is_singular() ) :
            the_title( '<h1 class="entry-title">', '</h1>' );
        else :
            the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
        endif;

        // City meta information
        $population = get_post_meta( get_the_ID(), 'city_population', true );
        $area = get_post_meta( get_the_ID(), 'city_area', true );
        $state = get_post_meta( get_the_ID(), 'city_state', true );
        ?>
        
        <div class="city-meta">
            <?php if ( $state ) : ?>
                <span class="city-state">
                    <i class="fas fa-map"></i>
                    <?php echo esc_html( $state ); ?>
                </span>
            <?php endif; ?>
            
            <?php if ( $population ) : ?>
                <span class="city-population">
                    <i class="fas fa-users"></i>
                    <?php echo esc_html( number_format( $population ) ); ?>
                </span>
            <?php endif; ?>
            
            <?php if ( $area ) : ?>
                <span class="city-area">
                    <i class="fas fa-vector-square"></i>
                    <?php echo esc_html( $area ); ?> sq mi
                </span>
            <?php endif; ?>
        </div>
    </header>

    <?php if ( has_post_thumbnail() ) : ?>
        <div class="city-image">
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
