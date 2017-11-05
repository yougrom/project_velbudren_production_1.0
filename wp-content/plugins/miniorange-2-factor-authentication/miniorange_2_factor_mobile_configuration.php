<?php

	function mo2f_check_if_registered_with_miniorange($current_user){
		if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR'){ 
				?>
				<br />
				<div style="display:block;color:red;background-color:rgba(251, 232, 0, 0.15);padding:5px;border:solid 1px rgba(255, 0, 9, 0.36);">Please <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mobile_configure">click here</a> to setup Two-Factor.</div>
	<?php	
		}else if(!(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_INITIALIZE_MOBILE_REGISTRATION' || get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS')) { ?>
			<br/><div style="display:block;color:red;background-color:rgba(251, 232, 0, 0.15);padding:5px;border:solid 1px rgba(255, 0, 9, 0.36);">Please <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=2factor_setup">Register with miniOrange</a> to configure miniOrange 2 Factor plugin.</div>
	<?php } 
	}
	
	function mo2f_update_and_sync_user_two_factor($user_id, $userinfo){
		
		$mo2f_second_factor = isset($userinfo['authType']) && !empty($userinfo['authType']) ? $userinfo['authType'] : 'NONE';
		
		if($mo2f_second_factor == 'OUT OF BAND EMAIL'){
			update_user_meta($user_id,'mo2f_email_verification_status',true);
		}else if ($mo2f_second_factor == 'SMS'){
			$phone_num = $userinfo['phone'];
			$_SESSION['mo2f_phone'] = $phone_num;
			update_user_meta($user_id,'mo2f_otp_registration_status',true);
		}else if($mo2f_second_factor == 'PHONE VERIFICATION'){
			$phone_num = $userinfo['phone'];
			$_SESSION['mo2f_phone'] = $phone_num;
			update_user_meta($user_id,'mo2f_otp_registration_status',true);
		}else if ($mo2f_second_factor == 'SOFT TOKEN'){
			update_user_meta($user_id,'mo2f_mobile_registration_status',true);
		}else if ($mo2f_second_factor == 'MOBILE AUTHENTICATION'){
			update_user_meta($user_id,'mo2f_mobile_registration_status',true);
		}else if ($mo2f_second_factor == 'PUSH NOTIFICATIONS'){
			update_user_meta($user_id,'mo2f_mobile_registration_status',true);
		}else if ($mo2f_second_factor == 'KBA'){
			update_user_meta($user_id,'mo2f_kba_registration_status',true);
		}else if($mo2f_second_factor == 'GOOGLE AUTHENTICATOR'){
			$app_type = get_user_meta($user_id,'mo2f_external_app_type',true);
			if($app_type == 'GOOGLE AUTHENTICATOR'){
				update_user_meta($user_id,'mo2f_external_app_type','GOOGLE AUTHENTICATOR');
				update_user_meta($user_id,'mo2f_google_authentication_status',true);
			}else if($app_type == 'AUTHY 2-FACTOR AUTHENTICATION'){
				update_user_meta($user_id,'mo2f_external_app_type','AUTHY 2-FACTOR AUTHENTICATION');
				update_user_meta($user_id,'mo2f_authy_authentication_status',true);
			}else{
				update_user_meta($user_id,'mo2f_external_app_type','GOOGLE AUTHENTICATOR');
				update_user_meta($user_id,'mo2f_google_authentication_status',true);
			}
		}
		return $mo2f_second_factor;
	}
	
	function mo2f_get_activated_second_factor($current_user){
		if(get_user_meta($current_user->ID,'mo_2factor_mobile_registration_status',true) == 'MO_2_FACTOR_SUCCESS'){ 
			//checking this option for existing users
			update_user_meta($current_user->ID,'mo2f_mobile_registration_status',true);
			$mo2f_second_factor = 'MOBILE AUTHENTICATION';
			return $mo2f_second_factor;
		}else if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR' ){
			return 'NONE';
		}else{
			//for new users
			if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS' && get_user_meta($current_user->ID,'mo_2factor_user_registration_with_miniorange',true) == 'SUCCESS'){
				$enduser = new Two_Factor_Setup();
				$userinfo = json_decode($enduser->mo2f_get_userinfo(get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true)),true);
				if(json_last_error() == JSON_ERROR_NONE){
					if($userinfo['status'] == 'ERROR'){
						update_option( 'mo2f_message', $userinfo['message']);
						$mo2f_second_factor = 'NONE';
					}else if($userinfo['status'] == 'SUCCESS'){
						$mo2f_second_factor = mo2f_update_and_sync_user_two_factor($current_user->ID, $userinfo);
					}else if($userinfo['status'] == 'FAILED'){
						$mo2f_second_factor = 'NONE';
						update_option( 'mo2f_message','Your account has been removed.Please contact your administrator.');
					}else{
						$mo2f_second_factor = 'NONE';
					}
				}else{
					update_option( 'mo2f_message','Invalid Request. Please try again.');
					$mo2f_second_factor = 'NONE';
				}
			}else{
				$mo2f_second_factor = 'NONE';
			}
			
			return $mo2f_second_factor;
		} 
	}
	
	function mo_2factor_is_curl_installed() {
		if  (in_array  ('curl', get_loaded_extensions())) {
			return 1;
		} else
			return 0;
	}
	
	function show_user_welcome_page($current_user){
	?>
		<form name="f" method="post" action="">
			<div class="mo2f_table_layout">
				<div><center><p style="font-size:17px;">A new security system has been enabled to better protect your account. Please configure your Two-Factor Authentication method by setting up your account.</p></center></div>
				<div id="panel1">
					<table class="mo2f_settings_table">
						
						<tr>
							<td><center><div class="alert-box"><input type="email" autofocus="true" name="mo_useremail" style="width:48%;text-align: center;height: 40px;font-size:18px;border-radius:5px;" required placeholder="person@example.com" value="<?php echo $current_user->user_email;?>"/></div></center></td>
						</tr>
						<tr>
							<td><center><p>Please enter a valid email id that you have access to. You will be able to move forward after verifying an OTP that we will be sending to this email.</p></center></td>
						</tr>
						<tr><td></td></tr>
						<tr><td></td></tr>
						<tr><td></td></tr>
						<tr><td></td></tr>
						<tr><td></td></tr>
						<tr><td></td></tr>
						<tr><td></td></tr>
						<tr><td></td></tr>
						<tr>
							<td><input type="hidden" name="miniorange_user_reg_nonce" value="<?php echo wp_create_nonce('miniorange-2-factor-user-reg-nonce'); ?>" />
							<center><input type="submit" name="miniorange_get_started" id="miniorange_get_started" class="button button-primary button-large extra-large" value="Get Started" /></center> </td>
						</tr>
					</table>
				</div>
			</div>
		</form>
	<?php
	}
	
	function show_2_factor_advanced_options($current_user){
		$random_mo_key = get_option('mo2f_new_customer');
	?>
		<div class="mo2f_table_layout">
			<?php echo mo2f_check_if_registered_with_miniorange($current_user); ?>
			<form name="f"  id="advance_options_form" method="post" action="">
				<?php if(current_user_can('manage_options')){ ?>
				<input type="hidden" name="option" value="mo_auth_advanced_options_save" />
			
				<h3>Device Profile View</h3><hr>
					<p>You can manage trusted devices which you have stored during login by remembering devices.</p> 
					<a class="button button-primary button-large" onclick="mo2fLoginMiniOrangeDashboard()" <?php if(mo2f_is_customer_registered()){}else{ echo 'disabled style="pointer-events: none;cursor: default;"';} ?> >View Profiles</a>
				<br><br />
				
				<h3>Customize Security Questions (KBA)*</h3><hr>
					<p>Administrator can choose the list of questions to show the question list to all users during KBA setup. Administrator can also decide how many default questions user can see during KBA setup and how many custom questions, user can add their own.</p> 
					
				<br>
				
				<h3>MultiSite Support*</h3><hr>
					<p>Just One time Setup. User has to setup his 2nd factor only once, no matter, in how many sites he exists. Ease of use.</p>
				<br />
				<h3>Custom Email and SMS Templates*</h3><hr>
					You can change the templates for Email and SMS as per your requirement.<br />
					<br>
					<a href = "javascript:void(0)" class="button button-primary button-large" disabled>Customize Templates</a>
					<div id="fade" class="black_overlay"></div>
				<h3>Custom Redirection*</h3><hr>
					This option will allow the users during login to redirect on the specific page role wise. 
				<br>
				
				<?php if(get_option('mo2f_enable_custom')==1 || $random_mo_key){?>
				<h3>Customize 'powered by' Logo*</h3><hr>
				 <div class="<?php echo ($random_mo_key) ?' mo2f_grayed_out':""?> ">
				 	<input type="checkbox" id="mo2f_disable_poweredby" name="mo2f_disable_poweredby" value="1" <?php checked( get_option('mo2f_disable_poweredby') == 1 ); 
				 	if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS' && !$random_mo_key){}else{ echo 'disabled';} ?> /> 
				 	Remove 'Powered By' option from the Login Screens. <br />
				 	<br /><div id="mo2f_note"><b>Note:</b> Checking this option will remove 'Powered By' from the Login Screens.</div>
				 	<br>
				 <input type="checkbox" id="mo2f_enable_custom_poweredby" name="mo2f_enable_custom_poweredby" value="1" <?php checked( get_option('mo2f_enable_custom_poweredby') == 1 ); 
					 if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS' && !$random_mo_key){}else{ echo 'disabled';} ?> />
					 
					 Enable Custom 'Powered By' option for the Login Screens. <br><br>
					 <div id="mo2f_note"><b>Instructions:</b>
						Go to /wp-content/uploads folder and upload a .png image with the name "custom".
					 </div>
				</div>
				 	<br>

				<h3>Customize Plugin Icon*</h3><hr>
				<div class="<?php echo ($random_mo_key) ?' mo2f_grayed_out':""?> ">
					<input type="checkbox" id="mo2f_enable_custom_icon" name="mo2f_enable_custom_icon" value="1" <?php checked( get_option('mo2f_enable_custom_icon') == 1 ); 
					 if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS' && !$random_mo_key){}else{ echo 'disabled';} ?> />
					 
					 Change Plugin Icon <br><br>
					 <div id="mo2f_note"><b>Instructions:</b>
						Go to /wp-content/uploads folder and upload a .png image with the name "plugin_icon".
					 </div>
				</div>
				 <br>

				<h3>Customize Plugin Name*</h3><hr>
				<div class="<?php echo ($random_mo_key) ?' mo2f_grayed_out':""?> ">
					 Change Plugin Name: <br><br>
				     <input type="text" class="mo2f_table_textbox" id="mo2f_custom_plugin_name" name="mo2f_custom_plugin_name" <?php if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS' && !$random_mo_key){}else{ echo 'disabled';} ?> value="<?php echo get_option('mo2f_custom_plugin_name')?>" placeholder="Enter a custom Plugin Name." />
					 <br><br>
					 <div id="mo2f_note"><b>Note:</b>
						This will be the Plugin Name You and your Users see in  WordPress Dashboard$.
					 </div>
				</div>	 	
					<br>
				<?php }	?>
				<br /><br/><div><b>*</b>These are premium features. You need to upgrade the plugin to use these features.</div><br /><br />
				<?php 
					
				} 
				?>
			</form>
			<form style="display:none;" id="mo2fa_loginform" action="<?php echo get_option( 'mo2f_host_name').'/moas/login'; ?>" 
		target="_blank" method="post">
			<input type="email" name="username" value="<?php echo get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true); ?>" />
			<input type="text" name="redirectUrl" value="<?php echo get_option('mo2f_host_name') . '/moas/viewrbaprofile';?>" />
		</form>
			<script>
			function mo2fLoginMiniOrangeDashboard(){
				jQuery('#mo2fa_loginform').submit();
			}
		</script>
			
		</div>
	<?php
	}
	
	function mo2f_show_user_otp_validation_page(){
	?>
		<!-- Enter otp -->
		
		<div class="mo2f_table_layout">
			<h3>Validate OTP</h3><hr>
			<div id="panel1">
				<table class="mo2f_settings_table">
					<form name="f" method="post" id="mo_2f_otp_form" action="">
						<input type="hidden" name="option" value="mo_2factor_validate_user_otp" />
							<tr>
								<td><b><font color="#FF0000">*</font>Enter OTP:</b></td>
								<td colspan="2"><input class="mo2f_table_textbox" autofocus="true" type="text" name="otp_token" required placeholder="Enter OTP" style="width:95%;"/></td>
								<td><a href="#resendotplink">Resend OTP ?</a></td>
							</tr>
							
							<tr>
								<td>&nbsp;</td>
								<td style="width:17%">
								<input type="submit" name="submit" value="Validate OTP" class="button button-primary button-large" /></td>

						</form>
						<form name="f" method="post" action="">
						<td>
						<input type="hidden" name="option" value="mo_2factor_backto_user_registration"/>
							<input type="submit" name="mo2f_goback" id="mo2f_goback" value="Back" class="button button-primary button-large" /></td>
						</form>
						</td>
						</tr>
						<form name="f" method="post" action="" id="resend_otp_form">
							<input type="hidden" name="option" value="mo_2factor_resend_user_otp"/>
						</form>
						
				</table>
				</div>
				<div>	
					<script>
						jQuery('a[href=\"#resendotplink\"]').click(function(e) {
							jQuery('#resend_otp_form').submit();
						});
					</script>
		
			<br><br>
			</div>
			
			
						
		</div>
					
	<?php
	}
	function modal_display(){ ?>
		
		<div id="smsAlertModal" class="mo2f_modal mo2f_modal_inner fade" role="dialog">
			<div class="mo2f_modal-dialog">
				<!-- Modal content-->
				<div class="login mo_customer_validation-modal-content" style="width:660px !important;">
					<div class="mo2f_modal-header">
						<button type="button" class="mo2f_close" data-dismiss="modal">&times;</button>
						<h2 class="mo2f_modal-title">Please Note!</h2>
					</div>
					<div class="mo2f_modal-body">
						<p style="font-size:14px;">Only <b><u>10 free transactions</u></b> of SMS can be used, post which your account <b style="color: red;">will get locked out, if you do not buy more  SMS transactions</b>. We highly recommended you to go for the other Phone based authentication methods like <b>Soft Token/Push Notification/QR Code Authentication </b>since they are as secure as the <b>OTP OVER SMS</b> method, and they do not require any purchase.</p>
						<ol  style="list-style-type:circle">
							<li>Setting up knowledge based questions (KBA) as an alternate login method will protect you in case your phone is not working or out of reach.<br></li>
							<li><b style="color: red;">What to do in case you are locked out?<br /></b/></li>
							<b>Rename</b> the plugin from FTP access. Go to <b>wp-content/plugins folder</b> and rename miniorange-2-factor-authentication folder.<br />You will be able to login with your Wordpress Username and password.<br />
						</ol>
					</div>
					<div class="mo2f_modal-footer">
						<button type="button" class="button button-primary" data-dismiss="modal">I understand</button>
					</div>
				</div>
			</div>
		</div>
	    
		<script>
			jQuery(function () {
				jQuery('#smsAlertModal').modal('toggle');
			});
		</script>
		
	<?php
	}
	
	function show_2_factor_login_demo($current_user){
			include_once('miniorange_2_factor_demo.php');
	}
	function mo2f_show_instruction_to_allusers($current_user,$mo2f_second_factor){
		//added for displying OTP over MS pop up to user
		if(!get_option('mo2f_modal_display')){
			modal_display();
			update_option('mo2f_modal_display', 1);
		}
		
		if($mo2f_second_factor == 'OUT OF BAND EMAIL'){
			$mo2f_second_factor = 'Email Verification';
		}else if($mo2f_second_factor == 'SMS'){
			$mo2f_second_factor = 'OTP over SMS';
		}else if($mo2f_second_factor == 'PHONE VERIFICATION'){
			$mo2f_second_factor = 'Phone Call Verification';
		}else if($mo2f_second_factor == 'SOFT TOKEN'){
			$mo2f_second_factor = 'Soft Token';
		}else if($mo2f_second_factor == 'MOBILE AUTHENTICATION'){
			$mo2f_second_factor = 'QR Code Authentication';
		}else if($mo2f_second_factor == 'PUSH NOTIFICATIONS'){
			$mo2f_second_factor = 'Push Notification';
		}else if($mo2f_second_factor == 'GOOGLE AUTHENTICATOR'){
				$app_type = get_user_meta($current_user->ID,'mo2f_external_app_type',true);
				if($app_type == 'GOOGLE AUTHENTICATOR'){
					$mo2f_second_factor = 'Google Authenticator';
				}else if($app_type == 'AUTHY 2-FACTOR AUTHENTICATION'){
					$mo2f_second_factor = 'Authy 2-Factor Authentication';
				}else{
					$mo2f_second_factor = 'Google Authenticator';
					update_user_meta($current_user->ID,'mo2f_external_app_type','GOOGLE AUTHENTICATOR');
				}
			}
		 ?>
	
			<div class="mo2f_table_layout">
				<?php
						if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR'){ 
					?>
						<br />
						<div style="display:block;color:red;background-color:rgba(251, 232, 0, 0.15);padding:5px;border:solid 1px rgba(255, 0, 9, 0.36);">Please <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mobile_configure">click here</a> to setup Two-Factor.</div>
				<?php }
				?>
					<h4>Thank you for registering with us.</h4>
					<h3>Your Profile</h3>
					<table border="1" style="background-color:#FFFFFF; border:1px solid #CCCCCC; border-collapse: collapse; padding:0px 0px 0px 10px; margin:2px; width:100%">
						<tr>
							<td style="width:45%; padding: 10px;"><b>2 Factor Registered Email</b></td>
							<td style="width:55%; padding: 10px;"><?php echo get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true); echo '  (' . $current_user->user_login . ')';?> 
							</td>
						</tr>
						<tr>
							<td style="width:45%; padding: 10px;"><b>Activated 2nd Factor</b></td>
							<td style="width:55%; padding: 10px;"><?php echo $mo2f_second_factor;?> 
							</td>
						</tr>
						<?php if(current_user_can('manage_options')){ ?>
						<tr>
							<td style="width:45%; padding: 10px;"><b>miniOrange Customer Email</b></td>
							<td style="width:55%; padding: 10px;"><?php echo get_option('mo2f_email');?></td>
						</tr>
						<tr>
							<td style="width:45%; padding: 10px;"><b>Customer ID</b></td>
							<td style="width:55%; padding: 10px;"><?php echo get_option('mo2f_customerKey');?></td>
						</tr>
						<tr>
							<td style="width:45%; padding: 10px;"><b>API Key</b></td>
							<td style="width:55%; padding: 10px;"><?php echo get_option('mo2f_api_key');?></td>
						</tr>
						<tr>
							<td style="width:45%; padding: 10px;"><b>Token Key</b></td>
							<td style="width:55%; padding: 10px;"><?php echo get_option('mo2f_customer_token');?></td>
						</tr>
						<?php if(get_option('mo2f_app_secret')){ ?>
							<tr>
								<td style="width:45%; padding: 10px;"><b>App Secret</b></td>
								<td style="width:55%; padding: 10px;"><?php echo get_option('mo2f_app_secret');?></td>
							</tr>
						<?php 
							}
						?>
						<tr style="height:40px;">
							<td style="border-right-color:white;"><a href="#mo_registered_forgot_password"><b>&nbsp; Click Here</b></a> if you forgot your password ?</td>
							<td></td>
							
						</tr>
						<?php
						}
						?>
					</table><br>
					<form name="f" method="post" action="" id="forgotpasswordform">
						<input type="hidden" name="email" id="hidden_email" value="<?php echo get_option('mo2f_email'); ?>" />
						<input type="hidden" name="option" value="mo_2factor_forgot_password"/>
					</form>
					<script>
						jQuery('a[href=\"#mo_registered_forgot_password\"]').click(function(){
							jQuery('#forgotpasswordform').submit();
						});
					</script>
				
			</div>	
		
		<br><br>
	
	<?php
	}
	
	function instruction_for_mobile_registration($current_user){ 
		if(!get_user_meta($current_user->ID,'mo2f_mobile_registration_status',true)) {
			download_instruction_for_mobile_app($current_user);
		}
	?><div>
		<h3>Step-2 : Scan QR code</h3><hr>
			
			<form name="f" method="post" action="">
				<input type="hidden" name="option" value="mo_auth_refresh_mobile_qrcode" />
					<?php if(get_user_meta($current_user->ID,'mo2f_mobile_registration_status',true)) {   ?>
					<div id="reconfigurePhone">
					<a  data-toggle="collapse" href="#mo2f_show_download_app" aria-expanded="false" >Click here to see Authenticator App download instructions.</a>
					<div id="mo2f_show_download_app" class="mo2f_collapse">
						<?php download_instruction_for_mobile_app($current_user); ?>
					</div>
					<br>
					<h4>Please click on 'Reconfigure your phone' button below to see QR Code.</h4>
					<input type="button" name="back" id="back_btn" class="button button-primary button-large" value="Back" />
					<input type="submit" name="submit" class="button button-primary button-large" value="Reconfigure your phone" />	
					</div>
					
					<?php } else {?>
					<div id="configurePhone"><h4>Please click on 'Configure your phone' button below to see QR Code.</h4>
					<input type="button" name="back" id="back_btn" class="button button-primary button-large" value="Back" />
					<input type="submit" name="submit" class="button button-primary button-large" value="Configure your phone" />
					</div>
					<?php } ?>
			</form>
				
					 <?php 
						if(isset($_SESSION[ 'mo2f_show_qr_code' ]) && $_SESSION[ 'mo2f_show_qr_code' ] == 'MO_2_FACTOR_SHOW_QR_CODE' && isset($_POST['option']) && $_POST['option'] == 'mo_auth_refresh_mobile_qrcode'){
									initialize_mobile_registration();
								 if(get_user_meta($current_user->ID,'mo2f_mobile_registration_status',true)) {   ?>
									<script>jQuery("#mo2f_app_div").show();</script>
								<?php
								} else{ ?>
									<script>jQuery("#mo2f_app_div").hide();</script>
								<?php
								}
						} else{
					?><br><br>
					<form name="f" method="post" action="" id="mo2f_cancel_form">
					<input type="hidden" name="option" value="mo2f_cancel_configuration" />
					
					</form>
		
					<script>
					jQuery('#back_btn').click(function() {	
						jQuery('#mo2f_cancel_form').submit();
					});
					</script>
					<?php } ?>
					
					
	<?php }
	
	function download_instruction_for_mobile_app($current_user){	?>	
	<div id="mo2f_app_div" class="mo_margin_left">
		<?php if(!get_user_meta($current_user->ID,'mo2f_mobile_registration_status',true)) { ?>
		<a  class="mo_app_link" data-toggle="collapse"  href="#mo2f_sub_header_app" aria-expanded="false" ><h3 class="mo2f_authn_header">Step-1 : Download the miniOrange<span style="color: #F78701;"> Authenticator</span> App</h3></a><hr class="mo_hr">
		
		<div class="mo2f_collapse in" id="mo2f_sub_header_app">
		<?php } ?>
		<table width="100%;" id="mo2f_inline_table">
			<tr id="mo2f_inline_table">
		
				<td>
				<h4 id="mo2f_phone_id"><b>iPhone Users</b></h4>
				<ol>
				<li>Go to App Store</li>
				<li>Search for <b>miniOrange</b></li>
				<li>Download and install <span style="color: #F78701;">miniOrange<b> Authenticator</b></span> app (<b>NOT MOAuth</b>)</li>
				</ol>
					<span><a target="_blank" href="https://itunes.apple.com/us/app/miniorange-authenticator/id796303566?ls=1"><img src="<?php echo plugins_url( 'includes/images/appstore.png' , __FILE__ );?>" style="width:120px; height:45px; margin-left:6px;"></a></span>
				</td>
				<td>
				<h4 id="mo2f_phone_id"><b>Android Users</b></h4>
				<ol>
				<li> Go to Google Play Store.</li>
				<li> Search for <b>miniOrange.</b></li>
				<li>Download and install <span style="color: #F78701;"><b> Authenticator</b></span> app (<b>NOT miniOrange Authenticator/MOAuth)</b></li>
				</ol>
				<a target="_blank" href="https://play.google.com/store/apps/details?id=com.miniorange.android.authenticator&hl=en"><img src="<?php echo plugins_url( 'includes/images/playStore.png' , __FILE__ );?>" style="width:120px; height:=45px; margin-left:6px;"></a>
				</td>
		
			</tr>
		</table>
		<?php if(!get_user_meta($current_user->ID,'mo2f_mobile_registration_status',true)) { ?> </div> <?php 
		}
		?>
	</div>
	<?php
	}
	function mo2f_configure_kba_questions(){ ?>
				<div class="mo2f_kba_header">Please choose 3 questions</div>
				<br>
			<table class="mo2f_kba_table" >
				<tr class="mo2f_kba_header">
					<td>
						Sr. No.
					</td>
					<td class="mo2f_kba_tb_data">
						Questions
					</td>
					<td>
						Answers
					</td>
				</tr>
				<tr class="mo2f_kba_body">
					<td>
					<center>1.</center>
					</td>
					<td class="mo2f_kba_tb_data">
						<select name="mo2f_kbaquestion_1" id="mo2f_kbaquestion_1" class="mo2f_kba_ques" required="true" onchange="mo_option_hide(1)">
							<option value="" selected="selected">-------------------------Select your question-------------------------</option>
							<option id="mq1_1" value="What is your first company name?">What is your first company name?</option>
							<option id="mq2_1" value="What was your childhood nickname?">What was your childhood nickname?</option>
							<option id="mq3_1" value="In what city did you meet your spouse/significant other?">In what city did you meet your spouse/significant other?</option>
							<option id="mq4_1" value="What is the name of your favorite childhood friend?">What is the name of your favorite childhood friend?</option>
							<option id="mq5_1" value="What school did you attend for sixth grade?">What school did you attend for sixth grade?</option>
							<option id="mq6_1" value="In what city or town was your first job?">In what city or town was your first job?</option>
							<option id="mq7_1" value="What is your favourite sport?">What is your favourite sport?</option>
							<option id="mq8_1" value="Who is your favourite sports player?">Who is your favourite sports player?</option>
							<option id="mq9_1" value="What is your grandmother's maiden name?">What is your grandmother's maiden name?</option>
							<option id="mq10_1" value="What was your first vehicle's registration number?">What was your first vehicle's registration number?</option>
						</select>
					</td>
					<td>
						<input class="mo2f_table_textbox" type="text" name="mo2f_kba_ans1" id="mo2f_kba_ans1" title="Only alphanumeric letters with special characters(_@.$#&amp;+-) are allowed." pattern="(?=\S)[A-Za-z0-9_@.$#&amp;+-\s]{1,100}" required="true" autofocus="true" placeholder="Enter your answer"  />
					</td>
				</tr>
				<tr class="mo2f_kba_body">
					<td>
					<center>2.</center>
					</td>
					<td class="mo2f_kba_tb_data">
						<select name="mo2f_kbaquestion_2" id="mo2f_kbaquestion_2" class="mo2f_kba_ques" required="true" onchange="mo_option_hide(2)">
							<option value="" selected="selected">-------------------------Select your question-------------------------</option>
							<option id="mq1_2" value="What is your first company name?">What is your first company name?</option>
							<option id="mq2_2" value="What was your childhood nickname?">What was your childhood nickname?</option>
							<option id="mq3_2" value="In what city did you meet your spouse/significant other?">In what city did you meet your spouse/significant other?</option>
							<option id="mq4_2" value="What is the name of your favorite childhood friend?">What is the name of your favorite childhood friend?</option>
							<option id="mq5_2" value="What school did you attend for sixth grade?">What school did you attend for sixth grade?</option>
							<option id="mq6_2" value="In what city or town was your first job?">In what city or town was your first job?</option>
							<option id="mq7_2" value="What is your favourite sport?">What is your favourite sport?</option>
							<option id="mq8_2" value="Who is your favourite sports player?">Who is your favourite sports player?</option>
							<option id="mq9_2" value="What is your grandmother's maiden name?">What is your grandmother's maiden name?</option>
							<option id="mq10_2" value="What was your first vehicle's registration number?">What was your first vehicle's registration number?</option>
						</select>
					</td>
					<td>
						<input class="mo2f_table_textbox" type="text" name="mo2f_kba_ans2" id="mo2f_kba_ans2" title="Only alphanumeric letters with special characters(_@.$#&amp;+-) are allowed." pattern="(?=\S)[A-Za-z0-9_@.$#&amp;+-\s]{1,100}" required="true" placeholder="Enter your answer"  />
					</td>
				</tr>
				<tr class="mo2f_kba_body">
					<td>
					<center>3.</center>
					</td>
					<td class="mo2f_kba_tb_data">
						<input class="mo2f_kba_ques" type="text" name="mo2f_kbaquestion_3" id="mo2f_kbaquestion_3"  required="true" placeholder="Enter your custom question here"/>
					</td>
					<td>
						<input class="mo2f_table_textbox" type="text" name="mo2f_kba_ans3" id="mo2f_kba_ans3"  title="Only alphanumeric letters with special characters(_@.$#&amp;+-) are allowed." pattern="(?=\S)[A-Za-z0-9_@.$#&amp;+-\s]{1,100}" required="true" placeholder="Enter your answer"/>
					</td>
				</tr>
			</table>
			<script>
				//hidden element in dropdown list 1
				var mo_option_to_hide1;
				//hidden element in dropdown list 2
				var mo_option_to_hide2;

				function mo_option_hide(list) {
					//grab the team selected by the user in the dropdown list
					var list_selected = document.getElementById("mo2f_kbaquestion_" + list).selectedIndex;
					//if an element is currently hidden, unhide it
					if (typeof (mo_option_to_hide1) != "undefined" && mo_option_to_hide1 !== null && list == 2) {
						mo_option_to_hide1.style.display = 'block';
					} else if (typeof (mo_option_to_hide2) != "undefined" && mo_option_to_hide2 !== null && list == 1) {
						mo_option_to_hide2.style.display = 'block';
					}
					//select the element to hide and then hide it
					if (list == 1) {
						if(list_selected != 0){
							mo_option_to_hide2 = document.getElementById("mq" + list_selected + "_2");
							mo_option_to_hide2.style.display = 'none';
						}
					}
					if (list == 2) {
						if(list_selected != 0){
							mo_option_to_hide1 = document.getElementById("mq" + list_selected + "_1");
							mo_option_to_hide1.style.display = 'none';
						}
					}
				}
			</script>
			<?php if(isset($_SESSION['mo2f_mobile_support']) && $_SESSION['mo2f_mobile_support'] == 'MO2F_EMAIL_BACKUP_KBA'){
			?>
				<input type="hidden" name="mobile_kba_option" value="mo2f_request_for_kba_as_emailbackup" />
			<?php
			}
	}
	function mo2f_configure_for_mobile_suppport_kba($current_user){
	?>
		
			<h3>Configure Second Factor - KBA (Security Questions)</h3><hr />
			<form name="f" method="post" action="" id="mo2f_kba_setup_form">
			<?php mo2f_configure_kba_questions(); ?>
	<br />
				<input type="hidden" name="option" value="mo2f_save_kba" />
		<table>
			<tr>
				<td></td>
				<td>
					<input type="submit" id="mo2f_kba_submit_btn" name="submit" value="Save" class="button button-primary button-large" style="width:100px;line-height:30px;"/>
					</form>	
				</td>
				<td>
				
				<form name="f" method="post" action="" id="mo2f_cancel_form">
					<input type="hidden" name="option" value="mo2f_cancel_configuration" />
					<input type="submit" name="back" id="back_btn" class="button button-primary button-large" value="Back" style="width:100px;line-height:30px;" />
				</form>
			
				</td>
			</tr>
		</table>
		<script>
		
			jQuery('#mo2f_kba_submit_btn').click(function() {
				jQuery('#mo2f_kba_setup_form').submit();
			});
		</script>
	<?php
	}
	
	function mo2f_select_2_factor_method($current_user,$mo2f_second_factor){ 
			
			$opt = (array) get_option('mo2f_auth_methods_for_users');
			$random_mo_key = get_option('mo2f_new_customer');
			$selectedMethod = $mo2f_second_factor;
			if($mo2f_second_factor == 'OUT OF BAND EMAIL'){
						$selectedMethod = "Email Verification";
			} else if($mo2f_second_factor == 'MOBILE AUTHENTICATION'){
						$selectedMethod = "QR Code Authentication";
			}else if($mo2f_second_factor == 'SMS'){
						$selectedMethod = "OTP Over SMS";
			}else if($mo2f_second_factor == 'GOOGLE AUTHENTICATOR'){
				$app_type = get_user_meta($current_user->ID,'mo2f_external_app_type',true);
				if($app_type == 'GOOGLE AUTHENTICATOR'){
					$selectedMethod = 'GOOGLE AUTHENTICATOR';
				}else if($app_type == 'AUTHY 2-FACTOR AUTHENTICATION'){
					$selectedMethod = 'AUTHY 2-FACTOR AUTHENTICATION';
				}else{
					$selectedMethod = 'GOOGLE AUTHENTICATOR';
					update_user_meta($current_user->ID,'mo2f_external_app_type','GOOGLE AUTHENTICATOR');
				}
			}
			
			if($selectedMethod == "OTP Over SMS"){
				update_option('mo2f_show_sms_transaction_message', 1);
			}else{
				update_option('mo2f_show_sms_transaction_message', 0);
			}?>
		<div class="mo2f_table_layout">	
		<?php
		
		if( get_user_meta($current_user->ID,'mo2f_configure_test_option',true) == 'MO2F_CONFIGURE'){
				 
				$current_selected_method = get_user_meta($current_user->ID,'mo2f_selected_2factor_method',true);
				if($current_selected_method == 'MOBILE AUTHENTICATION' || $current_selected_method == 'SOFT TOKEN' || $current_selected_method == 'PUSH NOTIFICATIONS'){
					instruction_for_mobile_registration($current_user);
				}else if($current_selected_method == 'SMS' || $current_selected_method == 'PHONE VERIFICATION'){
					show_verify_phone_for_otp($current_user);
				}else if($current_selected_method == 'GOOGLE AUTHENTICATOR' ){
					mo2f_configure_google_authenticator($current_user);
				}else if($current_selected_method == 'AUTHY 2-FACTOR AUTHENTICATION' ){
					mo2f_configure_authy_authenticator($current_user);
				}else if($current_selected_method == 'KBA' ){
					mo2f_configure_for_mobile_suppport_kba($current_user);
				}else{
					test_out_of_band_email($current_user);
				}
		} else if( get_user_meta($current_user->ID,'mo2f_configure_test_option',true) == 'MO2F_TEST') {
			
				$current_selected_method = get_user_meta($current_user->ID,'mo2f_selected_2factor_method',true);
				
				if($current_selected_method == 'MOBILE AUTHENTICATION') {
					test_mobile_authentication();
				}else if($current_selected_method == 'PUSH NOTIFICATIONS'){
					test_push_notification();
				}else if($current_selected_method == 'SOFT TOKEN'){
					test_soft_token();
				}else if ($current_selected_method == 'SMS' || $current_selected_method == 'PHONE VERIFICATION'){
					test_otp_over_sms($current_user);
				}else if($current_selected_method == 'GOOGLE AUTHENTICATOR' || $current_selected_method == 'AUTHY 2-FACTOR AUTHENTICATION' ){
					test_google_authenticator($current_selected_method);
				}else if( $current_selected_method == 'KBA' ){
					test_kba_authentication($current_user);
				}else {
					test_out_of_band_email($current_user);
				}
			
		}else{
		
		if(!get_user_meta($current_user->ID,'mo2f_kba_registration_status',true) && ((get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS') || (get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR'))){
			
		?>
		<br>
		<div style="display:block;color:red;background-color:rgba(251, 232, 0, 0.15);padding:5px;border:solid 1px rgba(255, 0, 9, 0.36);" class="error notice is-dismissible"><a href="#mo2f_kba_config">Click Here</a> to configure Security Questions (KBA) as alternate 2 factor method so that you are not locked out of your account in case you lost or forgot your phone. </div>
		
		<?php
			
		}else if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR'){ 
				?>
				<br />
				<div style="display:block;color:red;background-color:rgba(251, 232, 0, 0.15);padding:5px;border:solid 1px rgba(255, 0, 9, 0.36);">Please configure your 2nd factor here to complete the Two-Factor setup..</div>
	<?php	
		}
	?>
			<h3>Setup Two-Factor<span style="font-size:15px;color:rgb(24, 203, 45);padding-left:250px;">Active Method - <?php echo $selectedMethod; ?></span><span style="float:right;"><a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=2factor_setup" >Need Support?</a></span></h3><hr>
			<p><b>Select any Two-Factor of your choice below and complete its setup. <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_demo">Click here to see How To Setup ?</a></b>
		</p>
		<form name="f" method="post" action="" id="mo2f_2factor_form">
			
							
			<div id="smsAlertModal" class="mo2f_modal mo2f_modal_inner fade" role="dialog">
				<div class="mo2f_modal-dialog">
					<!-- Modal content-->
					<div class="login mo_customer_validation-modal-content" style="width:660px !important;">
						<div class="mo2f_modal-header">
							<button type="button" class="mo2f_close" data-dismiss="modal">&times;</button>
							<h2 class="mo2f_modal-title">Please Note!</h2>
						</div>
						<div class="mo2f_modal-body">
							<p style="font-size:14px;">Only <b><u>10 free transactions</u></b> of SMS can be used, post which your account <b style="color: red;">will get locked out, if you do not buy more  SMS transactions</b>. We highly recommended you to go for the other Phone based authentication methods like <b>Soft Token/Push Notification/QR Code Authentication </b>since they are as secure as the <b>OTP OVER SMS</b> method, and they do not require any purchase.</p>
							<ol  style="list-style-type:circle">
								<li>Setting up knowledge based questions (KBA) as an alternate login method will protect you in case your phone is not working or out of reach.<br></li>
								<li><b style="color: red;">What to do in case you are locked out?<br /></b/></li>
								<b>Rename</b> the plugin from FTP access. Go to <b>wp-content/plugins folder</b> and rename miniorange-2-factor-authentication folder.<br />You will be able to login with your Wordpress Username and password.<br />
							</ol>
						</div>
						<div class="mo2f_modal-footer">
							<button type="button" class="button button-primary" id="moSMSModalbutton">I understand</button>
						</div>
					</div>
				</div>
			</div>
							
			<table style="width:100%;padding:10px;">
				<tr>
					<td>
						<span class="color-icon selectedMethod"></span> - Active Method
						<span class="color-icon activeMethod"></span> - Configured Method
						<span class="color-icon inactiveMethod"></span> - Unconfigured Method
					</td>
				</tr>
			</table><br>
				<table>
				<tr>
				<td class="<?php if(!current_user_can('manage_options') && !(in_array("OUT OF BAND EMAIL", $opt))  ){ echo "mo2f_td_hide"; }else { echo "mo2f_td_show"; } ?>" >
					<div class="mo2f_thumbnail">
							<label title="Supported in Desktops, Laptops, Smartphones.">
								<input type="radio"  name="mo2f_selected_2factor_method" style="margin:5px;" value="OUT OF BAND EMAIL" <?php checked($mo2f_second_factor == 'OUT OF BAND EMAIL');
								if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS' || get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR' ){ 
											} else{ echo 'disabled'; } ?>   />
								Email Verification
							</label><hr>
							<p>
								You will receive an email with link. You have to click the ACCEPT or DENY link to verify your email. Supported in Desktops, Laptops, Smartphones.
							</p>
								
								<?php if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){
										if(!get_user_meta($current_user->ID,'mo2f_email_verification_status',true)){
											update_user_meta($current_user->ID,'mo2f_email_verification_status',true);
										}
									?> 
									<div class="configuredLaptop" id="OUT_OF_BAND_EMAIL" title="Supported in Desktops, Laptops, Smartphones">
										<a href="#test" data-method="OUT OF BAND EMAIL"  <?php checked($mo2f_second_factor == 'OUT OF BAND EMAIL'); ?> >Test</a>
									</div>
								<?php } else { ?>
									
									<div class="notConfiguredLaptop" style="padding:20px;" id="OUT_OF_BAND_EMAIL" title="Supported in Desktops, Laptops, Smartphones."></div>
								<?php } ?>
								</div>
						
						
					</td>
					<td class="<?php if(!current_user_can('manage_options') && !(in_array("SMS", $opt))  ){ echo "mo2f_td_hide"; }else { echo "mo2f_td_show"; } ?>" >
								   
						
						
						<div class="mo2f_thumbnail">
							<label title="Supported in Smartphones, Feature Phones.">
								<input type="radio"  name="mo2f_selected_2factor_method" style="margin:5px;" value="SMS" data-backdrop="static" data-toggle="modal" data-target="#smsAlertModal"<?php checked($mo2f_second_factor == 'SMS');
								if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS' || get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR' ){ 
											} else{ echo 'disabled'; } ?> />
								OTP Over SMS<?php echo $random_mo_key ? '*<span style="float:right;"><a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_pricing" ><b>PREMIUM**</b></a></span>' :'';?>
							</label><hr>
							<p>
								You will receive a one time passcode via SMS on your phone. You have to enter the otp on your screen to login. Supported in Smartphones, Feature Phones.
							</p>
							<?php if(get_user_meta($current_user->ID,'mo2f_otp_registration_status',true)){ ?>
								<div class="configuredBasic" id="SMS" title="supported in smartphone,feature phone">
									<a href="#reconfigure" data-method="SMS" >Reconfigure</a> | <a href="#test" data-method="SMS">Test</a>
								</div>
							<?php } else { ?>
								<div class="notConfiguredBasic" title="Supported in Smartphones, Feature Phones."><a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_demo">How To Setup ?</a></div>
							<?php } ?>
						</div>
					</td>
					<td class="<?php if( !current_user_can('manage_options') && !(in_array("PHONE VERIFICATION", $opt))  ){ echo "mo2f_td_hide"; }else { echo "mo2f_td_show"; } ?>">
						<div><div class="mo2f_grayed_out_link"><?php echo $random_mo_key ? '<span style="float:right;" title="This feature is avialable in premium version of plugin"><a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_pricing" ><b>PREMIUM**</b></a></span>' :'';?></div>
						<div class="mo2f_thumbnail<?php echo $random_mo_key ? " mo2f_grayed_out" : '';?>" >
							<label title="Supported in Landline phones, Smartphones, Feature phones.">
								<input type="radio"  name="mo2f_selected_2factor_method" style="margin:5px;" value="PHONE VERIFICATION" <?php checked($mo2f_second_factor == 'PHONE VERIFICATION');
								if(!$random_mo_key && (get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS' || get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR') ){ 
											} else{ echo 'disabled'; } ?> />
								Phone Call Verification 
							</label><hr>
							<p>
								You will receive a phone call telling a one time passcode. You have to enter the one time passcode to login. Supported in Landlines, Smartphones, Feature phones.
							</p>
							<?php if(get_user_meta($current_user->ID,'mo2f_otp_registration_status',true)){ ?>
								<div class="configuredLandline" id="PHONE_VERIFICATION" title="Supported in Landline phones, Smartphones, Feature phones.">
									<a href="#reconfigure" data-method="PHONE VERIFICATION" >Reconfigure</a> | <a href="#test" data-method="PHONE VERIFICATION">Test</a>
								</div>
							<?php } else { ?>
								<div class="notConfiguredLandline" title="supported in Landline phone,smartphone,feature phone"><a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_demo#demo2">How To Setup ?</a></div>
							<?php } ?>
						</div>
						</div>
					</td>
				</tr>
				<tr>
					<td class="<?php if( !current_user_can('manage_options') && !(in_array("SOFT TOKEN", $opt))  ){ echo "mo2f_td_hide"; }else { echo "mo2f_td_show"; } ?>" >
						<div class="mo2f_thumbnail">
							<label title="Supported in Smartphones only" >
								<input type="radio"  name="mo2f_selected_2factor_method" style="margin:5px;" value="SOFT TOKEN" <?php checked($mo2f_second_factor == 'SOFT TOKEN');
								if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS' ||	get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR' ){ 
												} else{ echo 'disabled'; } ?> />
								Soft Token
							</label><hr>
							<p>
								You have to enter 6 digits code generated by miniOrange Authenticator App like Google Authenticator code to login. Supported in Smartphones only.
							</p>
							<?php if(get_user_meta($current_user->ID,'mo2f_mobile_registration_status',true)){ ?>
							<div class="configuredSmart" id="SOFT_TOKEN" title="Supported in Smartphones only">
								<a href="#reconfigure" data-method="SOFT TOKEN" >Reconfigure</a> | <a href="#test" data-method="SOFT TOKEN">Test</a>
							</div>
							<?php } else { ?>
								<div class="notConfiguredSmart" title="supported in smartphone"><a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_demo#demo1">How To Setup ?</a></div>
							<?php } ?>
						</div>
					</td>
				
					<td class="<?php if( !current_user_can('manage_options') && !(in_array("MOBILE AUTHENTICATION", $opt))  ){ echo "mo2f_td_hide"; }else { echo "mo2f_td_show"; }?>">
						<div class="mo2f_thumbnail">
							<label title="Supported in Smartphones only.">
								<input type="radio"  name="mo2f_selected_2factor_method" style="margin:5px;" value="MOBILE AUTHENTICATION" <?php checked($mo2f_second_factor == 'MOBILE AUTHENTICATION');
								if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS' || get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR' ){ 
											} else{ echo 'disabled'; } ?> />
								QR Code Authentication
							</label><hr>
							<p>
								You have to scan the QR Code from your phone using miniOrange Authenticator App to login. Supported in Smartphones only.
							</p>
							<?php if(get_user_meta($current_user->ID,'mo2f_mobile_registration_status',true)  ){ ?>
								<div class="configuredSmart" id="MOBILE_AUTHENTICATION" title="Supported in Smartphones only.">
									<a href="#reconfigure" data-method="MOBILE AUTHENTICATION">Reconfigure</a> | <a href="#test" data-method="MOBILE AUTHENTICATION">Test</a>
								</div>
							<?php } else { ?>
								<div class="notConfiguredSmart" title="Supported in Smartphones only"><a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_demo#demo3">How To Setup ?</a></div>
							<?php } ?>
						</div>
					</td>
					<td class="<?php if( !current_user_can('manage_options') && !(in_array("PUSH NOTIFICATIONS", $opt))  ){ echo "mo2f_td_hide"; }else { echo "mo2f_td_show"; } ?>" >
						<div class="mo2f_thumbnail">
							<label title="Supported in Smartphones only">
								<input type="radio"  name="mo2f_selected_2factor_method" style="margin:5px;" value="PUSH NOTIFICATIONS" <?php checked($mo2f_second_factor == 'PUSH NOTIFICATIONS');
								
								if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS' ||	get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR' ){ 
												} else{ echo 'disabled'; } ?> />
								Push Notification
							</label><hr>
							<p>
								You will receive a push notification on your phone. You have to ACCEPT or DENY it to login. Supported in Smartphones only.
							</p>
							
							<?php if(get_user_meta($current_user->ID,'mo2f_mobile_registration_status',true)){ ?>
							<div class="configuredSmart" id="PUSH_NOTIFICATIONS" title="supported in smartphone">
								<a href="#reconfigure" data-method="PUSH NOTIFICATIONS" >Reconfigure</a> | <a href="#test" data-method="PUSH NOTIFICATIONS">Test</a>
							</div>
							<?php } else { ?>
								<div class="notConfiguredSmart" title="Supported in Smartphones only."><a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_demo#demo3">How To Setup ?</a></div>
							<?php } ?>
						</div>
					</td>
					</tr>
				<tr>
					<td class="<?php if( !current_user_can('manage_options') && !(in_array("GOOGLE AUTHENTICATOR", $opt))  ){ echo "mo2f_td_hide"; }else { echo "mo2f_td_show"; }?>">
						
						<div class="mo2f_thumbnail">
							<label title="Supported in Smartphones only">
								<input type="radio"  name="mo2f_selected_2factor_method" style="margin:5px;" value="GOOGLE AUTHENTICATOR" <?php checked($selectedMethod == 'GOOGLE AUTHENTICATOR');
								if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS' ||	get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR' ){ 
												} else{ echo 'disabled'; } ?> />
								Google Authenticator
							</label><hr>
							<p>
								You have to enter the 6 digits code generated by Google Authenticator App to login. Supported in Smartphones only.
							</p>
							
							<?php if(get_user_meta($current_user->ID,'mo2f_google_authentication_status',true)){ ?>
							<div class="configuredSmart" id="GOOGLE_AUTHENTICATOR" title="supported in smartphone">
								<a href="#reconfigure" data-method="GOOGLE AUTHENTICATOR" >Reconfigure</a> | <a href="#test" data-method="GOOGLE AUTHENTICATOR">Test</a>
							</div>
							<?php } else { ?>
								<div class="notConfiguredSmart" title="Supported in Smartphones only."><a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_demo#demo5">How To Setup ?</a></div>
							<?php } ?>
						</div>
					</td>
					<td class="<?php if( !current_user_can('manage_options') && !(in_array("AUTHY 2-FACTOR AUTHENTICATION", $opt))  ){ echo "mo2f_td_hide"; }else { echo "mo2f_td_show"; }?>">
						
						<div class="mo2f_thumbnail">
							<label title="Supported in Smartphones only">
								<input type="radio"  name="mo2f_selected_2factor_method" style="margin:5px;" value="AUTHY 2-FACTOR AUTHENTICATION" <?php checked($selectedMethod == 'AUTHY 2-FACTOR AUTHENTICATION');
								if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS' ||	get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR' ){ 
												} else{ echo 'disabled'; } ?> />
								Authy 2-Factor Authentication
							</label><hr>
							<p>
								You have to enter 6 digits code generated by Authy 2-Factor Authentication App to login. Supported in Smartphones only.
							</p>
							<?php if(get_user_meta($current_user->ID,'mo2f_authy_authentication_status',true)){ ?>
							<div class="configuredSmart" id="GOOGLE_AUTHENTICATOR" title="supported in smartphone">
								<a href="#reconfigure" data-method="AUTHY 2-FACTOR AUTHENTICATION" >Reconfigure</a> | <a href="#test" data-method="AUTHY 2-FACTOR AUTHENTICATION">Test</a>
							</div>
							<?php } else { ?>
								<div class="notConfiguredSmart" title="Supported in Smartphones only."><a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_demo#demo5">How To Setup ?</a></div>
							<?php } ?>
						</div>
					</td>
					<td class="<?php if( !current_user_can('manage_options') && !(in_array("KBA", $opt))  ){ echo "mo2f_td_hide"; }else { echo "mo2f_td_show"; }?>">
						
						<div class="mo2f_thumbnail">
							<label title="Supported in DeskTops,Laptops and Smartphones.">
								<input type="radio"  name="mo2f_selected_2factor_method" style="margin:5px;" value="KBA" <?php checked($mo2f_second_factor == 'KBA');
								if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS' ||	get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR' ){ 
												} else{ echo 'disabled'; } ?> />
								Security Questions( KBA )
							</label><hr>
							<p>
								You have to answers some knowledge based security questions which are only known to you to authenticate yourself. Supported in Desktops,Laptops,Smartphones.
							</p>
							<?php if(get_user_meta($current_user->ID,'mo2f_kba_registration_status',true)) { ?>
									<div class="configuredLaptop" id="KBA" title="Supported in Desktops, Laptops, Smartphones">
										<a href="#reconfigure" data-method="KBA" >Reconfigure</a> | <a href="#test" data-method="KBA">Test</a>
									</div>
							<?php } else { ?>
								<div class="notConfiguredLaptop" style="padding:10px !important;"title="Supported in Desktops, Laptops, Smartphones."><a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_demo#demo6">How To Setup ?</a></div>
							<?php } ?>
							
						</div>
					</td>
				</tr>
				<tr>	
					<!-- OTP Over SMS and EMail method-->
					<td class="<?php if( !current_user_can('manage_options') && !(in_array("SMS AND EMAIL", $opt))  ){ echo "mo2f_td_hide"; }else { echo "mo2f_td_show"; } ?>">
						<div><div class="mo2f_grayed_out_link"><?php echo $random_mo_key ? '<span style="float:right;" title="This feature is avialable in premium version of plugin"><a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_pricing" ><b>PREMIUM**</b></a></span>' :'';?></div>
						<div class="mo2f_thumbnail<?php echo $random_mo_key ? " mo2f_grayed_out" : '';?>" >
							<label title="Supported in Laptops, Smartphones, Feature phones.">
								<input type="radio"  name="mo2f_selected_2factor_method" style="margin:5px;" value="PHONE VERIFICATION" <?php checked($mo2f_second_factor == 'SMS AND EMAIL');
								if(!$random_mo_key && (get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS' || get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_INITIALIZE_TWO_FACTOR') ){ 
											} else{ echo 'disabled'; } ?> />
								OTP Over SMS And Email 
							</label><hr>
							<p>
								You will receive a one time passcode via SMS on your phone and your e-mail. You have to enter the otp on your screen to login. Supported in Smartphones, Feature Phones.
							</p>
							 
							<div class="notConfiguredBasic" title="Supported in Smartphones, Feature Phones."><a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_demo#demo2">How To Setup ?</a></div>
							
						</div>
						</div>
					</td>
				</tr>
				
				</table>
				<?php echo $random_mo_key ? '<h4>* Only 10 free transactions of SMS are provided with the plugin, post which you will have to purchase more SMS transactions as per your need. Please refer to the <b>Licensing Plans</b> tab for more details.</h4><h4>
				** These authentication methods are provided in premium plugin.</h4>' : ''; ?>
				<input type="hidden" name="option" value="mo2f_save_2factor_method" />		
		</form>
			<form name="f" method="post" action="" id="mo2f_2factor_save_form">
					<input type="hidden" name="option" value="mo2f_update_2factor_method" />
					<input type="hidden" name="mo2f_selected_2factor_method" id="mo2f_selected_2factor_method" />
			</form>
			<form name="f" method="post" action="" id="mo2f_2factor_reconfigure_form">
				<input type="hidden" name="mo2f_selected_2factor_method" id="mo2f_reconfigure_2factor_method" />
				<input type="hidden" name="option" value="mo2f_save_2factor_method" />
			</form>
			<form name="f" method="post" action="" id="mo2f_2factor_test_mobile_form">
				<input type="hidden" name="option" value="mo_2factor_test_mobile_authentication" />
			</form>	
			<form name="f" method="post" action="" id="mo2f_2factor_test_softtoken_form">
				<input type="hidden" name="option" value="mo_2factor_test_soft_token" />
			</form>	
			<form name="f" method="post" action="" id="mo2f_2factor_test_smsotp_form">
				<input type="hidden" name="mo2f_selected_2factor_method" id="mo2f_test_2factor_method" />
				<input type="hidden" name="option" value="mo_2factor_test_otp_over_sms" />
			</form>	
			<form name="f" method="post" action="" id="mo2f_2factor_test_push_form">
				<input type="hidden" name="option" value="mo_2factor_test_push_notification" />
			</form>	
			<form name="f" method="post" action="" id="mo2f_2factor_test_out_of_band_email_form">
				<input type="hidden" name="option" value="mo_2factor_test_out_of_band_email" />
			</form>
			<form name="f" method="post" action="" id="mo2f_2factor_test_google_auth_form" >
				<input type="hidden" name="option" value="mo_2factor_test_google_auth" />
			</form>
			<form name="f" method="post" action="" id="mo2f_2factor_test_authy_app_form" >
				<input type="hidden" name="option" value="mo_2factor_test_authy_auth" />
			</form>
			<form name="f" method="post" action="" id="mo2f_2factor_test_kba_form" >
				<input type="hidden" name="option" value="mo2f_2factor_test_kba" />
			</form>
			<form name="f" method="post" action="" id="mo2f_2factor_configure_kba_backup_form" >
				<input type="hidden" name="option" value="mo2f_2factor_configure_kba_backup" />
			</form>
	
		<script>
			
			jQuery('a[href=\"#mo2f_kba_config\"]').click(function() {
				jQuery('#mo2f_2factor_configure_kba_backup_form').submit();
			});
			
			jQuery('input:radio[name=mo2f_selected_2factor_method]').click(function() {
				var selectedMethod = jQuery(this).val();
				<?php if(get_user_meta($current_user->ID,'mo2f_mobile_registration_status',true)) { ?>
				    if(selectedMethod == 'MOBILE AUTHENTICATION' || selectedMethod == 'SOFT TOKEN' || selectedMethod == 'PUSH NOTIFICATIONS' ){
						jQuery('#mo2f_selected_2factor_method').val(selectedMethod);
						jQuery('#mo2f_2factor_save_form').submit();
					}
				<?php } else{ ?>
					if(selectedMethod == 'MOBILE AUTHENTICATION' || selectedMethod == 'SOFT TOKEN' || selectedMethod == 'PUSH NOTIFICATIONS'  ){
						jQuery('#mo2f_2factor_form').submit();
					}
				<?php } if(get_user_meta($current_user->ID,'mo2f_email_verification_status',true)) { ?>
					if(selectedMethod == 'OUT OF BAND EMAIL'  ){
						jQuery('#mo2f_selected_2factor_method').val(selectedMethod);
						jQuery('#mo2f_2factor_save_form').submit();
					 }
				<?php } else{ ?>
					if(selectedMethod == 'OUT OF BAND EMAIL' ){
						jQuery('#mo2f_2factor_form').submit();
					 }
				<?php } if(get_user_meta($current_user->ID,'mo2f_otp_registration_status',true)) { ?>
					 if(selectedMethod == 'PHONE VERIFICATION'){
						jQuery('#mo2f_selected_2factor_method').val(selectedMethod);
						jQuery('#mo2f_2factor_save_form').submit();
					 }
					
				<?php } else{ ?>
					if(selectedMethod == 'PHONE VERIFICATION'){
					    jQuery('#mo2f_2factor_form').submit();
					}
				
				<?php } if(get_user_meta($current_user->ID,'mo2f_otp_registration_status',true)) { ?>
					 if(selectedMethod == 'SMS'){
						jQuery('#moSMSModalbutton').click( function() {
							jQuery('#mo2f_selected_2factor_method').val(selectedMethod);
						    jQuery('#mo2f_2factor_save_form').submit();
						});
					 }
					
				<?php } else{ ?>
					if(selectedMethod == 'SMS'){
						jQuery('#moSMSModalbutton').click( function() {
 						    jQuery('#mo2f_2factor_form').submit();
						});
					}
				

					
				<?php } if(get_user_meta($current_user->ID,'mo2f_google_authentication_status',true)) { ?>
					  if(selectedMethod == 'GOOGLE AUTHENTICATOR' ){
						jQuery('#mo2f_selected_2factor_method').val(selectedMethod);
						jQuery('#mo2f_2factor_save_form').submit();
					  }
				<?php } else{ ?>
						if(selectedMethod == 'GOOGLE AUTHENTICATOR' ){
							jQuery('#mo2f_2factor_form').submit();
						}
				<?php } if(get_user_meta($current_user->ID,'mo2f_authy_authentication_status',true)) { ?>
					  if(selectedMethod == 'AUTHY 2-FACTOR AUTHENTICATION' ){
						jQuery('#mo2f_selected_2factor_method').val(selectedMethod);
						jQuery('#mo2f_2factor_save_form').submit();
					  }
				<?php } else{ ?>
						if(selectedMethod == 'AUTHY 2-FACTOR AUTHENTICATION' ){
							jQuery('#mo2f_2factor_form').submit();
						}
				<?php } if(get_user_meta($current_user->ID,'mo2f_kba_registration_status',true)) { ?>
					  if(selectedMethod == 'KBA' ){
						jQuery('#mo2f_selected_2factor_method').val(selectedMethod);
						jQuery('#mo2f_2factor_save_form').submit();
					  }
				<?php } else{ ?>
						if(selectedMethod == 'KBA' ){
							jQuery('#mo2f_2factor_form').submit();
						}
				<?php }?>
				
					
			});
			jQuery('a[href=\"#reconfigure\"]').click(function() {
				var reconfigureMethod = jQuery(this).data("method");
				jQuery('#mo2f_reconfigure_2factor_method').val(reconfigureMethod);
				jQuery('#mo2f_2factor_reconfigure_form').submit();
			});
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
				}else if(currentMethod == 'GOOGLE AUTHENTICATOR' ){
					jQuery('#mo2f_2factor_test_google_auth_form').submit();
				}else if(currentMethod == 'AUTHY 2-FACTOR AUTHENTICATION'){
					jQuery('#mo2f_2factor_test_authy_app_form').submit();
				}else if(currentMethod == 'OUT OF BAND EMAIL'){
					jQuery('#mo2f_2factor_test_out_of_band_email_form').submit();
				}else if(currentMethod == 'KBA' ){
					jQuery('#mo2f_2factor_test_kba_form').submit();
				}
			});
			<?php if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){ ?>
				var currentSecondFactor = jQuery('input[name=mo2f_selected_2factor_method][type=radio]:checked').val();
				var selectedMethod = currentSecondFactor.replace(/ /g, "_");
				jQuery("#" + selectedMethod).addClass('selectedMethod');
			<?php } ?>
		</script>
		<?php	} ?>
	
		<br><br>
		</div>
	<?php 
	}
	
	function mo2f_configure_authy_authenticator($current_user){
		$mo2f_authy_auth = isset($_SESSION['mo2f_authy_keys']) ? $_SESSION['mo2f_authy_keys'] : null;
		$data = isset($_SESSION['mo2f_authy_keys']) ? $mo2f_authy_auth['authy_qrCode'] : null;
		$authy_secret = isset($_SESSION['mo2f_authy_keys']) ? $mo2f_authy_auth['authy_secret'] : null;
		?>
		<table>
			<tr>
				<td style="vertical-align:top;width:26%;padding-right:15px">
					<h3>Step-1: Configure with Authy</h3><h3>2-Factor Authentication App.</h3><hr />
					<form name="f" method="post" id="mo2f_app_type_ga_form" action="" >
						<br /><input type="submit" name="mo2f_authy_configure" class="button button-primary button-large" style="width:45%;" value="Next >>" /><br /><br />
						<input type="hidden" name="option" value="mo2f_configure_authy_app" />
					</form>
					<form name="f" method="post" action="" id="mo2f_cancel_form">
						<input type="hidden" name="option" value="mo2f_cancel_configuration" />
						<input type="submit" name="back" id="back_btn" class="button button-primary button-large" style="width:45%;" value="Back" />
					</form>
				</td>
				<td style="border-left: 1px solid #EBECEC; padding: 5px;"></td>
				<td style="width:46%;padding-right:15px;vertical-align:top;">
					<h3>Step-2: Set up Authy 2-Factor Authentication App</h3><h3>&nbsp;	</h3><hr>
					<div style="<?php echo isset($_SESSION['mo2f_authy_keys']) ? 'display:block' : 'display:none'; ?>">
					<h4>Install the Authy 2-Factor Authentication App.</h4>
					<h4>Now open and configure Authy 2-Factor Authentication App.</h4>
					<h4> Tap on Add Account and then tap on SCAN QR CODE in your App and scan the qr code.</h4>
					<center><br><div id="displayQrCode" ><?php echo '<img src="data:image/jpg;base64,' . $data . '" />'; ?></div></center>
					<div><a  data-toggle="collapse" href="#mo2f_scanbarcode_a" aria-expanded="false" ><b>Can't scan the QR Code? </b></a></div>
					<div class="mo2f_collapse" id="mo2f_scanbarcode_a">
						<ol>
							<li>In Authy 2-Factor Authentication App, tap on ENTER KEY MANUALLY."</li>
							<li>In "Adding New Account" type your secret key:</li>
								<div style="padding: 10px; background-color: #f9edbe;width: 20em;text-align: center;" >
									<div style="font-size: 14px; font-weight: bold;line-height: 1.5;" >
									<?php echo $authy_secret; ?>
									</div>
									<div style="font-size: 80%;color: #666666;">
									Spaces don't matter.
									</div>
								</div>
							<li>Tap OK.</li>
						</ol>
					</div>
					</div>
				</td>
				<td style="border-left: 1px solid #EBECEC; padding: 5px;"></td>
				<td style="vertical-align:top;width:30%">
					<h3>Step-3: Verify and Save</h3><h3>&nbsp;</h3><hr>
					<div style="<?php echo isset($_SESSION['mo2f_authy_keys']) ? 'display:block' : 'display:none'; ?>">
					<h4>Once you have scanned the qr code, enter the verification code generated by the Authenticator app</h4><br/>
					<form name="f" method="post" action="" >
						<span><b>Code: </b>
						<input class="mo2f_table_textbox" style="width:200px;" autofocus="true" required="true" type="text" name="authy_token" placeholder="Enter OTP" style="width:95%;"/></span><br /><br/>
						<input type="hidden" name="authy_secret" value="<?php echo $authy_secret; ?>" />
						<input type="hidden" name="option" value="mo2f_validate_authy_auth" />
						<input type="submit" name="validate" id="validate" class="button button-primary button-large" style="margin-left:12%;"value="Verify and Save" />
					</form>
					</div>
				</td>
			</tr><br>
		</table>
		<script>
			jQuery('html,body').animate({scrollTop: jQuery(document).height()}, 600);
		</script>
	<?php
	}
	
	function mo2f_configure_google_authenticator($current_user){
	$mo2f_google_auth = isset($_SESSION['mo2f_google_auth']) ? $_SESSION['mo2f_google_auth'] : null;
	$data = isset($_SESSION['mo2f_google_auth']) ? $mo2f_google_auth['ga_qrCode'] : null;
	$ga_secret = isset($_SESSION['mo2f_google_auth']) ? $mo2f_google_auth['ga_secret'] : null;
	?>
		<table>
			<tr>
				<td style="vertical-align:top;width:22%;padding-right:15px">
					<h3>Step-1: Select phone Type</h3><hr />
					<form name="f" method="post" id="mo2f_app_type_ga_form" action="" >
						<input type="radio" name="mo2f_app_type_radio" value="android" <?php checked( $mo2f_google_auth['ga_phone'] == 'android' ); ?> /> <b>Android</b><br /><br />
						<input type="radio" name="mo2f_app_type_radio" value="iphone" <?php checked( $mo2f_google_auth['ga_phone'] == 'iphone' ); ?> /> <b>iPhone</b><br /><br />
						<input type="radio" name="mo2f_app_type_radio" value="blackberry" <?php checked( $mo2f_google_auth['ga_phone'] == 'blackberry' ); ?> /> <b>BlackBerry / Windows</b><br /><br />
						<input type="hidden" name="option" value="mo2f_configure_google_auth_phone_type" />
					</form>
					<form name="f" method="post" action="" id="mo2f_cancel_form">
						<input type="hidden" name="option" value="mo2f_cancel_configuration" />
						<input type="submit" name="back" id="back_btn" class="button button-primary button-large" style="width:45%;" value="Back" />
					</form>
				</td>
				<td style="border-left: 1px solid #EBECEC; padding: 5px;"></td>
				<td style="width:46%;padding-right:15px;vertical-align:top;">
					<h3>Step-2: Set up Google Authenticator</h3><hr>
					<div id="mo2f_android_div" style="<?php echo $mo2f_google_auth['ga_phone'] == 'android' ? 'display:block' : 'display:none'; ?>" >
					<h4>Install the Google Authenticator App for Android.</h4>
					<ol>
						<li>On your phone,Go to Google Play Store.</li>
						<li>Search for <b>Google Authenticator.</b>
						<a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank">Download from the Google Play Store and install the application.</a>
						</li>
					
					</ol>
					<h4>Now open and configure Google Authenticator.</h4>
					<ol>
						<li>In Google Authenticator, touch Menu and select "Set up account."</li>
						<li>Select "Scan a barcode". Use your phone's camera to scan this barcode.</li>
					<center><br><div id="displayQrCode" ><?php echo '<img src="data:image/jpg;base64,' . $data . '" />'; ?></div></center>
						
					</ol>
					<div><a  data-toggle="collapse" href="#mo2f_scanbarcode_a" aria-expanded="false" ><b>Can't scan the barcode? </b></a></div>
					<div class="mo2f_collapse" id="mo2f_scanbarcode_a">
						<ol>
							<li>In Google Authenticator, touch Menu and select "Set up account."</li>
							<li>Select "Enter provided key"</li>
							<li>In "Enter account name" type your full email address.</li>
							<li>In "Enter your key" type your secret key:</li>
								<div style="padding: 10px; background-color: #f9edbe;width: 20em;text-align: center;" >
									<div style="font-size: 14px; font-weight: bold;line-height: 1.5;" >
									<?php echo $ga_secret; ?>
									</div>
									<div style="font-size: 80%;color: #666666;">
									Spaces don't matter.
									</div>
								</div>
							<li>Key type: make sure "Time-based" is selected.</li>
							<li>Tap Add.</li>
						</ol>
					</div>
					</div>
					
					<div id="mo2f_iphone_div" style="<?php echo $mo2f_google_auth['ga_phone'] == 'iphone' ? 'display:block' : 'display:none'; ?>" >
					<h4>Install the Google Authenticator app for iPhone.</h4>
					<ol>
						<li>On your iPhone, tap the App Store icon.</li>
						<li>Search for <b>Google Authenticator.</b>
						<a href="http://itunes.apple.com/us/app/google-authenticator/id388497605?mt=8" target="_blank">Download from the App Store and install it</a>
						</li>
					</ol>
					<h4>Now open and configure Google Authenticator.</h4>
					<ol>
						<li>In Google Authenticator, tap "+", and then "Scan Barcode."</li>
						<li>Use your phone's camera to scan this barcode.
							<center><br><div id="displayQrCode" ><?php echo '<img src="data:image/jpg;base64,' . $data . '" />'; ?></div></center>
						</li>
					</ol>
					<div><a  data-toggle="collapse" href="#mo2f_scanbarcode_i" aria-expanded="false" ><b>Can't scan the barcode? </b></a></div>
					<div class="mo2f_collapse" id="mo2f_scanbarcode_i"  >
						<ol>
							<li>In Google Authenticator, tap +.</li>
							<li>Key type: make sure "Time-based" is selected.</li>
							<li>In "Account" type your full email address.</li>
							<li>In "Key" type your secret key:</li>
								<div style="padding: 10px; background-color: #f9edbe;width: 20em;text-align: center;" >
									<div style="font-size: 14px; font-weight: bold;line-height: 1.5;" >
									<?php echo $ga_secret; ?>
									</div>
									<div style="font-size: 80%;color: #666666;">
									Spaces don't matter.
									</div>
								</div>
							<li>Tap Add.</li>
						</ol>
					</div>
					</div>
					
					<div id="mo2f_blackberry_div" style="<?php echo $mo2f_google_auth['ga_phone'] == 'blackberry' ? 'display:block' : 'display:none'; ?>" >
					<h4>Install the Google Authenticator app for BlackBerry</h4>
					<ol>
						<li>On your phone, open a web browser.Go to <b>m.google.com/authenticator.</b></li>
						<li>Download and install the Google Authenticator application.</li>
					</ol>
					<h4>Now open and configure Google Authenticator.</h4>
					<ol>
						<li>In Google Authenticator, select Manual key entry.</li>
						<li>In "Enter account name" type your full email address.</li>
						<li>In "Enter key" type your secret key:</li>
							<div style="padding: 10px; background-color: #f9edbe;width: 20em;text-align: center;" >
								<div style="font-size: 14px; font-weight: bold;line-height: 1.5;" >
								<?php echo $ga_secret; ?>
								</div>
								<div style="font-size: 80%;color: #666666;">
								Spaces don't matter.
								</div>
							</div>
						<li>Choose Time-based type of key.</li>
						<li>Tap Save.</li>
					</ol>
					</div>
					
				</td>
				<td style="border-left: 1px solid #EBECEC; padding: 5px;"></td>
				<td style="vertical-align:top;width:30%">
					<h3>Step-3: Verify and Save</h3><hr>
					<div style="<?php echo isset($_SESSION['mo2f_google_auth']) ? 'display:block' : 'display:none'; ?>">
					<div>Once you have scanned the barcode, enter the 6-digit verification code generated by the Authenticator app</div><br/>
					<form name="f" method="post" action="" >
						<span><b>Code: </b>
						<input class="mo2f_table_textbox" style="width:200px;" autofocus="true" required="true" type="text" name="google_token" placeholder="Enter OTP" style="width:95%;"/></span><br /><br/>
						<input type="hidden" name="google_auth_secret" value="<?php echo $ga_secret ?>" />
						<input type="hidden" name="option" value="mo2f_validate_google_auth" />
						<input type="submit" name="validate" id="validate" class="button button-primary button-large" style="margin-left:12%;"value="Verify and Save" />
					</form>
					</div>
				</td>
			</tr><br>
			<a  data-toggle="collapse" href="#mo2f_question" aria-expanded="false" ><b>How miniOrange Authenticator is better than Google Authenticator ?</b></a>
			<div id="mo2f_question" class="mo2f_collapse"><p>
					 miniOrange Authenticator manages the Google Authenticator keys better and easier by providing these extra features:<br>
1. miniOrange <b>encrypts all data</b>, whereas Google Authenticator stores data in plain text.<br>
2. miniOrange Authenticator app has in-build <b>Pin-Protection</b> so you can protect your google authenticator keys or whole app using pin whereas Google Authenticator is not protected at all.<br>
3. No need to type in the code at all. Contact us to get <b>miniOrange Autofill Plugin</b>, it can seamlessly connect your computer to your phone. Code will get auto filled and saved.</p>
</div><br><br>
		</table>
		<script>
			 jQuery('input[type=radio][name=mo2f_app_type_radio]').change(function() {
				jQuery('#mo2f_app_type_ga_form').submit();
			 });
			 jQuery('html,body').animate({scrollTop: jQuery(document).height()}, 600);
		</script>
	<?php 
	}
	
	function show_verify_phone_for_otp($current_user){ 
	?>
				<h3>Verify Your Phone</h3><hr>
					<form name="f" method="post" action="" id="mo2f_verifyphone_form">
						<input type="hidden" name="option" value="mo2f_verify_phone" />
						
						<div style="display:inline;">
						<input class="mo2f_table_textbox" style="width:200px;" type="text" name="verify_phone" id="phone" 
						    value="<?php if( isset($_SESSION['mo2f_phone'])){ echo $_SESSION['mo2f_phone'];} else echo get_user_meta($current_user->ID,'mo2f_user_phone',true); ?>"  pattern="[\+]?[0-9]{1,4}\s?[0-9]{7,12}" title="Enter phone number without any space or dashes" /><br>
						<input type="submit" name="verify" id="verify" class="button button-primary button-large" value="Verify" />
						</div>
					</form>	
				<form name="f" method="post" action="" id="mo2f_validateotp_form">
					<input type="hidden" name="option" value="mo2f_validate_otp" />
						<p>Enter One Time Passcode</p>
								<input class="mo2f_table_textbox" style="width:200px;" autofocus="true" type="text" name="otp_token" placeholder="Enter OTP" style="width:95%;"/>
								<?php if (get_user_meta($current_user->ID, 'mo2f_selected_2factor_method',true) == 'SMS'){ ?>
									<a href="#resendsmslink">Resend OTP ?</a>
								<?php } else {?>
									<a href="#resendsmslink">Call Again ?</a>
								<?php } ?><br><br>
					<input type="button" name="back" id="back_btn" class="button button-primary button-large" value="Back" />
					<input type="submit" name="validate" id="validate" class="button button-primary button-large" value="Validate OTP" />
				</form><br>
				<form name="f" method="post" action="" id="mo2f_cancel_form">
					<input type="hidden" name="option" value="mo2f_cancel_configuration" />
				</form>
		<script>
			jQuery("#phone").intlTelInput();
			jQuery('#back_btn').click(function() {	
					jQuery('#mo2f_cancel_form').submit();
			});
			jQuery('a[href=\"#resendsmslink\"]').click(function(e) {
				jQuery('#mo2f_verifyphone_form').submit();
			});

		</script>
	<?php 
	}
	
	function initialize_mobile_registration() {
		$data = $_SESSION[ 'mo2f_qrCode' ];
		$url = get_option('mo2f_host_name');
		?>
		
			<p>Open your miniOrange<b> Authenticator</b> app and click on <b>Add Account</b> to scan the QR Code. Your phone should have internet connectivity to scan QR code.</p>
			<div style="color:red;">
			<p>I am not able to scan the QR code, <a  data-toggle="collapse" href="#mo2f_scanqrcode" aria-expanded="false" >click here </a></p></div>
			<div class="mo2f_collapse" id="mo2f_scanqrcode">
				Follow these instructions below and try again.
				<ol>
					<li>Make sure your desktop screen has enough brightness.</li>
					<li>Open your app and click on Configure button to scan QR Code again.</li>
					<li>If you get cross mark on QR Code then click on 'Refresh QR Code' link.</li>
				</ol>
			</div>
			
			<table class="mo2f_settings_table">
				<a href="#refreshQRCode">Click here to Refresh QR Code.</a>
				<div id="displayQrCode" style="margin-left:250px;"><br /> <?php echo '<img style="width:200px;" src="data:image/jpg;base64,' . $data . '" />'; ?>
				</div>
			</table>
			<br />
			<div id="mobile_registered" >
			<form name="f" method="post" id="mobile_register_form" action="" style="display:none;">
				<input type="hidden" name="option" value="mo_auth_mobile_registration_complete" />
			</form>
			</div>
			<form name="f" method="post" action="" id="mo2f_cancel_form" style="display:none;">
				<input type="hidden" name="option" value="mo2f_cancel_configuration" />
			</form >
			<form name="f" method="post" id="mo2f_refresh_qr_form" action="" style="display:none;">
				<input type="hidden" name="option" value="mo_auth_refresh_mobile_qrcode" />
			</form >
			
			<input type="button" name="back" id="back_to_methods" class="button button-primary button-large" value="Back" />
			
			<br /><br />
		
			<script>
			jQuery('#back_to_methods').click(function(e) {	
					jQuery('#mo2f_cancel_form').submit();
			});
			jQuery('a[href=\"#refreshQRCode\"]').click(function(e) {	
					jQuery('#mo2f_refresh_qr_form').submit();
			});
			jQuery("#configurePhone").hide();
			jQuery("#reconfigurePhone").hide();
			var timeout;
			pollMobileRegistration();
			function pollMobileRegistration()
			{
				var transId = "<?php echo $_SESSION[ 'mo2f_transactionId' ];  ?>";
				var jsonString = "{\"txId\":\""+ transId + "\"}";
				var postUrl = "<?php echo $url;  ?>" + "/moas/api/auth/registration-status";
				jQuery.ajax({
					url: postUrl,
					type : "POST",
					dataType : "json",
					data : jsonString,
					contentType : "application/json; charset=utf-8",
					success : function(result) {
						var status = JSON.parse(JSON.stringify(result)).status;
						if (status == 'SUCCESS') {
							var content = "<br/><div id='success'><img style='width:165px;margin-top:-1%;margin-left:2%;' src='" + "<?php echo plugins_url( 'includes/images/right.png' , __FILE__ );?>" + "' /></div>";
							jQuery("#displayQrCode").empty();
							jQuery("#displayQrCode").append(content);
							setTimeout(function(){jQuery("#mobile_register_form").submit();}, 1000);
						} else if (status == 'ERROR' || status == 'FAILED') {
							var content = "<br/><div id='error'><img style='width:165px;margin-top:-1%;margin-left:2%;' src='" + "<?php echo plugins_url( 'includes/images/wrong.png' , __FILE__ );?>" + "' /></div>";
							jQuery("#displayQrCode").empty();
							jQuery("#displayQrCode").append(content);
							jQuery("#messages").empty();
							
							jQuery("#messages").append("<div class='error mo2f_error_container'> <p class='mo2f_msgs'>An Error occured processing your request. Please try again to configure your phone.</p></div>");
						} else {
							timeout = setTimeout(pollMobileRegistration, 3000);
						}
					}
				});
			}
			jQuery('html,body').animate({scrollTop: jQuery(document).height()}, 800);
</script>
		<?php
	}
	
	function test_mobile_authentication() {
		?>
		
			<h3>Test QR Code Authentication</h3><hr>
			<p>Open your miniOrange <b>Authenticator App</b> and click on <b>'Scan QR code'</b> to scan the QR code. Your phone should have internet connectivity to scan QR code.</p>
			
			<div style="color:red;"><b>I am not able to scan the QR code, <a  data-toggle="collapse" href="#mo2f_testscanqrcode" aria-expanded="false" >click here </a></b></div>
			<div class="mo2f_collapse" id="mo2f_testscanqrcode">
				<br />Follow these instructions below and try again.
				<ol>
					<li>Make sure your desktop screen has enough brightness.</li>
					<li>Open your app and click on Green button (your registered email is displayed on the button) to scan QR Code.</li>
					<li>If you get cross mark on QR Code then click on 'Back' button and again click on 'Test' link.</li>
				</ol>
			</div>
			<br /><br />
			<table class="mo2f_settings_table">
				<div id="qr-success" ></div>
				<div id="displayQrCode" style="margin-left:250px;"><br/><?php echo '<img style="width:165px;" src="data:image/jpg;base64,' . $_SESSION[ 'mo2f_qrCode' ] . '" />'; ?>
				</div>
				
			</table>
			
			<div id="mobile_registered" >
			<form name="f" method="post" id="mo2f_mobile_authenticate_success_form" action="">
				<input type="hidden" name="option" value="mo2f_mobile_authenticate_success" />
			</form>
			<form name="f" method="post" id="mo2f_mobile_authenticate_error_form" action="">
				<input type="hidden" name="option" value="mo2f_mobile_authenticate_error" />
			</form>
			<form name="f" method="post" action="" id="mo2f_cancel_form">
					<input type="hidden" name="option" value="mo2f_cancel_configuration" />
				<input type="submit" name="validate" id="validate" class="button button-primary button-large" value="Back" />
			</form>
			</div>
				
		
			<script>
			var timeout;
			pollMobileValidation();
			function pollMobileValidation()
			{	
				var transId = "<?php echo $_SESSION[ 'mo2f_transactionId' ];  ?>";
				var jsonString = "{\"txId\":\""+ transId + "\"}";
				var postUrl = "<?php echo get_option('mo2f_host_name');  ?>" + "/moas/api/auth/auth-status";
				
				jQuery.ajax({
					url: postUrl,
					type : "POST",
					dataType : "json",
					data : jsonString,
					contentType : "application/json; charset=utf-8",
					success : function(result) {
						var status = JSON.parse(JSON.stringify(result)).status;
						if (status == 'SUCCESS') {
							var content = "<br /><div id='success'><img style='width:165px;margin-top:-1%;margin-left:2%;' src='" + "<?php echo plugins_url( 'includes/images/right.png' , __FILE__ );?>" + "' /></div>";
							jQuery("#displayQrCode").empty();
							jQuery("#displayQrCode").append(content);
							setTimeout(function(){jQuery('#mo2f_mobile_authenticate_success_form').submit();}, 1000);
							
						} else if (status == 'ERROR' || status == 'FAILED') {
							var content = "<br /><div id='error'><img style='width:165px;margin-top:-1%;margin-left:2%;' src='" + "<?php echo plugins_url( 'includes/images/wrong.png' , __FILE__ );?>" + "' /></div>";
							jQuery("#displayQrCode").empty();
							jQuery("#displayQrCode").append(content);
							setTimeout(function(){jQuery('#mo2f_mobile_authenticate_error_form').submit();}, 1000);
						} else {
							timeout = setTimeout(pollMobileValidation, 3000);
						}
					}
				});
			}
			jQuery('html,body').animate({scrollTop: jQuery(document).height()}, 600);
			</script>
		<?php
	}
	function test_soft_token(){	?>
		<h3>Test Soft Token</h3><hr>
		<p>Open your <b>miniOrange Authenticator App</b> and click on <b>Soft Token Tab</b>. Enter the <b>one time passcode</b> shown in App in the textbox below.</p>
			<form name="f" method="post" action="" id="mo2f_test_token_form">
					<input type="hidden" name="option" value="mo2f_validate_soft_token" />
					
								<input class="mo2f_table_textbox" style="width:200px;" autofocus="true" type="text" name="otp_token" required placeholder="Enter OTP" style="width:95%;"/>
								<a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_demo#demo4">Click here to see How To Setup ?</a><br><br>
					<input type="button" name="back" id="back_btn" class="button button-primary button-large" value="Back" />
					<input type="submit" name="validate" id="validate" class="button button-primary button-large" value="Validate OTP" />
					
		    </form>
			<form name="f" method="post" action="" id="mo2f_cancel_form">
					<input type="hidden" name="option" value="mo2f_cancel_configuration" />
			</form>
		<script>
			jQuery('#back_btn').click(function() {	
					jQuery('#mo2f_cancel_form').submit();
			});
		</script>
	<?php } 
	
	function test_google_authenticator($method){
		if($method == 'GOOGLE AUTHENTICATOR'){ ?>
			<h3>Test Google Authenticator</h3><hr>
			<p><b>Enter verification code</b></p>
			<p>Get a verification code from "Google Authenticator" app</p>
		<?php }else{ ?>
			<h3>Test Authy 2-Factor Authentication</h3><hr>
			<p><b>Enter verification code</b></p>
			<p>Get a verification code from "Authy 2-Factor Authentication" app</p>
		<?php } ?>
			<form name="f" method="post" action="" >
					<input type="hidden" name="option" value="mo2f_validate_google_auth_test" />
					
								<input class="mo2f_table_textbox" style="width:200px;" autofocus="true" type="text" name="otp_token" required placeholder="Enter OTP" style="width:95%;"/>
								<br><br>
					<input type="button" name="back" id="back_btn" class="button button-primary button-large" value="Back" />
					<input type="submit" name="validate" id="validate" class="button button-primary button-large" value="Validate OTP" />
					
		    </form>
			<form name="f" method="post" action="" id="mo2f_cancel_form">
					<input type="hidden" name="option" value="mo2f_cancel_configuration" />
			</form>
		<script>
			jQuery('#back_btn').click(function() {	
					jQuery('#mo2f_cancel_form').submit();
			});
		</script>
			
	<?php
	}
	
	function test_otp_over_sms($current_user){	
		
		if (get_user_meta($current_user->ID, 'mo2f_selected_2factor_method',true) == 'SMS'){ ?>
			<h3>Test OTP Over SMS</h3><hr>
				<p>Enter the one time passcode sent to your registered mobile number.</p>
		<?php } else { ?>
			<h3>Test Phone Call Verification</h3><hr>
			<p>You will receive a phone call now. Enter the one time passcode here.</p>
		<?php } ?>
	
			<form name="f" method="post" action="" id="mo2f_test_token_form">
					<input type="hidden" name="option" value="mo2f_validate_otp_over_sms" />
					
								<input class="mo2f_table_textbox" style="width:200px;" autofocus="true" type="text" name="otp_token" required placeholder="Enter OTP" style="width:95%;"/>
								<?php if (get_user_meta($current_user->ID, 'mo2f_selected_2factor_method',true) == 'SMS'){ ?>
									<a href="#resendsmslink">Resend OTP ?</a>
								<?php } else {?>
									<a href="#resendsmslink">Call Again ?</a>
								<?php } ?>
								<br><br>
					<input type="button" name="back" id="back_btn" class="button button-primary button-large" value="Back" />
					<input type="submit" name="validate" id="validate" class="button button-primary button-large" value="Validate OTP" />
					
		    </form>
			<form name="f" method="post" action="" id="mo2f_cancel_form">
					<input type="hidden" name="option" value="mo2f_cancel_configuration" />
			</form>
			<form name="f" method="post" action="" id="mo2f_test_smsotp_form">
				<input type="hidden" name="option" value="mo_2factor_test_otp_over_sms" />
				<input type="hidden" name="mo2f_selected_2factor_method" value="<?php echo get_user_meta($current_user->ID, 'mo2f_selected_2factor_method',true); ?>" 
					id="mo2f_test_2factor_method" />
			</form>	
		
		<script>
			jQuery('#back_btn').click(function() {	
					jQuery('#mo2f_cancel_form').submit();
			});
			jQuery('a[href=\"#resendsmslink\"]').click(function(e) {
				jQuery('#mo2f_test_smsotp_form').submit();
			});
		</script>
	
	<?php } 
	function test_push_notification() {?>
	
			<h3>Test Push Notification</h3><hr>
	<div >
			<br><br>
			<center>
				<h3>A Push Notification has been sent to your phone. <br>We are waiting for your approval...</h3>
				<img src="<?php echo plugins_url( 'includes/images/ajax-loader-login.gif' , __FILE__ );?>" />
			</center>
		<input type="button" name="back" id="back_btn" class="button button-primary button-large" value="Back" style="margin-top:100px;margin-left:10px;"/>
		<br><br>
	</div>
			
			<form name="f" method="post" action="" id="mo2f_cancel_form">
					<input type="hidden" name="option" value="mo2f_cancel_configuration" />
			</form>
			<form name="f" method="post" id="mo2f_push_success_form" action="">
				<input type="hidden" name="option" value="mo2f_out_of_band_success" />
			</form>
			<form name="f" method="post" id="mo2f_push_error_form" action="">
				<input type="hidden" name="option" value="mo2f_out_of_band_error" />
			</form>
		
		<script>
			jQuery('#back_btn').click(function() {	
					jQuery('#mo2f_cancel_form').submit();
			});
			
			var timeout;
			pollMobileValidation();
			function pollMobileValidation()
			{	
				var transId = "<?php echo $_SESSION[ 'mo2f_transactionId' ];  ?>";
				var jsonString = "{\"txId\":\""+ transId + "\"}";
				var postUrl = "<?php echo get_option('mo2f_host_name');  ?>" + "/moas/api/auth/auth-status";
				
				jQuery.ajax({
					url: postUrl,
					type : "POST",
					dataType : "json",
					data : jsonString,
					contentType : "application/json; charset=utf-8",
					success : function(result) {
						var status = JSON.parse(JSON.stringify(result)).status;
						if (status == 'SUCCESS') {
							jQuery('#mo2f_push_success_form').submit();
						} else if (status == 'ERROR' || status == 'FAILED' || status == 'DENIED') {
							jQuery('#mo2f_push_error_form').submit();
						} else {
							timeout = setTimeout(pollMobileValidation, 3000);
						}
					}
				});
			}
						
		</script>
	
	<?php }  function test_out_of_band_email($current_user) {?>
	
			<h3>Test Email Verification</h3><hr>
	<div>
			<br><br>
			<center>
				<h3>A verification email is sent to your registered email. <br>
				We are waiting for your approval...</h3>
				<img src="<?php echo plugins_url( 'includes/images/ajax-loader-login.gif' , __FILE__ );?>" />
			</center>
			
			<input type="button" name="back" id="back_btn" class="button button-primary button-large" value="Back" style="margin-top:100px;margin-left:10px;"/>
	</div>
			
			<form name="f" method="post" action="" id="mo2f_cancel_form">
					<input type="hidden" name="option" value="mo2f_cancel_configuration" />
			</form>
			<form name="f" method="post" id="mo2f_out_of_band_success_form" action="">
				<input type="hidden" name="option" value="mo2f_out_of_band_success" />
			</form>
			<form name="f" method="post" id="mo2f_out_of_band_error_form" action="">
				<input type="hidden" name="option" value="mo2f_out_of_band_error" />
			</form>
		
		<script>
			jQuery('#back_btn').click(function() {	
					jQuery('#mo2f_cancel_form').submit();
			});
			
			var timeout;
			pollMobileValidation();
			function pollMobileValidation()
			{	
				var transId = "<?php echo $_SESSION[ 'mo2f_transactionId' ];  ?>";
				var jsonString = "{\"txId\":\""+ transId + "\"}";
				var postUrl = "<?php echo get_option('mo2f_host_name');  ?>" + "/moas/api/auth/auth-status";
				
				jQuery.ajax({
					url: postUrl,
					type : "POST",
					dataType : "json",
					data : jsonString,
					contentType : "application/json; charset=utf-8",
					success : function(result) {
						var status = JSON.parse(JSON.stringify(result)).status;
						if (status == 'SUCCESS') {
							jQuery('#mo2f_out_of_band_success_form').submit();
						} else if (status == 'ERROR' || status == 'FAILED' || status == 'DENIED') {
							jQuery('#mo2f_out_of_band_error_form').submit();
						} else {
							timeout = setTimeout(pollMobileValidation, 3000);
						}
					}
				});
			}
						
		</script>
	
	<?php }

		function test_kba_authentication($current_user){ ?>
			
			<h3>Test Security Questions( KBA )</h3><hr>
			<p>Please answer the following question.</p>
	
			<form name="f" method="post" action="" id="mo2f_test_kba_form">
				<input type="hidden" name="option" value="mo2f_validate_kba_details" />
					
					<div id="mo2f_kba_content">
						<?php if(isset($_SESSION['mo_2_factor_kba_questions'])){
							echo $_SESSION['mo_2_factor_kba_questions'][0];
						?>
						<br />
						<input class="mo2f_table_textbox" style="width:227px;" type="text" name="mo2f_answer_1" id="mo2f_answer_1" required="true" autofocus="true" pattern="(?=\S)[A-Za-z0-9_@.$#&amp;+-\s]{1,100}" title="Only alphanumeric letters with special characters(_@.$#&amp;+-) are allowed." autocomplete="off" ><br /><br />
						<?php
							echo $_SESSION['mo_2_factor_kba_questions'][1];
						?>
						<br />
						<input class="mo2f_table_textbox" style="width:227px;" type="text" name="mo2f_answer_2" id="mo2f_answer_2" required="true" pattern="(?=\S)[A-Za-z0-9_@.$#&amp;+-\s]{1,100}" title="Only alphanumeric letters with special characters(_@.$#&amp;+-) are allowed." autocomplete="off" ><br /><br />
						<?php 
							}
						?>
					</div>
					<input type="button" name="back" id="back_btn" class="button button-primary button-large" value="Back" />
					<input type="submit" name="validate" id="validate" class="button button-primary button-large" value="Validate Answers" />
					
		    </form>
			<form name="f" method="post" action="" id="mo2f_cancel_form">
					<input type="hidden" name="option" value="mo2f_cancel_configuration" />
			</form>
		<script>
			jQuery('#back_btn').click(function() {	
					jQuery('#mo2f_cancel_form').submit();
			});
		</script>
		<?php
		} 
		
	function show_2_factor_pricing_page($current_user) { ?>
		<div class="mo2f_table_layout">
		<?php echo mo2f_check_if_registered_with_miniorange($current_user); ?>
		<table class="mo2f_pricing_table">
		
		<?php 
			if(!get_option('mo2f_modal_display')){
				modal_display();
				update_option('mo2f_modal_display', 1);
			}
		?>
		
		<h2>Licensing Plans
		<span style="float:right"><input type="button" name="ok_btn" id="ok_btn" class="button button-primary button-large" value="OK, Got It" onclick="window.location.href='admin.php?page=miniOrange_2_factor_settings&mo2f_tab=mobile_configure'" /></span>
		</h2><hr>
		<tr style="vertical-align:top;">
			<td><div class="mo2f_thumbnail mo2f_pricing_free_tab" >
				<h3 class="mo2f_pricing_header">Free</h3>
				<h4 class="mo2f_pricing_sub_header" style="padding-bottom:16px !important;">( You are automatically on this plan )</h4>
				<hr>
				<p class="mo2f_pricing_text">For 1 user - Forever</p><hr>
				<p  class="mo2f_pricing_text" style="padding-bottom:2px;">$0 - Subscription Fees<br /><br /><br /><br /><br /><br /><br /><br /><br /><br /></p>
				<hr>
				<p class="mo2f_pricing_text">Features:</p>
				<p class="mo2f_pricing_text">Limited Authentication Methods<br />
				Remember Device<br>
				Two-Factor for Woocommerce Front End Login<br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br />
				<hr>
				<p class="mo2f_pricing_text">Backup Method:<br />
				Security Questions (KBA)<br/><br />
				</p><hr>
				<p class="mo2f_pricing_text">Basic Support by Email</p>
			</div></td>
			<td><div class="mo2f_thumbnail mo2f_pricing_paid_tab">
				<h3 class="mo2f_pricing_header">Do it yourself</h3>
				<h4 class="mo2f_pricing_sub_header" style="padding-bottom:8px !important;"><a class="button button-primary button-large"
				 onclick="mo2f_upgradeform('wp_2fa_basic_plan')" >Click here to upgrade</a>*</h4>
				<hr>
				<p class="mo2f_pricing_text">For 1+ user</p><hr>
				<p class="mo2f_pricing_text">Yearly Subscription Fees**
					<select class="form-control" style="border-radius:5px;width:250px;">
						<option > 5 users - $15 per year </option> 
						<option > 10 users - $30 per year </option> 
						<option > 20 users - $45 per year </option> 
						<option > 30 users - $60 per year </option>
						<option > 40 users - $75 per year </option>
						<option > 50 users - $90 per year </option>
						<option > 60 users - $100 per year </option>
						<option > 70 users - $110 per year </option> 
						<option > 80 users - $120 per year </option>
						<option > 90 users - $130 per year </option>
						<option > 100 users - $140 per year </option>
						<option > 150 users - $177.5 per year </option> 
						<option > 200 users - $215 per year </option> 	
						<option > 250 users - $245 per year </option>
						<option > 300 users - $275 per year </option>
						<option > 350 users - $300 per year </option> 
						<option > 400 users - $325 per year </option>
						<option > 450 users - $347.5 per year </option>	
						<option > 500 users - $370 per year </option>			
						<option > 600 users - $395 per year </option>
						<option > 700 users - $420 per year </option>
						<option > 800 users - $445 per year </option>
						<option > 900 users - $470 per year </option>	
						<option > 1000 users - $495 per year </option>
						<option > 2000 users - $549 per year </option>	
						<option > 3000 users - $599 per year </option>
						<option > 4000 users - $649 per year </option>
						<option > 5000 users - $699 per year </option>	
						<option > 10000 users - $799 per year </option>
						<option > 20000 users - $999 per year </option>	
					</select>
				</p>
				<p  class="mo2f_pricing_text">SMS Cost <br />(<span style="font-size:10px;">Only applicable if you will use OTP over SMS as authentication method.</span>)<br>
					<select class="form-control" style="border-radius:5px;width:250px;">
					    <option>$5 per 100 OTP + SMS delivery charges</option>
						<option>$15 per 500 OTP + SMS delivery charges</option>
						<option>$22 per 1k OTP + SMS delivery charges</option>
						<option>$30 per 5k OTP + SMS delivery charges</option>
						<option>$40 per 10k OTP + SMS delivery charges</option>
						<option>$90 per 50k OTP + SMS delivery charges</option>
					</select>
				</p>  
				<p class="mo2f_pricing_text ">
					<i><span style="color:#b01a1a">Transaction prices & SMS delivery charges depend on country.</span><br/>
					Lifetime validity.</i>
				</p>
				<hr>
				<p class="mo2f_pricing_text">Features:</p>
				<p class="mo2f_pricing_text">All Authentication Methods***<br />
				Remember Device<br>
				Two-Factor for Woocommerce Front End Login<br>
				Enforce 2FA registration for users<br />
				Manage Registered Device Profiles<br />
				Multi-Site Support <br />
				Custom Redirection<br />
				Customize Email Templates<br />
				Customize SMS Templates<br/>
				Customize Powered By logo<br />
				Customize Security Questions (KBA)<br />
				Enable 2 Factor with various login forms****<br><br>
				</p><hr>
				<p class="mo2f_pricing_text">Backup Method:<br />
				Security Questions (KBA)<br />
				OTP over EMAIL</p>
				<hr>
				<p class="mo2f_pricing_text">Basic Support By Email</p>
			</div></td>
		</td>
		<td><div class="mo2f_thumbnail mo2f_pricing_free_tab">
				<h3 class="mo2f_pricing_header">Premium</h3>
				<h4 class="mo2f_pricing_sub_header" style="padding-bottom:8px !important;"><a class="button button-primary button-large"
				 onclick="mo2f_upgradeform('wp_2fa_premium_plan')" >Click here to upgrade</a>*</h4>
				<hr>
				<p class="mo2f_pricing_text">For 1+ user, Setup and Custom Work</p><hr>
				<p  class="mo2f_pricing_text">Yearly Subscription Fees**
					<select class="form-control" style="border-radius:5px;width:250px;">
						<option > 5 users - $15 per year </option> 
						<option > 10 users - $30 per year </option> 
						<option > 20 users - $45 per year </option> 
						<option > 30 users - $60 per year </option>
						<option > 40 users - $75 per year </option>
						<option > 50 users - $90 per year </option>
						<option > 60 users - $100 per year </option>
						<option > 70 users - $110 per year </option> 
						<option > 80 users - $120 per year </option>
						<option > 90 users - $130 per year </option>
						<option > 100 users - $140 per year </option>
						<option > 150 users - $177.5 per year </option> 
						<option > 200 users - $215 per year </option> 	
						<option > 250 users - $245 per year </option>
						<option > 300 users - $275 per year </option>
						<option > 350 users - $300 per year </option> 
						<option > 400 users - $325 per year </option>
						<option > 450 users - $347.5 per year </option>	
						<option > 500 users - $370 per year </option>			
						<option > 600 users - $395 per year </option>
						<option > 700 users - $420 per year </option>
						<option > 800 users - $445 per year </option>
						<option > 900 users - $470 per year </option>	
						<option > 1000 users - $495 per year </option>
						<option > 2000 users - $549 per year </option>	
						<option > 3000 users - $599 per year </option>
						<option > 4000 users - $649 per year </option>
						<option > 5000 users - $699 per year </option>	
						<option > 10000 users - $799 per year </option>
						<option > 20000 users - $999 per year </option>	
					</select></p>
				
				<p  class="mo2f_pricing_text">SMS Cost<br />(<span style="font-size:10px;">Only applicable if you will use OTP over SMS as authentication method.</span>)<br>
					<select class="form-control" style="border-radius:5px;width:250px;">
					    <option>$5 per 100 OTP + SMS delivery charges</option>
						<option>$15 per 500 OTP + SMS delivery charges</option>
						<option>$22 per 1k OTP + SMS delivery charges</option>
						<option>$30 per 5k OTP + SMS delivery charges</option>
						<option>$40 per 10k OTP + SMS delivery charges</option>
						<option>$90 per 50k OTP + SMS delivery charges</option>
					</select>
				</p>  
				<p class="mo_registration_pricing_text">
					<i><span style="color:#b01a1a">Transaction prices & SMS delivery charges depend on country.</span><br/>
					Lifetime validity.</i>
				</p>
				
				<hr>
				<p class="mo2f_pricing_text">Features:</p>
				<p class="mo2f_pricing_text">All Authentication Methods***<br />
				Remember Device<br>
				Two-Factor for Woocommerce Front End Login<br>
				Enforce 2FA registration for users<br />
				Manage Registered Device Profiles<br />
				Multi-Site Support <br />
				Custom Redirection<br />
				Customize Email Templates<br />
				Customize SMS Templates<br/>
				Customize Powered By logo<br />
				Customize Security Questions (KBA)<br />
				Enable 2 Factor with various login forms****<br />
				End to End 2FA Integration*****<br>
				</p><hr>
				<p class="mo2f_pricing_text">Backup Method:<br />
				Security Questions (KBA)<br />
				OTP over EMAIL</p>
				<hr>
				<p class="mo2f_pricing_text">Premium Support Plans Available</p>
			</div></td>
		</td>
		</tr>
		
		</table>
		<br>
		<h3>* Steps to upgrade to premium plugin -</h3>
		<p>1. You will be redirected to miniOrange Login Console. Enter your password with which you created an account with us and verify your 2nd factor. After that you will be redirected to the payment page.</p>
		<p>2. Enter you card details and complete the payment. On successful payment completion, you will see the link to download the premium plugin.</p>
		<p>3. Once you download the premium plugin, delete the Free plugin from the Wordpress Admin Panel and upload the Premium plugin using zip. </p>
		<br /><hr><br />
		<h3>** If you don't find your required number of users in the dropdown, click on 'Click here to upgrade' button, you will be taken to the Payment Page where you can check the price for  the exact number of users.</h3>
		<p>You can mail us at <a href="mailto:info@miniorange.com"><b>info@miniorange.com</b></a> or submit the support form under User Profile tab to contact us.</p><br /><hr><br />
		<h3>*** All Authentication Methods:</h3><ol> 
		<li>We highly recommend to use phone based authentication methods like Soft Token, QR Code Authentication and Push Notification.</li>
		<li>Setting up knowledge based questions (KBA) as an alternate login method will protect you in case your phone is not working or out of reach. <br /><b><u>What to do in case you are locked out (Its common when you are setting up 2FA for the first time, so please read this).<br /><a  data-toggle="collapse" href="#mo2f_locekd_out" aria-expanded="false" >Click Here to know how to login, in case you are locked out.</a></u></b/>
		<div class="mo2f_collapse" id="mo2f_locekd_out">
			==><b>Rename</b> the plugin by FTP access. Go to <b>wp-content/plugins folder</b> and rename miniorange-2-factor-authentication folder.<br /><br />
		</div>
		</li> 
		<li>OTP over SMS delivery depends on the SMS and SMTP Gateway you choose. There are different levels of these gateway:</li>
			<ul>
				<li><b>Standard Gateway:</b> You may get a lag in the service of SMS and Email.</li>
				<li><b>Premium Gateway:</b> The delivery of SMS will be fast if you choose this gateway. However, we provide a global gateway and you may have a better local gateway. So our experience is that if you want OTP over SMS then the best thing is to go with your own local gateway which is proven and fast in your local area. </li>
				<li><b>Choose your own SMS and SMTP Gateway:</b> We recommend you choose your own SMS and SMTP gateway to send Email and SMS.</li>
			</ul>
		</ol>
		<br /><hr><br />
		<p><b>****</b> The 2 Factor plugin works with various login forms like Woocommerce, Theme My Login and many more. We do not claim that 2 Factor works with all the customized login forms. In such cases, custom work is needed to integrate 2 factor with your customized login page.</p>
		<br/><hr><br>
		<h3>***** End to End 2FA Integration - We will setup a Conference Call / Gotomeeting and do end to end setup for you. We provide services to do the setup on your behalf.
		<h3>10 Days Return Policy -</h3>

		<div>At miniOrange, we want to ensure you are 100% happy with your purchase. If the premium plugin you purchased is not working as advertised and you've attempted to resolve any issues with our support team, which couldn't get resolved, we will refund the whole amount within 10 days of the purchase. Please email us at <a href="mailto:info@miniorange.com"><i>info@miniorange.com</i></a> for any queries regarding the return policy.<br /> 
		If you have any doubts regarding the licensing plans, you can mail us at <a href="mailto:info@miniorange.com"><i>info@miniorange.com</i></a> or submit a query using the support form.</div><br /><br />
		
						
		</div>
		<form style="display:none;" id="mo2fa_loginform" action="<?php echo get_option( 'mo2f_host_name').'/moas/login'; ?>" 
		target="_blank" method="post">
			<input type="email" name="username" value="<?php echo get_option('mo2f_email'); ?>" />
			<input type="text" name="redirectUrl" value="<?php echo get_option( 'mo2f_host_name').'/moas/initializepayment'; ?>" />
			<input type="text" name="requestOrigin" id="requestOrigin"  />
		</form>
		<script>
			function mo2f_upgradeform(planType){
				jQuery('#requestOrigin').val(planType);
				jQuery('#mo2fa_loginform').submit();
			}
		</script>
		
	<?php } ?>