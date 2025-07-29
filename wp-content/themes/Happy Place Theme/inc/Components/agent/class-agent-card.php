<?php
/**
 * Agent Card Component
 *
 * Display component for agent profiles and contact information.
 *
 * @package HappyPlace\Components\Agent
 * @since 2.0.0
 */

namespace HappyPlace\Components\Agent;

use HappyPlace\Components\Base_Component;

if (!defined('ABSPATH')) {
    exit;
}

class Agent_Card extends Base_Component {
    
    /**
     * Component name identifier
     *
     * @return string
     */
    protected function get_component_name() {
        return 'agent-card';
    }
    
    /**
     * Default component properties
     *
     * @return array
     */
    protected function get_defaults() {
        return [
            'agent_id' => 0,
            'style' => 'standard', // standard, compact, featured, minimal
            'show_photo' => true,
            'show_contact_info' => true,
            'show_social_links' => false,
            'show_bio' => false,
            'show_specialties' => false,
            'show_stats' => false,
            'show_reviews' => false,
            'show_contact_form' => false,
            'enable_modal' => false,
            'link_to_profile' => true,
            'orientation' => 'vertical', // vertical, horizontal
            'size' => 'medium', // small, medium, large
            'border_radius' => 'default',
            'shadow' => true,
            'hover_effect' => 'lift',
            'contact_button_text' => 'Contact Agent',
            'phone_format' => 'link', // link, text, button
            'email_format' => 'link', // link, text, button
            'custom_class' => ''
        ];
    }
    
    /**
     * Validate component properties
     */
    protected function validate_props() {
        if (empty($this->get_prop('agent_id'))) {
            $this->add_validation_error('agent_id is required');
        }
    }
    
    /**
     * Render the component
     *
     * @return string
     */
    protected function render() {
        $agent_id = $this->get_prop('agent_id');
        $agent = hph_get_agent_data($agent_id);
        
        if (!$agent) {
            return $this->render_no_agent();
        }
        
        $card_classes = $this->get_card_classes();
        $agent_url = $this->get_prop('link_to_profile') ? hph_get_agent_url($agent_id) : null;
        
        ob_start();
        ?>
        <div class="hph-agent-card <?php echo esc_attr($card_classes); ?>" 
             data-component="agent-card"
             data-agent-id="<?php echo esc_attr($agent_id); ?>"
             data-style="<?php echo esc_attr($this->get_prop('style')); ?>">
            
            <?php if ($this->get_prop('orientation') === 'horizontal'): ?>
                <?php $this->render_horizontal_layout($agent, $agent_url); ?>
            <?php else: ?>
                <?php $this->render_vertical_layout($agent, $agent_url); ?>
            <?php endif; ?>
            
            <?php if ($this->get_prop('show_contact_form') && $this->get_prop('enable_modal')): ?>
                <?php $this->render_contact_modal($agent); ?>
            <?php endif; ?>
            
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render vertical layout
     */
    private function render_vertical_layout($agent, $agent_url) {
        echo '<div class="hph-agent-card__content hph-agent-card__content--vertical">';
        
        // Photo section
        if ($this->get_prop('show_photo')) {
            $this->render_agent_photo($agent, $agent_url);
        }
        
        // Info section
        echo '<div class="hph-agent-card__info">';
        
        $this->render_agent_name($agent, $agent_url);
        $this->render_agent_title($agent);
        
        if ($this->get_prop('show_bio')) {
            $this->render_agent_bio($agent);
        }
        
        if ($this->get_prop('show_specialties')) {
            $this->render_agent_specialties($agent);
        }
        
        if ($this->get_prop('show_stats')) {
            $this->render_agent_stats($agent);
        }
        
        if ($this->get_prop('show_reviews')) {
            $this->render_agent_reviews($agent);
        }
        
        echo '</div>';
        
        // Contact section
        if ($this->get_prop('show_contact_info')) {
            $this->render_contact_section($agent);
        }
        
        echo '</div>';
    }
    
    /**
     * Render horizontal layout
     */
    private function render_horizontal_layout($agent, $agent_url) {
        echo '<div class="hph-agent-card__content hph-agent-card__content--horizontal">';
        
        // Left side - Photo
        if ($this->get_prop('show_photo')) {
            echo '<div class="hph-agent-card__left">';
            $this->render_agent_photo($agent, $agent_url);
            echo '</div>';
        }
        
        // Right side - Info & Contact
        echo '<div class="hph-agent-card__right">';
        
        echo '<div class="hph-agent-card__info">';
        $this->render_agent_name($agent, $agent_url);
        $this->render_agent_title($agent);
        
        if ($this->get_prop('show_bio')) {
            $this->render_agent_bio($agent);
        }
        
        if ($this->get_prop('show_specialties')) {
            $this->render_agent_specialties($agent);
        }
        echo '</div>';
        
        // Contact info in horizontal layout
        if ($this->get_prop('show_contact_info')) {
            $this->render_contact_section($agent);
        }
        
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Render agent photo
     */
    private function render_agent_photo($agent, $agent_url = null) {
        $photo_url = $agent['profile_image'] ?? '';
        $name = $agent['display_name'] ?? '';
        
        echo '<div class="hph-agent-card__photo">';
        
        $photo_content = '';
        if (!empty($photo_url)) {
            $photo_content = '<img src="' . esc_url($photo_url) . '" alt="' . esc_attr($name) . '" class="hph-agent-photo">';
        } else {
            $initials = $this->get_initials($name);
            $photo_content = '<div class="hph-agent-photo hph-agent-photo--placeholder">';
            $photo_content .= '<span class="hph-agent-initials">' . esc_html($initials) . '</span>';
            $photo_content .= '</div>';
        }
        
        if ($agent_url) {
            echo '<a href="' . esc_url($agent_url) . '" class="hph-agent-photo-link">';
            echo $photo_content;
            echo '</a>';
        } else {
            echo $photo_content;
        }
        
        // Status indicator
        if (!empty($agent['status']) && $agent['status'] === 'online') {
            echo '<div class="hph-agent-status hph-agent-status--online" title="' . esc_attr__('Available', 'happy-place') . '"></div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render agent name
     */
    private function render_agent_name($agent, $agent_url = null) {
        $name = $agent['display_name'] ?? '';
        
        if (empty($name)) {
            return;
        }
        
        echo '<div class="hph-agent-card__name">';
        
        if ($agent_url) {
            echo '<a href="' . esc_url($agent_url) . '" class="hph-agent-name-link">';
            echo '<h3 class="hph-agent-name">' . esc_html($name) . '</h3>';
            echo '</a>';
        } else {
            echo '<h3 class="hph-agent-name">' . esc_html($name) . '</h3>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render agent title/position
     */
    private function render_agent_title($agent) {
        $title = $agent['title'] ?? $agent['position'] ?? '';
        
        if (!empty($title)) {
            echo '<div class="hph-agent-card__title">';
            echo '<span class="hph-agent-title">' . esc_html($title) . '</span>';
            echo '</div>';
        }
    }
    
    /**
     * Render agent bio
     */
    private function render_agent_bio($agent) {
        $bio = $agent['bio'] ?? $agent['description'] ?? '';
        
        if (!empty($bio)) {
            echo '<div class="hph-agent-card__bio">';
            
            $style = $this->get_prop('style');
            if ($style === 'compact' || $style === 'minimal') {
                // Truncate bio for compact styles
                $bio = wp_trim_words($bio, 20, '...');
            }
            
            echo '<p class="hph-agent-bio">' . esc_html($bio) . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Render agent specialties
     */
    private function render_agent_specialties($agent) {
        $specialties = $agent['specialties'] ?? [];
        
        if (empty($specialties)) {
            return;
        }
        
        if (is_string($specialties)) {
            $specialties = explode(',', $specialties);
        }
        
        echo '<div class="hph-agent-card__specialties">';
        echo '<h4 class="hph-specialties-title">' . esc_html__('Specialties', 'happy-place') . '</h4>';
        echo '<ul class="hph-specialties-list">';
        
        foreach ($specialties as $specialty) {
            $specialty = trim($specialty);
            if (!empty($specialty)) {
                echo '<li class="hph-specialty-item">' . esc_html($specialty) . '</li>';
            }
        }
        
        echo '</ul>';
        echo '</div>';
    }
    
    /**
     * Render agent stats
     */
    private function render_agent_stats($agent) {
        $stats = hph_get_agent_stats($this->get_prop('agent_id'));
        
        if (empty($stats)) {
            return;
        }
        
        echo '<div class="hph-agent-card__stats">';
        echo '<h4 class="hph-stats-title">' . esc_html__('Performance', 'happy-place') . '</h4>';
        echo '<div class="hph-stats-grid">';
        
        if ($stats['listings_active'] > 0) {
            echo '<div class="hph-stat-item">';
            echo '<span class="hph-stat-value">' . esc_html($stats['listings_active']) . '</span>';
            echo '<span class="hph-stat-label">' . esc_html__('Active Listings', 'happy-place') . '</span>';
            echo '</div>';
        }
        
        if ($stats['sales_ytd'] > 0) {
            echo '<div class="hph-stat-item">';
            echo '<span class="hph-stat-value">' . esc_html($stats['sales_ytd']) . '</span>';
            echo '<span class="hph-stat-label">' . esc_html__('Sales This Year', 'happy-place') . '</span>';
            echo '</div>';
        }
        
        if ($stats['avg_days_on_market'] > 0) {
            echo '<div class="hph-stat-item">';
            echo '<span class="hph-stat-value">' . esc_html($stats['avg_days_on_market']) . '</span>';
            echo '<span class="hph-stat-label">' . esc_html__('Avg Days on Market', 'happy-place') . '</span>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Render agent reviews
     */
    private function render_agent_reviews($agent) {
        $reviews = hph_get_agent_reviews($this->get_prop('agent_id'), 1); // Get latest review
        
        if (empty($reviews)) {
            return;
        }
        
        $review = $reviews[0];
        $rating = $review['rating'] ?? 0;
        
        echo '<div class="hph-agent-card__review">';
        echo '<h4 class="hph-review-title">' . esc_html__('Latest Review', 'happy-place') . '</h4>';
        
        if ($rating > 0) {
            echo '<div class="hph-review-rating">';
            for ($i = 1; $i <= 5; $i++) {
                $filled = $i <= $rating ? 'hph-star--filled' : '';
                echo '<span class="hph-star ' . $filled . '" aria-hidden="true">★</span>';
            }
            echo '</div>';
        }
        
        if (!empty($review['comment'])) {
            $comment = wp_trim_words($review['comment'], 15, '...');
            echo '<p class="hph-review-comment">"' . esc_html($comment) . '"</p>';
        }
        
        if (!empty($review['reviewer_name'])) {
            echo '<p class="hph-review-author">- ' . esc_html($review['reviewer_name']) . '</p>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render contact section
     */
    private function render_contact_section($agent) {
        echo '<div class="hph-agent-card__contact">';
        
        $phone = $agent['phone'] ?? '';
        $email = $agent['email'] ?? '';
        
        // Phone
        if (!empty($phone)) {
            $this->render_contact_item('phone', $phone);
        }
        
        // Email
        if (!empty($email)) {
            $this->render_contact_item('email', $email);
        }
        
        // Social links
        if ($this->get_prop('show_social_links')) {
            $this->render_social_links($agent);
        }
        
        // Contact button/form
        if ($this->get_prop('show_contact_form')) {
            $this->render_contact_button($agent);
        }
        
        echo '</div>';
    }
    
    /**
     * Render individual contact item
     */
    private function render_contact_item($type, $value) {
        $format = $this->get_prop($type . '_format');
        
        echo '<div class="hph-contact-item hph-contact-item--' . esc_attr($type) . '">';
        
        if ($format === 'button') {
            $href = $type === 'phone' ? 'tel:' . $value : 'mailto:' . $value;
            $label = $type === 'phone' ? __('Call', 'happy-place') : __('Email', 'happy-place');
            
            echo '<a href="' . esc_attr($href) . '" class="hph-contact-button hph-contact-button--' . esc_attr($type) . '">';
            echo '<span class="hph-contact-icon hph-icon--' . esc_attr($type) . '" aria-hidden="true"></span>';
            echo '<span class="hph-contact-label">' . esc_html($label) . '</span>';
            echo '</a>';
            
        } elseif ($format === 'link') {
            $href = $type === 'phone' ? 'tel:' . $value : 'mailto:' . $value;
            
            echo '<a href="' . esc_attr($href) . '" class="hph-contact-link hph-contact-link--' . esc_attr($type) . '">';
            echo '<span class="hph-contact-icon hph-icon--' . esc_attr($type) . '" aria-hidden="true"></span>';
            echo '<span class="hph-contact-value">' . esc_html($value) . '</span>';
            echo '</a>';
            
        } else {
            // Text format
            echo '<div class="hph-contact-text hph-contact-text--' . esc_attr($type) . '">';
            echo '<span class="hph-contact-icon hph-icon--' . esc_attr($type) . '" aria-hidden="true"></span>';
            echo '<span class="hph-contact-value">' . esc_html($value) . '</span>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render social links
     */
    private function render_social_links($agent) {
        $social_links = [
            'facebook' => $agent['facebook_url'] ?? '',
            'instagram' => $agent['instagram_url'] ?? '',
            'linkedin' => $agent['linkedin_url'] ?? '',
            'twitter' => $agent['twitter_url'] ?? ''
        ];
        
        $has_links = array_filter($social_links);
        
        if (empty($has_links)) {
            return;
        }
        
        echo '<div class="hph-agent-card__social">';
        echo '<h4 class="hph-social-title">' . esc_html__('Connect', 'happy-place') . '</h4>';
        echo '<div class="hph-social-links">';
        
        foreach ($social_links as $platform => $url) {
            if (!empty($url)) {
                echo '<a href="' . esc_url($url) . '" class="hph-social-link hph-social-link--' . esc_attr($platform) . '" target="_blank" rel="noopener">';
                echo '<span class="hph-social-icon hph-icon--' . esc_attr($platform) . '" aria-hidden="true"></span>';
                echo '<span class="hph-sr-only">' . esc_html(ucfirst($platform)) . '</span>';
                echo '</a>';
            }
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Render contact button
     */
    private function render_contact_button($agent) {
        $button_text = $this->get_prop('contact_button_text');
        $enable_modal = $this->get_prop('enable_modal');
        
        echo '<div class="hph-agent-card__contact-action">';
        
        if ($enable_modal) {
            echo '<button type="button" class="hph-button hph-button--primary hph-contact-agent-btn" data-agent-id="' . esc_attr($this->get_prop('agent_id')) . '">';
            echo esc_html($button_text);
            echo '</button>';
        } else {
            // Simple mailto link
            $email = $agent['email'] ?? '';
            $subject = sprintf(__('Inquiry from %s', 'happy-place'), get_bloginfo('name'));
            
            echo '<a href="mailto:' . esc_attr($email) . '?subject=' . esc_attr($subject) . '" class="hph-button hph-button--primary">';
            echo esc_html($button_text);
            echo '</a>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render contact modal (if enabled)
     */
    private function render_contact_modal($agent) {
        echo '<div class="hph-agent-contact-modal" id="hph-contact-modal-' . esc_attr($this->get_prop('agent_id')) . '" style="display: none;">';
        echo '<div class="hph-modal-overlay">';
        echo '<div class="hph-modal-content">';
        
        echo '<div class="hph-modal-header">';
        echo '<h3 class="hph-modal-title">' . esc_html(sprintf(__('Contact %s', 'happy-place'), $agent['display_name'])) . '</h3>';
        echo '<button type="button" class="hph-modal-close" aria-label="' . esc_attr__('Close', 'happy-place') . '">&times;</button>';
        echo '</div>';
        
        echo '<div class="hph-modal-body">';
        // Contact form would be rendered here
        echo '<div class="hph-contact-form-placeholder">';
        echo '<p>' . esc_html__('Contact form will be implemented here.', 'happy-place') . '</p>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Render no agent message
     */
    private function render_no_agent() {
        ob_start();
        ?>
        <div class="hph-agent-card hph-agent-card--no-data" data-component="agent-card">
            <div class="hph-no-agent">
                <div class="hph-no-agent-icon" aria-hidden="true">👤</div>
                <p class="hph-no-agent-text"><?php esc_html_e('Agent information not available.', 'happy-place'); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get card CSS classes
     */
    private function get_card_classes() {
        $classes = [];
        
        $classes[] = 'hph-agent-card--' . $this->get_prop('style');
        $classes[] = 'hph-agent-card--' . $this->get_prop('orientation');
        $classes[] = 'hph-agent-card--' . $this->get_prop('size');
        
        if ($this->get_prop('shadow')) {
            $classes[] = 'hph-agent-card--shadow';
        }
        
        if ($this->get_prop('hover_effect') !== 'none') {
            $classes[] = 'hph-agent-card--hover-' . $this->get_prop('hover_effect');
        }
        
        $border_radius = $this->get_prop('border_radius');
        if ($border_radius !== 'default') {
            $classes[] = 'hph-agent-card--radius-' . $border_radius;
        }
        
        $custom_class = $this->get_prop('custom_class');
        if (!empty($custom_class)) {
            $classes[] = $custom_class;
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * Get initials from name for placeholder photo
     */
    private function get_initials($name) {
        if (empty($name)) {
            return '??';
        }
        
        $parts = explode(' ', trim($name));
        $initials = '';
        
        foreach ($parts as $part) {
            if (!empty($part)) {
                $initials .= strtoupper(substr($part, 0, 1));
                if (strlen($initials) >= 2) {
                    break;
                }
            }
        }
        
        return $initials ?: '??';
    }
}
