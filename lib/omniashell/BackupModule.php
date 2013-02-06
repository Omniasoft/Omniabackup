<?php
include('BackupModules/Modules.php');

abstract class BackupModule
{
	// Publics
	public $name;

	// Protecteds
	protected $configFile;
	
	// Privates
	private $configCache = null;
	
	// Every module should atleast implement a run function
	abstract public function run($args);
	
	// Read the configuration file
	protected function getConfig($key)
	{
		if($this->configCache == null)
			$this->configCache = @parse_ini_file($this->name.'.ini');
		
		if(!is_array($this->configCache))
			return null; // Something went wrong, sorry!
			
		return (array_key_exists($key, $this->configCache) ? $this->configCache[$key] : null);
	}

	// A static function that returns a correct module for modules in cron
	static public function getModule($name)
	{
		switch(strtolower($name))
		{
			case 's3': return new ModuleS3();
			default: return null;
		}
	}
}
