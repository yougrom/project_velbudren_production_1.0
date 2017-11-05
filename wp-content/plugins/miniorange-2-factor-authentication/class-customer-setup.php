<?php
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
class Customer_Setup{
	
	public $email;
	public $phone;
	public $customerKey;
	public $transactionId;
	
	function check_customer() {
		if(!MO2f_Utility::is_curl_installed()) {
			$message = 'Please enable curl extension. <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_help">Click here</a> for the steps to enable curl or check Help & Troubleshooting.';
			return json_encode(array("status"=>'ERROR',"message"=>$message));
		}
		
		$url 	= get_option('mo2f_host_name') . "/moas/rest/customer/check-if-exists";
		$ch 	= curl_init( $url );
		$email 	= get_option("mo2f_email");

		$fields = array(
			'email' 	=> $email,
		);
		$field_string = json_encode( $fields );

		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );
		curl_setopt( $ch, CURLOPT_ENCODING, "" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false);

		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json', 'charset: UTF - 8', 'Authorization: Basic' ) );
		curl_setopt( $ch, CURLOPT_POST, true);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $field_string);
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt( $ch, CURLOPT_TIMEOUT, 20);
		$content = curl_exec( $ch );
		if( curl_errno( $ch ) ){
			return null;
		}
		curl_close( $ch );

		return $content;
	}

	
	function create_customer(){
		if(!MO2f_Utility::is_curl_installed()) {
			$message = 'Please enable curl extension. <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_help">Click here</a> for the steps to enable curl or check Help & Troubleshooting.';
			return json_encode(array("status"=>'ERROR',"message"=>$message));
		}
		
		$url = get_option('mo2f_host_name') . '/moas/rest/customer/add';
		$ch = curl_init($url);
		global $current_user;
		$current_user = wp_get_current_user();
		$this->email = get_option('mo2f_email');
		$this->phone = get_user_meta($current_user->ID,'mo2f_user_phone',true);
		$password = get_option('mo2f_password');
		$company = get_option('mo2f_admin_company') != '' ? get_option('mo2f_admin_company') : $_SERVER['SERVER_NAME'];
		$firstName = get_option('mo2f_admin_first_name');
		$lastName = get_option('mo2_admin_last_name');
		
		$fields = array(
			'companyName' => $company,
			'areaOfInterest' => 'WordPress 2 Factor Authentication Plugin',
			'productInterest' => 'API_2FA',
			'firstname' => $firstName,
			'lastname' => $lastName,
			'email' => $this->email,
			'phone' => $this->phone,
			'password' => $password
		);
		$field_string = json_encode($fields);
		
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );
		curl_setopt( $ch, CURLOPT_ENCODING, "" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false);

		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
		
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'charset: UTF - 8',
			'Authorization: Basic'
			));
		curl_setopt( $ch, CURLOPT_POST, true);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $field_string);
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt( $ch, CURLOPT_TIMEOUT, 20);
		$content = curl_exec($ch);
		
		if(curl_errno($ch)){
			return null;
		}
		

		curl_close($ch);
		return $content;
	}
	
	function get_customer_key() {
		if(!MO2f_Utility::is_curl_installed()) {
			$message = 'Please enable curl extension. <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_help">Click here</a> for the steps to enable curl or check Help & Troubleshooting.';
			return json_encode(array("status"=>'ERROR',"message"=>$message));
		}
		
		$url = get_option('mo2f_host_name') . "/moas/rest/customer/key";
		$ch = curl_init($url);
		$email = get_option("mo2f_email");
		$password = get_option("mo2f_password");
		
		$fields = array(
			'email' => $email,
			'password' => $password
		);
		$field_string = json_encode($fields);
		
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );
		curl_setopt( $ch, CURLOPT_ENCODING, "" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				

		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
		
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'charset: UTF - 8',
			'Authorization: Basic'
			));
		curl_setopt( $ch, CURLOPT_POST, true);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $field_string);
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt( $ch, CURLOPT_TIMEOUT, 20);
		$content = curl_exec($ch);
		if(curl_errno($ch)){
			return null;
		}
		curl_close($ch);

		return $content;
	}
	
	function send_otp_token($uKey,$authType,$cKey,$apiKey){
		if(!MO2f_Utility::is_curl_installed()) {
			$message = 'Please enable curl extension. <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_help">Click here</a> for the steps to enable curl or check Help & Troubleshooting.';
			return json_encode(array("status"=>'ERROR',"message"=>$message));
		}
						
		$url = get_option('mo2f_host_name') . '/moas/api/auth/challenge';
		$ch = curl_init($url);
		
		/* The customer Key provided to you */
		$customerKey = $cKey;
	
		/* The customer API Key provided to you */
		$apiKey = $apiKey;
	
		/* Current time in milliseconds since midnight, January 1, 1970 UTC. */
		$currentTimeInMillis = round(microtime(true) * 1000);
	
		/* Creating the Hash using SHA-512 algorithm */
		$stringToHash = $customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
		$hashValue = hash("sha512", $stringToHash);
	
		$customerKeyHeader = "Customer-Key: " . $customerKey;
		$timestampHeader = "Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
		$authorizationHeader = "Authorization: " . $hashValue;
	
		
		$fields = '';
		if( $authType == 'EMAIL' ) {
			$fields = array(
				'customerKey' => $customerKey,
				'email' => $uKey,
				'authType' => $authType,
				'transactionName' => 'WordPress 2 Factor Authentication Plugin'
			);
		}else if($authType == 'OTP_OVER_SMS' || $authType == 'PHONE_VERIFICATION'){
			if($authType == 'OTP_OVER_SMS'){
				$authType ="SMS";
			}else if($authType == 'PHONE_VERIFICATION'){
				$authType ="PHONE VERIFICATION";
			}
			
			$fields = array(
				'customerKey' => $customerKey,
				'phone' => $uKey,
				'authType' => $authType
			);
		}else{			
			$fields = array(
				'customerKey' => $customerKey,
				'username' => $uKey,
				'authType' => $authType,
				'transactionName' => 'WordPress 2 Factor Authentication Plugin'
			);
		}
		
		$field_string = json_encode($fields);
		
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );
		curl_setopt( $ch, CURLOPT_ENCODING, "" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false);

		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
		
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", $customerKeyHeader, 
											$timestampHeader, $authorizationHeader));
		curl_setopt( $ch, CURLOPT_POST, true);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $field_string);
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt( $ch, CURLOPT_TIMEOUT, 20);
		$content = curl_exec($ch);
		
		if(curl_errno($ch)){
		   return null;
		}
		curl_close($ch);
		
		return $content;
	}
	
	function get_customer_transactions($cKey,$apiKey){
		
		$url = get_option('mo2f_host_name') . '/moas/rest/customer/license';
		$ch = curl_init($url);		
		
		$customerKey = $cKey;
		$apiKey = $apiKey;
	
	    $currentTimeInMillis = round(microtime(true) * 1000);
	
	
		$stringToHash = $customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
		$hashValue = hash("sha512", $stringToHash);
	
		$customerKeyHeader = "Customer-Key: " . $customerKey;
		$timestampHeader = "Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
		$authorizationHeader = "Authorization: " . $hashValue;
	
		
		$fields = '';
			$fields = array(
				'customerId' => $customerKey,
			    'applicationName' => 'wp_2fa',
				'licenseType' => 'DEMO'
			);
		
		$field_string = json_encode($fields);
		
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_ENCODING, "" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false ); 
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
		
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", $customerKeyHeader, $timestampHeader, $authorizationHeader));
		curl_setopt( $ch, CURLOPT_POST, true);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $field_string);
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5);
                 curl_setopt( $ch, CURLOPT_TIMEOUT, 20);
		
		
		/** Proxy Details **/
		if ( defined( 'WP_PROXY_HOST' ) && defined( 'WP_PROXY_PORT' ) ){
			curl_setopt( $ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP );
			curl_setopt( $ch, CURLOPT_PROXY, WP_PROXY_HOST );
			curl_setopt( $ch, CURLOPT_PROXYPORT, WP_PROXY_PORT );
		}
	
		$content = curl_exec($ch);
		if(curl_errno($ch))
			return null;		
		
		curl_close($ch);

		return $content;
	}
	
	function validate_otp_token($authType,$username,$transactionId,$otpToken,$cKey,$customerApiKey){
		if(!MO2f_Utility::is_curl_installed()) {
			$message = 'Please enable curl extension. <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_help">Click here</a> for the steps to enable curl or check Help & Troubleshooting.';
			return json_encode(array("status"=>'ERROR',"message"=>$message));
		}
		
		$url = get_option('mo2f_host_name') . '/moas/api/auth/validate';
		$ch = curl_init($url);
		
		/* The customer Key provided to you */
		$customerKey = $cKey;
	
		/* The customer API Key provided to you */
		$apiKey = $customerApiKey;
	
		/* Current time in milliseconds since midnight, January 1, 1970 UTC. */
		$currentTimeInMillis = round(microtime(true) * 1000);
	
		/* Creating the Hash using SHA-512 algorithm */
		$stringToHash = $customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
		$hashValue = hash("sha512", $stringToHash);
	
		$customerKeyHeader = "Customer-Key: " . $customerKey;
		$timestampHeader = "Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
		$authorizationHeader = "Authorization: " . $hashValue;
		
		$fields = '';
		if( $authType == 'SOFT TOKEN' || $authType == 'GOOGLE AUTHENTICATOR') {
			/*check for soft token*/
			$fields = array(
				'customerKey' => $customerKey,
				'username' => $username,
				'token' => $otpToken,
				'authType' => $authType
			);
		}else if($authType == 'KBA'){
			$fields = array(
				'txId' => $transactionId,
				 'answers' => array(
					array(
						'question' => $otpToken[0],
						'answer' => $otpToken[1]
					),
					array(
						'question' => $otpToken[2],
						'answer' => $otpToken[3]
					)
				)	
			);
		}else{
			//*check for otp over sms/email
			$fields = array(
				'txId' => $transactionId,
				'token' => $otpToken
			);
		}
		$field_string = json_encode($fields);
		
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );
		curl_setopt( $ch, CURLOPT_ENCODING, "" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false);

		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
		
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", $customerKeyHeader, 
											$timestampHeader, $authorizationHeader));
		curl_setopt( $ch, CURLOPT_POST, true);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $field_string);
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt( $ch, CURLOPT_TIMEOUT, 20);
		$content = curl_exec($ch);
		
		if(curl_errno($ch)){
			return null;
		}
		curl_close($ch);
		return $content;
	}
	
	function submit_contact_us( $q_email, $q_phone, $query ) {
		if(!MO2f_Utility::is_curl_installed()) {
			$message = 'Please enable curl extension. <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_help">Click here</a> for the steps to enable curl or check Help & Troubleshooting.';
			return json_encode(array("status"=>'ERROR',"message"=>$message));
		}
		
		$url = get_option('mo2f_host_name') . "/moas/rest/customer/contact-us";
		$ch = curl_init($url);
		global $current_user;
		$current_user = wp_get_current_user();
		$query = '[WordPress 2 Factor Authentication Plugin]: ' . $query;
		$fields = array(
			'firstName'			=> $current_user->user_firstname,
			'lastName'	 		=> $current_user->user_lastname,
			'company' 			=> $_SERVER['SERVER_NAME'],
			'email' 			=> $q_email,
			'phone'				=> $q_phone,
			'query'				=> $query
		);
		$field_string = json_encode( $fields );
		
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );
		curl_setopt( $ch, CURLOPT_ENCODING, "" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false);

		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
		
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json', 'charset: UTF-8', 'Authorization: Basic' ) );
		curl_setopt( $ch, CURLOPT_POST, true);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $field_string);
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt( $ch, CURLOPT_TIMEOUT, 20);
		$content = curl_exec( $ch );
		
		if(curl_errno($ch)){
			return null;
		}
		curl_close($ch);

		return true;
	}
	
	function forgot_password($email){
		if(!MO2f_Utility::is_curl_installed()) {
			$message = 'Please enable curl extension. <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_help">Click here</a> for the steps to enable curl or check Help & Troubleshooting.';
			return json_encode(array("status"=>'ERROR',"message"=>$message));
		}
		
		$url = get_option('mo2f_host_name') . '/moas/rest/customer/password-reset';
		$ch = curl_init($url);
	
		$fields = array(
			'email' => $email
		);
		
		$field_string = json_encode($fields);
		
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );
		curl_setopt( $ch, CURLOPT_ENCODING, "" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false);

		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
		
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json', 'charset: UTF - 8', 'Authorization: Basic' ) );
		curl_setopt( $ch, CURLOPT_POST, true);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $field_string);
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt( $ch, CURLOPT_TIMEOUT, 20);
		$content = curl_exec($ch);
		
		if(curl_errno($ch)){
			return null;
		}
		curl_close($ch);
		return $content;
	}


}?>