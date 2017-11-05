<?php
/**
* Plugin Name: miniOrange 2 Factor Authentication
* Plugin URI: http://miniorange.com
* Description: This plugin provides various two-factor authentication methods as an additional layer of security for wordpress login. We Support Phone Call, SMS, Email Verification, QR Code, Push, Soft Token, Google Authenticator, Authy, Security Questions(KBA), Woocommerce front-end login, Shortcodes for custom login pages.
* Version: 4.5.5
* Author: miniOrange
* Author URI: http://miniorange.com
* License: GPL2
*/
include_once dirname( __FILE__ ) . '/miniorange_2_factor_configuration.php';
include_once dirname( __FILE__ ) . '/miniorange_2_factor_mobile_configuration.php';
include_once dirname( __FILE__ ) . '/miniorange_2_factor_troubleshooting.php';
include_once dirname( __FILE__ ) . '/class-rba-attributes.php';
include_once dirname( __FILE__ ) . '/class-two-factor-setup.php';
include_once dirname( __FILE__ ) . '/class-customer-setup.php';
require('class-utility.php');
require('class-miniorange-2-factor-login.php');
require('miniorange_2_factor_support.php');
require('class-miniorange-2-factor-user-registration.php');
require('class-miniorange-2-factor-pass2fa-login.php');
define('MOAUTH_PATH', plugins_url(__FILE__));

class Miniorange_Authentication {
	
	private $defaultCustomerKey = "16555";
	private $defaultApiKey = "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";
	
	function __construct() {
	
		$mo2f_auth_types = array('OUT OF BAND EMAIL','SMS','PHONE VERIFICATION','SOFT TOKEN','MOBILE AUTHENTICATION','PUSH NOTIFICATIONS','GOOGLE AUTHENTICATOR','SMS AND EMAIL', 'AUTHY 2-FACTOR AUTHENTICATION','KBA');
		add_option( 'mo2f_auth_methods_for_users' ,$mo2f_auth_types);
		add_option( 'mo2f_inline_registration',0);
		add_option( 'mo2f_enable_mobile_support', 1);
		add_option( 'mo2f_activate_plugin', 1 );
		add_option( 'mo2f_login_policy', 1 );
		add_option( 'mo2f_msg_counter', 1 );
		add_option( 'mo2f_number_of_transactions', 1);
		add_option( 'mo2f_set_transactions', 0);
		add_option( 'mo2f_modal_display', 0);
		add_option( 'mo2f_enable_forgotphone', 1);
		add_option( 'mo2f_enable_xmlrpc', 0);
		/* App Specific Password
		add_option( 'mo_app_password', 0);
		add_action( 'init',  array( $this, 'miniorange_auth_init' ) );
		*/
		add_option( 'mo2f_disable_poweredby',0);
		add_option( 'mo2f_show_sms_transaction_message', 0);
		add_option( 'mo2f_custom_plugin_name', 'miniOrange 2-Factor');
		add_action( 'admin_menu', array( $this, 'miniorange_auth_menu' ) );
		add_action( 'admin_init',  array( $this, 'miniorange_auth_save_settings' ) );
		register_deactivation_hook(__FILE__, array( $this, 'mo_auth_deactivate'));
		add_action( 'admin_enqueue_scripts', array( $this, 'plugin_settings_style' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'plugin_settings_script' ) );
		remove_action( 'admin_notices', array( $this, 'mo_auth_success_message') );
		remove_action( 'admin_notices', array( $this, 'mo_auth_error_message') );
		add_action('admin_notices', array($this,'get_customer_SMS_transactions'));
		
		
		global $wp_roles;
		if (!isset($wp_roles))
			$wp_roles = new WP_Roles();
		if(get_option('mo2f_admin_disabled_status') == 1 || get_option('mo2f_admin_disabled_status') == 0){
			if(get_option('mo2f_admin_disabled_status') == 1){
				add_option('mo2fa_administrator',1);
			}else{
				foreach($wp_roles->role_names as $id => $name) {
					add_option('mo2fa_'.$id, 1);
				}
			}
			delete_option('mo2f_admin_disabled_status');
		}else{
			foreach($wp_roles->role_names as $id => $name) {
				add_option('mo2fa_'.$id, 1);
			}
		}
		
		if( get_option('mo2f_activate_plugin') == 1){
			$pass2fa_login = new Miniorange_Password_2Factor_Login();
			add_action( 'init', array( $pass2fa_login, 'miniorange_pass2login_redirect'));
			
			if(get_option('mo2f_login_policy')){ //password + 2nd factor enabled
				if(get_option( 'mo_2factor_admin_registration_status') == 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' ){
				
						
						remove_filter('authenticate', 'wp_authenticate_username_password',20);
						add_filter('authenticate', array($pass2fa_login, 'mo2f_check_username_password'),99999,4);
						add_action( 'init', array( $pass2fa_login, 'miniorange_pass2login_redirect'));
						add_action( 'login_form', array( $pass2fa_login, 'mo_2_factor_pass2login_show_wp_login_form' ),10 );
						if(get_option('mo2f_deviceid_enabled')){
							add_action( 'login_footer', array( $pass2fa_login, 'miniorange_pass2login_footer_form' ));
							add_action( 'woocommerce_before_customer_login_form', array( $pass2fa_login, 'miniorange_pass2login_footer_form' ) );
						}
						add_action( 'login_enqueue_scripts', array( $pass2fa_login,'mo_2_factor_enable_jquery_default_login') );
						
						add_action( 'woocommerce_login_form_end', array( $pass2fa_login, 'mo_2_factor_pass2login_show_wp_login_form' ) );
						add_action( 'wp_enqueue_scripts', array( $pass2fa_login,'mo_2_factor_enable_jquery_default_login') );
						
						//Actions for other plugins to use miniOrange 2FA plugin
						add_action('miniorange_pre_authenticate_user_login', array($pass2fa_login, 'mo2f_check_username_password'),1,4);
						add_action('miniorange_post_authenticate_user_login', array($pass2fa_login, 'miniorange_initiate_2nd_factor'),1,3);
						add_action('miniorange_collect_attributes_for_authenticated_user', array($pass2fa_login, 'mo2f_collect_device_attributes_for_authenticated_user'),1,2);
							
				}
				
			}else{ //login with phone enabled
				if(get_option( 'mo_2factor_admin_registration_status') == 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS'){

					$mobile_login = new Miniorange_Mobile_Login();
					add_action( 'login_form', array( $mobile_login, 'miniorange_login_form_fields' ),10 );
					add_action( 'login_footer', array( $mobile_login, 'miniorange_login_footer_form' ));
					
					remove_filter('authenticate', 'wp_authenticate_username_password',20);
					add_filter('authenticate', array($mobile_login, 'mo2fa_default_login'),99999,3);
					add_action( 'login_enqueue_scripts', array( $mobile_login,'custom_login_enqueue_scripts') );
				}
				
				
			}
		}
	}
	
	function get_customer_SMS_transactions()
	{
	    
		if(get_option( 'mo_2factor_admin_registration_status') == 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' && get_option('mo2f_show_sms_transaction_message')){
			if(!get_option('mo2f_set_transactions')){
				$customer = new Customer_Setup();
				
				$content = json_decode($customer->get_customer_transactions(get_option( 'mo2f_customerKey'),get_option( 'mo2f_api_key')), true);
				
				update_option( 'mo2f_set_transactions', 1);
				if(!array_key_exists('smsRemaining', $content)){
					$smsRemaining = 0; 
				}
				else{
					$smsRemaining = $content['smsRemaining'];
				
					if ($smsRemaining == null) {
						$smsRemaining = 0;	
					}
				}
				update_option( 'mo2f_number_of_transactions', $smsRemaining);
			}
			else {
				$smsRemaining = get_option('mo2f_number_of_transactions');
			}		

			$this->display_customer_transactions($smsRemaining);		
		}
	} 
	
	function display_customer_transactions($content)
	{
	   echo '<div class="is-dismissible notice notice-warning"> <form name="f" method="post" action=""><input type="hidden" name="option" value="mo_auth_sync_sms_transactions" /><p><b>miniOrange 2-Factor Plugin:</b> You have <b style="color:red">'.$content.' SMS transactions</b> remaining. <input type="submit" name="submit" value="Check Transactions" class="button button-primary button-large" /></form><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
	}
	
	 function mo_auth_deactivate() {
		delete_option('mo2f_email');
		delete_option('mo2f_host_name');
		delete_option('mo2f_phone');
		delete_option('mo2f_modal_display');
		delete_option('mo2f_customerKey');
		delete_option('mo2f_api_key');
		delete_option('mo2f_customer_token');
		delete_option('mo_2factor_admin_registration_status');
		delete_option('mo2f_miniorange_admin');
		delete_option('mo2f_number_of_transactions');
		delete_option('mo2f_set_transactions');
		delete_option('mo2f_show_sms_transaction_message');
		/* App Specific Password
		delete_option('mo_app_password');
		*/
		global $current_user;
		
		delete_user_meta($current_user->ID,'mo_2factor_user_registration_status');
		delete_user_meta($current_user->ID,'mo_2factor_mobile_registration_status');
		delete_user_meta($current_user->ID,'mo_2factor_user_registration_with_miniorange');
		delete_user_meta($current_user->ID,'mo_2factor_map_id_with_email');
		delete_user_meta($current_user->ID,'mo2f_user_phone');
		delete_user_meta($current_user->ID,'mo2f_mobile_registration_status');
		delete_user_meta($current_user->ID,'mo2f_otp_registration_status');
		delete_user_meta($current_user->ID,'mo2f_configure_test_option');
		delete_user_meta($current_user->ID,'mo2f_selected_2factor_method');
		delete_user_meta($current_user->ID,'mo2f_google_authentication_status');
		delete_user_meta($current_user->ID,'mo2f_kba_registration_status');
		delete_user_meta($current_user->ID,'mo2f_email_verification_status');
		delete_user_meta($current_user->ID,'mo2f_authy_authentication_status');
		/* App Specific Password
		delete_user_meta($current_user->ID,'mo2f_app_password');
		*/
	}
	

	function mo_auth_success_message() {
		$message = get_option('mo2f_message'); ?>
		<script> 
		
		jQuery(document).ready(function() {	
			var message = "<?php echo $message; ?>";
			jQuery('#messages').append("<div class='error notice is-dismissible mo2f_error_container'> <p class='mo2f_msgs'>" + message + "</p></div>");
		});
		</script>
		<?php
	}

	function mo_auth_error_message() {
		$message = get_option('mo2f_message'); ?>
		<script> 
		jQuery(document).ready(function() {
			var message = "<?php echo $message; ?>";
			jQuery('#messages').append("<div class='updated notice is-dismissible mo2f_success_container'> <p class='mo2f_msgs'>" + message + "</p></div>");
		
			jQuery('a[href=\"#test\"]').click(function() {
				var currentMethod = jQuery(this).data("method");
			
				if(currentMethod == 'MOBILE AUTHENTICATION'){
					jQuery('#mo2f_2factor_test_mobile_form').submit();
				}else if(currentMethod == 'PUSH NOTIFICATIONS'){
					jQuery('#mo2f_2factor_test_push_form').submit();
				}else if(currentMethod == 'SOFT TOKEN'){
					jQuery('#mo2f_2factor_test_softtoken_form').submit();
				}else if(currentMethod == 'SMS' || currentMethod == 'PHONE VERIFICATION'){
					jQuery('#mo2f_test_2factor_method').val(currentMethod);
					jQuery('#mo2f_2factor_test_smsotp_form').submit();
				}else if(currentMethod == 'OUT OF BAND EMAIL'){
					jQuery('#mo2f_2factor_test_out_of_band_email_form').submit();
				}else if(currentMethod == 'GOOGLE AUTHENTICATOR'){
					jQuery('#mo2f_2factor_test_google_auth_form').submit();
				}else if(currentMethod == 'AUTHY 2-FACTOR AUTHENTICATION'){
					jQuery('#mo2f_2factor_test_authy_app_form').submit();
				}else if(currentMethod == 'KBA'){
					jQuery('#mo2f_2factor_test_kba_form').submit();
				}
				
				
			});
		
		});
		</script>
		<?php
	}	

	function miniorange_auth_menu() {
		global $wpdb;
		global $current_user;
		$current_user = wp_get_current_user();
		if(get_option('mo2f_enable_custom_icon')!=1)
				$iconurl = plugin_dir_url(__FILE__) . 'includes/images/miniorange_icon.png';
			else
				$iconurl = site_url(). '/wp-content/uploads/plugin_icon.png';
			
		if(get_option( 'mo_2factor_admin_registration_status') == 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' ){
			if(current_user_can( 'manage_options' ) && get_option( 'mo2f_miniorange_admin') == $current_user->ID){
				$mo2fa_hook_page = add_menu_page ('miniOrange 2 Factor Auth',  get_option('mo2f_custom_plugin_name'), 'manage_options', 'miniOrange_2_factor_settings', array( $this, 'mo_auth_login_options' ),$iconurl);
			}
		}else if(current_user_can( 'manage_options' )){
			$mo2fa_hook_page = add_menu_page ('miniOrange 2 Factor Auth',  get_option('mo2f_custom_plugin_name'), 'manage_options', 'miniOrange_2_factor_settings', array( $this, 'mo_auth_login_options' ),$iconurl);
		}
		
	}

	function  mo_auth_login_options () {
		global $wpdb;
		global $current_user;
		$current_user = wp_get_current_user();
		update_option('mo2f_host_name', 'https://auth.miniorange.com');
		mo_2_factor_register($current_user);
	}

	function mo_2_factor_enable_frontend_style() {
		wp_enqueue_style( 'mo2f_frontend_login_style', plugins_url('includes/css/front_end_login.css?version=4.5.5', __FILE__));
		wp_enqueue_style( 'bootstrap_style', plugins_url('includes/css/bootstrap.min.css?version=4.5.5', __FILE__));
		wp_enqueue_style( 'mo_2_factor_admin_settings_phone_style', plugins_url('includes/css/phone.css?version=4.5.5', __FILE__));
	}
	
	function plugin_settings_style() {
		wp_enqueue_style( 'mo_2_factor_admin_settings_style', plugins_url('includes/css/style_settings.css?version=4.5.5', __FILE__));
		wp_enqueue_style( 'mo_2_factor_admin_settings_phone_style', plugins_url('includes/css/phone.css?version=4.5.5', __FILE__));
		wp_enqueue_style( 'bootstrap_style', plugins_url('includes/css/bootstrap.min.css?version=4.5.5', __FILE__));
	}

	function plugin_settings_script($mo2fa_hook_page) {
		if ( 'toplevel_page_miniOrange_2_factor_settings' != $mo2fa_hook_page ) {
			return;
		}
		wp_enqueue_script('jquery');
		wp_enqueue_script( 'mo_2_factor_admin_settings_phone_script', plugins_url('includes/js/phone.js', __FILE__ ));
		wp_enqueue_script( 'bootstrap_script', plugins_url('includes/js/bootstrap.min.js', __FILE__ ));
	}

	function mo_auth_show_success_message() {
		remove_action( 'admin_notices', array( $this, 'mo_auth_success_message') );
		add_action( 'admin_notices', array( $this, 'mo_auth_error_message') );
	}

	function mo_auth_show_error_message() {
		remove_action( 'admin_notices', array( $this, 'mo_auth_error_message') );
		add_action( 'admin_notices', array( $this, 'mo_auth_success_message') );
	}

	/* App Specific Password
	// added for App specific password - If post request is sent for creating a new password
	function miniorange_auth_init(){
		global $current_user;
		$current_user = wp_get_current_user();
		
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX) {
			add_action( 'wp_ajax_Authenticator_action', array( $this, 'ajax_callback' ) );
		}
		
		// call to generate password
		if(isset($_GET['option']) && $_GET['option'] ="generatepassword"){
			ajax_callback();
			exit;
		}
		
	}*/
	
	function miniorange_auth_save_settings(){
		
		global $current_user;
		$current_user = wp_get_current_user();
		
		if( ! session_id() || session_id() == '' || !isset($_SESSION) ) {
			session_start();
		}
		
		
		if(current_user_can( 'manage_options' )){
		if(isset($_POST['option']) and $_POST['option'] == "mo_auth_register_customer"){	//register the admin to miniOrange
			//validate and sanitize
			$email = '';
			$phone = '';
			$password = '';
			$confirmPassword = '';
			$company = '';
			$firstName = '';
			$lastName = '';
			if( MO2f_Utility::mo2f_check_empty_or_null( $_POST['email'] ) || MO2f_Utility::mo2f_check_empty_or_null( $_POST['password'] ) || MO2f_Utility::mo2f_check_empty_or_null( $_POST['confirmPassword'] ) || MO2f_Utility::mo2f_check_empty_or_null( $_POST['company'] ) ) {
				update_option( 'mo2f_message', 'All the fields are required. Please enter valid entries.');
				$this->mo_auth_show_error_message();
				return;
			}else if( strlen( $_POST['password'] ) < 6 || strlen( $_POST['confirmPassword'] ) < 6){
				update_option( 'mo2f_message', 'Choose a password with minimum length 6.');
				$this->mo_auth_show_error_message();
				return;
			} else{
				$email = sanitize_email( $_POST['email'] );
				$phone = sanitize_text_field( $_POST['phone'] );
				$password = sanitize_text_field( $_POST['password'] );
				$confirmPassword = sanitize_text_field( $_POST['confirmPassword'] );
				$company = sanitize_text_field( $_POST['company'] );
				$firstName = sanitize_text_field( $_POST['first_name'] );
				$lastName = sanitize_text_field( $_POST['last_name'] );
			}			
			$email = strtolower($email);
			update_option( 'mo2f_email', $email );
			update_user_meta( $current_user->ID,'mo2f_user_phone', $phone );
			update_option('mo2f_admin_company', $company);
			update_option('mo2f_admin_first_name', $firstName);
			update_option('mo2_admin_last_name', $lastName);
		
			if(strcmp($password, $confirmPassword) == 0) {
				update_option( 'mo2f_password', $password );
				$customer = new Customer_Setup();
				$customerKey = json_decode($customer->check_customer(), true);
				if($customerKey['status'] == 'ERROR'){
					update_option( 'mo2f_message', $customerKey['message']);
					
					$this->mo_auth_show_error_message();
				}else{
					
					if( strcasecmp( $customerKey['status'], 'CUSTOMER_NOT_FOUND') == 0 ){ //customer not found then send OTP to verify email 
						
						$content = json_decode($customer->send_otp_token(get_option('mo2f_email'),'EMAIL',$this->defaultCustomerKey,$this->defaultApiKey), true);
						
						if(strcasecmp($content['status'], 'SUCCESS') == 0) {
							
							update_option( 'mo2f_message', 'An OTP has been sent to <b>' . ( get_option('mo2f_email') ) . '</b>. Please enter the OTP below to verify your email. ');
							update_user_meta($current_user->ID,'mo2f_email_otp_count',1);
							update_user_meta($current_user->ID,'mo_2fa_verify_otp_create_account',$content['txId']);
							update_user_meta($current_user->ID, 'mo_2factor_user_registration_status','MO_2_FACTOR_OTP_DELIVERED_SUCCESS');
							$this->mo_auth_show_success_message();
						}else{
							update_option('mo2f_message','There was an error in sending OTP over email. Please click on Resend OTP to try again.');
							update_user_meta($current_user->ID, 'mo_2factor_user_registration_status','MO_2_FACTOR_OTP_DELIVERED_FAILURE');
							$this->mo_auth_show_error_message();
						}
					}else{ //customer already exists,retrieve its keys
						
						$content = $customer->get_customer_key();
						$customerKey = json_decode($content, true);
						if(json_last_error() == JSON_ERROR_NONE) { /*Admin enter right credentials,if already exist */
						
					
							if(is_array($customerKey) && array_key_exists("status", $customerKey) && $customerKey['status'] == 'ERROR'){
								update_option('mo2f_message',$customerKey['message']);
								$this->mo_auth_show_error_message();
							}else if(is_array($customerKey)){
								
								if(isset($customerKey['id']) && !empty($customerKey['id'])){
									update_option( 'mo2f_customerKey', $customerKey['id']);
									update_option( 'mo2f_api_key', $customerKey['apiKey']);
									update_option( 'mo2f_customer_token', $customerKey['token']);
									update_option( 'mo2f_app_secret', $customerKey['appSecret'] );
									update_option( 'mo2f_miniorange_admin',$current_user->ID);
									update_option( 'mo2f_new_customer',true);
									delete_option('mo2f_password');
									update_option( 'mo_2factor_admin_registration_status','MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS');
									update_user_meta($current_user->ID,'mo_2factor_user_registration_with_miniorange','SUCCESS');
									update_user_meta($current_user->ID,'mo_2factor_map_id_with_email',get_option('mo2f_email'));
									update_user_meta($current_user->ID,'mo_2factor_user_registration_status','MO_2_FACTOR_PLUGIN_SETTINGS');
									
									$enduser = new Two_Factor_Setup();
									
									$userinfo = json_decode($enduser->mo2f_get_userinfo(get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true)),true);
									$mo2f_second_factor = 'NONE';
									if(json_last_error() == JSON_ERROR_NONE){
										if($userinfo['status'] == 'SUCCESS'){
											$mo2f_second_factor = mo2f_update_and_sync_user_two_factor($current_user->ID, $userinfo);
										}
									}
									
									update_option( 'mo2f_message', 'Your account has been retrieved successfully.<b> ' . $mo2f_second_factor . ' </b> has been set as your default 2nd factor method. <a href=\"admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mobile_configure\" >Click Here </a>to configure another 2nd factor authentication method.');
									$this->mo_auth_show_success_message();
								}else{
									delete_option( 'mo2f_email');
									delete_option( 'mo2f_customerKey');
									update_option( 'mo2f_message', 'An error occured while creating your account. Please try again or contact us by sending a query from support.');
									$this->mo_auth_show_error_message();
								}
								
							}
						} else { /*Admin account exist but enter wrong credentials*/
							update_option( 'mo2f_message', 'You already have an account with miniOrange. Please enter a valid password.');
							update_user_meta( $current_user->ID,'mo_2factor_user_registration_status','MO_2_FACTOR_VERIFY_CUSTOMER');
							$this->mo_auth_show_success_message();
						}
					} 
				}
			} else {
				update_option( 'mo2f_message', 'Password and Confirm password do not match.');
				$this->mo_auth_show_error_message();
			}
		}
		
		if(isset($_POST['option']) and $_POST['option'] == "mo2f_goto_verifycustomer"){
			update_option( 'mo2f_message', 'Please enter your registered email and password.');
			update_user_meta( $current_user->ID,'mo_2factor_user_registration_status','MO_2_FACTOR_VERIFY_CUSTOMER');
			$this->mo_auth_show_success_message();
		}
		
		if(isset($_POST['option']) and $_POST['option'] == "mo_auth_verify_customer"){	//register the admin to miniOrange if already exist
		
			//validation and sanitization
			$email = '';
			$password = '';
			if( MO2f_Utility::mo2f_check_empty_or_null( $_POST['email'] ) || MO2f_Utility::mo2f_check_empty_or_null( $_POST['password'] ) ) {
				update_option( 'mo2f_message', 'All the fields are required. Please enter valid entries.');
				$this->mo_auth_show_error_message();
				return;
			}else{
				$email = sanitize_email( $_POST['email'] );
				$password = sanitize_text_field( $_POST['password'] );
			}
		
			update_option( 'mo2f_email', $email );
			update_option( 'mo2f_password', $password );
			$customer = new Customer_Setup();
			$content = $customer->get_customer_key();
			$customerKey = json_decode($content, true);
			if(json_last_error() == JSON_ERROR_NONE) {
				if(is_array($customerKey) && array_key_exists("status", $customerKey) && $customerKey['status'] == 'ERROR'){
					update_option('mo2f_message',$customerKey['message']);
					$this->mo_auth_show_error_message();
				}else if(is_array($customerKey)){
					if(isset($customerKey['id']) && !empty($customerKey['id'])){
						update_option( 'mo2f_customerKey', $customerKey['id']);
						update_option( 'mo2f_api_key', $customerKey['apiKey']);
						update_option( 'mo2f_customer_token', $customerKey['token']);
						update_option( 'mo2f_app_secret', $customerKey['appSecret'] );
						update_user_meta($current_user->ID,'mo2f_phone', $customerKey['phone']);
						update_option( 'mo2f_miniorange_admin',$current_user->ID);
						update_option( 'mo2f_new_customer',true);
						delete_option('mo2f_password');
						update_option( 'mo_2factor_admin_registration_status','MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS');
						update_user_meta($current_user->ID,'mo_2factor_user_registration_with_miniorange','SUCCESS');
						update_user_meta($current_user->ID,'mo_2factor_map_id_with_email',get_option('mo2f_email'));
						update_user_meta($current_user->ID,'mo_2factor_user_registration_status','MO_2_FACTOR_PLUGIN_SETTINGS');
						
						$enduser = new Two_Factor_Setup();
						$userinfo = json_decode($enduser->mo2f_get_userinfo(get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true)),true);
						$mo2f_second_factor = 'NONE';
						if(json_last_error() == JSON_ERROR_NONE){
							if($userinfo['status'] == 'SUCCESS'){
								$mo2f_second_factor = mo2f_update_and_sync_user_two_factor($current_user->ID, $userinfo);
							}
						}
						
						update_option( 'mo2f_message', 'Your account has been retrieved successfully.<b> ' . $mo2f_second_factor . ' </b> has been set as your default 2nd factor method. <a href=\"admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mobile_configure\" >Click Here </a>to configure another 2nd factor authentication method.');
						$this->mo_auth_show_success_message();
					}else{
						update_option( 'mo2f_message', 'Invalid email or password. Please try again.');
						update_user_meta($current_user->ID, 'mo_2factor_user_registration_status','MO_2_FACTOR_VERIFY_CUSTOMER');
						$this->mo_auth_show_error_message();
					}
					    
				}
			} else {
				update_option( 'mo2f_message', 'Invalid email or password. Please try again.');
				update_user_meta($current_user->ID, 'mo_2factor_user_registration_status','MO_2_FACTOR_VERIFY_CUSTOMER');
				$this->mo_auth_show_error_message();
			}
			delete_option('mo2f_password');
		}
		if(isset($_POST['option']) and $_POST['option'] == 'mo_2factor_phone_verification'){ //at registration time
					$phone = sanitize_text_field($_POST['phone_number']);
				
					$phone = str_replace(' ', '', $phone);
					$auth_type = 'OTP_OVER_SMS';
					$customer = new Customer_Setup();
					$send_otp_response = json_decode($customer->send_otp_token($phone,$auth_type, $this->defaultCustomerKey,$this->defaultApiKey),true);
					if(strcasecmp($send_otp_response['status'], 'SUCCESS') == 0){
						//Save txId
					
						update_user_meta($current_user->ID,'mo_2fa_verify_otp_create_account',$send_otp_response['txId']);
						update_user_meta($current_user->ID,'mo_2factor_user_registration_status','MO_2_FACTOR_OTP_DELIVERED_SUCCESS');
						if(get_user_meta($current_user->ID,'mo2f_sms_otp_count',true)){
							update_user_meta($current_user->ID,'mo2f_sms_otp_count',get_user_meta($current_user->ID,'mo2f_sms_otp_count',true) + 1);
							update_option('mo2f_message', 'Another One Time Passcode has been sent <b>( ' . get_user_meta($current_user->ID,'mo2f_sms_otp_count',true) . ' )</b> for verification to ' . $phone);
						}else{
								update_option('mo2f_message', 'One Time Passcode has been sent for verification to ' . $phone);
								update_user_meta($current_user->ID,'mo2f_sms_otp_count',1);
						}
					
						$this->mo_auth_show_success_message();
					}else{
						update_option('mo2f_message','There was an error in sending sms. Please click on Resend OTP to try again.');
						update_user_meta($current_user->ID,'mo_2factor_user_registration_status','MO_2_FACTOR_OTP_DELIVERED_FAILURE');
						$this->mo_auth_show_error_message();
					}
		}
		
		if(isset($_POST['option']) and trim($_POST['option']) == "mo_2factor_resend_otp"){ //resend OTP over email for admin
			$customer = new Customer_Setup();
			$content = json_decode($customer->send_otp_token(get_option('mo2f_email'),'EMAIL',$this->defaultCustomerKey,$this->defaultApiKey), true);
			if(strcasecmp($content['status'], 'SUCCESS') == 0) {
				if(get_user_meta($current_user->ID,'mo2f_email_otp_count',true)){
					update_user_meta($current_user->ID,'mo2f_email_otp_count',get_user_meta($current_user->ID,'mo2f_email_otp_count',true) + 1);
					update_option( 'mo2f_message', 'Another OTP has been sent <b>( ' . get_user_meta($current_user->ID,'mo2f_email_otp_count',true) .' )</b> to <b>' . ( get_option('mo2f_email') ) . '</b>. Please enter the OTP below to verify your email. ');
				}else{
					update_option( 'mo2f_message', 'An OTP has been sent to <b>' . ( get_option('mo2f_email') ) . '</b>. Please enter the OTP below to verify your email. ');
					update_user_meta($current_user->ID,'mo2f_email_otp_count',1);
				}
				update_user_meta($current_user->ID,'mo_2fa_verify_otp_create_account',$content['txId']);
				update_user_meta($current_user->ID, 'mo_2factor_user_registration_status','MO_2_FACTOR_OTP_DELIVERED_SUCCESS');
				$this->mo_auth_show_success_message();
			}else{
				update_option('mo2f_message','There was an error in sending email. Please click on Resend OTP to try again.');
				update_user_meta($current_user->ID,'mo_2factor_user_registration_status','MO_2_FACTOR_OTP_DELIVERED_FAILURE');
				$this->mo_auth_show_error_message();
			}
		}
		
		if(isset($_POST['option']) and $_POST['option'] == "mo_2factor_validate_otp"){ //validate OTP over email for admin
			
			//validation and sanitization
			$otp_token = '';
			if( MO2f_Utility::mo2f_check_empty_or_null( $_POST['otp_token'] ) ) {
				update_option( 'mo2f_message', 'All the fields are required. Please enter valid entries.');
				$this->mo_auth_show_error_message();
				return;
			} else{
				$otp_token = sanitize_text_field( $_POST['otp_token'] );
			}
			
			$customer = new Customer_Setup();
			$transactionId = get_user_meta($current_user->ID,'mo_2fa_verify_otp_create_account',true);
			
			$content = json_decode($customer->validate_otp_token( 'EMAIL', null,$transactionId, $otp_token, $this->defaultCustomerKey, $this->defaultApiKey ),true);
			if($content['status'] == 'ERROR'){
				update_option( 'mo2f_message', $content['message']);
				$this->mo_auth_show_error_message();
			}else{
				if(strcasecmp($content['status'], 'SUCCESS') == 0) { //OTP validated and generate QRCode
					$this->mo2f_create_customer($current_user);
					delete_user_meta($current_user->ID,'mo_2fa_verify_otp_create_account');
				}else{  // OTP Validation failed.
					update_option( 'mo2f_message','Invalid OTP. Please try again.');
					update_user_meta($current_user->ID,'mo_2factor_user_registration_status','MO_2_FACTOR_OTP_DELIVERED_FAILURE');
					$this->mo_auth_show_error_message();
				}
			}
		}
		
		if(isset($_POST['option']) and $_POST['option'] == "mo_2factor_validate_user_otp"){ //validate OTP over email for additional admin
			
			//validation and sanitization
			$otp_token = '';
			if( MO2f_Utility::mo2f_check_empty_or_null( $_POST['otp_token'] ) ) {
				update_option( 'mo2f_message', 'All the fields are required. Please enter valid entries.');
				$this->mo_auth_show_error_message();
				return;
			} else{
				$otp_token = sanitize_text_field( $_POST['otp_token'] );
			}
			
			if(!MO2f_Utility::check_if_email_is_already_registered(get_user_meta($current_user->ID,'mo_2factor_user_email',true))){
				$customer = new Customer_Setup();
				$content = json_decode($customer->validate_otp_token( 'EMAIL', null, $_SESSION[ 'mo2f_transactionId' ], $otp_token, get_option('mo2f_customerKey'), get_option('mo2f_api_key') ),true);
				if($content['status'] == 'ERROR'){
					update_option( 'mo2f_message', $content['message']);
					$this->mo_auth_show_error_message();
				}else{
					if(strcasecmp($content['status'], 'SUCCESS') == 0) { //OTP validated and generate QRCode
						$this->mo2f_create_user($current_user,get_user_meta($current_user->ID,'mo_2factor_user_email',true));
						delete_user_meta($current_user->ID,'mo_2fa_verify_otp_create_account');
					}else{  // OTP Validation failed.
						update_option( 'mo2f_message','Invalid OTP. Please try again.');
						update_user_meta($current_user->ID,'mo_2factor_user_registration_status','MO_2_FACTOR_OTP_DELIVERED_FAILURE');
						$this->mo_auth_show_error_message();
					}
				}
			}else{
				update_option('mo2f_message','The email is already used by other user. Please register with other email by clicking on Back button.');	
				$this->mo_auth_show_error_message();
			}
		}
		
		if(isset($_POST['option']) and $_POST['option'] == "mo_2factor_send_query"){ //Help me or support
			$query = '';
			if( MO2f_Utility::mo2f_check_empty_or_null( $_POST['query_email'] ) || MO2f_Utility::mo2f_check_empty_or_null( $_POST['query'] ) ) {
				update_option( 'mo2f_message', 'Please submit your query with email.');
				$this->mo_auth_show_error_message();
				return;
			} else{
				$query = sanitize_text_field( $_POST['query'] );
				$email = sanitize_text_field( $_POST['query_email'] );
				$phone = sanitize_text_field( $_POST['query_phone'] );
				$contact_us = new Customer_Setup();
				$submited = json_decode($contact_us->submit_contact_us($email, $phone, $query),true);
				if(json_last_error() == JSON_ERROR_NONE) {
					if(is_array($submited) && array_key_exists('status', $submited) && $submited['status'] == 'ERROR'){
						update_option( 'mo2f_message', $submited['message']);
						$this->mo_auth_show_error_message();
					}else{
						if ( $submited == false ) {
							update_option('mo2f_message', 'Your query could not be submitted. Please try again.');
							$this->mo_auth_show_error_message();
						} else {
							update_option('mo2f_message', 'Thanks for getting in touch! We shall get back to you shortly.');
							$this->mo_auth_show_success_message();
						}
					}
				}

			}
		}
		
		if(isset($_POST['option']) and $_POST['option'] == 'mo_auth_advanced_options_save'){
			update_option( 'mo2f_enable_2fa_for_woocommerce', isset( $_POST['mo2f_enable_2fa_for_woocommerce']) ? $_POST['mo2f_enable_2fa_for_woocommerce'] : 0);
			if(!get_option('mo2f_new_customer')){
				//plugin customization
				update_option( 'mo2f_disable_poweredby', isset( $_POST['mo2f_disable_poweredby']) ? $_POST['mo2f_disable_poweredby'] : 0);
				update_option( 'mo2f_enable_custom_poweredby', isset( $_POST['mo2f_enable_custom_poweredby']) ? $_POST['mo2f_enable_custom_poweredby'] : 0);
				if (get_option('mo2f_disable_poweredby') == 1){
					update_option( 'mo2f_enable_custom_poweredby',0);
				}
				update_option( 'mo2f_enable_custom_icon', isset( $_POST['mo2f_enable_custom_icon']) ? $_POST['mo2f_enable_custom_icon'] : 0);
				update_option( 'mo2f_custom_plugin_name',  isset($_POST['mo2f_custom_plugin_name']) ? $_POST['mo2f_custom_plugin_name'] : 'miniOrange 2-Factor');
			}
			update_option( 'mo2f_message', 'Your settings are saved successfully.');
			$this->mo_auth_show_success_message();
		}
		
		if(isset($_POST['option']) and $_POST['option'] == 'mo_auth_login_settings_save'){
			$random_mo_key = get_option('mo2f_new_customer');
			if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS' ) {
				
				if(!$random_mo_key){
					update_option( 'mo2f_inline_registration', isset( $_POST['mo2f_inline_registration']) ? $_POST['mo2f_inline_registration'] : 0);
					$authMethods = array();
					$authMethod = isset($_POST['mo2f_authmethods']) ? $_POST['mo2f_authmethods'] : array();
					foreach ($authMethod as $arrayvalue){
						$authMethods[$arrayvalue] = $arrayvalue;
					}
					update_option( 'mo2f_auth_methods_for_users', $authMethods);
				
				}
				
				update_option( 'mo2f_login_policy', isset( $_POST['mo2f_login_policy']) ? $_POST['mo2f_login_policy'] : 0);
				update_option( 'mo2f_deviceid_enabled', isset( $_POST['mo2f_deviceid_enabled'] ) ? $_POST['mo2f_deviceid_enabled'] : 0);
				if(get_site_option('mo2f_login_policy')==0)
				{
					
					update_option('mo2f_deviceid_enabled',0);
				}
				update_option( 'mo2f_enable_forgotphone', isset( $_POST['mo2f_forgotphone']) ? $_POST['mo2f_forgotphone'] : 0);
				update_option( 'mo2f_show_loginwith_phone', isset( $_POST['mo2f_loginwith_phone']) ? $_POST['mo2f_loginwith_phone'] : 0);
				update_option( 'mo2f_activate_plugin', isset( $_POST['mo2f_activate_plugin']) ? $_POST['mo2f_activate_plugin'] : 0);
				update_option( 'mo2f_enable_mobile_support', isset( $_POST['mo2f_enable_mobile_support']) ? $_POST['mo2f_enable_mobile_support'] : 0);
				update_option( 'mo2f_enable_xmlrpc', isset( $_POST['mo2f_enable_xmlrpc']) ? $_POST['mo2f_enable_xmlrpc'] : 0);
				
				/* App Specific Password
				// saving the generated App specific password 
				$app_password = $_POST['app_password'];
		        
				if (strtoupper($app_password) != '**** **** **** ****' ) {
					// Store the password in hashed format
					$app_password = sha1(strtoupper(str_replace(' ', '', $app_password )));
					update_user_option( $current_user->ID, 'mo2f_app_password', $app_password, true );
					update_option('mo_app_password', $app_password);
				}*/
				
				global $wp_roles;
				if (!isset($wp_roles))
					$wp_roles = new WP_Roles();
				foreach($wp_roles->role_names as $id => $name) {
					update_option('mo2fa_'.$id, isset( $_POST['mo2fa_'.$id] ) ? $_POST['mo2fa_'.$id] : 0);
				}
				
				
			
				
				if(get_option('mo2f_activate_plugin')){
					$logouturl = wp_login_url() . '?action=logout';
					update_option( 'mo2f_message', 'Your login settings are saved successfully. Now <a href=\"'.$logouturl.'\"><b>Click Here</b></a> to logout and try login with 2-Factor.');
					update_option( 'mo2f_msg_counter',2);
					$this->mo_auth_show_success_message();
				}else{
					update_option( 'mo2f_message', 'Two-Factor plugin has been disabled.');
					update_option( 'mo2f_msg_counter',2);
					$this->mo_auth_show_error_message();
				}
				
				if(get_option( 'mo2f_deviceid_enabled' ) && !get_option( 'mo2f_app_secret' )){
					$get_app_secret = new Miniorange_Rba_Attributes();
					$rba_response = json_decode($get_app_secret->mo2f_get_app_secret(),true); //fetch app secret
					if(json_last_error() == JSON_ERROR_NONE){
						if($rba_response['status'] == 'SUCCESS'){ 
							update_option( 'mo2f_app_secret',$rba_response['appSecret'] );
						}else{
							update_option( 'mo2f_deviceid_enabled',0 );
							update_option( 'mo2f_message', 'Error occurred while saving the settings.Please try again.');
							$this->mo_auth_show_error_message();
						}
					}else{
						update_option( 'mo2f_deviceid_enabled',0 );
						update_option( 'mo2f_message', 'Error occurred while saving the settings.Please try again.');
						$this->mo_auth_show_error_message();
					}
				}
			}else{
				update_option( 'mo2f_message', 'Invalid request. Please register with miniOrange and configure 2-Factor to save your login settings.');
				$this->mo_auth_show_error_message();
			}
		}
		
		if(isset($_POST['option']) and $_POST['option'] == 'mo_2factor_gobackto_registration_page'){ //back to registration page for admin
			delete_option('mo2f_email');
			delete_option('mo2f_password');
			delete_option('mo2f_customerKey');
			delete_option('mo2f_app_secret');
			delete_option('mo2f_admin_company');
			unset($_SESSION[ 'mo2f_transactionId' ]);
			delete_user_meta($current_user->ID,'mo_2factor_map_id_with_email');
			delete_user_meta($current_user->ID,'mo_2factor_user_registration_status');
			delete_user_meta($current_user->ID,'mo2f_sms_otp_count');
			delete_user_meta($current_user->ID,'mo2f_email_otp_count');
		}
		
		if(isset($_POST['option']) && $_POST['option'] == 'mo_2factor_forgot_password'){ // if admin forgot password
			if(isset( $_POST['email']) ){
				if( MO2f_Utility::mo2f_check_empty_or_null( $_POST['email'] ) ) {
					update_option( 'mo2f_message', 'Please enter your registered email below to reset your password.');
					
					
					$this->mo_auth_show_error_message();
					return;
				}else
					$email = sanitize_email($_POST['email']);
				
			}
			
			$customer = new Customer_Setup();
				$content = json_decode($customer->forgot_password($email),true);
					if(strcasecmp($content['status'], 'SUCCESS') == 0){
						update_option( 'mo2f_message','You password has been reset successfully. A new password has been sent to your registered mail.');
						$this->mo_auth_show_success_message();
					}else{
						update_option( 'mo2f_message','Your password could not be reset. Please enter your correct email in the textbox below and then click on the link.');
						$this->mo_auth_show_error_message();
					}
					
			
		}
		
		
		if(isset($_POST['option']) and trim($_POST['option']) == "mo_auth_sync_sms_transactions") {
			$customer = new Customer_Setup();
			$content = json_decode($customer->get_customer_transactions(get_option( 'mo2f_customerKey'),get_option( 'mo2f_api_key')), true);
			if(!array_key_exists('smsRemaining', $content)){
				$smsRemaining = 0; 
			}
			else{
				$smsRemaining = $content['smsRemaining'];
			
				if ($smsRemaining == null) {
					$smsRemaining = 0;
				}
			}
		
			update_option( 'mo2f_number_of_transactions', $smsRemaining);
		}
		
		
		}
		
		
		if(isset($_POST['option']) and trim($_POST['option']) == "mo_2factor_resend_user_otp"){ //resend OTP over email for additional admin and non-admin user
			$customer = new Customer_Setup();
			$content = json_decode($customer->send_otp_token(get_user_meta($current_user->ID,'mo_2factor_user_email',true),'EMAIL',get_option('mo2f_customerKey'),get_option('mo2f_api_key')), true);
			if(strcasecmp($content['status'], 'SUCCESS') == 0) {
				update_option( 'mo2f_message', 'An OTP has been sent to <b>' . ( get_user_meta($current_user->ID,'mo_2factor_user_email',true) ) . '</b>. Please enter the OTP below to verify your email. ');
				update_user_meta($current_user->ID,'mo_2fa_verify_otp_create_account',$content['txId']);
				update_user_meta($current_user->ID, 'mo_2factor_user_registration_status','MO_2_FACTOR_OTP_DELIVERED_SUCCESS');
				$this->mo_auth_show_success_message();
			}else{
				update_option('mo2f_message','There was an error in sending email. Please click on Resend OTP to try again.');
				update_user_meta($current_user->ID,'mo_2factor_user_registration_status','MO_2_FACTOR_OTP_DELIVERED_FAILURE');
				$this->mo_auth_show_error_message();
			}
		}
		
		if(isset($_POST['option']) and ($_POST['option'] == "mo_auth_mobile_registration_complete" || $_POST['option'] == 'mo_auth_mobile_reconfiguration_complete')){ //mobile registration successfully complete for all users
			unset($_SESSION[ 'mo2f_qrCode' ]);
			unset($_SESSION[ 'mo2f_transactionId' ]);
			unset($_SESSION[ 'mo2f_show_qr_code'] );
			$email = get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true);
			$enduser = new Two_Factor_Setup();
			$response = json_decode($enduser->mo2f_update_userinfo($email,get_user_meta( $current_user->ID,'mo2f_selected_2factor_method',true),null,null,null),true);
			if(json_last_error() == JSON_ERROR_NONE) { /* Generate Qr code */
					if($response['status'] == 'ERROR'){
						update_option( 'mo2f_message', $response['message']);
						$this->mo_auth_show_error_message();
					}else if($response['status'] == 'SUCCESS'){
							$selectedMethod = get_user_meta( $current_user->ID,'mo2f_selected_2factor_method',true);
							$testmethod = $selectedMethod;
							if( $selectedMethod == 'MOBILE AUTHENTICATION'){
									$selectedMethod = "QR Code Authentication";
							}
							$message = '<b>' . $selectedMethod.'</b> is set as your 2nd factor method. <a href=\"#test\" data-method=\"' . $testmethod . '\">Click Here</a> to test ' . $selectedMethod . ' method.';
							update_option( 'mo2f_message', $message);
							update_user_meta($current_user->ID,'mo2f_mobile_registration_status',true);
							delete_user_meta($current_user->ID,'mo2f_configure_test_option');
							update_user_meta($current_user->ID,'mo_2factor_user_registration_with_miniorange','SUCCESS');
							update_user_meta($current_user->ID,'mo_2factor_user_registration_status','MO_2_FACTOR_PLUGIN_SETTINGS');
							delete_user_meta($current_user->ID,'mo_2factor_mobile_registration_status');
							$this->mo_auth_show_success_message();
					}else{
							update_option( 'mo2f_message','An error occured while processing your request. Please Try again.');
							$this->mo_auth_show_error_message();
					}
					
			}else{
					update_option( 'mo2f_message','Invalid request. Please try again');
					$this->mo_auth_show_error_message();
			}
		
		}
		
		if(isset($_POST['option']) and $_POST['option'] == 'mo2f_mobile_authenticate_success'){ // mobile registration for all users(common)
			if(current_user_can('manage_options')){
				update_option( 'mo2f_message','You have successfully completed the test. Now <a href=\"admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_login&true\"><b>Click Here</b></a> to go to Login Settings. ');
			}else{
				update_option( 'mo2f_message','You have successfully completed the test. <a href='.wp_login_url() . '?action=logout><b>Click Here</b></a> to logout and try login with 2-Factor.');
			}
			delete_user_meta($current_user->ID,'mo2f_configure_test_option');
			unset($_SESSION['mo2f_qrCode']);
			unset($_SESSION['mo2f_transactionId']);
			unset($_SESSION['mo2f_show_qr_code']);
			$this->mo_auth_show_success_message();
		}
		
		if(isset($_POST['option']) and $_POST['option'] == 'mo2f_mobile_authenticate_error'){ //mobile registration failed for all users(common)
			update_option( 'mo2f_message','Authentication failed. Please try again to test the configuration.');
			unset($_SESSION['mo2f_show_qr_code']);
			$this->mo_auth_show_error_message();
		}
	
		if(isset($_POST['option']) and $_POST['option'] == "mo_auth_setting_configuration"){ // redirect to setings page
			update_user_meta($current_user->ID,'mo_2factor_user_registration_status','MO_2_FACTOR_PLUGIN_SETTINGS');
		}
		
		if(isset($_POST['option']) and $_POST['option'] == "mo_auth_refresh_mobile_qrcode"){ // refrsh Qrcode for all users
			if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR'
			||get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_INITIALIZE_MOBILE_REGISTRATION' 
			|| get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS') {
				$email = get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true);
				$this->mo2f_get_qr_code_for_mobile($email,$current_user->ID);
			}else{
				update_option( 'mo2f_message','Invalid request. Please register with miniOrange before configuring your mobile.');
				$this->mo_auth_show_error_message();
			}
		}
		
		if (isset($_POST['miniorange_get_started']) && isset($_POST['miniorange_user_reg_nonce'])){ //registration with miniOrange for additional admin and non-admin			
			$nonce = $_POST['miniorange_user_reg_nonce'];
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-user-reg-nonce' ) ) {
				update_option('mo2f_message','Invalid request');
			} else {
				$email = '';
				if( MO2f_Utility::mo2f_check_empty_or_null( $_POST['mo_useremail'] )){
					update_option( 'mo2f_message', 'Please enter email-id to register.');
					return;
				}else{
					$email = sanitize_email( $_POST['mo_useremail'] );
				}
				
				if(!MO2f_Utility::check_if_email_is_already_registered($email)){
					update_user_meta($current_user->ID,'mo_2factor_user_email',$email);
					
					$enduser = new Two_Factor_Setup();
					$check_user = json_decode($enduser->mo_check_user_already_exist($email),true);
					if(json_last_error() == JSON_ERROR_NONE){
						if($check_user['status'] == 'ERROR'){
							update_option( 'mo2f_message', $check_user['message']);
							$this->mo_auth_show_error_message();
							return;
						}else if(strcasecmp($check_user['status'], 'USER_FOUND_UNDER_DIFFERENT_CUSTOMER') == 0){
							update_option( 'mo2f_message', 'The email you entered is already registered. Please register with another email to set up Two-Factor.');
							$this->mo_auth_show_error_message();
							return;
						}
						else if(strcasecmp($check_user['status'], 'USER_FOUND') == 0 || strcasecmp($check_user['status'], 'USER_NOT_FOUND') == 0){
					

					
							$enduser = new Customer_Setup();
							$content = json_decode($enduser->send_otp_token($email,'EMAIL',get_option('mo2f_customerKey'),get_option('mo2f_api_key')), true);
							if(strcasecmp($content['status'], 'SUCCESS') == 0) {
								update_option( 'mo2f_message', 'An OTP has been sent to <b>' . ( $email ) . '</b>. Please enter the OTP below to verify your email. ');
								$_SESSION[ 'mo2f_transactionId' ] = $content['txId'];
								update_user_meta($current_user->ID, 'mo_2factor_user_registration_status','MO_2_FACTOR_OTP_DELIVERED_SUCCESS');
								$this->mo_auth_show_success_message();
							}else{
								update_option('mo2f_message','There was an error in sending OTP over email. Please click on Resend OTP to try again.');
								update_user_meta($current_user->ID, 'mo_2factor_user_registration_status','MO_2_FACTOR_OTP_DELIVERED_FAILURE');
								$this->mo_auth_show_error_message();
							}
						}
					}
				}else{
					update_option('mo2f_message','The email is already used by other user. Please register with other email.');	
					$this->mo_auth_show_error_message();
				}
			}
		}
		
		if(isset($_POST['option']) and $_POST['option'] == 'mo_2factor_backto_user_registration'){ //back to registration page for additional admin and non-admin
			delete_user_meta($current_user->ID,'mo_2factor_user_email');
			unset($_SESSION[ 'mo2f_transactionId' ]);
			delete_user_meta($current_user->ID,'mo_2factor_map_id_with_email');
			delete_user_meta($current_user->ID,'mo_2factor_user_registration_status');
		}
		
		if(isset($_POST['option']) && $_POST['option'] == 'mo_2factor_test_mobile_authentication'){ //test QR-Code authentication for all users
			
				$challengeMobile = new Customer_Setup();
				$content = $challengeMobile->send_otp_token(get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true), 'MOBILE AUTHENTICATION',get_option('mo2f_customerKey'),get_option('mo2f_api_key'));
				$response = json_decode($content, true);
				if(json_last_error() == JSON_ERROR_NONE) { /* Generate Qr code */
					if($response['status'] == 'ERROR'){
						update_option( 'mo2f_message', $response['message']);
						$this->mo_auth_show_error_message();
					}else{
						if($response['status'] == 'SUCCESS'){
						$_SESSION[ 'mo2f_qrCode' ] = $response['qrCode'];
						$_SESSION[ 'mo2f_transactionId' ] = $response['txId'];
						$_SESSION[ 'mo2f_show_qr_code'] = 'MO_2_FACTOR_SHOW_QR_CODE';
						update_option( 'mo2f_message','Please scan the QR Code now.');
						update_user_meta($current_user->ID,'mo2f_configure_test_option','MO2F_TEST');
						update_user_meta( $current_user->ID,'mo2f_selected_2factor_method', 'MOBILE AUTHENTICATION');
						$this->mo_auth_show_success_message();
						}else{
							unset($_SESSION[ 'mo2f_qrCode' ]);
							unset($_SESSION[ 'mo2f_transactionId' ]);
							unset($_SESSION[ 'mo2f_show_qr_code'] );
							update_option( 'mo2f_message','An error occured while processing your request. Please Try again.');
							$this->mo_auth_show_error_message();
						}
					}
				}else{
					update_option( 'mo2f_message','Invalid request. Please try again');
					$this->mo_auth_show_error_message();
				}
			
		}
		
		if(isset($_POST['option']) && $_POST['option'] == 'mo_2factor_test_soft_token'){  // Click on Test Soft Toekn link for all users
			update_user_meta($current_user->ID,'mo2f_configure_test_option','MO2F_TEST');
			update_user_meta($current_user->ID, 'mo2f_selected_2factor_method', 'SOFT TOKEN');
		}
		
		if(isset($_POST['option']) && $_POST['option'] == 'mo2f_validate_soft_token'){  // validate Soft Token during test for all users
				$otp_token = '';
				if( MO2f_Utility::mo2f_check_empty_or_null( $_POST['otp_token'] ) ) {
				update_option( 'mo2f_message', 'Please enter a value to test your authentication.');
				$this->mo_auth_show_error_message();
				return;
			} else{
				$otp_token = sanitize_text_field( $_POST['otp_token'] );
			}
			$email = get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true);
			$customer = new Customer_Setup();
			$content = json_decode($customer->validate_otp_token( 'SOFT TOKEN', $email, null, $otp_token, get_option('mo2f_customerKey'), get_option('mo2f_api_key') ),true);
			if($content['status'] == 'ERROR'){
				update_option( 'mo2f_message', $content['message']);
				$this->mo_auth_show_error_message();
			}else{
				if(strcasecmp($content['status'], 'SUCCESS') == 0) { //OTP validated and generate QRCode
					if(current_user_can('manage_options')){
						update_option( 'mo2f_message','You have successfully completed the test. Now <a href=\"admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_login&true\"><b>Click Here</b></a> to go to Login Settings. ');
					}else{
						update_option( 'mo2f_message','You have successfully completed the test. <a href='.wp_login_url() . '?action=logout><b>Click Here</b></a> to logout and try login with 2-Factor.');
					}
					delete_user_meta($current_user->ID,'mo2f_configure_test_option');
					$this->mo_auth_show_success_message();
					
				}else{  // OTP Validation failed.
					update_option( 'mo2f_message','Invalid OTP. Please try again.');
					$this->mo_auth_show_error_message();
				}
			}
		}
		
		if(isset($_POST['option']) && $_POST['option'] == 'mo_2factor_test_otp_over_sms'){ //sending otp for sms and phone call during test for all users
			update_user_meta($current_user->ID,'mo2f_configure_test_option','MO2F_TEST');
			update_user_meta($current_user->ID, 'mo2f_selected_2factor_method', $_POST['mo2f_selected_2factor_method']);
			
			$email = get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true);
			$phone = get_user_meta($current_user->ID,'mo2f_user_phone',true);
				$enduser = new Customer_Setup();
				$content = json_decode($enduser->send_otp_token($email,$_POST['mo2f_selected_2factor_method'],get_option('mo2f_customerKey'),get_option('mo2f_api_key')), true);
				if(strcasecmp($content['status'], 'SUCCESS') == 0) {
					if(get_user_meta( $current_user->ID,'mo2f_selected_2factor_method',true) == 'SMS'){
							update_option( 'mo2f_message', 'An OTP has been sent to <b>' . ( $phone ) . '</b>. Please enter the one time passcode below. ');
							update_option( 'mo2f_number_of_transactions', get_option('mo2f_number_of_transactions')-1);
					}else if(get_user_meta( $current_user->ID,'mo2f_selected_2factor_method',true) == 'PHONE VERIFICATION'){
						update_option( 'mo2f_message','You will receive a phone call on this number ' . $phone . '. Please enter the one time passcode below.');
					}
					$_SESSION[ 'mo2f_transactionId' ] = $content['txId'];
					$this->mo_auth_show_success_message();
				}else{
					update_option('mo2f_message','There was an error in sending one time passcode. Please click on Resend OTP to try again.');
					$this->mo_auth_show_error_message();
				}		
		}
		
		if(isset($_POST['option']) && $_POST['option'] == 'mo2f_validate_otp_over_sms'){ //validate otp over sms and phone call during test for all users
				$otp_token = '';
				if( MO2f_Utility::mo2f_check_empty_or_null( $_POST['otp_token'] ) ) {
				update_option( 'mo2f_message', 'Please enter a value to test your authentication.');
				$this->mo_auth_show_error_message();
				return;
			} else{
				$otp_token = sanitize_text_field( $_POST['otp_token'] );
			}
			$email = get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true);
			$customer = new Customer_Setup();
			$content = json_decode($customer->validate_otp_token( get_user_meta($current_user->ID, 'mo2f_selected_2factor_method',true), $email,$_SESSION[ 'mo2f_transactionId' ], $otp_token, get_option('mo2f_customerKey'), get_option('mo2f_api_key') ),true);
			if($content['status'] == 'ERROR'){
				update_option( 'mo2f_message', $content['message']);
				$this->mo_auth_show_error_message();
			}else{
				if(strcasecmp($content['status'], 'SUCCESS') == 0) { //OTP validated
					if(current_user_can('manage_options')){
						update_option( 'mo2f_message','You have successfully completed the test. Now <a href=\"admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_login&true\"><b>Click Here</b></a> to go to Login Settings. ');
					}else{
						update_option( 'mo2f_message','You have successfully completed the test. <a href='.wp_login_url() . '?action=logout><b>Click Here</b></a> to logout and try login with 2-Factor.');
					}
					delete_user_meta($current_user->ID,'mo2f_configure_test_option');
					$this->mo_auth_show_success_message();
					
				}else{  // OTP Validation failed.
					update_option( 'mo2f_message','Invalid OTP. Please try again.');
					$this->mo_auth_show_error_message();
				}
			}
		}
		
		if(isset($_POST['option']) && $_POST['option'] == 'mo_2factor_test_push_notification'){
			
				$challengeMobile = new Customer_Setup();
				$content = $challengeMobile->send_otp_token(get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true), 'PUSH NOTIFICATIONS',get_option('mo2f_customerKey'),get_option('mo2f_api_key'));
				$response = json_decode($content, true);
				if(json_last_error() == JSON_ERROR_NONE) { /* Generate Qr code */
					if($response['status'] == 'ERROR'){
						update_option( 'mo2f_message', $response['message']);
						$this->mo_auth_show_error_message();
					}else{
						if($response['status'] == 'SUCCESS'){
						$_SESSION[ 'mo2f_transactionId' ] = $response['txId'];
						$_SESSION[ 'mo2f_show_qr_code'] = 'MO_2_FACTOR_SHOW_QR_CODE';
						update_option( 'mo2f_message','A Push notification has been sent to your miniOrange Authenticator App.');
						update_user_meta($current_user->ID,'mo2f_configure_test_option','MO2F_TEST');
						update_user_meta( $current_user->ID,'mo2f_selected_2factor_method', 'PUSH NOTIFICATIONS');
						$this->mo_auth_show_success_message();
						}else{
							unset($_SESSION[ 'mo2f_qrCode' ]);
							unset($_SESSION[ 'mo2f_transactionId' ]);
							unset($_SESSION[ 'mo2f_show_qr_code'] );
							update_option( 'mo2f_message','An error occured while processing your request. Please Try again.');
							$this->mo_auth_show_error_message();
						}
					}
				}else{
					update_option( 'mo2f_message','Invalid request. Please try again');
					$this->mo_auth_show_error_message();
				}
			
		}
		
		if(isset($_POST['option']) && $_POST['option'] == 'mo_2factor_test_out_of_band_email'){
			$this->miniorange_email_verification_call($current_user);
		}

		if(isset($_POST['option']) && $_POST['option'] == 'mo2f_out_of_band_success'){
			if(!current_user_can('manage_options') && get_user_meta( $current_user->ID,'mo2f_selected_2factor_method', true) == 'OUT OF BAND EMAIL'){
				if(get_user_meta($current_user->ID,'mo2f_email_verification_status',true)){
					update_option( 'mo2f_message','You have successfully completed the test.');
				}else{
					$email = get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true);
					$enduser = new Two_Factor_Setup();
					$response = json_decode($enduser->mo2f_update_userinfo($email, get_user_meta( $current_user->ID,'mo2f_selected_2factor_method', true),null,null,null),true);
					update_option( 'mo2f_message','<b>Email Verification</b> has been set as your 2nd factor method.');
				}
			}else{
				update_option( 'mo2f_message','You have successfully completed the test. Now <a href=\"admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_login&true\"><b>Click Here</b></a> to go to Login Settings. ');
			}
			delete_user_meta($current_user->ID,'mo2f_configure_test_option');
			update_user_meta($current_user->ID,'mo2f_email_verification_status',true);
			update_user_meta($current_user->ID,'mo_2factor_user_registration_status','MO_2_FACTOR_PLUGIN_SETTINGS');
			$this->mo_auth_show_success_message();
			
		}
		
		if(isset($_POST['option']) and $_POST['option'] == 'mo2f_out_of_band_error'){ //push and out of band email denied
			update_option( 'mo2f_message','You have denied the request.');
			delete_user_meta($current_user->ID,'mo2f_configure_test_option');
			update_user_meta($current_user->ID,'mo2f_email_verification_status',true);
			update_user_meta($current_user->ID,'mo_2factor_user_registration_status','MO_2_FACTOR_PLUGIN_SETTINGS');
			$this->mo_auth_show_error_message();
		}
		
		if(isset($_POST['option']) && $_POST['option'] == 'mo_2factor_test_google_auth'){
			update_user_meta($current_user->ID,'mo2f_configure_test_option','MO2F_TEST');
			update_user_meta($current_user->ID, 'mo2f_selected_2factor_method', 'GOOGLE AUTHENTICATOR');
		}
		
		if(isset($_POST['option']) && $_POST['option'] == 'mo2f_validate_google_auth_test'){
			$otp_token = '';
			if( MO2f_Utility::mo2f_check_empty_or_null( $_POST['otp_token'] ) ) {
			update_option( 'mo2f_message', 'Please enter a value to test your authentication.');
			$this->mo_auth_show_error_message();
			return;
			} else{
				$otp_token = sanitize_text_field( $_POST['otp_token'] );
			}
			$email = get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true);
			$customer = new Customer_Setup();
			$content = json_decode($customer->validate_otp_token( 'GOOGLE AUTHENTICATOR', $email, null, $otp_token, get_option('mo2f_customerKey'), get_option('mo2f_api_key')),true);
			if(json_last_error() == JSON_ERROR_NONE) {
		
				if(strcasecmp($content['status'], 'SUCCESS') == 0) { //Google OTP validated 
					if(current_user_can('manage_options')){
						update_option( 'mo2f_message','You have successfully completed the test. Now <a href=\"admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_login&true\"><b>Click Here</b></a> to go to Login Settings. ');
					}else{
						update_option( 'mo2f_message','You have successfully completed the test.');
					}
					delete_user_meta($current_user->ID,'mo2f_configure_test_option');
					$this->mo_auth_show_success_message();
					
				}else{  // OTP Validation failed.
					update_option( 'mo2f_message','Invalid OTP. Please try again.');
					$this->mo_auth_show_error_message();
				}
			}else{
				update_option( 'mo2f_message','Error occurred while validating the OTP. Please try again.');
				$this->mo_auth_show_error_message();
			}
		}
		
		if(isset($_POST['option']) && $_POST['option'] == 'mo2f_configure_google_auth_phone_type' ){
			$phone_type = $_POST['mo2f_app_type_radio'];
			$google_auth = new Miniorange_Rba_Attributes();
			$google_response = json_decode($google_auth->mo2f_google_auth_service(get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true)),true);
			if(json_last_error() == JSON_ERROR_NONE) {
				if($google_response['status'] == 'SUCCESS'){
					$mo2f_google_auth = array();
					$mo2f_google_auth['ga_qrCode'] = $google_response['qrCodeData'];
					$mo2f_google_auth['ga_secret'] = $google_response['secret'];
					$mo2f_google_auth['ga_phone'] = $phone_type;
					$_SESSION['mo2f_google_auth'] = $mo2f_google_auth;
				}else{
					update_option( 'mo2f_message','Error occurred while registering the user. Please try again.');
					$this->mo_auth_show_error_message();
				}
			}else{
				update_option( 'mo2f_message','Error occurred while registering the user. Please try again.');
				$this->mo_auth_show_error_message();
			}
		}
		
		if(isset($_POST['option']) && $_POST['option'] == 'mo2f_validate_google_auth' ){
			$otpToken = $_POST['google_token'];
			$ga_secret = isset($_POST['google_auth_secret']) ? $_POST['google_auth_secret'] : null;
			if(MO2f_Utility::mo2f_check_number_length($otpToken)){
				$email = get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true);
				$google_auth = new Miniorange_Rba_Attributes();
				$google_response = json_decode($google_auth->mo2f_validate_google_auth($email,$otpToken,$ga_secret),true);
				if(json_last_error() == JSON_ERROR_NONE) {
					if($google_response['status'] == 'SUCCESS'){
						$enduser = new Two_Factor_Setup();
						$response = json_decode($enduser->mo2f_update_userinfo($email,get_user_meta( $current_user->ID,'mo2f_selected_2factor_method',true),null,null,null),true);
						if(json_last_error() == JSON_ERROR_NONE) { 
							
							if($response['status'] == 'SUCCESS'){
							
								update_user_meta($current_user->ID,'mo2f_google_authentication_status',true);
								update_user_meta($current_user->ID,'mo2f_authy_authentication_status',false);
								delete_user_meta($current_user->ID,'mo2f_configure_test_option');
								delete_user_meta($current_user->ID,'mo_2factor_mobile_registration_status');
								update_user_meta($current_user->ID,'mo_2factor_user_registration_with_miniorange','SUCCESS');
								update_user_meta($current_user->ID,'mo_2factor_user_registration_status','MO_2_FACTOR_PLUGIN_SETTINGS');
								update_user_meta($current_user->ID,'mo2f_external_app_type','GOOGLE AUTHENTICATOR');		
								$message = '<b>Google Authenticator</b> has been set as your 2nd factor method. <a href=\"#test\" data-method=\"GOOGLE AUTHENTICATOR\">Click Here</a> to test Google Authenticator method.';
								update_option( 'mo2f_message',$message );
								$this->mo_auth_show_success_message();
								
							}else{
								update_option( 'mo2f_message','An error occured while processing your request. Please Try again.');
								$this->mo_auth_show_error_message();
							}
						}else{
							update_option( 'mo2f_message','An error occured while processing your request. Please Try again.');
							$this->mo_auth_show_error_message();
						}
					}else{
						update_option( 'mo2f_message','Error occurred while validating the OTP. Please try again. Possible causes: <br />1. You have entered an invalid OTP.<br />2. You App Time is not in sync. Go to Settings and tap on Time correction for codes and tap on Sync now .');
						$this->mo_auth_show_error_message();
					}
				}else{
					update_option( 'mo2f_message','Error occurred while validating the user. Please try again.');
					$this->mo_auth_show_error_message();
				}
			}else{
				update_option( 'mo2f_message','Only digits are allowed. Please enter again.');
				$this->mo_auth_show_error_message();
			}
		}
		
		if(isset($_POST['option']) && $_POST['option'] == 'mo_2factor_test_authy_auth'){
			update_user_meta($current_user->ID,'mo2f_configure_test_option','MO2F_TEST');
			update_user_meta($current_user->ID, 'mo2f_selected_2factor_method', 'AUTHY 2-FACTOR AUTHENTICATION');
		}
		
		if(isset($_POST['option']) && $_POST['option'] == 'mo2f_configure_authy_app' ){
			$authy = new Miniorange_Rba_Attributes();
			$authy_response = json_decode($authy->mo2f_google_auth_service(get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true)),true);
			if(json_last_error() == JSON_ERROR_NONE) {
				if($authy_response['status'] == 'SUCCESS'){
					$mo2f_authy_keys = array();
					$mo2f_authy_keys['authy_qrCode'] = $authy_response['qrCodeData'];
					$mo2f_authy_keys['authy_secret'] = $authy_response['secret'];
					$_SESSION['mo2f_authy_keys'] = $mo2f_authy_keys;
				}else{
					update_option( 'mo2f_message','Error occurred while registering the user. Please try again.');
					$this->mo_auth_show_error_message();
				}
			}else{
				update_option( 'mo2f_message','Error occurred while registering the user. Please try again.');
				$this->mo_auth_show_error_message();
			}
		}
		
		if(isset($_POST['option']) && $_POST['option'] == 'mo2f_validate_authy_auth' ){
			$otpToken = $_POST['authy_token'];
			$authy_secret = isset($_POST['authy_secret']) ? $_POST['authy_secret'] : null;
			if(MO2f_Utility::mo2f_check_number_length($otpToken)){
				$email = get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true);
				$authy_auth = new Miniorange_Rba_Attributes();
				$authy_response = json_decode($authy_auth->mo2f_validate_google_auth($email,$otpToken,$authy_secret),true);
				if(json_last_error() == JSON_ERROR_NONE) {
					if($authy_response['status'] == 'SUCCESS'){
						$enduser = new Two_Factor_Setup();
						$response = json_decode($enduser->mo2f_update_userinfo($email,'GOOGLE AUTHENTICATOR',null,null,null),true);
						if(json_last_error() == JSON_ERROR_NONE) { 
							
							if($response['status'] == 'SUCCESS'){
							
								update_user_meta($current_user->ID,'mo2f_authy_authentication_status',true);
								update_user_meta($current_user->ID,'mo2f_google_authentication_status',false);
								delete_user_meta($current_user->ID,'mo2f_configure_test_option');
								delete_user_meta($current_user->ID,'mo_2factor_mobile_registration_status');
								update_user_meta($current_user->ID,'mo_2factor_user_registration_with_miniorange','SUCCESS');
								update_user_meta($current_user->ID,'mo_2factor_user_registration_status','MO_2_FACTOR_PLUGIN_SETTINGS');
								update_user_meta($current_user->ID,'mo2f_external_app_type','AUTHY 2-FACTOR AUTHENTICATION');		
								$message = '<b>Authy 2-Factor Authentication</b> has been set as your 2nd factor method. <a href=\"#test\" data-method=\"AUTHY 2-FACTOR AUTHENTICATION\">Click Here</a> to test Authy 2-Factor Authentication method.';
								update_option( 'mo2f_message',$message );
								$this->mo_auth_show_success_message();
								
							}else{
								update_option( 'mo2f_message','An error occured while processing your request. Please Try again.');
								$this->mo_auth_show_error_message();
							}
						}else{
							update_option( 'mo2f_message','An error occured while processing your request. Please Try again.');
							$this->mo_auth_show_error_message();
						}
					}else{
						update_option( 'mo2f_message','Error occurred while validating the OTP. Please try again. Possible causes: <br />1. You have entered an invalid OTP.<br />2. You App Time is not in sync. Go to Settings and tap on Time correction for codes and tap on Sync now .');
						$this->mo_auth_show_error_message();
					}
				}else{
					update_option( 'mo2f_message','Error occurred while validating the user. Please try again.');
					$this->mo_auth_show_error_message();
				}
			}else{
				update_option( 'mo2f_message','Only digits are allowed. Please enter again.');
				$this->mo_auth_show_error_message();
			}
		}
		
		if(isset($_POST['option']) && $_POST['option'] == 'mo2f_save_kba'){
			if(MO2f_Utility::mo2f_check_empty_or_null( $_POST['mo2f_kbaquestion_1'] ) || MO2f_Utility::mo2f_check_empty_or_null( $_POST['mo2f_kba_ans1'] ) || MO2f_Utility::mo2f_check_empty_or_null( $_POST['mo2f_kbaquestion_2'] ) || MO2f_Utility::mo2f_check_empty_or_null( $_POST['mo2f_kba_ans2'] ) || MO2f_Utility::mo2f_check_empty_or_null( $_POST['mo2f_kbaquestion_3'] ) || MO2f_Utility::mo2f_check_empty_or_null( $_POST['mo2f_kba_ans3'] ) ){
				update_option( 'mo2f_message', 'All the fields are required. Please enter valid entries.');
				$this->mo_auth_show_error_message();
				return;
			}
			
			$kba_q1 = $_POST[ 'mo2f_kbaquestion_1' ];
			$kba_a1 = sanitize_text_field( $_POST[ 'mo2f_kba_ans1' ] );
			$kba_q2 = $_POST[ 'mo2f_kbaquestion_2' ];
			$kba_a2 = sanitize_text_field( $_POST[ 'mo2f_kba_ans2' ] );
			$kba_q3 = sanitize_text_field( $_POST[ 'mo2f_kbaquestion_3' ] );
			$kba_a3 = sanitize_text_field( $_POST[ 'mo2f_kba_ans3' ] );
			
			
			if (strcasecmp($kba_q1, $kba_q2) == 0 || strcasecmp($kba_q2, $kba_q3) == 0 || strcasecmp($kba_q3, $kba_q1) == 0) {
				update_option( 'mo2f_message', 'The questions you select must be unique.');
				$this->mo_auth_show_error_message();
				return;
			}
			$kba_q1 = addcslashes(stripslashes($kba_q1), '"\\');
			$kba_a1 = addcslashes(stripslashes($kba_a1), '"\\');
			$kba_q2 = addcslashes(stripslashes($kba_q2), '"\\');
			$kba_a2 = addcslashes(stripslashes($kba_a2), '"\\');
			$kba_q3 = addcslashes(stripslashes($kba_q3), '"\\');
			$kba_a3 = addcslashes(stripslashes($kba_a3), '"\\');
			
			$kba_registration = new Two_Factor_Setup();
			$kba_reg_reponse = json_decode($kba_registration->register_kba_details(get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true), $kba_q1,$kba_a1,$kba_q2,$kba_a2,$kba_q3,$kba_a3),true);
			if(json_last_error() == JSON_ERROR_NONE) { 
				if($kba_reg_reponse['status'] == 'SUCCESS'){
					if(isset($_POST['mobile_kba_option']) && $_POST['mobile_kba_option'] == 'mo2f_request_for_kba_as_emailbackup'){
						unset($_SESSION['mo2f_mobile_support']);
						delete_user_meta($current_user->ID,'mo2f_configure_test_option');
						update_user_meta($current_user->ID,'mo2f_kba_registration_status',true);
						delete_user_meta( $current_user->ID,'mo2f_selected_2factor_method');
						$message = 'Your KBA as alternate 2 factor is configured successfully.';
						update_option( 'mo2f_message',$message );
						$this->mo_auth_show_success_message();
					}else{
						$email = get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true);
						$enduser = new Two_Factor_Setup();
						update_user_meta( $current_user->ID,'mo2f_selected_2factor_method', 'KBA'); 
						$response = json_decode($enduser->mo2f_update_userinfo($email,'KBA',null,null,null),true);
						if(json_last_error() == JSON_ERROR_NONE) { 
							if($response['status'] == 'ERROR'){
								update_option( 'mo2f_message', $response['message']);
								$this->mo_auth_show_error_message();
							}else if($response['status'] == 'SUCCESS'){
								delete_user_meta($current_user->ID,'mo2f_configure_test_option');
								update_user_meta($current_user->ID,'mo2f_kba_registration_status',true);
								update_user_meta($current_user->ID,'mo_2factor_user_registration_status','MO_2_FACTOR_PLUGIN_SETTINGS');
								$authType = 'KBA';
								$message = '<b>' . $authType.'</b> is set as your 2nd factor method. <a href=\"#test\" data-method=\"' . $authType . '\">Click Here</a> to test ' . $authType . ' method.';
								update_option( 'mo2f_message',$message );
								$this->mo_auth_show_success_message();
							}else{
								update_option( 'mo2f_message','An error occured while processing your request. Please Try again.');
								$this->mo_auth_show_error_message();
							}
						}else{
							update_option( 'mo2f_message','Invalid request. Please try again');
							$this->mo_auth_show_error_message();
						}
					}
				}else{
					update_option( 'mo2f_message', 'Error occured while saving your kba details. Please try again.');
					$this->mo_auth_show_error_message();
					return;
				}
			}else{
				update_option( 'mo2f_message', 'Error occured while saving your kba details. Please try again.');
				$this->mo_auth_show_error_message();
				return;
			}
		
		}
		
		if(isset($_POST['option']) && $_POST['option'] == 'mo2f_2factor_test_kba'){
		
			$email = get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true);
			$challengeKba = new Customer_Setup();
			$content = $challengeKba->send_otp_token($email, 'KBA',get_option('mo2f_customerKey'),get_option('mo2f_api_key'));
			$response = json_decode($content, true);
			if(json_last_error() == JSON_ERROR_NONE) { /* Generate KBA Questions*/
				if($response['status'] == 'SUCCESS'){
					update_user_meta($current_user->ID,'mo2f_configure_test_option','MO2F_TEST');
					$_SESSION[ 'mo2f_transactionId' ] = $response['txId'];
					$questions = array();
					$questions[0] = $response['questions'][0]['question'];
					$questions[1] = $response['questions'][1]['question'];
					$_SESSION[ 'mo_2_factor_kba_questions' ] = $questions;
					update_user_meta($current_user->ID,'mo2f_selected_2factor_method','KBA');
					update_option( 'mo2f_message','Please answer the following security questions.');
					$this->mo_auth_show_success_message();
				}else if($response['status'] == 'ERROR'){
					update_option('mo2f_message','There was an error fetching security questions. Please try again.');
					$this->mo_auth_show_error_message();
				}
			}else{
				update_option('mo2f_message','There was an error fetching security questions. Please try again.');
				$this->mo_auth_show_error_message();
			}		
		}
		
		if(isset($_POST['option']) && $_POST['option'] == 'mo2f_validate_kba_details'){
			$kba_ans_1 = '';
			$kba_ans_2 = '';
			if( MO2f_Utility::mo2f_check_empty_or_null( $_POST['mo2f_answer_1'] ) || MO2f_Utility::mo2f_check_empty_or_null( $_POST['mo2f_answer_1'] )) {
				update_option( 'mo2f_message', 'All the fields are required. Please enter valid entries.');
				$this->mo_auth_show_error_message();
				return;
			} else{
				$kba_ans_1 = sanitize_text_field( $_POST['mo2f_answer_1'] );
				$kba_ans_2 = sanitize_text_field( $_POST['mo2f_answer_2'] );
			}
			
			$kbaAns = array();
			$kbaAns[0] = $_SESSION['mo_2_factor_kba_questions'][0];
			$kbaAns[1] = $kba_ans_1;
			$kbaAns[2] = $_SESSION['mo_2_factor_kba_questions'][1];
			$kbaAns[3] = $kba_ans_2;
						
			$kba_validate = new Customer_Setup();
			$kba_validate_response = json_decode($kba_validate->validate_otp_token( 'KBA', null, $_SESSION[ 'mo2f_transactionId' ], $kbaAns, get_option('mo2f_customerKey'), get_option('mo2f_api_key') ),true);
			
			if(json_last_error() == JSON_ERROR_NONE) {
				if(strcasecmp($kba_validate_response['status'], 'SUCCESS') == 0) {
					update_option( 'mo2f_message','You have successfully completed the test. Now <a href=\"admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_login&true\"><b>Click Here</b></a> to go to Login Settings. ');
					delete_user_meta($current_user->ID,'mo2f_configure_test_option');
					$this->mo_auth_show_success_message();
					
				}else{  // KBA Validation failed.
					update_option( 'mo2f_message','Invalid Answers. Please try again.');
					$this->mo_auth_show_error_message();
				}
			}
		}
			
			
		if(isset($_POST['option']) && $_POST['option'] == 'mo2f_verify_phone'){ // sendin otp for configuring OTP over SMS and Phone Call Verification
			$phone = sanitize_text_field( $_POST['verify_phone'] );
						
			if( MO2f_Utility::mo2f_check_empty_or_null( $phone ) ){
				update_option( 'mo2f_message', 'All the fields are required. Please enter valid entries.');
				$this->mo_auth_show_error_message();
				return;
			}
			$phone = str_replace(' ', '', $phone);
			$_SESSION['mo2f_phone'] = $phone;
			
			$customer = new Customer_Setup();
				
				if(get_user_meta( $current_user->ID,'mo2f_selected_2factor_method',true) == 'SMS'){
					$currentMethod = "OTP_OVER_SMS";
				}else if(get_user_meta( $current_user->ID,'mo2f_selected_2factor_method',true) == 'PHONE VERIFICATION'){
					$currentMethod = "PHONE_VERIFICATION";
				}
				
				$content = json_decode($customer->send_otp_token($phone,$currentMethod,get_option( 'mo2f_customerKey'),get_option( 'mo2f_api_key')), true);

			if(json_last_error() == JSON_ERROR_NONE) { /* Generate otp token */
				if($content['status'] == 'ERROR'){
					update_option( 'mo2f_message', $response['message']);
					$this->mo_auth_show_error_message();
				}else if($content['status'] == 'SUCCESS'){
					$_SESSION[ 'mo2f_transactionId' ] = $content['txId'];
					
					if(get_user_meta( $current_user->ID,'mo2f_selected_2factor_method',true) == 'SMS'){
							update_option( 'mo2f_message','The One Time Passcode has been sent to ' . $phone . '. Please enter the one time passcode below to verify your number.');
							update_option( 'mo2f_number_of_transactions', get_option('mo2f_number_of_transactions')-1);
					}else if(get_user_meta( $current_user->ID,'mo2f_selected_2factor_method',true)== 'PHONE VERIFICATION'){
						update_option( 'mo2f_message','You will receive a phone call on this number ' . $phone . '. Please enter the one time passcode below to verify your number.');
					}
					$this->mo_auth_show_success_message();
				}else{
					update_option( 'mo2f_message',$content['message']);
					$this->mo_auth_show_error_message();
				}
				
			}else{
				update_option( 'mo2f_message','Invalid request. Please try again');
				$this->mo_auth_show_error_message();
			}
		}
		
		if(isset($_POST['option']) && $_POST['option'] == 'mo2f_validate_otp'){
			$otp_token = '';
			if( MO2f_Utility::mo2f_check_empty_or_null( $_POST['otp_token'] ) ) {
				update_option( 'mo2f_message', 'All the fields are required. Please enter valid entries.');
				$this->mo_auth_show_error_message();
				return;
			} else{
				$otp_token = sanitize_text_field( $_POST['otp_token'] );
			}
			
			$customer = new Customer_Setup();
			$content = json_decode($customer->validate_otp_token( get_user_meta( $current_user->ID,'mo2f_selected_2factor_method',true), null, $_SESSION[ 'mo2f_transactionId' ], $otp_token, get_option('mo2f_customerKey'), get_option('mo2f_api_key') ),true);
			if($content['status'] == 'ERROR'){
				update_option( 'mo2f_message', $content['message']);
			
			}else if(strcasecmp($content['status'], 'SUCCESS') == 0) { //OTP validated 
					if(get_user_meta($current_user->ID,'mo2f_user_phone',true) && strlen(get_user_meta($current_user->ID,'mo2f_user_phone',true)) >= 4){
						if($_SESSION['mo2f_phone'] != get_user_meta($current_user->ID,'mo2f_user_phone',true) ){
							update_user_meta($current_user->ID,'mo2f_mobile_registration_status',false);
						}
					}
					$email = get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true);
					$phone = $_SESSION['mo2f_phone'];
					
					$enduser = new Two_Factor_Setup();
					$response = json_decode($enduser->mo2f_update_userinfo($email,get_user_meta( $current_user->ID,'mo2f_selected_2factor_method',true),$phone,null,null),true);
					if(json_last_error() == JSON_ERROR_NONE) { 
							
							if($response['status'] == 'ERROR'){
								unset($_SESSION[ 'mo2f_phone']);
								update_option( 'mo2f_message', $response['message']);
								$this->mo_auth_show_error_message();
							}else if($response['status'] == 'SUCCESS'){
								delete_user_meta($current_user->ID,'mo2f_configure_test_option');
								update_user_meta($current_user->ID,'mo2f_otp_registration_status',true);
								delete_user_meta($current_user->ID,'mo_2factor_mobile_registration_status');
								update_user_meta($current_user->ID,'mo_2factor_user_registration_with_miniorange','SUCCESS');
								update_user_meta($current_user->ID,'mo_2factor_user_registration_status','MO_2_FACTOR_PLUGIN_SETTINGS');
								update_user_meta($current_user->ID,'mo2f_user_phone',$_SESSION[ 'mo2f_phone']);
								unset($_SESSION[ 'mo2f_phone']);
								$testmethod = get_user_meta( $current_user->ID,'mo2f_selected_2factor_method',true);
								if(get_user_meta( $current_user->ID,'mo2f_selected_2factor_method',true) == 'SMS'){
									$authType = "OTP Over SMS";
								}else if(get_user_meta( $current_user->ID,'mo2f_selected_2factor_method',true) == 'PHONE VERIFICATION'){
									$authType = "Phone Call Verification";
								}
								$message = '<b>' . $authType.'</b> is set as your 2nd factor method. <a href=\"#test\" data-method=\"' . $testmethod . '\">Click Here</a> to test ' . $authType . ' method.';
								update_option( 'mo2f_message',$message );
								$this->mo_auth_show_success_message();
							}else{
									unset($_SESSION[ 'mo2f_phone']);
									update_option( 'mo2f_message','An error occured while processing your request. Please Try again.');
									$this->mo_auth_show_error_message();
							}
					}else{
							unset($_SESSION[ 'mo2f_phone']);
							update_option( 'mo2f_message','Invalid request. Please try again');
							$this->mo_auth_show_error_message();
					}
					
			}else{  // OTP Validation failed.
					update_option( 'mo2f_message','Invalid OTP. Please try again.');
					$this->mo_auth_show_error_message();
			}
			
		}
		
		if(isset($_POST['option']) && $_POST['option'] == 'mo2f_save_2factor_method'){  // configure 2nd factor for all users
			if(get_user_meta($current_user->ID,'mo_2factor_user_registration_with_miniorange',true) == 'SUCCESS'){
				
				if($_POST['mo2f_selected_2factor_method'] == 'OUT OF BAND EMAIL' && !current_user_can('manage_options')){
					$this->miniorange_email_verification_call($current_user);
				}
				update_user_meta($current_user->ID,'mo2f_configure_test_option','MO2F_CONFIGURE'); //status for configuring the specific 2nd-factor method
				update_user_meta( $current_user->ID,'mo2f_selected_2factor_method', $_POST['mo2f_selected_2factor_method']); //status for second factor selected by user
			}else{
				update_option( 'mo2f_message','Invalid request. Please register with miniOrange to configure 2 Factor plugin.');
				$this->mo_auth_show_error_message();
			}
		}
		
		if(isset($_POST['option']) && $_POST['option'] == 'mo2f_update_2factor_method'){ // save 2nd factor method for all users
				
					$email = get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true);
					$enduser = new Two_Factor_Setup();
					update_user_meta( $current_user->ID,'mo2f_selected_2factor_method', $_POST['mo2f_selected_2factor_method']); 
					$current_method = $_POST['mo2f_selected_2factor_method'] == 'AUTHY 2-FACTOR AUTHENTICATION' ? 'GOOGLE AUTHENTICATOR' : $_POST['mo2f_selected_2factor_method'];
					$response = json_decode($enduser->mo2f_update_userinfo($email, $current_method,null,null,null),true);
					
					if(json_last_error() == JSON_ERROR_NONE) { 
							if($response['status'] == 'ERROR'){
								update_option( 'mo2f_message', $response['message']);
								$this->mo_auth_show_error_message();
							}else if($response['status'] == 'SUCCESS'){
								$selectedMethod = get_user_meta( $current_user->ID,'mo2f_selected_2factor_method',true);
								if($selectedMethod == 'OUT OF BAND EMAIL'){
									$selectedMethod = "Email Verification";
								} else if($selectedMethod == 'MOBILE AUTHENTICATION'){
									$selectedMethod = "QR Code Authentication";
								}else if($selectedMethod == 'SMS'){
									$authType = "OTP Over SMS";
								}else if($selectedMethod == 'GOOGLE AUTHENTICATOR' || $selectedMethod == 'AUTHY 2-FACTOR AUTHENTICATION'){
									update_user_meta($current_user->ID,'mo2f_external_app_type',$selectedMethod);
								}
								update_user_meta($current_user->ID,'mo_2factor_user_registration_status','MO_2_FACTOR_PLUGIN_SETTINGS');
								delete_user_meta($current_user->ID,'mo2f_configure_test_option');
								delete_user_meta($current_user->ID,'mo_2factor_mobile_registration_status');
								update_option( 'mo2f_message', $selectedMethod. ' is set as your Two-Factor method.');
								$this->mo_auth_show_success_message();
							}else{
									update_option( 'mo2f_message','An error occured while processing your request. Please Try again.');
									$this->mo_auth_show_error_message();
							}
					}else{
							update_option( 'mo2f_message','Invalid request. Please try again');
							$this->mo_auth_show_error_message();
					}
		}
		
		if(isset($_POST['option']) && $_POST['option'] == 'mo2f_cancel_configuration'){
			unset($_SESSION[ 'mo2f_qrCode' ]);
			unset($_SESSION[ 'mo2f_transactionId' ]);
			unset($_SESSION[ 'mo2f_show_qr_code']);
			unset($_SESSION[ 'mo2f_phone']);
			unset($_SESSION[ 'mo2f_google_auth' ]);
			unset($_SESSION[ 'mo2f_mobile_support' ]);
			unset($_SESSION[ 'mo2f_authy_keys' ]);
			delete_user_meta($current_user->ID,'mo2f_configure_test_option');
		}
		
		if(isset($_POST['option']) && $_POST['option'] == 'mo2f_2factor_configure_kba_backup'){
			$_SESSION['mo2f_mobile_support'] = 'MO2F_EMAIL_BACKUP_KBA';
			update_user_meta($current_user->ID,'mo2f_configure_test_option','MO2F_CONFIGURE');
			update_user_meta($current_user->ID,'mo2f_selected_2factor_method','KBA');
		}
		
	}
	
	function miniorange_email_verification_call($current_user){
		$challengeMobile = new Customer_Setup();
		$email = get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true);
		$content = $challengeMobile->send_otp_token($email, 'OUT OF BAND EMAIL',get_option('mo2f_customerKey'),get_option('mo2f_api_key'));
		$response = json_decode($content, true);
		if(json_last_error() == JSON_ERROR_NONE) { /* Generate out of band email */
			if($response['status'] == 'ERROR'){
				update_option( 'mo2f_message', $response['message']);
				$this->mo_auth_show_error_message();
			}else{
				if($response['status'] == 'SUCCESS'){
				
				$_SESSION[ 'mo2f_transactionId' ] = $response['txId'];
				update_option( 'mo2f_message','A verification email is sent to<b> '. $email . '</b>. Please click on accept link to verify your email.');
				update_user_meta($current_user->ID,'mo2f_configure_test_option','MO2F_TEST');
				update_user_meta( $current_user->ID,'mo2f_selected_2factor_method', 'OUT OF BAND EMAIL');
				$this->mo_auth_show_success_message();
				}else{
					unset($_SESSION[ 'mo2f_transactionId' ]);
					update_option( 'mo2f_message','An error occured while processing your request. Please Try again.');
					$this->mo_auth_show_error_message();
				}
			}
		}else{
			update_option( 'mo2f_message','Invalid request. Please try again');
			$this->mo_auth_show_error_message();
		}
	}
	
	function mo2f_create_customer($current_user){
		delete_user_meta($current_user->ID,'mo2f_sms_otp_count');
		delete_user_meta($current_user->ID,'mo2f_email_otp_count');
		$customer = new Customer_Setup();
		$customerKey = json_decode($customer->create_customer(), true);
		if($customerKey['status'] == 'ERROR'){
			update_option( 'mo2f_message', $customerKey['message']);
			$this->mo_auth_show_error_message();
		}else{
			if(strcasecmp($customerKey['status'], 'CUSTOMER_USERNAME_ALREADY_EXISTS') == 0) {	//admin already exists in miniOrange
				$content = $customer->get_customer_key();
				$customerKey = json_decode($content, true);
				if(json_last_error() == JSON_ERROR_NONE) {
					if(array_key_exists("status", $customerKey) && $customerKey['status'] == 'ERROR'){
						update_option('mo2f_message',$customerKey['message']);
						$this->mo_auth_show_error_message();
					}else{
						if(isset($customerKey['id']) && !empty($customerKey['id'])){
							update_option( 'mo2f_customerKey', $customerKey['id']);
							update_option( 'mo2f_api_key', $customerKey['apiKey']);
							update_option( 'mo2f_customer_token', $customerKey['token']);
							update_option( 'mo2f_app_secret', $customerKey['appSecret'] );
							update_option( 'mo2f_miniorange_admin',$current_user->ID);
							update_option( 'mo2f_new_customer',true);
							delete_option('mo2f_password');
							update_option( 'mo_2factor_admin_registration_status','MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS');
							update_user_meta($current_user->ID,'mo_2factor_user_registration_with_miniorange','SUCCESS');
							update_user_meta($current_user->ID,'mo_2factor_map_id_with_email',get_option('mo2f_email'));
							update_user_meta($current_user->ID,'mo_2factor_user_registration_status','MO_2_FACTOR_PLUGIN_SETTINGS');
							$enduser = new Two_Factor_Setup();
							$enduser->mo2f_update_userinfo(get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true), 'OUT OF BAND EMAIL',null,'API_2FA',true);	
							update_user_meta($current_user->ID,'mo2f_email_verification_status',true);
							update_option( 'mo2f_message', 'Your account has been retrieved successfully. <b>Email Verification</b> has been set as your default 2nd factor method. <a href=\"admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mobile_configure\" >Click Here </a>to configure another 2nd factor authentication method.');
							$this->mo_auth_show_success_message();
						}else{
							update_option( 'mo2f_message', 'An error occured while creating your account. Please try again by sending OTP again.');
							update_user_meta($current_user->ID, 'mo_2factor_user_registration_status','MO_2_FACTOR_OTP_DELIVERED_FAILURE');
							$this->mo_auth_show_error_message();
						}
						
					}
					
				} else {
					update_option( 'mo2f_message', 'Invalid email or password. Please try again.');
					update_user_meta($current_user->ID, 'mo_2factor_user_registration_status','MO_2_FACTOR_VERIFY_CUSTOMER');
					$this->mo_auth_show_error_message();
				}
			}else{
				if(isset($customerKey['id']) && !empty($customerKey['id'])){
					update_option( 'mo2f_customerKey', $customerKey['id']);
					update_option( 'mo2f_api_key', $customerKey['apiKey']);
					update_option( 'mo2f_customer_token', $customerKey['token']);
					update_option( 'mo2f_app_secret', $customerKey['appSecret'] );
					update_option( 'mo2f_miniorange_admin',$current_user->ID);
					delete_option('mo2f_password');
					update_option( 'mo2f_new_customer',true);
					update_option( 'mo_2factor_admin_registration_status','MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS');
					update_user_meta($current_user->ID,'mo_2factor_user_registration_with_miniorange','SUCCESS');
					update_user_meta($current_user->ID,'mo_2factor_map_id_with_email',get_option('mo2f_email'));
					update_option( 'mo2f_message', 'Your account has been created successfully. ');
					update_user_meta($current_user->ID,'mo_2factor_user_registration_status','MO_2_FACTOR_PLUGIN_SETTINGS');
					$enduser = new Two_Factor_Setup();
					$enduser->mo2f_update_userinfo(get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true), 'OUT OF BAND EMAIL',null,'API_2FA',true);	
					update_user_meta($current_user->ID,'mo2f_email_verification_status',true);
					update_option( 'mo2f_message', 'Your account has been created successfully. <b>Email Verification</b> has been set as your default 2nd factor method. <a href=\"admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mobile_configure\" >Click Here </a>to configure another 2nd factor authentication method.');
					$this->mo_auth_show_success_message();
					header('Location: admin.php?page=miniOrange_2_factor_settings&mo2f_tab=mo2f_pricing');
				}else{
					update_option( 'mo2f_message', 'An error occured while creating your account. Please try again by sending OTP again.');
					update_user_meta($current_user->ID, 'mo_2factor_user_registration_status','MO_2_FACTOR_OTP_DELIVERED_FAILURE');
					$this->mo_auth_show_error_message();
				}
				
			}
		}
	}
	
	function mo2f_create_user($current_user,$email){
		$email = strtolower($email);
		$enduser = new Two_Factor_Setup();
		$check_user = json_decode($enduser->mo_check_user_already_exist($email),true);
		if(json_last_error() == JSON_ERROR_NONE){
			if($check_user['status'] == 'ERROR'){
				update_option( 'mo2f_message', $check_user['message']);
				$this->mo_auth_show_error_message();
			}else{
				if(strcasecmp($check_user['status'], 'USER_FOUND') == 0){
					delete_user_meta($current_user->ID,'mo_2factor_user_email');
					update_user_meta($current_user->ID,'mo_2factor_user_registration_with_miniorange','SUCCESS');
					update_user_meta($current_user->ID,'mo_2factor_map_id_with_email',$email);
					update_user_meta($current_user->ID,'mo_2factor_user_registration_status','MO_2_FACTOR_PLUGIN_SETTINGS');
					$enduser->mo2f_update_userinfo(get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true), 'OUT OF BAND EMAIL',null,'API_2FA',true);	
					update_user_meta($current_user->ID,'mo2f_email_verification_status',true);
					$message = 'You are registered successfully. <b>Email Verification</b> has been set as your default 2nd factor method. <a href=\"admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mobile_configure\" >Click Here </a>to configure another 2nd factor authentication method.';
					update_option( 'mo2f_message', $message);
					$this->mo_auth_show_success_message();
				}else if(strcasecmp($check_user['status'], 'USER_NOT_FOUND') == 0){
					$content = json_decode($enduser->mo_create_user($current_user,$email), true);
						if(json_last_error() == JSON_ERROR_NONE) {
							if($content['status'] == 'ERROR'){
								update_option( 'mo2f_message', $content['message']);
								$this->mo_auth_show_error_message();
							}else{
								if(strcasecmp($content['status'], 'SUCCESS') == 0) {
									delete_user_meta($current_user->ID,'mo_2factor_user_email');
									update_user_meta($current_user->ID,'mo_2factor_user_registration_with_miniorange','SUCCESS');
									update_user_meta($current_user->ID,'mo_2factor_map_id_with_email',$email);
									update_user_meta($current_user->ID,'mo_2factor_user_registration_status','MO_2_FACTOR_PLUGIN_SETTINGS');
									$enduser->mo2f_update_userinfo(get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true), 'OUT OF BAND EMAIL',null,'API_2FA',true);	
									update_user_meta($current_user->ID,'mo2f_email_verification_status',true);
									$message = 'You are registered successfully. <b>Email Verification</b> has been set as your default 2nd factor method. <a href=\"admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mobile_configure\" >Click Here </a>to configure another 2nd factor authentication method.';
									update_option( 'mo2f_message', $message);
									$this->mo_auth_show_success_message();
									
								}else{
									update_option( 'mo2f_message','Error occurred while registering the user. Please try again.');
									$this->mo_auth_show_error_message();
								}
							}
						}else{
								update_option( 'mo2f_message','Error occurred while registering the user. Please try again or contact your admin.');
								$this->mo_auth_show_error_message();
						}
				}else{
					update_option( 'mo2f_message','Error occurred while registering the user. Please try again.');
					$this->mo_auth_show_error_message();
				}
			}
		}else{
			update_option( 'mo2f_message','Error occurred while registering the user. Please try again.');
			$this->mo_auth_show_error_message();
		}
	}

	function mo2f_get_qr_code_for_mobile($email,$id){
		$registerMobile = new Two_Factor_Setup();
		$content = $registerMobile->register_mobile($email);
		$response = json_decode($content, true);
		if(json_last_error() == JSON_ERROR_NONE) {
			if($response['status'] == 'ERROR'){
				update_option( 'mo2f_message', $response['message']);
				unset($_SESSION[ 'mo2f_qrCode' ]);
				unset($_SESSION[ 'mo2f_transactionId' ]);
				unset($_SESSION[ 'mo2f_show_qr_code']);
				$this->mo_auth_show_error_message();
			}else{
				if($response['status'] == 'IN_PROGRESS'){
				update_option( 'mo2f_message','Please scan the QR Code now.');
				$_SESSION[ 'mo2f_qrCode' ] = $response['qrCode'];
				$_SESSION[ 'mo2f_transactionId' ] = $response['txId'];
				$_SESSION[ 'mo2f_show_qr_code'] = 'MO_2_FACTOR_SHOW_QR_CODE';
				$this->mo_auth_show_success_message();
				}else{
						update_option( 'mo2f_message', "An error occured while processing your request. Please Try again.");
				unset($_SESSION[ 'mo2f_qrCode' ]);
				unset($_SESSION[ 'mo2f_transactionId' ]);
				unset($_SESSION[ 'mo2f_show_qr_code']);
				$this->mo_auth_show_error_message();
				}
			}
		}
	}
	
	function mo_get_2fa_shorcode($atts){
		if(!is_user_logged_in() && mo2f_is_customer_registered()){
			$mo2f_shorcode = new MO2F_ShortCode();
			$html = $mo2f_shorcode->mo2FAFormShortCode($atts);
			return $html;
		}
	}
	
	function mo_get_login_form_shortcode($atts){
		if(!is_user_logged_in() && mo2f_is_customer_registered()){
			$mo2f_shorcode = new MO2F_ShortCode();
			$html = $mo2f_shorcode->mo2FALoginFormShortCode($atts);
			return $html;
		}
	}
}

	function mo2f_is_customer_registered() {
		$email = get_option('mo2f_email');
		$customerKey = get_option('mo2f_customerKey');
		if(!$email || !$customerKey || !is_numeric(trim($customerKey))) {
			return 0;
		} else {
			return 1;
		}
	}
	
	/* App Specific Password
	//AJAX Function to callback
	function ajax_callback(){
		
		global $user_id;
			
		$secret = create_secret();
		$result = array( 'new-secret' => $secret );
			
		header( 'Content-Type: application/json' );
		echo json_encode( $result ); 

		// die() is required to return a proper result
		die(); 
			
	}
	
	//Create password secret
	function create_secret() {
	
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // allowed characters in Base32
		$charsLength = strlen($chars);
		$secret = '';
		
		for ( $i = 0; $i < 16; $i++ ) {
			$secret .= substr( $chars, wp_rand( 0, strlen( $chars ) - 1 ), 1 );
		}
		
		return $secret;
		
	}*/



	
new Miniorange_Authentication;
?>