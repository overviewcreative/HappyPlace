class RealEstateFlyerGenerator {
    constructor() {
        this.canvas = null;
        this.currentListingData = null;
        this.templates = {
            parker_group: this.parkerGroupTemplate.bind(this),
            luxury: this.luxuryTemplate.bind(this),
            modern: this.modernTemplate.bind(this)
        };
        
        this.init();
    }

    init() {
        // Initialize Fabric.js canvas
        this.canvas = new fabric.Canvas('flyer-canvas', {
            backgroundColor: '#ffffff'
        });

        // Event listeners
        document.getElementById('generate-flyer').addEventListener('click', () => {
            this.generateFlyer();
        });

        document.getElementById('download-flyer').addEventListener('click', () => {
            this.downloadFlyer('png');
        });

        document.getElementById('download-pdf').addEventListener('click', () => {
            this.downloadFlyer('pdf');
        });
    }

    async generateFlyer() {
        const listingId = document.getElementById('listing-select').value;
        const template = document.getElementById('template-select').value;

        if (!listingId) {
            alert('Please select a listing');
            return;
        }

        // Show loading
        document.querySelector('.flyer-loading').style.display = 'block';

        try {
            // Get listing data via AJAX
            const response = await fetch(flyerAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hph_generate_flyer',
                    listing_id: listingId,
                    nonce: flyerAjax.nonce
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.currentListingData = data.data;
                await this.renderTemplate(template);
                
                // Show download buttons
                document.getElementById('download-flyer').style.display = 'inline-block';
                document.getElementById('download-pdf').style.display = 'inline-block';
            } else {
                throw new Error('Failed to load listing data');
            }
        } catch (error) {
            console.error('Error generating flyer:', error);
            alert('Error generating flyer. Please try again.');
        } finally {
            // Hide loading
            document.querySelector('.flyer-loading').style.display = 'none';
        }
    }

    async renderTemplate(templateName) {
        // Clear canvas
        this.canvas.clear();
        this.canvas.setBackgroundColor('#ffffff', this.canvas.renderAll.bind(this.canvas));

        // Render selected template
        if (this.templates[templateName]) {
            await this.templates[templateName]();
        }

        this.canvas.renderAll();
    }

    async parkerGroupTemplate() {
        const data = this.currentListingData;
        
        // Background gradient (matching the blue from your PDF)
        const gradient = new fabric.Gradient({
            type: 'linear',
            coords: { x1: 0, y1: 0, x2: 0, y2: this.canvas.height },
            colorStops: [
                { offset: 0, color: '#4ECDC4' },
                { offset: 1, color: '#44A08D' }
            ]
        });
        
        this.canvas.setBackgroundColor(gradient, this.canvas.renderAll.bind(this.canvas));

        // FOR SALE header
        const forSaleText = new fabric.Text('FOR SALE', {
            left: 60,
            top: 60,
            fontSize: 72,
            fontWeight: 'bold',
            fontFamily: 'Arial Black',
            fill: '#ffffff',
            textAlign: 'left'
        });
        this.canvas.add(forSaleText);

        // Main property image
        if (data.listing.main_photo) {
            await this.addImageToCanvas(data.listing.main_photo, {
                left: 60,
                top: 150,
                width: 730,
                height: 350,
                cornerRadius: 10
            });
        }

        // Property details strip
        const detailsRect = new fabric.Rect({
            left: 60,
            top: 520,
            width: 730,
            height: 80,
            fill: 'rgba(0,0,0,0.8)',
            rx: 5,
            ry: 5
        });
        this.canvas.add(detailsRect);

        // Property stats
        const bedrooms = data.listing.bedrooms || 0;
        const bathrooms = data.listing.bathrooms || 0;
        const sqft = data.listing.square_footage || 0;
        const acres = data.listing.lot_size || 0;
        const price = data.listing.price || 0;

        const statsText = `${bedrooms} Bed    ${bathrooms} Bath    ${this.formatNumber(sqft)} Ft²    ${acres} Acres`;
        const stats = new fabric.Text(statsText, {
            left: 80,
            top: 535,
            fontSize: 18,
            fontFamily: 'Arial',
            fill: '#ffffff',
            fontWeight: 'bold'
        });
        this.canvas.add(stats);

        // Price
        const priceText = new fabric.Text(`$${this.formatNumber(price)}`, {
            left: 650,
            top: 525,
            fontSize: 48,
            fontFamily: 'Arial Black',
            fill: '#ffffff',
            textAlign: 'right'
        });
        this.canvas.add(priceText);

        // Property gallery (3 smaller images)
        if (data.listing.photo_gallery && data.listing.photo_gallery.length > 0) {
            const galleryY = 620;
            const imageWidth = 236;
            const imageHeight = 150;
            const spacing = 10;

            for (let i = 0; i < Math.min(3, data.listing.photo_gallery.length); i++) {
                const x = 60 + (i * (imageWidth + spacing));
                await this.addImageToCanvas(data.listing.photo_gallery[i].url, {
                    left: x,
                    top: galleryY,
                    width: imageWidth,
                    height: imageHeight,
                    cornerRadius: 5
                });
            }
        }

        // Property address and description
        const address = `${data.listing.street_address}\n${data.listing.city}, ${data.listing.region} ${data.listing.zip_code}`;
        const addressText = new fabric.Text(address, {
            left: 60,
            top: 800,
            fontSize: 28,
            fontFamily: 'Arial',
            fill: '#2C3E50',
            fontWeight: 'bold',
            lineHeight: 1.2
        });
        this.canvas.add(addressText);

        // Description
        if (data.listing.short_description) {
            const description = new fabric.Text(data.listing.short_description, {
                left: 60,
                top: 870,
                fontSize: 16,
                fontFamily: 'Arial',
                fill: '#34495E',
                width: 730,
                textAlign: 'left'
            });
            this.canvas.add(description);
        }

        // Agent section
        if (data.agent.name) {
            // Agent photo (circular)
            if (data.agent.profile_photo) {
                await this.addCircularImage(data.agent.profile_photo.url, {
                    left: 60,
                    top: 950,
                    radius: 40
                });
            }

            // Agent details
            const agentName = new fabric.Text(data.agent.name, {
                left: 150,
                top: 950,
                fontSize: 20,
                fontFamily: 'Arial',
                fill: '#2C3E50',
                fontWeight: 'bold'
            });
            this.canvas.add(agentName);

            const agentTitle = new fabric.Text('REALTOR®', {
                left: 150,
                top: 975,
                fontSize: 14,
                fontFamily: 'Arial',
                fill: '#7F8C8D'
            });
            this.canvas.add(agentTitle);

            // Contact info
            const contactInfo = `${data.agent.email}\n${data.agent.phone}`;
            const contact = new fabric.Text(contactInfo, {
                left: 150,
                top: 995,
                fontSize: 14,
                fontFamily: 'Arial',
                fill: '#2C3E50',
                lineHeight: 1.4
            });
            this.canvas.add(contact);

            // Office info
            if (data.agent.office_address) {
                const office = new fabric.Text(data.agent.office_address, {
                    left: 400,
                    top: 995,
                    fontSize: 14,
                    fontFamily: 'Arial',
                    fill: '#2C3E50'
                });
                this.canvas.add(office);
            }
        }

        // Logo area (you can add your company logo here)
        const logoText = new fabric.Text('the parker group', {
            left: 600,
            top: 950,
            fontSize: 24,
            fontFamily: 'Arial',
            fill: '#2C3E50',
            fontWeight: 'bold'
        });
        this.canvas.add(logoText);

        const tagline = new fabric.Text('find your happy place', {
            left: 600,
            top: 980,
            fontSize: 14,
            fontFamily: 'Arial',
            fill: '#7F8C8D'
        });
        this.canvas.add(tagline);

        // QR Code placeholder (you can integrate a QR code generator)
        const qrPlaceholder = new fabric.Rect({
            left: 750,
            top: 950,
            width: 60,
            height: 60,
            fill: '#000000',
            stroke: '#cccccc',
            strokeWidth: 1
        });
        this.canvas.add(qrPlaceholder);

        const qrText = new fabric.Text('QR', {
            left: 770,
            top: 970,
            fontSize: 16,
            fontFamily: 'Arial',
            fill: '#ffffff',
            textAlign: 'center'
        });
        this.canvas.add(qrText);
    }

    async luxuryTemplate() {
        // Implement luxury template with different colors and layout
        const data = this.currentListingData;
        
        // Dark background for luxury feel
        this.canvas.setBackgroundColor('#1a1a1a', this.canvas.renderAll.bind(this.canvas));
        
        // Gold accent color
        const goldColor = '#D4AF37';
        
        // Luxury styling with serif fonts and elegant layout
        // ... implement luxury template logic
    }

    async modernTemplate() {
        // Implement modern template with clean lines and minimal design
        const data = this.currentListingData;
        
        // Clean white background
        this.canvas.setBackgroundColor('#ffffff', this.canvas.renderAll.bind(this.canvas));
        
        // Modern color palette
        // ... implement modern template logic
    }

    async addImageToCanvas(imageUrl, options) {
        return new Promise((resolve) => {
            fabric.Image.fromURL(imageUrl, (img) => {
                img.set({
                    left: options.left,
                    top: options.top,
                    scaleX: options.width / img.width,
                    scaleY: options.height / img.height,
                    selectable: false
                });

                if (options.cornerRadius) {
                    img.set({
                        clipPath: new fabric.Rect({
                            width: options.width,
                            height: options.height,
                            rx: options.cornerRadius,
                            ry: options.cornerRadius,
                            originX: 'left',
                            originY: 'top'
                        })
                    });
                }

                this.canvas.add(img);
                resolve();
            }, { crossOrigin: 'anonymous' });
        });
    }

    async addCircularImage(imageUrl, options) {
        return new Promise((resolve) => {
            fabric.Image.fromURL(imageUrl, (img) => {
                const radius = options.radius;
                
                img.set({
                    left: options.left,
                    top: options.top,
                    scaleX: (radius * 2) / img.width,
                    scaleY: (radius * 2) / img.height,
                    selectable: false,
                    clipPath: new fabric.Circle({
                        radius: radius,
                        originX: 'left',
                        originY: 'top'
                    })
                });

                this.canvas.add(img);
                resolve();
            }, { crossOrigin: 'anonymous' });
        });
    }

    formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }

    downloadFlyer(format) {
        if (format === 'png') {
            const dataURL = this.canvas.toDataURL({
                format: 'png',
                quality: 1.0,
                multiplier: 2 // Higher resolution
            });

            const link = document.createElement('a');
            link.download = `listing-flyer-${Date.now()}.png`;
            link.href = dataURL;
            link.click();
        } else if (format === 'pdf') {
            // For PDF generation, you'd need to include jsPDF
            this.downloadAsPDF();
        }
    }

    downloadAsPDF() {
        // Include jsPDF library first
        if (typeof jsPDF !== 'undefined') {
            const imgData = this.canvas.toDataURL('image/jpeg', 1.0);
            const pdf = new jsPDF({
                orientation: 'portrait',
                unit: 'px',
                format: [this.canvas.width, this.canvas.height]
            });
            
            pdf.addImage(imgData, 'JPEG', 0, 0, this.canvas.width, this.canvas.height);
            pdf.save(`listing-flyer-${Date.now()}.pdf`);
        } else {
            alert('PDF generation not available. Please download as PNG.');
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('flyer-canvas')) {
        new RealEstateFlyerGenerator();
    }
});