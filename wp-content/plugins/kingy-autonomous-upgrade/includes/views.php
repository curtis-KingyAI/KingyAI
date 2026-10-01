<?php
if (!defined('ABSPATH')) { exit; }

function kau_assets() {
    wp_enqueue_style('kau-app',plugins_url('assets/app.css',KAU_DIR.'kingy-autonomous-upgrade.php'),array(),KAU_VERSION);
    wp_enqueue_script('kau-video-project',plugins_url('assets/vendor/kingy-video-project.js',KAU_DIR.'kingy-autonomous-upgrade.php'),array(),KAU_VERSION,true);
    wp_enqueue_script('kau-app',plugins_url('assets/app.mjs',KAU_DIR.'kingy-autonomous-upgrade.php'),array('kau-video-project'),KAU_VERSION,true);
    wp_add_inline_script('kau-app','window.KingyUpgrade='.wp_json_encode(array('api'=>rest_url('kingy-upgrade/v1/'),'nonce'=>wp_create_nonce('wp_rest'),'signedIn'=>is_user_logged_in(),'followUrl'=>get_option('kau_follow_url',home_url('/ai-stack-change-radar/')),'sponsorUrl'=>home_url('/sponsor-fit-review/'),'workflowUrl'=>get_option('kau_workflow_url',home_url('/make-this/#kau-commercial-flow')))).';','before');
}

function kau_workflow_view() {
    if(!kau_enabled('workflow')){return '<p>The connected commercial workflow is not enabled.</p>';}
    kau_assets();
    return '<section class="kau" id="kau-commercial-flow" data-kau="workflow" aria-label="Make a product commercial"><header><p class="kau-kicker">Kingy workflows</p><h2>Make a product commercial</h2><p>Carry one brief through your shot plan, camera direction, prompts and budget. Download the production pack when you are ready.</p><p class="kau-note">Outlines and prompts are editable planning templates. No media generation is performed.</p></header><div id="kau-workflow"></div><noscript>JavaScript is required to edit and save this workflow. <a href="/ai-camera-simulator/">Camera planner</a> and <a href="/make-this/">existing production recipes</a> remain available.</noscript></section>';
}
function kau_stack_view() {
    if(!kau_enabled('stack')){return '<p>My AI Stack is not enabled.</p>';}
    kau_assets();return '<section class="kau" data-kau="stack" aria-label="My AI Stack"><header><p class="kau-kicker">Your working tools</p><h2>My AI Stack</h2><p>Save product records, keep your projects and review verified changes relevant to your work.</p><p>Anonymous stacks stay in this browser on this device. Export a backup to move or recover them. Clearing browser storage removes the saved copy.</p></header><div id="kau-stack"></div><noscript>JavaScript is required for device saving. <a href="/ai-tools/">Browse the tool directory</a>.</noscript></section>';
}
function kau_sponsor_view() {
    if(!kau_enabled('sponsor')){return '';}
    return '<section class="kau"><h2>You Do AI. We Do Distribution.</h2><p>Explain a real product workflow through a dedicated video and practical articles. Confirm the distribution scope, revisions, timing, reporting and usage rights in a written proposal.</p><p><a href="/sponsor-fit-review/">Request a proposal</a></p><p><a href="/clients/">Inspect genuine campaign examples</a> · <a href="/media-kit/">Dated audience evidence</a> · <a href="/editorial-sponsorship-standards/">Scope and editorial standards</a></p><p>Campaign outcomes and availability depend on the agreed scope. Editorial submissions remain a separate route.</p></section>';
}
function kau_admin_menu(){add_management_page('Kingy Upgrade Operations','Kingy Upgrade','manage_options','kingy-upgrade','kau_admin_view');}
function kau_admin_view(){
    if(!current_user_can('manage_options')){return;}
    echo '<div class="wrap"><h1>Kingy upgrade operations</h1><p>Private job status, exceptions, email outcomes and release readiness. Fetch success does not establish fact verification.</p><pre>'.esc_html(wp_json_encode(kau_operations_status(),JSON_PRETTY_PRINT)).'</pre></div>';
}
add_filter('the_content',function($content){
    if(!kau_enabled('stack')||!is_singular(array('kingy_ai_tool','kingy_ai_model'))||!in_the_loop()||!is_main_query()){return $content;}
    kau_assets();$identity='wp:'.get_post_type(get_the_ID()).':'.get_the_ID();
    return $content.'<section class="kau" data-kau="product-save" data-product="'.esc_attr($identity).'"><button>Save to my stack</button><p role="status"></p><a href="'.esc_url(get_option('kau_stack_url',home_url('/my-ai-stack/'))).'">Open My AI Stack</a></section>';
},35);
add_filter('kingy_ali_current_price_record',function($price,$tool_id){
    if(!kau_enabled('changes')){return $price;}
    foreach(kau_changes(array('wp:kingy_ai_tool:'.(int)$tool_id)) as $event){if($event['kind']==='price'){return $event;}}
    return $price;
},20,2);

// Use the single verified-change projection on companion pages too.
add_filter('the_content',function($content){
    if(!kau_enabled('changes') || !is_singular('kingy_video') || !in_the_loop() || !is_main_query() || !function_exists('kingy_ali_companion_featured_tool_ids')){return $content;}
    $ids=array_map(function($id){return 'wp:kingy_ai_tool:'.$id;},kingy_ali_companion_featured_tool_ids(get_the_ID()));
    if(!$ids){return $content;}
    $events=kau_changes($ids,20);if(!$events){return $content;}
    $html='<section class="kau"><h2>Verified changes since filming</h2>';
    $published=get_post_meta(get_the_ID(),'_kingy_video_publish_date',true);
    foreach($events as $event){if($published && substr($event['published_at'],0,10)<=$published){continue;}$html.='<article><h3>'.esc_html($event['title']).'</h3><p>'.esc_html($event['summary']).'</p><p>'.esc_html($event['why_it_matters']).'</p><p><a href="'.esc_url($event['source_url']).'">Primary source</a> · Verified '.esc_html($event['verified_at']).'</p></article>';}
    return $content.$html.'</section>';
},30);
add_shortcode('kingy_verified_changes',function(){
    if(!kau_enabled('changes')){return '';}
    $events=kau_current_changes();if(!$events){return '<p>No published qualifying records are available. Check the maintained source monitor for actual checking coverage.</p>';}
    $html='<section class="kau"><h2>Recent verified product developments</h2>';
    foreach($events as $e){$html.='<article><h3><a href="'.esc_url($e['guide_url']?:$e['source_url']).'">'.esc_html($e['title']).'</a></h3><p>'.esc_html($e['summary']).'</p><p>'.esc_html($e['kind']).' · Published '.esc_html($e['published_at']).' · Verified '.esc_html($e['verified_at']).'</p><a href="'.esc_url($e['source_url']).'">Primary source</a></article>';}
    return $html.'</section>';
});
