/**
 * Flyer Generator Component
 * 
 * Advanced flyer generation system with template selection, customization,
 * real-time preview, and batch processing capabilities.
 * 
 * @since 3.0.0
 */

class FlyerGenerator extends DashboardComponent {
    constructor(element, options = {}) {
        super(element, {
            stateSubscriptions: ['listings'],
            ...options
        });
        
        this.canvas = null;
        this.fabricCanvas = null;
        this.currentTemplate = null;
        this.currentListing = null;
        this.templates = [];
        this.fonts = [];
        this.isGenerating = false;
        
        this.defaultDimensions = {
            width: 816,  // 8.5" at 96 DPI
            height: 1056 // 11" at 96 DPI
        };
        
        this.ajax = new DashboardAjax();
    }
    
    /**
     * Initialize component
     */
    onInit() {
        this.setupDOM();
        this.initializeFabric();
        this.loadTemplates();
        this.loadFonts();
    }
    
    /**
     * Setup DOM structure
     */
    setupDOM() {
        this.element.innerHTML = `
            <div class="flyer-generator-layout">
                <!-- Sidebar Controls -->
                <div class="flyer-sidebar">
                    <div class="sidebar-section">
                        <h3>Select Listing</h3>
                        <select class="listing-select" id="flyer-listing-select">
                            <option value="">Choose a listing...</option>
                        </select>
                    </div>
                    
                    <div class="sidebar-section">
                        <h3>Templates</h3>
                        <div class="template-grid" id="template-grid">
                            <div class="template-loading">Loading templates...</div>
                        </div>
                    </div>
                    
                    <div class="sidebar-section">
                        <h3>Customization</h3>
                        <div class="customization-panel">
                            <!-- Text Styling -->
                            <div class="control-group">
                                <label>Font Family</label>
                                <select class="font-select" id="font-family">
                                    <option value="Arial">Arial</option>
                                    <option value="Helvetica">Helvetica</option>
                                    <option value="Times New Roman">Times New Roman</option>
                                    <option value="Georgia">Georgia</option>
                                </select>
                            </div>
                            
                            <div class="control-group">
                                <label>Font Size</label>
                                <input type="range" class="font-size-slider" id="font-size" 
                                       min="8" max="72" value="16">
                                <span class="font-size-value">16px</span>
                            </div>
                            
                            <div class="control-group">
                                <label>Text Color</label>
                                <input type="color" class="color-picker" id="text-color" value="#000000">
                            </div>
                            
                            <!-- Background -->
                            <div class="control-group">
                                <label>Background Color</label>
                                <input type="color" class="color-picker" id="bg-color" value="#ffffff">
                            </div>
                            
                            <!-- Logo Upload -->
                            <div class="control-group">
                                <label>Agent Logo</label>
                                <input type="file" class="logo-upload" id="logo-upload" 
                                       accept="image/*">
                                <button type="button" class="remove-logo hidden">Remove Logo</button>
                            </div>
                            
                            <!-- Branding -->
                            <div class="control-group">
                                <label>Agent Name</label>
                                <input type="text" class="agent-name" id="agent-name" 
                                       placeholder="Your Name">
                            </div>
                            
                            <div class="control-group">
                                <label>Agent Phone</label>
                                <input type="tel" class="agent-phone" id="agent-phone" 
                                       placeholder="(555) 123-4567">
                            </div>
                            
                            <div class="control-group">
                                <label>Agent Email</label>
                                <input type="email" class="agent-email" id="agent-email" 
                                       placeholder="agent@example.com">
                            </div>
                            
                            <!-- Layout Options -->
                            <div class="control-group">
                                <label>Layout Style</label>
                                <select class="layout-select" id="layout-style">
                                    <option value="classic">Classic</option>
                                    <option value="modern">Modern</option>
                                    <option value="minimal">Minimal</option>
                                    <option value="luxury">Luxury</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sidebar-section">
                        <h3>Actions</h3>
                        <div class="action-buttons">
                            <button type="button" class="btn btn-primary generate-flyer" disabled>
                                <span class="dashicons dashicons-pdf"></span>
                                Generate Flyer
                            </button>
                            <button type="button" class="btn btn-secondary save-template">
                                <span class="dashicons dashicons-saved"></span>
                                Save Template
                            </button>
                            <button type="button" class="btn btn-secondary reset-flyer">
                                <span class="dashicons dashicons-undo"></span>
                                Reset
                            </button>
                        </div>
                        
                        <!-- Batch Generation -->
                        <div class="batch-generation">
                            <h4>Batch Generate</h4>
                            <button type="button" class="btn btn-outline batch-generate" 
                                    title="Generate flyers for all selected listings">
                                <span class="dashicons dashicons-admin-page"></span>
                                Generate All
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Main Canvas Area -->
                <div class="flyer-canvas-area">
                    <div class="canvas-toolbar">
                        <div class="toolbar-group">
                            <button type="button" class="tool-btn" data-tool="select" title="Select">
                                <span class="dashicons dashicons-move"></span>
                            </button>
                            <button type="button" class="tool-btn" data-tool="text" title="Add Text">
                                <span class="dashicons dashicons-editor-textcolor"></span>
                            </button>
                            <button type="button" class="tool-btn" data-tool="image" title="Add Image">
                                <span class="dashicons dashicons-format-image"></span>
                            </button>
                        </div>
                        
                        <div class="toolbar-group">
                            <button type="button" class="tool-btn" data-action="undo" title="Undo">
                                <span class="dashicons dashicons-undo"></span>
                            </button>
                            <button type="button" class="tool-btn" data-action="redo" title="Redo">
                                <span class="dashicons dashicons-redo"></span>
                            </button>
                        </div>
                        
                        <div class="toolbar-group">
                            <button type="button" class="tool-btn" data-action="zoom-in" title="Zoom In">
                                <span class="dashicons dashicons-plus-alt"></span>
                            </button>
                            <button type="button" class="tool-btn" data-action="zoom-out" title="Zoom Out">
                                <span class="dashicons dashicons-minus"></span>
                            </button>
                            <button type="button" class="tool-btn" data-action="zoom-fit" title="Fit to Screen">
                                <span class="dashicons dashicons-editor-expand"></span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="canvas-container">
                        <canvas id="flyer-canvas"></canvas>
                    </div>
                    
                    <!-- Loading Overlay -->
                    <div class="canvas-loading hidden">
                        <div class="loading-spinner"></div>
                        <p>Generating flyer...</p>
                    </div>
                </div>
            </div>
            
            <!-- Template Modal -->
            <div class="template-modal hidden">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Save Template</h3>
                        <button type="button" class="modal-close">×</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="template-name">Template Name</label>
                            <input type="text" id="template-name" placeholder="Enter template name">
                        </div>
                        <div class="form-group">
                            <label for="template-description">Description</label>
                            <textarea id="template-description" rows="3" 
                                      placeholder="Brief description of this template"></textarea>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="template-public"> 
                                Make this template public
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary modal-cancel">Cancel</button>
                        <button type="button" class="btn btn-primary template-save-confirm">Save Template</button>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Initialize Fabric.js canvas
     */
    initializeFabric() {
        this.canvas = this.$('#flyer-canvas');
        if (!this.canvas) return;
        
        // Initialize Fabric canvas
        this.fabricCanvas = new fabric.Canvas('flyer-canvas', {
            width: this.defaultDimensions.width,
            height: this.defaultDimensions.height,
            backgroundColor: '#ffffff'
        });
        
        // Setup canvas events
        this.setupCanvasEvents();
        
        // Initial zoom to fit
        this.zoomToFit();
    }
    
    /**
     * Setup canvas event handlers
     */
    setupCanvasEvents() {
        // Object selection
        this.fabricCanvas.on('selection:created', (e) => {
            this.updatePropertyPanel(e.selected[0]);
        });
        
        this.fabricCanvas.on('selection:updated', (e) => {
            this.updatePropertyPanel(e.selected[0]);
        });
        
        this.fabricCanvas.on('selection:cleared', () => {
            this.clearPropertyPanel();
        });
        
        // Object modification
        this.fabricCanvas.on('object:modified', () => {
            this.saveState();
        });
        
        // Mouse events for tools
        this.fabricCanvas.on('mouse:down', (e) => {
            this.handleCanvasMouseDown(e);
        });
    }
    
    /**
     * Bind component events
     */
    bindEvents() {
        // Listing selection
        const listingSelect = this.$('#flyer-listing-select');
        if (listingSelect) {
            this.addEventListener(listingSelect, 'change', this.handleListingChange);
        }
        
        // Template selection
        this.addEventListener(this.element, 'click', this.handleTemplateClick);
        
        // Control changes
        this.$$('.customization-panel input, .customization-panel select').forEach(control => {
            this.addEventListener(control, 'change', this.handleControlChange);
            this.addEventListener(control, 'input', this.handleControlChange);
        });
        
        // Action buttons
        const generateBtn = this.$('.generate-flyer');
        if (generateBtn) {
            this.addEventListener(generateBtn, 'click', this.generateFlyer);
        }
        
        const saveTemplateBtn = this.$('.save-template');
        if (saveTemplateBtn) {
            this.addEventListener(saveTemplateBtn, 'click', this.showSaveTemplateModal);
        }
        
        const resetBtn = this.$('.reset-flyer');
        if (resetBtn) {
            this.addEventListener(resetBtn, 'click', this.resetFlyer);
        }
        
        const batchBtn = this.$('.batch-generate');
        if (batchBtn) {
            this.addEventListener(batchBtn, 'click', this.batchGenerate);
        }
        
        // Toolbar buttons
        this.$$('.tool-btn').forEach(btn => {
            this.addEventListener(btn, 'click', this.handleToolClick);
        });
        
        // Logo upload
        const logoUpload = this.$('#logo-upload');
        if (logoUpload) {
            this.addEventListener(logoUpload, 'change', this.handleLogoUpload);
        }
        
        // Modal events
        this.addEventListener(this.element, 'click', this.handleModalClick);
    }
    
    /**
     * Load available templates
     */
    async loadTemplates() {
        try {
            const result = await this.ajax.request('get_flyer_templates');
            
            if (result.success) {
                this.templates = result.data.templates;
                this.renderTemplateGrid();
            }
        } catch (error) {
            console.error('Failed to load templates:', error);
            this.showError('Failed to load templates');
        }
    }
    
    /**
     * Load available fonts
     */
    async loadFonts() {
        try {
            const result = await this.ajax.request('get_available_fonts');
            
            if (result.success) {
                this.fonts = result.data.fonts;
                this.updateFontSelect();
            }
        } catch (error) {
            console.error('Failed to load fonts:', error);
        }
    }
    
    /**
     * Render template grid
     */
    renderTemplateGrid() {
        const grid = this.$('#template-grid');
        if (!grid) return;
        
        if (this.templates.length === 0) {
            grid.innerHTML = '<p class="no-templates">No templates available</p>';
            return;
        }
        
        grid.innerHTML = this.templates.map(template => `
            <div class="template-item" data-template-id="${template.id}">
                <div class="template-preview">
                    <img src="${template.thumbnail}" alt="${template.name}" loading="lazy">
                </div>
                <div class="template-info">
                    <h4>${template.name}</h4>
                    <p>${template.description || ''}</p>
                </div>
                <div class="template-actions">
                    <button type="button" class="btn btn-small use-template">Use Template</button>
                </div>
            </div>
        `).join('');
    }
    
    /**
     * Update font select options
     */
    updateFontSelect() {
        const fontSelect = this.$('#font-family');
        if (!fontSelect) return;
        
        // Clear existing options
        fontSelect.innerHTML = '';
        
        // Add fonts
        this.fonts.forEach(font => {
            const option = document.createElement('option');
            option.value = font.family;
            option.textContent = font.name;
            fontSelect.appendChild(option);
        });
    }
    
    /**
     * Handle listing selection change
     */
    handleListingChange(event) {
        const listingId = event.target.value;
        
        if (listingId) {
            const listing = this.state.getListing(parseInt(listingId));
            if (listing) {
                this.setCurrentListing(listing);
            }
        } else {
            this.currentListing = null;
            this.updateGenerateButton();
        }
    }
    
    /**
     * Set current listing
     */
    setCurrentListing(listing) {
        this.currentListing = listing;
        this.updateListingData();
        this.updateGenerateButton();
    }
    
    /**
     * Update listing data in template
     */
    updateListingData() {
        if (!this.currentListing || !this.fabricCanvas) return;
        
        // Update text objects with listing data
        const objects = this.fabricCanvas.getObjects();
        
        objects.forEach(obj => {
            if (obj.type === 'text' || obj.type === 'textbox') {
                const placeholder = obj.text;
                const newText = this.replacePlaceholders(placeholder, this.currentListing);
                
                if (newText !== placeholder) {
                    obj.set('text', newText);
                }
            }
        });
        
        this.fabricCanvas.renderAll();
    }
    
    /**
     * Replace placeholders with listing data
     */
    replacePlaceholders(text, listing) {
        const placeholders = {
            '{{address}}': listing.address || '',
            '{{price}}': listing.price ? `$${parseInt(listing.price).toLocaleString()}` : '',
            '{{bedrooms}}': listing.bedrooms || '',
            '{{bathrooms}}': listing.bathrooms || '',
            '{{sqft}}': listing.sqft ? `${parseInt(listing.sqft).toLocaleString()} sq ft` : '',
            '{{description}}': listing.description || '',
            '{{city}}': listing.city || '',
            '{{state}}': listing.state || '',
            '{{zip}}': listing.zip || '',
            '{{property_type}}': listing.property_type || '',
            '{{mls_number}}': listing.mls_number || '',
            '{{agent_name}}': this.$('#agent-name')?.value || '',
            '{{agent_phone}}': this.$('#agent-phone')?.value || '',
            '{{agent_email}}': this.$('#agent-email')?.value || ''
        };
        
        let result = text;
        Object.entries(placeholders).forEach(([placeholder, value]) => {
            result = result.replace(new RegExp(placeholder, 'g'), value);
        });
        
        return result;
    }
    
    /**
     * Handle template click
     */
    handleTemplateClick(event) {
        const templateItem = event.target.closest('.template-item');
        if (templateItem) {
            const templateId = templateItem.dataset.templateId;
            this.loadTemplate(templateId);
        }
    }
    
    /**
     * Load template onto canvas
     */
    async loadTemplate(templateId) {
        try {
            this.setLoading(true);
            
            const result = await this.ajax.request('get_flyer_template', { id: templateId });
            
            if (result.success) {
                this.currentTemplate = result.data.template;
                await this.applyTemplate(this.currentTemplate);
            }
        } catch (error) {
            console.error('Failed to load template:', error);
            this.showError('Failed to load template');
        } finally {
            this.setLoading(false);
        }
    }
    
    /**
     * Apply template to canvas
     */
    async applyTemplate(template) {
        if (!this.fabricCanvas) return;
        
        // Clear canvas
        this.fabricCanvas.clear();
        
        // Set canvas properties
        if (template.width && template.height) {
            this.fabricCanvas.setDimensions({
                width: template.width,
                height: template.height
            });
        }
        
        if (template.backgroundColor) {
            this.fabricCanvas.backgroundColor = template.backgroundColor;
        }
        
        // Load template objects
        if (template.objects) {
            await this.loadCanvasFromJSON(template.objects);
        }
        
        // Update listing data if available
        if (this.currentListing) {
            this.updateListingData();
        }
        
        this.fabricCanvas.renderAll();
        this.zoomToFit();
    }
    
    /**
     * Load canvas from JSON data
     */
    async loadCanvasFromJSON(jsonData) {
        return new Promise((resolve) => {
            this.fabricCanvas.loadFromJSON(jsonData, () => {
                resolve();
            });
        });
    }
    
    /**
     * Handle control changes
     */
    handleControlChange(event) {
        const control = event.target;
        const property = control.id;
        
        switch (property) {
            case 'font-family':
                this.updateSelectedTextFont(control.value);
                break;
            case 'font-size':
                this.updateSelectedTextSize(parseInt(control.value));
                this.updateFontSizeDisplay(control.value);
                break;
            case 'text-color':
                this.updateSelectedTextColor(control.value);
                break;
            case 'bg-color':
                this.updateCanvasBackground(control.value);
                break;
            case 'agent-name':
            case 'agent-phone':
            case 'agent-email':
                this.updateListingData();
                break;
        }
    }
    
    /**
     * Update selected text font
     */
    updateSelectedTextFont(fontFamily) {
        const activeObject = this.fabricCanvas.getActiveObject();
        
        if (activeObject && (activeObject.type === 'text' || activeObject.type === 'textbox')) {
            activeObject.set('fontFamily', fontFamily);
            this.fabricCanvas.renderAll();
        }
    }
    
    /**
     * Update selected text size
     */
    updateSelectedTextSize(fontSize) {
        const activeObject = this.fabricCanvas.getActiveObject();
        
        if (activeObject && (activeObject.type === 'text' || activeObject.type === 'textbox')) {
            activeObject.set('fontSize', fontSize);
            this.fabricCanvas.renderAll();
        }
    }
    
    /**
     * Update selected text color
     */
    updateSelectedTextColor(color) {
        const activeObject = this.fabricCanvas.getActiveObject();
        
        if (activeObject && (activeObject.type === 'text' || activeObject.type === 'textbox')) {
            activeObject.set('fill', color);
            this.fabricCanvas.renderAll();
        }
    }
    
    /**
     * Update canvas background
     */
    updateCanvasBackground(color) {
        this.fabricCanvas.backgroundColor = color;
        this.fabricCanvas.renderAll();
    }
    
    /**
     * Update font size display
     */
    updateFontSizeDisplay(value) {
        const display = this.$('.font-size-value');
        if (display) {
            display.textContent = `${value}px`;
        }
    }
    
    /**
     * Handle logo upload
     */
    handleLogoUpload(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = (e) => {
            this.addLogoToCanvas(e.target.result);
        };
        reader.readAsDataURL(file);
    }
    
    /**
     * Add logo to canvas
     */
    addLogoToCanvas(imageSrc) {
        fabric.Image.fromURL(imageSrc, (img) => {
            // Scale logo to reasonable size
            const maxWidth = 200;
            const maxHeight = 100;
            
            let scale = 1;
            if (img.width > maxWidth) {
                scale = maxWidth / img.width;
            }
            if (img.height * scale > maxHeight) {
                scale = maxHeight / img.height;
            }
            
            img.scale(scale);
            img.set({
                left: 50,
                top: 50,
                selectable: true
            });
            
            this.fabricCanvas.add(img);
            this.fabricCanvas.setActiveObject(img);
            this.fabricCanvas.renderAll();
        });
    }
    
    /**
     * Handle tool clicks
     */
    handleToolClick(event) {
        const btn = event.target.closest('.tool-btn');
        if (!btn) return;
        
        const tool = btn.dataset.tool;
        const action = btn.dataset.action;
        
        if (tool) {
            this.setActiveTool(tool);
        } else if (action) {
            this.executeAction(action);
        }
    }
    
    /**
     * Set active tool
     */
    setActiveTool(tool) {
        // Update button states
        this.$$('.tool-btn[data-tool]').forEach(btn => {
            btn.classList.remove('active');
        });
        
        this.$(`[data-tool="${tool}"]`)?.classList.add('active');
        
        // Set canvas mode
        switch (tool) {
            case 'select':
                this.fabricCanvas.selection = true;
                this.fabricCanvas.defaultCursor = 'default';
                break;
            case 'text':
                this.fabricCanvas.selection = false;
                this.fabricCanvas.defaultCursor = 'text';
                break;
            case 'image':
                this.fabricCanvas.selection = false;
                this.fabricCanvas.defaultCursor = 'crosshair';
                break;
        }
    }
    
    /**
     * Execute canvas action
     */
    executeAction(action) {
        switch (action) {
            case 'undo':
                // Implement undo functionality
                break;
            case 'redo':
                // Implement redo functionality
                break;
            case 'zoom-in':
                this.zoomIn();
                break;
            case 'zoom-out':
                this.zoomOut();
                break;
            case 'zoom-fit':
                this.zoomToFit();
                break;
        }
    }
    
    /**
     * Zoom in
     */
    zoomIn() {
        const zoom = this.fabricCanvas.getZoom();
        this.fabricCanvas.setZoom(Math.min(zoom * 1.2, 3));
    }
    
    /**
     * Zoom out
     */
    zoomOut() {
        const zoom = this.fabricCanvas.getZoom();
        this.fabricCanvas.setZoom(Math.max(zoom / 1.2, 0.1));
    }
    
    /**
     * Zoom to fit canvas
     */
    zoomToFit() {
        const container = this.$('.canvas-container');
        if (!container) return;
        
        const containerWidth = container.clientWidth - 40; // padding
        const containerHeight = container.clientHeight - 40;
        
        const canvasWidth = this.fabricCanvas.width;
        const canvasHeight = this.fabricCanvas.height;
        
        const scale = Math.min(
            containerWidth / canvasWidth,
            containerHeight / canvasHeight
        );
        
        this.fabricCanvas.setZoom(scale);
        this.fabricCanvas.centerObject = true;
    }
    
    /**
     * Generate flyer
     */
    async generateFlyer() {
        if (!this.currentListing || this.isGenerating) return;
        
        try {
            this.isGenerating = true;
            this.setLoading(true);
            
            // Get canvas data
            const canvasData = this.fabricCanvas.toJSON(['selectable', 'evented']);
            const canvasImage = this.fabricCanvas.toDataURL({
                format: 'png',
                quality: 1,
                multiplier: 2 // High quality
            });
            
            // Generate flyer
            const result = await this.ajax.generateFlyer(this.currentListing.id, {
                template: this.currentTemplate,
                canvasData,
                canvasImage,
                customization: this.getCustomizationData()
            });
            
            if (result.success) {
                this.handleFlyerGenerated(result.data);
            }
        } catch (error) {
            console.error('Failed to generate flyer:', error);
            this.showError('Failed to generate flyer');
        } finally {
            this.isGenerating = false;
            this.setLoading(false);
        }
    }
    
    /**
     * Handle flyer generation success
     */
    handleFlyerGenerated(data) {
        // Create download link
        const link = document.createElement('a');
        link.href = data.download_url;
        link.download = data.filename;
        link.click();
        
        // Show success message
        this.state.addNotification({
            type: 'success',
            message: 'Flyer generated successfully!',
            action: {
                label: 'Download Again',
                callback: () => {
                    window.open(data.download_url, '_blank');
                }
            }
        });
    }
    
    /**
     * Get customization data
     */
    getCustomizationData() {
        return {
            agentName: this.$('#agent-name')?.value || '',
            agentPhone: this.$('#agent-phone')?.value || '',
            agentEmail: this.$('#agent-email')?.value || '',
            layoutStyle: this.$('#layout-style')?.value || 'classic'
        };
    }
    
    /**
     * Batch generate flyers
     */
    async batchGenerate() {
        const listings = this.state.getFilteredListings();
        
        if (listings.length === 0) {
            this.showError('No listings to generate flyers for');
            return;
        }
        
        if (!confirm(`Generate flyers for ${listings.length} listings?`)) {
            return;
        }
        
        try {
            this.setLoading(true);
            
            const results = [];
            
            // Process listings in batches of 3
            for (let i = 0; i < listings.length; i += 3) {
                const batch = listings.slice(i, i + 3);
                const batchPromises = batch.map(listing => 
                    this.generateFlyerForListing(listing)
                );
                
                const batchResults = await Promise.allSettled(batchPromises);
                results.push(...batchResults);
                
                // Brief pause between batches
                await new Promise(resolve => setTimeout(resolve, 1000));
            }
            
            this.handleBatchResults(results);
        } catch (error) {
            console.error('Batch generation failed:', error);
            this.showError('Batch generation failed');
        } finally {
            this.setLoading(false);
        }
    }
    
    /**
     * Generate flyer for specific listing
     */
    async generateFlyerForListing(listing) {
        const canvasData = this.fabricCanvas.toJSON(['selectable', 'evented']);
        
        return this.ajax.generateFlyer(listing.id, {
            template: this.currentTemplate,
            canvasData,
            customization: this.getCustomizationData()
        });
    }
    
    /**
     * Handle batch generation results
     */
    handleBatchResults(results) {
        const successful = results.filter(r => r.status === 'fulfilled').length;
        const failed = results.filter(r => r.status === 'rejected').length;
        
        this.state.addNotification({
            type: successful > 0 ? 'success' : 'error',
            message: `Batch generation complete: ${successful} successful, ${failed} failed`,
            persistent: true
        });
    }
    
    /**
     * Update generate button state
     */
    updateGenerateButton() {
        const btn = this.$('.generate-flyer');
        if (btn) {
            btn.disabled = !this.currentListing || this.isGenerating;
        }
    }
    
    /**
     * Update listings dropdown
     */
    onStateChange(path, value) {
        if (path === 'listings') {
            this.updateListingsDropdown();
        }
    }
    
    /**
     * Update listings dropdown
     */
    updateListingsDropdown() {
        const select = this.$('#flyer-listing-select');
        if (!select) return;
        
        const listings = this.state.getListings();
        
        // Clear existing options except first
        select.innerHTML = '<option value="">Choose a listing...</option>';
        
        // Add listing options
        listings.forEach(listing => {
            const option = document.createElement('option');
            option.value = listing.id;
            option.textContent = `${listing.address} - $${parseInt(listing.price || 0).toLocaleString()}`;
            select.appendChild(option);
        });
    }
    
    /**
     * Show save template modal
     */
    showSaveTemplateModal() {
        const modal = this.$('.template-modal');
        if (modal) {
            modal.classList.remove('hidden');
        }
    }
    
    /**
     * Handle modal clicks
     */
    handleModalClick(event) {
        const modal = event.target.closest('.template-modal');
        if (!modal) return;
        
        if (event.target.classList.contains('modal-close') || 
            event.target.classList.contains('modal-cancel')) {
            modal.classList.add('hidden');
        }
        
        if (event.target.classList.contains('template-save-confirm')) {
            this.saveTemplate();
        }
    }
    
    /**
     * Save current canvas as template
     */
    async saveTemplate() {
        const name = this.$('#template-name')?.value;
        const description = this.$('#template-description')?.value;
        const isPublic = this.$('#template-public')?.checked;
        
        if (!name) {
            this.showError('Template name is required');
            return;
        }
        
        try {
            const canvasData = this.fabricCanvas.toJSON(['selectable', 'evented']);
            const thumbnail = this.fabricCanvas.toDataURL({
                format: 'jpeg',
                quality: 0.8,
                multiplier: 0.3
            });
            
            const result = await this.ajax.request('save_flyer_template', {
                name,
                description,
                isPublic,
                canvasData,
                thumbnail,
                width: this.fabricCanvas.width,
                height: this.fabricCanvas.height
            });
            
            if (result.success) {
                this.templates.push(result.data.template);
                this.renderTemplateGrid();
                
                // Hide modal
                this.$('.template-modal')?.classList.add('hidden');
                
                this.state.addNotification({
                    type: 'success',
                    message: 'Template saved successfully!'
                });
            }
        } catch (error) {
            console.error('Failed to save template:', error);
            this.showError('Failed to save template');
        }
    }
    
    /**
     * Reset flyer to blank state
     */
    resetFlyer() {
        if (!confirm('Reset flyer? This will clear all changes.')) return;
        
        this.fabricCanvas.clear();
        this.fabricCanvas.backgroundColor = '#ffffff';
        this.fabricCanvas.renderAll();
        
        this.currentTemplate = null;
        this.currentListing = null;
        
        // Reset form controls
        this.$('#flyer-listing-select').value = '';
        this.updateGenerateButton();
    }
    
    /**
     * Save current state for undo/redo
     */
    saveState() {
        // Implementation for undo/redo state management
    }
    
    /**
     * Update property panel for selected object
     */
    updatePropertyPanel(object) {
        if (!object) return;
        
        // Update controls based on selected object
        if (object.type === 'text' || object.type === 'textbox') {
            this.$('#font-family').value = object.fontFamily || 'Arial';
            this.$('#font-size').value = object.fontSize || 16;
            this.$('#text-color').value = object.fill || '#000000';
            this.updateFontSizeDisplay(object.fontSize || 16);
        }
    }
    
    /**
     * Clear property panel
     */
    clearPropertyPanel() {
        // Reset controls to defaults
    }
    
    /**
     * Handle canvas mouse down for tool actions
     */
    handleCanvasMouseDown(e) {
        const activeTool = this.$('.tool-btn.active')?.dataset.tool;
        
        if (activeTool === 'text') {
            this.addTextAtPoint(e.pointer);
        }
    }
    
    /**
     * Add text at specific point
     */
    addTextAtPoint(point) {
        const text = new fabric.Textbox('Click to edit text', {
            left: point.x,
            top: point.y,
            fontSize: 16,
            fontFamily: 'Arial',
            fill: '#000000',
            width: 200
        });
        
        this.fabricCanvas.add(text);
        this.fabricCanvas.setActiveObject(text);
        this.fabricCanvas.renderAll();
        
        // Enter edit mode
        text.enterEditing();
    }
}

// Register component
window.ComponentRegistry.register('flyer-generator', FlyerGenerator);
