<?php

if (!defined('ABSPATH')) {
    exit;
}

function kingy_ali_derived_attribute_terms() {
    return array(
        'Free Plan' => 'free-plan',
        'Paid Only' => 'paid-only',
        'Open Source' => 'open-source',
        'Open Weight' => 'open-weight',
        'API Available' => 'api-available',
        'Founder-Submitted' => 'founder-submitted',
        'Funding Announced' => 'funding-announced',
        'Video Demo Available' => 'video-demo-available',
        'Strong Demo' => 'strong-demo',
        'Clear Use Case' => 'clear-use-case',
        'Beginner-Friendly' => 'beginner-friendly',
        'Creator-Friendly' => 'creator-friendly',
        'Business-Friendly' => 'business-friendly',
        'Developer-Friendly' => 'developer-friendly',
        'Product Hunt Traction' => 'product-hunt-traction',
        'GitHub Traction' => 'github-traction',
        'High YouTube Potential' => 'high-youtube-potential',
        'Traction Signal' => 'traction-signal',
        'Creator Coverage Candidate' => 'creator-coverage-candidate',
        'Creator Campaign Candidate' => 'sponsor-candidate',
    );
}

function kingy_ali_derived_attribute_slugs() {
    return array_values(kingy_ali_derived_attribute_terms());
}

function kingy_ali_sync_derived_attributes($post_id) {
    $post_id = absint($post_id);
    if (!$post_id || !in_array(get_post_type($post_id), array('kingy_ai_launch', 'kingy_ai_tool', 'kingy_ai_company'), true)) {
        return;
    }

    kingy_ali_ensure_derived_attribute_terms();

    $current = wp_get_object_terms($post_id, 'kingy_tool_attribute', array('fields' => 'slugs'));
    if (is_wp_error($current)) {
        $current = array();
    }

    $manual = array_diff($current, kingy_ali_derived_attribute_slugs());
    $derived = kingy_ali_calculate_derived_attribute_slugs($post_id);
    $slugs = array_values(array_unique(array_merge($manual, $derived)));

    wp_set_object_terms($post_id, $slugs, 'kingy_tool_attribute', false);
}

function kingy_ali_ensure_derived_attribute_terms() {
    foreach (kingy_ali_derived_attribute_terms() as $name => $slug) {
        if (!term_exists($slug, 'kingy_tool_attribute')) {
            wp_insert_term($name, 'kingy_tool_attribute', array('slug' => $slug));
            continue;
        }

        $existing_term = get_term_by('slug', $slug, 'kingy_tool_attribute');
        if ($existing_term && !is_wp_error($existing_term) && $existing_term->name !== $name) {
            wp_update_term($existing_term->term_id, 'kingy_tool_attribute', array('name' => $name));
        }
    }
}

function kingy_ali_calculate_derived_attribute_slugs($post_id) {
    $slugs = array();

    if (kingy_ali_attribute_meta_text($post_id, 'free_plan') === 'yes') {
        $slugs[] = 'free-plan';
    } elseif (kingy_ali_attribute_meta_text($post_id, 'free_plan') === 'no') {
        $slugs[] = 'paid-only';
    }

    if (kingy_ali_attribute_meta_text($post_id, 'api_available') === 'yes') {
        $slugs[] = 'api-available';
    }

    if (kingy_ali_attribute_meta_text($post_id, 'open_source_or_open_weight') === 'yes') {
        $slugs[] = kingy_ali_has_term_slug($post_id, 'kingy_launch_category', 'open-weight-models') ? 'open-weight' : 'open-source';
    }

    if (kingy_ali_has_any_url_meta($post_id, array('demo_url', 'youtube_url'))) {
        $slugs[] = 'video-demo-available';
    }

    if (kingy_ali_attribute_meta_number($post_id, 'demo_quality_score') >= 7) {
        $slugs[] = 'strong-demo';
    }

    if (kingy_ali_has_any_meta($post_id, array('funding'))) {
        $slugs[] = 'funding-announced';
    }

    if (kingy_ali_attribute_meta_text($post_id, 'founder_submitted') !== '') {
        $slugs[] = 'founder-submitted';
    }

    if (kingy_ali_has_any_meta($post_id, array('what_launched', 'what_it_does', 'company_summary')) && kingy_ali_has_any_meta($post_id, array('who_it_is_for', 'best_for'))) {
        $slugs[] = 'clear-use-case';
    }

    if (kingy_ali_has_any_url_meta($post_id, array('product_hunt_url', 'github_url', 'huggingface_url', 'x_url')) || kingy_ali_has_any_meta($post_id, array('reddit_signal', 'youtube_signal', 'traction_notes'))) {
        $slugs[] = 'traction-signal';
    }

    if (kingy_ali_has_any_url_meta($post_id, array('product_hunt_url'))) {
        $slugs[] = 'product-hunt-traction';
    }

    if (kingy_ali_has_any_url_meta($post_id, array('github_url'))) {
        $slugs[] = 'github-traction';
    }

    if (kingy_ali_attribute_meta_number($post_id, 'youtube_score') >= 7) {
        $slugs[] = 'high-youtube-potential';
    }

    if (kingy_ali_is_creator_friendly($post_id)) {
        $slugs[] = 'creator-friendly';
    }

    if (kingy_ali_is_beginner_friendly($post_id)) {
        $slugs[] = 'beginner-friendly';
    }

    if (kingy_ali_is_business_friendly($post_id)) {
        $slugs[] = 'business-friendly';
    }

    if (kingy_ali_is_developer_friendly($post_id)) {
        $slugs[] = 'developer-friendly';
    }

    if (kingy_ali_attribute_meta_text($post_id, 'creator_coverage_interest') === 'yes' || kingy_ali_attribute_meta_number($post_id, 'youtube_score') >= 7) {
        $slugs[] = 'creator-coverage-candidate';
    }

    if (kingy_ali_attribute_meta_text($post_id, 'sponsorship_interest') === 'yes' || kingy_ali_attribute_meta_number($post_id, 'sponsor_fit_score_internal') >= 7) {
        $slugs[] = 'sponsor-candidate';
    }

    return array_values(array_unique(array_filter($slugs)));
}

function kingy_ali_attribute_meta_text($post_id, $key, $default = '') {
    return kingy_ali_attribute_text(kingy_ali_get_meta($post_id, $key, $default), $default);
}

function kingy_ali_attribute_text($value, $default = '') {
    if (function_exists('kingy_ali_public_profile_text')) {
        return kingy_ali_public_profile_text($value, $default);
    }

    if (!is_scalar($value)) {
        return is_scalar($default) ? (string) $default : '';
    }

    $value = trim((string) $value);
    if ($value === '' && is_scalar($default)) {
        return (string) $default;
    }

    return $value;
}

function kingy_ali_attribute_meta_number($post_id, $key) {
    $value = kingy_ali_get_meta($post_id, $key);
    if (function_exists('kingy_ali_public_profile_number')) {
        return kingy_ali_public_profile_number($value);
    }

    return is_scalar($value) ? (float) $value : 0.0;
}

function kingy_ali_has_any_meta($post_id, $keys) {
    foreach ($keys as $key) {
        if (kingy_ali_attribute_meta_text($post_id, $key) !== '') {
            return true;
        }
    }

    return false;
}

function kingy_ali_has_any_url_meta($post_id, $keys) {
    foreach ($keys as $key) {
        if (kingy_ali_attribute_url_value(kingy_ali_get_meta($post_id, $key)) !== '') {
            return true;
        }
    }

    return false;
}

function kingy_ali_attribute_url_value($url) {
    if (function_exists('kingy_ali_public_url_value')) {
        return kingy_ali_public_url_value($url);
    }

    if (function_exists('kingy_ali_schema_url')) {
        return kingy_ali_schema_url($url);
    }

    if (function_exists('kingy_ali_sanitize_public_profile_link_url')) {
        return kingy_ali_sanitize_public_profile_link_url($url);
    }

    if (!is_scalar($url)) {
        return '';
    }

    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }

    $url = esc_url_raw($url, array('http', 'https'));
    if ($url === '') {
        return '';
    }

    $parts = wp_parse_url($url);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
        return '';
    }

    return in_array(strtolower((string) $parts['scheme']), array('http', 'https'), true) ? $url : '';
}

function kingy_ali_has_term_slug($post_id, $taxonomy, $slug) {
    $terms = get_the_terms($post_id, $taxonomy);
    if (is_wp_error($terms) || empty($terms)) {
        return false;
    }

    return in_array($slug, wp_list_pluck($terms, 'slug'), true);
}

function kingy_ali_has_any_term_slug($post_id, $taxonomy, $slugs) {
    foreach ($slugs as $slug) {
        if (kingy_ali_has_term_slug($post_id, $taxonomy, $slug)) {
            return true;
        }
    }

    return false;
}

function kingy_ali_is_creator_friendly($post_id) {
    return kingy_ali_has_any_term_slug($post_id, 'kingy_audience', array('creators', 'youtubers', 'designers'))
        || kingy_ali_has_any_term_slug($post_id, 'kingy_launch_category', array('ai-video-tools', 'ai-image-tools', 'ai-voice-audio-tools', 'ai-writing-tools'));
}

function kingy_ali_is_beginner_friendly($post_id) {
    return kingy_ali_has_any_term_slug($post_id, 'kingy_audience', array('students', 'small-business-owners', 'founders', 'creators'))
        || kingy_ali_has_any_term_slug($post_id, 'kingy_tool_attribute', array('no-code', 'local-first', 'mobile-app', 'browser-extension'))
        || (
            kingy_ali_attribute_meta_text($post_id, 'free_plan') === 'yes'
            && kingy_ali_has_any_term_slug($post_id, 'kingy_launch_category', array('ai-productivity-tools', 'ai-writing-tools', 'ai-image-tools', 'ai-video-tools'))
        );
}

function kingy_ali_is_business_friendly($post_id) {
    return kingy_ali_has_any_term_slug($post_id, 'kingy_audience', array('founders', 'marketers', 'small-business-owners', 'agencies', 'enterprises', 'sales-teams', 'operators'))
        || kingy_ali_has_any_term_slug($post_id, 'kingy_launch_category', array('ai-agents', 'ai-marketing-tools', 'ai-productivity-tools'));
}

function kingy_ali_is_developer_friendly($post_id) {
    return kingy_ali_has_any_term_slug($post_id, 'kingy_audience', array('developers'))
        || kingy_ali_has_any_term_slug($post_id, 'kingy_launch_category', array('ai-coding-tools', 'ai-infrastructure', 'open-weight-models'));
}
