<?php

namespace Claromentis\ThankYou\ThankYous;

use Claromentis\Core\Acl\PermOClass;
use Claromentis\Core\Admin\AdminPanel;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\People\Entity\User;
use Claromentis\People\PeopleAcl;
use Claromentis\ThankYou\Thanked\Thanked;

class ThankYouAcl
{
	/**
	 * PeopleAcl
	 */
	private $people_acl;

	/**
	 * @var AdminPanel
	 */
	private $admin_panel;

	/**
	 * ThankYouAcl constructor.
	 *
	 * @param PeopleAcl  $people_acl
	 * @param AdminPanel $admin_panel
	 */
	public function __construct(PeopleAcl $people_acl, AdminPanel $admin_panel)
	{
		$this->people_acl  = $people_acl;
		$this->admin_panel = $admin_panel;
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
		$author_id = $thank_you->GetAuthor()->id;

		return ($author_id !== 0 && $author_id === $context->GetUser()->GetId()) || $this->IsAdmin($context);
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
		$author_id = $thank_you->GetAuthor()->id;

		return ($author_id !== 0 && $author_id === $context->GetUser()->GetId()) || $this->IsAdmin($context);
	}

	/**
	 * Determines whether a Security Context can view a Thanked's Name.
	 *
	 * @param SecurityContext $context
	 * @param Thanked         $thanked
	 * @return bool
	 */
	public function CanSeeThankedName(SecurityContext $context, Thanked $thanked): bool
	{
		$thanked_extranet_id = $thanked->GetExtranetId();
		$owner_class         = $thanked->GetOwnerClass();
		$item_id             = $thanked->GetItemId();

		if (isset($item_id))
		{
			if ($owner_class === PermOClass::INDIVIDUAL)
			{
				return $this->people_acl->CanViewUser($context, $item_id);
			} elseif ($owner_class === PermOClass::GROUP)
			{
				return $this->people_acl->CanViewGroup($context, $item_id);
			}
		}

		if (isset($thanked_extranet_id))
		{
			return $this->people_acl->CanViewExtranet($context, $thanked_extranet_id);
		}

		return true;
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
		return $this->people_acl->CanViewUser($context, $thank_you->GetAuthor());
	}

	/**
	 * Determines whether a Security Context can view a User's details.
	 * If the User's Extranet is not set, `false` is returned.
	 *
	 * @param SecurityContext $context
	 * @param User            $user
	 * @return bool
	 */
	public function CanSeeThankedUser(SecurityContext $context, User $user): bool
	{
		return $this->people_acl->CanViewUser($context, $user);
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
}
