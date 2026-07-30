<?php
/**
 * Dry-run-first graph metadata sync helpers and WP-CLI command.
 *
 * This intentionally avoids the existing graph helper functions that can publish
 * linked records as side effects.
 *
 * @package Kingy_AI_Launch_Intelligence
 */

if (!defined('ABSPATH')) {
    exit;
}

function kingy_ali_safe_sync_graph_tool_field_map() {
    return array(
        'company' => 'company',
        'official_url' => 'official_url',
        'demo_url' => 'demo_url',
        'what_launched' => 'what_it_does',
        'who_it_is_for' => 'best_for',
        'pricing' => 'pricing',
        'free_plan' => 'free_plan',
        'api_available' => 'api_available',
        'open_source_or_open_weight' => 'open_source_or_open_weight',
        'related_alternatives_url' => 'alternatives_url',
        'related_article_url' => 'related_article_url',
        'related_course_url' => 'related_course_url',
        'related_review_url' => 'related_review_url',
        'last_verified' => 'last_verified',
    );
}

function kingy_ali_safe_sync_graph_company_field_map() {
    return array(
        'official_url' => 'official_url',
        'what_launched' => 'company_summary',
        'last_verified' => 'last_verified',
        'verification_status' => 'verification_status',
    );
}

function kingy_ali_safe_sync_graph_parse_ids($value) {
    $ids = array();
    foreach (explode(',', (string) $value) as $raw_id) {
        $id = absint(trim($raw_id));
        if ($id) {
            $ids[] = $id;
        }
    }

    return array_values(array_unique($ids));
}

function kingy_ali_safe_sync_graph_meta_value($post_id, $key) {
    if (function_exists('kingy_ali_get_meta')) {
        return kingy_ali_get_meta($post_id, $key);
    }

    return get_post_meta($post_id, kingy_ali_meta_key($key), true);
}

function kingy_ali_safe_sync_graph_public_text($value) {
    if (!is_scalar($value)) {
        return '';
    }

    return trim((string) $value);
}

function kingy_ali_safe_sync_graph_related_id($value) {
    if (function_exists('kingy_ali_public_profile_id')) {
        return kingy_ali_public_profile_id($value);
    }

    return is_scalar($value) ? absint($value) : 0;
}

function kingy_ali_safe_sync_graph_normalized_title($title) {
    $title = strtolower(wp_strip_all_tags((string) $title));
    $title = preg_replace('/[^a-z0-9]+/', ' ', $title);
    return trim(preg_replace('/\s+/', ' ', (string) $title));
}

function kingy_ali_safe_sync_graph_title_matches($candidate_title, $expected_title) {
    $candidate = kingy_ali_safe_sync_graph_normalized_title($candidate_title);
    $expected = kingy_ali_safe_sync_graph_normalized_title($expected_title);
    if ($candidate === '' || $expected === '') {
        return false;
    }

    return $candidate === $expected || strpos($candidate, $expected . ' ') === 0;
}

function kingy_ali_safe_sync_graph_find_exact_post($post_type, $title) {
    $title = kingy_ali_safe_sync_graph_public_text($title);
    if ($title === '') {
        return array('id' => 0, 'error' => 'missing_title');
    }

    $slug = sanitize_title($title);
    $matches = get_posts(
        array(
            'post_type' => $post_type,
            'post_status' => array('publish', 'pending', 'draft', 'private'),
            'posts_per_page' => 20,
            'name' => $slug,
            'orderby' => 'ID',
            'order' => 'ASC',
        )
    );

    $exact = array();
    foreach ($matches as $post) {
        if (kingy_ali_safe_sync_graph_title_matches($post->post_title, $title)) {
            $exact[] = $post;
        }
    }

    if (!$exact) {
        $search_matches = get_posts(
            array(
                'post_type' => $post_type,
                'post_status' => array('publish', 'pending', 'draft', 'private'),
                'posts_per_page' => 20,
                's' => $title,
                'orderby' => 'ID',
                'order' => 'ASC',
            )
        );
        foreach ($search_matches as $post) {
            if (kingy_ali_safe_sync_graph_title_matches($post->post_title, $title)) {
                $exact[] = $post;
            }
        }
    }

    $ids = array();
    foreach ($exact as $post) {
        $ids[(int) $post->ID] = $post;
    }
    $exact = array_values($ids);

    if (count($exact) > 1) {
        return array(
            'id' => 0,
            'error' => 'ambiguous_match',
            'matches' => array_map(
                function ($post) {
                    return array('id' => (int) $post->ID, 'title' => get_the_title($post), 'status' => get_post_status($post));
                },
                $exact
            ),
        );
    }

    if (!$exact) {
        return array('id' => 0, 'error' => 'not_found');
    }

    return array('id' => (int) $exact[0]->ID, 'error' => '');
}

function kingy_ali_safe_sync_graph_resolve_tool_id($launch_id, $explicit_tool_id = 0) {
    if ($explicit_tool_id) {
        return get_post_type($explicit_tool_id) === 'kingy_ai_tool'
            ? array('id' => $explicit_tool_id, 'error' => '')
            : array('id' => 0, 'error' => 'explicit_tool_wrong_post_type');
    }

    $related_tool_id = kingy_ali_safe_sync_graph_related_id(kingy_ali_safe_sync_graph_meta_value($launch_id, 'related_tool_id'));
    if ($related_tool_id) {
        return get_post_type($related_tool_id) === 'kingy_ai_tool'
            ? array('id' => $related_tool_id, 'error' => '')
            : array('id' => 0, 'error' => 'related_tool_wrong_post_type');
    }

    return kingy_ali_safe_sync_graph_find_exact_post('kingy_ai_tool', get_the_title($launch_id));
}

function kingy_ali_safe_sync_graph_resolve_company_id($launch_id, $explicit_company_id = 0) {
    if ($explicit_company_id) {
        return get_post_type($explicit_company_id) === 'kingy_ai_company'
            ? array('id' => $explicit_company_id, 'error' => '')
            : array('id' => 0, 'error' => 'explicit_company_wrong_post_type');
    }

    $related_company_id = kingy_ali_safe_sync_graph_related_id(kingy_ali_safe_sync_graph_meta_value($launch_id, 'related_company_id'));
    if ($related_company_id) {
        return get_post_type($related_company_id) === 'kingy_ai_company'
            ? array('id' => $related_company_id, 'error' => '')
            : array('id' => 0, 'error' => 'related_company_wrong_post_type');
    }

    $company_name = kingy_ali_safe_sync_graph_meta_value($launch_id, 'company');
    return kingy_ali_safe_sync_graph_find_exact_post('kingy_ai_company', $company_name);
}

function kingy_ali_safe_sync_graph_diff_meta($post_id, $key, $to, $overwrite) {
    $from = get_post_meta($post_id, kingy_ali_meta_key($key), true);
    $to = kingy_ali_safe_sync_graph_public_text($to);
    if ($to === '') {
        return null;
    }
    if ($from !== '' && (string) $from !== (string) $to && !$overwrite) {
        return array(
            'post_id' => absint($post_id),
            'field' => kingy_ali_meta_key($key),
            'from' => $from,
            'to' => $to,
            'action' => 'blocked_non_empty',
        );
    }
    if ((string) $from === (string) $to) {
        return null;
    }

    return array(
        'post_id' => absint($post_id),
        'field' => kingy_ali_meta_key($key),
        'from' => $from,
        'to' => $to,
        'action' => 'update_meta',
    );
}

function kingy_ali_safe_sync_graph_add_diff(&$diffs, $diff) {
    if (is_array($diff)) {
        $diffs[] = $diff;
    }
}

function kingy_ali_safe_sync_graph_plan_launch($launch_id, $args = array()) {
    $launch_id = absint($launch_id);
    $overwrite = !empty($args['overwrite']);
    $skip_company_updates = !empty($args['skip_company_updates']);
    $explicit_tool_id = !empty($args['tool_id']) ? absint($args['tool_id']) : 0;
    $explicit_company_id = !empty($args['company_id']) ? absint($args['company_id']) : 0;
    $plan = array(
        'launch_id' => $launch_id,
        'launch_title' => $launch_id ? get_the_title($launch_id) : '',
        'status' => 'planned',
        'blockers' => array(),
        'resolved' => array(
            'tool_id' => 0,
            'company_id' => 0,
        ),
        'original_statuses' => array(),
        'diffs' => array(),
    );

    if (!$launch_id || get_post_type($launch_id) !== 'kingy_ai_launch') {
        $plan['status'] = 'blocked';
        $plan['blockers'][] = 'launch_missing_or_wrong_post_type';
        return $plan;
    }
    $plan['original_statuses'][$launch_id] = get_post_status($launch_id);

    $tool = kingy_ali_safe_sync_graph_resolve_tool_id($launch_id, $explicit_tool_id);
    if (empty($tool['id'])) {
        $plan['blockers'][] = 'tool_' . $tool['error'];
    } else {
        $tool_id = absint($tool['id']);
        $plan['resolved']['tool_id'] = $tool_id;
        $plan['original_statuses'][$tool_id] = get_post_status($tool_id);
    }

    $company = kingy_ali_safe_sync_graph_resolve_company_id($launch_id, $explicit_company_id);
    if (empty($company['id'])) {
        $plan['blockers'][] = 'company_' . $company['error'];
    } else {
        $company_id = absint($company['id']);
        $plan['resolved']['company_id'] = $company_id;
        $plan['original_statuses'][$company_id] = get_post_status($company_id);
    }

    if ($plan['blockers']) {
        $plan['status'] = 'blocked';
        return $plan;
    }

    $tool_id = absint($plan['resolved']['tool_id']);
    $company_id = absint($plan['resolved']['company_id']);

    kingy_ali_safe_sync_graph_add_diff($plan['diffs'], kingy_ali_safe_sync_graph_diff_meta($launch_id, 'related_tool_id', $tool_id, $overwrite));
    kingy_ali_safe_sync_graph_add_diff($plan['diffs'], kingy_ali_safe_sync_graph_diff_meta($launch_id, 'related_company_id', $company_id, $overwrite));

    foreach (kingy_ali_safe_sync_graph_tool_field_map() as $launch_key => $tool_key) {
        kingy_ali_safe_sync_graph_add_diff(
            $plan['diffs'],
            kingy_ali_safe_sync_graph_diff_meta($tool_id, $tool_key, kingy_ali_safe_sync_graph_meta_value($launch_id, $launch_key), $overwrite)
        );
    }
    kingy_ali_safe_sync_graph_add_diff($plan['diffs'], kingy_ali_safe_sync_graph_diff_meta($tool_id, 'latest_launch_id', $launch_id, $overwrite));
    kingy_ali_safe_sync_graph_add_diff($plan['diffs'], kingy_ali_safe_sync_graph_diff_meta($tool_id, 'related_company_id', $company_id, $overwrite));

    if (!$skip_company_updates) {
        foreach (kingy_ali_safe_sync_graph_company_field_map() as $launch_key => $company_key) {
            kingy_ali_safe_sync_graph_add_diff(
                $plan['diffs'],
                kingy_ali_safe_sync_graph_diff_meta($company_id, $company_key, kingy_ali_safe_sync_graph_meta_value($launch_id, $launch_key), $overwrite)
            );
        }

        $summary = kingy_ali_safe_sync_graph_meta_value($launch_id, 'what_launched');
        if ($summary !== '') {
            kingy_ali_safe_sync_graph_add_diff(
                $plan['diffs'],
                kingy_ali_safe_sync_graph_diff_meta(
                    $company_id,
                    'ai_evidence',
                    sprintf(__('This company profile is backed by a linked AI launch record: %s', 'kingy-ai-launch-intelligence'), $summary),
                    $overwrite
                )
            );
            kingy_ali_safe_sync_graph_add_diff(
                $plan['diffs'],
                kingy_ali_safe_sync_graph_diff_meta(
                    $company_id,
                    'source_notes',
                    sprintf(__('Company context synced from the linked launch record "%s"; verify product claims against the launch source links before relying on this profile.', 'kingy-ai-launch-intelligence'), get_the_title($launch_id)),
                    $overwrite
                )
            );
        }
    }

    foreach ($plan['diffs'] as $diff) {
        if (isset($diff['action']) && $diff['action'] === 'blocked_non_empty') {
            $plan['blockers'][] = 'non_empty_field:' . $diff['post_id'] . ':' . $diff['field'];
        }
    }

    $plan['status'] = $plan['blockers'] ? 'blocked' : 'planned';
    return $plan;
}

function kingy_ali_safe_sync_graph_apply_plan($plan) {
    if (!empty($plan['blockers'])) {
        return array(
            'applied' => false,
            'errors' => array('plan_has_blockers'),
            'plan' => $plan,
        );
    }

    $original_statuses = isset($plan['original_statuses']) && is_array($plan['original_statuses']) ? $plan['original_statuses'] : array();
    foreach ($plan['diffs'] as $diff) {
        if (!isset($diff['action']) || $diff['action'] !== 'update_meta') {
            continue;
        }
        update_post_meta(absint($diff['post_id']), (string) $diff['field'], $diff['to']);
    }

    $status_errors = array();
    foreach ($original_statuses as $post_id => $original_status) {
        $current_status = get_post_status(absint($post_id));
        if ($current_status !== $original_status) {
            $status_errors[] = array(
                'post_id' => absint($post_id),
                'from' => $original_status,
                'to' => $current_status,
            );
        }
    }

    return array(
        'applied' => empty($status_errors),
        'errors' => $status_errors ? array('status_changed') : array(),
        'status_errors' => $status_errors,
        'plan' => $plan,
    );
}

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('kingy-ali sync-graph', 'Kingy_ALI_Safe_Sync_Graph_CLI');
}

class Kingy_ALI_Safe_Sync_Graph_CLI {
    public function __invoke($args, $assoc_args) {
        unset($args);

        $launch_ids = isset($assoc_args['launch_ids']) ? kingy_ali_safe_sync_graph_parse_ids($assoc_args['launch_ids']) : array();
        if (!$launch_ids) {
            WP_CLI::error('Provide exact launch_ids, for example --launch_ids=916998. Broad/all mode is intentionally unsupported.');
        }

        $apply = !empty($assoc_args['apply']);
        $overwrite = !empty($assoc_args['overwrite']);
        $skip_company_updates = !empty($assoc_args['skip-company-updates']);
        $preserve_status = !empty($assoc_args['preserve-status']) || !$apply;
        $format = isset($assoc_args['format']) ? sanitize_key($assoc_args['format']) : 'table';
        $tool_id = isset($assoc_args['tool_id']) ? absint($assoc_args['tool_id']) : 0;
        $company_id = isset($assoc_args['company_id']) ? absint($assoc_args['company_id']) : 0;
        $results = array();

        foreach ($launch_ids as $launch_id) {
            $plan = kingy_ali_safe_sync_graph_plan_launch(
                $launch_id,
                array(
                    'overwrite' => $overwrite,
                    'skip_company_updates' => $skip_company_updates,
                    'tool_id' => $tool_id,
                    'company_id' => $company_id,
                )
            );

            if ($apply && $preserve_status && empty($plan['blockers'])) {
                $results[] = kingy_ali_safe_sync_graph_apply_plan($plan);
            } else {
                $results[] = array(
                    'applied' => false,
                    'dry_run' => !$apply,
                    'errors' => $apply && !$preserve_status ? array('preserve_status_required') : array(),
                    'plan' => $plan,
                );
            }
        }

        if ($format === 'json') {
            WP_CLI::line(wp_json_encode($results, JSON_PRETTY_PRINT));
            return;
        }

        foreach ($results as $result) {
            $plan = $result['plan'];
            WP_CLI::line(sprintf(
                'Launch %d: %s, diffs=%d, applied=%s',
                absint($plan['launch_id']),
                $plan['status'],
                count($plan['diffs']),
                !empty($result['applied']) ? 'yes' : 'no'
            ));
            foreach ($plan['blockers'] as $blocker) {
                WP_CLI::warning($blocker);
            }
        }
    }
}
