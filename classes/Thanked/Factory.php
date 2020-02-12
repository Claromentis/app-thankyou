<?php

namespace Claromentis\ThankYou\Thanked;

use Analogue\ORM\Exceptions\MappingException;
use Claromentis\Core\Acl\PermOClass;
use Claromentis\Core\CDN\CDNSystemException;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\People\Entity;
use Claromentis\People\Repository\GroupRepository;
use Claromentis\People\Repository\UserRepository;
use Claromentis\ThankYou\Exception\OwnerClassNameException;
use Claromentis\ThankYou\ThankYous\ThankYouUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use User;

class Factory implements LoggerAwareInterface
{
	use LoggerAwareTrait;

	/**
	 * @var GroupRepository
	 */
	private $group_repository;

	/**
	 * @var Lmsg
	 */
	private $lmsg;

	/**
	 * @var UserRepository
	 */
	private $user_repository;

	/**
	 * @var ThankYouUtility
	 */
	private $utility;

	/**
	 * Factory constructor.
	 *
	 * @param Lmsg            $lmsg
	 * @param ThankYouUtility $utility
	 * @param UserRepository  $user_repository
	 * @param GroupRepository $group_repository
	 */
	public function __construct(Lmsg $lmsg, ThankYouUtility $utility, UserRepository $user_repository, GroupRepository $group_repository)
	{
		$this->lmsg             = $lmsg;
		$this->utility          = $utility;
		$this->user_repository  = $user_repository;
		$this->group_repository = $group_repository;

		$this->logger = new NullLogger();
	}

	/**
	 * Given an Owner Class ID and an array of entity IDs, creates an array of Thankeds.
	 *
	 * @param int   $owner_class_id
	 * @param array $item_ids
	 * @return ThankedInterface[]
	 */
	public function Create(int $owner_class_id, array $item_ids): array
	{
		$thankeds = [];
		switch ($owner_class_id)
		{
			case PermOClass::INDIVIDUAL:
				try
				{
					$users = $this->user_repository->find($item_ids)->getDictionary();
				} catch (MappingException $e)
				{
					$users = [];
				}

				foreach ($item_ids as $user_id)
				{
					if (isset($users[$user_id]))
					{
						$thankeds[$user_id] = $this->CreateThankedUser($users[$user_id], null);
					} else
					{
						$thankeds[$user_id] = $this->CreateUnknown($owner_class_id);
					}
				}
				break;
			case PermOClass::GROUP:
				try
				{
					$groups = $this->group_repository->find($item_ids)->getDictionary();
				} catch (MappingException $e)
				{
					$groups = [];
				}

				foreach ($item_ids as $group_id)
				{
					if (isset($groups[$group_id]))
					{
						$thankeds[$group_id] = $this->CreateThankedGroup($groups[$group_id], null);
					} else
					{
						$thankeds[$group_id] = $this->CreateUnknown($owner_class_id);
					}
				}
				break;
			default:
				foreach ($item_ids as $item_id)
				{
					$thankeds[$item_id] = $this->CreateUnknown($owner_class_id);
				}
				break;
		}

		return $thankeds;
	}

	/**
	 * Given a User Entity, creates a ThankedUser.
	 *
	 * @param Entity\User $user - The Thanked User.
	 * @param int|null    $id   - The ID of the Thanked.
	 * @return ThankedUser
	 */
	public function CreateThankedUser(Entity\User $user, ?int $id): ThankedUser
	{
		$owner_class_name = $this->GetOwnerClassName(PermOClass::INDIVIDUAL);

		//TODO: Remove as this may later be determined from the User.
		try
		{
			$image_url = User::GetPhotoUrl($user->id);
		} catch (CDNSystemException $cdn_system_exception)
		{
			$this->logger->error("Failed to Get User's Photo URL when Creating Thanked: " . $cdn_system_exception->getMessage());
			$image_url = null;
		}

		//TODO: Replace with a non-static post People API update
		$profile_url = User::GetProfileUrl($user->id, false);

		return new ThankedUser($user, $owner_class_name, $id, $image_url, $profile_url);
	}

	/**
	 * Given a Group Entity, creates a ThankedGroup.
	 *
	 * @param Entity\Group $group
	 * @param int|null     $id
	 * @return ThankedGroup
	 */
	public function CreateThankedGroup(Entity\Group $group, ?int $id): ThankedGroup
	{
		return new ThankedGroup(
			$group,
			$this->GetOwnerClassName(PermOClass::GROUP),
			$id,
			null,
			null
		);
	}

	/**
	 * Creates and returns a Thanked to represent Thanked objects which could not be identified.
	 *
	 * @param int|null $owner_class_id
	 * @return Thanked
	 */
	public function CreateUnknown(?int $owner_class_id = null)
	{
		$owner_class_name = null;
		if (isset($owner_class_id))
		{
			$owner_class_name = $this->GetOwnerClassName($owner_class_id);
		}

		if ($owner_class_id === PermOClass::INDIVIDUAL)
		{
			$name = ($this->lmsg)('thankyou.thanked.user.deleted');
		} elseif ($owner_class_id === PermOClass::GROUP)
		{
			$name = ($this->lmsg)('thankyou.thanked.group.deleted');
		} else
		{
			$name = ($this->lmsg)('thankyou.thanked.deleted');
		}

		return new Thanked(
			$name,
			null,
			null,
			null,
			null,
			null,
			$owner_class_id,
			$owner_class_name
		);
	}

	/**
	 * @param int $owner_class_id
	 * @return string
	 */
	private function GetOwnerClassName(int $owner_class_id): string
	{
		try
		{
			return $this->utility->GetOwnerClassName($owner_class_id);
		} catch (OwnerClassNameException $exception)
		{
			return ($this->lmsg)('thankyou.owner_class.not_found');
		}
	}
}
