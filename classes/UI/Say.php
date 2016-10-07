<?php

namespace Claromentis\ThankYou\UI;
use Claromentis\Core\Application;
use Claromentis\Core\Templater\Plugin\TemplaterComponentTmpl;
use Claromentis\ThankYou\ThanksRepository;
use Claromentis\ThankYou\View\ThanksListView;

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

		$view = new ThanksListView();
		$args['items.datasrc'] = $view->Show($thanks);

		if (isset($attributes['allow_new']) && !(bool)$attributes['allow_new'])
		{
			$args['allow_new.visible'] = 0;
		}

		$template = 'thankyou/say.html';
		return $this->CallTemplater($template, $args);
	}
}