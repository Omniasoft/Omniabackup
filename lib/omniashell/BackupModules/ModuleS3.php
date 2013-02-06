<?php
if(!class_exists('S3')) require_once('lib/amazon-s3/S3.php');

/* Life cycle rules
 * In the bucket you should set up rules like LX_ where X is the mount of days after which the object expires
 */

/* Different ways to call S3 module
 * s3 [arguments] bucket name path1 [path2 ...]
 * arguments:
 * -f           The files are only files so add them that way (implicit when #files is 1)
 * -p <path>    The bucked path
 * -l <n>       The number of days to live
 */
class ModuleS3 extends BackupModule
{
	// Lifetimes
	const LIFE_DAY = 1;
	const LIFE_WEEK = 7;
	const LIFE_MONTH = 31;

	public $name = 's3';
	private $s3;
	
	public function __construct()
	{
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
	 */
	function backupFile($name, $file, $bucket, $directory = null, $life = self::LIFE_MONTH)
	{
		if(!file_exists($file))
			return false;
		
		$time = date('Ymd').'T'.date('His');

		$this->s3->putObjectFile($file, $bucket, $directory.(($directory != null) ? '/' : '').'L'.$life.'_'.$name.'.'.$time.'.tar.gz');
	}
	
	function run($args)
	{
		// Interpeter the arguments
		printf("S3 is running\n");
				
		// Default values for flags
		$dir = null;
		$fileOnly = false;
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
					case 'f': $fileOnly = true; $a=1; break;
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
		if(count($args) < 3)
			return false;
		
		// Rest variables parser
		$bucket = $args[0];
		$name = $args[1];
		$paths = array_slice($args, 2);
		
		// Implicit f flag
		if(count($paths) == 1 && is_file($paths[0]))
			$fileOnly = true;
				
		// Create the archive
		$tmpPath = $this->compress($paths, $fileOnly);
		if(!$tmpPath)
			return false;
		
		// Back this shit up
		$this->backupFile($name, $tmpPath, $bucket, $dir, $life);

		// Delete the archive for cleanup
		unlink($tmpPath);
	}
}