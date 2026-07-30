<?php

define('ABSPATH', __DIR__ . '/');

$kingy_test_is_launch_taxonomy = false;
$kingy_test_query_vars = array();

function add_filter() {
}

function add_action() {
}

function absint($value) {
    return abs((int) $value);
}

function is_tax($taxonomy = '') {
    global $kingy_test_is_launch_taxonomy;
    return $taxonomy === 'kingy_launch_category' && $kingy_test_is_launch_taxonomy;
}

function get_query_var($key) {
    global $kingy_test_query_vars;
    return isset($kingy_test_query_vars[$key]) ? $kingy_test_query_vars[$key] : '';
}

require_once dirname(__DIR__) . '/includes/launch-index.php';

function kingy_assert_same($expected, $actual, $message) {
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

kingy_assert_same(4, kingy_ali_launch_archive_current_page(4), 'Non-taxonomy launch surfaces retain query-parameter pagination.');

$kingy_test_is_launch_taxonomy = true;
kingy_assert_same(4, kingy_ali_launch_archive_current_page(4), 'Taxonomy archives retain the legacy query parameter as a fallback.');

$kingy_test_query_vars['paged'] = 2;
kingy_assert_same(2, kingy_ali_launch_archive_current_page(4), 'The cache-safe taxonomy path page takes precedence.');

$kingy_test_query_vars['paged'] = 0;
$kingy_test_query_vars['page'] = 3;
kingy_assert_same(3, kingy_ali_launch_archive_current_page(4), 'The secondary WordPress page query var is supported.');

fwrite(STDOUT, "Launch archive pagination smoke tests passed.\n");
