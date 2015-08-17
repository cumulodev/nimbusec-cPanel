<?php
require_once("/usr/local/nimbusec/lib/WHMAPIClient.php");
require_once("/usr/local/nimbusec/lib/NimbusecAPI.php");
require_once("/usr/local/nimbusec/lib/PackageExtensions.php");
require_once("/usr/local/nimbusec/lib/Logger.php");
require_once("/usr/local/nimbusec/lib/Provision.php");

// Contains all package names + an array with all extentions names
$pkgInfos = array();

// Contains the login name (email) + username of all users with nimbusec in their packages
$nimbuAccts = array();

$responseArray = array();
$responseArray['status'] = 0;
$responseArray['content'] = array();

try{
	$logger = new Logger("/usr/local/nimbusec/logs", "uninstall_error.log", true);

	$logger->progress("Nimbusec uninstallation begins...");

	// Get access data for WHM API - do not delete
	$hash = file_get_contents( "/root/.accesshash");
	$host = gethostname();
	$serverAddr = gethostbyname($host);

	$whmApi = new WHMAPIClient( $hash, $serverAddr );
	list($key, $secret, $agentKey) = $whmApi->getNVData(array("NIMBUSEC_APIKEY", "NIMBUSEC_APISECRET", "NIMBUSEC_SERVERAGENTKEY"));

	$nimbusecAPI = new NimbusecAPI($key, $secret);

	// To remind, every error will be handled in the particular method of each PHP (!) class you use
	// and catched in this class
	// So you can log after every command without needing to check whether it worked properly or not
	// because if it's not, it will be catched by the 'catch' - clause which will log it in addition

	// ################################## 1.) Remove all users and domains from database ##################################

	$logger->progress("Removing all user and domains from database...");

	// Retrieve names of all packages containing nimbusec extension into an array
	$pkgResult = $whmApi->sendRequest('listpkgs');

	$logger->info("Packages retrieved");
	$logger->debug("Resultstatus WHM API listpkgs: {$pkgResult['metadata']['result']}");

	// Loop through every package to get name + list of package extensions
	foreach ($pkgResult['data']['pkg'] as $pkg){

		if(!empty($pkg['_PACKAGE_EXTENSIONS'])){

			// Space-seperated list
			$extensions = explode(' ', $pkg['_PACKAGE_EXTENSIONS']);

			// Look for the key word nimbusec..
			// If the package has nimbusec, remove it from the list and add it to the array
			if(($keyPos = array_search("nimbusec", $extensions, true)) !== false){
				unset($extensions[$keyPos]);
				array_push($pkgInfos, array("name" => $pkg['name'], "extensions" => implode(' ', $extensions)));
			}
		}
		else
			$logger->debug("Zero package extensions installed for package: {$pkg['name']}");
	}

	$logger->info("Nimbusec specific packages retrieved");

	if(empty($pkgInfos))
		$logger->warning("No packages existing which have nimbusec included");

	// Filter all users by their hosting plan matching those in the array
	$acctResult = $whmApi->sendRequest('listaccts');

	$logger->info("Accounts retrieved");
	$logger->debug("Resultstatus WHM API listaccts: {$acctResult['metadata']['result']}");

	$logger->info("Filter account if their package contains the nimbusec package extension");
	foreach($acctResult['data']['acct'] as $acct){

		$logger->debug("Reading package: {$acct['plan']}, name: {$acct['user']} and email: {$acct['email']}");
		foreach($pkgInfos as $pkg){

			if(in_array($acct['plan'], $pkg, true)) // Find all users except nimbusec
				array_push($nimbuAccts, array("email" => $acct['email'], "user" => $acct['user']));
		}
	}

	$logger->info("Nimbusec specific accounts retrieved");

	if(empty($nimbuAccts))
		$logger->warning("No users existing with a package which has nimbusec included");

	// Removing users
	$provision = new Provision($key, $secret);

	foreach ($nimbuAccts as $acct)
	{
		$logger->debug("Looping through user {$acct['email']} to read & delete the database entries");
		$res = $provision->removeUser(array("user" => $acct['user'], "contactemail" => $acct['email']), $logger);

		if($res[0])
			$logger->debug("User {$acct['email']}: {$res[1]}");
		else
			$logger->warning("Failed to remove {$acct['email']}. Error message : {$res[1]}");
	}
	$logger->progress("Removed all user and domains from database");
	array_push($responseArray['content'], "Removed all user and domains from database");

	// ################################## 2.) Remove all parts of nimbusec ##################################

	$logger->progress("Removing all parts of nimbusec from the system...");
	$logger->info("Removing WHM Hooks + cPanel plugin from system");

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

		// --> 2.1) cPanel plugin
		$res = `/usr/local/nimbusec/nimbusec/cpanel_plugin/uninstall.sh`;
		$logger->debug("cPanel uninstallation message: " . trim($res));
		if(strpos($res, "#") !== false)
			list($stat, $message) = explode('#', $res);
		else{
			$stat = 0;
			$message = $res;
		}
		array_push($responseArray['content'], $message);

		if($stat)
		{
			$logger->info(trim($message));

			// --> 2.2) Whm / cPanel hooks
			$res = `/usr/local/nimbusec/nimbusec/hooks/uninstall.sh`;
			$logger->debug("Hooks uninstallation message: " . trim($res));
			if(strpos($res, "#") !== false)
				list($stat, $message) = explode('#', $res);
			else{
				$stat = 0;
				$message = $res;
			}
			array_push($responseArray['content'], $message);

			if($stat){
				$logger->info(trim($message));
			}
			else
				$logger->warning("WHM Hooks uninstallation failed {$message}");
		}else
			$logger->warning("cPanel uninstallation failed {$message}");

		$logger->info("Removed WHM Hooks + cPanel plugin from system");
		array_push($responseArray['content'], "Removed WHM Hooks + cPanel plugin from system");
	}else{

		$instructions = "You have disabled the function \"$func\" in your PHP configuration.\n
		Therefore, the uninstallation cannot be finished.\n
		Take the following steps to complete the uninstallation:\n
			1.) Run \"$ /usr/local/nimbusec/nimbusec/hooks/uninstall.sh\" to ensure that necessary internal script hooks are being removed from WHM.\n
			2.) Run \"$ /usr/local/nimbusec/nimbusec/cpanel_plugin/uninstall.sh\" to uninstall the Nimbusec plugin on cPanel side.\n
			3.) It is important that you run these scripts before removing the general WHM plugin. Otherwise the uninstallation script will not be accessible anymore.
		Alternatively, you may want to re-enable the affected function in your configuration and try it again.";
		$logger->error("Failed to remove the cPanel plugin and internal hook scripts because there are blocked by the system");
		$logger->error($instructions);

		throw new Exception("Failed to remove the cPanel plugin and internal hook scripts because there are blocked by the system");
	}

	// --> 2.3) Package extension

	$logger->progress("Removing all package extensions");

	// Remove the nimbusec package extension from the _PACKAGE_EXTENSION field
	// Use the array being created earlier
	foreach($pkgInfos as $pkg)
		$res = $whmApi->sendRequest('editpkg', array("name" => $pkg['name'], "_PACKAGE_EXTENSIONS" => $pkg['extensions']));

	$logger->info("Removed extensions from existing packages");

	// Check whether the extension files are existing, otherwise unlink will throw an error
	if(is_file(PackageExtensions::$extensionSettingPath) && is_file(PackageExtensions::$extensionTemplatePath)){
		unlink(PackageExtensions::$extensionSettingPath); unlink(PackageExtensions::$extensionTemplatePath);
	}
	else
		$logger->warning("Extension files paths doesn't exist, hence couldn't be deleted");

	$logger->info("Removed extensions files from system");
	array_push($responseArray['content'], "Removed extensions files from system");

	// --> 2.4) Server agent

	$logger->progress("Removing server agent");
	$token = $nimbusecAPI->findAgentToken("key=\"{$agentKey}\"");

	// Delete token
	if(!empty($token))
	{
		$logger->debug("Found agent token");
		$nimbusecAPI->deleteAgentToken($token[0]['id']);
		$logger->debug("Removed agent token with the ID {$token[0]['id']}");
	}
	else
		$logger->warning("No agent token existing for key {$agentKey} in database");

	// Delete server agent
	if(is_dir("/opt/nimbusec")){
		deleteDir("/opt/nimbusec");
		$logger->info("Deleted server agent files");
	}
	else
		$logger->warning("Server agent path doesn't exist, hence couldn't be deleted");

	$logger->info("SECOND PART completed: Removing all parts of nimbusec from the system");
	array_push($responseArray['content'], "Removed all parts of nimbusec from the system");

	// ################################## 3.) Remove rest files / nvdata ##################################

	$logger->info("THIRD PART: Removing other nimbusec files");
	// Delete later hashes temp file in /home/<user>/tmp/hashes-<domain>.txt

	// Cronjob
	if(is_file("/etc/cron.daily/nimbusec"))
		unlink("/etc/cron.daily/nimbusec");
	else
		$logger->warning("Cron job path doesn't exist, hence couldn't be deleted");

	$logger->info("Removing nimbusec env files");

	// WHM NVData
	$whmArr = array (
			array ("NIMBUSEC_APIKEY", null),
			array ("NIMBUSEC_APISECRET", null),
			array ("NIMBUSEC_SERVERAGENTKEY", null),
			array ("NIMBUSEC_SERVERAGENTSECRET", null),
			array ("NIMBUSEC_INSTALLED", null)
	);

	$whmApi->setNVData($whmArr);

	$logger->info("THIRD PART completed: Removed other nimbusec files\n");
	array_push($responseArray['content'], "Removed other nimbusec files");

	$logger->info("Nimbusec uninstalled");
	$logger->info("Nimbusec uninstallation ends...");

	$logger->close();
	$responseArray['status'] = 1;
 	return $responseArray;

}
catch(CUrlException $exp)
{
	$logger->error("[CURL SPECIFIC ERROR] in {$exp->getFile()}: {$exp->getMessage()} at line {$exp->getLine()}");
	$logger->info("Nimbusec uninstallation aborted...");
	$logger->close();

	array_push($responseArray['content'], "Nimbusec uninstallation aborted...");
	return $responseArray;
}
catch(NimbusecException $exp)
{
	$logger->error("[Nimbusec SPECIFIC ERROR] in {$exp->getFile()}: {$exp->getMessage()} at line {$exp->getLine()}");
	$logger->info("Nimbusec uninstallation aborted...");
	$logger->close();

	array_push($responseArray['content'], "Nimbusec uninstallation aborted...");
	return $responseArray;
}
catch(WHMException $exp)
{
	$logger->error("[WHM SPECIFIC ERROR] in {$exp->getFile()}: {$exp->getMessage()} at line {$exp->getLine()}");
	$logger->info("Nimbusec uninstallation aborted...");
	$logger->close();

	array_push($responseArray['content'], "Nimbusec uninstallation aborted...");
	return $responseArray;
}
catch(Exception $exp)
{
	$logger->error("[UNSPECIFIC ERROR] in {$exp->getFile()}: {$exp->getMessage()} at line {$exp->getLine()}");
	$logger->info("Nimbusec uninstallation aborted...");
	$logger->close();

	array_push($responseArray['content'], "Nimbusec uninstallation aborted...");
	return $responseArray;
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

?>
