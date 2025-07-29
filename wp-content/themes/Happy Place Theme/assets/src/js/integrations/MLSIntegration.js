/**
 * Enhanced MLS Integration
 * 
 * Advanced MLS integration with real-time data feeds, IDX compliance,
 * and automated listing synchronization.
 * 
 * @since 3.0.0
 */

class MLSIntegration extends BaseIntegration {
    constructor(config = {}) {
        super({
            apiUrl: config.apiUrl || 'https://api.reso.org',
            rateLimitRequests: 10, // Conservative MLS rate limit
            rateLimitWindow: 1000, // 1 second
            cacheTTL: 300000, // 5 minutes for MLS data
            timeout: 30000, // 30 seconds timeout for MLS requests
            ...config
        });
        
        this.mlsId = config.mlsId || '';
        this.loginUrl = config.loginUrl || '';
        this.tokenUrl = config.tokenUrl || '';
        this.dataUrl = config.dataUrl || '';
        
        // MLS specific configuration
        this.standardVersion = config.standardVersion || '1.7';
        this.mediaUrl = config.mediaUrl || '';
        this.clientId = config.clientId || '';
        this.clientSecret = config.clientSecret || '';
        
        // IDX compliance settings
        this.idxCompliance = {
            disclaimerText: config.disclaimerText || 'Listing information courtesy of [MLS_NAME]',
            logoUrl: config.logoUrl || '',
            updateFrequency: config.updateFrequency || 3600000, // 1 hour
            listingDetailRequirements: config.listingDetailRequirements || {}
        };
        
        // Field mappings for RESO standard
        this.fieldMappings = {
            listing: {
                'ListingKey': 'mls_id',
                'ListingId': 'mls_number',
                'UnparsedAddress': 'address',
                'City': 'city',
                'StateOrProvince': 'state',
                'PostalCode': 'zip',
                'CountyOrParish': 'county',
                'ListPrice': 'price',
                'BedroomsTotal': 'bedrooms',
                'BathroomsTotalInteger': 'bathrooms',
                'LivingAreaSqFt': 'sqft',
                'LotSizeSquareFeet': 'lot_size',
                'PropertyType': 'property_type',
                'PropertySubType': 'property_subtype',
                'StandardStatus': 'status',
                'YearBuilt': 'year_built',
                'PublicRemarks': 'description',
                'ListingContractDate': 'listing_date',
                'ModificationTimestamp': 'updated_at',
                'PhotosCount': 'photos_count',
                'ListAgentKey': 'listing_agent_id',
                'ListOfficeName': 'listing_office',
                'CoListAgentKey': 'co_listing_agent_id',
                'CoListOfficeName': 'co_listing_office',
                'Latitude': 'latitude',
                'Longitude': 'longitude',
                'ElementarySchool': 'elementary_school',
                'MiddleOrJuniorSchool': 'middle_school',
                'HighSchool': 'high_school',
                'Basement': 'basement',
                'GarageSpaces': 'garage_spaces',
                'WaterSource': 'water_source',
                'SewerSource': 'sewer_source',
                'PropertyCondition': 'condition',
                'ArchitecturalStyle': 'architectural_style',
                'FoundationDetails': 'foundation',
                'Heating': 'heating',
                'Cooling': 'cooling',
                'ExteriorFeatures': 'exterior_features',
                'InteriorFeatures': 'interior_features',
                'FireplaceFeatures': 'fireplace_features',
                'SecurityFeatures': 'security_features',
                'AssociationFee': 'hoa_fee',
                'TaxAmount': 'tax_amount',
                'TaxYear': 'tax_year'
            },
            agent: {
                'MemberKey': 'mls_id',
                'MemberMlsId': 'mls_number',
                'MemberFirstName': 'first_name',
                'MemberLastName': 'last_name',
                'MemberFullName': 'full_name',
                'MemberEmail': 'email',
                'MemberPhoneNumber': 'phone',
                'MemberPreferredPhone': 'preferred_phone',
                'MemberLicenseNumber': 'license_number',
                'OfficeKey': 'office_id',
                'OfficeName': 'office_name'
            },
            office: {
                'OfficeKey': 'mls_id',
                'OfficeMlsId': 'mls_number',
                'OfficeName': 'name',
                'OfficePhone': 'phone',
                'OfficeAddress1': 'address',
                'OfficeCity': 'city',
                'OfficeStateOrProvince': 'state',
                'OfficePostalCode': 'zip'
            },
            media: {
                'MediaKey': 'media_id',
                'ResourceRecordKey': 'listing_id',
                'MediaURL': 'url',
                'MediaType': 'type',
                'PreferredPhoto': 'is_preferred',
                'MediaCategory': 'category',
                'MediaObjectID': 'object_id',
                'Order': 'sort_order',
                'ShortDescription': 'description',
                'LongDescription': 'long_description'
            }
        };
        
        // Status mappings
        this.statusMappings = {
            'Active': 'active',
            'Pending': 'pending',
            'Sold': 'sold',
            'Expired': 'expired',
            'Withdrawn': 'withdrawn',
            'Cancelled': 'cancelled',
            'Hold': 'hold',
            'Incomplete': 'draft'
        };
        
        // Sync state
        this.lastSyncTime = null;
        this.syncInProgress = false;
        this.deltaToken = null;
        
        // OAuth tokens
        this.accessToken = null;
        this.refreshToken = null;
        this.tokenExpiry = null;
    }
    
    /**
     * Initialize MLS integration
     */
    async init() {
        await super.init();
        
        // Load stored tokens and sync state
        this.loadStoredState();
        
        // Validate MLS credentials
        await this.validateCredentials();
        
        // Setup automatic token refresh
        this.setupTokenRefresh();
    }
    
    /**
     * Load stored state from localStorage
     */
    loadStoredState() {
        const stored = localStorage.getItem('mls_integration_state');
        if (stored) {
            try {
                const state = JSON.parse(stored);
                this.accessToken = state.accessToken;
                this.refreshToken = state.refreshToken;
                this.tokenExpiry = state.tokenExpiry ? new Date(state.tokenExpiry) : null;
                this.lastSyncTime = state.lastSyncTime ? new Date(state.lastSyncTime) : null;
                this.deltaToken = state.deltaToken;
            } catch (error) {
                this.log('Failed to load stored state', 'error');
            }
        }
    }
    
    /**
     * Save state to localStorage
     */
    saveState() {
        const state = {
            accessToken: this.accessToken,
            refreshToken: this.refreshToken,
            tokenExpiry: this.tokenExpiry?.toISOString(),
            lastSyncTime: this.lastSyncTime?.toISOString(),
            deltaToken: this.deltaToken
        };
        
        localStorage.setItem('mls_integration_state', JSON.stringify(state));
    }
    
    // ===================
    // AUTHENTICATION
    // ===================
    
    /**
     * Authenticate with MLS system
     */
    async authenticate() {
        if (!this.clientId || !this.clientSecret) {
            throw new Error('MLS client ID and secret are required');
        }
        
        try {
            // Get access token using OAuth 2.0 client credentials flow
            const tokenData = await this.getAccessToken();
            
            this.accessToken = tokenData.access_token;
            this.refreshToken = tokenData.refresh_token;
            this.tokenExpiry = new Date(Date.now() + (tokenData.expires_in * 1000));
            
            this.authenticated = true;
            this.lastAuthTime = Date.now();
            
            this.saveState();
            this.log('MLS authentication successful');
            
        } catch (error) {
            throw new Error(`MLS authentication failed: ${error.message}`);
        }
    }
    
    /**
     * Get access token via OAuth
     */
    async getAccessToken() {
        const response = await fetch(this.tokenUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Authorization': `Basic ${btoa(`${this.clientId}:${this.clientSecret}`)}`
            },
            body: new URLSearchParams({
                grant_type: 'client_credentials',
                scope: 'ODataApi'
            })
        });
        
        if (!response.ok) {
            throw new Error(`Token request failed: ${response.status}`);
        }
        
        return response.json();
    }
    
    /**
     * Refresh access token
     */
    async refreshAccessToken() {
        if (!this.refreshToken) {
            throw new Error('No refresh token available');
        }
        
        try {
            const response = await fetch(this.tokenUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    grant_type: 'refresh_token',
                    refresh_token: this.refreshToken
                })
            });
            
            if (!response.ok) {
                throw new Error(`Token refresh failed: ${response.status}`);
            }
            
            const tokenData = await response.json();
            
            this.accessToken = tokenData.access_token;
            if (tokenData.refresh_token) {
                this.refreshToken = tokenData.refresh_token;
            }
            this.tokenExpiry = new Date(Date.now() + (tokenData.expires_in * 1000));
            
            this.saveState();
            this.log('Access token refreshed');
            
        } catch (error) {
            // If refresh fails, re-authenticate
            this.log('Token refresh failed, re-authenticating', 'error');
            await this.authenticate();
        }
    }
    
    /**
     * Check if token needs refresh
     */
    needsTokenRefresh() {
        if (!this.tokenExpiry) return true;
        
        // Refresh if token expires within 5 minutes
        const fiveMinutesFromNow = new Date(Date.now() + 300000);
        return this.tokenExpiry <= fiveMinutesFromNow;
    }
    
    /**
     * Setup automatic token refresh
     */
    setupTokenRefresh() {
        // Check token validity every minute
        setInterval(() => {
            if (this.needsTokenRefresh()) {
                this.refreshAccessToken().catch(error => {
                    this.log(`Token refresh error: ${error.message}`, 'error');
                });
            }
        }, 60000);
    }
    
    /**
     * Build headers for MLS API requests
     */
    buildHeaders(additionalHeaders = {}) {
        const headers = {
            'Accept': 'application/json',
            'User-Agent': 'WordPress-TPG-Dashboard/3.0.0',
            ...additionalHeaders
        };
        
        if (this.accessToken) {
            headers['Authorization'] = `Bearer ${this.accessToken}`;
        }
        
        return headers;
    }
    
    // ===================
    // CORE API METHODS
    // ===================
    
    /**
     * Make authenticated MLS API request
     */
    async mlsRequest(endpoint, options = {}) {
        // Ensure we have a valid token
        if (this.needsTokenRefresh()) {
            await this.refreshAccessToken();
        }
        
        const url = endpoint.startsWith('http') ? endpoint : `${this.dataUrl}/${endpoint}`;
        
        return this.request(url, {
            ...options,
            headers: this.buildHeaders(options.headers)
        });
    }
    
    /**
     * Get metadata about MLS resources
     */
    async getMetadata() {
        return this.mlsRequest('$metadata', {
            cache: true,
            cacheTTL: 86400000 // 24 hours
        });
    }
    
    /**
     * Query listings with OData filters
     */
    async queryListings(options = {}) {
        const params = new URLSearchParams();
        
        // Build OData query parameters
        if (options.select) {
            params.set('$select', Array.isArray(options.select) ? options.select.join(',') : options.select);
        }
        
        if (options.filter) {
            params.set('$filter', options.filter);
        }
        
        if (options.orderby) {
            params.set('$orderby', options.orderby);
        }
        
        if (options.top) {
            params.set('$top', options.top);
        }
        
        if (options.skip) {
            params.set('$skip', options.skip);
        }
        
        if (options.expand) {
            params.set('$expand', options.expand);
        }
        
        const endpoint = `Property?${params.toString()}`;
        return this.mlsRequest(endpoint, {
            cache: true,
            cacheTTL: this.cacheTTL
        });
    }
    
    /**
     * Get single listing by key
     */
    async getListing(listingKey) {
        return this.mlsRequest(`Property('${listingKey}')`, {
            cache: true,
            cacheTTL: this.cacheTTL
        });
    }
    
    /**
     * Get listing media
     */
    async getListingMedia(listingKey) {
        return this.mlsRequest(`Media?$filter=ResourceRecordKey eq '${listingKey}'&$orderby=Order`, {
            cache: true,
            cacheTTL: 3600000 // 1 hour for media
        });
    }
    
    /**
     * Get agent information
     */
    async getAgent(agentKey) {
        return this.mlsRequest(`Member('${agentKey}')`, {
            cache: true,
            cacheTTL: 86400000 // 24 hours for agent info
        });
    }
    
    /**
     * Get office information
     */
    async getOffice(officeKey) {
        return this.mlsRequest(`Office('${officeKey}')`, {
            cache: true,
            cacheTTL: 86400000 // 24 hours for office info
        });
    }
    
    // ===================
    // HIGH-LEVEL OPERATIONS
    // ===================
    
    /**
     * Get active listings with filters
     */
    async getActiveListings(filters = {}) {
        const filterParts = ["StandardStatus eq 'Active'"];
        
        // Add price range filter
        if (filters.minPrice) {
            filterParts.push(`ListPrice ge ${filters.minPrice}`);
        }
        if (filters.maxPrice) {
            filterParts.push(`ListPrice le ${filters.maxPrice}`);
        }
        
        // Add location filters
        if (filters.city) {
            filterParts.push(`City eq '${filters.city}'`);
        }
        if (filters.state) {
            filterParts.push(`StateOrProvince eq '${filters.state}'`);
        }
        if (filters.zip) {
            filterParts.push(`PostalCode eq '${filters.zip}'`);
        }
        
        // Add property type filter
        if (filters.propertyType) {
            if (Array.isArray(filters.propertyType)) {
                const typeFilters = filters.propertyType.map(type => `PropertyType eq '${type}'`);
                filterParts.push(`(${typeFilters.join(' or ')})`);
            } else {
                filterParts.push(`PropertyType eq '${filters.propertyType}'`);
            }
        }
        
        // Add bedrooms/bathrooms filters
        if (filters.minBedrooms) {
            filterParts.push(`BedroomsTotal ge ${filters.minBedrooms}`);
        }
        if (filters.minBathrooms) {
            filterParts.push(`BathroomsTotalInteger ge ${filters.minBathrooms}`);
        }
        
        // Add square footage filter
        if (filters.minSqft) {
            filterParts.push(`LivingAreaSqFt ge ${filters.minSqft}`);
        }
        if (filters.maxSqft) {
            filterParts.push(`LivingAreaSqFt le ${filters.maxSqft}`);
        }
        
        const result = await this.queryListings({
            filter: filterParts.join(' and '),
            orderby: 'ModificationTimestamp desc',
            top: filters.limit || 50
        });
        
        // Transform to WordPress format
        const listings = result.value?.map(listing => 
            this.transformFromMLS('listing', listing)
        ) || [];
        
        return { listings, totalCount: result['@odata.count'] };
    }
    
    /**
     * Get listing details with media
     */
    async getListingDetails(listingKey) {
        try {
            // Get listing data
            const listing = await this.getListing(listingKey);
            
            // Get media
            const media = await this.getListingMedia(listingKey);
            
            // Transform data
            const transformedListing = this.transformFromMLS('listing', listing);
            const transformedMedia = media.value?.map(item => 
                this.transformFromMLS('media', item)
            ) || [];
            
            // Add media to listing
            transformedListing.media = transformedMedia;
            transformedListing.featured_image = transformedMedia.find(m => m.is_preferred)?.url || transformedMedia[0]?.url;
            transformedListing.gallery = transformedMedia.map(m => m.url);
            
            // Get agent information if available
            if (transformedListing.listing_agent_id) {
                try {
                    const agent = await this.getAgent(transformedListing.listing_agent_id);
                    transformedListing.listing_agent = this.transformFromMLS('agent', agent);
                } catch (error) {
                    this.log(`Failed to get agent info: ${error.message}`, 'error');
                }
            }
            
            return transformedListing;
            
        } catch (error) {
            this.log(`Error getting listing details: ${error.message}`, 'error');
            throw error;
        }
    }
    
    /**
     * Sync listings from MLS
     */
    async syncListings(options = {}) {
        if (this.syncInProgress) {
            this.log('Sync already in progress');
            return { changes: 0 };
        }
        
        try {
            this.syncInProgress = true;
            this.emit('sync:started', { type: 'mls_listings' });
            
            const changes = await this.getListingChanges(options);
            
            if (changes.length === 0) {
                this.log('No listing changes found');
                return { changes: 0 };
            }
            
            // Process changes in batches
            const batchSize = 10;
            const processed = [];
            
            for (let i = 0; i < changes.length; i += batchSize) {
                const batch = changes.slice(i, i + batchSize);
                
                for (const change of batch) {
                    try {
                        const listing = await this.getListingDetails(change.ListingKey);
                        
                        // Send to WordPress
                        const ajax = new DashboardAjax();
                        const result = await ajax.request('sync_listing_from_mls', {
                            listing,
                            mls_id: change.ListingKey
                        });
                        
                        if (result.success) {
                            processed.push(listing);
                        }
                        
                    } catch (error) {
                        this.log(`Error processing listing ${change.ListingKey}: ${error.message}`, 'error');
                    }
                }
                
                // Brief pause between batches to respect rate limits
                if (i + batchSize < changes.length) {
                    await new Promise(resolve => setTimeout(resolve, 1000));
                }
            }
            
            // Update sync time
            this.lastSyncTime = new Date();
            this.saveState();
            
            this.emit('sync:completed', { 
                type: 'mls_listings',
                processed: processed.length,
                total: changes.length
            });
            
            return { changes: processed.length };
            
        } catch (error) {
            this.emit('sync:error', { type: 'mls_listings', error });
            throw error;
        } finally {
            this.syncInProgress = false;
        }
    }
    
    /**
     * Get listing changes since last sync
     */
    async getListingChanges(options = {}) {
        let filter = "StandardStatus eq 'Active'";
        
        if (this.lastSyncTime && !options.fullSync) {
            const isoDate = this.lastSyncTime.toISOString();
            filter += ` and ModificationTimestamp gt ${isoDate}`;
        }
        
        const result = await this.queryListings({
            select: 'ListingKey,ModificationTimestamp',
            filter,
            orderby: 'ModificationTimestamp asc',
            top: options.limit || 1000
        });
        
        return result.value || [];
    }
    
    // ===================
    // DATA TRANSFORMATION
    // ===================
    
    /**
     * Transform MLS data to WordPress format
     */
    transformFromMLS(type, data) {
        const mapping = this.fieldMappings[type];
        const transformed = {
            mls_source: this.mlsId,
            mls_updated: new Date().toISOString()
        };
        
        Object.entries(mapping).forEach(([mlsField, wpField]) => {
            const value = data[mlsField];
            
            if (value !== undefined && value !== null) {
                transformed[wpField] = this.transformFieldValue(value, wpField, type);
            }
        });
        
        // Handle status mapping
        if (type === 'listing' && data.StandardStatus) {
            transformed.status = this.statusMappings[data.StandardStatus] || 'active';
        }
        
        // Add IDX compliance fields
        if (type === 'listing') {
            transformed.idx_disclaimer = this.idxCompliance.disclaimerText.replace('[MLS_NAME]', this.mlsId);
            transformed.idx_logo = this.idxCompliance.logoUrl;
            transformed.courtesy_of = data.ListOfficeName || '';
        }
        
        return transformed;
    }
    
    /**
     * Transform field values
     */
    transformFieldValue(value, field, type) {
        // Handle arrays
        if (Array.isArray(value)) {
            return value.join(', ');
        }
        
        // Handle dates
        if (value instanceof Date || (typeof value === 'string' && value.match(/^\d{4}-\d{2}-\d{2}/))) {
            return new Date(value).toISOString();
        }
        
        // Handle numbers
        if (field.includes('price') || field.includes('fee') || field.includes('tax') || field.includes('sqft') || field.includes('size')) {
            return parseFloat(value) || 0;
        }
        
        // Handle integers
        if (field.includes('bedrooms') || field.includes('bathrooms') || field.includes('year') || field.includes('spaces')) {
            return parseInt(value) || 0;
        }
        
        // Handle booleans
        if (typeof value === 'boolean') {
            return value;
        }
        
        return String(value);
    }
    
    // ===================
    // IDX COMPLIANCE
    // ===================
    
    /**
     * Generate IDX compliant listing display
     */
    generateIDXCompliantHTML(listing) {
        const disclaimer = this.idxCompliance.disclaimerText.replace('[MLS_NAME]', this.mlsId);
        
        return `
            <div class="idx-listing" data-mls-id="${listing.mls_id}">
                <div class="listing-content">
                    ${this.generateListingHTML(listing)}
                </div>
                <div class="idx-compliance">
                    <div class="idx-disclaimer">${disclaimer}</div>
                    ${this.idxCompliance.logoUrl ? `<img src="${this.idxCompliance.logoUrl}" alt="MLS Logo" class="idx-logo">` : ''}
                    <div class="idx-timestamp">Last updated: ${new Date(listing.updated_at).toLocaleDateString()}</div>
                </div>
            </div>
        `;
    }
    
    /**
     * Validate IDX compliance requirements
     */
    validateIDXCompliance(listing) {
        const requirements = this.idxCompliance.listingDetailRequirements;
        const violations = [];
        
        // Check required fields
        if (requirements.requiredFields) {
            requirements.requiredFields.forEach(field => {
                if (!listing[field]) {
                    violations.push(`Missing required field: ${field}`);
                }
            });
        }
        
        // Check disclaimer presence
        if (!listing.idx_disclaimer) {
            violations.push('Missing IDX disclaimer');
        }
        
        // Check update frequency
        if (listing.updated_at) {
            const lastUpdate = new Date(listing.updated_at);
            const maxAge = this.idxCompliance.updateFrequency;
            
            if (Date.now() - lastUpdate.getTime() > maxAge) {
                violations.push('Listing data is stale and needs update');
            }
        }
        
        return {
            compliant: violations.length === 0,
            violations
        };
    }
    
    // ===================
    // STATUS & MONITORING
    // ===================
    
    /**
     * Get MLS integration status
     */
    async getStatus() {
        try {
            // Test connection with a simple metadata request
            await this.getMetadata();
            
            return {
                status: 'connected',
                mls_id: this.mlsId,
                authenticated: this.authenticated,
                token_expires: this.tokenExpiry?.toISOString(),
                last_sync: this.lastSyncTime?.toISOString(),
                rate_limit: this.getRateLimitStatus(),
                idx_compliant: true
            };
        } catch (error) {
            return {
                status: 'error',
                error: error.message,
                authenticated: false
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
            delta_token: this.deltaToken,
            cache_size: this.cache.size,
            token_valid: this.tokenExpiry > new Date()
        };
    }
    
    /**
     * Force full resync
     */
    async forceFullResync() {
        this.lastSyncTime = null;
        this.deltaToken = null;
        this.clearCache();
        this.saveState();
        
        return this.syncListings({ fullSync: true });
    }
    
    /**
     * Validate MLS credentials
     */
    async validateCredentials() {
        if (!this.clientId || !this.clientSecret) {
            throw new Error('MLS credentials not configured');
        }
        
        if (!this.dataUrl || !this.tokenUrl) {
            throw new Error('MLS endpoints not configured');
        }
        
        // Try to authenticate
        if (!this.authenticated || this.needsTokenRefresh()) {
            await this.authenticate();
        }
    }
    
    /**
     * Destroy integration
     */
    destroy() {
        this.saveState();
        super.destroy();
    }
}

// Export for use in other modules
window.MLSIntegration = MLSIntegration;
