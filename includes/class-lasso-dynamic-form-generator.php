<?php
/**
 * Lasso Dynamic Form Generator
 * Creates Gravity Forms dynamically based on selected Lasso questions
 * Updated: Jul 7, 2025 - Complete system with all questions and answers
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Lasso_Dynamic_Form_Generator {

    private static $instance = null;
    
    public static function get_instance() {
        if ( self::$instance == null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'wp_ajax_lasso_generate_form', array( $this, 'ajax_generate_form' ) );
        add_action( 'wp_ajax_lasso_preview_question', array( $this, 'ajax_preview_question' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Complete Lasso Questions Database
     * All questions with their types and answers from the API data
     */
    public function get_lasso_questions_database() {
        return array(
            // Standard Contact Fields
            'contact_fields' => array(
                'title' => 'Contact Information',
                'fields' => array(
                    'firstName' => array(
                        'label' => 'First Name',
                        'type' => 'text',
                        'required' => true,
                        'lasso_field' => 'firstName'
                    ),
                    'lastName' => array(
                        'label' => 'Last Name',
                        'type' => 'text',
                        'required' => true,
                        'lasso_field' => 'lastName'
                    ),
                    'email' => array(
                        'label' => 'Email',
                        'type' => 'email',
                        'required' => true,
                        'lasso_field' => 'email'
                    ),
                    'phone' => array(
                        'label' => 'Phone',
                        'type' => 'phone',
                        'required' => false,
                        'lasso_field' => 'phone'
                    )
                )
            ),

            // Contact Preference
            'contact_preference' => array(
                'title' => 'Contact Preferences',
                'fields' => array(
                    'q_157502' => array(
                        'question_id' => 157502,
                        'label' => 'Contact Preference',
                        'type' => 'select',
                        'required' => false,
                        'choices' => array(
                            '397182' => 'Any',
                            '397183' => 'Email',
                            '397184' => 'Phone',
                            '397185' => 'Agent',
                            '397186' => 'Text Message'
                        )
                    )
                )
            ),

            // Basic Demographics
            'demographics' => array(
                'title' => 'Demographics',
                'fields' => array(
                    'q_157181' => array(
                        'question_id' => 157181,
                        'label' => 'What is your current age range?',
                        'type' => 'select',
                        'required' => false,
                        'choices' => array(
                            '396550' => 'Under 25',
                            '396549' => '25-34',
                            '396548' => '35-44',
                            '396547' => '45-54',
                            '396546' => '55-64',
                            '396545' => '65+'
                        )
                    ),
                    'q_157182' => array(
                        'question_id' => 157182,
                        'label' => 'Do you presently own or rent?',
                        'type' => 'checkbox',
                        'required' => false,
                        'choices' => array(
                            '396552' => 'Own',
                            '396551' => 'Rent'
                        )
                    ),
                    'q_157189' => array(
                        'question_id' => 157189,
                        'label' => 'Type of Household',
                        'type' => 'select',
                        'required' => false,
                        'choices' => array(
                            '396629' => 'Family with children',
                            '396628' => 'Couple',
                            '396627' => 'Single female',
                            '396626' => 'Single male',
                            '396625' => 'Individuals buying together',
                            '396624' => 'Single Parent'
                        )
                    )
                )
            ),

            // Property Search
            'property_search' => array(
                'title' => 'Property Search Criteria',
                'fields' => array(
                    'q_157178' => array(
                        'question_id' => 157178,
                        'label' => 'What is your desired price range?',
                        'type' => 'select',
                        'required' => false,
                        'choices' => array(
                            '396534' => '$500,000 - $750,000',
                            '396533' => '$750,000 - $1M',
                            '396532' => '$1M - $1.25M',
                            '396531' => '$1.25M - $1.5M',
                            '396530' => '$1.5M - $2M',
                            '396529' => '$2M+'
                        )
                    ),
                    'q_157179' => array(
                        'question_id' => 157179,
                        'label' => 'What size home are you interested in?',
                        'type' => 'select',
                        'required' => false,
                        'choices' => array(
                            '396535' => '1 Bedroom',
                            '396540' => '2 Bedroom',
                            '396539' => '3 Bedroom',
                            '396538' => '3 Bedroom + Den',
                            '396537' => '3 Bedroom Penthouse',
                            '396536' => '4 Bedroom'
                        )
                    ),
                    'q_157180' => array(
                        'question_id' => 157180,
                        'label' => 'When are you planning to move?',
                        'type' => 'select',
                        'required' => false,
                        'choices' => array(
                            '396544' => 'Now',
                            '396543' => 'Within 3 months',
                            '396542' => 'Within 6 months',
                            '396541' => 'After 6 months'
                        )
                    ),
                    'q_157190' => array(
                        'question_id' => 157190,
                        'label' => 'Primary, Secondary, Investment',
                        'type' => 'select',
                        'required' => false,
                        'choices' => array(
                            '396632' => 'Primary',
                            '396631' => 'Secondary',
                            '396630' => 'Investment'
                        )
                    )
                )
            ),

            // Current Situation
            'current_situation' => array(
                'title' => 'Current Situation',
                'fields' => array(
                    'q_157186' => array(
                        'question_id' => 157186,
                        'label' => 'Where are you moving from?',
                        'type' => 'multiselect',
                        'required' => false,
                        'choices' => array(
                            '396568' => 'Inside Highway 285 perimeter',
                            '396567' => 'Outside Highway 285 perimeter',
                            '396580' => 'Alabama',
                            '396579' => 'Alaska',
                            '396578' => 'Arizona',
                            '396577' => 'Arkansas',
                            '396576' => 'California',
                            '396575' => 'Colorado',
                            '396574' => 'Connecticut',
                            '396573' => 'Delaware',
                            '396572' => 'District of Columbia',
                            '396571' => 'Florida',
                            '396570' => 'Hawaii',
                            '396569' => 'Idaho',
                            '396592' => 'Illinois',
                            '396591' => 'Indiana',
                            '396590' => 'Iowa',
                            '396589' => 'Kansas',
                            '396588' => 'Kentucky',
                            '396587' => 'Louisiana',
                            '396586' => 'Maine',
                            '396585' => 'Maryland',
                            '396584' => 'Massachusetts',
                            '396583' => 'Michigan',
                            '396582' => 'Mississippi',
                            '396581' => 'Missouri',
                            '396607' => 'Nebraska',
                            '396606' => 'Nevada',
                            '396605' => 'New Hampshire',
                            '396604' => 'New Jersey',
                            '396603' => 'New York',
                            '396602' => 'North Carolina',
                            '396601' => 'North Dakota',
                            '396600' => 'Ohio',
                            '396599' => 'Oklahoma',
                            '396598' => 'Oregon',
                            '396597' => 'Pennsylvania',
                            '396596' => 'Rhode Island',
                            '396595' => 'South Carolina',
                            '396594' => 'South Dakota',
                            '396593' => 'Tennessee',
                            '396616' => 'Texas',
                            '396615' => 'Utah',
                            '396614' => 'Vermont',
                            '396613' => 'Virginia',
                            '396612' => 'Washington',
                            '396611' => 'West Virginia',
                            '396610' => 'Wisconsin',
                            '396609' => 'Wyoming',
                            '396608' => 'International'
                        )
                    ),
                    'q_157187' => array(
                        'question_id' => 157187,
                        'label' => 'Moving From (Dwelling Type)',
                        'type' => 'select',
                        'required' => false,
                        'choices' => array(
                            '396620' => 'Single-family home',
                            '396619' => 'Rental',
                            '396618' => 'Townhome',
                            '396617' => 'Condo'
                        )
                    ),
                    'q_157188' => array(
                        'question_id' => 157188,
                        'label' => 'Home to Sell',
                        'type' => 'select',
                        'required' => false,
                        'choices' => array(
                            '396623' => 'No',
                            '396622' => 'Yes - Not Listed',
                            '396621' => 'Yes - Listed'
                        )
                    ),
                    'q_157191' => array(
                        'question_id' => 157191,
                        'label' => 'Current Neighborhood',
                        'type' => 'select',
                        'required' => false,
                        'choices' => array(
                            '396671' => 'Acworth',
                            '396670' => 'Ansley Park',
                            '396669' => 'Atlantic Station',
                            '396668' => 'Avondale Estates',
                            '396667' => 'Brookhaven',
                            '396666' => 'Buckhead',
                            '396665' => 'Buckhead North',
                            '396664' => 'Buckhead South',
                            '396663' => 'Castleberry Hill',
                            '396662' => 'Chamblee',
                            '396661' => 'Chastain Park',
                            '396660' => 'College Park',
                            '396659' => 'Conyers',
                            '396658' => 'Cumming',
                            '396657' => 'Decatur',
                            '396656' => 'Doraville',
                            '396655' => 'Douglasville',
                            '396654' => 'Druid Hills',
                            '396653' => 'Duluth',
                            '396652' => 'Dunwoody',
                            '396651' => 'East Atlanta',
                            '396650' => 'East Cobb',
                            '396649' => 'Fairburn',
                            '396648' => 'Gainesville',
                            '396647' => 'Grant Park',
                            '396646' => 'Kennesaw',
                            '396645' => 'Kirkwood',
                            '396644' => 'Lawrenceville',
                            '396643' => 'Lithia Springs',
                            '396642' => 'Mableton',
                            '396641' => 'Marietta',
                            '396640' => 'Midtown',
                            '396639' => 'Monroe',
                            '396638' => 'Morningside',
                            '396637' => 'Norcross',
                            '396636' => 'Northwest Atlanta',
                            '396635' => 'Old Fourth Ward',
                            '396634' => 'Oakhurst',
                            '396633' => 'Peachtree City',
                            '396685' => 'Roswell',
                            '396684' => 'Sandy Springs',
                            '396683' => 'Scottdale',
                            '396682' => 'Smyrna',
                            '396681' => 'South Fulton',
                            '396680' => 'Southwest Atlanta',
                            '396679' => 'Stockbridge',
                            '396678' => 'Stone Mountain',
                            '396677' => 'Tucker',
                            '396676' => 'Union City',
                            '396675' => 'Vinings',
                            '396674' => 'Virginia Highlands',
                            '396673' => 'West Midtown',
                            '396672' => 'Woodstock'
                        )
                    )
                )
            ),

            // Financial Information
            'financial' => array(
                'title' => 'Financial Information',
                'fields' => array(
                    'q_157184' => array(
                        'question_id' => 157184,
                        'label' => 'Income',
                        'type' => 'select',
                        'required' => false,
                        'choices' => array(
                            '396559' => 'Under $50,000',
                            '396558' => '$50,000 - $100,000',
                            '396557' => '$100,000 - $200,000',
                            '396556' => '$200,000 - $300,000',
                            '396555' => 'Over $300,000'
                        )
                    )
                )
            ),

            // Real Estate Professional
            'real_estate_pro' => array(
                'title' => 'Real Estate Professional Information',
                'fields' => array(
                    'q_156877' => array(
                        'question_id' => 156877,
                        'label' => 'Are you an Agent?',
                        'type' => 'checkbox',
                        'required' => false,
                        'choices' => array(
                            '395864' => 'Yes',
                            '395863' => 'No'
                        )
                    ),
                    'q_157183' => array(
                        'question_id' => 157183,
                        'label' => 'Are you Currently Working with an Agent?',
                        'type' => 'checkbox',
                        'required' => false,
                        'choices' => array(
                            '396554' => 'Yes',
                            '396553' => 'No'
                        )
                    ),
                    'q_156727' => array(
                        'question_id' => 156727,
                        'label' => 'Agent Name',
                        'type' => 'text',
                        'required' => false
                    ),
                    'q_156728' => array(
                        'question_id' => 156728,
                        'label' => 'Agent Phone Number',
                        'type' => 'phone',
                        'required' => false
                    ),
                    'q_156729' => array(
                        'question_id' => 156729,
                        'label' => 'Agent Email',
                        'type' => 'email',
                        'required' => false
                    ),
                    'q_156730' => array(
                        'question_id' => 156730,
                        'label' => 'Agent Brokerage',
                        'type' => 'text',
                        'required' => false
                    )
                )
            ),

            // Marketing & Lead Source
            'marketing' => array(
                'title' => 'Marketing & Lead Source',
                'fields' => array(
                    'q_157177' => array(
                        'question_id' => 157177,
                        'label' => 'Where did you hear about us?',
                        'type' => 'select',
                        'required' => false,
                        'choices' => array(
                            '396528' => 'Magazine',
                            '396527' => 'Walk/Drive By',
                            '396526' => 'Friend/Family',
                            '396525' => 'Referral',
                            '396524' => 'Google Search',
                            '396523' => 'Word of Mouth',
                            '396522' => 'Real Estate Agent',
                            '396521' => 'Site Signage',
                            '396520' => 'Social Media',
                            '396519' => 'Print - Atlanta Business Chronicle',
                            '396518' => 'Print - KNOW Atlanta',
                            '396517' => 'Print - Atlanta Home',
                            '396516' => 'Print - Atlanta Intown',
                            '396515' => 'Print - Atlanta Magazine',
                            '396514' => 'Digital - Atlanta Magazine',
                            '396513' => 'Digital - Atlanta Business Chronicle',
                            '396512' => 'Zillow'
                        )
                    ),
                    'q_157185' => array(
                        'question_id' => 157185,
                        'label' => 'Reason for Move',
                        'type' => 'select',
                        'required' => false,
                        'choices' => array(
                            '396566' => 'Interested in Project',
                            '396565' => 'Family Change',
                            '396564' => 'Need Larger Home',
                            '396563' => 'First Home',
                            '396562' => 'Downsizing',
                            '396561' => 'Neighborhood Change',
                            '396560' => 'Job Relocation'
                        )
                    )
                )
            ),

            // Project & Sales
            'project_sales' => array(
                'title' => 'Project & Sales Information',
                'fields' => array(
                    'q_156759' => array(
                        'question_id' => 156759,
                        'label' => 'Which Projects are you interested in?',
                        'type' => 'multiselect',
                        'required' => false,
                        'choices' => array(
                            '395601' => 'Dawson Corner',
                            '395600' => 'Swanns Bridge',
                            '396258' => 'Dickson Place'
                        )
                    ),
                    'q_156726' => array(
                        'question_id' => 156726,
                        'label' => 'Sales Reps',
                        'type' => 'multiselect',
                        'required' => false,
                        'choices' => array(
                            '395535' => 'Susie Proffitt',
                            '395534' => 'Sam Morgan',
                            '395533' => 'Christa Huffstickler',
                            '395537' => 'Sean Mautone',
                            '395536' => 'Ginger Bondurant',
                            '395595' => 'Katy Kosari',
                            '395594' => 'David Hollingshead',
                            '395593' => 'Manager'
                        )
                    )
                )
            ),

            // Additional Fields
            'additional' => array(
                'title' => 'Additional Information',
                'fields' => array(
                    'q_156918' => array(
                        'question_id' => 156918,
                        'label' => 'Would you like to request an appointment?',
                        'type' => 'checkbox',
                        'required' => false,
                        'choices' => array(
                            '396120' => 'Yes',
                            '396119' => 'No'
                        )
                    ),
                    'message' => array(
                        'label' => 'Comments/Message',
                        'type' => 'textarea',
                        'required' => false,
                        'lasso_field' => 'message'
                    )
                )
            )
        );
    }

    /**
     * Add admin menu for form generator
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=lasso_project',
            'Form Generator',
            'Form Generator',
            'manage_options',
            'lasso-form-generator',
            array( $this, 'render_admin_page' )
        );
    }

    /**
     * Render the admin page
     */
    public function render_admin_page() {
        $questions_db = $this->get_lasso_questions_database();
        ?>
        <div class="wrap">
            <h1>? Lasso Dynamic Form Generator</h1>
            <p class="description">Create custom Gravity Forms with any combination of Lasso CRM questions. Perfect for different projects and client needs!</p>
            
            <div class="lasso-form-generator-container">
                <div class="lasso-generator-sidebar">
                    <div class="lasso-form-config">
                        <h3>Form Configuration</h3>
                        <div class="form-group">
                            <label for="form-title">Form Title</label>
                            <input type="text" id="form-title" placeholder="e.g., Luxury Condo Registration" value="Dynamic Registration Form">
                        </div>
                        <div class="form-group">
                            <label for="form-description">Form Description</label>
                            <textarea id="form-description" placeholder="Brief description of the form purpose...">Custom registration form generated with Lasso Leader.</textarea>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="enable-lasso-integration" checked> 
                                Enable Lasso Integration
                            </label>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="enable-debugging" checked> 
                                Enable Debugging & Notes
                            </label>
                        </div>
                    </div>

                    <div class="lasso-form-actions">
                        <button type="button" class="button button-primary button-large" id="generate-form-btn">
                            ? Generate Gravity Form
                        </button>
                        <button type="button" class="button button-secondary" id="preview-form-btn">
                            ?? Preview Form
                        </button>
                        <button type="button" class="button" id="export-config-btn">
                            ? Export Configuration
                        </button>
                    </div>
                </div>

                <div class="lasso-generator-main">
                    <div class="lasso-question-categories">
                        <?php foreach ( $questions_db as $category_key => $category ) : ?>
                        <div class="category-section" data-category="<?php echo esc_attr($category_key); ?>">
                            <div class="category-header">
                                <h3>
                                    <input type="checkbox" class="category-toggle" id="cat-<?php echo esc_attr($category_key); ?>">
                                    <label for="cat-<?php echo esc_attr($category_key); ?>"><?php echo esc_html($category['title']); ?></label>
                                    <span class="category-count">(<?php echo count($category['fields']); ?> fields)</span>
                                </h3>
                            </div>
                            <div class="category-fields" style="display: none;">
                                <?php foreach ( $category['fields'] as $field_key => $field ) : ?>
                                <div class="field-option" data-field="<?php echo esc_attr($field_key); ?>">
                                    <label class="field-label">
                                        <input type="checkbox" class="field-checkbox" name="selected_fields[]" value="<?php echo esc_attr($field_key); ?>">
                                        <span class="field-title"><?php echo esc_html($field['label']); ?></span>
                                        <span class="field-type"><?php echo esc_html(ucfirst($field['type'])); ?></span>
                                        <?php if (isset($field['required']) && $field['required']) : ?>
                                            <span class="field-required">Required</span>
                                        <?php endif; ?>
                                    </label>
                                    <?php if (isset($field['choices']) && count($field['choices']) > 3) : ?>
                                        <div class="field-preview">
                                            <small><?php echo count($field['choices']); ?> choices available</small>
                                        </div>
                                    <?php elseif (isset($field['choices'])) : ?>
                                        <div class="field-preview">
                                            <small>Choices: <?php echo implode(', ', array_slice(array_values($field['choices']), 0, 3)); ?><?php echo count($field['choices']) > 3 ? '...' : ''; ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div id="form-preview-modal" class="lasso-modal" style="display: none;">
                <div class="lasso-modal-content">
                    <div class="lasso-modal-header">
                        <h2>Form Preview</h2>
                        <span class="lasso-modal-close">&times;</span>
                    </div>
                    <div class="lasso-modal-body">
                        <div id="form-preview-content"></div>
                    </div>
                </div>
            </div>

            <div id="generation-status" class="notice" style="display: none;"></div>
        </div>

        <style>
        .lasso-form-generator-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        
        .lasso-generator-sidebar {
            width: 300px;
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .lasso-generator-main {
            flex: 1;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .lasso-form-config h3 {
            margin-top: 0;
            color: #0073aa;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        .lasso-form-actions {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .lasso-form-actions .button {
            display: block;
            width: 100%;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .category-section {
            border-bottom: 1px solid #eee;
        }
        
        .category-header {
            padding: 15px 20px;
            background: #f9f9f9;
            cursor: pointer;
            user-select: none;
        }
        
        .category-header:hover {
            background: #f0f0f0;
        }
        
        .category-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .category-count {
            color: #666;
            font-size: 14px;
            font-weight: normal;
        }
        
        .category-fields {
            padding: 10px 20px;
        }
        
        .field-option {
            padding: 10px;
            border: 1px solid #eee;
            margin-bottom: 8px;
            border-radius: 3px;
            transition: all 0.2s;
        }
        
        .field-option:hover {
            background: #f9f9f9;
            border-color: #0073aa;
        }
        
        .field-option.selected {
            background: #e7f3ff;
            border-color: #0073aa;
        }
        
        .field-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            margin: 0;
        }
        
        .field-title {
            flex: 1;
            font-weight: 500;
        }
        
        .field-type {
            background: #0073aa;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .field-required {
            background: #d63638;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
        }
        
        .field-preview {
            margin-top: 5px;
            padding-left: 30px;
            color: #666;
            font-size: 13px;
        }
        
        .lasso-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 999999;
        }
        
        .lasso-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            width: 80%;
            max-width: 800px;
            max-height: 80%;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .lasso-modal-header {
            padding: 20px;
            background: #0073aa;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .lasso-modal-close {
            cursor: pointer;
            font-size: 24px;
        }
        
        .lasso-modal-body {
            padding: 20px;
            max-height: 60vh;
            overflow-y: auto;
        }
        
        .selected-fields-count {
            background: #0073aa;
            color: white;
            padding: 10px;
            text-align: center;
            font-weight: bold;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            let selectedFields = [];
            
            // Category toggle functionality
            $('.category-header').on('click', function() {
                const $fields = $(this).siblings('.category-fields');
                const $checkbox = $(this).find('.category-toggle');
                
                $fields.slideToggle();
                $checkbox.prop('checked', $fields.is(':visible'));
            });
            
            // Category checkbox toggle all fields in category
            $('.category-toggle').on('change', function(e) {
                e.stopPropagation();
                const isChecked = $(this).is(':checked');
                const $categoryFields = $(this).closest('.category-section').find('.field-checkbox');
                
                $categoryFields.prop('checked', isChecked);
                updateSelectedFields();
            });
            
            // Individual field selection
            $('.field-checkbox').on('change', function() {
                const $fieldOption = $(this).closest('.field-option');
                
                if ($(this).is(':checked')) {
                    $fieldOption.addClass('selected');
                } else {
                    $fieldOption.removeClass('selected');
                }
                
                updateSelectedFields();
                updateCategoryCheckboxes();
            });
            
            // Update selected fields array
            function updateSelectedFields() {
                selectedFields = [];
                $('.field-checkbox:checked').each(function() {
                    selectedFields.push($(this).val());
                });
                
                updateSelectedCount();
            }
            
            // Update category checkboxes based on field selections
            function updateCategoryCheckboxes() {
                $('.category-section').each(function() {
                    const $category = $(this);
                    const $categoryCheckbox = $category.find('.category-toggle');
                    const $fieldCheckboxes = $category.find('.field-checkbox');
                    const checkedCount = $fieldCheckboxes.filter(':checked').length;
                    const totalCount = $fieldCheckboxes.length;
                    
                    if (checkedCount === 0) {
                        $categoryCheckbox.prop('checked', false);
                        $categoryCheckbox.prop('indeterminate', false);
                    } else if (checkedCount === totalCount) {
                        $categoryCheckbox.prop('checked', true);
                        $categoryCheckbox.prop('indeterminate', false);
                    } else {
                        $categoryCheckbox.prop('checked', false);
                        $categoryCheckbox.prop('indeterminate', true);
                    }
                });
            }
            
            // Update selected count display
            function updateSelectedCount() {
                let $countDisplay = $('.selected-fields-count');
                if ($countDisplay.length === 0) {
                    $countDisplay = $('<div class="selected-fields-count"></div>');
                    $('.lasso-generator-main').prepend($countDisplay);
                }
                
                if (selectedFields.length > 0) {
                    $countDisplay.text(`${selectedFields.length} fields selected for form generation`).show();
                } else {
                    $countDisplay.hide();
                }
            }
            
            // Generate form button
            $('#generate-form-btn').on('click', function() {
                if (selectedFields.length === 0) {
                    alert('Please select at least one field to generate a form.');
                    return;
                }
                
                const formConfig = {
                    title: $('#form-title').val(),
                    description: $('#form-description').val(),
                    enableLassoIntegration: $('#enable-lasso-integration').is(':checked'),
                    enableDebugging: $('#enable-debugging').is(':checked'),
                    selectedFields: selectedFields
                };
                
                $(this).prop('disabled', true).text('? Generating...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'lasso_generate_form',
                        config: formConfig,
                        nonce: '<?php echo wp_create_nonce("lasso_generate_form"); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            showStatus('success', `? Form "${formConfig.title}" created successfully! <a href="${response.data.edit_url}">Edit Form</a> | <a href="${response.data.preview_url}">Preview</a>`);
                        } else {
                            showStatus('error', '? Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        showStatus('error', '? Ajax error occurred');
                    },
                    complete: function() {
                        $('#generate-form-btn').prop('disabled', false).text('? Generate Gravity Form');
                    }
                });
            });
            
            // Preview form button
            $('#preview-form-btn').on('click', function() {
                if (selectedFields.length === 0) {
                    alert('Please select at least one field to preview.');
                    return;
                }
                
                // Generate preview HTML
                let previewHTML = '<h3>Form Preview</h3>';
                previewHTML += '<div class="form-preview">';
                
                selectedFields.forEach(function(fieldKey) {
                    const $fieldOption = $(`.field-option[data-field="${fieldKey}"]`);
                    const fieldTitle = $fieldOption.find('.field-title').text();
                    const fieldType = $fieldOption.find('.field-type').text().toLowerCase();
                    
                    previewHTML += `<div class="preview-field">`;
                    previewHTML += `<label><strong>${fieldTitle}</strong></label>`;
                    
                    switch(fieldType) {
                        case 'text':
                        case 'email':
                        case 'phone':
                            previewHTML += `<input type="${fieldType}" placeholder="Enter ${fieldTitle.toLowerCase()}" disabled>`;
                            break;
                        case 'textarea':
                            previewHTML += `<textarea placeholder="Enter ${fieldTitle.toLowerCase()}" disabled></textarea>`;
                            break;
                        case 'select':
                            previewHTML += `<select disabled><option>Select ${fieldTitle}</option></select>`;
                            break;
                        case 'checkbox':
                        case 'multiselect':
                            previewHTML += `<div class="checkbox-group"><label><input type="checkbox" disabled> Option 1</label></div>`;
                            break;
                    }
                    previewHTML += `</div>`;
                });
                
                previewHTML += '</div>';
                previewHTML += '<style>.form-preview .preview-field { margin-bottom: 15px; } .form-preview input, .form-preview select, .form-preview textarea { width: 100%; padding: 8px; margin-top: 5px; }</style>';
                
                $('#form-preview-content').html(previewHTML);
                $('#form-preview-modal').show();
            });
            
            // Close modal
            $('.lasso-modal-close, .lasso-modal').on('click', function(e) {
                if (e.target === this) {
                    $('#form-preview-modal').hide();
                }
            });
            
            // Export configuration
            $('#export-config-btn').on('click', function() {
                const config = {
                    title: $('#form-title').val(),
                    description: $('#form-description').val(),
                    enableLassoIntegration: $('#enable-lasso-integration').is(':checked'),
                    enableDebugging: $('#enable-debugging').is(':checked'),
                    selectedFields: selectedFields,
                    timestamp: new Date().toISOString()
                };
                
                const blob = new Blob([JSON.stringify(config, null, 2)], {type: 'application/json'});
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `lasso-form-config-${Date.now()}.json`;
                a.click();
                URL.revokeObjectURL(url);
            });
            
            // Show status messages
            function showStatus(type, message) {
                const $status = $('#generation-status');
                $status.removeClass('notice-success notice-error notice-warning')
                       .addClass('notice-' + type)
                       .html(message)
                       .show();
                
                setTimeout(function() {
                    $status.fadeOut();
                }, 5000);
            }
            
            // Quick selection templates
            const templates = {
                'basic-contact': ['firstName', 'lastName', 'email', 'phone'],
                'full-onsite': ['firstName', 'lastName', 'email', 'phone', 'q_157502', 'q_157177', 'q_157178', 'q_157179', 'q_157180', 'q_157181', 'q_157182', 'q_157183', 'q_157184', 'q_157185', 'q_157186', 'q_157187', 'q_157188', 'q_157189', 'q_157190', 'q_157191'],
                'luxury-focused': ['firstName', 'lastName', 'email', 'phone', 'q_157178', 'q_157179', 'q_157180', 'q_157190', 'q_157191', 'q_157183', 'q_157184'],
                'agent-focused': ['firstName', 'lastName', 'email', 'phone', 'q_156877', 'q_157183', 'q_156727', 'q_156728', 'q_156729', 'q_156730']
            };
            
            // Add template buttons
            const $templateContainer = $('<div class="template-buttons" style="margin-bottom: 20px; padding: 15px; background: #f0f8ff; border-radius: 5px;"><h4 style="margin-top: 0;">Quick Templates</h4></div>');
            
            Object.keys(templates).forEach(function(templateKey) {
                const templateName = templateKey.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase());
                const $btn = $(`<button type="button" class="button template-btn" data-template="${templateKey}" style="margin-right: 5px; margin-bottom: 5px;">${templateName}</button>`);
                $templateContainer.append($btn);
            });
            
            $('.lasso-question-categories').before($templateContainer);
            
            // Template button functionality
            $('.template-btn').on('click', function() {
                const templateKey = $(this).data('template');
                const templateFields = templates[templateKey];
                
                // Clear all selections
                $('.field-checkbox').prop('checked', false);
                $('.field-option').removeClass('selected');
                
                // Select template fields
                templateFields.forEach(function(fieldKey) {
                    const $checkbox = $(`.field-checkbox[value="${fieldKey}"]`);
                    $checkbox.prop('checked', true);
                    $checkbox.closest('.field-option').addClass('selected');
                });
                
                updateSelectedFields();
                updateCategoryCheckboxes();
                
                // Show relevant categories
                templateFields.forEach(function(fieldKey) {
                    const $fieldOption = $(`.field-checkbox[value="${fieldKey}"]`).closest('.field-option');
                    const $categoryFields = $fieldOption.closest('.category-fields');
                    if (!$categoryFields.is(':visible')) {
                        $categoryFields.show();
                        $categoryFields.siblings('.category-header').find('.category-toggle').prop('checked', true);
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX handler for form generation
     */
    public function ajax_generate_form() {
        check_ajax_referer('lasso_generate_form', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $config = $_POST['config'];
        $selected_fields = $config['selectedFields'];
        
        if (empty($selected_fields)) {
            wp_send_json_error('No fields selected');
        }
        
        // Create new Gravity Form
        $form_id = $this->create_gravity_form($config);
        
        if ($form_id) {
            wp_send_json_success(array(
                'form_id' => $form_id,
                'edit_url' => admin_url('admin.php?page=gf_edit_forms&id=' . $form_id),
                'preview_url' => admin_url('admin.php?page=gf_entries&view=entry&id=' . $form_id)
            ));
        } else {
            wp_send_json_error('Failed to create form');
        }
    }

    /**
     * Create Gravity Form with selected fields
     */
    private function create_gravity_form($config) {
        if (!class_exists('GFAPI')) {
            return false;
        }
        
        $questions_db = $this->get_lasso_questions_database();
        $form_fields = array();
        $field_id = 1;
        
        // Build form structure
        $form = array(
            'title' => sanitize_text_field($config['title']),
            'description' => sanitize_textarea_field($config['description']),
            'fields' => array(),
            'button' => array('text' => 'Submit'),
            'confirmations' => array(
                '1' => array(
                    'id' => '1',
                    'name' => 'Default Confirmation',
                    'type' => 'message',
                    'message' => 'Thank you for your submission! We will contact you soon.',
                    'isDefault' => true
                )
            )
        );
        
        // Add selected fields to form
        foreach ($config['selectedFields'] as $field_key) {
            $field_config = $this->find_field_in_database($field_key, $questions_db);
            
            if ($field_config) {
                $gravity_field = $this->convert_to_gravity_field($field_config, $field_id);
                if ($gravity_field) {
                    $form['fields'][] = $gravity_field;
                    $field_id++;
                }
            }
        }
        
        // Create the form
        $form_id = GFAPI::add_form($form);
        
        if (!is_wp_error($form_id)) {
            // Configure Lasso Leader settings if enabled
            if ($config['enableLassoIntegration']) {
                $this->configure_lasso_integration($form_id, $config);
            }
            
            return $form_id;
        }
        
        return false;
    }

    /**
     * Find field configuration in database
     */
    private function find_field_in_database($field_key, $questions_db) {
        foreach ($questions_db as $category) {
            if (isset($category['fields'][$field_key])) {
                $field = $category['fields'][$field_key];
                $field['key'] = $field_key;
                return $field;
            }
        }
        return false;
    }

    /**
     * Convert Lasso field to Gravity Forms field
     */
    private function convert_to_gravity_field($field_config, $field_id) {
        $base_field = array(
            'id' => $field_id,
            'label' => $field_config['label'],
            'isRequired' => isset($field_config['required']) ? $field_config['required'] : false
        );
        
        switch ($field_config['type']) {
            case 'text':
                return array_merge($base_field, array('type' => 'text'));
                
            case 'email':
                return array_merge($base_field, array('type' => 'email'));
                
            case 'phone':
                return array_merge($base_field, array('type' => 'phone'));
                
            case 'textarea':
                return array_merge($base_field, array('type' => 'textarea'));
                
            case 'select':
                $choices = array();
                if (isset($field_config['choices'])) {
                    foreach ($field_config['choices'] as $value => $text) {
                        $choices[] = array('text' => $text, 'value' => $value);
                    }
                }
                return array_merge($base_field, array(
                    'type' => 'select',
                    'choices' => $choices
                ));
                
            case 'multiselect':
                $choices = array();
                if (isset($field_config['choices'])) {
                    foreach ($field_config['choices'] as $value => $text) {
                        $choices[] = array('text' => $text, 'value' => $value);
                    }
                }
                return array_merge($base_field, array(
                    'type' => 'checkbox',
                    'choices' => $choices
                ));
                
            case 'checkbox':
                $choices = array();
                if (isset($field_config['choices'])) {
                    foreach ($field_config['choices'] as $value => $text) {
                        $choices[] = array('text' => $text, 'value' => $value);
                    }
                }
                return array_merge($base_field, array(
                    'type' => 'checkbox',
                    'choices' => $choices
                ));
        }
        
        return false;
    }

    /**
     * Configure Lasso Leader integration for the form
     */
    private function configure_lasso_integration($form_id, $config) {
        $questions_db = $this->get_lasso_questions_database();
        $field_mappings = array();
        $field_id = 1;
        
        // Build field mappings
        foreach ($config['selectedFields'] as $field_key) {
            $field_config = $this->find_field_in_database($field_key, $questions_db);
            
            if ($field_config) {
                if (isset($field_config['lasso_field'])) {
                    // Standard field mapping
                    $field_mappings[$field_id] = $field_config['lasso_field'];
                } elseif (isset($field_config['question_id'])) {
                    // Question field mapping
                    $field_mappings[$field_id] = 'q_' . $field_config['question_id'];
                }
                $field_id++;
            }
        }
        
        // Save Lasso Leader settings for this form using WordPress options
        $form_settings_key = 'gravityformsaddon_lassoleadergf_settings_' . $form_id;
        $lasso_settings = array(
            'enable_integration' => 1,
            'field_mapping' => $field_mappings,
            'enable_debugging' => $config['enableDebugging'] ? 1 : 0,
            'debug_email' => get_option('admin_email')
        );
        
        // Save settings directly to WordPress options
        update_option($form_settings_key, $lasso_settings);
        
        // Also save in GF meta for compatibility
        gform_update_meta($form_id, 'lasso_leader_settings', $lasso_settings);
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'lasso-form-generator') !== false) {
            wp_enqueue_script('jquery');
        }
    }
}