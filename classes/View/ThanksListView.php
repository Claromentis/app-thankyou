<?php
namespace Claromentis\ThankYou\View;

use Carbon\Carbon;
use Claromentis\ThankYou\ThanksItem;
use Date;

/**
 * Displays list of "thank you" items
 */
class ThanksListView
{
	/**
	 * Build a datasource for the given thanks items.
	 *
	 * @param ThanksItem[] $thanks
	 * @return array
	 */
	public function Show($thanks)
	{
		$result = [];

		foreach ($thanks as $item)
		{
			$users_dsrc = [];

			/** @var ThanksItem $item */
			if (count($item->GetUsers()) > 0)
			{
				foreach ($item->GetUsers() as $user_id)
				{
					$users_dsrc[] = [
						'user_name.body' => \User::GetNameById($user_id),
						'user_link.href' => \User::GetProfileUrl($user_id),
						'delimiter_visible.visible' => 1,
					];
				}
				$users_dsrc[count($users_dsrc) - 1]['delimiter_visible.visible'] = 0;
			}

			$result[] = [
				'users.datasrc' => $users_dsrc,

				'author_name.body' => \User::GetNameById($item->author),
				'author_link.href' => \User::GetProfileUrl($item->author),

				'description.body_html' => \ClaText::ProcessPlain($item->description),
				'has_description.visible' => strlen(trim($item->description)) > 0,

				'date_created.body' => Carbon::instance(new Date($item->date_created))->diffForHumans()
			];
		}

		return $result;
	}


	public function ShowAddNew($user_id = null)
	{
		$args = [];

		$args['allow_new.visible'] = 1;
		if ($user_id)
		{
			$args['select_user.visible'] = 0;
			$args['preselected_user.visible'] = 1;
			$args['to_user_link.href'] = \User::GetProfileUrl($user_id);
			$args['to_user_name.body'] = \User::GetNameById($user_id);
			$args['thank_you_user.value'] = $user_id;
			$args['preselected_user.visible'] = 1;
			$args['select_user.visible'] = 0;
		}

		return $args;
	}
}