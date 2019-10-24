<?php

namespace Claromentis\ThankYou;

use Claromentis\ThankYou\Api\Configuration;
use Claromentis\ThankYou\Api\Tag;
use Claromentis\ThankYou\Api\ThankYous;

class Api
{
	private $config;

	private $tag;

	private $thank_yous;

	public function __construct(ThankYous $thank_yous, Configuration $config, Tag $tag)
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
