<?php
/**
 * Dashboard Marketing Section
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="hph-dashboard-marketing">
    
    <!-- Marketing Header -->
    <div class="marketing-header">
        <div class="header-content">
            <h2 class="page-title">Marketing Tools</h2>
            <p class="page-subtitle">Create professional marketing materials for your listings</p>
        </div>
    </div>

    <!-- Marketing Tools Grid -->
    <div class="marketing-tools-grid">
        
        <!-- Flyer Generator -->
        <div class="marketing-tool-card">
            <div class="tool-icon">
                <i class="fas fa-file-image"></i>
            </div>
            <div class="tool-content">
                <h3 class="tool-title">Property Flyers</h3>
                <p class="tool-description">Generate professional property flyers with your branding</p>
                <div class="tool-stats">
                    <div class="stat-item">
                        <div class="stat-value">12</div>
                        <div class="stat-label">Created</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">256</div>
                        <div class="stat-label">Downloads</div>
                    </div>
                </div>
                <div class="tool-actions">
                    <a href="#" class="action-btn action-btn--primary" onclick="openFlyerGenerator()">Create Flyer</a>
                    <a href="#" class="action-btn">View Templates</a>
                </div>
            </div>
        </div>

        <!-- Social Media Posts -->
        <div class="marketing-tool-card">
            <div class="tool-icon">
                <i class="fas fa-share-alt"></i>
            </div>
            <div class="tool-content">
                <h3 class="tool-title">Social Media Posts</h3>
                <p class="tool-description">Generate social media content for your listings</p>
                <div class="tool-stats">
                    <div class="stat-item">
                        <div class="stat-value">8</div>
                        <div class="stat-label">Posted</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">432</div>
                        <div class="stat-label">Engagements</div>
                    </div>
                </div>
                <div class="tool-actions">
                    <a href="#" class="action-btn action-btn--primary" onclick="openSocialGenerator()">Create Post</a>
                    <a href="#" class="action-btn">Schedule Posts</a>
                </div>
            </div>
        </div>

        <!-- Email Templates -->
        <div class="marketing-tool-card">
            <div class="tool-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="tool-content">
                <h3 class="tool-title">Email Templates</h3>
                <p class="tool-description">Professional email templates for client communication</p>
                <div class="tool-stats">
                    <div class="stat-item">
                        <div class="stat-value">15</div>
                        <div class="stat-label">Templates</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">89</div>
                        <div class="stat-label">Sent</div>
                    </div>
                </div>
                <div class="tool-actions">
                    <a href="#" class="action-btn action-btn--primary" onclick="openEmailTemplates()">Browse Templates</a>
                    <a href="#" class="action-btn">Create Custom</a>
                </div>
            </div>
        </div>

        <!-- Virtual Tour Links -->
        <div class="marketing-tool-card">
            <div class="tool-icon">
                <i class="fas fa-cube"></i>
            </div>
            <div class="tool-content">
                <h3 class="tool-title">Virtual Tours</h3>
                <p class="tool-description">Manage virtual tour links and embedded tours</p>
                <div class="tool-stats">
                    <div class="stat-item">
                        <div class="stat-value">6</div>
                        <div class="stat-label">Active Tours</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">1,234</div>
                        <div class="stat-label">Views</div>
                    </div>
                </div>
                <div class="tool-actions">
                    <a href="#" class="action-btn action-btn--primary" onclick="manageVirtualTours()">Manage Tours</a>
                    <a href="#" class="action-btn">Add New Tour</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Marketing Activity -->
    <div class="marketing-activity">
        <h3>Recent Marketing Activity</h3>
        <div class="activity-list">
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-file-image"></i>
                </div>
                <div class="activity-content">
                    <h4>Property flyer created</h4>
                    <p>Flyer for 123 Main Street was created and downloaded</p>
                    <span class="activity-time">2 hours ago</span>
                </div>
            </div>
            
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-share-alt"></i>
                </div>
                <div class="activity-content">
                    <h4>Social media post shared</h4>
                    <p>Instagram post for 456 Oak Avenue gained 23 likes</p>
                    <span class="activity-time">1 day ago</span>
                </div>
            </div>
            
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="activity-content">
                    <h4>Email template sent</h4>
                    <p>Open house invitation sent to 45 contacts</p>
                    <span class="activity-time">3 days ago</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Marketing Modals (these would be separate components) -->
<div id="flyer-generator-modal" class="marketing-modal" style="display: none;">
    <div class="modal-content">
        <h3>Flyer Generator</h3>
        <p>Flyer generator functionality would be implemented here</p>
        <button onclick="closeFlyerGenerator()">Close</button>
    </div>
</div>

<script>
function openFlyerGenerator() {
    document.getElementById('flyer-generator-modal').style.display = 'block';
}

function closeFlyerGenerator() {
    document.getElementById('flyer-generator-modal').style.display = 'none';
}

function openSocialGenerator() {
    alert('Social media generator would be implemented here');
}

function openEmailTemplates() {
    alert('Email templates browser would be implemented here');
}

function manageVirtualTours() {
    alert('Virtual tours manager would be implemented here');
}
</script>
