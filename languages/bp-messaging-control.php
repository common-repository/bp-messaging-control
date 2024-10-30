<?php
if ( !defined( 'ABSPATH' ) ) exit;

/*
- allow samegroups admin/mods
- flood protection for non admins/mods
* 
*/


Class BP_Messaging_Control {
	
	private $data = null;
	private $id = null;
	private $disabled = false;
	private $reply_only = false;
	private $admin_only = false;

	function bp_messaging_control( $id = null ) {
		$this->__construct($id);
	}

	function __construct( $id = null ) {
		global $bp;

		//respect the keymaster
		if ( $bp->loggedin_user->is_super_admin )
			return;
		
		if ( !$id && $bp->displayed_user->id ) {
			$this->id = bp_displayed_user_id();
		} else if ( $id ) {
			$this->id = $id;
		}

		if ( !$this->id )
			return;

		//respect the keymaster
		if ( $this->has_cap( 'administrator' ) )
			return;

		//check for messaging restrictions
		$this->check();
		
	}

	protected function check() {
		
		$user = $this->id ? new WP_User( $this->id ) : wp_get_current_user();
		$user_roles_array = $user->roles ? $user->roles : array();
		$settings = maybe_unserialize( get_option( 'bpmc_bp_messaging_control' ) );
		
		
		// Sort role options into arrays
		$disabled_roles = array();
		$admin_only_roles = array();
		$reply_only_roles = array();
		
		foreach( $settings as $role => $option ) {
			if ( $option['role_option'] == 'no_messaging' ) {
				$disabled_roles[] = $role;
			}
			if ( $option['role_option'] == 'admin_only' )
				$admin_only_roles[] = $role;
			if ( $option['role_option'] == 'reply_only' )
				$reply_only_roles[] = $role;

		}

		// Role check for disabled messaging
		foreach ( $user_roles_array as $key => $role ) {
			if ( in_array( $role, $disabled_roles ) )
			$this->disabled = true;
		}

		// Role check for admin messaging
		foreach ( $user_roles_array as $key => $role ) {
			if ( in_array( $role, $admin_only_roles ) )
			$this->admin_only = true;
		}

		// Role check for reply only messaging
		foreach ( $user_roles_array as $key => $role ) {
			if ( in_array( $role, $reply_only_roles ) )
			$this->reply_only = true;
		}

	}
	
	protected function has_cap( $cap ) {
		global $wpdb;
		
		if ( !$cap )
			return false;
				
		$displayedcaps = get_user_meta( $this->id, $wpdb->prefix.'capabilities', true );
		
		if ( !$displayedcaps || empty( $displayedcaps ) )
			return false;

		return array_key_exists( $cap, $displayedcaps );
	}
	
	public function is_disabled() {
		return $this->disabled;
	}

	public function is_admin_only() {
		return $this->admin_only;
	}

	public function is_reply_only() {
		return $this->reply_only;
	}

}


//hook to remove button
function bpmc_bp_messaging_control_header_button() {
	
	global $bp;
	
	if (  $bp->loggedin_user->is_super_admin || $bp->loggedin_user->id == $bp->displayed_user->id ) {
		return;
	}
	$displayed_user_id = bp_displayed_user_id();
	$current_user_id = get_current_user_id();
	$control_current_user = New BP_Messaging_Control( $current_user_id );
	
	if ( $control_current_user->is_reply_only() ) {
		$replied_ids = get_user_meta( $current_user_id, 'bpmc_replied_user_ids', true );
		if ( isset( $replied_ids ) && is_array( $replied_ids ) && in_array( $displayed_user_id, $replied_ids ) ) $replied = true;
	}

	$controlpm = New BP_Messaging_Control( $displayed_user_id );
	
	$is_admin = user_can( $bp->displayed_user_id, 'administrator' );
	
	if ( bp_is_user() &&  ( $controlpm->is_disabled() || ( $control_current_user->is_reply_only() && ! isset( $replied ) && ! $is_admin ) || ( $control_current_user->is_admin_only() && ! $is_admin ) || $control_current_user->is_disabled() ) ) {
		remove_action( 'bp_member_header_actions',    'bp_send_private_message_button', 20 );
	}
	
}

if ( bp_get_theme_package_id() == 'legacy' ) {
	add_action( 'bp_before_member_header', 'bpmc_bp_messaging_control_header_button' );
}
if ( bp_get_theme_package_id() == 'nouveau' ) {
	add_action( 'bp_nouveau_get_members_buttons', 'bpmc_check_private_message_button' );
}


function bpmc_check_private_message_button( $buttons ) {

	global $bp;
	
	if (  $bp->loggedin_user->is_super_admin || $bp->loggedin_user->id == bp_displayed_user_id() ) {
		return $buttons;
	}
	$displayed_user_id = bp_displayed_user_id();
	$current_user_id = get_current_user_id();
	$control_current_user = New BP_Messaging_Control( $current_user_id );
	
	if ( $control_current_user->is_reply_only() ) {
		$replied_ids = get_user_meta( $current_user_id, 'bpmc_replied_user_ids', true );
		if ( isset( $replied_ids ) && is_array( $replied_ids ) && in_array( $displayed_user_id, $replied_ids ) ) $replied = true;
	}

	$controlpm = New BP_Messaging_Control( $displayed_user_id );
	
	$is_admin = user_can( $bp->displayed_user_id, 'administrator' );
	
	if ( bp_is_user() &&  ( $controlpm->is_disabled() || ( $control_current_user->is_reply_only() && ! isset( $replied ) && ! $is_admin ) || ( $control_current_user->is_admin_only() && ! $is_admin ) || $control_current_user->is_disabled() ) ) {
        unset( $buttons['private_message'] );
    }
    return $buttons;
}

/**
 * Check recipients before saving message.
 *
 * @param BP_Messages_Message $message_info Current message object.
 */
function bpmc_check_recipients( $message_info ) {
	$recipients = $message_info->recipients;
	$current_user_id = get_current_user_id();
	$u = 0;
	$current_user_control = new BP_Messaging_Control( $current_user_id );

	// Get the senders approved destination ids
	if ( $current_user_control->is_reply_only() ) {
		$send_user_ids = get_user_meta( $current_user_id, 'bpmc_replied_user_ids', true );
	}
	
	foreach ( $recipients as $key => $recipient ) {

		// if site admin, skip check
		if( $GLOBALS['bp']->loggedin_user->is_site_admin == 1 ) {
			continue;
		}

		// make sure sender is not trying to send to themselves
		
		if ( $recipient->user_id == bp_loggedin_user_id() ) {
			unset( $message_info->recipients[$key] );
			continue;
		}
		/*
		 * Check if the attempted recipient is allowed.
		 *
		 * If we get a match, remove person from recipient list. If there are no
		 * recipients, BP_Messages_Message:send() will bail out of sending.
		 *
		 * At the same time, check the message recipients are reply only and 
		 * update their log of whos sent them messages.
		 *
		*/
		$controlpm = New BP_Messaging_Control( $recipient->user_id );	
		if ( $controlpm->is_reply_only() && ! $current_user_control->is_reply_only() ) {
			
			$replied_user_ids = get_user_meta( $recipient->user_id, 'bpmc_replied_user_ids', true );
			if ( ! isset( $replied_user_ids ) || ! is_array( $replied_user_ids ) ) {
				$replied_user_ids = array();
			}
			
			if ( ! in_array( $current_user_id, $replied_user_ids ) ) {
				array_push( $replied_user_ids, $current_user_id );
			}
			if ( is_array( $replied_user_ids ) ) {
				update_user_meta( $recipient->user_id, 'bpmc_replied_user_ids', $replied_user_ids );
			}
		}
		if ( $controlpm->is_disabled() || ( $current_user_control->is_admin_only() && ! user_can( $recipient->user_id, 'administrator' ) ) ) {
			unset( $message_info->recipients[$key] );
			$u++;
		}
		if ( ! $controlpm->is_reply_only() && $current_user_control->is_reply_only() ) {
			if ( isset( $send_user_ids ) && $send_user_ids != '' ) {
				if ( in_array( $recipient->user_id, $send_user_ids ) ) {
					$user_authorised = true;
				}
			}
		}
	}

	/*
	 * If there are multiple recipients and if one of the recipients is not a
	 * allowed, remove everyone from the recipient's list.
	 *
	 * This is done to prevent the message from being sent to anyone and is
	 * another spam prevention measure.
	 */
	if ( count( $recipients ) > 1 && $u > 0 ) {
		unset( $message_info->recipients );
	}

	// check if messaging is disabled for the user or if they are reply only and this is a new message.
	if ( $current_user_control->is_disabled() || ( $current_user_control->is_reply_only() && ! $message_info->thread_id  && ! isset( $user_authorised ) ) ) {
		unset( $message_info->recipients );
	}

}

add_action( 'messages_message_before_save', 'bpmc_check_recipients' );

//in case a direct link to compose - remove user from list
function bpmc_bp_messaging_control_recipient_usernames( $r ) {
	global $bp;

	if ( $bp->loggedin_user->is_super_admin )
		return $r;

	$r = explode( ' ', $r );
	$current_user_id = get_current_user_id();
	$control_current_user = New BP_Messaging_Control( $current_user_id );
	
	if ( $control_current_user->is_reply_only() ) {
		$replied_ids = get_user_meta( $current_user_id, 'bpmc_replied_user_ids', true );
	}
	
	foreach ( $r as $recipient => $arr ) {
		$arr = trim( $arr );
		
		if ( $user_id = bp_core_get_userid( $arr ) ) {
			$controlpm = New BP_Messaging_Control( $user_id );
			if ( isset( $replied_ids )&& $replied_ids != '' && in_array( $user_id, $replied_ids ) ) $replied = true;
			
			//need to filter usernames if reply only and it's a new message.
			if ( $controlpm->is_disabled() || ( $control_current_user->is_admin_only() && ! user_can( $user_id, 'administrator' ) ) || ( $control_current_user->is_reply_only() && ! isset( $replied ) ) ) {
				unset( $r[$recipient] );
			}
		}
	}
	
	return implode( ' ', $r );
}
add_filter( 'bp_get_message_get_recipient_usernames', 'bpmc_bp_messaging_control_recipient_usernames' );

//if someone else uses the bp functions
function bpmc_bp_messaging_control_send_private_message_link( $r ) {
	global $bp;

	//no worries on member page - as button is removed first via action hook
	if ( bp_is_user() || $bp->loggedin_user->is_super_admin )
		return $r;

	$current_user_id = get_current_user_id();
	$replied_ids = get_user_meta( $current_user_id, 'bpmc_replied_user_ids', true );
	if ( isset( $replied_ids ) && in_array( $bp->displayed_user_id, $replied_ids ) ) $replied = true;
	$controlpm = New BP_Messaging_Control();
	
	if ( $controlpm->is_disabled() || ( $controlpm->is_reply_only() && ! isset( $replied ) ) || ( $controlpm->is_admin_only() && ! user_can( bp_displayed_user_id(), 'administrator' ) ) )
		return false;
		
	return $r;
}
add_filter( 'bp_get_send_private_message_link', 'bpmc_bp_messaging_control_send_private_message_link' );


function bpmc_remove_member_tab_on_role() {

	$controlpm = New BP_Messaging_Control( bp_displayed_user_id() );
	if ( $controlpm->is_disabled() ) {
		bp_core_remove_nav_item( 'messages' );
	}
}
add_action( 'bp_actions', 'bpmc_remove_member_tab_on_role' );



function bpmc_admin_bar_remove_messages(){
	
	global $wp_admin_bar;
	$controlpm = New BP_Messaging_Control( bp_displayed_user_id() );
	if ( $controlpm->is_disabled() ) {
		$wp_admin_bar->remove_node('my-account-messages');
		$wp_admin_bar->remove_node('my-account-messages-default');
		$wp_admin_bar->remove_node('my-account-messages-starred');
		$wp_admin_bar->remove_node('my-account-messages-inbox');
		$wp_admin_bar->remove_node('my-account-messages-compose');
		$wp_admin_bar->remove_node('my-account-messages-sentbox');
    }
}
add_action('wp_before_admin_bar_render','bpmc_admin_bar_remove_messages');
 
add_filter( 'bp_members_suggestions_get_suggestions', 'bpmc_filter_suggestions' );

function bpmc_filter_suggestions( $results ) {
	
	global $bp;

	if ( $bp->loggedin_user->is_super_admin )
		return $results;

	$current_user_id = get_current_user_id();
	$replied_ids = get_user_meta( $current_user_id, 'bpmc_replied_user_ids', true );
	$control_current_user = New BP_Messaging_Control( $current_user_id );

	if ( bp_current_component() == 'messages' ) {
		foreach ( $results as $key => $user ) {
			if ( $control_current_user->is_reply_only() ) {
				if ( isset( $replied_ids ) && $replied_ids != '' && ( in_array( $user->user_id, $replied_ids ) || user_can( $user->user_id, 'administrator' ) ) ) {
					$replied = true;
				}

			}
			
			//$replied = true;
			$controlpm = New BP_Messaging_Control( $user->user_id );	
			if ( $controlpm->is_disabled() || ( $control_current_user->is_admin_only() && ! user_can( $user->user_id, 'administrator' ) ) || $control_current_user->is_disabled() || ( $control_current_user->is_reply_only() && ! isset( $replied ) ) ) {
				unset( $results[$key] );
			}
		}
	}
	
	return $results;
}


// Inform user of their messaing status
add_action( 'bp_before_messages_compose_content', 'bpmc_inform_user' );

function bpmc_inform_user() {
	
	$current_user_control = New BP_Messaging_Control( get_current_user_id() );
	
	if ( $current_user_control->is_admin_only() ) {
		echo sanitize_text_field( __( 'You can only message the site administrator', 'bp-messaging-control' ) );
	} else if ( $current_user_control->is_reply_only() ) {
		echo sanitize_text_field( __( 'Your messaging is set to Reply Only, you can only message users who have previously sent you a message', 'bp-messaging-control' ) );
	}
	
} 
?>
