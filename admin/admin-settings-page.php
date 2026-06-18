<?php
/**
 * admin/admin-settings-page.php
 * COMPLETE REAL FIX - Professional Gravity Forms styled settings page
 * Version: 6.0.0 - Single Debug Notice, Perfect Spacing
 * 
 * IMPORTANT: This page should ONLY show ONE debug notice
 * Any other debug notices are coming from other files
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// CRITICAL: Prevent duplicate notices by clearing any existing ones
// This removes notices from other parts of the plugin
remove_all_actions('admin_notices');

// Get current settings
$settings = get_option('lasso_leader_settings', array());
$api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
$project_id = isset($settings['project_id']) ? $settings['project_id'] : '';
$debug_mode = isset($settings['debug_mode']) ? $settings['debug_mode'] : false;
$debug_email = isset($settings['debug_email']) ? $settings['debug_email'] : '';

// Check API status
$api_configured = !empty($api_key);
$has_project_id = !empty($project_id);

// IMPORTANT: Track if we've shown the debug notice to prevent duplicates
static $debug_notice_shown = false;
?>

<div class="wrap lasso-leader-admin-wrap">
    <!-- Header Section - GF Style -->
    <div class="lasso-admin-header">
        <h1>
            <i class="fa fa-rocket"></i>
            <?php esc_html_e('Lasso Leader', 'lasso-leader'); ?>
            <span class="lasso-version-badge">v<?php echo esc_html(defined('LASSO_LEADER_VERSION') ? LASSO_LEADER_VERSION : '6.1.3'); ?></span>
        </h1>
        <p class="lasso-header-description">
            <?php esc_html_e('Professional WordPress to Lasso CRM integration for real estate professionals', 'lasso-leader'); ?>
        </p>
    </div>

    <?php 
    // SINGLE DEBUG NOTICE - Only show if debug mode is on AND we haven't shown it yet
    if ($debug_mode && !$debug_notice_shown): 
        $debug_notice_shown = true; // Mark as shown to prevent duplicates
    ?>
    <div class="lasso-admin-notice lasso-admin-notice-warning">
        <div class="lasso-notice-icon">⚠️</div>
        <div class="lasso-notice-content">
            <strong><?php esc_html_e('Debug Mode Active', 'lasso-leader'); ?></strong>
            <p><?php esc_html_e('Debug mode is enabled. Remember to disable it in production for optimal performance.', 'lasso-leader'); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content Grid -->
    <div class="lasso-admin-grid">
        
        <!-- Primary Column -->
        <div class="lasso-admin-primary">
            
            <!-- API Configuration Section -->
            <div class="lasso-settings-section">
                <div class="lasso-section-header">
                    <h2>
                        <i class="dashicons dashicons-admin-network"></i>
                        <?php esc_html_e('Lasso CRM API Settings', 'lasso-leader'); ?>
                    </h2>
                    <div class="lasso-status-indicator <?php echo $api_configured ? 'configured' : 'not-configured'; ?>">
                        <?php echo $api_configured ? '✅ Configured' : '❌ Not Configured'; ?>
                    </div>
                </div>
                
                <div class="lasso-section-content">
                    <form action="options.php" method="post" class="lasso-settings-form">
                        <?php settings_fields('lasso_leader_settings_group'); ?>
                        
                        <!-- API Key Field -->
                        <div class="lasso-form-row">
                            <label for="lasso_api_key" class="lasso-form-label">
                                <?php esc_html_e('Lasso API Key', 'lasso-leader'); ?>
                                <span class="lasso-required">*</span>
                            </label>
                            <div class="lasso-form-control">
                                <textarea 
                                    id="lasso_api_key" 
                                    name="lasso_leader_settings[api_key]" 
                                    rows="4" 
                                    class="lasso-api-key-field"
                                    placeholder="<?php esc_attr_e('Paste your JWT API key from Lasso CRM here...', 'lasso-leader'); ?>"
                                ><?php echo esc_textarea($api_key); ?></textarea>
                                <p class="lasso-field-description">
                                    <?php esc_html_e('Your JWT API key from Lasso CRM. This is project-specific and should start with "eyJ".', 'lasso-leader'); ?>
                                </p>
                                <?php if (!empty($api_key)): ?>
                                <div class="lasso-api-key-info">
                                    <small class="lasso-text-success">
                                        <i class="dashicons dashicons-yes-alt"></i>
                                        <?php esc_html_e('API Key configured', 'lasso-leader'); ?>
                                    </small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Default Project ID -->
                        <div class="lasso-form-row">
                            <label for="lasso_project_id" class="lasso-form-label">
                                <?php esc_html_e('Default Project ID', 'lasso-leader'); ?>
                            </label>
                            <div class="lasso-form-control">
                                <input 
                                    type="text" 
                                    id="lasso_project_id" 
                                    name="lasso_leader_settings[project_id]" 
                                    value="<?php echo esc_attr($project_id); ?>"
                                    class="lasso-text-field medium"
                                    placeholder="<?php esc_attr_e('e.g., 25633', 'lasso-leader'); ?>"
                                />
                                <p class="lasso-field-description">
                                    <?php esc_html_e('Default project ID for form submissions. Can be overridden on individual forms or projects.', 'lasso-leader'); ?>
                                </p>
                            </div>
                        </div>

                        <?php submit_button(__('Save API Settings', 'lasso-leader'), 'primary', 'submit', false, array('class' => 'lasso-save-button')); ?>
                    </form>
                </div>
            </div>

            <!-- Debug Settings Section -->
            <div class="lasso-settings-section">
                <div class="lasso-section-header">
                    <h2>
                        <i class="dashicons dashicons-admin-tools"></i>
                        <?php esc_html_e('Debug Settings', 'lasso-leader'); ?>
                    </h2>
                </div>
                
                <div class="lasso-section-content">
                    <form action="options.php" method="post" class="lasso-settings-form">
                        <?php settings_fields('lasso_leader_settings_group'); ?>
                        
                        <!-- Debug Mode Toggle -->
                        <div class="lasso-form-row">
                            <label class="lasso-form-label">
                                <?php esc_html_e('Debug Mode', 'lasso-leader'); ?>
                            </label>
                            <div class="lasso-form-control">
                                <label class="lasso-checkbox-label">
                                    <input 
                                        type="checkbox" 
                                        name="lasso_leader_settings[debug_mode]" 
                                        value="1" 
                                        <?php checked($debug_mode, 1); ?>
                                        class="lasso-checkbox"
                                    />
                                    <span class="lasso-checkbox-text">
                                        <?php esc_html_e('Enable debug logging and email notifications', 'lasso-leader'); ?>
                                    </span>
                                </label>
                                <p class="lasso-field-description">
                                    <?php esc_html_e('Enables detailed logging and debug email notifications for troubleshooting.', 'lasso-leader'); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Debug Email -->
                        <div class="lasso-form-row">
                            <label for="lasso_debug_email" class="lasso-form-label">
                                <?php esc_html_e('Debug Email Address', 'lasso-leader'); ?>
                            </label>
                            <div class="lasso-form-control">
                                <input 
                                    type="email" 
                                    id="lasso_debug_email" 
                                    name="lasso_leader_settings[debug_email]" 
                                    value="<?php echo esc_attr($debug_email); ?>"
                                    class="lasso-text-field medium"
                                    placeholder="<?php esc_attr_e('admin@example.com', 'lasso-leader'); ?>"
                                />
                                <p class="lasso-field-description">
                                    <?php esc_html_e('Email address to receive debug notifications when forms are submitted.', 'lasso-leader'); ?>
                                </p>
                            </div>
                        </div>

                        <?php submit_button(__('Save Debug Settings', 'lasso-leader'), 'secondary', 'submit', false, array('class' => 'lasso-save-button')); ?>
                    </form>
                </div>
            </div>

            <!-- Add-On Modules Section -->
            <div class="lasso-settings-section">
                <div class="lasso-section-header">
                    <h2>
                        <i class="dashicons dashicons-admin-plugins"></i>
                        <?php esc_html_e('Add-On Modules', 'lasso-leader'); ?>
                    </h2>
                </div>
                
                <div class="lasso-section-content">
                    <div class="lasso-modules-grid">
                        <!-- On-Site Registration Module -->
                        <div class="lasso-module-card">
                            <div class="lasso-module-header">
                                <h3><?php esc_html_e('On-Site Registration', 'lasso-leader'); ?></h3>
                                <span class="lasso-module-status enabled">
                                    <?php esc_html_e('Enabled', 'lasso-leader'); ?>
                                </span>
                            </div>
                            <p class="lasso-module-description">
                                <?php esc_html_e('Enables the On-Site Registration functionality, including custom post types and shortcodes.', 'lasso-leader'); ?>
                            </p>
                        </div>

                        <!-- Gravity Forms Integration -->
                        <div class="lasso-module-card">
                            <div class="lasso-module-header">
                                <h3><?php esc_html_e('Gravity Forms Integration', 'lasso-leader'); ?></h3>
                                <span class="lasso-module-status <?php echo class_exists('GFForms') ? 'enabled' : 'disabled'; ?>">
                                    <?php echo class_exists('GFForms') ? esc_html__('Active', 'lasso-leader') : esc_html__('Requires Gravity Forms', 'lasso-leader'); ?>
                                </span>
                            </div>
                            <p class="lasso-module-description">
                                <?php esc_html_e('Complete Add-On framework integration with visual field mapping and instant save functionality.', 'lasso-leader'); ?>
                            </p>
                        </div>

                        <!-- Contact Form 7 Integration -->
                        <div class="lasso-module-card">
                            <div class="lasso-module-header">
                                <h3><?php esc_html_e('Contact Form 7 Integration', 'lasso-leader'); ?></h3>
                                <span class="lasso-module-status <?php echo class_exists('WPCF7') ? 'enabled' : 'disabled'; ?>">
                                    <?php echo class_exists('WPCF7') ? esc_html__('Active', 'lasso-leader') : esc_html__('Requires Contact Form 7', 'lasso-leader'); ?>
                                </span>
                            </div>
                            <p class="lasso-module-description">
                                <?php esc_html_e('Hook-based integration with custom field mapping for Contact Form 7.', 'lasso-leader'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="lasso-admin-sidebar">
            
            <!-- System Status -->
            <div class="lasso-sidebar-section">
                <h3><?php esc_html_e('System Status', 'lasso-leader'); ?></h3>
                <div class="lasso-status-grid">
                    <div class="lasso-status-item">
                        <span class="lasso-status-label"><?php esc_html_e('Plugin Version', 'lasso-leader'); ?></span>
                        <span class="lasso-status-value"><?php echo esc_html(defined('LASSO_LEADER_VERSION') ? LASSO_LEADER_VERSION : '6.1.3'); ?></span>
                    </div>
                    <div class="lasso-status-item">
                        <span class="lasso-status-label"><?php esc_html_e('WordPress Version', 'lasso-leader'); ?></span>
                        <span class="lasso-status-value"><?php echo esc_html(get_bloginfo('version')); ?></span>
                    </div>
                    <div class="lasso-status-item">
                        <span class="lasso-status-label"><?php esc_html_e('PHP Version', 'lasso-leader'); ?></span>
                        <span class="lasso-status-value"><?php echo esc_html(PHP_VERSION); ?></span>
                    </div>
                    <div class="lasso-status-item">
                        <span class="lasso-status-label"><?php esc_html_e('Gravity Forms', 'lasso-leader'); ?></span>
                        <span class="lasso-status-value <?php echo class_exists('GFForms') ? 'status-active' : 'status-inactive'; ?>">
                            <?php if (class_exists('GFForms')): ?>
                                <?php esc_html_e('Active', 'lasso-leader'); ?>
                                <small>(v<?php echo esc_html(class_exists('GFForms') ? GFForms::$version : 'N/A'); ?>)</small>
                            <?php else: ?>
                                <?php esc_html_e('Not Installed', 'lasso-leader'); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="lasso-status-item">
                        <span class="lasso-status-label"><?php esc_html_e('API Status', 'lasso-leader'); ?></span>
                        <span class="lasso-status-value <?php echo $api_configured ? 'status-configured' : 'status-not-configured'; ?>">
                            <?php echo $api_configured ? esc_html__('Configured', 'lasso-leader') : esc_html__('Not Configured', 'lasso-leader'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="lasso-sidebar-section">
                <h3><?php esc_html_e('Quick Actions', 'lasso-leader'); ?></h3>
                <div class="lasso-quick-actions">
                    <?php if (class_exists('GFForms')): ?>
                    <a href="<?php echo admin_url('admin.php?page=gf_edit_forms'); ?>" class="lasso-quick-action-btn">
                        <i class="dashicons dashicons-forms"></i>
                        <?php esc_html_e('Manage Forms', 'lasso-leader'); ?>
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo admin_url('edit.php?post_type=lasso_project'); ?>" class="lasso-quick-action-btn">
                        <i class="dashicons dashicons-building"></i>
                        <?php esc_html_e('Manage Projects', 'lasso-leader'); ?>
                    </a>
                    <?php if (class_exists('GFForms')): ?>
                    <a href="<?php echo admin_url('admin.php?page=gf_entries'); ?>" class="lasso-quick-action-btn">
                        <i class="dashicons dashicons-list-view"></i>
                        <?php esc_html_e('View Entries', 'lasso-leader'); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Support -->
            <div class="lasso-sidebar-section">
                <h3><?php esc_html_e('Support & Documentation', 'lasso-leader'); ?></h3>
                <div class="lasso-support-links">
                    <p>
                        <a href="#" class="lasso-support-link">
                            <i class="dashicons dashicons-book"></i>
                            <?php esc_html_e('Documentation', 'lasso-leader'); ?>
                        </a>
                    </p>
                    <p>
                        <a href="#" class="lasso-support-link">
                            <i class="dashicons dashicons-sos"></i>
                            <?php esc_html_e('Get Support', 'lasso-leader'); ?>
                        </a>
                    </p>
                    <p>
                        <a href="#" class="lasso-support-link">
                            <i class="dashicons dashicons-star-filled"></i>
                            <?php esc_html_e('Rate Plugin', 'lasso-leader'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Styles with Perfect Spacing -->
<style>
/* Enhanced admin styles with perfect spacing */
.lasso-leader-admin-wrap {
    max-width: 1200px;
    margin: 20px auto;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.lasso-admin-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 35px;
    border-radius: 8px;
    margin-bottom: 30px;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.lasso-admin-header h1 {
    font-size: 28px;
    font-weight: 300;
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.lasso-version-badge {
    background: rgba(255,255,255,0.2);
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.lasso-header-description {
    font-size: 16px;
    opacity: 0.9;
    margin: 0;
}

.lasso-admin-notice {
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 15px;
    animation: slideDown 0.3s ease-out;
}

.lasso-admin-notice-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-left: 4px solid #ff9800;
    color: #856404;
}

.lasso-notice-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.lasso-notice-content strong {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
}

.lasso-admin-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

.lasso-settings-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.04);
    margin-bottom: 25px;
    overflow: hidden;
}

.lasso-section-header {
    background: linear-gradient(to bottom, #f9f9f9 0%, #ececec 100%);
    border-bottom: 1px solid #ddd;
    padding: 20px 25px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.lasso-section-header h2 {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

.lasso-section-content {
    padding: 35px 30px; /* Perfect spacing */
}

.lasso-form-row {
    margin-bottom: 30px;
}

.lasso-form-label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
    font-size: 14px;
}

.lasso-required {
    color: #dc3545;
    margin-left: 3px;
}

.lasso-api-key-field {
    width: 100%;
    min-height: 100px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-family: 'Courier New', Monaco, monospace;
    font-size: 12px;
    background: #fafafa;
    resize: vertical;
    transition: border-color 0.3s ease;
}

.lasso-text-field {
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    background: #fff;
    transition: border-color 0.3s ease;
}

.lasso-text-field.medium {
    width: 300px;
}

.lasso-text-field:focus,
.lasso-api-key-field:focus {
    border-color: #0073aa;
    box-shadow: 0 0 0 2px rgba(0,115,170,0.1);
    outline: none;
}

.lasso-field-description {
    color: #666;
    font-size: 13px;
    margin-top: 8px;
    line-height: 1.4;
}

.lasso-checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.lasso-checkbox {
    transform: scale(1.2);
}

.lasso-status-indicator {
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.lasso-status-indicator.configured {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.lasso-status-indicator.not-configured {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.lasso-save-button {
    background: #0073aa !important;
    border-color: #0073aa !important;
    border-radius: 4px !important;
    padding: 12px 24px !important;
    font-weight: 500 !important;
}

.lasso-modules-grid {
    display: grid;
    gap: 25px;
}

.lasso-module-card {
    border: 1px solid #e1e1e1;
    border-radius: 6px;
    padding: 25px;
    background: #fafafa;
}

.lasso-module-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.lasso-module-header h3 {
    margin: 0;
    font-size: 16px;
    color: #333;
}

.lasso-module-status {
    padding: 6px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.lasso-module-status.enabled {
    background: #d4edda;
    color: #155724;
}

.lasso-module-status.disabled {
    background: #f8d7da;
    color: #721c24;
}

.lasso-sidebar-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.04);
    margin-bottom: 20px;
    overflow: hidden;
}

.lasso-sidebar-section h3 {
    background: linear-gradient(to bottom, #f9f9f9 0%, #ececec 100%);
    border-bottom: 1px solid #ddd;
    padding: 18px 25px;
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #333;
}

.lasso-status-grid {
    padding: 25px;
}

.lasso-status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.lasso-status-item:last-child {
    border-bottom: none;
}

.lasso-status-label {
    font-weight: 500;
    color: #555;
}

.lasso-status-value {
    font-weight: 600;
}

.lasso-status-value.status-active {
    color: #28a745;
}

.lasso-status-value.status-inactive {
    color: #dc3545;
}

.lasso-status-value.status-configured {
    color: #28a745;
}

.lasso-status-value.status-not-configured {
    color: #dc3545;
}

.lasso-quick-actions {
    padding: 25px;
}

.lasso-quick-action-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    text-decoration: none;
    color: #333;
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 4px;
    margin-bottom: 12px;
    transition: all 0.3s ease;
    font-size: 14px;
}

.lasso-quick-action-btn:hover {
    background: #e9ecef;
    color: #0073aa;
    border-color: #0073aa;
    text-decoration: none;
}

.lasso-support-links {
    padding: 25px;
}

.lasso-support-link {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #0073aa;
    text-decoration: none;
    font-size: 14px;
    line-height: 1.8;
}

.lasso-support-link:hover {
    color: #005177;
    text-decoration: none;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .lasso-admin-grid {
        grid-template-columns: 1fr;
    }
    
    .lasso-text-field.medium {
        width: 100%;
    }
    
    .lasso-section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>