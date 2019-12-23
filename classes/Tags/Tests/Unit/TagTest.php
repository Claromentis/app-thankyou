<?php

namespace Claromentis\ThankYou\Tags\Tests\Unit;

use Claromentis\ThankYou\Tags\Exceptions\TagInvalidNameException;
use Claromentis\ThankYou\Tags\Tag;
use PHPUnit\Framework\TestCase;

class TagTest extends TestCase
{
	public function testTagSuccessfulConstruct()
	{
		$name   = "Any 'ol string";
		$active = true;

		$this->assertTrue(new Tag($name, $active) instanceof Tag);
	}

	public function testTagInvalidName()
	{
		$name   = "";
		$active = true;
		$this->expectException(TagInvalidNameException::class);

		new Tag($name, $active);
	}
}
