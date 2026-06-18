<?php
/**
 * Template for single agent posts
 * File: templates/single-lasso_agent.php
 * Updated with EV Brand Classes + project_name URL parameter support
 */

get_header(); ?>

<div class="lasso-leader-frontend">

<br><br>

    <div class="ev-container">
        
        <?php while ( have_posts() ) : the_post(); ?>
            
            <article id="post-<?php the_ID(); ?>" <?php post_class( 'lasso-agent-single' ); ?>>
                
                <header class="entry-header ev-text-center ev-mb-xl">
                    <h1 class="entry-title ev-mb-md">
                        <?php the_title(); ?>
                    </h1>
                    
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="agent-featured-image ev-mb-lg">
                            <?php the_post_thumbnail( 'large', array( 
                                'class' => 'ev-agent-photo',
                                'style' => 'width: 200px; height: 200px;'
                            ) ); ?>
                        </div>
                    <?php endif; ?>
                </header>

                <div class="entry-content ev-form ev-mb-xl">
                    <div class="agent-bio">
                        <?php 
                        the_content();
                        
                        wp_link_pages( array(
                            'before' => '<div class="page-links">',
                            'after'  => '</div>',
                        ) );
                        ?>
                    </div>
                </div>

                <?php
                // Display associated projects
                $associated_projects = get_post_meta( get_the_ID(), '_lasso_associated_projects', true );
                if ( ! empty( $associated_projects ) && is_array( $associated_projects ) ) :
                ?>
                    <div class="agent-projects ev-form">
                        <h2 class="ev-text-center ev-mb-lg">
                            <?php esc_html_e('Our Projects', 'lasso_leader'); ?>
                        </h2>
                        
                        <div class="ev-grid">
                            <?php foreach ( $associated_projects as $project_id ) : 
                                $project = get_post( $project_id );
                                if ( $project && $project->post_status === 'publish' ) :
                            ?>
                                <div class="ev-card">
                                    
                                    <?php if ( has_post_thumbnail( $project_id ) ) : ?>
                                        <div class="project-image">
                                            <a href="<?php echo esc_url( get_permalink( $project_id ) ); ?>">
                                                <?php echo get_the_post_thumbnail( $project_id, 'medium', array( 
                                                    'class' => 'ev-card-image'
                                                ) ); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="ev-card-content">
                                        <h3>
                                            <a href="<?php echo esc_url( get_permalink( $project_id ) ); ?>">
                                                <?php echo esc_html( $project->post_title ); ?>
                                            </a>
                                        </h3>
                                        
                                        <?php if ( $project->post_excerpt ) : ?>
                                            <p><?php echo esc_html( $project->post_excerpt ); ?></p>
                                        <?php endif; ?>
                                        
                                        <div class="project-actions ev-mt-md">
                                            <a href="<?php echo esc_url( get_permalink( $project_id ) ); ?>" 
                                               class="ev-btn ev-btn-view-project">
                                                View Project
                                            </a>
                                            
                                            <?php 
                                            // Add registration link if registration page exists
                                            $registration_page = get_page_by_path( 'registration' );
                                            if ( $registration_page ) :
                                                $lasso_project_id = get_post_meta( $project_id, '_lasso_project_id', true );
                                                $lasso_answer_id = get_post_meta( $project_id, '_lasso_answer_id', true );
                                                
                                                // Priority: Use Answer ID if available, fallback to Project ID
                                                if ( $lasso_answer_id ) {
                                                    // NEW: Use Answer ID format with project_name parameter
                                                    $reg_link = add_query_arg( array( 
                                                        'project' => $lasso_answer_id,  // Lasso Answer ID 
                                                        'project_name' => urlencode( $project->post_title ), // Project name for form population
                                                        'agent_name' => urlencode( get_the_title() ) 
                                                    ), get_permalink( $registration_page ) );
                                                } elseif ( $lasso_project_id ) {
                                                    // FALLBACK: Legacy Project ID format with project_name parameter
                                                    $reg_link = add_query_arg( array( 
                                                        'project_id' => $lasso_project_id, // Legacy Project ID
                                                        'project_name' => urlencode( $project->post_title ), // Project name for form population
                                                        'agent_name' => urlencode( get_the_title() ) 
                                                    ), get_permalink( $registration_page ) );
                                                } else {
                                                    // NO PROJECT DATA: Skip registration button
                                                    $reg_link = null;
                                                }
                                                
                                                if ( $reg_link ) :
                                            ?>
                                                <a href="<?php echo esc_url( $reg_link ); ?>" 
                                                   class="ev-btn ev-btn-register">
                                                    Register with <?php echo esc_html( get_the_title() ); ?>
                                                </a>
                                            <?php 
                                                endif;
                                            endif; 
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

                <footer class="entry-footer ev-text-center ev-mt-xl">
                    <div class="ev-form">
                        <a href="<?php echo esc_url( get_post_type_archive_link( 'lasso_agent' ) ); ?>" 
                           class="ev-btn ev-btn-secondary">
                            ← Back to All Agents
                        </a>
                    </div>
                </footer>

            </article>

        <?php endwhile; ?>

    </div>
</div>

<?php get_footer(); ?>