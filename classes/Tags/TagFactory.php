<?php

namespace Claromentis\ThankYou\Tags;

use Claromentis\ThankYou\Tags\Exceptions\TagInvalidNameException;

class TagFactory
{
	/**
	 * @param string    $name
	 * @param bool|null $active
	 * @return Tag
	 * @throws TagInvalidNameException - If the Name is an empty string.
	 */
	public function Create(string $name, ?bool $active = null)
	{
		$active = $active ?? true;
		return new Tag($name, $active);
	}
}
