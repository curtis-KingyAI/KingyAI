<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Launch collection freshness, edge-cache policy, and dependency purging.
 *
 * The stored mutation time is changed only by a real launch-index dependency
 * mutation (launch record, related profile, managed meta, or launch taxonomy).
 * Reading or rendering a collection never advances it.
 */

add_action('save_post_kingy_ai_launch', 'kingy_ali_maybe_purge_launch_collection_caches_for_post', 50, 3);
add_action('save_post_kingy_ai_tool', 'kingy_ali_maybe_queue_related_launch_entity_purge', 50, 3);
add_action('save_post_kingy_ai_company', 'kingy_ali_maybe_queue_related_launch_entity_purge', 50, 3);
add_action('pre_post_update', 'kingy_ali_launch_cache_entity_will_update', 5, 2);
add_action('before_delete_post', 'kingy_ali_maybe_purge_launch_collection_caches_for_deleted_post', 50, 2);
add_filter('update_post_metadata', 'kingy_ali_launch_metadata_will_change', 20, 5);
add_filter('delete_post_metadata', 'kingy_ali_launch_metadata_will_be_deleted', 20, 5);
add_action('added_post_meta', 'kingy_ali_launch_metadata_did_change', 50, 4);
add_action('updated_post_meta', 'kingy_ali_launch_metadata_did_change', 50, 4);
add_action('deleted_post_meta', 'kingy_ali_launch_metadata_did_change', 50, 4);
add_action('set_object_terms', 'kingy_ali_launch_terms_did_change', 50, 6);
add_action('deleted_term_relationships', 'kingy_ali_launch_term_relationships_were_deleted', 50, 3);
add_action('edit_terms', 'kingy_ali_launch_term_will_change', 5, 3);
add_action('edited_terms', 'kingy_ali_launch_term_did_change', 50, 3);
add_action('pre_delete_term', 'kingy_ali_launch_term_will_be_deleted', 5, 2);
add_action('created_term', 'kingy_ali_launch_term_was_created', 50, 3);
add_action('shutdown', 'kingy_ali_flush_launch_collection_purge_queue', PHP_INT_MAX);
add_action('init', 'kingy_ali_initialize_launch_data_generation', 1);
add_action('rest_api_init', 'kingy_ali_register_launch_collection_cache_purge_route');
add_filter('cdn_purge_cache_urls', 'kingy_ali_add_launch_collection_urls_to_cdn_purge', 20, 2);
add_filter('wp_headers', 'kingy_ali_apply_launch_collection_cache_headers', 999, 2);
add_filter('rest_post_dispatch', 'kingy_ali_apply_launch_rest_cache_headers', 20, 3);

function kingy_ali_launch_mutation_option_name() {
    return 'kingy_ali_launch_last_mutation_gmt';
}

function kingy_ali_launch_obsolete_alias_paths() {
    $paths = apply_filters(
        'kingy_ali_launch_cache_alias_paths',
        array(
            'ai-launches/founder-submitted-ai-tools-2',
            'ai-launches/ai-search-and-research-tools',
            'ai-launches/ai-coding-agents-and-ides',
            'ai-launches/funding',
        )
    );
    return array_values(
        array_unique(
            array_filter(
                array_map(
                    function ($path) {
                        return trim((string) $path, '/');
                    },
                    (array) $paths
                )
            )
        )
    );
}

function kingy_ali_launch_cache_parse_url($url) {
    return function_exists('wp_parse_url') ? wp_parse_url($url) : parse_url($url);
}

function kingy_ali_launch_cache_registry_add(&$registry, $key, $type, $url, $canonical = true) {
    if (!is_scalar($url)) {
        return;
    }

    $url = trim((string) $url);
    if ($url === '') {
        return;
    }

    if (strpos($url, '/') === 0) {
        $url = home_url($url);
    }

    $parts = kingy_ali_launch_cache_parse_url($url);
    if (!is_array($parts)) {
        return;
    }

    $path = isset($parts['path']) && is_string($parts['path']) && $parts['path'] !== '' ? $parts['path'] : '/';
    if (strpos($path, '/') !== 0) {
        $path = '/' . $path;
    }
    $query = isset($parts['query']) && is_string($parts['query']) ? $parts['query'] : '';
    $cache_path = $query !== '' ? $path . '?' . $query : $path;
    $identity = strtolower(rtrim($path, '/')) . ($query !== '' ? '?' . $query : '');
    if ($identity === '') {
        $identity = '/';
    }

    foreach ($registry as $entry) {
        if (!empty($entry['identity']) && $entry['identity'] === $identity) {
            return;
        }
    }

    $registry[sanitize_key((string) $key)] = array(
        'key' => sanitize_key((string) $key),
        'type' => sanitize_key((string) $type),
        'url' => $url,
        'path' => $cache_path,
        'request_path' => $path,
        'query' => $query,
        'canonical' => (bool) $canonical,
        'identity' => $identity,
    );
}

function kingy_ali_launch_collection_cache_registry($launch_post_id = 0) {
    $launch_post_id = absint($launch_post_id);
    $registry = array();
    $alias_paths = kingy_ali_launch_obsolete_alias_paths();

    if (function_exists('kingy_ali_launch_collection_pages_meta')) {
        foreach ((array) kingy_ali_launch_collection_pages_meta() as $path => $meta) {
            $path = trim((string) $path, '/');
            if ($path === '' || in_array($path, $alias_paths, true)) {
                continue;
            }
            $url = is_array($meta) && !empty($meta['url']) ? $meta['url'] : home_url('/' . $path . '/');
            kingy_ali_launch_cache_registry_add($registry, 'collection-' . $path, 'collection', $url);
        }
    } else {
        foreach (array(
            'ai-launches',
            'ai-launches/today',
            'ai-launches/this-week',
            'ai-launches/ai-agents',
            'ai-launches/ai-video-tools',
            'ai-launches/ai-coding-tools',
            'ai-launches/ai-image-tools',
            'ai-launches/open-weight-models',
            'ai-launches/ai-search-research-tools',
            'ai-launches/ai-app-builders',
            'ai-launches/youtube-worthy-ai-tools',
            'ai-launches/founder-submitted-ai-tools',
            'ai-launches/funding-announcements',
            'ai-launches/creator-coverage-ai-launches',
        ) as $path) {
            kingy_ali_launch_cache_registry_add($registry, 'collection-' . $path, 'collection', home_url('/' . $path . '/'));
        }
    }

    kingy_ali_launch_cache_registry_add(
        $registry,
        'collection-launches-of-the-week',
        'collection',
        home_url('/ai-launches/launches-of-the-week/')
    );

    if (function_exists('get_terms')) {
        $terms = get_terms(
            array(
                'taxonomy' => 'kingy_launch_category',
                'hide_empty' => false,
            )
        );
        if (!is_wp_error($terms)) {
            foreach ((array) $terms as $term) {
                if (!is_object($term) || empty($term->slug)) {
                    continue;
                }
                $term_url = function_exists('get_term_link') ? get_term_link($term, 'kingy_launch_category') : '';
                if (is_wp_error($term_url) || !$term_url) {
                    $term_url = home_url('/ai-launch-category/' . $term->slug . '/');
                }
                kingy_ali_launch_cache_registry_add($registry, 'category-' . $term->slug, 'taxonomy', $term_url);
            }
        }
    }

    kingy_ali_launch_cache_registry_add($registry, 'homepage-launch-summary', 'related_collection', home_url('/'));
    kingy_ali_launch_cache_registry_add($registry, 'news-archive', 'related_collection', home_url('/news/'));
    kingy_ali_launch_cache_registry_add($registry, 'related-tool-directory', 'related_collection', home_url('/ai-tools/'));
    kingy_ali_launch_cache_registry_add($registry, 'related-company-directory', 'related_collection', home_url('/ai-companies/'));

    $rest_url = function_exists('rest_url') ? rest_url('wp/v2/kingy_ai_launch') : home_url('/wp-json/wp/v2/kingy_ai_launch');
    kingy_ali_launch_cache_registry_add($registry, 'rest-launch-collection', 'rest', $rest_url);

    $feed_url = function_exists('kingy_ali_launch_feed_url') ? kingy_ali_launch_feed_url() : home_url('/feed/kingy-ai-launches/');
    kingy_ali_launch_cache_registry_add($registry, 'feed-launches', 'feed', $feed_url);
    kingy_ali_launch_cache_registry_add($registry, 'feed-launches-compat', 'feed', home_url('/feed/?post_type=kingy_ai_launch'), false);

    foreach (array(
        'sitemap-core-index' => '/wp-sitemap.xml',
        'sitemap-core-launches' => '/wp-sitemap-posts-kingy_ai_launch-1.xml',
        'sitemap-yoast-index' => '/sitemap_index.xml',
        'sitemap-yoast-launches' => '/kingy_ai_launch-sitemap.xml',
    ) as $key => $path) {
        kingy_ali_launch_cache_registry_add($registry, $key, 'sitemap', home_url($path));
    }

    $published_launches = 0;
    if (function_exists('wp_count_posts')) {
        $counts = wp_count_posts('kingy_ai_launch');
        $published_launches = is_object($counts) && isset($counts->publish) ? absint($counts->publish) : 0;
    }
    $core_sitemap_size = max(1, (int) apply_filters('wp_sitemaps_max_urls', 2000, 'post'));
    $yoast_sitemap_size = max(1, (int) apply_filters('wpseo_sitemap_entries_per_page', 1000));
    for ($page = 2, $pages = (int) ceil($published_launches / $core_sitemap_size); $page <= $pages; $page++) {
        kingy_ali_launch_cache_registry_add(
            $registry,
            'sitemap-core-launches-' . $page,
            'sitemap',
            home_url('/wp-sitemap-posts-kingy_ai_launch-' . $page . '.xml')
        );
    }
    for ($page = 2, $pages = (int) ceil($published_launches / $yoast_sitemap_size); $page <= $pages; $page++) {
        kingy_ali_launch_cache_registry_add(
            $registry,
            'sitemap-yoast-launches-' . $page,
            'sitemap',
            home_url('/kingy_ai_launch-sitemap' . $page . '.xml')
        );
    }

    if ($launch_post_id && get_post_type($launch_post_id) === 'kingy_ai_launch') {
        kingy_ali_launch_cache_registry_add($registry, 'launch-' . $launch_post_id, 'singular', get_permalink($launch_post_id));

        $related_ids = array();
        foreach (array('related_tool_id' => 'kingy_ai_tool', 'related_company_id' => 'kingy_ai_company') as $meta_key => $post_type) {
            $related_id = function_exists('kingy_ali_get_meta')
                ? absint(kingy_ali_get_meta($launch_post_id, $meta_key))
                : absint(get_post_meta($launch_post_id, kingy_ali_meta_key($meta_key), true));
            if (!$related_id || get_post_type($related_id) !== $post_type) {
                continue;
            }
            $related_ids[$post_type] = $related_id;
            kingy_ali_launch_cache_registry_add($registry, $post_type . '-' . $related_id, 'related', get_permalink($related_id));
        }

        if (!empty($related_ids['kingy_ai_tool']) && empty($related_ids['kingy_ai_company'])) {
            $tool_company_id = function_exists('kingy_ali_get_meta')
                ? absint(kingy_ali_get_meta($related_ids['kingy_ai_tool'], 'related_company_id'))
                : absint(get_post_meta($related_ids['kingy_ai_tool'], kingy_ali_meta_key('related_company_id'), true));
            if ($tool_company_id && get_post_type($tool_company_id) === 'kingy_ai_company') {
                kingy_ali_launch_cache_registry_add($registry, 'kingy_ai_company-' . $tool_company_id, 'related', get_permalink($tool_company_id));
            }
        }
    }

    return (array) apply_filters('kingy_ali_launch_collection_cache_registry', $registry, $launch_post_id);
}

function kingy_ali_launch_purge_dependency_registry($launch_post_id = 0) {
    $registry = kingy_ali_launch_collection_cache_registry($launch_post_id);
    foreach (kingy_ali_launch_obsolete_alias_paths() as $path) {
        kingy_ali_launch_cache_registry_add($registry, 'alias-' . $path, 'alias', home_url('/' . $path . '/'), false);
    }
    return (array) apply_filters('kingy_ali_launch_purge_dependency_registry', $registry, absint($launch_post_id));
}

function kingy_ali_launch_collection_cache_paths($launch_post_id = 0) {
    $paths = array();
    foreach (kingy_ali_launch_collection_cache_registry($launch_post_id) as $entry) {
        if (!empty($entry['path'])) {
            $paths[] = (string) $entry['path'];
        }
    }
    return array_values(array_unique($paths));
}

function kingy_ali_launch_collection_cache_urls($launch_post_id = 0) {
    $urls = array();
    foreach (kingy_ali_launch_collection_cache_registry($launch_post_id) as $entry) {
        if (!empty($entry['url'])) {
            $urls[] = (string) $entry['url'];
        }
    }
    return array_values(array_unique($urls));
}

function kingy_ali_launch_purge_dependency_paths($launch_post_id = 0) {
    $paths = array();
    foreach (kingy_ali_launch_purge_dependency_registry($launch_post_id) as $entry) {
        if (!empty($entry['path'])) {
            $paths[] = (string) $entry['path'];
        }
    }
    return array_values(array_unique($paths));
}

function kingy_ali_record_launch_mutation_timestamp($timestamp = null) {
    if ($timestamp === null) {
        $timestamp = function_exists('current_time') ? current_time('timestamp', true) : time();
    }
    $timestamp = (int) $timestamp;
    if ($timestamp <= 0) {
        return null;
    }

    $value = gmdate('c', $timestamp);
    update_option(kingy_ali_launch_mutation_option_name(), $value, false);
    return $value;
}

function kingy_ali_record_launch_mutation($post_id = 0, $timestamp = null) {
    if (!absint($post_id)) {
        return null;
    }
    return kingy_ali_record_launch_mutation_timestamp($timestamp);
}

/**
 * Request-local purge queue. Registry entries are captured when each hook
 * fires, so both old and new relationship permalinks survive later writes.
 */
function kingy_ali_launch_purge_queue_state($operation = 'get', $payload = null) {
    static $state = array(
        'registry' => array(),
        'post_ids' => array(),
        'record_mutation' => false,
        'reasons' => array(),
    );

    if ($operation === 'clear') {
        $previous = $state;
        $state = array('registry' => array(), 'post_ids' => array(), 'record_mutation' => false, 'reasons' => array());
        return $previous;
    }

    if ($operation === 'add' && is_array($payload)) {
        foreach ((array) ($payload['registry'] ?? array()) as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $encoded_entry = function_exists('wp_json_encode') ? wp_json_encode($entry) : json_encode($entry);
            $identity = !empty($entry['identity']) ? (string) $entry['identity'] : md5((string) $encoded_entry);
            $state['registry'][$identity] = $entry;
        }
        $post_id = absint($payload['post_id'] ?? 0);
        if ($post_id) {
            $state['post_ids'][$post_id] = $post_id;
        }
        if (!empty($payload['record_mutation'])) {
            $state['record_mutation'] = true;
        }
        if (!empty($payload['reason'])) {
            $state['reasons'][sanitize_key((string) $payload['reason'])] = true;
        }
    }

    return $state;
}

function kingy_ali_queue_launch_collection_purge($post_id = 0, $record_mutation = true, $reason = 'mutation', $registry = null) {
    $post_id = absint($post_id);
    if (!is_array($registry)) {
        $registry = kingy_ali_launch_purge_dependency_registry($post_id);
        $post_type = $post_id ? get_post_type($post_id) : '';
        if (in_array($post_type, array('kingy_ai_tool', 'kingy_ai_company'), true)) {
            kingy_ali_launch_cache_registry_add(
                $registry,
                'related-entity-' . $post_id,
                'related',
                get_permalink($post_id)
            );
        }
    }
    return kingy_ali_launch_purge_queue_state(
        'add',
        array(
            'registry' => $registry,
            'post_id' => $post_id,
            'record_mutation' => (bool) $record_mutation,
            'reason' => $reason,
        )
    );
}

function kingy_ali_flush_launch_collection_purge_queue() {
    $state = kingy_ali_launch_purge_queue_state('clear');
    if (empty($state['registry'])) {
        return null;
    }

    $post_ids = array_values(array_map('absint', (array) $state['post_ids']));
    $primary_post_id = $post_ids ? $post_ids[0] : 0;
    $result = kingy_ali_purge_launch_collection_caches(
        $primary_post_id,
        !empty($state['record_mutation']),
        array_values($state['registry'])
    );
    $result['affected_post_ids'] = $post_ids;
    $result['mutation_reasons'] = array_keys((array) $state['reasons']);
    return $result;
}

function kingy_ali_is_launch_cache_entity_post_type($post_type) {
    return in_array((string) $post_type, array('kingy_ai_launch', 'kingy_ai_tool', 'kingy_ai_company'), true);
}

function kingy_ali_is_launch_metadata_mutation($post_id, $meta_key) {
    return absint($post_id)
        && is_string($meta_key)
        && strpos($meta_key, '_kingy_ali_') === 0
        && kingy_ali_is_launch_cache_entity_post_type(get_post_type($post_id));
}

function kingy_ali_launch_metadata_will_change($check, $object_id, $meta_key, $meta_value, $prev_value) {
    unset($meta_value, $prev_value);
    if (kingy_ali_is_launch_metadata_mutation($object_id, $meta_key)) {
        kingy_ali_queue_launch_collection_purge($object_id, false, 'metadata_before_update');
    }
    return $check;
}

function kingy_ali_launch_metadata_will_be_deleted($delete, $object_id, $meta_key, $meta_value, $delete_all) {
    unset($meta_value, $delete_all);
    if (kingy_ali_is_launch_metadata_mutation($object_id, $meta_key)) {
        kingy_ali_queue_launch_collection_purge($object_id, false, 'metadata_before_delete');
    }
    return $delete;
}

function kingy_ali_launch_metadata_did_change($meta_id, $object_id, $meta_key, $meta_value) {
    unset($meta_id, $meta_value);
    if (kingy_ali_is_launch_metadata_mutation($object_id, $meta_key)) {
        kingy_ali_queue_launch_collection_purge($object_id, true, 'metadata');
    }
}

function kingy_ali_launch_cache_taxonomies() {
    return (array) apply_filters(
        'kingy_ali_launch_cache_taxonomies',
        array('kingy_launch_category', 'kingy_launch_type', 'kingy_audience', 'kingy_tool_attribute')
    );
}

function kingy_ali_launch_terms_did_change($object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids) {
    unset($terms, $tt_ids, $append, $old_tt_ids);
    if (kingy_ali_is_launch_cache_entity_post_type(get_post_type($object_id)) && in_array((string) $taxonomy, kingy_ali_launch_cache_taxonomies(), true)) {
        kingy_ali_queue_launch_collection_purge($object_id, true, 'taxonomy_assignment');
    }
}

function kingy_ali_launch_term_relationships_were_deleted($object_id, $tt_ids, $taxonomy) {
    unset($tt_ids);
    if (kingy_ali_is_launch_cache_entity_post_type(get_post_type($object_id)) && in_array((string) $taxonomy, kingy_ali_launch_cache_taxonomies(), true)) {
        kingy_ali_queue_launch_collection_purge($object_id, true, 'taxonomy_relationship_delete');
    }
}

function kingy_ali_launch_term_will_change($term_id, $taxonomy, $args) {
    unset($term_id, $args);
    if (in_array((string) $taxonomy, kingy_ali_launch_cache_taxonomies(), true)) {
        kingy_ali_queue_launch_collection_purge(0, false, 'taxonomy_term_before_update');
    }
}

function kingy_ali_launch_term_did_change($term_id, $taxonomy, $args) {
    unset($term_id, $args);
    if (in_array((string) $taxonomy, kingy_ali_launch_cache_taxonomies(), true)) {
        kingy_ali_queue_launch_collection_purge(0, true, 'taxonomy_term');
    }
}

function kingy_ali_launch_term_will_be_deleted($term_id, $taxonomy) {
    unset($term_id);
    if (in_array((string) $taxonomy, kingy_ali_launch_cache_taxonomies(), true)) {
        kingy_ali_queue_launch_collection_purge(0, true, 'taxonomy_term_delete');
    }
}

function kingy_ali_launch_term_was_created($term_id, $tt_id, $taxonomy) {
    unset($term_id, $tt_id);
    if (in_array((string) $taxonomy, kingy_ali_launch_cache_taxonomies(), true)) {
        kingy_ali_queue_launch_collection_purge(0, true, 'taxonomy_term_create');
    }
}

function kingy_ali_maybe_purge_launch_collection_caches_for_post($post_id, $post, $update) {
    unset($update);

    $post_id = absint($post_id);
    if (!$post_id || wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    if (!is_object($post) || empty($post->post_type) || $post->post_type !== 'kingy_ai_launch') {
        return;
    }

    kingy_ali_queue_launch_collection_purge($post_id, true, 'post');
}

function kingy_ali_maybe_queue_related_launch_entity_purge($post_id, $post, $update) {
    unset($update);
    $post_id = absint($post_id);
    if (!$post_id || wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    if (!is_object($post) || !kingy_ali_is_launch_cache_entity_post_type($post->post_type ?? '')) {
        return;
    }
    kingy_ali_queue_launch_collection_purge($post_id, true, 'related_profile');
}

function kingy_ali_launch_cache_entity_will_update($post_id, $data) {
    unset($data);
    $post_id = absint($post_id);
    if ($post_id && kingy_ali_is_launch_cache_entity_post_type(get_post_type($post_id))) {
        kingy_ali_queue_launch_collection_purge($post_id, false, 'post_before_update');
    }
}

function kingy_ali_maybe_purge_launch_collection_caches_for_deleted_post($post_id, $post) {
    $post_id = absint($post_id);
    if (!$post_id || !is_object($post) || empty($post->post_type) || !kingy_ali_is_launch_cache_entity_post_type($post->post_type)) {
        return;
    }

    kingy_ali_queue_launch_collection_purge($post_id, true, 'post_delete');
}

function kingy_ali_register_launch_collection_cache_purge_route() {
    register_rest_route(
        'kingy-ali/v1',
        '/purge-launch-collections',
        array(
            'methods' => 'POST',
            'callback' => 'kingy_ali_rest_purge_launch_collection_caches',
            'permission_callback' => function () {
                return current_user_can('publish_posts') || current_user_can('manage_options');
            },
        )
    );
}

function kingy_ali_rest_purge_launch_collection_caches() {
    return rest_ensure_response(kingy_ali_purge_launch_collection_caches(0, false));
}

function kingy_ali_launch_collection_cache_generation() {
    $generation = get_option('kingy_ali_launch_collection_cache_generation', '1');
    $generation = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $generation);
    return $generation !== '' ? $generation : '1';
}

function kingy_ali_bump_launch_collection_cache_generation() {
    $generation = function_exists('wp_generate_uuid4')
        ? str_replace('-', '', wp_generate_uuid4())
        : substr(hash('sha256', microtime(true) . ':' . mt_rand()), 0, 20);
    update_option('kingy_ali_launch_collection_cache_generation', $generation, false);
    return $generation;
}

function kingy_ali_launch_data_generation_option_name() {
    return 'kingy_ali_launch_data_generation';
}

function kingy_ali_launch_data_generation_is_valid($generation) {
    return is_scalar($generation) && preg_match('/^[1-9][0-9]*$/', (string) $generation) === 1;
}

function kingy_ali_launch_data_generation() {
    $generation = get_option(kingy_ali_launch_data_generation_option_name(), '');
    return kingy_ali_launch_data_generation_is_valid($generation) ? (string) $generation : '';
}

/**
 * Persist a positive generation without relying on an activation hook.
 *
 * Atomic directory swaps do not fire WordPress activation hooks. This
 * initializer therefore runs on `init`, before REST or feed rendering. The
 * single-statement upsert repairs missing, zero, and malformed values while
 * preserving an existing positive generation and its autoload state.
 */
function kingy_ali_initialize_launch_data_generation() {
    $current = kingy_ali_launch_data_generation();
    if (kingy_ali_launch_data_generation_is_valid($current)) {
        return $current;
    }

    $option_name = kingy_ali_launch_data_generation_option_name();
    global $wpdb;
    if (isset($wpdb) && is_object($wpdb) && !empty($wpdb->options) && method_exists($wpdb, 'query') && method_exists($wpdb, 'prepare')) {
        $sql = $wpdb->prepare(
            "INSERT INTO {$wpdb->options} (option_name, option_value, autoload)
             VALUES (%s, '1', 'no')
             ON DUPLICATE KEY UPDATE option_value = CASE
                 WHEN option_value REGEXP '^[1-9][0-9]*$' THEN option_value
                 ELSE '1'
             END",
            $option_name
        );
        $updated = $wpdb->query($sql);
        if ($updated !== false) {
            if (function_exists('wp_cache_delete')) {
                wp_cache_delete($option_name, 'options');
            }
            $current = kingy_ali_launch_data_generation();
            if (kingy_ali_launch_data_generation_is_valid($current)) {
                return $current;
            }
        }
    }

    update_option($option_name, '1', false);
    $current = kingy_ali_launch_data_generation();
    return kingy_ali_launch_data_generation_is_valid($current) ? $current : '';
}

/**
 * Advance the public data generation after a real launch mutation.
 *
 * The single-statement options-table update is atomic on MySQL/MariaDB, so
 * concurrent writes cannot move the generation backward or reuse a number.
 * The update_option fallback keeps test/minimal environments functional.
 */
function kingy_ali_bump_launch_data_generation() {
    $option_name = kingy_ali_launch_data_generation_option_name();
    global $wpdb;
    if (isset($wpdb) && is_object($wpdb) && !empty($wpdb->options) && method_exists($wpdb, 'query') && method_exists($wpdb, 'prepare')) {
        $sql = $wpdb->prepare(
            "INSERT INTO {$wpdb->options} (option_name, option_value, autoload)
             VALUES (%s, '1', 'no')
             ON DUPLICATE KEY UPDATE option_value = CASE
                 WHEN option_value REGEXP '^[1-9][0-9]*$' THEN CAST(option_value AS UNSIGNED) + 1
                 ELSE '1'
             END",
            $option_name
        );
        $updated = $wpdb->query($sql);
        if ($updated !== false) {
            if (function_exists('wp_cache_delete')) {
                wp_cache_delete($option_name, 'options');
            }
            return kingy_ali_launch_data_generation();
        }
    }

    $current_value = kingy_ali_launch_data_generation();
    if (!kingy_ali_launch_data_generation_is_valid($current_value)) {
        update_option($option_name, '1', false);
        return kingy_ali_launch_data_generation();
    }
    $current = (int) $current_value;
    $next = (string) ($current + 1);
    update_option($option_name, $next, false);
    return $next;
}

/**
 * Request-local release mode and adapter audit.
 *
 * No option is written here: an interrupted release cannot leave a persistent
 * cache-suppression flag behind. P0 apply and rollback explicitly enable this
 * mode for their process and flush the queued dependency registry themselves.
 */
function kingy_ali_launch_release_mode_state($operation = 'get', $payload = null) {
    static $state = array(
        'active' => false,
        'reason' => '',
        'adapter_invocations' => array(),
        'intended_paths' => array(),
        'intended_urls' => array(),
    );

    if ($operation === 'reset') {
        $state = array('active' => false, 'reason' => '', 'adapter_invocations' => array(), 'intended_paths' => array(), 'intended_urls' => array());
    } elseif ($operation === 'begin') {
        $state['active'] = true;
        $state['reason'] = sanitize_key(is_scalar($payload) ? (string) $payload : 'release');
    } elseif ($operation === 'record_adapter' && is_array($payload)) {
        $state['adapter_invocations'][] = $payload;
    } elseif ($operation === 'record_dependencies' && is_array($payload)) {
        $state['intended_paths'] = array_values(array_unique(array_merge($state['intended_paths'], (array) ($payload['paths'] ?? array()))));
        $state['intended_urls'] = array_values(array_unique(array_merge($state['intended_urls'], (array) ($payload['urls'] ?? array()))));
    }

    return $state;
}

function kingy_ali_begin_launch_release_mode($reason = 'release') {
    return kingy_ali_launch_release_mode_state('begin', $reason);
}

function kingy_ali_launch_release_mode_is_active() {
    $state = kingy_ali_launch_release_mode_state();
    $constant_enabled = defined('KINGY_ALI_RELEASE_MODE') && KINGY_ALI_RELEASE_MODE;
    return (bool) apply_filters('kingy_ali_launch_release_mode_active', $constant_enabled || !empty($state['active']), $state);
}

function kingy_ali_launch_cache_adapter_audit() {
    $state = kingy_ali_launch_release_mode_state();
    return array(
        'release_mode_active' => kingy_ali_launch_release_mode_is_active(),
        'release_mode_reason' => (string) $state['reason'],
        'adapter_invocation_count' => count($state['adapter_invocations']),
        'adapter_invocations' => array_values($state['adapter_invocations']),
        'intended_paths' => array_values($state['intended_paths']),
        'intended_urls' => array_values($state['intended_urls']),
    );
}

function kingy_ali_launch_collection_transient_keys() {
    $generation = kingy_ali_launch_collection_cache_generation();
    $date = function_exists('current_time') ? current_time('Ymd') : gmdate('Ymd');
    // Preserve the production Radar/newsletter transient invalidation contract
    // without adding generation keys, post URL purges, or publication hooks.
    $keys = array('kingy_ali_latest_launches_of_week_edition_cta_v2');
    foreach (range(1, 20) as $limit) {
        $keys[] = 'kingy_ali_daily_radar_posts_v3_' . $limit;
    }
    foreach (range(4, 6) as $limit) {
        $keys[] = 'kingy_ali_homepage_latest_launch_items_v3_' . $limit . '_' . $date;
        $keys[] = 'kingy_ali_homepage_latest_launch_items_v3_' . $limit . '_' . $date . '_' . $generation;
    }
    return array_values(array_unique($keys));
}

function kingy_ali_delete_launch_collection_transients() {
    $deleted = 0;
    if (function_exists('delete_transient')) {
        foreach (kingy_ali_launch_collection_transient_keys() as $key) {
            $deleted += delete_transient($key) ? 1 : 0;
        }
    }
    return $deleted;
}

function kingy_ali_purge_launch_collection_caches($launch_post_id = 0, $record_mutation = false, $registry_override = null) {
    $launch_post_id = absint($launch_post_id);
    $registry = is_array($registry_override) ? $registry_override : kingy_ali_launch_purge_dependency_registry($launch_post_id);
    $paths = array();
    $urls = array();

    foreach ($registry as $entry) {
        if (!empty($entry['path'])) {
            $paths[] = (string) $entry['path'];
        }
        if (!empty($entry['url'])) {
            $urls[] = (string) $entry['url'];
        }
    }
    $paths = array_values(array_unique($paths));
    $urls = array_values(array_unique($urls));
    $release_mode = kingy_ali_launch_release_mode_is_active();
    kingy_ali_launch_release_mode_state('record_dependencies', array('paths' => $paths, 'urls' => $urls));

    if ($record_mutation) {
        kingy_ali_record_launch_mutation_timestamp();
    }

    $cleaned_post_ids = array();
    foreach ($urls as $url) {
        $page_id = absint(url_to_postid($url));
        if (!$page_id || isset($cleaned_post_ids[$page_id])) {
            continue;
        }
        $cleaned_post_ids[$page_id] = true;
        clean_post_cache($page_id);
        if (function_exists('wp_cache_post_change')) {
            wp_cache_post_change($page_id);
        }
    }

    if ($launch_post_id && !isset($cleaned_post_ids[$launch_post_id])) {
        clean_post_cache($launch_post_id);
        if (function_exists('wp_cache_post_change')) {
            wp_cache_post_change($launch_post_id);
        }
    }

    $transients_deleted = kingy_ali_delete_launch_collection_transients();
    $cache_generation = kingy_ali_bump_launch_collection_cache_generation();
    $data_generation = $record_mutation
        ? kingy_ali_bump_launch_data_generation()
        : kingy_ali_launch_data_generation();
    $local_purge_adapters = array();

    if (!$release_mode && function_exists('wp_cache_clear_cache')) {
        kingy_ali_launch_release_mode_state('record_adapter', array('adapter' => 'wp_super_cache', 'scope' => 'all'));
        wp_cache_clear_cache();
        $local_purge_adapters[] = 'wp_super_cache';
    }
    if (!$release_mode && function_exists('prune_super_cache') && isset($GLOBALS['cache_path']) && is_string($GLOBALS['cache_path']) && $GLOBALS['cache_path'] !== '') {
        kingy_ali_launch_release_mode_state('record_adapter', array('adapter' => 'wp_super_cache_prune', 'scope' => 'all'));
        prune_super_cache($GLOBALS['cache_path'], true);
        $local_purge_adapters[] = 'wp_super_cache_prune';
    }

    foreach ($release_mode ? array() : $urls as $url) {
        do_action('litespeed_purge_url', $url);
        if (function_exists('has_action') && has_action('litespeed_purge_url')) {
            kingy_ali_launch_release_mode_state('record_adapter', array('adapter' => 'litespeed', 'scope' => $url));
            $local_purge_adapters[] = 'litespeed';
        }
        if (function_exists('w3tc_flush_url')) {
            kingy_ali_launch_release_mode_state('record_adapter', array('adapter' => 'w3tc', 'scope' => $url));
            w3tc_flush_url($url);
            $local_purge_adapters[] = 'w3tc';
        }
    }
    if (!$release_mode && function_exists('rocket_clean_files')) {
        kingy_ali_launch_release_mode_state('record_adapter', array('adapter' => 'wp_rocket', 'scope' => 'urls'));
        rocket_clean_files($urls);
        $local_purge_adapters[] = 'wp_rocket';
    }

    $cdn_purge_requested = false;
    $cdn_purge_result = null;
    $cdn_purge_error = '';
    $cdn_purge_action = $record_mutation ? 'purge_everything' : 'purge';
    $cdn_purge_payload = $record_mutation ? array() : $paths;
    if (!$release_mode && $paths && class_exists('CDN_Clear_Cache_Api') && is_callable(array('CDN_Clear_Cache_Api', 'cache_api_call'))) {
        $cdn_purge_requested = true;
        kingy_ali_launch_release_mode_state('record_adapter', array('adapter' => 'rocket_cdn', 'scope' => $cdn_purge_action));
        try {
            // Mutation batches use one full-edge purge because Rocket path
            // purges do not guarantee query/pagination variants when
            // CDN_HTML_PURGE is disabled. Manual maintenance purges retain the
            // audited path + query list as a bounded fallback.
            $cdn_purge_result = CDN_Clear_Cache_Api::cache_api_call($cdn_purge_payload, $cdn_purge_action);
        } catch (Throwable $throwable) {
            $cdn_purge_error = $throwable->getMessage();
        }
    }
    $cdn_purge_succeeded = kingy_ali_launch_cdn_purge_succeeded($cdn_purge_result);
    $local_purge_adapters = array_values(array_unique($local_purge_adapters));
    $purged = $cdn_purge_requested ? $cdn_purge_succeeded : !empty($local_purge_adapters);

    $result = array(
        'purged' => $purged,
        'purge_requested' => $cdn_purge_requested || !empty($local_purge_adapters),
        'launch_post_id' => $launch_post_id,
        'paths' => $paths,
        'urls' => $urls,
        'transients_deleted' => $transients_deleted,
        'cache_generation' => $cache_generation,
        'data_generation' => $data_generation,
        'local_purge_adapters' => $local_purge_adapters,
        'cdn_purge_requested' => $cdn_purge_requested,
        'cdn_purge_succeeded' => $cdn_purge_succeeded,
        'cdn_purge_action' => $cdn_purge_action,
        'cdn_purge_scope' => $record_mutation ? 'full_edge' : 'targeted_paths',
        'cdn_purge_result' => $cdn_purge_result,
        'cdn_purge_error' => $cdn_purge_error,
        'wp_super_cache_available' => function_exists('wp_cache_clear_cache') || function_exists('prune_super_cache'),
        'release_mode_active' => $release_mode,
        'cache_adapters_suppressed' => $release_mode,
        'cache_adapter_audit' => kingy_ali_launch_cache_adapter_audit(),
    );

    do_action('kingy_ali_launch_collection_caches_purged', $urls, $launch_post_id, $result);

    return $result;
}

function kingy_ali_launch_cdn_purge_succeeded($result) {
    if (is_object($result) && isset($result->success)) {
        return (bool) $result->success;
    }
    if (is_array($result) && array_key_exists('success', $result)) {
        return (bool) $result['success'];
    }
    return false;
}

function kingy_ali_add_launch_collection_urls_to_cdn_purge($urls, $post_id) {
    $post_id = absint($post_id);
    if (!$post_id || get_post_type($post_id) !== 'kingy_ai_launch') {
        return is_array($urls) ? $urls : array();
    }

    return array_values(array_unique(array_merge((array) $urls, kingy_ali_launch_purge_dependency_paths($post_id))));
}

function kingy_ali_launch_collection_cache_ttl() {
    $ttl = apply_filters('kingy_ali_launch_collection_cache_ttl', 300);
    $ttl = is_numeric($ttl) ? (int) $ttl : 300;
    return min(300, max(0, $ttl));
}

function kingy_ali_launch_cache_control_value() {
    $ttl = kingy_ali_launch_collection_cache_ttl();
    $stale = min(30, $ttl);
    return 'public, max-age=0, s-maxage=' . $ttl . ', stale-while-revalidate=' . $stale;
}

function kingy_ali_launch_cache_request_matches_entry($request_uri, $entry) {
    if (!is_array($entry) || empty($entry['request_path'])) {
        return false;
    }

    $request_parts = kingy_ali_launch_cache_parse_url($request_uri);
    if (!is_array($request_parts)) {
        return false;
    }
    $request_path = isset($request_parts['path']) && $request_parts['path'] !== '' ? $request_parts['path'] : '/';
    if (rtrim($request_path, '/') !== rtrim((string) $entry['request_path'], '/')) {
        return false;
    }

    if (empty($entry['query'])) {
        return true;
    }

    $required = array();
    $actual = array();
    parse_str((string) $entry['query'], $required);
    parse_str(isset($request_parts['query']) ? (string) $request_parts['query'] : '', $actual);
    foreach ($required as $key => $value) {
        if (!array_key_exists($key, $actual) || (string) $actual[$key] !== (string) $value) {
            return false;
        }
    }
    return true;
}

function kingy_ali_launch_cache_request_has_variant_query($request_uri, $entry) {
    $request_parts = kingy_ali_launch_cache_parse_url($request_uri);
    if (!is_array($request_parts)) {
        return true;
    }
    $actual = array();
    $required = array();
    parse_str(isset($request_parts['query']) ? (string) $request_parts['query'] : '', $actual);
    parse_str(!empty($entry['query']) ? (string) $entry['query'] : '', $required);
    ksort($actual);
    ksort($required);
    return $actual !== $required;
}

function kingy_ali_launch_frontend_cache_entry($request_uri) {
    foreach (kingy_ali_launch_collection_cache_registry(0) as $entry) {
        if (empty($entry['type']) || !in_array($entry['type'], array('collection', 'taxonomy', 'feed'), true)) {
            continue;
        }
        if (kingy_ali_launch_cache_request_matches_entry($request_uri, $entry)) {
            return $entry;
        }
    }
    return null;
}

function kingy_ali_is_launch_collection_variant_request() {
    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    if ($request_uri === '') {
        return false;
    }
    if (preg_match('#^/ai-launches/page/[0-9]+/?(?:\?.*)?$#', $request_uri)) {
        return true;
    }
    $entry = kingy_ali_launch_frontend_cache_entry($request_uri);
    return is_array($entry) && kingy_ali_launch_cache_request_has_variant_query($request_uri, $entry);
}

function kingy_ali_launch_no_store_headers($headers) {
    $headers['Cache-Control'] = 'private, no-store, max-age=0';
    $headers['CDN-Cache-Control'] = 'no-store';
    $headers['Cloudflare-CDN-Cache-Control'] = 'no-store';
    $headers['Surrogate-Control'] = 'no-store';
    $headers['X-Kingy-Launch-Cache-Policy'] = 'variant-no-store';
    return $headers;
}

function kingy_ali_launch_request_has_private_credentials() {
    foreach (array('HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION', 'PHP_AUTH_USER') as $key) {
        if (!empty($_SERVER[$key])) {
            return true;
        }
    }
    foreach (array_keys((array) ($_COOKIE ?? array())) as $cookie_name) {
        if (strpos((string) $cookie_name, 'wordpress_logged_in_') === 0 || strpos((string) $cookie_name, 'wordpress_sec_') === 0) {
            return true;
        }
    }
    return false;
}

function kingy_ali_launch_response_status_is_cacheable($status) {
    $status = (int) $status;
    return $status >= 200 && $status < 300;
}

function kingy_ali_is_cacheable_launch_collection_request() {
    $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
    if (!in_array($method, array('GET', 'HEAD'), true)) {
        return false;
    }
    if ((function_exists('is_admin') && is_admin()) || (function_exists('is_user_logged_in') && is_user_logged_in()) || kingy_ali_launch_request_has_private_credentials()) {
        return false;
    }
    if ((function_exists('wp_doing_ajax') && wp_doing_ajax()) || (function_exists('is_preview') && is_preview()) || (function_exists('is_404') && is_404())) {
        return false;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    if ($request_uri === '') {
        return false;
    }
    $request_parts = kingy_ali_launch_cache_parse_url($request_uri);
    $query = array();
    if (is_array($request_parts) && !empty($request_parts['query'])) {
        parse_str((string) $request_parts['query'], $query);
    }
    foreach (array('preview', '_wpnonce', 'context', 'status') as $private_query_key) {
        if (array_key_exists($private_query_key, $query)) {
            return false;
        }
    }

    $entry = kingy_ali_launch_frontend_cache_entry($request_uri);
    return is_array($entry) && !kingy_ali_launch_cache_request_has_variant_query($request_uri, $entry);
}

function kingy_ali_apply_launch_collection_cache_headers($headers, $wp = null) {
    unset($wp);
    $status = function_exists('http_response_code') ? http_response_code() : 200;
    if ($status === false || $status === 0) {
        $status = 200;
    }
    if (!is_array($headers) || !kingy_ali_launch_response_status_is_cacheable($status)) {
        return $headers;
    }
    if (kingy_ali_is_launch_collection_variant_request()) {
        return kingy_ali_launch_no_store_headers($headers);
    }
    if (!kingy_ali_is_cacheable_launch_collection_request()) {
        return $headers;
    }

    $ttl = kingy_ali_launch_collection_cache_ttl();
    $headers['Cache-Control'] = kingy_ali_launch_cache_control_value();
    $headers['CDN-Cache-Control'] = 'public, max-age=' . $ttl;
    $headers['Cloudflare-CDN-Cache-Control'] = 'public, max-age=' . $ttl;
    $headers['Surrogate-Control'] = 'max-age=' . $ttl;
    $headers['X-Kingy-Launch-Cache-Policy'] = 'canonical-' . $ttl;
    $mutation = kingy_ali_launch_freshness_stored_mutation();
    if (!empty($mutation['iso'])) {
        $headers['X-Kingy-Launch-Last-Mutation'] = $mutation['iso'];
    }
    return $headers;
}

function kingy_ali_apply_launch_rest_cache_headers($response, $server, $request) {
    unset($server);
    $route = is_object($request) && method_exists($request, 'get_route') ? rtrim((string) $request->get_route(), '/') : '';
    $method = is_object($request) && method_exists($request, 'get_method') ? strtoupper((string) $request->get_method()) : 'GET';
    if ($route !== '/wp/v2/kingy_ai_launch' || !in_array($method, array('GET', 'HEAD'), true) || kingy_ali_launch_request_has_private_credentials()) {
        return $response;
    }
    if (function_exists('is_user_logged_in') && is_user_logged_in()) {
        return $response;
    }

    $get_param = function ($key, $default = null) use ($request) {
        if (!is_object($request) || !method_exists($request, 'get_param')) {
            return $default;
        }
        $value = $request->get_param($key);
        return $value === null ? $default : $value;
    };
    $context = (string) $get_param('context', 'view');
    $status_param = $get_param('status', 'publish');
    $statuses = array();
    foreach ((array) $status_param as $status_value) {
        $statuses[] = is_scalar($status_value) ? sanitize_key((string) $status_value) : '';
    }
    $statuses = array_values(array_unique($statuses));
    if ($context !== 'view' || $statuses !== array('publish')) {
        return $response;
    }

    if (!is_object($response) || !method_exists($response, 'header')) {
        $response = rest_ensure_response($response);
    }
    if (!is_object($response) || !method_exists($response, 'header')) {
        return $response;
    }

    $status = method_exists($response, 'get_status') ? (int) $response->get_status() : 200;
    if (!kingy_ali_launch_response_status_is_cacheable($status)) {
        return $response;
    }
    $existing_headers = method_exists($response, 'get_headers') ? (array) $response->get_headers() : array();
    $existing_control = '';
    foreach ($existing_headers as $header_name => $header_value) {
        if (strtolower((string) $header_name) === 'cache-control') {
            $existing_control = strtolower((string) $header_value);
            break;
        }
    }
    if (strpos($existing_control, 'private') !== false || strpos($existing_control, 'no-store') !== false) {
        return $response;
    }

    $query_params = method_exists($request, 'get_query_params') ? (array) $request->get_query_params() : array();
    $data_generation = function_exists('kingy_ali_launch_data_generation')
        ? kingy_ali_launch_data_generation()
        : '';
    if (!kingy_ali_launch_data_generation_is_valid($data_generation)) {
        foreach (kingy_ali_launch_no_store_headers(array()) as $header_name => $header_value) {
            $response->header($header_name, $header_value);
        }
        return $response;
    }
    if ($query_params) {
        foreach (kingy_ali_launch_no_store_headers(array()) as $header_name => $header_value) {
            $response->header($header_name, $header_value);
        }
        $response->header('X-Kingy-Launch-Generation', $data_generation);
        return $response;
    }

    $ttl = kingy_ali_launch_collection_cache_ttl();
    $response->header('Cache-Control', kingy_ali_launch_cache_control_value());
    $response->header('CDN-Cache-Control', 'public, max-age=' . $ttl);
    $response->header('Cloudflare-CDN-Cache-Control', 'public, max-age=' . $ttl);
    $response->header('Surrogate-Control', 'max-age=' . $ttl);
    $response->header('X-Kingy-Launch-Cache-Policy', 'canonical-' . $ttl);
    $response->header('X-Kingy-Launch-Generation', $data_generation);
    $mutation = kingy_ali_launch_freshness_stored_mutation();
    if (!empty($mutation['iso'])) {
        $response->header('X-Kingy-Launch-Last-Mutation', $mutation['iso']);
    }
    return $response;
}

function kingy_ali_launch_edge_verification_targets() {
    return (array) apply_filters(
        'kingy_ali_launch_edge_verification_targets',
        array(
            'html' => home_url('/ai-launches/'),
            'feed' => function_exists('kingy_ali_launch_feed_url') ? kingy_ali_launch_feed_url() : home_url('/feed/kingy-ai-launches/'),
            'rest' => function_exists('rest_url') ? rest_url('wp/v2/kingy_ai_launch') : home_url('/wp-json/wp/v2/kingy_ai_launch'),
        )
    );
}

function kingy_ali_launch_freshness_normalize_date($value) {
    if (!is_scalar($value)) {
        return null;
    }
    $value = trim((string) $value);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || (is_array($errors) && (!empty($errors['warning_count']) || !empty($errors['error_count']))) || $date->format('Y-m-d') !== $value) {
        return null;
    }
    return $value;
}

function kingy_ali_launch_freshness_stored_mutation() {
    $raw = get_option(kingy_ali_launch_mutation_option_name(), '');
    if (!is_scalar($raw) || trim((string) $raw) === '') {
        return array('iso' => null, 'timestamp' => null);
    }
    $timestamp = strtotime((string) $raw);
    if (!$timestamp) {
        return array('iso' => null, 'timestamp' => null);
    }
    return array('iso' => gmdate('c', $timestamp), 'timestamp' => $timestamp);
}

function kingy_ali_launch_freshness_latest_launch_date() {
    global $wpdb;
    if (isset($wpdb) && is_object($wpdb) && !empty($wpdb->posts) && !empty($wpdb->postmeta) && method_exists($wpdb, 'get_var')) {
        $meta_key = kingy_ali_meta_key('launch_date');
        $sql = $wpdb->prepare(
            "SELECT MAX(pm.meta_value)
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.post_type = %s
              AND p.post_status = %s
              AND pm.meta_key = %s
              AND pm.meta_value REGEXP %s
              AND STR_TO_DATE(pm.meta_value, '%%Y-%%m-%%d') IS NOT NULL",
            'kingy_ai_launch',
            'publish',
            $meta_key,
            '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'
        );
        $database_date = kingy_ali_launch_freshness_normalize_date($wpdb->get_var($sql));
        if ($database_date !== null) {
            return $database_date;
        }
    }

    $ids = get_posts(
        array(
            'post_type' => 'kingy_ai_launch',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_key' => kingy_ali_meta_key('launch_date'),
            'orderby' => 'meta_value',
            'order' => 'DESC',
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
            'meta_query' => array(
                array(
                    'key' => kingy_ali_meta_key('launch_date'),
                    'compare' => 'EXISTS',
                ),
            ),
        )
    );

    foreach ((array) $ids as $post_id) {
        $date = kingy_ali_launch_freshness_normalize_date(get_post_meta(absint($post_id), kingy_ali_meta_key('launch_date'), true));
        if ($date !== null) {
            return $date;
        }
    }
    return null;
}

function kingy_ali_launch_freshness_coverage_lag_days($latest_launch_date, $today = null) {
    $latest_launch_date = kingy_ali_launch_freshness_normalize_date($latest_launch_date);
    if ($latest_launch_date === null) {
        return null;
    }
    if ($today === null) {
        $today = function_exists('current_time') ? current_time('Y-m-d') : gmdate('Y-m-d');
    }
    $today = kingy_ali_launch_freshness_normalize_date($today);
    if ($today === null) {
        return null;
    }

    $latest = new DateTimeImmutable($latest_launch_date, new DateTimeZone('UTC'));
    $current = new DateTimeImmutable($today, new DateTimeZone('UTC'));
    $lag = (int) $latest->diff($current)->format('%r%a');
    return max(0, $lag);
}

function kingy_ali_launch_freshness_snapshot($refresh = false) {
    static $snapshot = null;
    if (!$refresh && is_array($snapshot)) {
        return $snapshot;
    }

    $mutation = kingy_ali_launch_freshness_stored_mutation();
    $latest_launch_date = kingy_ali_launch_freshness_latest_launch_date();
    $snapshot = array(
        'last_mutation_gmt' => $mutation['iso'],
        'last_mutation_timestamp' => $mutation['timestamp'],
        'latest_launch_date' => $latest_launch_date,
        'coverage_lag_days' => kingy_ali_launch_freshness_coverage_lag_days($latest_launch_date),
    );
    return $snapshot;
}

function kingy_ali_launch_freshness_date_label($date) {
    $date = kingy_ali_launch_freshness_normalize_date($date);
    if ($date === null) {
        return __('Not available', 'kingy-ai-launch-intelligence');
    }
    $timestamp = strtotime($date . ' 12:00:00 UTC');
    return date_i18n(get_option('date_format'), $timestamp);
}

function kingy_ali_launch_freshness_mutation_label($timestamp) {
    $timestamp = (int) $timestamp;
    if ($timestamp <= 0) {
        return __('Not recorded', 'kingy-ai-launch-intelligence');
    }
    $format = trim((string) get_option('date_format') . ' ' . (string) get_option('time_format'));
    $label = function_exists('wp_date')
        ? wp_date($format, $timestamp, new DateTimeZone('UTC'))
        : gmdate($format, $timestamp);
    return $label . ' ' . __('UTC', 'kingy-ai-launch-intelligence');
}

function kingy_ali_launch_freshness_lag_label($lag) {
    if ($lag === null) {
        return __('Not available', 'kingy-ai-launch-intelligence');
    }
    $lag = max(0, (int) $lag);
    return sprintf(
        _n('%s day', '%s days', $lag, 'kingy-ai-launch-intelligence'),
        number_format_i18n($lag)
    );
}

function kingy_ali_render_launch_freshness($surface = 'launch_collection') {
    $snapshot = kingy_ali_launch_freshness_snapshot();
    $lag_value = $snapshot['coverage_lag_days'] === null ? '' : (string) $snapshot['coverage_lag_days'];

    ob_start();
    ?>
    <aside class="kingy-ali-launch-freshness" data-surface="<?php echo esc_attr(sanitize_key($surface)); ?>" data-coverage-lag-days="<?php echo esc_attr($lag_value); ?>" aria-labelledby="kingy-ali-launch-freshness-title">
        <div class="kingy-ali-launch-freshness__heading">
            <p class="kingy-ali-kicker"><?php esc_html_e('Tracker freshness', 'kingy-ai-launch-intelligence'); ?></p>
            <h3 id="kingy-ali-launch-freshness-title"><?php esc_html_e('Launch data freshness', 'kingy-ai-launch-intelligence'); ?></h3>
        </div>
        <dl class="kingy-ali-launch-freshness__facts">
            <div>
                <dt><?php esc_html_e('Last refreshed', 'kingy-ai-launch-intelligence'); ?></dt>
                <dd><?php if (!empty($snapshot['last_mutation_gmt'])) : ?><time datetime="<?php echo esc_attr($snapshot['last_mutation_gmt']); ?>"><?php echo esc_html(kingy_ali_launch_freshness_mutation_label($snapshot['last_mutation_timestamp'])); ?></time><?php else : ?><?php esc_html_e('Not recorded', 'kingy-ai-launch-intelligence'); ?><?php endif; ?></dd>
            </div>
            <div>
                <dt><?php esc_html_e('Newest tracked launch', 'kingy-ai-launch-intelligence'); ?></dt>
                <dd><?php if (!empty($snapshot['latest_launch_date'])) : ?><time datetime="<?php echo esc_attr($snapshot['latest_launch_date']); ?>"><?php echo esc_html(kingy_ali_launch_freshness_date_label($snapshot['latest_launch_date'])); ?></time><?php else : ?><?php esc_html_e('Not available', 'kingy-ai-launch-intelligence'); ?><?php endif; ?></dd>
            </div>
            <div>
                <dt><?php esc_html_e('Coverage lag', 'kingy-ai-launch-intelligence'); ?></dt>
                <dd><?php echo esc_html(kingy_ali_launch_freshness_lag_label($snapshot['coverage_lag_days'])); ?></dd>
            </div>
        </dl>
        <p class="kingy-ali-launch-freshness__note"><?php esc_html_e('Last refreshed is the stored time of the most recent launch record, related profile, launch metadata, or launch taxonomy mutation that triggered cache invalidation. Viewing this page does not change it.', 'kingy-ai-launch-intelligence'); ?></p>
    </aside>
    <?php
    return (string) ob_get_clean();
}

function kingy_ali_should_render_launch_freshness() {
    if ((function_exists('is_admin') && is_admin()) || (function_exists('is_feed') && is_feed())) {
        return false;
    }
    if (function_exists('kingy_ali_current_launch_collection_meta')) {
        return (bool) kingy_ali_current_launch_collection_meta();
    }
    return false;
}

function kingy_ali_render_launch_freshness_once($surface = 'launch_collection') {
    static $rendered = false;
    if ($rendered || !kingy_ali_should_render_launch_freshness()) {
        return '';
    }
    $rendered = true;
    return kingy_ali_render_launch_freshness($surface);
}
