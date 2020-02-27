<?php

namespace Claromentis\ThankYou\Thanked;

use Claromentis\Core\Acl\PermOClass;
use Claromentis\People\Entity\User;

class ThankedUser extends Thanked
{
	/**
	 * @var User
	 */
	private $user;

	public function __construct(User $user, string $owner_class_name, ?int $id, ?string $image_url, ?string $profile_url)
	{
		$this->user = $user;
		parent::__construct(
			$this->user->getFullname(),
			$this->user->extranet_id,
			$id,
			$image_url,
			$this->user->id,
			$profile_url,
			PermOClass::INDIVIDUAL,
			$owner_class_name
		);
	}

	public function GetUser(): User
	{
		return $this->user;
	}
}
