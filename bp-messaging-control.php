<?php
if ( !defined( 'ABSPATH' ) ) exit;

/*
*
* @package bp-messaging-control
* 
*/


Class BP_Messaging_Control {
	
	private $data = null;
	private $id = null;
	private $disabled = false;
	private $reply_only = false;
	private $admin_only = false;
	private $quota_met = false;
	private $capped = false;
	private $capped_limit = 'unlimited';
	private $character_limit = 100000;
	private $mentions_disabled = false;
	private $mentions_admin_only = false;
	private $mentions_reply_only = false;
	private $mentions_quota_met = false;
	private $mentions_capped = false;
	private $mentions_capped_limit = 'unlimited';
	private $mentions_character_limit = 100000;
	
	function bp_messaging_control( $id = null ) {
		$this->__construct($id);
	}

	function __construct( $id = null ) {
		global $bp;

		if ( !$id && bp_displayed_user_id() ) {
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
		$unlimited_quota_roles = array();
		$mentions_disabled_roles = array();
		$mentions_admin_only_roles = array();
		$mentions_reply_only_roles = array();
		$mentions_unlimited_quota_roles = array();
		
		foreach( $settings as $role => $option ) {
			if ( $role == 'quota_time_setting' || $role == 'mentions_quota_time_setting' ) continue;
			if ( isset( $option['role_option'] ) && $option['role_option'] == 'no_messaging' ) {
				$disabled_roles[] = $role;
			}
			if ( isset( $option['role_option'] ) && $option['role_option'] == 'admin_only' )
				$admin_only_roles[] = $role;
			if (  isset( $option['role_option'] ) &&$option['role_option'] == 'reply_only' )
				$reply_only_roles[] = $role;
			if (  isset( $option['quota_option'] ) &&$option['quota_option'] == 'unlimited' )
				$unlimited_quota_roles[] = $role;
			if ( isset( $option['mentions_option'] ) && $option['mentions_option'] == 'disabled' ) {
				$mentions_disabled_roles[] = $role;
			}
			if ( isset( $option['mentions_option'] ) && $option['mentions_option'] == 'admin_only' ) {
				$mentions_admin_only_roles[] = $role;
			}
			if ( isset( $option['mentions_option'] ) && $option['mentions_option'] == 'reply_only' ) {
				$mentions_reply_only_roles[] = $role;
			}
			if ( isset( $option['mentions_quota_option'] ) && $option['mentions_quota_option'] == 'unlimited' )
				$mentions_unlimited_quota_roles[] = $role;

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
		
		// Check for message character limitations
		foreach ( $user_roles_array as $key => $role ) {
			$limit = isset( $settings[$role]['character_limit'] ) && is_numeric( $settings[$role]['character_limit'] ) ? $settings[$role]['character_limit'] : 'unlimited';
			if ( is_numeric( $limit ) ) {
				if ( $limit < $this->character_limit ) {
					$this->character_limit = $limit;
				}
			}
		}
		
		// Quota met check
		$user_email_count = get_user_meta( $this->id, 'bpmc_user_email_count', true );
		
		if ( ! isset( $user_email_count ) )
			$user_email_count = 0;

		foreach ( $user_roles_array as $key => $role ) {
			if ( ! isset( $settings[$role] ) || $role == 'administrator' ) continue;
			if ( ! in_array( $role, $unlimited_quota_roles ) ) {
				$this->capped = true;
				
				if ( isset( $settings[$role]['quota_option'] ) )
					$role_quota = $settings[$role]['quota_option'];
				
				if ( isset( $settings['quota_time_setting'] ) )
					$quota_time_setting = $settings['quota_time_setting'];
				
				if ( isset( $role_quota ) && $quota_time_setting == 'month' ) {
					if ( isset($user_email_count['month'] ) && $user_email_count['month'] == current_time('m') ) {
						if ( $role_quota - $user_email_count['month-count'] <= 0 ) {
							$this->capped_limit = 0;
						} else {
							$this->capped_limit = $role_quota - $user_email_count['month-count'];
						}
						if ( isset( $user_email_count['month-count'] ) && $user_email_count['month-count'] >= $role_quota ) {
							$this->quota_met = true;
						}
					} else {
						$this->capped_limit = $role_quota;
					}
					
				} else if ( isset( $role_quota ) && $quota_time_setting == 'week' ) {
					if ( isset($user_email_count['week'] ) && $user_email_count['week'] == current_time('W') ) {
						if ( $role_quota - $user_email_count['week-count'] <= 0 ) {
							$this->capped_limit = 0;
						} else {
							$this->capped_limit = $role_quota - $user_email_count['week-count'];
						}
						if ( isset( $user_email_count['week-count'] ) && $user_email_count['week-count'] >= $role_quota ) {
							$this->quota_met = true;
						}
					} else {
						$this->capped_limit = $role_quota;
					}
					
				
				} else if ( isset( $role_quota ) && $quota_time_setting == 'day' ) {
					if ( isset($user_email_count['day'] ) && $user_email_count['day'] == current_time('d') ) {
						if ( $role_quota - $user_email_count['day-count'] <= 0 ) {
							$this->capped_limit = 0;
						} else {
							$this->capped_limit = $role_quota - $user_email_count['day-count'];
						}
						if ( isset( $user_email_count['day-count'] ) && $user_email_count['day-count'] >= $role_quota ) {
							$this->quota_met = true;
						}
					} else {
						$this->capped_limit = $role_quota;
					}
					
				}
			}
		}

		// Role check for @mentions disabled restrictions
		foreach ( $user_roles_array as $key => $role ) {
			if ( in_array( $role, $mentions_disabled_roles ) )
				$this->mentions_disabled = true;
		}

		// Role check for @mentions admin only restrictions
		foreach ( $user_roles_array as $key => $role ) {
			if ( in_array( $role, $mentions_admin_only_roles ) )
				$this->mentions_admin_only = true;
		}

		// Role check for @mentions admin only restrictions
		foreach ( $user_roles_array as $key => $role ) {
			if ( in_array( $role, $mentions_reply_only_roles ) )
				$this->mentions_reply_only = true;
		}

		// Check for mentions character limitations
		foreach ( $user_roles_array as $key => $role ) {
			$limit = isset( $settings[$role]['mentions_character_limit'] ) && is_numeric( $settings[$role]['mentions_character_limit'] ) ? $settings[$role]['mentions_character_limit'] : 'unlimited';
			if ( is_numeric( $limit ) ) {
				if ( $limit < $this->mentions_character_limit ) {
					$this->mentions_character_limit = $limit;
				}
			}
		}
		
		// Mentions Quota met check
		$mentions_user_email_count = get_user_meta( $this->id, 'bpmc_mentions_user_email_count', true );
		if ( ! isset( $mentions_user_email_count ) )
			$mentions_user_email_count = 0;

		foreach ( $user_roles_array as $key => $role ) {
			if ( ! isset( $settings[$role] ) || $role == 'administrator' ) continue;
			if ( ! in_array( $role, $mentions_unlimited_quota_roles ) ) {
				$this->mentions_capped = true;
				
				if ( isset( $settings[$role]['mentions_quota_option'] ) )
					$mentions_role_quota = $settings[$role]['mentions_quota_option'];
				
				if ( isset( $settings['mentions_quota_time_setting'] ) )
					$mentions_quota_time_setting = $settings['mentions_quota_time_setting'];
				
				if ( isset( $mentions_role_quota ) && $mentions_quota_time_setting == 'month' ) {
					if ( isset($mentions_user_email_count['month'] ) && $mentions_user_email_count['month'] == current_time('m') ) {
						if ( $mentions_role_quota - $mentions_user_email_count['month-count'] <= 0 ) {
							$this->mentions_capped_limit = 0;
						} else {
							$this->mentions_capped_limit = $mentions_role_quota - $mentions_user_email_count['month-count'];
						}
						if ( isset( $mentions_user_email_count['month-count'] ) && $mentions_user_email_count['month-count'] >= $mentions_role_quota ) {
							$this->mentions_quota_met = true;
						}
					} else {
						$this->mentions_capped_limit = $mentions_role_quota;
					}
					
				} else if ( isset( $mentions_role_quota ) && $mentions_quota_time_setting == 'week' ) {
					if ( isset($mentiions_user_email_count['week'] ) && $mentions_user_email_count['week'] == current_time('W') ) {
						if ( $mentions_role_quota - $mentions_user_email_count['week-count'] <= 0 ) {
							$this->mentions_capped_limit = 0;
						} else {
							$this->mentions_capped_limit = $mentions_role_quota - $mentions_user_email_count['week-count'];
						}
						if ( isset( $mentions_user_email_count['week-count'] ) && $mentions_user_email_count['week-count'] >= $mentions_role_quota ) {
							$this->mentions_quota_met = true;
						}
					} else {
						$this->mentions_capped_limit = $mentions_role_quota;
					}
					
				
				} else if ( isset( $mentions_role_quota ) && $mentions_quota_time_setting == 'day' ) {
					if ( isset($mentions_user_email_count['day'] ) && $mentions_user_email_count['day'] == current_time('d') ) {
						if ( $mentions_role_quota - $mentions_user_email_count['day-count'] <= 0 ) {
							$this->mentions_capped_limit = 0;
						} else {
							$this->mentions_capped_limit = $mentions_role_quota - $mentions_user_email_count['day-count'];
						}
						if ( isset( $mentions_user_email_count['day-count'] ) && $mentions_user_email_count['day-count'] >= $mentions_role_quota ) {
							$this->mentions_quota_met = true;
						}
					} else {
						$this->mentions_capped_limit = $mentions_role_quota;
					}
					
				}
			}
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

	public function is_quota_met() {
		return $this->quota_met;
	}
	
	public function is_capped() {
		return $this->capped;
	}

	public function get_capped_limit() {
		return $this->capped_limit;
	}

	public function get_character_limit() {
		return $this->character_limit;
	}

	public function is_mentions_disabled() {
		return $this->mentions_disabled;
	}

	public function is_mentions_admin_only() {
		return $this->mentions_admin_only;
	}

	public function is_mentions_reply_only() {
		return $this->mentions_reply_only;
	}

	public function is_mentions_quota_met() {
		return $this->mentions_quota_met;
	}
	
	public function is_mentions_capped() {
		return $this->mentions_capped;
	}

	public function get_mentions_capped_limit() {
		return $this->mentions_capped_limit;
	}

	public function get_mentions_character_limit() {
		return $this->mentions_character_limit;
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

	if ( $control_current_user->is_mentions_reply_only() ) {
		$mention_replied_ids = get_user_meta( $current_user_id, 'bpmc_user_have_mentioned', true );
		if ( isset( $mention_replied_ids ) && is_array( $mention_replied_ids ) && in_array( $displayed_user_id, $mention_replied_ids ) ) $mentions_replied = true;
	}

	$controlpm = New BP_Messaging_Control( $displayed_user_id );
	
	$is_admin = user_can( $bp->displayed_user_id, 'administrator' );
	
	if ( bp_is_user() &&  ( $controlpm->is_disabled() || ( $control_current_user->is_reply_only() && ! isset( $replied ) && ! $is_admin ) || ( $control_current_user->is_admin_only() && ! $is_admin ) || $control_current_user->is_disabled() || ( $control_current_user->is_quota_met() && ! $is_admin ) ) ) {
		remove_action( 'bp_member_header_actions',    'bp_send_private_message_button', 20 );
	}

	if ( bp_is_user() &&  ( $controlpm->is_mentions_disabled() || ( $control_current_user->is_mentions_reply_only() && ! isset( $mentions_replied ) && ! $is_admin ) || ( $control_current_user->is_mentions_admin_only() && ! $is_admin ) || ( $control_current_user->is_mentions_quota_met() && ! $is_admin ) ) ) {
		remove_action( 'bp_member_header_actions',    'bp_send_public_message_button', 20 );
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

	if ( $control_current_user->is_mentions_reply_only() ) {
		$mention_replied_ids = get_user_meta( $current_user_id, 'bpmc_user_have_mentioned', true );
		if ( isset( $mention_replied_ids ) && is_array( $mention_replied_ids ) && in_array( $displayed_user_id, $mention_replied_ids ) ) $mentions_replied = true;
	}

	$controlpm = New BP_Messaging_Control( $displayed_user_id );
	
	$is_admin = user_can( $bp->displayed_user_id, 'administrator' );
	
	if ( bp_is_user() &&  ( $controlpm->is_disabled() || ( $control_current_user->is_reply_only() && ! isset( $replied ) && ! $is_admin ) || ( $control_current_user->is_admin_only() && ! $is_admin ) || $control_current_user->is_disabled() || ( $control_current_user->is_quota_met() && ! $is_admin ) ) ) {
        unset( $buttons['private_message'] );
    }
	if ( bp_is_user() &&  ( ( $control_current_user->is_mentions_reply_only() && ! isset( $mentions_replied ) && ! $is_admin ) || ( $control_current_user->is_mentions_admin_only() && ! $is_admin ) || $controlpm->is_mentions_disabled()  || ( $control_current_user->is_mentions_quota_met() && ! $is_admin ) ) ) {
        unset( $buttons['public_message'] );
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
	$user_email_count = get_user_meta( $current_user_id, 'bpmc_user_email_count' );
	$settings = maybe_unserialize( get_site_option( 'bpmc_bp_messaging_control' ) );

	if ( isset( $user_email_count ) && ! empty( $user_email_count ) ) {
		$user_email_count = $user_email_count[0];
	}

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
		$recipient_is_admin = user_can( $recipient->user_id, 'administrator' );
		if ( $recipient_is_admin ) {
			$admin_destination = true;
		}
		
		if ( $controlpm->is_disabled() || ( $current_user_control->is_admin_only() && ! $recipient_is_admin ) || ( $current_user_control->is_quota_met() && ! $recipient_is_admin ) ) {
			unset( $message_info->recipients[$key] );
			$u++;
		}
		if ( ! $controlpm->is_reply_only() && $current_user_control->is_reply_only() ) {
			if ( $recipient_is_admin ) {
				$user_authorised = true;
			} else if ( isset( $send_user_ids ) && $send_user_ids != '' ) {
				if ( in_array( $recipient->user_id, $send_user_ids ) ) {
					$user_authorised = true;
				} else {
					$user_authorised = false;
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
		$u++;
	}

	if ( $u == 0 && ! isset( $admin_destination ) ) {
		
		if ( isset( $settings['quota_time_setting'] ) ) {
			if ( $settings['quota_time_setting'] == 'month' ) {
				if ( isset( $user_email_count['month'] ) && $user_email_count['month'] == current_time( 'm' ) ) {
					$user_email_count['month-count'] = $user_email_count['month-count'] + 1;
				} else {
					$user_email_count['month'] = current_time( 'm' );
					$user_email_count['month-count'] = 1;
				}
			} else if ( $settings['quota_time_setting'] == 'week' ) {
				if ( isset( $user_email_count['week'] ) && $user_email_count['week'] == current_time( 'W' ) ) {
					$user_email_count['week-count'] = $user_email_count['week-count'] + 1;
				} else {
					$user_email_count['week'] = current_time( 'W' );
					$user_email_count['week-count'] = 1;
				}
			} else if ( $settings['quota_time_setting'] == 'day' ) {
				if ( isset( $user_email_count['day'] ) && $user_email_count['day'] == current_time( 'd' ) ) {
					$user_email_count['day-count'] = $user_email_count['day-count'] + 1;
				} else {
					$user_email_count['day'] = current_time( 'd' );
					$user_email_count['day-count'] = 1;
				}
			}
		}
		update_user_meta( $current_user_id, 'bpmc_user_email_count', $user_email_count );
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
			if ( $controlpm->is_disabled() || ( $control_current_user->is_admin_only() && ! user_can( $user_id, 'administrator' ) ) || ( $control_current_user->is_reply_only() && ! isset( $replied ) && ! user_can( $user_id, 'administrator' ) ) ) {
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

	$displayed_user_id = bp_displayed_user_id();
	$current_user_id = get_current_user_id();
	$replied_ids = get_user_meta( $current_user_id, 'bpmc_replied_user_ids', true );
	if ( isset( $replied_ids ) && in_array( $bp->displayed_user_id, $replied_ids ) ) $replied = true;
	$controlpm = New BP_Messaging_Control( $displayed_user_id );
	$current_user_control = New BP_Messaging_Control( $current_user_id );
	
	if ( $controlpm->is_disabled() || ( $current_user_control->is_reply_only() && ! isset( $replied ) ) || ( $current_user_control->is_admin_only() && ! user_can( $displayed_user_id, 'administrator' ) ) || ( $controlpm->is_admin_only() && ! user_can( $current_user_id, 'administrator' ) ) || $current_user_control->is_disabled() || $current_user_control->is_quota_met() || $controlpm->is_quota_met() )
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
	
	$settings = maybe_unserialize( get_option( 'bpmc_bp_messaging_control' ) );
	$current_user_control = New BP_Messaging_Control( get_current_user_id() );
	$character_message = esc_attr__( 'You have unrestricted message length', 'bp-messaging-control' );
	
	if ( $current_user_control->get_character_limit() != 100000 ) {
		/* translators: Displays the character limit for the message. */
		$character_message = sprintf( esc_attr__( 'Your messages are limited to %d characters.', 'bp-messaging-control' ), $current_user_control->get_character_limit() );
	}
	
	if ( $current_user_control->is_admin_only() ) {
		 esc_attr_e( 'You can only message the site administrator, ', 'bp-messaging-control' ) . $character_message;
	} else if ( $current_user_control->is_reply_only() && ! $current_user_control->is_capped() ) {
		 esc_attr_e( 'Your messaging is set to Reply Only, you can only message users who have previously sent you a message', 'bp-messaging-control' ) . $character_message;
	} else if ( $current_user_control->is_reply_only() && $current_user_control->is_capped() ) {
		 esc_attr_e( 'Your messaging is set to Reply Only, you can only message users who have previously sent you a message, in addition you are only able to send another ', 'bp-messaging-control' ) . $current_user_control->get_capped_limit() . esc_attr__( ' messages this ', 'bp-messaging-control' ) . $settings['quota_time_setting']. ' '  . $character_message;
	} else if ( $current_user_control->is_capped() ) {
		 esc_attr_e( 'Your messaging is capped, you can send a further ', 'bp-messaging-control' ) . $current_user_control->get_capped_limit() . esc_attr__( ' messages this ', 'bp-messaging-control' ) . $settings['quota_time_setting'] . ' '  . $character_message;
	} else {
		echo esc_attr($character_message);
	}
	
}

function bpmc_messaging_control_control_mentions( $status ) {
	
	$control_current_user = new BP_Messaging_Control( get_current_user_id() );
	
	if ( $control_current_user->is_mentions_disabled() ) {
		return false;
	}
	
	return $status;
	
}

add_filter( 'bp_activity_do_mentions', 'bpmc_messaging_control_control_mentions' );

add_filter( 'bp_activity_mentioned_users', 'bpmc_messaging_control_filter_mentions' );

function bpmc_messaging_control_filter_mentions( $mentioned_users ) {
	
	$current_user_id = get_current_user_id();
	
	$current_user_control = new BP_Messaging_Control( $current_user_id );
	$mentions_user_email_count = get_user_meta( $current_user_id, 'bpmc_mentions_user_email_count' );
	$settings = maybe_unserialize( get_site_option( 'bpmc_bp_messaging_control' ) );
	
	if ( isset( $mentions_user_email_count ) && ! empty( $mentions_user_email_count ) ) {
		$mentions_user_email_count = $mentions_user_email_count[0];
	}

	foreach ( $mentioned_users as $mentioned_user_id => $mentioned_username ) {
		if ( user_can( $mentioned_user_id, 'manage_options' ) ) $admin_destination = true;
	}
	

	if ( $current_user_control->is_mentions_admin_only() ) {
		foreach( $mentioned_users as $user_id => $username ) {
			if (  ! user_can( $user_id, 'manage_options' ) ) {
				unset( $mentioned_users[$user_id] );
			}
		}
	}
	
	if ( $current_user_control->is_mentions_reply_only() ) {
		$allowed_replies = get_user_meta( $current_user_id, 'bpmc_user_have_mentioned', true );
		foreach( $mentioned_users as $user_id => $username ) {
			$is_admin = user_can( $user_id, 'manage_options' );
			if ( isset( $allowed_replies ) && is_array( $allowed_replies ) ) {
				if ( ( ! $is_admin && ! in_array( $user_id, $allowed_replies ) ) || ( $current_user_control->is_mentions_quota_met() && ! $is_admin ) ) {
					unset( $mentioned_users[$user_id] );
				}
			} else {
			unset( $mentioned_users[$user_id] );
			}
		}
	}
	
	if ( ! $current_user_control->is_mentions_admin_only() && ! $current_user_control->is_mentions_reply_only() ) {
		foreach( $mentioned_users as $user_id => $username ) {
			$recipient_user_control = new BP_Messaging_Control( $user_id );
			if ( ! $current_user_control->is_mentions_quota_met() || user_can( $user_id, 'manage_options' ) ) {
				if ( $recipient_user_control->is_mentions_reply_only() ) {
					$replies_allowed = get_user_meta( $user_id, 'bpmc_user_have_mentioned', true );
					if ( isset( $replies_allowed ) && is_array( $replies_allowed ) ) {
						if ( ! in_array( $user_id, $replies_allowed ) ) {
							$replies_allowed[] = $current_user_id;
						}
					} else {
						$replies_allowed = array( $current_user_id );
					}
					update_user_meta( $user_id, 'bpmc_user_have_mentioned', $replies_allowed );
				} 
			} else if ( $current_user_control->is_mentions_quota_met() ) {
				unset( $mentioned_users[$user_id] );
			}
		}
	}
	
	if ( ! isset( $admin_destination ) && count( $mentioned_users ) >= 1 ) {
		
		if ( isset( $settings['mentions_quota_time_setting'] ) ) {
			if ( $settings['mentions_quota_time_setting'] == 'month' ) {
				if ( isset( $mentions_user_email_count['month'] ) && $mentions_user_email_count['month'] == current_time( 'm' ) ) {
					$mentions_user_email_count['month-count'] = $mentions_user_email_count['month-count'] + 1;
				} else {
					$mentions_user_email_count['month'] = current_time( 'm' );
					$mentions_user_email_count['month-count'] = 1;
				}
			} else if ( $settings['mentions_quota_time_setting'] == 'week' ) {
				if ( isset( $mentions_user_email_count['week'] ) && $mentions_user_email_count['week'] == current_time( 'W' ) ) {
					$mentions_user_email_count['week-count'] = $mentions_user_email_count['week-count'] + 1;
				} else {
					$mentions_user_email_count['week'] = current_time( 'W' );
					$mentions_user_email_count['week-count'] = 1;
				}
			} else if ( $settings['mentions_quota_time_setting'] == 'day' ) {
				if ( isset( $mentions_user_email_count['day'] ) && $mentions_user_email_count['day'] == current_time( 'd' ) ) {
					$mentions_user_email_count['day-count'] = $mentions_user_email_count['day-count'] + 1;
				} else {
					$mentions_user_email_count['day'] = current_time( 'd' );
					$mentions_user_email_count['day-count'] = 1;
				}
			}
		}
		update_user_meta( $current_user_id, 'bpmc_mentions_user_email_count', $mentions_user_email_count );
	}
	
	
	return $mentioned_users;
}

function bpmc_messaging_control_filter_message_content( $content ) {
	
	if ( current_user_can( 'manage_options' ) ) return $content;
	
	$current_user_control = New BP_Messaging_Control( get_current_user_id() );
	if ( $current_user_control->get_character_limit() != 100000 ) {
	
		$content = bp_create_excerpt( html_entity_decode( $content ), $current_user_control->get_character_limit(), array(
			'html'				=> true,
			'filter_shortcodes' => true,
			'strip_tags'        => true,
			'remove_links'      => false
		) );
	}
	
	return $content;
}

add_filter( 'messages_message_content_before_save', 'bpmc_messaging_control_filter_message_content' );

/**
 * Make sure to only enforce activity length for activity updates and comments
 *
 * @param string $type
 * @return string $type
 * @since 1.6.0
 */
function bpmc_messaging_control_maybe_verify_activity_length( $type ) {
	
	$whitelist = array( 'activity_update', 'activity_comment' );
	$whitelist = apply_filters( 'bpmc_messaging_control_activity_types', $whitelist );

	if ( in_array( $type, $whitelist ) ) {
		add_filter( 'bp_activity_content_before_save', 'bpmc_messaging_control_filter_mention_content' );
	}

	return $type;
}

add_filter( "bp_activity_type_before_save", 'bpmc_messaging_control_maybe_verify_activity_length' );

// Enforce character limit limit on activity updates and comments

function bpmc_messaging_control_filter_mention_content( $content ) {
	
	if ( current_user_can( 'manage_options' ) ) return $content;
	
	$current_user_control = New BP_Messaging_Control( get_current_user_id() );
	if ( $current_user_control->get_mentions_character_limit() != 100000 ) {
	
		$content = bp_create_excerpt( html_entity_decode( $content ), $current_user_control->get_mentions_character_limit(), array(
			'html' 				=> true,
			'filter_shortcodes' => true,
			'strip_tags'        => true,
			'remove_links'      => false
		) );
	}
	
	return $content;
}





/**
 * Email message recipients to alert them of a new unread private message.
 * This replaces messages_notification_new_message() in order to provide
 * variable length excerpts for included content rather than send the entire content.
 *
 * @since 1.5.1
 *
 */

function bp_email_content_length( $formatted_tokens, $tokens, $obj ) {
	
	$settings = maybe_unserialize( get_site_option( 'bpmc_bp_messaging_control' ) );
	if ( isset( $settings['usermessage_length'] ) && is_numeric( $settings['usermessage_length'] ) ) {
		$character_limit = $settings['usermessage_length'];
	} else {
		$character_limit = 'unlimited';
	}
	if ( $character_limit != 'unlimited' ) {
		$formatted_tokens['usermessage'] =  wp_trim_words( $formatted_tokens['usermessage'], $character_limit, '...' );
	}
	
	return $formatted_tokens;
	
}
add_filter( 'bp_email_set_tokens', 'bp_email_content_length', 10, 3 );

// Allow outgoing new message notification emails to have a controlled message content length.

function bpmc_messages_notification_new_message( $raw_args = array() ) {

	$settings = maybe_unserialize( get_site_option( 'bpmc_bp_messaging_control' ) );
	if ( isset( $settings['excerpt_length'] ) && is_numeric( $settings['excerpt_length'] ) ) {
		$character_limit = $settings['excerpt_length'];
	} else {
		$character_limit = 'unlimited';
	}

	if ( is_object( $raw_args ) ) {
		$args = (array) $raw_args;
	} else {
		$args = $raw_args;
	}

	// These should be extracted below.
	$recipients    = array();
	$email_subject = $email_content = '';
	$sender_id     = 0;

	// Barf.
	extract( $args );

	if ( empty( $recipients ) ) {
		return;
	}

	$sender_name = bp_core_get_user_displayname( $sender_id );

	if ( isset( $message ) ) {
		$message = wpautop( $message );
	} else {
		$message = '';
	}

	// Send an email to each recipient.
	foreach ( $recipients as $recipient ) {
		if ( $sender_id == $recipient->user_id || 'no' == bp_get_user_meta( $recipient->user_id, 'notification_messages_new_message', true ) ) {
			continue;
		}

		// User data and links.
		$ud = get_userdata( $recipient->user_id );
		if ( empty( $ud ) ) {
			continue;
		}

		$unsubscribe_args = array(
			'user_id'           => $recipient->user_id,
			'notification_type' => 'messages-unread',
		);

		$message = wp_strip_all_tags( stripslashes( $message ) );
		if ( $character_limit != 'unlimited' ) {
			$message = bp_create_excerpt( html_entity_decode( $message ), $character_limit, array(
				'html' 				=> true,
				'filter_shortcodes' => true,
				'strip_tags'        => true,
				'remove_links'      => false
			) );
		}
		
		bp_send_email( 'messages-unread', $ud, array(
			'tokens' => array(
				'usermessage' => $message,
				'message.url' => esc_url( bp_members_get_user_url( $recipient->user_id ) . bp_get_messages_slug() . '/view/' . $thread_id . '/' ),
				'sender.name' => $sender_name,
				'usersubject' => sanitize_text_field( stripslashes( $subject ) ),
				'unsubscribe' => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
			),
		) );
	}

	/**
	 * Fires after the sending of a new message email notification.
	 *
	 * @since 1.5.0
	 * @deprecated 2.5.0 Use the filters in BP_Email.
	 *                   $email_subject and $email_content arguments unset and deprecated.
	 *
	 * @param array  $recipients    User IDs of recipients.
	 * @param string $email_subject Deprecated in 2.5; now an empty string.
	 * @param string $email_content Deprecated in 2.5; now an empty string.
	 * @param array  $args          Array of originally provided arguments.
	 */
	do_action( 'bpmc_messages_sent_notification_email', $recipients, '', '', $args );
}
remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
add_action( 'messages_message_sent', 'bpmc_messages_notification_new_message', 10 );

function bpmc_user_deletion_notify( $user_id ) {
	
	$data = maybe_unserialize( get_option( 'bpmc_bp_messaging_control') );
	$user_deletion_notify = isset( $data['user_deletion_notify'] ) ? $data['user_deletion_notify'] : FALSE;
	
	if ( ! $user_deletion_notify ) {
		return;
	}
	$site_admin_email = get_option( 'admin_email' );
	$userdata = get_userdata( $user_id );
	$subject                  = esc_attr__( 'User deleted', 'bp-messaging-control' ) . ': "' . $userdata->user_login . '"';
	$message                  = esc_attr__( 'A user has been deleted.', 'bp-messaging-control' );
	$message                 .= "\r\n\r\n";
	$message                 .= esc_attr__( 'User Name', 'bp-messaging-control' ) . ': ' . $userdata->user_login . "\r\n";
	$message                 .= esc_attr__( 'User ID', 'bp-messaging-control' ) . ': ' . $user_id . "\r\n";
	$result                   = wp_mail( $site_admin_email, $subject, $message );
	
}
add_action( 'bp_core_pre_delete_account', 'bpmc_user_deletion_notify', 10 );