<div class="lasso-onsite-directory lasso-leader-frontend">
    <?php if (filter_var($atts['show_filters'], FILTER_VALIDATE_BOOLEAN)) : ?>
    <div class="lasso-directory-filters">
        <form class="lasso-filter-form">
            <div class="lasso-filter-group">
                <label for="lasso-filter-type" class="ev-form-label"><?php esc_html_e('Project Type', 'lasso_leader'); ?></label>
                <?php echo $this->get_taxonomy_dropdown('project_type', 'lasso-filter-type'); ?>
            </div>
            <div class="lasso-filter-group">
                <label for="lasso-filter-location" class="ev-form-label"><?php esc_html_e('Location', 'lasso_leader'); ?></label>
                <?php echo $this->get_taxonomy_dropdown('project_location', 'lasso-filter-location'); ?>
            </div>
            <button type="button" class="ev-btn ev-btn-primary lasso-filter-button"><?php esc_html_e('Filter', 'lasso_leader'); ?></button>
        </form>
    </div>
    <?php endif; ?>

    <div class="ev-container">
        <h2 class="ev-text-center"><?php esc_html_e('Select a Project', 'lasso_leader'); ?></h2>
        
        <div class="ev-grid lasso-project-grid" style="--lasso-columns: <?php echo absint($atts['columns']); ?>">
            <?php foreach ($projects as $project) : ?>
            <div class="ev-card lasso-project-card">
                <a href="<?php echo esc_url(get_permalink($project->ID)); ?>">
                    <?php if (has_post_thumbnail($project->ID)) : ?>
                    <div class="lasso-project-image">
                        <?php echo get_the_post_thumbnail($project->ID, 'medium_large', array('class' => 'ev-card-image')); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="ev-card-content lasso-project-content">
                        <h3><?php echo esc_html($project->post_title); ?></h3>
                        <?php if ($excerpt = get_the_excerpt($project)) : ?>
                        <div class="lasso-project-excerpt">
                            <p><?php echo wp_kses_post(wpautop($excerpt)); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="ev-mt-md">
                            <span class="ev-btn ev-btn-view-project">View Project</span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>