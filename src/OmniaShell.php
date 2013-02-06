<?php
class Omniashell
{
	// Settings
	public $version = '0.0.1';
	public $group = 'dev';
	public $dirs = array('www' => '/var/www', 'passwd' => '/etc/passwd');

	// Protected
	protected $lastError;
	
	/**
	 * Gets the last message of a execute call
	 *
	 * @return string Output of the last executed command
	 */
	public function getLastError()
	{
		if(empty($this->lastError))
			return "Unknown";
		return $this->lastError;
	}
	
	/**
	 * Execute a shell command
	 *
	 * And redirects errors to the return of this function
	 *
	 * @param string The linux command
	 * @return bool True if the command had no output and false otherwise
	 */
	protected function execute($command)
	{
		// Capture also STDERR
		$cmd = $command.' 2>&1';		
		$this->lastError = `$cmd`;
		return empty($this->lastError);
	}
	
	// Helper functions
	function isUser($user)
	{
		$passwd = $this->dirs['passwd'];
		return stristr(`cat $passwd`, $user);
	}

	function isEmail($email)
	{	
		if(!preg_match("/^[^@]{1,64}@[^@]{1,255}$/", $email))
			return false;
			
		// Split it into sections to make life easier
		$email_array = explode("@", $email);
		$local_array = explode(".", $email_array[0]);
		for($i = 0; $i < sizeof($local_array); $i++)
			if(!preg_match(":^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&?'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$:", $local_array[$i]))
				return false;

		if(!preg_match("/^\[?[0-9\.]+\]?$/", $email_array[1]))
		{
			$domain_array = explode(".", $email_array[1]);
			if(sizeof($domain_array) < 2)
				return false; // Not enough parts to domain
			for($i = 0; $i < sizeof($domain_array); $i++)
				if(!preg_match("/^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])| ?([A-Za-z0-9]+))$/", $domain_array[$i]))
					return false;
		}
		return true;
	}

	function makePostgres($user, $password)
	{
		$sql = "CREATE ROLE ".$user."; ALTER ROLE ".$user." WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN PASSWORD '".$password."'";
	}
	
	/**
	 * Generate a password
	 *
	 * @param int Lenght of the password
	 * @return string A random generate password
	 */
	function getPassword($len = 10)
	{
		$specials = '@#$%';
		$numbers = '1234567890';
		$letters = 'abcdefghijklmnopqrstuvwxyz';
		$capLetters = strtoupper($letters);
		
		$password = '';
		for($i = 0; $i < $len; $i++)
		{
			// Get the source at random (with smallest chance for special)
			$c = rand() % 33;
			if($c <= 10)
				$s = $letters;
			elseif($c <= 20)
				$s = $capLetters;
			elseif($c <= 29)
				$s = $numbers;
			else
				$s = $specials;
				
			$password .= $s[(rand() % strlen($s))];
		}
		return $password;
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