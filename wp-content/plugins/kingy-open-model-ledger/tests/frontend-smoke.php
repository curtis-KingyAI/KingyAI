<?php

define('ABSPATH', __DIR__ . '/');
define('DAY_IN_SECONDS', 86400);
define('OBJECT', 'OBJECT');

$koml_test_meta = array();
$koml_test_model_fit_page = null;
$koml_test_post_status = array();
$koml_test_thumbnails = array();

function get_post_meta($post_id, $key, $single = true) {
    global $koml_test_meta;
    return isset($koml_test_meta[$post_id][$key]) ? $koml_test_meta[$post_id][$key] : '';
}

function __($text, $domain = '') {
    return $text;
}

function _n($single, $plural, $number, $domain = '') {
    return $number === 1 ? $single : $plural;
}

function get_page_by_path($path, $output = OBJECT, $post_type = 'page') {
    global $koml_test_model_fit_page;
    return $path === 'model-fit' ? $koml_test_model_fit_page : null;
}

function get_post_status($post_id) {
    global $koml_test_post_status;
    return isset($koml_test_post_status[$post_id]) ? $koml_test_post_status[$post_id] : false;
}

function has_post_thumbnail($post_id) {
    global $koml_test_thumbnails;
    return !empty($koml_test_thumbnails[$post_id]);
}

require_once dirname(__DIR__) . '/includes/class-koml-frontend.php';

function koml_assert_same($expected, $actual, $message) {
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

$koml_test_meta[1] = array(
    '_koml_weight_access' => 'public',
    '_koml_rights_profile' => 'permissive',
    '_koml_osaid_outcome' => 'insufficient_evidence',
    '_koml_announced_on' => '2025-01-01',
    '_koml_weights_available_on' => '2025-01-04',
);
$koml_test_meta[2] = array(
    '_koml_weight_access' => 'gated_manual',
    '_koml_rights_profile' => 'restricted',
    '_koml_osaid_outcome' => 'does_not_meet',
);
$koml_test_meta[3] = array(
    '_koml_weight_access' => 'public',
    '_koml_rights_profile' => 'permissive',
    '_koml_osaid_outcome' => 'meets',
);
$koml_test_meta[4] = array('_kingy_ali_open_weight' => 'yes');
$koml_test_meta[5] = array(
    '_koml_weight_access' => 'public',
    '_koml_rights_profile' => 'noncommercial',
    '_koml_osaid_outcome' => 'does_not_meet',
);

koml_assert_same('open_weight', KOML_Frontend::openness(1)['key'], 'Permissive public weights classify independently from OSAID.');
koml_assert_same('restricted', KOML_Frontend::openness(2)['key'], 'Restricted gated weights are not called open source.');
koml_assert_same('open_source', KOML_Frontend::openness(3)['key'], 'OSAID meets result receives the assessed open-source label.');
koml_assert_same('review', KOML_Frontend::openness(4)['key'], 'Legacy open-weight flag remains review-pending.');
koml_assert_same('restricted', KOML_Frontend::openness(5)['key'], 'Noncommercial weights are labeled restricted, not merely custom.');
koml_assert_same('3 days after announcement', KOML_Frontend::date_lag(1), 'Announcement-to-weights lag is explicit.');
koml_assert_same('671B', KOML_Frontend::format_parameter_count('671000000000'), 'Raw parameter counts format as billions.');
koml_assert_same('37B', KOML_Frontend::format_parameter_count('37B'), 'Human-readable parameter counts remain stable.');

koml_assert_same(false, KOML_Frontend::model_fit_is_ready(), 'Missing calculator page keeps the directory CTA disabled.');
$koml_test_model_fit_page = (object) array('ID' => 80);
$koml_test_post_status[80] = 'draft';
$koml_test_thumbnails[80] = true;
koml_assert_same(false, KOML_Frontend::model_fit_is_ready(), 'A draft calculator stays disabled even with a featured image.');
$koml_test_post_status[80] = 'publish';
$koml_test_thumbnails[80] = false;
koml_assert_same(false, KOML_Frontend::model_fit_is_ready(), 'A published calculator stays disabled without a featured image.');
$koml_test_thumbnails[80] = true;
koml_assert_same(true, KOML_Frontend::model_fit_is_ready(), 'A published calculator with a featured image enables the directory CTA.');

fwrite(STDOUT, "Open Model Ledger frontend smoke tests passed.\n");
