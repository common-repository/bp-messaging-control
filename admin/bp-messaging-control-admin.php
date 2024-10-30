<?php 
if(!defined('ABSPATH')) {
	exit;
}

function bpmc_bp_messaging_control_admin() {

	/* If the form has been submitted and the admin referrer checks out, save the settings */
	if ( isset( $_POST['submit'] ) && check_admin_referer('bpmc_bp_messaging_control_admin') ) {
	
		$new = array();
		$sites_roles = get_editable_roles();
		
		foreach ( $sites_roles as $role_name => $role_capabilities ) {
			
			if ( $role_name == 'administrator' ) continue;
			
			$new[$role_name]['role_option'] =esc_attr( $_POST[ 'bpmc-role-option-' . $role_name ] );
			
		}
				
		foreach ( $sites_roles as $role_name => $role_capabilities ) {
			
			if ( $role_name == 'administrator' ) continue;
			
			$new[$role_name]['character_limit'] =esc_attr( $_POST[ 'bpmc-character-limit-' . $role_name ] );
			
		}
				
		foreach ( $sites_roles as $role_name => $role_capabilities ) {
			
			if ( $role_name == 'administrator' ) continue;
			
			$new[$role_name]['quota_option'] =esc_attr( $_POST[ 'bpmc-quota-option-' . $role_name ] );
			
		}
		
		$new['quota_time_setting'] =esc_attr( $_POST[ 'bpmc-quota-time' ] );
		
		$new['excerpt_length'] =esc_attr( $_POST[ 'bpmc-new-message-content-limit' ] );
		
		foreach ( $sites_roles as $role_name => $role_capabilities ) {
			
			if ( $role_name == 'administrator' ) continue;
			
			$new[$role_name]['mentions_option'] =esc_attr( $_POST[ 'bpmc-mentions-option-' . $role_name ] );
			
		}

		foreach ( $sites_roles as $role_name => $role_capabilities ) {
			
			if ( $role_name == 'administrator' ) continue;
			
			$new[$role_name]['mentions_character_limit'] =esc_attr( $_POST[ 'bpmc-mentions-character-limit-' . $role_name ] );
			
		}

		foreach ( $sites_roles as $role_name => $role_capabilities ) {
			
			if ( $role_name == 'administrator' ) continue;
			
			$new[$role_name]['mentions_quota_option'] =esc_attr( $_POST[ 'bpmc-mentions-quota-option-' . $role_name ] );
			
		}
		
		$new['mentions_quota_time_setting'] =esc_attr( $_POST[ 'bpmc-mentions-quota-time' ] );

		$new['usermessage_length'] =esc_attr( $_POST[ 'bpmc-usermessage-content-limit' ] );
		
		$new['user_deletion_notify'] =esc_attr( $_POST[ 'bpmc-user-deletion-notify' ] );

		update_option( 'bpmc_bp_messaging_control', $new );
		
		$updated = true;
	}
	
	$data = maybe_unserialize( get_option( 'bpmc_bp_messaging_control') );
	

	// Get the proper URL for submitting the settings form. (Settings API workaround) - boone
	$url_base = function_exists( 'is_network_admin' ) && is_network_admin() ? network_admin_url( 'admin.php?page=bp-messaging-control-settings' ) : admin_url( 'admin.php?page=bp-messaging-control-settings' );
	
	$role_options = array(
		'no_messaging'		=> esc_attr__( 'Messaging disabled', 'bp-messaging-control' ),
		'admin_only'		=> esc_attr__( 'Message Site Admin only', 'bp-messaging-control' ),
		'reply_only'		=> esc_attr__( 'Reply only to other site users', 'bp-messaging-control' ),
		'full_messaging'	=> esc_attr__( 'Freely message all site users', 'bp-messaging-control' )
	);
	$site_roles = get_editable_roles();
	$quota_options = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 25, 50, 100, 150, 250, 500, 1000, 'unlimited' );
	$character_limits = array( 100, 150, 200, 250, 300, 350, 400, 450, 500, 750, 1000, 2000, 5000, 'unlimited' );
	$message_limits = array( 25, 50, 75, 100, 150, 200, 300, 'unlimited' );
	$usermessage_limits = array( 5, 10, 15, 20, 25, 50, 75, 100, 'unlimited' );
	$quota_times = array(
		'day'				=> esc_attr__( 'Daily', 'bp-messaging-control' ),
		'week'				=> esc_attr__( 'Weekly', 'bp-messaging-control' ),
		'month'				=> esc_attr__( 'Monthly', 'bp-messaging-control' )
	);
	$mentions_options = array(
		'enabled'			=> esc_attr__( '@Mention all', 'bp-messaging-control' ),
		'admin_only'		=> esc_attr__( '@Mention admin only', 'bp-messaging-control' ),
		'reply_only'		=> esc_attr__( '@mention as reply only', 'bp-messaging-control' ),
		'disabled'			=> esc_attr__( '@Mentions disabled', 'bp-messaging-control' )
	);
	?>
	<div class="wrap">
		<h2><?php esc_attr_e( 'Messaging Control Settings', 'bp-messaging-control' ); ?></h2>
		
		<p><?php esc_attr_e( 'Here you can manage Private Messaging and Public Messaging ( Activity Updates, @Mentions )', 'bp-messaging-control' ); ?></p>

		<?php if ( isset($updated) ) : echo "<div id='message' class='updated fade'><p>" . esc_attr__( 'Settings Updated.', 'bp-messaging-control' ) . "</p></div>"; endif; ?>

		<form action="<?php echo esc_attr($url_base) ?>" name="bp-messaging-control-settings-form" id="bp-messaging-control-settings-form" method="post">			

			<div class="message-settings" style="display: flex;">
			
				<div class="bpmc-private-messages" style="margin-right: 50px;">

					<h2><?php esc_attr_e( 'Private Messaging Role Restrictions', 'bp-messaging-control' ); ?></h2>

					<p><?php esc_attr_e( 'Here you can set the general restrictions on private messages per role', 'bp-messaging-control' ); ?></p>

					<div>
						<?php foreach ( $site_roles as $role_name => $role_attributes ) : ?>
						
								<?php if ( $role_name == 'administrator' ) continue; ?>
								
								<?php $setting = isset( $data[$role_name]['role_option'] ) ? $data[$role_name]['role_option'] : 'full_messaging'; ?>  
								
								<input type="text" value="<?php echo esc_attr($role_name); ?>" readonly="readonly" name="bpmc-role-<?php echo esc_attr($role_name); ?>"/>
								
								<label><?php esc_attr_e( ' can ', 'bp-messaging-control' ); ?></label>
								
								<select name="bpmc-role-option-<?php echo esc_attr($role_name); ?>" id="">
									
									<?php foreach ( $role_options as $key => $role_option ) : ?>
										
										<option name="" value="<?php echo esc_attr($key); ?>" <?php if ( $key == $setting ) echo 'selected'; ?>><?php echo esc_attr($role_option); ?></option>
									
									<?php endforeach; ?>
								
								</select>
								
								<br/>
								
						<?php endforeach; ?>
						
					</div>

					<br/>
					
					<h2><?php esc_attr_e( 'Private Message Character Length Restrictions', 'bp-messaging-control' ); ?></h2>
					
					<p><?php esc_attr_e( 'Here you can set a limit for the maximum number of characters', 'bp-messaging-control' ); ?></p>

					<p><?php esc_attr_e( 'for each message.', 'bp-messaging-control' ); ?></p>

					<div>
						<?php foreach ( $site_roles as $role_name => $role_attributes ) : ?>
						
								<?php if ( $role_name == 'administrator' ) continue; ?>
								
								<?php $setting = isset( $data[$role_name]['character_limit'] ) ? $data[$role_name]['character_limit'] : 'unlimited'; ?>  
								
								<input type="text" value="<?php echo esc_attr($role_name); ?>" readonly="readonly" name="bpmc-role-<?php echo esc_attr($role_name); ?>"/>
								
								<label><?php esc_attr_e( ' is limited to ', 'bp-messaging-control' ); ?></label>
								
								<select name="bpmc-character-limit-<?php echo esc_attr($role_name); ?>" id="">
									
									<?php foreach ( $character_limits as $limit ) : ?>
										
										<option name="" value="<?php echo esc_attr($limit); ?>" <?php if ( $limit == $setting ) echo 'selected'; ?>><?php echo esc_attr($limit); ?></option>
									
									<?php endforeach; ?>
								
								</select>
								
								<label><?php esc_attr_e( ' characters', 'bp-messaging-control' ); ?></label>

								<br/>
								
						<?php endforeach; ?>
						
					</div>

					<br/>
					
					<h2><?php esc_attr_e( 'Private Messaging Quota Restrictions', 'bp-messaging-control' ); ?></h2>

					<p><?php esc_attr_e( 'Here you can set a per role quota.', 'bp-messaging-control' ); ?></p>

					<p><?php esc_attr_e( 'So even if a user can freely message all users in the general role restrictions', 'bp-messaging-control' ); ?></p>

					<p><?php esc_attr_e( 'they would be limited to their quota.', 'bp-messaging-control' ); ?></p>

					<div>
						<?php foreach ( $site_roles as $role_name => $role_attributes ) : ?>
						
								<?php if ( $role_name == 'administrator' ) continue; ?>
								
								<?php $quota_setting = isset( $data[$role_name]['quota_option'] ) ? $data[$role_name]['quota_option'] : 'unlimited'; ?>  
								
								<input type="text" value="<?php echo esc_attr($role_name); ?>" readonly="readonly" name="bpmc-quota-<?php echo esc_attr($role_name); ?>"/>
								
								<label><?php esc_attr_e( ' private message quota ', 'bp-messaging-control' ); ?></label>
								
								<select name="bpmc-quota-option-<?php echo esc_attr($role_name); ?>" id="">
									
									<?php foreach ( $quota_options as $quota_option ) : ?>
										
										<option name="" value="<?php echo esc_attr($quota_option); ?>" <?php if ( $quota_option == $quota_setting ) echo 'selected'; ?>><?php echo esc_attr($quota_option); ?></option>
									
									<?php endforeach; ?>
								
								</select>
								
								<br/>
								
						<?php endforeach; ?>
						
						<?php $quota_time_setting = isset( $data['quota_time_setting'] ) ? $data['quota_time_setting'] : 'month'; ?>
						
						<h4><?php esc_attr_e( 'Apply quota restrictions over which time period?', 'bp-messaging-control' ); ?></h5>
						
						<select name="bpmc-quota-time" id="">
							
							<?php foreach ( $quota_times as $key => $time ) : ?>
								
								<option name="" value="<?php echo esc_attr($key); ?>" <?php if ( $key == $quota_time_setting ) echo 'selected'; ?>><?php echo esc_attr($time); ?></option>
							
							<?php endforeach; ?>
						
						</select>
					</div>

					<h2><?php esc_attr_e( 'New Message Notification emails', 'bp-messaging-control' ); ?></h2>

					<p><?php esc_attr_e( 'The default is to include the full message content in the notification email', 'bp-messaging-control' ); ?></p>
					<p><?php esc_attr_e( 'This option allows you to choose the length of the message content that is sent.', 'bp-messaging-control' ); ?></p>
					
					<div>
						<?php $message_limit_setting = isset( $data['excerpt_length'] ) ? $data['excerpt_length'] : 'unlimited'; ?>

						<select name="bpmc-new-message-content-limit" id="">
							
							<?php foreach ( $message_limits as $time ) : ?>
								
								<option name="" value="<?php echo esc_attr($time); ?>" <?php if ( $time == $message_limit_setting ) echo 'selected'; ?>><?php echo esc_attr($time); ?></option>
							
							<?php endforeach; ?>
						
						</select>
						
					</div>
					
					<h2><?php esc_attr_e( 'Notify Admin of user deletion', 'bp-messaging-control' ); ?></h2>

					<p><?php esc_attr_e( 'This option will inform site admin when a user is deleted via email', 'bp-messaging-control' ); ?></p>
					
					
					<div>
						<?php $user_deletion_notify = isset( $data['user_deletion_notify'] ) ? $data['user_deletion_notify'] : FALSE; ?>

						<input type="checkbox" name="bpmc-user-deletion-notify" id="" value="1" <?php 
							if ( isset( $user_deletion_notify ) && $user_deletion_notify == '1' ) {
								echo 'checked="checked"';
							}
						?>/>
						<label for="bpmc-user-deletion-notify"><?php esc_attr_e( 'Enable Notification', 'bp-messaging-control' ) ?></label>
							
					</div>

				</div>
				<?php //style="position: absolute; top: 80px; right:0%;"?>
				<div class="bpmc-public-messages" >

					<h2><?php esc_attr_e( 'Public Messaging Role Restrictions', 'bp-messaging-control' ); ?></h2>

					<p><?php esc_attr_e( 'Public Messages happen when a user @mentions them in an activity stream', 'bp-messaging-control' ); ?></p>

					<p><?php esc_attr_e( 'The mentioned user gets a notification and an email with the ntification.', 'bp-messaging-control' ); ?></p>

					<p><?php esc_attr_e( 'Here you can set the general restrictions per role', 'bp-messaging-control' ); ?></p>

					<div>
						<?php foreach ( $site_roles as $role_name => $role_attributes ) : ?>
						
								<?php if ( $role_name == 'administrator' ) continue; ?>
								
								<?php $setting = isset( $data[$role_name]['mentions_option'] ) ? $data[$role_name]['mentions_option'] : 'enabled'; ?>  
								
								<input type="text" value="<?php echo esc_attr($role_name); ?>" readonly="readonly" name="bpmc-role-<?php echo esc_attr($role_name); ?>"/>
								
								<label><?php esc_attr_e( ' can ', 'bp-messaging-control' ); ?></label>
								
								<select name="bpmc-mentions-option-<?php echo esc_attr($role_name); ?>" id="">
									
									<?php foreach ( $mentions_options as $key => $mentions_option ) : ?>
										
										<option name="" value="<?php echo esc_attr($key); ?>" <?php if ( $key == $setting ) echo 'selected'; ?>><?php echo esc_attr($mentions_option); ?></option>
									
									<?php endforeach; ?>
								
								</select>
								
								<br/>
								
						<?php endforeach; ?>
						
					</div>

					<br/>
					
					<h2><?php esc_attr_e( 'Public Message Character Length Restrictions', 'bp-messaging-control' ); ?></h2>

					<p><?php esc_attr_e( 'Here you can set a limit for the maximum number of characters', 'bp-messaging-control' ); ?></p>

					<p><?php esc_attr_e( 'for each activity update and comment.', 'bp-messaging-control' ); ?></p>

					<div>
						<?php foreach ( $site_roles as $role_name => $role_attributes ) : ?>
						
								<?php if ( $role_name == 'administrator' ) continue; ?>
								
								<?php $setting = isset( $data[$role_name]['mentions_character_limit'] ) ? $data[$role_name]['mentions_character_limit'] : 'unlimited'; ?>  
								
								<input type="text" value="<?php echo esc_attr($role_name); ?>" readonly="readonly" name="bpmc-role-<?php echo esc_attr($role_name); ?>"/>
								
								<label><?php esc_attr_e( ' is limited to ', 'bp-messaging-control' ); ?></label>
								
								<select name="bpmc-mentions-character-limit-<?php echo esc_attr($role_name); ?>" id="">
									
									<?php foreach ( $character_limits as $limit ) : ?>
										
										<option name="" value="<?php echo esc_attr($limit); ?>" <?php if ( $limit == $setting ) echo 'selected'; ?>><?php echo esc_attr($limit); ?></option>
									
									<?php endforeach; ?>
								
								</select>
								
								<label><?php esc_attr_e( ' characters', 'bp-messaging-control' ); ?></label>

								<br/>
								
						<?php endforeach; ?>
						
					</div>

					<br/>
					
					<h2><?php esc_attr_e( 'Public Messaging Quota Restrictions', 'bp-messaging-control' ); ?></h2>

					<p><?php esc_attr_e( 'Here you can set a per role quota.', 'bp-messaging-control' ); ?></p>

					<p><?php esc_attr_e( 'So even if a user can freely message all users in the general role restrictions', 'bp-messaging-control' ); ?></p>

					<p><?php esc_attr_e( 'they would be limited to their quota.', 'bp-messaging-control' ); ?></p>

					<div>
						<?php foreach ( $site_roles as $role_name => $role_attributes ) : ?>
						
								<?php if ( $role_name == 'administrator' ) continue; ?>
								
								<?php $mentions_quota_setting = isset( $data[$role_name]['mentions_quota_option'] ) ? $data[$role_name]['mentions_quota_option'] : 'unlimited'; ?>  
								
								<input type="text" value="<?php echo esc_attr($role_name); ?>" readonly="readonly" name="bpmc-mentions-quota-<?php echo esc_attr($role_name); ?>"/>
								
								<label><?php esc_attr_e( ' public message quota ', 'bp-messaging-control' ); ?></label>
								
								<select name="bpmc-mentions-quota-option-<?php echo esc_attr($role_name); ?>" id="">
									
									<?php foreach ( $quota_options as $quota_option ) : ?>
										
										<option name="" value="<?php echo esc_attr($quota_option); ?>" <?php if ( $quota_option == $mentions_quota_setting ) echo 'selected'; ?>><?php echo esc_attr($quota_option); ?></option>
									
									<?php endforeach; ?>
								
								</select>
								
								<br/>
								
						<?php endforeach; ?>
						
						<?php $mentions_quota_time_setting = isset( $data['mentions_quota_time_setting'] ) ? $data['mentions_quota_time_setting'] : 'month'; ?>
						
						<h4><?php esc_attr_e( 'Apply quota restrictions over which time period?', 'bp-messaging-control' ); ?></h5>
						
						<select name="bpmc-mentions-quota-time" id="">
							
							<?php foreach ( $quota_times as $key => $time ) : ?>
								
								<option name="" value="<?php echo esc_attr($key); ?>" <?php if ( $key == $mentions_quota_time_setting ) echo 'selected'; ?>><?php echo esc_attr($time); ?></option>
							
							<?php endforeach; ?>
						
						</select>
					</div>

					<h2><?php esc_attr_e( 'usermessage token length', 'bp-messaging-control' ); ?></h2>

					<p><?php esc_attr_e( 'The usermessage token is used to insert content into the public message notification email', 'bp-messaging-control' ); ?></p>
					
					<p><?php esc_attr_e( 'You will see it used in the Emails admin page, where it is used to represent the email content.', 'bp-messaging-control' ); ?></p>
					
					<p><?php esc_attr_e( 'This option allows you set a limit on the size of this so that the full message is not sent in the email.', 'bp-messaging-control' ); ?></p>
					
					<div>
						<?php $usermessage_limit_setting = isset( $data['usermessage_length'] ) ? $data['usermessage_length'] : 'unlimited'; ?>

						<select name="bpmc-usermessage-content-limit" id="">
							
							<?php foreach ( $usermessage_limits as $time ) : ?>
								
								<option name="" value="<?php echo esc_attr($time); ?>" <?php if ( $time == $usermessage_limit_setting ) echo 'selected'; ?>><?php echo esc_attr($time); ?></option>
							
							<?php endforeach; ?>
						
						</select>
						
					</div>
					
				</div>
				
			</div>

			<?php wp_nonce_field( 'bpmc_bp_messaging_control_admin' ); ?>
			
			<p class="submit"><input type="submit" name="submit" value="Save Settings"/></p>
			
		</form>
		
	</div>
<?php
}

?>
