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
function kau_due_slots($utc, $last_tick = null) {
    $local = $utc->setTimezone(new DateTimeZone('America/Vancouver')); $result = array();
    $first=$last_tick?$last_tick->setTimezone(new DateTimeZone('America/Vancouver'))->setTime(0,0):$local->setTime(0,0);
    $floor=$local->modify('-2 days')->setTime(0,0);if($first<$floor){$first=$floor;}
    for($day=$first;$day->format('Y-m-d')<=$local->format('Y-m-d');$day=$day->modify('+1 day')){
    foreach (kau_schedule_definitions() as $name => $rule) {
        if (isset($rule['weekday']) && (int) $day->format('N') !== $rule['weekday']) { continue; }
        foreach ($rule['hours'] as $hour) {
            $scheduled = $day->setTime($hour, $rule['minute']);
            if ($scheduled > $local) { continue; }
            $age = $utc->getTimestamp() - $scheduled->getTimestamp();
            // Never replay yesterday's newsletter as a current edition.
            if ($age > 3600) { $status = 'missed'; } else { $status = 'due'; }
            $result[] = array('name' => $name, 'key' => $name . ':' . $day->format('Y-m-d') . ':' . sprintf('%02d%02d', $hour, $rule['minute']),
                'status' => $status, 'scheduled_at' => $scheduled->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'));
        }
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
        $now=new DateTimeImmutable('now',new DateTimeZone('UTC'));$last=get_option('kau_last_tick');
        $slots = kau_due_slots($now, (!$test&&$last)?new DateTimeImmutable($last):null);
        if(!$test){if($last&&$now->getTimestamp()-strtotime($last)>3*86400){kau_exception('jobs','worker-gap:'.$last,'Worker gap exceeds bounded three-day slot recovery; inspect scheduler and prior interval manually.');}update_option('kau_last_tick',kau_now(),false);}
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
        $outbox = kau_drain_outbox($test, $deadline);
        return array('status' => 'completed', 'test' => $test, 'results' => $results, 'outbox' => $outbox);
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
    // A different worker may have finished after the earlier read and before this lock.
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE slot_key=%s", $slot['key']), ARRAY_A);
    if (!$row || in_array($row['status'],array('completed','missed','blocked','failed'),true) || (int)$row['next_attempt']>time()) {
        kau_unlock('job-'.$slot['name'],$token);
        return array('name'=>$slot['name'],'status'=>$row['status']??'failed','duplicate'=>true);
    }
    if ((int)$row['attempts']>=3) { $wpdb->update($table,array('status'=>'failed','completed_at'=>kau_now(),'detail'=>'abandoned_attempt_limit'),array('id'=>$row['id']));kau_exception('jobs',$slot['key'],'Repeated abandoned attempts reached the limit; inspect worker interruption before retrying.');kau_unlock('job-'.$slot['name'],$token);return array('name'=>$slot['name'],'status'=>'failed'); }
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

function kau_canonical_json($value){
    if(is_array($value)){if(!array_is_list($value)){ksort($value,SORT_STRING);}foreach($value as $k=>$v){$value[$k]=json_decode(kau_canonical_json($v),true);}}
    $json=wp_json_encode($value);if($json===false){throw new InvalidArgumentException('Invalid JSON payload.');}return $json;
}

function kau_queue_email($stream, $recipient_ref, $policy_key, $payload, $expires_at) {
    global $wpdb;
    if (!in_array($stream, array('brief','stack_digest'), true)) { throw new InvalidArgumentException('Unsupported stream.'); }
    $ref = kau_text($recipient_ref, 191); $policy = kau_text($policy_key, 191); kau_utc($expires_at);
    if (!$ref || !$policy || !is_array($payload) || empty($payload['event_keys']) || !is_array($payload['event_keys'])) { throw new InvalidArgumentException('Recipient, policy and qualifying events required.'); }
    foreach ($payload['event_keys'] as $event) { if (!is_string($event) || !kau_text($event,191)) { throw new InvalidArgumentException('Stable event keys required.'); } }
    $events = array_values(array_unique($payload['event_keys'])); sort($events); $payload['event_keys'] = $events;
    $json = kau_canonical_json($payload); if (strlen($json)>1000000) { throw new InvalidArgumentException('Oversized email payload.'); }
    $key = $stream . ':' . hash('sha256', $ref . ':' . $policy);
    $insert = $wpdb->query($wpdb->prepare('INSERT IGNORE INTO ' . kau_table('outbox') .
        ' (delivery_key,recipient_ref,stream,expires_at,payload,updated_at) VALUES (%s,%s,%s,%s,%s,%s)',
        $key, $ref, $stream, $expires_at, $json, kau_now()));
    $row = $wpdb->get_row($wpdb->prepare('SELECT id,payload,expires_at FROM ' . kau_table('outbox') . ' WHERE delivery_key=%s', $key),ARRAY_A);
    if ($insert===false || !$row) { throw new RuntimeException('Durable outbox write failed.'); }
    if (kau_canonical_json(json_decode($row['payload'],true))!==$json || $row['expires_at']!==$expires_at) { kau_exception('email',$key,'Conflicting payload for an existing delivery policy; original delivery retained.'); throw new RuntimeException('Delivery policy payload conflict.'); }
    return (int)$row['id'];
}

function kau_delivery_ready($stream) {
    $paused=get_option('kau_email_paused',array());if(!empty($paused[$stream])){return false;}
    $checks = get_option('kau_email_checks', array());
    foreach (array('provider_sandbox','rendering','archive_links','preferences','suppression','unsubscribe','reconciliation','account_restrictions') as $check) {
        if (($checks[$stream][$check] ?? false) !== true) { return false; }
    }
    return kau_enabled('email');
}

function kau_send_outbox($id, $test = false, $respect_backoff = true, $deadline = null) {
    global $wpdb;
    $token = kau_lock('email-' . (int) $id);
    if (!$token) { return array('status' => 'locked'); }
    $table = kau_table('outbox');
    try {
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $id), ARRAY_A);
        if (!$row) { throw new InvalidArgumentException('Delivery record missing.'); }
        if (in_array($row['status'], array('accepted','delivered','suppressed','expired','failed'), true)) { return array('status' => $row['status']); }
        if ($test) { return array('status' => 'test_no_send'); }
        if ($respect_backoff && (int)$row['next_attempt']>time()) { return array('status'=>'backoff'); }
        $next_attempt = 0;
        if (in_array($row['status'], array('sending','uncertain'), true)) {
            // Expiration or revoked consent cannot establish whether an earlier send was accepted.
            // Preserve ambiguity across editions; reconciliation is read-only and never sends here.
            try { $result = apply_filters('kau_email_reconcile', null, $row, array('deadline'=>min($deadline??time()+20,time()+20))); }
            catch (Throwable $e) { if ($e instanceof Error) { throw $e; } $result = null; }
            $status = is_array($result) ? ($result['status'] ?? 'uncertain') : 'uncertain';
            if ($status === 'definitely_not_sent') {
                $status = $row['expires_at'] < kau_now() ? 'expired' : ((int)$row['attempts']>=3 ? 'failed' : 'queued');
                $next_attempt = $status==='queued' ? time()+30*(2**max(0,(int)$row['attempts']-1)) : 0;
            }
            elseif (!in_array($status,array('accepted','delivered'),true)) { $status = 'uncertain'; }
            if ($status==='uncertain') { $next_attempt=time()+900; }
        }
        elseif ($row['expires_at'] < kau_now()) { $status = 'expired'; }
        elseif (!kau_delivery_ready($row['stream'])) { $wpdb->update($table,array('next_attempt'=>time()+900,'updated_at'=>kau_now()),array('id'=>$id)); return array('status' => 'blocked_provider_checks'); }
        else {
            $policy = apply_filters('kau_recipient_policy', null, $row);
            if (!is_array($policy) || ($policy['verified_opt_in'] ?? false) !== true || ($policy['suppressed'] ?? true) !== false
                || ($policy['stream'] ?? '') !== $row['stream'] || ($policy['frequency_eligible'] ?? false) !== true
                || ($policy['events_eligible'] ?? false) !== true) { $status = 'suppressed'; }
            else {
                if ((int)$row['attempts']>=3) { $wpdb->update($table,array('status'=>'failed','updated_at'=>kau_now()),array('id'=>$id)); return array('status'=>'failed'); }
                if ($wpdb->update($table, array('status'=>'sending','attempts'=>(int)$row['attempts']+1,'updated_at'=>kau_now()),array('id'=>$id))!==1) {
                    kau_exception('email',$row['delivery_key'],'Sending checkpoint could not be stored; no transport called.'); return array('status'=>'blocked_durable_state');
                }
                try { $result = apply_filters('kau_email_transport', null, $row, $policy, array('deadline'=>min($deadline??time()+20,time()+20))); }
                catch (Throwable $e) { if ($e instanceof Error) { throw $e; } $result = null; }
                $status = is_array($result) ? ($result['status'] ?? 'uncertain') : 'uncertain';
                // Transport errors alone cannot establish that an email was not accepted.
                if ($status==='definitely_not_sent') {
                    $status = (int)$row['attempts']+1>=3 ? 'failed' : 'queued';
                    $next_attempt = $status==='queued' ? time()+30*(2**(int)$row['attempts']) : 0;
                } elseif (!in_array($status,array('accepted','delivered'),true)) { $status = 'uncertain'; }
                if ($status==='uncertain') { $next_attempt=time()+30; }
            }
        }
        if (!in_array($status, array('queued','accepted','delivered','uncertain','suppressed','expired','failed'), true)) { $status = 'uncertain'; }
        $provider_id = isset($result['provider_id']) && is_string($result['provider_id']) ? substr($result['provider_id'], 0, 191) : $row['provider_id'];
        if (in_array($status, array('accepted','delivered'), true) && !$provider_id) { $status = 'uncertain'; $next_attempt=time()+900; }
        $saved=$wpdb->update($table,array('status'=>$status,'provider_id'=>$provider_id,'next_attempt'=>$next_attempt,'updated_at'=>kau_now()),array('id'=>$id));
        $persisted=$wpdb->get_row($wpdb->prepare("SELECT status,provider_id,next_attempt FROM $table WHERE id=%d",$id),ARRAY_A);
        if ($saved===false || !$persisted || $persisted['status']!==$status || $persisted['provider_id']!==$provider_id || (int)$persisted['next_attempt']!==$next_attempt) {
            kau_exception('email',$row['delivery_key'],'Provider outcome could not be persisted; reconcile the sending checkpoint before retrying.'); return array('status'=>'uncertain');
        }
        if ($status==='failed') { kau_exception('email',$row['delivery_key'],'Three confirmed-unsent attempts failed; affected delivery held for operator review.'); }
        return array('status' => $status);
    } finally { kau_unlock('email-' . (int) $id, $token); }
}

/** Recovery runs independently of a completed edition job, without replaying the newsletter. */
function kau_drain_outbox($test=false,$deadline=null) {
    global $wpdb;
    if (!kau_enabled('jobs')) { return array('status'=>'disabled'); }
    $deadline=min($deadline??time()+60,time()+60); $token=kau_lock('outbox-drain',90);
    if (!$token) { return array('status'=>'locked'); }
    $counts=array();
    try {
        $ids=$wpdb->get_col($wpdb->prepare('SELECT id FROM '.kau_table('outbox')." WHERE status IN ('queued','sending','uncertain') AND next_attempt<=%d ORDER BY updated_at,id LIMIT 50",time()));
        foreach ($ids as $id) {
            if (time()>$deadline-10) { return array('status'=>'bounded','outcomes'=>$counts); }
            try{$outcome=kau_send_outbox((int)$id,$test,true,$deadline);$name=$outcome['status'];}
            catch(Throwable $e){
                $row=$wpdb->get_row($wpdb->prepare('SELECT stream,status FROM '.kau_table('outbox').' WHERE id=%d',$id),ARRAY_A);
                if($row){$state=in_array($row['status'],array('sending','uncertain'),true)?'uncertain':'failed';$wpdb->update(kau_table('outbox'),array('status'=>$state,'next_attempt'=>time()+900,'updated_at'=>kau_now()),array('id'=>$id));$paused=get_option('kau_email_paused',array());$paused[$row['stream']]='code_defect_requires_operator';update_option('kau_email_paused',$paused,false);}
                kau_exception('email','outbox-'.$id,'Unexpected delivery code defect; affected stream held for operator repair. Prior send uncertainty retained.');$name='code_defect_held';
            }
            $counts[$name]=($counts[$name]??0)+1;
        }
        return array('status'=>'completed','test'=>$test,'outcomes'=>$counts);
    } finally { kau_unlock('outbox-drain',$token); }
}
