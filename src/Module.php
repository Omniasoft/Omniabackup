<?php

abstract class Module extends Base
{
	// Publics
	public $configName;

	// Protecteds
	protected $configFile;
	
	// Privates
	private $arguments;
	private $argOffset;
	private $configCache;
	
	// Every module should atleast implement a run function
	abstract public function run();
	
	/**
	 * Constructor
	 * 
	 * @param array $default overloaded if you want to pass your own arguments instead of default argv
	 */	
	public function __construct($args)
	{
		$this->configCache = array();
		$this->parseCmd($args);
	}
	
   /**
     * Parses command line arguments passed to class
     * 
     * @param array $arguments 
     */	
	private function parseCmd($arguments)
	{
		$this->arguments = array();
		
		if( ! is_array($arguments))
			return;
			
		$argArray = array();
		$flagArray = array();
		foreach ($arguments as &$argument)
		{
			// Flag var
			if (preg_match('/^-+(.*?)$/', $argument, $match))
			{
				$args = explode('=', $match[1]);
				$flagArray[$args[0]] = (count($args) == 2 ? $args[1] : true);		
			}
			else
			{
				$argArray[] = $argument;
			}
		}
		
		// Order array
		$this->argOffset = count($flagArray);
		$this->arguments = array_merge($flagArray, $argArray);
	}
	
    /**
     * Get the value for a command line flag
     * 
     * @param mixed $index 
     * 
     * @return mixed
     */
	public function getCmd($index, $default = false)
	{
		return array_key_exists($index, $this->arguments) ? $this->arguments[$index] : $default;
	}
	
	/**
	 * Get all unnamed arguments from a specefic offset
	 *
	 * @param int $offset
	 *
	 * @return array
	 */
	public function getCmds($offset = 0)
	{
		return array_slice($this->arguments, $this->argOffset+$offset);
	}
	
	
	/**
	 * Gets the number of arguments from the command line
	 *
	 * @return int
	 */
	public function getCmdNo()
	{
		return count($this->arguments);
	}
	
	/**
	 * Get a config value
	 *
	 * @return string If key not exists returns null else the value of the key in the ini
	 */
	protected function getConfig($key)
	{
		if ($this->configCache == null)
			$this->configCache = @parse_ini_file('conf.d'.DS.$this->configName.'.ini');

		if ( ! is_array($this->configCache))
			throw new Exception('Config file reading error');
		
		return (array_key_exists($key, $this->configCache) ? $this->configCache[$key] : null);
	}
		
	/** 
	 * Compress a list of files or a mix of files and folders
	 *
	 * @param string $paths An array of paths to files or folders
	 * @param bool $filesOnly True if you want only files in archive excluding directory
	 * @return string The pathname to the archive
	 */
	protected function compress($paths, $filesOnly = false)
	{
		if(!is_array($paths))
			throw new Exception('Paths was not an array');
		
		// Get tmp storage
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
				$files .= ' -C '.($d[0] != DS ? $cwd.DS.$d : $d).' '.basename($path);
			}
		}
		else
			$files = implode(' ',$paths); //Else just implode that shit
	
		// Run the command
		$this->execute('tar czf  "'.$tmp.'" '.$files);
		
		// Check for errors
		if( ! (file_exists($tmp) && filesize($tmp) > 0))
			throw new Exception('Something went wrong with compressing');
		
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
		return 'tmp'.DS.(($fileName != null) ? $fileName : uniqid('OS').'.tmp');
	}
}
