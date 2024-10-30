<?php
/**
 * Displays the content on the plugin settings page
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'Gglstpvrfctn_Settings_Tabs' ) ) {
	/**
	 * Class Gglstpvrfctn_Settings_Tabs for display Settings Tabs
	 */
	class Gglstpvrfctn_Settings_Tabs extends Bws_Settings_Tabs {
		public $editable_roles = array();

		/**
		 * Constructor.
		 *
		 * @access public
		 *
		 * @see Bws_Settings_Tabs::__construct() for more information on default arguments.
		 *
		 * @param string $plugin_basename Plugin basename.
		 */
		public function __construct( $plugin_basename ) {
			global $gglstpvrfctn_options, $gglstpvrfctn_plugin_info;

			$tabs = array(
				'settings'      => array( 'label' => __( 'Settings', 'bws-google-2-step-verification' ) ),
				'notifications' => array( 'label' => __( 'Notifications', 'bws-google-2-step-verification' ) ),
				'misc'          => array( 'label' => __( 'Misc', 'bws-google-2-step-verification' ) ),
				'custom_code'   => array( 'label' => __( 'Custom Code', 'bws-google-2-step-verification' ) ),
				'license'       => array( 'label' => __( 'License Key', 'bws-google-2-step-verification' ) ),
			);

			parent::__construct(
				array(
					'plugin_basename'       => $plugin_basename,
					'plugins_info'          => $gglstpvrfctn_plugin_info,
					'prefix'                => 'gglstpvrfctn',
					'default_options'       => gglstpvrfctn_get_options_default(),
					'options'               => $gglstpvrfctn_options,
					'is_network_options'    => is_network_admin(),
					'tabs'                  => $tabs,
					'wp_slug'               => 'bws-google-2-step-verification',
					'link_key'              => 'cafb0895a5730b761de64b55183d7a5b',
					'link_pn'               => '670',
					'doc_link'              => 'https://bestwebsoft.com/documentation/2-step-verification/2-step-verification-user-guide/',
				)
			);

			$this->editable_roles = get_editable_roles();

			add_filter( get_parent_class( $this ) . '_additional_restore_options', array( $this, 'additional_restore_options' ) );
			add_action( get_parent_class( $this ) . '_display_custom_messages', array( $this, 'display_custom_messages' ) );
		}

		/**
		 * Save plugin options to the database
		 *
		 * @access public
		 * @return array The action results.
		 */
		public function save_options() {
			$message = '';
			$notice  = '';
			$error   = '';

			$submit_methods = array(
				'email',
				'authenticator',
				'backup_code',
				'sms',
				'question',
			);
			$submit_checkboxes = array(
				'notification_fail',
			);
			$submit_firebase_set = array(
				'apikey',
			);

			if ( ! isset( $_POST['gglstpvrfctn_settings_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gglstpvrfctn_settings_nonce_field'] ) ), 'gglstpvrfctn_settings_action' ) ) {
				 esc_html_e( 'Sorry, your nonce did not verify.', 'bws-google-2-step-verification' );
				 exit;
			} else {
				foreach ( $submit_methods as $method ) {
					$this->options['methods'][ $method ] = isset( $_POST[ "gglstpvrfctn_method_{$method}" ] ) ? 1 : 0;
				}

				foreach ( $submit_checkboxes as $option ) {
					$this->options[ $option ] = isset( $_POST[ "gglstpvrfctn_{$option}" ] ) ? 1 : 0;
				}

				foreach ( $submit_firebase_set as $firebase_set ) {
					$this->options['firebase'][ $firebase_set ] = isset( $_POST[ 'gglstpvrfctn_firebase_' . $firebase_set ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'gglstpvrfctn_firebase_' . $firebase_set ] ) ) : '';
				}
				$this->options['default_email_method']      = isset( $_POST['default_email_method'] ) ? 1 : 0;
				$this->options['email_expiration']          = isset( $_POST['gglstpvrfctn_email_expiration'] ) ? absint( $_POST['gglstpvrfctn_email_expiration'] ) : 3;
				$this->options['authenticator_time_window'] = isset( $_POST['gglstpvrfctn_authenticator_time_window'] ) ? absint( $_POST['gglstpvrfctn_authenticator_time_window'] ) : 0;

				$this->options['enabled_roles'] = array();
				if ( isset( $_POST['gglstpvrfctn-all-roles'] ) ) {
					$this->options['enabled_roles'] = array_keys( $this->editable_roles );
				} else {
					$this->options['enabled_roles'] = array( 'administrator' );
				}

				$message = __( 'Settings saved.', 'bws-google-2-step-verification' );

				update_option( 'gglstpvrfctn_options', $this->options );
			}

			return compact( 'message', 'notice', 'error' );
		}

		/**
		 * Display custom error\message\notice
		 *
		 * @access public
		 * @param array $save_results Array with error\message\notice.
		 */
		public function display_custom_messages( $save_results ) {
			global $current_user; ?>
			<noscript><div class="error below-h2"><p><strong><?php esc_html_e( 'Please enable JavaScript in Your browser.', 'bws-google-2-step-verification' ); ?></strong></p></div></noscript>
			<?php if ( ! get_user_meta( $current_user->ID, 'gglstpvrfctn_hide_settings_banner' ) ) { ?>
				<div class="updated bws-notice notice is-dismissible below-h2 gglstpvrfctn-banner gglstpvrfctn-settings-banner" >
					<form class="gglstpvrfctn-banner-form" action="" method="post">
						<p>
							<?php
							printf(
								'<strong>%s:</strong> %s',
								esc_html__( 'Note', 'bws-google-2-step-verification' ),
								esc_html__( 'Users should enable 2-step verification option for their accounts on the personal profile page.', 'bws-google-2-step-verification' )
							);
							?>
						</p>
						<button class="notice-dismiss gglstpvrfctn-banner-dismiss gglstpvrfctn-settings-banner-dismiss" title="<?php esc_html_e( 'Close notice', 'bws-google-2-step-verification' ); ?>"></button>
						<input type="hidden" id="gglstpvrfctn_hide_banner" name="gglstpvrfctn_hide_banner" value="settings" />
						<input type="hidden" id="gglstpvrfctn_settings_nonce" name="gglstpvrfctn_settings_nonce" value="<?php echo esc_html( wp_create_nonce( 'gglstpvrfctn-settings-nonce' ) ); ?>">
					</form>
				</div>
				<?php
			}
		}

		/**
		 * Display Plugin Settings tab
		 */
		public function tab_settings() {
			?>
			<h3 class="bws_tab_label"><?php esc_html_e( '2-Step Verification Settings', 'bws-google-2-step-verification' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Verification Methods', 'bws-google-2-step-verification' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="checkbox" class="bws_option_affect" value="1" name="gglstpvrfctn_method_email" data-affect-show=".gglstpvrfctn_email_settings" <?php checked( 1, $this->options['methods']['email'] ); ?>/>&nbsp;<?php esc_html_e( 'Email', 'bws-google-2-step-verification' ); ?>
							</label><br>
							<label>
								<input type="checkbox" value="1" name="gglstpvrfctn_method_authenticator" <?php checked( 1, $this->options['methods']['authenticator'] ); ?>/>&nbsp;<?php esc_html_e( 'Authenticator app', 'bws-google-2-step-verification' ); ?>
							</label><br>
							<label>
								<input type="checkbox" value="1" name="gglstpvrfctn_method_backup_code" <?php checked( 1, $this->options['methods']['backup_code'] ); ?>/>&nbsp;<?php esc_html_e( 'Backup codes', 'bws-google-2-step-verification' ); ?>
							</label><br>
							<label>
								<input type="checkbox" class="bws_option_affect" value="1" name="gglstpvrfctn_method_sms" data-affect-show=".gglstpvrfctn_sms_settings" <?php checked( 1, $this->options['methods']['sms'] ); ?>/>&nbsp;<?php esc_html_e( 'SMS code', 'bws-google-2-step-verification' ); ?>
							</label><br>
							<label>
								<input type="checkbox" value="1" name="gglstpvrfctn_method_question" <?php checked( 1, $this->options['methods']['question'] ); ?>/>&nbsp;<?php esc_html_e( 'Secret question', 'bws-google-2-step-verification' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
				<!-- Settings block for default verification -->
				<tr class="gglstpvrfctn_email_settings">
					<th scope="row"><?php esc_html_e( 'Email Verification by Default ', 'bws-google-2-step-verification' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="checkbox" value="1" name="default_email_method" <?php checked( 1, $this->options['default_email_method'] ); ?>/>&nbsp;<span class="bws_info"><?php esc_html_e( 'Enable to apply 2-step authentication via email to the new user by default.', 'bws-google-2-step-verification' ); ?></span>
							</label><br />
						</fieldset>
					</td>
				</tr>
				<?php if ( ! $this->hide_pro_tabs ) { ?>
					</table>
						<div class="bws_pro_version_bloc">
						<div class="bws_pro_version_table_bloc">
								<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'bws-google-2-step-verification' ); ?>"></button>
								<div class="bws_table_bg"></div>
								<table class="form-table bws_pro_version">
									<tr valign="top">
										<th scope="row"><?php esc_html_e( 'Verification Methods for Non-registered Users ', 'bws-google-2-step-verification' ); ?></th>
										<td>
											<fieldset>
												<label>
													<input <?php disabled( true ); ?> type="checkbox" value="1" name="gglstpvrfctn_method_unregister_sms" />&nbsp;
													<?php esc_html_e( 'SMS code', 'bws-google-2-step-verification' ); ?>
												</label><br />
											</fieldset>
										</td>
									</tr>
								</table>
							</div>
							<?php $this->bws_pro_block_links(); ?>
						</div>
					<table class="form-table">
				<?php } ?>
				<tr class="gglstpvrfctn_sms_settings">
					<th scope="row"><?php esc_html_e( 'SMS Settings', 'bws-google-2-step-verification' ); ?></th>
					<td>
						<fieldset>
							<span class="bws_info">
							<?php
							printf(
								'%s&nbsp;<a href="%2s" target="_blank">%3s</a>&nbsp;%4s',
								esc_html__( 'Register your website in', 'bws-google-2-step-verification' ),
								'https://console.firebase.google.com/',
								esc_html__( 'Firebase console', 'bws-google-2-step-verification' ),
								esc_html__( 'to use the SMS code verification method. After registration add the API key into the field below.', 'bws-google-2-step-verification' )
							);
							?>
							</span><br><br>
							<span class="bws_info">
							<?php
							printf(
								'%s&nbsp;<a href="%2s" target="_blank">%3s</a>',
								esc_html__( 'Need help to setup a Firebase account?', 'bws-google-2-step-verification' ),
								'https://bestwebsoft.com/documentation/2-step-verification/2-step-verification-user-guide/',
								esc_html__( 'Learn More', 'bws-google-2-step-verification' )
							);
							?>
							</span><br><br>
							<label><?php esc_html_e( 'API Key', 'bws-google-2-step-verification' ); ?></label><br>
							<input type="text" name="gglstpvrfctn_firebase_apikey" id="gglstpvrfctn_firebase_apikey" value="<?php echo esc_attr( $this->options['firebase']['apikey'] ); ?>" /><br><br>
							<input type="button" class="button hide-if-no-js" id="gglstpvrfctn_firebase_test_button" value="<?php esc_html_e( 'Test Firebase SMS Auth', 'bws-google-2-step-verification' ); ?>" />
							<div class="hide-if-no-js" id="gglstpvrfctn_firebase_test">
								<label id="gglstpvrfctn-phone-label"><?php esc_html_e( 'Test phone number', 'bws-google-2-step-verification' ); ?></label><br>
								<input type="tel" class="gglstpvrfctn-test-userphone" name="gglstpvrfctn_test_phone" placeholder="+1234567890" value="">
								<input type="button" class="button button-primary" id="gglstpvrfctn_firebase_test_sms" value="<?php esc_html_e( 'Send test SMS', 'bws-google-2-step-verification' ); ?>" /><br>
								<span class="gglstpvrfctn-error"><?php esc_html_e( 'A valid phone number is required', 'bws-google-2-step-verification' ); ?></span>
								<div id="gglstpvrfctn-test-recaptcha-container" style="transform: scale(0.9);-webkit-transform: scale(0.9);transform-origin :0 0;-webkit-transform-origin: 0 0;"></div>
								<div id="gglstpvrfctn-test-code-label">
									<label >
									<input type="text" id="gglstpvrfctn-test-code" value="" size="8" />&nbsp;<span class="bws_info">
									<?php esc_html_e( 'Enter the code from sms and click \'Test code\' button ', 'bws-google-2-step-verification' ); ?>.</span>
									<br>
									</label><br>
									<input type="button" class="button button-primary" id="gglstpvrfctn_firebase_test_code_button" value="<?php esc_html_e( 'Test code', 'bws-google-2-step-verification' ); ?>" />
								</div>
								<div class="gglstpvrfctn-firebase-test-result">
								</div>
							</div>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Verification for', 'bws-google-2-step-verification' ); ?></th>
					<td>
						<label>
							<input type="checkbox" id="gglstpvrfctn-all-roles" name="gglstpvrfctn-all-roles" <?php checked( count( $this->options['enabled_roles'] ) == count( $this->editable_roles ) ); ?>/>&nbsp;
							<span class="gglstpvrfctn-role-name"><?php esc_html_e( 'All', 'bws-google-2-step-verification' ); ?></span>&nbsp;<span class="bws_info">
								<?php esc_html_e( 'Uncheck if you would like to allow 2-Step Verification only for the administrator', 'bws-google-2-step-verification' ); ?>.
							</span>
						</label>
					</td>
				</tr>
				<?php if ( ! $this->hide_pro_tabs ) { ?>
					</table>
					<div class="bws_pro_version_bloc">
						<div class="bws_pro_version_table_bloc">
							<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'bws-google-2-step-verification' ); ?>"></button>
							<div class="bws_table_bg"></div>
							<table class="form-table bws_pro_version">
								<tr valign="top">
									<th scope="row"></th>
									<td>
										<fieldset>
											<ul id="gglstpvrfctn-roles-list">
												<?php foreach ( $this->editable_roles as $role => $role_info ) { ?>
													<li>
														<label>
															<input<?php echo esc_html( $this->change_permission_attr ); ?> type='checkbox' 
																			 <?php
																				checked( count( $this->options['enabled_roles'] ) == count( $this->editable_roles ) || 'administrator' == $role );
																				disabled( true );
																				?>
															 />&nbsp;
															<span class="gglstpvrfctn-role-name"><?php echo esc_html( $role_info['name'] ); ?></span>
														</label>
													</li>
												<?php } ?>
											</ul><!-- #gglstpvrfctn-roles-list -->
										</fieldset>
									</td>
								</tr>
							</table>
						</div>
						<?php $this->bws_pro_block_links(); ?>
					</div>
			<table class="form-table">
				<?php } ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Email Codes Expiration Time', 'bws-google-2-step-verification' ); ?></th>
					<td>
						<label>
							<input type="number" name="gglstpvrfctn_email_expiration" id="gglstpvrfctn_email_expiration" min="0" max="240" value="<?php echo esc_attr( $this->options['email_expiration'] ); ?>">&ensp;<?php esc_html_e( 'minute(-s)', 'bws-google-2-step-verification' ); ?>
							<div class="bws_info"><?php printf( esc_html__( 'Set "%d" to disable expiration time.', 'bws-google-2-step-verification' ), 0 ); ?></div>
						</label>
					</td>
				</tr>
				<tr id="gglstpvrfctn-authenticator-time-window-row">
					<th scope="row"><?php esc_html_e( 'Authenticator Time Window', 'bws-google-2-step-verification' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="number" name="gglstpvrfctn_authenticator_time_window" id="gglstpvrfctn_authenticator_time_window" min="0" max="10" value="<?php echo esc_attr( $this->options['authenticator_time_window'] ); ?>">&ensp;
								<?php
								esc_html_e( 'minute(-s)', 'bws-google-2-step-verification' );
								echo wp_kses_post( bws_add_help_box( __( 'Time-based codes generation depends upon the time. The time on your web-server and on device with Authenticator app should be the same. You can increase Authenticator time window if you want to allow some difference between your web-server\'s and user device\'s time.', 'bws-google-2-step-verification' ) ) );
								?>
								<div class="bws_info">
									<?php printf( esc_html__( 'Set "%1$d" to use default value (default is %2$d sec).', 'bws-google-2-step-verification' ), 0, 30 ); ?>
								</div>
							</label>
						</fieldset>
					</td>
				</tr>
			</table>
			<?php wp_nonce_field( 'gglstpvrfctn_settings_action', 'gglstpvrfctn_settings_nonce_field' ); ?>
			<?php
		}

		/**
		 * Display plugin Notifications tab
		 */
		public function tab_notifications() {
			?>
			<h3 class="bws_tab_label"><?php esc_html_e( '2-Step Notifications Settings', 'bws-google-2-step-verification' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Email Notifications', 'bws-google-2-step-verification' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="checkbox" id="gglstpvrfctn_notification_fail" name="gglstpvrfctn_notification_fail" value="1" <?php checked( 1, $this->options['notification_fail'] ); ?>/>&nbsp;<span class="bws_info"><?php esc_html_e( 'Enable to activate failed verification attempts notifications option for users (users should enable appropriate option in their profiles to receive email notifications).', 'bws-google-2-step-verification' ); ?></span>
							</label>
						</fieldset>
					</td>
				</tr>
			</table>
			<?php if ( ! $this->hide_pro_tabs ) { ?>
				<div class="bws_pro_version_bloc">
					<div class="bws_pro_version_table_bloc">
						<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'bws-google-2-step-verification' ); ?>"></button>
						<div class="bws_table_bg"></div>
						<table class="form-table bws_pro_version">
							<tr>
								<th scope="row"><?php esc_html_e( 'Subject', 'bws-google-2-step-verification' ); ?></th>
								<td>
									<textarea <?php disabled( true ); ?>><?php echo esc_html( $this->options['notification_fail_email_subject'] ); ?></textarea>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Message', 'bws-google-2-step-verification' ); ?></th>
								<td>
									<textarea <?php disabled( true ); ?> rows="10"><?php echo esc_html( $this->options['notification_fail_email_message'] ); ?></textarea>
									<div class="bws_info">
										<p><span><?php esc_html_e( 'Allowed variables', 'bws-google-2-step-verification' ); ?>:</span></p>
										<p>{user_name} - <?php esc_html_e( 'User name', 'bws-google-2-step-verification' ); ?></p>
										<p>{user_email} - <?php esc_html_e( 'User email', 'bws-google-2-step-verification' ); ?></p>
										<p>{site_name} - <?php esc_html_e( 'Site name', 'bws-google-2-step-verification' ); ?></p>
										<p>{site_url} - <?php esc_html_e( 'Site URL', 'bws-google-2-step-verification' ); ?></p>
										<p>{when} - <?php esc_html_e( 'Date and time when the IP address was blocked', 'bws-google-2-step-verification' ); ?></p>
										<p>{ip} - <?php esc_html_e( 'Blocked IP address', 'bws-google-2-step-verification' ); ?></p>
										<p>{profile_page} - <?php esc_html_e( 'Profile settings page URL', 'bws-google-2-step-verification' ); ?></p>
									</div>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Restore Default Email Notifications', 'bws-google-2-step-verification' ); ?></th>
								<td>
									<input type="button" class="button" disabled="disabled" value="<?php esc_html_e( 'Restore Email Notifications', 'bws-google-2-step-verification' ); ?>">
									<div class="bws_info">
										<p><?php esc_html_e( 'Restore default email notifications.', 'bws-google-2-step-verification' ); ?></p>
									</div>
								</td>
							</tr>
						</table>
					</div>
					<?php $this->bws_pro_block_links(); ?>
				</div>
				<?php
			}
		}

		/**
		 * Additional actions on 'Restore Settings'.
		 *
		 * @access public
		 *
		 @param array $options Array with options.
		 @return array $options Array with options.
		 */
		public function additional_restore_options( $options ) {
			if ( ! $this->is_multisite ) {
				$metafields = array(
					'user_secret',
					'user_options',
					'backup_code',
					'email_code',
					'email_init_time',
				);
				foreach ( $metafields as $metafield ) {
					delete_metadata( 'user', 1, "gglstpvrfctn_{$metafield}", false, true );
				}
			}
			return $options;
		}
	}
}
