<?php
/**
 * Publication quality gates for launch records and factory-created posts.
 *
 * @package Kingy_AI_Launch_Intelligence
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('transition_post_status', 'kingy_ali_quality_gate_status_transition', 1, 3);
add_action('save_post_post', 'kingy_ali_quality_verify_generated_article_architecture_on_save', 50, 3);

function kingy_ali_quality_gate_audience_terms() {
    return array(
        'Agencies' => 'agencies',
        'AI Agent Developers' => 'agent-developers',
        'AI App Builders' => 'ai-app-builders',
        'AI Artists' => 'ai-artists',
        'AI Builders' => 'ai-builders',
        'AI Coding Tool Users' => 'ai-coding-tool-users',
        'AI Engineers' => 'ai-engineers',
        'AI Platform Teams' => 'ai-platform-teams',
        'AI Product Teams' => 'ai-product-teams',
        'Analysts' => 'analysts',
        'Automation Teams' => 'automation-teams',
        'Bioinformatics Teams' => 'bioinformatics-teams',
        'Biology Researchers' => 'biology-researchers',
        'Builders' => 'builders',
        'Cloud Architects' => 'cloud-architects',
        'Coding Tool Builders' => 'coding-tool-builders',
        'Consumers' => 'consumers',
        'Creators' => 'creators',
        'Data Analysts' => 'data-analysts',
        'Data Teams' => 'data-teams',
        'Designers' => 'designers',
        'Developers' => 'developers',
        'DevOps Teams' => 'devops-teams',
        'Edge AI Builders' => 'edge-ai-builders',
        'Engineering Teams' => 'engineering-teams',
        'Enterprise and Business Users' => 'enterprise-and-business-users',
        'Enterprise IT' => 'enterprise-it',
        'Enterprise Platform Teams' => 'enterprise-platform-teams',
        'Enterprise Sales' => 'enterprise-sales',
        'Enterprise Workspaces' => 'enterprise-workspaces',
        'Enterprises' => 'enterprises',
        'Filmmakers' => 'filmmakers',
        'Finance Teams' => 'finance-teams',
        'Founders' => 'founders',
        'Frontend Developers' => 'frontend-developers',
        'IT Teams' => 'it-teams',
        'Life Sciences Teams' => 'life-sciences-teams',
        'Marketers' => 'marketers',
        'Nontechnical Builders' => 'nontechnical-builders',
        'Nontechnical Makers' => 'nontechnical-makers',
        'Open Source Maintainers' => 'open-source-maintainers',
        'Operations Groups' => 'operations-groups',
        'Operations Teams' => 'operations-teams',
        'Operators' => 'operators',
        'Platform Teams' => 'platform-teams',
        'Power Users' => 'power-users',
        'Product Builders' => 'product-builders',
        'Product Managers' => 'product-managers',
        'Product Teams' => 'product-teams',
        'Public Sector Teams' => 'public-sector-teams',
        'Researchers' => 'researchers',
        'Sales Teams' => 'sales-teams',
        'Security Teams' => 'security-teams',
        'Service Teams' => 'service-teams',
        'Shopify Merchants' => 'shopify-merchants',
        'Small Business Owners' => 'small-business-owners',
        'Students' => 'students',
        'YouTubers' => 'youtubers',
    );
}

function kingy_ali_quality_audience_slug_to_name() {
    static $map = null;
    if ($map !== null) {
        return $map;
    }

    $map = array();
    foreach (kingy_ali_quality_gate_audience_terms() as $name => $slug) {
        $map[$slug] = $name;
    }

    return $map;
}

function kingy_ali_quality_allowed_audience_slugs() {
    return array_fill_keys(array_keys(kingy_ali_quality_audience_slug_to_name()), true);
}

function kingy_ali_quality_audience_aliases() {
    return array(
        'agent-builders' => 'agent-developers',
        'agent-developer' => 'agent-developers',
        'ai-agent-builders' => 'agent-developers',
        'ai-agent-developers' => 'agent-developers',
        'developers-using-codex-for-interactive' => 'developers',
        'developers-using-codex-for-interactive-coding' => 'developers',
        'excel' => '',
        'pr-creation' => '',
        'commerce' => '',
        'it' => 'it-teams',
        'marketing' => 'marketers',
        'security' => 'security-teams',
        'service' => 'service-teams',
    );
}

function kingy_ali_quality_normalize_term_slugs($value, $taxonomy) {
    $taxonomy = sanitize_key((string) $taxonomy);
    $tokens = is_array($value) ? $value : explode(',', (string) $value);
    $slugs = array();

    foreach ($tokens as $token) {
        if (is_object($token) && isset($token->slug)) {
            $candidate = (string) $token->slug;
        } else {
            $candidate = is_scalar($token) ? (string) $token : '';
        }

        $candidate = trim(wp_strip_all_tags($candidate));
        if ($candidate === '') {
            continue;
        }

        $slug = sanitize_title($candidate);
        if ($taxonomy === 'kingy_audience') {
            $slug = kingy_ali_quality_normalize_audience_slug($slug, $candidate);
            if ($slug === '') {
                continue;
            }
        }

        if ($slug !== '') {
            $slugs[] = $slug;
        }
    }

    return array_values(array_unique($slugs));
}

function kingy_ali_quality_normalize_audience_slug($slug, $raw_name = '') {
    $slug = sanitize_title((string) $slug);
    $raw_name = trim((string) $raw_name);

    $aliases = kingy_ali_quality_audience_aliases();
    if (array_key_exists($slug, $aliases)) {
        return $aliases[$slug];
    }

    if (preg_match('/^(and|or)-/', $slug)) {
        return '';
    }

    if (preg_match('/-(using|deploying|building|managing|measuring|working|needing|publishing)-/', $slug)) {
        return '';
    }

    if (str_word_count(str_replace('-', ' ', $slug)) > 4) {
        return '';
    }

    $allowed = kingy_ali_quality_audience_slug_to_name();
    return isset($allowed[$slug]) ? $slug : '';
}

function kingy_ali_quality_public_filter_terms($terms, $taxonomy) {
    if ($taxonomy !== 'kingy_audience' || !is_array($terms)) {
        return $terms;
    }

    $allowed = kingy_ali_quality_allowed_audience_slugs();
    return array_values(
        array_filter(
            $terms,
            function ($term) use ($allowed) {
                return is_object($term) && isset($term->slug) && isset($allowed[sanitize_title($term->slug)]);
            }
        )
    );
}

function kingy_ali_quality_ensure_terms($post_id, $value, $taxonomy) {
    $taxonomy = sanitize_key((string) $taxonomy);
    $slugs = kingy_ali_quality_normalize_term_slugs($value, $taxonomy);
    if (!$slugs) {
        return array();
    }

    foreach ($slugs as $slug) {
        $term = get_term_by('slug', $slug, $taxonomy);
        if (!$term || is_wp_error($term)) {
            $name = $slug;
            if ($taxonomy === 'kingy_audience') {
                $names = kingy_ali_quality_audience_slug_to_name();
                $name = isset($names[$slug]) ? $names[$slug] : $slug;
            } else {
                $name = ucwords(str_replace('-', ' ', $slug));
            }
            wp_insert_term($name, $taxonomy, array('slug' => $slug));
        }
    }

    wp_set_object_terms(absint($post_id), $slugs, $taxonomy, false);
    return $slugs;
}

function kingy_ali_quality_launch_fingerprint_from_record($record) {
    $product = isset($record['product_name']) ? sanitize_title($record['product_name']) : '';
    $date = isset($record['launch_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $record['launch_date']) ? (string) $record['launch_date'] : '';
    $type = !empty($record['launch_type']) ? sanitize_title((string) $record['launch_type']) : '';
    $source = kingy_ali_quality_record_primary_source($record);

    return kingy_ali_quality_fingerprint(array('launch', $product, $date, $type, $source));
}

function kingy_ali_quality_record_primary_source($record) {
    foreach (array('official_url', 'demo_url', 'github_url', 'huggingface_url', 'product_hunt_url', 'funding', 'press_kit_url') as $key) {
        if (!empty($record[$key])) {
            $url = kingy_ali_quality_canonical_url($record[$key]);
            if ($url !== '') {
                return $url;
            }
        }
    }

    if (!empty($record['sources'])) {
        $urls = kingy_ali_quality_extract_urls($record['sources']);
        if ($urls) {
            return kingy_ali_quality_canonical_url($urls[0]);
        }
    }

    return '';
}

function kingy_ali_quality_fingerprint($parts) {
    $parts = array_map(
        function ($part) {
            return is_scalar($part) ? strtolower(trim((string) $part)) : '';
        },
        (array) $parts
    );
    $parts = array_filter($parts, 'strlen');

    return hash('sha256', implode('|', $parts));
}

function kingy_ali_quality_canonical_url($url) {
    if (!is_scalar($url)) {
        return '';
    }

    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }

    $url = esc_url_raw($url, array('http', 'https'));
    $parts = wp_parse_url($url);
    if (!is_array($parts) || empty($parts['host'])) {
        return '';
    }

    $host = strtolower((string) $parts['host']);
    $host = preg_replace('/^www\./', '', $host);
    $path = isset($parts['path']) ? trim((string) $parts['path'], '/') : '';

    return $host . ($path !== '' ? '/' . strtolower($path) : '');
}

function kingy_ali_quality_extract_urls($text) {
    preg_match_all('#https?://[^\s<>"\']+#i', (string) $text, $matches);
    return !empty($matches[0]) ? array_values(array_unique($matches[0])) : array();
}

function kingy_ali_quality_find_launch_by_fingerprint($fingerprint) {
    $fingerprint = is_scalar($fingerprint) ? trim((string) $fingerprint) : '';
    if ($fingerprint === '') {
        return null;
    }

    $posts = get_posts(
        array(
            'post_type' => 'kingy_ai_launch',
            'post_status' => array('publish', 'future', 'pending', 'draft', 'private'),
            'posts_per_page' => 1,
            'meta_key' => kingy_ali_meta_key('canonical_fingerprint'),
            'meta_value' => $fingerprint,
            'no_found_rows' => true,
        )
    );

    return $posts ? $posts[0] : null;
}

function kingy_ali_quality_find_legacy_launch_duplicate($record, $slug = '') {
    $product_slug = !empty($record['product_name']) ? sanitize_title($record['product_name']) : '';
    if ($product_slug === '') {
        return null;
    }

    $exclude_id = !empty($record['_exclude_id']) ? absint($record['_exclude_id']) : 0;
    $source = kingy_ali_quality_record_primary_source($record);
    $date = !empty($record['launch_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $record['launch_date']) ? (string) $record['launch_date'] : '';

    $query_args = array(
        'post_type' => 'kingy_ai_launch',
        'post_status' => array('publish', 'future', 'pending', 'draft', 'private'),
        'posts_per_page' => 50,
        'orderby' => 'ID',
        'order' => 'ASC',
        'no_found_rows' => true,
    );

    if ($date !== '') {
        $query_args['meta_query'] = array(
            array(
                'key' => kingy_ali_meta_key('launch_date'),
                'value' => $date,
                'compare' => '=',
            ),
        );
    }

    $posts = get_posts($query_args);
    foreach ($posts as $post) {
        if ($exclude_id && (int) $post->ID === $exclude_id) {
            continue;
        }

        $post_slug = sanitize_title($post->post_name);
        $title_slug = sanitize_title(get_the_title($post));
        if ($slug !== '' && $post_slug === sanitize_title($slug)) {
            return $post;
        }

        $source_match = $source !== '' && kingy_ali_quality_post_primary_source($post->ID) === $source;
        $product_match = strpos($post_slug, $product_slug) === 0 || strpos($title_slug, $product_slug) === 0;
        if ($product_match && ($date === '' || $source_match || strpos($post_slug, $date) !== false)) {
            return $post;
        }
    }

    return null;
}

function kingy_ali_quality_post_primary_source($post_id) {
    foreach (array('official_url', 'demo_url', 'github_url', 'huggingface_url', 'product_hunt_url', 'funding', 'press_kit_url') as $key) {
        $url = kingy_ali_quality_canonical_url(kingy_ali_get_meta($post_id, $key));
        if ($url !== '') {
            return $url;
        }
    }

    $sources = kingy_ali_quality_extract_urls(kingy_ali_get_meta($post_id, 'sources'));
    return $sources ? kingy_ali_quality_canonical_url($sources[0]) : '';
}

function kingy_ali_quality_gate_status_transition($new_status, $old_status, $post) {
    static $reverting = false;

    if ($reverting || !$post instanceof WP_Post || !in_array($new_status, array('publish', 'future'), true) || $new_status === $old_status) {
        return;
    }

    if (!in_array($post->post_type, array('post', 'kingy_ai_launch'), true)) {
        return;
    }

    $result = kingy_ali_quality_gate_evaluate_post($post->ID);
    update_post_meta($post->ID, kingy_ali_meta_key('quality_gate_result'), wp_json_encode($result));
    if (!empty($result['pass'])) {
        return;
    }

    $reverting = true;
    wp_update_post(
        array(
            'ID' => $post->ID,
            'post_status' => 'draft',
        )
    );
    $reverting = false;
}

function kingy_ali_quality_gate_evaluate_post($post_id) {
    $post_id = absint($post_id);
    $post_type = $post_id ? get_post_type($post_id) : '';
    $blockers = array();

    if (!$post_id || !in_array($post_type, array('post', 'kingy_ai_launch'), true)) {
        return array('pass' => true, 'blockers' => array());
    }

    if (!kingy_ali_quality_ensure_featured_image($post_id)) {
        $blockers[] = 'missing_featured_image';
    }

    $slug_duplicate_id = kingy_ali_quality_duplicate_slug_id($post_id);
    if ($slug_duplicate_id) {
        $blockers[] = 'duplicate_slug:' . $slug_duplicate_id;
    }

    $batch_blocker = kingy_ali_quality_batch_publication_blocker($post_id);
    if ($batch_blocker !== '') {
        $blockers[] = $batch_blocker;
    }

    if ($post_type === 'post') {
        $duplicate_id = kingy_ali_quality_duplicate_post_id($post_id);
        if ($duplicate_id) {
            $blockers[] = 'duplicate_post:' . $duplicate_id;
        }

        if (kingy_ali_quality_is_factory_post($post_id)) {
            $data_floor_result = kingy_ali_quality_generated_article_data_floor_result($post_id);
            if (empty($data_floor_result['pass'])) {
                foreach ($data_floor_result['blockers'] as $floor_blocker) {
                    $blockers[] = $floor_blocker;
                }
            }

            if (!kingy_ali_quality_post_has_human_verdict($post_id)) {
                $blockers[] = 'generic_or_missing_verdict';
            }

            if (kingy_ali_quality_truthy_meta($post_id, 'generated_article')) {
                $architecture_result = kingy_ali_quality_generated_article_architecture_result($post_id);
                if (empty($architecture_result['pass'])) {
                    foreach ($architecture_result['blockers'] as $architecture_blocker) {
                        $blockers[] = $architecture_blocker;
                    }
                }
            }
        }

        return array('pass' => empty($blockers), 'blockers' => $blockers);
    }

    $duplicate_id = kingy_ali_quality_duplicate_launch_id($post_id);
    if ($duplicate_id) {
        $blockers[] = 'duplicate_launch:' . $duplicate_id;
    }

    if (!kingy_ali_quality_launch_has_sources($post_id)) {
        $blockers[] = 'missing_source_links';
    }

    if (!kingy_ali_quality_has_verification_date($post_id)) {
        $blockers[] = 'missing_verification_date';
    }

    if (!kingy_ali_quality_pricing_has_required_source($post_id)) {
        $blockers[] = 'missing_pricing_source';
    }

    $data_floor_result = kingy_ali_quality_kali_data_floor_result($post_id);
    if (empty($data_floor_result['pass'])) {
        foreach ($data_floor_result['blockers'] as $floor_blocker) {
            $blockers[] = $floor_blocker;
        }
    }

    if (!kingy_ali_quality_launch_audiences_are_approved($post_id)) {
        $blockers[] = 'unapproved_audience_terms';
    }

    $score_result = kingy_ali_quality_launch_score_result($post_id);
    if (empty($score_result['pass'])) {
        foreach ($score_result['blockers'] as $score_blocker) {
            $blockers[] = $score_blocker;
        }
    }

    if (!kingy_ali_quality_verdict_is_specific(kingy_ali_get_meta($post_id, 'kingy_verdict'), get_the_title($post_id))) {
        $blockers[] = 'generic_or_missing_verdict';
    }

    return array('pass' => empty($blockers), 'blockers' => $blockers);
}

function kingy_ali_quality_duplicate_slug_id($post_id) {
    $post_id = absint($post_id);
    $post_type = $post_id ? get_post_type($post_id) : '';
    $slug = $post_id ? sanitize_title(get_post_field('post_name', $post_id)) : '';
    if (!$post_id || $post_type === '' || $slug === '') {
        return 0;
    }

    $posts = get_posts(
        array(
            'post_type' => $post_type,
            'post_status' => array('publish', 'future', 'pending', 'draft', 'private'),
            'posts_per_page' => 2,
            'name' => $slug,
            'orderby' => 'ID',
            'order' => 'ASC',
            'exclude' => array($post_id),
            'no_found_rows' => true,
        )
    );

    return $posts ? (int) $posts[0]->ID : 0;
}

function kingy_ali_quality_ensure_featured_image($post_id) {
    $post_id = absint($post_id);
    if (!$post_id || has_post_thumbnail($post_id)) {
        return (bool) $post_id;
    }

    if (!function_exists('set_post_thumbnail')) {
        return false;
    }

    $candidate_id = absint(get_option('kingy_ali_default_featured_image_id', 0));
    if (!$candidate_id && function_exists('apply_filters')) {
        $candidate_id = absint(apply_filters('kingy_ali_quality_featured_image_id', 0, $post_id));
    }

    if ($candidate_id) {
        set_post_thumbnail($post_id, $candidate_id);
    }

    return has_post_thumbnail($post_id);
}

function kingy_ali_quality_duplicate_post_id($post_id) {
    $post_id = absint($post_id);
    $normalized_title = kingy_ali_quality_normalized_title(get_the_title($post_id));
    if ($normalized_title === '') {
        return 0;
    }

    $posts = get_posts(
        array(
            'post_type' => 'post',
            'post_status' => array('publish', 'future', 'pending', 'draft', 'private'),
            'posts_per_page' => 30,
            's' => get_the_title($post_id),
            'orderby' => 'ID',
            'order' => 'ASC',
            'exclude' => array($post_id),
            'no_found_rows' => true,
        )
    );

    foreach ($posts as $post) {
        if (kingy_ali_quality_normalized_title($post->post_title) === $normalized_title) {
            return (int) $post->ID;
        }
    }

    return 0;
}

function kingy_ali_quality_duplicate_launch_id($post_id) {
    $post_id = absint($post_id);
    $fingerprint = kingy_ali_get_meta($post_id, 'canonical_fingerprint');
    if ($fingerprint !== '') {
        $match = kingy_ali_quality_find_launch_by_fingerprint($fingerprint);
        if ($match && (int) $match->ID !== $post_id) {
            return (int) $match->ID;
        }
    }

    $record = array(
        'product_name' => get_the_title($post_id),
        'launch_date' => kingy_ali_get_meta($post_id, 'launch_date'),
        'launch_type' => kingy_ali_quality_first_term_slug($post_id, 'kingy_launch_type'),
        'official_url' => kingy_ali_get_meta($post_id, 'official_url'),
        'demo_url' => kingy_ali_get_meta($post_id, 'demo_url'),
        'github_url' => kingy_ali_get_meta($post_id, 'github_url'),
        'huggingface_url' => kingy_ali_get_meta($post_id, 'huggingface_url'),
        'product_hunt_url' => kingy_ali_get_meta($post_id, 'product_hunt_url'),
        'funding' => kingy_ali_get_meta($post_id, 'funding'),
        'sources' => kingy_ali_get_meta($post_id, 'sources'),
        '_exclude_id' => $post_id,
    );
    $match = kingy_ali_quality_find_legacy_launch_duplicate($record, get_post_field('post_name', $post_id));

    return $match && (int) $match->ID !== $post_id ? (int) $match->ID : 0;
}

function kingy_ali_quality_normalized_title($title) {
    $title = strtolower(wp_strip_all_tags((string) $title));
    $title = preg_replace('/\s+/', ' ', $title);
    $title = preg_replace('/\s*[-–—]\s*kingy ai.*$/', '', $title);
    return trim((string) $title);
}

function kingy_ali_quality_launch_has_sources($post_id) {
    foreach (array('official_url', 'demo_url', 'github_url', 'huggingface_url', 'product_hunt_url', 'pricing_url', 'funding', 'press_kit_url') as $key) {
        if (kingy_ali_quality_canonical_url(kingy_ali_get_meta($post_id, $key)) !== '') {
            return true;
        }
    }

    return (bool) kingy_ali_quality_extract_urls(kingy_ali_get_meta($post_id, 'sources'));
}

function kingy_ali_quality_has_verification_date($post_id) {
    foreach (array('last_verified', 'verification_date', 'kali_verified_at', 'pricing_verified_at') as $key) {
        $value = trim((string) kingy_ali_get_meta($post_id, $key));
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            return true;
        }
    }

    return false;
}

function kingy_ali_quality_pricing_has_required_source($post_id) {
    $pricing = strtolower(trim(wp_strip_all_tags((string) kingy_ali_get_meta($post_id, 'pricing'))));
    if ($pricing === '' || in_array($pricing, array('unknown', 'not public', 'not published', 'sales_contact', 'contact sales'), true)) {
        return true;
    }

    foreach (array('pricing_url', 'official_url') as $key) {
        $url = kingy_ali_quality_canonical_url(kingy_ali_get_meta($post_id, $key));
        if ($url !== '') {
            return true;
        }
    }

    $sources = strtolower((string) kingy_ali_get_meta($post_id, 'sources'));
    return strpos($sources, 'pricing') !== false && (bool) kingy_ali_quality_extract_urls($sources);
}

function kingy_ali_quality_kali_data_floor_result($post_id) {
    return kingy_ali_quality_kali_data_floor_from_fields(kingy_ali_quality_kali_data_fields($post_id));
}

function kingy_ali_quality_kali_data_fields($post_id) {
    $fields = array();
    foreach (kingy_ali_quality_kali_data_point_keys() as $key) {
        $fields[$key] = kingy_ali_get_meta($post_id, $key);
    }

    $fields['source_url_count'] = count(kingy_ali_quality_extract_urls(kingy_ali_get_meta($post_id, 'sources')));
    return $fields;
}

function kingy_ali_quality_kali_data_point_keys() {
    return array(
        'last_verified',
        'verification_date',
        'pricing',
        'pricing_url',
        'pricing_history_delta',
        'feature_diff',
        'snapshot_diff',
        'hands_on_note',
        'kali_observation',
        'kingy_verdict',
        'kingy_launch_score',
        'demo_quality_score',
        'youtube_score',
        'verification_status',
        'source_count',
        'source_checks',
        'canonical_fingerprint',
        'what_feels_promising',
        'what_feels_unproven',
        'demo_url',
        'related_tool_id',
        'related_company_id',
        'latest_launch_id',
    );
}

function kingy_ali_quality_nowhere_else_keys() {
    return array(
        'pricing_history_delta',
        'feature_diff',
        'snapshot_diff',
        'hands_on_note',
        'kali_observation',
        'kingy_launch_score',
        'demo_quality_score',
        'youtube_score',
        'last_verified',
        'verification_date',
    );
}

function kingy_ali_quality_kali_data_floor_from_fields($fields) {
    $fields = is_array($fields) ? $fields : array();
    $data_points = array();
    foreach (kingy_ali_quality_kali_data_point_keys() as $key) {
        if (kingy_ali_quality_field_has_value(isset($fields[$key]) ? $fields[$key] : '')) {
            $data_points[] = $key;
        }
    }

    if (!empty($fields['source_url_count']) && absint($fields['source_url_count']) > 0) {
        $data_points[] = 'source_url_count';
    }

    $nowhere_else = array();
    foreach (kingy_ali_quality_nowhere_else_keys() as $key) {
        if (kingy_ali_quality_field_has_value(isset($fields[$key]) ? $fields[$key] : '')) {
            $nowhere_else[] = $key;
        }
    }

    $blockers = array();
    if (count($data_points) < 5) {
        $blockers[] = 'kali_data_floor_missing_points';
    }
    if (empty($nowhere_else)) {
        $blockers[] = 'kali_data_floor_missing_nowhere_else_element';
    }

    return array(
        'pass' => empty($blockers),
        'blockers' => $blockers,
        'data_points' => array_values(array_unique($data_points)),
        'nowhere_else_elements' => array_values(array_unique($nowhere_else)),
    );
}

function kingy_ali_quality_field_has_value($value) {
    if (is_array($value)) {
        return !empty(array_filter($value, 'kingy_ali_quality_field_has_value'));
    }

    if (is_object($value)) {
        return true;
    }

    if (!is_scalar($value)) {
        return false;
    }

    $value = trim(wp_strip_all_tags((string) $value));
    return $value !== '' && strtolower($value) !== 'needs review';
}

function kingy_ali_quality_is_factory_post($post_id) {
    foreach (array('generated_article', 'kali_factory_page', 'kali_generated_page', 'requires_human_verdict') as $key) {
        if (kingy_ali_quality_truthy_meta($post_id, $key)) {
            return true;
        }
    }

    return false;
}

function kingy_ali_quality_generated_article_data_floor_result($post_id) {
    $source_ids = kingy_ali_quality_source_launch_ids($post_id);
    if (!$source_ids) {
        return array('pass' => false, 'blockers' => array('missing_source_launch_ids'));
    }

    $blockers = array();
    foreach ($source_ids as $source_id) {
        if (get_post_type($source_id) !== 'kingy_ai_launch') {
            $blockers[] = 'invalid_source_launch:' . $source_id;
            continue;
        }

        $source_result = kingy_ali_quality_kali_data_floor_result($source_id);
        if (empty($source_result['pass'])) {
            $blockers[] = 'source_launch_data_floor_failure:' . $source_id;
        }
        if (!kingy_ali_quality_launch_has_sources($source_id)) {
            $blockers[] = 'source_launch_missing_sources:' . $source_id;
        }
    }

    return array('pass' => empty($blockers), 'blockers' => array_values(array_unique($blockers)));
}

function kingy_ali_quality_source_launch_ids($post_id) {
    $raw = kingy_ali_get_meta($post_id, 'source_launch_ids');
    $tokens = is_array($raw) ? $raw : preg_split('/\s*,\s*/', (string) $raw);
    $ids = array();
    foreach ((array) $tokens as $token) {
        $id = absint($token);
        if ($id) {
            $ids[] = $id;
        }
    }

    return array_values(array_unique($ids));
}

function kingy_ali_quality_reverse_link_queue_ids($post_id) {
    $raw = kingy_ali_get_meta($post_id, 'reverse_link_queue');
    $tokens = is_array($raw) ? $raw : preg_split('/\s*,\s*/', (string) $raw);
    $ids = array();
    foreach ((array) $tokens as $token) {
        $id = absint($token);
        if ($id) {
            $ids[] = $id;
        }
    }

    return array_values(array_unique($ids));
}

function kingy_ali_quality_generated_article_architecture_result($post_id) {
    $post_id = absint($post_id);
    $source_ids = kingy_ali_quality_source_launch_ids($post_id);
    $queue_ids = kingy_ali_quality_reverse_link_queue_ids($post_id);
    sort($source_ids);
    sort($queue_ids);
    $blockers = array();

    if (!has_category('ai-launch-tracker', $post_id)) {
        $blockers[] = 'missing_launch_coverage_archive_membership';
    }

    if (!$source_ids) {
        $blockers[] = 'missing_source_launch_ids';
    }

    if ($queue_ids !== $source_ids) {
        $blockers[] = 'reverse_link_queue_mismatch';
    }

    $content = (string) get_post_field('post_content', $post_id, 'raw');
    foreach ($source_ids as $source_id) {
        $source_url = get_permalink($source_id);
        $source_path = $source_url ? wp_parse_url($source_url, PHP_URL_PATH) : '';
        if (!$source_path || strpos($content, $source_path) === false) {
            $blockers[] = 'missing_source_launch_link:' . $source_id;
        }

        $reverse_urls = array(esc_url_raw((string) kingy_ali_get_meta($source_id, 'related_article_url')));
        $editorial_meta_key = function_exists('kingy_ali_related_editorial_url_meta_key')
            ? kingy_ali_related_editorial_url_meta_key()
            : kingy_ali_meta_key('related_editorial_url');
        foreach (get_post_meta($source_id, $editorial_meta_key, false) as $editorial_url) {
            if (is_scalar($editorial_url)) {
                $reverse_urls[] = esc_url_raw((string) $editorial_url);
            }
        }
        $site_host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        $has_internal_reverse_link = false;
        foreach (array_filter(array_unique($reverse_urls)) as $reverse_url) {
            if (strtolower((string) wp_parse_url($reverse_url, PHP_URL_HOST)) === $site_host) {
                $has_internal_reverse_link = true;
                break;
            }
        }
        if (!$has_internal_reverse_link) {
            $blockers[] = 'reverse_link_missing:' . $source_id;
        }
    }

    return array(
        'pass' => empty($blockers),
        'source_launch_ids' => $source_ids,
        'reverse_link_queue_ids' => $queue_ids,
        'blockers' => array_values(array_unique($blockers)),
        'verified_at' => current_time('mysql', true),
    );
}

function kingy_ali_quality_persist_architecture_verification($post_id) {
    $post_id = absint($post_id);
    if (!$post_id || !kingy_ali_quality_truthy_meta($post_id, 'generated_article')) {
        return array('pass' => true, 'blockers' => array());
    }

    $result = kingy_ali_quality_generated_article_architecture_result($post_id);
    update_post_meta($post_id, kingy_ali_meta_key('architecture_verification'), wp_json_encode($result));
    update_post_meta(
        $post_id,
        kingy_ali_meta_key('reverse_link_queue_status'),
        !empty($result['pass']) ? 'verified' : 'blocked'
    );
    return $result;
}

function kingy_ali_quality_verify_generated_article_architecture_on_save($post_id, $post, $update) {
    if (
        wp_is_post_revision($post_id)
        || wp_is_post_autosave($post_id)
        || !$post instanceof WP_Post
        || $post->post_type !== 'post'
    ) {
        return;
    }

    kingy_ali_quality_persist_architecture_verification($post_id);
}

function kingy_ali_quality_post_has_human_verdict($post_id) {
    foreach (array('kingy_human_verdict', 'human_verdict', 'editorial_verdict', 'kingy_verdict') as $key) {
        if (kingy_ali_quality_verdict_is_specific(kingy_ali_get_meta($post_id, $key), get_the_title($post_id))) {
            return true;
        }
    }

    return false;
}

function kingy_ali_quality_launch_audiences_are_approved($post_id) {
    $terms = get_the_terms($post_id, 'kingy_audience');
    if (is_wp_error($terms) || empty($terms)) {
        return true;
    }

    foreach ($terms as $term) {
        if (!is_object($term) || !isset($term->slug) || kingy_ali_quality_normalize_audience_slug($term->slug, isset($term->name) ? $term->name : '') === '') {
            return false;
        }
    }

    return true;
}

function kingy_ali_quality_launch_score_result($post_id) {
    static $cache = array();

    $post_id = absint($post_id);
    if (isset($cache[$post_id])) {
        return $cache[$post_id];
    }

    if (kingy_ali_quality_truthy_meta($post_id, 'scores_suppressed')) {
        $cache[$post_id] = array('pass' => true, 'blockers' => array());
        return $cache[$post_id];
    }

    $scores = kingy_ali_quality_launch_score_triplet($post_id);
    $present_scores = array_values(array_filter($scores, 'strlen'));
    if (!$present_scores) {
        $cache[$post_id] = array('pass' => false, 'blockers' => array('missing_or_unsuppressed_scores'));
        return $cache[$post_id];
    }

    $blockers = array();
    if (count($present_scores) === 3 && kingy_ali_quality_score_triplet_is_placeholder($scores)) {
        $blockers[] = 'placeholder_score_triplet';
    }

    foreach ($present_scores as $score) {
        if ((float) $score >= 10 && !kingy_ali_quality_truthy_meta($post_id, 'score_editorial_approval')) {
            $blockers[] = 'perfect_score_requires_editorial_approval';
            break;
        }
    }

    if (count($present_scores) === 3 && !kingy_ali_quality_truthy_meta($post_id, 'score_editorial_approval') && kingy_ali_quality_score_triplet_repetition_count($post_id, $scores) >= 2) {
        $blockers[] = 'repeated_score_triplet_requires_review';
    }

    $cache[$post_id] = array('pass' => empty($blockers), 'blockers' => array_values(array_unique($blockers)));
    return $cache[$post_id];
}

function kingy_ali_quality_launch_score_triplet($post_id) {
    return array(
        kingy_ali_quality_score_value(kingy_ali_get_meta($post_id, 'kingy_launch_score')),
        kingy_ali_quality_score_value(kingy_ali_get_meta($post_id, 'demo_quality_score')),
        kingy_ali_quality_score_value(kingy_ali_get_meta($post_id, 'youtube_score')),
    );
}

function kingy_ali_quality_score_value($score) {
    if (!is_scalar($score)) {
        return '';
    }

    $score = trim((string) $score);
    return is_numeric($score) ? number_format((float) $score, 1, '.', '') : '';
}

function kingy_ali_quality_score_triplet_is_placeholder($scores) {
    $triplet = implode('|', array_map('strval', (array) $scores));
    if (in_array(
        $triplet,
        array(
            '7.2|6.8|6.0',
            '7.2|6.8|6.8',
            '7.2|6.8|7.1',
            '7.3|6.9|6.0',
            '7.3|6.9|7.1',
            '8.2|7.7|8.0',
            '8.2|7.7|7.7',
            '10.0|6.8|6.0',
            '10.0|6.8|7.1',
            '10.0|7.5|6.0',
        ),
        true
    )) {
        return true;
    }

    return in_array($scores[0] . '|' . $scores[1], array('7.2|6.8', '7.3|6.9', '8.2|7.7', '10.0|6.8'), true);
}

function kingy_ali_quality_score_triplet_repetition_count($post_id, $scores) {
    if (in_array('', $scores, true)) {
        return 0;
    }

    $posts = get_posts(
        array(
            'post_type' => 'kingy_ai_launch',
            'post_status' => array('publish', 'future'),
            'posts_per_page' => 4,
            'post__not_in' => array(absint($post_id)),
            'meta_query' => array(
                'relation' => 'AND',
                array('key' => kingy_ali_meta_key('kingy_launch_score'), 'value' => (string) (float) $scores[0], 'compare' => '='),
                array('key' => kingy_ali_meta_key('demo_quality_score'), 'value' => (string) (float) $scores[1], 'compare' => '='),
                array('key' => kingy_ali_meta_key('youtube_score'), 'value' => (string) (float) $scores[2], 'compare' => '='),
            ),
            'no_found_rows' => true,
        )
    );

    return count($posts);
}

function kingy_ali_quality_verdict_is_specific($verdict, $title = '') {
    if (!is_scalar($verdict)) {
        return false;
    }

    $verdict = trim(wp_strip_all_tags((string) $verdict));
    if (strlen($verdict) < 80) {
        return false;
    }

    $sentence_count = kingy_ali_quality_sentence_count($verdict);
    if ($sentence_count < 2 || $sentence_count > 4) {
        return false;
    }

    $lower = strtolower($verdict);
    foreach (kingy_ali_quality_blocked_verdict_fragments() as $fragment) {
        if (strpos($lower, $fragment) !== false) {
            return false;
        }
    }

    $title_slug = sanitize_title($title);
    if ($title_slug !== '') {
        $title_parts = array_filter(explode('-', $title_slug));
        $meaningful = array_filter(
            $title_parts,
            function ($part) {
                return strlen($part) > 3 && !in_array($part, array('launch', 'release', 'funding', 'announcement', 'guide'), true);
            }
        );
        if ($meaningful && !preg_match('/\b(' . implode('|', array_map('preg_quote', array_slice($meaningful, 0, 5))) . ')\b/i', $verdict)) {
            return false;
        }
    }

    return true;
}

function kingy_ali_quality_sentence_count($text) {
    $text = trim(wp_strip_all_tags((string) $text));
    if ($text === '') {
        return 0;
    }

    $sentences = preg_split('/[.!?]+[\s\)]*/', $text);
    $sentences = array_filter(
        (array) $sentences,
        function ($sentence) {
            return strlen(trim((string) $sentence)) > 20;
        }
    );

    return count($sentences);
}

function kingy_ali_quality_blocked_verdict_fragments() {
    return array(
        'worth tracking because it gives the tool a concrete',
        'source-backed product history point',
        'teams should still verify current plan limits',
        'promising starter record for launch radar planning',
        'needs verification before kingy ai publishes',
        'funding is treated as a market/distribution signal, not automatic product proof',
    );
}

function kingy_ali_quality_public_score_label($post_id, $score_key) {
    $post_id = absint($post_id);
    $score_key = sanitize_key((string) $score_key);
    if (get_post_type($post_id) === 'kingy_ai_launch' && function_exists('kingy_ali_launch_score_snapshot')) {
        $snapshot = kingy_ali_launch_score_snapshot($post_id);
        $map = array('kingy_launch_score' => 'kingy', 'demo_quality_score' => 'demo');
        if (isset($map[$score_key])) {
            return $snapshot[$map[$score_key]]['label'];
        }
    } elseif (get_post_type($post_id) === 'kingy_ai_launch') {
        if (kingy_ali_quality_truthy_meta($post_id, 'scores_suppressed')) {
            return __('Needs review', 'kingy-ai-launch-intelligence');
        }
        $score_result = kingy_ali_quality_launch_score_result($post_id);
        if (empty($score_result['pass'])) {
            return __('Needs review', 'kingy-ai-launch-intelligence');
        }
    }

    return kingy_ali_format_score(kingy_ali_get_meta($post_id, $score_key));
}

function kingy_ali_quality_public_score_band($post_id, $score_key) {
    $post_id = absint($post_id);
    $score_key = sanitize_key((string) $score_key);
    if (get_post_type($post_id) === 'kingy_ai_launch' && function_exists('kingy_ali_launch_score_snapshot')) {
        $snapshot = kingy_ali_launch_score_snapshot($post_id);
        return $snapshot['youtube']['label'];
    } elseif (get_post_type($post_id) === 'kingy_ai_launch') {
        if (kingy_ali_quality_truthy_meta($post_id, 'scores_suppressed')) {
            return __('Needs review', 'kingy-ai-launch-intelligence');
        }
        $score_result = kingy_ali_quality_launch_score_result($post_id);
        if (empty($score_result['pass'])) {
            return __('Needs review', 'kingy-ai-launch-intelligence');
        }
    }

    return kingy_ali_score_band(kingy_ali_get_meta($post_id, $score_key));
}

function kingy_ali_quality_truthy_meta($post_id, $key) {
    $value = kingy_ali_get_meta($post_id, $key);
    return in_array($value, array(1, '1', true, 'yes', 'true', 'approved'), true);
}

function kingy_ali_quality_batch_publication_blocker($post_id) {
    $batch_id = kingy_ali_quality_post_batch_id($post_id);
    if ($batch_id === '') {
        return '';
    }

    return kingy_ali_quality_batch_is_quarantined($batch_id) ? 'batch_quarantined:' . $batch_id : '';
}

function kingy_ali_quality_post_batch_id($post_id) {
    foreach (array('kali_batch_id', 'generation_batch_id', 'batch_id') as $key) {
        $value = trim((string) kingy_ali_get_meta($post_id, $key));
        if ($value !== '') {
            return sanitize_key($value);
        }
    }

    return '';
}

function kingy_ali_quality_batch_review_should_quarantine($sample_results) {
    foreach ((array) $sample_results as $result) {
        if (is_array($result) && isset($result['pass']) && empty($result['pass'])) {
            return true;
        }
        if ($result === false || $result === 'fail' || $result === 'failed') {
            return true;
        }
    }

    return false;
}

function kingy_ali_quality_batch_is_quarantined($batch_id) {
    $batch_id = sanitize_key((string) $batch_id);
    if ($batch_id === '') {
        return false;
    }

    return (bool) get_option(kingy_ali_quality_batch_quarantine_option_key($batch_id), false);
}

function kingy_ali_quality_quarantine_batch($batch_id, $reason, $failed_post_id = 0) {
    $batch_id = sanitize_key((string) $batch_id);
    if ($batch_id === '') {
        return false;
    }

    $payload = array(
        'batch_id' => $batch_id,
        'reason' => sanitize_text_field((string) $reason),
        'failed_post_id' => absint($failed_post_id),
        'quarantined_at' => current_time('mysql'),
    );

    update_option(kingy_ali_quality_batch_quarantine_option_key($batch_id), $payload, false);
    return true;
}

function kingy_ali_quality_record_batch_review($batch_id, $sample_results, $reason = 'sample_failure') {
    if (!kingy_ali_quality_batch_review_should_quarantine($sample_results)) {
        return array('quarantined' => false, 'batch_id' => sanitize_key((string) $batch_id));
    }

    kingy_ali_quality_quarantine_batch($batch_id, $reason);
    return array('quarantined' => true, 'batch_id' => sanitize_key((string) $batch_id), 'reason' => $reason);
}

function kingy_ali_quality_batch_quarantine_option_key($batch_id) {
    return 'kingy_ali_batch_quarantine_' . md5(sanitize_key((string) $batch_id));
}

function kingy_ali_quality_generation_batch_lock_option_key() {
    return 'kingy_ali_generation_batch_in_flight';
}

function kingy_ali_quality_generation_batch_lock_is_active($lock, $now = 0) {
    if (!is_array($lock) || empty($lock['batch_id'])) {
        return false;
    }

    $expires_at = !empty($lock['expires_at']) ? (int) $lock['expires_at'] : 0;
    $now = $now > 0 ? (int) $now : time();
    return $expires_at === 0 || $expires_at > $now;
}

function kingy_ali_quality_claim_generation_batch($batch_id, $context = array(), $ttl_seconds = 1800) {
    $batch_id = sanitize_key((string) $batch_id);
    if ($batch_id === '') {
        return array('claimed' => false, 'blocker' => 'missing_batch_id');
    }

    $option_key = kingy_ali_quality_generation_batch_lock_option_key();
    $now = time();
    $active_lock = get_option($option_key, false);
    if (kingy_ali_quality_generation_batch_lock_is_active($active_lock, $now)) {
        $active_batch_id = sanitize_key((string) $active_lock['batch_id']);
        return array(
            'claimed' => false,
            'blocker' => 'batch_in_flight:' . $active_batch_id,
            'active_batch_id' => $active_batch_id,
        );
    }

    $ttl_seconds = max(60, absint($ttl_seconds));
    $payload = array(
        'batch_id' => $batch_id,
        'claimed_at' => current_time('mysql'),
        'expires_at' => $now + $ttl_seconds,
        'context' => is_array($context) ? $context : array(),
    );
    update_option($option_key, $payload, false);

    return array('claimed' => true, 'batch_id' => $batch_id);
}

function kingy_ali_quality_release_generation_batch($batch_id) {
    $batch_id = sanitize_key((string) $batch_id);
    if ($batch_id === '') {
        return false;
    }

    $option_key = kingy_ali_quality_generation_batch_lock_option_key();
    $active_lock = get_option($option_key, false);
    if (!is_array($active_lock) || empty($active_lock['batch_id']) || sanitize_key((string) $active_lock['batch_id']) !== $batch_id) {
        return false;
    }

    if (function_exists('delete_option')) {
        delete_option($option_key);
    } else {
        update_option($option_key, false, false);
    }

    return true;
}

function kingy_ali_quality_first_term_slug($post_id, $taxonomy) {
    $terms = get_the_terms($post_id, $taxonomy);
    if (is_wp_error($terms) || empty($terms)) {
        return '';
    }

    return isset($terms[0]->slug) ? (string) $terms[0]->slug : '';
}

function kingy_ali_quality_import_status($requested_status, $post_id) {
    $requested_status = sanitize_key((string) $requested_status);
    if (!in_array($requested_status, array('publish', 'future'), true)) {
        return $requested_status;
    }

    $result = kingy_ali_quality_gate_evaluate_post($post_id);
    update_post_meta($post_id, kingy_ali_meta_key('quality_gate_result'), wp_json_encode($result));

    return !empty($result['pass']) ? $requested_status : 'draft';
}

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('kingy-ali quality', 'Kingy_ALI_Quality_Gate_CLI');
}

class Kingy_ALI_Quality_Gate_CLI {
    public function audit($args, $assoc_args) {
        unset($args);

        $report = array(
            'duplicate_posts' => kingy_ali_quality_duplicate_post_clusters(),
            'bad_audience_terms' => kingy_ali_quality_bad_audience_terms(),
            'published_launch_blockers' => kingy_ali_quality_published_launch_blockers(),
        );

        $format = isset($assoc_args['format']) ? sanitize_key($assoc_args['format']) : 'table';
        if ($format === 'json') {
            WP_CLI::line(wp_json_encode($report, JSON_PRETTY_PRINT));
            return;
        }

        WP_CLI::line('Duplicate posts: ' . count($report['duplicate_posts']));
        WP_CLI::line('Bad audience terms: ' . count($report['bad_audience_terms']));
        WP_CLI::line('Published launches with blockers: ' . count($report['published_launch_blockers']));
    }

    public function remediate_duplicates($args, $assoc_args) {
        unset($args);

        $apply = !empty($assoc_args['apply']);
        $clusters = kingy_ali_quality_duplicate_post_clusters();
        $changes = array();

        foreach ($clusters as $cluster) {
            $canonical = reset($cluster['posts']);
            foreach (array_slice($cluster['posts'], 1) as $duplicate) {
                $changes[] = array(
                    'canonical_id' => $canonical['id'],
                    'duplicate_id' => $duplicate['id'],
                    'duplicate_slug' => $duplicate['slug'],
                    'action' => $apply ? 'drafted' : 'would_draft',
                );
                if ($apply) {
                    wp_update_post(array('ID' => $duplicate['id'], 'post_status' => 'draft'));
                    update_post_meta($duplicate['id'], kingy_ali_meta_key('duplicate_canonical_post_id'), $canonical['id']);
                }
            }
        }

        WP_CLI::line(wp_json_encode($changes, JSON_PRETTY_PRINT));
    }

    public function backfill_fingerprints($args, $assoc_args) {
        unset($args);

        $apply = !empty($assoc_args['apply']);
        $rows = array();
        $posts = get_posts(
            array(
                'post_type' => 'kingy_ai_launch',
                'post_status' => array('publish', 'future', 'pending', 'draft', 'private'),
                'posts_per_page' => -1,
                'orderby' => 'ID',
                'order' => 'ASC',
            )
        );

        foreach ($posts as $post) {
            $record = array(
                'product_name' => get_the_title($post),
                'launch_date' => kingy_ali_get_meta($post->ID, 'launch_date'),
                'launch_type' => kingy_ali_quality_first_term_slug($post->ID, 'kingy_launch_type'),
                'official_url' => kingy_ali_get_meta($post->ID, 'official_url'),
                'demo_url' => kingy_ali_get_meta($post->ID, 'demo_url'),
                'github_url' => kingy_ali_get_meta($post->ID, 'github_url'),
                'huggingface_url' => kingy_ali_get_meta($post->ID, 'huggingface_url'),
                'product_hunt_url' => kingy_ali_get_meta($post->ID, 'product_hunt_url'),
                'funding' => kingy_ali_get_meta($post->ID, 'funding'),
                'sources' => kingy_ali_get_meta($post->ID, 'sources'),
            );
            $fingerprint = kingy_ali_quality_launch_fingerprint_from_record($record);
            $rows[] = array('id' => $post->ID, 'slug' => $post->post_name, 'fingerprint' => $fingerprint, 'action' => $apply ? 'updated' : 'would_update');
            if ($apply) {
                update_post_meta($post->ID, kingy_ali_meta_key('canonical_fingerprint'), $fingerprint);
            }
        }

        WP_CLI::line(wp_json_encode($rows, JSON_PRETTY_PRINT));
    }

    public function remediate_audiences($args, $assoc_args) {
        unset($args);

        $apply = !empty($assoc_args['apply']);
        $plan = kingy_ali_quality_audience_remediation_plan();
        foreach ($plan as $item) {
            if (!$apply) {
                continue;
            }

            if (!empty($item['target_slug'])) {
                $target = get_term_by('slug', $item['target_slug'], 'kingy_audience');
                if (!$target || is_wp_error($target)) {
                    $names = kingy_ali_quality_audience_slug_to_name();
                    $target_name = isset($names[$item['target_slug']]) ? $names[$item['target_slug']] : ucwords(str_replace('-', ' ', $item['target_slug']));
                    wp_insert_term($target_name, 'kingy_audience', array('slug' => $item['target_slug']));
                }
            }

            foreach ($item['object_ids'] as $object_id) {
                if (!empty($item['target_slug'])) {
                    wp_set_object_terms($object_id, $item['target_slug'], 'kingy_audience', true);
                }
                wp_remove_object_terms($object_id, (int) $item['term_id'], 'kingy_audience');
            }

            wp_delete_term((int) $item['term_id'], 'kingy_audience');
        }

        WP_CLI::line(wp_json_encode($plan, JSON_PRETTY_PRINT));
    }
}

function kingy_ali_quality_duplicate_post_clusters() {
    $posts = get_posts(
        array(
            'post_type' => 'post',
            'post_status' => array('publish', 'future'),
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        )
    );
    $groups = array();
    foreach ($posts as $post) {
        $key = kingy_ali_quality_normalized_title($post->post_title);
        if ($key === '') {
            continue;
        }
        $groups[$key][] = array(
            'id' => (int) $post->ID,
            'date' => $post->post_date,
            'slug' => $post->post_name,
            'title' => $post->post_title,
            'link' => get_permalink($post),
        );
    }

    foreach ($groups as $key => $posts) {
        usort($posts, 'kingy_ali_quality_duplicate_post_sort');
        $groups[$key] = $posts;
    }

    return array_values(
        array_filter(
            array_map(
                function ($title_key, $posts) {
                    return count($posts) > 1 ? array('normalized_title' => $title_key, 'posts' => $posts) : null;
                },
                array_keys($groups),
                $groups
            )
        )
    );
}

function kingy_ali_quality_duplicate_post_sort($a, $b) {
    $a_suffix = preg_match('/-\d+$/', (string) $a['slug']) ? 1 : 0;
    $b_suffix = preg_match('/-\d+$/', (string) $b['slug']) ? 1 : 0;
    if ($a_suffix !== $b_suffix) {
        return $a_suffix <=> $b_suffix;
    }

    return ((int) $a['id']) <=> ((int) $b['id']);
}

function kingy_ali_quality_bad_audience_terms() {
    $terms = get_terms(array('taxonomy' => 'kingy_audience', 'hide_empty' => false));
    if (is_wp_error($terms)) {
        return array();
    }

    $bad = array();
    $allowed = kingy_ali_quality_allowed_audience_slugs();
    foreach ($terms as $term) {
        if (!isset($allowed[sanitize_title($term->slug)])) {
            $bad[] = array('id' => (int) $term->term_id, 'slug' => $term->slug, 'name' => $term->name, 'count' => (int) $term->count);
        }
    }

    return $bad;
}

function kingy_ali_quality_audience_remediation_plan() {
    $terms = get_terms(array('taxonomy' => 'kingy_audience', 'hide_empty' => false));
    if (is_wp_error($terms)) {
        return array();
    }

    $aliases = kingy_ali_quality_audience_aliases();
    $allowed = kingy_ali_quality_allowed_audience_slugs();
    $plan = array();
    foreach ($terms as $term) {
        $source_slug = sanitize_title($term->slug);
        if (isset($allowed[$source_slug])) {
            continue;
        }

        $target_slug = '';
        if (array_key_exists($source_slug, $aliases)) {
            $target_slug = $aliases[$source_slug];
        } else {
            $target_slug = kingy_ali_quality_normalize_audience_slug($source_slug, $term->name);
        }

        if ($target_slug !== '' && !isset($allowed[$target_slug])) {
            $target_slug = '';
        }

        $object_ids = get_objects_in_term((int) $term->term_id, 'kingy_audience');
        if (is_wp_error($object_ids)) {
            $object_ids = array();
        }

        $plan[] = array(
            'term_id' => (int) $term->term_id,
            'slug' => $term->slug,
            'name' => $term->name,
            'count' => (int) $term->count,
            'target_slug' => $target_slug,
            'action' => $target_slug !== '' ? 'map' : 'retire',
            'object_ids' => array_map('absint', (array) $object_ids),
        );
    }

    return $plan;
}

function kingy_ali_quality_published_launch_blockers() {
    $posts = get_posts(
        array(
            'post_type' => 'kingy_ai_launch',
            'post_status' => array('publish', 'future'),
            'posts_per_page' => -1,
            'orderby' => 'ID',
            'order' => 'ASC',
        )
    );
    $rows = array();
    foreach ($posts as $post) {
        $result = kingy_ali_quality_gate_evaluate_post($post->ID);
        if (empty($result['pass'])) {
            $rows[] = array('id' => (int) $post->ID, 'slug' => $post->post_name, 'title' => $post->post_title, 'blockers' => $result['blockers']);
        }
    }

    return $rows;
}
