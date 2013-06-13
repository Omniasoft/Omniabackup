<?php
namespace Omniabackup\Modules;

use Omniabackup\Base;
use \ConsoleKit\DefaultOptionsParser;

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
		if( ! is_array($paths))
			$paths = array($paths);

		if (count($paths) <= 0)
			throw new Exception('Not processing empty archive!');
		
		// Get tmp storage
		$tmp = Base::getTempPath();
		$tar = new \Archive_Tar($tmp, true);

		// Figure out what kind of path to remove
		$removePrefixPath = dirname($paths[0]);
		foreach ($paths as &$path)
		{
			if (strlen(dirname($path)) < strlen($removePrefixPath))
				$removePrefixPath = dirname($path);
		}

		// Create
		if (! $tar->createModify($paths, '' , $removePrefixPath))
			throw new Exception('Something went wrong with tar creation');
		
		// Sanity check
		if( ! (file_exists($tmp) && filesize($tmp) > 0))
			throw new Exception('Tar creation was good but still no file...');
		
		// Return output
		return $tmp;
	}
}
