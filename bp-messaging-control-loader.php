<?php
/*
Plugin Name: BP Messaging Control
Plugin URI: https://wordpress.org/plugins/bp-messaging-control/
Description: Restrict private messaging - By user role, apply quota's.
Author: Venutius
Author URI: https://buddyuser.com
License: GNU GENERAL PUBLIC LICENSE 3.0 https://www.gnu.org/licenses/gpl.txt
Version: 1.8.0
Text Domain: bp-messaging-control
Network: false
Copyright: 2019 onwards venutius @buddyuser
*/
if(!defined('ABSPATH')) {
	exit;
}

/**
 * Version number
 *
 * @since 1.6.0
 */
define('BPMC_VERSION', '1.7.0');

function bpmc_bp_messaging_control_init() {

	//load if we care
	if ( !bp_is_active( 'messages' ) )
		return;

	load_textdomain( 'bp-messaging-control', dirname( __FILE__ ) . '/languages/' . 'bp-messaging-control-en_GB.mo' );
	
	require( dirname( __FILE__ ) . '/bp-messaging-control.php' );
	
	add_action( bp_core_admin_hook(), 'bpmc_bp_messaging_control_admin_add_admin_menu' );
	
}
add_action( 'bp_include', 'bpmc_bp_messaging_control_init', 88 );



// Load optional scripts for activity post restrictions
function bpmc_messaging_control_maybe_load_scripts() {
	global $bp;
	if ( current_user_can( 'manage_options' ) ) return;
	
	$current_user_control = New BP_Messaging_Control( get_current_user_id() );

	if ( $current_user_control->get_mentions_character_limit() != 100000 &&// Load the scripts on Activity pages
		( ( function_exists(bp_get_activity_slug()) && bp_is_activity_component())
		||
		// Load the scripts when Activity page is the Home page
		(function_exists(bp_get_activity_slug()) && 'page' == get_site_option('show_on_front') && is_front_page() && BP_ACTIVITY_SLUG == get_site_option('page_on_front'))
		||
		// Load the script on Group home page
		(function_exists(bp_get_groups_slug()) && bp_is_groups_component() && 'home' == $bp->current_action)
		) ) {
		add_action( "wp_enqueue_scripts", 'bpmc_messaging_control_enqueue_activity_scripts' );
		add_action( "wp_print_scripts", 'bpmc_messaging_control_print_style' );
	}
	
	if ( $current_user_control->get_character_limit() != 100000 && bp_is_messages_component() ) {
		add_action( "wp_enqueue_scripts", 'bpmc_messaging_control_enqueue_messages_scripts' );
		//add_action( "wp_print_scripts", 'bpmc_messaging_control_print_style' );
	}
}

add_action( 'bp_init', 'bpmc_messaging_control_maybe_load_scripts' );

function bpmc_messaging_control_enqueue_activity_scripts() {

	$current_user_control = New BP_Messaging_Control( get_current_user_id() );
	wp_enqueue_script( 'bp-messaging-control-js', plugin_dir_url( __FILE__ ) . 'js/bp-messaging-control.js', array('jquery'), BPMC_VERSION, true );
	wp_localize_script('bp-messaging-control-js', 'BPmcMessCntrl', array(
		'activityLimit'     	=> $current_user_control->get_mentions_character_limit(),
		'type'      			=> 'char',
		'characterLimitText'	=> esc_attr__( 'Character Limit: ', 'bp-messaging-control' )
	));
}

function bpmc_messaging_control_enqueue_messages_scripts() {

	$current_user_control = New BP_Messaging_Control( get_current_user_id() );
	wp_enqueue_script( 'bp-messaging-control-js', plugin_dir_url( __FILE__ ) . 'js/bp-messaging-control-messages.js', array('jquery'), BPMC_VERSION, true );
	wp_localize_script('bp-messaging-control-js', 'BPmcMessCntrl', array(
		'messageLimit'     		=> $current_user_control->get_character_limit(),
		'type'      			=> 'char',
		'characterLimitText'	=> esc_attr__( 'Character Limit: ', 'bp-messaging-control' )
	));
}

/**
 * Because it's just one declaration, let's put it in the header
 */
function bpmc_messaging_control_print_style() {
	?>
	<style>div.activity-limit{margin:12px 10px 0 0;line-height:28px;} #whats-new-form div.activity-limit {float:right;} .ac-form div.activity-limit {display:inline;} </style>
	<?php
}


//add admin_menu page
function bpmc_bp_messaging_control_admin_add_admin_menu() {
	global $bp;
	
	if ( ! current_user_can( 'manage_options' ) )
		return false;

	//Add the component's administration tab under the "Setting" menu for site administrators
	require ( dirname( __FILE__ ) . '/admin/bp-messaging-control-admin.php' );

	add_submenu_page( 'options-general.php', esc_attr__( 'Messaging Control Admin', 'bp-messaging-control' ), esc_attr__( 'Messaging Control', 'bp-messaging-control' ), 'manage_options', 'bp-messaging-control-settings', 'bpmc_bp_messaging_control_admin' );

}


function bpmc_bp_messaging_control_admin_add_action_link( $links, $file ) {
	if ( 'buddypress-restrict-messages/bp-messaging-control-loader.php' != $file )
		return $links;

	if ( function_exists( 'bp_core_do_network_admin' ) ) {
		$settings_url = add_query_arg( 'page', 'bp-messaging-control-settings', bp_core_do_network_admin() ? network_admin_url( 'admin.php' ) : admin_url( 'admin.php' ) );
	} else {
		$settings_url = add_query_arg( 'page', 'bp-messaging-control-settings', is_multisite() ? network_admin_url( 'admin.php' ) : admin_url( 'admin.php' ) );
	}

	$settings_link = '<a href="' . $settings_url . '">' . esc_attr__( 'Settings', 'bp-messaging-control' ) . '</a>';
	array_unshift( $links, $settings_link );

	return $links;
}
add_filter( 'plugin_action_links', 'bpmc_bp_messaging_control_admin_add_action_link', 10, 2 );
?>
