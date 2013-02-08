<?php

class OmniaBase
{
	// Password options
	const PW_ALL = 0;
	const PW_NUM_LET = 1;
	const PW_NUM = 2;
	const PW_LET = 3;
	
	// Settings
	private $templateDir = 'templates';
	private $bcc = array('ict@deskbookers.com', 'k.valk@deskbookers.com');
	public $group = 'dev';
	public $dirs = array('www' => '/var/www', 'passwd' => '/etc/passwd', 'vhost' => '/etc/apache2/sites-available');
	
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
		echo $cmd."\n"; //return;
		$this->lastError = trim(`$cmd`);
		echo $this->lastError."\n";
		return empty($this->lastError);
	}
	
	protected function getUserEmail($user)
	{
		return file_get_contents($this->dirs['www'].'/'.$user.'/email');
	}
	
	protected function setUserEmail($user, $email)
	{
		file_put_contents($this->dirs['www'].'/'.$user.'/email', $email);
	}
	
	protected function sendMail($email, $subject, $variables, $template)
	{
		// Make header		
		$headers   = array();
		$headers[] = 'MIME-Version: 1.0';
		$headers[] = 'Content-type: text/plain; charset=iso-8859-1';
		$headers[] = 'From: Deskbookers Accounts <ict@deskbookers.com>';
		$headers[] = 'Bcc: '.implode(' ', $this->bcc);
		$headers[] = 'Reply-To: Deskbookers Accounts <ict@deskbookers.com>';
		$headers[] = 'X-Mailer: PHP/'.phpversion();

		$contents = $this->renderTemplate($variables, 'mail_'.$template); //Render email template
		$contents = preg_replace('~\R~u', "\r\n", $contents); //Normalize line endings to CRLF (defined by RFC2822)
		$contents = wordwrap($contents, 78, "\r\n"); //Word wrap on 78 characters per line (defined by RFC2822)
		
		$return = mail($email, $subject, $contents, implode("\r\n", $headers));
		
		printf("Sended a mail with ret: %d, to: %s\n", $return, $email);
		
	}
	
	protected function renderTemplate($variables, $template)
	{
		$file = file_get_contents($this->templateDir.'/'.$template.'.tpl');
		foreach($variables as $key => $value)
			$file = str_replace('<%'.$key.'%>', $value, $file);
		
		return $file;
	}
	
	/**
	 * Asks a user a question
	 */
	protected function ask($question, $defaultValue = false)
	{
		echo $question.($defaultValue ? ' ['.$defaultValue.']' : '').': ';
		$value = trim(fgets(STDIN));
		return (empty($value) ? $defaultValue : $value);
	}

	/**
	 * Gets Passwd from password
	 *
	 * Creates a linux password from the given password with the SHA-512 algorithm
	 *
	 * @param string Password
	 * @return string Linux password
	 */
	function getPasswd($password)
	{
		return crypt($password, '$6$'.$this->getPassword(12, self::PW_NUM_LET).'$');
	}
	
	/**
	 * Generate a password
	 *
	 * @param int Lenght of the password
	 * @return string A random generate password
	 */
	function getPassword($len = 10, $type = self::PW_ALL)
	{
		$input = array();
	
		// Different sources
		$specials = '@#$%';
		$numbers = '1234567890';
		$letters = 'abcdefghijklmnopqrstuvwxyz';
		$capLetters = strtoupper($letters);
		
		if($type == self::PW_NUM_LET)
			$input = array($letters, $numbers, $capLetters, $letters);
		else
			$input = array($letters, $specials, $letters, $numbers, $capLetters);
		
		$password = '';
		for($i = 0; $i < $len; $i++)
		{
			$c = rand() % count($input);								
			$password .= $input[$c][(rand() % strlen($input[$c]))];
			
			// Only use one special char
			if($input[$c] == $specials)
			{
				unset($input[$c]);
				$input = array_values($input);
			}
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
		$this->execute('cat '.$this->dirs['passwd']);
		return stristr($this->getLastError(), $user);
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