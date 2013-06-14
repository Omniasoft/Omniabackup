<?php

namespace Omniabackup;

class Base
{
	/**
	 * Execute a shell command
	 *
	 * And redirects errors to the return of this function
	 *
	 * @param string The linux command
	 * @param bool Redirect STDERROR to script (default true)
	 * @return bool True if the command had no output and false otherwise
	 */
	static function execute($command, $catchError = true)
	{
		$cmd = $command.($catchError ? ' 2>&1' : ' > /dev/null 2>/dev/null &');
		return trim(`$cmd`);
	}
	
    /**
     * Kills a process by using the linux kill command
     * 
     * @param int $pid 
     * @param bool $force  
     * 
     * @return string
     */	
	static public function kill($pid, $force = false)
	{
		return $this->execute('kill'.($force ? ' -9 ' : ' ').$pid);
	}


	/**
	 * Get temp file path
	 * 
	 * @param string Will make a path to tmp directory with given name (OPTIONAL)
	 * @return string A path to a temporary file (it does not create this file)
	 */
	static public function getTempPath($fileName = null)
	{
		return TMP.DS.(($fileName != null) ? $fileName : uniqid('OS').'.tmp');
	}

	/** 
	 * Compress a list of files or a mix of files and folders
	 *
	 * @param string $paths An array of paths to files or folders
	 * @param bool $filesOnly True if you want only files in archive excluding directory
	 * @return string The pathname to the archive
	 */
	static public function compress($paths, $filesOnly = false)
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