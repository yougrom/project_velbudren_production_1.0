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
include_once dirname( __FILE__ ) . '/class-rba-attributes.php';


class Miniorange_Password_2Factor_Login{
	
	function remove_current_activity(){
		unset($_SESSION[ 'mo2f_current_user' ]);
		unset($_SESSION[ 'mo2f_1stfactor_status' ]);
		unset($_SESSION[ 'mo_2factor_login_status' ]);
		unset($_SESSION[ 'mo2f-login-qrCode' ]);
		unset($_SESSION[ 'mo2f-login-transactionId' ]);
		unset($_SESSION[ 'mo2f-login-message' ]);
		unset($_SESSION[ 'mo2f_rba_status' ]);
		unset($_SESSION[ 'mo_2_factor_kba_questions' ]);
		unset($_SESSION[ 'mo2f_show_qr_code']);
		unset($_SESSION['mo2f_google_auth']);
		unset($_SESSION['mo2f_authy_keys']);
		
	}
	
	function mo2fa_pass2login($redirect_to=null){
		if(isset($_SESSION[ 'mo2f_current_user' ]) && isset($_SESSION[ 'mo2f_1stfactor_status' ]) && $_SESSION[ 'mo2f_1stfactor_status' ] = 'VALIDATE_SUCCESS'){
			$currentuser = unserialize( $_SESSION[ 'mo2f_current_user' ] );
			$user_id = $currentuser->ID;
			wp_set_current_user($user_id, $currentuser->user_login);
			$this->remove_current_activity();
			wp_set_auth_cookie( $user_id, true );
			do_action( 'wp_login', $currentuser->user_login, $currentuser );
			redirect_user_to($currentuser, $redirect_to);
			exit;
		}else{
			$this->remove_current_activity();
		}
	}
	
	public function miniorange_pass2login_start_session(){
 		if( ! session_id() || session_id() == '' || !isset($_SESSION) ) {
 			session_start();
 		}
	}
	
	
	public function miniorange_pass2login_redirect() {
		
		if(!get_option('mo2f_login_policy')){
							
			if (isset($_POST['miniorange_login_nonce'])){			
				$nonce = $_POST['miniorange_login_nonce'];
				if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-login-nonce' ) ) {
					$this->remove_current_activity();
					$error = new WP_Error();
					$error->add('empty_username', __('<strong>ERROR</strong>: Invalid Request.'));
					return $error;
				} else {
					$this->miniorange_pass2login_start_session();
					$mobile_login = new Miniorange_Mobile_Login();
					//validation and sanitization
					$username = '';
					if( MO2f_Utility::mo2f_check_empty_or_null( $_POST['mo2fa_username'] ) ) {
						$_SESSION['mo2f-login-message'] = 'Please enter username to proceed';
						$mobile_login->mo_auth_show_error_message();
						return;
					} else{
						$username = sanitize_text_field( $_POST['mo2fa_username'] );
					}
					
					
					
					if ( username_exists( $username ) ){ /*if username exists in wp site */
						$user = new WP_User( $username );
						
						$_SESSION[ 'mo2f_current_user' ] = serialize($user);
						$redirect_to = isset($_REQUEST[ 'redirect_to' ]) ? $_REQUEST[ 'redirect_to' ] : null;
						
						$_SESSION[ 'mo2f_1stfactor_status' ] = 'VALIDATE_SUCCESS';
						
						$current_roles = miniorange_get_user_role($user);
						$enabled = miniorange_check_if_2fa_enabled_for_roles($current_roles);

						if($enabled){
							if(get_user_meta($user->ID,'mo_2factor_map_id_with_email',true) && get_user_meta($user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){
									if(MO2f_Utility::check_if_request_is_from_mobile_device($_SERVER['HTTP_USER_AGENT']) && get_user_meta($user->ID,'mo2f_kba_registration_status',true) && get_option('mo2f_enable_mobile_support')){
										$this->mo2f_pass2login_kba_verification($user->ID , $redirect_to);

									}else{
										$mo2f_second_factor = mo2f_get_user_2ndfactor($user);
										if($mo2f_second_factor == 'MOBILE AUTHENTICATION'){
											$this->mo2f_pass2login_mobile_verification($user , $redirect_to);
										}else if($mo2f_second_factor == 'PUSH NOTIFICATIONS' || $mo2f_second_factor == 'OUT OF BAND EMAIL'){
									$this->mo2f_pass2login_push_oobemail_verification($user,$mo2f_second_factor, $redirect_to);
										}else if($mo2f_second_factor == 'SOFT TOKEN' || $mo2f_second_factor == 'SMS' || $mo2f_second_factor == 'PHONE VERIFICATION' || $mo2f_second_factor == 'GOOGLE AUTHENTICATOR'){
											
											$this->mo2f_pass2login_otp_verification($user,$mo2f_second_factor, $redirect_to);
											
										}else if($mo2f_second_factor == 'KBA'){
											
											$this->mo2f_pass2login_kba_verification($user->ID, $redirect_to);
										}else{
											$this->remove_current_activity();
											$_SESSION['mo2f-login-message'] = 'Please try again or contact your admin.';
											$mobile_login->mo_auth_show_success_message();
										}
									}
							}else{
								
								$_SESSION['mo2f-login-message'] = 'Please login into your account using password.';
								$mobile_login->mo_auth_show_success_message();
								$mobile_login->mo2f_redirectto_wp_login();
							}	
						}else{
							$_SESSION['mo2f-login-message'] = 'Please login into your account using password.';
							$mobile_login->mo_auth_show_success_message();
							$mobile_login->mo2f_redirectto_wp_login();
						}
					} else{
						$mobile_login->remove_current_activity();
						$_SESSION['mo2f-login-message'] = 'Invalid Username.';
						$mobile_login->mo_auth_show_error_message();
					}
				}	
			}
			
		}
		
		if(isset($_POST['mo2f_trust_device_confirm_nonce'])){ /*register device as rba profile */
			$nonce = $_POST['mo2f_trust_device_confirm_nonce'];
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-trust-device-confirm-nonce' ) ) {
				$this->remove_current_activity();
				$error = new WP_Error();
				$error->add('empty_username', __('<strong>ERROR</strong>: Invalid Request.'));
				return $error;
			} else {
				$this->miniorange_pass2login_start_session();
				try{
					$currentuser = unserialize( $_SESSION[ 'mo2f_current_user' ] );
					mo2f_register_profile(get_user_meta($currentuser->ID,'mo_2factor_map_id_with_email',true),'true',$_SESSION[ 'mo2f_rba_status' ]);
				}catch(Exception $e){
					echo $e->getMessage();
				}
				$redirect_to = isset($_POST[ 'redirect_to' ]) ? $_POST[ 'redirect_to' ] : null;
				$this->mo2fa_pass2login($redirect_to);
			}
		}
		
		if(isset($_POST['mo2f_trust_device_cancel_nonce'])){ /*do not register device as rba profile */
			$nonce = $_POST['mo2f_trust_device_cancel_nonce'];
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-trust-device-cancel-nonce' ) ) {
				$error = new WP_Error();
				$error->add('empty_username', __('<strong>ERROR</strong>: Invalid Request.'));
				return $error;
			} else {
				$this->miniorange_pass2login_start_session();
				$redirect_to = isset($_POST[ 'redirect_to' ]) ? $_POST[ 'redirect_to' ] : null;
				$this->mo2fa_pass2login($redirect_to);
			}
		}
			
		if(isset($_POST['miniorange_challenge_forgotphone_nonce'])){ /*check kba validation*/
			$nonce = $_POST['miniorange_challenge_forgotphone_nonce'];
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-challenge-forgotphone-nonce' ) ) {
				$error = new WP_Error();
				$error->add('empty_username', __('<strong>ERROR</strong>: Invalid Request.'));
				return $error;
			}else {
				$this->miniorange_pass2login_start_session();
				$forgot_phone_enable = get_option('mo2f_enable_forgotphone');
				$forgot_phone_kba_enable = get_option('mo2f_enable_forgotphone_kba');
				$forgot_phone_email_enable = get_option('mo2f_enable_forgotphone_email');
				
				$second_factor = isset($_POST[ 'mo2f_selected_2factor_method' ]) ? $_POST[ 'mo2f_selected_2factor_method' ] : 'KBA';
				$redirect_to = isset($_POST[ 'redirect_to' ]) ? $_POST[ 'redirect_to' ] : null;
				$current_user = unserialize($_SESSION[ 'mo2f_current_user' ]);
				$id = $current_user->ID;
				if($forgot_phone_enable && $forgot_phone_email_enable && $second_factor == 'OTP OVER EMAIL'){
					$customer = new Customer_Setup();
					$content = json_decode($customer->send_otp_token(get_user_meta($id,'mo_2factor_map_id_with_email',true),'EMAIL',get_option('mo2f_customerKey'),get_option('mo2f_api_key')), true);
					
					$mo2fa_login_message = '';
					$mo2f_login_status = '' ;
					
					if(strcasecmp($content['status'], 'SUCCESS') == 0) {
						$_SESSION[ 'mo2f-login-transactionId' ] = $content['txId'];
						$mo2fa_login_message =  'A one time passcode has been sent to <b>' . MO2f_Utility::mo2f_get_hiden_email(get_user_meta($id,'mo_2factor_map_id_with_email',true) ) . '</b>. Please enter the OTP to verify your identity.';
						$mo2f_login_status = 'MO_2_FACTOR_CHALLENGE_OTP_OVER_EMAIL' ;
					}else{
						$mo2fa_login_message = 'Error occured while sending OTP over your regsitered email. Please try again.';
						$mo2f_login_status = 'MO_2_FACTOR_CHALLENGE_KBA_AND_OTP_OVER_EMAIL' ;
					}
					$this->miniorange_pass2login_form_fields($mo2f_login_status, $mo2fa_login_message, $redirect_to);
				}else if($forgot_phone_enable && $forgot_phone_kba_enable){
					if(get_user_meta($id,'mo2f_kba_registration_status',true)){
						$this->mo2f_pass2login_kba_verification($current_user->ID, $redirect_to);
					}else{
						$mo2fa_login_message = 'Your KBA is not configured. Please choose other option to procedd further.';
						$mo2f_login_status = 'MO_2_FACTOR_CHALLENGE_KBA_AND_OTP_OVER_EMAIL' ;
						$this->miniorange_pass2login_form_fields($mo2f_login_status, $mo2fa_login_message, $redirect_to);
					}
				}
			}
		}
		
		if(isset($_POST['miniorange_alternate_login_kba_nonce'])){ /*check kba validation*/
			$nonce = $_POST['miniorange_alternate_login_kba_nonce'];
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-alternate-login-kba-nonce' ) ) {
				$error = new WP_Error();
				$error->add('empty_username', __('<strong>ERROR</strong>: Invalid Request.'));
				return $error;
			}else {
				$this->miniorange_pass2login_start_session();
				$currentuser = isset($_SESSION[ 'mo2f_current_user' ]) ? unserialize( $_SESSION[ 'mo2f_current_user' ] ) : null;
				$redirect_to = isset($_POST[ 'redirect_to' ]) ? $_POST[ 'redirect_to' ] : null;
				$this->mo2f_pass2login_kba_verification($currentuser->ID, $redirect_to);
			}
		}
		
		if(isset($_POST['miniorange_kba_nonce'])){ /*check kba validation*/
			$nonce = $_POST['miniorange_kba_nonce'];
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-kba-nonce' ) ) {
				$error = new WP_Error();
				$error->add('empty_username', __('<strong>ERROR</strong>: Invalid Request.'));
				return $error;
			} else {
				
				$this->miniorange_pass2login_start_session();
				$currentuser = isset($_SESSION[ 'mo2f_current_user' ]) ? unserialize( $_SESSION[ 'mo2f_current_user' ] ): null;
				$redirect_to = isset($_POST[ 'redirect_to' ]) ? $_POST[ 'redirect_to' ] : null;
				
				if(isset($_SESSION[ 'mo2f_current_user' ])){
					if(MO2f_Utility::mo2f_check_empty_or_null($_POST[ 'mo2f_answer_1' ]) || MO2f_Utility::mo2f_check_empty_or_null($_POST[ 'mo2f_answer_2' ])){
						$mo2fa_login_message = 'Please provide both the answers.';
						$mo2fa_login_status = 'MO_2_FACTOR_CHALLENGE_KBA_AUTHENTICATION';
						$this->miniorange_pass2login_form_fields($mo2fa_login_status, $mo2fa_login_message, $redirect_to);
					}
					$otpToken = array();
					$otpToken[0] = $_SESSION['mo_2_factor_kba_questions'][0];
					$otpToken[1] = sanitize_text_field( $_POST[ 'mo2f_answer_1' ] );
					$otpToken[2] = $_SESSION['mo_2_factor_kba_questions'][1];
					$otpToken[3] = sanitize_text_field( $_POST[ 'mo2f_answer_2' ] );
					$check_trust_device = isset($_POST[ 'mo2f_trust_device' ] ) ? $_POST[ 'mo2f_trust_device' ] : 'false';
					
					$kba_validate = new Customer_Setup();
					$kba_validate_response = json_decode($kba_validate->validate_otp_token( 'KBA', null, $_SESSION[ 'mo2f-login-transactionId' ], $otpToken, get_option('mo2f_customerKey'), get_option('mo2f_api_key') ),true);
		
					if(strcasecmp($kba_validate_response['status'], 'SUCCESS') == 0) {
						if(get_option('mo2f_deviceid_enabled') && $check_trust_device == 'on'){
							try{
								mo2f_register_profile(get_user_meta($currentuser->ID,'mo_2factor_map_id_with_email',true),'true',$_SESSION[ 'mo2f_rba_status' ]);
							}catch(Exception $e){
								echo $e->getMessage();
							}
							$this->mo2fa_pass2login($redirect_to);
						}else{
							$this->mo2fa_pass2login($redirect_to);
						}
					}else{
						
						$mo2fa_login_message = 'The answers you have provided are incorrect.';
						$mo2fa_login_status = 'MO_2_FACTOR_CHALLENGE_KBA_AUTHENTICATION';
						$this->miniorange_pass2login_form_fields($mo2fa_login_status, $mo2fa_login_message, $redirect_to);
					}
				}else{
					$this->remove_current_activity();
					return new WP_Error('invalid_username', __('<strong>ERROR</strong>: Please try again..'));
				}
			}
		}
		
		if(isset($_POST['miniorange_mobile_validation_nonce'])){ /*check mobile validation */
			$nonce = $_POST['miniorange_mobile_validation_nonce'];
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-mobile-validation-nonce' ) ) {
				$error = new WP_Error();
				$error->add('empty_username', __('<strong>ERROR</strong>: Invalid Request.'));
				return $error;
			} else {	
				$this->miniorange_pass2login_start_session();
				$currentuser = unserialize( $_SESSION[ 'mo2f_current_user' ] );
				$redirect_to = isset($_POST[ 'redirect_to' ]) ? $_POST[ 'redirect_to' ] : null;
				$checkMobileStatus = new Two_Factor_Setup();
				$content = $checkMobileStatus->check_mobile_status($_SESSION[ 'mo2f-login-transactionId' ]);
				$response = json_decode($content, true);
				if(json_last_error() == JSON_ERROR_NONE) {
					if($response['status'] == 'SUCCESS'){	
						if(get_option('mo2f_deviceid_enabled')){
							$mo2fa_login_status = 'MO_2_FACTOR_REMEMBER_TRUSTED_DEVICE';
							$this->miniorange_pass2login_form_fields($mo2fa_login_status, null, $redirect_to);
						}else{
							$this->mo2fa_pass2login($redirect_to);
						}
					}else{
						$this->remove_current_activity();
						return new WP_Error('invalid_username', __('<strong>ERROR</strong>: Please try again.'));
					}
				}else{
					$this->remove_current_activity();
					return new WP_Error('invalid_username', __('<strong>ERROR</strong>: Please try again.'));
				}
			}
		}
		
		if (isset($_POST['miniorange_mobile_validation_failed_nonce'])){ /*Back to miniOrange Login Page if mobile validation failed and from back button of mobile challenge, soft token and default login*/
			$nonce = $_POST['miniorange_mobile_validation_failed_nonce'];
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-mobile-validation-failed-nonce' ) ) {
				$error = new WP_Error();
				$error->add('empty_username', __('<strong>ERROR</strong>: Invalid Request.'));
				return $error;
			} else {
				$this->miniorange_pass2login_start_session();
				$this->remove_current_activity();
			}
		}
		
		if(isset($_POST['miniorange_forgotphone'])){ /*Click on the link of forgotphone */
			$nonce = $_POST['miniorange_forgotphone'];
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-forgotphone' ) ) {
				$error = new WP_Error();
				$error->add('empty_username', __('<strong>ERROR</strong>: Invalid Request.'));
				return $error;
			} else{
				$mo2fa_login_status = isset($_POST['request_origin_method']) ? $_POST['request_origin_method'] : null;
				$redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : null;
				$mo2fa_login_message = '';
				
				$this->miniorange_pass2login_start_session();

				$customer = new Customer_Setup();
				$current_user = unserialize( $_SESSION[ 'mo2f_current_user' ] );
				$id = $current_user->ID;
				if(get_user_meta($id,'mo2f_kba_registration_status',true)){
					$mo2fa_login_status = 'MO_2_FACTOR_CHALLENGE_KBA_AND_OTP_OVER_EMAIL';
					$pass2fa_login = new Miniorange_Password_2Factor_Login();
					$pass2fa_login->mo2f_pass2login_kba_verification($id, $redirect_to);
				}else{
					$content = json_decode($customer->send_otp_token(get_user_meta($id,'mo_2factor_map_id_with_email',true),'EMAIL',get_option('mo2f_customerKey'),get_option('mo2f_api_key')), true);
					if(strcasecmp($content['status'], 'SUCCESS') == 0) {
						unset($_SESSION[ 'mo2f-login-qrCode' ]);
						unset($_SESSION[ 'mo2f-login-transactionId' ]);
						$_SESSION['mo2f-login-message'] =  'A one time passcode has been sent to <b>' . MO2f_Utility::mo2f_get_hiden_email(get_user_meta($id,'mo_2factor_map_id_with_email',true) ) . '</b>. Please enter the OTP to verify your identity.';
						$_SESSION[ 'mo2f-login-transactionId' ] = $content['txId'];
						$mo2fa_login_message =  'A one time passcode has been sent to <b>' . MO2f_Utility::mo2f_get_hiden_email(get_user_meta($id,'mo_2factor_map_id_with_email',true) ) . '</b>. Please enter the OTP to verify your identity.';
						$mo2fa_login_status = 'MO_2_FACTOR_CHALLENGE_OTP_OVER_EMAIL';
					}else{
						$mo2fa_login_message = 'Error occurred while sending OTP over email. Please try again.';
					}
					$this->miniorange_pass2login_form_fields($mo2fa_login_status, $mo2fa_login_message, $redirect_to);
				}
				$pass2fa_login = new Miniorange_Password_2Factor_Login();

				$pass2fa_login->miniorange_pass2login_form_fields($mo2fa_login_status, $mo2fa_login_message, $redirect_to);
			}
		} 
		
		if(isset($_POST['miniorange_softtoken'])){ /*Click on the link of phone is offline */
			$nonce = $_POST['miniorange_softtoken'];
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-softtoken' ) ) {
				$error = new WP_Error();
				$error->add('empty_username', __('<strong>ERROR</strong>: Invalid Request.'));
				return $error;
			} else{
				$this->miniorange_pass2login_start_session();
				unset($_SESSION[ 'mo2f-login-qrCode' ]);
				unset($_SESSION[ 'mo2f-login-transactionId' ]);
				$redirect_to = isset($_POST[ 'redirect_to' ]) ? $_POST[ 'redirect_to' ] : null;
				$mo2fa_login_message = 'Please enter the one time passcode shown in the miniOrange<b> Authenticator</b> app.';
				$mo2fa_login_status = 'MO_2_FACTOR_CHALLENGE_SOFT_TOKEN';
				$this->miniorange_pass2login_form_fields($mo2fa_login_status, $mo2fa_login_message, $redirect_to);
			}
		}
		
		if (isset($_POST['miniorange_soft_token_nonce'])){ /*Validate Soft Token,OTP over SMS,OTP over EMAIL,Phone verification */
			$nonce = $_POST['miniorange_soft_token_nonce'];
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-soft-token-nonce' ) ) {
				$error = new WP_Error();
				$error->add('empty_username', __('<strong>ERROR</strong>: Invalid Request.'));
				return $error;
			} else {
				$this->miniorange_pass2login_start_session();
				$mo2fa_login_status = isset($_POST['request_origin_method']) ? $_POST['request_origin_method'] : null;
				$redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : null;
				$softtoken = '';
				if( MO2f_utility::mo2f_check_empty_or_null( $_POST[ 'mo2fa_softtoken' ] ) ) {
					$mo2fa_login_message = 'Please enter OTP to proceed.';
					$this->miniorange_pass2login_form_fields($mo2fa_login_status, $mo2fa_login_message, $redirect_to);
				} else{
					$softtoken = sanitize_text_field( $_POST[ 'mo2fa_softtoken' ] );
					if(!MO2f_utility::mo2f_check_number_length($softtoken)){
						$mo2fa_login_message = 'Invalid OTP. Only digits within range 4-8 are allowed. Please try again.';
						$this->miniorange_pass2login_form_fields($mo2fa_login_status, $mo2fa_login_message, $redirect_to);
					}
				}
				$currentuser = isset($_SESSION[ 'mo2f_current_user' ]) ? unserialize( $_SESSION[ 'mo2f_current_user' ] ) : null;
				if(isset($_SESSION[ 'mo2f_current_user' ])){
					$customer = new Customer_Setup();
					$content ='';
					if(isset($mo2fa_login_status) && $mo2fa_login_status == 'MO_2_FACTOR_CHALLENGE_OTP_OVER_EMAIL'){
						$content = json_decode($customer->validate_otp_token( 'EMAIL', null, $_SESSION[ 'mo2f-login-transactionId' ], $softtoken, get_option('mo2f_customerKey'), get_option('mo2f_api_key') ),true);
					}else if(isset($mo2fa_login_status) && $mo2fa_login_status == 'MO_2_FACTOR_CHALLENGE_OTP_OVER_SMS'){
						$content = json_decode($customer->validate_otp_token( 'SMS', null, $_SESSION[ 'mo2f-login-transactionId' ], $softtoken, get_option('mo2f_customerKey'), get_option('mo2f_api_key') ),true);
					}else if(isset($mo2fa_login_status) && $mo2fa_login_status == 'MO_2_FACTOR_CHALLENGE_PHONE_VERIFICATION'){
						$content = json_decode($customer->validate_otp_token( 'PHONE VERIFICATION', null, $_SESSION[ 'mo2f-login-transactionId' ], $softtoken, get_option('mo2f_customerKey'), get_option('mo2f_api_key') ),true);
					}else if(isset($mo2fa_login_status) && $mo2fa_login_status == 'MO_2_FACTOR_CHALLENGE_SOFT_TOKEN'){
						$content = json_decode($customer->validate_otp_token( 'SOFT TOKEN', get_user_meta($currentuser->ID,'mo_2factor_map_id_with_email',true), null, $softtoken, get_option('mo2f_customerKey'), get_option('mo2f_api_key')),true);
					}else if(isset($mo2fa_login_status) && $mo2fa_login_status == 'MO_2_FACTOR_CHALLENGE_GOOGLE_AUTHENTICATION'){
						$content = json_decode($customer->validate_otp_token( 'GOOGLE AUTHENTICATOR', get_user_meta($currentuser->ID,'mo_2factor_map_id_with_email',true), null, $softtoken, get_option('mo2f_customerKey'), get_option('mo2f_api_key')),true);
					}else{
						$this->remove_current_activity();
						return new WP_Error('invalid_username', __('<strong>ERROR</strong>: Invalid Request. Please try again.'));
					}
					
					
					
					if(strcasecmp($content['status'], 'SUCCESS') == 0) {
						if(get_option('mo2f_deviceid_enabled')){
							$mo2fa_login_status = 'MO_2_FACTOR_REMEMBER_TRUSTED_DEVICE';
							$this->miniorange_pass2login_form_fields($mo2fa_login_status, null, $redirect_to);
						}else{
							$this->mo2fa_pass2login($redirect_to);
						}
					}else{
						
						$message = $mo2fa_login_status == 'MO_2_FACTOR_CHALLENGE_SOFT_TOKEN' ? 'You have entered an invalid OTP.<br>Please click on <b>Sync Time</b> in the miniOrange Authenticator app to sync your phone time with the miniOrange servers and try again.' : 'Invalid OTP. Please try again.';
						$this->miniorange_pass2login_form_fields($mo2fa_login_status, $message, $redirect_to);
					}
					
				}else{
					$this->remove_current_activity();
					return new WP_Error('invalid_username', __('<strong>ERROR</strong>: Please try again..'));
				}
			}
		}
		
		if (isset($_POST['miniorange_inline_skip_registration_nonce'])){ /*Validate Soft Token,OTP over SMS,OTP over EMAIL,Phone verification */
			$nonce = $_POST['miniorange_inline_skip_registration_nonce'];
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-inline-skip-registration-nonce' ) ) {
				$error = new WP_Error();
				$error->add('empty_username', __('<strong>ERROR</strong>: Invalid Request.'));
				return $error;
			} else {
				$this->miniorange_pass2login_start_session();
				$current_user = unserialize( $_SESSION[ 'mo2f_current_user' ] );
				delete_user_meta($current_user->ID,'mo2f_selected_2factor_method');
				$this->mo2fa_pass2login();
			}
		}
		
		if (isset($_POST['miniorange_attribute_collection_nonce'])){ /*Handling Rba Attributes from other plugins */
			$nonce = $_POST['miniorange_attribute_collection_nonce'];
			if ( ! wp_verify_nonce( $nonce, 'miniorange-2-factor-login-attribute-collection-nonce' ) ) {
				$error = new WP_Error();
				$error->add('empty_username', __('<strong>ERROR</strong>: Invalid Request.'));
				return $error;
			} else {
				$this->miniorange_pass2login_start_session();
				$current_user = unserialize( $_SESSION[ 'mo2f_current_user' ] );
				$attributes = isset($_POST[ 'miniorange_rba_attribures' ]) ? $_POST[ 'miniorange_rba_attribures' ] : null;
				$redirect_to = isset($_POST[ 'redirect_to' ]) ? $_POST[ 'redirect_to' ] : null;
				$this->miniorange_initiate_2nd_factor($current_user, $attributes, $redirect_to);
			}
		}
	}
	
	
	
	function mo2f_collect_device_attributes_for_authenticated_user($currentuser, $redirect_to = null){
		if(get_option('mo2f_deviceid_enabled')){
			$this->miniorange_pass2login_start_session();
			$_SESSION[ 'mo2f_current_user' ] = serialize( $currentuser );
			mo2f_collect_device_attributes_handler($redirect_to);
			exit;
		}else {
			$this->miniorange_initiate_2nd_factor($currentuser, null, $redirect_to);
		}
	}
	
	function mo2f_check_username_password($user, $username, $password, $redirect_to=null){
		if(is_a($user, 'WP_Error') && !empty($user)){
			return $user;
		}
		
		// if an app password is enabled, this is an XMLRPC / APP login ?
		if ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST && get_option('mo2f_enable_xmlrpc')){
			
			/* App Specific Password
			// Get information on user
			if ( get_user_by( 'email', $username ) === false ) {
				$user = get_user_by( 'login', $username );
			} else {
				$user = get_user_by( 'email', $username );
			}
			
			// checking if password was generated by user
			if(!get_user_option( 'mo2f_app_password', $user->ID )){
				$this->error = new IXR_Error(403, __('Bad login/pass combination.'));
				return false;
			}
			
			// Verifying the passwords
			$app_password   =   get_user_option( 'mo2f_app_password', $user->ID );
			$usersha1	=   sha1($password );
			
			// if passwords match
			if ( $app_password == $usersha1 ) { 
				return new WP_User( $user->ID );	  
			} else {
				// Wrong XMLRPC/APP password !
				$this->error = new IXR_Error(403, __('Bad login/pass combination.'));
				return false;
			}*/		

			$currentuser = wp_authenticate_username_password($user, $username, $password);
			if (is_wp_error($currentuser)) {
				$this->error = new IXR_Error(403, __('Bad login/pass combination.'));
				return false;
			}else{
				return $currentuser;
			}
		}else{
			$currentuser = wp_authenticate_username_password($user, $username, $password);
			if (is_wp_error($currentuser)) {
				$currentuser->add('invalid_username_password', __('<strong>ERROR</strong>: Invalid Username or password.'));
				return $currentuser;
			}else{
				
				$attributes = isset($_POST[ 'miniorange_rba_attribures' ]) ? $_POST[ 'miniorange_rba_attribures' ] : null;
				$redirect_to = isset($_REQUEST[ 'redirect_to' ]) ? $_REQUEST[ 'redirect_to' ] : null;
				$this->miniorange_initiate_2nd_factor($currentuser, $attributes, $redirect_to);
			}
		}
	}
	
	function miniorange_initiate_2nd_factor($currentuser, $attributes=null, $redirect_to=null){
		
		
		$this->miniorange_pass2login_start_session();
		$_SESSION[ 'mo2f_current_user' ] = serialize( $currentuser );
		$_SESSION[ 'mo2f_1stfactor_status' ] = 'VALIDATE_SUCCESS';
				
		$current_roles = miniorange_get_user_role($currentuser);
		$enabled = miniorange_check_if_2fa_enabled_for_roles($current_roles);
				
		if($enabled){
			$email = get_user_meta($currentuser->ID,'mo_2factor_map_id_with_email',true);
			if( $email && get_user_meta($currentuser->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){ //checking if user has configured any 2nd factor method
				try{
					$mo2f_rba_status = mo2f_collect_attributes($email,stripslashes($attributes)); // Rba flow
					$_SESSION[ 'mo2f_rba_status' ] = $mo2f_rba_status;
				}catch(Exception $e){
					echo $e->getMessage();
				}
				
				if($mo2f_rba_status['status'] == 'SUCCESS' && $mo2f_rba_status['decision_flag']){
					$this->mo2fa_pass2login($redirect_to);
				}else{
					if(MO2f_Utility::check_if_request_is_from_mobile_device($_SERVER['HTTP_USER_AGENT']) && get_user_meta($currentuser->ID,'mo2f_kba_registration_status',true) && get_option('mo2f_enable_mobile_support')){
						$this->mo2f_pass2login_kba_verification($currentuser->ID, $redirect_to);
					}else{
						$mo2f_second_factor = mo2f_get_user_2ndfactor($currentuser);
						if($mo2f_second_factor == 'MOBILE AUTHENTICATION'){
							$this->mo2f_pass2login_mobile_verification($currentuser, $redirect_to);
						}else if($mo2f_second_factor == 'PUSH NOTIFICATIONS' || $mo2f_second_factor == 'OUT OF BAND EMAIL'){
							$this->mo2f_pass2login_push_oobemail_verification($currentuser,$mo2f_second_factor, $redirect_to);
						}else if($mo2f_second_factor == 'SOFT TOKEN' || $mo2f_second_factor == 'SMS' || $mo2f_second_factor == 'PHONE VERIFICATION' || $mo2f_second_factor == 'GOOGLE AUTHENTICATOR' ){
							$this->mo2f_pass2login_otp_verification($currentuser,$mo2f_second_factor, $redirect_to);
						}else if($mo2f_second_factor == 'KBA'){
								$this->mo2f_pass2login_kba_verification($currentuser->ID, $redirect_to);
						}else{
							$this->remove_current_activity();
							$error = new WP_Error();
							$error->add('empty_username', __('<strong>ERROR</strong>: Please try again or contact your admin.'));
							return $error;
						}
					}
				}
			} else {
				$this->mo2fa_pass2login($redirect_to);
			}
			
		}else{ //plugin is not activated for current role then logged him in without asking 2 factor
			$this->mo2fa_pass2login($redirect_to);
		}
		
	}
	
	
	function mo_2_factor_enable_jquery_default_login(){
		wp_enqueue_script('jquery');
	}
	

	
	function miniorange_pass2login_form_fields($mo2fa_login_status=null, $mo2fa_login_message=null, $redirect_to=null){
	
		$login_status = $mo2fa_login_status;
		$login_message = $mo2fa_login_message;
		$current_user = isset($_SESSION[ 'mo2f_current_user' ]) ? unserialize( $_SESSION[ 'mo2f_current_user' ] ) : null;
		$current_user_id = is_null($current_user) ? null : $current_user->ID;
		if($this->miniorange_pass2login_check_mobile_status($login_status)){ //for mobile
			mo2f_getqrcode($login_status, $login_message, $redirect_to);
			exit;
		}else if($this->miniorange_pass2login_check_otp_status($login_status)){ //for soft-token,otp over email,sms,phone verification,google auth
			mo2f_getotp_form($login_status, $login_message, $redirect_to);
			exit;
		}else if($this->miniorange_pass2login_check_forgotphone_status($login_status)){ // forgot phone page if both KBA and Email are configured.
			mo2f_get_forgotphone_form($login_status, $login_message, $redirect_to);
			exit;
		}else if($this->miniorange_pass2login_check_push_oobemail_status($login_status)){ //for push and out of band email.
			mo2f_getpush_oobemail_response($current_user_id, $login_status, $login_message, $redirect_to);
			exit;
		}else if($this->miniorange_pass2login_check_kba_status($login_status)){ // for Kba 
			mo2f_getkba_form($login_status, $login_message, $redirect_to);
			exit;
		}else if($this->miniorange_pass2login_check_trusted_device_status($login_status)){ // trusted device
			mo2f_get_device_form($login_status, $login_message, $redirect_to);
			exit;
		}else{ //show login screen
			$this->mo_2_factor_pass2login_show_wp_login_form();
		}
	}
	
	function miniorange_pass2login_check_forgotphone_status($login_status){  // after clicking on forgotphone link when both kba and email are configured
		if($login_status == 'MO_2_FACTOR_CHALLENGE_KBA_AND_OTP_OVER_EMAIL'){
			return true;
		}
		return false;
	}
	
	function miniorange_pass2login_check_trusted_device_status($login_status){
		
		if($login_status == 'MO_2_FACTOR_REMEMBER_TRUSTED_DEVICE'){
			return true;
		}
		return false;
	}
	
	function miniorange_pass2login_check_push_oobemail_status($login_status){  // for push and out of and email
		if($login_status == 'MO_2_FACTOR_CHALLENGE_PUSH_NOTIFICATIONS' || $login_status == 'MO_2_FACTOR_CHALLENGE_OOB_EMAIL'){
			return true;
		}
		return false;
	}
	
	function miniorange_pass2login_check_otp_status($login_status,$sso=false){
		if($login_status == 'MO_2_FACTOR_CHALLENGE_SOFT_TOKEN' || $login_status == 'MO_2_FACTOR_CHALLENGE_OTP_OVER_EMAIL' || $login_status == 'MO_2_FACTOR_CHALLENGE_OTP_OVER_SMS' || $login_status == 'MO_2_FACTOR_CHALLENGE_PHONE_VERIFICATION' || $login_status == 'MO_2_FACTOR_CHALLENGE_GOOGLE_AUTHENTICATION'){
			return true;
		}
		return false;
	}
	
	function miniorange_pass2login_check_mobile_status($login_status){    //mobile authentication
		if($login_status == 'MO_2_FACTOR_CHALLENGE_MOBILE_AUTHENTICATION'){
			return true;
		}
		return false;
	}
	
	function miniorange_pass2login_check_kba_status($login_status){
		if($login_status == 'MO_2_FACTOR_CHALLENGE_KBA_AUTHENTICATION'){
			return true;
		}
		return false;
	}
	
	function miniorange_pass2login_footer_form(){
	?>
		<script>
			jQuery(document).ready(function(){
				if(document.getElementById('loginform') != null){
					 jQuery('#loginform').on('submit', function(e){
						jQuery('#miniorange_rba_attribures').val(JSON.stringify(rbaAttributes.attributes));
					});
				}else{
					if(document.getElementsByClassName('login') != null){
						jQuery('.login').on('submit', function(e){
							jQuery('#miniorange_rba_attribures').val(JSON.stringify(rbaAttributes.attributes));
						});
					}
				}
			});
		</script>
	<?php
			
	}
	
	function mo2f_pass2login_otp_verification($user,$mo2f_second_factor, $redirect_to){
		if($mo2f_second_factor == 'SOFT TOKEN'){
			$mo2fa_login_message = 'Please enter the one time passcode shown in the miniOrange<b> Authenticator</b> app.';
			$mo2fa_login_status = 'MO_2_FACTOR_CHALLENGE_SOFT_TOKEN';
			$this->miniorange_pass2login_form_fields($mo2fa_login_status, $mo2fa_login_message, $redirect_to);
		}else if($mo2f_second_factor == 'GOOGLE AUTHENTICATOR'){
			$mo2fa_login_message = get_user_meta($user->ID,'mo2f_external_app_type',true) == 'AUTHY 2-FACTOR AUTHENTICATION' ? 'Please enter the one time passcode shown in the <b>Authy 2-Factor Authentication</b> app.' : 'Please enter the one time passcode shown in the <b>Google Authenticator</b> app.';
			$mo2fa_login_status = 'MO_2_FACTOR_CHALLENGE_GOOGLE_AUTHENTICATION';
			$this->miniorange_pass2login_form_fields($mo2fa_login_status, $mo2fa_login_message, $redirect_to);
		}else{
			$challengeMobile = new Customer_Setup();
			$content = $challengeMobile->send_otp_token(get_user_meta($user->ID,'mo_2factor_map_id_with_email',true), $mo2f_second_factor,get_option('mo2f_customerKey'),get_option('mo2f_api_key'));
			$response = json_decode($content, true);
			if(json_last_error() == JSON_ERROR_NONE) {
				if($response['status'] == 'SUCCESS'){
					$message = $mo2f_second_factor == 'SMS' ? 'The OTP has been sent to '. MO2f_Utility::get_hidden_phone($response['phoneDelivery']['contact']) . '. Please enter the OTP you received to Validate.' : 'You will receive phone call on ' . MO2f_Utility::get_hidden_phone($response['phoneDelivery']['contact']) . ' with OTP. Please enter the OTP to Validate.';
					update_option( 'mo2f_number_of_transactions', get_option('mo2f_number_of_transactions')-1);
					$_SESSION[ 'mo2f-login-transactionId' ] = $response[ 'txId' ];
					
					$mo2fa_login_message = $message;
					$mo2fa_login_status = $mo2f_second_factor == 'SMS' ? 'MO_2_FACTOR_CHALLENGE_OTP_OVER_SMS' : 'MO_2_FACTOR_CHALLENGE_PHONE_VERIFICATION';
					$this->miniorange_pass2login_form_fields($mo2fa_login_status, $mo2fa_login_message, $redirect_to);
				}else{
					$message = $mo2f_second_factor == 'SMS' ? $response['message'] . ' You can click on <b>Forgot your phone</b> link to login via alternate method.' : 'We are unable to send the OTP via phone call on your registered phone. You can click on <b>Forgot your phone</b> link to receive OTP to your registered email.';
					
					$_SESSION[ 'mo2f-login-transactionId' ] = $response[ 'txId' ];
					
					$mo2fa_login_message = $message;
					$mo2fa_login_status = $mo2f_second_factor == 'SMS' ? 'MO_2_FACTOR_CHALLENGE_OTP_OVER_SMS' : 'MO_2_FACTOR_CHALLENGE_PHONE_VERIFICATION';
					$this->miniorange_pass2login_form_fields($mo2fa_login_status, $mo2fa_login_message, $redirect_to);
				}
			}else{
				$this->remove_current_activity();
				$error = new WP_Error();
				$error->add('empty_username', __('<strong>ERROR</strong>: An error occured while processing your request. Please Try again.'));
				return $error;
			}
		}
	}
	
	function mo2f_pass2login_push_oobemail_verification($user,$mo2f_second_factor, $redirect_to){
		$challengeMobile = new Customer_Setup();
		$content = $challengeMobile->send_otp_token(get_user_meta($user->ID,'mo_2factor_map_id_with_email',true),$mo2f_second_factor ,get_option('mo2f_customerKey'),get_option('mo2f_api_key'));
		$response = json_decode($content, true);
		if(json_last_error() == JSON_ERROR_NONE) { /* Generate Qr code */
			if($response['status'] == 'SUCCESS'){
				$_SESSION[ 'mo2f-login-transactionId' ] = $response['txId'];
				
				$mo2fa_login_message = $mo2f_second_factor == 'PUSH NOTIFICATIONS' ? 'A Push Notification has been sent to your phone. We are waiting for your approval.' : 'An email has been sent to ' . MO2f_Utility::mo2f_get_hiden_email(get_user_meta($user->ID,'mo_2factor_map_id_with_email',true)) . '. We are waiting for your approval.';
				$mo2fa_login_status = $mo2f_second_factor == 'PUSH NOTIFICATIONS' ? 'MO_2_FACTOR_CHALLENGE_PUSH_NOTIFICATIONS' : 'MO_2_FACTOR_CHALLENGE_OOB_EMAIL';
				$this->miniorange_pass2login_form_fields($mo2fa_login_status, $mo2fa_login_message, $redirect_to);
			}else if($response['status'] == 'ERROR' || $response['status'] == 'FAILED' ){
				$_SESSION[ 'mo2f-login-transactionId' ] = $response['txId'];
				
				$mo2fa_login_message = $mo2f_second_factor == 'PUSH NOTIFICATIONS' ? 'An error occured while sending push notification to your app. You can click on <b>Phone is Offline</b> button to enter soft token from app or <b>Forgot your phone</b> button to receive OTP to your registered email.' : 'An error occured while sending email. Please try again.';
				$mo2fa_login_status = $mo2f_second_factor == 'PUSH NOTIFICATIONS' ? 'MO_2_FACTOR_CHALLENGE_PUSH_NOTIFICATIONS' : 'MO_2_FACTOR_CHALLENGE_OOB_EMAIL';
				$this->miniorange_pass2login_form_fields($mo2fa_login_status, $mo2fa_login_message, $redirect_to);
			}
		}else{
			$this->remove_current_activity();
			$error = new WP_Error();
			$error->add('empty_username', __('<strong>ERROR</strong>: An error occured while processing your request. Please Try again.'));
			return $error;
		}
	}
	
	function mo2f_pass2login_kba_verification($user_id, $redirect_to){
		$challengeKba = new Customer_Setup();
			$content = $challengeKba->send_otp_token(get_user_meta($user_id,'mo_2factor_map_id_with_email',true), 'KBA',get_option('mo2f_customerKey'),get_option('mo2f_api_key'));
			$response = json_decode($content, true);
			if(json_last_error() == JSON_ERROR_NONE) { /* Generate Qr code */
				if($response['status'] == 'SUCCESS'){
					$_SESSION[ 'mo2f-login-transactionId' ] = $response['txId'];
					$questions = array();
					$questions[0] = $response['questions'][0]['question'];
					$questions[1] = $response['questions'][1]['question'];
					$_SESSION[ 'mo_2_factor_kba_questions' ] = $questions;
					
					$mo2fa_login_message = 'Please answer the following questions:';
					$mo2fa_login_status = 'MO_2_FACTOR_CHALLENGE_KBA_AUTHENTICATION';
					$this->miniorange_pass2login_form_fields($mo2fa_login_status, $mo2fa_login_message, $redirect_to);
				}else if($response['status'] == 'ERROR'){
					$this->remove_current_activity();
					$error = new WP_Error();
					$error->add('empty_username', __('<strong>ERROR</strong>: An error occured while processing your request. Please Try again.'));
					return $error;
				}
			}else{
				$this->remove_current_activity();
				$error = new WP_Error();
				$error->add('empty_username', __('<strong>ERROR</strong>: An error occured while processing your request. Please Try again.'));
				return $error;
			}
	}
	
	function mo2f_pass2login_mobile_verification($user, $redirect_to){
		$useragent = $_SERVER['HTTP_USER_AGENT'];
		if(MO2f_Utility::check_if_request_is_from_mobile_device($useragent)){
			unset($_SESSION[ 'mo2f-login-qrCode' ]);
			unset($_SESSION[ 'mo2f-login-transactionId' ]);
			
			$mo2fa_login_message = 'Please enter the one time passcode shown in the miniOrange<b> Authenticator</b> app.';
			$mo2fa_login_status = 'MO_2_FACTOR_CHALLENGE_SOFT_TOKEN';
			$this->miniorange_pass2login_form_fields($mo2fa_login_status, $mo2fa_login_message, $redirect_to);
		}else{
			$challengeMobile = new Customer_Setup();
			$content = $challengeMobile->send_otp_token(get_user_meta($user->ID,'mo_2factor_map_id_with_email',true), 'MOBILE AUTHENTICATION',get_option('mo2f_customerKey'),get_option('mo2f_api_key'));
			$response = json_decode($content, true);
			if(json_last_error() == JSON_ERROR_NONE) { /* Generate Qr code */
				if($response['status'] == 'SUCCESS'){
					$_SESSION[ 'mo2f-login-qrCode' ] = $response['qrCode'];
					$_SESSION[ 'mo2f-login-transactionId' ] = $response['txId'];
					
					$mo2fa_login_message = '';
					$mo2fa_login_status = 'MO_2_FACTOR_CHALLENGE_MOBILE_AUTHENTICATION';
					$this->miniorange_pass2login_form_fields($mo2fa_login_status, $mo2fa_login_message, $redirect_to);
				}else if($response['status'] == 'ERROR'){
					$this->remove_current_activity();
					$error = new WP_Error();
					$error->add('empty_username', __('<strong>ERROR</strong>: An error occured while processing your request. Please Try again.'));
					return $error;
				}
			}else{
				$this->remove_current_activity();
				$error = new WP_Error();
				$error->add('empty_username', __('<strong>ERROR</strong>: An error occured while processing your request. Please Try again.'));
				return $error;
			}
		}
		
	}
	
	function mo_2_factor_pass2login_show_wp_login_form(){
	?>
		<p><input type="hidden" name="miniorange_login_nonce" value="<?php echo wp_create_nonce('miniorange-2-factor-login-nonce'); ?>" />
		   <a href="http://miniorange.com/cloud-identity-broker-service" style="display:none;"></a>
		   <a href="http://miniorange.com/strong_auth" style="display:none;"></a>
		   <a href="http://miniorange.com/single-sign-on-sso" style="display:none;"></a>
		   <a href="http://miniorange.com/fraud" style="display:none;"></a>
		</p>
		
		<?php 
			if(get_option('mo2f_deviceid_enabled')){
		?>
				<p><input type="hidden" id="miniorange_rba_attribures" name="miniorange_rba_attribures" value="" /></p>
		<?php
				wp_enqueue_script( 'jquery_script', plugins_url('includes/js/rba/js/jquery-1.9.1.js', __FILE__ ));
				wp_enqueue_script( 'flash_script', plugins_url('includes/js/rba/js/jquery.flash.js', __FILE__ ));
				wp_enqueue_script( 'uaparser_script', plugins_url('includes/js/rba/js/ua-parser.js', __FILE__ ));
				wp_enqueue_script( 'client_script', plugins_url('includes/js/rba/js/client.js', __FILE__ ));
				wp_enqueue_script( 'device_script', plugins_url('includes/js/rba/js/device_attributes.js', __FILE__ ));
				wp_enqueue_script( 'swf_script', plugins_url('includes/js/rba/js/swfobject.js', __FILE__ ));
				wp_enqueue_script( 'font_script', plugins_url('includes/js/rba/js/fontdetect.js', __FILE__ ));
				wp_enqueue_script( 'murmur_script', plugins_url('includes/js/rba/js/murmurhash3.js', __FILE__ ));
				wp_enqueue_script( 'miniorange_script', plugins_url('includes/js/rba/js/miniorange-fp.js', __FILE__ ));
			}
	}
}
?>