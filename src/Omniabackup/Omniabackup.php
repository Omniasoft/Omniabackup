<?php
namespace Omniabackup;

use \Cron\CronExpression;

class Omniabackup
{	
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
			if( ! $job->cron->isDue())
				continue;
			
			// Get the module
			$className = 'Omniabackup\\Module\\'.$job->module;
			$module = new $className($job->args);
			if ($module == null)
				continue; // Wrong module specified
			
			// Run the backup module
			printf("\tRunning %s\n", $job->module);
			$module->run();
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
		
		// Check if we can read the crontab
		if ( ! is_readable(PATH_CRONTAB))
			throw new \Exception('Crontab is not readable.');

		// Get file contents and split on newline
		$crontents = file_get_contents(PATH_CRONTAB);
		$lines = preg_split('/\R/u', $crontents);
		
		// Parse all the lines
		foreach ($lines as &$l)
		{
			// Preprocess the line on whitespaces
			$l = preg_replace('!\s+!', ' ', trim($l));
			
			// Skip comment and empty lines
			if (empty($l)) continue;
			if ($l[0] == '#') continue;
			
			// Explode it and slice it
			preg_match_all('/[a-z|A-Z|0-9|=|-]*"(?:\\\\.|[^\\\\"])*"|\S+/', $l, $parts);
			$parts = $parts[0];
			$time = array_slice($parts, 0, 5);
			$arguments = array_slice($parts, 6);
			
			// Check if enough arguments (5 time and 1 module) at least
			if (count($parts) < 6) continue; // Malformed line so skip
			
			// Rebuild our original time part
			$cronExp = implode(' ', $time);
			$cron = CronExpression::factory($cronExp);
			
			// Add it to the list
			$return[] = (object) array('cron' => $cron, 'module' => $parts[5], 'args' => $arguments);
		}
		return $return;
	}
}