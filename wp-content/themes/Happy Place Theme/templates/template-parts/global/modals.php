<?php
/**
 * Global Modals Template
 * 
 * Contains modal structures for contact forms, property inquiries, etc.
 * Loaded globally to be available on any page.
 * 
 * @package HappyPlace
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Contact Agent Modal -->
<div class="hph-modal hph-modal--contact" id="contact-modal">
    <div class="hph-modal__overlay"></div>
    <div class="hph-modal__content">
        <div class="hph-modal__header">
            <h3 class="hph-modal__title">
                <i class="fas fa-envelope"></i>
                Contact <span class="hph-agent-name">Agent</span>
            </h3>
            <button class="hph-modal__close" aria-label="Close Contact Form">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="hph-modal__body">
            <form class="hph-form hph-quick-contact-form" id="agent-contact-form">
                <input type="hidden" name="agent_id" value="">
                
                <div class="hph-form__row">
                    <div class="hph-form__group">
                        <label for="contact_name" class="hph-form__label">Your Name *</label>
                        <input type="text" id="contact_name" name="contact_name" class="hph-form__input" required>
                    </div>
                    <div class="hph-form__group">
                        <label for="contact_email" class="hph-form__label">Your Email *</label>
                        <input type="email" id="contact_email" name="contact_email" class="hph-form__input" required>
                    </div>
                </div>
                
                <div class="hph-form__group">
                    <label for="contact_phone" class="hph-form__label">Your Phone</label>
                    <input type="tel" id="contact_phone" name="contact_phone" class="hph-form__input">
                </div>
                
                <div class="hph-form__row">
                    <div class="hph-form__group">
                        <label for="contact_subject" class="hph-form__label">Subject</label>
                        <select id="contact_subject" name="contact_subject" class="hph-form__select">
                            <option value="general">General Inquiry</option>
                            <option value="buying">Buying a Home</option>
                            <option value="selling">Selling a Home</option>
                            <option value="investing">Investment Properties</option>
                            <option value="market_analysis">Market Analysis</option>
                        </select>
                    </div>
                    <div class="hph-form__group">
                        <label for="preferred_contact" class="hph-form__label">Preferred Contact</label>
                        <select id="preferred_contact" name="preferred_contact" class="hph-form__select">
                            <option value="email">Email</option>
                            <option value="phone">Phone</option>
                            <option value="text">Text Message</option>
                        </select>
                    </div>
                </div>
                
                <div class="hph-form__group">
                    <label for="best_time" class="hph-form__label">Best Time to Contact</label>
                    <select id="best_time" name="best_time" class="hph-form__select">
                        <option value="anytime">Anytime</option>
                        <option value="morning">Morning (9am-12pm)</option>
                        <option value="afternoon">Afternoon (12pm-5pm)</option>
                        <option value="evening">Evening (5pm-8pm)</option>
                        <option value="weekend">Weekends</option>
                    </select>
                </div>
                
                <div class="hph-form__group">
                    <label for="contact_message" class="hph-form__label">Message *</label>
                    <textarea id="contact_message" name="contact_message" class="hph-form__textarea" rows="4" required placeholder="How can this agent help you?"></textarea>
                </div>
                
                <div class="hph-form__actions">
                    <button type="submit" class="action-btn action-btn--primary action-btn--block">
                        <i class="fas fa-paper-plane"></i>
                        Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Property Inquiry Modal -->
<div class="hph-modal hph-modal--inquiry" id="property-inquiry-modal">
    <div class="hph-modal__overlay"></div>
    <div class="hph-modal__content hph-modal__content--large">
        <div class="hph-modal__header">
            <h3 class="hph-modal__title">
                <i class="fas fa-search"></i>
                Property Search Inquiry
            </h3>
            <button class="hph-modal__close" aria-label="Close Property Inquiry">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="hph-modal__body">
            <form class="hph-form" id="agent-property-inquiry">
                <input type="hidden" name="agent_id" value="">
                
                <div class="hph-form__group">
                    <label for="inquiry_type" class="hph-form__label">I'm looking to:</label>
                    <select id="inquiry_type" name="inquiry_type" class="hph-form__select" required>
                        <option value="">Select an option</option>
                        <option value="buy">Buy a home</option>
                        <option value="sell">Sell my home</option>
                        <option value="invest">Invest in property</option>
                        <option value="rent">Rent a property</option>
                    </select>
                </div>
                
                <div class="hph-form__section">
                    <h4 class="hph-form__section-title">Budget & Property Details</h4>
                    <div class="hph-form__row">
                        <div class="hph-form__group">
                            <label for="price_range_min" class="hph-form__label">Min Price</label>
                            <input type="range" id="price_range_min" name="price_range_min" class="hph-form__range" min="100000" max="2000000" value="300000" step="25000">
                            <span class="hph-form__range-value">$300,000</span>
                        </div>
                        <div class="hph-form__group">
                            <label for="price_range_max" class="hph-form__label">Max Price</label>
                            <input type="range" id="price_range_max" name="price_range_max" class="hph-form__range" min="100000" max="2000000" value="600000" step="25000">
                            <span class="hph-form__range-value">$600,000</span>
                        </div>
                    </div>
                    
                    <div class="hph-form__row">
                        <div class="hph-form__group">
                            <label for="property_type" class="hph-form__label">Property Type</label>
                            <select id="property_type" name="property_type" class="hph-form__select">
                                <option value="">Any</option>
                                <option value="single-family">Single Family</option>
                                <option value="condo">Condo</option>
                                <option value="townhouse">Townhouse</option>
                                <option value="multi-family">Multi-Family</option>
                                <option value="land">Land</option>
                            </select>
                        </div>
                        <div class="hph-form__group">
                            <label for="bedrooms" class="hph-form__label">Bedrooms</label>
                            <select id="bedrooms" name="bedrooms" class="hph-form__select">
                                <option value="">Any</option>
                                <option value="1">1+</option>
                                <option value="2">2+</option>
                                <option value="3">3+</option>
                                <option value="4">4+</option>
                                <option value="5">5+</option>
                            </select>
                        </div>
                        <div class="hph-form__group">
                            <label for="bathrooms" class="hph-form__label">Bathrooms</label>
                            <select id="bathrooms" name="bathrooms" class="hph-form__select">
                                <option value="">Any</option>
                                <option value="1">1+</option>
                                <option value="2">2+</option>
                                <option value="3">3+</option>
                                <option value="4">4+</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="hph-form__group">
                        <label for="location" class="hph-form__label">Preferred Areas</label>
                        <input type="text" id="location" name="location" class="hph-form__input" placeholder="City, neighborhood, or ZIP code">
                    </div>
                    
                    <div class="hph-form__group">
                        <label for="timeline" class="hph-form__label">Timeline</label>
                        <select id="timeline" name="timeline" class="hph-form__select">
                            <option value="immediately">Ready to buy immediately</option>
                            <option value="1-3-months">1-3 months</option>
                            <option value="3-6-months">3-6 months</option>
                            <option value="6-12-months">6-12 months</option>
                            <option value="1-year-plus">More than a year</option>
                        </select>
                    </div>
                    
                    <div class="hph-form__group">
                        <label for="additional_requirements" class="hph-form__label">Additional Requirements</label>
                        <textarea id="additional_requirements" name="additional_requirements" class="hph-form__textarea" rows="3" placeholder="Any specific features, amenities, or requirements you're looking for?"></textarea>
                    </div>
                </div>
                
                <div class="hph-form__actions">
                    <button type="submit" class="action-btn action-btn--primary action-btn--block">
                        <i class="fas fa-search"></i>
                        Submit Inquiry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Mortgage Calculator Modal -->
<div class="hph-modal hph-modal--calculator" id="calculator-modal">
    <div class="hph-modal__overlay"></div>
    <div class="hph-modal__content">
        <div class="hph-modal__header">
            <h3 class="hph-modal__title">
                <i class="fas fa-calculator"></i>
                Mortgage Calculator
            </h3>
            <button class="hph-modal__close" aria-label="Close Calculator">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="hph-modal__body">
            <form class="hph-form" id="mortgage-calculator-form">
                <div class="hph-form__row">
                    <div class="hph-form__group">
                        <label for="home_price" class="hph-form__label">Home Price</label>
                        <input type="number" id="home_price" name="home_price" class="hph-form__input" value="500000" step="1000">
                    </div>
                    <div class="hph-form__group">
                        <label for="down_payment" class="hph-form__label">Down Payment</label>
                        <input type="number" id="down_payment" name="down_payment" class="hph-form__input" value="100000" step="1000">
                    </div>
                </div>
                
                <div class="hph-form__row">
                    <div class="hph-form__group">
                        <label for="interest_rate" class="hph-form__label">Interest Rate (%)</label>
                        <input type="number" id="interest_rate" name="interest_rate" class="hph-form__input" value="6.5" step="0.1">
                    </div>
                    <div class="hph-form__group">
                        <label for="loan_term" class="hph-form__label">Loan Term (years)</label>
                        <select id="loan_term" name="loan_term" class="hph-form__select">
                            <option value="15">15 years</option>
                            <option value="30" selected>30 years</option>
                        </select>
                    </div>
                </div>
                
                <div class="hph-calculator__results" id="calculator-results">
                    <div class="hph-calculator__result">
                        <span class="hph-calculator__label">Monthly Payment:</span>
                        <span class="hph-calculator__value" id="monthly-payment">$0</span>
                    </div>
                    <div class="hph-calculator__result">
                        <span class="hph-calculator__label">Total Interest:</span>
                        <span class="hph-calculator__value" id="total-interest">$0</span>
                    </div>
                    <div class="hph-calculator__result">
                        <span class="hph-calculator__label">Total Cost:</span>
                        <span class="hph-calculator__value" id="total-cost">$0</span>
                    </div>
                </div>
                
                <div class="hph-form__actions">
                    <button type="button" class="action-btn action-btn--primary action-btn--block" id="calculate-mortgage">
                        <i class="fas fa-calculator"></i>
                        Calculate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Schedule Showing Modal -->
<div class="hph-modal hph-modal--schedule" id="schedule-modal">
    <div class="hph-modal__overlay"></div>
    <div class="hph-modal__content">
        <div class="hph-modal__header">
            <h3 class="hph-modal__title">
                <i class="fas fa-calendar-alt"></i>
                Schedule a Showing
            </h3>
            <button class="hph-modal__close" aria-label="Close Schedule Form">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="hph-modal__body">
            <form class="hph-form" id="schedule-showing-form">
                <input type="hidden" name="property_id" value="">
                <input type="hidden" name="agent_id" value="">
                
                <div class="hph-form__row">
                    <div class="hph-form__group">
                        <label for="visitor_name" class="hph-form__label">Your Name *</label>
                        <input type="text" id="visitor_name" name="visitor_name" class="hph-form__input" required>
                    </div>
                    <div class="hph-form__group">
                        <label for="visitor_email" class="hph-form__label">Your Email *</label>
                        <input type="email" id="visitor_email" name="visitor_email" class="hph-form__input" required>
                    </div>
                </div>
                
                <div class="hph-form__group">
                    <label for="visitor_phone" class="hph-form__label">Your Phone</label>
                    <input type="tel" id="visitor_phone" name="visitor_phone" class="hph-form__input">
                </div>
                
                <div class="hph-form__row">
                    <div class="hph-form__group">
                        <label for="preferred_date" class="hph-form__label">Preferred Date *</label>
                        <input type="date" id="preferred_date" name="preferred_date" class="hph-form__input" required>
                    </div>
                    <div class="hph-form__group">
                        <label for="preferred_time" class="hph-form__label">Preferred Time *</label>
                        <select id="preferred_time" name="preferred_time" class="hph-form__select" required>
                            <option value="">Select time</option>
                            <option value="09:00">9:00 AM</option>
                            <option value="10:00">10:00 AM</option>
                            <option value="11:00">11:00 AM</option>
                            <option value="12:00">12:00 PM</option>
                            <option value="13:00">1:00 PM</option>
                            <option value="14:00">2:00 PM</option>
                            <option value="15:00">3:00 PM</option>
                            <option value="16:00">4:00 PM</option>
                            <option value="17:00">5:00 PM</option>
                        </select>
                    </div>
                </div>
                
                <div class="hph-form__group">
                    <label for="contact_method" class="hph-form__label">Preferred Contact Method</label>
                    <select id="contact_method" name="contact_method" class="hph-form__select">
                        <option value="phone">Phone</option>
                        <option value="email">Email</option>
                        <option value="text">Text Message</option>
                    </select>
                </div>
                
                <div class="hph-form__group">
                    <label for="showing_notes" class="hph-form__label">Additional Notes</label>
                    <textarea id="showing_notes" name="showing_notes" class="hph-form__textarea" rows="3" placeholder="Any special requests or questions?"></textarea>
                </div>
                
                <div class="hph-form__actions">
                    <button type="submit" class="action-btn action-btn--primary action-btn--block">
                        <i class="fas fa-calendar-check"></i>
                        Schedule Showing
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
