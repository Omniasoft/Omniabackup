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
		$create  = 'sudo -u postgres psql template1 -f - <<EOT'."\n";
		$create .= 'CREATE ROLE db'.$user.' NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOLOGIN;'."\n";
		$create .= 'CREATE ROLE '.$user.' NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT LOGIN ENCRYPTED PASSWORD \''.$password.'\';'."\n";
		$create .= 'GRANT db'.$user.' TO '.$user.';'."\n";
		$create .= 'CREATE DATABASE db'.$user.' WITH OWNER='.$user.';'."\n";
		$create .= 'REVOKE ALL ON DATABASE db'.$user.' FROM public;'."\n";
		$create .= 'EOT'."\n";
		
		$permis  = 'sudo -u postgres psql db'.$user.' -f - <<EOT'."\n";
		$permis .= 'GRANT ALL ON SCHEMA public TO '.$user.' WITH GRANT OPTION;'."\n";
		$permis .= 'EOT'."\n";

		$this->execute($create);
		$this->execute($permis);
	}

}