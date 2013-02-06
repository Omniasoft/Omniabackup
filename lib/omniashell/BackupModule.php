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
	
	/** 
	 * Compress a list of files or a mix of files and folders
	 *
	 * @param string $paths An array of paths to files or folders
	 * @param bool $filesOnly True if you want only files in archive excluding directory
	 * @return string The pathname to the archive
	 */
	protected function compress($paths, $filesOnly = false)
	{
		if(!is_array($paths)) return false;
		
		$tmp = $this->getTmpFile();
		
		// If only files change dir on every file but watch out for relative vs absolute
		$files = '';
		if($filesOnly)
		{
			$cwd = getcwd();
			foreach($paths as &$path)
			{
				if(!is_file($path)) continue;
				$d = dirname($path);
				$files .= ' -C '.($d[0] != '/' ? $cwd.'/'.$d : $d).' '.basename($path);
			}
		}
		else
			$files = implode(' ',$paths); //Else just implode that shit
	
		// Run the command
		$cmd = 'tar -czf  "'.$tmp.'" '.$files;
		$out = `$cmd`;
		
		// Check for errors
		if(!(file_exists($tmp) && filesize($tmp) > 0))
			return false;
		
		// Return output
		return $tmp;
	}
	
	/**
	 * Get temp file path
	 * 
	 * @param string Will make a path to tmp directory with given name (OPTIONAL)
	 * @return string A path to a temporary file (it does not create this file)
	 */
	protected function getTmpFile($fileName = null)
	{
		if(!is_dir('tmp'))
		{
			mkdir('tmp');
			chmod('tmp', 0777);
		}
		return 'tmp/'.(($fileName != null) ? $fileName : uniqid('OS').'.tmp');
	}
	
	/**
	 * Get a config value
	 *
	 * @return string If key not exists returns null else the value of the key in the ini
	 */
	protected function getConfig($key)
	{
		if($this->configCache == null)
			$this->configCache = @parse_ini_file('conf.d/'.$this->name.'.ini');

		if(!is_array($this->configCache))
			return null; // Something went wrong, sorry!
		
		return (array_key_exists($key, $this->configCache) ? $this->configCache[$key] : null);
	}

	/**
	 * Gets the correct module from a module name
	 * 
	 * @param string Name of the module
	 * @return BackupModule The module for name type
	 */
	static public function getModule($name)
	{
		switch(strtolower($name))
		{
			case 's3': return new ModuleS3();
			case 's3postgres': return new ModuleS3Postgres();
			default: return null;
		}
	}
}
