<?php
/**
 * Complete License Generator for WordPress Tools Menu
 * Add this complete code to your license-admin.php file
 */

// Add the Lasso_License_Generator class first
class Lasso_License_Generator {
    private $master_secret = '4044064367'; // Change this to your secret!
    
    public function generate_master_license($domain, $expiry_days = 365) {
        $license_data = array(
            'domain' => $domain,
            'issued' => time(),
            'expires' => time() + ($expiry_days * 24 * 60 * 60),
            'type' => 'master',
            'version' => '5.0.0',
            'features' => array(
                'gravity_forms' => true,
                'contact_form_7' => true,
                'api_integration' => true,
                'unlimited_projects' => true
            )
        );
        
        $license_string = base64_encode(json_encode($license_data));
        $signature = hash_hmac('sha256', $license_string, $this->master_secret);
        
        return $license_string . '.' . $signature;
    }
    
    public function create_license_file_content($license_key, $client_info = array()) {
        $license_hash = hash('sha256', $license_key);
        
        $license_record = array(
            'hash' => $license_hash,
            'created' => date('Y-m-d H:i:s'),
            'client' => $client_info,
            'status' => 'active'
        );
        
        return json_encode($license_record, JSON_PRETTY_PRINT);
    }
}

// Add to your plugin's admin menu
add_action('admin_menu', 'lasso_license_generator_menu');

function lasso_license_generator_menu() {
    add_management_page(
        'License Generator',           // Page title
        'Generate Licenses',           // Menu title  
        'manage_options',             // Capability
        'lasso-license-generator',    // Menu slug
        'lasso_license_generator_page' // Function
    );
}

function lasso_license_generator_page() {
    ?>
    <div class="wrap">
        <h1>🔑 Lasso Leader License Generator</h1>
        <p>Generate secure license keys for your Lasso Leader plugin installations.</p>
        
        <?php if (isset($_POST['generate_licenses']) && wp_verify_nonce($_POST['license_nonce'], 'generate_license_action')): ?>
            <div class="notice notice-success is-dismissible">
                <h3>✅ Generated Licenses:</h3>
                <?php
                $generator = new Lasso_License_Generator();
                
                // Get form data
                $clients = array();
                for ($i = 1; $i <= 5; $i++) {
                    $domain = sanitize_text_field($_POST["domain_$i"] ?? '');
                    $client = sanitize_text_field($_POST["client_$i"] ?? '');
                    if (!empty($domain) && !empty($client)) {
                        $clients[$domain] = $client;
                    }
                }
                
                foreach ($clients as $domain => $client_name) {
                    $license = $generator->generate_master_license($domain, 365);
                    $license_hash = hash('sha256', $license);
                    
                    $client_info = array(
                        'name' => $client_name,
                        'domain' => $domain,
                        'generated_by' => 'Jackson Murphy',
                        'generated_date' => date('Y-m-d H:i:s')
                    );
                    
                    $license_file_content = $generator->create_license_file_content($license, $client_info);
                    
                    echo "<div style='margin: 20px 0; padding: 15px; border: 1px solid #ddd; background: #f9f9f9;'>";
                    echo "<h4>📋 {$client_name} ({$domain})</h4>";
                    
                    echo "<p><strong>License Key:</strong></p>";
                    echo "<textarea readonly style='width:100%;height:80px;font-family:monospace;font-size:12px;'>{$license}</textarea>";
                    
                    echo "<p><strong>GitHub License File Name:</strong> <code>{$license_hash}.json</code></p>";
                    
                    echo "<p><strong>License File Content (for GitHub):</strong></p>";
                    echo "<textarea readonly style='width:100%;height:150px;font-family:monospace;font-size:12px;'>{$license_file_content}</textarea>";
                    
                    echo "<p><em>📝 Instructions: Copy the license key above and provide it to the client. Copy the file content and create a new file in your GitHub repository at: <code>licenses/{$license_hash}.json</code></em></p>";
                    echo "</div>";
                }
                ?>
                
                <div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0;">
                    <h4>📚 Next Steps:</h4>
                    <ol>
                        <li><strong>Save License Keys:</strong> Copy each license key and store them securely</li>
                        <li><strong>Create GitHub Files:</strong> In your <code>lasso-leader-licenses</code> repository, go to the <code>licenses/</code> folder and create new files with the specified names</li>
                        <li><strong>Provide to Clients:</strong> Give each client their respective license key</li>
                        <li><strong>Test:</strong> Have clients enter the license key in their WordPress admin under Lasso License settings</li>
                    </ol>
                </div>
            </div>
        <?php endif; ?>
        
        <div style="background: white; padding: 20px; border: 1px solid #ddd;">
            <form method="post">
                <?php wp_nonce_field('generate_license_action', 'license_nonce'); ?>
                
                <h3>🏗️ Generate New Licenses</h3>
                <p>Enter the domain and client information for each license you want to generate:</p>
                
                <table class="form-table">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <tr>
                        <th scope="row">Client <?php echo $i; ?> Domain</th>
                        <td>
                            <input type="text" 
                                   name="domain_<?php echo $i; ?>" 
                                   placeholder="<?php echo $i == 1 ? 'localhost' : "client{$i}.com"; ?>" 
                                   class="regular-text" 
                                   <?php echo $i == 1 ? 'value="localhost"' : ''; ?> />
                        </td>
                        <th scope="row">Client <?php echo $i; ?> Name</th>
                        <td>
                            <input type="text" 
                                   name="client_<?php echo $i; ?>" 
                                   placeholder="<?php echo $i == 1 ? 'Development Environment' : "Client Name {$i}"; ?>" 
                                   class="regular-text"
                                   <?php echo $i == 1 ? 'value="Development Environment"' : ''; ?> />
                        </td>
                    </tr>
                    <?php endfor; ?>
                </table>
                
                <p class="submit">
                    <input type="submit" name="generate_licenses" class="button-primary" value="🔑 Generate Licenses" />
                </p>
            </form>
        </div>
        
        <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;">
            <h4>⚠️ Security Notes:</h4>
            <ul>
                <li><strong>Master Secret:</strong> Make sure to change the master secret in the code above</li>
                <li><strong>License Keys:</strong> Store license keys securely - they cannot be regenerated</li>
                <li><strong>Domain Binding:</strong> Each license only works on the specified domain</li>
                <li><strong>GitHub Repository:</strong> Keep your license repository private</li>
            </ul>
        </div>
        
        <div style="background: #d1ecf1; padding: 15px; border-left: 4px solid #17a2b8; margin: 20px 0;">
            <h4>📖 How It Works:</h4>
            <ol>
                <li><strong>Generate:</strong> This tool creates cryptographically signed license keys</li>
                <li><strong>Validate:</strong> Your plugin checks these keys against your private GitHub repository</li>
                <li><strong>Authorize:</strong> Only valid licenses allow plugin functionality</li>
                <li><strong>Control:</strong> You can revoke licenses by deleting files from GitHub</li>
            </ol>
        </div>
    </div>
    
    <style>
        .wrap h1 { color: #23282d; }
        .wrap h3 { color: #0073aa; }
        .wrap h4 { color: #32373c; margin-top: 0; }
        .form-table th { width: 150px; }
        .form-table input[type="text"] { width: 250px; }
        code { background: #f1f1f1; padding: 2px 4px; border-radius: 3px; }
        textarea { border: 1px solid #ddd; border-radius: 4px; }
    </style>
    <?php
}
?>