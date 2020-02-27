<?php

namespace Claromentis\ThankYou\Thanked;

use Claromentis\Core\Acl\PermOClass;
use Claromentis\People\Entity\Group;

class ThankedGroup extends Thanked
{
	/**
	 * @var Group
	 */
	private $group;

	public function __construct(
		Group $group,
		string $owner_class_name,
		?int $id,
		?string $image_url,
		?string $object_url
	) {
		$this->group = $group;
		parent::__construct(
			$this->group->name,
			$this->group->extranet_id,
			$id,
			$image_url,
			$this->group->id,
			$object_url,
			PermOClass::GROUP,
			$owner_class_name
		);
	}

	public function GetGroup(): Group
	{
		return $this->group;
	}
}
