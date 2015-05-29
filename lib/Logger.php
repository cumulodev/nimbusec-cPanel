<?php

// This class is intended to depict a standard easy logging class
class Logger
{
	private $logFile;
	
	private $debug;
	
	private $initString = "******************* New Session *******************";
	private $endString = "***************************************************";
	
	function __construct($path, $fileName, $debug = false)
	{
		if(!is_dir($path)){
			mkdir($path, 0755, true);
		}
		
		$this->debug = $debug;
		
		$this->logFile = $path . "/". $fileName;
		file_put_contents($this->logFile, $this->initString . "\n", FILE_APPEND);
	}
	
	function close(){
		file_put_contents($this->logFile, $this->endString . "\n", FILE_APPEND);
	}
	
	function info($message){
		$date = date("Y-m-d_G:i:s");
		file_put_contents($this->logFile, "[{$date}] [INFO]: {$message}\n", FILE_APPEND);
	}	
	
	function debug($message){
		$date = date("Y-m-d_G:i:s");
		if($this->debug)
			file_put_contents($this->logFile, "[{$date}] [DEBUG]: {$message}\n", FILE_APPEND);
	}
	
	function warning($message){
		$date = date("Y-m-d_G:i:s");
		file_put_contents($this->logFile, "[{$date}] [WARNING]: {$message}\n", FILE_APPEND);
	}
	
	function error($message){
		$date = date("Y-m-d_G:i:s");
		file_put_contents($this->logFile, "[{$date}] [ERROR]: {$message}\n", FILE_APPEND);
	}
}
	
	
?>