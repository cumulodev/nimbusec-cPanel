<?php
require_once ("NimbusecAPI.php");

class PackageExtensions {
	
	private $api;
	
	public static $extensionSettingPath = "/var/cpanel/packages/extensions/nimbusec";
	public static $extensionTemplatePath = "/var/cpanel/packages/extensions/nimbusec.tt2";

	function __construct($key, $secret, $server, $extensionSettingPath = null, $extensionTemplatePath = null)
	{
		$this->api = new NimbusecAPI($key, $secret, $server);
		
		if(!empty($extensionSettingPath))
			self::$extensionSettingPath = $extensionSettingPath;
			
		if(!empty($extensionTemplatePath))
			self::$extensionTemplatePath = $extensionTemplatePath;
	}
	
	public function updatePackageExtensions() {
		
		$extVar = "nimbusec_bundles=";
	
		if (file_exists ( self::$extensionSettingPath ) && file_exists ( self::$extensionTemplatePath )) {
			$response = $this->api->findBundles ();
			if (gettype ( $response ) == "array") {
				
				if (! empty ( $response )) {
					// Concat bundles
					$bundleStr = $this->implodeBundles ( $response );
					
					//file_put_contents("/usr/local/nimbusec/nimbusec/logs/extensions.log", $bundleStr);
					
					// Read settings file
					$settingFile = file_get_contents ( self::$extensionSettingPath );
					
					if ($settingFile === false)
						throw new Exception ( "file_get_contents: Reading process failed" );
					else {
						$fileLines = explode ( "\n", $settingFile );
						for($i = 0; $i < count ( $fileLines ); $i ++) {
							if (strpos ( $fileLines [$i], $extVar ) !== false)
								$fileLines [$i] = substr_replace ( $fileLines [$i], $bundleStr, strlen ( $extVar ) );
						}
						
						if (file_put_contents ( self::$extensionSettingPath, implode ( "\n", $fileLines ) ) !== false)
							return true;
						else
							throw new Exception ( "file_put_contents: Writing process failed" );
					}
				} else
					throw new NimbusecException ( "No Bundles available" );
			} else
				throw new NimbusecException ( "A NimbusecException has been thrown: \n" . $response );
		} else
			throw new Exception ( "file_exists: No extension files existing" );
	}
	
	private function implodeBundles($bundles) {
		$arr = array ();
		foreach ( $bundles as $bundle )
			array_push ( $arr, "{$bundle['name']}={$bundle['id']}" );
		
		return implode ( ';', $arr );
	}
}

?>
