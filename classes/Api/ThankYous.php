<?php

namespace Claromentis\ThankYou\Api;

use Claromentis\Core\Config\Config;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Constants;
use Claromentis\ThankYou\Exception\ThankableNotFound;
use Claromentis\ThankYou\Exception\ThankYouAuthor;
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

	private $utility;

	public function __construct(LineManagerNotifier $line_manager_notifier, ThankYousRepository $thank_yous_repository, Config $config, ThankYouAcl $acl, ThanksListView $thank_yous_view, ThankYouUtility $thank_you_utility)
	{
		$this->acl                   = $acl;
		$this->config                = $config;
		$this->line_manager_notifier = $line_manager_notifier;
		$this->thank_yous_repository = $thank_yous_repository;
		$this->thank_yous_view       = $thank_yous_view;
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
	 * @param Thankable|Thankable[] $thankables
	 * @param SecurityContext|null  $security_context
	 * @return array
	 */
	public function ConvertThankablesToArrays($thankables, ?SecurityContext $security_context = null): array
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

//TODO: Tighten inputs and outputs
		return $array_return ? $thankables_array : $thankables_array[0];
	}

	/**
	 * @param ThankYou|ThankYou[]  $thank_yous
	 * @param DateTimeZone|null    $time_zone
	 * @param SecurityContext|null $security_context
	 * @return array
	 */
	public function ConvertThankYousToArrays($thank_yous, ?DateTimeZone $time_zone = null, ?SecurityContext $security_context = null): array
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
			if (!($thank_you instanceof ThankYou))
			{
				throw new InvalidArgumentException("Failed to Convert Thank Yous to array, 1st argument must be an array of ThankYous");
			}
			$thank_yous_array[] = $this->thank_yous_view->ConvertThankYouToArray($thank_you, $time_zone, $security_context);
		}

		return $array_return ? $thank_yous_array : $thank_yous_array[0];
	}

	/**
	 * @param User      $author
	 * @param array     $thanked
	 * @param string    $description
	 * @param Date|null $date_created
	 * @return int
	 * @throws ThankYouAuthor - If the Author could not be loaded.
	 * @throws ThankYouOClass - If one or more of the Owner Classes given is not supported.
	 * @throws ThankYouOClass - If one or more of the Owner Classes is not recognised.
	 * @throws ThankYouRepository - On failure to save to database.
	 */
	public function CreateAndSave(User $author, array $thanked, string $description, ?Date $date_created = null)
	{
		if (!$author->IsLoaded() && !$author->Load())
		{
			throw new ThankYouAuthor("Failed to create Thank You, could not load Author");
		}

		try
		{
			$thank_you = $this->thank_yous_repository->Create($author, $description, $date_created);
		} catch (ThankYouAuthor $exception)
		{
			throw new LogicException("Unexpected Exception thrown by Create in CreateAndSave", null, $exception);
		}

		$thankables = $this->thank_yous_repository->CreateThankablesFromOClasses($thanked);

		$thank_you->SetThanked($thankables);

		$this->thank_yous_repository->PopulateThankYouUsersFromThankables($thank_you);

		try
		{
			$id = $this->thank_yous_repository->SaveToDb($thank_you);
		} catch (ThankYouNotFound $exception)
		{
			throw new LogicException("Unexpected Exception thrown by SaveToDb", null, $exception);
		}

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
				'author'              => $author->GetFullName(),
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
	 * @param int|int[] $object_types_id
	 * @return string|string[]
	 * @throws ThankYouOClass
	 */
	public function GetOwnerClassNamesFromIds(array $object_types_id)
	{
		return $this->utility->GetOwnerClassNamesFromIds($object_types_id);
	}

	/**
	 * @param int  $id
	 * @param bool $thanked
	 * @param bool $users
	 * @return ThankYou
	 * @throws ThankYouNotFound - If the Thank You could not be found.
	 * @throws ThankYouOClass - If one or more Thankable's Owner Classes is not recognised.
	 */
	public function GetThankYou(int $id, bool $thanked = false, bool $users = false): ThankYou
	{
		return $this->GetThankYous([$id], $thanked, $users)[$id];
	}

	/**
	 * @param int|int[] $ids
	 * @param bool      $thanked
	 * @param bool      $users
	 * @return ThankYou|ThankYou[]
	 * @throws ThankYouOClass - If one or more Thankable's Owner Classes is not recognised.
	 * @throws ThankYouNotFound - If one or more Thank Yous could not be found.
	 */
	public function GetThankYous($ids, bool $thanked = false, bool $users = false)
		//TODO: Tighten inputs and outputs to be more specific.
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
	 * @throws ThankYouOClass - If one or more Thankable's Owner Classes is not recognised.
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
	 * @throws ThankYouOClass - If one or more Thankable's Owner Classes is not recognised.
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

	/**
	 * @param ThankYou $thank_you
	 * @throws ThankYouNotFound
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
