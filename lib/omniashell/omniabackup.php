<?php
require_once('lib/cron/build/cron.phar');
include('BackupModule.php');

class Omniabackup
{
	public $configDir = '/etc/omniashell';

	// Our main function
	function run()
	{
		// Get all cron jobs
		$jobs = $this->parseCron();
		
		// Go trough all crons and run them if due
		foreach($jobs as &$job)
		{
			// Skip jobs that are not due
			if(!$job['cron']->isDue())
				continue;
			
			// Get the module
			$module = BackupModule::getModule($job['module']);
			if($module == null) continue; // Wrong module specefied
			
			// Run the backup module
			$module->run($job['args']);
		}	
	}
	
	function parseCron()
	{
		// The return array with all Cron object and module + arguments
		$return = array();
		
		// Get file contents
		$crontents = file_get_contents('cron.conf'); //$this->configDir.'/
		$lines = preg_split('/\r\n|\r|\n/', $crontents);
		
		// Parse all the lines
		foreach($lines as &$l)
		{
			// Preprocess the line
			$l = preg_replace('!\s+!', ' ', trim($l));
			
			// Skip comment lines
			if($l[0] == '#') continue;
			
			// Explode it and slice it
			$parts = explode(' ', $l);
			$time = array_slice($parts, 0, 5);
			$arguments = array_slice($parts, 6);
			
			// Check if enough arguments (5 time and 1 module) at least
			if(count($parts) < 6) continue; // Malformed line so skip
			
			// Rebuild our original time part
			$cronExp = implode(' ', $time);
			$cron = Cron\CronExpression::factory($cronExp);
			
			// Add it to the list
			$return[] = array('cron' => $cron, 'module' => $parts[5], 'args' => $arguments);
		}
		
		return $return;
	}
}