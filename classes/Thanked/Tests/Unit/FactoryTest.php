<?php

use Claromentis\Core\Acl\PermOClass;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\ThankYou\Exception\OwnerClassNameException;
use Claromentis\ThankYou\Thanked\Factory;
use Claromentis\ThankYou\Thanked\Thanked;
use Claromentis\ThankYou\ThankYous\ThankYouUtility;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
	/**
	 * @var Factory
	 */
	private $factory;

	/**
	 * @var Lmsg|MockObject
	 */
	private $lmsg_mock;

	/**
	 * @var ThankYouUtility|MockObject
	 */
	private $thank_you_utility_mock;

	public function SetUp()
	{
		$this->lmsg_mock              = $this->createMock(Lmsg::class);
		$this->thank_you_utility_mock = $this->createMock(ThankYouUtility::class);
		$this->factory                = new Factory($this->lmsg_mock, $this->thank_you_utility_mock);
	}

	public function testCreateSuccessful()
	{
		$owner_class_name = "Some Owner Class Name";
		$owner_class_id   = 1;
		$this->thank_you_utility_mock->method('GetOwnerClassName')->willReturn($owner_class_name);
		$thanked = $this->factory->Create('a string', null, $owner_class_id);

		$this->assertTrue($thanked instanceof Thanked);
		$this->assertSame($owner_class_name, $thanked->GetOwnerClassName());
	}

	public function testCreateSuccessfulUnrecognisedOwnerClass()
	{
		$unknown_owner_class = "dno what you're on about!";
		$this->thank_you_utility_mock->method('GetOwnerClassName')->willThrowException(new OwnerClassNameException());
		$this->lmsg_mock->method('__invoke')->willReturn($unknown_owner_class);
		$thanked = $this->factory->Create('a string', null, 1);

		$this->assertTrue($thanked instanceof Thanked);
		$this->assertSame($unknown_owner_class, $thanked->GetOwnerClassName());
	}

	public function testCreateUnknownSuccessfulNoDetails()
	{
		$unknown_owner_class_name = 'I have no idea...';
		$this->lmsg_mock->expects($this->once())->method('__invoke')->with('thankyou.thanked.deleted')
			->willReturn($unknown_owner_class_name);
		$thanked_no_details = $this->factory->CreateUnknown();

		$this->assertSame($unknown_owner_class_name, $thanked_no_details->GetName());
	}

	public function testCreateUnknownSuccessfulUser()
	{
		$name             = 'Unknown User';
		$owner_class_id   = PermOClass::INDIVIDUAL;
		$owner_class_name = "Some Owner Class Name";
		$this->lmsg_mock->expects($this->once())->method('__invoke')->with('thankyou.thanked.user.deleted')
			->willReturn($name);
		$this->thank_you_utility_mock->method('GetOwnerClassName')
			->willReturn($owner_class_name);
		$thanked_unknown_user = $this->factory->CreateUnknown($owner_class_id);

		$this->assertSame($name, $thanked_unknown_user->GetName());
		$this->assertSame($owner_class_name, $thanked_unknown_user->GetOwnerClassName());
	}

	public function testCreateUnknownSuccessfulGroup()
	{
		$name             = 'Unknown Group';
		$owner_class_id   = PermOClass::GROUP;
		$owner_class_name = "Some Owner Class Name";
		$this->lmsg_mock->expects($this->once())->method('__invoke')->with('thankyou.thanked.group.deleted')
			->willReturn($name);
		$this->thank_you_utility_mock->method('GetOwnerClassName')
			->willReturn($owner_class_name);
		$thanked_unknown_group = $this->factory->CreateUnknown($owner_class_id);

		$this->assertSame($name, $thanked_unknown_group->GetName());
		$this->assertSame($owner_class_name, $thanked_unknown_group->GetOwnerClassName());
	}
}
