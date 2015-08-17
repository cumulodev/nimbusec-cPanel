<?php
require_once ('NimbusecAPI.php');

class ServerAgent
{
	// Nimbusec API
	private $api;

	function __construct($key, $secret)
	{
		$this->api = new NimbusecAPI($key, $secret);
	}

	function downloadServerAgent($os, $arch, $version, $type)
	{
		$response = $this->api->findSpecificServerAgent($os, $arch, $version, $type);

		if(strpos($response, "ELF") !== false)
		{
			if(!file_exists("/opt/nimbusec/"))
				mkdir("/opt/nimbusec/", 0755);

			if(file_put_contents("/opt/nimbusec/agent", $response) !== false)
			{
				chmod("/opt/nimbusec/agent", 0755);
				return true;
			}else
				throw new Exception(__METHOD__ . " - file_put_contents: Writing process failed");
		}else
			throw new NimbusecException(__METHOD__ . " - A NimbusecException has been thrown: Invalid server-agent starting with: " . substr($response, 0, 2) . "...");
	}

	function createAgentToken($token)
	{
		$response = $this->api->createAgentToken($token);

		if(gettype($response) == "array"){
			return array($response['key'], $response['secret']);
		}else
			throw new NimbusecException(__METHOD__ . " - A NimbusecException has been thrown: Creation of an agent token failed.");
	}

	function createConfigFile($key, $secret)
	{
		$conf = array(
			"key" => $key,
			"secret" => $secret,
			"tmpfile" => "/home/<user>/tmp/hashes-<domain>.txt",
			"domains" => "",
			"excludeDir" => array(),
			"excludeRegexp" => array(),
			"apiserver" => "https://api.nimbusec.com"
		);

		$jsonConf = json_encode ($conf);
		if (json_last_error () != JSON_ERROR_NONE)
			throw new Exception(__METHOD__ . " - json_encode: Config file could not be encoded => " . json_last_error ());

		if(file_put_contents("/opt/nimbusec/agent.conf", $jsonConf) !== false)
			return true;
		else
			throw new Exception(__METHOD__ . " - file_put_contents: Writing process failed");
	}
}
?>
