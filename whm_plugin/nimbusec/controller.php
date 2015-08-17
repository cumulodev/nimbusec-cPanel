<?php
/**
* Not yet finished REST API for cPanel nimbsusec plugin
* A better solution would be to uses standardized libraries (e.g ZEND)
*/
require_once ('/usr/local/nimbusec/lib/WHMAPIClient.php');
require_once ('/usr/local/nimbusec/lib/NimbusecAPI.php');
require_once ('/usr/local/nimbusec/lib/PackageExtensions.php');

// ******************************************** Method definitions ******************************************** //

	/**
	* --- Verify string ---
	* Verifies the given input on malicious content.. just in case ;)
	*/
	function verifyString($input){

		// Precautionally
		if(!isset($input))
			return false;

		// Injection
		if(!is_string($input) || !ctype_alnum($input))
			return false;

		return true;
	}

	/**
	* --- Is nimbusec installed ---
	* Verifies whether the nimbusec plugin is already installed by reading out the specific flag though the WHM API
	*/
	function isNimbusecInstalled(){

		$hash = file_get_contents ( "/root/.accesshash" );
		$host = gethostname();
		$serverAddr = gethostbyname($host);

		// Get access data for WHM API
		$whmApi = new WHMAPIClient( $hash, $serverAddr );

		list($installed) = $whmApi->getNVData(array("NIMBUSEC_INSTALLED"));

		// No ctype validaiton necessary
		if($installed === "1")
			return true;

		return false;
	}

	/**
	* --- Update bundles ---
	* Updates the hosters current amount of bundles the overriding the existing list with
	* the bundles returned by the nimbusec API
	*/
	function updateBundles(){

		// -- Retrieves crednetials --
		$credentials = getCredentials();

		$packageExt = new PackageExtensions ( $credentials['key'], $credentials['secret'] );
		$packageExt->updatePackageExtensions ();
	}

	/**
	* --- Get credentials ---
	* Retrieves the hosters nimbusec credentials by making a request to the WHM API
	*/
	function getCredentials(){

		$hash = file_get_contents ( "/root/.accesshash" );
		$host = gethostname();
		$serverAddr = gethostbyname($host);

		// Get access data for WHM API
		$whmApi = new WHMAPIClient( $hash, $serverAddr );

		list($key, $secret) = $whmApi->getNVData(array("NIMBUSEC_APIKEY", "NIMBUSEC_APISECRET"));
		return array("key" => $key, "secret" => $secret);
	}

	/**
	* --- Verify credentials ---
	* Verifies the gives credentials by making a dummy request to the nimbusec API
	*/
	function verifyCredentials($apiKey, $apiSecret){

		try{
			$api = new NimbusecAPI($apiKey, $apiSecret);
			$bundles = $api->findBundles();
			return true;
		}catch(Exception $exp){
			return false;
		}
	}

	/**
	* --- Retrieve users ---
	* Retrieves all users along with their corresponding packages as well as the assigned nimbusec bundle
	* Executes two request to the WHM API
	*/
	function retrieveUsers(){

		$hash = file_get_contents ( "/root/.accesshash" );
		$host = gethostname();
		$serverAddr = gethostbyname($host);

		// Contains all packages + users
		$packages = array();

		// Get access data for WHM API
		$whmApi = new WHMAPIClient( $hash, $serverAddr );

		// Retrieve all packages
		$pkgResult = $whmApi->sendRequest('listpkgs');

		// Loop through every package to get the name of the package
		foreach ($pkgResult['data']['pkg'] as $pkg){

			if(!empty($pkg['_PACKAGE_EXTENSIONS'])){
				if(strpos($pkg['_PACKAGE_EXTENSIONS'], "nimbusec") !== false){

					$bundle = explode('_', $pkg['nimbusec_bundles']);

					array_push($packages,
						array(
							"packageName" => $pkg['name'],
							"bundleName" => $bundle[0],
							"bundleID" => $bundle[1],
							"users" => array()
						));
				}
			}
		}

		if(!empty($packages)){

			// Retrieve all users
			$acctResult = $whmApi->sendRequest('listaccts');

			foreach($acctResult['data']['acct'] as $acct){
				foreach($packages as $key => $pkg){
					// If the user's package has the same package name
					if(in_array($acct['plan'], $pkg, true))
						array_push($packages[$key]['users'], array('email' => $acct['email'], 'domain' => $acct['domain'], 'name' => $acct['user']));
				}
			}

			return $packages;

		}else
			return "No package with nimbusec included could be found on the system.";
	}

	/**
	* --- Returns response ---
	* Maintains interaction between client & server by sending reponses back to the client
	* Sends data back as JSON
	*/
	function returnResponse($content, $status = 0){

		// -- PHP does not support skipping of parameter --
		$resp = array('status' => $status, 'content' => $content);

		// -- Simulate function overloading --
		if(gettype($content) == "array" && isset($content['status']) && isset($content['content']))
			$resp = $content;

		// -- Send back as JSON --
		header('Content-type: application/json');
		echo json_encode($resp);
	}

// ******************************************** Script beginning ******************************************** //

	if($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {

		try{

			/**
			* --- Controller methods ---
			*/
			if (isset ($_POST ['action'])) {

				/**
				* --- Installation ---
				* Request to install the nimbusec plugin
				*/
				if($_POST ['action'] === "installation"){

					// -- Continue only if credentials were passed ---
					if(isset($_POST ['apiKey'], $_POST ['apiSecret'])){

						// -- Declare response array --
						$resp = array('status' => 0);

						$apiKey = htmlentities($_POST ['apiKey'], ENT_QUOTES);
						$apiSecret = htmlentities($_POST ['apiSecret'], ENT_QUOTES);
						// -- If $_POST ['apiServer'] then validate for url pattern --

						if (verifyString($apiKey) && verifyString($apiSecret)) {
							if(!isNimbusecInstalled()){
								if(verifyCredentials($apiKey, $apiSecret)){

									// -- Get access data for WHM API --
									$hash = file_get_contents( "/root/.accesshash");
									$host = gethostname();
									$serverAddr = gethostbyname($host);

									$whmApi = new WHMAPIClient( $hash, $serverAddr );

									// -- Install Nimbusec (trigger installation file) --
									$res = require_once ("/usr/local/nimbusec/nimbusec/install.php");

									if ($res['status']){

										// Set installation flag
										$whmApi->setNVData(array(array("NIMBUSEC_INSTALLED", "1")));

										// Set credentials
										$whmApi->setNVData(array(
												array ("NIMBUSEC_APIKEY", $apiKey),
												array ("NIMBUSEC_APISECRET", $apiSecret)
										));

										array_push($res['content'], "The installation of the nimbusec cPanel / WHM has been finished successfully.");
									}else
										array_push($res['content'], "The installation of the nimbusec cPanel / WHM plugin has been aborted suddenly. It is advised to review the nimbusec logs files to find possible causes.");

									returnResponse($res);

								}else
									returnResponse("Please enter valid credentials to continue with the installation.");
							}else
								returnResponse("The nimbusec cPanel / WHM plugin is already installed. An installation can't be done twice.");
						}else
							returnResponse("Invalid data was passed.");
					}
				}
				/**
				* --- Update ---
				* Request to update the current bundles and possibly add new ones
				*/
				else if($_POST ['action'] === "update"){

					updateBundles();
					returnResponse("Your existing nimbusec bundles has been updated successfully.", 1);
				}
				/**
				* --- Initialize ---
				* Request for all credentials to initialize the page
				*/
				else if($_POST['action'] === "initialize"){

					if(isNimbusecInstalled()){
						returnResponse(getCredentials(), 1);
					}else
						returnResponse("The nimbusec cPanel / WHM plugin is currently not installed on your system.");
				}
				/**
				* --- Retrieve table data ---
				* Request for reading out all existing bundles
				*/
				else if($_POST['action'] === "retrieveTableData"){

					$bundles = PackageExtensions::getBundles();

					if(is_array($bundles) && count($bundles) > 0)
						returnResponse($bundles, 1);
					else
						returnResponse("No nimbusec bundles could be found on your system.");
				}
				/**
				* --- Uninstallation ---
				* Request for the uninstallation of the nimbusec plugin
				*/
				else if($_POST['action'] === "uninstallation"){

					// -- Uninstall Nimbusec (execute installation file) --
					$res = require_once ("/usr/local/nimbusec/nimbusec/uninstall.php");

					if ($res['status'])
						array_push($res['content'], "The uninstallation of the nimbusec cPanel / WHM plugin has been finished successfully.");
					else
						array_push($res['content'], "The uninstallation of the nimbusec cPanel / WHM plugin has been aborted suddenly. It is advised to review the nimbusec logs files to find the possible cause.");

					returnResponse($res);
				}
				/**
				* --- Users ---
				* Request for all provisioned users along with their corresponsing packages
				*/
				else if($_POST['action'] === "retrieveUsers"){

					$packages = retrieveUsers();

					if(gettype($packages) == "array")
						returnResponse($packages, 1);
					else
						returnResponse($packages);

				}
				/**
				* --- Default section ---
				* When the action param didn't match the existing functions
				*/
				else
					returnResponse("Unknown module called");
			}

		}catch(Exception $exp){

			$res = "[UNEXPECTED SPECIFIC ERROR] in {$exp->getFile()}: {$exp->getMessage()} at line {$exp->getLine()}";
			returnResponse($res);
		}
	}
?>