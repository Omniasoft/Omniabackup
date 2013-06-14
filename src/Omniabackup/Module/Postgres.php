<?php
namespace Omniabackup\Module;

/**
 * Different ways to call S3Postgress module
 * s3postgress [arguments] bucket name
 * arguments:
 * -p <path>    The bucked path
 * -l <n>       The number of days to live
 */
class Postgres extends Module
{		
	/**
	 * Dump and compress the database
	 *
	 * @return string Path to the compressed dump
	 */
	function getDatabaseDump()
	{
		// Execute the shell
		$sql = Module::getTempPath('Fulldump_'.date('d-m-Y_H-i').'.sql');
		
		// Make tmp postgres file
		$this->execute('sudo -u postgres touch '.$sql);
		if ( ! file_exists($sql))
			throw new \Exception('Unable to touch file');
		
		$this->execute('sudo -u postgres pg_dumpall -f '.$sql.' -o --inserts');
		if (filesize($sql) <= 0)
			throw new \Exception('Something wrong with database dump (zero filesize)');

		// Compress
		$archive = Module::compress(array($sql), true);
		
		// Cleanup
		unlink($sql);
		return $archive;
	}
	
	/**
	 * Run this module
	 *
	 * @param array Arguments for this function
	 * @return bool True on success False otherwise
	 */
	function run()
	{
		// Not enough args
		if($this->getCmdNo() < 2)
			throw new \Exception('Not enough arguments');
			
		// Get variables
		$path = $this->getCmd('p', null);
		$life = $this->getCmd('l', 0);
		$bucket = $this->getCmd(0);
		$name = $this->getCmd(1);
		
		// Dump the postgres database
		$file = $this->getDatabaseDump();
		if(!$file)
			throw new \Exception('Failed to dump the database');
		
		// Back this shit up
		$upload = $this->backupFile($name, $file, $bucket, $path, $life);

		// Delete the archive for cleanup
		unlink($file);
		return $upload;
	}
}