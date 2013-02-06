<?php
class ModuleS3 extends BackupModule
{
	public $name = 's3';
	
	function run($args)
	{
		printf('S3 is running MOFOs'."\n");
		$this->getConfig('asd');
	}
}