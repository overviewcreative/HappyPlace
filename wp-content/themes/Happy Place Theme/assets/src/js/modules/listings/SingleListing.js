/**
 * Single Listing Page JavaScript
 */

class SingleListing {
    constructor() {
        this.init();
    }

    init() {
        this.initGallery();
        this.initFavorites();
        this.initMortgageCalculator();
        this.initContactForm();
        this.initQuickActions();
    }

    initGallery() {
        const gallery = document.querySelector('.listing-gallery');
        if (!gallery) return;

        const dots = gallery.querySelectorAll('.gallery-dot');
        const mainImage = gallery.querySelector('.gallery-main');
        const counter = gallery.querySelector('.gallery-counter');

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                dots.forEach(d => d.classList.remove('active'));
                dot.classList.add('active');
                
                const imageUrl = dot.dataset.image;
                if (imageUrl && mainImage) {
                    mainImage.src = imageUrl;
                }
                
                if (counter) {
                    counter.querySelector('.current').textContent = index + 1;
                }
            });
        });
    }

    initFavorites() {
        const favoriteBtn = document.querySelector('.favorite-btn');
        if (!favoriteBtn) return;

        favoriteBtn.addEventListener('click', () => {
            const listingId = favoriteBtn.dataset.listingId;
            const isActive = favoriteBtn.classList.contains('active');
            
            favoriteBtn.classList.toggle('active');
            favoriteBtn.textContent = isActive ? '♡' : '♥';
            
            this.saveFavorite(listingId, !isActive);
        });
    }

    initMortgageCalculator() {
        const calcBtn = document.getElementById('calculate-payment');
        if (!calcBtn) return;

        calcBtn.addEventListener('click', () => {
            const homePrice = this.parsePrice(document.getElementById('home-price').value);
            const downPayment = this.parsePrice(document.getElementById('down-payment').value);
            const interestRate = parseFloat(document.getElementById('interest-rate').value);
            const loanTerm = parseInt(document.getElementById('loan-term').value);

            if (homePrice && downPayment && interestRate && loanTerm) {
                const monthlyPayment = this.calculateMonthlyPayment(
                    homePrice - downPayment,
                    interestRate / 100,
                    loanTerm
                );

                document.getElementById('payment-amount').textContent = 
                    ' + Math.round(monthlyPayment).toLocaleString();
                document.getElementById('payment-result').style.display = 'block';
            }
        });
    }

    initContactForm() {
        const form = document.getElementById('listing-contact-form');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const formData = new FormData(form);
            const messageDiv = document.getElementById('form-messages');
            
            messageDiv.innerHTML = '<div class="loading">Sending message...</div>';
            
            fetch(window.ajaxurl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.innerHTML = '<div class="success">Message sent successfully!</div>';
                    form.reset();
                } else {
                    messageDiv.innerHTML = '<div class="error">Error: ' + (data.message || 'Unknown error') + '</div>';
                }
            })
            .catch(error => {
                messageDiv.innerHTML = '<div class="error">Error sending message.</div>';
            });
        });
    }

    initQuickActions() {
        const saveBtn = document.querySelector('.save-listing');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                const listingId = saveBtn.dataset.listingId;
                this.saveListing(listingId);
            });
        }

        const shareBtn = document.querySelector('.share-listing');
        if (shareBtn) {
            shareBtn.addEventListener('click', () => {
                if (navigator.share) {
                    navigator.share({
                        title: document.title,
                        url: window.location.href
                    });
                } else {
                    navigator.clipboard.writeText(window.location.href);
                    alert('Link copied to clipboard!');
                }
            });
        }

        const tourBtn = document.querySelector('.schedule-tour');
        if (tourBtn) {
            tourBtn.addEventListener('click', () => {
                const listingId = tourBtn.dataset.listingId;
                this.scheduleTour(listingId);
            });
        }
    }

    // Helper methods
    parsePrice(priceString) {
        return parseInt(priceString.replace(/[^0-9]/g, ''));
    }

    calculateMonthlyPayment(principal, annualRate, years) {
        const monthlyRate = annualRate / 12;
        const numPayments = years * 12;
        
        return (principal * monthlyRate * Math.pow(1 + monthlyRate, numPayments)) / 
               (Math.pow(1 + monthlyRate, numPayments) - 1);
    }

    saveFavorite(listingId, isFavorite) {
        fetch(window.ajaxurl || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=save_favorite&listing_id=' + listingId + '&is_favorite=' + isFavorite
        });
    }

    saveListing(listingId) {
        fetch(window.ajaxurl || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=save_listing&listing_id=' + listingId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Listing saved successfully!');
            }
        });
    }

    scheduleTour(listingId) {
        const tourUrl = '/schedule-tour/?listing=' + listingId;
        window.open(tourUrl, '_blank');
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new SingleListing();
});

export default SingleListing;
