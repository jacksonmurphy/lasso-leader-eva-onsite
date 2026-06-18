<div class="lasso-project-agents lasso-leader-frontend">
    <div class="ev-container">
        <h3 class="ev-text-center ev-mb-lg">Our Agents</h3>
        
        <div class="ev-grid agent-list">
            <?php 
            // Get all agents or associated agents
            $agents = get_posts(array(
                'post_type' => 'lasso_agent',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC'
            ));
            
            foreach ($agents as $agent) : ?>
                <div class="ev-agent-profile">
                    
                    <?php if ( has_post_thumbnail( $agent->ID ) ) : ?>
                        <div class="agent-photo">
                            <?php echo get_the_post_thumbnail( $agent->ID, 'thumbnail', array( 
                                'class' => 'ev-agent-photo'
                            ) ); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="ev-agent-info">
                        <h4><?php echo esc_html( $agent->post_title ); ?></h4>
                        <p class="ev-agent-title">Sales Agent</p>
                        
                        <?php if ( $agent->post_excerpt ) : ?>
                            <p><?php echo esc_html( wp_trim_words( $agent->post_excerpt, 25 ) ); ?></p>
                        <?php endif; ?>
                        
                        <div class="agent-actions ev-mt-md">
                            <a href="<?php echo esc_url( get_permalink( $agent->ID ) ); ?>" 
                               class="ev-btn ev-btn-view-project">
                                View Profile
                            </a>
                            
                            <?php 
                            // Add registration link if registration page exists
                            $registration_page = get_page_by_path( 'registration' );
                            if ( $registration_page ) :
                                $reg_link = add_query_arg( array( 
                                    'agent_name' => urlencode( $agent->post_title ) 
                                ), get_permalink( $registration_page ) );
                            ?>
                                <a href="<?php echo esc_url( $reg_link ); ?>" 
                                   class="ev-btn ev-btn-register">
                                    Register with <?php echo esc_html( $agent->post_title ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>