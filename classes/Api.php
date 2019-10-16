<?php

namespace Claromentis\ThankYou;

use Claromentis\ThankYou\Api\Configuration;
use Claromentis\ThankYou\Api\ThankYous;

class Api
{
	private $config;

	private $thank_yous;

	public function __construct(ThankYous $thank_yous, Configuration $config)
	{
		$this->config     = $config;
		$this->thank_yous = $thank_yous;
	}

	public function ThankYous()
	{
		return $this->thank_yous;
	}

	public function Configuration()
	{
		return $this->config;
	}
}
