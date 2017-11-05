<?Php
/** miniOrange enables user to log in through mobile authentication as an additional layer of security over password.
    Copyright (C) 2015  miniOrange

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>
* @package 		miniOrange OAuth
* @license		http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/
/**
This library is miniOrange Authentication Service. 
Contains Request Calls to Customer service.

**/
include_once dirname( __FILE__ ) . '/miniorange_2_factor_common_login.php';


class Miniorange_Mobile_Login{
	
	public function miniorange_login_start_session(){
 		if( ! session_id() || session_id() == '' || !isset($_SESSION) ) {
 			session_start();
 		}
	}
	
	function remove_current_activity(){
		unset($_SESSION[ 'mo2f_current_user' ]);
		unset($_SESSION[ 'mo_2factor_login_status' ]);
		unset($_SESSION[ 'mo2f-login-qrCode' ]);
		unset($_SESSION[ 'mo2f-login-transactionId' ]);
		unset($_SESSION[ 'mo2f-login-message' ]);
		unset($_SESSION[ 'mo_2_factor_kba_questions' ]);
		unset($_SESSION[ 'mo2f_1stfactor_status' ]);
		unset($_SESSION[ 'mo2f_rba_status' ]);
		unset($_SESSION[ 'mo2f_show_qr_code']);
		unset($_SESSION['mo2f_google_auth']);
		unset($_SESSION['mo2f_authy_keys']);
	}
	
	
	function mo2fa_default_login($user,$username,$password){
		
		$currentuser = wp_authenticate_username_password($user, $username, $password);
		if (is_wp_error($currentuser)) {
			return $currentuser;
		}else{
			$this->miniorange_login_start_session();
			
			$current_roles = miniorange_get_user_role($currentuser);
			
			$enabled = miniorange_check_if_2fa_enabled_for_roles($current_roles);
			$redirect_to = isset($_REQUEST[ 'redirect_to' ]) ? $_REQUEST[ 'redirect_to' ] : null;
			
			if($enabled){
					
				if(get_user_meta($currentuser->ID,'mo_2factor_mobile_registration_status',true) == 'MO_2_FACTOR_SUCCESS'){ // for existing users
					
					$_SESSION['mo2f-login-message'] = '<strong>ERROR</strong>: Login with password is disabled for you. Please Login using your phone.';
					$this->mo_auth_show_error_message();
					$this->mo2f_redirectto_wp_login();
					$error = new WP_Error();
					return $error;
				} else if(get_user_meta($currentuser->ID,'mo_2factor_map_id_with_email',true) && get_user_meta($currentuser->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){ //checking if user has configured any 2nd factor method
					$_SESSION['mo2f-login-message'] = '<strong>ERROR</strong>: Login with password is disabled for you. Please Login using your phone.';
					$this->mo_auth_show_error_message();
					$this->mo2f_redirectto_wp_login();
					$error = new WP_Error();
					return $error;
				}else{ //if user has not configured any 2nd factor method then logged him in without asking 2nd factor
									
					$this->mo2f_verify_and_authenticate_userlogin($currentuser, $redirect_to);
				}
			}else{ //plugin is not activated for non-admin then logged him in
								

				$this->mo2f_verify_and_authenticate_userlogin($currentuser, $redirect_to);
			
			}
		}
	}
	
	function mo2f_verify_and_authenticate_userlogin($user, $redirect_to=null){
		
		$user_id = $user->ID;
		wp_set_current_user($user_id, $user->user_login);
		$this->remove_current_activity();
		wp_set_auth_cookie( $user_id, true );
		do_action( 'wp_login', $user->user_login, $user );
		redirect_user_to($user, $redirect_to);
		exit;
			
	}
	
	function mo2f_redirectto_wp_login(){
		remove_action('login_enqueue_scripts', array( $this, 'mo_2_factor_hide_login'));
		add_action('login_dequeue_scripts', array( $this, 'mo_2_factor_show_login'));
		if(get_option('mo2f_show_loginwith_phone')){
			$_SESSION[ 'mo_2factor_login_status' ] = 'MO_2_FACTOR_LOGIN_WHEN_PHONELOGIN_ENABLED';
		}else{
			$_SESSION[ 'mo_2factor_login_status' ] = 'MO_2_FACTOR_SHOW_USERPASS_LOGIN_FORM';
		}
	}
	
	function custom_login_enqueue_scripts(){
		wp_enqueue_script('jquery');
		wp_enqueue_script( 'bootstrap_script', plugins_url('includes/js/bootstrap.min.js', __FILE__ ));
	}
	
	function mo_2_factor_hide_login() {
		wp_register_style( 'hide-login', plugins_url( 'includes/css/hide-login.css?version=4.5.5', __FILE__ ) );
		wp_register_style( 'bootstrap', plugins_url( 'includes/css/bootstrap.min.css?version=4.5.5', __FILE__ ) );
		
		wp_enqueue_style( 'hide-login' );
		wp_enqueue_style( 'bootstrap' );
		
	}
	
	function mo_2_factor_show_login() {
		if(get_option('mo2f_show_loginwith_phone')){
			wp_register_style( 'show-login', plugins_url( 'includes/css/hide-login-form.css?version=4.5.5', __FILE__ ) );
		}else{
			wp_register_style( 'show-login', plugins_url( 'includes/css/show-login.css?version=4.5.5', __FILE__ ) );
		}
		wp_enqueue_style( 'show-login' );
	}
	
	function mo_2_factor_show_login_with_password_when_phonelogin_enabled(){
		wp_register_style( 'show-login', plugins_url( 'includes/css/show-login.css?version=4.5.5', __FILE__ ) );
		wp_enqueue_style( 'show-login' );
	}
	
	function mo_auth_success_message() {
		$message = $_SESSION['mo2f-login-message'];
		return "<div> <p class='message'>" . $message . "</p></div>";
	}

	function mo_auth_error_message() {
		$id = "login_error1";
		$message = $_SESSION['mo2f-login-message'];
		return "<div id='" . $id . "'> <p>" . $message . "</p></div>";
	}
	
	function mo_auth_show_error_message() {
		remove_filter( 'login_message', array( $this, 'mo_auth_success_message') );
		add_filter( 'login_message', array( $this, 'mo_auth_error_message') );
		
	}
	
	
	
	
	function mo_auth_show_success_message() {
		remove_filter( 'login_message', array( $this, 'mo_auth_error_message') );
		add_filter( 'login_message', array( $this, 'mo_auth_success_message') );
	}
	


	
	// login form fields
	function miniorange_login_form_fields($mo2fa_login_status=null, $mo2fa_login_message=null) {
		if(get_option('mo2f_show_loginwith_phone')){ //login with phone overwrite default login form
		
			$login_status_phone_enable = isset($_SESSION[ 'mo_2factor_login_status' ]) ? $_SESSION[ 'mo_2factor_login_status' ] : '';
			if($login_status_phone_enable == 'MO_2_FACTOR_LOGIN_WHEN_PHONELOGIN_ENABLED' && isset($_POST['miniorange_login_nonce']) && wp_verify_nonce( $_POST['miniorange_login_nonce'], 'miniorange-2-factor-login-nonce' )){
				$this->mo_2_factor_show_login_with_password_when_phonelogin_enabled();
				$this->mo_2_factor_show_wp_login_form_when_phonelogin_enabled();
				$current_user = isset($_SESSION[ 'mo2f_current_user' ]) ? unserialize($_SESSION[ 'mo2f_current_user' ]) : null;
				$mo2f_user_login = is_null($current_user) ? null : $current_user->user_login;
				?><script>
					jQuery('#user_login').val(<?php echo "'" . $mo2f_user_login . "'"; ?>);
				</script><?php
			}else{
				$this->mo_2_factor_show_login();
				$this->mo_2_factor_show_wp_login_form();
			}
			
		}else{ //Login with phone is alogin with default login form
		
			$this->mo_2_factor_show_login();
			$this->mo_2_factor_show_wp_login_form();
		}
		
	}
	
	function miniorange_login_footer_form(){
		
	?>
		<input type="hidden" name="miniorange_login_nonce" value="<?php echo wp_create_nonce('miniorange-2-factor-login-nonce'); ?>" />
		<form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo wp_login_url(); ?>" hidden>
			<input type="hidden" name="miniorange_mobile_validation_failed_nonce" value="<?php echo wp_create_nonce('miniorange-2-factor-mobile-validation-failed-nonce'); ?>" />
		</form>
		<form name="f" id="mo2f_show_qrcode_loginform" method="post" action="" hidden>
			<input type="text" name="mo2fa_username" id="mo2fa_username" hidden/>
			<input type="hidden" name="miniorange_login_nonce" value="<?php echo wp_create_nonce('miniorange-2-factor-login-nonce'); ?>" />
		</form>
	<?php
			
	}
	
	
	function mo_2_factor_show_wp_login_form_when_phonelogin_enabled(){
	?>
		<script>
			var content = '<a href="javascript:void(0)" id="backto_mo" onClick="mo2fa_backtomologin()" style="float:right">← Back</a>';
			jQuery('#login').append(content);
			function mo2fa_backtomologin(){
				jQuery('#mo2f_backto_mo_loginform').submit();
			}
		</script>
	<?php
	}
	
	function mo_2_factor_show_wp_login_form(){
	?>
		<div class="mo2f-login-container">
			<?php if(!get_option('mo2f_show_loginwith_phone')){ ?>
			<div style="position: relative" class="or-container">
				<div style="border-bottom: 1px solid #EEE; width: 90%; margin: 0 5%; z-index: 1; top: 50%; position: absolute;"></div>
				<h2 style="color: #666; margin: 0 auto 20px auto; padding: 3px 0; text-align:center; background: white; width: 20%; position:relative; z-index: 2;">or</h2>
			</div>
			<?php } ?>
			<div class="mo2f-button-container" id="mo2f_button_container">
				<input type="text" name="mo2fa_usernamekey" id="mo2fa_usernamekey" autofocus="true" placeholder="Username"/>
					<p>
						<input type="button" name="miniorange_login_submit"  style="width:100% !important;" onclick="mouserloginsubmit();" id="miniorange_login_submit" class="miniorange-button button-add" value="Login with 2nd factor" />
					</p>
					<?php if(!get_option('mo2f_show_loginwith_phone')){ ?><br /><br /><?php } ?>
			</div>
		</div>
		
		<script>
			jQuery(window).scrollTop(jQuery('#mo2f_button_container').offset().top);
			function mouserloginsubmit(){
				var username = jQuery('#mo2fa_usernamekey').val();
				document.getElementById("mo2f_show_qrcode_loginform").elements[0].value = username;
				jQuery('#mo2f_show_qrcode_loginform').submit();
				
			 }
			 
			 jQuery('#mo2fa_usernamekey').keypress(function(e){
				  if(e.which == 13){//Enter key pressed
					e.preventDefault();
					var username = jQuery('#mo2fa_usernamekey').val();
					document.getElementById("mo2f_show_qrcode_loginform").elements[0].value = username;
					jQuery('#mo2f_show_qrcode_loginform').submit();
				  }
				 
			});
		</script>
	<?php
	}
}
?>