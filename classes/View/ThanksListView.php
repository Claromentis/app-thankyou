<?php
namespace Claromentis\ThankYou\View;

use Claromentis\Core\Admin\AdminPanel;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Exception\ThankYouRuntimeException;
use Claromentis\ThankYou\ThankYous\Thankable;
use Claromentis\ThankYou\ThankYous\ThankYou;
use Claromentis\ThankYou\ThankYous\ThankYouAcl;
use Claromentis\ThankYou\ThankYous\ThankYousRepository;
use DateTimeZone;
use User;
use UserExtranetArea;

/**
 * Displays list of "thank you" items
 */
class ThanksListView
{
	private $lmsg;

	protected $panel;

	private $thank_yous_repository;

	private $thank_you_acl;

	/**
	 * Create a new list view for thank you notes.
	 *
	 * @param AdminPanel          $panel
	 * @param ThankYousRepository $thank_yous_repository
	 * @param ThankYouAcl         $thank_you_acl
	 * @param Lmsg                $lmsg
	 */
	public function __construct(AdminPanel $panel, ThankYousRepository $thank_yous_repository, ThankYouAcl $thank_you_acl, Lmsg $lmsg)
	{
		$this->lmsg                  = $lmsg;
		$this->panel                 = $panel;
		$this->thank_yous_repository = $thank_yous_repository;
		$this->thank_you_acl = $thank_you_acl;
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
	 *     author => [
	 *         id => int,
	 *         name => string
	 *     ],
	 *     date_created => Date,
	 *     description => string,
	 *     id => int|null,
	 *     thanked => null|array(see ConvertThankableToArray),
	 *     users => null|array[
	 *         id => int,
	 *         name => string
	 *     ]
	 * ]
	 * @throws ThankYouRuntimeException
	 */
	public function ConvertThankYouToArray(ThankYou $thank_you, DateTimeZone $time_zone, ?SecurityContext $security_context = null): array
	{
		$date_created = clone $thank_you->GetDateCreated();
		$date_created->setTimezone($time_zone);

		$author_extranet_id = $thank_you->GetAuthor()->GetExAreaId();

		$author_hidden = isset($security_context) &&
			!$security_context->IsPrimaryExtranet()
			&& $author_extranet_id !== UserExtranetArea::GetPrimaryId()
			&& $author_extranet_id !== $security_context->GetExtranetAreaId();

		if ($author_hidden)
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
	 * [
	 *     id => int|null,
	 *     extranet_area_id => int|null,
	 *     name    => string,
	 *     object_type => null|[
	 *         id  =>  int,
	 *         name => string
	 *     ]
	 * ]
	 * @throws ThankYouRuntimeException
	 */
	public function ConvertThankableToArray(Thankable $thankable, ?SecurityContext $security_context = null): array
	{
		$object_type    = null;
		$object_type_id = $thankable->GetOwnerClass();
		if (isset($object_type_id))
		{
			$object_type = ['id' => $object_type_id, 'name' => $this->thank_yous_repository->GetThankableObjectTypesNamesFromIds([$object_type_id])[0]];
		}

		$thankable_extranet_id = $thankable->GetExtranetId();

		$thankable_hidden = isset($security_context) &&
			!$security_context->IsPrimaryExtranet()
			&& $thankable_extranet_id !== UserExtranetArea::GetPrimaryId()
			&& $thankable_extranet_id !== $security_context->GetExtranetAreaId();

		if ($thankable_hidden)
		{
			$name = ($this->lmsg)('common.perms.hidden_name');
		} else
		{
			$name = $thankable->GetName();
		}

		$output = [
			'id'               => $thankable->GetId(),
			'extranet_area_id' => $thankable_extranet_id,
			'name'             => $name,
			'object_type'      => $object_type
		];

		return $output;
	}
}
