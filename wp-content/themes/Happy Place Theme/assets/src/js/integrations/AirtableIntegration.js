/**
 * Enhanced Airtable Integration
 * 
 * Advanced Airtable integration with real-time sync, conflict resolution,
 * and comprehensive field mapping for property listings.
 * 
 * @since 3.0.0
 */

class AirtableIntegration extends BaseIntegration {
    constructor(config = {}) {
        super({
            apiUrl: 'https://api.airtable.com/v0',
            rateLimitRequests: 5, // Airtable allows 5 requests per second
            rateLimitWindow: 1000, // 1 second
            cacheTTL: 60000, // 1 minute for Airtable data
            ...config
        });
        
        this.baseId = config.baseId || '';
        this.tableNames = {
            listings: config.listingsTable || 'Listings',
            agents: config.agentsTable || 'Agents',
            leads: config.leadsTable || 'Leads',
            analytics: config.analyticsTable || 'Analytics'
        };
        
        // Field mappings
        this.fieldMappings = {
            listings: {
                'ID': 'id',
                'Address': 'address',
                'Price': 'price',
                'Bedrooms': 'bedrooms',
                'Bathrooms': 'bathrooms',
                'Square Feet': 'sqft',
                'Property Type': 'property_type',
                'Status': 'status',
                'Description': 'description',
                'Featured Image': 'featured_image',
                'Gallery': 'gallery',
                'MLS Number': 'mls_number',
                'Agent': 'agent_id',
                'Created': 'created_at',
                'Updated': 'updated_at',
                'City': 'city',
                'State': 'state',
                'ZIP': 'zip',
                'Lot Size': 'lot_size',
                'Year Built': 'year_built',
                'HOA Fee': 'hoa_fee',
                'Property Tax': 'property_tax'
            },
            agents: {
                'ID': 'id',
                'Name': 'name',
                'Email': 'email',
                'Phone': 'phone',
                'License': 'license_number',
                'Bio': 'bio',
                'Photo': 'photo',
                'Active': 'active'
            },
            leads: {
                'ID': 'id',
                'Name': 'name',
                'Email': 'email',
                'Phone': 'phone',
                'Property': 'property_id',
                'Message': 'message',
                'Source': 'source',
                'Status': 'status',
                'Created': 'created_at',
                'Follow Up': 'follow_up_date'
            }
        };
        
        // Sync state
        this.lastSyncTime = null;
        this.syncInProgress = false;
        this.syncQueue = [];
        this.conflictResolver = null;
        
        // Real-time sync interval
        this.syncInterval = config.syncInterval || 300000; // 5 minutes
        this.syncTimer = null;
    }
    
    /**
     * Initialize Airtable integration
     */
    async init() {
        await super.init();
        
        // Load last sync time
        this.lastSyncTime = localStorage.getItem('airtable_last_sync');
        if (this.lastSyncTime) {
            this.lastSyncTime = new Date(this.lastSyncTime);
        }
        
        // Start real-time sync
        this.startRealTimeSync();
        
        // Setup webhook handling for Airtable automation
        this.setupAirtableWebhooks();
    }
    
    /**
     * Authenticate with Airtable
     */
    async authenticate() {
        if (!this.config.apiKey) {
            throw new Error('Airtable API key is required');
        }
        
        if (!this.baseId) {
            throw new Error('Airtable base ID is required');
        }
        
        // Test connection by fetching base schema
        try {
            await this.getBaseSchema();
            this.authenticated = true;
            this.lastAuthTime = Date.now();
            this.log('Airtable authentication successful');
        } catch (error) {
            throw new Error(`Airtable authentication failed: ${error.message}`);
        }
    }
    
    /**
     * Build headers for Airtable API
     */
    buildHeaders(additionalHeaders = {}) {
        return {
            'Authorization': `Bearer ${this.config.apiKey}`,
            'Content-Type': 'application/json',
            ...additionalHeaders
        };
    }
    
    // ===================
    // CORE API METHODS
    // ===================
    
    /**
     * Get base schema
     */
    async getBaseSchema() {
        return this.request(`meta/bases/${this.baseId}/tables`, {
            cache: true,
            cacheTTL: 3600000 // 1 hour
        });
    }
    
    /**
     * Get records from a table
     */
    async getRecords(tableName, options = {}) {
        const params = new URLSearchParams();
        
        if (options.fields) {
            options.fields.forEach(field => params.append('fields[]', field));
        }
        
        if (options.filterByFormula) {
            params.set('filterByFormula', options.filterByFormula);
        }
        
        if (options.sort) {
            options.sort.forEach((sort, index) => {
                params.set(`sort[${index}][field]`, sort.field);
                params.set(`sort[${index}][direction]`, sort.direction || 'asc');
            });
        }
        
        if (options.maxRecords) {
            params.set('maxRecords', options.maxRecords);
        }
        
        if (options.offset) {
            params.set('offset', options.offset);
        }
        
        const endpoint = `${this.baseId}/${encodeURIComponent(tableName)}?${params.toString()}`;
        return this.request(endpoint);
    }
    
    /**
     * Get all records with pagination
     */
    async getAllRecords(tableName, options = {}) {
        let allRecords = [];
        let offset = null;
        
        do {
            const result = await this.getRecords(tableName, {
                ...options,
                offset
            });
            
            allRecords = allRecords.concat(result.records);
            offset = result.offset;
            
        } while (offset);
        
        return { records: allRecords };
    }
    
    /**
     * Get single record
     */
    async getRecord(tableName, recordId) {
        const endpoint = `${this.baseId}/${encodeURIComponent(tableName)}/${recordId}`;
        return this.request(endpoint);
    }
    
    /**
     * Create records
     */
    async createRecords(tableName, records) {
        const endpoint = `${this.baseId}/${encodeURIComponent(tableName)}`;
        
        // Airtable allows max 10 records per request
        const batches = [];
        for (let i = 0; i < records.length; i += 10) {
            batches.push(records.slice(i, i + 10));
        }
        
        const results = [];
        for (const batch of batches) {
            const result = await this.request(endpoint, {
                method: 'POST',
                data: { records: batch },
                cache: false
            });
            results.push(...result.records);
        }
        
        return { records: results };
    }
    
    /**
     * Update records
     */
    async updateRecords(tableName, records) {
        const endpoint = `${this.baseId}/${encodeURIComponent(tableName)}`;
        
        // Airtable allows max 10 records per request
        const batches = [];
        for (let i = 0; i < records.length; i += 10) {
            batches.push(records.slice(i, i + 10));
        }
        
        const results = [];
        for (const batch of batches) {
            const result = await this.request(endpoint, {
                method: 'PATCH',
                data: { records: batch },
                cache: false
            });
            results.push(...result.records);
        }
        
        return { records: results };
    }
    
    /**
     * Delete records
     */
    async deleteRecords(tableName, recordIds) {
        const endpoint = `${this.baseId}/${encodeURIComponent(tableName)}`;
        
        // Airtable allows max 10 records per request
        const batches = [];
        for (let i = 0; i < recordIds.length; i += 10) {
            batches.push(recordIds.slice(i, i + 10));
        }
        
        const results = [];
        for (const batch of batches) {
            const params = batch.map(id => `records[]=${id}`).join('&');
            const result = await this.request(`${endpoint}?${params}`, {
                method: 'DELETE',
                cache: false
            });
            results.push(...result.records);
        }
        
        return { records: results };
    }
    
    // ===================
    // HIGH-LEVEL OPERATIONS
    // ===================
    
    /**
     * Get all listings
     */
    async getListings(filters = {}) {
        const options = {
            sort: [{ field: 'Updated', direction: 'desc' }]
        };
        
        // Build filter formula
        if (Object.keys(filters).length > 0) {
            const filterParts = [];
            
            Object.entries(filters).forEach(([key, value]) => {
                const airtableField = this.getAirtableField('listings', key);
                if (airtableField) {
                    if (Array.isArray(value)) {
                        const orParts = value.map(v => `{${airtableField}} = "${v}"`);
                        filterParts.push(`(${orParts.join(', ')})`);
                    } else {
                        filterParts.push(`{${airtableField}} = "${value}"`);
                    }
                }
            });
            
            if (filterParts.length > 0) {
                options.filterByFormula = `AND(${filterParts.join(', ')})`;
            }
        }
        
        const result = await this.getAllRecords(this.tableNames.listings, options);
        
        // Transform Airtable records to WordPress format
        const listings = result.records.map(record => 
            this.transformFromAirtable('listings', record)
        );
        
        return { listings };
    }
    
    /**
     * Create listing in Airtable
     */
    async createListing(listingData) {
        const airtableData = this.transformToAirtable('listings', listingData);
        
        const result = await this.createRecords(this.tableNames.listings, [
            { fields: airtableData }
        ]);
        
        if (result.records.length > 0) {
            return this.transformFromAirtable('listings', result.records[0]);
        }
        
        throw new Error('Failed to create listing in Airtable');
    }
    
    /**
     * Update listing in Airtable
     */
    async updateListing(listingId, listingData) {
        const airtableData = this.transformToAirtable('listings', listingData);
        
        const result = await this.updateRecords(this.tableNames.listings, [
            { id: listingId, fields: airtableData }
        ]);
        
        if (result.records.length > 0) {
            return this.transformFromAirtable('listings', result.records[0]);
        }
        
        throw new Error('Failed to update listing in Airtable');
    }
    
    /**
     * Sync listings with WordPress
     */
    async syncListings() {
        if (this.syncInProgress) {
            this.log('Sync already in progress, skipping');
            return;
        }
        
        try {
            this.syncInProgress = true;
            this.emit('sync:started', { type: 'listings' });
            
            // Get changes since last sync
            const changes = await this.getChangesSinceLastSync('listings');
            
            if (changes.length === 0) {
                this.log('No changes found since last sync');
                return { changes: 0 };
            }
            
            // Process changes
            const processed = await this.processListingChanges(changes);
            
            // Update last sync time
            this.lastSyncTime = new Date();
            localStorage.setItem('airtable_last_sync', this.lastSyncTime.toISOString());
            
            this.emit('sync:completed', { 
                type: 'listings', 
                processed: processed.length,
                changes: changes.length 
            });
            
            return { changes: processed.length };
            
        } catch (error) {
            this.emit('sync:error', { type: 'listings', error });
            throw error;
        } finally {
            this.syncInProgress = false;
        }
    }
    
    /**
     * Get changes since last sync
     */
    async getChangesSinceLastSync(type) {
        if (!this.lastSyncTime) {
            // First sync - get all records
            this.log('First sync - getting all records');
            const result = await this.getAllRecords(this.tableNames[type]);
            return result.records;
        }
        
        // Get records modified since last sync
        const isoDate = this.lastSyncTime.toISOString();
        const filterFormula = `IS_AFTER({Updated}, "${isoDate}")`;
        
        const result = await this.getAllRecords(this.tableNames[type], {
            filterByFormula: filterFormula,
            sort: [{ field: 'Updated', direction: 'asc' }]
        });
        
        return result.records;
    }
    
    /**
     * Process listing changes
     */
    async processListingChanges(changes) {
        const processed = [];
        
        for (const record of changes) {
            try {
                const listing = this.transformFromAirtable('listings', record);
                
                // Send to WordPress via AJAX
                const ajax = new DashboardAjax();
                const result = await ajax.request('sync_listing_from_airtable', {
                    listing,
                    airtable_id: record.id
                });
                
                if (result.success) {
                    processed.push(listing);
                    this.log(`Synced listing: ${listing.address}`);
                } else {
                    this.log(`Failed to sync listing: ${result.data?.error}`, 'error');
                }
                
            } catch (error) {
                this.log(`Error processing listing ${record.id}: ${error.message}`, 'error');
            }
        }
        
        return processed;
    }
    
    // ===================
    // DATA TRANSFORMATION
    // ===================
    
    /**
     * Transform data from Airtable to WordPress format
     */
    transformFromAirtable(type, record) {
        const mapping = this.fieldMappings[type];
        const transformed = {
            airtable_id: record.id,
            airtable_created: record.createdTime
        };
        
        Object.entries(mapping).forEach(([airtableField, wpField]) => {
            const value = record.fields[airtableField];
            
            if (value !== undefined) {
                // Handle different field types
                transformed[wpField] = this.transformFieldValue(value, wpField);
            }
        });
        
        return transformed;
    }
    
    /**
     * Transform data from WordPress to Airtable format
     */
    transformToAirtable(type, data) {
        const mapping = this.fieldMappings[type];
        const transformed = {};
        
        Object.entries(mapping).forEach(([airtableField, wpField]) => {
            const value = data[wpField];
            
            if (value !== undefined && value !== null && value !== '') {
                transformed[airtableField] = this.transformFieldValueToAirtable(value, wpField);
            }
        });
        
        return transformed;
    }
    
    /**
     * Transform individual field value from Airtable
     */
    transformFieldValue(value, field) {
        // Handle attachments (images)
        if (Array.isArray(value) && value.length > 0 && value[0].url) {
            return value.map(attachment => attachment.url);
        }
        
        // Handle linked records
        if (Array.isArray(value) && value.length > 0 && typeof value[0] === 'string') {
            return value[0]; // Take first linked record
        }
        
        // Handle numbers
        if (field.includes('price') || field.includes('sqft') || field.includes('fee') || field.includes('tax')) {
            return parseFloat(value) || 0;
        }
        
        // Handle integers
        if (field.includes('bedrooms') || field.includes('bathrooms') || field.includes('year')) {
            return parseInt(value) || 0;
        }
        
        return value;
    }
    
    /**
     * Transform field value to Airtable format
     */
    transformFieldValueToAirtable(value, field) {
        // Handle arrays (like image galleries)
        if (Array.isArray(value)) {
            // For attachments, convert URLs to Airtable attachment format
            if (field.includes('image') || field.includes('gallery')) {
                return value.map(url => ({ url }));
            }
            return value[0]; // Take first value for other arrays
        }
        
        return value;
    }
    
    /**
     * Get Airtable field name from WordPress field
     */
    getAirtableField(type, wpField) {
        const mapping = this.fieldMappings[type];
        
        for (const [airtableField, mappedWpField] of Object.entries(mapping)) {
            if (mappedWpField === wpField) {
                return airtableField;
            }
        }
        
        return null;
    }
    
    // ===================
    // REAL-TIME SYNC
    // ===================
    
    /**
     * Start real-time sync
     */
    startRealTimeSync() {
        if (this.syncTimer) {
            clearInterval(this.syncTimer);
        }
        
        this.syncTimer = setInterval(async () => {
            try {
                await this.syncListings();
            } catch (error) {
                this.log(`Real-time sync error: ${error.message}`, 'error');
            }
        }, this.syncInterval);
        
        this.log(`Real-time sync started with ${this.syncInterval / 1000}s interval`);
    }
    
    /**
     * Stop real-time sync
     */
    stopRealTimeSync() {
        if (this.syncTimer) {
            clearInterval(this.syncTimer);
            this.syncTimer = null;
            this.log('Real-time sync stopped');
        }
    }
    
    // ===================
    // WEBHOOK SUPPORT
    // ===================
    
    /**
     * Setup Airtable webhooks
     */
    setupAirtableWebhooks() {
        // Register webhook handlers for different events
        this.registerWebhook('listing.created', (data) => {
            this.handleListingCreated(data);
        });
        
        this.registerWebhook('listing.updated', (data) => {
            this.handleListingUpdated(data);
        });
        
        this.registerWebhook('listing.deleted', (data) => {
            this.handleListingDeleted(data);
        });
    }
    
    /**
     * Handle listing created webhook
     */
    async handleListingCreated(data) {
        try {
            const listing = this.transformFromAirtable('listings', data.record);
            
            const ajax = new DashboardAjax();
            await ajax.request('create_listing_from_airtable', {
                listing,
                airtable_id: data.record.id
            });
            
            this.emit('listing:created', { listing });
            
        } catch (error) {
            this.log(`Webhook handling error (created): ${error.message}`, 'error');
        }
    }
    
    /**
     * Handle listing updated webhook
     */
    async handleListingUpdated(data) {
        try {
            const listing = this.transformFromAirtable('listings', data.record);
            
            const ajax = new DashboardAjax();
            await ajax.request('update_listing_from_airtable', {
                listing,
                airtable_id: data.record.id
            });
            
            this.emit('listing:updated', { listing });
            
        } catch (error) {
            this.log(`Webhook handling error (updated): ${error.message}`, 'error');
        }
    }
    
    /**
     * Handle listing deleted webhook
     */
    async handleListingDeleted(data) {
        try {
            const ajax = new DashboardAjax();
            await ajax.request('delete_listing_from_airtable', {
                airtable_id: data.record.id
            });
            
            this.emit('listing:deleted', { airtable_id: data.record.id });
            
        } catch (error) {
            this.log(`Webhook handling error (deleted): ${error.message}`, 'error');
        }
    }
    
    // ===================
    // STATUS & HEALTH
    // ===================
    
    /**
     * Get service status
     */
    async getStatus() {
        try {
            const schema = await this.getBaseSchema();
            
            return {
                status: 'connected',
                base_id: this.baseId,
                tables: schema.tables?.length || 0,
                last_sync: this.lastSyncTime,
                sync_active: !!this.syncTimer,
                rate_limit: this.getRateLimitStatus()
            };
        } catch (error) {
            return {
                status: 'error',
                error: error.message
            };
        }
    }
    
    /**
     * Get sync statistics
     */
    getSyncStats() {
        return {
            last_sync_time: this.lastSyncTime,
            sync_in_progress: this.syncInProgress,
            sync_interval: this.syncInterval,
            queue_size: this.syncQueue.length,
            cache_size: this.cache.size
        };
    }
    
    /**
     * Force full resync
     */
    async forceFullResync() {
        this.lastSyncTime = null;
        localStorage.removeItem('airtable_last_sync');
        this.clearCache();
        
        return this.syncListings();
    }
    
    /**
     * Destroy integration
     */
    destroy() {
        this.stopRealTimeSync();
        super.destroy();
    }
}

// Export for use in other modules
window.AirtableIntegration = AirtableIntegration;
