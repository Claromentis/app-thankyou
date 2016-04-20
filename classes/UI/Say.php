<?php

namespace Claromentis\ThankYou\UI;
use Claromentis\Core\Application;
use Claromentis\Core\Templater\Plugin\TemplaterComponentTmpl;
use Claromentis\ThankYou\ThanksRepository;

/**
 * Component displays list of recent thanks and allows submitting a new one.
 *
 * <component class="\Claromentis\ThankYou\UI\Say" allow_new="1" limit="10">
 *
 * @author Alexander Polyanskikh
 */
class Say extends TemplaterComponentTmpl
{
	public function Show($attributes, Application $app)
	{
		$args = array();

		/** @var ThanksRepository $repository */
		$repository = $app['thankyou.repository'];

		$thanks = $repository->GetRecent(isset($attributes['limit']) ? (int)$attributes['limit'] : 10);

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
		}

		$template = 'thankyou/say.html';
		return $this->CallTemplater($template, $args);
	}
}