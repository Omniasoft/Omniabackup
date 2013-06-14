<?php
namespace Omniabackup\Filesystem;

use Omniabackup\Base;
use Ulrichsg\Getopt;

class Filesystem extends Base
{

	protected $options_;
	protected $getopt_;

	public function __construct($args)
	{
		
		$this->getopt_ = new Getopt($this->options_);
		$this->getopt_->parse($args);
	}

}