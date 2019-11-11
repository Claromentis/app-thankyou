<?php
namespace Claromentis\ThankYou\View;

use Claromentis\Core\Admin\AdminPanel;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\ThankYou\ThankYous\ThankYouAcl;
use Claromentis\ThankYou\ThankYous\ThankYouUtility;
use User;

/**
 * Displays list of "thank you" items
 */
class ThanksListView
{
	/**
	 * @var ThankYouAcl
	 */
	private $acl;

	/**
	 * @var Lmsg
	 */
	private $lmsg;

	/**
	 * @var AdminPanel
	 */
	protected $panel;

	/**
	 * @var ThankYouUtility
	 */
	private $utility;

	/**
	 * Create a new list view for thank you notes.
	 *
	 * @param AdminPanel      $panel
	 * @param ThankYouUtility $utility
	 * @param ThankYouAcl     $thank_you_acl
	 * @param Lmsg            $lmsg
	 */
	public function __construct(AdminPanel $panel, ThankYouUtility $utility, ThankYouAcl $thank_you_acl, Lmsg $lmsg)
	{
		$this->acl     = $thank_you_acl;
		$this->lmsg    = $lmsg;
		$this->panel   = $panel;
		$this->utility = $utility;
	}

	/**
	 * Build arguments for the add new thanks modal.
	 *
	 * @param int $user_id [optional]
	 * @return array
	 */
	public function ShowAddNew($user_id = null)
	{
		$args = [];

		$args['allow_new.visible'] = 1;

		if ($user_id)
		{
			$args['select_user.visible']      = 0;
			$args['preselected_user.visible'] = 1;
			$args['to_user_link.href']        = User::GetProfileUrl($user_id);
			$args['to_user_name.body']        = User::GetNameById($user_id);
			$args['thank_you_user.value']     = $user_id;
			$args['preselected_user.visible'] = 1;
			$args['select_user.visible']      = 0;
		}

		return $args;
	}
}
