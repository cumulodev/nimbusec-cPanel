<?php
require_once ('OAuth.php');
require_once ('CUrlSession.php');

class NimbusecAPI {
	
	private $key;
	private $secret;
	private $client;
	
	private $consumer;
	
	private $DEFAULT_BASE_URL = "https://dev-api.nimbusec.com";
	
	function __construct($key, $secret, $BASE_URL = null) {
		$this->key = $key;
		$this->secret = $secret;
		
		if(!empty($BASE_URL))
			$this->DEFAULT_BASE_URL = $BASE_URL;
		
		// Establish an OAuth consumer based on the given credentials
		$this->consumer = new OAuthConsumer ( $this->key, $this->secret );
		
		$this->client = new CUrlSession ();
	}
	
	/**
	 * Creates a domain. 
	 * 
	 * The domain will be created by passing a JSON-object to a REST request (cURL) which sends it via POST to the nimbusec database.
	 * 
	 * @param array $domain - An <b>assoziative array</b> containing the domain object to be inserted
	 * @throws NimbusecException When an error occurs during JSON encoding / decoding process; <br/>Contains the <b>json error message</b>.
	 * @return array - Created domain object as an assoziative array
	 */
	function createDomain($domain) {
		$payload = json_encode ( $domain );
		
		$err = $this->json_last_error_msg_dep();
		if (!empty($err))
			throw new NimbusecException("JSON: an error ocurred '{$err}' while encoding");
		
		$url = $this->DEFAULT_BASE_URL . "/v2/domain";
		
		// Create OAuth request based on OAuth consumer and the specific url
		$request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'POST', $url );
		
		// Make signed OAuth request to contact API server
		$request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
		
		// Get the usable url for the request
		$requestUrl = $request->to_url ();
		
		// Run the cUrl request
		$response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl, null, $payload );
		
		$domain = json_decode ( $response, true );
		
		$err = $this->json_last_error_msg_dep();
		if (!empty($err))
			throw new NimbusecException("JSON: an error occured '{$err}' while trying to decode {$response}");
		else
			return $domain;
	}
	
	/**
	 * Reads all existing domains depending on an optional filter.
	 * 
	 * The function appends the filter to a REST request (cURL) which sends it via GET to the nimbusec database.
	 * 
	 * @param string $filter - Defines the field + value to be filtered by. Filter format: <b>field="value"</b>.<br /><i>NOTE: the filter can be missing or left blank.</i>
	 * @throws NimbusecException When an error occurs during JSON encoding / decoding process; <br/>Contains the <b>json error message</b>.
	 * @return array - A nested array containing all domain objects as an assoziative array
	 */
	function findDomains($filter = null) {
		$url = $this->DEFAULT_BASE_URL . "/v2/domain";
		
		// Create OAuth request based on OAuth consumer and the specific url
		$request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'GET', $url );
		
		if (! empty ( $filter ))
			$request->set_parameter ( 'q', $filter );
			
			// Make signed OAuth request to contact API server
		$request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
		
		// Get the usable url for the request
		$requestUrl = $request->to_url ();
		
		// Run the cUrl request
		$response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
		
		$domains = json_decode ( $response, true );
		
		$err = $this->json_last_error_msg_dep();
		if (!empty($err))
			throw new NimbusecException("JSON: an error occured '{$err}' while trying to decode {$response}");
		else
			return $domains;
	}
	
	/**
	 * Updates an existing domain object.
	 * 
	 * The domain will be updated by passing a JSON-object to a REST request (cURL) which sends it via PUT to the nimbusec database. 
	 * To modify only certain fields of the domain you can include just these fields inside of the domain you pass.
	 * 
	 * The destination path for the request is determined by the $domainID.
	 * 
	 * @param unknown $domainID - The domain's assigned id
	 * @param array $domain - An <b>assoziative array</b> containing the domain object to be updated
	 * @throws NimbusecException When an error occurs during JSON encoding / decoding process; <br/>Contains the <b>json error message</b>.
	 * @return array - Updated domain object as an assoziative array
	 */
	function updateDomain($domainID, $domain) {
		$payload = json_encode ( $domain );
		
		$err = $this->json_last_error_msg_dep();
		if (!empty($err))
			throw new NimbusecException("JSON: an error ocurred '{$err}' while encoding");
		
		$url = $this->DEFAULT_BASE_URL . "/v2/domain/" . $domainID;
		
		// Create OAuth request based on OAuth consumer and the specific url
		$request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'PUT', $url );
		
		// Make signed OAuth request to contact API server
		$request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
		
		// Get the usable url for the request
		$requestUrl = $request->to_url ();
		
		// Run the cUrl request
		$response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl, null, $payload );
		
		$domain = json_decode ( $response, true );
		
		$err = $this->json_last_error_msg_dep();
		if (!empty($err))
			throw new NimbusecException("JSON: an error occured '{$err}' while trying to decode {$response}");
		else
			return $domain;
	}

	function deleteDomain($domainID) {
	
		$url = $this->DEFAULT_BASE_URL . "/v2/domain/" . $domainID;
		
		// Create OAuth request based on OAuth consumer and the specific url
		$request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'DELETE', $url );
			
		// Make signed OAuth request to contact API server
		$request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
		
		// Get the usable url for the request
		$requestUrl = $request->to_url ();
		
		// Run the cUrl request
		$response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
		
		return $response;
	}
	
	/**
	 * Reads all existing bundles depending on an optional filter.
	 *
	 * The function appends the filter to a REST request (cURL) which sends it via GET to the nimbusec database.
	 *
	 * @param string $filter - Defines the field + value to be filtered by. Filter format: <b>field="value"</b>.<br /><i>NOTE: the filter can be missing or left blank.</i>
	 * @throws NimbusecException When an error occurs during JSON encoding / decoding process; <br/>Contains the <b>json error message</b>.
	 * @return array - A nested array containing all bundle objects as an assoziative array
	 */
	function findBundles($filter = null)
	{
		$url = $this->DEFAULT_BASE_URL . "/v2/bundle";
	
		// Create OAuth request based on OAuth consumer and the specific url
		$request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'GET', $url );
	
		if (! empty ( $filter ))
			$request->set_parameter ( 'q', $filter );
			
		// Make signed OAuth request to contact API server
		$request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
	
		// Get the usable url for the request
		$requestUrl = $request->to_url ();
	
		// Run the cUrl request
		$response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
	
		$bundles = json_decode ( $response, true );
		
		$err = $this->json_last_error_msg_dep();
		if (!empty($err))
			throw new NimbusecException("JSON: an error occured '{$err}' while trying to decode {$response}");
		else
			return $bundles;
	}
	
	/**
	 * Creates an user. 
	 * 
	 * The user will be created by passing a JSON-object to a REST request (cURL) which sends it via POST to the nimbusec database.
	 * 
	 * @param array $domain - An <b>assoziative array</b> containing the user object to be inserted
	 * @throws NimbusecException When an error occurs during JSON encoding / decoding process; <br/>Contains the <b>json error message</b>.
	 * @return array - Created user object as an assoziative array
	 */
	function createUser($user) {
		$payload = json_encode ( $user );
		
		$err = $this->json_last_error_msg_dep();
		if (!empty($err))
			throw new NimbusecException("JSON: an error ocurred '{$err}' while encoding");
		
		$url = $this->DEFAULT_BASE_URL . "/v2/user";
		
		// Create OAuth request based on OAuth consumer and the specific url
		$request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'POST', $url );
		
		// Make signed OAuth request to contact API server
		$request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
		
		// Get the usable url for the request
		$requestUrl = $request->to_url ();
		
		// Run the cUrl request
		$response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl, null, $payload );
		
		$user = json_decode ( $response, true );
		
		$err = $this->json_last_error_msg_dep();
		if (!empty($err))
			throw new NimbusecException("JSON: an error occured '{$err}' while trying to decode {$response}");
		else
			return $user;
	}
	
	/**
	 * Reads all existing users depending on an optional filter.
	 *
	 * The function appends the filter to a REST request (cURL) which sends it via GET to the nimbusec database.
	 *
	 * @param string $filter - Defines the field + value to be filtered by. Filter format: <b>field="value"</b>.<br /><i>NOTE: the filter can be missing or left blank.</i>
	 * @throws NimbusecException When an error occurs during JSON encoding / decoding process; <br/>Contains the <b>json error message</b>.
	 * @return array - A nested array containing all user objects as an assoziative array
	 */
	function findUsers($filter = null) {
		$url = $this->DEFAULT_BASE_URL . "/v2/user";
		
		// Create OAuth request based on OAuth consumer and the specific url
		$request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'GET', $url );
		
		if (! empty ( $filter ))
			$request->set_parameter ( 'q', $filter );
			
			// Make signed OAuth request to contact API server
		$request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
		
		// Get the usable url for the request
		$requestUrl = $request->to_url ();
		
		// Run the cUrl request
		$response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
		
		$users = json_decode ( $response, true );
		
		$err = $this->json_last_error_msg_dep();
		if (!empty($err))
			throw new NimbusecException("JSON: an error occured '{$err}' while trying to decode {$response}");
		else
			return $users;
	}
	

	function updateUser($userID, $user) {
		$payload = json_encode ( $user );
		
		$err = $this->json_last_error_msg_dep();
		if (!empty($err))
			throw new NimbusecException("JSON: an error ocurred '{$err}' while encoding");
		
		$url = $this->DEFAULT_BASE_URL . "/v2/user/" . $userID;
		
		// Create OAuth request based on OAuth consumer and the specific url
		$request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'PUT', $url );
		
		// Make signed OAuth request to contact API server
		$request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
		
		// Get the usable url for the request
		$requestUrl = $request->to_url ();
		
		// Run the cUrl request
		$response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl, null, $payload );
		
		$user = json_decode ( $response, true );
		
		$err = $this->json_last_error_msg_dep();
		if (!empty($err))
			throw new NimbusecException("JSON: an error occured '{$err}' while trying to decode {$response}");
		else
			return $user;
	}
	
	function deleteUser($userID)
	{
		$url = $this->DEFAULT_BASE_URL . "/v2/user/" . $userID;
		
		// Create OAuth request based on OAuth consumer and the specific url
		$request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'DELETE', $url );
			
		// Make signed OAuth request to contact API server
		$request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
		
		// Get the usable url for the request
		$requestUrl = $request->to_url ();
		
		// Run the cUrl request
		$response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
		
		return $response;
	}
	
	/**
	 * Creates a notification for a certain user. 
	 * 
	 * The notification will be created by passing a JSON-object to a REST request (cURL) which sends it via POST to the nimbusec database.
	 * The destination path for the request is determined by the userID.
	 *
	 * @param array $notification - An <b>assoziative array</b> containing the notification object to be inserted
	 * @param int | string $userID - The user's assigned id
	 * @throws NimbusecException When an error occurs during JSON encoding / decoding process; <br/>Contains the <b>json error message</b>.
	 * @return array - Created notification object as an assoziative array
	 */
	function createNotification($notification, $userID) {
		$payload = json_encode ( $notification );
	
		$err = $this->json_last_error_msg_dep();
		if (!empty($err))
			throw new NimbusecException("JSON: an error ocurred '{$err}' while encoding");
	
		$url = $this->DEFAULT_BASE_URL . "/v2/user/{$userID}/notification";
	
		// Create OAuth request based on OAuth consumer and the specific url
		$request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'POST', $url );
	
		// Make signed OAuth request to contact API server
		$request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
	
		// Get the usable url for the request
		$requestUrl = $request->to_url ();
	
		// Run the cUrl request
		$response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl, null, $payload );
	
		$notification = json_decode ( $response, true );
	
		$err = $this->json_last_error_msg_dep();
		if (!empty($err))
			throw new NimbusecException("JSON: an error occured '{$err}' while trying to decode {$response}");
		else
			return $notification;
	}
	
	/**
	 * Reads all assigned notifications for a user depending on an optional filter.
	 * 
	 * The function appends the filter to a REST request (cURL) which sends it via GET to the nimbusec database. 
	 * The destination path for the request is determined by the userID.
	 * 
	 * @param int | string $userID - The user's assigned id
	 * @param string $filter - Defines the field + value to be filtered by. Filter format: <b>field="value"</b>.<br /><i>NOTE: the filter can be missing or left blank.</i>
	 * @throws NimbusecException When an error occurs during JSON encoding / decoding process; <br/>Contains the <b>json error message</b>.
	 * @return array - A nested array containing all notification objects as an assoziative array
	 */
	function findNotifications($userID, $filter = null)
	{
		$url = $this->DEFAULT_BASE_URL . "/v2/user/{$userID}/notification";
	
		// Create OAuth request based on OAuth consumer and the specific url
		$request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'GET', $url );
			
		if (! empty ( $filter ))
			$request->set_parameter ( 'q', $filter );
	
		// Make signed OAuth request to contact API server
		$request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
	
		// Get the usable url for the request
		$requestUrl = $request->to_url ();
	
		// Run the cUrl request
		$response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
	
		$notifications = json_decode ( $response, true );
	
		$err = $this->json_last_error_msg_dep();
		if (!empty($err))
			throw new NimbusecException("JSON: an error occured '{$err}' while trying to decode {$response}");
		else
			return $notifications;
	}
	
	/**
	 * Assigns a certain domain to a certain user.
	 * 
	 * The domainset will be created by passing a domainID to a REST request (cURL) which sends it via POST to the nimbusec database.
	 * The destination path for the request is determined by the userID.
	 * 
	 * @param int | string $userID - The user's assigned id
	 * @param int | string $domainID - The domain's assigned id
	 * @throws NimbusecException When an error occurs during JSON encoding / decoding process; <br/>Contains the <b>json error message</b>.
	 * @return array - A nested array containing a list of id's of all domains
	 */
	function createDomainSet($userID, $domainID)
	{
		$url = $this->DEFAULT_BASE_URL . "/v2/user/" . $userID . "/domains";
	
		// Create OAuth request based on OAuth consumer and the specific url
		$request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'POST', $url );
			
		// Make signed OAuth request to contact API server
		$request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
	
		// Get the usable url for the request
		$requestUrl = $request->to_url ();
	
		// Run the cUrl request
		$response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl, NULL, $domainID);
	
		$domainSet = json_decode ( $response, true );
		
		$err = $this->json_last_error_msg_dep();
		if (!empty($err))
			throw new NimbusecException("JSON: an error occured '{$err}' while trying to decode {$response}");
		else
			return $domainSet;
	}
	
	/**
	 * Reads all assigned domains for a certain user.
	 *
	 * The function appends the filter to a REST request (cURL) which sends it via GET to the nimbusec database.
	 * The destination path for the request is determined by the userID.
	 *
	 * @param int | string $userID - The user's assigned id
	 * @throws NimbusecException When an error occurs during JSON encoding / decoding process; <br/>Contains the <b>json error message</b>.
	 * @return array - A nested array containing a list of id's of all domains
	 */
	function findDomainSet($userID)
	{
		$url = $this->DEFAULT_BASE_URL . "/v2/user/" . $userID . "/domains";
		
		// Create OAuth request based on OAuth consumer and the specific url
		$request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'GET', $url );
			
		// Make signed OAuth request to contact API server
		$request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
		
		// Get the usable url for the request
		$requestUrl = $request->to_url ();
		
		// Run the cUrl request
		$response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
		
		$domainSet = json_decode ( $response, true );
		
		$err = $this->json_last_error_msg_dep();
		if (!empty($err))
			throw new NimbusecException("JSON: an error occured '{$err}' while trying to decode {$response}");
		else
			return $domainSet;
	}
	
	function deleteFromDomainSet($userID, $domainID)
	{
		$url = $this->DEFAULT_BASE_URL . "/v2/user/" . $userID . "/domains/" . $domainID;
	
		// Create OAuth request based on OAuth consumer and the specific url
		$request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'DELETE', $url );
			
		// Make signed OAuth request to contact API server
		$request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
		
		// Get the usable url for the request
		$requestUrl = $request->to_url ();
		
		// Run the cUrl request
		$response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
		
		return $response;
	}
	
	function findServerAgents($filter = null)
	{
		$url = $this->DEFAULT_BASE_URL . "/v2/agent/download";
		
		// Create OAuth request based on OAuth consumer and the specific url
		$request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'GET', $url );
		
		if (! empty ( $filter ))
			$request->set_parameter ( 'q', $filter );
			
		// Make signed OAuth request to contact API server
		$request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
		
		// Get the usable url for the request
		$requestUrl = $request->to_url ();
		
		// Run the cUrl request
		$response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl);
		
		$serverAgents = json_decode ( $response, true );
		
		$err = $this->json_last_error_msg_dep();
		if (!empty($err))
			throw new NimbusecException("JSON: an error occured '{$err}' while trying to decode {$response}");
		else
			return $serverAgents;
	}
	
	function findSpecificServerAgent($os, $arch, $version, $type)
	{
		$url = $this->DEFAULT_BASE_URL . "/v2/agent/download/nimbusagent-{$os}-{$arch}-{$version}.{$type}";
		
		// Create OAuth request based on OAuth consumer and the specific url
		$request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'GET', $url );
			
		// Make signed OAuth request to contact API server
		$request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
		
		// Get the usable url for the request
		$requestUrl = $request->to_url ();
		
		// Run the cUrl request
		$response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl);
		
		return $response;
	}
	
	function createAgentToken($token)
	{
		$payload = json_encode ( $token );
		
		$err = $this->json_last_error_msg_dep();
		if (!empty($err))
			throw new NimbusecException("JSON: an error ocurred '{$err}' while encoding");
		
		$url = $this->DEFAULT_BASE_URL . "/v2/agent/token";
		
		// Create OAuth request based on OAuth consumer and the specific url
		$request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'POST', $url );
		
		// Make signed OAuth request to contact API server
		$request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
		
		// Get the usable url for the request
		$requestUrl = $request->to_url ();
		
		// Run the cUrl request
		$response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl, null, $payload );
		
		$token = json_decode ( $response, true );
		
		$err = $this->json_last_error_msg_dep();
		if (!empty($err))
			throw new NimbusecException("JSON: an error occured '{$err}' while trying to decode {$response}");
		else
			return $token;
	}
	
	function findAgentToken($filter = null)
	{
		$url = $this->DEFAULT_BASE_URL . "/v2/agent/token";
		
		// Create OAuth request based on OAuth consumer and the specific url
		$request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'GET', $url );
		
		if (! empty ( $filter ))
			$request->set_parameter ( 'q', $filter );
			
		// Make signed OAuth request to contact API server
		$request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
		
		// Get the usable url for the request
		$requestUrl = $request->to_url ();
		
		// Run the cUrl request
		$response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl);
		
		$tokens = json_decode ( $response, true );
		
		$err = $this->json_last_error_msg_dep();
		if (!empty($err))
			throw new NimbusecException("JSON: an error occured '{$err}' while trying to decode {$response}");
		else
			return $tokens;
	}
	
	function updateAgentToken($tokenID, $token) {
		$payload = json_encode ( $token );
		
		$err = $this->json_last_error_msg_dep();
		if (!empty($err))
			throw new NimbusecException("JSON: an error ocurred '{$err}' while encoding");
		
		$url = $this->DEFAULT_BASE_URL . "/v2/agent/token/" . $tokenID;
		
		// Create OAuth request based on OAuth consumer and the specific url
		$request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'PUT', $url );
		
		// Make signed OAuth request to contact API server
		$request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
		
		// Get the usable url for the request
		$requestUrl = $request->to_url ();
		
		// Run the cUrl request
		$response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl, null, $payload );
		
		$token = json_decode ( $response, true );
		
		$err = $this->json_last_error_msg_dep();
		if (!empty($err))
			throw new NimbusecException("JSON: an error occured '{$err}' while trying to decode {$response}");
		else
			return $token;
	}
	
	function deleteAgentToken($tokenID)
	{
		$url = $this->DEFAULT_BASE_URL . "/v2/agent/token/" . $tokenID;
		
		// Create OAuth request based on OAuth consumer and the specific url
		$request = OAuthRequest::from_consumer_and_token ( $this->consumer, NULL, 'DELETE', $url );
			
		// Make signed OAuth request to contact API server
		$request->sign_request ( new OAuthSignatureMethod_HMAC_SHA1 (), $this->consumer, NULL );
		
		// Get the usable url for the request
		$requestUrl = $request->to_url ();
		
		// Run the cUrl request
		$response = $this->client->send_request ( $request->get_normalized_http_method (), $requestUrl );
		
		return $response;
	}
	
	/**
	 * This method is used as for a PHP 5 < 5.5.0 environment.<br /> 
	 * It determines the last json error and return a error message
	 * 
	 * <i>Note: The method json_last_error_msg() does the same, but is included <b>only</b> in PHP 5 >= 5.5.0</i>
	 * 
	 * @return string | NULL - Null on success (no error) or the json error message on failure
	 */
	private function json_last_error_msg_dep() {
		static $errors = array(
				JSON_ERROR_NONE             => null,
				JSON_ERROR_DEPTH            => 'Maximum stack depth exceeded',
				JSON_ERROR_STATE_MISMATCH   => 'Underflow or the modes mismatch',
				JSON_ERROR_CTRL_CHAR        => 'Unexpected control character found',
				JSON_ERROR_SYNTAX           => 'Syntax error, malformed JSON',
				JSON_ERROR_UTF8             => 'Malformed UTF-8 characters, possibly incorrectly encoded'
		);
		$error = json_last_error();
		return array_key_exists($error, $errors) ? $errors[$error] : "Unknown error ({$error})";
	}
}

class NimbusecException extends Exception {
	// pass
}

?>