<?php
include_once('OmniaBase.php');

class ShellPostgres extends OmniaBase
{
	/**
	 * Create environment
	 *
	 * Creates a database (db$user) where the user can make his own schemes for all his projects 
	 *
	 * @param string Username
	 * @param string Password
	 * @return ...
	 */
	function createEnvironment($user, $password)
	{
		//Create specefic database for the user and give him rights on that only
		$create  = 'sudo -i -u postgres psql template1 -f - <<EOT'."\n";
		$create .= 'CREATE ROLE db'.$user.' WITH NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOLOGIN;'."\n";
		$create .= 'CREATE ROLE '.$user.' WITH NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT LOGIN PASSWORD \''.$password.'\';'."\n";
		$create .= 'GRANT db'.$user.' TO '.$user.';'."\n";
		$create .= 'CREATE DATABASE db'.$user.' WITH OWNER='.$user.';'."\n";
		$create .= 'REVOKE ALL ON DATABASE db'.$user.' FROM public;'."\n";
		$create .= 'EOT'."\n";
		
		$permis  = 'sudo -i -u postgres psql db'.$user.' -f - <<EOT'."\n";
		$permis .= 'GRANT ALL ON SCHEMA public TO '.$user.' WITH GRANT OPTION;'."\n";
		$permis .= 'EOT'."\n";

		$this->execute($create);
		$this->execute($permis);
	}
	
	/**
	 * Delete environment
	 *
	 * Deletes the postgres environment (DESTRUCTIVE)
	 */
	function deleteEnvironment($user)
	{
		$delete  = 'sudo -i -u postgres psql template1 -f - <<EOT'."\n";
		$delete .= 'DROP DATABASE IF EXISTS db'.$user.';'."\n";
		$delete .= 'DROP ROLE IF EXISTS db'.$user.';'."\n";
		$delete .= 'DROP ROLE IF EXISTS '.$user.';'."\n";
		$delete .= 'EOT'."\n";
		
		$this->execute($delete);
	}
	
	function createScheme($user, $project)
	{
		$create  = 'sudo -i -u postgres psql db'.$user.' -f - <<EOT'."\n";
		$create .= 'CREATE SCHEMA '.$project.' AUTHORIZATION '.$user.';'."\n";
		$create .= 'EOT'."\n";
		
		$this->execute($create);
	}
	
	function deleteScheme($user, $project)
	{
		$delete  = 'sudo -i -u postgres psql db'.$user.' -f - <<EOT'."\n";
		$delete .= 'DROP SCHEMA IF EXISTS '.$project.' CASCADE;'."\n";
		$delete .= 'EOT'."\n";
		
		$this->execute($delete);
	}
}