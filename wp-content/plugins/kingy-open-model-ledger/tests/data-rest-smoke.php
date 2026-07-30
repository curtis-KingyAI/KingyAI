<?php

define('ABSPATH', __DIR__ . '/');
$koml_meta_store = array();
$koml_has_thumbnail = false;
$koml_posts = array();

function __($text, $domain = '') { return $text; }
function add_action() {}
function register_post_meta() {}
function current_user_can() { return true; }
function sanitize_text_field($value) { return trim(strip_tags((string) $value)); }
function sanitize_textarea_field($value) { return trim(strip_tags((string) $value)); }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value)); }
function esc_url_raw($value, $protocols = null) { return preg_match('#^https?://#', (string) $value) ? (string) $value : ''; }
function absint($value) { return abs((int) $value); }
function wp_json_encode($value) { return json_encode($value); }
function get_current_user_id() { return 7; }
function wp_generate_uuid4() { return '00000000-0000-4000-8000-000000000001'; }
function has_post_thumbnail($post_id) { global $koml_has_thumbnail; return $koml_has_thumbnail; }
function get_post_meta($post_id, $key, $single = true) { global $koml_meta_store; return isset($koml_meta_store[$post_id][$key]) ? $koml_meta_store[$post_id][$key] : ''; }
function update_post_meta($post_id, $key, $value) { global $koml_meta_store; $koml_meta_store[$post_id][$key] = $value; return true; }
function delete_post_meta($post_id, $key) { global $koml_meta_store; unset($koml_meta_store[$post_id][$key]); return true; }
function get_post($post_id) { global $koml_posts; return isset($koml_posts[$post_id]) ? $koml_posts[$post_id] : null; }
function get_the_title($post) { return is_object($post) ? $post->post_title : ''; }
function get_permalink($post) { return 'https://example.test/ai-models/' . $post->post_name . '/'; }
function get_post_modified_time() { return '2026-07-29T00:00:00Z'; }
function rest_ensure_response($value) { return $value; }

class WP_Error {
    public $code;
    public function __construct($code) { $this->code = $code; }
}

class KOML_Test_Request {
    private $params;
    public function __construct($params) { $this->params = $params; }
    public function get_param($key) { return isset($this->params[$key]) ? $this->params[$key] : null; }
}

function koml_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

require_once dirname(__DIR__) . '/includes/class-koml-data.php';
require_once dirname(__DIR__) . '/includes/class-koml-rest.php';

$raw_scalars = array(
    'scope_status' => 'curated',
    'announced_on' => '2025-01-20',
    'weights_available_on' => '2025-01-21',
    'weight_access' => 'public',
    'rights_profile' => 'permissive',
    'osaid_outcome' => 'insufficient_evidence',
    'total_parameters' => '671000000000',
    'active_parameters' => '37000000000',
    'repository_url' => 'https://huggingface.co/example/model',
);
$raw_repeatables = array(
    'artifacts' => array(array(
        'name' => 'BF16 weights',
        'url' => 'https://huggingface.co/example/model/tree/abcdef',
        'revision' => 'abcdef',
        'format' => 'safetensors',
        'size_bytes' => '404000000000',
        'provenance' => 'publisher',
    )),
    'evidence' => array(array(
        'field' => 'weights_available_on',
        'method' => 'artifact_inspected',
        'source_url' => 'https://huggingface.co/example/model/commits/abcdef',
        'locator' => 'commit abcdef',
        'confidence' => 'high',
        'verified_on' => '2026-07-29',
    )),
);

KOML_Data::save_ledger(101, $raw_scalars, $raw_repeatables, 'test');
koml_assert(get_post_meta(101, '_koml_scope_status', true) === 'under_review', 'Curation fails closed without a featured image.');
koml_assert(get_post_meta(101, '_koml_total_parameters', true) === '671000000000', 'Large parameter counts retain decimal precision.');
$history = get_post_meta(101, '_koml_change_log', true);
koml_assert(count($history) === 1 && $history[0]['event_type'] === 'weights_available', 'Save appends a typed change event.');
koml_assert(strpos($history[0]['note'], 'featured image') !== false, 'Image-gate rejection is recorded.');

$koml_has_thumbnail = true;
KOML_Data::save_ledger(101, array_merge($raw_scalars, array('scope_status' => 'curated')), $raw_repeatables, 'test');
koml_assert(get_post_meta(101, '_koml_scope_status', true) === 'curated', 'Curated status is accepted after the record has a featured image.');
$history = get_post_meta(101, '_koml_change_log', true);
koml_assert(count($history) === 2, 'Second material save appends rather than overwrites history.');

$koml_posts[101] = (object) array('ID' => 101, 'post_type' => 'kingy_ai_model', 'post_status' => 'publish', 'post_name' => 'example-model', 'post_title' => 'Example Model');
$public = KOML_REST::get_model(new KOML_Test_Request(array('id' => 101)));
koml_assert(is_array($public) && $public['id'] === 101, 'Curated record is visible through the read-only REST item endpoint.');
koml_assert(!isset($public['ledger']['change_log'][0]['actor_id']), 'Public change history omits internal actor IDs.');

update_post_meta(101, '_koml_scope_status', 'under_review');
$hidden = KOML_REST::get_model(new KOML_Test_Request(array('id' => 101)));
koml_assert($hidden instanceof WP_Error, 'Under-review record fails closed in the public REST endpoint.');

fwrite(STDOUT, "Open Model Ledger data and REST smoke tests passed.\n");
