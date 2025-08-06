<?php

/**
 * Content Template for Transactions
 *
 * @package HappyPlace
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('hph-card hph-card--transaction'); ?>>
    <div class="hph-card__content">
        <h3 class="hph-card__title">
            <a href="<?php the_permalink(); ?>" class="hph-link hph-link--primary"><?php the_title(); ?></a>
        </h3>

        <?php
        // Get transaction details
        $transaction_type = get_post_meta(get_the_ID(), 'transaction_type', true);
        $amount = get_post_meta(get_the_ID(), 'transaction_amount', true);
        $date = get_post_meta(get_the_ID(), 'transaction_date', true);
        $status = get_post_meta(get_the_ID(), 'transaction_status', true);
        ?>

        <div class="hph-card__meta">
            <?php if ($transaction_type) : ?>
                <div class="hph-meta-item">
                    <i class="fas fa-tag hph-icon hph-text-primary"></i>
                    <span><?php echo esc_html($transaction_type); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($amount) : ?>
                <div class="hph-meta-item">
                    <i class="fas fa-dollar-sign hph-icon hph-text-primary"></i>
                    <span><?php echo esc_html($amount); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($date) : ?>
                <div class="hph-meta-item">
                    <i class="fas fa-calendar hph-icon hph-text-primary"></i>
                    <span><?php echo esc_html($date); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($status) : ?>
            <div class="hph-card__footer">
                <div class="hph-status hph-status--<?php echo esc_attr(strtolower($status)); ?>">
                    <?php echo esc_html($status); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</article>