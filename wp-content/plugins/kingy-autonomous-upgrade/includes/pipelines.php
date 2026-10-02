<?php
if (!defined('ABSPATH')) { exit; }
add_filter('kau_job_youtube', 'kau_youtube_job', 20, 2);
foreach (array('launches','priority_changes','catalog_freshness') as $kau_name) {
    add_filter('kau_job_' . $kau_name, function ($result, $context) use ($kau_name) { return $result ?: kau_sources_job($kau_name, $context); }, 20, 2);
}
add_filter('kau_job_brief', 'kau_brief_job', 20, 2);
add_filter('kau_job_stack_digest', 'kau_digest_job', 20, 2);
add_filter('kau_job_health', function ($result, $context) {
    if ($result) { return $result; }
    $health = apply_filters('kau_existing_system_health', null, $context);
    return is_array($health) ? $health : array('status'=>'blocked','code'=>'existing_health_adapter_missing');
}, 20, 2);

function kau_youtube_job($result, $context) {
    if ($result) { return $result; }
    $identity = get_option('kau_verified_channel', array());
    if (empty($identity['id']) || empty($identity['evidence_reference'])) { return array('status'=>'blocked','code'=>'verified_channel_identity_missing'); }
    $response = wp_safe_remote_get('https://www.youtube.com/feeds/videos.xml?channel_id=' . rawurlencode($identity['id']), array('timeout'=>20,'redirection'=>0,'limit_response_size'=>2000000));
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response)!==200) { return array('status'=>'transient_failure','code'=>'official_youtube_feed_unavailable'); }
    $videos = array_slice(kau_parse_youtube_feed(wp_remote_retrieve_body($response), $identity['id']), 0, 5); $prepared=0;
    foreach ($videos as $video) {
        if (time()>$context['deadline']) { return array('status'=>'transient_failure','code'=>'bounded_run_limit'); }
        $evidence=apply_filters('kau_prepare_video', null, $video, $context);
        if (!is_array($evidence)) { kau_exception('companions',$video['video_id'],'Authorized transcript/artifacts unavailable. Prepare this video from retained evidence.'); continue; }
        try {
            $validated=kau_validate_companion($evidence);
            if ($validated['video_id']!==$video['video_id'] || $validated['published_at']!==$video['published_at'] || $validated['channel_id']!==$video['channel_id']) { throw new InvalidArgumentException('Feed identity mismatch.'); }
            if (apply_filters('kau_companion_evidence_check', false, $validated)!==true || apply_filters('kau_companion_technical_check', false, $validated)!==true) { throw new RuntimeException('Companion evidence and technical gates required in test mode too.'); }
            if (!$context['test']) { kau_publish_companion($evidence); }
            $prepared++;
        } catch (Throwable $e) { kau_exception('companions',$video['video_id'],'Companion validation held this record; correct source evidence or technical checks.'); }
    }
    return array('status'=>'completed','code'=>$prepared<count($videos)?'companion_exceptions_recorded':'companions_prepared','checked'=>count($videos),'qualified'=>$prepared);
}

function kau_sources_job($stream, $context) {
    // The canonical source engine already has a maintained registry and fetch policies.
    $batch=apply_filters('kau_collect_existing_sources', null, $stream, $context);
    if (!is_array($batch) || !isset($batch['checked'],$batch['failed'],$batch['verified_changes'])) { return array('status'=>'blocked','code'=>'canonical_source_adapter_missing'); }
    if ((int)$batch['checked']===0) { return array('status'=>'blocked','code'=>'source_checking_not_established'); }
    $qualified=0;$held=0;
    foreach (array_slice($batch['verified_changes'],0,50) as $change) {
        if (time()>$context['deadline']) { return array('status'=>'transient_failure','code'=>'bounded_run_limit'); }
        try { kau_check_canonical_change($change); if (!$context['test']) { kau_admit_change($change); } $qualified++; }
        catch (Throwable $e) { $held++;kau_exception('sources',$change['event_key']??'malformed','Unsupported or conflicting observation was held.'); }
    }
    if ((int)$batch['failed']>0) {
        kau_exception('sources',$stream,'Source checking failed for part of this batch; do not label it no qualifying developments.');
        $code='source_checking_failed';
    } else { $code=$held?'verification_exceptions_recorded':($qualified?'verified_developments':'no_qualifying_developments'); }
    return array('status'=>(int)$batch['failed']===(int)$batch['checked']?'transient_failure':'completed','code'=>$code,'checked'=>(int)$batch['checked'],'qualified'=>$qualified);
}

function kau_current_changes($days=14) {
    $cutoff=gmdate('Y-m-d\TH:i:s\Z',time()-$days*86400);
    return array_values(array_filter(kau_changes(),function($change)use($cutoff){return $change['published_at']>=$cutoff;}));
}

function kau_frozen_brief($key) {
    if (!is_string($key) || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$key)) { throw new InvalidArgumentException('Edition date key required.'); }
    $old=get_page_by_path('brief/edition-'.$key);
    if (!$old) { return null; }
    if (get_post_meta($old->ID,'_kau_edition_key',true)!==$key) { throw new RuntimeException('Archive path belongs to unrelated content.'); }
    $payload=get_post_meta($old->ID,'_kau_edition_payload',true);
    if ($old->post_status!=='publish' || !is_array($payload) || empty($payload['event_keys']) || get_post_meta($old->ID,'_kau_edition_content_sha256',true)!==hash('sha256',$old->post_content)) {
        throw new RuntimeException('Frozen edition needs operator reconciliation.');
    }
    return $payload;
}

function kau_brief_archive($edition, $key) {
    global $wpdb;$token=kau_lock('edition-'.$key);if(!$token){throw new RuntimeException('Edition is locked.');}
    $id=0;$transaction=false;
    try {
    $frozen=kau_frozen_brief($key);if($frozen){return $frozen['archive_url'];}
    $parent=get_page_by_path('brief');
    if (!$parent) { throw new RuntimeException('Existing Brief archive parent missing.'); }
    $slug='edition-'.$key;
    if (empty($edition['event_keys']) || !is_array($edition['sections']) || !$edition['sections']) { throw new InvalidArgumentException('Validated edition events and sections required.'); }
    $html='<p>'.esc_html($edition['date']).'</p>';
    foreach($edition['sections'] as $section){$html.='<h2>'.esc_html($section['title']).'</h2>';foreach($section['items'] as $item){$html.='<h3><a href="'.esc_url(kau_url($item['url'])).'">'.esc_html($item['title']).'</a></h3><p>'.esc_html($item['summary']).'</p>';if(!empty($item['source_url'])){$html.='<p><a href="'.esc_url(kau_url($item['source_url'])).'">Primary source</a> · Evidence '.esc_html($item['evidence_date']).'</p>';}}}
    if($wpdb->query('START TRANSACTION')===false){throw new RuntimeException('Archive checkpoint unavailable.');}$transaction=true;
    $post=array('post_type'=>'page','post_parent'=>$parent->ID,'post_name'=>$slug,'post_title'=>'The Kingy Brief — '.$edition['date'],'post_content'=>$html,'post_status'=>'draft');
    $id=wp_insert_post($post,true);if(is_wp_error($id)){throw new RuntimeException('Archive write failed.');}
    $edition['archive_url']=get_permalink($id);
    foreach(array('_kau_edition_key'=>$key,'_kau_edition_payload'=>$edition,'_kau_edition_content_sha256'=>hash('sha256',get_post($id)->post_content)) as $meta=>$value){
        if(update_post_meta($id,$meta,$value)===false){throw new RuntimeException('Frozen edition evidence write failed.');}
    }
    $published=wp_update_post(array('ID'=>$id,'post_status'=>'publish'),true);
    if(is_wp_error($published)||get_post_status($id)!=='publish'){throw new RuntimeException('Archive publication held.');}
    $edition['archive_url']=get_permalink($id);update_post_meta($id,'_kau_edition_payload',$edition);
    if(get_post_meta($id,'_kau_edition_payload',true)!==$edition){throw new RuntimeException('Published canonical URL could not be frozen.');}
    if($wpdb->query('COMMIT')===false){throw new RuntimeException('Archive commit failed.');}$transaction=false;
    return get_permalink($id);
    }catch(Throwable $e){if($transaction){$wpdb->query('ROLLBACK');if(is_int($id)&&$id){clean_post_cache($id);}}throw $e;}
    finally{kau_unlock('edition-'.$key,$token);}
}

function kau_brief_job($result,$context){
    global $wpdb;
    if($result){return $result;}
    $key=substr($context['slot']['scheduled_at'],0,10);$changes=kau_current_changes();
    $edition=kau_frozen_brief($key);
    if(!$edition){$extras=apply_filters('kau_brief_evidence_extras',array(),$context);$edition=kau_build_brief($changes,$extras,$key);$edition['event_keys']=array_column($changes,'event_key');if(!$edition['event_keys']){$edition['event_keys']=array('edition:'.$context['slot']['key']);}}
    if(!$edition['sections']){return array('status'=>'blocked','code'=>'current_verified_edition_evidence_missing');}
    if($context['test']){return array('status'=>'completed','code'=>'edition_prepared_no_send','qualified'=>count($changes));}
    if(!kau_delivery_ready('brief')){return array('status'=>'blocked','code'=>'brief_provider_checks_missing');}
    $recipients=apply_filters('kau_eligible_recipients',null,'brief',$context);
    if(!is_array($recipients)){return array('status'=>'blocked','code'=>'existing_opt_in_registry_missing');}
    if(count($recipients)>500){return array('status'=>'blocked','code'=>'provider_bulk_adapter_required');}
    kau_brief_archive($edition,$key);$edition=kau_frozen_brief($key);
    $queued=0;
    foreach(array_slice($recipients,0,500) as $recipient){
        if(time()>$context['deadline']-10){return array('status'=>'transient_failure','code'=>'delivery_batch_resume_required','qualified'=>$queued);}
        $delivery_key='brief:'.hash('sha256',$recipient['ref'].':'.$context['slot']['key']);$prior=$wpdb->get_var($wpdb->prepare('SELECT id FROM '.kau_table('outbox').' WHERE delivery_key=%s',$delivery_key));
        if($prior){kau_send_outbox((int)$prior,false,true,$context['deadline']);$queued++;continue;}
        $id=kau_queue_email('brief',$recipient['ref'],$context['slot']['key'],$edition,gmdate('Y-m-d\TH:i:s\Z',strtotime($context['slot']['scheduled_at'])+3600));
        kau_send_outbox($id,false,true,$context['deadline']);$queued++;
        if(time()>$context['deadline']-10){return array('status'=>'transient_failure','code'=>'delivery_batch_resume_required','qualified'=>$queued);}
    }
    return array('status'=>'completed','code'=>'edition_outbox_processed','qualified'=>$queued);
}

function kau_digest_job($result,$context){
    global $wpdb;
    if($result){return $result;}
    if($context['test']){return array('status'=>'completed','code'=>'digest_policy_test_no_send','qualified'=>count(kau_current_changes(1)));}
    if(!kau_delivery_ready('stack_digest')){return array('status'=>'blocked','code'=>'digest_provider_checks_missing');}
    $recipients=apply_filters('kau_eligible_recipients',null,'stack_digest',$context);
    if(!is_array($recipients)){return array('status'=>'blocked','code'=>'existing_follow_registry_missing');}
    if(count($recipients)>500){return array('status'=>'blocked','code'=>'provider_bulk_adapter_required');}
    // Verification may reveal an older material change that is new to this user's stack.
    $cutoff=gmdate('Y-m-d\TH:i:s\Z',time()-86400);$changes=array_values(array_filter(kau_changes(),function($c)use($cutoff){return $c['verified_at']>=$cutoff;}));$queued=0;
    foreach(array_slice($recipients,0,500) as $recipient){
        if(time()>$context['deadline']-10){return array('status'=>'transient_failure','code'=>'delivery_batch_resume_required','qualified'=>$queued);}
        $delivery_key='stack_digest:'.hash('sha256',$recipient['ref'].':'.$context['slot']['key']);$prior=$wpdb->get_var($wpdb->prepare('SELECT id FROM '.kau_table('outbox').' WHERE delivery_key=%s',$delivery_key));
        if($prior){kau_send_outbox((int)$prior,false,true,$context['deadline']);$queued++;continue;}
        $relevant=array_values(array_filter($changes,function($c)use($recipient){return in_array($c['product_id'],$recipient['product_ids']??array(),true);}));
        $relevant=kau_unseen_recipient_changes($recipient['ref'],$relevant);
        if(!$relevant){continue;}
        $payload=kau_build_digest($relevant,substr(kau_now(),0,10));$payload['archive_url']=home_url('/ai-stack-change-radar/');
        $id=kau_queue_email('stack_digest',$recipient['ref'],$context['slot']['key'],$payload,gmdate('Y-m-d\TH:i:s\Z',strtotime($context['slot']['scheduled_at'])+3600));
        kau_send_outbox($id,false,true,$context['deadline']);$queued++;
        if(time()>$context['deadline']-10){return array('status'=>'transient_failure','code'=>'delivery_batch_resume_required','qualified'=>$queued);}
    }
    return array('status'=>'completed','code'=>$queued?'relevant_digest_outbox_processed':'no_relevant_changes','qualified'=>$queued);
}

function kau_build_digest($changes,$date){
    $items=array();$events=array();
    foreach($changes as $c){$c=kau_validate_change($c);$events[]=$c['event_key'];$items[]=array('title'=>$c['title'],'summary'=>$c['summary'].' Why it matters: '.$c['why_it_matters'],'url'=>$c['guide_url']?:$c['source_url'],'source_url'=>$c['source_url'],'evidence_date'=>$c['verified_at']);}
    if(!$items){throw new InvalidArgumentException('Relevant verified changes required.');}
    return array('title'=>'Your Kingy AI Stack changes','date'=>$date,'sections'=>array(array('title'=>'Changes to your followed products','items'=>$items)),'event_keys'=>$events);
}

function kau_unseen_recipient_changes($recipient_ref,$changes){
    global $wpdb;$seen=array();
    $rows=$wpdb->get_col($wpdb->prepare('SELECT payload FROM '.kau_table('outbox')." WHERE recipient_ref=%s AND stream='stack_digest' AND status IN ('sending','uncertain','accepted','delivered')",$recipient_ref));
    foreach($rows as $row){$p=json_decode($row,true);foreach(($p['event_keys']??array()) as $key){$seen[$key]=true;}}
    return array_values(array_filter($changes,function($c)use($seen){return !isset($seen[$c['event_key']]);}));
}
