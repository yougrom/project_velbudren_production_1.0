<?php 
	function mo2f_show_help_and_troubleshooting($current_user) {
	?>
	<div class="mo2f_table_layout">
		<?php echo mo2f_check_if_registered_with_miniorange($current_user); ?>
		<br>
		<ul class="mo2f_faqs">
			
			<?php if(current_user_can( 'manage_options' )) { ?>
			<div class="mo_faq_blocks">
				<h3 style="text-align:center"><b>Lockout Issues</b><h3>
				<hr>
				<h2><a  data-toggle="collapse" href="#question1" aria-expanded="false" ><li>How do I gain access to my website if I get locked out?</li></a></h2>
				<div class="mo2f_collapse" id="question1">
					You can obtain access to your website by one of the below options:
					<ol>
						<br>
						<li>If you have an additional administrator account whose Two Factor is not enabled yet, you can login with it.</li>
						<li>If you had setup KBA questions earlier, you can use them as an alternate method to login to your website.</li>
						<li>Rename the plugin from FTP - this disables the 2FA plugin and you will be able to login with your Wordpress username and password.</li>
						<li>Go to WordPress Database. Select wp_options, search for mo2f_activate_plugin key and update its value to 0. Two Factor will get disabled.</li>
					</ol>
					<br>
				</div>
				
			</div>
			</br>
			
				
			<div class="mo_faq_blocks">
				<h3 style="text-align:center"><b>Registration Issues</b><h3>
					
				<hr>				
				<h3><a  data-toggle="collapse" href="#question2" aria-expanded="false" ><li>I want to change the email address to which the verification email is being sent / I want to change my email address registered with miniOrange.
				</li></a></h3>
				<div class="mo2f_collapse" id="question2">
					<ul>
						<li>To change the email address in either of the cases, You will have to sign up for a new account with miniOrange.</li>
					</ul>
					<br>
				</div>
					
				<hr>				
				<h3><a  data-toggle="collapse" href="#question3" aria-expanded="false" ><li>I did not receive OTP while trying to register with miniOrange. What should I do?
				</li></a></h3>
				<div class="mo2f_collapse" id="question3">
					<ul>
						<li>The OTP is sent to your email address with which you have registered with miniOrange. If you can't see the email from miniOrange in your mails, please make sure to check your <b>SPAM folder</b>.<br>
						If you don't see an email even in SPAM folder, please reach out to us.</li>
					</ul>
					<br>
				</div>
				<hr>
					
				<h3><a  data-toggle="collapse" href="#question4" aria-expanded="false" ><li>I forgot the password of my miniOrange account. How can I reset it?
				</li></a></h3>
				<div class="mo2f_collapse" id="question4">
					<ol>
						<li>Navigate to <b>Login with miniOrange</b> screen by clicking on <b>'Already registered with miniOrange?'</b>.</li>
						<li>Click on <b>'Click here if you forgot your password?'</b>.</li>
						<li>You will get a new password on your email address with which you have registered with miniOrange . Now you can login with the new password.</li>
					</ol>
					<br>
				</div>
			</div>
			<br>
				
			<div class="mo_faq_blocks">
				<h3 style="text-align:center"><b>Login Issues</b><h3>
				<hr>
				
				<h3><a  data-toggle="collapse" href="#question5" aria-expanded="false" ><li>My Users are not being prompted for 2-factor during login. Why?</li></a></h3>
				<div class="mo2f_collapse" id="question5">
				   <ul>
					   <li>The free plugin provides the 2-factor functionality for one user(Administrator) forever. To enable 2FA for more users, please upgrade to the Premium plan by clicking on 'Click here to Upgrade' from the Licensing Plans tab.</li>
					</ul>
				    <br>
				</div>
				<hr> 
				
				<h3><a  data-toggle="collapse" href="#question6" aria-expanded="false" ><li>I had setup QR Code Authentication/Push Notification as my 2-factor method. My phone has no internet connectivity, how can I login?</li></a></h3>
				<div class="mo2f_collapse" id="question6">
				   You can login using our alternate login method. Please follow below steps to login or <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_demo#myCarousel9">click here</a> to see how to setup 2-factor.<br>
				   <br>
				   <ol>
					   <li>Enter your username and click on login with 2nd factor.</li>
					   <li>Click on <b>Phone is Offline?</b> button below QR Code.</li>
					   <li>You will see a textbox to enter one time passcode.</li>
					   <li>Open miniOrange Authenticator app and Go to Soft Token Tab.</li>
					   <li>Enter the one time passcode shown in miniOrange Authenticator app in textbox.</li>
					   <li>Click on submit button to validate the otp.</li>
					   <li>Once you are authenticated, you will be logged in.</li>
					</ol>
				    <br>
				</div>
				<hr>
					
				<h3><a  data-toggle="collapse" href="#question7" aria-expanded="false" ><li>My phone has no internet connectivity and I am entering the one time passcode from miniOrange Authenticator App, it says Invalid OTP.</li></a></h3>
				<div class="mo2f_collapse" id="question7">
					<ul>
						<li>Click on the <b>Sync Time</b> option to the options on the left in miniOrange<b> Authenticator App</b> and press on <b>Sync Time now</b> to sync your time with miniOrange Servers.</li>
					</ul>
					<br>
		   		</div>
				<hr>
					
				<h3><a  data-toggle="collapse" href="#question8" aria-expanded="false" ><li>I want to hide default login form and just want to show login with phone?</li></a></h3>
				<div class="mo2f_collapse" id="question8">
					<ul>
						<li>You should go to <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_login">Login Settings Tab</a> and check <b>I want to hide default login form.</b> checkbox to hide the default login form. </li>
					</ul>
					<br>
				</div>
				<hr>
				
				<h3><a  data-toggle="collapse" href="#question9" aria-expanded="false" ><li>My phone is lost, stolen or discharged. How can I login?</li></a></h3>
				<div class="mo2f_collapse" id="question9">
					You can login using our alternate login method. Please follow below steps to login or <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_demo#myCarousel3">click here</a> to see how to setup 2-factor.
					<br><br>
					<ol>
						<li>Enter your username and click on login with your phone.</li>
						<li>Click on <b>Forgot Phone?</b> button.</li>
				   	    <li>You will see a textbox to enter one time passcode.</li>
				 	    <li>Check your registered email and copy the one time passcode in this textbox.</li>
						<li>Click on submit button to validate the otp.</li>
					    <li>Once you are authenticated, you will be logged in.</li>
			        </ol>
				    <br>
				</div>	
				<hr>
		
				<h3><a  data-toggle="collapse" href="#question10" aria-expanded="false" ><li>I want to go back to default login with password.</li></a></h3>
				<div class="mo2f_collapse" id="question10">
					<ul>
						<li>You can disable Two Factor from Login settings Tab by unchecking <b>Enable Two Factor Plugin</b> checkbox.</li>
					</ul>
					<br>
				</div>	
				<hr>
					
				<h3><a  data-toggle="collapse" href="#question11" aria-expanded="false" ><li>I have a custom / front-end login page on my site and I want the look and feel to remain the same when I add 2 factor ?</li></a></h3>
				<div class="mo2f_collapse" id="question11">
					<ul>
						<li>Our plugin works with most of the custom login pages. However, we do not claim that it will work with all the customized login pages.<br> In such cases, custom work is needed to integrate two factor with your customized login page. You can submit a query to us from Support section to the right for more details.</li>
					</ul>
					<br>
				</div>
			</div>
			<br>
				
			<div class="mo_faq_blocks">
				<h3 style="text-align:center"><b>Plugin Installation Errors</b><h3>
				<hr>
				<h3><a  data-toggle="collapse" href="#question12" aria-expanded="false" ><li>I am getting the fatal error of call to undefined function json_last_error(). What should I do?</li></a>
				</h3>
				<div class="mo2f_collapse" id="question12">
					<ul>
						<li>Please check your php version. The plugin is supported in php version 5.3.0 or above. You need to upgrade your php version to 5.3.0 or above to use the plugin.</li>
					</ul>
					<br>
				</div>
				<hr>
			
				<h3><a  data-toggle="collapse" href="#question13" aria-expanded="false" ><li>How to enable PHP cURL extension? (Pre-requisite)</li></a></h3>
					<div class="mo2f_collapse" id="question13">
						cURL is enabled by default but in case you have disabled it, follow the below steps to enable it.
						<ol>
							<br>
							<li>Open php.ini(it's usually in /etc/ or in php folder on the server).</li>
							<li>Search for extension=php_curl.dll. Uncomment it by removing the semi-colon( ; ) in front of it.</li>
							<li>Restart the Apache Server.</li>
						</ol>
						<br>
					</div>
					<hr>
					
					<h3><a  data-toggle="collapse" href="#question14" aria-expanded="false" ><li>I am getting error - curl_setopt(): CURLOPT_FOLLOWLOCATION cannot be activated when an open_basedir is set.
					</li></a></h3>
					<div class="mo2f_collapse" id="question14">
						<ul>
							<li>Just setsafe_mode = Off in your php.ini file (it's usually in /etc/ on the server). If that's already off, then look around for the open_basedir in the php.ini file, and change it to open_basedir = .</li>
						</ul>
						<br>
					</div>
				</div>
				</br>
				<div class="mo_faq_blocks">
					<h3 style="text-align:center"><b>Compatibility Issues with other plugins</b><h3>
					<hr>
					<h3><a  data-toggle="collapse" href="#question15" aria-expanded="false" ><li>I have installed plugins which limit the login attempts like Limit Login Attempt, Loginizer, Wordfence etc. Is there any incompatibility with these kind of plugins?</li></a></h3>
					<div class="mo2f_collapse" id="question15">
						<ul>
							<li>These plugins limit the number of login attempts and block the IP temporarily. So if you are using 2 factor along with these kind of plugins, it is highly recommended to increase the login attempts (minimum 5) so that you don't get locked out.</li>
						</ul>
						<br>
					</div>
					<hr>
					<h3><a  data-toggle="collapse" href="#question16" aria-expanded="false" ><li>I am using a Security Plugin in WordPress like Simple Security Firewall, All in One WP Security Plugin and I am not able to login with Two-Factor.</li></a></h3>
					<div class="mo2f_collapse" id="question16">
						<ul>
							<li>Our Two-Factor plugin is compatible with most of the security plugins, but if you are facing any issues, please reach out to us.</li>
						</ul>
						<br>
					</div>
					<hr>
					<h3><a  data-toggle="collapse" href="#question17" aria-expanded="false" ><li>I am using render blocking javascript and css Plugins like Async JS and CSS and I am not able to login with Two-Factor or the screen gets blank.</li></a></h3>
					<div class="mo2f_collapse" id="question17">
						<ul>
							<li>If you are using <b>Async JS and CSS Plugin</b>, please go to it's settings and add jQuery in the list of exceptions and save settings.</li>
					    </ul>
						<br>
					</div>
				</div>
				<br>
				<div class="mo_faq_blocks">
					<h3 style="text-align:center"><b>Others</b><h3>
					<hr>			
					<h3 style="color:#0073aa;">If your query is not listed above, or if it was not resolved with the solutions provided, please feel free to submit a query to us through the support section to the left. We will get back to you as soon as possible.</h3>
				</div>
		   	<?php }?>
		<br>
		</ul>			
	</div>
	<?php } ?>