<?php

use Claromentis\Core\Acl\PermOClass;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\ThankYou\Exception\ThankYouOClass;
use Claromentis\ThankYou\Thankable\Factory;
use Claromentis\ThankYou\Thankable\Thankable;
use Claromentis\ThankYou\ThankYous\ThankYouUtility;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
	/**
	 * @var Factory $factory
	 */
	private $factory;

	/**
	 * @var Lmsg $lmsg_mock
	 */
	private $lmsg_mock;

	/**
	 * @var ThankYouUtility $thank_you_utility_mock
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
		$this->thank_you_utility_mock->method('GetOwnerClassNamesFromIds')->willReturn([$owner_class_id => $owner_class_name]);
		$thankable = $this->factory->Create('a string', null, $owner_class_id);

		$this->assertTrue($thankable instanceof Thankable);
		$this->assertSame($owner_class_name, $thankable->GetOwnerClassName());
	}

	public function testCreateSuccessfulUnrecognisedOwnerClass()
	{
		$unknown_owner_class = "dno what you're on about!";
		$this->thank_you_utility_mock->method('GetOwnerClassNamesFromIds')->willThrowException(new ThankYouOClass());
		$this->lmsg_mock->method('__invoke')->willReturn($unknown_owner_class);
		$thankable = $this->factory->Create('a string', null, 1);

		$this->assertTrue($thankable instanceof Thankable);
		$this->assertSame($unknown_owner_class, $thankable->GetOwnerClassName());
	}

	public function testCreateUnknownSuccessfulNoDetails()
	{
		$unknown_owner_class_name = 'I have no idea...';
		$this->lmsg_mock->expects($this->once())->method('__invoke')->with('thankyou.thankable.not_found')
			->willReturn($unknown_owner_class_name);
		$thankable_no_details = $this->factory->CreateUnknown();

		$this->assertSame($unknown_owner_class_name, $thankable_no_details->GetName());
	}

	public function testCreateUnknownSuccessfulUser()
	{
		$name             = 'Unknown User';
		$owner_class_id   = PermOClass::INDIVIDUAL;
		$owner_class_name = "Some Owner Class Name";
		$this->lmsg_mock->expects($this->once())->method('__invoke')->with('thankyou.user.not_found')
			->willReturn($name);
		$this->thank_you_utility_mock->method('GetOwnerClassNamesFromIds')
			->willReturn([$owner_class_id => $owner_class_name]);
		$thankable_unknown_user = $this->factory->CreateUnknown(null, $owner_class_id);

		$this->assertSame($name, $thankable_unknown_user->GetName());
		$this->assertSame($owner_class_name, $thankable_unknown_user->GetOwnerClassName());
	}

	public function testCreateUnknownSuccessfulGroup()
	{
		$name             = 'Unknown Group';
		$owner_class_id   = PermOClass::GROUP;
		$owner_class_name = "Some Owner Class Name";
		$this->lmsg_mock->expects($this->once())->method('__invoke')->with('thankyou.group.not_found')
			->willReturn($name);
		$this->thank_you_utility_mock->method('GetOwnerClassNamesFromIds')
			->willReturn([$owner_class_id => $owner_class_name]);
		$thankable_unknown_group = $this->factory->CreateUnknown(null, $owner_class_id);

		$this->assertSame($name, $thankable_unknown_group->GetName());
		$this->assertSame($owner_class_name, $thankable_unknown_group->GetOwnerClassName());
	}
}
