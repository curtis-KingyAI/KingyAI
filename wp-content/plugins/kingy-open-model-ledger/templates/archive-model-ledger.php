<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$ledger_query = new WP_Query(KOML_Frontend::curated_query_args());
$selected_openness = isset($_GET['ledger_openness']) ? sanitize_key(wp_unslash($_GET['ledger_openness'])) : '';
$selected_format = isset($_GET['ledger_format']) ? sanitize_key(wp_unslash($_GET['ledger_format'])) : '';
$selected_lifecycle = isset($_GET['ledger_lifecycle']) ? sanitize_key(wp_unslash($_GET['ledger_lifecycle'])) : '';
$search_value = isset($_GET['ledger_q']) ? sanitize_text_field(wp_unslash($_GET['ledger_q'])) : '';
?>

<main id="primary" class="koml-wrap">
    <header class="koml-hero">
        <div class="koml-hero__copy">
            <p class="koml-kicker"><?php esc_html_e('Kingy AI decision infrastructure', 'kingy-open-model-ledger'); ?></p>
            <h1><?php esc_html_e('Open Model Ledger', 'kingy-open-model-ledger'); ?></h1>
            <p class="koml-deck"><?php esc_html_e('A curated record of meaningful model-family releases: when weights actually appeared, what artifacts exist, what the terms allow, and what hardware can run them.', 'kingy-open-model-ledger'); ?></p>
            <div class="koml-hero__actions">
                <?php if (KOML_Frontend::model_fit_is_ready()) : ?>
                    <a class="koml-button" href="<?php echo esc_url(home_url('/model-fit/')); ?>"><?php esc_html_e('Calculate model fit', 'kingy-open-model-ledger'); ?></a>
                <?php else : ?>
                    <button class="koml-button koml-button--disabled" type="button" disabled aria-disabled="true"><?php esc_html_e('Calculator in editorial review', 'kingy-open-model-ledger'); ?></button>
                <?php endif; ?>
                <a class="koml-button koml-button--secondary" href="<?php echo esc_url(home_url('/ai-launches/open-weight-models/')); ?>"><?php esc_html_e('View release/change feed', 'kingy-open-model-ledger'); ?></a>
            </div>
        </div>
        <aside class="koml-principle">
            <strong><?php esc_html_e('What belongs here', 'kingy-open-model-ledger'); ?></strong>
            <p><?php esc_html_e('Publisher-recognized model releases and materially changed checkpoints. Fine-tunes, quants, API aliases, and community conversions stay attached to the canonical record.', 'kingy-open-model-ledger'); ?></p>
        </aside>
    </header>

    <section class="koml-policy-strip" aria-label="Ledger standards">
        <div><strong><?php esc_html_e('Two dates', 'kingy-open-model-ledger'); ?></strong><span><?php esc_html_e('Announcement ≠ usable weights', 'kingy-open-model-ledger'); ?></span></div>
        <div><strong><?php esc_html_e('Two numbers', 'kingy-open-model-ledger'); ?></strong><span><?php esc_html_e('Total ≠ active parameters', 'kingy-open-model-ledger'); ?></span></div>
        <div><strong><?php esc_html_e('Two claims', 'kingy-open-model-ledger'); ?></strong><span><?php esc_html_e('Downloadable ≠ open source', 'kingy-open-model-ledger'); ?></span></div>
        <div><strong><?php esc_html_e('Every field', 'kingy-open-model-ledger'); ?></strong><span><?php esc_html_e('Source, confidence, verification', 'kingy-open-model-ledger'); ?></span></div>
    </section>

    <section class="koml-filter-panel" aria-labelledby="koml-filter-heading">
        <div>
            <p class="koml-kicker"><?php esc_html_e('Decision filters', 'kingy-open-model-ledger'); ?></p>
            <h2 id="koml-filter-heading"><?php esc_html_e('Find a release you can actually use', 'kingy-open-model-ledger'); ?></h2>
        </div>
        <form method="get" action="<?php echo esc_url(KOML_Frontend::ledger_url()); ?>" class="koml-filters">
            <label>
                <span><?php esc_html_e('Search family or publisher', 'kingy-open-model-ledger'); ?></span>
                <input type="search" name="ledger_q" value="<?php echo esc_attr($search_value); ?>" placeholder="<?php esc_attr_e('Qwen, OLMo, DeepSeek…', 'kingy-open-model-ledger'); ?>">
            </label>
            <label>
                <span><?php esc_html_e('Openness', 'kingy-open-model-ledger'); ?></span>
                <select name="ledger_openness">
                    <option value=""><?php esc_html_e('Any assessed status', 'kingy-open-model-ledger'); ?></option>
                    <option value="osaid" <?php selected($selected_openness, 'osaid'); ?>><?php esc_html_e('OSAID 1.0: meets', 'kingy-open-model-ledger'); ?></option>
                    <option value="permissive" <?php selected($selected_openness, 'permissive'); ?>><?php esc_html_e('Permissive weights', 'kingy-open-model-ledger'); ?></option>
                    <option value="restricted" <?php selected($selected_openness, 'restricted'); ?>><?php esc_html_e('Restricted weights', 'kingy-open-model-ledger'); ?></option>
                </select>
            </label>
            <label>
                <span><?php esc_html_e('Format', 'kingy-open-model-ledger'); ?></span>
                <select name="ledger_format">
                    <option value=""><?php esc_html_e('Any format', 'kingy-open-model-ledger'); ?></option>
                    <?php foreach (array('safetensors' => 'SafeTensors', 'gguf' => 'GGUF', 'mlx' => 'MLX', 'awq' => 'AWQ', 'gptq' => 'GPTQ') as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($selected_format, $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span><?php esc_html_e('Lifecycle', 'kingy-open-model-ledger'); ?></span>
                <select name="ledger_lifecycle">
                    <option value=""><?php esc_html_e('Any status', 'kingy-open-model-ledger'); ?></option>
                    <option value="current" <?php selected($selected_lifecycle, 'current'); ?>><?php esc_html_e('Current', 'kingy-open-model-ledger'); ?></option>
                    <option value="superseded" <?php selected($selected_lifecycle, 'superseded'); ?>><?php esc_html_e('Superseded', 'kingy-open-model-ledger'); ?></option>
                    <option value="deprecated" <?php selected($selected_lifecycle, 'deprecated'); ?>><?php esc_html_e('Deprecated', 'kingy-open-model-ledger'); ?></option>
                    <option value="withdrawn" <?php selected($selected_lifecycle, 'withdrawn'); ?>><?php esc_html_e('Withdrawn', 'kingy-open-model-ledger'); ?></option>
                </select>
            </label>
            <button class="koml-button" type="submit"><?php esc_html_e('Apply filters', 'kingy-open-model-ledger'); ?></button>
        </form>
    </section>

    <div class="koml-results-head" role="status">
        <strong><?php echo esc_html(sprintf(_n('%s release in this ledger view', '%s releases in this ledger view', (int) $ledger_query->found_posts, 'kingy-open-model-ledger'), number_format_i18n($ledger_query->found_posts))); ?></strong>
        <span><?php esc_html_e('Legacy open-weight claims remain visible with a review-pending label until their ledger fields are verified.', 'kingy-open-model-ledger'); ?></span>
    </div>

    <?php if ($ledger_query->have_posts()) : ?>
        <section class="koml-card-grid" aria-label="Open model records">
            <?php while ($ledger_query->have_posts()) : $ledger_query->the_post(); ?>
                <?php
                $model_id = get_the_ID();
                $openness = KOML_Frontend::openness($model_id);
                $scope = KOML_Frontend::scope_status($model_id);
                $announced = KOML_Frontend::announced_on($model_id);
                $weights = KOML_Frontend::weight_date($model_id);
                $total = KOML_Frontend::format_parameter_count(KOML_Frontend::meta($model_id, 'total_parameters', ''));
                $active = KOML_Frontend::format_parameter_count(KOML_Frontend::meta($model_id, 'active_parameters', ''));
                $context = KOML_Frontend::first_value($model_id, 'native_context_tokens', 'context_window', '');
                $formats = KOML_Frontend::meta($model_id, 'artifact_formats', array());
                if (!$formats) {
                    $artifact_rows = KOML_Frontend::meta($model_id, 'artifacts', array());
                    if (is_array($artifact_rows)) {
                        $formats = array_values(array_unique(array_filter(array_map(function ($artifact_row) {
                            return is_array($artifact_row) && !empty($artifact_row['format']) ? strtolower((string) $artifact_row['format']) : '';
                        }, $artifact_rows))));
                    }
                }
                if (!is_array($formats)) {
                    $formats = array_filter(array_map('trim', explode(',', (string) $formats)));
                }
                ?>
                <article <?php post_class('koml-card'); ?>>
                    <div class="koml-card__badges">
                        <span class="koml-badge koml-badge--<?php echo esc_attr($openness['key']); ?>"><?php echo esc_html($openness['label']); ?></span>
                        <?php if ($scope === 'legacy_review' || $scope === 'under_review') : ?>
                            <span class="koml-badge koml-badge--review"><?php esc_html_e('Ledger review pending', 'kingy-open-model-ledger'); ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="koml-card__provider"><?php echo esc_html(KOML_Frontend::legacy($model_id, 'provider_name', __('Publisher unknown', 'kingy-open-model-ledger'))); ?></p>
                    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                    <?php if (has_excerpt()) : ?>
                        <p class="koml-card__summary"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 28)); ?></p>
                    <?php else : ?>
                        <p class="koml-card__summary"><?php echo esc_html(wp_trim_words(KOML_Frontend::legacy($model_id, 'model_overview', ''), 28)); ?></p>
                    <?php endif; ?>
                    <dl class="koml-card__facts">
                        <div><dt><?php esc_html_e('Announced', 'kingy-open-model-ledger'); ?></dt><dd><?php echo esc_html(KOML_Frontend::display_value($announced)); ?></dd></div>
                        <div><dt><?php esc_html_e('Weights available', 'kingy-open-model-ledger'); ?></dt><dd><?php echo esc_html(KOML_Frontend::display_value($weights)); ?></dd></div>
                        <div><dt><?php esc_html_e('Parameters', 'kingy-open-model-ledger'); ?></dt><dd><?php echo esc_html($total . ($active !== __('Unknown', 'kingy-open-model-ledger') ? ' total · ' . $active . ' active' : '')); ?></dd></div>
                        <div><dt><?php esc_html_e('Native context', 'kingy-open-model-ledger'); ?></dt><dd><?php echo esc_html(KOML_Frontend::display_value($context)); ?></dd></div>
                    </dl>
                    <?php if ($formats) : ?>
                        <div class="koml-chip-row" aria-label="Artifact formats">
                            <?php foreach (array_slice($formats, 0, 6) as $format_name) : ?>
                                <span><?php echo esc_html(strtoupper((string) $format_name)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <footer class="koml-card__footer">
                        <span><?php echo esc_html(sprintf(__('Verified %s', 'kingy-open-model-ledger'), KOML_Frontend::display_value(KOML_Frontend::meta($model_id, 'last_verified', KOML_Frontend::legacy($model_id, 'last_verified', ''))))); ?></span>
                        <a href="<?php the_permalink(); ?>"><?php esc_html_e('Open ledger record →', 'kingy-open-model-ledger'); ?></a>
                    </footer>
                </article>
            <?php endwhile; ?>
        </section>

        <?php
        $pagination = paginate_links(array(
            'base' => KOML_Frontend::ledger_url() . '%_%',
            'format' => 'page/%#%/',
            'total' => $ledger_query->max_num_pages,
            'current' => max(1, (int) get_query_var('paged')),
            'type' => 'list',
            'add_args' => array_filter(array(
                'ledger_q' => $search_value,
                'ledger_openness' => $selected_openness,
                'ledger_format' => $selected_format,
                'ledger_lifecycle' => $selected_lifecycle,
            )),
        ));
        if ($pagination) :
            ?>
            <nav class="koml-pagination" aria-label="<?php esc_attr_e('Open Model Ledger pages', 'kingy-open-model-ledger'); ?>"><?php echo wp_kses_post($pagination); ?></nav>
        <?php endif; ?>
    <?php else : ?>
        <section class="koml-empty">
            <h2><?php esc_html_e('No release matches those filters', 'kingy-open-model-ledger'); ?></h2>
            <p><?php esc_html_e('Try a wider filter. A missing result may also mean the release has not passed ledger review yet.', 'kingy-open-model-ledger'); ?></p>
            <a class="koml-button" href="<?php echo esc_url(KOML_Frontend::ledger_url()); ?>"><?php esc_html_e('Clear filters', 'kingy-open-model-ledger'); ?></a>
        </section>
    <?php endif; ?>
    <?php wp_reset_postdata(); ?>
</main>

<?php get_footer(); ?>
