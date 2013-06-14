<?php
namespace Omniabackup\Module;

use Omniabackup\Base;
use ConsoleKit\DefaultOptionsParser;

abstract class Module extends Base
{
	// Publics
	public $configName;

	// Protecteds
	protected $configFile;
	protected $filesystem = null;
	
	// Privates
	private $arguments;
	private $argOffset;
	
	// Every module should atleast implement a run function
	abstract public function run();
	
	/**
	 * Constructor
	 * 
	 * @param array $default overloaded if you want to pass your own arguments instead of default argv
	 */	
	public function __construct($args)
	{
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
			
		print_r($arguments);
		$argArray = array();
		$flagArray = array();
		foreach ($arguments as &$argument)
		{
			// Flag var
			if (preg_match('/^-+(.*?)$/', $argument, $match))
			{
				print_r($match);
				$args = explode('=', $match[1]);
				print_r($args);
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
	 * Get all unnamed arguments from a specific offset
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
}
