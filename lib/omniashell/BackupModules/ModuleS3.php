<?php
if(!class_exists('S3')) require_once('lib/amazon-s3/S3.php');

class ModuleS3 extends BackupModule
{
	public $name = 's3';
	
	function run($args)
	{
		printf('S3 is running MOFOs'."\n");
		echo $this->getConfig('AccessKey');
	}
}