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
	
		$this->s3->putObjectFile($file, $bucket, $directory.(($directory != null) ? '/' : '').'L'.$life.'_'.baseName($file));
	}
	
	function run($args)
	{
		// Interpeter the arguments
		printf("S3 is running\n");
		print_r($args);
		
		$o = 0;
		
		// Default values for flags
		$dir = null;
		$fileOnly = false;
		$life = self::LIFE_MONTH;
		
		// Parse the optional flags
		for($i = 0; $i < count($args); $i++)
		{
			$arg = &$args[$i]; 
			if($arg[0] == '-')
			{
				$z = $i;
				switch($arg[1])
				{
					case 'f': $fileOnly = true; $a=1; break;
					case 'p': $dir = $args[++$i]; $a=2; break;
					case 'l': $life = intval($args[++$i]); $a=2; break;
				}
				for(; $z < $i+$a; $z++)
					unset($args[$z]);
			}
		}
		$args = array_values($args);
		print_r($args);
		
		// Not enough args
		if(count($args) < 3)
			return false; 
		
		// Rest variables parser
		$bucket = $args[0];
		$name = $args[1];
		$paths = array_slice($args, $o+2);
		
		// Implicit f flag
		$fileOnly = (count($paths) > 1 ? $fileOnly : true);
		
		printf("%s %s (dir: %s, fileOnly: %b, life: %d)\n", $bucket, $name, $dir, $fileOnly, $life);
		print_r($paths);
		
		//echo "S3::listBuckets(): ".print_r($this->s3->listBuckets(), 1)."\n";
		
		//$this->backupFile('conf.d/s3.ini', 'backup.devdb.nl', 'aap/faap');
		
		// Create the archive
		$tmpPath = $this->compress(array('conf.d/s3.ini', 'conf.d/.gitignore', 'omniabackup'), true);

		// Delete the archive for cleanup
		`rm $tmpPath`;
	}
}