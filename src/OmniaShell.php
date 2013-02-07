<?php
include('ShellPostgres.php');
include('OmniaBase.php');

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
	
	// Setuping up an jailed account
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
			
		// Add user
		$password = $this->getPassword();
		$cmdUseradd = 'useradd -m -c "'.$email.'" -g '.$this->group.' -p '.$password.' '.$user;
		$cmdJailuser = 'jk_jailuser -m -j '.$this->dirs['www'].'/'.$user.' '.$user;
		
		
		
		printf("New user\n%s\n%s", $cmdUseradd, $cmdJailuser);
	}
	
	// Installing phpPgAdmin
	function phpphgadmin()
	{
	
	}
}