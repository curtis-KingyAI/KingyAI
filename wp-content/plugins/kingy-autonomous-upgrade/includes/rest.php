<?php
if (!defined('ABSPATH')) { exit; }

function kau_api_permission() { return kau_enabled('workflow') && is_user_logged_in(); }
function kau_stack_permission() { return kau_enabled('stack') && is_user_logged_in(); }
function kau_private_permission() { return current_user_can('manage_options'); }
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
        return array_map(function($row){$row['payload']=json_decode($row['payload'],true);return $row;},$rows);
    }));
    register_rest_route('kingy-upgrade/v1', '/projects/(?P<id>[a-zA-Z0-9-]{16,64})', array(
        array('methods'=>'PUT','permission_callback'=>'kau_api_permission','callback'=>'kau_save_project'),
        array('methods'=>'DELETE','permission_callback'=>'kau_api_permission','callback'=>function($r){
            global $wpdb; $n=$wpdb->delete(kau_table('projects'),array('id'=>$r['id'],'owner_id'=>get_current_user_id()));
            return $n ? array('deleted'=>true) : new WP_Error('not_found','Project not found.',array('status'=>404));
        })
    ));
    register_rest_route('kingy-upgrade/v1', '/operations', array('methods'=>'GET','permission_callback'=>'kau_private_permission','callback'=>'kau_operations_status'));
    register_rest_route('kingy-upgrade/v1', '/stack', array(
        array('methods'=>'GET','permission_callback'=>'kau_stack_permission','callback'=>function(){
            global $wpdb; $body=$wpdb->get_var($wpdb->prepare('SELECT payload FROM '.kau_table('projects').' WHERE id=%s AND owner_id=%d','stack-user-'.get_current_user_id(),get_current_user_id()));
            return $body?json_decode($body,true):array('schemaVersion'=>1,'products'=>array(),'projects'=>array(),'preferences'=>array('plan'=>'','budget'=>''));
        }),
        array('methods'=>'PUT','permission_callback'=>'kau_stack_permission','callback'=>'kau_save_stack'),
        array('methods'=>'DELETE','permission_callback'=>'kau_stack_permission','callback'=>function(){global $wpdb;$wpdb->delete(kau_table('projects'),array('id'=>'stack-user-'.get_current_user_id(),'owner_id'=>get_current_user_id()));return array('deleted'=>true);})
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
    $owner=get_current_user_id();$ok=$wpdb->replace(kau_table('projects'),array('id'=>'stack-user-'.$owner,'owner_id'=>$owner,'payload'=>wp_json_encode($body),'updated_at'=>kau_now()));
    return $ok===false?new WP_Error('save_failed','Stack could not be saved.',array('status'=>503)):array('saved'=>true);
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
    $id=$request['id']; $owner=get_current_user_id(); $token=kau_lock('project-'.$id);
    if (!$token) { return new WP_Error('locked','Project is being saved. Retry shortly.',array('status'=>409)); }
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
    } finally { kau_unlock('project-'.$id,$token); }
}

function kau_reject_embedded_media($value,$depth=0){
    if($depth>16){throw new InvalidArgumentException('Project nesting is too deep.');}
    if(!is_array($value)){return;}
    foreach($value as $key=>$child){if(in_array($key,array('media','referenceImage','email','__proto__','constructor','prototype'),true)){throw new InvalidArgumentException('Media or unsafe property.');}kau_reject_embedded_media($child,$depth+1);}
}
function kau_validate_private_project($body){
    kau_reject_embedded_media($body);
    if(!is_array($body)||($body['schemaVersion']??null)!==1){throw new InvalidArgumentException();}
    kau_text($body['name']??'',120);$brief=$body['brief']??null;if(!is_array($brief)){throw new InvalidArgumentException();}
    foreach(array('product','audience','message','style','referenceNote') as $key){kau_text($brief[$key]??null,3000);}
    if(!in_array($brief['format']??'',array('16:9','9:16','1:1'),true)||!in_array($brief['currency']??'',array('USD','CAD','EUR','GBP'),true)){throw new InvalidArgumentException();}
    foreach(array('duration'=>array(6,180),'budget'=>array(0,1000000)) as $key=>$range){$n=$brief[$key]??null;if(!is_numeric($n)||$n<$range[0]||$n>$range[1]){throw new InvalidArgumentException();}}
    if(!empty($brief['productId'])){kau_product_id($brief['productId']);}
    if(!is_array($body['shots']??null)||count($body['shots'])>40){throw new InvalidArgumentException();}
    $ids=array();foreach($body['shots'] as $shot){if(!is_array($shot)||isset($ids[$shot['id']??''])||!is_numeric($shot['seconds']??null)||$shot['seconds']<1||$shot['seconds']>180){throw new InvalidArgumentException();}$ids[kau_text($shot['id']??'',64)]=true;foreach(array('purpose','description','framing','camera','motion','route') as $key){kau_text($shot[$key]??null,3000);}}
    if(!is_array($body['prompts']??null)||count($body['prompts'])>40){throw new InvalidArgumentException();}
    foreach($body['prompts'] as $prompt){if(!is_array($prompt)||!isset($ids[$prompt['shotId']??''])){throw new InvalidArgumentException();}kau_text($prompt['text']??null,12000);}
    return true;
}
