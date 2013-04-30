<?php
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
class ModuleS3 extends Module
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
	 * Run this module
	 *
	 * @param array Arguments for this function
	 * @return bool True on success False otherwise
	 */
	function run()
	{		
		// Not enough args
		if($this->getCmdNo() < 3)
			throw new Exception('Not enough arguments');
		
		// Rest variables parser
		$dir = $this->getCmd('p', null);
		$fileOnly = $this->getCmd('f', false);
		$life = $this->getCmd('l', self::LIFE_MONTH);
		$bucket = $this->getCmd(0);
		$name = $this->getCmd(1);
		$paths = $this->getCmds(2);
		
		// Implicit f flag
		if(count($paths) == 1 && is_file($paths[0]))
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