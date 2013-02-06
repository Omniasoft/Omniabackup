<?php
if(!class_exists('S3')) require_once('lib/amazon-s3/S3.php');

/* Life cycle rules
 * In the bucket you should set up rules like LX_ where X is the mount of days after which the object expires
 */

/* Different ways to call S3 module
 * s3 [arguments] file bucket bucketPath lifeCycle
 *
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
	 * @param string $file Full path to the file
	 * @param string $bucket The bucket name
	 * @param string $directory The directory for the file (can be like 'dir1', 'dir/dir2', etc..) leave empty for no directory
	 * @param string $life The life time for the object in days (add the LX_ rules to AWS)
	 */
	function backupFile($file, $bucket, $directory = null, $life = self::LIFE_MONTH)
	{
		if(!file_exists($file))
			return false;
	
		$this->s3->putObjectFile($file, $bucket, $directory.(($directory != null) ? '/' : '').'L'.$life.'_'.baseName($file));
	}
	
	function run($args)
	{
		printf("S3 is running\n");
		print_r($args);
		
		echo "S3::listBuckets(): ".print_r($this->s3->listBuckets(), 1)."\n";
		
		//$this->backupFile('conf.d/s3.ini', 'backup.devdb.nl', 'aap/faap');
		
		$this->compress(array('conf.d/s3.ini', 'conf.d/.gitignore', 'omniabackup', 'lib'), false);
	}
}