<?php
namespace Claromentis\ThankYou\View;

use AuthUser;
use Carbon\Carbon;
use Claromentis\Core\Admin\AdminPanel;
use Claromentis\Core\CDN\CDNSystemException;
use Claromentis\Core\Date\DateFormatter;
use Claromentis\Core\Localization\Lmsg;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Exception\ThankYouRuntimeException;
use Claromentis\ThankYou\ThanksItem;
use Claromentis\ThankYou\ThankYous\Thankable;
use Claromentis\ThankYou\ThankYous\ThankYou;
use Claromentis\ThankYou\ThankYous\ThankYouAcl;
use Claromentis\ThankYou\ThankYous\ThankYousRepository;
use ClaText;
use Date;
use DateClaTimeZone;
use DateTimeZone;
use InvalidArgumentException;
use User;

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
	 * @var array
	 */
	protected $default_options = [
		'admin'          => false,
		'profile_images' => false
	];

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
	 * Build a datasource for the given thanks items.
	 *
	 * Options:
	 *   'profile_images' - Show profile images instead of names for thanked users
	 *   'admin'          - Allow users with admin panel access to edit/delete all thank you notes
	 *
	 * @param ThanksItem[]    $thanks
	 * @param array           $options [optional]
	 * @param SecurityContext $context [optional]
	 * @return array
	 */
	public function Show($thanks, $options = [], SecurityContext $context = null)
	{
		$options  = array_merge($this->default_options, $options);
		$context  = $context ?: AuthUser::I()->GetContext();
		$is_admin = $this->panel->IsAccessible($context);

		$result = [];

		foreach ($thanks as $item)
		{
			$users_dsrc = [];

			/** @var ThanksItem $item */
			if (count($item->GetUsers()) > 0)
			{
				foreach ($item->GetUsers() as $user_id)
				{
					$user_fullname = User::GetNameById($user_id);
					$user_tooltip  = $options['profile_images'] ? $user_fullname : '';

					$users_dsrc[] = [
						'user_name.body'            => $user_fullname,
						'user_name.visible'         => !$options['profile_images'],
						'user_link.href'            => User::GetProfileUrl($user_id),
						'user_link.title'           => $user_tooltip,
						'profile_image.src'         => User::GetPhotoUrl($user_id),
						'profile_image.visible'     => $options['profile_images'],
						'delimiter_visible.visible' => !$options['profile_images']
					];
				}

				$users_dsrc[count($users_dsrc) - 1]['delimiter_visible.visible'] = 0;
			}

			$is_author    = (int) $context->GetUserId() === (int) $item->author;
			$can_edit     = $is_author || ($options['admin'] && $is_admin);
			$date_created = new Date($item->date_created);

			$result[] = [
				'users.datasrc' => $users_dsrc,

				'author_name.body'  => User::GetNameById($item->author),
				'author_link.href'  => User::GetProfileUrl($item->author),
				'profile_image.src' => User::GetPhotoUrl($item->author),

				'description.body_html'   => ClaText::ProcessPlain($item->description),
				'has_description.visible' => strlen(trim($item->description)) > 0,

				'like_component.object_id' => $item->id,

				'edit_thanks.visible'        => $can_edit,
				'edit_thanks_link.data-id'   => $item->id,
				'delete_thanks_link.data-id' => $item->id,

				'date_created.body'  => Carbon::instance($date_created)->diffForHumans(),
				'date_created.title' => $date_created->getDate(DateFormatter::LONG_DATE)
			];
		}

		return $result;
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
	 * @param ThankYou          $thank_you
	 * @param DateTimeZone|null $time_zone
	 * @param int|null $viewing_ex_area_id
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
	public function ConvertThankYouToArray(ThankYou $thank_you, DateTimeZone $time_zone, ?int $viewing_ex_area_id = null): array
	{
		$date_created = clone $thank_you->GetDateCreated();
		$date_created->setTimezone($time_zone);

		if (isset($viewing_ex_area_id) && $thank_you->GetAuthor()->GetExAreaId() !== $viewing_ex_area_id)
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

		$thanked = $thank_you->GetThanked();
		if (isset($thanked))
		{
			foreach ($thanked as $offset => $thank)
			{
				$thanked[$offset] = $this->ConvertThankableToArray($thank, $viewing_ex_area_id);
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
	 * @param Thankable $thankable
	 * @param int|null $viewing_ex_area_id
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
	public function ConvertThankableToArray(Thankable $thankable, ?int $viewing_ex_area_id = null): array
	{
		$object_type    = null;
		$object_type_id = $thankable->GetObjectTypeId();
		if (isset($object_type_id))
		{
			$object_type = ['id' => $object_type_id, 'name' => $this->thank_yous_repository->GetThankableObjectTypesNamesFromIds([$object_type_id])[0]];
		}

		$ex_area_id = $thankable->GetExtranetAreaId();

		if (isset($viewing_ex_area_id) && isset($ex_area_id) && $viewing_ex_area_id !== $ex_area_id)
		{
			$name = ($this->lmsg)('common.perms.hidden_name');
		} else
		{
			$name = $thankable->GetName();
		}

		$output = [
			'id'               => $thankable->GetId(),
			'extranet_area_id' => $ex_area_id,
			'name'             => $name,
			'object_type'      => $object_type
		];

		return $output;
	}

	/**
	 * @param ThankYou[]           $thank_yous
	 * @param DateTimeZone         $time_zone
	 * @param bool                 $display_thanked_images
	 * @param bool                 $allow_new
	 * @param bool                 $allow_edit
	 * @param bool                 $allow_delete
	 * @param bool                 $links_enabled
	 * @param SecurityContext|null $security_context
	 * @param Thankable|null       $preselected_thankable
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public function GetThankYousListArgs(array $thank_yous, DateTimeZone $time_zone, bool $display_thanked_images = false, bool $allow_new = false, bool $allow_edit = true, bool $allow_delete = true, bool $links_enabled = true, ?SecurityContext $security_context = null, ?Thankable $preselected_thankable = null): array
	{
		$viewer_ex_area_id = null;
		if (isset($security_context))
		{
			$viewer_ex_area_id = (int) $security_context->GetExtranetAreaId();
		}

		$args            = [];
		$view_thank_yous = [];
		foreach ($thank_yous as $thank_you)
		{
			if (!($thank_you instanceof ThankYou))
			{
				throw new InvalidArgumentException("Failed to Display List of Thank Yous, array of Thank Yous must contain ThankYous only");
			}

			$author_hidden = false;
			if (isset($viewer_ex_area_id) && $viewer_ex_area_id !== (int) $thank_you->GetAuthor()->GetExAreaId())
			{
				$author_hidden = true;
			}

			try
			{
				$author_image_url = $author_hidden ? null : User::GetPhotoUrl($thank_you->GetAuthor()->GetId(), false);//TODO: Replace with a non-static post People API update
			} catch (CDNSystemException $CDN_system_exception)
			{
				//TODO: Logging
				$author_image_url = null;
			}
			$author_link  = $author_hidden ? null : User::GetProfileUrl($thank_you->GetAuthor()->GetId(), false);//TODO: Replace with a non-static post People API update
			$author_name  = $author_hidden ? ($this->lmsg)('common.perms.hidden_name') : $thank_you->GetAuthor()->GetFullname();
			$can_edit     = isset($id) && $allow_edit && (!isset($security_context) || $this->thank_you_acl->CanEditThankYou($thank_you, $security_context));
			$can_delete   = isset($id) && $allow_delete && (!isset($security_context) || $this->thank_you_acl->CanDeleteThankYou($thank_you, $security_context));
			$date_created = clone $thank_you->GetDateCreated();
			$date_created->setTimezone($time_zone);
			$id = $thank_you->GetId();

			$thankeds     = $thank_you->GetThanked();
			$view_thanked = [];
			if (isset($thankeds))
			{
				$total_thanked = count($thankeds);
				foreach ($thankeds as $offset => $thanked)
				{
					$thanked_ex_area_id = $thanked->GetExtranetAreaId();
					$thanked_hidden     = false;
					if (isset($viewer_ex_area_id) && isset($thanked_ex_area_id) && $viewer_ex_area_id !== $thanked_ex_area_id)
					{
						$thanked_hidden = true;
					}

					$image_url             = $thanked_hidden ? null : $thanked->GetImageUrl();
					$thanked_link          = $thanked_hidden ? null : $thanked->GetProfileUrl();
					$display_thanked_image = !$thanked_hidden && $display_thanked_images && isset($image_url);
					$thanked_tooltip       = $display_thanked_image ? $thanked->GetName() : '';
					$thanked_link_enabled  = !$thanked_hidden && $links_enabled && isset($thanked_link);
					$thanked_name          = $thanked_hidden ? ($this->lmsg)('common.perms.hidden_name') : $thanked->GetName();

					$view_thanked[] = [
						'thanked_name.body'         => $thanked_name,
						'thanked_name.visible'      => !$display_thanked_image,
						'thanked_link.visible'      => $thanked_link_enabled,
						'thanked_no_link.visible'   => !$thanked_link_enabled,
						'thanked_link.href'         => $thanked_link,
						'thanked_link.title'        => $thanked_tooltip,
						'profile_image.src'         => $image_url,
						'profile_image.visible'     => $display_thanked_image,
						'delimiter_visible.visible' => !($offset === $total_thanked - 1)
					];
				}
			}

			$view_thank_yous[] = [
				'users.datasrc' => $view_thanked,

				'author_name.body'  => $author_name,
				'author_link.href'  => $author_link,
				'profile_image.src' => $author_image_url,

				'description.body_html'   => ClaText::ProcessPlain($thank_you->GetDescription()),
				'has_description.visible' => strlen($thank_you->GetDescription()) > 0,

				'like_component.object_id' => $thank_you->GetId(),
				'like_component.visible'   => isset($id),

				'delete_thanks.visible'      => $can_delete,
				'edit_thanks.visible'        => $can_edit,
				'edit_thanks_link.data-id'   => $thank_you->GetId(),
				'delete_thanks_link.data-id' => $thank_you->GetId(),

				'date_created.body'  => Carbon::instance($date_created)->diffForHumans(),
				'date_created.title' => $date_created->getDate(DateFormatter::LONG_DATE)
			];
		}

		$args['items.datasrc'] = $view_thank_yous;

		if (count($args['items.datasrc']) === 0)
		{
			$args['no_thanks.body'] = ($this->lmsg)('thankyou.thanks_list.no_thanks');
		}

		if ($allow_new)
		{
			$args['allow_new.visible'] = 1;

			if (isset($preselected_thankable))
			{
				$args['preselected_thankable.json'] = $this->ConvertThankableToArray($preselected_thankable);
			}
		} else
		{
			$args['allow_new.visible'] = 0;
		}

		return $args;
	}
}
