/**
 * Dashboard Entry Point
 * 
 * Main entry point for webpack to bundle all dashboard components
 * @since 3.0.0
 */

// Core Components (Phase 2) - Fixed imports
import './dashboard/ModernDashboard.js';
import './dashboard/DashboardAjax.js';
import './dashboard/DashboardState.js';
import './dashboard/DashboardComponent.js';

// UI Components (Phase 2) - Fixed imports
import './dashboard/SearchFilter.js';
import './dashboard/FlyerGenerator.js';

// Integration Framework (Phase 3)
import './integrations/BaseIntegration.js';
import './integrations/AirtableIntegration.js';
import './integrations/MLSIntegration.js';

// Advanced Components (Phase 3)
import './components/NotificationSystem.js';

// Enhanced Dashboard Manager
class EnhancedDashboard {
    constructor() {
        this.core = null;
        this.integrations = new Map();
        this.notifications = null;
        this.initialized = false;
        
        // Integration configurations
        this.integrationConfigs = {
            airtable: {
                apiKey: window.tpgDashboard?.integrations?.airtable?.apiKey || '',
                baseId: window.tpgDashboard?.integrations?.airtable?.baseId || '',
                listingsTable: 'Listings',
                agentsTable: 'Agents',
                leadsTable: 'Leads',
                syncInterval: 300000 // 5 minutes
            },
            mls: {
                clientId: window.tpgDashboard?.integrations?.mls?.clientId || '',
                clientSecret: window.tpgDashboard?.integrations?.mls?.clientSecret || '',
                apiUrl: window.tpgDashboard?.integrations?.mls?.apiUrl || '',
                tokenUrl: window.tpgDashboard?.integrations?.mls?.tokenUrl || '',
                dataUrl: window.tpgDashboard?.integrations?.mls?.dataUrl || '',
                mlsId: window.tpgDashboard?.integrations?.mls?.mlsId || ''
            }
        };
    }
    
    /**
     * Initialize enhanced dashboard
     */
    async init() {
        if (this.initialized) return;
        
        try {
            console.log('Initializing Enhanced Dashboard v3.0.0');
            
            // Initialize core dashboard
            if (window.DashboardCore) {
                this.core = new DashboardCore();
                await this.core.init();
            }
            
            // Initialize notification system
            await this.initNotificationSystem();
            
            // Initialize integrations
            await this.initIntegrations();
            
            // Setup event handlers
            this.setupEventHandlers();
            
            // Setup real-time features
            this.setupRealTimeFeatures();
            
            this.initialized = true;
            console.log('Enhanced Dashboard initialized successfully');
            
            // Show welcome notification
            this.notifications?.show({
                type: 'system.alert',
                title: 'Dashboard Enhanced',
                message: 'Advanced features and integrations are now active.',
                priority: 'medium'
            });
            
        } catch (error) {
            console.error('Failed to initialize Enhanced Dashboard:', error);
            
            // Show error notification if notification system is available
            this.notifications?.show({
                type: 'sync.error',
                title: 'Initialization Error',
                message: `Failed to initialize dashboard: ${error.message}`,
                priority: 'high'
            });
        }
    }
    
    /**
     * Initialize notification system
     */
    async initNotificationSystem() {
        if (!window.NotificationSystem) return;
        
        this.notifications = new NotificationSystem({
            websocketUrl: window.tpgDashboard?.websocketUrl || '',
            pushNotifications: true,
            desktopNotifications: true,
            soundEnabled: true,
            vapidPublicKey: window.tpgDashboard?.vapidPublicKey || ''
        });
        
        await this.notifications.init();
        
        // Make notifications globally available
        window.dashboardNotifications = this.notifications;
    }
    
    /**
     * Initialize integrations
     */
    async initIntegrations() {
        // Initialize Airtable integration
        if (window.AirtableIntegration && this.integrationConfigs.airtable.apiKey) {
            try {
                const airtable = new AirtableIntegration(this.integrationConfigs.airtable);
                await airtable.init();
                this.integrations.set('airtable', airtable);
                
                console.log('Airtable integration initialized');
                
                this.notifications?.show({
                    type: 'sync.completed',
                    title: 'Airtable Connected',
                    message: 'Airtable integration is active and syncing.',
                    priority: 'low'
                });
                
            } catch (error) {
                console.error('Airtable integration failed:', error);
                
                this.notifications?.show({
                    type: 'sync.error',
                    title: 'Airtable Connection Failed',
                    message: error.message,
                    priority: 'high'
                });
            }
        }
        
        // Initialize MLS integration
        if (window.MLSIntegration && this.integrationConfigs.mls.clientId) {
            try {
                const mls = new MLSIntegration(this.integrationConfigs.mls);
                await mls.init();
                this.integrations.set('mls', mls);
                
                console.log('MLS integration initialized');
                
                this.notifications?.show({
                    type: 'sync.completed',
                    title: 'MLS Connected',
                    message: 'MLS integration is active and syncing.',
                    priority: 'low'
                });
                
            } catch (error) {
                console.error('MLS integration failed:', error);
                
                this.notifications?.show({
                    type: 'sync.error',
                    title: 'MLS Connection Failed',
                    message: error.message,
                    priority: 'high'
                });
            }
        }
    }
    
    /**
     * Setup event handlers
     */
    setupEventHandlers() {
        // Integration event handlers
        this.integrations.forEach((integration, name) => {
            // Sync events
            integration.on('sync:started', (data) => {
                this.notifications?.show({
                    type: 'sync.completed',
                    title: 'Sync Started',
                    message: `${name.charAt(0).toUpperCase() + name.slice(1)} sync in progress...`,
                    priority: 'low'
                });
            });
            
            integration.on('sync:completed', (data) => {
                this.notifications?.show({
                    type: 'sync.completed',
                    title: 'Sync Completed',
                    message: `${name.charAt(0).toUpperCase() + name.slice(1)} sync completed. ${data.processed || 0} items processed.`,
                    priority: 'low'
                });
            });
            
            integration.on('sync:error', (data) => {
                this.notifications?.show({
                    type: 'sync.error',
                    title: 'Sync Error',
                    message: `${name.charAt(0).toUpperCase() + name.slice(1)} sync failed: ${data.error?.message}`,
                    priority: 'high'
                });
            });
            
            // Listing events
            integration.on('listing:created', (data) => {
                this.notifications?.show({
                    type: 'listing.created',
                    title: 'New Listing',
                    message: `New listing added: ${data.listing?.address || 'Unknown address'}`,
                    priority: 'high',
                    actions: [{
                        label: 'View Listing',
                        primary: true,
                        handler: () => {
                            this.viewListing(data.listing);
                        }
                    }]
                });
            });
            
            integration.on('listing:updated', (data) => {
                this.notifications?.show({
                    type: 'listing.updated',
                    title: 'Listing Updated',
                    message: `Listing updated: ${data.listing?.address || 'Unknown address'}`,
                    priority: 'medium'
                });
            });
        });
        
        // Dashboard core events
        if (this.core) {
            this.core.on('listing:saved', (data) => {
                this.notifications?.show({
                    type: 'listing.updated',
                    title: 'Listing Saved',
                    message: 'Listing has been saved successfully.',
                    priority: 'medium'
                });
            });
            
            this.core.on('lead:received', (data) => {
                this.notifications?.show({
                    type: 'lead.received',
                    title: 'New Lead',
                    message: `New lead from ${data.lead?.name || 'Unknown'} for ${data.listing?.address || 'a listing'}`,
                    priority: 'high',
                    actions: [{
                        label: 'View Lead',
                        primary: true,
                        handler: () => {
                            this.viewLead(data.lead);
                        }
                    }]
                });
            });
        }
    }
    
    /**
     * Setup real-time features
     */
    setupRealTimeFeatures() {
        // Auto-refresh listings every 5 minutes
        setInterval(() => {
            this.refreshListings();
        }, 300000);
        
        // Sync integrations every 10 minutes
        setInterval(() => {
            this.syncAllIntegrations();
        }, 600000);
        
        // Health check every minute
        setInterval(() => {
            this.performHealthCheck();
        }, 60000);
    }
    
    /**
     * Refresh listings from all sources
     */
    async refreshListings() {
        try {
            console.log('Refreshing listings from all sources...');
            
            const promises = [];
            
            // Sync from integrations
            this.integrations.forEach((integration, name) => {
                if (typeof integration.syncListings === 'function') {
                    promises.push(
                        integration.syncListings().catch(error => {
                            console.error(`Failed to sync ${name} listings:`, error);
                            return { error: error.message, source: name };
                        })
                    );
                }
            });
            
            const results = await Promise.all(promises);
            
            let totalChanges = 0;
            let errorCount = 0;
            
            results.forEach(result => {
                if (result.error) {
                    errorCount++;
                } else {
                    totalChanges += result.changes || 0;
                }
            });
            
            if (totalChanges > 0) {
                // Refresh the dashboard display
                if (this.core && typeof this.core.refreshListings === 'function') {
                    this.core.refreshListings();
                }
                
                this.notifications?.show({
                    type: 'sync.completed',
                    title: 'Listings Updated',
                    message: `${totalChanges} listing(s) synchronized.`,
                    priority: 'low'
                });
            }
            
        } catch (error) {
            console.error('Failed to refresh listings:', error);
        }
    }
    
    /**
     * Sync all integrations
     */
    async syncAllIntegrations() {
        const promises = [];
        
        this.integrations.forEach((integration, name) => {
            if (typeof integration.sync === 'function') {
                promises.push(integration.sync());
            }
        });
        
        if (promises.length > 0) {
            try {
                await Promise.all(promises);
                console.log('All integrations synced successfully');
            } catch (error) {
                console.error('Failed to sync some integrations:', error);
            }
        }
    }
    
    /**
     * Perform health check
     */
    async performHealthCheck() {
        const healthStatus = {
            dashboard: 'healthy',
            integrations: {},
            notifications: 'healthy'
        };
        
        // Check core dashboard
        if (!this.core || !this.core.initialized) {
            healthStatus.dashboard = 'error';
        }
        
        // Check integrations
        for (const [name, integration] of this.integrations) {
            try {
                const status = await integration.getStatus();
                healthStatus.integrations[name] = status.status || 'unknown';
            } catch (error) {
                healthStatus.integrations[name] = 'error';
            }
        }
        
        // Check notifications
        if (!this.notifications) {
            healthStatus.notifications = 'error';
        }
        
        // Log health status
        const hasErrors = healthStatus.dashboard === 'error' || 
                         Object.values(healthStatus.integrations).some(status => status === 'error') ||
                         healthStatus.notifications === 'error';
        
        if (hasErrors) {
            console.warn('Dashboard health check found issues:', healthStatus);
        }
        
        return healthStatus;
    }
    
    /**
     * Get integration by name
     */
    getIntegration(name) {
        return this.integrations.get(name);
    }
    
    /**
     * Get all integrations
     */
    getAllIntegrations() {
        return Array.from(this.integrations.entries()).map(([name, integration]) => ({
            name,
            integration
        }));
    }
    
    /**
     * View listing (placeholder for navigation)
     */
    viewListing(listing) {
        if (listing?.id) {
            window.location.href = `/wp-admin/post.php?post=${listing.id}&action=edit`;
        }
    }
    
    /**
     * View lead (placeholder for navigation)
     */
    viewLead(lead) {
        console.log('View lead:', lead);
        // Implementation depends on how leads are managed
    }
    
    /**
     * Destroy dashboard
     */
    destroy() {
        // Destroy integrations
        this.integrations.forEach(integration => {
            if (typeof integration.destroy === 'function') {
                integration.destroy();
            }
        });
        
        // Destroy notifications
        if (this.notifications && typeof this.notifications.destroy === 'function') {
            this.notifications.destroy();
        }
        
        // Destroy core
        if (this.core && typeof this.core.destroy === 'function') {
            this.core.destroy();
        }
        
        this.initialized = false;
    }
}

// Initialize enhanced dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.dashboard = new EnhancedDashboard();
    window.dashboard.init();
});

// Export for external use
window.EnhancedDashboard = EnhancedDashboard;
