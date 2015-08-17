<?php
require_once ("/usr/local/nimbusec/lib/ServerAgent.php");
require_once ("/usr/local/nimbusec/lib/Logger.php");

$responseArray = array();
$responseArray['status'] = 0;
$responseArray['content'] = array();

try{

	$logger = new Logger("/usr/local/nimbusec/logs", "install_error.log");
	$logger->progress("Nimbusec installation begins...");

	// To remind, every error will be handled in the particular method of each PHP (!) class you use
	// and catched in this class
	// So you can log after every command without needing to check whether it worked properly or not
	// because if it's not, it will be catched by the 'catch' - clause which will log it in addition

	// ################################## 1.) Server agent ##################################

	$serverAgent = new ServerAgent($apiKey, $apiSecret);
	$serverAgent->downloadServerAgent("linux", "64bit", "v7", "bin");
	$logger->info("Server agent downloaded and installed");

	// Define token name
	$name = "cPanelToken_" . date('Y/m/d-H:i:s');
	$token = array( "name" => $name	);
	list($serverAgentKey, $serverAgentSecret) = $serverAgent->createAgentToken($token);

	// Create array for calling whm api
	$serverAgentArr = array(array("NIMBUSEC_SERVERAGENTKEY", $serverAgentKey), array("NIMBUSEC_SERVERAGENTSECRET", $serverAgentSecret));
	// Call method to save credentials in non-volatile datastores (nvdata)
	$whmApi->setNVData($serverAgentArr);
	$logger->info("Server agent token created");

	// Default param $server
	$serverAgent->createConfigFile($serverAgentKey, $serverAgentSecret);
	$logger->info("Server agent config file created");
	array_push($responseArray['content'], "Server agent installation finished");
	$logger->progress("Server agent installation finished");

	// ################################## 2.) Package extensions ##################################

	$packageExt = new PackageExtensions($apiKey, $apiSecret);

	$res1 = copy("/usr/local/nimbusec/nimbusec/package_extensions/nimbusec", PackageExtensions::$extensionSettingPath);
	$res2 = copy("/usr/local/nimbusec/nimbusec/package_extensions/nimbusec.tt2", PackageExtensions::$extensionTemplatePath);
	if(!$res1 || !$res2)
		throw new Exception(__METHOD__ . " - Package extension: Couldn't copy extention files.\nStatus setting: ". json_encode($res1) . "\nStatus template: ". json_encode($res2) . "\n");

	$logger->info("Pacakge extensions copied");
	$packageExt->updatePackageExtensions();
	$logger->progress("Package extentions installed and updated");
	array_push($responseArray['content'], "Package extentions installed and updated");

	// ################################## 3.) Cron job ##################################

	if(file_put_contents("/etc/cron.daily/nimbusec", "#!/bin/bash") === false)
		throw new Exception(__METHOD__ . " - file_put_contents: creating cron job failed");

	if(!chmod("/etc/cron.daily/nimbusec", 0755))
		throw new Exception(__METHOD__ . " - chmod: setting permissions failed");

	$logger->progress("Cron job created");
	array_push($responseArray['content'], "Cron job created");

	// ################################## 4.) Hooks + cPanel ##################################

	// -- Installing hooks --
	$disabled = explode(',', ini_get('disable_functions'));
	$logger->debug(ini_get('disable_functions'));
	$funcArr = array('exec', 'shell_exec');
	$sysEnabled = false;
	$func = "";

	foreach($funcArr as $disFunc){
		if(function_exists($disFunc) && !in_array($disFunc, $disabled)){
			$sysEnabled = true; $func = $disFunc;
		}
	}

	if($sysEnabled){

		$res = $func('/usr/local/nimbusec/nimbusec/hooks/install.sh');
		$logger->debug("Hooks installation message: ". $res);

		if(strpos($res, "#") === false)
			throw new Exception(__METHOD__ . " - An unknown error occured while installing hooks: {$res}");

		list($stat, $message) = explode('#', $res);
		if(!$stat)
			throw new Exception(__METHOD__ . " - Hook installation failed: {$message}");

		$logger->progress($message);
		array_push($responseArray['content'], $message);

		// -- Installing cpanel --
		$res = $func('/usr/local/nimbusec/nimbusec/cpanel_plugin/install.sh');
		$logger->debug("cPanel installation message: ". $res);

		if(strpos($res, "#") === false)
			throw new Exception(__METHOD__ . " - An unknown error occured while installing cpanel: {$res}");

		list($stat, $message) = explode('#', $res);
		if(!$stat)
			throw new Exception(__METHOD__ . " - cPanel installation failed: {$message}");

		$logger->progress($message);
		array_push($responseArray['content'], $message);
		$responseArray['status'] = 1;

		return $responseArray;

	}else{
		$instructions = "You have disabled the function \"$func\" in your PHP configuration.\n
		Therefore, the installation cannot be finished.\n
		Take the following steps to complete the cPanel installation:\n
			1.) Run \"$ /usr/local/nimbusec/nimbusec/hooks/install.sh\" to ensure that necessary internal script hooks are being included by WHM.\n
			2.) Run \"$ /usr/local/nimbusec/nimbusec/cpanel_plugin/install.sh\" to install the Nimbusec plugin on cPanel side.\n
			3.) Only when the script were executed successfully, add the following line to /var/cpanel/whm/nvdata/root.yaml which contains all environmental variables\n
			- \"NIMBUSEC_INSTALLED: 1\" - or change the value to 1 if the field already exists.
			4.) Afterwards, refresh the WHM plugin page to continue.
		Alternatively, you may want to re-enable the affected function in your configuration and try it again.";
		$logger->error($instructions);

		array_push($responseArray['content'], "Nimbusec installation aborted...");
		return $responseArray;
	}
}
catch(CUrlException $exp)
{
	$logger->error("[CURL SPECIFIC ERROR] in {$exp->getFile()}: {$exp->getMessage()} at line {$exp->getLine()}");
	$logger->info("Nimbusec installation aborted...");
	$logger->close();

	array_push($responseArray['content'], "Nimbusec installation aborted...");
	return $responseArray;
}
catch(NimbusecException $exp)
{
	$logger->error("[Nimbusec SPECIFIC ERROR] in {$exp->getFile()}: {$exp->getMessage()} at line {$exp->getLine()}");
	$logger->info("Nimbusec installation aborted...");
	$logger->close();

	array_push($responseArray['content'], "Nimbusec installation aborted...");
	return $responseArray;
}
catch(WHMException $exp)
{
	$logger->error("[WHM SPECIFIC ERROR] in {$exp->getFile()}: {$exp->getMessage()} at line {$exp->getLine()}");
	$logger->info("Nimbusec installation aborted...");
	$logger->close();

	array_push($responseArray['content'], "Nimbusec installation aborted...");
	return $responseArray;
}
catch(Exception $exp)
{
	$logger->error("[UNSPECIFIC ERROR] in {$exp->getFile()}: {$exp->getMessage()} at line {$exp->getLine()}");
	$logger->info("Nimbusec installation aborted...");
	$logger->close();

	array_push($responseArray['content'], "Nimbusec installation aborted...");
	return $responseArray;
}
?>
