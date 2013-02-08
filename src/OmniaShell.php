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
		printf("Welcome to Omniashell version %s\n", $this->version);
		printf("  omniashell action user [-P <project>] [args...]\n");
		printf("  Where actions can be:\n");
		printf("   - devadd: Adds a dev user\n");
		printf("\n");
	}
	
	/**
	 * Creates a jailed system user
	 *
	 * @param..
	 * 
	 */
	function addJailedUser($user, $email, $password)
	{	
		// Create the jail
		$environments = 'basicshell editors extendedshell netutils ssh sftp scp apacheutils git postgres';
		$this->execute('jk_init -v -j '.$this->dirs['www'].'/'.$user.' '.$environments);
		
		// Create the user and jail it!
		$this->execute('useradd -m -c \''.$email.'\' -g '.$this->group.' -p \''.$this->getPasswd($password).'\' '.$user);
		$this->execute('jk_jailuser -m -s /bin/bash -j '.$this->dirs['www'].'/'.$user.' '.$user);
		
		// Add the log folder
		$this->execute('mkdir '.$this->dirs['www'].'/'.$user.'/logs');
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
	function devadd($user, $args)
	{
		// Check input
		if($this->isUser($user))
			die('User already exists');
		
		if(count($args) != 1)
			die('Wrong arg count');
		
		$email = $args[0];
		if(!$this->isEmail($email))
			die('Not a valid email');
		
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
	
	function projectadd($user, $project)
	{
		// Check input
		if(!$this->isUser($user))
			die("User does not exists\n");
		
		$vhostName = $user.'-'.$project;
		
		// Vhost template
		$vhost = $this->renderTemplate(array('basedir' => $this->dirs['www'], 'name' => $user, 'group' => $this->group, 'project' => $project), 'virtualhost');
		
		// Create dir
		$projectDir = $this->createWebDir($user, $project);
		
		// Save vhost and enable it and restart apache
		file_put_contents($this->dirs['vhost'].'/'.$vhostName, $vhost);
		$this->execute('a2ensite '.$vhostName);
		$this->execute('/etc/init.d/apache2 reload');
		
		$email = $this->getUserEmail($user);
		
		// Info this user
		$this->sendMail($email, "[devdb] Project added", array('user' => $user, 'email' => $email, 'project' => $project), 'projectadd');
	}
	
	function projectdel($user, $project)
	{
		// Check input
		if(!$this->isUser($user))
			die("User does not exists\n");
		
		$vhostName = $user.'-'.$project;
		
		// Disable it
		$this->execute('a2dissite '.$vhostName);
		$this->execute('/etc/init.d/apache2 reload');
		
		// Remove it
		$this->execute('rm -f '.$this->dirs['vhost'].'/'.$vhostName);
	}
	
	function devdelete($user)
	{
		// Check input
		if(!$this->isUser($user))
			die("User does not exists\n");
		
		// You sure?
		if(strtolower($this->ask("Are you sure you want to delete the user (n/y)", 'n')) != 'y')
			die("Not deleting!\n");
			
		// Lets delete him!
		$this->deleteJailedUser($user);
		$this->shellPostgres->deleteEnvironment($user);
		
		printf("User development environment removed!\n");
	}
}