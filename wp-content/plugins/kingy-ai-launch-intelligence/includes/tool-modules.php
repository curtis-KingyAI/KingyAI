<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dependency-light, reusable renderers for the current tool-profile modules.
 *
 * Historical pricing and feature storage is not populated in production. Any
 * snapshot request therefore fails closed and never falls back to current meta.
 */

if (!function_exists('kingy_ali_tool_terms_to_string')) {
    function kingy_ali_tool_terms_to_string($terms) {
        if (is_wp_error($terms) || empty($terms)) {
            return '';
        }

        return implode(', ', wp_list_pluck($terms, 'name'));
    }
}

if (!function_exists('kingy_ali_tool_fact')) {
    function kingy_ali_tool_fact($label, $value) {
        $value = kingy_ali_public_profile_text($value);
        if ($value === '') {
            $value = __('Unknown', 'kingy-ai-launch-intelligence');
        }

        echo '<div><dt>' . esc_html($label) . '</dt><dd>' . esc_html($value) . '</dd></div>';
    }
}

function kingy_ali_kali_heading_tag($heading_level = 2) {
    return 'h' . max(2, min(6, absint($heading_level)));
}

function kingy_ali_kali_humanize_boolean_value($value) {
    $value = kingy_ali_public_profile_text($value);
    $normalized = strtolower(trim($value));
    if (in_array($normalized, array('1', 'true', 'yes'), true)) {
        return __('Yes', 'kingy-ai-launch-intelligence');
    }
    if (in_array($normalized, array('0', 'false', 'no'), true)) {
        return __('No', 'kingy-ai-launch-intelligence');
    }
    return $value;
}

function kingy_ali_kali_tool_module_definitions() {
    return array(
        'facts' => array(
            'shortcode' => 'kingy_kali_tool_facts',
            'block' => 'kingy-ai-launch-intelligence/kali-tool-facts',
            'title' => __('KALI tool facts', 'kingy-ai-launch-intelligence'),
            'description' => __('Current tool facts. Snapshot requests fail closed when verified history is unavailable.', 'kingy-ai-launch-intelligence'),
        ),
        'pricing' => array(
            'shortcode' => 'kingy_kali_tool_pricing',
            'block' => 'kingy-ai-launch-intelligence/kali-tool-pricing',
            'title' => __('KALI tool pricing', 'kingy-ai-launch-intelligence'),
            'description' => __('Current pricing and free-plan facts with an explicit current verification label.', 'kingy-ai-launch-intelligence'),
        ),
        'features' => array(
            'shortcode' => 'kingy_kali_tool_features',
            'block' => 'kingy-ai-launch-intelligence/kali-tool-features',
            'title' => __('KALI tool features', 'kingy-ai-launch-intelligence'),
            'description' => __('Current capability summary. Historical feature requests fail closed.', 'kingy-ai-launch-intelligence'),
        ),
        'verification' => array(
            'shortcode' => 'kingy_kali_tool_verification',
            'block' => 'kingy-ai-launch-intelligence/kali-tool-verification',
            'title' => __('KALI verification', 'kingy-ai-launch-intelligence'),
            'description' => __('Current verification, freshness, sources, and correction controls.', 'kingy-ai-launch-intelligence'),
        ),
        'sources' => array(
            'shortcode' => 'kingy_kali_tool_sources',
            'block' => 'kingy-ai-launch-intelligence/kali-tool-sources',
            'title' => __('KALI tool sources', 'kingy-ai-launch-intelligence'),
            'description' => __('Current source links and correction controls for a tool record.', 'kingy-ai-launch-intelligence'),
        ),
        'launch_history' => array(
            'shortcode' => 'kingy_kali_tool_launch_history',
            'block' => 'kingy-ai-launch-intelligence/kali-tool-launch-history',
            'title' => __('KALI tool launch history', 'kingy-ai-launch-intelligence'),
            'description' => __('Published, index-ready launch records linked to a tool.', 'kingy-ai-launch-intelligence'),
        ),
    );
}

function kingy_ali_resolve_kali_tool($reference) {
    if (is_numeric($reference)) {
        $tool_id = absint($reference);
    } else {
        $slug = sanitize_title(is_scalar($reference) ? (string) $reference : '');
        $tool = $slug !== '' ? get_page_by_path($slug, OBJECT, 'kingy_ai_tool') : null;
        $tool_id = $tool ? absint($tool->ID) : 0;
    }

    if (!$tool_id || get_post_type($tool_id) !== 'kingy_ai_tool' || get_post_status($tool_id) !== 'publish') {
        return 0;
    }

    return $tool_id;
}

function kingy_ali_valid_kali_as_of($value) {
    if (!is_scalar($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
        return false;
    }

    $date = DateTime::createFromFormat('!Y-m-d', (string) $value);
    return $date && $date->format('Y-m-d') === (string) $value;
}

function kingy_ali_kali_tool_request($atts, $forced_module = '') {
    $atts = shortcode_atts(
        array(
            'tool' => '',
            'module' => $forced_module ?: 'facts',
            'mode' => 'live',
            'as_of' => '',
            'limit' => 12,
        ),
        is_array($atts) ? $atts : array(),
        'kingy_kali_tool_module'
    );

    $module = $forced_module ?: sanitize_key($atts['module']);
    $mode = sanitize_key($atts['mode']);
    $as_of = is_scalar($atts['as_of']) ? trim((string) $atts['as_of']) : '';
    $definitions = kingy_ali_kali_tool_module_definitions();

    if (!isset($definitions[$module])) {
        return array('error' => __('Unknown KALI tool module.', 'kingy-ai-launch-intelligence'));
    }
    if (!in_array($mode, array('live', 'snapshot'), true)) {
        return array('error' => __('Mode must be live or snapshot.', 'kingy-ai-launch-intelligence'));
    }
    if ($as_of !== '' && !kingy_ali_valid_kali_as_of($as_of)) {
        return array('error' => __('The as_of value must be a valid YYYY-MM-DD date.', 'kingy-ai-launch-intelligence'));
    }

    $tool_id = kingy_ali_resolve_kali_tool($atts['tool']);
    if (!$tool_id) {
        return array('error' => __('Choose a published AI tool by ID or slug.', 'kingy-ai-launch-intelligence'));
    }

    return array(
        'tool_id' => $tool_id,
        'module' => $module,
        'mode' => $mode,
        'as_of' => $as_of,
        'limit' => max(1, min(24, absint($atts['limit']))),
    );
}

function kingy_ali_kali_tool_error_html($message) {
    return '<section class="kingy-ali-tool-module kingy-ali-tool-module--error" role="status"><p>' . esc_html($message) . '</p></section>';
}

function kingy_ali_kali_current_verification_text($tool_id) {
    $last_verified = kingy_ali_public_profile_meta_text($tool_id, 'last_verified');
    if (!$last_verified || !strtotime($last_verified)) {
        return __('Current record verification date: unknown. No current value is presented as historical.', 'kingy-ai-launch-intelligence');
    }

    return sprintf(
        __('Current record verification date: %s. This identifies the current record only; it is not historical evidence.', 'kingy-ai-launch-intelligence'),
        date_i18n(get_option('date_format'), strtotime($last_verified))
    );
}

function kingy_ali_kali_snapshot_unavailable_html($tool_id, $as_of = '', $heading_level = 2) {
    $heading_tag = kingy_ali_kali_heading_tag($heading_level);
    $message = $as_of !== ''
        ? sprintf(__('No verified historical snapshot is available for %s.', 'kingy-ai-launch-intelligence'), $as_of)
        : __('No verified historical snapshot is available.', 'kingy-ai-launch-intelligence');

    return '<section class="kingy-ali-tool-module kingy-ali-tool-module--snapshot-unavailable" data-kingy-kali-mode="snapshot" role="status">'
        . '<' . $heading_tag . '>' . esc_html__('Historical snapshot unavailable', 'kingy-ai-launch-intelligence') . '</' . $heading_tag . '>'
        . '<p>' . esc_html($message) . '</p>'
        . '<p class="kingy-ali-small-note">' . esc_html(kingy_ali_kali_current_verification_text($tool_id)) . '</p>'
        . '</section>';
}

function kingy_ali_render_kali_tool_facts($tool_id) {
    $launch_rollup = kingy_ali_tool_launch_rollup($tool_id);

    ob_start();
    ?>
    <section class="kingy-ali-facts kingy-ali-tool-module" data-kingy-kali-module="facts" data-kingy-kali-mode="live" data-kingy-tool-id="<?php echo esc_attr($tool_id); ?>">
        <?php kingy_ali_tool_fact(__('Company', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($tool_id, 'company')); ?>
        <?php kingy_ali_tool_fact(__('Primary category', 'kingy-ai-launch-intelligence'), kingy_ali_tool_terms_to_string(get_the_terms($tool_id, 'kingy_launch_category'))); ?>
        <?php kingy_ali_tool_fact(__('Best for', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($tool_id, 'best_for')); ?>
        <?php kingy_ali_tool_fact(__('Pricing', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($tool_id, 'pricing')); ?>
        <?php kingy_ali_tool_fact(__('Free plan', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($tool_id, 'free_plan', __('Unknown', 'kingy-ai-launch-intelligence'))); ?>
        <?php kingy_ali_tool_fact(__('API', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($tool_id, 'api_available', __('Unknown', 'kingy-ai-launch-intelligence'))); ?>
        <?php kingy_ali_tool_fact(__('Open source/open weight', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($tool_id, 'open_source_or_open_weight', __('Unknown', 'kingy-ai-launch-intelligence'))); ?>
        <?php kingy_ali_tool_fact(__('Linked launches', 'kingy-ai-launch-intelligence'), (string) $launch_rollup['count']); ?>
        <?php kingy_ali_tool_fact(__('Latest launch date', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_date_label($launch_rollup['latest_date'])); ?>
        <?php kingy_ali_tool_fact(__('Last verified', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($tool_id, 'last_verified')); ?>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_kali_tool_pricing($tool_id, $heading_level = 2, $humanize_values = false) {
    $pricing_url = kingy_ali_sanitize_public_profile_link_url(kingy_ali_get_meta($tool_id, 'pricing_url'));
    $heading_tag = kingy_ali_kali_heading_tag($heading_level);
    $free_plan = kingy_ali_public_profile_meta_text($tool_id, 'free_plan', __('Unknown', 'kingy-ai-launch-intelligence'));
    if ($humanize_values) {
        $free_plan = kingy_ali_kali_humanize_boolean_value($free_plan);
    }

    ob_start();
    ?>
    <section class="kingy-ali-content-band kingy-ali-tool-module" data-kingy-kali-module="pricing" data-kingy-kali-mode="live" data-kingy-tool-id="<?php echo esc_attr($tool_id); ?>">
        <<?php echo tag_escape($heading_tag); ?>><?php esc_html_e('Current pricing', 'kingy-ai-launch-intelligence'); ?></<?php echo tag_escape($heading_tag); ?>>
        <dl class="kingy-ali-score-list">
            <?php kingy_ali_tool_fact(__('Pricing', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($tool_id, 'pricing')); ?>
            <?php kingy_ali_tool_fact(__('Free plan', 'kingy-ai-launch-intelligence'), $free_plan); ?>
        </dl>
        <p class="kingy-ali-small-note"><?php echo esc_html(kingy_ali_kali_current_verification_text($tool_id)); ?></p>
        <?php if ($pricing_url) : ?>
            <p><a data-kingy-ali-track="clicked_source_link" data-event-label="<?php esc_attr_e('Official pricing', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="kali_tool_pricing" href="<?php echo esc_url($pricing_url); ?>"<?php echo kingy_ali_source_link_target_attrs($pricing_url); ?>><?php esc_html_e('Check official pricing', 'kingy-ai-launch-intelligence'); ?></a></p>
        <?php endif; ?>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_kali_tool_features($tool_id, $heading_level = 2, $humanize_values = false) {
    $heading_tag = kingy_ali_kali_heading_tag($heading_level);
    $api_available = kingy_ali_public_profile_meta_text($tool_id, 'api_available', __('Unknown', 'kingy-ai-launch-intelligence'));
    $open_source = kingy_ali_public_profile_meta_text($tool_id, 'open_source_or_open_weight', __('Unknown', 'kingy-ai-launch-intelligence'));
    if ($humanize_values) {
        $api_available = kingy_ali_kali_humanize_boolean_value($api_available);
        $open_source = kingy_ali_kali_humanize_boolean_value($open_source);
    }

    ob_start();
    ?>
    <section class="kingy-ali-content-band kingy-ali-tool-module" data-kingy-kali-module="features" data-kingy-kali-mode="live" data-kingy-tool-id="<?php echo esc_attr($tool_id); ?>">
        <<?php echo tag_escape($heading_tag); ?>><?php esc_html_e('Current feature summary', 'kingy-ai-launch-intelligence'); ?></<?php echo tag_escape($heading_tag); ?>>
        <dl class="kingy-ali-score-list">
            <?php kingy_ali_tool_fact(__('What it does', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($tool_id, 'what_it_does')); ?>
            <?php kingy_ali_tool_fact(__('Best for', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($tool_id, 'best_for')); ?>
            <?php kingy_ali_tool_fact(__('API', 'kingy-ai-launch-intelligence'), $api_available); ?>
            <?php kingy_ali_tool_fact(__('Open source/open weight', 'kingy-ai-launch-intelligence'), $open_source); ?>
        </dl>
        <p class="kingy-ali-small-note"><?php echo esc_html(kingy_ali_kali_current_verification_text($tool_id)); ?></p>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_kali_tool_verification($tool_id, $heading_level = 2, $humanize_counts = false) {
    ob_start();
    echo '<div class="kingy-ali-tool-module" data-kingy-kali-module="verification" data-kingy-kali-mode="live" data-kingy-tool-id="' . esc_attr($tool_id) . '">';
    kingy_ali_render_trust_panel($tool_id, 'tool', $heading_level, $humanize_counts);
    echo '</div>';
    return ob_get_clean();
}

function kingy_ali_render_kali_tool_sources($tool_id) {
    $source_links = kingy_ali_public_source_links($tool_id);

    ob_start();
    ?>
    <section class="kingy-ali-link-panel kingy-ali-tool-module" data-kingy-kali-module="sources" data-kingy-kali-mode="live" data-kingy-tool-id="<?php echo esc_attr($tool_id); ?>">
        <h2><?php esc_html_e('Current sources', 'kingy-ai-launch-intelligence'); ?></h2>
        <?php if ($source_links) : ?>
            <div class="kingy-ali-link-list">
                <?php foreach ($source_links as $source) : ?>
                    <a data-kingy-ali-track="clicked_source_link" data-event-label="<?php echo esc_attr($source['label']); ?>" data-event-surface="kali_tool_sources" href="<?php echo esc_url($source['url']); ?>"<?php echo kingy_ali_source_link_target_attrs($source['url']); ?>><?php echo esc_html($source['label']); ?></a>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p><?php esc_html_e('No public source links are recorded for this tool yet.', 'kingy-ai-launch-intelligence'); ?></p>
        <?php endif; ?>
        <p class="kingy-ali-small-note"><?php echo esc_html(kingy_ali_kali_current_verification_text($tool_id)); ?></p>
        <?php kingy_ali_render_correction_form($tool_id); ?>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_kali_tool_launch_history($tool_id, $limit = 12) {
    $launches = kingy_ali_query_tool_launches($tool_id, $limit);

    ob_start();
    ?>
    <section class="kingy-ali-content-band kingy-ali-launch-history kingy-ali-tool-module" data-kingy-kali-module="launch_history" data-kingy-kali-mode="live" data-kingy-tool-id="<?php echo esc_attr($tool_id); ?>">
        <h2><?php esc_html_e('Launch History', 'kingy-ai-launch-intelligence'); ?></h2>
        <?php if ($launches->have_posts()) : ?>
            <div class="kingy-ali-grid">
                <?php
                while ($launches->have_posts()) :
                    $launches->the_post();
                    echo kingy_ali_render_launch_card(get_the_ID());
                endwhile;
                wp_reset_postdata();
                ?>
            </div>
        <?php else : ?>
            <p><?php esc_html_e('No structured launch records are linked to this tool yet.', 'kingy-ai-launch-intelligence'); ?></p>
        <?php endif; ?>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_kali_tool_module($atts, $forced_module = '') {
    $request = kingy_ali_kali_tool_request($atts, $forced_module);
    if (isset($request['error'])) {
        return kingy_ali_kali_tool_error_html($request['error']);
    }

    // An as_of value always requests historical meaning, even if mode was
    // accidentally left as live. Current meta must never be projected backward.
    if ($request['mode'] === 'snapshot' || $request['as_of'] !== '') {
        return kingy_ali_kali_snapshot_unavailable_html($request['tool_id'], $request['as_of']);
    }

    switch ($request['module']) {
        case 'pricing':
            return kingy_ali_render_kali_tool_pricing($request['tool_id']);
        case 'features':
            return kingy_ali_render_kali_tool_features($request['tool_id']);
        case 'verification':
            return kingy_ali_render_kali_tool_verification($request['tool_id']);
        case 'sources':
            return kingy_ali_render_kali_tool_sources($request['tool_id']);
        case 'launch_history':
            return kingy_ali_render_kali_tool_launch_history($request['tool_id'], $request['limit']);
        case 'facts':
        default:
            return kingy_ali_render_kali_tool_facts($request['tool_id']);
    }
}

function kingy_ali_shortcode_kali_tool_module($atts) {
    return kingy_ali_render_kali_tool_module($atts);
}

function kingy_ali_register_kali_tool_shortcodes() {
    add_shortcode('kingy_kali_tool_module', 'kingy_ali_shortcode_kali_tool_module');
    foreach (kingy_ali_kali_tool_module_definitions() as $module => $definition) {
        add_shortcode(
            $definition['shortcode'],
            function ($atts) use ($module) {
                return kingy_ali_render_kali_tool_module($atts, $module);
            }
        );
    }
}

kingy_ali_register_kali_tool_shortcodes();

function kingy_ali_kali_tool_block_render($attributes, $module) {
    return kingy_ali_render_kali_tool_module(
        array(
            'tool' => isset($attributes['tool']) ? $attributes['tool'] : '',
            'mode' => isset($attributes['mode']) ? $attributes['mode'] : 'live',
            'as_of' => isset($attributes['asOf']) ? $attributes['asOf'] : '',
            'limit' => isset($attributes['limit']) ? $attributes['limit'] : 12,
        ),
        $module
    );
}

function kingy_ali_register_kali_tool_blocks() {
    if (!function_exists('register_block_type')) {
        return;
    }

    wp_register_script(
        'kingy-ali-kali-tool-blocks',
        KINGY_ALI_PLUGIN_URL . 'assets/js/tool-modules-blocks.js',
        array('wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n'),
        KINGY_ALI_VERSION,
        true
    );

    foreach (kingy_ali_kali_tool_module_definitions() as $module => $definition) {
        register_block_type(
            $definition['block'],
            array(
                'api_version' => 2,
                'editor_script' => 'kingy-ali-kali-tool-blocks',
                'attributes' => array(
                    'tool' => array('type' => 'string', 'default' => ''),
                    'mode' => array('type' => 'string', 'default' => 'live'),
                    'asOf' => array('type' => 'string', 'default' => ''),
                    'limit' => array('type' => 'number', 'default' => 12),
                ),
                'render_callback' => function ($attributes) use ($module) {
                    return kingy_ali_kali_tool_block_render($attributes, $module);
                },
            )
        );
    }
}

add_action('init', 'kingy_ali_register_kali_tool_blocks');
