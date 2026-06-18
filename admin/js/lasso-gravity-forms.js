/**
 * Lasso Leader - Gravity Forms Admin JavaScript
 * Handles API testing, field mapping, and enhanced UI functionality
 */

jQuery(document).ready(function($) {
    
    // API Key Testing
    $('#lasso-test-api-btn').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $result = $('#lasso-api-test-result');
        var apiKey = $('input[name="_gaddon_setting_api_key_override"]').val();
        
        // Use global API key if override is empty
        if (!apiKey) {
            // Try to get global API key via AJAX if needed
            $result.html('<div class="notice notice-warning inline"><p>No API key override set. Testing with global API key...</p></div>');
            return;
        }
        
        $button.prop('disabled', true).text('Testing...');
        $result.html('<div class="notice notice-info inline"><p>Testing API connection...</p></div>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lasso_test_api_key',
                api_key: apiKey,
                nonce: gform_save_form_settings_nonce
            },
            success: function(response) {
                if (response.success) {
                    var message = response.data.message;
                    var isPro = response.data.is_pro;
                    var iconClass = isPro ? 'dashicons-star-filled' : 'dashicons-yes-alt';
                    var proLabel = isPro ? '<span class="lasso-pro-badge">PRO</span>' : '';
                    
                    $result.html(
                        '<div class="notice notice-success inline">' +
                        '<p><span class="dashicons ' + iconClass + '"></span> ' + message + ' ' + proLabel + '</p>' +
                        '</div>'
                    );
                } else {
                    $result.html(
                        '<div class="notice notice-error inline">' +
                        '<p><span class="dashicons dashicons-warning"></span> ' + response.data + '</p>' +
                        '</div>'
                    );
                }
            },
            error: function() {
                $result.html(
                    '<div class="notice notice-error inline">' +
                    '<p><span class="dashicons dashicons-warning"></span> Failed to test API connection</p>' +
                    '</div>'
                );
            },
            complete: function() {
                $button.prop('disabled', false).text('Test API Connection');
            }
        });
    });
    
    // Auto-detect API key type when typing
    var apiKeyTimeout;
    $('input[name="_gaddon_setting_api_key_override"]').on('input', function() {
        var $input = $(this);
        var $result = $('#lasso-api-test-result');
        var apiKey = $input.val().trim();
        
        clearTimeout(apiKeyTimeout);
        
        if (apiKey.length > 10) { // Only check if we have a reasonable length key
            apiKeyTimeout = setTimeout(function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'lasso_get_api_info',
                        api_key: apiKey,
                        nonce: gform_save_form_settings_nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.valid) {
                            var isPro = response.data.is_pro;
                            var features = response.data.features.join(', ');
                            var keyType = isPro ? 'Pro' : 'Standard';
                            var badgeClass = isPro ? 'lasso-pro-badge' : 'lasso-standard-badge';
                            
                            $result.html(
                                '<div class="lasso-api-info">' +
                                '<span class="' + badgeClass + '">' + keyType + ' Key</span>' +
                                '<small>Features: ' + features + '</small>' +
                                '</div>'
                            );
                        } else if (response.data && !response.data.valid) {
                            $result.html(
                                '<div class="lasso-api-info error">' +
                                '<span class="lasso-error-badge">Invalid Key</span>' +
                                '</div>'
                            );
                        }
                    }
                });
            }, 1000); // Wait 1 second after user stops typing
        } else {
            $result.empty();
        }
    });
    
    // Enhanced field mapping functionality
    if ($('.lasso-mapping-table').length) {
        initFieldMappingFeatures();
    }
    
    function initFieldMappingFeatures() {
        // Highlight mapped fields
        $('.lasso-field-select').each(function() {
            updateMappingRowStyle($(this));
        });
        
        // Update styling when mapping changes
        $('.lasso-field-select').on('change', function() {
            updateMappingRowStyle($(this));
        });
        
        // Add search functionality
        addMappingSearch();
        
        // Add mapping statistics
        updateMappingStats();
        $('.lasso-field-select').on('change', updateMappingStats);
    }
    
    function updateMappingRowStyle($select) {
        var $row = $select.closest('tr');
        var value = $select.val();
        
        $row.removeClass('mapped-standard mapped-question mapped-empty');
        
        if (!value) {
            $row.addClass('mapped-empty');
        } else if (value.startsWith('q_')) {
            $row.addClass('mapped-question');
        } else {
            $row.addClass('mapped-standard');
        }
    }
    
    function addMappingSearch() {
        var $searchContainer = $('<div class="lasso-mapping-search" style="margin-bottom: 15px;">' +
            '<input type="text" placeholder="Search form fields..." id="lasso-field-search" style="width: 300px;">' +
            '</div>');
        
        $('.lasso-mapping-table').before($searchContainer);
        
        $('#lasso-field-search').on('input', function() {
            var searchTerm = $(this).val().toLowerCase();
            
            $('.lasso-mapping-row').each(function() {
                var $row = $(this);
                var fieldName = $row.find('td:first strong').text().toLowerCase();
                
                if (fieldName.includes(searchTerm) || searchTerm === '') {
                    $row.show();
                } else {
                    $row.hide();
                }
            });
        });
    }
    
    function updateMappingStats() {
        var totalFields = $('.lasso-mapping-row').length;
        var mappedFields = $('.lasso-field-select').filter(function() {
            return $(this).val() !== '';
        }).length;
        var standardMapped = $('.lasso-field-select').filter(function() {
            var val = $(this).val();
            return val !== '' && !val.startsWith('q_');
        }).length;
        var questionsMapped = $('.lasso-field-select').filter(function() {
            return $(this).val().startsWith('q_');
        }).length;
        
        var $stats = $('.lasso-mapping-stats');
        if ($stats.length === 0) {
            $stats = $('<div class="lasso-mapping-stats" style="margin-top: 15px; padding: 10px; background: #f0f0f1; border-radius: 4px;"></div>');
            $('.lasso-mapping-tools').after($stats);
        }
        
        $stats.html(
            '<strong>Mapping Statistics:</strong> ' +
            mappedFields + ' of ' + totalFields + ' fields mapped | ' +
            standardMapped + ' standard fields, ' + questionsMapped + ' custom questions'
        );
    }
    
    // Save confirmation with mapping validation
    $('form').on('submit', function(e) {
        var mappedCount = $('.lasso-field-select').filter(function() {
            return $(this).val() !== '';
        }).length;
        
        if (mappedCount === 0 && $('input[name="_gaddon_setting_enable_integration"]').is(':checked')) {
            if (!confirm('You have Lasso integration enabled but no fields mapped. Are you sure you want to save?')) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // Add tooltips for field types
    $('.lasso-mapping-row').each(function() {
        var $row = $(this);
        var fieldId = $row.data('field-id');
        var $select = $row.find('.lasso-field-select');
        
        $select.on('change', function() {
            var value = $(this).val();
            var $tooltip = $row.find('.lasso-mapping-tooltip');
            
            if ($tooltip.length) {
                $tooltip.remove();
            }
            
            if (value.startsWith('q_')) {
                var questionId = value.substring(2);
                $('<small class="lasso-mapping-tooltip" style="display: block; color: #666; margin-top: 5px;">Will be sent as custom question ID: ' + questionId + '</small>')
                    .appendTo($select.parent());
            } else if (value && value !== '') {
                var description = getFieldDescription(value);
                if (description) {
                    $('<small class="lasso-mapping-tooltip" style="display: block; color: #666; margin-top: 5px;">' + description + '</small>')
                        .appendTo($select.parent());
                }
            }
        });
    });
    
    function getFieldDescription(fieldType) {
        var descriptions = {
            'firstName': 'Maps to the registrant\'s first name',
            'lastName': 'Maps to the registrant\'s last name',
            'email': 'Creates a primary email contact',
            'phone': 'Creates a primary phone contact',
            'message': 'Adds a note to the registrant record'
        };
        
        return descriptions[fieldType] || null;
    }
    
    // Add CSS for enhanced styling
    if (!$('#lasso-gf-admin-styles').length) {
        $('<style id="lasso-gf-admin-styles">' +
            '.lasso-pro-badge { background: #007cba; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: bold; }' +
            '.lasso-standard-badge { background: #72aee6; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; }' +
            '.lasso-error-badge { background: #d63638; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; }' +
            '.lasso-api-info { margin-top: 5px; }' +
            '.lasso-api-info.error { color: #d63638; }' +
            '.lasso-mapping-table tr.mapped-standard { background-color: #f0f8ff; }' +
            '.lasso-mapping-table tr.mapped-question { background-color: #f8f0ff; }' +
            '.lasso-mapping-table tr.mapped-empty { opacity: 0.7; }' +
            '.lasso-mapping-tools button { margin-right: 10px; }' +
            '</style>').appendTo('head');
    }
});