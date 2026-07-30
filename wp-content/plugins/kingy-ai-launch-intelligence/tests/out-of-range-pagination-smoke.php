<?php

define('ABSPATH', __DIR__ . '/');

$kingy_test_query_vars = array();
$kingy_test_archive = '';
$kingy_test_is_404 = false;
$kingy_test_status = 200;
$kingy_test_nocache = false;
$kingy_test_model_total = 0;
$kingy_test_launch_total_pages = 0;
$kingy_test_term = (object) array('slug' => 'ai-agents');

class Kingy_Test_Main_Query {
    public $is_404 = false;

    public function set_404() {
        $this->is_404 = true;
    }
}

$wp_query = new Kingy_Test_Main_Query();

function add_action() {
}

function absint($value) {
    return abs((int) $value);
}

function get_query_var($key) {
    global $kingy_test_query_vars;
    return isset($kingy_test_query_vars[$key]) ? $kingy_test_query_vars[$key] : '';
}

function is_404() {
    global $kingy_test_is_404;
    return $kingy_test_is_404;
}

function is_post_type_archive($post_type = '') {
    global $kingy_test_archive;
    return $post_type === 'kingy_ai_model' && $kingy_test_archive === 'models';
}

function is_tax($taxonomy = '') {
    global $kingy_test_archive;
    return $taxonomy === 'kingy_launch_category' && $kingy_test_archive === 'launches';
}

function kingy_ali_request_get_value($key) {
    return '';
}

function kingy_ali_model_request_filters() {
    return array();
}

function kingy_ali_query_model_directory($args = array()) {
    global $kingy_test_model_total;
    return (object) array('posts' => array_fill(0, $kingy_test_model_total, (object) array()));
}

function kingy_ali_request_filters() {
    return array('category' => '', 'page' => 1);
}

function get_queried_object() {
    global $kingy_test_term;
    return $kingy_test_term;
}

function is_wp_error($value) {
    return false;
}

function kingy_ali_query_launches($args = array()) {
    global $kingy_test_launch_total_pages;
    return (object) array('max_num_pages' => $kingy_test_launch_total_pages);
}

function status_header($status) {
    global $kingy_test_status;
    $kingy_test_status = (int) $status;
}

function nocache_headers() {
    global $kingy_test_nocache;
    $kingy_test_nocache = true;
}

require_once dirname(__DIR__) . '/includes/pagination.php';

function kingy_pagination_assert_same($expected, $actual, $message) {
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

function kingy_pagination_reset_response() {
    global $wp_query, $kingy_test_status, $kingy_test_nocache;
    $wp_query = new Kingy_Test_Main_Query();
    $kingy_test_status = 200;
    $kingy_test_nocache = false;
}

kingy_pagination_assert_same(false, kingy_ali_archive_page_is_out_of_range(1, 0), 'An empty first page remains a valid empty state.');
kingy_pagination_assert_same(true, kingy_ali_archive_page_is_out_of_range(2, 0), 'A later page with no results is out of range.');
kingy_pagination_assert_same(false, kingy_ali_archive_page_is_out_of_range(4, 4), 'The final populated page remains valid.');
kingy_pagination_assert_same(true, kingy_ali_archive_page_is_out_of_range(5, 4), 'A page after the final populated page is out of range.');

$kingy_test_archive = 'models';
$kingy_test_model_total = 92;
$kingy_test_query_vars['paged'] = 4;
kingy_pagination_assert_same(false, kingy_ali_enforce_directory_archive_pagination_bounds(), 'AI Models page 4 remains valid for 92 records.');

$kingy_test_query_vars['paged'] = 5;
kingy_pagination_assert_same(true, kingy_ali_enforce_directory_archive_pagination_bounds(), 'AI Models page 5 becomes a 404 for 92 records.');
kingy_pagination_assert_same(404, $kingy_test_status, 'The AI Models overflow response receives HTTP 404.');
kingy_pagination_assert_same(true, $wp_query->is_404, 'The AI Models overflow request uses the WordPress 404 template path.');
kingy_pagination_assert_same(true, $kingy_test_nocache, 'The AI Models overflow response is not cached as a directory page.');

kingy_pagination_reset_response();
$kingy_test_archive = 'launches';
$kingy_test_launch_total_pages = 5;
$kingy_test_query_vars['paged'] = 5;
kingy_pagination_assert_same(false, kingy_ali_enforce_directory_archive_pagination_bounds(), 'The final launch taxonomy page remains valid.');

$kingy_test_query_vars['paged'] = 6;
kingy_pagination_assert_same(true, kingy_ali_enforce_directory_archive_pagination_bounds(), 'A launch taxonomy page after the final page becomes a 404.');
kingy_pagination_assert_same(404, $kingy_test_status, 'The launch taxonomy overflow response receives HTTP 404.');
kingy_pagination_assert_same(true, $wp_query->is_404, 'The launch taxonomy overflow request uses the WordPress 404 template path.');

fwrite(STDOUT, "Out-of-range archive pagination smoke tests passed.\n");
