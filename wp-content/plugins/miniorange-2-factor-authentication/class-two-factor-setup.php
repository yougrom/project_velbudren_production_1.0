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
class Two_Factor_Setup{
	
	public $email;
	
	function check_mobile_status($tId){
		if(!MO2f_Utility::is_curl_installed()) {
			$message = 'Please enable curl extension. <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_help">Click here</a> for the steps to enable curl or check Help & Troubleshooting.';
			return json_encode(array("status"=>'ERROR',"message"=>$message));
		}
		
		$url = get_option('mo2f_host_name') . '/moas/api/auth/auth-status';
		$ch = curl_init($url);
		
		/* The customer Key provided to you */
		$customerKey = get_option('mo2f_customerKey');
	
		/* The customer API Key provided to you */
		$apiKey = get_option('mo2f_api_key');
	
		/* Current time in milliseconds since midnight, January 1, 1970 UTC. */
		$currentTimeInMillis = round(microtime(true) * 1000);
	
		/* Creating the Hash using SHA-512 algorithm */
		$stringToHash = $customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
		$hashValue = hash("sha512", $stringToHash);
	
		$customerKeyHeader = "Customer-Key: " . $customerKey;
		$timestampHeader = "Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
		$authorizationHeader = "Authorization: " . $hashValue;
		
		$fields = array(
			'txId' => $tId
		);
		
		$field_string = json_encode($fields);

		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );
		curl_setopt( $ch, CURLOPT_ENCODING, "" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

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
	
	function register_mobile($useremail){
		if(!MO2f_Utility::is_curl_installed()) {
			$message = 'Please enable curl extension. <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_help">Click here</a> for the steps to enable curl or check Help & Troubleshooting.';
			return json_encode(array("status"=>'ERROR',"message"=>$message));
		}
		
		$url = get_option('mo2f_host_name') . '/moas/api/auth/register-mobile';
		$ch = curl_init($url);
		global $current_user;
		$current_user = wp_get_current_user();
		$this->email = $useremail;
		
		/* The customer Key provided to you */
		$customerKey = get_option('mo2f_customerKey');
	
		/* The customer API Key provided to you */
		$apiKey = get_option('mo2f_api_key');
	
		/* Current time in milliseconds since midnight, January 1, 1970 UTC. */
		$currentTimeInMillis = round(microtime(true) * 1000);
	
		/* Creating the Hash using SHA-512 algorithm */
		$stringToHash = $customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
		$hashValue = hash("sha512", $stringToHash);
	
		$customerKeyHeader = "Customer-Key: " . $customerKey;
		$timestampHeader = "Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
		$authorizationHeader = "Authorization: " . $hashValue;
		
		$fields = array(
			'customerId' => $customerKey,
			'username' => $this->email
		);
		
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
	
	function mo_check_user_already_exist($email){
		if(!MO2f_Utility::is_curl_installed()) {
			$message = 'Please enable curl extension. <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_help">Click here</a> for the steps to enable curl or check Help & Troubleshooting.';
			return json_encode(array("status"=>'ERROR',"message"=>$message));
		}
		
		$url = get_option('mo2f_host_name') . '/moas/api/admin/users/search';
		$ch = curl_init($url);
		
		/* The customer Key provided to you */
		$customerKey = get_option('mo2f_customerKey');
	
		/* The customer API Key provided to you */
		$apiKey = get_option('mo2f_api_key');
	
		/* Current time in milliseconds since midnight, January 1, 1970 UTC. */
		$currentTimeInMillis = round(microtime(true) * 1000);
	
		/* Creating the Hash using SHA-512 algorithm */
		$stringToHash = $customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
		$hashValue = hash("sha512", $stringToHash);
	
		$customerKeyHeader = "Customer-Key: " . $customerKey;
		$timestampHeader = "Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
		$authorizationHeader = "Authorization: " . $hashValue;
		
		$fields = array(
			'customerKey' => $customerKey,
			'username' => $email,
			
		);
		
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
	
	function mo_create_user($currentuser,$email){
		if(!MO2f_Utility::is_curl_installed()) {
			$message = 'Please enable curl extension. <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_help">Click here</a> for the steps to enable curl or check Help & Troubleshooting.';
			return json_encode(array("status"=>'ERROR',"message"=>$message));
		}
		
		$url = get_option('mo2f_host_name') . '/moas/api/admin/users/create';
		$ch = curl_init($url);
		
		/* The customer Key provided to you */
		$customerKey = get_option('mo2f_customerKey');
	
		/* The customer API Key provided to you */
		$apiKey = get_option('mo2f_api_key');
	
		/* Current time in milliseconds since midnight, January 1, 1970 UTC. */
		$currentTimeInMillis = round(microtime(true) * 1000);
	
		/* Creating the Hash using SHA-512 algorithm */
		$stringToHash = $customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
		$hashValue = hash("sha512", $stringToHash);
	
		$customerKeyHeader = "Customer-Key: " . $customerKey;
		$timestampHeader = "Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
		$authorizationHeader = "Authorization: " . $hashValue;
		
		$fields = array(
			'customerKey' => $customerKey,
			'username' => $email,
			'firstName' => $currentuser->user_firstname,
			'lastName' => $currentuser->user_lastname
		);
		
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
	
	function mo2f_get_userinfo($email){
		if(!MO2f_Utility::is_curl_installed()) {
			$message = 'Please enable curl extension. <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_help">Click here</a> for the steps to enable curl or check Help & Troubleshooting.';
			return json_encode(array("status"=>'ERROR',"message"=>$message));
		}
		
		$url = get_option('mo2f_host_name') . '/moas/api/admin/users/get';
		$ch = curl_init($url);
		
		/* The customer Key provided to you */
		$customerKey = get_option('mo2f_customerKey');
	
		/* The customer API Key provided to you */
		$apiKey = get_option('mo2f_api_key');
	
		/* Current time in milliseconds since midnight, January 1, 1970 UTC. */
		$currentTimeInMillis = round(microtime(true) * 1000);
	
		/* Creating the Hash using SHA-512 algorithm */
		$stringToHash = $customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
		$hashValue = hash("sha512", $stringToHash);
	
		$customerKeyHeader = "Customer-Key: " . $customerKey;
		$timestampHeader = "Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
		$authorizationHeader = "Authorization: " . $hashValue;
		
		$fields = array(
			'customerKey' => $customerKey,
			'username' => $email,
		);
		
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
	
	function mo2f_update_userinfo($email,$authType,$phone,$tname,$enableAdminSecondFactor){
		if(!MO2f_Utility::is_curl_installed()) {
			$message = 'Please enable curl extension. <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_help">Click here</a> for the steps to enable curl or check Help & Troubleshooting.';
			return json_encode(array("status"=>'ERROR',"message"=>$message));
		}
		
		$url = get_option('mo2f_host_name') . '/moas/api/admin/users/update';
		$ch = curl_init($url);
		
		/* The customer Key provided to you */
		$customerKey = get_option('mo2f_customerKey');
	
		/* The customer API Key provided to you */
		$apiKey = get_option('mo2f_api_key');
	
		/* Current time in milliseconds since midnight, January 1, 1970 UTC. */
		$currentTimeInMillis = round(microtime(true) * 1000);
	
		/* Creating the Hash using SHA-512 algorithm */
		$stringToHash = $customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
		$hashValue = hash("sha512", $stringToHash);
	
		$customerKeyHeader = "Customer-Key: " . $customerKey;
		$timestampHeader = "Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
		$authorizationHeader = "Authorization: " . $hashValue;
		if($authType == 'PUSH'){
			$authType = 'PUSH NOTIFICATIONS';
		}
		
		$fields = array(
			'customerKey' => $customerKey,
			'username' => $email,
			'phone' => $phone,
			'authType' => $authType,
			'transactionName' => $tname,
			'adminLoginSecondFactor' => $enableAdminSecondFactor
		);
		
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
	
	function register_kba_details($email,$question1,$answer1,$question2,$answer2,$question3,$answer3){
		if(!MO2f_Utility::is_curl_installed()) {
			$message = 'Please enable curl extension. <a href="admin.php?page=miniOrange_2_factor_settings&amp;mo2f_tab=mo2f_help">Click here</a> for the steps to enable curl or check Help & Troubleshooting.';
			return json_encode(array("status"=>'ERROR',"message"=>$message));
		}
		
		$url = get_option('mo2f_host_name') . '/moas/api/auth/register';
		$ch = curl_init($url);
		
		/* The customer Key provided to you */
		$customerKey = get_option('mo2f_customerKey');
	
		/* The customer API Key provided to you */
		$apiKey = get_option('mo2f_api_key');
	
		/* Current time in milliseconds since midnight, January 1, 1970 UTC. */
		$currentTimeInMillis = round(microtime(true) * 1000);
	
		/* Creating the Hash using SHA-512 algorithm */
		$stringToHash = $customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
		$hashValue = hash("sha512", $stringToHash);
	
		$customerKeyHeader = "Customer-Key: " . $customerKey;
		$timestampHeader = "Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
		$authorizationHeader = "Authorization: " . $hashValue;
		
		$q_and_a_list = "[{\"question\":\"" . $question1 . "\",\"answer\":\"" . $answer1 . "\" },{\"question\":\"" . $question2 . "\",\"answer\":\"" . $answer2 . "\" },{\"question\":\"" . $question3 . "\",\"answer\":\"" . $answer3 . "\" }]";
		
		$field_string = "{\"customerKey\":\"" . $customerKey . "\",\"username\":\"" . $email . "\",\"questionAnswerList\":" . $q_and_a_list . "}";
		
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
	
}
?>