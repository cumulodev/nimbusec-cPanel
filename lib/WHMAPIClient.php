<?php
require_once ('CURLClient.php');

// Custom class serving as a interface between the WHM API and the Nimbusec plugin
// It uses the access hash authentication to communicate with the WHM API as described on
// https://documentation.cpanel.net/display/SDK/Guide+to+API+Authentication
class WHMAPIClient {

	// -- Given by WHM --
	private $userName = 'root';
	private $urlScheme = 'https';
	private $urlServerAddr;
	private $urlPort = '2087';
	private $formatType = 'json-api';
	private $apiVersion = '1';

	private $accessHash;

	// -- CURL instance --
	private $client;

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

		// -- As WHM doesn't "support" SSL authentication (they use their own cp_security_token instead)
		//	  It's necessary to create a CURL instance without the specific SSL flags being set --
		$this->client = new CURLClient(false, false);
	}

	function sendRequest($whmMethod, $paramArr = null)
	{
		$urlParams = "";

		// Parameter handling
		if(!empty($paramArr)){
			if(gettype($paramArr) == "array"){
				foreach($paramArr as $key => $value){
					$value = rawurlencode($value);
					$urlParams .= "&{$key}={$value}";
				}
			}else
				throw new WHMException("WHM Error: invalid param type: '" . gettype($paramArr));
		}

		// Define http auth header based on the passed whm access hash
		$header = "Authorization: WHM {$this->userName}:" . preg_replace("'(\r|\n)'", "", $this->accessHash);

		// Define url
		$url =  "{$this->urlScheme}://{$this->urlServerAddr}:{$this->urlPort}/{$this->formatType}/{$whmMethod}?api.version={$this->apiVersion}{$urlParams}";
		$response = $this->client->send_request ( "GET", $url, $header);

		$output = json_decode ( $response, true );

		if (json_last_error () === JSON_ERROR_NONE){
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
	 * NVGet do now allow to append multiple parameter
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
		}

		return $resArr;
	}

	/**
	 * Send an NVSet request to the WHM API and process the result
	 * NVSet do not allow to append multiple parameter
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
		}

		return true;
	}
}

class WHMException extends Exception {
	// pass
}
?>
