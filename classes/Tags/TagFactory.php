<?php

namespace Claromentis\ThankYou\Tags;

class TagFactory
{
	public function Create(string $name, ?bool $active = null)
	{
		$active = $active ?? true;
		return new Tag($name, $active);
	}
}
