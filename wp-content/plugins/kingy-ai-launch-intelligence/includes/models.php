<?php

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('kingy_ai_model_directory', 'kingy_ali_shortcode_model_directory');
add_shortcode('kingy_ai_model_compare', 'kingy_ali_shortcode_model_compare');
add_shortcode('kingy_best_ai_models', 'kingy_ali_shortcode_best_ai_models');
add_shortcode('kingy_ai_model_landing', 'kingy_ali_shortcode_model_landing');
add_shortcode('kingy_ai_model_static_compare', 'kingy_ali_shortcode_model_static_compare');

function kingy_ali_model_page_paths() {
    $paths = array('compare-ai-models');
    if (function_exists('kingy_ali_best_model_page_configs')) {
        $paths = array_merge($paths, array_keys(kingy_ali_best_model_page_configs()));
    }
    if (function_exists('kingy_ali_model_landing_page_configs')) {
        foreach (kingy_ali_model_landing_page_configs() as $config) {
            if (!empty($config['path'])) {
                $paths[] = $config['path'];
            }
        }
    }
    if (function_exists('kingy_ali_model_static_compare_page_configs')) {
        foreach (kingy_ali_model_static_compare_page_configs() as $config) {
            if (!empty($config['path'])) {
                $paths[] = $config['path'];
            }
        }
    }

    return array_values(array_unique(array_filter($paths)));
}

function kingy_ali_model_noindex_page_paths() {
    $paths = array('compare-ai-models');
    if (function_exists('kingy_ali_best_model_page_configs')) {
        $paths = array_merge($paths, array_keys(kingy_ali_best_model_page_configs()));
    }
    if (function_exists('kingy_ali_model_landing_page_configs')) {
        foreach (kingy_ali_model_landing_page_configs() as $config) {
            if (!empty($config['path']) && !empty($config['noindex'])) {
                $paths[] = $config['path'];
            }
        }
    }
    if (function_exists('kingy_ali_model_static_compare_page_configs')) {
        foreach (kingy_ali_model_static_compare_page_configs() as $config) {
            if (!empty($config['path']) && !empty($config['noindex'])) {
                $paths[] = $config['path'];
            }
        }
    }

    return array_values(array_unique(array_filter($paths)));
}

function kingy_ali_best_model_page_configs() {
    return array(
        'best-ai-coding-models' => array('title' => __('Best AI Coding Models', 'kingy-ai-launch-intelligence'), 'use_case' => 'coding', 'post_status' => 'publish'),
        'best-open-weight-ai-models' => array('title' => __('Best Open-Weight AI Models', 'kingy-ai-launch-intelligence'), 'access_type' => 'open-weights', 'post_status' => 'publish'),
        'best-local-ai-models' => array('title' => __('Best Local AI Models', 'kingy-ai-launch-intelligence'), 'access_type' => 'local', 'post_status' => 'publish'),
        'best-ai-models-for-agents' => array('title' => __('Best AI Models for Agents', 'kingy-ai-launch-intelligence'), 'use_case' => 'agents', 'post_status' => 'publish'),
        'best-multimodal-ai-models' => array('title' => __('Best Multimodal AI Models', 'kingy-ai-launch-intelligence'), 'modality' => 'multimodal', 'post_status' => 'publish'),
        'best-ai-models-for-creators' => array('title' => __('Best AI Models for Creators', 'kingy-ai-launch-intelligence'), 'use_case' => 'creative-writing'),
        'best-ai-video-models' => array('title' => __('Best AI Video Models', 'kingy-ai-launch-intelligence'), 'modality' => 'video'),
        'best-ai-image-models' => array('title' => __('Best AI Image Models', 'kingy-ai-launch-intelligence'), 'modality' => 'image'),
        'best-ai-voice-models' => array('title' => __('Best AI Voice Models', 'kingy-ai-launch-intelligence'), 'modality' => 'audio'),
    );
}

function kingy_ali_model_landing_page_configs() {
    return array(
        'openai' => array(
            'path' => 'openai-ai-models',
            'title' => __('OpenAI AI Models', 'kingy-ai-launch-intelligence'),
            'label' => __('OpenAI models', 'kingy-ai-launch-intelligence'),
            'entity_label' => __('OpenAI', 'kingy-ai-launch-intelligence'),
            'provider' => 'openai',
            'company_slug' => 'openai',
            'noindex' => false,
        ),
        'claude' => array(
            'path' => 'claude-ai-models',
            'title' => __('Claude AI Models', 'kingy-ai-launch-intelligence'),
            'label' => __('Claude models', 'kingy-ai-launch-intelligence'),
            'entity_label' => __('Claude / Anthropic', 'kingy-ai-launch-intelligence'),
            'provider' => 'anthropic',
            'company_slug' => 'anthropic',
            'noindex' => false,
        ),
        'gemini' => array(
            'path' => 'gemini-ai-models',
            'title' => __('Gemini AI Models', 'kingy-ai-launch-intelligence'),
            'label' => __('Gemini models', 'kingy-ai-launch-intelligence'),
            'entity_label' => __('Gemini / Google', 'kingy-ai-launch-intelligence'),
            'provider' => 'google',
            'company_slug' => 'google-deepmind',
            'noindex' => false,
        ),
        'llama' => array(
            'path' => 'llama-ai-models',
            'title' => __('Llama AI Models', 'kingy-ai-launch-intelligence'),
            'label' => __('Llama models', 'kingy-ai-launch-intelligence'),
            'entity_label' => __('Llama / Meta', 'kingy-ai-launch-intelligence'),
            'provider' => 'meta',
            'company_slug' => 'meta',
            'noindex' => true,
        ),
        'mistral' => array(
            'path' => 'mistral-ai-models',
            'title' => __('Mistral AI Models', 'kingy-ai-launch-intelligence'),
            'label' => __('Mistral models', 'kingy-ai-launch-intelligence'),
            'entity_label' => __('Mistral AI', 'kingy-ai-launch-intelligence'),
            'provider' => 'mistral-ai',
            'company_slug' => 'mistral-ai',
            'noindex' => false,
        ),
        'deepseek' => array(
            'path' => 'deepseek-ai-models',
            'title' => __('DeepSeek AI Models', 'kingy-ai-launch-intelligence'),
            'label' => __('DeepSeek models', 'kingy-ai-launch-intelligence'),
            'entity_label' => __('DeepSeek', 'kingy-ai-launch-intelligence'),
            'provider' => 'deepseek',
            'company_slug' => 'deepseek',
            'noindex' => true,
        ),
        'grok' => array(
            'path' => 'grok-ai-models',
            'title' => __('Grok AI Models', 'kingy-ai-launch-intelligence'),
            'label' => __('Grok models', 'kingy-ai-launch-intelligence'),
            'entity_label' => __('Grok / xAI', 'kingy-ai-launch-intelligence'),
            'provider' => 'xai',
            'company_slug' => 'xai',
            'noindex' => true,
        ),
        'qwen' => array(
            'path' => 'qwen-ai-models',
            'title' => __('Qwen AI Models', 'kingy-ai-launch-intelligence'),
            'label' => __('Qwen models', 'kingy-ai-launch-intelligence'),
            'entity_label' => __('Qwen', 'kingy-ai-launch-intelligence'),
            'provider' => 'qwen',
            'company_slug' => 'alibaba-qwen-team',
            'noindex' => true,
        ),
    );
}

function kingy_ali_model_landing_page_config($key) {
    $key = sanitize_key($key);
    $configs = kingy_ali_model_landing_page_configs();
    return isset($configs[$key]) ? $configs[$key] : array();
}

function kingy_ali_model_static_compare_page_configs() {
    return array(
        'gpt_vs_claude' => array(
            'path' => 'gpt-vs-claude',
            'title' => __('GPT vs Claude AI Models', 'kingy-ai-launch-intelligence'),
            'label' => __('GPT vs Claude', 'kingy-ai-launch-intelligence'),
            'side_a' => array('label' => __('GPT / OpenAI', 'kingy-ai-launch-intelligence'), 'provider' => 'openai', 'landing' => 'openai'),
            'side_b' => array('label' => __('Claude / Anthropic', 'kingy-ai-launch-intelligence'), 'provider' => 'anthropic', 'landing' => 'claude'),
            'noindex' => false,
        ),
        'claude_vs_gemini' => array(
            'path' => 'claude-vs-gemini',
            'title' => __('Claude vs Gemini AI Models', 'kingy-ai-launch-intelligence'),
            'label' => __('Claude vs Gemini', 'kingy-ai-launch-intelligence'),
            'side_a' => array('label' => __('Claude / Anthropic', 'kingy-ai-launch-intelligence'), 'provider' => 'anthropic', 'landing' => 'claude'),
            'side_b' => array('label' => __('Gemini / Google', 'kingy-ai-launch-intelligence'), 'provider' => 'google', 'landing' => 'gemini'),
            'noindex' => false,
        ),
        'chatgpt_vs_gemini' => array(
            'path' => 'chatgpt-vs-gemini',
            'title' => __('ChatGPT vs Gemini AI Models', 'kingy-ai-launch-intelligence'),
            'label' => __('ChatGPT vs Gemini', 'kingy-ai-launch-intelligence'),
            'side_a' => array('label' => __('ChatGPT / OpenAI', 'kingy-ai-launch-intelligence'), 'provider' => 'openai', 'landing' => 'openai'),
            'side_b' => array('label' => __('Gemini / Google', 'kingy-ai-launch-intelligence'), 'provider' => 'google', 'landing' => 'gemini'),
            'noindex' => false,
        ),
        'openai_vs_anthropic' => array(
            'path' => 'openai-vs-anthropic-ai-models',
            'title' => __('OpenAI vs Anthropic AI Models', 'kingy-ai-launch-intelligence'),
            'label' => __('OpenAI vs Anthropic', 'kingy-ai-launch-intelligence'),
            'side_a' => array('label' => __('OpenAI', 'kingy-ai-launch-intelligence'), 'provider' => 'openai', 'landing' => 'openai'),
            'side_b' => array('label' => __('Anthropic', 'kingy-ai-launch-intelligence'), 'provider' => 'anthropic', 'landing' => 'claude'),
            'noindex' => true,
            'noindex_reason' => __('It overlaps heavily with the GPT vs Claude page, so Kingy AI keeps this entity-name version noindex until it has distinct editorial depth.', 'kingy-ai-launch-intelligence'),
        ),
        'openai_vs_google' => array(
            'path' => 'openai-vs-google-ai-models',
            'title' => __('OpenAI vs Google AI Models', 'kingy-ai-launch-intelligence'),
            'label' => __('OpenAI vs Google', 'kingy-ai-launch-intelligence'),
            'side_a' => array('label' => __('OpenAI', 'kingy-ai-launch-intelligence'), 'provider' => 'openai', 'landing' => 'openai'),
            'side_b' => array('label' => __('Google / Gemini', 'kingy-ai-launch-intelligence'), 'provider' => 'google', 'landing' => 'gemini'),
            'noindex' => false,
        ),
        'llama_vs_mistral' => array(
            'path' => 'llama-vs-mistral-ai-models',
            'title' => __('Llama vs Mistral AI Models', 'kingy-ai-launch-intelligence'),
            'label' => __('Llama vs Mistral', 'kingy-ai-launch-intelligence'),
            'side_a' => array('label' => __('Llama / Meta', 'kingy-ai-launch-intelligence'), 'provider' => 'meta', 'landing' => 'llama'),
            'side_b' => array('label' => __('Mistral AI', 'kingy-ai-launch-intelligence'), 'provider' => 'mistral-ai', 'landing' => 'mistral'),
            'noindex' => true,
            'noindex_reason' => __('The Llama side is still thin in the source-ready model set, so this page remains noindex while it functions as an internal research path.', 'kingy-ai-launch-intelligence'),
        ),
        'deepseek_vs_qwen' => array(
            'path' => 'deepseek-vs-qwen-ai-models',
            'title' => __('DeepSeek vs Qwen AI Models', 'kingy-ai-launch-intelligence'),
            'label' => __('DeepSeek vs Qwen', 'kingy-ai-launch-intelligence'),
            'side_a' => array('label' => __('DeepSeek', 'kingy-ai-launch-intelligence'), 'provider' => 'deepseek', 'landing' => 'deepseek'),
            'side_b' => array('label' => __('Qwen', 'kingy-ai-launch-intelligence'), 'provider' => 'qwen', 'landing' => 'qwen'),
            'noindex' => true,
            'noindex_reason' => __('The Qwen side has too little source-ready coverage for index consideration, so this page stays noindex.', 'kingy-ai-launch-intelligence'),
        ),
        'grok_vs_claude' => array(
            'path' => 'grok-vs-claude-ai-models',
            'title' => __('Grok vs Claude AI Models', 'kingy-ai-launch-intelligence'),
            'label' => __('Grok vs Claude', 'kingy-ai-launch-intelligence'),
            'side_a' => array('label' => __('Grok / xAI', 'kingy-ai-launch-intelligence'), 'provider' => 'xai', 'landing' => 'grok'),
            'side_b' => array('label' => __('Claude / Anthropic', 'kingy-ai-launch-intelligence'), 'provider' => 'anthropic', 'landing' => 'claude'),
            'noindex' => true,
            'noindex_reason' => __('The Grok side is still thin in the source-ready model set, so this page stays noindex until coverage expands.', 'kingy-ai-launch-intelligence'),
        ),
    );
}

function kingy_ali_model_static_compare_page_config($key) {
    $key = sanitize_key($key);
    $configs = kingy_ali_model_static_compare_page_configs();
    return isset($configs[$key]) ? $configs[$key] : array();
}

function kingy_ali_current_page_path() {
    if (!is_page()) {
        return '';
    }

    $post_id = get_queried_object_id();
    return $post_id ? trim((string) get_page_uri($post_id), '/') : '';
}

function kingy_ali_is_model_intelligence_page() {
    if (is_post_type_archive('kingy_ai_model') || is_singular('kingy_ai_model')) {
        return true;
    }

    $path = kingy_ali_current_page_path();
    return $path !== '' && in_array($path, kingy_ali_model_page_paths(), true);
}

function kingy_ali_model_page_should_noindex() {
    $path = kingy_ali_current_page_path();
    return $path !== '' && in_array($path, kingy_ali_model_noindex_page_paths(), true);
}

function kingy_ali_model_text($post_id, $key, $default = '') {
    if (function_exists('kingy_ali_public_profile_meta_text')) {
        return kingy_ali_public_profile_meta_text($post_id, $key, $default);
    }

    $value = get_post_meta($post_id, kingy_ali_meta_key($key), true);
    return is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : $default;
}

function kingy_ali_model_url($post_id, $key) {
    if (function_exists('kingy_ali_sanitize_public_profile_link_url')) {
        return kingy_ali_sanitize_public_profile_link_url(kingy_ali_get_meta($post_id, $key));
    }

    return esc_url_raw(kingy_ali_get_meta($post_id, $key), array('http', 'https'));
}

function kingy_ali_model_terms_to_string($post_id, $taxonomy, $fallback = '') {
    $terms = get_the_terms($post_id, $taxonomy);
    if (is_wp_error($terms) || empty($terms)) {
        return $fallback;
    }

    return implode(', ', wp_list_pluck($terms, 'name'));
}

function kingy_ali_model_provider_label($post_id) {
    $provider = kingy_ali_model_text($post_id, 'provider_name');
    return $provider !== '' ? $provider : kingy_ali_model_terms_to_string($post_id, 'model_provider', __('Unknown', 'kingy-ai-launch-intelligence'));
}

function kingy_ali_model_summary($post_id) {
    $summary = kingy_ali_model_text($post_id, 'model_overview', get_the_excerpt($post_id));
    if ($summary === '') {
        $summary = __('This model profile is waiting for a source-backed overview.', 'kingy-ai-launch-intelligence');
    }

    return $summary;
}

function kingy_ali_model_benchmark_caveat_note($post_id = 0) {
    $note = $post_id ? kingy_ali_model_text($post_id, 'benchmark_caveat') : '';
    if ($note !== '') {
        return $note;
    }

    return __('Benchmarks are directional signals, not universal rankings. Results can shift with prompts, tool use, latency targets, pricing tier, eval contamination, safety filters, context length, and the task mix a real team runs.', 'kingy-ai-launch-intelligence');
}

function kingy_ali_shortcode_model_directory($atts = array()) {
    try {
        return kingy_ali_shortcode_model_directory_inner($atts);
    } catch (Throwable $throwable) {
        return kingy_ali_render_model_emergency_safe_fallback('directory', $atts, $throwable);
    }
}

function kingy_ali_shortcode_model_directory_inner($atts = array()) {
    kingy_ali_enqueue_model_assets();
    $atts = shortcode_atts(array('limit' => 24, 'heading' => 'yes'), $atts, 'kingy_ai_model_directory');
    $filters = kingy_ali_model_request_filters();
    $query = kingy_ali_query_model_directory(
        array_merge(
            $filters,
            array(
                'limit' => absint($atts['limit']),
                'track_search' => kingy_ali_model_directory_has_filters($filters),
            )
        )
    );

    ob_start();
    echo '<section class="kingy-ali-model-hub kingy-ali-model-directory">';
    if ($atts['heading'] !== 'no') {
        kingy_ali_render_model_hub_hero();
    }
    echo kingy_ali_render_model_hub_foundation_sections();
    echo '<div class="kingy-ali-model-disclosure"><strong>' . esc_html__('Benchmark caveat', 'kingy-ai-launch-intelligence') . '</strong><span>' . esc_html(kingy_ali_model_benchmark_caveat_note()) . '</span></div>';
    echo kingy_ali_render_model_directory_filters($filters);
    echo kingy_ali_render_model_directory_grid($query);
    echo '</section>';

    return ob_get_clean();
}

function kingy_ali_log_model_emergency_safe_fallback($context, $throwable = null) {
    if (!function_exists('error_log')) {
        return;
    }

    $message = is_scalar($context) ? sanitize_key((string) $context) : 'unknown';
    if ($throwable instanceof Throwable) {
        $message .= ': ' . sanitize_text_field($throwable->getMessage());
    }

    error_log('Kingy AI Launch Intelligence model emergency safe fallback: ' . $message);
}

function kingy_ali_render_model_emergency_safe_fallback($context = 'directory', $atts = array(), $throwable = null) {
    kingy_ali_log_model_emergency_safe_fallback($context, $throwable);

    $atts = is_array($atts) ? $atts : array();
    $heading = isset($atts['heading']) ? (string) $atts['heading'] : 'yes';
    $heading_tag = $heading === 'no' ? 'h2' : 'h1';
    $is_compare = $context === 'compare';
    $title = $is_compare ? __('Compare AI Models', 'kingy-ai-launch-intelligence') : __('AI Model Intelligence Hub', 'kingy-ai-launch-intelligence');
    $copy = $is_compare
        ? __('Kingy AI is expanding source-backed AI model comparison data across provider, access, pricing, modality, open-weight status, benchmark caveats, and last-verified notes. Use the AI Model Intelligence Hub and Open-Weight Model Launches while comparison tables are being filled.', 'kingy-ai-launch-intelligence')
        : __('Kingy AI is building source-backed AI model profiles with provider, access, pricing, modality, open-weight status, benchmark caveats, official links, and last-verified notes. Start with the AI Tool Directory and Open-Weight Model Launches while the model profile database is being expanded.', 'kingy-ai-launch-intelligence');

    ob_start();
    ?>
    <section class="kingy-ali-model-hub kingy-ali-model-emergency-safe">
        <<?php echo tag_escape($heading_tag); ?>><?php echo esc_html($title); ?></<?php echo tag_escape($heading_tag); ?>>
        <p><?php echo esc_html($copy); ?></p>
        <div class="kingy-ali-cta-row">
            <?php if ($is_compare && kingy_ali_model_hub_is_public()) : ?>
                <a href="<?php echo esc_url(home_url('/ai-models/')); ?>"><?php esc_html_e('AI Model Intelligence Hub', 'kingy-ai-launch-intelligence'); ?></a>
            <?php endif; ?>
            <a href="<?php echo esc_url(home_url('/ai-tools/')); ?>"><?php esc_html_e('AI Tools', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="<?php echo esc_url(home_url('/ai-launches/open-weight-models/')); ?>"><?php esc_html_e('Open-Weight Model Launches', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="<?php echo esc_url(home_url('/ai-launches/')); ?>"><?php esc_html_e('AI Launches', 'kingy-ai-launch-intelligence'); ?></a>
            <?php if (!$is_compare && kingy_ali_model_compare_page_is_public()) : ?>
                <a href="<?php echo esc_url(home_url('/compare-ai-models/')); ?>"><?php esc_html_e('Compare AI Models', 'kingy-ai-launch-intelligence'); ?></a>
            <?php endif; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_model_hub_hero() {
    kingy_ali_render_directory_hero(
        __('AI Model Intelligence Hub', 'kingy-ai-launch-intelligence'),
        __('Kingy AI tracks AI models by provider, model family, modality, release status, API access, web access, local availability, open-weight/open-source status, pricing notes, hardware requirements, official sources, benchmark caveats, and last-verified status.', 'kingy-ai-launch-intelligence')
    );
}

function kingy_ali_render_model_hub_foundation_sections() {
    ob_start();
    ?>
    <section class="kingy-ali-model-foundation">
        <article>
            <h2><?php esc_html_e('What Kingy AI Tracks', 'kingy-ai-launch-intelligence'); ?></h2>
            <ul>
                <li><?php esc_html_e('Provider, model family, modality, release status, access paths, pricing notes, hardware requirements, and last-verified status.', 'kingy-ai-launch-intelligence'); ?></li>
                <li><?php esc_html_e('Open-weight/open-source status, license notes, official docs, model cards, source links, and benchmark caveats.', 'kingy-ai-launch-intelligence'); ?></li>
                <li><?php esc_html_e('Use-case notes for coding, agents, local/private workflows, multimodal work, creators, and business teams.', 'kingy-ai-launch-intelligence'); ?></li>
            </ul>
        </article>
        <article>
            <h2><?php esc_html_e('Model Categories To Build', 'kingy-ai-launch-intelligence'); ?></h2>
            <ul>
                <li><?php esc_html_e('Open-weight models, coding models, local/private models, multimodal models, agent-ready models, creator models, and business workflow models.', 'kingy-ai-launch-intelligence'); ?></li>
                <li><?php esc_html_e('Provider and family pages such as GPT, Claude, Gemini, Llama, Mistral, Qwen, DeepSeek, and other source-backed model lines.', 'kingy-ai-launch-intelligence'); ?></li>
            </ul>
        </article>
    </section>
    <section class="kingy-ali-link-panel kingy-ali-model-link-panel">
        <h2><?php esc_html_e('Current Useful Links', 'kingy-ai-launch-intelligence'); ?></h2>
        <div class="kingy-ali-link-list">
            <a href="<?php echo esc_url(home_url('/ai-launches/open-weight-models/')); ?>"><?php esc_html_e('Browse Open-Weight Model Launches', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="<?php echo esc_url(home_url('/ai-tools/')); ?>"><?php esc_html_e('Search AI Tools', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="<?php echo esc_url(home_url('/ai-companies/')); ?>"><?php esc_html_e('Explore AI Companies', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="<?php echo esc_url(home_url('/ai-launches/')); ?>"><?php esc_html_e('Browse AI Launch Intelligence', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="<?php echo esc_url(home_url('/ai-launches/today/')); ?>"><?php esc_html_e('Latest AI Launch Radar', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="<?php echo esc_url(home_url('/sponsor-kingy-ai/')); ?>"><?php esc_html_e('Sponsor Kingy AI', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php esc_html_e('Submit a Model Launch', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </section>
    <?php echo kingy_ali_render_model_browse_paths(); ?>
    <?php
    return ob_get_clean();
}

function kingy_ali_model_directory_url($args = array()) {
    $args = is_array($args) ? array_filter($args) : array();
    return $args ? add_query_arg($args, home_url('/ai-models/')) : home_url('/ai-models/');
}

function kingy_ali_model_landing_page_url($key, $fallback_args = array()) {
    $config = kingy_ali_model_landing_page_config($key);
    if (empty($config['path'])) {
        return kingy_ali_model_directory_url($fallback_args);
    }

    $url = home_url('/' . trim($config['path'], '/') . '/');
    if (!function_exists('kingy_ali_find_page_by_path')) {
        return $url;
    }

    $page = kingy_ali_find_page_by_path($config['path']);
    return ($page && get_post_status($page) === 'publish') ? $url : kingy_ali_model_directory_url($fallback_args);
}

function kingy_ali_model_static_compare_page_url($key) {
    $config = kingy_ali_model_static_compare_page_config($key);
    if (empty($config['path'])) {
        return home_url('/compare-ai-models/');
    }

    $url = home_url('/' . trim($config['path'], '/') . '/');
    if (!function_exists('kingy_ali_find_page_by_path')) {
        return $url;
    }

    $page = kingy_ali_find_page_by_path($config['path']);
    return ($page && get_post_status($page) === 'publish') ? $url : home_url('/compare-ai-models/');
}

function kingy_ali_model_static_compare_links_for_landing($landing_key) {
    $landing_key = sanitize_key($landing_key);
    if ($landing_key === '' || !function_exists('kingy_ali_model_static_compare_page_configs')) {
        return array();
    }

    $links = array();
    foreach (kingy_ali_model_static_compare_page_configs() as $key => $config) {
        $side_a_landing = !empty($config['side_a']['landing']) ? sanitize_key($config['side_a']['landing']) : '';
        $side_b_landing = !empty($config['side_b']['landing']) ? sanitize_key($config['side_b']['landing']) : '';
        if ($landing_key !== $side_a_landing && $landing_key !== $side_b_landing) {
            continue;
        }

        $links[$key] = $config;
    }

    return $links;
}

function kingy_ali_model_landing_key_from_config($config) {
    if (!is_array($config) || !function_exists('kingy_ali_model_landing_page_configs')) {
        return '';
    }

    $path = !empty($config['path']) ? trim((string) $config['path'], '/') : '';
    foreach (kingy_ali_model_landing_page_configs() as $key => $landing_config) {
        if (!empty($landing_config['path']) && trim((string) $landing_config['path'], '/') === $path) {
            return sanitize_key($key);
        }
    }

    return '';
}

function kingy_ali_model_term_slugs($post_id, $taxonomy) {
    $terms = get_the_terms($post_id, $taxonomy);
    if (is_wp_error($terms) || empty($terms)) {
        return array();
    }

    return array_values(array_filter(array_map('sanitize_key', wp_list_pluck($terms, 'slug'))));
}

function kingy_ali_model_landing_key_for_post($post_id) {
    $post_id = absint($post_id);
    if (!$post_id || get_post_type($post_id) !== 'kingy_ai_model') {
        return '';
    }

    $landing_by_provider = array(
        'openai' => 'openai',
        'anthropic' => 'claude',
        'google' => 'gemini',
        'google-deepmind' => 'gemini',
        'meta' => 'llama',
        'mistral-ai' => 'mistral',
        'deepseek' => 'deepseek',
        'xai' => 'grok',
        'qwen' => 'qwen',
    );
    foreach (kingy_ali_model_term_slugs($post_id, 'model_provider') as $provider_slug) {
        if (isset($landing_by_provider[$provider_slug])) {
            return $landing_by_provider[$provider_slug];
        }
    }

    $landing_by_family = array(
        'gpt' => 'openai',
        'chatgpt' => 'openai',
        'claude' => 'claude',
        'gemini' => 'gemini',
        'llama' => 'llama',
        'mistral' => 'mistral',
        'deepseek' => 'deepseek',
        'grok' => 'grok',
        'qwen' => 'qwen',
    );
    foreach (kingy_ali_model_term_slugs($post_id, 'model_family') as $family_slug) {
        if (isset($landing_by_family[$family_slug])) {
            return $landing_by_family[$family_slug];
        }
    }

    $provider_name = sanitize_key(kingy_ali_model_text($post_id, 'provider_name'));
    return isset($landing_by_provider[$provider_name]) ? $landing_by_provider[$provider_name] : '';
}

function kingy_ali_model_landing_link_for_post($post_id) {
    $landing_key = kingy_ali_model_landing_key_for_post($post_id);
    if ($landing_key === '') {
        return array();
    }

    $config = kingy_ali_model_landing_page_config($landing_key);
    if (empty($config['path'])) {
        return array();
    }

    return array(
        'key' => $landing_key,
        'label' => !empty($config['label']) ? $config['label'] : (!empty($config['title']) ? $config['title'] : __('Provider model page', 'kingy-ai-launch-intelligence')),
        'url' => kingy_ali_model_landing_page_url($landing_key, !empty($config['provider']) ? array('kali_model_provider' => $config['provider']) : array()),
    );
}

function kingy_ali_model_static_compare_links_for_post($post_id) {
    $landing_key = kingy_ali_model_landing_key_for_post($post_id);
    if ($landing_key === '') {
        return array();
    }

    return kingy_ali_model_static_compare_links_for_landing($landing_key);
}

function kingy_ali_model_static_compare_link_items() {
    $items = array();
    if (!function_exists('kingy_ali_model_static_compare_page_configs')) {
        return $items;
    }

    foreach (kingy_ali_model_static_compare_page_configs() as $key => $config) {
        if (empty($config['label'])) {
            continue;
        }

        $items[$key] = array(
            'label' => $config['label'],
            'url' => kingy_ali_model_static_compare_page_url($key),
            'noindex' => !empty($config['noindex']),
        );
    }

    return $items;
}

function kingy_ali_render_model_browse_paths() {
    $model_lines = array(
        array('label' => __('OpenAI models', 'kingy-ai-launch-intelligence'), 'landing' => 'openai', 'args' => array('kali_model_provider' => 'openai')),
        array('label' => __('Claude models', 'kingy-ai-launch-intelligence'), 'landing' => 'claude', 'args' => array('kali_model_provider' => 'anthropic')),
        array('label' => __('Gemini models', 'kingy-ai-launch-intelligence'), 'landing' => 'gemini', 'args' => array('kali_model_provider' => 'google')),
        array('label' => __('Llama models', 'kingy-ai-launch-intelligence'), 'landing' => 'llama', 'args' => array('kali_model_provider' => 'meta')),
        array('label' => __('Mistral models', 'kingy-ai-launch-intelligence'), 'landing' => 'mistral', 'args' => array('kali_model_provider' => 'mistral-ai')),
        array('label' => __('DeepSeek models', 'kingy-ai-launch-intelligence'), 'landing' => 'deepseek', 'args' => array('kali_model_provider' => 'deepseek')),
        array('label' => __('Grok models', 'kingy-ai-launch-intelligence'), 'landing' => 'grok', 'args' => array('kali_model_provider' => 'xai')),
        array('label' => __('Qwen models', 'kingy-ai-launch-intelligence'), 'landing' => 'qwen', 'args' => array('kali_model_provider' => 'qwen')),
    );
    $task_paths = array(
        array('label' => __('Coding models', 'kingy-ai-launch-intelligence'), 'args' => array('kali_model_use_case' => 'coding')),
        array('label' => __('Agent-ready models', 'kingy-ai-launch-intelligence'), 'args' => array('kali_model_use_case' => 'agents')),
        array('label' => __('Open-weight models', 'kingy-ai-launch-intelligence'), 'args' => array('kali_open_weight' => 'yes')),
        array('label' => __('Local/private models', 'kingy-ai-launch-intelligence'), 'args' => array('kali_local_available' => 'yes')),
        array('label' => __('Multimodal models', 'kingy-ai-launch-intelligence'), 'args' => array('kali_model_modality' => 'multimodal')),
    );
    $comparison_paths = kingy_ali_model_static_compare_link_items();

    ob_start();
    ?>
    <section class="kingy-ali-link-panel kingy-ali-model-browse-paths">
        <h2><?php esc_html_e('Browse AI Models By Provider, Family, And Workflow', 'kingy-ai-launch-intelligence'); ?></h2>
        <p><?php esc_html_e('These paths use the same source-backed filters as the directory. They are discovery routes, not rankings.', 'kingy-ai-launch-intelligence'); ?></p>
        <div class="kingy-ali-content-grid">
            <div>
                <h3><?php esc_html_e('Model lines', 'kingy-ai-launch-intelligence'); ?></h3>
                <div class="kingy-ali-link-list">
                    <?php foreach ($model_lines as $link) : ?>
                        <a data-kingy-ali-track="clicked_model_browse_path" data-event-label="<?php echo esc_attr($link['label']); ?>" data-event-surface="model_hub_browse_paths" href="<?php echo esc_url(kingy_ali_model_landing_page_url($link['landing'], $link['args'])); ?>"><?php echo esc_html($link['label']); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <h3><?php esc_html_e('Workflow paths', 'kingy-ai-launch-intelligence'); ?></h3>
                <div class="kingy-ali-link-list">
                    <?php foreach ($task_paths as $link) : ?>
                        <a data-kingy-ali-track="clicked_model_browse_path" data-event-label="<?php echo esc_attr($link['label']); ?>" data-event-surface="model_hub_browse_paths" href="<?php echo esc_url(kingy_ali_model_directory_url($link['args'])); ?>"><?php echo esc_html($link['label']); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if ($comparison_paths) : ?>
                <div>
                    <h3><?php esc_html_e('Comparison paths', 'kingy-ai-launch-intelligence'); ?></h3>
                    <div class="kingy-ali-link-list">
                        <?php foreach ($comparison_paths as $link) : ?>
                            <a data-kingy-ali-track="clicked_model_static_compare" data-event-label="<?php echo esc_attr($link['label']); ?>" data-event-surface="model_hub_comparison_paths" href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_model_request_filters() {
    return array(
        'q' => kingy_ali_sanitize_public_search_query(kingy_ali_request_get_value('kali_q')),
        'provider' => kingy_ali_sanitize_slug_filter(kingy_ali_request_get_value('kali_model_provider')),
        'family' => kingy_ali_sanitize_slug_filter(kingy_ali_request_get_value('kali_model_family')),
        'modality' => kingy_ali_sanitize_slug_filter(kingy_ali_request_get_value('kali_model_modality')),
        'use_case' => kingy_ali_sanitize_slug_filter(kingy_ali_request_get_value('kali_model_use_case')),
        'access_type' => kingy_ali_sanitize_slug_filter(kingy_ali_request_get_value('kali_model_access_type')),
        'license_type' => kingy_ali_sanitize_slug_filter(kingy_ali_request_get_value('kali_model_license_type')),
        'status' => kingy_ali_sanitize_slug_filter(kingy_ali_request_get_value('kali_model_status')),
        'api_available' => kingy_ali_sanitize_yes_no_filter(kingy_ali_request_get_value('kali_api_available')),
        'open_weight' => kingy_ali_sanitize_yes_no_filter(kingy_ali_request_get_value('kali_open_weight')),
        'local_available' => kingy_ali_sanitize_yes_no_filter(kingy_ali_request_get_value('kali_local_available')),
    );
}

function kingy_ali_model_directory_has_filters($filters) {
    return (bool) array_filter($filters, function ($value) {
        return $value !== '' && $value !== null;
    });
}

function kingy_ali_model_search_meta_keys() {
    return array(
        'provider_name',
        'model_family_name',
        'model_overview',
        'what_changed',
        'best_for',
        'skip_if',
        'strengths',
        'weaknesses',
        'pricing',
        'api_pricing',
        'context_window',
        'agent_suitability',
        'coding_notes',
        'reasoning_notes',
        'creative_notes',
        'research_notes',
        'license_notes',
        'benchmark_summary',
        'kingy_verdict',
    );
}

function kingy_ali_query_model_directory($args = array()) {
    $defaults = array(
        'limit' => 24,
        'q' => '',
        'provider' => '',
        'family' => '',
        'modality' => '',
        'use_case' => '',
        'access_type' => '',
        'license_type' => '',
        'status' => '',
        'api_available' => '',
        'open_weight' => '',
        'local_available' => '',
        'track_search' => false,
    );
    $args = wp_parse_args($args, $defaults);
    $limit = absint($args['limit']);

    $query_args = array(
        'post_type' => 'kingy_ai_model',
        'post_status' => 'publish',
        'posts_per_page' => kingy_ali_public_query_batch_size($limit),
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
    );

    if ($args['q']) {
        $matching_ids = kingy_ali_directory_matching_post_ids('kingy_ai_model', kingy_ali_model_search_meta_keys(), array_keys(kingy_ali_model_filter_taxonomy_map()), $args['q']);
        $query_args['post__in'] = $matching_ids ? $matching_ids : array(0);
    }

    kingy_ali_apply_model_tax_filters($query_args, $args);
    kingy_ali_apply_model_meta_filters($query_args, $args);
    kingy_ali_apply_public_noindex_meta_constraint($query_args);

    $query = kingy_ali_run_public_filtered_query(
        $query_args,
        $limit,
        function ($post) {
            return kingy_ali_public_query_accepts_index_ready_post($post);
        }
    );

    if ($args['track_search'] && function_exists('kingy_ali_track_directory_search')) {
        kingy_ali_track_directory_search('model_directory', $args, $query);
    }

    return $query;
}

function kingy_ali_model_filter_taxonomy_map() {
    return array(
        'model_provider' => 'provider',
        'model_family' => 'family',
        'model_modality' => 'modality',
        'model_use_case' => 'use_case',
        'model_access_type' => 'access_type',
        'model_license_type' => 'license_type',
        'model_status' => 'status',
    );
}

function kingy_ali_apply_model_tax_filters(&$query_args, $args) {
    $tax_query = array();
    foreach (kingy_ali_model_filter_taxonomy_map() as $taxonomy => $arg_key) {
        $slug = isset($args[$arg_key]) ? $args[$arg_key] : '';
        if (!$slug) {
            continue;
        }

        $tax_query[] = array(
            'taxonomy' => $taxonomy,
            'field' => 'slug',
            'terms' => function_exists('kingy_ali_public_filter_slug_terms') ? kingy_ali_public_filter_slug_terms($taxonomy, $slug) : $slug,
        );
    }

    if ($tax_query) {
        $query_args['tax_query'] = $tax_query;
    }
}

function kingy_ali_apply_model_meta_filters(&$query_args, $args) {
    $meta_query = array();
    foreach (array('api_available', 'open_weight', 'local_available') as $key) {
        if (!empty($args[$key])) {
            $meta_query[] = array(
                'key' => kingy_ali_meta_key($key),
                'value' => $args[$key],
                'compare' => '=',
            );
        }
    }

    if ($meta_query) {
        $query_args['meta_query'] = $meta_query;
    }
}

function kingy_ali_render_model_directory_filters($filters) {
    $has_filters = kingy_ali_model_directory_has_filters($filters);

    ob_start();
    ?>
    <form class="kingy-ali-search kingy-ali-model-search" method="get">
        <div class="kingy-ali-search__bar">
            <label class="screen-reader-text" for="kingy-ali-model-q"><?php esc_html_e('Search AI models', 'kingy-ai-launch-intelligence'); ?></label>
            <input id="kingy-ali-model-q" type="search" name="kali_q" value="<?php echo esc_attr($filters['q']); ?>" placeholder="<?php esc_attr_e('Search models, providers, capabilities, pricing, context, and use cases...', 'kingy-ai-launch-intelligence'); ?>">
            <button type="submit"><?php esc_html_e('Search', 'kingy-ai-launch-intelligence'); ?></button>
            <?php if ($has_filters) : ?>
                <a class="kingy-ali-search__reset" data-kingy-ali-track="clicked_model_directory_reset" data-event-label="<?php esc_attr_e('Reset model filters', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_directory_filters" href="<?php echo esc_url(home_url('/ai-models/')); ?>"><?php esc_html_e('Reset', 'kingy-ai-launch-intelligence'); ?></a>
            <?php endif; ?>
        </div>
        <div class="kingy-ali-filter-grid kingy-ali-model-filter-grid">
            <?php kingy_ali_render_term_select('kali_model_provider', __('Provider', 'kingy-ai-launch-intelligence'), kingy_ali_public_filter_terms('model_provider'), $filters['provider']); ?>
            <?php kingy_ali_render_term_select('kali_model_family', __('Family', 'kingy-ai-launch-intelligence'), kingy_ali_public_filter_terms('model_family'), $filters['family']); ?>
            <?php kingy_ali_render_term_select('kali_model_modality', __('Modality', 'kingy-ai-launch-intelligence'), kingy_ali_public_filter_terms('model_modality'), $filters['modality']); ?>
            <?php kingy_ali_render_term_select('kali_model_use_case', __('Use case', 'kingy-ai-launch-intelligence'), kingy_ali_public_filter_terms('model_use_case'), $filters['use_case']); ?>
            <?php kingy_ali_render_term_select('kali_model_access_type', __('Access', 'kingy-ai-launch-intelligence'), kingy_ali_public_filter_terms('model_access_type'), $filters['access_type']); ?>
            <?php kingy_ali_render_term_select('kali_model_license_type', __('License', 'kingy-ai-launch-intelligence'), kingy_ali_public_filter_terms('model_license_type'), $filters['license_type']); ?>
            <?php kingy_ali_render_term_select('kali_model_status', __('Status', 'kingy-ai-launch-intelligence'), kingy_ali_public_filter_terms('model_status'), $filters['status']); ?>
            <?php kingy_ali_render_yes_no_select('kali_api_available', __('API', 'kingy-ai-launch-intelligence'), $filters['api_available']); ?>
            <?php kingy_ali_render_yes_no_select('kali_open_weight', __('Open weights', 'kingy-ai-launch-intelligence'), $filters['open_weight']); ?>
            <?php kingy_ali_render_yes_no_select('kali_local_available', __('Local', 'kingy-ai-launch-intelligence'), $filters['local_available']); ?>
        </div>
    </form>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_model_directory_grid($query) {
    ob_start();
    if ($query->have_posts()) {
        echo '<div class="kingy-ali-grid kingy-ali-model-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            echo kingy_ali_render_model_card(get_the_ID());
        }
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo kingy_ali_render_model_empty_state();
    }

    return ob_get_clean();
}

function kingy_ali_render_model_empty_state() {
    ob_start();
    ?>
    <div class="kingy-ali-empty">
        <h3><?php esc_html_e('No source-ready AI model profiles match yet.', 'kingy-ai-launch-intelligence'); ?></h3>
        <p><?php esc_html_e('Kingy AI is building source-backed AI model profiles with provider, access, pricing, modality, open-weight status, benchmark caveats, official links, and last-verified notes. Start with the AI Tool Directory and Open-Weight Model Launches while the model profile database is being expanded.', 'kingy-ai-launch-intelligence'); ?></p>
        <div class="kingy-ali-cta-row">
            <a data-kingy-ali-track="clicked_ai_tools_cta" data-event-label="<?php esc_attr_e('AI Tools from model no results', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_no_results" href="<?php echo esc_url(home_url('/ai-tools/')); ?>"><?php esc_html_e('AI Tools', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_open_weight_launches_cta" data-event-label="<?php esc_attr_e('Open-weight launches from model no results', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_no_results" href="<?php echo esc_url(home_url('/ai-launches/open-weight-models/')); ?>"><?php esc_html_e('Open-Weight Model Launches', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_launch_hub_cta" data-event-label="<?php esc_attr_e('AI Launch Intelligence from model no results', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_no_results" href="<?php echo esc_url(home_url('/ai-launches/')); ?>"><?php esc_html_e('AI Launch Intelligence', 'kingy-ai-launch-intelligence'); ?></a>
            <?php if (kingy_ali_model_compare_page_is_public()) : ?>
                <a data-kingy-ali-track="clicked_compare_models_cta" data-event-label="<?php esc_attr_e('Compare AI Models from model no results', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_no_results" href="<?php echo esc_url(home_url('/compare-ai-models/')); ?>"><?php esc_html_e('Compare AI Models', 'kingy-ai-launch-intelligence'); ?></a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function kingy_ali_model_compare_page_is_public() {
    if (!function_exists('get_page_by_path')) {
        return false;
    }

    $page = get_page_by_path('compare-ai-models', OBJECT, 'page');
    return $page && get_post_status($page) === 'publish';
}

function kingy_ali_model_hub_is_public() {
    return post_type_exists('kingy_ai_model') && (bool) get_post_type_archive_link('kingy_ai_model');
}

function kingy_ali_render_model_card($post_id) {
    $summary = kingy_ali_model_summary($post_id);
    $related_launch_id = kingy_ali_public_profile_id(kingy_ali_get_meta($post_id, 'related_launch_id'));
    if (!kingy_ali_related_post_is_public_index_ready($related_launch_id, 'kingy_ai_launch')) {
        $related_launch_id = 0;
    }

    ob_start();
    ?>
    <article class="kingy-ali-card kingy-ali-model-card">
        <div class="kingy-ali-card__meta">
            <span><?php echo esc_html(kingy_ali_model_terms_to_string($post_id, 'model_modality', __('AI model', 'kingy-ai-launch-intelligence'))); ?></span>
        </div>
        <h3><a data-kingy-ali-track="clicked_model" data-object-id="<?php echo esc_attr($post_id); ?>" data-event-surface="model_directory_card" href="<?php echo esc_url(get_permalink($post_id)); ?>"><?php echo esc_html(get_the_title($post_id)); ?></a></h3>
        <p><?php echo esc_html(wp_trim_words($summary, 30)); ?></p>
        <div class="kingy-ali-badges">
            <?php kingy_ali_render_fact_badge(__('API', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'api_available')); ?>
            <?php kingy_ali_render_fact_badge(__('Open weights', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'open_weight')); ?>
            <?php kingy_ali_render_fact_badge(__('Local', 'kingy-ai-launch-intelligence'), kingy_ali_model_text($post_id, 'local_available')); ?>
        </div>
        <dl class="kingy-ali-score-list">
            <div><dt><?php esc_html_e('Provider', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html(kingy_ali_model_provider_label($post_id)); ?></dd></div>
            <div><dt><?php esc_html_e('Context', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html(kingy_ali_model_text($post_id, 'context_window', __('Unknown', 'kingy-ai-launch-intelligence'))); ?></dd></div>
            <div><dt><?php esc_html_e('Last verified', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html(kingy_ali_model_text($post_id, 'last_verified', __('Unknown', 'kingy-ai-launch-intelligence'))); ?></dd></div>
        </dl>
        <div class="kingy-ali-card__actions">
            <a data-kingy-ali-track="clicked_model" data-object-id="<?php echo esc_attr($post_id); ?>" data-event-surface="model_directory_card" href="<?php echo esc_url(get_permalink($post_id)); ?>"><?php esc_html_e('View model', 'kingy-ai-launch-intelligence'); ?></a>
            <?php if ($related_launch_id) : ?>
                <a data-kingy-ali-track="clicked_launch" data-object-id="<?php echo esc_attr($related_launch_id); ?>" data-event-surface="model_directory_card" href="<?php echo esc_url(get_permalink($related_launch_id)); ?>"><?php esc_html_e('Launch profile', 'kingy-ai-launch-intelligence'); ?></a>
            <?php endif; ?>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

function kingy_ali_model_external_link($label, $url, $surface = 'model_profile_links') {
    $url = function_exists('kingy_ali_sanitize_public_profile_link_url') ? kingy_ali_sanitize_public_profile_link_url($url) : esc_url_raw($url, array('http', 'https'));
    if (!$url) {
        return;
    }

    $rel = function_exists('kingy_ali_source_link_target_attrs') ? kingy_ali_source_link_target_attrs($url) : '';
    echo '<a data-kingy-ali-track="clicked_source_link" data-event-label="' . esc_attr($label) . '" data-event-surface="' . esc_attr($surface) . '" href="' . esc_url($url) . '"' . $rel . '>' . esc_html($label) . '</a>';
}

function kingy_ali_model_related_article_label($url) {
    $url = function_exists('kingy_ali_sanitize_public_profile_link_url') ? kingy_ali_sanitize_public_profile_link_url($url) : esc_url_raw($url, array('http', 'https'));
    if (!$url) {
        return __('Related article', 'kingy-ai-launch-intelligence');
    }

    $path = (string) wp_parse_url($url, PHP_URL_PATH);
    if (strpos(untrailingslashit($path), '/ai-launches/open-weight-models') !== false) {
        return __('Open-weight model launches', 'kingy-ai-launch-intelligence');
    }

    return __('Related article', 'kingy-ai-launch-intelligence');
}

function kingy_ali_query_related_models_for_post($post_id, $meta_key, $limit = 8) {
    $post_id = absint($post_id);
    $limit = max(1, min(12, absint($limit)));
    $allowed_meta_keys = array('related_launch_id', 'related_tool_id', 'related_company_id');
    if (!$post_id || !in_array($meta_key, $allowed_meta_keys, true)) {
        return array();
    }

    $query_args = array(
        'post_type' => 'kingy_ai_model',
        'post_status' => 'publish',
        'posts_per_page' => kingy_ali_public_query_batch_size($limit),
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
        'meta_query' => array(
            array(
                'key' => kingy_ali_meta_key($meta_key),
                'value' => (string) $post_id,
                'compare' => '=',
            ),
        ),
    );

    if (function_exists('kingy_ali_apply_public_noindex_meta_constraint')) {
        kingy_ali_apply_public_noindex_meta_constraint($query_args);
    }

    $query = function_exists('kingy_ali_run_public_filtered_query')
        ? kingy_ali_run_public_filtered_query($query_args, $limit, 'kingy_ali_public_query_accepts_index_ready_post')
        : new WP_Query($query_args);

    $ids = array();
    if ($query && $query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $ids[] = get_the_ID();
        }
        wp_reset_postdata();
    }

    $ids = array_slice(array_values(array_unique(array_map('absint', $ids))), 0, $limit);

    if (!$ids && $meta_key === 'related_company_id') {
        $ids = kingy_ali_query_related_models_for_company_provider($post_id, $limit);
    }

    return $ids;
}

function kingy_ali_model_provider_fallback_for_company($company_id) {
    $company_id = absint($company_id);
    if (!$company_id || get_post_type($company_id) !== 'kingy_ai_company') {
        return '';
    }

    $slug = (string) get_post_field('post_name', $company_id);
    $providers_by_slug = array(
        'openai' => 'OpenAI',
        'anthropic' => 'Anthropic',
        'google' => 'Google',
        'google-deepmind' => 'Google',
        'meta' => 'Meta',
        'mistral-ai' => 'Mistral AI',
        'deepseek' => 'DeepSeek',
        'xai' => 'xAI',
        'alibaba-qwen-team' => 'Qwen',
    );

    return isset($providers_by_slug[$slug]) ? $providers_by_slug[$slug] : '';
}

function kingy_ali_model_provider_slug_fallback_for_company($company_id) {
    $company_id = absint($company_id);
    if (!$company_id || get_post_type($company_id) !== 'kingy_ai_company') {
        return '';
    }

    $slug = (string) get_post_field('post_name', $company_id);
    $providers_by_slug = array(
        'openai' => 'openai',
        'anthropic' => 'anthropic',
        'google' => 'google',
        'google-deepmind' => 'google',
        'meta' => 'meta',
        'mistral-ai' => 'mistral-ai',
        'deepseek' => 'deepseek',
        'xai' => 'xai',
        'alibaba-qwen-team' => 'qwen',
    );

    return isset($providers_by_slug[$slug]) ? $providers_by_slug[$slug] : '';
}

function kingy_ali_query_related_models_for_company_provider($company_id, $limit = 8) {
    $provider_slug = kingy_ali_model_provider_slug_fallback_for_company($company_id);
    if ($provider_slug === '') {
        return array();
    }

    $limit = max(1, min(12, absint($limit)));
    $query_args = array(
        'post_type' => 'kingy_ai_model',
        'post_status' => 'publish',
        'posts_per_page' => kingy_ali_public_query_batch_size($limit),
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
        'tax_query' => array(
            array(
                'taxonomy' => 'model_provider',
                'field' => 'slug',
                'terms' => function_exists('kingy_ali_public_filter_slug_terms') ? kingy_ali_public_filter_slug_terms('model_provider', $provider_slug) : $provider_slug,
            ),
        ),
    );

    if (function_exists('kingy_ali_apply_public_noindex_meta_constraint')) {
        kingy_ali_apply_public_noindex_meta_constraint($query_args);
    }

    $query = function_exists('kingy_ali_run_public_filtered_query')
        ? kingy_ali_run_public_filtered_query($query_args, $limit, 'kingy_ali_public_query_accepts_index_ready_post')
        : new WP_Query($query_args);

    $ids = array();
    if ($query && $query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $ids[] = get_the_ID();
        }
        wp_reset_postdata();
    }

    return array_slice(array_values(array_unique(array_map('absint', $ids))), 0, $limit);
}

function kingy_ali_render_related_model_profile_panel($post_id, $meta_key, $surface, $limit = 8) {
    $model_ids = kingy_ali_query_related_models_for_post($post_id, $meta_key, $limit);
    if (!$model_ids) {
        return;
    }
    ?>
    <section class="kingy-ali-link-panel kingy-ali-related-models">
        <h2><?php esc_html_e('Related AI Model Profiles', 'kingy-ai-launch-intelligence'); ?></h2>
        <p><?php esc_html_e('These source-backed model profiles link back to this record in the Kingy AI graph. Treat them as research paths, not rankings.', 'kingy-ai-launch-intelligence'); ?></p>
        <div class="kingy-ali-link-list">
            <?php foreach ($model_ids as $model_id) : ?>
                <a data-kingy-ali-track="clicked_model" data-object-id="<?php echo esc_attr($model_id); ?>" data-event-surface="<?php echo esc_attr($surface); ?>" href="<?php echo esc_url(get_permalink($model_id)); ?>"><?php echo esc_html(get_the_title($model_id)); ?></a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function kingy_ali_model_fact($label, $value) {
    $value = function_exists('kingy_ali_public_profile_text') ? kingy_ali_public_profile_text($value) : (is_scalar($value) ? trim((string) $value) : '');
    if ($value === '') {
        $value = __('Unknown', 'kingy-ai-launch-intelligence');
    }

    echo '<div><dt>' . esc_html($label) . '</dt><dd>' . esc_html($value) . '</dd></div>';
}

function kingy_ali_model_text_panel($title, $body) {
    $body = function_exists('kingy_ali_public_profile_text') ? kingy_ali_public_profile_text($body) : (is_scalar($body) ? trim((string) $body) : '');
    if (!$body) {
        return;
    }

    echo '<div class="kingy-ali-text-panel"><h3>' . esc_html($title) . '</h3><p>' . esc_html($body) . '</p></div>';
}

function kingy_ali_public_model_choices($selected_ids = array(), $limit = 160) {
    $query = new WP_Query(
        array(
            'post_type' => 'kingy_ai_model',
            'post_status' => 'publish',
            'posts_per_page' => max(1, absint($limit)),
            'fields' => 'ids',
            'orderby' => 'title',
            'order' => 'ASC',
            'no_found_rows' => true,
        )
    );

    $ids = is_array($query->posts) ? array_map('absint', $query->posts) : array();
    $selected_ids = is_array($selected_ids) ? $selected_ids : array($selected_ids);
    $selected_ids = array_values(array_filter(array_map('absint', $selected_ids)));
    foreach ($selected_ids as $selected_id) {
        if ($selected_id && get_post_type($selected_id) === 'kingy_ai_model' && !in_array($selected_id, $ids, true)) {
            array_unshift($ids, $selected_id);
        }
    }

    $choices = array();
    foreach (array_values(array_unique($ids)) as $post_id) {
        if (!kingy_ali_model_is_comparison_selectable($post_id)) {
            continue;
        }

        $provider = kingy_ali_model_provider_label($post_id);
        $choices[] = array(
            'id' => $post_id,
            'label' => $provider && $provider !== __('Unknown', 'kingy-ai-launch-intelligence') ? get_the_title($post_id) . ' - ' . $provider : get_the_title($post_id),
        );
    }

    return $choices;
}

function kingy_ali_model_is_comparison_selectable($post_id) {
    $post_id = absint($post_id);
    if (
        !$post_id
        || get_post_type($post_id) !== 'kingy_ai_model'
        || get_post_status($post_id) !== 'publish'
        || !kingy_ali_related_post_is_public_index_ready($post_id, 'kingy_ai_model')
    ) {
        return false;
    }

    return !kingy_ali_model_comparison_readiness_issues($post_id);
}

function kingy_ali_selected_model_id($query_key) {
    $value = kingy_ali_request_get_value($query_key);
    $model_id = absint($value);
    if (!$model_id || !kingy_ali_model_is_comparison_selectable($model_id)) {
        return 0;
    }

    return $model_id;
}

function kingy_ali_shortcode_model_compare($atts = array()) {
    try {
        return kingy_ali_shortcode_model_compare_inner($atts);
    } catch (Throwable $throwable) {
        return kingy_ali_render_model_emergency_safe_fallback('compare', $atts, $throwable);
    }
}

function kingy_ali_shortcode_model_compare_inner($atts = array()) {
    kingy_ali_enqueue_model_assets();
    $atts = shortcode_atts(array('heading' => 'yes'), $atts, 'kingy_ai_model_compare');
    $model_a = kingy_ali_selected_model_id('kali_model_a');
    $model_b = kingy_ali_selected_model_id('kali_model_b');
    $choices = kingy_ali_public_model_choices(array($model_a, $model_b));

    ob_start();
    echo '<section class="kingy-ali-model-compare">';
    if ($atts['heading'] !== 'no') {
        kingy_ali_render_directory_hero(
            __('Compare AI Models', 'kingy-ai-launch-intelligence'),
            __('Kingy AI compares AI models using source-backed model profile data, including provider, access, pricing, modality, open-weight status, benchmark caveats, official sources, and last-verified notes.', 'kingy-ai-launch-intelligence')
        );
    }
    echo '<div class="kingy-ali-model-disclosure"><strong>' . esc_html__('Comparison caveat', 'kingy-ai-launch-intelligence') . '</strong><span>' . esc_html(kingy_ali_model_benchmark_caveat_note()) . '</span></div>';
    echo kingy_ali_render_model_compare_dimensions();
    echo kingy_ali_render_model_compare_guide_links();
    echo kingy_ali_render_model_compare_methodology_note();
    echo kingy_ali_render_model_compare_form($choices, $model_a, $model_b);

    if (!$choices) {
        echo kingy_ali_render_model_compare_empty_state();
        echo kingy_ali_render_model_compare_cta();
    } elseif ($model_a && $model_b && $model_a === $model_b) {
        echo kingy_ali_render_model_compare_notice(__('Choose two different source-ready model profiles to create a useful comparison.', 'kingy-ai-launch-intelligence'));
        echo kingy_ali_render_model_compare_cta($model_a, $model_b);
    } elseif ($model_a && $model_b) {
        echo kingy_ali_render_model_compare_readiness_panel($model_a, $model_b);
        echo kingy_ali_render_model_compare_table($model_a, $model_b);
        echo kingy_ali_render_model_compare_source_panels($model_a, $model_b);
        echo kingy_ali_render_model_compare_cta($model_a, $model_b);
    } else {
        echo kingy_ali_render_model_compare_notice(__('Select two source-ready model profiles to compare. Profiles with missing sources, stale verification, rumored status, or readiness noindex gates are held back from this selector.', 'kingy-ai-launch-intelligence'));
        echo kingy_ali_render_model_compare_cta();
    }

    echo '</section>';

    return ob_get_clean();
}

function kingy_ali_render_model_compare_dimensions() {
    $dimensions = array(
        __('Best for coding', 'kingy-ai-launch-intelligence'),
        __('Best for agents', 'kingy-ai-launch-intelligence'),
        __('Best for local/private use', 'kingy-ai-launch-intelligence'),
        __('Best open-weight option', 'kingy-ai-launch-intelligence'),
        __('Best for creators', 'kingy-ai-launch-intelligence'),
        __('Best for business workflows', 'kingy-ai-launch-intelligence'),
        __('Best for multimodal work', 'kingy-ai-launch-intelligence'),
        __('Best for cost-sensitive teams', 'kingy-ai-launch-intelligence'),
    );

    ob_start();
    ?>
    <section class="kingy-ali-model-compare-dimensions">
        <h2><?php esc_html_e('Comparison Dimensions', 'kingy-ai-launch-intelligence'); ?></h2>
        <p><?php esc_html_e('These are editorial comparison lanes to fill as source-backed model records mature. Kingy AI does not publish unsupported rankings from missing or unverified data.', 'kingy-ai-launch-intelligence'); ?></p>
        <ul>
            <?php foreach ($dimensions as $dimension) : ?>
                <li><?php echo esc_html($dimension); ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_model_compare_guide_links() {
    $guides = array(
        array('label' => __('Best AI coding models', 'kingy-ai-launch-intelligence'), 'url' => home_url('/best-ai-coding-models/')),
        array('label' => __('Best open-weight AI models', 'kingy-ai-launch-intelligence'), 'url' => home_url('/best-open-weight-ai-models/')),
        array('label' => __('Best local AI models', 'kingy-ai-launch-intelligence'), 'url' => home_url('/best-local-ai-models/')),
        array('label' => __('Best AI models for agents', 'kingy-ai-launch-intelligence'), 'url' => home_url('/best-ai-models-for-agents/')),
        array('label' => __('Best multimodal AI models', 'kingy-ai-launch-intelligence'), 'url' => home_url('/best-multimodal-ai-models/')),
    );
    $static_guides = array();
    if (function_exists('kingy_ali_model_static_compare_page_configs')) {
        foreach (kingy_ali_model_static_compare_page_configs() as $key => $config) {
            if (empty($config['label'])) {
                continue;
            }

            $static_guides[] = array(
                'label' => $config['label'],
                'url' => kingy_ali_model_static_compare_page_url($key),
            );
        }
    }

    ob_start();
    ?>
    <section class="kingy-ali-link-panel kingy-ali-model-compare-guides">
        <h2><?php esc_html_e('Source-Backed Comparison Workbenches', 'kingy-ai-launch-intelligence'); ?></h2>
        <p><?php esc_html_e('These noindex-safe guides group verified model profiles by workflow. They are comparison workbenches, not final rankings.', 'kingy-ai-launch-intelligence'); ?></p>
        <div class="kingy-ali-link-list">
            <?php foreach ($guides as $guide) : ?>
                <a data-kingy-ali-track="clicked_model_compare_guide" data-event-label="<?php echo esc_attr($guide['label']); ?>" data-event-surface="model_compare_guides" href="<?php echo esc_url($guide['url']); ?>"><?php echo esc_html($guide['label']); ?></a>
            <?php endforeach; ?>
        </div>
        <?php if ($static_guides) : ?>
            <h3><?php esc_html_e('Provider and family comparison pages', 'kingy-ai-launch-intelligence'); ?></h3>
            <div class="kingy-ali-link-list">
                <?php foreach ($static_guides as $guide) : ?>
                    <a data-kingy-ali-track="clicked_model_static_compare" data-event-label="<?php echo esc_attr($guide['label']); ?>" data-event-surface="model_compare_static_guides" href="<?php echo esc_url($guide['url']); ?>"><?php echo esc_html($guide['label']); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_model_compare_empty_state() {
    ob_start();
    ?>
    <div class="kingy-ali-empty">
        <h3><?php esc_html_e('Source-backed comparison tables are being expanded.', 'kingy-ai-launch-intelligence'); ?></h3>
        <p><?php esc_html_e('Kingy AI is expanding source-backed AI model comparison data across provider, access, pricing, modality, open-weight status, benchmark caveats, and last-verified notes. Use the AI Model Intelligence Hub and Open-Weight Model Launches while comparison tables are being filled.', 'kingy-ai-launch-intelligence'); ?></p>
        <div class="kingy-ali-cta-row">
            <?php if (kingy_ali_model_hub_is_public()) : ?>
                <a data-kingy-ali-track="clicked_model_hub_cta" data-event-label="<?php esc_attr_e('AI Model Intelligence Hub from compare no results', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_compare_no_results" href="<?php echo esc_url(home_url('/ai-models/')); ?>"><?php esc_html_e('AI Model Intelligence Hub', 'kingy-ai-launch-intelligence'); ?></a>
            <?php endif; ?>
            <a data-kingy-ali-track="clicked_open_weight_launches_cta" data-event-label="<?php esc_attr_e('Open-weight launches from compare no results', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_compare_no_results" href="<?php echo esc_url(home_url('/ai-launches/open-weight-models/')); ?>"><?php esc_html_e('Open-Weight Model Launches', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_ai_tools_cta" data-event-label="<?php esc_attr_e('AI Tools from compare no results', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_compare_no_results" href="<?php echo esc_url(home_url('/ai-tools/')); ?>"><?php esc_html_e('AI Tools', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_launch_hub_cta" data-event-label="<?php esc_attr_e('AI Launch Intelligence from compare no results', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_compare_no_results" href="<?php echo esc_url(home_url('/ai-launches/')); ?>"><?php esc_html_e('AI Launch Intelligence', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_model_compare_methodology_note() {
    ob_start();
    ?>
    <div class="kingy-ali-model-compare-note">
        <strong><?php esc_html_e('How to read this comparison', 'kingy-ai-launch-intelligence'); ?></strong>
        <p><?php esc_html_e('Kingy AI compares stored model profile fields that have passed source and readiness gates. Treat the table as a decision aid, not a benchmark ranking: pricing, limits, availability, and model behavior can change, and the better fit depends on the workflow being tested.', 'kingy-ai-launch-intelligence'); ?></p>
    </div>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_model_compare_notice($message) {
    return '<div class="kingy-ali-model-compare-notice">' . esc_html($message) . '</div>';
}

function kingy_ali_render_model_compare_form($choices, $model_a, $model_b) {
    ob_start();
    ?>
    <form class="kingy-ali-model-compare-form" method="get" data-kingy-model-compare-form>
        <label>
            <span><?php esc_html_e('Model A', 'kingy-ai-launch-intelligence'); ?></span>
            <select name="kali_model_a" data-kingy-model-compare-select>
                <option value=""><?php esc_html_e('Choose a model', 'kingy-ai-launch-intelligence'); ?></option>
                <?php foreach ($choices as $choice) : ?>
                    <option value="<?php echo esc_attr($choice['id']); ?>" <?php selected($model_a, $choice['id']); ?>><?php echo esc_html($choice['label']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span><?php esc_html_e('Model B', 'kingy-ai-launch-intelligence'); ?></span>
            <select name="kali_model_b" data-kingy-model-compare-select>
                <option value=""><?php esc_html_e('Choose a model', 'kingy-ai-launch-intelligence'); ?></option>
                <?php foreach ($choices as $choice) : ?>
                    <option value="<?php echo esc_attr($choice['id']); ?>" <?php selected($model_b, $choice['id']); ?>><?php echo esc_html($choice['label']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit"><?php esc_html_e('Compare', 'kingy-ai-launch-intelligence'); ?></button>
        <?php if ($model_a || $model_b) : ?>
            <a href="<?php echo esc_url(home_url('/compare-ai-models/')); ?>"><?php esc_html_e('Reset', 'kingy-ai-launch-intelligence'); ?></a>
        <?php endif; ?>
    </form>
    <?php
    return ob_get_clean();
}

function kingy_ali_model_compare_rows() {
    return array(
        array('label' => __('Identity', 'kingy-ai-launch-intelligence'), 'type' => 'section'),
        array('label' => __('Model profile', 'kingy-ai-launch-intelligence'), 'type' => 'profile'),
        array('label' => __('Provider', 'kingy-ai-launch-intelligence'), 'type' => 'provider'),
        array('label' => __('Family', 'kingy-ai-launch-intelligence'), 'type' => 'taxonomy_meta', 'taxonomy' => 'model_family', 'key' => 'model_family_name'),
        array('label' => __('Status', 'kingy-ai-launch-intelligence'), 'type' => 'taxonomy_meta', 'taxonomy' => 'model_status', 'key' => 'model_status_note'),
        array('label' => __('Release date', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'release_date'),
        array('label' => __('Verification status', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'verification_status'),
        array('label' => __('Last verified', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'last_verified'),
        array('label' => __('Capabilities', 'kingy-ai-launch-intelligence'), 'type' => 'section'),
        array('label' => __('Modalities', 'kingy-ai-launch-intelligence'), 'type' => 'taxonomy', 'taxonomy' => 'model_modality'),
        array('label' => __('Use cases', 'kingy-ai-launch-intelligence'), 'type' => 'taxonomy', 'taxonomy' => 'model_use_case'),
        array('label' => __('Context window', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'context_window'),
        array('label' => __('Output limit', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'output_limit'),
        array('label' => __('Tool/function calling', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'tool_calling'),
        array('label' => __('Fine-tuning', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'fine_tuning'),
        array('label' => __('Coding notes', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'coding_notes'),
        array('label' => __('Reasoning notes', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'reasoning_notes'),
        array('label' => __('Creative notes', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'creative_notes'),
        array('label' => __('Research notes', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'research_notes'),
        array('label' => __('Agent suitability', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'agent_suitability'),
        array('label' => __('Access and cost', 'kingy-ai-launch-intelligence'), 'type' => 'section'),
        array('label' => __('Access types', 'kingy-ai-launch-intelligence'), 'type' => 'taxonomy', 'taxonomy' => 'model_access_type'),
        array('label' => __('API availability', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'api_available'),
        array('label' => __('Web app availability', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'web_app_available'),
        array('label' => __('Local/self-hosted', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'local_available'),
        array('label' => __('Open weights', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'open_weight'),
        array('label' => __('Open source', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'open_source'),
        array('label' => __('License type', 'kingy-ai-launch-intelligence'), 'type' => 'taxonomy', 'taxonomy' => 'model_license_type'),
        array('label' => __('License notes', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'license_notes'),
        array('label' => __('Pricing notes', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'pricing'),
        array('label' => __('API pricing', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'api_pricing'),
        array('label' => __('Hardware requirements', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'hardware_requirements'),
        array('label' => __('Editorial fit', 'kingy-ai-launch-intelligence'), 'type' => 'section'),
        array('label' => __('Best for', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'best_for'),
        array('label' => __('Skip if', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'skip_if'),
        array('label' => __('Strengths', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'strengths'),
        array('label' => __('Weaknesses', 'kingy-ai-launch-intelligence'), 'type' => 'meta', 'key' => 'weaknesses'),
        array('label' => __('Alternatives/comparison URL', 'kingy-ai-launch-intelligence'), 'type' => 'url', 'key' => 'alternatives_url'),
        array('label' => __('Trust and sources', 'kingy-ai-launch-intelligence'), 'type' => 'section'),
        array('label' => __('Benchmark caveat', 'kingy-ai-launch-intelligence'), 'type' => 'caveat'),
        array('label' => __('Official and source URLs', 'kingy-ai-launch-intelligence'), 'type' => 'sources'),
    );
}

function kingy_ali_model_compare_value($post_id, $row) {
    if ($row['type'] === 'provider') {
        return kingy_ali_model_provider_label($post_id);
    }

    if ($row['type'] === 'taxonomy') {
        return kingy_ali_model_terms_to_string($post_id, $row['taxonomy'], __('Unknown', 'kingy-ai-launch-intelligence'));
    }

    if ($row['type'] === 'caveat') {
        return kingy_ali_model_benchmark_caveat_note($post_id);
    }

    return kingy_ali_model_text($post_id, $row['key'], __('Unknown', 'kingy-ai-launch-intelligence'));
}

function kingy_ali_model_compare_cell_html($post_id, $row) {
    if ($row['type'] === 'profile') {
        return kingy_ali_model_compare_profile_link($post_id);
    }

    if ($row['type'] === 'provider') {
        return kingy_ali_model_compare_text_html(kingy_ali_model_provider_label($post_id));
    }

    if ($row['type'] === 'taxonomy') {
        return kingy_ali_model_compare_text_html(kingy_ali_model_terms_to_string($post_id, $row['taxonomy']));
    }

    if ($row['type'] === 'taxonomy_meta') {
        return kingy_ali_model_compare_text_html(kingy_ali_model_text($post_id, $row['key'], kingy_ali_model_terms_to_string($post_id, $row['taxonomy'])));
    }

    if ($row['type'] === 'caveat') {
        $caveat = kingy_ali_model_text($post_id, 'benchmark_caveat');
        return $caveat !== ''
            ? kingy_ali_model_compare_text_html($caveat)
            : kingy_ali_model_compare_unknown_html(__('Benchmark caveat not yet entered.', 'kingy-ai-launch-intelligence'));
    }

    if ($row['type'] === 'sources') {
        return kingy_ali_model_compare_source_links_html($post_id);
    }

    if ($row['type'] === 'url') {
        return kingy_ali_model_compare_url_html($post_id, $row['key']);
    }

    return kingy_ali_model_compare_text_html(kingy_ali_model_text($post_id, $row['key']));
}

function kingy_ali_model_compare_profile_link($post_id) {
    $title = get_the_title($post_id);
    if ($title === '') {
        $title = __('Untitled model profile', 'kingy-ai-launch-intelligence');
    }

    if (!kingy_ali_model_is_comparison_selectable($post_id)) {
        return esc_html($title);
    }

    return '<a data-kingy-ali-track="clicked_model" data-object-id="' . esc_attr($post_id) . '" data-event-surface="model_compare_table" href="' . esc_url(get_permalink($post_id)) . '">' . esc_html($title) . '</a>';
}

function kingy_ali_model_compare_text_html($value) {
    $value = function_exists('kingy_ali_public_profile_text') ? kingy_ali_public_profile_text($value) : (is_scalar($value) ? trim((string) $value) : '');
    $value = kingy_ali_model_compare_value_label($value);
    if ($value === '') {
        return kingy_ali_model_compare_unknown_html();
    }

    return nl2br(esc_html($value));
}

function kingy_ali_model_compare_value_label($value) {
    if (!is_scalar($value)) {
        return '';
    }

    $value = trim((string) $value);
    $labels = array(
        'yes' => __('Yes', 'kingy-ai-launch-intelligence'),
        'no' => __('No', 'kingy-ai-launch-intelligence'),
        'unknown' => __('Unknown', 'kingy-ai-launch-intelligence'),
        'verified' => __('Verified', 'kingy-ai-launch-intelligence'),
        'partially_verified' => __('Partially verified', 'kingy-ai-launch-intelligence'),
        'founder_submitted' => __('Founder submitted', 'kingy-ai-launch-intelligence'),
        'outdated' => __('Outdated', 'kingy-ai-launch-intelligence'),
        'rumored' => __('Rumored / do not compare publicly', 'kingy-ai-launch-intelligence'),
    );

    $key = sanitize_key($value);
    return isset($labels[$key]) && $value === $key ? $labels[$key] : $value;
}

function kingy_ali_model_compare_unknown_html($message = '') {
    if ($message === '') {
        $message = __('Unknown / needs verification', 'kingy-ai-launch-intelligence');
    }

    return '<span class="kingy-ali-model-unknown">' . esc_html($message) . '</span>';
}

function kingy_ali_model_compare_url_html($post_id, $key) {
    $url = kingy_ali_model_url($post_id, $key);
    if (!$url) {
        return kingy_ali_model_compare_unknown_html();
    }

    $label = __('Open source link', 'kingy-ai-launch-intelligence');
    if ($key === 'alternatives_url') {
        $label = __('Open alternatives/comparison page', 'kingy-ai-launch-intelligence');
    }

    $rel = function_exists('kingy_ali_source_link_target_attrs') ? kingy_ali_source_link_target_attrs($url) : '';
    return '<a data-kingy-ali-track="clicked_source_link" data-event-label="' . esc_attr($label) . '" data-event-surface="model_compare_table" href="' . esc_url($url) . '"' . $rel . '>' . esc_html($label) . '</a>';
}

function kingy_ali_model_compare_source_links_html($post_id) {
    $links = function_exists('kingy_ali_public_source_links') ? kingy_ali_public_source_links($post_id) : array();
    if (!$links) {
        return kingy_ali_model_compare_unknown_html(__('No source links entered yet.', 'kingy-ai-launch-intelligence'));
    }

    ob_start();
    echo '<ul class="kingy-ali-model-source-list">';
    foreach (array_slice($links, 0, 12) as $link) {
        $label = isset($link['label']) ? (string) $link['label'] : __('Source', 'kingy-ai-launch-intelligence');
        $url = isset($link['url']) && is_scalar($link['url']) ? (string) $link['url'] : '';
        $url = function_exists('kingy_ali_sanitize_public_profile_link_url') ? kingy_ali_sanitize_public_profile_link_url($url) : esc_url_raw($url, array('http', 'https'));
        if (!$url) {
            continue;
        }

        $rel = function_exists('kingy_ali_source_link_target_attrs') ? kingy_ali_source_link_target_attrs($url) : '';
        echo '<li><a data-kingy-ali-track="clicked_source_link" data-event-label="' . esc_attr($label) . '" data-event-surface="model_compare_sources" href="' . esc_url($url) . '"' . $rel . '>' . esc_html($label) . '</a></li>';
    }
    if (count($links) > 12) {
        echo '<li><span>' . esc_html(sprintf(__('%d more sources on the model profile', 'kingy-ai-launch-intelligence'), count($links) - 12)) . '</span></li>';
    }
    echo '</ul>';

    $html = trim(ob_get_clean());
    return $html !== '<ul class="kingy-ali-model-source-list"></ul>' ? $html : kingy_ali_model_compare_unknown_html(__('No valid source links entered yet.', 'kingy-ai-launch-intelligence'));
}

function kingy_ali_model_comparison_readiness_issues($post_id) {
    $issues = array();
    $post_id = absint($post_id);
    if (!$post_id || get_post_type($post_id) !== 'kingy_ai_model') {
        return array(__('Unavailable model record.', 'kingy-ai-launch-intelligence'));
    }

    if (function_exists('kingy_ali_profile_should_noindex') && kingy_ali_profile_should_noindex($post_id)) {
        $issues[] = __('Profile is still held by the noindex/readiness gate.', 'kingy-ai-launch-intelligence');
    }

    $source_count = function_exists('kingy_ali_public_source_links') ? count(kingy_ali_public_source_links($post_id)) : 0;
    $has_official_source = function_exists('kingy_ali_model_has_official_indexable_source') ? kingy_ali_model_has_official_indexable_source($post_id) : $source_count > 0;
    if ($source_count < 1 || !$has_official_source) {
        $issues[] = __('Missing official/source links.', 'kingy-ai-launch-intelligence');
    }

    if (kingy_ali_model_text($post_id, 'benchmark_caveat') === '') {
        $issues[] = __('Missing benchmark caveat.', 'kingy-ai-launch-intelligence');
    }

    $verification_status = sanitize_key(kingy_ali_model_text($post_id, 'verification_status'));
    if ($verification_status === 'rumored' || (function_exists('kingy_ali_model_has_term_slug') && kingy_ali_model_has_term_slug($post_id, 'model_status', 'rumored'))) {
        $issues[] = __('Marked rumored.', 'kingy-ai-launch-intelligence');
    } elseif ($verification_status === 'outdated') {
        $issues[] = __('Marked outdated.', 'kingy-ai-launch-intelligence');
    } elseif ($verification_status === '') {
        $issues[] = __('Missing verification status.', 'kingy-ai-launch-intelligence');
    }

    if (function_exists('kingy_ali_last_verified_needs_update') && kingy_ali_last_verified_needs_update($post_id)) {
        $issues[] = __('Last verified date is missing or stale.', 'kingy-ai-launch-intelligence');
    }

    if (kingy_ali_model_comparison_signal_count($post_id) < 8) {
        $issues[] = __('Insufficient comparison detail for confident editorial guidance.', 'kingy-ai-launch-intelligence');
    }

    return array_values(array_unique($issues));
}

function kingy_ali_model_comparison_signal_count($post_id) {
    $count = 0;
    if (kingy_ali_model_provider_label($post_id) !== __('Unknown', 'kingy-ai-launch-intelligence')) {
        $count++;
    }

    foreach (array('model_family', 'model_modality', 'model_use_case', 'model_access_type', 'model_license_type', 'model_status') as $taxonomy) {
        if (kingy_ali_model_terms_to_string($post_id, $taxonomy) !== '') {
            $count++;
        }
    }

    foreach (array('release_date', 'context_window', 'output_limit', 'tool_calling', 'fine_tuning', 'api_available', 'web_app_available', 'local_available', 'open_weight', 'open_source', 'pricing', 'api_pricing', 'hardware_requirements', 'license_notes', 'best_for', 'strengths', 'weaknesses', 'agent_suitability', 'coding_notes', 'reasoning_notes', 'creative_notes', 'research_notes', 'alternatives_url') as $key) {
        if (kingy_ali_model_text($post_id, $key) !== '') {
            $count++;
        }
    }

    return $count;
}

function kingy_ali_render_model_compare_readiness_panel($model_a, $model_b) {
    ob_start();
    ?>
    <section class="kingy-ali-model-readiness" aria-label="<?php esc_attr_e('Model comparison readiness', 'kingy-ai-launch-intelligence'); ?>">
        <?php foreach (array($model_a, $model_b) as $model_id) : ?>
            <?php $issues = kingy_ali_model_comparison_readiness_issues($model_id); ?>
            <article class="kingy-ali-model-readiness__card <?php echo esc_attr($issues ? 'is-caution' : 'is-ready'); ?>">
                <h3><?php echo esc_html(get_the_title($model_id)); ?></h3>
                <?php if ($issues) : ?>
                    <p><?php esc_html_e('Needs editorial caution before using this comparison as a recommendation.', 'kingy-ai-launch-intelligence'); ?></p>
                    <ul>
                        <?php foreach ($issues as $issue) : ?>
                            <li><?php echo esc_html($issue); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p><?php esc_html_e('Ready for cautious side-by-side comparison based on currently verified Kingy AI fields.', 'kingy-ai-launch-intelligence'); ?></p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_model_compare_table($model_a, $model_b) {
    ob_start();
    ?>
    <div class="kingy-ali-model-compare-table-wrap">
        <table class="kingy-ali-model-compare-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Signal', 'kingy-ai-launch-intelligence'); ?></th>
                    <th><?php echo kingy_ali_model_compare_profile_link($model_a); ?></th>
                    <th><?php echo kingy_ali_model_compare_profile_link($model_b); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (kingy_ali_model_compare_rows() as $row) : ?>
                    <?php if ($row['type'] === 'section') : ?>
                        <tr class="kingy-ali-model-compare-section"><th colspan="3"><?php echo esc_html($row['label']); ?></th></tr>
                    <?php else : ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($row['label']); ?></th>
                            <td><?php echo kingy_ali_model_compare_cell_html($model_a, $row); ?></td>
                            <td><?php echo kingy_ali_model_compare_cell_html($model_b, $row); ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_model_compare_source_panels($model_a, $model_b) {
    ob_start();
    ?>
    <section class="kingy-ali-model-compare-sources">
        <h2><?php esc_html_e('Sources Used In This Comparison', 'kingy-ai-launch-intelligence'); ?></h2>
        <div class="kingy-ali-model-compare-source-grid">
            <?php foreach (array($model_a, $model_b) as $model_id) : ?>
                <article>
                    <h3><?php echo esc_html(get_the_title($model_id)); ?></h3>
                    <?php echo kingy_ali_model_compare_source_links_html($model_id); ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_model_compare_cta($model_a = 0, $model_b = 0) {
    $comparison_label = '';
    if ($model_a && $model_b && $model_a !== $model_b) {
        $comparison_label = sprintf(
            /* translators: 1: first model title, 2: second model title. */
            __('Request an editorial comparison for %1$s and %2$s', 'kingy-ai-launch-intelligence'),
            get_the_title($model_a),
            get_the_title($model_b)
        );
    } else {
        $comparison_label = __('Request an AI model comparison', 'kingy-ai-launch-intelligence');
    }

    $contact_url = function_exists('kingy_ali_contact_url') ? kingy_ali_contact_url() : home_url('/contact/');

    ob_start();
    ?>
    <div class="kingy-ali-cta-row kingy-ali-model-compare-cta">
        <a data-kingy-ali-track="clicked_contact_cta" data-event-label="<?php echo esc_attr($comparison_label); ?>" data-event-surface="model_compare_cta" href="<?php echo esc_url($contact_url); ?>"><?php echo esc_html($comparison_label); ?></a>
        <a data-kingy-ali-track="clicked_submit_cta" data-event-label="<?php esc_attr_e('Suggest a model for comparison', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_compare_cta" href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php esc_html_e('Suggest a model', 'kingy-ai-launch-intelligence'); ?></a>
    </div>
    <?php
    return ob_get_clean();
}

function kingy_ali_best_ai_models_context($atts) {
    $use_case = isset($atts['use_case']) ? sanitize_key($atts['use_case']) : '';
    $modality = isset($atts['modality']) ? sanitize_key($atts['modality']) : '';
    $access_type = isset($atts['access_type']) ? sanitize_key($atts['access_type']) : '';

    $contexts = array(
        'coding' => array(
            'key' => 'coding',
            'label' => __('coding model', 'kingy-ai-launch-intelligence'),
            'intro' => __('This page groups source-ready model profiles that carry coding use-case signals. Use it to compare coding notes, tool/function calling, access paths, pricing notes, source links, and last-verified status before choosing models for developer workflows.', 'kingy-ai-launch-intelligence'),
            'dimensions' => array(
                __('Coding notes and developer workflow fit', 'kingy-ai-launch-intelligence'),
                __('API access, tool/function calling, and context notes', 'kingy-ai-launch-intelligence'),
                __('Pricing, rate-limit, and deployment constraints that need verification', 'kingy-ai-launch-intelligence'),
                __('Benchmark caveats and official source links', 'kingy-ai-launch-intelligence'),
            ),
            'primary_fields' => array('coding_notes', 'agent_suitability', 'tool_calling', 'context_window', 'pricing'),
            'browse_args' => array('kali_model_use_case' => 'coding'),
        ),
        'open_weight' => array(
            'key' => 'open_weight',
            'label' => __('open-weight model', 'kingy-ai-launch-intelligence'),
            'intro' => __('This page groups source-ready model profiles that match open-weight access signals. Use it to compare license notes, local/self-hosted feasibility, hardware requirements, official model cards, and benchmark caveats without assuming that open weights mean unrestricted open source.', 'kingy-ai-launch-intelligence'),
            'dimensions' => array(
                __('Open-weight and open-source status are separate checks', 'kingy-ai-launch-intelligence'),
                __('License notes and official model-card links', 'kingy-ai-launch-intelligence'),
                __('Local/self-hosted availability and hardware requirements', 'kingy-ai-launch-intelligence'),
                __('Pricing, API availability, and hosted access paths when relevant', 'kingy-ai-launch-intelligence'),
            ),
            'primary_fields' => array('license_notes', 'hardware_requirements', 'local_available', 'open_source', 'pricing'),
            'browse_args' => array('kali_model_access_type' => 'open-weights'),
        ),
        'local' => array(
            'key' => 'local',
            'label' => __('local/private model', 'kingy-ai-launch-intelligence'),
            'intro' => __('This page groups source-ready model profiles with local or private-deployment signals. Use it to compare local availability, hardware requirements, license notes, open-weight status, and source-backed deployment caveats before planning private workflows.', 'kingy-ai-launch-intelligence'),
            'dimensions' => array(
                __('Local/self-hosted availability and private workflow fit', 'kingy-ai-launch-intelligence'),
                __('Hardware requirements and deployment notes', 'kingy-ai-launch-intelligence'),
                __('License and open-weight constraints', 'kingy-ai-launch-intelligence'),
                __('API or hosted fallback options when local operation is not enough', 'kingy-ai-launch-intelligence'),
            ),
            'primary_fields' => array('hardware_requirements', 'local_available', 'license_notes', 'open_weight', 'api_available'),
            'browse_args' => array('kali_model_access_type' => 'local'),
        ),
        'agents' => array(
            'key' => 'agents',
            'label' => __('agent-ready model', 'kingy-ai-launch-intelligence'),
            'intro' => __('This page groups source-ready model profiles that carry agent workflow signals. Use it to compare agent suitability, tool/function calling, API access, context notes, reliability caveats, and last-verified status before building automated workflows.', 'kingy-ai-launch-intelligence'),
            'dimensions' => array(
                __('Agent suitability notes and workflow constraints', 'kingy-ai-launch-intelligence'),
                __('Tool/function calling, API availability, and context notes', 'kingy-ai-launch-intelligence'),
                __('Reasoning notes and benchmark caveats', 'kingy-ai-launch-intelligence'),
                __('Source links and last-verified status for fast-moving model behavior', 'kingy-ai-launch-intelligence'),
            ),
            'primary_fields' => array('agent_suitability', 'tool_calling', 'api_available', 'context_window', 'reasoning_notes'),
            'browse_args' => array('kali_model_use_case' => 'agents'),
        ),
        'multimodal' => array(
            'key' => 'multimodal',
            'label' => __('multimodal model', 'kingy-ai-launch-intelligence'),
            'intro' => __('This page groups source-ready model profiles with multimodal signals. Use it to compare modalities, creative and research notes, access paths, source links, and benchmark caveats before using a model for text, image, audio, video, or mixed-media work.', 'kingy-ai-launch-intelligence'),
            'dimensions' => array(
                __('Modalities supported by the stored model profile', 'kingy-ai-launch-intelligence'),
                __('Creative, research, and business workflow fit', 'kingy-ai-launch-intelligence'),
                __('API, web app, and pricing notes for production workflows', 'kingy-ai-launch-intelligence'),
                __('Official source links and benchmark caveats for changing capabilities', 'kingy-ai-launch-intelligence'),
            ),
            'primary_fields' => array('creative_notes', 'research_notes', 'api_available', 'web_app_available', 'pricing'),
            'browse_args' => array('kali_model_modality' => 'multimodal'),
        ),
        'general' => array(
            'key' => 'general',
            'label' => __('AI model', 'kingy-ai-launch-intelligence'),
            'intro' => __('This page groups source-ready model profiles from Kingy AI. Use it as a comparison workbench, not a final ranking.', 'kingy-ai-launch-intelligence'),
            'dimensions' => array(
                __('Provider, family, access paths, and modality', 'kingy-ai-launch-intelligence'),
                __('Use-case notes and skip-if constraints', 'kingy-ai-launch-intelligence'),
                __('Pricing, license, and deployment signals', 'kingy-ai-launch-intelligence'),
                __('Official source links, benchmark caveats, and last-verified status', 'kingy-ai-launch-intelligence'),
            ),
            'primary_fields' => array('best_for', 'strengths', 'weaknesses', 'pricing', 'benchmark_caveat'),
            'browse_args' => array(),
        ),
    );

    if ($use_case === 'coding') {
        return $contexts['coding'];
    }
    if ($use_case === 'agents') {
        return $contexts['agents'];
    }
    if ($access_type === 'open-weights') {
        return $contexts['open_weight'];
    }
    if ($access_type === 'local') {
        return $contexts['local'];
    }
    if ($modality === 'multimodal') {
        return $contexts['multimodal'];
    }

    return $contexts['general'];
}

function kingy_ali_best_ai_models_candidate_ids($query) {
    if (!($query instanceof WP_Query) || empty($query->posts)) {
        return array();
    }

    $ids = array();
    foreach ($query->posts as $post) {
        $ids[] = is_object($post) && isset($post->ID) ? absint($post->ID) : absint($post);
    }

    return array_values(array_filter(array_unique($ids)));
}

function kingy_ali_render_best_ai_models_methodology($context, $query) {
    $candidate_count = count(kingy_ali_best_ai_models_candidate_ids($query));
    $browse_url = !empty($context['browse_args']) ? kingy_ali_model_directory_url($context['browse_args']) : home_url('/ai-models/');

    ob_start();
    ?>
    <div class="kingy-ali-model-compare-note">
        <strong><?php esc_html_e('How to use this page', 'kingy-ai-launch-intelligence'); ?></strong>
        <p>
            <?php
            echo esc_html(
                sprintf(
                    __('This is a noindex-safe comparison workbench built from %1$d source-ready Kingy AI model profiles. The order is alphabetical, not a ranking. Use the matrix to narrow candidates, then open the model profiles and official sources before making a buying, engineering, or editorial decision.', 'kingy-ai-launch-intelligence'),
                    $candidate_count
                )
            );
            ?>
        </p>
        <p><?php echo esc_html($context['intro']); ?></p>
        <p><a data-kingy-ali-track="clicked_model_directory_filtered" data-event-label="<?php echo esc_attr($context['label']); ?>" data-event-surface="best_model_methodology" href="<?php echo esc_url($browse_url); ?>"><?php esc_html_e('Open this candidate set in the AI Model Intelligence Hub', 'kingy-ai-launch-intelligence'); ?></a></p>
    </div>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_best_ai_models_dimensions($context) {
    $dimensions = isset($context['dimensions']) && is_array($context['dimensions']) ? $context['dimensions'] : array();
    if (!$dimensions) {
        return '';
    }

    ob_start();
    ?>
    <section class="kingy-ali-model-compare-dimensions">
        <h2><?php esc_html_e('Comparison Dimensions', 'kingy-ai-launch-intelligence'); ?></h2>
        <p><?php esc_html_e('These are the checks Kingy AI uses to make the page useful without turning incomplete or fast-changing model data into unsupported rankings.', 'kingy-ai-launch-intelligence'); ?></p>
        <ul>
            <?php foreach ($dimensions as $dimension) : ?>
                <li><?php echo esc_html($dimension); ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_best_ai_models_first_available_note($post_id, $keys) {
    $keys = is_array($keys) ? $keys : array();
    foreach ($keys as $key) {
        $value = kingy_ali_model_text($post_id, $key);
        if ($value !== '') {
            return $value;
        }
    }

    return kingy_ali_model_text($post_id, 'model_overview', kingy_ali_model_summary($post_id));
}

function kingy_ali_best_ai_models_access_html($post_id) {
    $signals = array(
        __('API', 'kingy-ai-launch-intelligence') => kingy_ali_model_text($post_id, 'api_available'),
        __('Web', 'kingy-ai-launch-intelligence') => kingy_ali_model_text($post_id, 'web_app_available'),
        __('Local', 'kingy-ai-launch-intelligence') => kingy_ali_model_text($post_id, 'local_available'),
        __('Open weights', 'kingy-ai-launch-intelligence') => kingy_ali_model_text($post_id, 'open_weight'),
    );

    $items = array();
    foreach ($signals as $label => $value) {
        $items[] = '<li><strong>' . esc_html($label) . ':</strong> ' . kingy_ali_model_compare_text_html($value) . '</li>';
    }

    return '<ul class="kingy-ali-model-source-list kingy-ali-model-signal-list">' . implode('', $items) . '</ul>';
}

function kingy_ali_best_ai_models_trust_html($post_id) {
    $source_count = function_exists('kingy_ali_public_source_links') ? count(kingy_ali_public_source_links($post_id)) : 0;
    $last_verified = kingy_ali_model_text($post_id, 'last_verified', __('Unknown', 'kingy-ai-launch-intelligence'));
    $verification = kingy_ali_model_text($post_id, 'verification_status', __('Unknown', 'kingy-ai-launch-intelligence'));

    return '<ul class="kingy-ali-model-source-list kingy-ali-model-signal-list">'
        . '<li><strong>' . esc_html__('Last verified', 'kingy-ai-launch-intelligence') . ':</strong> ' . kingy_ali_model_compare_text_html($last_verified) . '</li>'
        . '<li><strong>' . esc_html__('Verification', 'kingy-ai-launch-intelligence') . ':</strong> ' . kingy_ali_model_compare_text_html($verification) . '</li>'
        . '<li><strong>' . esc_html__('Sources', 'kingy-ai-launch-intelligence') . ':</strong> ' . esc_html(sprintf(_n('%d link', '%d links', $source_count, 'kingy-ai-launch-intelligence'), $source_count)) . '</li>'
        . '</ul>';
}

function kingy_ali_best_ai_models_source_links_html($post_id, $limit = 4) {
    $links = function_exists('kingy_ali_public_source_links') ? kingy_ali_public_source_links($post_id) : array();
    if (!$links) {
        return kingy_ali_model_compare_unknown_html(__('No source links entered yet.', 'kingy-ai-launch-intelligence'));
    }

    ob_start();
    echo '<ul class="kingy-ali-model-source-list">';
    foreach (array_slice($links, 0, max(1, absint($limit))) as $link) {
        $label = isset($link['label']) ? (string) $link['label'] : __('Source', 'kingy-ai-launch-intelligence');
        $url = isset($link['url']) && is_scalar($link['url']) ? (string) $link['url'] : '';
        $url = function_exists('kingy_ali_sanitize_public_profile_link_url') ? kingy_ali_sanitize_public_profile_link_url($url) : esc_url_raw($url, array('http', 'https'));
        if (!$url) {
            continue;
        }

        $rel = function_exists('kingy_ali_source_link_target_attrs') ? kingy_ali_source_link_target_attrs($url) : '';
        echo '<li><a data-kingy-ali-track="clicked_source_link" data-event-label="' . esc_attr($label) . '" data-event-surface="best_model_sources" href="' . esc_url($url) . '"' . $rel . '>' . esc_html($label) . '</a></li>';
    }
    if (count($links) > $limit) {
        echo '<li><span>' . esc_html(sprintf(__('%d more on profile', 'kingy-ai-launch-intelligence'), count($links) - $limit)) . '</span></li>';
    }
    echo '</ul>';

    $html = trim(ob_get_clean());
    return $html !== '<ul class="kingy-ali-model-source-list"></ul>' ? $html : kingy_ali_model_compare_unknown_html(__('No valid source links entered yet.', 'kingy-ai-launch-intelligence'));
}

function kingy_ali_render_best_ai_models_matrix($query, $context) {
    $candidate_ids = kingy_ali_best_ai_models_candidate_ids($query);
    if (!$candidate_ids) {
        return '';
    }

    $primary_fields = isset($context['primary_fields']) && is_array($context['primary_fields']) ? $context['primary_fields'] : array('best_for', 'strengths', 'weaknesses');

    ob_start();
    ?>
    <section class="kingy-ali-model-best-matrix">
        <h2><?php esc_html_e('Candidate Comparison Matrix', 'kingy-ai-launch-intelligence'); ?></h2>
        <p><?php esc_html_e('This matrix compares stored profile signals. It does not score, rank, or crown a winner.', 'kingy-ai-launch-intelligence'); ?></p>
        <div class="kingy-ali-model-compare-table-wrap">
            <table class="kingy-ali-model-compare-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Model', 'kingy-ai-launch-intelligence'); ?></th>
                        <th><?php esc_html_e('Provider / Family', 'kingy-ai-launch-intelligence'); ?></th>
                        <th><?php esc_html_e('Why compare it here', 'kingy-ai-launch-intelligence'); ?></th>
                        <th><?php esc_html_e('Access signals', 'kingy-ai-launch-intelligence'); ?></th>
                        <th><?php esc_html_e('Trust signals', 'kingy-ai-launch-intelligence'); ?></th>
                        <th><?php esc_html_e('Source trail', 'kingy-ai-launch-intelligence'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($candidate_ids as $model_id) : ?>
                        <tr>
                            <th scope="row"><?php echo kingy_ali_model_compare_profile_link($model_id); ?></th>
                            <td>
                                <?php echo kingy_ali_model_compare_text_html(kingy_ali_model_provider_label($model_id)); ?><br>
                                <span class="kingy-ali-model-unknown"><?php echo esc_html(kingy_ali_model_terms_to_string($model_id, 'model_family', __('Family unknown', 'kingy-ai-launch-intelligence'))); ?></span>
                            </td>
                            <td><?php echo kingy_ali_model_compare_text_html(kingy_ali_best_ai_models_first_available_note($model_id, $primary_fields)); ?></td>
                            <td><?php echo kingy_ali_best_ai_models_access_html($model_id); ?></td>
                            <td><?php echo kingy_ali_best_ai_models_trust_html($model_id); ?></td>
                            <td><?php echo kingy_ali_best_ai_models_source_links_html($model_id, 4); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_best_ai_models_next_steps($context) {
    $browse_url = !empty($context['browse_args']) ? kingy_ali_model_directory_url($context['browse_args']) : home_url('/ai-models/');

    ob_start();
    ?>
    <section class="kingy-ali-link-panel kingy-ali-model-best-next-steps">
        <h2><?php esc_html_e('Next Research Paths', 'kingy-ai-launch-intelligence'); ?></h2>
        <div class="kingy-ali-link-list">
            <a data-kingy-ali-track="clicked_model_directory_filtered" data-event-label="<?php echo esc_attr($context['label']); ?>" data-event-surface="best_model_next_steps" href="<?php echo esc_url($browse_url); ?>"><?php esc_html_e('Browse this filtered model set', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_model_compare" data-event-label="<?php esc_attr_e('Compare two AI models', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="best_model_next_steps" href="<?php echo esc_url(home_url('/compare-ai-models/')); ?>"><?php esc_html_e('Compare two AI models', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_model_hub" data-event-label="<?php esc_attr_e('AI Model Intelligence Hub', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="best_model_next_steps" href="<?php echo esc_url(home_url('/ai-models/')); ?>"><?php esc_html_e('AI Model Intelligence Hub', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_open_weight_launches" data-event-label="<?php esc_attr_e('Open-weight model launches', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="best_model_next_steps" href="<?php echo esc_url(home_url('/ai-launches/open-weight-models/')); ?>"><?php esc_html_e('Open-weight model launches', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_model_landing_query($config, $limit = 24) {
    $filters = array(
        'q' => '',
        'provider' => !empty($config['provider']) ? kingy_ali_sanitize_slug_filter($config['provider']) : '',
        'family' => !empty($config['family']) ? kingy_ali_sanitize_slug_filter($config['family']) : '',
        'modality' => '',
        'use_case' => '',
        'access_type' => '',
        'license_type' => '',
        'status' => '',
        'api_available' => '',
        'open_weight' => '',
        'local_available' => '',
        'track_search' => false,
        'limit' => max(1, absint($limit)),
    );

    return kingy_ali_query_model_directory($filters);
}

function kingy_ali_model_landing_filtered_url($config) {
    $args = array();
    if (!empty($config['provider'])) {
        $args['kali_model_provider'] = kingy_ali_sanitize_slug_filter($config['provider']);
    }
    if (!empty($config['family'])) {
        $args['kali_model_family'] = kingy_ali_sanitize_slug_filter($config['family']);
    }

    return kingy_ali_model_directory_url($args);
}

function kingy_ali_model_landing_related_ids($model_ids, $meta_key, $post_type) {
    $ids = array();
    foreach ((array) $model_ids as $model_id) {
        $related_id = function_exists('kingy_ali_public_profile_id')
            ? kingy_ali_public_profile_id(kingy_ali_get_meta($model_id, $meta_key))
            : absint(kingy_ali_get_meta($model_id, $meta_key));
        if (!$related_id || get_post_type($related_id) !== $post_type) {
            continue;
        }
        if (function_exists('kingy_ali_related_post_is_public_index_ready') && !kingy_ali_related_post_is_public_index_ready($related_id, $post_type)) {
            continue;
        }
        $ids[] = $related_id;
    }

    return array_values(array_unique(array_map('absint', $ids)));
}

function kingy_ali_render_model_landing_overview($config, $query) {
    $count = count(kingy_ali_best_ai_models_candidate_ids($query));
    $entity_label = !empty($config['entity_label']) ? $config['entity_label'] : (!empty($config['label']) ? $config['label'] : __('this model group', 'kingy-ai-launch-intelligence'));

    ob_start();
    ?>
    <section class="kingy-ali-model-compare-note kingy-ali-model-landing-overview">
        <strong><?php echo esc_html(sprintf(__('%s model overview', 'kingy-ai-launch-intelligence'), $entity_label)); ?></strong>
        <p>
            <?php
            echo esc_html(
                sprintf(
                    __('Kingy AI currently groups %1$d source-ready AI model profile(s) for %2$s on this static landing page. The page is generated from existing model profile fields, provider/family filters, source links, benchmark caveats, access notes, and last-verified status.', 'kingy-ai-launch-intelligence'),
                    $count,
                    $entity_label
                )
            );
            ?>
        </p>
        <p><?php esc_html_e('Treat this as a source-backed research path, not a ranking. Capabilities, pricing, access, licenses, and model behavior can change, so each model card links back to the full profile and official source trail.', 'kingy-ai-launch-intelligence'); ?></p>
        <?php if (!empty($config['noindex'])) : ?>
            <p><strong><?php esc_html_e('Indexing note:', 'kingy-ai-launch-intelligence'); ?></strong> <?php esc_html_e('This page is intentionally kept noindex until the model set is broader or the editorial overview is strong enough for search indexing.', 'kingy-ai-launch-intelligence'); ?></p>
        <?php else : ?>
            <p><strong><?php esc_html_e('Indexing note:', 'kingy-ai-launch-intelligence'); ?></strong> <?php esc_html_e('This page has enough source-backed model coverage for index consideration, while filtered query URLs remain noindex discovery paths.', 'kingy-ai-launch-intelligence'); ?></p>
        <?php endif; ?>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_model_landing_trust_panel($config, $query) {
    $candidate_ids = kingy_ali_best_ai_models_candidate_ids($query);
    $source_count = 0;
    foreach ($candidate_ids as $model_id) {
        $source_count += function_exists('kingy_ali_public_source_links') ? count(kingy_ali_public_source_links($model_id)) : 0;
    }

    ob_start();
    ?>
    <section class="kingy-ali-model-compare-dimensions kingy-ali-model-landing-trust">
        <h2><?php esc_html_e('Source And Trust Safeguards', 'kingy-ai-launch-intelligence'); ?></h2>
        <p>
            <?php
            echo esc_html(
                sprintf(
                    __('This page is assembled from %1$d model profile(s) and %2$d stored source link(s). Kingy AI does not infer missing specifications, prices, licenses, release dates, benchmark scores, or model rankings from this grouping.', 'kingy-ai-launch-intelligence'),
                    count($candidate_ids),
                    $source_count
                )
            );
            ?>
        </p>
        <ul>
            <li><?php esc_html_e('Official/provider sources and model profile source trails carry more weight than summaries.', 'kingy-ai-launch-intelligence'); ?></li>
            <li><?php echo esc_html(kingy_ali_model_benchmark_caveat_note()); ?></li>
            <li><?php esc_html_e('Filtered provider URLs remain noindex; this static page exists to give users a cleaner research path.', 'kingy-ai-launch-intelligence'); ?></li>
            <li><?php esc_html_e('Thin groups stay noindex until the page has enough source-backed depth for search indexing.', 'kingy-ai-launch-intelligence'); ?></li>
        </ul>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_model_landing_matrix($query) {
    $candidate_ids = kingy_ali_best_ai_models_candidate_ids($query);
    if (!$candidate_ids) {
        return '';
    }

    ob_start();
    ?>
    <section class="kingy-ali-model-best-matrix kingy-ali-model-landing-matrix">
        <h2><?php esc_html_e('Model Profiles In This Group', 'kingy-ai-launch-intelligence'); ?></h2>
        <p><?php esc_html_e('This table compares stored profile signals for discovery. It does not score, rank, or choose a winner.', 'kingy-ai-launch-intelligence'); ?></p>
        <div class="kingy-ali-model-compare-table-wrap">
            <table class="kingy-ali-model-compare-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Model', 'kingy-ai-launch-intelligence'); ?></th>
                        <th><?php esc_html_e('Provider / Family', 'kingy-ai-launch-intelligence'); ?></th>
                        <th><?php esc_html_e('Modality / Use cases', 'kingy-ai-launch-intelligence'); ?></th>
                        <th><?php esc_html_e('Access signals', 'kingy-ai-launch-intelligence'); ?></th>
                        <th><?php esc_html_e('Trust signals', 'kingy-ai-launch-intelligence'); ?></th>
                        <th><?php esc_html_e('Source trail', 'kingy-ai-launch-intelligence'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($candidate_ids as $model_id) : ?>
                        <tr>
                            <th scope="row"><?php echo kingy_ali_model_compare_profile_link($model_id); ?></th>
                            <td>
                                <?php echo kingy_ali_model_compare_text_html(kingy_ali_model_provider_label($model_id)); ?><br>
                                <span class="kingy-ali-model-unknown"><?php echo esc_html(kingy_ali_model_terms_to_string($model_id, 'model_family', __('Family unknown', 'kingy-ai-launch-intelligence'))); ?></span>
                            </td>
                            <td>
                                <?php echo kingy_ali_model_compare_text_html(kingy_ali_model_terms_to_string($model_id, 'model_modality', __('Unknown modality', 'kingy-ai-launch-intelligence'))); ?><br>
                                <span class="kingy-ali-model-unknown"><?php echo esc_html(kingy_ali_model_terms_to_string($model_id, 'model_use_case', __('Use cases not entered', 'kingy-ai-launch-intelligence'))); ?></span>
                            </td>
                            <td><?php echo kingy_ali_best_ai_models_access_html($model_id); ?></td>
                            <td><?php echo kingy_ali_best_ai_models_trust_html($model_id); ?></td>
                            <td><?php echo kingy_ali_best_ai_models_source_links_html($model_id, 4); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_model_landing_related_links($query) {
    $candidate_ids = kingy_ali_best_ai_models_candidate_ids($query);
    if (!$candidate_ids) {
        return '';
    }

    $company_ids = kingy_ali_model_landing_related_ids($candidate_ids, 'related_company_id', 'kingy_ai_company');
    $tool_ids = kingy_ali_model_landing_related_ids($candidate_ids, 'related_tool_id', 'kingy_ai_tool');
    if (!$company_ids && !$tool_ids) {
        return '';
    }

    ob_start();
    ?>
    <section class="kingy-ali-link-panel kingy-ali-model-landing-related">
        <h2><?php esc_html_e('Related Company And Tool Profiles', 'kingy-ai-launch-intelligence'); ?></h2>
        <p><?php esc_html_e('These links come from relationship fields already attached to the model profiles in this group.', 'kingy-ai-launch-intelligence'); ?></p>
        <?php if ($company_ids) : ?>
            <h3><?php esc_html_e('Related companies', 'kingy-ai-launch-intelligence'); ?></h3>
            <div class="kingy-ali-link-list">
                <?php foreach ($company_ids as $company_id) : ?>
                    <a data-kingy-ali-track="clicked_company" data-object-id="<?php echo esc_attr($company_id); ?>" data-event-surface="model_landing_related_company" href="<?php echo esc_url(get_permalink($company_id)); ?>"><?php echo esc_html(get_the_title($company_id)); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($tool_ids) : ?>
            <h3><?php esc_html_e('Related tools', 'kingy-ai-launch-intelligence'); ?></h3>
            <div class="kingy-ali-link-list">
                <?php foreach ($tool_ids as $tool_id) : ?>
                    <a data-kingy-ali-track="clicked_tool" data-object-id="<?php echo esc_attr($tool_id); ?>" data-event-surface="model_landing_related_tool" href="<?php echo esc_url(get_permalink($tool_id)); ?>"><?php echo esc_html(get_the_title($tool_id)); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_model_landing_research_paths($config) {
    $filtered_url = kingy_ali_model_landing_filtered_url($config);
    $landing_key = kingy_ali_model_landing_key_from_config($config);
    $comparison_links = kingy_ali_model_static_compare_links_for_landing($landing_key);

    ob_start();
    ?>
    <section class="kingy-ali-link-panel kingy-ali-model-landing-next-steps">
        <h2><?php esc_html_e('Continue Researching AI Models', 'kingy-ai-launch-intelligence'); ?></h2>
        <div class="kingy-ali-link-list">
            <a data-kingy-ali-track="clicked_model_hub" data-event-label="<?php esc_attr_e('AI Model Intelligence Hub', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_landing_next_steps" href="<?php echo esc_url(home_url('/ai-models/')); ?>"><?php esc_html_e('AI Model Intelligence Hub', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_model_directory_filtered" data-event-label="<?php echo esc_attr(!empty($config['label']) ? $config['label'] : __('Filtered model set', 'kingy-ai-launch-intelligence')); ?>" data-event-surface="model_landing_next_steps" href="<?php echo esc_url($filtered_url); ?>"><?php esc_html_e('Open the noindex filtered directory view', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_model_compare" data-event-label="<?php esc_attr_e('Compare AI Models', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_landing_next_steps" href="<?php echo esc_url(home_url('/compare-ai-models/')); ?>"><?php esc_html_e('Compare AI Models', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_open_weight_launches" data-event-label="<?php esc_attr_e('Open-weight model launches', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_landing_next_steps" href="<?php echo esc_url(home_url('/ai-launches/open-weight-models/')); ?>"><?php esc_html_e('Open-weight model launches', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
        <?php if ($comparison_links) : ?>
            <h3><?php esc_html_e('Related comparison pages', 'kingy-ai-launch-intelligence'); ?></h3>
            <div class="kingy-ali-link-list">
                <?php foreach ($comparison_links as $key => $comparison_config) : ?>
                    <a data-kingy-ali-track="clicked_model_static_compare" data-event-label="<?php echo esc_attr(!empty($comparison_config['label']) ? $comparison_config['label'] : $key); ?>" data-event-surface="model_landing_static_compare_links" href="<?php echo esc_url(kingy_ali_model_static_compare_page_url($key)); ?>"><?php echo esc_html(!empty($comparison_config['label']) ? $comparison_config['label'] : ucwords(str_replace('-', ' ', basename(trim((string) $comparison_config['path'], '/'))))); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_shortcode_model_landing($atts = array()) {
    kingy_ali_enqueue_model_assets();
    $atts = shortcode_atts(
        array(
            'key' => '',
            'limit' => 24,
            'heading' => 'yes',
        ),
        $atts,
        'kingy_ai_model_landing'
    );

    $config = kingy_ali_model_landing_page_config($atts['key']);
    if (!$config) {
        return kingy_ali_render_model_empty_state();
    }

    $query = kingy_ali_model_landing_query($config, $atts['limit']);

    ob_start();
    echo '<section class="kingy-ali-model-best-list kingy-ali-model-landing">';
    if ($atts['heading'] !== 'no') {
        kingy_ali_render_directory_hero(
            !empty($config['title']) ? $config['title'] : __('AI Model Landing Page', 'kingy-ai-launch-intelligence'),
            __('A static, source-backed AI model landing page generated from verified Kingy AI model profile data.', 'kingy-ai-launch-intelligence')
        );
    }
    echo kingy_ali_render_model_landing_overview($config, $query);
    echo kingy_ali_render_model_landing_trust_panel($config, $query);
    echo kingy_ali_render_model_landing_matrix($query);
    echo kingy_ali_render_model_landing_related_links($query);
    echo kingy_ali_render_model_directory_grid($query);
    echo kingy_ali_render_model_landing_research_paths($config);
    echo '</section>';

    return ob_get_clean();
}

function kingy_ali_model_static_compare_side_label($side) {
    return !empty($side['label']) ? $side['label'] : __('This model group', 'kingy-ai-launch-intelligence');
}

function kingy_ali_model_static_compare_side_query($side, $limit = 24) {
    $side = is_array($side) ? $side : array();
    $filters = array(
        'q' => '',
        'provider' => !empty($side['provider']) ? kingy_ali_sanitize_slug_filter($side['provider']) : '',
        'family' => !empty($side['family']) ? kingy_ali_sanitize_slug_filter($side['family']) : '',
        'modality' => '',
        'use_case' => '',
        'access_type' => '',
        'license_type' => '',
        'status' => '',
        'api_available' => '',
        'open_weight' => '',
        'local_available' => '',
        'track_search' => false,
        'limit' => max(1, absint($limit)),
    );

    return kingy_ali_query_model_directory($filters);
}

function kingy_ali_model_static_compare_side_filtered_url($side) {
    $side = is_array($side) ? $side : array();
    $args = array();
    if (!empty($side['provider'])) {
        $args['kali_model_provider'] = kingy_ali_sanitize_slug_filter($side['provider']);
    }
    if (!empty($side['family'])) {
        $args['kali_model_family'] = kingy_ali_sanitize_slug_filter($side['family']);
    }

    return kingy_ali_model_directory_url($args);
}

function kingy_ali_model_static_compare_source_count($model_ids) {
    $count = 0;
    foreach ((array) $model_ids as $model_id) {
        $count += function_exists('kingy_ali_public_source_links') ? count(kingy_ali_public_source_links($model_id)) : 0;
    }

    return $count;
}

function kingy_ali_render_model_static_compare_overview($config, $query_a, $query_b) {
    $ids_a = kingy_ali_best_ai_models_candidate_ids($query_a);
    $ids_b = kingy_ali_best_ai_models_candidate_ids($query_b);
    $label_a = kingy_ali_model_static_compare_side_label($config['side_a']);
    $label_b = kingy_ali_model_static_compare_side_label($config['side_b']);

    ob_start();
    ?>
    <section class="kingy-ali-model-compare-note kingy-ali-model-static-compare-overview">
        <strong><?php echo esc_html(!empty($config['label']) ? $config['label'] : __('AI model comparison', 'kingy-ai-launch-intelligence')); ?></strong>
        <p>
            <?php
            echo esc_html(
                sprintf(
                    __('This static comparison page groups %1$d source-ready profile(s) for %2$s and %3$d source-ready profile(s) for %4$s. It is generated from existing Kingy AI model fields, source links, access signals, modality/use-case terms, benchmark caveats, and last-verified status.', 'kingy-ai-launch-intelligence'),
                    count($ids_a),
                    $label_a,
                    count($ids_b),
                    $label_b
                )
            );
            ?>
        </p>
        <p><?php esc_html_e('This page does not score, rank, or declare a winner. Use it to inspect the current candidate sets, then open the model profiles and official source trails before choosing a model for a real workflow.', 'kingy-ai-launch-intelligence'); ?></p>
        <?php if (!empty($config['noindex'])) : ?>
            <p><strong><?php esc_html_e('Indexing note:', 'kingy-ai-launch-intelligence'); ?></strong> <?php echo esc_html(!empty($config['noindex_reason']) ? $config['noindex_reason'] : __('This page is intentionally kept noindex until the source-backed comparison depth is strong enough for search indexing.', 'kingy-ai-launch-intelligence')); ?></p>
        <?php else : ?>
            <p><strong><?php esc_html_e('Indexing note:', 'kingy-ai-launch-intelligence'); ?></strong> <?php esc_html_e('This page has enough source-backed model coverage for index consideration, while filtered query URLs remain noindex discovery paths.', 'kingy-ai-launch-intelligence'); ?></p>
        <?php endif; ?>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_model_static_compare_trust_panel($config, $query_a, $query_b) {
    $ids_a = kingy_ali_best_ai_models_candidate_ids($query_a);
    $ids_b = kingy_ali_best_ai_models_candidate_ids($query_b);
    $source_count = kingy_ali_model_static_compare_source_count($ids_a) + kingy_ali_model_static_compare_source_count($ids_b);

    ob_start();
    ?>
    <section class="kingy-ali-model-compare-dimensions kingy-ali-model-static-compare-trust">
        <h2><?php esc_html_e('Source And Trust Safeguards', 'kingy-ai-launch-intelligence'); ?></h2>
        <p>
            <?php
            echo esc_html(
                sprintf(
                    __('This comparison is assembled from %1$d total model profile(s) and %2$d stored source link(s). Kingy AI does not infer missing specifications, prices, context windows, release dates, licenses, benchmark scores, or provider rankings from this grouping.', 'kingy-ai-launch-intelligence'),
                    count($ids_a) + count($ids_b),
                    $source_count
                )
            );
            ?>
        </p>
        <ul>
            <li><?php esc_html_e('Official/provider sources and model profile source trails carry more weight than summaries.', 'kingy-ai-launch-intelligence'); ?></li>
            <li><?php echo esc_html(kingy_ali_model_benchmark_caveat_note()); ?></li>
            <li><?php esc_html_e('Candidate tables are alphabetical and source-gated; they are not leaderboards.', 'kingy-ai-launch-intelligence'); ?></li>
            <li><?php esc_html_e('Filtered comparison and directory query URLs remain noindex; static pages are indexed only when coverage is strong enough.', 'kingy-ai-launch-intelligence'); ?></li>
        </ul>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_model_static_compare_dimensions($config) {
    $label_a = kingy_ali_model_static_compare_side_label($config['side_a']);
    $label_b = kingy_ali_model_static_compare_side_label($config['side_b']);

    ob_start();
    ?>
    <section class="kingy-ali-model-compare-dimensions kingy-ali-model-static-compare-dimensions">
        <h2><?php esc_html_e('Comparison Dimensions', 'kingy-ai-launch-intelligence'); ?></h2>
        <p><?php echo esc_html(sprintf(__('Use these checks to compare %1$s and %2$s without turning incomplete or fast-changing model data into unsupported rankings.', 'kingy-ai-launch-intelligence'), $label_a, $label_b)); ?></p>
        <ul>
            <li><?php esc_html_e('Model profiles currently available on each side of the comparison.', 'kingy-ai-launch-intelligence'); ?></li>
            <li><?php esc_html_e('Provider, family, modality, and use-case signals stored on each profile.', 'kingy-ai-launch-intelligence'); ?></li>
            <li><?php esc_html_e('Access signals such as API, web app, local availability, and open-weight status.', 'kingy-ai-launch-intelligence'); ?></li>
            <li><?php esc_html_e('Verification status, last-verified date, benchmark caveat, and official source trail.', 'kingy-ai-launch-intelligence'); ?></li>
        </ul>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_model_static_compare_side_table($label, $query, $surface) {
    $candidate_ids = kingy_ali_best_ai_models_candidate_ids($query);

    ob_start();
    ?>
    <article class="kingy-ali-model-static-compare-side">
        <h3><?php echo esc_html($label); ?></h3>
        <?php if (!$candidate_ids) : ?>
            <p><?php esc_html_e('No source-ready model profiles are currently available for this side of the comparison.', 'kingy-ai-launch-intelligence'); ?></p>
        <?php else : ?>
            <div class="kingy-ali-model-compare-table-wrap">
                <table class="kingy-ali-model-compare-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Model', 'kingy-ai-launch-intelligence'); ?></th>
                            <th><?php esc_html_e('Provider / Family', 'kingy-ai-launch-intelligence'); ?></th>
                            <th><?php esc_html_e('Modality / Use cases', 'kingy-ai-launch-intelligence'); ?></th>
                            <th><?php esc_html_e('Access signals', 'kingy-ai-launch-intelligence'); ?></th>
                            <th><?php esc_html_e('Trust signals', 'kingy-ai-launch-intelligence'); ?></th>
                            <th><?php esc_html_e('Source trail', 'kingy-ai-launch-intelligence'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($candidate_ids as $model_id) : ?>
                            <tr>
                                <th scope="row"><?php echo kingy_ali_model_compare_profile_link($model_id); ?></th>
                                <td>
                                    <?php echo kingy_ali_model_compare_text_html(kingy_ali_model_provider_label($model_id)); ?><br>
                                    <span class="kingy-ali-model-unknown"><?php echo esc_html(kingy_ali_model_terms_to_string($model_id, 'model_family', __('Family unknown', 'kingy-ai-launch-intelligence'))); ?></span>
                                </td>
                                <td>
                                    <?php echo kingy_ali_model_compare_text_html(kingy_ali_model_terms_to_string($model_id, 'model_modality', __('Unknown modality', 'kingy-ai-launch-intelligence'))); ?><br>
                                    <span class="kingy-ali-model-unknown"><?php echo esc_html(kingy_ali_model_terms_to_string($model_id, 'model_use_case', __('Use cases not entered', 'kingy-ai-launch-intelligence'))); ?></span>
                                </td>
                                <td><?php echo kingy_ali_best_ai_models_access_html($model_id); ?></td>
                                <td><?php echo kingy_ali_best_ai_models_trust_html($model_id); ?></td>
                                <td><?php echo kingy_ali_best_ai_models_source_links_html($model_id, 4); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_model_static_compare_candidate_tables($config, $query_a, $query_b) {
    ob_start();
    ?>
    <section class="kingy-ali-model-best-matrix kingy-ali-model-static-compare-matrix">
        <h2><?php esc_html_e('Comparison Candidate Tables', 'kingy-ai-launch-intelligence'); ?></h2>
        <p><?php esc_html_e('These tables compare stored profile signals for each side. The order is alphabetical, not a ranking.', 'kingy-ai-launch-intelligence'); ?></p>
        <?php echo kingy_ali_render_model_static_compare_side_table(kingy_ali_model_static_compare_side_label($config['side_a']), $query_a, 'model_static_compare_side_a'); ?>
        <?php echo kingy_ali_render_model_static_compare_side_table(kingy_ali_model_static_compare_side_label($config['side_b']), $query_b, 'model_static_compare_side_b'); ?>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_model_static_compare_related_links($config) {
    $side_a = !empty($config['side_a']) && is_array($config['side_a']) ? $config['side_a'] : array();
    $side_b = !empty($config['side_b']) && is_array($config['side_b']) ? $config['side_b'] : array();

    ob_start();
    ?>
    <section class="kingy-ali-link-panel kingy-ali-model-static-compare-next-steps">
        <h2><?php esc_html_e('Continue Comparing AI Models', 'kingy-ai-launch-intelligence'); ?></h2>
        <div class="kingy-ali-link-list">
            <a data-kingy-ali-track="clicked_model_hub" data-event-label="<?php esc_attr_e('AI Model Intelligence Hub', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_static_compare_next_steps" href="<?php echo esc_url(home_url('/ai-models/')); ?>"><?php esc_html_e('AI Model Intelligence Hub', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_model_compare" data-event-label="<?php esc_attr_e('Compare two AI models', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="model_static_compare_next_steps" href="<?php echo esc_url(home_url('/compare-ai-models/')); ?>"><?php esc_html_e('Compare two AI models', 'kingy-ai-launch-intelligence'); ?></a>
            <?php if (!empty($side_a['landing'])) : ?>
                <a data-kingy-ali-track="clicked_model_landing" data-event-label="<?php echo esc_attr(kingy_ali_model_static_compare_side_label($side_a)); ?>" data-event-surface="model_static_compare_next_steps" href="<?php echo esc_url(kingy_ali_model_landing_page_url($side_a['landing'], array('kali_model_provider' => !empty($side_a['provider']) ? $side_a['provider'] : ''))); ?>"><?php echo esc_html(sprintf(__('Browse %s models', 'kingy-ai-launch-intelligence'), kingy_ali_model_static_compare_side_label($side_a))); ?></a>
            <?php endif; ?>
            <?php if (!empty($side_b['landing'])) : ?>
                <a data-kingy-ali-track="clicked_model_landing" data-event-label="<?php echo esc_attr(kingy_ali_model_static_compare_side_label($side_b)); ?>" data-event-surface="model_static_compare_next_steps" href="<?php echo esc_url(kingy_ali_model_landing_page_url($side_b['landing'], array('kali_model_provider' => !empty($side_b['provider']) ? $side_b['provider'] : ''))); ?>"><?php echo esc_html(sprintf(__('Browse %s models', 'kingy-ai-launch-intelligence'), kingy_ali_model_static_compare_side_label($side_b))); ?></a>
            <?php endif; ?>
            <a data-kingy-ali-track="clicked_model_directory_filtered" data-event-label="<?php echo esc_attr(kingy_ali_model_static_compare_side_label($side_a)); ?>" data-event-surface="model_static_compare_next_steps" href="<?php echo esc_url(kingy_ali_model_static_compare_side_filtered_url($side_a)); ?>"><?php echo esc_html(sprintf(__('Open %s in the noindex filtered directory', 'kingy-ai-launch-intelligence'), kingy_ali_model_static_compare_side_label($side_a))); ?></a>
            <a data-kingy-ali-track="clicked_model_directory_filtered" data-event-label="<?php echo esc_attr(kingy_ali_model_static_compare_side_label($side_b)); ?>" data-event-surface="model_static_compare_next_steps" href="<?php echo esc_url(kingy_ali_model_static_compare_side_filtered_url($side_b)); ?>"><?php echo esc_html(sprintf(__('Open %s in the noindex filtered directory', 'kingy-ai-launch-intelligence'), kingy_ali_model_static_compare_side_label($side_b))); ?></a>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_shortcode_model_static_compare($atts = array()) {
    try {
        return kingy_ali_shortcode_model_static_compare_inner($atts);
    } catch (Throwable $throwable) {
        return kingy_ali_render_model_emergency_safe_fallback('compare', $atts, $throwable);
    }
}

function kingy_ali_shortcode_model_static_compare_inner($atts = array()) {
    kingy_ali_enqueue_model_assets();
    $atts = shortcode_atts(
        array(
            'key' => '',
            'limit' => 24,
            'heading' => 'yes',
        ),
        $atts,
        'kingy_ai_model_static_compare'
    );

    $config = kingy_ali_model_static_compare_page_config($atts['key']);
    if (!$config || empty($config['side_a']) || empty($config['side_b'])) {
        return kingy_ali_render_model_compare_empty_state();
    }

    $query_a = kingy_ali_model_static_compare_side_query($config['side_a'], $atts['limit']);
    $query_b = kingy_ali_model_static_compare_side_query($config['side_b'], $atts['limit']);

    ob_start();
    echo '<section class="kingy-ali-model-best-list kingy-ali-model-static-compare">';
    if ($atts['heading'] !== 'no') {
        kingy_ali_render_directory_hero(
            !empty($config['title']) ? $config['title'] : __('AI Model Comparison', 'kingy-ai-launch-intelligence'),
            __('A static, source-backed AI model comparison page generated from verified Kingy AI model profile data.', 'kingy-ai-launch-intelligence')
        );
    }
    echo '<div class="kingy-ali-model-disclosure"><strong>' . esc_html__('Comparison caveat', 'kingy-ai-launch-intelligence') . '</strong><span>' . esc_html(kingy_ali_model_benchmark_caveat_note()) . '</span></div>';
    echo kingy_ali_render_model_static_compare_overview($config, $query_a, $query_b);
    echo kingy_ali_render_model_static_compare_trust_panel($config, $query_a, $query_b);
    echo kingy_ali_render_model_static_compare_dimensions($config);
    echo kingy_ali_render_model_static_compare_candidate_tables($config, $query_a, $query_b);
    echo kingy_ali_render_model_static_compare_related_links($config);
    echo '</section>';

    return ob_get_clean();
}

function kingy_ali_shortcode_best_ai_models($atts = array()) {
    kingy_ali_enqueue_model_assets();
    $atts = shortcode_atts(
        array(
            'limit' => 12,
            'title' => __('Best AI Models', 'kingy-ai-launch-intelligence'),
            'use_case' => '',
            'modality' => '',
            'access_type' => '',
            'heading' => 'yes',
        ),
        $atts,
        'kingy_best_ai_models'
    );

    $filters = array(
        'q' => '',
        'provider' => '',
        'family' => '',
        'modality' => kingy_ali_sanitize_slug_filter($atts['modality']),
        'use_case' => kingy_ali_sanitize_slug_filter($atts['use_case']),
        'access_type' => kingy_ali_sanitize_slug_filter($atts['access_type']),
        'license_type' => '',
        'status' => '',
        'api_available' => '',
        'open_weight' => '',
        'local_available' => '',
        'track_search' => false,
        'limit' => absint($atts['limit']),
    );

    $query = kingy_ali_query_model_directory($filters);
    $context = kingy_ali_best_ai_models_context($atts);

    ob_start();
    echo '<section class="kingy-ali-model-best-list">';
    if ($atts['heading'] !== 'no') {
        kingy_ali_render_directory_hero(
            $atts['title'],
            __('A source-backed comparison workbench generated from Kingy AI model profile data. This page is useful for research, but it should remain noindex until editorial rankings and claims are fully reviewed.', 'kingy-ai-launch-intelligence')
        );
    }
    echo '<div class="kingy-ali-model-disclosure"><strong>' . esc_html__('Ranking caveat', 'kingy-ai-launch-intelligence') . '</strong><span>' . esc_html(kingy_ali_model_benchmark_caveat_note()) . '</span></div>';
    echo kingy_ali_render_best_ai_models_methodology($context, $query);
    echo kingy_ali_render_best_ai_models_dimensions($context);
    echo kingy_ali_render_best_ai_models_matrix($query, $context);
    echo kingy_ali_render_model_directory_grid($query);
    echo kingy_ali_render_best_ai_models_next_steps($context);
    echo '</section>';

    return ob_get_clean();
}
