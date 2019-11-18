<?php

namespace Claromentis\ThankYou\Api;

use Claromentis\Core\Config\Config;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Constants;
use Claromentis\ThankYou\Exception\ThankableNotFound;
use Claromentis\ThankYou\Exception\ThankYouAuthor;
use Claromentis\ThankYou\Exception\ThankYouException;
use Claromentis\ThankYou\Exception\ThankYouForbidden;
use Claromentis\ThankYou\Exception\ThankYouNotFound;
use Claromentis\ThankYou\Exception\ThankYouOClass;
use Claromentis\ThankYou\Exception\ThankYouRepository;
use Claromentis\ThankYou\LineManagerNotifier;
use Claromentis\ThankYou\ThankYous\Thankable;
use Claromentis\ThankYou\ThankYous\ThankYou;
use Claromentis\ThankYou\ThankYous\ThankYouAcl;
use Claromentis\ThankYou\ThankYous\ThankYousRepository;
use Claromentis\ThankYou\ThankYous\ThankYouUtility;
use Date;
use DateTime;
use DateTimeZone;
use Exception;
use LogicException;
use NotificationMessage;
use User;

class ThankYous
{
	private $acl;

	private $config;

	private $line_manager_notifier;

	private $thank_yous_repository;

	private $utility;

	public function __construct(
		LineManagerNotifier $line_manager_notifier,
		ThankYousRepository $thank_yous_repository,
		Config $config,
		ThankYouAcl $acl,
		ThankYouUtility $thank_you_utility
	) {
		$this->acl                   = $acl;
		$this->config                = $config;
		$this->line_manager_notifier = $line_manager_notifier;
		$this->thank_yous_repository = $thank_yous_repository;
		$this->utility               = $thank_you_utility;
	}

	/**
	 * @param ThankYou        $thank_you
	 * @param SecurityContext $security_context
	 * @return bool
	 */
	public function CanDeleteThankYou(ThankYou $thank_you, SecurityContext $security_context)
	{
		return $this->acl->CanDeleteThankYou($thank_you, $security_context);
	}

	/**
	 * @param ThankYou        $thank_you
	 * @param SecurityContext $security_context
	 * @return bool
	 */
	public function CanEditThankYou(ThankYou $thank_you, SecurityContext $security_context)
	{
		return $this->acl->CanEditThankYou($thank_you, $security_context);
	}

	/**
	 * @param SecurityContext $security_context
	 * @param ThankYou        $thank_you
	 * @return bool
	 */
	public function CanSeeThankYouAuthor(SecurityContext $security_context, ThankYou $thank_you): bool
	{
		return $this->acl->CanSeeThankYouAuthor($security_context, $thank_you);
	}

	/**
	 * @param SecurityContext $security_context
	 * @param Thankable       $thankable
	 * @return bool
	 */
	public function CanSeeThankableName(SecurityContext $security_context, Thankable $thankable): bool
	{
		return $this->acl->CanSeeThankableName($security_context, $thankable);
	}

	/**
	 * @param User|int  $author
	 * @param string    $description
	 * @param Date|null $date_created
	 * @return ThankYou
	 * @throws ThankYouAuthor - If the Author could not be loaded.
	 */
	public function Create($author, string $description, ?Date $date_created = null)
	{
		return $this->thank_yous_repository->Create($author, $description, $date_created);
	}

	/**
	 * @param ThankYou $thank_you
	 * @throws ThankYouOClass
	 */
	public function PopulateThankYouUsersFromThankables(ThankYou $thank_you)
	{
		$this->thank_yous_repository->PopulateThankYouUsersFromThankables($thank_you);
	}

	/**
	 * @param ThankYou $thank_you
	 * @throws ThankYouException
	 */
	public function Notify(ThankYou $thank_you)
	{
		$thanked_users = $thank_you->GetUsers();
		if (!isset($thanked_users))
		{
			throw new ThankYouException("Failed to Notify Thanked Users, Thanked Users haven't been set");
		}

		$all_users_ids = [];
		foreach ($thanked_users as $thanked_user)
		{
			$all_users_ids[] = $thanked_user->GetId();
		}

		$description = $thank_you->GetDescription();

		try
		{
			NotificationMessage::AddApplicationPrefix('thankyou', 'thankyou');

			$params = [
				'author'              => $thank_you->GetAuthor()->GetFullName(),
				'other_people_number' => count($all_users_ids) - 1,
				'description'         => $description
			];
			NotificationMessage::Send('thankyou.new_thanks', $params, $all_users_ids, Constants::IM_TYPE_THANKYOU);

			if ($this->config->Get('notify_line_manager'))
			{
				$thankables = $thank_you->GetThankable();
				if (!isset($thankables))
				{
					throw new ThankYouException("Failed to Notify Thanked User's Line Managers");
				}

				$user_ids = [];
				foreach ($thankables as $thankable)
				{
					$owner_class_id = $thankable->GetOwnerClass();
					$thanked_id     = $thankable->GetId();
					if ($owner_class_id === PERM_OCLASS_INDIVIDUAL && isset($id))
					{
						$user_ids[] = $thanked_id;
					}
				}
				$this->line_manager_notifier->SendMessage($description, $user_ids);
			}
		} catch (ThankYouException $exception)
		{
			throw $exception;
		} catch (Exception $exception)
		{
			throw new LogicException("Unexpected Exception thrown by NotificationMessage library", null, $exception);
		}
	}

	/**
	 * @param int $o_class
	 * @param int $id
	 * @return Thankable
	 * @throws ThankYouOClass - If the Owner Class given is not supported.
	 * @throws ThankableNotFound - If the Thankable could not be found.
	 */
	public function CreateThankableFromOClass(int $o_class, int $id)
	{
		$thankables = $this->thank_yous_repository->CreateThankablesFromOClasses([['oclass' => $o_class, 'id' => $id]]);
		if (!isset($thankables[0]))
		{
			throw new ThankableNotFound("Thankable could not be found with Owner Class '" . $o_class . "' and ID '" . $id . "'");
		}

		return $thankables[0];
	}

	/**
	 * @param array $oclasses
	 * @return array|Thankable[]
	 * @throws ThankYouOClass - If one or more of the Owner Classes given is not supported.
	 */
	public function CreateThankablesFromOClasses(array $oclasses)
	{
		return $this->thank_yous_repository->CreateThankablesFromOClasses($oclasses);
	}

	/**
	 * @param int|int[] $object_types_id
	 * @return string|string[]
	 * @throws ThankYouOClass - If the Name of the oClass could not be determined.
	 */
	public function GetOwnerClassNamesFromIds(array $object_types_id)
	{
		return $this->utility->GetOwnerClassNamesFromIds($object_types_id);
	}

	/**
	 * @param int  $id
	 * @param bool $thanked
	 * @param bool $users
	 * @param bool $tags
	 * @return ThankYou
	 * @throws ThankYouNotFound - If the Thank You could not be found.
	 * @throws ThankYouOClass - If one or more Thankable's Owner Classes is not recognised.
	 */
	public function GetThankYou(int $id, bool $thanked = false, bool $users = false, bool $tags = false): ThankYou
	{
		return $this->GetThankYous([$id], $thanked, $users, $tags)[$id];
	}

	/**
	 * @param int|int[] $ids
	 * @param bool      $thanked
	 * @param bool      $users
	 * @param bool      $tags
	 * @return ThankYou|ThankYou[]
	 * @throws ThankYouOClass - If one or more Thankable's Owner Classes is not recognised.
	 * @throws ThankYouNotFound - If one or more Thank Yous could not be found.
	 */
	public function GetThankYous($ids, bool $thanked = false, bool $users = false, bool $tags = false)
		//TODO: Tighten inputs and outputs to be more specific.
	{
		$array_return = true;
		if (!is_array($ids))
		{
			$array_return = false;
			$ids          = [$ids];
		}

		$thank_yous = $this->thank_yous_repository->GetThankYous($ids, $thanked, $users, $tags);

		return $array_return ? $thank_yous : $thank_yous[$ids[0]];
	}

	/**
	 * Return total number of Thank Yous in the database
	 *
	 * @return int
	 */
	public function GetTotalThankYousCount(): int
	{
		return $this->thank_yous_repository->GetTotalThankYousCount();
	}

	/**
	 * @param int        $limit
	 * @param int        $offset
	 * @param array|null $date_range
	 * @param bool       $thanked
	 * @param bool       $users
	 * @param bool       $tags
	 * @return ThankYou[]
	 * @throws ThankYouOClass - If one or more Thankable's Owner Classes is not recognised.
	 */
	public function GetRecentThankYous(int $limit, int $offset = 0, ?array $date_range = null, bool $thanked = false, bool $users = false, bool $tags = false)
	{
		if (isset($date_range[0]) && ($date_range[0] instanceof DateTime))
		{
			/**
			 * @var DateTime $from_date
			 */
			$from_date = clone $date_range[0];
			$from_date->setTimezone(new DateTimeZone('UTC'));

			$date_range[0] = (int) $from_date->format('YmdHis');
		}
		if (isset($date_range[1]) && ($date_range[1] instanceof DateTime))
		{
			/**
			 * @var DateTime $from_date
			 */
			$to_date = clone $date_range[1];
			$to_date->setTimezone(new DateTimeZone('UTC'));

			$date_range[1] = (int) $to_date->format('YmdHis');
		}

		$thank_you_ids = $this->thank_yous_repository->GetRecentThankYousIds($limit, $offset, $date_range);

		try
		{
			return $this->GetThankYous($thank_you_ids, $thanked, $users, $tags);
		} catch (ThankYouNotFound $thank_you_not_found)
		{
			throw new LogicException("Unexpected ThankYouNotFound Exception thrown by GetThankYous", null, $thank_you_not_found);
		}
	}

	/**
	 * @return int[]
	 */
	public function GetThankableObjectTypes(): array
	{
		return $this->thank_yous_repository::THANKABLES;
	}

	/**
	 * @param int  $user_id
	 * @param int  $limit
	 * @param int  $offset
	 * @param bool $thanked
	 * @param bool $users
	 * @param bool $tags
	 * @return ThankYou[]
	 * @throws ThankYouOClass - If one or more Thankable's Owner Classes is not recognised.
	 */
	public function GetUsersRecentThankYous(int $user_id, int $limit, int $offset = 0, bool $thanked = false, bool $users = false, bool $tags = false)
	{
		$thank_you_ids = $this->thank_yous_repository->GetUsersRecentThankYousIdsFromDb($user_id, $limit, $offset);

		try
		{
			return $this->GetThankYous($thank_you_ids, $thanked, $users, $tags);
		} catch (ThankYouNotFound $thank_you_not_found)
		{
			throw new LogicException("Unexpected ThankYouNotFound Exception thrown by GetThankYous", null, $thank_you_not_found);
		}
	}

	/**
	 * @param int $user_id
	 * @return int
	 */
	public function GetUsersThankYousCount(int $user_id): int
	{
		return $this->thank_yous_repository->GetUsersThankYousCount($user_id);
	}

	/**
	 * @param SecurityContext $security_context
	 * @return bool
	 */
	public function IsAdmin(SecurityContext $security_context): bool
	{
		return $this->acl->IsAdmin($security_context);
	}

	/**
	 * @param ThankYou $thank_you
	 * @throws ThankYouNotFound - If the Thank You could not be found in the Repository.
	 * @throws ThankYouRepository - On failure to save to database.
	 */
	public function Save(ThankYou $thank_you)
	{
		$this->thank_yous_repository->SaveToDb($thank_you);
	}

	/**
	 * @param SecurityContext $security_context
	 * @param int             $id
	 * @throws ThankYouNotFound - If the Thank You could not be found.
	 * @throws ThankYouForbidden - If the Security Context's User does not have permission.
	 */
	public function Delete(SecurityContext $security_context, int $id)
	{
		try
		{
			$thank_you = $this->thank_yous_repository->GetThankYous([$id], false)[$id];
		} catch (ThankYouOClass $exception)
		{
			throw new LogicException("Unexpected Exception thrown by GetThankYous in Delete", null, $exception);
		}

		if (!$this->CanDeleteThankYou($thank_you, $security_context))
		{
			throw new ThankYouForbidden("Failed to Update Thank You, User is not the Author and does not have administrative privileges");
		}

		$this->thank_yous_repository->DeleteFromDb($id);
	}
}
