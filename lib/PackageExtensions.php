<?php
require_once ("NimbusecAPI.php");

class PackageExtensions {

	private $api;

	public static $extensionSettingPath = "/var/cpanel/packages/extensions/nimbusec";
	public static $extensionTemplatePath = "/var/cpanel/packages/extensions/nimbusec.tt2";
	public static $extVar = "nimbusec_bundles=";

	function __construct($key, $secret, $extensionSettingPath = null, $extensionTemplatePath = null)
	{
		$this->api = new NimbusecAPI($key, $secret);

		if(!empty($extensionSettingPath))
			self::$extensionSettingPath = $extensionSettingPath;

		if(!empty($extensionTemplatePath))
			self::$extensionTemplatePath = $extensionTemplatePath;
	}

	public static function getBundles(){

		if (file_exists ( self::$extensionSettingPath )) {
			$settingFile = file_get_contents( self::$extensionSettingPath );
			$fileLines = explode ( "\n", $settingFile );

			$bundleJSON = array();
			$bundleStr = "";

			for($i = 0; $i < count ( $fileLines ); $i ++) {
				if (strpos ( $fileLines [$i], self::$extVar ) !== false)
					 $bundleStr = substr_replace ( $fileLines [$i], "", 0, strlen ( self::$extVar ) );
			}

			$bundleArr = explode ( ";", $bundleStr);
			foreach ($bundleArr as $bundle){
				$bundleFields = explode( "=", $bundle);
				array_push($bundleJSON, array("id" => $bundleFields[1], "name" => $bundleFields[0] ));
			}
			return $bundleJSON;
		}else
			throw new Exception ( __METHOD__ . " - file_exists: No setting file existing" );
	}

	public function updatePackageExtensions() {

		if (file_exists ( self::$extensionSettingPath ) && file_exists ( self::$extensionTemplatePath )) {
			$response = $this->api->findBundles ();
			if (gettype ( $response ) == "array") {

				if (! empty ( $response )) {
					// Concat bundles
					$bundleStr = $this->implodeBundles ( $response );

					// Read settings file
					$settingFile = file_get_contents ( self::$extensionSettingPath );

					if ($settingFile === false)
						throw new Exception ( __METHOD__ . " - file_get_contents: Reading process failed" );
					else {
						$fileLines = explode ( "\n", $settingFile );
						for($i = 0; $i < count ( $fileLines ); $i ++) {
							if (strpos ( $fileLines [$i], self::$extVar ) !== false)
								$fileLines [$i] = substr_replace ( $fileLines [$i], $bundleStr, strlen ( self::$extVar ) );
						}

						if (file_put_contents ( self::$extensionSettingPath, implode ( "\n", $fileLines ) ) !== false)
							return true;
						else
							throw new Exception ( __METHOD__ . " - file_put_contents: Writing process failed" );
					}
				} else
					throw new NimbusecException ( __METHOD__ . " - No Bundles available" );
			} else
				throw new NimbusecException ( __METHOD__ . " - A NimbusecException has been thrown: \n" . $response );
		} else
			throw new Exception ( __METHOD__ . " - file_exists: No extension files existing" );
	}

	private function implodeBundles($bundles) {
		$arr = array ();
		foreach ( $bundles as $bundle )
			array_push ( $arr, "{$bundle['name']}={$bundle['id']}" );

		return implode ( ';', $arr );
	}
}

?>
