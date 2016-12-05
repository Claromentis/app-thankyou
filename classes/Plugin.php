<?php

namespace Claromentis\ThankYou;
use Claromentis\Core\Application;
use Claromentis\Core\Templater\Plugin\TemplaterComponent;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 *
 * @author Alexander Polyanskikh
 */
class Plugin implements TemplaterComponent, ServiceProviderInterface
{
	/**
	 * Registers services on the given container.
	 *
	 * This method should only be used to configure services and parameters.
	 * It should not get services.
	 *
	 * @param Container $app A container instance
	 */
	public function register(Container $app)
	{
		$app['thankyou.repository'] = function ($app) {
			return new ThanksRepository($app->db);
		};

		// pages component
		$app['pages.component.thankyou'] = function()
		{
			return new UI\PagesComponent();
		};

		$app['localization.domain.thankyou'] = function ($app) {
			return $app['localization.domain_from_files_factory']('thankyou');
		};
	}

	public function Show($attr, Application $app)
	{
		switch ($attr['page'])
		{
			case 'viewprofile.tab_nav':
				if (empty($attr['user_id']) || !is_numeric($attr['user_id']))
					return '';
				/** @var ThanksRepository $repository */
				$repository = $app['thankyou.repository'];
				$count = $repository->GetCount($attr['user_id']);
				return '<li><a href="#thanks"><span class="cla-icon-thumbs-up"></span> Thanks (<b>'.$count.'</b>)</a></li>';
			case 'viewprofile.tab_content':
				if (empty($attr['user_id']) || !is_numeric($attr['user_id']))
					return '';
				$component = new UI\Wall();
				$component_data = $component->Show(array('user_id' => $attr['user_id']), $app);
				return '<div id="thanks">' . $component_data . '</div>';
		}
		return '';
	}
}