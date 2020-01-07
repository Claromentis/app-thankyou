<?php

namespace Claromentis\ThankYou\Tags\Tests\Unit;

use Claromentis\ThankYou\Tags\Exceptions\TagInvalidNameException;
use Claromentis\ThankYou\Tags\Tag;
use Claromentis\ThankYou\Tags\TagFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TagFactoryTest extends TestCase
{
	/**
	 * @var TagFactory
	 */
	private $tag_factory;

	public function SetUp()
	{
		$this->tag_factory = new TagFactory();
	}

	public function testCreateSuccessful()
	{
		$name = "Any old Tag Name";
		$tag  = $this->tag_factory->Create($name);

		$this->assertTrue($tag instanceof Tag);
		$this->assertSame($name, $tag->GetName());
		$this->assertSame($tag->GetActive(), true);
	}

	public function testCreateInvalidName()
	{
		$name = "";
		$this->expectException(TagInvalidNameException::class);

		$this->tag_factory->Create($name);
	}
}
