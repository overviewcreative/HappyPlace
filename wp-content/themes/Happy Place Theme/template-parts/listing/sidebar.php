<?php
/**
 * Sidebar - Agent Card & Mortgage Calculator - Bridge Function Version
 * Professional implementation matching .hph-sidebar CSS component
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get listing ID from parent template
$listing_id = $listing_id ?? get_the_ID();

// Use enhanced bridge functions with fallbacks
$agent_data = function_exists('hph_get_listing_agent') ? hph_get_listing_agent($listing_id) : null;
$raw_price = function_exists('hph_bridge_get_price') ? hph_bridge_get_price($listing_id, false) : 0;
$formatted_price = function_exists('hph_bridge_get_price_formatted') ? hph_bridge_get_price_formatted($listing_id, 'standard') : '';
$short_price = function_exists('hph_bridge_get_price_formatted') ? hph_bridge_get_price_formatted($listing_id, 'short') : '';

// Add demo agent data for lewes-colonial if no real agent data
if (!$agent_data || empty($agent_data['name'])) {
    $post = get_post($listing_id);
    if ($post && $post->post_name === 'lewes-colonial') {
        $agent_data = [
            'name' => 'Pat Gallagher',
            'title' => 'REALTOR®',
            'phone' => '(302) 555-0123',
            'email' => 'pat@theparkergroup.com',
            'license' => 'DE License #RS-12345',
            'bio' => 'Experienced real estate professional specializing in Delaware coastal properties.',
            'photo' => ''
        ];
    }
}

// Show sidebar with agent data (real or demo)
$show_agent = $agent_data && !empty($agent_data['name']);

// Use agent data (real or demo)
$agent = [];
if ($show_agent) {
    $agent = [
        'name' => $agent_data['name'],
    'title' => $agent_data['title'] ?? '',
    'phone' => $agent_data['phone'] ?? '',
    'email' => $agent_data['email'] ?? '',
    'license' => $agent_data['license'] ?? '',
    'bio' => $agent_data['bio'] ?? '',
    'photo' => $agent_data['photo'] ?? ''
];
}
// Calculate mortgage defaults (only if price available)
$price = $raw_price > 0 ? $raw_price : 0;
$down_payment = $price > 0 ? round($price * 0.2) : 0; // 20% down
$interest_rate = 6.75;
?>

<!-- Contact Agent -->
<div class="hph-agent-card">
    <div class="hph-agent-card__header">
        <img src="<?php echo esc_url($agent['photo']); ?>" 
             alt="<?php echo esc_attr($agent['name']); ?>" 
             class="hph-agent-card__avatar">
        
        <div class="hph-agent-card__name"><?php echo esc_html($agent['name']); ?></div>
        <div class="hph-agent-card__title"><?php echo esc_html($agent['title']); ?></div>
        <div class="hph-agent-card__company"><?php echo esc_html($agent['company']); ?></div>
    </div>
    
    <div class="hph-agent-card__contact">
        <?php if ($agent['phone']): ?>
        <div class="hph-contact-item">
            <i class="fas fa-phone hph-contact-item__icon"></i>
            <span><?php echo esc_html($agent['phone']); ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($agent['email']): ?>
        <div class="hph-contact-item">
            <i class="fas fa-envelope hph-contact-item__icon"></i>
            <span><?php echo esc_html($agent['email']); ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="hph-agent-card__actions">
        <button class="hph-btn hph-btn--primary" data-action="call" data-phone="<?php echo esc_attr($agent['phone']); ?>">
            <i class="fas fa-phone"></i>
            Call Agent
        </button>
        <button class="hph-btn hph-btn--secondary" data-action="message" data-email="<?php echo esc_attr($agent['email']); ?>">
            <i class="fas fa-envelope"></i>
            Send Message
        </button>
    </div>
</div>

    <!-- Mortgage Calculator -->
    <div class="sidebar-card mortgage-calculator">
        <h3 class="sidebar-card-title">
            <i class="fas fa-calculator"></i>
            Mortgage Calculator
        </h3>
        
        <div class="calculator-form">
            <div class="form-group">
                <label for="loan-amount">Home Price</label>
                <input type="number" id="loan-amount" class="form-control" value="<?php echo esc_attr($price); ?>" placeholder="Home Price">
            </div>
            
            <div class="form-group">
                <label for="down-payment">Down Payment</label>
                <div class="input-group">
                    <input type="number" id="down-payment" class="form-control" value="20" placeholder="20">
                    <span class="input-group-text">%</span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="interest-rate">Interest Rate</label>
                <div class="input-group">
                    <input type="number" id="interest-rate" class="form-control" value="7.5" step="0.01" placeholder="7.5">
                    <span class="input-group-text">%</span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="loan-term">Loan Term</label>
                <select id="loan-term" class="form-control">
                    <option value="30">30 years</option>
                    <option value="25">25 years</option>
                    <option value="20">20 years</option>
                    <option value="15">15 years</option>
                    <option value="10">10 years</option>
                </select>
            </div>
            
            <div class="calculator-result">
                <div class="monthly-payment">
                    <span class="label">Monthly Payment</span>
                    <span class="amount" id="monthly-payment">$0</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Days on Market -->
    <?php 
    $days_on_market = hph_bridge_get_days_on_market($listing_id);
    $list_date = hph_bridge_get_list_date($listing_id);
    if ($days_on_market || $list_date): 
    ?>
    <div class="sidebar-card days-on-market">
        <h3 class="sidebar-card-title">
            <i class="fas fa-calendar-day"></i>
            Market Information
        </h3>
        
        <div class="market-stats">
            <?php if ($days_on_market): ?>
            <div class="stat-item">
                <div class="stat-label">Days on Market</div>
                <div class="stat-value"><?php echo esc_html($days_on_market); ?> days</div>
            </div>
            <?php endif; ?>
            
            <?php if ($list_date): ?>
            <div class="stat-item">
                <div class="stat-label">Listed Date</div>
                <div class="stat-value"><?php echo esc_html($list_date); ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Open Houses -->
    <?php 
    $open_houses = hph_bridge_get_open_houses($listing_id, true);
    $next_open_house = hph_bridge_get_next_open_house($listing_id);
    if (!empty($open_houses)): 
    ?>
    <div class="sidebar-card open-houses">
        <h3 class="sidebar-card-title">
            <i class="fas fa-home"></i>
            Open Houses
        </h3>
        
        <?php if ($next_open_house): ?>
        <div class="next-open-house">
            <div class="open-house-highlight">
                <div class="open-house-date">
                    <i class="fas fa-calendar"></i>
                    <?php echo esc_html($next_open_house['formatted_date']); ?>
                </div>
                <div class="open-house-time">
                    <i class="fas fa-clock"></i>
                    <?php echo esc_html($next_open_house['formatted_start_time']); ?> - <?php echo esc_html($next_open_house['formatted_end_time']); ?>
                </div>
                
                <?php if ($next_open_house['hosting_agent']): ?>
                <div class="hosting-agent">
                    <i class="fas fa-user"></i>
                    Hosted by <?php echo esc_html($next_open_house['hosting_agent']->post_title); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($next_open_house['rsvp_required']): ?>
                <div class="rsvp-required">
                    <i class="fas fa-exclamation-circle"></i>
                    RSVP Required
                    <?php if ($next_open_house['max_attendees']): ?>
                    <span class="max-attendees">(Max <?php echo esc_html($next_open_house['max_attendees']); ?> attendees)</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($next_open_house['special_instructions']): ?>
                <div class="special-instructions">
                    <i class="fas fa-info-circle"></i>
                    <?php echo esc_html($next_open_house['special_instructions']); ?>
                </div>
                <?php endif; ?>
                
                <div class="open-house-actions">
                    <?php if ($next_open_house['rsvp_required']): ?>
                    <button class="hph-btn hph-btn--primary hph-btn--sm" data-action="rsvp-open-house" data-open-house-id="<?php echo esc_attr($next_open_house['id']); ?>">
                        <i class="fas fa-check"></i>
                        RSVP Now
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($next_open_house['virtual_tour_link']): ?>
                    <a href="<?php echo esc_url($next_open_house['virtual_tour_link']); ?>" target="_blank" class="hph-btn hph-btn--secondary hph-btn--sm">
                        <i class="fas fa-play"></i>
                        Virtual Tour
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (count($open_houses) > 1): ?>
        <div class="all-open-houses">
            <h4>All Scheduled Open Houses</h4>
            <?php foreach ($open_houses as $open_house): ?>
            <div class="open-house-item <?php echo ($open_house['id'] === $next_open_house['id']) ? 'is-next' : ''; ?>">
                <div class="open-house-summary">
                    <span class="date"><?php echo esc_html($open_house['formatted_date']); ?></span>
                    <span class="time"><?php echo esc_html($open_house['formatted_start_time']); ?> - <?php echo esc_html($open_house['formatted_end_time']); ?></span>
                </div>
                <?php if ($open_house['rsvp_required']): ?>
                <span class="rsvp-badge">RSVP Required</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

<!-- Quick Actions -->
<div class="quick-actions">
    <h3 class="actions-title">Quick Actions</h3>
    <div class="actions-list">
        <button class="hph-btn hph-btn--secondary action-item" data-action="save-listing" data-listing-id="<?php echo esc_attr($listing_id); ?>">
            <i class="fas fa-heart action-icon"></i>
            Save Property
        </button>
        <button class="hph-btn hph-btn--secondary action-item" data-action="share-listing" data-listing-id="<?php echo esc_attr($listing_id); ?>">
            <i class="fas fa-share-alt action-icon"></i>
            Share Property
        </button>
        <button class="hph-btn hph-btn--secondary action-item" data-action="schedule-showing" data-listing-id="<?php echo esc_attr($listing_id); ?>">
            <i class="fas fa-calendar-check action-icon"></i>
            Schedule Showing
        </button>
        <button class="hph-btn hph-btn--secondary action-item" data-action="request-info" data-listing-id="<?php echo esc_attr($listing_id); ?>">
            <i class="fas fa-info-circle action-icon"></i>
            Request Information
        </button>
    </div>
</div>
