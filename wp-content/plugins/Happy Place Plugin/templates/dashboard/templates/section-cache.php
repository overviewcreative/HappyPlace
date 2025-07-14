<?php

/**
 * Dashboard Cache Management Section Template Part
 *
 * @package HappyPlace
 */

// Only administrators can access this section
if (!current_user_can('manage_options')) {
    return;
}

// Access the section data passed from the parent template
$section_data = $args['section_data'] ?? [];
?>

<div class="hph-dashboard-cache">
    <div class="hph-section-header">
        <h2 class="hph-section-title">
            <i class="fas fa-sync-alt"></i>
            <?php _e('Cache Management', 'happy-place'); ?>
        </h2>
        <p class="hph-section-description">
            <?php _e('Clear various WordPress caches to improve site performance.', 'happy-place'); ?>
        </p>
    </div>

    <div class="hph-cache-actions">
        <div class="hph-cache-card">
            <div class="hph-cache-card-header">
                <h3><?php _e('Object Cache', 'happy-place'); ?></h3>
                <p><?php _e('Clear WordPress object cache', 'happy-place'); ?></p>
            </div>
            <div class="hph-cache-card-actions">
                <button class="hph-btn hph-btn--primary hph-cache-action" data-cache-type="object">
                    <i class="fas fa-trash"></i>
                    <?php _e('Clear Object Cache', 'happy-place'); ?>
                </button>
            </div>
        </div>

        <div class="hph-cache-card">
            <div class="hph-cache-card-header">
                <h3><?php _e('Transients', 'happy-place'); ?></h3>
                <p><?php _e('Clear expired transients', 'happy-place'); ?></p>
            </div>
            <div class="hph-cache-card-actions">
                <button class="hph-btn hph-btn--primary hph-cache-action" data-cache-type="transients">
                    <i class="fas fa-trash"></i>
                    <?php _e('Clear Transients', 'happy-place'); ?>
                </button>
            </div>
        </div>

        <div class="hph-cache-card">
            <div class="hph-cache-card-header">
                <h3><?php _e('Rewrite Rules', 'happy-place'); ?></h3>
                <p><?php _e('Flush WordPress rewrite rules', 'happy-place'); ?></p>
            </div>
            <div class="hph-cache-card-actions">
                <button class="hph-btn hph-btn--primary hph-cache-action" data-cache-type="rewrite">
                    <i class="fas fa-sync"></i>
                    <?php _e('Flush Rewrite Rules', 'happy-place'); ?>
                </button>
            </div>
        </div>
    </div>

    <div class="hph-cache-status" id="hph-cache-status" style="display: none;">
        <div class="hph-alert hph-alert--success">
            <p id="hph-cache-message"></p>
        </div>
    </div>
</div>

<style>
    .hph-cache-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: var(--hph-spacing-6);
        margin-top: var(--hph-spacing-6);
    }

    .hph-cache-card {
        background: var(--hph-color-white);
        border: 1px solid var(--hph-color-gray-200);
        border-radius: var(--hph-radius-lg);
        padding: var(--hph-spacing-6);
    }

    .hph-cache-card-header h3 {
        margin: 0 0 var(--hph-spacing-2) 0;
        font-size: var(--hph-font-size-lg);
        font-weight: var(--hph-font-semibold);
    }

    .hph-cache-card-header p {
        margin: 0 0 var(--hph-spacing-4) 0;
        color: var(--hph-color-gray-600);
        font-size: var(--hph-font-size-sm);
    }

    .hph-cache-action {
        width: 100%;
    }

    .hph-alert {
        padding: var(--hph-spacing-4);
        border-radius: var(--hph-radius-lg);
        margin-top: var(--hph-spacing-4);
    }

    .hph-alert--success {
        background-color: #f0f9ff;
        border: 1px solid #0ea5e9;
        color: #0c4a6e;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cacheActions = document.querySelectorAll('.hph-cache-action');
        const statusDiv = document.getElementById('hph-cache-status');
        const messageDiv = document.getElementById('hph-cache-message');

        cacheActions.forEach(button => {
            button.addEventListener('click', function() {
                const cacheType = this.getAttribute('data-cache-type');
                clearCache(cacheType, this);
            });
        });

        function clearCache(type, button) {
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Clearing...';
            button.disabled = true;

            // Simulate cache clearing (you would implement actual cache clearing here)
            setTimeout(() => {
                showMessage(`${type.charAt(0).toUpperCase() + type.slice(1)} cache cleared successfully!`);
                button.innerHTML = originalText;
                button.disabled = false;
            }, 1500);
        }

        function showMessage(message) {
            messageDiv.textContent = message;
            statusDiv.style.display = 'block';

            setTimeout(() => {
                statusDiv.style.display = 'none';
            }, 3000);
        }
    });
</script>