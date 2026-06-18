/**
 * Lasso Leader Admin JavaScript - CORRECTED VERSION
 * Version: 7.0.1 - Fixed syntax errors, complete and working
 * File: admin/js/lasso-leader-admin.js
 */
(function($) {
    'use strict';
    
    // Debug flag
    const DEBUG = window.lassoLeaderAdmin && window.lassoLeaderAdmin.debug;
    
    // Utility function for debug logging
    function debugLog(message, data = null) {
        if (DEBUG) {
            console.log(`[Lasso Leader] ${message}`, data || '');
        }
    }
    
    // Main initialization
    $(document).ready(function() {
        debugLog('Admin JS v7.0.1 loaded (Corrected - No Syntax Errors)');
        debugLog('Current URL', window.location.href);
        
        try {
            // Initialize based on page type
            if (isGravityFormsPage()) {
                debugLog('Detected: Gravity Forms page');
                initializeGravityFormsIntegration();
            } else if (isLassoAdminPage()) {
                debugLog('Detected: Lasso Leader admin page');
                initializeLassoAdminFeatures();
            } else {
                debugLog('Detected: Other admin page');
                initializeGeneralFeatures();
            }
            
        } catch (error) {
            debugLog('Initialization error', error);
            console.error('Lasso Leader JS Error:', error);
        }
    });
    
    // ========================================
    // PAGE DETECTION
    // ========================================
    
    function isGravityFormsPage() {
        return window.location.href.includes('gf_edit_forms') ||
               window.location.href.includes('subview=lassoleader') ||
               $('.gform-settings-panel').length > 0 ||
               $('body').hasClass('toplevel_page_gf_edit_forms') ||
               $('#gform-settings').length > 0;
    }
    
    function isLassoAdminPage() {
        return window.location.href.includes('lasso-leader');
    }
    
    // ========================================
    // GRAVITY FORMS INTEGRATION
    // ========================================
    
    function initializeGravityFormsIntegration() {
        debugLog('Initializing Gravity Forms integration');
        
        // Wait for GF interface to be ready
        setTimeout(function() {
            attachFieldMappingEvents();
            enhanceGravityFormsInterface();
        }, 500);
        
        // Also try immediately in case elements are already loaded
        attachFieldMappingEvents();
        enhanceGravityFormsInterface();
    }
    
    function attachFieldMappingEvents() {
        debugLog('Attaching field mapping events');
        
        // Use event delegation for better compatibility with GF's dynamic loading
        $(document).off('change.lassoFieldMapping');
        $(document).on('change.lassoFieldMapping', getFieldMappingSelectors(), function() {
            debugLog('Field mapping changed', {
                name: this.name,
                value: this.value,
                type: this.type
            });
            handleFieldMappingChange($(this));
        });
        
        // Clear button events
        $(document).off('click.lassoClearMapping');
        $(document).on('click.lassoClearMapping', '.mapping-clear-btn', function(e) {
            e.preventDefault();
            debugLog('Clear button clicked');
            handleClearMapping($(this));
        });
        
        // Enhance save functionality
        enhanceSaveButton();
        
        // Fix form submission
        fixFormSubmission();
        
        debugLog('✅ Field mapping events attached successfully');
    }
    
    function getFieldMappingSelectors() {
        return [
            'select[name*="_gaddon_setting_field_mapping"][name*="[standard]"]',
            'input[name*="_gaddon_setting_field_mapping"][name*="[question_id]"]',
            'select[name*="_gaddon_setting_field_mapping"][name*="[question_type]"]',
            '.standard-field-select',
            '.question-id-input',
            '.question-type-select'
        ].join(', ');
    }
    
    function handleFieldMappingChange($element) {
        const fieldId = extractFieldIdFromName($element.attr('name'));
        const $row = $element.closest('tr');
        
        debugLog('Handling field mapping change', {
            fieldId: fieldId,
            elementName: $element.attr('name'),
            value: $element.val()
        });
        
        // Handle mutual exclusivity (standard vs question)
        if (fieldId) {
            if ($element.attr('name').includes('[standard]') && $element.val()) {
                // Clear question fields when standard field is selected
                clearQuestionFields(fieldId);
                debugLog('Cleared question fields for field', fieldId);
            } else if (($element.attr('name').includes('[question_id]') || 
                       $element.attr('name').includes('[question_type]')) && $element.val()) {
                // Clear standard field when question is selected
                clearStandardField(fieldId);
                debugLog('Cleared standard field for field', fieldId);
            }
        }
        
        // Update UI
        updateMappingUI($row, $element);
        showNotification('Field mapping updated', 'success');
        showSaveReminder();
    }
    
    function clearQuestionFields(fieldId) {
        $('input[name*="_gaddon_setting_field_mapping[' + fieldId + '][question_id]"]').val('');
        $('select[name*="_gaddon_setting_field_mapping[' + fieldId + '][question_type]"]').val('');
        
        // Remove clear buttons
        $('.mapping-clear-btn[data-field-id="' + fieldId + '"][data-type="question"]').remove();
    }
    
    function clearStandardField(fieldId) {
        $('select[name*="_gaddon_setting_field_mapping[' + fieldId + '][standard]"]').val('');
        
        // Remove clear buttons
        $('.mapping-clear-btn[data-field-id="' + fieldId + '"][data-type="standard"]').remove();
    }
    
    function updateMappingUI($row, $element) {
        const fieldId = extractFieldIdFromName($element.attr('name'));
        const $controls = $element.closest('.lasso-mapping-controls');
        
        if ($element.val() && fieldId) {
            // Add clear button if it doesn't exist
            if (!$controls.find('.mapping-clear-btn').length) {
                const type = $element.attr('name').includes('[standard]') ? 'standard' : 'question';
                const $clearBtn = $('<button type="button" class="mapping-clear-btn" data-type="' + type + '" data-field-id="' + fieldId + '">Clear</button>');
                $controls.append($clearBtn);
            }
        }
    }
    
    function handleClearMapping($button) {
        const fieldId = $button.data('field-id');
        const type = $button.data('type');
        const $row = $button.closest('tr');
        
        debugLog('Clearing mapping', { fieldId: fieldId, type: type });
        
        if (type === 'standard') {
            $row.find('select[name*="[standard]"]').val('');
        } else if (type === 'question') {
            $row.find('input[name*="[question_id]"], select[name*="[question_type]"]').val('');
        }
        
        $button.remove();
        showNotification('Field mapping cleared', 'info');
        showSaveReminder();
    }
    
    function extractFieldIdFromName(name) {
        if (!name) return null;
        const match = name.match(/\[(\d+)\]/);
        return match ? match[1] : null;
    }
    
    // ========================================
    // INTERFACE ENHANCEMENTS
    // ========================================
    
    function enhanceGravityFormsInterface() {
        debugLog('Enhancing Gravity Forms interface');
        
        // Add native GF styling classes
        $('.lasso-field-mapping-table').addClass('gf-table');
        $('.lasso-mapping-controls select').addClass('gf-select');
        $('.lasso-mapping-controls input').addClass('gf-input');
        
        // Enhance field mapping section
        enhanceFieldMappingSection();
        
        // Add helpful tooltips
        addTooltips();
        
        debugLog('✅ Gravity Forms interface enhanced');
    }
    
    function enhanceFieldMappingSection() {
        const $section = $('.lasso-field-mapping-section');
        if ($section.length) {
            // Add collapsible functionality
            $section.find('h4').addClass('lasso-collapsible-header');
            
            // Add status indicator
            if (!$section.find('.lasso-field-mapping-status').length) {
                const statusHtml = '<div class="lasso-field-mapping-status">' +
                    '<span class="dashicons dashicons-yes-alt"></span>' +
                    '<strong>Field Mapping Active:</strong> Enhanced mapping with automatic conflict resolution and native Gravity Forms styling.' +
                    '</div>';
                $section.find('h4').after(statusHtml);
            }
        }
    }
    
    function addTooltips() {
        // Add tooltips to mapping controls
        $('.question-id-input').attr('title', 'Enter the numeric ID of the Lasso question');
        $('.question-type-select').attr('title', 'Select whether this is a text answer or answer ID');
        $('.standard-field-select').attr('title', 'Map to a standard Lasso CRM field');
    }
    
    function enhanceSaveButton() {
        const $saveButton = $('input[type="submit"][value*="Save"], button[type="submit"]').first();
        
        if ($saveButton.length) {
            $saveButton.addClass('gform-settings-save-button');
            debugLog('Save button enhanced with native GF styling');
        }
    }
    
    // ========================================
    // NOTIFICATION SYSTEM
    // ========================================
    
    function showNotification(message, type, duration) {
        type = type || 'info';
        duration = duration || 3000;
        
        // Remove existing notifications
        $('.lasso-notification').remove();
        
        const $notification = $('<div class="lasso-notification ' + type + '">' + message + '</div>');
        
        // Insert notification
        const $target = $('.lasso-field-mapping-section, .gform-settings-panel, .lasso-leader-settings-wrap').first();
        if ($target.length) {
            $target.prepend($notification);
        } else {
            $('body').prepend($notification);
        }
        
        // Auto-remove after duration
        if (duration > 0) {
            setTimeout(function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, duration);
        }
        
        debugLog('Notification shown', { message: message, type: type });
    }
    
    function showSaveReminder() {
        // Remove existing reminders
        $('.lasso-temp-save-reminder').remove();
        
        const $saveButton = $('.gform-settings-save-button, input[type="submit"][value*="Save"]').first();
        
        if ($saveButton.length) {
            const reminderHtml = '<div class="lasso-temp-save-reminder">' +
                'You have unsaved field mapping changes. Click "Save Settings" to preserve your mappings.' +
                '</div>';
            $saveButton.before(reminderHtml);
            
            // Add visual indicator to save button
            $saveButton.addClass('has-changes');
        }
    }
    
    // ========================================
    // FORM SUBMISSION HANDLING
    // ========================================
    
    function fixFormSubmission() {
        let formSubmitted = false;
        
        $('form').off('submit.lassoLeader').on('submit.lassoLeader', function(e) {
            if (formSubmitted) {
                e.preventDefault();
                debugLog('Prevented double form submission');
                return false;
            }
            
            formSubmitted = true;
            debugLog('Form submission initiated');
            
            // Show saving notification
            showNotification('Saving field mappings...', 'info', 0);
            
            // Reset flag after reasonable time
            setTimeout(function() {
                formSubmitted = false;
            }, 5000);
        });
    }
    
    // ========================================
    // LASSO ADMIN FEATURES
    // ========================================
    
    function initializeLassoAdminFeatures() {
        debugLog('Initializing Lasso Leader admin features');
        
        // Enhance API key display
        enhanceApiKeyField();
        
        // Add system status checks
        addSystemStatusChecks();
        
        // Initialize CF7 form handling
        initializeCF7Features();
        
        debugLog('✅ Lasso admin features initialized');
    }
    
    function enhanceApiKeyField() {
        const $apiKeyField = $('#lasso_leader_api_key');
        
        if ($apiKeyField.length) {
            // Add validation
            $apiKeyField.on('input', function() {
                const value = $(this).val();
                const $container = $(this).closest('.lasso-api-key-container');
                
                if (value.length > 0 && !value.startsWith('eyJ')) {
                    $container.addClass('invalid');
                    showNotification('API key should start with "eyJ"', 'warning');
                } else {
                    $container.removeClass('invalid');
                }
            });
            
            // Add copy button
            if (!$apiKeyField.siblings('.copy-api-key').length) {
                const copyButton = '<button type="button" class="button copy-api-key" style="margin-top: 10px;">Copy API Key</button>';
                $apiKeyField.after(copyButton);
                
                $('.copy-api-key').on('click', function() {
                    $apiKeyField.select();
                    document.execCommand('copy');
                    showNotification('API key copied to clipboard', 'success');
                });
            }
        }
    }
    
    function addSystemStatusChecks() {
        // Add live status indicators
        $('.lasso-status-indicator').each(function() {
            const $indicator = $(this);
            // Add animation for better UX
            $indicator.addClass('status-animated');
        });
    }
    
    function initializeCF7Features() {
        // Enhanced CF7 form management
        $('.lasso-cf7-form-item input[type="checkbox"]').on('change', function() {
            const $item = $(this).closest('.lasso-cf7-form-item');
            const $configButton = $item.find('.button');
            
            if ($(this).is(':checked')) {
                $configButton.show().removeClass('disabled');
                $item.addClass('enabled');
            } else {
                $configButton.hide().addClass('disabled');
                $item.removeClass('enabled');
            }
        });
    }
    
    // ========================================
    // GENERAL FEATURES
    // ========================================
    
    function initializeGeneralFeatures() {
        debugLog('Initializing general features');
        
        // Add smooth scrolling to anchors
        $('a[href*="#"]').on('click', function(e) {
            const target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top - 50
                }, 500);
            }
        });
        
        // Add loading states to buttons
        $('.button').on('click', function() {
            const $button = $(this);
            if ($button.attr('type') === 'submit') {
                $button.addClass('loading');
                setTimeout(function() {
                    $button.removeClass('loading');
                }, 2000);
            }
        });
    }
    
    // ========================================
    // DEBUG & TESTING
    // ========================================
    
    // Debug function - can be called from console
    window.lassoDebugMapping = function() {
        console.log('=== LASSO MAPPING DEBUG v7.0.1 (Corrected) ===');
        console.log('Current URL:', window.location.href);
        console.log('Page types:', {
            gravityForms: isGravityFormsPage(),
            lassoAdmin: isLassoAdminPage()
        });
        
        console.log('Elements found:');
        console.log('- Forms:', $('form').length);
        console.log('- Tables:', $('table').length);
        console.log('- Field mapping tables:', $('.lasso-field-mapping-table').length);
        console.log('- Standard selects:', $(getFieldMappingSelectors().split(',')[0]).length);
        console.log('- Question inputs:', $(getFieldMappingSelectors().split(',')[1]).length);
        console.log('- Question selects:', $(getFieldMappingSelectors().split(',')[2]).length);
        
        // Show current mappings
        console.log('Current mappings:');
        $('input[name*="question_id"]').each(function(i) {
            if (this.value) {
                console.log('  ' + i + ': ' + this.name + ' = ' + this.value);
            }
        });
        
        return 'Debug complete - Corrected interface active!';
    };
    
    // Error handling
    window.addEventListener('error', function(e) {
        if (e.filename && e.filename.includes('lasso-leader')) {
            debugLog('JavaScript error caught', {
                message: e.message,
                filename: e.filename,
                line: e.lineno
            });
        }
    });
    
    // Expose public API
    window.LassoLeaderAdmin = {
        debug: debugLog,
        showNotification: showNotification,
        enhanceInterface: enhanceGravityFormsInterface,
        version: '7.0.1'
    };
    
})(jQuery);