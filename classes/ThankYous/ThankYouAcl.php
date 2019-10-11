<?php

namespace Claromentis\ThankYou\ThankYous;

use Claromentis\Core\Admin\AdminPanel;
use Claromentis\Core\Security\SecurityContext;

class ThankYouAcl
{
	private $admin_panel;

	public function __construct(AdminPanel $admin_panel)
	{
		$this->admin_panel = $admin_panel;
	}

	/**
	 * @param ThankYou        $thank_you
	 * @param SecurityContext $security_context
	 * @return bool
	 */
	public function CanDeleteThankYou(ThankYou $thank_you, SecurityContext $security_context): bool
	{
		return $thank_you->GetAuthor()->GetId() === $security_context->GetUser()->GetId() || $this->IsAdmin($security_context);
	}

	/**
	 * @param ThankYou        $thank_you
	 * @param SecurityContext $security_context
	 * @return bool
	 */
	public function CanEditThankYou(ThankYou $thank_you, SecurityContext $security_context): bool
	{
		return $thank_you->GetAuthor()->GetId() === $security_context->GetUser()->GetId() || $this->IsAdmin($security_context);
	}

	/**
	 * @param SecurityContext $security_context
	 * @return bool
	 */
	public function IsAdmin(SecurityContext $security_context): bool
	{
		return $this->admin_panel->IsAccessible($security_context);
	}
}
