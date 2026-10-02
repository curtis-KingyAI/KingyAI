<?php
/** Local test credentials are written only outside the source tree, never to logs. */
if(!defined('ABSPATH')||wp_get_environment_type()!=='local'||!defined('WP_CLI')||!WP_CLI){throw new RuntimeException('Disposable local CLI required.');}
global $wpdb;$rows=array();
foreach(array('kau-browser-a','kau-browser-b') as $login){
    $id=username_exists($login);if(!$id){$id=wp_create_user($login,wp_generate_password(40,true,true),$login.'@example.invalid');}
    if(is_wp_error($id)){throw new RuntimeException('Local account fixture creation failed.');}
    (new WP_User($id))->set_role('subscriber');$wpdb->delete(kau_table('projects'),array('owner_id'=>$id));
    $expiry=time()+3600;$rows[$login]=array('name'=>LOGGED_IN_COOKIE,'value'=>wp_generate_auth_cookie($id,$expiry,'logged_in'),'domain'=>'127.0.0.1','path'=>'/','expires'=>$expiry,'httpOnly'=>true,'secure'=>false,'sameSite'=>'Lax');
}
$path='/var/www/html/kau-browser-cookies.json';file_put_contents($path,wp_json_encode($rows));chmod($path,0600);
WP_CLI::log('Two isolated native account sessions prepared; credentials withheld.');
