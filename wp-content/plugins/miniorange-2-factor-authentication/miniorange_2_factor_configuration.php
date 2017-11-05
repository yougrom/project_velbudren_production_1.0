<?php
	function mo_2_factor_register($current_user) {
		if(mo_2factor_is_curl_installed()==0){ ?>
			<p style="color:red;">(Warning: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP CURL extension</a> is not installed or disabled)</p>
		<?php
		}
		
		if(version_compare(PHP_VERSION, '5.3.0') < 0){ 
		?>
			<p style="color:red;"><b><span style="font-size:18px;">(Warning:</span></b> Your current PHP version is <?php echo PHP_VERSION; ?>. Some of the functionality of the plugin may not work in this version of PHP. Please upgrade your PHP version to 5.3.0 or above.<br/> You can also write us by submitting a query on the right hand side in our <b>Support Section</b>. )</p>
		<?php
		}
		
		
		$mo2f_active_tab = isset($_GET['mo2f_tab']) ? $_GET['mo2f_tab'] : '2factor_setup';
	
		
		?>
		
		<div id="tab">
			<h2 class="nav-tab-wrapper">
				<a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=2factor_setup" class="nav-tab <?php echo $mo2f_active_tab == '2factor_setup' ? 'nav-tab-active' : ''; ?>" id="mo2f_tab1">
				<?php if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_INITIALIZE_MOBILE_REGISTRATION' || get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){ ?>User Profile <?php }else{ ?> Account Setup <?php } ?></a> 
				<a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mobile_configure" class="nav-tab <?php echo $mo2f_active_tab == 'mobile_configure' ? 'nav-tab-active' : ''; ?>" id="mo2f_tab3">Setup Two-Factor</a>
				<a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_login" class="nav-tab <?php echo $mo2f_active_tab == 'mo2f_login' ? 'nav-tab-active' : ''; ?>" id="mo2f_tab2">Login Settings</a>
				<a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=advance_option" class="nav-tab <?php echo $mo2f_active_tab == 'advance_option' ? 'nav-tab-active' : ''; ?>" id="mo2f_tab2">Premium Options</a>
				<a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_pricing" class="nav-tab <?php echo $mo2f_active_tab == 'mo2f_pricing' ? 'nav-tab-active' : ''; ?>" id="mo2f_tab6">Licensing Plans</a>
				<a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_demo" class="nav-tab <?php echo $mo2f_active_tab == 'mo2f_demo' ? 'nav-tab-active' : ''; ?>" id="mo2f_tab4">How To Setup</a>
			    <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_help" class="nav-tab <?php echo $mo2f_active_tab == 'mo2f_help' ? 'nav-tab-active' : ''; ?>" id="mo2f_tab5">Help & Troubleshooting</a>
				
			</h2>
		</div>

		
		<div class="mo2f_container">
		<div id="messages"></div>
			<table style="width:100%;padding:10px;">
				<tr>
					<td style="width:60%;vertical-align:top;">
						<?php
						/* to update the status of existing customers for adding their user registration status */
							if(get_option( 'mo_2factor_admin_registration_status') == 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' && get_option( 'mo2f_miniorange_admin') == $current_user->ID ){
								update_user_meta($current_user->ID,'mo_2factor_user_registration_with_miniorange','SUCCESS');
							}
						/* ----------------------------------------- */
						
							if($mo2f_active_tab == 'mobile_configure') {
								
									$mo2f_second_factor= mo2f_get_activated_second_factor($current_user);
									
									mo2f_select_2_factor_method($current_user,$mo2f_second_factor); //Configure 2-Factor tab
								
								?>
									<script>
										jQuery(document).ready(function(){
											jQuery("#mo2f_support_table").hide();
										});
									</script>
								<?php
							}else if($mo2f_active_tab == 'mo2f_help'){
								unset($_SESSION[ 'mo2f_google_auth' ]);
								unset($_SESSION[ 'mo2f_authy_keys' ]);
								unset($_SESSION[ 'mo2f_mobile_support' ]);
								mo2f_show_help_and_troubleshooting($current_user);  //Help & Troubleshooting tab
							}else if($mo2f_active_tab == 'mo2f_demo'){
								unset($_SESSION[ 'mo2f_google_auth' ]);
								unset($_SESSION[ 'mo2f_authy_keys' ]);
								unset($_SESSION[ 'mo2f_mobile_support' ]);
								show_2_factor_login_demo($current_user);
							}else if(current_user_can( 'manage_options' ) && $mo2f_active_tab == 'mo2f_login'){
								unset($_SESSION[ 'mo2f_google_auth' ]);
								unset($_SESSION[ 'mo2f_authy_keys' ]);
								unset($_SESSION[ 'mo2f_mobile_support' ]);
								show_2_factor_login_settings($current_user); //Login Settings tab
							}else if(current_user_can( 'manage_options' ) && $mo2f_active_tab == 'advance_option'){
								unset($_SESSION[ 'mo2f_google_auth' ]);
								unset($_SESSION[ 'mo2f_authy_keys' ]);
								unset($_SESSION[ 'mo2f_mobile_support' ]);
								show_2_factor_advanced_options($current_user); //Login Settings tab
							}else if(current_user_can( 'manage_options' ) && $mo2f_active_tab == 'mo2f_pricing'){
								unset($_SESSION[ 'mo2f_google_auth' ]);
								unset($_SESSION[ 'mo2f_authy_keys' ]);
								unset($_SESSION[ 'mo2f_mobile_support' ]);
								show_2_factor_pricing_page($current_user); //Login Settings tab
							}else{
							
								unset($_SESSION[ 'mo2f_google_auth' ]);
								unset($_SESSION[ 'mo2f_mobile_support' ]);
								unset($_SESSION[ 'mo2f_authy_keys' ]);
								if(get_option( 'mo_2factor_admin_registration_status') == 'MO_2_FACTOR_CUSTOMER_REGISTERED_SUCCESS' && get_option( 'mo2f_miniorange_admin') != $current_user->ID){
									if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_OTP_DELIVERED_SUCCESS' || get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_OTP_DELIVERED_FAILURE'){
										mo2f_show_user_otp_validation_page();  // OTP over email validation page
									} else if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_INITIALIZE_MOBILE_REGISTRATION'){  //displaying user profile
										$mo2f_second_factor = mo2f_get_activated_second_factor($current_user);
										mo2f_show_instruction_to_allusers($current_user,$mo2f_second_factor);
									} else if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){
										$mo2f_second_factor = mo2f_get_activated_second_factor($current_user);
										mo2f_show_instruction_to_allusers($current_user,$mo2f_second_factor);  //displaying user profile	
									}else{
										show_user_welcome_page($current_user);  //Landing page for additional admin for registration
									}
								}
								else{
								
									if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_OTP_DELIVERED_SUCCESS' || get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_OTP_DELIVERED_FAILURE'){
										mo2f_show_otp_validation_page($current_user);  // OTP over email validation page for admin
									} else if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_INITIALIZE_MOBILE_REGISTRATION'){  //displaying user profile
										$mo2f_second_factor = mo2f_get_activated_second_factor($current_user);
										mo2f_show_instruction_to_allusers($current_user,$mo2f_second_factor);
									} else if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){
										$mo2f_second_factor = mo2f_get_activated_second_factor($current_user);
										mo2f_show_instruction_to_allusers($current_user,$mo2f_second_factor);  //displaying user profile
										
									}else if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_VERIFY_CUSTOMER') {
										mo2f_show_verify_password_page();  //verify password page
									} else if(!mo2f_is_customer_registered()){
										delete_option('password_mismatch');
										mo2f_show_new_registration_page($current_user); //new registration page
									} 
								}
							
							}
						?>
					</td>
					<td style="vertical-align:top;padding-left:1%;" id="mo2f_support_table">
						<?php if(!($mo2f_active_tab == 'mobile_configure' || $mo2f_active_tab == 'mo2f_pricing')) {echo mo2f_support(); }?>	
					</td>
				</tr>
			</table>
		</div>
		<?php
	}
	
	function mo2f_show_new_registration_page($current_user) {
		?>
		
		<!-- Modal -->
	<div id="clefMigration" class="mo2f_modal mo2f_modal_inner fade" role="dialog">
		<div class="mo2f_modal-dialog">
			<!-- Modal content-->
			<div class="login mo_customer_validation-modal-content" style="width:660px !important;">
				<div class="mo2f_modal-header">
					<button type="button" class="mo2f_close" data-dismiss="modal">&times;</button>
					<h2 class="mo2f_modal-title">Follow these steps if you are migrating from Clef.</h2>
				</div>
				<div class="mo2f_modal-body">
					<div class="mo2f_help_container">
						<div id="myCarouse_first" class="mo2f_carousel slide" data-ride="carousel" >
							<ol class="mo2f_carousel-indicators">
								<li data-target="#myCarouse_first" data-slide-to="0" class="active"></li>
								<li data-target="#myCarouse_first" data-slide-to="1"></li>
								<li data-target="#myCarouse_first" data-slide-to="2"></li>
								<li data-target="#myCarouse_first" data-slide-to="3"></li>
								<li data-target="#myCarouse_first" data-slide-to="4"></li>
							</ol>
							<div class="mo2f_carousel-inner" role="listbox">
								<div class="item active">
									<center><p><b>Step 1.</b> Enter your Email to setup the QR Code.</p></center>
									<img class="first-slide" style="padding-left:3%;" src="<?php echo plugins_url('includes/images/help/step1.png', __FILE__ ) ?>" alt="First slide">
								 </div>
								<div class="item">
									<center><p><b>Step 2.</b> Enter the OTP to verify your email</p></center>
									<img class="first-slide" style="padding-left:3%;" src="<?php echo plugins_url('includes/images/help/step2.png', __FILE__ ) ?>" alt="First slide">
								</div>
								<div class="item">
									<center><p><b>Step 3.</b> Select QR Code radio button to configure the authentication method</p></center>
									<img class="first-slide" style="padding-left:3%;" src="<?php echo plugins_url('includes/images/help/step3.png', __FILE__ ) ?>" alt="First slide">
								</div>
								<div class="item">
									<center><p><b>Step 4.</b> Download the miniOrange Authenticator App and Click on Configure button</p></center>
									<img class="first-slide" style="padding-left:3%;" src="<?php echo plugins_url('includes/images/help/step4.png', __FILE__ ) ?>" alt="First slide">
								</div>
								<div class="item">
									<center><p><b>Step 5.</b> Scan the QR Code from miniOrange Authenticator App and you are done.</p></center>
									<img class="first-slide" style="padding-left:3%;" src="<?php echo plugins_url('includes/images/help/step5.png', __FILE__ ) ?>" alt="First slide">
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="mo2f_modal-footer">
					<button type="button" class="button button-primary" data-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
	</div>
			<!--Register with miniOrange-->
			<form name="f" method="post" action="">
				<input type="hidden" name="option" value="mo_auth_register_customer" />
				<div class="mo2f_table_layout">
					<h3><span>Register with miniOrange</span><span style="float:right;color:red;"><button type="button" class="button button-primary button-large" data-toggle="modal" data-target="#clefMigration">Migrating From Clef?</button></span></h3><hr>
					<div id="panel1">
						<div><b>Please enter a valid email id that you have access to. You will be able to move forward after verifying an OTP that we will be sending to this email. <a href="#mo2f_account_exist">Already registered with miniOrange?</a></b></div>
						<p class="float-right"><font color="#FF0000">*</font> Indicates Required Fields</p>
						<table class="mo2f_settings_table">
							<tr>
							<td><b><font color="#FF0000">*</font>Email :</b></td>
							<td><input class="mo2f_table_textbox" type="email" name="email" required placeholder="person@example.com" value="<?php if(get_option('mo2f_email')){echo get_option('mo2f_email');}else{echo $current_user->user_email;}?>"/></td>
							</tr>
							<tr>
								<td><b><font color="#FF0000">*</font>Company/Organisation:</b></td>
								<td><input class="mo2f_table_textbox" type="text" name="company"
									required placeholder="Your company name"
									value="<?php echo (get_option('mo2f_admin_company') == '') ? site_url() : get_option('mo2f_admin_company');?>" /></td>
							</tr>
							<tr>
								<td><b>First Name:</b></td>
								<td><input class="mo2f_table_textbox" type="text" name="first_name"
									placeholder="First Name"
									value="<?php echo (get_option('mo2f_admin_first_name') == '') ? $current_user->first_name : get_option('mo2f_admin_first_name');?>" /></td>
							</tr>
							<tr>
								<td><b>Last Name:</b></td>
								<td><input class="mo2f_table_textbox" type="text" name="last_name"
									placeholder="Last Name"
									value="<?php echo (get_option('mo2_admin_last_name') == '') ? $current_user->last_name : get_option('mo2_admin_last_name');?>" /></td>
							</tr>

							<tr>
							<td><b>&nbsp;&nbsp;Phone number :</b></td>
							 <td><input class="mo2f_table_textbox" style="width:100% !important;" type="text" name="phone" pattern="[\+]?([0-9]{1,4})?\s?([0-9]{7,12})?" id="phone" autofocus="true"  value="<?php echo get_user_meta($current_user->ID,'mo2f_user_phone',true);?>" />
							 This is an optional field. We will contact you only if you need support.</td>
							</tr>
							
							<tr>
							<td><b><font color="#FF0000">*</font>Password :</b></td>
							 <td><input class="mo2f_table_textbox" type="password" required name="password" placeholder="Choose your password with minimun 6 characters" /></td>
							</tr>
							<tr>
							<td><b><font color="#FF0000">*</font>Confirm Password :</b></td>
							 <td><input class="mo2f_table_textbox" type="password" required name="confirmPassword" placeholder="Confirm your password with minimum 6 characters" /></td>
							</tr>
							<tr><td>&nbsp;</td></tr>
						  <tr>
							<td>&nbsp;</td>
							<td><input type="submit" name="submit" value="Submit" class="button button-primary button-large" /></td>
						  </tr>
						</table>
						<br>
						
					</div>
				</div>
			</form>
			<form name="f" method="post" action="" id="mo2f_verify_customerform" >
				<input type="hidden" name="option" value="mo2f_goto_verifycustomer">
			</form>
						
			<script>
				jQuery("#phone").intlTelInput();
				jQuery('a[href=\"#mo2f_account_exist\"]').click(function(e) {	
					jQuery('#mo2f_verify_customerform').submit();
				});
			</script>
		<?php
	}
	
	function mo2f_show_otp_validation_page($current_user){
	?>
		<!-- Enter otp -->
		
		<div class="mo2f_table_layout">
			<h3>Validate OTP</h3><hr>
			<div id="panel1">
				<table class="mo2f_settings_table">
					<form name="f" method="post" id="mo_2f_otp_form" action="">
						<input type="hidden" name="option" value="mo_2factor_validate_otp" />
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
						<input type="hidden" name="option" value="mo_2factor_gobackto_registration_page"/>
							<input type="submit" name="mo2f_goback" id="mo2f_goback" value="Back" class="button button-primary button-large" /></td>
						</form>
						</td>
						</tr>
						<form name="f" method="post" action="" id="resend_otp_form">
							<input type="hidden" name="option" value="mo_2factor_resend_otp"/>
						</form>
						
				</table>
				<br>
				<hr>

				<h3>I did not recieve any email with OTP . What should I do ?</h3>
				<form id="phone_verification" method="post" action="">
					<input type="hidden" name="option" value="mo_2factor_phone_verification" />
					 If you can't see the email from miniOrange in your mails, please check your <b>SPAM Folder</b>. If you don't see an email even in SPAM folder, verify your identity with our alternate method.
					 <br><br>
						<b>Enter your valid phone number here and verify your identity using one time passcode sent to your phone.</b>
						<br><br>
						<table>
						<tr>
						<td>
						<input class="mo2f_table_textbox" required autofocus="true" type="text" name="phone_number" id="phone" placeholder="Enter Phone Number" value="<?php echo get_user_meta( $current_user->ID,'mo2f_user_phone',true); ?>" pattern="[\+]?[0-9]{1,4}\s?[0-9]{7,12}" title="Enter phone number without any space or dashes."/>
						</td>
						<td>
						<a href="#resendsmsotplink">Resend OTP ?</a>
						</td>
						</tr>
						</table>
						<br><input type="submit" value="Send OTP" class="button button-primary button-large" />
				
				</form>
				<br>
				<h3>What is an OTP ?</h3>
				<p>OTP is a one time passcode ( a series of numbers) that is sent to your email or phone number to verify that you have access to your email account or phone. </p>
				</div>
				<div>	
					<script>
						jQuery("#phone").intlTelInput();
						jQuery('a[href=\"#resendotplink\"]').click(function(e) {
							jQuery('#resend_otp_form').submit();
						});
						jQuery('a[href=\"#resendsmsotplink\"]').click(function(e) {
							jQuery('#phone_verification').submit();
						});
					</script>
		
			<br><br>
			</div>
			
			
						
		</div>
					
	<?php
	}
	
	function miniorange_2_factor_user_roles($current_user,$random_mo_key) {
			 
		global $wp_roles;
		if (!isset($wp_roles))
			$wp_roles = new WP_Roles();
		
		print '<div>';
		if($random_mo_key){
			foreach($wp_roles->role_names as $id => $name) {	
				$setting = get_option('mo2fa_'.$id);
				if($id == 'administrator'){ ?>
					<input type="checkbox" name="<?php echo 'mo2fa_'.$id; ?>" value="1" <?php checked($setting == 1); if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){}else{ echo 'disabled';} ?> /><?php echo $name; ?><span style="float:right;">You can enable the following roles in the premium plugin.</span><br />
				<?php }else{ ?>
					<div class="mo2f_grayed_out">
					<input type="checkbox" name="<?php echo 'mo2fa_'.$id; ?>" value="1" <?php checked($setting == 1); echo 'disabled' ?> /><?php echo $name; ?></div>
				<?php }
			}
		}else{
			foreach($wp_roles->role_names as $id => $name) {	
				$setting = get_option('mo2fa_'.$id);
			?>
				<input type="checkbox" name="<?php echo 'mo2fa_'.$id; ?>" value="1" <?php checked($setting == 1); if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){}else{ echo 'disabled';} ?> /><?php echo $name; ?><br />
			<?php
			}
		}
		print '</div>';
	}
	
	function show_2_factor_login_settings($current_user) {
				$opt = (array) get_option('mo2f_auth_methods_for_users');
				$random_mo_key = get_option('mo2f_new_customer');
		?>
	<div class="mo2f_table_layout">
			<?php echo mo2f_check_if_registered_with_miniorange($current_user); ?>
				
			    <form name="f"  id="login_settings_form" method="post" action="">
				<input type="hidden" name="option" value="mo_auth_login_settings_save" />
				<span>
				<h3>Select Roles to enable 2-Factor
				<input type="submit" name="submit" value="Save Settings" style="float:right;" class="button button-primary button-large" <?php 
				if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS' ){ } else{ echo 'disabled' ; } ?> /></h3><span>
				<hr><br>
				
				<?php  echo miniorange_2_factor_user_roles($current_user,$random_mo_key); ?>
				<br>
				<div id="mo2f_note"><b>Note:</b> Selecting the above roles will enable 2-Factor for all users associated with that role.Users of the selected role who have not setup their 2-Factor will be able to setup 2 factor during inline registration.</div>
				<br>
				
				<h3>Select the specific set of authentication methods for your users.<?php echo $random_mo_key ? '<span style="float:right;font-size: 13px;"><a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_pricing"><b>PREMIUM*</b></a></span>' : '' ?></h3><hr><br />
				
				<div class="<?php echo $random_mo_key ? 'mo2f_grayed_out' : '' ?>">
				<table><tbody>
				<tr>
				<td>

				<input type='checkbox' name='mo2f_authmethods[]'  value='OUT OF BAND EMAIL' <?php echo (in_array("OUT OF BAND EMAIL", $opt)) ? 'checked="checked"' : '';  if(!$random_mo_key && get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){}else{ echo 'disabled';} ?> />Email Verification&nbsp;&nbsp;
				</td>
				<td>
				<input type='checkbox' name='mo2f_authmethods[]'  value='SMS' <?php echo (in_array("SMS", $opt)) ? 'checked="checked"' : '';  if(!$random_mo_key && get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){}else{ echo 'disabled';} ?> />OTP Over SMS&nbsp;&nbsp;
				</td>
				<td>
				<input type='checkbox' name='mo2f_authmethods[]'  value='PHONE VERIFICATION' <?php echo (in_array("PHONE VERIFICATION", $opt)) ? 'checked="checked"' : '';  if(!$random_mo_key && get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){}else{ echo 'disabled';} ?> />Phone Call Verification&nbsp;&nbsp;
				</td>
				</tr>
				
				<tr>
				<td>
				<input type='checkbox' name='mo2f_authmethods[]'  value='SOFT TOKEN' <?php echo (in_array("SOFT TOKEN", $opt)) ? 'checked="checked"' : '';  if(!$random_mo_key && get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){}else{ echo 'disabled';} ?> />Soft Token&nbsp;&nbsp;
				</td>
				<td>
				<input type='checkbox' name='mo2f_authmethods[]'  value='MOBILE AUTHENTICATION' <?php echo (in_array("MOBILE AUTHENTICATION", $opt)) ? 'checked="checked"' : '';  if(!$random_mo_key && get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){}else{ echo 'disabled';} ?> />QR Code Authentication&nbsp;&nbsp;
				</td>
				<td>
				<input type='checkbox' name='mo2f_authmethods[]'  value='PUSH NOTIFICATIONS' <?php echo (in_array("PUSH NOTIFICATIONS", $opt)) ? 'checked="checked"' : '';  if(!$random_mo_key && get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){}else{ echo 'disabled';} ?> />Push Notifications&nbsp;&nbsp;
				</td>
				</tr>
				
				<tr>
				<td>
				<input type='checkbox' name='mo2f_authmethods[]'  value='GOOGLE AUTHENTICATOR' <?php echo (in_array("GOOGLE AUTHENTICATOR", $opt)) ? 'checked="checked"' : '';  if(!$random_mo_key && get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){}else{ echo 'disabled';} ?> />Google Authenticator&nbsp;&nbsp;
				</td>
				<td>
				<input type='checkbox' name='mo2f_authmethods[]'  value='AUTHY 2-FACTOR AUTHENTICATION' <?php echo (in_array("AUTHY 2-FACTOR AUTHENTICATION", $opt)) ? 'checked="checked"' : '';  if(!$random_mo_key && get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){}else{ echo 'disabled';} ?> />AUTHY 2-FACTOR AUTHENTICATION&nbsp;&nbsp;
				</td>
				<td>
				<input type='checkbox' name='mo2f_authmethods[]'  value='KBA' <?php echo (in_array("KBA", $opt)) ? 'checked="checked"' : '';  if(!$random_mo_key && get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){}else{ echo 'disabled';} ?> />Security Questions (KBA)&nbsp;&nbsp;
				</td>
				</tr>
				</tbody>
				</table>
				
				 <br><br><div id="mo2f_note"><b>Note:</b> You can select which Two Factor methods you want to enable for your users. By default all Two Factor methods are enabled for all users of the role you have selected above.</div>
				 
				</div>
				<br>
				<h3>Invoke Inline Registration to setup 2nd factor for users.<?php echo  $random_mo_key ? '<span style="float:right;font-size: 13px;"><a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_pricing" ><b>PREMIUM*</b></a></span>' : ''; ?></h3><hr><br />
				
				<div class="<?php echo $random_mo_key ? 'mo2f_grayed_out' : '' ?>">
				
				 <input type="radio" name="mo2f_inline_registration" value="1" <?php checked( get_option('mo2f_inline_registration') == 1 ); 
				 if(!$random_mo_key && get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){}else{ echo 'disabled';} ?> />
				 Enforce 2 Factor registration for users at login time.&nbsp;&nbsp;
				 <input type="radio" name="mo2f_inline_registration" value="0" <?php checked( get_option('mo2f_inline_registration') == 0 ); 
				 if(!$random_mo_key && get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){}else{ echo 'disabled';} ?> />
				 Skip 2 Factor registration at login.
				 <br><br>
				 <div id="mo2f_note"><b>Note:</b> If this option is enabled then users have to setup their two-factor account forcefully during their login. By selecting second option, you will provide your users to skip their two-factor setup during login.</div>
				</div>
				 <br />
				 <h3>Mobile Support</h3><hr><br>
				 <input type="checkbox" name="mo2f_enable_mobile_support" value="1" <?php checked( get_option('mo2f_enable_mobile_support') == 1 ); 
				 if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){}else{ echo 'disabled';} ?> />
				 Enable Mobile Support for users.<br /><br />
				 <div id="mo2f_note"><b>Note:</b> If this option is enabled then Security Questions (KBA) will be invoked as 2nd factor during login through mobile browsers.</div>
				 <br />
				 
				
				
				
				<h3>Select Login Screen Options</h3><hr><br>
				<input type="radio"   name="mo2f_login_policy"  value="1"
						<?php checked( get_option('mo2f_login_policy')); 
						if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){}else{ echo 'disabled';} ?> />
						Login with password + 2nd Factor <span style="color:red">(Recommended)</span>&nbsp;&nbsp;
				<br><br>
				<div id="mo2f_note"><b>Note:</b> By default 2nd Factor is enabled after password authentication. If you do not want to remember passwords anymore and just login with 2nd Factor, please select 2nd option.</div>
				<br>
				
				<div style="margin-left:6%;" >
				 <input type="checkbox" id="mo2f_deviceid_enabled" name="mo2f_deviceid_enabled" value="1" <?php checked( get_option('mo2f_deviceid_enabled') == 1 ); 
				 if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){}else{ echo 'disabled';} ?> />Enable '<b>Remember device</b>' option <br /><span style="color:red;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Applicable only with <i>Login with password + 2nd Factor)</i></span><br />
				 <br />
				 <div id="mo2f_note"><b>Note:</b> Checking this option will display an option '<b>Remember this device</b>' on 2nd factor screen. In the next login from the same device, user will bypass 2nd factor, i.e. user will be logged in through username + password only.</div>
				</div>
				 
				<br>
				
				<input type="radio"   name="mo2f_login_policy"  value="0"
						<?php checked( !get_option('mo2f_login_policy')); 
						if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){}else{ echo 'disabled';} ?> />
						Login with 2nd Factor only <span style="color:red">(No password required.)</span> &nbsp;<a class="btn btn-link" data-toggle="collapse" href="#preview1" aria-expanded="false">See preview</a>
				<br>
				<div class="mo2f_collapse" id="preview1" style="height:300px;">
					<center><br>
					<img style="height:300px;" src="https://auth.miniorange.com/moas/images/help/login-help-1.png" >
					</center>
				 </div> 
			    <br><div id="mo2f_note"><b>Note:</b> Checking this option will add login with your phone button below default login form. Click above link to see the preview.</div>
				<div id="loginphonediv" hidden>	<br>
				<input type="checkbox" id="mo2f_loginwith_phone" name="mo2f_loginwith_phone" value="1" <?php checked( get_option('mo2f_show_loginwith_phone') == 1 ); 
				if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){}else{ echo 'disabled';} ?> /> 
				I want to hide default login form. &nbsp;<a class="btn btn-link" data-toggle="collapse" href="#preview2" aria-expanded="false">See preview</a>
				<br>
				<div class="mo2f_collapse" id="preview2" style="height:300px;">
					<center><br>
					<img style="height:300px;" src="https://auth.miniorange.com/moas/images/help/login-help-3.png" >
					</center>
				 </div> 
				<br><div id="mo2f_note"><b>Note:</b> Checking this option will hide default login form and just show login with your phone. Click above link to see the preview.</div>
			
				 </div>
				 <br>
				 <h3>What happens if my phone is lost, discharged or not with me</h3><hr>
				 <br>
				 <input type="checkbox" id="mo2f_forgotphone" name="mo2f_forgotphone" value="1" <?php checked( get_option('mo2f_enable_forgotphone') == 1 ); 
				 if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){}else{ echo 'disabled';} ?> />
				 Enable Forgot Phone.<?php echo $random_mo_key ? '<span style="color:red;font-size:20px;"><b>**</b></span>' : '';?>
				 <span style="color:red;float:right;">( If you disable this checkbox, then users will not get this option.)</span><br />
				 <br /><div id="mo2f_note"><b>Note:</b>This option will provide you alternate way of login in case your phone is lost, discharged or not with you.</div>
				 <?php echo $random_mo_key ? '<span><b>**This option will make you login through backup method.In the free version of plugin, Security Questions (KBA) will be backup method. In the premium version of the plugin, Security Questions (KBA) and OTP over Email will be backup method.</b><span>' : '';?>
				<br><br />
				
				<h3>XML-RPC Settings</h3>
				<hr><br>
				Enabling this option will decrease your overall login security. Users will be able to login through external applications which support XML-RPC without authenticating from miniOrange. <b>Please keep it unchecked.</b><br /><br />
				<input type="checkbox" id="mo2f_enable_xmlrpc" name="mo2f_enable_xmlrpc" value="1" <?php checked( get_option('mo2f_enable_xmlrpc') == 1 ); 
				 if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){}else{ echo 'disabled';} ?> />
				Enable XML-RPC Login.
				
				<!--(Application Specific Password)
				<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<div id="mo2f_xmlrpc_password" >
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<input name="app_password" id="app_password" readonly="readonly" value="**** **** **** ****" type="text" size="25">
					<input name="app_createpassword" id="app_createpassword" value="Create new password" type="button" class="button">
					
					<span class="description" id="app_passworddesc" style="display: none;">  Password is not stored in cleartext, this is your only chance to see it.</span>
				</div>
				
				<br /><br /><div id="mo2f_note"><b>Note:</b> Enable this option in case you want to use the plugin with any third party applications like WordPress application.</div>
				-->
				<br/><br />
				
				<h3>Enable Two-Factor plugin</h3>
				<hr>
				 <br>
				 <input type="checkbox" id="mo2f_activate_plugin" name="mo2f_activate_plugin" value="1" <?php checked( get_option('mo2f_activate_plugin') == 1 ); 
				 if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS'){}else{ echo 'disabled';} ?> />
				 Enable Two-Factor plugin. <span style="color:red;">( If you disable this checkbox, Two-Factor plugin will not invoke for any user during login.)</span><br />
				 <br /><div id="mo2f_note"><b>Note:</b> Disabling this option will allow all users to login with their username and password.Two-Factor will not invoke during login.</div>
				<br>
				
				<br>
				<input type="submit" name="submit" value="Save Settings" class="button button-primary button-large" <?php 
				if(get_user_meta($current_user->ID,'mo_2factor_user_registration_status',true) == 'MO_2_FACTOR_PLUGIN_SETTINGS' ){ } else{ echo 'disabled' ; } ?> />
				<br /><br />
				<?php echo $random_mo_key ? '<div><b>*</b>These are premium features. You need to upgrade the plugin to use these features.</div>' : '' ?>
				<br /></br>
			</form>
			<script>
				
				if(jQuery("input[name=mo2f_login_policy]:radio:checked").val() == 0){
					jQuery('#loginphonediv').show();
				}
				jQuery("input[name=mo2f_login_policy]:radio").change(function () {
					if (this.value == 1) {
						jQuery('#loginphonediv').hide();
					}else{
						jQuery('#loginphonediv').show();
					}
				});
				
				<?php  
				if( isset( $_REQUEST['true'] ) && get_option( 'mo2f_msg_counter') == 1 ){ 
				$logouturl= wp_login_url() . '?action=logout';
				
				?>
				jQuery("#messages").append("<div class='updated notice is-dismissible mo2f_success_container'> <p class='mo2f_msgs'>If you are OK with default settings. <a href=<?php echo  $logouturl; ?>><b>Click Here</b></a> to logout and try login with 2-Factor.</p></div>");
				<?php } ?>
				
			</script>
			<!--<script>
				if(jQuery('input[name="mo2f_enable_xmlrpc"]:checked').length == 0){
					jQuery('#mo2f_xmlrpc_password').hide();
				}
				var checker = document.getElementById('mo2f_enable_xmlrpc');
				var generatebtn = document.getElementById('app_createpassword');
				checker.onchange = function() {
				  generatebtn.disabled = !this.checked;
				  if(this.checked){
					jQuery('#mo2f_xmlrpc_password').show();
				  }else{
					jQuery('#mo2f_xmlrpc_password').hide(); 
				  }
				};
			</script>
			<script type ="text/javascript">
				var Appnonce= <?php echo wp_create_nonce('Authenticatoraction');?> ';
				
				jQuery("#app_createpassword").on('click',function() {
					
					var data=new Object();
					data['action']	= 'Authenticator_action';
					data['nonce']	= Appnonce;
					data['save']	= 1;
					
					var url = '<?php echo site_url(); ?>/?option=generatepassword';
					
					jQuery.post(url, data,function(response) {
						jQuery('#app_password').val(response['new-secret'].match(new RegExp(".{0,4}","g")).join(' '));
						jQuery('#app_passworddesc').show();
					});  	
				});
	
			</script>-->	
		</div>

	<?php
	}

	function mo2f_show_verify_password_page() {
		?>
			<!--Verify password with miniOrange-->
			<form name="f" method="post" action="">
			<input type="hidden" name="option" value="mo_auth_verify_customer" />
			<div class="mo2f_table_layout">
			<h3>Login with miniOrange</h3><hr>
			<div id="panel1">
			<p><b>It seems you already have an account with miniOrange. Please enter your miniOrange email and password. <a href="#forgot_password">Click here if you forgot your password ?</a></b></p>
			<br/>
			<table class="mo2f_settings_table">
				<tr>
				<td><b><font color="#FF0000">*</font>Email:</b></td>
				<td><input class="mo2f_table_textbox" type="email"  name="email" id="email" required placeholder="person@example.com" value="<?php echo get_option('mo2f_email');?>"/></td>
				</tr>
				<tr>
				<td><b><font color="#FF0000">*</font>Password:</b></td>
				 <td><input class="mo2f_table_textbox" type="password" name="password" required placeholder="Enter your miniOrange password" /></td>
				</tr>
				<tr><td colspan="2">&nbsp;</td></tr>
				<tr>
				<td>&nbsp;</td>
				<td>
				<input type="button" name="mo2f_goback" id="mo2f_go_back" value="Back" class="button button-primary button-large" />
					
				<input type="submit" name="submit" value="Submit" class="button button-primary button-large" /></td>
					
			  </tr>
			
			</table>
		
			</div><br><br>
			</div>
			</form>
			<form name="f" method="post" action="" id="gobackform">
					<input type="hidden" name="option" value="mo_2factor_gobackto_registration_page"/>
			</form>
			<form name="f" method="post" action="" id="forgotpasswordform">
					<input type="hidden" name="email" id="hidden_email" />
					<input type="hidden" name="option" value="mo_2factor_forgot_password"/>
			</form>
			<script>
				jQuery('#mo2f_go_back').click(function(){
					jQuery('#gobackform').submit();
				});
				jQuery('a[href=\"#forgot_password\"]').click(function(){
					var email = jQuery('#email').val();
					jQuery('#hidden_email').val(email);
					jQuery('#forgotpasswordform').submit();
				});
			</script>
	<?php	}
?>