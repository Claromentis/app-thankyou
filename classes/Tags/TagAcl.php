<?php

namespace Claromentis\ThankYou\Tags;

use Claromentis\Core\Admin\AdminPanel;
use Claromentis\Core\Security\SecurityContext;

class TagAcl
{
	/**
	 * @var AdminPanel $admin_panel
	 */
	private $admin_panel;

	/**
	 * TagAcl constructor.
	 *
	 * @param AdminPanel $admin_panel
	 */
	public function __construct(AdminPanel $admin_panel)
	{
		$this->admin_panel = $admin_panel;
	}

	/**
	 * Determine whether the Security Context can Delete a Tag.
	 *
	 * @param SecurityContext $context
	 * @return bool
	 */
	public function CanDeleteTag(SecurityContext $context): bool
	{
		return $this->admin_panel->IsAccessible($context);
	}
}
