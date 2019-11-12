<?php

namespace Claromentis\ThankYou\Tags;

use Claromentis\Core\Admin\AdminPanel;
use Claromentis\Core\Security\SecurityContext;

class TagAcl
{
	private $admin_panel;

	public function __construct(AdminPanel $admin_panel)
	{
		$this->admin_panel = $admin_panel;
	}

	public function CanDeleteTag(SecurityContext $context)
	{
		return $this->admin_panel->IsAccessible($context);
	}
}
