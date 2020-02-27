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

	/**
	 * Api constructor.
	 *
	 * @param ThankYous\Api     $thank_yous
	 * @param Configuration\Api $config
	 * @param Tags\Api          $tag
	 */
	public function __construct(ThankYous\Api $thank_yous, Configuration\Api $config, Tags\Api $tag)
	{
		$this->config     = $config;
		$this->tag        = $tag;
		$this->thank_yous = $thank_yous;
	}

	/**
	 * Returns the API for interacting with the Tags library.
	 *
	 * @return Tags\Api
	 */
	public function Tag()
	{
		return $this->tag;
	}

	/**
	 * Returns the API for interacting with the ThankYous library.
	 *
	 * @return ThankYous\Api
	 */
	public function ThankYous()
	{
		return $this->thank_yous;
	}

	/**
	 * Returns the API for interacting with the ThankYou Module Configuration.
	 *
	 * @return Configuration\Api
	 */
	public function Configuration()
	{
		return $this->config;
	}
}
