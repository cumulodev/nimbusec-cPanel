<?php
require_once ("/usr/local/nimbusec/lib/WHMAPI.php");
require_once ("/usr/local/nimbusec/lib/ServerAgent.php");
require_once ("/usr/local/nimbusec/lib/PackageExtensions.php");
require_once ("/usr/local/nimbusec/lib/Logger.php");

$stat = 0;
$message = "";

try{
	
	$logger = new Logger("/usr/local/nimbusec/nimbusec/logs", "installNimbusec.log", true);
	$logger->info("Nimbusec installation begins...");
	
	// Get access data for WHM API
	$hash = file_get_contents( "/root/.accesshash");
	$host = gethostname();
	$serverAddr = gethostbyname($host);
	
	$whmApi = new WHMAPI( $hash, $serverAddr );
	list($key, $secret, $server) = $whmApi->getNVData(array("NIMBUSEC_APIKEY", "NIMBUSEC_APISECRET", "NIMBUSEC_APISERVER"));
	
	$logger->debug("Retrieved [key]: {$key}, [secret]: {$secret}, and [server]: {$server} from nvdata stores");
	
	// To remind, every error will be handled in the particular method of each PHP (!) class you use
	// and catched in this class
	// So you can log after every command without needing to check whether it worked properly or not
	// because if it's not, it will be catched by the 'catch' - clause which will log it in addition
	
	// ################################## 1.) Server agent ##################################
	
	$serverAgent = new ServerAgent($key, $secret, $server);
	$serverAgent->downloadServerAgent("linux", "64bit", "v7", "bin");
	$logger->info("Server agent downloaded and installed");
	
	// Define token name
	$token = array( "name" => date('Y/m/d-H:i:s') . "Token"	);
	list($serverAgentKey, $serverAgentSecret) = $serverAgent->createAgentToken($token);
	
	// Create array for calling whm api
	$serverAgentArr = array(array("NIMBUSEC_SERVERAGENTKEY", $serverAgentKey), array("NIMBUSEC_SERVERAGENTSECRET", $serverAgentSecret));
	// Call method to save credentials in non-volatile datastores (nvdata)
	$whmApi->setNVData($serverAgentArr);
	
	$logger->info("Server agent token created");

	// Default param $server
	$serverAgent->createConfigFile($serverAgentKey, $serverAgentSecret, $server);
	$logger->info("Server agent config file created");

	// ################################## 2.) Package extensions ##################################
	
	$packageExt = new PackageExtensions($key, $secret, $server);
	
	$res1 = copy("/usr/local/nimbusec/nimbusec/package_extensions/nimbusec", PackageExtensions::$extensionSettingPath);
	$res2 = copy("/usr/local/nimbusec/nimbusec/package_extensions/nimbusec.tt2", PackageExtensions::$extensionTemplatePath);
	if(!$res1 || !$res2)
		throw new Exception("Package extension: Couldn't copy extention files.\nStatus setting: {$res1}\nStatus template: {$res2}\n");
	
	$logger->info("Pacakge extensions copied");
	$packageExt->updatePackageExtensions();
	$logger->info("Package extensions updated");
	
	// ################################## 3.) Cron job ##################################
	
	if(file_put_contents("/etc/cron.daily/nimbusec", "#!/bin/bash") === false)
		throw new Exception("file_put_contents: creating cron job failed");

	if(!chmod("/etc/cron.daily/nimbusec", 0755))
		throw new Exception("chmod: setting permission failed");		

	$logger->info("Cron job created");
	
	// ################################## 4.) Hooks + cPanel ##################################
	
	/*$themes = array("x3", "paper_lantern");
	foreach($themes as $theme){
		copyDir("/usr/local/nimbusec/nimbusec/cpanel_plugin/{$theme}/nimbusec", "/usr/local/cpanel/base/frontend/{$theme}/nimbusec");
	}*/
	
	$res = `/usr/local/nimbusec/nimbusec/hooks/install.sh`;
	$logger->debug("Hooks installation message: ". trim($res));
	
	if(strpos($res, "#") !== false)
		list($stat, $message) = explode('#', $res);
	else{
		$stat = 0;
		$message = $res;
	}
	
	if($stat)
	{	
		$logger->info(trim($message));

		// Install cpanel
		$res = `/usr/local/nimbusec/nimbusec/cpanel_plugin/install.sh`;
		$logger->debug("cPanel installation message: ". trim($res));
		if(strpos($res, "#") !== false)
			list($stat, $message) = explode('#', $res);
		else{
			$stat = 0;
			$message = $res;
		}
		
		if($stat)
		{
		 	$logger->info(trim($message));
		 	$logger->info("Nimbusec installed");
		 	$logger->info("Nimbusec installation ends...");
			$logger->close();
			return true;
			
		 }else
		 	throw new Exception("cPanel installation failed: {$message}");
	}else
		throw new Exception("Hook installation failed: {$message}");
	
	$logger->error("Nimbusec installation finished unsuccessfully without throwing an exception...");
	$logger->close();
	
	return false;

}
catch(CUrlException $exp)
{
	$logger->error("[CUrl SPECIFIC ERROR] in {$exp->getFile()}: {$exp->getMessage()} at line {$exp->getLine()}");
	$logger->info("Nimbusec installation aborted...");
	
	$logger->close();
	return false;
}
catch(NimbusecException $exp)
{
	$logger->error("[Nimbusec SPECIFIC ERROR] in {$exp->getFile()}: {$exp->getMessage()} at line {$exp->getLine()}");
	$logger->info("Nimbusec installation aborted...");
	
	$logger->close();
	return false;
}
catch(WHMException $exp)
{
	$logger->error("[WHM SPECIFIC ERROR] in {$exp->getFile()}: {$exp->getMessage()} at line {$exp->getLine()}");
	$logger->info("Nimbusec installation aborted...");
	
	$logger->close();
	return false;
}
catch(Exception $exp)
{
	$logger->error("[UNSPECIFIC ERROR] in {$exp->getFile()}: {$exp->getMessage()} at line {$exp->getLine()}");
	$logger->info("Nimbusec installation aborted...");
	
	$logger->close();
	return false;
}

function deleteDir($path)
{
	if (is_dir($path) === true){
		$files = array_diff(scandir($path), array('.', '..'));

		foreach ($files as $file)
			deleteDir(realpath($path) . '/' . $file);

		return rmdir($path);
	}
	else if (is_file($path) === true)
		return unlink($path);

	return false;
}
//
//function copyDir($from, $to)
//{	
//	if (is_dir($from) === true && is_dir($to) === true){
//		$files = array_diff(scandir($from), array('.', '..'));
//
//		foreach ($files as $file)
//			return copyDir(realpath($from) . '/' . $file, realpath($to) . '/' . $file);
//	}
//	else if (is_file($from) === true && is_file($from) === false){
//		return copy($from, $to);
//	}
//	else if(is_dir($to) === false){
//		mkdir($to, 0755);
//		return copyDir($from, $to);
//	}	
//	return false;
//}
?>
