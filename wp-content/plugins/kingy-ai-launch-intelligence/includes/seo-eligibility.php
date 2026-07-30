<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * One persisted authority for entity SEO eligibility and public promotion.
 */

add_filter('wpseo_robots', 'kingy_ali_entity_seo_robots', 999);
add_filter('wpseo_canonical', 'kingy_ali_entity_seo_canonical', 999);
add_filter('get_canonical_url', 'kingy_ali_entity_seo_canonical', 999);
add_filter('wpseo_sitemap_entry', 'kingy_ali_entity_seo_sitemap_entry', 999, 3);
add_filter('wpseo_exclude_from_sitemap_by_post_ids', 'kingy_ali_entity_seo_sitemap_excluded_ids', 999);
add_filter('kingy_homepage_v2_launches_query', 'kingy_ali_entity_seo_homepage_launch_query', 999);
add_filter('posts_where', 'kingy_ali_entity_seo_search_where', 999, 2);
add_action('pre_get_posts', 'kingy_ali_entity_seo_public_query_gate', 999);

foreach (kingy_ali_entity_seo_post_types() as $kingy_ali_entity_seo_post_type) {
    add_action('save_post_' . $kingy_ali_entity_seo_post_type, 'kingy_ali_entity_seo_recalculate_on_save', 999, 3);
}
unset($kingy_ali_entity_seo_post_type);

add_action('set_object_terms', 'kingy_ali_entity_seo_recalculate_on_terms', 999, 6);
add_action('added_post_meta', 'kingy_ali_entity_seo_recalculate_on_meta', 999, 4);
add_action('updated_post_meta', 'kingy_ali_entity_seo_recalculate_on_meta', 999, 4);
add_action('deleted_post_meta', 'kingy_ali_entity_seo_recalculate_on_meta', 999, 4);

function kingy_ali_entity_seo_post_types() {
    return array('kingy_ai_launch', 'kingy_ai_tool', 'kingy_ai_company', 'kingy_ai_model');
}

function kingy_ali_entity_seo_state_meta_key() {
    return '_kingy_ali_seo_eligibility_state';
}

function kingy_ali_entity_seo_is_entity($post_id) {
    return in_array(get_post_type(absint($post_id)), kingy_ali_entity_seo_post_types(), true);
}

function kingy_ali_entity_seo_is_indexable($post_id) {
    $post_id = absint($post_id);
    return $post_id
        && kingy_ali_entity_seo_is_entity($post_id)
        && get_post_status($post_id) === 'publish'
        && get_post_meta($post_id, kingy_ali_entity_seo_state_meta_key(), true) === 'indexable';
}

/**
 * Backwards-compatible public predicate. All existing public surfaces that use
 * this function now read the persisted authority instead of recalculating.
 */
function kingy_ali_profile_should_noindex($post_id) {
    $post_id = absint($post_id);
    return $post_id && kingy_ali_entity_seo_is_entity($post_id)
        ? !kingy_ali_entity_seo_is_indexable($post_id)
        : false;
}

function kingy_ali_entity_seo_evaluate($post_id) {
    $post_id = absint($post_id);
    $post = $post_id ? get_post($post_id) : null;
    $reasons = array();

    if (!$post || !in_array($post->post_type, kingy_ali_entity_seo_post_types(), true)) {
        return array('state' => 'noindex', 'reasons' => array('not_an_entity'));
    }

    if ($post->post_status !== 'publish') {
        $reasons[] = 'not_published';
    }

    $override = sanitize_key((string) get_post_meta($post_id, '_kingy_ali_seo_eligibility_override', true));
    if ($override === 'hold') {
        $reasons[] = 'editorial_hold';
        $override_reason = trim((string) get_post_meta($post_id, '_kingy_ali_seo_eligibility_override_reason', true));
        if ($override_reason !== '') {
            $reasons[] = 'editorial_hold:' . sanitize_text_field($override_reason);
        }
    }

    if (!function_exists('kingy_ali_entity_quality_gate_should_noindex')) {
        $reasons[] = 'quality_gate_unavailable';
    } elseif (kingy_ali_entity_quality_gate_should_noindex($post_id, true)) {
        $reasons[] = 'quality_gate_failed';
    }

    return array(
        'state' => empty($reasons) ? 'indexable' : 'noindex',
        'reasons' => array_values(array_unique($reasons)),
    );
}

function kingy_ali_entity_seo_recalculate($post_id) {
    static $running = array();

    $post_id = absint($post_id);
    if (!$post_id || !kingy_ali_entity_seo_is_entity($post_id) || !empty($running[$post_id])) {
        return '';
    }

    $running[$post_id] = true;
    try {
        $result = kingy_ali_entity_seo_evaluate($post_id);
        $state = $result['state'] === 'indexable' ? 'indexable' : 'noindex';
        update_post_meta($post_id, kingy_ali_entity_seo_state_meta_key(), $state);
        update_post_meta($post_id, '_kingy_ali_seo_eligibility_reasons', wp_json_encode($result['reasons']));
        update_post_meta($post_id, '_kingy_ali_seo_eligibility_version', '1');
        update_post_meta($post_id, '_kingy_ali_seo_eligibility_checked_at', current_time('mysql', true));

        if ($state === 'indexable') {
            delete_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex');
            $canonical = get_permalink($post_id);
            if ($canonical) {
                update_post_meta($post_id, '_yoast_wpseo_canonical', $canonical);
            }
        } else {
            update_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', '1');
            delete_post_meta($post_id, '_yoast_wpseo_canonical');
        }

        clean_post_cache($post_id);
        return $state;
    } finally {
        unset($running[$post_id]);
    }
}

function kingy_ali_entity_seo_recalculate_on_save($post_id, $post, $update) {
    if (!wp_is_post_revision($post_id) && !wp_is_post_autosave($post_id)) {
        kingy_ali_entity_seo_recalculate($post_id);
    }
}

function kingy_ali_entity_seo_recalculate_on_terms($object_id, $terms, $tt_ids, $taxonomy) {
    if (in_array((string) $taxonomy, array('kingy_launch_category', 'model_provider', 'model_modality', 'model_status'), true)) {
        kingy_ali_entity_seo_recalculate($object_id);
    }
}

function kingy_ali_entity_seo_recalculate_on_meta($meta_id, $post_id, $meta_key, $meta_value) {
    $meta_key = (string) $meta_key;
    if (
        strpos($meta_key, '_kingy_ali_') !== 0
        || strpos($meta_key, '_kingy_ali_seo_eligibility_') === 0
        || !kingy_ali_entity_seo_is_entity($post_id)
    ) {
        return;
    }

    kingy_ali_entity_seo_recalculate($post_id);
}

function kingy_ali_entity_seo_backfill() {
    $counts = array('indexable' => 0, 'noindex' => 0, 'total' => 0);
    foreach (kingy_ali_entity_seo_post_types() as $post_type) {
        $ids = get_posts(array(
            'post_type' => $post_type,
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC',
            'suppress_filters' => true,
        ));
        foreach ($ids as $post_id) {
            $state = kingy_ali_entity_seo_recalculate($post_id);
            if (isset($counts[$state])) {
                $counts[$state]++;
                $counts['total']++;
            }
        }
    }

    return $counts;
}

function kingy_ali_entity_seo_queried_id() {
    if (!is_singular(kingy_ali_entity_seo_post_types())) {
        return 0;
    }
    return absint(get_queried_object_id());
}

function kingy_ali_entity_seo_robots($robots) {
    $post_id = kingy_ali_entity_seo_queried_id();
    if (!$post_id) {
        return $robots;
    }

    return kingy_ali_entity_seo_is_indexable($post_id)
        ? 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1'
        : 'noindex, follow';
}

function kingy_ali_entity_seo_canonical($canonical) {
    $post_id = kingy_ali_entity_seo_queried_id();
    if (!$post_id) {
        return $canonical;
    }

    return kingy_ali_entity_seo_is_indexable($post_id) ? get_permalink($post_id) : false;
}

function kingy_ali_entity_seo_sitemap_entry($url, $type, $object) {
    if ($type === 'post' && is_object($object) && !empty($object->ID) && kingy_ali_entity_seo_is_entity($object->ID)) {
        return kingy_ali_entity_seo_is_indexable($object->ID) ? $url : false;
    }
    return $url;
}

function kingy_ali_entity_seo_sitemap_excluded_ids($excluded_ids) {
    $excluded_ids = array_map('absint', (array) $excluded_ids);
    $ids = get_posts(array(
        'post_type' => kingy_ali_entity_seo_post_types(),
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'suppress_filters' => true,
    ));
    foreach ($ids as $post_id) {
        if (!kingy_ali_entity_seo_is_indexable($post_id)) {
            $excluded_ids[] = absint($post_id);
        }
    }
    return array_values(array_unique(array_filter($excluded_ids)));
}

function kingy_ali_entity_seo_homepage_launch_query($query_args) {
    if (is_array($query_args) && function_exists('kingy_ali_apply_public_noindex_meta_constraint')) {
        kingy_ali_apply_public_noindex_meta_constraint($query_args);
    }
    return $query_args;
}

/**
 * Catch entity-only public discovery queries that do not use the shared query
 * builder. Singular requests remain reachable so held records can return a
 * deliberate noindex response instead of a soft 404.
 */
function kingy_ali_entity_seo_public_query_gate($query) {
    if (
        is_admin()
        || (defined('WP_CLI') && WP_CLI)
        || !$query instanceof WP_Query
        || $query->is_singular()
        || $query->get('kingy_ali_include_ineligible')
    ) {
        return;
    }

    $post_types = $query->get('post_type');
    $post_types = is_array($post_types) ? $post_types : array($post_types);
    $post_types = array_values(array_filter(array_map('sanitize_key', $post_types)));
    if (!$post_types || array_diff($post_types, kingy_ali_entity_seo_post_types())) {
        return;
    }

    $current_meta_query = $query->get('meta_query');
    if (kingy_ali_entity_seo_meta_query_has_state($current_meta_query)) {
        return;
    }

    $query_args = array('meta_query' => $current_meta_query);
    kingy_ali_apply_public_noindex_meta_constraint($query_args);
    $query->set('meta_query', $query_args['meta_query']);
}

function kingy_ali_entity_seo_meta_query_has_state($meta_query) {
    foreach ((array) $meta_query as $key => $constraint) {
        if ($key === 'key' && $constraint === kingy_ali_entity_seo_state_meta_key()) {
            return true;
        }
        if (is_array($constraint) && kingy_ali_entity_seo_meta_query_has_state($constraint)) {
            return true;
        }
    }
    return false;
}

/**
 * Native search mixes posts/pages with entities, so preserve non-entities and
 * require the persisted state only for entity rows.
 */
function kingy_ali_entity_seo_search_where($where, $query) {
    global $wpdb;

    if (is_admin() || !$query instanceof WP_Query || !$query->is_main_query() || !$query->is_search()) {
        return $where;
    }

    $post_types = array_map('sanitize_key', kingy_ali_entity_seo_post_types());
    $quoted_types = "'" . implode("','", array_map('esc_sql', $post_types)) . "'";
    $meta_key = esc_sql(kingy_ali_entity_seo_state_meta_key());
    $where .= " AND ({$wpdb->posts}.post_type NOT IN ({$quoted_types}) OR EXISTS ("
        . "SELECT 1 FROM {$wpdb->postmeta} kingy_entity_seo_pm "
        . "WHERE kingy_entity_seo_pm.post_id = {$wpdb->posts}.ID "
        . "AND kingy_entity_seo_pm.meta_key = '{$meta_key}' "
        . "AND kingy_entity_seo_pm.meta_value = 'indexable'))";

    return $where;
}
