<?php
/* Different ways to call S3 Postgres module
 * s3postgres bucket name
 */
class ModuleS3Postgres extends ModuleS3
{
	function compressDatabase()
	{
		// Format
		$tmp = $this->getTmpFile('dump.sql');
		$cmd1 = 'sudo -u postgres touch '.$tmp;
		$cmd2 = 'sudo -u postgres pg_dumpall -f '.$tmp.' -o';
		
		// Execute
		`$cmd1`; //File permission fix
		`$cmd2`;
		
		// Compress and cleanup
		$archive = $this->compress(array($tmp), true);
		unlink($tmp);
		
		if(!$archive)
		{
			unlink($archive);
			return false;
		}
		return $archive;
	}

	function run($args)
	{
		// Interpeter the arguments
		printf("S3Postgres is running\n");
				
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
		$file = $this->compressDatabase();
		
		if(!$file)
			return false;
		
		// Back this shit up
		$this->backupFile($name, $file, $bucket, $dir, $life);

		// Delete the archive for cleanup
		unlink($file);
	}
}