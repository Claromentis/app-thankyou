<?php

namespace Claromentis\ThankYou\UI;
use Claromentis\Core\Application;
use Claromentis\Core\Templater\Plugin\TemplaterComponentTmpl;
use Claromentis\ThankYou\ThanksItem;
use Claromentis\ThankYou\ThanksRepository;
use Claromentis\ThankYou\View\ThanksListView;

/**
 * Component displays list of recent thanks for a particular user and allows submitting a new one.
 *
 * <component class="\Claromentis\ThankYou\UI\Wall" allow_new="1" user_id="123" limit="10">
 *
 * @author Alexander Polyanskikh
 */
class Wall extends TemplaterComponentTmpl
{
	public function Show($attributes, Application $app)
	{
		$args = array();

		/** @var ThanksRepository $repository */
		$repository = $app['thankyou.repository'];

		$user_id = (int)$attributes['user_id'];
		if (!$user_id)
			return "No user id given";

		$limit = isset($attributes['limit']) ? (int)$attributes['limit'] : 10;
		$thanks = $repository->GetForUser($user_id, $limit);

		$view = new ThanksListView();
		$args['items.datasrc'] = $view->Show($thanks);

		if (isset($attributes['allow_new']) && !(bool)$attributes['allow_new'])
		{
			$args['allow_new.visible'] = 0;
		} else
		{
			$args = $view->ShowAddNew($user_id) + $args;
		}

		$template = 'thankyou/wall.html';
		return $this->CallTemplater($template, $args);
	}
}
