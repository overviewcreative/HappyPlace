<?php

/**
 * Single Transaction Template
 *
 * @package HappyPlace
 */

get_header();
?>

<main class="hph-site-main hph-site-main--single">
    <div class="hph-container">
        <?php
        while (have_posts()) :
            the_post();

            // Get transaction details
            $transaction_type = get_post_meta(get_the_ID(), 'transaction_type', true);
            $property_id = get_post_meta(get_the_ID(), 'property_id', true);
            $amount = get_post_meta(get_the_ID(), 'transaction_amount', true);
            $date = get_post_meta(get_the_ID(), 'transaction_date', true);
            $status = get_post_meta(get_the_ID(), 'transaction_status', true);
        ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class('hph-single-transaction'); ?>>
                <header class="hph-entry-header">
                    <h1 class="hph-entry-title"><?php the_title(); ?></h1>

                    <div class="hph-transaction-meta">
                        <?php if ($transaction_type) : ?>
                            <div class="hph-meta-item">
                                <i class="fas fa-tag hph-icon"></i>
                                <span><?php echo esc_html($transaction_type); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($amount) : ?>
                            <div class="hph-meta-item">
                                <i class="fas fa-dollar-sign hph-icon"></i>
                                <span><?php echo esc_html($amount); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($date) : ?>
                            <div class="hph-meta-item">
                                <i class="fas fa-calendar hph-icon"></i>
                                <span><?php echo esc_html($date); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($status) : ?>
                            <div class="hph-meta-item">
                                <i class="fas fa-info-circle hph-icon"></i>
                                <span class="hph-status hph-status--<?php echo esc_attr(strtolower($status)); ?>">
                                    <?php echo esc_html($status); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </header>

                <?php if ($property_id) : ?>
                    <div class="hph-associated-property">
                        <h2><?php esc_html_e('Property Details', 'happy-place'); ?></h2>
                        <?php
                        $property = get_post($property_id);
                        if ($property) {
                            setup_postdata($property);
                            get_template_part('templates/listing/content', 'listing');
                            wp_reset_postdata();
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <div class="hph-entry-content">
                    <?php the_content(); ?>
                </div>
            </article>

        <?php endwhile; ?>
    </div>
</main>

<?php
get_footer();
