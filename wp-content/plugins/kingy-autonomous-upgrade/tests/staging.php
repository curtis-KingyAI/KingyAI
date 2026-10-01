<?php
/** Run only in disposable local WordPress: wp eval-file .../tests/staging.php */
if (!defined('ABSPATH') || wp_get_environment_type() !== 'local') { throw new RuntimeException('Disposable local staging required.'); }
global $wpdb;
$GLOBALS['kau_test_passed']=array();
function kau_test($name,$condition){if(!$condition){throw new RuntimeException('FAILED: '.$name);}$GLOBALS['kau_test_passed'][]=$name;}
function kau_throws($call){try{$call();return false;}catch(Throwable $e){return true;}}
update_option('kau_feature_flags',array('workflow'=>true,'stack'=>true,'changes'=>true,'jobs'=>true,'email'=>false,'sponsor'=>true),false);
kau_migrate();kau_migrate();
kau_test('idempotent additive migration',get_option('kau_schema_version')==='1');
$tool=wp_insert_post(array('post_type'=>'kingy_ai_tool','post_status'=>'publish','post_title'=>'STAGING FIXTURE — bottle-video tool'));
update_post_meta($tool,'_kingy_ali_last_verified','2026-09-01');
update_post_meta($tool,'_kingy_ali_pricing','Legacy fixture price');
update_post_meta($tool,'_kingy_ali_official_url','https://example.org/staging-tool');
update_post_meta($tool,'_kingy_ali_what_it_does','Synthetic source-backed generation tool for local acceptance testing only.');
wp_set_object_terms($tool,'ai-video-tools','kingy_launch_category');
$launch=wp_insert_post(array('post_type'=>'kingy_ai_launch','post_status'=>'publish','post_title'=>'STAGING FIXTURE launch'));
foreach(array('official_url'=>'https://example.org/staging-launch','launch_date'=>substr(kau_now(),0,10),'what_launched'=>'Synthetic generation tool for local testing.','who_it_is_for'=>'Video creators in a staging fixture.','pricing'=>'USD 0.12 per second (fixture)','kingy_verdict'=>'Fixture only, not a real Kingy test.','last_verified'=>substr(kau_now(),0,10),'sources'=>'https://example.org/staging-launch','related_article_url'=>'https://kingy.ai/make-this/','tool_profile'=>$tool) as $key=>$value){update_post_meta($launch,'_kingy_ali_'.$key,$value);}
wp_set_object_terms($launch,'ai-video-tools','kingy_launch_category');kingy_ali_entity_seo_recalculate($launch);
update_post_meta($tool,'_kingy_ali_latest_launch_id',$launch);kingy_ali_entity_seo_recalculate($tool);
kau_test('existing public quality gates hold incomplete synthetic launch',!kingy_ali_entity_seo_is_indexable($tool)&&get_post_status($launch)!=='publish');
$product='wp:kingy_ai_tool:'.$tool;
$now=kau_now();$body='STAGING FIXTURE: generated seconds cost USD 0.12.';
$change=array('product_id'=>$product,'event_key'=>'staging:price:'.$tool,'kind'=>'price','published_at'=>$now,'observed_at'=>$now,'verified_at'=>$now,
 'source_id'=>'staging-existing-source:1','source_url'=>'https://example.org/fixture-pricing','source_sha256'=>hash('sha256',$body),
 'title'=>'STAGING FIXTURE price change','summary'=>'Fixture generation rate is USD 0.12 per second.','why_it_matters'=>'A saved production budget may require revision.',
 'source_quote'=>$body,'guide_url'=>'https://kingy.ai/make-this/','price'=>array('amount'=>.12,'currency'=>'USD','unit'=>'second'));
add_filter('kau_verify_canonical_change',function($v,$c)use($body,$product){return array('supported'=>$c['source_quote']===$body,'official'=>true,'source_id'=>$c['source_id'],'source_sha256'=>hash('sha256',$body),'product_id'=>$product);},10,2);
$first=kau_admit_change($change);$second=kau_admit_change($change);
kau_test('verified change and idempotent rerun',$first['created']===true&&$second['created']===false);
kau_test('one durable event',$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.kau_table('changes').' WHERE event_key=%s',$change['event_key']))==='1');
kau_test('price date does not refresh other facts',get_post_meta($tool,'_kingy_ali_last_verified',true)==='2026-09-01');
$catalog=array_values(array_filter(kau_catalog('bottle-video'),function($p)use($product){return $p['id']===$product;}));
kau_test('workflow price comes from same change',$catalog[0]['price']['event_key']===$change['event_key']&&$catalog[0]['price']['amount']===.12);
kau_test('legacy companion live module uses same change',strpos(kingy_ali_render_kali_tool_pricing($tool),'0.12 USD per second')!==false);
kau_test('legacy module labels price date separately',strpos(kingy_ali_render_kali_tool_pricing($tool),'Other product facts have separate verification dates')!==false);
kau_test('stack relevance filter',count(kau_changes(array($product)))===1&&kau_changes(array('wp:kingy_ai_tool:999999'))===array());
kau_test('launch coverage uses same change',strpos(do_shortcode('[kingy_verified_changes]'),'STAGING FIXTURE price change')!==false);
$conflict=$change;$conflict['summary']='Unsupported replacement';kau_test('conflicting event held',kau_throws(function()use($conflict){kau_admit_change($conflict);}));
$bad=$change;$bad['event_key']='staging:unsupported';$bad['source_quote']='An unsupported fact';kau_test('unsupported source claim held',kau_throws(function()use($bad){kau_admit_change($bad);}));
$bad=$change;$bad['published_at']='2026-02-30T00:00:00Z';kau_test('invalid date rejected',kau_throws(function()use($bad){kau_validate_change($bad);}));
$bad=$change;$bad['observed_at']='2026-01-01T00:00:00Z';kau_test('conflicting evidence dates rejected',kau_throws(function()use($bad){kau_validate_change($bad);}));
$bad=$change;$bad['source_url']='https://user:secret@example.org';kau_test('credential-bearing source URL rejected',kau_throws(function()use($bad){kau_validate_change($bad);}));

$token=kau_lock('staging-lock',1);kau_test('expiring lock excludes another worker',$token&&kau_lock('staging-lock')===false);
kau_unlock('staging-lock','wrong-token');kau_test('lock ownership enforced',kau_lock('staging-lock')===false);
$wpdb->update(kau_table('locks'),array('expires'=>time()-1),array('name'=>'staging-lock'));$replacement=kau_lock('staging-lock');
kau_test('expired lock can recover',$replacement&&$replacement!==$token);kau_unlock('staging-lock',$replacement);

foreach(array('2026-03-06T17:00:00Z'=>'2026-03-06T17:00:00Z','2026-03-13T16:00:00Z'=>'2026-03-13T16:00:00Z','2026-11-06T17:00:00Z'=>'2026-11-06T17:00:00Z') as $date=>$expected){
 $slots=kau_due_slots(new DateTimeImmutable($date));$brief=array_values(array_filter($slots,function($s){return $s['name']==='brief';}));
 kau_test('Friday 09:00 Vancouver DST '.$date,count($brief)===1&&$brief[0]['scheduled_at']===$expected&&$brief[0]['status']==='due');
}
$slots=kau_due_slots(new DateTimeImmutable('2026-03-13T18:01:00Z'));$brief=array_values(array_filter($slots,function($s){return $s['name']==='brief';}));kau_test('expired Brief is a missed run',$brief[0]['status']==='missed');
$dstA=kau_due_slots(new DateTimeImmutable('2026-11-01T08:05:00Z'));$dstB=kau_due_slots(new DateTimeImmutable('2026-11-01T09:05:00Z'));
$one=function($rows){return array_values(array_filter($rows,function($s){return $s['name']==='health'&&str_ends_with($s['key'],':0100');}))[0]['key'];};
kau_test('fall-back repeated hour uses one durable slot',$one($dstA)===$one($dstB));
$ctx=array('test'=>true,'deadline'=>time()+240,'slot'=>array('key'=>'test','scheduled_at'=>$now));
$sourceFilter=function(){return array('checked'=>1,'failed'=>0,'verified_changes'=>array());};
add_filter('kau_collect_existing_sources',$sourceFilter);$zero=kau_sources_job('launches',$ctx);remove_filter('kau_collect_existing_sources',$sourceFilter);
kau_test('genuine source zero is distinct',$zero['code']==='no_qualifying_developments');
$failedFilter=function(){return array('checked'=>1,'failed'=>1,'verified_changes'=>array());};add_filter('kau_collect_existing_sources',$failedFilter);$failure=kau_sources_job('launches',$ctx);remove_filter('kau_collect_existing_sources',$failedFilter);
kau_test('unavailable source is not a zero',$failure['code']==='source_checking_failed'&&$failure['status']==='transient_failure');
kau_test('missing registry adapter is blocked',kau_sources_job('launches',$ctx)['status']==='blocked');
$heldFilter=function()use($bad){return array('checked'=>1,'failed'=>0,'verified_changes'=>array($bad));};add_filter('kau_collect_existing_sources',$heldFilter);
kau_test('unsupported test-mode observation is an exception, not a zero',kau_sources_job('launches',$ctx)['code']==='verification_exceptions_recorded');remove_filter('kau_collect_existing_sources',$heldFilter);
add_filter('kau_job_staging_retry',function(){return array('status'=>'transient_failure','code'=>'fixture_timeout');});
$slot=array('name'=>'staging_retry','key'=>'staging-retry-'.bin2hex(random_bytes(4)),'status'=>'due','scheduled_at'=>$now);
for($i=1;$i<=3;$i++){$out=kau_run_slot($slot,true);$wpdb->update(kau_table('jobs'),array('next_attempt'=>0),array('slot_key'=>$slot['key']));kau_test('bounded retry attempt '.$i,$out['status']===($i<3?'retry':'failed'));}
kau_test('duplicate failed invocation does not restart',kau_run_slot($slot,true)['duplicate']===true);
kau_test('discovery without channel identity blocked',kau_youtube_job(null,$ctx)['status']==='blocked');
$xml='<?xml version="1.0"?><feed xmlns="http://www.w3.org/2005/Atom" xmlns:yt="http://www.youtube.com/xml/schemas/2015"><yt:channelId>UCaaaaaaaaaaaaaaaaaaaaaa</yt:channelId><entry><yt:videoId>aaaaaaaaaaa</yt:videoId><yt:channelId>UCaaaaaaaaaaaaaaaaaaaaaa</yt:channelId><title>Older fixture</title><published>2026-09-20T00:00:00Z</published></entry><entry><yt:videoId>bbbbbbbbbbb</yt:videoId><yt:channelId>UCaaaaaaaaaaaaaaaaaaaaaa</yt:channelId><title>Newer fixture</title><published>2026-09-21T00:00:00Z</published></entry></feed>';
$videos=kau_parse_youtube_feed($xml,'UCaaaaaaaaaaaaaaaaaaaaaa');kau_test('official feed sorted by actual publication',$videos[0]['video_id']==='bbbbbbbbbbb');
kau_test('wrong channel rejected',kau_throws(function()use($xml){kau_parse_youtube_feed($xml,'UCbbbbbbbbbbbbbbbbbbbbbb');}));
kau_test('external-entity feed rejected',kau_throws(function(){kau_parse_youtube_feed('<!DOCTYPE foo><feed/>','UCaaaaaaaaaaaaaaaaaaaaaa');}));
kau_test('legacy unchecked sponsor status is unconfirmed',kingy_ali_companion_commercial_status($tool)==='unconfirmed');
update_post_meta($tool,'_kingy_sponsored',true);kau_test('legacy sponsor disclosure preserved',kingy_ali_companion_commercial_status($tool)==='sponsored');
$videoId=str_pad((string)$tool,11,'x',STR_PAD_LEFT);
$companion=array('video_id'=>$videoId,'channel_id'=>'UCaaaaaaaaaaaaaaaaaaaaaa','published_at'=>$now,'title'=>'STAGING companion','summary'=>'Synthetic retained summary','suits'=>'Fixture creators','limitations'=>'Fixture only, no real test','disclosure'=>'Commercial relationship unconfirmed','evidence_reference'=>'staging-only:retained-fixture','duration_seconds'=>60,'chapters'=>array(array('seconds'=>0,'title'=>'Fixture introduction')),'product_ids'=>array($product));
update_option('kau_verified_channel',array('id'=>$companion['channel_id'],'evidence_reference'=>'staging-fixture'));
kau_test('companion evidence adapter cannot be omitted',kau_throws(function()use($companion){kau_publish_companion($companion);}));
$pass=function(){return true;};add_filter('kau_companion_evidence_check',$pass);add_filter('kau_companion_technical_check',$pass);
kau_test('existing companion gates remain enforced',kau_throws(function()use($companion){kau_publish_companion($companion);}));
$existing=get_posts(array('post_type'=>'kingy_video','post_status'=>'draft','meta_key'=>'_kingy_youtube_video_id','meta_value'=>$videoId))[0];
// Simulate an already working record solely to exercise recovery; production quality gates are not disabled.
$wpdb->update($wpdb->posts,array('post_status'=>'publish','post_content'=>'Original retained evidence','post_name'=>'original-fixture-'.$tool),array('ID'=>$existing->ID));clean_post_cache($existing->ID);
$beforeMeta=get_post_meta($existing->ID);$beforeUrl=get_permalink($existing->ID);
kau_test('publication failure restores existing content and URL',kau_throws(function()use($companion){kau_publish_companion($companion);})&&get_post_status($existing->ID)==='publish'&&get_post($existing->ID)->post_content==='Original retained evidence'&&get_permalink($existing->ID)===$beforeUrl&&get_post_meta($existing->ID)===$beforeMeta);
$throwHook=function($value,$id,$key)use($existing){if($id===$existing->ID&&$key==='_kau_companion_evidence'){throw new RuntimeException('Synthetic hook defect');}return $value;};add_filter('update_post_metadata',$throwHook,10,3);
kau_test('throwing metadata hook also restores existing record',kau_throws(function()use($companion){kau_publish_companion($companion);})&&get_post_status($existing->ID)==='publish'&&get_post($existing->ID)->post_content==='Original retained evidence');remove_filter('update_post_metadata',$throwHook,10);
kau_test('companion rerun retains one platform identity',count(get_posts(array('post_type'=>'kingy_video','post_status'=>array('publish','draft'),'meta_key'=>'_kingy_youtube_video_id','meta_value'=>$videoId)))===1);
remove_filter('kau_companion_evidence_check',$pass);remove_filter('kau_companion_technical_check',$pass);delete_option('kau_verified_channel');

$edition=array('title'=>'STAGING FIXTURE email','date'=>substr($now,0,10),'sections'=>array(array('title'=>'Verified fixture','items'=>array(array('title'=>'Fixture change','summary'=>'Fixture only','url'=>'https://kingy.ai/make-this/')))),'archive_url'=>'https://kingy.ai/brief/','event_keys'=>array($change['event_key']));
$email=kau_render_email($edition,'https://example.org/unsubscribe','https://example.org/preferences');
kau_test('accessible email contains preferences, unsubscribe and archive',strpos($email,'lang="en"')!==false&&strpos($email,'Unsubscribe')!==false&&strpos($email,'Email preferences')!==false&&strpos($email,'Read on Kingy.ai')!==false);
$expire=gmdate('Y-m-d\TH:i:s\Z',time()+3600);$id=kau_queue_email('brief','staging-recipient:1','edition-fixture-'.$tool,$edition,$expire);
kau_test('email outbox unique per recipient policy',$id===kau_queue_email('brief','staging-recipient:1','edition-fixture-'.$tool,$edition,$expire));
kau_test('email provider gate closed',kau_send_outbox($id)['status']==='blocked_provider_checks');
kau_test('test invocation never sends',kau_send_outbox($id,true)['status']==='test_no_send');
$flags=get_option('kau_feature_flags');$flags['email']=true;update_option('kau_feature_flags',$flags);
$checks=array_fill_keys(array('provider_sandbox','rendering','archive_links','preferences','suppression','unsubscribe','reconciliation','account_restrictions'),true);update_option('kau_email_checks',array('brief'=>$checks,'stack_digest'=>$checks));
$policy=function($v,$row){return array('verified_opt_in'=>true,'suppressed'=>false,'frequency_eligible'=>true,'events_eligible'=>true,'stream'=>$row['stream']);};add_filter('kau_recipient_policy',$policy,10,2);
$calls=0;$transport=function()use(&$calls){$calls++;return array('status'=>'accepted','provider_id'=>'staging-provider-1');};add_filter('kau_email_transport',$transport);
kau_test('provider acceptance stored separately',kau_send_outbox($id)['status']==='accepted');kau_send_outbox($id);kau_test('duplicate invocation never resends',$calls===1);
remove_filter('kau_email_transport',$transport);
$uncertain=kau_queue_email('brief','staging-recipient:2','uncertain-fixture-'.$tool,$edition,$expire);$unknownCalls=0;
$unknown=function()use(&$unknownCalls){$unknownCalls++;throw new RuntimeException('Simulated ambiguous provider timeout');};add_filter('kau_email_transport',$unknown);
kau_test('ambiguous provider result held',kau_send_outbox($uncertain)['status']==='uncertain');kau_send_outbox($uncertain);kau_test('uncertain send held without reconciliation',$unknownCalls===1);remove_filter('kau_email_transport',$unknown);
$reconcile=function(){return array('status'=>'accepted','provider_id'=>'staging-reconciled');};add_filter('kau_email_reconcile',$reconcile);
kau_test('ambiguous send reconciles without retry',kau_send_outbox($uncertain)['status']==='accepted');remove_filter('kau_email_reconcile',$reconcile);
$expiredAmbiguous=kau_queue_email('brief','staging-recipient:5','expired-ambiguous-'.$tool,$edition,gmdate('Y-m-d\TH:i:s\Z',time()-60));$wpdb->update(kau_table('outbox'),array('status'=>'uncertain'),array('id'=>$expiredAmbiguous));
kau_test('expiration cannot erase an ambiguous earlier send',kau_send_outbox($expiredAmbiguous)['status']==='uncertain');
$malformedReconcile=function(){return array('status'=>'queued');};add_filter('kau_email_reconcile',$malformedReconcile);kau_test('unproven reconcile status cannot authorize a resend',kau_send_outbox($expiredAmbiguous)['status']==='uncertain');remove_filter('kau_email_reconcile',$malformedReconcile);
remove_filter('kau_recipient_policy',$policy);kau_test('revoked consent cannot erase earlier send uncertainty',kau_send_outbox($expiredAmbiguous)['status']==='uncertain');add_filter('kau_recipient_policy',$policy,10,2);
add_filter('kau_email_reconcile',$reconcile);kau_test('expired ambiguous result reconciles without another send',kau_send_outbox($expiredAmbiguous)['status']==='accepted');remove_filter('kau_email_reconcile',$reconcile);
$digestRef='staging-digest:'.$tool;$digestId=kau_queue_email('stack_digest',$digestRef,'day-one',$edition,$expire);$wpdb->update(kau_table('outbox'),array('status'=>'uncertain'),array('id'=>$digestId));
kau_test('uncertain digest event is held across daily policies',kau_unseen_recipient_changes($digestRef,array($change))===array());
$wpdb->update(kau_table('outbox'),array('status'=>'accepted'),array('id'=>$digestId));kau_test('accepted digest event cannot repeat on a later day',kau_unseen_recipient_changes($digestRef,array($change))===array());
$wpdb->update(kau_table('outbox'),array('status'=>'suppressed'),array('id'=>$digestId));kau_test('unsent suppressed digest does not falsely mark an event delivered',count(kau_unseen_recipient_changes($digestRef,array($change)))===1);
$bulk=function(){return array_fill(0,501,array('ref'=>'staging-bulk'));};add_filter('kau_eligible_recipients',$bulk);$beforeOutbox=$wpdb->get_var('SELECT COUNT(*) FROM '.kau_table('outbox'));$liveCtx=$ctx;$liveCtx['test']=false;
kau_test('large established list requires bulk adapter before any partial send',kau_brief_job(null,$liveCtx)['code']==='provider_bulk_adapter_required'&&kau_digest_job(null,$liveCtx)['code']==='provider_bulk_adapter_required'&&$wpdb->get_var('SELECT COUNT(*) FROM '.kau_table('outbox'))===$beforeOutbox);remove_filter('kau_eligible_recipients',$bulk);
remove_filter('kau_recipient_policy',$policy);
$suppressed=kau_queue_email('brief','staging-recipient:3','suppressed-fixture-'.$tool,$edition,$expire);
kau_test('missing consent or suppression evidence prevents sending',kau_send_outbox($suppressed)['status']==='suppressed');
$expired=kau_queue_email('brief','staging-recipient:4','old-fixture-'.$tool,$edition,gmdate('Y-m-d\TH:i:s\Z',time()-60));kau_test('old edition never replayed',kau_send_outbox($expired)['status']==='expired');
$flags['email']=false;update_option('kau_feature_flags',$flags); // All adapters are in this disposable test process only.

$owner=wp_create_user('staging-user-a',wp_generate_password(),'staging-a@example.invalid');if(is_wp_error($owner)){$owner=username_exists('staging-user-a');}
$other=wp_create_user('staging-user-b',wp_generate_password(),'staging-b@example.invalid');if(is_wp_error($other)){$other=username_exists('staging-user-b');}
$wpdb->replace(kau_table('projects'),array('id'=>'staging-owned-project-1','owner_id'=>$owner,'payload'=>'{"schemaVersion":1}','updated_at'=>$now));
wp_set_current_user($other);$request=new WP_REST_Request('DELETE','/kingy-upgrade/v1/projects/staging-owned-project-1');
$response=rest_do_request($request);kau_test('another user cannot delete a project',$response->get_status()===404);
kau_test('owner data remains private',$wpdb->get_var($wpdb->prepare('SELECT owner_id FROM '.kau_table('projects').' WHERE id=%s','staging-owned-project-1'))===(string)$owner);
wp_set_current_user(0);$response=rest_do_request(new WP_REST_Request('GET','/kingy-upgrade/v1/projects'));kau_test('anonymous server operation forbidden',$response->get_status()===401);
wp_set_current_user($owner);$r=new WP_REST_Request('PUT','/kingy-upgrade/v1/stack/email');$r->set_header('content-type','application/json');$r->set_body('{"dailyDigest":true,"productIds":[]}');$response=rest_do_request($r);
kau_test('missing follow adapter does not pretend opt-in',$response->get_status()===503);
wp_set_current_user(1);
kau_test('sponsor details reject malformed budget',kau_throws(function(){kau_validate_sponsor_details(array('kau_budget'=>array('bad')));}));
kau_test('sponsor details preserve required planning fields',kau_validate_sponsor_details(array('kau_campaign_format'=>'dedicated','kau_budget'=>'15000','kau_currency'=>'USD','kau_rights'=>'Organic excerpts'))['budget']===15000.0);
kau_test('sponsor enrichment cannot create alternate inquiries',kau_throws(function()use($tool){kau_store_sponsor_details($tool,array());}));
foreach(array('commercial-staging'=>'[kingy_product_commercial]','my-ai-stack-staging'=>'[kingy_my_stack]') as $slug=>$shortcode){$old=get_page_by_path($slug);wp_insert_post(array('ID'=>$old?$old->ID:0,'post_type'=>'page','post_name'=>$slug,'post_status'=>'publish','post_title'=>'STAGING FIXTURE — '.$slug,'post_content'=>$shortcode));}
update_option('permalink_structure','');update_option('kau_staging_fixture_product',$product);$GLOBALS['kau_fixture_email']=$email;
$report=array('scope'=>'disposable local WordPress; synthetic fixtures and fake email transports; zero real sends','passed'=>count($GLOBALS['kau_test_passed']),'checks'=>$GLOBALS['kau_test_passed'],'observed_at'=>kau_now(),'production_touched'=>false);
file_put_contents('/var/www/html/staging-test-results.json',wp_json_encode($report,JSON_PRETTY_PRINT));file_put_contents('/var/www/html/staging-email.html',$email);
WP_CLI::log(wp_json_encode($report));
