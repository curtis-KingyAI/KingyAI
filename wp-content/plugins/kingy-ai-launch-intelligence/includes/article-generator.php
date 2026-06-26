<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_post_kingy_ali_generate_article', 'kingy_ali_handle_generate_article');

function kingy_ali_render_article_generator_page() {
    if (!current_user_can('edit_posts')) {
        return;
    }

    $default_date = current_time('Y-m-d');
    $recent_launches = kingy_ali_get_recent_launch_choices(30);
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Launch Radar Draft Generator', 'kingy-ai-launch-intelligence'); ?></h1>
        <p><?php esc_html_e('Generate an editorial draft from structured launch records. The database remains the source of truth; the article becomes the narrative layer.', 'kingy-ai-launch-intelligence'); ?></p>
        <p><?php esc_html_e('Each launch section uses the standard Launch Radar format: Category, launch date, company, official link, what launched, who it is for, pricing, funding, demo, press kit, verification status, source checks, traction signals, Kingy Launch Score, YouTube Potential, Kingy AI take, promising, unproven, and best next link. The database-record block links each item to launch, tool, alternatives, correction, and related-submission paths.', 'kingy-ai-launch-intelligence'); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('kingy_ali_generate_article', 'kingy_ali_generate_article_nonce'); ?>
            <input type="hidden" name="action" value="kingy_ali_generate_article">
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="kingy_ali_article_period"><?php esc_html_e('Period', 'kingy-ai-launch-intelligence'); ?></label></th>
                        <td>
                            <select id="kingy_ali_article_period" name="period">
                                <option value="day"><?php esc_html_e('Daily Launch Radar', 'kingy-ai-launch-intelligence'); ?></option>
                                <option value="week"><?php esc_html_e('Weekly Launch Tracker', 'kingy-ai-launch-intelligence'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="kingy_ali_article_date"><?php esc_html_e('Date', 'kingy-ai-launch-intelligence'); ?></label></th>
                        <td><input id="kingy_ali_article_date" type="date" name="article_date" value="<?php echo esc_attr($default_date); ?>" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="kingy_ali_article_limit"><?php esc_html_e('Maximum launches', 'kingy-ai-launch-intelligence'); ?></label></th>
                        <td><input id="kingy_ali_article_limit" type="number" name="limit" min="1" max="30" value="15"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="kingy_ali_article_status"><?php esc_html_e('Launch status', 'kingy-ai-launch-intelligence'); ?></label></th>
                        <td>
                            <select id="kingy_ali_article_status" name="launch_post_status">
                                <option value="publish"><?php esc_html_e('Public-ready published launches only', 'kingy-ai-launch-intelligence'); ?></option>
                                <option value="all"><?php esc_html_e('Published, pending, and draft launches', 'kingy-ai-launch-intelligence'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('The default published-only mode skips launch profiles held back by the package noindex/readiness safeguards. Use all statuses only for internal editorial drafts that will be reviewed before publishing.', 'kingy-ai-launch-intelligence'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Optional launch records', 'kingy-ai-launch-intelligence'); ?></th>
                        <td>
                            <p class="description"><?php esc_html_e('Choose exact records when you want the draft to use a hand-picked Launch Radar lineup. Leave blank to use the period/date query above.', 'kingy-ai-launch-intelligence'); ?></p>
                            <?php if ($recent_launches) : ?>
                                <fieldset>
                                    <legend class="screen-reader-text"><?php esc_html_e('Select launch records for this article', 'kingy-ai-launch-intelligence'); ?></legend>
                                    <table class="widefat striped">
                                        <thead>
                                            <tr>
                                                <th scope="col"><?php esc_html_e('Use', 'kingy-ai-launch-intelligence'); ?></th>
                                                <th scope="col"><?php esc_html_e('Launch', 'kingy-ai-launch-intelligence'); ?></th>
                                                <th scope="col"><?php esc_html_e('Date', 'kingy-ai-launch-intelligence'); ?></th>
                                                <th scope="col"><?php esc_html_e('Status', 'kingy-ai-launch-intelligence'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_launches as $launch) : ?>
                                                <tr>
                                                    <td><input type="checkbox" name="launch_ids[]" value="<?php echo esc_attr($launch->ID); ?>"></td>
                                                    <td><?php echo esc_html(get_the_title($launch->ID)); ?></td>
                                                    <td><?php echo esc_html(kingy_ali_article_launch_date_label($launch->ID)); ?></td>
                                                    <td><?php echo esc_html(kingy_ali_article_post_status_label($launch->ID)); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </fieldset>
                            <?php else : ?>
                                <p><?php esc_html_e('No launch records are available yet.', 'kingy-ai-launch-intelligence'); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p><button class="button button-primary" type="submit"><?php esc_html_e('Create Article Draft', 'kingy-ai-launch-intelligence'); ?></button></p>
        </form>
    </div>
    <?php
}

function kingy_ali_handle_generate_article() {
    if (!current_user_can('edit_posts')) {
        wp_die(esc_html__('You do not have permission to generate article drafts.', 'kingy-ai-launch-intelligence'));
    }

    if (!wp_verify_nonce(sanitize_text_field(kingy_ali_article_post_value('kingy_ali_generate_article_nonce')), 'kingy_ali_generate_article')) {
        wp_die(esc_html__('Article generator nonce check failed.', 'kingy-ai-launch-intelligence'));
    }

    $period = kingy_ali_sanitize_article_period(kingy_ali_article_post_value('period'));
    $article_date = kingy_ali_sanitize_article_date(kingy_ali_article_post_value('article_date'));
    $limit = kingy_ali_sanitize_article_limit(kingy_ali_article_post_value('limit'));
    $launch_status = kingy_ali_sanitize_article_launch_status(kingy_ali_article_post_value('launch_post_status'));
    $post_statuses = $launch_status === 'all' ? array('publish', 'pending', 'draft') : array('publish');
    $selected_launch_ids = kingy_ali_sanitize_article_launch_ids(kingy_ali_article_post_array('launch_ids'), 30);

    $launches = $selected_launch_ids
        ? kingy_ali_get_selected_launches_for_article($selected_launch_ids, $post_statuses, $limit)
        : kingy_ali_get_launches_for_article($period, $article_date, $limit, $post_statuses);
    if (empty($launches)) {
        wp_die(esc_html__('No launch records matched that article period.', 'kingy-ai-launch-intelligence'));
    }

    $title = kingy_ali_article_title($period, $article_date);
    $content = kingy_ali_build_article_content($launches, $period, $article_date);

    $post_id = wp_insert_post(
        array(
            'post_type' => 'post',
            'post_status' => 'draft',
            'post_title' => $title,
            'post_content' => $content,
            'post_excerpt' => sprintf(
                _n(
                    'Kingy AI Launch Radar draft generated from %d structured launch record.',
                    'Kingy AI Launch Radar draft generated from %d structured launch records.',
                    count($launches),
                    'kingy-ai-launch-intelligence'
                ),
                count($launches)
            ),
            'meta_input' => array(
                kingy_ali_meta_key('generated_article') => '1',
                kingy_ali_meta_key('generated_article_period') => $period,
                kingy_ali_meta_key('generated_article_date') => $article_date,
                kingy_ali_meta_key('source_launch_ids') => implode(',', wp_list_pluck($launches, 'ID')),
            ),
        ),
        true
    );

    if (is_wp_error($post_id)) {
        wp_die(esc_html($post_id->get_error_message()));
    }

    kingy_ali_link_generated_article_to_launches($post_id, $launches);

    kingy_ali_track_event(
        'article_draft_generated',
        array(
            'event_label' => $title,
            'object_id' => $post_id,
            'result_count' => count($launches),
            'filters' => array(
                'period' => $period,
                'date' => $article_date,
                'selected_launch_ids' => $selected_launch_ids ? implode(',', $selected_launch_ids) : '',
            ),
        )
    );

    wp_safe_redirect(admin_url('post.php?post=' . $post_id . '&action=edit'));
    exit;
}

function kingy_ali_article_post_value($key) {
    $values = kingy_ali_article_post_values();
    if (!isset($values[$key])) {
        return '';
    }

    if (!is_scalar($values[$key])) {
        return '';
    }

    $value = wp_unslash($values[$key]);
    return is_scalar($value) ? (string) $value : '';
}

function kingy_ali_article_post_array($key) {
    $values = kingy_ali_article_post_values();
    if (!isset($values[$key])) {
        return array();
    }

    if (!is_array($values[$key])) {
        return array();
    }

    $value = wp_unslash($values[$key]);
    return is_array($value) ? $value : array();
}

function kingy_ali_article_post_values() {
    return is_array($_POST) ? $_POST : array();
}

function kingy_ali_article_text_value($value, $default = '') {
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

function kingy_ali_article_meta_text($post_id, $key, $default = '') {
    return kingy_ali_article_text_value(kingy_ali_get_meta($post_id, $key, $default), $default);
}

function kingy_ali_article_related_id($value) {
    if (function_exists('kingy_ali_public_profile_id')) {
        return kingy_ali_public_profile_id($value);
    }

    return is_scalar($value) ? absint($value) : 0;
}

function kingy_ali_sanitize_article_period($value) {
    $period = sanitize_key(is_scalar($value) ? (string) $value : '');
    return in_array($period, array('day', 'week'), true) ? $period : 'day';
}

function kingy_ali_sanitize_article_date($value) {
    $date = sanitize_text_field(is_scalar($value) ? (string) $value : '');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return current_time('Y-m-d');
    }

    list($year, $month, $day) = array_map('absint', explode('-', $date));
    return checkdate($month, $day, $year) ? $date : current_time('Y-m-d');
}

function kingy_ali_sanitize_article_limit($value) {
    $value = is_scalar($value) ? trim((string) $value) : '';
    if ($value === '') {
        return 15;
    }

    $limit = (int) $value;
    return max(1, min(30, $limit));
}

function kingy_ali_sanitize_article_launch_status($value) {
    $status = sanitize_key(is_scalar($value) ? (string) $value : '');
    return $status === 'all' ? 'all' : 'publish';
}

function kingy_ali_sanitize_article_launch_ids($value, $limit = 30) {
    $ids = array();
    foreach ((array) $value as $id) {
        if (!is_scalar($id)) {
            continue;
        }

        $id = (int) $id;
        if ($id > 0) {
            $ids[] = $id;
        }
    }

    $ids = array_values(array_unique($ids));
    $limit = max(1, min(30, absint($limit)));
    return array_slice($ids, 0, $limit);
}

function kingy_ali_get_launches_for_article($period, $article_date, $limit, $post_statuses) {
    $public_ready_only = kingy_ali_article_public_ready_only($post_statuses);
    $date_query = array(
        'key' => kingy_ali_meta_key('launch_date'),
        'value' => $article_date,
        'compare' => '=',
    );

    if ($period === 'week') {
        $start = date_i18n('Y-m-d', strtotime('-6 days', strtotime($article_date)));
        $date_query = array(
            'key' => kingy_ali_meta_key('launch_date'),
            'value' => array($start, $article_date),
            'compare' => 'BETWEEN',
        );
    }

    $query = new WP_Query(
        array(
            'post_type' => 'kingy_ai_launch',
            'post_status' => $post_statuses,
            'posts_per_page' => $public_ready_only ? -1 : $limit,
            'meta_key' => kingy_ali_meta_key('launch_date'),
            'orderby' => array(
                'meta_value' => 'DESC',
                'date' => 'DESC',
            ),
            'no_found_rows' => true,
            'meta_query' => array($date_query),
        )
    );

    $launches = $query->posts;
    return $public_ready_only ? kingy_ali_filter_article_public_ready_launches($launches, $limit) : $launches;
}

function kingy_ali_get_selected_launches_for_article($launch_ids, $post_statuses, $limit) {
    $launch_ids = kingy_ali_sanitize_article_launch_ids($launch_ids, 30);
    if (!$launch_ids) {
        return array();
    }

    $public_ready_only = kingy_ali_article_public_ready_only($post_statuses);
    $query = new WP_Query(
        array(
            'post_type' => 'kingy_ai_launch',
            'post_status' => $post_statuses,
            'post__in' => $launch_ids,
            'posts_per_page' => $public_ready_only ? count($launch_ids) : max(1, min(absint($limit), count($launch_ids))),
            'orderby' => 'post__in',
            'no_found_rows' => true,
        )
    );

    $launches = $query->posts;
    return $public_ready_only ? kingy_ali_filter_article_public_ready_launches($launches, $limit) : $launches;
}

function kingy_ali_article_public_ready_only($post_statuses) {
    $post_statuses = array_values(array_unique(array_map('sanitize_key', (array) $post_statuses)));
    sort($post_statuses);
    return $post_statuses === array('publish');
}

function kingy_ali_filter_article_public_ready_launches($launches, $limit = 0) {
    $filtered = array();
    foreach ((array) $launches as $launch) {
        $launch_id = is_object($launch) && isset($launch->ID) ? absint($launch->ID) : absint($launch);
        if (!$launch_id) {
            continue;
        }

        if (function_exists('kingy_ali_profile_should_noindex') && kingy_ali_profile_should_noindex($launch_id)) {
            continue;
        }

        $filtered[] = $launch;
    }

    $limit = absint($limit);
    return $limit > 0 ? array_slice($filtered, 0, $limit) : $filtered;
}

function kingy_ali_get_recent_launch_choices($limit = 30) {
    $query = new WP_Query(
        array(
            'post_type' => 'kingy_ai_launch',
            'post_status' => array('publish', 'pending', 'draft'),
            'posts_per_page' => max(1, absint($limit)),
            'meta_key' => kingy_ali_meta_key('launch_date'),
            'orderby' => array(
                'meta_value' => 'DESC',
                'date' => 'DESC',
            ),
        )
    );

    return $query->posts;
}

function kingy_ali_article_launch_date_label($post_id) {
    $launch_date = kingy_ali_article_meta_text($post_id, 'launch_date');
    if (!$launch_date) {
        return __('No launch date', 'kingy-ai-launch-intelligence');
    }

    $timestamp = strtotime($launch_date);
    return $timestamp ? date_i18n(get_option('date_format'), $timestamp) : sanitize_text_field($launch_date);
}

function kingy_ali_article_post_status_label($post_id) {
    $status = get_post_status($post_id);
    $status_object = $status ? get_post_status_object($status) : null;

    if ($status_object && !empty($status_object->label)) {
        return $status_object->label;
    }

    return $status ? ucfirst(str_replace(array('_', '-'), ' ', sanitize_key($status))) : __('Unknown', 'kingy-ai-launch-intelligence');
}

function kingy_ali_article_title($period, $article_date) {
    $formatted = date_i18n('F j, Y', strtotime($article_date));
    if ($period === 'week') {
        return sprintf(__('AI Launch Tracker: Week Ending %s', 'kingy-ai-launch-intelligence'), $formatted);
    }

    return sprintf(__('AI Launch Radar: %s', 'kingy-ai-launch-intelligence'), $formatted);
}

function kingy_ali_build_article_content($launches, $period, $article_date) {
    $content = array();
    $intro = $period === 'week'
        ? __('This weekly AI Launch Tracker draft is generated from structured Kingy AI Launch Intelligence records. Review, tighten, and add editorial context before publishing.', 'kingy-ai-launch-intelligence')
        : __('Today\'s AI Launch Radar is generated from structured Kingy AI Launch Intelligence records. Review, tighten, and add editorial context before publishing.', 'kingy-ai-launch-intelligence');
    $records_heading = $period === 'week'
        ? __('This week\'s launch database records', 'kingy-ai-launch-intelligence')
        : __('Today\'s launch database records', 'kingy-ai-launch-intelligence');

    $content[] = '<!-- wp:paragraph --><p>' . esc_html($intro) . '</p><!-- /wp:paragraph -->';
    $content[] = '<!-- wp:heading --><h2>' . esc_html($records_heading) . '</h2><!-- /wp:heading -->';
    $content[] = kingy_ali_article_records_list($launches);

    foreach ($launches as $launch) {
        $content[] = kingy_ali_article_launch_section($launch);
    }

    $content[] = '<!-- wp:heading --><h2>' . esc_html__('Founder and creator coverage next steps', 'kingy-ai-launch-intelligence') . '</h2><!-- /wp:heading -->';
    $content[] = '<!-- wp:paragraph --><p>' . esc_html__('Launching an AI product? Submit it to Kingy AI Launch Intelligence to be considered for the database, Launch Radar, and potential creator coverage.', 'kingy-ai-launch-intelligence') . '</p><!-- /wp:paragraph -->';
    $content[] = '<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url(home_url('/ai-launches/submit/')) . '">' . esc_html__('Submit your launch', 'kingy-ai-launch-intelligence') . '</a></div><!-- /wp:button --><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="' . esc_url(home_url('/ai-launches/launch-visibility-score/')) . '">' . esc_html__('Get a Launch Visibility Score', 'kingy-ai-launch-intelligence') . '</a></div><!-- /wp:button --></div><!-- /wp:buttons -->';

    return implode("\n\n", $content);
}

function kingy_ali_article_records_list($launches) {
    $items = '';
    foreach ($launches as $launch) {
        $items .= '<li><strong>' . esc_html(get_the_title($launch->ID)) . '</strong>';
        $items .= '<ul>' . kingy_ali_article_record_action_items($launch->ID) . '</ul></li>';
    }

    return '<!-- wp:list --><ul>' . $items . '</ul><!-- /wp:list -->';
}

function kingy_ali_article_record_action_items($post_id) {
    $actions = kingy_ali_article_record_actions($post_id);
    $items = '';
    foreach ($actions as $action) {
        $url = isset($action['url']) ? kingy_ali_article_sanitize_link_url($action['url']) : '';
        if (!$url) {
            continue;
        }

        $items .= '<li><a href="' . esc_url($url) . '">' . esc_html($action['label']) . '</a></li>';
    }

    return $items;
}

function kingy_ali_article_record_actions($post_id) {
    $post_id = absint($post_id);
    $actions = array(
        array(
            'label' => __('View launch profile', 'kingy-ai-launch-intelligence'),
            'url' => get_permalink($post_id),
        ),
        array(
            'label' => __('View tool profile', 'kingy-ai-launch-intelligence'),
            'url' => kingy_ali_article_tool_profile_url($post_id),
        ),
        array(
            'label' => __('View alternatives', 'kingy-ai-launch-intelligence'),
            'url' => kingy_ali_article_alternatives_url($post_id),
        ),
        array(
            'label' => __('Suggest correction', 'kingy-ai-launch-intelligence'),
            'url' => get_permalink($post_id) . '#kingy-ali-correction-' . $post_id,
        ),
        array(
            'label' => __('Submit related launch', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-launches/submit/'),
        ),
    );

    return $actions;
}

function kingy_ali_article_tool_profile_url($post_id) {
    $tool_id = kingy_ali_article_related_id(kingy_ali_get_meta($post_id, 'related_tool_id'));
    if (kingy_ali_related_post_is_public_index_ready($tool_id, 'kingy_ai_tool')) {
        return get_permalink($tool_id);
    }

    $tool = get_page_by_path(sanitize_title(get_the_title($post_id)), OBJECT, 'kingy_ai_tool');
    if ($tool && kingy_ali_related_post_is_public_index_ready($tool->ID, 'kingy_ai_tool')) {
        return get_permalink($tool->ID);
    }

    return add_query_arg('kali_q', get_the_title($post_id), home_url('/ai-tools/'));
}

function kingy_ali_article_alternatives_url($post_id) {
    $alternatives_url = kingy_ali_article_sanitize_link_url(kingy_ali_get_meta($post_id, 'related_alternatives_url'));
    if ($alternatives_url) {
        return $alternatives_url;
    }

    $tool_id = kingy_ali_article_related_id(kingy_ali_get_meta($post_id, 'related_tool_id'));
    if ($tool_id) {
        $tool_alternatives_url = kingy_ali_article_sanitize_link_url(kingy_ali_get_meta($tool_id, 'alternatives_url'));
        if ($tool_alternatives_url) {
            return $tool_alternatives_url;
        }
    }

    return add_query_arg('kali_q', get_the_title($post_id), home_url('/ai-tools/'));
}

function kingy_ali_article_launch_section($launch) {
    $post_id = $launch->ID;
    $facts = array(
        __('Category', 'kingy-ai-launch-intelligence') => kingy_ali_article_terms($post_id, 'kingy_launch_category'),
        __('Launch date', 'kingy-ai-launch-intelligence') => kingy_ali_article_meta_text($post_id, 'launch_date'),
        __('Company', 'kingy-ai-launch-intelligence') => kingy_ali_article_meta_text($post_id, 'company'),
        __('Official link', 'kingy-ai-launch-intelligence') => kingy_ali_article_meta_text($post_id, 'official_url'),
        __('What launched', 'kingy-ai-launch-intelligence') => kingy_ali_article_meta_text($post_id, 'what_launched'),
        __('Who it is for', 'kingy-ai-launch-intelligence') => kingy_ali_article_meta_text($post_id, 'who_it_is_for'),
        __('Pricing', 'kingy-ai-launch-intelligence') => kingy_ali_article_meta_text($post_id, 'pricing'),
        __('Funding', 'kingy-ai-launch-intelligence') => kingy_ali_article_meta_text($post_id, 'funding'),
        __('Demo', 'kingy-ai-launch-intelligence') => kingy_ali_article_meta_text($post_id, 'demo_url'),
        __('Press kit', 'kingy-ai-launch-intelligence') => kingy_ali_article_meta_text($post_id, 'press_kit_url'),
        __('Verification status', 'kingy-ai-launch-intelligence') => kingy_ali_article_verification_status($post_id),
        __('Last verified', 'kingy-ai-launch-intelligence') => kingy_ali_article_last_verified($post_id),
        __('Source checks', 'kingy-ai-launch-intelligence') => kingy_ali_article_source_checks($post_id),
        __('Traction signals', 'kingy-ai-launch-intelligence') => kingy_ali_article_traction_signals($post_id),
        __('Kingy Launch Score', 'kingy-ai-launch-intelligence') => kingy_ali_format_score(kingy_ali_article_meta_text($post_id, 'kingy_launch_score')),
        __('YouTube Potential', 'kingy-ai-launch-intelligence') => kingy_ali_score_band(kingy_ali_article_meta_text($post_id, 'youtube_score')),
    );

    $html = array();
    $html[] = '<!-- wp:heading --><h2>' . esc_html(get_the_title($post_id)) . '</h2><!-- /wp:heading -->';
    $html[] = '<!-- wp:list --><ul>' . kingy_ali_article_fact_items($facts) . '</ul><!-- /wp:list -->';

    $take = kingy_ali_article_meta_text($post_id, 'kingy_verdict');
    $html[] = '<!-- wp:heading {"level":3} --><h3>' . esc_html__('Kingy AI take', 'kingy-ai-launch-intelligence') . '</h3><!-- /wp:heading -->';
    $html[] = '<!-- wp:paragraph --><p>' . esc_html(kingy_ali_article_editorial_value($take)) . '</p><!-- /wp:paragraph -->';

    $promising = kingy_ali_article_meta_text($post_id, 'what_feels_promising');
    $html[] = '<!-- wp:heading {"level":3} --><h3>' . esc_html__('What feels promising', 'kingy-ai-launch-intelligence') . '</h3><!-- /wp:heading -->';
    $html[] = '<!-- wp:paragraph --><p>' . esc_html(kingy_ali_article_editorial_value($promising)) . '</p><!-- /wp:paragraph -->';

    $unproven = kingy_ali_article_meta_text($post_id, 'what_feels_unproven');
    $html[] = '<!-- wp:heading {"level":3} --><h3>' . esc_html__('What feels unproven', 'kingy-ai-launch-intelligence') . '</h3><!-- /wp:heading -->';
    $html[] = '<!-- wp:paragraph --><p>' . esc_html(kingy_ali_article_editorial_value(wp_trim_words($unproven, 70))) . '</p><!-- /wp:paragraph -->';

    $html[] = '<!-- wp:heading {"level":3} --><h3>' . esc_html__('Best next link', 'kingy-ai-launch-intelligence') . '</h3><!-- /wp:heading -->';
    $html[] = '<!-- wp:paragraph --><p>' . kingy_ali_article_value_html(kingy_ali_article_best_next_link($post_id)) . '</p><!-- /wp:paragraph -->';

    return implode("\n", $html);
}

function kingy_ali_article_fact_items($facts) {
    $items = '';
    foreach ($facts as $label => $value) {
        $items .= '<li><strong>' . esc_html($label) . ':</strong> ' . kingy_ali_article_value_html($value) . '</li>';
    }

    return $items;
}

function kingy_ali_article_value_html($value) {
    if (is_array($value)) {
        if (isset($value[0]) && is_array($value[0])) {
            $items = array();
            foreach ($value as $item) {
                $items[] = kingy_ali_article_value_html($item);
            }

            $items = array_filter($items);
            return $items ? implode(', ', $items) : '<em>' . esc_html__('Needs editorial review', 'kingy-ai-launch-intelligence') . '</em>';
        }

        $url = isset($value['url']) ? $value['url'] : '';
        $label = isset($value['label']) && is_scalar($value['label']) && $value['label'] !== '' ? (string) $value['label'] : $url;
        $url = kingy_ali_article_sanitize_link_url($url);

        if ($url) {
            return '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        }

        $value = $label;
    }

    if (!is_scalar($value)) {
        return '<em>' . esc_html__('Needs editorial review', 'kingy-ai-launch-intelligence') . '</em>';
    }

    $value = trim((string) $value);

    if ($value === '') {
        return '<em>' . esc_html__('Needs editorial review', 'kingy-ai-launch-intelligence') . '</em>';
    }

    $url = kingy_ali_article_sanitize_link_url($value);
    if ($url) {
        return '<a href="' . esc_url($url) . '">' . esc_html($url) . '</a>';
    }

    return esc_html($value);
}

function kingy_ali_article_sanitize_link_url($url) {
    $url = trim(is_scalar($url) ? (string) $url : '');
    if ($url === '') {
        return '';
    }

    if (function_exists('kingy_ali_sanitize_public_profile_link_url')) {
        return kingy_ali_sanitize_public_profile_link_url($url);
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

function kingy_ali_article_editorial_value($value) {
    $value = kingy_ali_article_text_value($value);
    return $value !== '' ? $value : __('Needs editorial review before publishing.', 'kingy-ai-launch-intelligence');
}

function kingy_ali_article_terms($post_id, $taxonomy) {
    $terms = get_the_terms($post_id, $taxonomy);
    if (is_wp_error($terms) || empty($terms)) {
        return '';
    }

    return implode(', ', wp_list_pluck($terms, 'name'));
}

function kingy_ali_article_verification_status($post_id) {
    if (function_exists('kingy_ali_verification_label')) {
        return kingy_ali_verification_label($post_id);
    }

    return kingy_ali_article_meta_text($post_id, 'verification_status');
}

function kingy_ali_article_last_verified($post_id) {
    $last_verified = kingy_ali_article_meta_text($post_id, 'last_verified');
    if (!$last_verified) {
        return '';
    }

    $timestamp = strtotime($last_verified);
    return $timestamp ? date_i18n(get_option('date_format'), $timestamp) : $last_verified;
}

function kingy_ali_article_source_checks($post_id) {
    if (function_exists('kingy_ali_public_source_links')) {
        return kingy_ali_public_source_links($post_id);
    }

    $sources = kingy_ali_article_meta_text($post_id, 'sources');
    if (!$sources) {
        return array();
    }

    $links = array();
    foreach (array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $sources))) as $line) {
        $url = kingy_ali_article_sanitize_link_url($line);
        if ($url) {
            $links[] = array('label' => $url, 'url' => $url);
        }
    }

    return $links;
}

function kingy_ali_article_traction_signals($post_id) {
    $signals = array();
    foreach (array('product_hunt_url' => 'Product Hunt', 'github_url' => 'GitHub', 'huggingface_url' => 'Hugging Face', 'x_url' => 'X/social', 'youtube_url' => 'YouTube') as $key => $label) {
        $url = kingy_ali_article_meta_text($post_id, $key);
        if ($url) {
            $signals[] = $label . ': ' . $url;
        }
    }

    foreach (array('reddit_signal' => 'Reddit/community', 'youtube_signal' => 'YouTube/creator', 'traction_notes' => 'Notes') as $key => $label) {
        $signal = kingy_ali_article_meta_text($post_id, $key);
        if ($signal) {
            $signals[] = $label . ': ' . $signal;
        }
    }

    return implode(' | ', $signals);
}

function kingy_ali_article_best_next_link($post_id) {
    $best_next_link = kingy_ali_article_link_candidate(
        kingy_ali_article_meta_text($post_id, 'best_next_link_url'),
        kingy_ali_article_meta_text($post_id, 'best_next_link_label')
    );
    if ($best_next_link) {
        return $best_next_link;
    }

    foreach (array('related_review_url', 'related_alternatives_url', 'related_calculator_url', 'related_article_url', 'related_course_url') as $key) {
        $link = kingy_ali_article_link_candidate(kingy_ali_article_meta_text($post_id, $key), kingy_ali_article_link_label($key));
        if ($link) {
            return $link;
        }
    }

    $permalink = kingy_ali_article_sanitize_link_url(get_permalink($post_id));
    return $permalink ? $permalink : '';
}

function kingy_ali_article_link_candidate($url, $label = '') {
    $url = kingy_ali_article_sanitize_link_url($url);
    if (!$url) {
        return array();
    }

    $label = is_scalar($label) ? trim((string) $label) : '';
    if ($label === '') {
        $label = $url;
    }

    return array(
        'url' => $url,
        'label' => $label,
    );
}

function kingy_ali_article_link_label($key) {
    $labels = array(
        'related_review_url' => __('Related review', 'kingy-ai-launch-intelligence'),
        'related_alternatives_url' => __('Alternatives page', 'kingy-ai-launch-intelligence'),
        'related_calculator_url' => __('Related calculator', 'kingy-ai-launch-intelligence'),
        'related_article_url' => __('Related article', 'kingy-ai-launch-intelligence'),
        'related_course_url' => __('Related course', 'kingy-ai-launch-intelligence'),
    );

    return isset($labels[$key]) ? $labels[$key] : __('Best next link', 'kingy-ai-launch-intelligence');
}

function kingy_ali_link_generated_article_to_launches($article_post_id, $launches) {
    $article_post_id = absint($article_post_id);
    if (!$article_post_id) {
        return;
    }

    $article_url = get_permalink($article_post_id);
    if (!$article_url) {
        $article_url = add_query_arg('p', $article_post_id, home_url('/'));
    }

    $article_url = kingy_ali_article_sanitize_link_url($article_url);
    if (!$article_url) {
        return;
    }

    foreach ($launches as $launch) {
        $launch_id = isset($launch->ID) ? absint($launch->ID) : 0;
        $existing_article_url = $launch_id ? kingy_ali_article_sanitize_link_url(kingy_ali_article_meta_text($launch_id, 'related_article_url')) : '';
        if (!$launch_id || $existing_article_url !== '') {
            continue;
        }

        update_post_meta($launch_id, kingy_ali_meta_key('related_article_url'), $article_url);
    }
}
