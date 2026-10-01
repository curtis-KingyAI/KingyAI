<?php
if (!defined('ABSPATH')) { exit; }

function kau_schedule_definitions() {
    return array(
        'youtube' => array('hours' => array(0,6,12,18), 'minute' => 0),
        'launches' => array('hours' => array(6,12,18), 'minute' => 0),
        'priority_changes' => array('hours' => array(7), 'minute' => 0),
        'catalog_freshness' => array('hours' => array(6), 'minute' => 30, 'weekday' => 1),
        'brief' => array('hours' => array(9), 'minute' => 0, 'weekday' => 5),
        'stack_digest' => array('hours' => array(8), 'minute' => 0),
        'health' => array('hours' => range(0,23), 'minute' => 0),
    );
}

/** Slots use local civil time (including DST); the worker records UTC instants. */
function kau_due_slots($utc) {
    $local = $utc->setTimezone(new DateTimeZone('America/Vancouver')); $result = array();
    foreach (kau_schedule_definitions() as $name => $rule) {
        if (isset($rule['weekday']) && (int) $local->format('N') !== $rule['weekday']) { continue; }
        foreach ($rule['hours'] as $hour) {
            $scheduled = $local->setTime($hour, $rule['minute']);
            if ($scheduled > $local) { continue; }
            $age = $utc->getTimestamp() - $scheduled->getTimestamp();
            // Never replay yesterday's newsletter as a current edition.
            if ($age > 3600) { $status = 'missed'; } else { $status = 'due'; }
            $result[] = array('name' => $name, 'key' => $name . ':' . $local->format('Y-m-d') . ':' . sprintf('%02d%02d', $hour, $rule['minute']),
                'status' => $status, 'scheduled_at' => $scheduled->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'));
        }
    }
    return $result;
}

function kau_tick($test = false) {
    global $wpdb;
    if (!kau_enabled('jobs')) { return array('status' => 'disabled'); }
    $token = kau_lock('tick', 300);
    if (!$token) { return array('status' => 'locked'); }
    $results = array(); $deadline = time()+240;
    try {
        $slots = kau_due_slots(new DateTimeImmutable('now', new DateTimeZone('UTC')));
        if ($test) {
            $slots = array_map(function ($name) { return array('name' => $name, 'key' => 'test:' . $name . ':' . gmdate('Y-m-d-H'), 'status' => 'due', 'scheduled_at' => kau_now()); }, array_keys(kau_schedule_definitions()));
        }
        $bindings = get_option('kau_job_bindings', array());
        foreach ($slots as $slot) {
            // Only explicitly assigned ownership is eligible. Equivalent existing jobs remain external.
            if (($bindings[$slot['name']] ?? '') !== 'extension' && !$test) { continue; }
            if (time()>$deadline-10) { return array('status'=>'bounded','test'=>$test,'results'=>$results); }
            $results[] = kau_run_slot($slot, $test, $deadline);
        }
        return array('status' => 'completed', 'test' => $test, 'results' => $results);
    } finally { kau_unlock('tick', $token); }
}

function kau_run_slot($slot, $test = false, $deadline = null) {
    global $wpdb;
    $table = kau_table('jobs');
    $wpdb->query($wpdb->prepare("INSERT IGNORE INTO $table (slot_key,name,status,started_at,detail) VALUES (%s,%s,'pending',%s,'')", $slot['key'], $slot['name'], kau_now()));
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE slot_key=%s", $slot['key']), ARRAY_A);
    if (!$row) { throw new RuntimeException('Job state unavailable.'); }
    if (in_array($row['status'], array('completed','missed','blocked','failed'), true)) { return array('name' => $slot['name'], 'status' => $row['status'], 'duplicate' => true); }
    if ((int) $row['next_attempt'] > time()) { return array('name' => $slot['name'], 'status' => 'backoff'); }
    if ($slot['status'] === 'missed') {
        $wpdb->update($table, array('status' => 'missed', 'completed_at' => kau_now(), 'detail' => 'Expired slot; no send replayed.'), array('id' => $row['id']));
        return array('name' => $slot['name'], 'status' => 'missed');
    }
    $token = kau_lock('job-' . $slot['name']);
    if (!$token) { return array('name' => $slot['name'], 'status' => 'locked'); }
    $attempt = (int) $row['attempts'] + 1;
    $wpdb->update($table, array('status' => 'running', 'attempts' => $attempt, 'started_at' => kau_now()), array('id' => $row['id']));
    try {
        // Adapter must be bounded to <240 seconds and reuse the maintained system, not a new registry/list.
        $result = apply_filters('kau_job_' . $slot['name'], null, array('test' => $test, 'slot' => $slot, 'deadline' => min(time()+240,$deadline ?? time()+240)));
        if (!is_array($result) || !in_array($result['status'] ?? '', array('completed','blocked','transient_failure'), true)) {
            $result = array('status' => 'blocked', 'code' => 'existing_system_adapter_missing');
        }
        $status = $result['status'];
        if ($status === 'transient_failure') { $status = $attempt < 3 ? 'retry' : 'failed'; }
        $code = isset($result['code']) && is_string($result['code']) && preg_match('/^[a-z0-9_]{1,80}$/', $result['code']) ? $result['code'] : $status;
        $wpdb->update($table, array('status' => $status, 'next_attempt' => $status === 'retry' ? time() + min(900, 30 * (2 ** ($attempt-1))) : 0,
            'completed_at' => $status === 'retry' ? '' : kau_now(), 'detail' => wp_json_encode(array('code' => $code, 'test' => $test,
                'checked' => max(0,(int)($result['checked'] ?? 0)), 'qualified' => max(0,(int)($result['qualified'] ?? 0))))), array('id' => $row['id']));
        if (in_array($status, array('blocked','failed'), true)) { kau_exception('jobs', $slot['key'], $code); }
        return array('name' => $slot['name'], 'status' => $status, 'code' => $code);
    } catch (Throwable $e) {
        $wpdb->update($table, array('status' => 'failed', 'completed_at' => kau_now(), 'detail' => 'code_defect_requires_operator'), array('id' => $row['id']));
        kau_exception('jobs', $slot['key'], 'Unexpected code defect; affected job held for operator review.');
        return array('name' => $slot['name'], 'status' => 'failed');
    } finally { kau_unlock('job-' . $slot['name'], $token); }
}

function kau_queue_email($stream, $recipient_ref, $policy_key, $payload, $expires_at) {
    global $wpdb;
    if (!in_array($stream, array('brief','stack_digest'), true)) { throw new InvalidArgumentException('Unsupported stream.'); }
    $ref = kau_text($recipient_ref, 191); $policy = kau_text($policy_key, 191); kau_utc($expires_at);
    if (!$ref || !$policy || !is_array($payload) || empty($payload['event_keys']) || !is_array($payload['event_keys'])) { throw new InvalidArgumentException('Recipient, policy and qualifying events required.'); }
    $events = array_values(array_unique($payload['event_keys'])); sort($events); $payload['event_keys'] = $events;
    $key = $stream . ':' . hash('sha256', $ref . ':' . $policy);
    $wpdb->query($wpdb->prepare('INSERT IGNORE INTO ' . kau_table('outbox') .
        ' (delivery_key,recipient_ref,stream,expires_at,payload,updated_at) VALUES (%s,%s,%s,%s,%s,%s)',
        $key, $ref, $stream, $expires_at, wp_json_encode($payload), kau_now()));
    return (int) $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . kau_table('outbox') . ' WHERE delivery_key=%s', $key));
}

function kau_delivery_ready($stream) {
    $checks = get_option('kau_email_checks', array());
    foreach (array('provider_sandbox','rendering','archive_links','preferences','suppression','unsubscribe','reconciliation','account_restrictions') as $check) {
        if (($checks[$stream][$check] ?? false) !== true) { return false; }
    }
    return kau_enabled('email');
}

function kau_send_outbox($id, $test = false) {
    global $wpdb;
    $token = kau_lock('email-' . (int) $id);
    if (!$token) { return array('status' => 'locked'); }
    $table = kau_table('outbox');
    try {
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $id), ARRAY_A);
        if (!$row) { throw new InvalidArgumentException('Delivery record missing.'); }
        if (in_array($row['status'], array('accepted','delivered','suppressed','expired','failed'), true)) { return array('status' => $row['status']); }
        if ($test) { return array('status' => 'test_no_send'); }
        if ($row['expires_at'] < kau_now()) { $status = 'expired'; }
        elseif (!kau_delivery_ready($row['stream'])) { return array('status' => 'blocked_provider_checks'); }
        else {
            $policy = apply_filters('kau_recipient_policy', null, $row);
            if (!is_array($policy) || ($policy['verified_opt_in'] ?? false) !== true || ($policy['suppressed'] ?? true) !== false
                || ($policy['stream'] ?? '') !== $row['stream'] || ($policy['frequency_eligible'] ?? false) !== true
                || ($policy['events_eligible'] ?? false) !== true) { $status = 'suppressed'; }
            elseif (in_array($row['status'], array('sending','uncertain'), true)) {
                // A crash after provider acceptance must be reconciled before any retry.
                $result = apply_filters('kau_email_reconcile', null, $row);
                $status = is_array($result) ? ($result['status'] ?? 'uncertain') : 'uncertain';
                if ($status === 'definitely_not_sent') { $status = 'queued'; } // Retry only on a later invocation.
            } else {
                $wpdb->update($table, array('status' => 'sending', 'updated_at' => kau_now()), array('id' => $id));
                try { $result = apply_filters('kau_email_transport', null, $row, $policy); }
                catch (Throwable $e) { $result = null; }
                $status = is_array($result) ? ($result['status'] ?? 'uncertain') : 'uncertain';
                // Transport errors alone cannot establish that an email was not accepted.
            }
        }
        if (!in_array($status, array('queued','accepted','delivered','uncertain','suppressed','expired','failed'), true)) { $status = 'uncertain'; }
        $provider_id = isset($result['provider_id']) && is_string($result['provider_id']) ? substr($result['provider_id'], 0, 191) : $row['provider_id'];
        if (in_array($status, array('accepted','delivered'), true) && !$provider_id) { $status = 'uncertain'; }
        $wpdb->update($table, array('status' => $status, 'provider_id' => $provider_id, 'updated_at' => kau_now()), array('id' => $id));
        return array('status' => $status);
    } finally { kau_unlock('email-' . (int) $id, $token); }
}
