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
			$args['items.datasrc'][] = [
				'user_name.body' => \User::GetNameById($item->user_id),
				'user_link.href' => \User::GetProfileUrl($item->user_id),

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
			$args['select_user.visible'] = 0;
			$args['preselected_user.visible'] = 1;
			$args['to_user_link.href'] = \User::GetProfileUrl($user_id);
			$args['to_user_name.body'] = \User::GetNameById($user_id);
			$args['thank_you_user.value'] = $user_id;
		}

		$template = 'thankyou/wall.html';
		return $this->CallTemplater($template, $args);
	}
}
