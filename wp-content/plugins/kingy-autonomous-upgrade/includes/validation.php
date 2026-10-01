<?php
if (!defined('ABSPATH')) { exit; }

function kau_text($value, $limit = 2000) {
    if (!is_string($value) || strlen($value) > $limit) { throw new InvalidArgumentException('Invalid or oversized text field.'); }
    return trim($value);
}

function kau_utc($value) {
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $value)) {
        throw new InvalidArgumentException('Use a UTC timestamp with seconds and Z.');
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $value, new DateTimeZone('UTC'));
    if (!$date || $date->format('Y-m-d\TH:i:s\Z') !== $value) { throw new InvalidArgumentException('Invalid date.'); }
    return $value;
}

function kau_url($value) {
    $value = kau_text($value, 2048);
    $parts = parse_url($value);
    if (!$parts || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
        throw new InvalidArgumentException('An absolute HTTPS URL without credentials is required.');
    }
    return $value;
}

function kau_product_id($value) {
    if (!is_string($value) || !preg_match('/^wp:(kingy_ai_tool|kingy_ai_model):([1-9][0-9]{0,9})$/', $value, $m)) {
        throw new InvalidArgumentException('Use an existing stable WordPress product identity.');
    }
    return array('type' => $m[1], 'id' => (int) $m[2]);
}

/** A fetch timestamp never substitutes for any of these evidence dates. */
function kau_validate_change($input, $now = null) {
    if (!is_array($input)) { throw new InvalidArgumentException('Change must be an object.'); }
    $now = $now ?: gmdate('Y-m-d\TH:i:s\Z');
    $out = array();
    $out['product_id'] = kau_text($input['product_id'] ?? '', 80); kau_product_id($out['product_id']);
    $out['event_key'] = kau_text($input['event_key'] ?? '', 150);
    if (!preg_match('/^[a-zA-Z0-9:._-]{3,150}$/', $out['event_key'])) { throw new InvalidArgumentException('Stable event key required.'); }
    $out['kind'] = $input['kind'] ?? '';
    if (!in_array($out['kind'], array('announcement','preview','availability','shipping','update','funding','deprecation','retirement','price'), true)) {
        throw new InvalidArgumentException('Unsupported change kind.');
    }
    foreach (array('published_at','observed_at','verified_at') as $key) {
        $out[$key] = kau_utc($input[$key] ?? '');
        if ($out[$key] > $now) { throw new InvalidArgumentException('Evidence dates cannot be in the future.'); }
    }
    if ($out['published_at'] > $out['observed_at'] || $out['observed_at'] > $out['verified_at']) {
        throw new InvalidArgumentException('Conflicting publication, observation or verification dates.');
    }
    $out['source_id'] = kau_text($input['source_id'] ?? '', 191);
    if ($out['source_id'] === '') { throw new InvalidArgumentException('Existing source registry identity required.'); }
    $out['source_url'] = kau_url($input['source_url'] ?? '');
    $out['source_sha256'] = kau_text($input['source_sha256'] ?? '', 64);
    if (!preg_match('/^[a-f0-9]{64}$/', $out['source_sha256'])) { throw new InvalidArgumentException('Retained source fingerprint required.'); }
    foreach (array('title','summary','why_it_matters','source_quote') as $key) {
        $out[$key] = kau_text($input[$key] ?? '', $key === 'title' ? 180 : 3000);
        if ($out[$key] === '') { throw new InvalidArgumentException('Missing ' . $key); }
    }
    $out['guide_url'] = empty($input['guide_url']) ? null : kau_url($input['guide_url']);
    // Exact retained quotation and product/source association are checked by the canonical verifier adapter.
    if ($out['kind'] === 'price') {
        $price = $input['price'] ?? null;
        if (!is_array($price) || !is_numeric($price['amount'] ?? null) || (float) $price['amount'] < 0 || (float) $price['amount'] > 1000000
            || !preg_match('/^[A-Z]{3}$/', $price['currency'] ?? '') || !in_array($price['unit'] ?? '', array('second','generation','month','million_tokens'), true)) {
            throw new InvalidArgumentException('Price amount, currency and billing unit required.');
        }
        $out['price'] = array('amount' => (float) $price['amount'], 'currency' => $price['currency'], 'unit' => $price['unit']);
    }
    return $out;
}

function kau_validate_companion($input) {
    if (!is_array($input) || !preg_match('/^[A-Za-z0-9_-]{11}$/', $input['video_id'] ?? '')
        || !preg_match('/^UC[A-Za-z0-9_-]{22}$/', $input['channel_id'] ?? '')) {
        throw new InvalidArgumentException('Verified platform video and channel identities required.');
    }
    $out = array('video_id' => $input['video_id'], 'channel_id' => $input['channel_id'], 'published_at' => kau_utc($input['published_at'] ?? ''));
    foreach (array('title','summary','suits','limitations','disclosure','evidence_reference') as $key) {
        $out[$key] = kau_text($input[$key] ?? '', $key === 'title' ? 180 : 10000);
        if (!$out[$key]) { throw new InvalidArgumentException('Missing companion ' . $key); }
    }
    $out['commercial_status'] = $input['commercial_status'] ?? 'unconfirmed';
    if (!in_array($out['commercial_status'], array('sponsored','independent','unconfirmed','mixed'), true)) { throw new InvalidArgumentException('Invalid disclosure.'); }
    $duration = $input['duration_seconds'] ?? 0;
    if (!is_int($duration) || $duration <= 0) { throw new InvalidArgumentException('Verified duration required.'); }
    $out['duration_seconds'] = $duration; $out['chapters'] = array(); $last = -1;
    foreach (($input['chapters'] ?? array()) as $chapter) {
        if (!is_array($chapter) || !is_int($chapter['seconds'] ?? null) || $chapter['seconds'] <= $last || $chapter['seconds'] >= $duration) {
            throw new InvalidArgumentException('Chapters must increase and fall within the verified video duration.');
        }
        $last = $chapter['seconds']; $label = kau_text($chapter['title'] ?? '', 180);
        if (!$label) { throw new InvalidArgumentException('Chapter title required.'); }
        $out['chapters'][] = array('seconds' => $last, 'title' => $label);
    }
    if (!$out['chapters']) { throw new InvalidArgumentException('Evidence-supported chapters required.'); }
    $out['prompts'] = kau_text($input['prompts'] ?? 'Exact prompts and settings were not retained.', 15000);
    $out['verdict'] = kau_text($input['verdict'] ?? 'No evidence-supported Curtis verdict is available.', 5000);
    $out['assets'] = array();
    foreach (($input['assets'] ?? array()) as $asset) {
        if (!is_array($asset) || ($asset['authorized'] ?? false) !== true) { throw new InvalidArgumentException('Asset permission must be documented.'); }
        $out['assets'][] = array('url' => kau_url($asset['url'] ?? ''), 'label' => kau_text($asset['label'] ?? '', 180), 'rights_reference' => kau_text($asset['rights_reference'] ?? '', 1000));
        if (!$out['assets'][count($out['assets'])-1]['rights_reference']) { throw new InvalidArgumentException('Asset rights evidence required.'); }
    }
    $out['product_ids'] = array_values(array_unique($input['product_ids'] ?? array()));
    foreach ($out['product_ids'] as $id) { kau_product_id($id); }
    if (!$out['product_ids']) { throw new InvalidArgumentException('Related existing products required.'); }
    return $out;
}
