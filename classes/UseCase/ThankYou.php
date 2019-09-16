<?php

namespace Claromentis\ThankYou\UseCase;

use AuthUser;
use Claromentis\Core\Admin\AdminPanel;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Constants;
use Claromentis\ThankYou\Exception\ThankYouForbidden;
use Claromentis\ThankYou\Exception\ThankYouInvalidUsers;
use Claromentis\ThankYou\Exception\ThankYouNotFound;
use Claromentis\ThankYou\LineManagerNotifier;
use Claromentis\ThankYou\ThanksItemFactory;
use Date;
use Exception;
use LogicException;
use NotificationMessage;

class ThankYou
{
	private $line_manager_notifier;

	private $thanks_item_factory;

	public function __construct(LineManagerNotifier $line_manager_notifier, ThanksItemFactory $thanks_item_factory)
	{
		$this->line_manager_notifier = $line_manager_notifier;
		$this->thanks_item_factory   = $thanks_item_factory;
	}

	/**
	 * @param array  $users_ids
	 * @param string $description
	 * @param bool   $notify_line_manager
	 * @throws ThankYouInvalidUsers
	 * @throws LogicException
	 */
	public function Create(array $users_ids, string $description, bool $notify_line_manager = false)
	{
		if (count($users_ids) === 0)
		{
			throw new ThankYouInvalidUsers("Failed to Create Thank You, at least one User must be thanked");
		}

		foreach ($users_ids as $offset => $user_id)
		{
			$users_ids[$offset] = (int) $user_id;
		}

		$thanks_item = $this->thanks_item_factory->Create();

		$thanks_item->SetAuthor(AuthUser::I()->GetId());
		$thanks_item->SetDateCreated(Date::getNowTimestamp());
		$thanks_item->SetDescription($description);
		$thanks_item->SetUsers($users_ids);
		$thanks_item->Save();

		try
		{
			NotificationMessage::AddApplicationPrefix('thankyou', 'thankyou');

			$params = [
				'author'              => AuthUser::I()->GetFullName(),
				'other_people_number' => count($users_ids) - 1,
				'description'         => $description,
			];
			NotificationMessage::Send('thankyou.new_thanks', $params, $users_ids, Constants::IM_TYPE_THANKYOU);

			if ($notify_line_manager)
			{
				$this->line_manager_notifier->SendMessage($description, $users_ids);
			}
		} catch (Exception $exception)
		{
			throw new LogicException("Unexpected Exception thrown by NotificationMessage library", null, $exception);
		}
	}

	/**
	 * @param SecurityContext $security_context
	 * @param int             $id
	 * @param array           $users_ids
	 * @param string          $description
	 * @param AdminPanel      $thank_you_admin_panel
	 * @throws ThankYouNotFound
	 * @throws ThankYouForbidden
	 */
	public function Update(SecurityContext $security_context, int $id, array $users_ids, string $description, AdminPanel $thank_you_admin_panel)
	{
		$thanks_item = $this->thanks_item_factory->Create();
		if (!$thanks_item->Load($id))
		{
			throw new ThankYouNotFound("Failed to Update Thank You, Thank You not found");
		}

		if ($thanks_item->GetAuthor() !== AuthUser::I()->GetId() && !$thank_you_admin_panel->IsAccessible($security_context))
		{
			throw new ThankYouForbidden("Failed to Update Thank You, User is not the Author and does not have administrative privileges");
		}

		$thanks_item->SetDescription($description);
		$thanks_item->SetUsers($users_ids);
		$thanks_item->Save();
	}

	public function Delete(SecurityContext $security_context, int $id, AdminPanel $thank_you_admin_panel)
	{
		$thanks_item = $this->thanks_item_factory->Create();
		if (!$thanks_item->Load($id))
		{
			throw new ThankYouNotFound("Failed to Update Thank You, Thank You not found");
		}

		if ($thanks_item->GetAuthor() !== AuthUser::I()->GetId() && !$thank_you_admin_panel->IsAccessible($security_context))
		{
			throw new ThankYouForbidden("Failed to Update Thank You, User is not the Author and does not have administrative privileges");
		}

		$thanks_item->Delete();
	}
}
