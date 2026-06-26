<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'kingy_ali_handle_correction_submission');

function kingy_ali_launch_score_methodology_note() {
    return __('Kingy AI launch scores are readiness heuristics. They measure whether a launch is clear, source-backed, demonstrable, searchable, and useful for editorial or creator review; they do not measure product quality, safety, retention, revenue, market share, Product Hunt rank, or guaranteed coverage.', 'kingy-ai-launch-intelligence');
}

function kingy_ali_creator_disclosure_note() {
    return __('Creator coverage and creator campaign reviews are planning signals only. Any paid, gifted, affiliate, or otherwise materially supported creator coverage should be disclosed clearly in the published content, creator brief, and campaign tracking.', 'kingy-ai-launch-intelligence');
}

function kingy_ali_launch_data_privacy_note() {
    return __('Form submissions, correction notes, score details, URLs, and analytics events may be stored for editorial review, spam prevention, product improvement, and follow-up. Do not submit secrets, unreleased financials, private customer data, or regulated personal data through these forms.', 'kingy-ai-launch-intelligence');
}

function kingy_ali_index_readiness_summary() {
    return __('Index-ready launch profiles need an official URL, launch date, clear launch description, audience, known pricing, editorial verdict, recent verification date, at least one useful source or related link, and a launch category. Tool profiles need an official URL, clear product description, known pricing, public launch history, last verification date, and category. Company profiles need an official URL, company summary, AI-specific evidence, source notes or source links, last verification date, and category. Model profiles need provider and modality context, an official source, clear model overview, release date or status note, access or pricing notes, benchmark caveat, source links, verification status, and last verification date.', 'kingy-ai-launch-intelligence');
}

function kingy_ali_source_url_fields() {
    return array(
        'official_url' => __('Official site', 'kingy-ai-launch-intelligence'),
        'demo_url' => __('Demo', 'kingy-ai-launch-intelligence'),
        'product_hunt_url' => __('Product Hunt', 'kingy-ai-launch-intelligence'),
        'github_url' => __('GitHub', 'kingy-ai-launch-intelligence'),
        'huggingface_url' => __('Hugging Face', 'kingy-ai-launch-intelligence'),
        'x_url' => __('X/social', 'kingy-ai-launch-intelligence'),
        'youtube_url' => __('YouTube/demo video', 'kingy-ai-launch-intelligence'),
        'press_kit_url' => __('Press kit', 'kingy-ai-launch-intelligence'),
        'contact_url' => __('Contact page', 'kingy-ai-launch-intelligence'),
        'related_review_url' => __('Related review', 'kingy-ai-launch-intelligence'),
        'related_alternatives_url' => __('Alternatives page', 'kingy-ai-launch-intelligence'),
        'official_announcement_url' => __('Official announcement', 'kingy-ai-launch-intelligence'),
        'official_docs_url' => __('Official docs', 'kingy-ai-launch-intelligence'),
        'api_reference_url' => __('API reference', 'kingy-ai-launch-intelligence'),
        'model_card_url' => __('Model card', 'kingy-ai-launch-intelligence'),
        'system_card_url' => __('System/safety card', 'kingy-ai-launch-intelligence'),
        'evals_url' => __('Official evals', 'kingy-ai-launch-intelligence'),
        'pricing_url' => __('Pricing', 'kingy-ai-launch-intelligence'),
        'license_url' => __('License', 'kingy-ai-launch-intelligence'),
        'weights_url' => __('Weights/download', 'kingy-ai-launch-intelligence'),
        'context_source_url' => __('Context window source', 'kingy-ai-launch-intelligence'),
        'benchmark_url' => __('Benchmark/source', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_public_source_links($post_id) {
    $links = array();
    foreach (kingy_ali_source_url_fields() as $key => $label) {
        $url = kingy_ali_get_meta($post_id, $key);
        if (!$url) {
            continue;
        }

        $links[] = array(
            'label' => $label,
            'url' => $url,
        );
    }

    $funding_source = kingy_ali_meta_text_source_link($post_id, 'funding', __('Funding announcement', 'kingy-ai-launch-intelligence'));
    if ($funding_source) {
        $links[] = $funding_source;
    }

    $sources = kingy_ali_get_meta($post_id, 'sources');
    if (is_scalar($sources) && $sources !== '') {
        $line_number = 1;
        foreach (array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $sources))) as $line) {
            $source = kingy_ali_parse_source_line($line, $line_number);
            if ($source) {
                $links[] = $source;
            }
            $line_number++;
        }
    }

    return kingy_ali_unique_source_links($links);
}

function kingy_ali_meta_text_source_link($post_id, $key, $label) {
    $value = kingy_ali_get_meta($post_id, $key);
    if (!is_scalar($value) || $value === '' || !preg_match('/https?:\/\/[^\s<>"\']+/i', (string) $value, $matches)) {
        return array();
    }

    $url = kingy_ali_sanitize_public_source_url(rtrim($matches[0], '.,);]'));
    if (!$url) {
        return array();
    }

    return array(
        'label' => $label,
        'url' => $url,
    );
}

function kingy_ali_parse_source_line($line, $line_number = 1) {
    if (!is_scalar($line)) {
        return array();
    }

    $line = trim(wp_strip_all_tags((string) $line));
    if ($line === '') {
        return array();
    }

    $raw_url = '';
    if (preg_match('/https?:\/\/[^\s<>"\']+/i', $line, $matches)) {
        $raw_url = $matches[0];
    } elseif (wp_http_validate_url($line)) {
        $raw_url = $line;
    }

    $url = kingy_ali_sanitize_public_source_url(rtrim($raw_url, '.,);]'));

    if (!$url) {
        return array();
    }

    $label = trim(str_replace($raw_url, '', $line));
    $label = trim($label, " \t\n\r\0\x0B-|:");
    if (!$label) {
        $label = sprintf(__('Source %d', 'kingy-ai-launch-intelligence'), absint($line_number));
    }

    return array(
        'label' => $label,
        'url' => $url,
    );
}

function kingy_ali_unique_source_links($links) {
    $unique = array();
    $seen = array();
    foreach ($links as $link) {
        if (!is_array($link)) {
            continue;
        }

        if (empty($link['url'])) {
            continue;
        }

        $url = kingy_ali_sanitize_public_source_url($link['url']);
        if (!$url) {
            continue;
        }

        $key = strtolower(untrailingslashit($url));
        if (isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;
        $label = isset($link['label']) && is_scalar($link['label']) ? trim((string) $link['label']) : '';
        $unique[] = array(
            'label' => $label !== '' ? $label : __('Source', 'kingy-ai-launch-intelligence'),
            'url' => $url,
        );
    }

    return $unique;
}

function kingy_ali_sanitize_public_source_url($url) {
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
    if (!is_array($parts)) {
        return '';
    }

    $scheme = isset($parts['scheme']) ? strtolower((string) $parts['scheme']) : '';
    $host = isset($parts['host']) ? trim((string) $parts['host']) : '';
    return in_array($scheme, array('http', 'https'), true) && $host !== '' ? $url : '';
}

function kingy_ali_sanitize_public_profile_link_url($url) {
    return kingy_ali_normalize_legacy_internal_url(kingy_ali_sanitize_public_source_url($url));
}

function kingy_ali_normalize_legacy_internal_url($url) {
    if (!is_scalar($url) || trim((string) $url) === '') {
        return '';
    }

    $url = (string) $url;
    $parts = wp_parse_url($url);
    if (!is_array($parts) || empty($parts['host'])) {
        return $url;
    }

    $host = strtolower((string) $parts['host']);
    if (!in_array($host, array('kingy.ai', 'www.kingy.ai'), true)) {
        return $url;
    }

    $path = isset($parts['path']) ? trim((string) $parts['path'], '/') : '';
    if (!preg_match('#^ai-launches/([^/]+)$#', $path, $matches)) {
        return $url;
    }

    if (function_exists('get_page_by_path') && get_page_by_path($path, OBJECT, 'page')) {
        return $url;
    }

    $slug = sanitize_title($matches[1]);
    if (
        function_exists('taxonomy_exists')
        && taxonomy_exists('kingy_launch_category')
        && function_exists('term_exists')
        && term_exists($slug, 'kingy_launch_category')
    ) {
        $normalized = home_url('/ai-launch-category/' . $slug . '/');
        if (!empty($parts['query'])) {
            $normalized .= '?' . $parts['query'];
        }
        if (!empty($parts['fragment'])) {
            $normalized .= '#' . $parts['fragment'];
        }
        return $normalized;
    }

    return $url;
}

function kingy_ali_public_profile_text($value, $default = '') {
    if (!is_scalar($value)) {
        return is_scalar($default) ? (string) $default : '';
    }

    $value = trim((string) $value);
    if ($value === '' && is_scalar($default)) {
        return (string) $default;
    }

    return $value;
}

function kingy_ali_public_profile_meta_text($post_id, $key, $default = '') {
    return kingy_ali_public_profile_text(kingy_ali_get_meta($post_id, $key, $default), $default);
}

function kingy_ali_public_profile_number($value) {
    return is_scalar($value) ? (float) $value : 0.0;
}

function kingy_ali_public_profile_id($value) {
    return is_scalar($value) ? absint($value) : 0;
}

function kingy_ali_public_profile_date_label($value) {
    $value = kingy_ali_public_profile_text($value);
    if ($value === '') {
        return '';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date_i18n(get_option('date_format'), $timestamp) : '';
}

function kingy_ali_source_link_target_attrs($url) {
    if (!is_scalar($url)) {
        return '';
    }

    $url = (string) $url;
    $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
    $url_host = wp_parse_url($url, PHP_URL_HOST);
    $is_relative = strpos($url, '/') === 0 && strpos($url, '//') !== 0;
    $is_internal = $is_relative || ($site_host && $url_host && strtolower($site_host) === strtolower($url_host));

    return $is_internal ? '' : ' rel="nofollow noopener" target="_blank"';
}

function kingy_ali_source_count($post_id) {
    $count = count(kingy_ali_public_source_links($post_id));
    foreach (array('reddit_signal', 'youtube_signal') as $key) {
        if (kingy_ali_get_meta($post_id, $key)) {
            $count++;
        }
    }

    return $count;
}

function kingy_ali_verification_stale_days() {
    $days = absint(apply_filters('kingy_ali_verification_stale_days', 30));
    return $days > 0 ? $days : 30;
}

function kingy_ali_verification_cutoff_date($days = null) {
    $days = $days === null ? kingy_ali_verification_stale_days() : absint($days);
    $days = $days > 0 ? $days : kingy_ali_verification_stale_days();

    return date_i18n('Y-m-d', current_time('timestamp') - ($days * DAY_IN_SECONDS));
}

function kingy_ali_last_verified_timestamp($post_id) {
    $last_verified = kingy_ali_public_profile_meta_text($post_id, 'last_verified');
    if (!$last_verified) {
        return 0;
    }

    $timestamp = strtotime($last_verified);
    return $timestamp ? $timestamp : 0;
}

function kingy_ali_last_verified_is_stale($post_id, $days = null) {
    $last_verified = kingy_ali_public_profile_meta_text($post_id, 'last_verified');
    if (!$last_verified || !kingy_ali_last_verified_timestamp($post_id)) {
        return false;
    }

    return $last_verified < kingy_ali_verification_cutoff_date($days);
}

function kingy_ali_last_verified_needs_update($post_id, $days = null) {
    return !kingy_ali_last_verified_timestamp($post_id) || kingy_ali_last_verified_is_stale($post_id, $days);
}

function kingy_ali_launch_needs_verification_update($post_id, $days = null) {
    $status = kingy_ali_public_profile_meta_text($post_id, 'verification_status');

    return in_array($status, array('', 'outdated', 'partially_verified'), true)
        || kingy_ali_last_verified_needs_update($post_id, $days);
}

function kingy_ali_launches_needing_verification_update_ids($limit = 0, $days = null) {
    $limit = absint($limit);
    $posts_per_page = $limit ? max($limit * 3, $limit) : -1;
    $cutoff_date = kingy_ali_verification_cutoff_date($days);

    $query = new WP_Query(
        array(
            'post_type' => 'kingy_ai_launch',
            'post_status' => array('publish', 'pending', 'draft'),
            'posts_per_page' => $posts_per_page,
            'fields' => 'ids',
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => kingy_ali_meta_key('verification_status'),
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => kingy_ali_meta_key('verification_status'),
                    'value' => array('', 'outdated', 'partially_verified'),
                    'compare' => 'IN',
                ),
                array(
                    'key' => kingy_ali_meta_key('last_verified'),
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => kingy_ali_meta_key('last_verified'),
                    'value' => '',
                    'compare' => '=',
                ),
                array(
                    'key' => kingy_ali_meta_key('last_verified'),
                    'value' => $cutoff_date,
                    'compare' => '<',
                    'type' => 'DATE',
                ),
            ),
        )
    );

    $ids = array();
    foreach ($query->posts as $post_id) {
        $post_id = absint($post_id);
        if (!kingy_ali_launch_needs_verification_update($post_id, $days)) {
            continue;
        }

        $ids[] = $post_id;
        if ($limit && count($ids) >= $limit) {
            break;
        }
    }

    return $ids;
}

function kingy_ali_verification_freshness_label($post_id) {
    $last_verified = kingy_ali_public_profile_meta_text($post_id, 'last_verified');
    $timestamp = kingy_ali_last_verified_timestamp($post_id);

    if (!$last_verified) {
        return __('Not verified yet', 'kingy-ai-launch-intelligence');
    }

    if (!$timestamp) {
        return __('Needs recheck: verification date is invalid', 'kingy-ai-launch-intelligence');
    }

    $date = date_i18n(get_option('date_format'), $timestamp);
    if (kingy_ali_last_verified_is_stale($post_id)) {
        return sprintf(
            __('Needs recheck: verified %s', 'kingy-ai-launch-intelligence'),
            $date
        );
    }

    return sprintf(
        __('Verified %s', 'kingy-ai-launch-intelligence'),
        $date
    );
}

function kingy_ali_verification_label($post_id) {
    $status = kingy_ali_public_profile_meta_text($post_id, 'verification_status');
    $labels = array(
        '' => __('Needs verification', 'kingy-ai-launch-intelligence'),
        'verified' => __('Verified', 'kingy-ai-launch-intelligence'),
        'founder_submitted' => __('Founder-submitted', 'kingy-ai-launch-intelligence'),
        'partially_verified' => __('Partially verified', 'kingy-ai-launch-intelligence'),
        'outdated' => __('Outdated', 'kingy-ai-launch-intelligence'),
    );

    return isset($labels[$status]) ? $labels[$status] : $labels[''];
}

function kingy_ali_profile_verification_label($post_id, $record_type = 'launch') {
    if ($record_type === 'launch' || $record_type === 'model') {
        return kingy_ali_verification_label($post_id);
    }

    $status = kingy_ali_public_profile_meta_text($post_id, 'verification_status');
    if ($status !== '') {
        return kingy_ali_verification_label($post_id);
    }

    return kingy_ali_public_profile_meta_text($post_id, 'last_verified')
        ? __('Verified profile', 'kingy-ai-launch-intelligence')
        : __('Needs verification', 'kingy-ai-launch-intelligence');
}

function kingy_ali_render_trust_panel($post_id, $record_type = 'launch') {
    $last_verified = kingy_ali_public_profile_meta_text($post_id, 'last_verified');
    $source_count = kingy_ali_source_count($post_id);
    $source_links = kingy_ali_public_source_links($post_id);
    $modified = get_the_modified_date(get_option('date_format'), $post_id);
    $verification = kingy_ali_profile_verification_label($post_id, $record_type);
    $freshness = kingy_ali_verification_freshness_label($post_id);
    $freshness_class = kingy_ali_last_verified_needs_update($post_id) ? 'kingy-ali-freshness kingy-ali-freshness--needs-update' : 'kingy-ali-freshness kingy-ali-freshness--current';

    ?>
    <section class="kingy-ali-trust-panel">
        <div>
            <h2><?php esc_html_e('Verification & Sources', 'kingy-ai-launch-intelligence'); ?></h2>
            <dl class="kingy-ali-score-list">
                <div><dt><?php esc_html_e('Status', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($verification); ?></dd></div>
                <div><dt><?php esc_html_e('Source links', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html((string) $source_count); ?></dd></div>
                <div><dt><?php esc_html_e('Freshness', 'kingy-ai-launch-intelligence'); ?></dt><dd class="<?php echo esc_attr($freshness_class); ?>"><?php echo esc_html($freshness); ?></dd></div>
                <div><dt><?php esc_html_e('Last verified', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($last_verified ? date_i18n(get_option('date_format'), strtotime($last_verified)) : __('Unknown', 'kingy-ai-launch-intelligence')); ?></dd></div>
                <div><dt><?php esc_html_e('Last updated', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($modified); ?></dd></div>
            </dl>
            <?php if ($source_links) : ?>
                <div class="kingy-ali-source-checks">
                    <h3><?php esc_html_e('Key source checks', 'kingy-ai-launch-intelligence'); ?></h3>
                    <div class="kingy-ali-link-list">
                        <?php foreach ($source_links as $source) : ?>
                            <a data-kingy-ali-track="clicked_source_link" data-event-label="<?php echo esc_attr($source['label']); ?>" data-event-surface="<?php echo esc_attr($record_type . '_trust_panel'); ?>" href="<?php echo esc_url($source['url']); ?>"<?php echo kingy_ali_source_link_target_attrs($source['url']); ?>><?php echo esc_html($source['label']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php kingy_ali_render_correction_form($post_id); ?>
    </section>
    <?php
}

function kingy_ali_render_correction_form($post_id) {
    if (absint(kingy_ali_correction_get_value('kingy_correction_submitted')) === absint($post_id)) {
        echo '<div class="kingy-ali-success"><p>' . esc_html__('Thanks — your correction was sent for editorial review.', 'kingy-ai-launch-intelligence') . '</p></div>';
        return;
    }

    ?>
    <details id="<?php echo esc_attr('kingy-ali-correction-' . $post_id); ?>" class="kingy-ali-correction">
        <summary><?php esc_html_e('Suggest a correction', 'kingy-ai-launch-intelligence'); ?></summary>
        <form method="post">
            <?php wp_nonce_field('kingy_ali_suggest_correction_' . $post_id, 'kingy_ali_correction_nonce'); ?>
            <input type="hidden" name="kingy_ali_action" value="suggest_correction">
            <input type="hidden" name="kingy_ali_post_id" value="<?php echo esc_attr($post_id); ?>">
            <label class="kingy-ali-hp" aria-hidden="true">
                <span><?php esc_html_e('Leave this field empty', 'kingy-ai-launch-intelligence'); ?></span>
                <input type="text" name="kingy_ali_website">
            </label>
            <label>
                <span><?php esc_html_e('Correction or source note', 'kingy-ai-launch-intelligence'); ?></span>
                <textarea name="kingy_ali_correction_note" rows="4" required></textarea>
            </label>
            <label>
                <span><?php esc_html_e('Email, optional', 'kingy-ai-launch-intelligence'); ?></span>
                <input type="email" name="kingy_ali_correction_email">
            </label>
            <button type="submit"><?php esc_html_e('Send correction', 'kingy-ai-launch-intelligence'); ?></button>
            <p class="kingy-ali-policy-note"><?php echo esc_html(kingy_ali_launch_data_privacy_note()); ?></p>
        </form>
    </details>
    <?php
}

function kingy_ali_correction_get_value($key) {
    $values = kingy_ali_correction_get_values();
    if (!isset($values[$key])) {
        return '';
    }

    if (!is_scalar($values[$key])) {
        return '';
    }

    $value = wp_unslash($values[$key]);
    return is_scalar($value) ? (string) $value : '';
}

function kingy_ali_correction_post_value($key) {
    $values = kingy_ali_correction_post_values();
    if (!isset($values[$key])) {
        return '';
    }

    if (!is_scalar($values[$key])) {
        return '';
    }

    $value = wp_unslash($values[$key]);
    return is_scalar($value) ? (string) $value : '';
}

function kingy_ali_correction_get_values() {
    return is_array($_GET) ? $_GET : array();
}

function kingy_ali_correction_post_values() {
    return is_array($_POST) ? $_POST : array();
}

function kingy_ali_sanitize_correction_note($value) {
    if (!is_scalar($value)) {
        return '';
    }

    $note = sanitize_textarea_field((string) $value);
    if (strlen($note) > 2000) {
        $note = wp_html_excerpt($note, 2000, '');
    }

    return trim($note);
}

function kingy_ali_handle_correction_submission() {
    if (kingy_ali_correction_post_value('kingy_ali_action') !== 'suggest_correction') {
        return;
    }

    $post_id = absint(kingy_ali_correction_post_value('kingy_ali_post_id'));
    if (!$post_id || !wp_verify_nonce(sanitize_text_field(kingy_ali_correction_post_value('kingy_ali_correction_nonce')), 'kingy_ali_suggest_correction_' . $post_id)) {
        wp_die(esc_html__('Correction nonce check failed.', 'kingy-ai-launch-intelligence'));
    }

    if (kingy_ali_correction_post_value('kingy_ali_website') !== '') {
        wp_safe_redirect(add_query_arg('kingy_correction_submitted', $post_id, get_permalink($post_id)));
        exit;
    }

    $note = kingy_ali_sanitize_correction_note(kingy_ali_correction_post_value('kingy_ali_correction_note'));
    $raw_email = trim(kingy_ali_correction_post_value('kingy_ali_correction_email'));
    $email = $raw_email === '' ? '' : sanitize_email($raw_email);
    if ($note === '') {
        wp_die(esc_html__('Please include a correction note.', 'kingy-ai-launch-intelligence'));
    }
    if ($raw_email !== '' && !is_email($email)) {
        wp_die(esc_html__('Please enter a valid email address or leave the email field blank.', 'kingy-ai-launch-intelligence'));
    }

    if (kingy_ali_correction_rate_limited($email)) {
        wp_die(esc_html__('Too many correction suggestions were sent recently. Please wait before sending another correction.', 'kingy-ai-launch-intelligence'));
    }

    kingy_ali_increment_correction_rate_limit($email);

    kingy_ali_track_event(
        'correction_suggested',
        array(
            'event_label' => get_the_title($post_id),
            'object_id' => $post_id,
            'filters' => array(
                'note' => $note,
                'email' => $email,
                'record_type' => get_post_type($post_id),
                'record_url' => get_permalink($post_id),
            ),
        )
    );

    $admin_email = get_option('admin_email');
    if ($admin_email) {
        wp_mail(
            $admin_email,
            sprintf(__('Correction suggested: %s', 'kingy-ai-launch-intelligence'), get_the_title($post_id)),
            sprintf(
                "Record: %s\nURL: %s\nEmail: %s\n\nCorrection:\n%s",
                get_the_title($post_id),
                get_permalink($post_id),
                $email ? $email : __('Not provided', 'kingy-ai-launch-intelligence'),
                $note
            )
        );
    }

    wp_safe_redirect(add_query_arg('kingy_correction_submitted', $post_id, get_permalink($post_id)));
    exit;
}

function kingy_ali_correction_rate_limit_key($email) {
    $ip = function_exists('kingy_ali_request_remote_addr') ? kingy_ali_request_remote_addr() : '';
    $email = is_scalar($email) ? sanitize_email((string) $email) : '';
    $seed = $email ? $email : $ip;
    if ($seed === '') {
        $seed = 'anonymous_correction';
    }

    return 'kingy_ali_correction_' . hash('sha256', wp_salt('nonce') . $seed);
}

function kingy_ali_correction_rate_limited($email) {
    $count = (int) get_transient(kingy_ali_correction_rate_limit_key($email));
    return $count >= 5;
}

function kingy_ali_increment_correction_rate_limit($email) {
    $key = kingy_ali_correction_rate_limit_key($email);
    $count = (int) get_transient($key);
    set_transient($key, $count + 1, HOUR_IN_SECONDS);
}
