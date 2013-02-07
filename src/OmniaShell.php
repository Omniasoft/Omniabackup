<?php
include('ShellPostgres.php');
include_once('OmniaBase.php');

class Omniashell extends OmniaBase
{
	// Settings
	public $version = '0.0.1';
	public $group = 'dev';
	public $dirs = array('www' => '/var/www', 'passwd' => '/etc/passwd');
	
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
		$this->execute('useradd -m -c "'.$email.'" -g '.$this->group.' -p '.$this->getPasswd($password).' '.$user);
		$this->execute('jk_jailuser -m -s /bin/bash -j '.$this->dirs['www'].'/'.$user.' '.$user);
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
		
		// Make some passwords
		$userPassword = $this->getPassword();
		$postgresPassword = $this->getPassword();
		
		// Create the different parts for this environment
		$this->addJailedUser($user, $email, $userPassword);
		$this->shellPostgres->createEnvironment($user, $postgresPassword);
	}
}