<?php 
   function mo2f_collect_device_attributes_handler($redirect_to = null){
   	?>
<html>
   <head>
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <?php
         echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>';
         ?>
   </head>
   <body>
      <div style="text-align:center;">
         <form id="morba_loginform" method="post" >
            <h1>Please wait...</h1>
            <img src="<?php echo plugins_url( 'includes/images/ajax-loader-login.gif' , __FILE__ );?>" />
            <?php 
               if(get_site_option('mo2f_deviceid_enabled')){
               ?>
            <p><input type="hidden" id="miniorange_rba_attribures" name="miniorange_rba_attribures" value="" /></p>
            <?php
               echo '<script src="' . plugins_url('includes/js/rba/js/jquery-1.9.1.js', __FILE__ ) . '" ></script>';
               echo '<script src="' . plugins_url('includes/js/rba/js/jquery.flash.js', __FILE__ ) . '" ></script>';
               echo '<script src="' . plugins_url('includes/js/rba/js/ua-parser.js', __FILE__ ) . '" ></script>';
               echo '<script src="' . plugins_url('includes/js/rba/js/client.js', __FILE__ ) . '" ></script>';
               echo '<script src="' . plugins_url('includes/js/rba/js/device_attributes.js', __FILE__ ) . '" ></script>';
               echo '<script src="' . plugins_url('includes/js/rba/js/swfobject.js', __FILE__ ) . '" ></script>';
               echo '<script src="' . plugins_url('includes/js/rba/js/fontdetect.js', __FILE__ ) . '" ></script>';
               echo '<script src="' . plugins_url('includes/js/rba/js/murmurhash3.js', __FILE__ ) . '" ></script>';
               echo '<script src="' . plugins_url('includes/js/rba/js/miniorange-fp.js', __FILE__ ) . '" ></script>';
               }
               ?>
            <input type="hidden" name="miniorange_attribute_collection_nonce" value="<?php echo wp_create_nonce('miniorange-2-factor-login-attribute-collection-nonce'); ?>" />
            <input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>" />
	    </form>
      </div>
   </body>
</html>
<?php
   }
   
   function miniorange_get_user_role($current_user){
   	$current_roles = array();
   	$current_roles = $current_user->roles;
   	return $current_roles;
   }
   
   function miniorange_check_if_2fa_enabled_for_roles($current_roles){
   	if(empty($current_roles)){
   	    return 0;	
   	}
   	
   	foreach( $current_roles as $value )
   	{	
   		if(get_site_option('mo2fa_'.$value))
   		{
   			return 1;
   		}
   	}
   	return 0;
   }
   
   function redirect_user_to($user, $redirect_to){
   	
   	$roles = $user->roles;
          $current_role = array_shift($roles);
   	$redirectUrl = isset($redirect_to) && !empty($redirect_to) ? $redirect_to : null;
   	if($current_role == 'administrator'){
   		$redirectUrl = empty($redirectUrl) ? admin_url() : $redirectUrl;
   		wp_redirect( $redirectUrl );
   	}else{
   		$redirectUrl = empty($redirectUrl) ? home_url() : $redirectUrl;
   		wp_redirect( $redirectUrl);
   	}
   }
   
   
   
   function mo2f_register_profile($email,$deviceKey,$mo2f_rba_status){
   	
   	if(isset($deviceKey) && $deviceKey == 'true'){
   		if($mo2f_rba_status['status'] == 'WAIT_FOR_INPUT' && $mo2f_rba_status['decision_flag']){
   			$rba_profile = new Miniorange_Rba_Attributes();
   			$rba_response = json_decode($rba_profile->mo2f_register_rba_profile($email,$mo2f_rba_status['sessionUuid']),true); //register profile
   			return true;
   		}else{
   			return false;
   		}
   	}
   	return false;
   }
   
   function mo2f_collect_attributes($email,$attributes){
   	if(get_option('mo2f_deviceid_enabled')){
   		$rba_attributes = new Miniorange_Rba_Attributes();
   		$rba_response = json_decode($rba_attributes->mo2f_collect_attributes($email,$attributes),true); //collect rba attributes
   		if(json_last_error() == JSON_ERROR_NONE){
   			if($rba_response['status'] == 'SUCCESS'){ //attribute are collected successfully
   				$sessionUuid = $rba_response['sessionUuid'];
   				$rba_risk_response = json_decode($rba_attributes->mo2f_evaluate_risk($email,$sessionUuid),true); // evaluate the rba risk
   				if(json_last_error() == JSON_ERROR_NONE){
   					if($rba_risk_response['status'] == 'SUCCESS' || $rba_risk_response['status'] == 'WAIT_FOR_INPUT'){ 
   						$mo2f_rba_status = array();
   						$mo2f_rba_status['status'] = $rba_risk_response['status'];
   						$mo2f_rba_status['sessionUuid'] = $sessionUuid;
   						$mo2f_rba_status['decision_flag'] = true;
   						return $mo2f_rba_status;
   					}else{
   						$mo2f_rba_status = array();
   						$mo2f_rba_status['status'] = $rba_risk_response['status'];
   						$mo2f_rba_status['sessionUuid'] = $sessionUuid;
   						$mo2f_rba_status['decision_flag'] = false;
   						return $mo2f_rba_status;
   					}
   				}else{
   					$mo2f_rba_status = array();
   					$mo2f_rba_status['status'] = 'JSON_EVALUATE_ERROR';
   					$mo2f_rba_status['sessionUuid'] = $sessionUuid;
   					$mo2f_rba_status['decision_flag'] = false;
   					return $mo2f_rba_status;
   				}
   			}else{
   				$mo2f_rba_status = array();
   				$mo2f_rba_status['status'] = 'ATTR_NOT_COLLECTED';
   				$mo2f_rba_status['sessionUuid'] = '';
   				$mo2f_rba_status['decision_flag'] = false;
   				return $mo2f_rba_status;
   			}
   		}else{
   			$mo2f_rba_status = array();
   			$mo2f_rba_status['status'] = 'JSON_ATTR_NOT_COLLECTED';
   			$mo2f_rba_status['sessionUuid'] = '';
   			$mo2f_rba_status['decision_flag'] = false;
   			return $mo2f_rba_status;
   		}
   	}else{
   		$mo2f_rba_status = array();
   		$mo2f_rba_status['status'] = 'RBA_NOT_ENABLED';
   		$mo2f_rba_status['sessionUuid'] = '';
   		$mo2f_rba_status['decision_flag'] = false;
   		return $mo2f_rba_status;
   	}
   }
   
   function mo2f_get_user_2ndfactor($current_user){
   	if(get_user_meta($current_user->ID,'mo_2factor_mobile_registration_status',true) == 'MO_2_FACTOR_SUCCESS'){
   		$mo2f_second_factor = 'MOBILE AUTHENTICATION';
   	}else{
   		$enduser = new Two_Factor_Setup();
   		$userinfo = json_decode($enduser->mo2f_get_userinfo(get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true)),true);
   		if(json_last_error() == JSON_ERROR_NONE){
   			if($userinfo['status'] == 'ERROR'){
   				$mo2f_second_factor = 'NONE';
   			}else if($userinfo['status'] == 'SUCCESS'){
   				$mo2f_second_factor = $userinfo['authType'];
   			}else if($userinfo['status'] == 'FAILED'){
   				$mo2f_second_factor = 'USER_NOT_FOUND';
   			}else{
   				$mo2f_second_factor = 'NONE';
   			}
   		}else{
   			$mo2f_second_factor = 'NONE';
   		}
   	}
   	return $mo2f_second_factor;
   }
   
   function mo2f_customize_logo(){
   	
   	if(get_option('mo2f_disable_poweredby') != 1 ){
   		
   		if(get_option('mo2f_enable_custom_poweredby')==1) { ?>
<div style="float:right;" ><img alt="logo" src="<?php echo plugins_url('/includes/images/custom.png',__FILE__); ?>" /></div>
<?php }else { ?>
<div style="float:right;" ><a target="_blank" href="http://miniorange.com/2-factor-authentication"><img alt="logo" src="<?php echo plugins_url('/includes/images/miniOrange2.png',__FILE__); ?>" /></a></div>
<?php } 
   }
   
   }	
   
   function mo2f_get_forgotphone_form($login_status, $login_message, $redirect_to){
   ?>
<html>
   <head>
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <?php
         echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>';
         echo '<script src="' . plugins_url('includes/js/bootstrap.min.js', __FILE__) . '" ></script>';
         echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/bootstrap.min.css?version=4.5.5', __FILE__) . '" />';
         echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/front_end_login.css?version=4.5.5', __FILE__) . '" />';
         echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/style_settings.css?version=4.5.5', __FILE__) . '" />';
         echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/hide-login.css?version=4.5.5', __FILE__) . '" />';
         ?>
   </head>
   <body>
      <div class="mo2f_modal" tabindex="-1" role="dialog" id="myModal5">
         <div class="mo2f-modal-backdrop"></div>
         <div class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md">
            <div class="login mo_customer_validation-modal-content">
               <div class="mo2f_modal-header">
                  <h4 class="mo2f_modal-title"><button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close" title="Back to login" onclick="mologinback();"><span aria-hidden="true">&times;</span></button>
                     How would you like to authenticate yourself?
                  </h4>
               </div>
               <div class="mo2f_modal-body">
                  <?php if(get_option( 'mo2f_enable_forgotphone' )) {
                     if(isset($login_message) && !empty($login_message)){ ?>
                  <div  id="otpMessage">
                     <p class="mo2fa_display_message_frontend" ><?php echo $login_message; ?></p>
                  </div>
                  <?php } ?>
                  <p style="padding-left:10px;padding-right:10px;"><?php echo 'Please choose the options from below:'; ?></p>
                  <div style="padding-left:10px;padding-right:40px;">
                     <?php if(get_option( 'mo2f_enable_forgotphone_email' )) {?>
                     <input type="radio"  name="mo2f_selected_forgotphone_option"  value="OTP OVER EMAIL"  checked="ckecked" />Send a one time passcode to my registered email<br /><br />
                     <?php } 
                        if(get_option( 'mo2f_enable_forgotphone_kba' )) {
                        ?>
                     <input type="radio"  name="mo2f_selected_forgotphone_option"  value="KBA"  />Answer your Security Questions (KBA)
                     <?php } ?>
                     <br /><br />
                     <input type="button" name="miniorange_validtae_otp" value="Continue" class="miniorange_validate_otp" onclick="mo2fselectforgotphoneoption();" />
                  </div>
                  <?php mo2f_customize_logo(); 
                     }
                     ?>
               </div>
            </div>
         </div>
      </div>
      <form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo wp_login_url(); ?>" style="display:none;">
         <input type="hidden" name="miniorange_mobile_validation_failed_nonce" value="<?php echo wp_create_nonce('miniorange-2-factor-mobile-validation-failed-nonce'); ?>" />
      </form>
      <form name="f" id="mo2f_challenge_forgotphone_form" method="post"   style="display:none;">
         <input type="hidden" name="mo2f_selected_2factor_method" />
         <input type="hidden" name="miniorange_challenge_forgotphone_nonce" value="<?php echo wp_create_nonce('miniorange-2-factor-challenge-forgotphone-nonce'); ?>" />
         <input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>" />
      </form>
   </body>
   <script>
      function mologinback(){
      	jQuery('#mo2f_backto_mo_loginform').submit();
      }
      function mo2fselectforgotphoneoption(){
      	var option = jQuery('input[name=mo2f_selected_forgotphone_option]:checked').val();
      	document.getElementById("mo2f_challenge_forgotphone_form").elements[0].value = option;
      	jQuery('#mo2f_challenge_forgotphone_form').submit();
       }
   </script>
</html>
<?php }	
   function mo2f_getkba_form($login_status, $login_message, $redirect_to){
   ?>
<html>
   <head>
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <?php
         echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>';
         echo '<script src="' . plugins_url('includes/js/bootstrap.min.js', __FILE__) . '" ></script>';
         echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/bootstrap.min.css?version=4.5.5', __FILE__) . '" />';
         echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/front_end_login.css?version=4.5.5', __FILE__) . '" />';
         echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/style_settings.css?version=4.5.5', __FILE__) . '" />';
         echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/hide-login.css?version=4.5.5', __FILE__) . '" />';
         ?>
   </head>
   <body>
      <div class="mo2f_modal" tabindex="-1" role="dialog" id="myModal5">
         <div class="mo2f-modal-backdrop"></div>
         <div class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md">
            <div class="login mo_customer_validation-modal-content">
               <div class="mo2f_modal-header">
                  <h4 class="mo2f_modal-title"><button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close" title="Back to login" onclick="mologinback();"><span aria-hidden="true">&times;</span></button>
                     Validate Security Questions
                  </h4>
               </div>
               <div class="mo2f_modal-body">
                  <div id="kbaSection" style="padding-left:10px;padding-right:10px;">
                     <div id="otpMessage">
                        <p style="font-size:13px;"><?php echo (isset($login_message) && !empty($login_message)) ? $login_message : 'Please answer the following questions:'; ?></p>
                     </div>
                     <form name="f" id="mo2f_submitkba_loginform" method="post"  >
                        <div id="mo2f_kba_content">
                           <p style="font-size:15px;">
                              <?php if(isset($_SESSION['mo_2_factor_kba_questions'])){
                                 echo $_SESSION['mo_2_factor_kba_questions'][0];
                                 ?><br />
                              <input class="mo2f-textbox" type="text" name="mo2f_answer_1" id="mo2f_answer_1" required="true" autofocus="true" pattern="(?=\S)[A-Za-z0-9_@.$#&amp;+-\s]{1,100}" title="Only alphanumeric letters with special characters(_@.$#&amp;+-) are allowed." autocomplete="off" ><br />
                              <?php
                                 echo $_SESSION['mo_2_factor_kba_questions'][1];
                                 ?><br />
                              <input class="mo2f-textbox" type="text" name="mo2f_answer_2" id="mo2f_answer_2" required="true" pattern="(?=\S)[A-Za-z0-9_@.$#&amp;+-\s]{1,100}" title="Only alphanumeric letters with special characters(_@.$#&amp;+-) are allowed." autocomplete="off">
                              <?php 
                                 }
                                 ?>
                           </p>
                        </div>
                        <?php if(get_option('mo2f_login_policy')){
                           if(get_option('mo2f_deviceid_enabled')){
                           ?>
                        <span style="float:left; font-size:15px;padding-right:10px;"><input style="vertical-align:text-top;" type="checkbox" name="mo2f_trust_device" id="mo2f_trust_device" />Remember this device.</span><br /><br />
                        <?php 
                           }
                           }		
                           ?>
                        <input type="submit" name="miniorange_kba_validate" id="miniorange_kba_validate" class="miniorange_kba_validate"  style="float:left;" value="Validate" />
                        <input type="hidden" name="miniorange_kba_nonce" value="<?php echo wp_create_nonce('miniorange-2-factor-kba-nonce'); ?>" />
                        <input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>" />
                     </form>
					 <br>
                  </div>
                  <?php mo2f_customize_logo() ?>
               </div>
            </div>
         </div>
      </div>
      <form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo wp_login_url(); ?>" style="display:none;">
         <input type="hidden" name="miniorange_mobile_validation_failed_nonce" value="<?php echo wp_create_nonce('miniorange-2-factor-mobile-validation-failed-nonce'); ?>" />
      </form>
   </body>
   <script>
      function mologinback(){
      	jQuery('#mo2f_backto_mo_loginform').submit();
      }
      
      
   </script>
   <?php
      }
      
      function mo2f_getpush_oobemail_response($id, $login_status, $login_message, $redirect_to){
      ?>
   <html>
      <head>
         <meta http-equiv="X-UA-Compatible" content="IE=edge">
         <meta name="viewport" content="width=device-width, initial-scale=1">
         <?php
            echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>';
            echo '<script src="' . plugins_url('includes/js/bootstrap.min.js', __FILE__) . '" ></script>';
            echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/bootstrap.min.css?version=4.5.5', __FILE__) . '" />';
            echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/front_end_login.css?version=4.5.5', __FILE__) . '" />';
            echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/style_settings.css?version=4.5.5', __FILE__) . '" />';
            echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/hide-login.css?version=4.5.5', __FILE__) . '" />';
            ?>
      </head>
      <body>
         <div class="mo2f_modal" tabindex="-1" role="dialog" id="myModal5">
            <div class="mo2f-modal-backdrop"></div>
            <div class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md">
               <div class="login mo_customer_validation-modal-content">
                  <div class="mo2f_modal-header">
                     <h4 class="mo2f_modal-title"><button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close" title="Back to login" onclick="mologinback();"><span aria-hidden="true">&times;</span></button>
                        Accept Your Transaction
                     </h4>
                  </div>
                  <div class="mo2f_modal-body">
                     <?php if(isset($login_message) && !empty($login_message)){ ?>
                     <div  id="otpMessage">
                        <p class="mo2fa_display_message_frontend" ><?php echo $login_message; ?></p>
                     </div>
                     <?php } ?>
                     <div id="pushSection">
                        
                        <div>
                           <center>
                              <p style="font-size:16px; font-weight:bold; color:#34495E; ">Waiting for your approval...</p>
                           </center>
                        </div>
                        <div id="showPushImage">
                           <center> 
                              <img src="<?php echo plugins_url( 'includes/images/ajax-loader-login.gif' , __FILE__ );?>" />
                           </center>
                        </div>
						
						<center>
                           <a href="#showPushHelp" id="pushHelpLink">
                              <p style="font-size:14px; font-weight:bold; color:#2980B9; ">See how it works ?</p>
                           </a>
                        </center>
						
                        <span style="padding-right:2%;">
                           <?php if(isset($login_status) && $login_status == 'MO_2_FACTOR_CHALLENGE_PUSH_NOTIFICATIONS'){ ?>
                           <center>
                              <?php if(get_option('mo2f_enable_forgotphone')){ ?>
                              <input type="button" name="miniorange_login_forgotphone" onclick="mologinforgotphone();" id="miniorange_login_forgotphone" class="miniorange_login_forgotphone" value="Forgot Phone?" />
                              <?php } ?>
							  &emsp;&emsp;
                              <input type="button" name="miniorange_login_offline" onclick="mologinoffline();" id="miniorange_login_offline" class="miniorange_login_offline" value="Phone is Offline?" />
                           </center>
                           <?php }else if(isset($login_status) && $login_status == 'MO_2_FACTOR_CHALLENGE_OOB_EMAIL' && get_option('mo2f_enable_forgotphone') && get_user_meta($id,'mo2f_kba_registration_status',true)){ ?>
                           <center>
                              <a href="#mo2f_alternate_login_kba" >
                                 <p style="font-size:14px; font-weight:bold; color:#2980B9; ">Didn't receive mail?</p>
                              </a>
                           </center>
                           <?php }?>
                        </span>
                     </div>
                     <div id="showPushHelp" class="showPushHelp" hidden>
                        <center>
                           <div id="myCarousel" class="mo2f_carousel slide" data-ride="carousel"  data-interval="15000" >
                              <ol class="mo2f_carousel-indicators">
                                 <li data-target="#myCarousel" data-slide-to="0" class="active"></li>
                                 <li data-target="#myCarousel" data-slide-to="1"></li>
                                 <li data-target="#myCarousel" data-slide-to="2"></li>
                              </ol>
                              <div class="mo2f_carousel-inner" role="listbox">
                                 <?php  if($login_status == 'MO_2_FACTOR_CHALLENGE_OOB_EMAIL') { ?>
                                 <div class="item active">
									<p>A verification email has been sent to your registered email id.</p>
                                    <br>
									<img class="first-slide" src="https://auth.miniorange.com/moas/images/help/email-with-link-login-flow-1.png" alt="First slide" style="width:80%">
                                 </div>
                                 <div class="item">
                                    <p>Click on <b style="color:red">Accept Transaction</b> link to verify your email .</p>
                                    <br>
                                    <img class="first-slide" src="https://auth.miniorange.com/moas/images/help/email-with-link-login-flow-2.png" alt="First slide"style="width:100%">
                                 </div>
                                 <div class="item">
								    <p>You have been validated. You will be logged in to your website now.</p>
                                    <br>
									<img class="first-slide" src="https://auth.miniorange.com/moas/images/help/email-with-link-login-flow-3.png" alt="First slide" style="width:100%">
                                 </div>
                                 <?php } else {	?>
                                 <!-- Indicators -->
                                 <div class="item active">
                                    <p>You will receive a notification on your phone.</p>
                                    <br>
                                    <img class="first-slide" src="https://auth.miniorange.com/moas/images/help/push-login-flow.png" alt="First slide"style="width:80%">
                                 </div>
                                 <div class="item">
                                    <p>Click on <b style="color:red">Approve</b> button.</p>
                                    <br>
                                    <img class="first-slide" src="https://auth.miniorange.com/moas/images/help/push-login-flow-1.jpg" alt="First slide"style="width:100%">
                                 </div>
                                 <div class="item">
								    <p>You are successfully authenticated.</p>
                                    <br>
									<img class="first-slide" src="https://auth.miniorange.com/moas/images/help/mo2f_softtoken_5.jpg" alt="First slide"style="width:100%">
                                 </div>
                                 <?php } ?>
                              </div>
                           </div>
						   <a href="#showPushHelp" id="pushLink">
							   <p>Go Back.</p>
						   </a>
                        </center>
                     </div>
                     <?php mo2f_customize_logo() ?>
                  </div>
               </div>
            </div>
         </div>
         <form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo wp_login_url(); ?>" style="display:none;">
            <input type="hidden" name="miniorange_mobile_validation_failed_nonce" value="<?php echo wp_create_nonce('miniorange-2-factor-mobile-validation-failed-nonce'); ?>" />
         </form>
         <form name="f" id="mo2f_mobile_validation_form" method="post"   style="display:none;">
            <input type="hidden" name="miniorange_mobile_validation_nonce" value="<?php echo wp_create_nonce('miniorange-2-factor-mobile-validation-nonce'); ?>" />
            <input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>" />
         </form>
         <form name="f" id="mo2f_show_softtoken_loginform" method="post"   style="display:none;">
            <input type="hidden" name="miniorange_softtoken" value="<?php echo wp_create_nonce('miniorange-2-factor-softtoken'); ?>" />
            <input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>" />
         </form>
         <form name="f" id="mo2f_show_forgotphone_loginform" method="post"   style="display:none;">
            <input type="hidden" name="request_origin_method" value="<?php echo $login_status; ?>" />
            <input type="hidden" name="miniorange_forgotphone" value="<?php echo wp_create_nonce('miniorange-2-factor-forgotphone'); ?>" />
            <input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>" />
         </form>
         <form name="f" id="mo2f_alternate_login_kbaform" method="post"   style="display:none;">
            <input type="hidden" name="miniorange_alternate_login_kba_nonce" value="<?php echo wp_create_nonce('miniorange-2-factor-alternate-login-kba-nonce'); ?>" />
            <input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>" />
         </form>
      </body>
      <script>
         var timeout;
         pollPushValidation();
         function pollPushValidation()
         {	
         	var transId = "<?php echo $_SESSION[ 'mo2f-login-transactionId' ];  ?>";
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
         				jQuery('#mo2f_mobile_validation_form').submit();
         			} else if (status == 'ERROR' || status == 'FAILED' || status == 'DENIED') {
         				jQuery('#mo2f_backto_mo_loginform').submit();
         			} else {
         				timeout = setTimeout(pollPushValidation, 3000);
         			}
         		}
         	});
         }
         jQuery('#myCarousel').carousel('pause');
         jQuery('#pushHelpLink').click(function() {
         	jQuery('#showPushHelp').show();
         	jQuery('#pushSection').hide();
         	jQuery('#otpMessage').hide();
         	jQuery('#myCarousel').carousel(0); 
         });
         jQuery('#pushLink').click(function() {
         	jQuery('#showPushHelp').hide();
         	jQuery('#pushSection').show();
         	jQuery('#otpMessage').show();
         	jQuery('#myCarousel').carousel('pause');
         });
         function mologinback(){
         	jQuery('#mo2f_backto_mo_loginform').submit();
         }
         function mologinoffline(){
         	jQuery('#mo2f_show_softtoken_loginform').submit();
         }
         function mologinforgotphone(){
         	jQuery('#mo2f_show_forgotphone_loginform').submit();
         }
         jQuery('a[href="#mo2f_alternate_login_kba"]').click(function() {
         	jQuery('#mo2f_alternate_login_kbaform').submit();
         });
         
      </script>
   </html>
   <?php 
      }
      	
      	function mo2f_getqrcode($login_status, $login_message, $redirect_to){
      ?>
   <html>
      <head>
         <meta http-equiv="X-UA-Compatible" content="IE=edge">
         <meta name="viewport" content="width=device-width, initial-scale=1">
         <?php
            echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>';
            echo '<script src="' . plugins_url('includes/js/bootstrap.min.js', __FILE__) . '" ></script>';
            echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/bootstrap.min.css?version=4.5.5', __FILE__) . '" />';
            echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/front_end_login.css?version=4.5.5', __FILE__) . '" />';
            echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/style_settings.css?version=4.5.5', __FILE__) . '" />';
            echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/hide-login.css?version=4.5.5', __FILE__) . '" />';
            ?>
      </head>
      <body>
         <div class="mo2f_modal" tabindex="-1" role="dialog" id="myModal5">
            <div class="mo2f-modal-backdrop"></div>
            <div class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md">
               <div class="login mo_customer_validation-modal-content">
                  <div class="mo2f_modal-header">
                     <h4 class="mo2f_modal-title"><button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close" title="Back to login" onclick="mologinback();"><span aria-hidden="true">&times;</span></button>
                        Scan QR Code
                     </h4>
                  </div>
                  <div class="mo2f_modal-body center">
                     <?php if(isset($login_message) && !empty($login_message)){ ?>
                     <div id="otpMessage">
                        <p class="mo2fa_display_message_frontend" style="text-align: left !important;"  ><?php echo $login_message; ?></p>
                     </div>
                     <br />
                     <?php } ?>
                     <div id="scanQRSection">
                        <center>
                           <a href="#showQRHelp" id="helpLink">
                              <p style="font-size:16px; font-weight:bold; color:#2980B9; ">See how it works ?</p>
                           </a>
                        </center>
                        <div style="margin-bottom:10%;">
                           <center>
                              <p style="font-size:16px; font-weight:bold; font-color:#2980B9">Identify yourself by scanning the QR code with miniOrange Authenticator app.</p>
                           </center>
                        </div>
                        <div id="showQrCode" style="margin-bottom:10%;">
                           <center><?php echo '<img src="data:image/jpg;base64,' . $_SESSION[ 'mo2f-login-qrCode' ] . '" />'; ?></center>
                        </div>
                        <span style="padding-right:2%;">
                           <center>
                              <?php if(get_option('mo2f_enable_forgotphone')){ ?>
                              <input type="button" name="miniorange_login_forgotphone" onclick="mologinforgotphone();" id="miniorange_login_forgotphone" class="miniorange_login_forgotphone" style="margin-right:5%;" value="Forgot Phone?" />
                              <?php } ?>
							  &emsp;&emsp;
                              <input type="button" name="miniorange_login_offline" onclick="mologinoffline();" id="miniorange_login_offline" class="miniorange_login_offline" value="Phone is Offline?" />
                           </center>
                        </span>
                     </div>
                     <div id="showQRHelp" class="showQRHelp" hidden>
                        <center>
                           <div id="myCarousel" class="mo2f_carousel slide" data-ride="carousel"  data-interval="15000" >
                              <!-- Indicators -->
                              <ol class="mo2f_carousel-indicators">
                                 <li data-target="#myCarousel" data-slide-to="0" class="active"></li>
                                 <li data-target="#myCarousel" data-slide-to="1"></li>
                                 <li data-target="#myCarousel" data-slide-to="2"></li>
                              </ol>
                              <div class="mo2f_carousel-inner" role="listbox">
                                 <div class="item active">
                                    <img class="first-slide" src="https://auth.miniorange.com/moas/images/help/qr-how-to-setup-1.png" alt="First slide"style="width:80%; height:100%;">
                                 </div>
                                 <div class="item">
                                    <p>Open miniOrange <b style="color:red">Authenticator</b> app and click on SCAN QR CODE.</p>
                                    <br>
                                    <img class="first-slide" src="https://auth.miniorange.com/moas/images/help/mo2f_softtoken_5.jpg" alt="First slide"style="width:100%">
                                 </div>
                                 <div class="item">
								    <p>Scan the QR code from the app.</p>
                                    <br>
                                    <img class="first-slide" src="https://auth.miniorange.com/moas/images/help/qr-help-3.jpg" alt="First slide"
									style="width:100%">
                                 </div>
                                 
                              </div>
                           </div>
						   <a href="#showQRHelp" id="qrLink">
                              <p style="font-size:14px; font-weight:bold; color:#2980B9; ">Back to Scan QR Code.</p>
                           </a>
                        </center>
                     </div>
                     <?php mo2f_customize_logo() ?>
                  </div>
               </div>
            </div>
         </div>
         <form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo wp_login_url(); ?>" style="display:none;">
            <input type="hidden" name="miniorange_mobile_validation_failed_nonce" value="<?php echo wp_create_nonce('miniorange-2-factor-mobile-validation-failed-nonce'); ?>" />
         </form>
         <form name="f" id="mo2f_mobile_validation_form" method="post"   style="display:none;">
            <input type="hidden" name="miniorange_mobile_validation_nonce" value="<?php echo wp_create_nonce('miniorange-2-factor-mobile-validation-nonce'); ?>" />
            <input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>" />
         </form>
         <form name="f" id="mo2f_show_softtoken_loginform" method="post"   style="display:none;">
            <input type="hidden" name="miniorange_softtoken" value="<?php echo wp_create_nonce('miniorange-2-factor-softtoken'); ?>" />
            <input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>" />
         </form>
         <form name="f" id="mo2f_show_forgotphone_loginform" method="post"   style="display:none;">
            <input type="hidden" name="request_origin_method" value="<?php echo $login_status; ?>" />
            <input type="hidden" name="miniorange_forgotphone" value="<?php echo wp_create_nonce('miniorange-2-factor-forgotphone'); ?>" />
            <input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>" />
         </form>
      </body>
      <script>
         var timeout;
         pollMobileValidation();
         function pollMobileValidation()
         {
         	var transId = "<?php echo $_SESSION[ 'mo2f-login-transactionId' ];  ?>";
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
         				var content = "<div id='success'><center><img src='" + "<?php echo plugins_url( 'includes/images/right.png' , __FILE__ );?>" + "' /></center></div>";
         				jQuery("#showQrCode").empty();
         				jQuery("#showQrCode").append(content);
         				setTimeout(function(){jQuery("#mo2f_mobile_validation_form").submit();}, 100);
         			} else if (status == 'ERROR' || status == 'FAILED') {
         				var content = "<div id='error'><center><img src='" + "<?php echo plugins_url( 'includes/images/wrong.png' , __FILE__ );?>" + "' /></center></div>";
         				jQuery("#showQrCode").empty();
         				jQuery("#showQrCode").append(content);
         				setTimeout(function(){jQuery('#mo2f_backto_mo_loginform').submit();}, 1000);
         			} else {
         				timeout = setTimeout(pollMobileValidation, 3000);
         			}
         		}
         	});
         }
         jQuery('#myCarousel').carousel('pause');
         jQuery('#helpLink').click(function() {
         	jQuery('#showQRHelp').show();
         	jQuery('#scanQRSection').hide();
         	
         	jQuery('#myCarousel').carousel(0); 
         });
         jQuery('#qrLink').click(function() {
         	jQuery('#showQRHelp').hide();
         	jQuery('#scanQRSection').show();
         	jQuery('#myCarousel').carousel('pause');
         });
         function mologinback(){
         	jQuery('#mo2f_backto_mo_loginform').submit();
          }
          function mologinoffline(){
         	jQuery('#mo2f_show_softtoken_loginform').submit();
          }
          function mologinforgotphone(){
         	jQuery('#mo2f_show_forgotphone_loginform').submit();
          }
      </script>
   </html>
   <?php 
      }
      	
      	function mo2f_getotp_form($login_status, $login_message, $redirect_to){
      	?>
   <html>
      <head>
         <meta http-equiv="X-UA-Compatible" content="IE=edge">
         <meta name="viewport" content="width=device-width, initial-scale=1">
         <?php
            echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>';
            echo '<script src="' . plugins_url('includes/js/bootstrap.min.js', __FILE__) . '" ></script>';
            echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/bootstrap.min.css?version=4.5.5', __FILE__) . '" />';
            echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/front_end_login.css?version=4.5.5', __FILE__) . '" />';
            echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/style_settings.css?version=4.5.5', __FILE__) . '" />';
            echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/hide-login.css?version=4.5.5', __FILE__) . '" />';
            ?>
      </head>
      <body>
         <div class="mo2f_modal" tabindex="-1" role="dialog" id="myModal5">
            <div class="mo2f-modal-backdrop"></div>
            <div class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md">
               <div class="login mo_customer_validation-modal-content">
                  <div class="mo2f_modal-header">
                     <h4 class="mo2f_modal-title"><button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close" title="Back to login" onclick="mologinback();"><span aria-hidden="true">&times;</span></button>
                        Validate OTP
                     </h4>
                  </div>
                  <div class="mo2f_modal-body center">
                     <?php if(isset($login_message) && !empty($login_message)){ ?>
                     <div  id="otpMessage">
                        <p class="mo2fa_display_message_frontend" style="text-align: left !important;"  ><?php echo $login_message; ?></p>
                     </div>
                     <?php } ?>
                     <br />
                     <div id="showOTP">
                        <div class="mo2f-login-container">
                           <form name="f" id="mo2f_submitotp_loginform" method="post"  > 
							  <center> 
								 <input type="text" name="mo2fa_softtoken" style="height:28px !important;" placeholder="Enter the code" id="mo2fa_softtoken" required="true" class="mo_otp_token" autofocus="true" pattern="[0-9]{4,8}" title="Only digits within range 4-8 are allowed."/>
						   	  </center>
                              <br />
                              <input type="submit" name="miniorange_otp_token_submit" id="miniorange_otp_token_submit" class="miniorange_otp_token_submit"  value="Validate" />
                              <input type="hidden" name="request_origin_method" value="<?php echo $login_status; ?>" />
                              <input type="hidden" name="miniorange_soft_token_nonce" value="<?php echo wp_create_nonce('miniorange-2-factor-soft-token-nonce'); ?>" />
                              <input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>" />
                           </form><br/>
                           <?php if(get_option('mo2f_enable_forgotphone') && isset($login_status ) && $login_status != 'MO_2_FACTOR_CHALLENGE_OTP_OVER_EMAIL'){ ?>
                           <a name="miniorange_login_forgotphone"  onclick="mologinforgotphone();" id="miniorange_login_forgotphone" class="mo2f-link"   >Forgot Phone ?</a>
                           <?php } ?>
						   &emsp;&emsp;
						   
						   <?php if($login_status != 'MO_2_FACTOR_CHALLENGE_GOOGLE_AUTHENTICATION'){  ?>
                               <a href="#showOTPHelp" id="otpHelpLink" class="mo2f-link">See how it works ?</a>
                           <?php } ?>
						   <br><br>
                        </div>
                     </div>
                     <div id="showOTPHelp" class="showOTPHelp" hidden>
                        <center>
                        <div id="myCarousel" class="mo2f_carousel slide" data-ride="carousel"  data-interval="15000" >
                           <!-- Indicators -->
                           <?php if($login_status == 'MO_2_FACTOR_CHALLENGE_SOFT_TOKEN'){ ?>
                           <ol class="mo2f_carousel-indicators">
                              <li data-target="#myCarousel" data-slide-to="0" class="active"></li>
                              <li data-target="#myCarousel" data-slide-to="1"></li>
                              <li data-target="#myCarousel" data-slide-to="2"></li>
                              <li data-target="#myCarousel" data-slide-to="3"></li>
                           </ol>
                           <div class="mo2f_carousel-inner" role="listbox">
                              <div class="item active">
                                 <p>Open miniOrange <b style="color:red">Authenticator</b> app and click on Sync time from the top left menu option.</p>
                                 <br>
                                 <img class="first-slide" src="https://auth.miniorange.com/moas/images/help/qr-help-2.jpg" alt="First slide" style="width:100%">
                              </div>
                              <div class="item">
                                 <p>Click on <b style="color:red">Sync Time now</b> to sync your time with miniOrange Servers. This is a one time sync to avoid otp validation failure.</p>
                                 <br>
                                 <img class="first-slide" src="https://auth.miniorange.com/moas/images/help/token-help-3.jpg" alt="First slide"
								 style="width:100%">
                              </div>
                              <div class="item">
                                 <p>Go to Home</p>
                                 <br>
                                 <img class="first-slide" src="https://auth.miniorange.com/moas/images/help/token-help-2.jpg" alt="First slide"
								 style="width:100%">
                              </div>
                              <div class="item">
                                 <p>Enter the one time passcode shown in miniOrange <b style="color:red">Authenticator</b> app here.</p>
                                 <br>
                                 <img class="first-slide" src="https://auth.miniorange.com/moas/images/help/soft-token-test-5.png" alt="First slide"style="width:100%">
                              </div>
                           </div>
                           <?php } else  if($login_status == 'MO_2_FACTOR_CHALLENGE_OTP_OVER_EMAIL') { ?>
                           <ol class="mo2f_carousel-indicators">
                              <li data-target="#myCarousel" data-slide-to="0" class="active"></li>
                              <li data-target="#myCarousel" data-slide-to="1"></li>
                              <li data-target="#myCarousel" data-slide-to="2"></li>
                           </ol>
                           <div class="mo2f_carousel-inner" role="listbox">
                              <div class="item active">
							     <p>An One Time Passcode has been sent to your registered email address.</p>
                                 <br>
                                 <img class="first-slide" src="https://auth.miniorange.com/moas/images/help/otp-help-1.png" alt="First slide"
								 style="width:100%">
                              </div>
                              <div class="item">
                                 <p>Check your email with which you registered and copy the one time passcode.</p>
                                 <br>
                                 <img class="first-slide" src="https://auth.miniorange.com/moas/images/help/otp-help-2.png" alt="First slide"
								 style="width:100%">
                              </div>
                              <div class="item">
							     <p>Enter the One Time Passcode to validate yourself.</p>
                                 <br>
                                 <img class="first-slide" src="https://auth.miniorange.com/moas/images/help/otp-help-3.png" alt="First slide"
								 style="width:100%">
                              </div>
                           </div>
                           <?php } else if($login_status == 'MO_2_FACTOR_CHALLENGE_OTP_OVER_SMS') { ?>
                           <ol class="mo2f_carousel-indicators">
                              <li data-target="#myCarousel" data-slide-to="0" class="active"></li>
                              <li data-target="#myCarousel" data-slide-to="1"></li>
                           </ol>
                           <div class="mo2f_carousel-inner" role="listbox">
                              <div class="item active">
								<p>An OTP has been sent to your registered mobile number.</p>
                                 <br>
                                 <img class="first-slide" src="https://auth.miniorange.com/moas/images/help/otp-over-sms-login-flow-1.png" alt="First slide"style="width:100%">
                              </div>
                              <div class="item">
							     <p>Enter the OTP received on your mobile phone to validate yourself.</p>
								 <br>
                                 <img class="first-slide" src="https://auth.miniorange.com/moas/images/help/otp-over-sms-login-flow-2.jpg" alt="First slide" style="width:100%">
                              </div>
                           </div>
                           <?php } ?>
                        </div>
						<br>
						<a href="#showOTP" id="otpLink" class="mo2f-link">Go Back.</a>
						<br>
                     </div>
					 </center>
                     <?php mo2f_customize_logo() ?>
                  </div>
               </div>
            </div>
         </div>
         <form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo wp_login_url(); ?>" style="display:none;">
            <input type="hidden" name="miniorange_mobile_validation_failed_nonce" value="<?php echo wp_create_nonce('miniorange-2-factor-mobile-validation-failed-nonce'); ?>" />
         </form>
         <?php if(get_option('mo2f_enable_forgotphone') && isset($login_status ) && $login_status != 'MO_2_FACTOR_CHALLENGE_OTP_OVER_EMAIL'){ ?>
         <form name="f" id="mo2f_show_forgotphone_loginform" method="post" action="" style="display:none;">
            <input type="hidden" name="request_origin_method" value="<?php echo $login_status; ?>" />
            <input type="hidden" name="miniorange_forgotphone" value="<?php echo wp_create_nonce('miniorange-2-factor-forgotphone'); ?>" />
            <input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>" />
         </form>
         <?php } ?>
      </body>
      <script>
         jQuery('#otpHelpLink').click(function() {
         	jQuery('#showOTPHelp').show();
         	jQuery('#showOTP').hide();
         	jQuery('#otpMessage').hide();
         });
         jQuery('#otpLink').click(function() {
         	jQuery('#showOTPHelp').hide();
         	jQuery('#showOTP').show();
         	jQuery('#otpMessage').show();
         });
         
         function mologinback(){
         	jQuery('#mo2f_backto_mo_loginform').submit();
          }
          function mologinforgotphone(){
         	jQuery('#mo2f_show_forgotphone_loginform').submit();
          }
      </script>
   </html>
   <?php
      }
      
      
      function mo2f_get_device_form($login_status, $login_message, $redirect_to){
      ?>
   <html>
      <head>
         <meta http-equiv="X-UA-Compatible" content="IE=edge">
         <meta name="viewport" content="width=device-width, initial-scale=1">
         <?php
            echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>';
            echo '<script src="' . plugins_url('includes/js/bootstrap.min.js', __FILE__) . '" ></script>';
            echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/bootstrap.min.css?version=4.5.5', __FILE__) . '" />';
            echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/front_end_login.css?version=4.5.5', __FILE__) . '" />';
            echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/style_settings.css?version=4.5.5', __FILE__) . '" />';
            echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('includes/css/hide-login.css?version=4.5.5', __FILE__) . '" />';
            ?>
      </head>
      <body>
         <div class="mo2f_modal" tabindex="-1" role="dialog" id="myModal5">
            <div class="mo2f-modal-backdrop"></div>
            <div class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md">
               <div class="login mo_customer_validation-modal-content">
                  <div class="mo2f_modal-header">
                     <h4 class="mo2f_modal-title"><button type="button" class="mo2f_close" data-dismiss="modal" aria-label="Close" title="Back to login" onclick="mologinback();"><span aria-hidden="true">&times;</span></button>
                        Remember Device
                     </h4>
                  </div>
                  <div class="mo2f_modal-body center">
                     <div id="mo2f_device_content">
                        <p style="font-size:16px; font-weight:bold; color:#2980B9; ">Do you want to remember this device?</p>
                        <input type="button" name="miniorange_trust_device_yes" onclick="mo_check_device_confirm();" id="miniorange_trust_device_yes" class="mo_green" style="margin-right:5%;" value="Yes" />
                        <input type="button" name="miniorange_trust_device_no" onclick="mo_check_device_cancel();" id="miniorange_trust_device_no" class="mo_red" value="No" />
                     </div>
                     <div id="showLoadingBar"  hidden>
                        <p style="font-size:16px; font-weight:bold; color:#2980B9; ">Please wait...We are taking you into your account.</p>
                        <img src="<?php echo plugins_url( 'includes/images/ajax-loader-login.gif' , __FILE__ );?>" />
                     </div>
                     <br /><br />
                     <span>
                     Click on <i><b>Yes</b></i> if this is your personal device.<br />
                     Click on <i><b>No</b></i> if this is a public device.
                     </span><br /><br />
                     <?php mo2f_customize_logo() ?>
                  </div>
               </div>
            </div>
         </div>
         <form name="f" id="mo2f_backto_mo_loginform" method="post" action="<?php echo wp_login_url(); ?>" style="display:none;">
            <input type="hidden" name="miniorange_mobile_validation_failed_nonce" value="<?php echo wp_create_nonce('miniorange-2-factor-mobile-validation-failed-nonce'); ?>" />
         </form>
         <form name="f" id="mo2f_trust_device_confirm_form" method="post" action="" style="display:none;">
            <input type="hidden" name="mo2f_trust_device_confirm_nonce" value="<?php echo wp_create_nonce('miniorange-2-factor-trust-device-confirm-nonce'); ?>" />
            <input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>" />
         </form>
         <form name="f" id="mo2f_trust_device_cancel_form" method="post" action="" style="display:none;">
            <input type="hidden" name="mo2f_trust_device_cancel_nonce" value="<?php echo wp_create_nonce('miniorange-2-factor-trust-device-cancel-nonce'); ?>" />
            <input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>" />
         </form>
         <script>
            function mologinback(){
            	jQuery('#mo2f_backto_mo_loginform').submit();
             }
            function mo_check_device_confirm(){
            	jQuery('#mo2f_device_content').hide();
            	jQuery('#showLoadingBar').show();
            	jQuery('#mo2f_trust_device_confirm_form').submit();
            }
            function mo_check_device_cancel(){
            	jQuery('#mo2f_device_content').hide();
            	jQuery('#showLoadingBar').show();
            	jQuery('#mo2f_trust_device_cancel_form').submit();
            }
         </script>
      </body>
   </html>
   <?php } 
      ?>