<?php
/**
 * Lasso Leader: On-Site Registration Module
 * FINAL COMPLETE VERSION - Answer ID URL System + EV Atlanta Branding + project_name support
 * Uses Lasso Answer IDs in URLs while preserving API integration
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Lasso_OnSite_Registration {

    public function init() {
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_custom_meta_boxes' ) );
        add_action( 'save_post_lasso_project', array( $this, 'save_project_meta_data' ) );
        add_action( 'save_post_lasso_agent', array( $this, 'save_agent_meta_data' ) );
        add_shortcode( 'lasso_onsite_directory', array( $this, 'render_directory_shortcode' ) );
        add_shortcode( 'lasso_registration_status', array( $this, 'render_registration_status_shortcode' ) );
        add_shortcode( 'lasso_registration_check', array( $this, 'registration_check_shortcode' ) );
        add_filter( 'the_content', array( $this, 'append_agents_to_project_content' ) );
        
        // Auto-inject registration status at top of registration pages
        add_filter( 'the_content', array( $this, 'auto_inject_registration_status' ), 1 );
        
        // Form hiding functionality
        add_filter( 'the_content', array( $this, 'maybe_hide_registration_form' ), 15 );
        
        // Fix for agent post display issues
        add_filter( 'template_include', array( $this, 'custom_post_template' ) );
        add_action( 'wp_head', array( $this, 'add_custom_post_styles' ) );
        
        // Ensure proper content display for custom post types
        add_filter( 'the_content', array( $this, 'ensure_agent_content_display' ), 5 );
    }

    /**
     * Auto-inject registration status at the top of registration pages
     */
    public function auto_inject_registration_status( $content ) {
        // Only run on pages (not posts, projects, etc.)
        if ( ! is_page() || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }
        
        // Check if this is a registration page by looking for URL parameters
        $project_answer_id = isset($_GET['project']) ? sanitize_text_field($_GET['project']) : null;
        $project_id = isset($_GET['project_id']) ? sanitize_text_field($_GET['project_id']) : null; // Fallback
        $agent_name = isset($_GET['agent_name']) ? sanitize_text_field(urldecode($_GET['agent_name'])) : null;
        
        // If we have registration parameters OR we're on a registration page, inject the status
        $page_slug = get_post_field( 'post_name', get_post() );
        if ( $project_answer_id || $project_id || $agent_name || in_array( $page_slug, array( 'registration', 'register', 'signup', 'contact' ) ) ) {
            $registration_status = $this->render_registration_status_shortcode( array() );
            
            // Add the registration status at the very beginning of content
            $content = $registration_status . $content;
        }
        
        return $content;
    }

    /**
     * Fix agent post template issues by ensuring proper template loading
     */
    public function custom_post_template( $template ) {
        if ( is_singular( array( 'lasso_project', 'lasso_agent' ) ) ) {
            // Check if theme has specific templates
            $post_type = get_post_type();
            $theme_template = locate_template( array( 
                "single-{$post_type}.php", 
                'single.php', 
                'index.php' 
            ) );
            
            if ( $theme_template ) {
                return $theme_template;
            }
            
            // Fallback to plugin template if theme doesn't have one
            $plugin_template = LASSO_LEADER_PATH . "templates/single-{$post_type}.php";
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }
        
        return $template;
    }

    /**
     * Add enhanced styling for custom post types with EV branding
     */
    public function add_custom_post_styles() {
        if ( is_singular( array( 'lasso_project', 'lasso_agent' ) ) ) {
            ?>
            <style type="text/css">
            /* Enhanced EV-branded styles for custom post types */
            body.single-lasso_project,
            body.single-lasso_agent {
                background: #f8f9fa;
            }
            
            .lasso-custom-post {
                max-width: 900px;
                margin: 60px auto;
                padding: 40px;
                background: #fff;
                box-shadow: 0 4px 12px rgba(196, 30, 58, 0.1);
                border-radius: 12px;
                border: 1px solid #dee2e6;
            }
            
            .lasso-custom-post h1 {
                color: #C41E3A;
                margin-bottom: 30px;
                padding-bottom: 15px;
                border-bottom: 3px solid #C41E3A;
                font-size: 2.2rem;
                font-weight: 300;
                font-family: Georgia, serif;
            }
            
            .lasso-custom-post .post-meta {
                background: #f8f9fa;
                padding: 25px;
                margin: 30px 0;
                border-left: 5px solid #C41E3A;
                border-radius: 8px;
            }
            
            .lasso-custom-post .post-meta h3 {
                margin-top: 0;
                color: #C41E3A;
                font-size: 1.3rem;
                font-weight: 600;
                margin-bottom: 20px;
            }
            
            .lasso-agent-projects-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            
            .lasso-agent-projects-list li {
                background: #fff;
                margin: 15px 0;
                padding: 20px;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                transition: all 0.3s ease;
            }
            
            .lasso-agent-projects-list li:hover {
                border-color: #C41E3A;
                box-shadow: 0 2px 8px rgba(196, 30, 58, 0.1);
            }
            
            .lasso-agent-projects-list a {
                text-decoration: none;
                color: #C41E3A;
                font-weight: 600;
                font-size: 1.1rem;
                transition: color 0.3s ease;
            }
            
            .lasso-agent-projects-list a:hover {
                color: #A61729;
            }
            
            .agent-featured-image {
                float: left;
                margin: 0 25px 25px 0;
            }
            
            .agent-featured-image img {
                border-radius: 12px;
                border: 3px solid #e9ecef;
                transition: border-color 0.3s ease;
            }
            
            .agent-content {
                line-height: 1.7;
                color: #495057;
            }
            
            @media (max-width: 768px) {
                .lasso-custom-post {
                    margin: 30px auto;
                    padding: 25px;
                }
                
                .agent-featured-image {
                    float: none;
                    text-align: center;
                    margin: 0 0 25px 0;
                }
            }
            </style>
            <?php
        }
    }

    /**
     * Ensure agent content displays properly and isn't filtered out
     */
    public function ensure_agent_content_display( $content ) {
        if ( is_singular( 'lasso_agent' ) && in_the_loop() && is_main_query() ) {
            global $post;
            
            // Build the agent display content
            $agent_content = '<div class="lasso-custom-post lasso-agent-post">';
            
            // Display featured image if available
            if ( has_post_thumbnail( $post->ID ) ) {
                $agent_content .= '<div class="agent-featured-image">';
                $agent_content .= get_the_post_thumbnail( $post->ID, 'medium' );
                $agent_content .= '</div>';
            }
            
            // Display the actual post content
            $agent_content .= '<div class="agent-content">';
            $agent_content .= apply_filters( 'the_content', $post->post_content );
            $agent_content .= '</div>';
            
            // Clear floats
            $agent_content .= '<div style="clear: both;"></div>';
            
            // Display associated projects
            $associated_projects = get_post_meta( $post->ID, '_lasso_associated_projects', true );
            if ( ! empty( $associated_projects ) && is_array( $associated_projects ) ) {
                $agent_content .= '<div class="post-meta">';
                $agent_content .= '<h3>' . esc_html__( 'Associated Projects', 'lasso-leader' ) . '</h3>';
                $agent_content .= '<ul class="lasso-agent-projects-list">';
                
                foreach ( $associated_projects as $project_id ) {
                    $project = get_post( $project_id );
                    if ( $project && $project->post_status === 'publish' ) {
                        $agent_content .= '<li>';
                        $agent_content .= '<a href="' . esc_url( get_permalink( $project_id ) ) . '">';
                        $agent_content .= esc_html( $project->post_title );
                        $agent_content .= '</a>';
                        if ( $project->post_excerpt ) {
                            $agent_content .= '<p>' . esc_html( $project->post_excerpt ) . '</p>';
                        }
                        $agent_content .= '</li>';
                    }
                }
                $agent_content .= '</ul>';
                $agent_content .= '</div>';
            }
            
            $agent_content .= '</div>';
            
            return $agent_content;
        }
        
        return $content;
    }

    /**
     * FINAL: Render registration status using Lasso Answer IDs in URL
     * Supports both new (project=answer_id) and legacy (project_id) formats
     */
    public function render_registration_status_shortcode( $atts ) {
        // Support both project_id (old) and project (new Answer ID) parameters
        $project_answer_id = isset($_GET['project']) ? sanitize_text_field($_GET['project']) : null;
        $project_id = isset($_GET['project_id']) ? sanitize_text_field($_GET['project_id']) : null; // Fallback support
        $agent_name = isset($_GET['agent_name']) ? sanitize_text_field(urldecode($_GET['agent_name'])) : null;
        $project_name_param = isset($_GET['project_name']) ? sanitize_text_field(urldecode($_GET['project_name'])) : null;
        
        // Handle missing parameters
        if ( ( ! $project_answer_id && ! $project_id ) || ! $agent_name ) { 
            $output = '<div class="lasso-registration-status missing-params">';
            $output .= '<div class="ev-container">';
            $output .= '<div class="registration-status-card">';
            $output .= '<div class="status-header">';
            $output .= '<span class="dashicons dashicons-info status-icon"></span>';
            $output .= '<h4>Registration Setup Required</h4>';
            $output .= '</div>';
            $output .= '<div class="status-message">';
            $output .= '<p>To complete your registration, please select a project and agent first.</p>';
            $output .= '<div class="status-actions">';
            $output .= '<a href="' . esc_url( home_url() ) . '" class="ev-btn ev-btn-primary">';
            $output .= '<span class="dashicons dashicons-arrow-left-alt"></span>';
            $output .= 'Start Registration Process';
            $output .= '</a>';
            $output .= '</div>';
            $output .= '</div>';
            $output .= '</div>';
            $output .= '</div>';
            $output .= '</div>';
            
            global $lasso_registration_incomplete;
            $lasso_registration_incomplete = true;
            return $output;
        }
        
        $project_name = 'Unknown Project';
        
        // PRIORITY 1: Use project_name parameter if provided
        if ( $project_name_param ) {
            $project_name = $project_name_param;
        } else {
            // PRIORITY 2: Map Lasso Answer IDs to project names (from Question 156759)
            if ( $project_answer_id ) {
                $answer_id_to_name = array(
                    '395601' => 'Dawson Corner',
                    '395600' => 'Swanns Bridge', 
                    '396258' => 'Dickson Place',
                    '397189' => '40 West 12th',
                    '397256' => 'Downing Park',
                    '397255' => 'Findley Row',
                    '397254' => 'J5',
                    '397253' => 'Moderns on Memorial',
                    '397252' => 'The Harman'
                );
                
                if ( isset($answer_id_to_name[$project_answer_id]) ) {
                    $project_name = $answer_id_to_name[$project_answer_id];
                } else {
                    // Try to find project by Answer ID in WordPress meta
                    $project_posts = get_posts(array(
                        'post_type' => 'lasso_project', 
                        'posts_per_page' => 1,
                        'post_status' => 'publish',
                        'meta_query' => array( 
                            array( 
                                'key' => '_lasso_answer_id', 
                                'value' => $project_answer_id,
                                'compare' => '='
                            ) 
                        )
                    ));
                    
                    if ( !empty($project_posts) ) {
                        $project_post = $project_posts[0];
                        $lasso_project_name = get_post_meta( $project_post->ID, '_lasso_project_name', true );
                        $project_name = !empty($lasso_project_name) ? $lasso_project_name : get_the_title( $project_post->ID );
                    }
                }
            } else {
                // FALLBACK: Old project_id method (still supported)
                $project_posts = get_posts(array(
                    'post_type' => 'lasso_project', 
                    'posts_per_page' => 1,
                    'post_status' => 'publish',
                    'meta_query' => array( 
                        array( 
                            'key' => '_lasso_project_id', 
                            'value' => $project_id,
                            'compare' => '='
                        ) 
                    )
                ));

                if ( !empty($project_posts) ) {
                    $project_post = $project_posts[0];
                    $lasso_project_name = get_post_meta( $project_post->ID, '_lasso_project_name', true );
                    $project_name = !empty($lasso_project_name) ? $lasso_project_name : get_the_title( $project_post->ID );
                }
            }
        }
        
        // Clear the incomplete flag
        global $lasso_registration_incomplete;
        $lasso_registration_incomplete = false;
        
        // Render the complete registration status
        $output = '<div class="lasso-registration-status complete">';
        $output .= '<div class="ev-container">';
        $output .= '<div class="registration-status-card">';
        $output .= '<div class="status-header">';
        $output .= '<span class="dashicons dashicons-yes-alt status-icon"></span>';
        $output .= '<h4>Registration Details</h4>';
        $output .= '</div>';
        $output .= '<div class="status-info">';
        $output .= '<span class="project-name">Project: ' . esc_html($project_name) . '</span><br>';
        $output .= '<span class="agent-name">Agent: ' . esc_html($agent_name) . '</span>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Helper shortcode for conditional content based on registration status
     */
    public function registration_check_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'show_when' => 'complete', // 'complete' or 'incomplete'
            'message' => '',
        ), $atts );
        
        global $lasso_registration_incomplete;
        $is_incomplete = (bool) $lasso_registration_incomplete;
        
        if ( $atts['show_when'] === 'complete' && ! $is_incomplete ) {
            return $atts['message'];
        } elseif ( $atts['show_when'] === 'incomplete' && $is_incomplete ) {
            return $atts['message'];
        }
        
        return '';
    }

    /**
     * Helper function to check if registration is complete
     */
    public function is_registration_complete() {
        global $lasso_registration_incomplete;
        return ! $lasso_registration_incomplete;
    }

    /**
     * Enhanced form hiding with support for both URL formats
     */
    public function maybe_hide_registration_form( $content ) {
        global $lasso_registration_incomplete;
        
        // Only hide on pages where registration status is incomplete
        if ( $lasso_registration_incomplete && is_page() ) {
            // More comprehensive form detection
            $form_patterns = array(
                'gravityform',
                'contact-form-7',
                'wpcf7',
                'gform',
                'elementor-form',
                '<form',
                'class="gform_wrapper"',
                'class="wpcf7-form"'
            );
            
            $has_form = false;
            foreach ( $form_patterns as $pattern ) {
                if ( stripos( $content, $pattern ) !== false ) {
                    $has_form = true;
                    break;
                }
            }
            
            if ( $has_form ) {
                // Replace all form content with a message
                $replacement = '<div class="lasso-form-hidden-message">';
                $replacement .= '<div class="ev-form">';
                $replacement .= '<div class="form-notice">';
                $replacement .= '<span class="dashicons dashicons-info"></span>';
                $replacement .= '<p>Please complete the registration setup above before accessing the form.</p>';
                $replacement .= '</div>';
                $replacement .= '</div>';
                $replacement .= '</div>';
                
                // Remove form content more aggressively
                $content = preg_replace('/\[gravityform[^\]]*\]/', '', $content);
                $content = preg_replace('/\[contact-form-7[^\]]*\]/', '', $content);
                $content = preg_replace('/\[wpcf7[^\]]*\]/', '', $content);
                $content = preg_replace('/<form[^>]*>.*?<\/form>/is', '', $content);
                
                // Add replacement message
                $content = $replacement . $content;
            }
        }
        
        return $content;
    }

    /**
     * UPDATED: Generate registration URLs using Lasso Answer IDs + project_name parameter
     * Maintains API compatibility by preserving Project ID 25633 for form processing
     */
    public function append_agents_to_project_content( $content ) {
        if ( is_singular( 'lasso_project' ) && in_the_loop() && is_main_query() ) {
            $current_project_id = get_the_ID();
            $associated_agents = array();
            $all_agents = get_posts( array( 'post_type' => 'lasso_agent', 'posts_per_page' => -1 ) );

            if ( ! empty( $all_agents ) ) {
                foreach ( $all_agents as $agent ) {
                    $projects_for_agent = get_post_meta( $agent->ID, '_lasso_associated_projects', true );
                    if ( is_array( $projects_for_agent ) && in_array( $current_project_id, $projects_for_agent ) ) {
                        $associated_agents[] = $agent;
                    }
                }
            }

            if ( ! empty( $associated_agents ) ) {
                $agent_list_html = '<div class="lasso-agent-list">';
                $agent_list_html .= '<h3>' . esc_html__( 'Our Agents', 'lasso_leader' ) . '</h3>';
                $agent_list_html .= '<div class="agents-grid">';
                
                $registration_page_url = get_permalink( get_page_by_path( 'registration' ) );
                
                // Get the Lasso Answer ID for this project
                $lasso_answer_id = get_post_meta( $current_project_id, '_lasso_answer_id', true );
                
                // FALLBACK: If no Answer ID set, use project title to determine Answer ID
                if ( empty($lasso_answer_id) ) {
                    $project_title = get_the_title( $current_project_id );
                    $name_to_answer_id = array(
                        'Dawson Corner' => '395601',
                        'Swanns Bridge' => '395600', 
                        'Dickson Place' => '396258',
                        '40 West 12th' => '397189',
                        'Downing Park' => '397256',
                        'Findley Row' => '397255',
                        'J5' => '397254',
                        'Moderns on Memorial' => '397253',
                        'The Harman' => '397252'
                    );
                    $lasso_answer_id = isset($name_to_answer_id[$project_title]) ? $name_to_answer_id[$project_title] : null;
                }

                foreach ( $associated_agents as $agent ) {
                    $agent_list_html .= '<div class="agent-card">';
                    
                    // Agent photo - make it clickable
                    if ( has_post_thumbnail( $agent->ID ) ) { 
                        $agent_list_html .= '<div class="agent-photo">';
                        
                        // UPDATED: Registration link using Lasso Answer ID + project_name
                        if ( $registration_page_url && $lasso_answer_id ) {
                            $reg_link = add_query_arg( array( 
                                'project' => $lasso_answer_id,  // Use Answer ID instead of project_id
                                'project_name' => urlencode( get_the_title( $current_project_id ) ), // ADD PROJECT NAME
                                'agent_name' => urlencode( $agent->post_title ) 
                            ), $registration_page_url );
                            
                            $agent_list_html .= '<a href="' . esc_url( $reg_link ) . '" aria-label="' . esc_attr( sprintf( __( 'Register with %s', 'lasso_leader' ), $agent->post_title ) ) . '">';
                            $agent_list_html .= get_the_post_thumbnail( $agent->ID, 'thumbnail' );
                            $agent_list_html .= '</a>';
                        } else {
                            $agent_list_html .= get_the_post_thumbnail( $agent->ID, 'thumbnail' );
                        }
                        
                        $agent_list_html .= '</div>';
                    }
                    
                    // Agent details
                    $agent_list_html .= '<div class="agent-details">';
                    $agent_list_html .= '<h4>' . esc_html( $agent->post_title ) . '</h4>';
                    
                    // Add agent title/role if available in excerpt
                    if ( $agent->post_excerpt ) {
                        $agent_list_html .= '<div class="agent-title">' . esc_html( $agent->post_excerpt ) . '</div>';
                    }
                    
                    // Temporarily remove this filter to prevent infinite recursion
                    remove_filter( 'the_content', array( $this, 'append_agents_to_project_content' ) );
                    $agent_content = apply_filters( 'the_content', $agent->post_content );
                    add_filter( 'the_content', array( $this, 'append_agents_to_project_content' ) );
                    
                    $agent_list_html .= '<div class="agent-bio">' . wp_trim_words( $agent_content, 30, '...' ) . '</div>';

                    // UPDATED: Registration button using Lasso Answer ID + project_name
                    if ( $registration_page_url && $lasso_answer_id ) {
                        $reg_link = add_query_arg( array( 
                            'project' => $lasso_answer_id,  // Use Answer ID instead of project_id
                            'project_name' => urlencode( get_the_title( $current_project_id ) ), // ADD PROJECT NAME
                            'agent_name' => urlencode( $agent->post_title ) 
                        ), $registration_page_url );
                        
                        $agent_list_html .= '<a href="' . esc_url( $reg_link ) . '" class="lasso-register-button">';
                        $agent_list_html .= sprintf( esc_html__( 'Register with %s', 'lasso_leader' ), esc_html( $agent->post_title ) );
                        $agent_list_html .= '</a>';
                    }
                    
                    $agent_list_html .= '</div>'; // .agent-details
                    $agent_list_html .= '</div>'; // .agent-card
                }
                
                $agent_list_html .= '</div>'; // .agents-grid
                $agent_list_html .= '</div>'; // .lasso-agent-list
                $content .= $agent_list_html;
            }
        }
        return $content;
    }

    /**
     * Render directory shortcode with enhanced styling
     */
    public function render_directory_shortcode( $atts ) {
        $projects = get_posts( array( 
            'post_type' => 'lasso_project', 
            'posts_per_page' => -1, 
            'orderby' => 'title', 
            'order' => 'ASC',
            'post_status' => 'publish'
        ) );
        
        if ( empty( $projects ) ) { 
            return '<div class="lasso-no-projects">' .
                   '<p>' . esc_html__( 'No projects are currently available.', 'lasso_leader' ) . '</p>' .
                   '</div>';
        }
        
        $output = '<div class="lasso-onsite-directory">';
        $output .= '<h2>' . esc_html__( 'Select a Project', 'lasso_leader' ) . '</h2>';
        $output .= '<div class="lasso-project-grid">';
        
        foreach ( $projects as $project ) {
            $project_url = get_permalink( $project->ID );
            $output .= '<div class="project-card">';
            
            // Project thumbnail - make it clickable
            if ( has_post_thumbnail( $project->ID ) ) {
                $output .= '<div class="project-image">';
                $output .= '<a href="' . esc_url( $project_url ) . '" aria-label="' . esc_attr( sprintf( __( 'View %s project details', 'lasso_leader' ), $project->post_title ) ) . '">';
                $output .= get_the_post_thumbnail( $project->ID, 'medium' );
                $output .= '</a>';
                $output .= '</div>';
            }
            
            $output .= '<div class="project-details">';
            $output .= '<h3>';
            $output .= '<a href="' . esc_url( $project_url ) . '">';
            $output .= esc_html( $project->post_title );
            $output .= '</a></h3>';
            
            if ( $project->post_excerpt ) {
                $output .= '<p>' . esc_html( $project->post_excerpt ) . '</p>';
            }
            
            $output .= '<a href="' . esc_url( $project_url ) . '" class="project-select-button">Select Project</a>';
            $output .= '</div>'; // .project-details
            $output .= '</div>'; // .project-card
        }
        
        $output .= '</div>'; // .lasso-project-grid
        $output .= '</div>'; // .lasso-onsite-directory
        return $output;
    }

    /**
     * Register custom post types
     */
    public function register_post_types() {
        // Register Projects Post Type
        $project_labels = array( 
            'name' => _x( 'Projects', 'Post Type General Name', 'lasso_leader' ), 
            'singular_name' => _x( 'Project', 'Post Type Singular Name', 'lasso_leader' ), 
            'menu_name' => __( 'Projects', 'lasso_leader' ), 
            'all_items' => __( 'All Projects', 'lasso_leader' ), 
            'add_new_item' => __( 'Add New Project', 'lasso_leader' ),
            'edit_item' => __( 'Edit Project', 'lasso_leader' ),
            'new_item' => __( 'New Project', 'lasso_leader' ),
            'view_item' => __( 'View Project', 'lasso_leader' ),
            'search_items' => __( 'Search Projects', 'lasso_leader' ),
            'not_found' => __( 'No projects found', 'lasso_leader' ),
            'not_found_in_trash' => __( 'No projects found in trash', 'lasso_leader' ),
        );
        
        $project_args = array( 
            'label' => __( 'Project', 'lasso_leader' ), 
            'labels' => $project_labels, 
            'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' ), 
            'public' => true, 
            'show_ui' => true, 
            'show_in_menu' => true, 
            'menu_position' => 20, 
            'menu_icon' => 'dashicons-building', 
            'has_archive' => 'projects', 
            'capability_type' => 'post', 
            'show_in_rest' => true,
            'rewrite' => array( 'slug' => 'projects' ),
        );
        register_post_type( 'lasso_project', $project_args );

        // Register Agents Post Type
        $agent_labels = array( 
            'name' => _x( 'Agents', 'Post Type General Name', 'lasso_leader' ), 
            'singular_name' => _x( 'Agent', 'Post Type Singular Name', 'lasso_leader' ), 
            'menu_name' => __( 'Agents', 'lasso_leader' ), 
            'all_items' => __( 'All Agents', 'lasso_leader' ), 
            'add_new_item' => __( 'Add New Agent', 'lasso_leader' ),
            'edit_item' => __( 'Edit Agent', 'lasso_leader' ),
            'new_item' => __( 'New Agent', 'lasso_leader' ),
            'view_item' => __( 'View Agent', 'lasso_leader' ),
            'search_items' => __( 'Search Agents', 'lasso_leader' ),
            'not_found' => __( 'No agents found', 'lasso_leader' ),
            'not_found_in_trash' => __( 'No agents found in trash', 'lasso_leader' ),
        );
        
        $agent_args = array( 
            'label' => __( 'Agent', 'lasso_leader' ), 
            'labels' => $agent_labels, 
            'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' ), 
            'public' => true, 
            'show_ui' => true, 
            'show_in_menu' => 'edit.php?post_type=lasso_project', 
            'capability_type' => 'post', 
            'show_in_rest' => true,
            'rewrite' => array( 'slug' => 'agents' ),
        );
        register_post_type( 'lasso_agent', $agent_args );
    }

    /**
     * Add custom meta boxes
     */
    public function add_custom_meta_boxes() {
        add_meta_box( 
            'lasso_project_details_meta_box', 
            __( 'Lasso Project Details', 'lasso_leader' ), 
            array( $this, 'render_project_meta_box_content' ), 
            'lasso_project', 
            'normal', 
            'high' 
        );
        
        add_meta_box( 
            'lasso_agent_projects_meta_box', 
            __( 'Associated Projects', 'lasso_leader' ), 
            array( $this, 'render_agent_meta_box_content' ), 
            'lasso_agent', 
            'side', 
            'default' 
        );
    }

    /**
     * Render project meta box content
     */
    public function render_project_meta_box_content( $post ) {
        wp_nonce_field( 'lasso_project_meta_nonce_action', 'lasso_project_meta_nonce' );
        
        $project_id = get_post_meta( $post->ID, '_lasso_project_id', true );
        $api_key = get_post_meta( $post->ID, '_lasso_api_key', true );
        $project_name = get_post_meta( $post->ID, '_lasso_project_name', true );
        $answer_id = get_post_meta( $post->ID, '_lasso_answer_id', true );
        
        echo '<table class="form-table">';
        
        echo '<tr>';
        echo '<th scope="row"><label for="lasso_project_id">' . esc_html__( 'Lasso Project ID', 'lasso_leader' ) . '</label></th>';
        echo '<td><input type="text" id="lasso_project_id" name="lasso_project_id" value="' . esc_attr( $project_id ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'The unique project ID from your Lasso CRM system.', 'lasso_leader' ) . '</p></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th scope="row"><label for="lasso_project_name">' . esc_html__( 'Project Name for Lasso', 'lasso_leader' ) . '</label></th>';
        echo '<td><input type="text" id="lasso_project_name" name="lasso_project_name" value="' . esc_attr( $project_name ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'This will be sent to Question ID 156759 in Lasso CRM when users register.', 'lasso_leader' ) . '</p></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th scope="row"><label for="lasso_answer_id">' . esc_html__( 'Lasso Answer ID (Optional)', 'lasso_leader' ) . '</label></th>';
        echo '<td><input type="text" id="lasso_answer_id" name="lasso_answer_id" value="' . esc_attr( $answer_id ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Answer ID for Question 156759 (e.g., 397189 for 40 West 12th). Leave blank to use automatic mapping.', 'lasso_leader' ) . '</p></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th scope="row"><label for="lasso_api_key">' . esc_html__( 'Lasso API Key (Project Specific)', 'lasso_leader' ) . '</label></th>';
        echo '<td><textarea id="lasso_api_key" name="lasso_api_key" rows="3" class="large-text" style="font-family: monospace;">' . esc_textarea( $api_key ) . '</textarea>';
        echo '<p class="description">' . esc_html__( 'Optional: Override the global API key for this specific project.', 'lasso_leader' ) . '</p></td>';
        echo '</tr>';
        
        echo '</table>';
    }

    /**
     * Render agent meta box content
     */
    public function render_agent_meta_box_content( $post ) {
        wp_nonce_field( 'lasso_agent_projects_nonce_action', 'lasso_agent_projects_nonce' );
        
        $associated_projects = get_post_meta( $post->ID, '_lasso_associated_projects', true );
        if ( ! is_array( $associated_projects ) ) {
            $associated_projects = array();
        }
        
        $all_projects = get_posts( array( 
            'post_type' => 'lasso_project', 
            'posts_per_page' => -1, 
            'orderby' => 'title', 
            'order' => 'ASC',
            'post_status' => 'publish'
        ) );
        
        if ( empty( $all_projects ) ) {
            echo '<p>' . esc_html__( 'No projects available. Create some projects first.', 'lasso_leader' ) . '</p>';
            return;
        }
        
        echo '<div class="lasso-project-checkboxes" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; background: white; border-radius: 4px;">';
        foreach ( $all_projects as $project ) {
            $checked = in_array( $project->ID, $associated_projects ) ? 'checked="checked"' : '';
            echo '<label style="display: block; margin-bottom: 10px; padding: 8px; border-radius: 4px; transition: background-color 0.3s ease;">';
            echo '<input type="checkbox" name="associated_projects[]" value="' . esc_attr( $project->ID ) . '" ' . $checked . ' style="margin-right: 10px; transform: scale(1.1);">';
            echo esc_html( $project->post_title );
            echo '</label>';
        }
        echo '</div>';
        
        echo '<p class="description" style="margin-top: 10px;">';
        echo esc_html__( 'Select which projects this agent is associated with.', 'lasso_leader' );
        echo '</p>';
    }

    /**
     * Save project meta data
     */
    public function save_project_meta_data( $post_id ) {
        if ( ! isset( $_POST['lasso_project_meta_nonce'] ) || 
             ! wp_verify_nonce( $_POST['lasso_project_meta_nonce'], 'lasso_project_meta_nonce_action' ) ) { 
            return; 
        }
        
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { 
            return; 
        }
        
        if ( ! current_user_can( 'edit_post', $post_id ) ) { 
            return; 
        }
        
        $fields = array( 'lasso_project_id', 'lasso_project_name', 'lasso_api_key', 'lasso_answer_id' );
        
        foreach ( $fields as $field ) {
            if ( isset( $_POST[$field] ) ) {
                $value = $field === 'lasso_api_key' ? sanitize_textarea_field( $_POST[$field] ) : sanitize_text_field( $_POST[$field] );
                update_post_meta( $post_id, '_' . $field, $value );
            }
        }
    }

    /**
     * Save agent meta data
     */
    public function save_agent_meta_data( $post_id ) {
        if ( ! isset( $_POST['lasso_agent_projects_nonce'] ) || 
             ! wp_verify_nonce( $_POST['lasso_agent_projects_nonce'], 'lasso_agent_projects_nonce_action' ) ) { 
            return; 
        }
        
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { 
            return; 
        }
        
        if ( ! current_user_can( 'edit_post', $post_id ) ) { 
            return; 
        }
        
        $new_associated_projects = array();
        if ( isset( $_POST['associated_projects'] ) && is_array( $_POST['associated_projects'] ) ) {
            foreach ( $_POST['associated_projects'] as $project_id ) { 
                $new_associated_projects[] = absint( $project_id ); 
            }
        }
        
        update_post_meta( $post_id, '_lasso_associated_projects', $new_associated_projects );
    }
}