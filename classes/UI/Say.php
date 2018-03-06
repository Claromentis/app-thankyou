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
 */
class Say extends TemplaterComponentTmpl
{
	/**
	 * @var array
	 */
	protected $default_attributes = [
		'allow_new' => true,
		'profile_images' => false,
		'limit' => 10,
		'user_id' => 0
	];

	/**
	 * @param $attributes
	 * @param Application $app
	 * @return string
	 */
	public function Show($attributes, Application $app)
	{
		$attributes = array_merge($this->default_attributes, $attributes);
		$args = array();

		/** @var ThanksRepository $repository */
		$repository = $app['thankyou.repository'];

		$thanks = $repository->GetRecent($attributes['limit']);

		/**
		 * @var ThanksListView $view
		 */
		$view = $app['thankyou.thanks_list_view'];
		$args['items.datasrc'] = $view->Show($thanks, $attributes, $app->security);

		if (isset($attributes['allow_new']) && !(bool) $attributes['allow_new'])
		{
			$args['allow_new.visible'] = 0;
		}

		$args['no_thanks.body'] = lmsg('thankyou.component.no_thanks_all');

		return $this->CallTemplater('thankyou/say.html', $args);
	}
}