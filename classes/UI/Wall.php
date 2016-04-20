<?php

namespace Claromentis\ThankYou\UI;
use Claromentis\ThankYou\ThanksRepository;

/**
 * Component displays list of recent thanks and allows submitting a new one.
 *
 * <component class="\Claromentis\ThankYou\UI\Say" allow_new="1">
 *
 * @author Alexander Polyanskikh
 */
class Wall extends \TemplaterComponentTmpl
{
	public function Show($attributes)
	{
		$args = array();

		$repository = new ThanksRepository();

		$user_id = (int)$attributes['user_id'];
		if (!$user_id)
			return "No user id given";

		$limit = isset($attributes['limit']) ? (int)$attributes['limit'] : 10;
		$thanks = $repository->GetForUser($user_id, $limit);

		$args['items.datasrc'] = [];
		foreach ($thanks as $item)
		{
			$users_dsrc = [];
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

			$args['items.datasrc'][] = [
				'users.datasrc' => $users_dsrc,

				'author_name.body' => \User::GetNameById($item->author),
				'author_link.href' => \User::GetProfileUrl($item->author),

				'description.body_html' => \ClaText::ProcessPlain($item->description),
				'has_description.visible' => strlen(trim($item->description)) > 0,
			];
		}

		if (isset($attributes['allow_new']) && !(bool)$attributes['allow_new'])
		{
			$args['allow_new.visible'] = 0;
		} else
		{
			$args['to_user_link.href'] = \User::GetProfileUrl($user_id);
			$args['to_user_name.body'] = \User::GetNameById($user_id);
			$args['thank_you_user.value'] = $user_id;
			$args['preselected_user.visible'] = 1;
			$args['select_user.visible'] = 0;
		}

		$template = 'thankyou/wall.html';
		return $this->CallTemplater($template, $args);
	}
}
