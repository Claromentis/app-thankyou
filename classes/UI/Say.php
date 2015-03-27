<?php

namespace Claromentis\ThankYou\UI;
use Claromentis\ThankYou\ThanksRepository;

/**
 * Description of Say.php
 *
 * @author Alexander Polyanskikh
 */
class Say extends \TemplaterComponentTmpl
{

	public function Show($attributes)
	{
		$args = array();

		$repository = new ThanksRepository();

		$thanks = $repository->GetRecent(10);

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

		$template = 'thankyou/say.html';
		return $this->CallTemplater($template, $args);
	}
}