<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'kingy_ali_register_companion_video_meta', 20);
add_action('add_meta_boxes', 'kingy_ali_add_companion_video_meta_box');
add_action('save_post_kingy_video', 'kingy_ali_save_companion_video', 20, 3);
add_action('admin_notices', 'kingy_ali_companion_admin_notices');
add_action('wp_enqueue_scripts', 'kingy_ali_enqueue_companion_video_assets', 30);
add_action('wp_enqueue_scripts', 'kingy_ali_enqueue_tool_companion_styles', 30);
add_action('wp_head', 'kingy_ali_output_companion_video_schema', 30);
add_action('save_post_kingy_video', 'kingy_ali_queue_companion_cache_purge', 60, 3);
add_action('template_redirect', 'kingy_ali_enforce_companion_private_cache_headers', PHP_INT_MAX);
add_filter('wp_insert_post_data', 'kingy_ali_guard_companion_publication', 20, 2);
add_filter('wp_headers', 'kingy_ali_companion_cache_headers', 1000, 2);
add_filter('wp_robots', 'kingy_ali_companion_wp_robots');
add_filter('wpseo_robots', 'kingy_ali_companion_wpseo_robots');
add_filter('wpseo_title', 'kingy_ali_companion_seo_title');
add_filter('wpseo_metadesc', 'kingy_ali_companion_seo_description');
add_filter('wpseo_canonical', 'kingy_ali_companion_canonical');
add_filter('wpseo_opengraph_title', 'kingy_ali_companion_seo_title');
add_filter('wpseo_opengraph_desc', 'kingy_ali_companion_seo_description');
add_filter('wpseo_opengraph_url', 'kingy_ali_companion_canonical');
add_filter('wpseo_twitter_title', 'kingy_ali_companion_seo_title');
add_filter('wpseo_twitter_description', 'kingy_ali_companion_seo_description');
add_filter('get_canonical_url', 'kingy_ali_companion_canonical');
add_filter('document_title_parts', 'kingy_ali_companion_document_title');
add_filter('wp_sitemaps_post_types', 'kingy_ali_companion_core_sitemap_post_types');
add_filter('wpseo_sitemap_exclude_post_type', 'kingy_ali_companion_yoast_exclude_post_type', 20, 2);
add_filter('wpseo_sitemap_entry', 'kingy_ali_companion_yoast_sitemap_entry', 50, 3);
add_filter('wpseo_sitemap_post_type_archive_link', 'kingy_ali_companion_yoast_archive_link', 50, 2);
add_filter('kingy_ali_launch_purge_dependency_registry', 'kingy_ali_companion_purge_dependency_registry', 20, 2);
add_filter('cdn_purge_cache_urls', 'kingy_ali_companion_cdn_purge_urls', 30, 2);

function kingy_ali_companion_meta_key($key) {
    $keys = array(
        'youtube_video_id' => '_kingy_youtube_video_id',
        'video_publish_date' => '_kingy_video_publish_date',
        'featured_tool_id' => '_kingy_featured_tool_id',
        'featured_tool_event' => '_kingy_featured_tool_event',
        'snapshot_json' => '_kingy_snapshot_json',
        'snapshot_json_revision' => '_kingy_snapshot_json_revision',
        'snapshot_verified_date' => '_kingy_snapshot_verified_date',
        'sponsored' => '_kingy_sponsored',
        'image_qa_approved' => '_kingy_image_qa_approved',
        'editorial_qa_approved' => '_kingy_editorial_qa_approved',
        'index_approved' => '_kingy_companion_index_approved',
    );

    return isset($keys[$key]) ? $keys[$key] : '_kingy_' . sanitize_key($key);
}

function kingy_ali_companion_meta_auth($allowed, $meta_key, $post_id) {
    unset($allowed, $meta_key);
    return current_user_can('edit_post', $post_id);
}

function kingy_ali_register_companion_video_meta() {
    $scalar_meta = array(
        'youtube_video_id' => 'string',
        'video_publish_date' => 'string',
        'snapshot_json' => 'string',
        'snapshot_verified_date' => 'string',
        'sponsored' => 'boolean',
        'image_qa_approved' => 'boolean',
        'editorial_qa_approved' => 'boolean',
        'index_approved' => 'boolean',
    );

    foreach ($scalar_meta as $key => $type) {
        register_post_meta(
            'kingy_video',
            kingy_ali_companion_meta_key($key),
            array(
                'type' => $type,
                'single' => true,
                'show_in_rest' => $key !== 'snapshot_json',
                'auth_callback' => 'kingy_ali_companion_meta_auth',
            )
        );
    }

    register_post_meta(
        'kingy_video',
        kingy_ali_companion_meta_key('featured_tool_id'),
        array(
            'type' => 'integer',
            'single' => false,
            // Relationship changes must flow through the append-only admin
            // workflow below. Direct REST mutation could erase that history.
            'show_in_rest' => false,
            'auth_callback' => 'kingy_ali_companion_meta_auth',
        )
    );
}

function kingy_ali_companion_youtube_id($value) {
    if (!is_scalar($value)) {
        return '';
    }

    $value = trim((string) $value);
    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $value)) {
        return $value;
    }

    $parts = wp_parse_url($value);
    if (!is_array($parts) || empty($parts['host'])) {
        return '';
    }

    $host = strtolower((string) $parts['host']);
    $candidate = '';
    if ($host === 'youtu.be' || $host === 'www.youtu.be') {
        $candidate = trim(isset($parts['path']) ? $parts['path'] : '', '/');
    } elseif (in_array($host, array('youtube.com', 'www.youtube.com', 'm.youtube.com'), true)) {
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
            $candidate = isset($query['v']) && is_scalar($query['v']) ? (string) $query['v'] : '';
        }
        if ($candidate === '' && !empty($parts['path']) && preg_match('#/(?:shorts|embed)/([A-Za-z0-9_-]{11})#', $parts['path'], $matches)) {
            $candidate = $matches[1];
        }
    }

    return preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate) ? $candidate : '';
}

function kingy_ali_companion_valid_date($value) {
    return kingy_ali_valid_kali_as_of($value);
}

function kingy_ali_companion_featured_tool_ids($post_id) {
    $base_ids = array_values(array_unique(array_filter(array_map('absint', (array) get_post_meta($post_id, kingy_ali_companion_meta_key('featured_tool_id'), false)))));
    $state = array();
    foreach ($base_ids as $tool_id) {
        $state[$tool_id] = true;
    }

    foreach ((array) get_post_meta($post_id, kingy_ali_companion_meta_key('featured_tool_event'), false) as $event_json) {
        if (!is_scalar($event_json)) {
            continue;
        }
        $event = json_decode((string) $event_json, true);
        $tool_id = is_array($event) && isset($event['tool_id']) ? absint($event['tool_id']) : 0;
        if (!$tool_id) {
            continue;
        }
        if (!in_array($tool_id, $base_ids, true)) {
            $base_ids[] = $tool_id;
        }
        $state[$tool_id] = !empty($event['active']);
    }

    return array_values(array_filter($base_ids, function ($tool_id) use ($state) {
        return !empty($state[$tool_id])
            && get_post_type($tool_id) === 'kingy_ai_tool'
            && get_post_status($tool_id) === 'publish';
    }));
}

function kingy_ali_companion_resolve_tool_references($value) {
    $references = is_scalar($value) ? preg_split('/[\s,]+/', trim((string) $value)) : array();
    $ids = array();
    $invalid = array();

    foreach (array_filter($references) as $reference) {
        $tool_id = kingy_ali_resolve_kali_tool($reference);
        if (!$tool_id) {
            $invalid[] = sanitize_text_field($reference);
            continue;
        }
        $ids[] = $tool_id;
    }

    return array(
        'ids' => array_values(array_unique($ids)),
        'invalid' => array_values(array_unique($invalid)),
    );
}

function kingy_ali_companion_update_tool_relationships($post_id, $requested_ids) {
    $requested_ids = array_values(array_unique(array_filter(array_map('absint', (array) $requested_ids))));
    $current_ids = kingy_ali_companion_featured_tool_ids($post_id);
    $known_ids = array_values(array_unique(array_filter(array_map('absint', (array) get_post_meta($post_id, kingy_ali_companion_meta_key('featured_tool_id'), false)))));

    foreach ($requested_ids as $tool_id) {
        if (!in_array($tool_id, $known_ids, true)) {
            add_post_meta($post_id, kingy_ali_companion_meta_key('featured_tool_id'), $tool_id, false);
            $known_ids[] = $tool_id;
        }
    }

    $changed_ids = array_values(array_unique(array_merge(array_diff($requested_ids, $current_ids), array_diff($current_ids, $requested_ids))));
    foreach ($changed_ids as $tool_id) {
        add_post_meta(
            $post_id,
            kingy_ali_companion_meta_key('featured_tool_event'),
            wp_json_encode(
                array(
                    'tool_id' => $tool_id,
                    'active' => in_array($tool_id, $requested_ids, true),
                    'changed_at' => current_time('mysql', true),
                    'user_id' => get_current_user_id(),
                )
            ),
            false
        );
    }
}

function kingy_ali_add_companion_video_meta_box() {
    add_meta_box(
        'kingy-ali-companion-video',
        __('Living Companion Page', 'kingy-ai-launch-intelligence'),
        'kingy_ali_render_companion_video_meta_box',
        'kingy_video',
        'normal',
        'high'
    );
}

function kingy_ali_render_companion_video_meta_box($post) {
    $youtube_id = get_post_meta($post->ID, kingy_ali_companion_meta_key('youtube_video_id'), true);
    $publish_date = get_post_meta($post->ID, kingy_ali_companion_meta_key('video_publish_date'), true);
    $tool_ids = kingy_ali_companion_featured_tool_ids($post->ID);
    $tool_value = implode(', ', $tool_ids);
    $snapshot = kingy_ali_companion_snapshot($post->ID);
    $snapshot_verified = get_post_meta($post->ID, kingy_ali_companion_meta_key('snapshot_verified_date'), true);
    $sponsored = (bool) get_post_meta($post->ID, kingy_ali_companion_meta_key('sponsored'), true);
    $image_qa = (bool) get_post_meta($post->ID, kingy_ali_companion_meta_key('image_qa_approved'), true);
    $editorial_qa = (bool) get_post_meta($post->ID, kingy_ali_companion_meta_key('editorial_qa_approved'), true);
    $index_approved = (bool) get_post_meta($post->ID, kingy_ali_companion_meta_key('index_approved'), true);

    wp_nonce_field('kingy_ali_save_companion_' . $post->ID, 'kingy_ali_companion_nonce');
    ?>
    <p><strong><?php esc_html_e('Data ownership', 'kingy-ai-launch-intelligence'); ?></strong><br><?php esc_html_e('This page composes KALI records. Do not copy current prices or features into the editor as historical facts.', 'kingy-ai-launch-intelligence'); ?></p>
    <p>
        <label for="kingy-youtube-video-id"><strong><?php esc_html_e('YouTube video ID or URL', 'kingy-ai-launch-intelligence'); ?></strong></label><br>
        <input class="widefat" id="kingy-youtube-video-id" name="kingy_youtube_video_id" type="text" value="<?php echo esc_attr($youtube_id); ?>" autocomplete="off">
    </p>
    <p>
        <label for="kingy-video-publish-date"><strong><?php esc_html_e('Video publish date', 'kingy-ai-launch-intelligence'); ?></strong></label><br>
        <input id="kingy-video-publish-date" name="kingy_video_publish_date" type="date" value="<?php echo esc_attr($publish_date); ?>">
    </p>
    <p>
        <label for="kingy-featured-tools"><strong><?php esc_html_e('Featured tools', 'kingy-ai-launch-intelligence'); ?></strong></label><br>
        <input class="widefat" id="kingy-featured-tools" name="kingy_featured_tools" type="text" value="<?php echo esc_attr($tool_value); ?>" aria-describedby="kingy-featured-tools-help">
        <span class="description" id="kingy-featured-tools-help"><?php esc_html_e('Comma-separated published tool IDs or slugs. Relationship changes are preserved as an append-only event history.', 'kingy-ai-launch-intelligence'); ?></span>
    </p>
    <p><label><input name="kingy_sponsored" type="checkbox" value="1" <?php checked($sponsored); ?>> <?php esc_html_e('Sponsored video — show disclosure', 'kingy-ai-launch-intelligence'); ?></label></p>
    <hr>
    <p><strong><?php esc_html_e('Snapshot', 'kingy-ai-launch-intelligence'); ?></strong><br>
        <?php if ($snapshot) : ?>
            <?php echo esc_html(sprintf(__('Verified snapshot captured; verification date: %s.', 'kingy-ai-launch-intelligence'), $snapshot_verified ?: __('unknown', 'kingy-ai-launch-intelligence'))); ?>
        <?php else : ?>
            <?php esc_html_e('No verified snapshot is stored. Production currently has no populated historical pricing or feature snapshots, so capture is expected to fail closed.', 'kingy-ai-launch-intelligence'); ?>
        <?php endif; ?>
    </p>
    <p>
        <button class="button" name="kingy_capture_snapshot" type="submit" value="1"><?php esc_html_e('Capture verified snapshot', 'kingy-ai-launch-intelligence'); ?></button>
        <?php if ($snapshot) : ?>
            <label><input name="kingy_confirm_recapture" type="checkbox" value="1"> <?php esc_html_e('Confirm re-capture; preserve the prior snapshot as a revision', 'kingy-ai-launch-intelligence'); ?></label>
        <?php endif; ?>
    </p>
    <hr>
    <p><strong><?php esc_html_e('Publication gates', 'kingy-ai-launch-intelligence'); ?></strong></p>
    <p><label><input name="kingy_image_qa_approved" type="checkbox" value="1" <?php checked($image_qa); ?>> <?php esc_html_e('Rendered featured-image/contact-sheet review passed; hero is story-specific and not duplicated in the body', 'kingy-ai-launch-intelligence'); ?></label></p>
    <p><label><input name="kingy_editorial_qa_approved" type="checkbox" value="1" <?php checked($editorial_qa); ?>> <?php esc_html_e('Final editorial and rendered-preview review passed', 'kingy-ai-launch-intelligence'); ?></label></p>
    <p><label><input name="kingy_index_approved" type="checkbox" value="1" <?php checked($index_approved); ?>> <?php esc_html_e('Curtis approved indexing after all Phase F criteria passed', 'kingy-ai-launch-intelligence'); ?></label></p>
    <p class="description"><?php esc_html_e('Indexing remains technically disabled for this build even when checked. A later explicit release must enable it.', 'kingy-ai-launch-intelligence'); ?></p>
    <?php
}

function kingy_ali_companion_request_scalar($key) {
    if (!isset($_POST[$key]) || !is_scalar($_POST[$key])) {
        return '';
    }
    return trim((string) wp_unslash($_POST[$key]));
}

function kingy_ali_companion_set_notice($message, $type = 'error') {
    if (!function_exists('set_transient') || !function_exists('get_current_user_id')) {
        return;
    }
    set_transient(
        'kingy_ali_companion_notice_' . get_current_user_id(),
        array('message' => sanitize_text_field($message), 'type' => sanitize_key($type)),
        MINUTE_IN_SECONDS
    );
}

function kingy_ali_companion_admin_notices() {
    if (!function_exists('get_current_screen') || !function_exists('get_transient')) {
        return;
    }
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'kingy_video') {
        return;
    }
    $key = 'kingy_ali_companion_notice_' . get_current_user_id();
    $notice = get_transient($key);
    if (!is_array($notice) || empty($notice['message'])) {
        return;
    }
    delete_transient($key);
    $type = isset($notice['type']) && in_array($notice['type'], array('success', 'warning', 'error', 'info'), true) ? $notice['type'] : 'error';
    echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible"><p>' . esc_html($notice['message']) . '</p></div>';
}

function kingy_ali_save_companion_video($post_id, $post, $update) {
    unset($update);
    if (
        !$post
        || $post->post_type !== 'kingy_video'
        || wp_is_post_revision($post_id)
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        || !current_user_can('edit_post', $post_id)
    ) {
        return;
    }

    $nonce = kingy_ali_companion_request_scalar('kingy_ali_companion_nonce');
    if (!$nonce || !wp_verify_nonce($nonce, 'kingy_ali_save_companion_' . $post_id)) {
        return;
    }

    // Validate the complete request before the first metadata write so an
    // invalid field cannot leave a partial companion record behind.
    $youtube_raw = kingy_ali_companion_request_scalar('kingy_youtube_video_id');
    $youtube_id = $youtube_raw !== '' ? kingy_ali_companion_youtube_id($youtube_raw) : '';
    if ($youtube_raw !== '' && !$youtube_id) {
        kingy_ali_companion_set_notice(__('The YouTube video ID or URL is invalid. Existing metadata was preserved.', 'kingy-ai-launch-intelligence'));
        return;
    }

    $publish_date = kingy_ali_companion_request_scalar('kingy_video_publish_date');
    if ($publish_date !== '' && !kingy_ali_companion_valid_date($publish_date)) {
        kingy_ali_companion_set_notice(__('The video publish date is invalid. Existing metadata was preserved.', 'kingy-ai-launch-intelligence'));
        return;
    }

    $resolved = null;
    if (isset($_POST['kingy_featured_tools']) && is_scalar($_POST['kingy_featured_tools'])) {
        $resolved = kingy_ali_companion_resolve_tool_references(wp_unslash($_POST['kingy_featured_tools']));
        if ($resolved['invalid']) {
            kingy_ali_companion_set_notice(
                sprintf(__('Unknown or unpublished tool reference(s): %s. Existing relationships were preserved.', 'kingy-ai-launch-intelligence'), implode(', ', $resolved['invalid']))
            );
            return;
        }
    }

    if (function_exists('kingy_ali_queue_launch_collection_purge')) {
        kingy_ali_queue_launch_collection_purge(
            $post_id,
            false,
            'companion_before_update',
            kingy_ali_companion_cache_registry($post_id, kingy_ali_companion_featured_tool_ids($post_id))
        );
    }

    if ($youtube_id !== '') {
        update_post_meta($post_id, kingy_ali_companion_meta_key('youtube_video_id'), $youtube_id);
    }
    if ($publish_date !== '') {
        update_post_meta($post_id, kingy_ali_companion_meta_key('video_publish_date'), $publish_date);
    }
    if (is_array($resolved)) {
        kingy_ali_companion_update_tool_relationships($post_id, $resolved['ids']);
    }

    update_post_meta($post_id, kingy_ali_companion_meta_key('sponsored'), isset($_POST['kingy_sponsored']) ? 1 : 0);
    update_post_meta($post_id, kingy_ali_companion_meta_key('image_qa_approved'), isset($_POST['kingy_image_qa_approved']) ? 1 : 0);
    update_post_meta($post_id, kingy_ali_companion_meta_key('editorial_qa_approved'), isset($_POST['kingy_editorial_qa_approved']) ? 1 : 0);
    update_post_meta($post_id, kingy_ali_companion_meta_key('index_approved'), isset($_POST['kingy_index_approved']) ? 1 : 0);

    if (kingy_ali_companion_request_scalar('kingy_capture_snapshot') === '1') {
        $result = kingy_ali_companion_capture_snapshot($post_id, kingy_ali_companion_request_scalar('kingy_confirm_recapture') === '1');
        if (is_wp_error($result)) {
            kingy_ali_companion_set_notice($result->get_error_message());
        } else {
            kingy_ali_companion_set_notice(__('Verified companion snapshot captured.', 'kingy-ai-launch-intelligence'), 'success');
        }
    }
}

function kingy_ali_companion_sanitize_snapshot_value($value) {
    if (is_array($value)) {
        $sanitized = array();
        foreach ($value as $key => $item) {
            $sanitized[sanitize_key((string) $key)] = kingy_ali_companion_sanitize_snapshot_value($item);
        }
        return $sanitized;
    }
    if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
        return $value;
    }
    return is_scalar($value) ? sanitize_text_field((string) $value) : '';
}

function kingy_ali_companion_capture_snapshot($post_id, $confirm_recapture = false) {
    $existing = get_post_meta($post_id, kingy_ali_companion_meta_key('snapshot_json'), true);
    if ($existing && !$confirm_recapture) {
        return new WP_Error('snapshot_exists', __('A snapshot already exists. Re-capture requires explicit confirmation.', 'kingy-ai-launch-intelligence'));
    }

    $as_of = get_post_meta($post_id, kingy_ali_companion_meta_key('video_publish_date'), true);
    $tool_ids = kingy_ali_companion_featured_tool_ids($post_id);
    if (!kingy_ali_companion_valid_date($as_of)) {
        return new WP_Error('missing_publish_date', __('Set a valid video publish date before capturing a snapshot.', 'kingy-ai-launch-intelligence'));
    }
    if (!$tool_ids) {
        return new WP_Error('missing_tools', __('Choose at least one published featured tool before capturing a snapshot.', 'kingy-ai-launch-intelligence'));
    }

    $tools = array();
    $verified_dates = array();
    foreach ($tool_ids as $tool_id) {
        $snapshot = apply_filters('kingy_ali_verified_tool_snapshot', null, $tool_id, $as_of, $post_id);
        if (!is_array($snapshot) || ($snapshot['status'] ?? '') !== 'verified') {
            return new WP_Error(
                'historical_snapshot_unavailable',
                sprintf(__('No verified historical snapshot is available for %1$s as of %2$s. Nothing was stored.', 'kingy-ai-launch-intelligence'), get_the_title($tool_id), $as_of)
            );
        }

        $verified_date = isset($snapshot['verified_date']) && kingy_ali_companion_valid_date($snapshot['verified_date']) ? $snapshot['verified_date'] : '';
        if (
            !$verified_date
            || !array_key_exists('pricing', $snapshot)
            || !array_key_exists('features', $snapshot)
            || empty($snapshot['sources'])
        ) {
            return new WP_Error(
                'historical_snapshot_incomplete',
                sprintf(__('The historical snapshot for %s is incomplete or unverified. Nothing was stored.', 'kingy-ai-launch-intelligence'), get_the_title($tool_id))
            );
        }

        $data = kingy_ali_companion_sanitize_snapshot_value(
            array(
                'pricing' => $snapshot['pricing'],
                'features' => $snapshot['features'],
                'sources' => $snapshot['sources'],
            )
        );
        if (!kingy_ali_companion_snapshot_sources(array('data' => $data))) {
            return new WP_Error(
                'historical_snapshot_sources_invalid',
                sprintf(__('The historical snapshot for %s has no valid public source URL. Nothing was stored.', 'kingy-ai-launch-intelligence'), get_the_title($tool_id))
            );
        }
        $tools[] = array(
            'tool_id' => $tool_id,
            'tool_slug' => get_post_field('post_name', $tool_id),
            'as_of' => $as_of,
            'verified_date' => $verified_date,
            'data' => $data,
            'sha256' => hash('sha256', wp_json_encode($data)),
        );
        $verified_dates[] = $verified_date;
    }

    sort($verified_dates);
    $payload = array(
        'schema_version' => 1,
        'status' => 'verified',
        'as_of' => $as_of,
        'captured_at' => current_time('mysql', true),
        'tools' => $tools,
    );
    $encoded = wp_json_encode($payload);
    if (!$encoded) {
        return new WP_Error('snapshot_encoding_failed', __('The verified snapshot could not be encoded. Nothing was stored.', 'kingy-ai-launch-intelligence'));
    }

    if ($existing) {
        add_post_meta($post_id, kingy_ali_companion_meta_key('snapshot_json_revision'), $existing, false);
    }
    update_post_meta($post_id, kingy_ali_companion_meta_key('snapshot_json'), $encoded);
    update_post_meta($post_id, kingy_ali_companion_meta_key('snapshot_verified_date'), end($verified_dates));
    return $payload;
}

function kingy_ali_companion_snapshot($post_id) {
    $json = get_post_meta($post_id, kingy_ali_companion_meta_key('snapshot_json'), true);
    if (!is_scalar($json) || $json === '') {
        return array();
    }
    $snapshot = json_decode((string) $json, true);
    if (
        !is_array($snapshot)
        || ($snapshot['status'] ?? '') !== 'verified'
        || empty($snapshot['tools'])
        || !is_array($snapshot['tools'])
        || empty($snapshot['as_of'])
        || !kingy_ali_companion_valid_date($snapshot['as_of'])
    ) {
        return array();
    }
    foreach ($snapshot['tools'] as $tool) {
        if (
            !is_array($tool)
            || empty($tool['tool_id'])
            || empty($tool['data'])
            || !is_array($tool['data'])
            || empty($tool['sha256'])
            || !hash_equals((string) $tool['sha256'], hash('sha256', wp_json_encode($tool['data'])))
        ) {
            return array();
        }
    }
    return $snapshot;
}

function kingy_ali_companion_snapshot_tool($snapshot, $tool_id) {
    foreach (isset($snapshot['tools']) && is_array($snapshot['tools']) ? $snapshot['tools'] : array() as $tool) {
        if (is_array($tool) && isset($tool['tool_id']) && absint($tool['tool_id']) === absint($tool_id)) {
            return $tool;
        }
    }
    return array();
}

function kingy_ali_companion_snapshot_value_text($value) {
    if (is_bool($value)) {
        return $value ? __('Yes', 'kingy-ai-launch-intelligence') : __('No', 'kingy-ai-launch-intelligence');
    }
    if (is_scalar($value)) {
        return trim((string) $value);
    }
    if (!is_array($value)) {
        return '';
    }

    $values = array();
    foreach ($value as $key => $item) {
        $text = kingy_ali_companion_snapshot_value_text($item);
        if ($text === '') {
            continue;
        }
        $label = is_string($key) && !is_numeric($key)
            ? ucwords(str_replace(array('-', '_'), ' ', $key)) . ': '
            : '';
        $values[] = $label . rtrim($text, " \t\n\r\0\x0B.;");
    }
    return $values ? implode('; ', $values) . '.' : '';
}

function kingy_ali_companion_snapshot_sources($snapshot_tool) {
    $sources = isset($snapshot_tool['data']['sources']) && is_array($snapshot_tool['data']['sources'])
        ? $snapshot_tool['data']['sources']
        : array();
    $links = array();
    foreach ($sources as $key => $source) {
        if (is_array($source)) {
            $url = isset($source['url']) && is_scalar($source['url']) ? (string) $source['url'] : '';
            $label = isset($source['label']) && is_scalar($source['label']) ? (string) $source['label'] : '';
        } else {
            $url = is_scalar($source) ? (string) $source : '';
            $label = is_string($key) && !is_numeric($key) ? ucwords(str_replace(array('-', '_'), ' ', $key)) : '';
        }
        $url = kingy_ali_sanitize_public_profile_link_url($url);
        if (!$url) {
            continue;
        }
        $links[] = array(
            'url' => $url,
            'label' => $label !== '' ? sanitize_text_field($label) : __('Source', 'kingy-ai-launch-intelligence'),
        );
    }
    return $links;
}

function kingy_ali_render_companion_snapshot_tool($post_id, $tool_id, $heading_level = 3) {
    $heading_level = max(2, min(6, absint($heading_level)));
    $heading_tag = 'h' . $heading_level;
    $snapshot = kingy_ali_companion_snapshot($post_id);
    $snapshot_tool = kingy_ali_companion_snapshot_tool($snapshot, $tool_id);
    if (!$snapshot_tool) {
        return kingy_ali_kali_snapshot_unavailable_html(
            $tool_id,
            get_post_meta($post_id, kingy_ali_companion_meta_key('video_publish_date'), true),
            $heading_level
        );
    }

    $pricing = isset($snapshot_tool['data']['pricing'])
        ? kingy_ali_companion_snapshot_value_text($snapshot_tool['data']['pricing'])
        : '';
    $features = isset($snapshot_tool['data']['features'])
        ? kingy_ali_companion_snapshot_value_text($snapshot_tool['data']['features'])
        : '';
    $as_of = isset($snapshot_tool['as_of']) ? (string) $snapshot_tool['as_of'] : '';
    $verified_date = isset($snapshot_tool['verified_date']) ? (string) $snapshot_tool['verified_date'] : '';
    $as_of_label = $as_of !== '' ? kingy_ali_public_profile_date_label($as_of) : '';
    $verified_date_label = $verified_date !== '' ? kingy_ali_public_profile_date_label($verified_date) : '';
    $sources = kingy_ali_companion_snapshot_sources($snapshot_tool);

    ob_start();
    ?>
    <section class="kingy-ali-tool-module kingy-ali-companion-snapshot" data-kingy-kali-mode="snapshot" data-kingy-tool-id="<?php echo esc_attr($tool_id); ?>">
        <<?php echo tag_escape($heading_tag); ?>><?php echo esc_html(get_the_title($tool_id)); ?></<?php echo tag_escape($heading_tag); ?>>
        <dl class="kingy-ali-score-list">
            <div><dt><?php esc_html_e('Pricing at publication', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($pricing !== '' ? $pricing : __('Not recorded', 'kingy-ai-launch-intelligence')); ?></dd></div>
            <div><dt><?php esc_html_e('Features at publication', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($features !== '' ? $features : __('Not recorded', 'kingy-ai-launch-intelligence')); ?></dd></div>
        </dl>
        <p class="kingy-ali-small-note">
            <?php
            echo esc_html(
                sprintf(
                    __('Historical record date: %1$s. Snapshot verification date: %2$s.', 'kingy-ai-launch-intelligence'),
                    $as_of_label ?: __('unknown', 'kingy-ai-launch-intelligence'),
                    $verified_date_label ?: __('unknown', 'kingy-ai-launch-intelligence')
                )
            );
            ?>
        </p>
        <?php if ($sources) : ?>
            <div class="kingy-ali-link-list" aria-label="<?php esc_attr_e('Historical snapshot sources', 'kingy-ai-launch-intelligence'); ?>">
                <?php foreach ($sources as $source) : ?>
                    <a href="<?php echo esc_url($source['url']); ?>"<?php echo kingy_ali_source_link_target_attrs($source['url']); ?>><?php echo esc_html($source['label']); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_companion_live_tool($tool_id) {
    return '<div class="kingy-ali-companion-live-tool" data-kingy-tool-id="' . esc_attr($tool_id) . '">'
        . '<h3><a href="' . esc_url(get_permalink($tool_id)) . '">' . esc_html(get_the_title($tool_id)) . '</a></h3>'
        . kingy_ali_render_kali_tool_pricing($tool_id, 4, true)
        . kingy_ali_render_kali_tool_features($tool_id, 4, true)
        . kingy_ali_render_kali_tool_verification($tool_id, 4, true)
        . '</div>';
}

function kingy_ali_render_companion_youtube_facade($post_id) {
    $youtube_id = kingy_ali_companion_youtube_id(get_post_meta($post_id, kingy_ali_companion_meta_key('youtube_video_id'), true));
    if (!$youtube_id) {
        return '<div class="kingy-ali-companion-unavailable" role="status"><p>'
            . esc_html__('The video is not available because no valid YouTube ID is stored.', 'kingy-ai-launch-intelligence')
            . '</p></div>';
    }

    $title = get_the_title($post_id);
    $watch_url = 'https://www.youtube.com/watch?v=' . rawurlencode($youtube_id);
    ob_start();
    ?>
    <div class="kingy-ali-youtube-facade" data-kingy-youtube-id="<?php echo esc_attr($youtube_id); ?>">
        <button type="button" aria-label="<?php echo esc_attr(sprintf(__('Play %s on YouTube', 'kingy-ai-launch-intelligence'), $title)); ?>">
            <img src="<?php echo esc_url(kingy_ali_companion_youtube_thumbnail($youtube_id)); ?>" data-fallback-src="<?php echo esc_url(kingy_ali_companion_youtube_thumbnail($youtube_id, 'hqdefault')); ?>" data-kingy-youtube-thumbnail alt="<?php echo esc_attr(sprintf(__('Video thumbnail for %s', 'kingy-ai-launch-intelligence'), $title)); ?>" width="1280" height="720" loading="eager" decoding="async" fetchpriority="high">
            <span class="kingy-ali-youtube-facade__play" aria-hidden="true"></span>
        </button>
        <noscript><p><a href="<?php echo esc_url($watch_url); ?>"><?php esc_html_e('Watch this video on YouTube', 'kingy-ai-launch-intelligence'); ?></a></p></noscript>
    </div>
    <?php
    return ob_get_clean();
}

function kingy_ali_companion_editorial_word_count($post_id) {
    $content = get_post_field('post_content', $post_id);
    return str_word_count(trim(wp_strip_all_tags(is_scalar($content) ? (string) $content : '')));
}

function kingy_ali_companion_body_has_featured_image($post_id) {
    $image_id = absint(get_post_thumbnail_id($post_id));
    $content = (string) get_post_field('post_content', $post_id);
    if (!$image_id || $content === '') {
        return false;
    }

    if (preg_match('/\bwp-image-' . preg_quote((string) $image_id, '/') . '\b/', $content)) {
        return true;
    }

    $image_url = wp_get_attachment_url($image_id);
    if (!$image_url) {
        return false;
    }
    $image_path = wp_parse_url($image_url, PHP_URL_PATH);
    $basename = is_string($image_path) ? wp_basename($image_path) : '';
    return $basename !== '' && strpos($content, $basename) !== false;
}

function kingy_ali_companion_editorial_has_citation($post_id) {
    $content = (string) get_post_field('post_content', $post_id);
    if (!preg_match_all('/<a\b[^>]*\bhref=(?:"|\')([^"\']+)(?:"|\')/i', $content, $matches)) {
        return false;
    }
    $home_host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    foreach ($matches[1] as $url) {
        $scheme = strtolower((string) wp_parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
        if (in_array($scheme, array('http', 'https'), true) && $host !== '' && $host !== $home_host) {
            return true;
        }
    }
    return false;
}

function kingy_ali_companion_editorial_has_bare_url($post_id) {
    $visible_text = wp_strip_all_tags((string) get_post_field('post_content', $post_id));
    return (bool) preg_match('#https?://[^\s<]+#i', $visible_text);
}

function kingy_ali_companion_snapshot_matches_record($post_id) {
    $snapshot = kingy_ali_companion_snapshot($post_id);
    if (!$snapshot) {
        return false;
    }
    $publish_date = get_post_meta($post_id, kingy_ali_companion_meta_key('video_publish_date'), true);
    if ((string) $snapshot['as_of'] !== (string) $publish_date) {
        return false;
    }
    $snapshot_ids = array_values(array_unique(array_map('absint', wp_list_pluck($snapshot['tools'], 'tool_id'))));
    $relationship_ids = array_values(array_unique(array_map('absint', kingy_ali_companion_featured_tool_ids($post_id))));
    sort($snapshot_ids);
    sort($relationship_ids);
    return $snapshot_ids === $relationship_ids;
}

function kingy_ali_companion_operational_gate_passes($post_id, $meta_key, $allowed_statuses) {
    $status = sanitize_key((string) get_post_meta($post_id, $meta_key, true));
    return $status !== '' && in_array($status, $allowed_statuses, true);
}

function kingy_ali_companion_content_gates($post_id) {
    $failures = array();
    if (!kingy_ali_companion_youtube_id(get_post_meta($post_id, kingy_ali_companion_meta_key('youtube_video_id'), true))) {
        $failures[] = 'youtube_video_id';
    }
    if (!kingy_ali_companion_valid_date(get_post_meta($post_id, kingy_ali_companion_meta_key('video_publish_date'), true))) {
        $failures[] = 'video_publish_date';
    }
    if (!kingy_ali_companion_featured_tool_ids($post_id)) {
        $failures[] = 'featured_tools';
    }
    if (!kingy_ali_companion_operational_gate_passes(
        $post_id,
        '_kingy_entity_ownership_status',
        array('resolved')
    )) {
        $failures[] = 'entity_ownership';
    }
    if (!kingy_ali_companion_operational_gate_passes(
        $post_id,
        '_kingy_reciprocity_status',
        array('resolved', 'prepared-auto-on-publish')
    )) {
        $failures[] = 'reciprocity_contract';
    }
    if (!kingy_ali_companion_operational_gate_passes(
        $post_id,
        '_kingy_disclosure_status',
        array('resolved')
    )) {
        $failures[] = 'commercial_disclosure';
    }
    if (!kingy_ali_companion_snapshot_matches_record($post_id)) {
        $failures[] = 'verified_snapshot';
    }
    if (kingy_ali_companion_editorial_word_count($post_id) < 300) {
        $failures[] = 'editorial_context_300_words';
    }
    if (!kingy_ali_companion_editorial_has_citation($post_id)) {
        $failures[] = 'editorial_citation';
    }
    if (kingy_ali_companion_editorial_has_bare_url($post_id)) {
        $failures[] = 'editorial_bare_url';
    }
    if (!has_post_thumbnail($post_id)) {
        $failures[] = 'featured_image';
    } elseif (kingy_ali_companion_body_has_featured_image($post_id)) {
        $failures[] = 'featured_image_duplicated_in_body';
    }
    if (!get_post_meta($post_id, kingy_ali_companion_meta_key('image_qa_approved'), true)) {
        $failures[] = 'rendered_image_qa';
    }
    if (!get_post_meta($post_id, kingy_ali_companion_meta_key('editorial_qa_approved'), true)) {
        $failures[] = 'rendered_editorial_qa';
    }
    return $failures;
}

function kingy_ali_companion_publication_gates($post_id) {
    $failures = kingy_ali_companion_content_gates($post_id);
    if (!get_post_meta($post_id, kingy_ali_companion_meta_key('index_approved'), true)) {
        $failures[] = 'curtis_indexing_approval';
    }
    if (!defined('KINGY_ALI_COMPANION_INDEXING_ENABLED') || !KINGY_ALI_COMPANION_INDEXING_ENABLED) {
        $failures[] = 'indexing_release_disabled';
    }
    return array_values(array_unique($failures));
}

function kingy_ali_guard_companion_publication($data, $postarr) {
    if (($data['post_type'] ?? '') !== 'kingy_video' || !in_array($data['post_status'] ?? '', array('publish', 'future'), true)) {
        return $data;
    }
    $post_id = isset($postarr['ID']) ? absint($postarr['ID']) : 0;
    $failures = $post_id ? kingy_ali_companion_publication_gates($post_id) : array('save_draft_first');
    if ($failures) {
        $data['post_status'] = 'draft';
        kingy_ali_companion_set_notice(
            sprintf(__('Publication was blocked. Required gate(s): %s.', 'kingy-ai-launch-intelligence'), implode(', ', $failures))
        );
    }
    return $data;
}

function kingy_ali_companion_index_ready($post_id) {
    return !kingy_ali_companion_content_gates($post_id)
        && (bool) get_post_meta($post_id, kingy_ali_companion_meta_key('index_approved'), true);
}

function kingy_ali_companion_may_index($post_id) {
    $post_id = absint($post_id);
    return $post_id
        && get_post_type($post_id) === 'kingy_video'
        && get_post_status($post_id) === 'publish'
        && defined('KINGY_ALI_COMPANION_INDEXING_ENABLED')
        && KINGY_ALI_COMPANION_INDEXING_ENABLED
        && kingy_ali_companion_index_ready($post_id);
}

function kingy_ali_is_companion_surface() {
    return is_singular('kingy_video') || is_post_type_archive('kingy_video');
}

function kingy_ali_companion_wp_robots($robots) {
    if (!kingy_ali_is_companion_surface()) {
        return $robots;
    }
    if (is_singular('kingy_video') && kingy_ali_companion_may_index(get_queried_object_id())) {
        return $robots;
    }
    $robots['noindex'] = true;
    $robots['follow'] = true;
    return $robots;
}

function kingy_ali_companion_wpseo_robots($robots) {
    if (!kingy_ali_is_companion_surface()) {
        return $robots;
    }
    if (is_singular('kingy_video') && kingy_ali_companion_may_index(get_queried_object_id())) {
        return $robots;
    }
    return 'noindex, follow';
}

function kingy_ali_companion_canonical($canonical) {
    if (is_singular('kingy_video')) {
        return get_permalink(get_queried_object_id());
    }
    if (is_post_type_archive('kingy_video')) {
        return get_post_type_archive_link('kingy_video');
    }
    return $canonical;
}

function kingy_ali_companion_document_title($parts) {
    if (is_singular('kingy_video')) {
        $parts['title'] = get_the_title(get_queried_object_id());
    } elseif (is_post_type_archive('kingy_video')) {
        $parts['title'] = __('Living Companion Videos', 'kingy-ai-launch-intelligence');
    }
    return $parts;
}

function kingy_ali_companion_seo_title($title) {
    if (is_singular('kingy_video')) {
        $post_id = get_queried_object_id();
        $custom_title = $post_id
            ? trim((string) get_post_meta($post_id, '_kingy_ali_seo_title', true))
            : '';
        if ($custom_title !== '') {
            return $custom_title;
        }
        return sprintf(__('%s — Living Video Companion', 'kingy-ai-launch-intelligence'), get_the_title(get_queried_object_id()));
    }
    if (is_post_type_archive('kingy_video')) {
        return __('Living Companion Videos | Kingy AI', 'kingy-ai-launch-intelligence');
    }
    return $title;
}

function kingy_ali_companion_seo_description($description) {
    if (!is_singular('kingy_video')) {
        return is_post_type_archive('kingy_video')
            ? __('Moment-anchored Kingy AI video companions with honestly labeled historical availability and current tool data.', 'kingy-ai-launch-intelligence')
            : $description;
    }
    $post_id = get_queried_object_id();
    $custom_description = $post_id
        ? trim((string) get_post_meta($post_id, '_kingy_ali_meta_description', true))
        : '';
    if ($custom_description !== '') {
        return $custom_description;
    }
    $excerpt = get_the_excerpt($post_id);
    return $excerpt ?: wp_trim_words(wp_strip_all_tags(get_post_field('post_content', $post_id)), 28, '…');
}

function kingy_ali_companion_core_sitemap_post_types($post_types) {
    if (is_array($post_types)) {
        unset($post_types['kingy_video']);
    }
    return $post_types;
}

function kingy_ali_companion_yoast_exclude_post_type($excluded, $post_type) {
    return $post_type === 'kingy_video' ? true : $excluded;
}

function kingy_ali_companion_yoast_sitemap_entry($url, $type, $object) {
    if (
        $type === 'kingy_video'
        || ($type === 'post' && is_object($object) && isset($object->ID) && get_post_type($object->ID) === 'kingy_video')
    ) {
        return false;
    }
    return $url;
}

function kingy_ali_companion_yoast_archive_link($archive_url, $post_type) {
    return $post_type === 'kingy_video' ? false : $archive_url;
}

function kingy_ali_enqueue_companion_video_assets() {
    if (!kingy_ali_is_companion_surface()) {
        return;
    }
    // Companion pages reuse the public stylesheet but do not need the launch
    // collection filter/live scripts, keeping the video page dependency-light.
    wp_enqueue_style('kingy-ali-launch-intelligence');
    $style_file = KINGY_ALI_PLUGIN_DIR . 'assets/css/companion-videos.css';
    wp_enqueue_style(
        'kingy-ali-companion-videos',
        KINGY_ALI_PLUGIN_URL . 'assets/css/companion-videos.css',
        array('kingy-ali-launch-intelligence'),
        is_readable($style_file) ? (string) filemtime($style_file) : KINGY_ALI_VERSION
    );
    $script_file = KINGY_ALI_PLUGIN_DIR . 'assets/js/companion-videos.js';
    wp_enqueue_script(
        'kingy-ali-companion-videos',
        KINGY_ALI_PLUGIN_URL . 'assets/js/companion-videos.js',
        array(),
        is_readable($script_file) ? (string) filemtime($script_file) : KINGY_ALI_VERSION,
        true
    );
    wp_script_add_data('kingy-ali-companion-videos', 'strategy', 'defer');
}

function kingy_ali_enqueue_tool_companion_styles() {
    if (!is_singular('kingy_ai_tool')) {
        return;
    }
    $tool_id = get_queried_object_id();
    if (!$tool_id || !kingy_ali_companion_videos_for_tool($tool_id, 1)) {
        return;
    }
    $style_file = KINGY_ALI_PLUGIN_DIR . 'assets/css/companion-videos.css';
    wp_enqueue_style(
        'kingy-ali-companion-videos',
        KINGY_ALI_PLUGIN_URL . 'assets/css/companion-videos.css',
        array('kingy-ali-launch-intelligence'),
        is_readable($style_file) ? (string) filemtime($style_file) : KINGY_ALI_VERSION
    );
}

function kingy_ali_companion_cache_headers($headers, $wp) {
    unset($wp);
    if (!kingy_ali_is_companion_surface()) {
        return $headers;
    }
    if (is_singular('kingy_video') && !kingy_ali_companion_may_index(get_queried_object_id())) {
        $headers['Cache-Control'] = 'private, no-store, max-age=0';
        return $headers;
    }
    $headers['Cache-Control'] = 'public, max-age=0, s-maxage=300, stale-while-revalidate=30';
    return $headers;
}

/**
 * Replace early/default cache headers after WordPress has resolved the queried
 * companion. WP::send_headers() runs before the main query, so the wp_headers
 * filter alone cannot reliably identify authenticated draft previews.
 */
function kingy_ali_enforce_companion_private_cache_headers() {
    if (!is_singular('kingy_video')) {
        return;
    }
    $post_id = get_queried_object_id();
    if (!$post_id || kingy_ali_companion_may_index($post_id)) {
        return;
    }
    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }
    nocache_headers();
    header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0', true);
    header('CDN-Cache-Control: no-store', true);
    header('Cloudflare-CDN-Cache-Control: no-store', true);
    header('Surrogate-Control: no-store', true);
}

function kingy_ali_companion_youtube_thumbnail($youtube_id, $quality = 'maxresdefault') {
    $youtube_id = kingy_ali_companion_youtube_id($youtube_id);
    $quality = in_array($quality, array('maxresdefault', 'hqdefault'), true) ? $quality : 'maxresdefault';
    return $youtube_id ? 'https://i.ytimg.com/vi/' . rawurlencode($youtube_id) . '/' . $quality . '.jpg' : '';
}

function kingy_ali_output_companion_video_schema() {
    if (!is_singular('kingy_video')) {
        return;
    }
    $post_id = get_queried_object_id();
    if (!kingy_ali_companion_may_index($post_id)) {
        return;
    }
    $youtube_id = kingy_ali_companion_youtube_id(get_post_meta($post_id, kingy_ali_companion_meta_key('youtube_video_id'), true));
    $publish_date = get_post_meta($post_id, kingy_ali_companion_meta_key('video_publish_date'), true);
    if (!$youtube_id || !kingy_ali_companion_valid_date($publish_date)) {
        return;
    }

    $about = array();
    foreach (kingy_ali_companion_featured_tool_ids($post_id) as $tool_id) {
        $about[] = array(
            '@type' => 'SoftwareApplication',
            '@id' => function_exists('kingy_ali_schema_entity_id')
                ? kingy_ali_schema_entity_id($tool_id, 'tool')
                : get_permalink($tool_id) . '#tool',
            'name' => get_the_title($tool_id),
            'url' => get_permalink($tool_id),
        );
    }
    $description = kingy_ali_companion_seo_description('');
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'VideoObject',
        '@id' => get_permalink($post_id) . '#video',
        'name' => get_the_title($post_id),
        'description' => $description,
        'thumbnailUrl' => array(
            kingy_ali_companion_youtube_thumbnail($youtube_id),
            kingy_ali_companion_youtube_thumbnail($youtube_id, 'hqdefault'),
        ),
        'uploadDate' => $publish_date,
        'contentUrl' => 'https://www.youtube.com/watch?v=' . rawurlencode($youtube_id),
        'embedUrl' => 'https://www.youtube-nocookie.com/embed/' . rawurlencode($youtube_id),
        'mainEntityOfPage' => get_permalink($post_id),
        'dateModified' => get_post_modified_time('c', true, $post_id),
        'publisher' => array(
            '@type' => 'Organization',
            'name' => 'Kingy AI',
            'url' => home_url('/'),
        ),
        'about' => $about,
        'mentions' => $about,
    );
    $featured_image = get_the_post_thumbnail_url($post_id, 'full');
    if ($featured_image) {
        $schema['image'] = array($featured_image);
    }
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}

function kingy_ali_companion_videos_for_tool($tool_id, $limit = 6) {
    $tool_id = absint($tool_id);
    $limit = max(1, min(12, absint($limit)));
    if (!$tool_id || get_post_type($tool_id) !== 'kingy_ai_tool') {
        return array();
    }
    $query = new WP_Query(
        array(
            'post_type' => 'kingy_video',
            'post_status' => 'publish',
            'posts_per_page' => max($limit * 3, 12),
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => array(
                array(
                    'key' => kingy_ali_companion_meta_key('featured_tool_id'),
                    'value' => $tool_id,
                    'compare' => '=',
                    'type' => 'NUMERIC',
                ),
            ),
            'meta_key' => kingy_ali_companion_meta_key('video_publish_date'),
            'orderby' => 'meta_value',
            'order' => 'DESC',
        )
    );
    $ids = array();
    foreach ($query->posts as $video_id) {
        if (in_array($tool_id, kingy_ali_companion_featured_tool_ids($video_id), true)) {
            $ids[] = absint($video_id);
        }
        if (count($ids) >= $limit) {
            break;
        }
    }
    return $ids;
}

function kingy_ali_render_tool_companion_videos($tool_id, $limit = 6) {
    $video_ids = kingy_ali_companion_videos_for_tool($tool_id, $limit);
    if (!$video_ids) {
        return '';
    }
    ob_start();
    ?>
    <section class="kingy-ali-content-band kingy-ali-companion-reverse-links">
        <h2><?php esc_html_e('Featured in these videos', 'kingy-ai-launch-intelligence'); ?></h2>
        <div class="kingy-ali-companion-card-grid">
            <?php foreach ($video_ids as $video_id) :
                $youtube_id = get_post_meta($video_id, kingy_ali_companion_meta_key('youtube_video_id'), true);
                $publish_date = get_post_meta($video_id, kingy_ali_companion_meta_key('video_publish_date'), true);
                ?>
                <article class="kingy-ali-companion-card">
                    <a href="<?php echo esc_url(get_permalink($video_id)); ?>">
                        <img src="<?php echo esc_url(kingy_ali_companion_youtube_thumbnail($youtube_id)); ?>" data-fallback-src="<?php echo esc_url(kingy_ali_companion_youtube_thumbnail($youtube_id, 'hqdefault')); ?>" data-kingy-youtube-thumbnail alt="<?php echo esc_attr(sprintf(__('Video thumbnail for %s', 'kingy-ai-launch-intelligence'), get_the_title($video_id))); ?>" width="1280" height="720" loading="lazy" decoding="async">
                        <span><?php echo esc_html(get_the_title($video_id)); ?></span>
                    </a>
                    <?php if ($publish_date) : ?><time datetime="<?php echo esc_attr($publish_date); ?>"><?php echo esc_html(kingy_ali_public_profile_date_label($publish_date)); ?></time><?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_companion_tool_canonical_links($tool_id) {
    return array(
        array('label' => get_the_title($tool_id), 'url' => get_permalink($tool_id), 'type' => 'tool'),
    );
}

function kingy_ali_companion_cache_registry($post_id, $tool_ids = null) {
    $registry = array();
    if (!function_exists('kingy_ali_launch_cache_registry_add')) {
        return $registry;
    }
    $post_id = absint($post_id);
    if ($post_id) {
        kingy_ali_launch_cache_registry_add($registry, 'companion-video-' . $post_id, 'companion', get_permalink($post_id));
    }
    kingy_ali_launch_cache_registry_add($registry, 'companion-video-archive', 'companion', get_post_type_archive_link('kingy_video'));
    if ($tool_ids === null && $post_id) {
        $tool_ids = kingy_ali_companion_featured_tool_ids($post_id);
    }
    foreach (array_values(array_unique(array_filter(array_map('absint', (array) $tool_ids)))) as $tool_id) {
        kingy_ali_launch_cache_registry_add($registry, 'companion-tool-' . $tool_id, 'related', get_permalink($tool_id));
    }
    return $registry;
}

function kingy_ali_queue_companion_cache_purge($post_id, $post, $update) {
    unset($update);
    if (
        !$post
        || $post->post_type !== 'kingy_video'
        || wp_is_post_revision($post_id)
        || wp_is_post_autosave($post_id)
        || !function_exists('kingy_ali_queue_launch_collection_purge')
    ) {
        return;
    }
    kingy_ali_queue_launch_collection_purge(
        $post_id,
        false,
        'companion_video',
        kingy_ali_companion_cache_registry($post_id)
    );
}

function kingy_ali_companion_purge_dependency_registry($registry, $post_id) {
    if (get_post_type($post_id) !== 'kingy_ai_tool' || !function_exists('kingy_ali_launch_cache_registry_add')) {
        return $registry;
    }
    foreach (kingy_ali_companion_videos_for_tool($post_id, 12) as $video_id) {
        kingy_ali_launch_cache_registry_add($registry, 'companion-video-' . $video_id, 'companion', get_permalink($video_id));
    }
    return $registry;
}

function kingy_ali_companion_cdn_purge_urls($urls, $post_id) {
    $urls = is_array($urls) ? $urls : array();
    $post_type = get_post_type($post_id);
    if ($post_type === 'kingy_video') {
        $urls[] = wp_parse_url(get_permalink($post_id), PHP_URL_PATH);
        $urls[] = wp_parse_url(get_post_type_archive_link('kingy_video'), PHP_URL_PATH);
        foreach (kingy_ali_companion_featured_tool_ids($post_id) as $tool_id) {
            $urls[] = wp_parse_url(get_permalink($tool_id), PHP_URL_PATH);
        }
    } elseif ($post_type === 'kingy_ai_tool') {
        foreach (kingy_ali_companion_videos_for_tool($post_id, 12) as $video_id) {
            $urls[] = wp_parse_url(get_permalink($video_id), PHP_URL_PATH);
        }
    }
    return array_values(array_unique(array_filter($urls)));
}
