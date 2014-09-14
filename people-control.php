<?php
/*
Plugin Name: DreamKyiv People Control
Version: 0.0.4
Description: Plugin for Kyiv rada deputies control
Author: Vitaliy Pylypiv
GitHub Plugin URI: https://github.com/DreamKyiv/people-control
GitHub Branch:     master
*/

// Setting constants
define('DK_PEOPLE_CONTROL_DIR', dirname( __FILE__ )); //an absolute path to this directory
define('DK_PEOPLE_CONTROL_DIR_URI', trailingslashit(plugins_url('',__FILE__))); //an absolute path to this directory

//Table names
global $wpdb;
$prefix = $wpdb->base_prefix;

define('DK_PEOPLE_CONTROL_VOTINGS_TABLE', $prefix.'dkpc_votings');

/**
 * Perform init actions
 */
function dk_people_control_init(){
	//add custom functions.php file
	require_once(DK_PEOPLE_CONTROL_DIR.'/functions.php');
}
add_filter('init','dk_people_control_init',1);


function dk_people_control_activate() {
    error_log( 'activate plugin' );
    if( is_admin() && current_user_can('list_users') ){
        require_once( dirname(__FILE__).'/people-control-install.php');
        people_control_activate();
    }
}
register_activation_hook( __FILE__,'dk_people_control_activate');

function dk_people_control_deactivate() {
    error_log( 'deactivate plugin');
    if( is_admin() && current_user_can('list_users') ){
        require_once( dirname(__FILE__).'/people-control-install.php');
        people_control_deactivate();
    }
}
register_deactivation_hook( __FILE__,'dk_people_control_deactivate');

?>