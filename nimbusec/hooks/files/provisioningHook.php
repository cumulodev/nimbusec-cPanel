#!/usr/bin/php -q
<?php
require_once ('/usr/local/nimbusec/lib/WHMAPIClient.php');
require_once ('/usr/local/nimbusec/lib/Provision.php');
require_once ('/usr/local/nimbusec/lib/Logger.php');

// Read input from STDIN
$input = get_passed_data ();
list ( $result_status, $result_msg ) = provisioningUser ( $input );

// Write response to STDOUT
echo "$result_status $result_msg";
function provisioningUser($input = array()) {

	$logger = new Logger("/usr/local/nimbusec/logs", "provisioning.log", true);
	$data = $input ['data'];

	$logger->info("Triggered provisioning hook");
	$logger->info("Check bundle");

	try {
		// isset is much faster than array_key_exists
		// http://stackoverflow.com/questions/2473989/list-of-big-o-for-php-functions
		// At the same time you can save checking whether with empty(), isset() does the same anyway
		// https://www.virendrachandak.com/techtalk/php-isset-vs-empty-vs-is_null/
		if(isset($data['contactemail']))
		{
			if (isset($data['nimbusec_bundles'])) {

				$logger->info("User has nimbusec");

				// Get access data for WHM API
				$hash = file_get_contents ( "/root/.accesshash" );
				$host = gethostname ();
				$serverAddr = gethostbyname ( $host );

				$whmApi = new WHMAPIClient ( $hash, $serverAddr );

				list($key, $secret) = $whmApi->getNVData(array("NIMBUSEC_APIKEY", "NIMBUSEC_APISECRET"));

				$logger->debug("Read relevant data for [user] '{$data ['user']}': [domain] => '{$data ['domain']}'
				, [contactemail] => '{$data ['contactemail']}' and [nimbusec_bundles] => '{$data ['nimbusec_bundles']}'");

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

			} else {
				$str = "User doesn't have nimbusec";
				$logger->info($str);
				$logger->close();

				return array (
						"1",
						$str
				);
			}
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

		$str = "Something unexpected happened in provision hook. Better check immediately";

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
		$logger->info("Processing data in provision hook aborted...");

		$logger->close();
		return array (
				"1",
				$exp->getMessage()
		);
	}
	catch(NimbusecException $exp)
	{
		$logger->error("[Nimbusec SPECIFIC ERROR] in {$exp->getFile()}: {$exp->getMessage()} at line {$exp->getLine()}");
		$logger->info("Processing data in provision hook aborted...");

		$logger->close();
		return array (
				"1",
				$exp->getMessage()
		);
	}
	catch(WHMException $exp)
	{
		$logger->error("[WHM SPECIFIC ERROR] in {$exp->getFile()}: {$exp->getMessage()} at line {$exp->getLine()}");
		$logger->info("Processing data in provision hook aborted...");

		$logger->close();
		return array (
				"1",
				$exp->getMessage()
		);
	}
	catch(Exception $exp)
	{
		$logger->error("[UNSPECIFIC ERROR] in {$exp->getFile()}: {$exp->getMessage()} at line {$exp->getLine()}");
		$logger->info("Processing data in provision hook aborted...");

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
