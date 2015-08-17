#!/usr/bin/php -q
<?php
require_once ('/usr/local/nimbusec/lib/WHMAPIClient.php');
require_once ('/usr/local/nimbusec/lib/Provision.php');
require_once ('/usr/local/nimbusec/lib/Logger.php');

// Read input from STDIN
$input = get_passed_data ();
list ( $result_status, $result_msg ) = checkPackage ( $input );

// Write response to STDOUT
echo "$result_status $result_msg";

function checkPackage($input = array())
{
	$logger = new Logger("/usr/local/nimbusec/logs", "changePackage.log", true);
	$data = $input ['data'];

	$logger->info("Triggered change_package hook");

	try{
		$logger->info("Check bundles");

		// Get access data for WHM API
		$hash = file_get_contents ( "/root/.accesshash" );
		$host = gethostname ();
		$serverAddr = gethostbyname ( $host );

		$whmApi = new WHMAPIClient ( $hash, $serverAddr );

		$oldPkgName = $data['cur_pkg'];
		$newPkgName = $data['new_pkg'];

		$userName = $data['user'];
		$logger->debug("Get input data [cur/old_pkg] '{$oldPkgName}' [new_pkg] '{$newPkgName}' and [user] '{$userName}'");

		$oldPkgRes = $whmApi->sendRequest('getpkginfo', array("pkg" => $oldPkgName));
		$newPkgRes = $whmApi->sendRequest('getpkginfo', array("pkg" => $newPkgName));
		$logger->info("Read infomation for both packages");

		// Isset is much faster than array_key_exists
		$old_hasNimbusec = isset($oldPkgRes['data']['pkg']['nimbusec_bundles']);
		$new_hasNimbusec = isset($newPkgRes['data']['pkg']['nimbusec_bundles']);
		$logger->debug("Old package [has_nimbusec] 'json_encode({$old_hasNimbusec})', new package [has_nimbusec] 'json_encode({$new_hasNimbusec})'");

		list($key, $secret) = $whmApi->getNVData(array("NIMBUSEC_APIKEY", "NIMBUSEC_APISECRET"));

		if(!$old_hasNimbusec && $new_hasNimbusec)
		{
			$logger->info("Include nimbusec and provision [user] '{$userName}'");

			$accRes = $whmApi->sendRequest('accountsummary', array("user" => $userName));
			$logger->info("Retrieve account information of user");

			$data = array("user" => $userName, "domain" => $accRes['data']['acct'][0]['domain'], "contactemail" => $accRes['data']['acct'][0]['email'],
					"nimbusec_bundles" => $newPkgRes['data']['pkg']['nimbusec_bundles']);

			if(isset($data['contactemail']))
			{
				$logger->debug("Read relevant data for [user] '{$userName}': [domain] => '{$accRes['data']['acct'][0]['domain']}'
				, [contactemail] => '{$accRes['data']['acct'][0]['email']}' and [nimbusec_bundles] => '{$newPkgRes['data']['pkg']['nimbusec_bundles']}'");

				$logger->info("Provisioning begins..");

				$provision = new Provision ( $key, $secret );
				$res = $provision->provisionUser($data, $logger);

				if($res[0])
					$logger->info($res[1]);
				else
					$logger->error($res[1]);

				$logger->info("Provisioning ends..");
				$logger->close();
				return array(1, $res[1]);

			}else
			{
				$str = "No email specified. Therefore user can't be provisioned with nimbusec";
				$logger->info($str);
				$logger->close();

				return array (
						"1",
						$str
				);
			}
		}
		else if($old_hasNimbusec && $new_hasNimbusec)
		{
			$logger->info("Checking package bundles...");
			$oldBundle = $oldPkgRes['data']['pkg']['nimbusec_bundles'];
			$newBundle = $newPkgRes['data']['pkg']['nimbusec_bundles'];

			// Check / update bundles
			if($oldBundle == $newBundle)
			{
				$logger->info("The old package's bundle and the new package's bundle is the same");
				$logger->debug("[old_bundle] '{$oldBundle}' == [new_bundle] '{$newBundle}'");

				$logger->close();
				return array (
						"1",
						"The old package's bundle and the new package's bundle is the same"
				);
			}
			else
			{
				$logger->info("Updating bundle begins..");

				$accRes = $whmApi->sendRequest('accountsummary', array("user" => $userName));
				$logger->info("Retrieve account information of user");

				$email = $accRes['data']['acct'][0]['email'];

				$logger->debug("Updating {$oldPkgName} with nimbusec bundle '{$oldBundle}' to {$newPkgName} with nimbusec bundle '{$newBundle}' for user {$email}");

				$provision = new Provision ( $key, $secret );
				$res = $provision->updateBundle($email, $newBundle, $logger);

				if($res[0])
					$logger->info($res[1]);
				else
					$logger->error($res[1]);

				$logger->info("Updating bundle ends..");
				$logger->close();
				return array(1, $res[1]);
			}
		}
		else if($old_hasNimbusec && !$new_hasNimbusec)
		{
			$logger->info("Removing account begins..");

			$accRes = $whmApi->sendRequest('accountsummary', array("user" => $userName));
			$logger->info("Retrieve account information of user");

			$email = $accRes['data']['acct'][0]['email'];
			$logger->debug("Removing user {$userName} with email {$email} from database and system");

			$provision = new Provision ( $key, $secret );
			$res = $provision->removeUser(array("user" => $userName, "contactemail" => $email), $logger);

			if($res[0])
				$logger->info($res[1]);
			else
				$logger->error($res[1]);

			$logger->info("Removing user ends..");
			$logger->close();
			return array(1, $res[1]);
		}
		else if(!$old_hasNimbusec && !$new_hasNimbusec)
		{
			$str = "Neither the old package nor the new package have nimbusec included.";
			$logger->info($str);
			$logger->close();

			return array (
				"1",
				$str
			);
		}

		$str = "Something unexpected happened in change_package hook. Better check immediately";

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
		$logger->info("Processing data in change_package hook aborted...");

		$logger->close();
		return array (
				"1",
				$exp->getMessage()
		);
	}
	catch(NimbusecException $exp)
	{
		$logger->error("[Nimbusec SPECIFIC ERROR] in {$exp->getFile()}: {$exp->getMessage()} at line {$exp->getLine()}");
		$logger->info("Processing data in change_package hook aborted...");

		$logger->close();
		return array (
				"1",
				$exp->getMessage()
		);
	}
	catch(WHMException $exp)
	{
		$logger->error("[WHM SPECIFIC ERROR] in {$exp->getFile()}: {$exp->getMessage()} at line {$exp->getLine()}");
		$logger->info("Processing data in change_package hook aborted...");

		$logger->close();
		return array (
				"1",
				$exp->getMessage()
		);
	}
	catch(Exception $exp)
	{
		$logger->error("[UNSPECIFIC ERROR] in {$exp->getFile()}: {$exp->getMessage()} at line {$exp->getLine()}");
		$logger->info("Processing data in change_package hook aborted...");

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
