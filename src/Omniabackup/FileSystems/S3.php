<?php
namespace Omniabackup\FileSystems;

/**
 * Different ways to call S3 module
 * s3 [arguments] bucket name path1 [path2 ...]
 * arguments:
 * -f           The files are only files so add them that way (implicit when #files is 1)
 * -p <path>    The bucked path
 * -l <n>       The number of days to live
 * -w <path>	Change the working directory of tar (TODO)
 *
 * -clean       Special argument that cleans the S3 storage
 */
class S3 extends FileSystem
{
	// Lifetimes
	const LIFE_DAY = 1;
	const LIFE_WEEK = 7;
	const LIFE_MONTH = 31;

	// Publics
	public $configName = 's3';
	
	// Privates
	private $s3;
	
	// The constructor
	public function __construct($args)
	{
		parent::__construct($args);
		
		// Setup our wrapper for aws S3
		$this->s3 = new S3($this->getConfig('AccessKey'), $this->getConfig('SecretKey'));
	}
	
	/**
	 * Backup a file
	 *
	 * @param string $name Name for the new object
	 * @param string $file Full path to the file
	 * @param string $bucket The bucket name
	 * @param string $directory The directory for the file (can be like 'dir1', 'dir/dir2', etc..) leave empty for no directory
	 * @param string $life The life time for the object in days (add the LX_ rules to AWS)
	 * @return bool True on success False otherwise
	 */
	function backupFile($name, $file, $bucket, $directory = null, $life = self::LIFE_MONTH)
	{
		if ( ! file_exists($file))
			throw new Exception('The input file does not exists');
		
		$time = date('Ymd').'T'.date('His');
		if ( ! $this->s3->putObjectFile($file, $bucket, $directory.(($directory != null) ? '/' : '').'L'.$life.'_'.$name.'.'.$time.'.tar.gz'))
			throw new Exception('An error occured in the s3 library');
			
		return true;
	}
	
	/**
	 * Cleans a bucket
	 *
	 * Removes all files that have EOL expired
	 *
	 * @return int Number of files to remove
	 */
	function cleanBucket($bucket)
	{
		$fileNo = 0;
		$objects = $this->s3->getBucket($bucket);
		foreach($objects as &$object)
			$fileNo += $this->cleanFile( (object) $object, $bucket);
		return $fileNo;
	}
	
	/**
	 * Gets the end of life timestamp for an object
	 *
	 * @param object $object S3 Object
	 *
	 * @return bool|int
	 */
	function getEndOfLife($object)
	{
		$n = end(explode('/', $object->name));
		
		// Check if empty
		if (empty($n))
			return false;
		
		// Extract TTL			
		if (preg_match('/^L([0-9]+)_/', $n, $matches))
			return (intval($matches[1]) * 24 * 60 * 60) + $object->time;	
		return false;		
	}
	
	/**
	 * Cleans a object from S3
	 *
	 * @param object $object S3 Object
	 * @param string $bucket S3 Bucket
	 * 
	 * @return int 1 if file removed, 0 else
	 */
	function cleanFile($object, $bucket)
	{
		$eol = $this->getEndOfLife($object);
		if ($eol !== false && $eol < time())
				if ($this->s3->deleteObject($bucket, $object->name))
					return 1;
		return 0;
	}
	
	/**
	 * Run this module
	 *
	 * @param array Arguments for this function
	 * @return bool True on success False otherwise
	 */
	function run()
	{		
		// Check for cleanup mode
		if ($this->getCmd('cleanup'))
		{
			// Not enough args
			if ($this->getCmdNo() < 1)
				throw new Exception('Not enough arguments');
				
			printf("\t\tCleaning....\n");
			foreach ($this->getCmds() as $bucket)
				printf("\t\t  cleaned %d files from %s\n", $this->cleanBucket($bucket), $bucket);
				
			return;
		}
		
		// Not enough args
		if ($this->getCmdNo() < 3)
			throw new Exception('Not enough arguments');
		
		// Rest variables parser
		$dir = $this->getCmd('p', null);
		$fileOnly = $this->getCmd('f', false);
		$life = $this->getCmd('l', self::LIFE_MONTH);
		$bucket = $this->getCmd(0);
		$name = $this->getCmd(1);
		$paths = $this->getCmds(2);
		
		// Implicit f flag
		if (count($paths) == 1 && is_file($paths[0]))
			$fileOnly = true;
				
		// Create the archive
		$tmpPath = $this->compress($paths, $fileOnly);
		
		// Back this shit up
		$upload = $this->backupFile($name, $tmpPath, $bucket, $dir, $life);

		// Delete the archive for cleanup
		unlink($tmpPath);
		return $upload;
	}
}