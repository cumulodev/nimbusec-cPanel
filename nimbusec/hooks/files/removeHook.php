#!/usr/bin/php -q
<?php
require_once ('/usr/local/nimbusec/lib/WHMAPIClient.php');
require_once ('/usr/local/nimbusec/lib/Provision.php');
require_once ('/usr/local/nimbusec/lib/Logger.php');

// Read input from STDIN
$input = get_passed_data ();
list ( $result_status, $result_msg ) = removeUser ( $input );

// Write response to STDOUT
echo "$result_status $result_msg";
function removeUser($input = array()) {

	$logger = new Logger("/usr/local/nimbusec/logs", "remove.log", true);
	$data = $input ['data'];

	$userName = "";

	if(array_key_exists('user', $data))
		$userName = $data['user'];
	else
		$userName = $data['username'];

	$logger->info("Triggered removeuser hook");
	$logger->debug("Removing user {$userName}");
	$logger->info("Check bundle");

	try {

		// Get access data for WHM API
		$hash = file_get_contents ( "/root/.accesshash" );
		$host = gethostname ();
		$serverAddr = gethostbyname ( $host );

		$whmApi = new WHMAPIClient ( $hash, $serverAddr );

		$accRes = $whmApi->sendRequest('accountsummary', array("user" => $userName));
		$logger->info("Retrieve account information of user");

		$pkgRes = $whmApi->sendRequest('getpkginfo', array("pkg" => $accRes['data']['acct'][0]['plan']));
		$logger->info("Read infomation for user's package {$accRes['data']['acct'][0]['plan']}");

		$hasNimbusec = isset($pkgRes['data']['pkg']['nimbusec_bundles']);
		$logger->debug("User has nimbusec [has_nimbusec] 'json_encode({$hasNimbusec})'");

		list($key, $secret) = $whmApi->getNVData(array("NIMBUSEC_APIKEY", "NIMBUSEC_APISECRET"));

		if ($hasNimbusec) {

			$logger->info("User has nimbusec");
			$logger->info("Removing begins..");

			$provision = new Provision ( $key, $secret );
			$res = $provision->removeUser(array("user" => $userName, "contactemail" => $accRes['data']['acct'][0]['email']), $logger);

			if($res[0])
				$logger->info($res[1]);
			else
				$logger->error($res[1]);

			$logger->info("Removing ends..");
			$logger->close();
			return array(1, $res[1]);

		} else
		{
			$str = "User doesn't have nimbusec";
			$logger->info($str);
			$logger->close();

			return array (
				"1",
				$str
			);
		}

		$str = "Something unexpected happened in remove hook. Better check immediately";

		$logger->error($str);
		$logger->close();
		return array (
				"1",
				$str
		);
	}
	catch(CUrlException $exp)
	{
		$logger->error("[CUrl SPECIFIC ERROR] in {$exp->getFile()}: {$exp->getMessage()} at line {$exp->getLine()}");
		$logger->info("Processing data in remove hook aborted...");

		$logger->close();
		return array (
				"1",
				$exp->getMessage()
		);
	}
	catch(NimbusecException $exp)
	{
		$logger->error("[Nimbusec SPECIFIC ERROR] in {$exp->getFile()}: {$exp->getMessage()} at line {$exp->getLine()}");
		$logger->info("Processing data in remove hook aborted...");

		$logger->close();
		return array (
				"1",
				$exp->getMessage()
		);
	}
	catch(WHMException $exp)
	{
		$logger->error("[WHM SPECIFIC ERROR] in {$exp->getFile()}: {$exp->getMessage()} at line {$exp->getLine()}");
		$logger->info("Processing data in remove hook aborted...");

		$logger->close();
		return array (
				"1",
				$exp->getMessage()
		);
	}
	catch(Exception $exp)
	{
		$logger->error("[UNSPECIFIC ERROR] in {$exp->getFile()}: {$exp->getMessage()} at line {$exp->getLine()}");
		$logger->info("Processing data in remove hook aborted...");

		$logger->close();
		return array (
				"1",
				$exp->getMessage()
		);
	}
}
function get_passed_data() {
	$raw_data;
	$stdin_fh = fopen ( 'php://stdin', 'r' );
	if (is_resource ( $stdin_fh )) {
		stream_set_blocking ( $stdin_fh, 0 );
		while ( ($line = fgets ( $stdin_fh, 1024 )) !== false ) {
			$raw_data .= trim ( $line );
		}
		fclose ( $stdin_fh );
	}
	if ($raw_data) {
		$input_data = json_decode ( $raw_data, true );
	} else {
		$input_data = array (
				'context' => array (),
				'data' => array (),
				'hook' => array ()
		);
	}
	return $input_data;
}
?>
