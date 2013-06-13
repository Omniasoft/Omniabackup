<?php
namespace Omniabackup;

class Config
{
	private static $data = array();

	/**
	 * Gets a configuration object
	 *
	 * @param String $config Which configuration object has to be retrieved
	 * @return Object Returns an object representation of the configuration file array
	 */
	public static function get($config)
	{
		// Return cached content
		if (isset(self::$data[$config]))
			return self::$data[$config];

		$filename = PATH_CONFIG.'/'.$config.'.php';
		if (is_readable($filename) && ! is_dir($filename))
		{
			// Cache the content
			self::$data[$config] = (object) include($filename);
			return self::$data[$config];
		}
		else
		{
			throw new \Exception('Config file '.$filename.' not found or is not readable.');
		}
	}
}
