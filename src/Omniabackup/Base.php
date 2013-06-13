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
}