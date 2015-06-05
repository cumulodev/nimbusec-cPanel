<?php
require_once ('/usr/local/nimbusec/lib/WHMAPI.php');
require_once ('/usr/local/nimbusec/lib/PackageExtensions.php');

$cptoken = $_ENV ['cp_security_token'];

$apiKeyErr = "";
$apiSecretErr = "";
$apiServerErr = "";

$info = "";
$error = "";

$hash = file_get_contents ( "/root/.accesshash" );
$serverAddr = $_SERVER['SERVER_ADDR'];

try {
	
	// Get access data for WHM API
	$whmApi = new WHMAPI( $hash, $serverAddr );
	
	if (isset ( $_POST ['install'] )) {
		
		$apiKey = $_POST ['api_key'];
		$apiSecret = $_POST ['api_secret'];
		$apiServer = $_POST ['api_server'];
		
		if (! empty ( $apiKey ) && ! empty ( $apiSecret ) && ! empty ( $apiServer )) {
			
			if (filter_var ( $_POST ['api_server'], FILTER_VALIDATE_URL ) !== FALSE)
			{
				$whmApi->setNVData(array(
						array (
								"NIMBUSEC_APIKEY",
								trim ( $apiKey )
						),
						array (
								"NIMBUSEC_APISECRET",
								trim ( $apiSecret )
						),
						array (
								"NIMBUSEC_APISERVER",
								trim ( $apiServer ) 
						)
					)
				);
				
				list($installed) = $whmApi->getNVData(array("NIMBUSEC_INSTALLED"));
				
				if(empty($installed) && $installed != 1)
				{
					// Set installation flag
					$whmApi->setNVData(array(array("NIMBUSEC_INSTALLED", "1")));
					
					// Install Nimbusec (trigger installation file)
					$res = require_once ("/usr/local/nimbusec/nimbusec/install.php");			
					if ($res)
						$info = "Nimbusec Security Monitor installation complete.";
					else
						$error = "Nimbusec Security Monitor installation failed. Check log files.";
				}
				else
					$error = "Nimbusec already installed. A installation can't be executed twice.";
			}else
				$apiServerErr = "Not a valid Url";
		}else
		{
			if (empty ( $apiKey ))
				$apiKeyErr = "Key is required";
			
			if (empty ( $apiSecret ))
				$apiSecretErr = "Secret is required";
			
			if (empty ( $apiServer ))
				$apiServerErr = "Server is required";
		}
		
	} else if (isset ( $_POST ['update'] )) {
		
		list($installed) = $whmApi->getNVData(array("NIMBUSEC_INSTALLED"));
		
		if(!empty($installed) && $installed == 1)
		{
			list($key, $secret, $server) = $whmApi->getNVData(array("NIMBUSEC_APIKEY", "NIMBUSEC_APISECRET", "NIMBUSEC_APISERVER"));
			
			$packageExt = new PackageExtensions ( $key, $secret, $server);
			$packageExt->updatePackageExtensions ();
			
			$info = "Package extensions updated";
		}
		else
			$error = "Nimbusec not installed yet. Therefore, bundles can't be updated.";
		
	} else if (isset ( $_POST ['uninstall'] )) {
		
		list($installed) = $whmApi->getNVData(array("NIMBUSEC_INSTALLED"));
		
		if(!empty($installed) && $installed == 1)
		{
			// Uninstall Nimbusec (trigger uninstallation file)
			$res = require_once ("/usr/local/nimbusec/nimbusec/uninstall.php");	
			if ($res)
				$info = "Nimbusec Security Monitor uninstallation complete.";
			else
				$error = "Nimbusec Security Monitor uninstallation failed. Check log files.";
		}
		else
			$error = "Nimbusec not installed yet. Therefore, a uninstallation can't be executed.";
	}
}
catch(Exception $exp)
{
	$error = $exp->getMessage();
}
?>
<html>
<head>
<title>Nimbusec - WHM Plugin</title>
<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel='stylesheet' type='text/css' href='/combined_optimized.css' />
<link rel='stylesheet' type='text/css'
	href='/themes/x/style_optimized.css' />
<link rel='stylesheet' type='text/css' href='static/custom.css' />
<script type='text/javascript'
	src='/yui-gen/utilities_container/utilities_container.js'></script>
<script type='text/javascript' src='/cjt/cpanel-all-min.js'></script>
</head>
<style>
.error {
	color: #FF0000;
}
</style>
<body>
	<div id="masterContainer">
		<div id="navigation">
			<div id="breadcrumbsContainer">
				<ul id="breadcrumbs_list" class="breadcrumbs">
					<li><a href="<?php echo $cptoken; ?>/scripts/command?PFILE=main"><img
							border="0" alt="Home" src="/images/home.png"><span
							class="imageNode">Home</span></a> <span> » </span></li>
					<li><a uniquekey="plugins"
						href="<?php echo $cptoken; ?>/scripts/command?PFILE=Plugins"
						class="leafNode"><span>Plugins</span></a><span> » </span></li>
					<li><a href="<?php echo $_SERVER['SCRIPT_NAME']?>" class="leafNode"><span>Nimbusec
								WHM Plugin</span></a></li>
				</ul>
			</div>
		</div>
		<div class="body-content">
			<br />
			<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
				<h1>Nimbusec Security Monitor</h1>
				<h2>Nimbusec API Configuration</h2>
				<p>
					API Key: <input type="text" name="api_key" style="width: 300px;" />
					<span class="error"><?php echo $apiKeyErr;?></span>
				</p>
				<p>
					API Secret: <input type="text" name="api_secret"
						style="width: 300px;" /> <span class="error"><?php echo $apiSecretErr;?></span>
				</p>
				<p>
					<!-- https://dev-api.nimbusec.com -->
					API Server: <input type="text" name="api_server"
						style="width: 300px;" value="https://dev-api.nimbusec.com" /> <span
						class="error"><?php echo $apiServerErr;?></span>
				</p>
				<p>
					<input type="Submit" name="install" value="Install Nimbusec Plugin">
				</p>

				<p>
					<input type="Submit" name="update" value="Update Nimbusec Bundles">
				</p>

				<p>
					<input type="Submit" name="uninstall"
						value="Uninstall Nimbusec Plugin">
				</p>
				<p>
				
				
				<h2><?php echo $info; ?></h2>
				<br />
				<h2>
					<span class="error"><?php echo $error;?></span>
				</h2>
				</p>
			</form>
		</div>
	</div>
</body>
</html>

