<?php
require_once ('NimbusecAPI.php');
require_once ('Logger.php');


class Provision {
	
	private $nimbusecAPI;
	
	private $nimbusecAPIKey;
	private $nimbusecAPISecret;
	private $nimbusecAPIServer;
	
	private $domainID;
	private $userID;
	
	private $DEFAULT_NVPATH = "/home/<user>/.cpanel/nvdata/";
	private $DEFAULT_CRONPATH = "/etc/cron.daily/nimbusec";
	private $DEFAULT_AGENTCONFPATH = "<nvpath><user>agent.conf";
	private $DEFAULT_CRONSTRING = "su -c \"/opt/nimbusec/agent -config <confFile>\" <user>";

	function __construct($key, $secret, $server) {
		$this->nimbusecAPIKey = $key;
		$this->nimbusecAPISecret = $secret;
		$this->nimbusecAPIServer = $server;
		
		$this->nimbusecAPI = new NimbusecAPI ( $key, $secret, $server );
	}
	
	function updateBundle($email, $newBundle, $logger)
	{
		$msg = "";
		
		list ( $bundleName, $bundleId ) = explode ( '_', $newBundle );
		$response = $this->nimbusecAPI->findBundles("id=\"" . $bundleId . "\"");
		
		if (!empty ( $response ))
		{
			$logger->debug("Bundle {$newBundle} is valid");
			
			$userArr = array("login" => $email);
			if ($this->existUser ( $userArr )) {
				
				$logger->debug("User {$email} exists in Database and has ID {$this->userID}");
				
				$domainArr = array("bundle" => $bundleId);
				$domains = $this->nimbusecAPI->findDomainSet($this->userID);
				if(!empty($domains))
				{
					$logger->info("Updating domains");
					foreach($domains as $domain)
					{
						$logger->debug("Updating domain with ID {$domain}");
						$this->nimbusecAPI->updateDomain($domain, $domainArr);
					}
					return array(1, "Successfully updated user's domains with new bundle");
					
				}else 
					$msg = "User {$email} has no domain whose bundle can be updated";
			}else
				$msg = "The user '{$email}', whose bundles should have been updated doesn't exist in the database";	
		}else 
			$msg = "Bundle {$newBundle} doesn't exist in the database";
		
		$msg = "UPDATING BUNDLE FAILED: {$msg}";
		return array (0, $msg);
	}	
	
	function provisionUser($data, $logger)
	{
		$msg = "";
		$user = $data ['user'];
		$domain = $data ['domain'];
		$userMail = $data ['contactemail'];
		list ( $bundleName, $bundleId ) = explode ( '_', $data ['nimbusec_bundles'] );
		
		$deepScan = "http://{$domain}";
		
		$domainArr = array (
				"scheme" => "http",
				"name" => $domain,
				"deepScan" => $deepScan,
				"fastScans" => array (
						$deepScan 
				),
				"bundle" => $bundleId 
		);
		
		$signatureKey = md5 ( uniqid ( rand (), true ) );
		$userArr = array (
				"login" => $userMail,
				"mail" => $userMail,
				"role" => "user",
				"signatureKey" => $signatureKey 
		);
		if (! $this->existUser ( $userArr )) {
			$logger->debug("User {$userMail} doesn't exist in database");
			$this->registerUser ( $userArr );
			$logger->info("User registered");
			
			if (! $this->existDomain ( $domainArr ))
			{
				$logger->debug("Domain {$domain} doesn't exist in the database");
				$this->registerDomain ( $domainArr );
				$logger->info("Domain registered");
			}
			else
				$logger->debug("Domain {$domain} already exist in the database and has ID {$this->domainID}");
			
			$this->registerDomainSet ();
			$logger->debug("User and domain linked");
			
			$notificationArr = array (
					"domain" => $this->domainID,
					"transport" => "mail",
					"serverside" => 3, 
					"content" => 3, 
					"blacklist" => 3
			);
			
			$this->registerNotification($notificationArr);
			$logger->debug("Notification created");
			
			// Write name + signaturekey in nvdata stores for sso link
			$nvPath = str_replace("<user>", $user, $this->DEFAULT_NVPATH);
			if (! is_dir( $nvPath )) {
				mkdir ( $nvPath, 0700 );
				chown ( $nvPath, $user );
				chgrp ( $nvPath, $user );
			}
			file_put_contents ( "{$nvPath}NIMBUSEC_NAME", $userMail );
			file_put_contents ( "{$nvPath}NIMBUSEC_SECRET", $signatureKey );
			file_put_contents ( "{$nvPath}NIMBUSEC_SERVER", $this->nimbusecAPIServer );
			
			$logger->debug("Name, secret and server saved in nv data stores at {$nvPath}");
			
			// Create conf + cronjob
			$confFile = str_replace(array("<nvpath>", "<user>"), array($nvPath, $user), $this->DEFAULT_AGENTCONFPATH);
			$logger->debug("Conf file path {$confFile}");
			if (is_file("/opt/nimbusec/agent.conf")) {
				copy ( "/opt/nimbusec/agent.conf", $confFile );
				$jsonString = file_get_contents ( $confFile );
				$conf = json_decode ( $jsonString, true );
				
				$conf ['tmpfile'] = str_replace ( "<user>", $user, $conf ['tmpfile'] );
				$domainsJson = array (
						$domain => "/home/{$user}/www" 
				);
				$conf ['domains'] = $domainsJson;
				if (file_put_contents ( $confFile, json_encode ( $conf ) ) !== false) {
					
					$logger->info("Agent conf file customized");
					if (is_file ( $this->DEFAULT_CRONPATH )) {
						$cronString = "\n" . str_replace(array("<confFile>", "<user>"), array($confFile, $user), $this->DEFAULT_CRONSTRING);
						if (file_put_contents ( $this->DEFAULT_CRONPATH, $cronString, FILE_APPEND ) !== false) 
							return array(1, "Successfully provisioned user");
						else
							$msg = "Writing error occured while trying to update cron job";
					} else
						$msg = "Default cron job doesn't exist at $this->DEFAULT_CRONPATH";
				} else
					$msg = "Writing error occured while trying to update server agent conf file";
			} else
				$msg = "Default conf file doesn't exist at /opt/nimbusec/";
		} else
			$msg = "To be provisioned user '{$user}' with email '{$userMail}' already exists";
		
		
		$msg = "PROVISIONING USER FAILED: {$msg}";
		return array (0, $msg);
	}
	
	function removeUser($data, $logger)
	{
		$msg = "";
		
		$user = $data ['user'];
		$userMail = $data ['contactemail'];
		
		if($this->existUser(array("login" => $userMail)))
		{
			$logger->debug("User {$userMail} exists in the database and has ID {$this->userID}");
			$domains = $this->nimbusecAPI->findDomainSet($this->userID);
			if(!empty($domains))
			{
				$logger->info("Removing domains");
				foreach($domains as $domain)
				{
					$logger->debug("Removing domain {$domain}");
					$this->nimbusecAPI->deleteFromDomainSet($this->userID, $domain);
					$this->nimbusecAPI->deleteDomain($domain);
				}
			}else
				$logger->warning("No domains existing for user {$userMail} with ID {$this->userID} in database");
				
			$logger->info("Removing user");
			$this->nimbusecAPI->deleteUser($this->userID);
			
			// NVData
			$nvPath = str_replace("<user>", $user, $this->DEFAULT_NVPATH);
				
			if(is_file("{$nvPath}NIMBUSEC_NAME"))
				unlink("{$nvPath}NIMBUSEC_NAME");	
				
			if(is_file("{$nvPath}NIMBUSEC_SECRET"))
				unlink("{$nvPath}NIMBUSEC_SECRET");
				
			if(is_file("{$nvPath}NIMBUSEC_SERVER"))
				unlink("{$nvPath}NIMBUSEC_SERVER");	
			
			$logger->debug("Name, secret and server removed from nv data stores at {$nvPath}");
			
			$confFile = str_replace(array("<nvpath>", "<user>"), array($nvPath, $user), $this->DEFAULT_AGENTCONFPATH);
			if(is_file($confFile))
				unlink($confFile);
			
			$logger->debug("Removed agent conf file at path {$confFile}");
			
			// Cronjob
			$cronString = str_replace(array("<confFile>", "<user>"), array($confFile, $user), $this->DEFAULT_CRONSTRING);
			
			if (is_file ( $this->DEFAULT_CRONPATH )) {
				
				$logger->debug("Conf file exists at {$this->DEFAULT_CRONPATH}");
				$logger->debug("Removing cron string '{$cronString}'");
				$cron = file_get_contents($this->DEFAULT_CRONPATH);
				$cronlines = explode("\n", $cron);
				// Search for the particular cron line
				foreach($cronlines as $key => $cronline)
				{
					$logger->debug("Reading cron line '{$cronline}' to search for {$user}");
					if(strpos($cronline, $user) !== false)
					{
						$logger->debug("Remove cronline at $key {$cronlines[$key]}");
						unset($cronlines[$key]);
					}
				}
				// Update cron
				if (file_put_contents ( $this->DEFAULT_CRONPATH, implode("\n", $cronlines) ) !== false) 
						return array(1, "Successfully removed user");
				else
					$msg = "Writing error occured while trying to update cron job";
			} else
				$msg = "Default cron job doesn't exist at $this->DEFAULT_CRONPATH";	
		}else
			$msg = "No users existing for email {$userMail} in database";
		
		$msg = "REMOVING USER FAILED: {$msg}";
		return array (0, $msg);
	}
	
	private function existDomain($domain) {
		
		$response = $this->nimbusecAPI->findDomains("name=\"" . $domain['name'] . "\"");
			
		if (empty ( $response ))
			return false;
		else {
			// Save domainID for later
			$this->domainID = $response[0]['id'];
			return true;
		}
	}
	
	private function registerDomain($domain) {
		
		$response = $this->nimbusecAPI->createDomain ( $domain );
		
		// Save domainID for later
		$this->domainID = $response['id'];
	}
	
	private function existUser($user) {
	
		$response = $this->nimbusecAPI->findUsers("login=\"" . $user['login'] . "\"");
				
		if (empty ( $response ))
			return false;
		else {
			// Save domainID for later
			$this->userID = $response[0]['id'];
			return true;
		}
	}
	
	private function registerUser($user)
	{
		$response = $this->nimbusecAPI->createUser( $user );
		
		// Save domainID for later
		$this->userID = $response['id'];
	}
	
	private function registerNotification($notification)
	{
		// Packed in a seperate method to retain code's readability
		$response = $this->nimbusecAPI->createNotification($notification, $this->userID);
	}
	
	private function registerDomainSet()
	{
		if(!empty($this->domainID) && !empty($this->userID))
			$this->nimbusecAPI->createDomainSet($this->userID, $this->domainID);
		else  
		{
			$exc = "A NimbusecException has been thrown:  
					The Id of the domain or user hasn't been set";
			throw new NimbusecException($exc);
		}
	}
}

?>