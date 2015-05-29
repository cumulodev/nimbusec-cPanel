<?php
require_once ('CUrlSession.php');

class WHMAPI {

	private $userName = 'root';
	private $urlScheme = 'https';
	private $urlServerAddr;
	private $urlPort = '2087';
	private $formatType = 'json-api';
	private $apiVersion = '1';
	
	private $accessHash;
	private $curlSession;
	
	private $urlParam = '';
	
	function __construct($accessHash, $urlServerAddr, $userName = null, $urlScheme = null, $urlPort = null, $formatType = null, $apiVersion = null)
	{
		$this->accessHash = $accessHash;
		$this->urlServerAddr = $urlServerAddr;
		
		if(!empty($userName))
			$this->userName = $userName;
		if(!empty($urlScheme))
			$this->urlScheme = $urlScheme;
		if(!empty($urlPort))
			$this->urlPort = $urlPort;
		if(!empty($formatType))
			$this->formatType = $formatType;
		if(!empty($apiVersion))
			$this->apiVersion = $apiVersion;
		
		$this->init();
	}
	
	function init()
	{
		$this->curlSession = new CUrlSession();
	}
	
	function sendRequest($whmMethod, $paramArr = null)
	{
		$httpMethod = 'GET';
	
		$this->urlParam = "";
	
		// Parameter handling
		if(!empty($paramArr)){
			if(gettype($paramArr) == "array"){
				
				foreach($paramArr as $key => $value)
				{
					$value = rawurlencode($value);
					$this->urlParam .= "&{$key}={$value}";
				}
				
			}else 			
				throw new WHMException("WHM Error: invalid param type: '" . gettype($paramArr) . "' when trying to call whm method: '{$whmMethod}'");
		}
		
		// Define http auth header based on the passed whm access hash 
		$header = "Authorization: WHM {$this->userName}:" . preg_replace("'(\r|\n)'", "", $this->accessHash);
	 		
		// Define url
		$url =  "{$this->urlScheme}://{$this->urlServerAddr}:{$this->urlPort}/{$this->formatType}/{$whmMethod}?api.version={$this->apiVersion}{$this->urlParam}";
		$response = $this->curlSession->send_request ( $httpMethod, $url, $header, null);
		
		$this->init();
		
		$output = json_decode ( $response, true );
		//file_put_contents("/usr/local/nimbusec/nimbusec/nimbuConnectionLog.log", "Header: {$header}\n\nUrl: {$url}\n\nWhmMethod: {$whmMethod}\n\nHTTPMethod: {$httpMethod}\n\nUrl Scheme: {$this->urlScheme}\n\nUrl Params: {$this->urlParam}\n\nResponse: " . gettype($output) . gettype($response)  . "{$response}\n\nIs client set: " . isset($this->curlSession) .  " " . empty($this->curlSession) . "\n\n\n\n\n", FILE_APPEND);
		
		if (json_last_error () == JSON_ERROR_NONE){
			if($output['metadata'] ['result'])
				return $output;
			else
				throw new WHMException("A WHM specific error occured while trying to call '{$output['metadata'] ['command']}':\n
				Status: {$output['metadata'] ['result']}\n
				Reason: {$output['metadata'] ['reason']}\n");
		}
		else
			throw new WHMException($response);
	}
	
	/**
	 * Send an NVGet request to the WHM API and process the result
	 * 
	 * @param array $nvArr - An array containing n keys for n 'nvget' requests structured the following way: <br />
	 * <b>array = { [0] = $key1, [1] = $key2, [2] = ... } </b>
	 * @return array - Returns a simple array with the values belonging to the keys in the same order
	 */
	function getNVData($nvArr)
	{
		$resArr = array();
		
		foreach ($nvArr as $field)
		{
			$reqArr = array("key" => $field);
			$res = $this->sendRequest('nvget', $reqArr);
			
			array_push($resArr, $res ['data'] ['nvdatum'] ['value'][0]);
			
			$this->init();
		}
		
		return $resArr;
	}
	
	/**
	 * Send an NVSet request to the WHM API and process the result
	 * 
	 * @param array $nvArr - An array containing n simple arrays for n 'nvset' requests structured the following way: <br />
	 * <b>array = { [0] = array { [0] = $key, [1] = $value }, [1] = ... } </b>
	 * @return boolean - Returns true <b>only</b> if all requests have been conducted properly 
	 */
	function setNVData($nvArr)
	{
		foreach($nvArr as $field)
		{
			$reqArr = array("key" => $field[0], "value" => $field[1]);	
			
			$res = $this->sendRequest('nvset', $reqArr);
			$this->init();
		}
		
		return true;
	}
}

class WHMException extends Exception {
	// pass
}


?>
