<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'kingy_ali_register_campaign_breakdown_meta_fields');
add_action('wp_enqueue_scripts', 'kingy_ali_enqueue_campaign_breakdown_assets', 30);
add_filter('body_class', 'kingy_ali_campaign_breakdown_body_class');
add_filter('the_content', 'kingy_ali_render_campaign_breakdown_content', 18);
add_filter('document_title_parts', 'kingy_ali_campaign_breakdown_document_title', 30);
add_filter('wpseo_title', 'kingy_ali_campaign_breakdown_wpseo_title', 30);
add_filter('wpseo_metadesc', 'kingy_ali_campaign_breakdown_wpseo_metadesc', 30);
add_action('wp_head', 'kingy_ali_output_campaign_breakdown_schema', 25);
add_action('save_post_page', 'kingy_ali_flag_campaign_breakdown_for_enrichment', 20, 3);

function kingy_ali_campaign_breakdown_meta_fields() {
    return array(
        'campaign_format_version' => array('label' => 'Campaign format version', 'type' => 'text', 'public' => false),
        'campaign_video_url' => array('label' => 'Campaign video URL', 'type' => 'url'),
        'campaign_video_id' => array('label' => 'Campaign YouTube video ID', 'type' => 'text'),
        'campaign_youtube_title' => array('label' => 'YouTube title', 'type' => 'text'),
        'campaign_youtube_description' => array('label' => 'YouTube description', 'type' => 'textarea'),
        'campaign_youtube_published_date' => array('label' => 'YouTube published date', 'type' => 'text'),
        'campaign_youtube_thumbnail' => array('label' => 'YouTube thumbnail', 'type' => 'url'),
        'campaign_transcript_available' => array('label' => 'Transcript available', 'type' => 'checkbox'),
        'campaign_transcript_source' => array('label' => 'Transcript source', 'type' => 'text'),
        'campaign_product_name' => array('label' => 'Product name', 'type' => 'text'),
        'campaign_company_name' => array('label' => 'Company name', 'type' => 'text'),
        'campaign_category_or_use_case' => array('label' => 'Category or use case', 'type' => 'text'),
        'campaign_official_product_url' => array('label' => 'Official product URL', 'type' => 'url'),
        'campaign_source_urls' => array('label' => 'Campaign source URLs', 'type' => 'textarea'),
        'campaign_last_enriched_date' => array('label' => 'Last enriched date', 'type' => 'text', 'public' => false),
        'campaign_enrichment_status' => array('label' => 'Campaign enrichment status', 'type' => 'text', 'public' => false),
        'campaign_needs_manual_review' => array('label' => 'Needs manual review', 'type' => 'checkbox', 'public' => false),
        'campaign_notes' => array('label' => 'Campaign notes', 'type' => 'textarea', 'public' => false),
        'seo_title' => array('label' => 'SEO title', 'type' => 'text'),
        'meta_description' => array('label' => 'Meta description', 'type' => 'textarea'),
    );
}

function kingy_ali_register_campaign_breakdown_meta_fields() {
    foreach (kingy_ali_campaign_breakdown_meta_fields() as $key => $field) {
        $type = 'string';
        if (isset($field['type']) && $field['type'] === 'checkbox') {
            $type = 'boolean';
        }

        register_post_meta(
            'page',
            kingy_ali_meta_key($key),
            array(
                'single' => true,
                'type' => $type,
                'sanitize_callback' => function ($value) use ($field) {
                    return kingy_ali_campaign_breakdown_sanitize_meta_value($value, $field);
                },
                'show_in_rest' => true,
                'auth_callback' => function () {
                    return current_user_can('edit_pages');
                },
            )
        );
    }
}

function kingy_ali_campaign_breakdown_sanitize_meta_value($value, $field) {
    $type = isset($field['type']) ? $field['type'] : 'text';
    if ($type === 'checkbox') {
        return !empty($value);
    }

    if (!is_scalar($value)) {
        return '';
    }

    $value = trim((string) $value);
    if ($type === 'url') {
        return esc_url_raw($value, array('http', 'https'));
    }

    if ($type === 'textarea') {
        return sanitize_textarea_field($value);
    }

    return sanitize_text_field($value);
}

function kingy_ali_campaign_breakdown_meta($post_id, $key, $default = '') {
    $value = get_post_meta(absint($post_id), kingy_ali_meta_key($key), true);
    if ($value === '' || $value === array()) {
        return $default;
    }

    return is_scalar($value) ? trim((string) $value) : $default;
}

function kingy_ali_is_campaign_breakdown_page($post_id = 0) {
    $post_id = absint($post_id);
    if (!$post_id) {
        $post_id = get_queried_object_id();
    }

    if (!$post_id || get_post_type($post_id) !== 'page') {
        return false;
    }

    $post = get_post($post_id);
    if (!$post) {
        return false;
    }

    if (kingy_ali_campaign_breakdown_meta($post_id, 'campaign_format_version') !== '') {
        return true;
    }

    $slug = isset($post->post_name) ? (string) $post->post_name : '';
    if (strpos($slug, 'campaign-breakdown') === 0) {
        return true;
    }

    $title = wp_strip_all_tags(get_the_title($post_id));
    return stripos($title, 'Campaign Breakdown') !== false;
}

function kingy_ali_current_campaign_breakdown_post_id() {
    if (is_admin() || !is_singular('page')) {
        return 0;
    }

    $post_id = get_queried_object_id();
    return kingy_ali_is_campaign_breakdown_page($post_id) ? absint($post_id) : 0;
}

function kingy_ali_campaign_breakdown_has_explicit_identity($post_id) {
    return kingy_ali_campaign_breakdown_meta($post_id, 'campaign_product_name') !== ''
        || kingy_ali_campaign_breakdown_meta($post_id, 'campaign_company_name') !== '';
}

function kingy_ali_campaign_breakdown_label_needs_review($label) {
    $label = trim((string) $label);
    if ($label === '') {
        return true;
    }

    return (bool) preg_match('/^(new\s+)?ai\s+|^(one-click\s+)?ai\s+|^free\s+ai\s+/i', $label);
}

function kingy_ali_campaign_breakdown_can_render_public_layout($post_id, $content = '') {
    $post_id = absint($post_id);
    if (!$post_id || !kingy_ali_is_campaign_breakdown_page($post_id)) {
        return false;
    }

    if (kingy_ali_campaign_breakdown_meta($post_id, 'campaign_needs_manual_review') === '1') {
        return false;
    }

    if (kingy_ali_campaign_breakdown_has_explicit_identity($post_id)) {
        return true;
    }

    $label = kingy_ali_campaign_breakdown_product_label($post_id);
    if (kingy_ali_campaign_breakdown_label_needs_review($label)) {
        return false;
    }

    return kingy_ali_campaign_breakdown_video_id($post_id, $content) !== '';
}

function kingy_ali_enqueue_campaign_breakdown_assets() {
    $post_id = kingy_ali_current_campaign_breakdown_post_id();
    if ($post_id && kingy_ali_campaign_breakdown_can_render_public_layout($post_id, get_post_field('post_content', $post_id)) && wp_style_is('kingy-ali-launch-intelligence', 'registered')) {
        wp_enqueue_style('kingy-ali-launch-intelligence');
    }
}

function kingy_ali_campaign_breakdown_body_class($classes) {
    $post_id = kingy_ali_current_campaign_breakdown_post_id();
    if (!$post_id || !kingy_ali_campaign_breakdown_can_render_public_layout($post_id, get_post_field('post_content', $post_id))) {
        return $classes;
    }

    $classes[] = 'kingy-ali-launch-intelligence-page';
    $classes[] = 'kingy-campaign-breakdown-page';
    return array_unique($classes);
}

function kingy_ali_campaign_breakdown_product_label($post_id) {
    $product = kingy_ali_campaign_breakdown_meta($post_id, 'campaign_product_name');
    $company = kingy_ali_campaign_breakdown_meta($post_id, 'campaign_company_name');
    if ($product && $company && stripos($product, $company) === false) {
        return $product . ' by ' . $company;
    }
    if ($product) {
        return $product;
    }
    if ($company) {
        return $company;
    }

    $title = wp_strip_all_tags(get_the_title($post_id));
    $title = preg_replace('/^How\s+Kingy\s+AI\s+Explained\s+/i', '', $title);
    $title = preg_replace('/\s+Campaign\s+Breakdown.*$/i', '', $title);
    return trim($title) !== '' ? trim($title) : __('this AI campaign', 'kingy-ai-launch-intelligence');
}

function kingy_ali_campaign_breakdown_video_id($post_id, $content = '') {
    $video_id = kingy_ali_campaign_breakdown_meta($post_id, 'campaign_video_id');
    if ($video_id !== '') {
        return kingy_ali_campaign_breakdown_normalize_youtube_id($video_id);
    }

    $video_url = kingy_ali_campaign_breakdown_meta($post_id, 'campaign_video_url');
    if ($video_url !== '') {
        $video_id = kingy_ali_campaign_breakdown_extract_youtube_id($video_url);
        if ($video_id) {
            return $video_id;
        }
    }

    return kingy_ali_campaign_breakdown_extract_youtube_id($content);
}

function kingy_ali_campaign_breakdown_normalize_youtube_id($value) {
    if (!is_scalar($value)) {
        return '';
    }

    $value = trim((string) $value);
    return preg_match('/^[A-Za-z0-9_-]{11}$/', $value) ? $value : '';
}

function kingy_ali_campaign_breakdown_extract_youtube_id($value) {
    if (!is_scalar($value)) {
        return '';
    }

    $value = html_entity_decode((string) $value, ENT_QUOTES, get_bloginfo('charset'));
    $patterns = array(
        '#youtu\.be/([A-Za-z0-9_-]{11})#i',
        '#youtube\.com/watch\?[^"\']*v=([A-Za-z0-9_-]{11})#i',
        '#youtube\.com/embed/([A-Za-z0-9_-]{11})#i',
        '#youtube\.com/shorts/([A-Za-z0-9_-]{11})#i',
        '#youtube-nocookie\.com/embed/([A-Za-z0-9_-]{11})#i',
    );

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $value, $matches)) {
            return $matches[1];
        }
    }

    return '';
}

function kingy_ali_campaign_breakdown_video_url($post_id, $content = '') {
    $video_url = kingy_ali_campaign_breakdown_meta($post_id, 'campaign_video_url');
    if ($video_url !== '') {
        return $video_url;
    }

    $video_id = kingy_ali_campaign_breakdown_video_id($post_id, $content);
    return $video_id ? 'https://www.youtube.com/watch?v=' . $video_id : '';
}

function kingy_ali_campaign_breakdown_intro($post_id) {
    $description = kingy_ali_campaign_breakdown_meta($post_id, 'meta_description');
    if (!$description) {
        $description = kingy_ali_campaign_breakdown_meta($post_id, 'campaign_youtube_description');
    }
    if (!$description) {
        $description = get_the_excerpt($post_id);
    }

    $description = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags((string) $description)));
    if ($description !== '') {
        return kingy_ali_meta_description_excerpt($description, 220);
    }

    return sprintf(
        __('A Kingy AI campaign breakdown showing how %s was presented to an AI-curious audience through a video-led distribution campaign.', 'kingy-ai-launch-intelligence'),
        kingy_ali_campaign_breakdown_product_label($post_id)
    );
}

function kingy_ali_render_campaign_breakdown_content($content) {
    $post_id = kingy_ali_current_campaign_breakdown_post_id();
    if (!$post_id || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    if (!kingy_ali_campaign_breakdown_can_render_public_layout($post_id, $content)) {
        return $content;
    }

    if (strpos($content, 'kingy-campaign-breakdown') !== false) {
        return $content;
    }

    $hero = kingy_ali_campaign_breakdown_hero_html($post_id, $content);
    $supplemental = kingy_ali_campaign_breakdown_supplemental_html($post_id, $content);

    return $hero . '<div class="kingy-campaign-breakdown__body">' . $content . '</div>' . $supplemental;
}

function kingy_ali_campaign_breakdown_hero_html($post_id, $content) {
    $product = kingy_ali_campaign_breakdown_product_label($post_id);
    $title = get_the_title($post_id);
    if (stripos($title, $product) === false && $product) {
        $title = sprintf(__('How Kingy AI Explained %s', 'kingy-ai-launch-intelligence'), $product);
    }

    $video_id = kingy_ali_campaign_breakdown_video_id($post_id, $content);
    $video_url = kingy_ali_campaign_breakdown_video_url($post_id, $content);
    $thumbnail = kingy_ali_campaign_breakdown_meta($post_id, 'campaign_youtube_thumbnail');

    ob_start();
    ?>
    <section class="kingy-campaign-breakdown kingy-campaign-breakdown__hero" aria-label="<?php esc_attr_e('Campaign Breakdown', 'kingy-ai-launch-intelligence'); ?>">
        <div class="kingy-campaign-breakdown__copy">
            <p class="kingy-ali-kicker"><?php esc_html_e('Campaign Breakdown', 'kingy-ai-launch-intelligence'); ?></p>
            <h1><?php echo esc_html($title); ?></h1>
            <p><?php echo esc_html(kingy_ali_campaign_breakdown_intro($post_id)); ?></p>
            <p class="kingy-campaign-breakdown__positioning"><?php esc_html_e('You do AI. We do distribution.', 'kingy-ai-launch-intelligence'); ?></p>
            <div class="kingy-campaign-breakdown__actions">
                <a href="<?php echo esc_url(home_url('/sponsor-kingy-ai/')); ?>"><?php esc_html_e('Request Sponsor Fit Review', 'kingy-ai-launch-intelligence'); ?></a>
                <?php if ($video_url) : ?>
                    <a href="<?php echo esc_url($video_url); ?>"><?php esc_html_e('Watch the Video', 'kingy-ai-launch-intelligence'); ?></a>
                <?php endif; ?>
                <a href="<?php echo esc_url(home_url('/clients/')); ?>"><?php esc_html_e('See Client Examples', 'kingy-ai-launch-intelligence'); ?></a>
            </div>
        </div>
        <div class="kingy-campaign-breakdown__media">
            <?php if ($video_id) : ?>
                <div class="kingy-campaign-breakdown__video">
                    <iframe
                        src="<?php echo esc_url('https://www.youtube.com/embed/' . $video_id); ?>"
                        title="<?php echo esc_attr(sprintf(__('Kingy AI video for %s', 'kingy-ai-launch-intelligence'), $product)); ?>"
                        loading="lazy"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen></iframe>
                </div>
            <?php elseif ($thumbnail) : ?>
                <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($product); ?>" loading="lazy">
            <?php else : ?>
                <div class="kingy-campaign-breakdown__video kingy-campaign-breakdown__video--empty">
                    <span><?php esc_html_e('Video source pending review', 'kingy-ai-launch-intelligence'); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_campaign_breakdown_supplemental_html($post_id, $content) {
    $lower_content = strtolower(wp_strip_all_tags($content));
    $html = '';

    if (strpos($lower_content, 'sponsor takeaways') === false) {
        $html .= '<section class="kingy-campaign-breakdown__takeaway">';
        $html .= '<h2>' . esc_html__('Sponsor Takeaways', 'kingy-ai-launch-intelligence') . '</h2>';
        $html .= '<p>' . esc_html__('This campaign is part of the Kingy AI proof hub: video-led product explanation, audience fit, and source-backed positioning for AI sponsors evaluating creator distribution.', 'kingy-ai-launch-intelligence') . '</p>';
        $html .= '</section>';
    }

    if (strpos($lower_content, 'disclosure:') === false) {
        $html .= '<section class="kingy-campaign-breakdown__disclosure">';
        $html .= '<h2>' . esc_html__('Disclosure', 'kingy-ai-launch-intelligence') . '</h2>';
        $html .= '<p>' . esc_html__('Disclosure: This page is a Kingy AI campaign breakdown based on a public Kingy AI video, available YouTube metadata, available transcript/source material, existing page content, and public product/company information. Product features, pricing, and positioning may change.', 'kingy-ai-launch-intelligence') . '</p>';
        $html .= '</section>';
    }

    $html .= '<nav class="kingy-campaign-breakdown__links" aria-label="' . esc_attr__('Related Kingy AI proof links', 'kingy-ai-launch-intelligence') . '">';
    $html .= '<a href="' . esc_url(home_url('/sponsor-kingy-ai/')) . '">' . esc_html__('Sponsor Kingy AI', 'kingy-ai-launch-intelligence') . '</a>';
    $html .= '<a href="' . esc_url(home_url('/clients/')) . '">' . esc_html__('Client Examples', 'kingy-ai-launch-intelligence') . '</a>';
    $html .= '<a href="' . esc_url(home_url('/ai-sponsored-video-roi-calculator/')) . '">' . esc_html__('ROI Calculator', 'kingy-ai-launch-intelligence') . '</a>';
    $html .= '<a href="' . esc_url(home_url('/ai-tools/')) . '">' . esc_html__('AI Tools', 'kingy-ai-launch-intelligence') . '</a>';
    $html .= '</nav>';

    return $html;
}

function kingy_ali_campaign_breakdown_seo_title($post_id) {
    $custom = kingy_ali_campaign_breakdown_meta($post_id, 'seo_title');
    if ($custom) {
        return $custom;
    }

    return sprintf(
        __('%s Campaign Breakdown | Kingy AI', 'kingy-ai-launch-intelligence'),
        kingy_ali_campaign_breakdown_product_label($post_id)
    );
}

function kingy_ali_campaign_breakdown_meta_description($post_id) {
    $custom = kingy_ali_campaign_breakdown_meta($post_id, 'meta_description');
    if ($custom) {
        return kingy_ali_meta_description_excerpt($custom);
    }

    $use_case = kingy_ali_campaign_breakdown_meta($post_id, 'campaign_category_or_use_case', __('AI product proof', 'kingy-ai-launch-intelligence'));
    return kingy_ali_meta_description_excerpt(
        sprintf(
            __('See how Kingy AI explained %s in a video-led campaign focused on %s, audience fit, and sponsor takeaways.', 'kingy-ai-launch-intelligence'),
            kingy_ali_campaign_breakdown_product_label($post_id),
            $use_case
        )
    );
}

function kingy_ali_campaign_breakdown_document_title($parts) {
    $post_id = kingy_ali_current_campaign_breakdown_post_id();
    if ($post_id && kingy_ali_campaign_breakdown_can_render_public_layout($post_id, get_post_field('post_content', $post_id))) {
        $parts['title'] = kingy_ali_campaign_breakdown_seo_title($post_id);
    }

    return $parts;
}

function kingy_ali_campaign_breakdown_wpseo_title($title) {
    $post_id = kingy_ali_current_campaign_breakdown_post_id();
    return $post_id && kingy_ali_campaign_breakdown_can_render_public_layout($post_id, get_post_field('post_content', $post_id)) ? kingy_ali_campaign_breakdown_seo_title($post_id) : $title;
}

function kingy_ali_campaign_breakdown_wpseo_metadesc($description) {
    $post_id = kingy_ali_current_campaign_breakdown_post_id();
    return $post_id && kingy_ali_campaign_breakdown_can_render_public_layout($post_id, get_post_field('post_content', $post_id)) ? kingy_ali_campaign_breakdown_meta_description($post_id) : $description;
}

function kingy_ali_output_campaign_breakdown_schema() {
    $post_id = kingy_ali_current_campaign_breakdown_post_id();
    if (!$post_id || !kingy_ali_campaign_breakdown_can_render_public_layout($post_id, get_post_field('post_content', $post_id))) {
        return;
    }

    $content = get_post_field('post_content', $post_id);
    $video_id = kingy_ali_campaign_breakdown_video_id($post_id, $content);
    $video_url = kingy_ali_campaign_breakdown_video_url($post_id, $content);
    $thumbnail = kingy_ali_campaign_breakdown_meta($post_id, 'campaign_youtube_thumbnail');
    if (!$thumbnail && $video_id) {
        $thumbnail = 'https://i.ytimg.com/vi/' . $video_id . '/hqdefault.jpg';
    }

    $article = array(
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        '@id' => get_permalink($post_id) . '#campaign-breakdown',
        'headline' => wp_strip_all_tags(get_the_title($post_id)),
        'description' => kingy_ali_campaign_breakdown_meta_description($post_id),
        'url' => get_permalink($post_id),
        'publisher' => function_exists('kingy_ali_schema_publisher') ? kingy_ali_schema_publisher() : array('@type' => 'Organization', 'name' => 'Kingy AI'),
        'datePublished' => get_the_date('c', $post_id),
        'dateModified' => get_the_modified_time('c', $post_id),
    );

    $schema = array($article);
    if ($video_id) {
        $schema[] = array(
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject',
            '@id' => get_permalink($post_id) . '#campaign-video',
            'name' => kingy_ali_campaign_breakdown_meta($post_id, 'campaign_youtube_title', wp_strip_all_tags(get_the_title($post_id))),
            'description' => kingy_ali_campaign_breakdown_meta_description($post_id),
            'thumbnailUrl' => $thumbnail ? array($thumbnail) : array(),
            'uploadDate' => kingy_ali_campaign_breakdown_meta($post_id, 'campaign_youtube_published_date'),
            'contentUrl' => $video_url,
            'embedUrl' => 'https://www.youtube.com/embed/' . $video_id,
            'publisher' => function_exists('kingy_ali_schema_publisher') ? kingy_ali_schema_publisher() : array('@type' => 'Organization', 'name' => 'Kingy AI'),
        );
    }

    echo "\n<script type=\"application/ld+json\" class=\"kingy-campaign-breakdown-schema\">" . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
}

function kingy_ali_campaign_breakdown_review_blockers($post_id, $content = '') {
    $post_id = absint($post_id);
    if (!$post_id || !kingy_ali_is_campaign_breakdown_page($post_id)) {
        return array();
    }

    $blockers = array();
    $video_id = kingy_ali_campaign_breakdown_video_id($post_id, $content);
    $product = kingy_ali_campaign_breakdown_meta($post_id, 'campaign_product_name');
    $label = kingy_ali_campaign_breakdown_product_label($post_id);

    if (!$video_id) {
        $blockers[] = 'missing_video_id';
    }

    if ($product === '') {
        $blockers[] = 'missing_product_name';
    } elseif (kingy_ali_campaign_breakdown_label_needs_review($label)) {
        $blockers[] = 'generic_product_label';
    }

    if (kingy_ali_campaign_breakdown_meta($post_id, 'campaign_official_product_url') === '') {
        $blockers[] = 'missing_official_product_url';
    }

    if (kingy_ali_campaign_breakdown_meta($post_id, 'campaign_source_urls') === '') {
        $blockers[] = 'missing_source_urls';
    }

    if (!has_post_thumbnail($post_id)) {
        $blockers[] = 'missing_featured_image';
    }

    return array_values(array_unique($blockers));
}

function kingy_ali_flag_campaign_breakdown_for_enrichment($post_id, $post, $update) {
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || !$post instanceof WP_Post) {
        return;
    }

    if (!kingy_ali_is_campaign_breakdown_page($post_id)) {
        return;
    }

    if (kingy_ali_campaign_breakdown_meta($post_id, 'campaign_format_version') === '') {
        update_post_meta($post_id, kingy_ali_meta_key('campaign_format_version'), '1');
    }

    $content = isset($post->post_content) ? (string) $post->post_content : '';
    $video_id = kingy_ali_campaign_breakdown_video_id($post_id, $content);
    if ($video_id && kingy_ali_campaign_breakdown_meta($post_id, 'campaign_video_id') === '') {
        update_post_meta($post_id, kingy_ali_meta_key('campaign_video_id'), $video_id);
        update_post_meta($post_id, kingy_ali_meta_key('campaign_video_url'), 'https://www.youtube.com/watch?v=' . $video_id);
    }

    $review_blockers = kingy_ali_campaign_breakdown_review_blockers($post_id, $content);
    if ($review_blockers) {
        update_post_meta($post_id, kingy_ali_meta_key('campaign_enrichment_status'), 'needs_enrichment');
        update_post_meta($post_id, kingy_ali_meta_key('campaign_needs_manual_review'), true);

        if (kingy_ali_campaign_breakdown_meta($post_id, 'campaign_notes') === '') {
            update_post_meta($post_id, kingy_ali_meta_key('campaign_notes'), 'Auto-review blockers: ' . implode(', ', $review_blockers));
        }
    }
}

if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI')) {
    WP_CLI::add_command('kingy campaign-breakdowns', 'Kingy_ALI_Campaign_Breakdowns_CLI');
}

class Kingy_ALI_Campaign_Breakdowns_CLI {
    public function scan($args, $assoc_args) {
        $mode = isset($assoc_args['mode']) ? sanitize_key($assoc_args['mode']) : 'dry-run';
        if (!in_array($mode, array('dry-run', 'brief-only', 'draft', 'update-existing'), true)) {
            WP_CLI::error('Mode must be dry-run, brief-only, draft, or update-existing.');
        }

        $posts = get_posts(
            array(
                'post_type' => 'page',
                'post_status' => array('publish', 'draft', 'future', 'pending', 'private'),
                'numberposts' => -1,
                's' => 'campaign breakdown',
            )
        );

        $rows = array();
        foreach ($posts as $post) {
            if (!kingy_ali_is_campaign_breakdown_page($post->ID)) {
                continue;
            }

            $video_id = kingy_ali_campaign_breakdown_video_id($post->ID, $post->post_content);
            $review_blockers = kingy_ali_campaign_breakdown_review_blockers($post->ID, $post->post_content);
            $rows[] = array(
                'ID' => $post->ID,
                'slug' => $post->post_name,
                'title' => get_the_title($post),
                'video_id' => $video_id ? $video_id : 'missing',
                'featured_media' => has_post_thumbnail($post->ID) ? (string) get_post_thumbnail_id($post->ID) : 'missing',
                'format_version' => kingy_ali_campaign_breakdown_meta($post->ID, 'campaign_format_version', 'missing'),
                'status' => kingy_ali_campaign_breakdown_meta($post->ID, 'campaign_enrichment_status', 'unreviewed'),
                'review_blockers' => $review_blockers ? implode(',', $review_blockers) : 'none',
                'mode' => $mode,
            );
        }

        WP_CLI\Utils\format_items('table', $rows, array('ID', 'slug', 'title', 'video_id', 'featured_media', 'format_version', 'status', 'review_blockers', 'mode'));
        WP_CLI::success(sprintf('Scanned %d campaign breakdown page(s). No publish action is performed by this command.', count($rows)));
    }
}
