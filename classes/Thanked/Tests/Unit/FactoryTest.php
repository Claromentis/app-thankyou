<?php

use Analogue\ORM\EntityCollection;
use Claromentis\Core\Acl\PermOClass;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\People\Entity\Group;
use Claromentis\People\Repository\GroupRepository;
use Claromentis\People\Repository\UserRepository;
use Claromentis\ThankYou\Thanked\Factory;
use Claromentis\ThankYou\Thanked\ThankedInterface;
use Claromentis\ThankYou\ThankYous\ThankYouUtility;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
	//TODO: Add testing for User Entities once the usage of static User methods have been removed from Create.
	/**
	 * @var EntityCollection|MockObject
	 */
	private $entity_collection_mock;

	/**
	 * @var Factory
	 */
	private $factory;

	/**
	 * @var GroupRepository|MockObject
	 */
	private $group_repository_mock;

	/**
	 * @var Lmsg|MockObject
	 */
	private $lmsg_mock;

	/**
	 * @var ThankYouUtility|MockObject
	 */
	private $thank_you_utility_mock;

	/**
	 * @var UserRepository|MockObject
	 */
	private $user_repository_mock;

	public function SetUp()
	{
		$this->lmsg_mock              = $this->createMock(Lmsg::class);
		$this->thank_you_utility_mock = $this->createMock(ThankYouUtility::class);
		$this->user_repository_mock   = $this->createMock(UserRepository::class);
		$this->group_repository_mock  = $this->createMock(GroupRepository::class);

		$this->factory = new Factory($this->lmsg_mock, $this->thank_you_utility_mock, $this->user_repository_mock, $this->group_repository_mock);

		$this->entity_collection_mock = $this->createMock(EntityCollection::class);
	}

	public function testCreateGroupsSuccessful()
	{
		$entity_ids         = [1, 2];
		$unknown_group_name = "Unknown Group";
		$owner_class_name   = 'Group';

		$group_mock = $this->createMock(Group::class);
		$group_mock->expects($this->at(0))->method('__get')->with('name')->willReturn('Group 1');

		$group_entities = [1 => $group_mock];

		$this->group_repository_mock->expects($this->once())->method('find')->with($entity_ids)->willReturn($this->entity_collection_mock);
		$this->entity_collection_mock->method('getDictionary')->willReturn($group_entities);
		$this->lmsg_mock->expects($this->once())->method('__invoke')->with('thankyou.thanked.group.deleted')
			->willReturn($unknown_group_name);
		$this->thank_you_utility_mock->expects($this->exactly(2))->method('GetOwnerClassName')->with(PermOClass::GROUP)->willReturn($owner_class_name);

		$thankeds = $this->factory->Create(PermOClass::GROUP, $entity_ids);
		$this->assertCount(count($entity_ids), $thankeds);
		$this->assertInstanceOf(ThankedInterface::class, $thankeds[1]);
		$this->assertInstanceOf(ThankedInterface::class, $thankeds[2]);
		$this->assertSame($owner_class_name, $thankeds[1]->GetOwnerClassName());
		$this->assertSame($owner_class_name, $thankeds[2]->GetOwnerClassName());
		$this->assertSame($unknown_group_name, $thankeds[2]->GetName());
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
