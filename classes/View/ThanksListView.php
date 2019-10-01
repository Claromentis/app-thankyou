<?php
namespace Claromentis\ThankYou\View;

use AuthUser;
use Carbon\Carbon;
use Claromentis\Core\Admin\AdminPanel;
use Claromentis\Core\Date\DateFormatter;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\Api;
use Claromentis\ThankYou\Exception\ThankYouRuntimeException;
use Claromentis\ThankYou\ThanksItem;
use Claromentis\ThankYou\ThankYous\Thankable;
use Claromentis\ThankYou\ThankYous\ThankYou;
use ClaText;
use Date;
use DateClaTimeZone;
use DateTimeZone;
use User;

/**
 * Displays list of "thank you" items
 */
class ThanksListView
{
	/**
	 * @var AdminPanel
	 */
	protected $panel;

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
	 * @param AdminPanel $panel
	 * @param Api        $api
	 */
	public function __construct(AdminPanel $panel, Api $api)
	{
		$this->api   = $api;
		$this->panel = $panel;
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
	 * @return array
	 * @throws ThankYouRuntimeException
	 */
	public function ConvertThankYouToArray(ThankYou $thank_you, ?DateTimeZone $time_zone = null): array
	{
		if (!isset($time_zone))
		{
			$time_zone = DateClaTimeZone::GetDefaultTZ();
		}

		$date_created = clone $thank_you->GetDateCreated();
		$date_created->setTimezone($time_zone);

		$output = [
			'author'       => [
				'id'   => $thank_you->GetAuthor()->GetId(),
				'name' => $thank_you->GetAuthor()->GetFullname()
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
				$thanked[$offset] = $this->ConvertThankableToArray($thank);
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
	 * @return array
	 * @throws ThankYouRuntimeException
	 */
	public function ConvertThankableToArray(Thankable $thankable): array
	{
		$object_type    = null;
		$object_type_id = $thankable->GetObjectTypeId();
		if (isset($object_type_id))
		{
			$object_type = ['id' => $object_type_id, 'name' => $this->api->ThankYous()->GetThankableObjectTypesNamesFromIds($object_type_id)];
		}

		$output = [
			'id'               => $thankable->GetId(),
			'extranet_area_id' => $thankable->GetExtranetAreaId(),
			'name'             => $thankable->GetName(),
			'object_type'      => $object_type
		];

		return $output;
	}
}
