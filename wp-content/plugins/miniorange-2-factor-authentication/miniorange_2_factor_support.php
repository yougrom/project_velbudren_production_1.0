<?php

function mo2f_support(){
global $wpdb;
global $current_user;
$current_user = wp_get_current_user();
?>
	<div class="mo2f_support_layout">
		<h3>Support</h3>
			<form name="f" method="post" action="">
				<div>Need any help? Just send us a query so we can help you. <br /><br /></div>
				<div>
					<table style="width:95%;">
						<tr><td>
							<input type="email" class="mo2f_table_textbox" id="query_email" name="query_email" value="<?php echo get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true) ? get_user_meta($current_user->ID,'mo_2factor_map_id_with_email',true) : $current_user->user_email; ?>" placeholder="Enter your email" required="true" />
							</td>
						</tr>
						<tr><td>
							<input type="text" class="mo2f_table_textbox" style="width:100% !important;" name="query_phone" id="query_phone" value="<?php echo get_user_meta($current_user->ID,'mo2f_user_phone',true); ?>" placeholder="Enter your phone"/>
							</td>

						</tr>
						<tr>
							<td>
								<textarea id="query" name="query" style="resize: vertical;border-radius:4px;width:100%;height:143px;" onkeyup="mo2f_valid(this)" onblur="mo2f_valid(this)" onkeypress="mo2f_valid(this)" placeholder="Write your query here"></textarea>
							</td>
						</tr>
					</table>
				</div>
				<input type="hidden" name="option" value="mo_2factor_send_query"/>
				<input type="submit" name="send_query" id="send_query" value="Submit Query" style="margin-bottom:3%;" class="button button-primary button-large" />
			</form>
			<br />			
	</div>
	<br>
	
	<script>
		jQuery("#query_phone").intlTelInput();
		function mo2f_valid(f) {
			!(/^[a-zA-Z?,.\(\)\/@ 0-9]*$/).test(f.value) ? f.value = f.value.replace(/[^a-zA-Z?,.\(\)\/@ 0-9]/, '') : null;
		}
	</script>
<?php
}
?>