<?php

namespace Claromentis\ThankYou;

use Claromentis\ThankYou\Api\ThankYous;

class Api
{
	private $thank_yous;

	public function __construct(ThankYous $thank_yous)
	{
		$this->thank_yous = $thank_yous;
	}

	public function ThankYous()
	{
		return $this->thank_yous;
	}
}
