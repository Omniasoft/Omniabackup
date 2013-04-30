<?php
/**
 * Different ways to call S3Postgress module
 * s3postgress [arguments] bucket name
 * arguments:
 * -p <path>    The bucked path
 * -l <n>       The number of days to live
 */
class ModuleS3Postgres extends Module
{
	/**
	 * Constants
	 */
	const LIFE_MONTH = 31;
	
	// My name
	public $name = 's3postgres';
	
	/**
	 * Dump and compress the database
	 *
	 * @return string Path to the compressed dump
	 */
	function getDatabaseDump()
	{
		// Execute the shell
		$sql = $this->getTmpFile('dump.sql');
		if(!$this->execute('sudo -u postgres touch '.$sql))
			return false;		
		if(!$this->execute('sudo -u postgres pg_dumpall -f '.$sql.' -o'))
			return false;
				
		// Compress
		$archive = $this->compress(array($sql), true);
		
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
	function run($args)
	{
		printf("Executing job S3Postgres\n");
				
		// Default values for flags
		$dir = null;
		$life = self::LIFE_MONTH;
		
		// Parse the optional flags
		$c = count($args);
		for($i = 0; $i < $c; $i++)
		{
			$arg = &$args[$i]; 
			if($arg[0] == '-')
			{
				$t = $i;
				switch($arg[1])
				{
					case 'p': $dir = $args[++$i]; $a=2; break;
					case 'l': $life = intval($args[++$i]); $a=2; break;
					default: $a=1; break;
				}
				for($z = $t; $z < $t+$a; $z++)
					unset($args[$z]);
			}
		}
		$args = array_values($args);
		
		// Not enough args
		if(count($args) < 2)
			return false;
			
		// Rest variables parser
		$bucket = $args[0];
		$name = $args[1];
		
		// Dump the postgres database
		$file = $this->getDatabaseDump();
		if(!$file)
			return false;
		
		// Back this shit up
		$upload = $this->backupFile($name, $file, $bucket, $dir, $life);

		// Delete the archive for cleanup
		unlink($file);
		return $upload;
	}
}