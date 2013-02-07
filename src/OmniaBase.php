<?php

class OmniaBase
{
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
	 * @param bool Redirect STDERROR to script (default true)
	 * @return bool True if the command had no output and false otherwise
	 */
	protected function execute($command, $catchError = true)
	{
		// Capture also STDERR
		$cmd = $command.($catchError ? ' 2>&1' : '');
		echo $cmd."\n"; return;
		$this->lastError = `$cmd`;
		return empty($this->lastError);
	}
	
	/**
	 * Gets Passwd from password
	 *
	 * Creates a linux password from the given password
	 *
	 * @param string Password
	 * @return string Linux password
	 */
	function getPasswd($password)
	{
		$this->execute('openssl passwd -1 '.$password);
		return $this->getLastError();
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
	
	/**
	 * Is User
	 *
	 * Checks if the user is in the passwd file (thus is a user)
	 *
	 * @param string Username
	 * @return bool True when its a user else False
	 */
	function isUser($user)
	{
		return stristr($this->execute('cat '.$this->dirs['passwd']), $user);
	}

	/**
	 * Is Email
	 *
	 * Checks if the given email address is syntacticly correct
	 *
	 * @param string The email address
	 * @return bool True when syntacticly correct False else
	 */
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
}