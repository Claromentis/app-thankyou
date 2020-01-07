<?php

namespace Claromentis\ThankYou;

use Claromentis\ThankYou\Configuration;
use Claromentis\ThankYou\Tags;
use Claromentis\ThankYou\ThankYous;

class Api
{
	/**
	 * @var Configuration\Api
	 */
	private $config;

	/**
	 * @var Tags\Api
	 */
	private $tag;

	/**
	 * @var ThankYous\Api
	 */
	private $thank_yous;

	public function __construct(ThankYous\Api $thank_yous, Configuration\Api $config, Tags\Api $tag)
	{
		$this->config     = $config;
		$this->tag        = $tag;
		$this->thank_yous = $thank_yous;
	}

	public function Tag()
	{
		return $this->tag;
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
