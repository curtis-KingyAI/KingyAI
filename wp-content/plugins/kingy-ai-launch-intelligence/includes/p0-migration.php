<?php
/**
 * Bounded, reversible P0 data repair for the Launch Command Center.
 *
 * This command intentionally supports only the 26 objects reviewed on the
 * Kingy.ai staging and production sites on 2026-07-16. Dry-run is the default.
 * It never creates posts, changes editorial fields, changes post status, or
 * replaces an object's full taxonomy set.
 *
 * Usage:
 *   wp kingy-ali p0-migrate --format=json
 *   wp kingy-ali p0-migrate --apply --backup-dir=/secure/backups
 *   wp kingy-ali p0-migrate --rollback=/secure/backups/.../manifest.json
 *
 * Production apply additionally requires a fresh, readable database backup:
 *   --db-backup=/secure/backups/kingy-before.sql.gz
 *
 * @package Kingy_AI_Launch_Intelligence
 */

if (!defined('ABSPATH')) {
    exit;
}

function kingy_ali_p0_migration_schema_version() {
    return 1;
}

function kingy_ali_p0_migration_name() {
    return 'launch-command-center-p0-funding-and-source-alias-20260716';
}

function kingy_ali_p0_funding_attribute_taxonomy() {
    return 'kingy_tool_attribute';
}

function kingy_ali_p0_funding_attribute_slug() {
    return 'funding-announced';
}

function kingy_ali_p0_legacy_announcement_meta_key() {
    return '_kingy_ali_official_launch_url';
}

function kingy_ali_p0_canonical_announcement_meta_key() {
    return '_kingy_ali_official_announcement_url';
}

/**
 * Exact reviewed records. Do not broaden this list from a query at runtime.
 *
 * `attribute_before` is the reviewed bad state. `attribute_after` is the only
 * accepted repaired state. Both are accepted during planning so the command is
 * idempotent and can report a clean no-op after a successful run.
 */
function kingy_ali_p0_expected_records() {
    $records = array(
        916491 => array(
            'post_type' => 'kingy_ai_launch',
            'status' => 'publish',
            'slug' => 'baseten-series-f-funding-2026-06-22',
            'title' => 'Baseten Series F funding announcement',
            'funding' => '$1.5 billion Series F; lead investor(s): Altimeter Capital, Conviction, Spark Capital, Sands Capital, Wellington Management',
            'funding_type' => true,
            'attribute_before' => false,
            'attribute_after' => true,
            'announcement_url' => 'https://www.businesswire.com/news/home/20260622645563/en/Baseten-Raises-%241.5-Billion-to-Power-the-Next-Era-of-AI-Inference',
        ),
        916494 => array(
            'post_type' => 'kingy_ai_launch',
            'status' => 'publish',
            'slug' => 'gradial-series-c-funding-2026-06-17',
            'title' => 'Gradial Series C funding announcement',
            'funding' => '$65 million Series C; lead investor(s): Insight Partners',
            'funding_type' => true,
            'attribute_before' => false,
            'attribute_after' => true,
            'announcement_url' => 'https://www.gradial.com/blog/gradial-65m-series-c',
        ),
        916497 => array(
            'post_type' => 'kingy_ai_launch',
            'status' => 'publish',
            'slug' => 'genspark-ai-series-b-extension-funding-2026-06-17',
            'title' => 'Genspark.ai Series B extension funding announcement',
            'funding' => '$100 million Series B extension; total Series B funding to $485 million Series B extension; lead investor(s): Not disclosed',
            'funding_type' => true,
            'attribute_before' => false,
            'attribute_after' => true,
            'announcement_url' => 'https://www.businesswire.com/news/home/20260617758937/en/Genspark.ai-Extends-Series-B-to-%24485M-at-%242.6B-Post-Money-Valuation-Appoints-Jamison-Powell-as-Chief-Revenue-Officer',
        ),
        916500 => array(
            'post_type' => 'kingy_ai_launch',
            'status' => 'publish',
            'slug' => 'kotoba-technologies-seed-extension-funding-2026-06-24',
            'title' => 'Kotoba Technologies Seed extension funding announcement',
            'funding' => 'Additional USD 10 million in seed funding Seed extension; lead investor(s): Kindred Ventures',
            'funding_type' => true,
            'attribute_before' => false,
            'attribute_after' => true,
            'announcement_url' => 'https://www.businesswire.com/news/home/20260624276012/en/Kotoba-Technologies-Raises-%2410-Million-in-Seed-Funding-to-Expand-Real-Time-Voice-AI-Platform-Across-East-Asia',
        ),
        916503 => array(
            'post_type' => 'kingy_ai_launch',
            'status' => 'publish',
            'slug' => 'hydra-host-series-a-funding-2026-06-15',
            'title' => 'Hydra Host Series A funding announcement',
            'funding' => '$100 million Series A; lead investor(s): Kindred Ventures',
            'funding_type' => true,
            'attribute_before' => false,
            'attribute_after' => true,
            'announcement_url' => 'https://hydrahost.com/blog/news/hydra-host-raises-100m-series-a/',
        ),
        916506 => array(
            'post_type' => 'kingy_ai_launch',
            'status' => 'publish',
            'slug' => 'coram-ai-series-b-funding-2026-06-11',
            'title' => 'Coram AI Series B funding announcement',
            'funding' => '$35 million Series B; lead investor(s): Ansa Capital, Battery Ventures',
            'funding_type' => true,
            'attribute_before' => false,
            'attribute_after' => true,
            'announcement_url' => 'https://www.coram.ai/post/coram-series-b-fundraise',
        ),
        916509 => array(
            'post_type' => 'kingy_ai_launch',
            'status' => 'publish',
            'slug' => 'hang-ten-systems-seed-funding-2026-06-24',
            'title' => 'Hang Ten Systems Seed funding announcement',
            'funding' => '$32 million Seed; lead investor(s): Mayfield',
            'funding_type' => true,
            'attribute_before' => false,
            'attribute_after' => true,
            'announcement_url' => 'https://www.businesswire.com/news/home/20260624847213/en/Hang-Ten-Systems-Raises-%2432-Million-to-Help-Enterprises-Succeed-With-AI',
        ),
        916995 => array(
            'post_type' => 'kingy_ai_launch',
            'status' => 'publish',
            'slug' => 'gemini-3-5-flash-computer-use-2026-06-24',
            'title' => 'Gemini 3.5 Flash Computer Use',
            'funding' => 'unknown',
            'funding_type' => false,
            'attribute_before' => true,
            'attribute_after' => false,
        ),
        916998 => array(
            'post_type' => 'kingy_ai_launch',
            'status' => 'publish',
            'slug' => 'microsoft-365-education-ai-teaching-capabilities-2026-06-24',
            'title' => 'Microsoft 365 Education AI Teaching Capabilities',
            'funding' => 'unknown',
            'funding_type' => false,
            'attribute_before' => true,
            'attribute_after' => false,
        ),
        917001 => array(
            'post_type' => 'kingy_ai_launch',
            'status' => 'publish',
            'slug' => 'kore-ai-agent-blueprint-language-2026-06-24',
            'title' => 'Kore.ai Agent Blueprint Language',
            'funding' => 'unknown',
            'funding_type' => false,
            'attribute_before' => true,
            'attribute_after' => false,
        ),
        917004 => array(
            'post_type' => 'kingy_ai_launch',
            'status' => 'publish',
            'slug' => 'mem0-plugin-for-pi-code-2026-06-24',
            'title' => 'Mem0 Plugin for Pi Code',
            'funding' => 'unknown',
            'funding_type' => false,
            'attribute_before' => true,
            'attribute_after' => false,
        ),
        917007 => array(
            'post_type' => 'kingy_ai_launch',
            'status' => 'publish',
            'slug' => 'builder-io-visual-plan-and-visual-recap-skills-2026-06-24',
            'title' => 'Builder.io Visual Plan and Visual Recap Skills',
            'funding' => 'unknown',
            'funding_type' => false,
            'attribute_before' => true,
            'attribute_after' => false,
        ),
        917010 => array(
            'post_type' => 'kingy_ai_launch',
            'status' => 'publish',
            'slug' => 'google-ads-api-v24-2-ai-transparency-features-2026-06-24',
            'title' => 'Google Ads API v24.2 AI Transparency Features',
            'funding' => 'unknown',
            'funding_type' => false,
            'attribute_before' => true,
            'attribute_after' => false,
        ),
        922084 => array(
            'post_type' => 'kingy_ai_launch',
            'status' => 'publish',
            'slug' => 'kimi-k2-7-code-in-github-copilot-business-and-enterprise-2026-07-07-2',
            'title' => 'Kimi K2.7 Code in GitHub Copilot Business and Enterprise',
            'funding' => 'unknown',
            'funding_type' => false,
            'attribute_before' => true,
            'attribute_after' => false,
        ),
        922088 => array(
            'post_type' => 'kingy_ai_launch',
            'status' => 'publish',
            'slug' => 'github-copilot-app-for-all-copilot-plans-2026-07-07-2',
            'title' => 'GitHub Copilot app for all Copilot plans',
            'funding' => 'unknown',
            'funding_type' => false,
            'attribute_before' => true,
            'attribute_after' => false,
        ),
        922092 => array(
            'post_type' => 'kingy_ai_launch',
            'status' => 'publish',
            'slug' => 'copilot-usage-metrics-review-cycle-fields-2026-07-07-2',
            'title' => 'Copilot usage metrics review-cycle fields',
            'funding' => 'unknown',
            'funding_type' => false,
            'attribute_before' => true,
            'attribute_after' => false,
        ),
        922096 => array(
            'post_type' => 'kingy_ai_launch',
            'status' => 'publish',
            'slug' => 'hugging-face-models-on-foundry-managed-compute-2026-07-08-2',
            'title' => 'Hugging Face Models on Foundry Managed Compute',
            'funding' => 'unknown',
            'funding_type' => false,
            'attribute_before' => true,
            'attribute_after' => false,
        ),
        922101 => array(
            'post_type' => 'kingy_ai_launch',
            'status' => 'publish',
            'slug' => 'katalyst-ai-2026-07-07-2',
            'title' => 'Katalyst AI',
            'funding' => 'unknown',
            'funding_type' => false,
            'attribute_before' => true,
            'attribute_after' => false,
        ),
        922106 => array(
            'post_type' => 'kingy_ai_launch',
            'status' => 'publish',
            'slug' => 'badge-peer-review-agent-2026-07-07-2',
            'title' => 'Badge peer review agent',
            'funding' => 'unknown',
            'funding_type' => false,
            'attribute_before' => true,
            'attribute_after' => false,
        ),
        922111 => array(
            'post_type' => 'kingy_ai_launch',
            'status' => 'publish',
            'slug' => 'copilot-cost-center-per-user-budgets-in-billing-ui-2026-07-07-2',
            'title' => 'Copilot cost center per-user budgets in billing UI',
            'funding' => 'unknown',
            'funding_type' => false,
            'attribute_before' => true,
            'attribute_after' => false,
        ),
        913120 => array(
            'post_type' => 'kingy_ai_company',
            'status' => 'publish',
            'slug' => 'github',
            'title' => 'GitHub',
            'funding' => 'unknown',
            'funding_type' => false,
            'attribute_before' => true,
            'attribute_after' => false,
        ),
        913126 => array(
            'post_type' => 'kingy_ai_company',
            'status' => 'publish',
            'slug' => 'anysphere',
            'title' => 'Anysphere',
            'funding' => 'unknown',
            'funding_type' => false,
            'attribute_before' => true,
            'attribute_after' => false,
        ),
        914283 => array(
            'post_type' => 'kingy_ai_company',
            'status' => 'publish',
            'slug' => 'vercel',
            'title' => 'Vercel',
            'funding' => 'unknown',
            'funding_type' => false,
            'attribute_before' => true,
            'attribute_after' => false,
        ),
        922098 => array(
            'post_type' => 'kingy_ai_company',
            'status' => 'publish',
            'slug' => 'hugging-face-and-microsoft',
            'title' => 'Hugging Face and Microsoft',
            'funding' => 'unknown',
            'funding_type' => false,
            'attribute_before' => true,
            'attribute_after' => false,
        ),
        922103 => array(
            'post_type' => 'kingy_ai_company',
            'status' => 'publish',
            'slug' => 'katalyst-ai',
            'title' => 'Katalyst AI',
            'funding' => 'unknown',
            'funding_type' => false,
            'attribute_before' => true,
            'attribute_after' => false,
        ),
        922108 => array(
            'post_type' => 'kingy_ai_company',
            'status' => 'publish',
            'slug' => 'badge',
            'title' => 'Badge',
            'funding' => 'unknown',
            'funding_type' => false,
            'attribute_before' => true,
            'attribute_after' => false,
        ),
    );

    ksort($records, SORT_NUMERIC);
    return $records;
}

function kingy_ali_p0_expected_record_ids() {
    return array_map('intval', array_keys(kingy_ali_p0_expected_records()));
}

function kingy_ali_p0_is_list($value) {
    if (!is_array($value)) {
        return false;
    }
    if (!$value) {
        return true;
    }
    return array_keys($value) === range(0, count($value) - 1);
}

function kingy_ali_p0_stable_value($value) {
    if (!is_array($value)) {
        return $value;
    }

    if (!kingy_ali_p0_is_list($value)) {
        ksort($value, SORT_STRING);
    }
    foreach ($value as $key => $item) {
        $value[$key] = kingy_ali_p0_stable_value($item);
    }
    return $value;
}

function kingy_ali_p0_json($value, $pretty = false) {
    $flags = JSON_UNESCAPED_SLASHES;
    if ($pretty) {
        $flags |= JSON_PRETTY_PRINT;
    }
    $value = kingy_ali_p0_stable_value($value);
    return function_exists('wp_json_encode') ? wp_json_encode($value, $flags) : json_encode($value, $flags);
}

function kingy_ali_p0_hash_value($value) {
    return hash('sha256', (string) kingy_ali_p0_json($value, false));
}

function kingy_ali_p0_meta_values($post_id, $meta_key) {
    $values = get_post_meta(absint($post_id), (string) $meta_key, false);
    return is_array($values) ? array_values($values) : array();
}

function kingy_ali_p0_term_slugs($post_id, $taxonomy) {
    $terms = wp_get_object_terms(absint($post_id), (string) $taxonomy, array('fields' => 'slugs'));
    if (is_wp_error($terms)) {
        return array();
    }
    $slugs = array_values(array_unique(array_map('strval', (array) $terms)));
    sort($slugs, SORT_STRING);
    return $slugs;
}

function kingy_ali_p0_has_funding_attribute($post_id) {
    return in_array(
        kingy_ali_p0_funding_attribute_slug(),
        kingy_ali_p0_term_slugs($post_id, kingy_ali_p0_funding_attribute_taxonomy()),
        true
    );
}

function kingy_ali_p0_has_funding_launch_type($post_id) {
    return in_array('funding', kingy_ali_p0_term_slugs($post_id, 'kingy_launch_type'), true);
}

function kingy_ali_p0_valid_absolute_http_url($value) {
    if (!is_scalar($value)) {
        return false;
    }
    $value = trim((string) $value);
    if ($value === '') {
        return false;
    }
    $url = function_exists('esc_url_raw') ? esc_url_raw($value, array('http', 'https')) : $value;
    $parts = function_exists('wp_parse_url') ? wp_parse_url($url) : parse_url($url);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
        return false;
    }
    return in_array(strtolower((string) $parts['scheme']), array('http', 'https'), true);
}

/**
 * Authorize the reviewed P0 migration inside the private Phase 2B clone only.
 *
 * Every condition is mandatory so an environment variable by itself can never
 * broaden the production/staging allowlist or authorize an HTTP/admin request.
 */
function kingy_ali_p0_is_authorized_isolated_clone($host = '') {
    $host = strtolower(trim((string) $host));
    return PHP_SAPI === 'cli'
        && defined('WP_CLI')
        && WP_CLI
        && $host === 'kingy-launch-clone.invalid'
        && getenv('KINGY_ALI_ALLOW_ISOLATED_CLONE') === '1'
        && getenv('KINGY_ALI_REQUIRE_PRODUCTION_FIXTURES') === '1'
        && getenv('KINGY_ALI_AUTHORIZE_CLONE_P0_MIGRATION') === 'YES';
}

function kingy_ali_p0_site_environment() {
    $site_url = function_exists('home_url') ? home_url('/') : '';
    $parts = function_exists('wp_parse_url') ? wp_parse_url($site_url) : parse_url($site_url);
    $host = is_array($parts) && !empty($parts['host']) ? strtolower((string) $parts['host']) : '';
    if (in_array($host, array('kingy.ai', 'www.kingy.ai'), true)) {
        return 'production';
    }
    if ($host === 'r8har1us7b-staging.onrocket.site') {
        return 'staging';
    }
    if (kingy_ali_p0_is_authorized_isolated_clone($host)) {
        return 'isolated_clone';
    }
    return 'unsupported';
}

function kingy_ali_p0_inspect_record($post_id, $expected) {
    $post_id = absint($post_id);
    $post = get_post($post_id);
    $blockers = array();
    $actions = array();

    if (!$post) {
        return array(
            'post_id' => $post_id,
            'title' => isset($expected['title']) ? $expected['title'] : '',
            'blockers' => array('missing_post'),
            'actions' => array(),
        );
    }

    $actual_type = isset($post->post_type) ? (string) $post->post_type : get_post_type($post_id);
    $actual_status = isset($post->post_status) ? (string) $post->post_status : get_post_status($post_id);
    $actual_slug = isset($post->post_name) ? (string) $post->post_name : '';
    $actual_title = isset($post->post_title) ? (string) $post->post_title : get_the_title($post_id);
    if ($actual_type !== $expected['post_type']) {
        $blockers[] = 'post_type_mismatch';
    }
    if ($actual_status !== $expected['status']) {
        $blockers[] = 'post_status_mismatch';
    }
    if ($actual_slug !== $expected['slug']) {
        $blockers[] = 'post_slug_mismatch';
    }
    if ($actual_title !== $expected['title']) {
        $blockers[] = 'post_title_mismatch';
    }

    $funding_values = kingy_ali_p0_meta_values($post_id, '_kingy_ali_funding');
    if (count($funding_values) !== 1 || (string) $funding_values[0] !== (string) $expected['funding']) {
        $blockers[] = 'funding_meta_mismatch';
    }

    $has_funding_type = $actual_type === 'kingy_ai_launch' ? kingy_ali_p0_has_funding_launch_type($post_id) : false;
    if ((bool) $has_funding_type !== (bool) $expected['funding_type']) {
        $blockers[] = 'funding_launch_type_mismatch';
    }

    $has_attribute = kingy_ali_p0_has_funding_attribute($post_id);
    if ($has_attribute !== (bool) $expected['attribute_before'] && $has_attribute !== (bool) $expected['attribute_after']) {
        $blockers[] = 'funding_attribute_state_invalid';
    } elseif ($has_attribute !== (bool) $expected['attribute_after']) {
        $actions[] = array(
            'type' => !empty($expected['attribute_after']) ? 'add_term' : 'remove_term',
            'taxonomy' => kingy_ali_p0_funding_attribute_taxonomy(),
            'slug' => kingy_ali_p0_funding_attribute_slug(),
        );
    }

    $legacy_values = kingy_ali_p0_meta_values($post_id, kingy_ali_p0_legacy_announcement_meta_key());
    $canonical_values = kingy_ali_p0_meta_values($post_id, kingy_ali_p0_canonical_announcement_meta_key());
    if (!empty($expected['announcement_url'])) {
        $expected_url = (string) $expected['announcement_url'];
        if (count($legacy_values) !== 1 || (string) $legacy_values[0] !== $expected_url || !kingy_ali_p0_valid_absolute_http_url($expected_url)) {
            $blockers[] = 'legacy_announcement_url_mismatch';
        }
        if (!$canonical_values) {
            $actions[] = array(
                'type' => 'add_meta',
                'meta_key' => kingy_ali_p0_canonical_announcement_meta_key(),
                'value' => $expected_url,
            );
        } elseif (count($canonical_values) !== 1 || (string) $canonical_values[0] !== $expected_url) {
            $blockers[] = 'canonical_announcement_url_conflict';
        }
    }

    return array(
        'post_id' => $post_id,
        'post_type' => $actual_type,
        'status' => $actual_status,
        'slug' => $actual_slug,
        'title' => $actual_title,
        'funding' => $funding_values,
        'funding_type' => (bool) $has_funding_type,
        'funding_attribute' => (bool) $has_attribute,
        'legacy_announcement' => $legacy_values,
        'canonical_announcement' => $canonical_values,
        'blockers' => array_values(array_unique($blockers)),
        'actions' => $actions,
    );
}

function kingy_ali_p0_build_plan() {
    $environment = kingy_ali_p0_site_environment();
    $blockers = array();
    if ($environment === 'unsupported') {
        $blockers[] = 'unsupported_site_environment';
    }

    $term = get_term_by('slug', kingy_ali_p0_funding_attribute_slug(), kingy_ali_p0_funding_attribute_taxonomy());
    if (!$term || is_wp_error($term)) {
        $blockers[] = 'funding_attribute_term_missing';
    }

    $records = array();
    $counts = array(
        'records' => 0,
        'pending_actions' => 0,
        'add_term' => 0,
        'remove_term' => 0,
        'add_meta' => 0,
        'blocked_records' => 0,
    );
    foreach (kingy_ali_p0_expected_records() as $post_id => $expected) {
        $inspection = kingy_ali_p0_inspect_record($post_id, $expected);
        $records[] = $inspection;
        $counts['records']++;
        if ($inspection['blockers']) {
            $counts['blocked_records']++;
            foreach ($inspection['blockers'] as $record_blocker) {
                $blockers[] = $post_id . ':' . $record_blocker;
            }
        }
        foreach ($inspection['actions'] as $action) {
            $counts['pending_actions']++;
            if (isset($counts[$action['type']])) {
                $counts[$action['type']]++;
            }
        }
    }

    if ($counts['records'] !== 26) {
        $blockers[] = 'allowlist_record_count_mismatch';
    }
    if ($counts['add_term'] > 7 || $counts['remove_term'] > 19 || $counts['add_meta'] > 7) {
        $blockers[] = 'planned_action_count_exceeds_reviewed_bound';
    }

    return array(
        'schema_version' => kingy_ali_p0_migration_schema_version(),
        'migration' => kingy_ali_p0_migration_name(),
        'generated_at_utc' => gmdate('c'),
        'environment' => $environment,
        'site_url' => function_exists('home_url') ? home_url('/') : '',
        'term' => array(
            'taxonomy' => kingy_ali_p0_funding_attribute_taxonomy(),
            'slug' => kingy_ali_p0_funding_attribute_slug(),
            'term_id' => $term && !is_wp_error($term) && isset($term->term_id) ? (int) $term->term_id : 0,
            'term_taxonomy_id' => $term && !is_wp_error($term) && isset($term->term_taxonomy_id) ? (int) $term->term_taxonomy_id : 0,
        ),
        'counts' => $counts,
        'blockers' => array_values(array_unique($blockers)),
        'records' => $records,
    );
}

function kingy_ali_p0_plan_is_full_reviewed_before_state($plan) {
    return empty($plan['blockers'])
        && isset($plan['counts'])
        && (int) $plan['counts']['records'] === 26
        && (int) $plan['counts']['add_term'] === 7
        && (int) $plan['counts']['remove_term'] === 19
        && (int) $plan['counts']['add_meta'] === 7
        && (int) $plan['counts']['pending_actions'] === 33;
}

function kingy_ali_p0_plan_is_noop($plan) {
    return empty($plan['blockers'])
        && !empty($plan['counts'])
        && (int) $plan['counts']['pending_actions'] === 0;
}

function kingy_ali_p0_database_meta_rows($post_id) {
    global $wpdb;

    if (!is_object($wpdb) || empty($wpdb->postmeta) || !method_exists($wpdb, 'prepare') || !method_exists($wpdb, 'get_results')) {
        return array();
    }

    $output_type = defined('ARRAY_A') ? ARRAY_A : 'ARRAY_A';
    $query = $wpdb->prepare(
        "SELECT meta_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d ORDER BY meta_id ASC",
        absint($post_id)
    );
    $rows = $wpdb->get_results($query, $output_type);
    if (!is_array($rows)) {
        return array();
    }

    $normalized = array();
    foreach ($rows as $row) {
        if (is_object($row)) {
            $row = get_object_vars($row);
        }
        if (!is_array($row)) {
            continue;
        }
        $normalized[] = array(
            'meta_id' => isset($row['meta_id']) ? (int) $row['meta_id'] : 0,
            'meta_key' => isset($row['meta_key']) ? (string) $row['meta_key'] : '',
            'meta_value' => isset($row['meta_value']) ? (string) $row['meta_value'] : '',
        );
    }
    return $normalized;
}

function kingy_ali_p0_taxonomy_rows($post_id, $post_type) {
    $taxonomies = get_object_taxonomies((string) $post_type, 'names');
    if (!is_array($taxonomies)) {
        $taxonomies = array();
    }
    $taxonomies = array_values(array_unique(array_map('strval', $taxonomies)));
    sort($taxonomies, SORT_STRING);

    $rows = array();
    foreach ($taxonomies as $taxonomy) {
        $terms = wp_get_object_terms(absint($post_id), $taxonomy, array('fields' => 'all'));
        if (is_wp_error($terms)) {
            continue;
        }
        foreach ((array) $terms as $term) {
            if (is_array($term)) {
                $term = (object) $term;
            }
            if (!is_object($term)) {
                continue;
            }
            $rows[] = array(
                'taxonomy' => $taxonomy,
                'term_id' => isset($term->term_id) ? (int) $term->term_id : 0,
                'term_taxonomy_id' => isset($term->term_taxonomy_id) ? (int) $term->term_taxonomy_id : 0,
                'slug' => isset($term->slug) ? (string) $term->slug : '',
                'name' => isset($term->name) ? (string) $term->name : '',
                'parent' => isset($term->parent) ? (int) $term->parent : 0,
            );
        }
    }

    usort(
        $rows,
        function ($left, $right) {
            $left_key = $left['taxonomy'] . "\0" . $left['slug'] . "\0" . $left['term_id'];
            $right_key = $right['taxonomy'] . "\0" . $right['slug'] . "\0" . $right['term_id'];
            return strcmp($left_key, $right_key);
        }
    );
    return $rows;
}

function kingy_ali_p0_snapshot_record($post_id) {
    $post_id = absint($post_id);
    $post = get_post($post_id);
    if (!$post) {
        return null;
    }

    $core = get_object_vars($post);
    ksort($core, SORT_STRING);
    $post_type = isset($post->post_type) ? (string) $post->post_type : get_post_type($post_id);
    $snapshot = array(
        'snapshot_schema_version' => 1,
        'captured_at_utc' => gmdate('c'),
        'post_id' => $post_id,
        'post_type' => $post_type,
        'featured_media' => function_exists('get_post_thumbnail_id') ? (int) get_post_thumbnail_id($post_id) : 0,
        'post' => $core,
        'meta_rows' => kingy_ali_p0_database_meta_rows($post_id),
        'taxonomy_rows' => kingy_ali_p0_taxonomy_rows($post_id, $post_type),
        'focused_state' => array(
            'funding_values' => kingy_ali_p0_meta_values($post_id, '_kingy_ali_funding'),
            'funding_attribute' => kingy_ali_p0_has_funding_attribute($post_id),
            'attribute_slugs' => kingy_ali_p0_term_slugs($post_id, kingy_ali_p0_funding_attribute_taxonomy()),
            'launch_type_slugs' => $post_type === 'kingy_ai_launch' ? kingy_ali_p0_term_slugs($post_id, 'kingy_launch_type') : array(),
            'legacy_announcement_values' => kingy_ali_p0_meta_values($post_id, kingy_ali_p0_legacy_announcement_meta_key()),
            'canonical_announcement_values' => kingy_ali_p0_meta_values($post_id, kingy_ali_p0_canonical_announcement_meta_key()),
        ),
    );
    $snapshot['record_hash'] = kingy_ali_p0_snapshot_hash($snapshot);
    $snapshot['protected_hash'] = kingy_ali_p0_snapshot_protected_hash($snapshot);
    return $snapshot;
}

function kingy_ali_p0_snapshot_without_volatile_fields($snapshot) {
    $copy = is_array($snapshot) ? $snapshot : array();
    unset($copy['captured_at_utc'], $copy['record_hash'], $copy['protected_hash']);
    return $copy;
}

function kingy_ali_p0_snapshot_hash($snapshot) {
    return kingy_ali_p0_hash_value(kingy_ali_p0_snapshot_without_volatile_fields($snapshot));
}

function kingy_ali_p0_snapshot_protected_value($snapshot) {
    $copy = kingy_ali_p0_snapshot_without_volatile_fields($snapshot);
    if (isset($copy['meta_rows']) && is_array($copy['meta_rows'])) {
        $copy['meta_rows'] = array_values(
            array_filter(
                $copy['meta_rows'],
                function ($row) {
                    return !is_array($row) || !isset($row['meta_key']) || $row['meta_key'] !== kingy_ali_p0_canonical_announcement_meta_key();
                }
            )
        );
    }
    if (isset($copy['taxonomy_rows']) && is_array($copy['taxonomy_rows'])) {
        $copy['taxonomy_rows'] = array_values(
            array_filter(
                $copy['taxonomy_rows'],
                function ($row) {
                    if (!is_array($row)) {
                        return true;
                    }
                    return !(
                        isset($row['taxonomy'], $row['slug'])
                        && $row['taxonomy'] === kingy_ali_p0_funding_attribute_taxonomy()
                        && $row['slug'] === kingy_ali_p0_funding_attribute_slug()
                    );
                }
            )
        );
    }
    if (isset($copy['focused_state'])) {
        unset(
            $copy['focused_state']['funding_attribute'],
            $copy['focused_state']['canonical_announcement_values']
        );
        if (isset($copy['focused_state']['attribute_slugs']) && is_array($copy['focused_state']['attribute_slugs'])) {
            $copy['focused_state']['attribute_slugs'] = array_values(
                array_diff($copy['focused_state']['attribute_slugs'], array(kingy_ali_p0_funding_attribute_slug()))
            );
        }
    }
    return $copy;
}

function kingy_ali_p0_snapshot_protected_hash($snapshot) {
    return kingy_ali_p0_hash_value(kingy_ali_p0_snapshot_protected_value($snapshot));
}

function kingy_ali_p0_normalize_path($path) {
    $path = (string) $path;
    return function_exists('wp_normalize_path') ? wp_normalize_path($path) : str_replace('\\', '/', $path);
}

function kingy_ali_p0_path_is_absolute($path) {
    $path = kingy_ali_p0_normalize_path($path);
    return $path !== '' && substr($path, 0, 1) === '/';
}

function kingy_ali_p0_path_is_within($path, $root) {
    $path = rtrim(kingy_ali_p0_normalize_path($path), '/');
    $root = rtrim(kingy_ali_p0_normalize_path($root), '/');
    if ($path === '' || $root === '') {
        return false;
    }
    return $path === $root || strpos($path . '/', $root . '/') === 0;
}

function kingy_ali_p0_validate_external_path($path, $must_exist = true) {
    $path = kingy_ali_p0_normalize_path($path);
    if (!kingy_ali_p0_path_is_absolute($path) || $path === '/') {
        return 'path_must_be_specific_and_absolute';
    }
    if ($must_exist && !file_exists($path)) {
        return 'path_does_not_exist';
    }
    $resolved = $must_exist ? realpath($path) : false;
    if ($resolved !== false) {
        $path = kingy_ali_p0_normalize_path($resolved);
    }
    if (preg_match('#/(public_html|htdocs)(/|$)#', $path)) {
        return 'path_must_be_outside_webroot';
    }
    $web_roots = array(ABSPATH);
    if (defined('WP_CONTENT_DIR')) {
        $web_roots[] = WP_CONTENT_DIR;
    }
    foreach ($web_roots as $web_root) {
        if (kingy_ali_p0_path_is_within($path, $web_root)) {
            return 'path_must_be_outside_webroot';
        }
    }
    return '';
}

function kingy_ali_p0_atomic_write($path, $contents) {
    $path = kingy_ali_p0_normalize_path($path);
    $directory = dirname($path);
    if (!is_dir($directory) || !is_writable($directory)) {
        return false;
    }
    $temporary = $path . '.tmp-' . substr(hash('sha256', uniqid('', true)), 0, 12);
    $bytes = file_put_contents($temporary, (string) $contents, LOCK_EX);
    if ($bytes === false) {
        return false;
    }
    @chmod($temporary, 0600);
    if (!rename($temporary, $path)) {
        @unlink($temporary);
        return false;
    }
    @chmod($path, 0600);
    return true;
}

function kingy_ali_p0_write_json_file($path, $value) {
    $json = kingy_ali_p0_json($value, true);
    return $json !== false && kingy_ali_p0_atomic_write($path, $json . "\n");
}

function kingy_ali_p0_relative_path($base, $path) {
    $base = rtrim(kingy_ali_p0_normalize_path($base), '/') . '/';
    $path = kingy_ali_p0_normalize_path($path);
    return strpos($path, $base) === 0 ? substr($path, strlen($base)) : '';
}

function kingy_ali_p0_file_descriptor($base, $path) {
    return array(
        'path' => kingy_ali_p0_relative_path($base, $path),
        'sha256' => is_file($path) ? hash_file('sha256', $path) : '',
        'bytes' => is_file($path) ? (int) filesize($path) : 0,
    );
}

function kingy_ali_p0_plugin_descriptor() {
    $plugin_file = defined('KINGY_ALI_PLUGIN_FILE') ? KINGY_ALI_PLUGIN_FILE : '';
    return array(
        'version' => defined('KINGY_ALI_VERSION') ? (string) KINGY_ALI_VERSION : '',
        'file' => $plugin_file ? basename($plugin_file) : '',
        'sha256' => $plugin_file && is_file($plugin_file) ? hash_file('sha256', $plugin_file) : '',
        'migration_file' => basename(__FILE__),
        'migration_sha256' => is_file(__FILE__) ? hash_file('sha256', __FILE__) : '',
    );
}

/**
 * Options that the atomic upgrade, launch mutation, generation, rewrite, and
 * cache paths may create or change during the bounded release window.
 */
function kingy_ali_p0_release_option_names() {
    return array(
        'kingy_ali_analytics_table_ready_version',
        'kingy_ali_company_directory_seed_version',
        'kingy_ali_company_profile_evidence_backfill_version',
        'kingy_ali_installed_version',
        'kingy_ali_pages_checked_version',
        'kingy_ali_page_install_results',
        'kingy_ali_ai_courses_page_created',
        'kingy_ali_ai_launch_scorecard_page_created',
        'kingy_ali_compare_ai_models_page_checked',
        'kingy_ali_compare_ai_models_page_created',
        'kingy_ali_model_landing_pages_checked',
        'kingy_ali_model_landing_page_install_results',
        'kingy_ali_model_static_compare_pages_checked',
        'kingy_ali_model_static_compare_page_install_results',
        'kingy_ali_launch_last_mutation_gmt',
        'kingy_ali_launch_collection_cache_generation',
        'kingy_ali_launch_data_generation',
        'kingy_ali_launch_feed_rewrite_schema',
        'kingy_ali_flush_rewrite_rules_deferred',
    );
}

function kingy_ali_p0_release_option_snapshot($option_name) {
    $option_name = (string) $option_name;
    $exists = false;
    $raw_value = '';
    $autoload = null;
    $source = 'wordpress_fallback';
    global $wpdb;

    if (isset($wpdb) && is_object($wpdb) && !empty($wpdb->options) && method_exists($wpdb, 'get_row') && method_exists($wpdb, 'prepare')) {
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT option_value, autoload FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", $option_name),
            ARRAY_A
        );
        if (is_array($row)) {
            $exists = true;
            $raw_value = (string) $row['option_value'];
            $autoload = (string) $row['autoload'];
        }
        $source = 'options_table_raw';
    } else {
        $sentinel = new stdClass();
        $value = get_option($option_name, $sentinel);
        if ($value !== $sentinel) {
            $exists = true;
            $raw_value = function_exists('maybe_serialize') ? maybe_serialize($value) : serialize($value);
        }
    }

    return array(
        'option_name' => $option_name,
        'exists' => $exists,
        'value_base64' => $exists ? base64_encode($raw_value) : '',
        'value_sha256' => $exists ? hash('sha256', $raw_value) : '',
        'autoload' => $exists ? $autoload : null,
        'source' => $source,
    );
}

function kingy_ali_p0_release_options_snapshot() {
    $options = array();
    foreach (kingy_ali_p0_release_option_names() as $option_name) {
        $options[$option_name] = kingy_ali_p0_release_option_snapshot($option_name);
    }
    return array(
        'schema_version' => 1,
        'captured_at_utc' => gmdate('c'),
        'options' => $options,
        'state_hash' => kingy_ali_p0_hash_value($options),
    );
}

function kingy_ali_p0_release_options_snapshot_is_valid($snapshot) {
    if (!is_array($snapshot) || empty($snapshot['options']) || empty($snapshot['state_hash'])) {
        return false;
    }
    $names = array_keys((array) $snapshot['options']);
    $expected = kingy_ali_p0_release_option_names();
    sort($names, SORT_STRING);
    sort($expected, SORT_STRING);
    if ($names !== $expected || !hash_equals((string) $snapshot['state_hash'], kingy_ali_p0_hash_value($snapshot['options']))) {
        return false;
    }
    foreach ($snapshot['options'] as $name => $option) {
        if (!is_array($option) || (string) ($option['option_name'] ?? '') !== (string) $name || !array_key_exists('exists', $option)) {
            return false;
        }
        if (!empty($option['exists'])) {
            $raw = base64_decode((string) ($option['value_base64'] ?? ''), true);
            if ($raw === false || empty($option['value_sha256']) || !hash_equals((string) $option['value_sha256'], hash('sha256', $raw))) {
                return false;
            }
        }
    }
    return true;
}

function kingy_ali_p0_restore_release_options($snapshot) {
    if (!kingy_ali_p0_release_options_snapshot_is_valid($snapshot)) {
        return array('ok' => false, 'error' => 'release_option_snapshot_invalid');
    }

    global $wpdb;
    $errors = array();
    foreach ($snapshot['options'] as $option_name => $option) {
        $exists = !empty($option['exists']);
        $raw_value = $exists ? base64_decode((string) $option['value_base64'], true) : '';
        if ($exists && $raw_value === false) {
            $errors[] = $option_name . ':base64_decode_failed';
            break;
        }

        if (isset($wpdb) && is_object($wpdb) && !empty($wpdb->options) && method_exists($wpdb, 'query') && method_exists($wpdb, 'prepare')) {
            if ($exists) {
                $autoload = is_scalar($option['autoload']) && (string) $option['autoload'] !== '' ? (string) $option['autoload'] : 'no';
                $sql = $wpdb->prepare(
                    "INSERT INTO {$wpdb->options} (option_name, option_value, autoload)
                     VALUES (%s, %s, %s)
                     ON DUPLICATE KEY UPDATE option_value = VALUES(option_value), autoload = VALUES(autoload)",
                    $option_name,
                    $raw_value,
                    $autoload
                );
            } else {
                $sql = $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s", $option_name);
            }
            if ($wpdb->query($sql) === false) {
                $errors[] = $option_name . ':database_restore_failed';
                break;
            }
            if (function_exists('wp_cache_delete')) {
                wp_cache_delete($option_name, 'options');
            }
            continue;
        }

        if ($exists) {
            $value = function_exists('maybe_unserialize') ? maybe_unserialize($raw_value) : unserialize($raw_value);
            update_option($option_name, $value, false);
        } else {
            delete_option($option_name);
        }
    }

    $after = kingy_ali_p0_release_options_snapshot();
    $ok = !$errors && hash_equals((string) $snapshot['state_hash'], (string) $after['state_hash']);
    return array('ok' => $ok, 'errors' => $errors, 'state_hash' => $after['state_hash']);
}

function kingy_ali_p0_prepare_backup($base_directory, $plan, $db_backup = '') {
    $base_directory = rtrim(kingy_ali_p0_normalize_path($base_directory), '/');
    $path_error = kingy_ali_p0_validate_external_path($base_directory, true);
    if ($path_error !== '') {
        return array('ok' => false, 'error' => 'backup_dir_' . $path_error);
    }
    if (!is_dir($base_directory) || !is_writable($base_directory)) {
        return array('ok' => false, 'error' => 'backup_dir_not_writable');
    }

    $suffix = substr(hash('sha256', uniqid('', true)), 0, 10);
    $run_directory = $base_directory . '/kingy-ali-p0-' . gmdate('Ymd\THis\Z') . '-' . $suffix;
    if (!mkdir($run_directory, 0700, false)) {
        return array('ok' => false, 'error' => 'backup_run_dir_create_failed');
    }
    @chmod($run_directory, 0700);
    $records_directory = $run_directory . '/records';
    if (!mkdir($records_directory, 0700, false)) {
        return array('ok' => false, 'error' => 'backup_records_dir_create_failed', 'run_dir' => $run_directory);
    }
    @chmod($records_directory, 0700);

    $plan_path = $run_directory . '/plan.json';
    if (!kingy_ali_p0_write_json_file($plan_path, $plan)) {
        return array('ok' => false, 'error' => 'plan_write_failed', 'run_dir' => $run_directory);
    }

    $manifest_records = array();
    foreach (kingy_ali_p0_expected_records() as $post_id => $expected) {
        $snapshot = kingy_ali_p0_snapshot_record($post_id);
        if (!$snapshot) {
            return array('ok' => false, 'error' => 'snapshot_failed:' . $post_id, 'run_dir' => $run_directory);
        }
        $file_name = $expected['post_type'] . '-' . $post_id . '.before.json';
        $snapshot_path = $records_directory . '/' . $file_name;
        if (!kingy_ali_p0_write_json_file($snapshot_path, $snapshot)) {
            return array('ok' => false, 'error' => 'snapshot_write_failed:' . $post_id, 'run_dir' => $run_directory);
        }
        $manifest_records[(string) $post_id] = array(
            'post_id' => (int) $post_id,
            'post_type' => $expected['post_type'],
            'title' => $expected['title'],
            'before' => kingy_ali_p0_file_descriptor($run_directory, $snapshot_path),
            'before_record_hash' => $snapshot['record_hash'],
            'before_protected_hash' => $snapshot['protected_hash'],
            'after' => null,
            'after_record_hash' => '',
            'applied_actions' => array(),
        );
    }

    $db_descriptor = null;
    if ($db_backup !== '') {
        $db_backup = kingy_ali_p0_normalize_path($db_backup);
        $db_error = kingy_ali_p0_validate_external_path($db_backup, true);
        if ($db_error !== '' || !is_file($db_backup) || !is_readable($db_backup)) {
            return array('ok' => false, 'error' => 'db_backup_invalid:' . ($db_error ? $db_error : 'not_readable_file'), 'run_dir' => $run_directory);
        }
        $db_descriptor = array(
            'path' => $db_backup,
            'sha256' => hash_file('sha256', $db_backup),
            'bytes' => (int) filesize($db_backup),
        );
    }

    $manifest = array(
        'schema_version' => kingy_ali_p0_migration_schema_version(),
        'migration' => kingy_ali_p0_migration_name(),
        'migration_id' => basename($run_directory),
        'created_at_utc' => gmdate('c'),
        'updated_at_utc' => gmdate('c'),
        'status' => 'prepared',
        'environment' => $plan['environment'],
        'site_url' => $plan['site_url'],
        'wordpress_version' => function_exists('get_bloginfo') ? get_bloginfo('version') : '',
        'php_version' => PHP_VERSION,
        'plugin' => kingy_ali_p0_plugin_descriptor(),
        'database_backup' => $db_descriptor,
        'selection' => array(
            'exact_ids' => kingy_ali_p0_expected_record_ids(),
            'expected_record_count' => 26,
            'expected_full_actions' => array('add_term' => 7, 'remove_term' => 19, 'add_meta' => 7),
        ),
        'term' => $plan['term'],
        'plan' => kingy_ali_p0_file_descriptor($run_directory, $plan_path),
        'plan_hash' => kingy_ali_p0_hash_value($plan),
        'records' => $manifest_records,
        'release_options' => array(
            'before' => kingy_ali_p0_release_options_snapshot(),
            'after' => null,
            'restored' => null,
        ),
        'cache_suppression' => array(),
        'apply_log' => null,
        'errors' => array(),
    );

    $manifest_path = $run_directory . '/manifest.json';
    if (!kingy_ali_p0_write_manifest($manifest_path, $manifest)) {
        return array('ok' => false, 'error' => 'manifest_write_failed', 'run_dir' => $run_directory);
    }
    if (!kingy_ali_p0_write_checksums($run_directory, $manifest)) {
        return array('ok' => false, 'error' => 'checksum_write_failed', 'run_dir' => $run_directory);
    }

    return array(
        'ok' => true,
        'run_dir' => $run_directory,
        'manifest_path' => $manifest_path,
        'manifest' => $manifest,
    );
}

function kingy_ali_p0_write_manifest($manifest_path, $manifest) {
    $manifest['updated_at_utc'] = gmdate('c');
    if (!kingy_ali_p0_write_json_file($manifest_path, $manifest)) {
        return false;
    }
    $sidecar = hash_file('sha256', $manifest_path) . '  manifest.json' . "\n";
    return kingy_ali_p0_atomic_write(dirname($manifest_path) . '/manifest.sha256', $sidecar);
}

function kingy_ali_p0_write_checksums($run_directory, $manifest) {
    $paths = array();
    if (!empty($manifest['plan']['path'])) {
        $paths[] = $manifest['plan']['path'];
    }
    foreach ((array) $manifest['records'] as $record) {
        foreach (array('before', 'after') as $state) {
            if (!empty($record[$state]['path'])) {
                $paths[] = $record[$state]['path'];
            }
        }
    }
    if (!empty($manifest['apply_log']['path'])) {
        $paths[] = $manifest['apply_log']['path'];
    }
    if (!empty($manifest['rollback_log']['path'])) {
        $paths[] = $manifest['rollback_log']['path'];
    }
    $paths = array_values(array_unique($paths));
    sort($paths, SORT_STRING);
    $lines = array();
    foreach ($paths as $relative) {
        if (!kingy_ali_p0_safe_relative_file($run_directory, $relative)) {
            return false;
        }
        $path = rtrim($run_directory, '/') . '/' . $relative;
        if (!is_file($path)) {
            return false;
        }
        $lines[] = hash_file('sha256', $path) . '  ' . $relative;
    }
    return kingy_ali_p0_atomic_write($run_directory . '/SHA256SUMS', implode("\n", $lines) . "\n");
}

function kingy_ali_p0_safe_relative_file($run_directory, $relative) {
    $relative = kingy_ali_p0_normalize_path($relative);
    if ($relative === '' || substr($relative, 0, 1) === '/' || strpos($relative, '..') !== false) {
        return false;
    }
    $full = rtrim(kingy_ali_p0_normalize_path($run_directory), '/') . '/' . $relative;
    return kingy_ali_p0_path_is_within($full, $run_directory);
}

function kingy_ali_p0_append_log($path, $row) {
    $line = kingy_ali_p0_json($row, false) . "\n";
    $bytes = file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    if ($bytes === false) {
        return false;
    }
    @chmod($path, 0600);
    return true;
}

function kingy_ali_p0_record_actions_from_plan($plan, $post_id) {
    foreach ((array) $plan['records'] as $record) {
        if ((int) $record['post_id'] === (int) $post_id) {
            return isset($record['actions']) && is_array($record['actions']) ? $record['actions'] : array();
        }
    }
    return array();
}

function kingy_ali_p0_wp_error_message($result) {
    if (is_wp_error($result)) {
        return $result->get_error_code() . ':' . $result->get_error_message();
    }
    return '';
}

function kingy_ali_p0_apply_action($post_id, $action) {
    $post_id = absint($post_id);
    $type = isset($action['type']) ? (string) $action['type'] : '';
    if ($type === 'add_term') {
        $result = wp_add_object_terms($post_id, $action['slug'], $action['taxonomy']);
        if (is_wp_error($result)) {
            return array('ok' => false, 'error' => kingy_ali_p0_wp_error_message($result));
        }
        return array('ok' => true, 'error' => '');
    }
    if ($type === 'remove_term') {
        $result = wp_remove_object_terms($post_id, $action['slug'], $action['taxonomy']);
        if (is_wp_error($result)) {
            return array('ok' => false, 'error' => kingy_ali_p0_wp_error_message($result));
        }
        return array('ok' => true, 'error' => '');
    }
    if ($type === 'add_meta') {
        $added = add_post_meta($post_id, $action['meta_key'], $action['value'], true);
        if ($added === false) {
            return array('ok' => false, 'error' => 'add_post_meta_failed_or_conflicted');
        }
        return array('ok' => true, 'error' => '');
    }
    return array('ok' => false, 'error' => 'unsupported_action');
}

function kingy_ali_p0_record_is_desired_after_state($post_id, $expected) {
    $inspection = kingy_ali_p0_inspect_record($post_id, $expected);
    return empty($inspection['blockers']) && empty($inspection['actions']);
}

function kingy_ali_p0_save_after_snapshot(&$manifest, $manifest_path, $post_id, $snapshot, $applied_actions) {
    $run_directory = dirname($manifest_path);
    $expected = kingy_ali_p0_expected_records();
    if (!isset($expected[$post_id]) || !isset($manifest['records'][(string) $post_id])) {
        return false;
    }
    $file_name = $expected[$post_id]['post_type'] . '-' . $post_id . '.after.json';
    $snapshot_path = $run_directory . '/records/' . $file_name;
    if (!kingy_ali_p0_write_json_file($snapshot_path, $snapshot)) {
        return false;
    }
    $manifest['records'][(string) $post_id]['after'] = kingy_ali_p0_file_descriptor($run_directory, $snapshot_path);
    $manifest['records'][(string) $post_id]['after_record_hash'] = $snapshot['record_hash'];
    $manifest['records'][(string) $post_id]['applied_actions'] = array_values($applied_actions);
    return kingy_ali_p0_write_manifest($manifest_path, $manifest);
}

function kingy_ali_p0_apply_plan($plan, $backup) {
    $manifest_path = $backup['manifest_path'];
    $manifest = $backup['manifest'];
    $run_directory = $backup['run_dir'];
    $log_path = $run_directory . '/apply.ndjson';
    if (!kingy_ali_p0_atomic_write($log_path, '')) {
        return array('ok' => false, 'error' => 'apply_log_create_failed', 'manifest_path' => $manifest_path);
    }
    $manifest['status'] = 'applying';
    $manifest['apply_log'] = kingy_ali_p0_file_descriptor($run_directory, $log_path);
    if (!kingy_ali_p0_write_manifest($manifest_path, $manifest)) {
        return array('ok' => false, 'error' => 'manifest_applying_write_failed', 'manifest_path' => $manifest_path);
    }

    if (function_exists('kingy_ali_launch_release_mode_state')) {
        kingy_ali_launch_release_mode_state('reset');
    }
    if (function_exists('kingy_ali_begin_launch_release_mode')) {
        kingy_ali_begin_launch_release_mode('p0_apply');
    }

    $expected_records = kingy_ali_p0_expected_records();
    $applied_count = 0;
    $errors = array();
    foreach ($expected_records as $post_id => $expected) {
        $actions = kingy_ali_p0_record_actions_from_plan($plan, $post_id);
        $manifest_record = $manifest['records'][(string) $post_id];
        $before_path = $run_directory . '/' . $manifest_record['before']['path'];
        $before_snapshot = kingy_ali_p0_read_verified_json_file($before_path, $manifest_record['before']['sha256']);
        if (!is_array($before_snapshot)) {
            $errors[] = $post_id . ':before_snapshot_verification_failed';
            break;
        }

        $current = kingy_ali_p0_snapshot_record($post_id);
        if (!$current || !hash_equals((string) $before_snapshot['record_hash'], (string) $current['record_hash'])) {
            $errors[] = $post_id . ':pre_apply_record_conflict';
            break;
        }

        $record_applied_actions = array();
        foreach ($actions as $action) {
            $result = kingy_ali_p0_apply_action($post_id, $action);
            $log_row = array(
                'time_utc' => gmdate('c'),
                'phase' => 'apply_action',
                'post_id' => (int) $post_id,
                'action' => $action,
                'ok' => !empty($result['ok']),
                'error' => isset($result['error']) ? $result['error'] : '',
            );
            if (!kingy_ali_p0_append_log($log_path, $log_row)) {
                $errors[] = $post_id . ':apply_log_write_failed';
                break 2;
            }
            if (empty($result['ok'])) {
                $errors[] = $post_id . ':' . $result['error'];
                break 2;
            }
            $record_applied_actions[] = $action;
            $applied_count++;
        }

        $after = kingy_ali_p0_snapshot_record($post_id);
        if (!$after) {
            $errors[] = $post_id . ':after_snapshot_failed';
            break;
        }
        if (!hash_equals((string) $before_snapshot['protected_hash'], (string) $after['protected_hash'])) {
            $errors[] = $post_id . ':protected_state_changed';
        }
        if (!kingy_ali_p0_record_is_desired_after_state($post_id, $expected)) {
            $errors[] = $post_id . ':desired_after_state_not_reached';
        }
        if (!kingy_ali_p0_save_after_snapshot($manifest, $manifest_path, $post_id, $after, $record_applied_actions)) {
            $errors[] = $post_id . ':after_snapshot_or_manifest_write_failed';
        }
        if ($errors) {
            break;
        }
    }

    /*
     * Snapshot untouched records as after=before. This makes rollback of a
     * partial run deterministic and lets it distinguish no-op records from
     * records changed after the command stopped.
     */
    foreach ($expected_records as $post_id => $expected) {
        unset($expected);
        if (!empty($manifest['records'][(string) $post_id]['after'])) {
            continue;
        }
        $manifest_record = $manifest['records'][(string) $post_id];
        $before_path = $run_directory . '/' . $manifest_record['before']['path'];
        $before_snapshot = kingy_ali_p0_read_verified_json_file($before_path, $manifest_record['before']['sha256']);
        $snapshot = kingy_ali_p0_snapshot_record($post_id);
        if (!$before_snapshot || !$snapshot) {
            $errors[] = $post_id . ':partial_after_snapshot_failed';
            continue;
        }
        if (!hash_equals((string) $before_snapshot['protected_hash'], (string) $snapshot['protected_hash'])) {
            $errors[] = $post_id . ':partial_after_protected_state_conflict';
            continue;
        }
        if (!kingy_ali_p0_save_after_snapshot($manifest, $manifest_path, $post_id, $snapshot, array())) {
            $errors[] = $post_id . ':partial_after_manifest_write_failed';
        }
    }

    $flush_result = function_exists('kingy_ali_flush_launch_collection_purge_queue')
        ? kingy_ali_flush_launch_collection_purge_queue()
        : null;
    $cache_audit = function_exists('kingy_ali_launch_cache_adapter_audit')
        ? kingy_ali_launch_cache_adapter_audit()
        : array('release_mode_active' => false, 'adapter_invocation_count' => null);
    if ((int) ($cache_audit['adapter_invocation_count'] ?? 0) !== 0) {
        $errors[] = 'cache_adapter_invoked_in_release_mode';
    }
    $manifest['release_options']['after'] = kingy_ali_p0_release_options_snapshot();
    $manifest['cache_suppression']['apply'] = array('flush_result' => $flush_result, 'audit' => $cache_audit);
    $manifest['apply_log'] = kingy_ali_p0_file_descriptor($run_directory, $log_path);
    $manifest['status'] = $errors ? 'partial' : 'applied';
    $manifest['errors'] = array_values(array_unique($errors));
    $manifest['applied_action_count'] = $applied_count;
    $manifest['completed_at_utc'] = gmdate('c');
    kingy_ali_p0_write_manifest($manifest_path, $manifest);
    kingy_ali_p0_write_checksums($run_directory, $manifest);

    return array(
        'ok' => !$errors,
        'status' => $manifest['status'],
        'applied_action_count' => $applied_count,
        'errors' => $errors,
        'manifest_path' => $manifest_path,
        'run_dir' => $run_directory,
        'cache_suppression' => $manifest['cache_suppression']['apply'],
        'release_options' => array(
            'before_state_hash' => $manifest['release_options']['before']['state_hash'],
            'after_state_hash' => $manifest['release_options']['after']['state_hash'],
        ),
    );
}

function kingy_ali_p0_read_verified_json_file($path, $expected_sha256) {
    if (!is_file($path) || !is_readable($path)) {
        return null;
    }
    $actual_sha256 = hash_file('sha256', $path);
    if (!$expected_sha256 || !hash_equals((string) $expected_sha256, (string) $actual_sha256)) {
        return null;
    }
    $decoded = json_decode((string) file_get_contents($path), true);
    return is_array($decoded) ? $decoded : null;
}

function kingy_ali_p0_verify_manifest_sidecar($manifest_path) {
    $sidecar_path = dirname($manifest_path) . '/manifest.sha256';
    if (!is_file($sidecar_path) || !is_readable($sidecar_path)) {
        return false;
    }
    $line = trim((string) file_get_contents($sidecar_path));
    $parts = preg_split('/\s+/', $line, 2);
    return isset($parts[0])
        && preg_match('/^[a-f0-9]{64}$/', $parts[0])
        && hash_equals($parts[0], hash_file('sha256', $manifest_path));
}

function kingy_ali_p0_load_manifest($manifest_path) {
    $manifest_path = kingy_ali_p0_normalize_path($manifest_path);
    $path_error = kingy_ali_p0_validate_external_path($manifest_path, true);
    if ($path_error !== '' || !is_file($manifest_path) || !is_readable($manifest_path)) {
        return array('ok' => false, 'error' => 'manifest_path_invalid:' . ($path_error ? $path_error : 'not_readable_file'));
    }
    if (!kingy_ali_p0_verify_manifest_sidecar($manifest_path)) {
        return array('ok' => false, 'error' => 'manifest_hash_verification_failed');
    }
    $manifest = json_decode((string) file_get_contents($manifest_path), true);
    if (!is_array($manifest)) {
        return array('ok' => false, 'error' => 'manifest_json_invalid');
    }
    if ((int) $manifest['schema_version'] !== kingy_ali_p0_migration_schema_version() || $manifest['migration'] !== kingy_ali_p0_migration_name()) {
        return array('ok' => false, 'error' => 'manifest_schema_or_migration_mismatch');
    }
    $manifest_ids = isset($manifest['selection']['exact_ids']) ? array_map('intval', (array) $manifest['selection']['exact_ids']) : array();
    sort($manifest_ids, SORT_NUMERIC);
    $expected_ids = kingy_ali_p0_expected_record_ids();
    sort($expected_ids, SORT_NUMERIC);
    if ($manifest_ids !== $expected_ids || count((array) $manifest['records']) !== 26) {
        return array('ok' => false, 'error' => 'manifest_allowlist_mismatch');
    }
    if ($manifest['environment'] !== kingy_ali_p0_site_environment() || $manifest['site_url'] !== home_url('/')) {
        return array('ok' => false, 'error' => 'manifest_site_mismatch');
    }
    $plugin = kingy_ali_p0_plugin_descriptor();
    if (
        empty($manifest['plugin']['sha256'])
        || empty($plugin['sha256'])
        || !hash_equals($manifest['plugin']['sha256'], $plugin['sha256'])
        || empty($manifest['plugin']['migration_sha256'])
        || empty($plugin['migration_sha256'])
        || !hash_equals($manifest['plugin']['migration_sha256'], $plugin['migration_sha256'])
    ) {
        return array('ok' => false, 'error' => 'plugin_hash_drift');
    }

    $run_directory = dirname($manifest_path);
    if (empty($manifest['plan']['path']) || !kingy_ali_p0_safe_relative_file($run_directory, $manifest['plan']['path'])) {
        return array('ok' => false, 'error' => 'manifest_plan_path_invalid');
    }
    $plan_path = $run_directory . '/' . $manifest['plan']['path'];
    $verified_plan = kingy_ali_p0_read_verified_json_file($plan_path, $manifest['plan']['sha256']);
    if (!$verified_plan || empty($manifest['plan_hash']) || !hash_equals((string) $manifest['plan_hash'], kingy_ali_p0_hash_value($verified_plan))) {
        return array('ok' => false, 'error' => 'manifest_plan_hash_invalid');
    }
    foreach (array('apply_log', 'rollback_log') as $log_key) {
        if (empty($manifest[$log_key])) {
            continue;
        }
        $descriptor = $manifest[$log_key];
        if (empty($descriptor['path']) || !kingy_ali_p0_safe_relative_file($run_directory, $descriptor['path'])) {
            return array('ok' => false, 'error' => 'manifest_' . $log_key . '_path_invalid');
        }
        $log_path = $run_directory . '/' . $descriptor['path'];
        if (!is_file($log_path) || empty($descriptor['sha256']) || !hash_equals((string) $descriptor['sha256'], hash_file('sha256', $log_path))) {
            return array('ok' => false, 'error' => 'manifest_' . $log_key . '_hash_invalid');
        }
    }
    foreach ((array) $manifest['records'] as $record) {
        foreach (array('before', 'after') as $state) {
            if (empty($record[$state])) {
                continue;
            }
            if (!kingy_ali_p0_safe_relative_file($run_directory, $record[$state]['path'])) {
                return array('ok' => false, 'error' => 'manifest_record_path_invalid');
            }
            $path = $run_directory . '/' . $record[$state]['path'];
            if (!kingy_ali_p0_read_verified_json_file($path, $record[$state]['sha256'])) {
                return array('ok' => false, 'error' => 'manifest_record_hash_invalid:' . $record['post_id'] . ':' . $state);
            }
        }
    }
    if (
        empty($manifest['release_options']['before'])
        || empty($manifest['release_options']['after'])
        || !kingy_ali_p0_release_options_snapshot_is_valid($manifest['release_options']['before'])
        || !kingy_ali_p0_release_options_snapshot_is_valid($manifest['release_options']['after'])
    ) {
        return array('ok' => false, 'error' => 'manifest_release_options_invalid');
    }
    return array('ok' => true, 'manifest' => $manifest, 'manifest_path' => $manifest_path, 'run_dir' => $run_directory);
}

function kingy_ali_p0_restore_record_targets($post_id, $before_snapshot, $expected) {
    $post_id = absint($post_id);
    $desired_attribute = !empty($before_snapshot['focused_state']['funding_attribute']);
    $has_attribute = kingy_ali_p0_has_funding_attribute($post_id);
    if ($has_attribute !== $desired_attribute) {
        $action = array(
            'type' => $desired_attribute ? 'add_term' : 'remove_term',
            'taxonomy' => kingy_ali_p0_funding_attribute_taxonomy(),
            'slug' => kingy_ali_p0_funding_attribute_slug(),
        );
        $result = kingy_ali_p0_apply_action($post_id, $action);
        if (empty($result['ok'])) {
            return array('ok' => false, 'error' => 'term_restore_failed:' . $result['error']);
        }
    }

    if (!empty($expected['announcement_url'])) {
        $before_values = isset($before_snapshot['focused_state']['canonical_announcement_values'])
            ? (array) $before_snapshot['focused_state']['canonical_announcement_values']
            : array();
        if ($before_values) {
            return array('ok' => false, 'error' => 'unexpected_nonempty_before_canonical_alias');
        }
        delete_post_meta($post_id, kingy_ali_p0_canonical_announcement_meta_key());
    }

    return array('ok' => true, 'error' => '');
}

/**
 * Restore one reviewed record while temporarily suspending the runtime funding
 * invariant. Without this narrow scope, deleting Funding Announced from one of
 * the seven genuine Funding launches is immediately undone by the taxonomy
 * deletion hook and an exact rollback becomes impossible.
 */
function kingy_ali_p0_restore_record_targets_with_invariant_suspended($post_id, $before_snapshot, $expected) {
    $suspended = function_exists('kingy_ali_suspend_funding_attribute_invariant')
        && function_exists('kingy_ali_resume_funding_attribute_invariant');
    if ($suspended) {
        kingy_ali_suspend_funding_attribute_invariant();
    }

    try {
        return kingy_ali_p0_restore_record_targets($post_id, $before_snapshot, $expected);
    } finally {
        if ($suspended) {
            kingy_ali_resume_funding_attribute_invariant();
        }
    }
}

function kingy_ali_p0_rollback_manifest($loaded, $apply) {
    $manifest = $loaded['manifest'];
    $manifest_path = $loaded['manifest_path'];
    $run_directory = $loaded['run_dir'];
    $expected_records = kingy_ali_p0_expected_records();
    $plan = array(
        'migration' => kingy_ali_p0_migration_name(),
        'mode' => $apply ? 'rollback_apply' : 'rollback_dry_run',
        'generated_at_utc' => gmdate('c'),
        'records' => array(),
        'blockers' => array(),
        'pending_actions' => 0,
        'release_options' => array('state' => '', 'blockers' => array()),
    );

    $current_options = kingy_ali_p0_release_options_snapshot();
    $before_options = $manifest['release_options']['before'];
    $after_options = $manifest['release_options']['after'];
    if (hash_equals((string) $before_options['state_hash'], (string) $current_options['state_hash'])) {
        $plan['release_options']['state'] = 'already_rolled_back';
    } elseif (hash_equals((string) $after_options['state_hash'], (string) $current_options['state_hash'])) {
        $plan['release_options']['state'] = 'ready';
    } else {
        $plan['release_options']['state'] = 'conflict';
        $plan['release_options']['blockers'][] = 'current_option_state_differs_from_recorded_after';
        $plan['blockers'][] = 'release_options:current_state_differs_from_recorded_after';
    }

    foreach ($expected_records as $post_id => $expected) {
        $record_manifest = $manifest['records'][(string) $post_id];
        $before_path = $run_directory . '/' . $record_manifest['before']['path'];
        $before = kingy_ali_p0_read_verified_json_file($before_path, $record_manifest['before']['sha256']);
        $after = null;
        if (!empty($record_manifest['after'])) {
            $after_path = $run_directory . '/' . $record_manifest['after']['path'];
            $after = kingy_ali_p0_read_verified_json_file($after_path, $record_manifest['after']['sha256']);
        }
        $current = kingy_ali_p0_snapshot_record($post_id);
        $row = array(
            'post_id' => (int) $post_id,
            'title' => $expected['title'],
            'state' => '',
            'actions' => array(),
            'blockers' => array(),
        );
        if (!$before || !$current) {
            $row['blockers'][] = 'snapshot_missing';
        } elseif (hash_equals((string) $before['record_hash'], (string) $current['record_hash'])) {
            $row['state'] = 'already_rolled_back';
        } elseif (!$after || !hash_equals((string) $after['record_hash'], (string) $current['record_hash'])) {
            $row['state'] = 'conflict';
            $row['blockers'][] = 'current_state_differs_from_recorded_after';
        } else {
            $row['state'] = 'ready';
            if ((bool) $before['focused_state']['funding_attribute'] !== (bool) $current['focused_state']['funding_attribute']) {
                $row['actions'][] = array(
                    'type' => !empty($before['focused_state']['funding_attribute']) ? 'add_term' : 'remove_term',
                    'taxonomy' => kingy_ali_p0_funding_attribute_taxonomy(),
                    'slug' => kingy_ali_p0_funding_attribute_slug(),
                );
            }
            if (!empty($expected['announcement_url']) && $current['focused_state']['canonical_announcement_values'] !== $before['focused_state']['canonical_announcement_values']) {
                $row['actions'][] = array(
                    'type' => 'remove_canonical_alias',
                    'meta_key' => kingy_ali_p0_canonical_announcement_meta_key(),
                    'value' => $expected['announcement_url'],
                );
            }
        }
        if ($row['blockers']) {
            foreach ($row['blockers'] as $blocker) {
                $plan['blockers'][] = $post_id . ':' . $blocker;
            }
        }
        $plan['pending_actions'] += count($row['actions']);
        $plan['records'][] = $row;
    }

    if (!$apply || $plan['blockers']) {
        return array('ok' => !$plan['blockers'], 'applied' => false, 'plan' => $plan, 'manifest_path' => $manifest_path);
    }

    $rollback_log = $run_directory . '/rollback.ndjson';
    if (!kingy_ali_p0_atomic_write($rollback_log, '')) {
        return array('ok' => false, 'applied' => false, 'error' => 'rollback_log_create_failed', 'plan' => $plan, 'manifest_path' => $manifest_path);
    }

    if (function_exists('kingy_ali_launch_release_mode_state')) {
        kingy_ali_launch_release_mode_state('reset');
    }
    if (function_exists('kingy_ali_begin_launch_release_mode')) {
        kingy_ali_begin_launch_release_mode('p0_rollback');
    }

    $errors = array();
    foreach ($plan['records'] as $row) {
        if ($row['state'] !== 'ready') {
            continue;
        }
        $post_id = (int) $row['post_id'];
        $record_manifest = $manifest['records'][(string) $post_id];
        $before_path = $run_directory . '/' . $record_manifest['before']['path'];
        $after_path = $run_directory . '/' . $record_manifest['after']['path'];
        $before = kingy_ali_p0_read_verified_json_file($before_path, $record_manifest['before']['sha256']);
        $after = kingy_ali_p0_read_verified_json_file($after_path, $record_manifest['after']['sha256']);
        $current = kingy_ali_p0_snapshot_record($post_id);
        if (!$before || !$after || !$current || !hash_equals((string) $after['record_hash'], (string) $current['record_hash'])) {
            $errors[] = $post_id . ':rollback_precondition_conflict';
            break;
        }
        $result = kingy_ali_p0_restore_record_targets_with_invariant_suspended($post_id, $before, $expected_records[$post_id]);
        $after_rollback = kingy_ali_p0_snapshot_record($post_id);
        $ok = !empty($result['ok'])
            && $after_rollback
            && hash_equals((string) $before['record_hash'], (string) $after_rollback['record_hash']);
        kingy_ali_p0_append_log(
            $rollback_log,
            array(
                'time_utc' => gmdate('c'),
                'phase' => 'rollback_record',
                'post_id' => $post_id,
                'actions' => $row['actions'],
                'ok' => $ok,
                'error' => $ok ? '' : (isset($result['error']) ? $result['error'] : 'rollback_hash_mismatch'),
            )
        );
        if (!$ok) {
            $errors[] = $post_id . ':' . (isset($result['error']) && $result['error'] ? $result['error'] : 'rollback_hash_mismatch');
            break;
        }
    }

    $flush_result = function_exists('kingy_ali_flush_launch_collection_purge_queue')
        ? kingy_ali_flush_launch_collection_purge_queue()
        : null;
    $cache_audit = function_exists('kingy_ali_launch_cache_adapter_audit')
        ? kingy_ali_launch_cache_adapter_audit()
        : array('release_mode_active' => false, 'adapter_invocation_count' => null);
    if ((int) ($cache_audit['adapter_invocation_count'] ?? 0) !== 0) {
        $errors[] = 'cache_adapter_invoked_in_release_mode';
    }
    if (!$errors && $plan['release_options']['state'] === 'ready') {
        $option_restore = kingy_ali_p0_restore_release_options($before_options);
        if (empty($option_restore['ok'])) {
            $errors[] = 'release_option_restore_failed';
        }
    }
    $manifest['release_options']['restored'] = kingy_ali_p0_release_options_snapshot();
    if (!$errors && !hash_equals((string) $before_options['state_hash'], (string) $manifest['release_options']['restored']['state_hash'])) {
        $errors[] = 'release_option_restore_hash_mismatch';
    }
    $manifest['cache_suppression']['rollback'] = array('flush_result' => $flush_result, 'audit' => $cache_audit);
    $manifest['status'] = $errors ? 'rollback_partial' : 'rolled_back';
    $manifest['rollback_at_utc'] = gmdate('c');
    $manifest['rollback_log'] = kingy_ali_p0_file_descriptor($run_directory, $rollback_log);
    $manifest['rollback_errors'] = $errors;
    kingy_ali_p0_write_manifest($manifest_path, $manifest);
    kingy_ali_p0_write_checksums($run_directory, $manifest);

    return array(
        'ok' => !$errors,
        'applied' => true,
        'errors' => $errors,
        'plan' => $plan,
        'manifest_path' => $manifest_path,
        'cache_suppression' => $manifest['cache_suppression']['rollback'],
        'release_options_restored' => !$errors && hash_equals((string) $before_options['state_hash'], (string) $manifest['release_options']['restored']['state_hash']),
    );
}

class Kingy_ALI_P0_Migration_CLI {
    public function __invoke($args, $assoc_args) {
        unset($args);

        $apply = !empty($assoc_args['apply']);
        $rollback_path = isset($assoc_args['rollback']) ? trim((string) $assoc_args['rollback']) : '';
        $format = isset($assoc_args['format']) ? sanitize_key($assoc_args['format']) : 'table';
        if ($apply && $rollback_path !== '') {
            WP_CLI::error('Choose either --apply for the forward migration or --rollback=<manifest>; never both.');
        }

        if ($rollback_path !== '') {
            $loaded = kingy_ali_p0_load_manifest($rollback_path);
            if (empty($loaded['ok'])) {
                WP_CLI::error('Rollback manifest rejected: ' . $loaded['error']);
            }
            $rollback_apply = !empty($assoc_args['apply-rollback']);
            $result = kingy_ali_p0_rollback_manifest($loaded, $rollback_apply);
            $this->render($result, $format);
            if (!empty($result['plan']['blockers'])) {
                WP_CLI::error('Rollback blocked; no records were changed.');
            }
            if ($rollback_apply && empty($result['ok'])) {
                WP_CLI::error('Rollback stopped after a conflict or verification failure.');
            }
            if ($rollback_apply) {
                WP_CLI::success('Rollback completed and verified against the before-record hashes.');
            } else {
                WP_CLI::success('Rollback dry-run completed. Re-run with --apply-rollback only after review.');
            }
            return;
        }

        $plan = kingy_ali_p0_build_plan();
        if (!$apply) {
            $this->render($plan, $format);
            if ($plan['blockers']) {
                WP_CLI::error('Dry-run found blockers; no records were changed.');
            }
            if (kingy_ali_p0_plan_is_noop($plan)) {
                WP_CLI::success('Dry-run is clean: all 26 records are already reconciled; 0 actions pending.');
            } else {
                WP_CLI::success('Dry-run completed; review the exact bounded plan before using --apply.');
            }
            return;
        }

        if ($plan['blockers']) {
            $this->render($plan, $format);
            WP_CLI::error('Apply blocked by precondition failures; no records were changed.');
        }
        if (kingy_ali_p0_plan_is_noop($plan)) {
            $this->render($plan, $format);
            WP_CLI::success('All 26 records are already reconciled; apply is an idempotent no-op.');
            return;
        }
        if (!kingy_ali_p0_plan_is_full_reviewed_before_state($plan) && empty($assoc_args['resume'])) {
            $this->render($plan, $format);
            WP_CLI::error('The site is in a partial reviewed state. Use --resume only after reviewing the dry-run and any earlier manifest.');
        }

        $backup_dir = isset($assoc_args['backup-dir']) ? trim((string) $assoc_args['backup-dir']) : '';
        if ($backup_dir === '') {
            WP_CLI::error('--backup-dir=<absolute path outside the webroot> is mandatory for apply.');
        }
        $db_backup = isset($assoc_args['db-backup']) ? trim((string) $assoc_args['db-backup']) : '';
        if ($plan['environment'] === 'production' && $db_backup === '') {
            WP_CLI::error('Production apply requires --db-backup=<fresh readable full database backup outside the webroot>.');
        }

        $backup = kingy_ali_p0_prepare_backup($backup_dir, $plan, $db_backup);
        if (empty($backup['ok'])) {
            WP_CLI::error('Backup preparation failed: ' . $backup['error']);
        }
        WP_CLI::log('Verified per-record backups written before mutation: ' . $backup['manifest_path']);

        $result = kingy_ali_p0_apply_plan($plan, $backup);
        $this->render($result, $format);
        if (empty($result['ok'])) {
            WP_CLI::error('Migration stopped. Use the reported manifest for a dry-run rollback after inspecting the conflict.');
        }

        $post_plan = kingy_ali_p0_build_plan();
        if (!kingy_ali_p0_plan_is_noop($post_plan)) {
            WP_CLI::error('Migration writes completed but final idempotence verification failed. Use the manifest for rollback.');
        }
        WP_CLI::success('P0 migration applied: exact 26-record set reconciled, aliases promoted, 0 actions remain.');
    }

    private function render($value, $format) {
        if ($format === 'json') {
            WP_CLI::line(kingy_ali_p0_json($value, true));
            return;
        }

        if (isset($value['counts'])) {
            WP_CLI::line(
                sprintf(
                    'Environment=%s records=%d pending=%d add_term=%d remove_term=%d add_meta=%d blockers=%d',
                    isset($value['environment']) ? $value['environment'] : '',
                    (int) $value['counts']['records'],
                    (int) $value['counts']['pending_actions'],
                    (int) $value['counts']['add_term'],
                    (int) $value['counts']['remove_term'],
                    (int) $value['counts']['add_meta'],
                    count((array) $value['blockers'])
                )
            );
            foreach ((array) $value['blockers'] as $blocker) {
                WP_CLI::warning($blocker);
            }
            return;
        }
        WP_CLI::line(kingy_ali_p0_json($value, true));
    }
}

if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI')) {
    WP_CLI::add_command('kingy-ali p0-migrate', 'Kingy_ALI_P0_Migration_CLI');
}
