<?php
namespace Omniabackup\Module;

use Omniabackup\Base;
use Ulrichsg\Getopt;

abstract class Module extends Base
{
	// Protected
	protected $filesystem_ = null;
	protected $options_;
	protected $getopt_;

	public function __construct($args)
	{
		$this->options_[] = array(null, 'filesystem', Getopt::REQUIRED_ARGUMENT, 'The file system and its arguments');

		// Parse options
		$this->getopt_ = new Getopt($this->options_);
		$this->getopt_->parse($args);

		// Check if there is a filesystem defined
		if ($this->getopt_->getOption('filesystem') != null)
		{
			$option = str_replace('\"', '"', trim($this->getopt_->getOption('filesystem'), '"'));
			preg_match_all(REGEX_CLI, $option, $parts);

			$filesystem = 'Omniabackup\\Filesystem\\'.$parts[0][0];
			$this->filesystem_ = new $filesystem(array_slice($parts[0], 1));
		}
	}

	// Abstract functions to implement
	abstract public function run();
}