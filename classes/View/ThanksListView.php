<?php
namespace Claromentis\ThankYou\View;

use AuthUser;
use Carbon\Carbon;
use Claromentis\Core\Security\SecurityContext;
use Claromentis\ThankYou\ThanksItem;
use ClaText;
use Date;
use User;

/**
 * Displays list of "thank you" items
 */
class ThanksListView
{
	/**
	 * @var array
	 */
	protected $default_options = [
		'profile_images' => false
	];

	/**
	 * Build a datasource for the given thanks items.
	 *
	 * Options:
	 *   'profile_images' - Show profile images instead of names for thanked users
	 *
	 * @param ThanksItem[] $thanks
	 * @param array $options [optional]
	 * @param SecurityContext $context [optional]
	 * @return array
	 */
	public function Show($thanks, $options = [], SecurityContext $context = null)
	{
		$options = array_merge($this->default_options, $options);
		$context = $context ?: AuthUser::I()->GetContext();

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
					$user_tooltip = $options['profile_images'] ? $user_fullname : '';

					$users_dsrc[] = [
						'user_name.body' => $user_fullname,
						'user_name.visible' => !$options['profile_images'],
						'user_link.href' => User::GetProfileUrl($user_id),
						'user_link.title' => $user_tooltip,
						'profile_image.src' => User::GetPhotoUrl($user_id),
						'profile_image.visible' => $options['profile_images'],
						'delimiter_visible.visible' => !$options['profile_images']
					];
				}

				$users_dsrc[count($users_dsrc) - 1]['delimiter_visible.visible'] = 0;
			}

			$can_edit = (int) $context->GetUserId() === (int) $item->author;
			$date_created = new Date($item->date_created);

			$result[] = [
				'users.datasrc' => $users_dsrc,

				'author_name.body' => User::GetNameById($item->author),
				'author_link.href' => User::GetProfileUrl($item->author),
				'profile_image.src' => User::GetPhotoUrl($item->author),

				'description.body_html' => ClaText::ProcessPlain($item->description),
				'has_description.visible' => strlen(trim($item->description)) > 0,

				'like_component.object_id' => $item->id,

				'edit_thanks.visible' => $can_edit,
				'edit_thanks_link.data-id' => $item->id,
				'delete_thanks_link.data-id' => $item->id,


				'date_created.body' => Carbon::instance($date_created)->diffForHumans(),
				'date_created.title' => $date_created->getDate(DATE_FORMAT_CLA_LONG_DATE)
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
			$args['select_user.visible'] = 0;
			$args['preselected_user.visible'] = 1;
			$args['to_user_link.href'] = User::GetProfileUrl($user_id);
			$args['to_user_name.body'] = User::GetNameById($user_id);
			$args['thank_you_user.value'] = $user_id;
			$args['preselected_user.visible'] = 1;
			$args['select_user.visible'] = 0;
		}

		return $args;
	}
}
