<?php
include('ShellPostgres.php');
include_once('OmniaBase.php');

class Omniashell extends OmniaBase
{
	// Settings
	public $version = '0.0.5';
	
	// Extra shells
	private $shellPostgres;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->shellPostgres = new ShellPostgres();
	}
	
	// Help function
	function help()
	{
		printf("Welcome to Omniashell version %s\n\n", $this->version);
		printf("   omniashell action [args]\n\n");
		printf(" See below for a list of actions and its arguments\n");
		printf("  User management:\n");
		printf("   devadd <user> <email>         - Creates a development account\n");
		printf("   devdel <user>                 - Deletes a development account\n");
		printf("   projectadd <user> <project>   - Creates a project for the developer\n");
		printf("   projectdel <user> <project>   - Deletes a project from the developer\n");
		printf("\n");
		printf("  Other actions:\n");
		printf("   pass                          - Generates a random password of lenght 10\n");
		printf("   passwd <password>             - Creates a linux shadow entry for a password\n");
		printf("\n");
	}
	
	/**
	 * Creates a jailed system user
	 *
	 * @param..
	 */
	function addJailedUser($user, $email, $password)
	{	
		$root = $this->dirs['www'].'/'.$user;
		
		// Create the jail
		$environments = 'basicshell editors extshellplusnet ssh sftp scp git postgres logbasics php';
		$this->execute('jk_init -v -j '.$root.' '.$environments);
		
		// Create the user and jail it!
		$this->execute('useradd -m -c \''.$email.'\' -g '.$this->group.' -p \''.$this->getPasswd($password).'\' '.$user);
		$this->execute('jk_jailuser -m -s /bin/bash -j '.$root.' '.$user);
		
		// Add some extra folders
		$this->execute('mkdir '.$root.'/logs');
		$this->execute('mkdir '.$root.'/tmp');
		$this->execute('mkdir '.$root.'/opt');
		
		// Fix permissions
		$this->execute('chmod a+rwx '.$root.'/tmp');
		
		// Add email addy
		$this->setUserEmail($user, $email);
	}

	function createWebDir($user, $project)
	{
		$usrDir = $this->dirs['www'].'/'.$user.'/';
		
		// Check for the web folder and if not exists create it
		if(!is_dir($usrDir."web"))
		{
			$this->execute('mkdir '.$usrDir.'web');
			$this->execute('chown '.$user.':'.$this->group.' '.$usrDir.'web');
		}
		$this->execute('mkdir '.$usrDir.'web/'.$project);
		$this->execute('chown '.$user.':'.$this->group.' '.$usrDir.'web/'.$project);
		
		return $usrDir.'web/'.$project;
	}
	
	/**
	 * Delete jailed user
	 *
	 */
	function deleteJailedUser($user)
	{
		$this->execute('userdel -r '.$user);
		$this->execute('rm -rf '.$this->dirs['www'].'/'.$user);
	}
	
	/**
	 * Create development environment
	 *
	 */
	function devadd($args)
	{
		// Arguments
		if(count($args) != 2) die("Wrong arg count\n");
		$user = $args[0];
		$email = $args[1];
		
		// Check input
		if($this->isUser($user))
			die("User already exists\n");
					
		if(!$this->isEmail($email))
			die("Not a valid email\n");
		
		// Run this
		printf("Making this development account (warning this will take some time)\n");
		
		// Make some passwords
		$userPassword = $this->getPassword();
		$postgresPassword = $this->getPassword();
		
		// Create the different parts for this environment
		$this->addJailedUser($user, $email, $userPassword);
		$this->shellPostgres->createEnvironment($user, $postgresPassword);
		
		// Report information
		printf("User information:\n Username: %s\n Email: %s\n Userpassword: %s\n Postgrespassword: %s\n", $user, $email, $userPassword, $postgresPassword);
		
		$this->sendMail($email, "[devdb] Environment added", array('user' => $user, 'email' => $email, 'lpassword' => $userPassword, 'ppassword' => $postgresPassword), 'devadd');
	}

	function devdel($args)
	{
		// Arguments
		if(count($args) != 1) die("Wrong argument count\n");
		$user = $args[0];
		
		// Check input
		if(!$this->isUser($user))
			die("User does not exists\n");
		
		// You sure?
		if(strtolower($this->ask("Are you sure you want to delete the user (n/y)", 'n')) != 'y')
			die("Not deleting!\n");
		
		// Delete all its projects
		// TODO: Fix this
		if($handle = opendir($this->dirs['www'].'/'.$user.'/web'))
		{
			while(false !== ($entry = readdir($handle)))
				if($entry != "." && $entry != "..")
					if(is_dir($entry))
						$this->projectdel(array($user, basename($entry)), true);
			closedir($handle);
		}
		
		// Lets delete him!
		$this->deleteJailedUser($user);
		$this->shellPostgres->deleteEnvironment($user);
		
		printf("User development environment removed!\n");
	}
	
	function projectadd($args)
	{
		// Arguments
		if(count($args) != 2) die("Wrong argument count\n");
		$user = $args[0];
		$project = $args[1];
		
		// Check input
		if(!$this->isUser($user))
			die("User does not exists\n");
		
		// Vhost name
		$projectDir = $this->dirs['www'].'/'.$user.'/web/'.$project;
		$vhostName = $user.'-'.$project;
		
		if(is_dir($projectDir))
			die("Project already exists\n");
		
		// Vhost template
		$vhost = $this->renderTemplate(array('basedir' => $this->dirs['www'], 'name' => $user, 'group' => $this->group, 'project' => $project), 'virtualhost');
		
		// Create dir
		$projectDir = $this->createWebDir($user, $project);
		
		// Save vhost and enable it and restart apache
		file_put_contents($this->dirs['vhost'].'/'.$vhostName, $vhost);
		$this->execute('a2ensite '.$vhostName);
		$this->execute('/etc/init.d/apache2 reload');
		
		// Postgres
		$this->shellPostgres->createScheme($user, $project);
		
		$email = $this->getUserEmail($user);
		
		// Info this user
		$this->sendMail($email, "[devdb] Project added", array('user' => $user, 'email' => $email, 'project' => $project), 'projectadd');
	}
	
	function projectdel($args, $force = false)
	{
		// Arguments
		if(count($args) != 2) die("Wrong argument count\n");
		$user = $args[0];
		$project = $args[1];
		
		// Vhost name
		$projectDir = $this->dirs['www'].'/'.$user.'/web/'.$project;
		$vhostName = $user.'-'.$project;
		
		// Check input
		if(!$this->isUser($user))
			die("User does not exists\n");
		
		if(!is_dir($projectDir))
			die("Project does not exists\n");
		
		// You sure?
		if(!$force)
			if(strtolower($this->ask("Are you sure you want to delete the project (n/y)", 'n')) != 'y')
				die("Not deleting!\n");
			
		// Disable it
		$this->execute('a2dissite '.$vhostName);
		$this->execute('rm -f '.$this->dirs['vhost'].'/'.$vhostName);
		$this->execute('rm -rf '.$projectDir);
		$this->execute('/etc/init.d/apache2 reload');

		// Postgres
		$this->shellPostgres->deleteScheme($user, $project);
	}
	
	// Useless options
	function pass()
	{
		die($this->getPassword()."\n");
	}
	
	function passwd($args)
	{	
		// Arguments
		if(count($args) != 1) die("Wrong argument count\n");
		$password = $args[0];
		
		$hash = $this->getPasswd($password);
		
		die($hash."\n");
	}
}