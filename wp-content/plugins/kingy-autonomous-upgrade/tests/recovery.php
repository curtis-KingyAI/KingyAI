<?php
/** Disposable local acceptance only. No provider API, real recipient or paid inference. */
if(!defined('ABSPATH')||wp_get_environment_type()!=='local'){throw new RuntimeException('Disposable local WordPress required.');}
global $wpdb;$GLOBALS['kau_recovery_checks']=array();$run=bin2hex(random_bytes(6));
function kau_recovery_check($name,$ok){if(!$ok){throw new RuntimeException('FAILED: '.$name);}$GLOBALS['kau_recovery_checks'][]=$name;}
function kau_recovery_throws($fn){try{$fn();return false;}catch(Throwable $e){return true;}}
function kau_recovery_due($id){global $wpdb;$wpdb->update(kau_table('outbox'),array('next_attempt'=>0),array('id'=>$id));}
function kau_recovery_request($method,$path,$body=null){$r=new WP_REST_Request($method,'/kingy-upgrade/v1/'.$path);if($body!==null){$r->set_header('Content-Type','application/json');$r->set_body(wp_json_encode($body));}return rest_do_request($r);}
function kau_recovery_key(){for($i=0;$i<100;$i++){$key=random_int(1900,2000).'-'.sprintf('%02d',random_int(1,12)).'-'.sprintf('%02d',random_int(1,28));if(!get_page_by_path('brief/edition-'.$key)){return $key;}}throw new RuntimeException('Fixture archive key unavailable.');}
$flags=array('workflow'=>true,'stack'=>true,'changes'=>true,'jobs'=>true,'email'=>true,'sponsor'=>true);update_option('kau_feature_flags',$flags);delete_option('kau_email_paused');
try {
$ready=array_fill_keys(array('provider_sandbox','rendering','archive_links','preferences','suppression','unsubscribe','reconciliation','account_restrictions'),true);update_option('kau_email_checks',array('brief'=>$ready,'stack_digest'=>$ready));
kau_recovery_check('schema v2 outbox recovery fields exist',get_option('kau_schema_version')==='2'&&$wpdb->get_var('SHOW COLUMNS FROM '.kau_table('outbox')." LIKE 'attempts'")==='attempts'&&$wpdb->get_var('SHOW COLUMNS FROM '.kau_table('outbox')." LIKE 'next_attempt'")==='next_attempt');
$oldSlots=kau_due_slots(new DateTimeImmutable('2026-10-03T09:00:00Z'),new DateTimeImmutable('2026-10-02T16:00:00Z'));
$missed=array_values(array_filter($oldSlots,function($s){return $s['key']==='brief:2026-10-02:0900';}));
kau_recovery_check('Friday missed run survives local midnight without replay',count($missed)===1&&$missed[0]['status']==='missed');
$bounded=kau_due_slots(new DateTimeImmutable('2026-10-03T09:00:00Z'),new DateTimeImmutable('2026-01-01T00:00:00Z'));
kau_recovery_check('long outage slot recovery is bounded to three local dates',count(array_unique(array_map(function($s){return explode(':',$s['key'])[1];},$bounded)))===3);
$slot=array('name'=>'recovery_fixture','key'=>'recovery-crash-'.$run,'status'=>'due','scheduled_at'=>kau_now());
$wpdb->insert(kau_table('jobs'),array('slot_key'=>$slot['key'],'name'=>$slot['name'],'status'=>'running','attempts'=>3,'started_at'=>kau_now(),'detail'=>'synthetic abandoned worker'));
$jobCalls=0;add_filter('kau_job_recovery_fixture',function()use(&$jobCalls){$jobCalls++;return array('status'=>'completed');});
kau_recovery_check('three abandoned job attempts require operator repair',kau_run_slot($slot,true)['status']==='failed'&&$jobCalls===0);

$payload=array('title'=>'STAGING recovery fixture','date'=>substr(kau_now(),0,10),'sections'=>array(array('title'=>'Fixture','items'=>array(array('title'=>'Retained fixture','summary'=>'Synthetic evidence only','url'=>'https://kingy.ai/make-this/')))),'archive_url'=>'https://kingy.ai/brief/','event_keys'=>array('recovery:'.$run));
$expire=gmdate('Y-m-d\TH:i:s\Z',time()+3600);$ref='staging-recovery:'.$run;
$policy=function($default,$row)use($ref){return array('verified_opt_in'=>str_starts_with($row['recipient_ref'],$ref),'suppressed'=>false,'stream'=>$row['stream'],'frequency_eligible'=>true,'events_eligible'=>true);};add_filter('kau_recipient_policy',$policy,10,2);
$calls=0;$transport=function($default,$row)use(&$calls){$calls++;return array('status'=>'accepted','provider_id'=>'staging-recovery-'.$row['id']);};add_filter('kau_email_transport',$transport,10,2);
$id=kau_queue_email('brief',$ref.':queued','policy-one',$payload,$expire);
$reordered=array_reverse($payload,true);kau_recovery_check('semantic payload key order retains one delivery',kau_queue_email('brief',$ref.':queued','policy-one',$reordered,$expire)===$id);
$conflict=$payload;$conflict['title']='Conflicting edition';kau_recovery_check('delivery policy conflict does not overwrite original',kau_recovery_throws(function()use($ref,$conflict,$expire){kau_queue_email('brief',$ref.':queued','policy-one',$conflict,$expire);})&&json_decode($wpdb->get_var($wpdb->prepare('SELECT payload FROM '.kau_table('outbox').' WHERE id=%d',$id)),true)['title']===$payload['title']);
$missInsert=function($query){if(str_starts_with($query,'INSERT IGNORE INTO '.kau_table('outbox'))){return 'SELECT 0';}return $query;};add_filter('query',$missInsert);
kau_recovery_check('missing durable outbox insert throws before delivery',kau_recovery_throws(function()use($ref,$payload,$expire){kau_queue_email('brief',$ref.':missing','missing',$payload,$expire);}));remove_filter('query',$missInsert);
$blockCheckpoint=function($query){return str_contains($query,'`status` = \'sending\'')&&str_contains($query,kau_table('outbox'))?preg_replace('/WHERE.*$/s','WHERE id=-1',$query):$query;};add_filter('query',$blockCheckpoint);
kau_recovery_check('sending checkpoint failure never calls transport',kau_send_outbox($id)['status']==='blocked_durable_state'&&$calls===0);remove_filter('query',$blockCheckpoint);
$before=$wpdb->get_row($wpdb->prepare('SELECT status,attempts FROM '.kau_table('outbox').' WHERE id=%d',$id),ARRAY_A);kau_drain_outbox(true);$after=$wpdb->get_row($wpdb->prepare('SELECT status,attempts FROM '.kau_table('outbox').' WHERE id=%d',$id),ARRAY_A);
kau_recovery_check('test outbox drain changes neither delivery state nor transport',$before===$after&&$calls===0);
kau_drain_outbox();kau_recovery_check('queued email recovers without rerunning newsletter job',$wpdb->get_var($wpdb->prepare('SELECT status FROM '.kau_table('outbox').' WHERE id=%d',$id))==='accepted'&&$calls===1);
kau_drain_outbox();kau_recovery_check('accepted recovery delivery cannot resend',$calls===1);
$brokenOutcome=kau_queue_email('brief',$ref.':outcome','policy-two',$payload,$expire);
$missOutcome=function($query){return str_contains($query,'`status` = \'accepted\'')&&str_contains($query,kau_table('outbox'))?preg_replace('/WHERE.*$/s','WHERE id=-1',$query):$query;};add_filter('query',$missOutcome);
kau_recovery_check('unpersisted provider acceptance retains sending checkpoint',kau_send_outbox($brokenOutcome)['status']==='uncertain'&&$wpdb->get_var($wpdb->prepare('SELECT status FROM '.kau_table('outbox').' WHERE id=%d',$brokenOutcome))==='sending');remove_filter('query',$missOutcome);
$acceptedCalls=$calls;$reconcile=function(){return array('status'=>'accepted','provider_id'=>'staging-reconciled-recovery');};add_filter('kau_email_reconcile',$reconcile);
kau_recovery_check('sending checkpoint reconciles after outcome-write interruption',kau_send_outbox($brokenOutcome)['status']==='accepted'&&$calls===$acceptedCalls);remove_filter('kau_email_reconcile',$reconcile);
remove_filter('kau_email_transport',$transport,10);
$retry=kau_queue_email('brief',$ref.':retry','policy-three',$payload,$expire);$retryCalls=0;$notSent=function()use(&$retryCalls){$retryCalls++;return array('status'=>'definitely_not_sent');};add_filter('kau_email_transport',$notSent);
kau_recovery_check('confirmed unsent transport schedules backoff',kau_send_outbox($retry)['status']==='queued'&&$retryCalls===1);
kau_recovery_check('backoff forbids immediate repeat transport',kau_send_outbox($retry)['status']==='backoff'&&$retryCalls===1);
kau_recovery_due($retry);kau_recovery_check('second confirmed-unsent retry remains bounded',kau_send_outbox($retry)['status']==='queued'&&$retryCalls===2);
kau_recovery_due($retry);kau_recovery_check('third confirmed-unsent failure stops retry',kau_send_outbox($retry)['status']==='failed'&&$retryCalls===3);kau_send_outbox($retry);kau_recovery_check('failed email cannot repeat under same policy',$retryCalls===3);remove_filter('kau_email_transport',$notSent);
$unknown=kau_queue_email('brief',$ref.':unclear','policy-four',$payload,$expire);$unproven=function(){return array('status'=>'failed');};add_filter('kau_email_transport',$unproven);kau_recovery_check('unproven provider failure remains uncertain',kau_send_outbox($unknown)['status']==='uncertain');remove_filter('kau_email_transport',$unproven);
kau_recovery_due($unknown);kau_drain_outbox();kau_recovery_check('unreconciled uncertainty remains held during automatic recovery',$wpdb->get_var($wpdb->prepare('SELECT status FROM '.kau_table('outbox').' WHERE id=%d',$unknown))==='uncertain');
$paused=kau_queue_email('brief',$ref.':blocked','policy-five',$payload,$expire);$mailFlags=get_option('kau_feature_flags');$mailFlags['email']=false;update_option('kau_feature_flags',$mailFlags);
kau_recovery_check('provider-disabled row backs off without blocking eligible queue forever',kau_send_outbox($paused)['status']==='blocked_provider_checks'&&(int)$wpdb->get_var($wpdb->prepare('SELECT next_attempt FROM '.kau_table('outbox').' WHERE id=%d',$paused))>time());$mailFlags['email']=true;update_option('kau_feature_flags',$mailFlags);
$defect=kau_queue_email('brief',$ref.':defect','policy-six',$payload,$expire);$unaffected=kau_queue_email('stack_digest',$ref.':unaffected','policy-seven',$payload,$expire);
remove_filter('kau_recipient_policy',$policy,10);$throwPolicy=function($default,$row)use($ref){if($row['recipient_ref']===$ref.':defect'){throw new RuntimeException('Synthetic policy code defect');}return array('verified_opt_in'=>str_starts_with($row['recipient_ref'],$ref),'suppressed'=>false,'stream'=>$row['stream'],'frequency_eligible'=>true,'events_eligible'=>true);};add_filter('kau_recipient_policy',$throwPolicy,10,2);add_filter('kau_email_transport',$transport,10,2);
kau_drain_outbox();kau_recovery_check('novel code defect pauses only its stream',!kau_delivery_ready('brief')&&kau_delivery_ready('stack_digest')&&$wpdb->get_var($wpdb->prepare('SELECT status FROM '.kau_table('outbox').' WHERE id=%d',$unaffected))==='accepted');
remove_filter('kau_recipient_policy',$throwPolicy,10);remove_filter('kau_email_transport',$transport,10);delete_option('kau_email_paused');
add_filter('kau_recipient_policy',$policy,10,2);
$transportDefect=kau_queue_email('brief',$ref.':transport-defect','transport-error',$payload,$expire);
$healthyDigest=kau_queue_email('stack_digest',$ref.':transport-unaffected','transport-error',$payload,$expire);
$throwTransport=function($default,$row)use($ref){if($row['recipient_ref']===$ref.':transport-defect'){throw new TypeError('Synthetic transport adapter defect');}return array('status'=>'accepted','provider_id'=>'staging-healthy-'.$row['id']);};
add_filter('kau_email_transport',$throwTransport,10,2);kau_drain_outbox();
kau_recovery_check('transport code defect preserves uncertainty and isolates affected stream',!kau_delivery_ready('brief')&&kau_delivery_ready('stack_digest')&&$wpdb->get_var($wpdb->prepare('SELECT status FROM '.kau_table('outbox').' WHERE id=%d',$transportDefect))==='uncertain'&&$wpdb->get_var($wpdb->prepare('SELECT status FROM '.kau_table('outbox').' WHERE id=%d',$healthyDigest))==='accepted');
remove_filter('kau_email_transport',$throwTransport,10);remove_filter('kau_recipient_policy',$policy,10);delete_option('kau_email_paused');


$existingParent=get_page_by_path('brief');if(!$existingParent){wp_insert_post(array('post_type'=>'page','post_name'=>'brief','post_title'=>'STAGING Brief parent','post_status'=>'publish'));}
$key=kau_recovery_key();$payload['date']=$key;$url=kau_brief_archive($payload,$key);$frozen=kau_frozen_brief($key);$page=get_page_by_path('brief/edition-'.$key);$original=$page->post_content;
kau_recovery_check('archive retains immutable edition, event keys and actual URL',$frozen['event_keys']===$payload['event_keys']&&$frozen['archive_url']===$url&&get_permalink($page->ID)===$url);
$changed=$payload;$changed['sections'][0]['items'][0]['summary']='New arrival must not alter an already prepared edition';kau_brief_archive($changed,$key);
kau_recovery_check('edition retry preserves original archive and delivery payload',get_post($page->ID)->post_content===$original&&kau_frozen_brief($key)['sections']===$payload['sections']);
$unownedKey=kau_recovery_key();$parent=get_page_by_path('brief');$unowned=wp_insert_post(array('post_type'=>'page','post_parent'=>$parent->ID,'post_name'=>'edition-'.$unownedKey,'post_content'=>'Unrelated retained content','post_status'=>'publish'));
kau_recovery_check('archive path conflict preserves unrelated page',kau_recovery_throws(function()use($payload,$unownedKey){kau_brief_archive($payload,$unownedKey);})&&get_post($unowned)->post_content==='Unrelated retained content');
$fixtureProduct=kau_product_id(get_option('kau_staging_fixture_product'))['id'];$now=kau_now();$events=array();
for($i=0;$i<6;$i++){$events[]=array('product_id'=>'wp:kingy_ai_tool:'.$fixtureProduct,'event_key'=>'recovery:'.$run.':'.$i,'kind'=>'update','published_at'=>$now,'observed_at'=>$now,'verified_at'=>$now,'source_id'=>'staging-existing-source','source_url'=>'https://example.org/fixture','source_sha256'=>hash('sha256','Synthetic source'),'source_quote'=>'Synthetic source','title'=>'Fixture event '.$i,'summary'=>'Retained local observation '.$i,'why_it_matters'=>'Fixture relevance only','guide_url'=>'https://kingy.ai/make-this/');}
$digest=kau_build_digest($events,substr($now,0,10));
kau_recovery_check('digest does not mark undisplayed fourth through sixth events delivered',count($digest['sections'][0]['items'])===6&&count($digest['event_keys'])===6);
$slotPolicy='recovery-digest-'.$run;$onlyRecipient=function()use($ref,$fixtureProduct){return array(array('ref'=>$ref.':fixed-digest','product_ids'=>array('wp:kingy_ai_tool:'.$fixtureProduct)));};add_filter('kau_eligible_recipients',$onlyRecipient);add_filter('kau_recipient_policy',$policy,10,2);add_filter('kau_email_transport',$transport,10,2);
$fixedId=kau_queue_email('stack_digest',$ref.':fixed-digest',$slotPolicy,$digest+array('archive_url'=>'https://kingy.ai/ai-stack-change-radar/'),$expire);kau_send_outbox($fixedId);$priorCalls=$calls;$context=array('test'=>false,'deadline'=>time()+60,'slot'=>array('key'=>$slotPolicy,'scheduled_at'=>$now));kau_digest_job(null,$context);
kau_recovery_check('repeated digest job uses frozen policy without resending',$calls===$priorCalls);remove_filter('kau_eligible_recipients',$onlyRecipient);remove_filter('kau_recipient_policy',$policy,10);remove_filter('kau_email_transport',$transport,10);

$owner=username_exists('staging-user-a');$other=username_exists('staging-user-b');wp_set_current_user($owner);
$project=array('schemaVersion'=>1,'id'=>'recovery-project-'.$run,'name'=>'STAGING account project','serverRevision'=>0,'brief'=>array('product'=>'Bottle','audience'=>'Commuters','message'=>'Refill','format'=>'16:9','duration'=>20,'style'=>'Plain','budget'=>20,'currency'=>'USD','productId'=>'','referenceNote'=>''),'outline'=>'','shots'=>array(),'prompts'=>array(),'outlineBrief'=>null,'promptShots'=>null,'promptBrief'=>null,'budget'=>array('price'=>null,'unit'=>'second','attemptsPerShot'=>3,'usableFraction'=>.5,'reviewMinutesPerAttempt'=>2,'laborRate'=>0,'evidenceDate'=>'','sourceUrl'=>'','rateEventKey'=>''),'referenceMetadata'=>null);
$saved=kau_recovery_request('PUT','projects/'.$project['id'],$project);kau_recovery_check('native owner saves a validated resumable project',$saved->get_status()===200&&$saved->get_data()['revision']===1);
kau_recovery_check('stale revision receives useful conflict',kau_recovery_request('PUT','projects/'.$project['id'],$project)->get_status()===409);
$project['serverRevision']=1;$updated=kau_recovery_request('PUT','projects/'.$project['id'],$project);kau_recovery_check('current revision updates without duplication',$updated->get_status()===200&&$updated->get_data()['revision']===2);
$bad=$project;$bad['budget']['usableFraction']=0;kau_recovery_check('server rejects unusable budget assumption',kau_recovery_request('PUT','projects/'.$project['id'],$bad)->get_status()===400);
$bad=$project;$bad['budget']['sourceUrl']='javascript:alert(1)';kau_recovery_check('server rejects unsafe imported price URL',kau_recovery_request('PUT','projects/'.$project['id'],$bad)->get_status()===400);
$bad=$project;$bad['brief']['media']='data:image/png;base64,AAAA';kau_recovery_check('nested media is rejected on account save',kau_recovery_request('PUT','projects/'.$project['id'],$bad)->get_status()===400);
$bad=$project;$bad['id']='different-project-'.$run;kau_recovery_check('body cannot misbind another project identity',kau_recovery_request('PUT','projects/'.$project['id'],$bad)->get_status()===400);
$stack=array('schemaVersion'=>1,'products'=>array(array('id'=>'wp:kingy_ai_tool:'.$fixtureProduct,'category'=>'Video')),'projects'=>array($project),'preferences'=>array('plan'=>'Monthly','budget'=>'USD 20'));
kau_recovery_check('native owner saves stack and projects',kau_recovery_request('PUT','stack',$stack)->get_status()===200);
wp_set_current_user($other);kau_recovery_check('second user sees no first-user project',!in_array($project['id'],array_column(kau_recovery_request('GET','projects')->get_data(),'id'),true));
$foreignStack=kau_recovery_request('GET','stack')->get_data();kau_recovery_check('second user stack excludes first-user preferences',($foreignStack['preferences']['plan']??'')!=='Monthly');
kau_recovery_check('second user cannot overwrite first-user project',kau_recovery_request('PUT','projects/'.$project['id'],$project)->get_status()===404);
$otherProject=$project;$otherProject['id']='other-recovery-'.$run;$otherProject['serverRevision']=0;kau_recovery_request('PUT','projects/'.$otherProject['id'],$otherProject);
wp_set_current_user($owner);$accountLock=kau_lock('account-'.$owner);kau_recovery_check('account deletion respects concurrent write lock',kau_recovery_request('DELETE','stack')->get_status()===409);kau_unlock('account-'.$owner,$accountLock);
kau_recovery_check('account stack deletion succeeds',kau_recovery_request('DELETE','stack')->get_status()===200);
kau_recovery_check('deletion removes standalone owned projects too',$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.kau_table('projects').' WHERE owner_id=%d',$owner))==='0');
kau_recovery_check('deletion preserves another user and authentication',$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.kau_table('projects').' WHERE id=%s AND owner_id=%d',$otherProject['id'],$other))==='1'&&get_userdata($owner)!==false);
wp_set_current_user(1);$flags['email']=false;update_option('kau_feature_flags',$flags);delete_option('kau_email_paused');
$report=array('scope'=>'Disposable local WordPress with synthetic editions/recipients and fake providers; zero real sends','passed'=>count($GLOBALS['kau_recovery_checks']),'checks'=>$GLOBALS['kau_recovery_checks'],'observed_at'=>kau_now(),'production_touched'=>false);
file_put_contents('/var/www/html/recovery-test-results.json',wp_json_encode($report,JSON_PRETTY_PRINT));WP_CLI::log(wp_json_encode($report));
}finally{$flags['email']=false;update_option('kau_feature_flags',$flags);delete_option('kau_email_paused');}
