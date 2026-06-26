<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('add_meta_boxes', 'kingy_ali_add_score_helper_meta_box');
add_action('admin_notices', 'kingy_ali_score_helper_admin_notices');
add_action('admin_post_kingy_ali_apply_suggested_scores', 'kingy_ali_handle_apply_suggested_scores');
add_action('init', 'kingy_ali_handle_visibility_score_lead');
add_action('init', 'kingy_ali_handle_sponsor_roi_lead');
add_action('wp_ajax_kingy_ali_calculate_visibility_score', 'kingy_ali_ajax_calculate_visibility_score');
add_action('wp_ajax_nopriv_kingy_ali_calculate_visibility_score', 'kingy_ali_ajax_calculate_visibility_score');

function kingy_ali_score_band($score) {
    $score = (float) $score;
    if ($score >= 8) {
        return __('High', 'kingy-ai-launch-intelligence');
    }

    if ($score >= 5.5) {
        return __('Medium', 'kingy-ai-launch-intelligence');
    }

    if ($score > 0) {
        return __('Early', 'kingy-ai-launch-intelligence');
    }

    return __('Not scored yet', 'kingy-ai-launch-intelligence');
}

function kingy_ali_score_value($value, $points) {
    return $value ? $points : 0;
}

function kingy_ali_score_meta_present($post_id, $key) {
    return kingy_ali_score_meta_text($post_id, $key) !== '';
}

function kingy_ali_score_meta_text($post_id, $key, $default = '') {
    return kingy_ali_score_text_value(kingy_ali_get_meta($post_id, $key, $default), $default);
}

function kingy_ali_score_text_value($value, $default = '') {
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

function kingy_ali_score_has_any_meta($post_id, $keys) {
    foreach ($keys as $key) {
        if (kingy_ali_score_meta_present($post_id, $key)) {
            return true;
        }
    }

    return false;
}

function kingy_ali_score_has_terms($post_id, $taxonomy) {
    $terms = get_the_terms($post_id, $taxonomy);
    return !is_wp_error($terms) && !empty($terms);
}

function kingy_ali_score_has_category_slug($post_id, $slugs) {
    $terms = get_the_terms($post_id, 'kingy_launch_category');
    if (is_wp_error($terms) || empty($terms)) {
        return false;
    }

    $actual = wp_list_pluck($terms, 'slug');
    return (bool) array_intersect($actual, $slugs);
}

function kingy_ali_suggest_launch_scores($post_id) {
    $has_demo = kingy_ali_score_has_any_meta($post_id, array('demo_url', 'youtube_url'));
    $has_traction = kingy_ali_score_has_any_meta($post_id, array('product_hunt_url', 'github_url', 'huggingface_url', 'x_url', 'reddit_signal', 'youtube_signal', 'traction_notes'));
    $has_source = kingy_ali_score_has_any_meta($post_id, array('official_url', 'sources'));
    $has_pricing = kingy_ali_score_meta_present($post_id, 'pricing');
    $has_audience = kingy_ali_score_meta_present($post_id, 'who_it_is_for') || kingy_ali_score_has_terms($post_id, 'kingy_audience');
    $has_category = kingy_ali_score_has_terms($post_id, 'kingy_launch_category');
    $has_founder = kingy_ali_score_has_any_meta($post_id, array('founder_team', 'founder_contact_email'));
    $has_funding = kingy_ali_score_meta_present($post_id, 'funding');
    $has_editorial = kingy_ali_score_has_any_meta($post_id, array('kingy_verdict', 'what_feels_promising', 'what_feels_unproven'));
    $creator_category = kingy_ali_score_has_category_slug($post_id, array('ai-video-tools', 'ai-agents', 'ai-coding-tools', 'ai-marketing-tools', 'ai-productivity-tools'));

    $launch_points = 0;
    $launch_points += kingy_ali_score_value(kingy_ali_score_meta_present($post_id, 'launch_date'), 15);
    $launch_points += kingy_ali_score_value($has_source, 10);
    $launch_points += kingy_ali_score_value(kingy_ali_score_meta_present($post_id, 'what_launched'), 10);
    $launch_points += kingy_ali_score_value($has_demo, 15);
    $launch_points += kingy_ali_score_value($has_category, 10);
    $launch_points += kingy_ali_score_value($has_audience, 10);
    $launch_points += kingy_ali_score_value($has_editorial, 10);
    $launch_points += kingy_ali_score_value($has_traction, 10);
    $launch_points += kingy_ali_score_value($creator_category || $has_audience, 10);

    $demo_points = 0;
    $demo_points += kingy_ali_score_value(kingy_ali_score_meta_present($post_id, 'demo_url'), 45);
    $demo_points += kingy_ali_score_value(kingy_ali_score_meta_present($post_id, 'youtube_url'), 25);
    $demo_points += kingy_ali_score_value(kingy_ali_score_meta_present($post_id, 'what_launched'), 10);
    $demo_points += kingy_ali_score_value($has_audience, 10);
    $demo_points += kingy_ali_score_value($has_editorial, 10);

    $youtube_points = 0;
    $youtube_points += kingy_ali_score_value($has_demo, 25);
    $youtube_points += kingy_ali_score_value($creator_category, 15);
    $youtube_points += kingy_ali_score_value($has_audience, 15);
    $youtube_points += kingy_ali_score_value($has_editorial, 15);
    $youtube_points += kingy_ali_score_value($has_traction, 10);
    $youtube_points += kingy_ali_score_value($has_pricing, 10);
    $youtube_points += kingy_ali_score_value(kingy_ali_score_meta_present($post_id, 'open_source_or_open_weight') || kingy_ali_score_meta_present($post_id, 'api_available'), 10);

    $seo_points = 0;
    $seo_points += kingy_ali_score_value(kingy_ali_score_meta_present($post_id, 'what_launched'), 15);
    $seo_points += kingy_ali_score_value($has_category, 15);
    $seo_points += kingy_ali_score_value($has_audience, 15);
    $seo_points += kingy_ali_score_value($has_pricing, 10);
    $seo_points += kingy_ali_score_value($has_source, 15);
    $seo_points += kingy_ali_score_value($has_traction, 10);
    $seo_points += kingy_ali_score_value(kingy_ali_score_has_any_meta($post_id, array('related_article_url', 'related_course_url', 'related_review_url', 'related_alternatives_url', 'related_calculator_url', 'related_tool_id')), 10);
    $seo_points += kingy_ali_score_value(kingy_ali_score_meta_present($post_id, 'last_verified'), 10);

    $partner_points = 0;
    $partner_points += kingy_ali_score_value($has_pricing, 15);
    $partner_points += kingy_ali_score_value($has_audience, 15);
    $partner_points += kingy_ali_score_value($has_demo, 15);
    $partner_points += kingy_ali_score_value(kingy_ali_score_meta_present($post_id, 'launch_date'), 15);
    $partner_points += kingy_ali_score_value($has_founder, 10);
    $partner_points += kingy_ali_score_value($has_funding || $has_traction, 10);
    $partner_points += kingy_ali_score_value($creator_category, 10);
    $budget_likelihood = kingy_ali_score_meta_text($post_id, 'budget_likelihood_internal');
    $creator_coverage_interest = kingy_ali_score_meta_text($post_id, 'creator_coverage_interest');
    $creator_campaign_interest = kingy_ali_score_meta_text($post_id, 'sponsorship_interest');
    $partner_points += kingy_ali_score_value($budget_likelihood === 'high' || $creator_coverage_interest === 'yes' || $creator_campaign_interest === 'yes', 10);

    return array(
        'kingy_launch_score' => min(10, round($launch_points / 10, 1)),
        'demo_quality_score' => min(10, round($demo_points / 10, 1)),
        'youtube_score' => min(10, round($youtube_points / 10, 1)),
        'seo_score' => min(10, round($seo_points / 10, 1)),
        'sponsor_fit_score_internal' => min(10, round($partner_points / 10, 1)),
    );
}

function kingy_ali_add_score_helper_meta_box() {
    add_meta_box(
        'kingy_ali_score_helper',
        __('Score Helper', 'kingy-ai-launch-intelligence'),
        'kingy_ali_render_score_helper_meta_box',
        'kingy_ai_launch',
        'side',
        'high'
    );
}

function kingy_ali_render_score_helper_meta_box($post) {
    $scores = kingy_ali_suggest_launch_scores($post->ID);
    echo '<p>' . esc_html__('Suggested scores based on filled launch fields. Review before applying.', 'kingy-ai-launch-intelligence') . '</p>';
    echo '<table class="widefat striped"><tbody>';
    foreach ($scores as $key => $score) {
        echo '<tr><td>' . esc_html(kingy_ali_score_label($key)) . '</td><td><strong>' . esc_html(number_format_i18n($score, 1)) . '</strong></td></tr>';
    }
    echo '</tbody></table>';
    echo '<p><a class="button button-secondary" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=kingy_ali_apply_suggested_scores&post_id=' . $post->ID), 'kingy_ali_apply_suggested_scores_' . $post->ID)) . '">' . esc_html__('Apply Suggested Scores', 'kingy-ai-launch-intelligence') . '</a></p>';
}

function kingy_ali_score_label($key) {
    $labels = array(
        'kingy_launch_score' => __('Launch', 'kingy-ai-launch-intelligence'),
        'demo_quality_score' => __('Demo', 'kingy-ai-launch-intelligence'),
        'youtube_score' => __('YouTube', 'kingy-ai-launch-intelligence'),
        'seo_score' => __('SEO', 'kingy-ai-launch-intelligence'),
        'sponsor_fit_score_internal' => __('Internal partner-fit', 'kingy-ai-launch-intelligence'),
    );

    return isset($labels[$key]) ? $labels[$key] : ucwords(str_replace('_', ' ', $key));
}

function kingy_ali_score_get_value($key) {
    $values = kingy_ali_score_get_values();
    if (!isset($values[$key])) {
        return '';
    }

    if (!is_scalar($values[$key])) {
        return '';
    }

    $value = wp_unslash($values[$key]);
    return is_scalar($value) ? (string) $value : '';
}

function kingy_ali_score_get_values() {
    return is_array($_GET) ? $_GET : array();
}

function kingy_ali_handle_apply_suggested_scores() {
    $post_id = absint(kingy_ali_score_get_value('post_id'));
    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_die(esc_html__('You do not have permission to update these scores.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_apply_suggested_scores_' . $post_id);

    foreach (kingy_ali_suggest_launch_scores($post_id) as $key => $score) {
        update_post_meta($post_id, kingy_ali_meta_key($key), $score);
    }

    wp_safe_redirect(add_query_arg('kingy_scores_applied', '1', get_edit_post_link($post_id, '')));
    exit;
}

function kingy_ali_score_helper_admin_notices() {
    if (kingy_ali_score_get_value('kingy_scores_applied') !== '1') {
        return;
    }

    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Suggested Kingy scores applied.', 'kingy-ai-launch-intelligence') . '</p></div>';
}

function kingy_ali_format_score($score) {
    if ($score === '' || $score === null) {
        return __('Not scored yet', 'kingy-ai-launch-intelligence');
    }

    return number_format_i18n((float) $score, 1) . ' / 10';
}

function kingy_ali_visibility_score_weights() {
    return array(
        'product_category' => 5,
        'launch_stage' => 7,
        'website_quality' => 8,
        'demo_quality' => 12,
        'pricing_clarity' => 8,
        'free_plan' => 5,
        'product_hunt_launch' => 7,
        'github_huggingface_activity' => 7,
        'founder_visibility' => 7,
        'youtube_demo_potential' => 12,
        'clear_use_case' => 8,
        'clear_target_audience' => 6,
        'comparison_angle' => 4,
        'launch_distribution_budget' => 4,
    );
}

function kingy_ali_visibility_score_labels() {
    return array(
        'product_category' => __('Product category', 'kingy-ai-launch-intelligence'),
        'launch_stage' => __('Launch stage', 'kingy-ai-launch-intelligence'),
        'website_quality' => __('Official website quality', 'kingy-ai-launch-intelligence'),
        'demo_quality' => __('Demo quality', 'kingy-ai-launch-intelligence'),
        'pricing_clarity' => __('Pricing clarity', 'kingy-ai-launch-intelligence'),
        'free_plan' => __('Free plan', 'kingy-ai-launch-intelligence'),
        'product_hunt_launch' => __('Product Hunt launch', 'kingy-ai-launch-intelligence'),
        'github_huggingface_activity' => __('GitHub or Hugging Face activity', 'kingy-ai-launch-intelligence'),
        'founder_visibility' => __('Founder/company visibility', 'kingy-ai-launch-intelligence'),
        'youtube_demo_potential' => __('YouTube demo potential', 'kingy-ai-launch-intelligence'),
        'clear_use_case' => __('Clear use case', 'kingy-ai-launch-intelligence'),
        'clear_target_audience' => __('Clear target audience', 'kingy-ai-launch-intelligence'),
        'comparison_angle' => __('Comparison angle', 'kingy-ai-launch-intelligence'),
        'launch_distribution_budget' => __('Launch distribution budget clarity', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_ai_launch_scorecard_weights() {
    return array(
        'product_clarity' => 15,
        'audience_clarity' => 10,
        'demo_quality' => 15,
        'website_quality' => 10,
        'pricing_clarity' => 8,
        'launch_distribution_readiness' => 12,
        'founder_company_visibility' => 8,
        'traction_signals' => 8,
        'seo_comparison_potential' => 7,
        'creator_coverage_fit' => 7,
    );
}

function kingy_ali_ai_launch_scorecard_labels() {
    return array(
        'product_clarity' => __('Product clarity', 'kingy-ai-launch-intelligence'),
        'audience_clarity' => __('Audience clarity', 'kingy-ai-launch-intelligence'),
        'demo_quality' => __('Demo quality', 'kingy-ai-launch-intelligence'),
        'website_quality' => __('Website / launch page quality', 'kingy-ai-launch-intelligence'),
        'pricing_clarity' => __('Pricing clarity', 'kingy-ai-launch-intelligence'),
        'launch_distribution_readiness' => __('Launch distribution readiness', 'kingy-ai-launch-intelligence'),
        'founder_company_visibility' => __('Founder / company visibility', 'kingy-ai-launch-intelligence'),
        'traction_signals' => __('Traction signals', 'kingy-ai-launch-intelligence'),
        'seo_comparison_potential' => __('SEO / comparison potential', 'kingy-ai-launch-intelligence'),
        'creator_coverage_fit' => __('Creator coverage fit', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_sanitize_ai_launch_scorecard_input($raw_scores) {
    $raw_scores = is_array($raw_scores) ? $raw_scores : array();
    $scores = array();
    foreach (kingy_ali_ai_launch_scorecard_weights() as $key => $weight) {
        $value = isset($raw_scores[$key]) ? kingy_ali_lead_scalar_value($raw_scores[$key]) : 0;
        $scores[$key] = max(0, min(1, (float) $value));
    }

    return $scores;
}

function kingy_ali_calculate_ai_launch_scorecard_score($input) {
    $weights = kingy_ali_ai_launch_scorecard_weights();

    $score = 0;
    foreach ($weights as $key => $weight) {
        $value = isset($input[$key]) ? (float) $input[$key] : 0;
        $value = max(0, min(1, $value));
        $score += $value * $weight;
    }

    return (int) round($score);
}

function kingy_ali_ai_launch_scorecard_tier($score) {
    $score = (int) $score;
    if ($score >= 90) {
        return __('Breakout Launch Candidate', 'kingy-ai-launch-intelligence');
    }

    if ($score >= 75) {
        return __('Coverage-Ready Launch', 'kingy-ai-launch-intelligence');
    }

    if ($score >= 60) {
        return __('Promising Launch', 'kingy-ai-launch-intelligence');
    }

    if ($score >= 40) {
        return __('Launchable, But Weak', 'kingy-ai-launch-intelligence');
    }

    return __('Invisible Launch', 'kingy-ai-launch-intelligence');
}

function kingy_ali_calculate_visibility_score($input) {
    $weights = kingy_ali_visibility_score_weights();

    $score = 0;
    foreach ($weights as $key => $weight) {
        $value = isset($input[$key]) ? (float) $input[$key] : 0;
        $value = max(0, min(1, $value));
        $score += $value * $weight;
    }

    return (int) round($score);
}

function kingy_ali_visibility_score_band($score) {
    if ($score >= 75) {
        return __('Strong', 'kingy-ai-launch-intelligence');
    }

    if ($score >= 50) {
        return __('Promising', 'kingy-ai-launch-intelligence');
    }

    return __('Needs work', 'kingy-ai-launch-intelligence');
}

function kingy_ali_visibility_recommendations($score, $input) {
    $strengths = array();
    $weak_spots = array();
    $next_steps = array();

    if (!empty($input['demo_quality']) && (float) $input['demo_quality'] >= 0.75) {
        $strengths[] = __('Strong demo potential', 'kingy-ai-launch-intelligence');
    } else {
        $weak_spots[] = __('Demo is not clear enough yet', 'kingy-ai-launch-intelligence');
        $next_steps[] = __('Create a 60-second product demo', 'kingy-ai-launch-intelligence');
    }

    if (!empty($input['clear_use_case']) && (float) $input['clear_use_case'] >= 0.75) {
        $strengths[] = __('Clear creator or business use case', 'kingy-ai-launch-intelligence');
    } else {
        $weak_spots[] = __('Use case needs a sharper explanation', 'kingy-ai-launch-intelligence');
        $next_steps[] = __('Add a simple statement of who the product helps and what changes after using it', 'kingy-ai-launch-intelligence');
    }

    if (!empty($input['pricing_clarity']) && (float) $input['pricing_clarity'] >= 0.75) {
        $strengths[] = __('Pricing is easy to understand', 'kingy-ai-launch-intelligence');
    } else {
        $weak_spots[] = __('Pricing is unclear', 'kingy-ai-launch-intelligence');
        $next_steps[] = __('Add pricing or a clear free-plan label before outreach', 'kingy-ai-launch-intelligence');
    }

    if (!empty($input['product_category']) && (float) $input['product_category'] >= 0.75) {
        $strengths[] = __('Category is easy to place', 'kingy-ai-launch-intelligence');
    } else {
        $weak_spots[] = __('Product category is not obvious enough', 'kingy-ai-launch-intelligence');
        $next_steps[] = __('Choose the launch category you want buyers, builders, or creators to remember', 'kingy-ai-launch-intelligence');
    }

    if (!empty($input['launch_stage']) && (float) $input['launch_stage'] >= 0.75) {
        $strengths[] = __('Launch stage is clear', 'kingy-ai-launch-intelligence');
    } else {
        $weak_spots[] = __('Launch stage is fuzzy', 'kingy-ai-launch-intelligence');
        $next_steps[] = __('State whether this is a beta, public launch, major update, funding announcement, or model release', 'kingy-ai-launch-intelligence');
    }

    if (!empty($input['product_hunt_launch']) && (float) $input['product_hunt_launch'] >= 0.75) {
        $strengths[] = __('Product Hunt signal is present', 'kingy-ai-launch-intelligence');
    } else {
        $next_steps[] = __('Add Product Hunt context when it exists, or explain the primary launch channel', 'kingy-ai-launch-intelligence');
    }

    if (!empty($input['github_huggingface_activity']) && (float) $input['github_huggingface_activity'] >= 0.75) {
        $strengths[] = __('Developer or model traction is visible', 'kingy-ai-launch-intelligence');
    } else {
        $next_steps[] = __('Add GitHub or Hugging Face activity if the product is technical or model-led', 'kingy-ai-launch-intelligence');
    }

    if (!empty($input['comparison_angle']) && (float) $input['comparison_angle'] >= 0.75) {
        $strengths[] = __('Good comparison angle', 'kingy-ai-launch-intelligence');
    } else {
        $next_steps[] = __('Name the obvious alternatives and what makes this different', 'kingy-ai-launch-intelligence');
    }

    if (!empty($input['launch_distribution_budget']) && (float) $input['launch_distribution_budget'] >= 0.75) {
        $strengths[] = __('Launch distribution plan is credible', 'kingy-ai-launch-intelligence');
    } else {
        $next_steps[] = __('Decide the launch distribution budget or organic channel plan before asking for coverage', 'kingy-ai-launch-intelligence');
    }

    if ($score >= 75) {
        $next_steps[] = __('Submit to Kingy AI Launch Intelligence for editorial review', 'kingy-ai-launch-intelligence');
    }

    return array(
        'strengths' => array_slice(array_unique($strengths), 0, 4),
        'weak_spots' => array_slice(array_unique($weak_spots), 0, 4),
        'next_steps' => array_slice(array_unique($next_steps), 0, 5),
    );
}

function kingy_ali_ajax_calculate_visibility_score() {
    check_ajax_referer('kingy_ali_visibility_score', 'nonce');

    $raw_scores = kingy_ali_lead_post_array('scores');
    $input = kingy_ali_sanitize_visibility_score_input($raw_scores);

    $score = kingy_ali_calculate_visibility_score($input);
    $recommendations = kingy_ali_visibility_recommendations($score, $input);

    kingy_ali_track_event(
        'visibility_score_calculated',
        array(
            'event_label' => kingy_ali_visibility_score_band($score),
            'result_count' => $score,
            'filters' => $input,
        )
    );

    wp_send_json_success(
        array(
            'score' => $score,
            'band' => kingy_ali_visibility_score_band($score),
            'recommendations' => $recommendations,
        )
    );
}

function kingy_ali_handle_visibility_score_lead() {
    if (kingy_ali_lead_post_value('kingy_ali_action') !== 'visibility_score_lead') {
        return;
    }

    if (!wp_verify_nonce(sanitize_text_field(kingy_ali_lead_post_value('kingy_ali_visibility_score_lead_nonce')), 'kingy_ali_visibility_score_lead')) {
        return;
    }

    $source = sanitize_key(kingy_ali_lead_post_value('kingy_ali_visibility_source'));
    $is_scorecard_lead = $source === 'ai_launch_scorecard';
    $redirect_base = kingy_ali_public_form_redirect_base($is_scorecard_lead ? home_url('/ai-launch-scorecard/') : home_url('/ai-launches/launch-visibility-score/'));
    $success_query_key = $is_scorecard_lead ? 'kingy_launch_scorecard_lead' : 'kingy_visibility_score_lead';
    if (kingy_ali_lead_post_value('kingy_ali_company_site') !== '') {
        wp_safe_redirect(add_query_arg($success_query_key, '1', $redirect_base));
        exit;
    }

    if ($is_scorecard_lead) {
        $raw_scores = kingy_ali_lead_post_array('kingy_ali_scorecard_scores');
        $scores = kingy_ali_sanitize_ai_launch_scorecard_input($raw_scores);
        $score = kingy_ali_calculate_ai_launch_scorecard_score($scores);
        $band = kingy_ali_ai_launch_scorecard_tier($score);
    } else {
        $raw_scores = kingy_ali_lead_post_array('kingy_ali_visibility_scores');
        $scores = kingy_ali_sanitize_visibility_score_input($raw_scores);
        $score = kingy_ali_calculate_visibility_score($scores);
        $band = kingy_ali_visibility_score_band($score);
    }

    $raw_lead = kingy_ali_lead_post_array('kingy_ali_visibility_lead');
    $lead = kingy_ali_sanitize_visibility_lead($raw_lead);

    if ($lead['email'] === '' || $lead['product_name'] === '') {
        wp_die(esc_html($is_scorecard_lead ? __('Product name and email are required for an AI Launch Scorecard review request.', 'kingy-ai-launch-intelligence') : __('Product name and email are required for a visibility score review request.', 'kingy-ai-launch-intelligence')));
    }

    if (!is_email($lead['email'])) {
        wp_die(esc_html($is_scorecard_lead ? __('Please enter a valid email address for the AI Launch Scorecard review request.', 'kingy-ai-launch-intelligence') : __('Please enter a valid email address for the visibility score review request.', 'kingy-ai-launch-intelligence')));
    }

    if (kingy_ali_lead_raw_url_was_provided($raw_lead, 'official_url') && $lead['official_url'] === '') {
        wp_die(esc_html__('Please enter a valid http or https URL for the official product URL.', 'kingy-ai-launch-intelligence'));
    }

    if (kingy_ali_visibility_lead_rate_limited($lead['email'])) {
        wp_die(esc_html__('Too many visibility score review requests were sent recently. Please wait before submitting another request.', 'kingy-ai-launch-intelligence'));
    }

    kingy_ali_increment_visibility_lead_rate_limit($lead['email']);

    kingy_ali_track_event(
        $is_scorecard_lead ? 'ai_launch_scorecard_lead' : 'visibility_score_lead',
        array(
            'event_label' => $band,
            'query_text' => $lead['product_name'],
            'result_count' => $score,
            'filters' => array_merge(
                $scores,
                array(
                    'source' => $is_scorecard_lead ? 'ai_launch_scorecard' : 'launch_visibility_score',
                    'interest' => $lead['interest'],
                    'product_name' => $lead['product_name'],
                    'official_url' => $lead['official_url'],
                    'contact_name' => $lead['contact_name'],
                    'email' => $lead['email'],
                    'notes' => $lead['notes'],
                )
            ),
        )
    );

    $admin_email = get_option('admin_email');
    if ($admin_email) {
        wp_mail(
            $admin_email,
            sprintf($is_scorecard_lead ? __('AI Launch Scorecard lead: %s', 'kingy-ai-launch-intelligence') : __('Launch Visibility Score lead: %s', 'kingy-ai-launch-intelligence'), $lead['product_name']),
            kingy_ali_visibility_lead_email_body(
                $lead,
                $score,
                $band,
                $is_scorecard_lead ? admin_url('admin.php?page=kingy-ali-scorecard-leads') : admin_url('admin.php?page=kingy-ali-analytics')
            )
        );
    }

    wp_safe_redirect(add_query_arg($success_query_key, '1', $redirect_base));
    exit;
}

function kingy_ali_lead_post_value($key) {
    $values = kingy_ali_lead_post_values();
    if (!isset($values[$key])) {
        return '';
    }

    if (!is_scalar($values[$key])) {
        return '';
    }

    $value = wp_unslash($values[$key]);
    return kingy_ali_lead_scalar_value($value);
}

function kingy_ali_lead_post_array($key) {
    $values = kingy_ali_lead_post_values();
    if (!isset($values[$key])) {
        return array();
    }

    if (!is_array($values[$key])) {
        return array();
    }

    $value = wp_unslash($values[$key]);
    return is_array($value) ? $value : array();
}

function kingy_ali_lead_post_values() {
    return is_array($_POST) ? $_POST : array();
}

function kingy_ali_lead_scalar_value($value) {
    return is_scalar($value) ? (string) $value : '';
}

function kingy_ali_sanitize_lead_text($value, $max_length = 191) {
    $text = sanitize_text_field(kingy_ali_lead_scalar_value($value));
    $max_length = absint($max_length);
    if ($max_length > 0 && function_exists('mb_strlen') && mb_strlen($text) > $max_length) {
        $text = mb_substr($text, 0, $max_length);
    } elseif ($max_length > 0 && strlen($text) > $max_length) {
        $text = substr($text, 0, $max_length);
    }

    return $text;
}

function kingy_ali_sanitize_lead_textarea($value, $max_length = 2000) {
    $text = sanitize_textarea_field(kingy_ali_lead_scalar_value($value));
    $max_length = absint($max_length);
    if ($max_length > 0 && function_exists('mb_strlen') && mb_strlen($text) > $max_length) {
        $text = mb_substr($text, 0, $max_length);
    } elseif ($max_length > 0 && strlen($text) > $max_length) {
        $text = substr($text, 0, $max_length);
    }

    return trim($text);
}

function kingy_ali_public_form_redirect_base($fallback_url) {
    $fallback_url = esc_url_raw($fallback_url, array('http', 'https'));
    if (!$fallback_url || !kingy_ali_public_form_redirect_url_is_allowed($fallback_url)) {
        $fallback_url = home_url('/');
    }

    $referer = wp_get_referer();
    if (!$referer || !is_string($referer)) {
        return $fallback_url;
    }

    $referer = esc_url_raw($referer, array('http', 'https'));
    if (!$referer || !kingy_ali_public_form_redirect_url_is_allowed($referer)) {
        return $fallback_url;
    }

    $redirect = wp_validate_redirect($referer, $fallback_url);
    return kingy_ali_public_form_redirect_url_is_allowed($redirect) ? $redirect : $fallback_url;
}

function kingy_ali_public_form_redirect_url_is_allowed($url) {
    if (!is_scalar($url)) {
        return false;
    }

    $url = trim((string) $url);
    if ($url === '' || !kingy_ali_lead_url_is_absolute_http($url)) {
        return false;
    }

    $parts = wp_parse_url($url);
    $home_parts = wp_parse_url(home_url('/'));
    if (!is_array($parts) || !is_array($home_parts)) {
        return false;
    }

    if (kingy_ali_public_form_redirect_host($parts) !== kingy_ali_public_form_redirect_host($home_parts)) {
        return false;
    }

    if (kingy_ali_public_form_redirect_port($parts) !== kingy_ali_public_form_redirect_port($home_parts)) {
        return false;
    }

    $path = kingy_ali_public_form_redirect_path($parts);
    $query = isset($parts['query']) && is_scalar($parts['query']) ? (string) $parts['query'] : '';
    return !kingy_ali_public_form_redirect_path_is_blocked($path, $query);
}

function kingy_ali_public_form_redirect_host($parts) {
    if (!is_array($parts) || !isset($parts['host']) || !is_scalar($parts['host'])) {
        return '';
    }

    return strtolower(rtrim(trim((string) $parts['host']), '.'));
}

function kingy_ali_public_form_redirect_port($parts) {
    return is_array($parts) && isset($parts['port']) ? absint($parts['port']) : 0;
}

function kingy_ali_public_form_redirect_path($parts) {
    if (!is_array($parts) || !isset($parts['path']) || !is_scalar($parts['path'])) {
        return '/';
    }

    $path = '/' . ltrim((string) $parts['path'], '/');
    return $path === '' ? '/' : $path;
}

function kingy_ali_public_form_redirect_path_is_blocked($path, $query) {
    $path = kingy_ali_public_form_redirect_normalize_path($path);
    $query = is_scalar($query) ? (string) $query : '';
    if (preg_match('/(^|&)rest_route=/', ltrim($query, '?'))) {
        return true;
    }

    foreach (kingy_ali_public_form_redirect_blocked_paths() as $blocked_path) {
        if ($blocked_path === '') {
            continue;
        }

        if (substr($blocked_path, -1) === '/') {
            if (strpos($path, $blocked_path) === 0) {
                return true;
            }
            continue;
        }

        if ($path === $blocked_path) {
            return true;
        }
    }

    return false;
}

function kingy_ali_public_form_redirect_blocked_paths() {
    $urls = array(
        admin_url('/'),
        site_url('wp-login.php'),
        site_url('xmlrpc.php'),
    );

    if (function_exists('rest_url')) {
        $rest_url = rest_url('/');
        $rest_parts = is_scalar($rest_url) ? wp_parse_url((string) $rest_url) : array();
        $rest_query = is_array($rest_parts) && isset($rest_parts['query']) ? (string) $rest_parts['query'] : '';
        if ($rest_query === '' || strpos($rest_query, 'rest_route=') === false) {
            $urls[] = $rest_url;
        }
    }

    $paths = array();
    foreach ($urls as $url) {
        $parts = is_scalar($url) ? wp_parse_url((string) $url) : array();
        if (!is_array($parts) || !isset($parts['path']) || !is_scalar($parts['path'])) {
            continue;
        }

        $paths[] = kingy_ali_public_form_redirect_normalize_path((string) $parts['path']);
    }

    return array_values(array_unique($paths));
}

function kingy_ali_public_form_redirect_normalize_path($path) {
    $path = '/' . ltrim((string) $path, '/');
    return preg_replace('#/+#', '/', $path);
}

function kingy_ali_sanitize_visibility_score_input($raw_scores) {
    $raw_scores = is_array($raw_scores) ? $raw_scores : array();
    $scores = array();
    foreach (kingy_ali_visibility_score_weights() as $key => $weight) {
        $value = isset($raw_scores[$key]) ? kingy_ali_lead_scalar_value($raw_scores[$key]) : 0;
        $scores[$key] = max(0, min(1, (float) $value));
    }

    return $scores;
}

function kingy_ali_normalize_visibility_interest($interest) {
    $interest = sanitize_key(kingy_ali_lead_scalar_value($interest));
    if ($interest === 'sponsorship') {
        return 'creator_campaign';
    }

    if (!in_array($interest, array('visibility_score', 'creator_coverage', 'creator_campaign'), true)) {
        return 'visibility_score';
    }

    return $interest;
}

function kingy_ali_visibility_interest_label($interest) {
    $interest = kingy_ali_normalize_visibility_interest($interest);
    $labels = array(
        'visibility_score' => __('Launch Visibility Score', 'kingy-ai-launch-intelligence'),
        'creator_coverage' => __('Creator coverage fit', 'kingy-ai-launch-intelligence'),
        'creator_campaign' => __('Creator campaign review', 'kingy-ai-launch-intelligence'),
    );

    return isset($labels[$interest]) ? $labels[$interest] : $labels['visibility_score'];
}

function kingy_ali_sanitize_visibility_lead($raw_lead) {
    $raw_lead = is_array($raw_lead) ? $raw_lead : array();
    $interest = isset($raw_lead['interest']) ? kingy_ali_normalize_visibility_interest($raw_lead['interest']) : 'visibility_score';

    return array(
        'product_name' => isset($raw_lead['product_name']) ? kingy_ali_sanitize_lead_text($raw_lead['product_name'], 191) : '',
        'contact_name' => isset($raw_lead['contact_name']) ? kingy_ali_sanitize_lead_text($raw_lead['contact_name'], 191) : '',
        'email' => isset($raw_lead['email']) ? sanitize_email(kingy_ali_lead_scalar_value($raw_lead['email'])) : '',
        'official_url' => isset($raw_lead['official_url']) ? kingy_ali_sanitize_lead_url($raw_lead['official_url']) : '',
        'interest' => $interest,
        'notes' => isset($raw_lead['notes']) ? kingy_ali_sanitize_lead_textarea($raw_lead['notes'], 2000) : '',
    );
}

function kingy_ali_sanitize_lead_url($value) {
    $value = trim(kingy_ali_lead_scalar_value($value));
    if ($value === '') {
        return '';
    }

    $url = esc_url_raw($value, array('http', 'https'));
    return kingy_ali_lead_url_is_absolute_http($url) ? $url : '';
}

function kingy_ali_lead_url_is_absolute_http($url) {
    $parts = wp_parse_url((string) $url);
    if (!is_array($parts)) {
        return false;
    }

    $scheme = isset($parts['scheme']) ? strtolower((string) $parts['scheme']) : '';
    $host = isset($parts['host']) ? trim((string) $parts['host']) : '';
    return in_array($scheme, array('http', 'https'), true) && $host !== '';
}

function kingy_ali_lead_raw_url_was_provided($raw_lead, $key) {
    return is_array($raw_lead) && isset($raw_lead[$key]) && trim(kingy_ali_lead_scalar_value($raw_lead[$key])) !== '';
}

if (!function_exists('kingy_ali_request_server_values')) {
    function kingy_ali_request_server_values() {
        return is_array($_SERVER) ? $_SERVER : array();
    }
}

if (!function_exists('kingy_ali_request_remote_addr')) {
    function kingy_ali_request_remote_addr() {
        $values = kingy_ali_request_server_values();
        if (!isset($values['REMOTE_ADDR'])) {
            return '';
        }

        if (!is_scalar($values['REMOTE_ADDR'])) {
            return '';
        }

        $value = wp_unslash($values['REMOTE_ADDR']);
        if (!is_scalar($value)) {
            return '';
        }

        $value = sanitize_text_field((string) $value);
        return strlen($value) > 100 ? substr($value, 0, 100) : $value;
    }
}

function kingy_ali_visibility_lead_rate_limit_key($email) {
    $ip = kingy_ali_request_remote_addr();
    $email = sanitize_email($email);
    $seed = $email ? $email : $ip;
    if ($seed === '') {
        $seed = 'anonymous_visibility_lead';
    }

    return 'kingy_ali_visibility_lead_' . hash('sha256', wp_salt('nonce') . $seed);
}

function kingy_ali_visibility_lead_rate_limited($email) {
    $count = (int) get_transient(kingy_ali_visibility_lead_rate_limit_key($email));
    return $count >= 3;
}

function kingy_ali_increment_visibility_lead_rate_limit($email) {
    $key = kingy_ali_visibility_lead_rate_limit_key($email);
    $count = (int) get_transient($key);
    set_transient($key, $count + 1, HOUR_IN_SECONDS);
}

function kingy_ali_visibility_lead_email_body($lead, $score, $band, $admin_url = '') {
    $admin_url = $admin_url ? esc_url_raw($admin_url, array('http', 'https')) : admin_url('admin.php?page=kingy-ali-analytics');

    return sprintf(
        "Product: %s\nContact: %s\nEmail: %s\nOfficial URL: %s\nInterest: %s\nScore: %d / 100 (%s)\n\nNotes:\n%s\n\nOpen admin review:\n%s",
        $lead['product_name'],
        $lead['contact_name'] ? $lead['contact_name'] : __('Not provided', 'kingy-ai-launch-intelligence'),
        $lead['email'],
        $lead['official_url'] ? $lead['official_url'] : __('Not provided', 'kingy-ai-launch-intelligence'),
        kingy_ali_visibility_interest_label($lead['interest']),
        (int) $score,
        $band,
        $lead['notes'] ? $lead['notes'] : __('Not provided', 'kingy-ai-launch-intelligence'),
        $admin_url
    );
}

function kingy_ali_sponsor_roi_fields() {
    return array(
        'expected_views' => array(
            'label' => __('Expected video views', 'kingy-ai-launch-intelligence'),
            'default' => 25000,
            'min' => 0,
            'max' => 100000000,
            'step' => 100,
        ),
        'click_through_rate' => array(
            'label' => __('Click-through rate (%)', 'kingy-ai-launch-intelligence'),
            'default' => 1.5,
            'min' => 0,
            'max' => 100,
            'step' => 0.1,
        ),
        'conversion_rate' => array(
            'label' => __('Visitor-to-lead/customer rate (%)', 'kingy-ai-launch-intelligence'),
            'default' => 5,
            'min' => 0,
            'max' => 100,
            'step' => 0.1,
        ),
        'value_per_conversion' => array(
            'label' => __('Estimated value per lead/customer ($)', 'kingy-ai-launch-intelligence'),
            'default' => 120,
            'min' => 0,
            'max' => 1000000,
            'step' => 1,
        ),
        'sponsorship_cost' => array(
            'label' => __('Creator campaign cost ($)', 'kingy-ai-launch-intelligence'),
            'default' => 2500,
            'min' => 0,
            'max' => 10000000,
            'step' => 50,
        ),
    );
}

function kingy_ali_creator_campaign_roi_public_field_key($key) {
    return $key === 'sponsorship_cost' ? 'creator_campaign_cost' : $key;
}

function kingy_ali_calculate_sponsor_roi($input) {
    $views = isset($input['expected_views']) ? (float) $input['expected_views'] : 0;
    $ctr = isset($input['click_through_rate']) ? (float) $input['click_through_rate'] : 0;
    $conversion_rate = isset($input['conversion_rate']) ? (float) $input['conversion_rate'] : 0;
    $value_per_conversion = isset($input['value_per_conversion']) ? (float) $input['value_per_conversion'] : 0;
    $cost = isset($input['sponsorship_cost']) ? (float) $input['sponsorship_cost'] : 0;

    $clicks = $views * ($ctr / 100);
    $conversions = $clicks * ($conversion_rate / 100);
    $revenue = $conversions * $value_per_conversion;
    $profit = $revenue - $cost;
    $roi = $cost > 0 ? ($profit / $cost) * 100 : 0;

    return array(
        'clicks' => round($clicks, 1),
        'conversions' => round($conversions, 1),
        'revenue' => round($revenue, 2),
        'profit' => round($profit, 2),
        'roi' => round($roi, 1),
        'band' => kingy_ali_sponsor_roi_band($roi),
    );
}

function kingy_ali_sponsor_roi_band($roi) {
    $roi = (float) $roi;
    if ($roi >= 200) {
        return __('Strong upside', 'kingy-ai-launch-intelligence');
    }

    if ($roi >= 50) {
        return __('Promising', 'kingy-ai-launch-intelligence');
    }

    if ($roi >= 0) {
        return __('Needs validation', 'kingy-ai-launch-intelligence');
    }

    return __('Risky', 'kingy-ai-launch-intelligence');
}

function kingy_ali_handle_sponsor_roi_lead() {
    $action = kingy_ali_lead_post_value('kingy_ali_action');
    if (!in_array($action, array('creator_campaign_roi_lead', 'sponsor_roi_lead'), true)) {
        return;
    }

    $nonce = kingy_ali_lead_post_value('kingy_ali_creator_campaign_roi_lead_nonce');
    $nonce_action = 'kingy_ali_creator_campaign_roi_lead';
    if ($nonce === '') {
        $nonce = kingy_ali_lead_post_value('kingy_ali_sponsor_roi_lead_nonce');
        $nonce_action = 'kingy_ali_sponsor_roi_lead';
    }

    if (!wp_verify_nonce(sanitize_text_field($nonce), $nonce_action)) {
        return;
    }

    $redirect_base = kingy_ali_public_form_redirect_base(home_url('/ai-sponsored-video-roi-calculator/'));
    if (
        kingy_ali_lead_post_value('kingy_ali_creator_campaign_company_site') !== ''
        || kingy_ali_lead_post_value('kingy_ali_sponsor_company_site') !== ''
    ) {
        wp_safe_redirect(add_query_arg('kingy_creator_campaign_roi_lead', '1', $redirect_base));
        exit;
    }

    $raw_roi = kingy_ali_lead_post_array('kingy_ali_creator_campaign_roi');
    if (!$raw_roi) {
        $raw_roi = kingy_ali_lead_post_array('kingy_ali_sponsor_roi');
    }
    $roi_input = kingy_ali_sanitize_sponsor_roi_input($raw_roi);
    $roi_result = kingy_ali_calculate_sponsor_roi($roi_input);

    $raw_lead = kingy_ali_lead_post_array('kingy_ali_creator_campaign_lead');
    if (!$raw_lead) {
        $raw_lead = kingy_ali_lead_post_array('kingy_ali_sponsor_lead');
    }
    $lead = kingy_ali_sanitize_sponsor_lead($raw_lead);

    if ($lead['email'] === '' || $lead['company_name'] === '') {
        wp_die(esc_html__('Company name and email are required for a creator campaign review request.', 'kingy-ai-launch-intelligence'));
    }

    if (!is_email($lead['email'])) {
        wp_die(esc_html__('Please enter a valid email address for the creator campaign review request.', 'kingy-ai-launch-intelligence'));
    }

    if (kingy_ali_lead_raw_url_was_provided($raw_lead, 'official_url') && $lead['official_url'] === '') {
        wp_die(esc_html__('Please enter a valid http or https URL for the official product URL.', 'kingy-ai-launch-intelligence'));
    }

    if (kingy_ali_sponsor_lead_rate_limited($lead['email'])) {
        wp_die(esc_html__('Too many creator campaign review requests were sent recently. Please wait before submitting another request.', 'kingy-ai-launch-intelligence'));
    }

    kingy_ali_increment_sponsor_lead_rate_limit($lead['email']);

    kingy_ali_track_event(
        'sponsor_roi_lead',
        array(
            'event_label' => $roi_result['band'],
            'query_text' => $lead['company_name'],
            'result_count' => (int) round($roi_result['roi']),
            'filters' => array_merge($roi_input, $roi_result, $lead),
        )
    );

    $admin_email = get_option('admin_email');
    if ($admin_email) {
        wp_mail(
            $admin_email,
            sprintf(__('Creator campaign ROI lead: %s', 'kingy-ai-launch-intelligence'), $lead['company_name']),
            kingy_ali_sponsor_roi_lead_email_body($lead, $roi_input, $roi_result)
        );
    }

    wp_safe_redirect(add_query_arg('kingy_creator_campaign_roi_lead', '1', $redirect_base));
    exit;
}

function kingy_ali_sanitize_sponsor_roi_input($raw_roi) {
    $raw_roi = is_array($raw_roi) ? $raw_roi : array();
    $input = array();
    foreach (kingy_ali_sponsor_roi_fields() as $key => $field) {
        $public_key = kingy_ali_creator_campaign_roi_public_field_key($key);
        if (isset($raw_roi[$public_key])) {
            $raw_value = $raw_roi[$public_key];
        } elseif (isset($raw_roi[$key])) {
            $raw_value = $raw_roi[$key];
        } else {
            $raw_value = $field['default'];
        }

        $value = (float) kingy_ali_lead_scalar_value($raw_value);
        $input[$key] = max((float) $field['min'], min((float) $field['max'], $value));
    }

    return $input;
}

function kingy_ali_sanitize_sponsor_lead($raw_lead) {
    $raw_lead = is_array($raw_lead) ? $raw_lead : array();

    return array(
        'company_name' => isset($raw_lead['company_name']) ? kingy_ali_sanitize_lead_text($raw_lead['company_name'], 191) : '',
        'contact_name' => isset($raw_lead['contact_name']) ? kingy_ali_sanitize_lead_text($raw_lead['contact_name'], 191) : '',
        'email' => isset($raw_lead['email']) ? sanitize_email(kingy_ali_lead_scalar_value($raw_lead['email'])) : '',
        'official_url' => isset($raw_lead['official_url']) ? kingy_ali_sanitize_lead_url($raw_lead['official_url']) : '',
        'notes' => isset($raw_lead['notes']) ? kingy_ali_sanitize_lead_textarea($raw_lead['notes'], 2000) : '',
    );
}

function kingy_ali_sponsor_lead_rate_limit_key($email) {
    $ip = kingy_ali_request_remote_addr();
    $email = sanitize_email($email);
    $seed = $email ? $email : $ip;
    if ($seed === '') {
        $seed = 'anonymous_sponsor_lead';
    }

    return 'kingy_ali_sponsor_lead_' . hash('sha256', wp_salt('nonce') . $seed);
}

function kingy_ali_sponsor_lead_rate_limited($email) {
    $count = (int) get_transient(kingy_ali_sponsor_lead_rate_limit_key($email));
    return $count >= 3;
}

function kingy_ali_increment_sponsor_lead_rate_limit($email) {
    $key = kingy_ali_sponsor_lead_rate_limit_key($email);
    $count = (int) get_transient($key);
    set_transient($key, $count + 1, HOUR_IN_SECONDS);
}

function kingy_ali_sponsor_roi_lead_email_body($lead, $roi_input, $roi_result) {
    return sprintf(
        "Company: %s\nContact: %s\nEmail: %s\nOfficial URL: %s\nExpected views: %s\nClick-through rate: %s%%\nConversion rate: %s%%\nValue per conversion: $%s\nCreator campaign cost: $%s\nEstimated clicks: %s\nEstimated conversions: %s\nProjected value: $%s\nProjected profit: $%s\nEstimated ROI: %s%% (%s)\n\nNotes:\n%s\n\nOpen analytics:\n%s",
        $lead['company_name'],
        $lead['contact_name'] ? $lead['contact_name'] : __('Not provided', 'kingy-ai-launch-intelligence'),
        $lead['email'],
        $lead['official_url'] ? $lead['official_url'] : __('Not provided', 'kingy-ai-launch-intelligence'),
        number_format_i18n($roi_input['expected_views']),
        number_format_i18n($roi_input['click_through_rate'], 1),
        number_format_i18n($roi_input['conversion_rate'], 1),
        number_format_i18n($roi_input['value_per_conversion'], 2),
        number_format_i18n($roi_input['sponsorship_cost'], 2),
        number_format_i18n($roi_result['clicks'], 1),
        number_format_i18n($roi_result['conversions'], 1),
        number_format_i18n($roi_result['revenue'], 2),
        number_format_i18n($roi_result['profit'], 2),
        number_format_i18n($roi_result['roi'], 1),
        $roi_result['band'],
        $lead['notes'] ? $lead['notes'] : __('Not provided', 'kingy-ai-launch-intelligence'),
        admin_url('admin.php?page=kingy-ali-analytics')
    );
}
