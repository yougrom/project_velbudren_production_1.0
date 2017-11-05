<?php
	if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
		exit();
	}
 
	if ( !is_multisite() ) {
     //delete all your options
    //E.g: delete_option( {option name} );
	//delete all stored key-value pairs which are available to all users
		delete_option('mo2f_email');
		delete_option('mo2f_host_name');
		delete_option('mo2f_phone');
		delete_option('mo2f_customerKey');
		delete_option('mo2f_api_key');
		delete_option('mo2f_customer_token');
		delete_option('mo2f_message');
		delete_option('mo_2factor_admin_registration_status');
		delete_option('mo2f-login-message');
		delete_option('mo_2f_login_type_enabled');
		delete_option('mo2f_admin_disabled_status');
		delete_option('mo2f_disabled_status');
		delete_option('mo2f_miniorange_admin');
		delete_option('mo2f_enable_forgotphone');
		delete_option('mo2f_show_loginwith_phone');
		delete_option('mo2f_login_policy');
		delete_option( 'mo2f_msg_counter');
		delete_option( 'mo2f_activate_plugin');
		delete_option( 'mo2f_enable_2fa_for_woocommerce');
		delete_option( 'mo2f_auth_methods_for_users');
		delete_option( 'mo2f_deviceid_enabled' );
		delete_option( 'mo2f_app_secret' );
		delete_option( 'mo2f_inline_registration' );
		delete_option( 'mo2f_enable_custom');
		delete_option( 'mo2f_disable_poweredby');
		delete_option( 'mo2f_custom_plugin_name');
		delete_option( 'mo2f_enable_custom_poweredby' );
		delete_option( 'mo2f_enable_custom_icon' );
		delete_option('mo2f_enable_mobile_support'); 
		delete_option( 'mo2f_new_customer' );
		delete_option('mo2f_show_sms_transaction_message');
		/* App Specific Password
		delete_option('mo2f_app_password');
		*/
		
		//delete all stored key-value pairs for the roles
		global $wp_roles;
		if (!isset($wp_roles))
			$wp_roles = new WP_Roles();
		foreach($wp_roles->role_names as $id => $name) {	
			delete_option('mo2fa_'.$id);	
			delete_option('mo2fa_'.$id.'_login_url');
		}
	} 
	else {
		global $wpdb;
		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
		$original_blog_id = get_current_blog_id();

		foreach ( $blog_ids as $blog_id ){
			switch_to_blog( $blog_id );
			//delete all your options
			//E.g: delete_option( {option name} );  
			delete_option('mo2f_email');
			delete_option('mo2f_host_name');
			delete_option('mo2f_phone');
			delete_option('mo2f_customerKey');
			delete_option('mo2f_api_key');
			delete_option('mo2f_customer_token');
			delete_option('mo2f_message');
			delete_option('mo_2factor_admin_registration_status');
			delete_option('mo2f-login-message');
			delete_option('mo_2f_login_type_enabled');
			delete_option('mo2f_admin_disabled_status');
			delete_option('mo2f_disabled_status');
			delete_option('mo2f_miniorange_admin');
			delete_option('mo2f_enable_forgotphone');
			delete_option('mo2f_show_loginwith_phone');
			delete_option('mo2f_login_policy');
			delete_option( 'mo2f_msg_counter');
			delete_option( 'mo2f_activate_plugin');
			delete_option( 'mo2f_enable_2fa_for_woocommerce');
			delete_option( 'mo2f_auth_methods_for_users');
			delete_option( 'mo2f_deviceid_enabled' );
			delete_option( 'mo2f_app_secret' );
			delete_option( 'mo2f_inline_registration' );
			delete_option( 'mo2f_enable_custom');
			delete_option( 'mo2f_disable_poweredby');
			delete_option( 'mo2f_custom_plugin_name');
			delete_option( 'mo2f_enable_custom_poweredby' );
			delete_option( 'mo2f_enable_custom_icon' );
			delete_option('mo2f_enable_mobile_support'); 
			delete_option( 'mo2f_new_customer' );
			delete_option( 'mo2f_number_of_transactions' );
			delete_option( 'mo2f_set_transactions' );	
			delete_option('mo2f_show_sms_transaction_message');
			/* App Specific Password
			delete_option('mo2f_app_password');			
			*/
			
			//delete all stored key-value pairs for the roles
			global $wp_roles;
			if (!isset($wp_roles))
				$wp_roles = new WP_Roles();
			foreach($wp_roles->role_names as $id => $name) {	
				delete_option('mo2fa_'.$id);
				delete_option('mo2fa_'.$id.'_login_url');
			}
		
		}
		switch_to_blog( $original_blog_id );
	}
	
		
		//delete user specific key-value pair
		$users = get_users( array() );
		foreach ( $users as $user ) {
			delete_user_meta($user->ID,'mo_2factor_user_registration_status');
			delete_user_meta($user->ID,'mo_2factor_mobile_registration_status');
			delete_user_meta($user->ID,'mo_2factor_user_registration_with_miniorange');
			delete_user_meta($user->ID,'mo_2factor_map_id_with_email');
			delete_user_meta($user->ID,'mo2f_user_phone');
			delete_user_meta($user->ID,'mo2f_mobile_registration_status');
			delete_user_meta($user->ID,'mo2f_otp_registration_status');
			delete_user_meta($user->ID,'mo2f_configure_test_option');
			delete_user_meta($user->ID,'mo2f_selected_2factor_method');
			delete_user_meta($user->ID,'mo2f_google_authentication_status');
			delete_user_meta($user->ID,'mo2f_kba_registration_status');
			delete_user_meta($user->ID,'mo2f_email_verification_status');
			delete_user_meta($user->ID,'mo2f_authy_authentication_status');
			/* App Specific Password
			delete_user_meta($user->ID,'mo2f_app_password');
			*/
		}
		
		//delete previous version key-value pairs
		delete_option('mo_2factor_admin_mobile_registration_status');
		delete_option('mo_2factor_registration_status');
		delete_option('mo_2factor_temp_status');
		delete_option('mo2f_login_username');
		delete_option('mo2f-login-qrCode');
		delete_option('mo2f-login-transactionId');
		delete_option('mo_2factor_login_status');
		delete_option('mo2f_mowplink');
?>