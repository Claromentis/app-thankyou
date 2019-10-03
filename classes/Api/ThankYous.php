<?php

namespace Claromentis\ThankYou\Api;

use Claromentis\Core\Admin\AdminPanel;
use Claromentis\Core\Config\Config;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Constants;
use Claromentis\ThankYou\Exception\ThankYouForbidden;
use Claromentis\ThankYou\Exception\ThankYouInvalidThankable;
use Claromentis\ThankYou\Exception\ThankYouNotFound;
use Claromentis\ThankYou\Exception\ThankYouRuntimeException;
use Claromentis\ThankYou\LineManagerNotifier;
use Claromentis\ThankYou\ThankYous\ThankYou;
use Claromentis\ThankYou\ThankYous\ThankYouAcl;
use Claromentis\ThankYou\ThankYous\ThankYousRepository;
use Date;
use Exception;
use LogicException;
use NotificationMessage;
use User;

class ThankYous
{
	private $acl;

	private $config;

	private $line_manager_notifier;

	private $thank_yous;

	public function __construct(LineManagerNotifier $line_manager_notifier, ThankYousRepository $thank_yous, Config $config, ThankYouAcl $acl)
	{
		$this->acl = $acl;
		$this->config = $config;
		$this->line_manager_notifier = $line_manager_notifier;
		$this->thank_yous = $thank_yous;
	}

	public function CanDeleteThankYou(ThankYou $thank_you, SecurityContext $security_context)
	{
		return $this->acl->CanDeleteThankYou($thank_you, $security_context);
	}

	public function CanEditThankYou(ThankYou $thank_you, SecurityContext $security_context)
	{
		return $this->acl->CanEditThankYou($thank_you, $security_context);
	}

	public function CreateAndSave(User $user, array $thanked, string $description, ?Date $date_created = null)
	{
		$thank_you = $this->thank_yous->Create($user, $description, $date_created);

		$thankables = $this->thank_yous->CreateThankablesFromOClasses($thanked);
		$thank_you->SetThanked($thankables);

		$this->thank_yous->PopulateThankYouUsersFromThankables($thank_you);

		$this->thank_yous->SaveToDb($thank_you);

		$thanked_users = $thank_you->GetUsers();
		$users_ids = [];
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
				$this->line_manager_notifier->SendMessage($description, $users_ids);
			}
		} catch (Exception $exception)
		{
			throw new LogicException("Unexpected Exception thrown by NotificationMessage library", null, $exception);
		}
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
			$array_return = false;
			$object_types_id = [$object_types_id];
		}

		$names = $this->thank_yous->GetThankableObjectTypesNamesFromIds($object_types_id);

		return $array_return ?  $names : $names[0];
	}

	/**
	 * @param int|int[] $ids
	 * @param bool $thanked
	 * @return ThankYou|ThankYou[]
	 * @throws ThankYouRuntimeException
	 * @throws ThankYouInvalidThankable
	 * @throws ThankYouNotFound
	 * @throws LogicException
	 */
	public function GetThankYous($ids, bool $thanked = false)
	{
		$array_return = true;
		if (!is_array($ids))
		{
			$array_return = false;
			$ids = [$ids];
		}

		$thank_yous = $this->thank_yous->GetThankYous($ids, $thanked);

		return $array_return ? $thank_yous : $thank_yous[$ids[0]];
	}

	/**
	 * @param int      $limit
	 * @param int      $offset
	 * @param bool     $thanked
	 * @param int|null $viewing_user_id
	 * @return ThankYou[]
	 * @throws ThankYouInvalidThankable
	 * @throws ThankYouRuntimeException
	 * @throws LogicException
	 */
	public function GetRecentThankYous(int $limit, int $offset = 0, bool $thanked = false, ?int $viewing_user_id = null)
	{
		$extranet_area_id = null;
		if (isset($viewing_user_id))
		{
			$users = $this->thank_yous->GetUsers([$viewing_user_id]);
			if (!isset($users[$viewing_user_id]))
			{
				throw new ThankYouRuntimeException("Failed to Get Recent Thank Yous, User not found");
			}

			$extranet_area_id = $users[$viewing_user_id]->GetExAreaId();

			if (is_string($extranet_area_id))
			{
				$extranet_area_id = (int) $extranet_area_id;
			}
		}

		$thank_you_ids = $this->thank_yous->GetRecentThankYousIdsFromDb($limit, $offset, $extranet_area_id);

		try
		{
			return $this->GetThankYous($thank_you_ids, $thanked);
		} catch (ThankYouNotFound $thank_you_not_found)
		{
			throw new LogicException("Unexpected ThankYouNotFound Exception thrown by GetThankYous", null, $thank_you_not_found);
		}
	}

	public function UpdateAndSave(SecurityContext $security_context, int $id, ?array $thanked = null, ?string $description = null)
	{
		$thank_you = $this->thank_yous->GetThankYous([$id], false)[$id];

		if (!$this->acl->CanEditThankYou($thank_you, $security_context))
		{
			throw new ThankYouForbidden("Failed to Update Thank You, User is not the Author and does not have administrative privileges");
		}

		if (isset($description))
		{
			$thank_you->SetDescription($description);
		}

		if (isset($thanked))
		{
			$thankables = $this->thank_yous->CreateThankablesFromOClasses($thanked);
			$thank_you->SetThanked($thankables);
			$this->thank_yous->PopulateThankYouUsersFromThankables($thank_you);
		}
		$this->thank_yous->SaveToDb($thank_you);
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
		$thank_you = $this->thank_yous->GetThankYous([$id], false)[$id];

		if (!$this->CanEditThankYou($thank_you, $security_context))
		{
			throw new ThankYouForbidden("Failed to Update Thank You, User is not the Author and does not have administrative privileges");
		}

		$this->thank_yous->DeleteFromDb($id);
	}

}
