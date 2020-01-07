<?php

namespace Claromentis\ThankYou\ThankYous;

use Claromentis\Core\Admin\AdminPanel;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\People\Service\UserExtranetService;
use Claromentis\ThankYou\Thankable\Thankable;
use User;

class ThankYouAcl
{
	/**
	 * @var AdminPanel
	 */
	private $admin_panel;

	/**
	 * @var UserExtranetService
	 */
	private $user_extranet;

	/**
	 * ThankYouAcl constructor.
	 *
	 * @param AdminPanel          $admin_panel
	 * @param UserExtranetService $user_extranet_service
	 */
	public function __construct(AdminPanel $admin_panel, UserExtranetService $user_extranet_service)
	{
		$this->admin_panel   = $admin_panel;
		$this->user_extranet = $user_extranet_service;
	}

	/**
	 * Determines whether a Security Context can Delete a Thank You.
	 *
	 * @param SecurityContext $context
	 * @param ThankYou        $thank_you
	 * @return bool
	 */
	public function CanDeleteThankYou(SecurityContext $context, ThankYou $thank_you): bool
	{
		return $thank_you->GetAuthor()->GetId() === $context->GetUser()->GetId() || $this->IsAdmin($context);
	}

	/**
	 * Determines whether a Security Context can Edit a Thank You.
	 *
	 * @param SecurityContext $context
	 * @param ThankYou        $thank_you
	 * @return bool
	 */
	public function CanEditThankYou(SecurityContext $context, ThankYou $thank_you): bool
	{
		return $thank_you->GetAuthor()->GetId() === $context->GetUser()->GetId() || $this->IsAdmin($context);
	}

	/**
	 * Determines whether a Security Context can view a Thankable's Name.
	 *
	 * @param SecurityContext $context
	 * @param Thankable       $thankable
	 * @return bool
	 */
	public function CanSeeThankableName(SecurityContext $context, Thankable $thankable): bool
	{
		$thankable_extranet_id = $thankable->GetExtranetId();

		return !isset($thankable_extranet_id) || $this->IsExtranetVisible($context, $thankable_extranet_id);
	}

	/**
	 * Determines whether a Security Context can view a Thank You Author's Name.
	 * If the Author's Extranet is not set, `false` is returned.
	 *
	 * @param SecurityContext $context
	 * @param ThankYou        $thank_you
	 * @return bool
	 */
	public function CanSeeThankYouAuthor(SecurityContext $context, ThankYou $thank_you): bool
	{
		return $this->CanSeeUser($context, $thank_you->GetAuthor());
	}

	/**
	 * Determines whether a Security Context can view a User's details.
	 * If the User's Extranet is not set, `false` is returned.
	 *
	 * @param SecurityContext $context
	 * @param User            $user
	 * @return bool
	 */
	public function CanSeeUser(SecurityContext $context, User $user): bool
	{
		$user_extranet_id = $user->GetExAreaId();

		if (!isset($user_extranet_id))
		{
			return false;
		}

		return $this->IsExtranetVisible($context, $user_extranet_id);
	}

	/**
	 * Determines whether a Security Context has access to the Thank You Admin.
	 *
	 * @param SecurityContext $context
	 * @return bool
	 */
	public function IsAdmin(SecurityContext $context): bool
	{
		return $this->admin_panel->IsAccessible($context);
	}

	/**
	 * Determines whether an Extranet Area is visible to a particular User.
	 *
	 * @param SecurityContext $context
	 * @param int             $target_extranet_id
	 * @return bool
	 */
	public function IsExtranetVisible(SecurityContext $context, int $target_extranet_id): bool
	{
		$user_extranet_id    = $context->GetExtranetAreaId();
		$primary_extranet_id = (int) $this->user_extranet->GetPrimaryId();

		return $target_extranet_id === $primary_extranet_id || $user_extranet_id === $primary_extranet_id || $target_extranet_id === $user_extranet_id;
	}
}
