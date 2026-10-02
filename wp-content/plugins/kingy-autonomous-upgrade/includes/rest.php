<?php
if (!defined('ABSPATH')) { exit; }

function kau_api_permission() { return kau_enabled('workflow') && is_user_logged_in(); }
function kau_stack_permission() { return kau_enabled('stack') && is_user_logged_in(); }
function kau_private_permission() { return current_user_can('manage_options'); }
function kau_with_account_lock($callback){
    $name='account-'.get_current_user_id();$token=kau_lock($name);
    if(!$token){return new WP_Error('locked','Account data is being updated. Retry shortly.',array('status'=>409));}
    try{return $callback();}finally{kau_unlock($name,$token);}
}
function kau_register_routes() {
    register_rest_route('kingy-upgrade/v1', '/catalog', array('methods' => 'GET', 'permission_callback' => function () { return kau_enabled('workflow') || kau_enabled('stack'); }, 'callback' => function ($r) {
        $search = $r->get_param('search');
        if ($search !== null && (!is_string($search) || strlen($search)>100)) { return new WP_Error('invalid_search','Use a short search.',array('status'=>400)); }
        $ids=$r->get_param('ids')?:array();
        try{if(!is_array($ids)||count($ids)>100){throw new InvalidArgumentException();}return kau_catalog(sanitize_text_field($search ?: ''),$ids);}
        catch(Throwable $e){return new WP_Error('invalid_products','Invalid product identities.',array('status'=>400));}
    }));
    register_rest_route('kingy-upgrade/v1', '/changes', array('methods' => 'GET', 'permission_callback' => function () { return kau_enabled('stack') || kau_enabled('workflow'); }, 'callback' => function ($r) {
        try { $ids = $r->get_param('products') ?: array(); if (!is_array($ids) || count($ids)>100) { throw new InvalidArgumentException(); } return kau_changes($ids); }
        catch (Throwable $e) { return new WP_Error('invalid_products','Invalid product identities.',array('status'=>400)); }
    }));
    register_rest_route('kingy-upgrade/v1', '/projects', array('methods'=>'GET', 'permission_callback'=>'kau_api_permission', 'callback'=>function () {
        global $wpdb; $rows=$wpdb->get_results($wpdb->prepare('SELECT id,payload,revision,updated_at FROM '.kau_table('projects')." WHERE owner_id=%d AND id NOT LIKE 'stack-user-%%' ORDER BY updated_at DESC LIMIT 100",get_current_user_id()), ARRAY_A);
        return array_map(function($row){$row['payload']=json_decode($row['payload'],true);$row['revision']=(int)$row['revision'];return $row;},$rows);
    }));
    register_rest_route('kingy-upgrade/v1', '/projects/(?P<id>[a-zA-Z0-9-]{16,64})', array(
        array('methods'=>'PUT','permission_callback'=>'kau_api_permission','callback'=>'kau_save_project'),
        array('methods'=>'DELETE','permission_callback'=>'kau_api_permission','callback'=>function($r){
            return kau_with_account_lock(function()use($r){global $wpdb;$n=$wpdb->delete(kau_table('projects'),array('id'=>$r->get_url_params()['id'],'owner_id'=>get_current_user_id()));
                if($n===false){return new WP_Error('delete_failed','Project could not be deleted.',array('status'=>503));}
                return $n ? array('deleted'=>true) : new WP_Error('not_found','Project not found.',array('status'=>404));});
        })
    ));
    register_rest_route('kingy-upgrade/v1', '/operations', array('methods'=>'GET','permission_callback'=>'kau_private_permission','callback'=>'kau_operations_status'));
    register_rest_route('kingy-upgrade/v1', '/stack', array(
        array('methods'=>'GET','permission_callback'=>'kau_stack_permission','callback'=>function(){
            global $wpdb; $body=$wpdb->get_var($wpdb->prepare('SELECT payload FROM '.kau_table('projects').' WHERE id=%s AND owner_id=%d','stack-user-'.get_current_user_id(),get_current_user_id()));
            return $body?json_decode($body,true):array('schemaVersion'=>1,'products'=>array(),'projects'=>array(),'preferences'=>array('plan'=>'','budget'=>''));
        }),
        array('methods'=>'PUT','permission_callback'=>'kau_stack_permission','callback'=>'kau_save_stack'),
        array('methods'=>'DELETE','permission_callback'=>'kau_stack_permission','callback'=>function(){return kau_with_account_lock(function(){global $wpdb;$n=$wpdb->delete(kau_table('projects'),array('owner_id'=>get_current_user_id()));return $n===false?new WP_Error('delete_failed','Saved data could not be deleted.',array('status'=>503)):array('deleted'=>true,'projects_deleted'=>$n);});})
    ));
    register_rest_route('kingy-upgrade/v1','/stack/email',array('methods'=>'PUT','permission_callback'=>'kau_stack_permission','callback'=>function($r){
        $body=$r->get_json_params();if(!is_array($body)||!is_bool($body['dailyDigest']??null)||!is_array($body['productIds']??null)||count($body['productIds'])>100){return new WP_Error('invalid_preferences','Invalid preferences.',array('status'=>400));}
        try{foreach($body['productIds'] as $id){kau_product_id($id);}}
        catch(Throwable $e){return new WP_Error('invalid_preferences','Invalid products.',array('status'=>400));}
        $result=apply_filters('kau_existing_follow_preferences',null,get_current_user_id(),$body);
        if(!is_array($result)||($result['recorded']??false)!==true){return new WP_Error('follow_adapter_missing','The established follow preference integration is not configured. No email preference was changed. Use the existing preferences link.',array('status'=>503));}
        return array('recorded'=>true,'dailyDigest'=>$body['dailyDigest']);
    }));
    register_rest_route('kingy-upgrade/v1', '/changes/admit', array('methods'=>'POST','permission_callback'=>'kau_private_permission','callback'=>function($r){
        try { return kau_admit_change($r->get_json_params()); } catch(Throwable $e) { return new WP_Error('evidence_gate','Evidence validation failed; see the private exception queue.',array('status'=>422)); }
    }));
}

function kau_save_stack($r){
    global $wpdb;$body=$r->get_json_params();
    if(!is_array($body)||strlen(wp_json_encode($body))>1000000||($body['schemaVersion']??null)!==1||!is_array($body['products']??null)||count($body['products'])>100||!is_array($body['projects']??null)||count($body['projects'])>40){return new WP_Error('invalid_stack','Stack schema or size is invalid.',array('status'=>400));}
    try{
        $products=array();$seen=array();
        foreach($body['products'] as $p){$id=kau_product_id($p['id']??null);if(isset($seen[$p['id']])){throw new InvalidArgumentException();}$seen[$p['id']]=true;$products[]=array('id'=>$p['id'],'category'=>kau_text($p['category']??'',100));}
        foreach($body['projects'] as $p){kau_validate_private_project($p);}
        $body=array('schemaVersion'=>1,'products'=>$products,'projects'=>$body['projects'],'preferences'=>array('plan'=>kau_text($body['preferences']['plan']??'',500),'budget'=>kau_text($body['preferences']['budget']??'',100)));
    }catch(Throwable $e){return new WP_Error('invalid_stack','Stack fields are invalid.',array('status'=>400));}
    return kau_with_account_lock(function()use($body){global $wpdb;$owner=get_current_user_id();$ok=$wpdb->replace(kau_table('projects'),array('id'=>'stack-user-'.$owner,'owner_id'=>$owner,'payload'=>wp_json_encode($body),'updated_at'=>kau_now()));
        return $ok===false?new WP_Error('save_failed','Stack could not be saved.',array('status'=>503)):array('saved'=>true);});
}

function kau_save_project($request) {
    global $wpdb;
    $body=$request->get_json_params();
    if (!is_array($body) || strlen(wp_json_encode($body))>200000 || ($body['schemaVersion']??null)!==1 || !is_array($body['brief']??null) || !is_array($body['shots']??null) || count($body['shots'])>40) {
        return new WP_Error('invalid_project','Project schema or size is invalid.',array('status'=>400));
    }
    // Payload is private, never public HTML. Media is device-only at every nesting level.
    try { kau_validate_private_project($body); }
    catch(Throwable $e) { return new WP_Error('invalid_project','Project fields or media policy are invalid.',array('status'=>400)); }
    $id=$request->get_url_params()['id'];
    if($body['id']!==$id || !is_int($body['serverRevision']??0) || ($body['serverRevision']??0)<0){return new WP_Error('invalid_project','Project identity or revision is invalid.',array('status'=>400));}
    $owner=get_current_user_id();$account_token=kau_lock('account-'.$owner);
    if(!$account_token){return new WP_Error('locked','Account data is being updated. Retry shortly.',array('status'=>409));}
    $token=kau_lock('project-'.$id);
    if (!$token) { kau_unlock('account-'.$owner,$account_token);return new WP_Error('locked','Project is being saved. Retry shortly.',array('status'=>409)); }
    try {
        $old=$wpdb->get_row($wpdb->prepare('SELECT owner_id,revision FROM '.kau_table('projects').' WHERE id=%s',$id),ARRAY_A);
        if ($old && (int)$old['owner_id']!==$owner) { return new WP_Error('not_found','Project not found.',array('status'=>404)); }
        $revision=(int)($body['serverRevision']??0);
        if ($old && $revision!==(int)$old['revision']) { return new WP_Error('revision_conflict','Saved project changed elsewhere. Reload or duplicate this project.',array('status'=>409)); }
        $next=$old?(int)$old['revision']+1:1;
        $data=array('id'=>$id,'owner_id'=>$owner,'payload'=>wp_json_encode($body),'revision'=>$next,'updated_at'=>kau_now());
        $ok=$old?$wpdb->update(kau_table('projects'),$data,array('id'=>$id,'owner_id'=>$owner)):$wpdb->insert(kau_table('projects'),$data);
        if ($ok===false) { return new WP_Error('save_failed','Project could not be saved.',array('status'=>503)); }
        return array('id'=>$id,'revision'=>$next,'updated_at'=>$data['updated_at']);
    } finally { kau_unlock('project-'.$id,$token);kau_unlock('account-'.$owner,$account_token); }
}

function kau_reject_embedded_media($value,$depth=0){
    if($depth>16){throw new InvalidArgumentException('Project nesting is too deep.');}
    if(!is_array($value)){return;}
    foreach($value as $key=>$child){if(in_array($key,array('media','referenceImage','email','__proto__','constructor','prototype'),true)){throw new InvalidArgumentException('Media or unsafe property.');}kau_reject_embedded_media($child,$depth+1);}
}
function kau_validate_private_project($body){
    kau_reject_embedded_media($body);
    if(!is_array($body)||($body['schemaVersion']??null)!==1){throw new InvalidArgumentException();}
    if(!preg_match('/^[a-zA-Z0-9-]{16,64}$/',kau_text($body['id']??'',64))){throw new InvalidArgumentException();}
    kau_text($body['name']??'',120);kau_text($body['outline']??null,10000);$brief=$body['brief']??null;if(!is_array($brief)){throw new InvalidArgumentException();}
    foreach(array('product','audience','message','style','referenceNote') as $key){kau_text($brief[$key]??null,3000);}
    if(!in_array($brief['format']??'',array('16:9','9:16','1:1'),true)||!in_array($brief['currency']??'',array('USD','CAD','EUR','GBP'),true)){throw new InvalidArgumentException();}
    foreach(array('duration'=>array(6,180),'budget'=>array(0,1000000)) as $key=>$range){$n=$brief[$key]??null;if(!is_numeric($n)||$n<$range[0]||$n>$range[1]){throw new InvalidArgumentException();}}
    if(!empty($brief['productId'])){kau_product_id($brief['productId']);}
    if(!is_array($body['shots']??null)||count($body['shots'])>40){throw new InvalidArgumentException();}
    $routes=array('generic','artlist','invideo');
    $ids=array();foreach($body['shots'] as $shot){if(!is_array($shot)||!preg_match('/^[a-zA-Z0-9-]{16,64}$/',$shot['id']??'')||isset($ids[$shot['id']??''])||!is_numeric($shot['seconds']??null)||$shot['seconds']<1||$shot['seconds']>180||!in_array($shot['route']??'',$routes,true)){throw new InvalidArgumentException();}$ids[kau_text($shot['id'],64)]=true;foreach(array('purpose','description','framing','camera','motion','route') as $key){kau_text($shot[$key]??null,3000);}}
    if(!is_array($body['prompts']??null)||count($body['prompts'])>40){throw new InvalidArgumentException();}
    $seen=array();foreach($body['prompts'] as $prompt){if(!is_array($prompt)||!isset($ids[$prompt['shotId']??''])||isset($seen[$prompt['shotId']])||!in_array($prompt['route']??'',$routes,true)){throw new InvalidArgumentException();}$seen[$prompt['shotId']]=true;kau_text($prompt['text']??null,12000);}
    $budget=$body['budget']??null;if(!is_array($budget)||!in_array($budget['unit']??'',array('second','generation'),true)){throw new InvalidArgumentException();}
    foreach(array('price'=>array(0,1000000),'attemptsPerShot'=>array(1,20),'usableFraction'=>array(.01,1),'reviewMinutesPerAttempt'=>array(0,1440),'laborRate'=>array(0,10000)) as $key=>$range){$n=$budget[$key]??null;if($key==='price'&&$n===null){continue;}if(!is_int($n)&&!is_float($n)||!is_finite((float)$n)||$n<$range[0]||$n>$range[1]){throw new InvalidArgumentException();}}
    if(!is_int($budget['attemptsPerShot'])){throw new InvalidArgumentException();}
    kau_text($budget['evidenceDate']??null,30);kau_text($budget['rateEventKey']??null,191);$source=kau_text($budget['sourceUrl']??null,2048);if($source){kau_url($source);}
    if($budget['evidenceDate']&&!date_create_immutable($budget['evidenceDate'])){throw new InvalidArgumentException();}
    foreach(array('outlineBrief','promptShots','promptBrief') as $key){if(($body[$key]??null)!==null){kau_text($body[$key],60000);}}
    if(($body['referenceMetadata']??null)!==null){$r=$body['referenceMetadata'];if(!is_array($r)||!is_numeric($r['width']??null)||!is_numeric($r['height']??null)||$r['width']<1||$r['height']<1||$r['width']>12000||$r['height']>12000||$r['width']*$r['height']>32000000){throw new InvalidArgumentException();}kau_text($r['name']??null,150);}
    return true;
}
