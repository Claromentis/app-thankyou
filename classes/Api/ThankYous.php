<?php

namespace Claromentis\ThankYou\Api;

use Claromentis\Core\Config\Config;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Constants;
use Claromentis\ThankYou\Exception\ThankYouForbidden;
use Claromentis\ThankYou\Exception\ThankYouInvalidThankable;
use Claromentis\ThankYou\Exception\ThankYouInvalidUsers;
use Claromentis\ThankYou\Exception\ThankYouNotFound;
use Claromentis\ThankYou\Exception\ThankYouRuntimeException;
use Claromentis\ThankYou\LineManagerNotifier;
use Claromentis\ThankYou\ThankYous\Thankable;
use Claromentis\ThankYou\ThankYous\ThankYou;
use Claromentis\ThankYou\ThankYous\ThankYouAcl;
use Claromentis\ThankYou\ThankYous\ThankYousRepository;
use Claromentis\ThankYou\View\ThanksListView;
use Date;
use DateClaTimeZone;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use LogicException;
use NotificationMessage;
use User;

class ThankYous
{
	private $acl;

	private $config;

	private $line_manager_notifier;

	private $thank_yous_repository;

	private $thank_yous_view;

	public function __construct(LineManagerNotifier $line_manager_notifier, ThankYousRepository $thank_yous_repository, Config $config, ThankYouAcl $acl, ThanksListView $thank_yous_view)
	{
		$this->acl                   = $acl;
		$this->config                = $config;
		$this->line_manager_notifier = $line_manager_notifier;
		$this->thank_yous_repository = $thank_yous_repository;
		$this->thank_yous_view       = $thank_yous_view;
	}

	public function CanDeleteThankYou(ThankYou $thank_you, SecurityContext $security_context)
	{
		return $this->acl->CanDeleteThankYou($thank_you, $security_context);
	}

	public function CanEditThankYou(ThankYou $thank_you, SecurityContext $security_context)
	{
		return $this->acl->CanEditThankYou($thank_you, $security_context);
	}

	/**
	 * @param Thankable|Thankable[] $thankables
	 * @param SecurityContext|null  $security_context
	 * @return array
	 */
	public function ConvertThankablesToArrays($thankables, ?SecurityContext $security_context = null)
	{
		$array_return = true;
		if (!is_array($thankables))
		{
			$array_return = false;
			$thankables   = [$thankables];
		}

		$thankables_array = [];
		foreach ($thankables as $thankable)
		{
			$thankables_array[] = $this->thank_yous_view->ConvertThankableToArray($thankable, $security_context);
		}

		return $array_return ? $thankables_array : $thankables_array[0];
	}

	/**
	 * @param ThankYou|ThankYou[]  $thank_yous
	 * @param DateTimeZone|null    $time_zone
	 * @param SecurityContext|null $security_context
	 * @return array
	 */
	public function ConvertThankYousToArrays($thank_yous, ?DateTimeZone $time_zone = null, ?SecurityContext $security_context = null)
	{
		if (!isset($time_zone))
		{
			$time_zone = DateClaTimeZone::GetCurrentTZ();
		}

		$array_return = true;
		if (!is_array($thank_yous))
		{
			$array_return = false;
			$thank_yous   = [$thank_yous];
		}

		$thank_yous_array = [];
		foreach ($thank_yous as $thank_you)
		{
			$thank_yous_array[] = $this->thank_yous_view->ConvertThankYouToArray($thank_you, $time_zone, $security_context);
		}

		return $array_return ? $thank_yous_array : $thank_yous_array[0];
	}

	public function CreateAndSave(User $user, array $thanked, string $description, ?Date $date_created = null)
	{
		$thank_you = $this->thank_yous_repository->Create($user, $description, $date_created);

		$thankables = $this->thank_yous_repository->CreateThankablesFromOClasses($thanked);
		$thank_you->SetThanked($thankables);

		$this->thank_yous_repository->PopulateThankYouUsersFromThankables($thank_you);

		$id = $this->thank_yous_repository->SaveToDb($thank_you);

		$thanked_users = $thank_you->GetUsers();
		$users_ids     = [];
		foreach ($thanked_users as $thanked_user)
		{
			$users_ids[] = $thanked_user->GetId();
		}

		try
		{
			NotificationMessage::AddApplicationPrefix('thankyou', 'thankyou');

			$params = [
				'author'              => $user->GetFullName(),
				'other_people_number' => count($users_ids) - 1,
				'description'         => $description,
			];
			NotificationMessage::Send('thankyou.new_thanks', $params, $users_ids, Constants::IM_TYPE_THANKYOU);

			if ($this->config->Get('notify_line_manager'))
			{
				//TODO: Fix mad spam if a big group is thanked (build $user_ids from thanked users only, don't affect the notification code above
				$this->line_manager_notifier->SendMessage($description, $users_ids);
			}
		} catch (Exception $exception)
		{
			throw new LogicException("Unexpected Exception thrown by NotificationMessage library", null, $exception);
		}

		return $id;
	}

	/**
	 * @param int $o_class
	 * @param int $id
	 * @return Thankable
	 * @throws InvalidArgumentException
	 * @throws ThankYouInvalidUsers
	 */
	public function CreateThankableFromOClass(int $o_class, int $id)
	{
		return $this->thank_yous_repository->CreateThankablesFromOClasses([['oclass' => $o_class, 'id' => $id]])[0];
	}

	/**
	 * @param int|int[] $object_types_id
	 * @return string|string[]
	 * @throws ThankYouRuntimeException
	 */
	public function GetThankableObjectTypesNamesFromIds($object_types_id)
	{
		$array_return = true;
		if (!is_array($object_types_id))
		{
			$array_return    = false;
			$object_types_id = [$object_types_id];
		}

		$names = $this->thank_yous_repository->GetThankableObjectTypesNamesFromIds($object_types_id);

		return $array_return ? $names : $names[0];
	}

	/**
	 * @param int|int[] $ids
	 * @param bool      $thanked
	 * @param bool      $users
	 * @return ThankYou|ThankYou[]
	 * @throws ThankYouRuntimeException
	 * @throws ThankYouInvalidThankable
	 * @throws ThankYouNotFound
	 * @throws LogicException
	 */
	public function GetThankYous($ids, bool $thanked = false, bool $users = false)
	{
		$array_return = true;
		if (!is_array($ids))
		{
			$array_return = false;
			$ids          = [$ids];
		}

		$thank_yous = $this->thank_yous_repository->GetThankYous($ids, $thanked, $users);

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
	 * @param int  $limit
	 * @param int  $offset
	 * @param bool $thanked
	 * @return ThankYou[]
	 * @throws ThankYouInvalidThankable
	 * @throws ThankYouRuntimeException
	 * @throws LogicException
	 */
	public function GetRecentThankYous(int $limit, int $offset = 0, bool $thanked = false)
	{
		$thank_you_ids = $this->thank_yous_repository->GetRecentThankYousIds($limit, $offset);

		try
		{
			return $this->GetThankYous($thank_you_ids, $thanked);
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
	 * @return ThankYou[]
	 * @throws ThankYouRuntimeException
	 * @throws ThankYouInvalidThankable
	 * @throws ThankYouNotFound
	 * @throws LogicException
	 */
	public function GetUsersRecentThankYous(int $user_id, int $limit, int $offset = 0, bool $thanked = false)
	{
		$thank_you_ids = $this->thank_yous_repository->GetUsersRecentThankYousIdsFromDb($user_id, $limit, $offset);

		try
		{
			return $this->GetThankYous($thank_you_ids, $thanked);
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

	public function UpdateAndSave(SecurityContext $security_context, int $id, ?array $thanked = null, ?string $description = null)
	{
		$thank_you = $this->thank_yous_repository->GetThankYous([$id], false)[$id];

		if (!$this->CanEditThankYou($thank_you, $security_context))
		{
			throw new ThankYouForbidden("Failed to Update Thank You, User is not the Author and does not have administrative privileges");
		}

		if (isset($description))
		{
			$thank_you->SetDescription($description);
		}

		if (isset($thanked))
		{
			$thankables = $this->thank_yous_repository->CreateThankablesFromOClasses($thanked);
			$thank_you->SetThanked($thankables);
			$this->thank_yous_repository->PopulateThankYouUsersFromThankables($thank_you);
		}

		return $this->thank_yous_repository->SaveToDb($thank_you);
	}

	/**
	 * @param SecurityContext $security_context
	 * @param int             $id
	 * @throws ThankYouForbidden
	 * @throws ThankYouNotFound
	 * @throws ThankYouNotFound
	 * @throws LogicException
	 */
	public function Delete(SecurityContext $security_context, int $id)
	{
		$thank_you = $this->thank_yous_repository->GetThankYous([$id], false)[$id];

		if (!$this->CanDeleteThankYou($thank_you, $security_context))
		{
			throw new ThankYouForbidden("Failed to Update Thank You, User is not the Author and does not have administrative privileges");
		}

		$this->thank_yous_repository->DeleteFromDb($id);
	}

}
