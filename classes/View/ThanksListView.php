<?php
namespace Claromentis\ThankYou\View;

use Claromentis\Core\Admin\AdminPanel;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Exception\ThankYouOClass;
use Claromentis\ThankYou\ThankYous\Thankable;
use Claromentis\ThankYou\ThankYous\ThankYou;
use Claromentis\ThankYou\ThankYous\ThankYouAcl;
use Claromentis\ThankYou\ThankYous\ThankYouUtility;
use DateTimeZone;
use User;
use UserExtranetArea;

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

	/**
	 * @param ThankYou             $thank_you
	 * @param DateTimeZone|null    $time_zone
	 * @param SecurityContext|null $security_context
	 * @return array[
	 *         author => [
	 *         id => int,
	 *         name => string
	 *         ],
	 *         date_created => Date,
	 *         description => string,
	 *         id => int|null,
	 *         thanked => null|array(see ConvertThankableToArray),
	 *         users => null|array[
	 *         id => int,
	 *         name => string
	 *         ]
	 *         ]
	 */
	public function ConvertThankYouToArray(ThankYou $thank_you, DateTimeZone $time_zone, ?SecurityContext $security_context = null): array
	{
		$date_created = clone $thank_you->GetDateCreated();
		$date_created->setTimezone($time_zone);

		if (isset($security_context) && !$this->acl->CanSeeThankYouAuthor($security_context, $thank_you))
		{
			$author_name = ($this->lmsg)('common.perms.hidden_name');
		} else
		{
			$author_name = $thank_you->GetAuthor()->GetFullname();
		}

		$output = [
			'author'       => [
				'id'   => $thank_you->GetAuthor()->GetId(),
				'name' => $author_name
			],
			'date_created' => $date_created,
			'description'  => $thank_you->GetDescription(),
			'id'           => $thank_you->GetId()
		];

		$thanked = $thank_you->GetThankable();
		if (isset($thanked))
		{
			foreach ($thanked as $offset => $thank)
			{
				$thanked[$offset] = $this->ConvertThankableToArray($thank, $security_context);
			}
		}
		$output['thanked'] = $thanked;

		$users = $thank_you->GetUsers();
		if (isset($users))
		{
			foreach ($users as $offset => $user)
			{
				$users[$offset] = ['id' => $user->GetId(), 'name' => $user->GetFullname()];
			}
		}
		$output['users'] = $users;

		return $output;
	}

	/**
	 * @param Thankable            $thankable
	 * @param SecurityContext|null $security_context
	 * @return array:
	 *         [
	 *         id => int|null,
	 *         extranet_area_id => int|null,
	 *         name    => string,
	 *         object_type => null|[
	 *         id  =>  int,
	 *         name => string
	 *         ]
	 *         ]
	 */
	public function ConvertThankableToArray(Thankable $thankable, ?SecurityContext $security_context = null): array
	{
		$object_type    = null;
		$object_type_id = $thankable->GetOwnerClass();
		if (isset($object_type_id))
		{
			try
			{
				$owner_class_name = $this->utility->GetOwnerClassNamesFromIds([$object_type_id])[0];
			} catch (ThankYouOClass $exception)
			{
				$owner_class_name = '';
			}
			$object_type = ['id' => $object_type_id, 'name' => $owner_class_name];
		}

		if (isset($security_context) && !$this->acl->CanSeeThankableName($security_context, $thankable))
		{
			$name = ($this->lmsg)('common.perms.hidden_name');
		} else
		{
			$name = $thankable->GetName();
		}

		$output = [
			'id'               => $thankable->GetId(),
			'extranet_area_id' => $thankable->GetExtranetId(),
			'name'             => $name,
			'object_type'      => $object_type
		];

		return $output;
	}
}
