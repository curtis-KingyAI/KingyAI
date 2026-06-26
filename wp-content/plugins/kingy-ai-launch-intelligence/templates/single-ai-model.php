<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main id="primary" class="site-main kingy-ali-template kingy-ali-model-template">
    <?php
    while (have_posts()) :
        the_post();
        $post_id = get_the_ID();
        kingy_ali_enqueue_model_assets();

        $official_url = kingy_ali_model_url($post_id, 'official_url');
        $announcement_url = kingy_ali_model_url($post_id, 'official_announcement_url');
        $docs_url = kingy_ali_model_url($post_id, 'official_docs_url');
        $api_reference_url = kingy_ali_model_url($post_id, 'api_reference_url');
        $model_card_url = kingy_ali_model_url($post_id, 'model_card_url');
        $system_card_url = kingy_ali_model_url($post_id, 'system_card_url');
        $evals_url = kingy_ali_model_url($post_id, 'evals_url');
        $pricing_url = kingy_ali_model_url($post_id, 'pricing_url');
        $license_url = kingy_ali_model_url($post_id, 'license_url');
        $weights_url = kingy_ali_model_url($post_id, 'weights_url');
        $benchmark_url = kingy_ali_model_url($post_id, 'benchmark_url');
        $alternatives_url = kingy_ali_model_url($post_id, 'alternatives_url');
        $related_article_url = kingy_ali_model_url($post_id, 'related_article_url');
        $related_course_url = kingy_ali_model_url($post_id, 'related_course_url');

        $related_launch_id = kingy_ali_public_profile_id(kingy_ali_get_meta($post_id, 'related_launch_id'));
        if (!kingy_ali_related_post_is_public_index_ready($related_launch_id, 'kingy_ai_launch')) {
            $related_launch_id = 0;
        }
        $related_tool_id = kingy_ali_public_profile_id(kingy_ali_get_meta($post_id, 'related_tool_id'));
        if (!kingy_ali_related_post_is_public_index_ready($related_tool_id, 'kingy_ai_tool')) {
            $related_tool_id = 0;
        }
        $related_company_id = kingy_ali_public_profile_id(kingy_ali_get_meta($post_id, 'related_company_id'));
        if (!kingy_ali_related_post_is_public_index_ready($related_company_id, 'kingy_ai_company')) {
            $related_company_id = 0;
        }

        $model_landing_link = function_exists('kingy_ali_model_landing_link_for_post') ? kingy_ali_model_landing_link_for_post($post_id) : array();
        $model_comparison_links = function_exists('kingy_ali_model_static_compare_links_for_post') ? kingy_ali_model_static_compare_links_for_post($post_id) : array();
        $overview = kingy_ali_model_text($post_id, 'model_overview');
        ?>
        <article <?php post_class('kingy-ali-single kingy-ali-model-single'); ?>>
            <header class="kingy-ali-single__header">
                <div class="kingy-ali-single__header-inner">
                    <div>
                        <p class="kingy-ali-kicker"><?php esc_html_e('AI Model Profile', 'kingy-ai-launch-intelligence'); ?></p>
                        <h1><?php the_title(); ?></h1>
                        <?php if ($overview) : ?>
                            <p><?php echo esc_html($overview); ?></p>
                        <?php elseif (has_excerpt()) : ?>
                            <p><?php echo esc_html(get_the_excerpt()); ?></p>
                        <?php endif; ?>
                        <div class="kingy-ali-single__actions">
                            <?php kingy_ali_model_external_link(__('Official model page', 'kingy-ai-launch-intelligence'), $official_url); ?>
                            <?php kingy_ali_model_external_link(__('Official docs', 'kingy-ai-launch-intelligence'), $docs_url); ?>
                            <a data-kingy-ali-track="clicked_model_compare" data-event-label="<?php esc_attr_e('Compare AI models from profile', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_profile_header" href="<?php echo esc_url(home_url('/compare-ai-models/')); ?>"><?php esc_html_e('Compare models', 'kingy-ai-launch-intelligence'); ?></a>
                        </div>
                    </div>
                    <aside class="kingy-ali-single__hero-facts">
                        <dl>
                            <?php kingy_ali_model_fact(__('Provider', 'kingy-ai-launch-intelligence'), kingy_ali_model_provider_label($post_id)); ?>
                            <?php kingy_ali_model_fact(__('Modalities', 'kingy-ai-launch-intelligence'), kingy_ali_model_terms_to_string($post_id, 'model_modality')); ?>
                            <?php kingy_ali_model_fact(__('Access', 'kingy-ai-launch-intelligence'), kingy_ali_model_terms_to_string($post_id, 'model_access_type')); ?>
                            <?php kingy_ali_model_fact(__('Last verified', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'last_verified')); ?>
                        </dl>
                    </aside>
                </div>
            </header>

            <section class="kingy-ali-facts">
                <?php kingy_ali_model_fact(__('Family', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'model_family_name', kingy_ali_model_terms_to_string($post_id, 'model_family'))); ?>
                <?php kingy_ali_model_fact(__('Release date', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'release_date')); ?>
                <?php kingy_ali_model_fact(__('Status', 'kingy-ai-launch-intelligence'), kingy_ali_model_terms_to_string($post_id, 'model_status', kingy_ali_model_text($post_id, 'model_status_note'))); ?>
                <?php kingy_ali_model_fact(__('Context window', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'context_window')); ?>
                <?php kingy_ali_model_fact(__('Output limit', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'output_limit')); ?>
                <?php kingy_ali_model_fact(__('API', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'api_available')); ?>
                <?php kingy_ali_model_fact(__('Open weights', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'open_weight')); ?>
                <?php kingy_ali_model_fact(__('Local/self-hosted', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'local_available')); ?>
                <?php kingy_ali_model_fact(__('Pricing', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'pricing')); ?>
                <?php kingy_ali_model_fact(__('Verification', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'verification_status')); ?>
            </section>

            <?php kingy_ali_render_trust_panel($post_id, 'model'); ?>

            <section class="kingy-ali-content-band kingy-ali-model-caveat">
                <h2><?php esc_html_e('Benchmark Caveat', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php echo esc_html(kingy_ali_model_benchmark_caveat_note($post_id)); ?></p>
                <?php if (kingy_ali_model_text($post_id, 'benchmark_summary')) : ?>
                    <p><?php echo esc_html(kingy_ali_model_text($post_id, 'benchmark_summary')); ?></p>
                <?php endif; ?>
            </section>

            <section class="kingy-ali-content-grid kingy-ali-model-panels">
                <?php kingy_ali_model_text_panel(__('Best for', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'best_for')); ?>
                <?php kingy_ali_model_text_panel(__('Skip if', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'skip_if')); ?>
                <?php kingy_ali_model_text_panel(__('Strengths', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'strengths')); ?>
                <?php kingy_ali_model_text_panel(__('Weaknesses', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'weaknesses')); ?>
                <?php kingy_ali_model_text_panel(__('Agent suitability', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'agent_suitability')); ?>
                <?php kingy_ali_model_text_panel(__('Kingy AI take', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'kingy_verdict')); ?>
            </section>

            <?php if (get_the_content()) : ?>
                <section class="kingy-ali-content-band kingy-ali-model-body">
                    <h2><?php esc_html_e('Full Model Notes', 'kingy-ai-launch-intelligence'); ?></h2>
                    <?php the_content(); ?>
                </section>
            <?php endif; ?>

            <section class="kingy-ali-content-grid kingy-ali-model-panels">
                <?php kingy_ali_model_text_panel(__('Coding notes', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'coding_notes')); ?>
                <?php kingy_ali_model_text_panel(__('Reasoning notes', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'reasoning_notes')); ?>
                <?php kingy_ali_model_text_panel(__('Creative notes', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'creative_notes')); ?>
                <?php kingy_ali_model_text_panel(__('Research notes', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'research_notes')); ?>
                <?php kingy_ali_model_text_panel(__('API pricing notes', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'api_pricing')); ?>
                <?php kingy_ali_model_text_panel(__('License notes', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'license_notes')); ?>
                <?php kingy_ali_model_text_panel(__('Hardware requirements', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'hardware_requirements')); ?>
            </section>

            <?php if ($official_url || $announcement_url || $docs_url || $api_reference_url || $model_card_url || $system_card_url || $evals_url || $pricing_url || $license_url || $weights_url || $benchmark_url) : ?>
                <section class="kingy-ali-link-panel">
                    <h2><?php esc_html_e('Official Model Links', 'kingy-ai-launch-intelligence'); ?></h2>
                    <div class="kingy-ali-link-list">
                        <?php kingy_ali_model_external_link(__('Official model page', 'kingy-ai-launch-intelligence'), $official_url); ?>
                        <?php kingy_ali_model_external_link(__('Announcement', 'kingy-ai-launch-intelligence'), $announcement_url); ?>
                        <?php kingy_ali_model_external_link(__('Docs', 'kingy-ai-launch-intelligence'), $docs_url); ?>
                        <?php kingy_ali_model_external_link(__('API reference', 'kingy-ai-launch-intelligence'), $api_reference_url); ?>
                        <?php kingy_ali_model_external_link(__('Model card', 'kingy-ai-launch-intelligence'), $model_card_url); ?>
                        <?php kingy_ali_model_external_link(__('System/safety card', 'kingy-ai-launch-intelligence'), $system_card_url); ?>
                        <?php kingy_ali_model_external_link(__('Official evals', 'kingy-ai-launch-intelligence'), $evals_url); ?>
                        <?php kingy_ali_model_external_link(__('Pricing', 'kingy-ai-launch-intelligence'), $pricing_url); ?>
                        <?php kingy_ali_model_external_link(__('License', 'kingy-ai-launch-intelligence'), $license_url); ?>
                        <?php kingy_ali_model_external_link(__('Weights/download', 'kingy-ai-launch-intelligence'), $weights_url); ?>
                        <?php kingy_ali_model_external_link(__('Benchmark/source', 'kingy-ai-launch-intelligence'), $benchmark_url); ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($model_landing_link || $model_comparison_links || $related_launch_id || $related_tool_id || $related_company_id || $alternatives_url || $related_article_url || $related_course_url) : ?>
                <section class="kingy-ali-link-panel kingy-ali-model-profile-research-map">
                    <h2><?php esc_html_e('Model Intelligence Research Map', 'kingy-ai-launch-intelligence'); ?></h2>
                    <p><?php esc_html_e('Use these internal paths to move from this model profile into provider pages, static comparison pages, related Kingy records, and the broader AI launch graph. These are research paths, not rankings.', 'kingy-ai-launch-intelligence'); ?></p>
                    <div class="kingy-ali-link-list">
                        <a data-kingy-ali-track="clicked_model_directory" data-event-label="<?php esc_attr_e('AI Model Intelligence Hub from model research map', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_profile_research_map" href="<?php echo esc_url(home_url('/ai-models/')); ?>"><?php esc_html_e('AI Model Intelligence Hub', 'kingy-ai-launch-intelligence'); ?></a>
                        <a data-kingy-ali-track="clicked_model_compare" data-event-label="<?php esc_attr_e('Compare AI Models from model research map', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_profile_research_map" href="<?php echo esc_url(home_url('/compare-ai-models/')); ?>"><?php esc_html_e('Compare AI Models', 'kingy-ai-launch-intelligence'); ?></a>
                        <?php if ($model_landing_link) : ?>
                            <a data-kingy-ali-track="clicked_model_landing" data-event-label="<?php echo esc_attr($model_landing_link['label']); ?>" data-event-surface="model_profile_research_map" href="<?php echo esc_url($model_landing_link['url']); ?>"><?php echo esc_html($model_landing_link['label']); ?></a>
                        <?php endif; ?>
                        <?php foreach ($model_comparison_links as $comparison_key => $comparison_config) : ?>
                            <?php if (empty($comparison_config['label'])) : ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <a data-kingy-ali-track="clicked_model_static_compare" data-event-label="<?php echo esc_attr($comparison_config['label']); ?>" data-event-surface="model_profile_research_map" href="<?php echo esc_url(kingy_ali_model_static_compare_page_url($comparison_key)); ?>"><?php echo esc_html($comparison_config['label']); ?></a>
                        <?php endforeach; ?>
                        <?php if ($related_launch_id) : ?>
                            <a data-kingy-ali-track="clicked_launch" data-object-id="<?php echo esc_attr($related_launch_id); ?>" data-event-surface="model_profile_related" href="<?php echo esc_url(get_permalink($related_launch_id)); ?>"><?php esc_html_e('Launch profile', 'kingy-ai-launch-intelligence'); ?></a>
                        <?php endif; ?>
                        <?php if ($related_tool_id) : ?>
                            <a data-kingy-ali-track="clicked_tool" data-object-id="<?php echo esc_attr($related_tool_id); ?>" data-event-surface="model_profile_related" href="<?php echo esc_url(get_permalink($related_tool_id)); ?>"><?php esc_html_e('Tool profile', 'kingy-ai-launch-intelligence'); ?></a>
                        <?php endif; ?>
                        <?php if ($related_company_id) : ?>
                            <a data-kingy-ali-track="clicked_company" data-object-id="<?php echo esc_attr($related_company_id); ?>" data-event-surface="model_profile_related" href="<?php echo esc_url(get_permalink($related_company_id)); ?>"><?php esc_html_e('Company profile', 'kingy-ai-launch-intelligence'); ?></a>
                        <?php endif; ?>
                        <a data-kingy-ali-track="clicked_ai_tools_cta" data-event-label="<?php esc_attr_e('AI Tools from model research map', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_profile_research_map" href="<?php echo esc_url(home_url('/ai-tools/')); ?>"><?php esc_html_e('AI Tools', 'kingy-ai-launch-intelligence'); ?></a>
                        <a data-kingy-ali-track="clicked_company_directory" data-event-label="<?php esc_attr_e('AI Companies from model research map', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_profile_research_map" href="<?php echo esc_url(home_url('/ai-companies/')); ?>"><?php esc_html_e('AI Companies', 'kingy-ai-launch-intelligence'); ?></a>
                        <a data-kingy-ali-track="clicked_launch_hub_cta" data-event-label="<?php esc_attr_e('AI Launches from model research map', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_profile_research_map" href="<?php echo esc_url(home_url('/ai-launches/')); ?>"><?php esc_html_e('AI Launches', 'kingy-ai-launch-intelligence'); ?></a>
                        <?php kingy_ali_model_external_link(__('Alternatives / comparison', 'kingy-ai-launch-intelligence'), $alternatives_url, 'model_profile_related'); ?>
                        <?php kingy_ali_model_external_link(kingy_ali_model_related_article_label($related_article_url), $related_article_url, 'model_profile_related'); ?>
                        <?php kingy_ali_model_external_link(__('Related course', 'kingy-ai-launch-intelligence'), $related_course_url, 'model_profile_related'); ?>
                    </div>
                </section>
            <?php endif; ?>

            <div class="kingy-ali-cta-row">
                <a data-kingy-ali-track="clicked_model_directory" data-event-label="<?php esc_attr_e('Back to AI Models', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_profile_cta" href="<?php echo esc_url(home_url('/ai-models/')); ?>"><?php esc_html_e('Browse AI models', 'kingy-ai-launch-intelligence'); ?></a>
                <a data-kingy-ali-track="clicked_model_compare" data-event-label="<?php esc_attr_e('Compare AI models from model CTA', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_profile_cta" href="<?php echo esc_url(home_url('/compare-ai-models/')); ?>"><?php esc_html_e('Compare AI models', 'kingy-ai-launch-intelligence'); ?></a>
                <a data-kingy-ali-track="clicked_submit_cta" data-event-label="<?php esc_attr_e('Submit a model launch', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_profile_cta" href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php esc_html_e('Submit a model launch', 'kingy-ai-launch-intelligence'); ?></a>
            </div>
        </article>
    <?php endwhile; ?>
</main>
<?php
get_footer();
