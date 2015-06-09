<?php
class CUrlSession {
	public $curl;
	
	function __construct() {
		$this->curl = curl_init ();
		curl_setopt ( $this->curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt ( $this->curl, CURLOPT_FAILONERROR, false );
		curl_setopt ( $this->curl, CURLOPT_SSL_VERIFYPEER, true );
		curl_setopt ( $this->curl, CURLOPT_SSL_VERIFYHOST, 2 );
		curl_setopt ( $this->curl, CURLOPT_HEADER, true );
		curl_setopt ( $this->curl, CURLOPT_TIMEOUT, 20 );
		curl_setopt ( $this->curl, CURLOPT_FRESH_CONNECT, true);
	}
	function __destruct() {
		curl_close ( $this->curl );
	}
	
	/**
	 * Makes an HTTP request to the specified URL
	 *
	 * @param unknown $http_method
	 *        	The HTTP method (GET, POST, PUT, DELETE)
	 * @param unknown $url
	 *        	Full URL of the resource to access
	 * @param string $auth_header
	 *        	(optional) Authorization header
	 * @param string $postData
	 *        	(optional) POST/PUT request body
	 * @return string|Ambigous <string, unknown>|unknown Response body from the server
	 */
	function send_request($http_method, $url, $auth_header = null, $postData = null) {
		curl_setopt ( $this->curl, CURLOPT_URL, $url );
		curl_setopt ( $this->curl, CURLOPT_POST, false );
		
		switch ($http_method) {
			case 'GET' :
				if ($auth_header) {
					curl_setopt ( $this->curl, CURLOPT_HTTPHEADER, array (
							$auth_header 
					) );
				}
				break;
			case 'POST' :
				curl_setopt ( $this->curl, CURLOPT_HTTPHEADER, array (
						'Content-Type: application/json',
						$auth_header 
				) );
				curl_setopt ( $this->curl, CURLOPT_POST, true );
				curl_setopt ( $this->curl, CURLOPT_POSTFIELDS, $postData );
				break;
			case 'PUT' :
				curl_setopt ( $this->curl, CURLOPT_HTTPHEADER, array (
						'Content-Type: application/json',
						$auth_header 
				) );
				curl_setopt ( $this->curl, CURLOPT_CUSTOMREQUEST, $http_method );
				curl_setopt ( $this->curl, CURLOPT_POSTFIELDS, $postData );
				break;
			case 'DELETE' :
				curl_setopt ( $this->curl, CURLOPT_HTTPHEADER, array (
						$auth_header 
				) );
				curl_setopt ( $this->curl, CURLOPT_CUSTOMREQUEST, $http_method );
				break;
		}
		
		$response = curl_exec ( $this->curl );
		
		$httpStatus = curl_getinfo ( $this->curl, CURLINFO_HTTP_CODE );
		$header_size = curl_getinfo ( $this->curl, CURLINFO_HEADER_SIZE );
		$header = substr ( $response, 0, $header_size );
		$body = substr ( $response, $header_size );
		
		//return array(curl_getinfo ($this->curl), $response, curl_error($this->curl), $header_size, $header, $body);
		if ($httpStatus == "200") {
			return $body;
		}
		$httpFields = explode ( "\n", $header );
		$response = $httpFields[0];
		foreach ( $httpFields as $field ) {
			
			if ((strpos ( $field, "X-Nimbusec-Error" )) !== false)
				$response .= $field;
		}
		throw new CUrlException($response);
	}
}

class CUrlException extends Exception {
	// pass
}
