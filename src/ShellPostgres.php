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
		$create = ' sudo -u postgres psql template1 -f - <<EOT
					CREATE ROLE db'.$user.' NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOLOGIN;
					CREATE ROLE '.$user.' NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT LOGIN ENCRYPTED PASSWORD \''.$password.'\';
					GRANT db'.$user.' TO '.$user.';
					CREATE DATABASE db'.$user.' WITH OWNER='.$user.';
					REVOKE ALL ON DATABASE db'.$user.' FROM public;
					EOT';
		$permis = ' sudo -u postgres psql db'.$user.' -f - <<EOT
					GRANT ALL ON SCHEMA public TO '.$user.' WITH GRANT OPTION;
					EOT';

		$this->execute($create);
		$this->execute($permis);
	}

}