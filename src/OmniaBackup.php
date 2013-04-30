<?php
define('MODULE_PATH', 'Modules');
define('MODULE_CLASS', 'Module');

spl_autoload_register(function ($class)
{
	if ($class != MODULE_CLASS && strncmp($class, MODULE_CLASS, strlen(MODULE_CLASS)) == 0)
		include(MODULE_PATH.DIRECTORY_SEPARATOR.$class.'.php');
	else
		include($class.'.php');
});

class Omniabackup
{
	public $configDir = '/etc/omniashell';

	/**
	 * Run all the jobs
	 */
	function run()
	{
		// Get all cron jobs
		$jobs = $this->parseCron();
		printf ("Checking %d job(s)\n", count($jobs));
		
		// Go trough all crons and run them if due
		foreach ($jobs as &$job)
		{			
			// Skip jobs that are not due
			if(!$job->cron->isDue())
				continue;
			
			// Get the module
			$className = (MODULE_CLASS.$job->module);
			$module = new $className;
			if ($module == null)
				continue; // Wrong module specefied
			
			// Run the backup module
			if ( ! $module->run($job->args))
				printf("An error occured: %s\n", $module->getLastError());
		}	
	}
	
	/**
	 * Parses the omniashell cron
	 *
	 * @return array An array('cron', 'module', 'args') which contains all information about the job
	 */
	function parseCron()
	{
		// The return array with all Cron object and module + arguments
		$return = array();
		
		// Get file contents
		$crontents = file_get_contents('conf.d/cron.conf'); //$this->configDir.'/
		$lines = preg_split('/\r\n|\r|\n/', $crontents);
		
		// Parse all the lines
		foreach ($lines as &$l)
		{
			// Preprocess the line
			$l = preg_replace('!\s+!', ' ', trim($l));
			
			// Skip comment and empty lines
			if (empty($l)) continue;
			if ($l[0] == '#') continue;
			
			// Explode it and slice it
			$parts = explode(' ', $l);
			$time = array_slice($parts, 0, 5);
			$arguments = array_slice($parts, 6);
			
			// Check if enough arguments (5 time and 1 module) at least
			if (count($parts) < 6) continue; // Malformed line so skip
			
			// Rebuild our original time part
			$cronExp = implode(' ', $time);
			$cron = Cron\CronExpression::factory($cronExp);
			
			// Add it to the list
			$return[] = (object) array('cron' => $cron, 'module' => $parts[5], 'args' => $arguments);
		}
		return $return;
	}
}