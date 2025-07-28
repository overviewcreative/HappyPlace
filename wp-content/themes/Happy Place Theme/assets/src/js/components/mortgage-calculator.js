// assets/js/components/mortgage-calculator.js

class MortgageCalculator {
  constructor(element) {
    this.element = element;
    this.config = this.getConfig();
    this.init();
  }

  init() {
    this.bindEvents();
    this.calculate(); // Initial calculation
  }

  getConfig() {
    const configElement = this.element.querySelector('[data-component-config="mortgage-calculator"]');
    if (configElement) {
      try {
        return JSON.parse(configElement.textContent);
      } catch (e) {
        console.warn('Failed to parse mortgage calculator config:', e);
      }
    }
    return {};
  }

  bindEvents() {
    // Range sliders
    const downPaymentRange = this.element.querySelector('#down-payment-range');
    const interestRateRange = this.element.querySelector('#interest-rate-range');
    
    if (downPaymentRange) {
      downPaymentRange.addEventListener('input', () => this.calculate());
    }
    
    if (interestRateRange) {
      interestRateRange.addEventListener('input', () => this.calculate());
    }

    // Loan term radio buttons
    const loanTermInputs = this.element.querySelectorAll('input[name="loan_term"]');
    loanTermInputs.forEach(input => {
      input.addEventListener('change', () => this.calculate());
    });

    // Action buttons
    const preApprovalBtn = this.element.querySelector('#get-pre-approved');
    const contactLenderBtn = this.element.querySelector('#contact-lender');
    
    if (preApprovalBtn) {
      preApprovalBtn.addEventListener('click', () => this.handlePreApproval());
    }
    
    if (contactLenderBtn) {
      contactLenderBtn.addEventListener('click', () => this.handleContactLender());
    }
  }

  calculate() {
    const formData = this.getFormData();
    const calculations = this.performCalculations(formData);
    this.updateDisplay(formData, calculations);
  }

  getFormData() {
    const homePrice = parseFloat(this.element.querySelector('#home-price')?.value || 0);
    const downPaymentPercent = parseFloat(this.element.querySelector('#down-payment-range')?.value || 20);
    const interestRate = parseFloat(this.element.querySelector('#interest-rate-range')?.value || 6.5);
    const loanTerm = parseInt(this.element.querySelector('input[name="loan_term"]:checked')?.value || 30);
    
    return {
      homePrice,
      downPaymentPercent,
      interestRate,
      loanTerm
    };
  }

  performCalculations(data) {
    const { homePrice, downPaymentPercent, interestRate, loanTerm } = data;
    
    // Calculate loan amount
    const downPaymentAmount = (homePrice * downPaymentPercent) / 100;
    const loanAmount = homePrice - downPaymentAmount;
    
    // Calculate monthly payment (P&I)
    const monthlyRate = (interestRate / 100) / 12;
    const numPayments = loanTerm * 12;
    
    let monthlyPI = 0;
    if (monthlyRate > 0 && loanAmount > 0) {
      monthlyPI = loanAmount * (monthlyRate * Math.pow(1 + monthlyRate, numPayments)) / (Math.pow(1 + monthlyRate, numPayments) - 1);
    } else if (loanAmount > 0) {
      monthlyPI = loanAmount / numPayments;
    }
    
    // Calculate other components
    const propertyTax = this.config.propertyTax || 0;
    const monthlyTax = propertyTax / 12;
    const monthlyInsurance = this.estimateInsurance(homePrice);
    
    // PMI calculation (if down payment < 20%)
    let monthlyPMI = 0;
    if (downPaymentPercent < 20) {
      monthlyPMI = (loanAmount * 0.005) / 12; // 0.5% annually
    }
    
    // HOA fees
    const hoaFees = this.config.hoaFees || 0;
    
    // Total monthly payment
    const totalMonthly = monthlyPI + monthlyTax + monthlyInsurance + monthlyPMI + hoaFees;
    
    return {
      downPaymentAmount,
      loanAmount,
      monthlyPI,
      monthlyTax,
      monthlyInsurance,
      monthlyPMI,
      hoaFees,
      totalMonthly
    };
  }

  estimateInsurance(homePrice) {
    // Rough estimate: 0.35% of home value annually
    return (homePrice * 0.0035) / 12;
  }

  updateDisplay(formData, calculations) {
    const { downPaymentPercent, interestRate } = formData;
    const {
      downPaymentAmount,
      monthlyPI,
      monthlyTax,
      monthlyInsurance,
      monthlyPMI,
      totalMonthly
    } = calculations;

    // Update range displays
    this.updateElement('#down-payment-display', `${downPaymentPercent}%`);
    this.updateElement('#interest-rate-display', `${interestRate}%`);
    this.updateElement('#down-payment-amount', `$${this.formatNumber(downPaymentAmount)}`);

    // Update payment breakdown
    this.updateElement('#principal-interest', `$${this.formatNumber(monthlyPI)}`);
    this.updateElement('#property-tax-monthly', `$${this.formatNumber(monthlyTax)}`);
    this.updateElement('#insurance-monthly', `$${this.formatNumber(monthlyInsurance)}`);
    this.updateElement('#total-payment', `$${this.formatNumber(totalMonthly)}`);

    // Handle PMI display
    const pmiRow = this.element.querySelector('.payment-item.pmi');
    const pmiElement = this.element.querySelector('#pmi-monthly');
    
    if (formData.downPaymentPercent < 20) {
      if (pmiRow) pmiRow.style.display = 'flex';
      if (pmiElement) pmiElement.textContent = `$${this.formatNumber(monthlyPMI)}`;
    } else {
      if (pmiRow) pmiRow.style.display = 'none';
    }

    // Track calculation
    this.trackEvent('mortgage_calculated', {
      home_price: formData.homePrice,
      down_payment_percent: formData.downPaymentPercent,
      interest_rate: formData.interestRate,
      loan_term: formData.loanTerm,
      monthly_payment: totalMonthly
    });
  }

  updateElement(selector, value) {
    const element = this.element.querySelector(selector);
    if (element) {
      element.textContent = value;
    }
  }

  formatNumber(num) {
    return Math.round(num).toLocaleString();
  }

  handlePreApproval() {
    this.trackEvent('mortgage_pre_approval_click');
    
    // Get current calculation data for pre-approval
    const formData = this.getFormData();
    const calculations = this.performCalculations(formData);
    
    // Create pre-approval form modal
    this.createPreApprovalModal(formData, calculations);
  }

  handleContactLender() {
    this.trackEvent('contact_lender_click');
    
    // Create contact lender modal
    this.createContactLenderModal();
  }

  createPreApprovalModal(formData, calculations) {
    const modal = document.createElement('div');
    modal.className = 'pre-approval-modal';
    modal.innerHTML = `
      <div class="modal-overlay"></div>
      <div class="modal-content">
        <div class="modal-header">
          <h3>Get Pre-Approved</h3>
          <button class="modal-close" type="button">&times;</button>
        </div>
        <div class="modal-body">
          <p>Based on your calculations:</p>
          <ul>
            <li>Estimated monthly payment: $${this.formatNumber(calculations.totalMonthly)}</li>
            <li>Loan amount: $${this.formatNumber(calculations.loanAmount)}</li>
            <li>Down payment: $${this.formatNumber(calculations.downPaymentAmount)}</li>
          </ul>
          <form class="pre-approval-form">
            <div class="form-group">
              <label>Full Name</label>
              <input type="text" name="name" required>
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="email" name="email" required>
            </div>
            <div class="form-group">
              <label>Phone</label>
              <input type="tel" name="phone" required>
            </div>
            <button type="submit" class="btn btn-primary">Submit Application</button>
          </form>
        </div>
      </div>
    `;

    this.showModal(modal);
  }

  createContactLenderModal() {
    const modal = document.createElement('div');
    modal.className = 'contact-lender-modal';
    modal.innerHTML = `
      <div class="modal-overlay"></div>
      <div class="modal-content">
        <div class="modal-header">
          <h3>Contact a Lender</h3>
          <button class="modal-close" type="button">&times;</button>
        </div>
        <div class="modal-body">
          <p>Connect with our preferred lenders for the best rates and service.</p>
          <form class="contact-form">
            <div class="form-group">
              <label>Name</label>
              <input type="text" name="name" required>
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="email" name="email" required>
            </div>
            <div class="form-group">
              <label>Phone</label>
              <input type="tel" name="phone" required>
            </div>
            <div class="form-group">
              <label>Message</label>
              <textarea name="message" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send Message</button>
          </form>
        </div>
      </div>
    `;

    this.showModal(modal);
  }

  showModal(modal) {
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';

    // Bind close events
    const closeBtn = modal.querySelector('.modal-close');
    const overlay = modal.querySelector('.modal-overlay');
    const form = modal.querySelector('form');
    
    const closeModal = () => {
      document.body.removeChild(modal);
      document.body.style.overflow = '';
    };

    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);
    
    if (form) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        this.submitPreApproval(new FormData(form), closeModal);
      });
    }
  }

  closeModal(modal) {
    document.body.removeChild(modal);
    document.body.style.overflow = '';
  }

  submitPreApproval(formData, closeCallback) {
    // Here you would submit to your backend
    console.log('Submitting pre-approval:', formData);
    
    this.showSuccessMessage('Your pre-approval request has been submitted!');
    closeCallback();
  }

  showSuccessMessage(message) {
    const toast = document.createElement('div');
    toast.className = 'toast toast-success';
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
      if (document.body.contains(toast)) {
        document.body.removeChild(toast);
      }
    }, 3000);
  }

  trackEvent(eventName, parameters = {}) {
    if (typeof gtag !== 'undefined') {
      gtag('event', eventName, {
        event_category: 'mortgage_calculator',
        event_label: window.location.pathname,
        listing_id: this.config.listingId,
        ...parameters
      });
    }

    // Also dispatch custom event for other tracking systems
    const customEvent = new CustomEvent('hph:mortgage-calculator', {
      detail: { eventName, parameters, config: this.config }
    });
    this.element.dispatchEvent(customEvent);
  }
}

// Export for manual initialization
export default MortgageCalculator;
